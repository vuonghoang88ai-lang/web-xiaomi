<?php
/**
 * Plugin Name: Mi ERP System
 * Plugin URI: https://michinhhang.com
 * Description: Hệ thống quản trị ERP cho Mi Chính Hãng (Modular & Event-driven architecture).
 * Version: 1.0.0
 * Author: Mi Chính Hãng
 * Text Domain: mi-erp-system
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// 1. Install & Database
require_once dirname( __FILE__ ) . '/includes/class-mi-erp-installer.php';
register_activation_hook( __FILE__, array( 'Mi_ERP_Installer', 'install' ) );

// Hàm hỗ trợ tự động thêm trang vào menu (chạy 1 lần)
add_action( 'init', 'mi_erp_auto_add_menu' );
function mi_erp_auto_add_menu() {
    if ( get_option( 'mi_erp_menu_added' ) ) return;

    $page_title = 'Tra cứu bảo hành';
    $page = get_page_by_title( $page_title );
    if ( $page ) {
        $locations = get_nav_menu_locations();
        $menu_id = isset( $locations['primary'] ) ? $locations['primary'] : 0;
        
        if ( ! $menu_id && isset( $locations['main'] ) ) {
            $menu_id = $locations['main'];
        }

        if ( ! $menu_id ) {
            $menus = wp_get_nav_menus();
            if ( ! empty( $menus ) ) {
                $menu_id = $menus[0]->term_id;
            }
        }

        if ( $menu_id ) {
            wp_update_nav_menu_item( $menu_id, 0, array(
                'menu-item-title'   => $page_title,
                'menu-item-object'  => 'page',
                'menu-item-object-id' => $page->ID,
                'menu-item-type'    => 'post_type',
                'menu-item-status'  => 'publish'
            ));
            update_option( 'mi_erp_menu_added', 1 );
        } else {
            // Nếu không có menu nào, tạo một menu mới
            $menu_id = wp_create_nav_menu( 'Main Menu' );
            if ( ! is_wp_error( $menu_id ) ) {
                wp_update_nav_menu_item( $menu_id, 0, array(
                    'menu-item-title'   => $page_title,
                    'menu-item-object'  => 'page',
                    'menu-item-object-id' => $page->ID,
                    'menu-item-type'    => 'post_type',
                    'menu-item-status'  => 'publish'
                ));
                $locations['primary'] = $menu_id;
                set_theme_mod( 'nav_menu_locations', $locations );
                update_option( 'mi_erp_menu_added', 1 );
            }
        }
    }
}

// 2. Admin Dashboard
if ( is_admin() ) {
    require_once dirname( __FILE__ ) . '/includes/admin/class-mi-erp-admin.php';
}

// 2.5 Modules ERP Core
require_once dirname( __FILE__ ) . '/includes/modules/pos/pos-init.php';
require_once dirname( __FILE__ ) . '/includes/modules/orders/orders-init.php';
require_once dirname( __FILE__ ) . '/includes/modules/scm/scm-init.php';
require_once dirname( __FILE__ ) . '/includes/modules/crm/crm-init.php';
require_once dirname( __FILE__ ) . '/includes/modules/finance/finance-init.php';
require_once dirname( __FILE__ ) . '/includes/modules/procurement/procurement-init.php';
require_once dirname( __FILE__ ) . '/includes/modules/hrm/hrm-init.php';
require_once dirname( __FILE__ ) . '/includes/modules/manufacturing/manufacturing-init.php';

// 3. Order Automation
require_once dirname( __FILE__ ) . '/includes/events/class-mi-erp-order-automation.php';

// 4. Advanced Shipping
require_once dirname( __FILE__ ) . '/includes/shipping/class-mi-erp-shipping.php';

// 5. Frontend Warranty Portal
if ( ! is_admin() || wp_doing_ajax() ) {
    require_once dirname( __FILE__ ) . '/includes/frontend/class-mi-erp-frontend.php';
}

// 6. SEO & Schema
require_once dirname( __FILE__ ) . '/includes/seo/class-mi-erp-seo-schema.php';

