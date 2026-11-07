#!/usr/bin/env python3
"""
Scraper Tool Facade shim for backward compatibility (SOP 23).
Re-exports functionality decomposed into:
  - scraper_core.py: HTTP fetching, price cleaning, Schema JSON-LD & universal scraping
  - scraper_media.py: Logo extraction, watermark detection & clean asset curation
  - scraper_legacy.py: Site-specific crawlers (TCS, Mi Hanoi, Mi Vietnam)
  - scraper_recon.py: Multi-competitor reconnaissance & price benchmark matrix
"""
import json
import sys
from scraper_core import (
    HEADERS,
    HAS_CURL_CFFI,
    fetch_html,
    clean_price_text,
    extract_json_ld_data,
    extract_heuristic_data,
    scrape_universal_url,
)
from scraper_media import (
    extract_website_logo,
    is_watermarked_or_low_quality,
    curate_clean_competitor_images,
)
from scraper_legacy import (
    scrape_tcs_search,
    scrape_mihanoi_search,
    scrape_mivietnam_search,
)
from scraper_recon import scrape_competitor_recon

__all__ = [
    "HEADERS",
    "HAS_CURL_CFFI",
    "fetch_html",
    "clean_price_text",
    "extract_json_ld_data",
    "extract_heuristic_data",
    "scrape_universal_url",
    "extract_website_logo",
    "is_watermarked_or_low_quality",
    "curate_clean_competitor_images",
    "scrape_tcs_search",
    "scrape_mihanoi_search",
    "scrape_mivietnam_search",
    "scrape_competitor_recon",
]

if __name__ == "__main__":
    kw = sys.argv[1] if len(sys.argv) > 1 else ""
    print(f"Bắt đầu trinh sát từ khóa: {kw}...")
    result = scrape_competitor_recon(kw)
    print(json.dumps(result, ensure_ascii=False, indent=2))
