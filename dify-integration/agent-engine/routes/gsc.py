#!/usr/bin/env python3
"""Route module - auto-generated from server.py refactor."""
import os
import sys
import json
import logging
import uuid
from uuid import UUID
from datetime import datetime, timezone, timedelta
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

@router.get("/api/v1/projects/{project_id}/gsc/connect")
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



@router.get("/api/v1/gsc/oauth/callback")
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



@router.get("/api/v1/projects/{project_id}/gsc/connections")
def gsc_connections(project_id: str, tenant_id: UUID = Query(...)):
    if not db_service.get_project_full_intelligence(project_id, str(tenant_id)):
        raise HTTPException(status_code=404, detail="Không tìm thấy project thuộc tenant này")
    return {"project_id": project_id, "connections": db_service.list_gsc_connections(project_id, str(tenant_id))}



@router.post("/api/v1/projects/{project_id}/gsc/connections/{connection_id}/property")
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



@router.get("/api/v1/projects/{project_id}/gsc/sites")
def gsc_sites(project_id: str, tenant_id: UUID = Query(...), connection_id: Optional[str] = None):
    connection = db_service.get_gsc_connection(project_id, str(tenant_id), connection_id=connection_id)
    if not connection:
        raise HTTPException(status_code=404, detail="Chưa có kết nối GSC cho project này")
    try:
        sites = gsc_service.list_sites(_gsc_access_token(connection))
    except (gsc_service.GSCAPIError, gsc_service.GSCConfigurationError) as exc:
        raise HTTPException(status_code=502, detail=str(exc))
    return {"project_id": project_id, "connection_id": str(connection["id"]), "sites": sites}



@router.post("/api/v1/projects/{project_id}/gsc/performance")
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



