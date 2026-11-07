# TIÊU CHUẨN XỬ LÝ HÌNH ẢNH SẢN PHẨM & BÀI VIẾT (SOP 11)
> Quy chuẩn đồ họa, đóng dấu bảo vệ bản quyền (Watermarking) và tối ưu hóa hiệu năng ảnh đa nền tảng.

## 1. Nguyên Tắc Cốt Lõi (Authenticity First)
1. **Ưu tiên ảnh chụp thực tế:** Sử dụng hình ảnh thực tế từ nhà sản xuất chính hãng, showroom hoặc ảnh tư liệu đã được cấp quyền, tránh dùng ảnh AI giả lập thiếu chân thực.
2. **Làm sạch đối thủ (Visual Hygiene):** Tẩy sạch 100% watermark, hotline, logo của các đối thủ khác bằng kỹ thuật clone hoặc gradient interpolation trước khi xuất bản.

## 2. Quy Cách Đóng Mộc Logo Chìm (Watermarking)
- **Tỷ lệ**: Chiều ngang logo `{brand_logo}` chiếm 20% - 25% chiều rộng ảnh.
- **Vị trí**: Góc trên bên trái (Top-Left) hoặc góc trên bên phải (Top-Right), cách mép ảnh margin 4%.
- **Độ trong suốt (Alpha Blending)**:
  - Nền tối: Sử dụng logo phiên bản sáng màu `#FFFFFF` (opacity 85%) kết hợp màu nhận diện chính `{brand_primary_color}` (opacity 90%), có bóng đổ mềm (drop shadow).
  - Nền trắng / Studio: Sử dụng logo xám đậm `#2D3748` kết hợp màu nhận diện `{brand_primary_color}` opacity 85%.
- **Cấm kỵ**: Không dán khung hộp sticker dày cộp, không để logo đè lên chi tiết trọng tâm của sản phẩm.

## 3. Tỷ Lệ & Kích Thước Chuẩn Responsive
- **Ảnh sản phẩm E-Commerce (Vuông 1:1)**:
  - Master HD: `1000 x 1000 px` (< 250 KB)
  - Single Product: `600 x 600 px`
  - Catalog / Archive: `300 x 300 px`
  - Thumbnail: `150 x 150 px`
- **Ảnh bài viết tin tức & Hero Banner (Tỷ lệ 16:9 hoặc 3:2)**:
  - Featured Image: `1200 x 675 px` (16:9) hoặc `1200 x 800 px` (3:2).
  - Tối ưu định dạng WebP / JPEG nén, thẻ `alt` chứa từ khóa mô tả ngữ cảnh chính xác.

## 4. Quy Tắc Đặt Tên File SEO (Slug Convention)
- Cấu trúc chuẩn: `{loai-san-pham}-{ten-san-pham}-{model}-{nam}-{brand-slug}.webp`
- Ví dụ: `smart-tivi-4k-uhd-55-inch-2026-tongkhomi.webp`
- Không đặt tên vô nghĩa như `image_1.jpg`, `untitled.png`.
