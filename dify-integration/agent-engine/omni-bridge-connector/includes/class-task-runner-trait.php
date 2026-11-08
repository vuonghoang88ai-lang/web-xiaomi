<?php
/**
 * Trait Omni_Bridge_Task_Runner_Trait
 * Xử lý Hàng đợi Đồng bộ 2 Chiều (Reverse Task Queue Pull) Vượt Cloudflare WAF 100%.
 * Tuân thủ nghiêm ngặt SOP 23 (<= 300 dòng).
 */

if (!defined('ABSPATH')) {
    exit;
}

trait Omni_Bridge_Task_Runner_Trait {

    public function init_task_runner() {
        // 1. Đăng ký khoảng thời gian Cron 2 phút
        add_filter('cron_schedules', [$this, 'add_cron_interval_two_minutes']);

        // 2. Đăng ký Hook chạy định kỳ cho WP-Cron
        add_action('omni_bridge_task_pull_cron', [$this, 'run_background_task_poll']);

        // 3. Kích hoạt lịch nếu chưa có
        if (!wp_next_scheduled('omni_bridge_task_pull_cron')) {
            wp_schedule_event(time(), 'omni_two_minutes', 'omni_bridge_task_pull_cron');
        }

        // 4. Hook fallback khi Admin truy cập (Throttled 60s qua Transient)
        add_action('admin_init', [$this, 'maybe_trigger_throttled_poll']);

        // 5. AJAX trigger đồng bộ thủ công từ WP-Admin
        add_action('wp_ajax_omni_bridge_manual_poll', [$this, 'handle_manual_task_poll_ajax']);
    }

    public function add_cron_interval_two_minutes($schedules) {
        $schedules['omni_two_minutes'] = [
            'interval' => 120,
            'display'  => __('Mỗi 2 phút (Omni Bridge)', 'omni-bridge-connector')
        ];
        return $schedules;
    }

    public function maybe_trigger_throttled_poll() {
        if (!current_user_can('manage_options')) return;
        $transient_key = 'omni_bridge_poll_throttle';
        if (false === get_transient($transient_key)) {
            set_transient($transient_key, 1, 60); // Khóa 60 giây
            $this->run_background_task_poll();
        }
    }

    public function handle_manual_task_poll_ajax() {
        check_ajax_referer('omni_bridge_admin_nonce', 'security');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Bạn không có quyền thực hiện.']);
        }
        $result = $this->run_background_task_poll();
        wp_send_json_success($result);
    }

    public function run_background_task_poll() {
        $health = $this->check_license_health();
        if (!$health['valid']) {
            return ['status' => 'skipped', 'message' => 'License không hợp lệ: ' . $health['message']];
        }

        $default_portal = (defined('OMNI_PORTAL_URL') && OMNI_PORTAL_URL) ? OMNI_PORTAL_URL : (getenv('PORTAL_BASE_URL') ?: 'https://seo.vuonghoang.name.vn/');
        $portal_url = get_option('omni_bridge_portal_url', $default_portal);
        $license_key = get_option('omni_bridge_license_key', '');
        $current_domain = $this->get_current_host_domain();

        if (empty($license_key)) {
            return ['status' => 'skipped', 'message' => 'Chưa cấu hình License Key.'];
        }

        $poll_url = rtrim($portal_url, '/') . '/api/v1/bridge/tasks/poll';
        $res = wp_remote_post($poll_url, [
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => wp_json_encode([
                'license_key' => $license_key,
                'domain'      => $current_domain,
                'limit'       => 5
            ]),
            'timeout' => 10,
            'sslverify' => false
        ]);

        if (is_wp_error($res)) {
            return ['status' => 'error', 'message' => $res->get_error_message()];
        }

        $code = wp_remote_retrieve_response_code($res);
        $body = json_decode(wp_remote_retrieve_body($res), true);

        if ($code !== 200 || !isset($body['tasks'])) {
            return ['status' => 'error', 'message' => $body['detail'] ?? 'Không thể tải tasks'];
        }

        $tasks = $body['tasks'];
        $executed = 0;

        foreach ($tasks as $t) {
            $task_id = $t['task_id'];
            $action  = $t['action'];
            $payload = $t['payload'];

            try {
                if ($action === 'publish_article') {
                    $post_data = $this->execute_internal_post_publish($payload);
                    $this->send_task_ack($portal_url, $license_key, $task_id, 'completed', $post_data);
                    $executed++;
                } else {
                    $this->send_task_ack($portal_url, $license_key, $task_id, 'failed', null, 'Action không hỗ trợ');
                }
            } catch (Exception $e) {
                $this->send_task_ack($portal_url, $license_key, $task_id, 'failed', null, $e->getMessage());
            }
        }

        update_option('omni_bridge_last_poll_time', current_time('mysql'));
        update_option('omni_bridge_last_poll_count', $executed);

        return [
            'status'   => 'success',
            'executed' => $executed,
            'time'     => current_time('mysql')
        ];
    }

    private function execute_internal_post_publish($data) {
        $title   = sanitize_text_field($data['title'] ?? 'Bài viết mới');
        $content = wp_kses_post($data['content'] ?? '');
        $slug    = sanitize_title($data['slug'] ?? $title);
        $status  = sanitize_text_field($data['status'] ?? 'publish');
        $post_type = sanitize_key($data['post_type'] ?? 'post');

        $post_arr = [
            'post_title'   => $title,
            'post_content' => $content,
            'post_name'    => $slug,
            'post_status'  => $status,
            'post_type'    => $post_type,
            'post_author'  => 1
        ];

        // Hỗ trợ Lập lịch đăng bài tự động chuẩn WordPress Native (Scheduled Future Post)
        $raw_sched = !empty($data['scheduled_at']) ? $data['scheduled_at'] : (!empty($data['post_date']) ? $data['post_date'] : null);
        if ($raw_sched) {
            $schedule_ts = strtotime($raw_sched);
            if ($schedule_ts && $schedule_ts > time()) {
                $post_arr['post_status'] = 'future';
                $gmt_offset = floatval(get_option('gmt_offset', 7));
                $post_arr['post_date'] = gmdate('Y-m-d H:i:s', $schedule_ts + (int)($gmt_offset * 3600));
                $post_arr['post_date_gmt'] = gmdate('Y-m-d H:i:s', $schedule_ts);
                $post_arr['edit_date'] = true;
            }
        }

        // Tạo hoặc cập nhật bài viết bằng WordPress PHP nội bộ (100% không bị Cloudflare chặn)
        $post_id = wp_insert_post($post_arr);

        if (is_wp_error($post_id)) {
            throw new Exception($post_id->get_error_message());
        }

        // Gán chuyên mục nếu có
        if (!empty($data['category'])) {
            wp_set_object_terms($post_id, [$data['category']], 'category', true);
        }

        // Gán thẻ Tags
        if (!empty($data['tags']) && is_array($data['tags'])) {
            wp_set_object_terms($post_id, $data['tags'], 'post_tag', true);
        }

        // Tích hợp SEO Meta (RankMath & Yoast SEO)
        if (!empty($data['focus_keyword'])) {
            update_post_meta($post_id, 'rank_math_focus_keyword', sanitize_text_field($data['focus_keyword']));
            update_post_meta($post_id, '_yoast_wpseo_focuskw', sanitize_text_field($data['focus_keyword']));
        }
        if (!empty($data['meta_description'])) {
            update_post_meta($post_id, 'rank_math_description', sanitize_text_field($data['meta_description']));
            update_post_meta($post_id, '_yoast_wpseo_metadesc', sanitize_text_field($data['meta_description']));
        }

        return [
            'post_id'   => $post_id,
            'permalink' => get_permalink($post_id),
            'title'     => $title,
            'status'    => $status
        ];
    }

    private function send_task_ack($portal_url, $license_key, $task_id, $status, $result = null, $error = null) {
        $ack_url = rtrim($portal_url, '/') . '/api/v1/bridge/tasks/ack';
        wp_remote_post($ack_url, [
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => wp_json_encode([
                'task_id'       => $task_id,
                'license_key'   => $license_key,
                'status'        => $status,
                'result_data'   => $result,
                'error_message' => $error
            ]),
            'timeout' => 8,
            'sslverify' => false
        ]);
    }
}
