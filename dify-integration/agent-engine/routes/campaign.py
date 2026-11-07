#!/usr/bin/env python3
"""Route module - auto-generated from server.py refactor."""
import os
import sys
import json
import logging
import uuid
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

@router.post("/api/v1/campaign")
def trigger_campaign(req: CampaignRequest, background_tasks: BackgroundTasks):
    """
    Tier 2 Multi-Agent Campaign Trigger (Đơn lẻ):
    Hỗ trợ cả 'post' (SEO) và 'product' (WooCommerce) với liên kết project_id từ Database.
    """
    if req.project_id and not req.tenant_id:
        raise HTTPException(status_code=422, detail="project_id bắt buộc đi kèm tenant_id.")
    if req.project_id and not db_service.get_project_full_intelligence(req.project_id, str(req.tenant_id)):
        raise HTTPException(status_code=404, detail="Project không thuộc tenant này")
    if not req.project_id and (not req.brand_name or not req.target_website_url):
        raise HTTPException(
            status_code=422,
            detail="Khi không truyền project_id, bắt buộc truyền brand_name và target_website_url theo hồ sơ dự án.",
        )

    job_id = f"job_{uuid.uuid4().hex[:8]}"
    JOBS_DB[job_id] = {
        "job_id": job_id,
        "project_id": req.project_id,
        "keyword": req.keyword,
        "category": req.category,
        "post_type": req.post_type or "post",
        "status": "queued"
    }

    if req.async_mode:
        background_tasks.add_task(
            background_campaign_worker,
            job_id,
            req.keyword,
            req.category,
            req.publish_to_wp,
            req.post_type or "post",
            req.project_id,
            str(req.tenant_id) if req.tenant_id else None,
            {
                "post_status": req.post_status or "publish",
                "brand_name": req.brand_name or "",
                "target_website_url": req.target_website_url or "",
                "target_location": req.target_location or "",
                "business_description": req.business_description or "",
                "brand_profile": req.brand_profile,
            }
        )
        return {
            "status": "accepted",
            "job_id": job_id,
            "project_id": req.project_id,
            "message": f"Chiến dịch ({req.post_type}) cho '{req.keyword}' đã được khởi động ngầm. Tra cứu tại /api/v1/jobs/{job_id}."
        }
    else:
        # Chạy đồng bộ
        result = run_full_pipeline(
            req.keyword, req.category, req.publish_to_wp, post_type=req.post_type or "post",
            post_status=req.post_status or "publish",
            project_id=req.project_id, tenant_id=str(req.tenant_id) if req.tenant_id else None,
            brand_name=req.brand_name or "",
            target_website_url=req.target_website_url or "",
            target_location=req.target_location or "",
            business_description=req.business_description or "",
            brand_profile=req.brand_profile,
        )
        return {
            "status": "success",
            "job_id": job_id,
            "project_id": req.project_id,
            "result": result
        }



@router.post("/api/v1/campaign/product")
def trigger_product_campaign(req: CampaignRequest, background_tasks: BackgroundTasks):
    """
    Dedicated Worker 1: Viết trang chi tiết sản phẩm WooCommerce chuẩn UECS & ACF Repeater specs.
    Tách biệt vật lý hoàn toàn khỏi luồng viết bài SEO blog.
    """
    req.post_type = "product"
    if not req.tenant_id:
        def_tenant, _ = db_service.get_default_tenant_and_user()
        if def_tenant:
            req.tenant_id = UUID(str(def_tenant["id"]))
    if not req.target_website_url and os.getenv("TARGET_WEBSITE_URL"):
        req.target_website_url = os.getenv("TARGET_WEBSITE_URL")
    if not req.brand_name and os.getenv("BRAND_NAME"):
        req.brand_name = os.getenv("BRAND_NAME")
    req.async_mode = True
    req.publish_to_wp = True
    return trigger_campaign(req, background_tasks)



@router.post("/api/v1/campaign/article")
def trigger_article_campaign(req: CampaignRequest, background_tasks: BackgroundTasks):
    """
    Dedicated Worker 2: Viết bài viết SEO Blog / Tin tức / Cẩm nang chuẩn 10x Editorial Architecture.
    Tách biệt vật lý hoàn toàn khỏi luồng sản phẩm WooCommerce.
    """
    req.post_type = "post"
    if not req.tenant_id:
        def_tenant, _ = db_service.get_default_tenant_and_user()
        if def_tenant:
            req.tenant_id = UUID(str(def_tenant["id"]))
    if not req.target_website_url and os.getenv("TARGET_WEBSITE_URL"):
        req.target_website_url = os.getenv("TARGET_WEBSITE_URL")
    if not req.brand_name and os.getenv("BRAND_NAME"):
        req.brand_name = os.getenv("BRAND_NAME")
    req.async_mode = True
    req.publish_to_wp = True
    return trigger_campaign(req, background_tasks)



@router.post("/api/v1/campaign/batch")
def trigger_batch_campaign(req: BatchCampaignRequest, background_tasks: BackgroundTasks):
    """
    Tier 2 Multi-Agent Batch Campaign Trigger (Hàng loạt):
    Nhận danh sách sản phẩm/từ khóa, xử lý bất đồng bộ ngầm cho project_id tương ứng.
    """
    if req.project_id and not req.tenant_id:
        raise HTTPException(status_code=422, detail="project_id bắt buộc đi kèm tenant_id.")
    if req.project_id and not db_service.get_project_full_intelligence(req.project_id, str(req.tenant_id)):
        raise HTTPException(status_code=404, detail="Project không thuộc tenant này")
    batch_job_id = f"batch_{uuid.uuid4().hex[:8]}"
    total_items = len(req.items)
    JOBS_DB[batch_job_id] = {
        "job_id": batch_job_id,
        "project_id": req.project_id,
        "total_items": total_items,
        "status": "queued",
        "progress": f"0/{total_items}",
        "current_step": "Đang xếp hàng...",
        "results": []
    }

    background_tasks.add_task(
        background_batch_worker,
        batch_job_id,
        req.items,
        req.default_category or "",
        req.publish_to_wp,
        req.post_type or "post",
        req.project_id,
        str(req.tenant_id) if req.tenant_id else None,
    )

    return {
        "status": "accepted",
        "job_id": batch_job_id,
        "project_id": req.project_id,
        "total_items": total_items,
        "message": f"Đã tiếp nhận danh sách {total_items} sản phẩm cho dự án {req.project_id or 'mặc định'}. Hệ thống đang tiến hành cào dữ liệu, viết bài và xuất bản ngầm. Tra cứu tiến độ tại /api/v1/jobs/{batch_job_id}."
    }



@router.get("/api/v1/jobs/{job_id}")
def get_job_status(job_id: str):
    """Tra cứu tiến độ thực thi của chiến dịch."""
    if job_id not in JOBS_DB:
        raise HTTPException(status_code=404, detail="Không tìm thấy job_id này")
    return JOBS_DB[job_id]


# =============================================================================
# COMMERCIAL SAAS CLIENT PORTAL ENDPOINTS (SOP 20)
# =============================================================================


