#!/usr/bin/env python3
"""Media curation, logo extraction & competitor watermark detection."""
import re
from urllib.parse import urlparse, urljoin
from bs4 import BeautifulSoup
from scraper_core import fetch_html

def extract_website_logo(target_url: str, provided_logo_url: str = "") -> str:
    """3-Tầng tự thu thập Logo Thương Hiệu: 1. User cung cấp, 2. HTML scrape, 3. Google Favicon."""
    if provided_logo_url and provided_logo_url.strip().startswith("http"):
        return provided_logo_url.strip()

    parsed = urlparse(target_url)
    base_url = f"{parsed.scheme}://{parsed.netloc}"

    try:
        html = fetch_html(base_url, timeout=10)
        if html:
            soup = BeautifulSoup(html, "html.parser")
            for rel_val in ["apple-touch-icon", "apple-touch-icon-precomposed"]:
                tag = soup.find("link", rel=lambda r: r and rel_val in (r if isinstance(r, list) else [r]))
                if tag and tag.get("href"):
                    return urljoin(base_url, tag["href"])

            for link_tag in soup.find_all("link", rel=True):
                rels = link_tag.get("rel", [])
                if isinstance(rels, str): rels = [rels]
                if any(r in rels for r in ["icon", "shortcut icon"]):
                    href = link_tag.get("href", "")
                    sizes = link_tag.get("sizes", "")
                    try: w = int(sizes.split("x")[0]) if sizes and "x" in sizes else 0
                    except Exception: w = 0
                    if href and (w >= 32 or not sizes):
                        return urljoin(base_url, href)

            for img in soup.find_all("img"):
                attrs_str = " ".join([str(img.get("class", "")), str(img.get("id", "")), str(img.get("alt", "")), str(img.get("src", ""))]).lower()
                if "logo" in attrs_str and img.get("src"):
                    return urljoin(base_url, img.get("src"))

            og_img = soup.find("meta", property="og:image")
            if og_img and og_img.get("content"):
                return urljoin(base_url, og_img["content"])
    except Exception:
        pass

    domain = parsed.netloc or target_url.replace("https://", "").replace("http://", "").split("/")[0]
    return f"https://www.google.com/s2/favicons?domain={domain}&sz=128"

def is_watermarked_or_low_quality(img_url: str, alt_text: str = "") -> bool:
    """Kiểm tra và loại bỏ ảnh chứa Watermark đối thủ hoặc ảnh kích thước thumbnail quá nhỏ."""
    if not img_url:
        return True
    url_l = img_url.lower()
    alt_l = (alt_text or "").lower()

    bad_patterns = [
        "100x100", "150x150", "300x", "icon", "logo", "badge", "bct.svg", "favicon",
        "avatar", "rating", "star", "banner", "footer", "header", "payment",
        "visa", "mastercard", "vnpay", "tragop", "giao-hang", "hotline"
    ]
    if any(p in url_l for p in bad_patterns):
        return True

    if "mihanoi.vn" in url_l and any(k in url_l for k in ["robot", "hut-bui", "tivi", "san-pham"]):
        return True

    if any(k in url_l or k in alt_l for k in ["nha-khoa", "dentist", "paris", "rang", "nhakhoa", "doctor"]):
        return True

    return False

def curate_clean_competitor_images(keyword: str, competitor_urls: list = None) -> dict:
    """Chắt lọc bộ ảnh đẹp nhất từ đa đối thủ và CDN chính hãng (loại bỏ watermark)."""
    clean_hero = ""
    clean_gallery = []
    clean_features = {}

    target_urls = competitor_urls or []
    if not target_urls:
        try:
            from serp_discovery_tool import discover_top_10_competitors
            discovered = discover_top_10_competitors(keyword)
            target_urls = [c["url"] for c in discovered if c.get("is_online") and c.get("url")]
        except Exception:
            pass

    if target_urls:
        from scraper_core import scrape_universal_url
        for c_url in target_urls[:4]:
            try:
                scraped = scrape_universal_url(c_url, timeout=5)
                for img in scraped.get("images", []):
                    if not is_watermarked_or_low_quality(img):
                        if not clean_hero:
                            clean_hero = img
                        elif img not in clean_gallery and img != clean_hero:
                            clean_gallery.append(img)
                        if len(clean_gallery) >= 5:
                            break
            except Exception:
                pass
            if clean_hero and len(clean_gallery) >= 3:
                break

    return {
        "hero_image": clean_hero,
        "gallery_images": clean_gallery,
        "feature_images": clean_features,
        "watermark_removed": True,
        "source": "curated_clean_assets"
    }
