#!/usr/bin/env python3
"""Multi-tenant directory storage, download to internal staging, and responsive image generation."""
import os
import re
import uuid
import urllib.request
from PIL import Image

BASE_STORAGE_DIR = os.path.join(os.path.dirname(os.path.abspath(__file__)), "storage", "projects")

def get_project_storage_dirs(project_id: str) -> dict:
    """Tạo cấu trúc thư mục lưu trữ cục bộ riêng biệt cho từng Dự án (Multi-Tenant Isolation)."""
    clean_pid = re.sub(r"[^a-zA-Z0-9_\-]", "_", str(project_id or "default_project"))
    proj_dir = os.path.join(BASE_STORAGE_DIR, clean_pid)
    raw_dir = os.path.join(proj_dir, "raw")
    cutouts_dir = os.path.join(proj_dir, "cutouts")
    staged_dir = os.path.join(proj_dir, "staged")
    responsive_dir = os.path.join(proj_dir, "responsive")
    brand_dir = os.path.join(proj_dir, "brand")

    for d in [raw_dir, cutouts_dir, staged_dir, responsive_dir, brand_dir]:
        os.makedirs(d, exist_ok=True)

    return {
        "project_dir": proj_dir,
        "raw_dir": raw_dir,
        "cutouts_dir": cutouts_dir,
        "staged_dir": staged_dir,
        "responsive_dir": responsive_dir,
        "brand_dir": brand_dir
    }

def download_image_to_staging(project_id: str, image_url: str, slug: str = "product") -> str:
    """
    Tải ảnh thô từ đối thủ về Kho Lưu Trữ Nội Bộ (Staging) của hệ thống.
    TUYỆT ĐỐI KHÔNG TẢI TRỰC TIẾP VÀO WORDPRESS CỦA KHÁCH HÀNG.
    """
    dirs = get_project_storage_dirs(project_id)
    ext = ".jpg"
    if ".png" in image_url.lower():
        ext = ".png"
    elif ".webp" in image_url.lower():
        ext = ".webp"

    filename = f"raw_{slug[:25]}_{uuid.uuid4().hex[:6]}{ext}"
    local_path = os.path.join(dirs["raw_dir"], filename)

    if image_url.startswith("http"):
        headers = {
            "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36"
        }
        req = urllib.request.Request(image_url, headers=headers)
        with urllib.request.urlopen(req, timeout=12) as resp:
            with open(local_path, "wb") as f:
                f.write(resp.read())
    elif os.path.exists(image_url):
        import shutil
        shutil.copy2(image_url, local_path)
    else:
        raise FileNotFoundError(f"Không thể tải ảnh nguồn hoặc file không tồn tại: {image_url}")

    return local_path

def generate_responsive_sizes(image_path: str, project_id: str, slug: str = "product") -> dict:
    """
    TẦNG 4: Xuất bản 5 kích thước chuẩn SOP 11 (WebP & JPG):
    - 1000px: Master HD
    - 600px: Single Product
    - 300px: Catalog Card
    - 150px: Admin Thumbnail
    - 100px: Gallery Thumbnail
    """
    dirs = get_project_storage_dirs(project_id)
    sizes_spec = {
        "master": (1000, 1000),
        "single": (600, 600),
        "catalog": (300, 300),
        "admin": (150, 150),
        "thumb": (100, 100)
    }

    results = {}
    with Image.open(image_path) as im:
        rgb_im = im.convert("RGB")
        for s_name, (sw, sh) in sizes_spec.items():
            resized = rgb_im.copy()
            resized.thumbnail((sw, sh), Image.Resampling.LANCZOS)

            jpg_name = f"{slug}_{s_name}.jpg"
            webp_name = f"{slug}_{s_name}.webp"
            jpg_path = os.path.join(dirs["responsive_dir"], jpg_name)
            webp_path = os.path.join(dirs["responsive_dir"], webp_name)

            resized.save(jpg_path, format="JPEG", quality=90, optimize=True)
            resized.save(webp_path, format="WEBP", quality=88)

            results[s_name] = {
                "jpg": jpg_path,
                "webp": webp_path,
                "width": resized.width,
                "height": resized.height
            }

    return results
