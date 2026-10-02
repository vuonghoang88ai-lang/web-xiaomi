<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function omni_commerce_customize_register( $wp_customize ) {
    // Topbar Section
    $wp_customize->add_section( 'omni_commerce_topbar_section', array(
        'title'    => __( 'Topbar Settings', 'omni-commerce' ),
        'priority' => 30,
    ) );

    // Topbar Text
    $wp_customize->add_setting( 'omni_commerce_topbar_text', array(
        'default'           => 'Chào mừng quý khách đến với Xiaomi Store - Hệ thống phân phối chính hãng',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'omni_commerce_topbar_text', array(
        'label'   => __( 'Topbar Text', 'omni-commerce' ),
        'section' => 'omni_commerce_topbar_section',
        'type'    => 'text',
    ) );

    // Address
    $wp_customize->add_setting( 'omni_commerce_address', array(
        'default'           => 'Số 41 Khuất Duy Tiến, Thanh Xuân, Hà Nội',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'omni_commerce_address', array(
        'label'   => __( 'Address', 'omni-commerce' ),
        'section' => 'omni_commerce_topbar_section',
        'type'    => 'text',
    ) );

    // Hotline
    $wp_customize->add_setting( 'omni_commerce_hotline', array(
        'default'           => '0822.83.4444',
        'sanitize_callback' => 'sanitize_text_field',
    ) );
    $wp_customize->add_control( 'omni_commerce_hotline', array(
        'label'   => __( 'Hotline', 'omni-commerce' ),
        'section' => 'omni_commerce_topbar_section',
        'type'    => 'text',
    ) );

    // Theme Colors
    $wp_customize->add_setting( 'omni_commerce_primary_color', array(
        'default'           => '#006779',
        'sanitize_callback' => 'sanitize_hex_color',
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'omni_commerce_primary_color', array(
        'label'   => __( 'Primary Color', 'omni-commerce' ),
        'section' => 'colors',
    ) ) );

    $wp_customize->add_setting( 'omni_commerce_secondary_color', array(
        'default'           => '#e3001b',
        'sanitize_callback' => 'sanitize_hex_color',
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'omni_commerce_secondary_color', array(
        'label'   => __( 'Secondary Color', 'omni-commerce' ),
        'section' => 'colors',
    ) ) );
}
add_action( 'customize_register', 'omni_commerce_customize_register' );

// Output Custom Colors as CSS variables in header
function omni_commerce_customizer_css() {
    $primary = get_theme_mod( 'omni_commerce_primary_color', '#006779' );
    $secondary = get_theme_mod( 'omni_commerce_secondary_color', '#e3001b' );
    ?>
    <style type="text/css">
        :root {
            --color-primary: <?php echo esc_attr( $primary ); ?>;
            --color-secondary: <?php echo esc_attr( $secondary ); ?>;
        }
    </style>
    <?php
}
add_action( 'wp_head', 'omni_commerce_customizer_css' );
