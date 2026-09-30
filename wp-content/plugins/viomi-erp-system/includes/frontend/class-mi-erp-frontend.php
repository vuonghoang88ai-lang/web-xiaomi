<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Mi_ERP_Frontend {
    public static function init() {
        add_shortcode( 'mi_warranty_check', array( __CLASS__, 'render_warranty_form' ) );
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
        add_action( 'wp_ajax_nopriv_mi_erp_frontend_check_warranty', array( __CLASS__, 'ajax_check_warranty' ) );
        add_action( 'wp_ajax_mi_erp_frontend_check_warranty', array( __CLASS__, 'ajax_check_warranty' ) );
    }

    public static function enqueue_scripts() {
        wp_enqueue_style( 'mi-erp-frontend-style', plugins_url( 'assets/css/style.css', dirname( __FILE__, 2 ) ), array(), time() );
        wp_register_script( 'mi-erp-frontend-js', plugins_url( 'js/mi-erp-frontend.js', __FILE__ ), array( 'jquery' ), time(), true );
        wp_localize_script( 'mi-erp-frontend-js', 'mi_erp_frontend', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'mi_erp_frontend_nonce' )
        ) );
    }

    public static function render_warranty_form() {
        wp_enqueue_script( 'mi-erp-frontend-js' );
        ob_start();
        ?>
        <div class="mi-erp-warranty-portal bg-white rounded-2xl border border-slate-200 shadow-sm p-6 max-w-4xl mx-auto">
            <h3 class="text-2xl font-black text-slate-800 mb-4">Tra cứu thông tin bảo hành</h3>
            
            <!-- Form nhập liệu -->
            <form id="mi-warranty-check-form" class="flex flex-col md:flex-row gap-4 mb-8">
                <div class="flex-grow">
                    <input type="text" 
                           name="serial_number" 
                           id="mi_serial_number" 
                           placeholder="Nhập Mã Serial hoặc Số điện thoại mua hàng..." 
                           required 
                           class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all">
                </div>
                <button type="submit" 
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-xl transition-colors shrink-0">
                    Tra cứu
                </button>
            </form>

            <!-- Container kết quả (Dùng block tĩnh để tránh BUG-04) -->
            <div id="mi-warranty-result" class="hidden bg-slate-50 rounded-xl p-6 border border-slate-200">
                <!-- Dữ liệu JS sẽ được render vào đây -->
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function ajax_check_warranty() {
        check_ajax_referer( 'mi_erp_frontend_nonce', 'nonce' );

        $query_string = isset( $_POST['serial_number'] ) ? sanitize_text_field( wp_unslash( $_POST['serial_number'] ) ) : '';

        if ( empty( $query_string ) ) {
            wp_send_json_error( 'Vui lòng nhập Số điện thoại hoặc Mã Serial.' );
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'mi_serials';
        $serials    = array();

        // 1. Phân loại tra cứu (Số điện thoại vs Serial)
        if ( is_numeric( $query_string ) && strlen( $query_string ) >= 9 && strlen( $query_string ) <= 11 ) {
            
            // Dùng LIKE %s với prepare
            $like_phone = '%' . $wpdb->esc_like( $query_string ) . '%';
            $order_ids = $wpdb->get_col( $wpdb->prepare( "
                SELECT post_id FROM {$wpdb->postmeta} 
                WHERE meta_key = '_billing_phone' AND meta_value LIKE %s
            ", $like_phone ) );

            if ( ! empty( $order_ids ) ) {
                $placeholders = implode( ',', array_fill( 0, count( $order_ids ), '%d' ) );
                $query = "SELECT * FROM {$table_name} WHERE order_id IN ($placeholders) AND status = 'sold'";
                $serials = $wpdb->get_results( $wpdb->prepare( $query, $order_ids ) );
            }

        } else {
            // 2. Tra cứu bằng Serial
            $serial_data = $wpdb->get_row( $wpdb->prepare( "
                SELECT * FROM {$table_name} WHERE serial_number = %s AND status = 'sold'
            ", $query_string ) );
            
            if ( $serial_data ) {
                $serials[] = $serial_data;
            }
        }

        // 3. Kiểm tra kết quả
        if ( empty( $serials ) ) {
            wp_send_json_error( 'Không tìm thấy thông tin bảo hành. Sản phẩm chưa kích hoạt hoặc sai mã.' );
        }

        $result_data = array();

        // 4. Tính hạn bảo hành
        foreach ( $serials as $serial_data ) {
            $product_id = $serial_data->product_id;
            $product = wc_get_product( $product_id );
            
            $warranty_months = 12; 
            
            if ( function_exists('get_field') ) {
                $acf_months = get_field('mi_warranty_info', $product_id);
                if ( $acf_months !== false && $acf_months !== '' ) {
                    $warranty_months = intval( $acf_months );
                }
            } else {
                $meta_months = get_post_meta( $product_id, 'mi_warranty_info', true );
                if ( ! empty( $meta_months ) ) {
                    $warranty_months = intval( $meta_months );
                }
            }

            $sold_timestamp   = strtotime( $serial_data->sold_date );
            $expire_timestamp = strtotime( "+{$warranty_months} months", $sold_timestamp );

            $result_data[] = array(
                'product_name'    => $product ? $product->get_name() : 'Sản phẩm không xác định',
                'serial_number'   => $serial_data->serial_number,
                'warranty_months' => $warranty_months,
                'sold_date'       => date_i18n( get_option( 'date_format' ), $sold_timestamp ),
                'expire_date'     => date_i18n( get_option( 'date_format' ), $expire_timestamp ),
                'is_expired'      => time() > $expire_timestamp,
            );
        }

        // 5. Trả về JSON
        wp_send_json_success( $result_data );
    }
}

Mi_ERP_Frontend::init();
