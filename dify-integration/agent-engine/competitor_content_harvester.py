#!/usr/bin/env python3
"""Competitor Article Live Harvester & Content Gap Synthesizer (SOP 12, 34, 35).
Strictly complies with SOP 23 (<= 300 lines) and SOP 30 (Zero Hardcoding).
"""
import re
import json
import logging
import urllib.request
import urllib.parse
from concurrent.futures import ThreadPoolExecutor, as_completed
from typing import Dict, Any, List, Optional
from bs4 import BeautifulSoup
import serp_crawler

logger = logging.getLogger("marketing_engine.competitor_harvester")

def _clean_text(text: str) -> str:
    """Loại bỏ khoảng trắng thừa và ký tự rác."""
    return re.sub(r"\s+", " ", text or "").strip()

def extract_article_outline(html_content: str, max_headings: int = 6) -> Dict[str, Any]:
    """Bóc tách cấu trúc đề mục H2/H3 và đoạn tóm tắt từ bài viết đối thủ."""
    soup = BeautifulSoup(html_content, "html.parser")
    
    # Loại bỏ các thẻ script, style, nav, footer, header
    for tag in soup(["script", "style", "nav", "footer", "header", "aside", "noscript"]):
        tag.decompose()
        
    headings = []
    seen = set()
    for h in soup.find_all(["h2", "h3"]):
        txt = _clean_text(h.get_text())
        if len(txt) >= 8 and len(txt) <= 120 and txt.lower() not in seen:
            # Loại bỏ các heading rác của hệ thống (menu, bình luận, bài liên quan)
            if not any(sw in txt.lower() for sw in ["bình luận", "bài viết liên quan", "tin cùng chuyên mục", "xem thêm", "đăng ký", "tìm kiếm"]):
                seen.add(txt.lower())
                headings.append(txt)
                if len(headings) >= max_headings:
                    break

    # Trích xuất 2 đoạn văn chính (tối đa 300 ký tự)
    paragraphs = []
    for p in soup.find_all("p"):
        p_txt = _clean_text(p.get_text())
        if len(p_txt) >= 50 and not any(sw in p_txt.lower() for sw in ["copyright", "bản quyền", "hotline:", "liên hệ:"]):
            paragraphs.append(p_txt[:180])
            if len(paragraphs) >= 2:
                break

    return {
        "headings": headings,
        "summary": " ".join(paragraphs) if paragraphs else ""
    }

def fetch_single_article(item: Dict[str, Any], timeout: int = 4) -> Optional[Dict[str, Any]]:
    """Tải và phân tích 1 bài viết đối thủ qua HTTP request an toàn."""
    url = item.get("url", "")
    if not url or not url.startswith("http"):
        return None
        
    try:
        req = urllib.request.Request(
            url,
            headers={
                "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36",
                "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
                "Accept-Language": "vi-VN,vi;q=0.9,en-US;q=0.8"
            }
        )
        with urllib.request.urlopen(req, timeout=timeout) as resp:
            content_type = resp.headers.get("Content-Type", "").lower()
            if "text/html" not in content_type:
                return None
            html = resp.read().decode("utf-8", errors="ignore")
            outline = extract_article_outline(html)
            
            domain = urllib.parse.urlsplit(url).netloc.replace("www.", "")
            return {
                "title": item.get("title", ""),
                "url": url,
                "domain": domain,
                "headings": outline["headings"],
                "summary": outline["summary"]
            }
    except Exception as e:
        logger.debug(f"[HARVEST_ITEM_FAIL] {url}: {e}")
        return None

def harvest_top_competitor_articles(
    query: str,
    project_domain: Optional[str] = None,
    max_competitors: int = 3,
    total_timeout: int = 6
) -> List[Dict[str, Any]]:
    """
    Trinh sát thời gian thực Google/DDG SERP, lấy và bóc tách nội dung 2-3 đối thủ Top đầu.
    Tuân thủ SOP 34 (Tri-Stream Hybrid RAG) và SOP 35 (Ngân sách token an toàn).
    """
    clean_q = re.sub(r"\s+", " ", query or "").strip()
    clean_q = re.sub(r"[\"']", "", clean_q)
    if len(clean_q) < 5:
        return []

    # 1. Truy vấn SERP lấy danh sách URL đối thủ đang rank
    try:
        serp_results = serp_crawler._fetch_serp_results(clean_q, timeout=total_timeout)
    except Exception as se:
        logger.warning(f"[SERP_HARVEST_QUERY_FAIL] {clean_q}: {se}")
        return []

    if not serp_results:
        return []

    # 2. Lọc bỏ tên miền chính chủ của dự án
    norm_proj_dom = (project_domain or "").lower().replace("www.", "").replace("https://", "").replace("http://", "").split("/")[0]
    candidate_items = []
    for r in serp_results:
        u = r.get("url", "")
        d = urllib.parse.urlsplit(u).netloc.lower().replace("www.", "")
        if norm_proj_dom and norm_proj_dom in d:
            continue
        candidate_items.append(r)
        if len(candidate_items) >= max_competitors + 1:
            break

    # 3. Thu hoạch song song nội dung các bài viết (ThreadPoolExecutor)
    harvested = []
    with ThreadPoolExecutor(max_workers=max_competitors) as executor:
        futures = [executor.submit(fetch_single_article, item, timeout=4) for item in candidate_items[:max_competitors]]
        for f in as_completed(futures):
            res = f.result()
            if res and res.get("headings"):
                harvested.append(res)

    return harvested

def build_competitor_grounding_block(
    harvested_articles: List[Dict[str, Any]],
    user_query: str,
    brand_name: str = "Kho Hàng"
) -> str:
    """
    Đóng gói dữ liệu đối thủ thành khối Grounding tri thức thực chiến (SOP 35 ngân sách <= 1.200 tokens).
    Kèm chỉ đạo chiến lược đè bẹp đối thủ và khử sạch dấu vết AI (SOP 12 & SOP 30).
    """
    if not harvested_articles:
        return ""

    lines = [
        "\n\n[DỮ LIỆU ĐỐI THỦ THỰC CHIẾN - SERP REAL-TIME GROUNDING (SOP 12, 34 & 35)]:",
        f"Chủ đề/Từ khóa tìm kiếm: \"{user_query}\"",
        f"Hệ thống đã trinh sát thời gian thực {len(harvested_articles)} bài viết đối thủ đang đứng Top đầu trên Google:\n"
    ]

    for idx, art in enumerate(harvested_articles, start=1):
        lines.append(f"{idx}. Đối thủ [{art['domain']}] - Tiêu đề: \"{art['title']}\"")
        if art.get("headings"):
            h_str = " -> ".join(art["headings"][:4])
            lines.append(f"   * Bố cục đề mục chính: {h_str}")
        if art.get("summary"):
            lines.append(f"   * Luận điểm chính họ đã nêu: {art['summary'][:160]}...")
        lines.append("")

    lines.extend([
        "💡 CHỈ ĐẠO CHIẾN LƯỢC ĐÈ BẸP ĐỐI THỦ (OUTRANKING & HUMAN EXPERTISE):",
        "1. KHOẢNG TRỐNG NỘI DUNG (CONTENT GAPS): Đối thủ chỉ dừng lại ở việc chép lại thông số kỹ thuật của hãng và phân tích lý thuyết chung chung.",
        f"2. BẮT CẦU THỰC CHIẾN: Bạn BẮT BUỘC mang góc nhìn của thợ kỹ thuật/chủ kho điện máy {brand_name} để giải quyết triệt để các khúc mắc thật của người dùng (ví dụ: bản nội địa vs quốc tế, bắt wifi xa bị giật thì xử lý thế nào, thói quen tắt nguồn standby làm hỏng board mạch, cam kết bảo hành lỗi 1 đổi 1 tận nơi).",
        "3. QUY TẮC KHỬ DẤU HIỆU AI (ZERO AI CLICHES - BẮT BUỘC):",
        "   - CẤM đặt emoji ở đầu mọi thẻ H2/H3 (như 🔍, 📊, 🛡️, 🔧...). Tiêu đề phải trang trọng, tự nhiên chuẩn báo chí công nghệ.",
        "   - CẤM đưa các thuật ngữ nội bộ như 'E-E-A-T', 'Anti-Persona', 'PAA', 'Schema', '# 👑 BÀI VIẾT HOÀN CHỈNH...' vào bài viết cho khách hàng.",
        "   - CẤM bịa đặt bảng số liệu phần trăm học thuật giả tạo (như '4.3% wifi yếu', '1.2% lỗi'). Hãy dùng trải nghiệm thực tế tại kho.",
        "   - CẤM mở bài bằng câu sáo rỗng 'Bạn đang phân vân giữa... Bài viết này không chỉ... mà còn...'. Hãy đi thẳng vào câu trả lời ngay đoạn mở đầu (BLUF)."
    ])

    return "\n".join(lines)
