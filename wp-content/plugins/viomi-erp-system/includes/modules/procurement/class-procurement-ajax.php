<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Mi_ERP_Procurement_Ajax {
    public function __construct() {
        add_action( 'wp_ajax_mi_erp_load_procurement', array( $this, 'load_procurement_data' ) );
        add_action( 'wp_ajax_mi_erp_add_supplier', array( $this, 'add_supplier' ) );
        add_action( 'wp_ajax_mi_erp_add_supplier', array( $this, 'add_supplier' ) );
        add_action( 'wp_ajax_mi_erp_add_po', array( $this, 'add_po' ) );
        add_action( 'wp_ajax_mi_erp_update_po_status', array( $this, 'update_po_status' ) );
    }

    private function check_permission() {
        check_ajax_referer( 'mi_erp_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permission denied' );
        }
    }

    public function load_procurement_data() {
        $this->check_permission();
        global $wpdb;

        $table_sup = $wpdb->prefix . 'mi_suppliers';
        $table_po = $wpdb->prefix . 'mi_purchase_orders';

        $suppliers = $wpdb->get_results( "SELECT * FROM $table_sup ORDER BY name ASC" );
        
        $pos = $wpdb->get_results( "
            SELECT po.*, sup.name as supplier_name 
            FROM $table_po po
            LEFT JOIN $table_sup sup ON po.supplier_id = sup.id
            ORDER BY po.created_at DESC LIMIT 50
        " );

        $formatted_pos = array();
        foreach ( $pos as $p ) {
            $formatted_pos[] = array(
                'id' => $p->id,
                'supplier_name' => $p->supplier_name ? $p->supplier_name : 'Unknown',
                'total_amount' => wc_price( $p->total_amount ),
                'status' => $p->status,
                'created_at' => $p->created_at
            );
        }

        wp_send_json_success( array(
            'suppliers' => $suppliers,
            'pos' => $formatted_pos
        ) );
    }

    public function add_supplier() {
        $this->check_permission();
        global $wpdb;
        
        $name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
        $phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
        $email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
        
        if ( empty( $name ) ) {
            wp_send_json_error( 'Tên nhà cung cấp không được để trống' );
        }

        $table_sup = $wpdb->prefix . 'mi_suppliers';
        $wpdb->insert( $table_sup, array(
            'name' => $name,
            'phone' => $phone,
            'email' => $email
        ) );

        wp_send_json_success( 'Đã thêm nhà cung cấp' );
    }

    public function add_po() {
        $this->check_permission();
        global $wpdb;

        $supplier_id = isset( $_POST['supplier_id'] ) ? intval( $_POST['supplier_id'] ) : 0;
        $amount = isset( $_POST['amount'] ) ? floatval( $_POST['amount'] ) : 0;
        $items_json = isset( $_POST['items'] ) ? wp_unslash( $_POST['items'] ) : ''; 
        
        if ( $supplier_id <= 0 || $amount <= 0 ) {
            wp_send_json_error( 'Vui lòng chọn nhà cung cấp và nhập số tiền hợp lệ' );
        }

        $table_po = $wpdb->prefix . 'mi_purchase_orders';
        $wpdb->insert( $table_po, array(
            'supplier_id' => $supplier_id,
            'total_amount' => $amount,
            'status' => 'draft',
            'created_by' => get_current_user_id()
        ) );
        
        $po_id = $wpdb->insert_id;
        
        // Lưu chi tiết PO Items
        if ( !empty($items_json) ) {
            $items = json_decode($items_json, true);
            if ( is_array($items) ) {
                $table_po_items = $wpdb->prefix . 'mi_po_items';
                foreach ($items as $item) {
                    $pid = intval($item['product_id']);
                    $qty = intval($item['quantity']);
                    $price = floatval($item['unit_price']);
                    if ($pid > 0 && $qty > 0) {
                        $wpdb->insert( $table_po_items, array(
                            'po_id' => $po_id,
                            'product_id' => $pid,
                            'quantity' => $qty,
                            'unit_price' => $price
                        ) );
                    }
                }
            }
        }

        wp_send_json_success( 'Đã tạo Đơn Đặt Hàng (PO)' );
    }

    public function update_po_status() {
        $this->check_permission();
        global $wpdb;
        
        $po_id = isset( $_POST['po_id'] ) ? intval( $_POST['po_id'] ) : 0;
        $status = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : ''; // received
        
        if ( $po_id <= 0 || empty($status) ) {
            wp_send_json_error( 'Dữ liệu không hợp lệ' );
        }

        $table_po = $wpdb->prefix . 'mi_purchase_orders';
        
        $po = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_po WHERE id = %d", $po_id ) );
        if ( !$po ) {
            wp_send_json_error( 'Không tìm thấy PO' );
        }
        
        if ( $po->status === 'received' ) {
            wp_send_json_error( 'PO này đã được xử lý nhận hàng trước đó' );
        }

        $wpdb->update( $table_po, array( 'status' => $status ), array( 'id' => $po_id ) );

        // Automation liên thông phân hệ khi "received" (Đã nhận hàng)
        if ( $status === 'received' ) {
            // 1. Tự động sinh Phiếu Chi cho hệ thống Finance
            $finance_table = $wpdb->prefix . 'mi_transactions';
            $wpdb->insert( $finance_table, array(
                'type' => 'expense',
                'amount' => $po->total_amount,
                'ref_type' => 'po',
                'ref_id' => $po_id,
                'description' => 'Thanh toán tự động cho Purchase Order #' . $po_id,
                'created_by' => get_current_user_id()
            ) );
            
            // 2. Tự động sinh giao dịch Nhập kho cho SCM
            $table_po_items = $wpdb->prefix . 'mi_po_items';
            $items = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_po_items WHERE po_id = %d", $po_id ) );
            
            $serial_table = $wpdb->prefix . 'mi_serials';
            $trans_table = $wpdb->prefix . 'mi_inventory_transactions';
            
            foreach ( $items as $item ) {
                for ( $i = 1; $i <= $item->quantity; $i++ ) {
                    // Tự động generate mã Serial tạm (PO-ID-PID-Qty-Random)
                    $generated_serial = sprintf( 'PO%d-%d-%d-%s', $po_id, $item->product_id, $i, strtoupper(substr(md5(uniqid()), 0, 4)) );
                    
                    $wpdb->insert( $serial_table, array(
                        'product_id' => $item->product_id,
                        'serial_number' => $generated_serial,
                        'status' => 'in_stock',
                        'warehouse_id' => 1 // Kho mặc định
                    ) );
                    
                    $serial_id = $wpdb->insert_id;
                    
                    $wpdb->insert( $trans_table, array(
                        'type' => 'in',
                        'ref_type' => 'po',
                        'ref_id' => $po_id,
                        'created_by' => get_current_user_id()
                    ) );
                }
            }
        }
        
        wp_send_json_success( 'Đã cập nhật trạng thái PO và đồng bộ hệ thống thành công!' );
    }
}
new Mi_ERP_Procurement_Ajax();
