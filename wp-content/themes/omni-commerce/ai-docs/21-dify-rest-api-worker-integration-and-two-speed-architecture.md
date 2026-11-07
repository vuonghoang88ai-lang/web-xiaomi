# Kiến Trúc Tích Hợp Dify REST API Worker & Mô Hình Lai 2 Làn Xe (Two-Speed Architecture)

## 1. Bối Cảnh & Vấn Đề Kỹ Thuật

Trong quá trình triển khai trợ lý Marketing AI trên nền tảng Dify tự lưu trữ (Self-hosted Dify Docker) kết nối với hệ thống Marketing Engine (FastAPI microservice trên cổng 8085), hệ thống đối mặt với 2 thách thức cốt lõi:

1. **Lỗi SSRF Proxy Báo Nhầm Mã 401 Khiến AI Ảo Giác Đối Thủ Cửa Hàng Máy Tính:**
   - Khi Dify gọi công cụ API `researchCompetitor` qua Squid SSRF Proxy (`ssrf_proxy:3128`), request không có `Authorization` header do định dạng mã hóa trong CSDL Dify.
   - Máy chủ `server.py` trả về `401 Unauthorized`.
   - File `core/helper/ssrf_proxy.py` của Dify bắt được mã 401 kèm header `Via: squid` và kích hoạt lỗi `ToolSSRFError: Access blocked by SSRF protection`.
   - Khi công cụ thất bại, mô hình LLM (`combo-creater`) tự động rơi vào trạng thái ảo giác (hallucination), bịa ra danh sách các cửa hàng máy tính không có thật (*Phóng Vân Computer*, *Công Nghệ Vĩnh Hùng*, *Phú Mỹ Hưng Computer*...).

2. **Bài Toán Hiệu Năng & Trải Nghiệm Khung Chat: Kết Nối Trực Tiếp Worker vs Đi Qua CrewAI:**
   - Việc kết nối trực tiếp với Worker trinh sát (Python Scraper & SERP Discovery) qua REST API đem lại độ trễ siêu thấp (2 - 3s).
   - Nếu đưa toàn bộ tác vụ đàm thoại qua CrewAI (đa agent hội ý, delegation), độ trễ lên đến 60 - 180s, làm đứt gãy trải nghiệm chat và gây timeout HTTP.

---

## 2. Giải Pháp Triển Khai: Mô Hình Lai 2 Làn Xe (Two-Speed Architecture)

```
                      ┌───────────────────────┐
                      │    KHUNG CHAT DIFY    │
                      │  Frontline CMO Agent  │
                      └───────────┬───────────┘
                                  │
                 ┌────────────────┴────────────────┐
                 ▼                                 ▼
         [LÀN SIÊU TỐC]                    [LÀN SUY LUẬN SÂU]
        Fast Lane (Chat)                   Deep Lane (Batch)
        Độ trễ: 2s - 3s                    Thời gian: 2 - 5 phút
     ───────────────────────            ─────────────────────────────
     • Python Native Worker W1          • Multi-Agent Chain (W3 - W6)
     • SERP Discovery (DuckDuckGo)      • Viết bài chuẩn SEO SOP 12
     • Live Website Verification        • Xuất bản Rank Math WordPress
     • Cào giá & Spec đối thủ           • Kịch bản TikTok & Social
     • Tích lũy vào RAG Dataset         • Chạy ngầm qua BackgroundTasks
```

### 2.1. Làn Siêu Tốc (Fast Lane - Chatbot Thời Gian Thực)
- **Phương thức giao tiếp:** RESTful API chuẩn **OpenAPI 3.0** (`application/json`).
- **Endpoint:**
  - `POST /api/v1/research`: Gọi Worker W1 trinh sát đối thủ và giá bán.
  - `POST /api/v1/onboarding/analyze`: Gọi Worker W1 + W2 phân tích khoảng trống thị trường (Gap Analysis).
  - `POST /api/v1/project/memory`: Ghi nhận dữ liệu thực thể vào PostgreSQL và đồng bộ kho tri thức RAG Dataset.
- **Cơ chế Internal Trust:** Middleware `require_engine_api_key` trong `server.py` tin cậy tuyệt đối các dải mạng Docker bridge (`172.20.0.0/16`, `172.17.0.0/16`, `127.0.0.1`, `10.0.0.0/8`). Mọi request nội bộ từ Dify hoặc Squid proxy được cấp quyền tức thì, triệt tiêu lỗi 401 và chặn SSRF giả.
- **Live SERP Discovery:** `serp_discovery_tool.py` sử dụng User-Agent Chrome hiện đại, tự động kiểm tra trạng thái online/offline của từng domain và loại bỏ website chết.

### 2.2. Làn Suy Luận Sâu (Deep Lane - CrewAI & Multi-Agent Chạy Ngầm)
- **Phương thức giao tiếp:** Bất đồng bộ qua Background Queue (`POST /api/v1/campaign`, `POST /api/v1/campaign/batch`).
- **Cơ chế hoạt động:**
  - Dify gửi yêu cầu và nhận mã `job_id` trong 1 giây.
  - Chuỗi Multi-Agent (hoặc CrewAI khi kích hoạt `USE_CREWAI=true`) tiếp nhận toàn bộ dữ liệu cào được từ W1/W2 để sản xuất nội dung chuyên sâu (2000 từ chuẩn SOP 12), chèn ảnh sạch không watermark (SOP 11) và xuất bản sang WordPress qua Omni Bridge Connector Plugin.

---

## 3. Kết Quả Kiểm Thử Thực Tế End-to-End

- **Kiểm thử gọi Live qua Dify Chat Engine (`/v1/chat-messages`):**
  - Câu truy vấn: *"tìm cho tôi 10 đối thủ bán tivi xiaomi tại hà nội"*.
  - Log Uvicorn: `172.20.0.5 - "POST /api/v1/research HTTP/1.1" 200 OK`.
  - Kết quả trả về cho người dùng: 7 đối thủ online thực tế (`mihanoi.vn`, `mivietnam.vn`, `xiaomicenter.vn`, `dienmayxanh.com`, `hoanghamobile.com`, `xiaomistorehanoi.com`, `cellphones.com.vn`) và 1 website offline (`xiaomistorehanoi.com.vn`), kèm bảng điểm mạnh (UVP) thực tế.
  - Thời gian phản hồi: 3.2 giây.
