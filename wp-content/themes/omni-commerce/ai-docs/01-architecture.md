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
- **Fallback 6 mục chuẩn** khi chưa cấu hình WP Admin: Tivi Xiaomi, Tủ Lạnh Xiaomi, Thiết bị gia đình, Liên hệ, Tin tức, Tra cứu bảo hành
- "Tra cứu bảo hành" — mục thứ 6, tự động thêm qua init hook, **KHÔNG TỰ Ý XÓA**
- **Phân giải icon tự động** từ title:
  - `tivi/tv` → `tv`
  - `lạnh` → `kitchen`
  - `gia đình/thiết bị` → `home_iot_device`
  - `liên hệ` → `contact_support`
  - `tin tức` → `newspaper`
  - `bảo hành/tra cứu` → `verified_user`

### Auto-Menu Creation (init hook) — `inc/theme-setup.php` ~dòng 402
- Init hook tự động tạo "Header Menu", gán vào location `header-menu`
- **LUÔN override** `header-menu` → "Header Menu" (ID 20), kể cả khi đã gán nhầm vào menu khác
- Tự động thêm 6 mục chuẩn nếu chưa có item (check từng title để tránh duplicate)
- **BUG-077 pitfall:** Early-return ngăn hook chạy lại → header-menu stuck ở menu sai
- **Nav CSS pattern:** `header.php` dùng `hidden lg:flex` + `.main-nav-header{display:flex!important}` trong `@media(min-width:900px)` (input.css dòng ~28). CSS override bắt buộc hiển thị ở desktop, `hidden` ẩn ở mobile. Xem `02-frontend-rules.md` §3.

### Menu Cấp 3 — Footer Policy (`footer-policy`)
- Render qua `wp_nav_menu()` trong `footer.php` (dòng ~88), có fallback hardcoded links


### Menu Cấp 2 — Category Bar (`category-menu`)
- Nằm trong `woocommerce/archive-product.php`, dropdown "DANH MỤC SẢN PHẨM"
- Có fallback danh sách category mặc định nếu menu rỗng

### Anti-pattern Menu rỗng
```php
// ĐÚNG — kiểm tra items thực tế, không chỉ dùng has_nav_menu()
$menu_items = wp_get_nav_menu_items( get_nav_menu_locations()['header-menu'] ?? 0 );
if ( ! empty( $menu_items ) ) {
    wp_nav_menu( [...] );
} else {
    // Fallback render 6 mục chuẩn
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

## 5. Bản đồ Template Toàn Diện (Template Hierarchy & Mapping)
- **`front-page.php`:** Điều phối trang chủ theo trình tự chuẩn: Hero ➔ Media Services ➔ 3 Hàng sản phẩm ➔ News ➔ Footer (chứa pre-footer badges).
- **`page-tin-tuc.php` (Trang Tin Tức):** Template chuyên dụng cho trang tin tức/tạp chí công nghệ. Layout Magazine Grid gồm:
  - Khối 5 bài viết nổi bật đầu trang (1 bài chính lớn `md:col-span-3` cao 320px kèm overlay gradient + 4 bài phụ bên cạnh `md:col-span-2` tỉ lệ ảnh 4/3).
  - Thanh lọc chuyên mục nhanh dạng pill badges bo tròn với hiệu ứng hover mượt mà.
  - Danh sách bài viết chuẩn với ảnh tỉ lệ 16/9, meta tác giả/ngày tháng, excerpt 3 dòng và nút "Xem thêm" có hiệu ứng trượt icon.
  - Phân trang chuẩn WordPress qua `paginate_links()`.
  - Cột bên phải: Sticky sidebar "TIN XEM NHIỀU" lấy top 5 bài viết có nhiều bình luận.
- **`page-lien-he.php` (Trang Liên Hệ):** Template form liên hệ tích hợp bảo mật Nonce (`mi_contact_action`), làm sạch dữ liệu đầu vào (`sanitize_text_field`, `sanitize_email`, `sanitize_textarea_field`), gửi thông báo tức thời qua `wp_mail()`, nhúng bản đồ Google Maps tương tác và danh sách hệ thống showroom Tổng Kho Xiaomi.
- **`page-tra-cuu-bao-hanh.php` (Trang Tra Cứu Bảo Hành):** Khung bao chuyên dụng nhúng shortcode `[mi_warranty_check]` từ plugin `viomi-erp-system`, cho phép khách hàng tra cứu trạng thái bảo hành thiết bị theo Số điện thoại hoặc mã Serial/IMEI.
- **`page.php` (Trang Tĩnh & Trang Chính Sách Pháp Lý):** Khung bao trang tĩnh chuẩn `max-w-[1440px]`. Tự động nhận diện các trang chính sách pháp lý (`is_policy`) để áp dụng bố cục 2 cột chuyên nghiệp: Sticky sidebar điều hướng các chính sách liên quan bên trái và nội dung bài viết với typography viền accent cam Xiaomi, badge chứng nhận bên phải.
- **`single.php` (Chi Tiết Bài Viết Blog/Tin Tức):** Bố cục 2 cột với typography `prose prose-slate` thoáng đạt, tiêu đề H1 chuẩn mực, meta tác giả/ngày đăng, hỗ trợ phân trang bài viết dài (`wp_link_pages`), khung bình luận và sticky sidebar "TIN XEM NHIỀU".
- **`home.php` / `category.php` / `archive.php`:** Template lưu trữ và phân loại bài viết theo chuyên mục, tự động kế thừa cấu trúc grid tin tức và sidebar.
- **`search.php` & `404.php`:** Trang kết quả tìm kiếm với loop safety (`rewind_posts()`) và trang 404 thân thiện dẫn về trang chủ và danh mục sản phẩm chính.
- **`template-parts/content-post.php` & `content.php`:** Component card bài viết hiển thị ảnh tỉ lệ 16/9, ngày đăng, tiêu đề 2 dòng (`line-clamp-2`), mô tả ngắn và link "Đọc tiếp".

## 6. Mục Lục Sổ Phụ Kỹ Thuật & SOP Tự Động Hóa (`ai-docs/`)
1. **`01-architecture.md`**: Kiến trúc Theme, Template Mapping & Navigation Fallback.
2. **`02-frontend-rules.md`**: Quy tắc CSS Tailwind, Breakpoint Mobile Full-Width & Typography.
3. **`03-backend-wpcs.md`**: Chuẩn bảo mật WPCS, Nonce, Sanitization & HPOS Queries.
4. **`04-homepage.md`**: Cấu trúc thành phần trang chủ, Product Rows & Video Marquee.
5. **`05-buglog.md`**: Nhật ký 68 lỗi thực chiến (BUG-001 đến BUG-068) & giải pháp triệt để.
6. **`06-infrastructure.md`**: Môi trường hạ tầng Docker, Redis Caching, Cloudflare Tunnel & Docker-based Tailwind Build.
7. **`07-seo-json-ld-checklist.md`**: Kiểm thử Schema dữ liệu có cấu trúc JSON-LD & BreadcrumbList.
8. **`07-seo-pagespeed-audit.md`**: Chuẩn hóa Core Web Vitals, kiểm toán liên kết nội bộ & thẻ alt.
9. **`08-theme-standardization.md`**: Quy chuẩn đóng gói theme thương mại độc lập.
10. **`09-theme-commercialization.md`**: Quy trình thương mại hóa giao diện.
11. **`10-commercialization-plan.md`**: Lộ trình triển khai.
12. **`11-image-processing-sop.md`**: SOP xử lý ảnh sản phẩm tự động (Telea inpaint, crop 1:1, watermark TongKhoMi chìm, 5 kích thước WebP + JPG).
13. **`12-content-writing-sop.md`**: SOP viết bài chuẩn SEO On-page & CRO (4 link chính sách, 8 cụm từ sáo rỗng AI bị cấm, LLM-as-a-Judge 4 trọng số định lượng).
14. **`17-cmo-onboarding-saas-rag-architecture.md`**: SOP 17 - Phòng Marketing AI Thương Mại Hóa (Multi-tenant SaaS), Trinh sát TOP 10 đối thủ online, Cơ chế Kép Phân tích UVP & Tích hợp Dify RAG PostgreSQL.
15. **`18-cross-competitor-synthesis-and-woocommerce-batch-engine.md`**: SOP 18 - Động cơ hợp nhất đa đối thủ (Cross-Competitor Synthesis), Chế độ kép SEO Blog & WooCommerce Product, Hàng đợi xử lý hàng loạt Async Batch.

