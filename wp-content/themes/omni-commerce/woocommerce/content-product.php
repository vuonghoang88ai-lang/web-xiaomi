<?php
/**
 * @version 1.0.0
 */
/**
 * The template for displaying product content within loops
 */

defined( 'ABSPATH' ) || exit;

global $product;

// Ensure visibility.
if ( empty( $product ) || ! $product->is_visible() ) {
	return;
}

$rating_count = $product->get_rating_count();
$average_rating = $product->get_average_rating();
$total_sales = get_post_meta( $product->get_id(), 'total_sales', true );
if ( ! $total_sales ) {
    $total_sales = 0;
}

// Convert large numbers to k (e.g. 1.2k)
$formatted_sales = $total_sales;
if ( $total_sales >= 1000 ) {
    $formatted_sales = round( $total_sales / 1000, 1 ) . 'k';
}

$is_in_stock = $product->is_in_stock();
$is_variable = $product->is_type( 'variable' );

if ( $is_variable ) {
    $regular_price = (float) $product->get_variation_regular_price( 'max', true );
    $sale_price = (float) $product->get_variation_sale_price( 'min', true );
} else {
    $regular_price = (float) $product->get_regular_price();
    $sale_price = (float) $product->get_sale_price();
}

$is_on_sale = $product->is_on_sale() && $sale_price > 0 && $regular_price > $sale_price;
$percentage = 0;
if ( $is_on_sale && $regular_price > 0 ) {
    $percentage = round( ( ( $regular_price - $sale_price ) / $regular_price ) * 100 );
}

$card_classes = 'product-card bg-white border border-border-gray rounded-lg p-2.5 sm:p-3 relative group h-full flex flex-col transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_12px_24px_rgba(0,0,0,0.06)] w-full min-w-0 overflow-hidden';
if ( ! $is_in_stock ) {
    $card_classes .= ' opacity-90';
}
?>

<div <?php wc_product_class( $card_classes, $product ); ?>>
    
    <?php if ( $is_on_sale && $percentage > 0 ) : ?>
        <div class="absolute top-2 right-2 bg-secondary text-white text-[10px] font-bold px-1.5 py-0.5 rounded-full z-10">-<?php echo esc_html( $percentage ); ?>%</div>
    <?php endif; ?>

    <a href="<?php echo esc_url( get_permalink() ); ?>" class="block aspect-square w-full mb-3 overflow-hidden rounded relative bg-gray-50 <?php echo ( ! $is_in_stock ) ? 'grayscale' : ''; ?>">
        <?php
        echo mi_safe_product_thumbnail( get_the_ID(), 'woocommerce_thumbnail', [ 'class' => 'object-contain w-full h-full group-hover:scale-105 transition-transform', 'loading' => 'lazy' ] );
        ?>
    </a>
    
    <div class="mb-2">
        <?php if ( $product->is_in_stock() ) : ?>
            <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[9px] font-bold uppercase tracking-[0.08em] text-emerald-700"><?php esc_html_e( 'Có sẵn', 'omni-commerce' ); ?></span>
        <?php else : ?>
            <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[9px] font-bold uppercase tracking-[0.08em] text-slate-500"><?php esc_html_e( 'Tạm hết hàng', 'omni-commerce' ); ?></span>
        <?php endif; ?>
    </div>
    
    <a href="<?php echo esc_url( get_permalink() ); ?>" class="block after:absolute after:inset-0 after:z-20">
        <h3 class="text-xs font-semibold line-clamp-2 mb-2 group-hover:text-primary min-h-[2.25rem] leading-[1.35] text-text-main"><?php echo esc_html( get_the_title() ); ?></h3>
    </a>
    
    <!-- Khối giá: .pc-price-row responsive qua @media CSS trong header.php -->
    <div class="pc-price-row mb-1">
        <?php if ( $is_on_sale ) : ?>
            <span class="text-secondary font-black text-sm xl:text-base tracking-tight whitespace-nowrap"><?php echo wp_kses_post( wc_price( $sale_price ) ); ?></span>
            <span class="flex items-center gap-1 flex-wrap">
                <span class="text-slate-400 line-through text-[11px] xl:text-xs font-normal whitespace-nowrap"><?php echo wp_kses_post( wc_price( $regular_price ) ); ?></span>
                <?php if ( $percentage > 0 ) : ?>
                    <span class="text-secondary bg-red-50 border border-secondary/20 text-[9px] xl:text-[10px] font-bold px-1.5 py-0.5 rounded leading-none"><?php echo '-' . esc_html( $percentage ); ?>%</span>
                <?php endif; ?>
            </span>
        <?php else : ?>
            <?php 
            $price = $product->get_price();
            if ( $price !== '' && (float) $price > 0 ) : 
            ?>
                <span class="text-secondary font-black text-sm xl:text-base tracking-tight whitespace-nowrap"><?php echo wp_kses_post( wc_price( $price ) ); ?></span>
            <?php else : ?>
                <span class="text-secondary font-bold text-xs whitespace-nowrap"><?php echo esc_html__( 'Liên hệ', 'omni-commerce' ); ?></span>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <!-- Hàng sao & Đã bán: .pc-meta-row responsive qua @media CSS trong header.php -->
    <div class="pc-meta-row mt-auto pt-2 border-t border-slate-100">
        <div class="flex text-yellow-400">
            <?php 
            for ( $i = 1; $i <= 5; $i++ ) {
                $fill = ( $i <= round( $average_rating ) ) ? '1' : '0';
                echo '<span class="material-symbols-outlined text-[12px] xl:text-[13px]" style="font-variation-settings: \'FILL\' ' . $fill . ';">star</span>';
            }
            ?>
            <span class="text-slate-400 text-[9px] xl:text-[10px] ml-0.5 font-medium">(<?php echo esc_html( $rating_count ); ?>)</span>
        </div>
        <span class="text-[9px] xl:text-[10px] text-text-muted whitespace-nowrap"><?php echo esc_html__( 'Đã bán', 'omni-commerce' ) . ' ' . esc_html( $formatted_sales ); ?></span>
    </div>
</div>
