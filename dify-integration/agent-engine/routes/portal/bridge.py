#!/usr/bin/env python3
"""Hybrid 2-Way Bridge Task API Routes. Strictly complies with SOP 23 (<= 300 lines)."""
import os
import json
import logging
import urllib.request
from typing import Optional, Dict, Any, List
from pydantic import BaseModel
from fastapi import APIRouter, HTTPException, Request
from models import PortalCmsBridgeRequest
import db_service
import bridge_task_service

logger = logging.getLogger("marketing_engine")
router = APIRouter(tags=["Hybrid Bridge Tasks"])

class PollTasksRequest(BaseModel):
    license_key: str
    domain: str
    limit: Optional[int] = 5

class AckTaskRequest(BaseModel):
    task_id: str
    license_key: str
    status: str
    result_data: Optional[Dict[str, Any]] = None
    error_message: Optional[str] = None

class PublishHybridRequest(BaseModel):
    title: str
    content: str
    slug: Optional[str] = None
    category: Optional[str] = None
    categories: Optional[List[Any]] = None
    tags: Optional[List[str]] = None
    focus_keyword: Optional[str] = None
    meta_description: Optional[str] = None
    featured_image_url: Optional[str] = None
    post_type: Optional[str] = "post"
    status: Optional[str] = "draft"
    scheduled_at: Optional[str] = None

@router.post("/api/v1/bridge/tasks/poll")
def poll_tasks_api(req: PollTasksRequest):
    """WordPress Plugin gọi lên định kỳ để kéo các tác vụ cần thực thi (Bypass Cloudflare WAF)."""
    try:
        tasks = bridge_task_service.poll_pending_tasks(
            license_key=req.license_key,
            client_domain=req.domain,
            limit=req.limit or 5
        )
        return {
            "status": "success",
            "count": len(tasks),
            "tasks": tasks
        }
    except PermissionError as pe:
        logger.warning(f"Poll task từ chối quyền: {pe}")
        raise HTTPException(status_code=403, detail=str(pe))
    except Exception as e:
        logger.error(f"Lỗi poll tasks: {e}")
        raise HTTPException(status_code=500, detail=str(e))

@router.post("/api/v1/bridge/tasks/ack")
def ack_task_api(req: AckTaskRequest):
    """WordPress Plugin báo cáo kết quả sau khi thực thi xong tác vụ nội bộ."""
    try:
        bridge_task_service.ack_task_completion(
            task_id=req.task_id,
            license_key=req.license_key,
            status=req.status,
            result_data=req.result_data,
            error_message=req.error_message
        )
        return {
            "status": "success",
            "message": f"Đã ghi nhận kết quả cho tác vụ {req.task_id}."
        }
    except PermissionError as pe:
        raise HTTPException(status_code=403, detail=str(pe))
    except Exception as e:
        logger.error(f"Lỗi ack task: {e}")
        raise HTTPException(status_code=400, detail=str(e))

def ping_indexnow(domain: str, url_to_index: str) -> bool:
    """Bắn tín hiệu IndexNow tới các công cụ tìm kiếm (Bing, Yandex, Seznam) lập chỉ mục trong 5 phút."""
    try:
        import urllib.request
        data = {
            "host": domain,
            "key": "omni_indexnow_2026",
            "urlList": [url_to_index]
        }
        req = urllib.request.Request(
            "https://api.indexnow.org/indexnow",
            data=json.dumps(data).encode("utf-8"),
            headers={"Content-Type": "application/json; charset=utf-8", "User-Agent": "OmniMarketingSaaS/1.0"}
        )
        with urllib.request.urlopen(req, timeout=4) as resp:
            return resp.getcode() in (200, 202)
    except Exception as e:
        logger.info(f"IndexNow notification: {e}")
        return False

@router.post("/api/v1/portal/projects/{project_id}/publish-hybrid")
def publish_article_hybrid_api(project_id: str, req: PublishHybridRequest):
    """Xuất bản bài viết theo mô hình Hybrid thông minh & tự động ping IndexNow lập chỉ mục tức thì."""
    try:
        result = bridge_task_service.dispatch_hybrid_action(
            project_id=project_id,
            action="publish_article",
            payload=req.dict()
        )
        # Tự động ping IndexNow nếu có URL bài viết hoặc URL website
        indexnow_sent = False
        project = db_service.get_project_by_id(project_id) or {}
        dom = (project.get("target_website_url") or "").replace("https://", "").replace("http://", "").split("/")[0]
        article_url = result.get("post_url") or project.get("target_website_url")
        if dom and article_url:
            indexnow_sent = ping_indexnow(dom, article_url)

        return {"status": "success", "result": result, "indexnow_ping": indexnow_sent}
    except Exception as e:
        logger.error(f"Lỗi publish hybrid: {e}")
        raise HTTPException(status_code=400, detail=str(e))

@router.post("/api/v1/portal/projects/{project_id}/cms-bridge")
def portal_configure_cms_bridge(project_id: str, req: PortalCmsBridgeRequest):
    """Cấu hình và kiểm tra kết nối WordPress Omni Bridge từ giao diện Portal."""
    import urllib.request
    import json
    import db_service

    project = db_service.get_project_by_id(project_id)
    if not project:
        raise HTTPException(status_code=404, detail="Không tìm thấy dự án.")

    clean_url = req.cms_api_url.rstrip("/")
    if os.path.exists("/.dockerenv") and ("127.0.0.1:8080" in clean_url or "localhost:8080" in clean_url):
        clean_url = clean_url.replace("127.0.0.1:8080", "wordpress:80").replace("localhost:8080", "wordpress:80")
    clean_key = req.api_key.strip()
    health_endpoint = f"{clean_url}/wp-json/omni-bridge/v1/health"

    try:
        req_health = urllib.request.Request(
            health_endpoint,
            headers={
                "X-Omni-Bridge-Key": clean_key,
                "User-Agent": "Autonomous-Marketing-Agent/1.0",
            }
        )
        with urllib.request.urlopen(req_health, timeout=10) as resp:
            health_data = json.loads(resp.read().decode("utf-8"))
    except Exception as e:
        raise HTTPException(status_code=400, detail=f"Không thể kết nối đến Omni Bridge: {e}")

    with db_service.get_connection() as conn:
        with conn.cursor() as cur:
            cur.execute("""
                UPDATE projects 
                SET cms_type = %s, cms_api_url = %s, cms_auth_config = %s, updated_at = NOW()
                WHERE id = %s;
            """, (
                req.cms_type or "wordpress",
                clean_url,
                json.dumps({"api_key": clean_key}, ensure_ascii=False),
                project_id
            ))
            conn.commit()

    return {
        "status": "connected",
        "project_id": project_id,
        "cms_api_url": clean_url,
        "health": health_data,
        "message": "Kết nối Omni Bridge WordPress thành công!"
    }

