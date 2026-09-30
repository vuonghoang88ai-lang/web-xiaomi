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

$card_classes = 'product-card bg-white border border-border-gray rounded-lg p-3 relative group h-full flex flex-col transition-all duration-300 hover:-translate-y-1 hover:shadow-[0_12px_24px_rgba(0,0,0,0.06)]';
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
        if ( has_post_thumbnail() ) {
            echo get_the_post_thumbnail( get_the_ID(), 'woocommerce_thumbnail', [ 'class' => 'object-contain w-full h-full group-hover:scale-105 transition-transform', 'loading' => 'lazy' ] );
        } else {
            // Fix: Use WooCommerce's built-in placeholder image function to prevent broken image / huge whitespace
            echo '<img class="object-contain w-full h-full group-hover:scale-105 transition-transform" src="' . esc_url( wc_placeholder_img_src( 'woocommerce_thumbnail' ) ) . '" alt="Placeholder" loading="lazy" />';
        }
        ?>
    </a>
    
    <div class="mb-2">
        <?php if ( $product->is_in_stock() ) : ?>
            <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[9px] font-bold uppercase tracking-[0.08em] text-emerald-700">Có sẵn</span>
        <?php else : ?>
            <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[9px] font-bold uppercase tracking-[0.08em] text-slate-500">Tạm hết hàng</span>
        <?php endif; ?>
    </div>
    
    <a href="<?php echo esc_url( get_permalink() ); ?>" class="block after:absolute after:inset-0 after:z-20">
        <h3 class="text-xs font-semibold line-clamp-2 mb-2 group-hover:text-primary min-h-[2.25rem] leading-[1.35] text-text-main"><?php echo esc_html( get_the_title() ); ?></h3>
    </a>
    
    <div class="flex flex-wrap items-baseline gap-2 mb-1">
        <?php if ( $is_on_sale ) : ?>
            <span class="text-secondary font-bold text-base"><?php echo wc_price( $sale_price ); ?></span>
            <span class="text-text-muted line-through text-[10px]"><?php echo wc_price( $regular_price ); ?></span>
        <?php else : ?>
            <?php 
            $price = $product->get_price();
            if ( $price !== '' && (float) $price > 0 ) : 
            ?>
                <span class="text-secondary font-bold text-base"><?php echo wc_price( $price ); ?></span>
            <?php else : ?>
                <span class="text-secondary font-bold text-base"><?php echo esc_html__( 'Liên hệ', 'omni-commerce' ); ?></span>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <div class="flex flex-wrap items-center justify-between gap-1 mt-auto pt-2">
        <div class="flex text-yellow-400">
            <?php 
            for ( $i = 1; $i <= 5; $i++ ) {
                $fill = ( $i <= round( $average_rating ) ) ? '1' : '0';
                echo '<span class="material-symbols-outlined text-xs" style="font-variation-settings: \'FILL\' ' . $fill . ';">star</span>';
            }
            ?>
        </div>
        <span class="text-[10px] text-text-muted whitespace-nowrap shrink-0">Đã bán <?php echo esc_html( $formatted_sales ); ?></span>
    </div>
</div>
