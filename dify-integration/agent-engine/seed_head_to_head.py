#!/usr/bin/env python3
"""Head-to-head matrix comparison - split from seed_competitor_analyzer.py."""
import json, logging, re, subprocess, urllib.parse
from datetime import datetime, timezone
from typing import Dict, List, Optional, Any, Union
from bs4 import BeautifulSoup
from serp_discovery_tool import get_latest_paa_questions
from seed_utils import HEADERS, normalize_url, fetch_html_with_meta, fetch_html, VIETNAMESE_STOPWORDS, INTENT_MODIFIERS

logger = logging.getLogger("seed_analyzer")

def build_head_to_head_matrix(my_site_audit: dict, seed_analysis: dict) -> dict:
    """Trụ Cột 6: Lập Ma trận Đối đầu Trực diện giữa Website Của Bạn vs Đối Thủ Mẫu."""
    my = my_site_audit or {}
    my_audit = my.get("onpage_audit") or my
    my_pillars = my.get("pillars") or {}

    seed_audit = seed_analysis.get("onpage_audit") or {}
    seed_pillars = seed_analysis.get("pillars") or {}

    rows = []

    # 1. Thẻ Title
    my_t = my_audit.get("title_length") or len(my.get("title") or "")
    seed_t = seed_audit.get("title_length", 0)
    my_title_sample = f" ({my.get('title', '')[:30]}...)" if my.get("title") else ""
    seed_title_sample = f" ({seed_analysis.get('title', '')[:30]}...)" if seed_analysis.get("title") else ""
    rows.append({
        "metric": "Tiêu Đề Trang (Title)",
        "my_site": f"{my_t} ký tự{my_title_sample}" if my_t else "Chưa tối ưu",
        "competitor": f"{seed_t} ký tự{seed_title_sample}",
        "status": "better" if 40 <= my_t <= 65 else "needs_work",
        "recommendation": "Tối ưu độ dài 45-65 ký tự chứa từ khóa chính + định vị giá sỉ."
    })

    # 2. Thẻ Meta Description
    my_m = my_audit.get("meta_desc_length") or len(my.get("meta_description") or "")
    seed_m = seed_audit.get("meta_desc_length", 0)
    rows.append({
        "metric": "Thẻ Mô Tả (Meta Description)",
        "my_site": f"{my_m} ký tự" if my_m else "Chưa có",
        "competitor": f"{seed_m} ký tự" if seed_m else "Chưa có",
        "status": "better" if 130 <= my_m <= 165 else "needs_work",
        "recommendation": "Viết mô tả hấp dẫn chứa lời hứa UVP và số hotline để kích thích CTR."
    })

    # 3. Thẻ H1 Phân Cấp
    my_h1 = my_audit.get("h1_count") or len(my.get("h1_tags") or my.get("h1") or [])
    seed_h1 = seed_audit.get("h1_count", 0)
    rows.append({
        "metric": "Cấu Trúc Thẻ H1",
        "my_site": f"{my_h1} thẻ H1" if my_h1 else "Thiếu H1",
        "competitor": f"{seed_h1} thẻ H1",
        "status": "better" if my_h1 == 1 else "needs_work",
        "recommendation": "Đảm bảo duy nhất 1 thẻ H1 duy nhất làm rõ danh mục chủ lực."
    })

    # 4. Cấu trúc Silo Danh mục
    my_cats = len(my.get("category_structure") or my.get("key_services_or_nav") or [])
    seed_cats = len(seed_analysis.get("category_structure") or [])
    rows.append({
        "metric": "Độ Phủ Danh Mục Silo",
        "my_site": f"{my_cats} danh mục",
        "competitor": f"{seed_cats} danh mục cốt lõi",
        "status": "better" if my_cats >= seed_cats else "gap",
        "recommendation": f"Cần thiết lập đủ {seed_cats} nhóm danh mục Silo như đối thủ để bao phủ từ khóa."
    })

    # 5. Schema Dữ liệu có cấu trúc
    my_schemas_list = my_audit.get("schemas", [])
    my_schema = ", ".join(my_schemas_list[:3]) or ("Có" if my_audit.get("has_schema") else "Chưa gắn")
    seed_schema = ", ".join(seed_audit.get("schemas", [])[:3]) or ("Có" if seed_audit.get("has_schema") else "Chưa gắn")
    rows.append({
        "metric": "Cấu Trúc Dữ Liệu Schema.org",
        "my_site": my_schema,
        "competitor": seed_schema,
        "status": "better" if "Product" in my_schema else "gap",
        "recommendation": "Cài đặt Schema Product & FAQPage để hiển thị ngôi sao đánh giá trên Google."
    })

    # 6. Tốc độ Phản hồi Máy Chủ (TTFB)
    my_tech = my_pillars.get("pillar3_technical_performance") or {}
    seed_tech = seed_pillars.get("pillar3_technical_performance") or {}
    my_ttfb = my_tech.get("ttfb_ms", 380)
    seed_ttfb = seed_tech.get("ttfb_ms", 450)
    rows.append({
        "metric": "Tốc Độ Phản Hồi (TTFB)",
        "my_site": f"{my_ttfb}ms",
        "competitor": f"{seed_ttfb}ms",
        "status": "better" if my_ttfb <= seed_ttfb else "needs_work",
        "recommendation": "Tối ưu bộ nhớ đệm Redis & CDN để đạt tốc độ < 400ms."
    })

    # 7. Độ sâu bài viết chi tiết
    my_content = my_pillars.get("pillar2_content_eeat") or {}
    seed_content = seed_pillars.get("pillar2_content_eeat") or {}
    my_words = my_content.get("avg_word_count", 0)
    seed_words = seed_content.get("avg_word_count", 1200)
    rows.append({
        "metric": "Độ Sâu Nội Dung Bài Viết",
        "my_site": f"~{my_words:,} từ/bài" if my_words else "Sản xuất mới (SOP 12)",
        "competitor": f"~{seed_words:,} từ/bài",
        "status": "better" if my_words >= seed_words else "gap",
        "recommendation": f"Sản xuất bài viết > {max(seed_words + 300, 1800)} từ kèm bảng so sánh để vượt top."
    })

    # 8. Mạng lưới Thực thể (Entity Social)
    my_entity = my_pillars.get("pillar7_entity_social") or {}
    seed_entity = seed_pillars.get("pillar7_entity_social") or {}
    my_act_social = my_entity.get("active_count", 0)
    seed_act_social = seed_entity.get("active_count", 0)
    rows.append({
        "metric": "Thực Thể Mạng Xã Hội (Entity sameAs)",
        "my_site": f"{my_act_social}/5 kênh" if my_act_social else "Cần đồng bộ",
        "competitor": f"{seed_act_social}/5 kênh" if seed_act_social else "Chưa đồng bộ",
        "status": "better" if my_act_social >= seed_act_social else "gap",
        "recommendation": "Đồng bộ liên kết sameAs Schema & Footer tới Facebook, YouTube, TikTok, Zalo, Google Maps."
    })

    # 9. Thẩm quyền & Tuổi đời tên miền
    my_auth = my_pillars.get("pillar8_authority_backlinks") or {}
    seed_auth = seed_pillars.get("pillar8_authority_backlinks") or {}
    my_age = my_auth.get("age_years", 0)
    seed_age = seed_auth.get("age_years", 0)
    my_da = my_auth.get("moz_da")
    seed_da = seed_auth.get("moz_da")
    my_da_str = f" - Moz DA {my_da}" if my_da else ""
    seed_da_str = f" - Moz DA {seed_da}" if seed_da else ""
    rows.append({
        "metric": "Tuổi Đời Tên Miền & Thẩm Quyền",
        "my_site": f"~{my_age} năm{my_da_str}" if my_age else "Tên miền mới",
        "competitor": f"~{seed_age} năm{seed_da_str}" if seed_age else "Chưa xác định",
        "status": "better" if my_age >= seed_age else "info",
        "recommendation": "Gia tăng trích dẫn Citation & Backlink thực thể để rút ngắn khoảng cách uy tín E-E-A-T."
    })

    # 10. Độ tươi mới nội dung (Content Freshness)
    my_fresh = my_content.get("content_freshness") or {}
    freshness = seed_content.get("content_freshness") or {}
    my_days_stale = my_fresh.get("days_since_update")
    days_stale = freshness.get("days_since_update")
    is_stale = freshness.get("is_stale", False)
    stale_text = f"Cũ ({days_stale} ngày chưa cập nhật)" if is_stale and days_stale else (f"Mới ({days_stale} ngày)" if days_stale is not None else "Bình thường")
    my_fresh_text = f"Mới ({my_days_stale} ngày)" if my_days_stale is not None and my_days_stale <= 60 else "Tươi mới 100% (SOP 12)"
    rows.append({
        "metric": "Độ Tươi Mới (Content Freshness)",
        "my_site": my_fresh_text,
        "competitor": stale_text,
        "status": "better" if is_stale else "info",
        "recommendation": "Tận dụng nội dung đối thủ bị lão hóa để xuất bản bài viết chuẩn dateModified 2026 vượt Top."
    })

    t_url = my.get("url") or my.get("target_url") or seed_analysis.get("seed_url") or ""
    is_content = any(k in f"{t_url} {my.get('title', '')} {seed_analysis.get('title', '')}".lower() for k in ["truyen", "truyện", "book", "comic", "novel", "doc"])
    schema_action = "Tích hợp cấu trúc Schema Book, WebSite & FAQPage chuẩn E-E-A-T lên website." if is_content else "Tích hợp cấu trúc Schema Product & FAQPage lên tất cả bài viết/sản phẩm."
    meta_action = "Tối ưu hóa thẻ Meta Description 150 ký tự nêu bật kho nội dung đặc sắc và trải nghiệm mượt mà." if is_content else "Tối ưu hóa thẻ Meta Description 150 ký tự nêu bật lợi thế cạnh tranh và chính sách khách hàng."

    priority_actions = [
        {"step": 1, "action": f"Bao phủ trọn vẹn {seed_cats} nhóm danh mục Silo cốt lõi của đối thủ.", "type": "silo"},
        {"step": 2, "action": schema_action, "type": "schema"},
        {"step": 3, "action": f"Sản xuất bài viết đánh giá chuyên sâu > {max(seed_words + 300, 1800)} từ theo chuẩn SOP 12.", "type": "content"},
        {"step": 4, "action": "Bổ sung tiện ích hỗ trợ độc giả/khách hàng tương tác nhanh để tối ưu tỷ lệ gắn kết.", "type": "cro"},
        {"step": 5, "action": meta_action, "type": "onpage"},
        {"step": 6, "action": "Xuất bản bài viết cập nhật dateModified mới nhất năm 2026 đánh vào lỗ hổng nội dung cũ của đối thủ.", "type": "freshness"}
    ]

    return {
        "matrix": rows,
        "top_actions": priority_actions
    }

