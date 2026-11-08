#!/usr/bin/env python3
"""Conversational Scheduling & Approval Engine (SOP 23 <= 300 lines, SOP 30 Zero Hardcoding)."""
import re
import json
import logging
from typing import Dict, Any, Optional, List, Tuple
from datetime import datetime, timedelta, timezone
from pydantic import BaseModel
from fastapi import APIRouter, HTTPException, Depends
from psycopg2.extras import RealDictCursor
import db_service
import bridge_task_service

logger = logging.getLogger("marketing_engine")
router = APIRouter(tags=["Conversational Scheduling & Approval"])
VN_TZ = timezone(timedelta(hours=7))

class ParseScheduleIntentRequest(BaseModel):
    message: str
    context_text: Optional[str] = None
    conversation_id: Optional[str] = None

class ScheduleTaskRequest(BaseModel):
    title: str
    content: str
    scheduled_at: str
    post_type: Optional[str] = "post"
    category: Optional[str] = None
    tags: Optional[List[str]] = None
    focus_keyword: Optional[str] = None
    auto_approve: Optional[bool] = False
    task_type: Optional[str] = "scheduled_publish"

def parse_relative_time_vn(text: str, base_dt: Optional[datetime] = None) -> Tuple[datetime, str]:
    """Thuật toán giải mã thời gian tương đối & tuyệt đối tiếng Việt (SOP 30 Zero Hardcoding)."""
    now = base_dt or datetime.now(VN_TZ)
    t = text.lower().strip()
    target_date = now.date()
    target_hour = 9
    target_minute = 0
    time_explicit = False

    time_match = re.search(r'(?:lúc\s*|vào\s*)?(\d{1,2})\s*[:h]\s*(\d{1,2})', t)
    if not time_match:
        time_match = re.search(r'(\d{1,2})\s*(?:h|giờ)\b', t)
    if not time_match:
        time_match = re.search(r'(?:lúc|vào)\s*(\d{1,2})\b', t)

    if time_match:
        h = int(time_match.group(1))
        m = int(time_match.group(2)) if len(time_match.groups()) >= 2 and time_match.group(2) else 0
        if 0 <= h <= 23 and 0 <= m <= 59:
            target_hour = h
            target_minute = m
            time_explicit = True

    if not time_explicit:
        if 'chiều' in t:
            target_hour = 14
        elif 'tối' in t:
            target_hour = 19
        elif 'sáng' in t:
            target_hour = 9

    day_offset_match = re.search(r'sau\s*(\d+)\s*ngày', t)
    if day_offset_match:
        target_date += timedelta(days=int(day_offset_match.group(1)))
    elif 'ngày kia' in t or 'hôm kia' in t or 'ngày mốt' in t:
        target_date += timedelta(days=2)
    elif 'ngày mai' in t or 'sáng mai' in t or 'chiều mai' in t or 'tối mai' in t or re.search(r'\bmai\b', t):
        target_date += timedelta(days=1)
    elif 'hôm nay' in t or 'nay' in t:
        target_date = now.date()
        if not time_explicit and now.hour >= 9:
            target_hour = min(23, now.hour + 2)
            target_minute = 0
    else:
        dow_map = {
            'hai': 0, '2': 0, 'ba': 1, '3': 1, 'tư': 2, 'bốn': 2, '4': 2,
            'năm': 3, '5': 3, 'sáu': 4, '6': 4, 'bảy': 5, '7': 5, 'chủ nhật': 6, 'cn': 6
        }
        dow_match = re.search(r'(?:thứ\s*|t)(hai|ba|tư|bốn|năm|sáu|bảy|[2-7]|chủ\s*nhật|cn)', t)
        if dow_match:
            target_dow = dow_map.get(dow_match.group(1).replace(' ', ''))
            if target_dow is not None:
                days_ahead = (target_dow - now.weekday()) % 7
                if days_ahead == 0 and (now.hour > target_hour or 'tuần sau' in t):
                    days_ahead += 7
                elif 'tuần sau' in t:
                    days_ahead += 7
                target_date += timedelta(days=days_ahead)
        else:
            if not time_explicit:
                target_date += timedelta(days=1)

    target_dt = datetime(target_date.year, target_date.month, target_date.day, target_hour, target_minute, 0, tzinfo=VN_TZ)
    if target_dt <= now and target_date == now.date():
        target_dt = now + timedelta(hours=2)

    weekday_vn = ['Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy', 'Chủ Nhật'][target_dt.weekday()]
    display_str = f"{target_dt.strftime('%H:%M')} - {weekday_vn}, {target_dt.strftime('%d/%m/%Y')}"
    return target_dt, display_str

def inspect_chat_context_asset(context_text: str) -> Dict[str, Any]:
    """Phân loại tài sản nội dung (Bài viết hay Kế hoạch) & trích xuất siêu dữ liệu chuẩn xác."""
    raw = (context_text or "").strip()
    is_deliverable = len(raw) > 500 and re.search(r'(^|\n)##?\s+', raw)
    h1_match = re.search(r'(?:^|\n)#\s+(.+)', raw)
    first_h2 = re.search(r'(?:^|\n)##\s+(.+)', raw)
    
    title = ""
    if h1_match:
        title = h1_match.group(1).strip()
    elif first_h2:
        title = first_h2.group(1).strip()
    else:
        lines = [l.strip() for l in raw.split('\n') if l.strip() and not l.strip().startswith(':::')]
        title = lines[0][:100] if lines else "Nội dung tiếp thị từ CMO"

    title = re.sub(r'^[#\*\-:\s]+', '', title)
    asset_type = "article_deliverable" if is_deliverable else "strategic_plan"

    lines = [l.strip() for l in raw.split('\n') if l.strip() and not l.strip().startswith('#') and not l.strip().startswith(':::')]
    summary = " ".join(lines[:3])[:220] + "..." if lines else ""

    post_type = "post"
    raw_lower = raw.lower()
    if 'sab_chapter' in raw_lower or 'chương ' in raw_lower or 'hồi ' in raw_lower:
        post_type = "sab_chapter"
    elif 'woocommerce' in raw_lower or 'giá bán' in raw_lower or 'thông số kỹ thuật' in raw_lower or 'sản phẩm' in raw_lower:
        post_type = "product"

    return {
        "asset_type": asset_type,
        "title": title,
        "summary": summary,
        "post_type": post_type,
        "char_count": len(raw),
        "clean_content": raw
    }

@router.post("/api/v1/portal/projects/{project_id}/parse-schedule-intent")
def parse_schedule_intent_api(project_id: str, req: ParseScheduleIntentRequest):
    """Phân tích ý định câu lệnh lập lịch & thấu cảm ngữ cảnh hội thoại."""
    msg = req.message.strip().lower()
    schedule_keywords = ['lập lịch', 'lên lịch', 'hẹn giờ', 'schedule', 'duyệt bài', 'phê duyệt', 'đăng lúc', 'đăng vào']
    is_schedule = any(k in msg for k in schedule_keywords) or msg.startswith('/schedule') or msg.startswith('/plan') or msg.startswith('/duyet')

    target_dt, display_time = parse_relative_time_vn(req.message)
    asset_info = inspect_chat_context_asset(req.context_text or "")

    return {
        "is_schedule_command": is_schedule,
        "scheduled_at": target_dt.isoformat(),
        "display_time": display_time,
        "asset_type": asset_info["asset_type"],
        "title": asset_info["title"],
        "summary": asset_info["summary"],
        "post_type": asset_info["post_type"],
        "char_count": asset_info["char_count"],
        "raw_content": asset_info["clean_content"]
    }

@router.post("/api/v1/portal/projects/{project_id}/schedule-task")
def schedule_task_api(project_id: str, req: ScheduleTaskRequest):
    """Tạo tác vụ Lập lịch & Phê duyệt trong bridge_tasks và đồng bộ sang WordPress."""
    bridge_task_service.ensure_bridge_tasks_schema()
    action = req.task_type or "scheduled_publish"
    status = "approved" if req.auto_approve else "pending_approval"
    
    payload = {
        "title": req.title,
        "content": req.content,
        "post_type": req.post_type or "post",
        "category": req.category,
        "tags": req.tags or [],
        "focus_keyword": req.focus_keyword or req.title,
        "status": "future",
        "scheduled_at": req.scheduled_at
    }

    with db_service.get_connection() as conn:
        with conn.cursor(cursor_factory=RealDictCursor) as cur:
            cur.execute("""
                INSERT INTO bridge_tasks (project_id, action, payload, status)
                VALUES (%s, %s, %s, %s)
                RETURNING id, project_id, action, status, created_at;
            """, (project_id, action, json.dumps(payload), status))
            task_row = cur.fetchone()
            conn.commit()

    task_id = str(task_row["id"])
    wp_result = None

    if req.auto_approve:
        try:
            wp_result = bridge_task_service.dispatch_hybrid_action(
                project_id=project_id,
                action="publish_article",
                payload=payload
            )
            with db_service.get_connection() as conn:
                with conn.cursor() as cur:
                    cur.execute("""
                        UPDATE bridge_tasks 
                        SET status = 'completed', result_data = %s, executed_at = NOW()
                        WHERE id = %s;
                    """, (json.dumps(wp_result), task_id))
                    conn.commit()
        except Exception as e:
            logger.warning(f"Lỗi khi điều phối lập lịch sang WordPress: {e}")

    return {
        "status": "success",
        "task_id": task_id,
        "task_status": status,
        "scheduled_at": req.scheduled_at,
        "wp_result": wp_result
    }

@router.post("/api/v1/portal/projects/{project_id}/approve-task/{task_id}")
def approve_scheduled_task_api(project_id: str, task_id: str):
    """Phê duyệt tác vụ đang chờ duyệt và kích hoạt đẩy sang WordPress Native."""
    bridge_task_service.ensure_bridge_tasks_schema()
    with db_service.get_connection() as conn:
        with conn.cursor(cursor_factory=RealDictCursor) as cur:
            cur.execute("""
                SELECT id, project_id, action, payload, status 
                FROM bridge_tasks 
                WHERE id = %s AND project_id = %s;
            """, (task_id, project_id))
            task = cur.fetchone()

    if not task:
        raise HTTPException(status_code=404, detail="Không tìm thấy tác vụ lập lịch.")

    payload = task["payload"] if isinstance(task["payload"], dict) else json.loads(task["payload"])
    payload["status"] = "future"

    wp_result = bridge_task_service.dispatch_hybrid_action(
        project_id=project_id,
        action="publish_article",
        payload=payload
    )

    with db_service.get_connection() as conn:
        with conn.cursor() as cur:
            cur.execute("""
                UPDATE bridge_tasks 
                SET status = 'completed', result_data = %s, executed_at = NOW()
                WHERE id = %s;
            """, (json.dumps(wp_result), task_id))
            conn.commit()

    return {"status": "success", "task_id": task_id, "wp_result": wp_result}

@router.get("/api/v1/portal/projects/{project_id}/scheduled-tasks")
def list_scheduled_tasks_api(project_id: str, limit: int = 10):
    """Lấy danh sách các tác vụ lập lịch xuất bản gần nhất của dự án."""
    bridge_task_service.ensure_bridge_tasks_schema()
    with db_service.get_connection() as conn:
        with conn.cursor(cursor_factory=RealDictCursor) as cur:
            cur.execute("""
                SELECT id, action, payload, status, result_data, error_message, created_at, executed_at
                FROM bridge_tasks
                WHERE project_id = %s
                ORDER BY created_at DESC
                LIMIT %s;
            """, (project_id, limit))
            rows = cur.fetchall()

    return [
        {
            "id": str(r["id"]),
            "action": r["action"],
            "status": r["status"],
            "payload": r["payload"] if isinstance(r["payload"], dict) else json.loads(r["payload"]),
            "result_data": r["result_data"] if isinstance(r["result_data"], dict) else (json.loads(r["result_data"]) if r["result_data"] else None),
            "created_at": r["created_at"].isoformat() if r["created_at"] else None,
            "executed_at": r["executed_at"].isoformat() if r["executed_at"] else None
        }
        for r in rows
    ]
