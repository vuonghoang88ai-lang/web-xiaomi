<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Mi_ERP_Procurement_Init {
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_admin_menu' ), 99 );
        require_once dirname( __FILE__ ) . '/class-procurement-ajax.php';
    }

    public function register_admin_menu() {
        add_submenu_page(
            'mi-erp-system',
            'Mua hàng & Nhà cung cấp',
            'Mua hàng',
            'manage_options',
            'mi-erp-procurement',
            array( $this, 'render_dashboard' ),
            7
        );
    }

    public function render_dashboard() {
        $view_path = dirname( __FILE__ ) . '/views/procurement-dashboard.php';
        if ( file_exists( $view_path ) ) {
            require_once $view_path;
        }
    }
}
new Mi_ERP_Procurement_Init();
