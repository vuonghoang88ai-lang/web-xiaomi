<?php
/**
 * Trait: plugin_interop_trait
 * Cung cấp Cổng Giao Tiếp & Kết Nối Phổ Quát (Universal Plugin Interoperability Gateway).
 * Cho phép Marketing AI kết nối, đọc options, kích hoạt action hooks, và truy vấn an toàn
 * bảng dữ liệu của BẤT KỲ plugin nào đang cài trên WordPress (ERP, CRM, POS, Kho vận...).
 * Tuân thủ SOP 23 (<= 300 dòng) và SOP 30 (Zero Hardcoding).
 */

if (!defined('ABSPATH')) {
    exit;
}

trait Omni_Bridge_Plugin_Interop_Trait {

    /**
     * REST Route: POST /omni-bridge/v1/interop/execute
     */
    public function handle_interop_execute($request) {
        $params = $request->get_json_params() ?: $request->get_params();
        $action_type = !empty($params['action_type']) ? sanitize_key($params['action_type']) : '';

        if (empty($action_type)) {
            return new WP_Error('missing_action', 'action_type là bắt buộc (get_option, do_action, apply_filters, query_table, call_method).', array('status' => 400));
        }

        switch ($action_type) {
            case 'get_option':
                return $this->interop_get_option($params);

            case 'update_option':
                return $this->interop_update_option($params);

            case 'do_action':
                return $this->interop_do_action($params);

            case 'apply_filters':
                return $this->interop_apply_filters($params);

            case 'query_table':
                return $this->interop_query_table($params);

            case 'call_method':
                return $this->interop_call_method($params);

            default:
                return new WP_Error('unsupported_action', 'Hành động interop không được hỗ trợ: ' . esc_html($action_type), array('status' => 400));
        }
    }

    private function interop_get_option($params) {
        $key = !empty($params['option_key']) ? sanitize_text_field($params['option_key']) : '';
        if (empty($key)) {
            return new WP_Error('missing_key', 'option_key là bắt buộc.', array('status' => 400));
        }
        $val = get_option($key, null);
        return rest_ensure_response(array(
            'status'     => 'success',
            'option_key' => $key,
            'value'      => $val,
            'exists'     => $val !== null
        ));
    }

    private function interop_update_option($params) {
        $key = !empty($params['option_key']) ? sanitize_text_field($params['option_key']) : '';
        if (empty($key)) {
            return new WP_Error('missing_key', 'option_key là bắt buộc.', array('status' => 400));
        }
        $val = isset($params['value']) ? $params['value'] : '';
        update_option($key, $val);
        return rest_ensure_response(array(
            'status'     => 'success',
            'option_key' => $key,
            'updated'    => true
        ));
    }

    private function interop_do_action($params) {
        $hook = !empty($params['hook']) ? sanitize_text_field($params['hook']) : '';
        if (empty($hook)) {
            return new WP_Error('missing_hook', 'hook name là bắt buộc.', array('status' => 400));
        }
        $args = !empty($params['args']) && is_array($params['args']) ? $params['args'] : array();
        
        // Kích hoạt Action Hook của Plugin bên thứ ba
        do_action_ref_array($hook, $args);

        return rest_ensure_response(array(
            'status'  => 'success',
            'action'  => 'do_action',
            'hook'    => $hook,
            'message' => 'Action hook đã được kích hoạt thành công trên WordPress runtime.'
        ));
    }

    private function interop_apply_filters($params) {
        $hook = !empty($params['hook']) ? sanitize_text_field($params['hook']) : '';
        if (empty($hook)) {
            return new WP_Error('missing_hook', 'hook name là bắt buộc.', array('status' => 400));
        }
        $value = isset($params['value']) ? $params['value'] : null;
        $args = !empty($params['args']) && is_array($params['args']) ? $params['args'] : array();
        array_unshift($args, $value);

        $result = apply_filters_ref_array($hook, $args);

        return rest_ensure_response(array(
            'status' => 'success',
            'action' => 'apply_filters',
            'hook'   => $hook,
            'result' => $result
        ));
    }

    /**
     * Truy vấn an toàn bảng dữ liệu riêng của plugin bên thứ ba (Chỉ cho phép SELECT có điều kiện)
     */
    private function interop_query_table($params) {
        global $wpdb;
        $table = !empty($params['table']) ? sanitize_text_field($params['table']) : '';
        if (empty($table)) {
            return new WP_Error('missing_table', 'table name là bắt buộc.', array('status' => 400));
        }

        // BẮT BUỘC có prefix của WordPress để chống tấn công xuyên bảng hệ thống
        if (strpos($table, $wpdb->prefix) !== 0) {
            $table = $wpdb->prefix . ltrim($table, '_');
        }

        // Kiểm tra bảng có tồn tại không
        $check = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
        if ($check !== $table) {
            return new WP_Error('table_not_found', 'Bảng dữ liệu không tồn tại: ' . esc_html($table), array('status' => 404));
        }

        $limit = !empty($params['limit']) ? min(intval($params['limit']), 100) : 20;
        $where = !empty($params['where']) && is_array($params['where']) ? $params['where'] : array();

        $where_clauses = array('1=1');
        $where_vals = array();
        foreach ($where as $col => $val) {
            $clean_col = preg_replace('/[^a-zA-Z0-9_]/', '', $col);
            if (!empty($clean_col)) {
                $where_clauses[] = "`{$clean_col}` = %s";
                $where_vals[] = strval($val);
            }
        }

        $sql = "SELECT * FROM `{$table}` WHERE " . implode(' AND ', $where_clauses) . " LIMIT " . intval($limit);
        if (!empty($where_vals)) {
            $sql = $wpdb->prepare($sql, $where_vals);
        }

        $rows = $wpdb->get_results($sql, ARRAY_A);
        return rest_ensure_response(array(
            'status'      => 'success',
            'table'       => $table,
            'total_rows'  => count($rows),
            'rows'        => $rows
        ));
    }

    /**
     * Gọi hàm/phương thức công khai của Plugin bên thứ 3 (Singleton / Static Method)
     */
    private function interop_call_method($params) {
        $class = !empty($params['class_name']) ? sanitize_text_field($params['class_name']) : '';
        $method = !empty($params['method_name']) ? sanitize_text_field($params['method_name']) : '';
        $args = !empty($params['args']) && is_array($params['args']) ? $params['args'] : array();

        if (!class_exists($class)) {
            return new WP_Error('class_not_found', 'Lớp PHP không tồn tại hoặc plugin chưa được nạp: ' . esc_html($class), array('status' => 404));
        }
        if (!method_exists($class, $method)) {
            return new WP_Error('method_not_found', 'Phương thức không tồn tại trong lớp: ' . esc_html($method), array('status' => 404));
        }

        try {
            // Kiểm tra Singleton hoặc Static Method
            if (method_exists($class, 'instance')) {
                $instance = call_user_func(array($class, 'instance'));
                $res = call_user_func_array(array($instance, $method), $args);
            } elseif (method_exists($class, 'get_instance')) {
                $instance = call_user_func(array($class, 'get_instance'));
                $res = call_user_func_array(array($instance, $method), $args);
            } else {
                $res = call_user_func_array(array($class, $method), $args);
            }

            return rest_ensure_response(array(
                'status' => 'success',
                'class'  => $class,
                'method' => $method,
                'result' => $res
            ));
        } catch (Throwable $e) {
            return new WP_Error('execution_failed', 'Lỗi thực thi phương thức: ' . $e->getMessage(), array('status' => 500));
        }
    }
}

