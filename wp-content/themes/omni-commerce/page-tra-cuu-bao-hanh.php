<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

/**
 * Template Name: Trang Tra Cứu Bảo Hành
 * Description: Giao diện chuyên dụng dành cho chức năng tra cứu bảo hành từ ERP.
 */

get_header(); 
?>

<main id="primary" class="max-w-[1440px] mx-auto px-4 py-8 lg:py-12">
    
    <!-- Tiêu đề trang -->
    <div class="mb-8 lg:mb-10 text-center">
        <h1 class="text-2xl md:text-4xl font-black text-primary uppercase tracking-tight mb-3">
            <?php echo esc_html( get_the_title() ); ?>
        </h1>
        <?php if ( has_excerpt() ) : ?>
            <p class="text-gray-600 max-w-2xl mx-auto text-base leading-relaxed">
                <?php echo esc_html( get_the_excerpt() ); ?>
            </p>
        <?php endif; ?>
    </div>

    <!-- Nội dung chính -->
    <div class="max-w-5xl mx-auto">
        
        <div class="prose prose-slate max-w-none w-full mi-page-content">
            <?php
            if ( have_posts() ) {
                while ( have_posts() ) {
                    the_post();
                    the_content();
                }
            } else {
                echo '<div class="text-center py-10">';
                echo '<span class="material-symbols-outlined text-6xl text-gray-300 mb-4 block">article</span>';
                echo '<p class="text-gray-500">Nội dung trang đang được cập nhật...</p>';
                echo '</div>';
            }
            ?>
        </div>
        
    </div>

</main>

<?php get_footer(); ?>
