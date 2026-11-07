#!/usr/bin/env python3
"""Route module - auto-generated from server.py refactor."""
import os
import sys
import json
import logging
from uuid import UUID
from typing import Optional
from fastapi import APIRouter, HTTPException, Query, BackgroundTasks, Request
from fastapi.responses import JSONResponse, StreamingResponse, FileResponse, Response

CURRENT_DIR = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, os.path.dirname(CURRENT_DIR))

from models import *
from utils import JOBS_DB, _gsc_access_token, _project_gsc_connector, _mask_api_key, background_campaign_worker, background_batch_worker
import db_service
import rag_service
import gsc_service
from scraper_tool import scrape_competitor_recon
from agent_orchestrator import run_full_pipeline
from serp_discovery_tool import (
    SerpDiscoveryError,
    normalize_website_url,
    run_onboarding_gap_analysis,
    discover_top_10_competitors,
    inspect_website,
    generate_uvp_suggestions,
)
from seed_analyzer import (
    run_continuous_seed_reconnaissance,
    generate_kickoff_briefing,
    analyze_seed_competitor,
    deep_audit_competitor_batch,
)

logger = logging.getLogger('marketing_engine')

router = APIRouter()

@router.post("/api/v1/saas/tenants")
def api_create_tenant(req: CreateTenantRequest):
    """SaaS API: Đăng ký một doanh nghiệp / khách hàng mới"""
    import db_service
    return db_service.create_tenant(req.name, req.plan, req.max_projects)



@router.post("/api/v1/saas/users")
def api_create_user(req: CreateUserRequest):
    """SaaS API: Cấp tài khoản người dùng cho khách hàng"""
    import db_service
    return db_service.create_user(req.tenant_id, req.email, req.full_name, req.role)



@router.get("/api/v1/saas/projects/{project_id}")
def api_get_project(project_id: str, tenant_id: Optional[UUID] = Query(None)):
    """SaaS API: Lấy toàn bộ hồ sơ trí tuệ của một dự án (Web, 10 đối thủ, Báo cáo Gap, RAG KB)"""
    import db_service
    data = db_service.get_project_full_intelligence(project_id, str(tenant_id) if tenant_id else None)
    if not data:
        raise HTTPException(status_code=404, detail="Không tìm thấy dự án")
    return data



@router.post("/api/v1/saas/projects/{project_id}/select-uvp")
def api_select_uvp(project_id: str, req: SelectUvpRequest):
    """SaaS API: Khách hàng chọn 1 trong 4 phương án UVP do AI gợi ý"""
    import db_service
    data = db_service.get_project_full_intelligence(project_id, str(req.tenant_id))
    if not data:
        raise HTTPException(status_code=404, detail="Không tìm thấy dự án")
    
    project = data["project"]
    suggested_uvps = project.get("suggested_uvp_options") or []
    if isinstance(suggested_uvps, str):
        suggested_uvps = json.loads(suggested_uvps)
    
    chosen_text = (req.custom_uvp_text or "").strip()
    if chosen_text and req.selected_uvp_id is not None:
        raise HTTPException(status_code=422, detail="Chỉ chọn UVP gợi ý hoặc nhập UVP tùy chỉnh, không chọn đồng thời.")
    if not chosen_text and req.selected_uvp_id is None:
        raise HTTPException(status_code=422, detail="Cần chọn selected_uvp_id hoặc nhập custom_uvp_text.")
    if not chosen_text:
        for u in suggested_uvps:
            try:
                matches = int(u.get("id")) == req.selected_uvp_id
            except (TypeError, ValueError):
                matches = False
            if matches:
                chosen_text = f"[{u.get('title')}] {u.get('slogan')} - {u.get('rationale')}"
                break

    if not chosen_text:
        raise HTTPException(status_code=422, detail="selected_uvp_id không khớp với phương án UVP của dự án.")
    if not db_service.update_project_uvp(project_id, str(req.tenant_id), chosen_text):
        raise HTTPException(status_code=404, detail="Không tìm thấy dự án thuộc tenant này")
            
    # Đồng bộ UVP chính thức vào RAG Knowledge Base
    dify_dataset_id = project.get("dify_dataset_id")
    if dify_dataset_id:
        try:
            import rag_service
            upload_result = rag_service.upload_document_to_dataset(
                dataset_id=str(dify_dataset_id),
                doc_name="02_CHOSEN_UVP_POSITIONING.md",
                text_content=f"# CHIẾN LƯỢC ĐỊNH VỊ THƯƠNG HIỆU & UVP ĐÃ PHÊ DUYỆT\n- **Dự án**: {project.get('name')}\n- **Website**: {project.get('target_website_url')}\n- **Lợi thế độc quyền (UVP) đã chốt**: {chosen_text}\n\nToàn bộ nội dung chiến dịch marketing của các Worker Agents (W1 đến W6) bắt buộc phải bám sát định vị độc quyền này để định hình giọng văn và thông điệp chuyển đổi."
            )
            if not upload_result or upload_result.get("error"):
                return {
                    "status": "partial",
                    "project_id": project_id,
                    "selected_uvp": chosen_text,
                    "persistence": {"postgresql": "saved", "rag": "failed"},
                }
        except Exception as rag_err:
            logger.exception("UVP was saved to PostgreSQL, but RAG sync failed")
            return {
                "status": "partial",
                "project_id": project_id,
                "selected_uvp": chosen_text,
                "persistence": {"postgresql": "saved", "rag": "failed"},
            }

    return {
        "status": "complete" if dify_dataset_id else "partial",
        "project_id": project_id,
        "selected_uvp": chosen_text,
        "persistence": {
            "postgresql": "saved",
            "rag": "synced" if dify_dataset_id else "not_configured",
        },
    }



