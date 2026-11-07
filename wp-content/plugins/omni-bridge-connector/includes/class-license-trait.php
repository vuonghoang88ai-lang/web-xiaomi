<?php
/**
 * License & Subscription Management Trait for Omni Bridge Connector.
 * Strictly complies with SOP 23 (<= 300 lines).
 */
if (!defined('ABSPATH')) exit;

trait Omni_Bridge_License_Trait {

    /**
     * Tự động phát hiện và đồng bộ file omni-license.json được nhúng trong zip.
     */
    public function auto_sync_embedded_license() {
        $license_file = plugin_dir_path(dirname(__FILE__)) . 'omni-license.json';
        if (!file_exists($license_file)) {
            return;
        }

        $raw = file_get_contents($license_file);
        $data = json_decode($raw, true);
        if (!$data || empty($data['license_key'])) return;

        // SOP 48: Tự động ghi đè API Key nhúng sẵn từ zip để hai bên khớp nhau 100%
        if (!empty($data['api_key'])) {
            update_option('omni_bridge_api_key', sanitize_text_field($data['api_key']));
        }

        $existing_key = get_option('omni_bridge_license_key', '');
        $synced_api_key = get_option('omni_bridge_synced_api_key', '');
        $current_api_key = get_option('omni_bridge_api_key', '');

        // Chạy khi chưa có key hoặc đổi key hoặc API key chưa đồng bộ
        if (empty($existing_key) || $existing_key !== $data['license_key'] || $synced_api_key !== $current_api_key) {
            $portal = !empty($data['portal_url']) ? $data['portal_url'] : '';
            if (empty($portal) || strpos($portal, '100.') !== false || strpos($portal, '127.0.0.1') !== false) {
                $portal = 'https://seo.vuonghoang.name.vn/';
            }
            $this->activate_license_by_key($data['license_key'], $portal);
        }
    }

    /**
     * Lấy tên miền chuẩn FQDN của website hiện tại (loại bỏ www, port).
     */
    public function get_current_host_domain() {
        $host = $_SERVER['HTTP_HOST'] ?? parse_url(get_site_url(), PHP_URL_HOST);
        $host = strtolower(trim($host));
        $host = explode(':', $host)[0];
        if (0 === strpos($host, 'www.')) {
            $host = substr($host, 4);
        }
        return $host;
    }

    /**
     * Kiểm tra trạng thái bản quyền (Khóa cứng tên miền & ngày hết hạn).
     */
    public function check_license_health() {
        $license_key = get_option('omni_bridge_license_key', '');
        $allowed_domain = get_option('omni_bridge_allowed_domain', '');
        $expires_at = get_option('omni_bridge_expires_at', '');
        $plan_label = get_option('omni_bridge_plan_label', 'Chưa kích hoạt');

        $current_host = $this->get_current_host_domain();
        $is_ip = filter_var($current_host, FILTER_VALIDATE_IP) !== false;
        $is_priv_ip = $is_ip && (filter_var($current_host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false);
        $is_local = in_array($current_host, ['localhost', '127.0.0.1', 'wordpress', 'michinhhang-wordpress-1']) || strpos($current_host, '100.') === 0;

        if ($is_local || $is_priv_ip) {
            return [
                'valid' => true,
                'status' => 'local_dev_trial',
                'plan_label' => 'Môi Trường Thử Nghiệm (Dev)',
                'allowed_domain' => $current_host,
                'license_key' => $license_key ?: 'DEV-LOCAL-TRIAL',
                'days_left' => 99,
                'expires_at' => date('c', strtotime('+99 days')),
                'label' => '🟢 Thử Nghiệm (Dev Mode)'
            ];
        }

        if (empty($license_key)) {
            return [
                'valid' => false,
                'status' => 'unlicensed',
                'label' => 'Chưa Có Bản Quyền',
                'message' => 'Plugin chưa được kích hoạt bản quyền. Vui lòng nhập License Key hoặc tải bản zip cấu hình sẵn từ Portal.'
            ];
        }

        // 1. Kiểm tra khóa cứng FQDN Domain
        $norm_allowed = strtolower(trim(str_replace('www.', '', $allowed_domain)));

        // Cho phép localhost / 127.0.0.1 trong môi trường test nếu domain chưa set
        if (!empty($norm_allowed) && $current_host !== $norm_allowed && !in_array($current_host, ['localhost', '127.0.0.1'])) {
            return [
                'valid' => false,
                'status' => 'domain_mismatch',
                'label' => 'Sai Tên Miền Cấp Phép',
                'message' => sprintf('Bản quyền này bị khóa cứng cho tên miền [%s], không thể sử dụng trên [%s].', $norm_allowed, $current_host)
            ];
        }

        // 2. Kiểm tra ngày hết hạn
        $now = time();
        $exp_timestamp = !empty($expires_at) ? strtotime($expires_at) : 0;

        if ($exp_timestamp > 0 && $now > $exp_timestamp) {
            return [
                'valid' => false,
                'status' => 'expired',
                'label' => 'Bản Quyền Đã Hết Hạn',
                'message' => sprintf('Gói [%s] đã hết hạn vào ngày %s. Vui lòng gia hạn để tiếp tục sử dụng API tự động.', $plan_label, date_i18n('d/m/Y H:i', $exp_timestamp))
            ];
        }

        $days_left = $exp_timestamp > 0 ? round(($exp_timestamp - $now) / 86400, 1) : 0;
        return [
            'valid' => true,
            'status' => 'active',
            'plan_label' => $plan_label,
            'allowed_domain' => $allowed_domain,
            'license_key' => $license_key,
            'days_left' => $days_left,
            'expires_at' => $expires_at,
            'label' => sprintf('🟢 %s (Còn %s ngày)', $plan_label, $days_left)
        ];
    }

    /**
     * Middleware chặn request API nếu bản quyền không hợp lệ hoặc hết hạn.
     */
    public function verify_license_permission_guard() {
        $health = $this->check_license_health();
        if (!$health['valid']) {
            return new WP_Error(
                'omni_license_error',
                $health['message'],
                ['status' => 403, 'license_status' => $health['status']]
            );
        }
        return true;
    }

    /**
     * Kích hoạt hoặc gia hạn bản quyền trực tiếp bằng License Key kèm đồng bộ 2 chiều.
     */
    public function activate_license_by_key($key, $portal_url = '') {
        $key = sanitize_text_field(trim($key));
        if (empty($key)) {
            return new WP_Error('empty_key', 'Vui lòng cung cấp License Key.');
        }

        $default_portal = (defined('OMNI_PORTAL_URL') && OMNI_PORTAL_URL) ? OMNI_PORTAL_URL : 'https://seo.vuonghoang.name.vn/';
        $target_portal = !empty($portal_url) ? esc_url_raw($portal_url) : get_option('omni_bridge_portal_url', $default_portal);
        if (strpos($target_portal, '100.') !== false || strpos($target_portal, '127.0.0.1') !== false) $target_portal = 'https://seo.vuonghoang.name.vn/';
        $endpoint = rtrim($target_portal, '/') . '/api/v1/portal/license/verify';

        $api_key = get_option('omni_bridge_api_key', '');
        if (empty($api_key)) {
            $api_key = 'obc_' . wp_generate_password(36, false);
            update_option('omni_bridge_api_key', $api_key);
        }
        $current_host = $this->get_current_host_domain();
        $response = wp_remote_post($endpoint, [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode([
                'license_key' => $key,
                'domain' => $current_host,
                'cms_api_key' => $api_key,
                'cms_api_url' => home_url(),
                'publish_endpoint' => rest_url('omni-bridge/v1/publish'),
                'seo_provider' => get_option('omni_bridge_seo_provider', 'auto')
            ]),
            'timeout' => 10
        ]);

        if (is_wp_error($response)) {
            return new WP_Error('connection_failed', 'Không thể kết nối đến Marketing Portal: ' . $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        $code = wp_remote_retrieve_response_code($response);

        if ($code !== 200 || empty($data['data']['valid'])) {
            $msg = $data['detail']['message'] ?? $data['message'] ?? 'Xác thực bản quyền không thành công.';
            return new WP_Error('activation_failed', $msg);
        }

        $lic = $data['data'];
        update_option('omni_bridge_license_key', $key);
        update_option('omni_bridge_allowed_domain', $lic['domain'] ?? $current_host);
        update_option('omni_bridge_plan_type', $lic['plan_type'] ?? 'trial_7d');
        update_option('omni_bridge_plan_label', $lic['plan_label'] ?? 'Gói Dịch Vụ');
        update_option('omni_bridge_expires_at', $lic['expires_at'] ?? '');
        update_option('omni_bridge_license_status', 'active');
        update_option('omni_bridge_synced_api_key', $api_key);
        if (!empty($portal_url)) update_option('omni_bridge_portal_url', $portal_url);
        if (!empty($lic['gsc_engine_url'])) update_option('omni_bridge_gsc_engine_url', esc_url_raw($lic['gsc_engine_url']));
        if (!empty($lic['tenant_id'])) update_option('omni_bridge_gsc_tenant_id', sanitize_text_field($lic['tenant_id']));
        if (!empty($lic['project_id'])) update_option('omni_bridge_gsc_project_id', sanitize_text_field($lic['project_id']));
        if (!empty($lic['engine_api_key'])) update_option('omni_bridge_gsc_engine_api_key', sanitize_text_field($lic['engine_api_key']));

        return $lic;
    }

    /**
     * Render giao diện thẻ Quản Lý Bản Quyền trong WP-Admin.
     */
    public function render_license_admin_card() {
        if (isset($_POST['omni_bridge_activate_license']) && check_admin_referer('omni_bridge_license_nonce')) {
            $key = sanitize_text_field($_POST['omni_license_key_input'] ?? '');
            $portal = esc_url_raw($_POST['omni_license_portal_url'] ?? '');
            $res = $this->activate_license_by_key($key, $portal);
            if (is_wp_error($res)) {
                echo '<div class="notice notice-error is-dismissible"><p><strong>Lỗi bản quyền:</strong> ' . esc_html($res->get_error_message()) . '</p></div>';
            } else {
                echo '<div class="notice notice-success is-dismissible"><p><strong>Thành công!</strong> Đã kích hoạt ' . esc_html($res['plan_label'] ?? 'gói cước') . ' (Còn ' . esc_html($res['days_left'] ?? 0) . ' ngày).</p></div>';
            }
        }

        $health = $this->check_license_health();
        $current_host = $this->get_current_host_domain();
        $badge_color = $health['valid'] ? '#10b981' : ($health['status'] === 'expired' ? '#ef4444' : '#f59e0b');
        $license_key = get_option('omni_bridge_license_key', '');
        $default_portal = (defined('OMNI_PORTAL_URL') && OMNI_PORTAL_URL) ? OMNI_PORTAL_URL : (getenv('PORTAL_BASE_URL') ?: 'https://seo.vuonghoang.name.vn/');
        $portal_url = get_option('omni_bridge_portal_url', $default_portal);
        ?>
        <div style="background: #fff; padding: 22px 24px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #f1f5f9; padding-bottom: 12px; margin-bottom: 15px;">
                <h2 style="font-size: 18px; margin: 0; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                    <span>🛡️</span> Quản Trị Bản Quyền & Gói Cước
                </h2>
                <span style="font-size: 12px; font-weight: 700; color: #fff; background: <?php echo esc_attr($badge_color); ?>; padding: 4px 10px; border-radius: 20px;">
                    <?php echo esc_html($health['label']); ?>
                </span>
            </div>

            <p style="color: #64748b; font-size: 13px; margin-top: 0;">
                Mỗi bản quyền Omni Bridge được <strong>khóa cứng theo tên miền duy nhất</strong> để chống sao chép và bảo vệ quyền lợi gói dịch vụ.
            </p>

            <table class="form-table" style="margin-top: 5px;">
                <tr>
                    <th scope="row" style="width: 220px;">Tên miền website này</th>
                    <td>
                        <strong style="font-size: 14px; color: #0284c7;"><?php echo esc_html($current_host); ?></strong>
                        <span style="font-size: 11px; background: #e0f2fe; color: #0369a1; padding: 2px 7px; border-radius: 4px; margin-left: 6px;">FQDN Khóa Cứng</span>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Gói cước & Hạn sử dụng</th>
                    <td>
                        <div><strong><?php echo esc_html($health['plan_label'] ?? 'Chưa kích hoạt'); ?></strong></div>
                        <div style="font-size: 12px; color: #64748b; margin-top: 3px;">
                            <?php if (!empty($health['expires_at'])): ?>
                                Hết hạn lúc: <strong><?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($health['expires_at']))); ?></strong> (Còn lại ~<?php echo esc_html($health['days_left']); ?> ngày)
                            <?php else: ?>
                                Chưa xác định ngày hết hạn.
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Kết nối 2 chiều (Hybrid)</th>
                    <td>
                        <span style="color: #10b981; font-weight: 600;">🟢 Chế độ: Hybrid (Bypass Cloudflare WAF Sẵn Sàng)</span>
                        <div style="font-size: 11.5px; color: #64748b; margin-top: 3px;">
                            Đồng bộ lần cuối: <?php echo esc_html(get_option('omni_bridge_last_poll_time', 'Vừa khởi động')); ?> 
                            (WP-Cron tự động kiểm tra mỗi 2 phút)
                        </div>
                    </td>
                </tr>
            </table>

            <form method="post" style="margin-top: 15px; background: #f8fafc; padding: 16px; border-radius: 6px; border: 1px solid #e2e8f0;">
                <?php wp_nonce_field('omni_bridge_license_nonce'); ?>
                <div style="font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 8px;">
                    🔑 Nhập hoặc Cập nhật License Key (Gia hạn / Nâng cấp gói):
                </div>
                <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
                    <input type="text" name="omni_license_key_input" value="<?php echo esc_attr($license_key); ?>" placeholder="Dán License Key (Ví dụ: OMNI-PRO-81BFE4C0...)" style="flex: 1; min-width: 320px; font-family: monospace; font-size: 13px; padding: 7px 10px; border: 1px solid #cbd5e1; border-radius: 4px;" required />
                    <input type="hidden" name="omni_license_portal_url" value="<?php echo esc_attr($portal_url); ?>" />
                    <button type="submit" name="omni_bridge_activate_license" class="button button-primary" style="height: 34px;">
                        ⚡ Kích Hoạt / Xác Thực Bản Quyền
                    </button>
                </div>
                <div style="font-size: 11.5px; color: #64748b; margin-top: 6px;">
                    💡 <em>Nếu bạn tải bản zip có nhúng sẵn license từ Portal, plugin sẽ tự động nhận diện mà không cần dán key thủ công.</em>
                </div>
            </form>
        </div>
        <?php
    }
}
