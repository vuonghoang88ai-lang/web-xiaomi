<?php
/**
 * @version 1.0.0
 */
/**
 * Show options for ordering
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<form class="woocommerce-ordering flex items-center gap-2 text-xs !m-0 !p-0" method="get">
	<span class="text-text-muted whitespace-nowrap shrink-0 min-w-max">Xếp theo:</span>
	<select name="orderby" class="orderby border-none bg-transparent focus:ring-0 font-semibold cursor-pointer !m-0 !p-0 !py-0 leading-none" aria-label="<?php esc_attr_e( 'Shop order', 'woocommerce' ); ?>" onchange="this.form.submit()">
		<?php foreach ( $catalog_orderby_options as $id => $name ) : ?>
			<option value="<?php echo esc_attr( $id ); ?>" <?php selected( $orderby, $id ); ?>><?php echo esc_html( $name ); ?></option>
		<?php endforeach; ?>
	</select>
	<input type="hidden" name="paged" value="1" />
	<?php wc_query_string_form_fields( null, array( 'orderby', 'submit', 'paged', 'product-page' ) ); ?>
</form>
