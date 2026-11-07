# Master Audit Checklist: Technical SEO & PageSpeed

Tài liệu rà soát và kiểm toán tổng thể cho dự án TMĐT michinhhang (WordPress/WooCommerce, PHP thuần, TailwindCSS, Docker).

## 1. Technical SEO & Crawlability
*Công cụ kiểm thử: Google Search Console, Screaming Frog, Custom SEO Crawler*

- [x] `robots.txt`: Khai báo chính xác, chặn các đường dẫn không cần thiết (vd: `/wp-admin/`, `/cart/`) và cấp quyền crawl cho các trang sản phẩm/bài viết.
- [x] `sitemap_index.xml`: Tự động generate Sitemap hợp lệ (HTTP 200) gồm đầy đủ 5 sub-sitemaps (`post`, `page`, `product`, `category`, `product_cat`), được khai báo trong robots.txt.
- [x] **Thẻ Canonical**: Tất cả các trang đều sinh thẻ `<link rel="canonical">` để chống trùng lặp nội dung do tham số URL hoặc phân trang.
- [x] **Cấu trúc URL Phẳng (Flat URL)**: Chuẩn SEO, URL thân thiện, loại bỏ hoàn toàn tiền tố `/san-pham/`, `/product-category/`, `/category/`.
- [x] **Xử lý Flat URL Category**: Khắc phục triệt để lỗi 404 cho URL chuyên mục tin tức (`/chua-phan-loai/`) qua filter `request` kiểm tra cả `product_cat` và `category`.
- [x] **Audit Liên Kết Nội Bộ (Internal Links)**: 0 liên kết chết (404 = 0), 0 liên kết rác (`href="#"` và `href=""` = 0) trên toàn bộ 44 trang live.

## 2. Semantic HTML & On-page
*Công cụ kiểm thử: W3C Validator, Lighthouse (SEO panel)*

- [x] **Thẻ ngữ nghĩa**: Bắt buộc sử dụng `<main id="primary">` bao bọc nội dung cốt lõi, `<article>` cho chi tiết nội dung/sản phẩm và `<nav>` cho các thanh điều hướng.
- [x] **Phân cấp Heading (H1-H6)**: 
  - Chỉ duy nhất một thẻ `<h1>` chứa từ khóa chính trên mỗi trang.
  - Tự động hạ bậc các thẻ `<h1>` trong body bài viết thành `<h2>` qua filter `the_content` (singular) để ngăn ngừa lỗi trùng lặp thẻ H1 từ trình soạn thảo.
- [x] **Thẻ Meta Động**: `<title>` và `<meta name="description">` sinh động 100% qua SSR thông qua Rank Math kết hợp bộ lọc Fallback tự động thông minh.
- [x] **Tối ưu hình ảnh (100% Image SEO)**: Tự động bổ sung `alt="[Tiêu đề trang/sản phẩm]"` cho toàn bộ thẻ `<img>` thô trong nội dung thiếu alt qua filter `the_content` kết hợp `wp_get_attachment_image_attributes`.

## 3. Dữ liệu có cấu trúc (JSON-LD)
*Công cụ kiểm thử: Google Rich Results Test, Schema Markup Validator*

- [x] **Product (WooCommerce)**: Khai báo đủ các trường bắt buộc (`name`, `image`, `offers`, `price`, `priceCurrency`, `availability`) và các trường khuyến nghị (`aggregateRating`, `brand`, `sku`).
- [x] **Organization & WebSite Schema**: Tích hợp đồng bộ trong Rank Math và ERP.
- [x] **Cơ chế nhường quyền Schema**: ERP plugin tự động nhường quyền cho Rank Math khi phát hiện `RANK_MATH_VERSION` để chống lỗi trùng lặp schema trên Google Search Console.

## 4. Google PageSpeed & Core Web Vitals
*Công cụ kiểm thử: Google PageSpeed Insights, Chrome DevTools (Lighthouse)*

- [x] **LCP (Largest Contentful Paint)**: Banner chính nạp trực tiếp WebP cục bộ từ `/wp-content/uploads/banners/`, gán `fetchpriority="high"`.
- [x] **INP (Interaction to Next Paint)**: Tối ưu JS AJAX thêm giỏ hàng, tra cứu bảo hành phản hồi tức thì dưới 200ms.
- [x] **CLS (Cumulative Layout Shift)**: Cấp trước kích thước `width`/`height` và aspect ratio 16:9 cho hình ảnh banner, logo và thumbnail.
- [x] **Tối ưu Hình ảnh**:
  - Tự chủ 100% media assets nội bộ (Rule 4.12), loại bỏ hoàn toàn placeholder ngoài (`placehold.co`, `lh3.googleusercontent.com`).
  - Nạp WebP với fallback PNG.
- [x] **Tối ưu Server/Cache**:
  - Tích hợp **Redis Object Cache** (`redis:7-alpine`) và OPcache PHP 8.2 trên môi trường Docker.

---

## 5. Quy Chuẩn Tương Thích Giao Diện Mobile Full Màn Hình (Mobile Full-Width Standards)

- [x] **Bảo Vệ Tràn Ngang Toàn Cục**:
  - `html, body { max-width: 100% !important; overflow-x: clip !important; }`
  - Bọc toàn bộ bảng biểu với `overflow-x: auto !important; -webkit-overflow-scrolling: touch !important;`
- [x] **Triệt Tiêu Anti-pattern Lề Kép (Double Padding)**:
  - Container ngoài: `px-2.5 sm:px-4`
  - Card/Khung nội dung: `p-4 sm:p-6 md:p-10`
  - Giúp nội dung và hình ảnh bài viết trên màn hình điện thoại trải rộng full-width sát mép, tăng 25% diện tích hiển thị văn bản so với lề cũ.
- [x] **Lưới 2 Cột Sản Phẩm Tràn Viền Mobile**:
  - `grid-cols-2 gap-2.5 sm:gap-4` trên mobile, tối đa hóa diện tích hiển thị hình ảnh và thông số giá, mang lại trải nghiệm tương đương ứng dụng native.
- [x] **Thanh Điều Hướng Chuyên Mục Cuộn Ngang**:
  - Thay thế `hidden md:flex` bằng `overflow-x-auto scrollbar-hide w-full` trên mobile, cho phép vuốt ngang chọn chuyên mục tức thì.
- [x] **Bảo Toàn 100% Bố Cục Desktop**:
  - Mọi tinh chỉnh mobile đều sử dụng tiền tố breakpoint Tailwind chuẩn (`sm:`, `md:`, `lg:`), giữ nguyên 100% container `max-w-[1440px] mx-auto px-4` trên Desktop.

