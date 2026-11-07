<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header(); ?>
<main id="primary" class="site-main bg-slate-50/60 min-h-screen py-4 md:py-8">

<div class="max-w-[1440px] mx-auto px-3 sm:px-4 md:px-6">

    <?php while ( have_posts() ) : the_post(); ?>
        <!-- Breadcrumbs -->
        <nav class="article-breadcrumb" aria-label="Breadcrumb">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>">
                <span class="material-symbols-outlined text-[18px]">home</span>
                <span>Trang chủ</span>
            </a>
            <span class="separator material-symbols-outlined">chevron_right</span>
            <a href="<?php echo esc_url( home_url( '/tin-tuc/' ) ); ?>">
                <span>Tin tức</span>
            </a>
            <?php
            $categories = get_the_category();
            if ( ! empty( $categories ) ) :
                $primary_cat = $categories[0];
                ?>
                <span class="separator material-symbols-outlined">chevron_right</span>
                <a href="<?php echo esc_url( get_category_link( $primary_cat->term_id ) ); ?>">
                    <?php echo esc_html( wp_specialchars_decode( $primary_cat->name ) ); ?>
                </a>
            <?php endif; ?>
            <span class="separator material-symbols-outlined">chevron_right</span>
            <span class="current truncate max-w-[200px] sm:max-w-md"><?php the_title(); ?></span>
        </nav>

        <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 lg:gap-8">
            
            <!-- Sidebar (Cột Trái trên Desktop: lg:col-span-1, lg:order-1) -->
            <aside class="lg:col-span-1 order-2 lg:order-1">
                <div class="sticky top-24 sidebar-sticky-wrap">
                    
                    <!-- Khối Sản Phẩm Liên Quan (WooCommerce Products) -->
                    <div class="sidebar-box">
                        <h3 class="sidebar-box-title">
                            <span class="material-symbols-outlined text-primary text-[20px]">shopping_bag</span>
                            <span>Sản Phẩm Liên Quan</span>
                        </h3>
                        
                        <div class="flex flex-col gap-3">
                            <?php
                            $product_args = array(
                                'post_type'      => 'product',
                                'posts_per_page' => 4,
                                'post_status'    => 'publish',
                                'orderby'        => 'date',
                                'order'          => 'DESC',
                            );

                            // Tự động gom sản phẩm theo ngữ cảnh bài viết (Tivi, Tủ Lạnh)
                            $title_lower = mb_strtolower( get_the_title() );
                            if ( strpos( $title_lower, 'tivi' ) !== false || strpos( $title_lower, 'tv' ) !== false ) {
                                $product_args['tax_query'] = array(
                                    array(
                                        'taxonomy' => 'product_cat',
                                        'field'    => 'slug',
                                        'terms'    => array( 'tivi-xiaomi', 'tivi' ),
                                    ),
                                );
                            } elseif ( strpos( $title_lower, 'tủ lạnh' ) !== false || strpos( $title_lower, 'tu lanh' ) !== false ) {
                                $product_args['tax_query'] = array(
                                    array(
                                        'taxonomy' => 'product_cat',
                                        'field'    => 'slug',
                                        'terms'    => array( 'tu-lanh-xiaomi', 'tu-lanh' ),
                                    ),
                                );
                            }

                            $rel_products = new WP_Query( $product_args );
                            if ( ! $rel_products->have_posts() ) {
                                unset( $product_args['tax_query'] );
                                $rel_products = new WP_Query( $product_args );
                            }

                            if ( $rel_products->have_posts() ) :
                                while ( $rel_products->have_posts() ) : $rel_products->the_post();
                                    global $product;
                                    if ( ! is_a( $product, 'WC_Product' ) ) {
                                        $product = wc_get_product( get_the_ID() );
                                    }
                                ?>
                                <a href="<?php the_permalink(); ?>" class="group flex gap-3 p-2 rounded-xl border border-slate-100 hover:border-primary/40 hover:bg-slate-50/80 transition-all duration-200">
                                    <div class="w-[72px] h-[72px] shrink-0 rounded-lg overflow-hidden bg-white border border-slate-200/80 p-1 flex items-center justify-center relative">
                                        <?php if ( has_post_thumbnail() ) : ?>
                                            <?php echo mi_safe_product_thumbnail( get_the_ID(), 'woocommerce_thumbnail', array( 'class' => 'w-full h-full object-contain group-hover:scale-105 transition-transform duration-300', 'loading' => 'lazy' ) ); ?>
                                        <?php else : ?>
                                            <span class="material-symbols-outlined text-slate-300 text-[24px]">tv</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-1 min-w-0 flex flex-col justify-center">
                                        <h4 class="text-[13px] font-bold text-slate-800 leading-snug group-hover:text-primary line-clamp-2 transition-colors">
                                            <?php the_title(); ?>
                                        </h4>
                                        <div class="mt-1 flex items-baseline gap-1.5 flex-wrap">
                                            <?php if ( $product ) : ?>
                                                <span class="text-xs sm:text-sm font-black text-rose-600">
                                                    <?php echo $product->get_price_html(); ?>
                                                </span>
                                            <?php else : ?>
                                                <span class="text-xs font-semibold text-rose-600">Liên hệ kho</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </a>
                                <?php 
                                endwhile; 
                                wp_reset_postdata(); 
                            endif; 
                            ?>
                        </div>
                    </div>

                    <!-- Hotline Support Card -->
                    <div class="sidebar-hotline-card">
                        <span class="material-symbols-outlined text-[28px] text-amber-600">support_agent</span>
                        <div class="text-xs font-bold text-amber-800 uppercase tracking-wider mt-1">Tổng Đài Đặt Hàng & Tư Vấn</div>
                        <a href="tel:0822834444" class="sidebar-hotline-number">0822.83.4444</a>
                        <p class="sidebar-hotline-desc">Hỗ trợ kỹ thuật 24/7 • Giao nhanh 60 phút</p>
                    </div>

                    <!-- Commitments Box -->
                    <div class="sidebar-box">
                        <h3 class="sidebar-box-title">
                            <span class="material-symbols-outlined text-primary text-[20px]">verified_user</span>
                            <span>Cam Kết Tổng Kho</span>
                        </h3>
                        <div class="space-y-3">
                            <div class="sidebar-commitment-item">
                                <div class="sidebar-commitment-icon">
                                    <span class="material-symbols-outlined text-[18px]">workspace_premium</span>
                                </div>
                                <div class="sidebar-commitment-text">
                                    <h5>100% Chính Hãng Xiaomi</h5>
                                    <p>Nguyên seal hộp, bảo hành điện tử chính hãng 24 tháng toàn quốc.</p>
                                </div>
                            </div>
                            <div class="sidebar-commitment-item">
                                <div class="sidebar-commitment-icon">
                                    <span class="material-symbols-outlined text-[18px]">cached</span>
                                </div>
                                <div class="sidebar-commitment-text">
                                    <h5>Đổi Mới 30 Ngày</h5>
                                    <p>Lỗi do nhà sản xuất đổi mới 1-1 ngay tại nhà hoàn toàn miễn phí.</p>
                                </div>
                            </div>
                            <div class="sidebar-commitment-item">
                                <div class="sidebar-commitment-icon">
                                    <span class="material-symbols-outlined text-[18px]">local_shipping</span>
                                </div>
                                <div class="sidebar-commitment-text">
                                    <h5>Giao Siêu Tốc 60 Phút</h5>
                                    <p>Lắp đặt và kiểm tra máy tận nơi trước khi thanh toán.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tin Xem Nhiều (Popular Posts) -->
                    <div class="sidebar-box">
                        <h3 class="sidebar-box-title">
                            <span class="material-symbols-outlined text-primary text-[20px]">trending_up</span>
                            <span>Tin Xem Nhiều</span>
                        </h3>
                        
                        <div class="flex flex-col gap-3">
                            <?php
                            $popular_args = array(
                                'posts_per_page'      => 4,
                                'post_status'         => 'publish',
                                'post__not_in'        => array( get_the_ID() ),
                                'ignore_sticky_posts' => true,
                                'orderby'             => 'date',
                                'order'               => 'DESC'
                            );
                            $popular_posts = new WP_Query($popular_args);
                            if ($popular_posts->have_posts()) :
                                while ($popular_posts->have_posts()) : $popular_posts->the_post();
                                ?>
                                <a href="<?php the_permalink(); ?>" class="flex gap-3 group pb-3 border-b border-slate-100 last:border-0 last:pb-0">
                                    <div class="w-[75px] h-[52px] shrink-0 rounded-lg overflow-hidden bg-slate-100 flex items-center justify-center relative">
                                        <?php if ( has_post_thumbnail() ) : ?>
                                            <?php the_post_thumbnail( 'thumbnail', array('class' => 'w-full h-full object-cover group-hover:scale-110 transition-transform duration-500') ); ?>
                                        <?php else : ?>
                                            <span class="material-symbols-outlined text-slate-400 text-[18px]">image</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-[12px] font-bold text-slate-800 leading-snug group-hover:text-primary line-clamp-2">
                                            <?php the_title(); ?>
                                        </h4>
                                        <span class="text-[11px] text-slate-400 mt-0.5 block"><?php echo esc_html( get_the_date( 'd/m/Y' ) ); ?></span>
                                    </div>
                                </a>
                                <?php 
                                endwhile;
                                wp_reset_postdata();
                            endif;
                            ?>
                        </div>
                    </div>

                </div>
            </aside>

            <!-- Nội Dung Bài Viết (Cột Phải trên Desktop: lg:col-span-3, lg:order-2) -->
            <div class="lg:col-span-3 order-1 lg:order-2">
                <div class="bg-white rounded-xl sm:rounded-2xl shadow-sm border border-slate-200/80 p-4 sm:p-6 md:p-10 lg:p-12">
                    <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                        <header class="article-header">
                            <?php if ( ! empty( $categories ) ) : ?>
                                <a href="<?php echo esc_url( get_category_link( $primary_cat->term_id ) ); ?>" class="article-cat-badge">
                                    <span class="material-symbols-outlined text-[15px]">label</span>
                                    <span><?php echo esc_html( wp_specialchars_decode( $primary_cat->name ) ); ?></span>
                                </a>
                            <?php endif; ?>

                            <h1 class="article-title">
                                <?php the_title(); ?>
                            </h1>

                            <div class="article-meta">
                                <span class="article-meta-item">
                                    <span class="material-symbols-outlined">calendar_today</span>
                                    <span><?php echo esc_html( get_the_date( 'd/m/Y' ) ); ?></span>
                                </span>
                                <span>•</span>
                                <span class="article-meta-item">
                                    <span class="material-symbols-outlined">person</span>
                                    <span><?php echo esc_html( get_the_author() ); ?></span>
                                </span>
                                <span>•</span>
                                <span class="article-meta-item">
                                    <span class="material-symbols-outlined">timer</span>
                                    <span>
                                        <?php
                                        $content_text = wp_strip_all_tags( get_the_content() );
                                        $word_count = str_word_count( $content_text );
                                        $reading_time = max( 1, ceil( $word_count / 200 ) );
                                        echo esc_html( '~' . $reading_time . ' phút đọc' );
                                        ?>
                                    </span>
                                </span>
                                <span>•</span>
                                <span class="article-meta-item text-emerald-600 font-medium">
                                    <span class="material-symbols-outlined text-emerald-500">verified</span>
                                    <span>Kiểm định kỹ thuật</span>
                                </span>
                            </div>
                        </header>

                        <!-- Ảnh đại diện bài viết chuẩn Retina & Mobile-responsive -->
                        <?php if ( has_post_thumbnail() ) : ?>
                            <div class="article-featured-image-wrap mb-8 rounded-2xl overflow-hidden shadow-sm border border-slate-200/80 bg-slate-50 flex items-center justify-center">
                                <?php the_post_thumbnail( 'large', array( 'class' => 'w-full h-auto max-h-[520px] object-cover transition-transform duration-500 hover:scale-[1.02]' ) ); ?>
                            </div>
                        <?php endif; ?>

                        <!-- Khung Nội Dung Bài Viết chuẩn Gutenberg Typography -->
                        <div class="mi-article-content prose max-w-none prose-slate text-slate-700 leading-relaxed text-[16px]">
                            <?php
                            the_content();
                            wp_link_pages( array(
                                'before' => '<div class="page-links mt-8">' . esc_html__( 'Trang:', 'omni-commerce' ),
                                'after'  => '</div>',
                            ) );
                            ?>
                        </div>

                        <!-- Article Footer -->
                        <footer class="article-footer">
                            <!-- Tags -->
                            <?php
                            $tags = get_the_tags();
                            if ( $tags ) :
                            ?>
                                <div class="article-tags-wrap">
                                    <span class="article-tag-label">
                                        <span class="material-symbols-outlined text-[16px]">sell</span>
                                        <span>Từ khóa:</span>
                                    </span>
                                    <?php foreach ( $tags as $tag ) : ?>
                                        <a href="<?php echo esc_url( get_tag_link( $tag->term_id ) ); ?>" class="article-tag-pill">
                                            #<?php echo esc_html( $tag->name ); ?>
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <!-- Author Card -->
                            <div class="article-author-card">
                                <div class="article-author-avatar">
                                    <span class="material-symbols-outlined text-[32px]">shield_person</span>
                                </div>
                                <div class="article-author-info">
                                    <h4 class="article-author-name">Ban Biên Tập Kỹ Thuật TongKhoMi</h4>
                                    <p class="article-author-bio">Đội ngũ kỹ thuật viên và chuyên viên đánh giá sản phẩm chuyên sâu về hệ sinh thái thông minh Xiaomi tại Việt Nam. Cam kết thông tin chính xác, khách quan và minh bạch.</p>
                                </div>
                            </div>

                            <!-- Consultation Banner -->
                            <div class="article-consultation-banner">
                                <div>
                                    <h4 class="article-consultation-title">Bạn cần hỗ trợ tư vấn chọn mua Tivi Xiaomi?</h4>
                                    <p class="article-consultation-desc">Đội ngũ kỹ thuật viên Tổng Kho Mi luôn sẵn sàng tư vấn trực tiếp 24/7 theo không gian phòng và ngân sách.</p>
                                </div>
                                <a href="tel:0822834444" class="article-consultation-btn">
                                    <span class="material-symbols-outlined text-[20px]">call</span>
                                    <span>GỌI 0822.83.4444</span>
                                </a>
                            </div>

                            <!-- Related Posts -->
                            <?php
                            $related_args = array(
                                'posts_per_page'      => 3,
                                'post_status'         => 'publish',
                                'post__not_in'        => array( get_the_ID() ),
                                'ignore_sticky_posts' => true,
                                'orderby'             => 'date',
                                'order'               => 'DESC',
                            );
                            if ( ! empty( $categories ) ) {
                                $related_args['cat'] = $primary_cat->term_id;
                            }
                            $related_query = new WP_Query( $related_args );
                            if ( ! $related_query->have_posts() ) {
                                unset( $related_args['cat'] );
                                $related_query = new WP_Query( $related_args );
                            }
                            if ( $related_query->have_posts() ) :
                            ?>
                                <div class="mt-8">
                                    <h3 class="article-related-title">Bài Viết Liên Quan</h3>
                                    <div class="article-related-grid">
                                        <?php while ( $related_query->have_posts() ) : $related_query->the_post(); ?>
                                            <a href="<?php the_permalink(); ?>" class="article-related-card">
                                                <div class="article-related-thumb">
                                                    <?php if ( has_post_thumbnail() ) : ?>
                                                        <?php the_post_thumbnail( 'medium', array( 'class' => 'w-full h-full object-cover' ) ); ?>
                                                    <?php else : ?>
                                                        <div class="w-full h-full flex items-center justify-center bg-slate-100 text-slate-400">
                                                            <span class="material-symbols-outlined text-[36px]">image</span>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="article-related-body">
                                                    <h4 class="article-related-post-title"><?php the_title(); ?></h4>
                                                    <span class="article-related-date"><?php echo esc_html( get_the_date( 'd/m/Y' ) ); ?></span>
                                                </div>
                                            </a>
                                        <?php endwhile; wp_reset_postdata(); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </footer>
                    </article>

                    <?php
                    if ( comments_open() || get_comments_number() ) :
                        comments_template();
                    endif;
                    ?>
                </div>
            </div>

        </div>

        <!-- Table of Contents Auto-Generator Script -->
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var content = document.querySelector('.mi-article-content');
            if (!content) return;
            var headings = content.querySelectorAll('h2');
            if (headings.length < 2) return;
            
            var tocBox = document.createElement('div');
            tocBox.className = 'article-toc-box';
            
            var header = document.createElement('div');
            header.className = 'article-toc-header';
            header.innerHTML = '<h4 class="article-toc-title"><span class="material-symbols-outlined">format_list_bulleted</span>Mục Lục Nội Dung Bài Viết</h4><button type="button" class="article-toc-toggle-btn" id="toc-btn">[Thu gọn]</button>';
            
            var list = document.createElement('ol');
            list.className = 'article-toc-list';
            
            headings.forEach(function(h, index) {
                var id = h.id || 'muc-' + (index + 1);
                h.id = id;
                var li = document.createElement('li');
                var a = document.createElement('a');
                a.href = '#' + id;
                a.textContent = h.textContent.replace(/^[0-9]+\.\s*/, '').trim();
                li.appendChild(a);
                list.appendChild(li);
            });
            
            tocBox.appendChild(header);
            tocBox.appendChild(list);
            
            // Chèn Mục lục trước thẻ H2 đầu tiên
            var firstH2 = headings[0];
            firstH2.parentNode.insertBefore(tocBox, firstH2);
            
            // Xử lý bật/tắt đóng mở
            var btn = tocBox.querySelector('#toc-btn');
            header.addEventListener('click', function() {
                list.classList.toggle('collapsed');
                btn.textContent = list.classList.contains('collapsed') ? '[Mở rộng]' : '[Thu gọn]';
            });
        });
        </script>

    <?php endwhile; ?>

</div>
</main>
<?php get_footer(); ?>
