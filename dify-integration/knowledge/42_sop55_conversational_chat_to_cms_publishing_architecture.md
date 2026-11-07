# SOP 55: KIẾN TRÚC XUẤT BẢN HỘI THOẠI TRỰC TIẾP & BỘ ĐIỀU KHIỂN CHAT-TO-CMS (CONVERSATIONAL CHAT-TO-CMS PUBLISHING ARCHITECTURE)

## 1. BỐI CẢNH & VẤN ĐỀ THỰC CHIẾN
- Trước đây, sau khi người dùng trao đổi chiến lược và yêu cầu CMO AI Copilot viết một bài viết hoàn chỉnh, người dùng không có cách nào trực quan để ra lệnh đăng tải lên website đích ngay tại cửa sổ chat.
- Khi người dùng gõ câu lệnh tự nhiên: *"Đăng bài này lên web"*, LLM chỉ sinh câu trả lời thụ động (ảo giác rằng đã đăng bài) mà không có cơ chế Action Routing.
- Nút "Xuất Bản" trên giao diện Dual-Pane Canvas bị gán placeholder rỗng, không liên kết với API thực tế.
- Toolbar dưới tin nhắn gửi thô toàn bộ nội dung (bao gồm cả monologue và thẻ họp) mà không cho phép tùy biến Tiêu đề, Định dạng CPT, Chuyên mục hay xem trước.

## 2. NGUYÊN TẮC KIẾN TRÚC TOÀN NĂNG (3 TRỤ CỘT)

### Trụ cột 1: Điều Hướng Ý Định Hội Thoại & Phím Tắt Slash Command (`/publish`)
- Bổ sung phím tắt lệnh nhanh `🚀 /publish` trên thanh phím tắt (`slashCommandsBar`).
- Bộ định tuyến `handleCustomChatCommand` nhận diện cả hai dạng:
  1. Lệnh nhanh trực tiếp: `/publish`, `/post`, `/dang-bai`.
  2. Ý định ngôn ngữ tự nhiên (SOP 30 Semantic Intent Routing): bóc tách các cụm ý định như *"đăng lên web"*, *"xuất bản bài này"*, *"publish to wordpress"*, *"đăng bài vừa viết"*.
- Khi bắt được lệnh, hệ thống tự động bóc tách tin nhắn Assistant gần nhất trong feed (`extractLatestArticleFromChat`), làm sạch toàn bộ thẻ DSML, function calls và khối họp `:::boardroom-debate`.

### Trụ cột 2: Bảng Điều Khiển Xuất Bản Đa Năng Tự Thích Ứng (Adaptive Publishing Controller)
- Tự động quét CPTs và Taxonomy từ `site_topology` đã đồng bộ qua Omni Bridge (SOP 50):
  - Website đọc truyện (`truyen360.top`): Hiển thị `post` (Bài viết blog) hoặc `sab_chapter` (Chương truyện).
  - Website thương mại điện tử: Hiển thị `product` (Sản phẩm WooCommerce) hoặc `post` (Bài viết).
  - Website tin tức / dịch vụ: Hiển thị `post` hoặc `page`.
- Giao diện điều khiển cho phép người dùng:
  - Hiệu chỉnh Tiêu đề bài viết (mặc định lấy từ H1 đầu tiên).
  - Lựa chọn Định dạng CPT và Chuyên mục phù hợp.
  - Chọn trạng thái: Bản nháp (`draft` - khuyến nghị để biên tập duyệt) hoặc Công khai (`publish`).
  - Nút chuyển nhanh sang Dual-Pane Canvas (`openArtifactCanvas`) nếu muốn sửa chi tiết.

### Trụ cột 3: Đồng Bộ Hóa Hai Chiều & Thẻ Phản Hồi Kết Quả Trực Quan
- Tích hợp trực tiếp tới Endpoint Gateway: `POST /api/v1/portal/projects/{id}/publish-hybrid`.
- Xử lý điều phối lai (Hybrid Dispatcher): Instant Push 3.5s kết hợp Task Queue tự động vượt Cloudflare WAF.
- Tự động kích hoạt **IndexNow Protocol** ping Google & Bing Bot lập chỉ mục tức thì.
- Xuất thẻ phản hồi kết quả trực quan ngay trong luồng chat (`renderPublishSuccessCard`):
  - Huy hiệu xanh: `🚀 ĐÃ XUẤT BẢN THÀNH CÔNG LÊN WEBSITE!`
  - Nút **`🔗 Xem Trên Web`** (mở trực tiếp Permalink bài viết).
  - Nút **`✏️ Chỉnh Sửa Trong WP`** (mở trực tiếp trang sửa bài trong WP Admin).
  - Trạng thái IndexNow ping thời gian thực.

## 3. TIÊU CHUẨN KỸ THUẬT & TUÂN THỦ
- **SOP 23:** Module `portal_chat_publish.js` duy trì $\le 300$ dòng code (thực tế 255 dòng).
- **SOP 30:** Zero hardcoding CPTs hay Categories; 100% nạp động từ `site_topology`.
- **SOP 46 & 51:** Thiết kế Responsive chuẩn di động, typography thanh mảnh, dark-mode anti-dilation, nạp cache-busting `?v=YYYYMMDD_HHMM`.

