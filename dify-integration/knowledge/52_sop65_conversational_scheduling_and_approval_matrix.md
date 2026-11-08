# SOP 65: MA TRẬN LẬP LỊCH HỘI THOẠI & PHÊ DUYỆT TỰ ĐỘNG (CONVERSATIONAL SCHEDULING & APPROVAL MATRIX)

## 1. BỐI CẢNH & MỤC TIÊU CỐT LÕI
Trong quy trình làm việc với CMO AI Copilot, sau khi bàn bạc chiến lược On-page, lên dàn ý SEO hoặc hoàn thiện bài viết, người dùng cần có khả năng ra lệnh bằng ngôn ngữ tự nhiên trong chat (hoặc dùng slash command `/schedule`, `/duyet`) để hệ thống:
1. **Thấu cảm ngữ cảnh (Contextual Empathy):** Nhận diện chính xác đối tượng cần lập lịch: Bài viết hoàn chỉnh (`article_deliverable`) hay Kế hoạch chiến lược (`strategic_plan`), trích xuất tiêu đề, tóm tắt và định dạng CPT tương ứng.
2. **Giải mã thời gian tương đối & tuyệt đối (Zero Hardcoding Temporal Resolver):** Phân tích thời gian tự nhiên tiếng Việt (*"sáng mai 9h"*, *"14:30 thứ 4 tuần sau"*, *"sau 2 ngày nữa"*, *"chiều nay"*) thành mốc thời gian chuẩn hóa ISO 8601 và nhãn hiển thị thân thiện.
3. **Thẻ Phê Duyệt Tương Tác Trực Tiếp (In-Chat Schedule & Approval Card):** Hiển thị thẻ giao diện trực quan trong luồng chat với trạng thái `🟡 Chờ Phê Duyệt`, cho phép người dùng 1-click `✅ Phê Duyệt Lập Lịch`, `⚡ Xuất Bản Ngay`, `✏️ Đổi Giờ` hoặc `❌ Hủy Lịch`.
4. **Bảo chứng xuất bản tự động chuẩn WordPress Native:** Đồng bộ với Omni Bridge Connector đẩy sang WordPress dưới dạng `post_status = 'future'` kèm `post_date` và `post_date_gmt` chuẩn múi giờ địa phương, kích hoạt cơ chế `publish_future_post` của WP Cron tự động đăng bài đúng giờ mà không cần sự can thiệp thủ công.

---

## 2. QUY CHUẨN THUẬT TOÁN 4 BƯỚC

### BƯỚC 1: NHẬN DIỆN Ý ĐỊNH & THẤU CẢM NGỮ CẢNH (CONTEXT INSPECTION)
- Router phân tích ý định dựa trên tập từ khóa hành động: `lập lịch`, `lên lịch`, `hẹn giờ`, `duyệt bài`, `phê duyệt`, `schedule`, `đăng lúc`.
- Thuật toán quét ngược lịch sử chat để trích xuất đối tượng:
  - Nếu độ dài văn bản $> 500$ ký tự kèm các tiêu đề `#`, `##` $\rightarrow$ phân loại là `article_deliverable`.
  - Nếu nội dung chứa dàn ý, kế hoạch phát triển, checklist $\rightarrow$ phân loại là `strategic_plan`.
  - Tự động bóc tách tiêu đề sạch không dính ký tự markdown (`#`, `*`, quotes).
  - Tự động nhận diện định dạng CPT (`post`, `product`, `sab_chapter`) dựa trên `site_topology` của website.

### BƯỚC 2: GIẢI MÃ THỜI GIAN THUẬT TOÁN (SOP 30 TEMPORAL RESOLUTION)
- Tuyệt đối không fix cứng chuỗi ngày giờ tĩnh. Sử dụng biểu thức chính quy tách lớp:
  - Nhận diện giờ/phút: `(\d{1,2})[:h](\d{1,2})` hoặc `(\d{1,2})\s*h\b` hoặc `lúc\s*(\d{1,2})`.
  - Nhận diện mốc ngày: `hôm nay` ($T+0$), `ngày mai` / `sáng mai` ($T+1$), `ngày kia` ($T+2$), `sau N ngày` ($T+N$).
  - Nhận diện thứ trong tuần: `thứ [2-7|hai|ba|tư|bốn|năm|sáu|bảy|chủ nhật] [tuần sau]` $\rightarrow$ tính toán số ngày chênh lệch mô-đun 7 chính xác.
  - Heuristic pha trong ngày: Sáng $\rightarrow$ `09:00`, Chiều $\rightarrow$ `14:00`, Tối $\rightarrow$ `19:00`.
- Chuyển đổi sang `scheduled_at` dạng ISO 8601 (`YYYY-MM-DDTHH:MM:SS+07:00`) và định dạng hiển thị tiếng Việt (`HH:MM - Thứ ..., DD/MM/YYYY`).

### BƯỚC 3: THẺ PHÊ DUYỆT TRỰC QUAN TRONG CHAT FEED (`renderScheduleApprovalCard`)
- Thẻ giao diện kế thừa ngôn ngữ thiết kế Glassmorphism & Typographic Hierarchy (SOP 51):
  - Badge trạng thái động: `🟡 Chờ Phê Duyệt` $\rightarrow$ `🟢 Đã Phê Duyệt Lập Lịch`.
  - Lưới thông số (Mission Grid): Thời điểm hẹn giờ xuất bản, Định dạng CPT, Tóm tắt nội dung.
  - 4 nút hành động tức thì:
    - `✅ Phê Duyệt Lập Lịch`: Gửi yêu cầu lưu vào PostgreSQL `bridge_tasks` và đồng bộ sang WordPress.
    - `⚡ Xuất Bản Ngay`: Xuất bản bài viết tức thì bỏ qua lịch hẹn.
    - `✏️ Đổi Giờ`: Hộp thoại tương tác cho phép cập nhật lại thời gian theo ngôn ngữ tự nhiên.
    - `❌ Hủy Lịch`: Thu hồi và vô hiệu hóa thẻ lập lịch.

### BƯỚC 4: BẢO CHỨNG ĐỒNG BỘ 2 CHIỀU WORDPRESS NATIVE (`future` STATUS)
- Phía Omni Bridge Connector (`class-api-trait.php` & `class-task-runner-trait.php`):
  - Khi nhận `status = 'future'` và `scheduled_at`:
    - Tính toán `post_date` theo múi giờ local của WordPress: `gmdate('Y-m-d H:i:s', $ts + ($gmt_offset * 3600))`.
    - Tính toán `post_date_gmt` theo GMT chuẩn: `gmdate('Y-m-d H:i:s', $ts)`.
    - Bật cờ `'edit_date' => true` để WordPress tôn trọng thời gian hẹn giờ thay vì ghi đè bằng `current_time`.
  - WordPress tự động nạp sự kiện `publish_future_post` vào bảng cron nội bộ, hiển thị bài viết ở trạng thái "Scheduled" trong WP Admin.

---

## 3. CHECKLIST KIỂM THỬ ĐỊNH LƯỢNG (VERIFICATION CHECKLIST)
- [x] Lệnh tự nhiên *"Lập lịch đăng bài này vào 9h sáng mai"* bóc tách chính xác `09:00` ngày hôm sau.
- [x] Lệnh phức hợp *"lên lịch duyệt kế hoạch này vào thứ 4 tuần sau lúc 14:30"* bóc tách chính xác `14:30` ngày Thứ Tư tuần kế tiếp.
- [x] Thẻ tương tác xuất hiện trực tiếp trong chat feed, không làm gián đoạn trải nghiệm người dùng.
- [x] Nút `Phê Duyệt Lập Lịch` lưu trữ bản ghi vào `bridge_tasks` PostgreSQL và khởi tạo bài viết `future` trên WordPress.
- [x] Sự kiện `publish_future_post` đăng ký thành công trong WP Cron (`wp cron event list`).
- [x] Mọi file `.py`, `.js`, `.php` tuân thủ nghiêm ngặt giới hạn $\le 300$ dòng code (SOP 23).
