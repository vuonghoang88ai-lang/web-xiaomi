<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Mi_ERP_CRM_Init {
    public function __construct() {
        // Priority 99 để đảm bảo menu Mi ERP đã được load
        add_action( 'admin_menu', array( $this, 'register_admin_menu' ), 99 );
        add_shortcode( 'mi_warranty_check', array( $this, 'render_warranty_shortcode' ) );
        
        $this->load_dependencies();
    }

    private function load_dependencies() {
        require_once dirname( __FILE__ ) . '/class-crm-ajax.php';
    }

    public function register_admin_menu() {
        add_submenu_page(
            'mi-erp-system',
            'Khách hàng (CRM)',
            'Khách hàng',
            'manage_options',
            'mi-erp-crm',
            array( $this, 'render_dashboard' ),
            4
        );
    }

    public function render_dashboard() {
        $view_path = dirname( __FILE__ ) . '/views/customer-dashboard.php';
        if ( file_exists( $view_path ) ) {
            require_once $view_path;
        } else {
            echo '<div class="wrap"><h2>Đang xây dựng giao diện khách hàng...</h2></div>';
        }
    }

    public function render_warranty_shortcode( $atts ) {
        ob_start();
        $view_path = dirname( __FILE__ ) . '/views/warranty-shortcode.php';
        if ( file_exists( $view_path ) ) {
            require $view_path;
        } else {
            echo '<p>Giao diện tra cứu bảo hành đang được cập nhật.</p>';
        }
        return ob_get_clean();
    }
}

new Mi_ERP_CRM_Init();
