#!/usr/bin/env python3
"""Site-specific scrapers for legacy Xiaomi competitor portals (TCS, Mi Hanoi, Mi Vietnam)."""
import re
from urllib.parse import quote
from bs4 import BeautifulSoup
from scraper_core import fetch_html, clean_price_text

def scrape_tcs_search(keyword: str) -> dict:
    """Cào kết quả tìm kiếm và giá bán từ Cửa Hàng TCS (cuahangtcs.com)."""
    search_url = f"https://cuahangtcs.com/search?q={quote(keyword)}"
    html = fetch_html(search_url)
    if not html:
        return {"source": "cuahangtcs.com", "status": "failed", "products": []}

    soup = BeautifulSoup(html, "html.parser")
    products = []
    for title_tag in soup.find_all("p", class_="txt-title")[:6]:
        link_tag = title_tag.find("a")
        if link_tag:
            title = link_tag.get_text(strip=True)
            link = link_tag.get("href", "")
            if link and not link.startswith("http"):
                link = "https://cuahangtcs.com" + link
            price_tag = title_tag.find_next_sibling("p", class_="txt-des")
            raw_price = price_tag.get_text(strip=True) if price_tag else "Liên hệ"
            sale_price = clean_price_text(raw_price)
            if title and len(title) > 3:
                products.append({
                    "name": title, "price": sale_price, "sale_price": sale_price,
                    "original_price": "", "price_display": f"{sale_price} (Giá khuyến mãi)",
                    "url": link, "competitor": "cuahangtcs.com"
                })
    return {"source": "cuahangtcs.com", "status": "success" if products else "empty", "search_url": search_url, "products": products}

def scrape_mihanoi_search(keyword: str) -> dict:
    """Cào sản phẩm từ Mi Hà Nội (mihanoi.vn) bóc tách thẻ ins và del."""
    kw_lower = keyword.lower()
    target_urls = [f"https://mihanoi.vn/?s={quote(keyword)}"]
    if "tivi" in kw_lower: target_urls.insert(0, "https://mihanoi.vn/danh-muc/nha-cua-doi-song-thoi-trang/tivi-xiaomi/")
    elif "robot" in kw_lower or "hút bụi" in kw_lower: target_urls.insert(0, "https://mihanoi.vn/danh-muc/thiet-bi-ve-sinh-nha-cua/robot-hut-bui/")
    elif "tủ lạnh" in kw_lower: target_urls.insert(0, "https://mihanoi.vn/danh-muc/thiet-bi-gia-dung/tu-lanh/")
    elif "lọc" in kw_lower or "không khí" in kw_lower: target_urls.insert(0, "https://mihanoi.vn/danh-muc/thiet-bi-gia-dung/may-loc-khong-khi/")

    products = []
    seen_names = set()
    for url in target_urls:
        html = fetch_html(url)
        if not html: continue
        soup = BeautifulSoup(html, "html.parser")
        for item in soup.find_all("div", class_=re.compile(r"box-text-products|product-small|product", re.I)):
            t_tag = item.find(["p", "h3", "h4", "a"], class_=re.compile(r"product-title|name|title", re.I))
            if not t_tag: continue
            name = t_tag.get_text(strip=True)
            if not name or len(name) < 8 or name.lower() in ["kích thước tivi", "xem thêm", "bộ lọc"] or name in seen_names: continue
            link_tag = t_tag.find("a") if t_tag.name != "a" else t_tag
            prod_url = link_tag.get("href", "") if link_tag else url

            price_wrap = item.find(["div", "span"], class_=re.compile(r"price-wrapper|price", re.I))
            sale_price = "Liên hệ"
            original_price = ""
            if price_wrap:
                ins_tag = price_wrap.find("ins")
                del_tag = price_wrap.find("del")
                if ins_tag: sale_price = clean_price_text(ins_tag.get_text(strip=True))
                if del_tag: original_price = clean_price_text(del_tag.get_text(strip=True))
                if not ins_tag:
                    amount_tag = price_wrap.find(class_=re.compile(r"amount|price", re.I))
                    if amount_tag: sale_price = clean_price_text(amount_tag.get_text(strip=True))

            price_display = f"{sale_price} (Giá niêm yết: {original_price} - Đang KM)" if original_price and original_price != sale_price else f"{sale_price} (Giá bán thực tế)"
            seen_names.add(name)
            products.append({
                "name": name, "price": sale_price, "sale_price": sale_price, "original_price": original_price,
                "price_display": price_display, "url": prod_url, "competitor": "mihanoi.vn"
            })
            if len(products) >= 6: break
        if products: break
    return {"source": "mihanoi.vn", "status": "success" if products else "empty", "target_url": target_urls[0], "products": products}

def scrape_mivietnam_search(keyword: str) -> dict:
    """Cào sản phẩm từ Mi Việt Nam (mivietnam.vn)."""
    search_url = f"https://mivietnam.vn/?s={quote(keyword)}"
    html = fetch_html(search_url, timeout=6)
    if not html: return {"source": "mivietnam.vn", "status": "failed", "products": []}

    soup = BeautifulSoup(html, "html.parser")
    products = []
    for item in soup.find_all("div", class_=re.compile(r"product-small|product", re.I))[:4]:
        t_tag = item.find(["h3", "p", "a"], class_=re.compile(r"title|name", re.I))
        if not t_tag: continue
        name = t_tag.get_text(strip=True)
        link = t_tag.get("href", "") if t_tag.name == "a" else (t_tag.find("a").get("href", "") if t_tag.find("a") else "")
        p_tag = item.find(["span", "div"], class_=re.compile(r"price|amount", re.I))
        sale_price = clean_price_text(p_tag.get_text(strip=True) if p_tag else "Liên hệ")
        if name and len(name) > 3:
            products.append({
                "name": name, "price": sale_price, "sale_price": sale_price, "original_price": "",
                "price_display": f"{sale_price} (Giá bán thực tế)", "url": link, "competitor": "mivietnam.vn"
            })
    return {"source": "mivietnam.vn", "status": "success" if products else "empty", "search_url": search_url, "products": products}
