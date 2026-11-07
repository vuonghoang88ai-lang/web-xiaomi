<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>><head>
<meta charset="<?php bloginfo( 'charset' ); ?>"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">
<link rel="icon" type="image/png" sizes="32x32" href="<?php echo esc_url( get_theme_file_uri( '/assets/images/favicon.png' ) ); ?>" />
<link rel="apple-touch-icon" sizes="180x180" href="<?php echo esc_url( get_theme_file_uri( '/assets/images/tongkhomi-icon.png' ) ); ?>" />
<!-- Title tag is handled by wp_head() through theme support -->
<?php wp_head(); ?>
</head>
<body <?php body_class("bg-surface font-body-md text-on-surface"); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#primary">Skip to content</a>
<style>
/* Đảm bảo Topbar hiển thị chuẩn xác giữa Mobile và Desktop không phụ thuộc JIT Tailwind */
@media (max-width: 899px) {
    .mi-topbar-text { display: none !important; }
    .mi-topbar-info { width: 100% !important; justify-content: center !important; text-align: center !important; }
}
@media (min-width: 900px) {
    .mi-topbar-text { display: block !important; }
    .mi-topbar-info { width: auto !important; flex-direction: row !important; gap: 1.5rem !important; justify-content: flex-end !important; }
}
/* Chuẩn hóa thị giác giá sản phẩm: Giá khuyến mãi đỏ đậm nổi bật, giá gốc chìm xám gạch ngang */
del, .price del, .product-card del {
    color: #94a3b8 !important;
    font-size: 0.82em !important;
    font-weight: 400 !important;
    text-decoration: line-through !important;
}
ins, .price ins, .product-card ins {
    color: var(--secondary, #ef4444) !important;
    font-size: 1.05em !important;
    font-weight: 900 !important;
    text-decoration: none !important;
}
/* Chống tràn thẻ sản phẩm và bảo vệ layout mobile full màn hình */
html, body {
    max-width: 100% !important;
    overflow-x: clip !important;
}
img, video, iframe {
    max-width: 100% !important;
}
.mi-page-content table, .prose table {
    display: block !important;
    width: 100% !important;
    overflow-x: auto !important;
    -webkit-overflow-scrolling: touch !important;
}
.product-card {
    min-width: 0 !important;
    max-width: 100% !important;
    box-sizing: border-box !important;
}
.product-card .woocommerce-Price-amount {
    white-space: nowrap !important;
}

/* =====================================================================
   PRODUCT CARD – RESPONSIVE PRICE & RATING ROWS
   Thay thế sm:flex-row/sm:flex-col không tồn tại trong precompiled CSS.
   Mobile (<640px): dọc (flex-col). Desktop (≥640px): ngang (flex-row).
   ===================================================================== */

/* --- Khối giá: dọc mặc định (mobile) --- */
.pc-price-row {
    display: flex;
    flex-direction: column;
    gap: 2px;
}
/* --- Khối giá: ngang trên desktop --- */
@media (min-width: 640px) {
    .pc-price-row {
        flex-direction: row;
        align-items: baseline;
        gap: 6px;
        flex-wrap: wrap;
    }
}

/* --- Hàng sao + Đã bán: dọc mặc định (mobile) --- */
.pc-meta-row {
    display: flex;
    flex-direction: column;
    gap: 2px;
}
/* --- Hàng sao + Đã bán: ngang trên desktop --- */
@media (min-width: 640px) {
    .pc-meta-row {
        flex-direction: row;
        align-items: center;
        justify-content: space-between;
        gap: 4px;
    }
}
/* Chuẩn hóa kích thước logo thương hiệu TongKhoMi.com đồng bộ tuyệt đối với footer */
.mi-brand-logo {
    height: 42px !important;
    max-height: 42px !important;
    width: auto !important;
    object-fit: contain !important;
    display: block !important;
    transition: filter 0.2s ease, transform 0.2s ease;
}
@media (min-width: 768px) {
    .mi-brand-logo {
        height: 48px !important;
        max-height: 48px !important;
    }
}
.mi-brand-logo:hover {
    filter: brightness(1.05);
}
</style>
<!-- 1. Header Section -->
<?php
$topbar_text = function_exists('get_field') ? get_field('mi_hf_topbar_text', 'option') : '';
if ( empty($topbar_text) ) {
    $topbar_text = get_theme_mod('omni_commerce_topbar_text', __( 'Chào mừng quý khách đến với TongKhoMi.com - Tổng Kho Phân Phối Xiaomi Chính Hãng', 'omni-commerce' ));
}

$topbar_address = function_exists('get_field') ? get_field('mi_hf_topbar_address', 'option') : '';
if ( empty($topbar_address) ) {
    $topbar_address = get_theme_mod('omni_commerce_address', 'Số 65 Hồ Ba Mẫu, Đống Đa, Hà Nội');
}

$hotline_number = function_exists('get_field') ? get_field('mi_hf_topbar_hotline', 'option') : '';
if ( empty($hotline_number) ) {
    $hotline_number = get_theme_mod('omni_commerce_hotline', '0822.83.4444');
}
?>
<header class="w-full sticky top-0 z-50 shadow-sm transition-all duration-300">
<!-- Top Bar -->
<div class="bg-primary-container text-white">
<div class="max-w-[1440px] mx-auto py-2 px-4 flex justify-between items-center text-label-sm mi-topbar-wrapper">
<div class="mi-topbar-text"><?php echo esc_html($topbar_text); ?></div>
<div class="flex flex-col md:flex-row items-center gap-1 md:gap-6 w-full lg:w-auto justify-center text-center mi-topbar-info">
<div class="flex items-center gap-1 mi-topbar-address">
<span class="material-symbols-outlined text-[16px] hidden md:inline-block">location_on</span>
<span class="line-clamp-1 md:line-clamp-none"><?php echo esc_html($topbar_address); ?></span>
</div>
<div class="flex items-center gap-1">
<span class="material-symbols-outlined text-[16px]">call</span>
<span class="font-bold"><?php esc_html_e( 'Hotline:', 'omni-commerce' ); ?> <?php echo esc_html($hotline_number); ?></span>
</div>
</div>
</div>
<!-- Main Header -->
</div>
<div class="bg-white border-b border-outline-variant">
<div class="max-w-[1440px] mx-auto py-3 md:py-4 px-4 flex flex-wrap lg:flex-nowrap items-center justify-between gap-y-3 gap-x-4">
<!-- Logo -->
<div class="shrink-0 flex items-center gap-2 order-1">
<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="flex items-center gap-2 group" title="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>">
<?php 
$custom_logo_url = wp_get_attachment_image_url( get_theme_mod( 'custom_logo' ), 'full' );
if ( ! $custom_logo_url && function_exists('get_field') ) {
    $custom_logo_url = get_field('mi_hf_logo', 'option');
}
$site_name = get_bloginfo( 'name', 'display' ) ?: 'TongKhoMi.com - Tổng Kho Xiaomi Chính Hãng';

if ( $custom_logo_url ) : 
?>
<img src="<?php echo esc_url($custom_logo_url); ?>" alt="<?php echo esc_attr($site_name); ?>" class="mi-brand-logo" width="150" height="48" fetchpriority="high" />
<?php else: ?>
<picture>
    <source srcset="<?php echo esc_url( get_theme_file_uri( '/assets/images/tongkhomi-logo.webp' ) ); ?>" type="image/webp" />
    <img src="<?php echo esc_url( get_theme_file_uri( '/assets/images/tongkhomi-logo.png' ) ); ?>" alt="<?php echo esc_attr($site_name); ?>" class="mi-brand-logo" width="150" height="48" fetchpriority="high" />
</picture>
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
<input class="w-full pl-4 pr-10 py-2 bg-gray-50 lg:bg-white border border-outline-variant rounded-full text-body-md focus:ring-2 focus:ring-primary outline-none transition-shadow appearance-none" placeholder="<?php esc_attr_e( 'Tìm gì...?', 'omni-commerce' ); ?>" type="text" name="s" value="<?php echo esc_attr( get_search_query() ); ?>"/>
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
</header>