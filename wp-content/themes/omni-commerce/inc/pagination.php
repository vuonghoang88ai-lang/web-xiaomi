<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

/**
 * Tùy chỉnh class Tailwind cho Pagination của WooCommerce
 */
add_filter( 'paginate_links', 'mi_tailwind_pagination_links' );
function mi_tailwind_pagination_links( $link ) {
    // Current page
    $link = str_replace( "<span aria-current=\"page\" class=\"page-numbers current\">", "<span class=\"bg-primary text-white border-2 border-primary px-4 py-2 rounded-full font-bold text-sm inline-block\">", $link );
    
    // Other pages
    $link = str_replace( "<a class=\"page-numbers\"", "<a class=\"bg-white border-2 border-primary text-primary px-4 py-2 rounded-full font-bold hover:bg-primary hover:text-white transition-all text-sm inline-block\"", $link );
    
    // Next/Prev (usually has class 'next page-numbers' or 'prev page-numbers')
    $link = str_replace( "<a class=\"next page-numbers\"", "<a class=\"bg-white border-2 border-primary text-primary px-4 py-2 rounded-full font-bold hover:bg-primary hover:text-white transition-all text-sm inline-block\"", $link );
    $link = str_replace( "<a class=\"prev page-numbers\"", "<a class=\"bg-white border-2 border-primary text-primary px-4 py-2 rounded-full font-bold hover:bg-primary hover:text-white transition-all text-sm inline-block\"", $link );

    return $link;
}
