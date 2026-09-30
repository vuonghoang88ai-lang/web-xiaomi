# Kiến trúc Theme & Template Mapping

## 1. Cấu trúc file theme (Classic Theme)
```
omni-commerce/
├── front-page.php          # Điều phối trang chủ
├── header.php              # Header toàn site
├── footer.php              # Footer + Pre-Footer Trust Badges + FAB
├── functions.php           # Đăng ký hooks, ACF fields, nav walker
├── template-parts/
│   └── home/
│       ├── hero.php           # Hero 3 cột (features + slider + sidebar)
│       ├── media-services.php # Marquee + 3 video + value bar
│       ├── product-row.php    # 1 hàng danh mục sản phẩm (được gọi 3 lần)
│       └── news.php           # Lưới 4 tin tức
├── woocommerce/
│   ├── archive-product.php   # Lưới danh mục sản phẩm
│   ├── content-product.php   # Card sản phẩm đơn lẻ
│   └── single-product.php    # Trang chi tiết sản phẩm
└── ai-docs/                  # Tài liệu AI (sổ phụ)
```

## 2. Template Mapping (HTML tĩnh → PHP)
| File HTML gốc | File PHP đích | Ghi chú |
|--------------|--------------|---------|
| `html-templates/homepage.html` | `front-page.php` + `template-parts/home/` | |
| `html-templates/category.html` | `woocommerce/archive-product.php` | |
| `html-templates/product_detail.html` | `woocommerce/single-product.php` | |

**Quy tắc bóc tách:** Giữ nguyên 100% cấu trúc thẻ + class + ID HTML gốc. Chỉ thay text tĩnh bằng PHP functions.

## 3. Hệ thống Menu

### Menu Cấp 1 — Header (`header-menu`)
- Render qua `mi_render_header_navigation(false)` (desktop) và `mi_render_header_navigation(true)` (mobile)
- **Fallback 5 mục chuẩn** khi chưa cấu hình WP Admin: Tivi Xiaomi, Tủ Lạnh Xiaomi, Thiết bị gia đình, Liên hệ, Tin tức
- "Tra cứu bảo hành" là mục thứ 6 — Production thêm vào Admin, **KHÔNG TỰ Ý XÓA**
- **Phân giải icon tự động** từ title:
  - `tivi/tv` → `tv`
  - `lạnh` → `kitchen`
  - `gia đình/thiết bị` → `home_iot_device`
  - `liên hệ` → `contact_support`
  - `tin tức` → `newspaper`
  - `bảo hành/tra cứu` → `verified_user`

### Menu Cấp 2 — Category Bar (`category-menu`)
- Nằm trong `woocommerce/archive-product.php`, dropdown "DANH MỤC SẢN PHẨM"

### Anti-pattern Menu rỗng
```php
// ĐÚNG — kiểm tra items thực tế, không chỉ dùng has_nav_menu()
$menu_items = wp_get_nav_menu_items( get_nav_menu_locations()['header-menu'] ?? 0 );
if ( ! empty( $menu_items ) ) {
    wp_nav_menu( [...] );
} else {
    // Fallback render 5 mục chuẩn
}
```

## 4. Điều phối Product Row (front-page.php)
```php
// ĐÚNG — duyệt mảng chuẩn, truyền $args tường minh
$home_categories = [
    ['slug' => 'tivi-xiaomi',      'cat_title' => 'Tivi Xiaomi',       ...],
    ['slug' => 'tu-lanh-xiaomi',   'cat_title' => 'Tủ Lạnh Xiaomi',    ...],
    ['slug' => 'thiet-bi-gia-dinh','cat_title' => 'Thiết Bị Gia Đình', ...],
];
foreach ( $home_categories as $index => $cat_def ) {
    // ... resolve term, lấy thumbnail ...
    get_template_part( 'template-parts/home/product-row', null, $row_args );
}

// SAI — vòng lặp ACF không truyền $args → 3 hàng đều thành "Tivi Xiaomi"
// while ( have_rows('mi_home_product_row') ) { get_template_part('product-row'); }
```

## 5. Bản đồ Trang Danh mục
- **`woocommerce/archive-product.php`** — Layout chính, sidebar bộ lọc taxonomy, mô tả SEO
- **`woocommerce/content-product.php`** — Card sản phẩm (badge SALE/HOT, rating, giá, nút Mua)

## 6. Bản đồ Trang Chi tiết Sản phẩm
- **`woocommerce/single-product.php`** — Gallery, giá & khuyến mãi, nút MUA NGAY / MUA TRẢ GÓP, specs, reviews

## 5. Template Bắt Buộc (Required Templates)
- **`page.php`:** Đặc biệt quan trọng trong Custom Theme sử dụng Tailwind. Nếu thiếu `page.php`, WP sẽ fallback về `index.php`. Khi tạo trang tĩnh (Chính sách, Giới thiệu), `page.php` bắt buộc phải có khung layout chuẩn (VD: `max-w-[1440px] mx-auto`) để tránh việc nội dung bị tràn viền 100% hoặc mất định dạng.
