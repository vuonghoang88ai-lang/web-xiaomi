<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Mi_ERP_HRM_Init {
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_admin_menu' ), 99 );
        require_once dirname( __FILE__ ) . '/class-hrm-ajax.php';
    }

    public function register_admin_menu() {
        add_submenu_page(
            'mi-erp-system',
            'Quản lý Nhân sự',
            'Nhân sự',
            'manage_options',
            'mi-erp-hrm',
            array( $this, 'render_dashboard' ),
            10
        );
    }

    public function render_dashboard() {
        $view_path = dirname( __FILE__ ) . '/views/hrm-dashboard.php';
        if ( file_exists( $view_path ) ) {
            require_once $view_path;
        }
    }
}
new Mi_ERP_HRM_Init();
