# Kế hoạch động hóa Trang Chủ (Homepage Plan)

Danh sách các công việc cần thực hiện để biến các khu vực hardcode trên trang chủ thành các phần có thể quản trị dễ dàng qua ACF (Custom Fields/Options).
*Lưu ý: Tất cả các field, function, option đều sử dụng tiền tố `mi_`.*

## Phase 1 & 2: Checklist Công Việc

### 1. Hero Section (`template-parts/home/hero.php`)
- [x] **Tạo ACF Options Page:** Đăng ký trang Options "Cấu hình Trang Chủ" (nếu chưa có).
- [x] **Tính năng nổi bật (Cột trái):** Đăng ký ACF Repeater `mi_hero_features` (Icon, Tiêu đề chính, Tiêu đề phụ, Địa chỉ/Hotline) và render ra frontend.
- [x] **Hero Slider (Cột giữa):** Đăng ký ACF Repeater `mi_hero_slider` (Hình ảnh, Link) và render ra frontend.
- [x] **Tab Menu (Dưới Slider):** Đăng ký ACF Repeater `mi_hero_tabs` (Tiêu đề, Link, Trạng thái Active) và render.
- [x] **Banner Quảng cáo & Tin tức nhanh (Cột phải):** Đăng ký ACF Group `mi_hero_sidebar` (Banner Image, Banner Title, Bài viết nổi bật chọn thủ công) và render.

### 2. Videos & Value Props (`template-parts/home/videos.php`)
- [x] **Big Sale Banner:** Đăng ký field text `mi_videos_sale_banner` để quản lý dòng chữ chạy "BIG SALE...".
- [x] **Value Props (Cam kết):** Đăng ký ACF Repeater `mi_value_props` (Icon, Tiêu đề, Mô tả ngắn) để thay thế 3 block cứng ("Mua hàng dễ dàng", "Ship code toàn quốc", "Trả góp 0%").

### 3. Product Row (`template-parts/home/product-row.php`)
- [x] **Cấu hình Product Row:** Tạo nhóm field `mi_home_product_row` (Tiêu đề danh mục, Link "Xem tất cả", Banner dọc bên trái bao gồm: Tiêu đề lớn, Mô tả, Hình ảnh, Text nút, Link nút).
- [x] **Tích hợp Frontend:** Sửa file `product-row.php` lấy dữ liệu động từ ACF Options thay cho HTML cứng. (Sau này có thể nâng cấp thành Repeater cho nhiều row).

### 4. News Section (`template-parts/home/news.php`)
- [x] **Cấu hình News:** Thêm field `mi_home_news_title` cho tiêu đề "Tin tức nổi bật".
- [x] **Tích hợp Frontend:** Thay thế tiêu đề cứng trong `news.php`.

### 5. Trust Badges (`template-parts/home/trust-badges.php`)
- [x] **Cấu hình Badges:** Đăng ký ACF Repeater `mi_trust_badges` (Hình ảnh/Icon, Tên đối tác/Cam kết).
- [x] **Xây dựng Frontend:** Code cấu trúc UI (dùng Tailwind) trong `trust-badges.php` và hiển thị dữ liệu động.

---
**Quy tắc chung khi Execute:**
1. Code Backend (đăng ký ACF qua PHP hoặc JSON).
2. Code Frontend (sửa file template, lấy dữ liệu qua `get_field`/`get_option`).
3. Giữ nguyên 100% class Tailwind CSS.
4. Đánh dấu `[x]` vào từng mục sau khi hoàn thành.
