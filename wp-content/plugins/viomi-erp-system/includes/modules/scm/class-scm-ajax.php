<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Mi_ERP_SCM_Ajax {
    public function __construct() {
        // Lấy danh sách kho
        add_action( 'wp_ajax_mi_erp_get_warehouses', array( $this, 'get_warehouses' ) );
        // Thêm/Sửa kho
        add_action( 'wp_ajax_mi_erp_save_warehouse', array( $this, 'save_warehouse' ) );
        // Xóa kho
        add_action( 'wp_ajax_mi_erp_delete_warehouse', array( $this, 'delete_warehouse' ) );

        // Quản lý tồn kho
        add_action( 'wp_ajax_mi_erp_get_inventory_list', array( $this, 'get_inventory_list' ) );
        add_action( 'wp_ajax_mi_erp_add_inventory', array( $this, 'add_inventory' ) );
        add_action( 'wp_ajax_mi_erp_transfer_inventory', array( $this, 'transfer_inventory' ) );
        add_action( 'wp_ajax_mi_erp_outbound_inventory', array( $this, 'outbound_inventory' ) );
        
        // Gán mã Serial vào Đơn hàng (Xuất kho thủ công)
        add_action( 'wp_ajax_mi_erp_assign_order_serial', array( $this, 'assign_order_serial' ) );
        
        // Tìm kiếm sản phẩm WooCommerce
        add_action( 'wp_ajax_mi_erp_search_products', array( $this, 'search_products' ) );
    }

    private function check_permission() {
        check_ajax_referer( 'mi_erp_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permission denied' );
        }
    }

    public function get_warehouses() {
        $this->check_permission();
        global $wpdb;
        $table = $wpdb->prefix . 'mi_warehouses';
        $results = $wpdb->get_results( "SELECT * FROM $table ORDER BY id DESC" );
        wp_send_json_success( $results );
    }

    public function save_warehouse() {
        $this->check_permission();
        global $wpdb;
        $table = $wpdb->prefix . 'mi_warehouses';

        $id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
        $name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
        $location = isset( $_POST['location'] ) ? sanitize_text_field( wp_unslash( $_POST['location'] ) ) : '';
        $status = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : 'active';

        if ( empty( $name ) ) {
            wp_send_json_error( 'Tên kho không được để trống' );
        }

        $data = array(
            'name' => $name,
            'location' => $location,
            'status' => $status
        );

        $format = array( '%s', '%s', '%s' );

        if ( $id > 0 ) {
            $result = $wpdb->update( $table, $data, array( 'id' => $id ), $format, array( '%d' ) );
            if ( $result === false ) {
                wp_send_json_error( 'Lỗi khi cập nhật kho' );
            }
            wp_send_json_success( 'Cập nhật kho thành công' );
        } else {
            $result = $wpdb->insert( $table, $data, $format );
            if ( $result === false ) {
                wp_send_json_error( 'Lỗi khi thêm kho' );
            }
            wp_send_json_success( 'Thêm kho thành công' );
        }
    }

    public function delete_warehouse() {
        $this->check_permission();
        global $wpdb;
        $table = $wpdb->prefix . 'mi_warehouses';
        $id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;

        if ( $id > 0 ) {
            // Kiểm tra kho có chứa hàng hóa không
            $serial_table = $wpdb->prefix . 'mi_serials';
            $count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(id) FROM $serial_table WHERE warehouse_id = %d AND status = 'in_stock'", $id ) );
            
            if ( $count > 0 ) {
                wp_send_json_error( 'Không thể xóa kho đang chứa sản phẩm' );
            }

            $wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );
            wp_send_json_success( 'Xóa kho thành công' );
        }
        wp_send_json_error( 'ID không hợp lệ' );
    }

    public function get_inventory_list() {
        $this->check_permission();
        global $wpdb;
        $serial_table = $wpdb->prefix . 'mi_serials';
        $warehouse_table = $wpdb->prefix . 'mi_warehouses';
        
        $sql = "SELECT s.id, s.product_id, s.serial_number, w.name as warehouse_name 
                FROM $serial_table s 
                LEFT JOIN $warehouse_table w ON s.warehouse_id = w.id 
                WHERE s.status = 'in_stock' 
                ORDER BY s.id DESC LIMIT 100";
        $results = $wpdb->get_results( $sql );

        foreach ( $results as $row ) {
            $row->product_name = get_the_title( $row->product_id );
        }

        wp_send_json_success( $results );
    }

    public function add_inventory() {
        $this->check_permission();
        global $wpdb;

        $warehouse_id = isset( $_POST['warehouse_id'] ) ? intval( $_POST['warehouse_id'] ) : 0;
        $product_id = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;
        $serial_text = isset( $_POST['serial_number'] ) ? sanitize_textarea_field( wp_unslash( $_POST['serial_number'] ) ) : '';
        $import_price = isset( $_POST['import_price'] ) ? floatval( $_POST['import_price'] ) : 0;

        if ( ! $warehouse_id || ! $product_id || empty( $serial_text ) ) {
            wp_send_json_error( 'Vui lòng điền đầy đủ thông tin' );
        }

        // Kiểm tra product_id có phải là sản phẩm hợp lệ không
        $product = get_post( $product_id );
        if ( ! $product || $product->post_type !== 'product' ) {
            wp_send_json_error( 'ID Sản phẩm không hợp lệ (Không tồn tại trong WooCommerce)' );
        }

        // Cập nhật giá nhập cho sản phẩm nếu có
        if ( $import_price > 0 ) {
            update_post_meta( $product_id, '_mi_cost', $import_price );
            update_post_meta( $product_id, '_wc_cog_cost', $import_price ); // Tương thích với plugin Cost of Goods
        }

        $serials = array_filter( array_map( 'trim', explode( "\n", $serial_text ) ) );
        if ( empty( $serials ) ) {
            wp_send_json_error( 'Không có mã Serial nào hợp lệ' );
        }

        $serial_table = $wpdb->prefix . 'mi_serials';
        $trans_table = $wpdb->prefix . 'mi_inventory_transactions';
        
        $added = 0;
        $errors = array();

        foreach ( $serials as $serial ) {
            // Kiểm tra serial đã tồn tại chưa
            $exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $serial_table WHERE serial_number = %s", $serial ) );
            if ( $exists ) {
                $errors[] = "$serial (Trùng lặp)";
                continue;
            }

            // Thêm vào bảng serials
            $result = $wpdb->insert(
                $serial_table,
                array(
                    'product_id' => $product_id,
                    'serial_number' => $serial,
                    'status' => 'in_stock',
                    'warehouse_id' => $warehouse_id,
                ),
                array( '%d', '%s', '%s', '%d' )
            );

            if ( $result ) {
                $serial_id = $wpdb->insert_id;
                // Ghi log vào mi_inventory_transactions
                $wpdb->insert(
                    $trans_table,
                    array(
                        'type' => 'in',
                        'ref_type' => 'serial',
                        'ref_id' => $serial_id,
                        'created_by' => get_current_user_id()
                    ),
                    array( '%s', '%s', '%d', '%d' )
                );
                $added++;
            } else {
                $errors[] = "$serial (Lỗi DB)";
            }
        }

        if ( $added > 0 ) {
            $msg = "Đã nhập kho thành công $added mã Serial.";
            if ( ! empty( $errors ) ) {
                $msg .= " Bỏ qua các mã lỗi: " . implode( ', ', $errors );
            }
            wp_send_json_success( $msg );
        } else {
            wp_send_json_error( 'Không thể thêm mã Serial nào. Chi tiết lỗi: ' . implode( ', ', $errors ) );
        }
    }

    public function transfer_inventory() {
        $this->check_permission();
        global $wpdb;

        $warehouse_id = isset( $_POST['warehouse_id'] ) ? intval( $_POST['warehouse_id'] ) : 0;
        $product_id = isset( $_POST['product_id'] ) ? intval( $_POST['product_id'] ) : 0;
        $serial_text = isset( $_POST['serial_number'] ) ? sanitize_textarea_field( wp_unslash( $_POST['serial_number'] ) ) : '';

        if ( ! $warehouse_id || empty( $serial_text ) ) {
            wp_send_json_error( 'Vui lòng điền mã Serial và chọn Kho đích' );
        }

        $serials = array_filter( array_map( 'trim', explode( "\n", $serial_text ) ) );
        if ( empty( $serials ) ) {
            wp_send_json_error( 'Không có mã Serial nào hợp lệ' );
        }

        $serial_table = $wpdb->prefix . 'mi_serials';
        $trans_table = $wpdb->prefix . 'mi_inventory_transactions';
        
        $transferred = 0;
        $errors = array();

        foreach ( $serials as $serial ) {
            // Kiểm tra serial có tồn tại và đang in_stock không
            $serial_obj = $wpdb->get_row( $wpdb->prepare( "SELECT id, product_id, warehouse_id, status FROM $serial_table WHERE serial_number = %s", $serial ) );

            if ( ! $serial_obj ) {
                $errors[] = "$serial (Không tìm thấy)";
                continue;
            }

            if ( $product_id > 0 && $serial_obj->product_id != $product_id ) {
                $errors[] = "$serial (Không khớp SP)";
                continue;
            }

            if ( $serial_obj->status !== 'in_stock' ) {
                $errors[] = "$serial (Đã xuất hoặc lỗi)";
                continue;
            }

            if ( $serial_obj->warehouse_id == $warehouse_id ) {
                $errors[] = "$serial (Đang ở kho đích)";
                continue;
            }

            // Cập nhật warehouse_id mới
            $wpdb->update(
                $serial_table,
                array( 'warehouse_id' => $warehouse_id ),
                array( 'id' => $serial_obj->id ),
                array( '%d' ),
                array( '%d' )
            );

            // Ghi log
            $wpdb->insert(
                $trans_table,
                array(
                    'type' => 'transfer',
                    'ref_type' => 'serial',
                    'ref_id' => $serial_obj->id,
                    'created_by' => get_current_user_id()
                ),
                array( '%s', '%s', '%d', '%d' )
            );
            
            $transferred++;
        }

        if ( $transferred > 0 ) {
            $msg = "Đã chuyển kho thành công $transferred mã Serial.";
            if ( ! empty( $errors ) ) {
                $msg .= " Bỏ qua các mã lỗi: " . implode( ', ', $errors );
            }
            wp_send_json_success( $msg );
        } else {
            wp_send_json_error( 'Không thể chuyển mã Serial nào. Chi tiết lỗi: ' . implode( ', ', $errors ) );
        }
    }

    public function outbound_inventory() {
        $this->check_permission();
        global $wpdb;

        $serial_number = isset( $_POST['serial_number'] ) ? sanitize_text_field( wp_unslash( $_POST['serial_number'] ) ) : '';
        $reason = isset( $_POST['reason'] ) ? sanitize_text_field( wp_unslash( $_POST['reason'] ) ) : ''; // sold_manual, defective, lost

        if ( empty( $serial_number ) || empty( $reason ) ) {
            wp_send_json_error( 'Vui lòng điền mã Serial và chọn Lý do xuất' );
        }

        $serial_table = $wpdb->prefix . 'mi_serials';
        
        // Kiểm tra serial
        $serial = $wpdb->get_row( $wpdb->prepare( "SELECT id, status FROM $serial_table WHERE serial_number = %s", $serial_number ) );

        if ( ! $serial ) {
            wp_send_json_error( 'Không tìm thấy mã Serial này trong hệ thống' );
        }

        if ( $serial->status !== 'in_stock' ) {
            wp_send_json_error( 'Mã Serial này đã được xuất trước đó hoặc không tồn kho' );
        }

        // Cập nhật trạng thái
        $wpdb->update(
            $serial_table,
            array( 
                'status' => $reason,
                'sold_date' => current_time( 'mysql' ) // Ghi nhận thời điểm xuất
            ),
            array( 'id' => $serial->id ),
            array( '%s', '%s' ),
            array( '%d' )
        );

        // Ghi log
        $trans_table = $wpdb->prefix . 'mi_inventory_transactions';
        $wpdb->insert(
            $trans_table,
            array(
                'type' => 'out',
                'ref_type' => 'serial',
                'ref_id' => $serial->id,
                'created_by' => get_current_user_id()
            ),
            array( '%s', '%s', '%d', '%d' )
        );

        wp_send_json_success( "Đã xuất kho Serial $serial_number thành công với lý do: $reason!" );
    }

    public function assign_order_serial() {
        $this->check_permission();
        global $wpdb;

        $serial_number = isset( $_POST['serial_number'] ) ? sanitize_text_field( wp_unslash( $_POST['serial_number'] ) ) : '';
        $order_id = isset( $_POST['order_id'] ) ? intval( $_POST['order_id'] ) : 0;

        if ( empty( $serial_number ) || ! $order_id ) {
            wp_send_json_error( 'Vui lòng điền mã Serial và ID đơn hàng' );
        }

        $serial_table = $wpdb->prefix . 'mi_serials';
        
        // Kiểm tra serial
        $serial = $wpdb->get_row( $wpdb->prepare( "SELECT id, status FROM $serial_table WHERE serial_number = %s", $serial_number ) );

        if ( ! $serial ) {
            wp_send_json_error( 'Không tìm thấy mã Serial này trong hệ thống' );
        }

        if ( $serial->status !== 'in_stock' ) {
            wp_send_json_error( 'Mã Serial này đã được xuất trước đó hoặc không tồn kho' );
        }

        // Cập nhật trạng thái
        $wpdb->update(
            $serial_table,
            array( 
                'status' => 'sold',
                'order_id' => $order_id,
                'sold_date' => current_time( 'mysql' )
            ),
            array( 'id' => $serial->id ),
            array( '%s', '%d', '%s' ),
            array( '%d' )
        );

        // Ghi log
        $trans_table = $wpdb->prefix . 'mi_inventory_transactions';
        $wpdb->insert(
            $trans_table,
            array(
                'type' => 'out',
                'ref_type' => 'serial',
                'ref_id' => $serial->id,
                'created_by' => get_current_user_id()
            ),
            array( '%s', '%s', '%d', '%d' )
        );

        // Cập nhật Order Meta theo Rule 6
        update_post_meta( $order_id, '_mi_erp_serials_assigned', 'yes' );

        wp_send_json_success( "Đã gán Serial $serial_number cho Đơn hàng #$order_id thành công!" );
    }
    public function search_products() {
        $this->check_permission();
        
        $search = isset( $_POST['q'] ) ? sanitize_text_field( wp_unslash( $_POST['q'] ) ) : '';
        
        $args = array(
            'post_type'      => array( 'product', 'product_variation' ),
            'post_status'    => 'publish',
            'posts_per_page' => 20,
            's'              => $search,
        );
        
        if ( is_numeric( $search ) ) {
            $args['post__in'] = array( (int) $search );
            unset( $args['s'] );
        }
        
        $query = new WP_Query( $args );
        $items = array();
        
        if ( $query->have_posts() ) {
            foreach ( $query->posts as $post ) {
                $product = wc_get_product( $post->ID );
                if ( $product ) {
                    $sku = $product->get_sku() ? ' - SKU: ' . $product->get_sku() : '';
                    $items[] = array(
                        'id'   => $product->get_id(),
                        'text' => '#' . $product->get_id() . ' - ' . $product->get_name() . $sku
                    );
                }
            }
        }
        
        wp_send_json_success( array( 'results' => $items ) );
    }
}

new Mi_ERP_SCM_Ajax();
