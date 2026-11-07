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

@router.post("/api/v1/portal/projects/{project_id}/analyze-target")
def portal_analyze_target_website(project_id: str, req: Optional[PortalAnalyzeTargetRequest] = None):
    """
    Phân tích chuyên sâu 6 trụ cột cho Website đích (Target Website):
    - Đánh giá Title, Meta, H1, Silo URL, Schema JSON-LD, TTFB tốc độ, Word Count, Social Entity, Tuổi domain & DA Moz.
    - Xây dựng lại Ma trận đối đầu trực diện (Head-to-Head) nếu có dữ liệu đối thủ mẫu.
    - Cập nhật vào DB và trả về kết quả.
    """
    import db_service
    from seed_analyzer import analyze_seed_competitor, build_head_to_head_matrix

    project = db_service.get_project_by_id(project_id)
    if not project:
        raise HTTPException(status_code=404, detail="Không tìm thấy dự án.")

    target_url = (req.target_url if req else None) or project.get("target_website_url")
    if not target_url:
        raise HTTPException(status_code=400, detail="Vui lòng cung cấp URL Website đích của bạn.")

    tenant_id = project.get("tenant_id")
    target_audit = analyze_seed_competitor(target_url, timeout=5, tenant_id=tenant_id)
    
    brand_profile = project.get("brand_profile") or {}
    if isinstance(brand_profile, str):
        try:
            brand_profile = json.loads(brand_profile)
        except Exception:
            brand_profile = {}
    
    seed_analysis = brand_profile.get("seed_analysis") or {}
    head_to_head = build_head_to_head_matrix(target_audit, seed_analysis) if seed_analysis else []

    db_service.save_project_target_analysis(project_id, target_audit, head_to_head)

    return {
        "status": "success",
        "project_id": project_id,
        "target_url": target_url,
        "target_analysis": target_audit,
        "head_to_head": head_to_head,
        "message": f"Đã phân tích chuyên sâu 6 trụ cột cho Web đích '{target_url}' thành công!"
    }



@router.post("/api/v1/portal/projects/{project_id}/competitors/deep-audit")
def portal_deep_audit_single_competitor(project_id: str, req: PortalCompetitorDeepAuditRequest):
    """
    Phân tích đột kích 6 trụ cột chuyên sâu theo yêu cầu cho 1 đối thủ cụ thể trên SERP.
    Lưu vào scraped_structure của đối thủ trong project_competitors.
    """
    import db_service
    from seed_analyzer import analyze_seed_competitor

    project = db_service.get_project_by_id(project_id)
    if not project:
        raise HTTPException(status_code=404, detail="Không tìm thấy dự án.")

    comp_url = req.competitor_url
    if not comp_url:
        raise HTTPException(status_code=400, detail="Vui lòng cung cấp URL đối thủ cần phân tích.")

    tenant_id = project.get("tenant_id")
    deep_audit = analyze_seed_competitor(comp_url, timeout=5, tenant_id=tenant_id)

    db_service.update_competitor_deep_audit(project_id, req.competitor_domain or comp_url, deep_audit)

    return {
        "status": "success",
        "project_id": project_id,
        "competitor_url": comp_url,
        "competitor_domain": req.competitor_domain,
        "deep_audit": deep_audit,
        "message": f"Đã đột kích 6 trụ cột cho đối thủ '{comp_url}' thành công!"
    }



@router.post("/api/v1/portal/projects/{project_id}/competitors/batch-deep-audit")
def portal_batch_deep_audit_competitors(project_id: str):
    """
    Quét chuyên sâu 6 trụ cột đồng loạt cho toàn bộ danh sách đối thủ SERP của dự án (đa luồng ThreadPoolExecutor).
    """
    import db_service
    from seed_analyzer import deep_audit_competitor_batch

    project = db_service.get_project_by_id(project_id)
    if not project:
        raise HTTPException(status_code=404, detail="Không tìm thấy dự án.")

    intel = db_service.get_project_full_intelligence(project_id)
    competitors = intel.get("competitors", []) if intel else []
    if not competitors:
        raise HTTPException(status_code=400, detail="Dự án chưa có danh sách đối thủ. Vui lòng quét SERP trước.")

    comp_urls = [c["url"] for c in competitors if c.get("url")]
    tenant_id = project.get("tenant_id")
    results = deep_audit_competitor_batch(comp_urls, tenant_id=tenant_id, max_workers=5)

    updated_count = 0
    for comp in competitors:
        url = comp.get("url", "")
        domain = comp.get("domain", "")
        audit_res = results.get(url) or results.get(domain)
        if audit_res:
            db_service.update_competitor_deep_audit(project_id, domain or url, audit_res)
            updated_count += 1

    return {
        "status": "success",
        "project_id": project_id,
        "total_competitors": len(competitors),
        "audited_count": updated_count,
        "results": results,
        "message": f"Đã quét chuyên sâu 6 trụ cột thành công cho {updated_count}/{len(competitors)} đối thủ!"
    }







@router.post("/api/v1/portal/projects/{project_id}/rescan-my-site")
def portal_rescan_my_site(project_id: str):
    """
    Quét lại và phân tích sâu cấu trúc Onpage của Website Mục Tiêu (cần phát triển):
    - Kiểm tra trạng thái HTTP & Online 100%
    - Bóc tách Title, Meta Description, Thẻ H1, H2, Menu Silo, Hotline, Logo
    - Đánh giá khoảng trống Onpage & Điểm sức khỏe SEO
    - Cập nhật lại brand_profile['my_site_audit'] trong DB
    """
    import db_service
    from serp_discovery_tool import inspect_website

    project = db_service.get_project_by_id(project_id)
    if not project:
        raise HTTPException(status_code=404, detail="Không tìm thấy dự án.")

    target_url = project.get("target_website_url")
    if not target_url:
        raise HTTPException(status_code=400, detail="Dự án chưa cấu hình website mục tiêu.")

    logger.info(f"Đang quét lại On-page website mục tiêu: {target_url} (Project: {project_id})")
    my_site_data = inspect_website(target_url, timeout=8)

    # Đọc brand_profile hiện tại
    bp = project.get("brand_profile") or {}
    if isinstance(bp, str):
        try:
            bp = json.loads(bp)
        except Exception:
            bp = {}
    bp["my_site_audit"] = my_site_data

    # Cập nhật logo nếu bóc tách được và trước đó chưa có
    logo_url = my_site_data.get("logo_url")
    params = [json.dumps(bp, ensure_ascii=False)]
    update_logo_sql = ""
    if logo_url and not project.get("brand_logo_url"):
        update_logo_sql = ", brand_logo_url = %s"
        params.append(logo_url)
    params.append(project_id)

    try:
        with db_service.get_connection() as conn:
            with conn.cursor() as cur:
                cur.execute(f"UPDATE projects SET brand_profile = %s{update_logo_sql} WHERE id = %s;", params)
                conn.commit()
    except Exception as e:
        logger.error(f"Lỗi cập nhật my_site_audit vào DB: {e}")

    return {
        "status": "success",
        "project_id": project_id,
        "my_site_audit": my_site_data,
        "message": f"Đã quét và cập nhật báo cáo Onpage cho {target_url} thành công!"
    }



