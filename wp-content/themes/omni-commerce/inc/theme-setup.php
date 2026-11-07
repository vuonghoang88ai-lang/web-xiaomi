<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action('init', function() {
    if (get_option('mi_auto_setup_done')) return;
    
    $categories = ['Tivi Xiaomi', 'Tủ Lạnh Xiaomi', 'Thiết Bị Gia Đình'];
    $rows = [];
    
    foreach ($categories as $cat_name) {
        $term = get_term_by('name', $cat_name, 'product_cat');
        if (!$term) {
            $term_info = wp_insert_term($cat_name, 'product_cat');
            if (!is_wp_error($term_info)) {
                $term_id = $term_info['term_id'];
            } else {
                continue;
            }
        } else {
            $term_id = $term->term_id;
        }

        $thumbnail_id = get_term_meta( $term_id, 'thumbnail_id', true );
        $banner_img = $thumbnail_id ? wp_get_attachment_url( $thumbnail_id ) : ( function_exists('wc_placeholder_img_src') ? wc_placeholder_img_src('woocommerce_thumbnail') : content_url('/uploads/banners/banner-01-tivi.jpg') );

        $rows[] = array(
            'product_cat' => $term_id,
            'cat_title' => $cat_name,
            'cat_link' => get_term_link($term_id, 'product_cat'),
            'banner_title' => 'Siêu Phẩm<br/>' . $cat_name,
            'banner_desc' => 'Khám phá các sản phẩm nổi bật',
            'banner_img' => $banner_img,
            'banner_btn_text' => 'MUA NGAY',
            'banner_btn_link' => get_term_link($term_id, 'product_cat')
        );
    }

    if (function_exists('update_field')) {
        update_field('field_mi_home_product_row', $rows, 'option');
        update_option('mi_auto_setup_done', 1);
    }
});

/**
 * Tự động tạo các trang tiêu chuẩn (Tin tức, Liên hệ, Tra cứu bảo hành) nếu chưa tồn tại.
 * Đảm bảo template page-{slug}.php được áp dụng qua WordPress template hierarchy.
 */
function mi_ensure_standard_pages() {
    $standard_pages = [
        'lien-he'          => ['title' => 'Liên Hệ',          'template' => 'page-lien-he.php'],
        'tin-tuc'          => ['title' => 'Tin tức',          'template' => 'page-tin-tuc.php'],
        'tra-cuu-bao-hanh' => ['title' => 'Tra cứu Bảo hành', 'template' => 'page-tra-cuu-bao-hanh.php'],
        'chinh-sach-bao-mat'    => ['title' => 'Chính sách bảo mật',    'template' => 'page.php'],
        'chinh-sach-bao-hanh'   => ['title' => 'Chính sách bảo hành',   'template' => 'page.php'],
        'chinh-sach-doi-tra'    => ['title' => 'Chính sách đổi trả',    'template' => 'page.php'],
        'chinh-sach-van-chuyen' => ['title' => 'Chính sách vận chuyện', 'template' => 'page.php'],
    ];

    $needs_flush = false;

    foreach ($standard_pages as $slug => $data) {
        $page = get_page_by_path($slug);
        if (!$page) {
            $page_id = wp_insert_post([
                'post_title'   => $data['title'],
                'post_name'    => $slug,
                'post_content' => '',
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'post_author'  => 1,
            ]);
            if (!is_wp_error($page_id) && $page_id > 0) {
                update_post_meta($page_id, '_wp_page_template', $data['template']);
                $needs_flush = true;
            }
        } elseif (empty(get_post_meta($page->ID, '_wp_page_template', true))) {
            update_post_meta($page->ID, '_wp_page_template', $data['template']);
        }
    }

    if ($needs_flush) {
        flush_rewrite_rules(false);
    }
}
add_action('init', 'mi_ensure_standard_pages');

/**
 * Helper: lấy URL trang page theo slug, dùng permalink thực nếu page tồn tại.
 */
function mi_get_page_url( $slug ) {
    $page = get_page_by_path( $slug );
    return $page ? get_permalink( $page ) : home_url( '/' . $slug . '/' );
}

/**
 * Tự động tạo Footer Policy Menu và gán vào vị trí footer-policy.
 * Chứa 4 mục: Chính sách bảo mật, bảo hành, đổi trả, vận chuyện.
 */
function mi_setup_footer_policy_menu() {
    $menu_name = 'Footer Policy Menu';
    $menu_obj  = wp_get_nav_menu_object($menu_name);
    $menu_id   = $menu_obj ? $menu_obj->term_id : wp_create_nav_menu($menu_name);

    if (is_wp_error($menu_id) || !$menu_id) {
        return;
    }

    // Gán vào vị trí footer-policy
    $locations = get_theme_mod('nav_menu_locations');
    if (!is_array($locations)) {
        $locations = [];
    }
    if (empty($locations['footer-policy']) || $locations['footer-policy'] != $menu_id) {
        $locations['footer-policy'] = $menu_id;
        set_theme_mod('nav_menu_locations', $locations);
    }

    // Thêm items chính sách nếu chưa có
    $policy_items = [
        ['Chính sách bảo mật',   'chinh-sach-bao-mat'],
        ['Chính sách bảo hành',  'chinh-sach-bao-hanh'],
        ['Chính sách đổi trả',   'chinh-sach-doi-tra'],
        ['Chính sách vận chuyện', 'chinh-sach-van-chuyen'],
    ];

    $existing_titles = [];
    foreach (wp_get_nav_menu_items($menu_id) as $item) {
        $existing_titles[] = $item->title;
    }

    foreach ($policy_items as $item_data) {
        if (!in_array($item_data[0], $existing_titles)) {
            $page    = get_page_by_path($item_data[1]);
            $url     = $page ? get_permalink($page) : home_url('/' . $item_data[1] . '/');
            wp_update_nav_menu_item($menu_id, 0, [
                'menu-item-title'    => $item_data[0],
                'menu-item-url'      => $url,
                'menu-item-status'   => 'publish',
            ]);
        }
    }

    // Fix localhost / Docker IP URLs (override)
    $correct_base = get_option('home');
    foreach (wp_get_nav_menu_items($menu_id) as $item) {
        if (strpos($item->url, 'localhost') !== false
            || strpos($item->url, '127.0.0.1') !== false
            || preg_match('#https?://\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#', $item->url)
        ) {
            wp_update_nav_menu_item($item->ID, [
                'menu-item-url'      => preg_replace('#^https?://[^/]+#', $correct_base, $item->url),
                'menu-item-status'   => 'publish',
            ]);
        }
    }
}
add_action('init', 'mi_setup_footer_policy_menu', 20);

function viomi_theme_enqueue_styles() {
    // Main stylesheet with auto cache busting via filemtime
    $theme_version = file_exists( get_stylesheet_directory() . '/style.css' ) ? filemtime( get_stylesheet_directory() . '/style.css' ) : '1.0.0';
    wp_enqueue_style( 'omni-commerce-style', get_stylesheet_uri(), array(), $theme_version );
    
    // Tải font chữ và icon từ local để tăng tốc website và bảo mật
    wp_enqueue_style( 'local-fonts', get_stylesheet_directory_uri() . '/assets/css/fonts.css', array(), null );

    // Tải script xử lý menu navigation (defer)
    wp_enqueue_script( 'omni-commerce-navigation', get_template_directory_uri() . '/assets/js/navigation.js', array(), $theme_version, array( 'in_footer' => true, 'strategy' => 'defer' ) );

    // Nạp Asset có điều kiện: chỉ enqueue script comment-reply khi cần
    if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
        wp_enqueue_script( 'comment-reply' );
    }

    // CSS khôi phục WP Core Widgets
    wp_enqueue_style( 'omni-commerce-widgets-core', get_stylesheet_directory_uri() . '/assets/css/widgets-core.css', array(), $theme_version );

    // CSS giao diện bài viết chuẩn Gutenberg & Typography
    $single_css_path = get_stylesheet_directory() . '/assets/css/single-post.css';
    if ( file_exists( $single_css_path ) ) {
        wp_enqueue_style( 'omni-single-post', get_stylesheet_directory_uri() . '/assets/css/single-post.css', array( 'omni-commerce-style' ), filemtime( $single_css_path ) );
    }
}
add_action( 'wp_enqueue_scripts', 'viomi_theme_enqueue_styles', 20 );

function omni_commerce_setup() {
    load_theme_textdomain( 'omni-commerce', get_template_directory() . '/languages' );
    add_theme_support( 'woocommerce' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'title-tag' );
    add_theme_support( 'rank-math-breadcrumbs' );
    add_theme_support( 'automatic-feed-links' );
    add_theme_support( 'align-wide' );
    add_theme_support( 'wp-block-styles' );
    add_theme_support( 'responsive-embeds' );
    add_theme_support( 'html5', array(
        'search-form',
        'comment-form',
        'comment-list',
        'gallery',
        'caption',
        'style',
        'script',
    ) );
    add_theme_support( 'custom-logo', array(
        'height'      => 80,
        'width'       => 240,
        'flex-height' => true,
        'flex-width'  => true,
    ) );
    add_theme_support( 'wc-product-gallery-zoom' );
    add_theme_support( 'wc-product-gallery-lightbox' );
    add_theme_support( 'wc-product-gallery-slider' );

    register_nav_menus( array(
        'header-menu'   => __( 'Header Menu', 'omni-commerce' ),
        'footer-policy' => __( 'Footer Policy Menu', 'omni-commerce' ),
        'category-menu' => __( 'Category Submenu', 'omni-commerce' ),
    ) );
    
    global $content_width;
    if ( ! isset( $content_width ) ) {
        $content_width = 1440;
    }

    // Tắt chế độ Coming Soon của WooCommerce
    update_option( 'woocommerce_coming_soon', 'no' );
    update_option( 'woocommerce_store_pages_only', 'no' );
}
add_action( 'after_setup_theme', 'omni_commerce_setup' );

// Đăng ký Sidebar
function viomi_theme_widgets_init() {
    register_sidebar( array(
        'name'          => __( 'Shop Sidebar', 'omni-commerce' ),
        'id'            => 'shop-sidebar',
        'description'   => __( 'Widgets in this area will be shown on WooCommerce category pages.', 'omni-commerce' ),
        'before_widget' => '<div id="%1$s" class="widget %2$s mb-6">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ) );

    register_sidebar( array(
        'name'          => __( 'Blog Sidebar', 'omni-commerce' ),
        'id'            => 'blog-sidebar',
        'description'   => __( 'Widgets in this area will be shown on blog pages.', 'omni-commerce' ),
        'before_widget' => '<div id="%1$s" class="widget %2$s mb-6">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="text-[18px] font-bold text-primary uppercase border-b-2 border-primary pb-2 mb-6">',
        'after_title'   => '</h3>',
    ) );
}
add_action( 'widgets_init', 'viomi_theme_widgets_init' );

/**
 * Update cart count via AJAX
 */
add_filter( 'woocommerce_add_to_cart_fragments', 'viomi_theme_cart_count_fragments', 10, 1 );
function viomi_theme_cart_count_fragments( $fragments ) {
    ob_start();
    ?>
    <span class="cart-contents-count absolute top-0 right-0 bg-secondary text-white text-[10px] w-5 h-5 flex items-center justify-center rounded-full border-2 border-white">
    <?php echo function_exists('WC') ? WC()->cart->get_cart_contents_count() : '0'; ?>
    </span>
    <?php
    $fragments['span.cart-contents-count'] = ob_get_clean();
    return $fragments;
}
require_once get_stylesheet_directory() . '/inc/pagination.php';

// Lưu ý: woocommerce_catalog_orderby được cấu hình tập trung tại hàm mi_custom_catalog_orderby bên dưới.

// Class Walker cho Menu có hỗ trợ Dropdown và Icon Material Symbols (Chuẩn WPCS & AGENTS.md)
class Mi_Header_Menu_Walker extends Walker_Nav_Menu {
    function start_lvl( &$output, $depth = 0, $args = null ) {
        $output .= "<ul class=\"absolute top-full left-0 mt-2 min-w-[200px] bg-white shadow-lg border border-gray-100 rounded-md z-[60] opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 flex flex-col py-2\">\n";
    }

    function end_lvl( &$output, $depth = 0, $args = null ) {
        $output .= "</ul>\n";
    }

    function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
        $classes = empty( $item->classes ) ? array() : (array) $item->classes;
        $classes[] = 'menu-item-' . $item->ID;
        
        if ( in_array('menu-item-has-children', $classes) ) {
            $classes[] = 'group relative';
        }
        
        $class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item, $args, $depth ) );
        $class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';
        
        $output .= '<li' . $class_names . '>';
        
        $atts = array();
        $atts['title']  = ! empty( $item->attr_title ) ? $item->attr_title : '';
        $atts['target'] = ! empty( $item->target )     ? $item->target     : '';
        $atts['rel']    = ! empty( $item->xfn )        ? $item->xfn        : '';
        $atts['href']   = ! empty( $item->url )        ? $item->url        : '';

        if ( $depth === 0 ) {
            $atts['class']  = 'flex flex-col items-center gap-1.5 text-on-surface hover:text-primary font-medium text-[13px] py-2 relative whitespace-nowrap transition-colors';
        } else {
            $atts['class']  = 'block px-4 py-2 text-[14px] text-gray-700 hover:bg-gray-50 hover:text-primary transition-colors whitespace-nowrap text-left w-full';
        }

        $atts = apply_filters( 'nav_menu_link_attributes', $atts, $item, $args, $depth );
        
        $attributes = '';
        foreach ( $atts as $attr => $value ) {
            if ( ! empty( $value ) ) {
                $value = ( 'href' === $attr ) ? esc_url( $value ) : esc_attr( $value );
                $attributes .= ' ' . $attr . '="' . $value . '"';
            }
        }
        
        $title = apply_filters( 'the_title', $item->title, $item->ID );
        
        $item_output = $args->before ?? '';
        $item_output .= '<a'. $attributes .'>';
        
        if ( $depth === 0 ) {
            $icon = ! empty( $item->description ) ? esc_html( $item->description ) : 'tv';
            $item_output .= '<span class="material-symbols-outlined text-[26px] text-outline">' . $icon . '</span>';
        }
        
        $item_output .= '<span>' . ($args->link_before ?? '') . $title . ($args->link_after ?? '') . '</span>';
        $item_output .= '</a>';
        $item_output .= $args->after ?? '';
        
        $output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
    }

    function end_el( &$output, $item, $depth = 0, $args = null ) {
        $output .= "</li>\n";
    }
}
class_alias( 'Mi_Header_Menu_Walker', 'Viomi_Header_Menu_Walker' );

/**
 * Lấy URL của danh mục sản phẩm hoặc archive fallback
 */
function mi_get_category_or_archive_url( $slug, $name = '' ) {
    $term = get_term_by( 'slug', $slug, 'product_cat' );
    if ( ! $term && ! empty( $name ) ) {
        $term = get_term_by( 'name', $name, 'product_cat' );
    }
    if ( $term && ! is_wp_error( $term ) ) {
        return get_term_link( $term, 'product_cat' );
    }
    return get_post_type_archive_link( 'product' );
}

/**
 * Dùng chung cho các banner danh mục sản phẩm để tránh hardcode lặp lại và rời rạc.
 */
function mi_get_product_category_banner_set( $term = null ) {
    $slug = is_object( $term ) && ! empty( $term->slug ) ? strtolower( $term->slug ) : '';

    if ( false !== strpos( $slug, 'tu-lanh' ) ) {
        return array(
            wp_get_attachment_url( 187 ) ?: content_url( 'uploads/banners/cat-tulanh-banner-1.jpg' ),
            wp_get_attachment_url( 188 ) ?: content_url( 'uploads/banners/cat-tulanh-banner-2.jpg' ),
            wp_get_attachment_url( 189 ) ?: content_url( 'uploads/banners/cat-tulanh-banner-3.jpg' ),
        );
    }

    if ( false !== strpos( $slug, 'tivi' ) || false !== strpos( $slug, 'tv' ) ) {
        return array(
            wp_get_attachment_url( 184 ) ?: content_url( 'uploads/banners/cat-tivi-banner-1.jpg' ),
            wp_get_attachment_url( 185 ) ?: content_url( 'uploads/banners/cat-tivi-banner-2.jpg' ),
            wp_get_attachment_url( 186 ) ?: content_url( 'uploads/banners/cat-tivi-banner-3.jpg' ),
        );
    }

    return array(
        wp_get_attachment_url( 190 ) ?: content_url( 'uploads/banners/cat-giadinh-banner-1.jpg' ),
        wp_get_attachment_url( 191 ) ?: content_url( 'uploads/banners/cat-giadinh-banner-2.jpg' ),
        wp_get_attachment_url( 192 ) ?: content_url( 'uploads/banners/cat-giadinh-banner-3.jpg' ),
    );
}

/**
 * Render Header Navigation tuân thủ 100% cấu trúc HTML homepage.html theo AGENTS.md
 */
function mi_render_header_navigation( $is_mobile = false ) {
    $menu_items_data = array();

    // 1. Kiểm tra xem vị trí 'header-menu' đã được gán menu trong WP Admin chưa
    if ( has_nav_menu( 'header-menu' ) ) {
        $locations = get_nav_menu_locations();
        if ( ! empty( $locations['header-menu'] ) ) {
            $wp_items = wp_get_nav_menu_items( $locations['header-menu'] );
            if ( ! empty( $wp_items ) ) {
                $parents = array();
                foreach ( $wp_items as $item ) {
                    if ( empty( $item->menu_item_parent ) || $item->menu_item_parent == 0 ) {
                        $icon = ! empty( $item->description ) ? $item->description : '';
                        if ( empty( $icon ) ) {
                            $t = mb_strtolower( $item->title, 'UTF-8' );
                            if ( strpos( $t, 'tivi' ) !== false || strpos( $t, 'tv' ) !== false ) {
                                $icon = 'tv';
                            } elseif ( strpos( $t, 'lạnh' ) !== false ) {
                                $icon = 'kitchen';
                            } elseif ( strpos( $t, 'gia đình' ) !== false || strpos( $t, 'thiết bị' ) !== false ) {
                                $icon = 'home_iot_device';
                            } elseif ( strpos( $t, 'liên hệ' ) !== false ) {
                                $icon = 'contact_support';
                            } elseif ( strpos( $t, 'tin tức' ) !== false ) {
                                $icon = 'newspaper';
                            } elseif ( strpos( $t, 'bảo hành' ) !== false || strpos( $t, 'tra cứu' ) !== false ) {
                                $icon = 'verified_user';
                            } else {
                                $icon = 'category';
                            }
                        }
                        $parents[$item->ID] = array(
                            'title'    => $item->title,
                            'url'      => $item->url,
                            'icon'     => $icon,
                            'children' => array(),
                        );
                    }
                }
                foreach ( $wp_items as $item ) {
                    if ( ! empty( $item->menu_item_parent ) && $item->menu_item_parent != 0 ) {
                        $parent_id = $item->menu_item_parent;
                        if ( isset( $parents[$parent_id] ) ) {
                            $parents[$parent_id]['children'][] = array(
                                'title' => $item->title,
                                'url'   => $item->url,
                            );
                        }
                    }
                }
                $menu_items_data = array_values( $parents );
            }
        }
    }

    // 2. Fallback: Nếu chưa gán menu trong WP Admin (khi chạy container mới/khởi tạo DB),
    // render đúng 5 mục chuẩn của Xiaomi Store theo homepage.html
    if ( empty( $menu_items_data ) ) {
        $contact_page = get_page_by_path( 'lien-he' );
        $news_page    = get_page_by_path( 'tin-tuc' );

        $menu_items_data = array(
            array(
                'title' => 'Tivi Xiaomi',
                'icon'  => 'tv',
                'url'   => mi_get_category_or_archive_url( 'tivi-xiaomi', 'Tivi Xiaomi' ),
            ),
            array(
                'title' => 'Tủ Lạnh Xiaomi',
                'icon'  => 'kitchen',
                'url'   => mi_get_category_or_archive_url( 'tu-lanh-xiaomi', 'Tủ Lạnh Xiaomi' ),
            ),
            array(
                'title' => 'Thiết Bị Gia Đình',
                'icon'  => 'home_iot_device',
                'url'   => mi_get_category_or_archive_url( 'thiet-bi-gia-dinh', 'Thiết Bị Gia Đình' ),
            ),
            array(
                'title' => 'Liên hệ',
                'icon'  => 'contact_support',
                'url'   => $contact_page ? get_permalink( $contact_page ) : home_url( '/lien-he/' ),
            ),
            array(
                'title' => 'Tin tức',
                'icon'  => 'newspaper',
                'url'   => $news_page ? get_permalink( $news_page ) : home_url( '/tin-tuc/' ),
            ),
        );
    }

    // 3. Render HTML
    foreach ( $menu_items_data as $item ) {
        if ( $is_mobile ) {
            ?>
            <a class="flex items-center gap-2 p-2.5 rounded-lg bg-surface hover:bg-primary/10 text-on-surface hover:text-primary transition-colors text-label-sm font-medium" href="<?php echo esc_url( $item['url'] ); ?>">
                <span class="material-symbols-outlined text-[22px] text-primary"><?php echo esc_html( $item['icon'] ); ?></span>
                <span class="truncate"><?php echo esc_html( $item['title'] ); ?></span>
            </a>
            <?php
        } else {
            // Giữ nguyên 100% cấu trúc thẻ, class của homepage.html (Mục 4 AGENTS.md)
            // Có bổ sung Dropdown Submenu
            $has_children = ! empty( $item['children'] );
            ?>
            <div class="relative group">
                <a class="flex flex-col items-center gap-1.5 text-on-surface group-hover:text-primary font-medium text-[13px] transition-all duration-300 whitespace-nowrap relative py-2 before:content-[''] before:absolute before:bottom-0 before:left-1/2 before:-translate-x-1/2 before:w-0 before:h-[3px] before:bg-primary before:transition-all before:duration-300 group-hover:before:w-[80%] before:rounded-t-md" href="<?php echo esc_url( $item['url'] ); ?>">
                    <span class="material-symbols-outlined text-[26px] text-outline group-hover:text-primary group-hover:-translate-y-1 transition-all duration-300"><?php echo esc_html( $item['icon'] ); ?></span>
                    <span><?php echo esc_html( $item['title'] ); ?></span>
                </a>
                <?php if ( $has_children ) : ?>
                <div class="absolute top-full left-1/2 -translate-x-1/2 pt-2 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 z-50">
                    <ul class="bg-white border border-outline-variant shadow-lg rounded-lg py-2 min-w-[200px] flex flex-col relative before:content-[''] before:absolute before:-top-[8px] before:left-1/2 before:-translate-x-1/2 before:border-[8px] before:border-transparent before:border-b-white filter drop-shadow-sm">
                        <?php foreach ( $item['children'] as $child ) : ?>
                        <li>
                            <a href="<?php echo esc_url( $child['url'] ); ?>" class="block px-4 py-2.5 text-[14px] text-on-surface hover:bg-primary/5 hover:text-primary transition-colors border-b border-outline-variant last:border-b-0">
                                <span class="inline-block transition-transform duration-200 hover:translate-x-1"><?php echo esc_html( $child['title'] ); ?></span>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
            <?php
        }
    }
}

// Tự động gán và cấu hình Menu trong môi trường Container

add_action('init', function() {
    $menu_name = 'Header Menu';
    $menu_exists = wp_get_nav_menu_object($menu_name);

    if (!$menu_exists) {
        $menu_id = wp_create_nav_menu($menu_name);
    } else {
        $menu_id = $menu_exists->term_id;
    }

    if (!is_wp_error($menu_id) && $menu_id > 0) {
        $locations = get_theme_mod('nav_menu_locations');
        if (!is_array($locations)) {
            $locations = [];
        }

        // Luôn gán Header Menu vào vị trí header-menu (override nếu đã gán sai menu khác — BUG: header-menu bị gán nhầm vào Main Menu ID 2)
        if (empty($locations['header-menu']) || $locations['header-menu'] != $menu_id) {
            $locations['header-menu'] = $menu_id;
            set_theme_mod('nav_menu_locations', $locations);
        }

        // Thêm các mục menu chuẩn còn thiếu (check từng item để tránh duplicate)
        $existing_items = wp_get_nav_menu_items($menu_id);
        $existing_titles = array_map(function($i) { return $i->title; }, $existing_items);
        $standard_items = [
            ['Tivi Xiaomi',          'tv',                mi_get_category_or_archive_url('tivi-xiaomi', 'Tivi Xiaomi')],
            ['Tủ Lạnh Xiaomi',       'kitchen',           mi_get_category_or_archive_url('tu-lanh-xiaomi', 'Tủ Lạnh Xiaomi')],
            ['Thiết Bị Gia Đình',    'home_iot_device',   mi_get_category_or_archive_url('thiet-bi-gia-dinh', 'Thiết Bị Gia Đình')],
            ['Liên hệ',              'contact_support',   (get_page_by_path('lien-he') ? get_permalink(get_page_by_path('lien-he')) : home_url('/lien-he/'))],
            ['Tin tức',              'newspaper',         (get_page_by_path('tin-tuc') ? get_permalink(get_page_by_path('tin-tuc')) : home_url('/tin-tuc/'))],
            ['Tra cứu bảo hành',     'verified_user',     (get_page_by_path('tra-cuu-bao-hanh') ? get_permalink(get_page_by_path('tra-cuu-bao-hanh')) : home_url('/tra-cuu-bao-hanh/'))],
        ];
        foreach ($standard_items as $item) {
            if (!in_array($item[0], $existing_titles)) {
                wp_update_nav_menu_item($menu_id, 0, [
                    'menu-item-title'       => $item[0],
                    'menu-item-description' => $item[1],
                    'menu-item-url'         => $item[2],
                    'menu-item-status'      => 'publish',
                ]);
            }
        }

        // Fix: Ensure menu item URLs use domain from DB, not localhost/IP
        // WP_HOME/WP_SITEURL is overridden via $_SERVER['HTTP_HOST'] in Docker → URLs may use localhost or IP
        $correct_base = get_option('home');
        $menu_items = wp_get_nav_menu_items($menu_id);
        foreach ($menu_items as $item) {
            if (strpos($item->url, 'localhost') !== false
                || strpos($item->url, '127.0.0.1') !== false
                || preg_match('#https?://\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#', $item->url)
            ) {
                $new_url = preg_replace('#^https?://[^/]+#', $correct_base, $item->url);
                wp_update_nav_menu_item($item->ID, [
                    'menu-item-url' => $new_url,
                    'menu-item-status' => 'publish',
                ]);
            }
        }
    }
});

// Fallback Site Icon & Favicon cho TongKhoMi.com
add_filter( 'get_site_icon_url', function( $url, $size, $blog_id ) {
    if ( empty( $url ) ) {
        if ( $size <= 32 ) {
            return get_theme_file_uri( '/assets/images/favicon.png' );
        }
        return get_theme_file_uri( '/assets/images/tongkhomi-icon.png' );
    }
    return $url;
}, 10, 3 );

add_filter( 'has_site_icon', function( $has_icon ) {
    return true;
} );

// ---------------------------------------------------------------------------
// Helper: Safe product thumbnail — checks file exists on disk before output.
// Prevents 404s when featured image metadata exists but source file is missing.
// ---------------------------------------------------------------------------
function mi_safe_product_thumbnail( $post_id, $size = 'woocommerce_thumbnail', $attr = array() ) {
    $post_id = $post_id ?: get_the_ID();
    $thumbnail_id = get_post_thumbnail_id( $post_id );

    // No featured image set → use WooCommerce placeholder
    if ( ! $thumbnail_id ) {
        $placeholder = wc_placeholder_img_src( $size );
        $default_attr = wp_parse_args( $attr, array(
            'class'  => 'object-contain w-full h-full',
            'loading' => 'lazy',
        ) );
        return '<img class="' . esc_attr( $default_attr['class'] ) . '" src="' . esc_url( $placeholder ) . '" alt="Placeholder" loading="lazy" />';
    }

    // Get image src for the requested size
    $image_src = wp_get_attachment_image_src( $thumbnail_id, $size );
    if ( ! $image_src ) {
        return '';
    }

    // Verify the actual file exists on disk
    $upload_dir = wp_upload_dir();
    $file_path = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $image_src[0] );

    if ( file_exists( $file_path ) ) {
        $merged_attr = wp_parse_args( $attr, array(
            'class'  => 'object-contain w-full h-full',
            'loading' => 'lazy',
        ) );
        return wp_get_attachment_image( $thumbnail_id, $size, false, $merged_attr );
    }

    // File missing — fall back to WooCommerce placeholder
    $placeholder = wc_placeholder_img_src( $size );
    $fallback_attr = wp_parse_args( $attr, array(
        'class'  => 'object-contain w-full h-full',
        'loading' => 'lazy',
    ) );
    return '<img class="' . esc_attr( $fallback_attr['class'] ) . '" src="' . esc_url( $placeholder ) . '" alt="Placeholder" loading="lazy" />';
}

// ---------------------------------------------------------------------------
// Permissions-Policy header sanitisation
// Removes browser-unrecognised features to suppress console warnings.
// ---------------------------------------------------------------------------
function mi_sanitize_permissions_policy_header( $headers ) {
    if ( ! isset( $headers['Permissions-Policy'] ) ) {
        return $headers;
    }
    $policy = $headers['Permissions-Policy'];
    // Strip features modern browsers (Chrome 130+) don't recognise yet
    $unrecognised = array(
        'attribution-reporting',
        'shared-storage',
        'shared-storage-select-url',
    );
    foreach ( $unrecognised as $feature ) {
        $policy = preg_replace(
            '/\s*' . preg_quote( $feature, '/' ) . '=[^,(]+(,\s*)?/',
            '',
            $policy
        );
    }
    $headers['Permissions-Policy'] = trim( $policy );
    return $headers;
}
add_filter( 'wp_headers', 'mi_sanitize_permissions_policy_header', 20 );
