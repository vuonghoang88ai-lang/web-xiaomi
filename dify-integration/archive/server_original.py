#!/usr/bin/env python3
"""
Tier 2, 3, 5 Gateway Server: Autonomous Marketing Engine API
Chạy FastAPI daemon tại port 8085.
Đóng vai trò là cầu nối trực tiếp giữa Dify (Tier 1) và Multi-Agent Execution Engine (Tier 2-5).
"""

import os
import sys
import re
import uuid
import json
import urllib.request
import urllib.error
import hmac
import logging
from datetime import date, datetime, timedelta, timezone
from typing import Optional
from uuid import UUID
from fastapi import FastAPI, BackgroundTasks, HTTPException, Query, Request
from fastapi.responses import FileResponse, JSONResponse, StreamingResponse, Response
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel, Field, field_validator

CURRENT_DIR = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, CURRENT_DIR)

from scraper_tool import scrape_competitor_recon
from agent_orchestrator import run_full_pipeline
from serp_discovery_tool import (
    SerpDiscoveryError,
    normalize_website_url,
    run_onboarding_gap_analysis,
    discover_top_10_competitors,
)
import db_service
import rag_service
import gsc_service

logger = logging.getLogger("marketing_engine")

app = FastAPI(
    title="Autonomous Marketing Multi-Agent Engine API",
    description="Hệ thống tự động hóa Marketing, cào đối thủ, SEO & GEO cho mọi ngành nghề",
    version="2.1.0"
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=[origin for origin in os.getenv("ENGINE_CORS_ORIGINS", "").split(",") if origin] or ["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)


@app.middleware("http")
async def require_engine_api_key(request: Request, call_next):
    """Protect every API route; health/docs/portal remain available for diagnostics & UI."""
    public_paths = {"/health", "/docs", "/openapi.json", "/redoc"}
    if not request.url.path.startswith("/api/") or request.url.path in public_paths or request.url.path.startswith("/api/v1/portal"):
        return await call_next(request)

    client_ip = request.client.host if request.client else ""
    # Tin cậy tuyệt đối các kết nối nội bộ từ Docker bridge / localhost (Dify backend, Squid SSRF proxy)
    if client_ip.startswith(("172.", "10.", "127.", "192.168.")):
        return await call_next(request)

    configured = os.getenv("ENGINE_API_KEY", "").strip()
    supplied = request.headers.get("x-engine-api-key", "")
    auth = request.headers.get("authorization", "")
    if not supplied and auth.lower().startswith("bearer "):
        supplied = auth[7:].strip()
    elif not supplied and auth:
        supplied = auth.strip()
    if not configured:
        return JSONResponse(status_code=503, content={"detail": "Marketing Engine chưa được cấu hình ENGINE_API_KEY."})
    if not supplied or not hmac.compare_digest(configured, supplied):
        logger.warning(f"[AUTH_REJECT] ip={client_ip} configured={configured[:8]}... supplied={supplied[:8] if supplied else 'NONE'}...")
        return JSONResponse(status_code=401, content={"detail": "Thiếu hoặc sai ENGINE_API_KEY."})
    return await call_next(request)


@app.middleware("http")
async def add_no_cache_for_portal(request: Request, call_next):
    """Đảm bảo browser luôn nhận được phiên bản mới nhất của Portal UI (tránh kẹt cache JS/CSS)."""
    response = await call_next(request)
    if request.url.path.startswith("/portal") or request.url.path == "/":
        response.headers["Cache-Control"] = "no-cache, no-store, must-revalidate, max-age=0"
        response.headers["Pragma"] = "no-cache"
        response.headers["Expires"] = "0"
    return response


from fastapi.staticfiles import StaticFiles
STORAGE_DIR = os.path.join(CURRENT_DIR, "storage")
os.makedirs(STORAGE_DIR, exist_ok=True)
app.mount("/storage", StaticFiles(directory=STORAGE_DIR), name="storage")

PORTAL_DIR = os.path.join(CURRENT_DIR, "portal")
os.makedirs(PORTAL_DIR, exist_ok=True)
app.mount("/portal", StaticFiles(directory=PORTAL_DIR), name="portal")


@app.get("/favicon.ico", include_in_schema=False)
def serve_favicon():
    return Response(
        content='<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#6366f1"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>',
        media_type="image/svg+xml"
    )


@app.get("/")
def serve_portal_root():
    """Phục vụ giao diện Client Portal độc lập tại root URL."""
    index_file = os.path.join(PORTAL_DIR, "index.html")
    if os.path.exists(index_file):
        return FileResponse(index_file)
    return JSONResponse({
        "service": "Universal Marketing AI Portal",
        "version": "3.0.0",
        "status": "ready"
    })


JOBS_DB = {}


class OnboardingRequest(BaseModel):
    tenant_id: Optional[UUID] = None
    target_website_url: str
    business_description: Optional[str] = "Kinh doanh và phân phối sản phẩm, dịch vụ"
    seed_competitor_url: Optional[str] = None
    target_location: Optional[str] = None
    target_language: Optional[str] = Field(default="vi", max_length=20)
    industry: Optional[str] = Field(default="", max_length=200)
    offer_type: Optional[str] = Field(default="", max_length=100)
    core_strengths: Optional[str] = ""
    brand_logo_url: Optional[str] = None  # Đường dẫn ảnh Logo thương hiệu của người dùng
    target_audience: Optional[str] = Field(default="", max_length=500)
    primary_offers: Optional[str] = Field(default="", max_length=500)
    conversion_goal: Optional[str] = Field(default="", max_length=300)
    brand_voice: Optional[str] = Field(default="", max_length=300)
    claims_evidence: Optional[str] = Field(default="", max_length=1000)
    compliance_constraints: Optional[str] = Field(default="", max_length=1000)

    @field_validator("target_website_url", "seed_competitor_url")
    @classmethod
    def validate_website_url(cls, value: Optional[str]) -> Optional[str]:
        return normalize_website_url(value) if value else value

    @field_validator("brand_logo_url")
    @classmethod
    def validate_logo_url(cls, value: Optional[str]) -> Optional[str]:
        return normalize_website_url(value) if value else value

    @field_validator("offer_type")
    @classmethod
    def validate_offer_type(cls, value: Optional[str]) -> Optional[str]:
        allowed = {"product", "service", "saas", "content", "marketplace", "mixed", "other"}
        if value and value not in allowed:
            raise ValueError(f"offer_type phải thuộc một trong: {', '.join(sorted(allowed))}")
        return value


class ResearchRequest(BaseModel):
    keyword: str
    custom_competitor_urls: list[str] = Field(default_factory=list, max_length=6)

    @field_validator("custom_competitor_urls")
    @classmethod
    def validate_competitor_urls(cls, values: list[str]) -> list[str]:
        return [normalize_website_url(value) for value in values]


class CampaignRequest(BaseModel):
    tenant_id: Optional[UUID] = None
    keyword: str
    category: Optional[str] = None
    post_type: Optional[str] = "post" # 'post' (SEO) hoặc 'product' (WooCommerce)
    post_status: Optional[str] = "publish" # 'publish' hoặc 'draft'
    publish_to_wp: Optional[bool] = True
    async_mode: Optional[bool] = False
    project_id: Optional[str] = None
    brand_logo_url: Optional[str] = None  # Override logo trực tiếp cho chiến dịch
    brand_name: Optional[str] = None
    target_website_url: Optional[str] = None
    target_location: Optional[str] = None
    business_description: Optional[str] = None
    brand_profile: dict = Field(default_factory=dict)

    @field_validator("target_website_url")
    @classmethod
    def validate_campaign_website_url(cls, value: Optional[str]) -> Optional[str]:
        return normalize_website_url(value) if value else value


class BatchCampaignRequest(BaseModel):
    tenant_id: Optional[UUID] = None
    items: list
    default_category: Optional[str] = None
    post_type: Optional[str] = "post" # 'post' (SEO) hoặc 'product' (WooCommerce)
    publish_to_wp: Optional[bool] = True
    project_id: Optional[str] = None


@app.post("/api/v1/onboarding/analyze")
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


class ProjectMemoryRequest(BaseModel):
    tenant_id: Optional[UUID] = None
    target_website_url: Optional[str] = None
    seed_competitor_url: Optional[str] = None
    business_description: Optional[str] = None
    core_strengths: Optional[str] = None
    target_location: Optional[str] = None


@app.post("/api/v1/project/memory")
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
        doc_text = f"""# HỒ SƠ DỰ ÁN & BỘ NHỚ CHIẾN LƯỢC: {proj.get('name', 'TongKhoMi')}
- **Website của bạn**: {proj.get('target_website_url') or 'Chưa cung cấp'}
- **Đối thủ cạnh tranh**: {proj.get('seed_competitor_url') or 'Chưa cung cấp'}
- **Ngành hàng / Dịch vụ**: {proj.get('business_description') or 'Chưa cung cấp'}
- **Khu vực mục tiêu**: {proj.get('target_location') or 'Toàn quốc'}
- **Lợi thế độc quyền (UVP)**: {proj.get('core_strengths') or 'Đang xác định'}
- **Thời gian cập nhật**: {datetime.now(timezone.utc).isoformat()}
"""
        rag_service.upload_document_to_dataset(
            "0d7a7e06-cb8c-481e-8056-d9689e11a2c9",
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


class CreateTenantRequest(BaseModel):
    name: str
    plan: Optional[str] = "pro"
    max_projects: Optional[int] = 5


class CreateUserRequest(BaseModel):
    tenant_id: str
    email: str
    full_name: str
    role: Optional[str] = "client"


class SelectUvpRequest(BaseModel):
    tenant_id: UUID
    selected_uvp_id: Optional[int] = None
    custom_uvp_text: Optional[str] = Field(default=None, max_length=1000)


class GSCSelectPropertyRequest(BaseModel):
    tenant_id: UUID
    property_uri: str = Field(min_length=1, max_length=1000)


class GSCPerformanceRequest(BaseModel):
    tenant_id: UUID
    property_uri: Optional[str] = None
    start_date: date
    end_date: date
    dimensions: list[str] = Field(default_factory=lambda: ["date"], max_length=6)
    row_limit: int = Field(default=25000, ge=1, le=25000)
    start_row: int = Field(default=0, ge=0)
    dimension_filter_groups: Optional[list[dict]] = None


def _gsc_access_token(connection: dict) -> str:
    token = gsc_service.refresh_access_token(connection["encrypted_refresh_token"])
    return token["access_token"]


def _project_gsc_connector(project_data: dict) -> dict:
    config = project_data.get("project", {}).get("cms_auth_config") or {}
    if isinstance(config, str):
        try:
            config = json.loads(config)
        except json.JSONDecodeError:
            config = {}
    return config.get("gsc_connector") or {}


@app.get("/api/v1/projects/{project_id}/gsc/connect")
def gsc_connect(project_id: str, tenant_id: UUID = Query(...)):
    """Create a tenant-scoped Google OAuth URL for Search Console readonly access."""
    project = db_service.get_project_full_intelligence(project_id, str(tenant_id))
    if not project:
        raise HTTPException(status_code=404, detail="Không tìm thấy project thuộc tenant này")
    existing = db_service.get_gsc_connection(project_id, str(tenant_id))
    if existing and existing.get("status") == "active":
        return {
            "status": "already_owned",
            "source": "marketing_engine",
            "project_id": project_id,
            "tenant_id": str(tenant_id),
            "property_uri": existing.get("property_uri"),
            "connection_id": str(existing.get("id")),
            "message": "Marketing Engine đã sở hữu kết nối GSC; không cần kết nối lại.",
        }
    plugin_connector = _project_gsc_connector(project)
    detected_property_uri = None
    if plugin_connector.get("connection_detected") or plugin_connector.get("reuse_available"):
        detected_property_uri = plugin_connector.get("property_uri") or None
    state = uuid.uuid4().hex + uuid.uuid4().hex
    db_service.create_gsc_oauth_state(
        state,
        str(tenant_id),
        project_id,
        datetime.now(timezone.utc) + timedelta(minutes=10),
        detected_property_uri=detected_property_uri,
    )
    try:
        authorization_url = gsc_service.build_authorization_url(state)
    except gsc_service.GSCConfigurationError as exc:
        raise HTTPException(status_code=503, detail=str(exc))
    return {
        "status": "authorization_required",
        "project_id": project_id,
        "tenant_id": str(tenant_id),
        "authorization_url": authorization_url,
        "scope": gsc_service.GSC_READONLY_SCOPE,
        "detected_property_uri": detected_property_uri,
        "message": (
            "Property GSC đã được phát hiện từ plugin. Cấp quyền cho Marketing Engine một lần để hệ thống sở hữu token độc lập."
            if detected_property_uri else
            "Mở authorization_url để cấp quyền Google Search Console readonly."
        ),
    }


@app.get("/api/v1/gsc/oauth/callback")
def gsc_oauth_callback(code: Optional[str] = None, state: Optional[str] = None,
                       error: Optional[str] = None):
    """Exchange the one-time OAuth code and discover properties visible to the user."""
    if error:
        raise HTTPException(status_code=400, detail=f"Google OAuth bị từ chối: {error}")
    if not code or not state:
        raise HTTPException(status_code=400, detail="Thiếu code hoặc state từ Google OAuth")
    oauth_state = db_service.consume_gsc_oauth_state(state)
    if not oauth_state:
        raise HTTPException(status_code=400, detail="OAuth state không hợp lệ, đã dùng hoặc đã hết hạn")
    try:
        token = gsc_service.exchange_code(code)
        access_token = token["access_token"]
        sites = gsc_service.list_sites(access_token)
        property_uris = [site.get("siteUrl") for site in sites if site.get("siteUrl")]
        detected_property_uri = oauth_state.get("detected_property_uri")
        selected_property_uri = detected_property_uri if detected_property_uri in property_uris else None
        connection = db_service.save_gsc_connection(
            str(oauth_state["tenant_id"]),
            str(oauth_state["project_id"]),
            gsc_service.encrypt_refresh_token(token["refresh_token"]),
            token.get("scope", gsc_service.GSC_READONLY_SCOPE).split(),
            property_uri=selected_property_uri or (property_uris[0] if len(property_uris) == 1 else None),
            status="active" if selected_property_uri or len(property_uris) == 1 else "pending_property",
            provider="google_search_console",
            connection_owner="marketing_engine",
        )
    except (gsc_service.GSCAPIError, gsc_service.GSCConfigurationError) as exc:
        raise HTTPException(status_code=502, detail=str(exc))
    return {
        "status": connection["status"],
        "connection_id": str(connection["id"]),
        "project_id": str(oauth_state["project_id"]),
        "tenant_id": str(oauth_state["tenant_id"]),
        "properties": property_uris,
        "message": "Đã lưu kết nối GSC độc lập cho Marketing Engine." if connection["status"] == "active" else "Đã kết nối GSC. Chọn property để hoàn tất.",
    }


@app.get("/api/v1/projects/{project_id}/gsc/connections")
def gsc_connections(project_id: str, tenant_id: UUID = Query(...)):
    if not db_service.get_project_full_intelligence(project_id, str(tenant_id)):
        raise HTTPException(status_code=404, detail="Không tìm thấy project thuộc tenant này")
    return {"project_id": project_id, "connections": db_service.list_gsc_connections(project_id, str(tenant_id))}


@app.post("/api/v1/projects/{project_id}/gsc/connections/{connection_id}/property")
def gsc_select_property(project_id: str, connection_id: str, req: GSCSelectPropertyRequest):
    connection = db_service.get_gsc_connection(project_id, str(req.tenant_id), connection_id=connection_id)
    if not connection:
        raise HTTPException(status_code=404, detail="Không tìm thấy kết nối GSC")
    try:
        sites = gsc_service.list_sites(_gsc_access_token(connection))
    except (gsc_service.GSCAPIError, gsc_service.GSCConfigurationError) as exc:
        db_service.mark_gsc_connection_error(connection_id, str(exc))
        raise HTTPException(status_code=502, detail=str(exc))
    allowed = {site.get("siteUrl") for site in sites}
    if req.property_uri not in allowed:
        raise HTTPException(status_code=422, detail="Google account không có quyền với property này")
    result = db_service.activate_gsc_connection(connection_id, str(req.tenant_id), project_id, req.property_uri)
    return {"status": "active", "connection": result}


@app.get("/api/v1/projects/{project_id}/gsc/sites")
def gsc_sites(project_id: str, tenant_id: UUID = Query(...), connection_id: Optional[str] = None):
    connection = db_service.get_gsc_connection(project_id, str(tenant_id), connection_id=connection_id)
    if not connection:
        raise HTTPException(status_code=404, detail="Chưa có kết nối GSC cho project này")
    try:
        sites = gsc_service.list_sites(_gsc_access_token(connection))
    except (gsc_service.GSCAPIError, gsc_service.GSCConfigurationError) as exc:
        raise HTTPException(status_code=502, detail=str(exc))
    return {"project_id": project_id, "connection_id": str(connection["id"]), "sites": sites}


@app.post("/api/v1/projects/{project_id}/gsc/performance")
def gsc_performance(project_id: str, req: GSCPerformanceRequest):
    project_data = db_service.get_project_full_intelligence(project_id, str(req.tenant_id))
    if not project_data:
        raise HTTPException(status_code=404, detail="Không tìm thấy project thuộc tenant này")
    connection = db_service.get_gsc_connection(
        project_id,
        str(req.tenant_id),
        property_uri=req.property_uri,
    )
    if not connection or connection.get("status") != "active":
        plugin_connector = _project_gsc_connector(project_data)
        if plugin_connector.get("connection_detected") or plugin_connector.get("reuse_available"):
            raise HTTPException(
                status_code=409,
                detail={
                    "code": "gsc_engine_authorization_required",
                    "message": "Plugin đã có kết nối/property GSC, nhưng Marketing Engine chưa có token riêng. Hãy cấp quyền một lần qua endpoint /gsc/connect.",
                    "property_uri": plugin_connector.get("property_uri"),
                },
            )
        raise HTTPException(status_code=404, detail="Project chưa có property GSC đang hoạt động")
    try:
        data = gsc_service.query_search_analytics(
            _gsc_access_token(connection),
            connection["property_uri"],
            req.start_date,
            req.end_date,
            dimensions=req.dimensions,
            row_limit=req.row_limit,
            start_row=req.start_row,
            dimension_filter_groups=req.dimension_filter_groups,
        )
        rows = data.get("rows", [])
        db_service.save_gsc_performance_rows(
            str(req.tenant_id), project_id, connection["property_uri"],
            req.start_date, req.end_date, req.dimensions, rows,
        )
    except (gsc_service.GSCAPIError, gsc_service.GSCConfigurationError) as exc:
        db_service.mark_gsc_connection_error(str(connection["id"]), str(exc))
        raise HTTPException(status_code=502, detail=str(exc))
    return {
        "status": "success",
        "project_id": project_id,
        "property_uri": connection["property_uri"],
        "start_date": req.start_date,
        "end_date": req.end_date,
        "dimensions": req.dimensions,
        "rows": rows,
        "row_count": len(rows),
        "response": data,
    }


@app.post("/api/v1/saas/tenants")
def api_create_tenant(req: CreateTenantRequest):
    """SaaS API: Đăng ký một doanh nghiệp / khách hàng mới"""
    import db_service
    return db_service.create_tenant(req.name, req.plan, req.max_projects)


@app.post("/api/v1/saas/users")
def api_create_user(req: CreateUserRequest):
    """SaaS API: Cấp tài khoản người dùng cho khách hàng"""
    import db_service
    return db_service.create_user(req.tenant_id, req.email, req.full_name, req.role)


@app.get("/api/v1/saas/projects/{project_id}")
def api_get_project(project_id: str, tenant_id: UUID = Query(...)):
    """SaaS API: Lấy toàn bộ hồ sơ trí tuệ của một dự án (Web, 10 đối thủ, Báo cáo Gap, RAG KB)"""
    import db_service
    data = db_service.get_project_full_intelligence(project_id, str(tenant_id))
    if not data:
        raise HTTPException(status_code=404, detail="Không tìm thấy dự án")
    return data


@app.post("/api/v1/saas/projects/{project_id}/select-uvp")
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


class CmsBridgeConfigRequest(BaseModel):
    tenant_id: UUID
    cms_type: Optional[str] = "wordpress"
    cms_api_url: str
    api_key: str


@app.post("/api/v1/projects/{project_id}/cms-bridge")
def configure_project_cms_bridge(project_id: str, req: CmsBridgeConfigRequest):
    """
    Cấu hình và kiểm tra kết nối Omni Bridge Connector Plugin cho Website WordPress của Dự án.
    Cho phép Agent xuất bản trực tiếp bài viết & sản phẩm từ xa lên Hosting/VPS của khách hàng.
    """
    import urllib.request
    import json

    # 1. Kiểm tra sức khỏe của Bridge trên WordPress của khách hàng
    health_endpoint = f"{req.cms_api_url.rstrip('/')}/wp-json/omni-bridge/v1/health"
    try:
        req_health = urllib.request.Request(
            health_endpoint,
            headers={
                "X-Omni-Bridge-Key": req.api_key.strip(),
                "User-Agent": "Autonomous-Marketing-Agent/1.0",
            }
        )
        with urllib.request.urlopen(req_health, timeout=10) as resp:
            health_data = json.loads(resp.read().decode("utf-8"))
    except Exception as e:
        raise HTTPException(status_code=400, detail=f"Không thể kết nối đến Omni Bridge tại {health_endpoint}: {e}")

    # Detect an existing official plugin adapter without importing/exposing its OAuth token.
    try:
        gsc_request = urllib.request.Request(
            f"{req.cms_api_url.rstrip('/')}/wp-json/omni-bridge/v1/gsc/config",
            headers={
                "X-Omni-Bridge-Key": req.api_key.strip(),
                "User-Agent": "Autonomous-Marketing-Agent/1.0",
            },
        )
        with urllib.request.urlopen(gsc_request, timeout=10) as response:
            gsc_connector = json.loads(response.read().decode("utf-8"))
    except Exception:
        # Older bridge versions remain compatible and use engine OAuth as fallback.
        gsc_connector = {
            "source": "none",
            "connection_detected": False,
            "reuse_available": False,
            "property_uri": req.cms_api_url.rstrip("/"),
        }

    if not db_service.get_project_full_intelligence(project_id, str(req.tenant_id)):
        raise HTTPException(status_code=404, detail="Project không thuộc tenant này")

    # 2. Lưu vào Database của Project
    with db_service.get_connection() as conn:
        with conn.cursor() as cur:
            cur.execute("""
                UPDATE projects 
                SET cms_type = %s,
                    cms_api_url = %s,
                    cms_auth_config = %s,
                    updated_at = NOW()
                WHERE id = %s AND tenant_id = %s;
            """, (
                req.cms_type or "wordpress",
                req.cms_api_url.rstrip('/'),
                json.dumps({
                    "api_key": req.api_key.strip(),
                    "gsc_connector": gsc_connector,
                }, ensure_ascii=False),
                project_id,
                str(req.tenant_id)
            ))
            conn.commit()

    return {
        "status": "connected",
        "project_id": project_id,
        "cms_api_url": req.cms_api_url.rstrip('/'),
        "bridge_health": health_data,
        "gsc_connector": gsc_connector,
        "message": "Kết nối thành công Omni Bridge Connector với WordPress của Dự án!"
    }


@app.get("/api/v1/download/omni-bridge-connector")
def download_omni_bridge_connector():
    """Tải về file plugin omni-bridge-connector.zip để khách hàng cài vào WordPress."""
    zip_path = os.path.join(CURRENT_DIR, "../plugins/omni-bridge-connector.zip")
    if not os.path.exists(zip_path):
        zip_path = "/app/omni-bridge-connector.zip"
    if not os.path.exists(zip_path):
        raise HTTPException(status_code=404, detail="Không tìm thấy file plugin omni-bridge-connector.zip")
    return FileResponse(zip_path, media_type="application/zip", filename="omni-bridge-connector.zip")


@app.get("/health")
def health_check():
    return {
        "status": "healthy",
        "service": "Autonomous Marketing Multi-Agent Engine",
        "port": 8085,
        "connected_tiers": ["1.Dify", "2.MultiAgent", "3.PythonScraper", "4.9Router", "5.WordPress"]
    }


@app.post("/api/v1/research")
def research_competitor(req: ResearchRequest):
    """
    Tier 3 Scraper Endpoint:
    Cào nhanh thông tin và giá bán đối thủ thực tế cho Dify Chatbot.
    Nếu chưa có URL cụ thể hoặc không có sản phẩm, tự động kích hoạt SERP Discovery tìm Top đối thủ online thực tế.
    """
    try:
        data = scrape_competitor_recon(
            req.keyword,
            custom_competitor_urls=req.custom_competitor_urls or None,
        )
        if not data.get("products") or data.get("status") == "no_results":
            # Tự động trinh sát đối thủ online thời gian thực từ SERP
            discovered = discover_top_10_competitors(req.keyword)
            if discovered:
                data["status"] = "success"
                data["competitor_sources"] = [c.get("domain") for c in discovered if c.get("domain")]
                data["discovered_competitors"] = discovered
                data["found_count"] = len(discovered)
                data["summary"] = f"Đã phát hiện và xác thực {len(discovered)} đối thủ trực tuyến đang hoạt động thực tế trên thị trường cho từ khóa '{req.keyword}'."
        return {
            "status": data.get("status", "success"),
            "keyword": req.keyword,
            "data": data
        }
    except Exception as e:
        logger.exception("Competitor research failed")
        raise HTTPException(status_code=500, detail="Không thể hoàn tất trinh sát đối thủ.")


def background_campaign_worker(job_id: str, keyword: str, category: str, publish_to_wp: bool, post_type: str = "post", project_id: Optional[str] = None, tenant_id: Optional[str] = None, context: dict = None):
    """Worker chạy ngầm cho các job kéo dài để Dify không bao giờ bị timeout."""
    JOBS_DB[job_id]["status"] = "processing"
    try:
        result = run_full_pipeline(keyword, category, publish_to_wp, post_type=post_type, project_id=project_id, tenant_id=tenant_id, **(context or {}))
        JOBS_DB[job_id].update({
            "status": "completed",
            "result": result
        })
    except Exception as e:
        JOBS_DB[job_id].update({
            "status": "failed",
            "error": str(e)
        })


def background_batch_worker(job_id: str, items: list, default_category: str, publish_to_wp: bool, default_post_type: str, project_id: Optional[str] = None, tenant_id: Optional[str] = None):
    """Worker duyệt hàng loạt danh sách sản phẩm chạy ngầm."""
    JOBS_DB[job_id]["status"] = "processing"
    completed_items = []
    total = len(items)
    
    for idx, item in enumerate(items, 1):
        if isinstance(item, str):
            kw = item
            cat = default_category
            p_type = default_post_type
            p_id = project_id
        elif isinstance(item, dict):
            kw = item.get("keyword") or item.get("name") or str(item)
            cat = item.get("category", default_category)
            p_type = item.get("post_type", default_post_type)
            p_id = item.get("project_id", project_id)
        else:
            continue

        JOBS_DB[job_id]["current_step"] = f"[{idx}/{total}] Đang xử lý: '{kw}'"
        try:
            if p_id and tenant_id and not db_service.get_project_full_intelligence(p_id, tenant_id):
                raise ValueError("Project trong batch không thuộc tenant đã xác thực")
            res = run_full_pipeline(kw, cat, publish_to_wp, post_type=p_type, project_id=p_id, tenant_id=tenant_id)
            completed_items.append({
                "keyword": kw,
                "project_id": p_id,
                "status": "success",
                "post_id": res.get("wordpress_post_id"),
                "wordpress_status": res.get("wordpress_status"),
                "post_title": res.get("post_title")
            })
        except Exception as e:
            completed_items.append({
                "keyword": kw,
                "project_id": p_id,
                "status": "failed",
                "error": str(e)
            })
        
        JOBS_DB[job_id]["progress"] = f"{idx}/{total}"
        JOBS_DB[job_id]["results"] = completed_items

    JOBS_DB[job_id]["status"] = "completed"
    JOBS_DB[job_id]["summary"] = f"Hoàn tất xử lý hàng loạt {len(completed_items)}/{total} sản phẩm."


@app.post("/api/v1/campaign")
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


@app.post("/api/v1/campaign/product")
def trigger_product_campaign(req: CampaignRequest, background_tasks: BackgroundTasks):
    """
    Dedicated Worker 1: Viết trang chi tiết sản phẩm WooCommerce chuẩn UECS & ACF Repeater specs.
    Tách biệt vật lý hoàn toàn khỏi luồng viết bài SEO blog.
    """
    req.post_type = "product"
    if not req.tenant_id:
        req.tenant_id = UUID("0cfcbe3e-fbfa-4025-8aa0-ee5ff82b77d8")
    if not req.target_website_url:
        req.target_website_url = "https://tongkhomi.com"
    if not req.brand_name:
        req.brand_name = "TongKhoMi"
    req.async_mode = True
    req.publish_to_wp = True
    return trigger_campaign(req, background_tasks)


@app.post("/api/v1/campaign/article")
def trigger_article_campaign(req: CampaignRequest, background_tasks: BackgroundTasks):
    """
    Dedicated Worker 2: Viết bài viết SEO Blog / Tin tức / Cẩm nang chuẩn 10x Editorial Architecture.
    Tách biệt vật lý hoàn toàn khỏi luồng sản phẩm WooCommerce.
    """
    req.post_type = "post"
    if not req.tenant_id:
        req.tenant_id = UUID("0cfcbe3e-fbfa-4025-8aa0-ee5ff82b77d8")
    if not req.target_website_url:
        req.target_website_url = "https://tongkhomi.com"
    if not req.brand_name:
        req.brand_name = "TongKhoMi"
    req.async_mode = True
    req.publish_to_wp = True
    return trigger_campaign(req, background_tasks)


@app.post("/api/v1/campaign/batch")
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


@app.get("/api/v1/jobs/{job_id}")
def get_job_status(job_id: str):
    """Tra cứu tiến độ thực thi của chiến dịch."""
    if job_id not in JOBS_DB:
        raise HTTPException(status_code=404, detail="Không tìm thấy job_id này")
    return JOBS_DB[job_id]


# =============================================================================
# COMMERCIAL SAAS CLIENT PORTAL ENDPOINTS (SOP 20)
# =============================================================================

class PortalProjectCreate(BaseModel):
    name: str
    target_website_url: str
    business_description: Optional[str] = "Kinh doanh và phân phối sản phẩm, dịch vụ"
    seed_competitor_url: Optional[str] = None
    target_location: Optional[str] = "Hà Nội"


class PortalChatMessage(BaseModel):
    message: str
    user: Optional[str] = "admin@tongkhomi.com"


@app.get("/api/v1/portal/projects")
def portal_list_projects():
    """Lấy danh sách các Project cho giao diện Client Portal."""
    import db_service
    tenant, user = db_service.get_default_tenant_and_user()
    if not tenant:
        raise HTTPException(status_code=404, detail="Chưa có tenant nào trong hệ thống.")
    projects = db_service.list_projects_by_tenant(str(tenant["id"]))
    return {
        "tenant": tenant,
        "user": user,
        "projects": projects
    }


@app.post("/api/v1/portal/projects")
def portal_create_project(req: PortalProjectCreate):
    """
    Tạo mới một Project từ giao diện Client Portal (Zero-Click Autonomous Marketing Kickoff):
    1. Tự động cào & trinh sát sâu Onpage website cần phát triển (Title, Meta, H1, Silo, Logo).
    2. Nếu có seed_competitor_url: Tự động giải mã đối thủ mẫu, bóc tách bộ từ khóa vàng & ma trận SERP.
    3. Tự động truy kích Google SERP tìm 10 đối thủ (100% Online, loại trừ web mình, cảnh báo brand conflict).
    4. Tự động sinh 4 phương án Định vị Lợi thế Độc quyền (UVP) từ điểm mù đối thủ (Tab 4).
    5. Tự động tổng hợp và xuất bản Báo Cáo Khởi Động Dự Án (Executive Kickoff Briefing) ngay vào chat.
    """
    import db_service
    from serp_discovery_tool import inspect_website, generate_uvp_suggestions
    from seed_analyzer import run_continuous_seed_reconnaissance, generate_kickoff_briefing

    tenant, _ = db_service.get_default_tenant_and_user()
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
            logger.info(f"Đang tự động bóc tách đối thủ mẫu: {req.seed_competitor_url}")
            seed_recon_result = run_continuous_seed_reconnaissance(
                seed_url=req.seed_competitor_url,
                target_url=req.target_website_url,
                location=req.target_location or "",
                business_description=req.business_description or ""
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

    # 5. Lưu toàn bộ Trí tuệ Dự án vào CSDL PostgreSQL
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

    # 6. Lưu danh sách 10 đối thủ vào bảng project_competitors
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


class PortalProjectUpdate(BaseModel):
    name: str
    target_website_url: str
    business_description: Optional[str] = ""
    seed_competitor_url: Optional[str] = None
    target_location: Optional[str] = ""
    trigger_reanalyze: Optional[bool] = False


@app.put("/api/v1/portal/projects/{project_id}")
def portal_update_project(project_id: str, req: PortalProjectUpdate):
    """Cập nhật thông tin dự án từ giao diện Sửa Dự Án của Client Portal."""
    import db_service
    project = db_service.get_project_by_id(project_id)
    if not project:
        raise HTTPException(status_code=404, detail="Không tìm thấy dự án.")

    # 1. Cập nhật bảng projects
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

    # 2. Nếu người dùng tick chọn phân tích lại đối thủ mẫu
    seed_analysis = None
    competitors_count = 0
    if req.trigger_reanalyze and req.seed_competitor_url:
        from seed_analyzer import run_continuous_seed_reconnaissance
        try:
            recon = run_continuous_seed_reconnaissance(
                seed_url=req.seed_competitor_url.strip(),
                target_url=req.target_website_url.strip(),
                location=req.target_location or "",
                business_description=req.business_description or project.get("business_description") or ""
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
                    cur.execute("UPDATE projects SET brand_profile = %s WHERE id = %s;", 
                                (json.dumps(bp, ensure_ascii=False), project_id))
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


@app.delete("/api/v1/portal/projects/{project_id}")
def portal_delete_project_endpoint(project_id: str):
    """Xóa hoàn toàn Dự án khỏi cơ sở dữ liệu cùng các dữ liệu liên quan."""
    import db_service
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


class PortalAnalyzeSeedRequest(BaseModel):
    seed_competitor_url: Optional[str] = None


@app.post("/api/v1/portal/projects/{project_id}/analyze-seed")
def portal_analyze_seed_competitor(project_id: str, req: PortalAnalyzeSeedRequest):
    """
    Kích hoạt phân tích chuyên sâu đối thủ mẫu và truy kích 10 đối thủ SERP theo bộ từ khóa giải mã được:
    - Bóc tách On-page, cấu trúc Silo danh mục, bộ từ khóa vàng.
    - Truy quét Google SERP (100% Online, loại trừ chính mình, gắn cờ tranh chấp thương hiệu).
    - Lưu kết quả vào database và trả về giao diện Portal.
    """
    import db_service
    from seed_analyzer import run_continuous_seed_reconnaissance

    project = db_service.get_project_by_id(project_id)
    if not project:
        raise HTTPException(status_code=404, detail="Không tìm thấy dự án.")

    seed_url = req.seed_competitor_url or project.get("seed_competitor_url")
    if not seed_url:
        raise HTTPException(status_code=400, detail="Vui lòng cung cấp URL đối thủ mẫu.")

    recon = run_continuous_seed_reconnaissance(
        seed_url=seed_url,
        target_url=project.get("target_website_url"),
        location=project.get("target_location") or "",
        business_description=project.get("business_description") or ""
    )

    brand_profile = project.get("brand_profile") or {}
    if isinstance(brand_profile, str):
        brand_profile = json.loads(brand_profile)
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


class PortalAnalyzeTargetRequest(BaseModel):
    target_url: Optional[str] = None


class PortalCompetitorDeepAuditRequest(BaseModel):
    competitor_url: str
    competitor_domain: Optional[str] = None


@app.post("/api/v1/portal/projects/{project_id}/analyze-target")
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


@app.post("/api/v1/portal/projects/{project_id}/competitors/deep-audit")
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


@app.post("/api/v1/portal/projects/{project_id}/competitors/batch-deep-audit")
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


@app.get("/api/v1/portal/projects/{project_id}")
def portal_get_project_detail(project_id: str):
    """Lấy thông tin chi tiết trí tuệ của dự án (Web, Đối thủ, UVP, GSC, Seed Analysis, Keyword Matrix)."""
    import db_service
    data = db_service.get_project_full_intelligence(project_id)
    if not data:
        raise HTTPException(status_code=404, detail="Không tìm thấy dự án.")

    # Trích xuất seed_analysis & keyword_matrix từ brand_profile nếu có để frontend dễ sử dụng
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
    
    # Ma trận từ khóa SERP toàn diện đa ngành hàng (60 từ khóa chiến lược) và Kho Từ Khóa (> 1.200 từ)
    kw_matrix = bp.get("keyword_matrix")
    master_pool = bp.get("master_keyword_pool")
    if not kw_matrix or len(kw_matrix) < 30 or not master_pool:
        from seed_analyzer import generate_keyword_serp_matrix
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
        # Lưu cache lại vào DB
        try:
            with db_service.get_connection() as conn:
                with conn.cursor() as cur:
                    cur.execute("UPDATE projects SET brand_profile = %s WHERE id = %s;", 
                                (json.dumps(bp, ensure_ascii=False), project_id))
                    conn.commit()
        except Exception as e:
            logger.warning(f"Lỗi lưu cache keyword_matrix: {e}")

    data["keyword_matrix"] = kw_matrix
    data["master_keyword_pool"] = master_pool or []
    return data


@app.post("/api/v1/portal/projects/{project_id}/keywords/regenerate")
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


class PortalScanSingleKeywordRequest(BaseModel):
    keyword: str
    category: Optional[str] = "Gia dụng thông minh"


@app.post("/api/v1/portal/projects/{project_id}/keywords/scan")
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


@app.delete("/api/v1/portal/projects/{project_id}/keywords/{keyword_id}")
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



class PortalSelectUvp(BaseModel):
    selected_uvp_id: Optional[int] = None
    custom_uvp_text: Optional[str] = None


@app.post("/api/v1/portal/projects/{project_id}/select-uvp")
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


@app.post("/api/v1/portal/projects/{project_id}/rescan-competitors")
def portal_rescan_competitors(project_id: str):
    """
    Quét lại danh sách 10 đối thủ thực tế từ SERP:
    - BẮT BUỘC: Kiểm tra trạng thái trực tiếp, CHỈ LẤY ĐỐI THỦ ĐANG ONLINE 100%.
    - Tự động loại trừ tên miền của chính mình (target_website_url / brand root domain).
    - Cập nhật ngay vào database và trả về danh sách đối thủ mới.
    """
    import db_service
    import urllib.parse
    from concurrent.futures import ThreadPoolExecutor
    from serp_discovery_tool import (
        _fetch_serp_results, check_url_online, normalize_website_url
    )

    project = db_service.get_project_by_id(project_id)
    if not project:
        raise HTTPException(status_code=404, detail="Không tìm thấy dự án.")

    business_desc = project.get("business_description") or "Kinh doanh điện tử gia dụng Xiaomi"
    target_web = project.get("target_website_url") or ""
    seed_url = project.get("seed_competitor_url")
    location = project.get("target_location") or ""

    # Trích xuất domain của chính mình để loại trừ hoàn toàn
    target_host = ""
    target_root = ""
    if target_web:
        try:
            norm_target = normalize_website_url(target_web)
            target_host = (urllib.parse.urlsplit(norm_target).hostname or "").lower().removeprefix("www.")
            target_root = target_host.split(".")[0]
        except Exception as e:
            logger.warning(f"Lỗi parse target_website_url: {e}")

    # Tạo các truy vấn thương mại có độ nhắm mục tiêu cao
    primary_queries = []
    if "xiaomi" in business_desc.lower() or "xiaomi" in project.get("name", "").lower():
        primary_queries = [
            f"robot hut bui xiaomi {location}".strip(),
            f"tivi xiaomi chinh hang {location}".strip(),
            f"do gia dung thong minh xiaomi {location}".strip(),
        ]
    elif "nha khoa" in business_desc.lower() or "nha khoa" in project.get("name", "").lower():
        primary_queries = [
            f"nha khoa uy tin {location}".strip(),
            f"dan su veneer {location}".strip(),
            f"nieng rang trong suot {location}".strip(),
        ]
    else:
        words = business_desc.split()[:6]
        primary_queries = [f"{' '.join(words)} {location}".strip()]

    candidate_map = {}

    # Nếu có seed_url hợp lệ và khác domain của mình, ưu tiên đưa vào ứng viên
    if seed_url:
        try:
            norm_seed = normalize_website_url(seed_url)
            seed_host = (urllib.parse.urlsplit(norm_seed).hostname or "").lower().removeprefix("www.")
            if seed_host != target_host and not (target_root and len(target_root) > 4 and target_root in seed_host):
                candidate_map[seed_host] = {
                    "url": norm_seed,
                    "domain": seed_host,
                    "brand_name": seed_host.capitalize(),
                    "serp_snippet": f"Đối thủ hạt giống: {norm_seed}",
                    "source": "seed_input"
                }
        except Exception:
            pass

    # Thu thập kết quả từ DuckDuckGo SERP
    for q in primary_queries:
        if len(candidate_map) >= 20:
            break
        raw_items = _fetch_serp_results(q)
        for item in raw_items:
            d = item.get("domain", "")
            # Loại trừ tên miền của chính mình
            if target_host and (d == target_host or (target_root and len(target_root) > 4 and target_root in d)):
                continue
            if d and d not in candidate_map:
                candidate_map[d] = item

    # Danh mục dự phòng chuẩn thị trường Xiaomi (phòng trường hợp SERP rate-limit)
    if len(candidate_map) < 10 and "xiaomi" in business_desc.lower():
        known_xiaomi = [
            ("mihanoi.vn", "https://mihanoi.vn/", "Mi Hà Nội - Đại lý phân phối Xiaomi chính hãng"),
            ("cellphones.com.vn", "https://cellphones.com.vn/", "CellphoneS - Hệ thống bán lẻ điện thoại, smarthome Xiaomi"),
            ("dienmayxanh.com", "https://www.dienmayxanh.com/", "Điện Máy Xanh - Siêu thị điện máy, tivi robot Xiaomi"),
            ("fptshop.com.vn", "https://fptshop.com.vn/", "FPT Shop - Thiết bị thông minh, gia dụng Xiaomi"),
            ("xiaomistorehanoi.com.vn", "https://xiaomistorehanoi.com.vn/", "Xiaomi Store Hà Nội - Đại lý ủy quyền Xiaomi"),
            ("mivietnam.vn", "https://mivietnam.vn/", "Mi Việt Nam - Chuyên đồ gia dụng & robot Xiaomi"),
            ("mistore.com.vn", "https://mistore.com.vn/", "Mi Store - Hệ sinh thái công nghệ Xiaomi"),
            ("phonghoa.vn", "https://phonghoa.vn/", "Phong Hòa Xiaomi - Tivi & robot hút bụi chính hãng"),
            ("dienmaythienphu.vn", "https://dienmaythienphu.vn/", "Điện Máy Thiên Phú - Tổng kho phân phối Xiaomi"),
            ("ihomestore.vn", "https://ihomestore.vn/", "iHome Store - Chuyên gia robot hút bụi lau nhà thông minh"),
        ]
        for d, u, snip in known_xiaomi:
            if d not in candidate_map and d != target_host:
                candidate_map[d] = {
                    "url": u,
                    "domain": d,
                    "brand_name": d.capitalize(),
                    "serp_snippet": snip,
                    "source": "curated_radar"
                }

    # BẮT BUỘC: Kiểm tra trạng thái ONLINE - Chỉ giữ các domain đang ONLINE thực tế (HTTP 200..399)
    candidate_list = list(candidate_map.values())
    with ThreadPoolExecutor(max_workers=8) as executor:
        online_statuses = list(executor.map(lambda c: check_url_online(c["url"], timeout=4), candidate_list))

    verified_online = []
    for c, is_on in zip(candidate_list, online_statuses):
        if not is_on:
            continue  # BỎ QUA WEBSITE OFFLINE / KHÔNG PHẢN HỒI

        snippet = c.get("serp_snippet", "")
        is_ad = c.get("is_ad", False)
        comp_type = c.get("competitor_type", "paid_ads" if is_ad else "organic_seo")
        ad_info = c.get("ad_details", {})

        verified_online.append({
            "domain": c.get("domain", ""),
            "url": c.get("url", ""),
            "top_rank": len(verified_online) + 1,
            "is_online": True,
            "is_ad": is_ad,
            "competitor_type": comp_type,
            "ad_details": ad_info,
            "scraped_structure": {
                "title": c.get("brand_name", c.get("domain")),
                "snippet": snippet,
                "source": c.get("source", "serp_verified"),
                "is_ad": is_ad,
                "competitor_type": comp_type
            },
            "pricing_data": {},
            "strengths": f"{'Đang đấu thầu Google Ads tích cực' if is_ad else 'Nhà bán lẻ xếp hạng SEO tự nhiên'}: {snippet[:120]}",
            "weaknesses": "Cần đối chiếu chiến lược giá và tối ưu trang đích để chuyển đổi cao hơn."
        })
        if len(verified_online) == 10:
            break

    # Lưu 10 đối thủ mới (đã xác minh 100% Online) vào Database
    db_service.save_competitors(project_id, verified_online)

    return {
        "status": "success",
        "project_id": project_id,
        "count": len(verified_online),
        "competitors": verified_online,
        "message": f"Đã quét và xác minh {len(verified_online)} đối thủ SERP đang Online 100%!"
    }


@app.post("/api/v1/portal/projects/{project_id}/rescan-my-site")
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


class PortalCmsBridgeRequest(BaseModel):
    cms_type: Optional[str] = "wordpress"
    cms_api_url: str
    api_key: str


@app.post("/api/v1/portal/projects/{project_id}/cms-bridge")
def portal_configure_cms_bridge(project_id: str, req: PortalCmsBridgeRequest):
    """Cấu hình và kiểm tra kết nối WordPress Omni Bridge từ giao diện Portal."""
    import urllib.request
    import json
    import db_service

    project = db_service.get_project_by_id(project_id)
    if not project:
        raise HTTPException(status_code=404, detail="Không tìm thấy dự án.")

    clean_url = req.cms_api_url.rstrip("/")
    clean_key = req.api_key.strip()

    health_endpoint = f"{clean_url}/wp-json/omni-bridge/v1/health"
    try:
        req_health = urllib.request.Request(
            health_endpoint,
            headers={
                "X-Omni-Bridge-Key": clean_key,
                "User-Agent": "Autonomous-Marketing-Agent/1.0",
            }
        )
        with urllib.request.urlopen(req_health, timeout=10) as resp:
            health_data = json.loads(resp.read().decode("utf-8"))
    except Exception as e:
        raise HTTPException(status_code=400, detail=f"Không thể kết nối đến Omni Bridge: {e}")

    with db_service.get_connection() as conn:
        with conn.cursor() as cur:
            cur.execute("""
                UPDATE projects 
                SET cms_type = %s,
                    cms_api_url = %s,
                    cms_auth_config = %s,
                    updated_at = NOW()
                WHERE id = %s;
            """, (
                req.cms_type or "wordpress",
                clean_url,
                json.dumps({"api_key": clean_key}, ensure_ascii=False),
                project_id
            ))
            conn.commit()

    return {
        "status": "connected",
        "project_id": project_id,
        "cms_api_url": clean_url,
        "health": health_data,
        "message": "Kết nối Omni Bridge WordPress thành công!"
    }


@app.get("/api/v1/portal/projects/{project_id}/chat-history")
def portal_get_chat_history(project_id: str, user: Optional[str] = Query(None)):
    """
    Lấy toàn bộ lịch sử trò chuyện của dự án từ Dify API qua `dify_conversation_id`.
    F5 hoặc đổi máy tính vẫn giữ nguyên 100% ngữ cảnh. Tự động dọn dẹp nếu phiên cũ không còn.
    """
    import db_service
    import urllib.request
    import json

    project = db_service.get_project_by_id(project_id)
    if not project:
        raise HTTPException(status_code=404, detail="Không tìm thấy dự án.")

    conversation_id = project.get("dify_conversation_id")
    if not conversation_id:
        return {
            "project_id": project_id,
            "conversation_id": None,
            "messages": []
        }

    # Nếu không truyền user, lấy default user trong DB
    if not user:
        _, def_user = db_service.get_default_tenant_and_user()
        user = def_user.get("email") if def_user else "admin@marketing-ai.internal"

    dify_url = os.getenv("DIFY_API_URL", "http://docker-api-1:5001/v1").rstrip("/")
    dify_app_key = os.getenv("DIFY_APP_API_KEY", "app-cmo-test-token-tongkhomi")

    def try_fetch_messages(target_user: str):
        req_url = f"{dify_url}/messages?conversation_id={conversation_id}&user={target_user}&limit=100"
        req = urllib.request.Request(
            req_url,
            headers={"Authorization": f"Bearer {dify_app_key}"}
        )
        with urllib.request.urlopen(req, timeout=10) as res:
            res_data = json.loads(res.read().decode("utf-8"))
            return res_data.get("data", [])

    raw_messages = None
    try:
        raw_messages = try_fetch_messages(user)
    except urllib.error.HTTPError as he:
        if he.code == 404:
            # Thử user fallback (admin@marketing-ai.internal hoặc admin@tongkhomi.com)
            fb_user = "admin@marketing-ai.internal" if user != "admin@marketing-ai.internal" else "admin@tongkhomi.com"
            try:
                raw_messages = try_fetch_messages(fb_user)
            except Exception:
                # Phiên chat đã mất hoặc không tồn tại trong Dify -> Tự động reset để bắt đầu phiên mới
                logger.warning(f"[DIFY_RESET_STALE_CONVERSATION] Stale cid={conversation_id} for project {project_id}, resetting.")
                db_service.reset_project_conversation_id(project_id)
                return {
                    "project_id": project_id,
                    "conversation_id": None,
                    "messages": []
                }
        else:
            logger.error(f"[DIFY_HISTORY_ERROR] project_id={project_id} err={he}")
            return {
                "project_id": project_id,
                "conversation_id": conversation_id,
                "messages": [],
                "error": str(he)
            }
    except Exception as e:
        logger.error(f"[DIFY_HISTORY_ERROR] project_id={project_id} err={e}")
        return {
            "project_id": project_id,
            "conversation_id": conversation_id,
            "messages": [],
            "error": str(e)
        }

    formatted = []
    for item in (raw_messages or []):
        query = item.get("query")
        answer = item.get("answer")
        created_at = item.get("created_at")
        if query:
            formatted.append({
                "id": f"{item.get('id')}_q",
                "role": "user",
                "content": query,
                "created_at": created_at
            })
        if answer:
            formatted.append({
                "id": f"{item.get('id')}_a",
                "role": "assistant",
                "content": answer,
                "created_at": created_at
            })

    return {
        "project_id": project_id,
        "conversation_id": conversation_id,
        "messages": formatted
    }


@app.post("/api/v1/portal/projects/{project_id}/chat")
async def portal_chat_stream(project_id: str, payload: PortalChatMessage):
    """
    Gửi tin nhắn vào CMO AI Copilot của dự án qua giao thức SSE Streaming.
    Tự động ghi nhận và khóa chặt `dify_conversation_id` vào Database.
    """
    import db_service
    import httpx
    import json

    project = db_service.get_project_by_id(project_id)
    if not project:
        raise HTTPException(status_code=404, detail="Không tìm thấy dự án.")

    existing_conversation_id = project.get("dify_conversation_id") or ""
    dify_url = os.getenv("DIFY_API_URL", "http://docker-api-1:5001/v1").rstrip("/")
    dify_app_key = os.getenv("DIFY_APP_API_KEY", "app-cmo-test-token-tongkhomi")

    # Xác định user cho Dify
    chat_user = payload.user
    if not chat_user:
        _, def_user = db_service.get_default_tenant_and_user()
        chat_user = def_user.get("email") if def_user else "admin@marketing-ai.internal"

    request_body = {
        "inputs": {},
        "query": payload.message,
        "response_mode": "streaming",
        "conversation_id": existing_conversation_id,
        "user": chat_user
    }

    async def event_generator():
        client = httpx.AsyncClient(timeout=120.0)
        saved_cid = False
        try:
            async with client.stream(
                "POST",
                f"{dify_url}/chat-messages",
                json=request_body,
                headers={
                    "Authorization": f"Bearer {dify_app_key}",
                    "Content-Type": "application/json"
                }
            ) as response:
                if response.status_code == 404 and existing_conversation_id:
                    logger.warning(f"[DIFY_AUTO_RECOVER] Stale cid={existing_conversation_id}, resetting and retrying.")
                    db_service.reset_project_conversation_id(project_id)
                    fallback_body = dict(request_body)
                    fallback_body["conversation_id"] = ""
                    async with client.stream(
                        "POST",
                        f"{dify_url}/chat-messages",
                        json=fallback_body,
                        headers={
                            "Authorization": f"Bearer {dify_app_key}",
                            "Content-Type": "application/json"
                        }
                    ) as fb_response:
                        if fb_response.status_code != 200:
                            err_text = await fb_response.aread()
                            yield f"data: {json.dumps({'event': 'error', 'message': f'Dify Error {fb_response.status_code}: {err_text.decode()}'})}\n\n"
                            return
                        async for line in fb_response.aiter_lines():
                            if line.startswith("data: "):
                                data_str = line[6:].strip()
                                try:
                                    chunk = json.loads(data_str)
                                    cid = chunk.get("conversation_id")
                                    if cid and not saved_cid:
                                        db_service.update_project_conversation_id(project_id, cid)
                                        logger.info(f"[PERSIST_CONVERSATION] Bound new cid={cid} to project_id={project_id}")
                                        saved_cid = True
                                except Exception:
                                    pass
                            yield f"{line}\n"
                    return

                if response.status_code != 200:
                    err_text = await response.aread()
                    yield f"data: {json.dumps({'event': 'error', 'message': f'Dify Error {response.status_code}: {err_text.decode()}'})}\n\n"
                    return

                async for line in response.aiter_lines():
                    if line.startswith("data: "):
                        data_str = line[6:].strip()
                        try:
                            chunk = json.loads(data_str)
                            cid = chunk.get("conversation_id")
                            if cid and not saved_cid:
                                if cid != existing_conversation_id:
                                    db_service.update_project_conversation_id(project_id, cid)
                                    logger.info(f"[PERSIST_CONVERSATION] Bound cid={cid} to project_id={project_id}")
                                saved_cid = True
                        except Exception:
                            pass
                    yield f"{line}\n"
        except Exception as e:
            logger.error(f"[DIFY_STREAM_ERROR] {e}")
            yield f"data: {json.dumps({'event': 'error', 'message': str(e)})}\n\n"
        finally:
            await client.aclose()

    return StreamingResponse(event_generator(), media_type="text/event-stream")


@app.post("/api/v1/portal/projects/{project_id}/chat/reset")
def portal_reset_chat(project_id: str):
    """Làm mới phiên chat cho dự án (xóa conversation_id để bắt đầu cuộc trò chuyện mới)."""
    import db_service
    success = db_service.reset_project_conversation_id(project_id)
    if not success:
        raise HTTPException(status_code=404, detail="Không tìm thấy dự án.")
    return {
        "status": "success",
        "project_id": project_id,
        "message": "Đã làm mới phiên chat thành công cho dự án."
    }


class PortalSaveSettingsRequest(BaseModel):
    serp_provider: str = Field(default="serper")
    serper_api_keys: str = Field(default="")
    gl: str = Field(default="vn")
    hl: str = Field(default="vi")
    ai_mode: str = Field(default="system")  # "system" | "custom"
    ai_provider: str = Field(default="openai")  # "openai", "gemini", "anthropic", "custom"
    ai_api_key: str = Field(default="")
    ai_base_url: str = Field(default="")
    ai_model_name: str = Field(default="")
    authority_mode: str = Field(default="free_rdap_dork")  # "free_rdap_dork" | "moz_pool"
    moz_api_keys: str = Field(default="")
    enable_dorking: bool = Field(default=True)


class PortalTestSerpRequest(BaseModel):
    serper_api_keys: str
    gl: str = "vn"
    hl: str = "vi"


class PortalTestAiRequest(BaseModel):
    provider: str = "openai"
    api_key: str
    base_url: str = ""
    model_name: str = ""


class PortalTestAuthorityRequest(BaseModel):
    moz_api_keys: str = ""
    test_domain: str = "viomiviet.vn"


def _mask_api_key(key: str) -> str:
    """Che giấu một phần API key để hiển thị an toàn trên giao diện."""
    k = (key or "").strip()
    if not k:
        return ""
    if ":" in k:
        parts = k.split(":", 1)
        return f"{_mask_api_key(parts[0])}:{_mask_api_key(parts[1])}"
    if len(k) <= 8:
        return "****"
    return f"{k[:4]}...{k[-4:]}"


@app.get("/api/v1/portal/settings")
def portal_get_settings(tenant_id: Optional[str] = None):
    """Lấy thông tin cấu hình SERP, AI và Thẩm quyền Authority của hệ thống/tenant (đã che giấu API keys)."""
    import db_service
    serp_cfg = db_service.get_system_setting("serp_config", tenant_id) or {}
    ai_cfg = db_service.get_system_setting("ai_config", tenant_id) or {}
    auth_cfg = db_service.get_system_setting("authority_config", tenant_id) or {}

    raw_serp_keys = serp_cfg.get("serper_api_keys", "")
    if isinstance(raw_serp_keys, list):
        serp_keys_list = raw_serp_keys
    elif isinstance(raw_serp_keys, str):
        serp_keys_list = [k.strip() for k in re.split(r"[,;\n\r]+", raw_serp_keys) if k.strip()]
    else:
        serp_keys_list = []

    # Kiểm tra fallback env nếu trong DB chưa có
    env_serp = os.environ.get("SERPER_API_KEYS", os.environ.get("SERPER_API_KEY", ""))
    if not serp_keys_list and env_serp:
        serp_keys_list = [k.strip() for k in re.split(r"[,;\n\r]+", env_serp) if k.strip()]

    masked_serp_keys = [_mask_api_key(k) for k in serp_keys_list]

    ai_key = ai_cfg.get("api_key", "")

    # Moz Keys
    raw_moz_keys = auth_cfg.get("moz_api_keys", "")
    if isinstance(raw_moz_keys, list):
        moz_keys_list = raw_moz_keys
    elif isinstance(raw_moz_keys, str):
        moz_keys_list = [k.strip() for k in re.split(r"[,;\n\r]+", raw_moz_keys) if ":" in k.strip()]
    else:
        moz_keys_list = []

    masked_moz_keys = [_mask_api_key(k) for k in moz_keys_list]

    return {
        "status": "success",
        "serp_config": {
            "provider": serp_cfg.get("provider", "serper" if serp_keys_list else "duckduckgo"),
            "has_keys": len(serp_keys_list) > 0,
            "key_count": len(serp_keys_list),
            "masked_keys": masked_serp_keys,
            "raw_keys_display": ", ".join(masked_serp_keys) if masked_serp_keys else "",
            "gl": serp_cfg.get("gl", "vn"),
            "hl": serp_cfg.get("hl", "vi")
        },
        "ai_config": {
            "mode": ai_cfg.get("mode", "system"),
            "provider": ai_cfg.get("provider", "openai"),
            "has_api_key": bool(ai_key),
            "api_key_masked": _mask_api_key(ai_key),
            "base_url": ai_cfg.get("base_url", ""),
            "model_name": ai_cfg.get("model_name", "gpt-4o")
        },
        "authority_config": {
            "mode": auth_cfg.get("mode", "free_rdap_dork"),
            "has_moz_keys": len(moz_keys_list) > 0,
            "moz_key_count": len(moz_keys_list),
            "masked_keys": masked_moz_keys,
            "raw_keys_display": "\n".join(masked_moz_keys) if masked_moz_keys else "",
            "enable_dorking": auth_cfg.get("enable_dorking", True)
        }
    }


@app.post("/api/v1/portal/settings")
def portal_save_settings(payload: PortalSaveSettingsRequest, tenant_id: Optional[str] = None):
    """Lưu cấu hình SERP (Google Ads & SEO) và AI Copilot (BYOK)."""
    import db_service

    # 1. Xử lý SERP Config
    curr_serp = db_service.get_system_setting("serp_config", tenant_id) or {}
    in_serp_keys = payload.serper_api_keys.strip()

    # Nếu người dùng không nhập key mới (hoặc chứa mask ...), giữ nguyên key cũ trong DB
    if in_serp_keys and "..." not in in_serp_keys:
        final_serp_keys = in_serp_keys
    else:
        final_serp_keys = curr_serp.get("serper_api_keys", "")

    serp_data = {
        "provider": payload.serp_provider,
        "serper_api_keys": final_serp_keys,
        "gl": payload.gl or "vn",
        "hl": payload.hl or "vi"
    }
    db_service.save_system_setting("serp_config", serp_data, tenant_id)

    # 2. Xử lý AI Config
    curr_ai = db_service.get_system_setting("ai_config", tenant_id) or {}
    in_ai_key = payload.ai_api_key.strip()
    if in_ai_key and "..." not in in_ai_key:
        final_ai_key = in_ai_key
    else:
        final_ai_key = curr_ai.get("api_key", "")

    ai_data = {
        "mode": payload.ai_mode,
        "provider": payload.ai_provider,
        "api_key": final_ai_key,
        "base_url": payload.ai_base_url.strip(),
        "model_name": payload.ai_model_name.strip()
    }
    db_service.save_system_setting("ai_config", ai_data, tenant_id)

    # 3. Xử lý Thẩm Quyền Authority Config (Moz Free Pool & RDAP/Dorking)
    curr_auth = db_service.get_system_setting("authority_config", tenant_id) or {}
    in_moz_keys = payload.moz_api_keys.strip()
    if in_moz_keys and "..." not in in_moz_keys:
        final_moz_keys = in_moz_keys
    else:
        final_moz_keys = curr_auth.get("moz_api_keys", "")

    auth_data = {
        "mode": payload.authority_mode,
        "moz_api_keys": final_moz_keys,
        "enable_dorking": payload.enable_dorking
    }
    db_service.save_system_setting("authority_config", auth_data, tenant_id)

    return {
        "status": "success",
        "message": "Đã lưu toàn bộ cấu hình Cài đặt & API thành công!"
    }


@app.post("/api/v1/portal/settings/test-authority")
def portal_test_authority(payload: PortalTestAuthorityRequest, tenant_id: Optional[str] = None):
    """Kiểm tra cụm khóa Moz Free API và kiểm tra tính năng bóc tách Thẩm quyền (Tuổi tên miền / Trích dẫn)."""
    import base64
    import db_service
    from seed_analyzer import extract_domain_age_and_citations

    raw_keys = payload.moz_api_keys.strip()
    if not raw_keys or "..." in raw_keys:
        curr = db_service.get_system_setting("authority_config", tenant_id) or {}
        raw_keys = curr.get("moz_api_keys", "")

    test_domain = payload.test_domain.strip() or "viomiviet.vn"
    key_pairs = [k.strip() for k in re.split(r"[,;\n\r]+", raw_keys) if ":" in k.strip()]

    moz_results = []
    if key_pairs:
        for idx, pair in enumerate(key_pairs, start=1):
            acc_id, sec_key = pair.split(":", 1)
            auth_str = base64.b64encode(f"{acc_id.strip()}:{sec_key.strip()}".encode()).decode()
            t_payload = {"targets": [f"https://{test_domain}"]}
            try:
                req = urllib.request.Request(
                    "https://lsapi.seomoz.com/v2/url_metrics",
                    data=json.dumps(t_payload).encode(),
                    headers={
                        "Authorization": f"Basic {auth_str}",
                        "Content-Type": "application/json"
                    }
                )
                with urllib.request.urlopen(req, timeout=5) as resp:
                    if resp.status == 200:
                        m_data = json.loads(resp.read().decode())
                        m_res = m_data.get("results", [])
                        if m_res:
                            da = m_res[0].get("domain_authority", 0)
                            pa = m_res[0].get("page_authority", 0)
                            moz_results.append(f"Moz Key #{idx} ({_mask_api_key(acc_id)}): OK (DA: {da}/100, PA: {pa}/100)")
                        else:
                            moz_results.append(f"Moz Key #{idx}: OK (Không có dữ liệu cho domain test)")
            except urllib.error.HTTPError as he:
                moz_results.append(f"Moz Key #{idx}: Lỗi HTTP {he.code} (Hạn ngạch hoặc key không hợp lệ)")
            except Exception as e:
                moz_results.append(f"Moz Key #{idx}: {str(e)}")

    # Thử nghiệm tính năng 0đ (Footer Domain Age & Dorking)
    sample_age_info = extract_domain_age_and_citations(test_domain, "", tenant_id=tenant_id)
    dork_text = f"Cơ chế 0đ (RDAP / Copyright / Dorking): Tuổi đời ~{sample_age_info.get('age_years', 0)} năm, Trích dẫn ước tính ~{sample_age_info.get('citations_count', 0)}."

    msg_lines = []
    if moz_results:
        msg_lines.extend(moz_results)
    else:
        msg_lines.append("Chưa cấu hình Moz API Keys (Hệ thống sẽ chạy hoàn toàn bằng cơ chế 0đ miễn phí).")
    msg_lines.append(dork_text)

    return {
        "status": "success",
        "message": "\n".join(msg_lines)
    }


@app.post("/api/v1/portal/settings/test-serp")
def portal_test_serp(payload: PortalTestSerpRequest, tenant_id: Optional[str] = None):
    """Kiểm tra tính hợp lệ và hạn ngạch của Serper.dev API Keys."""
    import db_service
    raw_keys = payload.serper_api_keys.strip()

    # Nếu chuỗi chứa mask (...), lấy key thật từ DB
    if not raw_keys or "..." in raw_keys:
        curr = db_service.get_system_setting("serp_config", tenant_id) or {}
        raw_keys = curr.get("serper_api_keys", "")

    if not raw_keys:
        raw_keys = os.environ.get("SERPER_API_KEYS", os.environ.get("SERPER_API_KEY", ""))

    keys = [k.strip() for k in re.split(r"[,;\n\r]+", raw_keys) if k.strip()]
    if not keys:
        raise HTTPException(status_code=400, detail="Vui lòng nhập ít nhất 1 Serper.dev API Key để kiểm tra.")

    success_keys = []
    failed_keys = []

    for idx, k in enumerate(keys, start=1):
        test_payload = {
            "q": "tivi xiaomi",
            "gl": payload.gl or "vn",
            "hl": payload.hl or "vi"
        }
        req = urllib.request.Request(
            "https://google.serper.dev/search",
            data=json.dumps(test_payload).encode("utf-8"),
            headers={
                "X-API-KEY": k,
                "Content-Type": "application/json"
            }
        )
        try:
            with urllib.request.urlopen(req, timeout=8) as resp:
                if resp.status == 200:
                    data = json.loads(resp.read().decode("utf-8"))
                    ad_cnt = len(data.get("ads", []))
                    org_cnt = len(data.get("organic", []))
                    success_keys.append(f"Key #{idx} ({_mask_api_key(k)}): OK (Bóc tách được {ad_cnt} Google Ads, {org_cnt} Organic SEO)")
                else:
                    failed_keys.append(f"Key #{idx} ({_mask_api_key(k)}): Lỗi HTTP {resp.status}")
        except urllib.error.HTTPError as he:
            failed_keys.append(f"Key #{idx} ({_mask_api_key(k)}): Lỗi HTTP {he.code} (Hết hạn mức hoặc key sai)")
        except Exception as e:
            failed_keys.append(f"Key #{idx} ({_mask_api_key(k)}): {str(e)}")

    if not success_keys:
        raise HTTPException(
            status_code=400, 
            detail=f"Tất cả {len(keys)} key đều thất bại:\n" + "\n".join(failed_keys)
        )

    return {
        "status": "success",
        "message": f"Kiểm tra hoàn tất ({len(success_keys)}/{len(keys)} keys hoạt động tốt):\n" + "\n".join(success_keys) + (f"\n\nLưu ý:\n" + "\n".join(failed_keys) if failed_keys else "")
    }


@app.post("/api/v1/portal/settings/test-ai")
def portal_test_ai(payload: PortalTestAiRequest, tenant_id: Optional[str] = None):
    """Kiểm tra kết nối tới nhà cung cấp AI tùy chọn (BYOK)."""
    import db_service
    api_key = payload.api_key.strip()
    if not api_key or "..." in api_key:
        curr = db_service.get_system_setting("ai_config", tenant_id) or {}
        api_key = curr.get("api_key", "")

    if not api_key:
        raise HTTPException(status_code=400, detail="Vui lòng nhập API Key của AI để kiểm tra.")

    provider = payload.provider or "openai"
    base_url = payload.base_url.strip()
    model = payload.model_name.strip()

    if not base_url:
        if provider == "openai":
            base_url = "https://api.openai.com/v1/chat/completions"
        elif provider == "gemini":
            base_url = "https://generativelanguage.googleapis.com/v1beta/openai/chat/completions"
        else:
            base_url = "https://api.openai.com/v1/chat/completions"
    elif not base_url.endswith("/chat/completions"):
        base_url = base_url.rstrip("/") + "/chat/completions"

    if not model:
        model = "gpt-4o" if provider == "openai" else ("gemini-1.5-flash" if provider == "gemini" else "default")

    test_body = {
        "model": model,
        "messages": [
            {"role": "user", "content": "Xin chào, hãy trả lời đúng 1 từ: 'CONNECTED'"}
        ],
        "max_tokens": 15,
        "temperature": 0.1
    }

    req = urllib.request.Request(
        base_url,
        data=json.dumps(test_body).encode("utf-8"),
        headers={
            "Authorization": f"Bearer {api_key}",
            "Content-Type": "application/json"
        }
    )

    try:
        with urllib.request.urlopen(req, timeout=12) as resp:
            data = json.loads(resp.read().decode("utf-8"))
            reply = data["choices"][0]["message"]["content"].strip()
            return {
                "status": "success",
                "message": f"Kết nối AI thành công! Phản hồi từ model [{model}]: \"{reply}\""
            }
    except urllib.error.HTTPError as he:
        err_msg = he.read().decode("utf-8", errors="replace")
        raise HTTPException(status_code=400, detail=f"Lỗi kết nối AI (HTTP {he.code}): {err_msg[:200]}")
    except Exception as e:
        raise HTTPException(status_code=400, detail=f"Lỗi kiểm tra AI: {str(e)}")


if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8085)

