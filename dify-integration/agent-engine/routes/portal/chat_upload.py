import os
import logging
from typing import Optional
from fastapi import APIRouter, File, UploadFile, Form, HTTPException
import httpx

logger = logging.getLogger("marketing_engine")
router = APIRouter()

MAX_FILE_SIZE = 15 * 1024 * 1024  # 15MB

ALLOWED_EXTENSIONS = {
    "jpg", "jpeg", "png", "webp", "gif", "svg",
    "pdf", "docx", "xlsx", "csv", "txt", "md"
}

@router.post("/api/v1/portal/projects/{project_id}/chat-upload")
@router.post("/api/v1/portal/chat/upload")
async def portal_chat_file_upload(
    project_id: Optional[str] = None,
    file: UploadFile = File(...),
    user: Optional[str] = Form("admin@marketing-ai.internal")
):
    """
    Tiếp nhận tệp đính kèm và ảnh từ Portal Chat, chuyển tiếp lên Dify API /files/upload.
    Hỗ trợ xử lý thị giác (Vision) cho ảnh và phân tích tài liệu theo SOP 23 & SOP 30.
    """
    if not file or not file.filename:
        raise HTTPException(status_code=400, detail="Vui lòng chọn một tệp tin hợp lệ.")

    filename = file.filename
    ext = filename.rsplit(".", 1)[-1].lower() if "." in filename else ""
    if ext not in ALLOWED_EXTENSIONS:
        raise HTTPException(
            status_code=400,
            detail=f"Định dạng '.{ext}' không được hỗ trợ. Chấp nhận: {', '.join(sorted(ALLOWED_EXTENSIONS))}."
        )

    content = await file.read()
    if len(content) > MAX_FILE_SIZE:
        raise HTTPException(status_code=400, detail=f"Kích thước tệp vượt quá giới hạn cho phép (tối đa 15MB).")

    dify_url = os.getenv("DIFY_API_URL", "http://docker-api-1:5001/v1").rstrip("/")
    dify_app_key = os.getenv("DIFY_APP_API_KEY", "app-cmo-copilot-default")

    content_type = file.content_type or "application/octet-stream"
    is_image = ext in {"jpg", "jpeg", "png", "webp", "gif", "svg"} or "image/" in content_type

    try:
        async with httpx.AsyncClient(timeout=45.0) as client:
            resp = await client.post(
                f"{dify_url}/files/upload",
                files={"file": (filename, content, content_type)},
                data={"user": user or "admin@marketing-ai.internal"},
                headers={"Authorization": f"Bearer {dify_app_key}"}
            )

            if resp.status_code not in (200, 201):
                err_text = resp.text
                logger.error(f"[DIFY_UPLOAD_FAIL] status={resp.status_code} err={err_text}")
                raise HTTPException(
                    status_code=resp.status_code,
                    detail=f"Dify upload thất bại: {err_text[:160]}"
                )

            dify_res = resp.json()
            file_id = dify_res.get("id")

            return {
                "status": "success",
                "file": {
                    "id": file_id,
                    "name": filename,
                    "size": len(content),
                    "extension": ext,
                    "mime_type": content_type,
                    "type": "image" if is_image else "document",
                    "preview_url": dify_res.get("source_url") or dify_res.get("preview_url")
                }
            }
    except HTTPException:
        raise
    except Exception as e:
        logger.error(f"[PORTAL_CHAT_UPLOAD_ERROR] {e}")
        raise HTTPException(status_code=500, detail=f"Lỗi tải tệp: {str(e)}")

