<footer class="bg-white">
<!-- Pre-Footer -->
<div class="bg-surface-container-low py-6 md:py-10 px-4">
    <div class="max-w-[1440px] mx-auto grid grid-cols-2 md:grid-cols-4 gap-6 md:gap-8">
        <?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
 
        $trust_badges = get_field('mi_trust_badges', 'option');
        if ($trust_badges) : 
            foreach ($trust_badges as $badge) : 
        ?>
            <div class="flex flex-col items-center text-center">
                <?php if (filter_var($badge['icon'], FILTER_VALIDATE_URL)) : ?>
                    <img src="<?php echo esc_url($badge['icon']); ?>" alt="<?php echo esc_attr($badge['title']); ?>" class="w-10 h-10 object-contain mb-2" loading="lazy" />
                <?php else : ?>
                    <span class="material-symbols-outlined text-primary text-[40px] mb-2"><?php echo esc_html($badge['icon'] ?: 'verified'); ?></span>
                <?php endif; ?>
                <h4 class="font-bold text-label-md"><?php echo esc_html($badge['title']); ?></h4>
                <?php if (!empty($badge['desc'])) : ?>
                    <p class="text-label-sm text-outline"><?php echo esc_html($badge['desc']); ?></p>
                <?php endif; ?>
            </div>
        <?php 
            endforeach;
        else : 
        ?>
            <div class="flex flex-col items-center text-center">
                <span class="material-symbols-outlined text-primary text-[40px] mb-2">verified_user</span>
                <h4 class="font-bold text-label-md">100% CHÍNH HÃNG</h4>
                <p class="text-label-sm text-outline">Hoàn tiền nếu phát hiện hàng giả</p>
            </div>
            <div class="flex flex-col items-center text-center">
                <span class="material-symbols-outlined text-primary text-[40px] mb-2">local_shipping</span>
                <h4 class="font-bold text-label-md">GIAO HÀNG SIÊU TỐC</h4>
                <p class="text-label-sm text-outline">Nội thành Hà Nội trong 2 giờ</p>
            </div>
            <div class="flex flex-col items-center text-center">
                <span class="material-symbols-outlined text-primary text-[40px] mb-2">build</span>
                <h4 class="font-bold text-label-md">BẢO HÀNH TẬN TÂM</h4>
                <p class="text-label-sm text-outline">Hỗ trợ kỹ thuật trọn đời</p>
            </div>
            <div class="flex flex-col items-center text-center">
                <span class="material-symbols-outlined text-primary text-[40px] mb-2">published_with_changes</span>
                <h4 class="font-bold text-label-md">ĐỔI TRẢ 7 NGÀY</h4>
                <p class="text-label-sm text-outline">Nếu sản phẩm có lỗi từ nhà sản xuất</p>
            </div>
        <?php endif; ?>
    </div>
</div>
<!-- Main Footer -->
<?php
$footer_about = get_field('mi_hf_footer_about', 'option') ?: 'Chúng tôi là hệ thống phân phối các sản phẩm Xiaomi chính hãng hàng đầu tại Việt Nam, cam kết chất lượng và dịch vụ tốt nhất.';
$footer_fb = get_field('mi_hf_footer_facebook_html', 'option');
$footer_showrooms = get_field('mi_hf_footer_showrooms', 'option');
$footer_copyright = get_field('mi_hf_footer_copyright', 'option') ?: '© 2024 Xiaomi Store. All Rights Reserved. Thiết kế và vận hành bởi Xiaomi Store Vietnam.';
$footer_payment_icons = get_field('mi_hf_footer_payment_icons', 'option');
$footer_certificate = get_field('mi_hf_footer_certificate', 'option');
?>
<div class="max-w-[1440px] mx-auto px-4 py-8 md:py-16 grid grid-cols-1 md:grid-cols-4 gap-8 md:gap-12">
    <div class="flex flex-col h-full">
        <h3 class="font-bold text-primary mb-4 md:mb-6 uppercase"><?php esc_html_e( 'Về Xiaomi Store', 'omni-commerce' ); ?></h3>
        <p class="text-body-md text-outline mb-4"><?php echo esc_html($footer_about); ?></p>
        <div class="flex gap-3 mt-auto">
            <?php 
            $social_fb = get_field('mi_hf_fab_messenger', 'option') ?: 'https://m.me/xiaomivn';
            $social_zalo = get_field('mi_hf_fab_zalo', 'option') ?: 'https://zalo.me/0822834444';
            ?>
            <a href="<?php echo esc_url($social_fb); ?>" target="_blank" class="size-8 bg-surface-container rounded-full flex items-center justify-center text-primary cursor-pointer hover:bg-primary hover:text-white transition-all" title="Facebook Messenger">
                <span class="material-symbols-outlined text-[20px]">chat</span>
            </a>
            <a href="<?php echo esc_url($social_zalo); ?>" target="_blank" class="size-8 bg-surface-container rounded-full flex items-center justify-center text-primary cursor-pointer hover:bg-primary hover:text-white transition-all" title="Zalo">
                <span class="material-symbols-outlined text-[20px]">forum</span>
            </a>
        </div>
    </div>
    
    <div class="flex flex-col h-full">
        <h3 class="font-bold text-primary mb-4 md:mb-6 uppercase"><?php esc_html_e( 'Chính sách', 'omni-commerce' ); ?></h3>
        <?php
        if (has_nav_menu('footer-policy')) {
            wp_nav_menu([
                'theme_location' => 'footer-policy',
                'container' => false,
                'menu_class' => 'flex flex-col gap-3 text-body-md text-outline',
                'fallback_cb' => false,
                'add_a_class' => 'hover:text-primary transition-colors'
            ]);
        } else {
            // Fallback
        ?>
        <ul class="flex flex-col gap-3 text-body-md text-outline">
            <li><a class="hover:text-primary transition-colors" href="#">Chính sách bảo mật</a></li>
            <li><a class="hover:text-primary transition-colors" href="#">Chính sách bảo hành</a></li>
            <li><a class="hover:text-primary transition-colors" href="#">Chính sách đổi trả</a></li>
            <li><a class="hover:text-primary transition-colors" href="#">Chính sách vận chuyển</a></li>
        </ul>
        <?php } ?>
    </div>
    
    <div class="flex flex-col h-full">
        <h3 class="font-bold text-primary mb-4 md:mb-6 uppercase"><?php esc_html_e( 'Fanpage Facebook', 'omni-commerce' ); ?></h3>
        <?php if ($footer_fb): ?>
            <div class="w-full overflow-hidden rounded-lg">
                <?php echo $footer_fb; // Output raw HTML for iframe ?>
            </div>
        <?php else: ?>
            <div class="w-full overflow-hidden rounded-lg bg-white shadow-sm border border-outline-variant flex justify-center">
                <iframe src="https://www.facebook.com/plugins/page.php?href=https%3A%2F%2Fwww.facebook.com%2FXiaomiVietnam&tabs=timeline&width=300&height=214&small_header=false&adapt_container_width=true&hide_cover=false&show_facepile=true&appId" width="300" height="214" style="border:none;overflow:hidden" scrolling="no" frameborder="0" allowfullscreen="true" allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share"></iframe>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="flex flex-col h-full">
        <h3 class="font-bold text-primary mb-4 md:mb-6 uppercase"><?php esc_html_e( 'Thanh toán &amp; Chứng nhận', 'omni-commerce' ); ?></h3>
        <div class="flex flex-wrap gap-2 mb-6">
            <?php 
            if ($footer_payment_icons): 
                foreach ($footer_payment_icons as $pay):
                    if (!empty($pay['image'])):
            ?>
                <img src="<?php echo esc_url($pay['image']); ?>" alt="Payment" class="h-8 w-auto object-contain bg-white rounded border border-outline-variant p-1" loading="lazy" />
            <?php 
                    elseif (!empty($pay['text'])):
            ?>
                <div class="h-8 px-3 bg-surface-container rounded border border-outline-variant flex items-center justify-center text-[12px] font-bold text-outline uppercase"><?php echo esc_html($pay['text']); ?></div>
            <?php 
                    endif;
                endforeach;
            else: 
            ?>
                <!-- Fallback Payment Icons -->
                <div class="h-8 px-3 bg-white rounded border border-outline-variant flex items-center justify-center text-[12px] font-bold text-[#1434CB] shadow-sm"><span class="italic">VISA</span></div>
                <div class="h-8 px-3 bg-white rounded border border-outline-variant flex items-center justify-center text-[12px] font-bold text-[#FF5F00] shadow-sm"><span class="italic">Mastercard</span></div>
                <div class="h-8 px-3 bg-white rounded border border-outline-variant flex items-center justify-center text-[12px] font-bold text-[#0068ff] shadow-sm">VNPAY</div>
                <div class="h-8 px-3 bg-white rounded border border-outline-variant flex items-center justify-center text-[12px] font-bold text-gray-700 shadow-sm">COD</div>
            <?php endif; ?>
        </div>
        
        <?php if ($footer_certificate): ?>
            <img class="h-12 w-auto object-contain mt-auto" alt="Chứng nhận" src="<?php echo esc_url($footer_certificate); ?>" loading="lazy" />
        <?php else: ?>
            <img class="h-12 w-auto object-contain mt-auto" alt="Chứng nhận Bộ Công Thương" src="https://bizweb.dktcdn.net/100/342/645/themes/701297/assets/bct.png" loading="lazy" />
        <?php endif; ?>
    </div>
</div>

<!-- Address Footer -->
<div class="bg-primary text-white py-8 md:py-12 px-4">
    <div class="max-w-[1440px] mx-auto grid grid-cols-1 md:grid-cols-3 gap-6 md:gap-8">
        <?php 
        if ($footer_showrooms): 
            foreach ($footer_showrooms as $showroom):
        ?>
            <div>
                <h4 class="font-bold text-tertiary-fixed mb-2 uppercase"><?php echo esc_html($showroom['name']); ?></h4>
                <p class="text-label-sm leading-relaxed">
                    <?php echo esc_html($showroom['address']); ?><br/>
                    Hotline: <?php echo esc_html($showroom['hotline']); ?>
                </p>
            </div>
        <?php 
            endforeach;
        else: 
        ?>
            <div>
                <h4 class="font-bold text-tertiary-fixed mb-2 uppercase">Showroom Thanh Xuân</h4>
                <p class="text-label-sm leading-relaxed">Số 41 Khuất Duy Tiến, Thanh Xuân Bắc, Hà Nội<br/>Hotline: 0822.83.4444</p>
            </div>
            <div>
                <h4 class="font-bold text-tertiary-fixed mb-2 uppercase">Showroom Long Biên</h4>
                <p class="text-label-sm leading-relaxed">Số 123 Nguyễn Văn Cừ, Ngọc Lâm, Hà Nội<br/>Hotline: 0822.83.5555</p>
            </div>
            <div>
                <h4 class="font-bold text-tertiary-fixed mb-2 uppercase">Showroom Cầu Giấy</h4>
                <p class="text-label-sm leading-relaxed">Số 88 Xuân Thủy, Dịch Vọng Hậu, Hà Nội<br/>Hotline: 0822.83.6666</p>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="max-w-[1440px] mx-auto mt-8 md:mt-12 pt-6 border-t border-white/20 text-center text-[12px] opacity-70">
        <?php echo esc_html($footer_copyright); ?>
    </div>
</div>
</footer>
<!-- 7. Floating Action Bar -->
<?php 
// Get ACF Options
$fab_hotline = get_field('mi_hf_fab_hotline', 'option') ?: '0822834444';
$fab_messenger = get_field('mi_hf_fab_messenger', 'option') ?: 'https://m.me/xiaomivn';
$fab_zalo = get_field('mi_hf_fab_zalo', 'option') ?: 'https://zalo.me/0822834444';
$fab_showroom = home_url('/lien-he/');
?>
<!-- Back to Top Button -->
<style>
    @media (min-width: 768px) {
        #mi-back-to-top {
            right: 96px !important; /* Dịch nút sang trái để không đè lên chat widget */
        }
    }
</style>
<button id="mi-back-to-top" class="hidden items-center justify-center w-12 h-12 bg-white text-outline hover:text-primary transition-colors pointer-events-auto rounded-full shadow-[0_4px_15px_rgba(0,0,0,0.1)] border border-outline-variant hover:scale-110 fixed bottom-28 right-4 md:bottom-[280px] z-[9998]">
    <span class="material-symbols-outlined text-[24px]">arrow_upward</span>
</button>

<div class="fixed bottom-0 left-0 right-0 z-[9999] md:bottom-24 md:left-auto md:right-8 md:w-auto w-full pointer-events-none">
    <!-- Desktop: Floating Buttons | Mobile: Bottom App Bar -->
    <div class="flex flex-row md:flex-col items-center md:items-end justify-between md:justify-end gap-2 md:gap-3 w-full pointer-events-auto bg-white md:bg-transparent px-4 py-3 md:p-0 shadow-[0_-5px_20px_rgba(0,0,0,0.1)] md:shadow-none border-t border-gray-100 md:border-none">
        
        <!-- Messenger -->
        <a href="<?php echo esc_url($fab_messenger); ?>" target="_blank" rel="noopener noreferrer" class="flex flex-col md:flex-row items-center justify-center w-12 h-12 text-white rounded-full md:shadow-md hover:scale-110 transition-transform" style="background-color: #0084ff;">
            <span class="material-symbols-outlined">chat</span>
        </a>
        
        <!-- Zalo -->
        <a href="<?php echo esc_url($fab_zalo); ?>" target="_blank" rel="noopener noreferrer" class="flex flex-col md:flex-row items-center justify-center w-12 h-12 text-white rounded-full md:shadow-md hover:scale-110 transition-transform" style="background-color: #0068ff;">
            <span class="material-symbols-outlined">forum</span>
        </a>
        
        <!-- Showroom -->
        <a href="<?php echo esc_url($fab_showroom); ?>" class="flex flex-col md:flex-row items-center justify-center w-12 h-12 text-white rounded-full md:shadow-md hover:scale-110 transition-transform" style="background-color: #00a651;">
            <span class="material-symbols-outlined">storefront</span>
        </a>

        <!-- Hotline (Prominent) -->
        <a href="tel:<?php echo esc_attr(str_replace([' ', '.'], '', $fab_hotline)); ?>" class="flex items-center justify-center gap-2 text-white px-5 py-3 md:py-3 rounded-full md:shadow-lg group hover:scale-105 transition-transform order-first md:order-last w-full md:w-auto" style="background-color: #e60012;">
            <span class="material-symbols-outlined" style="font-variation-settings: 'FILL' 1;">call</span>
            <span class="text-sm font-bold uppercase whitespace-nowrap"><?php esc_html_e( 'Gọi Hotline', 'omni-commerce' ); ?></span>
        </a>
    </div>
</div>
<!-- Add bottom padding to body on mobile to prevent content being hidden behind app bar -->



<?php wp_footer(); ?>
</body></html>