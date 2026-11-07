<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
 get_header(); ?>

<main id="primary" class="overflow-x-clip">
    <?php
    if ( ! defined( 'OMNI_BRIDGE_H1_RENDERED' ) ) {
        define( 'OMNI_BRIDGE_H1_RENDERED', true );
    }
    $h1_text = get_option( 'omni_bridge_homepage_h1' );
    if ( empty( $h1_text ) ) {
        $h1_text = get_bloginfo( 'description' ) ?: get_bloginfo( 'name' );
    }
    ?>
    <h1 style="position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border-width: 0;"><?php echo esc_html( $h1_text ); ?></h1>

<?php get_template_part( 'template-parts/home/hero' ); ?>

<?php get_template_part( 'template-parts/home/media-services' ); ?>

<div id="product-row-section">
<style>
/* Chuẩn responsive theme: Ẩn cột banner trên mobile/tablet (< 900px), hiển thị 100% trên Desktop (>= 900px) */
@media (max-width: 899px) {
    .mi-product-row-banner {
        display: none !important;
    }
}
</style>
<?php 
// 3 danh mục sản phẩm hiển thị trên trang chủ theo yêu cầu nghiệp vụ
$home_categories = [
    [
        'cat_title'       => __( 'Tivi Xiaomi', 'omni-commerce' ),
        'slug'            => 'tivi-xiaomi',
        'banner_title'    => __( 'Siêu Phẩm<br/>Tivi Xiaomi', 'omni-commerce' ),
        'banner_desc'     => __( 'Màn hình vô cực, trải nghiệm điện ảnh tại gia', 'omni-commerce' ),
        'banner_bg_color' => 'from-xiaomi-yellow to-tertiary-fixed',
        'banner_btn_text' => __( 'MUA NGAY', 'omni-commerce' ),
        'banner_img'      => content_url( 'uploads/banners/left-col-tivi.jpg' ),
        'search_keywords' => ['tivi', 'tv', 'redmi'],
    ],
    [
        'cat_title'       => __( 'Tủ Lạnh Xiaomi', 'omni-commerce' ),
        'slug'            => 'tu-lanh-xiaomi',
        'banner_title'    => __( 'Đẳng Cấp<br/>Tủ Lạnh Xiaomi', 'omni-commerce' ),
        'banner_desc'     => __( 'Bảo quản tươi ngon, công nghệ làm lạnh kép', 'omni-commerce' ),
        'banner_bg_color' => 'from-cyan-500 to-blue-600',
        'banner_btn_text' => __( 'KHÁM PHÁ', 'omni-commerce' ),
        'banner_img'      => content_url( 'uploads/banners/left-col-tu-lanh.jpg' ),
        'search_keywords' => ['tủ lạnh', 'tu lanh', 'tủ', '430l'],
    ],
    [
        'cat_title'       => __( 'Thiết Bị Gia Đình', 'omni-commerce' ),
        'slug'            => 'thiet-bi-gia-dinh',
        'banner_title'    => __( 'Hệ Sinh Thái<br/>Thiết Bị Gia Đình', 'omni-commerce' ),
        'banner_desc'     => __( 'Tiện nghi thông minh, giải phóng sức lao động', 'omni-commerce' ),
        'banner_bg_color' => 'from-amber-500 to-orange-600',
        'banner_btn_text' => __( 'XEM NGAY', 'omni-commerce' ),
        'banner_img'      => content_url( 'uploads/banners/left-col-gia-dinh.jpg' ),
        'search_keywords' => ['gia đình', 'robot', 'hút bụi', 'máy lọc', 'quạt', 'nồi'],
    ],
];

// Lấy dữ liệu tùy biến từ ACF nếu có
$acf_rows = array();
if ( function_exists( 'get_field' ) ) {
    $acf_data = get_field( 'mi_home_product_row', 'option' );
    if ( empty( $acf_data ) ) {
        $acf_data = get_field( 'mi_home_product_rows', 'option' );
    }
    if ( is_array( $acf_data ) ) {
        $acf_rows = $acf_data;
    }
}

foreach ( $home_categories as $index => $cat_def ) {
    $row_args = $cat_def;

    // 1. Tìm term_id của danh mục
    $term = get_term_by( 'slug', $cat_def['slug'], 'product_cat' );
    if ( ! $term || is_wp_error( $term ) ) {
        $term = get_term_by( 'name', $cat_def['cat_title'], 'product_cat' );
    }
    if ( ! $term || is_wp_error( $term ) ) {
        if ( $cat_def['slug'] === 'tu-lanh-xiaomi' ) {
            $term = get_term_by( 'name', 'Tủ lạnh Xiaomi', 'product_cat' );
        }
    }

    // Tự động tạo term nếu chưa có trong hệ thống
    if ( ( ! $term || is_wp_error( $term ) ) && function_exists( 'wp_insert_term' ) ) {
        $created = wp_insert_term( $cat_def['cat_title'], 'product_cat', [ 'slug' => $cat_def['slug'] ] );
        if ( ! is_wp_error( $created ) && isset( $created['term_id'] ) ) {
            $term = get_term( $created['term_id'], 'product_cat' );
        }
    }

    $term_id = ( $term && ! is_wp_error( $term ) ) ? $term->term_id : 0;
    $term_link = ( $term && ! is_wp_error( $term ) ) ? get_term_link( $term ) : home_url( '/danh-muc/' . $cat_def['slug'] . '/' );
    if ( is_wp_error( $term_link ) ) {
        $term_link = '#';
    }

    $row_args['product_cat']     = $term_id;
    $row_args['cat_link']        = $term_link;
    $row_args['banner_btn_link'] = $term_link;

    // Lấy thumbnail của term nếu có
    if ( $term_id ) {
        $thumb_id = get_term_meta( $term_id, 'thumbnail_id', true );
        if ( $thumb_id ) {
            $row_args['banner_img'] = wp_get_attachment_url( $thumb_id );
        }
    }

    // 2. Kế thừa các tùy biến từ ACF nếu admin có cấu hình riêng (tránh bị trùng tên Tivi ở hàng 2 và 3)
    if ( isset( $acf_rows[ $index ] ) && is_array( $acf_rows[ $index ] ) ) {
        $custom = $acf_rows[ $index ];
        if ( ! empty( $custom['banner_img'] ) ) {
            $acf_img = is_array( $custom['banner_img'] ) ? $custom['banner_img']['url'] : $custom['banner_img'];
            if ( stripos( $acf_img, 'placehold.co' ) === false ) {
                $row_args['banner_img'] = $acf_img;
            }
        }
        if ( ! empty( $custom['banner_btn_text'] ) ) {
            $row_args['banner_btn_text'] = $custom['banner_btn_text'];
        }
        if ( ! empty( $custom['cat_title'] ) ) {
            // Không áp dụng tên Tivi cho hàng Tủ Lạnh hoặc Thiết bị gia đình
            if ( $index === 0 || stripos( $custom['cat_title'], 'tivi' ) === false ) {
                $row_args['cat_title'] = $custom['cat_title'];
            }
        }
        if ( ! empty( $custom['banner_title'] ) ) {
            if ( $index === 0 || stripos( $custom['banner_title'], 'tivi' ) === false ) {
                $row_args['banner_title'] = $custom['banner_title'];
            }
        }
        if ( ! empty( $custom['banner_desc'] ) ) {
            $row_args['banner_desc'] = $custom['banner_desc'];
        }
    }

    // Render component cho từng danh mục riêng biệt
    get_template_part( 'template-parts/home/product-row', null, $row_args );
}
?>
</div>

<?php get_template_part( 'template-parts/home/news' ); ?>

</main>

<?php get_footer(); ?>
