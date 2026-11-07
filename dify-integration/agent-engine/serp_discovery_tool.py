#!/usr/bin/env python3
"""
Generalized Onboarding & Top 10 Competitor Discovery Engine Facade (SOP 23 compliant <= 300 lines).
Decomposed into:
  - serp_site_inspector.py: URL normalization, logo extraction & site inspection
  - serp_crawler.py: Serper / DuckDuckGo SERP query & Top 10 discovery
  - serp_uvp_analyzer.py: LLM router call & UVP generation/optimization
  - serp_onboarding.py: Full onboarding gap analysis & persistence
"""
import sys
import json
import subprocess
import urllib.request
import urllib.parse

import db_service
import rag_service

from serp_site_inspector import (
    SerpDiscoveryError,
    GLOBAL_EXCLUDED_DOMAINS,
    is_excluded_competitor_domain,
    normalize_website_url,
    check_url_online,
    extract_website_logo,
    inspect_website,
)

from serp_crawler import (
    get_latest_paa_questions,
    fetch_serper_results,
    _fetch_duckduckgo_results,
    _fetch_serp_results,
    discover_top_10_competitors,
)

from serp_uvp_analyzer import (
    call_llm,
    generate_uvp_suggestions,
    analyze_and_optimize_user_uvp,
)

from serp_onboarding import (
    run_onboarding_gap_analysis,
)

__all__ = [
    "subprocess",
    "urllib",
    "db_service",
    "rag_service",
    "SerpDiscoveryError",
    "GLOBAL_EXCLUDED_DOMAINS",
    "is_excluded_competitor_domain",
    "normalize_website_url",
    "check_url_online",
    "extract_website_logo",
    "inspect_website",
    "get_latest_paa_questions",
    "fetch_serper_results",
    "_fetch_duckduckgo_results",
    "_fetch_serp_results",
    "discover_top_10_competitors",
    "call_llm",
    "generate_uvp_suggestions",
    "analyze_and_optimize_user_uvp",
    "run_onboarding_gap_analysis",
]

if __name__ == "__main__":
    if len(sys.argv) < 4:
        raise SystemExit("Usage: serp_discovery_tool.py <target_url> <business_description> <tenant_uuid>")
    test_result = run_onboarding_gap_analysis(
        target_website_url=sys.argv[1],
        business_description=sys.argv[2],
        tenant_id=sys.argv[3],
        seed_competitor_url=None,
        target_location="",
        core_strengths=""
    )
    print("\nBÁO CÁO CHIẾN LƯỢC TỪ CMO:")
    print(test_result["strategic_report"][:1200] + "\n...")
    print(f"\nProject ID: {test_result.get('project_id')}")
    print(f"RAG Dataset ID: {test_result.get('dify_dataset_id')}")
    print(f"Gợi ý UVP: {json.dumps(test_result.get('suggested_uvp_options'), ensure_ascii=False, indent=2)}")
