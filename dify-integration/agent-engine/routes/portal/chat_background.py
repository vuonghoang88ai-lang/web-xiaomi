#!/usr/bin/env python3
"""
Portal Background Chat Task Manager — Decoupled Async Stream Processing.
Strictly complies with SOP 23 (<= 300 lines).
"""
import time
import json
import asyncio
import logging
from typing import Dict, Any, Optional, List
import httpx
import db_service

logger = logging.getLogger("marketing_engine")

# project_id -> task dict
ACTIVE_CHAT_TASKS: Dict[str, Dict[str, Any]] = {}


def get_task_status(project_id: str, ack: bool = False) -> Dict[str, Any]:
    """Lấy trạng thái tác vụ chạy ngầm hiện tại của dự án."""
    task = ACTIVE_CHAT_TASKS.get(project_id)
    if not task:
        return {"is_generating": False, "status": "idle"}

    now = time.time()
    elapsed = int(now - task.get("started_at", now))

    # Tự động hủy nếu chạy quá 600 giây (10 phút)
    if task.get("status") == "generating" and elapsed > 600:
        task["status"] = "timeout"
        task["completed_at"] = now
        task["error"] = "Tác vụ chạy ngầm quá thời gian tối đa (10 phút)."
        return {"is_generating": False, "status": "timeout", "error": task["error"]}

    # Tự động dọn dẹp task đã hoàn tất hoặc lỗi sau 30 giây (hoặc khi client đã ack)
    if task.get("status") in ("completed", "error", "timeout", "aborted"):
        completed_at = task.get("completed_at", task.get("started_at", now))
        if ack or (now - completed_at > 30):
            status = task.get("status", "idle")
            err = task.get("error")
            del ACTIVE_CHAT_TASKS[project_id]
            return {"is_generating": False, "status": status, "acknowledged": True, "error": err}

    is_gen = task.get("status") == "generating"
    return {
        "is_generating": is_gen,
        "status": task.get("status", "idle"),
        "elapsed_seconds": elapsed,
        "prompt": task.get("prompt", ""),
        "accumulated_length": len(task.get("accumulated_answer", "")),
        "accumulated_preview": task.get("accumulated_answer", "")[:300],
        "conversation_id": task.get("conversation_id"),
        "error": task.get("error")
    }


def acknowledge_task(project_id: str) -> bool:
    """Xác nhận client đã nhận thông báo hoàn tất, dọn dẹp task về idle."""
    if project_id in ACTIVE_CHAT_TASKS:
        del ACTIVE_CHAT_TASKS[project_id]
        return True
    return False


def abort_task(project_id: str) -> bool:
    """Hủy một tác vụ đang chạy ngầm của dự án."""
    task = ACTIVE_CHAT_TASKS.get(project_id)
    if not task:
        return False
    task["status"] = "aborted"
    task["error"] = "Người dùng đã chủ động dừng tác vụ."
    client = task.get("http_client")
    if client and not client.is_closed:
        try:
            loop = asyncio.get_event_loop()
            if loop.is_running():
                loop.create_task(client.aclose())
        except Exception:
            pass
    return True


async def run_dify_background_worker(
    project_id: str,
    user: str,
    query: str,
    dify_url: str,
    dify_app_key: str,
    conversation_id: str,
    broadcast_queue: Optional[asyncio.Queue] = None,
    files: Optional[List[Dict[str, Any]]] = None
):
    """
    Worker máy chủ chạy ngầm độc lập với kết nối Client.
    Đảm bảo Dify sinh xong 100% bản kế hoạch dù trình duyệt F5 hay mất mạng.
    """
    started_at = time.time()
    stream_timeout = httpx.Timeout(600.0, connect=30.0, read=600.0, write=60.0)
    client = httpx.AsyncClient(timeout=stream_timeout)

    task_data = {
        "user": user,
        "prompt": query,
        "status": "generating",
        "started_at": started_at,
        "accumulated_answer": "",
        "conversation_id": conversation_id,
        "http_client": client,
        "broadcast_queue": broadcast_queue,
        "error": None
    }
    ACTIVE_CHAT_TASKS[project_id] = task_data

    # SOP 40: Gateway Pre-Routing & Grounding (Cưỡng chế nạp dữ liệu vệ tinh & giá kho)
    dify_query = query
    try:
        from routes.portal.pre_routing_grounder import enrich_query_with_live_grounding
        dify_query = enrich_query_with_live_grounding(query, project_id)
    except Exception as e_gr:
        logger.warning(f"[PRE_ROUTING_FAIL] {e_gr}")

    request_body = {
        "inputs": {},
        "query": dify_query,
        "response_mode": "streaming",
        "conversation_id": conversation_id,
        "user": user
    }

    dify_files = []
    for f in (files or []):
        fid = f.get("id") or f.get("upload_file_id")
        if fid:
            ftype = f.get("type") or ("image" if "image" in str(f.get("mime_type", "")) else "document")
            dify_files.append({"type": ftype, "transfer_method": "local_file", "upload_file_id": fid})
    if dify_files:
        request_body["files"] = dify_files

    try:
        async with client.stream(
            "POST",
            f"{dify_url}/chat-messages",
            json=request_body,
            headers={"Authorization": f"Bearer {dify_app_key}", "Content-Type": "application/json"}
        ) as response:
            if response.status_code == 404 and conversation_id:
                logger.warning(f"[BG_DIFY_RECOVER] Stale cid={conversation_id}, resetting.")
                db_service.reset_project_conversation_id(project_id)
                fb_body = dict(request_body)
                fb_body["conversation_id"] = ""
                async with client.stream(
                    "POST",
                    f"{dify_url}/chat-messages",
                    json=fb_body,
                    headers={"Authorization": f"Bearer {dify_app_key}", "Content-Type": "application/json"}
                ) as fb_res:
                    if fb_res.status_code == 200:
                        await _process_stream_lines(project_id, fb_res, task_data, broadcast_queue)
                    else:
                        task_data["status"] = "error"
                        task_data["error"] = f"Dify Error {fb_res.status_code}"
                return

            if response.status_code != 200:
                task_data["status"] = "error"
                task_data["error"] = f"Dify Error {response.status_code}"
                if broadcast_queue:
                    await broadcast_queue.put(f"data: {json.dumps({'event': 'error', 'message': task_data['error']})}\n\n")
                return

            await _process_stream_lines(project_id, response, task_data, broadcast_queue)

    except httpx.ConnectError:
        task_data["status"] = "error"
        task_data["completed_at"] = time.time()
        task_data["error"] = f"Không thể kết nối tới Dify API tại '{dify_url}'."
        logger.error(f"[BG_DIFY_CONNECT_ERROR] {task_data['error']}")
    except asyncio.CancelledError:
        task_data["status"] = "aborted"
        task_data["completed_at"] = time.time()
    except Exception as e:
        task_data["status"] = "error"
        task_data["completed_at"] = time.time()
        task_data["error"] = str(e)
        logger.error(f"[BG_DIFY_STREAM_ERROR] {e}")
    finally:
        if task_data.get("status") == "generating":
            task_data["status"] = "completed"
            task_data["completed_at"] = time.time()
        if not client.is_closed:
            await client.aclose()
        if broadcast_queue:
            await broadcast_queue.put(None)  # Sentinel kết thúc stream SSE cho client

    # Kích hoạt thu hoạch tự động sang RAG nếu bài viết đạt chuẩn deliverable dài
    if task_data.get("accumulated_answer") and len(task_data["accumulated_answer"]) >= 2500:
        try:
            from routes.portal.chat_compactor import auto_harvest_to_rag
            asyncio.create_task(
                auto_harvest_to_rag(
                    project_id=project_id,
                    prompt=task_data.get("prompt", ""),
                    answer=task_data.get("accumulated_answer", "")
                )
            )
        except Exception as e_harv:
            logger.warning(f"[TRIGGER_AUTO_HARVEST_FAIL] {e_harv}")


async def _process_stream_lines(project_id: str, stream_res, task_data: dict, broadcast_queue: Optional[asyncio.Queue]):
    """Đọc từng chunk SSE từ Dify, ghi vào bộ nhớ task và chuyển tiếp cho client nếu có."""
    saved_cid = False
    async for line in stream_res.aiter_lines():
        if task_data.get("status") == "aborted":
            break
        if line.startswith("data: "):
            if broadcast_queue:
                try:
                    broadcast_queue.put_nowait(f"{line}\n")
                except Exception:
                    pass

            data_str = line[6:].strip()
            try:
                chunk = json.loads(data_str)
                cid = chunk.get("conversation_id")
                if cid and not saved_cid:
                    task_data["conversation_id"] = cid
                    db_service.update_project_conversation_id(project_id, cid)
                    saved_cid = True
                    try:
                        import re, db_chat_sessions
                        raw_p = task_data.get("prompt", "")
                        clean_title = re.sub(r"\[(?:BỐI CẢNH|QUY TẮC|YÊU CẦU|PROJECT|MANDATORY)[^\]]+\]\s*", "", raw_p).strip()
                        clean_title = clean_title[:45] or "Cuộc trò chuyện mới"
                        db_chat_sessions.touch_conversation(project_id, cid, title=clean_title)
                    except Exception as e_touch:
                        logger.warning(f"[TOUCH_CONV_FAIL] {e_touch}")

                ev = chunk.get("event")
                if ev in ("agent_message", "message"):
                    task_data["accumulated_answer"] += (chunk.get("answer") or "")
                elif ev == "agent_thought":
                    th = (chunk.get("thought") or "").strip()
                    if th and len(th) > 20 and not task_data["accumulated_answer"]:
                        task_data["accumulated_answer"] = th
                elif ev == "error":
                    err_msg = chunk.get("message") or "Lỗi xử lý mô hình AI"
                    task_data["status"] = "error"
                    task_data["error"] = err_msg
                    try:
                        from routes.portal.chat_compactor import clean_stale_error_messages
                        clean_stale_error_messages(task_data.get("conversation_id"))
                    except Exception:
                        pass
            except Exception:
                pass
    task_data["status"] = "completed"
    task_data["completed_at"] = time.time()
    if task_data.get("accumulated_answer") and len(task_data["accumulated_answer"]) >= 2500:
        try:
            from routes.portal.chat_compactor import auto_harvest_to_rag
            asyncio.create_task(
                auto_harvest_to_rag(
                    project_id=project_id,
                    prompt=task_data.get("prompt", ""),
                    answer=task_data.get("accumulated_answer", "")
                )
            )
        except Exception as e_harv:
            logger.warning(f"[TRIGGER_AUTO_HARVEST_FAIL] {e_harv}")

