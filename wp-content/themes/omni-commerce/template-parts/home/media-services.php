<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

/**
 * Template part for displaying Media & Services section
 */
$sale_banner = get_field('mi_videos_sale_banner', 'option') ?: 'BIG SALE TIVI XIAOMI 85 INCH CHỈ CÒN 21.990.000Đ';
?>
<!-- 3. Media & Services -->
<section class="w-full mb-8 hidden md:block">
    <style>
        @keyframes marquee {
            0%   { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }
        .mi-marquee-container {
            display: flex;
            overflow: hidden;
            white-space: nowrap;
            width: 100%;
        }
        .mi-marquee-text {
            display: flex;
            flex-shrink: 0;
            min-width: 100%;
            animation: marquee 20s linear infinite;
            will-change: transform;
        }
    </style>
    <!-- Big Sale Banner -->
    <div class="bg-secondary w-full py-4 relative mi-marquee-container">
        <div class="absolute inset-0 opacity-10 bg-[radial-gradient(#fff_1px,transparent_1px)] [background-size:16px_16px]">
        </div>
        <!-- 2 bản text nối tiếp nhau để marquee liền mạch trên mọi độ rộng màn hình -->
        <div class="relative z-10 flex mi-marquee-text">
            <span class="text-headline-lg font-black text-white italic uppercase tracking-widest drop-shadow-[2px_2px_0px_#fbbc05] pr-24"><?php echo esc_html($sale_banner); ?></span>
            <span class="text-headline-lg font-black text-white italic uppercase tracking-widest drop-shadow-[2px_2px_0px_#fbbc05] pr-24" aria-hidden="true"><?php echo esc_html($sale_banner); ?></span>
        </div>
    </div>
    
    <div class="max-w-[1440px] mx-auto px-4 mt-6 flex md:grid md:grid-cols-3 gap-5 overflow-x-auto snap-x snap-mandatory scrollbar-hide pb-2">
        <!-- Video Grid -->
        <?php
        // Try to get YouTube video thumbnail or fallback to static images
        $video_ids = [
            get_field('mi_video_1', 'option'),
            get_field('mi_video_2', 'option'),
            get_field('mi_video_3', 'option')
        ];
        
        $fallback_images = [
            content_url( '/uploads/banners/banner-01-tivi.jpg' ),
            content_url( '/uploads/banners/banner-02-tu-lanh.jpg' ),
            content_url( '/uploads/banners/banner-03-gia-dinh.jpg' )
        ];

        foreach($video_ids as $index => $vid): 
            $img_url = $vid ? 'https://img.youtube.com/vi/' . esc_attr($vid) . '/maxresdefault.jpg' : $fallback_images[$index];
            $href = $vid ? 'https://www.youtube.com/watch?v=' . esc_attr($vid) : home_url( '/tin-tuc/' );
            $target = $vid ? '_blank' : '_self';
        ?>
        <a href="<?php echo esc_url($href); ?>" target="<?php echo esc_attr($target); ?>" aria-label="Xem video đánh giá trải nghiệm thiết bị Xiaomi" class="min-w-[85vw] md:min-w-0 snap-center shrink-0 relative aspect-video rounded-2xl overflow-hidden group shadow-sm border border-slate-100 block hover:shadow-md transition-shadow duration-300">
            <img loading="lazy" class="w-full h-full object-cover" src="<?php echo esc_url($img_url); ?>" alt="Video review"/>
            <div class="absolute inset-0 bg-black/20 flex items-center justify-center">
                <div class="bg-secondary w-16 h-12 rounded-xl flex items-center justify-center shadow-lg transform group-hover:scale-110 transition-transform">
                    <span class="material-symbols-outlined text-white text-[32px]" style="font-variation-settings: 'FILL' 1;">play_arrow</span>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    
    <!-- Value Bar -->
    <div class="max-w-[1440px] mx-auto px-4 mt-8 flex md:grid md:grid-cols-3 gap-4 overflow-x-auto snap-x snap-mandatory scrollbar-hide pb-2">
        <?php if (have_rows('mi_value_props', 'option')): ?>
            <?php while (have_rows('mi_value_props', 'option')): the_row(); 
                $icon = get_sub_field('icon');
                $title = get_sub_field('title');
                $desc = get_sub_field('desc');
            ?>
            <div class="min-w-[85vw] md:min-w-0 snap-center shrink-0 flex items-center gap-4 bg-white border border-outline-variant p-6 rounded-lg shadow-sm">
                <div class="size-14 bg-primary/10 text-primary flex items-center justify-center rounded-full shrink-0">
                    <span class="material-symbols-outlined text-[32px]"><?php echo esc_html($icon); ?></span>
                </div>
                <div>
                    <h3 class="font-bold text-on-surface"><?php echo esc_html($title); ?></h3>
                    <p class="text-label-sm text-outline"><?php echo esc_html($desc); ?></p>
                </div>
            </div>
            <?php endwhile; ?>
        <?php else: ?>
            <!-- Fallback Value Props -->
            <div class="min-w-[85vw] md:min-w-0 snap-center shrink-0 flex items-center gap-4 bg-white border border-outline-variant p-6 rounded-lg shadow-sm">
                <div class="size-14 bg-primary/10 text-primary flex items-center justify-center rounded-full shrink-0">
                    <span class="material-symbols-outlined text-[32px]">shopping_cart_checkout</span>
                </div>
                <div>
                    <h3 class="font-bold text-on-surface"><?php esc_html_e( 'Mua hàng dễ dàng', 'omni-commerce' ); ?></h3>
                    <p class="text-label-sm text-outline"><?php esc_html_e( 'Thanh toán linh hoạt & bảo mật', 'omni-commerce' ); ?></p>
                </div>
            </div>
            <div class="min-w-[85vw] md:min-w-0 snap-center shrink-0 flex items-center gap-4 bg-white border border-outline-variant p-6 rounded-lg shadow-sm">
                <div class="size-14 bg-primary/10 text-primary flex items-center justify-center rounded-full shrink-0">
                    <span class="material-symbols-outlined text-[32px]">local_shipping</span>
                </div>
                <div>
                    <h3 class="font-bold text-on-surface"><?php esc_html_e( 'Ship COD toàn quốc', 'omni-commerce' ); ?></h3>
                    <p class="text-label-sm text-outline"><?php esc_html_e( 'Nhận hàng rồi mới thanh toán', 'omni-commerce' ); ?></p>
                </div>
            </div>
            <div class="min-w-[85vw] md:min-w-0 snap-center shrink-0 flex items-center gap-4 bg-white border border-outline-variant p-6 rounded-lg shadow-sm">
                <div class="size-14 bg-primary/10 text-primary flex items-center justify-center rounded-full shrink-0">
                    <span class="material-symbols-outlined text-[32px]">credit_card</span>
                </div>
                <div>
                    <h3 class="font-bold text-on-surface"><?php esc_html_e( 'Trả góp 0%', 'omni-commerce' ); ?></h3>
                    <p class="text-label-sm text-outline"><?php esc_html_e( 'Lãi suất ưu đãi qua thẻ tín dụng', 'omni-commerce' ); ?></p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>
