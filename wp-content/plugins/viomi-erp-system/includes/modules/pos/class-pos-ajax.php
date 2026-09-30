<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Mi_ERP_POS_Ajax {
    public function __construct() {
        add_action( 'wp_ajax_mi_erp_search_product', array( $this, 'ajax_search_product' ) );
        add_action( 'wp_ajax_mi_erp_create_pos_order', array( $this, 'ajax_create_pos_order' ) );
    }

    public function ajax_search_product() {
        check_ajax_referer( 'mi_erp_admin_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permission denied' );
        }

        $term = isset( $_GET['term'] ) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : '';
        
        if ( empty( $term ) ) {
            wp_send_json_success( array() );
        }

        $args = array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => 20,
            's'              => $term,
        );

        $query = new WP_Query( $args );
        $results = array();

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $product = wc_get_product( get_the_ID() );
                if ( $product ) {
                    $results[] = array(
                        'id'    => $product->get_id(),
                        'text'  => $product->get_name() . ' (#' . $product->get_id() . ')',
                        'price' => $product->get_price(),
                        'formatted_price' => strip_tags( wc_price( $product->get_price() ) )
                    );
                }
            }
        }
        wp_reset_postdata();

        wp_send_json_success( $results );
    }



    public function ajax_create_pos_order() {
        check_ajax_referer( 'mi_erp_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permission denied' );
        }

        $customer_name  = isset( $_POST['customer_name'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_name'] ) ) : '';
        $customer_phone = isset( $_POST['customer_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_phone'] ) ) : '';
        $customer_addr  = isset( $_POST['customer_address'] ) ? sanitize_text_field( wp_unslash( $_POST['customer_address'] ) ) : '';
        $sales_emp      = isset( $_POST['sales_employee'] ) ? absint( wp_unslash( $_POST['sales_employee'] ) ) : 0;
        $order_note     = isset( $_POST['order_note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['order_note'] ) ) : '';
        $cart_items     = isset( $_POST['cart'] ) && is_array( $_POST['cart'] ) ? $_POST['cart'] : array();

        if ( empty( $customer_name ) || empty( $customer_phone ) ) {
            wp_send_json_error( 'Vui lòng nhập Tên và Số điện thoại khách hàng.' );
        }

        if ( empty( $cart_items ) ) {
            wp_send_json_error( 'Giỏ hàng đang trống.' );
        }

        try {
            $order = wc_create_order();

            foreach ( $cart_items as $item ) {
                $product_id = absint( $item['id'] );
                $qty        = absint( $item['qty'] );
                
                if ( $qty > 0 ) {
                    $order->add_product( wc_get_product( $product_id ), $qty );
                }
            }

            // Set Billing Address
            $address = array(
                'first_name' => $customer_name,
                'phone'      => $customer_phone,
                'address_1'  => $customer_addr
            );
            $order->set_address( $address, 'billing' );
            $order->set_address( $address, 'shipping' );

            // Meta Data
            if ( $sales_emp ) {
                $order->update_meta_data( '_mi_erp_sales_employee', $sales_emp );
            }
            if ( $order_note ) {
                $order->set_customer_note( $order_note );
            }

            $order->calculate_totals();

            // Thêm phí vận chuyển nếu có
            $shipping_fee = isset( $_POST['shipping_fee'] ) ? floatval( $_POST['shipping_fee'] ) : 0;
            $delivery_distance = isset( $_POST['delivery_distance'] ) ? floatval( $_POST['delivery_distance'] ) : 0;
            
            if ( $delivery_distance > 0 ) {
                $order->update_meta_data( '_mi_erp_delivery_distance', $delivery_distance );
            }
            
            if ( $shipping_fee > 0 ) {
                $item_fee = new WC_Order_Item_Fee();
                $item_fee->set_name( sprintf( 'Phí vận chuyển (%.1f km)', $delivery_distance ) );
                $item_fee->set_amount( $shipping_fee );
                $item_fee->set_total( $shipping_fee );
                $order->add_item( $item_fee );
                
                // Cần tính lại tổng sau khi thêm fee
                $order->calculate_totals();
            }

            // Cập nhật trạng thái thành Processing (Đang xử lý) để kích hoạt Hook bốc kho (Task 3)
            $order->update_status( 'processing', 'Đơn hàng tạo từ hệ thống POS ERP.' );

            wp_send_json_success( array(
                'message'  => 'Đã tạo đơn hàng thành công!',
                'order_id' => $order->get_id(),
                'url'      => $order->get_edit_order_url()
            ) );

        } catch ( Exception $e ) {
            wp_send_json_error( 'Lỗi tạo đơn: ' . $e->getMessage() );
        }
    }


}
new Mi_ERP_POS_Ajax();
