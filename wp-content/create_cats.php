<?php
define('WP_MEMORY_LIMIT', '512M');
ini_set('memory_limit', '512M');
require_once( dirname( dirname( __FILE__ ) ) . '/wp-load.php' );

// Bảo vệ script: Chỉ cho phép chạy từ CLI hoặc Administrator
if ( php_sapi_name() !== 'cli' && ( ! function_exists( 'current_user_can' ) || ! current_user_can( 'manage_options' ) ) ) {
    wp_die( 'Forbidden: You do not have permission to run this script.', 403 );
}

// Tạo danh mục sản phẩm nếu chưa có
$categories = [
    'Tivi Xiaomi' => 'tivi-xiaomi',
    'Tủ Lạnh Xiaomi' => 'tu-lanh-xiaomi',
    'Thiết Bị Gia Đình' => 'thiet-bi-gia-dinh'
];

foreach ( $categories as $name => $slug ) {
    if ( ! term_exists( $slug, 'product_cat' ) ) {
        wp_insert_term( $name, 'product_cat', array(
            'slug' => $slug
        ) );
        echo "Created category: $name\n";
    } else {
        echo "Category already exists: $name\n";
    }
}

// Cập nhật rewrite rules (permalink)
flush_rewrite_rules();
echo "Flushed rewrite rules.\n";
