# Omni-Commerce Theme (`omni-commerce`)

Giao diện thương mại điện tử chuyên biệt dành riêng cho hệ thống phân phối thiết bị công nghệ Xiaomi chính hãng (**TongKhoMi.com / Michinhhang**). Được thiết kế theo chuẩn Classic Theme kết hợp TailwindCSS CLI, ACF Pro, WooCommerce, và tích hợp sâu với Rank Math SEO cùng hệ sinh thái đa tác tử Omni CrewAI.

---

## 1. Thông Tin & Triết Lý Thiết Kế

| Hạng mục | Quy chuẩn kỹ thuật |
|----------|-------------------|
| **Theme Name** | Omni-Commerce (TongKhoMi Xiaomi Storefront) |
| **Kiến trúc** | Classic Theme (PHP thuần, không FSE, không Gutenberg Page Builder) |
| **CSS Engine** | TailwindCSS CLI biên dịch độc lập (`tailwindcss-linux-x64`), không dùng inline CSS |
| **Tầng dữ liệu** | ACF Pro dynamic (`get_field()`, `have_rows()`) kết hợp WooCommerce CRUD |
| **Độ rộng khung chứa** | Đồng nhất toàn trang `max-w-[1440px] mx-auto px-4` |
| **Thương hiệu & Màu sắc** | Cam Xiaomi (`#f95700` / `#ff6900`), Xanh phiến thạch Slate Navy (`#1e293b`), Trắng (`#ffffff`) |
| **Môi trường chạy** | 100% Docker (`michinhhang-wordpress-1`), không chạy PHP/WP-CLI trên host |

### Các Nguyên Tắc Bất Di Bất Dịch
1. **YAGNI (You Aren't Gonna Need It)**: Không cài đặt plugin bên thứ 3 cho các tính năng nhỏ. Ưu tiên viết PHP thuần tối ưu hiệu năng.
2. **Decoupling (Phân rã độc lập)**: Theme hoàn toàn độc lập với ERP Plugin (`viomi-erp-system`) và Portal (`omni-crewai-portal`). Không chia sẻ CSS chéo.
3. **Loop & Template Safety**: Mọi custom query phải bảo đảm tính toàn vẹn với `rewind_posts()`. Luôn có template parts fallback (`content.php`, `content-search.php`, `index.php`).
4. **Data Fallback**: Nếu dữ liệu ACF rỗng, theme luôn tự động render giao diện mẫu (mock layout) chuẩn chỉnh, tuyệt đối không để trang trắng hoặc text lỗi.
5. **Escape Output**: Mọi dữ liệu in ra màn hình phải được lọc an toàn qua `esc_html()`, `esc_attr()`, `esc_url()`.

---

## 2. Cấu Trúc Thư Mục Theme (Directory Tree)

```text
wp-content/themes/omni-commerce/
├── style.css                      # CSS chính đã được biên dịch & minify từ Tailwind CLI
├── functions.php                  # Entry point nạp các module con trong inc/
├── header.php                     # Header sticky top-0, logo TongKhoMi WebP, search & nav menu
├── footer.php                     # Footer 4 cột chuẩn e-commerce, pre-footer trust badges & FAB hotline
├── front-page.php                 # Điều phối trang chủ theo đúng thứ tự logic bắt buộc
├── home.php                       # Template trang danh sách tin tức / blog
├── index.php                      # Fallback index chính của WordPress
├── single.php                     # Giao diện chi tiết bài viết tin tức & đánh giá
├── archive.php                    # Lưu trữ bài viết theo ngày/tháng/tác giả
├── category.php                   # Danh mục bài viết blog
├── search.php                     # Giao diện kết quả tìm kiếm toàn trang
├── 404.php                        # Trang báo lỗi 404 thân thiện người dùng
├── page.php                       # Template trang tĩnh chung (container 1440px)
├── page-lien-he.php               # Giao diện trang Liên hệ & Bản đồ
├── page-tin-tuc.php               # Giao diện chuyên mục Tin tức công nghệ
├── page-tra-cuu-bao-hanh.php      # Giao diện tra cứu bảo hành điện tử (Serial/IMEI)
├── tailwind.config.js             # Cấu hình bảng màu Xiaomi, breakpoints và typography
├── screenshot.png                 # Ảnh chụp xem trước giao diện trong WordPress Admin
│
├── inc/                           # Các module tính năng backend tách biệt
│   ├── theme-setup.php            # Khởi tạo theme support, menu, scripts, responsive assets
│   ├── acf-fields.php             # Đăng ký field groups ACF bằng PHP (Zero DB-query)
│   ├── woocommerce.php            # Tùy biến hooks, AJAX add-to-cart, giá khuyến mãi & social proof
│   ├── seo.php                    # Tích hợp Rank Math JSON-LD, Breadcrumb, dọn dẹp thẻ thừa
│   ├── performance.php            # Tối ưu hóa tải trang, lazy-load, async/defer scripts
│   ├── customizer.php             # Tích hợp tùy biến giao diện qua WordPress Customizer
│   ├── pagination.php             # Phân trang số học chuẩn UX
│   ├── plugins-require.php        # Khai báo plugin bắt buộc
│   └── class-tgm-plugin-activation.php # Thư viện TGMPA quản lý plugin phụ thuộc
│
├── template-parts/                # Các thành phần giao diện nhỏ dùng lại
│   ├── content.php                # Card bài viết mặc định trong vòng lặp
│   ├── content-post.php           # Template render nội dung post
│   ├── content-search.php         # Card hiển thị bài viết trong kết quả tìm kiếm
│   └── home/                      # Các khối section chuyên biệt trên Trang Chủ
│       ├── hero.php               # Hero banner: 3 cột (Danh mục, Slider chính, Ưu đãi nhanh)
│       ├── media-services.php     # Marquee cam kết & khối 3 video / dịch vụ nổi bật
│       ├── product-row.php        # Hàng sản phẩm theo 3 danh mục bắt buộc kèm banner gradient
│       ├── news.php               # Lưới 4 tin tức công nghệ mới nhất
│       └── trust-badges.php       # Pre-footer trust badges (cam kết chính hãng, giao nhanh)
│
├── woocommerce/                   # Override toàn bộ template mặc định của WooCommerce
│   ├── archive-product.php        # Trang danh mục sản phẩm (Sidebar lọc + Lưới sản phẩm)
│   ├── single-product.php         # Trang chi tiết sản phẩm (Gallery ảnh, biến thể, tabs, CTA)
│   ├── content-product.php        # Thẻ sản phẩm chuẩn CRO, 2-tier price responsive, rating sao
│   ├── cart/                      # Giao diện giỏ hàng 2 cột chuẩn hiện đại
│   ├── loop/                      # Các hook vòng lặp WooCommerce
│   └── global/                    # Thành phần toàn cục (breadcrumb, wrapper)
│
├── ai-docs/                       # Kho 13 tài liệu tri thức chuyên sâu dành cho AI Agents
│   ├── 01-architecture.md         # Template mapping, file structure, menu system
│   ├── 02-frontend-rules.md       # Tailwind, CSS, UI/UX, micro-interactions, menu hover
│   ├── 03-backend-wpcs.md         # WPCS, ACF patterns, WooCommerce, ERP integration
│   ├── 04-homepage.md             # Tri thức sâu từng component trang chủ
│   ├── 05-buglog.md               # Nhật ký 65 bugs & anti-patterns đã xử lý
│   ├── 06-infrastructure.md       # Docker, hạ tầng, môi trường thực thi
│   ├── 07-seo-json-ld-checklist.md# Checklist thẩm định Schema Org & Microdata
│   ├── 07-seo-pagespeed-audit.md  # Báo cáo tối ưu hóa PageSpeed Insights & Core Web Vitals
│   ├── 08-theme-standardization.md# Chuẩn hóa cấu trúc WordPress Theme (Refactoring)
│   ├── 09-theme-commercialization.md # Tiêu chuẩn thương mại hóa (i18n, TGMPA, Customizer)
│   ├── 10-commercialization-plan.md  # Kế hoạch & checklist phát hành thương mại
│   ├── 11-image-processing-sop.md # SOP 11: Chuẩn hóa 5 ảnh sản phẩm 1:1 WebP & inpainting
│   └── 12-content-writing-sop.md  # SOP 12: Viết bài chuẩn SEO & chuyển đổi CRO (Evals >= 75đ)
│
└── assets/                        # Tài nguyên tĩnh
    ├── css/                       # CSS dự phòng
    ├── js/                        # JavaScript tương tác menu, gallery, AJAX
    └── images/                    # Bộ nhận diện TongKhoMi (Logo WebP, Favicon, PWA icons)
```

---

## 3. Luồng Trang Chủ (Thứ Tự Bắt Buộc)

Trang chủ (`front-page.php`) phải tuân thủ nghiêm ngặt trình tự hiển thị sau:
```php
get_header();
get_template_part('template-parts/home/hero');
get_template_part('template-parts/home/media-services');
// #product-row-section: Lặp qua 3 danh mục bắt buộc
get_template_part('template-parts/home/news');
get_footer(); // footer.php đã tích hợp sẵn Pre-Footer Trust Badges
```

### 3 Danh Mục Sản Phẩm Bắt Buộc Trên Trang Chủ
| Tên Danh Mục | Slug | Bảng Màu Banner Gradient |
|--------------|------|-------------------------|
| **Tivi Xiaomi** | `tivi-xiaomi` | `from-xiaomi-yellow to-tertiary-fixed` |
| **Tủ Lạnh Xiaomi** | `tu-lanh-xiaomi` | `from-cyan-500 to-blue-600` |
| **Thiết Bị Gia Đình** | `thiet-bi-gia-dinh` | `from-amber-500 to-orange-600` |

---

## 4. Chuẩn Quy Cách Giao Diện & Frontend Rules

### 4.1. Chiều Rộng Khung Chứa Đồng Nhất (Uniform Container Width)
- Mọi trang (Header, Thân trang, Footer) sử dụng chung chuẩn: `max-w-[1440px] mx-auto px-4`.
- **Cấm**: Tuyệt đối không dùng class `container` của Tailwind kết hợp `max-w-[1440px]` vì class `container` sẽ ép giới hạn tại 1280px gây giật co màn hình.
- **Cấm**: Không dùng padding Header dị biệt (`px-12`, `px-20`) làm lệch mép Logo so với lưới nội dung.

### 4.2. Thẻ Sản Phẩm Responsive & Phân Tầng Giá 2 Cấp (2-Tier Price)
- Khung thẻ (`.product-card`): `w-full min-w-0 overflow-hidden p-2.5 sm:p-3` để co giãn chính xác 100% trong cột lưới grid di động (2 cột màn hình ~360-390px).
- **Phân tầng giá**:
  - **Mobile (< 640px)**: `flex-col items-start`. Dòng 1: Giá sale nổi bật (`text-sm font-black text-secondary whitespace-nowrap`). Dòng 2: Giá gốc gạch ngang kèm badge giảm (`text-[11px] text-slate-400 line-through` + badge `-26%`).
  - **Desktop (≥ 640px)**: `sm:flex-row sm:items-baseline gap-2`.
- **Chống tràn ngang**: Thẻ `<main id="primary">` sử dụng `overflow-x-clip` để chặn trượt ngang do ảnh hoặc bảng tràn viền trên di động mà không gây lỗi sticky cho Header.

### 4.3. Bộ Nhận Diện TongKhoMi.com
- Thư mục logo: `assets/images/tongkhomi-logo.webp` (933x298px, ~67KB lossless).
- Hiển thị qua thẻ `<picture>` nạp WebP ưu tiên, fallback PNG.
- Class quản lý: `.mi-brand-logo` (`height: 48px; max-height: 48px; width: auto; object-fit: contain;` trên Desktop, `42px` trên Mobile).

---

## 5. Tích Hợp SEO & Rank Math (`inc/seo.php`)

Theme xây dựng kiến trúc SEO theo triết lý **Không Trùng Lặp (Anti-Duplication)**:
1. **Làm giàu JSON-LD trực tiếp**: Filter `rank_math/json_ld` bổ sung thuộc tính thương hiệu `brand: Xiaomi`, `sku`, và `priceValidUntil` vào thực thể Product duy nhất của Rank Math, không in lặp schema riêng.
2. **Loại bỏ Schema trùng lặp**: Gỡ bỏ action `WC()->structured_data->output_structured_data` ở `wp_footer` của WooCommerce.
3. **Flat URL & Permalinks Beautification**: Loại bỏ toàn bộ tiền tố `/san-pham/`, `/product-category/`, `/category/`. Rank Math tự động chuyển hướng 301 bảo toàn liên kết cũ.
4. **Auto Image ALT**: Filter `wp_get_attachment_image_attributes` tự động gán thẻ `alt` thông minh từ tiêu đề sản phẩm/bài viết cho hình ảnh còn thiếu.
5. **Flush Rewrite Rules**: Tự động xử lý rewrite rules trên hook `init` (priority 99) khi có cập nhật, triệt tiêu lỗi 404 trang sitemap `/sitemap_index.xml`.

---

## 6. Biên Dịch TailwindCSS Bằng Docker

Tuyệt đối không chạy binary trực tiếp trên máy chủ host. Biên dịch CSS thông qua container WordPress:

```bash
docker exec -i michinhhang-wordpress-1 sh -c "cd /var/www/html/wp-content/themes/omni-commerce && ./tailwindcss-linux-x64 -i ./src/input.css -o ./style.css --minify"
```

Sau khi biên dịch, xóa Redis Object Cache và browser cache để kiểm tra thay đổi:
```bash
docker exec -i michinhhang-wordpress-1 wp cache flush --allow-root
```

---

## 7. Liên Kết Tri Thức Liên Quan

- **Sổ tay phát triển AI Theme**: [omni-commerce/AGENTS.md](AGENTS.md)
- **Bản vẽ kiến trúc kỹ thuật**: [omni-commerce/PROJECT_BLUEPRINT.md](PROJECT_BLUEPRINT.md)
- **Nhật ký 65 lỗi & cách giải quyết**: [ai-docs/05-buglog.md](ai-docs/05-buglog.md)
- **Quy chuẩn hình ảnh SOP 11**: [ai-docs/11-image-processing-sop.md](ai-docs/11-image-processing-sop.md)
- **Quy chuẩn viết bài SOP 12**: [ai-docs/12-content-writing-sop.md](ai-docs/12-content-writing-sop.md)
- **Tri thức tổng toàn hệ thống**: [Master AGENTS.md](../../AGENTS.md)
