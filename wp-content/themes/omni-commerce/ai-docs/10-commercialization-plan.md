# Kế Hoạch Tổng Thể: Tái Cấu Trúc Thương Mại Hóa (Viomi Theme)

Mục tiêu: Xử lý dứt điểm 29 vấn đề để biến `omni-commerce` đạt tiêu chuẩn xét duyệt của các sàn thương mại (ThemeForest / WordPress.org).

Dự án sẽ được chia làm 4 Giai đoạn (Phase) từ dễ đến khó, đảm bảo không làm gãy đổ cấu trúc hiện tại.

---

## GIAI ĐOẠN 1: Chuẩn Hóa Cấu Trúc & File Nhận Diện (Core Structure & Identity)
**Mục tiêu:** Vượt qua các bài kiểm tra cấu trúc cơ bản của hệ thống Theme Review.
1. Tạo file `screenshot.png` (1200x900px) tại thư mục gốc.
2. Sửa `style.css`: Bổ sung khối Meta Data Header chuẩn (Theme Name, Author, Version, Text Domain, License).
3. Bổ sung các file Template bắt buộc còn thiếu:
   - `404.php`
   - `search.php`
   - `single.php`
   - `archive.php`
   - `comments.php`
4. Bảo mật File (Direct Access): Chèn `if ( ! defined( 'ABSPATH' ) ) { exit; }` vào đầu tất cả các file PHP (như `functions.php`, `header.php`, `footer.php`).

---

## GIAI ĐOẠN 2: Dọn Dẹp Hook & Định Danh (Hooks, Prefixing & Scripts)
**Mục tiêu:** Xử lý triệt để các xung đột script, lỗi Enqueue, và cấu hình Theme Supports.
1. Đổi tên handle lỗi bản quyền: Chuyển `astra-theme-css` thành `omni-commerce-style`.
2. Dọn dẹp `header.php`:
   - Xóa bỏ đoạn `<script>` inline của Mobile menu. Chuyển sang file `assets/js/navigation.js` và enqueue bằng `wp_enqueue_script()`.
   - Xóa bỏ thẻ `<style>` inline.
   - Thêm `<a class="skip-link screen-reader-text" href="#primary">Skip to content</a>`.
   - Thêm gọi hàm `wp_body_open()` sau thẻ `<body>`.
   - Thêm `<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">`.
3. Cập nhật Theme Supports trong `functions.php`:
   - `add_theme_support( 'title-tag' )`
   - `add_theme_support( 'automatic-feed-links' )`
   - Khai báo `$content_width`.
   - Gutenberg: `align-wide` và `wp-block-styles`.
   - Khai báo 3 support chuẩn của WooCommerce Gallery.

---

## GIAI ĐOẠN 3: Xử Lý Logic & Phân Quyền (Logic Decoupling & i18n)
**Mục tiêu:** Bóc tách dữ liệu động, làm đa ngôn ngữ và tạo Fallback an toàn.
1. Đa ngôn ngữ (i18n):
   - Thêm `load_theme_textdomain( 'omni-commerce', get_template_directory() . '/languages' );`.
   - Quét toàn bộ file `.php` và thay các chữ cứng bằng `esc_html__( 'Text', 'omni-commerce' )`.
2. Xử lý WooCommerce Fallback: Bọc các gọi hàm `WC()->...` trong khối `if( class_exists('WooCommerce') )`. Phục hồi comment `@version` trong các file template `woocommerce/`.
3. Tách ACF & Lãnh thổ Plugin (Plugin Territory):
   - Chuyển toàn bộ code liên quan đến "SEO JSON-LD" (nếu có) ra khỏi theme.
   - Bắt đầu đưa các thiết lập của ACF sang Customizer API hoặc dùng thư viện TGM Plugin Activation (TGMPA) để tự động yêu cầu cài ACF.
4. Cập nhật `post_class()`: Đưa hàm này vào toàn bộ các vòng lặp trong `home.php`, `category.php`. Fix lại định dạng ngày bằng `get_the_date()` không tham số.

---

## GIAI ĐOẠN 4: Tailwind & WP Core Widgets (Thiết kế & Tương thích nâng cao)
**Mục tiêu:** Đảm bảo TailwindCSS sống hòa thuận với các thành phần cốt lõi của WordPress.
1. Sidebar & Widgets: Bổ sung gọi hàm `dynamic_sidebar()` vào giao diện Blog.
2. Styling cho WP Core: Viết 1 file CSS `widgets-core.css` phục hồi giao diện cho lịch, search box, tag cloud bị Tailwind xóa mất. Định dạng cho các class `.alignleft`, `.wp-caption` trong nội dung.
3. Fix màu sắc động: Đưa biến màu của Tailwind trong `tailwind.config.js` thành Custom Properties (`var(--color-primary)`), cho phép đổi màu qua Customizer.
4. Bảo vệ Checkout: Cài `@tailwindcss/forms` để khôi phục giao diện các form mặc định của WooCommerce (Cart/Checkout).

---
**Tình trạng:** Sẵn sàng triển khai. Chờ lệnh từ User để tiến hành Giai đoạn 1.
