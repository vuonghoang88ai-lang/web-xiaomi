<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * The main template file
 */

get_header(); ?>

<main id="primary" class="container mx-auto max-w-[1440px] px-4 py-8">
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        
        <!-- Main Content (Left 3 cols) -->
        <div class="lg:col-span-3">
            <!-- Header section -->
            <div class="flex items-center justify-between border-b border-gray-200 mb-6 flex-wrap gap-4">
                <h1 class="text-[18px] font-bold uppercase text-primary border-b-2 border-primary pb-2 -mb-[1px] whitespace-nowrap shrink-0">Tin Tức</h1>
                <div class="hidden md:flex items-center gap-4 lg:gap-6 text-[14px] font-medium text-gray-700 pb-2 flex-wrap">
                    <a href="#" class="hover:text-primary transition-colors whitespace-nowrap">Tin công nghệ</a>
                    <a href="#" class="hover:text-primary transition-colors whitespace-nowrap">Khuyến mại</a>
                    <a href="#" class="hover:text-primary transition-colors whitespace-nowrap">Tư vấn</a>
                    <a href="#" class="hover:text-primary transition-colors whitespace-nowrap">Hướng dẫn kĩ thuật</a>
                </div>
            </div>

            <?php if ( have_posts() ) : ?>

                <?php if ( ! is_paged() ) : ?>
                    <!-- Featured Section (Page 1 Only) -->
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-8">
                        
                        <?php
                        $main_post = null;
                        $side_posts = array();
                        
                        $count = 0;
                        while ( have_posts() && $count < 5 ) : the_post();
                            if ( $count === 0 ) {
                                $main_post = get_post();
                            } else {
                                $side_posts[] = get_post();
                            }
                            $count++;
                        endwhile;
                        ?>

                        <!-- Main Featured -->
                        <?php if ( $main_post ) : setup_postdata( $main_post ); ?>
                            <div <?php post_class("md:col-span-3 group relative"); ?>>
                                <a href="<?php the_permalink(); ?>" class="block overflow-hidden rounded-md h-[320px]">
                                    <?php if ( has_post_thumbnail() ) : ?>
                                        <?php the_post_thumbnail( 'large', array( 'class' => 'w-full h-full object-cover group-hover:scale-105 transition-transform duration-500' ) ); ?>
                                    <?php else : ?>
                                        <div class="w-full h-full bg-gray-200 flex items-center justify-center">
                                            <span class="material-symbols-outlined text-gray-400 text-5xl">image</span>
                                        </div>
                                    <?php endif; ?>
                                </a>
                                <div class="absolute bottom-0 left-0 w-full bg-gradient-to-t from-black/80 to-transparent p-4">
                                    <h2 class="text-[18px] font-bold text-white mb-2 leading-snug group-hover:text-primary transition-colors line-clamp-2">
                                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                    </h2>
                                    <div class="flex items-center gap-3 text-gray-300 text-[12px]">
                                        <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">person</span> <?php the_author(); ?></span>
                                        <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">schedule</span> <?php echo get_the_date(); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php wp_reset_postdata(); endif; ?>

                        <!-- Side Featured -->
                        <?php if ( ! empty( $side_posts ) ) : ?>
                            <div class="md:col-span-2 flex flex-col gap-3">
                                <?php foreach ( $side_posts as $post ) : setup_postdata( $post ); ?>
                                    <div <?php post_class("flex gap-3 group"); ?>>
                                        <a href="<?php the_permalink(); ?>" class="w-[110px] h-[75px] shrink-0 overflow-hidden rounded-md relative">
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
                    if ( have_posts() ) :
                        while ( have_posts() ) : the_post(); ?>
                            
                            <article <?php post_class("flex flex-col sm:flex-row gap-5 group pb-6 border-b border-gray-100 last:border-0"); ?>>
                                <a href="<?php the_permalink(); ?>" class="w-full sm:w-[260px] shrink-0 overflow-hidden rounded-md">
                                    <?php if ( has_post_thumbnail() ) : ?>
                                        <?php the_post_thumbnail( 'medium_large', array( 'class' => 'w-full h-auto aspect-[16/9] object-cover group-hover:scale-105 transition-transform duration-500' ) ); ?>
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
                                        <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">person</span> <?php the_author(); ?></span>
                                        <span class="flex items-center gap-1"><span class="material-symbols-outlined text-[14px]">schedule</span> <?php echo get_the_date(); ?></span>
                                    </div>
                                    <div class="text-gray-600 text-[14px] line-clamp-3 mb-2">
                                        <?php the_excerpt(); ?>
                                    </div>
                                    <div>
                                        <a href="<?php the_permalink(); ?>" class="text-[13px] text-primary hover:underline">Xem thêm</a>
                                    </div>
                                </div>
                            </article>

                        <?php endwhile;
                    endif;
                    ?>
                </div>

                <!-- Pagination -->
                <div class="mt-8 flex justify-center border-t border-gray-100 pt-8">
                    <?php
                    the_posts_pagination( array(
                        'mid_size'  => 2,
                        'prev_text' => '<span class="material-symbols-outlined">chevron_left</span>',
                        'next_text' => '<span class="material-symbols-outlined">chevron_right</span>',
                        'class'     => 'viomi-pagination'
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
        </div>

        <!-- Sidebar (Right col) -->
        <aside class="lg:col-span-1">
            <div class="sticky top-6">
                <?php if ( is_active_sidebar( 'blog-sidebar' ) ) : ?>
                    <?php dynamic_sidebar( 'blog-sidebar' ); ?>
                <?php else : ?>
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
                            <div class="w-[100px] h-[65px] shrink-0 rounded-md overflow-hidden bg-gray-200 flex items-center justify-center relative">
                                <?php if ( has_post_thumbnail() ) : ?>
                                    <?php the_post_thumbnail( 'thumbnail', array('class' => 'w-full h-full object-cover group-hover:scale-110 transition-transform duration-500') ); ?>
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
                <?php endif; ?>
            </div>
        </aside>

    </div>
</main>

<?php
// Thêm CSS tuỳ chỉnh cho phân trang Pagination để chuẩn Tailwind
add_action('wp_footer', function() {
    ?>
    <style>
        .viomi-pagination .nav-links {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .viomi-pagination .page-numbers {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 0.5rem;
            background-color: #f3f4f6; /* gray-100 */
            color: #4b5563; /* gray-600 */
            font-weight: 500;
            transition: all 0.2s;
        }
        .viomi-pagination .page-numbers:hover {
            background-color: #e5e7eb; /* gray-200 */
            color: #008198; /* primary */
        }
        .viomi-pagination .page-numbers.current {
            background-color: #008198; /* primary */
            color: white;
            font-weight: 700;
            box-shadow: 0 4px 6px -1px rgba(0, 129, 152, 0.2);
        }
        .viomi-pagination .page-numbers.dots {
            background-color: transparent;
            pointer-events: none;
        }
    </style>
    <?php
}, 100);
?>

<?php get_footer(); ?>
