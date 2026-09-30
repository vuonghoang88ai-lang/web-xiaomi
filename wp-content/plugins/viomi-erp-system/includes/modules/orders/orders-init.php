<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Mi_ERP_Orders_Init {
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_admin_menu' ), 11 );
        $this->load_dependencies();
    }

    private function load_dependencies() {
        require_once dirname( __FILE__ ) . '/class-orders-core.php';
    }

    public function register_admin_menu() {
        add_submenu_page(
            'mi-erp-system',
            __( 'Đơn hàng ERP', 'mi-erp-system' ),
            __( 'Đơn hàng ERP', 'mi-erp-system' ),
            'manage_options',
            'mi-erp-orders',
            array( $this, 'render_dashboard' ),
            3
        );
    }

    public function render_dashboard() {
        $view_path = dirname( __FILE__ ) . '/views/orders-dashboard.php';
        if ( file_exists( $view_path ) ) {
            require_once $view_path;
        }
    }
}
new Mi_ERP_Orders_Init();
