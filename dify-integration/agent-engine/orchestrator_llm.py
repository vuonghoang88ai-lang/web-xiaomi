#!/usr/bin/env python3
"""LLM 9Router invocation, prompt builders, FAQ Schema and technical specs extraction."""
import os
import re
import json
import urllib.request
from typing import Optional

ROUTER_URL = os.environ.get(
    "ROUTER_URL", "http://100.73.214.3:40128/v1/chat/completions"
)
ROUTER_KEY = os.environ.get("ROUTER_API_KEY", "")
MODEL_NAME = "combo-creater"

def call_9router(system_prompt: str, user_prompt: str, max_tokens: int = 4000) -> str:
    """Gọi LLM qua 9Router Gateway với timeout và error handling."""
    if not ROUTER_KEY:
        raise RuntimeError("Thiếu ROUTER_API_KEY; không được hardcode credential trong mã nguồn.")
    payload = {
        "model": MODEL_NAME,
        "messages": [
            {"role": "system", "content": system_prompt},
            {"role": "user", "content": user_prompt}
        ],
        "temperature": 0.3,
        "max_tokens": max_tokens
    }
    req = urllib.request.Request(
        ROUTER_URL,
        data=json.dumps(payload).encode("utf-8"),
        headers={
            "Authorization": f"Bearer {ROUTER_KEY}",
            "Content-Type": "application/json"
        }
    )
    with urllib.request.urlopen(req, timeout=90) as resp:
        res = json.loads(resp.read().decode("utf-8"))
        return res["choices"][0]["message"]["content"]

def extract_and_generate_faq_schema(md_text: str) -> str:
    """Tự động bóc tách các câu hỏi FAQ và tạo Schema JSON-LD FAQPage chuẩn Google."""
    faq_matches = re.findall(r'###\s*(?:\d+[\.\)]\s*)?([^\n\?]+\?)\s*\n+(.*?)(?=\n###|\n##|\Z)', md_text, re.DOTALL)
    if not faq_matches:
        return ""
    faq_items = []
    for q, a in faq_matches[:5]:
        clean_q = re.sub(r'[*_#]', '', q).strip()
        clean_a = re.sub(r'[*_#\[\]]', '', a).strip()
        clean_a = re.sub(r'\(http[^\)]+\)', '', clean_a).strip()
        if clean_q and clean_a:
            faq_items.append({
                "@type": "Question",
                "name": clean_q,
                "acceptedAnswer": {"@type": "Answer", "text": clean_a[:500]}
            })
    if not faq_items:
        return ""
    schema_data = {
        "@context": "https://schema.org",
        "@type": "FAQPage",
        "mainEntity": faq_items
    }
    return f'\n<script type="application/ld+json">\n{json.dumps(schema_data, ensure_ascii=False, indent=2)}\n</script>\n'

def extract_technical_specs_from_markdown(md_text: str) -> list:
    """Quét các bảng Markdown để trích xuất danh sách cặp spec_label, spec_value cho ACF Repeater."""
    specs = []
    lines = md_text.splitlines()
    header_seen = False
    for line in lines:
        line_s = line.strip()
        if line_s.startswith("|") and line_s.endswith("|"):
            parts = [p.strip() for p in line_s.split("|")[1:-1]]
            if len(parts) >= 2:
                if all(re.match(r"^:?-+:?$", p) for p in parts):
                    header_seen = True
                    continue
                if not header_seen:
                    header_seen = True
                    continue
                label = parts[0]
                val = parts[1]
                if label.lower() in ["thông số", "đặc điểm", "tiêu chí", "tính năng", "model"] and val.lower() in ["chi tiết", "giá trị", "thông số", "mô tả"]:
                    continue
                clean_lbl = re.sub(r'[*_`]', '', label).strip()
                clean_v = re.sub(r'[*_`]', '', val).strip()
                skip_keywords = ["chính sách", "bảo hành", "khuyến mãi", "ưu đãi", "giao hàng", "vận chuyển", "quà tặng", "hotline", "kho hàng", "đổi trả"]
                if any(sk in clean_lbl.lower() for sk in skip_keywords):
                    continue
                if len(clean_v) > 120 and any(sk in clean_v.lower() for sk in skip_keywords):
                    continue
                if clean_lbl and clean_v:
                    specs.append({"spec_label": clean_lbl, "spec_value": clean_v})
        else:
            header_seen = False
    return specs

def build_system_prompt(post_type: str, project_intelligence: dict, project_name: str, target_url: str, target_location: str, business_desc: str, uvp_positioning: str) -> str:
    """Sinh System Prompt tùy biến cho loại bài viết blog SEO hoặc trang chi tiết WooCommerce."""
    if post_type == "product":
        if project_intelligence:
            return (
                f"Bạn là Hệ thống Đa tác nhân Marketing AI chuyên viết TRANG CHI TIẾT SẢN PHẨM/DỊCH VỤ WOOCOMMERCE ĐỘC QUYỀN cho thương hiệu: '{project_name}' ({target_url}).\n"
                f"Khu vực địa bàn thị trường: {target_location}. Ngành hàng/Dịch vụ: {business_desc}.\n\n"
                f"ĐỊNH VỊ ĐỘC QUYỀN (UVP ĐÃ PHÊ DUYỆT BẮT BUỘC BÁM SÁT):\n\"{uvp_positioning}\"\n\n"
                f"TIÊU CHUẨN NỘI DUNG SẢN PHẨM TOÀN DIỆN (UNIVERSAL E-COMMERCE CONTENT STANDARD - UECS):\n"
                f"1. TIÊU ĐỀ H1 CHUẨN HÓA: [Tên Sản Phẩm/Dịch Vụ] + [Model/Quy Cách Chính Xác] + [Đặc Tính/Công Nghệ Đột Phá] + [Chính Hãng/Độc Quyền].\n"
                f"2. ĐOẠN TÓM TẮT USP NGẮN đặt sau ===SHORT_DESCRIPTION===: 4-5 bullet points súc tích đánh trúng chuyển đổi.\n"
                f"3. BẢNG THÔNG SỐ KỸ THUẬT: Bảng Markdown 25-35 thông số định lượng chính xác (số + đơn vị đo).\n"
                f"4. BÀI VIẾT ĐÁNH GIÁ CHI TIẾT đặt sau ===FULL_DESCRIPTION===: 1200 - 2200 từ, đầy đủ 6 TRỤ CỘT (Bối cảnh thực tế, Stress test, Anti-Persona, Ma trận so sánh, Checklist lắp đặt, Mẹo bảo dưỡng).\n"
                f"5. KỊCH BẢN TIKTOK 60S & BÀI POST FACEBOOK đặt sau thẻ ===TIKTOK_CAMPAIGN===."
            )
        return (
            "Bạn là Hệ thống Đa tác nhân Marketing AI chuyên viết TRANG CHI TIẾT SẢN PHẨM/DỊCH VỤ WOOCOMMERCE ĐỘC QUYỀN.\n"
            "TIÊU CHUẨN NỘI DUNG SẢN PHẨM TOÀN DIỆN (UNIVERSAL E-COMMERCE CONTENT STANDARD - UECS):\n"
            "1. H1 chuẩn hóa, 2. Short Description sau ===SHORT_DESCRIPTION===, 3. Bảng thông số định lượng, 4. Full Description 1200-2200 từ (6 trụ cột), 5. Kịch bản TikTok sau ===TIKTOK_CAMPAIGN===."
        )
    if project_intelligence:
        return (
            f"Bạn là Hệ thống Đa tác nhân Marketing AI chuyên trách sản xuất BÀI VIẾT CHUẨN SEO, CHUYÊN SÂU & GIÀU TÍNH THỰC CHIẾN cho thương hiệu: '{project_name}' ({target_url}).\n"
            f"Khu vực địa bàn thị trường: {target_location}. Ngành hàng/Dịch vụ: {business_desc}.\n\n"
            f"ĐỊNH VỊ ĐỘC QUYỀN (UVP ĐÃ PHÊ DUYỆT BẮT BUỘC BÁM SÁT):\n\"{uvp_positioning}\"\n\n"
            f"TIÊU CHUẨN BÀI VIẾT ĐỘC QUYỀN CHỐNG RẬP KHUÔN (ANTI-CLICHE & HUMAN-GRADE EDITORIAL):\n"
            f"1. Mở bài xoáy sâu nỗi đau thật, cấm mở bài sáo rỗng AI. 2. Bóc tách lầm tưởng và dữ liệu đo lường. 3. Tình huống đời thường. 4. Bảng so sánh đa chiều. 5. Ưu nhược điểm khách quan. 6. 5 câu hỏi FAQ thực chiến. 7. Độ dài 1.500 - 2.500 từ.\n"
            f"Tách nội dung sau ===AGENT COPYWRITER=== và kịch bản mạng xã hội sau ===TIKTOK_CAMPAIGN===."
        )
    return (
        "Bạn là biên tập viên SEO chuyên sâu đa ngành cho website của dự án.\n"
        "TIÊU CHUẨN BÀI VIẾT CHUYÊN GIA THỰC CHIẾN (10X EDITORIAL STANDARDS):\n"
        "1. Đi thẳng vào vấn đề, cấm mở bài sáo rỗng. 2. Dẫn chứng và myth-busting. 3. Bảng so sánh đa chiều & 5 câu FAQ. 4. Độ dài 1.500 - 2.500 từ. 5. Tách phần viết bài sau ===AGENT COPYWRITER=== và kịch bản sau ===TIKTOK_CAMPAIGN===."
    )
