<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Mi_ERP_Admin {
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'add_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
        add_action( 'admin_head', array( __CLASS__, 'add_admin_styles' ) );
    }

    public static function add_admin_menu() {
        // Main menu (Dashboard)
        add_menu_page(
            __( 'Mi ERP System', 'mi-erp-system' ),
            __( 'Mi ERP', 'mi-erp-system' ),
            'manage_options',
            'mi-erp-system',
            array( __CLASS__, 'render_dashboard_page' ),
            'dashicons-clipboard',
            58
        );

        // Submenu 0: Dashboard
        add_submenu_page(
            'mi-erp-system',
            __( 'Tổng quan', 'mi-erp-system' ),
            __( 'Tổng quan', 'mi-erp-system' ),
            'manage_options',
            'mi-erp-system',
            array( __CLASS__, 'render_dashboard_page' ),
            1
        );

        // Submenu 1: Nhập kho (đã được thay thế bởi module SCM)




    }

    public static function enqueue_scripts( $hook ) {
        // Chỉ load script trên các trang của Mi ERP
        if ( strpos( $hook, 'mi-erp' ) === false ) {
            return;
        }

        // Load SelectWoo locally from our own plugin folder to guarantee it loads, completely independent of WC
        wp_enqueue_style( 'mi-erp-select2', plugins_url( 'css/select2.min.css', __FILE__ ), array(), '4.0.13' );
        wp_enqueue_script( 'mi-erp-select2', plugins_url( 'js/vendor/select2.min.js', __FILE__ ), array( 'jquery' ), '4.0.13', true );

        // Tailwind CSS
        wp_enqueue_style( 'mi-erp-tailwind', plugins_url( '../../assets/css/tailwind-admin.css', __FILE__ ), array(), filemtime( dirname( dirname( dirname( __FILE__ ) ) ) . '/assets/css/tailwind-admin.css' ) );

        // DataTables
        wp_enqueue_style( 'datatables-css', 'https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css', array(), '1.13.6' );
        wp_enqueue_script( 'datatables-js', 'https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js', array( 'jquery' ), '1.13.6', true );

        wp_enqueue_script( 'mi-erp-admin-js', plugins_url( 'js/mi-erp-admin.js', __FILE__ ), array( 'jquery', 'mi-erp-select2', 'datatables-js' ), time(), true );
        wp_localize_script( 'mi-erp-admin-js', 'mi_erp_admin', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'mi_erp_admin_nonce' )
        ) );
    }

    public static function add_admin_styles() {
        $screen = get_current_screen();
        if ( ! $screen || strpos( $screen->id, 'mi-erp' ) === false ) {
            return;
        }
        echo '<style>
            /* Global fix for DataTables elements in WP Admin */
            .dataTables_wrapper .dataTables_length select {
                width: 60px !important;
                padding-right: 24px !important;
                margin: 0 5px;
            }
            .dataTables_wrapper .dataTables_filter input {
                margin-left: 0.5em;
                padding: 2px 8px;
                border-radius: 4px;
                border: 1px solid #cbd5e1;
                outline: none;
            }
            .dataTables_wrapper .dataTables_filter input:focus {
                border-color: #3b82f6;
                box-shadow: 0 0 0 1px #3b82f6;
            }
        </style>';
    }

    public static function render_dashboard_page() {
        include dirname( __FILE__ ) . '/views/html-dashboard.php';
    }



}
Mi_ERP_Admin::init();
