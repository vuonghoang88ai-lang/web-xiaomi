import json
import logging
from fastapi import APIRouter, HTTPException
from models import PortalProjectCreate, PortalProjectUpdate
import db_service
from routes.portal.project_kickoff_service import (
    kickoff_new_project,
    ensure_project_keyword_matrix,
)

logger = logging.getLogger('marketing_engine')
router = APIRouter()

@router.get("/api/v1/portal/projects")
def portal_list_projects():
    """Lấy danh sách các Project cho giao diện Client Portal."""
    tenant, user = db_service.get_default_tenant_and_user()
    if not tenant:
        raise HTTPException(status_code=404, detail="Chưa có tenant nào trong hệ thống.")
    projects = db_service.list_projects_by_tenant(str(tenant["id"]))
    return {
        "tenant": tenant,
        "user": user,
        "projects": projects
    }

@router.post("/api/v1/portal/projects")
def portal_create_project(req: PortalProjectCreate):
    """
    Tạo mới một Project từ giao diện Client Portal (Zero-Click Autonomous Marketing Kickoff).
    """
    tenant, _ = db_service.get_default_tenant_and_user()
    return kickoff_new_project(req, tenant)

@router.put("/api/v1/portal/projects/{project_id}")
def portal_update_project(project_id: str, req: PortalProjectUpdate):
    """Cập nhật thông tin dự án từ giao diện Sửa Dự Án của Client Portal."""
    project = db_service.get_project_by_id(project_id)
    if not project:
        raise HTTPException(status_code=404, detail="Không tìm thấy dự án.")

    success = db_service.update_project_info(
        project_id=project_id,
        name=req.name.strip(),
        target_website_url=req.target_website_url.strip(),
        business_description=req.business_description.strip() if req.business_description else "",
        seed_competitor_url=req.seed_competitor_url.strip() if req.seed_competitor_url else None,
        target_location=req.target_location.strip() if req.target_location else ""
    )
    if not success:
        raise HTTPException(status_code=500, detail="Không thể cập nhật dự án vào CSDL.")

    seed_analysis = None
    competitors_count = 0
    if req.trigger_reanalyze and req.seed_competitor_url:
        from seed_analyzer import run_continuous_seed_reconnaissance
        from seed_utils import normalize_url
        try:
            bp_temp = project.get("brand_profile") or {}
            if isinstance(bp_temp, str):
                try: bp_temp = json.loads(bp_temp)
                except Exception: bp_temp = {}
            my_site = bp_temp.get("my_site_audit") or bp_temp.get("target_analysis")

            recon = run_continuous_seed_reconnaissance(
                seed_url=normalize_url(req.seed_competitor_url.strip()),
                target_url=req.target_website_url.strip(),
                location=req.target_location or "",
                business_description=req.business_description or project.get("business_description") or "",
                project_name=req.name or project.get("name") or "",
                my_site_data=my_site if isinstance(my_site, dict) else None
            )
            bp = project.get("brand_profile") or {}
            if isinstance(bp, str):
                bp = json.loads(bp)
            bp["seed_analysis"] = recon.get("seed_analysis")
            bp["extracted_keywords"] = recon.get("extracted_keywords")
            if recon.get("keyword_matrix"):
                bp["keyword_matrix"] = recon.get("keyword_matrix")

            with db_service.get_connection() as conn:
                with conn.cursor() as cur:
                    cur.execute(
                        "UPDATE projects SET brand_profile = %s WHERE id = %s;",
                        (json.dumps(bp, ensure_ascii=False), project_id)
                    )
                    conn.commit()

            if recon.get("competitors"):
                db_service.save_competitors(project_id, recon["competitors"])
            seed_analysis = recon.get("seed_analysis")
            competitors_count = len(recon.get("competitors", []))
        except Exception as e:
            logger.warning(f"Lỗi phân tích lại đối thủ mẫu khi sửa dự án: {e}")

    updated_project = db_service.get_project_by_id(project_id)
    return {
        "status": "success",
        "project": updated_project,
        "seed_analysis": seed_analysis,
        "competitors_count": competitors_count,
        "message": f"Dự án '{req.name}' đã được cập nhật thành công!"
    }

@router.delete("/api/v1/portal/projects/{project_id}")
def portal_delete_project_endpoint(project_id: str):
    """Xóa hoàn toàn Dự án khỏi cơ sở dữ liệu cùng các dữ liệu liên quan."""
    project = db_service.get_project_by_id(project_id)
    if not project:
        raise HTTPException(status_code=404, detail="Không tìm thấy dự án để xóa.")

    tenant, _ = db_service.get_default_tenant_and_user()
    db_service.delete_project(project_id)
    remaining_projects = db_service.list_projects_by_tenant(str(tenant["id"]))
    return {
        "status": "success",
        "deleted_project_id": project_id,
        "remaining_projects": remaining_projects,
        "message": f"Đã xóa hoàn toàn dự án '{project.get('name')}'."
    }

@router.get("/api/v1/portal/projects/{project_id}")
def portal_get_project_detail(project_id: str):
    """Lấy thông tin chi tiết trí tuệ của dự án (Web, Đối thủ, UVP, GSC, Seed Analysis, Keyword Matrix)."""
    data = db_service.get_project_full_intelligence(project_id)
    if not data:
        raise HTTPException(status_code=404, detail="Không tìm thấy dự án.")

    proj = data.get("project") or {}
    bp = proj.get("brand_profile") or {}
    if isinstance(bp, str):
        try:
            bp = json.loads(bp)
        except Exception:
            bp = {}
    data["seed_analysis"] = bp.get("seed_analysis")
    data["extracted_keywords"] = bp.get("extracted_keywords")
    data["kickoff_briefing"] = bp.get("kickoff_briefing")
    data["my_site_audit"] = bp.get("my_site_audit") or bp.get("target_analysis")
    data["target_analysis"] = bp.get("target_analysis") or bp.get("my_site_audit")
    data["head_to_head"] = bp.get("head_to_head")

    kw_matrix, master_pool = ensure_project_keyword_matrix(project_id, data, proj, bp)
    data["keyword_matrix"] = kw_matrix
    data["master_keyword_pool"] = master_pool
    return data
