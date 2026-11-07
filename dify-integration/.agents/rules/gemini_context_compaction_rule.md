---
trigger: always_on
description: "SOP 33: Thuật toán nén ngữ cảnh Gemini-Style, Tách rời sản phẩm và triệt tiêu lỗi Timeout 300s."
---

# SOP 33: NÉN NGỮ CẢNH & TÁCH RỜI SẢN PHẨM (GEMINI-STYLE CONTEXT COMPACTION)

Khi xử lý các luồng hội thoại đa phiên (Multi-turn Chat) và kết nối với mô hình LLM, AI Assistant và Gateway BẮT BUỘC tuân thủ:

1. **BẢO TOÀN TRẢI NGHIỆM ĐƠN PHIÊN (Single-Session User Experience):**
   - Giữ người dùng trong **1 phiên chat duy nhất**, tuyệt đối KHÔNG tự ý ngắt phiên hoặc tự động tạo phiên mới khi context dài làm gián đoạn trải nghiệm người dùng.
   - Toàn bộ nội dung gốc (kể cả các bài viết dài > 12.000 ký tự) BẮT BUỘC lưu trữ 100% nguyên vẹn trong Cơ sở dữ liệu và hiển thị đầy đủ trên giao diện khi người dùng cuộn lên đọc lại.

2. **THUẬT TOÁN TÁCH RỜI SẢN PHẨM (Artifact Pointer Decoupling):**
   - Khi chuẩn bị Prompt gửi cho LLM ở các lượt sau, bất kỳ phản hồi trợ lý nào vượt ngưỡng sản phẩm (`len(text) > 900` ký tự) BẮT BUỘC phải được nén thành con trỏ định danh:
     - Giữ nguyên các tiêu đề đề mục cấu trúc (`#`, `##`, `:::boardroom-debate`).
     - Giữ nguyên Móc nối mở đầu (Opening Hook - 4 dòng đầu).
     - Chèn thẻ con trỏ: `[📦 ARTIFACT DECOUPLED: Nội dung chi tiết (xx.xxx ký tự) đã xuất bản và lưu trữ đầy đủ trong lịch sử giao diện]`.
     - Giữ nguyên Cam kết hành động/CTA kết bài (3 dòng cuối).

3. **CỬA SỔ TRƯỢT ĐỘNG & TRẦN TOKEN AN TOÀN (Sliding Window & Safe Ceiling):**
   - Áp dụng Cửa sổ trượt mặc định $K = 6$ messages gần nhất (tương đương 3 lượt trao đổi qua lại).
   - Thiết lập trần an toàn tuyệt đối cho bộ nhớ lịch sử: `safe_token_limit = min(max_token_limit, 4000)` tokens.
   - Tuyệt đối không để Prompt gửi sang LLM vượt quá 4.000 tokens, đảm bảo thời gian suy luận luôn trong ngưỡng 10s - 90s, triệt tiêu hoàn toàn lỗi Timeout 300s.

4. **DỌN DẸP BẢN GHI LỖI TIỀN TRẠM (Pre-Flight Cleanup):**
   - Gateway trước khi gửi câu lệnh mới hoặc khi bắt được sự kiện lỗi BẮT BUỘC tự động dọn sạch các bản ghi lỗi rỗng (`status = 'error'` hoặc `answer = ''`) trong DB để ngăn chặn lỗi dây chuyền làm nhiễm độc context.

