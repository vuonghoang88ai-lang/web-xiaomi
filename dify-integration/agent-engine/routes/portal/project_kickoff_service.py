#!/usr/bin/env python3
"""Project kickoff orchestration, competitor reconnaissance & keyword matrix service."""
import json
import logging
from fastapi import HTTPException
import db_service
import license_service
from serp_discovery_tool import inspect_website, generate_uvp_suggestions
from seed_analyzer import (
    run_continuous_seed_reconnaissance,
    generate_kickoff_briefing,
    generate_keyword_serp_matrix,
)

logger = logging.getLogger('marketing_engine')

def validate_project_domain_uniqueness(target_url: str, project_name: str) -> None:
    """Đảm bảo tên miền không bị trùng lặp trong hệ thống."""
    target_norm = license_service.normalize_domain_fqdn(target_url or project_name or "")
    with db_service.get_connection() as conn:
        with conn.cursor() as cur:
            cur.execute("SELECT id, name, target_website_url FROM projects;")
            for p in cur.fetchall():
                p_norm = license_service.normalize_domain_fqdn(p[2] or p[1] or "")
                if p_norm == target_norm:
                    raise HTTPException(
                        status_code=400,
                        detail=f"Tên miền '{target_norm}' đã tồn tại trong hệ thống (Dự án: '{p[1]}'). Vui lòng chọn dự án có sẵn để tiếp tục."
                    )

def kickoff_new_project(req, tenant: dict) -> dict:
    """
    Quy trình Zero-Click Autonomous Marketing Kickoff:
    1. Trinh sát Onpage website mục tiêu
    2. Giải mã đối thủ mẫu & ma trận SERP
    3. Sinh 4 phương án Định vị UVP độc quyền
    4. Sinh Executive Kickoff Briefing
    5. Lưu toàn bộ trí tuệ vào PostgreSQL
    """
    validate_project_domain_uniqueness(req.target_website_url, req.name)

    brand_profile = {}
    seed_recon_result = None
    my_site_data = {}
    verified_competitors = []
    suggested_uvps = []

    # 1. Trinh sát sâu Onpage website cần phát triển
    try:
        logger.info(f"Đang trinh sát Onpage website mục tiêu: {req.target_website_url}")
        my_site_data = inspect_website(req.target_website_url, timeout=6)
        brand_profile["my_site_audit"] = my_site_data
    except Exception as e:
        logger.warning(f"Lỗi khi cào Onpage website đích {req.target_website_url}: {e}")
        my_site_data = {"status": "error", "error": str(e), "title": req.name}
        brand_profile["my_site_audit"] = my_site_data

    # 2. Phân tích đối thủ mẫu & Quét 10 đối thủ Google SERP
    if req.seed_competitor_url:
        try:
            from seed_utils import normalize_url
            norm_seed = normalize_url(req.seed_competitor_url)
            req.seed_competitor_url = norm_seed
            logger.info(f"Đang tự động bóc tách đối thủ mẫu: {norm_seed}")
            seed_recon_result = run_continuous_seed_reconnaissance(
                seed_url=norm_seed,
                target_url=req.target_website_url,
                location=req.target_location or "",
                business_description=req.business_description or "",
                project_name=req.name or "",
                my_site_data=my_site_data if isinstance(my_site_data, dict) else None
            )
            brand_profile["seed_analysis"] = seed_recon_result.get("seed_analysis")
            brand_profile["extracted_keywords"] = seed_recon_result.get("extracted_keywords")
            if seed_recon_result.get("keyword_matrix"):
                brand_profile["keyword_matrix"] = seed_recon_result.get("keyword_matrix")
            verified_competitors = seed_recon_result.get("competitors") or []
        except Exception as e:
            logger.warning(f"Lỗi khi tự động phân tích đối thủ mẫu trong quá trình tạo dự án: {e}")

    # 3. Tự động sinh 4 phương án Định Vị UVP Độc Quyền
    try:
        logger.info("Đang sinh 4 phương án UVP độc quyền từ điểm mù thị trường...")
        suggested_uvps = generate_uvp_suggestions(
            req.business_description or "Kinh doanh và phân phối sản phẩm, dịch vụ",
            req.target_location or "Toàn quốc",
            verified_competitors
        )
    except Exception as e:
        logger.warning(f"Lỗi khi sinh phương án UVP: {e}")
        suggested_uvps = []

    # 4. Tự động sinh Bản Báo Cáo Khởi Động Dự Án (Executive Kickoff Briefing)
    briefing = ""
    try:
        briefing = generate_kickoff_briefing(
            project_name=req.name,
            target_url=req.target_website_url,
            my_site_data=my_site_data,
            seed_url=req.seed_competitor_url,
            seed_analysis=brand_profile.get("seed_analysis"),
            extracted_keywords=brand_profile.get("extracted_keywords") or [],
            competitors=verified_competitors,
            suggested_uvps=suggested_uvps,
            location=req.target_location or "Toàn quốc"
        )
        brand_profile["kickoff_briefing"] = briefing
    except Exception as e:
        logger.warning(f"Lỗi khi tạo Executive Kickoff Briefing: {e}")

    # 5. Lưu vào CSDL PostgreSQL
    brand_logo = my_site_data.get("logo_url") if isinstance(my_site_data, dict) else None
    project_id = db_service.save_project(
        tenant_id=str(tenant["id"]),
        name=req.name,
        target_website_url=req.target_website_url,
        business_description=req.business_description or "Kinh doanh và phân phối sản phẩm, dịch vụ",
        seed_competitor_url=req.seed_competitor_url,
        target_location=req.target_location or "",
        suggested_uvp_options=suggested_uvps,
        brand_logo_url=brand_logo,
        brand_profile=brand_profile
    )

    if verified_competitors:
        try:
            db_service.save_competitors(project_id, verified_competitors)
        except Exception as e:
            logger.warning(f"Lỗi lưu danh sách đối thủ: {e}")

    return {
        "status": "created",
        "project_id": project_id,
        "kickoff_briefing": briefing,
        "seed_analysis": brand_profile.get("seed_analysis"),
        "competitors_count": len(verified_competitors),
        "suggested_uvps_count": len(suggested_uvps),
        "message": f"Dự án '{req.name}' đã được khởi tạo và kích hoạt chiến dịch Marketing toàn diện thành công!"
    }

def ensure_project_keyword_matrix(project_id: str, data: dict, proj: dict, bp: dict) -> tuple:
    """Tự động đảm bảo ma trận từ khóa SERP đầy đủ và lưu cache vào CSDL nếu thiếu."""
    kw_matrix = bp.get("keyword_matrix")
    master_pool = bp.get("master_keyword_pool")
    if not kw_matrix or len(kw_matrix) < 30 or not master_pool:
        cats = []
        if data.get("seed_analysis"):
            cats = data["seed_analysis"].get("category_structure", [])
        matrix_res = generate_keyword_serp_matrix(
            extracted_keywords=data.get("extracted_keywords", []),
            categories=cats,
            target_url=proj.get("target_website_url", ""),
            seed_audit=data.get("seed_analysis"),
            business_description=proj.get("business_description") or "",
            competitors=data.get("competitors", []),
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
                    cur.execute(
                        "UPDATE projects SET brand_profile = %s WHERE id = %s;",
                        (json.dumps(bp, ensure_ascii=False), project_id)
                    )
                    conn.commit()
        except Exception as e:
            logger.warning(f"Lỗi lưu cache keyword_matrix: {e}")

    return kw_matrix, master_pool or []
