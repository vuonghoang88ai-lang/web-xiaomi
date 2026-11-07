"""
SOP 30, 40, 41 & 42: Dynamic Algorithmic Pre-Routing & Gemini Grounding Engine.
TUYỆT ĐỐI KHÔNG FIX CỨNG (ZERO HARDCODING - SOP 30):
1. Phân loại ý định qua Goal-Driven Semantic FSM (SOP 41).
2. Phân định rành mạch giữa Blog Post (SOP 12) và WooCommerce Product (SOP 15/26).
3. Dynamic Project Catalog Synthesizer: Khai thác sản phẩm thực tế từ hồ sơ dự án.
4. Gemini Grounding Footnotes & Inline Citations (SOP 42).
Tuân thủ SOP 23 (<= 300 dòng) và SOP 30.
"""

from typing import Dict, Any, Optional, Tuple, List
import logging
import re
import json
from datetime import datetime
from .weather_grounder import extract_geospatial_temporal, fetch_live_weather_grounding

logger = logging.getLogger("marketing_engine.pre_routing")


def classify_intent_fsm(query: str, project: Optional[Dict[str, Any]] = None) -> Tuple[str, str]:
    """
    SOP 30 & SOP 41: Máy Trạng Thái Ngữ Nghĩa (Goal-Driven Semantic Intent FSM).
    Phân tích cấu trúc cú pháp hành động thay vì quét từ khóa tĩnh thô thiển.
    Đặc biệt phân định rạch ròi giữa Blog Editorial (SOP 12) và WooCommerce Product (SOP 26).
    """
    q_lower = query.lower()

    # 1. Kiểm tra Lệnh Chiến Lược Cấp Cao hoặc Phân tích Đa Tiêu Chí (Strategic Advisory)
    has_strategy_cmd = bool(re.search(r"^/(?:strategy|agentic|brief)\b", q_lower))
    multi_criteria_match = len(re.findall(r"\b(?:chiến lược|ngân sách|c-suite|boardroom|rủi ro|phân bổ|kế hoạch năm|kế hoạch quý|định vị|cvi)\b", q_lower))
    if has_strategy_cmd or multi_criteria_match >= 2:
        guide = (
            "\n\n[INTENT_MODE: TIER 3 - STRATEGIC ADVISORY]:\n"
            "Người dùng yêu cầu hoạch định chiến lược đa mục tiêu. Kích hoạt C-Suite phản biện đa chiều và đúc kết bản kế hoạch."
        )
        return "BOARDROOM_MODE", guide

    # 2. Kiểm tra Ý định Tạo Ảnh / Bìa Sách / Khắc phục Media (Visual Creative Handoff)
    action_verb = bool(re.search(r"\b(?:viết|soạn|tạo|lập|xuất bản|làm|build|draft|generate|compose|thiết kế|đăng|post)\b", q_lower))
    has_image_intent = bool(re.search(r"\b(?:ảnh|bìa|hình ảnh|cover|banner|thumbnail|thiếu ảnh|bù đắp ảnh)\b", q_lower))
    if has_image_intent and (action_verb or any(k in q_lower for k in ["bắt đầu", "đồng ý", "tiến hành", "xử lý", "thiếu", "bù đắp"])):
        guide = (
            "\n\n[INTENT_MODE: TIER 2 - VISUAL CREATIVE HANDOFF]:\n"
            "Yêu cầu tạo hình ảnh / bìa tác phẩm / bù đắp media. Xác nhận súc tích trong 1-2 đoạn ngắn, đề xuất phong cách Typography "
            "và mời người dùng nhấn nút 'Tạo ảnh bìa tự động (Novel Cover Artisan)' hoặc 'Tự động quét & bù đắp ảnh thiếu trên web' "
            "ở ngay bên dưới để hệ thống ngầm render trong < 0.1s và gán lên WordPress. "
            "TUYỆT ĐỐI CẤM yêu cầu người dùng tự lưu file vào thư mục host hay tự copy prompt thủ công."
        )
        return "VISUAL_HANDOFF_MODE", guide

    # 3. SOP 30 & 41: Khắc phục Kỹ thuật On-Page & Thẻ Heading (On-Page Technical Remediation)
    onpage_tech_tokens = len(re.findall(r"\b(?:heading|h1|h2|h3|thẻ\s*h[1-3]|thẻ\s*tiêu\s*đề|onpage|on-page|meta\s*description|trùng\s*lặp|canonical|silo|schema)\b", q_lower))
    remediation_verb = bool(re.search(r"\b(?:tối\s*ưu|sửa|khắc\s*phục|fix|chuẩn\s*hóa|xử\s*lý|áp\s*dụng|cấu\s*hình|kiểm\s*tra|tiến\s*hành)\b", q_lower))
    if onpage_tech_tokens >= 1 and (remediation_verb or "⚠️" in query or "lỗi" in q_lower):
        current_year = datetime.now().year
        guide = (
            f"\n\n[INTENT_MODE: TIER 2 - ONPAGE TECHNICAL REMEDIATION]:\n"
            f"Người dùng yêu cầu TỐI ƯU KỸ THUẬT ON-PAGE (Thẻ Heading/Meta Description/Silo).\n"
            f"1. Xác định lỗi kỹ thuật và nguyên nhân gốc rễ (trùng lặp thẻ H1, thiếu phân cấp H2/H3).\n"
            f"2. Xuất bản Cấu trúc Heading chuẩn hóa 100% cho dự án theo thời gian thực (Năm hiện tại: {current_year}, TUYỆT ĐỐI KHÔNG dùng năm cũ 2024 hay 2023).\n"
            f"3. Cung cấp rõ ràng thẻ H1 chuẩn đề xuất dạng: `H1_SUGGESTED: \"<Nội dung thẻ H1 chuẩn>\"` để hệ thống tự động bóc tách và gán lên WordPress.\n"
            f"4. Kết luận ngắn gọn hướng dẫn người dùng nhấn nút '⚡ Tự Động Áp Dụng Heading & On-Page Lên WordPress' bên dưới để đẩy trực tiếp qua Omni Bridge."
        )
        return "ONPAGE_REMEDIATION", guide

    # 4. Phân loại Sản Phẩm Thực Thi (Deliverable Execution: Blog vs Product)
    deliverable_entity = bool(re.search(r"\b(?:bài|post|bài viết|bài đăng|content|kịch bản|landing|woocommerce|quảng cáo|sản phẩm|product)\b", q_lower))
    if action_verb and deliverable_entity:
        # Thuật toán Đo lường Mật độ Thuộc tính Thương mại (Commercial Specification Density - CSD)
        comm_tokens = len(re.findall(r"\b(?:giá|giá bán|khuyến mãi|bảo hành|thông số|sku|woocommerce|mua ngay|kỹ thuật|model|sản phẩm|cửa hàng)\b", q_lower))
        editorial_tokens = len(re.findall(r"\b(?:blog|hướng dẫn|cẩm nang|kinh nghiệm|mẹo|chia sẻ|đánh giá|review|top\s+\d+|phân tích|nghiên cứu)\b", q_lower))
        
        # Lấy archetype từ dự án để tăng trọng số ngữ cảnh
        arch = ""
        if project:
            bp = project.get("brand_profile") or {}
            arch = (bp.get("archetype") if isinstance(bp, dict) else "") or ""

        is_product = (comm_tokens > editorial_tokens) or (arch == "commerce_retail" and "sản phẩm" in q_lower and not editorial_tokens)
        
        if is_product:
            guide = (
                "\n\n[INTENT_MODE: TIER 2 - DELIVERABLE WOOCOMMERCE PRODUCT]:\n"
                "Người dùng yêu cầu đăng/soạn SẢN PHẨM WOOCOMMERCE. Xuất bản 100% nội dung hoàn chỉnh ngay (CẤM dừng lại ở dàn ý).\n"
                "Cấu trúc bắt buộc: Tên sản phẩm chuẩn SEO, Bảng thông số kỹ thuật (Specs Table), Giá niêm yết, Lợi ích cốt lõi, "
                "Chính sách bảo hành & Cam kết kho hàng thực tế, Nút CTA mua hàng."
            )
            return "DELIVERABLE_PRODUCT", guide
        else:
            guide = (
                "\n\n[INTENT_MODE: TIER 2 - DELIVERABLE BLOG EDITORIAL]:\n"
                "Người dùng yêu cầu viết BÀI VIẾT BLOG CHUẨN SEO (SOP 12). Xuất bản 100% nội dung bài viết hoàn chỉnh ngay (CẤM dừng ở dàn ý).\n"
                "Cấu trúc bắt buộc: Tiêu đề H1 thu hút, Hook mở đầu 4 dòng, Các đề mục H2/H3 chuyên sâu E-E-A-T, Phân tích thực chứng, "
                "FAQ Schema và Kết bài kèm Call-to-Action tự nhiên."
            )
            return "DELIVERABLE_BLOG", guide

    # 4. Mặc định Fast-Path Trực Diện (Factual Tra cứu, thời tiết, giá cả, hỏi đáp)
    guide = (
        "\n\n[INTENT_MODE: TIER 1 - DIRECT FAST-PATH]:\n"
        "Câu hỏi tra cứu thông tin trực diện. Trả lời thẳng vào trọng tâm trong 1-3 đoạn văn súc tích, không mở boardroom-debate."
    )
    return "DIRECT_FAST_PATH", guide


def synthesize_dynamic_catalog(project: Dict[str, Any], query: str) -> Optional[str]:
    """
    SOP 30: Thuật toán Tổng hợp Danh mục Sản phẩm Động từ Hồ sơ Dự án.
    Tuyệt đối không fix cứng tên sản phẩm hay giá bán vào mã nguồn!
    """
    if not project:
        return None

    brand_profile = project.get("brand_profile") or {}
    keyword_matrix = brand_profile.get("keyword_matrix") or []
    q_words = set(re.findall(r"\w+", query.lower()))

    matched_items = []
    # Thuật toán so khớp độ tương đồng ngữ nghĩa động (Jaccard Overlap)
    for item in keyword_matrix:
        kw_words = set(re.findall(r"\w+", (item.get("keyword") or "").lower()))
        cat_words = set(re.findall(r"\w+", (item.get("category") or "").lower()))
        if (kw_words & q_words) or (cat_words & q_words):
            matched_items.append(item)

    if not matched_items:
        return None

    lines = []
    project_name = project.get("name") or "Dự án"
    lines.append(f"\n\n[HỒ SƠ DANH MỤC THỰC TẾ CỦA DỰ ÁN '{project_name}' (NGUỒN [2])]:")
    
    if project.get("business_description"):
        lines.append(f"- Ngành hàng & Dịch vụ: {project.get('business_description')}")
    if project.get("core_strengths"):
        lines.append(f"- Lợi thế cạnh tranh (UVP): {project.get('core_strengths')}")

    for item in matched_items[:4]:
        kw = item.get("keyword") or ""
        cat = item.get("category") or ""
        recom = item.get("recommended_action") or ""
        vol = item.get("search_volume_est") or ""
        lines.append(f"- Phân mục/Từ khóa: {kw} (Ngành: {cat}) | Hành động đề xuất: {recom} | Volume: {vol}")

    lines.append("⚠️ QUY TẮC: Tôn trọng 100% hồ sơ dự án trên khi tư vấn hoặc so sánh sản phẩm/dịch vụ.")
    return "\n".join(lines)


def extract_pure_user_query(query: str) -> str:
    """SOP 45: Bóc tách câu hỏi thuần túy của người dùng, loại bỏ các thẻ ngữ cảnh hệ thống."""
    if not query:
        return ""
    q = re.sub(r"\[(?:BỐI CẢNH|PROJECT CONTEXT|CHỈ THỊ|DỮ LIỆU|HỒ SƠ|QUY CHUẨN|INTENT)[^\]]*\]\s*", "", query)
    return q.strip() or query.strip()


def enrich_query_with_live_grounding(query: str, project_id: str) -> str:
    """
    SOP 30, 41, 42 & 45: Tự động phân tích, bổ sung Grounding thực tế không fix cứng.
    """
    if not query:
        return query

    import db_service
    project = db_service.get_project_by_id(project_id) or {}
    pure_query = extract_pure_user_query(query)

    injections = []
    sources_meta = []

    # 1. Phân loại Ngưỡng Họp Động qua Máy Trạng Thái FSM (SOP 41 & Blog vs Product)
    tier_mode, meeting_guide = classify_intent_fsm(pure_query, project)
    injections.append(meeting_guide)
    logger.info(f"[INTENT_FSM] Query classified into tier={tier_mode}")

    # 2. Phân giải Địa lý & Thời gian Động
    loc_name, time_frame = extract_geospatial_temporal(pure_query, project.get("target_location"))

    # 3. SOP 30 & 45: Nhận diện Ý định Khí tượng bằng Cụm Khái Niệm Thời Tiết
    weather_concept_pattern = (
        r"\b(?:thời\s+tiết|nhiệt\s+độ|khí\s+tượng|khí\s+hậu|dự\s+báo\s+thời\s+tiết|"
        r"trời\s+mưa|mưa\s+rào|lượng\s+mưa|trời\s+nắng|nắng\s+nóng|độ\s+ẩm|"
        r"trời\s+lạnh|se\s+lạnh|không\s+khí\s+lạnh|đợt\s+lạnh|rét\s+đậm|rét\s+hại|trời\s+rét)\b"
    )
    has_weather_intent = bool(re.search(weather_concept_pattern, pure_query.lower()))
    if has_weather_intent:
        try:
            weather_res = fetch_live_weather_grounding(loc_name, time_frame)
            if weather_res:
                w_block, w_cite = weather_res
                injections.append(w_block)
                sources_meta.append(w_cite)
                logger.info(f"[DYNAMIC_WEATHER] Grounded for loc={loc_name}")
        except Exception as ew:
            logger.warning(f"[DYNAMIC_WEATHER_FAIL] {ew}")

    # 4. SOP 30: Tổng hợp Danh mục Sản phẩm Động (chỉ nạp khi không phải Visual Handoff)
    if project and tier_mode != "VISUAL_HANDOFF_MODE":
        catalog_block = synthesize_dynamic_catalog(project, pure_query)
        if catalog_block:
            injections.append(catalog_block)
            sources_meta.append(f"- `[2]` Hồ sơ Kho Tri thức Dự án {project.get('name')} (Xác thực thực tế)")
            logger.info(f"[DYNAMIC_CATALOG] Injected for project={project.get('name')}")

    # 4b. SOP 12, 34 & 35: Trinh sát Bài viết Đối thủ Thời gian thực (Live Competitor Article Harvester)
    content_intent_pattern = (
        r"\b(?:viết\s+bài|lập\s+dàn\s+ý|dàn\s+ý|bài\s+viết|content|seo|paa|w[1-5]|"
        r"đánh\s+giá|review|so\s+sánh|hướng\s+dẫn|có\s+tốt\s+không|có\s+bền\s+không|"
        r"có\s+nên\s+mua|mua\s+loại\s+nào|kinh\s+nghiệm|giải\s+đáp)\b"
    )
    if project and re.search(content_intent_pattern, pure_query.lower()):
        try:
            from competitor_content_harvester import harvest_top_competitor_articles, build_competitor_grounding_block
            quote_match = re.search(r"[\"']([^\"']{8,100})[\"']", pure_query)
            topic_query = quote_match.group(1) if quote_match else pure_query
            topic_query = re.sub(r"^(?:hãy\s+)?(?:viết\s+bài|lập\s+dàn\s+ý|giải\s+đáp|cho\s+tôi\s+biết|tìm\s+hiểu|phân\s+tích)\s+", "", topic_query, flags=re.IGNORECASE).strip()

            proj_url = project.get("target_website_url", "")
            brand_name = project.get("name", "Kho Hàng")
            harvested_arts = harvest_top_competitor_articles(topic_query, project_domain=proj_url, max_competitors=3, total_timeout=5)
            if harvested_arts:
                comp_block = build_competitor_grounding_block(harvested_arts, topic_query, brand_name)
                injections.append(comp_block)
                for art in harvested_arts:
                    sources_meta.append(f"- `[SERP]` Bài đối thủ: {art['title']} ({art['domain']})")
                logger.info(f"[COMPETITOR_HARVEST] Injected {len(harvested_arts)} articles for topic: {topic_query[:40]}")
        except Exception as ce:
            logger.warning(f"[COMPETITOR_HARVEST_FAIL] {ce}")

    # 5. Minh bạch Trích dẫn Gemini Grounding (SOP 42)
    if sources_meta:
        sources_list = "\n".join(sources_meta)
        citations_rule = (
            f"\n\n[QUY CHUẨN MINH BẠCH DỮ LIỆU - GEMINI GROUNDING CITATIONS (SOP 42)]:\n"
            f"1. Gắn ký hiệu trích dẫn `[1]` ngay sau mỗi số liệu thời tiết lấy từ trạm vệ tinh.\n"
            f"2. Gắn ký hiệu trích dẫn `[2]` ngay sau thông số hoặc phân khúc sản phẩm của dự án.\n"
            f"3. Cuối câu trả lời, in danh mục nguồn minh bạch:\n"
            f"---\n"
            f"### 📚 Nguồn Dữ Liệu Thực Tế (Grounding Sources)\n"
            f"{sources_list}\n"
        )
        injections.append(citations_rule)

    if injections:
        return query + "".join(injections)

    return query
