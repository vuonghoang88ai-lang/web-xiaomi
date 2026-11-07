"""
SOP 35: Dynamic Tool Budgeting & Payload Condensation Engine.
Lọc thô dữ liệu đối thủ SERP & Live Intelligence, đảm bảo payload gửi cho LLM
luôn nằm trong ngân sách an toàn (<= 1.500 tokens / 6.000 ký tự) để triệt tiêu lỗi Timeout 300s.
"""

from typing import Any, Dict, List, Optional
import json
import logging

logger = logging.getLogger("marketing_engine.tool_budgeter")

# Ngân sách payload an toàn cho LLM
MAX_PAYLOAD_CHARS = 5500
MAX_COMPETITORS_COUNT = 5


def condense_competitor_data(
    raw_data: Dict[str, Any],
    max_chars: int = MAX_PAYLOAD_CHARS,
    max_items: int = MAX_COMPETITORS_COUNT
) -> Dict[str, Any]:
    """
    Thuật toán tinh lọc dữ liệu đối thủ (Extractive Condensation):
    - Giữ lại top đối thủ có thứ hạng cao nhất (Top SERP / Organic / Ads).
    - Lọc bỏ metadata dư thừa, thẻ HTML, snippet quá dài.
    - Đảm bảo payload xuất ra siêu nhẹ, trực diện cho AI viết bài hoặc so sánh giá.
    """
    if not isinstance(raw_data, dict):
        return {"summary": "Không có dữ liệu đối thủ hợp lệ."}

    keyword = raw_data.get("keyword", "")
    discovered = raw_data.get("discovered_competitors") or []
    products = raw_data.get("products") or []
    sources = raw_data.get("competitor_sources") or []

    # 1. Tinh lọc danh sách đối thủ phát hiện
    condensed_competitors: List[Dict[str, Any]] = []
    for item in discovered[:max_items]:
        if not isinstance(item, dict):
            continue
        domain = item.get("domain") or item.get("brand_name") or ""
        condensed_competitors.append({
            "domain": domain,
            "rank": item.get("rank", 0),
            "title": (item.get("title") or "")[:90],
            "core_strength": (item.get("core_strength") or "")[:120],
            "vulnerability": (item.get("vulnerability") or "")[:120],
            "type": item.get("competitor_type", "organic_seo"),
            "url": item.get("url") or ""
        })

    # 2. Tinh lọc sản phẩm & giá thị trường
    condensed_products: List[Dict[str, Any]] = []
    for p in products[:max_items]:
        if not isinstance(p, dict):
            continue
        condensed_products.append({
            "title": (p.get("title") or "")[:90],
            "price": p.get("price") or p.get("price_raw") or "Liên hệ",
            "source": p.get("source") or p.get("domain") or ""
        })

    # 3. Tổng hợp cấu trúc tinh gọn
    lowest_price = raw_data.get("lowest_market_price") or "Chưa rõ"
    rec_price = raw_data.get("recommended_price") or "Chưa rõ"

    condensed_result = {
        "status": "success",
        "keyword": keyword,
        "market_price_overview": {
            "lowest_market_price": lowest_price,
            "recommended_price": rec_price
        },
        "top_verified_competitors": condensed_competitors,
        "sample_market_products": condensed_products,
        "active_domains": sources[:max_items],
        "summary": (
            f"Trinh sát {len(condensed_competitors)} đối thủ SERP hàng đầu cho '{keyword}'. "
            f"Giá sàn thị trường: {lowest_price}."
        )
    }

    # 4. Kiểm định trần ký tự ngân sách
    dumped = json.dumps(condensed_result, ensure_ascii=False)
    if len(dumped) > max_chars:
        # Cắt bớt sản phẩm nếu vượt ngưỡng
        condensed_result["sample_market_products"] = condensed_products[:2]
        condensed_result["top_verified_competitors"] = condensed_competitors[:3]
        logger.info(f"[TOOL_BUDGETER] Payload trimmed to safe ceiling ({len(dumped)} -> {max_chars} chars)")

    return condensed_result


def condense_live_intel_payload(intel_data: Dict[str, Any], max_chars: int = 3500) -> Dict[str, Any]:
    """
    Thuật toán tinh lọc dữ liệu Internet thời gian thực (Live Intel):
    Đảm bảo các bản tin tức hoặc thời tiết không gây nghẽn context của Agent.
    """
    if not isinstance(intel_data, dict):
        return {"status": "empty"}

    category = intel_data.get("category", "general")
    query = intel_data.get("query", "")
    data = intel_data.get("data", {})

    clean_data = {}
    if category == "weather":
        clean_data = {
            "location": data.get("location"),
            "current_temp": data.get("current_temp"),
            "condition": data.get("condition"),
            "daily_forecast": (data.get("daily_forecast") or [])[:5],
            "commercial_retail_insight": data.get("commercial_retail_insight", "")[:400]
        }
    else:
        # News / Trends
        articles = data.get("articles") or []
        clean_articles = []
        for art in articles[:4]:
            clean_articles.append({
                "title": (art.get("title") or "")[:100],
                "snippet": (art.get("snippet") or "")[:200],
                "source": art.get("source") or ""
            })
        clean_data = {
            "articles": clean_articles,
            "market_angle": data.get("market_angle", "")[:300]
        }

    return {
        "status": "success",
        "category": category,
        "query": query,
        "data": clean_data
    }

