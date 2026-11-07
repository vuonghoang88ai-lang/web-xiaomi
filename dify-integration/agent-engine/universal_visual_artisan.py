#!/usr/bin/env python3
"""
Universal Visual Artisan Engine: Single unified visual asset generator for all business archetypes.
Complies with SOP 23 (<= 300 lines) and SOP 30 (Zero Hardcoding).
"""
import os, logging
from typing import Dict, Any, List, Optional
from visual_artisan_core import save_optimized_asset
from visual_artisan_layouts import (
    render_book_editorial_layout,
    render_commerce_product_layout,
    render_professional_service_layout
)

logger = logging.getLogger("marketing_engine")

def generate_universal_visual_asset(
    title: str,
    archetype: str = "content_media",
    aspect_ratio: str = "auto",
    genre: str = "",
    author: str = "Sưu tầm",
    price: str = "",
    specs: Optional[List[str]] = None,
    doctor: str = "",
    hotline: str = "1900 xxxx",
    domain: str = "example.com",
    output_dir: str = "/tmp",
    slug: str = ""
) -> Dict[str, Any]:
    """
    Tạo nội dung hình ảnh chuẩn E-E-A-T đa ngành thích ứng 100% ngữ cảnh:
    - content_media: Bìa sách, tiểu thuyết, blog (2:3 hoặc 16:9)
    - commerce_retail: Banner sản phẩm, giá kho, bảo hành (1:1 hoặc 16:9)
    - service_health_b2b: Thẻ tín nhiệm chuyên gia, phác đồ y khoa (16:9 hoặc 1:1)
    """
    clean_domain = domain.replace("https://", "").replace("http://", "").rstrip("/")
    clean_title = title.strip() or "Tác Phẩm Tiêu Biểu"

    # 1. Xác định tỷ lệ và kích thước pixel tối ưu theo Archetype
    if aspect_ratio == "auto":
        if archetype == "content_media":
            width, height, resolved_ratio = 600, 900, "2:3"
        elif archetype == "commerce_retail":
            width, height, resolved_ratio = 800, 800, "1:1"
        else:
            width, height, resolved_ratio = 1200, 675, "16:9"
    elif aspect_ratio in ("1:1", "square"):
        width, height, resolved_ratio = 800, 800, "1:1"
    elif aspect_ratio in ("16:9", "banner"):
        width, height, resolved_ratio = 1200, 675, "16:9"
    else:  # 2:3 book
        width, height, resolved_ratio = 600, 900, "2:3"

    # 2. Điều phối Render theo Ma trận Bố cục Ngành Nghề
    if archetype == "commerce_retail":
        img = render_commerce_product_layout(clean_title, price, specs or [], clean_domain, width, height)
        prefix = "product"
        alt_text = f"Sản phẩm {clean_title} - Giá tại kho, bảo hành chính hãng tại {clean_domain}"
    elif archetype == "service_health_b2b":
        img = render_professional_service_layout(clean_title, doctor, hotline, clean_domain, width, height)
        prefix = "service"
        alt_text = f"Dịch vụ {clean_title} - Chuẩn chuyên môn E-E-A-T tại {clean_domain}"
    else:
        img = render_book_editorial_layout(clean_title, genre, author, clean_domain, width, height)
        prefix = "bia"
        alt_text = f"Bìa tác phẩm {clean_title} - Thể loại {genre or 'Hay'} full bộ tại {clean_domain}"

    # 3. Lưu ảnh WebP tối ưu dung lượng < 20KB
    out_name, out_path, size_kb = save_optimized_asset(img, output_dir, prefix, slug or clean_title)

    return {
        "status": "success",
        "file_name": out_name,
        "file_path": out_path,
        "width": width,
        "height": height,
        "aspect_ratio": resolved_ratio,
        "archetype": archetype,
        "file_size_kb": size_kb,
        "alt_text": alt_text
    }

# Backward compatibility alias
def generate_novel_cover(title: str, genre: str = "", author: str = "Sưu tầm", domain: str = "truyen360.top", aspect_ratio: str = "2:3", output_dir: str = "/tmp", slug: str = "") -> Dict[str, Any]:
    return generate_universal_visual_asset(title=title, archetype="content_media", aspect_ratio=aspect_ratio, genre=genre, author=author, domain=domain, output_dir=output_dir, slug=slug)

if __name__ == "__main__":
    print("Test Novel:", generate_universal_visual_asset("Đấu Phá Thương Khung", archetype="content_media", genre="Tiên Hiệp"))
    print("Test Product:", generate_universal_visual_asset("Tivi Xiaomi 55 Inch 4K", archetype="commerce_retail", price="8.490.000đ", specs=["4K UHD", "120Hz", "Google TV"]))
    print("Test Clinic:", generate_universal_visual_asset("Cấy Ghép Implant Nha Khoa", archetype="service_health_b2b", doctor="BS. Nguyễn Văn A"))

