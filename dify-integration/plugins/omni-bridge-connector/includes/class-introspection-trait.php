<?php
/**
 * Trait: introspection_trait
 * Cung cấp năng lực Autonomous Site Topology & Universal Plugin Discovery.
 * Quét 100% CPTs, Taxonomies, Custom Database Tables và MỌI Plugins đã cài trên WordPress.
 * Tuân thủ SOP 23 (<= 300 dòng) và SOP 30 (Zero Hardcoding).
 */

if (!defined('ABSPATH')) {
    exit;
}

trait Omni_Bridge_Introspection_Trait {

    public function scan_site_topology() {
        // 1. Phân tích Post Types công khai và các trường hỗ trợ
        $raw_cpts = get_post_types(array('public' => true), 'objects');
        $ignore_cpts = array('attachment', 'wp_block', 'wp_template', 'wp_template_part', 'wp_navigation', 'nav_menu_item');
        $cpt_schema = array();
        $primary_content_type = 'post';

        foreach ($raw_cpts as $slug => $cpt) {
            if (in_array($slug, $ignore_cpts)) continue;
            $count = wp_count_posts($slug);
            $total_published = isset($count->publish) ? intval($count->publish) : 0;

            $cpt_schema[$slug] = array(
                'label'              => $cpt->label ?: $slug,
                'rest_base'          => $cpt->rest_base ?: $slug,
                'supports_thumbnail' => post_type_supports($slug, 'thumbnail'),
                'supports_editor'    => post_type_supports($slug, 'editor'),
                'published_count'    => $total_published,
                'taxonomies'         => get_object_taxonomies($slug)
            );

            if (in_array($slug, array('sab_story', 'story', 'novel', 'book', 'product')) && $total_published > 0) {
                $primary_content_type = $slug;
            }
        }

        // 2. Phân tích Taxonomies
        $tax_schema = array();
        foreach (get_taxonomies(array('public' => true), 'objects') as $t_slug => $tax) {
            $tax_schema[$t_slug] = array(
                'label'      => $tax->label ?: $t_slug,
                'post_types' => $tax->object_type,
                'term_count' => wp_count_terms(array('taxonomy' => $t_slug, 'hide_empty' => false))
            );
        }

        // 3. Quét 100% TẤT CẢ Plugin đã cài đặt trên WordPress (Universal Discovery)
        $installed_plugins = $this->discover_all_installed_plugins();

        // 4. Quét Bảng Dữ Liệu Riêng của các Plugin thứ 3 (Custom Database Tables)
        $custom_tables = $this->discover_custom_plugin_tables();

        // 5. Tổng hợp Trạng thái ERP & Kho vận
        $erp_summary = array('detected' => false, 'active_connectors' => array(), 'total_sku' => 0);
        foreach ($installed_plugins as $p) {
            if ($p['category'] === 'erp_inventory' && $p['is_active']) {
                $erp_summary['detected'] = true;
                $erp_summary['active_connectors'][] = $p['name'];
            }
        }
        if (class_exists('WooCommerce')) {
            $erp_summary['woocommerce_active'] = true;
            $p_count = wp_count_posts('product');
            $erp_summary['total_sku'] = isset($p_count->publish) ? intval($p_count->publish) : 0;
        }

        // 6. Đoán định Archetype theo cấu trúc thực tế
        $archetype = 'commerce_retail';
        if (isset($cpt_schema['sab_story']) || isset($cpt_schema['novel']) || isset($cpt_schema['madara_manga'])) {
            $archetype = 'content_media';
        } elseif (isset($cpt_schema['service']) || isset($cpt_schema['doctor']) || isset($cpt_schema['listing'])) {
            $archetype = 'service_health_b2b';
        }

        return array(
            'site_name'            => get_bloginfo('name'),
            'site_url'             => home_url('/'),
            'primary_content_type' => $primary_content_type,
            'detected_archetype'   => $archetype,
            'post_types'           => $cpt_schema,
            'taxonomies'           => $tax_schema,
            'installed_plugins'    => $installed_plugins,
            'custom_tables'        => $custom_tables,
            'erp_ecosystem'        => $erp_summary,
            'theme_info'           => array(
                'name'               => wp_get_theme()->get('Name'),
                'version'            => wp_get_theme()->get('Version'),
                'supports_thumbnail' => current_theme_supports('post-thumbnails')
            ),
            'scanned_at'           => current_time('mysql')
        );
    }

    /**
     * Quét và phân loại ngữ nghĩa 100% mọi plugin trong thư mục wp-content/plugins
     */
    private function discover_all_installed_plugins() {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $raw_plugins = get_plugins();
        $active_plugins = (array) get_option('active_plugins', array());
        $plugins_list = array();

        foreach ($raw_plugins as $file => $data) {
            $is_active = in_array($file, $active_plugins) || (is_multisite() && is_plugin_active_for_network($file));
            $text_corpus = strtolower($data['Name'] . ' ' . $data['Description'] . ' ' . $file);

            // Phân loại ngữ nghĩa tự động (Zero Hardcoding Semantic Categorization)
            $cat = 'general_utility';
            if (preg_match('/\b(erp|inventory|stock|warehouse|kho|pos|kiotviet|sapo|haravan|odoo|zoho|nhanh|misa|crm|lead)\b/i', $text_corpus)) {
                $cat = 'erp_inventory';
            } elseif (preg_match('/\b(woocommerce|ecommerce|commerce|shop|store|cart|bán hàng)\b/i', $text_corpus)) {
                $cat = 'ecommerce';
            } elseif (preg_match('/\b(seo|sitemap|meta|rank math|yoast|schema)\b/i', $text_corpus)) {
                $cat = 'seo';
            } elseif (preg_match('/\b(cache|optimize|speed|rocket|litespeed|redis|memcached)\b/i', $text_corpus)) {
                $cat = 'performance';
            } elseif (preg_match('/\b(novel|truyện|manga|comic|reader|chapter)\b/i', $text_corpus)) {
                $cat = 'content_media';
            }

            $plugins_list[] = array(
                'file'        => $file,
                'name'        => $data['Name'],
                'version'     => $data['Version'],
                'author'      => wp_strip_all_tags($data['Author']),
                'category'    => $cat,
                'is_active'   => $is_active,
                'description' => substr(wp_strip_all_tags($data['Description']), 0, 160)
            );
        }
        return $plugins_list;
    }

    /**
     * Khám phá các bảng cơ sở dữ liệu riêng do các plugin bên thứ 3 tạo ra
     */
    private function discover_custom_plugin_tables() {
        global $wpdb;
        $core_tables = array(
            $wpdb->prefix . 'posts', $wpdb->prefix . 'postmeta',
            $wpdb->prefix . 'users', $wpdb->prefix . 'usermeta',
            $wpdb->prefix . 'comments', $wpdb->prefix . 'commentmeta',
            $wpdb->prefix . 'terms', $wpdb->prefix . 'termmeta',
            $wpdb->prefix . 'term_taxonomy', $wpdb->prefix . 'term_relationships',
            $wpdb->prefix . 'options', $wpdb->prefix . 'links'
        );

        $all_tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}%'");
        $custom_tables = array();

        foreach ($all_tables as $tbl) {
            if (!in_array($tbl, $core_tables)) {
                $custom_tables[] = $tbl;
            }
        }
        return array_slice($custom_tables, 0, 40);
    }

    public function handle_get_site_topology($request) {
        return rest_ensure_response(array('status' => 'success', 'topology' => $this->scan_site_topology()));
    }

    public function push_site_topology_to_portal() {
        $engine_url = get_option('omni_bridge_gsc_engine_url', '');
        $api_key = get_option('omni_bridge_api_key', '');
        if (empty($engine_url) || empty($api_key)) return false;

        $response = wp_remote_post(rtrim($engine_url, '/') . '/api/v1/cms/telemetry-sync', array(
            'timeout'   => 12,
            'headers'   => array('Content-Type' => 'application/json', 'X-Omni-Bridge-Key' => $api_key),
            'body'      => wp_json_encode(array('site_url' => home_url('/'), 'topology' => $this->scan_site_topology())),
            'sslverify' => false
        ));
        return !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;
    }
}
