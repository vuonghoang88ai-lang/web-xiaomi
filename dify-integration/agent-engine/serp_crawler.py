#!/usr/bin/env python3
"""Google & DuckDuckGo SERP querying, Serper API rotation, PAA extraction."""
import os
import re
import json
import logging
import urllib.request
import urllib.parse
from concurrent.futures import ThreadPoolExecutor
from datetime import datetime, timezone
from typing import Optional, List
from bs4 import BeautifulSoup

import serp_site_inspector
from serp_site_inspector import (
    normalize_website_url,
    is_excluded_competitor_domain,
    check_url_online,
    SerpDiscoveryError,
)

logger = logging.getLogger("serp_discovery")

_SERPER_KEY_INDEX = 0
_LATEST_PAA_QUESTIONS = []

def get_latest_paa_questions(clear: bool = False) -> list:
    """Trả về danh sách câu hỏi People Also Ask (PAA) thu thập được từ SERP."""
    global _LATEST_PAA_QUESTIONS
    res = list(_LATEST_PAA_QUESTIONS)
    if clear:
        _LATEST_PAA_QUESTIONS = []
    return res

def fetch_serper_results(query: str, tenant_id: str = None, timeout: int = 12) -> Optional[list]:
    """Truy vấn Google SERP qua Serper.dev: bóc tách paid_ads và organic_seo kèm xoay vòng API Keys."""
    global _SERPER_KEY_INDEX, _LATEST_PAA_QUESTIONS
    keys = []
    gl = "vn"
    hl = "vi"

    try:
        import db_service
        cfg = db_service.get_system_setting("serp_config", tenant_id) or {}
        raw_keys = cfg.get("serper_api_keys", "")
        if isinstance(raw_keys, list):
            keys = [k.strip() for k in raw_keys if k and k.strip()]
        elif isinstance(raw_keys, str):
            keys = [k.strip() for k in re.split(r"[,;\n\r]+", raw_keys) if k.strip()]
        gl = cfg.get("gl") or "vn"
        hl = cfg.get("hl") or "vi"
    except Exception as e:
        logger.warning(f"Không đọc được serp_config từ DB: {e}")

    if not keys:
        env_keys = os.environ.get("SERPER_API_KEYS", os.environ.get("SERPER_API_KEY", ""))
        keys = [k.strip() for k in re.split(r"[,;\n\r]+", env_keys) if k.strip()]

    if not keys:
        return None

    attempts = len(keys)
    for _ in range(attempts):
        active_key = keys[_SERPER_KEY_INDEX % len(keys)]
        payload = {"q": query, "gl": gl, "hl": hl}
        req = urllib.request.Request(
            "https://google.serper.dev/search",
            data=json.dumps(payload).encode("utf-8"),
            headers={"X-API-KEY": active_key, "Content-Type": "application/json"}
        )

        try:
            with urllib.request.urlopen(req, timeout=timeout) as response:
                if response.status == 200:
                    data = json.loads(response.read().decode("utf-8"))
                    results = []
                    seen_domains = set()

                    raw_paa = data.get("peopleAlsoAsk") or []
                    for item in raw_paa:
                        q_text = (item.get("question") or "").strip()
                        if q_text and not any(x.get("question") == q_text for x in _LATEST_PAA_QUESTIONS):
                            _LATEST_PAA_QUESTIONS.append({
                                "question": q_text,
                                "snippet": (item.get("snippet") or "").strip(),
                                "title": (item.get("title") or "").strip(),
                                "link": item.get("link", "")
                            })
                    if len(_LATEST_PAA_QUESTIONS) > 30:
                        _LATEST_PAA_QUESTIONS = _LATEST_PAA_QUESTIONS[-30:]

                    # 1. BÓC TÁCH GOOGLE ADS
                    raw_ads = data.get("ads") or []
                    for pos, ad in enumerate(raw_ads, start=1):
                        href = ad.get("link", "")
                        try:
                            href = normalize_website_url(href)
                        except ValueError:
                            continue
                        host = (urllib.parse.urlsplit(href).hostname or "").lower()
                        domain = host.removeprefix("www.")
                        headline = ad.get("title", "")
                        snippet = ad.get("snippet", "")
                        if not domain or domain in seen_domains or is_excluded_competitor_domain(domain, context=query, title=headline, snippet=snippet):
                            continue
                        seen_domains.add(domain)
                        sitelinks = [s.get("title", "") for s in ad.get("sitelinks", [])] if ad.get("sitelinks") else []

                        results.append({
                            "url": href,
                            "domain": domain,
                            "brand_name": headline or domain,
                            "title": headline,
                            "serp_snippet": snippet,
                            "source": "google_ads",
                            "rank": pos,
                            "is_ad": True,
                            "competitor_type": "paid_ads",
                            "ad_details": {
                                "headline": headline,
                                "snippet": snippet,
                                "position": pos,
                                "sitelinks": sitelinks
                            }
                        })

                    # 2. BÓC TÁCH ORGANIC SEO
                    raw_organic = data.get("organic") or []
                    for pos, org in enumerate(raw_organic, start=1):
                        href = org.get("link", "")
                        try:
                            href = normalize_website_url(href)
                        except ValueError:
                            continue
                        host = (urllib.parse.urlsplit(href).hostname or "").lower()
                        domain = host.removeprefix("www.")
                        title = org.get("title", "")
                        snippet = org.get("snippet", "")
                        if not domain or domain in seen_domains or is_excluded_competitor_domain(domain, context=query, title=title, snippet=snippet):
                            continue
                        seen_domains.add(domain)

                        results.append({
                            "url": href,
                            "domain": domain,
                            "brand_name": title or domain,
                            "title": title,
                            "serp_snippet": snippet,
                            "source": "google_organic",
                            "rank": pos,
                            "is_ad": False,
                            "competitor_type": "organic_seo",
                            "ad_details": {}
                        })
                        if len(results) >= 15:
                            break

                    logger.info(f"[SERPER_OK] Đã bóc tách {len(raw_ads)} Ads và {len(raw_organic)} Organic cho: '{query}'")
                    return results

        except urllib.error.HTTPError as he:
            if he.code in (403, 429):
                logger.warning(f"⚠️ Serper key index {_SERPER_KEY_INDEX} lỗi HTTP {he.code}. Xoay sang key tiếp theo...")
                _SERPER_KEY_INDEX = (_SERPER_KEY_INDEX + 1) % len(keys)
                continue
            else:
                logger.warning(f"Lỗi Serper HTTP {he.code}: {he}")
                break
        except Exception as exc:
            logger.warning(f"Lỗi kết nối Serper.dev: {exc}")
            break

    return None

def _fetch_duckduckgo_results(query: str, timeout: int = 12) -> list:
    """Truy vấn DuckDuckGo HTML miễn phí làm cơ chế dự phòng."""
    search_url = "https://html.duckduckgo.com/html/?" + urllib.parse.urlencode({"q": query})
    request = urllib.request.Request(
        search_url,
        headers={
            "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36",
            "Accept-Language": "vi-VN,vi;q=0.9,en;q=0.8",
        },
    )
    try:
        # Check if serp_discovery_tool.urllib.request is mocked
        import serp_discovery_tool
        opener = getattr(serp_discovery_tool.urllib.request, "urlopen", urllib.request.urlopen)
        with opener(request, timeout=timeout) as response:
            html = response.read().decode("utf-8", errors="replace")
    except Exception as exc:
        logger.warning(f"Không truy vấn được DuckDuckGo SERP trực tiếp: {exc}")
        return []

    soup = BeautifulSoup(html, "html.parser")
    results = []
    seen_domains = set()

    for position, anchor in enumerate(soup.select("a.result__a"), start=1):
        href = anchor.get("href", "")
        parsed_href = urllib.parse.urlsplit(href)
        if parsed_href.hostname and parsed_href.hostname.endswith("duckduckgo.com"):
            href = urllib.parse.parse_qs(parsed_href.query).get("uddg", [""])[0]
        try:
            href = normalize_website_url(href)
        except ValueError:
            continue

        hostname = (urllib.parse.urlsplit(href).hostname or "").lower()
        domain = hostname.removeprefix("www.")
        result_container = anchor.find_parent(class_="result")
        snippet_tag = result_container.select_one(".result__snippet") if result_container else None
        b_name = anchor.get_text(" ", strip=True) or domain
        snip_txt = snippet_tag.get_text(" ", strip=True) if snippet_tag else ""
        if not domain or domain in seen_domains or is_excluded_competitor_domain(domain, context=query, title=b_name, snippet=snip_txt):
            continue
        seen_domains.add(domain)

        results.append({
            "url": href,
            "domain": domain,
            "brand_name": b_name,
            "title": b_name,
            "serp_snippet": snip_txt,
            "source": "duckduckgo_html",
            "rank": position,
            "is_ad": False,
            "competitor_type": "organic_seo",
            "ad_details": {}
        })
        if len(results) == 10:
            break
    return results

def _fetch_serp_results(query: str, timeout: int = 12, tenant_id: str = None) -> list:
    """Truy vấn SERP: Ưu tiên Serper.dev (Google Ads + SEO), tự động fallback sang DuckDuckGo."""
    serper_data = fetch_serper_results(query, tenant_id=tenant_id, timeout=timeout)
    if serper_data:
        return serper_data
    return _fetch_duckduckgo_results(query, timeout=timeout)

def discover_top_10_competitors(
    business_desc: str,
    seed_url: str = None,
    location: str = "",
    exclude_url: str = None,
    only_online: bool = False
) -> list:
    """Return organic results found on DuckDuckGo and individually checked."""
    import serp_discovery_tool
    fetch_fn = getattr(serp_discovery_tool, "_fetch_serp_results", _fetch_serp_results)
    check_fn = getattr(serp_discovery_tool, "check_url_online", check_url_online)

    query = f"{business_desc} {location}".strip()
    results = fetch_fn(query)

    if exclude_url and results:
        try:
            norm_exc = normalize_website_url(exclude_url)
            exc_host = (urllib.parse.urlsplit(norm_exc).hostname or "").lower().removeprefix("www.")
            exc_root = exc_host.split(".")[0]
            results = [
                r for r in results
                if r.get("domain") != exc_host and not (exc_root and len(exc_root) > 4 and exc_root in r.get("domain", ""))
            ]
        except Exception as e:
            logger.warning(f"Lỗi parse exclude_url: {e}")

    if not results:
        raise SerpDiscoveryError(f"Không tìm thấy kết quả SERP thực tế nào từ DuckDuckGo cho từ khóa: '{query}'")

    with ThreadPoolExecutor(max_workers=5) as executor:
        online_results = list(executor.map(lambda item: check_fn(item["url"]), results))

    verified = []
    checked_at = datetime.now(timezone.utc).isoformat()
    for item, is_online in zip(results, online_results):
        if only_online and not is_online:
            continue
        verified.append({
            **item,
            "status": "online" if is_online else "offline",
            "is_online": is_online,
            "verified_at": checked_at,
            "core_strength": item.get("serp_snippet", ""),
            "vulnerability": "",
            "rank": len(verified) + 1 if only_online else item.get("rank", len(verified) + 1),
        })
        if len(verified) == 10:
            break

    return verified
