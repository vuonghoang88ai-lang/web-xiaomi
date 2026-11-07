#!/usr/bin/env python3
"""AI visual art direction, scene style detection, and contextual scene composition."""
import os
import json
import uuid
import urllib.request
from PIL import Image, ImageDraw, ImageFilter
from image_storage import get_project_storage_dirs

def reason_visual_scene_blueprint(
    product_name: str,
    category_name: str = "",
    brand_name: str = "",
    target_audience: str = ""
) -> dict:
    """
    AI ĐẠO DIỄN THỊ GIÁC (AI Visual Art Director / Cognitive Scene Reasoner):
    Suy luận sâu về ngữ cảnh sử dụng thực tế của sản phẩm trước khi bóc tách và ghép ảnh.
    """
    router_url = os.environ.get("ROUTER_URL", "http://100.73.214.3:40128/v1/chat/completions")
    router_key = os.environ.get("ROUTER_API_KEY", "")
    model_name = os.environ.get("MODEL_NAME", "combo-creater")

    system_prompt = """Bạn là Giám đốc Nghệ thuật & Đạo diễn Hình ảnh Thương mại (AI Visual Art Director).
Hãy suy luận không gian bối cảnh lý tưởng nhất để trưng bày sản phẩm/dịch vụ này.
Chỉ trả về JSON thuần túy (không codeblock markdown, không giải thích):
{
  "environment_type": "tên không gian tiếng Anh (beach, urban_street, luxury_kitchen, etc.)",
  "scene_description_vi": "mô tả ngắn tiếng Việt về không gian",
  "surface_type": "bề mặt đặt sản phẩm (cát biển, mặt đường nhựa, bàn đá, vỉa hè...)",
  "lighting_vibe": "ánh sáng và thời điểm (golden hour, bright sunlight, neon night...)",
  "human_interaction": "mức độ xuất hiện của nhân vật (none, model_wearing, hands_holding, athlete_running...)",
  "sd_background_prompt": "Prompt tiếng Anh chuẩn nhiếp ảnh thương mại 8k commercial photography để sinh phông nền"
}"""

    payload = {
        "model": model_name,
        "messages": [
            {"role": "system", "content": system_prompt},
            {"role": "user", "content": f"Sản phẩm / Dịch vụ: '{product_name}' | Ngành hàng: '{category_name}' | Thương hiệu: '{brand_name}' | Đối tượng: '{target_audience or 'Đại chúng'}'"}
        ],
        "temperature": 0.3
    }

    if not router_key:
        return {
            "environment_type": "modern_lifestyle",
            "scene_description_vi": "Chưa có cấu hình LLM để suy luận bối cảnh",
            "surface_type": "neutral_surface",
            "lighting_vibe": "natural_daylight",
            "human_interaction": "none",
            "sd_background_prompt": ""
        }
    try:
        req = urllib.request.Request(
            router_url,
            data=json.dumps(payload).encode("utf-8"),
            headers={
                "Authorization": f"Bearer {router_key}",
                "Content-Type": "application/json",
                "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36"
            }
        )
        with urllib.request.urlopen(req, timeout=30) as resp:
            res = json.loads(resp.read().decode("utf-8"))
            raw = res["choices"][0]["message"]["content"].strip()
            if "```" in raw:
                parts = raw.split("```")
                raw = parts[1]
                if raw.startswith("json"):
                    raw = raw[4:]
            return json.loads(raw.strip())
    except Exception:
        return {
            "environment_type": "modern_lifestyle",
            "scene_description_vi": "Không gian hiện đại sang trọng chuẩn thương mại",
            "surface_type": "table_surface",
            "lighting_vibe": "natural_daylight",
            "human_interaction": "none",
            "sd_background_prompt": f"8k commercial product photography, modern luxury setting for {product_name}, clean aesthetic, natural soft light"
        }

def detect_scene_style(keyword: str = "", category_name: str = "") -> str:
    """Tự động nhận diện bối cảnh phù hợp nhất dựa trên ngành hàng và loại sản phẩm."""
    txt = (keyword + " " + category_name).lower()
    if any(k in txt for k in ["tivi", "tv", "màn hình", "soundbar", "loa", "máy chiếu"]):
        return "wall_entertainment"
    elif any(k in txt for k in ["tủ lạnh", "nồi cơm", "nồi chiên", "bếp", "máy rửa bát", "lò vi sóng", "ấm siêu tốc"]):
        return "luxury_kitchen"
    elif any(k in txt for k in ["điện thoại", "tai nghe", "laptop", "đồng hồ", "tablet", "ipad", "bàn phím", "chuột"]):
        return "minimalist_desk"
    elif any(k in txt for k in ["nha khoa", "răng", "mỹ phẩm", "kem", "serum", "y tế", "spa", "chăm sóc da", "son"]):
        return "studio_podium"
    elif any(k in txt for k in ["robot", "hút bụi", "quạt", "lọc không khí", "máy sưởi", "hút ẩm"]):
        return "modern_living_room"
    return "studio_minimalist"

def _draw_scene_background(scene_style: str, cw: int, ch: int) -> Image.Image:
    """Vẽ phông nền studio phù hợp với từng bối cảnh."""
    canvas = Image.new("RGBA", (cw, ch), (255, 255, 255, 255))
    draw = ImageDraw.Draw(canvas)

    if scene_style == "luxury_kitchen":
        wall_h = int(ch * 0.60)
        for y in range(wall_h):
            ratio = y / wall_h
            val = int(245 - ratio * 15)
            draw.line([(0, y), (cw, y)], fill=(val, val + 2, val + 4, 255))
        draw.rectangle([0, wall_h - 6, cw, wall_h], fill=(180, 185, 195, 255))
        counter_h = ch - wall_h
        for y in range(wall_h, ch):
            ratio = (y - wall_h) / counter_h
            val = int(240 - ratio * 20)
            draw.line([(0, y), (cw, y)], fill=(val, val + 1, val + 2, 255))
        draw.arc([100, wall_h + 30, cw - 100, ch + 80], 0, 180, fill=(210, 215, 222, 120), width=2)
        draw.arc([200, wall_h + 80, cw - 50, ch + 120], 10, 160, fill=(218, 222, 228, 90), width=1)

    elif scene_style == "minimalist_desk":
        wall_h = int(ch * 0.65)
        for y in range(wall_h):
            ratio = y / wall_h
            r, g, b = int(35 + ratio * 20), int(40 + ratio * 20), int(50 + ratio * 22)
            draw.line([(0, y), (cw, y)], fill=(r, g, b, 255))
        desk_h = ch - wall_h
        for y in range(wall_h, ch):
            ratio = (y - wall_h) / desk_h
            r, g, b = int(185 - ratio * 30), int(140 - ratio * 25), int(100 - ratio * 20)
            draw.line([(0, y), (cw, y)], fill=(r, g, b, 255))

    elif scene_style == "wall_entertainment":
        for y in range(int(ch * 0.78)):
            ratio = y / ch
            r, g, b = int(238 - ratio * 15), int(240 - ratio * 15), int(245 - ratio * 15)
            draw.line([(0, y), (cw, y)], fill=(r, g, b, 255))
        shelf_y = int(ch * 0.78)
        draw.rectangle([0, shelf_y, cw, ch], fill=(75, 55, 45, 255))
        draw.line([(0, shelf_y), (cw, shelf_y)], fill=(120, 95, 80, 255), width=3)

    elif scene_style == "studio_podium":
        for y in range(ch):
            ratio = y / ch
            val = int(250 - ratio * 12)
            draw.line([(0, y), (cw, y)], fill=(val, val + 2, val + 5, 255))
        podium_w, podium_h = int(cw * 0.75), int(ch * 0.16)
        pod_x0, pod_y0 = (cw - podium_w) // 2, int(ch * 0.76)
        draw.ellipse([pod_x0, pod_y0, pod_x0 + podium_w, pod_y0 + podium_h], fill=(235, 238, 245, 255), outline=(220, 225, 235, 255), width=2)
        draw.ellipse([pod_x0 + 10, pod_y0 + 5, pod_x0 + podium_w - 10, pod_y0 + podium_h - 5], fill=(245, 248, 252, 255))

    else:  # modern_living_room
        wall_h = int(ch * 0.62)
        for y in range(wall_h):
            ratio = y / wall_h
            r, g, b = int(248 * (1 - ratio) + 232 * ratio), int(250 * (1 - ratio) + 238 * ratio), int(252 * (1 - ratio) + 245 * ratio)
            draw.line([(0, y), (cw, y)], fill=(r, g, b, 255))
        draw.rectangle([0, wall_h - 8, cw, wall_h], fill=(210, 215, 225, 255))
        floor_h = ch - wall_h
        for y in range(wall_h, ch):
            ratio = (y - wall_h) / floor_h
            r, g, b = int(235 * (1 - ratio) + 210 * ratio), int(218 * (1 - ratio) + 190 * ratio), int(200 * (1 - ratio) + 170 * ratio)
            draw.line([(0, y), (cw, y)], fill=(r, g, b, 255))
        for line_y in [wall_h + int(floor_h * 0.33), wall_h + int(floor_h * 0.66)]:
            draw.line([(0, line_y), (cw, line_y)], fill=(190, 170, 150, 100), width=1)

    return canvas

def compose_contextual_scene(
    project_id: str,
    cutout_png_path: str,
    scene_style: str = "auto",
    custom_background_path: str = None,
    keyword: str = "",
    category_name: str = "",
    canvas_size: tuple = (1200, 1200),
    slug: str = "scene"
) -> str:
    """TẦNG 2: Tái tạo bối cảnh mới linh hoạt ĐA NGÀNH HÀNG."""
    dirs = get_project_storage_dirs(project_id)
    cw, ch = canvas_size

    if scene_style == "auto":
        scene_style = detect_scene_style(keyword, category_name)

    if custom_background_path and os.path.exists(custom_background_path):
        with Image.open(custom_background_path) as bg_im:
            scene_canvas = bg_im.convert("RGBA").resize((cw, ch), Image.Resampling.LANCZOS)
    else:
        scene_canvas = _draw_scene_background(scene_style, cw, ch)

    with Image.open(cutout_png_path) as prod_im:
        prod_im = prod_im.convert("RGBA")
        bbox = prod_im.getbbox()
        if bbox:
            prod_im = prod_im.crop(bbox)

        target_h = int(ch * 0.72)
        ratio = target_h / prod_im.height
        target_w = int(prod_im.width * ratio)
        if target_w > int(cw * 0.85):
            ratio = (cw * 0.85) / prod_im.width
            target_w = int(prod_im.width * ratio)
            target_h = int(prod_im.height * ratio)

        prod_im = prod_im.resize((target_w, target_h), Image.Resampling.LANCZOS)
        pos_x = (cw - target_w) // 2
        pos_y = int(ch * 0.90) - target_h

        # Shadow layer
        shadow_layer = Image.new("RGBA", (cw, ch), (0, 0, 0, 0))
        s_draw = ImageDraw.Draw(shadow_layer)
        shadow_w = int(target_w * 0.92)
        shadow_h = max(24, int(target_h * 0.08))
        shadow_x0 = pos_x + (target_w - shadow_w) // 2
        shadow_y0 = pos_y + target_h - (shadow_h // 2)

        s_draw.ellipse(
            [shadow_x0 - 15, shadow_y0 - 5, shadow_x0 + shadow_w + 15, shadow_y0 + shadow_h + 10],
            fill=(20, 20, 30, 70)
        )
        s_draw.ellipse(
            [shadow_x0 + 10, shadow_y0, shadow_x0 + shadow_w - 10, shadow_y0 + shadow_h - 2],
            fill=(10, 10, 20, 140)
        )
        shadow_layer = shadow_layer.filter(ImageFilter.GaussianBlur(radius=12))

        scene_canvas = Image.alpha_composite(scene_canvas, shadow_layer)
        scene_canvas.paste(prod_im, (pos_x, pos_y), prod_im)

    out_filename = f"scene_{slug[:25]}_{uuid.uuid4().hex[:6]}.png"
    out_path = os.path.join(dirs["staged_dir"], out_filename)
    scene_canvas.save(out_path, format="PNG")
    return out_path
