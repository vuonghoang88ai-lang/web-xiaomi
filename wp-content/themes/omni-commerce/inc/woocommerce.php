<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if ( class_exists( 'WooCommerce' ) ) {
// Remove Add to Cart button on product archives (loop)
remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_add_to_cart', 10);

// Sửa lỗi giao diện Danh mục sản phẩm (archive-product)
// Gỡ bỏ các thẻ div wrapper mặc định của WooCommerce gây vỡ cấu trúc DOM
remove_action( 'woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10 );
remove_action( 'woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10 );
// Gỡ bỏ Astra Theme WooCommerce wrappers (nếu có)
add_action( 'wp', function() {
    if ( class_exists( 'Astra_Woocommerce' ) ) {
        $astra_woo = Astra_Woocommerce::get_instance();
        remove_action( 'woocommerce_before_main_content', array( $astra_woo, 'before_main_content_start' ) );
        remove_action( 'woocommerce_after_main_content', array( $astra_woo, 'before_main_content_end' ) );
    }
}, 20 );

// Gỡ bỏ Breadcrumb trùng lặp
remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
// Gỡ bỏ đoạn text "Hiển thị x-y của z kết quả" gây xô lệch layout bộ lọc
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );

// Tự động làm sạch và fix responsive ảnh trong nội dung bài viết
add_filter( 'the_content', 'mi_clean_content_styles_and_images', 20 );
function mi_clean_content_styles_and_images( $content ) {
    if ( is_product() ) {
        // 1. Loại bỏ các thuộc tính style nội tuyến
        $content = preg_replace('/(<[^>]+) style=".*?"/i', '$1', $content);
        
        // 2. Ép style responsive cho mọi thẻ img
        $content = preg_replace('/<img /i', '<img style="max-width:100%;height:auto;border-radius:12px;" ', $content);
        
        // 3. Chuyển h1 thành h2 để tối ưu SEO
        $content = preg_replace('/<h1(.*?)>/i', '<h2$1 class="text-2xl font-bold my-4">', $content);
        $content = str_replace('</h1>', '</h2>', $content);
    }
    return $content;
}

/**
 * Tùy chỉnh danh sách sắp xếp sản phẩm chuẩn tiếng Việt theo HTML category template
 */
add_filter( 'woocommerce_catalog_orderby', 'mi_custom_catalog_orderby' );
function mi_custom_catalog_orderby( $sortby ) {
    return array(
        'date'       => __( 'Sản phẩm mới', 'omni-commerce' ),
        'price'      => __( 'Giá thấp đến cao', 'omni-commerce' ),
        'price-desc' => __( 'Giá cao đến thấp', 'omni-commerce' ),
        'popularity' => __( 'Xem nhiều nhất', 'omni-commerce' ),
    );
}

add_filter( 'woocommerce_default_catalog_orderby', 'mi_default_catalog_orderby' );
function mi_default_catalog_orderby( $sortby ) {
    return 'date';
}

/**
 * Xây dựng meta_query lọc theo khoảng giá từ $_GET.
 * Dùng chung cho cả main query (pre_get_posts) và custom WP_Query (archive-product.php).
 *
 * @return array Meta query array (rỗng nếu không có filter giá)
 */
function mi_build_price_meta_query() {
    $meta_query = array();
    $cur_min = isset( $_GET['min_price'] ) ? (float) sanitize_text_field( $_GET['min_price'] ) : 0;
    $cur_max = isset( $_GET['max_price'] ) ? (float) sanitize_text_field( $_GET['max_price'] ) : 0;

    if ( isset( $_GET['min_price'] ) && isset( $_GET['max_price'] ) ) {
        $meta_query[] = array(
            'key'     => '_price',
            'value'   => array( $cur_min, $cur_max ),
            'type'    => 'NUMERIC',
            'compare' => 'BETWEEN',
        );
    } elseif ( isset( $_GET['min_price'] ) ) {
        $meta_query[] = array(
            'key'     => '_price',
            'value'   => $cur_min,
            'type'    => 'NUMERIC',
            'compare' => '>=',
        );
    } elseif ( isset( $_GET['max_price'] ) ) {
        $meta_query[] = array(
            'key'     => '_price',
            'value'   => $cur_max,
            'type'    => 'NUMERIC',
            'compare' => '<=',
        );
    }
    return $meta_query;
}

/**
 * Xây dựng tax_query + search fallback cho bộ lọc kích thước/dung tích từ $_GET.
 * Dùng chung cho cả main query (pre_get_posts) và custom WP_Query (archive-product.php).
 *
 * @return array( 'tax_query' => array|null, 'search' => string|null )
 */
function mi_build_size_filter_args() {
    $result   = array( 'tax_query' => null, 'search' => null );
    $cur_size = isset( $_GET['size'] ) ? sanitize_text_field( $_GET['size'] ) : '';

    if ( empty( $cur_size ) ) {
        return $result;
    }

    $size_slug_candidates = array(
        'tivi-xiaomi-' . $cur_size . '-inch',
        $cur_size . '-inch',
        'tivi-' . $cur_size . '-inch',
        'tu-lanh-' . $cur_size . 'l',
        $cur_size . 'l',
    );
    $size_terms = get_terms( array(
        'taxonomy'   => 'product_cat',
        'hide_empty' => false,
        'slug'       => $size_slug_candidates,
    ) );

    if ( ! empty( $size_terms ) && ! is_wp_error( $size_terms ) ) {
        $result['tax_query'] = array(
            'taxonomy' => 'product_cat',
            'field'    => 'term_id',
            'terms'    => wp_list_pluck( $size_terms, 'term_id' ),
        );
    } else {
        // Fallback: tìm theo keyword trong tiêu đề
        $result['search'] = $cur_size;
    }
    return $result;
}

/**
 * Đồng bộ query chính của WordPress trên trang danh mục sản phẩm (archive / taxonomy)
 * Tránh lỗi WordPress tự kích hoạt 404 khi paged lớn hơn max_num_pages của query mặc định.
 * Logic filter được lấy từ hàm dùng chung mi_build_price_meta_query() và mi_build_size_filter_args().
 */
add_action( 'pre_get_posts', 'mi_sync_product_archive_main_query' );
function mi_sync_product_archive_main_query( $query ) {
    if ( ! is_admin() && $query->is_main_query() && ( $query->is_post_type_archive( 'product' ) || $query->is_tax( 'product_cat' ) ) ) {
        if ( isset( $_GET['per_page'] ) ) {
            $query->set( 'posts_per_page', max( 1, min( 48, absint( $_GET['per_page'] ) ) ) );
        }

        // Price filter
        $price_mq = mi_build_price_meta_query();
        if ( ! empty( $price_mq ) ) {
            $existing_mq = $query->get( 'meta_query' ) ?: array();
            $query->set( 'meta_query', array_merge( $existing_mq, $price_mq ) );
        }

        // Size filter
        $size_args = mi_build_size_filter_args();
        if ( $size_args['tax_query'] ) {
            $existing_tq = $query->get( 'tax_query' ) ?: array();
            $existing_tq[] = $size_args['tax_query'];
            $query->set( 'tax_query', $existing_tq );
        } elseif ( $size_args['search'] ) {
            $query->set( 's', $size_args['search'] );
        }
    }
}

/**
 * Tích hợp Chế độ bảo hành vào Giỏ hàng WooCommerce
 */
// 1. Thêm hidden input vào form MUA NGAY
add_action( 'woocommerce_before_add_to_cart_button', 'mi_add_warranty_hidden_input' );
function mi_add_warranty_hidden_input() {
    echo '<input type="hidden" name="mi_warranty_package" id="mi_warranty_hidden" value="default" />';
}

// 2. Lưu data bảo hành vào giỏ hàng
add_filter( 'woocommerce_add_cart_item_data', 'mi_add_warranty_cart_item_data', 10, 2 );
function mi_add_warranty_cart_item_data( $cart_item_data, $product_id ) {
    if ( isset( $_POST['mi_warranty_package'] ) ) {
        $package = sanitize_text_field( $_POST['mi_warranty_package'] );
        $cart_item_data['mi_warranty_package'] = $package;
        
        // Cộng thêm phí bảo hành vàng (+500k)
        if ( $package === 'gold' ) {
            $cart_item_data['mi_warranty_fee'] = 500000;
        }
    }
    return $cart_item_data;
}

// 3. Hiển thị thông tin bảo hành trong giỏ hàng
add_filter( 'woocommerce_get_item_data', 'mi_display_warranty_in_cart', 10, 2 );
function mi_display_warranty_in_cart( $item_data, $cart_item ) {
    if ( isset( $cart_item['mi_warranty_package'] ) ) {
        $name = $cart_item['mi_warranty_package'] === 'gold' ? 'Gói bảo hành vàng' : 'Bảo hành mặc định';
        $item_data[] = array(
            'key'     => 'Chế độ bảo hành',
            'value'   => $name,
            'display' => '',
        );
    }
    return $item_data;
}

// 4. Cộng tiền bảo hành vào giá sản phẩm trong giỏ hàng
add_action( 'woocommerce_before_calculate_totals', 'mi_add_warranty_fee_to_price', 10, 1 );
function mi_add_warranty_fee_to_price( $cart_obj ) {
    if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
    
    // Ngăn vòng lặp vô hạn nếu có plugin khác gọi calculate_totals
    if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 ) return;

    foreach ( $cart_obj->get_cart() as $key => $value ) {
        if ( isset( $value['mi_warranty_fee'] ) ) {
            $price = $value['data']->get_regular_price();
            if ( $value['data']->get_sale_price() ) {
                $price = $value['data']->get_sale_price();
            }
            $value['data']->set_price( $price + $value['mi_warranty_fee'] );
        }
    }
}

// 5. Lưu thông tin bảo hành vào Order (Đơn hàng)
add_action( 'woocommerce_checkout_create_order_line_item', 'mi_add_warranty_to_order_items', 10, 4 );
function mi_add_warranty_to_order_items( $item, $cart_item_key, $values, $order ) {
    if ( isset( $values['mi_warranty_package'] ) ) {
        $name = $values['mi_warranty_package'] === 'gold' ? 'Gói bảo hành vàng' : 'Bảo hành mặc định';
        $item->add_meta_data( 'Chế độ bảo hành', $name, true );
    }
}



/**
 * ==========================================
 * TỐI ƯU HÓA & VIỆT HÓA TRANG CHECKOUT (WOOCOMMERCE)
 * ==========================================
 */
add_filter( 'woocommerce_checkout_fields' , 'mi_custom_override_checkout_fields', 9999 );
function mi_custom_override_checkout_fields( $fields ) {
    // Xóa các trường không cần thiết cho quy trình giao hàng tại VN
    unset($fields['billing']['billing_last_name']);
    unset($fields['billing']['billing_company']);
    unset($fields['billing']['billing_address_2']);
    unset($fields['billing']['billing_city']);
    unset($fields['billing']['billing_postcode']);
    unset($fields['billing']['billing_country']);
    unset($fields['billing']['billing_state']);
    
    // Vô hiệu hóa Shipping fields (thường dùng chung với Billing ở VN để tối giản)
    unset($fields['shipping']);
    
    // Việt hóa & tinh chỉnh Billing Fields
    if (isset($fields['billing']['billing_first_name'])) {
        $fields['billing']['billing_first_name']['label'] = 'Họ và tên người nhận';
        $fields['billing']['billing_first_name']['placeholder'] = 'Nhập đầy đủ họ và tên';
        $fields['billing']['billing_first_name']['class'] = array('form-row-wide');
        $fields['billing']['billing_first_name']['priority'] = 10;
    }
    
    if (isset($fields['billing']['billing_phone'])) {
        $fields['billing']['billing_phone']['label'] = 'Số điện thoại';
        $fields['billing']['billing_phone']['placeholder'] = 'Ví dụ: 0912345678';
        $fields['billing']['billing_phone']['class'] = array('form-row-first');
        $fields['billing']['billing_phone']['priority'] = 20;
    }
    
    if (isset($fields['billing']['billing_email'])) {
        $fields['billing']['billing_email']['label'] = 'Địa chỉ Email (Tùy chọn)';
        $fields['billing']['billing_email']['placeholder'] = 'Để nhận thông tin bảo hành điện tử';
        $fields['billing']['billing_email']['class'] = array('form-row-last');
        $fields['billing']['billing_email']['required'] = false;
        $fields['billing']['billing_email']['priority'] = 30;
    }
    
    if (isset($fields['billing']['billing_address_1'])) {
        $fields['billing']['billing_address_1']['label'] = 'Địa chỉ nhận hàng chi tiết';
        $fields['billing']['billing_address_1']['placeholder'] = 'Số nhà, ngõ/ngách, đường, phường/xã, quận/huyện, tỉnh/thành phố';
        $fields['billing']['billing_address_1']['class'] = array('form-row-wide');
        $fields['billing']['billing_address_1']['priority'] = 40;
    }
    
    // Việt hóa Order Comments
    if (isset($fields['order']['order_comments'])) {
        $fields['order']['order_comments']['label'] = 'Ghi chú đơn hàng (Tùy chọn)';
        $fields['order']['order_comments']['placeholder'] = 'Ví dụ: Giao hàng trong giờ hành chính, Vui lòng gọi trước khi giao...';
    }

    return $fields;
}

// Bỏ validation bắt buộc cho Zip/Postcode và State
add_filter( 'woocommerce_default_address_fields' , 'mi_override_default_address_fields', 9999 );
function mi_override_default_address_fields( $address_fields ) {
    if (isset($address_fields['postcode'])) {
        $address_fields['postcode']['required'] = false;
    }
    if (isset($address_fields['state'])) {
        $address_fields['state']['required'] = false;
    }
    return $address_fields;
}

// Đổi text nút "Place order"
add_filter( 'woocommerce_order_button_text', 'mi_custom_order_button_text' );
function mi_custom_order_button_text() {
    return 'Xác nhận đặt hàng';
}

/**
 * ==========================================
 * VIỆT HÓA TOÀN BỘ TEXT (GETTEXT)
 * ==========================================
 */
add_filter( 'gettext', 'mi_translate_woocommerce_strings', 999, 3 );
function mi_translate_woocommerce_strings( $translated, $text, $domain ) {
    if ( ! is_admin() ) {
        switch ( $text ) {
            case 'Billing details':
                $translated = 'Thông tin giao hàng';
                break;
            case 'Your order':
                $translated = 'Đơn hàng của bạn';
                break;
            case 'Product':
                $translated = 'Sản phẩm';
                break;
            case 'Subtotal':
                $translated = 'Tạm tính';
                break;
            case 'Total':
                $translated = 'Tổng cộng';
                break;
            case 'Have a coupon?':
                $translated = 'Bạn có mã giảm giá?';
                break;
            case 'Click here to enter your code':
                $translated = 'Bấm vào đây để nhập mã';
                break;
            case 'If you have a coupon code, please apply it below.':
                $translated = 'Nếu bạn có mã giảm giá, vui lòng nhập vào bên dưới.';
                break;
            case 'Apply coupon':
                $translated = 'Áp dụng';
                break;
            case 'Coupon code':
                $translated = 'Mã giảm giá';
                break;
            case 'Sorry, it seems that there are no available payment methods for your state. Please contact us if you require assistance or wish to make alternate arrangements.':
            case 'Sorry, it seems that there are no available payment methods for your location. Please contact us if you require assistance or wish to make alternate arrangements.':
            case 'Please fill in your details above to see available payment methods.':
            case 'Sorry, it seems that there are no available payment methods. Please contact us if you require assistance or wish to make alternate arrangements.':
                $translated = 'Phương thức thanh toán sẽ được nhân viên tư vấn xác nhận qua điện thoại sau khi đặt hàng.';
                break;
            case 'Your personal data will be used to process your order, support your experience throughout this website, and for other purposes described in our %s.':
                $translated = 'Thông tin của bạn sẽ được bảo mật và dùng để xử lý đơn hàng theo %s.';
                break;
            case 'privacy policy':
                $translated = 'chính sách bảo mật';
                break;
            case 'Shipping':
            case 'Shipment':
                $translated = 'Giao hàng';
                break;
            case 'Free shipping':
                $translated = 'Miễn phí giao hàng';
                break;
            case 'Checkout':
            case 'CHECKOUT':
                $translated = 'Thanh toán';
                break;
            case '(optional)':
                $translated = ''; // Ẩn chữ optional tiếng Anh mặc định
                break;
        }
    }
    return $translated;
}

/**
 * ==========================================
 * LOẠI BỎ CÁC YẾU TỐ THỪA TRÊN CHECKOUT
 * ==========================================
 */
// Tắt checkbox "Ship to a different address?"
add_filter( 'woocommerce_cart_needs_shipping_address', '__return_false' );

// Ẩn checkbox đăng ký nhận email quảng cáo (nếu có từ plugin bên thứ 3)
add_action( 'wp_head', 'mi_hide_checkout_junk_css' );
function mi_hide_checkout_junk_css() {
    if ( is_checkout() ) {
        echo '<style>
            /* Ẩn dòng chữ (optional) mặc định của Woo */
            .optional { display: none !important; }
            /* Ẩn khối đăng ký nhận bản tin thường chèn bởi plugin */
            .woocommerce-checkout .mailchimp-newsletter,
            .woocommerce-checkout input[name="mailchimp_woocommerce_newsletter"] + span,
            .woocommerce-checkout label[for="mailchimp_woocommerce_newsletter"] {
                display: none !important;
            }
            /* Đảm bảo form gọn gàng hơn */
            .woocommerce-checkout h3 { font-size: 1.5rem !important; font-weight: 700 !important; color: #006779 !important; margin-bottom: 1.5rem !important; }
            .woocommerce-checkout-review-order { background: #fbf9f8; padding: 1.5rem; border-radius: 12px; border: 1px solid #e5e7eb; }
        </style>';
    }
}

// AJAX Xử lý Đăng ký mua trả góp
add_action( 'wp_ajax_nopriv_mi_submit_installment', 'mi_submit_installment_handler' );
add_action( 'wp_ajax_mi_submit_installment', 'mi_submit_installment_handler' );
function mi_submit_installment_handler() {
    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
    $phone = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
    
    if ( empty($product_id) || empty($name) || empty($phone) ) {
        wp_send_json_error( 'Vui lòng điền đủ Họ tên và Số điện thoại!' );
    }

    $gender = isset($_POST['gender']) ? sanitize_text_field($_POST['gender']) : '';
    $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
    $city = isset($_POST['city']) ? sanitize_text_field($_POST['city']) : '';
    $doc = isset($_POST['doc']) ? sanitize_text_field($_POST['doc']) : '';
    $note = isset($_POST['note']) ? sanitize_textarea_field($_POST['note']) : '';
    
    try {
        $order = wc_create_order();
        $order->add_product( wc_get_product( $product_id ), 1 );
        $order->set_address( array(
            'first_name' => $gender . ' ' . $name,
            'email'      => $email,
            'phone'      => $phone,
            'city'       => $city,
        ), 'billing' );
        
        $order->calculate_totals();
        
        // Note
        $order_note = "ĐƠN YÊU CẦU TRẢ GÓP\n";
        $order_note .= "- Giấy tờ: " . $doc . "\n";
        $order_note .= "- Tỉnh/Thành: " . $city . "\n";
        if ( !empty($note) ) {
            $order_note .= "- Ghi chú khách hàng: " . $note;
        }
        $order->add_order_note( $order_note );
        $order->set_customer_note( "Yêu cầu mua trả góp" );
        
        $order->update_status( 'on-hold', 'Khách hàng gửi yêu cầu mua trả góp từ trang sản phẩm.' );
        
        wp_send_json_success( 'Đã tạo đơn yêu cầu trả góp thành công.' );
    } catch (Exception $e) {
        wp_send_json_error( 'Không thể tạo đơn hàng, vui lòng gọi Hotline.' );
    }
}

/**
 * ==========================================
 * XÓA "PRODUCT-CATEGORY" KHỎI ĐƯỜNG DẪN DANH MỤC WOOCOMMERCE
 * ==========================================
 */
add_filter('request', function( $vars ) {
    global $wpdb;
    if( ! empty( $vars['pagename'] ) || ! empty( $vars['category_name'] ) || ! empty( $vars['name'] ) || ! empty( $vars['attachment'] ) ) {
        $slug = ! empty( $vars['pagename'] ) ? $vars['pagename'] : ( ! empty( $vars['name'] ) ? $vars['name'] : ( !empty( $vars['category_name'] ) ? $vars['category_name'] : $vars['attachment'] ) );
        $exists = $wpdb->get_var( $wpdb->prepare( "SELECT t.term_id FROM $wpdb->terms t LEFT JOIN $wpdb->term_taxonomy tt ON tt.term_id = t.term_id WHERE tt.taxonomy = 'product_cat' AND t.slug = %s" ,array( $slug )));
        if( $exists ){
            $old_vars = $vars;
            $vars = array('product_cat' => $slug );
            if ( !empty( $old_vars['paged'] ) || !empty( $old_vars['page'] ) )
                $vars['paged'] = ! empty( $old_vars['paged'] ) ? $old_vars['paged'] : $old_vars['page'];
            if ( !empty( $old_vars['orderby'] ) )
                $vars['orderby'] = $old_vars['orderby'];
            if ( !empty( $old_vars['order'] ) )
                $vars['order'] = $old_vars['order'];
        }
    }
    return $vars;
});

add_filter('term_link', function( $url, $term, $taxonomy ) {
    if ( $taxonomy == 'product_cat' ) {
        $category_base = get_option('woocommerce_permalinks');
        $base = isset($category_base['category_base']) ? $category_base['category_base'] : 'product-category';
        if (empty($base)) $base = 'product-category';
        return str_replace('/' . $base . '/', '/', $url);
    }
    return $url;
}, 10, 3);

}
