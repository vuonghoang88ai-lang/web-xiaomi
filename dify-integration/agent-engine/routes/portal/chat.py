#!/usr/bin/env python3
"""
Portal Chat Route Module — Executive CMO AI Copilot SSE Stream & History.
Strictly complies with SOP 23 (<= 300 lines).
"""
import os
import sys
import json
import logging
import re
import urllib.request
import urllib.error
import asyncio
from typing import Optional, List, Dict, Any
from fastapi import APIRouter, HTTPException, Query
from fastapi.responses import StreamingResponse

CURRENT_DIR = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, os.path.dirname(os.path.dirname(CURRENT_DIR)))

from models import PortalChatMessage
import db_service
import httpx
from routes.portal.chat_background import (
    get_task_status,
    abort_task,
    acknowledge_task,
    run_dify_background_worker
)

logger = logging.getLogger('marketing_engine')
router = APIRouter()


def _clean_user_display_query(query: str) -> str:
    if not query:
        return ""
    parts = re.split(r"\n\s*\[(?:CHỈ THỊ|📡|🏷️|DỮ LIỆU|BẢNG GIÁ|BỐI CẢNH|QUY TẮC|YÊU CẦU|INTENT|HỒ SƠ|QUY CHUẨN|GROUNDING)", query)
    clean = re.sub(r"^\[(?:BỐI CẢNH|PROJECT CONTEXT|QUY TẮC|YÊU CẦU|PROJECT|INTENT)[^\]]+\]\s*", "", parts[0]).strip()
    return clean


def _clean_agent_message(answer: str, raw_thoughts: list):
    thought_steps, final_thought_ans = [], ""
    for i, t in enumerate(raw_thoughts or []):
        tool = (t.get("tool") or "").strip()
        th = (t.get("thought") or "").strip()
        th = re.sub(r"<[｜|]\s*DSML[\s\S]*?(?=(?:\n# |\n:::|\n\*\*\[TIER|$))", "", th)
        th = re.sub(r"<function_calls?>[\s\S]*?(</function_calls?>|$)", "", th)
        th = re.sub(r"\[\d+\s+items\s+compressed\s+to\s+\d+\.\s+Retrieve\s+more:[^\]]+\]", "", th).strip()
        if tool:
            thought_steps.append({"tool": tool, "text": th})
        elif i == len(raw_thoughts) - 1 and th:
            final_thought_ans = th
        elif th:
            thought_steps.append({"tool": None, "text": th})

    clean_ans = answer or ""
    if not clean_ans.strip() and final_thought_ans and len(final_thought_ans) > 20:
        clean_ans = final_thought_ans

    # SOP 45: Khử trùng triệt để thẻ DSML, function calling artifacts và internal monologue preamble
    if "<｜" in clean_ans or "<|" in clean_ans or "<function_call" in clean_ans:
        clean_ans = re.sub(r"<[｜|]\s*DSML[\s\S]*?(?=(?:\n# |\n:::boardroom|\n\*\*\[TIER|$))", "", clean_ans)
        clean_ans = re.sub(r"<function_calls?>[\s\S]*?(?=(?:\n# |\n:::boardroom|\n\*\*\[TIER|$))", "", clean_ans)
        m = re.search(r"(\n|^)(# [^\n]+|:::boardroom|\*\*\[TIER)", clean_ans)
        if m and m.start() > 0:
            preamble = clean_ans[:m.start()].strip()
            if any(k in preamble.lower() for k in ["tôi sẽ", "tôi cần", "phân tích", "function_call", "dsml"]):
                clean_ans = clean_ans[m.start():].strip()
        elif not m:
            lines = [l for l in clean_ans.splitlines() if not re.match(r"^\s*(?:Tôi sẽ|Tôi cần|Phân tích|<[｜|]\s*DSML|<function)", l, re.I)]
            clean_ans = "\n".join(lines).strip()
    clean_ans = re.sub(r"<[｜|]\s*DSML[\s\S]*?(?=\n\n|$)", "", clean_ans)
    clean_ans = re.sub(r"<function_calls?>[\s\S]*?(</function_calls?>|$)", "", clean_ans)
    clean_ans = re.sub(r"\[\d+\s+items\s+compressed\s+to\s+\d+\.\s+Retrieve\s+more:[^\]]+\]", "", clean_ans).strip()

    thought_parts = []
    for s in thought_steps:
        lbl = f"🛠️ [Công cụ: {s['tool']}]" if s.get("tool") else ""
        txt = s.get("text", "")
        thought_parts.append(f"{lbl}\n{txt}" if (lbl and txt) else (lbl or txt))
    thought_text = "\n\n".join(thought_parts) if thought_parts else None
    return thought_steps, thought_text, clean_ans


@router.get("/api/v1/portal/projects/{project_id}/chat-history")
@router.get("/api/v1/projects/{project_id}/chat-history")
def portal_get_chat_history(project_id: str, user: Optional[str] = Query(None)):
    """
    Lấy toàn bộ lịch sử trò chuyện của dự án từ Dify API qua `dify_conversation_id`.
    F5 hoặc đổi máy tính vẫn giữ nguyên 100% ngữ cảnh. Tự động dọn dẹp nếu phiên cũ không còn.
    """
    project = db_service.get_project_by_id(project_id)
    if not project:
        raise HTTPException(status_code=404, detail="Không tìm thấy dự án.")

    conversation_id = project.get("dify_conversation_id")
    if not conversation_id:
        return {"project_id": project_id, "conversation_id": None, "messages": []}

    if not user:
        _, def_user = db_service.get_default_tenant_and_user()
        user = def_user.get("email") if def_user else "admin@marketing-ai.internal"

    dify_url = os.getenv("DIFY_API_URL", "http://docker-api-1:5001/v1").rstrip("/")
    dify_app_key = os.getenv("DIFY_APP_API_KEY", "app-cmo-copilot-default")

    def try_fetch_messages(target_user: str):
        req_url = f"{dify_url}/messages?conversation_id={conversation_id}&user={target_user}&limit=100"
        req = urllib.request.Request(req_url, headers={"Authorization": f"Bearer {dify_app_key}"})
        with urllib.request.urlopen(req, timeout=25) as res:
            res_data = json.loads(res.read().decode("utf-8"))
            return res_data.get("data", [])

    raw_messages = None
    try:
        raw_messages = try_fetch_messages(user)
    except urllib.error.HTTPError as he:
        if he.code == 404:
            fb_user = "admin@marketing-ai.internal" if user != "admin@marketing-ai.internal" else "client@portal.local"
            try:
                raw_messages = try_fetch_messages(fb_user)
            except Exception:
                logger.warning(f"[DIFY_RESET_STALE_CONVERSATION] Stale cid={conversation_id} for project {project_id}, resetting.")
                db_service.reset_project_conversation_id(project_id)
                return {"project_id": project_id, "conversation_id": None, "messages": []}
        else:
            logger.error(f"[DIFY_HISTORY_ERROR] project_id={project_id} err={he}")
            return {"project_id": project_id, "conversation_id": conversation_id, "messages": [], "error": str(he)}
    except Exception as e:
        logger.error(f"[DIFY_HISTORY_ERROR] project_id={project_id} err={e}")
        return {"project_id": project_id, "conversation_id": conversation_id, "messages": [], "error": str(e)}

    formatted = []
    for item in (raw_messages or []):
        query = item.get("query")
        answer = item.get("answer")
        created_at = item.get("created_at")
        msg_files = item.get("message_files") or []
        if query:
            clean_display_query = _clean_user_display_query(query)
            if clean_display_query or msg_files:
                formatted.append({
                    "id": f"{item.get('id')}_q",
                    "role": "user",
                    "content": clean_display_query,
                    "files": msg_files,
                    "created_at": created_at
                })
        has_err = item.get("status") == "error" or bool(item.get("error"))
        if answer or item.get("agent_thoughts") or has_err:
            raw_thoughts = item.get("agent_thoughts") or []
            th_steps, th_text, clean_ans = _clean_agent_message(answer, raw_thoughts)
            if not clean_ans and has_err:
                err_raw = str(item.get("error") or "")
                clean_ans = (
                    "⚠️ **Yêu cầu không thể hoàn thành do máy chủ AI phản hồi quá thời gian (Timeout).**\n\n"
                    f"*Chi tiết lỗi:* `{err_raw[:180] or 'Read timed out (300s)'}`\n\n"
                    "💡 *Khắc phục:* Phiên trò chuyện của dự án đã tích lũy nhiều bài viết và tin nhắn dài khiến ngữ cảnh quá tải. "
                    "Vui lòng bấm nút **'Làm mới phiên chat'** (màu đỏ góc trên bên phải) để bắt đầu phiên mới nhanh chóng."
                )
            if clean_ans or th_text:
                formatted.append({
                    "id": f"{item.get('id')}_a",
                    "role": "assistant",
                    "content": clean_ans or "⚠️ Phản hồi từ AI bị gián đoạn.",
                    "thought": th_text,
                    "thought_steps": th_steps,
                    "created_at": created_at
                })

    return {"project_id": project_id, "conversation_id": conversation_id, "messages": formatted}


@router.post("/api/v1/portal/projects/{project_id}/chat")
@router.post("/api/v1/projects/{project_id}/chat")
async def portal_chat_stream(project_id: str, payload: PortalChatMessage):
    """
    Gửi tin nhắn vào CMO AI Copilot của dự án qua giao thức SSE Streaming.
    Tự động tiêm thông số bối cảnh dự án (Context Injection) và duy trì conversation_id.
    """
    project = db_service.get_project_by_id(project_id)
    if not project:
        raise HTTPException(status_code=404, detail="Không tìm thấy dự án.")

    existing_conversation_id = project.get("dify_conversation_id") or ""
    dify_url = os.getenv("DIFY_API_URL", "http://docker-api-1:5001/v1").rstrip("/")
    dify_app_key = os.getenv("DIFY_APP_API_KEY", "app-cmo-copilot-default")

    chat_user = payload.user
    if not chat_user:
        _, def_user = db_service.get_default_tenant_and_user()
        chat_user = def_user.get("email") if def_user else "admin@marketing-ai.internal"

    # ─── Bối cảnh tự động (Gateway Project Context Injection) ───────────────
    p_name = project.get("name", "Dự án")
    p_url = project.get("target_website_url", "")
    bp = project.get("brand_profile") or {}
    if isinstance(bp, str):
        try:
            bp = json.loads(bp)
        except Exception:
            bp = {}
    industry = bp.get("industry") or project.get("business_description") or ""
    selected_uvp = bp.get("selected_uvp", {}).get("slogan") if isinstance(bp.get("selected_uvp"), dict) else ""
    clean_message = payload.message.strip()
    is_en = (getattr(payload, "lang", "vi") == "en")
    if not clean_message and payload.files:
        clean_message = "Vui lòng phân tích hình ảnh/tệp đính kèm tôi vừa gửi và đề xuất giải pháp tối ưu cho website." if not is_en else "Please analyze the attached image/file and suggest optimization solutions for the website."
    if is_en:
        context_tag = f"[PROJECT CONTEXT: Name='{p_name}', Website='{p_url}', Industry='{industry[:60]}', UVP='{selected_uvp[:60] or 'E-E-A-T'}']\n[MANDATORY LANGUAGE: ENGLISH]\n"
    else:
        context_tag = f"[BỐI CẢNH DỰ ÁN: Tên='{p_name}', Website='{p_url}', Ngành='{industry[:60]}', UVP='{selected_uvp[:60] or 'E-E-A-T'}']\n"
    query_to_send = f"{context_tag}{clean_message}"

    if existing_conversation_id:
        from routes.portal.chat_compactor import clean_stale_error_messages
        clean_stale_error_messages(existing_conversation_id)

    broadcast_queue = asyncio.Queue()
    asyncio.create_task(
        run_dify_background_worker(
            project_id=project_id,
            user=chat_user,
            query=query_to_send,
            dify_url=dify_url,
            dify_app_key=dify_app_key,
            conversation_id=existing_conversation_id,
            broadcast_queue=broadcast_queue,
            files=payload.files or []
        )
    )

    async def event_generator():
        try:
            while True:
                item = await broadcast_queue.get()
                if item is None:
                    break
                yield item
        except asyncio.CancelledError:
            logger.info(f"[CLIENT_DISCONNECT] Client disconnected from {project_id}, task runs in background.")

    return StreamingResponse(event_generator(), media_type="text/event-stream")

@router.get("/api/v1/portal/projects/{project_id}/chat-status")
@router.get("/api/v1/projects/{project_id}/chat-status")
def portal_chat_task_status(project_id: str, ack: bool = False):
    """Kiểm tra xem dự án có đang có tác vụ sinh câu trả lời ngầm không."""
    return get_task_status(project_id, ack=ack)

@router.post("/api/v1/portal/projects/{project_id}/chat-ack")
@router.post("/api/v1/projects/{project_id}/chat-ack")
def portal_chat_task_ack(project_id: str):
    """Xác nhận client đã nhận kết quả, đưa task về idle."""
    return {"status": "ok", "acknowledged": acknowledge_task(project_id), "project_id": project_id}

@router.post("/api/v1/portal/projects/{project_id}/chat-abort")
@router.post("/api/v1/projects/{project_id}/chat-abort")
def portal_chat_task_abort(project_id: str):
    """Chủ động dừng tác vụ chạy ngầm của dự án."""
    success = abort_task(project_id)
    return {"status": "success" if success else "not_found", "project_id": project_id}


@router.post("/api/v1/portal/projects/{project_id}/chat/reset")
@router.post("/api/v1/projects/{project_id}/chat/reset")
def portal_reset_chat(project_id: str):
    """Làm mới phiên chat cho dự án (xóa conversation_id để bắt đầu cuộc trò chuyện mới)."""
    success = db_service.reset_project_conversation_id(project_id)
    if not success:
        raise HTTPException(status_code=404, detail="Không tìm thấy dự án.")
    return {
        "status": "success",
        "project_id": project_id,
        "message": "Đã làm mới phiên chat thành công cho dự án."
    }


@router.get("/api/v1/portal/projects/{project_id}/chat-context")
@router.get("/api/v1/projects/{project_id}/chat-context")
def portal_chat_context_metrics(project_id: str):
    """Lấy thông số tải ngữ cảnh và trạng thái bộ nén thông minh Gemini."""
    project = db_service.get_project_by_id(project_id)
    if not project:
        raise HTTPException(status_code=404, detail="Không tìm thấy dự án.")
    cid = project.get("dify_conversation_id") or ""
    from routes.portal.chat_compactor import get_conversation_context_metrics
    return get_conversation_context_metrics(cid)
