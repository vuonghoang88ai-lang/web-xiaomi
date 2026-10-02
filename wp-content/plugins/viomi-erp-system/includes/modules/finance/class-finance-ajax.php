<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Mi_ERP_Finance_Ajax {
    public function __construct() {
        add_action( 'wp_ajax_mi_erp_finance_load', array( $this, 'load_finance' ) );
        add_action( 'wp_ajax_mi_erp_finance_add_transaction', array( $this, 'add_transaction' ) );
        add_action( 'wp_ajax_mi_erp_finance_sync', array( $this, 'sync_historical_orders' ) );
        
        // Tự động sinh phiếu thu khi đơn hàng thành công/xử lý
        add_action( 'woocommerce_order_status_processing', array( $this, 'auto_record_order_income' ) );
        add_action( 'woocommerce_order_status_completed', array( $this, 'auto_record_order_income' ) );
    }

    private function check_permission() {
        check_ajax_referer( 'mi_erp_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permission denied' );
        }
    }

    public function load_finance() {
        $this->check_permission();
        global $wpdb;
        $table = $wpdb->prefix . 'mi_transactions';

        $transactions = $wpdb->get_results( "SELECT * FROM $table ORDER BY date DESC LIMIT 100" );
        
        $total_income = 0;
        $total_expense = 0;

        // Tối ưu DB query: Dùng SUM() thay vì loop PHP
        $sums = $wpdb->get_results( "SELECT type, SUM(amount) as total FROM $table GROUP BY type" );
        foreach ( $sums as $s ) {
            if ( $s->type === 'income' ) {
                $total_income = floatval($s->total);
            } else if ( $s->type === 'expense' ) {
                $total_expense = floatval($s->total);
            }
        }

        $net_profit = $total_income - $total_expense;

        $formatted_tx = array();
        foreach ( $transactions as $t ) {
            $formatted_tx[] = array(
                'id' => $t->id,
                'type' => $t->type,
                'amount_formatted' => wc_price( $t->amount ),
                'ref_type' => $t->ref_type,
                'ref_id' => $t->ref_id,
                'description' => $t->description,
                'date' => $t->date
            );
        }

        wp_send_json_success( array(
            'total_income' => wc_price( $total_income ),
            'total_expense' => wc_price( $total_expense ),
            'net_profit' => wc_price( $net_profit ),
            'transactions' => $formatted_tx
        ) );
    }

    public function add_transaction() {
        $this->check_permission();
        global $wpdb;
        $table = $wpdb->prefix . 'mi_transactions';

        $type = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '';
        $amount = isset( $_POST['amount'] ) ? floatval( $_POST['amount'] ) : 0;
        $ref = isset( $_POST['ref'] ) ? sanitize_text_field( wp_unslash( $_POST['ref'] ) ) : '';
        $desc = isset( $_POST['desc'] ) ? sanitize_textarea_field( wp_unslash( $_POST['desc'] ) ) : '';

        if ( ! in_array( $type, array( 'income', 'expense' ) ) || $amount <= 0 ) {
            wp_send_json_error( 'Dữ liệu không hợp lệ.' );
        }

        $wpdb->insert( $table, array(
            'type' => $type,
            'amount' => $amount,
            'ref_type' => $ref, // Using ref field to store manual ref for now
            'description' => $desc,
            'created_by' => get_current_user_id()
        ) );

        wp_send_json_success( 'Đã ghi nhận giao dịch.' );
    }

    public function auto_record_order_income( $order_id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'mi_transactions';
        
        // Tránh trùng lặp (nếu processing rồi completed không ghi 2 lần)
        $exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE ref_type = 'order' AND ref_id = %d", $order_id ) );
        if ( $exists ) {
            return;
        }
        
        $order = wc_get_order( $order_id );
        if ( ! $order ) return;
        
        $wpdb->insert( $table, array(
            'type'        => 'income',
            'amount'      => $order->get_total(),
            'ref_type'    => 'order',
            'ref_id'      => $order_id,
            'description' => 'Tự động ghi nhận doanh thu từ đơn hàng #' . $order_id,
            'created_by'  => 0 // System
        ), array( '%s', '%f', '%s', '%d', '%s', '%d' ) );
    }

    public function sync_historical_orders() {
        $this->check_permission();
        global $wpdb;
        
        $args = array(
            'status' => array( 'wc-processing', 'wc-completed' ),
            'limit'  => -1,
            'return' => 'ids',
        );
        
        $order_ids = wc_get_orders( $args );
        $synced_count = 0;
        
        foreach ( $order_ids as $order_id ) {
            $table = $wpdb->prefix . 'mi_transactions';
            $exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE ref_type = 'order' AND ref_id = %d", $order_id ) );
            if ( ! $exists ) {
                $order = wc_get_order( $order_id );
                if ( $order ) {
                    $wpdb->insert( $table, array(
                        'type'        => 'income',
                        'amount'      => $order->get_total(),
                        'ref_type'    => 'order',
                        'ref_id'      => $order_id,
                        'description' => 'Tự động ghi nhận doanh thu từ đơn hàng cũ #' . $order_id,
                        'created_by'  => get_current_user_id()
                    ), array( '%s', '%f', '%s', '%d', '%s', '%d' ) );
                    $synced_count++;
                }
            }
        }
        
        wp_send_json_success( 'Đã đồng bộ ' . $synced_count . ' đơn hàng vào sổ quỹ.' );
    }
}
new Mi_ERP_Finance_Ajax();
