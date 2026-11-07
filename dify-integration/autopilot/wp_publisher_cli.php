<?php
/**
 * TongKhoMi WordPress Direct Publisher CLI
 * Nhận payload JSON qua stdin và tạo bài viết WordPress chuẩn Rank Math SEO.
 */

require_once '/var/www/html/wp-load.php';

$raw_input = file_get_contents( 'php://stdin' );
if ( empty( $raw_input ) ) {
    echo json_encode( array( 'success' => false, 'error' => 'Empty input payload' ) );
    exit( 1 );
}

$data = json_decode( $raw_input, true );
if ( ! is_array( $data ) || empty( $data['title'] ) || empty( $data['content'] ) ) {
    echo json_encode( array( 'success' => false, 'error' => 'Missing title or content' ) );
    exit( 1 );
}

$title          = sanitize_text_field( $data['title'] );
$content        = wp_kses_post( $data['content'] );
$status         = ! empty( $data['status'] ) && in_array( $data['status'], array( 'publish', 'draft' ) ) ? $data['status'] : 'draft';
$category_name  = ! empty( $data['category'] ) ? sanitize_text_field( $data['category'] ) : 'Tin tức';
$focus_keyword  = ! empty( $data['focus_keyword'] ) ? sanitize_text_field( $data['focus_keyword'] ) : '';
$seo_title      = ! empty( $data['seo_title'] ) ? sanitize_text_field( $data['seo_title'] ) : $title;
$meta_desc      = ! empty( $data['meta_description'] ) ? sanitize_text_field( $data['meta_description'] ) : '';
$slug           = ! empty( $data['slug'] ) ? sanitize_title( $data['slug'] ) : sanitize_title( $title );

// Tìm hoặc tạo category
$cat_id = 0;
$term   = get_term_by( 'name', $category_name, 'category' );
if ( $term && ! is_wp_error( $term ) ) {
    $cat_id = $term->term_id;
} else {
    $new_cat = wp_insert_term( $category_name, 'category' );
    if ( ! is_wp_error( $new_cat ) ) {
        $cat_id = $new_cat['term_id'];
    }
}

$post_data = array(
    'post_title'    => $title,
    'post_content'  => $content,
    'post_status'   => $status,
    'post_name'     => $slug,
    'post_type'     => 'post',
    'post_author'   => 1,
    'post_category' => $cat_id ? array( $cat_id ) : array(),
);

$post_id = wp_insert_post( $post_data );

if ( is_wp_error( $post_id ) || empty( $post_id ) ) {
    echo json_encode( array(
        'success' => false,
        'error'   => is_wp_error( $post_id ) ? $post_id->get_error_message() : 'Failed to insert post',
    ) );
    exit( 1 );
}

// Cập nhật Rank Math SEO Meta
if ( ! empty( $focus_keyword ) ) {
    update_post_meta( $post_id, 'rank_math_focus_keyword', $focus_keyword );
}
if ( ! empty( $seo_title ) ) {
    update_post_meta( $post_id, 'rank_math_title', $seo_title );
}
if ( ! empty( $meta_desc ) ) {
    update_post_meta( $post_id, 'rank_math_description', $meta_desc );
}

$site_url = home_url();
$preview_url = $site_url . '/?p=' . $post_id . '&preview=true';
$permalink   = get_permalink( $post_id );

echo json_encode( array(
    'success'       => true,
    'post_id'       => $post_id,
    'title'         => $title,
    'status'        => $status,
    'permalink'     => $permalink,
    'preview_url'   => $preview_url,
    'category'      => $category_name,
    'rank_math_seo' => array(
        'focus_keyword' => $focus_keyword,
        'seo_title'     => $seo_title,
    ),
) );
exit( 0 );
