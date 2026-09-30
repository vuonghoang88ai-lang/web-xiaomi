<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Mi_ERP_Manufacturing_Init {
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_admin_menu' ), 99 );
        require_once dirname( __FILE__ ) . '/class-manufacturing-ajax.php';
    }

    public function register_admin_menu() {
        add_submenu_page(
            'mi-erp-system',
            'Quản lý Sản xuất',
            'Sản xuất',
            'manage_options',
            'mi-erp-manufacturing',
            array( $this, 'render_dashboard' ),
            8
        );
    }

    public function render_dashboard() {
        $view_path = dirname( __FILE__ ) . '/views/manufacturing-dashboard.php';
        if ( file_exists( $view_path ) ) {
            require_once $view_path;
        }
    }
}
new Mi_ERP_Manufacturing_Init();
