<?php
/**
 * Template part for displaying posts (default content)
 *
 * @package Omni_Commerce
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( get_post_type() === 'product' && function_exists( 'wc_get_template_part' ) ) {
    wc_get_template_part( 'content', 'product' );
    return;
}
?>

<article id="post-<?php the_ID(); ?>" <?php post_class( 'bg-white rounded-2xl border border-slate-100 overflow-hidden shadow-sm hover:shadow-md transition-all duration-300 hover:-translate-y-1 flex flex-col p-4 group' ); ?>>
    <a href="<?php the_permalink(); ?>" class="aspect-[16/9] rounded-xl overflow-hidden bg-slate-100 mb-3 block">
        <?php if ( has_post_thumbnail() ) : ?>
            <?php the_post_thumbnail( 'medium_large', array( 'class' => 'w-full h-full object-cover group-hover:scale-105 transition-transform duration-500' ) ); ?>
        <?php else : ?>
            <div class="w-full h-full flex items-center justify-center text-slate-300">
                <span class="material-symbols-outlined text-4xl">article</span>
            </div>
        <?php endif; ?>
    </a>

    <div class="flex items-center gap-2 text-outline text-[12px] mb-2">
        <span class="material-symbols-outlined text-[14px]">schedule</span>
        <span><?php echo esc_html( get_the_date() ); ?></span>
    </div>

    <h2 class="text-[16px] font-bold text-slate-800 group-hover:text-primary transition-colors line-clamp-2 leading-[1.4] mb-2">
        <a href="<?php the_permalink(); ?>"><?php echo esc_html( get_the_title() ); ?></a>
    </h2>

    <div class="text-slate-600 text-sm line-clamp-3 mb-4 leading-relaxed">
        <?php the_excerpt(); ?>
    </div>

    <a href="<?php the_permalink(); ?>" class="mt-auto inline-flex items-center gap-1 text-primary text-sm font-bold group-hover:underline">
        <?php esc_html_e( 'Đọc tiếp', 'omni-commerce' ); ?>
        <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
    </a>
</article>
