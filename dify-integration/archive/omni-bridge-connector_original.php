<?php
/**
 * Plugin Name: Omni Bridge Connector
 * Plugin URI: https://michinhhang.vn
 * Description: High-performance, lightweight REST API Bridge for Autonomous Marketing AI Engine (Dify / CrewAI). Enables secure remote publishing to WordPress & WooCommerce with Rank Math / Yoast SEO metadata and media sideloading.
 * Version: 1.0.0
 * Author: Omni Marketing Engineering Team
 * Text Domain: omni-bridge-connector
 * License: GPLv2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

class Omni_Bridge_Connector {
    const VERSION = '1.0.0';
    const OPTION_KEY = 'omni_bridge_api_key';
    const OPTION_STATUS = 'omni_bridge_default_status';
    const OPTION_SEO_PROVIDER = 'omni_bridge_seo_provider';
    const OPTION_GSC_ENGINE_URL = 'omni_bridge_gsc_engine_url';
    const OPTION_GSC_TENANT_ID = 'omni_bridge_gsc_tenant_id';
    const OPTION_GSC_PROJECT_ID = 'omni_bridge_gsc_project_id';
    const OPTION_GSC_ENGINE_API_KEY = 'omni_bridge_gsc_engine_api_key';
    const REST_NAMESPACE = 'omni-bridge/v1';

    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        add_action('rest_api_init', array($this, 'register_routes'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_post_omni_bridge_gsc_connect', array($this, 'handle_admin_gsc_connect'));

        // Hooks tương thích Theme Phổ quát (Universal Theme Compatibility)
        add_filter('woocommerce_product_tabs', array($this, 'register_universal_specs_tab'), 25);
        add_shortcode('product_technical_specs', array($this, 'shortcode_technical_specs'));
    }

    public function activate() {
        if (!get_option(self::OPTION_KEY)) {
            $key = 'obc_' . bin2hex(random_bytes(20));
            update_option(self::OPTION_KEY, $key);
        }
        if (!get_option(self::OPTION_STATUS)) {
            update_option(self::OPTION_STATUS, 'draft');
        }
        if (!get_option(self::OPTION_SEO_PROVIDER)) {
            update_option(self::OPTION_SEO_PROVIDER, 'auto');
        }
    }

    public function register_settings() {
        register_setting('omni_bridge_group', self::OPTION_KEY, array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default' => ''
        ));
        register_setting('omni_bridge_group', self::OPTION_STATUS, array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_post_status_option'),
            'default' => 'draft'
        ));
        register_setting('omni_bridge_group', self::OPTION_SEO_PROVIDER, array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_seo_provider'),
            'default' => 'auto'
        ));
        register_setting('omni_bridge_group', self::OPTION_GSC_ENGINE_URL, array(
            'type' => 'string', 'sanitize_callback' => 'esc_url_raw', 'default' => ''
        ));
        register_setting('omni_bridge_group', self::OPTION_GSC_TENANT_ID, array(
            'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => ''
        ));
        register_setting('omni_bridge_group', self::OPTION_GSC_PROJECT_ID, array(
            'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => ''
        ));
        register_setting('omni_bridge_group', self::OPTION_GSC_ENGINE_API_KEY, array(
            'type' => 'string', 'sanitize_callback' => 'sanitize_text_field', 'default' => ''
        ));
    }

    public function add_admin_menu() {
        add_options_page(
            'Omni Bridge Connector',
            'Omni Bridge',
            'manage_options',
            'omni-bridge-settings',
            array($this, 'render_admin_page')
        );
    }

    public function render_admin_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Handle Regenerate Key
        if (isset($_POST['omni_bridge_regenerate_key']) && check_admin_referer('omni_bridge_regen_nonce')) {
            $new_key = 'obc_' . bin2hex(random_bytes(20));
            update_option(self::OPTION_KEY, $new_key);
            echo '<div class="notice notice-success is-dismissible"><p><strong>Đã làm mới API Secret Key thành công!</strong></p></div>';
        }

        $current_key = get_option(self::OPTION_KEY, '');
        $default_status = get_option(self::OPTION_STATUS, 'draft');
        $seo_provider = get_option(self::OPTION_SEO_PROVIDER, 'auto');
        $gsc_engine_url = get_option(self::OPTION_GSC_ENGINE_URL, '');
        $gsc_tenant_id = get_option(self::OPTION_GSC_TENANT_ID, '');
        $gsc_project_id = get_option(self::OPTION_GSC_PROJECT_ID, '');
        $gsc_engine_api_key = get_option(self::OPTION_GSC_ENGINE_API_KEY, '');
        $detected_seo = $this->get_detected_seo_plugins();
        $endpoint_publish = get_rest_url(null, self::REST_NAMESPACE . '/publish');
        $endpoint_health = get_rest_url(null, self::REST_NAMESPACE . '/health');
        $gsc_status = isset($_GET['omni_gsc_status']) ? sanitize_key(wp_unslash($_GET['omni_gsc_status'])) : '';
        $gsc_message = isset($_GET['omni_gsc_message']) ? sanitize_text_field(wp_unslash($_GET['omni_gsc_message'])) : '';
        ?>
        <div class="wrap" style="max-width: 900px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;">
            <div style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #fff; padding: 24px; border-radius: 8px; margin-bottom: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
                <h1 style="color: #38bdf8; margin: 0 0 8px 0; font-size: 24px;">🚀 Omni Bridge Connector</h1>
                <p style="margin: 0; color: #cbd5e1; font-size: 14px;">
                    Cổng kết nối chuẩn hóa giữa Hệ thống AI Tự động hóa Marketing (Dify / CrewAI) và Website WordPress này.
                </p>
            </div>

            <div style="background: #fff; padding: 24px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 20px;">
                <h2 style="font-size: 18px; margin-top: 0; color: #0f172a; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px;">
                    🔑 Cấu hình Khóa Xác thực (API Secret Key)
                </h2>
                <p style="color: #64748b; font-size: 13px;">
                    Sao chép mã này và dán vào trường <code>cms_auth_config</code> (hoặc Biến môi trường hệ thống Dify Agent) để cho phép AI xuất bản nội dung.
                </p>

                <table class="form-table" style="margin-top: 10px;">
                    <tr>
                        <th scope="row" style="width: 200px;">API Secret Key</th>
                        <td>
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <input type="text" id="obc_key_input" readonly value="<?php echo esc_attr($current_key); ?>" style="width: 380px; font-family: monospace; background: #f8fafc; font-size: 14px; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 4px;" />
                                <button type="button" class="button button-secondary" onclick="navigator.clipboard.writeText(document.getElementById('obc_key_input').value); alert('Đã sao chép API Key!');">📋 Copy</button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Endpoint Xuất bản (POST)</th>
                        <td>
                            <code style="background: #f1f5f9; padding: 4px 8px; border-radius: 4px; font-size: 13px; color: #0284c7;"><?php echo esc_html($endpoint_publish); ?></code>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Endpoint Kiểm tra (GET)</th>
                        <td>
                            <code style="background: #f1f5f9; padding: 4px 8px; border-radius: 4px; font-size: 13px; color: #16a34a;"><?php echo esc_html($endpoint_health); ?></code>
                        </td>
                    </tr>
                </table>

                <form method="post" style="margin-top: 15px;">
                    <?php wp_nonce_field('omni_bridge_regen_nonce'); ?>
                    <button type="submit" name="omni_bridge_regenerate_key" class="button" onclick="return confirm('Bạn có chắc muốn tạo khóa mới? Hệ thống AI kết nối hiện tại sẽ bị từ chối nếu không cập nhật key mới!');" style="color: #dc2626;">
                        🔄 Tạo lại API Secret Key
                    </button>
                </form>
            </div>

            <form method="post" action="options.php" style="background: #fff; padding: 24px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 20px;">
                <?php settings_fields('omni_bridge_group'); ?>
                <h2 style="font-size: 18px; margin-top: 0; color: #0f172a; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px;">
                    ⚙️ Tùy chọn Xuất bản Mặc định
                </h2>
                <table class="form-table">
                    <tr>
                        <th scope="row">Trạng thái bài viết mặc định</th>
                        <td>
                            <select name="<?php echo self::OPTION_STATUS; ?>">
                                <option value="draft" <?php selected($default_status, 'draft'); ?>>Bản nháp (Draft - Kiểm duyệt thủ công)</option>
                            </select>
                            <p class="description">Khi Agent không chỉ định trạng thái cụ thể, trạng thái này sẽ được áp dụng.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Tích hợp SEO</th>
                        <td>
                            <select name="<?php echo self::OPTION_SEO_PROVIDER; ?>">
                                <option value="auto" <?php selected($seo_provider, 'auto'); ?>>Tự động phát hiện plugin đang active</option>
                                <option value="rank_math" <?php selected($seo_provider, 'rank_math'); ?>>Rank Math</option>
                                <option value="yoast" <?php selected($seo_provider, 'yoast'); ?>>Yoast SEO</option>
                                <option value="aioseo" <?php selected($seo_provider, 'aioseo'); ?>>All in One SEO</option>
                                <option value="seopress" <?php selected($seo_provider, 'seopress'); ?>>SEOPress</option>
                                <option value="none" <?php selected($seo_provider, 'none'); ?>>Không đồng bộ SEO plugin</option>
                            </select>
                            <p class="description">Omni Bridge đọc/ghi metadata qua WordPress API và post meta. Không đọc hoặc trích xuất API key nội bộ của plugin SEO.</p>
                            <p class="description"><strong>Đang phát hiện:</strong>
                                <?php
                                $detected_labels = array();
                                foreach ($detected_seo as $provider => $is_active) {
                                    if ($is_active) {
                                        $detected_labels[] = esc_html($provider);
                                    }
                                }
                                echo !empty($detected_labels) ? implode(', ', $detected_labels) : 'Không phát hiện plugin SEO hỗ trợ';
                                ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Google Search Console</th>
                        <td>
                            <input type="url" name="<?php echo self::OPTION_GSC_ENGINE_URL; ?>" value="<?php echo esc_attr($gsc_engine_url); ?>" class="regular-text" placeholder="https://marketing-engine.example.com" />
                            <p class="description">URL của Marketing Engine có OAuth GSC chính thức. Plugin không đọc token/API key của Yoast hoặc plugin SEO.</p>
                            <label>Tenant ID<br><input type="text" name="<?php echo self::OPTION_GSC_TENANT_ID; ?>" value="<?php echo esc_attr($gsc_tenant_id); ?>" class="regular-text code" /></label><br>
                            <label>Project ID<br><input type="text" name="<?php echo self::OPTION_GSC_PROJECT_ID; ?>" value="<?php echo esc_attr($gsc_project_id); ?>" class="regular-text code" /></label>
                            <br><label>Marketing Engine API key<br><input type="password" name="<?php echo self::OPTION_GSC_ENGINE_API_KEY; ?>" value="<?php echo esc_attr($gsc_engine_api_key); ?>" class="regular-text code" autocomplete="new-password" /></label>
                            <?php if ($gsc_engine_url && $gsc_tenant_id && $gsc_project_id): ?>
                                <p>
                                    <button type="submit" form="omni-bridge-gsc-connect-form" class="button button-primary">🔗 Kết nối Google Search Console</button>
                                    <span class="description">Thao tác thủ công: mở Google OAuth, chọn tài khoản có quyền GSC và xác nhận quyền đọc.</span>
                                </p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Lưu thiết lập'); ?>
            </form>

            <?php if ($gsc_engine_url && $gsc_tenant_id && $gsc_project_id && $gsc_engine_api_key): ?>
                <form id="omni-bridge-gsc-connect-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:none;">
                    <input type="hidden" name="action" value="omni_bridge_gsc_connect" />
                    <input type="hidden" name="_wpnonce" value="<?php echo esc_attr(wp_create_nonce('omni_bridge_gsc_connect')); ?>" />
                </form>
            <?php endif; ?>

            <?php if ($gsc_status === 'already_owned'): ?>
                <div class="notice notice-success"><p>Marketing Engine đã sở hữu kết nối GSC. Không cần cấp quyền lại.</p></div>
            <?php elseif ($gsc_status === 'error'): ?>
                <div class="notice notice-error"><p><?php echo esc_html($gsc_message ?: 'Không thể tạo luồng OAuth GSC.'); ?></p></div>
            <?php endif; ?>

            <div style="background: #f8fafc; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <h3 style="margin-top: 0; font-size: 15px; color: #334155;">✨ Khả năng Tích hợp Sẵn có</h3>
                <ul style="list-style: disc; margin-left: 20px; color: #475569; font-size: 13px; line-height: 1.6;">
                    <li><strong>Rank Math SEO:</strong> Tự động đồng bộ <code>focus_keyword</code>, <code>seo_title</code>, <code>seo_description</code>, <code>canonical_url</code>.</li>
                    <li><strong>Yoast SEO và plugin khác:</strong> Chọn adapter ở trên; plugin sẽ đồng bộ metadata bằng API nội bộ của WordPress, không cần API key riêng.</li>
                    <li><strong>WooCommerce:</strong> Tự động cập nhật <code>_price</code>, <code>_regular_price</code>, <code>_sale_price</code>, <code>_sku</code>, <code>_stock_status</code> khi đăng bài kiểu <code>product</code>.</li>
                    <li><strong>Media Library:</strong> Tự động tải ảnh từ URL về WordPress Media Library, gán Featured Image chuẩn SEO alt tag.</li>
                    <li><strong>Google Search Console:</strong> Kết nối OAuth readonly tại Marketing Engine để lấy clicks, impressions, CTR, position và truy vấn theo property.</li>
                </ul>
            </div>
        </div>
        <?php
    }

    /**
     * Start the Engine-owned GSC OAuth flow from the WordPress admin screen.
     * The plugin never receives or stores Google's OAuth tokens.
     */
    public function handle_admin_gsc_connect() {
        if (!current_user_can('manage_options')) {
            wp_die('Bạn không có quyền thực hiện thao tác này.', 'Omni Bridge', array('response' => 403));
        }
        check_admin_referer('omni_bridge_gsc_connect');

        $engine_url = trim(get_option(self::OPTION_GSC_ENGINE_URL, ''));
        $tenant_id = trim(get_option(self::OPTION_GSC_TENANT_ID, ''));
        $project_id = trim(get_option(self::OPTION_GSC_PROJECT_ID, ''));
        $engine_api_key = trim(get_option(self::OPTION_GSC_ENGINE_API_KEY, ''));
        $settings_url = admin_url('options-general.php?page=omni-bridge-settings');
        if (!$engine_url || !$tenant_id || !$project_id || !$engine_api_key) {
            wp_safe_redirect(add_query_arg(array(
                'omni_gsc_status' => 'error',
                'omni_gsc_message' => 'Cần lưu Marketing Engine URL, Tenant ID, Project ID và Engine API key trước.',
            ), $settings_url));
            exit;
        }

        $endpoint = trailingslashit($engine_url) . 'api/v1/projects/' . rawurlencode($project_id) . '/gsc/connect';
        $response = wp_remote_get(add_query_arg('tenant_id', $tenant_id, $endpoint), array(
            'timeout' => 20,
            'redirection' => 2,
            'sslverify' => true,
            'user-agent' => 'Omni-Bridge-Connector/' . self::VERSION,
            'headers' => array('X-Engine-API-Key' => $engine_api_key),
        ));
        if (is_wp_error($response)) {
            wp_safe_redirect(add_query_arg(array(
                'omni_gsc_status' => 'error',
                'omni_gsc_message' => 'Không kết nối được Marketing Engine: ' . $response->get_error_message(),
            ), $settings_url));
            exit;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $auth_url = is_array($body) ? ($body['authorization_url'] ?? '') : '';
        if ($auth_url && 0 === strpos($auth_url, 'https://accounts.google.com/')) {
            wp_redirect($auth_url, 302, 'Omni Bridge Connector');
            exit;
        }
        if (is_array($body) && $body['status'] === 'already_owned') {
            wp_safe_redirect(add_query_arg('omni_gsc_status', 'already_owned', $settings_url));
            exit;
        }

        $message = is_array($body) ? ($body['message'] ?? 'Marketing Engine không trả URL OAuth hợp lệ.') : 'Phản hồi Engine không hợp lệ.';
        wp_safe_redirect(add_query_arg(array(
            'omni_gsc_status' => 'error',
            'omni_gsc_message' => $message,
        ), $settings_url));
        exit;
    }

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
    }

    public function verify_api_key($request) {
        $configured_key = get_option(self::OPTION_KEY, '');
        if (empty($configured_key)) {
            return new WP_Error('not_configured', 'Omni Bridge API key has not been configured on this site.', array('status' => 500));
        }

        // 1. Check Header X-Omni-Bridge-Key
        $header_key = $request->get_header('x_omni_bridge_key');
        if (!empty($header_key) && hash_equals($configured_key, trim($header_key))) {
            return true;
        }

        // 2. Check Authorization Header (Bearer token)
        $auth_header = $request->get_header('authorization');
        if (!empty($auth_header) && preg_match('/Bearer\s+(\S+)/i', $auth_header, $matches)) {
            if (hash_equals($configured_key, $matches[1])) {
                return true;
            }
        }

        return new WP_Error('unauthorized', 'Invalid or missing Omni Bridge API secret key.', array('status' => 401));
    }

    public function handle_health_check($request) {
        return rest_ensure_response(array(
            'status' => 'healthy',
            'bridge_version' => self::VERSION,
            'wp_version' => get_bloginfo('version'),
            'site_name' => get_bloginfo('name'),
            'site_url' => get_site_url(),
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
    private function get_site_kit_gsc_connection() {
        $result = array(
            'source' => 'none',
            'connection_detected' => false,
            'property_uri' => '',
        );

        $plugin_class = 'Google\\Site_Kit\\Plugin';
        if (!class_exists($plugin_class)) {
            return $result;
        }

        $settings = get_option('googlesitekit_search-console_settings', array());
        $credentials = get_option('googlesitekit_credentials', array());
        $property_uri = is_array($settings) ? sanitize_text_field($settings['propertyID'] ?? '') : '';
        $is_connected = is_array($credentials) && !empty($credentials) && !empty($property_uri);
        if ($is_connected) {
            $result['source'] = 'google_site_kit';
            $result['connection_detected'] = true;
            $result['property_uri'] = $property_uri;
        }
        return $result;
    }

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

    public function sanitize_seo_provider($value) {
        $allowed = array('auto', 'rank_math', 'yoast', 'aioseo', 'seopress', 'none');
        return in_array($value, $allowed, true) ? $value : 'auto';
    }

    public function sanitize_post_status_option($value) {
        return 'draft';
    }

    private function get_detected_seo_plugins() {
        return array(
            'rank_math' => defined('RANK_MATH_VERSION') || class_exists('RankMath'),
            'yoast' => defined('WPSEO_VERSION') || class_exists('WPSEO_Frontend'),
            'aioseo' => defined('AIOSEO_VERSION') || function_exists('aioseo'),
            'seopress' => defined('SEOPRESS_VERSION') || function_exists('seopress_init'),
        );
    }

    private function resolve_seo_provider() {
        $configured = get_option(self::OPTION_SEO_PROVIDER, 'auto');
        if ($configured !== 'auto') {
            return $configured;
        }

        foreach ($this->get_detected_seo_plugins() as $provider => $is_active) {
            if ($is_active) {
                return $provider;
            }
        }
        return 'none';
    }

    private function get_seo_meta_map($provider) {
        $maps = array(
            'rank_math' => array(
                'focus_keyword' => 'rank_math_focus_keyword',
                'seo_title' => 'rank_math_title',
                'seo_description' => 'rank_math_description',
                'canonical_url' => 'rank_math_canonical_url',
            ),
            'yoast' => array(
                'focus_keyword' => '_yoast_wpseo_focuskw',
                'seo_title' => '_yoast_wpseo_title',
                'seo_description' => '_yoast_wpseo_metadesc',
                'canonical_url' => '_yoast_wpseo_canonical',
            ),
            'aioseo' => array(
                'focus_keyword' => '_aioseo_keywords',
                'seo_title' => '_aioseo_title',
                'seo_description' => '_aioseo_description',
                'canonical_url' => '_aioseo_canonical_url',
            ),
            'seopress' => array(
                'focus_keyword' => '_seopress_analysis_target_kw',
                'seo_title' => '_seopress_titles_title',
                'seo_description' => '_seopress_titles_desc',
                'canonical_url' => '_seopress_robots_canonical',
            ),
        );
        return isset($maps[$provider]) ? $maps[$provider] : array();
    }

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

    private function sync_seo_meta($post_id, $seo) {
        $provider = $this->resolve_seo_provider();
        $map = $this->get_seo_meta_map($provider);
        if ($provider === 'none' || empty($map)) {
            return array('provider' => 'none', 'synced' => false);
        }

        foreach ($map as $field => $meta_key) {
            if (!array_key_exists($field, $seo)) {
                continue;
            }
            $value = $seo[$field];
            if ($field === 'canonical_url') {
                $value = esc_url_raw($value);
            } elseif ($field === 'seo_description') {
                $value = sanitize_textarea_field($value);
            } else {
                $value = sanitize_text_field($value);
            }
            update_post_meta($post_id, $meta_key, $value);
        }

        return array('provider' => $provider, 'synced' => true);
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

        $title = !empty($params['post_title']) ? sanitize_text_field($params['post_title']) : '';
        if (empty($title)) {
            return new WP_Error('missing_title', 'post_title is required.', array('status' => 400));
        }

        $post_type = !empty($params['post_type']) ? sanitize_key($params['post_type']) : 'post';
        $post_content = !empty($params['post_content']) ? wp_kses_post($params['post_content']) : '';
        if ($post_type === 'product' && !empty($post_content)) {
            // Tự động làm sạch bảng thông số kỹ thuật thô khỏi nội dung thân bài
            // Vì giao diện theme đã có component Thông số kỹ thuật (#technical-specs) riêng biệt
            $post_content = preg_replace('/<p><strong>Thông số kỹ thuật[^<]*:?<\/strong><\/p>\s*<table>.*?<\/table>/is', '', $post_content);
            $post_content = preg_replace('/<table>\s*<thead>\s*<tr>\s*<th>(?:Thông số|Đặc tính|Tiêu chí)<\/th>.*?<\/table>/is', '', $post_content);
        }
        $post_excerpt = !empty($params['post_excerpt']) ? sanitize_textarea_field($params['post_excerpt']) : '';
        // Omni Bridge is a review queue. Never allow remote callers to publish directly.
        $post_status = 'draft';
        $post_name = !empty($params['post_slug']) ? sanitize_title($params['post_slug']) : sanitize_title($title);

        $post_data = array(
            'post_title'   => $title,
            'post_content' => $post_content,
            'post_excerpt' => $post_excerpt,
            'post_status'  => $post_status,
            'post_type'    => $post_type,
            'post_name'    => $post_name
        );

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
            $stock = !empty($product_data['stock_status']) ? sanitize_text_field($product_data['stock_status']) : 'instock';
            update_post_meta($post_id, '_stock_status', $stock);
            wp_set_object_terms($post_id, 'simple', 'product_type');

            // Hỗ trợ đồng bộ ACF Repeater 'technical_specs' (Thông số kỹ thuật sản phẩm)
            if (!empty($product_data['technical_specs']) && is_array($product_data['technical_specs'])) {
                $clean_specs = array();
                foreach ($product_data['technical_specs'] as $spec) {
                    if (!empty($spec['spec_label'])) {
                        $clean_specs[] = array(
                            'spec_label' => sanitize_text_field($spec['spec_label']),
                            'spec_value' => sanitize_text_field($spec['spec_value'] ?? '')
                        );
                    }
                }
                if (!empty($clean_specs)) {
                    // 1. Luôn lưu mảng trực tiếp vào postmeta để template render tức thì không phụ thuộc ACF field key
                    update_post_meta($post_id, 'technical_specs', $clean_specs);
                    // 2. Đồng thời lưu cấu trúc từng subfield chuẩn ACF Repeater
                    foreach ($clean_specs as $idx => $spec) {
                        update_post_meta($post_id, "technical_specs_{$idx}_spec_label", $spec['spec_label']);
                        update_post_meta($post_id, "technical_specs_{$idx}_spec_value", $spec['spec_value']);
                    }
                    // 3. Nếu có ACF active, kích hoạt thêm update_field để cập nhật cache nội bộ của ACF
                    if (function_exists('update_field')) {
                        update_field('technical_specs', $clean_specs, $post_id);
                    }
                    // 4. Đồng bộ thuộc tính chuẩn Core WooCommerce (_product_attributes)
                    // Giúp 100% Theme WooCommerce (Flatsome, WoodMart, Astra, Storefront, OceanWP...)
                    // tự động nhận diện và hiển thị thông số trong tab "Thông tin bổ sung" (Additional Information)
                    $wc_attributes = array();
                    foreach ($clean_specs as $idx => $spec) {
                        $attr_slug = sanitize_title($spec['spec_label']);
                        $wc_attributes[$attr_slug] = array(
                            'name'         => $spec['spec_label'],
                            'value'        => $spec['spec_value'],
                            'position'     => $idx,
                            'is_visible'   => 1,
                            'is_variation' => 0,
                            'is_taxonomy'  => 0,
                        );
                    }
                    update_post_meta($post_id, '_product_attributes', $wc_attributes);
                }
            }

            // Hỗ trợ đồng bộ thư viện ảnh Gallery sản phẩm qua IDs
            if (!empty($product_data['gallery_image_ids']) && is_array($product_data['gallery_image_ids'])) {
                update_post_meta($post_id, '_product_image_gallery', implode(',', array_map('intval', $product_data['gallery_image_ids'])));
            }

            // Hỗ trợ đồng bộ thư viện ảnh Gallery sản phẩm qua danh sách URLs
            if (!empty($product_data['gallery_image_urls']) && is_array($product_data['gallery_image_urls'])) {
                $g_ids = !empty($product_data['gallery_image_ids']) && is_array($product_data['gallery_image_ids']) ? $product_data['gallery_image_ids'] : array();
                foreach ($product_data['gallery_image_urls'] as $g_url) {
                    $g_id = $this->sideload_image_to_post($g_url, $post_id, $title);
                    if ($g_id && !is_wp_error($g_id)) {
                        $g_ids[] = $g_id;
                    }
                }
                if (!empty($g_ids)) {
                    update_post_meta($post_id, '_product_image_gallery', implode(',', array_unique(array_map('intval', $g_ids))));
                }
            }

            if (function_exists('wc_delete_product_transients')) {
                wc_delete_product_transients($post_id);
            }
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

    private function sideload_image_to_post($image_url, $post_id, $alt_text = '') {
        if (empty($image_url)) {
            return false;
        }

        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        // 1. Kiểm tra nếu URL là file cục bộ đã tồn tại trong wp-content/uploads/
        $upload_dir = wp_upload_dir();
        $parsed_path = parse_url($image_url, PHP_URL_PATH);
        if ($parsed_path && strpos($parsed_path, '/wp-content/uploads/') !== false) {
            $rel_path = substr($parsed_path, strpos($parsed_path, '/wp-content/uploads/') + strlen('/wp-content/uploads/'));
            $local_file = trailingslashit($upload_dir['basedir']) . ltrim($rel_path, '/');
            if (file_exists($local_file)) {
                // Kiểm tra xem attachment đã đăng ký chưa
                global $wpdb;
                $existing_att = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value = %s LIMIT 1", $rel_path));
                if ($existing_att) {
                    if (!empty($alt_text)) {
                        update_post_meta($existing_att, '_wp_attachment_image_alt', sanitize_text_field($alt_text));
                    }
                    return intval($existing_att);
                }

                // Nếu file đã có nhưng chưa có attachment post, tạo ngay
                $file_type = wp_check_filetype(basename($local_file), null);
                $attachment = array(
                    'guid'           => $upload_dir['baseurl'] . '/' . _wp_relative_upload_path($local_file),
                    'post_mime_type' => $file_type['type'],
                    'post_title'     => preg_replace('/\.[^.]+$/', '', basename($local_file)),
                    'post_content'   => '',
                    'post_status'    => 'inherit'
                );
                $attach_id = wp_insert_attachment($attachment, $local_file, $post_id);
                if (!is_wp_error($attach_id)) {
                    $attach_data = wp_generate_attachment_metadata($attach_id, $local_file);
                    wp_update_attachment_metadata($attach_id, $attach_data);
                    if (!empty($alt_text)) {
                        update_post_meta($attach_id, '_wp_attachment_image_alt', sanitize_text_field($alt_text));
                    }
                    return $attach_id;
                }
            }
        }

        // 2. Kiểm tra nếu ảnh đã có trong Media Library qua attachment_url_to_postid
        $existing_id = attachment_url_to_postid($image_url);
        if ($existing_id) {
            if (!empty($alt_text)) {
                update_post_meta($existing_id, '_wp_attachment_image_alt', sanitize_text_field($alt_text));
            }
            return $existing_id;
        }

        // 3. Tải từ xa (fallback) với timeout ngắn & bypass SSRF chặn local IP
        add_filter('http_request_args', function($args) {
            $args['timeout'] = 15;
            $args['reject_unsafe_urls'] = false;
            return $args;
        });
        $att_id = media_sideload_image($image_url, $post_id, $alt_text, 'id');
        if (!is_wp_error($att_id)) {
            if (!empty($alt_text)) {
                update_post_meta($att_id, '_wp_attachment_image_alt', sanitize_text_field($alt_text));
            }
            return $att_id;
        }
        return false;
    }

    /**
     * Tương thích Theme Phổ quát (Universal Theme Compatibility):
     * Tự động đăng ký Tab "Thông số kỹ thuật" chuẩn WooCommerce Product Tabs.
     * Tương thích 100% với Flatsome, WoodMart, Astra, Storefront, Kadence, GeneratePress...
     */
    public function register_universal_specs_tab($tabs) {
        global $product;
        if (!$product) {
            return $tabs;
        }

        // Bỏ qua nếu theme hiện tại là omni-commerce (đã có block #technical-specs riêng biệt)
        if (get_template() === 'omni-commerce') {
            return $tabs;
        }

        $specs = get_post_meta($product->get_id(), 'technical_specs', true);
        if (!empty($specs) && is_array($specs)) {
            $tabs['obc_technical_specs'] = array(
                'title'    => __('Thông số kỹ thuật', 'omni-bridge-connector'),
                'priority' => 15,
                'callback' => array($this, 'render_universal_specs_tab_content')
            );
        }
        return $tabs;
    }

    /**
     * Render bảng thông số kỹ thuật chuẩn HTML/CSS trung lập (Theme-Agnostic)
     */
    public function render_universal_specs_tab_content() {
        global $product;
        if (!$product) {
            return;
        }
        echo $this->get_specs_table_html($product->get_id());
    }

    /**
     * Shortcode [product_technical_specs id=""]
     * Cho phép nhúng bảng thông số kỹ thuật vào Elementor, Divi, Gutenberg, WPBakery hoặc bất kỳ theme nào
     */
    public function shortcode_technical_specs($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
        ), $atts, 'product_technical_specs');

        $post_id = intval($atts['id']);
        if (!$post_id) {
            $post_id = get_the_ID();
        }

        if (!$post_id) {
            return '';
        }

        return $this->get_specs_table_html($post_id);
    }

    /**
     * Tạo HTML bảng thông số kỹ thuật trung tính, tự thích ứng màu sắc và độ rộng của mọi theme
     */
    public function get_specs_table_html($post_id) {
        $specs = get_post_meta($post_id, 'technical_specs', true);
        if (empty($specs) || !is_array($specs)) {
            return '';
        }

        ob_start();
        ?>
        <div class="obc-specs-wrapper" style="margin: 20px 0; overflow-x: auto;">
            <table class="obc-specs-table" style="width: 100%; border-collapse: collapse; font-size: 14px; border: 1px solid #e2e8f0; line-height: 1.6; text-align: left;">
                <tbody>
                    <?php foreach ($specs as $idx => $spec) :
                        $lbl = !empty($spec['spec_label']) ? esc_html($spec['spec_label']) : '';
                        $val = !empty($spec['spec_value']) ? wp_kses_post($spec['spec_value']) : '';
                        if (empty($lbl)) continue;
                        $bg = ($idx % 2 === 0) ? '#f8fafc' : '#ffffff';
                    ?>
                    <tr style="background-color: <?php echo esc_attr($bg); ?>; border-bottom: 1px solid #e2e8f0;">
                        <td style="padding: 10px 14px; font-weight: 600; color: #475569; width: 35%; border-right: 1px solid #e2e8f0; vertical-align: top;"><?php echo $lbl; ?></td>
                        <td style="padding: 10px 14px; color: #1e293b; vertical-align: top;"><?php echo $val; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }
}

new Omni_Bridge_Connector();
