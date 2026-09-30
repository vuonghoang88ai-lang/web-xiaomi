<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

/**
 * Template Name: Trang Tin Tức
 * Description: Trang hiển thị toàn bộ bài viết tin tức.
 */

get_header(); 

// 1. Query cho danh sách bài viết chính (List)
$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
$list_args = array(
    'post_type'      => 'post',
    'post_status'    => 'publish',
    'posts_per_page' => get_option('posts_per_page', 10),
    'paged'          => $paged,
);
$list_query = new WP_Query($list_args);

// 2. Query riêng cho phần Nổi bật (Chỉ chạy ở trang 1)
if ( $paged == 1 ) {
    $featured_args = array(
        'post_type'           => 'post',
        'post_status'         => 'publish',
        'posts_per_page'      => 5,
        'ignore_sticky_posts' => true,
    );
    $featured_query = new WP_Query($featured_args);
}
?>

<main id="primary" class="mx-auto max-w-[1440px] px-4 py-8">
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        
        <!-- Main Content (Left 3 cols) -->
        <div class="lg:col-span-3">
            <!-- Header section -->
            <div class="flex items-center justify-between border-b border-gray-200 mb-6 flex-wrap gap-4">
                <h1 class="text-[28px] md:text-[32px] font-black uppercase text-primary border-b-2 border-primary pb-2 -mb-[1px] whitespace-nowrap shrink-0"><?php echo esc_html(get_the_title()); ?></h1>
                <div class="hidden md:flex items-center gap-2 lg:gap-3 text-[14px] pb-2 flex-wrap">
                    <?php 
                    $nav_categories = get_categories(array('number' => 4, 'orderby' => 'count', 'order' => 'DESC'));
                    foreach ($nav_categories as $cat) {
                        echo '<a href="' . esc_url(get_category_link($cat)) . '" class="px-4 py-1.5 rounded-full border border-gray-200 bg-gray-50 hover:border-primary hover:bg-primary hover:text-white transition-all font-semibold text-gray-700 whitespace-nowrap">' . esc_html($cat->name) . '</a>';
                    }
                    ?>
                </div>
            </div>

            <?php if ( $list_query->have_posts() || ( isset($featured_query) && $featured_query->have_posts() ) ) : ?>

                <?php if ( $paged == 1 ) : ?>
                    <!-- Featured Section (Page 1 Only) -->
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-8">
                        
                        <?php
                        $main_post = null;
                        $side_posts = array();
                        
                        if ( isset($featured_query) && $featured_query->have_posts() ) {
                            $count = 0;
                            while ( $featured_query->have_posts() ) : $featured_query->the_post();
                                if ( $count === 0 ) {
                                    $main_post = get_post();
                                } else {
                                    $side_posts[] = get_post();
                                }
                                $count++;
                            endwhile;
                            wp_reset_postdata();
                        }
                        ?>

                        <!-- Main Featured -->
                        <?php if ( $main_post ) : global $post; $post = $main_post; setup_postdata( $post ); ?>
                            <div class="md:col-span-3 group relative">
                                <a href="<?php the_permalink(); ?>" class="block overflow-hidden rounded-md h-[320px]">
                                    <?php if ( has_post_thumbnail() ) : ?>
                                        <?php the_post_thumbnail( 'large', array( 'class' => 'w-full h-full object-cover group-hover:scale-105 transition-transform duration-500' ) ); ?>
                                    <?php else : ?>
                                        <div class="w-full h-full bg-gray-200 flex items-center justify-center">
                                            <span class="material-symbols-outlined text-gray-400 text-5xl">image</span>
                                        </div>
                                    <?php endif; ?>
                                </a>
                                <div class="absolute bottom-0 left-0 w-full bg-gradient-to-t from-black/80 via-black/40 to-transparent p-5 md:p-6">
                                    <h2 class="text-[20px] md:text-[24px] font-black text-white mb-2 leading-snug group-hover:text-primary transition-colors line-clamp-2">
                                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                    </h2>
                                    <div class="flex items-center gap-3 text-gray-300 text-[12px]">
                                        <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">person</span> <?php echo esc_html(get_the_author()); ?></span>
                                        <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">schedule</span> <?php echo esc_html(get_the_date()); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Side Featured -->
                        <?php if ( ! empty( $side_posts ) ) : ?>
                            <div class="md:col-span-2 flex flex-col gap-3">
                                <?php foreach ( $side_posts as $p ) : global $post; $post = $p; setup_postdata( $post ); ?>
                                    <div class="flex gap-3 group">
                                        <a href="<?php the_permalink(); ?>" class="w-[120px] sm:w-[130px] aspect-[4/3] shrink-0 overflow-hidden rounded-xl relative">
                                            <?php if ( has_post_thumbnail() ) : ?>
                                                <?php the_post_thumbnail( 'medium', array( 'class' => 'w-full h-full object-cover group-hover:scale-105 transition-transform duration-500' ) ); ?>
                                            <?php else : ?>
                                                <div class="w-full h-full bg-gray-200 flex items-center justify-center">
                                                    <span class="material-symbols-outlined text-gray-400">image</span>
                                                </div>
                                            <?php endif; ?>
                                        </a>
                                        <div class="flex-1">
                                            <h3 class="font-bold text-[13px] text-on-surface leading-snug mb-1 group-hover:text-primary transition-colors line-clamp-3">
                                                <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                            </h3>
                                        </div>
                                    </div>
                                <?php endforeach; wp_reset_postdata(); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Standard List -->
                <div class="flex flex-col gap-6">
                    <?php 
                    if ( $list_query->have_posts() ) :
                        while ( $list_query->have_posts() ) : $list_query->the_post(); ?>
                            
                            <article class="flex flex-col sm:flex-row gap-5 group p-4 border border-transparent hover:border-gray-100 rounded-xl hover:-translate-y-1 hover:shadow-[0_10px_25px_-5px_rgba(0,0,0,0.1)] transition-all duration-300 bg-white">
                                <a href="<?php the_permalink(); ?>" class="w-full sm:w-[260px] shrink-0 overflow-hidden rounded-xl">
                                    <?php if ( has_post_thumbnail() ) : ?>
                                        <?php the_post_thumbnail( 'medium_large', array( 'class' => 'w-full h-auto aspect-[16/9] object-cover group-hover:scale-105 transition-transform duration-500', 'loading' => 'lazy' ) ); ?>
                                    <?php else : ?>
                                        <div class="w-full aspect-[16/9] bg-gray-200 flex items-center justify-center">
                                            <span class="material-symbols-outlined text-gray-400 text-4xl">image</span>
                                        </div>
                                    <?php endif; ?>
                                </a>
                                <div class="flex-1 flex flex-col justify-start pt-1">
                                    <h3 class="text-[17px] font-bold text-on-surface mb-2 leading-snug group-hover:text-primary transition-colors">
                                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                    </h3>
                                    <div class="flex items-center gap-3 text-outline text-[12px] mb-2">
                                        <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">person</span> <?php echo esc_html(get_the_author()); ?></span>
                                        <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">schedule</span> <?php echo esc_html(get_the_date()); ?></span>
                                    </div>
                                    <div class="text-gray-600 text-[14px] line-clamp-3 mb-2">
                                        <?php the_excerpt(); ?>
                                    </div>
                                    <div class="mt-auto pt-2">
                                        <a href="<?php the_permalink(); ?>" class="inline-flex items-center gap-1 text-[13px] font-semibold text-primary group-hover:text-primary-dark transition-colors">
                                            Xem thêm 
                                            <span class="material-symbols-outlined text-[16px] transition-transform duration-300 group-hover:translate-x-1">arrow_forward</span>
                                        </a>
                                    </div>
                                </div>
                            </article>

                        <?php endwhile;
                    endif;
                    ?>
                </div>

                <div class="mt-8 flex justify-center border-t border-gray-100 pt-8">
                    <?php
                    echo paginate_links( array(
                        'total'     => $list_query->max_num_pages,
                        'current'   => $paged,
                        'mid_size'  => 2,
                        'prev_text' => '<span class="material-symbols-outlined">chevron_left</span>',
                        'next_text' => '<span class="material-symbols-outlined">chevron_right</span>',
                        'type'      => 'list'
                    ) );
                    ?>
                </div>

            <?php else : ?>
                <div class="text-center py-20 bg-surface-container-low rounded-lg border border-gray-100">
                    <span class="material-symbols-outlined text-6xl text-gray-300 mb-4 block">article</span>
                    <h2 class="text-xl font-bold text-gray-700 mb-2">Chưa có bài viết nào</h2>
                    <p class="text-gray-500">Nội dung đang được chúng tôi cập nhật. Vui lòng quay lại sau!</p>
                </div>
            <?php endif; ?>
            <?php wp_reset_postdata(); ?>
        </div>

        <!-- Sidebar (Right col) -->
        <aside class="lg:col-span-1">
            <div class="sticky top-6">
                
                <h3 class="text-[18px] font-bold text-primary uppercase border-b-2 border-primary pb-2 mb-6">TIN XEM NHIỀU</h3>
                
                <div class="flex flex-col gap-4">
                    <?php
                    // Display some posts for sidebar
                    $popular_args = array(
                        'posts_per_page' => 5,
                        'post_status'    => 'publish',
                        'orderby'        => 'comment_count' // Fallback for popular
                    );
                    $popular_posts = new WP_Query($popular_args);
                    if ($popular_posts->have_posts()) :
                        while ($popular_posts->have_posts()) : $popular_posts->the_post();
                        ?>
                        <a href="<?php the_permalink(); ?>" class="flex gap-3 group pb-4 border-b border-gray-100 last:border-0 last:pb-0">
                            <div class="w-[110px] aspect-[4/3] shrink-0 rounded-xl overflow-hidden bg-gray-200 flex items-center justify-center relative">
                                <?php if ( has_post_thumbnail() ) : ?>
                                    <?php the_post_thumbnail( 'thumbnail', array('class' => 'w-full h-full object-cover group-hover:scale-110 transition-transform duration-500', 'loading' => 'lazy') ); ?>
                                <?php else : ?>
                                    <span class="material-symbols-outlined text-gray-400">image</span>
                                <?php endif; ?>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-[13px] font-bold text-gray-800 leading-snug group-hover:text-primary line-clamp-3">
                                    <?php the_title(); ?>
                                </h4>
                            </div>
                        </a>
                        <?php 
                        endwhile;
                        wp_reset_postdata();
                    endif;
                    ?>
                </div>

            </div>
        </aside>

    </div>
</main>

<?php get_footer(); ?>
