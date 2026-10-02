<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header(); ?>
<main id="primary" class="site-main">

<div class="max-w-[1440px] mx-auto px-4 py-8 md:py-12">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 md:p-10 lg:p-12 max-w-4xl mx-auto">
        <?php while ( have_posts() ) : the_post(); ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <header class="mb-8 border-b border-slate-200 pb-6 text-center">
                    <?php the_title('<h1 class="text-3xl md:text-4xl font-black text-slate-800 m-0 uppercase mb-4">', '</h1>'); ?>
                    <div class="text-sm text-slate-500">
                        <span><?php echo get_the_date(); ?></span>
                    </div>
                </header>
                <div class="prose max-w-none prose-slate">
                    <?php
                    the_content();
                    wp_link_pages( array(
                        'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'omni-commerce' ),
                        'after'  => '</div>',
                    ) );
                    ?>
                </div>
            </article>
            <?php
            if ( comments_open() || get_comments_number() ) :
                comments_template();
            endif;
            ?>
        <?php endwhile; ?>
    </div>
</div>
</main>
<?php get_footer(); ?>
