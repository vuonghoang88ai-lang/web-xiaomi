# Kế hoạch Chuẩn hóa WordPress Theme (Theme Standardization)

## 1. Tình trạng hiện tại (Assessment)
Theme `omni-commerce` hiện đang có nền tảng cơ bản của WordPress nhưng chưa tuân thủ hoàn toàn các tiêu chuẩn (WordPress Theme Standards) để trở thành một "starter theme" sạch, dễ bảo trì và nhân bản.

### ✅ Điểm đạt:
- Đã đăng ký hook và có setup cơ bản trong `functions.php`.
- Đã sử dụng `wp_head()`, `body_class()`, `get_header()`, `get_footer()` đúng chuẩn trong `header.php`.
- Đã hỗ trợ WooCommerce và `post-thumbnails`.
- Có phân cấp template rõ ràng (`front-page.php`, `page.php`, v.v.).

### ⚠️ Điểm cần khắc phục:
1. **Thiếu Theme Header Metadata:** File `style.css` hiện tại chỉ chứa code build của Tailwind mà không có khối comment khai báo thông tin theme (Theme Name, Author, Version, Description...).
2. **Inline CSS sai chỗ:** `header.php` chứa quá nhiều CSS inline (`<style>`). Cần di chuyển vào file CSS chính (thông qua quá trình build của Tailwind).
3. **Thiếu Theme Supports quan trọng:** Chưa khai báo hỗ trợ `title-tag`, `custom-logo`, `html5`, `menus` trong `functions.php`.
4. **Code bị dồn cục (Bloated):** `functions.php` đang gánh quá nhiều trách nhiệm (khai báo ACF, setup theme, WooCommerce tweaks...). Cần chia nhỏ theo Module.

## 2. Kế hoạch Tái cấu trúc & Chuẩn hóa (Action Plan)

### Bước 1: Chuẩn hóa `style.css`
- Bổ sung khối comment header (Theme Name, Theme URI, Author, Description, Version, Text Domain) ở vị trí đầu file.
- Đảm bảo cấu hình config Tailwind build ra file không xóa mất header này.

### Bước 2: Dọn dẹp `header.php`
- Loại bỏ toàn bộ thẻ `<style>` inline.
- Chuyển logic CSS vào file mã nguồn Tailwind (`src/style.css` hoặc tương đương) để build lại.
- Bảm bảo `header.php` chỉ thuần HTML/PHP mở `<body>`, `<header>`, gọi `wp_head()`.

### Bước 3: Đăng ký Theme Supports
Thêm vào hàm setup của theme:
```php
add_theme_support( 'title-tag' );
add_theme_support( 'custom-logo' );
add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
register_nav_menus( array(
    'primary' => __( 'Primary Menu', 'omni-commerce' ),
    'footer'  => __( 'Footer Menu', 'omni-commerce' ),
) );
```

### Bước 4: Tái cấu trúc `functions.php` (Decoupling)
- Tạo/kiểm tra thư mục `inc/` và tách file:
  - `inc/theme-setup.php` (chứa enqueue, theme support).
  - `inc/acf-fields.php` (chứa code `acf_add_local_field_group`).
  - `inc/woocommerce-tweaks.php` (chứa logic liên quan tới WooCommerce).
- Tại `functions.php` chỉ còn lại các lệnh `require_once` để include các module vào.

## 3. Mục tiêu cuối cùng
- Biến `omni-commerce` thành một cấu trúc "skeleton" sạch chuẩn WordPress.
- Dễ dàng maintain, không bị rối logic khi dự án phình to.
- Có thể copy nguyên gốc sang một dự án khác để làm nền tảng nhanh chóng.
