<?php
/**
 * Trait: site_repair_trait
 * Cung cấp toàn bộ năng lực tự động hóa sửa lỗi SEO On-Page, Homepage SEO,
 * Post SEO update và chèn thẻ H1 Semantic + Schema E-E-A-T trên WordPress.
 */

if (!defined('ABSPATH')) {
    exit;
}

trait SiteRepairTrait {

    /**
     * Tự động cấu hình SEO Trang Chủ (Title, Tagline, Rank Math, Yoast, H1, Meta Description)
     */
    public function handle_site_seo($request) {
        $params = $request->get_json_params() ?: $request->get_params();

        $site_title = !empty($params['site_title']) ? sanitize_text_field($params['site_title']) : '';
        $tagline = !empty($params['tagline']) ? sanitize_text_field($params['tagline']) : '';
        $homepage_title = !empty($params['homepage_title']) ? sanitize_text_field($params['homepage_title']) : '';
        $homepage_desc = !empty($params['homepage_description']) ? sanitize_textarea_field($params['homepage_description']) : '';
        $homepage_h1 = !empty($params['homepage_h1']) ? sanitize_text_field($params['homepage_h1']) : '';
        $homepage_keywords = !empty($params['homepage_keywords']) ? sanitize_text_field($params['homepage_keywords']) : '';

        $updated_items = array();

        // 1. Cập nhật WordPress Core Settings
        if (!empty($site_title)) {
            update_option('blogname', $site_title);
            $updated_items[] = 'blogname';
        }
        if (!empty($tagline)) {
            update_option('blogdescription', $tagline);
            $updated_items[] = 'blogdescription';
        }

        // 2. Lưu cấu hình H1 và Meta riêng của Omni Bridge làm fallback an toàn
        if (!empty($homepage_h1)) {
            update_option('omni_bridge_homepage_h1', $homepage_h1);
            $updated_items[] = 'homepage_h1';
        }
        if (!empty($homepage_title)) {
            update_option('omni_bridge_homepage_title', $homepage_title);
        }
        if (!empty($homepage_desc)) {
            update_option('omni_bridge_homepage_desc', $homepage_desc);
        }

        // 3. Xử lý trường hợp Trang Chủ là Trang Tĩnh (Static Front Page)
        $front_page_id = (int) get_option('page_on_front');
        if (get_option('show_on_front') === 'page' && $front_page_id > 0) {
            $seo_data = array(
                'seo_title' => $homepage_title ?: $site_title,
                'seo_description' => $homepage_desc ?: $tagline,
                'focus_keyword' => $homepage_keywords,
                'canonical_url' => home_url('/'),
            );
            $this->sync_seo_meta($front_page_id, $seo_data);
            $updated_items[] = 'static_front_page_meta_id_' . $front_page_id;
        }

        // 4. Đồng bộ trực tiếp vào Rank Math Options
        $rm_titles = get_option('rank-math-options-titles', array());
        if (is_array($rm_titles)) {
            if (!empty($homepage_title)) $rm_titles['homepage_title'] = $homepage_title;
            if (!empty($homepage_desc)) $rm_titles['homepage_description'] = $homepage_desc;
            update_option('rank-math-options-titles', $rm_titles);
            $updated_items[] = 'rank_math_homepage_options';
        }

        // 5. Đồng bộ trực tiếp vào Yoast SEO Options
        $wpseo_titles = get_option('wpseo_titles', array());
        if (is_array($wpseo_titles)) {
            if (!empty($homepage_title)) $wpseo_titles['title-home-wpseo'] = $homepage_title;
            if (!empty($homepage_desc)) $wpseo_titles['metadesc-home-wpseo'] = $homepage_desc;
            update_option('wpseo_titles', $wpseo_titles);
            $updated_items[] = 'yoast_homepage_options';
        }

        // 6. Xóa cache LiteSpeed / Object cache để khách truy cập nhận ngay SEO mới
        if (function_exists('litespeed_purge_all')) {
            litespeed_purge_all();
        }
        if (has_action('litespeed_purge_all')) {
            do_action('litespeed_purge_all');
        }
        wp_cache_flush();

        return rest_ensure_response(array(
            'status' => 'success',
            'message' => 'Đã cấu hình toàn diện SEO Trang chủ thành công.',
            'site_title' => get_option('blogname'),
            'tagline' => get_option('blogdescription'),
            'homepage_title' => $homepage_title,
            'homepage_description' => $homepage_desc,
            'homepage_h1' => get_option('omni_bridge_homepage_h1'),
            'updated_items' => $updated_items
        ));
    }

    /**
     * Tự động sửa hoặc cập nhật SEO Meta cho bài viết/trang/sản phẩm bất kỳ
     */
    public function handle_update_post_seo($request) {
        $params = $request->get_json_params() ?: $request->get_params();
        $post_id = !empty($params['post_id']) ? absint($params['post_id']) : 0;

        if (!$post_id && !empty($params['post_url'])) {
            $post_id = url_to_postid($params['post_url']);
        }
        if (!$post_id && !empty($params['post_slug'])) {
            $post = get_page_by_path(sanitize_title($params['post_slug']), OBJECT, array('post', 'page', 'product'));
            if ($post) $post_id = $post->ID;
        }

        if (!$post_id || !get_post($post_id)) {
            return new WP_Error('not_found', 'Không tìm thấy bài viết hoặc trang hợp lệ.', array('status' => 404));
        }

        $seo_data = array();
        if (isset($params['seo_title'])) $seo_data['seo_title'] = sanitize_text_field($params['seo_title']);
        if (isset($params['seo_description'])) $seo_data['seo_description'] = sanitize_textarea_field($params['seo_description']);
        if (isset($params['focus_keyword'])) $seo_data['focus_keyword'] = sanitize_text_field($params['focus_keyword']);
        if (isset($params['canonical_url'])) $seo_data['canonical_url'] = esc_url_raw($params['canonical_url']);

        $sync_result = $this->sync_seo_meta($post_id, $seo_data);

        $post_update = array('ID' => $post_id);
        if (!empty($params['post_title'])) $post_update['post_title'] = sanitize_text_field($params['post_title']);
        if (!empty($params['post_content'])) $post_update['post_content'] = wp_kses_post($params['post_content']);
        if (count($post_update) > 1) {
            wp_update_post($post_update);
        }

        return rest_ensure_response(array(
            'status' => 'success',
            'post_id' => $post_id,
            'permalink' => get_permalink($post_id),
            'seo_sync' => $sync_result
        ));
    }

    /**
     * Hook chèn Thẻ H1 Semantic & Schema JSON-LD vào Trang Chủ nếu theme thiếu
     */
    public function render_homepage_seo_head() {
        if (!is_front_page() && !is_home()) return;

        $site_name = get_bloginfo('name');
        $site_url = home_url('/');
        $desc = get_option('omni_bridge_homepage_desc') ?: get_bloginfo('description');

        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $site_name,
            'url' => $site_url,
            'description' => $desc,
            'potentialAction' => array(
                '@type' => 'SearchAction',
                'target' => $site_url . '?s={search_term_string}',
                'query-input' => 'required name=search_term_string'
            )
        );
        echo "\n<!-- Omni Bridge E-E-A-T Schema -->\n";
        echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "</script>\n";

        if ($this->resolve_seo_provider() === 'none' && !empty($desc)) {
            echo '<meta name="description" content="' . esc_attr($desc) . "\">\n";
        }
    }

    /**
     * Chèn thẻ H1 semantic đảm bảo chuẩn On-Page 100/100 mà không làm vỡ giao diện theme
     */
    public function render_homepage_semantic_h1() {
        if (!is_front_page() && !is_home()) return;

        $h1 = get_option('omni_bridge_homepage_h1', '');
        if (!empty($h1)) {
            echo "\n<!-- Omni Bridge Semantic H1 Tag -->\n";
            echo '<div class="omni-bridge-h1-container" style="position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0;">';
            echo '<h1 class="omni-seo-h1">' . esc_html($h1) . '</h1>';
            echo "</div>\n";
        }
    }

    /**
     * Quét danh sách bài viết / truyện thiếu ảnh đại diện (Missing Featured Images)
     */
    public function handle_get_missing_thumbnails($request) {
        $params = $request->get_params();
        $limit = !empty($params['limit']) ? min(intval($params['limit']), 50) : 20;
        $post_type = !empty($params['post_type']) ? sanitize_key($params['post_type']) : 'any';

        if ($post_type !== 'any') {
            $all_types = array($post_type);
        } else {
            $cpts = get_post_types(array('public' => true, '_builtin' => false), 'names');
            $ignore = array('page', 'attachment', 'wp_block', 'wp_template', 'wp_template_part', 'wp_navigation', 'nav_menu_item', 'sab_chapter', 'chapter', 'manga_chapter', 'episode');
            $valid_cpts = array_values(array_diff($cpts, $ignore));
            $priority = array('sab_story', 'story', 'novel', 'book', 'product', 'post');
            $sorted = array();
            foreach ($priority as $pt) {
                if (in_array($pt, $valid_cpts) || in_array($pt, array('post', 'product'))) {
                    $sorted[] = $pt;
                }
            }
            $all_types = array_unique(array_merge($sorted, $valid_cpts));
            if (empty($all_types)) $all_types = array('post');
        }

        $args = array(
            'post_type'      => $all_types,
            'post_status'    => array('publish', 'draft'),
            'posts_per_page' => $limit,
            'meta_query'     => array(
                array(
                    'key'     => '_thumbnail_id',
                    'compare' => 'NOT EXISTS'
                )
            ),
            'orderby'        => 'date',
            'order'          => 'DESC'
        );

        $query = new WP_Query($args);
        $missing = array();

        foreach ($query->posts as $p) {
            $cats = wp_get_post_categories($p->ID, array('fields' => 'names'));
            $missing[] = array(
                'id'         => $p->ID,
                'title'      => $p->post_title,
                'slug'       => $p->post_name,
                'post_type'  => $p->post_type,
                'categories' => $cats,
                'status'     => $p->post_status,
                'url'        => get_permalink($p->ID)
            );
        }

        return rest_ensure_response(array(
            'status' => 'success',
            'total_missing' => count($missing),
            'items'  => $missing
        ));
    }

    /**
     * Tự động tải nạp và gán ảnh đại diện chuẩn SEO (Media Sideloading & Featured Image Auto-Binding)
     */
    public function handle_set_featured_image($request) {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $params = $request->get_json_params() ?: $request->get_params();
        $post_id = !empty($params['post_id']) ? intval($params['post_id']) : 0;
        $image_url = !empty($params['image_url']) ? esc_url_raw($params['image_url']) : '';
        $alt_text = !empty($params['alt_text']) ? sanitize_text_field($params['alt_text']) : '';

        if (!$post_id || !get_post($post_id)) {
            return new WP_Error('invalid_post', 'post_id không tồn tại hoặc không hợp lệ.', array('status' => 404));
        }
        if (empty($image_url)) {
            return new WP_Error('missing_image', 'image_url là bắt buộc.', array('status' => 400));
        }

        $desc = $alt_text ?: get_the_title($post_id);
        $attach_id = media_sideload_image($image_url, $post_id, $desc, 'id');
        if (is_wp_error($attach_id)) {
            return new WP_Error('sideload_failed', 'Lỗi tải ảnh vào Media Library: ' . $attach_id->get_error_message(), array('status' => 500));
        }

        set_post_thumbnail($post_id, $attach_id);
        if (!empty($alt_text)) {
            update_post_meta($attach_id, '_wp_attachment_image_alt', $alt_text);
        }

        return rest_ensure_response(array(
            'status'        => 'success',
            'post_id'       => $post_id,
            'attachment_id' => $attach_id,
            'thumbnail_url' => wp_get_attachment_url($attach_id),
            'alt_text'      => $alt_text
        ));
    }
}
