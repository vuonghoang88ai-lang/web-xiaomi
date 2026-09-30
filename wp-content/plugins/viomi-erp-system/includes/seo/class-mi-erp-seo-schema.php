<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Mi_ERP_SEO_Schema {
    public static function init() {
        add_action( 'wp_head', array( __CLASS__, 'render_schema' ), 5 );
    }

    public static function render_schema() {
        if ( is_singular( 'product' ) ) {
            self::render_product_schema();
        } elseif ( is_single() || is_page() ) {
            self::render_article_schema();
        }

        // Always render breadcrumb if it's not front page
        if ( ! is_front_page() ) {
            self::render_breadcrumb_schema();
        }
    }

    private static function render_product_schema() {
        global $product;
        if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
            return;
        }

        $image = wp_get_attachment_image_url( $product->get_image_id(), 'full' );
        if ( ! $image ) {
            $image = '';
        }

        $schema = array(
            '@context' => 'https://schema.org/',
            '@type'    => 'Product',
            'name'     => $product->get_name(),
            'image'    => $image,
            'sku'      => $product->get_sku() ?: 'SP-' . $product->get_id(),
            'brand'    => array(
                '@type' => 'Brand',
                'name'  => 'Xiaomi'
            ),
            'offers'   => array(
                '@type'         => 'Offer',
                'url'           => get_permalink( $product->get_id() ),
                'priceCurrency' => get_woocommerce_currency(),
                'price'         => $product->get_price(),
                'availability'  => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock'
            )
        );

        // Optional aggregate rating
        if ( $product->get_rating_count() > 0 ) {
            $schema['aggregateRating'] = array(
                '@type'       => 'AggregateRating',
                'ratingValue' => $product->get_average_rating(),
                'reviewCount' => $product->get_review_count()
            );
        }

        echo '<script type="application/ld+json">' . wp_json_encode( $schema ) . '</script>' . "\n";
    }

    private static function render_article_schema() {
        global $post;
        if ( ! $post ) return;

        $image = get_the_post_thumbnail_url( $post->ID, 'full' ) ?: '';

        $schema = array(
            '@context'      => 'https://schema.org',
            '@type'         => 'Article',
            'headline'      => mb_substr( get_the_title( $post->ID ), 0, 110 ),
            'image'         => $image,
            'datePublished' => get_the_date( 'c', $post->ID ),
            'dateModified'  => get_the_modified_date( 'c', $post->ID ),
            'author'        => array(
                '@type' => 'Person',
                'name'  => get_the_author_meta( 'display_name', $post->post_author ) ?: 'Admin'
            ),
            'publisher'     => array(
                '@type' => 'Organization',
                'name'  => get_bloginfo( 'name' ),
                'logo'  => array(
                    '@type' => 'ImageObject',
                    'url'   => '' // TODO: Can fetch from customizer/acf if needed
                )
            )
        );

        echo '<script type="application/ld+json">' . wp_json_encode( $schema ) . '</script>' . "\n";
    }

    private static function render_breadcrumb_schema() {
        $breadcrumbs = array();
        $breadcrumbs[] = array(
            '@type'    => 'ListItem',
            'position' => 1,
            'name'     => 'Trang chủ',
            'item'     => home_url( '/' )
        );

        $position = 2;

        if ( is_singular( 'product' ) ) {
            global $post;
            $terms = wc_get_product_terms( $post->ID, 'product_cat', array( 'orderby' => 'parent', 'order' => 'DESC' ) );
            if ( ! empty( $terms ) ) {
                $main_term = $terms[0];
                $breadcrumbs[] = array(
                    '@type'    => 'ListItem',
                    'position' => $position++,
                    'name'     => $main_term->name,
                    'item'     => get_term_link( $main_term )
                );
            }
            $breadcrumbs[] = array(
                '@type'    => 'ListItem',
                'position' => $position,
                'name'     => get_the_title(),
                'item'     => get_permalink()
            );
        } elseif ( is_category() || is_tax() ) {
            $queried_object = get_queried_object();
            if ( $queried_object ) {
                $breadcrumbs[] = array(
                    '@type'    => 'ListItem',
                    'position' => $position,
                    'name'     => $queried_object->name,
                    'item'     => get_term_link( $queried_object )
                );
            }
        } elseif ( is_page() || is_single() ) {
            $breadcrumbs[] = array(
                '@type'    => 'ListItem',
                'position' => $position,
                'name'     => get_the_title(),
                'item'     => get_permalink()
            );
        }

        if ( count( $breadcrumbs ) > 1 ) {
            $schema = array(
                '@context'        => 'https://schema.org',
                '@type'           => 'BreadcrumbList',
                'itemListElement' => $breadcrumbs
            );
            echo '<script type="application/ld+json">' . wp_json_encode( $schema ) . '</script>' . "\n";
        }
    }
}

Mi_ERP_SEO_Schema::init();
