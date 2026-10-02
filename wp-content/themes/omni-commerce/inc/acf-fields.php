<?php
if ( ! defined( 'ABSPATH' ) ) exit;

add_action('acf/init', function() {
    if( function_exists('acf_add_local_field_group') ) {
        acf_add_local_field_group(array(
            'key' => 'group_mi_product_info',
            'title' => 'Thông tin Sản phẩm Mi',
            'fields' => array(
                array(
                    'key' => 'field_mi_product_gifts',
                    'label' => 'Quà tặng & Khuyến mãi',
                    'name' => 'mi_product_gifts',
                    'type' => 'wysiwyg',
                ),
                array(
                    'key' => 'field_mi_warranty_info',
                    'label' => 'Thông tin Bảo hành',
                    'name' => 'mi_warranty_info',
                    'type' => 'wysiwyg',
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'product',
                    ),
                ),
            ),
        ));
    }
});

add_action('acf/init', function() {
    // Đăng ký Options Page
    if( function_exists('acf_add_options_page') ) {
        acf_add_options_page(array(
            'page_title'    => 'Cấu hình Trang Chủ',
            'menu_title'    => 'Cấu hình Trang Chủ',
            'menu_slug'     => 'mi-theme-home-settings',
            'capability'    => 'edit_posts',
            'redirect'      => false
        ));
    }

    if( function_exists('acf_add_local_field_group') ) {
        // Cấu hình hiện tại (Banners, Videos) - Chuyển sang Options Page
        acf_add_local_field_group(array(
            'key' => 'group_mi_homepage_config',
            'title' => 'Cấu hình Trang chủ (Chung)',
            'fields' => array(
                array(
                    'key' => 'field_mi_tab_banners',
                    'label' => 'Hero Banners (Cũ)',
                    'name' => 'mi_tab_banners',
                    'type' => 'tab',
                ),
                array(
                    'key' => 'field_mi_hero_banner_1',
                    'label' => 'Banner 1',
                    'name' => 'mi_hero_banner_1',
                    'type' => 'image',
                    'return_format' => 'url',
                ),
                array(
                    'key' => 'field_mi_hero_banner_2',
                    'label' => 'Banner 2',
                    'name' => 'mi_hero_banner_2',
                    'type' => 'image',
                    'return_format' => 'url',
                ),
                array(
                    'key' => 'field_mi_tab_videos',
                    'label' => 'Video Nổi Bật',
                    'name' => 'mi_tab_videos',
                    'type' => 'tab',
                ),
                array(
                    'key' => 'field_mi_video_1',
                    'label' => 'YouTube Video ID 1',
                    'name' => 'mi_video_1',
                    'type' => 'text',
                ),
                array(
                    'key' => 'field_mi_video_2',
                    'label' => 'YouTube Video ID 2',
                    'name' => 'mi_video_2',
                    'type' => 'text',
                ),
                array(
                    'key' => 'field_mi_video_3',
                    'label' => 'YouTube Video ID 3',
                    'name' => 'mi_video_3',
                    'type' => 'text',
                ),
                array(
                    'key' => 'field_mi_videos_sale_banner',
                    'label' => 'Banner Giảm Giá (Dòng chữ chạy)',
                    'name' => 'mi_videos_sale_banner',
                    'type' => 'text',
                    'default_value' => 'BIG SALE TIVI XIAOMI 85 INCH CHỈ CÒN 21.990.000Đ',
                ),
                array(
                    'key' => 'field_mi_tab_value_props',
                    'label' => 'Cam Kết Giá Trị',
                    'name' => 'mi_tab_value_props',
                    'type' => 'tab',
                ),
                array(
                    'key' => 'field_mi_value_props',
                    'label' => 'Danh sách Cam Kết',
                    'name' => 'mi_value_props',
                    'type' => 'repeater',
                    'sub_fields' => array(
                        array('key' => 'sub_mi_vp_icon', 'label' => 'Icon (Material Symbol)', 'name' => 'icon', 'type' => 'text'),
                        array('key' => 'sub_mi_vp_title', 'label' => 'Tiêu đề', 'name' => 'title', 'type' => 'text'),
                        array('key' => 'sub_mi_vp_desc', 'label' => 'Mô tả ngắn', 'name' => 'desc', 'type' => 'text'),
                    ),
                ),
                array(
                    'key' => 'field_mi_tab_product_row',
                    'label' => 'Product Row',
                    'name' => 'mi_tab_product_row',
                    'type' => 'tab',
                ),
                array(
                    'key' => 'field_mi_home_product_row',
                    'label' => 'Cấu hình Khối Sản Phẩm',
                    'name' => 'mi_home_product_row',
                    'type' => 'repeater',
                    'sub_fields' => array(
                        array('key' => 'sub_mi_pr_product_cat', 'label' => 'Danh mục Sản phẩm (để lấy SP)', 'name' => 'product_cat', 'type' => 'taxonomy', 'taxonomy' => 'product_cat', 'field_type' => 'select', 'return_format' => 'id', 'allow_null' => 1),
                        array('key' => 'sub_mi_pr_cat_title', 'label' => 'Tiêu đề Khối', 'name' => 'cat_title', 'type' => 'text'),
                        array('key' => 'sub_mi_pr_cat_link', 'label' => 'Link Xem tất cả', 'name' => 'cat_link', 'type' => 'url'),
                        array('key' => 'sub_mi_pr_banner_title', 'label' => 'Tiêu đề Banner', 'name' => 'banner_title', 'type' => 'textarea', 'rows' => 2),
                        array('key' => 'sub_mi_pr_banner_desc', 'label' => 'Mô tả Banner', 'name' => 'banner_desc', 'type' => 'text'),
                        array('key' => 'sub_mi_pr_banner_img', 'label' => 'Ảnh Banner', 'name' => 'banner_img', 'type' => 'image', 'return_format' => 'url'),
                        array('key' => 'sub_mi_pr_banner_btn_text', 'label' => 'Text Nút', 'name' => 'banner_btn_text', 'type' => 'text', 'default_value' => 'XEM NGAY'),
                        array('key' => 'sub_mi_pr_banner_btn_link', 'label' => 'Link Nút', 'name' => 'banner_btn_link', 'type' => 'url'),
                    ),
                ),
                array(
                    'key' => 'field_mi_tab_news_badges',
                    'label' => 'News & Trust Badges',
                    'name' => 'mi_tab_news_badges',
                    'type' => 'tab',
                ),
                array(
                    'key' => 'field_mi_home_news_title',
                    'label' => 'Tiêu đề Tin tức',
                    'name' => 'mi_home_news_title',
                    'type' => 'text',
                    'default_value' => 'Tin tức nổi bật',
                ),
                array(
                    'key' => 'field_mi_trust_badges',
                    'label' => 'Danh sách Trust Badges',
                    'name' => 'mi_trust_badges',
                    'type' => 'repeater',
                    'sub_fields' => array(
                        array('key' => 'sub_mi_tb_icon', 'label' => 'Icon (Ảnh hoặc Text icon)', 'name' => 'icon', 'type' => 'text'),
                        array('key' => 'sub_mi_tb_title', 'label' => 'Tên đối tác / Cam kết', 'name' => 'title', 'type' => 'text'),
                    ),
                ),
                array(
                    'key' => 'field_mi_tab_contact_info',
                    'label' => 'Floating Contact Bar',
                    'name' => 'mi_tab_contact_info',
                    'type' => 'tab',
                ),
                array(
                    'key' => 'field_mi_hf_fab_hotline',
                    'label' => 'Hotline',
                    'name' => 'mi_hf_fab_hotline',
                    'type' => 'text',
                    'default_value' => '0822834444',
                ),
                array(
                    'key' => 'field_mi_hf_fab_messenger',
                    'label' => 'Link Messenger',
                    'name' => 'mi_hf_fab_messenger',
                    'type' => 'url',
                    'default_value' => 'https://m.me/xiaomi',
                ),
                array(
                    'key' => 'field_mi_hf_fab_zalo',
                    'label' => 'Link Zalo',
                    'name' => 'mi_hf_fab_zalo',
                    'type' => 'url',
                    'default_value' => 'https://zalo.me/0822834444',
                ),
                array(
                    'key' => 'field_mi_hf_fab_map',
                    'label' => 'Link Hệ thống Showroom',
                    'name' => 'mi_hf_fab_map',
                    'type' => 'url',
                    'default_value' => '/lien-he',
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'options_page',
                        'operator' => '==',
                        'value' => 'mi-theme-home-settings',
                    ),
                ),
            ),
        ));

        // Group Hero Section
        acf_add_local_field_group(array(
            'key' => 'group_mi_hero_section',
            'title' => 'Hero Section',
            'fields' => array(
                // Tab Features
                array(
                    'key' => 'field_mi_hero_tab_features',
                    'label' => 'Tính năng nổi bật (Trái)',
                    'name' => '',
                    'type' => 'tab',
                ),
                array(
                    'key' => 'field_mi_hero_features',
                    'label' => 'Danh sách tính năng',
                    'name' => 'mi_hero_features',
                    'type' => 'repeater',
                    'sub_fields' => array(
                        array('key' => 'sub_mi_hf_icon', 'label' => 'Icon (Material Symbol)', 'name' => 'icon', 'type' => 'text'),
                        array('key' => 'sub_mi_hf_title', 'label' => 'Tiêu đề', 'name' => 'title', 'type' => 'text'),
                        array('key' => 'sub_mi_hf_desc', 'label' => 'Mô tả', 'name' => 'desc', 'type' => 'text'),
                    ),
                ),
                array(
                    'key' => 'field_mi_hero_address_title',
                    'label' => 'Tiêu đề Địa chỉ',
                    'name' => 'mi_hero_address_title',
                    'type' => 'text',
                    'default_value' => 'ĐỊA CHỈ SHOWROOM',
                ),
                array(
                    'key' => 'field_mi_hero_address_text',
                    'label' => 'Text Địa chỉ',
                    'name' => 'mi_hero_address_text',
                    'type' => 'text',
                    'default_value' => '41 Khuất Duy Tiến, HN',
                ),
                array(
                    'key' => 'field_mi_hero_hotline_title',
                    'label' => 'Tiêu đề Hotline',
                    'name' => 'mi_hero_hotline_title',
                    'type' => 'text',
                    'default_value' => 'HOTLINE HỖ TRỢ 24/7',
                ),
                array(
                    'key' => 'field_mi_hero_hotline_text',
                    'label' => 'Text Hotline',
                    'name' => 'mi_hero_hotline_text',
                    'type' => 'text',
                    'default_value' => '0822.83.4444',
                ),
                // Tab Slider
                array(
                    'key' => 'field_mi_hero_tab_slider',
                    'label' => 'Hero Slider (Giữa)',
                    'name' => '',
                    'type' => 'tab',
                ),
                array(
                    'key' => 'field_mi_hero_slider',
                    'label' => 'Danh sách Slider',
                    'name' => 'mi_hero_slider',
                    'type' => 'repeater',
                    'sub_fields' => array(
                        array('key' => 'sub_mi_hs_image', 'label' => 'Hình ảnh', 'name' => 'image', 'type' => 'image', 'return_format' => 'url'),
                        array('key' => 'sub_mi_hs_link', 'label' => 'Link', 'name' => 'link', 'type' => 'url'),
                    ),
                ),
                // Tab Menu
                array(
                    'key' => 'field_mi_hero_tab_menu',
                    'label' => 'Tab Menu',
                    'name' => '',
                    'type' => 'tab',
                ),
                array(
                    'key' => 'field_mi_hero_tabs',
                    'label' => 'Danh sách Tab',
                    'name' => 'mi_hero_tabs',
                    'type' => 'repeater',
                    'sub_fields' => array(
                        array('key' => 'sub_mi_ht_title', 'label' => 'Tiêu đề', 'name' => 'title', 'type' => 'text'),
                        array('key' => 'sub_mi_ht_link', 'label' => 'Link', 'name' => 'link', 'type' => 'url'),
                        array('key' => 'sub_mi_ht_active', 'label' => 'Active?', 'name' => 'active', 'type' => 'true_false', 'ui' => 1),
                    ),
                ),
                // Tab Sidebar
                array(
                    'key' => 'field_mi_hero_tab_sidebar',
                    'label' => 'Sidebar (Phải)',
                    'name' => '',
                    'type' => 'tab',
                ),
                array(
                    'key' => 'field_mi_hero_sidebar',
                    'label' => 'Thông tin Sidebar',
                    'name' => 'mi_hero_sidebar',
                    'type' => 'group',
                    'sub_fields' => array(
                        array('key' => 'sub_mi_hsb_banner_img', 'label' => 'Ảnh Banner', 'name' => 'banner_image', 'type' => 'image', 'return_format' => 'url'),
                        array('key' => 'sub_mi_hsb_banner_title', 'label' => 'Tiêu đề Banner', 'name' => 'banner_title', 'type' => 'text'),
                        array('key' => 'sub_mi_hsb_news_title', 'label' => 'Tiêu đề Tin tức', 'name' => 'news_title', 'type' => 'text', 'default_value' => 'Tin tức nổi bật'),
                    ),
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'options_page',
                        'operator' => '==',
                        'value' => 'mi-theme-home-settings',
                    ),
                ),
            ),
        ));

        // Group Header & Footer
        acf_add_local_field_group(array(
            'key' => 'group_mi_header_footer',
            'title' => 'Cấu hình Header & Footer',
            'fields' => array(
                array(
                    'key' => 'field_mi_hf_tab_header',
                    'label' => 'Header',
                    'name' => '',
                    'type' => 'tab',
                ),
                array(
                    'key' => 'field_mi_hf_logo',
                    'label' => 'Logo Header',
                    'name' => 'mi_hf_logo',
                    'type' => 'image',
                    'return_format' => 'url',
                ),
                array(
                    'key' => 'field_mi_hf_topbar_text',
                    'label' => 'Text Topbar',
                    'name' => 'mi_hf_topbar_text',
                    'type' => 'text',
                    'default_value' => 'Chào mừng quý khách đến với Xiaomi Store - Hệ thống phân phối chính hãng',
                ),
                array(
                    'key' => 'field_mi_hf_topbar_address',
                    'label' => 'Địa chỉ Topbar',
                    'name' => 'mi_hf_topbar_address',
                    'type' => 'text',
                    'default_value' => 'Số 41 Khuất Duy Tiến, Thanh Xuân, Hà Nội',
                ),
                array(
                    'key' => 'field_mi_hf_topbar_hotline',
                    'label' => 'Hotline Topbar',
                    'name' => 'mi_hf_topbar_hotline',
                    'type' => 'text',
                    'default_value' => '0822.83.4444',
                ),
                array(
                    'key' => 'field_mi_hf_tab_footer',
                    'label' => 'Footer',
                    'name' => '',
                    'type' => 'tab',
                ),
                array(
                    'key' => 'field_mi_hf_footer_about',
                    'label' => 'Giới thiệu (Về Xiaomi Store)',
                    'name' => 'mi_hf_footer_about',
                    'type' => 'textarea',
                    'rows' => 3,
                    'default_value' => 'Chúng tôi là hệ thống phân phối các sản phẩm Xiaomi chính hãng hàng đầu tại Việt Nam, cam kết chất lượng và dịch vụ tốt nhất.',
                ),
                array(
                    'key' => 'field_mi_hf_footer_facebook_html',
                    'label' => 'Mã nhúng Facebook Fanpage',
                    'name' => 'mi_hf_footer_facebook_html',
                    'type' => 'textarea',
                    'rows' => 4,
                ),
                array(
                    'key' => 'field_mi_hf_footer_showrooms',
                    'label' => 'Danh sách Showroom',
                    'name' => 'mi_hf_footer_showrooms',
                    'type' => 'repeater',
                    'sub_fields' => array(
                        array('key' => 'sub_mi_sh_name', 'label' => 'Tên Showroom (VD: Showroom Thanh Xuân)', 'name' => 'name', 'type' => 'text'),
                        array('key' => 'sub_mi_sh_address', 'label' => 'Địa chỉ', 'name' => 'address', 'type' => 'text'),
                        array('key' => 'sub_mi_sh_hotline', 'label' => 'Hotline', 'name' => 'hotline', 'type' => 'text'),
                    ),
                ),
                array(
                    'key' => 'field_mi_hf_footer_copyright',
                    'label' => 'Text Copyright',
                    'name' => 'mi_hf_footer_copyright',
                    'type' => 'text',
                    'default_value' => '© 2024 Xiaomi Store. All Rights Reserved. Thiết kế và vận hành bởi Xiaomi Store Vietnam.',
                ),
                array(
                    'key' => 'field_mi_hf_footer_payment_icons',
                    'label' => 'Các hình thức Thanh toán',
                    'name' => 'mi_hf_footer_payment_icons',
                    'type' => 'repeater',
                    'sub_fields' => array(
                        array('key' => 'sub_mi_pay_img', 'label' => 'Ảnh Icon', 'name' => 'image', 'type' => 'image', 'return_format' => 'url'),
                        array('key' => 'sub_mi_pay_text', 'label' => 'Chữ hiển thị thay thế (Ví dụ: VISA, COD)', 'name' => 'text', 'type' => 'text'),
                    ),
                ),
                array(
                    'key' => 'field_mi_hf_footer_certificate',
                    'label' => 'Ảnh Chứng nhận (Bộ Công Thương...)',
                    'name' => 'mi_hf_footer_certificate',
                    'type' => 'image',
                    'return_format' => 'url',
                ),
                array(
                    'key' => 'field_mi_hf_tab_fab',
                    'label' => 'Nút Liên Hệ Nổi (FAB)',
                    'name' => '',
                    'type' => 'tab',
                ),
                array(
                    'key' => 'field_mi_hf_fab_hotline',
                    'label' => 'Số điện thoại gọi Hotline',
                    'name' => 'mi_hf_fab_hotline',
                    'type' => 'text',
                    'default_value' => '0822834444',
                ),
                array(
                    'key' => 'field_mi_hf_fab_messenger',
                    'label' => 'Link Messenger',
                    'name' => 'mi_hf_fab_messenger',
                    'type' => 'url',
                ),
                array(
                    'key' => 'field_mi_hf_fab_zalo',
                    'label' => 'Link Zalo',
                    'name' => 'mi_hf_fab_zalo',
                    'type' => 'url',
                ),
                array(
                    'key' => 'field_mi_hf_fab_map',
                    'label' => 'Link Chỉ đường (Google Maps)',
                    'name' => 'mi_hf_fab_map',
                    'type' => 'url',
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'options_page',
                        'operator' => '==',
                        'value' => 'mi-theme-home-settings',
                    ),
                ),
            ),
        ));
    }
});

// Đăng ký Menu
function viomi_theme_register_menus() {
    register_nav_menus(
        array(
            'header-menu' => __( 'Header Menu', 'omni-commerce' ),
            'footer-policy' => __( 'Footer Policy Menu', 'omni-commerce' ),
            'category-menu' => __( 'Category Submenu', 'omni-commerce' )
        )
    );
}
