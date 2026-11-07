#!/usr/bin/env python3
"""Master Super Admin Portal Routes. Strictly complies with SOP 23 (<= 300 lines)."""
import os
import logging
from typing import Optional, Dict, Any, List
from datetime import datetime, timezone
from pydantic import BaseModel
from fastapi import APIRouter, HTTPException, Header, Depends
import db_service
import license_service
from psycopg2.extras import RealDictCursor

logger = logging.getLogger("marketing_engine")
router = APIRouter(prefix="/api/v1/portal/admin", tags=["Master Super Admin"])

MASTER_ADMIN_KEY = os.getenv("MASTER_ADMIN_KEY", "omni_master_admin_2026")

class AdminLoginRequest(BaseModel):
    master_key: str

class ConfigurePlanAdminRequest(BaseModel):
    plan_type: str
    custom_days: Optional[int] = None
    extend_existing: Optional[bool] = False

class ToggleStatusRequest(BaseModel):
    status: str # active or suspended

def verify_admin_auth(x_admin_key: Optional[str] = Header(None, alias="X-Admin-Key")):
    """Xác thực quyền Super Admin thông qua header X-Admin-Key."""
    if not x_admin_key or x_admin_key.strip() != MASTER_ADMIN_KEY:
        raise HTTPException(status_code=401, detail="Xác thực Admin không hợp lệ hoặc sai Master Key.")
    return True

@router.post("/login")
def admin_login(req: AdminLoginRequest):
    """Đăng nhập Super Admin Console bằng Master Key."""
    if req.master_key.strip() != MASTER_ADMIN_KEY:
        raise HTTPException(status_code=401, detail="Master Admin Key không chính xác.")
    return {
        "status": "success",
        "token": MASTER_ADMIN_KEY,
        "message": "Đăng nhập Super Admin thành công."
    }

@router.get("/stats")
def get_admin_dashboard_stats(_auth: bool = Depends(verify_admin_auth)):
    """Lấy các chỉ số thống kê tổng hợp toàn hệ thống."""
    license_service.ensure_license_schema()
    with db_service.get_connection() as conn:
        with conn.cursor(cursor_factory=RealDictCursor) as cur:
            cur.execute("""
                SELECT 
                    COUNT(*) as total_sites,
                    COUNT(*) FILTER (WHERE plan_type = 'trial_7d' AND plan_status = 'active' AND (plan_expires_at IS NULL OR plan_expires_at > NOW())) as trial_sites,
                    COUNT(*) FILTER (WHERE plan_type != 'trial_7d' AND plan_status = 'active' AND plan_expires_at > NOW()) as paid_sites,
                    COUNT(*) FILTER (WHERE plan_status = 'active' AND plan_expires_at > NOW() AND plan_expires_at <= NOW() + INTERVAL '7 days') as expiring_soon,
                    COUNT(*) FILTER (WHERE plan_status = 'expired' OR (plan_expires_at IS NOT NULL AND plan_expires_at <= NOW())) as expired_sites
                FROM projects;
            """)
            stats = cur.fetchone() or {}

    return {
        "status": "success",
        "stats": {
            "total_sites": stats.get("total_sites", 0),
            "trial_sites": stats.get("trial_sites", 0),
            "paid_sites": stats.get("paid_sites", 0),
            "expiring_soon": stats.get("expiring_soon", 0),
            "expired_sites": stats.get("expired_sites", 0),
        }
    }

@router.get("/licenses")
def list_all_licenses(_auth: bool = Depends(verify_admin_auth)):
    """Lấy danh sách tất cả các website/domain và trạng thái bản quyền."""
    license_service.ensure_license_schema()
    now = datetime.now(timezone.utc)
    results = []
    with db_service.get_connection() as conn:
        with conn.cursor(cursor_factory=RealDictCursor) as cur:
            cur.execute("""
                SELECT p.id, p.name, p.target_website_url, p.license_key, p.plan_type, p.plan_status,
                       p.plan_expires_at, p.last_heartbeat_at, p.created_at, t.name as tenant_name
                FROM projects p
                LEFT JOIN tenants t ON p.tenant_id = t.id
                ORDER BY p.created_at DESC;
            """)
            projects = cur.fetchall()

    for p in projects:
        lic_key = p.get("license_key")
        if not lic_key:
            lic = license_service.get_or_create_project_license(p["id"])
            lic_key = lic.get("license_key")
            exp = datetime.fromisoformat(lic["expires_at"]) if lic.get("expires_at") else None
            plan_type = lic.get("plan_type")
        else:
            exp = p.get("plan_expires_at")
            plan_type = p.get("plan_type") or "trial_7d"

        domain = license_service.normalize_domain_fqdn(p.get("target_website_url") or p.get("name") or "")
        is_expired = bool(exp and now > exp)
        days_left = round(max(0, (exp - now).total_seconds() / 86400), 1) if exp else 0.0

        plan_cfg = license_service.PLANS_CONFIG.get(plan_type, {})
        results.append({
            "project_id": str(p["id"]),
            "project_name": p.get("name"),
            "tenant_name": p.get("tenant_name") or "Enterprise",
            "domain": domain,
            "license_key": lic_key,
            "plan_type": plan_type,
            "plan_label": plan_cfg.get("label", "Gói Dịch Vụ"),
            "plan_status": "expired" if is_expired else (p.get("plan_status") or "active"),
            "is_expired": is_expired,
            "days_left": days_left,
            "expires_at": exp.isoformat() if exp else None,
            "last_heartbeat_at": p.get("last_heartbeat_at").isoformat() if p.get("last_heartbeat_at") else None,
            "created_at": p.get("created_at").isoformat() if p.get("created_at") else None
        })

    return {"status": "success", "licenses": results}

@router.delete("/licenses/{project_id}")
def admin_delete_license(project_id: str, _auth: bool = Depends(verify_admin_auth)):
    """Admin xóa vĩnh viễn một dự án/bản quyền khỏi hệ thống."""
    with db_service.get_connection() as conn:
        with conn.cursor() as cur:
            cur.execute("SELECT name, target_website_url FROM projects WHERE id = %s;", (project_id,))
            proj = cur.fetchone()
            if not proj:
                raise HTTPException(status_code=404, detail="Không tìm thấy dự án.")
            db_service.delete_project(project_id)

    return {"status": "success", "message": f"Đã xóa vĩnh viễn dự án '{proj[0]}' khỏi hệ thống."}

@router.post("/licenses/{project_id}/configure")
def admin_configure_license(project_id: str, req: ConfigurePlanAdminRequest, _auth: bool = Depends(verify_admin_auth)):
    """Admin nâng cấp / đổi gói hoặc gia hạn ngày cho dự án."""
    try:
        updated = license_service.configure_project_plan(
            project_id=project_id,
            plan_type=req.plan_type,
            custom_days=req.custom_days,
            extend_existing=bool(req.extend_existing)
        )
        return {
            "status": "success",
            "message": f"Đã áp dụng thành công {updated.get('plan_label')} (Còn {updated.get('days_left')} ngày).",
            "license": updated
        }
    except Exception as e:
        logger.error(f"Lỗi admin configure: {e}")
        raise HTTPException(status_code=400, detail=str(e))

@router.post("/licenses/{project_id}/toggle-status")
def admin_toggle_license_status(project_id: str, req: ToggleStatusRequest, _auth: bool = Depends(verify_admin_auth)):
    """Admin tạm khóa hoặc kích hoạt lại tên miền."""
    status = req.status.strip().lower()
    if status not in ("active", "suspended", "expired"):
        raise HTTPException(status_code=400, detail="Trạng thái không hợp lệ.")

    with db_service.get_connection() as conn:
        with conn.cursor() as cur:
            cur.execute("UPDATE projects SET plan_status = %s, updated_at = NOW() WHERE id = %s;", (status, project_id))
            conn.commit()

    return {"status": "success", "message": f"Đã chuyển trạng thái dự án sang '{status}'."}
