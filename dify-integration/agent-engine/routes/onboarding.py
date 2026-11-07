#!/usr/bin/env python3
"""Route module - auto-generated from server.py refactor."""
import os
import sys
import json
import logging
from uuid import UUID
from datetime import datetime, timezone
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

@router.post("/api/v1/onboarding/analyze")
def analyze_onboarding(req: OnboardingRequest):
    """
    Tier 1 CMO Discovery Endpoint:
    Tiếp cận dự án mới, bóc tách web của bạn, tìm Top 10 đối thủ đang online, bóc tách Logo thương hiệu và phân tích khoảng trống (Gap Analysis).
    """
    try:
        data = run_onboarding_gap_analysis(
            target_website_url=req.target_website_url,
            business_description=req.business_description or f"Dự án kinh doanh online và bán lẻ tại {req.target_website_url}",
            seed_competitor_url=req.seed_competitor_url,
            target_location=req.target_location or "",
            core_strengths=req.core_strengths or "",
            brand_logo_url=req.brand_logo_url,
            tenant_id=str(req.tenant_id or os.getenv("DEFAULT_TENANT_ID", "0cfcbe3e-fbfa-4025-8aa0-ee5ff82b77d8")),
            brand_profile={
                "target_language": req.target_language or "vi",
                "industry": req.industry or "",
                "offer_type": req.offer_type or "",
                "target_audience": req.target_audience or "",
                "primary_offers": req.primary_offers or "",
                "conversion_goal": req.conversion_goal or "",
                "brand_voice": req.brand_voice or "",
                "claims_evidence": req.claims_evidence or "",
                "compliance_constraints": req.compliance_constraints or "",
            },
        )
        return data
    except SerpDiscoveryError as e:
        raise HTTPException(status_code=502, detail=str(e))
    except ValueError as e:
        raise HTTPException(status_code=422, detail=str(e))
    except RuntimeError as e:
        logger.exception("Onboarding processing failed")
        raise HTTPException(status_code=500, detail=str(e))
    except Exception as e:
        logger.exception("Unexpected onboarding error")
        raise HTTPException(status_code=500, detail="Không thể hoàn tất onboarding.")



@router.post("/api/v1/project/memory")
def update_project_memory(req: ProjectMemoryRequest):
    """
    Cập nhật và tích lũy từng mẩu thông tin người dùng nhập (website, đối thủ, ngành hàng, UVP, địa điểm)
    vào Database PostgreSQL và đồng bộ sang Dify RAG Dataset.
    Trả về hồ sơ tổng hợp hoàn chỉnh hiện tại.
    """
    tenant_id = str(req.tenant_id or "0cfcbe3e-fbfa-4025-8aa0-ee5ff82b77d8")
    fields = req.model_dump(exclude_unset=True)
    proj = db_service.update_project_fields(tenant_id, fields)

    # Đồng bộ sang kho tri thức RAG của Dify
    try:
        doc_text = f"""# HỒ SƠ DỰ ÁN & BỘ NHỚ CHIẾN LƯỢC: {proj.get('name', 'DuAn')}
- **Website của bạn**: {proj.get('target_website_url') or 'Chưa cung cấp'}
- **Đối thủ cạnh tranh**: {proj.get('seed_competitor_url') or 'Chưa cung cấp'}
- **Ngành hàng / Dịch vụ**: {proj.get('business_description') or 'Chưa cung cấp'}
- **Khu vực mục tiêu**: {proj.get('target_location') or 'Toàn quốc'}
- **Lợi thế độc quyền (UVP)**: {proj.get('core_strengths') or 'Đang xác định'}
- **Thời gian cập nhật**: {datetime.now(timezone.utc).isoformat()}
"""
        target_dataset_id = proj.get("dify_dataset_id") or os.getenv("DIFY_DEFAULT_DATASET_ID", "0d7a7e06-cb8c-481e-8056-d9689e11a2c9")
        rag_service.upload_document_to_dataset(
            target_dataset_id,
            f"Ho_So_TongHop_{proj.get('name', 'DuAn')}",
            doc_text
        )
    except Exception as e:
        logger.warning(f"Không thể sync RAG: {e}")

    return {
        "status": "success",
        "message": "Đã ghi nhận thông tin vào bộ nhớ dự án và RAG thành công.",
        "current_project_dossier": {
            "target_website_url": proj.get("target_website_url"),
            "seed_competitor_url": proj.get("seed_competitor_url"),
            "business_description": proj.get("business_description"),
            "target_location": proj.get("target_location"),
            "core_strengths": proj.get("core_strengths"),
        },
        "is_ready_for_analysis": bool(
            proj.get("target_website_url") and proj.get("seed_competitor_url")
        )
    }



