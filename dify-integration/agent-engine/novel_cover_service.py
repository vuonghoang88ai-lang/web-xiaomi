#!/usr/bin/env python3
"""
Universal Visual Artisan Service (SOP 23 compliant <= 300 lines).
Handles multi-archetype visual asset generation, WordPress Omni Bridge integration, and batch repair.
"""
import os, json, logging, urllib.request, urllib.parse
from typing import Dict, Any, List, Optional, Tuple
from fastapi import HTTPException
import db_service
from universal_visual_artisan import generate_universal_visual_asset

logger = logging.getLogger("marketing_engine")
STORAGE_ROOT = os.path.join(os.path.dirname(os.path.abspath(__file__)), "storage")

def _get_project_covers_dir(project_id: str) -> Tuple[str, str]:
    clean_id = str(project_id).replace("/", "_").replace("..", "")
    folder = os.path.join(STORAGE_ROOT, "projects", clean_id, "covers")
    os.makedirs(folder, exist_ok=True)
    web_base = f"/storage/projects/{clean_id}/covers"
    return folder, web_base

def detect_project_archetype(project: dict) -> str:
    bp = project.get("brand_profile") or {}
    bp = json.loads(bp) if isinstance(bp, str) else bp
    if bp.get("archetype"):
        return bp["archetype"]
    desc = f"{project.get('business_description', '')} {project.get('target_website_url', '')} {project.get('name', '')}".lower()
    if any(k in desc for k in ["truyện", "novel", "book", "comic", "manga", "đọc"]):
        return "content_media"
    if any(k in desc for k in ["phòng khám", "nha khoa", "y tế", "bác sĩ", "clinic", "luật", "b2b"]):
        return "service_health_b2b"
    return "commerce_retail"

def _discover_public_content_endpoints(cms_url: str, headers: dict, archetype: str) -> List[str]:
    """SOP 30: Khám phá động các Content Endpoints từ /wp-json/wp/v2/types. Không fix cứng 'posts'."""
    try:
        req = urllib.request.Request(f"{cms_url}/wp-json/wp/v2/types", headers=headers)
        with urllib.request.urlopen(req, timeout=8) as resp:
            types_data = json.loads(resp.read().decode("utf-8"))
        ignore = {"attachment", "wp_block", "wp_template", "wp_template_part", "wp_navigation", "nav_menu_item", "sab_chapter"}
        candidates = [info.get("rest_base") or slug for slug, info in types_data.items() if slug not in ignore]
        priority = {
            "content_media": ["sab_story", "story", "novel", "book", "truyen", "manga", "posts"],
            "commerce_retail": ["products", "items", "posts"],
            "service_health_b2b": ["services", "doctors", "posts", "pages"]
        }.get(archetype, ["posts", "pages"])
        return sorted(candidates, key=lambda x: priority.index(x) if x in priority else 99)
    except Exception as e:
        logger.warning(f"[DISCOVER_TYPES_FAIL] {e}")
        return ["posts"]

def generate_single_cover_for_project(
    project_id: str, title: str, archetype: str = "", genre: str = "", author: str = "Sưu tầm",
    price: str = "", specs: Optional[List[str]] = None, doctor: str = "", aspect_ratio: str = "auto", portal_host: str = ""
) -> Dict[str, Any]:
    project = db_service.get_project_by_id(project_id)
    if not project:
        raise HTTPException(status_code=404, detail="Không tìm thấy dự án.")
    resolved_arch = archetype or detect_project_archetype(project)
    target_url = project.get("target_website_url", "")
    domain = (urllib.parse.urlsplit(target_url).hostname or target_url).replace("www.", "") if target_url else "example.com"
    out_dir, web_base = _get_project_covers_dir(project_id)
    res = generate_universal_visual_asset(
        title=title, archetype=resolved_arch, aspect_ratio=aspect_ratio, genre=genre, author=author,
        price=price, specs=specs, doctor=doctor, domain=domain, output_dir=out_dir
    )
    res["web_url"] = f"{web_base}/{res['file_name']}"
    res["full_url"] = f"{portal_host.rstrip('/')}{res['web_url']}" if portal_host else res["web_url"]
    return res

def scan_and_repair_project_covers(project_id: str, limit: int = 10, portal_base_url: str = "") -> Dict[str, Any]:
    project = db_service.get_project_by_id(project_id)
    if not project:
        raise HTTPException(status_code=404, detail="Không tìm thấy dự án.")
    cms_url = (project.get("cms_api_url") or "").rstrip("/")
    if os.path.exists("/.dockerenv") and ("127.0.0.1:8080" in cms_url or "localhost:8080" in cms_url):
        cms_url = cms_url.replace("127.0.0.1:8080", "wordpress:80").replace("localhost:8080", "wordpress:80")
    auth = json.loads(project.get("cms_auth_config") or "{}") if isinstance(project.get("cms_auth_config"), str) else (project.get("cms_auth_config") or {})
    api_key = auth.get("api_key", "").strip()
    if not cms_url or not api_key:
        raise HTTPException(status_code=400, detail="Website chưa kết nối Omni Bridge Connector hợp lệ.")

    headers = {"X-Omni-Bridge-Key": api_key, "Content-Type": "application/json", "User-Agent": "Autonomous-Marketing-Agent/1.0"}
    resolved_arch = detect_project_archetype(project)

    # 1. Kiểm tra phiên bản Omni Bridge qua Health Check
    bridge_ver = "1.0.0"
    try:
        h_req = urllib.request.Request(f"{cms_url}/wp-json/omni-bridge/v1/health", headers=headers)
        with urllib.request.urlopen(h_req, timeout=6) as h_resp:
            h_data = json.loads(h_resp.read().decode("utf-8"))
            bridge_ver = h_data.get("bridge_version") or "1.0.0"
    except Exception as eh:
        logger.warning(f"[HEALTH_CHECK_WARN] {eh}")

    # Nếu website đang dùng v1.3.0 trở xuống, yêu cầu người dùng nâng cấp lên v1.4.0
    if bridge_ver < "1.4.0":
        return {
            "status": "requires_plugin_update",
            "current_version": bridge_ver,
            "required_version": "1.4.0",
            "download_url": "/api/v1/download/omni-bridge-connector",
            "message": f"Website đang kết nối Omni Bridge Connector v{bridge_ver}. Để tự động quét và gán ảnh bìa trực tiếp vào WordPress Media Library một chạm, bạn vui lòng cập nhật plugin lên bản v1.4.0."
        }

    # 2. Quét bài viết / truyện thiếu ảnh bìa qua Omni Bridge v1.4.0+
    missing_items = []
    topology = project.get("site_topology") or {}
    topology = json.loads(topology) if isinstance(topology, str) else topology
    primary_cpt = topology.get("primary_content_type") or ("sab_story" if resolved_arch == "content_media" else "any")

    try:
        req = urllib.request.Request(f"{cms_url}/wp-json/omni-bridge/v1/missing-thumbnails?post_type={primary_cpt}&limit={min(limit, 30)}", headers=headers)
        with urllib.request.urlopen(req, timeout=10) as resp:
            raw_items = json.loads(resp.read().decode("utf-8")).get("items", [])
            missing_items = [it for it in raw_items if it.get("post_type") not in ("page", "sab_chapter", "chapter", "attachment")]
    except Exception as e:
        logger.warning(f"[SCAN_ROUTE_ERR] {e}")

    # Fallback khám phá động theo Site Topology nếu route trên chưa trả về kết quả
    if not missing_items:
        known_cpts = [k for k in topology.get("post_types", {}).keys() if k not in ("page", "sab_chapter", "chapter", "attachment")] if topology.get("post_types") else []
        endpoints = known_cpts or _discover_public_content_endpoints(cms_url, headers, resolved_arch)
        endpoints = [ep for ep in endpoints if ep not in ("pages", "sab_chapter", "chapter")]
        for ep in endpoints:
            if len(missing_items) >= limit:
                break
            try:
                ep_req = urllib.request.Request(f"{cms_url}/wp-json/wp/v2/{ep}?per_page={min(limit - len(missing_items), 15)}", headers=headers)
                with urllib.request.urlopen(ep_req, timeout=10) as ep_resp:
                    items = json.loads(ep_resp.read().decode("utf-8"))
                    if isinstance(items, list):
                        for p in items:
                            if not p.get("featured_media"):
                                t_obj = p.get("title", {})
                                t_str = t_obj.get("rendered", "") if isinstance(t_obj, dict) else str(p.get("title") or "")
                                missing_items.append({
                                    "id": p.get("id"),
                                    "title": t_str or f"Mục {p.get('id')}",
                                    "slug": p.get("slug", ""),
                                    "post_type": p.get("type") or ep,
                                    "categories": []
                                })
            except Exception as e_ep:
                logger.warning(f"[SCAN_EP_ERR] {ep}: {e_ep}")

    domain = (urllib.parse.urlsplit(project.get("target_website_url", "")).hostname or "example.com").replace("www.", "")
    out_dir, web_base = _get_project_covers_dir(project_id)
    portal_host = portal_base_url or os.getenv("PORTAL_BASE_URL", "https://seo.vuonghoang.name.vn").rstrip("/")

    repaired_results = []
    for item in missing_items[:limit]:
        p_id = item.get("id")
        p_title = item.get("title", f"Bài viết {p_id}")
        genre_str = item.get("categories", [""])[0] if item.get("categories") else ""

        try:
            cover_info = generate_universal_visual_asset(
                title=p_title, archetype=resolved_arch, genre=genre_str, domain=domain,
                output_dir=out_dir, slug=item.get("slug", "")
            )
            image_public_url = f"{portal_host}{web_base}/{cover_info['file_name']}"

            b_req = urllib.request.Request(
                f"{cms_url}/wp-json/omni-bridge/v1/set-featured-image",
                data=json.dumps({"post_id": p_id, "image_url": image_public_url, "alt_text": cover_info["alt_text"]}).encode("utf-8"),
                headers=headers
            )
            with urllib.request.urlopen(b_req, timeout=15) as b_resp:
                b_data = json.loads(b_resp.read().decode("utf-8"))
                repaired_results.append({
                    "post_id": p_id, "title": p_title, "cover_url": image_public_url,
                    "status": "success", "attachment_id": b_data.get("attachment_id")
                })
        except Exception as e_bind:
            repaired_results.append({"post_id": p_id, "title": p_title, "status": "failed", "error": str(e_bind)})

    return {
        "status": "success", "archetype": resolved_arch,
        "total_scanned": len(missing_items),
        "total_repaired": sum(1 for r in repaired_results if r.get("status") == "success"),
        "items": repaired_results
    }
