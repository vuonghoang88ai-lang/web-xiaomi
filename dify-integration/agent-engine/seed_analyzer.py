#!/usr/bin/env python3
"""
Module Phân Tích & Giải Mã Đối Thủ Mẫu (Seed Competitor Analyzer)

NOTE: Đã được refactor từ bản gốc monolithic (2054 dòng) sang cấu trúc module:
  - seed_utils.py: Utility functions, constants
  - seed_site_analyzer.py: On-page & site structure analysis
  - seed_competitor_analyzer.py: Competitor recon & head-to-head matrix
  - seed_keyword_extractor.py: Keyword extraction
  - seed_orchestrator.py: Seed analysis orchestration
  - seed_serp.py: SERP discovery & keyword matrix
  - seed_report.py: Kickoff briefing report generation

Shim này duy trì backward compatibility cho các import hiện có.
"""
from seed_utils import *
from seed_site_analyzer import *
from seed_competitor_analyzer import *
from seed_head_to_head import *
from seed_keyword_extractor import *
from seed_orchestrator import *
from seed_reconnaissance import *
from seed_serp import *
from seed_report import *

__all__ = [
    "normalize_url", "fetch_html_with_meta", "fetch_html",
    "HEADERS", "VIETNAMESE_STOPWORDS", "INTENT_MODIFIERS",
    "detect_tech_stack_and_cms", "inspect_robots_and_sitemap", "discover_and_audit_subpages",
    "extract_entity_sameas_recon", "fetch_moz_metrics_pool",
    "extract_domain_age_and_citations", "build_commercial_and_cro_footprint",
    "build_head_to_head_matrix", "deep_audit_competitor_batch",
    "extract_multi_tier_keywords", "clean_category_name", "extract_pillar_intents_from_description",
    "analyze_seed_competitor", "run_continuous_seed_reconnaissance",
    "scan_keyword_serp_live", "generate_keyword_serp_matrix",
    "generate_kickoff_briefing",
]

