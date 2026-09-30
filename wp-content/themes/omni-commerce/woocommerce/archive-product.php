<?php
/**
 * @version 1.0.0
 */
/**
 * The Template for displaying product archives, including the main shop page which is a post type archive
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/archive-product.php.
 */

defined( 'ABSPATH' ) || exit;

get_header(); ?>

<!-- Navigation Bar -->
<nav class="bg-white border-b border-border-gray hidden md:block">
    <div class="mx-auto max-w-[1440px] px-4">
        <?php
        $dropdown_cats = get_terms( array(
            'taxonomy'   => 'product_cat',
            'parent'     => 0,
            'hide_empty' => false,
        ) );
        
        ob_start();
        ?>
        <li class="relative group flex items-center gap-1 text-primary cursor-pointer py-1">
            <span class="material-symbols-outlined">menu</span> DANH MỤC SẢN PHẨM
            <span class="material-symbols-outlined text-xs transition-transform duration-200 group-hover:rotate-180">expand_more</span>
            
            <!-- Cột Submenu xổ xuống bên tay trái khi rê chuột -->
            <div class="absolute left-0 top-full mt-2 w-64 bg-white border border-border-gray rounded-lg shadow-xl opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50 py-2">
                <ul class="space-y-1 text-xs font-normal normal-case text-text-main">
                    <?php 
                    if ( ! empty( $dropdown_cats ) && ! is_wp_error( $dropdown_cats ) ) :
                        foreach ( $dropdown_cats as $dc ) :
                            if ( $dc->slug === 'uncategorized' ) continue;
                    ?>
                        <li>
                            <a href="<?php echo esc_url( get_term_link( $dc ) ); ?>" class="group/sub flex items-center justify-between px-4 py-2.5 hover:bg-background-light hover:text-primary transition-colors">
                                <span class="font-medium transition-transform group-hover/sub:translate-x-1"><?php echo esc_html( $dc->name ); ?></span>
                                <span class="material-symbols-outlined text-xs text-text-muted transition-transform group-hover/sub:translate-x-1">chevron_right</span>
                            </a>
                        </li>
                    <?php 
                        endforeach;
                    endif;
                    ?>
                </ul>
            </div>
        </li>
        <?php
        $dropdown_menu_li = ob_get_clean();

        $cat_menu_locations = get_nav_menu_locations();
        $has_valid_cat_menu = false;
        if ( ! empty( $cat_menu_locations['category-menu'] ) ) {
            $cat_items = wp_get_nav_menu_items( $cat_menu_locations['category-menu'] );
            if ( ! empty( $cat_items ) ) {
                $has_valid_cat_menu = true;
            }
        }

        if ( $has_valid_cat_menu ) {
            wp_nav_menu( array(
                'theme_location' => 'category-menu',
                'container'      => false,
                'menu_class'     => 'flex items-center gap-8 py-3 text-sm font-semibold uppercase',
                'items_wrap'     => '<ul id="%1$s" class="%2$s">' . $dropdown_menu_li . '%3$s</ul>',
                'fallback_cb'    => false,
            ) );
        } else {
            // Fallback Menu matching category.html
            $default_cats = array(
                'tivi-xiaomi'       => 'TIVI XIAOMI',
                'robot-hut-bui'     => 'ROBOT HÚT BỤI',
                'may-loc-khong-khi' => 'MÁY LỌC KHÔNG KHÍ',
                'may-giat'          => 'MÁY GIẶT',
                'may-chay-bo'       => 'MÁY CHẠY BỘ',
            );
        ?>
        <ul class="flex items-center gap-8 py-3 text-sm font-semibold uppercase">
            <?php echo $dropdown_menu_li; ?>
            <?php foreach ( $default_cats as $slug => $name ) : 
                $term_obj = get_term_by( 'slug', $slug, 'product_cat' );
                $link = $term_obj && ! is_wp_error( $term_obj ) ? get_term_link( $term_obj ) : home_url( '/?s=' . urlencode( strtolower( $name ) ) . '&post_type=product' );
            ?>
                <li class="hover:text-primary cursor-pointer"><a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $name ); ?></a></li>
            <?php endforeach; ?>
            <li class="text-secondary cursor-pointer"><a href="<?php echo esc_url( function_exists('wc_get_page_permalink') ? wc_get_page_permalink( 'shop' ) : home_url( '/shop/' ) ); ?>?on_sale=1">KHUYẾN MÃI HOT</a></li>
        </ul>
        <?php } ?>
    </div>
</nav>

<?php do_action( 'woocommerce_before_main_content' ); ?>

<?php
// --- Biến dùng chung toàn trang ---
$current_term  = get_queried_object();
$mi_base_url   = remove_query_arg( array_keys( $_GET ) );
?>

<main id="primary" class="mx-auto max-w-[1440px] px-4 py-4">

<!-- Breadcrumbs -->
<div class="text-[13px] text-text-muted mb-6">
    <?php woocommerce_breadcrumb( array(
        'delimiter'   => ' <span class="mx-2">/</span> ',
        'wrap_before' => '<nav class="woocommerce-breadcrumb flex items-center flex-wrap">',
        'wrap_after'  => '</nav>',
        'before'      => '<span class="hover:text-primary">',
        'after'       => '</span>',
    ) ); ?>
</div>

<!-- Top Banner Grid (Fallback to dummy data if no ACF) -->
<?php
$banner_set = function_exists( 'mi_get_product_category_banner_set' ) ? mi_get_product_category_banner_set( $current_term ) : array();
$banner_1 = isset( $current_term->taxonomy ) && function_exists( 'get_field' ) ? get_field( 'cat_banner_1', $current_term ) : '';
$banner_2 = isset( $current_term->taxonomy ) && function_exists( 'get_field' ) ? get_field( 'cat_banner_2', $current_term ) : '';
$banner_3 = isset( $current_term->taxonomy ) && function_exists( 'get_field' ) ? get_field( 'cat_banner_3', $current_term ) : '';
$banner_1 = $banner_1 ?: ( $banner_set[0] ?? '' );
$banner_2 = $banner_2 ?: ( $banner_set[1] ?? '' );
$banner_3 = $banner_3 ?: ( $banner_set[2] ?? '' );

if ( $banner_1 || $banner_2 || $banner_3 ) :
?>
<style>
    /* Ẩn khối banner trên Mobile để tránh UX Bloat, giữ nguyên trên Desktop */
    @media (max-width: 1023px) {
        .mi-desktop-only-banner { display: none !important; }
    }
</style>
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8 mi-desktop-only-banner">
    <div class="h-48 rounded-xl overflow-hidden relative group cursor-pointer bg-cover bg-center shadow-sm" style="background-image: url('<?php echo esc_url( $banner_1 ); ?>');">
        <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/20 to-black/10 group-hover:from-black/80 group-hover:via-black/20 transition-all"></div>
        <div class="absolute inset-x-0 bottom-0 p-4">
            <span class="inline-flex items-center rounded-full bg-white/15 border border-white/30 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.08em] text-white backdrop-blur-sm">Nổi bật</span>
            <p class="mt-2 text-lg font-bold text-white">Smart TV</p>
        </div>
    </div>
    <div class="h-48 rounded-xl overflow-hidden relative group cursor-pointer bg-cover bg-center shadow-sm" style="background-image: url('<?php echo esc_url( $banner_2 ); ?>');">
        <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/20 to-black/10 group-hover:from-black/80 group-hover:via-black/20 transition-all"></div>
        <div class="absolute inset-x-0 bottom-0 p-4">
            <span class="inline-flex items-center rounded-full bg-white/15 border border-white/30 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.08em] text-white backdrop-blur-sm">Phòng khách</span>
            <p class="mt-2 text-lg font-bold text-white">Công nghệ mới</p>
        </div>
    </div>
    <div class="h-48 rounded-xl overflow-hidden relative group cursor-pointer bg-cover bg-center shadow-sm" style="background-image: url('<?php echo esc_url( $banner_3 ); ?>');">
        <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/20 to-black/10 group-hover:from-black/80 group-hover:via-black/20 transition-all"></div>
        <div class="absolute inset-x-0 bottom-0 p-4">
            <span class="inline-flex items-center rounded-full bg-white/15 border border-white/30 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.08em] text-white backdrop-blur-sm">Gia đình</span>
            <p class="mt-2 text-lg font-bold text-white">Tiết kiệm năng lượng</p>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="flex flex-col lg:flex-row gap-8">
    <!-- Sidebar Content Buffer -->
    <?php 
    $cur_min  = isset( $_GET['min_price'] ) ? (float) sanitize_text_field( $_GET['min_price'] ) : 0;
    $cur_max  = isset( $_GET['max_price'] ) ? (float) sanitize_text_field( $_GET['max_price'] ) : 0;
    $cur_size = isset( $_GET['size'] ) ? sanitize_text_field( $_GET['size'] ) : '';
    $has_active_filters = ( isset( $_GET['min_price'] ) || isset( $_GET['max_price'] ) || ! empty( $cur_size ) );
    
    $is_fridge = ( isset( $current_term->slug ) && strpos( $current_term->slug, 'tu-lanh' ) !== false );
    
    ob_start(); 
    ?>
        <!-- Category Tree -->
        <div class="mb-8 p-4 bg-background-light rounded-xl border border-border-gray shadow-sm">
            <div class="flex items-center justify-between border-b border-primary/40 pb-2 mb-4">
                <h2 class="text-base font-bold text-primary uppercase !m-0">DANH MỤC</h2>
            </div>
            <ul class="space-y-3 text-sm">
                <?php 
                $current_term_id = ( isset( $current_term->term_id ) && isset( $current_term->taxonomy ) && $current_term->taxonomy === 'product_cat' ) ? $current_term->term_id : 0;
                
                // 1. Tìm danh mục cha cấp cao nhất của danh mục hiện tại
                $top_parent_id = 0;
                $top_parent = null;
                
                if ( $current_term_id > 0 ) {
                    $ancestors = get_ancestors( $current_term_id, 'product_cat' );
                    if ( empty( $ancestors ) ) {
                        $top_parent_id = $current_term_id;
                        $top_parent = $current_term;
                    } else {
                        $top_parent_id = end( $ancestors );
                        $top_parent = get_term( $top_parent_id, 'product_cat' );
                    }
                }
                
                // 2. Render danh mục cha và các con của nó
                if ( $top_parent && ! is_wp_error( $top_parent ) ) {
                    // Render danh mục cha
                    $is_parent_active = ( $current_term_id === $top_parent_id );
                    $parent_class = $is_parent_active ? 'text-primary font-semibold' : 'text-text-muted hover:text-primary cursor-pointer';
                    
                    echo '<li class="flex justify-between items-center ' . esc_attr($parent_class) . '">';
                    echo '<a href="' . esc_url( get_term_link( $top_parent ) ) . '" class="w-full">' . esc_html( $top_parent->name ) . '</a>';
                    if ( $is_parent_active ) {
                        echo '<span class="material-symbols-outlined text-xs">chevron_right</span>';
                    }
                    echo '</li>';
                    
                    // Render các danh mục con
                    $subcats = get_terms( array(
                        'taxonomy'   => 'product_cat',
                        'parent'     => $top_parent_id,
                        'hide_empty' => false,
                    ) );
                    
                    if ( ! empty( $subcats ) && ! is_wp_error( $subcats ) ) {
                        foreach ( $subcats as $subcat ) {
                            $is_sub_active = ( $current_term_id === $subcat->term_id );
                            $sub_class = $is_sub_active ? 'text-primary font-semibold' : 'text-text-muted hover:text-primary cursor-pointer';
                            echo '<li class="flex justify-between items-center ' . esc_attr($sub_class) . '">';
                            echo '<a href="' . esc_url( get_term_link( $subcat ) ) . '" class="w-full">' . esc_html( $subcat->name ) . '</a>';
                            if ( $is_sub_active ) {
                                echo '<span class="material-symbols-outlined text-xs">chevron_right</span>';
                            }
                            echo '</li>';
                        }
                    }
                } elseif ( $current_term_id === 0 ) {
                    // Nếu ở trang Shop tổng, render tất cả danh mục gốc giống menu ngang
                    $topcats = ( ! empty( $dropdown_cats ) && ! is_wp_error( $dropdown_cats ) ) ? $dropdown_cats : array();
                    if ( ! empty( $topcats ) ) {
                        foreach ( $topcats as $cat ) {
                            if ( $cat->slug === 'uncategorized' ) continue;
                            echo '<li class="flex justify-between items-center text-text-muted hover:text-primary cursor-pointer">';
                            echo '<a href="' . esc_url( get_term_link( $cat ) ) . '" class="w-full">' . esc_html( $cat->name ) . '</a>';
                            echo '</li>';
                        }
                    }
                }
                ?>
            </ul>
        </div>

        <!-- Dynamic WooCommerce Filters / BỘ LỌC -->
        
        <div class="mb-8 p-4 bg-background-light rounded-xl border border-border-gray shadow-sm" id="mi-filters-accordion">
            <div class="flex items-center justify-between border-b border-primary/40 pb-2 mb-4">
                <h2 class="text-base font-bold text-primary uppercase !m-0">BỘ LỌC</h2>
                <?php if ( $has_active_filters ) : ?>
                    <a href="<?php echo esc_url( $mi_base_url ); ?>" class="text-[11px] text-secondary hover:underline font-semibold flex items-center gap-0.5" title="Xóa tất cả bộ lọc">
                        <span class="material-symbols-outlined !text-sm">close</span> Xóa bộ lọc
                    </a>
                <?php endif; ?>
            </div>
            
            <!-- Built-in HTML Filter matching category.html -->
                <div class="mb-6">
                    <p class="font-bold text-xs uppercase mb-3 flex items-center justify-between">
                        Khoảng giá <span class="material-symbols-outlined text-sm">expand_more</span>
                    </p>
                    <div class="space-y-2">
                        <?php
                        $price_ranges = array(
                            array( 'label' => 'Dưới 5 triệu',   'min' => 0,        'max' => 5000000 ),
                            array( 'label' => '5 - 10 triệu',   'min' => 5000000,  'max' => 10000000 ),
                            array( 'label' => '10 - 20 triệu',  'min' => 10000000, 'max' => 20000000 ),
                            array( 'label' => 'Trên 20 triệu',  'min' => 20000000, 'max' => 0 ),
                        );
                        foreach ( $price_ranges as $idx => $range ) :
                            $is_checked = false;
                            if ( $range['max'] > 0 && $range['min'] == 0 ) {
                                if ( isset( $_GET['max_price'] ) && $cur_max == $range['max'] && ( ! isset( $_GET['min_price'] ) || $cur_min == 0 ) ) {
                                    $is_checked = true;
                                }
                            } elseif ( $range['max'] > 0 ) {
                                if ( isset( $_GET['min_price'] ) && isset( $_GET['max_price'] ) && $cur_min == $range['min'] && $cur_max == $range['max'] ) {
                                    $is_checked = true;
                                }
                            } else {
                                if ( isset( $_GET['min_price'] ) && $cur_min >= $range['min'] && ( ! isset( $_GET['max_price'] ) || $cur_max == 0 ) ) {
                                    $is_checked = true;
                                }
                            }
                        ?>
                        <label class="flex items-center text-xs text-text-muted cursor-pointer hover:text-primary">
                            <input class="mr-2 rounded border-gray-300 text-primary focus:ring-primary mi-price-filter-input" 
                                   type="checkbox" 
                                   data-min="<?php echo esc_attr( $range['min'] ); ?>" 
                                   data-max="<?php echo esc_attr( $range['max'] ); ?>" 
                                   <?php checked( $is_checked ); ?> /> 
                            <?php echo esc_html( $range['label'] ); ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php
                $size_filter_title = $is_fridge ? 'Dung tích sử dụng' : 'Kích thước màn hình';
                $size_options = $is_fridge 
                    ? array( 'Dưới 300L' => '300', '300 - 450L' => '430', '450 - 600L' => '500', 'Trên 600L' => '600' )
                    : array( '32 inch' => '32', '43 inch' => '43', '50 inch' => '50', '55 inch' => '55', '65 inch' => '65', '75+ inch' => '75' );
                ?>
                <div class="mb-2">
                    <p class="font-bold text-xs uppercase mb-3 flex items-center justify-between">
                        <?php echo esc_html( $size_filter_title ); ?> <span class="material-symbols-outlined text-sm">expand_more</span>
                    </p>
                    <div class="grid grid-cols-2 gap-2">
                        <?php
                        foreach ( $size_options as $lbl => $sz_val ) :
                            $is_sz_checked = ( $cur_size === (string) $sz_val );
                        ?>
                        <label class="flex items-center text-xs text-text-muted cursor-pointer hover:text-primary">
                            <input class="mr-2 rounded border-gray-300 text-primary focus:ring-primary mi-size-filter-input" 
                                   type="checkbox" 
                                   data-size="<?php echo esc_attr( $sz_val ); ?>" 
                                   <?php checked( $is_sz_checked ); ?> /> 
                            <?php echo esc_html( $lbl ); ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
        </div>
    <?php $mi_sidebar_content = ob_get_clean(); ?>

    <!-- Left Sidebar (Desktop only) -->
    <style>
        /* [BUG-026] Sử dụng CSS thuần để ẩn Sidebar trên Mobile, bảo vệ tuyệt đối layout Desktop */
        @media (max-width: 1023px) {
            .mi-desktop-sidebar { display: none !important; }
        }
    </style>
    <aside class="w-full lg:w-1/4 shrink-0 mi-sidebar-accordion mi-desktop-sidebar">
        <?php echo $mi_sidebar_content; ?>
    </aside>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Price Range Filtering Handler
        const priceInputs = document.querySelectorAll('.mi-price-filter-input');
        priceInputs.forEach(input => {
            input.addEventListener('change', function() {
                const url = new URL(window.location.href);
                if (this.checked) {
                    // Uncheck siblings
                    priceInputs.forEach(other => { if (other !== this) other.checked = false; });
                    const min = this.getAttribute('data-min');
                    const max = this.getAttribute('data-max');
                    if (min && min !== '0') {
                        url.searchParams.set('min_price', min);
                    } else if (min === '0' && max && max !== '0') {
                        url.searchParams.set('min_price', '0');
                    } else {
                        url.searchParams.delete('min_price');
                    }
                    if (max && max !== '0') {
                        url.searchParams.set('max_price', max);
                    } else {
                        url.searchParams.delete('max_price');
                    }
                } else {
                    url.searchParams.delete('min_price');
                    url.searchParams.delete('max_price');
                }
                url.searchParams.delete('paged');
                window.location.href = url.toString();
            });
        });

        // Size Filter Handler
        const sizeInputs = document.querySelectorAll('.mi-size-filter-input');
        sizeInputs.forEach(input => {
            input.addEventListener('change', function() {
                const url = new URL(window.location.href);
                if (this.checked) {
                    sizeInputs.forEach(other => { if (other !== this) other.checked = false; });
                    const size = this.getAttribute('data-size');
                    if (size) url.searchParams.set('size', size);
                } else {
                    url.searchParams.delete('size');
                }
                url.searchParams.delete('paged');
                window.location.href = url.toString();
            });
        });

        // Drawer Toggle Logic
        const openDrawerBtn = document.getElementById('mi-open-drawer');
        const closeDrawerBtn = document.getElementById('mi-close-drawer');
        const filterDrawer = document.getElementById('mi-filter-drawer');
        const filterOverlay = document.getElementById('mi-filter-overlay');

        function toggleDrawer() {
            if (!filterDrawer) return;
            const isClosed = filterDrawer.classList.contains('translate-x-full');
            if (isClosed) {
                filterDrawer.classList.remove('translate-x-full');
                filterDrawer.classList.add('translate-x-0');
                if (filterOverlay) filterOverlay.classList.remove('hidden');
                document.body.style.overflow = 'hidden';
            } else {
                filterDrawer.classList.add('translate-x-full');
                filterDrawer.classList.remove('translate-x-0');
                if (filterOverlay) filterOverlay.classList.add('hidden');
                document.body.style.overflow = '';
            }
        }

        if (openDrawerBtn) openDrawerBtn.addEventListener('click', toggleDrawer);
        if (closeDrawerBtn) closeDrawerBtn.addEventListener('click', toggleDrawer);
        if (filterOverlay) filterOverlay.addEventListener('click', toggleDrawer);


    });
    </script>

    <!-- Right Content -->
    <section class="flex-1">
        <?php 
        // Remove notices from the shop loop hook so it doesn't break the flex layout
        remove_action( 'woocommerce_before_shop_loop', 'woocommerce_output_all_notices', 10 );
        ?>
        <!-- Override WooCommerce default notices wrapper to prevent empty margin collapsing bug -->
        <div class="woocommerce-notices-wrapper" style="margin: 0;">
            <?php wc_print_notices(); ?>
        </div>

        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-5 pb-3 border-b border-border-gray gap-4">
            <h1 class="text-xl font-bold uppercase !m-0 !p-0 leading-none pt-1 text-text-main"><?php woocommerce_page_title(); ?></h1>
            <div class="flex items-center justify-between w-full sm:w-auto gap-4">
                <button id="mi-open-drawer" class="lg:hidden flex items-center gap-1 bg-white px-3 py-2 border border-border-gray rounded-lg text-sm font-semibold text-primary hover:bg-background-light transition-colors cursor-pointer shadow-sm">
                    <svg class="w-4 h-4 fill-current" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M3 4c0-.55.45-1 1-1h16c.55 0 1 .45 1 1v2c0 .27-.11.52-.29.71l-6.71 6.71V19c0 .55-.45 1-1 1h-2c-.55 0-1-.45-1-1v-4.59L3.29 6.71C3.11 6.52 3 6.27 3 6V4z"/></svg>
                    LỌC SẢN PHẨM
                </button>
                <div class="mi-order-wrapper flex items-center gap-2 text-xs !m-0 !p-0">
                    <?php do_action( 'woocommerce_before_shop_loop' ); ?>
                </div>
            </div>
        </div>

        <?php if ( $has_active_filters ) : ?>
            <!-- Active Filter Badges -->
            <div class="flex flex-wrap items-center gap-2 mb-4 bg-background-light p-2.5 rounded-lg border border-border-gray text-xs">
                <span class="text-text-muted font-medium">Đang lọc:</span>
                <?php if ( isset( $_GET['min_price'] ) || isset( $_GET['max_price'] ) ) : 
                    $price_lbl = '';
                    if ( $cur_min == 0 && $cur_max == 5000000 ) $price_lbl = 'Dưới 5 triệu';
                    elseif ( $cur_min == 5000000 && $cur_max == 10000000 ) $price_lbl = '5 - 10 triệu';
                    elseif ( $cur_min == 10000000 && $cur_max == 20000000 ) $price_lbl = '10 - 20 triệu';
                    elseif ( $cur_min >= 20000000 && $cur_max == 0 ) $price_lbl = 'Trên 20 triệu';
                    else $price_lbl = number_format( $cur_min, 0, ',', '.' ) . 'đ - ' . number_format( $cur_max, 0, ',', '.' ) . 'đ';
                ?>
                    <span class="inline-flex items-center gap-1.5 bg-white text-secondary border border-secondary/30 px-2.5 py-1 rounded-full font-semibold shadow-xs">
                        Giá: <?php echo esc_html( $price_lbl ); ?>
                        <a href="<?php echo esc_url( remove_query_arg( array( 'min_price', 'max_price', 'paged' ) ) ); ?>" class="hover:text-red-700 font-bold ml-1 text-sm leading-none" title="Bỏ lọc giá">&times;</a>
                    </span>
                <?php endif; ?>

                <?php if ( ! empty( $cur_size ) ) : 
                    $sz_suffix = $is_fridge ? 'L' : ' inch';
                ?>
                    <span class="inline-flex items-center gap-1.5 bg-white text-secondary border border-secondary/30 px-2.5 py-1 rounded-full font-semibold shadow-xs">
                        <?php echo esc_html( $cur_size . $sz_suffix ); ?>
                        <a href="<?php echo esc_url( remove_query_arg( array( 'size', 'paged' ) ) ); ?>" class="hover:text-red-700 font-bold ml-1 text-sm leading-none" title="Bỏ lọc kích thước">&times;</a>
                    </span>
                <?php endif; ?>

                <a href="<?php echo esc_url( $mi_base_url ); ?>" class="text-xs text-secondary hover:underline font-semibold ml-auto">
                    Xóa tất cả
                </a>
            </div>
        <?php endif; ?>

        <!-- Product Grid (WP_Query Standard with Filters & Pagination) -->
        <?php
        $paged = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : ( get_query_var( 'page' ) ? get_query_var( 'page' ) : ( isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1 ) );
        $posts_per_page = isset( $_GET['per_page'] ) ? max( 1, min( 48, absint( $_GET['per_page'] ) ) ) : apply_filters( 'loop_shop_per_page', 12 );

        $grid_args = array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => $posts_per_page,
            'paged'          => $paged,
        );

        // Sorting Logic
        $orderby = isset( $_GET['orderby'] ) ? wc_clean( wp_unslash( $_GET['orderby'] ) ) : apply_filters( 'woocommerce_default_catalog_orderby', get_option( 'woocommerce_default_catalog_orderby', 'menu_order' ) );
        
        switch ( $orderby ) {
            case 'price':
                $grid_args['meta_key'] = '_price';
                $grid_args['orderby']  = 'meta_value_num';
                $grid_args['order']    = 'ASC';
                break;
            case 'price-desc':
                $grid_args['meta_key'] = '_price';
                $grid_args['orderby']  = 'meta_value_num';
                $grid_args['order']    = 'DESC';
                break;
            case 'popularity':
                $grid_args['meta_key'] = 'total_sales';
                $grid_args['orderby']  = 'meta_value_num';
                $grid_args['order']    = 'DESC';
                break;
            case 'rating':
                $grid_args['meta_key'] = '_wc_average_rating';
                $grid_args['orderby']  = 'meta_value_num';
                $grid_args['order']    = 'DESC';
                break;
            case 'date':
                $grid_args['orderby'] = 'date';
                $grid_args['order']   = 'DESC';
                break;
            case 'menu_order':
            default:
                $grid_args['orderby'] = 'menu_order title';
                $grid_args['order']   = 'ASC';
                break;
        }

        $tax_query = array();

        // $current_term đã được khai báo ở đầu file (dùng chung toàn trang)
        if ( isset( $current_term->taxonomy ) && $current_term->taxonomy === 'product_cat' ) {
            $tax_query[] = array(
                'taxonomy'         => 'product_cat',
                'field'            => 'term_id',
                'terms'            => $current_term->term_id,
                'include_children' => true,
            );
        }

        // Lọc theo kích thước — gọi hàm dùng chung từ functions.php (C2 refactor)
        $size_args = mi_build_size_filter_args();
        if ( $size_args['tax_query'] ) {
            $tax_query[] = $size_args['tax_query'];
        } elseif ( $size_args['search'] ) {
            $grid_args['s'] = $size_args['search'];
        }

        if ( ! empty( $tax_query ) ) {
            if ( count( $tax_query ) > 1 ) {
                $tax_query['relation'] = 'AND';
            }
            $grid_args['tax_query'] = $tax_query;
        }

        // Lọc theo khoảng giá — gọi hàm dùng chung từ functions.php (C2 refactor)
        $meta_query = mi_build_price_meta_query();
        if ( ! empty( $meta_query ) ) {
            if ( count( $meta_query ) > 1 ) {
                $meta_query['relation'] = 'AND';
            }
            $grid_args['meta_query'] = $meta_query;
        }

        $grid_query = new WP_Query( $grid_args );

        if ( $grid_query->have_posts() ) :
        ?>
            <!-- Products Grid (2 cols mobile, 3 cols tablet, 4 cols desktop) -->
            <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-4">
                <?php
                while ( $grid_query->have_posts() ) :
                    $grid_query->the_post();
                    wc_get_template_part( 'content', 'product' );
                endwhile;
                wp_reset_postdata();
                ?>
            </div>

            <!-- Dynamic Pagination -->
            <?php
            $total_pages = (int) $grid_query->max_num_pages;
            if ( $total_pages > 1 ) :
            ?>
                <nav class="flex justify-center items-center gap-2 mt-12" aria-label="Phân trang sản phẩm">
                    <?php if ( $paged > 1 ) : ?>
                        <a href="<?php echo esc_url( get_pagenum_link( $paged - 1 ) ); ?>" 
                           class="flex items-center justify-center w-9 h-9 rounded-lg border border-border-gray bg-white text-text-main hover:bg-primary hover:text-white hover:border-primary transition-colors text-xs font-semibold shadow-xs" 
                           title="Trang trước">
                            <span class="material-symbols-outlined !text-sm">chevron_left</span>
                        </a>
                    <?php endif; ?>

                    <?php
                    $range = 2;
                    $show_first_dot = false;
                    $show_last_dot = false;

                    for ( $i = 1; $i <= $total_pages; $i++ ) {
                        if ( $i == 1 || $i == $total_pages || ( $i >= $paged - $range && $i <= $paged + $range ) ) {
                            if ( $i == $paged ) {
                                echo '<span class="flex items-center justify-center w-9 h-9 rounded-lg bg-primary text-white font-bold text-xs shadow-sm" aria-current="page">' . esc_html( $i ) . '</span>';
                            } else {
                                echo '<a href="' . esc_url( get_pagenum_link( $i ) ) . '" class="flex items-center justify-center w-9 h-9 rounded-lg border border-border-gray bg-white text-text-main hover:bg-primary hover:text-white hover:border-primary transition-colors text-xs font-semibold shadow-xs">' . esc_html( $i ) . '</a>';
                            }
                        } elseif ( $i < $paged - $range && ! $show_first_dot ) {
                            echo '<span class="px-1 text-text-muted text-xs">...</span>';
                            $show_first_dot = true;
                        } elseif ( $i > $paged + $range && ! $show_last_dot ) {
                            echo '<span class="px-1 text-text-muted text-xs">...</span>';
                            $show_last_dot = true;
                        }
                    }
                    ?>

                    <?php if ( $paged < $total_pages ) : ?>
                        <a href="<?php echo esc_url( get_pagenum_link( $paged + 1 ) ); ?>" 
                           class="flex items-center justify-center w-9 h-9 rounded-lg border border-border-gray bg-white text-text-main hover:bg-primary hover:text-white hover:border-primary transition-colors text-xs font-semibold shadow-xs" 
                           title="Trang tiếp">
                            <span class="material-symbols-outlined !text-sm">chevron_right</span>
                        </a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        <?php else : ?>
            <div class="p-8 text-center bg-background-light rounded-lg border border-border-gray my-6">
                <span class="material-symbols-outlined text-4xl text-text-muted mb-2">filter_alt_off</span>
                <p class="text-text-main font-semibold mb-1">Không tìm thấy sản phẩm nào phù hợp với bộ lọc đã chọn.</p>
                <p class="text-text-muted text-xs mb-4">Vui lòng thử bỏ chọn một số điều kiện lọc hoặc xem tất cả sản phẩm.</p>
                <a href="<?php echo esc_url( $mi_base_url ); ?>" class="inline-block bg-primary text-white text-xs font-bold px-4 py-2 rounded hover:bg-primary/90 transition-colors">
                    Xóa tất cả bộ lọc
                </a>
            </div>
        <?php endif; ?>

    </section>
</div>

<!-- SEO Content Section -->
<?php 
$page_cat_name = ( isset($current_term->name) ) ? $current_term->name : 'Tivi Xiaomi';
$seo_video_image = ( isset($current_term->taxonomy) && function_exists('get_field') ) ? get_field('cat_seo_image', $current_term) : '';
$seo_video_image = $seo_video_image ?: 'https://lh3.googleusercontent.com/aida-public/AB6AXuCDVYBOJyVGfnmzM92j7ekivl9ozv_1h-0Ihmg0obQQwrBNEAqHzgfBHV8KMPy90yg6FGGRBm4whajuJEOD-fAJZZg3bX_hbpbYIPLg-wJ8PrpaX6iv0IR3vI-So4qmIaVybi9Uv8FAoW6pb0WXaVBebb6fEwAMol9-bHpS0O_kqdW8WO-lsbGe8ZgFkkbMeAoHvP8Uu8Vy04n8_3VqZXeisT89fg1470DmVkXf-5U-VXclMkgIFO06';
$has_custom_desc = ( isset($current_term->taxonomy) && ! empty($current_term->description) );
?>
<style>
    @media (min-width: 1024px) {
        .mi-seo-video-wrapper {
            max-width: 320px !important;
            margin-left: auto;
        }
    }
</style>
<section class="mt-20 p-8 bg-background-light rounded-xl border border-border-gray">
    <h2 class="text-2xl font-bold mb-6"><?php echo esc_html($page_cat_name); ?>: Sự Lựa Chọn Hoàn Hảo Cho Gia Đình Việt</h2>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">
        <div class="space-y-4 text-sm leading-relaxed text-text-main">
            <?php if ( $has_custom_desc ) : ?>
                <div class="prose max-w-none text-text-main">
                    <?php echo wp_kses_post($term->description); ?>
                </div>
            <?php else : ?>
                <p class="font-bold text-primary italic">Tại sao nên chọn <?php echo esc_html($page_cat_name); ?> tại Viomi Việt Nam?</p>
                <p><?php echo esc_html($page_cat_name); ?> đã và đang khẳng định vị thế dẫn đầu trong phân khúc thiết bị thông minh giá rẻ nhưng sở hữu cấu hình "khủng". Với thiết kế tràn viền tinh tế, độ phân giải sắc nét và hệ điều hành thông minh mượt mà, sản phẩm mang đến trải nghiệm giải trí đỉnh cao cho mọi gia đình.</p>
                <div id="mi-seo-more-text" class="hidden space-y-4">
                    <p>Các dòng sản phẩm nổi bật luôn là tâm điểm chú ý nhờ tích hợp các công nghệ tiên tiến nhất như Dolby Audio, HDR10+ và khả năng điều khiển thông minh bằng giọng nói tiếng Việt cực kỳ nhạy bén thông qua hệ sinh thái Xiaomi.</p>
                    <p>Tại hệ thống của chúng tôi, 100% sản phẩm đều được cam kết chính hãng, hỗ trợ giao hàng siêu tốc và chế độ bảo hành tận tâm uy tín hàng đầu trên toàn quốc.</p>
                </div>
                <button id="mi-seo-toggle-btn" type="button" class="text-primary font-bold flex items-center gap-1 cursor-pointer transition-colors hover:underline">
                    <span id="mi-seo-toggle-text">Xem thêm</span> 
                    <span id="mi-seo-toggle-icon" class="material-symbols-outlined transition-transform duration-300">expand_more</span>
                </button>
            <?php endif; ?>
        </div>
        <div class="flex justify-start lg:justify-end w-full">
            <div class="relative rounded-lg overflow-hidden aspect-video group cursor-pointer border-4 border-white shadow-xl w-full mi-seo-video-wrapper">
            <div class="w-full h-full bg-cover bg-center" style="background-image: url('<?php echo esc_url($seo_video_image); ?>')"></div>
            <div class="absolute inset-0 bg-black/20 flex items-center justify-center">
                <div class="w-16 h-16 bg-secondary rounded-full flex items-center justify-center text-white shadow-lg group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-4xl" style="font-variation-settings: 'FILL' 1;">play_arrow</span>
                </div>
            </div>
        </div>
    </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const seoToggleBtn = document.getElementById('mi-seo-toggle-btn');
    const seoMoreText = document.getElementById('mi-seo-more-text');
    const seoToggleText = document.getElementById('mi-seo-toggle-text');
    const seoToggleIcon = document.getElementById('mi-seo-toggle-icon');

    if (seoToggleBtn && seoMoreText) {
        seoToggleBtn.addEventListener('click', function() {
            const isHidden = seoMoreText.classList.contains('hidden');
            if (isHidden) {
                seoMoreText.classList.remove('hidden');
                if (seoToggleText) seoToggleText.textContent = 'Thu gọn';
                if (seoToggleIcon) seoToggleIcon.classList.add('rotate-180');
            } else {
                seoMoreText.classList.add('hidden');
                if (seoToggleText) seoToggleText.textContent = 'Xem thêm';
                if (seoToggleIcon) seoToggleIcon.classList.remove('rotate-180');
            }
        });
    }
});
</script>

<!-- Mobile Filter Drawer -->
<div id="mi-filter-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-[60] lg:hidden hidden"></div>
<div id="mi-filter-drawer" class="fixed inset-y-0 right-0 z-[70] w-80 bg-white shadow-2xl transform translate-x-full transition-transform duration-300 ease-in-out flex flex-col lg:hidden">
    <div class="p-4 border-b border-border-gray flex justify-between items-center bg-background-light shrink-0">
        <span class="font-bold text-lg uppercase text-primary">Lọc Sản Phẩm</span>
        <button id="mi-close-drawer" class="material-symbols-outlined text-2xl text-text-muted hover:text-primary cursor-pointer">close</button>
    </div>
    <div class="p-4 mi-sidebar-accordion flex-1 overflow-y-auto">
        <?php 
        $mobile_sidebar = str_replace('id="mi-filters-accordion"', 'id="mi-filters-accordion-mobile"', $mi_sidebar_content);
        echo $mobile_sidebar; 
        ?>
    </div>
</div>

</main>

<?php get_footer(); ?>

