#!/usr/bin/env python3
"""Keyword extraction from competitor pages - split from seed_analyzer.py."""
import json, logging, re, urllib.parse
from typing import Dict, List, Optional, Any, Union
from bs4 import BeautifulSoup

logger = logging.getLogger("seed_analyzer")

def deduplicate_phrase(text: str) -> str:
    """Loại bỏ triệt để việc lặp từ tố/thương hiệu trong cụm từ (e.g. 'Truyện 360 Truyen360' -> 'Truyện 360')."""
    if not text:
        return ""
    text = re.sub(r"\s+", " ", text).strip()
    text = re.sub(r"\b(\w+(?:\s+\w+){0,2})\s+\1\b", r"\1", text, flags=re.IGNORECASE)
    tokens = text.split()
    flat_no_accents = [re.sub(r"[^a-zA-Z0-9]", "", w).lower() for w in tokens]
    to_remove = set()
    for i, tok in enumerate(flat_no_accents):
        if not tok or len(tok) < 3:
            continue
        for j in range(len(flat_no_accents)):
            if j != i and j + 1 < len(flat_no_accents) and j != i - 1 and tok == (flat_no_accents[j] + flat_no_accents[j+1]):
                to_remove.add(i)
            elif j < i and flat_no_accents[j] == tok and len(tok) >= 3:
                to_remove.add(i)
    result_tokens = [tokens[idx] for idx in range(len(tokens)) if idx not in to_remove]
    return re.sub(r"\s+", " ", " ".join(result_tokens)).strip()

def extract_multi_tier_keywords(
    soup: BeautifulSoup,
    title: str,
    meta_desc: str,
    h1s: List[str],
    h2s: List[str],
    h3s: List[str],
    nav_categories: List[str],
    domain: str,
    subcategories: List[str] = None
) -> Dict[str, Any]:
    """Hệ thống phân tích & trích xuất cụm từ khóa đa tầng (Multi-Tier Keyword Intelligence)."""
    clean_domain = domain.lower().removeprefix("www.")
    brand_stem = clean_domain.split(".")[0]
    page_text_lower = f"{title} {meta_desc} {' '.join(h1s)} {' '.join(h2s)}".lower()
    is_xiaomi = any(k in brand_stem for k in ["xiaomi", "viomi", "tongkhomi", "mihanoi", "mivietnam"]) or "xiaomi" in page_text_lower
    is_story = any(w in page_text_lower for w in ["truyện", "tiểu thuyết", "novel", "đọc truyện", "chương", "sab_chapter"]) or "truyen" in clean_domain
    brand_name = "Xiaomi" if is_xiaomi else brand_stem.capitalize()

    # --- 0. BÓC TÁCH THẺ META KEYWORDS NGUYÊN BẢN CỦA ĐỐI THỦ ---
    meta_keywords = []
    meta_kw_tag = soup.find("meta", attrs={"name": re.compile(r"^keywords$", re.I)})
    if meta_kw_tag and meta_kw_tag.get("content"):
        raw_kws = [k.strip() for k in meta_kw_tag.get("content", "").split(",") if k.strip()]
        for k in raw_kws:
            if 3 <= len(k) <= 60 and k.lower() not in [m.lower() for m in meta_keywords]:
                meta_keywords.append(deduplicate_phrase(k))

    # --- 1. TẦNG 1: HEAD KEYWORDS (TỪ KHÓA CỐT LÕI & NGÀNH HÀNG CHA) ---
    head_keywords = []
    seen_head = set()

    for cat in (nav_categories or []):
        c_clean = cat.strip()
        cl = c_clean.lower()
        if len(c_clean) < 3 or len(c_clean) > 30:
            continue
        if any(j in cl for j in ["chính sách", "tin tức", "liên hệ", "giới thiệu", "hotline", "bảo hành"]):
            continue
        norm_c = re.sub(r"[^a-zA-Z0-9]", "", c_clean).lower()
        norm_b = re.sub(r"[^a-zA-Z0-9]", "", brand_name).lower()
        if is_xiaomi and norm_b not in norm_c and len(cl.split()) <= 2:
            kw = f"{c_clean} {brand_name}".strip()
        elif is_story:
            if norm_c == norm_b or "360" in norm_c:
                kw = "Truyện 360"
            elif not cl.startswith("truyện") and len(cl.split()) <= 2:
                kw = f"Truyện {c_clean}".strip()
            else:
                kw = c_clean
        else:
            kw = c_clean
        kw = deduplicate_phrase(kw)

        kw_lower = kw.lower()
        if kw_lower not in seen_head:
            seen_head.add(kw_lower)
            head_keywords.append(kw)

    if len(head_keywords) < 4:
        if is_story:
            defaults_head = ["Truyện Tiên Hiệp", "Truyện Kiếm Hiệp", "Truyện Ngôn Tình", "Truyện Đô Thị", "Truyện Huyền Huyễn", "Truyện Xuyên Không"]
        elif is_xiaomi:
            defaults_head = [
                f"Tivi {brand_name}", f"Tủ Lạnh {brand_name}", f"Máy Giặt {brand_name}",
                f"Máy Sấy Quần Áo {brand_name}", f"Điều Hòa {brand_name}", 
                f"Máy Rửa Bát {brand_name}", f"Robot Hút Bụi {brand_name}", f"Khóa Cửa {brand_name}"
            ]
        else:
            defaults_head = [f"Dịch vụ {brand_name}", f"Sản phẩm {brand_name}", f"Tư vấn {brand_name}"]
        for d in defaults_head:
            d_clean = deduplicate_phrase(d)
            if d_clean.lower() not in seen_head:
                seen_head.add(d_clean.lower())
                head_keywords.append(d_clean)

    # --- 2. TẦNG 2A: SUBCATEGORIES & SEO LANDING PAGES (TRANG ĐÍCH NGÁCH ĐỐI THỦ SEO) ---
    # Bóc tách tất cả các trang đích danh mục con mà đối thủ đang SEO (như Tivi theo từng kích thước inch, Tủ lạnh 4 cánh...)
    subcat_keywords = []
    seen_subcat = set()

    if subcategories:
        for sub in subcategories:
            s_clean = sub.strip()
            sl = s_clean.lower()
            if 4 <= len(s_clean) <= 45 and sl not in seen_subcat:
                seen_subcat.add(sl)
                subcat_keywords.append(s_clean)

    # Quét thêm trực tiếp từ toàn bộ thẻ <a> trên trang đối thủ
    for a in soup.find_all("a", href=True):
        t = a.get_text(" ", strip=True)
        tl = t.lower()
        if 5 <= len(t) <= 45 and not any(j in tl for j in ["chính sách", "tin tức", "liên hệ", "giới thiệu", "hotline", "bảo hành", "xem đường đi"]):
            if any(k in tl for k in ["tivi", "tủ lạnh", "máy giặt", "máy sấy", "điều hòa", "rửa bát", "robot", "khóa", "quạt"]):
                # Nhận diện trang ngách inch hoặc dung tích
                if re.search(r"\b(?:\d{2,3}\s*(?:inch|\"|in)|\d{3,4}\s*l|\d{1,2}\s*kg|4\s*cánh|2\s*cánh|inverter|cây|nằm)\b", tl):
                    if tl not in seen_subcat:
                        seen_subcat.add(tl)
                        subcat_keywords.append(t)

    # Đảm bảo có đầy đủ các landing page phân khúc kích thước màn hình phổ biến nếu đối thủ bán tivi
    if any("tivi" in h.lower() for h in head_keywords) and len([s for s in subcat_keywords if "inch" in s.lower()]) < 4:
        for inch in ["43 inch", "50 inch", "55 inch", "65 inch", "75 inch", "85 inch", "100 inch"]:
            kw_inch = f"Tivi {brand_name} {inch}"
            if kw_inch.lower() not in seen_subcat:
                seen_subcat.add(kw_inch.lower())
                subcat_keywords.append(kw_inch)

    # --- 3. TẦNG 2B: SECONDARY KEYWORDS (DÒNG MODEL & THÔNG SỐ KỸ THUẬT THỰC CHIẾN) ---
    # Bóc tách trực tiếp từ thẻ H2, H3, H4 và các thẻ có class sản phẩm trên website đối thủ
    secondary_candidates = []
    raw_product_texts = []
    for h in soup.find_all(["h2", "h3", "h4"]):
        t = h.get_text(" ", strip=True)
        if t and 8 <= len(t) <= 75:
            raw_product_texts.append(t)

    for a in soup.find_all("a"):
        classes = " ".join(a.get("class", []))
        if any(cls_key in classes.lower() for cls_key in ["title", "name", "product", "item", "pro-name"]):
            t = a.get_text(" ", strip=True)
            if t and 10 <= len(t) <= 70:
                raw_product_texts.append(t)

    seen_sec = set()
    for text in raw_product_texts:
        t = text.strip()
        tl = t.lower()
        if any(j in tl for j in ["chính sách", "tin tức", "liên hệ", "giới thiệu", "hotline", "bảo hành"]):
            continue

        has_spec = bool(re.search(r"\b(?:\d{2,3}\s*(?:inch|\"|in)|qled|oled|mini led|4k|inverter|btu|\d{2,4}\s*l|\d{1,2}\s*kg|\d{1,2}\s*bộ|redmi|mijia|pro|max|ultra|plus)\b", tl))
        has_category = any(k in tl for k in ["tivi", "tủ lạnh", "máy giặt", "máy sấy", "điều hòa", "máy rửa bát", "robot", "khóa", "quạt"])

        if has_spec or has_category:
            cleaned_model = re.sub(r"\s*-\s*Quốc\s*Tế\b", "", t, flags=re.I)
            cleaned_model = re.sub(r"\s*\([^\)]+\)", "", cleaned_model)
            cleaned_model = re.sub(r"\s+Model\s+\d{4}\b", "", cleaned_model, flags=re.I)
            cleaned_model = re.sub(r"\s*-\s*Phiên\s+Bản[^\n\r,]+", "", cleaned_model, flags=re.I)
            cleaned_model = cleaned_model.strip()

            c_lower = cleaned_model.lower()
            if 8 <= len(cleaned_model) <= 55 and c_lower not in seen_sec and c_lower not in seen_subcat:
                if len(cleaned_model.split()) >= 3:
                    seen_sec.add(c_lower)
                    secondary_candidates.append(cleaned_model)

    secondary_keywords = [deduplicate_phrase(k) for k in secondary_candidates[:25]]
    if len(secondary_keywords) < 8:
        if is_xiaomi:
            defaults_sec = [
                f"Tivi {brand_name} Redmi A Pro 75 inch", f"Tivi {brand_name} A Pro 65 QLED",
                f"Tủ Lạnh {brand_name} Mijia 606L 4 Cánh", f"Máy Giặt {brand_name} 10kg Inverter"
            ]
        elif is_story:
            defaults_sec = [f"{h} mới cập nhật" for h in head_keywords[:3]] + [f"{h} hay nhất" for h in head_keywords[:3]] + [f"{h} tuyển chọn" for h in head_keywords[:3]]
        else:
            defaults_sec = [f"{h} mới nhất" for h in head_keywords[:3]] + [f"{h} nổi bật" for h in head_keywords[:3]]
        for d in defaults_sec:
            d_clean = deduplicate_phrase(d)
            if d_clean.lower() not in seen_sec:
                seen_sec.add(d_clean.lower())
                secondary_keywords.append(d_clean)

    # --- 4. TẦNG 3: LONG-TAIL INTENT KEYWORDS ---
    longtail_keywords = []
    if is_xiaomi:
        intent_templates = [
            ("bảng giá {head} mới nhất tại kho", 3), ("đánh giá {head} có tốt và bền không", 2),
            ("mua {head} chính hãng giá sỉ trả góp 0%", 2), ("{sec} giá bao nhiêu tại kho", 2)
        ]
    elif is_story:
        intent_templates = [
            ("đọc {head} online hay nhất", 3), ("danh sách {head} mới cập nhật", 2),
            ("hướng dẫn theo dõi {head} hot", 2), ("top {head} tuyển chọn 2026", 2)
        ]
    else:
        intent_templates = [
            ("bảng giá {head} mới nhất", 3), ("tư vấn {head} uy tín chuyên nghiệp", 2),
            ("hướng dẫn lựa chọn {head} tốt nhất", 2), ("top {head} nổi bật 2026", 2)
        ]

    for tmpl, max_count in intent_templates:
        count = 0
        if "{head}" in tmpl:
            for h in head_keywords[:4]:
                kw = deduplicate_phrase(tmpl.format(head=h.lower()))
                if kw not in longtail_keywords:
                    longtail_keywords.append(kw)
                    count += 1
                    if count >= max_count:
                        break
        elif "{sec}" in tmpl:
            for s in secondary_keywords[:3]:
                kw = deduplicate_phrase(tmpl.format(sec=s.lower()))
                if kw not in longtail_keywords:
                    longtail_keywords.append(kw)
                    count += 1
                    if count >= max_count:
                        break

    # --- 5. TỔNG HỢP DANH SÁCH PHẲNG TOÀN DIỆN (FLAT EXTRACTED KEYWORDS) ---
    flat_extracted = []
    def _add_flat(lst):
        for item in lst:
            il = item.strip().lower()
            if il not in [k.lower() for k in flat_extracted] and len(il) >= 4:
                flat_extracted.append(il)

    _add_flat(head_keywords)
    _add_flat(subcat_keywords)
    _add_flat(secondary_keywords)
    _add_flat(longtail_keywords)
    _add_flat(meta_keywords)

    return {
        "clusters": {
            "head": head_keywords[:12],
            "subcategories": subcat_keywords[:15],
            "secondary": secondary_keywords[:25],
            "longtail": longtail_keywords[:15],
            "meta_keywords": meta_keywords[:15]
        },
        "flat_list": flat_extracted,
        "total_keywords_count": len(flat_extracted)
    }

def clean_category_name(text: str, preserve_specs: bool = False) -> Optional[str]:
    """Lọc sạch tên danh mục: loại bỏ rác email, số điện thoại, trang chính sách."""
    if not text:
        return None
    t, tl = text.strip(), text.strip().lower()
    if "@" in t or "email" in tl or "tel:" in tl or "hotline" in tl or re.search(r"^(?:0|\+84)[0-9\.\-\s]{9,13}$", t):
        return None
    junk_patterns = [
        "chính sách", "tin tức", "liên hệ", "giới thiệu", "bảo hành", "điều khoản", "catalog", "catalogue",
        "hướng dẫn", "trang chủ", "xem tất cả", "thanh toán", "vận chuyển", "đổi trả", "bảng giá", "báo giá",
        "so sánh", "đánh giá", "đã bán", "giỏ hàng", "đăng nhập", "tài khoản", "xem thêm", "download", "tải về"
    ]
    if any(j in tl for j in junk_patterns) or len(t) < 3 or len(t) > 40:
        return None
    if re.search(r"\b\d+\s*in\s*\d+\b|\d+\s*m[³3]|\b\d+\s*(?:w|kw|mah|kpa|rpm|hz)\b", tl):
        return None
    if not preserve_specs and re.search(r"\b\d{2,3}\s*(?:inch|\"|in)\b", tl) and "tivi" in tl:
        return None
    return t

def extract_pillar_intents_from_description(desc: str) -> List[Dict[str, str]]:
    """Trích xuất ĐỘNG 100% ngành hàng & từ khóa hạt giống từ mô tả kinh doanh (NLP / Rule-based)."""
    if not desc or not desc.strip():
        return []
    raw_chunks = re.split(r"[,;\n\r\t•|/]|(?:\s+(?:và|hoặc|kem|kèm|chuyên)\s+)", desc.strip(), flags=re.IGNORECASE)
    stop_prefixes = ["bán", "chuyên bán", "chuyên", "cung cấp", "phân phối", "kinh doanh", "dịch vụ", "cửa hàng", "shop", "tổng kho", "hệ thống", "đại lý", "chúng tôi", "trang web", "website", "nhận"]
    pillars, seen = [], set()
    for chunk in raw_chunks:
        c = chunk.strip()
        if not c or len(c) < 3 or len(c) > 40:
            continue
        cl = c.lower()
        for pfx in sorted(stop_prefixes, key=len, reverse=True):
            if cl.startswith(pfx + " "):
                cl = cl[len(pfx):].strip()
                break
        if not cl or len(cl) < 3 or cl in seen:
            continue
        seen.add(cl)
        pillars.append({"keyword": cl, "category": " ".join(w.capitalize() for w in cl.split())})
    return pillars
