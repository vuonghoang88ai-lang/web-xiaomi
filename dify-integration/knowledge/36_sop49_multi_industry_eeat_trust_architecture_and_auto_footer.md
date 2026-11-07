# SOP 49: BỘ TRANG TÍN NHIỆM E-E-A-T ĐA NGÀNH NGHỀ & TỰ ĐỘNG GẮN CHÂN TRANG (MULTI-INDUSTRY E-E-A-T ARCHITECTURE & AUTONOMOUS FOOTER BINDING)

## 1. NGUYÊN TẮC CỐT LÕI (SOP 30: ZERO HARDCODING NGÀNH NGHỀ)
- **Triệt tiêu tư duy Fix cứng bán hàng:** Tuyệt đối không áp đặt các trang "Bảo hành 1 đổi 1", "Đổi trả hoàn tiền", "Vận chuyển lắp đặt" cho các website nội dung số, đọc truyện chữ giải trí, tin tức hoặc dịch vụ tư vấn.
- **Mô hình hóa Archetype Ngữ nghĩa Đa ngành:** Sử dụng ma trận trọng số ngữ cảnh để tự động nhận diện chính xác 3 phân loại thực thể website:
  1. `content_media`: Nền tảng đọc truyện, tiểu thuyết, blog, tin tức, nội dung giải trí số.
  2. `service_health_b2b`: Phòng khám, nha khoa, bác sĩ, tư vấn, giáo dục, luật, SaaS, agency.
  3. `commerce_retail`: Thương mại điện tử, kho hàng, phân phối hàng hóa, thiết bị, bán lẻ.

## 2. MA TRẬN 5 TRANG TÍN NHIỆM E-E-A-T THEO TỪNG ARCHETYPE

### 1. Archetype Nội Dung Số & Đọc Truyện Giải Trí (`content_media` - Ví dụ: truyen360.top)
- `dieu-khoan-su-dung`: Điều Khoản Sử Dụng & Nội Quy Nền Tảng (Quy chế hoạt động, nội quy cộng đồng độc giả, bình luận văn minh).
- `chinh-sach-bao-mat`: Chính Sách Bảo Mật & Quyền Riêng Tư Độc Giả (Bảo mật tài khoản, cookie lưu tiến độ đọc).
- `ban-quyen-dmca`: Tuyên Bố Bản Quyền & Miễn Trừ Trách Nhiệm DMCA (Tôn trọng quyền tác giả, quy trình tiếp nhận và gỡ bỏ vi phạm trong 24-48h).
- `gioi-thieu`: Về Chúng Tôi - Nền Tảng Đọc Truyện Chữ Chuẩn E-E-A-T (Sứ mệnh không gian đọc trực tuyến mượt mà, phi thương mại).
- `lien-he`: Liên Hệ Ban Biên Tập & Hộp Thư Báo Lỗi (Tiếp nhận phản ánh, báo lỗi chương truyện, liên hệ ban quản trị).

### 2. Archetype Dịch Vụ, Y Tế & Chuyên Môn (`service_health_b2b` - Ví dụ: nhakhoa...)
- `dieu-khoan-dich-vu`: Điều Khoản & Quy Trình Cung Cấp Dịch Vụ.
- `chinh-sach-bao-mat`: Chính Sách Bảo Mật Hồ Sơ & Thông Tin Khách Hàng.
- `quy-chuan-chuyen-mon`: Cam Kết Tiêu Chuẩn & Năng Lực Chuyên Môn Y Khoa / Chuyên Gia.
- `gioi-thieu`: Về Chúng Tôi - Đội Ngũ Chuyên Gia Chuẩn E-E-A-T.
- `lien-he`: Thông Tin Liên Hệ & Đặt Lịch Tư Vấn Trực Tiếp.

### 3. Archetype Thương Mại Điện Tử & Bán Lẻ (`commerce_retail` - Ví dụ: tongkhomi.com)
- `chinh-sach-bao-hanh`: Chính Sách Bảo Hành & Đổi Mới Chuẩn Hãng.
- `chinh-sach-doi-tra`: Chính Sách Đổi Trả & Hoàn Tiền Minh Bạch.
- `chinh-sach-van-chuyen`: Chính Sách Vận Chuyển & Giao Hàng Toàn Quốc.
- `gioi-thieu`: Về Chúng Tôi - Đơn Vị Phân Phối Chuẩn E-E-A-T.
- `lien-he`: Thông Tin Liên Hệ & Hệ Thống Kho Hàng / Cửa Hàng.

## 3. CƠ CHẾ HIỂN THỊ CHÂN TRANG TỰ ĐỘNG (AUTONOMOUS FOOTER BINDING)
Triệt tiêu hoàn toàn hướng dẫn thủ công "Vào Appearance ➔ Menu ➔ Footer Menu":
1. **Theme Location Binding:** Tự động tạo menu `Omni E-E-A-T Footer Menu` và map trực tiếp vào các vị trí nav menu chân trang phổ biến (`footer`, `footer-menu`, `secondary`, `bottom`).
2. **Universal Footer Trust Bar (`wp_footer`):** Hook trực tiếp vào đáy giao diện front-end hiển thị thanh Trust Bar tinh tế chứa đầy đủ 5 liên kết tín nhiệm chuẩn responsive, đảm bảo độc giả và Googlebot luôn nhìn thấy và click được ngay lập tức trên mọi theme WordPress.

