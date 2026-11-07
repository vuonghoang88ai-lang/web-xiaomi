# SOP 31: KIẾN TRÚC ĐA PHIÊN CHAT (GEMINI-STYLE) & QUẢN TRỊ VÒNG ĐỜI NGỮ CẢNH (CONTEXT LIFECYCLE)

> [!IMPORTANT]
> **MỤC TIÊU CỐT LÕI:**
> Giải quyết triệt để vấn đề "phình to ngữ cảnh hội thoại" dẫn đến lỗi Timeout 300s của mô hình LLM, đồng thời cung cấp trải nghiệm quản lý công việc theo luồng chủ đề độc lập chuẩn phong cách Google Gemini / OpenAI ChatGPT.

---

## 1. NGUYÊN NHÂN GỐC RỄ CỦA HIỆN TƯỢNG PHÌNH TO NGỮ CẢNH (CONTEXT BLOAT)

Khi một phiên hội thoại kéo dài (ví dụ: trên 50–70 tin nhắn chứa nhiều bài viết, mã HTML, và kết quả trinh sát):
1. **Quá tải Token:** Tổng lượng token đầu vào vượt qua ngưỡng xử lý hiệu quả của mô hình LLM (mất từ 90s đến trên 300s).
2. **HTTP Read Timeout:** Dify hoặc Gateway hết thời gian chờ kết nối (`read timeout=300`), dẫn đến trạng thái `status: error`.
3. **Bong bóng chat rỗng:** Nếu Gateway hoặc Frontend không bắt lỗi này, trình duyệt sẽ render bong bóng xám trống trơn, gây hiểu lầm là hệ thống bị treo.

---

## 2. THIẾT KẾ CƠ SỞ DỮ LIỆU ĐA PHIÊN (MULTI-SESSION SCHEMA)

Dữ liệu phiên được lưu trữ trong bảng `chat_conversations` (PostgreSQL) liên kết với từng dự án:

```sql
CREATE TABLE IF NOT EXISTS chat_conversations (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    project_id UUID NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    dify_conversation_id VARCHAR(255) NOT NULL,
    title VARCHAR(255) NOT NULL DEFAULT 'Cuộc trò chuyện mới',
    message_count INT NOT NULL DEFAULT 0,
    is_pinned BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_chat_conversations_project ON chat_conversations(project_id, updated_at DESC);
```

### Nguyên tắc bảo mật & cô lập dự án:
- Mỗi dự án (`project_id`) có danh sách phiên riêng biệt.
- Khi người dùng chuyển dự án, giao diện tự động lọc chỉ hiển thị phiên của dự án đó.
- Nút "Làm mới phiên chat" hoặc "+ Đoạn chat mới" không xóa dữ liệu cũ mà chỉ tạo ra phiên mới để bắt đầu ngữ cảnh sạch.

---

## 3. THIẾT KẾ GIAO DIỆN DRAWER TRƯỢT (GEMINI / CHATGPT STYLE)

1. **Thanh trượt Collapsible Drawer:**
   - Chiều rộng 270px, có nút thu gọn `[ ✕ ]` hoặc nút mở `[ 🕒 Lịch sử chat ]` trên Topbar.
   - Khi thu gọn, dành 100% không gian màn hình cho bảng số liệu và nội dung bài viết.
2. **Phân nhóm thời gian trực quan:**
   - **Hôm nay (Today):** Các phiên phát sinh trong ngày.
   - **7 ngày qua (Previous 7 days):** Các phiên trong tuần gần nhất.
   - **Cũ hơn (Older):** Các phiên lưu trữ lịch sử trước đó.
3. **Hệ thống cảnh báo thông minh (Context Warning Badge):**
   - Khi `message_count >= 30`: Hiển thị huy hiệu màu vàng `⚠️` kèm tooltip nhắc nhở: *"Phiên đã dài (>30 tin). Nên mở phiên mới để AI phản hồi nhanh nhất."*

---

## 4. QUY CHUẨN XỬ LÝ LỖI VÀ CHỐNG BONG BÓNG RỖNG (DEFENSIVE UI/UX)

1. **Tại Gateway (`chat.py`):**
   - Khi `item.get("status") == "error"` hoặc `item.get("error")`, Gateway tuyệt đối không trả về `content: ""` mà phải đóng gói thông báo lỗi thân thiện kèm nguyên nhân và hướng dẫn cụ thể.
2. **Tại Frontend (`portal_chat.js`):**
   - Hàm `appendMessage()` kiểm tra: nếu `role === 'assistant'` mà không có nội dung chữ và không có thought, UI tự động hiển thị thông báo lỗi thay vì bong bóng trống.

