#!/usr/bin/env python3
"""LLM invocation, UVP suggestions generation & UVP optimization analysis."""
import os
import re
import json
import logging
import urllib.request
from typing import Optional, List, Dict

logger = logging.getLogger("serp_discovery")

ROUTER_URL = os.environ.get("ROUTER_URL", "http://100.73.214.3:40128/v1/chat/completions")
ROUTER_KEY = os.environ.get("ROUTER_API_KEY", "")
MODEL_NAME = "combo-creater"

def call_llm(system_prompt: str, user_prompt: str, max_tokens: int = 2500, timeout: int = 150, tenant_id: str = None) -> str:
    """Gọi LLM: Hỗ trợ AI BYOK (Custom/OpenAI/Gemini) hoặc mặc định qua 9Router Gateway."""
    try:
        import db_service
        ai_cfg = db_service.get_system_setting("ai_config", tenant_id) or {}
    except Exception:
        ai_cfg = {}

    mode = ai_cfg.get("mode", "system")
    if mode == "custom" and ai_cfg.get("api_key"):
        provider = ai_cfg.get("provider", "openai")
        api_key = ai_cfg.get("api_key", "").strip()
        base_url = (ai_cfg.get("base_url") or "").strip()
        model = (ai_cfg.get("model_name") or "").strip()

        if not base_url:
            if provider == "openai":
                base_url = "https://api.openai.com/v1/chat/completions"
            elif provider == "gemini":
                base_url = "https://generativelanguage.googleapis.com/v1beta/openai/chat/completions"
            else:
                base_url = "https://api.openai.com/v1/chat/completions"
        elif not base_url.endswith("/chat/completions"):
            base_url = base_url.rstrip("/") + "/chat/completions"

        if not model:
            model = "gpt-4o" if provider == "openai" else ("gemini-1.5-flash" if provider == "gemini" else "default")

        payload = {
            "model": model,
            "messages": [
                {"role": "system", "content": system_prompt},
                {"role": "user", "content": user_prompt}
            ],
            "temperature": 0.2,
            "max_tokens": max_tokens
        }
        req = urllib.request.Request(
            base_url,
            data=json.dumps(payload).encode("utf-8"),
            headers={"Authorization": f"Bearer {api_key}", "Content-Type": "application/json"}
        )
        with urllib.request.urlopen(req, timeout=timeout) as resp:
            res = json.loads(resp.read().decode("utf-8"))
            return res["choices"][0]["message"]["content"]

    router_url = os.environ.get("ROUTER_URL", ROUTER_URL)
    router_key = os.environ.get("ROUTER_API_KEY", ROUTER_KEY)
    if not router_key:
        raise RuntimeError("Thiếu ROUTER_API_KEY; vui lòng cấu hình trong biến môi trường hoặc Cài đặt AI BYOK.")

    payload = {
        "model": MODEL_NAME,
        "messages": [
            {"role": "system", "content": system_prompt},
            {"role": "user", "content": user_prompt}
        ],
        "temperature": 0.2,
        "max_tokens": max_tokens
    }
    req = urllib.request.Request(
        router_url,
        data=json.dumps(payload).encode("utf-8"),
        headers={"Authorization": f"Bearer {router_key}", "Content-Type": "application/json"}
    )
    with urllib.request.urlopen(req, timeout=timeout) as resp:
        res = json.loads(resp.read().decode("utf-8"))
        return res["choices"][0]["message"]["content"]

def generate_uvp_suggestions(business_desc: str, target_location: str, top_competitors: list) -> list:
    """Khi người dùng chưa có lợi thế độc quyền, đề xuất đúng 4 phương án UVP phù hợp ngành hàng."""
    prompt = (
        f"Ngành hàng / Dịch vụ: '{business_desc}'\n"
        f"Khu vực địa lý: '{target_location}'\n"
        f"Kết quả SERP đã thu thập và xác minh (có thể không đủ 10):\n"
        f"{json.dumps(top_competitors, ensure_ascii=False, indent=2)}\n\n"
        "Hãy đóng vai trò Giám Đốc Chiến Lược Định Vị Thương Hiệu (Brand Positioning CMO):\n"
        "Chỉ kết luận về đối thủ từ dữ liệu có trong kết quả. Nếu bằng chứng không đủ, ghi rõ các phương án là GIẢ THUYẾT cần kiểm chứng, không gán điểm yếu chưa xác thực. Đề xuất ĐÚNG 4 phương án UVP phù hợp ngành hàng:\n"
        "1. Phương án 1: Giá trị và cam kết có thể đo lường.\n"
        "2. Phương án 2: Tốc độ, tiện lợi hoặc trải nghiệm khác biệt.\n"
        "3. Phương án 3: Chuyên môn, công nghệ hoặc phương pháp đã có bằng chứng.\n"
        "4. Phương án 4: Chăm sóc, cá nhân hóa hoặc mô hình phục vụ độc đáo.\n\n"
        "Xuất kết quả dưới dạng JSON Array gồm đúng 4 objects:\n"
        "[\n"
        "  {\n"
        "    \"id\": 1,\n"
        "    \"title\": \"Trục Giá & Cam Kết Đột Phá\",\n"
        "    \"slogan\": \"Khẩu hiệu định vị ngắn gọn, ấn tượng\",\n"
        "    \"rationale\": \"Lý do tại sao góc này thắng đối thủ\",\n"
        "    \"tactics\": \"Chiến thuật thực thi cụ thể trên website & nội dung\"\n"
        "  }\n"
        "]"
    )
    raw_res = call_llm("Bạn là chuyên gia định vị thương hiệu & chiến lược UVP cấp cao.", prompt, max_tokens=500, timeout=30)
    json_match = re.search(r"\[\s*\{.*?\}\s*\]", raw_res, re.DOTALL)
    if json_match:
        try:
            return json.loads(json_match.group(0))
        except Exception:
            pass

    return [
        {
            "id": 1,
            "title": "Giá trị & Cam kết minh bạch",
            "slogan": "Giá trị rõ ràng, điều kiện rõ ràng, không hứa vượt quá bằng chứng",
            "rationale": "Giả thuyết định vị; cần xác thực bằng phỏng vấn khách hàng và dữ liệu đối thủ.",
            "tactics": "Chỉ công bố giá và cam kết có thể thực hiện, kèm điều khoản rõ ràng."
        },
        {
            "id": 2,
            "title": "Tốc độ & Tiện lợi",
            "slogan": "Trải nghiệm đơn giản, nhanh và phù hợp năng lực vận hành",
            "rationale": "Giả thuyết định vị; kiểm tra nhu cầu phản hồi nhanh với khách hàng mục tiêu.",
            "tactics": "Đặt SLA phản hồi phù hợp năng lực vận hành trước khi truyền thông."
        },
        {
            "id": 3,
            "title": "Chuyên môn & Bằng chứng",
            "slogan": "Năng lực được chứng minh bằng hồ sơ, quy trình và kết quả hợp lệ",
            "rationale": "Giả thuyết định vị; chỉ sử dụng chứng chỉ, kinh nghiệm và số liệu có bằng chứng.",
            "tactics": "Công bố hồ sơ chuyên gia, chứng nhận và kết quả được phép xác thực."
        },
        {
            "id": 4,
            "title": "Cá nhân hóa & Đồng hành",
            "slogan": "Giải pháp được điều chỉnh theo nhu cầu và bối cảnh khách hàng",
            "rationale": "Giả thuyết định vị; kiểm chứng mức độ khách hàng coi trọng dịch vụ cá nhân hóa.",
            "tactics": "Thiết kế hoạt động chăm sóc phù hợp nguồn lực và chính sách thực tế."
        }
    ]

def analyze_and_optimize_user_uvp(user_uvp: str, business_desc: str, target_location: str, top_competitors: list) -> dict:
    """Khi người dùng đã điền lợi thế, đánh giá bằng chứng hiện có và đề xuất 3-4 phương án tối ưu."""
    prompt = (
        f"Ngành hàng / Dịch vụ: '{business_desc}'\n"
        f"Khu vực địa lý: '{target_location}'\n"
        f"Lợi thế cạnh tranh người dùng tự điền: '{user_uvp}'\n\n"
        f"Kết quả SERP đã thu thập và xác minh (có thể không đủ 10):\n"
        f"{json.dumps(top_competitors, ensure_ascii=False, indent=2)}\n\n"
        "Hãy đóng vai trò Giám Đốc Chiến Lược Định Vị Thương Hiệu & CRO (Brand Strategy CMO):\n"
        "1. ĐÁNH GIÁ LỢI THẾ (chỉ gọi là benchmarking nếu dữ liệu đối thủ đủ bằng chứng):\n"
        "   - Lợi thế người dùng điền có bị bão hòa (nhiều đối thủ cũng tuyên bố tương tự) hay không?\n"
        "   - Đâu là điểm mạnh thực sự cần giữ lại, và đâu là điểm yếu/rủi ro trong cách diễn đạt hiện tại?\n"
        "2. ĐỀ XUẤT 3-4 PHƯƠNG ÁN TỐI ƯU TIẾP (UVP Optimization & Amplification):\n"
        "   - Phương án 1 (Số liệu hóa & Minh chứng thực tế): Biến lời khẳng định thành số liệu và chứng thực cụ thể.\n"
        "   - Phương án 2 (Cam kết Đảo ngược Rủi ro - Risk Reversal): Bổ sung cam kết đền bù, bảo hành vượt trội để đè bẹp đối thủ.\n"
        "   - Phương án 3 (Đóng gói Thành Hệ Thống Độc Bản): Đặt tên cho công nghệ hoặc quy trình phục vụ độc quyền.\n"
        "   - Phương án 4 (Định vị Trải nghiệm / Chuyển đổi Cao cấp).\n\n"
        "Xuất kết quả dưới dạng JSON Object duy nhất:\n"
        "{\n"
        "  \"benchmark_evaluation\": \"Đánh giá bằng chứng; ghi rõ giới hạn nếu chưa thể benchmark\",\n"
        "  \"overlap_with_competitors\": \"Những điểm đối thủ cũng đang nói\",\n"
        "  \"real_winning_edge\": \"Điểm thực sự vượt trội\",\n"
        "  \"suggested_options\": [\n"
        "    {\n"
        "      \"id\": 1,\n"
        "      \"title\": \"Tên phương án nâng cấp\",\n"
        "      \"slogan\": \"Khẩu hiệu định vị sau khi tối ưu sắc bén\",\n"
        "      \"rationale\": \"Tại sao phương án này thuyết phục khách hàng hơn lợi thế ban đầu\",\n"
        "      \"tactics\": \"Cách triển khai cụ thể trên web và bài viết\"\n"
        "    }\n"
        "  ]\n"
        "}"
    )
    raw_res = call_llm("Bạn là chuyên gia phân tích và tối ưu hóa lợi thế cạnh tranh thương hiệu.", prompt, max_tokens=600, timeout=30)
    json_match = re.search(r"\{\s*\"benchmark_evaluation\".*?\}\s*$", raw_res, re.DOTALL)
    if json_match:
        try:
            return json.loads(json_match.group(0))
        except Exception:
            pass

    return {
        "benchmark_evaluation": "Chưa đủ dữ liệu SERP được xác minh để kết luận mức độ bão hòa hoặc so sánh với đối thủ.",
        "overlap_with_competitors": "Chưa thể xác minh điểm trùng lặp từ dữ liệu hiện có.",
        "real_winning_edge": "Chưa thể kết luận; cần bằng chứng khách hàng và dữ liệu đối thủ sâu hơn.",
        "suggested_options": [
            {
                "id": 1,
                "title": "Số Liệu Hóa & Minh Chứng Thực Tế",
                "slogan": f"{user_uvp} - Minh chứng rõ ràng, thông tin minh bạch",
                "rationale": "Đề xuất tối ưu ngôn từ; chưa được benchmark với đối thủ.",
                "tactics": "Chỉ công bố số liệu và chứng nhận có hồ sơ kiểm chứng."
            },
            {
                "id": 2,
                "title": "Cam Kết Đảo Ngược Rủi Ro (Risk Reversal)",
                "slogan": f"{user_uvp} - Cam kết rõ phạm vi và điều kiện thực hiện",
                "rationale": "Làm rõ kỳ vọng và giảm rủi ro cảm nhận cho khách hàng.",
                "tactics": "Chỉ đưa ra cam kết được doanh nghiệp phê duyệt và có thể thực hiện."
            },
            {
                "id": 3,
                "title": "Đóng Gói Thành Quy Trình Độc Bản",
                "slogan": f"Quy trình phục vụ minh bạch kết hợp {user_uvp}",
                "rationale": "Giúp khách hàng hiểu rõ cách doanh nghiệp cung cấp giá trị.",
                "tactics": "Mô tả các bước dịch vụ và tiêu chuẩn đã được áp dụng thực tế."
            }
        ]
    }
