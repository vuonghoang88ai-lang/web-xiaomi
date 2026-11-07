# BỘ QUY CHUẨN KỸ NGHỆ: BẢO MẬT MẠNG LAI TAILSCALE/CGNAT & TỰ ĐỘNG HÓA ON-PAGE ĐA NGÀNH (SOP 47)

> **Mã quy chuẩn:** SOP 47 — TAILSCALE/CGNAT SECURITY & DOMAIN-AGNOSTIC ON-PAGE REMEDIATION  
> **Áp dụng:** Toàn bộ hệ thống Gateway, Portal Frontend, API Route Cms/SaaS và AI Copilot  
> **Mục tiêu:** Triệt tiêu lỗi 401 khi truy cập qua mạng nội bộ/VPN ảo; ngăn chặn tuyệt đối rò rỉ ngữ nghĩa chéo (Cross-Domain Leakage) giữa các dự án.

---

## 1. BẢO MẬT & ĐỊNH TUYẾN MẠNG LAI (TAILSCALE CGNAT & TRUSTED SUBNETS)

### 1.1. Bản chất sự cố HTTP 401: "Thiếu hoặc sai ENGINE_API_KEY"
- Khi người dùng truy cập Portal qua Tailscale Mesh VPN hoặc mạng CGNAT (dải IP `100.64.0.0/10`, tiêu biểu như `100.122.224.122`), middleware kiểm tra IP nội bộ nếu chỉ khai báo tĩnh `("172.", "10.", "127.", "192.168.")` sẽ coi đây là kết nối từ Internet công cộng.
- Nếu một nút hành động trên Portal gọi endpoint không có tiền tố `/api/v1/portal/*` (ví dụ: `/api/v1/projects/{id}/auto-fix-onpage`), yêu cầu sẽ bị chặn lại vì không kèm `x-engine-api-key`.

### 1.2. Bộ quy chuẩn định tuyến 2 lớp (Dual-Layer Routing Standard)
1. **Chuẩn hóa tiền tố Portal:** Toàn bộ request từ giao diện khách hàng BẮT BUỘC sử dụng tiền tố `/api/v1/portal/*`. Mọi API router phục vụ Portal phải đăng ký route song hành:
   ```python
   @router.post("/api/v1/portal/projects/{project_id}/auto-fix-onpage")
   @router.post("/api/v1/projects/{project_id}/auto-fix-onpage")
   def auto_fix_project_onpage_via_bridge(project_id: str):
       ...
   ```
2. **Mở rộng dải IP tin cậy:** Middleware BẮT BUỘC bổ sung dải `100.` để hỗ trợ môi trường Tailscale/WireGuard:
   ```python
   client_ip.startswith(("172.", "10.", "127.", "192.168.", "100."))
   ```

---

## 2. NGUYÊN TẮC BẤT BIẾN: KHÔNG RÒ RỈ NGÀNH HÀNG (ZERO CROSS-DOMAIN LEAKAGE)

### 2.1. Nhận diện lỗi Rò rỉ Ngữ nghĩa Chéo (Cross-Domain Hallucination)
- **Biểu hiện:** Trong dự án đọc truyện chữ online (`truyen360.top`), Copilot lại khuyên người dùng *"đánh vào giá tại kho để vượt mặt điện máy xanh"*.
- **Nguyên nhân cốt lõi:** Các hàm sinh dữ liệu ma trận từ khóa (`seed_serp.py`, `seed_serp_discovery.py`) khi gặp từ khóa vượt ngưỡng quét live đã dùng mảng đối thủ mẫu fix cứng của ngành điện máy (`dienmayxanh.com`, `mihanoi.vn`).

### 2.2. Quy chuẩn Thuật toán Thích ứng Ngữ cảnh Dự án
1. **Khai thác đối thủ từ chính dự án:** Dữ liệu fallback phải lấy trực tiếp từ `seed_competitors` hoặc danh sách đối thủ đã xác minh trong `project_competitors` của chính dự án đó.
2. **Khuyến nghị hành động theo bản chất kinh doanh (`business_description`):**
   - **Thương mại điện tử / Bán lẻ:** Tối ưu giá kho, so sánh thông số, Schema Product.
   - **Nội dung số / Truyện đọc / Blog:** Tối ưu trải nghiệm đọc, Silo phân cấp chương hồi, Schema Book & WebSite, củng cố E-E-A-T tác giả.
   - **CẤM:** Không được phép đưa tên các nhãn hàng ngoài ngành (`Điện Máy Xanh`, `CellphoneS`, `Tổng Kho Mi`) vào dữ liệu của dự án khác.

---

## 3. NÚT HÀNH ĐỘNG DỰ ĐOÁN ĐỘNG (DYNAMIC NEXT-ACTION MATRIX)

- Mọi nút bấm dự đoán MDP (`portal_chat_next_actions.js`) phải lấy tên thương hiệu động từ `state.currentProject.brand_name || state.currentProject.name`.
- Tuyệt đối cấm fix cứng chuỗi tĩnh "Tổng Kho Mi" trong các câu lệnh gợi ý của AI Copilot.

