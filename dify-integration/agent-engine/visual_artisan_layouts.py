#!/usr/bin/env python3
"""
Visual Artisan Layouts: Archetype-driven layout rendering dispatchers.
Complies with SOP 23 (<= 300 lines) and SOP 30 (Zero Hardcoding).
"""
import os
from typing import Dict, Any, Tuple, List
from PIL import Image, ImageDraw
from visual_artisan_core import resolve_system_font, create_gradient_canvas, wrap_text_lines

GENRE_PALETTES = {
    "xianxia": {"grad": [(10, 25, 47), (2, 12, 27)], "acc": (64, 224, 208), "tag": "TIÊN HIỆP • BẢN FULL"},
    "wuxia": {"grad": [(30, 22, 16), (12, 8, 6)], "acc": (218, 165, 32), "tag": "KIẾM HIỆP • KỲ ẢO"},
    "romance": {"grad": [(48, 14, 30), (20, 5, 14)], "acc": (244, 143, 177), "tag": "NGÔN TÌNH • SẮC BẢN"},
    "mystery": {"grad": [(18, 18, 24), (8, 8, 10)], "acc": (239, 68, 68), "tag": "TRINH THÁM • KINH DỊ"},
    "urban": {"grad": [(26, 16, 50), (10, 6, 25)], "acc": (167, 139, 250), "tag": "ĐÔ THỊ • DỊ NĂNG"},
    "default": {"grad": [(15, 23, 42), (2, 6, 23)], "acc": (56, 189, 248), "tag": "TRUYỆN HAY CHỌN LỌC"}
}

def detect_novel_genre(genre_str: str, title: str = "") -> str:
    corpus = f"{genre_str} {title}".lower()
    mapping = {
        "xianxia": ["tiên hiệp", "tu chân", "huyền huyễn", "tu tiên"],
        "wuxia": ["kiếm hiệp", "võ hiệp", "võ lâm", "giang hồ"],
        "romance": ["ngôn tình", "trọng sinh", "tổng tài", "điền văn", "sủng"],
        "mystery": ["trinh thám", "kinh dị", "án mạng", "bí mật", "tâm linh"],
        "urban": ["đô thị", "khoa huyễn", "dị năng", "hệ thống", "mạt thế"]
    }
    for k, v in mapping.items():
        if any(w in corpus for w in v): return k
    return "default"

def render_book_editorial_layout(title: str, genre: str, author: str, domain: str, width: int, height: int) -> Image.Image:
    """Bố cục 1: Bìa sách & Bài viết Blog Nội dung số (Archetype content_media)."""
    gk = detect_novel_genre(genre, title)
    pal = GENRE_PALETTES.get(gk, GENRE_PALETTES["default"])
    img = create_gradient_canvas(width, height, pal["grad"][0], pal["grad"][1])
    draw = ImageDraw.Draw(img)

    margin = 24 if width <= 800 else 36
    draw.rectangle([(margin, margin), (width - margin, height - margin)], outline=pal["acc"], width=2)

    # 1. Header Badge
    f_badge = resolve_system_font("LiberationSans-Bold", 14 if width <= 800 else 18)
    badge_txt = f"✦  {genre.upper() if genre else pal['tag']}  ✦"
    bb = draw.textbbox((0, 0), badge_txt, font=f_badge)
    bw, bh = bb[2] - bb[0], bb[3] - bb[1]
    bx, by = (width - bw) // 2, margin + 35
    draw.rounded_rectangle([(bx - 16, by - 6), (bx + bw + 16, by + bh + 8)], radius=14, fill=(15, 23, 42), outline=pal["acc"], width=1)
    draw.text((bx, by), badge_txt, fill=pal["acc"], font=f_badge)

    # 2. Tiêu đề tác phẩm
    f_size = 46 if len(title) <= 20 else (36 if len(title) <= 35 else 30)
    f_title = resolve_system_font("LiberationSerif-Bold", f_size)
    lines = wrap_text_lines(title.title(), f_title, width - (margin * 2 + 50), draw)
    lh = int(f_size * 1.35)
    sy = (height - len(lines) * lh) // 2 - 20
    for i, line in enumerate(lines):
        tbb = draw.textbbox((0, 0), line, font=f_title)
        draw.text(((width - (tbb[2] - tbb[0])) // 2, sy + i * lh), line, fill=(255, 255, 255), font=f_title)

    # 3. Tác giả & Chân trang tín nhiệm
    div_y = sy + len(lines) * lh + 20
    draw.line([(width // 2 - 70, div_y), (width // 2 + 70, div_y)], fill=pal["acc"], width=2)
    f_sub = resolve_system_font("LiberationSans-Regular", 16 if width <= 800 else 20)
    auth_txt = f"Tác giả: {author or 'Sưu tầm'}"
    draw.text(((width - (draw.textbbox((0, 0), auth_txt, font=f_sub)[2] - draw.textbbox((0, 0), auth_txt, font=f_sub)[0])) // 2, div_y + 16), auth_txt, fill=(203, 213, 225), font=f_sub)

    f_dom = resolve_system_font("LiberationSans-Bold", 14 if width <= 800 else 18)
    dom_txt = f"✦ {domain.upper()} • ĐỌC TRUYỆN FULL MIỄN PHÍ ✦"
    draw.text(((width - (draw.textbbox((0, 0), dom_txt, font=f_dom)[2] - draw.textbbox((0, 0), dom_txt, font=f_dom)[0])) // 2, height - margin - 35), dom_txt, fill=pal["acc"], font=f_dom)
    return img

def render_commerce_product_layout(title: str, price: str, specs: List[str], domain: str, width: int, height: int) -> Image.Image:
    """Bố cục 2: Banner Sản phẩm & Giá kho Thương mại (Archetype commerce_retail)."""
    img = create_gradient_canvas(width, height, (15, 23, 42), (2, 6, 23))
    draw = ImageDraw.Draw(img)
    margin = 28 if width <= 800 else 38
    draw.rectangle([(margin, margin), (width - margin, height - margin)], outline=(30, 58, 138), width=2)

    # 1. Header Commercial Guard Badge
    f_badge = resolve_system_font("LiberationSans-Bold", 13 if width <= 800 else 16)
    badge_txt = "✅ BẢO CHỨNG 100% THÔNG SỐ KHO • BẢO HÀNH CHÍNH HÃNG"
    bb = draw.textbbox((0, 0), badge_txt, font=f_badge)
    bx = (width - (bb[2] - bb[0])) // 2
    draw.rounded_rectangle([(bx - 14, margin + 25), (bx + (bb[2] - bb[0]) + 14, margin + 55)], radius=6, fill=(16, 185, 129), outline=(5, 150, 105))
    draw.text((bx, margin + 31), badge_txt, fill=(255, 255, 255), font=f_badge)

    # 2. Tên Sản Phẩm / Model
    f_title = resolve_system_font("LiberationSans-Bold", 40 if len(title) <= 25 else 32)
    lines = wrap_text_lines(title.upper(), f_title, width - (margin * 2 + 40), draw, max_lines=2)
    sy = margin + 110
    for i, line in enumerate(lines):
        tbb = draw.textbbox((0, 0), line, font=f_title)
        draw.text(((width - (tbb[2] - tbb[0])) // 2, sy + i * 48), line, fill=(248, 250, 252), font=f_title)

    # 3. Huy hiệu Giá kho độc quyền (Price Highlight Pill)
    price_val = price or "GIÁ SỈ TẠI KHO"
    f_pr = resolve_system_font("LiberationSans-Bold", 34 if width <= 800 else 42)
    pr_bb = draw.textbbox((0, 0), price_val, font=f_pr)
    pr_w = pr_bb[2] - pr_bb[0]
    pr_y = sy + len(lines) * 48 + 35
    draw.rounded_rectangle([(width // 2 - pr_w // 2 - 25, pr_y - 8), (width // 2 + pr_w // 2 + 25, pr_y + 50)], radius=12, fill=(225, 29, 72), outline=(244, 63, 94), width=2)
    draw.text(((width - pr_w) // 2, pr_y), price_val, fill=(255, 255, 255), font=f_pr)

    # 4. Thông số kỹ thuật chủ lực (Specs Grid)
    f_sp = resolve_system_font("LiberationSans-Regular", 16 if width <= 800 else 20)
    spec_txt = " • ".join(specs[:4]) if specs else "Chính Hãng 100% • Sẵn Hàng Tại Kho • Lắp Đặt 2H"
    sbb = draw.textbbox((0, 0), spec_txt, font=f_sp)
    draw.text(((width - (sbb[2] - sbb[0])) // 2, pr_y + 85), spec_txt, fill=(148, 163, 184), font=f_sp)

    # 5. Footer Brand Trust
    f_dom = resolve_system_font("LiberationSans-Bold", 14 if width <= 800 else 18)
    dom_txt = f"✦ {domain.upper()} • TỔNG KHO PHÂN PHỐI CHÍNH HÃNG ✦"
    draw.text(((width - (draw.textbbox((0, 0), dom_txt, font=f_dom)[2] - draw.textbbox((0, 0), dom_txt, font=f_dom)[0])) // 2, height - margin - 35), dom_txt, fill=(56, 189, 248), font=f_dom)
    return img

def render_professional_service_layout(title: str, doctor: str, hotline: str, domain: str, width: int, height: int) -> Image.Image:
    """Bố cục 3: Banner Dịch vụ Chuyên Môn & Y Tế / B2B (Archetype service_health_b2b)."""
    img = create_gradient_canvas(width, height, (13, 42, 60), (4, 18, 30))
    draw = ImageDraw.Draw(img)
    margin = 28 if width <= 800 else 38
    draw.rectangle([(margin, margin), (width - margin, height - margin)], outline=(20, 184, 166), width=2)

    # 1. Header E-E-A-T Medical & Legal Trust
    f_badge = resolve_system_font("LiberationSans-Bold", 13 if width <= 800 else 16)
    badge_txt = "🏛️ CHỨNG NHẬN CHUYÊN MÔN E-E-A-T • TIÊU CHUẨN Y KHOA QUỐC TẾ"
    bb = draw.textbbox((0, 0), badge_txt, font=f_badge)
    bx = (width - (bb[2] - bb[0])) // 2
    draw.rounded_rectangle([(bx - 14, margin + 25), (bx + (bb[2] - bb[0]) + 14, margin + 55)], radius=6, fill=(13, 148, 136), outline=(45, 212, 191))
    draw.text((bx, margin + 31), badge_txt, fill=(255, 255, 255), font=f_badge)

    # 2. Tên Phác đồ / Dịch vụ
    f_title = resolve_system_font("LiberationSerif-Bold", 42 if len(title) <= 25 else 34)
    lines = wrap_text_lines(title.upper(), f_title, width - (margin * 2 + 50), draw, max_lines=2)
    sy = margin + 120
    for i, line in enumerate(lines):
        tbb = draw.textbbox((0, 0), line, font=f_title)
        draw.text(((width - (tbb[2] - tbb[0])) // 2, sy + i * 50), line, fill=(240, 253, 250), font=f_title)

    # 3. Đội ngũ Chuyên gia / Bác sĩ
    doc_txt = f"Chuyên gia phụ trách: {doctor or 'Đội ngũ Thạc sĩ - Bác sĩ Chuyên khoa'}"
    f_doc = resolve_system_font("LiberationSans-Regular", 18 if width <= 800 else 22)
    dbb = draw.textbbox((0, 0), doc_txt, font=f_doc)
    draw.text(((width - (dbb[2] - dbb[0])) // 2, sy + len(lines) * 50 + 35), doc_txt, fill=(153, 246, 228), font=f_doc)

    # 4. Footer Hotline & Đặt lịch
    f_dom = resolve_system_font("LiberationSans-Bold", 14 if width <= 800 else 18)
    hl_txt = f"✦ HOTLINE TƯ VẤN: {hotline or '1900 xxxx'} • {domain.upper()} ✦"
    draw.text(((width - (draw.textbbox((0, 0), hl_txt, font=f_dom)[2] - draw.textbbox((0, 0), hl_txt, font=f_dom)[0])) // 2, height - margin - 35), hl_txt, fill=(45, 212, 191), font=f_dom)
    return img

