<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Template Name: Default Page
 *
 * @package Viomi_Theme
 */

get_header(); ?>
<main id="primary" class="site-main">


<div class="max-w-[1440px] mx-auto px-4 py-8 md:py-12">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 md:p-10 lg:p-12">
        <?php
        while (have_posts()) :
            the_post();
            ?>
            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <header class="mb-8 border-b border-slate-200 pb-6">
                    <?php the_title('<h1 class="text-3xl md:text-4xl font-black text-slate-800 m-0 uppercase">', '</h1>'); ?>
                </header>

                <div class="mi-page-content text-slate-700 leading-relaxed text-[16px]">
                    <?php
                    the_content();
                    
                    wp_link_pages(
                        array(
                            'before' => '<div class="page-links mt-8">' . esc_html__('Trang:', 'omni-commerce'),
                            'after'  => '</div>',
                        )
                    );
                    ?>
                </div>
            </article>
            <?php
        endwhile; 
        ?>
    </div>
</div>

<style>
    /* Styling for standard WP page content */
    .mi-page-content h2, .mi-page-content h3, .mi-page-content h4 {
        color: #1e293b;
        font-weight: 800;
        margin-top: 2rem;
        margin-bottom: 1rem;
    }
    .mi-page-content h2 { font-size: 1.75rem; }
    .mi-page-content h3 { font-size: 1.5rem; }
    .mi-page-content p {
        margin-bottom: 1.25rem;
    }
    .mi-page-content ul, .mi-page-content ol {
        margin-left: 1.5rem;
        margin-bottom: 1.25rem;
    }
    .mi-page-content ul { list-style-type: disc; }
    .mi-page-content ol { list-style-type: decimal; }
    .mi-page-content a {
        color: #006779;
        text-decoration: underline;
    }
    .mi-page-content img {
        border-radius: 0.5rem;
        max-width: 100%;
        height: auto;
        margin: 1.5rem 0;
    }
</style>

</main>
<?php get_footer();
