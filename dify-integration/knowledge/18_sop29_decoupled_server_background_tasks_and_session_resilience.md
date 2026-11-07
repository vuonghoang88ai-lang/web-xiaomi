# SOP 29: KIẾN TRÚC NHIỆM VỤ NGẦM MÁY CHỦ ĐỘC LẬP & TỰ PHỤC HỒI PHIÊN HỘI THOẠI (DECOUPLED SERVER BACKGROUND TASKS & SESSION RESILIENCE)

> **Phiên bản**: 1.0 — Ban hành ngày 02/11/2026  
> **Phạm vi áp dụng**: Toàn bộ hệ sinh thái AI Gateway, Background Workers, Client Portal và Hệ thống Streaming SSE.  
> **Tôn chỉ tối cao**: "Vòng đời sinh văn bản của AI máy chủ độc lập hoàn toàn với kết nối mạng của Client. Người dùng có thể F5, tắt máy, đổi máy tính hoặc chuyển dự án mà không bao giờ làm gián đoạn hay mất phiên chat."

---

## 1. BỐI CẢNH VÀ NGUYÊN NHÂN SỰ CỐ "ĐỨT KẾT NỐI MẤT DỮ LIỆU"

Trong các hệ thống AI Copilot thế hệ cũ, client mở kết nối HTTP SSE trực tiếp với AI worker. Mô hình này bộc lộ 3 điểm yếu chí mạng:
1. **Lỗ hổng gián đoạn client:** Khi AI sinh câu trả lời dài (từ 1 đến 3 phút), nếu người dùng vô tình bấm F5, rớt mạng WiFi, hoặc đóng trình duyệt, kết nối HTTP bị cắt đứt. Phía máy chủ coi đó là sự cố và hủy bỏ tiến trình hoặc không thể trả dữ liệu về sau khi mở lại.
2. **Xung đột đa dự án (Multi-Project Collision):** Khi chuyển sang Dự án B trong lúc Dự án A đang sinh câu trả lời, câu trả lời của A tràn sang cửa sổ của B hoặc phải bấm Hủy (Abort) làm mất dữ liệu của A.
3. **Mất trắng văn bản tích lũy (Stream Wipeout):** Khối `catch` ở client thường ghi đè `innerHTML = "Lỗi kết nối"`, xóa sạch hàng trăm từ giá trị mà người dùng vừa theo dõi.

---

## 2. BỐN TRỤ CỘT KIẾN TRÚC NHIỆM VỤ NGẦM BỀN VỮNG

```
┌────────────────────────────────────────────────────────────────────────┐
│ 1. DECOUPLED LIFECYCLE (Tách Rời Vòng Đời Máy Chủ Khỏi Client)         │
│    - Sử dụng asyncio.create_task() khởi chạy worker nền độc lập.       │
│    - Client ngắt kết nối -> Worker vẫn âm thầm chạy 100% đến khi xong. │
├────────────────────────────────────────────────────────────────────────┤
│ 2. IN-MEMORY TASK REGISTRY & TTL SAFETY (Quản Lý Tác Vụ An Toàn)       │
│    - Lưu vết ACTIVE_CHAT_TASKS[project_id] = { status, prompt, ans }   │
│    - Áp dụng TTL trần 600 giây (10 phút) tự giải phóng bộ nhớ RAM.     │
├────────────────────────────────────────────────────────────────────────┤
│ 3. RESILIENT POLLER & STATE HYDRATION (Tự Phục Hồi Phiên Thông Minh)   │
│    - Khi F5 hoặc mở lại dự án: Client gọi GET /chat-status             │
│    - Nhận diện is_generating=True -> Tự động chèn thẻ chờ & đếm giây.  │
│    - Hoàn tất -> Tự động nạp kết quả hoàn chỉnh vào Chat Feed.         │
├────────────────────────────────────────────────────────────────────────┤
│ 4. MULTI-PROJECT CONCURRENT STREAM ISOLATION (Đa Luồng Đa Dự Án)       │
│    - Quản lý luồng theo project_id riêng biệt, không gán cờ toàn cục.  │
│    - Chuyển dự án thoải mái, mỗi dự án tiếp tục chạy ngầm độc lập.     │
└────────────────────────────────────────────────────────────────────────┘
```

---

## 3. GIAO THỨC TƯƠNG TÁC API (DECOUPLED PROTOCOL)

1. **Khởi tạo (`POST /api/v1/portal/projects/{id}/chat`):**
   - Gateway tạo `broadcast_queue = asyncio.Queue()`.
   - Gateway khởi chạy worker nền `run_dify_background_worker(...)`.
   - StreamingResponse đọc từ queue và phát SSE cho Client.
   - Nếu client đóng kết nối, `CancelledError` chỉ dừng vòng lặp SSE; worker nền tiếp tục hứng stream từ Dify.

2. **Kiểm tra trạng thái (`GET /api/v1/portal/projects/{id}/chat-status`):**
   - Trả về JSON:
     ```json
     {
       "is_generating": true,
       "status": "generating",
       "elapsed_seconds": 38,
       "prompt": "Lập chiến lược...",
       "accumulated_preview": "..."
     }
     ```

3. **Chủ động hủy (`POST /api/v1/portal/projects/{id}/chat-abort`):**
   - Đóng kết nối `httpx.AsyncClient` và đánh dấu `status = "aborted"`.

---

## 4. QUY TẮC BẢO TOÀN DỮ LIỆU TẠI FRONTEND (CLIENT RESILIENCE RULES)

1. **Không ghi đè khi lỗi:** Nếu stream bị lỗi giữa chừng, tuyệt đối không dùng `answerContainer.innerHTML = Error`. Luôn giữ lại `accumulatedAnswer` và chỉ nối thêm thông báo cảnh báo lỗi bên dưới.
2. **Không hủy stream khi chuyển dự án:** Khi người dùng đổi dự án trên dropdown, không gọi `abortChatStream()`. Chỉ reset cờ hiển thị nội bộ của view và để task tiếp tục chạy ngầm ở máy chủ.
3. **Phục hồi tiến độ tức thì:** Bất cứ khi nào gọi `loadChatHistory(projectId)`, hàm kiểm tra `checkBackgroundChatStatus(projectId)` phải được kích hoạt song song để đồng bộ trạng thái ngay lập tức.

