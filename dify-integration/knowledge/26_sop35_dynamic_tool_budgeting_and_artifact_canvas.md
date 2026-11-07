# SOP 35 & 36: DYNAMIC TOOL BUDGETING & DUAL-PANE ARTIFACT CANVAS

## 1. SOP 35: NGÂN SÁCH CÔNG CỤ ĐỘNG & TINH LỌC SERP (DYNAMIC TOOL BUDGETING)

### Bối cảnh & Thách thức
Khi Agent kích hoạt các công cụ trinh sát đối thủ (`researchCompetitor`, `discover_top_10_competitors`) hoặc cào dữ liệu bên ngoài, dữ liệu thô (HTML snippet, metadata kỹ thuật, cookie headers) thường có dung lượng từ 6.000 đến 40.000 ký tự. Khi nạp thẳng vào LLM ở chế độ Non-Stream Agent ReAct, máy chủ AI xử lý vượt quá 300 giây dẫn đến lỗi `Read timed out (read timeout=300)`.

### Thuật toán Extractive Payload Condensation
Mọi công cụ trinh sát đối thủ và Live Internet tại Gateway bắt buộc đi qua bộ lọc tinh gọn `condense_competitor_data`:
1. **Trần Token Cứng:** Payload trả về cho AI Core giới hạn $\le 1.500$ tokens (~5.500 ký tự).
2. **Loại bỏ rác kỹ thuật:** Xóa bỏ 100% `ad_details`, `verified_at`, mã trạng thái và các đoạn snippet dư thừa.
3. **Cấu trúc dữ liệu tinh lọc:**
   - `top_verified_competitors`: Chỉ giữ tối đa 5 đối thủ có rank cao nhất kèm Domain, Title, Core Strength và Vulnerability.
   - `market_price_overview`: Giá sàn thị trường và giá đề xuất.
   - `sample_market_products`: Top sản phẩm tiêu biểu phục vụ so sánh giá.

---

## 2. SOP 36: GIAO DIỆN TÁCH ĐÔI MÀN HÌNH (DUAL-PANE ARTIFACT CANVAS)

### Bối cảnh & Thách thức
Người dùng khi yêu cầu bài viết sản phẩm WooCommerce hoặc bài blog 2.000 từ thường phải cuộn qua các đoạn phân tích dài của C-Suite (`:::boardroom-debate`), khó đọc và không thể sửa đổi trực tiếp trước khi xuất bản lên website.

### Kiến trúc Split-Pane Canvas (Gemini / Claude Style)
1. **Kích hoạt tự động hoặc thủ công:**
   - Tự động: Khi AI xuất bản bài viết dài (`> 1.200 ký tự` có cấu trúc đề mục `#`, `##`), giao diện tự động trượt mở cột Canvas bên phải (tỷ lệ 45% Chat - 55% Canvas).
   - Thủ công: Người dùng có thể nhấn nút `📦 Mở Canvas` tại thanh công cụ dưới mỗi tin nhắn.
2. **Tính năng cốt lõi trên Canvas:**
   - **3 Chế độ hiển thị:** `Xem Trước WYSIWYG (Preview)`, `Chỉnh Sửa Trực Tiếp (Live Edit)`, `Mã Markdown Thô (Raw)`.
   - **Bảng chỉ số bài viết:** Tự động đếm số từ, số ký tự, ước tính thời gian đọc và huy hiệu `[✅ Bảo chứng thông số kho 100%]`.
   - **Thao tác 1 chạm:** Sao chép nội dung, Chế độ toàn màn hình (Fullscreen), và Đẩy thẳng sang WordPress WooCommerce.
3. **Ma trận Gợi ý Hành động Tiếp theo (Markov Decision Process - MDP):**
   - Tự động phân tích Intent của câu trả lời vừa sinh và sinh ra 3-4 nút chip ngữ cảnh:
     - Bài viết $\rightarrow$ Gợi ý tạo kịch bản TikTok 60s, 5 bài post Facebook Seeding, Đăng WordPress.
     - Đối thủ $\rightarrow$ Gợi ý khai thác khoảng trống giá Top 1, viết bài đè đối thủ, lập bảng tính năng.
     - Thời tiết $\rightarrow$ Gợi ý chiến dịch Flash Sale theo mùa, viết bài tư vấn tiêu dùng.

