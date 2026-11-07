#!/usr/bin/env python3
"""Site structure analysis - split from seed_analyzer.py."""
import json, logging, re, subprocess, urllib.parse
from concurrent.futures import ThreadPoolExecutor
from datetime import datetime, timezone
from typing import Dict, List, Optional, Any, Union
from bs4 import BeautifulSoup
from serp_discovery_tool import get_latest_paa_questions
from seed_utils import HEADERS, normalize_url, fetch_html_with_meta, fetch_html, VIETNAMESE_STOPWORDS, INTENT_MODIFIERS

logger = logging.getLogger("seed_analyzer")

def detect_tech_stack_and_cms(soup: BeautifulSoup, html: str) -> dict:
    """Trụ Cột 3: Nhận diện nền tảng CMS và công cụ SEO của đối thủ."""
    html_lower = (html or "").lower()
    cms = "Custom Web (Code tay)"
    if "wp-content" in html_lower or "wp-includes" in html_lower or (soup.find("meta", attrs={"name": "generator", "content": re.compile(r"WordPress", re.I)})):
        if "woocommerce" in html_lower or "wc-block" in html_lower or "product-type-" in html_lower:
            cms = "WordPress (WooCommerce)"
        else:
            cms = "WordPress CMS"
    elif "cdn.shopify.com" in html_lower or "shopify.theme" in html_lower:
        cms = "Shopify Platform"
    elif "haravan" in html_lower or "hstatic.net" in html_lower:
        cms = "Haravan Omni"
    elif "mage/" in html_lower or "mage.cookies" in html_lower:
        cms = "Magento E-Commerce"
    elif "__next_data__" in html_lower:
        cms = "Next.js (React Framework)"
    elif "__nuxt__" in html_lower:
        cms = "Nuxt (Vue Framework)"

    seo_plugin = "Mặc định / Tùy chỉnh"
    if "rank-math" in html_lower:
        seo_plugin = "Rank Math SEO"
    elif "yoast" in html_lower:
        seo_plugin = "Yoast SEO"
    elif "aioseo" in html_lower:
        seo_plugin = "All in One SEO"

    return {"cms_platform": cms, "seo_plugin": seo_plugin}



def inspect_robots_and_sitemap(base_url: str, timeout: int = 4) -> dict:
    """Trụ Cột 1: Kiểm toán tệp robots.txt và ước lượng quy mô chỉ mục sitemap.xml."""
    parsed = urllib.parse.urlsplit(normalize_url(base_url))
    root = f"{parsed.scheme}://{parsed.hostname}"
    
    robots_url = f"{root}/robots.txt"
    robots_res = fetch_html_with_meta(robots_url, timeout=timeout)
    robots_txt = robots_res.get("html") or ""
    has_robots = robots_res.get("http_status") == 200 and len(robots_txt) > 20

    sitemap_url = None
    if has_robots:
        for line in robots_txt.splitlines():
            if line.strip().lower().startswith("sitemap:"):
                sitemap_url = line.split(":", 1)[1].strip()
                break

    if not sitemap_url:
        sitemap_url = f"{root}/sitemap_index.xml"

    sm_res = fetch_html_with_meta(sitemap_url, timeout=timeout)
    sm_content = sm_res.get("html") or ""
    has_sitemap = sm_res.get("http_status") == 200 and ("<url" in sm_content or "<sitemap" in sm_content)
    loc_count = len(re.findall(r"<loc>", sm_content, re.I))

    if not has_sitemap or loc_count == 0:
        sitemap_url = f"{root}/sitemap.xml"
        sm_res = fetch_html_with_meta(sitemap_url, timeout=timeout)
        sm_content = sm_res.get("html") or ""
        has_sitemap = sm_res.get("http_status") == 200 and ("<url" in sm_content or "<sitemap" in sm_content)
        loc_count = len(re.findall(r"<loc>", sm_content, re.I))

    estimated_urls = loc_count
    if "<sitemap>" in sm_content and loc_count > 0:
        estimated_urls = loc_count * 120

    return {
        "has_robots": has_robots,
        "has_sitemap": has_sitemap,
        "sitemap_url": sitemap_url if has_sitemap else None,
        "estimated_indexed_urls": estimated_urls,
        "summary": f"Phát hiện sitemap với quy mô khoảng ~{estimated_urls:,} URLs" if has_sitemap and estimated_urls else ("Có sitemap hợp lệ" if has_sitemap else "Chưa phát hiện hoặc sitemap bị ẩn")
    }



def discover_and_audit_subpages(soup: BeautifulSoup, base_url: str, nav_categories: list) -> dict:
    """Trụ Cột 1 & 2: Quét sâu 1 trang Danh mục Silo và 1 trang Sản phẩm/Bài viết chi tiết."""
    clean_base = normalize_url(base_url)
    parsed_base = urllib.parse.urlsplit(clean_base)
    base_host = (parsed_base.hostname or "").lower().removeprefix("www.")

    cat_url = None
    prod_url = None

    for a in soup.find_all("a", href=True):
        href = a["href"].strip()
        if not href or href.startswith(("#", "javascript:", "tel:", "mailto:")):
            continue
        full = urllib.parse.urljoin(clean_base, href)
        parsed = urllib.parse.urlsplit(full)
        host = (parsed.hostname or "").lower().removeprefix("www.")
        if host != base_host:
            continue
        path = parsed.path
        if not path or path == "/":
            continue

        text = a.get_text(" ", strip=True).lower()
        # Tìm Category URL
        if not cat_url and (any(k in path.lower() or k in text for k in ["tivi", "tu-lanh", "may-giat", "dieu-hoa", "danh-muc", "category", "gia-dung"]) or any(c.lower() in text for c in nav_categories[:6])):
            cat_url = full

        # Tìm Product URL
        if not prod_url and (any(k in path.lower() for k in ["san-pham", "product", "-p", ".html"]) or re.search(r"\b\d{2,3}-(?:inch|\"|in)|\b\d{3,4}l|\b\d{1,2}kg", path.lower())):
            prod_url = full

        if cat_url and prod_url:
            break

    cat_audit = {"url": cat_url, "has_breadcrumb": False, "has_seo_desc": False, "products_found": 0}
    prod_audit = {"url": prod_url, "word_count": 0, "has_comparison_table": False, "has_faq": False, "images_count": 0, "alt_coverage_pct": 100}

    # Fetch subpages concurrently
    with ThreadPoolExecutor(max_workers=2) as executor:
        f_cat = executor.submit(fetch_html_with_meta, cat_url) if cat_url else None
        f_prod = executor.submit(fetch_html_with_meta, prod_url) if prod_url else None
        cat_res = f_cat.result() if f_cat else {}
        prod_res = f_prod.result() if f_prod else {}

    if cat_res.get("html"):
        c_soup = BeautifulSoup(cat_res["html"], "html.parser")
        cat_audit["has_breadcrumb"] = bool(c_soup.find(attrs={"class": re.compile(r"breadcrumb", re.I)})) or "BreadcrumbList" in cat_res["html"]
        cat_audit["has_seo_desc"] = len([p.get_text() for p in c_soup.find_all("p") if len(p.get_text().strip()) > 100]) > 0
        cat_audit["products_found"] = len(c_soup.find_all(attrs={"class": re.compile(r"product|item|col", re.I)}))

    p_soup = None
    if prod_res.get("html"):
        p_soup = BeautifulSoup(prod_res["html"], "html.parser")
        text_clean = p_soup.get_text(" ", strip=True)
        prod_audit["word_count"] = len(text_clean.split())
        prod_audit["has_comparison_table"] = bool(p_soup.find("table"))
        prod_audit["has_faq"] = bool(p_soup.find(attrs={"class": re.compile(r"faq|accordion|hoi-dap", re.I)})) or "FAQPage" in prod_res["html"]
        imgs = p_soup.find_all("img")
        prod_audit["images_count"] = len(imgs)
        if imgs:
            imgs_with_alt = [img for img in imgs if img.get("alt", "").strip()]
            prod_audit["alt_coverage_pct"] = int((len(imgs_with_alt) / len(imgs)) * 100)

        # Bóc tách độ tươi mới nội dung (Content Freshness - datePublished vs dateModified)
        date_pub = None
        date_mod = None
        for s in p_soup.find_all("script", type="application/ld+json"):
            try:
                sdata = json.loads(s.string or "")
                items = sdata if isinstance(sdata, list) else [sdata]
                for it in items:
                    if isinstance(it, dict):
                        if it.get("datePublished") and not date_pub:
                            date_pub = str(it.get("datePublished"))[:10]
                        if it.get("dateModified") and not date_mod:
                            date_mod = str(it.get("dateModified"))[:10]
            except Exception:
                pass

        if not date_mod:
            m_mod = p_soup.find("meta", property=re.compile(r"modified_time|updated_time", re.I))
            if m_mod and m_mod.get("content"):
                date_mod = str(m_mod.get("content"))[:10]
        if not date_pub:
            m_pub = p_soup.find("meta", property=re.compile(r"published_time|pubdate", re.I))
            if m_pub and m_pub.get("content"):
                date_pub = str(m_pub.get("content"))[:10]
        if not date_mod:
            t_tag = p_soup.find("time", attrs={"datetime": True})
            if t_tag and t_tag.get("datetime"):
                date_mod = str(t_tag.get("datetime"))[:10]

        prod_audit["date_published"] = date_pub
        prod_audit["date_modified"] = date_mod or date_pub

        days_stale = None
        if prod_audit["date_modified"]:
            try:
                from datetime import datetime
                mod_dt = datetime.strptime(prod_audit["date_modified"][:10], "%Y-%m-%d")
                days_stale = (datetime.now() - mod_dt).days
            except Exception:
                pass
        prod_audit["days_since_update"] = days_stale
        prod_audit["is_stale"] = bool(days_stale is not None and days_stale > 180)

    return {
        "category_page": cat_audit,
        "product_page": prod_audit,
        "prod_html": prod_res.get("html") or "",
        "prod_soup": p_soup
    }



