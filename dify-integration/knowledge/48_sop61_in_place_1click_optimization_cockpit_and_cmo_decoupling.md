# SOP 61: COCKPIT TỐI ƯU TẠI CHỖ 1-CLICK & TÁCH RỜI TRÁCH NHIỆM CMO COPILOT (IN-PLACE 1-CLICK OPTIMIZATION COCKPIT & DUAL-TRACK ARCHITECTURE)

## 1. BỐI CẢNH & NGUYÊN LÝ THIẾT KẾ (SEPARATION OF CONCERNS)
Trước đây, các nút hành vi trên tab *Báo Cáo Web Mục Tiêu* (Title, Meta Description, Heading, 5 Bước Kế hoạch) đều dùng cơ chế `askCopilotFixOnpage()`, cưỡng chế nhảy tab người dùng sang Chat (`switchTab('chat')`) và nhồi một đoạn văn bản chỉ thị dài vào khung chat. Luồng này tạo ra 2 sai lệch nghiêm trọng:
1. **Ma sát nhận thức (Cognitive Friction):** Người dùng muốn sửa nhanh một thẻ Meta nhưng bị văng khỏi trang báo cáo, phải chờ AI sinh lời thoại rồi lại phải tự quay lại Dashboard để kiểm tra.
2. **Nhiễm bẩn ngữ cảnh hội thoại (Context Pollution - Vi phạm SOP 45):** Khung chat CMO bị ngập bởi các lệnh cấu hình kỹ thuật vụn vặt, làm phân tán bộ nhớ ngữ cảnh và giảm độ nhạy bén khi người dùng cần CMO sáng tạo nội dung chuyên sâu.

## 2. QUY CHUẨN KIẾN TRÚC PHÂN ĐỊNH HAI TẦNG (DUAL-TRACK UX PARADIGM)
Hệ thống phân tách rạch ròi thành 2 tầng trải nghiệm độc lập:

### Tầng 1: Cockpit Vận Hành Kỹ Thuật (Deterministic On-Page Cockpit - In-Place 1-Click)
* **Vị trí:** Tab *Báo Cáo Web Mục Tiêu* (`portal_my_site.js`, `portal_my_site_actions.js`).
* **Trách nhiệm:** Cho phép kiểm tra và tối ưu trực tiếp tại chỗ (In-Place Remediation) không cần chuyển tab:
  * **Top Metrics:** Nút `⚡ Tối ưu Title`, `⚡ Tối ưu Description`, `⚡ Chuẩn hóa H1` mở Quick Optimize Modal tại chỗ với bộ đếm ký tự thời gian thực, gợi ý AI 1-click và nút `🚀 Lưu Lên Web (Omni Bridge)`.
  * **Kế hoạch 5 Bước On-page:** Từng bước sở hữu badge trạng thái trực quan (`✅ Đã Đạt Chuẩn` / `⚠️ Cần Chuẩn Hóa`) kèm nút xử lý độc lập tại chỗ (`⚡ Sửa tại chỗ`, `⚡ Chuẩn hóa 1 H1`, `⚡ Đồng bộ Silo`, `👁️ Xem 5 trang`).
  * **Header Button:** Nút `🚀 Tối Ưu Toàn Diện 100 Điểm` chạy 1-click cập nhật toàn bộ thông số lên WordPress qua Omni Bridge mà không làm gián đoạn màn hình làm việc.

### Tầng 2: CMO AI Copilot (Strategic Advisory & Creative Content Generation)
* **Vị trí:** Tab *Chat Copilot* (`portal_chat.js`, Artifact Canvas).
* **Trách nhiệm:** Giữ ngữ cảnh sạch 100% để tập trung vào:
  1. Cố vấn chiến lược phân phối, phân tích 10 đối thủ SERP, lập kế hoạch marketing.
  2. Sản xuất bài viết chuyên sâu 2000+ từ chuẩn SOP 12 (kích hoạt từ Bước 5 của Kế hoạch On-page qua nút `✍️ Viết Bài Với CMO`).
  3. Lệnh xuất bản hội thoại `/publish` khi bài viết đã được người dùng duyệt hoàn chỉnh.

## 3. TIÊU CHUẨN KỸ THUẬT VÀ BẢO TOÀN SỐ DÒNG
* Mọi file mã nguồn (`portal_my_site.js`, `portal_my_site_actions.js`, `portal_tab_views.html`) duy trì nghiêm ngặt **dưới 300 dòng code** theo chuẩn SOP 23.
* Cập nhật phiên bản Cache-Busting `?v=YYYYMMDD_HHMM` trên các thẻ script ở `index.html` để trình duyệt và thiết bị di động nhận diện ngay logic mới.

