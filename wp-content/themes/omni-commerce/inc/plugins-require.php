<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once get_template_directory() . '/inc/class-tgm-plugin-activation.php';

add_action( 'tgmpa_register', 'omni_commerce_register_required_plugins' );

function omni_commerce_register_required_plugins() {
    $plugins = array(
        array(
            'name'      => 'Advanced Custom Fields',
            'slug'      => 'advanced-custom-fields',
            'required'  => true,
        ),
        array(
            'name'      => 'WooCommerce',
            'slug'      => 'woocommerce',
            'required'  => true,
        ),
        array(
            'name'      => 'Mi ERP System',
            'slug'      => 'viomi-erp-system',
            'required'  => false, // ERP system is optional but recommended
        ),
    );

    $config = array(
        'id'           => 'omni-commerce',         // Unique ID for hashing notices for multiple instances of TGMPA.
        'default_path' => '',                      // Default absolute path to bundled plugins.
        'menu'         => 'tgmpa-install-plugins', // Menu slug.
        'parent_slug'  => 'themes.php',            // Parent menu slug.
        'capability'   => 'edit_theme_options',    // Capability needed to view plugin install page, should be a capability associated with the parent menu used.
        'has_notices'  => true,                    // Show admin notices or not.
        'dismissable'  => false,                   // If false, a user cannot dismiss the nag message.
        'dismiss_msg'  => '',                      // If 'dismissable' is false, this message will be output at top of nag.
        'is_automatic' => true,                    // Automatically activate plugins after installation or not.
        'message'      => '',                      // Message to output right before the plugins table.
    );

    tgmpa( $plugins, $config );
}

// Fallback for ACF functions to prevent fatal errors when ACF is not active yet
if ( ! function_exists( 'get_field' ) ) {
    function get_field( $selector, $post_id = false, $format_value = true ) {
        return false;
    }
}
if ( ! function_exists( 'the_field' ) ) {
    function the_field( $selector, $post_id = false, $format_value = true ) {
        return false;
    }
}
if ( ! function_exists( 'have_rows' ) ) {
    function have_rows( $selector, $post_id = false ) {
        return false;
    }
}
if ( ! function_exists( 'the_row' ) ) {
    function the_row() {
        return false;
    }
}
if ( ! function_exists( 'get_sub_field' ) ) {
    function get_sub_field( $selector ) {
        return false;
    }
}
