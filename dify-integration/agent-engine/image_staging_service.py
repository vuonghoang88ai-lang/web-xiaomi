#!/usr/bin/env python3
"""
Tầng Xử Lý & Lưu Trữ Hình Ảnh Độc Lập Chuẩn SaaS Thương Mại (SOP 23 Facade).
Re-exports functionality decomposed into:
  - image_storage.py: Storage directories, image download & responsive sizes
  - image_rembg.py: AI background removal (U2Net / Rembg)
  - image_scene.py: Cognitive scene reasoning & contextual scene composition
  - image_watermark.py: Brand logo resolution & SOP 11 watermarking
"""
import sys
from image_storage import (
    BASE_STORAGE_DIR,
    get_project_storage_dirs,
    download_image_to_staging,
    generate_responsive_sizes,
)
from image_rembg import (
    HAS_REMBG,
    remove_background_and_cutout,
)
from image_scene import (
    reason_visual_scene_blueprint,
    detect_scene_style,
    compose_contextual_scene,
)
from image_watermark import (
    resolve_brand_logo_file,
    apply_brand_watermark,
)

__all__ = [
    "BASE_STORAGE_DIR",
    "HAS_REMBG",
    "get_project_storage_dirs",
    "download_image_to_staging",
    "generate_responsive_sizes",
    "remove_background_and_cutout",
    "reason_visual_scene_blueprint",
    "detect_scene_style",
    "compose_contextual_scene",
    "resolve_brand_logo_file",
    "apply_brand_watermark",
    "process_full_image_pipeline",
]

def process_full_image_pipeline(
    project_id: str,
    raw_source: str,
    brand_name: str = "",
    brand_logo_source: str = None,
    slug: str = "product",
    scene_style: str = "auto",
    custom_background_path: str = None,
    keyword: str = "",
    category_name: str = ""
) -> dict:
    """
    CHẠY TRỌN VẸN CHU TRÌNH ĐỒ HỌA THƯƠNG MẠI (ĐA NGÀNH HÀNG):
    1. Tải về kho Staging nội bộ
    2. AI bóc tách phông nền cũ của đối thủ
    3. Tự động nhận diện bối cảnh phù hợp ngành hàng (Bếp, Bàn làm việc, Vách TV, Phòng khách, Podium)
    4. Ghép vào bối cảnh mới có đổ bóng tiếp xúc chân thực
    5. Đóng mộc logo thương hiệu độc quyền (SOP 11: Logo PNG + Alpha Blending + Soft Drop Shadow)
    6. Xuất 5 kích cỡ Responsive chuẩn SEO
    """
    if not raw_source:
        return {"status": "error", "message": "Nguồn ảnh (raw_source) không được để trống"}
    raw_path = download_image_to_staging(project_id, raw_source, slug=slug)
    cutout_path = remove_background_and_cutout(project_id, raw_path, slug=slug)
    scene_path = compose_contextual_scene(
        project_id=project_id,
        cutout_png_path=cutout_path,
        scene_style=scene_style,
        custom_background_path=custom_background_path,
        keyword=keyword,
        category_name=category_name,
        slug=slug
    )
    staged_path = apply_brand_watermark(
        project_id=project_id,
        image_path=scene_path,
        brand_name=brand_name,
        brand_logo_source=brand_logo_source,
        output_slug=slug
    )
    responsive_data = generate_responsive_sizes(staged_path, project_id, slug=slug)

    return {
        "status": "success",
        "project_id": project_id,
        "raw_path": raw_path,
        "cutout_path": cutout_path,
        "scene_path": scene_path,
        "staged_path": staged_path,
        "responsive": responsive_data
    }

if __name__ == "__main__":
    if len(sys.argv) < 3:
        raise SystemExit("Usage: image_staging_service.py <project_id> <image_path> [brand_name]")
    project_id = sys.argv[1]
    src = sys.argv[2]
    brand_name = sys.argv[3] if len(sys.argv) > 3 else ""
    print("Running Full Image Pipeline Test on:", src)
    res = process_full_image_pipeline(project_id, src, brand_name=brand_name, slug="featured")
    print("Pipeline Complete! Staged File:", res["staged_path"])
