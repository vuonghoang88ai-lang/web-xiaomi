# SOP 48: BẮT TAY TỰ ĐỘNG 2 CHIỀU & BẢO MẬT KHÓA CỨNG BẢN QUYỀN TÊN MIỀN (ZERO-MANUAL HANDSHAKE & DOMAIN TRIAL ANTI-ABUSE REGISTRY)

## 1. BỐI CẢNH & NGUY CƠ BẢN QUYỀN TRÊN NỀN TẢNG SAAS
- **Rào cản trải nghiệm người dùng (UX Friction):** Khi khách hàng cài đặt WordPress Plugin Omni Bridge, việc bắt người dùng copy thủ công 4 thông số kỹ thuật (Marketing Engine URL, Tenant ID, Project ID, Engine API Key) và dán API Secret Key ngược lại Portal tạo ra tỷ lệ drop-off cao và nguy cơ nhầm lẫn key giữa các dự án.
- **Lỗ hổng gian lận dùng thử (Trial Abuse via Plugin Reinstallation / Project Recreation):** Một số người dùng có thể cố tình gỡ bỏ plugin (delete plugin) hoặc tạo lại dự án với tên miền cũ nhằm reset lại chu kỳ dùng thử 7 ngày miễn phí nhiều lần mà không trả phí.

## 2. QUY CHUẨN BẮT TAY TỰ ĐỘNG 2 CHIỀU (ZERO-MANUAL TWO-WAY HANDSHAKE)
Mọi quy trình kết nối giữa WordPress Plugin và Marketing Portal BẮT BUỘC tự động hóa 100%:

1. **Chiều WordPress ➔ Portal (Heartbeat Outbound):**
   - Plugin tự động sinh mã khóa API Secret Key an toàn (`obc_<token>`) nếu chưa tồn tại.
   - Khi kích hoạt bản quyền (qua file nhúng `omni-license.json` hoặc nhập License Key), Plugin gửi toàn bộ thông số trong payload xác thực:
     - `license_key`, `domain (FQDN)`
     - `cms_api_key`: Khóa bí mật API của WordPress site
     - `cms_api_url`: Địa chỉ gốc của site
     - `publish_endpoint`: Đường dẫn API REST xuất bản bài viết
     - `seo_provider`: Plugin SEO đang kích hoạt (`yoast`, `rank_math`, `seopress`)
   - Portal nhận dữ liệu và tự động cập nhật bảng `projects` (`cms_auth_config`, `cms_api_url`, `cms_type = 'wordpress'`). Giao diện Portal lập tức báo xanh "Đã kết nối".

2. **Chiều Portal ➔ WordPress (Credentials Inbound):**
   - Portal phản hồi gói dữ liệu hoàn chỉnh:
     - `gsc_engine_url`: Địa chỉ Engine Portal công khai
     - `tenant_id`: Định danh Tenant bảo mật
     - `project_id`: UUID của dự án tương ứng
     - `engine_api_key`: Khóa chứng thực hệ thống
   - Plugin WordPress nhận phản hồi và tự động ghi đè an toàn vào `wp_options`:
     - `omni_bridge_gsc_engine_url`
     - `omni_bridge_gsc_tenant_id`
     - `omni_bridge_gsc_project_id`
     - `omni_bridge_gsc_engine_api_key`
   - Khách hàng không cần phải copy - dán thủ công bất kỳ một ký tự nào.

## 3. THUẬT TOÁN KHÓA CỨNG TÊN MIỀN ĐỘC LẬP (DOMAIN TRIAL ANTI-ABUSE REGISTRY)
Để triệt tiêu hoàn toàn hành vi xóa plugin cài lại hoặc xóa dự án tạo lại để trục lợi dùng thử:

1. **Bảng Registry Độc Lập (`domain_license_registry`):**
   - Lưu trữ tại PostgreSQL ngoài phạm vi phiên làm việc:
     - `domain (VARCHAR PRIMARY KEY)`: Chuẩn hóa FQDN (loại bỏ `www.`, scheme, port).
     - `first_trial_started_at (TIMESTAMPTZ)`: Thời điểm kích hoạt dùng thử đầu tiên không thể thay đổi.
     - `trial_expires_at (TIMESTAMPTZ)`: Đúng `first_trial_started_at + 7 ngày`.
     - `current_plan_type`: Gói cước hiện tại (`trial_7d`, `plan_1m`, `plan_6m`,...).
     - `current_expires_at`: Hạn dùng gói cước thanh toán.
     - `license_key`, `project_id`.

2. **Cơ chế Thẩm Định Chống Gian Lận (Anti-Cheat Validation):**
   - Khi người dùng cài lại plugin hoặc xác thực:
     - Nếu `now > trial_expires_at` và `current_plan_type == 'trial_7d'`: Trả về lỗi `403 Expired` với thông báo minh bạch: *"Thời hạn dùng thử 7 ngày đã kết thúc. Việc cài lại plugin không gia hạn dùng thử."*
     - Nếu còn trong hạn: Trả về chính xác số ngày còn lại tính từ mốc ban đầu (ví dụ: `4.7 ngày`), tuyệt đối không cấp mới `7.0 ngày`.
   - Khi người dùng xóa dự án trên Portal và tạo lại với tên miền cũ: Hệ thống tự động so khớp `domain_license_registry` và áp dụng lại đúng ngày hết hạn ban đầu.

## 4. TIÊU CHUẨN KIỂM CHỨNG & BẢO TRÌ MÃ NGUỒN
- Toàn bộ các file xử lý (`license_service.py`, `license.py`, `class-license-trait.php`) BẮT BUỘC duy trì $\le 300$ dòng code theo SOP 23.
- Mọi logic đồng bộ phải có kiểm thử đơn vị tự động (Unit Test Coverage) đạt 100% pass trước khi triển khai lên môi trường sản xuất.

