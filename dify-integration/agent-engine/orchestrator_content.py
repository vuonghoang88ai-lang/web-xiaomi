#!/usr/bin/env python3
"""Article cleaning, Vietnamese spelling correction, image curation & HTML enrichment."""
import os
import re
from datetime import datetime
from typing import Tuple, Dict, Any, Optional
from scraper_tool import curate_clean_competitor_images, is_watermarked_or_low_quality

CURRENT_DIR = os.path.dirname(os.path.abspath(__file__))
BASE_DIR = os.path.abspath(os.path.join(CURRENT_DIR, ".."))

SPELLING_FIXES = [
    (r'\blao nhà\b', 'lau nhà'),
    (r'\bLao nhà\b', 'Lau nhà'),
    (r'\bLAO NHÀ\b', 'LAU NHÀ'),
    (r'\blao chùi\b', 'lau chùi'),
    (r'\bLao chùi\b', 'Lau chùi'),
    (r'\bcông nghệ lao động kép\b', 'công nghệ lau xoay kép'),
    (r'\blao động kép\b', 'lau xoay kép'),
    (r'\bhút bụi sâu lớp\b', 'hút bụi đa tầng chuyên sâu'),
    (r'\[/chinh-sach-bao-hanh\]\(http', '[Chính sách bảo hành chính hãng 24 tháng](http'),
    (r'\[/chinh-sach-doi-tra\]\(http', '[Chính sách đổi mới 1-1 trong 30 ngày](http'),
    (r'\[/tra-cuu-bao-hanh\]\(http', '[Hệ thống tra cứu bảo hành điện tử](http'),
    (r'\[/chinh-sach-van-chuyen\]\(http', '[Chính sách giao hàng hỏa tốc 2 giờ](http'),
    (r'\[/([a-zA-Z0-9\-]+)\]\(http', r'[Chính sách dịch vụ](http'),
]

def split_and_clean_content(llm_output: str, post_type: str, keyword: str, brand_suffix: str) -> Tuple[str, str, str, str]:
    """Tách bài viết, kịch bản social, trích xuất H1 và chuẩn hóa lỗi chính tả."""
    social_campaign = ""
    if "===TIKTOK_CAMPAIGN===" in llm_output:
        parts = llm_output.split("===TIKTOK_CAMPAIGN===")
        main_body = parts[0].strip()
        social_campaign = parts[1].strip()
    else:
        main_body = llm_output.strip()

    short_description = ""
    article_content = main_body
    if post_type == "product" and "===FULL_DESCRIPTION===" in main_body:
        sub_parts = main_body.split("===FULL_DESCRIPTION===")
        top_part = sub_parts[0]
        article_content = sub_parts[1].strip()
        if "===SHORT_DESCRIPTION===" in top_part:
            short_description = top_part.split("===SHORT_DESCRIPTION===")[1].strip()
    elif post_type == "post" and "===AGENT COPYWRITER===" in main_body:
        article_content = main_body.split("===AGENT COPYWRITER===")[1].strip()

    title_match = re.search(r"^#\s+(.+)$", main_body, re.MULTILINE)
    post_title = title_match.group(1).strip() if title_match else f"{keyword.title()} - {brand_suffix}"
    clean_md_content = re.sub(r"^#\s+.+$\n*", "", article_content, flags=re.MULTILINE).strip()

    for pattern, repl in SPELLING_FIXES:
        post_title = re.sub(pattern, repl, post_title)
        clean_md_content = re.sub(pattern, repl, clean_md_content)

    return post_title, clean_md_content, short_description, social_campaign

def curate_featured_and_gallery(keyword: str, category_name: str, products_found: list) -> Tuple[Optional[str], list, dict]:
    """Thu thập và tuyển chọn ảnh đại diện cùng ảnh gallery sạch từ đối thủ."""
    curated = curate_clean_competitor_images(keyword)
    feat_img = curated.get("hero_image")
    gallery_images = curated.get("gallery_images", [])
    feature_images = curated.get("feature_images", {})

    if not feat_img:
        for p in products_found:
            imgs = p.get("images") or []
            if imgs and isinstance(imgs, list) and len(imgs) > 0 and imgs[0]:
                cand = imgs[0]
                if not is_watermarked_or_low_quality(cand):
                    feat_img = cand
                    break

    kw_l = (keyword + " " + category_name).lower()
    if not feat_img:
        if "tivi" in kw_l or "tv" in kw_l:
            feat_img = "https://images.unsplash.com/photo-1593359677879-a4bb92f829d1?auto=format&fit=crop&w=1200&q=80"
        elif "tủ lạnh" in kw_l or "tu lanh" in kw_l:
            feat_img = "https://images.unsplash.com/photo-1571175443880-49e1d25b2bc5?auto=format&fit=crop&w=1200&q=80"
        elif "lọc không khí" in kw_l or "air purifier" in kw_l:
            feat_img = "https://images.unsplash.com/photo-1585771724684-38269d6639fd?auto=format&fit=crop&w=1200&q=80"
        else:
            feat_img = "https://images.unsplash.com/photo-1526738549149-8e07eca6c147?auto=format&fit=crop&w=1200&q=80"

    return feat_img, gallery_images, feature_images

def convert_md_to_html_with_images(clean_md_content: str, keyword: str, brand_suffix: str, feature_images: dict, gallery_images: list) -> str:
    """Chuyển Markdown sang HTML và chèn ảnh chi tiết tính năng xen kẽ giữa các H2."""
    try:
        import markdown
        html_article_content = markdown.markdown(clean_md_content, extensions=['tables', 'fenced_code', 'nl2br'])
    except Exception:
        html_article_content = clean_md_content

    images_to_embed = []
    if feature_images:
        images_to_embed.extend(list(feature_images.values()))
    if gallery_images:
        images_to_embed.extend(gallery_images)

    if images_to_embed and "</h2>" in html_article_content:
        h2_splits = html_article_content.split("</h2>")
        new_body = ""
        img_idx = 0
        for idx, part in enumerate(h2_splits[:-1]):
            new_body += part + "</h2>\n"
            if img_idx < len(images_to_embed):
                cur_img = images_to_embed[img_idx]
                caption_text = f"Hình ảnh thực tế & chi tiết tính năng của {keyword} chính hãng tại {brand_suffix}"
                new_body += f'<figure class="my-8 text-center"><img src="{cur_img}" alt="{keyword} - {brand_suffix}" class="rounded-2xl shadow-sm mx-auto max-w-full h-auto border border-slate-200" loading="lazy" /><figcaption class="text-xs text-slate-500 mt-2 italic font-medium">{caption_text}</figcaption></figure>\n'
                img_idx += 1
        new_body += h2_splits[-1]
        html_article_content = new_body

    return html_article_content

def stage_featured_image(feat_img: str, keyword: str, category_name: str, brand_suffix: str, project_id: str, brand_logo_path: str, brand_logo_url: str) -> Tuple[Optional[str], Optional[str]]:
    """Xử lý ảnh đại diện qua Image Staging Service và đồng bộ vào thư mục WordPress uploads."""
    if not feat_img:
        return None, None
    try:
        from image_staging_service import process_full_image_pipeline
        clean_slug = re.sub(r'[^a-zA-Z0-9_]', '_', keyword.lower())[:25]
        proc_res = process_full_image_pipeline(
            project_id=project_id or "default_project",
            raw_source=feat_img,
            brand_name=brand_suffix,
            brand_logo_source=brand_logo_path or brand_logo_url,
            slug=clean_slug,
            keyword=keyword,
            category_name=category_name
        )
        staged_img_path = proc_res.get("staged_path")
        staged_img_url = None
        if staged_img_path and os.path.exists(staged_img_path):
            staged_filename = os.path.basename(staged_img_path)
            import shutil
            current_ym = datetime.now().strftime("%Y/%m")
            ym_parts = current_ym.split("/")
            candidate_dirs = [
                f"/var/www/html/wp-content/uploads/{current_ym}",
                f"/home/ubuntu/michinhhang/wp-content/uploads/{current_ym}",
                os.path.abspath(os.path.join(BASE_DIR, "..", "wp-content", "uploads", *ym_parts)),
                os.path.abspath(os.path.join(BASE_DIR, "..", "..", "wp-content", "uploads", *ym_parts))
            ]
            for c_dir in candidate_dirs:
                try:
                    os.makedirs(c_dir, exist_ok=True)
                    shutil.copy2(staged_img_path, os.path.join(c_dir, staged_filename))
                except Exception:
                    pass
            wp_public_url = os.getenv("WP_PUBLIC_URL", os.getenv("TARGET_WEBSITE_URL", "http://localhost:8080")).rstrip("/")
            staged_img_url = f"{wp_public_url}/wp-content/uploads/{current_ym}/{staged_filename}"
        return staged_img_path, staged_img_url
    except Exception as img_err:
        return None, None
