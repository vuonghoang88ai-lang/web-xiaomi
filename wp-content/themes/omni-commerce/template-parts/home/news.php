<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

/**
 * Template part: Blog / Tin tức nổi bật
 * Hiển thị 4 bài viết blog mới nhất.
 * Fallback: 4 card mock tĩnh khi chưa có bài viết.
 */
$news_title = function_exists( 'get_field' ) ? ( get_field( 'mi_home_news_title', 'option' ) ?: __( 'Tin tức nổi bật', 'omni-commerce' ) ) : __( 'Tin tức nổi bật', 'omni-commerce' );
?>
<!-- 5. Blog Section -->
<section class="py-12 bg-white hidden md:block">
    <div class="max-w-[1440px] mx-auto px-4">
        <h2 class="text-[24px] leading-tight font-black text-primary border-l-4 border-secondary pl-3 mb-6 uppercase tracking-tight"><?php echo esc_html( $news_title ); ?></h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-5">
            <?php
            $news_query = new WP_Query( [
                'post_type'           => 'post',
                'posts_per_page'      => 4,
                'ignore_sticky_posts' => true,
            ] );

            if ( $news_query->have_posts() ) :
                while ( $news_query->have_posts() ) : $news_query->the_post();
            ?>
            <!-- Blog Card (Live) -->
            <a href="<?php the_permalink(); ?>" class="flex flex-col group cursor-pointer block rounded-2xl border border-slate-100 p-2.5 shadow-[0_8px_18px_-16px_rgba(15,23,42,0.25)] hover:shadow-md transition-all duration-300 hover:-translate-y-1">
                <div class="aspect-[4/3] rounded-xl overflow-hidden bg-surface-container-highest mb-3">
                    <?php if ( has_post_thumbnail() ) : ?>
                        <?php the_post_thumbnail( 'medium', [ 'class' => 'w-full h-full object-cover group-hover:scale-105 transition-transform duration-500' ] ); ?>
                    <?php else : ?>
                        <img loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" src="https://placehold.co/400x300?text=News" alt="<?php echo esc_attr( get_the_title() ); ?>"/>
                    <?php endif; ?>
                </div>
                <div class="flex items-center gap-2 text-outline text-[12px] mb-2">
                    <span class="material-symbols-outlined text-[14px]">schedule</span>
                    <span><?php echo get_the_date(); ?></span>
                </div>
                <h3 class="font-bold text-[14px] text-slate-800 group-hover:text-primary transition-colors line-clamp-2 leading-[1.5] mb-1"><?php the_title(); ?></h3>
            </a>
            <?php
                endwhile;
                wp_reset_postdata();
            else :
                // Fallback 4 mock cards chuẩn khi chưa có bài viết nào
                $mock_posts = [
                    [
                        'img'   => 'https://lh3.googleusercontent.com/aida-public/AB6AXuAcMvFpNERobRGcNkLeDXek73vDrB8hoqNyDbn-5BKIKR_6A9fMhPu0Z31ui-Xm5H4DVMTA26boMGqTdkzob9QC906rhyX68JM27LJeTK7nRNibw1yNdIY5rEKv8X7YLgqFA2EASdIXY9pP935BXq_XWeqJk5-oH39jSvtFlnYTDq79c6vwXzXVfrF0qaoRg6LA4pQGBllV6Vn6UMOOAqWxKpLdjP756wSfmSONon5ve0op_TUPc7rM',
                        'date'  => '24/05/2025',
                        'title' => 'Những lý do nên sở hữu Tivi Xiaomi 85 inch ngay hôm nay',
                    ],
                    [
                        'img'   => 'https://lh3.googleusercontent.com/aida-public/AB6AXuAs7q1NOzItCLJpsrFy80wNNA0bG0Z4gFzxxZ122yJi5VoUp0slrXKoB8rYmtE6qoZ6A0qWb2Ou_DxY_j_0ViBXCdq4QUos47NK4Nv8xpDa9WiO987KNUzU4qs-nzucsV4ij5UWoXhaYEO274vbq2wEjvwrVs8V9ksmRFfH_l33676dVkQX-xkGFIVa3umIP0N8-RZS4FLjZiP84lC7rm1TEsDetmSSBv4xa1NzcJv_rROrSGSlmkCP',
                        'date'  => '18/05/2025',
                        'title' => 'Đánh giá Tivi Xiaomi A Pro Series 2025 có gì mới?',
                    ],
                    [
                        'img'   => 'https://lh3.googleusercontent.com/aida-public/AB6AXuB9kb5CZbP7UBqwxNtd69C0kIzqURwlEV-zR0c5kBeq00QMaZVa9WMn2d4PoAAhEuojA1iH4796JHQsIRb8kwnZPztQoU34ElyFHX4x9fmdgZd_kwZGwniHXUEwHK_qsv6L18ipeaHIZQAB5alXdxkJHclRKy6pECnJj7_ILak4Dqnu9ZAynXPu6TDZwAhxE37vxu2btWpszQG5EvbZ7tuOWsOruJuhznehDrqk4MheaGiihOUVW7Ll',
                        'date'  => '10/05/2025',
                        'title' => 'Hướng dẫn kết nối Mihome cho Tủ lạnh Xiaomi 430L',
                    ],
                    [
                        'img'   => 'https://lh3.googleusercontent.com/aida-public/AB6AXuB8JxGNdYdbbCRM33s-AGrMffN258o6ql3LH2M13omqmnEvUxjfSt0Z7y-nFa7cn38NB0hevzY4_AJvrRzKdVr7jWJ3g-1q-ex1vrfg92AY54xFsKm15a4S2IlbjIxhkBYeqwPft8D7bRsSOj21K4AMde0gVzl1u3ruijBuHpeSrnwG_KR0n1PAeJCH3DNTMBa0ugEW8DdjfvvidctGJydnwkHpmbGosAxYFNEuuxYa4IhZJrD1wa7A',
                        'date'  => '02/05/2025',
                        'title' => 'Top 5 thiết bị gia đình Xiaomi đáng mua nhất năm 2025',
                    ],
                ];
                foreach ( $mock_posts as $mock ) :
            ?>
            <!-- Blog Card (Fallback Mock) -->
            <div class="flex flex-col group cursor-pointer rounded-2xl border border-slate-100 p-2.5 shadow-[0_8px_18px_-16px_rgba(15,23,42,0.25)] hover:shadow-md transition-all duration-300 hover:-translate-y-1">
                <div class="aspect-[4/3] rounded-xl overflow-hidden bg-surface-container-highest mb-3">
                    <img loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" src="<?php echo esc_url( $mock['img'] ); ?>" alt="<?php echo esc_attr( $mock['title'] ); ?>"/>
                </div>
                <div class="flex items-center gap-2 text-outline text-[12px] mb-2">
                    <span class="material-symbols-outlined text-[14px]">schedule</span>
                    <span><?php echo esc_html( $mock['date'] ); ?></span>
                </div>
                <h3 class="font-bold text-[14px] text-slate-800 group-hover:text-primary transition-colors line-clamp-2 leading-[1.5] mb-1"><?php echo esc_html( $mock['title'] ); ?></h3>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>
