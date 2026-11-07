<?php
/**
 * Trait: routes_trait
 * Auto-generated from omni-bridge-connector.php refactor.
 * Contains split methods for the Omni_Bridge_Connector class.
 */

trait RoutesTrait {
    public function register_routes() {
        register_rest_route(self::REST_NAMESPACE, '/health', array(
            'methods' => 'GET',
            'callback' => array($this, 'handle_health_check'),
            'permission_callback' => array($this, 'verify_api_key')
        ));

        register_rest_route(self::REST_NAMESPACE, '/seo/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'handle_seo_inspect'),
            'permission_callback' => array($this, 'verify_api_key')
        ));

        register_rest_route(self::REST_NAMESPACE, '/gsc/config', array(
            'methods' => 'GET',
            'callback' => array($this, 'handle_gsc_config'),
            'permission_callback' => array($this, 'verify_api_key')
        ));

        register_rest_route(self::REST_NAMESPACE, '/gsc/performance', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_gsc_performance'),
            'permission_callback' => array($this, 'verify_api_key')
        ));

        register_rest_route(self::REST_NAMESPACE, '/categories', array(
            'methods' => 'GET',
            'callback' => array($this, 'handle_categories'),
            'permission_callback' => array($this, 'verify_api_key'),
            'args' => array(
                'post_type' => array(
                    'required' => false,
                    'default' => 'post',
                    'type' => 'string',
                ),
            ),
        ));

        register_rest_route(self::REST_NAMESPACE, '/search', array(
            'methods' => 'GET',
            'callback' => array($this, 'handle_search'),
            'permission_callback' => array($this, 'verify_api_key'),
            'args' => array(
                's' => array(
                    'required' => false,
                    'default' => '',
                    'type' => 'string',
                ),
                'post_type' => array(
                    'required' => false,
                    'default' => 'post',
                    'type' => 'string',
                ),
            ),
        ));

        register_rest_route(self::REST_NAMESPACE, '/publish', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_publish'),
            'permission_callback' => array($this, 'verify_api_key')
        ));

        register_rest_route(self::REST_NAMESPACE, '/homepage-seo', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_site_seo'),
            'permission_callback' => array($this, 'verify_api_key')
        ));

        register_rest_route(self::REST_NAMESPACE, '/update-seo', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_update_post_seo'),
            'permission_callback' => array($this, 'verify_api_key')
        ));

        register_rest_route(self::REST_NAMESPACE, '/silo-menu', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_create_silo_menu'),
            'permission_callback' => array($this, 'verify_api_key')
        ));

        register_rest_route(self::REST_NAMESPACE, '/legal-pages', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_setup_legal_pages'),
            'permission_callback' => array($this, 'verify_api_key')
        ));

        register_rest_route(self::REST_NAMESPACE, '/missing-thumbnails', array(
            'methods' => 'GET',
            'callback' => array($this, 'handle_get_missing_thumbnails'),
            'permission_callback' => array($this, 'verify_api_key')
        ));

        register_rest_route(self::REST_NAMESPACE, '/set-featured-image', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_set_featured_image'),
            'permission_callback' => array($this, 'verify_api_key')
        ));

        register_rest_route(self::REST_NAMESPACE, '/topology', array(
            'methods' => 'GET',
            'callback' => array($this, 'handle_get_site_topology'),
            'permission_callback' => array($this, 'verify_api_key')
        ));

        register_rest_route(self::REST_NAMESPACE, '/interop/execute', array(
            'methods' => 'POST',
            'callback' => array($this, 'handle_interop_execute'),
            'permission_callback' => array($this, 'verify_api_key')
        ));
    }

    public function verify_api_key($request) {
        $configured_key = get_option(self::OPTION_KEY, '');
        $header_key = $request->get_header('x-omni-bridge-key') ?: ($request->get_header('x_omni_bridge_key') ?: '');
        if (empty($header_key) && !empty($_SERVER['HTTP_X_OMNI_BRIDGE_KEY'])) {
            $header_key = sanitize_text_field(wp_unslash($_SERVER['HTTP_X_OMNI_BRIDGE_KEY']));
        }
        if (empty($header_key)) {
            $auth_h = $request->get_header('authorization') ?: '';
            if (preg_match('/Bearer\s+(\S+)/i', $auth_h, $matches)) {
                $header_key = $matches[1];
            }
        }
        $header_key = trim($header_key);

        $license_header = $request->get_header('x-omni-license-key') ?: ($request->get_header('x_omni_license_key') ?: '');
        if (empty($license_header) && !empty($_SERVER['HTTP_X_OMNI_LICENSE_KEY'])) {
            $license_header = sanitize_text_field(wp_unslash($_SERVER['HTTP_X_OMNI_LICENSE_KEY']));
        }
        $configured_lic = get_option('omni_bridge_license_key', '');

        // SOP 48 Master Handshake: Tự động bắt tay nếu request mang License Key chuẩn của dự án
        $authorized = false;
        if (!empty($configured_lic) && !empty($license_header) && hash_equals($configured_lic, trim($license_header))) {
            if (!empty($header_key) && $header_key !== $configured_key) {
                update_option(self::OPTION_KEY, $header_key);
                $configured_key = $header_key;
            }
            $authorized = true;
        } elseif (!empty($header_key) && !empty($configured_key) && hash_equals($configured_key, $header_key)) {
            $authorized = true;
        }

        // Tự động gán API Key nếu web chưa lưu key
        if (!$authorized && empty($configured_key) && !empty($header_key) && strpos($header_key, 'obc_') === 0) {
            update_option(self::OPTION_KEY, $header_key);
            $authorized = true;
        }

        // SOP 48: Tự động lưu License Key khi nhận request hợp lệ từ Portal
        if ($authorized && empty($configured_lic) && !empty($license_header) && strpos($license_header, 'OMNI-') === 0) {
            update_option('omni_bridge_license_key', $license_header);
            update_option('omni_bridge_allowed_domain', $this->get_current_host_domain());
            update_option('omni_bridge_plan_label', 'Dùng Thử (Auto-Handshake)');
            update_option('omni_bridge_expires_at', date('c', strtotime('+7 days')));
        }

        if (!$authorized) {
            return new WP_Error('unauthorized', 'Invalid or missing Omni Bridge API secret key.', array('status' => 401));
        }

        // Health check endpoint cho phép qua để chẩn đoán trạng thái; các tác vụ sửa đổi yêu cầu license hợp lệ
        if (strpos($request->get_route(), '/health') !== false) {
            return true;
        }

        // 3. Khóa cứng FQDN Domain & Kiểm tra hạn dùng gói cước
        return $this->verify_license_permission_guard();
    }

    public function handle_health_check($request) {
        $license_health = $this->check_license_health();
        return rest_ensure_response(array(
            'status' => 'healthy',
            'bridge_version' => self::VERSION,
            'wp_version' => get_bloginfo('version'),
            'site_name' => get_bloginfo('name'),
            'site_url' => get_site_url(),
            'license_health' => $license_health,
            'woocommerce_active' => class_exists('WooCommerce'),
            'rank_math_active' => class_exists('RankMath'),
            'yoast_active' => defined('WPSEO_VERSION'),
            'aioseo_active' => defined('AIOSEO_VERSION'),
            'seopress_active' => defined('SEOPRESS_VERSION'),
            'seo_provider_setting' => get_option(self::OPTION_SEO_PROVIDER, 'auto'),
            'seo_provider_resolved' => $this->resolve_seo_provider(),
            'gsc_connector' => array(
                'mode' => 'external_official_oauth',
                'configured' => !empty(get_option(self::OPTION_GSC_ENGINE_URL, '')) &&
                    !empty(get_option(self::OPTION_GSC_TENANT_ID, '')) &&
                    !empty(get_option(self::OPTION_GSC_PROJECT_ID, '')),
                'connection_detected' => $this->get_site_kit_gsc_connection()['connection_detected'],
                'source' => $this->get_site_kit_gsc_connection()['source'],
                'property_uri' => $this->get_site_kit_gsc_connection()['property_uri'] ?: get_site_url(),
            ),
            'server_time' => current_time('mysql')
        ));
    }

    public function handle_gsc_config($request) {
        $site_kit = $this->get_site_kit_gsc_connection();
        return rest_ensure_response(array(
            'mode' => $site_kit['connection_detected'] ? 'detected_existing_connection' : 'external_official_oauth',
            'source' => $site_kit['source'],
            'connection_detected' => $site_kit['connection_detected'],
            'token_transfer_supported' => false,
            'site_url' => get_site_url(),
            'property_uri' => $site_kit['property_uri'] ?: get_site_url(),
            'engine_url' => get_option(self::OPTION_GSC_ENGINE_URL, ''),
            'tenant_id' => get_option(self::OPTION_GSC_TENANT_ID, ''),
            'project_id' => get_option(self::OPTION_GSC_PROJECT_ID, ''),
            'message' => $site_kit['connection_detected']
                ? 'Đã phát hiện property GSC từ Google Site Kit. Marketing Engine cần cấp quyền OAuth riêng một lần để hoạt động độc lập.'
                : 'Chưa phát hiện kết nối GSC từ plugin; Marketing Engine sẽ dùng OAuth chính thức.',
        ));
    }

    /**
     * Detect a reusable Site Kit connection without exposing its OAuth token.
     * Yoast verification meta tags are intentionally not treated as a data connection.
     */
    public function handle_gsc_performance($request) {
        $connection = $this->get_site_kit_gsc_connection();
        if (!$connection['connection_detected']) {
            return new WP_Error(
                'gsc_plugin_connection_unavailable',
                'Không có kết nối Google Site Kit GSC hợp lệ để tái sử dụng.',
                array('status' => 409)
            );
        }

        $params = $request->get_json_params();
        $start_date = sanitize_text_field($params['start_date'] ?? '');
        $end_date = sanitize_text_field($params['end_date'] ?? '');
        if (!$start_date || !$end_date) {
            return new WP_Error('invalid_dates', 'Cần truyền start_date và end_date.', array('status' => 422));
        }

        try {
            $plugin_class = 'Google\\Site_Kit\\Plugin';
            $site_kit = call_user_func(array($plugin_class, 'instance'));
            $module = $site_kit->modules()->get_module('search-console');
            if (!$module || !method_exists($module, 'get_data')) {
                throw new RuntimeException('Site Kit Search Console module không hỗ trợ data adapter.');
            }
            $data = $module->get_data('searchanalytics', array(
                'startDate' => $start_date,
                'endDate' => $end_date,
                'dimensions' => array_map('sanitize_text_field', (array) ($params['dimensions'] ?? array('date'))),
                'limit' => min(absint($params['row_limit'] ?? 25000), 25000),
                'startRow' => absint($params['start_row'] ?? 0),
            ));
            if (is_wp_error($data)) {
                return $data;
            }
            return rest_ensure_response(array(
                'source' => 'google_site_kit',
                'property_uri' => $connection['property_uri'],
                'response' => $data,
            ));
        } catch (Throwable $error) {
            return new WP_Error('gsc_site_kit_adapter_error', $error->getMessage(), array('status' => 502));
        }
    }

}
