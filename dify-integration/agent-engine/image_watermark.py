#!/usr/bin/env python3
"""Brand watermark, logo resolution from tenant/PostgreSQL, and alpha-blending with drop shadow."""
import os
import uuid
import urllib.request
from PIL import Image, ImageDraw, ImageFont, ImageFilter
from image_storage import BASE_STORAGE_DIR, get_project_storage_dirs

def resolve_brand_logo_file(project_id: str, brand_logo_source: str = None) -> str:
    """
    Xác định và nạp file Logo thương hiệu thực tế:
    1. brand_logo_source (URL hoặc File path cụ thể)
    2. Hồ sơ dự án từ PostgreSQL (projects.brand_logo_path hoặc brand_logo_url)
    3. Không tự dùng logo của tenant khác nếu dự án chưa có logo.
    """
    dirs = get_project_storage_dirs(project_id)
    brand_dir = dirs.get("brand_dir", os.path.join(BASE_STORAGE_DIR, str(project_id), "brand"))
    os.makedirs(brand_dir, exist_ok=True)

    if brand_logo_source:
        if os.path.exists(brand_logo_source):
            return brand_logo_source
        if brand_logo_source.startswith("http://") or brand_logo_source.startswith("https://"):
            cached_logo = os.path.join(brand_dir, "logo.png")
            try:
                req = urllib.request.Request(brand_logo_source, headers={"User-Agent": "Mozilla/5.0"})
                with urllib.request.urlopen(req, timeout=10) as resp:
                    with open(cached_logo, "wb") as f:
                        f.write(resp.read())
                if os.path.exists(cached_logo) and os.path.getsize(cached_logo) > 500:
                    return cached_logo
            except Exception as dl_err:
                print(f"⚠️ Lỗi tải logo từ {brand_logo_source}: {dl_err}")

    try:
        import db_service
        p_intel = db_service.get_project_full_intelligence(project_id)
        if p_intel and p_intel.get("project"):
            proj = p_intel["project"]
            db_path = proj.get("brand_logo_path")
            if db_path and os.path.exists(db_path):
                return db_path
            db_url = proj.get("brand_logo_url")
            if db_url and (db_url.startswith("http://") or db_url.startswith("https://")):
                cached_logo = os.path.join(brand_dir, "logo.png")
                try:
                    req = urllib.request.Request(db_url, headers={"User-Agent": "Mozilla/5.0"})
                    with urllib.request.urlopen(req, timeout=10) as resp:
                        with open(cached_logo, "wb") as f:
                            f.write(resp.read())
                    if os.path.exists(cached_logo) and os.path.getsize(cached_logo) > 500:
                        return cached_logo
                except Exception as dl_err:
                    print(f"⚠️ Lỗi tải logo từ db_url {db_url}: {dl_err}")
    except Exception:
        pass

    return None

def _apply_png_logo(watermark_layer, logo_file, width, height, padding_x, padding_y, position):
    """Gắn logo PNG với soft drop shadow và alpha blending 88%."""
    try:
        with Image.open(logo_file) as l_im:
            logo_rgba = l_im.convert("RGBA")
            lw, lh = logo_rgba.size
            target_lw = max(120, int(width * 0.22))
            aspect = lh / lw
            target_lh = int(target_lw * aspect)
            max_lh = int(height * 0.12)
            if target_lh > max_lh:
                target_lh = max_lh
                target_lw = int(target_lh / aspect)

            resized_logo = logo_rgba.resize((target_lw, target_lh), Image.Resampling.LANCZOS)
            pos_x = padding_x if position == "top-left" else width - target_lw - padding_x
            pos_y = padding_y

            # Shadow mask
            logo_alpha = resized_logo.split()[3]
            shadow_mask = logo_alpha.point(lambda p: int(p * 0.40))
            shadow_img = Image.new("RGBA", (target_lw, target_lh), (0, 0, 0, 255))
            shadow_img.putalpha(shadow_mask)

            pad_s = 10
            shadow_canvas = Image.new("RGBA", (target_lw + pad_s * 2, target_lh + pad_s * 2), (0, 0, 0, 0))
            shadow_canvas.paste(shadow_img, (pad_s, pad_s), shadow_img)
            shadow_blurred = shadow_canvas.filter(ImageFilter.GaussianBlur(radius=3.5))

            watermark_layer.paste(shadow_blurred, (pos_x - pad_s + 2, pos_y - pad_s + 2), shadow_blurred)

            # Alpha blending 88%
            r, g, b, a = resized_logo.split()
            resized_logo.putalpha(a.point(lambda p: int(p * 0.88)))
            watermark_layer.paste(resized_logo, (pos_x, pos_y), resized_logo)
            return True
    except Exception as e:
        print(f"⚠️ Lỗi xử lý logo PNG: {e}, fallback sang typography badge...")
        return False

def _apply_typography_badge(watermark_layer, brand_name, width, padding_x, padding_y):
    """Badge text thay thế khi không có file logo ảnh."""
    draw = ImageDraw.Draw(watermark_layer)
    badge_text = brand_name.upper()
    font_size = max(18, int(width * 0.024))
    try:
        font = ImageFont.truetype("/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf", font_size)
    except Exception:
        font = ImageFont.load_default()

    text_bbox = draw.textbbox((0, 0), badge_text, font=font)
    text_w, text_h = text_bbox[2] - text_bbox[0], text_bbox[3] - text_bbox[1]
    box_w, box_h = text_w + 32, text_h + 18
    box_x = width - box_w - padding_x
    box_y = padding_y

    draw.rounded_rectangle([box_x, box_y, box_x + box_w, box_y + box_h], radius=8, fill=(15, 23, 42, 200), outline=(255, 103, 0, 230), width=2)
    dot_r = 4
    dot_cx = box_x + 15
    dot_cy = box_y + (box_h // 2)
    draw.ellipse([dot_cx - dot_r, dot_cy - dot_r, dot_cx + dot_r, dot_cy + dot_r], fill=(255, 103, 0, 255))
    draw.text((dot_cx + 10, box_y + ((box_h - text_h) // 2) - 1), badge_text, font=font, fill=(255, 255, 255, 245))

def apply_brand_watermark(
    project_id: str,
    image_path: str,
    brand_name: str = "",
    brand_logo_source: str = None,
    output_slug: str = "featured",
    position: str = "top-right"
) -> str:
    """TẦNG 3: Đóng mộc thương hiệu website độc quyền chuẩn SOP 11."""
    dirs = get_project_storage_dirs(project_id)
    logo_file = resolve_brand_logo_file(project_id, brand_logo_source)

    with Image.open(image_path) as im:
        base_img = im.convert("RGBA")
        width, height = base_img.size

        watermark_layer = Image.new("RGBA", (width, height), (0, 0, 0, 0))
        padding_x = max(20, int(width * 0.04))
        padding_y = max(20, int(height * 0.04))

        logo_applied = False
        if logo_file and os.path.exists(logo_file):
            logo_applied = _apply_png_logo(watermark_layer, logo_file, width, height, padding_x, padding_y, position)

        if not logo_applied:
            _apply_typography_badge(watermark_layer, brand_name, width, padding_x, padding_y)

        final_img = Image.alpha_composite(base_img, watermark_layer)
        final_rgb = final_img.convert("RGB")

        out_filename = f"staged_{output_slug}_{uuid.uuid4().hex[:6]}.jpg"
        out_path = os.path.join(dirs["staged_dir"], out_filename)
        final_rgb.save(out_path, format="JPEG", quality=93, optimize=True)
        return out_path
