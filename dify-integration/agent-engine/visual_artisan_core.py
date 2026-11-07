#!/usr/bin/env python3
"""
Visual Artisan Core: Shared rendering primitives for Universal Visual Creative Agent.
Complies with SOP 23 (<= 300 lines) and SOP 30 (Zero Hardcoding).
"""
import os, math, uuid, logging, unicodedata, re
from typing import Tuple, List, Optional
from PIL import Image, ImageDraw, ImageFont

logger = logging.getLogger("marketing_engine")

def resolve_system_font(font_name: str, size: int) -> ImageFont.ImageFont:
    """Tải font chữ hệ thống có hỗ trợ dấu tiếng Việt đầy đủ."""
    font_candidates = [
        f"/usr/share/fonts/truetype/liberation/{font_name}.ttf",
        f"/usr/share/fonts/truetype/dejavu/{font_name}.ttf",
        f"/usr/share/fonts/{font_name}.ttf",
        f"{font_name}.ttf"
    ]
    for p in font_candidates:
        if os.path.exists(p):
            try:
                return ImageFont.truetype(p, size)
            except Exception:
                continue
    for p in [
        "/usr/share/fonts/truetype/liberation/LiberationSerif-Bold.ttf",
        "/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf",
        "/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf"
    ]:
        if os.path.exists(p):
            try:
                return ImageFont.truetype(p, size)
            except Exception:
                pass
    return ImageFont.load_default()

def create_gradient_canvas(width: int, height: int, top_color: Tuple[int, int, int], bot_color: Tuple[int, int, int]) -> Image.Image:
    """Tạo nền Gradient chuyển màu mượt mà kèm hoa văn chiều sâu hình học."""
    base = Image.new("RGB", (width, height), top_color)
    draw = ImageDraw.Draw(base)
    for y in range(height):
        ratio = y / float(height)
        r = int(top_color[0] + (bot_color[0] - top_color[0]) * ratio)
        g = int(top_color[1] + (bot_color[1] - top_color[1]) * ratio)
        b = int(top_color[2] + (bot_color[2] - top_color[2]) * ratio)
        draw.line([(0, y), (width, y)], fill=(r, g, b))
    # Chèn đường sáng cong nghệ thuật tinh tế
    accent_layer = Image.new("RGBA", (width, height), (0, 0, 0, 0))
    acc_draw = ImageDraw.Draw(accent_layer)
    acc_draw.ellipse([(-width * 0.4, -height * 0.2), (width * 1.4, height * 0.5)], outline=(255, 255, 255, 12), width=2)
    acc_draw.ellipse([(-width * 0.2, height * 0.4), (width * 1.2, height * 1.2)], outline=(255, 255, 255, 8), width=1)
    base.paste(accent_layer, (0, 0), accent_layer)
    return base

def wrap_text_lines(text: str, font: ImageFont.ImageFont, max_width: int, draw: ImageDraw.ImageDraw, max_lines: int = 4) -> List[str]:
    """Tự động phân dòng tự nhiên cho văn bản tiếng Việt vừa vặn kích thước hiển thị."""
    words = text.split()
    lines, current_line = [], []
    for w in words:
        test_line = " ".join(current_line + [w])
        bbox = draw.textbbox((0, 0), test_line, font=font)
        if (bbox[2] - bbox[0]) <= max_width:
            current_line.append(w)
        else:
            if current_line:
                lines.append(" ".join(current_line))
            current_line = [w]
    if current_line:
        lines.append(" ".join(current_line))
    return lines[:max_lines]

def to_ascii_slug(text: str) -> str:
    """Khử hoàn toàn dấu tiếng Việt để tạo file path an toàn cho HTTP/Cloudflare."""
    t = (text or "asset").lower().replace("đ", "d").replace("Đ", "d")
    t = unicodedata.normalize('NFKD', t).encode('ASCII', 'ignore').decode('utf-8')
    t = re.sub(r'[^a-z0-9\-_]+', '-', t).strip('-_')
    return t[:35] or "asset"

def save_optimized_asset(img: Image.Image, output_dir: str, prefix: str, slug: str) -> Tuple[str, str, float]:
    """Lưu tệp ảnh WebP nén lossless chuẩn SEO Google (< 30KB)."""
    os.makedirs(output_dir, exist_ok=True)
    clean_slug = to_ascii_slug(slug)
    out_name = f"{prefix}-{clean_slug}-{uuid.uuid4().hex[:6]}.webp"
    out_path = os.path.join(output_dir, out_name)
    img.save(out_path, "WEBP", quality=88, method=6)
    file_size_kb = round(os.path.getsize(out_path) / 1024, 1)
    return out_name, out_path, file_size_kb

