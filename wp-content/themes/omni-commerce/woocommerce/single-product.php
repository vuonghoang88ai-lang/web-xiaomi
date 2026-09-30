<?php
/**
 * The Template for displaying all single products
 *
 * @see         https://woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     3.6.0
 */

defined( 'ABSPATH' ) || exit;

get_header(); 
?>
<style>
/* Ẩn các dropdown mặc định của WooCommerce cho Variable Product theo quy tắc UX */
.variations_form table.variations { display: none !important; }
.variations_form .single_variation_wrap .woocommerce-variation-price { display: none !important; }

/* Ẩn nút submit mặc định của WooCommerce */
.single_add_to_cart_button { display: none !important; }

@keyframes shake {
  0%, 100% { transform: translateX(0); }
  25% { transform: translateX(-5px); }
  75% { transform: translateX(5px); }
}
.animate-shake { animation: shake 0.2s ease-in-out 0s 2; }
</style>
<?php
while ( have_posts() ) :
	the_post();
	global $product;
    if ( ! is_a( $product, 'WC_Product' ) ) {
        $product = wc_get_product( get_the_ID() );
    }

    $product_id     = $product->get_id();
    $hotline        = get_field( 'mi_hotline_number', 'option' ) ?: '0822.83.4444';
    $hotline_clean  = preg_replace( '/[^0-9]/', '', $hotline );
    $fab_messenger  = get_field( 'mi_hf_fab_messenger', 'option' ) ?: 'https://m.me/xiaomivn';
    $fab_zalo       = get_field( 'mi_hf_fab_zalo', 'option' ) ?: 'https://zalo.me/0822834444';

    // Safe pricing for both Simple and Variable products (Quy tắc 2 AGENTS.md)
    $is_variable = $product->is_type( 'variable' );
    if ( $is_variable ) {
        $regular_price = (float) $product->get_variation_regular_price( 'max', true );
        $sale_price    = (float) $product->get_variation_sale_price( 'min', true );
        $min_price     = (float) $product->get_variation_price( 'min', true );
        $has_sale      = $product->is_on_sale() && $sale_price > 0 && $sale_price < $regular_price;
        $display_price = $has_sale ? $sale_price : $min_price;
    } else {
        $regular_price = (float) $product->get_regular_price();
        $sale_price    = (float) $product->get_sale_price();
        $min_price     = (float) $product->get_price();
        $has_sale      = $product->is_on_sale() && $sale_price > 0 && $sale_price < $regular_price;
        $display_price = $has_sale ? $sale_price : $min_price;
    }

    // Gallery images preparation
    $main_image_id  = $product->get_image_id();
    $attachment_ids = $product->get_gallery_image_ids();
    $all_image_ids  = array();
    if ( $main_image_id ) {
        $all_image_ids[] = $main_image_id;
    }
    if ( ! empty( $attachment_ids ) ) {
        foreach ( $attachment_ids as $aid ) {
            if ( $aid != $main_image_id ) {
                $all_image_ids[] = $aid;
            }
        }
    }
    $main_image_url = $main_image_id ? wp_get_attachment_image_url( $main_image_id, 'full' ) : wc_placeholder_img_src( 'woocommerce_single' );
?>

<!-- Main Content Wrapper -->
<main id="primary" class="max-w-[1440px] mx-auto px-4 py-6 pb-24 lg:pb-8">
    
    <div class="mb-4">
        <?php do_action( 'woocommerce_before_single_product' ); ?>
    </div>

    <!-- Breadcrumb -->
    <div class="mb-3">
        <?php woocommerce_breadcrumb( array(
            'delimiter'   => ' <span class="text-outline">/</span> ',
            'wrap_before' => '<nav class="woocommerce-breadcrumb text-sm text-outline flex items-center gap-2 flex-wrap mb-2">',
            'wrap_after'  => '</nav>',
            'before'      => '<span class="text-primary hover:underline">',
            'after'       => '</span>',
            'home'        => _x( 'Trang chủ', 'breadcrumb', 'woocommerce' ),
        ) ); ?>
    </div>

    <!-- Product Meta Header (Exact match with user image) -->
    <div class="mb-4 pb-4 border-b border-gray-200">
        <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2.5"><?php echo esc_html( get_the_title() ); // P1-fix: the_title() không tự escape HTML ?></h1>
        <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-outline">
            <?php 
            // L1-fix: $rating_count đã bị xóa — biến này không được dùng ở bất kỳ đâu trong template
            $views_count  = get_post_meta( $product_id, 'views_count', true ) ?: '0';
            $review_count = (int) $product->get_review_count() ?: 0;
            $sales        = get_post_meta( $product_id, 'total_sales', true ) ?: '0';
            ?>
            <span class="text-gray-600">Lượt xem: <strong class="text-gray-800 font-medium"><?php echo esc_html( $views_count ); ?></strong></span>
            
            <span class="text-gray-600">Tình trạng: <strong class="font-bold <?php echo esc_attr( $product->is_in_stock() ? 'text-primary' : 'text-error' ); ?>"><?php echo esc_html( $product->is_in_stock() ? 'CÒN HÀNG' : 'HẾT HÀNG' ); ?></strong></span>
            
            <div class="flex items-center gap-1.5">
                <div class="flex items-center text-yellow-400">
                    <?php for ( $i = 1; $i <= 5; $i++ ) : ?>
                        <span class="material-symbols-outlined !text-[18px]" style="font-variation-settings: 'FILL' 1;">star</span>
                    <?php endfor; ?>
                </div>
                <span class="text-gray-700">(<?php echo esc_html( $review_count ); ?> đánh giá)</span>
            </div>
            
            <span class="text-gray-400">|</span>
            <span class="text-gray-600">Đã bán <strong class="text-gray-800 font-medium"><?php echo esc_html( $sales ); ?></strong></span>
        </div>
    </div>

    <!-- Main Product Section (3-Column Flexbox) -->
    <div id="product-<?php the_ID(); ?>" <?php wc_product_class( 'flex flex-col lg:flex-row gap-8', $product ); ?>>
        
        <!-- Col 1: Media Gallery (35%) -->
        <div class="lg:w-[35%] min-w-0">
            <!-- Main Image Container -->
            <div id="main-image-container" class="relative bg-white rounded-xl shadow-sm overflow-hidden aspect-square sm:aspect-video lg:aspect-[4/3] border border-outline-variant mb-4 group flex items-center justify-center p-2 cursor-crosshair">
                <img id="main-product-image" class="w-full h-full object-contain transition-transform duration-200 ease-out" src="<?php echo esc_url( $main_image_url ); ?>" alt="<?php echo esc_attr( $product->get_name() ); ?>" />
                
                <?php if ( count( $all_image_ids ) > 1 ) : ?>
                    <button type="button" id="gallery-prev" class="absolute left-2 top-1/2 -translate-y-1/2 bg-white/80 hover:bg-white text-primary p-2 rounded-full shadow-md border border-gray-200 transition-all z-10 flex items-center justify-center hover:scale-110" aria-label="Ảnh trước">
                        <span class="material-symbols-outlined !text-xl">chevron_left</span>
                    </button>
                    <button type="button" id="gallery-next" class="absolute right-2 top-1/2 -translate-y-1/2 bg-white/80 hover:bg-white text-primary p-2 rounded-full shadow-md border border-gray-200 transition-all z-10 flex items-center justify-center hover:scale-110" aria-label="Ảnh sau">
                        <span class="material-symbols-outlined !text-xl">chevron_right</span>
                    </button>
                <?php endif; ?>
            </div>
            
            <!-- Thumbnails Slider -->
            <?php if ( count( $all_image_ids ) > 1 ) : ?>
            <div id="gallery-thumbs-container" class="flex gap-2 overflow-x-auto pb-2 mb-4 scrollbar-hide">
                <?php foreach ( $all_image_ids as $index => $img_id ) : 
                    $thumb_url = wp_get_attachment_image_url( $img_id, 'woocommerce_gallery_thumbnail' ) ?: wp_get_attachment_image_url( $img_id, 'thumbnail' );
                    $full_url  = wp_get_attachment_image_url( $img_id, 'full' );
                    $active_cls = ( $index === 0 ) ? 'border-2 border-secondary' : 'border border-outline-variant opacity-75 hover:opacity-100';
                ?>
                    <div class="gallery-thumb w-16 h-16 flex-shrink-0 <?php echo esc_attr( $active_cls ); ?> rounded-lg overflow-hidden cursor-pointer p-1 bg-white transition-all" data-full="<?php echo esc_url( $full_url ); ?>" data-index="<?php echo esc_attr( $index ); ?>">
                        <img class="w-full h-full object-contain" src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( $product->get_name() ); ?>" loading="lazy" />
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <!-- 4 Quick Icon Tabs under gallery -->
            <div class="grid grid-cols-4 gap-2 text-center text-xs pt-2 border-t border-gray-200">
                <a href="#technical-specs" class="cursor-pointer hover:text-primary flex flex-col items-center py-1 transition-colors group">
                    <div class="w-10 h-10 rounded-lg border border-gray-200 flex items-center justify-center text-gray-600 group-hover:border-primary group-hover:text-primary transition-colors bg-white">
                        <span class="material-symbols-outlined !text-[20px]">image</span>
                    </div>
                    <p class="mt-1 font-medium text-[11px] text-gray-700 group-hover:text-primary">Thông số</p>
                </a>
                <a href="#product-<?php the_ID(); ?>" class="cursor-pointer hover:text-primary flex flex-col items-center py-1 transition-colors group">
                    <div class="w-10 h-10 rounded-lg border border-gray-200 flex items-center justify-center text-gray-600 group-hover:border-primary group-hover:text-primary transition-colors bg-white">
                        <span class="material-symbols-outlined !text-[20px]">smartphone</span>
                    </div>
                    <p class="mt-1 font-medium text-[11px] text-gray-700 group-hover:text-primary">Sản phẩm</p>
                </a>
                <a href="#shipping-policy" class="cursor-pointer hover:text-primary flex flex-col items-center py-1 transition-colors group">
                    <div class="w-10 h-10 rounded-lg border border-gray-200 flex items-center justify-center text-gray-600 group-hover:border-primary group-hover:text-primary transition-colors bg-white">
                        <span class="material-symbols-outlined !text-[20px]">directions_car</span>
                    </div>
                    <p class="mt-1 font-medium text-[11px] text-gray-700 group-hover:text-primary">Giao hàng</p>
                </a>
                <a href="#reviews-section" class="cursor-pointer hover:text-primary flex flex-col items-center py-1 transition-colors group">
                    <div class="w-10 h-10 rounded-lg border border-gray-200 flex items-center justify-center text-gray-600 group-hover:border-primary group-hover:text-primary transition-colors bg-white">
                        <span class="material-symbols-outlined !text-[20px]">sms</span>
                    </div>
                    <p class="mt-1 font-medium text-[11px] text-gray-700 group-hover:text-primary">Bình luận</p>
                </a>
            </div>
        </div>

        <!-- Col 2: Pricing, Warranty Dropdown & Promo Box (35%) -->
        <div class="lg:w-[35%] min-w-0 flex flex-col gap-4">
            <!-- Price Display with HOT Badge -->
            <div class="flex items-center gap-3 flex-wrap price-display-wrapper">
                <!-- Fire Hot Badge -->
                <div class="flex flex-col items-center leading-none">
                    <span class="text-xs font-black text-red-600 uppercase tracking-tighter drop-shadow-sm flex items-center">
                        <span class="material-symbols-outlined !text-base text-red-500 mr-0.5" style="font-variation-settings: 'FILL' 1;">local_fire_department</span>
                        HOT
                    </span>
                </div>
                
                <?php if ( $display_price > 0 ) : ?>
                    <span class="text-3xl md:text-4xl font-extrabold text-secondary tracking-tight" id="main-display-price">
                        <?php echo number_format( $display_price, 0, ',', '.' ); ?> <span class="underline text-2xl font-bold">đ</span>
                    </span>
                    
                    <?php 
                    $percent = 0;
                    if ($has_sale && $regular_price > $display_price) {
                        $percent = round( ( ( $regular_price - $display_price ) / $regular_price ) * 100 );
                    }
                    ?>
                    <span id="main-regular-price" class="text-base text-gray-400 line-through <?php echo ($has_sale && $regular_price > $display_price) ? '' : 'hidden'; ?>">
                        <?php echo number_format( $regular_price, 0, ',', '.' ); ?> đ
                    </span>
                    
                    <span id="main-sale-badge" class="bg-secondary text-white px-2 py-0.5 rounded text-xs font-bold <?php echo ($percent > 0) ? '' : 'hidden'; ?>">
                        Giảm <?php echo esc_html( $percent ); ?>%
                    </span>
                <?php else : ?>
                    <span class="text-3xl font-bold text-secondary">Liên hệ</span>
                <?php endif; ?>
            </div>

            <!-- Warranty Dropdown Select -->
            <div class="flex items-center gap-2 text-sm bg-gray-50 border border-gray-200 rounded-lg p-2.5">
                <span class="text-gray-700 font-semibold whitespace-nowrap">Chế độ bảo hành</span>
                <?php
                // H7: Giá gói bảo hành vàng đọc từ ACF option, fallback về 500,000
                $warranty_gold_price = (int) ( function_exists('get_field') ? get_field('mi_warranty_gold_price', 'option') : 0 );
                if ( $warranty_gold_price <= 0 ) $warranty_gold_price = 500000;
                $warranty_gold_label = sprintf( 'Gói bảo hành vàng (+%sđ)', number_format( $warranty_gold_price, 0, ',', '.' ) );
                ?>
                <select id="warranty-package-select" class="text-sm border border-gray-300 rounded px-2.5 py-1 bg-white focus:ring-1 focus:ring-primary outline-none flex-1 cursor-pointer">
                    <option value="default">Bảo hành mặc định</option>
                    <option value="gold"><?php echo esc_html( $warranty_gold_label ); ?></option>
                </select>
                <a href="#warranty-section" class="text-gray-400 hover:text-primary transition-colors flex items-center" title="Xem chi tiết chế độ bảo hành">
                    <span class="material-symbols-outlined !text-lg">help_outline</span>
                </a>
            </div>
            
            <!-- Promotion Box -->
            <div id="shipping-policy" class="border border-outline-variant rounded-xl overflow-hidden shadow-sm bg-white mt-4">
                <!-- Promo Header Banner -->
                <div class="bg-primary text-white px-4 py-2.5 flex items-center gap-2.5 rounded-t-lg">
                    <span class="material-symbols-outlined !text-[22px]" style="font-variation-settings: 'FILL' 1;">featured_seasonal_and_gifts</span>
                    <span class="font-bold text-sm tracking-wide uppercase text-white">THÔNG TIN KHUYẾN MẠI</span>
                </div>
                
                <?php
                // H4-H6: Tiêu đề và danh sách ưu đãi đọc từ ACF option, fallback về mảng tĩnh
                $promo_title   = function_exists('get_field') ? get_field('mi_promo_box_title', 'option') : '';
                $promo_title   = $promo_title ?: sprintf( 'Ưu đãi khi mua hàng tại %s:', get_bloginfo('name') );

                $promo_fallback = array(
                    array( 'text' => 'Sẵn hàng giao và lắp đặt ngay tại Hà Nội - Hồ Chí Minh - Tp Hà Tĩnh - Tp Vinh, Nghệ An', 'bold' => true ),
                    array( 'text' => 'Giá rẻ nhất Việt Nam', 'bold' => false ),
                    array( 'text' => 'Trả góp 0% qua thẻ tín dụng', 'bold' => false ),
                    array( 'text' => 'Cài đặt giao diện và giọng nói Tiếng Việt miễn phí', 'bold' => false ),
                    array( 'text' => 'Hỗ trợ phần mềm trọn đời', 'bold' => false ),
                );
                $has_promo_acf = function_exists('have_rows') && have_rows('mi_promo_box_items', 'option');
                ?>
                <div class="p-4 text-sm space-y-2.5 text-gray-800">
                    <p class="font-bold text-primary flex items-center gap-1.5 text-xs uppercase tracking-wider mb-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-primary inline-block"></span>
                        <?php echo esc_html( $promo_title ); ?>
                    </p>

                    <?php if ( $has_promo_acf ) :
                        while ( have_rows('mi_promo_box_items', 'option') ) : the_row();
                            $item_text = get_sub_field('promo_item_text');
                            $item_bold = (bool) get_sub_field('promo_item_bold');
                    ?>
                        <div class="flex items-start gap-2 text-[13px] leading-relaxed">
                            <span class="text-red-500 font-bold shrink-0 mt-0.5">♦</span>
                            <p <?php if ( $item_bold ) echo 'class="font-semibold text-red-600"'; ?>><?php echo esc_html( $item_text ); ?></p>
                        </div>
                    <?php endwhile;
                    else :
                        foreach ( $promo_fallback as $item ) :
                    ?>
                        <div class="flex items-start gap-2 text-[13px] leading-relaxed">
                            <span class="text-red-500 font-bold shrink-0 mt-0.5">♦</span>
                            <p <?php if ( $item['bold'] ) echo 'class="font-semibold text-red-600"'; ?>><?php echo esc_html( $item['text'] ); ?></p>
                        </div>
                    <?php endforeach;
                    endif; ?>
                </div>
            </div>

            <!-- Conversion Action Buttons -->
            <div class="grid grid-cols-1 gap-2.5">
                <?php if ( $is_variable ) : ?>
                    <div class="variable-product-action-wrapper bg-white border border-outline-variant rounded-xl p-4 shadow-sm">
                        <?php woocommerce_variable_add_to_cart(); ?>
                        <div class="flex w-full gap-2.5 mt-4 custom-action-buttons">
                            <button type="button" class="btn-add-to-cart-custom flex-1 bg-surface hover:bg-primary/5 text-primary border border-primary py-3 px-2 rounded-xl font-bold text-[14px] transition-all duration-200 active:scale-[0.99] flex flex-col items-center justify-center text-center leading-tight">
                                <span>THÊM VÀO GIỎ</span>
                            </button>
                            <button type="button" class="btn-buy-now-custom flex-1 bg-secondary hover:bg-secondary-fixed-variant text-white py-3 px-2 rounded-xl font-bold text-[14px] shadow-lg transition-all duration-200 active:scale-[0.99] flex flex-col items-center justify-center text-center leading-tight">
                                <span>MUA NGAY</span>
                            </button>
                        </div>
                    </div>
                <?php else : ?>
                    <form class="cart" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>" method="post" enctype='multipart/form-data'>
                        <?php do_action( 'woocommerce_before_add_to_cart_button' ); ?>
                        <input type="hidden" name="add-to-cart" value="<?php echo esc_attr( $product_id ); ?>" />
                        
                        <div class="flex items-center gap-3 mb-4">
                            <span class="text-sm font-semibold text-gray-700">Số lượng:</span>
                            <?php 
                            woocommerce_quantity_input( array(
                                'min_value'   => apply_filters( 'woocommerce_quantity_input_min', $product->get_min_purchase_quantity(), $product ),
                                'max_value'   => apply_filters( 'woocommerce_quantity_input_max', $product->get_max_purchase_quantity(), $product ),
                                'input_value' => isset( $_POST['quantity'] ) ? wc_stock_amount( wp_unslash( $_POST['quantity'] ) ) : $product->get_min_purchase_quantity(),
                                'classes'     => apply_filters( 'woocommerce_quantity_input_classes', array( 'input-text', 'qty', 'text', 'border', 'border-gray-300', 'rounded', 'px-3', 'py-1.5', 'w-16', 'text-center', 'outline-none', 'focus:ring-1', 'focus:ring-primary', 'font-medium' ), $product ),
                            ) ); 
                            ?>
                        </div>
                        <input type="hidden" id="mi_warranty_hidden" name="mi_warranty_package" value="default" />
                        
                        <div class="flex w-full gap-2.5 mt-4 custom-action-buttons">
                            <button type="button" class="btn-add-to-cart-custom flex-1 bg-surface hover:bg-primary/5 text-primary border border-primary py-3 px-2 rounded-xl font-bold text-[14px] transition-all duration-200 active:scale-[0.99] flex flex-col items-center justify-center text-center leading-tight">
                                <span>THÊM VÀO GIỎ</span>
                            </button>
                            <button type="button" class="btn-buy-now-custom flex-1 bg-secondary hover:bg-secondary-fixed-variant text-white py-3 px-2 rounded-xl font-bold text-[14px] shadow-lg transition-all duration-200 active:scale-[0.99] flex flex-col items-center justify-center text-center leading-tight">
                                <span>MUA NGAY</span>
                            </button>
                        </div>
                        <!-- Nút mặc định WooCommerce (bị ẩn) -->
                        <button type="submit" class="hidden single_add_to_cart_button button alt"><?php echo esc_html( $product->single_add_to_cart_text() ); ?></button>
                        
                        <?php do_action( 'woocommerce_after_add_to_cart_button' ); ?>
                    </form>
                <?php endif; ?>
                
                <button type="button" onclick="document.getElementById('mi-installment-modal').classList.remove('hidden', 'opacity-0'); document.getElementById('mi-installment-modal').classList.add('flex', 'opacity-100'); document.querySelector('#mi-installment-modal > div').classList.replace('scale-95', 'scale-100');" class="border-2 border-secondary text-secondary hover:bg-secondary/5 py-3 px-4 rounded-xl font-bold text-base text-center flex flex-col items-center justify-center transition-colors w-full bg-transparent">
                    <span>MUA TRẢ GÓP 0%</span>
                    <span class="text-xs font-normal opacity-90">Xét duyệt nhanh qua điện thoại trong 5 phút</span>
                </button>
            </div>

            <!-- Quick Consultation Callback Box -->
            <div class="bg-surface-container rounded-xl p-4 border border-outline-variant">
                <p class="font-bold text-primary mb-2 text-center uppercase tracking-wider text-xs">Tư vấn miễn phí ngay</p>
                <form id="mi-quick-consult-form" class="flex flex-col gap-2" onsubmit="event.preventDefault(); const phone = this.querySelector('input').value; if(phone){ this.reset(); }">
                    <input class="rounded-lg border-outline-variant bg-white focus:ring-primary focus:border-primary text-sm p-2.5 outline-none" placeholder="Số điện thoại của bạn..." type="tel" required pattern="[0-9]{9,11}" />
                    <button type="submit" class="bg-primary hover:bg-primary-container text-white py-2 rounded-lg font-bold text-sm transition-colors shadow-sm">Gọi cho tôi</button>
                </form>
            </div>
        </div>

        <!-- Col 3: Variants & Warranty (Exact match with user image) (30%) -->
        <div class="lg:w-[30%] min-w-0 flex flex-col gap-5">
            
            <!-- LỰA CHỌN PHIÊN BẢN (2-Row Card Structure: Name + Price) -->
            <div>
                <p class="font-bold mb-3 text-base text-gray-900">Lựa chọn phiên bản</p>
                
                <?php
                // D5: $current_title & $prefixes_to_remove dùng cho cả 2 nhánh.
                // $prefix (4 từ đầu) chỉ cần trong nhánh non-variable nên khai báo ở đó.
                $versions        = array();
                $current_title   = get_the_title();
                $prefixes_to_remove = array(
                    'Tivi Xiaomi ',
                    'Tủ Lạnh Xiaomi ',
                    'Robot hút bụi lau nhà Xiaomi ',
                    'Robot hút bụi Xiaomi ',
                    'Máy lọc không khí Xiaomi ',
                    'Máy hút bụi Xiaomi ',
                    'Quạt thông minh Xiaomi ',
                    'Thiết bị gia đình Xiaomi ',
                );

                if ( $is_variable ) {
                    $available_variations = $product->get_available_variations();

                    // D5-fix: dùng $current_title thay vì $prefix (4 từ có thể thiếu) để fallback $cat_prefix
                    if ( has_term('tivi-xiaomi', 'product_cat', $product_id) )            $cat_prefix = 'Tivi Xiaomi ';
                    elseif ( has_term('tu-lanh-xiaomi', 'product_cat', $product_id) )     $cat_prefix = 'Tủ lạnh Xiaomi ';
                    elseif ( has_term('robot-hut-bui-xiaomi', 'product_cat', $product_id) ) $cat_prefix = 'Robot hút bụi Xiaomi ';
                    elseif ( has_term('may-loc-khong-khi-xiaomi', 'product_cat', $product_id) ) $cat_prefix = 'Máy lọc không khí Xiaomi ';
                    else $cat_prefix = trim( str_replace( $prefixes_to_remove, '', $current_title ) ) . ' ';

                    foreach ( $available_variations as $var ) {
                        $attr_values = array();
                        foreach ( $var['attributes'] as $key => $val ) {
                            $term        = get_term_by( 'slug', $val, str_replace( 'attribute_', '', $key ) );
                            $attr_values[] = $term ? $term->name : $val;
                        }
                        $var_name = $cat_prefix . implode( ', ', $attr_values );

                        $versions[] = array(
                            'name'         => $var_name,
                            'price'        => strip_tags( wc_price( $var['display_price'] ) ),
                            'url'          => 'javascript:void(0);',
                            'is_active'    => false,
                            'is_variation' => true,
                            'variation_id' => $var['variation_id'],
                            'attributes'   => $var['attributes'],
                        );
                    }
                } else {
                    // Priority 1: ACF Relationship Field
                    if ( function_exists('get_field') && $related = get_field('related_versions', $product_id) ) {
                        foreach ( $related as $p_id ) {
                            $p = wc_get_product( $p_id );
                            if ( $p && $p->is_visible() ) {
                                $versions[] = array(
                                    'name'         => $p->get_name(),
                                    'price'        => strip_tags( wc_price( $p->get_price() ) ),
                                    'url'          => $p->get_permalink(),
                                    'is_active'    => ( $p_id == $product_id ),
                                    'is_variation' => false,
                                );
                            }
                        }
                    }

                    // Priority 2: Fallback query sản phẩm cùng series trong cùng danh mục
                    // D5-fix: khai báo $prefix ở đây, không ở scope ngoài
                    if ( empty( $versions ) ) {
                        $title_parts = explode( ' ', $current_title );
                        $prefix      = implode( ' ', array_slice( $title_parts, 0, 4 ) );
                        $cat_ids     = $product->get_category_ids();

                        if ( ! empty( $prefix ) && ! empty( $cat_ids ) ) {
                            $sim_args = array(
                                'post_type'      => 'product',
                                'posts_per_page' => 6,
                                'post_status'    => 'publish',
                                's'              => $prefix,
                                'tax_query'      => array(
                                    array(
                                        'taxonomy' => 'product_cat',
                                        'field'    => 'term_id',
                                        'terms'    => $cat_ids[0],
                                    ),
                                ),
                            );
                            $sim_query = new WP_Query( $sim_args );
                            if ( $sim_query->have_posts() ) {
                                while ( $sim_query->have_posts() ) {
                                    $sim_query->the_post();
                                    $sim_p = wc_get_product( get_the_ID() );

                                    // D6-fix: dùng str_replace nhất quán cho mọi danh mục, bỏ regex thiếu coverage
                                    $short_name = trim( str_replace( $prefixes_to_remove, '', get_the_title() ) );

                                    $versions[] = array(
                                        'name'         => $short_name,
                                        'price'        => strip_tags( wc_price( $sim_p->get_price() ) ),
                                        'url'          => get_permalink(),
                                        'is_active'    => ( get_the_ID() == $product_id ),
                                        'is_variation' => false,
                                    );
                                }
                                wp_reset_postdata();
                            }
                        }
                    }

                    // Absolute fallback
                    if ( empty( $versions ) ) {
                        $versions[] = array(
                            'name'         => trim( str_replace( $prefixes_to_remove, '', $current_title ) ),
                            'price'        => strip_tags( wc_price( $product->get_price() ) ),
                            'url'          => $product->get_permalink(),
                            'is_active'    => true,
                            'is_variation' => false,
                        );
                    }
                }
                ?>
                <div class="grid grid-cols-2 gap-2.5" id="product-versions-grid">
                    <?php foreach ( $versions as $ver ) :
                        $is_var     = isset( $ver['is_variation'] ) && $ver['is_variation'];
                        // S2+L2-fix: dùng wp_json_encode+esc_attr (chuẩn WP) thay htmlspecialchars(json_encode)
                        // Chỉ tính khi là variation card, tránh gọi hàm thừa cho simple product card
                        $var_attrs  = $is_var ? esc_attr( wp_json_encode( $ver['attributes'] ) ) : '';
                        $active_cls = $ver['is_active'] ? 'border-secondary ring-1 ring-secondary/30 cursor-default' : 'border-gray-300 hover:border-secondary hover:shadow-md cursor-pointer';
                        $text_cls   = $ver['is_active'] ? 'text-gray-900' : 'text-gray-800 group-hover:text-secondary transition-colors';
                    ?>
                        <div class="version-card p-2.5 bg-white border-2 rounded-md text-center transition-all group relative overflow-hidden <?php echo esc_attr($active_cls); ?>" 
                            <?php if ($is_var): ?> data-variation-id="<?php echo esc_attr($ver['variation_id']); ?>" data-attributes="<?php echo $var_attrs; ?>" <?php endif; ?>
                            <?php if (!$is_var && !$ver['is_active']): ?> onclick="window.location.href='<?php echo esc_url($ver['url']); ?>';" <?php endif; ?>>
                            <span class="name block w-full text-[13px] font-bold leading-snug <?php echo esc_attr($text_cls); ?>"><?php echo esc_html( $ver['name'] ); ?></span>
                            <span class="block w-full text-secondary text-xs font-bold mt-1"><?php echo esc_html( $ver['price'] ); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- CHẾ ĐỘ BẢO HÀNH (Exact match with user image) -->
            <div id="warranty-section" class="bg-[#f8f9fa] border border-gray-200 rounded-xl p-4 shadow-sm">
                <div class="flex items-center gap-2.5 mb-3.5">
                    <div class="w-7 h-7 rounded-full bg-emerald-500 flex items-center justify-center text-white shrink-0 shadow-sm">
                        <span class="material-symbols-outlined !text-lg" style="font-variation-settings: 'FILL' 1;">verified</span>
                    </div>
                    <h3 class="font-extrabold text-gray-800 uppercase text-sm tracking-wide">CHẾ ĐỘ BẢO HÀNH</h3>
                </div>
                
                <ul class="text-xs space-y-2.5 text-gray-700 leading-relaxed list-disc list-outside pl-4">
                    <li><strong class="text-gray-900">Gói bảo hành mặc định:</strong> Bảo hành 30 ngày, lỗi đổi mới trong 15 ngày đầu</li>
                    <li><strong class="text-gray-900">Gói bảo hành vàng:</strong> Bảo hành 24 tháng bao gồm cả main, nguồn, màn hình. Bảo hành lỗi đổi mới trong 15 ngày đầu</li>
                    <li><strong class="text-gray-900">Bảo hành phần cứng:</strong> Bao gồm nguồn, màn hình</li>
                    <li><strong class="text-gray-900">Không bảo hành:</strong> Chập cháy, va đập, rơi rớt, vào nước, thiên tai</li>
                </ul>
                
                <div class="mt-4 pt-3 border-t border-gray-200 flex items-center justify-between">
                    <a href="<?php echo esc_url( home_url( '/tra-cuu-bao-hanh/' ) ); ?>" class="text-primary font-semibold hover:underline flex items-center gap-1.5 text-xs">
                        <span class="material-symbols-outlined !text-base text-primary">qr_code_scanner</span>
                        Tra cứu bảo hành Serial Number (ERP)
                    </a>
                </div>
            </div>
            
            <!-- Hotline & Showroom Quick Card -->
            <div class="border border-gray-200 rounded-xl p-4 bg-white shadow-sm">
                <div class="flex items-center justify-between mb-2">
                    <p class="font-bold text-sm text-gray-800">Hotline hỗ trợ:</p>
                    <a class="text-secondary font-black text-xl hover:underline" href="tel:<?php echo esc_attr( $hotline_clean ); ?>">
                        <?php echo esc_html( $hotline ); ?>
                    </a>
                </div>
                <a class="flex items-center justify-center gap-1.5 text-primary hover:underline text-sm mt-1" href="<?php echo esc_url( home_url( '/lien-he/' ) ); ?>">
                    <span class="material-symbols-outlined !text-lg">location_on</span>
                    Tìm cửa hàng gần bạn nhất
                </a>
            </div>

        </div>
    </div> <!-- End Main Flexbox -->

    <!-- Technical Specs & Feature Banner -->
    <div id="technical-specs" class="mt-12 grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">
        <div class="rounded-xl overflow-hidden border border-outline-variant bg-white p-2 shadow-sm flex items-center justify-center">
            <?php 
            $spec_image = function_exists( 'get_field' ) ? get_field( 'technical_specs_image' ) : null;
            if ( $spec_image && ! empty( $spec_image['ID'] ) ) {
                echo wp_get_attachment_image( $spec_image['ID'], 'full', false, array( 'class' => 'w-full h-auto object-contain rounded-lg' ) );
            } elseif ( count( $all_image_ids ) > 1 ) {
                echo wp_get_attachment_image( $all_image_ids[1], 'full', false, array( 'class' => 'w-full h-auto object-contain rounded-lg' ) );
            } elseif ( $main_image_id ) {
                echo wp_get_attachment_image( $main_image_id, 'full', false, array( 'class' => 'w-full h-auto object-contain rounded-lg' ) );
            }
            ?>
        </div>
        <div>
            <h3 class="text-xl font-bold mb-4 flex items-center gap-2 text-on-surface">
                <span class="material-symbols-outlined text-primary">settings_suggest</span>
                Thông số kỹ thuật
            </h3>
            
            <?php 
            $attributes    = $product->get_attributes();
            $has_acf_specs = function_exists( 'have_rows' ) && have_rows( 'technical_specs', $product_id );

            if ( $has_acf_specs || ! empty( $attributes ) ) :
                // D4: Capture specs rows một lần, tái sử dụng cho cả view rút gọn & modal.
                // Tránh query DB 2 lần và giữ con trỏ ACF have_rows nhất quán.
                ob_start();
                if ( $has_acf_specs ) :
                    while ( have_rows( 'technical_specs', $product_id ) ) : the_row();
                        $spec_label = get_sub_field( 'spec_label' );
                        $spec_value = get_sub_field( 'spec_value' );
                        echo '<tr>';
                        echo '<td class="p-3 font-semibold text-outline-variant w-1/3 border-b border-outline-variant/30">' . esc_html( $spec_label ) . '</td>';
                        echo '<td class="p-3 text-on-surface border-b border-outline-variant/30">' . wp_kses_post( $spec_value ) . '</td>';
                        echo '</tr>';
                    endwhile;
                else :
                    foreach ( $attributes as $attribute ) :
                        if ( ! $attribute->get_visible() ) continue;
                        $attr_name   = wc_attribute_label( $attribute->get_name() );
                        $attr_values = array();
                        if ( $attribute->is_taxonomy() ) {
                            $attr_terms = wc_get_product_terms( $product_id, $attribute->get_name(), array( 'fields' => 'all' ) );
                            foreach ( $attr_terms as $attr_term ) {
                                $attr_values[] = esc_html( $attr_term->name );
                            }
                        } else {
                            foreach ( $attribute->get_options() as $opt ) {
                                $attr_values[] = esc_html( $opt );
                            }
                        }
                        echo '<tr>';
                        echo '<td class="p-3 font-semibold text-outline-variant w-1/3 border-b border-outline-variant/30">' . esc_html( $attr_name ) . '</td>';
                        echo '<td class="p-3 text-on-surface border-b border-outline-variant/30">' . wp_kses_post( implode( ', ', $attr_values ) ) . '</td>';
                        echo '</tr>';
                    endforeach;
                endif;
                $specs_tbody_html = ob_get_clean();
            ?>
                <div class="relative overflow-hidden rounded-lg" id="specs-container" style="max-height: 380px;">
                    <table class="w-full text-sm border-collapse zebra-table border border-outline-variant">
                        <tbody>
                            <?php echo $specs_tbody_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML pre-escaped via esc_html/wp_kses_post above ?>
                        </tbody>
                    </table>
                    <div class="absolute bottom-0 left-0 w-full h-24 bg-gradient-to-t from-white to-transparent pointer-events-none" id="specs-gradient"></div>
                </div>

                <button type="button" id="btn-show-specs" class="w-full mt-4 bg-primary/10 hover:bg-primary/20 text-primary py-2.5 rounded font-bold transition-colors flex items-center justify-center gap-1.5">
                    <span>Xem thông tin đầy đủ</span>
                    <span class="material-symbols-outlined text-[18px]">open_in_new</span>
                </button>

                <!-- Modal for Full Specs -->
                <div id="specs-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden opacity-0 transition-opacity duration-300">
                    <div class="absolute inset-0 bg-black/60 cursor-pointer" id="specs-modal-bg"></div>
                    <div class="relative bg-white w-11/12 md:w-3/4 max-w-2xl rounded-xl shadow-2xl flex flex-col z-10" style="max-height: 85vh;">
                        <div class="flex justify-between items-center p-4 border-b border-outline-variant shrink-0">
                            <h3 class="font-bold text-lg text-primary flex items-center gap-2">
                                <span class="material-symbols-outlined">settings_suggest</span>
                                Thông số kỹ thuật chi tiết
                            </h3>
                            <button type="button" id="specs-modal-close" class="text-outline hover:text-error transition-colors p-1 rounded-full hover:bg-error/10 flex items-center justify-center" aria-label="Đóng">
                                <span class="material-symbols-outlined !text-2xl">close</span>
                            </button>
                        </div>
                        <div class="p-4 overflow-y-auto">
                            <table class="w-full text-sm border-collapse zebra-table border border-outline-variant">
                                <tbody>
                                    <?php echo $specs_tbody_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php else : ?>
                <div class="p-6 bg-surface-container-low rounded-xl text-center text-outline text-sm">
                    <span class="material-symbols-outlined text-[32px] text-primary mb-1">info</span>
                    <p>Thông số kỹ thuật chi tiết đang được cập nhật cho sản phẩm này.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Detailed Description & Sidebar Section -->
    <div class="mt-12 flex flex-col lg:flex-row gap-8 items-start">
        
        <!-- Main Description Column (70%) -->
        <div class="lg:w-[70%] min-w-0 w-full">
            <div class="bg-white p-6 rounded-xl border border-outline-variant shadow-sm mb-8">
                <h2 class="text-2xl font-bold mb-6 border-l-4 border-primary pl-4 text-on-surface">
                    Đánh giá chi tiết <?php echo esc_html( $product->get_name() ); ?>
                </h2>
                
                <div class="prose max-w-none text-on-surface-variant line-clamp-fade" id="description-content">
                    <?php 
                    $content = get_the_content();
                    if ( ! empty( $content ) ) {
                        the_content();
                    } else {
                        echo '<p class="mb-4">Sản phẩm <strong>' . esc_html( $product->get_name() ) . '</strong> chính hãng Xiaomi sở hữu thiết kế hiện đại, hiệu năng vượt trội và độ bền cao, đem lại trải nghiệm sống tiện nghi cho mọi gia đình Việt.</p>';
                    }
                    ?>
                    
                    <!-- Sticky Buy Box inside Content -->
                    <div class="sticky-buy-box bg-surface-container-low border border-primary/20 p-4 rounded-xl my-8 flex flex-col sm:flex-row items-center justify-between gap-4 shadow-md">
                        <div class="flex-1 min-w-0">
                            <h4 class="font-bold text-on-surface line-clamp-1"><?php echo esc_html( $product->get_name() ); ?></h4>
                            <p class="text-secondary font-bold text-base">
                                <?php // S3-fix: escape nhánh fallback 'Liên hệ' theo WPCS
                                echo $display_price > 0 ? wc_price( $display_price ) : esc_html__( 'Liên hệ', 'omni-commerce' ); ?>
                            </p>
                        </div>
                        <div class="flex gap-2 w-full sm:w-auto shrink-0">
                            <input class="w-full sm:w-44 rounded-lg border-outline-variant bg-white py-2 px-3 text-sm outline-none focus:ring-1 focus:ring-primary" placeholder="SĐT mua nhanh" type="tel" id="quick-buy-input"/>
                            <button type="button" onclick="const p = document.getElementById('quick-buy-input').value; if(p){ document.getElementById('quick-buy-input').value=''; } else { document.querySelector('form.cart button[type=submit]')?.click(); }" class="bg-primary hover:bg-primary-container text-white px-5 py-2 rounded-lg font-bold text-sm whitespace-nowrap transition-colors shadow-sm">
                                MUA NGAY
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-6" id="read-more-wrapper">
                    <button type="button" class="inline-flex items-center gap-1 text-primary font-bold hover:underline transition-colors" onclick="document.getElementById('description-content').classList.remove('line-clamp-fade'); document.getElementById('read-more-wrapper').style.display='none';">
                        <span>Đọc tiếp nội dung</span>
                        <span class="material-symbols-outlined">expand_more</span>
                    </button>
                </div>
            </div>
            
            <!-- Real Customer Reviews & Rating Section -->
            <div id="reviews-section" class="bg-white p-6 rounded-xl border border-outline-variant shadow-sm">
                <h3 class="text-xl font-bold mb-6 text-on-surface">Đánh giá &amp; Nhận xét</h3>
                
                <!-- Add Review Form -->
                <div class="mb-8 p-5 bg-surface-container-low rounded-xl border border-outline-variant/40">
                    <p class="font-bold mb-2 text-sm text-on-surface">Gửi nhận xét của bạn</p>
                    <form action="<?php echo esc_url( site_url( '/wp-comments-post.php' ) ); ?>" method="post" id="commentform" class="space-y-3">
                        <div class="flex items-center gap-1 text-outline-variant" id="star-rating-selector">
                            <span class="text-xs text-outline mr-2 font-medium">Đánh giá sao:</span>
                            <?php for ( $s = 1; $s <= 5; $s++ ) : ?>
                                <button type="button" class="material-symbols-outlined text-[24px] star-btn hover:text-tertiary cursor-pointer transition-colors" data-star="<?php echo esc_attr( $s ); ?>" style="font-variation-settings: 'FILL' 1; color: #fbbc06;">star</button>
                            <?php endfor; ?>
                            <input type="hidden" name="rating" id="selected-rating" value="5" />
                        </div>
                        
                        <textarea class="w-full rounded-lg border border-outline-variant p-3 focus:ring-1 focus:ring-primary focus:border-primary h-24 text-sm bg-white outline-none" name="comment" required placeholder="Mời bạn để lại bình luận và cảm nhận thực tế về sản phẩm..."></textarea>
                        
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <input type="text" name="author" class="rounded-lg border border-outline-variant p-2.5 text-sm bg-white outline-none focus:ring-1 focus:ring-primary" placeholder="Họ và tên của bạn *" required />
                            <input type="email" name="email" class="rounded-lg border border-outline-variant p-2.5 text-sm bg-white outline-none focus:ring-1 focus:ring-primary" placeholder="Email của bạn *" required />
                        </div>
                        
                        <?php wp_comment_form_unfiltered_html_nonce(); ?>
                        <input type="hidden" name="comment_post_ID" value="<?php echo esc_attr( $product_id ); ?>" id="comment_post_ID" />
                        <input type="hidden" name="comment_parent" id="comment_parent" value="0" />
                        <?php do_action( 'comment_form', $product_id ); ?>
                        
                        <div class="flex justify-end pt-1">
                            <button type="submit" name="submit" class="bg-primary hover:bg-primary-container text-white px-8 py-2.5 rounded-lg font-bold text-sm transition-colors shadow-sm">
                                Gửi đánh giá
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Comments / Reviews List -->
                <div class="space-y-6">
                    <?php 
                    $comments = get_comments( array(
                        'post_id' => $product_id,
                        'status'  => 'approve',
                        'type'    => 'review', // Fix: WooCommerce uses 'review' type, not 'comment'
                    ) );

                    if ( ! empty( $comments ) ) :
                        foreach ( $comments as $comment ) :
                            $user_rating = (int) get_comment_meta( $comment->comment_ID, 'rating', true );
                            $initials = mb_substr( $comment->comment_author, 0, 2 );
                    ?>
                        <div class="flex gap-4 pb-4 border-b border-outline-variant/30 last:border-0 last:pb-0">
                            <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center text-primary font-bold uppercase shrink-0 text-sm">
                                <?php echo esc_html( $initials ?: 'KH' ); ?>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1 flex-wrap">
                                    <span class="font-bold text-sm text-on-surface"><?php echo esc_html( $comment->comment_author ); ?></span>
                                    <?php if ( $user_rating > 0 ) : ?>
                                        <div class="flex text-tertiary">
                                            <?php for ( $k = 1; $k <= 5; $k++ ) : 
                                                $fill = ( $k <= $user_rating ) ? 1 : 0; // P2-fix: int thay string, tránh untyped output
                                            ?>
                                                <span class="material-symbols-outlined !text-xs" style="font-variation-settings: 'FILL' <?php echo (int) $fill; ?>;">star</span>
                                            <?php endfor; ?>
                                        </div>
                                    <?php endif; ?>
                                    <span class="text-xs text-outline"><?php echo esc_html( human_time_diff( strtotime( $comment->comment_date ), current_time( 'timestamp' ) ) . ' trước' ); ?></span>
                                </div>
                                <p class="text-sm text-on-surface-variant leading-relaxed"><?php echo nl2br( esc_html( $comment->comment_content ) ); ?></p>
                            </div>
                        </div>
                    <?php 
                        endforeach;
                    else :
                    ?>
                        <!-- L3-fix: Empty state sạch thay vì mock reviews hardcode (tên người, nội dung cứng) -->
                        <div class="flex flex-col items-center py-10 text-center">
                            <span class="material-symbols-outlined text-[48px] text-outline-variant mb-3">rate_review</span>
                            <p class="font-semibold text-gray-600 text-sm">Chưa có đánh giá nào</p>
                            <p class="text-xs text-outline mt-1">Hãy là người đầu tiên chia sẻ cảm nhận về sản phẩm này!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar Column (30%) -->
        <div class="lg:w-[30%] min-w-0 w-full space-y-8">
            <!-- Related Products -->
            <?php
            $related_products = wc_get_related_products( $product_id, 3 );
            if ( ! empty( $related_products ) ) :
            ?>
            <div class="bg-white p-5 rounded-xl border border-outline-variant shadow-sm">
                <h3 class="text-base font-bold mb-4 uppercase text-primary border-b-2 border-primary inline-block pb-1">
                    Sản phẩm liên quan
                </h3>
                <div class="space-y-4">
                <?php
                    foreach ( $related_products as $rel_id ) :
                        $rel_prod = wc_get_product( $rel_id );
                        if ( ! $rel_prod ) continue;
                ?>
                    <a href="<?php echo esc_url( $rel_prod->get_permalink() ); ?>" class="flex gap-3 items-center group cursor-pointer block">
                        <div class="w-16 h-16 flex-shrink-0 bg-surface-container-low rounded-lg border border-outline-variant overflow-hidden p-1 flex items-center justify-center">
                            <?php 
                            if ( $rel_prod->get_image_id() ) {
                                echo wp_get_attachment_image( $rel_prod->get_image_id(), 'thumbnail', false, array( 'class' => 'w-full h-full object-contain group-hover:scale-105 transition-transform' ) );
                            } else {
                                echo '<img class="w-full h-full object-contain" src="' . esc_url( wc_placeholder_img_src( 'thumbnail' ) ) . '" alt="Placeholder"/>';
                            }
                            ?>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h4 class="text-xs md:text-sm font-semibold line-clamp-2 group-hover:text-primary transition-colors text-on-surface">
                                <?php echo esc_html( $rel_prod->get_name() ); ?>
                            </h4>
                            <p class="text-secondary font-bold text-sm mt-0.5">
                                <?php echo $rel_prod->get_price_html(); ?>
                            </p>
                        </div>
                    </a>
                <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Similar Category Products -->
            <?php
            $current_cats = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );
            $similar_args = array(
                'post_type'      => 'product',
                'posts_per_page' => 2,
                'post__not_in'   => array( $product_id ),
                'tax_query'      => array(
                    array(
                        'taxonomy' => 'product_cat',
                        'field'    => 'id',
                        'terms'    => $current_cats,
                    ),
                ),
            );
            $similar_query = new WP_Query( $similar_args );
            if ( $similar_query->have_posts() ) :
            ?>
            <div class="bg-white p-5 rounded-xl border border-outline-variant shadow-sm">
                <h3 class="text-base font-bold mb-4 uppercase text-primary border-b-2 border-primary inline-block pb-1">
                    Sản phẩm cùng loại
                </h3>
                <div class="space-y-4">
                <?php
                    while ( $similar_query->have_posts() ) : $similar_query->the_post();
                        $sim_prod = wc_get_product( get_the_ID() );
                        if ( ! $sim_prod ) continue;
                ?>
                    <a href="<?php the_permalink(); ?>" class="flex gap-3 items-center group cursor-pointer block">
                        <div class="w-16 h-16 flex-shrink-0 bg-surface-container-low rounded-lg border border-outline-variant overflow-hidden p-1 flex items-center justify-center">
                            <?php 
                            if ( $sim_prod->get_image_id() ) {
                                echo wp_get_attachment_image( $sim_prod->get_image_id(), 'thumbnail', false, array( 'class' => 'w-full h-full object-contain group-hover:scale-105 transition-transform' ) );
                            } else {
                                echo '<img class="w-full h-full object-contain" src="' . esc_url( wc_placeholder_img_src( 'thumbnail' ) ) . '" alt="Placeholder"/>';
                            }
                            ?>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h4 class="text-xs md:text-sm font-semibold line-clamp-2 group-hover:text-primary transition-colors text-on-surface">
                                <?php echo esc_html( get_the_title() ); // S4-fix: the_title() không tự escape — dùng esc_html(get_the_title()) ?>
                            </h4>
                            <p class="text-secondary font-bold text-sm mt-0.5">
                                <?php echo $sim_prod->get_price_html(); ?>
                            </p>
                        </div>
                    </a>
                <?php 
                    endwhile;
                    wp_reset_postdata();
                ?>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
    
    <?php do_action( 'woocommerce_after_single_product' ); ?>
    
</main>

<!-- Fixed Floating Action Bar for Mobile (Matches product_detail.html) -->
<div class="fixed bottom-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-sm border-t border-outline-variant shadow-[0_-4px_10px_rgba(0,0,0,0.1)] lg:hidden">
    <div class="flex justify-between items-center px-2 py-1.5 max-w-md mx-auto">
        <a class="flex flex-col items-center flex-1 py-1 text-secondary hover:opacity-80 transition-opacity" href="tel:<?php echo esc_attr( $hotline_clean ); ?>">
            <span class="material-symbols-outlined text-[20px]">call</span>
            <span class="text-[10px] font-bold">Hotline</span>
        </a>
        <a class="flex flex-col items-center flex-1 py-1 text-primary hover:opacity-80 transition-opacity" href="<?php echo esc_url( $fab_messenger ); ?>" target="_blank" rel="noopener noreferrer">
            <span class="material-symbols-outlined text-[20px]">chat</span>
            <span class="text-[10px] font-bold">Chat</span>
        </a>
        <a class="flex flex-col items-center flex-1 py-1 text-[#0068ff] hover:opacity-80 transition-opacity" href="<?php echo esc_url( $fab_zalo ); ?>" target="_blank" rel="noopener noreferrer">
            <span class="material-symbols-outlined text-[20px]">contact_support</span>
            <span class="text-[10px] font-bold">Zalo</span>
        </a>
        <a class="flex flex-col items-center flex-1 py-1 text-outline hover:text-primary transition-colors" href="<?php echo esc_url( home_url( '/lien-he/' ) ); ?>">
            <span class="material-symbols-outlined text-[20px]">storefront</span>
            <span class="text-[10px] font-bold">Showroom</span>
        </a>
        <div class="flex-[2] px-2">
            <button type="button" onclick="const f = document.querySelector('form.cart button[type=submit]'); if(f){ f.click(); } else { window.location.href='<?php echo esc_url( wc_get_checkout_url() ); ?>'; }" class="w-full bg-secondary hover:bg-secondary-fixed-variant text-white py-2 rounded-lg font-bold text-xs uppercase shadow-md active:scale-95 transition-all flex items-center justify-center gap-1">
                <span class="material-symbols-outlined !text-sm">shopping_cart</span>
                <span>Mua Ngay</span>
            </button>
        </div>
    </div>
</div>

<!-- Modal Mua Ngay -->
<div id="mi-buy-now-modal" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/50 backdrop-blur-sm transition-opacity opacity-0">
    <div class="bg-white rounded-2xl shadow-2xl w-[90%] max-w-md p-6 relative transform scale-95 transition-transform duration-300">
        <!-- Close Button -->
        <button type="button" class="absolute top-3 right-3 text-red-500 hover:text-red-700 p-1" onclick="document.getElementById('mi-buy-now-modal').classList.remove('flex', 'opacity-100'); document.getElementById('mi-buy-now-modal').classList.add('hidden', 'opacity-0'); document.querySelector('#mi-buy-now-modal > div').classList.replace('scale-100', 'scale-95');">
            <span class="material-symbols-outlined text-3xl font-bold">close</span>
        </button>
        
        <!-- Logo -->
        <div class="flex justify-center mb-4">
            <div class="text-primary font-bold text-3xl flex items-center gap-2">
                <span class="material-symbols-outlined text-4xl">shopping_cart_checkout</span>
                <!-- H8: Tên thương hiệu đọc từ WP, không hardcode -->
                <span><?php echo esc_html( get_bloginfo('name') ); ?></span>
            </div>
        </div>
        
        <!-- Text -->
        <p class="text-center text-primary text-lg font-medium mb-6">Cảm ơn bạn đã đặt mua ngay. Vui lòng chọn:</p>
        
        <!-- Buttons -->
        <div class="flex gap-4 justify-center">
            <button type="button" class="flex-1 py-2.5 px-4 rounded-lg border border-primary text-primary font-bold text-center hover:bg-primary/5 transition-colors" onclick="document.getElementById('mi-buy-now-modal').classList.remove('flex', 'opacity-100'); document.getElementById('mi-buy-now-modal').classList.add('hidden', 'opacity-0'); document.querySelector('#mi-buy-now-modal > div').classList.replace('scale-100', 'scale-95'); const related = document.querySelector('.related.products'); if(related) { related.scrollIntoView({behavior: 'smooth', block: 'start'}); }">Tiếp tục mua hàng</button>
            <a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="flex-1 py-2.5 px-4 rounded-lg bg-secondary text-white font-bold text-center hover:bg-secondary-fixed-variant transition-colors">Thanh toán ngay</a>
        </div>
    </div>
</div>

<!-- Modal Trả Góp -->
<div id="mi-installment-modal" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/50 backdrop-blur-sm transition-opacity opacity-0">
    <div class="bg-white rounded-2xl shadow-2xl w-[95%] md:w-[600px] max-h-[90vh] overflow-y-auto p-5 lg:p-8 relative transform scale-95 transition-transform duration-300">
        <!-- Close Button -->
        <button type="button" class="absolute top-3 right-3 text-red-500 hover:text-red-700 p-1 bg-gray-100 rounded-full w-8 h-8 flex items-center justify-center transition-colors" onclick="document.getElementById('mi-installment-modal').classList.remove('flex', 'opacity-100'); document.getElementById('mi-installment-modal').classList.add('hidden', 'opacity-0'); document.querySelector('#mi-installment-modal > div').classList.replace('scale-100', 'scale-95');">
            <span class="material-symbols-outlined font-bold text-[20px]">close</span>
        </button>
        
        <div class="flex items-center gap-3 mb-6 border-b border-outline-variant pb-4">
            <span class="material-symbols-outlined text-4xl text-primary">credit_card</span>
            <div>
                <h3 class="text-xl lg:text-2xl font-bold text-primary">Đăng ký mua trả góp</h3>
                <p class="text-sm text-outline">Vui lòng điền thông tin, nhân viên sẽ gọi lại tư vấn trong 5 phút.</p>
            </div>
        </div>

        <form id="mi-installment-form" onsubmit="event.preventDefault(); mi_submit_installment();" class="space-y-4">
            <input type="hidden" id="installment-product-id" value="<?php echo esc_attr( $product->get_id() ); ?>">
            <input type="hidden" id="installment-product-name" value="<?php echo esc_attr( $product->get_name() ); ?>">
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="md:col-span-1">
                    <select id="installment-gender" class="w-full border border-outline-variant bg-white rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm p-3 outline-none">
                        <option value="Anh">Anh</option>
                        <option value="Chị">Chị</option>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <input type="text" id="installment-name" required placeholder="Họ và tên bạn (bắt buộc)" class="w-full border border-outline-variant bg-white rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm p-3 outline-none">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <input type="tel" id="installment-phone" required pattern="[0-9]{9,11}" placeholder="Số điện thoại (bắt buộc)" class="w-full border border-outline-variant bg-white rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm p-3 outline-none">
                </div>
                <div>
                    <input type="email" id="installment-email" placeholder="Email (không bắt buộc)" class="w-full border border-outline-variant bg-white rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm p-3 outline-none">
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <select id="installment-city" required class="w-full border border-outline-variant bg-white rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm p-3 outline-none text-outline">
                        <option value="">Chọn Tỉnh/Thành phố *</option>
                        <option value="Hà Nội">Hà Nội</option>
                        <option value="Hồ Chí Minh">Hồ Chí Minh</option>
                        <option value="Đà Nẵng">Đà Nẵng</option>
                        <option value="Tỉnh khác">Tỉnh/Thành khác</option>
                    </select>
                </div>
                <div>
                    <select id="installment-doc" required class="w-full border border-outline-variant bg-white rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm p-3 outline-none text-outline">
                        <option value="">Giấy tờ bạn có *</option>
                        <option value="CMND/CCCD">Chỉ có CMND / CCCD</option>
                        <option value="CMND + Hộ khẩu">CMND / CCCD + Hộ khẩu</option>
                        <option value="CMND + Bằng lái">CMND / CCCD + Bằng lái xe</option>
                    </select>
                </div>
            </div>

            <div>
                <textarea id="installment-note" rows="3" placeholder="Ghi chú thêm (nếu có)" class="w-full border border-outline-variant bg-white rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm p-3 outline-none"></textarea>
            </div>

            <div class="pt-2">
                <button type="submit" id="installment-submit-btn" class="w-full bg-primary hover:bg-primary-container text-white py-3.5 rounded-xl font-bold text-[15px] transition-colors shadow-md flex items-center justify-center gap-2">
                    <span>ĐẶT MUA TRẢ GÓP</span>
                    <span class="material-symbols-outlined text-[18px]">arrow_right_alt</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function mi_submit_installment() {
    const btn = document.getElementById('installment-submit-btn');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<span class="material-symbols-outlined animate-spin">refresh</span> Đang gửi...';
    btn.disabled = true;
    
    const data = new FormData();
    data.append('action', 'mi_submit_installment');
    data.append('product_id', document.getElementById('installment-product-id').value);
    data.append('product_name', document.getElementById('installment-product-name').value);
    data.append('gender', document.getElementById('installment-gender').value);
    data.append('name', document.getElementById('installment-name').value);
    data.append('phone', document.getElementById('installment-phone').value);
    data.append('email', document.getElementById('installment-email').value);
    data.append('city', document.getElementById('installment-city').value);
    data.append('doc', document.getElementById('installment-doc').value);
    data.append('note', document.getElementById('installment-note').value);
    data.append('nonce', typeof mi_ajax_obj !== 'undefined' ? mi_ajax_obj.nonce : '');
    
    fetch((typeof wc_add_to_cart_params !== 'undefined' ? wc_add_to_cart_params.ajax_url : '/wp-admin/admin-ajax.php'), {
        method: 'POST',
        body: data
    }).then(res => res.json())
      .then(response => {
          btn.innerHTML = originalText;
          btn.disabled = false;
          if(response.success) {
              alert('Cảm ơn bạn! Đăng ký mua trả góp thành công. Nhân viên sẽ liên hệ với bạn trong vòng 5 phút.');
              document.getElementById('mi-installment-modal').classList.remove('flex', 'opacity-100'); 
              document.getElementById('mi-installment-modal').classList.add('hidden', 'opacity-0');
              document.getElementById('mi-installment-form').reset();
          } else {
              alert(response.data || 'Có lỗi xảy ra, vui lòng thử lại!');
          }
      }).catch(err => {
          btn.innerHTML = originalText;
          btn.disabled = false;
          alert('Lỗi kết nối, vui lòng gọi Hotline.');
      });
}
</script>

<!-- Interactive Scripts for Single Product Page -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Gallery Image Switcher & Carousel Controls & Zoom
    const mainImgContainer = document.getElementById('main-image-container');
    const mainImg = document.getElementById('main-product-image');
    const thumbs = document.querySelectorAll('.gallery-thumb');
    const prevBtn = document.getElementById('gallery-prev');
    const nextBtn = document.getElementById('gallery-next');
    let currentIndex = 0;

    function setMainImage(index) {
        if (!thumbs.length || !mainImg) return;
        if (index < 0) index = thumbs.length - 1;
        if (index >= thumbs.length) index = 0;
        currentIndex = index;

        const targetThumb = thumbs[currentIndex];
        const fullSrc = targetThumb.getAttribute('data-full');
        if (fullSrc) {
            mainImg.src = fullSrc;
        }

        thumbs.forEach((t, i) => {
            if (i === currentIndex) {
                t.classList.remove('border', 'border-outline-variant', 'opacity-75');
                t.classList.add('border-2', 'border-secondary', 'opacity-100');
            } else {
                t.classList.remove('border-2', 'border-secondary', 'opacity-100');
                t.classList.add('border', 'border-outline-variant', 'opacity-75');
            }
        });
    }

    // Hover Zoom Effect (Tiki/Shopee style Adjacent Popup)
    const zoomResult = document.createElement('div');
    zoomResult.className = 'fixed border border-outline-variant shadow-2xl bg-white hidden z-[9999] rounded-xl overflow-hidden';
    zoomResult.style.width = '500px';
    zoomResult.style.height = '500px';
    zoomResult.style.backgroundRepeat = 'no-repeat';
    document.body.appendChild(zoomResult);

    if (mainImgContainer && mainImg) {
        // Hủy bỏ transform cũ trên ảnh gốc
        mainImg.style.transform = '';
        mainImg.classList.remove('transition-transform', 'duration-200');
        
        mainImgContainer.addEventListener('mousemove', function(e) {
            // Tắt kính lúp khi hover vào nút bấm, hoặc trên màn hình nhỏ
            if (e.target.closest('button') || window.innerWidth < 1024) {
                zoomResult.classList.add('hidden');
                return;
            }

            zoomResult.classList.remove('hidden');
            zoomResult.style.backgroundImage = `url('${mainImg.src}')`;
            
            const rectContainer = mainImgContainer.getBoundingClientRect();
            // Đặt popup zoom bên phải của khung ảnh chính
            zoomResult.style.left = `${rectContainer.right + 20}px`;
            zoomResult.style.top = `${rectContainer.top}px`;

            const rect = mainImg.getBoundingClientRect();
            const imgAspect = mainImg.naturalWidth / mainImg.naturalHeight;
            const rectAspect = rect.width / rect.height;
            
            let renderWidth, renderHeight;
            if (imgAspect > rectAspect) {
                renderWidth = rect.width;
                renderHeight = rect.width / imgAspect;
            } else {
                renderHeight = rect.height;
                renderWidth = rect.height * imgAspect;
            }

            const offsetX = (rect.width - renderWidth) / 2;
            const offsetY = (rect.height - renderHeight) / 2;

            // Tọa độ chuột so với vùng ảnh thực tế
            const x = e.clientX - rect.left - offsetX;
            const y = e.clientY - rect.top - offsetY;
            
            // Nếu chuột ra ngoài vùng ảnh thực, ẩn popup
            if (x < 0 || x > renderWidth || y < 0 || y > renderHeight) {
                zoomResult.classList.add('hidden');
                return;
            }

            // Tỷ lệ phần trăm chuột trên ảnh (0 đến 1)
            const percentX = x / renderWidth;
            const percentY = y / renderHeight;

            // Tính toán tỷ lệ Zoom tối thiểu để lấp đầy box 500x500
            const minZoomX = 500 / renderWidth;
            const minZoomY = 500 / renderHeight;
            const zoom = Math.max(2.5, minZoomX, minZoomY); // Zoom tối thiểu 2.5x

            const bgWidth = renderWidth * zoom;
            const bgHeight = renderHeight * zoom;
            zoomResult.style.backgroundSize = `${bgWidth}px ${bgHeight}px`;

            // Di chuyển Background tỷ lệ thuận với vị trí chuột
            // Khi percentX = 0 -> bgX = 0. Khi percentX = 1 -> bgX = 500 - bgWidth
            const bgX = percentX * (500 - bgWidth);
            const bgY = percentY * (500 - bgHeight);

            zoomResult.style.backgroundPosition = `${bgX}px ${bgY}px`;
        });
        
        mainImgContainer.addEventListener('mouseleave', function() {
            zoomResult.classList.add('hidden');
        });
    }

    thumbs.forEach((thumb, idx) => {
        thumb.addEventListener('click', () => setMainImage(idx));
    });

    if (prevBtn) {
        prevBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            setMainImage(currentIndex - 1);
        });
    }
    if (nextBtn) {
        nextBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            setMainImage(currentIndex + 1);
        });
    }

    // 2. Technical Specs Modal Controls
    const container = document.getElementById('specs-container');
    const gradient = document.getElementById('specs-gradient');
    const btnSpecs = document.getElementById('btn-show-specs');
    const modal = document.getElementById('specs-modal');
    const modalBg = document.getElementById('specs-modal-bg');
    const modalClose = document.getElementById('specs-modal-close');
    
    if (btnSpecs && container && modal) {
        if (container.scrollHeight <= 380) {
            btnSpecs.style.display = 'none';
            if (gradient) gradient.style.display = 'none';
        }
        
        const openModal = () => {
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                modal.classList.add('opacity-100');
            }, 10);
            document.body.style.overflow = 'hidden';
        };
        
        const closeModal = () => {
            modal.classList.remove('opacity-100');
            modal.classList.add('opacity-0');
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
            document.body.style.overflow = '';
        };

        btnSpecs.addEventListener('click', openModal);
        if (modalBg) modalBg.addEventListener('click', closeModal);
        if (modalClose) modalClose.addEventListener('click', closeModal);
        
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                closeModal();
            }
        });
    }

    // 3. Review Form Star Selector
    const starBtns = document.querySelectorAll('.star-btn');
    const ratingInput = document.getElementById('selected-rating');
    if (starBtns.length && ratingInput) {
        starBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const val = parseInt(this.getAttribute('data-star'), 10);
                ratingInput.value = val;
                starBtns.forEach(b => {
                    const bVal = parseInt(b.getAttribute('data-star'), 10);
                    if (bVal <= val) {
                        b.style.color = '#fbbc06';
                    } else {
                        b.style.color = '#bdc8cc';
                    }
                });
            });
        });
    }

    // 4. Warranty Sync & Variable Form Injector
    const warrantySelect = document.getElementById('warranty-package-select');
    if (warrantySelect) {
        warrantySelect.addEventListener('change', function() {
            const hidden = document.getElementById('mi_warranty_hidden');
            if (hidden) {
                hidden.value = this.value;
            }
        });
        
        setTimeout(() => {
            let hidden = document.getElementById('mi_warranty_hidden');
            if (!hidden) {
                const cartForm = document.querySelector('form.cart');
                if (cartForm) {
                    hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.id = 'mi_warranty_hidden';
                    hidden.name = 'mi_warranty_package';
                    cartForm.appendChild(hidden);
                }
            }
            if (hidden) {
                hidden.value = warrantySelect.value;
            }
        }, 500);
    }

    // 5. Variable Product Mapping JS
    const versionCards = document.querySelectorAll('.version-card[data-variation-id]');
    if (versionCards.length > 0) {
        versionCards.forEach(card => {
            card.addEventListener('click', function() {
                // Update UI selection classes
                versionCards.forEach(c => {
                    c.classList.remove('border-secondary', 'ring-1', 'ring-secondary/30', 'cursor-default');
                    c.classList.add('border-gray-300', 'hover:border-secondary', 'hover:shadow-md', 'cursor-pointer');
                    c.querySelector('.name').classList.remove('text-gray-900');
                    c.querySelector('.name').classList.add('text-gray-800', 'group-hover:text-secondary');
                });
                this.classList.remove('border-gray-300', 'hover:border-secondary', 'hover:shadow-md', 'cursor-pointer');
                this.classList.add('border-secondary', 'ring-1', 'ring-secondary/30', 'cursor-default');
                this.querySelector('.name').classList.remove('text-gray-800', 'group-hover:text-secondary');
                this.querySelector('.name').classList.add('text-gray-900');

                // Map attributes to Woo form
                const attrs = JSON.parse(this.getAttribute('data-attributes'));
                const form = document.querySelector('form.variations_form');
                if (form && attrs) {
                    for (const [key, val] of Object.entries(attrs)) {
                        const select = form.querySelector(`select[name="${key}"]`);
                        if (select) {
                            select.value = val;
                            select.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    }
                }
            });
        });

        // Listen for WooCommerce variation changes
        if (typeof jQuery !== 'undefined') {
            jQuery('form.variations_form').on('found_variation', function(event, variation) {
                if (variation.display_price) {
                    const priceEl = document.getElementById('main-display-price');
                    if (priceEl) {
                        priceEl.innerHTML = new Intl.NumberFormat('vi-VN').format(variation.display_price) + ' <span class="underline text-2xl font-bold">đ</span>';
                    }
                    const regEl = document.getElementById('main-regular-price');
                    const badgeEl = document.getElementById('main-sale-badge');
                    
                    if (variation.display_price < variation.display_regular_price) {
                        if (regEl) {
                            regEl.innerHTML = new Intl.NumberFormat('vi-VN').format(variation.display_regular_price) + ' đ';
                            regEl.classList.remove('hidden');
                        }
                        if (badgeEl) {
                            const percent = Math.round(((variation.display_regular_price - variation.display_price) / variation.display_regular_price) * 100);
                            badgeEl.innerHTML = `Giảm ${percent}%`;
                            badgeEl.classList.remove('hidden');
                        }
                    } else {
                        if (regEl) regEl.classList.add('hidden');
                        if (badgeEl) badgeEl.classList.add('hidden');
                    }
                }
                if (variation.image && variation.image.src) {
                    const mainImg = document.getElementById('main-product-image');
                    if (mainImg) mainImg.src = variation.image.src;
                }
            });
        }

        // Auto click first valid card after a short delay
        setTimeout(() => {
            if (versionCards[0]) versionCards[0].click();
        }, 400);
    }

    // 6. Luồng Add to Cart & Buy Now nâng cao (Ajax + Redirect)
    const btnBuyNow = document.querySelectorAll('.btn-buy-now-custom');
    const btnAddToCart = document.querySelectorAll('.btn-add-to-cart-custom');
    
    function showToast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `fixed bottom-5 right-5 px-4 py-3 rounded-lg shadow-xl text-white font-medium text-sm flex items-center gap-2 z-[9999] transition-all duration-300 transform translate-y-10 opacity-0 ${type === 'success' ? 'bg-green-600' : 'bg-red-600'}`;
        toast.innerHTML = `<span class="material-symbols-outlined">${type === 'success' ? 'check_circle' : 'error'}</span> ${message}`;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.remove('translate-y-10', 'opacity-0');
        }, 10);

        setTimeout(() => {
            toast.classList.add('translate-y-10', 'opacity-0');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }

    function handleCartAction(btn, isBuyNow) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            let form = document.querySelector('form.cart');
            if (!form) return;

            // Validation cho Variable product
            if (form.classList.contains('variations_form')) {
                const variationId = form.querySelector('input[name="variation_id"]');
                if (!variationId || variationId.value === '' || variationId.value === '0') {
                    // Highlight lỗi mượt mà
                    const versionGrid = document.getElementById('product-versions-grid');
                    if (versionGrid) {
                        versionGrid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        versionGrid.classList.add('animate-shake', 'ring-2', 'ring-red-500', 'rounded-md');
                        setTimeout(() => versionGrid.classList.remove('animate-shake', 'ring-2', 'ring-red-500', 'rounded-md'), 800);
                        showToast('Vui lòng chọn phiên bản sản phẩm!', 'error');
                        return;
                    }
                }
            }

            // AJAX Thêm vào giỏ cho cả 2 nút
            // D1: Đã xóa block is_buy_now_flag – flag này không được đọc phía server nên là dead code.
            const formData = new FormData(form);
            const addToCartVal = form.querySelector('[name="add-to-cart"]');
            if (addToCartVal) formData.append('add-to-cart', addToCartVal.value);
            
            const originalText = this.innerHTML;
            this.innerHTML = '<span class="material-symbols-outlined animate-spin block mx-auto">progress_activity</span>';
            this.disabled = true;

            fetch(window.location.href, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.text();
            })
            .then(html => {
                // Parse HTML trả về để lấy số lượng giỏ hàng mới nhất
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newCartCount = doc.querySelector('.cart-contents-count');
                const currentCartCount = document.querySelector('.cart-contents-count');
                
                if (newCartCount && currentCartCount) {
                    currentCartCount.innerHTML = newCartCount.innerHTML;
                    // Kích hoạt hiệu ứng nhẹ để báo hiệu
                    currentCartCount.classList.add('scale-125');
                    setTimeout(() => currentCartCount.classList.remove('scale-125'), 300);
                }

                if (isBuyNow) {
                    // Hiện Modal
                    const modal = document.getElementById('mi-buy-now-modal');
                    if (modal) {
                        modal.classList.remove('hidden', 'opacity-0');
                        modal.classList.add('flex', 'opacity-100');
                        modal.querySelector('div').classList.replace('scale-95', 'scale-100');
                    }
                } else {
                    showToast('Đã thêm sản phẩm vào giỏ hàng!', 'success');
                }
                
                // Vẫn giữ trigger để đảm bảo tương thích với các plugin khác nếu cần
                if (typeof jQuery !== 'undefined') {
                    if (typeof sessionStorage !== 'undefined') {
                        sessionStorage.removeItem('wc_fragments');
                        sessionStorage.removeItem('wc_cart_hash');
                    }
                    jQuery(document.body).trigger('wc_fragment_refresh');
                }
            })
            .catch(() => { // P3-fix: err không được dùng, bỏ tham số tránh lint no-unused-vars
                showToast('Có lỗi xảy ra, vui lòng thử lại.', 'error');
            })
            .finally(() => {
                this.innerHTML = originalText;
                this.disabled = false;
            });
        });
    }

    btnBuyNow.forEach(btn => handleCartAction(btn, true));
    btnAddToCart.forEach(btn => handleCartAction(btn, false));
});
</script>

<?php 
endwhile; // end of the loop.

get_footer(); 
?>
