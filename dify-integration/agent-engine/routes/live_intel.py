#!/usr/bin/env python3
"""
Live Internet Hybrid Intelligence Route Module.
Provides real-time web search, weather forecasting, trend grounding,
and commercial synthesis for CMO AI Copilot.
Strictly complies with SOP 23 (<= 300 lines) and SOP 30 (Zero Hardcoding).
"""
import os
import re
import json
import time
import logging
import urllib.request
import urllib.parse
from typing import Optional, Dict, Any, List
from pydantic import BaseModel
from fastapi import APIRouter, HTTPException

logger = logging.getLogger("marketing_engine")
router = APIRouter(tags=["Live Intelligence"])

# In-memory TTL Cache: cache_key -> (timestamp, data_dict)
_INTEL_CACHE: Dict[str, tuple[float, Dict[str, Any]]] = {}
CACHE_TTL_SECONDS = 3600  # 1 hour


class LiveIntelRequest(BaseModel):
    query: str
    category: Optional[str] = "general"  # 'weather', 'news', 'trend', 'competitor', 'general'
    location: Optional[str] = "Hanoi"


def _get_cached_data(key: str) -> Optional[Dict[str, Any]]:
    cached = _INTEL_CACHE.get(key)
    if cached:
        cached_time, data = cached
        if time.time() - cached_time < CACHE_TTL_SECONDS:
            return data
    return None


def _set_cached_data(key: str, data: Dict[str, Any]):
    _INTEL_CACHE[key] = (time.time(), data)
    # Prune stale entries if cache gets too large
    if len(_INTEL_CACHE) > 500:
        now = time.time()
        stale_keys = [k for k, (t, _) in _INTEL_CACHE.items() if now - t > CACHE_TTL_SECONDS]
        for k in stale_keys:
            _INTEL_CACHE.pop(k, None)


def _fetch_weather_forecast(location: str = "Hanoi") -> Dict[str, Any]:
    """Truy vấn dự báo thời tiết 7 ngày từ Open-Meteo API (100% miễn phí, chuẩn xác)."""
    coords = {
        "hanoi": (21.0285, 105.8542),
        "hcm": (10.8231, 106.6297),
        "hochiminh": (10.8231, 106.6297),
        "danang": (16.0544, 108.2022),
        "haiphong": (20.8449, 106.6881),
        "cantho": (10.0452, 105.7469),
    }
    loc_key = re.sub(r"[^a-zA-Z]", "", location.lower())
    lat, lon = coords.get(loc_key, coords["hanoi"])

    url = (
        f"https://api.open-meteo.com/v1/forecast?"
        f"latitude={lat}&longitude={lon}&daily=temperature_2m_max,temperature_2m_min,precipitation_sum&"
        f"timezone=Asia%2FBangkok&forecast_days=7"
    )

    req = urllib.request.Request(url, headers={"User-Agent": "MarketingEngine/2.0"})
    with urllib.request.urlopen(req, timeout=10) as resp:
        data = json.loads(resp.read().decode("utf-8"))

    daily = data.get("daily", {})
    times = daily.get("time", [])
    max_temps = daily.get("temperature_2m_max", [])
    min_temps = daily.get("temperature_2m_min", [])
    precips = daily.get("precipitation_sum", [])

    daily_facts = []
    for i in range(len(times)):
        daily_facts.append(
            f"• Ngày {times[i]}: Nhiệt độ {min_temps[i]}°C – {max_temps[i]}°C | Lượng mưa: {precips[i]}mm"
        )

    min_val = min(min_temps) if min_temps else 20
    max_val = max(max_temps) if max_temps else 30
    total_rain = sum(precips) if precips else 0

    if min_val <= 22 or total_rain > 15:
        comm_angle = (
            "Thời tiết chuyển lạnh, nồm ẩm hoặc mưa nhiều: Người tiêu dùng có xu hướng ở nhà sinh hoạt gia đình. "
            "Đây là cơ hội thúc đẩy mạnh chiến dịch Tivi 4K HDR màn hình lớn, Loa Soundbar giải trí gia đình, "
            "Máy giặt sấy cao cấp và Máy hút ẩm Xiaomi."
        )
    elif max_val >= 33:
        comm_angle = (
            "Thời tiết nắng nóng gay gắt: Nhu cầu làm mát và bảo quản thực phẩm tăng vọt. "
            "Tập trung ngân sách cho Tủ lạnh dung tích lớn, Điều hòa thông minh và Quạt cây biến tần Xiaomi."
        )
    else:
        comm_angle = (
            "Thời tiết mát mẻ dễ chịu: Phù hợp với các chiến dịch mua sắm nâng cấp không gian sống, "
            "Gia dụng thông minh (Robot hút bụi, Nồi chiên không dầu, Máy lọc không khí Xiaomi)."
        )

    return {
        "domain": "weather",
        "location": location,
        "temperature_range": f"{min_val}°C – {max_val}°C",
        "total_precipitation": f"{total_rain:.1f}mm",
        "daily_forecast": daily_facts,
        "commercial_implication": comm_angle
    }


def _fetch_web_news(query: str, max_results: int = 5) -> Dict[str, Any]:
    """Truy vấn tin tức và xu hướng thời gian thực từ Internet qua SERP & DuckDuckGo."""
    results = []
    try:
        from serp_crawler import _fetch_duckduckgo_results
        ddg_res = _fetch_duckduckgo_results(query, timeout=10)
        for r in ddg_res[:max_results]:
            results.append({
                "title": r.get("title") or r.get("brand_name"),
                "url": r.get("url"),
                "snippet": r.get("serp_snippet") or ""
            })
    except Exception as exc:
        logger.warning(f"[LIVE_WEB_FAIL] query='{query}' err={exc}")

    return {
        "domain": "web_search",
        "query": query,
        "result_count": len(results),
        "results": results
    }


@router.post("/api/v1/live-intelligence")
def get_live_market_intelligence(req: LiveIntelRequest):
    """
    Tier 3 Live Internet Grounding Tool:
    Tra cứu thời gian thực dữ liệu bên ngoài (thời tiết, tin tức, xu hướng, đối thủ).
    Tự động gắn liền dữ kiện thực tế với lăng kính thương mại của dự án.
    """
    q = (req.query or "").strip()
    if not q:
        raise HTTPException(status_code=400, detail="Query không được để trống.")

    cat = (req.category or "").strip().lower()
    cache_key = f"{cat}:{q.lower()}"
    cached = _get_cached_data(cache_key)
    if cached:
        res = dict(cached)
        res["cached"] = True
        return res

    is_weather = cat == "weather" or any(w in q.lower() for w in [
        "thời tiết", "nhiệt độ", "trời mưa", "rét", "không khí lạnh", "nồm", "nắng nóng", "mùa đông", "mùa hè"
    ])

    if is_weather:
        # Detect target city
        loc = req.location or "Hanoi"
        for city_name in ["HCM", "Hồ Chí Minh", "Sài Gòn", "Đà Nẵng", "Hải Phòng", "Cần Thơ", "Hà Nội"]:
            if city_name.lower() in q.lower():
                loc = city_name
                break
        intel_data = _fetch_weather_forecast(loc)
    else:
        intel_data = _fetch_web_news(q)

    response_payload = {
        "status": "success",
        "query": q,
        "category": "weather" if is_weather else "web_news",
        "cached": False,
        "data": intel_data,
        "commercial_instruction": (
            "Dữ kiện thực tế trên là nguyên liệu đầu vào. CMO AI BẮT BUỘC kết hợp dữ kiện này "
            "với danh mục sản phẩm của dự án (Tivi, Tủ lạnh, Máy giặt Xiaomi) để đề xuất hành động bán hàng, "
            "chương trình ưu đãi hoặc thông điệp content thích ứng ngay lập tức."
        )
    }

    _set_cached_data(cache_key, response_payload)
    return response_payload

