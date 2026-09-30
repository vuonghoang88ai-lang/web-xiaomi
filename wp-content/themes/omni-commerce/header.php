<!DOCTYPE html>

<html <?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
 language_attributes(); ?>><head>
<meta charset="<?php bloginfo( 'charset' ); ?>"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">
<!-- Title tag is handled by wp_head() through theme support -->
<?php wp_head(); ?>
</head>
<body <?php body_class("bg-surface font-body-md text-on-surface"); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#primary">Skip to content</a>
<!-- 1. Header Section -->
<header class="w-full sticky top-0 z-50 shadow-sm transition-all duration-300">
<!-- Top Bar -->
<div class="bg-primary-container text-white">
<div class="max-w-[1440px] mx-auto py-2 px-4 md:px-20 flex justify-between items-center text-label-sm mi-topbar-wrapper">
<div class="mi-topbar-text hidden lg:block"><?php echo esc_html(get_theme_mod('omni_commerce_topbar_text', __( 'Chào mừng quý khách đến với Xiaomi Store - Hệ thống phân phối chính hãng', 'omni-commerce' ))); ?></div>
<div class="flex flex-col md:flex-row items-center gap-1 md:gap-6 w-full lg:w-auto justify-center text-center">
<div class="flex items-center gap-1 mi-topbar-address">
<span class="material-symbols-outlined text-[16px] hidden md:inline-block">location_on</span>
<span class="line-clamp-1 md:line-clamp-none"><?php echo esc_html(get_theme_mod('omni_commerce_address', 'Số 41 Khuất Duy Tiến, Thanh Xuân, Hà Nội')); ?></span>
</div>
<div class="flex items-center gap-1">
<span class="material-symbols-outlined text-[16px]">call</span>
<span class="font-bold"><?php esc_html_e( 'Hotline:', 'omni-commerce' ); ?> <?php echo esc_html(get_theme_mod('omni_commerce_hotline', '0822.83.4444')); ?></span>
</div>
</div>
</div>
<!-- Main Header -->
</div>
<div class="bg-white border-b border-outline-variant">
<div class="max-w-[1440px] mx-auto py-3 md:py-4 px-4 lg:px-8 xl:px-12 flex flex-wrap lg:flex-nowrap items-center justify-between gap-y-3 gap-x-4">
<!-- Logo -->
<div class="shrink-0 flex items-center gap-2 order-1">
<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="flex items-center gap-2">
<?php 
$header_logo = wp_get_attachment_image_url( get_theme_mod( 'custom_logo' ), 'full' );
if ( ! $header_logo && function_exists('get_field') ) {
    $header_logo = get_field('mi_hf_logo', 'option');
}
if ($header_logo): 
?>
<img src="<?php echo esc_url($header_logo); ?>" alt="Xiaomi Store" class="h-10 w-auto object-contain" width="160" height="40" fetchpriority="high" />
<?php else: ?>
<div class="size-10 bg-secondary flex items-center justify-center rounded-lg">
<svg class="w-6 h-6" fill="white" viewbox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
<path d="M4 42.4379C4 42.4379 14.0962 36.0744 24 41.1692C35.0664 46.8624 44 42.2078 44 42.2078L44 7.01134C44 7.01134 35.068 11.6577 24.0031 5.96913C14.0971 0.876274 4 7.27094 4 7.27094L4 42.4379Z" fill="currentColor"></path>
</svg>
</div>
<span class="text-2xl text-secondary font-bold tracking-tight whitespace-nowrap"><?php esc_html_e( 'Xiaomi Store', 'omni-commerce' ); ?></span>
<?php endif; ?>
</a>
</div>
<!-- Navigation (Giữ nguyên 100% cấu trúc thẻ, class theo Mục 4 AGENTS.md) -->
<nav class="hidden lg:flex flex-1 justify-center gap-4 xl:gap-8 main-nav-header order-2">
<?php mi_render_header_navigation( false ); ?>
</nav>
<!-- Search Bar -->
<div class="w-full md:w-[20%] lg:w-[150px] xl:w-[180px] order-3 relative shrink-0">
<form role="search" method="get" class="w-full relative flex items-center" action="<?php echo esc_url(home_url('/')); ?>">
<input class="w-full pl-4 pr-10 py-2 bg-gray-50 lg:bg-white border border-outline-variant rounded-full text-body-md focus:ring-2 focus:ring-primary outline-none transition-shadow appearance-none" placeholder="<?php esc_attr_e( 'Tìm gì...?', 'omni-commerce' ); ?>" type="text" name="s" value="<?php echo get_search_query(); ?>"/>
<input type="hidden" name="post_type" value="product" />
<button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-on-surface flex items-center justify-center hover:text-primary transition-colors bg-transparent border-none p-0 cursor-pointer appearance-none"><span class="material-symbols-outlined text-[20px]">search</span></button>
</form>
</div>
<!-- Actions (Cart & Menu) -->
<div class="shrink-0 flex items-center justify-end gap-1 md:gap-3 order-2 lg:order-4">
<a href="<?php echo esc_url( function_exists('wc_get_cart_url') ? wc_get_cart_url() : '#' ); ?>" class="relative p-2 text-primary group cursor-pointer block shrink-0" aria-label="Giỏ hàng">
<span class="material-symbols-outlined text-[28px] group-hover:scale-110 transition-transform">shopping_cart</span>
<span class="cart-contents-count absolute top-0 right-0 bg-secondary text-white text-[10px] w-5 h-5 flex items-center justify-center rounded-full border-2 border-white"><?php echo function_exists('WC') ? WC()->cart->get_cart_contents_count() : '0'; ?></span>
</a>
<!-- Mobile Menu Toggle Button -->
<button id="mi-mobile-menu-toggle" type="button" aria-expanded="false" aria-controls="mi-mobile-menu" class="lg:hidden p-2 text-on-surface hover:text-primary transition-colors focus:outline-none flex items-center justify-center" aria-label="Menu">
<span id="mi-menu-icon" class="material-symbols-outlined text-[28px]">menu</span>
</button>
</div>
</div>
</div>
<!-- Mobile Navigation Drawer -->
<div id="mi-mobile-menu" class="hidden lg:hidden bg-white border-t border-outline-variant px-4 py-3 shadow-inner">
<div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
<?php mi_render_header_navigation( true ); ?>
</div>
</div>
</div>
</header>