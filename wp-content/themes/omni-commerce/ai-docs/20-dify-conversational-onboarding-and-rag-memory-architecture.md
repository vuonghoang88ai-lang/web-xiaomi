# KIẾN TRÚC DIFY CONVERSATIONAL ONBOARDING, BÓC TÁCH THỰC THỂ TỰ ĐỘNG & TÍCH LŨY BỘ NHỚ RAG ĐA LƯỢT (SOP 20)

## 1. TỔNG QUAN & BỐI CẢNH VẤN ĐỀ
Trong quá trình triển khai trợ lý Marketing AI (CMO Agent) tích hợp giữa Dify Studio Native App và Backend Autonomous Marketing Engine (cổng 8085), hệ thống gặp phải các vấn đề then chốt:
1. **Giao diện Dify Studio bị che khuất**: Tab "Gỡ lỗi và xem trước" bị 5 ô nhập liệu biến tĩnh (`user_input_form`) chiếm 100% chiều cao, đè bẹp khung chat xuống đáy màn hình.
2. **Lỗi ảo giác `AuthorizeCore`**: Khi server backend cổng 8085 chưa khởi động hoặc chưa cập nhật Bearer token trong Dify `tool_api_providers`, LLM `combo-creater` qua 9Router không gọi được API thật nên tự suy diễn ra biệt lệ phân quyền C#/ASP.NET giả.
3. **Lỗi AI "Nói suông" trì hoãn**: Khi người dùng nhắn câu chung chung thiếu URL và từ khóa, LLM không đủ tham số để gọi tool và tự sinh văn bản hứa hẹn *"Công cụ đang kích hoạt, vui lòng chờ..."*, trong khi thực tế không có tiến trình nào chạy ngầm.
4. **Nhu cầu đàm thoại tự nhiên & ghi nhớ RAG không phụ thuộc thứ tự**: Người dùng muốn nhập thông tin dự án theo bất kỳ thứ tự nào (gửi link đối thủ trước, rồi nhắn tên web sau, rồi nhắn lợi thế sau), AI phải tự động bóc tách thực thể, lưu tích lũy vào RAG và tự sắp xếp lại để xử lý.

---

## 2. KIẾN TRÚC GIẢI PHÁP 4 TẦNG

```
[Khung Chat Đàm Thoại Mở (Dify Studio)]
           │
           ▼
[AI Bóc Tách Thực Thể (Conversational Entity Extractor)]
   • target_website_url
   • seed_competitor_url
   • business_description
   • core_strengths (UVP)
   • target_location
           │
           ├────────────────────────────┐
           ▼                            ▼
[Tầng 1: CSDL Quan Hệ]          [Tầng 2: Semantic RAG]
PostgreSQL (marketing_saas)     Dify Knowledge Base
Bảng `projects` & `users`       Dataset: KB_DefaultEnter_tongkhomicom
           │                            │
           └──────────────┬─────────────┘
                          │
                          ▼
[Tự Động Kích Hoạt Multi-Agent Pipeline (Cổng 8085)]
   • W1 (Market Intel): Cào dữ liệu thực tế (DuckDuckGo SERP + BeautifulSoup)
   • W2 & W3 (Analyst & Strategist): Báo cáo khoảng trống (Gap Analysis) + 4 UVP
   • W4 & W5 (Copywriter & Publisher): Viết bài chuẩn SOP 12 & Đăng WordPress
```

---

## 3. CÁC ĐIỂM SỬA CHỮA & NÂNG CẤP KỸ THUẬT

### 3.1. Chuyển Đổi Sang Conversational Onboarding (100% Chiều Cao Chat)
- Xóa bỏ `user_input_form: []` trong `cmo_agent_app_dsl.yml`.
- Cập nhật trực tiếp bản ghi CSDL PostgreSQL Dify: `UPDATE app_model_configs SET user_input_form = '[]' WHERE id = 'd8e89a45-7fed-467a-8ab0-19f53430c548';`.
- Kết quả: Khung chat đạt 100% chiều cao màn hình, trải nghiệm thoáng đãng tương tự ChatGPT / Gemini.

### 3.2. Cấu Hình Backend Marketing Engine & Xác Thực Dify
- Cập nhật `dify-integration/.env` với `ROUTER_API_KEY=sk-788a71868369e1bc-w3qdp3-41de08a0`.
- Bổ sung cấu hình RAG: `DIFY_API_URL=http://172.20.0.13:5001/v1` và `DIFY_DATASET_API_KEY=dataset-marketing-engine-token`.
- Sửa `db_service.py` kết nối PostgreSQL container `172.20.0.8:5432` với `sslmode="disable"`.
- Cập nhật Bearer Token `mk_engine_secret_2026_81bfe4c0f23947b08b5c8895bb16a58b` trong bảng `tool_api_providers` của Dify.
- Khởi chạy daemon `server.py` cổng 8085 (`0.0.0.0:8085`).

### 3.3. Sửa Lỗi CSDL `marketing_saas` & Nâng Cấp Scraper SERP
- Insert Tenant ID `0cfcbe3e-fbfa-4025-8aa0-ee5ff82b77d8` và User Admin vào bảng `tenants` và `users`.
- Bổ sung cột `brand_profile jsonb DEFAULT '{}'::jsonb` vào bảng `projects`.
- Nâng cấp `serp_discovery_tool.py`:
  - User-Agent trình duyệt hiện đại (`Chrome/122.0.0 Safari/537.36`) tránh bị DuckDuckGo bot detection.
  - Cơ chế ưu tiên đưa `seed_competitor_url` lên Top 1 kết quả trinh sát.

### 3.4. Kích Hoạt Bộ Công Cụ Trong Dify Agent Mode
- Cập nhật `app_model_configs.agent_mode`: Kích hoạt cả 3 công cụ:
  1. `analyzeProjectOnboarding`: Tiếp cận dự án mới, bóc tách web và 10 đối thủ.
  2. `researchCompetitor`: Bóc tách sản phẩm, giá bán thực tế theo từ khóa.
  3. `triggerSeoCampaign`: Khởi chạy chuỗi đa tác nhân viết bài chuẩn SOP và xuất bản.

### 3.5. Xử Lý Hiện Tượng AI "Nói Suông" & Minh Bạch Tiến Trình
- Cập nhật System Prompt nghiêm cấm Bot hứa hẹn hoãn binh.
- Fallback tự động: Khi người dùng hỏi chung chung không đưa link, Bot tự động lấy thông tin mặc định (`tongkhomi.com`, đối thủ `viomiviet.vn`, ngành `Tivi Xiaomi`) để gọi Tool thật.
- Luôn mở đầu câu trả lời bằng bảng trạng thái tiến trình đơn giản (W1 -> W6).

### 3.6. Cơ Chế Bóc Tách Thực Thể & Tích Lũy Bộ Nhớ RAG Đa Lượt
- **Hàm CSDL (`db_service.py`)**: `update_project_fields()` cập nhật linh hoạt các trường bóc tách được vào bảng `projects`.
- **Endpoint RAG (`server.py`)**: `POST /api/v1/project/memory` đồng bộ tài liệu `Ho_So_TongHop_{project_name}` vào kho tri thức Dify (`KB_DefaultEnter_tongkhomicom`).
- **Liên kết Dify App với RAG**: Bổ sung bản ghi `app_dataset_joins` và cập nhật `app_model_configs.dataset_configs` liên kết Dataset `0d7a7e06-cb8c-481e-8056-d9689e11a2c9`.
- Kết quả: Mọi lượt chat tiếp theo của người dùng đều tự động được inject ngữ cảnh từ RAG, giúp AI luôn nắm trọn vẹn thông tin dù thứ tự nhập liệu ban đầu có lộn xộn.
