# QUY CHUẨN VIẾT BÀI CHUẨN SEO & CRO (SOP 12)
> Tiêu chuẩn biên tập nội dung số, kiến trúc Heading chuyển đổi và kiểm soát E-E-A-T đa ngành hàng.

## 1. Định Vị Thương Hiệu & Làm Sạch Dữ Liệu (Brand Hygiene)
- **Tên thương hiệu chính thức**: `{brand_name}` (Truy xuất tự động từ cấu hình dự án).
- **Hotline bán hàng & CSKH**: `{brand_hotline}`.
- **Hệ thống cơ sở / Showroom / Kho hàng**: `{brand_addresses}`.
- **Quy tắc làm sạch đối thủ (Competitor Hygiene)**: Tuyệt đối không để sót tên miền, hotline, watermark hay địa chỉ của đối thủ cạnh tranh trên SERP trong nội dung bài viết. Mọi trích dẫn phải quy về thực thể thương hiệu `{brand_name}`.

## 2. Cấu Trúc Ngữ Nghĩa Heading Chuẩn Google
- Thẻ `<h1>` do Theme tự động render (không viết thêm thẻ H1 trong nội dung bài).
- Thẻ `<h2>`: Tối thiểu 3 - 5 thẻ H2 theo cấu trúc logic:
  - `<h2> Mở đầu & Tổng quan giá trị cốt lõi`
  - `<h2> Thiết kế & Không gian hiển thị (hoặc Công năng thực tế)`
  - `<h2> Hiệu năng phần cứng & Công nghệ độc quyền`
  - `<h2> Bảng so sánh thông số kỹ thuật & Bảng giá`
  - `<h2> Vì sao nên mua tại {brand_name} & Cam kết bảo hành, hậu mãi`
- Thẻ `<h3>`: Triển khai chi tiết các khía cạnh nhỏ dưới từng H2.
- **Quy định kỹ thuật**:
  - Không bọc ảnh trong thẻ Heading. Ảnh phải đặt trong thẻ `<figure>` riêng.
  - Không sử dụng inline CSS font-family rác.

## 3. Chuyển Đổi Tính Năng (Feature) Thành Lợi Ích (Benefit)
Viết bài cho sản phẩm gia dụng và điện tử cao cấp đòi hỏi diễn giải lợi ích thực tế:
- **Tần số quét 120Hz/144Hz + MEMC**: Hình ảnh mượt mà tuyệt đối, loại bỏ bóng ma giật hình khi xem bóng đá Ngoại Hạng Anh hay chơi game tốc độ cao.
- **Viền kim loại tràn viền > 97%**: Tác phẩm nghệ thuật sang trọng trong phòng khách, nâng tầm đẳng cấp không gian sống.
- **Hệ điều hành Android TV / HyperOS tiếng Việt**: Giao diện thuần Việt dễ dùng cho cả người lớn tuổi và trẻ nhỏ, tích hợp giọng nói tiếng Việt một chạm.
- **Âm thanh Dolby Audio / DTS**: Âm thanh vòm sống động như tại rạp chiếu phim mà không cần tốn tiền mua thêm loa ngoài.
- **Lực hút Robot 6000Pa - 8000Pa**: Hút sạch lông thú cưng, bụi mịn trong khe gạch và thảm sâu.

## 4. Bắt Buộc 4 Liên Kết Chính Sách Nội Bộ (Internal Policy Links)
Mọi bài viết xuất bản bắt buộc phải lồng ghép tự nhiên cả 4 liên kết sau:
1. **Chính sách bảo hành**: Anchor text *"Bảo hành chính hãng 12 - 24 tháng"*, URL `/chinh-sach-bao-hanh/`.
2. **Chính sách đổi trả**: Anchor text *"Lỗi 1 đổi 1 trong 15 - 30 ngày"*, URL `/chinh-sach-doi-tra/`.
3. **Tra cứu bảo hành điện tử**: Anchor text *"Tra cứu bảo hành online theo Serial"*, URL `/tra-cuu-bao-hanh/`.
4. **Chính sách vận chuyển**: Anchor text *"Giao hàng siêu tốc 60 phút nội thành"*, URL `/chinh-sach-van-chuyen/`.

## 5. Danh Sách 8 Cụm Từ Sáo Rỗng Cấm Tuyệt Đối (Anti-AI Clichés)
Hệ thống kiểm duyệt tự động sẽ trừ điểm nặng nếu bài viết chứa các cụm từ sau:
1. `trong thời đại số`
2. `trong thời đại công nghệ số`
3. `ngày nay,`
4. `như chúng ta đã biết`
5. `không thể phủ nhận rằng`
6. `tóm lại,`
7. `kết luận lại,`
8. `bước vào kỷ nguyên`

## 6. Tiêu Chuẩn Thẩm Định Điểm Số (QA Evaluation)
- Bài viết đạt chuẩn khi độ dài $\ge 1200$ từ ($100$ điểm), tối thiểu 800 từ.
- Công thức điểm: $\text{Overall} = (\text{SOP} \times 0.30) + (\text{Policy} \times 0.25) + (\text{CRO} \times 0.25) + (\text{Brand} \times 0.20)$.
- **Ngưỡng phê duyệt tự động**: $\ge 75 / 100$ điểm.
