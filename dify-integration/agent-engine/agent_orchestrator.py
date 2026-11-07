#!/usr/bin/env python3
"""
Multi-Agent Orchestrator Facade (SOP 23 compliant <= 300 lines).
Coordinates:
  - orchestrator_llm.py: 9Router LLM call, prompt building, FAQ schema & technical specs
  - orchestrator_content.py: Content cleanup, spelling fixes, image curation & HTML enrichment
  - orchestrator_publisher.py: Omni Bridge REST API & local Docker CLI publisher
"""
import os
import sys
import re
import json
import time
from datetime import datetime
from typing import Optional

CURRENT_DIR = os.path.dirname(os.path.abspath(__file__))
BASE_DIR = os.path.abspath(os.path.join(CURRENT_DIR, ".."))
CAMPAIGNS_DIR = os.path.join(BASE_DIR, "autopilot", "campaigns")
os.makedirs(CAMPAIGNS_DIR, exist_ok=True)

sys.path.insert(0, CURRENT_DIR)
from scraper_tool import scrape_competitor_recon
import db_service

from orchestrator_llm import (
    call_9router,
    extract_and_generate_faq_schema,
    extract_technical_specs_from_markdown,
    build_system_prompt,
)
from orchestrator_content import (
    split_and_clean_content,
    curate_featured_and_gallery,
    convert_md_to_html_with_images,
    stage_featured_image,
)
from orchestrator_publisher import (
    publish_via_omni_bridge,
    publish_via_sandbox_cli,
)

__all__ = [
    "call_9router",
    "publish_via_omni_bridge",
    "extract_and_generate_faq_schema",
    "extract_technical_specs_from_markdown",
    "run_full_pipeline",
]

def run_full_pipeline(
    keyword: str,
    category_name: str = "",
    publish_to_wp: bool = False,
    post_type: str = "post",
    post_status: str = "publish",
    project_id: Optional[str] = None,
    tenant_id: Optional[str] = None,
    brand_name: str = "",
    target_website_url: str = "",
    target_location: str = "",
    business_description: str = "",
    brand_profile: Optional[dict] = None,
) -> dict:
    """Chạy trọn vẹn chu trình 5 Tầng: Cào đối thủ, Multi-Agent qua 9Router, Đẩy WordPress CMS."""
    start_time = time.time()
    job_id = f"job_{int(start_time)}_{keyword.replace(' ', '_')[:20]}"

    project_intelligence = None
    project_name = brand_name or "Dự án marketing"
    target_url = target_website_url
    target_location = target_location or ""
    business_desc = business_description or category_name or "Sản phẩm hoặc dịch vụ"
    uvp_positioning = "Chưa có UVP được phê duyệt"
    top_competitors_intel = []
    brand_logo_url = None
    brand_logo_path = None

    if project_id:
        try:
            p_data = db_service.get_project_full_intelligence(project_id, tenant_id)
            if p_data and p_data.get("project"):
                project = p_data["project"]
                project_intelligence = p_data
                project_name = project.get("name") or project_name
                target_url = project.get("target_website_url") or target_url
                target_location = project.get("target_location") or target_location
                business_desc = project.get("business_description") or business_desc
                uvp_positioning = project.get("core_strengths") or uvp_positioning
                brand_logo_url = project.get("brand_logo_url")
                brand_logo_path = project.get("brand_logo_path")
                top_competitors_intel = p_data.get("competitors") or []
        except Exception as err:
            print(f"⚠️ Cảnh báo nạp hồ sơ dự án {project_id}: {err}")

    competitor_urls = [c.get("url") for c in top_competitors_intel if c.get("url")]
    competitor_data = scrape_competitor_recon(keyword, custom_competitor_urls=competitor_urls if competitor_urls else None)
    products_found = competitor_data.get("products", [])

    competitor_summary = ""
    if top_competitors_intel:
        competitor_summary += f"MA TRẬN 10 ĐỐI THỦ THỰC TẾ TRONG NGÀNH:\n"
        for idx, c in enumerate(top_competitors_intel[:10], 1):
            competitor_summary += f"{idx}. [{c.get('domain') or c.get('url')}] ➜ Điểm mạnh: {c.get('strengths')} | Lỗ hổng: {c.get('weaknesses')}\n"
        competitor_summary += "\n"

    first_sale_price_num = 0
    first_orig_price_num = 0
    if products_found:
        competitor_summary += f"DỮ LIỆU SẢN PHẨM & GIÁ BÁN:\n"
        for p in products_found[:8]:
            competitor_summary += f"- [{p.get('competitor')}] {p.get('name')} ➜ Giá: {p.get('price_display') or p.get('sale_price') or 'Liên hệ'}\n"
            if first_sale_price_num == 0:
                raw_sp = re.sub(r"[^\d]", "", p.get('sale_price', ''))
                raw_op = re.sub(r"[^\d]", "", p.get('original_price', ''))
                if raw_sp.isdigit() and int(raw_sp) > 0: first_sale_price_num = int(raw_sp)
                if raw_op.isdigit() and int(raw_op) > 0: first_orig_price_num = int(raw_op)
        competitor_summary += "\n"

    system_prompt = build_system_prompt(post_type, project_intelligence, project_name, target_url, target_location, business_desc, uvp_positioning)
    user_prompt = (
        f"Từ khóa/Tên sản phẩm: '{keyword}' | Ngành hàng: '{category_name}' | Loại: {post_type}\n"
        f"Hồ sơ thương hiệu: {json.dumps(brand_profile or {}, ensure_ascii=False)}\n"
        f"DỮ LIỆU ĐỐI THỦ:\n{competitor_summary}\nThực thi chiến dịch hoàn chỉnh."
    )

    llm_output = call_9router(system_prompt, user_prompt, max_tokens=4000)
    brand_suffix = project_name if project_intelligence else "Dự án marketing"
    post_title, clean_md_content, short_description, social_campaign = split_and_clean_content(llm_output, post_type, keyword, brand_suffix)

    feat_img, gallery_images, feature_images = curate_featured_and_gallery(keyword, category_name, products_found)
    html_article_content = convert_md_to_html_with_images(clean_md_content, keyword, brand_suffix, feature_images, gallery_images)
    faq_schema_script = extract_and_generate_faq_schema(clean_md_content)
    if faq_schema_script: html_article_content += faq_schema_script

    clean_sku = f"MI-{re.sub(r'[^A-Z0-9]', '', keyword.upper())[:10]}-88"
    staged_img_path, staged_img_url = stage_featured_image(feat_img, keyword, category_name, brand_suffix, project_id, brand_logo_path, brand_logo_url)

    wp_post_id = None
    wp_status = "Skipped"

    if publish_to_wp:
        meta_desc = f"{post_title}. Khám phá thông tin chi tiết, dịch vụ chính hãng và cam kết chất lượng tại {brand_suffix}."
        rank_math_title = f"{post_title} - {brand_suffix}"
        bridge_url = None
        bridge_key = None
        if project_intelligence and project_intelligence.get("project"):
            proj = project_intelligence["project"]
            bridge_url = proj.get("cms_api_url")
            cms_auth = proj.get("cms_auth_config") or {}
            bridge_key = cms_auth.get("api_key") if isinstance(cms_auth, dict) else (cms_auth if isinstance(cms_auth, str) else None)

        if not bridge_url: bridge_url = os.environ.get("OMNI_BRIDGE_URL", "")
        if bridge_url and os.path.exists("/.dockerenv") and ("127.0.0.1:8080" in bridge_url or "localhost:8080" in bridge_url):
            bridge_url = bridge_url.replace("127.0.0.1:8080", "wordpress:80").replace("localhost:8080", "wordpress:80")
        if not bridge_key: bridge_key = os.environ.get("OMNI_BRIDGE_KEY", "")

        bridge_success = False
        if bridge_url and bridge_key:
            try:
                bridge_payload = {
                    "post_title": post_title, "post_content": html_article_content,
                    "post_excerpt": short_description, "post_status": post_status,
                    "post_type": post_type, "categories": [category_name],
                    "tags": [keyword, brand_suffix],
                    "seo_meta": {"focus_keyword": keyword, "seo_title": rank_math_title, "seo_description": meta_desc}
                }
                final_img_to_use = staged_img_url or feat_img
                if final_img_to_use: bridge_payload["featured_image_url"] = final_img_to_use

                if post_type == "product":
                    html_article_content = re.sub(r'<p><strong>Thông số kỹ thuật[^<]*:?</strong></p>\s*<table>.*?</table>', '', html_article_content, flags=re.DOTALL | re.IGNORECASE)
                    bridge_payload["post_content"] = html_article_content
                    parsed_specs = extract_technical_specs_from_markdown(clean_md_content)
                    bridge_payload["product_data"] = {"sku": clean_sku, "stock_status": "instock"}
                    if first_orig_price_num > 0: bridge_payload["product_data"]["regular_price"] = first_orig_price_num
                    if first_sale_price_num > 0: bridge_payload["product_data"]["sale_price"] = first_sale_price_num
                    if gallery_images: bridge_payload["product_data"]["gallery_image_urls"] = gallery_images
                    if parsed_specs: bridge_payload["product_data"]["technical_specs"] = parsed_specs

                b_res = publish_via_omni_bridge(bridge_url, bridge_key, bridge_payload)
                if b_res.get("success"):
                    wp_post_id = b_res.get("post_id")
                    wp_status = f"Draft Post ID #{wp_post_id} via Omni Bridge ({b_res.get('permalink')})"
                    bridge_success = True
            except Exception as b_err:
                print(f"   ⚠️ Thử xuất bản Omni Bridge thất bại: {b_err}")

        if not bridge_success and os.environ.get("ALLOW_LOCAL_WP_FALLBACK", "false").lower() == "true":
            featured_img_target = staged_img_url or feat_img or ""
            wp_post_id, wp_status = publish_via_sandbox_cli(
                post_title, html_article_content, short_description, category_name,
                keyword, meta_desc, rank_math_title, post_type,
                first_sale_price_num, first_orig_price_num, clean_sku, featured_img_target
            )
        elif not bridge_success:
            wp_status = "Chưa xuất bản: thiếu Omni Bridge URL/API key hoặc fallback nội bộ chưa được bật."

    elapsed = round(time.time() - start_time, 2)
    campaign_file = os.path.join(CAMPAIGNS_DIR, f"{job_id}.md")
    with open(campaign_file, "w", encoding="utf-8") as f:
        f.write(f"# CHIẾN DỊCH SEO & GEO: {keyword}\n- Thời gian: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}\n- WordPress: {wp_status}\n\n## 1. BÀI VIẾT\n\n{clean_md_content}\n\n## 2. SOCIAL\n\n{social_campaign}\n")

    return {
        "job_id": job_id, "project_id": project_id, "brand_name": project_name,
        "uvp_positioning": uvp_positioning if project_intelligence else None,
        "keyword": keyword, "category": category_name, "status": "completed",
        "elapsed_seconds": elapsed, "competitor_data": competitor_data,
        "wordpress_status": wp_status, "wordpress_post_id": wp_post_id,
        "post_title": post_title, "campaign_file": campaign_file,
        "social_campaign": social_campaign,
        "summary": f"Chiến dịch SEO '{keyword}' cho {project_name} hoàn thành trong {elapsed}s. {wp_status}."
    }

if __name__ == "__main__":
    kw = sys.argv[1] if len(sys.argv) > 1 else ""
    cat = sys.argv[2] if len(sys.argv) > 2 else ""
    result = run_full_pipeline(kw, cat, publish_to_wp=True)
    print("\nKẾT QUẢ CUỐI CÙNG:")
    print(json.dumps({k: v for k, v in result.items() if k != 'article_content'}, ensure_ascii=False, indent=2))
