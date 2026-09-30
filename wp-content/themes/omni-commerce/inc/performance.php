<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * 1. Defer parsing of JavaScript
 * Giúp tối ưu điểm Render-blocking trong PageSpeed
 */
add_filter( 'script_loader_tag', 'omni_commerce_defer_scripts', 10, 3 );
function omni_commerce_defer_scripts( $tag, $handle, $src ) {
    // Không defer script trong trang admin hoặc jquery core (tránh lỗi plugin phụ thuộc jquery)
    if ( is_admin() || strpos( $handle, 'jquery' ) !== false ) {
        return $tag;
    }
    
    // Thêm thuộc tính defer
    if ( strpos( $tag, 'defer' ) === false ) {
        $tag = str_replace( ' src', ' defer="defer" src', $tag );
    }
    
    return $tag;
}

/**
 * 2. Remove query strings from static resources
 * Cải thiện bộ nhớ cache (Caching) cho tài nguyên tĩnh
 */
function omni_commerce_remove_script_version( $src ) {
    if ( strpos( $src, 'tailwind.css' ) !== false ) {
        return $src;
    }
    if ( strpos( $src, '?ver=' ) ) {
        $src = remove_query_arg( 'ver', $src );
    }
    return $src;
}
add_filter( 'style_loader_src', 'omni_commerce_remove_script_version', 15, 1 );
add_filter( 'script_loader_src', 'omni_commerce_remove_script_version', 15, 1 );

/**
 * 3. Disable WP Emojis
 * Tiết kiệm HTTP requests không cần thiết cho trang TMĐT
 */
function omni_commerce_disable_emojis() {
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
    remove_action( 'admin_print_styles', 'print_emoji_styles' );
    remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
    remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
    remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
}
add_action( 'init', 'omni_commerce_disable_emojis' );
