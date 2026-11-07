<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Template Name: Default Page
 *
 * @package Omni_Commerce
 */

get_header(); 

// Detect policy page
$current_slug = get_post_field( 'post_name', get_post() );
$policy_slugs = array( 'chinh-sach-bao-mat', 'chinh-sach-bao-hanh', 'chinh-sach-doi-tra', 'chinh-sach-van-chuyen' );
$is_policy    = in_array( $current_slug, $policy_slugs, true );

$policy_links = array(
    array(
        'title' => 'Chính sách bảo mật',
        'url'   => mi_get_page_url( 'chinh-sach-bao-mat' ),
        'icon'  => 'shield',
        'slug'  => 'chinh-sach-bao-mat',
    ),
    array(
        'title' => 'Chính sách bảo hành',
        'url'   => mi_get_page_url( 'chinh-sach-bao-hanh' ),
        'icon'  => 'verified',
        'slug'  => 'chinh-sach-bao-hanh',
    ),
    array(
        'title' => 'Chính sách đổi trả',
        'url'   => mi_get_page_url( 'chinh-sach-doi-tra' ),
        'icon'  => 'published_with_changes',
        'slug'  => 'chinh-sach-doi-tra',
    ),
    array(
        'title' => 'Chính sách vận chuyển',
        'url'   => mi_get_page_url( 'chinh-sach-van-chuyen' ),
        'icon'  => 'local_shipping',
        'slug'  => 'chinh-sach-van-chuyen',
    ),
);
?>
<main id="primary" class="site-main bg-slate-50/50 min-h-screen py-4 md:py-8">

    <div class="max-w-[1440px] mx-auto px-3 sm:px-4 md:px-6">
        
        <!-- Breadcrumbs -->
        <nav class="flex items-center flex-wrap gap-2 text-xs sm:text-sm text-slate-500 mb-4 sm:mb-6" aria-label="Breadcrumb">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="hover:text-primary flex items-center gap-1 transition-colors">
                <span class="material-symbols-outlined text-[18px]">home</span>
                <span>Trang chủ</span>
            </a>
            <span class="material-symbols-outlined text-[16px] text-slate-400">chevron_right</span>
            <?php if ( $is_policy ) : ?>
                <span class="text-slate-500">Chính sách &amp; Quy định</span>
                <span class="material-symbols-outlined text-[16px] text-slate-400">chevron_right</span>
            <?php endif; ?>
            <span class="text-slate-800 font-semibold truncate max-w-[200px] sm:max-w-none"><?php the_title(); ?></span>
        </nav>

        <?php if ( $is_policy ) : ?>
            <!-- Layout 2 cột cho các trang chính sách -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 lg:gap-8">
                
                <!-- Sidebar chính sách (Desktop) -->
                <aside class="lg:col-span-1">
                    <div class="sticky top-24 space-y-5">
                        
                        <!-- Policy Nav Box -->
                        <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 p-4">
                            <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider mb-3 pb-2 border-b border-slate-100 flex items-center gap-2">
                                <span class="material-symbols-outlined text-primary text-[20px]">policy</span>
                                <span>Chính sách chung</span>
                            </h3>
                            <nav class="flex flex-col gap-1">
                                <?php foreach ( $policy_links as $item ) : 
                                    $is_active = ( $current_slug === $item['slug'] );
                                ?>
                                    <a href="<?php echo esc_url( $item['url'] ); ?>" 
                                       class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all <?php echo $is_active ? 'bg-primary/10 text-primary font-bold shadow-xs' : 'text-slate-600 hover:bg-slate-50 hover:text-primary'; ?>">
                                        <span class="material-symbols-outlined text-[20px] <?php echo $is_active ? 'text-primary' : 'text-slate-400'; ?>"><?php echo esc_html( $item['icon'] ); ?></span>
                                        <span class="truncate"><?php echo esc_html( $item['title'] ); ?></span>
                                        <?php if ( $is_active ) : ?>
                                            <span class="material-symbols-outlined text-[16px] text-primary ml-auto">arrow_right</span>
                                        <?php endif; ?>
                                    </a>
                                <?php endforeach; ?>
                            </nav>
                        </div>

                        <!-- Hotline Support Box -->
                        <div class="bg-gradient-to-br from-primary/10 via-primary/5 to-white rounded-xl border border-primary/20 p-4 sm:p-5 text-center shadow-xs">
                            <div class="size-11 mx-auto bg-primary text-white rounded-full flex items-center justify-center mb-2 shadow-sm">
                                <span class="material-symbols-outlined text-[24px]">support_agent</span>
                            </div>
                            <h4 class="font-bold text-slate-800 text-sm sm:text-base">Hỗ Trợ Khách Hàng</h4>
                            <p class="text-xs text-slate-500 mb-3">Tư vấn chính sách &amp; kỹ thuật 24/7</p>
                            <a href="tel:0822834444" class="inline-flex items-center justify-center gap-2 w-full py-2.5 px-4 bg-primary text-white text-sm font-bold rounded-lg shadow-sm hover:bg-primary-hover transition-colors">
                                <span class="material-symbols-outlined text-[18px]">call</span>
                                <span>0822.83.4444</span>
                            </a>
                            <div class="mt-3 pt-3 border-t border-slate-200/60 flex items-center justify-center gap-3 text-xs text-slate-600">
                                <a href="<?php echo esc_url( home_url( '/tra-cuu-bao-hanh/' ) ); ?>" class="hover:text-primary flex items-center gap-1 font-medium transition-colors">
                                    <span class="material-symbols-outlined text-[14px]">verified_user</span>
                                    <span>Tra cứu bảo hành</span>
                                </a>
                            </div>
                        </div>

                    </div>
                </aside>

                <!-- Cột nội dung chính -->
                <div class="lg:col-span-3">
                    <div class="bg-white rounded-xl sm:rounded-2xl shadow-sm border border-slate-200/80 p-5 sm:p-8 md:p-10">
                        <?php
                        while ( have_posts() ) :
                            the_post();
                            ?>
                            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                                <header class="mb-6 sm:mb-8 pb-5 border-b border-slate-100">
                                    <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-primary/10 text-primary mb-3">
                                        <span class="material-symbols-outlined text-[14px]">verified</span>
                                        <span>CHÍNH SÁCH CHÍNH HÃNG</span>
                                    </div>
                                    <h1 class="text-2xl sm:text-3xl lg:text-4xl font-extrabold text-slate-900 m-0 uppercase tracking-tight leading-tight">
                                        <?php the_title(); ?>
                                    </h1>
                                    <div class="flex flex-wrap items-center gap-4 text-xs sm:text-sm text-slate-500 mt-3 pt-3 border-t border-dashed border-slate-100">
                                        <span class="flex items-center gap-1">
                                            <span class="material-symbols-outlined text-[16px] text-slate-400">update</span>
                                            <span>Cập nhật lần cuối: <strong><?php echo esc_html( get_the_modified_date( 'd/m/Y' ) ); ?></strong></span>
                                        </span>
                                        <span class="hidden sm:inline text-slate-300">•</span>
                                        <span class="flex items-center gap-1">
                                            <span class="material-symbols-outlined text-[16px] text-emerald-500">check_circle</span>
                                            <span>Hiệu lực: <strong>Toàn hệ thống</strong></span>
                                        </span>
                                        <span class="hidden sm:inline text-slate-300">•</span>
                                        <span class="flex items-center gap-1">
                                            <span class="material-symbols-outlined text-[16px] text-slate-400">schedule</span>
                                            <span>Thời gian đọc: <strong>~3 phút</strong></span>
                                        </span>
                                    </div>
                                </header>

                                <div class="mi-page-content text-slate-700 leading-relaxed text-[15px] sm:text-[16px]">
                                    <?php
                                    the_content();
                                    
                                    wp_link_pages(
                                        array(
                                            'before' => '<div class="page-links mt-8">' . esc_html__( 'Trang:', 'omni-commerce' ),
                                            'after'  => '</div>',
                                        )
                                    );
                                    ?>
                                </div>
                            </article>
                            <?php
                        endwhile; 
                        ?>
                    </div>
                </div>

            </div>

        <?php else : ?>
            <!-- Layout mặc định cho các trang nội dung khác (Shop, Cart, v.v.) -->
            <div class="bg-white rounded-xl sm:rounded-2xl shadow-sm border border-slate-200/80 p-4 sm:p-6 md:p-10 lg:p-12">
                <?php
                while ( have_posts() ) :
                    the_post();
                    ?>
                    <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                        <header class="mb-6 md:mb-8 border-b border-slate-200 pb-5">
                            <?php the_title( '<h1 class="text-2xl sm:text-3xl md:text-4xl font-extrabold text-slate-800 m-0 uppercase">', '</h1>' ); ?>
                        </header>

                        <div class="mi-page-content text-slate-700 leading-relaxed text-[15px] sm:text-[16px]">
                            <?php
                            the_content();
                            
                            wp_link_pages(
                                array(
                                    'before' => '<div class="page-links mt-8">' . esc_html__( 'Trang:', 'omni-commerce' ),
                                    'after'  => '</div>',
                                )
                            );
                            ?>
                        </div>
                    </article>
                    <?php
                endwhile; 
                ?>
            </div>
        <?php endif; ?>

    </div>

<style>
    /* Styling for standard WP page content & typography */
    .mi-page-content {
        line-height: 1.8;
    }
    .mi-page-content h2 {
        color: #0f172a;
        font-weight: 800;
        font-size: 1.25rem;
        margin-top: 2rem;
        margin-bottom: 0.875rem;
        padding: 0.5rem 0.75rem;
        border-left: 4px solid var(--color-primary, #ff6700);
        background: linear-gradient(90deg, rgba(255, 103, 0, 0.05) 0%, rgba(255, 255, 255, 0) 100%);
        border-radius: 0 0.375rem 0.375rem 0;
    }
    @media (min-width: 640px) {
        .mi-page-content h2 {
            font-size: 1.45rem;
            padding: 0.625rem 1rem;
        }
    }
    .mi-page-content h3 {
        color: #1e293b;
        font-weight: 700;
        font-size: 1.15rem;
        margin-top: 1.5rem;
        margin-bottom: 0.75rem;
    }
    .mi-page-content p {
        margin-bottom: 1.25rem;
    }
    .mi-page-content p.lead-text {
        font-size: 1.05rem;
        color: #334155;
        background-color: #f8fafc;
        border-left: 3px solid #94a3b8;
        padding: 0.875rem 1.125rem;
        border-radius: 0 0.5rem 0.5rem 0;
        margin-bottom: 1.5rem;
    }
    .mi-page-content ul, .mi-page-content ol {
        margin-left: 1.25rem;
        margin-bottom: 1.25rem;
    }
    .mi-page-content ul { 
        list-style-type: disc; 
    }
    .mi-page-content ol { 
        list-style-type: decimal; 
    }
    .mi-page-content li {
        margin-bottom: 0.5rem;
        padding-left: 0.25rem;
    }
    .mi-page-content li::marker {
        color: var(--color-primary, #ff6700);
        font-weight: bold;
    }
    .mi-page-content a {
        color: var(--color-primary, #ff6700);
        text-decoration: underline;
        font-weight: 500;
    }
    .mi-page-content a:hover {
        opacity: 0.85;
    }
    .mi-page-content img {
        border-radius: 0.75rem;
        max-width: 100%;
        height: auto;
        margin: 1.5rem 0;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }
    .mi-page-content .policy-contact-card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .mi-page-content .policy-contact-card:hover {
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
    }
</style>

</main>
<?php get_footer();
