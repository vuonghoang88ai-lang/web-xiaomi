#!/usr/bin/env python3
"""Multi-competitor reconnaissance and price benchmark matrix analysis."""
import re
import sys
from scraper_core import scrape_universal_url

def _resolve(attr_name, fallback_fn):
    mod = sys.modules.get("scraper_tool")
    if mod and hasattr(mod, attr_name):
        return getattr(mod, attr_name)
    return fallback_fn

def scrape_competitor_recon(keyword: str, custom_competitor_urls: list = None) -> dict:
    """
    Hàm trinh sát tổng hợp ĐA ĐỐI THỦ (Multi-Competitor Reconnaissance):
    1. Hỗ trợ truyền custom_competitor_urls (cho bất kỳ ngành nghề nào ngoài Xiaomi).
    2. Nếu không có custom_competitor_urls, trả no_results; URL đối thủ phải đến từ SERP hoặc người dùng cung cấp.
    3. Hợp nhất ma trận giá (Price Matrix) & thông số để bù đắp các khoảng trống dữ liệu.
    """
    all_products = []
    competitor_sources = []
    universal_fn = _resolve("scrape_universal_url", scrape_universal_url)

    # Nếu có danh sách URL đối thủ tùy chỉnh (Multi-Tenant Project)
    if custom_competitor_urls:
        for c_url in custom_competitor_urls[:6]:
            scraped_res = universal_fn(c_url)
            if scraped_res.get("status") == "success":
                all_products.append(scraped_res)
                dom = scraped_res.get("domain", c_url)
                if dom not in competitor_sources:
                    competitor_sources.append(dom)

    # Bóc tách ma trận so sánh giá đa nguồn (Price Benchmark)
    price_matrix = []
    lowest_price_num = float('inf')
    lowest_price_str = ""

    for p in all_products:
        sp_text = p.get("sale_price", "")
        clean_num = re.sub(r"[^\d]", "", sp_text)
        if clean_num.isdigit():
            val = int(clean_num)
            price_matrix.append({
                "competitor": p.get("competitor"),
                "name": p.get("name"),
                "price": sp_text,
                "price_num": val
            })
            if 0 < val < lowest_price_num:
                lowest_price_num = val
                lowest_price_str = sp_text

    if not all_products:
        return {
            "status": "no_results",
            "keyword": keyword,
            "competitor_sources": competitor_sources,
            "found_count": 0,
            "fallback_used": False,
            "lowest_market_price": "",
            "recommended_price": "",
            "products": [],
            "summary": "Không thu thập được dữ liệu giá thực tế; không có dữ liệu thay thế hoặc giá suy đoán.",
        }

    return {
        "status": "success",
        "keyword": keyword,
        "competitor_sources": competitor_sources,
        "found_count": len(all_products),
        "fallback_used": False,
        "lowest_market_price": lowest_price_str,
        "recommended_price": "",
        "products": all_products,
        "summary": (
            f"Trinh sát thành công {len(all_products)} sản phẩm từ {len(competitor_sources)} "
            f"đối thủ cạnh tranh ({', '.join(competitor_sources)}). Hợp nhất ma trận giá thị trường để bù đắp thông số thiếu sót."
        )
    }
