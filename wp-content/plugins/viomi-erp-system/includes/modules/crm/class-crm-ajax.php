<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Mi_ERP_CRM_Ajax {
    public function __construct() {
        add_action( 'wp_ajax_mi_erp_crm_search', array( $this, 'crm_search' ) );
        add_action( 'wp_ajax_mi_erp_crm_create_ticket', array( $this, 'create_ticket' ) );
        add_action( 'wp_ajax_mi_erp_crm_update_ticket', array( $this, 'update_ticket' ) );
        
        // Tra cứu bảo hành (Frontend & Backend)
        add_action( 'wp_ajax_mi_erp_check_warranty', array( $this, 'check_warranty' ) );
        add_action( 'wp_ajax_nopriv_mi_erp_check_warranty', array( $this, 'check_warranty' ) );
    }

    private function check_permission() {
        check_ajax_referer( 'mi_erp_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permission denied' );
        }
    }

    public function crm_search() {
        $this->check_permission();
        global $wpdb;

        $phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
        if ( empty( $phone ) ) {
            wp_send_json_error( 'Vui lòng nhập số điện thoại' );
        }

        // Theo quy chuẩn BUG-03: Tìm kiếm SĐT cần dùng LIKE %s
        $orders = $wpdb->get_results( $wpdb->prepare( "
            SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = '_billing_phone' AND meta_value LIKE %s
        ", '%' . $wpdb->esc_like( $phone ) . '%' ) );

        if ( empty( $orders ) ) {
            wp_send_json_error( 'Không tìm thấy dữ liệu khách hàng với số điện thoại này.' );
        }

        $order_ids = array();
        foreach ( $orders as $o ) {
            $order_ids[] = (int) $o->post_id;
        }

        $latest_order_id = max($order_ids);
        $order = wc_get_order( $latest_order_id );

        $customer_data = array(
            'name' => $order ? $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() : '',
            'phone' => $order ? $order->get_billing_phone() : $phone,
            'address' => $order ? $order->get_billing_address_1() . ', ' . $order->get_billing_city() : '',
            'total_spent' => 0
        );

        // Tính tổng chi tiêu
        $total_spent = 0;
        foreach ( $order_ids as $oid ) {
            $o = wc_get_order( $oid );
            if ( $o && $o->get_status() !== 'cancelled' && $o->get_status() !== 'failed' ) {
                $total_spent += $o->get_total();
            }
        }
        $customer_data['total_spent'] = wc_price( $total_spent );

        $serial_table = $wpdb->prefix . 'mi_serials';
        $placeholders = implode( ',', array_fill( 0, count( $order_ids ), '%d' ) );
        $serials = $wpdb->get_results( $wpdb->prepare( "
            SELECT * FROM $serial_table WHERE order_id IN ($placeholders) ORDER BY sold_date DESC
        ", $order_ids ) );

        $serials_data = array();
        foreach ( $serials as $s ) {
            $product_name = get_the_title( $s->product_id );
            
            $warranty_months = get_post_meta( $s->product_id, 'mi_warranty_info', true );
            if ( ! $warranty_months ) $warranty_months = 12;

            $sold_timestamp = strtotime( $s->sold_date );
            $end_timestamp = strtotime( "+{$warranty_months} months", $sold_timestamp );
            $is_valid = time() <= $end_timestamp;

            $serials_data[] = array(
                'order_id'      => $s->order_id,
                'order_url'     => admin_url( 'post.php?post=' . $s->order_id . '&action=edit' ),
                'product_name'  => $product_name,
                'serial_number' => $s->serial_number,
                'sold_date'     => date( 'd/m/Y', $sold_timestamp ),
                'warranty_end'  => date( 'd/m/Y', $end_timestamp ),
                'warranty_valid'=> $is_valid
            );
        }

        $ticket_table = $wpdb->prefix . 'mi_tickets';
        $tickets = $wpdb->get_results( $wpdb->prepare( "
            SELECT * FROM $ticket_table WHERE customer_phone = %s ORDER BY created_at DESC
        ", $phone ) );

        wp_send_json_success( array(
            'customer' => $customer_data,
            'serials'  => $serials_data,
            'tickets'  => $tickets
        ) );
    }

    public function create_ticket() {
        $this->check_permission();
        global $wpdb;
        $table = $wpdb->prefix . 'mi_tickets';
        
        $phone = isset($_POST['phone']) ? sanitize_text_field(wp_unslash($_POST['phone'])) : '';
        $serial = isset($_POST['serial']) ? sanitize_text_field(wp_unslash($_POST['serial'])) : '';
        $issue = isset($_POST['issue']) ? sanitize_text_field(wp_unslash($_POST['issue'])) : '';
        $note = isset($_POST['note']) ? sanitize_textarea_field(wp_unslash($_POST['note'])) : '';
        
        if (empty($phone) || empty($issue)) {
            wp_send_json_error('Vui lòng điền đủ thông tin');
        }
        
        $wpdb->insert($table, array(
            'customer_phone' => $phone,
            'serial_number' => $serial,
            'issue_type' => $issue,
            'note' => $note,
            'status' => 'open',
            'assigned_to' => get_current_user_id()
        ));
        
        wp_send_json_success('Tạo ticket thành công');
    }

    public function update_ticket() {
        $this->check_permission();
        global $wpdb;
        $table = $wpdb->prefix . 'mi_tickets';
        
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $status = isset($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : '';
        
        if ($id <= 0 || empty($status)) {
            wp_send_json_error('Dữ liệu không hợp lệ');
        }
        
        $wpdb->update($table, array('status' => $status), array('id' => $id));
        wp_send_json_success('Cập nhật thành công');
    }

    public function check_warranty() {
        global $wpdb;
        $keyword = isset( $_POST['keyword'] ) ? sanitize_text_field( wp_unslash( $_POST['keyword'] ) ) : '';
        
        if ( empty( $keyword ) ) {
            wp_send_json_error( 'Vui lòng nhập số điện thoại hoặc mã Serial.' );
        }

        $serial_table = $wpdb->prefix . 'mi_serials';
        $serials = array();

        // 1. Tìm theo số Serial chính xác
        $serial_result = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $serial_table WHERE serial_number = %s", $keyword ) );
        if ( !empty($serial_result) ) {
            $serials = $serial_result;
        } else {
            // 2. Tìm theo số điện thoại (SĐT trong _billing_phone) - BUG-03
            $orders = $wpdb->get_results( $wpdb->prepare( "
                SELECT post_id FROM {$wpdb->postmeta} 
                WHERE meta_key = '_billing_phone' AND meta_value LIKE %s
            ", '%' . $wpdb->esc_like( $keyword ) . '%' ) );

            if ( !empty($orders) ) {
                $order_ids = wp_list_pluck( $orders, 'post_id' );
                $placeholders = implode( ',', array_fill( 0, count( $order_ids ), '%d' ) );
                $serials = $wpdb->get_results( $wpdb->prepare( "
                    SELECT * FROM $serial_table WHERE order_id IN ($placeholders) ORDER BY sold_date DESC
                ", $order_ids ) );
            }
        }

        if ( empty( $serials ) ) {
            wp_send_json_error( 'Không tìm thấy thông tin bảo hành với dữ liệu này.' );
        }

        $results = array();
        foreach ( $serials as $s ) {
            // Chỉ hiển thị sản phẩm đã bán (kích hoạt bảo hành)
            if ( $s->status !== 'sold' && $s->status !== 'defective' ) continue;

            $product_name = get_the_title( $s->product_id );
            
            // Logic tính hạn bảo hành: Ưu tiên ACF, fallback get_post_meta
            $warranty_months = 12;
            if ( function_exists('get_field') ) {
                $acf_val = get_field('mi_warranty_info', $s->product_id);
                if ( $acf_val ) {
                    $warranty_months = intval($acf_val);
                } else {
                    $meta_val = get_post_meta( $s->product_id, 'mi_warranty_info', true );
                    if ( $meta_val ) $warranty_months = intval($meta_val);
                }
            } else {
                $meta_val = get_post_meta( $s->product_id, 'mi_warranty_info', true );
                if ( $meta_val ) $warranty_months = intval($meta_val);
            }

            $sold_timestamp = strtotime( $s->sold_date );
            $end_timestamp = strtotime( "+{$warranty_months} months", $sold_timestamp );
            $is_valid = time() <= $end_timestamp;

            $results[] = array(
                'product_name'  => $product_name,
                'serial_number' => $s->serial_number,
                'sold_date'     => date( 'd/m/Y', $sold_timestamp ),
                'warranty_end'  => date( 'd/m/Y', $end_timestamp ),
                'warranty_valid'=> $is_valid,
                'months'        => $warranty_months
            );
        }
        
        if ( empty($results) ) {
            wp_send_json_error( 'Các sản phẩm tìm thấy chưa được kích hoạt bảo hành.' );
        }

        wp_send_json_success( $results );
    }
}

new Mi_ERP_CRM_Ajax();
