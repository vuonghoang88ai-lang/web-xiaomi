#!/usr/bin/env python3
"""Deep audit batch processing - split from seed_competitor_analyzer.py."""
import logging
from concurrent.futures import ThreadPoolExecutor
from typing import Dict, List, Optional, Any, Union
from bs4 import BeautifulSoup

logger = logging.getLogger("seed_analyzer")

def deep_audit_competitor_batch(competitor_urls: list, tenant_id: str = None, max_workers: int = 5) -> dict:
    """
    Thực thi phân tích chuyên sâu đồng thời cho danh sách 10 đối thủ theo cùng công thức.
    Sử dụng ThreadPoolExecutor song song đa luồng (~8-12 giây cho cả 10 website).
    """
    from concurrent.futures import ThreadPoolExecutor, as_completed
    from seed_orchestrator import analyze_seed_competitor
    results = {}
    with ThreadPoolExecutor(max_workers=max_workers) as executor:
        future_to_url = {
            executor.submit(analyze_seed_competitor, u, 5, tenant_id): u
            for u in competitor_urls if u
        }
        for future in as_completed(future_to_url):
            url = future_to_url[future]
            try:
                res = future.result()
                d = res.get("domain", "")
                if d:
                    results[d] = res
                    results[url] = res
            except Exception as e:
                logger.warning(f"Lỗi phân tích sâu đối thủ {url}: {e}")
    return results



