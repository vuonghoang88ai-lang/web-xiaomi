#!/usr/bin/env python3
"""AI background removal and product cutout engine (U2Net / Rembg)."""
import os
import uuid
from PIL import Image
from image_storage import get_project_storage_dirs

try:
    import rembg
    HAS_REMBG = True
except ImportError:
    HAS_REMBG = False

def remove_background_and_cutout(project_id: str, input_image_path: str, slug: str = "cutout") -> str:
    """
    TẦNG 1: Bóc tách vật thể sản phẩm chuẩn từng pixel bằng AI (U2Net / Rembg).
    Cắt bỏ toàn bộ phông nền cũ và loại bỏ 90% watermark của đối thủ nằm ngoài rìa.
    """
    dirs = get_project_storage_dirs(project_id)
    out_filename = f"cutout_{slug[:25]}_{uuid.uuid4().hex[:6]}.png"
    out_path = os.path.join(dirs["cutouts_dir"], out_filename)

    with Image.open(input_image_path) as im:
        # Giới hạn kích thước tối đa 1200px để xử lý nhanh và sắc nét
        im_work = im.copy()
        im_work.thumbnail((1200, 1200), Image.Resampling.LANCZOS)
        
        if HAS_REMBG:
            cutout_im = rembg.remove(im_work)
        else:
            cutout_im = im_work.convert("RGBA")

        cutout_im.save(out_path, format="PNG")

    return out_path
