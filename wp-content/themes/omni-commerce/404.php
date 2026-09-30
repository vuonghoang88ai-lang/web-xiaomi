<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header(); ?>
<main id="primary" class="site-main">

<div class="max-w-[1440px] mx-auto px-4 py-8 md:py-12 text-center">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-12 lg:p-24">
        <h1 class="text-6xl md:text-8xl font-black text-slate-800 mb-4">404</h1>
        <h2 class="text-2xl md:text-3xl font-bold text-slate-700 mb-6 uppercase"><?php esc_html_e( 'Không tìm thấy trang', 'omni-commerce' ); ?></h2>
        <p class="text-slate-500 mb-8 max-w-md mx-auto"><?php esc_html_e( 'Trang bạn đang tìm kiếm có thể đã bị xóa, đổi tên hoặc tạm thời không thể truy cập.', 'omni-commerce' ); ?></p>
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="inline-flex items-center justify-center bg-primary text-white px-8 py-3 rounded-full font-bold hover:bg-primary-container transition-colors">
            <?php esc_html_e( 'Quay về trang chủ', 'omni-commerce' ); ?>
        </a>
    </div>
</div>
</main>
<?php get_footer(); ?>
