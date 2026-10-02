<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Mi_ERP_Manufacturing_Ajax {
    public function __construct() {
        add_action( 'wp_ajax_mi_erp_load_manufacturing', array( $this, 'load_data' ) );
        add_action( 'wp_ajax_mi_erp_add_production_order', array( $this, 'add_order' ) );
        add_action( 'wp_ajax_mi_erp_update_production_status', array( $this, 'update_status' ) );
    }

    private function check_permission() {
        check_ajax_referer( 'mi_erp_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permission denied' );
        }
    }

    public function load_data() {
        $this->check_permission();
        global $wpdb;

        $table = $wpdb->prefix . 'mi_production_orders';
        $orders = $wpdb->get_results( "SELECT * FROM $table ORDER BY created_at DESC LIMIT 50" );

        $formatted = array();
        foreach ( $orders as $o ) {
            $product = wc_get_product( $o->product_id );
            $product_name = $product ? $product->get_name() : 'Sản phẩm #' . $o->product_id;
            
            $formatted[] = array(
                'id' => $o->id,
                'product_name' => $product_name,
                'quantity' => $o->quantity,
                'status' => $o->status,
                'created_at' => $o->created_at
            );
        }

        wp_send_json_success( array(
            'orders' => $formatted
        ) );
    }

    public function add_order() {
        $this->check_permission();
        global $wpdb;

        $product_id = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;
        $qty = isset( $_POST['qty'] ) ? intval( $_POST['qty'] ) : 0;
        
        if ( $product_id <= 0 || $qty <= 0 ) {
            wp_send_json_error( 'Vui lòng cung cấp ID sản phẩm và số lượng hợp lệ' );
        }

        $table = $wpdb->prefix . 'mi_production_orders';
        $wpdb->insert( $table, array(
            'product_id' => $product_id,
            'quantity' => $qty,
            'status' => 'pending'
        ) );

        wp_send_json_success( 'Đã tạo Lệnh Sản Xuất' );
    }

    public function update_status() {
        $this->check_permission();
        global $wpdb;

        $order_id = isset( $_POST['order_id'] ) ? intval( $_POST['order_id'] ) : 0;
        $status = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : ''; // completed
        $consumed_serials = isset( $_POST['consumed_serials'] ) ? wp_unslash( $_POST['consumed_serials'] ) : ''; // JSON ["SER-1", "SER-2"]
        
        if ( $order_id <= 0 || empty($status) ) {
            wp_send_json_error( 'Dữ liệu không hợp lệ' );
        }

        $table = $wpdb->prefix . 'mi_production_orders';
        $order = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $order_id ) );

        if ( !$order ) {
            wp_send_json_error( 'Không tìm thấy Lệnh sản xuất' );
        }

        if ( $order->status === 'completed' ) {
            wp_send_json_error( 'Lệnh này đã được hoàn tất trước đó' );
        }

        $wpdb->update( $table, array( 'status' => $status ), array( 'id' => $order_id ) );

        // Liên thông hệ thống SCM khi hoàn tất sản xuất
        if ( $status === 'completed' ) {
            $serial_table = $wpdb->prefix . 'mi_serials';
            $trans_table = $wpdb->prefix . 'mi_inventory_transactions';

            // 1. Trừ kho linh kiện (consumed)
            if ( !empty($consumed_serials) ) {
                $serials_array = json_decode($consumed_serials, true);
                if ( is_array($serials_array) && !empty($serials_array) ) {
                    foreach ( $serials_array as $serial_number ) {
                        $serial_number = sanitize_text_field( $serial_number );
                        $s_row = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM $serial_table WHERE serial_number = %s AND status = 'in_stock'", $serial_number ) );
                        if ( $s_row ) {
                            $wpdb->update( $serial_table, array( 'status' => 'consumed' ), array( 'id' => $s_row->id ) );
                            
                            $wpdb->insert( $trans_table, array(
                                'type' => 'out', // Đã xuất kho vì dùng cho sản xuất
                                'ref_type' => 'production',
                                'ref_id' => $order_id,
                                'created_by' => get_current_user_id()
                            ) );
                        }
                    }
                }
            }

            // 2. Sinh mã Serial cho thành phẩm nhập kho
            for ( $i = 1; $i <= $order->quantity; $i++ ) {
                // Format: MNF[OrderID]-[ProductID]-[Index]-[Random]
                $generated_serial = sprintf( 'MNF%d-%d-%d-%s', $order_id, $order->product_id, $i, strtoupper(substr(md5(uniqid()), 0, 4)) );
                
                $wpdb->insert( $serial_table, array(
                    'product_id' => $order->product_id,
                    'serial_number' => $generated_serial,
                    'status' => 'in_stock',
                    'warehouse_id' => 1 // Kho mặc định
                ) );
                
                $wpdb->insert( $trans_table, array(
                    'type' => 'in', // Nhập kho thành phẩm
                    'ref_type' => 'production',
                    'ref_id' => $order_id,
                    'created_by' => get_current_user_id()
                ) );
            }
        }

        wp_send_json_success( 'Đã cập nhật trạng thái Lệnh Sản Xuất và đồng bộ kho thành công' );
    }
}
new Mi_ERP_Manufacturing_Ajax();
