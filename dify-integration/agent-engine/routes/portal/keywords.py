#!/usr/bin/env python3
"""Portal route module - auto-generated from server.py refactor."""
import os
import sys
import json
import logging
import re
import uuid
from uuid import UUID
from typing import Optional
from datetime import datetime, timezone, timedelta
from fastapi import APIRouter, HTTPException, Query, BackgroundTasks, Request
from fastapi.responses import JSONResponse, StreamingResponse, FileResponse, Response
from pydantic import BaseModel, Field, field_validator

CURRENT_DIR = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, os.path.dirname(os.path.dirname(CURRENT_DIR)))

from models import *
from utils import JOBS_DB, _gsc_access_token, _project_gsc_connector, _mask_api_key
import db_service
import rag_service
import gsc_service
from scraper_tool import scrape_competitor_recon
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
import urllib.request
import urllib.error

logger = logging.getLogger('marketing_engine')

router = APIRouter()

@router.post("/api/v1/portal/projects/{project_id}/keywords/regenerate")
def portal_regenerate_keyword_matrix(project_id: str):
    """
    Tái tạo toàn diện Ma trận từ khóa SERP (60 từ khóa chiến lược) 
    và Kho Từ Khóa Tổng Lực Bóc Tách từ toàn bộ 10 Đối Thủ SERP + Web Đích (> 1.200 từ).
    """
    import db_service
    from seed_analyzer import generate_keyword_serp_matrix

    project = db_service.get_project_by_id(project_id)
    if not project:
        raise HTTPException(status_code=404, detail="Không tìm thấy dự án.")

    bp = project.get("brand_profile") or {}
    if isinstance(bp, str):
        try:
            bp = json.loads(bp)
        except Exception:
            bp = {}

    intel = db_service.get_project_full_intelligence(project_id) or {}
    competitors = intel.get("competitors", [])

    seed_analysis = bp.get("seed_analysis") or {}
    cats = seed_analysis.get("category_structure", [])
    extracted_kws = bp.get("extracted_keywords") or seed_analysis.get("extracted_keywords") or []

    matrix_res = generate_keyword_serp_matrix(
        extracted_keywords=extracted_kws,
        categories=cats,
        target_url=project.get("target_website_url", ""),
        seed_audit=seed_analysis,
        business_description=project.get("business_description") or "",
        competitors=competitors,
        target_analysis=bp.get("target_analysis"),
        return_dict=True
    )
    kw_matrix = matrix_res["keyword_matrix"]
    master_pool = matrix_res["master_keyword_pool"]

    bp["keyword_matrix"] = kw_matrix
    bp["master_keyword_pool"] = master_pool

    try:
        with db_service.get_connection() as conn:
            with conn.cursor() as cur:
                cur.execute("UPDATE projects SET brand_profile = %s WHERE id = %s;", 
                            (json.dumps(bp, ensure_ascii=False), project_id))
                conn.commit()
    except Exception as e:
        logger.error(f"Lỗi cập nhật keyword_matrix: {e}")
        raise HTTPException(status_code=500, detail=str(e))

    return {
        "status": "success", 
        "keyword_matrix": kw_matrix, 
        "master_keyword_pool": master_pool,
        "count": len(kw_matrix),
        "master_pool_count": len(master_pool)
    }



@router.post("/api/v1/portal/projects/{project_id}/keywords/scan")
def portal_scan_single_keyword(project_id: str, req: PortalScanSingleKeywordRequest):
    """
    Quét trực tiếp Google SERP cho 1 từ khóa cụ thể (VD: 'tivi xiaomi', 'máy giặt xiaomi')
    Lấy Top 5 đối thủ chính xác đang xếp hạng và cập nhật vào Ma Trận Từ Khóa của dự án.
    """
    import db_service
    from seed_analyzer import scan_keyword_serp_live

    project = db_service.get_project_by_id(project_id)
    if not project:
        raise HTTPException(status_code=404, detail="Không tìm thấy dự án.")

    target_url = project.get("target_website_url", "")
    serp_result = scan_keyword_serp_live(req.keyword, target_url=target_url)

    bp = project.get("brand_profile") or {}
    if isinstance(bp, str):
        try:
            bp = json.loads(bp)
        except Exception:
            bp = {}

    matrix = bp.get("keyword_matrix") or []
    # Tìm xem keyword đã tồn tại chưa
    clean_kw = req.keyword.strip().lower()
    existing_item = None
    for item in matrix:
        if item.get("keyword", "").lower() == clean_kw:
            existing_item = item
            break

    if existing_item:
        existing_item.update({
            "category": req.category or existing_item.get("category", "Gia dụng thông minh"),
            "search_intent": serp_result.get("search_intent", existing_item.get("search_intent")),
            "competition_level": serp_result.get("competition_level", existing_item.get("competition_level")),
            "top_competitors": serp_result.get("top_competitors", []),
            "recommended_action": serp_result.get("recommended_action", ""),
            "last_scanned_at": serp_result.get("last_scanned_at", "")
        })
    else:
        new_item = {
            "id": f"kw-{len(matrix) + 1}",
            "keyword": clean_kw,
            "category": req.category or "Gia dụng thông minh",
            "search_intent": serp_result.get("search_intent", "Thương mại (Commercial)"),
            "competition_level": serp_result.get("competition_level", "Trung bình"),
            "search_volume_est": "6.500 tìm kiếm/tháng",
            "top_competitors": serp_result.get("top_competitors", []),
            "recommended_action": serp_result.get("recommended_action", ""),
            "last_scanned_at": serp_result.get("last_scanned_at", "")
        }
        matrix.insert(0, new_item)

    bp["keyword_matrix"] = matrix

    with db_service.get_connection() as conn:
        with conn.cursor() as cur:
            cur.execute("UPDATE projects SET brand_profile = %s, updated_at = NOW() WHERE id = %s;", 
                        (json.dumps(bp, ensure_ascii=False), project_id))
            conn.commit()

    return {
        "status": "success",
        "project_id": project_id,
        "scanned_keyword": clean_kw,
        "item": existing_item or new_item,
        "keyword_matrix": matrix
    }



@router.delete("/api/v1/portal/projects/{project_id}/keywords/{keyword_id}")
def portal_delete_keyword(project_id: str, keyword_id: str):
    """Xóa một từ khóa khỏi Ma Trận Từ Khóa của dự án."""
    import db_service
    project = db_service.get_project_by_id(project_id)
    if not project:
        raise HTTPException(status_code=404, detail="Không tìm thấy dự án.")

    bp = project.get("brand_profile") or {}
    if isinstance(bp, str):
        try:
            bp = json.loads(bp)
        except Exception:
            bp = {}

    matrix = bp.get("keyword_matrix") or []
    matrix = [m for m in matrix if m.get("id") != keyword_id and m.get("keyword") != keyword_id]
    bp["keyword_matrix"] = matrix

    with db_service.get_connection() as conn:
        with conn.cursor() as cur:
            cur.execute("UPDATE projects SET brand_profile = %s, updated_at = NOW() WHERE id = %s;", 
                        (json.dumps(bp, ensure_ascii=False), project_id))
            conn.commit()

    return {"status": "success", "keyword_matrix": matrix}




@router.post("/api/v1/portal/projects/{project_id}/select-uvp")
def portal_select_uvp(project_id: str, req: PortalSelectUvp):
    """Chọn phương án UVP trực tiếp từ Client Portal."""
    import db_service
    project = db_service.get_project_by_id(project_id)
    if not project:
        raise HTTPException(status_code=404, detail="Không tìm thấy dự án.")

    suggested_uvps = project.get("suggested_uvp_options") or []
    if isinstance(suggested_uvps, str):
        suggested_uvps = json.loads(suggested_uvps)

    chosen_text = (req.custom_uvp_text or "").strip()
    if not chosen_text and req.selected_uvp_id is not None:
        for u in suggested_uvps:
            try:
                matches = int(u.get("id")) == req.selected_uvp_id
            except (TypeError, ValueError):
                matches = False
            if matches:
                chosen_text = f"[{u.get('title')}] {u.get('slogan')} - {u.get('rationale')}"
                break

    if not chosen_text:
        chosen_text = "Phương án định vị độc quyền được chọn"

    db_service.update_project_uvp(project_id, str(project["tenant_id"]), chosen_text)
    return {
        "status": "success",
        "project_id": project_id,
        "core_strengths": chosen_text
    }



