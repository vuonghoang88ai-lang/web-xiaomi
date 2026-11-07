<?php
/**
 * The main template file
 *
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 *
 * @package Omni_Commerce
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

get_header();
?>

<main id="primary" class="site-main max-w-[1440px] mx-auto px-4 py-8 md:py-12">
    <?php if ( have_posts() ) : ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php
            while ( have_posts() ) :
                the_post();
                get_template_part( 'template-parts/content', get_post_format() );
            endwhile;
            ?>
        </div>

        <div class="mt-8">
            <?php the_posts_pagination(); ?>
        </div>
    <?php else : ?>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-12 text-center">
            <span class="material-symbols-outlined text-6xl text-slate-300 mb-4 block">article</span>
            <h2 class="text-2xl font-bold text-slate-800 mb-2"><?php esc_html_e( 'Không tìm thấy nội dung', 'omni-commerce' ); ?></h2>
            <p class="text-slate-500 mb-6"><?php esc_html_e( 'Chưa có bài viết hoặc nội dung nào được đăng tải tại đây.', 'omni-commerce' ); ?></p>
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="inline-flex items-center justify-center bg-primary text-white px-6 py-2.5 rounded-full font-bold hover:bg-primary-container transition-colors">
                <?php esc_html_e( 'Về trang chủ', 'omni-commerce' ); ?>
            </a>
        </div>
    <?php endif; ?>
</main>

<?php
get_footer();
