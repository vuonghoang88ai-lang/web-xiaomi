<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Mi_ERP_Inventory {
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'add_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
        // AJAX hooks will go here
        // add_action( 'wp_ajax_mi_erp_inventory_action', array( __CLASS__, 'ajax_inventory_action' ) );
    }

    public static function add_admin_menu() {
        add_menu_page(
            'Quản lý Kho',
            'Quản lý Kho',
            'manage_options',
            'mi-erp-inventory',
            array( __CLASS__, 'render_dashboard' ),
            'dashicons-store',
            30
        );
    }

    public static function enqueue_scripts( $hook ) {
        // Only load on our inventory page
        if ( 'toplevel_page_mi-erp-inventory' !== $hook ) {
            return;
        }

        // Avoid BUG-01 by using __FILE__ properly for plugins_url inside a subfolder
        wp_register_script( 'mi-erp-inventory-js', plugins_url( 'js/mi-erp-inventory.js', __FILE__ ), array( 'jquery' ), time(), true );
        wp_localize_script( 'mi-erp-inventory-js', 'mi_erp_inventory_data', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'mi_erp_admin_nonce' )
        ) );
        wp_enqueue_script( 'mi-erp-inventory-js' );
        
        // CSS if needed (Tailwind classes will handle most of the UI)
        // wp_enqueue_style( 'mi-erp-inventory-css', plugins_url( 'css/mi-erp-inventory.css', __FILE__ ), array(), time() );
    }

    public static function render_dashboard() {
        // Include the view file
        $view_file = dirname( __FILE__ ) . '/views/inventory-dashboard.php';
        if ( file_exists( $view_file ) ) {
            require_once $view_file;
        } else {
            echo '<div class="wrap"><h2>Lỗi: Không tìm thấy giao diện quản lý kho.</h2></div>';
        }
    }
}

Mi_ERP_Inventory::init();
