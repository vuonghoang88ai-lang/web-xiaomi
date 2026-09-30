<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

/**
 * Template part for displaying hero section
 */
?>
<!-- 2. Hero Section -->
<style>
    /* Mobile-First: Ẩn cột phụ trên màn hình nhỏ để tránh UX Bloat */
    @media (max-width: 899px) {
        .mi-hero-secondary { display: none !important; }
    }
</style>
<section class="max-w-[1440px] mx-auto px-4 py-6 grid grid-cols-1 lg:grid-cols-4 gap-4">
    <!-- Left Column: Features -->
    <aside class="lg:col-span-1 flex flex-col gap-3 order-2 lg:order-1 mi-hero-secondary h-full">
        <div class="bg-white border border-secondary/80 p-2.5 rounded-2xl flex flex-col gap-2.5 h-full shadow-sm">
            <?php if (have_rows('mi_hero_features', 'option')): ?>
                <?php while (have_rows('mi_hero_features', 'option')): the_row(); 
                    $icon = get_sub_field('icon');
                    $title = get_sub_field('title');
                    $desc = get_sub_field('desc');
                ?>
                <div class="flex items-center gap-2.5 p-2 bg-gradient-to-r from-white to-slate-50 rounded-xl border border-xiaomi-yellow/80 shadow-[0_2px_10px_rgba(15,23,42,0.04)]">
                    <span class="material-symbols-outlined text-secondary text-[20px]"><?php echo esc_html($icon); ?></span>
                    <div>
                        <div class="font-bold text-[13px] leading-snug text-slate-800"><?php echo esc_html($title); ?></div>
                        <div class="text-[11px] text-slate-600 leading-relaxed"><?php echo esc_html($desc); ?></div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <!-- Default layout (4 items to fill vertical gap) -->
                <div class="flex items-center gap-3 p-2.5 bg-gradient-to-r from-white to-slate-50 rounded-xl border border-xiaomi-yellow/80 shadow-[0_2px_10px_rgba(15,23,42,0.04)] hover:shadow-md transition-shadow">
                    <span class="material-symbols-outlined text-secondary text-[22px]">workspace_premium</span>
                    <div>
                        <div class="font-bold text-[13px] leading-snug text-slate-800"><?php esc_html_e('100% Chính hãng', 'omni-commerce'); ?></div>
                        <div class="text-[11px] text-slate-600 leading-relaxed"><?php esc_html_e('Cam kết chất lượng Xiaomi', 'omni-commerce'); ?></div>
                    </div>
                </div>
                <div class="flex items-center gap-3 p-2.5 bg-gradient-to-r from-white to-slate-50 rounded-xl border border-xiaomi-yellow/80 shadow-[0_2px_10px_rgba(15,23,42,0.04)] hover:shadow-md transition-shadow">
                    <span class="material-symbols-outlined text-secondary text-[22px]">local_shipping</span>
                    <div>
                        <div class="font-bold text-[13px] leading-snug text-slate-800"><?php esc_html_e('Giao Hàng Siêu Tốc', 'omni-commerce'); ?></div>
                        <div class="text-[11px] text-slate-600 leading-relaxed"><?php esc_html_e('Nhận hàng trong 2 giờ', 'omni-commerce'); ?></div>
                    </div>
                </div>
                <div class="flex items-center gap-3 p-2.5 bg-gradient-to-r from-white to-slate-50 rounded-xl border border-xiaomi-yellow/80 shadow-[0_2px_10px_rgba(15,23,42,0.04)] hover:shadow-md transition-shadow">
                    <span class="material-symbols-outlined text-secondary text-[22px]">support_agent</span>
                    <div>
                        <div class="font-bold text-[13px] leading-snug text-slate-800"><?php esc_html_e('Bảo Hành Tận Nơi', 'omni-commerce'); ?></div>
                        <div class="text-[11px] text-slate-600 leading-relaxed"><?php esc_html_e('Hỗ trợ kỹ thuật 24/7', 'omni-commerce'); ?></div>
                    </div>
                </div>
                <div class="flex items-center gap-3 p-2.5 bg-gradient-to-r from-white to-slate-50 rounded-xl border border-xiaomi-yellow/80 shadow-[0_2px_10px_rgba(15,23,42,0.04)] hover:shadow-md transition-shadow">
                    <span class="material-symbols-outlined text-secondary text-[22px]">credit_score</span>
                    <div>
                        <div class="font-bold text-[13px] leading-snug text-slate-800"><?php esc_html_e('Trả Góp 0%', 'omni-commerce'); ?></div>
                        <div class="text-[11px] text-slate-600 leading-relaxed"><?php esc_html_e('Duyệt hồ sơ siêu tốc 5 phút', 'omni-commerce'); ?></div>
                    </div>
                </div>
            <?php endif; ?>

            <?php
            $address_title = get_field('mi_hero_address_title', 'option') ?: 'ĐỊA CHỈ SHOWROOM';
            $address_text = get_field('mi_hero_address_text', 'option') ?: '41 Khuất Duy Tiến, HN';
            $hotline_title = get_field('mi_hero_hotline_title', 'option') ?: 'HOTLINE HỖ TRỢ 24/7';
            // Ưu tiên lấy hotline từ field mi_hotline_number (đăng ký ở group_mi_header_footer)
            $hotline_text = get_field('mi_hotline_number', 'option') ?: '0822.83.4444';
            ?>
            <div class="mt-auto bg-xiaomi-yellow/10 border border-xiaomi-yellow/80 py-2 px-3 rounded-xl text-center">
                <div class="text-[10px] font-bold uppercase text-tertiary"><?php echo esc_html($address_title); ?></div>
                <div class="text-[13px] font-semibold text-primary mt-0.5"><?php echo esc_html($address_text); ?></div>
            </div>
            <div class="bg-secondary text-white py-2 px-3 rounded-xl text-center">
                <div class="text-[10px] uppercase tracking-wide"><?php echo esc_html($hotline_title); ?></div>
                <div class="text-base font-black mt-0.5"><?php echo esc_html($hotline_text); ?></div>
            </div>
        </div>
    </aside>

    <!-- Middle Column: Slider & Tabs -->
    <section class="lg:col-span-2 flex flex-col gap-3 order-1 lg:order-2 h-full">
        <style>
            .mi-slider-container {
                position: relative;
                width: 100%;
                height: 100%;
                overflow: hidden;
            }
            .mi-slider-track {
                display: flex;
                height: 100%;
                transition: transform 0.5s ease-in-out;
            }
            .mi-slide {
                width: 100%;
                flex: 0 0 100%;
                height: 100%;
            }
            .mi-slide img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                display: block;
            }
            .mi-slider-dots {
                position: absolute;
                bottom: 12px;
                left: 50%;
                transform: translateX(-50%);
                display: flex;
                gap: 8px;
                z-index: 10;
            }
            .mi-dot {
                width: 8px;
                height: 6px;
                background-color: rgba(255, 255, 255, 0.5);
                border-radius: 9999px;
                cursor: pointer;
                transition: all 0.3s ease;
            }
            .mi-dot.active {
                width: 32px;
                background-color: rgba(255, 255, 255, 1);
            }
            .mi-slider-nav {
                position: absolute;
                top: 50%;
                left: 0;
                right: 0;
                transform: translateY(-50%);
                display: flex;
                justify-content: space-between;
                padding: 0 16px;
                opacity: 0;
                transition: opacity 0.3s;
                pointer-events: none;
                z-index: 10;
            }
            .mi-slider-container:hover .mi-slider-nav {
                opacity: 1;
            }
            .mi-slider-btn {
                background: rgba(255, 255, 255, 0.8);
                border: none;
                border-radius: 50%;
                width: 36px;
                height: 36px;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
                pointer-events: auto;
                transition: background 0.3s;
                color: #1b1c1c;
            }
            .mi-slider-btn span {
                color: #1b1c1c;
                font-size: 20px;
            }
            .mi-slider-btn:hover {
                background: rgba(255, 255, 255, 1);
            }
            /* Hide scrollbar for tabs */
            #mi-hero-tabs-container::-webkit-scrollbar {
                display: none;
            }
            #mi-hero-tabs-container {
                -ms-overflow-style: none;
                scrollbar-width: none;
            }
        </style>
        <div class="flex-1 w-full min-h-[300px] lg:min-h-0 rounded-2xl bg-surface-container-highest shadow-sm overflow-hidden border border-slate-100 relative">
            <div class="mi-slider-container absolute inset-0" id="heroSlider">
                <div class="mi-slider-track" id="heroSliderTrack">
                    <?php 
                    $slides = [];
                    
                    // Fetch banners from ACF
                    $main_banner = get_field('mi_home_main_banner', 'option') ?: get_field('mi_home_main_banner');
                    $sub_banner_1 = get_field('mi_home_sub_banner_1', 'option') ?: get_field('mi_home_sub_banner_1');
                    $sub_banner_2 = get_field('mi_home_sub_banner_2', 'option') ?: get_field('mi_home_sub_banner_2');

                    if ($main_banner) {
                        $slides[] = ['image' => $main_banner, 'link' => get_field('mi_home_main_banner_link', 'option') ?: (get_field('mi_home_main_banner_link') ?: '#')];
                    }
                    if ($sub_banner_1) {
                        $slides[] = ['image' => $sub_banner_1, 'link' => get_field('mi_home_sub_banner_1_link', 'option') ?: (get_field('mi_home_sub_banner_1_link') ?: '#')];
                    }
                    if ($sub_banner_2) {
                        $slides[] = ['image' => $sub_banner_2, 'link' => get_field('mi_home_sub_banner_2_link', 'option') ?: (get_field('mi_home_sub_banner_2_link') ?: '#')];
                    }

                    // Fallback to mi_hero_slider if specific banner fields are not set
                    if (empty($slides) && have_rows('mi_hero_slider', 'option')) {
                        while (have_rows('mi_hero_slider', 'option')) {
                            the_row();
                            $slides[] = [
                                'image' => get_sub_field('image'),
                                'link' => get_sub_field('link')
                            ];
                        }
                    } 
                    
                    // Fallback static images if everything is empty
                    if (empty($slides)) {
                        $slides = [
                            [
                                'image' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuA7Epdywzm5MgL-gcNkcW7qMRZojNtGUacOM4WJXAaRiYGu5ZYsbxCFbqoCg_5o1RioEYsT66H_3fEmoMtRKory9QhGm-Bea7Z-5grRyQy5amFZkKGQ0eCtkVjkyVaH-qFOsGjHzK2e-m90cdP4HUWSpYdg5xbA0CPZyHUpubXEpc5gk66Va4k6rrmupelJRgNNqW_id-rEIqW5UCXMqp9nA5O9UreuhKCmYt29LTODukSqg7RHsP7P',
                                'link' => '#'
                            ],
                            [
                                'image' => 'https://placehold.co/1200x675/008198/FFFFFF?text=Xiaomi+Smart+Home',
                                'link' => '#'
                            ],
                            [
                                'image' => 'https://placehold.co/1200x675/cc0000/FFFFFF?text=Xiaomi+Smartphone+Series',
                                'link' => '#'
                            ]
                        ];
                    }
                    
                    foreach ($slides as $slide):
                    ?>
                    <div class="mi-slide">
                        <a href="<?php echo esc_url($slide['link'] ?: '#'); ?>" style="display: block; width: 100%; height: 100%;">
                            <img fetchpriority="high" src="<?php echo esc_url($slide['image']); ?>" alt="<?php esc_attr_e('Xiaomi Banner', 'omni-commerce'); ?>" />
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="mi-slider-nav">
                    <button class="mi-slider-btn" id="heroPrevBtn"><span class="material-symbols-outlined">chevron_left</span></button>
                    <button class="mi-slider-btn" id="heroNextBtn"><span class="material-symbols-outlined">chevron_right</span></button>
                </div>
                
                <div class="mi-slider-dots" id="heroSliderDots">
                    <?php foreach ($slides as $index => $slide): ?>
                    <div class="mi-dot <?php echo $index === 0 ? 'active' : ''; ?>" data-index="<?php echo $index; ?>"></div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const track = document.getElementById('heroSliderTrack');
                const slides = track.querySelectorAll('.mi-slide');
                const prevBtn = document.getElementById('heroPrevBtn');
                const nextBtn = document.getElementById('heroNextBtn');
                const dots = document.querySelectorAll('#heroSliderDots .mi-dot');
                let currentIndex = 0;
                const totalSlides = slides.length;
                let autoPlayInterval;

                if(totalSlides <= 1) {
                    if(prevBtn) prevBtn.style.display = 'none';
                    if(nextBtn) nextBtn.style.display = 'none';
                    return;
                }

                function updateSlider() {
                    track.style.transform = `translateX(-${currentIndex * 100}%)`;
                    dots.forEach((dot, index) => {
                        dot.classList.toggle('active', index === currentIndex);
                    });
                }

                function nextSlide() {
                    currentIndex = (currentIndex + 1) % totalSlides;
                    updateSlider();
                }

                function prevSlide() {
                    currentIndex = (currentIndex - 1 + totalSlides) % totalSlides;
                    updateSlider();
                }

                if (nextBtn) {
                    nextBtn.addEventListener('click', () => {
                        nextSlide();
                        resetAutoPlay();
                    });
                }

                if (prevBtn) {
                    prevBtn.addEventListener('click', () => {
                        prevSlide();
                        resetAutoPlay();
                    });
                }

                dots.forEach(dot => {
                    dot.addEventListener('click', (e) => {
                        currentIndex = parseInt(e.target.getAttribute('data-index'));
                        updateSlider();
                        resetAutoPlay();
                    });
                });

                function startAutoPlay() {
                    autoPlayInterval = setInterval(nextSlide, 5000);
                }

                function resetAutoPlay() {
                    clearInterval(autoPlayInterval);
                    startAutoPlay();
                }

                startAutoPlay();
            });
        </script>

        <!-- Horizontal Tab Menu -->
        <div id="mi-hero-tabs-container" class="bg-white border-b border-outline-variant flex gap-6 overflow-x-auto whitespace-nowrap scrollbar-hide px-2 relative" style="scroll-behavior: auto;">
            <?php if (have_rows('mi_hero_tabs', 'option')): ?>
                <?php while (have_rows('mi_hero_tabs', 'option')): the_row(); 
                    $title = get_sub_field('title');
                    $link = get_sub_field('link');
                    $active = get_sub_field('active');
                    $class = $active ? 'tab-active py-3 text-label-md' : 'py-3 text-label-md text-outline hover:text-primary transition-colors';
                ?>
                <a class="<?php echo esc_attr($class); ?>" href="<?php echo esc_url($link); ?>"><?php echo esc_html($title); ?></a>
                <?php endwhile; ?>
            <?php else: 
                $tu_lanh_term = get_term_by('slug', 'tu-lanh-xiaomi', 'product_cat') ?: get_term_by('slug', 'tu-lanh-xiaomi', 'category');
                $tu_lanh_link = $tu_lanh_term && !is_wp_error($tu_lanh_term) ? get_term_link($tu_lanh_term) : '#';
                
                $tivi_term = get_term_by('slug', 'tivi-xiaomi', 'product_cat') ?: get_term_by('slug', 'tivi-xiaomi', 'category');
                $tivi_link = $tivi_term && !is_wp_error($tivi_term) ? get_term_link($tivi_term) : '#';

                $gia_dinh_term = get_term_by('slug', 'thiet-bi-gia-dinh', 'product_cat') ?: get_term_by('slug', 'thiet-bi-gia-dinh', 'category');
                $gia_dinh_link = $gia_dinh_term && !is_wp_error($gia_dinh_term) ? get_term_link($gia_dinh_term) : '#';
            ?>
                <a class="tab-active py-3 text-label-md" href="#">Trải nghiệm mua hàng</a>
                <a class="py-3 text-label-md text-outline hover:text-primary transition-colors" href="<?php echo esc_url($tu_lanh_link); ?>">TỦ LẠNH XIAOMI</a>
                <a class="py-3 text-label-md text-outline hover:text-primary transition-colors" href="<?php echo esc_url($tivi_link); ?>">TIVI XIAOMI</a>
                <a class="py-3 text-label-md text-outline hover:text-primary transition-colors" href="<?php echo esc_url($gia_dinh_link); ?>">THIẾT BỊ GIA ĐÌNH</a>
                <a class="py-3 text-label-md text-outline hover:text-primary transition-colors" href="#product-row-section">ƯU ĐÃI HOT (SCROLL)</a>
                <a class="py-3 text-label-md text-outline hover:text-primary transition-colors" href="<?php echo esc_url(home_url('/lien-he/')); ?>">HỆ THỐNG CỬA HÀNG</a>
            <?php endif; ?>
        </div>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const tabContainer = document.getElementById('mi-hero-tabs-container');
                if (tabContainer) {
                    let scrollDirection = 1; // 1 for right, -1 for left
                    let isHovered = false;

                    tabContainer.addEventListener('mouseenter', () => isHovered = true);
                    tabContainer.addEventListener('mouseleave', () => isHovered = false);
                    tabContainer.addEventListener('touchstart', () => isHovered = true);
                    tabContainer.addEventListener('touchend', () => isHovered = false);

                    setInterval(() => {
                        if (isHovered) return;
                        
                        // Use a small threshold to account for decimal scrollLeft values
                        const maxScrollLeft = tabContainer.scrollWidth - tabContainer.clientWidth;
                        if (maxScrollLeft <= 2) return; // No overflow

                        tabContainer.scrollLeft += (scrollDirection * 0.5);

                        if (tabContainer.scrollLeft >= maxScrollLeft - 1) {
                            scrollDirection = -1;
                        } else if (tabContainer.scrollLeft <= 0) {
                            scrollDirection = 1;
                        }
                    }, 20); 
                }
            });
        </script>
    </section>

    <!-- Right Column: Ads & News -->
    <aside class="lg:col-span-1 flex flex-col gap-4 order-3 mi-hero-secondary">
        <?php 
        $sidebar = get_field('mi_hero_sidebar', 'option');
        $banner_image = $sidebar['banner_image'] ?? 'https://lh3.googleusercontent.com/aida-public/AB6AXuB9dOEhonr8974Fd8r5lXeIpO2RXPwNg7wGwrBAYKZ5V8vHMIde-UzGeVyigPPIXC4Gl3NL1xO9cwoMwgJiFvgUHs9BKpOq2lckfKDerYsa5fLOe5b7mBtVJIoWNGn5luixVGdDbdfnDRazh678jrrZi2tbwrSpDlpl5hYZmqO4j8xtsFQFSy666e1E5O14NG_LSmsZBBYQ8U0HOU9PEzTiR8BG_LAUO_xkcKj3CMP_OaolC7O1RYrr';
        $banner_title = $sidebar['banner_title'] ?? 'Tủ lạnh Xiaomi 430L - Công nghệ bù ẩm';
        $news_title = $sidebar['news_title'] ?? 'Tin tức nổi bật';
        ?>
        <div class="flex-1 bg-surface-container-highest rounded-xl overflow-hidden relative min-h-[140px]">
            <div class="w-full h-full bg-cover bg-center" style="background-image: url('<?php echo esc_url($banner_image); ?>')"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent flex items-end p-4">
                <div class="text-white font-bold text-label-md"><?php echo esc_html($banner_title); ?></div>
            </div>
        </div>
        
        <div class="bg-white border border-outline-variant rounded-xl p-4 flex flex-col gap-3">
            <div class="flex items-center justify-between border-b pb-2 mb-1">
                <h3 class="font-bold text-primary text-label-md uppercase"><?php echo esc_html($news_title); ?></h3>
                <a href="<?php echo esc_url( home_url('/tin-tuc/') ); ?>" aria-label="Xem tất cả tin tức" class="text-outline hover:text-primary transition-colors">
                    <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                </a>
            </div>
            
            <?php 
            $news_query = new WP_Query([
                'post_type' => 'post',
                'posts_per_page' => 2,
                'ignore_sticky_posts' => true
            ]);

            if ($news_query->have_posts()):
                while ($news_query->have_posts()): $news_query->the_post();
            ?>
            <a href="<?php the_permalink(); ?>" class="flex gap-3 items-center group cursor-pointer">
                <div class="w-16 h-12 bg-surface-container-highest rounded overflow-hidden flex-shrink-0">
                    <?php if (has_post_thumbnail()): ?>
                        <?php the_post_thumbnail('thumbnail', ['class' => 'w-full h-full object-cover']); ?>
                    <?php else: ?>
                        <img loading="lazy" class="w-full h-full object-cover" src="https://placehold.co/100x100?text=News" alt="News banner"/>
                    <?php endif; ?>
                </div>
                <p class="text-label-sm font-medium line-clamp-2 group-hover:text-primary"><?php the_title(); ?></p>
            </a>
            <?php 
                endwhile;
                wp_reset_postdata();
            else:
            ?>
            <!-- Fallback static content if no posts found -->
            <div class="flex gap-3 items-center group cursor-pointer">
                <div class="w-16 h-12 bg-surface-container-highest rounded overflow-hidden flex-shrink-0">
                    <img loading="lazy" class="w-full h-full object-cover" src="https://lh3.googleusercontent.com/aida-public/AB6AXuAs7q1NOzItCLJpsrFy80wNNA0bG0Z4gFzxxZ122yJi5VoUp0slrXKoB8rYmtE6qoZ6A0qWb2Ou_DxY_j_0ViBXCdq4QUos47NK4Nv8xpDa9WiO987KNUzU4qs-nzucsV4ij5UWoXhaYEO274vbq2wEjvwrVs8V9ksmRFfH_l33676dVkQX-xkGFIVa3umIP0N8-RZS4FLjZiP84lC7rm1TEsDetmSSBv4xa1NzcJv_rROrSGSlmkCP" alt="Promo banner"/>
                </div>
                <p class="text-label-sm font-medium line-clamp-2 group-hover:text-primary">Đánh giá Tivi Xiaomi A Pro Series 2024 có gì mới?</p>
            </div>
            <div class="flex gap-3 items-center group cursor-pointer">
                <div class="w-16 h-12 bg-surface-container-highest rounded overflow-hidden flex-shrink-0">
                    <img loading="lazy" class="w-full h-full object-cover" src="https://lh3.googleusercontent.com/aida-public/AB6AXuB9kb5CZbP7UBqwxNtd69C0kIzqURwlEV-zR0c5kBeq00QMaZVa9WMn2d4PoAAhEuojA1iH4796JHQsIRb8kwnZPztQoU34ElyFHX4x9fmdgZd_kwZGwniHXUEwHK_qsv6L18ipeaHIZQAB5alXdxkJHclRKy6pECnJj7_ILak4Dqnu9ZAynXPu6TDZwAhxE37vxu2btWpszQG5EvbZ7tuOWsOruJuhznehDrqk4MheaGiihOUVW7Ll" alt="Promo banner"/>
                </div>
                <p class="text-label-sm font-medium line-clamp-2 group-hover:text-primary">Hướng dẫn kết nối Mihome cho Tủ lạnh Xiaomi</p>
            </div>
            <?php endif; ?>
        </div>
    </aside>
</section>
