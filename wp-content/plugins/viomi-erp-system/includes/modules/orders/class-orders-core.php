<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Mi_ERP_Orders_Core {
    public function __construct() {
        add_action( 'wp_ajax_mi_erp_get_orders', array( $this, 'ajax_get_erp_orders' ) );
        add_action( 'wp_ajax_mi_erp_get_order_items', array( $this, 'ajax_get_order_items' ) );
        add_action( 'wp_ajax_mi_erp_search_serials', array( $this, 'ajax_search_serials' ) );
        add_action( 'wp_ajax_mi_erp_assign_serials', array( $this, 'ajax_assign_serials' ) );

        add_action( 'add_meta_boxes', array( $this, 'add_quick_info_meta_box' ), 9, 2 );
        add_action( 'woocommerce_process_shop_order_meta', array( $this, 'save_quick_info_meta_box' ), 10, 2 );
        add_action( 'add_meta_boxes', array( $this, 'add_order_serials_meta_box' ), 10, 2 );
    }

    public function add_quick_info_meta_box( $post_type, $post ) {
        $screen = function_exists( 'wc_get_page_screen_id' ) ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';
        
        if ( 'shop_order' === $post_type || 'woocommerce_page_wc-orders' === $screen || $screen === $post_type ) {
            add_meta_box(
                'mi_erp_quick_info',
                __( 'Thông tin nhanh (Mi ERP)', 'mi-erp-system' ),
                array( $this, 'render_quick_info_meta_box' ),
                $post_type,
                'normal',
                'high'
            );
        }
    }

    public function render_quick_info_meta_box( $post_or_order_object ) {
        $order_id = is_a( $post_or_order_object, 'WC_Order' ) ? $post_or_order_object->get_id() : $post_or_order_object->ID;
        $order = wc_get_order( $order_id );
        if ( ! $order ) return;

        $first_name = $order->get_billing_first_name();
        $last_name = $order->get_billing_last_name();
        $full_name = trim( $first_name . ' ' . $last_name );
        $phone = $order->get_billing_phone();
        $sales_employee = $order->get_meta( '_mi_erp_sales_employee' );
        $employee_note = $order->get_meta( '_mi_erp_employee_note' );

        $users = get_users( array( 'role__in' => array( 'administrator', 'editor', 'shop_manager', 'author' ) ) );

        wp_nonce_field( 'mi_erp_quick_info_nonce_action', 'mi_erp_quick_info_nonce' );
        ?>
        <div style="display:flex; flex-wrap:wrap; gap:15px;">
            <div style="flex:1; min-width:200px;">
                <label style="display:block; font-weight:bold; margin-bottom:5px;"><?php esc_html_e('Tên khách hàng:', 'mi-erp-system'); ?></label>
                <input type="text" name="mi_erp_customer_name" value="<?php echo esc_attr( $full_name ); ?>" style="width:100%;">
            </div>
            <div style="flex:1; min-width:200px;">
                <label style="display:block; font-weight:bold; margin-bottom:5px;"><?php esc_html_e('Số điện thoại:', 'mi-erp-system'); ?></label>
                <input type="text" name="mi_erp_customer_phone" value="<?php echo esc_attr( $phone ); ?>" style="width:100%;">
            </div>
            <div style="flex:1; min-width:200px;">
                <label style="display:block; font-weight:bold; margin-bottom:5px;"><?php esc_html_e('Nhân viên bán hàng:', 'mi-erp-system'); ?></label>
                <select name="mi_erp_sales_employee" style="width:100%;">
                    <option value=""><?php esc_html_e('-- Chọn nhân viên --', 'mi-erp-system'); ?></option>
                    <?php foreach ( $users as $user ) : ?>
                        <option value="<?php echo esc_attr( $user->ID ); ?>" <?php selected( $sales_employee, $user->ID ); ?>><?php echo esc_html( $user->display_name ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div style="margin-top:15px;">
            <label style="display:block; font-weight:bold; margin-bottom:5px;"><?php esc_html_e('Ghi chú của nhân viên:', 'mi-erp-system'); ?></label>
            <textarea name="mi_erp_employee_note" rows="2" style="width:100%;"><?php echo esc_textarea( $employee_note ); ?></textarea>
        </div>
        <?php
    }

    public function save_quick_info_meta_box( $order_id, $post = null ) {
        if ( ! isset( $_POST['mi_erp_quick_info_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['mi_erp_quick_info_nonce'] ), 'mi_erp_quick_info_nonce_action' ) ) {
            return;
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) return;

        if ( isset( $_POST['mi_erp_customer_name'] ) ) {
            $name = sanitize_text_field( wp_unslash( $_POST['mi_erp_customer_name'] ) );
            $order->set_billing_first_name( $name );
            $order->set_billing_last_name( '' ); // Gộp chung vào first name
        }

        if ( isset( $_POST['mi_erp_customer_phone'] ) ) {
            $order->set_billing_phone( sanitize_text_field( wp_unslash( $_POST['mi_erp_customer_phone'] ) ) );
        }

        if ( isset( $_POST['mi_erp_sales_employee'] ) ) {
            $order->update_meta_data( '_mi_erp_sales_employee', absint( wp_unslash( $_POST['mi_erp_sales_employee'] ) ) );
        }

        if ( isset( $_POST['mi_erp_employee_note'] ) ) {
            $order->update_meta_data( '_mi_erp_employee_note', sanitize_textarea_field( wp_unslash( $_POST['mi_erp_employee_note'] ) ) );
        }

        // Avoid infinite loop if save() triggers hooks again, though save() is safe here usually
        $order->save();
    }

    public function add_order_serials_meta_box( $post_type, $post ) {
        // Hỗ trợ cả Custom Post Type (WC < 8.2) và HPOS (WC >= 8.2)
        $screen = function_exists( 'wc_get_page_screen_id' ) ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';
        
        if ( 'shop_order' === $post_type || 'woocommerce_page_wc-orders' === $screen || $screen === $post_type ) {
            add_meta_box(
                'mi_erp_order_serials',
                __( 'Mã Serial (Mi ERP)', 'mi-erp-system' ),
                array( $this, 'render_order_serials_meta_box' ),
                $post_type,
                'side',
                'high'
            );
        }
    }

    public function render_order_serials_meta_box( $post_or_order_object ) {
        $order_id = is_a( $post_or_order_object, 'WC_Order' ) ? $post_or_order_object->get_id() : $post_or_order_object->ID;
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'mi_serials';
        
        $serials = $wpdb->get_results( $wpdb->prepare( "SELECT product_id, serial_number FROM $table_name WHERE order_id = %d", $order_id ) );

        if ( empty( $serials ) ) {
            echo '<p>' . esc_html__( 'Chưa có mã Serial nào được gán cho đơn hàng này.', 'mi-erp-system' ) . '</p>';
        } else {
            echo '<ul style="margin:0; padding-left:1em; list-style-type: disc;">';
            foreach ( $serials as $serial ) {
                $product = wc_get_product( $serial->product_id );
                $product_name = $product ? $product->get_name() : '#' . $serial->product_id;
                echo '<li><strong>' . esc_html( $serial->serial_number ) . '</strong> - ' . esc_html( $product_name ) . '</li>';
            }
            echo '</ul>';
        }
    }


    public function ajax_get_erp_orders() {
        check_ajax_referer( 'mi_erp_admin_nonce', 'nonce' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Permission denied' );
        }

        $draw = isset( $_POST['draw'] ) ? intval( $_POST['draw'] ) : 1;
        $start = isset( $_POST['start'] ) ? intval( $_POST['start'] ) : 0;
        $length = isset( $_POST['length'] ) ? intval( $_POST['length'] ) : 10;
        $search = isset( $_POST['search']['value'] ) ? sanitize_text_field( wp_unslash( $_POST['search']['value'] ) ) : '';

        // Query WooCommerce Orders
        $args = array(
            'limit'  => $length,
            'offset' => $start,
            'orderby' => 'date',
            'order'   => 'DESC',
            'return'  => 'objects',
        );

        if ( ! empty( $search ) ) {
            // Simplified search: WC doesn't easily support wildcard meta search in wc_get_orders without complex queries, 
            // so we rely on WC's default search capability which searches by billing email, name, etc.
            // A more advanced search might require raw SQL if strictly needed, but wc_get_orders 'customer' search works well.
            // Actually, wc_get_orders doesn't have a direct string search in basic args without specific hooks, 
            // but we can query by ID if it's numeric, or we just leave it for now.
            // For simplicity, if search is numeric, search by ID.
            if ( is_numeric( $search ) ) {
                $args['post__in'] = array( (int) $search );
            }
        }

        $orders = wc_get_orders( $args );
        
        // Count total orders correctly for DataTables pagination
        $total_orders = 0;
        $statuses = array_keys( wc_get_order_statuses() );
        foreach ( $statuses as $status ) {
            $total_orders += wc_orders_count( str_replace( 'wc-', '', $status ) );
        }

        $data = array();
        foreach ( $orders as $order ) {
            $order_id = $order->get_id();
            
            // Check ERP status
            $is_assigned = $order->get_meta( '_mi_erp_serials_assigned' );
            $badge = $is_assigned ? '<span style="color:green; font-weight:bold;">✔ Đã xuất Serial</span>' : '<span style="color:orange;">⚠ Chờ xuất</span>';

            $order_link = '<a href="' . esc_url( $order->get_edit_order_url() ) . '">#' . $order_id . '</a>';
            $customer_name = $order->get_formatted_billing_full_name();
            if ( empty( $customer_name ) ) {
                $customer_name = '-';
            }
            
            $sales_emp_id = $order->get_meta( '_mi_erp_sales_employee' );
            $sales_emp_name = '-';
            if ( $sales_emp_id ) {
                $emp_user = get_userdata( $sales_emp_id );
                if ( $emp_user ) {
                    $sales_emp_name = $emp_user->display_name;
                }
            }

            $actions = '<a href="' . esc_url( $order->get_edit_order_url() ) . '" class="button button-small">Xem đơn WC</a>';
            if ( ! $is_assigned ) {
                $actions .= ' <button type="button" class="button button-small button-primary mi-erp-assign-serial-btn" data-order-id="' . esc_attr( $order_id ) . '">Gán Serial</button>';
            }

            $data[] = array(
                $order_link,
                $customer_name,
                $sales_emp_name,
                $order->get_date_created() ? $order->get_date_created()->date( 'Y-m-d H:i' ) : '-',
                $order->get_formatted_order_total(),
                wc_get_order_status_name( $order->get_status() ),
                $badge,
                $actions
            );
        }

        $response = array(
            "draw"            => $draw,
            "recordsTotal"    => intval( $total_orders ),
            "recordsFiltered" => intval( $total_orders ), // Not doing complex filtered count for now
            "data"            => $data
        );

        wp_send_json( $response );
    }

    public function ajax_get_order_items() {
        check_ajax_referer( 'mi_erp_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permission denied' );

        $order_id = isset( $_POST['order_id'] ) ? absint( wp_unslash( $_POST['order_id'] ) ) : 0;
        if ( ! $order_id ) wp_send_json_error( 'Invalid Order ID' );

        $order = wc_get_order( $order_id );
        if ( ! $order ) wp_send_json_error( 'Order not found' );

        $items_data = array();
        foreach ( $order->get_items() as $item_id => $item ) {
            $product_id = $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id();
            $items_data[] = array(
                'item_id'    => $item_id,
                'product_id' => $product_id,
                'name'       => $item->get_name(),
                'qty'        => $item->get_quantity()
            );
        }

        wp_send_json_success( array( 'items' => $items_data ) );
    }

    public function ajax_search_serials() {
        check_ajax_referer( 'mi_erp_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permission denied' );

        $product_id = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;
        $search     = isset( $_POST['q'] ) ? sanitize_text_field( wp_unslash( $_POST['q'] ) ) : '';

        if ( ! $product_id ) wp_send_json_error( 'Invalid Product ID' );

        global $wpdb;
        $table_name = $wpdb->prefix . 'mi_serials';
        
        $query = "SELECT id, serial_number FROM $table_name WHERE product_id = %d AND status = 'in_stock'";
        $args = array( $product_id );

        if ( ! empty( $search ) ) {
            $query .= " AND serial_number LIKE %s";
            $args[] = '%' . $wpdb->esc_like( $search ) . '%';
        }

        $query .= " ORDER BY id ASC LIMIT 20";

        $results = $wpdb->get_results( $wpdb->prepare( $query, $args ) );

        $items = array();
        foreach ( $results as $row ) {
            $items[] = array(
                'id'   => $row->serial_number,
                'text' => $row->serial_number
            );
        }

        wp_send_json_success( array( 'results' => $items ) );
    }

    public function ajax_assign_serials() {
        check_ajax_referer( 'mi_erp_admin_nonce', 'nonce' );
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permission denied' );

        $order_id = isset( $_POST['order_id'] ) ? absint( wp_unslash( $_POST['order_id'] ) ) : 0;
        $assignments = isset( $_POST['assignments'] ) ? $_POST['assignments'] : array();

        if ( ! $order_id || empty( $assignments ) ) {
            wp_send_json_error( 'Dữ liệu không hợp lệ.' );
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) wp_send_json_error( 'Không tìm thấy đơn hàng.' );

        global $wpdb;
        $table_name = $wpdb->prefix . 'mi_serials';
        $has_assigned_any = false;

        foreach ( $assignments as $assignment ) {
            $item_id = absint( $assignment['item_id'] );
            $product_id = absint( $assignment['product_id'] );
            $serials = isset( $assignment['serials'] ) && is_array( $assignment['serials'] ) ? array_map( 'sanitize_text_field', $assignment['serials'] ) : array();

            if ( empty( $serials ) ) continue;

            $item = $order->get_item( $item_id );
            if ( ! $item ) continue;

            $assigned_serials = array();

            foreach ( $serials as $serial_number ) {
                // Kiểm tra xem serial này có đang in_stock và đúng product_id không
                $serial_row = $wpdb->get_row( $wpdb->prepare(
                    "SELECT id FROM $table_name WHERE serial_number = %s AND product_id = %d AND status = 'in_stock'",
                    $serial_number, $product_id
                ) );

                if ( $serial_row ) {
                    // Cập nhật trạng thái serial trong database
                    $wpdb->update(
                        $table_name,
                        array(
                            'status'    => 'sold',
                            'order_id'  => $order_id,
                            'sold_date' => current_time( 'mysql' )
                        ),
                        array( 'id' => $serial_row->id ),
                        array( '%s', '%d', '%s' ),
                        array( '%d' )
                    );
                    $assigned_serials[] = $serial_number;
                }
            }

            if ( ! empty( $assigned_serials ) ) {
                $item->add_meta_data( 'Số Serial', implode( ', ', $assigned_serials ), true );
                $item->save_meta_data();
                $has_assigned_any = true;
            }
        }

        if ( $has_assigned_any ) {
            $order->update_meta_data( '_mi_erp_serials_assigned', 'yes' );
            $order->add_order_note( 'Nhân viên đã gán mã Serial thủ công cho đơn hàng.' );
            $order->save_meta_data();
            wp_send_json_success( 'Đã gán Serial thành công!' );
        } else {
            wp_send_json_error( 'Không có mã Serial nào hợp lệ được gán.' );
        }
    }
}



new Mi_ERP_Orders_Core();
