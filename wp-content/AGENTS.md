# Trạm Điều Phối AI Tổng — Dự án Michinhhang

Đây là file tri thức tổng (Master Knowledge File) cho toàn bộ dự án `michinhhang`. File này có nhiệm vụ điều phối và liên kết tới các file tri thức cụ thể của từng thành phần trong hệ thống, bao gồm Theme và Plugin ERP.

## 1. Thông tin tổng quan dự án
- **Tên dự án:** Michinhhang
- **Loại hình:** Hệ thống TMĐT phân phối thiết bị Xiaomi chính hãng + Quản trị ERP nội bộ.
- **Nền tảng:** WordPress + WooCommerce + MariaDB + Redis.
- **Hạ tầng:** 100% Docker (không chạy trực tiếp trên host).

## 2. Liên kết đến các file tri thức thành phần

Khi cần phát triển hoặc sửa đổi các thành phần cụ thể, AI **BẮT BUỘC** phải đọc và tuân thủ các quy tắc trong các file tri thức tương ứng dưới đây:

### 2.1. Frontend & Giao diện (Theme)
Chịu trách nhiệm hiển thị giao diện người dùng (TMĐT), tích hợp TailwindCSS, quản lý ACF, và logic hiển thị frontend.
- **File tri thức Theme:** [viomi-theme/AGENTS.md](./themes/viomi-theme/AGENTS.md)
- **Vị trí:** `wp-content/themes/viomi-theme/`
- **Các quy tắc cốt lõi:** YAGNI, Classic Theme, Tailwind first, ACF dynamic, Escape output.

### 2.2. Backend & Nghiệp vụ (Plugin ERP)
Hệ thống ERP độc lập xử lý kho hàng, POS, CRM, tài chính, mua hàng, nhân sự và sản xuất.
- **File tri thức Plugin ERP:** [viomi-erp-system/AGENTS.md](./plugins/viomi-erp-system/AGENTS.md)
- **Vị trí:** `wp-content/plugins/viomi-erp-system/`
- **Các quy tắc cốt lõi:** Độc lập logic, Thiết lập và build TailwindCSS độc lập (không share với theme), Bảo mật dữ liệu (prepare, nonce), Tối ưu truy vấn (HPOS).

### 2.3. Hạ tầng & Docker Compose
Toàn bộ dự án được quản lý qua `docker-compose.yml`. Các Agent cần nắm rõ cấu trúc này để thực thi lệnh (chủ yếu thao tác thông qua `docker exec`):
- **Container WordPress (`michinhhang-wordpress-1`):** Nơi chứa mã nguồn PHP và chạy WP-CLI, TailwindCSS. Đường dẫn nội bộ được mount là `/var/www/html/wp-content`.
- **Container Database (`michinhhang-db-1`):** Chạy MariaDB.
- **Container Redis (`michinhhang-redis-1`):** Chạy Redis Cache.
*Lưu ý: Bất kỳ thao tác cài đặt gói (npm, composer) hoặc build assets nào cũng phải gọi vào đúng container WordPress thay vì chạy trực tiếp trên host.*

## 3. Quy tắc làm việc chung toàn dự án
1. **Luôn ưu tiên đọc tài liệu con:** Nếu yêu cầu liên quan đến sửa đổi giao diện, hãy đọc tài liệu của Theme. Nếu liên quan đến nghiệp vụ ERP, hãy đọc tài liệu của Plugin.
2. **Tính độc lập (Decoupling):** Cả Theme và Plugin phải hoạt động như 2 sản phẩm hoàn toàn độc lập để có thể tách ra triển khai riêng lẻ trong tương lai. Hệ thống CSS/TailwindCSS của mỗi thành phần phải được thiết lập riêng, build riêng, tuyệt đối không share chéo cấu hình hoặc phụ thuộc chéo vào file CSS của nhau.
3. **Môi trường Docker:** Mọi thao tác biên dịch (ví dụ build TailwindCSS) hoặc chạy lệnh hệ thống liên quan đến PHP/WP-CLI phải được thực thi thông qua các container Docker tương ứng, không can thiệp trực tiếp lên host.
