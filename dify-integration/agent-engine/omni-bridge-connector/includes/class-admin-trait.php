<?php
/**
 * Trait: admin_trait
 * Auto-generated from omni-bridge-connector.php refactor.
 * Contains split methods for the Omni_Bridge_Connector class.
 */

trait AdminTrait {
    public function register_settings() {
        foreach ([self::OPTION_KEY => 'sanitize_text_field', self::OPTION_STATUS => array($this, 'sanitize_post_status_option'), self::OPTION_SEO_PROVIDER => array($this, 'sanitize_seo_provider'), self::OPTION_GSC_ENGINE_URL => 'esc_url_raw', self::OPTION_GSC_TENANT_ID => 'sanitize_text_field', self::OPTION_GSC_PROJECT_ID => 'sanitize_text_field', self::OPTION_GSC_ENGINE_API_KEY => 'sanitize_text_field'] as $k => $cb) {
            register_setting('omni_bridge_group', $k, array('type' => 'string', 'sanitize_callback' => $cb));
        }
    }

    public function add_admin_menu() {
        add_options_page('Omni Bridge Connector', 'Omni Bridge', 'manage_options', 'omni-bridge-settings', array($this, 'render_admin_page'));
    }

    public function render_admin_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Handle Regenerate Key
        if (isset($_POST['omni_bridge_regenerate_key']) && check_admin_referer('omni_bridge_regen_nonce')) {
            $new_key = 'obc_' . bin2hex(random_bytes(20));
            update_option(self::OPTION_KEY, $new_key);
            $lic = get_option('omni_bridge_license_key', '');
            if (!empty($lic)) {
                $this->activate_license_by_key($lic);
            }
            echo '<div class="notice notice-success is-dismissible"><p><strong>Đã làm mới API Secret Key và tự động đồng bộ sang Portal thành công!</strong></p></div>';
        }

        $current_key = get_option(self::OPTION_KEY, '');
        // SOP 48: Tự động đồng bộ API Key sang Portal khi admin mở trang cài đặt
        $lic_key = get_option('omni_bridge_license_key', '');
        if (!empty($current_key) && !empty($lic_key) && false === get_transient('omni_admin_key_sync')) {
            set_transient('omni_admin_key_sync', 1, 300);
            $this->activate_license_by_key($lic_key);
        }
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

            <?php $this->render_license_admin_card(); ?>

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
                            <?php $sk = $this->get_site_kit_gsc_connection();
                            if (!empty($sk['connection_detected'])): ?>
                                <p style="color: #16a34a; font-weight: 600; margin: 0 0 8px;">
                                    🟢 Đã liên kết Google Site Kit: <code><?php echo esc_html($sk['property_uri']); ?></code>
                                </p>
                            <?php endif; ?>
                            <input type="url" name="<?php echo self::OPTION_GSC_ENGINE_URL; ?>" value="<?php echo esc_attr($gsc_engine_url); ?>" class="regular-text" placeholder="https://marketing-engine.example.com" />
                            <p class="description">URL Marketing Engine (AI Cloud). Tự động nhận diện Site Kit và đồng bộ từ xa.</p>
                            <label>Tenant ID<br><input type="text" name="<?php echo self::OPTION_GSC_TENANT_ID; ?>" value="<?php echo esc_attr($gsc_tenant_id); ?>" class="regular-text code" /></label><br>
                            <label>Project ID<br><input type="text" name="<?php echo self::OPTION_GSC_PROJECT_ID; ?>" value="<?php echo esc_attr($gsc_project_id); ?>" class="regular-text code" /></label>
                            <br><label>Marketing Engine API key<br><input type="password" name="<?php echo self::OPTION_GSC_ENGINE_API_KEY; ?>" value="<?php echo esc_attr($gsc_engine_api_key); ?>" class="regular-text code" autocomplete="new-password" /></label>
                            <?php if ($gsc_engine_url && $gsc_tenant_id && $gsc_project_id): ?>
                                <p>
                                    <button type="submit" form="omni-bridge-gsc-connect-form" class="button button-primary">🔗 Kết nối Google Search Console</button>
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
}
