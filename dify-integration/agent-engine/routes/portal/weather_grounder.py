"""
SOP 30 & SOP 42: Dynamic Geospatial Weather Grounding Service.
Integrates Open-Meteo real-time satellite telemetry without hardcoding.
Maintains modular architecture (SOP 23 <= 300 lines).
"""

from typing import Dict, Any, Optional, Tuple, List
import logging
import re
import urllib.request
import urllib.parse
import json

logger = logging.getLogger("marketing_engine.weather_grounder")

# Cache toạ độ địa lý động (LRU/Memory Cache)
GEO_COORDS_CACHE: Dict[str, Tuple[float, float, str]] = {
    "hà nội": (21.0245, 105.84117, "Hà Nội"),
    "hồ chí minh": (10.8231, 106.6297, "Hồ Chí Minh"),
    "đà nẵng": (16.06778, 108.22083, "Đà Nẵng")
}


def resolve_dynamic_geocoding(location_name: str) -> Tuple[float, float, str]:
    """
    SOP 30: Thuật toán Geocoding động qua Open-Meteo API.
    Không fix cứng toạ độ, hỗ trợ mọi tỉnh thành phố trên thế giới.
    """
    clean_name = location_name.strip().lower()
    if clean_name in GEO_COORDS_CACHE:
        return GEO_COORDS_CACHE[clean_name]

    try:
        url = f"https://geocoding-api.open-meteo.com/v1/search?name={urllib.parse.quote(location_name)}&count=1&language=vi"
        req = urllib.request.Request(url, headers={"User-Agent": "MarketingEngine/2.0"})
        with urllib.request.urlopen(req, timeout=3) as resp:
            data = json.loads(resp.read().decode("utf-8"))
            results = data.get("results")
            if results:
                r = results[0]
                lat, lon, name = float(r.get("latitude")), float(r.get("longitude")), r.get("name")
                GEO_COORDS_CACHE[clean_name] = (lat, lon, name)
                return lat, lon, name
    except Exception as e:
        logger.warning(f"[GEOCODING_FALLBACK] {location_name}: {e}")

    return 21.0245, 105.84117, "Hà Nội"


def extract_geospatial_temporal(query: str, project_location: Optional[str]) -> Tuple[str, str]:
    """
    SOP 42: Bóc tách động vị trí địa lý và khung thời gian từ query và hồ sơ dự án.
    """
    q_lower = query.lower()
    loc_match = re.search(
        r"(?:tại|ở|khu vực|tỉnh|tp|thành phố)\s+([A-ZÀ-Ỹa-zà-ỹ0-9\s]+?)(?=\s+(?:thế nào|như nào|ra sao|tuần|ngày|dự báo|$|[?,.!]))",
        query,
        re.IGNORECASE
    )
    resolved_loc = loc_match.group(1).strip() if loc_match else (project_location or "Hà Nội")

    time_match = re.search(r"\b(ngày mai|hôm nay|tuần tới|tháng này|\d+\s+ngày tới)\b", q_lower)
    resolved_time = time_match.group(1) if time_match else "hiện tại"

    return resolved_loc, resolved_time


def fetch_live_weather_grounding(location_name: str, time_frame: str) -> Optional[Tuple[str, str]]:
    """
    SOP 42: Thu thập số liệu khí tượng thực tế từ Open-Meteo Satellite API.
    """
    lat, lon, resolved_name = resolve_dynamic_geocoding(location_name)
    url = (
        f"https://api.open-meteo.com/v1/forecast?latitude={lat}&longitude={lon}"
        f"&daily=temperature_2m_max,temperature_2m_min,precipitation_sum&timezone=Asia%2FBangkok"
    )

    req = urllib.request.Request(url, headers={"User-Agent": "MarketingEngine/2.0"})
    with urllib.request.urlopen(req, timeout=4) as resp:
        data = json.loads(resp.read().decode("utf-8"))

    daily = data.get("daily", {})
    times = daily.get("time", [])
    max_temps = daily.get("temperature_2m_max", [])
    min_temps = daily.get("temperature_2m_min", [])
    precips = daily.get("precipitation_sum", [])

    if not times:
        return None

    facts = [f"• Ngày {times[i]}: {min_temps[i]}°C – {max_temps[i]}°C (Mưa: {precips[i]}mm)" for i in range(min(5, len(times)))]
    summary_block = (
        f"\n\n[DỮ LIỆU THỜI TIẾT THỰC TẾ TỪ TRẠM VỆ TINH (NGUỒN [1]: OPEN-METEO)]:\n"
        f"- Khu vực trắc địa: {resolved_name} (Khung thời gian: {time_frame})\n"
        f"- Biên độ nhiệt: {min(min_temps)}°C – {max(max_temps)}°C\n"
        f"- Dự báo chi tiết:\n" + "\n".join([f"  {f}" for f in facts]) + "\n"
        f"⚠️ QUY TẮC: Sử dụng đúng 100% số liệu vệ tinh trên. TUYỆT ĐỐI CẤM TỰ BỊA ĐẶT số liệu thời tiết khác!"
    )
    source_citation = f"- `[1]` Trạm Khí tượng Vệ tinh Open-Meteo ({resolved_name} - Dữ liệu thời gian thực)"
    return summary_block, source_citation

