# SOP 17: TIẾP CẬN DỰ ÁN MỚI, XÁC MINH SERP ĐỐI THỦ VÀ TRÍ TUỆ DỰ ÁN LÂU DÀI (POSTGRESQL + DIFY RAG)

> **Mục tiêu**: Hướng dẫn quy trình chuẩn hóa tiếp cận dự án mới cho CMO AI Copilot, tự động trinh sát 10 đối thủ online, xử lý lợi thế cạnh tranh kép (UVP), và lưu trữ tri thức bền vững vào Database & Vector RAG.

---

## 1. QUY TRÌNH 5 BƯỚC TIẾP CẬN DỰ ÁN (PROJECT ONBOARDING WORKFLOW)

```
[Khách hàng nhập 5 trường] 
       │
       ▼
[CMO AI: Bóc tách Web bạn + Đối thủ mẫu]
       │
       ▼
[Truy vấn DuckDuckGo SERP thực tế + kiểm tra URL]
       │
       ▼
[Cơ chế Kép UVP: Gợi ý 4 UVP khi để trống / Tối ưu Benchmarking khi đã điền]
       │
       ▼
[Lưu PostgreSQL `marketing_saas` + Đồng bộ Weaviate Dify Dataset]
```

---

## 2. CƠ CHẾ KÉP XỬ LÝ LỢI THẾ CẠNH TRANH (DUAL-TRACK UVP)

### A. Kịch bản Để Trống (`core_strengths` rỗng)
- AI phân tích kết quả SERP thực tế đã thu thập và kiểm tra; số kết quả có thể ít hơn 10.
- Nếu không đủ dữ liệu, các phương án UVP chỉ là giả thuyết cần xác thực, không được khẳng định là điểm yếu chung của đối thủ.
- Tự động sinh 4 phương án UVP chiến lược:
  1. **Trục Giá & Cam kết**: Minh bạch giá 100%, bảo hành dài hạn gấp đôi, đền bù nếu phát sinh chi phí.
  2. **Trục Tốc độ & Tiện lợi**: Phản hồi trực tuyến 24/7 trong 60 giây, đặt lịch ưu tiên không chờ đợi.
  3. **Trục Chuyên môn & Tiêu chuẩn Quốc tế**: Đội ngũ chuyên gia 10+ năm kinh nghiệm, chuyển giao công nghệ chính hãng.
  4. **Trục Chăm sóc & Cá nhân hóa**: Phác đồ 1:1, chuyên viên chăm sóc suốt vòng đời.

### B. Kịch bản Đã Điền (`core_strengths` có sẵn)
- AI không chấp nhận thụ động, mà đưa vào bộ lọc đối đầu (Competitive Benchmarking):
  - **Chỉ ra điểm bão hòa**: Ví dụ 80% đối thủ ngoài kia cũng tự nhận "bác sĩ kinh nghiệm".
  - **Nhận diện khoảng trống độc bản**: Combo riêng biệt (vd: bảo hành 10 năm tại Cầu Giấy).
  - **Đề xuất 4 phương án mài sắc bén hơn**:
    1. Số liệu hóa & Minh chứng thực tế
    2. Cam kết Đảo ngược Rủi ro (Risk Reversal - Hoàn tiền 100%)
    3. Đóng gói Thành Hệ Thống Độc Bản (Quy trình tiêu chuẩn riêng)
    4. Định vị Trải nghiệm Cao cấp

---

## 3. MÔ HÌNH MULTI-TENANT SAAS & DIFY RAG
- **PostgreSQL (`marketing_saas`)**: Lưu thông tin doanh nghiệp (`tenants`), người dùng (`users`), hồ sơ thương hiệu JSONB (`projects.brand_profile`), dự án (`projects`), đối thủ (`project_competitors`) và báo cáo khoảng trống (`market_intelligence_reports`).
- **Dify RAG Knowledge Base**: Tự động sinh dataset `KB_{client}_{project}` (độ dài tối đa 40 ký tự) và nạp toàn bộ hồ sơ trinh sát đối thủ vào Weaviate.
- **Tái sử dụng cho W1-W6**: Khi W1 cào giá, W2 nghiên cứu từ khóa, W3 lập cấu trúc Silo, W4 viết bài chi tiết... các Agent con chỉ cần truy vấn ngữ nghĩa từ RAG Dataset mà không cần cào lại.

---

## 4. BẢNG MÃ LỆNH VÀ CỔNG KẾT NỐI
- FastAPI Microservice: Cổng `8085` (`POST /api/v1/onboarding/analyze`); yêu cầu tenant UUID hợp lệ.
- Dify Web & API: Cổng `8090` / `5001`.
- Token Dataset: `dataset-marketing-engine-token`.
- Dự án chỉ được thông báo onboarding hoàn tất khi cả PostgreSQL và Dify RAG đã đồng bộ; trạng thái `partial` phải được xử lý tiếp.
