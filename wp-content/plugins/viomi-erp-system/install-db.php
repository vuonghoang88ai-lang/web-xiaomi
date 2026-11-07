<?php
/**
 * Script khởi tạo DB nội bộ cho ERP System.
 * Bắt buộc kiểm tra quyền Administrator để ngăn chặn truy cập trái phép.
 */
if ( ! defined( 'ABSPATH' ) ) {
    require_once dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . '/wp-load.php';
}

if ( ! current_user_can( 'manage_options' ) && ( ! defined( 'WP_CLI' ) || ! WP_CLI ) ) {
    wp_die( esc_html__( 'Bạn không có quyền truy cập trang này.', 'mi-erp-system' ), 403 );
}

try {
    require_once dirname( __FILE__ ) . '/includes/class-mi-erp-installer.php';
    Mi_ERP_Installer::install();
    echo 'Install OK';
} catch ( Throwable $e ) {
    echo esc_html( $e->getMessage() );
}
