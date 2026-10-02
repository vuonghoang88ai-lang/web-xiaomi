<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Mi_ERP_Finance_Init {
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_admin_menu' ), 99 );
        require_once dirname( __FILE__ ) . '/class-finance-ajax.php';
    }

    public function register_admin_menu() {
        add_submenu_page(
            'mi-erp-system',
            'Tài chính & Kế toán',
            'Tài chính',
            'manage_options',
            'mi-erp-finance',
            array( $this, 'render_dashboard' ),
            9
        );
    }

    public function render_dashboard() {
        $view_path = dirname( __FILE__ ) . '/views/finance-dashboard.php';
        if ( file_exists( $view_path ) ) {
            require_once $view_path;
        }
    }
}
new Mi_ERP_Finance_Init();
