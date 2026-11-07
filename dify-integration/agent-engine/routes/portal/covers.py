#!/usr/bin/env python3
"""
Portal Routes: Novel Cover Artisan & Media Repair Engine (SOP 23 <= 300 lines).
"""
import logging
from typing import Optional, List
from fastapi import APIRouter, HTTPException, Request
from pydantic import BaseModel, Field
import novel_cover_service

logger = logging.getLogger("marketing_engine")
router = APIRouter()

class GenerateCoverRequest(BaseModel):
    title: str = Field(..., description="Tên tác phẩm, sản phẩm hoặc dịch vụ")
    archetype: Optional[str] = Field("", description="content_media, commerce_retail, service_health_b2b")
    genre: Optional[str] = Field("", description="Thể loại hoặc phân nhóm")
    author: Optional[str] = Field("Sưu tầm", description="Tên tác giả hoặc người sưu tầm")
    price: Optional[str] = Field("", description="Giá niêm yết (cho thương mại)")
    specs: Optional[List[str]] = Field(default_factory=list, description="Danh sách thông số nổi bật")
    doctor: Optional[str] = Field("", description="Bác sĩ hoặc Chuyên gia phụ trách chuyên môn")
    aspect_ratio: Optional[str] = Field("auto", description="Tỷ lệ: auto, 2:3, 1:1, 16:9")

class ScanRepairRequest(BaseModel):
    limit: Optional[int] = Field(10, description="Số lượng bài viết tối đa cần quét và sửa bìa")

@router.post("/api/v1/portal/projects/{project_id}/covers/generate")
@router.post("/api/v1/projects/{project_id}/covers/generate")
def api_generate_single_cover(project_id: str, payload: GenerateCoverRequest, request: Request):
    """Sinh ảnh bìa hoặc asset hình ảnh chuẩn E-E-A-T đa ngành theo yêu cầu."""
    try:
        portal_host = f"{request.base_url.scheme}://{request.base_url.netloc}".rstrip("/")
        return novel_cover_service.generate_single_cover_for_project(
            project_id=project_id,
            title=payload.title,
            archetype=payload.archetype or "",
            genre=payload.genre or "",
            author=payload.author or "Sưu tầm",
            price=payload.price or "",
            specs=payload.specs or [],
            doctor=payload.doctor or "",
            aspect_ratio=payload.aspect_ratio or "auto",
            portal_host=portal_host
        )
    except Exception as e:
        logger.error(f"[COVER_GEN_ERROR] {e}")
        raise HTTPException(status_code=500, detail=f"Không thể tạo ảnh nghệ thuật: {e}")

@router.post("/api/v1/portal/projects/{project_id}/covers/scan-and-repair")
@router.post("/api/v1/projects/{project_id}/covers/scan-and-repair")
def api_scan_and_repair_covers(project_id: str, payload: ScanRepairRequest, request: Request):
    """Tự động quét bài viết/truyện thiếu ảnh bìa trên WordPress và gán ảnh hàng loạt qua Omni Bridge."""
    try:
        portal_base = f"{request.base_url.scheme}://{request.base_url.netloc}".rstrip("/")
        return novel_cover_service.scan_and_repair_project_covers(
            project_id=project_id,
            limit=payload.limit or 10,
            portal_base_url=portal_base
        )
    except Exception as e:
        logger.error(f"[COVER_REPAIR_ERROR] {e}")
        raise HTTPException(status_code=500, detail=f"Không thể tự động sửa ảnh bìa: {e}")

