#!/usr/bin/env python3
"""Core HTTP fetching, price cleaning, Schema.org JSON-LD & universal scraping."""
import json
import re
import subprocess
from urllib.parse import urljoin
from bs4 import BeautifulSoup

try:
    from curl_cffi import requests as cffi_requests
    HAS_CURL_CFFI = True
except ImportError:
    HAS_CURL_CFFI = False

HEADERS = [
    "-H", "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36",
    "-H", "Accept-Language: vi-VN,vi;q=0.9,en-US;q=0.8,en;q=0.7",
    "-H", "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8"
]

def fetch_html(url: str, timeout: int = 8) -> str:
    """Lấy HTML với cơ chế giả lập TLS Fingerprint (curl_cffi) và fallback subprocess."""
    if HAS_CURL_CFFI:
        try:
            resp = cffi_requests.get(
                url,
                impersonate="chrome124",
                timeout=timeout,
                headers={
                    "Accept-Language": "vi-VN,vi;q=0.9,en-US;q=0.8,en;q=0.7",
                    "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8"
                }
            )
            if resp.status_code == 200 and resp.text:
                return resp.text
        except Exception:
            pass

    cmd = ["curl", "-s", "-L", "--max-time", str(timeout)] + HEADERS + [url]
    try:
        res = subprocess.run(cmd, capture_output=True, text=True, timeout=timeout + 2)
        if res.returncode == 0 and res.stdout:
            return res.stdout
    except Exception as e:
        print(f"[Scraper Tool] Lỗi fetch {url}: {e}")
    return ""

def clean_price_text(text: str) -> str:
    """Làm sạch chuỗi giá thành định dạng chuẩn xxx.xxx₫."""
    if not text:
        return ""
    text = text.replace("&nbsp;", " ").replace("\xa0", " ").strip()
    if re.search(r"liên\s*hệ|tùy\s*chọn|hết\s*hàng", text, re.I):
        return "Liên hệ"
    # 1. Số đi liền ký hiệu tiền tệ (₫, đ, VND)
    match_curr = re.search(r"([\d\.,]{3,})\s*(₫|VND|đ)", text, re.I)
    if match_curr:
        num = match_curr.group(1).rstrip(".,")
        return f"{num}₫"
    # 2. Định dạng số phân tách hàng nghìn (ví dụ 12.990.000 hoặc 12,990,000)
    match_fmt = re.search(r"\b([\d]{1,3}(?:[\.,][\d]{3})+)\b", text)
    if match_fmt:
        num = match_fmt.group(1).rstrip(".,")
        return f"{num}₫"
    # 3. Chuỗi số lớn (>= 5 chữ số, ví dụ 12990000)
    match_num = re.search(r"\b(\d{5,})\b", text)
    if match_num:
        num = match_num.group(1)
        formatted = f"{int(num):,}".replace(",", ".")
        return f"{formatted}₫"
    return ""

def extract_json_ld_data(html: str) -> dict:
    """TẦNG 1: Bóc tách Schema.org JSON-LD cho Product và Article."""
    if not html:
        return {}
    soup = BeautifulSoup(html, "html.parser")
    json_ld_scripts = soup.find_all("script", type="application/ld+json")
    extracted = {}
    for script in json_ld_scripts:
        try:
            content = script.string
            if not content:
                continue
            data = json.loads(content.strip())
            items = []
            if isinstance(data, list):
                items = data
            elif isinstance(data, dict):
                items = data.get("@graph", [data]) if isinstance(data.get("@graph"), list) else [data]

            for item in items:
                item_type = str(item.get("@type", "")).lower()
                if "product" in item_type:
                    name = item.get("name", "")
                    description = item.get("description", "")
                    brand = item.get("brand", {}).get("name", "") if isinstance(item.get("brand"), dict) else str(item.get("brand", ""))
                    sku = item.get("sku", "")
                    offers = item.get("offers", {})
                    price = ""
                    orig_price = ""
                    currency = "VND"
                    if isinstance(offers, list) and offers:
                        offers = offers[0]
                    if isinstance(offers, dict):
                        raw_p = str(offers.get("price") or offers.get("lowPrice") or "")
                        currency = offers.get("priceCurrency", "VND")
                        if raw_p:
                            price = clean_price_text(raw_p)
                        raw_orig = str(offers.get("highPrice") or "")
                        if raw_orig and raw_orig != raw_p:
                            orig_price = clean_price_text(raw_orig)
                    images = item.get("image", [])
                    if isinstance(images, str):
                        images = [images]
                    elif isinstance(images, list):
                        images = [img if isinstance(img, str) else img.get("url", "") for img in images if img]
                    extracted["product"] = {
                        "name": name, "sale_price": price, "original_price": orig_price,
                        "description": description, "brand": brand, "sku": sku,
                        "currency": currency, "images": images[:4]
                    }
                elif any(t in item_type for t in ["article", "blogposting", "newsarticle"]):
                    extracted["article"] = {
                        "title": item.get("headline") or item.get("name", ""),
                        "description": item.get("description", ""),
                        "body_snippet": item.get("articleBody", "")[:500] if item.get("articleBody") else item.get("description", ""),
                        "date_published": item.get("datePublished", ""),
                        "author": item.get("author", {}).get("name", "") if isinstance(item.get("author"), dict) else str(item.get("author", ""))
                    }
        except Exception:
            continue
    return extracted

def extract_heuristic_data(html: str, url: str = "") -> dict:
    """TẦNG 2: Bóc tách Heuristic DOM (Title, Headings, Sale/Original Price, Media)."""
    if not html:
        return {}
    soup = BeautifulSoup(html, "html.parser")
    title = ""
    h1_tag = soup.find("h1")
    if h1_tag: title = h1_tag.get_text(strip=True)
    if not title:
        og_title = soup.find("meta", property="og:title")
        if og_title: title = og_title.get("content", "").strip()
    if not title and soup.title: title = soup.title.get_text(strip=True)

    h2s = [h.get_text(strip=True) for h in soup.find_all("h2") if h.get_text(strip=True) and len(h.get_text(strip=True)) > 5][:10]
    h3s = [h.get_text(strip=True) for h in soup.find_all("h3") if h.get_text(strip=True) and len(h.get_text(strip=True)) > 5][:10]

    sale_price = ""
    original_price = ""
    ins_tag = soup.find("ins")
    if ins_tag: sale_price = clean_price_text(ins_tag.get_text(strip=True))
    del_tag = soup.find("del")
    if del_tag: original_price = clean_price_text(del_tag.get_text(strip=True))

    if not sale_price:
        price_elem = soup.find(class_=re.compile(r"price|amount|current-price|sale-price", re.I))
        if price_elem: sale_price = clean_price_text(price_elem.get_text(strip=True))

    main_elem = soup.find(["article", "main"]) or soup.find(class_=re.compile(r"content|post-body|entry-content|product-detail|description", re.I))
    content_snippet = ""
    if main_elem:
        for s in main_elem(["script", "style", "nav", "footer"]):
            s.decompose()
        content_snippet = main_elem.get_text(separator=" ", strip=True)[:1500]

    images = []
    og_img = soup.find("meta", property="og:image")
    if og_img and og_img.get("content"):
        images.append(urljoin(url, og_img["content"]))
    from scraper_media import is_watermarked_or_low_quality
    for img in soup.find_all("img"):
        src = img.get("data-src") or img.get("data-lazy-src") or img.get("src") or ""
        if src and not src.startswith("data:"):
            full_src = urljoin(url, src)
            if not is_watermarked_or_low_quality(full_src, img.get("alt", "")):
                if full_src not in images: images.append(full_src)
            if len(images) >= 12: break

    return {
        "url": url, "title": title, "sale_price": sale_price, "original_price": original_price,
        "headings": {"h2": h2s, "h3": h3s}, "content_snippet": content_snippet, "images": images
    }

def scrape_universal_url(url: str, timeout: int = 8) -> dict:
    """CÀO MỌI WEBSITE ĐỐI THỦ: Kết hợp JSON-LD và Heuristic DOM cho bất kỳ CMS nào."""
    if not url: return {"status": "error", "message": "URL rỗng"}
    if not url.startswith("http"): url = "https://" + url
    html = fetch_html(url, timeout=timeout)
    if not html: return {"status": "failed", "url": url, "message": "Không thể kết nối đến website"}

    json_ld = extract_json_ld_data(html)
    heuristic = extract_heuristic_data(html, url)
    domain = url.split("//")[-1].split("/")[0]

    is_product = bool(json_ld.get("product") or heuristic.get("sale_price"))
    if is_product:
        p_data = json_ld.get("product", {})
        title = p_data.get("name") or heuristic.get("title") or "Sản phẩm đối thủ"
        sale_p = p_data.get("sale_price") or heuristic.get("sale_price") or "Liên hệ"
        orig_p = p_data.get("original_price") or heuristic.get("original_price") or ""
        return {
            "status": "success", "type": "product", "url": url, "domain": domain,
            "source_method": "json_ld" if p_data else "heuristic",
            "title": title, "name": title, "price": sale_p, "sale_price": sale_p,
            "original_price": orig_p,
            "price_display": f"{sale_p} (Giá niêm yết: {orig_p})" if orig_p and orig_p != sale_p else f"{sale_p} (Giá thực tế)",
            "headings": heuristic.get("headings", {}),
            "description": p_data.get("description") or heuristic.get("content_snippet", ""),
            "images": p_data.get("images") or heuristic.get("images", []),
            "competitor": domain
        }
    a_data = json_ld.get("article", {})
    title = a_data.get("title") or heuristic.get("title") or "Bài viết đối thủ"
    return {
        "status": "success", "type": "article", "url": url, "domain": domain,
        "source_method": "json_ld" if a_data else "heuristic",
        "title": title, "name": title, "headings": heuristic.get("headings", {}),
        "description": a_data.get("description") or heuristic.get("content_snippet", "")[:300],
        "content_snippet": heuristic.get("content_snippet", ""),
        "images": heuristic.get("images", []), "competitor": domain
    }
