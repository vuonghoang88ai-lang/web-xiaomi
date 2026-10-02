<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action('init', function() {
    if (get_option('mi_auto_setup_done')) return;
    
    $categories = ['Tivi Xiaomi', 'Tủ Lạnh Xiaomi', 'Thiết bị gia đình'];
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
        $banner_img = $thumbnail_id ? wp_get_attachment_url( $thumbnail_id ) : 'https://placehold.co/400x400?text=' . urlencode($cat_name);

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

function viomi_theme_enqueue_styles() {
    wp_enqueue_style( 'omni-commerce-style', get_template_directory_uri() . '/style.css' );
    
    // Auto cache busting for main style.css
    $theme_version = filemtime( get_stylesheet_directory() . '/style.css' );
    wp_enqueue_style( 'omni-commerce-css', get_stylesheet_uri(), array('omni-commerce-style'), $theme_version );
    
    $tailwind_version = time();
    wp_enqueue_style( 'tailwind-css', get_stylesheet_directory_uri() . '/assets/css/tailwind.css', array(), $tailwind_version );
    
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
}
add_action( 'wp_enqueue_scripts', 'viomi_theme_enqueue_styles', 20 );

function omni_commerce_setup() {
    load_theme_textdomain( 'omni-commerce', get_template_directory() . '/languages' );
    add_theme_support( 'woocommerce' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'title-tag' );
    add_theme_support( 'automatic-feed-links' );
    add_theme_support( 'align-wide' );
    add_theme_support( 'wp-block-styles' );
    add_theme_support( 'wc-product-gallery-zoom' );
    add_theme_support( 'wc-product-gallery-lightbox' );
    add_theme_support( 'wc-product-gallery-slider' );
    
    global $content_width;
    if ( ! isset( $content_width ) ) {
        $content_width = 1440;
    }

    // Tắt chế độ Coming Soon của WooCommerce
    update_option( 'woocommerce_coming_soon', 'no' );
    update_option( 'woocommerce_store_pages_only', 'no' );
}
add_action( 'after_setup_theme', 'omni_commerce_setup' );


add_action( 'init', 'viomi_theme_register_menus' );

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
            'https://images.unsplash.com/photo-1585518419759-7fe2e0fbf8a6?auto=format&fit=crop&w=900&q=80',
            'https://images.unsplash.com/photo-1556911220-bff31c812dba?auto=format&fit=crop&w=900&q=80',
            'https://images.unsplash.com/photo-1599599810769-bcde5a160d32?auto=format&fit=crop&w=900&q=80',
        );
    }

    if ( false !== strpos( $slug, 'tivi' ) || false !== strpos( $slug, 'tv' ) ) {
        return array(
            'https://images.unsplash.com/photo-1593359677879-a4bb92f829d1?auto=format&fit=crop&w=900&q=80',
            'https://images.unsplash.com/photo-1549187774-b4e9b0445b41?auto=format&fit=crop&w=900&q=80',
            'https://images.unsplash.com/photo-1497493292307-31c376b6e479?auto=format&fit=crop&w=900&q=80',
        );
    }

    return array(
        'https://images.unsplash.com/photo-1518770660439-4636190af475?auto=format&fit=crop&w=900&q=80',
        'https://images.unsplash.com/photo-1556656793-08538906a9f8?auto=format&fit=crop&w=900&q=80',
        'https://images.unsplash.com/photo-1524758631624-e2822e304c36?auto=format&fit=crop&w=900&q=80',
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
                'title' => 'Thiết bị gia đình',
                'icon'  => 'home_iot_device',
                'url'   => mi_get_category_or_archive_url( 'thiet-bi-gia-dinh', 'Thiết bị gia đình' ),
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
    $locations = get_theme_mod('nav_menu_locations');
    if (is_array($locations) && !empty($locations['header-menu'])) {
        return; // Đã gán vị trí header-menu
    }

    $menu_name = 'Header Menu';
    $menu_exists = wp_get_nav_menu_object($menu_name);

    if (!$menu_exists) {
        $menu_id = wp_create_nav_menu($menu_name);
    } else {
        $menu_id = $menu_exists->term_id;
    }

    if (!is_wp_error($menu_id) && $menu_id > 0) {
        if (!is_array($locations)) {
            $locations = [];
        }
        $locations['header-menu'] = $menu_id;
        set_theme_mod('nav_menu_locations', $locations);
    }
});
