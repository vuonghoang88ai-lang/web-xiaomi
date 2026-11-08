"""
SOP 39: Burstiness & Stylometry Anti-AI Normalizer.
Tự nhiên hóa văn phong bài viết, triệt tiêu các cụm từ sáo rỗng AI (AI Cliches)
và cân bằng độ biến thiên độ dài câu (Burstiness) để nâng cao điểm thẩm định EEAT của Google.
Tuân thủ SOP 23 (<= 300 dòng) và SOP 30 (Zero Hardcoding).
"""

from typing import Dict, List, Tuple
import re
import logging

logger = logging.getLogger("marketing_engine.stylometry_normalizer")

# Từ điển ánh xạ cụm từ sáo rỗng AI sang ngôn ngữ bán lẻ tự nhiên
AI_CLICHE_MAPPINGS: List[Tuple[re.Pattern, str]] = [
    (re.compile(r'trong thời đại (?:công nghệ )?số(?:\s*hóa)?', re.IGNORECASE), "Trên thị trường hiện nay"),
    (re.compile(r'trong bối cảnh thị trường hiện nay', re.IGNORECASE), "Hiện tại"),
    (re.compile(r'không thể (?:không nhắc đến|bỏ qua|phủ nhận rằng)', re.IGNORECASE), "Đáng chú ý là"),
    (re.compile(r'hãy cùng (?:chúng tôi )?(?:khám phá|đi sâu|tìm hiểu)', re.IGNORECASE), "Cùng đánh giá thực tế"),
    (re.compile(r'đóng vai trò (?:vô cùng |hết sức )?quan trọng', re.IGNORECASE), "Rất cần thiết"),
    (re.compile(r'mang lại trải nghiệm (?:đỉnh cao|tuyệt vời|hoàn hảo)', re.IGNORECASE), "Đáp ứng trọn vẹn nhu cầu thực tế"),
    (re.compile(r'tóm lại, có thể thấy rằng', re.IGNORECASE), "Tổng kết lại"),
    (re.compile(r'sự lựa chọn hoàn hảo', re.IGNORECASE), "Lựa chọn rất đáng cân nhắc"),
    (re.compile(r'không chỉ dừng lại ở đó', re.IGNORECASE), "Thêm vào đó"),
    (re.compile(r'như một giải pháp cứu cánh', re.IGNORECASE), "Như một giải pháp tối ưu"),
    (re.compile(r'xứng đáng là người bạn đồng hành', re.IGNORECASE), "Rất phù hợp cho gia đình"),
    (re.compile(r'hứa hẹn sẽ mang đến', re.IGNORECASE), "Mang lại"),
    (re.compile(r'bước đột phá vượt bậc', re.IGNORECASE), "Cải tiến đáng kể"),
    (re.compile(r'vô vàn những tiện ích', re.IGNORECASE), "Nhiều tiện ích thực tế"),
    (re.compile(r'mở ra một chân trời mới', re.IGNORECASE), "Nâng cấp trải nghiệm"),
    (re.compile(r'bạn đang phân vân giữa hàng loạt[^\.\n]+(?:\.|\n+)', re.IGNORECASE), ""),
    (re.compile(r'bài viết này không chỉ trả lời[^\.\n]+mà còn[^\.\n]+(?:\.|\n+)', re.IGNORECASE), ""),
    (re.compile(r'dưới đây là bài viết hoàn chỉnh[^\n]*\n+', re.IGNORECASE), ""),
    (re.compile(r'dựa trên dữ liệu từ các datasets[^\n]*\n+', re.IGNORECASE), ""),
]


def purge_ai_cliches(text: str) -> Tuple[str, int]:
    """
    Quét và thanh lọc các cụm từ sáo rỗng kinh điển của AI.
    Trả về (text_đã_làm_mượt, số_lượng_cụm_từ_đã_thay_thế).
    """
    if not text:
        return text, 0

    purged_text = text
    replacement_count = 0

    for pattern, replacement in AI_CLICHE_MAPPINGS:
        matches = list(pattern.finditer(purged_text))
        if matches:
            replacement_count += len(matches)
            purged_text = pattern.sub(replacement, purged_text)

    return purged_text, replacement_count


def calculate_burstiness_score(text: str) -> Dict[str, float]:
    """
    Tính toán chỉ số Burstiness (độ biến thiên độ dài câu).
    Điểm Burstiness cao thể hiện văn phong người thật, xen kẽ câu ngắn và câu dài.
    """
    sentences = re.split(r'[.!?]+', text)
    sentences = [s.strip() for s in sentences if len(s.strip()) > 3]

    if not sentences:
        return {"avg_sentence_len": 0, "burstiness_variance": 0, "rating": 0}

    lengths = [len(s.split()) for s in sentences]
    avg_len = sum(lengths) / len(lengths)

    # Tính phương sai độ dài câu
    variance = sum((l - avg_len) ** 2 for l in lengths) / len(lengths)
    std_dev = variance ** 0.5

    # Điểm đánh giá (0-100)
    score = min(100.0, max(20.0, (std_dev / (avg_len or 1)) * 100))

    return {
        "avg_sentence_len": round(avg_len, 1),
        "sentence_count": len(sentences),
        "burstiness_score": round(score, 1),
        "is_human_like": score >= 45.0
    }


def normalize_human_burstiness(text: str) -> Dict[str, any]:
    """
    Toàn bộ chu trình chuẩn hóa văn phong người thật chống AI Detect.
    """
    if not text:
        return {"text": "", "purged_count": 0, "metrics": {}}

    cleaned_text, count = purge_ai_cliches(text)
    metrics = calculate_burstiness_score(cleaned_text)

    return {
        "text": cleaned_text,
        "purged_count": count,
        "metrics": metrics,
        "anti_ai_badge": "🟢 EEAT HUMAN PASS" if metrics.get("is_human_like") else "🟡 NHỊP ĐIỆU CẦN TỐI ƯU"
    }


def sanitize_ai_footprints(text: str) -> str:
    """
    SOP 12 & 45: Khử trùng triệt để các dấu vết AI, tiêu đề rập khuôn,
    lạm phát emoji và rò rỉ thuật ngữ kỹ thuật nội bộ (E-E-A-T, Schema, Anti-Persona).
    """
    if not text:
        return text

    t = text
    # 1. Khử tiêu đề boilerplate đầu bài và huy hiệu vương miện giả tạo
    t = re.sub(r"^#\s*👑\s*[^\n]*\n+", "", t, flags=re.I).strip()
    t = re.sub(r"^#\s*BÀI VIẾT HOÀN CHỈNH[^\n]*\n+", "", t, flags=re.I).strip()

    # 2. Khử toàn bộ emoji máy móc ở đầu các thẻ H1, H2, H3, H4
    t = re.sub(r"(?m)^(#{1,4}\s*)[\U00010000-\U0010ffff\u2600-\u27bf\u2b50\ufe0f\s]+", r"\1", t)
    t = re.sub(r"(?m)^(#{1,4}\s*)[🔍📊🛡️🔧⚠️🛠️🌟📞❓🎯💡👑✨🚀📌💎🏆🔥]\s*", r"\1", t)

    # 3. Khử rò rỉ thuật ngữ kỹ thuật nội bộ
    t = re.sub(r"\s*\(Expertise,\s*Experience,\s*Authoritativeness,\s*Trustworthiness\)", "", t, flags=re.I)
    t = re.sub(r"\s*\(People Also Ask\)", "", t, flags=re.I)
    t = re.sub(r"\s*\(FAQ Schema\)", "", t, flags=re.I)
    t = re.sub(r"Anti-Persona:\s*", "", t, flags=re.I)
    t = re.sub(r"Anti-Persona\s*\([^\)]*\)", "Những ai không phù hợp với model này", t, flags=re.I)

    # 4. Thanh lọc các cụm từ sáo rỗng AI
    t, _ = purge_ai_cliches(t)

    # 5. Khử trùng ngôn từ lệch pha B2B / kho bãi nội bộ sang góc nhìn người tiêu dùng gia đình (B2C)
    t = re.sub(r"(?i)(?:Là|Với tư cách là|Dưới góc độ)\s+chủ kho\s+[^\,\.\n]+[\,\.]\s*", "Dưới góc độ đánh giá công nghệ thực tế, ", t)
    t = re.sub(r"(?i)từ chủ kho điện máy lâu năm", "từ góc nhìn chuyên gia tiêu dùng thực tế", t)
    t = re.sub(r"(?i)từ chủ kho[^\n]+", "từ góc nhìn chuyên gia tiêu dùng thực tế", t)
    t = re.sub(r"để được chủ kho trực tiếp tư vấn", "để được chuyên viên tư vấn tận tâm", t, flags=re.I)
    t = re.sub(r"chủ kho trực tiếp tư vấn", "chuyên viên tư vấn tận tâm", t, flags=re.I)
    t = re.sub(r"tại kho chúng tôi", "tại hệ thống chúng tôi", t, flags=re.I)
    t = re.sub(r"tại kho(?=[\s,\.\)])", "tại hệ thống", t, flags=re.I)
    t = re.sub(r"thống kê tại kho", "thống kê thực tế từ người dùng", t, flags=re.I)
    t = re.sub(r"thay board nguồn\s+\d+[\-\d]*\s*k\s*(?:tại kho)?", "chi phí sửa chữa linh kiện", t, flags=re.I)
    t = re.sub(r"mua USB LAN tại kho", "sử dụng bộ chuyển đổi mạng LAN", t, flags=re.I)
    return t


