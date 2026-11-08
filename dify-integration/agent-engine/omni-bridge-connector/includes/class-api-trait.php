<?php
/**
 * Trait: api_trait
 * Auto-generated from omni-bridge-connector.php refactor.
 * Contains split methods for the Omni_Bridge_Connector class.
 */

trait ApiTrait {
    public function handle_seo_inspect($request) {
        $post_id = absint($request['id']);
        $post = get_post($post_id);
        if (!$post) {
            return new WP_Error('not_found', 'Post không tồn tại.', array('status' => 404));
        }

        $provider = $this->resolve_seo_provider();
        $meta = array();
        foreach ($this->get_seo_meta_map($provider) as $field => $meta_key) {
            $meta[$field] = get_post_meta($post_id, $meta_key, true);
        }

        return rest_ensure_response(array(
            'post_id' => $post_id,
            'provider' => $provider,
            'detected_plugins' => $this->get_detected_seo_plugins(),
            'metadata' => $meta,
            'source' => 'wordpress_post_meta',
        ));
    }

    public function handle_categories($request) {
        $post_type = !empty($request['post_type']) ? sanitize_key($request['post_type']) : 'post';
        $taxonomy = ($post_type === 'product') ? 'product_cat' : 'category';
        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
        ));
        if (is_wp_error($terms)) {
            return $terms;
        }
        $categories = array();
        foreach ($terms as $term) {
            $categories[] = array(
                'id' => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
                'count' => $term->count,
            );
        }
        return rest_ensure_response($categories);
    }

    public function handle_search($request) {
        $s = !empty($request['s']) ? sanitize_text_field($request['s']) : '';
        $post_type = !empty($request['post_type']) ? sanitize_key($request['post_type']) : 'post';
        $args = array(
            'post_type' => $post_type,
            's' => $s,
            'posts_per_page' => 20,
            'post_status' => array('draft', 'publish'),
        );
        $query = new WP_Query($args);
        $results = array();
        if ($query->have_posts()) {
            foreach ($query->posts as $post) {
                $results[] = array(
                    'id' => $post->ID,
                    'title' => get_the_title($post->ID),
                    'permalink' => get_permalink($post->ID),
                    'status' => $post->post_status,
                    'type' => $post->post_type,
                );
            }
        }
        return rest_ensure_response($results);
    }
    
    public function handle_publish($request) {
        $params = $request->get_json_params();
        if (empty($params)) {
            $params = $request->get_params();
        }

        $title = !empty($params['post_title']) ? sanitize_text_field($params['post_title']) : (!empty($params['title']) ? sanitize_text_field($params['title']) : '');
        if (empty($title)) {
            return new WP_Error('missing_title', 'post_title is required.', array('status' => 400));
        }

        $post_type = !empty($params['post_type']) ? sanitize_key($params['post_type']) : 'post';
        $post_content = !empty($params['post_content']) ? wp_kses_post($params['post_content']) : (!empty($params['content']) ? wp_kses_post($params['content']) : '');
        if ($post_type === 'product' && !empty($post_content)) {
            // Tự động làm sạch bảng thông số kỹ thuật thô khỏi nội dung thân bài
            // Vì giao diện theme đã có component Thông số kỹ thuật (#technical-specs) riêng biệt
            $post_content = preg_replace('/<p><strong>Thông số kỹ thuật[^<]*:?<\/strong><\/p>\s*<table>.*?<\/table>/is', '', $post_content);
            $post_content = preg_replace('/<table>\s*<thead>\s*<tr>\s*<th>(?:Thông số|Đặc tính|Tiêu chí)<\/th>.*?<\/table>/is', '', $post_content);
        }
        $post_excerpt = !empty($params['post_excerpt']) ? sanitize_textarea_field($params['post_excerpt']) : '';
        $post_status = (!empty($params['status']) && in_array($params['status'], array('publish', 'draft', 'pending', 'future'))) ? sanitize_key($params['status']) : 'draft';
        $post_name = !empty($params['post_slug']) ? sanitize_title($params['post_slug']) : sanitize_title($title);

        $post_data = array(
            'post_title'   => $title,
            'post_content' => $post_content,
            'post_excerpt' => $post_excerpt,
            'post_status'  => $post_status,
            'post_type'    => $post_type,
            'post_name'    => $post_name
        );

        // Hỗ trợ Lập lịch đăng bài tự động chuẩn WordPress Native (Scheduled Future Post)
        $raw_sched = !empty($params['scheduled_at']) ? $params['scheduled_at'] : (!empty($params['post_date']) ? $params['post_date'] : null);
        if ($raw_sched) {
            $schedule_ts = strtotime($raw_sched);
            if ($schedule_ts && $schedule_ts > time()) {
                $post_data['post_status'] = 'future';
                $gmt_offset = floatval(get_option('gmt_offset', 7));
                $post_data['post_date'] = gmdate('Y-m-d H:i:s', $schedule_ts + (int)($gmt_offset * 3600));
                $post_data['post_date_gmt'] = gmdate('Y-m-d H:i:s', $schedule_ts);
                $post_data['edit_date'] = true;
            }
        }

        if (!empty($params['post_id'])) {
            $post_data['ID'] = intval($params['post_id']);
            $post_id = wp_update_post($post_data, true);
        } else {
            // Tự động tìm xem sản phẩm/bài viết đã tồn tại theo slug hoặc SKU chưa để tránh trùng lặp
            $existing_post = get_page_by_path($post_name, OBJECT, $post_type);
            if (!$existing_post && $post_type === 'product' && !empty($params['product_data']['sku']) && function_exists('wc_get_product_id_by_sku')) {
                $sku_id = wc_get_product_id_by_sku($params['product_data']['sku']);
                if ($sku_id) {
                    $existing_post = get_post($sku_id);
                }
            }
            if ($existing_post) {
                $post_data['ID'] = $existing_post->ID;
                $post_id = wp_update_post($post_data, true);
            } else {
                $post_id = wp_insert_post($post_data, true);
            }
        }

        if (is_wp_error($post_id)) {
            return $post_id;
        }

        // 1. Categories / Custom Taxonomy
        if (empty($params['categories']) && !empty($params['category'])) {
            $params['categories'] = array($params['category']);
        }
        if (!empty($params['categories']) && is_array($params['categories'])) {
            $cat_ids = array();
            $taxonomy = !empty($params['taxonomy']) ? sanitize_key($params['taxonomy']) : (($post_type === 'product') ? 'product_cat' : 'category');
            foreach ($params['categories'] as $cat) {
                if (is_numeric($cat)) {
                    $cat_ids[] = intval($cat);
                } else {
                    $term = term_exists($cat, $taxonomy);
                    if ($term) {
                        $cat_ids[] = is_array($term) ? $term['term_id'] : $term;
                    } else {
                        $new_term = wp_insert_term($cat, $taxonomy);
                        if (!is_wp_error($new_term)) {
                            $cat_ids[] = $new_term['term_id'];
                        }
                    }
                }
            }
            if (!empty($cat_ids)) {
                wp_set_object_terms($post_id, $cat_ids, $taxonomy);
            }
        }

        // 2. Tags / Custom Tag Taxonomy
        if (!empty($params['tags']) && is_array($params['tags'])) {
            $tag_taxonomy = !empty($params['tag_taxonomy']) ? sanitize_key($params['tag_taxonomy']) : (($post_type === 'product') ? 'product_tag' : 'post_tag');
            wp_set_object_terms($post_id, $params['tags'], $tag_taxonomy);
        }

        // 3. Featured Image (Media Sideload)
        $thumbnail_id = 0;
        $thumbnail_url = '';
        if (!empty($params['featured_image_url'])) {
            $img_url = esc_url_raw($params['featured_image_url']);
            $thumbnail_id = $this->sideload_image_to_post($img_url, $post_id, $title);
            if ($thumbnail_id && !is_wp_error($thumbnail_id)) {
                set_post_thumbnail($post_id, $thumbnail_id);
                $thumbnail_url = wp_get_attachment_url($thumbnail_id);
            }
        }

        // 4. SEO Meta: chỉ đồng bộ adapter SEO được cấu hình/phát hiện.
        $seo_sync = array('provider' => 'none', 'synced' => false);
        if (!empty($params['seo_meta']) && is_array($params['seo_meta'])) {
            $seo_sync = $this->sync_seo_meta($post_id, $params['seo_meta']);
        }

        // 5. WooCommerce Meta
        if ($post_type === 'product' && class_exists('WooCommerce')) {
            $product_data = !empty($params['product_data']) && is_array($params['product_data']) ? $params['product_data'] : array();
            if (isset($product_data['regular_price'])) {
                $reg = sanitize_text_field($product_data['regular_price']);
                update_post_meta($post_id, '_regular_price', $reg);
                update_post_meta($post_id, '_price', $reg);
            }
            if (isset($product_data['sale_price']) && $product_data['sale_price'] !== '') {
                $sale = sanitize_text_field($product_data['sale_price']);
                update_post_meta($post_id, '_sale_price', $sale);
                update_post_meta($post_id, '_price', $sale);
            }
            if (!empty($product_data['sku'])) {
                update_post_meta($post_id, '_sku', sanitize_text_field($product_data['sku']));
            }
            $this->sync_woocommerce_product_meta($post_id, $product_data, $title);
        }

        // 6. Custom Meta
        if (!empty($params['custom_meta']) && is_array($params['custom_meta'])) {
            foreach ($params['custom_meta'] as $meta_k => $meta_v) {
                if (is_scalar($meta_v)) {
                    update_post_meta($post_id, sanitize_key($meta_k), sanitize_text_field($meta_v));
                }
            }
        }

        return rest_ensure_response(array(
            'success' => true,
            'post_id' => $post_id,
            'permalink' => get_permalink($post_id),
            'post_status' => get_post_status($post_id),
            'post_type' => $post_type,
            'seo' => $seo_sync,
            'featured_image_id' => $thumbnail_id ? $thumbnail_id : null,
            'featured_image_url' => $thumbnail_url ? $thumbnail_url : null
        ));
    }

}
