#!/usr/bin/env python3
"""Website inspection, URL normalization, logo extraction & online check."""
import re
import time
import urllib.parse
import subprocess
from typing import Optional
from bs4 import BeautifulSoup

HEADERS = [
    "-H", "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36",
    "-H", "Accept-Language: vi-VN,vi;q=0.9,en-US;q=0.8,en;q=0.7"
]

class SerpDiscoveryError(RuntimeError):
    """Raised when live search results cannot be obtained or verified."""

SEARCH_ENGINES = ("duckduckgo.com", "google.com", "bing.com", "yahoo.com", "wikipedia.org")
SOCIAL_DOMAINS = ("facebook.com", "youtube.com", "tiktok.com", "instagram.com", "pinterest.com", "twitter.com", "x.com", "linkedin.com")
MARKETPLACE_DOMAINS = ("shopee.vn", "lazada.vn", "tiki.vn", "sendo.vn")
GOV_DOMAINS = (
    "dangkykinhdoanh.gov.vn", "chinhphu.vn", "dichvucong.gov.vn",
    "mic.vn", "luatvietnam.vn", "thuvienphapluat.vn", "moit.gov.vn",
    "bocongan.gov.vn", "customs.gov.vn", "gdt.gov.vn", "baochinhphu.vn"
)
NEWS_MEDIA_DOMAINS = (
    "vnexpress.net", "dantri.com.vn", "thanhnien.vn", "tuoitre.vn",
    "cafef.vn", "cafebiz.vn", "vietnamnet.vn", "znews.vn", "zingnews.vn",
    "vtv.vn", "laodong.vn", "24h.com.vn", "tienphong.vn", "nld.com.vn",
    "vtcnews.vn", "soha.vn", "kenh14.vn", "genk.vn", "tinhte.vn", "voz.vn", "techrum.vn"
)
GLOBAL_EXCLUDED_DOMAINS = SEARCH_ENGINES + SOCIAL_DOMAINS + MARKETPLACE_DOMAINS + GOV_DOMAINS + NEWS_MEDIA_DOMAINS

MEDIA_PR_SIGNALS = (
    "mở bán", "trên tay", "đánh giá", "theo nguồn tin", "phóng sự",
    "bài viết", "tin tức", "thời sự", "báo", "chuyên mục", "tác giả:",
    "chính hãng giá tốt -", "đổi mới tận nhà nếu", "tinhte", "diễn đàn",
    "review", "unboxing", "bình luận", "nhận định", "báo cáo thị trường"
)

RETAIL_COMMERCIAL_SIGNALS = (
    "giá rẻ", "giá sốc", "góp 0%", "mua ngay", "đặt hàng", "freeship",
    "giao hàng", "bảo hành chính hãng", "kho sẵn", "bao giao lắp",
    "khuyến mãi", "ưu đãi", "giỏ hàng", "hotline:", "địa chỉ showroom",
    "trả góp", "tặng kèm", "đơn hàng", "chính hãng 100%"
)

def detect_page_intent_heuristic(title: str = "", snippet: str = "", domain: str = "") -> str:
    """Phân loại ý định của trang web (Intent Heuristics) không phụ thuộc danh sách tên miền."""
    text = f"{title} {snippet}".lower()
    d = (domain or "").lower()
    if any(k in d for k in ["news", "tin", "bao", "press", "forum", "diendan", "review"]):
        if not any(comm in text for comm in ["mua ngay", "giỏ hàng", "đặt hàng", "trả góp 0%"]):
            return "NEWS_MEDIA"
    has_pr_signal = any(sig in text for sig in MEDIA_PR_SIGNALS)
    has_retail_signal = any(sig in text for sig in RETAIL_COMMERCIAL_SIGNALS)
    if has_pr_signal and not has_retail_signal:
        return "NEWS_MEDIA"
    if has_retail_signal:
        return "RETAIL_COMMERCIAL"
    return "GENERAL"

def is_excluded_competitor_domain(
    domain: str, context: str = "", title: str = "", snippet: str = ""
) -> bool:
    """
    Loại trừ tên miền cơ quan nhà nước, luật pháp, mạng xã hội, sàn TMĐT khỏi danh sách đối thủ cạnh tranh.
    Tự động thích ứng theo ngữ cảnh ngành nghề & ý định trang web (Context & Intent Heuristics):
    - Nếu dự án làm báo chí / tin tức: giữ lại các trang báo chí làm đối thủ trực tiếp.
    - Nếu dự án bán lẻ / TMĐT: tự động lọc các bài báo PR / trang tin tức (bằng cả danh sách và Heuristic).
    """
    if not domain:
        return True
    d = domain.lower().removeprefix("www.")
    if d.endswith(".gov.vn") or d.endswith(".gov") or d.endswith(".chinhphu.vn") or ".gov." in d:
        return True
    base_excluded = SEARCH_ENGINES + GOV_DOMAINS + SOCIAL_DOMAINS + MARKETPLACE_DOMAINS
    if any(d == exc or d.endswith(f".{exc}") for exc in base_excluded):
        return True
    ctx = (context or "").lower()
    is_media_project = any(k in ctx for k in ["báo", "tin tức", "media", "news", "tạp chí", "thông tin", "journalism", "editorial"])
    if is_media_project:
        return False
    if any(d == exc or d.endswith(f".{exc}") for exc in NEWS_MEDIA_DOMAINS):
        return True
    if title or snippet:
        intent = detect_page_intent_heuristic(title, snippet, d)
        if intent == "NEWS_MEDIA":
            return True
    return False

def normalize_website_url(url: str) -> str:
    value = (url or "").strip()
    if not value:
        raise ValueError("Website URL không được để trống")
    if "://" not in value:
        value = f"https://{value}"
    parsed = urllib.parse.urlsplit(value)
    if parsed.scheme not in {"http", "https"} or not parsed.hostname:
        raise ValueError("URL phải là địa chỉ website HTTP hoặc HTTPS hợp lệ")
    if parsed.username or parsed.password:
        raise ValueError("URL website không được chứa thông tin đăng nhập")
    return value

def check_url_online(url: str, timeout: int = 4) -> bool:
    """Kiểm tra xem tên miền/URL có đang online và trả về mã HTTP hợp lệ hay không."""
    url = normalize_website_url(url)
    cmd = ["curl", "-s", "-o", "/dev/null", "-w", "%{http_code}", "-L", "--max-time", str(timeout)] + HEADERS + [url]
    try:
        import serp_discovery_tool
        sub_runner = getattr(serp_discovery_tool.subprocess, "run", subprocess.run)
        res = sub_runner(cmd, capture_output=True, text=True, timeout=timeout + 2)
        code = int(res.stdout.strip() or "0")
        return 200 <= code < 400
    except Exception:
        return False

def extract_website_logo(url: str, soup: BeautifulSoup = None, html: str = None) -> Optional[str]:
    """Tier 2 Intelligent Brand Logo Extractor."""
    import json
    from urllib.parse import urljoin
    if not soup:
        if not html:
            if not url.startswith("http"):
                url = "https://" + url
            cmd = ["curl", "-s", "-L", "--max-time", "6"] + HEADERS + [url]
            try:
                import serp_discovery_tool
                sub_runner = getattr(serp_discovery_tool.subprocess, "run", subprocess.run)
                res = sub_runner(cmd, capture_output=True, text=True, timeout=8)
                html = res.stdout
            except Exception:
                return None
        if not html:
            return None
        soup = BeautifulSoup(html, "html.parser")

    for script in soup.find_all("script", type="application/ld+json"):
        try:
            data = json.loads(script.string or "")
            if isinstance(data, dict):
                if data.get("@type") in ["Organization", "Corporation", "LocalBusiness", "Store"]:
                    logo = data.get("logo")
                    if isinstance(logo, str) and logo:
                        return urljoin(url, logo)
                    elif isinstance(logo, dict) and logo.get("url"):
                        return urljoin(url, logo["url"])
            elif isinstance(data, list):
                for item in data:
                    if isinstance(item, dict) and item.get("@type") in ["Organization", "Corporation", "LocalBusiness", "Store"]:
                        logo = item.get("logo")
                        if isinstance(logo, str) and logo:
                            return urljoin(url, logo)
                        elif isinstance(logo, dict) and logo.get("url"):
                            return urljoin(url, logo["url"])
        except Exception:
            pass

    header = soup.find("header") or soup.find("nav") or soup
    for img in header.find_all("img"):
        alt = img.get("alt", "").lower()
        cls = " ".join(img.get("class", [])).lower() if isinstance(img.get("class"), list) else str(img.get("class", "")).lower()
        src = img.get("src") or img.get("data-src") or ""
        img_id = img.get("id", "").lower()
        if any(k in alt or k in cls or k in img_id or k in src.lower() for k in ["logo", "brand", "header-logo", "site-logo"]):
            if src and not src.startswith("data:"):
                return urljoin(url, src)

    for link in soup.find_all("link", rel=lambda r: r and "icon" in str(r).lower()):
        href = link.get("href")
        rel = " ".join(link.get("rel", [])) if isinstance(link.get("rel"), list) else str(link.get("rel", ""))
        if href and ("apple-touch-icon" in rel.lower() or "192x192" in link.get("sizes", "")):
            return urljoin(url, href)

    og_img = soup.find("meta", property="og:image")
    if og_img and og_img.get("content"):
        return urljoin(url, og_img["content"])

    return None

def inspect_website(url: str, timeout: int = 6) -> dict:
    """Bóc tách cấu trúc cơ bản của website: Title, Meta, Headings, Navigation, Contact."""
    url = normalize_website_url(url)
    sep = "&" if "?" in url else "?"
    audit_fetch_url = f"{url}{sep}_omni_audit={int(time.time())}"
    cache_headers = [
        "-H", "Cache-Control: no-cache, no-store, must-revalidate",
        "-H", "Pragma: no-cache"
    ]
    cmd = [
        "curl", "-sS", "-L", "--max-time", str(timeout),
        "-w", "\n__HTTP_STATUS__%{http_code}\n__EFFECTIVE_URL__%{url_effective}",
    ] + HEADERS + cache_headers + [audit_fetch_url]
    try:
        import serp_discovery_tool
        sub_runner = getattr(serp_discovery_tool.subprocess, "run", subprocess.run)
        res = sub_runner(cmd, capture_output=True, text=True, timeout=timeout + 2)
        html, metadata = res.stdout.rsplit("\n__HTTP_STATUS__", 1)
        status_text, effective_url = metadata.split("\n__EFFECTIVE_URL__", 1)
        status_code = int(status_text.strip() or "0")
        effective_url = effective_url.strip()
    except Exception as e:
        return {"status": "offline_or_error", "url": url, "error": str(e)}

    if not 200 <= status_code < 400:
        return {
            "status": "http_error" if status_code else "unreachable",
            "url": url,
            "http_status": status_code,
            "summary": "Không thể xác minh website truy cập thành công.",
        }
    if not html or len(html) < 200:
        return {
            "status": "empty",
            "url": effective_url or url,
            "http_status": status_code,
            "summary": "Website phản hồi nhưng không có đủ nội dung HTML để phân tích.",
        }

    soup = BeautifulSoup(html, "html.parser")
    title_tag = soup.find("title")
    title = title_tag.get_text(strip=True) if title_tag else ""

    meta_desc_tag = soup.find("meta", attrs={"name": re.compile(r"description", re.I)})
    meta_desc = meta_desc_tag.get("content", "").strip() if meta_desc_tag else ""

    h1s = [h.get_text(strip=True) for h in soup.find_all("h1") if h.get_text(strip=True)][:3]
    h2s = [h.get_text(strip=True) for h in soup.find_all("h2") if h.get_text(strip=True)][:8]

    nav_links = []
    for a in soup.find_all("a", href=True)[:30]:
        text = a.get_text(strip=True)
        href = a["href"]
        if text and len(text) > 3 and not href.startswith("#") and "javascript" not in href:
            if text not in nav_links:
                nav_links.append(text)

    hotlines = re.findall(r"(?:0|\+84)[0-9\.\-\s]{9,13}", html)
    clean_hotlines = list(set([re.sub(r"[^\d]", "", h) for h in hotlines if len(re.sub(r"[^\d]", "", h)) in [10, 11]]))[:3]
    discovered_logo = extract_website_logo(url, soup=soup, html=html)

    return {
        "status": "online",
        "url": effective_url or url,
        "http_status": status_code,
        "title": title,
        "meta_description": meta_desc,
        "logo_url": discovered_logo,
        "h1": h1s,
        "h2": h2s[:5],
        "key_services_or_nav": nav_links[:10],
        "hotlines": clean_hotlines
    }
