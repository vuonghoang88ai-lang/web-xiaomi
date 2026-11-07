# Checklist Nghiệm Thu Dữ Liệu Có Cấu Trúc (JSON-LD)

Tài liệu rà soát và kiểm toán Technical SEO cho dự án michinhhang, tập trung vào việc triển khai chính xác Schema Markup dạng JSON-LD.

## 1. Schema Product (WooCommerce)
- [ ] Khai báo đầy đủ các trường bắt buộc: `name`, `image`, `offers`, `price`, `priceCurrency`, `availability`.
- [ ] Khai báo các trường khuyến nghị để hiển thị phong phú hơn: `aggregateRating`, `brand`, `sku` hoặc `gtin`.
- [ ] Dữ liệu giá (`price`) phải là số nguyên hoặc thập phân thuần túy, không chứa ký tự tiền tệ.
- [ ] Tình trạng kho (`availability`) tuân thủ chuẩn Schema.org (vd: `https://schema.org/InStock`).

**Code mẫu JSON-LD Product:**
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
  },
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "4.8",
    "reviewCount": "125"
  }
}
</script>
```

## 2. Schema Article (Tin tức / Hướng dẫn)
- [ ] Khai báo trường `headline` rõ ràng, không quá dài (dưới 110 ký tự).
- [ ] Trường `image` phải chứa URL ảnh đại diện chất lượng cao của bài viết.
- [ ] Định dạng ngày tháng cho `datePublished` và `dateModified` phải chuẩn ISO 8601.
- [ ] Trường `author` phải có `name` (tên tác giả hoặc admin).
- [ ] Trường `publisher` khai báo thông tin tổ chức/website kèm logo.

**Code mẫu JSON-LD Article:**
```json
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "Article",
  "headline": "Hướng dẫn kết nối Tivi Xiaomi với điện thoại",
  "image": "https://michinhhang.com/wp-content/uploads/2026/09/hd-tivi.jpg",
  "datePublished": "2026-09-26T08:00:00+07:00",
  "dateModified": "2026-09-26T09:00:00+07:00",
  "author": {
    "@type": "Person",
    "name": "Admin Mi Chính Hãng"
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

## 3. Schema BreadcrumbList
- [ ] Khai báo mảng `itemListElement` chứa các mục (ListItem).
- [ ] Thuộc tính `position` phải tăng dần tuần tự (1, 2, 3...) tương ứng với cấp bậc thư mục.
- [ ] Mỗi mục phải chứa `name` và `item` (URL của cấp đó).
- [ ] Cấu trúc chuỗi liên kết phải liền mạch, bắt đầu từ Trang chủ và kết thúc ở danh mục hiện tại hoặc trang hiện tại.

**Code mẫu JSON-LD BreadcrumbList:**
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
    },
    {
      "@type": "ListItem",
      "position": 3,
      "name": "Tivi Xiaomi 55 inch",
      "item": "https://michinhhang.com/tivi-xiaomi/55-inch"
    }
  ]
}
</script>
```

## 4. Công Cụ Test & Kiểm Toán Schema
Để nghiệm thu dữ liệu cấu trúc thực tế trên dự án, hãy sử dụng các công cụ sau:
1. **[Rich Results Test (Google)](https://search.google.com/test/rich-results)**: Công cụ chính thức của Google để xem liệu Schema có đạt đủ điều kiện hiển thị kết quả nhiều định dạng không.
2. **[Schema Markup Validator (Schema.org)](https://validator.schema.org/)**: Dùng để kiểm tra cấu trúc cú pháp chung và cảnh báo các trường không hợp lệ hoặc thiếu sót theo tiêu chuẩn của Schema.org.

## 5. Kiến Trúc Tương Thích & Chống Trùng Lặp Với Plugin SEO (Rank Math)
Để đảm bảo website chuẩn SEO cao cấp mà không gây xung đột hay trùng lặp thẻ meta/schema với plugin SEO (`seo-by-rank-math`), theme triển khai module `inc/seo.php` theo các nguyên tắc:

1. **Không in thẻ script JSON-LD hay meta tags thủ công trong header:**
   - Tất cả dữ liệu Schema Product được tích hợp và làm giàu trực tiếp qua bộ lọc `rank_math/json_ld` (bổ sung `brand` Xiaomi và `sku` vào `richSnippet`). Tránh việc tạo ra nhiều thẻ `<script type="application/ld+json">` chứa các Product entity trùng nhau trên cùng 1 trang.
2. **Loại bỏ dữ liệu có cấu trúc trùng lặp từ WooCommerce:**
   - Gỡ bỏ hook mặc định `output_structured_data` ở `wp_footer` của WooCommerce nhằm tránh in lặp BreadcrumbList ở cuối trang.
3. **Fallback Meta Description thông minh:**
   - Sử dụng bộ lọc `rank_math/frontend/description` để tự động điền Meta Description chuyên nghiệp cho Trang chủ khi admin chưa nhập trong Rank Math, tự động đồng bộ sang cả Open Graph (`og:description`) và Twitter Card.
4. **Tự động hóa Image Alt (Image SEO):**
   - Sử dụng hook `wp_get_attachment_image_attributes` để tự động điền `alt` bằng Media Title hoặc Post Title cho bất kỳ ảnh nào bị bỏ trống `alt=""`.
5. **Lưu ý triển khai môi trường Rank Math:**
   - Khi cài mới plugin Rank Math, nếu chưa kết nối tài khoản hoặc chưa nhấn bỏ qua Wizard, Rank Math sẽ chặn frontend (`registration->invalid = true`). Cần đảm bảo option `rank_math_registration_skip` có giá trị `1` trong `wp_options` hoặc hằng số `RANK_MATH_REGISTRATION_SKIP` được khai báo.
6. **Cơ chế nhường quyền Schema của Plugin ERP nội bộ:**
   - Trong `class-mi-erp-seo-schema.php`, hàm `render_schema()` tự động kiểm tra `defined('RANK_MATH_VERSION') || function_exists('rank_math')` để return sớm, ngăn chặn hoàn toàn việc in thẻ `<script type="application/ld+json">` chứa `Article` và `BreadcrumbList` độc lập khi Rank Math đã xử lý `@graph`.
7. **Tự động kích hoạt Sơ đồ trang web XML (`/sitemap_index.xml`):**
   - Theme tự động flush rewrite rules trong hook `init` (priority 99) khi phát hiện option `rank_math_flush_rewrite`, đảm bảo URL `/sitemap_index.xml` và các sub-sitemap trả về HTTP 200 OK ngay mà không cần truy cập `wp-admin`.
8. **Chuẩn hóa Phân cấp Heading (`<h1>` - `<h6>`) cho Danh mục:**
   - Đảm bảo Document Outline hợp lệ: Widget lọc và sidebar dùng thẻ `<h3>`, bảo toàn vị trí duy nhất của `<h1>` cho tên danh mục và `<h2>` cho đoạn mô tả SEO.

