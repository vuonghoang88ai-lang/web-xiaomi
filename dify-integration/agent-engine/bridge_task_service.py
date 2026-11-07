#!/usr/bin/env python3
"""Hybrid 2-Way Bridge Task Queue Service (SOP 23 <= 300 lines). Bypasses Cloudflare WAF 100% via Reverse Task Queue."""
import os
import json
import logging
from typing import Dict, Any, List, Optional, Tuple
from datetime import datetime, timezone
import requests
from psycopg2.extras import RealDictCursor
import db_service
import license_service

logger = logging.getLogger("marketing_engine")

def ensure_bridge_tasks_schema():
    """Tạo bảng bridge_tasks nếu chưa có."""
    with db_service.get_connection() as conn:
        with conn.cursor() as cur:
            cur.execute("""
                CREATE TABLE IF NOT EXISTS bridge_tasks (
                    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                    project_id UUID NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
                    action VARCHAR(64) NOT NULL,
                    payload JSONB NOT NULL DEFAULT '{}'::jsonb,
                    status VARCHAR(32) NOT NULL DEFAULT 'pending',
                    retry_count INT DEFAULT 0,
                    result_data JSONB,
                    error_message TEXT,
                    created_at TIMESTAMPTZ DEFAULT NOW(),
                    executed_at TIMESTAMPTZ
                );
                CREATE INDEX IF NOT EXISTS idx_bridge_tasks_proj_status ON bridge_tasks(project_id, status);
            """)
            conn.commit()

def enqueue_task(project_id: str, action: str, payload: Dict[str, Any]) -> Dict[str, Any]:
    """Đẩy một task mới vào hàng đợi (Task Queue)."""
    ensure_bridge_tasks_schema()
    with db_service.get_connection() as conn:
        with conn.cursor(cursor_factory=RealDictCursor) as cur:
            cur.execute("""
                INSERT INTO bridge_tasks (project_id, action, payload, status)
                VALUES (%s, %s, %s, 'pending')
                RETURNING id, project_id, action, status, created_at;
            """, (project_id, action, json.dumps(payload)))
            row = cur.fetchone()
            conn.commit()
    return dict(row)

def poll_pending_tasks(license_key: str, client_domain: str, limit: int = 5) -> List[Dict[str, Any]]:
    """WordPress Plugin gọi lên để kéo các task pending về thực thi nội bộ (Vượt Cloudflare)."""
    ensure_bridge_tasks_schema()
    # 1. Xác thực License Key và Domain khóa cứng
    lic = license_service.verify_wp_heartbeat(license_key, client_domain)
    if not lic.get("valid"):
        raise PermissionError(lic.get("message", "Bản quyền không hợp lệ."))

    # 2. Tìm project_id từ license_key
    with db_service.get_connection() as conn:
        with conn.cursor(cursor_factory=RealDictCursor) as cur:
            cur.execute("SELECT id FROM projects WHERE license_key = %s LIMIT 1;", (license_key.strip(),))
            proj = cur.fetchone()
            if not proj:
                return []

            project_id = proj["id"]
            cur.execute("""
                SELECT id, action, payload, created_at
                FROM bridge_tasks
                WHERE project_id = %s AND status = 'pending'
                ORDER BY created_at ASC
                LIMIT %s;
            """, (project_id, limit))
            tasks = cur.fetchall()

            if tasks:
                task_ids = [str(t["id"]) for t in tasks]
                cur.execute("""
                    UPDATE bridge_tasks 
                    SET status = 'in_progress', retry_count = retry_count + 1 
                    WHERE id = ANY(%s::uuid[]);
                """, (task_ids,))
                conn.commit()

    return [
        {
            "task_id": str(t["id"]),
            "action": t["action"],
            "payload": t["payload"] if isinstance(t["payload"], dict) else json.loads(t["payload"]),
            "created_at": t["created_at"].isoformat() if t["created_at"] else None
        }
        for t in tasks
    ]

def ack_task_completion(task_id: str, license_key: str, status: str, result_data: Optional[Dict[str, Any]] = None, error_message: Optional[str] = None) -> bool:
    """WordPress Plugin báo cáo kết quả sau khi thực thi xong task."""
    ensure_bridge_tasks_schema()
    valid_status = "completed" if status.lower() in ("completed", "success") else "failed"

    with db_service.get_connection() as conn:
        with conn.cursor(cursor_factory=RealDictCursor) as cur:
            cur.execute("""
                SELECT bt.id, p.license_key 
                FROM bridge_tasks bt
                JOIN projects p ON bt.project_id = p.id
                WHERE bt.id = %s;
            """, (task_id,))
            row = cur.fetchone()
            if not row or row["license_key"] != license_key.strip():
                raise PermissionError("Task không tồn tại hoặc không khớp License Key.")

            cur.execute("""
                UPDATE bridge_tasks 
                SET status = %s, result_data = %s, error_message = %s, executed_at = NOW()
                WHERE id = %s;
            """, (valid_status, json.dumps(result_data or {}), error_message, task_id))
            conn.commit()
    return True

def dispatch_hybrid_action(project_id: str, action: str, payload: Dict[str, Any]) -> Dict[str, Any]:
    """Điều phối thông minh: Thử Instant Push trước, nếu gặp Cloudflare Challenge / lỗi mạng -> Tự động xếp hàng đợi Queue."""
    ensure_bridge_tasks_schema()
    with db_service.get_connection() as conn:
        with conn.cursor(cursor_factory=RealDictCursor) as cur:
            cur.execute("SELECT id, cms_api_url, cms_auth_config, target_website_url FROM projects WHERE id = %s;", (project_id,))
            proj = cur.fetchone()

    if not proj:
        raise ValueError("Không tìm thấy dự án.")

    cms_url = (proj.get("cms_api_url") or proj.get("target_website_url") or "").rstrip("/")
    if os.path.exists("/.dockerenv") and ("127.0.0.1:8080" in cms_url or "localhost:8080" in cms_url):
        cms_url = cms_url.replace("127.0.0.1:8080", "wordpress:80").replace("localhost:8080", "wordpress:80")
    api_key = (proj.get("cms_auth_config") or {}).get("api_key", "")

    # Nếu có cấu hình Push trực tiếp, thử Push trong 3.5s
    if cms_url and api_key and action == "publish_article":
        try:
            endpoint = f"{cms_url}/wp-json/omni-bridge/v1/publish"
            headers = {
                "Content-Type": "application/json",
                "X-Omni-Bridge-Key": api_key,
                "X-Omni-Bypass-Token": "omni_bypass_cloudflare_2026",
                "User-Agent": f"OmniBridgeConnector/1.3 (+{cms_url or 'https://marketing-ai.internal'})"
            }
            resp = requests.post(endpoint, json=payload, headers=headers, timeout=3.5)
            if resp.status_code == 200:
                data = resp.json()
                logger.info(f"Instant Push thành công cho project {project_id}: {data}")
                return {
                    "mode": "instant_push",
                    "status": "completed",
                    "message": "Đã xuất bản tức thì qua Instant Push.",
                    "data": data
                }
            else:
                logger.warning(f"Push trực tiếp bị từ chối (Status {resp.status_code}), chuyển sang Task Queue...")
        except Exception as push_err:
            logger.warning(f"Push trực tiếp gặp sự cố ({push_err}), tự động chuyển sang Task Queue...")

    # Fallback tự động: Xếp vào hàng đợi (100% vượt Cloudflare)
    queued = enqueue_task(project_id, action, payload)
    return {
        "mode": "hybrid_queue",
        "status": "queued",
        "task_id": str(queued["id"]),
        "message": "Đã xếp vào hàng đợi đồng bộ ngầm (Miễn nhiễm Cloudflare WAF). Plugin sẽ tự động kéo về xuất bản trong vòng 1-2 phút."
    }
