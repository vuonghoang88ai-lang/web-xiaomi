<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header(); ?>
<main id="primary" class="site-main">

<div class="max-w-[1440px] mx-auto px-4 py-8 md:py-12">
    <header class="mb-8 border-b border-slate-200 pb-6 text-center">
        <?php
        the_archive_title( '<h1 class="text-3xl md:text-4xl font-black text-slate-800 m-0 uppercase">', '</h1>' );
        the_archive_description( '<div class="archive-description mt-4 text-slate-500">', '</div>' );
        ?>
    </header>
    <?php if ( have_posts() ) : ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php while ( have_posts() ) : the_post(); ?>
                <?php get_template_part( 'template-parts/content', get_post_type() ); ?>
            <?php endwhile; ?>
        </div>
        <div class="mt-8">
            <?php the_posts_pagination(); ?>
        </div>
    <?php else : ?>
        <div class="text-center py-12">
            <p class="text-slate-500 text-lg"><?php esc_html_e( 'Không có bài viết nào.', 'omni-commerce' ); ?></p>
        </div>
    <?php endif; ?>
</div>
</main>
<?php get_footer(); ?>
