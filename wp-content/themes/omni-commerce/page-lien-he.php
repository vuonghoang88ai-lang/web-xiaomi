<?php
if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly.

/**
 * Template Name: Trang Liên Hệ
 */

$submit_status = '';
$submit_message = '';

// Xử lý Form Submit
if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['mi_contact_submit'] ) ) {
    
    // Validate Nonce (Bảo mật form)
    if ( ! isset( $_POST['mi_contact_nonce'] ) || ! wp_verify_nonce( $_POST['mi_contact_nonce'], 'mi_contact_action' ) ) {
        $submit_status = 'error';
        $submit_message = 'Xác thực bảo mật thất bại. Vui lòng thử lại!';
    } else {
        // Sanitize data
        $full_name = isset($_POST['full_name']) ? sanitize_text_field($_POST['full_name']) : '';
        $phone     = isset($_POST['phone']) ? sanitize_text_field($_POST['phone']) : '';
        $email     = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $message   = isset($_POST['message']) ? sanitize_textarea_field($_POST['message']) : '';

        // Validate
        if ( empty($full_name) || empty($phone) || empty($message) ) {
            $submit_status = 'error';
            $submit_message = 'Vui lòng điền đầy đủ các thông tin bắt buộc (*).';
        } elseif ( ! empty($email) && ! is_email($email) ) {
            $submit_status = 'error';
            $submit_message = 'Địa chỉ Email không hợp lệ.';
        } else {
            // Chuẩn bị gửi email
            $to = get_option('admin_email');
            $subject = 'Liên hệ mới từ ' . $full_name . ' - ' . get_bloginfo('name');
            
            $body = "Bạn vừa nhận được một liên hệ mới từ website:\n\n";
            $body .= "Họ và tên: " . $full_name . "\n";
            $body .= "Số điện thoại: " . $phone . "\n";
            if ( ! empty($email) ) {
                $body .= "Email: " . $email . "\n";
            }
            $body .= "Nội dung lời nhắn:\n" . $message . "\n";
            
            $headers = array('Content-Type: text/plain; charset=UTF-8');
            if ( ! empty($email) ) {
                $headers[] = 'Reply-To: ' . $full_name . ' <' . $email . '>';
            }

            // Gửi email
            $sent = wp_mail( $to, $subject, $body, $headers );

            if ( $sent ) {
                $submit_status = 'success';
                $submit_message = 'Cảm ơn bạn! Lời nhắn của bạn đã được gửi thành công. Chúng tôi sẽ liên hệ lại trong thời gian sớm nhất.';
            } else {
                $submit_status = 'error';
                $submit_message = 'Có lỗi xảy ra trong quá trình gửi mail. Vui lòng gọi trực tiếp qua Hotline.';
            }
        }
    }
}

get_header(); 

// Lấy thông tin công ty từ cấu hình Theme (ACF)
$address = function_exists('get_field') ? get_field('mi_hf_topbar_address', 'option') : '';
$address = $address ?: 'Số 41 Khuất Duy Tiến, Thanh Xuân, Hà Nội';

$hotline = function_exists('get_field') ? get_field('mi_hotline_number', 'option') : '';
$hotline = $hotline ?: '0822.83.4444';

$zalo_url = function_exists('get_field') ? get_field('mi_hf_fab_zalo', 'option') : '#';
$messenger_url = function_exists('get_field') ? get_field('mi_hf_fab_messenger', 'option') : '#';
$contact_intro = 'Nếu bạn có bất kỳ thắc mắc nào về sản phẩm, dịch vụ hoặc cần hỗ trợ bảo hành, đừng ngần ngại liên hệ với Xiaomi Store. Đội ngũ của chúng tôi luôn sẵn sàng hỗ trợ bạn 24/7.';
?>

<main id="primary" class="max-w-[1440px] mx-auto px-4 py-6 lg:py-12">
    
    <!-- Tiêu đề trang -->
    <div class="mb-8 lg:mb-10 text-center">
        <h1 class="text-2xl md:text-4xl font-black text-primary uppercase tracking-tight mb-3">
            <?php echo esc_html( get_the_title() ) ?: 'Liên Hệ Với Chúng Tôi'; ?>
        </h1>
        <div class="text-gray-600 max-w-2xl mx-auto text-base leading-relaxed">
            <?php 
            if ( have_posts() ) {
                while ( have_posts() ) {
                    the_post();
                    $content = get_the_content();
                    if ( empty( trim( $content ) ) ) {
                        echo esc_html( $contact_intro );
                    } else {
                        the_content(); // In ra nội dung admin soạn trong wp-admin
                    }
                }
            } else {
                echo esc_html( $contact_intro );
            }
            ?>
        </div>
    </div>

    <!-- Bố cục 2 cột -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
        
        <!-- Cột Trái: Thông tin & Bản đồ -->
        <div class="flex flex-col gap-6 lg:gap-8">
            
            <!-- Box Thông tin -->
            <div class="bg-white p-5 lg:p-6 rounded-2xl border border-slate-200 shadow-[0_12px_28px_-20px_rgba(15,23,42,0.28)]">
                <h2 class="text-xl font-bold text-slate-800 mb-6 border-b border-slate-200 pb-3">Thông Tin Liên Hệ</h2>
                
                <div class="flex flex-col gap-5">
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center text-primary shrink-0">
                            <span class="material-symbols-outlined">location_on</span>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800 mb-1">Địa chỉ Showroom</h3>
                            <p class="text-gray-600 text-sm"><?php echo esc_html($address); ?></p>
                        </div>
                    </div>
                    
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center text-primary shrink-0">
                            <span class="material-symbols-outlined">call</span>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800 mb-1">Hotline Tư Vấn (Miễn phí)</h3>
                            <a href="tel:<?php echo esc_attr(str_replace([' ', '.'], '', $hotline)); ?>" class="text-primary font-bold text-lg hover:underline"><?php echo esc_html($hotline); ?></a>
                        </div>
                    </div>
                    
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 rounded-full bg-primary/10 flex items-center justify-center text-primary shrink-0">
                            <span class="material-symbols-outlined">mail</span>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-800 mb-1">Email Hỗ Trợ</h3>
                            <a href="mailto:<?php echo esc_attr(get_option('admin_email')); ?>" class="text-gray-600 text-sm hover:text-primary transition-colors"><?php echo esc_html(get_option('admin_email')); ?></a>
                        </div>
                    </div>
                </div>

                <!-- Nút MXH -->
                <div class="mt-8 pt-6 border-t border-gray-200 flex flex-col sm:flex-row gap-3 sm:gap-4">
                    <a href="<?php echo esc_url($zalo_url); ?>" target="_blank" rel="noopener noreferrer" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white py-3 sm:py-2.5 rounded-lg text-center font-bold transition-colors text-sm flex items-center justify-center gap-2">
                        Chat qua Zalo
                    </a>
                    <a href="<?php echo esc_url($messenger_url); ?>" target="_blank" rel="noopener noreferrer" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2.5 rounded-lg text-center font-bold transition-colors text-sm flex items-center justify-center gap-2">
                        Chat qua Messenger
                    </a>
                </div>
            </div>

            <!-- Bản đồ Google Maps -->
            <div class="rounded-xl overflow-hidden border border-gray-200 shadow-sm h-[300px]">
                <?php
                $map_iframe = function_exists('get_field') ? get_field('mi_contact_map_iframe', 'option') : '';
                if ( ! empty( $map_iframe ) ) {
                    // Cho phép render mã nhúng iframe hợp lệ
                    echo $map_iframe;
                } else {
                ?>
                <!-- Fallback Map nếu chưa cấu hình ACF -->
                <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1m3!1d3724.8465620803456!2d105.79555461540192!3d21.00062409418659!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3135aca6cba8d147%3A0x6a0a2df362c451da!2zNDEgS2h14bqldCBEdXkgVGnhur9uLCBUaGFuaCBYdcOibiwgSMOgIE7hu5lp!5e0!3m2!1svi!2s!4v1689230584042!5m2!1svi!2s" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                <?php } ?>
            </div>

        </div>

        <!-- Cột Phải: Form Liên Hệ -->
        <div class="bg-white p-5 lg:p-8 rounded-2xl border border-slate-200 shadow-[0_12px_28px_-20px_rgba(15,23,42,0.28)] h-fit">
            <h2 class="text-xl lg:text-2xl font-black text-slate-800 mb-2">Gửi Tin Nhắn Cho Chúng Tôi</h2>
            <p class="text-slate-500 text-sm mb-6 leading-relaxed">Vui lòng điền thông tin vào mẫu bên dưới, chúng tôi sẽ phản hồi sớm nhất có thể.</p>

            <!-- Thông báo Form -->
            <?php if ( $submit_status === 'success' ) : ?>
                <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded">
                    <div class="flex items-center">
                        <span class="material-symbols-outlined text-green-500 mr-2">check_circle</span>
                        <p class="text-green-700 text-sm font-medium"><?php echo esc_html($submit_message); ?></p>
                    </div>
                </div>
            <?php elseif ( $submit_status === 'error' ) : ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded">
                    <div class="flex items-center">
                        <span class="material-symbols-outlined text-red-500 mr-2">error</span>
                        <p class="text-red-700 text-sm font-medium"><?php echo esc_html($submit_message); ?></p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Form -->
            <form action="" method="POST" class="space-y-5">
                <?php wp_nonce_field( 'mi_contact_action', 'mi_contact_nonce' ); ?>
                
                <div>
                    <label for="full_name" class="block text-sm font-bold text-gray-700 mb-1">Họ và tên <span class="text-red-500">*</span></label>
                    <input type="text" name="full_name" id="full_name" required placeholder="Nhập họ và tên của bạn" class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all text-sm bg-slate-50 focus:bg-white">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label for="phone" class="block text-sm font-bold text-gray-700 mb-1">Số điện thoại <span class="text-red-500">*</span></label>
                        <input type="tel" name="phone" id="phone" required placeholder="Nhập số điện thoại" class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all text-sm bg-slate-50 focus:bg-white">
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-bold text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" id="email" placeholder="Nhập địa chỉ email" class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all text-sm bg-slate-50 focus:bg-white">
                    </div>
                </div>

                <div>
                    <label for="message" class="block text-sm font-bold text-gray-700 mb-1">Nội dung lời nhắn <span class="text-red-500">*</span></label>
                    <textarea name="message" id="message" rows="5" required placeholder="Bạn cần chúng tôi hỗ trợ vấn đề gì?" class="w-full px-4 py-3 rounded-lg border border-slate-300 focus:ring-2 focus:ring-primary focus:border-primary outline-none transition-all text-sm bg-slate-50 focus:bg-white resize-none"></textarea>
                </div>

                <button type="submit" name="mi_contact_submit" class="w-full bg-primary hover:bg-primary-container text-white font-bold py-3.5 px-6 rounded-lg transition-colors flex items-center justify-center gap-2 mt-4 group">
                    <span>Gửi Yêu Cầu</span>
                    <span class="material-symbols-outlined group-hover:translate-x-1 transition-transform">send</span>
                </button>
            </form>
        </div>

    </div>
</main>

<?php get_footer(); ?>
