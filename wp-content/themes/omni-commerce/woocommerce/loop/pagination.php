<?php
/**
 * Pagination - Show numbered pagination for catalog pages
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/loop/pagination.php.
 *
 * @see     https://woo.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.3.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$total   = isset( $total ) ? $total : wc_get_loop_prop( 'total_pages' );
$current = isset( $current ) ? $current : wc_get_loop_prop( 'current_page' );
$base    = isset( $base ) ? $base : esc_url_raw( str_replace( 999999999, '%#%', remove_query_arg( 'add-to-cart', get_pagenum_link( 999999999, false ) ) ) );
$format  = isset( $format ) ? $format : '';

if ( $total <= 1 ) {
	return;
}
?>
<nav class="flex justify-center mt-12 mb-8">
	<?php
	$pages = paginate_links(
		apply_filters(
			'woocommerce_pagination_args',
			array( // WPCS: XSS ok.
				'base'      => $base,
				'format'    => $format,
				'add_args'  => false,
				'current'   => max( 1, $current ),
				'total'     => $total,
				'prev_text' => '<span class="material-symbols-outlined text-sm">chevron_left</span>',
				'next_text' => '<span class="material-symbols-outlined text-sm">chevron_right</span>',
				'type'      => 'array',
				'end_size'  => 3,
				'mid_size'  => 3,
			)
		)
	);

	if ( is_array( $pages ) ) {
		echo '<ul class="flex items-center gap-2">';
		foreach ( $pages as $page ) {
            // Apply Tailwind classes to anchors and spans
            $base_class = 'flex items-center justify-center w-10 h-10 rounded-full border-2 border-primary text-primary font-bold hover:bg-primary hover:text-white transition-all text-sm bg-white';
            $current_class = 'flex items-center justify-center w-10 h-10 rounded-full border-2 border-primary bg-primary text-white font-bold text-sm';
            $dots_class = 'flex items-center justify-center w-10 h-10 text-text-muted font-bold';
            
            // Current page
            if ( strpos( $page, 'current' ) !== false ) {
                $page = str_replace( "class='page-numbers current'", 'class="page-numbers current ' . $current_class . '"', $page );
                $page = str_replace( 'class="page-numbers current"', 'class="page-numbers current ' . $current_class . '"', $page );
            }
            // Dots
            elseif ( strpos( $page, 'dots' ) !== false ) {
                $page = str_replace( "class='page-numbers dots'", 'class="page-numbers dots ' . $dots_class . '"', $page );
                $page = str_replace( 'class="page-numbers dots"', 'class="page-numbers dots ' . $dots_class . '"', $page );
            }
            // Standard links (Next, Prev, Numbers)
            else {
                $page = preg_replace( '/class=[\'"]([^\'"]*)page-numbers([^\'"]*)[\'"]/', 'class="$1page-numbers ' . $base_class . '$2"', $page );
            }
            
			echo '<li>' . $page . '</li>';
		}
		echo '</ul>';
	}
	?>
</nav>
