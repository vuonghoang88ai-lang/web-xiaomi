#!/usr/bin/env python3
"""
Tier 2, 3, 5 Gateway Server: Autonomous Marketing Engine API
Chạy FastAPI daemon tại port 8085.
Đóng vai trò là cầu nối trực tiếp giữa Dify (Tier 1) và Multi-Agent Execution Engine (Tier 2-5).

NOTE: Đã được refactor từ bản gốc monolithic (2536 dòng) sang cấu trúc module:
  - models.py: Tất cả Pydantic models
  - utils.py: JOBS_DB, helper functions, background workers
  - routes/: Các module APIRouter theo chức năng (onboarding, gsc, saas, cms, campaign, portal)
"""

import os
import sys
import hmac
import logging
from typing import Optional
from uuid import UUID
from fastapi import FastAPI, HTTPException, Query, Request
from fastapi.responses import FileResponse, HTMLResponse, JSONResponse, Response
from fastapi.middleware.cors import CORSMiddleware
from fastapi.staticfiles import StaticFiles

CURRENT_DIR = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, CURRENT_DIR)

logger = logging.getLogger("marketing_engine")

# ─── App Factory ──────────────────────────────────────────────────────────
app = FastAPI(
    title="Autonomous Marketing Multi-Agent Engine API",
    description="Hệ thống tự động hóa Marketing, cào đối thủ, SEO & GEO cho mọi ngành nghề",
    version="2.1.0",
)

app.add_middleware(
    CORSMiddleware,
    allow_origins=[origin for origin in os.getenv("ENGINE_CORS_ORIGINS", "").split(",") if origin] or ["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)


# ─── Middleware ───────────────────────────────────────────────────────────
@app.middleware("http")
async def require_engine_api_key(request: Request, call_next):
    """Protect every API route; health/docs/portal remain available for diagnostics & UI."""
    public_paths = {
        "/health", "/docs", "/openapi.json", "/redoc",
        "/api/v1/download/omni-bridge-connector",
        "/api/v1/gsc/oauth/callback",
        "/api/v1/bridge/tasks/poll",
        "/api/v1/bridge/tasks/ack",
    }
    is_public = (
        not request.url.path.startswith("/api/")
        or request.url.path in public_paths
        or request.url.path.startswith("/api/v1/portal")
        or request.url.path.endswith("/gsc/connect")
    )
    if is_public:
        return await call_next(request)

    client_ip = request.client.host if request.client else ""
    if client_ip.startswith(("172.", "10.", "127.", "192.168.", "100.")):
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
    """Đảm bảo browser luôn nhận được phiên bản mới nhất của Portal UI và cho phép unload cho inspector."""
    response = await call_next(request)
    # Suppress Chrome Permissions policy violation for unload event in devtools/inspectors
    response.headers["Permissions-Policy"] = "unload=*"
    if request.url.path.startswith("/portal") or request.url.path == "/":
        response.headers["Cache-Control"] = "no-cache, no-store, must-revalidate, max-age=0"
        response.headers["Pragma"] = "no-cache"
        response.headers["Expires"] = "0"
    return response


# ─── Static Files ───────────────────────────────────────────────────────────
from fastapi.staticfiles import StaticFiles

STORAGE_DIR = os.path.join(CURRENT_DIR, "storage")
os.makedirs(STORAGE_DIR, exist_ok=True)
app.mount("/storage", StaticFiles(directory=STORAGE_DIR), name="storage")

PORTAL_DIR = os.path.join(CURRENT_DIR, "portal")
os.makedirs(PORTAL_DIR, exist_ok=True)
app.mount("/portal", StaticFiles(directory=PORTAL_DIR), name="portal")


# ─── Core routes (not in sub-modules) ──────────────────────────────────────
@app.get("/favicon.ico", include_in_schema=False)
def serve_favicon():
    return Response(
        content='<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#6366f1"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>',
        media_type="image/svg+xml"
    )


@app.get("/", operation_id="serve_portal_root")
def serve_portal_root():
    """Phục vụ giao diện Client Portal độc lập tại root URL."""
    index_file = os.path.join(PORTAL_DIR, "index.html")
    if os.path.exists(index_file):
        with open(index_file, "r", encoding="utf-8") as f:
            content = f.read()
        return HTMLResponse(
            content=content,
            headers={
                "Cache-Control": "no-cache, no-store, must-revalidate, max-age=0",
                "Pragma": "no-cache",
                "Expires": "0"
            }
        )
    return JSONResponse({
        "service": "Universal Marketing AI Portal",
        "version": "3.0.0",
        "status": "ready"
    })


@app.head("/", include_in_schema=False)
def serve_portal_root_head():
    """HEAD route cho root endpoint (không sinh duplicate operation_id trong OpenAPI schema)."""
    return Response(status_code=200)


# ─── Include Route Modules ──────────────────────────────────────────────────
from routes.onboarding import router as onboarding_router
from routes.gsc import router as gsc_router
from routes.saas import router as saas_router
from routes.cms import router as cms_router
from routes.campaign import router as campaign_router
from routes.live_intel import router as live_intel_router
from routes.portal import all_routers as portal_routers

app.include_router(onboarding_router)
app.include_router(gsc_router)
app.include_router(saas_router)
app.include_router(cms_router)
app.include_router(campaign_router)
app.include_router(live_intel_router)
for portal_router in portal_routers:
    app.include_router(portal_router)


if __name__ == "__main__":
    import uvicorn
    uvicorn.run("server:app", host="0.0.0.0", port=8085, reload=True)

