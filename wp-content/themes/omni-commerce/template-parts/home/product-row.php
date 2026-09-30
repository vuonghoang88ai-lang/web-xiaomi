<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

/**
 * Template part for displaying a single product row on the homepage.
 *
 * Luôn được gọi từ front-page.php với $args truyền đầy đủ.
 * Các fallback get_sub_field() bên dưới là LEGACY FALLBACK: chỉ kích hoạt
 * nếu file được gọi theo kiểu cũ bên trong vòng lặp have_rows() (không khuyến khích).
 */

// Nhận tham số từ $args (cách chuẩn) hoặc get_sub_field() (legacy fallback)
$product_cat     = isset($args['product_cat'])     ? $args['product_cat']                               : get_sub_field('product_cat');
$cat_title       = isset($args['cat_title'])       ? $args['cat_title']                                 : get_sub_field('cat_title');
$cat_link        = isset($args['cat_link'])        ? $args['cat_link']                                  : get_sub_field('cat_link');
$banner_title    = isset($args['banner_title'])    ? $args['banner_title']                              : get_sub_field('banner_title');
$banner_desc     = isset($args['banner_desc'])     ? $args['banner_desc']                               : get_sub_field('banner_desc');
$banner_img      = isset($args['banner_img'])      ? $args['banner_img']                                : get_sub_field('banner_img');
$banner_btn_text = isset($args['banner_btn_text']) ? $args['banner_btn_text']                           : ( get_sub_field('banner_btn_text') ?: 'MUA NGAY' );
$banner_btn_link = isset($args['banner_btn_link']) ? $args['banner_btn_link']                           : ( get_sub_field('banner_btn_link') ?: '#' );
$banner_bg_color = isset($args['banner_bg_color']) ? $args['banner_bg_color']                           : ( get_sub_field('banner_bg_color') ?: 'from-xiaomi-yellow to-tertiary-fixed' );

// Resolve category term first
$term = null;
$term_id = 0;

if ($product_cat) {
    $term = is_object($product_cat) ? $product_cat : get_term($product_cat, 'product_cat');
}

if (!$term || is_wp_error($term)) {
    if (!empty($cat_title)) {
        $term = get_term_by('name', $cat_title, 'product_cat');
        if (!$term) {
            $term = get_term_by('slug', sanitize_title($cat_title), 'product_cat');
        }
    }
}

if ($term && !is_wp_error($term)) {
    $term_id = $term->term_id;
    if (empty($cat_title)) $cat_title = $term->name;
    if (empty($cat_link) || $cat_link === '#') $cat_link = get_term_link($term);
}

if (empty($cat_title)) $cat_title = 'Sản phẩm Xiaomi';
if (empty($cat_link) || is_wp_error($cat_link)) $cat_link = '#';
if ($banner_btn_link === '#' && $cat_link !== '#') $banner_btn_link = $cat_link;

// Resolve banner image if not set
if (empty($banner_img) && $term_id) {
    $thumb_id = get_term_meta($term_id, 'thumbnail_id', true);
    if ($thumb_id) {
        $banner_img = wp_get_attachment_url($thumb_id);
    }
}

// Fallback values if banner text/image is not set
if (!$banner_title) {
    $banner_title = "Siêu Phẩm<br/>" . esc_html($cat_title);
}
if (!$banner_desc) {
    $banner_desc = "Khám phá các sản phẩm chính hãng Xiaomi tốt nhất";
}
if (!$banner_img) {
    $banner_img = "https://lh3.googleusercontent.com/aida-public/AB6AXuCZoT_h2iJ6lG4pVOknKHLQUwhNBH2H6nRtAhTFrCRZCk187XpzgEPOfLCjeyGlXEkQuETQ8_5MjxHaRcSRaAfKMg0yXkX5K7l2gWDQtrjY7cRHeb0_SWiUrk4auPRh5Ha7X64B9Ns_aYF3hG_fF-DLBKx1bhk7tLgKKHSokDFFTWCJtLT2p5mBLpoYZ61oFy19uVZeL2_ns_x7Grq7b4FoURJaqr-95lnvtldUMFhuqEJO8OOfZ4o0";
}
?>
<!-- 4. Product Sections -->
<section class="bg-surface-container-low py-8 sm:py-10">
    <div class="max-w-[1440px] mx-auto px-4">
        <div class="flex items-center justify-between mb-5">
            <h2 class="text-[24px] leading-tight font-black text-primary border-l-4 border-secondary pl-3 uppercase tracking-tight"><?php echo esc_html($cat_title); ?></h2>
            <a class="text-primary font-bold text-[13px] flex items-center gap-1 hover:underline" href="<?php echo esc_url($cat_link); ?>">Xem tất cả <span class="material-symbols-outlined text-[18px]">chevron_right</span></a>
        </div>
        <div class="flex flex-col lg:flex-row gap-5">
            <!-- Left Split: Vertical Banner -->
            <div class="lg:w-1/5 bg-gradient-to-br <?php echo esc_attr($banner_bg_color); ?> rounded-3xl p-4 flex flex-col items-center text-center shadow-[0_16px_28px_-18px_rgba(15,23,42,0.28)] min-h-[160px] lg:min-h-[340px] border border-white/20">
                <div class="text-[22px] font-black text-primary leading-tight mt-3 uppercase"><?php echo wp_kses_post($banner_title); ?></div>
                <p class="mt-3 text-sm font-medium text-tertiary"><?php echo esc_html($banner_desc); ?></p>
                <div class="mt-4 lg:mt-6 size-16 lg:size-28 hidden md:block">
                    <img loading="lazy" class="w-full h-full object-contain" src="<?php echo esc_url($banner_img); ?>" alt="<?php echo esc_attr(strip_tags($banner_title)); ?>"/>
                </div>
                <a href="<?php echo esc_url($banner_btn_link); ?>" class="mt-auto bg-primary text-white px-6 py-2.5 rounded-full font-bold shadow-sm hover:bg-surface-tint transition-all active:scale-95 inline-block text-sm"><?php echo esc_html($banner_btn_text); ?></a>
            </div>
            
            <!-- Right Split: Product Grid -->
            <div class="lg:w-4/5 grid grid-cols-2 md:grid-cols-4 gap-3">
                <?php
                $products_query = null;

                if ($term_id) {
                    $products_query = new WP_Query([
                        'post_type'      => 'product',
                        'post_status'    => 'publish',
                        'posts_per_page' => 8,
                        'tax_query'      => [
                            [
                                'taxonomy'         => 'product_cat',
                                'field'            => 'term_id',
                                'terms'            => $term_id,
                                'include_children' => true,
                            ]
                        ]
                    ]);
                }

                // Bỏ fallback query theo keyword vì dễ lấy nhầm Tivi vào danh mục Thiết Bị Gia Đình.
                // Nếu danh mục trống, hiển thị thông báo "Chưa có sản phẩm" chuẩn WooCommerce.

                // Nếu vẫn không có query hoặc không có bài viết, khởi tạo query rỗng an toàn
                if (!$products_query) {
                    $products_query = new WP_Query(['post__in' => [0]]);
                }

                if ($products_query->have_posts()):
                    while ($products_query->have_posts()): $products_query->the_post();
                        global $product;
                        $rating_count = $product->get_rating_count();
                        $average_rating = $product->get_average_rating();
                        ?>
                        <div class="product-card bg-white p-3 rounded-2xl border border-slate-200 relative flex flex-col transition-all cursor-pointer group hover:-translate-y-1 hover:shadow-[0_14px_28px_-10px_rgba(15,23,42,0.2)] h-fit self-start md:h-full md:self-auto">
                            
                            <?php if ( $product->is_on_sale() ) : ?>
                                <span class="absolute top-2 right-2 bg-secondary text-white text-[10px] font-bold px-2 py-1 rounded-full z-10">SALE</span>
                            <?php endif; ?>

                            <?php if ( $product->is_featured() ) : ?>
                                <div class="absolute top-2 left-2 bg-xiaomi-yellow text-on-surface text-[10px] font-bold px-2 py-1 rounded z-10">HOT</div>
                            <?php endif; ?>

                            <a href="<?php echo esc_url( get_permalink() ); ?>" class="block aspect-square w-full mb-3 relative overflow-hidden rounded-xl bg-slate-50">
                                <?php 
                                if ( has_post_thumbnail() ) {
                                    echo get_the_post_thumbnail( get_the_ID(), 'woocommerce_thumbnail', [ 'class' => 'w-full h-full object-contain group-hover:scale-105 transition-transform duration-300 mix-blend-multiply' ] );
                                } else {
                                    echo '<img loading="lazy" class="w-full h-full object-contain" src="' . esc_url( get_template_directory_uri() . '/assets/img/placeholder.jpg' ) . '" alt="Placeholder"/>';
                                }
                                ?>
                            </a>
                            
                            <a href="<?php echo esc_url( get_permalink() ); ?>" class="block mt-2 after:absolute after:inset-0 after:z-20">
                                <h3 class="text-[13px] font-bold text-slate-800 line-clamp-2 min-h-[38px] leading-[1.4] group-hover:text-primary transition-colors"><?php echo esc_html( get_the_title() ); ?></h3>
                            </a>
                            
                            <div class="mt-2 text-secondary font-black text-body-lg flex items-baseline gap-2 flex-wrap">
                                <?php 
                                if ( $product->is_type('variable') ) {
                                    $min_regular = $product->get_variation_regular_price('min', true);
                                    $min_sale = $product->get_variation_sale_price('min', true);
                                    
                                    if ( $product->is_on_sale() && $min_sale < $min_regular ) {
                                        echo '<del aria-hidden="true" class="text-outline text-body-sm font-normal mr-2">' . wp_kses_post( wc_price( $min_regular ) ) . '</del>';
                                        echo '<ins aria-hidden="true" class="no-underline">' . wp_kses_post( wc_price( $min_sale ) ) . '</ins>';
                                    } else {
                                        echo $min_regular > 0 ? wp_kses_post( wc_price( $min_regular ) ) : esc_html__( 'Liên hệ', 'omni-commerce' );
                                    }
                                } else {
                                    $price = $product->get_price();
                                    echo $price > 0 ? wp_kses_post( $product->get_price_html() ) : esc_html__( 'Liên hệ', 'omni-commerce' );
                                }
                                ?>
                            </div>
                            
                            <div class="mt-2 flex items-center text-xiaomi-yellow">
                                <?php 
                                for ( $i = 1; $i <= 5; $i++ ) {
                                    $fill = ( $i <= round( $average_rating ) ) ? '1' : '0';
                                    echo '<span class="material-symbols-outlined text-[14px]" style="font-variation-settings: \'FILL\' ' . $fill . ';">star</span>';
                                }
                                ?>
                                <span class="text-outline text-label-sm ml-1">(<?php echo esc_html( $rating_count ?: 0 ); ?>)</span>
                            </div>


                        </div>
                        <?php
                    endwhile;
                    wp_reset_postdata();
                else:
                    // Fallback if no products found
                    echo '<p class="col-span-full text-center text-outline">Chưa có sản phẩm nào trong danh mục này.</p>';
                endif;
                ?>
            </div>
        </div>
        
        <div class="mt-10 text-center">
            <a href="<?php echo esc_url($cat_link); ?>" class="inline-block bg-primary text-white px-12 py-3 rounded-full font-bold shadow-md hover:bg-primary-container transition-colors uppercase text-label-md">Xem tất cả sản phẩm</a>
        </div>
    </div>
</section>
