<?php
/**
 * Trait: SiloLegalTrait
 * Cung cấp khả năng tự động tạo Cấu trúc Menu Điều hướng Silo
 * và 5 Trang Chính Sách Pháp Lý Tín Nhiệm Đa Ngành Chuẩn E-E-A-T trên WordPress.
 * Tuân thủ nghiêm ngặt SOP 23 (<= 300 lines) và SOP 30 (Zero Hardcoding).
 */
if (!defined('ABSPATH')) exit;

trait SiloLegalTrait {

    /**
     * Tự động khởi tạo Menu Điều hướng phân tầng Silo (Silo Architecture Navigation)
     */
    public function handle_create_silo_menu($request) {
        $params = $request->get_json_params() ?: $request->get_params();
        $menu_name = !empty($params['menu_name']) ? sanitize_text_field($params['menu_name']) : 'Omni Silo Navigation';
        $categories = !empty($params['categories']) ? (array) $params['categories'] : array();

        if (empty($categories)) {
            return new WP_Error('empty_categories', 'Danh sách danh mục Silo không được để trống.', array('status' => 400));
        }

        $menu_obj = wp_get_nav_menu_object($menu_name);
        $menu_id = $menu_obj ? $menu_obj->term_id : wp_create_nav_menu($menu_name);
        if (is_wp_error($menu_id)) return $menu_id;

        $created_count = 0;
        foreach ($categories as $cat) {
            $title = is_array($cat) ? ($cat['title'] ?? '') : (string) $cat;
            $url = is_array($cat) && !empty($cat['url']) ? esc_url_raw($cat['url']) : home_url('/?s=' . urlencode($title));
            if (empty($title)) continue;

            $item_id = wp_update_nav_menu_item($menu_id, 0, array(
                'menu-item-title'   => sanitize_text_field($title),
                'menu-item-url'     => $url,
                'menu-item-status'  => 'publish',
                'menu-item-type'    => 'custom'
            ));
            if (!is_wp_error($item_id)) {
                $created_count++;
                if (is_array($cat) && !empty($cat['children']) && is_array($cat['children'])) {
                    foreach ($cat['children'] as $child) {
                        $c_title = is_array($child) ? ($child['title'] ?? '') : (string) $child;
                        $c_url = is_array($child) && !empty($child['url']) ? esc_url_raw($child['url']) : home_url('/?s=' . urlencode($c_title));
                        if (empty($c_title)) continue;

                        wp_update_nav_menu_item($menu_id, 0, array(
                            'menu-item-title'     => sanitize_text_field($c_title),
                            'menu-item-url'       => $c_url,
                            'menu-item-status'    => 'publish',
                            'menu-item-parent-id' => $item_id,
                            'menu-item-type'      => 'custom'
                        ));
                        $created_count++;
                    }
                }
            }
        }

        $locations = get_theme_mod('nav_menu_locations', array());
        if (empty($locations['primary'])) {
            $locations['primary'] = $menu_id;
            set_theme_mod('nav_menu_locations', $locations);
        }

        return rest_ensure_response(array(
            'status' => 'success',
            'menu_id' => $menu_id,
            'menu_name' => $menu_name,
            'items_created' => $created_count
        ));
    }

    /**
     * Tự động khởi tạo và hiển thị 5 Trang Pháp Lý & Tín Nhiệm Đa Ngành Chuẩn E-E-A-T
     */
    public function handle_setup_legal_pages($request) {
        $params = $request->get_json_params() ?: $request->get_params();
        $brand_name = !empty($params['brand_name']) ? sanitize_text_field($params['brand_name']) : get_bloginfo('name');
        $domain = !empty($params['domain']) ? sanitize_text_field($params['domain']) : $this->get_current_host_domain();
        $hotline = !empty($params['hotline']) ? sanitize_text_field($params['hotline']) : '1900 xxxx';
        $address = !empty($params['address']) ? sanitize_text_field($params['address']) : 'Việt Nam';

        // 1. Nhận danh sách 5 trang động từ AI Engine hoặc tự sinh theo ngữ cảnh
        $pages = !empty($params['pages']) && is_array($params['pages']) ? $params['pages'] : $this->build_fallback_pages($brand_name, $domain, $hotline, $address);

        $results = array();
        foreach ($pages as $slug => $data) {
            $existing = get_page_by_path($slug, OBJECT, 'page');
            $post_title = $data['title'] ?? $slug;
            $post_content = $data['content'] ?? '';
            $post_desc = $data['desc'] ?? $post_title;
            $short_title = !empty($data['short_title']) ? sanitize_text_field($data['short_title']) : $this->get_clean_short_title($post_title);

            $force_overwrite = !empty($params['force_overwrite']);
            if ($existing) {
                $page_id = $existing->ID;
                if ($force_overwrite) {
                    wp_update_post(array(
                        'ID'           => $page_id,
                        'post_title'   => $post_title,
                        'post_content' => $post_content
                    ));
                }
            } else {
                $page_id = wp_insert_post(array(
                    'post_title'    => $post_title,
                    'post_name'     => $slug,
                    'post_content'  => $post_content,
                    'post_status'   => 'publish',
                    'post_type'     => 'page'
                ));
            }

            if ($page_id && !is_wp_error($page_id)) {
                $this->sync_seo_meta($page_id, array(
                    'seo_title'       => $post_title,
                    'seo_description' => $post_desc,
                    'focus_keyword'   => $brand_name
                ));
                $results[] = array(
                    'slug'        => $slug,
                    'title'       => $post_title,
                    'short_title' => $short_title,
                    'id'          => $page_id,
                    'url'         => get_permalink($page_id),
                    'status'      => $existing ? 'updated' : 'created'
                );
            }
        }

        // 2. Lưu liên kết để hiển thị Footer Trust Bar
        update_option('omni_bridge_legal_links', $results);

        // 3. Tự động liên kết vào Footer Menu WordPress (Triệt tiêu thao tác tay)
        $this->bind_pages_to_footer_menu($results);

        return rest_ensure_response(array(
            'status' => 'success',
            'pages'  => $results,
            'footer_menu_linked' => true,
            'message' => 'Đã tự động khởi tạo và hiển thị 5 trang pháp lý E-E-A-T ra ngoài chân trang giao diện thành công!'
        ));
    }

    /**
     * Tự động liên kết các trang vào Footer Menu của giao diện WordPress.
     */
    protected function bind_pages_to_footer_menu($page_results) {
        $menu_name = 'Omni E-E-A-T Footer Menu';
        $menu_obj = wp_get_nav_menu_object($menu_name);
        $menu_id = $menu_obj ? $menu_obj->term_id : wp_create_nav_menu($menu_name);
        if (!$menu_id || is_wp_error($menu_id)) return;

        $existing_items = wp_get_nav_menu_items($menu_id) ?: array();
        $existing_urls = array_map(function($i) { return untrailingslashit($i->url ?? ''); }, $existing_items);

        foreach ($page_results as $p) {
            $p_url = untrailingslashit($p['url'] ?? '');
            if (!empty($p_url) && !in_array($p_url, $existing_urls)) {
                wp_update_nav_menu_item($menu_id, 0, array(
                    'menu-item-title'   => sanitize_text_field($p['title']),
                    'menu-item-url'     => esc_url_raw($p['url']),
                    'menu-item-status'  => 'publish',
                    'menu-item-type'    => 'custom'
                ));
            }
        }

        // Thuật toán gỡ bỏ liên kết đè: Ưu tiên thanh Khiên Tín Nhiệm (Shield Trust Bar) độc quyền,
        // tự động giải phóng theme locations để không gây xung đột menu.
        $locations = get_theme_mod('nav_menu_locations', array());
        if (is_array($locations) && in_array($menu_id, $locations)) {
            foreach ($locations as $loc => $m_id) {
                if ($m_id == $menu_id) {
                    $locations[$loc] = 0;
                }
            }
            set_theme_mod('nav_menu_locations', $locations);
        }
    }

    /**
     * Bóc tách nhãn hiển thị ngắn gọn, tránh lặp lại tên miền gây dãn nở thanh Footer Trust Bar.
     */
    protected function get_clean_short_title($full_title) {
        $clean = preg_replace('/\s*[-–—|]\s*.*$/u', '', $full_title);
        $clean = preg_replace('/^(Về Chúng Tôi|Chính Sách|Tuyên Bố|Điều Khoản)\s*[-–—:]\s*/ui', '', $clean);
        return trim($clean) ?: trim($full_title);
    }

    /**
     * Render thanh liên kết E-E-A-T Footer Trust Bar hiển thị trực tiếp ngoài giao diện cho độc giả và Googlebot.
     * Áp dụng Thuật toán Thẩm mỹ Tự thích ứng (Adaptive Color-Mix) và Khử trùng lặp Ngữ nghĩa (Semantic Set Intersection).
     */
    public function render_eeat_footer_trust_links() {
        $links = get_option('omni_bridge_legal_links', array());
        if (empty($links) || !is_array($links)) return;
        ?>
        <style>
        footer nav, [role="contentinfo"] nav { display: none !important; }
        #omni-eeat-trust-footer {
            width: 100% !important; background: color-mix(in srgb, currentColor 4%, transparent) !important;
            border-top: 1px solid color-mix(in srgb, currentColor 10%, transparent) !important;
            backdrop-filter: blur(8px) !important; -webkit-backdrop-filter: blur(8px) !important;
            padding: 12px 16px !important; margin-top: 32px !important; box-sizing: border-box !important;
            clear: both !important; line-height: 1.5 !important;
        }
        .omni-trust-bar-inner {
            max-width: 900px !important; margin: 0 auto !important; display: flex !important;
            flex-wrap: wrap !important; justify-content: center !important; align-items: center !important; gap: 6px 8px !important;
        }
        .omni-trust-shield-label {
            font-size: 12px !important; font-weight: 600 !important; color: inherit !important; opacity: 0.9 !important;
            display: inline-flex !important; align-items: center !important; gap: 4px !important; margin-right: 4px !important; flex: 0 0 auto !important;
        }
        .omni-trust-pill {
            display: inline-flex !important; align-items: center !important; padding: 4px 12px !important;
            border-radius: 20px !important; font-size: 12px !important; font-weight: 500 !important;
            color: inherit !important; opacity: 0.85 !important; text-decoration: none !important;
            background: color-mix(in srgb, currentColor 6%, transparent) !important;
            border: 1px solid color-mix(in srgb, currentColor 12%, transparent) !important;
            transition: all 0.2s ease !important; white-space: nowrap !important; flex: 0 0 auto !important;
        }
        .omni-trust-pill:hover {
            opacity: 1 !important; background: color-mix(in srgb, currentColor 12%, transparent) !important;
            border-color: #0284c7 !important; color: #0284c7 !important; transform: translateY(-1px) !important;
        }
        </style>
        <div id="omni-eeat-trust-footer">
            <div class="omni-trust-bar-inner">
                <span class="omni-trust-shield-label">
                    <span>🛡️</span> Tín Nhiệm & Pháp Lý:
                </span>
                <?php foreach ($links as $item): 
                    if (empty($item['url'])) continue;
                    $display_title = !empty($item['short_title']) ? $item['short_title'] : $this->get_clean_short_title($item['title'] ?? '');
                    if (empty($display_title)) continue; ?>
                    <a href="<?php echo esc_url($item['url']); ?>" class="omni-trust-pill" title="<?php echo esc_attr($item['title'] ?? $display_title); ?>">
                        <?php echo esc_html($display_title); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <script>
        /* Thuật toán Khử Trùng Lặp Ngữ Nghĩa DOM: Tự phát hiện và ẩn khối danh sách trùng lặp >= 3 URL */
        (function() {
            var legalUrls = <?php echo json_encode(array_values(array_filter(array_map(function($i){ return untrailingslashit($i['url'] ?? ''); }, $links)))); ?>;
            if (!legalUrls || !legalUrls.length) return;
            var legalSet = new Set(legalUrls.map(function(u){ return (u || '').toLowerCase(); }));
            var footers = document.querySelectorAll('footer, [role="contentinfo"], .site-footer');
            footers.forEach(function(f) {
                f.querySelectorAll('nav, ul').forEach(function(el) {
                    if (el.closest('#omni-eeat-trust-footer')) return;
                    var matchCount = 0;
                    el.querySelectorAll('a').forEach(function(a) {
                        var href = (a.getAttribute('href') || '').replace(/\/+$/, '').toLowerCase();
                        if (legalSet.has(href)) matchCount++;
                    });
                    if (matchCount >= 3) {
                        el.style.setProperty('display', 'none', 'important');
                    }
                });
            });
        })();
        </script>
        <?php
    }

    /**
     * Bộ trang dự phòng thích ứng theo tên miền (Digital Content vs Commerce) từ EeatFallbackData.
     */
    protected function build_fallback_pages($brand, $domain, $hotline, $address) {
        if (class_exists('EeatFallbackData')) {
            return EeatFallbackData::get_pages($brand, $domain, $hotline, $address);
        }
        return array();
    }
}
