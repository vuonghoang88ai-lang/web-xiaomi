"""
Router: routes/portal/chat_sessions.py
Mục đích: API Quản lý đa phiên trò chuyện (Multi-session Chat History) kiểu Gemini.
Tuân thủ nghiêm ngặt SOP 23 (<= 300 dòng code).
"""
import logging
from pydantic import BaseModel, Field
from typing import Optional
from fastapi import APIRouter, HTTPException, Depends
import db_service
import db_chat_sessions

logger = logging.getLogger("routes.portal.chat_sessions")
router = APIRouter(tags=["Portal Chat Sessions"])

class RenameSessionRequest(BaseModel):
    title: str = Field(..., min_length=1, max_length=200, description="Tiêu đề mới cho phiên trò chuyện")

class SwitchSessionRequest(BaseModel):
    dify_conversation_id: str = Field(..., description="Dify conversation ID muốn chuyển sang")


@router.get("/api/v1/portal/projects/{project_id}/conversations")
@router.get("/api/v1/projects/{project_id}/conversations")
def list_project_conversations(project_id: str):
    """Lấy danh sách toàn bộ các phiên trò chuyện của một dự án."""
    project = db_service.get_project_by_id(project_id)
    if not project:
        raise HTTPException(status_code=404, detail="Không tìm thấy dự án.")

    active_dify_id = project.get("dify_conversation_id")
    sessions = db_chat_sessions.get_project_conversations(project_id)

    # Tự động đồng bộ phiên hiện tại nếu chưa có trong bảng chat_conversations
    if active_dify_id and not any(s.get("dify_conversation_id") == active_dify_id for s in sessions):
        auto_session = db_chat_sessions.create_or_get_conversation(
            project_id=project_id,
            dify_conversation_id=active_dify_id,
            title=f"Phiên làm việc {active_dify_id[:8]}"
        )
        if auto_session:
            sessions.insert(0, auto_session)

    return {
        "project_id": project_id,
        "active_conversation_id": active_dify_id,
        "conversations": sessions
    }


@router.post("/api/v1/portal/projects/{project_id}/conversations/new")
@router.post("/api/v1/projects/{project_id}/conversations/new")
def create_new_chat_session(project_id: str):
    """Bắt đầu một phiên hội thoại mới cho dự án (xóa active conversation_id)."""
    project = db_service.get_project_by_id(project_id)
    if not project:
        raise HTTPException(status_code=404, detail="Không tìm thấy dự án.")

    db_service.reset_project_conversation_id(project_id)
    return {
        "status": "success",
        "project_id": project_id,
        "active_conversation_id": None,
        "message": "Đã sẵn sàng phiên trò chuyện mới."
    }


@router.post("/api/v1/portal/projects/{project_id}/conversations/switch")
@router.post("/api/v1/projects/{project_id}/conversations/switch")
def switch_chat_session(project_id: str, payload: SwitchSessionRequest):
    """Chuyển phiên hội thoại hiện tại sang một phiên cũ đã chọn."""
    project = db_service.get_project_by_id(project_id)
    if not project:
        raise HTTPException(status_code=404, detail="Không tìm thấy dự án.")

    target_dify_id = payload.dify_conversation_id.strip()
    db_service.update_project_conversation_id(project_id, target_dify_id)
    return {
        "status": "success",
        "project_id": project_id,
        "active_conversation_id": target_dify_id
    }


@router.patch("/api/v1/portal/projects/{project_id}/conversations/{conversation_id}")
@router.patch("/api/v1/projects/{project_id}/conversations/{conversation_id}")
def rename_chat_session(project_id: str, conversation_id: str, payload: RenameSessionRequest):
    """Đổi tên tiêu đề của một phiên trò chuyện."""
    conv = db_chat_sessions.get_conversation_by_id(conversation_id)
    if not conv or str(conv.get("project_id")) != str(project_id):
        raise HTTPException(status_code=404, detail="Không tìm thấy phiên trò chuyện.")

    success = db_chat_sessions.update_conversation_title(conversation_id, payload.title)
    if not success:
        raise HTTPException(status_code=500, detail="Không thể cập nhật tiêu đề.")
    return {
        "status": "success",
        "conversation_id": conversation_id,
        "new_title": payload.title.strip()
    }


@router.delete("/api/v1/portal/projects/{project_id}/conversations/{conversation_id}")
@router.delete("/api/v1/projects/{project_id}/conversations/{conversation_id}")
def remove_chat_session(project_id: str, conversation_id: str):
    """Xóa một phiên trò chuyện khỏi dự án."""
    project = db_service.get_project_by_id(project_id)
    if not project:
        raise HTTPException(status_code=404, detail="Không tìm thấy dự án.")

    deleted = db_chat_sessions.delete_conversation(conversation_id)
    if not deleted:
        raise HTTPException(status_code=404, detail="Không tìm thấy phiên trò chuyện cần xóa.")

    # Nếu phiên bị xóa đang là phiên active của dự án -> reset active conversation
    if project.get("dify_conversation_id") == deleted.get("dify_conversation_id"):
        db_service.reset_project_conversation_id(project_id)

    return {
        "status": "success",
        "deleted_conversation_id": conversation_id,
        "dify_conversation_id": deleted.get("dify_conversation_id")
    }

