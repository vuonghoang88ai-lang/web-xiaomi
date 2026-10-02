<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Mi_ERP_Order_Automation {
    public static function init() {
        // TẮT TÍNH NĂNG TỰ ĐỘNG GÁN SERIAL ĐỂ CHUYỂN SANG GÁN THỦ CÔNG THEO YÊU CẦU
        // add_action( 'woocommerce_order_status_processing', array( __CLASS__, 'process_order_serials' ), 10, 2 );
        // add_action( 'woocommerce_order_status_completed', array( __CLASS__, 'process_order_serials' ), 10, 2 );
    }

    public static function process_order_serials( $order_id, $order ) {
        // Hỗ trợ HPOS, kiểm tra nếu $order không phải object
        if ( ! is_a( $order, 'WC_Order' ) ) {
            $order = wc_get_order( $order_id );
        }

        if ( ! $order ) {
            return;
        }

        // Kiểm tra nếu đơn hàng đã được gán serial để tránh xử lý trùng lặp
        if ( $order->get_meta( '_mi_erp_serials_assigned' ) ) {
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'mi_serials';
        $items = $order->get_items();
        $has_assigned_any = false;

        foreach ( $items as $item_id => $item ) {
            $product_id = $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id();
            $quantity = $item->get_quantity();

            // Auto-pick serials từ kho
            $query = $wpdb->prepare( "
                SELECT id, serial_number 
                FROM {$table_name} 
                WHERE product_id = %d AND status = 'in_stock' 
                ORDER BY id ASC 
                LIMIT %d
            ", $product_id, $quantity );

            $serials = $wpdb->get_results( $query );

            if ( $serials && count( $serials ) > 0 ) {
                $assigned_serials = array();
                
                foreach ( $serials as $serial ) {
                    $assigned_serials[] = $serial->serial_number;

                    // Cập nhật trạng thái serial trong database
                    $wpdb->update(
                        $table_name,
                        array(
                            'status'    => 'sold',
                            'order_id'  => $order_id,
                            'sold_date' => current_time( 'mysql' )
                        ),
                        array( 'id' => $serial->id ),
                        array( '%s', '%d', '%s' ),
                        array( '%d' )
                    );
                }

                // Gán danh sách serial vào Item Meta để hiển thị cho khách hàng & admin
                $item->add_meta_data( 'Số Serial', implode( ', ', $assigned_serials ), true );
                $item->save_meta_data();
                
                $has_assigned_any = true;
            }
        }

        // Đánh dấu đơn hàng đã được xử lý
        $order->update_meta_data( '_mi_erp_serials_assigned', 'yes' );
        $order->save_meta_data();
        
        if ( $has_assigned_any ) {
            $order->add_order_note( 'Hệ thống Mi ERP đã tự động gán Số Serial cho các sản phẩm trong đơn hàng.' );
        }
    }
}

Mi_ERP_Order_Automation::init();
