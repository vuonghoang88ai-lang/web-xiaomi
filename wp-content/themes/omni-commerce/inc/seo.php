<?php
/**
 * SEO Integration & Extensions (Rank Math SEO Compatibility)
 *
 * Tối ưu hóa SEO toàn diện, mở rộng và tương thích 100% với Rank Math SEO:
 * 1. Hợp nhất Schema Product vào Rank Math JSON-LD (tránh trùng lặp script).
 * 2. Gỡ bỏ Schema Breadcrumb thừa do WooCommerce mặc định sinh ra.
 * 3. Tự động điền thuộc tính alt thông minh cho mọi hình ảnh thiếu alt.
 * 4. Fallback Meta Description tự nhiên cho Trang chủ nếu chưa cấu hình.
 *
 * @package Omni_Commerce
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * 0. Đảm bảo Cấu trúc Đường dẫn Rút gọn tối đa (Flat URL) & Rewrite Rules của Rank Math luôn được cập nhật:
 * - Xóa '/san-pham/' khỏi URL sản phẩm (vd: /tivi-xiaomi-a-32-inch-hd/)
 * - Xóa '/product-category/' khỏi URL danh mục sản phẩm (vd: /tivi-xiaomi/)
 * - Xóa '/category/' khỏi URL danh mục bài viết (vd: /tin-tuc/)
 * - Xóa slug cha khỏi danh mục con (vd: /tivi-xiaomi-32-inch/)
 * - Tự động 301 Redirect từ URL cũ sang URL rút gọn mới để bảo vệ thứ hạng SEO.
 */
add_action( 'init', function() {
    $needs_flush = false;

    // 1. Cấu hình Flat URL trong rank-math-options-general
    $general_opts = get_option( 'rank-math-options-general', array() );
    if ( is_array( $general_opts ) ) {
        $general_updated = false;
        $flat_keys = array(
            'strip_category_base'             => 'on',
            'wc_remove_category_base'         => 'on',
            'wc_remove_product_base'          => 'on',
            'wc_remove_category_parent_slugs' => 'on',
        );

        foreach ( $flat_keys as $key => $val ) {
            if ( empty( $general_opts[ $key ] ) || $val !== $general_opts[ $key ] ) {
                $general_opts[ $key ] = $val;
                $general_updated      = true;
            }
        }

        if ( isset( $general_opts['attachment_redirect_default'] ) && preg_match( '#https?://\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}#', $general_opts['attachment_redirect_default'] ) ) {
            $general_opts['attachment_redirect_default'] = home_url( '/' );
            $general_updated = true;
        }

        if ( $general_updated ) {
            update_option( 'rank-math-options-general', $general_opts );
            $needs_flush = true;
        }
    }

    // 2. Kích hoạt danh mục sản phẩm trong sitemap nếu chưa bật
    $sitemap_opts = get_option( 'rank-math-options-sitemap', array() );
    if ( is_array( $sitemap_opts ) && empty( $sitemap_opts['tax_product_cat_sitemap'] ) ) {
        $sitemap_opts['tax_product_cat_sitemap'] = 'on';
        update_option( 'rank-math-options-sitemap', $sitemap_opts );
        $needs_flush = true;
    }

    // 3. Tự động flush rewrite rules nếu có thay đổi permalink
    if ( $needs_flush || get_option( 'rank_math_flush_rewrite' ) ) {
        delete_option( 'rank_math_flush_rewrite' );
        flush_rewrite_rules( false );
    }
}, 99 );

/**
 * 1. Gỡ bỏ Structured Data mặc định của WooCommerce để tránh in lặp Breadcrumb
 */
add_action( 'wp', function() {
    if ( function_exists( 'WC' ) && isset( WC()->structured_data ) ) {
        remove_action( 'wp_footer', array( WC()->structured_data, 'output_structured_data' ), 10 );
    }
} );

/**
 * 2. Tối ưu hóa và làm giàu Schema Product của Rank Math (Bổ sung Brand Xiaomi, SKU nếu thiếu)
 * Không tạo thêm entity trùng lặp mà chuẩn hóa trực tiếp vào richSnippet của Rank Math.
 */
add_filter( 'rank_math/json_ld', function( $data, $jsonld ) {
    if ( is_product() ) {
        global $product;
        if ( ! is_a( $product, 'WC_Product' ) ) {
            $product = wc_get_product( get_the_ID() );
        }

        if ( $product ) {
            $product_id = $product->get_id();
            $sku        = $product->get_sku() ?: ( 'XIAOMI-' . $product_id );

            if ( isset( $data['richSnippet'] ) && is_array( $data['richSnippet'] ) ) {
                // Bổ sung Brand nếu chưa có
                if ( empty( $data['richSnippet']['brand'] ) ) {
                    $data['richSnippet']['brand'] = array(
                        '@type' => 'Brand',
                        'name'  => 'Xiaomi',
                    );
                }
                // Bổ sung SKU nếu chưa có
                if ( empty( $data['richSnippet']['sku'] ) ) {
                    $data['richSnippet']['sku'] = $sku;
                }
            } else {
                // Fallback nếu Rank Math chưa tạo richSnippet
                $image_url = wp_get_attachment_image_url( $product->get_image_id(), 'full' );

                if ( $product->is_type( 'variable' ) ) {
                    $sale_price = (float) $product->get_variation_sale_price( 'min', true );
                    $min_price  = (float) $product->get_variation_price( 'min', true );
                    $price      = $sale_price > 0 ? $sale_price : $min_price;
                } else {
                    $sale_price = (float) $product->get_sale_price();
                    $min_price  = (float) $product->get_price();
                    $price      = $sale_price > 0 ? $sale_price : $min_price;
                }

                $avg_rating   = (float) $product->get_average_rating();
                $review_count = (int) $product->get_rating_count();
                $description  = wp_strip_all_tags( $product->get_short_description() ?: get_the_excerpt( $product_id ) ?: get_the_title( $product_id ) );

                $product_schema = array(
                    '@type'       => 'Product',
                    '@id'         => get_permalink( $product_id ) . '#richSnippet',
                    'name'        => get_the_title( $product_id ),
                    'description' => $description,
                    'url'         => get_permalink( $product_id ),
                    'image'       => $image_url ?: wc_placeholder_img_src( 'full' ),
                    'sku'         => $sku,
                    'brand'       => array(
                        '@type' => 'Brand',
                        'name'  => 'Xiaomi',
                    ),
                    'offers'      => array(
                        '@type'         => 'Offer',
                        'price'         => (string) ( $price > 0 ? $price : 0 ),
                        'priceCurrency' => 'VND',
                        'availability'  => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                        'url'           => get_permalink( $product_id ),
                    ),
                );

                if ( $avg_rating > 0 && $review_count > 0 ) {
                    $product_schema['aggregateRating'] = array(
                        '@type'       => 'AggregateRating',
                        'ratingValue' => number_format( $avg_rating, 1 ),
                        'reviewCount' => (string) $review_count,
                        'bestRating'  => '5',
                        'worstRating' => '1',
                    );
                }

                $data['richSnippet'] = $product_schema;
            }
        }
    }

    return $data;
}, 99, 2 );

/**
 * 3. Fallback Meta Description tự nhiên cho tất cả các trang nếu Rank Math để trống
 */
add_filter( 'rank_math/frontend/description', function( $description ) {
    if ( ! empty( $description ) ) {
        return $description;
    }

    if ( is_front_page() || is_home() ) {
        return __( 'TongKhoMi.com - Hệ thống tổng kho phân phối chính hãng Tivi Xiaomi, Tủ lạnh Xiaomi, Robot hút bụi và thiết bị gia đình thông minh giá tốt nhất, bảo hành chính hãng toàn quốc.', 'omni-commerce' );
    }

    if ( is_product_taxonomy() || is_category() || is_tag() || is_tax() ) {
        $term = get_queried_object();
        if ( $term && ! empty( $term->description ) ) {
            return wp_strip_all_tags( $term->description );
        }
        $name = $term && isset( $term->name ) ? $term->name : 'Xiaomi';
        return sprintf( __( 'Khám phá danh sách %s chính hãng tại TongKhoMi.com. Cam kết giá gốc tại kho, bảo hành chính hãng, trả góp 0%%, giao hàng và lắp đặt tận nơi.', 'omni-commerce' ), $name );
    }

    if ( function_exists( 'is_shop' ) && is_shop() ) {
        return __( 'Hệ thống phân phối và bán lẻ thiết bị Xiaomi chính hãng hàng đầu Việt Nam. Đầy đủ Tivi, Tủ lạnh, Robot hút bụi, Máy giặt Xiaomi giá tốt nhất thị trường.', 'omni-commerce' );
    }

    if ( is_singular() ) {
        $post = get_queried_object();
        if ( $post && ! empty( $post->post_excerpt ) ) {
            return wp_strip_all_tags( $post->post_excerpt );
        }
        if ( $post && ! empty( $post->post_content ) ) {
            $stripped = wp_strip_all_tags( $post->post_content );
            return wp_trim_words( $stripped, 35, '...' );
        }
        return sprintf( __( '%s - Thông tin chi tiết, tính năng và bảo hành tại hệ thống phân phối Xiaomi chính hãng TongKhoMi.com.', 'omni-commerce' ), get_the_title() );
    }

    return $description;
} );

/**
 * 4. Tự động điền thuộc tính alt thông minh cho mọi thẻ img nếu bị rỗng (Image SEO)
 */
add_filter( 'wp_get_attachment_image_attributes', function( $attr, $attachment, $size ) {
    if ( empty( $attr['alt'] ) ) {
        // 1. Thử lấy alt từ meta của attachment
        $alt = get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true );

        // 2. Nếu trống, lấy tiêu đề của tệp đính kèm (Media title)
        if ( empty( $alt ) ) {
            $alt = get_the_title( $attachment->ID );
        }

        // 3. Nếu vẫn trống, lấy tiêu đề bài viết / sản phẩm hiện tại
        if ( empty( $alt ) && in_the_loop() ) {
            $alt = get_the_title();
        }

        if ( ! empty( $alt ) ) {
            $attr['alt'] = esc_attr( trim( strip_tags( $alt ) ) );
        }
    }
    return $attr;
}, 10, 3 );

/**
 * 5. Chuẩn hóa Document Heading (duy nhất 1 thẻ h1/trang) và bổ sung Image Alt trong the_content
 */
add_filter( 'the_content', function( $content ) {
    if ( empty( $content ) ) {
        return $content;
    }

    // 5.1 Đổi h1 trong nội dung thành h2 để tránh trùng lặp với tiêu đề chính của template
    if ( is_singular() ) {
        $content = preg_replace( '/<h1(\s+[^>]*)?>/i', '<h2$1>', $content );
        $content = preg_replace( '/<\/h1>/i', '</h2>', $content );
    }

    // 5.2 Tự động bổ sung alt cho mọi ảnh thô trong nội dung bài viết / mô tả sản phẩm nếu thiếu
    $page_title = esc_attr( get_the_title() );
    if ( ! empty( $page_title ) ) {
        $content = preg_replace_callback( '/<img\s+([^>]*?)>/i', function( $matches ) use ( $page_title ) {
            $attrs = $matches[1];
            if ( ! preg_match( '/\balt\s*=/i', $attrs ) ) {
                return '<img ' . $attrs . ' alt="' . $page_title . '">';
            }
            if ( preg_match( '/\balt\s*=\s*(["\'])\s*\1/i', $attrs ) ) {
                return preg_replace( '/\balt\s*=\s*(["\'])\s*\1/i', 'alt="' . $page_title . '"', $matches[0] );
            }
            return $matches[0];
        }, $content );
    }

    return $content;
}, 25 );

/**
 * 6. Tự động 301 Redirect các URL có hậu tố '-2' sang URL sản phẩm chính thức
 */
add_action( 'template_redirect', function() {
    if ( is_404() ) {
        $req_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
        if ( preg_match( '#^/([^/?]+)-2/?(\?.*)?$#', $req_uri, $matches ) ) {
            $clean_slug = $matches[1];
            $found_post = get_page_by_path( $clean_slug, OBJECT, 'product' );
            if ( $found_post ) {
                wp_safe_redirect( get_permalink( $found_post->ID ), 301 );
                exit;
            }
        }
    }
} );



