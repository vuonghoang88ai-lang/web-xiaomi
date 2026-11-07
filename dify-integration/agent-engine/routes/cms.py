#!/usr/bin/env python3
"""Route module for CMS bridge connectivity, research & On-page autofix."""
import os
import sys
import json
import logging
import urllib.request
from typing import Optional
from fastapi import APIRouter, HTTPException, Response, Request
from fastapi.responses import FileResponse

CURRENT_DIR = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, os.path.dirname(CURRENT_DIR))

from models import ResearchRequest, CmsBridgeConfigRequest
import db_service
from scraper_tool import scrape_competitor_recon
from serp_discovery_tool import discover_top_10_competitors
from routes.portal.tool_budgeter import condense_competitor_data
from cms_autofix_service import execute_project_onpage_autofix

logger = logging.getLogger('marketing_engine')
router = APIRouter()

@router.get("/health")
def health_check():
    return {
        "status": "healthy",
        "service": "Autonomous Marketing Multi-Agent Engine",
        "port": 8085,
        "connected_tiers": ["1.Dify", "2.MultiAgent", "3.PythonScraper", "4.9Router", "5.WordPress"]
    }

@router.head("/health", include_in_schema=False)
def health_check_head():
    return Response(status_code=200)

@router.post("/api/v1/research")
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
            discovered = discover_top_10_competitors(req.keyword)
            if discovered:
                data["status"] = "success"
                data["competitor_sources"] = [c.get("domain") for c in discovered if c.get("domain")]
                data["discovered_competitors"] = discovered
                data["found_count"] = len(discovered)
                data["summary"] = f"Đã phát hiện và xác thực {len(discovered)} đối thủ trực tuyến đang hoạt động thực tế trên thị trường cho từ khóa '{req.keyword}'."
        
        # SOP 35: Dynamic Tool Budgeting - Tinh lọc payload để LLM không bị Timeout 300s
        condensed = condense_competitor_data(data)
        return {
            "status": "success",
            "keyword": req.keyword,
            "data": condensed
        }
    except Exception:
        logger.exception("Competitor research failed")
        raise HTTPException(status_code=500, detail="Không thể hoàn tất trinh sát đối thủ.")

def _get_omni_bridge_zip_path() -> str:
    candidate_paths = [
        os.path.abspath(os.path.join(CURRENT_DIR, "../../plugins/omni-bridge-connector.zip")),
        os.path.abspath(os.path.join(CURRENT_DIR, "../plugins/omni-bridge-connector.zip")),
        "/app/omni-bridge-connector.zip",
        "/home/ubuntu/michinhhang/dify-integration/plugins/omni-bridge-connector.zip",
    ]
    for p in candidate_paths:
        if os.path.exists(p):
            return p
    raise HTTPException(status_code=404, detail="Không tìm thấy file plugin omni-bridge-connector.zip")

@router.get("/api/v1/download/omni-bridge-connector", operation_id="download_omni_bridge_connector")
def download_omni_bridge_connector():
    """Tải về file plugin omni-bridge-connector.zip để khách hàng cài vào WordPress."""
    zip_path = _get_omni_bridge_zip_path()
    return FileResponse(zip_path, media_type="application/zip", filename="omni-bridge-connector.zip")

@router.head("/api/v1/download/omni-bridge-connector", include_in_schema=False)
def head_omni_bridge_connector():
    """HEAD route cho health check file plugin (không xuất hiện trùng trong OpenAPI schema)."""
    zip_path = _get_omni_bridge_zip_path()
    file_size = os.path.getsize(zip_path) if os.path.exists(zip_path) else 0
    return Response(
        status_code=200,
        headers={
            "Content-Type": "application/zip",
            "Content-Length": str(file_size)
        }
    )

@router.post("/api/v1/projects/{project_id}/cms-bridge")
def configure_project_cms_bridge(project_id: str, req: CmsBridgeConfigRequest):
    """
    Cấu hình và kiểm tra kết nối Omni Bridge Connector Plugin cho Website WordPress của Dự án.
    Cho phép Agent xuất bản trực tiếp bài viết & sản phẩm từ xa lên Hosting/VPS của khách hàng.
    """
    target_url = req.cms_api_url.rstrip('/')
    if os.path.exists("/.dockerenv") and ("127.0.0.1:8080" in target_url or "localhost:8080" in target_url):
        target_url = target_url.replace("127.0.0.1:8080", "wordpress:80").replace("localhost:8080", "wordpress:80")
    health_endpoint = f"{target_url}/wp-json/omni-bridge/v1/health"
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

    try:
        gsc_request = urllib.request.Request(
            f"{target_url}/wp-json/omni-bridge/v1/gsc/config",
            headers={
                "X-Omni-Bridge-Key": req.api_key.strip(),
                "User-Agent": "Autonomous-Marketing-Agent/1.0",
            },
        )
        with urllib.request.urlopen(gsc_request, timeout=10) as response:
            gsc_connector = json.loads(response.read().decode("utf-8"))
    except Exception:
        gsc_connector = {
            "source": "none",
            "connection_detected": False,
            "reuse_available": False,
            "property_uri": req.cms_api_url.rstrip("/"),
        }

    if not db_service.get_project_full_intelligence(project_id, str(req.tenant_id)):
        raise HTTPException(status_code=404, detail="Project không thuộc tenant này")

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

@router.post("/api/v1/portal/projects/{project_id}/auto-fix-onpage")
def auto_fix_project_onpage_via_bridge(project_id: str, custom_seo: Optional[dict] = None):
    """
    Tự động khắc phục 100% On-Page và khởi tạo bộ trang pháp lý E-E-A-T trên WordPress qua Omni Bridge.
    """
    return execute_project_onpage_autofix(project_id, custom_seo=custom_seo)

@router.post("/api/v1/cms/telemetry-sync")
def receive_cms_telemetry_sync(req: dict, response: Response, request: Request = None):
    """
    Nhận Site Topology & ERP Ecosystem Manifest từ Omni Bridge Connector trên WordPress (SOP 30 & SOP 48).
    Tự động lưu trữ sơ đồ CPTs, Taxonomies, ERP (KiotViet/Sapo) vào database.
    """
    # Lấy API key từ header hoặc payload
    api_key = ""
    if request:
        api_key = request.headers.get("x-omni-bridge-key", "").strip()
    if not api_key:
        api_key = req.get("api_key", "").strip()

    site_url = req.get("site_url", "").rstrip("/")
    topology = req.get("topology") or {}

    with db_service.get_connection() as conn:
        with conn.cursor() as cur:
            # Tìm project theo api_key hoặc site_url
            cur.execute("""
                SELECT id, name, brand_profile 
                FROM projects 
                WHERE (cms_auth_config->>'api_key' = %s AND %s != '')
                   OR (target_website_url ILIKE %s AND %s != '')
                LIMIT 1;
            """, (api_key, api_key, f"%{site_url}%", site_url))
            row = cur.fetchone()
            if not row:
                raise HTTPException(status_code=404, detail="Không tìm thấy dự án khớp với API key hoặc website này.")

            project_id = str(row[0])
            brand_profile = row[2] or {}
            brand_profile = json.loads(brand_profile) if isinstance(brand_profile, str) else brand_profile

            # Đồng bộ archetype nếu topology phát hiện được
            detected_arch = topology.get("detected_archetype")
            if detected_arch and not brand_profile.get("archetype"):
                brand_profile["archetype"] = detected_arch

            cur.execute("""
                UPDATE projects
                SET site_topology = %s::jsonb,
                    brand_profile = %s::jsonb,
                    updated_at = NOW()
                WHERE id = %s;
            """, (json.dumps(topology, ensure_ascii=False), json.dumps(brand_profile, ensure_ascii=False), project_id))
            conn.commit()

    logger.info(f"[TELEMETRY_SYNC] Saved site topology for project={project_id} (archetype={topology.get('detected_archetype')})")
    return {
        "status": "success",
        "project_id": project_id,
        "message": "Đã đồng bộ Sơ đồ Cấu trúc Website (Site Topology) & ERP Ecosystem thành công!"
    }

@router.post("/api/v1/portal/projects/{project_id}/topology/sync")
def pull_project_topology_from_site(project_id: str):
    """
    Chủ động kéo Sơ đồ Cấu trúc Website & ERP Ecosystem từ WordPress qua GET /omni-bridge/v1/topology.
    """
    project = db_service.get_project_by_id(project_id)
    if not project:
        raise HTTPException(status_code=404, detail="Không tìm thấy dự án.")

    cms_url = (project.get("cms_api_url") or "").rstrip("/")
    if os.path.exists("/.dockerenv") and ("127.0.0.1:8080" in cms_url or "localhost:8080" in cms_url):
        cms_url = cms_url.replace("127.0.0.1:8080", "wordpress:80").replace("localhost:8080", "wordpress:80")

    auth = json.loads(project.get("cms_auth_config") or "{}") if isinstance(project.get("cms_auth_config"), str) else (project.get("cms_auth_config") or {})
    api_key = auth.get("api_key", "").strip()
    if not cms_url or not api_key:
        raise HTTPException(status_code=400, detail="Website chưa cấu hình Omni Bridge Connector.")

    headers = {"X-Omni-Bridge-Key": api_key, "User-Agent": "Autonomous-Marketing-Agent/1.0"}
    try:
        req = urllib.request.Request(f"{cms_url}/wp-json/omni-bridge/v1/topology", headers=headers)
        with urllib.request.urlopen(req, timeout=10) as resp:
            data = json.loads(resp.read().decode("utf-8"))
            topology = data.get("topology") or {}
    except Exception as e:
        raise HTTPException(status_code=400, detail=f"Không thể lấy cấu trúc site từ WordPress: {e}")

    with db_service.get_connection() as conn:
        with conn.cursor() as cur:
            cur.execute("""
                UPDATE projects 
                SET site_topology = %s::jsonb, updated_at = NOW() 
                WHERE id = %s;
            """, (json.dumps(topology, ensure_ascii=False), project_id))
            conn.commit()

    return {
        "status": "success",
        "project_id": project_id,
        "topology": topology
    }
