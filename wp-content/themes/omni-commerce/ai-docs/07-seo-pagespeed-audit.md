# Master Audit Checklist: Technical SEO & PageSpeed

Tài liệu rà soát và kiểm toán tổng thể cho dự án TMĐT michinhhang (WordPress/WooCommerce, PHP thuần, TailwindCSS, Docker).

## 1. Technical SEO & Crawlability
*Công cụ kiểm thử: Google Search Console, Screaming Frog*

- [ ] `robots.txt`: Khai báo chính xác, chặn các đường dẫn không cần thiết (vd: `/wp-admin/`, `/cart/`) và cấp quyền crawl cho các trang sản phẩm/bài viết.
- [ ] `sitemap.xml`: Tự động generate Sitemap hợp lệ chứa các post type chính, được update định kỳ, và khai báo trong file `robots.txt`.
- [ ] **Thẻ Canonical**: Tất cả các trang đều sinh thẻ `<link rel="canonical">` để chống trùng lặp nội dung do tham số URL hoặc phân trang.
  ```html
  <link rel="canonical" href="https://michinhhang.com/url-chinh-thuc" />
  ```
- [ ] **Cấu trúc URL**: Chuẩn SEO, URL thân thiện, phân cấp rõ ràng (không chứa tham số truy vấn lạ cho các trang đích tĩnh).

## 2. Semantic HTML & On-page
*Công cụ kiểm thử: W3C Validator, Lighthouse (SEO panel)*

- [ ] **Thẻ ngữ nghĩa**: Bắt buộc sử dụng `<main>` bao bọc nội dung cốt lõi, `<article>` cho chi tiết nội dung/sản phẩm và `<nav>` cho các thanh điều hướng.
- [ ] **Phân cấp Heading (H1-H6)**: 
  - Chỉ duy nhất một thẻ `<h1>` chứa từ khóa chính trên mỗi trang.
  - Các thẻ `<h2>`, `<h3>` được sử dụng để phân chia nội dung tuần tự, không nhảy cóc (vd: từ H1 sang H3).
- [ ] **Thẻ Meta Động**: `<title>` và `<meta name="description">` được sinh động qua SSR thông qua PHP cho tất cả Custom Post Types.
  ```php
  <title><?php echo esc_html( $seo_title ); ?></title>
  <meta name="description" content="<?php echo esc_attr( $seo_description ); ?>">
  ```
- [ ] **Tối ưu hình ảnh**: Tất cả các thẻ `<img>` đều có thuộc tính `alt` mô tả trực quan và chứa từ khóa chính (nếu phù hợp).

## 3. Dữ liệu có cấu trúc (JSON-LD)
*Công cụ kiểm thử: Google Rich Results Test, Schema Markup Validator*

- [ ] **Product (WooCommerce)**: Khai báo đủ các trường bắt buộc (`name`, `image`, `offers`, `price`, `priceCurrency`, `availability`) và các trường khuyến nghị (`aggregateRating`, `brand`, `sku/gtin`).
  ```json
  <script type="application/ld+json">
  {
    "@context": "https://schema.org/",
    "@type": "Product",
    "name": "Tủ Lạnh Xiaomi Siêu Tốc 500L",
    "image": "https://michinhhang.com/wp-content/uploads/2026/09/tu-lanh-xiaomi.jpg",
    "sku": "XIAOMI-TL500",
    "brand": {
      "@type": "Brand",
      "name": "Xiaomi"
    },
    "offers": {
      "@type": "Offer",
      "url": "https://michinhhang.com/tu-lanh-xiaomi-500l",
      "priceCurrency": "VND",
      "price": "15000000",
      "availability": "https://schema.org/InStock"
    }
  }
  </script>
  ```

- [ ] **Article (Tin tức/Hướng dẫn)**: Khai báo đủ `headline`, `image`, `datePublished`, `dateModified`, `author`, `publisher`.
  ```json
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "Article",
    "headline": "Hướng dẫn kết nối Tivi Xiaomi",
    "image": "https://michinhhang.com/hd-tivi.jpg",
    "datePublished": "2026-09-26T08:00:00+07:00",
    "dateModified": "2026-09-26T09:00:00+07:00",
    "author": {
      "@type": "Person",
      "name": "Admin"
    },
    "publisher": {
      "@type": "Organization",
      "name": "Mi Chính Hãng",
      "logo": {
        "@type": "ImageObject",
        "url": "https://michinhhang.com/logo.png"
      }
    }
  }
  </script>
  ```

- [ ] **BreadcrumbList**: Khai báo tuần tự `position`, `name`, `item` từ trang chủ đến trang hiện tại liền mạch.
  ```json
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    "itemListElement": [
      {
        "@type": "ListItem",
        "position": 1,
        "name": "Trang chủ",
        "item": "https://michinhhang.com/"
      },
      {
        "@type": "ListItem",
        "position": 2,
        "name": "Tivi Xiaomi",
        "item": "https://michinhhang.com/tivi-xiaomi/"
      }
    ]
  }
  </script>
  ```

## 4. Google PageSpeed & Core Web Vitals
*Công cụ kiểm thử: Google PageSpeed Insights, Chrome DevTools (Lighthouse)*

- [ ] **LCP (Largest Contentful Paint)**: Tối ưu tải nội dung chính để hiển thị cực nhanh (< 2.5s).
- [ ] **INP (Interaction to Next Paint)**: Giảm bớt các task JS dài hạn, đảm bảo độ trễ phản hồi khi người dùng tương tác thấp (< 200ms).
- [ ] **CLS (Cumulative Layout Shift)**: Chống giật cục giao diện bằng cách cấp trước kích thước `width`/`height` cho toàn bộ hình ảnh và quảng cáo/thành phần động.
- [ ] **Tối ưu Hình ảnh**:
  - Tích hợp **Lazyload** (thêm `loading="lazy"`) cho ảnh ngoài vùng nhìn đầu tiên (below the fold) và iframes.
  - Chuyển đổi định dạng hình ảnh sang **WebP** hoặc **AVIF**.
- [ ] **Xử lý tài nguyên chặn hiển thị (Render-blocking)**: 
  - Trì hoãn hoặc tải bất đồng bộ (`defer`/`async`) các tệp JS không cần thiết.
  - Sử dụng Critical CSS, hoặc đẩy file CSS xuống đúng vùng cần tải.
- [ ] **Tối ưu Server/Cache**:
  - Tích hợp **Redis** / **OPcache** tại cấp server/Docker để giảm độ trễ TTFB (Time to First Byte).
