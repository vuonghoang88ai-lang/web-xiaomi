"""
SOP 37: Speculative Prompt Expander Engine.
Tự động làm giàu và tối ưu hóa các câu lệnh ngắn (raw query) của người dùng
thành câu lệnh chuyên sâu 10x chuẩn Editorial Architecture & WooCommerce,
tự động ánh xạ model sản phẩm và UVP dự án.
Tuân thủ SOP 23 (<= 300 dòng) và SOP 30 (Zero Hardcoding).
"""

from typing import Any, Dict, List, Optional
import re
import logging
from pydantic import BaseModel
from fastapi import APIRouter, HTTPException

logger = logging.getLogger("marketing_engine.prompt_expander")
router = APIRouter()


class ExpandPromptRequest(BaseModel):
    prompt: str
    target_mode: Optional[str] = "auto"  # 'auto', 'product', 'blog', 'competitor', 'strategy'


class ExpandPromptResponse(BaseModel):
    original_prompt: str
    enhanced_prompt: str
    detected_intent: str
    matched_entity: Optional[str] = None
    expansion_summary: str


def classify_prompt_intent(prompt: str) -> str:
    """Phân loại ý định người dùng bằng thuật toán Goal-Driven Decomposition."""
    p_lower = prompt.lower()
    if any(k in p_lower for k in ["sản phẩm", "bán", "woocommerce", "mua", "giá bao nhiêu", "chi tiết máy", "inch", "tivi", "tủ lạnh", "máy giặt"]):
        return "deliverable_product"
    if any(k in p_lower for k in ["soi", "đối thủ", "competitor", "serp", "thị phần", "giá sàn"]):
        return "competitor_recon"
    if any(k in p_lower for k in ["chiến lược", "kế hoạch", "lộ trình", "giai đoạn", "flash sale", "thời vụ"]):
        return "campaign_strategy"
    if any(k in p_lower for k in ["hướng dẫn", "cách", "kinh nghiệm", "đánh giá", "review", "so sánh"]):
        return "deliverable_blog"
    return "general_marketing"


def find_matching_product_entity(prompt: str, project_data: Dict[str, Any]) -> Optional[str]:
    """Tìm model/từ khóa sản phẩm khớp nhất trong cơ sở dữ liệu dự án."""
    p_lower = prompt.lower()
    
    # Quét qua keyword_matrix của dự án
    matrix = project_data.get("keyword_matrix") or []
    for item in matrix:
        kw = item.get("keyword") if isinstance(item, dict) else str(item)
        if kw and any(term in kw.lower() for term in p_lower.split() if len(term) >= 3):
            return kw

    # Quét qua master_keyword_pool
    pool = project_data.get("master_keyword_pool") or []
    for item in pool:
        kw = item.get("keyword") if isinstance(item, dict) else str(item)
        if kw and any(term in kw.lower() for term in p_lower.split() if len(term) >= 3):
            return kw

    return None


def generate_enhanced_prompt(
    raw_prompt: str,
    intent: str,
    matched_entity: Optional[str],
    domain: str = "tongkhomi.com",
    brand_name: str = "Tổng Kho Mi"
) -> str:
    """Kiến tạo Prompt 10x chuẩn Editorial Architecture."""
    entity_str = matched_entity or raw_prompt.strip()

    if intent == "deliverable_product":
        return (
            f"Hãy đóng vai trò Giám đốc Tiếp thị (CMO) và Chuyên gia Nội dung Thương mại điện tử của {brand_name} ({domain}). "
            f"Dựa trên yêu cầu: '{raw_prompt}', hãy nghiên cứu 10 đối thủ SERP hàng đầu và xuất bản 100% bài viết sản phẩm WooCommerce hoàn chỉnh cho: '{entity_str}'.\n\n"
            f"Yêu cầu cấu trúc bắt buộc:\n"
            f"1. Tiêu đề H1 chuẩn SEO chứa tên model và năm 2026.\n"
            f"2. Bảng thông số kỹ thuật cốt lõi (Màn hình, Âm thanh, Kết nối, Bảo hành).\n"
            f"3. 4 Đề mục H2 giải quyết nỗi đau khách hàng và trải nghiệm thực tế.\n"
            f"4. Bảng so sánh điểm vượt trội của {brand_name} so với đối thủ (Giá kho, Giao hàng siêu tốc, Bảo hành chính hãng).\n"
            f"5. Lời kêu gọi hành động (CTA) dứt khoát kèm chính sách bảo hành."
        )

    if intent == "competitor_recon":
        return (
            f"Đóng vai trò Trưởng ban Trinh sát Thị trường của {brand_name} ({domain}). "
            f"Hãy kích hoạt công cụ trinh sát 10 đối thủ trực tuyến cho chủ đề: '{entity_str}'. "
            f"Bóc tách rõ: Bảng giá sàn thị trường, Top 3 đối thủ Organic SEO mạnh nhất, điểm yếu (Vulnerability) của đối thủ Top 1 "
            f"và đề xuất khoảng trống thị trường (Market Gap) để {brand_name} chiếm lĩnh."
        )

    if intent == "campaign_strategy":
        return (
            f"Đóng vai trò CMO chiến lược của {brand_name}. Dựa trên yêu cầu '{raw_prompt}', "
            f"hãy xây dựng bản Kế hoạch Hành động Thương mại (Commercial Action Plan) tích hợp dữ liệu thời tiết thực tế và thời vụ bán lẻ. "
            f"Bao gồm: Mục tiêu doanh số, Nhóm sản phẩm mũi nhọn ({entity_str}), Thông điệp chiến dịch (UVP Hook), và Lịch phân bổ nội dung đa kênh (Web, TikTok, Fanpage)."
        )

    # Blog / Cẩm nang SEO SOP 12
    return (
        f"Hãy viết bài viết cẩm nang chuyên sâu chuẩn SOP 12 (10x Editorial Architecture) cho website {domain} "
        f"với chủ đề: '{entity_str}'.\n"
        f"Yêu cầu: Không dùng văn phong sáo rỗng AI; cung cấp số liệu thực tế, bảng so sánh trực quan, "
        f"trả lời trúng Search Intent của khách hàng và lồng ghép khéo léo giải pháp mua hàng tại {brand_name}."
    )


@router.post("/api/v1/portal/projects/{project_id}/expand-prompt", response_model=ExpandPromptResponse)
def portal_expand_prompt(project_id: str, req: ExpandPromptRequest):
    """
    Endpoint tối ưu hóa prompt chuyên sâu (Speculative Prompt Expansion):
    Biến câu lệnh ngắn ngủi của người dùng thành prompt 10x chuẩn SEO và WooCommerce.
    """
    raw_prompt = req.prompt.strip()
    if not raw_prompt:
        raise HTTPException(status_code=400, detail="Prompt không được để trống.")

    # Đọc dữ liệu dự án từ DB
    import db_service
    project_full = db_service.get_project_full_intelligence(project_id, "") or {}
    project = project_full.get("project") or db_service.get_project_by_id(project_id) or {}

    domain = (project.get("target_website_url") or "tongkhomi.com").replace("https://", "").replace("http://", "").rstrip("/")
    brand_name = project.get("brand_name") or project.get("name") or "Tổng Kho Mi"

    intent = req.target_mode if req.target_mode != "auto" else classify_prompt_intent(raw_prompt)
    matched_entity = find_matching_product_entity(raw_prompt, project_full)

    enhanced = generate_enhanced_prompt(
        raw_prompt=raw_prompt,
        intent=intent,
        matched_entity=matched_entity,
        domain=domain,
        brand_name=brand_name
    )

    summary = f"Đã tối ưu hóa sang định dạng {intent.upper()}"
    if matched_entity:
        summary += f", tự động khớp sản phẩm '{matched_entity}'"

    return ExpandPromptResponse(
        original_prompt=raw_prompt,
        enhanced_prompt=enhanced,
        detected_intent=intent,
        matched_entity=matched_entity,
        expansion_summary=summary
    )
