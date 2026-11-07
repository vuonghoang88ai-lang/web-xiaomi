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
    <!-- Left Column: Features (Co giãn thông minh toàn diện trên Desktop) -->
    <aside class="lg:col-span-1 flex flex-col order-2 lg:order-1 mi-hero-secondary h-full">
        <div class="bg-white border border-secondary/80 p-2.5 xl:p-3 rounded-2xl flex flex-col justify-between h-full shadow-sm">
            <!-- 4 Feature Cards (flex-1 chia đều khoảng cách đứng, triệt tiêu khoảng trống thừa) -->
            <div class="flex-1 flex flex-col justify-between gap-2 xl:gap-2.5 mb-2.5 min-h-0">
                <?php if (have_rows('mi_hero_features', 'option')): ?>
                    <?php while (have_rows('mi_hero_features', 'option')): the_row(); 
                        $icon = get_sub_field('icon');
                        $title = get_sub_field('title');
                        $desc = get_sub_field('desc');
                    ?>
                    <div class="flex-1 min-h-0 flex items-center gap-2 xl:gap-3 p-2 xl:p-2.5 bg-gradient-to-r from-white to-slate-50 rounded-xl border border-xiaomi-yellow/80 shadow-[0_2px_8px_rgba(15,23,42,0.04)] hover:shadow-md hover:border-secondary/60 transition-all">
                        <span class="material-symbols-outlined text-secondary text-[20px] xl:text-[22px] shrink-0"><?php echo esc_html($icon); ?></span>
                        <div class="min-w-0 flex-1">
                            <div class="font-bold text-[12px] xl:text-[13px] leading-snug text-slate-800 line-clamp-1"><?php echo esc_html($title); ?></div>
                            <div class="text-[10px] xl:text-[11px] text-slate-600 leading-tight mt-0.5 line-clamp-1"><?php echo esc_html($desc); ?></div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <!-- Default layout (4 items co giãn đều đặn theo chiều cao slider) -->
                    <div class="flex-1 min-h-0 flex items-center gap-2 xl:gap-3 p-2 xl:p-2.5 bg-gradient-to-r from-white to-slate-50 rounded-xl border border-xiaomi-yellow/80 shadow-[0_2px_8px_rgba(15,23,42,0.04)] hover:shadow-md hover:border-secondary/60 transition-all">
                        <span class="material-symbols-outlined text-secondary text-[20px] xl:text-[22px] shrink-0">workspace_premium</span>
                        <div class="min-w-0 flex-1">
                            <div class="font-bold text-[12px] xl:text-[13px] leading-snug text-slate-800 line-clamp-1"><?php esc_html_e('100% Chính hãng', 'omni-commerce'); ?></div>
                            <div class="text-[10px] xl:text-[11px] text-slate-600 leading-tight mt-0.5 line-clamp-1"><?php esc_html_e('Cam kết chất lượng Xiaomi', 'omni-commerce'); ?></div>
                        </div>
                    </div>
                    <div class="flex-1 min-h-0 flex items-center gap-2 xl:gap-3 p-2 xl:p-2.5 bg-gradient-to-r from-white to-slate-50 rounded-xl border border-xiaomi-yellow/80 shadow-[0_2px_8px_rgba(15,23,42,0.04)] hover:shadow-md hover:border-secondary/60 transition-all">
                        <span class="material-symbols-outlined text-secondary text-[20px] xl:text-[22px] shrink-0">local_shipping</span>
                        <div class="min-w-0 flex-1">
                            <div class="font-bold text-[12px] xl:text-[13px] leading-snug text-slate-800 line-clamp-1"><?php esc_html_e('Giao Hàng Siêu Tốc', 'omni-commerce'); ?></div>
                            <div class="text-[10px] xl:text-[11px] text-slate-600 leading-tight mt-0.5 line-clamp-1"><?php esc_html_e('Nhận hàng trong 2 giờ', 'omni-commerce'); ?></div>
                        </div>
                    </div>
                    <div class="flex-1 min-h-0 flex items-center gap-2 xl:gap-3 p-2 xl:p-2.5 bg-gradient-to-r from-white to-slate-50 rounded-xl border border-xiaomi-yellow/80 shadow-[0_2px_8px_rgba(15,23,42,0.04)] hover:shadow-md hover:border-secondary/60 transition-all">
                        <span class="material-symbols-outlined text-secondary text-[20px] xl:text-[22px] shrink-0">support_agent</span>
                        <div class="min-w-0 flex-1">
                            <div class="font-bold text-[12px] xl:text-[13px] leading-snug text-slate-800 line-clamp-1"><?php esc_html_e('Bảo Hành Tận Nơi', 'omni-commerce'); ?></div>
                            <div class="text-[10px] xl:text-[11px] text-slate-600 leading-tight mt-0.5 line-clamp-1"><?php esc_html_e('Hỗ trợ kỹ thuật 24/7', 'omni-commerce'); ?></div>
                        </div>
                    </div>
                    <div class="flex-1 min-h-0 flex items-center gap-2 xl:gap-3 p-2 xl:p-2.5 bg-gradient-to-r from-white to-slate-50 rounded-xl border border-xiaomi-yellow/80 shadow-[0_2px_8px_rgba(15,23,42,0.04)] hover:shadow-md hover:border-secondary/60 transition-all">
                        <span class="material-symbols-outlined text-secondary text-[20px] xl:text-[22px] shrink-0">credit_score</span>
                        <div class="min-w-0 flex-1">
                            <div class="font-bold text-[12px] xl:text-[13px] leading-snug text-slate-800 line-clamp-1"><?php esc_html_e('Trả Góp 0%', 'omni-commerce'); ?></div>
                            <div class="text-[10px] xl:text-[11px] text-slate-600 leading-tight mt-0.5 line-clamp-1"><?php esc_html_e('Duyệt hồ sơ siêu tốc 5 phút', 'omni-commerce'); ?></div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Bottom: Showroom & Hotline (Cố định cân xứng ở đáy, Hotline có link click) -->
            <?php
            $address_title = get_field('mi_hero_address_title', 'option') ?: 'ĐỊA CHỈ SHOWROOM';
            $address_text = get_field('mi_hero_address_text', 'option') ?: get_field('mi_hf_topbar_address', 'option') ?: '65 Hồ Ba Mẫu, Đống Đa, HN';
            $hotline_title = get_field('mi_hero_hotline_title', 'option') ?: 'HOTLINE HỖ TRỢ 24/7';
            $hotline_text = get_field('mi_hf_topbar_hotline', 'option') ?: get_field('mi_hero_hotline_text', 'option') ?: '0822.83.4444';
            ?>
            <div class="flex flex-col gap-1.5 xl:gap-2 shrink-0">
                <div class="bg-xiaomi-yellow/10 border border-xiaomi-yellow/80 py-1.5 xl:py-2 px-2.5 xl:px-3 rounded-xl text-center">
                    <div class="text-[9px] xl:text-[10px] font-bold uppercase text-tertiary tracking-wider"><?php echo esc_html($address_title); ?></div>
                    <div class="text-[11px] xl:text-[12px] font-semibold text-primary mt-0.5 line-clamp-1"><?php echo esc_html($address_text); ?></div>
                </div>
                <a href="tel:<?php echo esc_attr(str_replace([' ', '.'], '', $hotline_text)); ?>" class="bg-secondary text-white py-1.5 xl:py-2 px-2.5 xl:px-3 rounded-xl text-center block hover:opacity-95 transition-all shadow-sm group cursor-pointer">
                    <div class="text-[9px] xl:text-[10px] uppercase tracking-wider opacity-90"><?php echo esc_html($hotline_title); ?></div>
                    <div class="text-sm xl:text-base font-black mt-0.5 tracking-tight group-hover:scale-105 transition-transform"><?php echo esc_html($hotline_text); ?></div>
                </a>
            </div>
        </div>
    </aside>

    <!-- Middle Column: Slider & Tabs -->
    <section class="lg:col-span-2 flex flex-col gap-3 order-1 lg:order-2 h-full">
        <style>
            .mi-slider-container {
                position: absolute;
                inset: 0;
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

            /* Mobile: slider giữ đúng tỷ lệ 16:9 thay vì fixed height cắt ảnh */
            .mi-hero-slider-wrap {
                position: relative;
                width: 100%;
                border-radius: 1rem;
                overflow: hidden;
                border: 1px solid #f1f5f9;
                box-shadow: 0 1px 3px rgba(0,0,0,.08);
                background: #f8fafc;
            }
            /* Mobile (<1024px): aspect-ratio 16:9 tự nhiên */
            @media (max-width: 1023px) {
                .mi-hero-slider-wrap {
                    aspect-ratio: 16 / 9;
                    height: auto;
                    max-height: 56vw; /* giới hạn trên tablet */
                }
            }
            /* Desktop (≥1024px): chiều cao fill theo grid row (flex-1) */
            @media (min-width: 1024px) {
                .mi-hero-slider-wrap {
                    flex: 1;
                    min-height: 0;
                }
            }
            /* Ảnh banner: contain trên mobile để không bị crop, cover trên desktop */
            @media (max-width: 1023px) {
                .mi-slide img {
                    object-fit: contain !important;
                    background: #0f1927;
                }
            }
        </style>
        <div class="mi-hero-slider-wrap" id="miHeroSliderWrap">
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
                    // Banners đã import vào Media Library (ID 174-178) — quản lý qua WP Admin → Media
                    if (empty($slides)) {
                        $banner_ids = [
                            174 => home_url('/danh-muc/tivi-xiaomi/'),
                            175 => home_url('/danh-muc/tu-lanh-xiaomi/'),
                            176 => home_url('/danh-muc/thiet-bi-gia-dinh/'),
                            177 => home_url('/#product-row-section'),
                            178 => home_url('/lien-he/'),
                        ];
                        foreach ($banner_ids as $att_id => $link) {
                            $img_url = wp_get_attachment_url($att_id);
                            if ($img_url) {
                                $slides[] = ['image' => $img_url, 'link' => $link];
                            }
                        }
                        // Nếu attachment bị xóa, fallback về content_url
                        if (empty($slides)) {
                            $base = content_url('uploads/banners');
                            $slides = [
                                ['image' => $base . '/banner-01-tivi.jpg',     'link' => home_url('/danh-muc/tivi-xiaomi/')],
                                ['image' => $base . '/banner-02-tu-lanh.jpg',  'link' => home_url('/danh-muc/tu-lanh-xiaomi/')],
                                ['image' => $base . '/banner-03-gia-dinh.jpg', 'link' => home_url('/danh-muc/thiet-bi-gia-dinh/')],
                                ['image' => $base . '/banner-04-khuyen-mai.jpg','link' => home_url('/#product-row-section')],
                                ['image' => $base . '/banner-05-uy-tin.jpg',   'link' => home_url('/lien-he/')],
                            ];
                        }
                    }
                    
                    foreach ($slides as $idx => $slide):
                        // Lấy alt text từ Media Library nếu slide có attachment_id
                        $alt_text = '';
                        if ( ! empty($slide['attachment_id']) ) {
                            $alt_text = get_post_meta($slide['attachment_id'], '_wp_attachment_image_alt', true);
                        }
                        if ( empty($alt_text) && ! empty($slide['alt']) ) {
                            $alt_text = $slide['alt'];
                        }
                        if ( empty($alt_text) ) {
                            $alt_text = get_bloginfo('name') . ' - Banner ' . ($idx + 1);
                        }
                    ?>
                    <div class="mi-slide">
                        <a href="<?php echo esc_url($slide['link'] ?: '#'); ?>" style="display: block; width: 100%; height: 100%;" title="<?php echo esc_attr($alt_text); ?>">
                            <img
                                <?php echo $idx === 0 ? 'fetchpriority="high"' : 'loading="lazy"'; ?>
                                src="<?php echo esc_url($slide['image']); ?>"
                                alt="<?php echo esc_attr($alt_text); ?>"
                                width="1200" height="675"
                                style="width:100%;height:100%;object-fit:cover;display:block;"
                            />
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
                <a class="tab-active py-3 text-label-md" href="<?php echo esc_url(home_url('/')); ?>">Trải nghiệm mua hàng</a>
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
        $banner_image = ! empty($sidebar['banner_image']) ? $sidebar['banner_image'] : '';
        if ( empty($banner_image) || stripos($banner_image, 'googleusercontent') !== false ) {
            $banner_image = wp_get_attachment_url(183) ?: content_url('uploads/banners/sidebar-refrigerator.jpg');
        }
        $banner_title = ! empty($sidebar['banner_title']) ? $sidebar['banner_title'] : 'Tủ lạnh Xiaomi 430L - Công nghệ bù ẩm';
        $banner_link  = ! empty($sidebar['banner_link']) ? $sidebar['banner_link'] : home_url('/danh-muc/tu-lanh-xiaomi/');
        $news_title   = ! empty($sidebar['news_title']) ? $sidebar['news_title'] : 'Tin tức nổi bật';
        ?>
        <a href="<?php echo esc_url($banner_link); ?>" class="flex-1 bg-surface-container-highest rounded-2xl overflow-hidden relative min-h-[150px] block group border border-slate-100 shadow-sm hover:shadow-md transition-all cursor-pointer">
            <div class="w-full h-full bg-cover bg-center group-hover:scale-105 transition-transform duration-500" style="background-image: url('<?php echo esc_url($banner_image); ?>')"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/25 to-transparent flex items-end p-3.5">
                <div class="text-white font-bold text-[13px] leading-snug drop-shadow-sm flex items-center justify-between w-full">
                    <span class="line-clamp-1"><?php echo esc_html($banner_title); ?></span>
                    <span class="material-symbols-outlined text-[18px] opacity-80 group-hover:opacity-100 group-hover:translate-x-1 transition-all shrink-0 ml-1">arrow_forward</span>
                </div>
            </div>
            <span class="absolute top-2.5 left-2.5 bg-secondary text-white text-[10px] font-extrabold uppercase px-2 py-0.5 rounded-md shadow-sm tracking-wider">HOT DEAL</span>
        </a>
        
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
                        <img loading="lazy" class="w-full h-full object-cover" src="<?php echo esc_url( content_url( '/uploads/banners/cat-tivi-banner-2.jpg' ) ); ?>" alt="News banner"/>
                    <?php endif; ?>
                </div>
                <p class="text-label-sm font-medium line-clamp-2 group-hover:text-primary"><?php the_title(); ?></p>
            </a>
            <?php 
                endwhile;
                wp_reset_postdata();
            else:
            ?>
            <!-- Fallback static content if no posts found (sử dụng banner nội bộ 100%) -->
            <div class="flex gap-3 items-center group cursor-pointer">
                <div class="w-16 h-12 bg-surface-container-highest rounded overflow-hidden flex-shrink-0">
                    <img loading="lazy" class="w-full h-full object-cover" src="<?php echo esc_url( content_url( '/uploads/banners/cat-tivi-banner-2.jpg' ) ); ?>" alt="Promo banner"/>
                </div>
                <p class="text-label-sm font-medium line-clamp-2 group-hover:text-primary">Đánh giá Tivi Xiaomi A Pro Series 2024 có gì mới?</p>
            </div>
            <div class="flex gap-3 items-center group cursor-pointer">
                <div class="w-16 h-12 bg-surface-container-highest rounded overflow-hidden flex-shrink-0">
                    <img loading="lazy" class="w-full h-full object-cover" src="<?php echo esc_url( content_url( '/uploads/banners/cat-tulanh-banner-2.jpg' ) ); ?>" alt="Promo banner"/>
                </div>
                <p class="text-label-sm font-medium line-clamp-2 group-hover:text-primary">Hướng dẫn kết nối Mihome cho Tủ lạnh Xiaomi</p>
            </div>
            <?php endif; ?>
        </div>
    </aside>
</section>
