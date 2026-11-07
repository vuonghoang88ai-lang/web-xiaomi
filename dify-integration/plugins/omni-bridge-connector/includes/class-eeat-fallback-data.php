<?php
/**
 * Class: EeatFallbackData
 * Kho dữ liệu 5 Trang Pháp Lý & Tín Nhiệm Đa Ngành Chuẩn E-E-A-T tổng hợp từ Đối thủ.
 * Tuân thủ nghiêm ngặt SOP 23 (<= 300 lines) và SOP 30 (Zero Hardcoding).
 */
if (!defined('ABSPATH')) exit;

class EeatFallbackData {

    public static function get_pages($brand, $domain, $hotline, $address) {
        $is_content = false;
        foreach (array('truyen', 'novel', 'comic', 'manga', 'book', 'read') as $w) {
            if (strpos($domain, $w) !== false || strpos(strtolower($brand), $w) !== false) {
                $is_content = true;
                break;
            }
        }
        if ($is_content) {
            return self::get_novel_pages($brand, $domain, $hotline);
        }
        return self::get_commerce_pages($brand, $domain, $hotline, $address);
    }

    protected static function get_novel_pages($brand, $domain, $hotline) {
        return array(
            'gioi-thieu' => array(
                'title'       => 'Về Chúng Tôi – Nền Tảng Đọc Truyện Chữ Chuẩn E-E-A-T ' . $brand,
                'short_title' => 'Giới Thiệu',
                'desc'        => 'Giới thiệu ' . $brand . ' - Nền tảng đọc truyện chữ trực tuyến tốc độ cao, tuyển chọn bản dịch & convert chất lượng cao.',
                'content'     => '<h2>1. Sứ Mệnh & Tầm Nhìn Nền Tảng ' . esc_html($brand) . '</h2>'
                    . '<p><strong>' . esc_html($brand) . '</strong> ra đời nhằm xây dựng không gian đọc truyện trực tuyến văn minh, tốc độ cao, phi thương mại và tối ưu trải nghiệm cho độc giả Việt Nam. Chúng tôi giải quyết triệt để vấn đề quảng cáo độc hại che khuất chữ, tình trạng khóa chương vô lý và dịch máy cẩu thả.</p>'
                    . '<h2>2. Kho Tàng Thư Viện Đa Dạng & Tuyển Chọn</h2>'
                    . '<p>Lưu trữ hàng chục ngàn bộ tiểu thuyết phân loại khoa học: Tiên Hiệp, Kiếm Hiệp, Ngôn Tình, Huyền Huyễn, Đô Thị, Trọng Sinh, Võng Du, Khoa Huyễn và Mạt Thế.</p>'
                    . '<h2>3. Tiêu Chuẩn Kiểm Duyệt Kép (Double-Check Curation)</h2>'
                    . '<p><strong>Bản Dịch Chọn Lọc:</strong> Hợp tác với các dịch giả tài năng, giữ trọn văn phong tác giả. <strong>Bản Convert / AI Hiệu Đính:</strong> Ứng dụng từ điển chuẩn 200.000 thuật ngữ, lọc sạch sạn từ ngữ xưng hô.</p>'
                    . '<h2>4. Công Nghệ Đọc Tối Ưu Mobile-First</h2>'
                    . '<p>Hỗ trợ Dark Mode chống mỏi mắt, tùy biến cỡ font, tự động lưu chương đọc dở và tải trang siêu tốc dưới 0.3s không pop-up.</p>'
                    . '<h2>5. Trụ Cột Tín Nhiệm E-E-A-T & Trách Nhiệm Xã Hội</h2>'
                    . '<p>' . esc_html($brand) . ' cam kết chuẩn E-E-A-T Google, bài trừ nội dung bạo lực, vi phạm thuần phong mỹ tục hoặc pháp luật Việt Nam.</p>'
            ),
            'dieu-khoan-su-dung' => array(
                'title'       => 'Điều Khoản Sử Dụng & Nội Quy Nền Tảng Độc Giả – ' . $brand,
                'short_title' => 'Điều Khoản Sử Dụng',
                'desc'        => 'Quy chế hoạt động, điều khoản dịch vụ và nội quy cộng đồng độc giả tại ' . $brand . '.',
                'content'     => '<h2>1. Chấp Thuận Điều Khoản</h2>'
                    . '<p>Khi truy cập ' . esc_html($brand) . ', bạn mặc nhiên đồng ý với quy chế hoạt động, chính sách bảo mật và nội quy văn hóa đọc văn minh của nền tảng.</p>'
                    . '<h2>2. Dịch Vụ Đọc Truyện Phi Thương Mại</h2>'
                    . '<p>Toàn bộ tác phẩm được cung cấp để đọc thử, giao lưu ngôn ngữ miễn phí. Nghiêm cấm sử dụng bot, crawler tự động cào trộm dữ liệu gây nghẽn băng thông.</p>'
                    . '<h2>3. Chuẩn Mực Ứng Xử & Văn Hóa Bình Luận</h2>'
                    . '<p>Tôn trọng tác giả, nhóm dịch. Nghiêm cấm spoil nội dung ác ý, nghiêm cấm chèn link cờ bạc, cá độ, khiêu dâm hoặc mã độc vào khung bình luận.</p>'
                    . '<h2>4. Quyền Sở Hữu Trí Tuệ & Chế Tài Vi Phạm</h2>'
                    . '<p>Tác giả thành viên giữ bản quyền truyện sáng tác. Ban Quản Trị có quyền xóa bình luận và khóa IP vĩnh viễn đối với hành vi phá hoại.</p>'
            ),
            'chinh-sach-bao-mat' => array(
                'title'       => 'Chính Sách Bảo Mật & Quyền Riêng Tư Độc Giả – ' . $brand,
                'short_title' => 'Chính Sách Bảo Mật',
                'desc'        => 'Cam kết bảo vệ quyền riêng tư và an toàn dữ liệu độc giả theo Nghị định 13/2023/NĐ-CP tại ' . $brand . '.',
                'content'     => '<h2>1. Tôn Trọng Sự Ẩn Danh Của Độc Giả</h2>'
                    . '<p>' . esc_html($brand) . ' tôn trọng tối đa quyền riêng tư: độc giả có thể thưởng thức toàn bộ truyện mà không bắt buộc khai báo danh tính cá nhân.</p>'
                    . '<h2>2. Phạm Vi Thu Thập Dữ Liệu Tối Thiểu</h2>'
                    . '<p>Chỉ lưu trữ cookie cài đặt giao diện (màu nền, cỡ chữ), lịch sử chương đọc dở và log IP ẩn danh để chống tấn công DDoS.</p>'
                    . '<h2>3. Cam Kết Ba KHÔNG</h2>'
                    . '<p>KHÔNG bán dữ liệu cho bên thứ ba; KHÔNG cài mã đào tiền ảo ngầm hay pop-up lừa đảo; KHÔNG lưu trữ dữ liệu tài chính nhạy cảm.</p>'
                    . '<h2>4. Bảo Mật Đường Truyền SSL/TLS</h2>'
                    . '<p>Toàn bộ kết nối được mã hóa chuẩn HTTPS 256-bit, bảo vệ an toàn cho độc giả trên mọi hạ tầng mạng Wi-Fi.</p>'
            ),
            'ban-quyen-dmca' => array(
                'title'       => 'Tuyên Bố Bản Quyền & Quy Trình Khiếu Nại DMCA – ' . $brand,
                'short_title' => 'Bản Quyền & DMCA',
                'desc'        => 'Chính sách tôn trọng quyền tác giả và quy trình tiếp nhận khiếu nại DMCA minh bạch tại ' . $brand . '.',
                'content'     => '<h2>1. Tôn Trọng Quyền Tác Giả & Sở Hữu Trí Tuệ</h2>'
                    . '<p>' . esc_html($brand) . ' tuân thủ Luật Sở hữu trí tuệ Việt Nam và Công ước Bern về bảo hộ tác phẩm văn học nghệ thuật.</p>'
                    . '<h2>2. Bản Chất Nền Tảng Thư Viện Mở</h2>'
                    . '<p>Tác phẩm được chia sẻ phi thương mại nhằm giao lưu học tập. Chúng tôi sẵn sàng hợp tác với các chủ sở hữu quyền tác giả hợp pháp.</p>'
                    . '<h2>3. Quy Trình Tiếp Nhận DMCA</h2>'
                    . '<p>Chủ sở hữu gửi văn bản khiếu nại kèm link chương vi phạm và giấy tờ chứng minh tác quyền về hòm thư <strong>dmca@' . esc_html($domain) . '</strong>.</p>'
                    . '<h2>4. Cam Kết Xử Lý Thần Tốc</h2>'
                    . '<p>Xác nhận email trong 12h và gỡ bỏ hoàn toàn tác phẩm vi phạm khỏi máy chủ trong vòng 24 - 48h làm việc.</p>'
            ),
            'lien-he' => array(
                'title'       => 'Liên Hệ Ban Biên Tập & Hộp Thư Báo Lỗi Chương – ' . $brand,
                'short_title' => 'Liên Hệ & Báo Lỗi',
                'desc'        => 'Kênh liên hệ trực tiếp Ban Quản Trị ' . $brand . ' để báo lỗi chương, góp ý và hợp tác nội dung.',
                'content'     => '<h2>1. Kênh Kết Nối Ban Quản Trị</h2>'
                    . '<p>Chúng tôi luôn lắng nghe phản hồi của quý độc giả để hoàn thiện chất lượng bản dịch và trải nghiệm đọc mỗi ngày.</p>'
                    . '<h2>2. Hướng Dẫn Báo Lỗi Chương Nhanh</h2>'
                    . '<p>Khi phát hiện lỗi mất chữ, nhảy chương, lỗi font: Gửi tên truyện, số chương và link bị lỗi. Kỹ thuật cam kết xử lý trong 2 - 6 giờ.</p>'
                    . '<h2>3. Thông Tin Liên Hệ Chính Thức</h2>'
                    . '<p>Hộp thư Ban Quản Trị: <strong>contact@' . esc_html($domain) . '</strong> | Bộ phận DMCA: <strong>dmca@' . esc_html($domain) . '</strong> | Trực ban 24/7.</p>'
            )
        );
    }

    protected static function get_commerce_pages($brand, $domain, $hotline, $address) {
        return array(
            'chinh-sach-bao-hanh' => array(
                'title'       => 'Chính Sách Bảo Hành & Đổi Mới Chuẩn Hãng – ' . $brand,
                'short_title' => 'Chính Sách Bảo Hành',
                'desc'        => 'Cam kết bảo hành chính hãng và hỗ trợ kỹ thuật tận tâm tại ' . $brand . '.',
                'content'     => '<h2>Chính Sách Bảo Hành</h2><p>Cam kết bảo hành chính hãng 100% tại kho ' . esc_html($address) . '. Hotline: ' . esc_html($hotline) . '.</p>'
            ),
            'chinh-sach-doi-tra' => array(
                'title'       => 'Chính Sách Đổi Trả & Hoàn Tiền Minh Bạch – ' . $brand,
                'short_title' => 'Chính Sách Đổi Trả',
                'desc'        => 'Đổi trả minh bạch trong 30 ngày tại ' . $brand . '.',
                'content'     => '<h2>Chính Sách Đổi Trả</h2><p>Đổi mới trong 30 ngày nếu phát sinh lỗi kỹ thuật từ nhà sản xuất.</p>'
            ),
            'chinh-sach-van-chuyen' => array(
                'title'       => 'Chính Sách Giao Hàng & Đồng Kiểm Toàn Quốc – ' . $brand,
                'short_title' => 'Chính Sách Giao Hàng',
                'desc'        => 'Giao hàng an toàn và hỗ trợ đồng kiểm trước khi thanh toán tại ' . $brand . '.',
                'content'     => '<h2>Chính Sách Giao Hàng</h2><p>Giao hàng toàn quốc an toàn, khách hàng được kiểm tra trước khi thanh toán.</p>'
            ),
            'gioi-thieu' => array(
                'title'       => 'Về Chúng Tôi – Đơn Vị Phân Phối Chuẩn E-E-A-T ' . $brand,
                'short_title' => 'Giới Thiệu',
                'desc'        => 'Giới thiệu năng lực phân phối và uy tín dịch vụ của ' . $brand . '.',
                'content'     => '<h2>Về ' . esc_html($brand) . '</h2><p>Đơn vị phân phối uy tín hàng đầu, cam kết giá gốc và chất lượng chính hãng.</p>'
            ),
            'lien-he' => array(
                'title'       => 'Thông Tin Liên Hệ Trực Tiếp & Kho Hàng – ' . $brand,
                'short_title' => 'Liên Hệ & Kho Hàng',
                'desc'        => 'Địa chỉ kho hàng, hotline và kênh hỗ trợ trực tiếp của ' . $brand . '.',
                'content'     => '<h2>Liên Hệ</h2><p>Kho hàng: ' . esc_html($address) . ' | Hotline: ' . esc_html($hotline) . ' | Email: contact@' . esc_html($domain) . '</p>'
            )
        );
    }
}

