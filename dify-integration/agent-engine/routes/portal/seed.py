#!/usr/bin/env python3
"""Seed Competitor Analysis Route. Strictly complies with SOP 23 (<= 300 lines)."""
import json
import logging
from fastapi import APIRouter, HTTPException
from models import PortalAnalyzeSeedRequest
import db_service
from seed_analyzer import run_continuous_seed_reconnaissance

logger = logging.getLogger("marketing_engine")
router = APIRouter(prefix="/api/v1/portal/projects", tags=["Seed Competitor Analysis"])

@router.post("/{project_id}/analyze-seed")
def portal_analyze_seed_competitor(project_id: str, req: PortalAnalyzeSeedRequest):
    """Kích hoạt phân tích chuyên sâu đối thủ mẫu và truy kích 10 đối thủ SERP."""
    project = db_service.get_project_by_id(project_id)
    if not project:
        raise HTTPException(status_code=404, detail="Không tìm thấy dự án.")

    raw_seed = req.seed_competitor_url or project.get("seed_competitor_url")
    if not raw_seed:
        raise HTTPException(status_code=400, detail="Vui lòng cung cấp URL đối thủ mẫu.")

    from seed_utils import normalize_url
    seed_url = normalize_url(raw_seed)

    brand_profile = project.get("brand_profile") or {}
    if isinstance(brand_profile, str):
        try: brand_profile = json.loads(brand_profile)
        except Exception: brand_profile = {}

    my_site = brand_profile.get("my_site_audit") or brand_profile.get("target_analysis")

    recon = run_continuous_seed_reconnaissance(
        seed_url=seed_url,
        target_url=project.get("target_website_url"),
        location=project.get("target_location") or "",
        business_description=project.get("business_description") or "",
        project_name=project.get("name") or "",
        my_site_data=my_site if isinstance(my_site, dict) else None
    )

    brand_profile["seed_analysis"] = recon.get("seed_analysis")
    brand_profile["extracted_keywords"] = recon.get("extracted_keywords")
    if recon.get("target_analysis"):
        brand_profile["target_analysis"] = recon.get("target_analysis")
        brand_profile["my_site_audit"] = recon.get("target_analysis")
    if recon.get("head_to_head"):
        brand_profile["head_to_head"] = recon.get("head_to_head")
    if recon.get("keyword_matrix"):
        brand_profile["keyword_matrix"] = recon.get("keyword_matrix")

    with db_service.get_connection() as conn:
        with conn.cursor() as cur:
            cur.execute("""
                UPDATE projects 
                SET seed_competitor_url = %s,
                    brand_profile = %s,
                    updated_at = NOW()
                WHERE id = %s;
            """, (seed_url, json.dumps(brand_profile, ensure_ascii=False), project_id))
            conn.commit()

    if recon.get("competitors"):
        db_service.save_competitors(project_id, recon["competitors"])

    return {
        "status": "success",
        "project_id": project_id,
        "seed_analysis": recon.get("seed_analysis"),
        "target_analysis": recon.get("target_analysis"),
        "head_to_head": recon.get("head_to_head"),
        "competitors": recon.get("competitors"),
        "keyword_matrix": recon.get("keyword_matrix", []),
        "count": len(recon.get("competitors", [])),
        "message": f"Đã giải mã thành công đối thủ mẫu '{seed_url}' và cập nhật 10 đối thủ SERP Online!"
    }
