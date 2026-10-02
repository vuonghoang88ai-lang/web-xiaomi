<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Mi_ERP_SCM_Init {
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'register_admin_menu' ), 99 );
        $this->load_dependencies();
    }

    private function load_dependencies() {
        require_once dirname( __FILE__ ) . '/class-scm-ajax.php';
    }

    public function register_admin_menu() {
        add_submenu_page(
            'mi-erp-system',
            'Quản lý Kho',
            'Kho hàng',
            'manage_options',
            'mi-erp-scm',
            array( $this, 'render_dashboard' ),
            5
        );

        add_submenu_page(
            'mi-erp-system',
            'Quản lý Tồn kho',
            'Tồn kho (SCM)',
            'manage_options',
            'mi-erp-scm-inventory',
            array( $this, 'render_inventory' ),
            6
        );
    }

    public function render_dashboard() {
        $view_path = dirname( __FILE__ ) . '/views/warehouse-dashboard.php';
        if ( file_exists( $view_path ) ) {
            require_once $view_path;
        } else {
            echo '<div class="wrap"><h2>Đang xây dựng giao diện kho...</h2></div>';
        }
    }

    public function render_inventory() {
        $view_path = dirname( __FILE__ ) . '/views/inventory-dashboard.php';
        if ( file_exists( $view_path ) ) {
            require_once $view_path;
        } else {
            echo '<div class="wrap"><h2>Đang xây dựng giao diện tồn kho...</h2></div>';
        }
    }
}

new Mi_ERP_SCM_Init();
