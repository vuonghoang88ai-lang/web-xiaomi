# Backend Rules — WPCS, ACF & WooCommerce

## 1. WordPress Coding Standards (WPCS)

| Loại | Hàm bắt buộc |
|------|-------------|
| Echo ra HTML | `esc_html()` |
| Echo trong attribute | `esc_attr()` |
| Echo URL | `esc_url()` |
| Input người dùng | `sanitize_text_field()` |
| JSON trong `data-*` | `esc_attr( wp_json_encode($data) )` |
| `the_title()` | **Không tự escape** — dùng `echo esc_html( get_the_title() )` |

```php
// ĐÚNG
echo esc_html( get_the_title() );
echo esc_attr( $product->is_in_stock() ? 'text-primary' : 'text-error' );
$fill = (int) ( $k <= $rating ) ? 1 : 0; // Explicit cast cho CSS font-variation-settings

// SAI
the_title();  // Không escape
echo $fill;   // Có thể là string
echo $display_price > 0 ? wc_price($p) : 'Liên hệ'; // Nhánh fallback cũng phải escape
```

### Query isolation cho page templates
```php
// ĐÚNG — query riêng cho từng section
$featured_query = new WP_Query( [ 'post_type' => 'post', 'posts_per_page' => 5, 'paged' => $paged ] );
if ( $featured_query->have_posts() ) {
    while ( $featured_query->have_posts() ) : $featured_query->the_post();
        // render featured
    endwhile;
    wp_reset_postdata();
}

$list_query = new WP_Query( [ 'post_type' => 'post', 'posts_per_page' => 10, 'paged' => $paged ] );
if ( $list_query->have_posts() ) {
    while ( $list_query->have_posts() ) : $list_query->the_post();
        // render list
    endwhile;
    wp_reset_postdata();
}

// SAI — dùng chung `have_posts()` / `the_post()` cho nhiều block
while ( have_posts() ) : the_post();
    // featured
endwhile;
while ( have_posts() ) : the_post();
    // list
endwhile;
```

> Nếu dùng chung query global, phải `rewind_posts()` / `wp_reset_postdata()` đúng chỗ. Nhưng với page template nhiều section, cách an toàn nhất là `WP_Query` tách riêng cho từng block.

## 2. ACF Patterns

### Verify field trước khi dùng
Trước khi gọi `get_field('field_name')`, grep trong `functions.php`:
```bash
grep -n "'name' => 'field_name'" functions.php
# Nếu không thấy → field không tồn tại → tìm đúng tên
```

### Cấm chạy ACF repeater 2 lần
```php
// ĐÚNG — PHP Output Buffering, buffer lần 1, echo lại ở vị trí 2
ob_start();
while ( have_rows('technical_specs', $id) ) : the_row();
    echo '<tr><td>' . esc_html(get_sub_field('spec_label')) . '</td>'
       . '<td>' . wp_kses_post(get_sub_field('spec_value')) . '</td></tr>';
endwhile;
$html = ob_get_clean();
echo $html; // Vị trí 1: bảng rút gọn
echo $html; // Vị trí 2: modal

// SAI — Con trỏ ACF không reset, lần 2 không trả về data
while ( have_rows('technical_specs', $id) ) { ... } // Lần 1
while ( have_rows('technical_specs', $id) ) { ... } // Lần 2 — KHÔNG CHẠY ĐƯỢC
```

### Kế thừa ACF an toàn cho Product Row
```php
if ( isset( $acf_rows[$index] ) ) {
    $custom = $acf_rows[$index];
    // Chặn "Tivi" ghi đè vào hàng Tủ Lạnh hoặc Thiết bị gia đình
    if ( $index === 0 || stripos( $custom['cat_title'], 'tivi' ) === false ) {
        $row_args['cat_title'] = $custom['cat_title'];
    }
}
```

## 3. WooCommerce

### Xử lý giá sản phẩm biến thể
```php
// ĐÚNG
if ( $product->is_type('variable') ) {
    $sale_price = $product->get_variation_sale_price('min', true);
    $reg_price  = $product->get_variation_regular_price('max', true);
} else {
    $sale_price = $product->get_sale_price();
    $reg_price  = $product->get_regular_price();
}
// Giá rỗng hoặc = 0 → "Liên hệ"
echo $display_price > 0
    ? wc_price( $display_price )
    : esc_html__( 'Liên hệ', 'omni-commerce' );
```

### Variable Product — Thẻ 2 dòng sync WooCommerce
```php
// Ẩn select mặc định WooCommerce
.variations_form table.variations { display: none !important; }
```
```js
// JS: Click thẻ phiên bản → set select value → trigger WooCommerce recalculate
selectEl.value = attrValue;
selectEl.dispatchEvent( new Event('change', { bubbles: true }) );

// Lắng nghe found_variation để cập nhật giá + ảnh
$('.variations_form').on('found_variation', function(e, variation) {
    // Cập nhật giá lớn, ảnh gallery
});
```

### Rút ngắn tên sản phẩm
```php
// ĐÚNG — str_replace array bao phủ mọi danh mục
$prefixes = [
    'Tivi Xiaomi ', 'Tủ Lạnh Xiaomi ', 'Robot hút bụi lau nhà Xiaomi ',
    'Robot hút bụi Xiaomi ', 'Máy lọc không khí Xiaomi ', 'Máy hút bụi Xiaomi ',
    'Quạt thông minh Xiaomi ', 'Thiết bị gia đình Xiaomi ',
];
$short_name = trim( str_replace( $prefixes, '', get_the_title() ) );

// SAI — preg_match chỉ lấy số, sai với sản phẩm không có số trong tên
preg_match( '/\b(\d{2,3})(L|inch)?\b/i', get_the_title(), $matches );
$short_name = $matches[1]; // Rỗng nếu tên không chứa số
```

### Khai báo biến đúng scope
```php
// ĐÚNG — $prefix chỉ khai báo trong nhánh cần dùng
if ( $is_variable ) { ... }
else {
    $prefix   = implode(' ', array_slice( explode(' ', get_the_title()), 0, 4 ));
    $sim_args = ['s' => $prefix];
}

// SAI — khai báo ở scope ngoài nhưng chỉ dùng trong nhánh else → lãng phí
$prefix = implode(' ', array_slice( explode(' ', get_the_title()), 0, 4 ));
if ( $is_variable ) { /* không dùng $prefix ở đây */ }
```

## 4. ERP Integration (mi-erp-system)
- **Warranty Portal:** shortcode `[mi_warranty_check]` trên trang `/tra-cuu-bao-hanh/`
- **Custom order status:** `wc-cho-xuat-kho` (đồng bộ WooCommerce ↔ ERP)
- **Warranty packages (4 gói cố định):** Mặc định 30 ngày / Vàng 24 tháng / Phần cứng / Điều kiện từ chối
  - **TUYỆT ĐỐI KHÔNG** thêm/bớt gói (ví dụ: không thêm "Bản Quốc Tế")
- **Tương lai:** Variable Product + Product Wizard trong Plugin ERP

## 5. Tiêu chuẩn Dữ liệu & Form

| Tình huống | Chuẩn |
|-----------|-------|
| Chưa có dữ liệu thực (views, rating) | Fallback = `0`, không hardcode số ngẫu nhiên |
| Form chưa có API | `event.preventDefault()` + `console.log()` + comment `/* TODO: API */` — KHÔNG `alert()` |
| Empty state (reviews, related...) | Material Symbols icon + text hướng dẫn — KHÔNG mock tên người/nội dung cứng |
| Anchor link | Kiểm tra `id` tồn tại trong DOM trước khi dùng `href="#id"` |

## 6. Chuỗi nghiệp vụ — Nguồn dữ liệu bắt buộc

| Loại dữ liệu | Nguồn |
|-------------|-------|
| Site name | `get_bloginfo('name')` |
| Hotline, Zalo, Messenger | ACF option (`mi_hotline_number`, `mi_hf_fab_*`) |
| Giá dịch vụ bảo hành | ACF option + `number_format()` |
| Ưu đãi / khuyến mãi | ACF repeater hoặc `$fallback_array` có comment rõ |

Grep vi phạm: `>[^<]{5,}(Xiaomi|HÃNG|VIOMI|Store)` trong `woocommerce/` và `template-parts/`

## 7. Chống Duplicate Hook
```php
// SAI — 2 hàm cùng hook vào 1 filter → behavior không đoán được
add_filter( 'woocommerce_catalog_orderby', 'mi_custom_woocommerce_catalog_orderby' );
add_filter( 'woocommerce_catalog_orderby', 'mi_custom_catalog_orderby' );

// ĐÚNG — chỉ giữ 1 hàm duy nhất, xóa bản cũ
add_filter( 'woocommerce_catalog_orderby', 'mi_custom_catalog_orderby' );
```

## 8. Tối ưu WooCommerce Checkout & Cart (Thị trường VN)

### Việt hóa & Dọn dẹp Checkout Field
```php
add_filter( 'woocommerce_checkout_fields' , 'mi_custom_override_checkout_fields', 9999 );
function mi_custom_override_checkout_fields( $fields ) {
    // Xóa các trường rườm rà chuẩn Tây
    unset($fields['billing']['billing_last_name'], $fields['billing']['billing_company'], $fields['billing']['billing_address_2'], $fields['billing']['billing_city'], $fields['billing']['billing_postcode'], $fields['billing']['billing_country'], $fields['billing']['billing_state'], $fields['shipping']);
    
    // Đổi tên & placeholder cho phù hợp VN
    $fields['billing']['billing_first_name']['label'] = 'Họ và tên người nhận';
    $fields['billing']['billing_phone']['priority'] = 20; // Đưa SĐT lên ngay dưới Tên
    return $fields;
}

// Bỏ bắt buộc Zip/State
add_filter( 'woocommerce_default_address_fields' , 'mi_override_default_address_fields', 9999 );
// Tắt checkbox giao hàng khác (Shipping Address)
add_filter( 'woocommerce_cart_needs_shipping_address', '__return_false' );
```

### Việt hóa lõi WooCommerce không cần Plugin (Loco Translate)
Dùng filter `gettext` để can thiệp trực tiếp vào ngôn ngữ thay vì cài plugin nặng:
```php
add_filter( 'gettext', 'mi_translate_woocommerce_strings', 999, 3 );
function mi_translate_woocommerce_strings( $translated, $text, $domain ) {
    if ( ! is_admin() ) {
        switch ( $text ) {
            case 'Billing details': return 'Thông tin giao hàng';
            case 'Your order': return 'Đơn hàng của bạn';
            case '(optional)': return ''; // Ẩn chữ optional
            // Các text Cart/Checkout khác...
        }
    }
    return $translated;
}
```

### Ẩn các thành phần "Rác" từ Third-party trên Checkout/Cart
Sử dụng CSS nội bộ chèn qua hook `wp_head` nếu cần thiết (ví dụ: ẩn khối đăng ký newsletter Mailchimp, ẩn Calculate Shipping) để không làm nặng file style chung:
```php
add_action( 'wp_head', 'mi_custom_woo_css' );
function mi_custom_woo_css() {
    if ( is_checkout() || is_cart() ) {
        echo '<style> .mailchimp-newsletter, .shipping-calculator-button { display: none !important; } </style>';
    }
}
```

## 9. Xử lý Đơn hàng Trả Góp (Installment)
- **Cơ chế thu thập khách hàng:** Sử dụng Modal Popup và AJAX thuần (vanilla JS) trên trang chi tiết sản phẩm.
- **Tạo đơn hàng:** Dùng `wc_create_order()` để ghi lại trực tiếp vào hệ thống WooCommerce với status `on-hold`.
- **Ghi chú đơn hàng:** Các thông tin phụ (CMND, Hộ khẩu, Thành phố) được lưu thẳng vào Order Note (`add_order_note()`) thay vì tạo trường Custom Meta (ACF), giúp Sale dễ dàng đọc ngay ở giao diện Order mặc định mà không cần chỉnh sửa admin panel.
