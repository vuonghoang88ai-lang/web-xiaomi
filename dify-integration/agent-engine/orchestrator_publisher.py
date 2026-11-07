#!/usr/bin/env python3
"""WordPress publishing handlers: Omni Bridge REST API and local Docker CLI fallback."""
import os
import re
import json
import subprocess
import urllib.request
from typing import Tuple, Optional

def publish_via_omni_bridge(bridge_url: str, api_key: str, payload: dict) -> dict:
    """Xuất bản bài viết hoặc sản phẩm lên WordPress thông qua Omni Bridge Connector Plugin."""
    if "/wp-json/omni-bridge/v1/publish" in bridge_url:
        endpoint = bridge_url
    else:
        endpoint = f"{bridge_url.rstrip('/')}/wp-json/omni-bridge/v1/publish"
    data_bytes = json.dumps(payload).encode("utf-8")
    req = urllib.request.Request(
        endpoint,
        data=data_bytes,
        headers={
            "Content-Type": "application/json",
            "X-Omni-Bridge-Key": api_key,
            "User-Agent": "Autonomous-Marketing-Agent/1.0"
        }
    )
    with urllib.request.urlopen(req, timeout=40) as resp:
        return json.loads(resp.read().decode("utf-8"))

def publish_via_sandbox_cli(
    post_title: str, html_content: str, short_desc: str, category_name: str,
    keyword: str, meta_desc: str, rank_math_title: str, post_type: str,
    sale_price: int, orig_price: int, sku: str, featured_img: str
) -> Tuple[Optional[str], str]:
    """Fallback xuất bản nội bộ qua Docker CLI container trong môi trường Sandbox/Dev."""
    is_prod = 1 if post_type == "product" else 0
    import base64
    b64_title = base64.b64encode(post_title.encode('utf-8')).decode('ascii')
    b64_content = base64.b64encode(html_content.encode('utf-8')).decode('ascii')
    b64_short = base64.b64encode(short_desc.encode('utf-8')).decode('ascii')

    php_code = f"""
    require('/var/www/html/wp-load.php');
    $title = base64_decode('{b64_title}');
    $content = base64_decode('{b64_content}');
    $short_desc = base64_decode('{b64_short}');
    $category_name = '{category_name}';
    $focus_kw = '{keyword}';
    $meta_desc = '{meta_desc}';
    $rm_title = '{rank_math_title}';
    $is_product = {is_prod};
    $sale_price = {sale_price};
    $orig_price = {orig_price};
    $sku = '{sku}';
    $featured_img_url = '{featured_img}';

    if ($is_product) {{
        $post_id = wp_insert_post([
            'post_title' => $title, 'post_content' => $content, 'post_excerpt' => $short_desc,
            'post_status' => 'draft', 'post_type' => 'product', 'post_author' => 1
        ]);
        if ($post_id && !is_wp_error($post_id)) {{
            wp_set_object_terms($post_id, $category_name, 'product_cat');
            wp_set_object_terms($post_id, 'simple', 'product_type');
            if ($sale_price > 0) {{ update_post_meta($post_id, '_price', $sale_price); update_post_meta($post_id, '_sale_price', $sale_price); }}
            if ($orig_price > 0) {{ update_post_meta($post_id, '_regular_price', $orig_price); }}
            update_post_meta($post_id, '_sku', $sku);
            update_post_meta($post_id, '_stock_status', 'instock');
            if (!empty($featured_img_url)) {{
                require_once(ABSPATH . 'wp-admin/includes/media.php');
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                $att_id = media_sideload_image($featured_img_url, $post_id, $title, 'id');
                if (!is_wp_error($att_id)) {{ set_post_thumbnail($post_id, $att_id); }}
            }}
            if (function_exists('wc_delete_product_transients')) {{ wc_delete_product_transients($post_id); }}
            update_post_meta($post_id, 'rank_math_title', $rm_title);
            update_post_meta($post_id, 'rank_math_description', $meta_desc);
            update_post_meta($post_id, 'rank_math_focus_keyword', $focus_kw);
            echo "WP_SUCCESS:" . $post_id;
        }} else {{ echo "WP_FAILED"; }}
    }} else {{
        $cat_id = 0;
        $term = get_term_by('name', $category_name, 'category');
        if ($term && !is_wp_error($term)) {{ $cat_id = $term->term_id; }}
        else {{
            $new_cat = wp_insert_term($category_name, 'category');
            if (!is_wp_error($new_cat)) {{ $cat_id = $new_cat['term_id']; }}
        }}
        $post_id = wp_insert_post([
            'post_title' => $title, 'post_content' => $content, 'post_status' => 'draft',
            'post_type' => 'post', 'post_author' => 1, 'post_category' => $cat_id ? [$cat_id] : []
        ]);
        if ($post_id && !is_wp_error($post_id)) {{
            update_post_meta($post_id, 'rank_math_title', $rm_title);
            update_post_meta($post_id, 'rank_math_description', $meta_desc);
            update_post_meta($post_id, 'rank_math_focus_keyword', $focus_kw);
            echo "WP_SUCCESS:" . $post_id;
        }} else {{ echo "WP_FAILED"; }}
    }}
    """
    wp_container = os.environ.get("WP_CONTAINER_NAME", "michinhhang-wordpress-1")
    try:
        res = subprocess.run(["docker", "exec", wp_container, "php", "-r", php_code], capture_output=True, text=True, timeout=20)
        if "WP_SUCCESS:" not in res.stdout:
            alt_container = "michinhhang_wordpress" if wp_container == "michinhhang-wordpress-1" else "michinhhang-wordpress-1"
            res = subprocess.run(["docker", "exec", alt_container, "php", "-r", php_code], capture_output=True, text=True, timeout=20)
        if "WP_SUCCESS:" in res.stdout:
            post_id = res.stdout.split("WP_SUCCESS:")[1].strip()
            return post_id, f"Draft Post ID #{post_id}"
        return None, f"Lỗi tạo bài: {res.stderr or res.stdout}"
    except Exception as e:
        return None, f"Lỗi thực thi docker exec WP: {e}"
