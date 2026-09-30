# Tiêu chuẩn Thương mại hóa WordPress Theme (Commercialization Standards)

## 1. Tính Đa ngôn ngữ (Internationalization - i18n)
- **Vấn đề:** Các văn bản trong giao diện (như text ở header, footer, placeholder) đang bị hardcode cứng (ví dụ: tiếng Việt).
- **Giải pháp:** Bọc toàn bộ các chuỗi văn bản tĩnh bằng các hàm dịch thuật của WordPress.
  - Sử dụng `esc_html__( 'Text', 'omni-commerce' )` hoặc `__( 'Text', 'omni-commerce' )`.
  - Tạo thư mục `languages/` và biên dịch file `omni-commerce.pot` chứa tất cả các string này làm file gốc cho người dùng.

## 2. Quản lý Theme Options (Giảm phụ thuộc cứng ACF)
- **Vấn đề:** Đang phụ thuộc 100% vào plugin ACF Pro cho các cài đặt (Theme Options). Người dùng cuối bắt buộc phải cài (và đôi khi mua) ACF để theme hoạt động.
- **Giải pháp:**
  - Cách 1: Chuyển đổi các cấu hình cốt lõi (như thay đổi Logo, Hotline, Header Text) sang sử dụng **WordPress Customizer API** mặc định.
  - Cách 2: Tích hợp thư viện **TGM Plugin Activation (TGMPA)** để tự động yêu cầu cài đặt ACF ngay khi người dùng kích hoạt theme, đảm bảo trải nghiệm mượt mà.

## 3. Đồng nhất Tiền tố (Prefixing)
- **Vấn đề:** Hiện tại có sự pha trộn giữa tiền tố `mi_` (ở ACF/Options) và `viomi_theme_` (ở hooks). 
- **Giải pháp:** Thống nhất một tiền tố (Prefix) duy nhất là `viomi_` cho toàn bộ dự án. Áp dụng cho: Tên hàm, biến toàn cục, ID của Hook, Handle của scripts/styles, và Image Sizes.

## 4. Bảo mật dữ liệu (Sanitization & Escaping)
- **Vấn đề:** Theme Review Team sẽ quét rất kỹ việc bảo mật dữ liệu ở cấp độ thương mại.
- **Giải pháp:** Thực hiện kiểm tra 100% mã nguồn:
  - Mọi output in ra (echo) phải qua các hàm `esc_html()`, `esc_url()`, `esc_attr()`, hoặc `wp_kses_post()`.
  - Dữ liệu trước khi lưu vào DB phải qua `sanitize_text_field()` v.v...

## 5. Tài liệu & Khả năng triển khai
- **Cấu trúc File:** Cần bổ sung file `readme.txt` chuẩn WordPress, chứa changelog, plugin requires, thông tin phiên bản.
- **License:** Đảm bảo toàn bộ tài nguyên đi kèm (JS, CSS, Font) tuân thủ hoàn toàn giấy phép GPL.
- **Demo Import:** Bổ sung tính năng "One-Click Demo Import" kèm theo cấu hình tự động (dữ liệu mẫu .xml, widget, customizer), giúp người mua có thể cài đặt ra giao diện giống hệt demo chỉ với 1 click.

## 6. Thiếu Core Templates & `wp_body_open()`
- **Vấn đề:** Theme đang thiếu các file bắt buộc: `404.php`, `search.php`, `single.php`, `archive.php`, `comments.php`. Ngoài ra, `header.php` thiếu hook `wp_body_open()` (bắt buộc từ WP 5.2).
- **Giải pháp:** Bổ sung toàn bộ các template file cơ bản. Gọi `wp_body_open()` ngay sau thẻ mở `<body>`.

## 7. Xử lý Giao diện Soạn thảo (Editor Styles & Core Classes)
- **Vấn đề:** Theme sử dụng Tailwind làm vỡ style của các class mặc định do WP Editor sinh ra (`.alignleft`, `.wp-caption`...). Không có sự đồng bộ giao diện giữa khung soạn thảo và frontend.
- **Giải pháp:** Cần thêm CSS định dạng lại các class mặc định của WP. Khai báo `add_theme_support( 'editor-styles' )` và nạp `editor-style.css`.

## 8. Khả năng tiếp cận (Accessibility - a11y)
- **Vấn đề:** Điều hướng menu và các nút bấm chưa đạt chuẩn hỗ trợ phím (Keyboard Navigation) và Screen Readers.
- **Giải pháp:** Bổ sung các thuộc tính `aria-expanded`, `aria-controls` cho các thành phần tương tác (như Mobile Menu). Đảm bảo sub-menu có thể mở bằng phím Tab/Enter.

## 9. Thiếu File Nhận diện (screenshot.png/jpg)
- **Vấn đề:** Không có ảnh screenshot trong thư mục gốc. Khi cài theme, admin sẽ hiển thị khoảng trống, bị đánh lỗi Fatal khi duyệt theme.
- **Giải pháp:** Thêm file `screenshot.png` (kích thước 1200x900) vào thư mục gốc của theme.

## 10. Fallback an toàn cho WooCommerce
- **Vấn đề:** Theme gọi thẳng các hàm của WooCommerce (như `WC()->cart`) mà không bọc trong điều kiện `class_exists('WooCommerce')`. Nếu người dùng tắt plugin, website sẽ sập (Fatal Error).
- **Giải pháp:** Phải có code kiểm tra an toàn hoặc dùng hàm `function_exists()` trước khi gọi API của WooCommerce.

## 11. Các chuẩn khai báo nhỏ khác (Content Width & Comment Reply)
- **Vấn đề:** Thiếu biến toàn cục `$content_width` (bắt buộc để xác định kích thước tối đa của oEmbed video). Thiếu enqueue script `comment-reply` nếu bài viết mở tính năng bình luận.
- **Giải pháp:** Thêm biến global `$content_width` vào `functions.php`. Thêm điều kiện `if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) { wp_enqueue_script( 'comment-reply' ); }`.

## 12. Thiếu Hỗ trợ WooCommerce Gallery Standards
- **Vấn đề:** Theme đã support `woocommerce`, nhưng thiếu các khai báo cụ thể cho tính năng bộ sưu tập ảnh (Gallery) tiêu chuẩn của WooCommerce.
- **Giải pháp:** Thêm các dòng `add_theme_support('wc-product-gallery-zoom');`, `add_theme_support('wc-product-gallery-lightbox');` và `add_theme_support('wc-product-gallery-slider');`.

## 13. Thiếu `post_class()` trong các Vòng lặp (Loops)
- **Vấn đề:** Các file `category.php` và `home.php` render bài viết nhưng không dùng hàm `post_class()` ở thẻ HTML bọc ngoài mỗi bài. Điều này phá vỡ tiêu chuẩn thiết kế của WP, khiến các plugin khác không thể nhận diện dạng bài viết qua CSS class.
- **Giải pháp:** Đảm bảo mọi bài viết trong vòng lặp đều có `<article <?php post_class( 'các-class-tailwind-của-bạn' ); ?>>`.

## 14. Vi phạm chuẩn Phiên bản Template WooCommerce (Template Versioning)
- **Vấn đề:** File `woocommerce/content-product.php` (và có thể các file khác) đã bị xóa mất khối comment chứa `@version` ở đầu file. 
- **Giải pháp:** Bắt buộc phải giữ lại comment phiên bản (ví dụ: `@version 3.6.0`) để trang System Status của WooCommerce có thể theo dõi và cảnh báo người dùng khi template bị lỗi thời.

## 15. Nhầm lẫn Lãnh thổ (Theme Territory vs Plugin Territory)
- **Vấn đề:** Thông qua các file định hướng, dường như theme đang gánh cả việc xử lý "SEO JSON-LD". Theo luật của WP: *Theme chỉ xử lý Giao diện, Plugin xử lý Tính năng*. 
- **Giải pháp:** Mọi tính năng về SEO, Custom Post Types, Shortcodes, Analytics bắt buộc phải được bóc tách ra một Plugin đi kèm (ví dụ `viomi-core-plugin`), tuyệt đối không được nhét vào `functions.php`. Nếu không, theme sẽ bị từ chối 100% trên các chợ thương mại.

## 16. Website vô tình bị "mất" thẻ `<title>`
- **Vấn đề:** Mặc dù trong `header.php` có ghi chú là thẻ title được xử lý qua `wp_head()`, nhưng khi quét `functions.php`, hoàn toàn **không có hàm `add_theme_support('title-tag')`**. Hệ quả là nếu không cài plugin SEO, trang web không hề có tiêu đề trang (Title) trên trình duyệt.
- **Giải pháp:** Bắt buộc bổ sung `add_theme_support( 'title-tag' );` vào hàm setup của theme.

## 17. Vi phạm nguyên tắc Enqueue (Chứa Script inline trong HTML)
- **Vấn đề:** Ở cuối file `header.php` có một đoạn `<script>` viết trực tiếp (inline) để xử lý logic đóng/mở Mobile Menu. 
- **Giải pháp:** Trong theme thương mại, mọi logic JavaScript phải được tách ra file `.js` độc lập (ví dụ `assets/js/navigation.js`) và nạp vào bằng hàm `wp_enqueue_script()`. Không được nhúng thẳng thẻ `<script>` vào các file template php.

## 18. Thiếu hỗ trợ Gutenberg Block Editor
- **Vấn đề:** Hiện tại là thời đại của Gutenberg (Block Editor), nhưng theme hoàn toàn không khai báo hỗ trợ các tính năng cơ bản của trình soạn thảo này.
- **Giải pháp:** Phải bổ sung `add_theme_support( 'align-wide' );` (cho phép block dàn toàn màn hình) và `add_theme_support( 'wp-block-styles' );` để tương thích với các core blocks của WP.

## 19. Xung đột định danh (Sử dụng code copy-paste từ theme khác)
- **Vấn đề:** Trong `functions.php`, phần `wp_enqueue_style` đang gọi handle là `'astra-theme-css'`. Đây là một dấu vết copy-paste trực tiếp từ theme nổi tiếng Astra. Việc dùng handle của theme khác sẽ gây conflict nghiêm trọng hoặc bị đánh trượt ngay lập tức vì vi phạm bản quyền định danh.
- **Giải pháp:** Đổi tên handle thành tiền tố của theme hiện tại (ví dụ: `'viomi-style'`).

## 20. Khuyết thiếu Hỗ trợ RSS Feed
- **Vấn đề:** WordPress bắt buộc mọi theme thương mại phải hỗ trợ tính năng xuất RSS tự động cho độc giả hoặc các hệ thống thu thập tin tức, nhưng code hiện tại không có.
- **Giải pháp:** Bổ sung `add_theme_support( 'automatic-feed-links' );` vào hàm setup của theme.

## 21. Bỏ qua Bảo mật Truy cập Trực tiếp (Direct Access)
- **Vấn đề:** Các file PHP cốt lõi (`functions.php`, `header.php`...) hoàn toàn không có cơ chế chặn người dùng gõ URL truy cập trực tiếp vào file.
- **Giải pháp:** Phải đặt đoạn mã `if ( ! defined( 'ABSPATH' ) ) { exit; }` ở dòng thứ 2 của TẤT CẢ các file PHP (trừ các template gọi trực tiếp từ router WP).

## 22. Vi phạm trợ năng: Thiếu "Skip to Content"
- **Vấn đề:** Bất kỳ theme chuẩn nào cũng phải có một thẻ `<a>` ẩn ở ngay sau thẻ `<body>` để giúp người dùng khiếm thị (dùng phím Tab) có thể nhảy thẳng vào nội dung chính mà không phải qua Menu.
- **Giải pháp:** Thêm `<a class="skip-link screen-reader-text" href="#primary">Skip to content</a>` vào đầu `header.php` và thêm CSS ẩn mặc định, hiện khi focus.

## 23. Ghi đè cấu hình hiển thị Ngày tháng (Hardcoded Date Format)
- **Vấn đề:** Tôi thấy hàm `get_the_date('d/m/Y')` được dùng cứng. Nếu một khách hàng (ở Mỹ chẳng hạn) mua theme và set định dạng ngày trong WP Admin là `m/d/Y`, theme của bạn vẫn cứng đầu hiện `d/m/Y`.
- **Giải pháp:** Gọi hàm `get_the_date()` không truyền tham số để WP tự động lấy cấu hình trong mục Settings > General.

## 24. Thiếu thẻ Pingback URL
- **Vấn đề:** Thẻ `head` thiếu đường dẫn Pingback/Trackback tiêu chuẩn của WP.
- **Giải pháp:** Thêm `<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">` vào `header.php`.

## 25. Thiếu khai báo `load_theme_textdomain` (Liệt tính năng dịch)
- **Vấn đề:** Mặc dù trước đó chúng ta nói về việc dùng hàm `__()` để bọc các chuỗi văn bản, nhưng nếu trong `functions.php` không gọi hàm `load_theme_textdomain()`, WordPress sẽ không bao giờ tải được các file `.mo/.po` từ thư mục `/languages`. Mọi nỗ lực đa ngôn ngữ sẽ hoàn toàn vô tác dụng.
- **Giải pháp:** Gọi `load_theme_textdomain( 'omni-commerce', get_template_directory() . '/languages' );` trong hàm `after_setup_theme`.

## 26. Tính năng Sidebar (Widget) bị "liệt" trên Blog
- **Vấn đề:** Theme có đăng ký tính năng sidebar thông qua `register_sidebar()` trong `functions.php`. Tuy nhiên, ở các trang danh mục bài viết (`category.php`, `home.php` hay `page.php`), hoàn toàn không có hàm `dynamic_sidebar()` nào được gọi ra.
- **Hệ quả:** Người dùng mua theme về, hí hửng kéo thả các Widget (Bài viết mới nhất, Banner quảng cáo...) vào Sidebar trong WP Admin, nhưng ngoài màn hình Blog thì không bao giờ hiển thị.
- **Giải pháp:** Bổ sung cấu trúc 2 cột cho trang Blog và thêm `dynamic_sidebar( 'tên-sidebar' )`.

## 27. Đóng băng Màu sắc thương hiệu (Hardcoded Branding)
- **Vấn đề:** Màu sắc chủ đạo (Primary color, Secondary color) đang bị "đóng cứng" (hardcode) trong file `tailwind.config.js` và biên dịch thẳng ra file CSS tĩnh. Khách hàng mua theme không thể vào phần *Appearance > Customize* để tự đổi màu theo thương hiệu của họ.
- **Giải pháp:** Phải thiết lập Tailwind sử dụng CSS Variables (Ví dụ: `colors: { primary: 'var(--color-primary)' }`). Sau đó dùng Customizer API để người dùng có thể đổi màu sắc biến CSS này qua giao diện Admin.

## 28. "Mù" CSS với các Widget cốt lõi của WordPress
- **Vấn đề:** Trình reset CSS mặc định của Tailwind (Preflight) đã xóa sạch định dạng thẻ HTML. Vì theme không viết CSS cho các class mặc định của Widget (như `.widget_calendar`, `.widget_recent_entries`, `.tagcloud`), nên nếu khách hàng chèn Lịch hoặc Tag Cloud vào, giao diện sẽ vỡ nát, các con số dính chặt vào nhau.
- **Giải pháp:** Bổ sung file `widgets-style.css` định dạng lại toàn bộ 12 Widget mặc định của WordPress.

## 29. Khả năng vỡ form Checkout/Cart của WooCommerce
- **Vấn đề:** Theme không ghi đè (override) các file giỏ hàng (`cart.php`) hay thanh toán (`checkout.php`), tức là đang dùng bản gốc của WooCommerce. Tuy nhiên, Tailwind Preflight reset toàn bộ style của form (`input`, `select`, `textarea`). Hệ quả là trang Checkout mặc định của WooCommerce sẽ bị lỗi hiển thị các ô nhập liệu.
- **Giải pháp:** Hoặc là thiết kế lại template Checkout, hoặc phải sử dụng plugin `@tailwindcss/forms` kết hợp viết thêm CSS chuyên biệt để cứu lại giao diện form gốc của WooCommerce.
