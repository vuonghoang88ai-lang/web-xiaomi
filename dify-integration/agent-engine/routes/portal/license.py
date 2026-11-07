#!/usr/bin/env python3
"""Portal License & Subscription Routes. Strictly complies with SOP 23 (<= 300 lines)."""
import logging
from typing import Optional
from pydantic import BaseModel
from fastapi import APIRouter, HTTPException, Request, Response
import license_service

logger = logging.getLogger("marketing_engine")
router = APIRouter(tags=["Portal License"])

class ConfigurePlanRequest(BaseModel):
    plan_type: str
    custom_days: Optional[int] = None

class ActivateLicenseRequest(BaseModel):
    license_key: str

class VerifyLicenseRequest(BaseModel):
    license_key: str
    domain: str
    cms_api_key: Optional[str] = None
    cms_api_url: Optional[str] = None
    seo_provider: Optional[str] = None
    publish_endpoint: Optional[str] = None

@router.post("/api/v1/portal/projects/{project_id}/license/activate")
def activate_project_license_api(project_id: str, req: ActivateLicenseRequest):
    """Khách hàng kích hoạt / gia hạn bản quyền thông qua License Key."""
    try:
        updated = license_service.activate_license_key_for_project(project_id, req.license_key)
        return {
            "status": "success",
            "message": f"Kích hoạt thành công {updated.get('plan_label')} (Còn {updated.get('days_left')} ngày).",
            "license": updated
        }
    except Exception as e:
        logger.error(f"Lỗi kích hoạt license: {e}")
        raise HTTPException(status_code=400, detail=str(e))


@router.get("/api/v1/portal/projects/{project_id}/license")
def get_project_license_api(project_id: str):
    """Lấy thông tin bản quyền và thời hạn gói cước của dự án."""
    try:
        return {
            "status": "success",
            "license": license_service.get_or_create_project_license(project_id)
        }
    except Exception as e:
        logger.error(f"Lỗi lấy thông tin license: {e}")
        raise HTTPException(status_code=400, detail=str(e))

@router.post("/api/v1/portal/projects/{project_id}/license/configure")
def configure_project_plan_api(project_id: str, req: ConfigurePlanRequest):
    """Cập nhật hoặc gia hạn gói cước cho dự án (khóa cứng theo tên miền FQDN)."""
    try:
        updated = license_service.configure_project_plan(
            project_id=project_id,
            plan_type=req.plan_type,
            custom_days=req.custom_days
        )
        return {
            "status": "success",
            "message": f"Đã áp dụng thành công {updated.get('plan_label')} (Còn {updated.get('days_left')} ngày).",
            "license": updated
        }
    except Exception as e:
        logger.error(f"Lỗi cấu hình gói cước: {e}")
        raise HTTPException(status_code=400, detail=str(e))

@router.get("/api/v1/portal/projects/{project_id}/license/download-plugin")
def download_customized_plugin_api(project_id: str, request: Request):
    """Tải file zip plugin cài đặt cá nhân hóa có nhúng sẵn omni-license.json cho dự án."""
    try:
        portal_base = f"{request.base_url.scheme}://{request.base_url.netloc}".rstrip("/")
        filename, zip_bytes = license_service.bundle_customized_plugin_zip(project_id, portal_base)
        return Response(
            content=zip_bytes,
            media_type="application/zip",
            headers={
                "Content-Disposition": f'attachment; filename="{filename}"',
                "Cache-Control": "no-cache, no-store, must-revalidate"
            }
        )
    except Exception as e:
        logger.error(f"Lỗi tạo zip cá nhân hóa: {e}")
        raise HTTPException(status_code=500, detail=f"Không thể đóng gói plugin: {e}")

@router.post("/api/v1/portal/license/verify")
def verify_license_heartbeat_api(req: VerifyLicenseRequest):
    """WordPress Plugin gọi lên để xác thực bản quyền và duy trì heartbeat."""
    res = license_service.verify_wp_heartbeat(
        license_key=req.license_key,
        client_domain=req.domain,
        sync_data={
            "cms_api_key": req.cms_api_key,
            "cms_api_url": req.cms_api_url,
            "seo_provider": req.seo_provider,
            "publish_endpoint": req.publish_endpoint
        }
    )
    if not res.get("valid"):
        status_code = 403 if res.get("status") in ("expired", "domain_mismatch") else 400
        raise HTTPException(status_code=status_code, detail=res)
    return {"status": "success", "data": res}
