#!/usr/bin/env python3
"""Report generation - split from seed_analyzer.py."""
import json, logging, re, subprocess, urllib.parse
from datetime import datetime, timezone
from typing import Dict, List, Optional, Any, Union
from bs4 import BeautifulSoup
from serp_discovery_tool import get_latest_paa_questions, discover_top_10_competitors, normalize_website_url
from seed_utils import HEADERS, normalize_url, fetch_html, fetch_html_with_meta, VIETNAMESE_STOPWORDS, INTENT_MODIFIERS
from seed_site_analyzer import detect_tech_stack_and_cms, inspect_robots_and_sitemap, discover_and_audit_subpages
from seed_competitor_analyzer import extract_entity_sameas_recon, fetch_moz_metrics_pool, extract_domain_age_and_citations, build_commercial_and_cro_footprint, build_head_to_head_matrix, deep_audit_competitor_batch
from seed_keyword_extractor import extract_multi_tier_keywords, clean_category_name, extract_pillar_intents_from_description
from seed_orchestrator import analyze_seed_competitor

logger = logging.getLogger("seed_analyzer")

def generate_kickoff_briefing(
    project_name: str,
    target_url: str,
    my_site_data: dict,
    seed_url: Optional[str] = None,
    seed_analysis: Optional[dict] = None,
    extracted_keywords: list = None,
    competitors: list = None,
    suggested_uvps: list = None,
    location: str = "Toàn quốc"
) -> str:
    """
    Sinh Bản Báo Cáo Tình Báo Khởi Động Dự Án (Executive Kickoff Briefing).
    Được CMO AI Copilot xuất bản ngay trong bong bóng chat đầu tiên khi dự án được khởi tạo.
    Mô phỏng báo cáo chiến lược của Giám đốc Tiếp thị (CMO) gửi Ban Lãnh đạo.
    """
    import urllib.parse
    clean_target_url = target_url.strip()
    target_host = urllib.parse.urlsplit(clean_target_url).hostname or clean_target_url
    my_site = my_site_data or {}
    my_title = my_site.get("title") or "Chưa cấu hình Title tối ưu"
    my_h1_list = my_site.get("h1") or []
    my_h1_str = ", ".join(my_h1_list) if my_h1_list else "Chưa có thẻ H1 chuẩn"
    my_meta = my_site.get("meta_description") or ""
    my_hotlines = my_site.get("hotlines") or []
    my_nav = my_site.get("key_services_or_nav") or []

    # Đánh giá hiện trạng Onpage website mình
    onpage_notes = []
    if not my_meta:
        onpage_notes.append("Thiếu thẻ Meta Description (Khoảng trống CTR lớn trên Google Search)")
    elif len(my_meta) < 120 or len(my_meta) > 165:
        onpage_notes.append(f"Meta Description dài {len(my_meta)} ký tự (Khuyến nghị chuẩn 150-160 ký tự)")
    if not my_h1_list:
        onpage_notes.append("Chưa phát hiện thẻ H1 duy nhất chứa từ khóa định vị thương hiệu")
    if not my_nav:
        onpage_notes.append("Cấu trúc Menu Silo điều hướng danh mục chưa bóc tách được rõ ràng")
    if not onpage_notes:
        onpage_notes.append("Cấu trúc Onpage cơ bản ổn định, sẵn sàng tiếp nhận luồng traffic từ Google")

    # Phân tích đối thủ mẫu
    seed_section = ""
    if seed_url and seed_analysis:
        seed_host = urllib.parse.urlsplit(seed_url).hostname or seed_url
        seed_title = seed_analysis.get("title") or "N/A"
        seed_cats = seed_analysis.get("category_structure") or []
        seed_h1s = seed_analysis.get("h1_tags") or []
        seed_gaps = seed_analysis.get("onpage_gaps") or []

        top_kws = extracted_keywords[:8] if extracted_keywords else []
        kw_badges = " • ".join([f"`{kw}`" for kw in top_kws]) if top_kws else "Đang bóc tách"

        seed_section = f"""
- **Đối Thủ Mẫu Tham Chiếu (`{seed_host}`)**:
  - **Tiêu đề & Định vị đối thủ**: {seed_title}
  - **Cấu trúc Silo danh mục**: Phát hiện **{len(seed_cats)} nhóm danh mục cốt lõi** ({', '.join(seed_cats[:5]) if seed_cats else 'Đang quét'})
  - **Bộ Từ Khóa Vàng đang thống trị**: {kw_badges}
  - **Lỗ hổng Onpage đối thủ phát hiện được**: {seed_gaps[0] if seed_gaps else 'Cấu trúc bài viết chưa tối ưu chuyên sâu E-E-A-T, đây là cơ hội vàng để ta đè bẹp.'}
"""
    else:
        seed_section = f"""
- **Đối Thủ Mẫu Tham Chiếu**: Chưa chỉ định đối thủ mẫu ban đầu. Quý khách có thể bổ sung đối thủ mẫu bất kỳ lúc nào tại Tab **2. 10 Đối Thủ SERP** để AI giải mã.
"""

    # Radar 10 đối thủ
    comps = competitors or []
    comp_bullets = []
    brand_comp_warning = ""

    for idx, c in enumerate(comps[:5], start=1):
        d = c.get("domain", "")
        rank = c.get("top_rank", idx)
        st = c.get("strengths", "")
        comp_bullets.append(f"  {idx}. **{d}** (Top {rank} Google) - *{st[:75]}...*")
        if c.get("is_brand_competitor"):
            brand_comp_warning = f"\n> 🚨 **CẢNH BÁO TRANH CHẤP THƯƠNG HIỆU**: Phát hiện đối thủ `{d}` có tên miền tương tự thương hiệu `{project_name}`, có nguy cơ ăn chặn lượng tìm kiếm trực tiếp của bạn!"

    comp_summary = "\n".join(comp_bullets) if comp_bullets else "  *(Đang tải dữ liệu radar)*"

    # 4 Phương án UVP
    uvps = suggested_uvps or []
    uvp_bullets = []
    for idx, u in enumerate(uvps[:4], start=1):
        t = u.get("title", f"Định vị {idx}")
        s = u.get("slogan", "")
        uvp_bullets.append(f"- 🎯 **Phương án {idx}: {t}**\n  *\"{s}\"*")

    uvp_summary = "\n".join(uvp_bullets) if uvp_bullets else "- *(Đang tối ưu 4 phương án UVP)*"

    briefing = f"""# 🚀 BÁO CÁO KHỞI ĐỘNG CHIẾN DỊCH MARKETING & TÌNH BÁO THỊ TRƯỜNG
**Dự án**: **{project_name.upper()}** | **Website đích**: [{target_host}]({clean_target_url})  
**Địa bàn phục vụ**: {location or 'Toàn quốc'} | **Trạng thái**: ✅ **Kích hoạt tự động 100% Online**

---
Xin chào Ban Lãnh Đạo **{project_name}**! Tôi là **CMO AI Copilot** cùng Đội ngũ Multi-Agent 6 Chuyên gia (W1-W6).  
Hệ thống đã tự động hoàn tất trinh sát kép Onpage và quét Google SERP thực tế ngay khi dự án được khởi tạo. Dưới đây là bức tranh tình báo tổng thể:

### 1. 🔍 Soi Chiếu Kép Onpage (Dual Onpage Audit)
- **Website Cần Phát Triển (`{target_host}`)**:
  - **Tiêu đề (Title)**: {my_title}
  - **Thẻ H1**: `{my_h1_str}`
  - **Meta Description**: {f'"{my_meta}"' if my_meta else '*(Trống - cần bổ sung gấp)*'}
  - **Khoảng trống Onpage cần xử lý ngay**: {'; '.join(onpage_notes)}.
{seed_section}
### 2. 🌐 Radar 10 Đối Thủ Google SERP (100% Online)
Hệ thống đã truy kích và xác minh trực tiếp trên Google Search. Phát hiện **{len(comps)} đối thủ thực chiến** đang cạnh tranh thứ hạng:
{comp_summary}{brand_comp_warning}

### 3. 🎯 Đề Xuất 4 Phương Án Định Vị Độc Quyền (UVP - Tab 4)
Để chiến thắng mà không rơi vào bẫy cạnh tranh giá rẻ kiệt quệ, AI đề xuất 4 góc định vị:
{uvp_summary}
*(Quý khách có thể chuyển sang Tab **4. Định Vị UVP** để duyệt 1 chạm hoặc chọn phương án ngay bên dưới).*

### 4. ⚡ Kế Hoạch 3 Bước Tiếp Theo & Hành Động Ngay:
1. **Bước 1**: Phê duyệt 1 trong 4 Phương án Định vị UVP độc quyền.
2. **Bước 2**: Ra lệnh cho Agent W1 quét bóc tách bảng giá và thông số 10 đối thủ.
3. **Bước 3**: Ra lệnh cho Agent W4 chấp bút ngay 1 bài viết chuẩn SEO 2000+ từ (SOP 12) đè bẹp đối thủ top 1.

---
**💡 Gợi ý phím tắt khởi động tức thì (Bấm trực tiếp hoặc gõ lệnh):**
- [🎯 Phê duyệt Định vị UVP số 1](#action-uvp1)
- [📊 Soi Radar Giá & Khuyến Mãi 10 Đối Thủ](#action-prices)
- [✍️ Chấp Bút Bài Chuẩn SEO Ngay](#action-write-seo)
- [🗺️ Lập Kế Hoạch Đăng Bài 30 Ngày](#action-plan30)
"""
    return briefing.strip()




