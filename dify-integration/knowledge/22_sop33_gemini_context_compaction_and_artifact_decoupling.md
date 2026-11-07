# SOP 33: THUẬT TOÁN NÉN NGỮ CẢNH VÀ TÁCH RỜI SẢN PHẨM (GEMINI-STYLE CONTEXT COMPACTION & ARTIFACT DECOUPLING)

## 1. BỐI CẢNH VÀ BẢN CHẤT KỸ THUẬT

### 1.1 Vấn đề Context Bloat & Timeout 300s
- Khi người dùng tương tác trong một phiên trò chuyện kéo dài (Multi-Turn Chat), các câu trả lời của AI sinh ra sản phẩm hoàn chỉnh (Bài viết WooCommerce, bài chuẩn SEO 2000+ từ, chiến lược dài từ 5.000 đến 12.000+ ký tự).
- Nếu đưa nguyên khối toàn bộ văn bản thô của các lượt trước vào `TokenBufferMemory`, tổng token gửi sang LLM tăng phi mã (> 20.000 – 30.000 tokens).
- Mô hình LLM private trên máy chủ (`100.73.214.3:40128`) xử lý attention ma trận $O(N^2)$ vượt ngưỡng 300 giây, gây lỗi tê liệt:
  `[models] Connection Error, HTTPConnectionPool(host='100.73.214.3', port=40128): Read timed out. (read timeout=300)`.

### 1.2 UX Anti-Pattern: Tự ý nhảy sang phiên chat mới
- Việc hệ thống tự ngắt và mở một phiên trò chuyện mới làm đứt gãy trải nghiệm người dùng, phân mảnh danh sách lịch sử và khiến người dùng tưởng nhầm phiên làm việc bị lỗi.
- **Tiêu chuẩn Google Gemini / Anthropic Claude**: Người dùng duy trì trong **1 phiên làm việc duy nhất**, cuộn lên đọc lại toàn bộ bài viết cũ 100% nguyên vẹn.

---

## 2. NGUYÊN LÝ THUẬT TOÁN 3 TẦNG

```
[Database / UI Giao diện] <---------------- 100% Full Text (12.099 ký tự)
                                                   ▲
                                                   │ (Bảo tồn toàn vẹn)
[Gateway Pre-Flight Health] ───────────────► Dọn dẹp stub lỗi timeout
                                                   ▼
[TokenBufferMemory (Dify)] ───────────────► 1. Cửa sổ trượt (Sliding Window K=6)
                                            2. Tách rời sản phẩm (Artifact Decoupling)
                                            3. Trần token an toàn (Safe Ceiling: 4.000 tokens)
                                                   ▼
[Private LLM Router] ─────────────────────► Context siêu nhẹ (< 1.500 tokens)
                                            Thời gian xử lý: 10s - 80s (200 OK)
```

### Lớp 1: Tách rời Sản phẩm (Artifact Pointer Decoupling)
Đối với mọi phản hồi của trợ lý có độ dài vượt ngưỡng sản phẩm (`len(text) > 900` ký tự):
1. **Trích xuất Đề mục cấu trúc (Headings)**: Giữ lại tiêu đề chính `#` và các phân mục `##`, `:::boardroom-debate`.
2. **Trích xuất Móc nối mở đầu (Opening Hook)**: 4 dòng đầu tiên (~350 ký tự).
3. **Thẻ con trỏ định danh (Artifact Pointer)**:
   `[📦 ARTIFACT DECOUPLED: Nội dung chi tiết (xx.xxx ký tự) đã xuất bản và lưu trữ đầy đủ trong lịch sử giao diện]`
4. **Cam kết hành động (Trailing Action)**: 3 dòng kết luận/CTA cuối cùng (~250 ký tự).

### Lớp 2: Cửa sổ trượt động (Sliding Window K=6)
- Duy trì 6 tin nhắn gần nhất (tương đương 3 lượt hỏi-đáp qua lại) để giữ nguyên vẹn luồng suy nghĩ tức thì.
- Các tin nhắn trước đó được nén phân tầng hoặc loại trừ khỏi prompt mà không hề bị xóa trong Database.

### Lớp 3: Trần bảo vệ Token (Safe Token Ceiling)
- Giới hạn tối đa ngân sách token cho bộ nhớ ngữ cảnh ở mức `4.000 tokens`.
- Đảm bảo GPU không bị quá tải ma trận chú ý, giảm độ trễ phản hồi từ > 300s xuống dưới 90s cho cả quy trình đa tác vụ có Tool Calling.

---

## 3. CÁC ĐIỂM KIỂM SOÁT KIẾN TRÚC (CHECKPOINTS)

1. **Gateway Module**: `routes/portal/chat_compactor.py`:
   - `clean_stale_error_messages(conversation_id)`: Dọn sạch các bản ghi lỗi timeout rỗng trước khi gửi request mới.
   - `get_conversation_context_metrics(conversation_id)`: Đo lường turn count, tổng ký tự, và cảnh báo nhu cầu nén.
2. **Dify Backend**: `core/memory/token_buffer_memory.py`:
   - Áp dụng `_compact_assistant_text()` khi nạp `AssistantPromptMessage`.
   - Đặt `message_limit = 6` mặc định cho sliding window.
   - Giới hạn `safe_token_limit = min(max_token_limit, 4000)`.
3. **Tuân thủ SOP 23 & SOP 30**:
   - Mọi file code duy trì $\le 300$ dòng.
   - Thuật toán thích ứng tự động, không dùng danh sách từ khóa tĩnh hay ngưỡng tùy tiện.

