# SOP 20: KIẾN TRÚC GIAO DIỆN CLIENT PORTAL THƯƠNG MẠI & CƠ CHẾ DUY TRÌ PHIÊN CHAT CỐ ĐỊNH THEO DỰ ÁN (PERSISTENT PROJECT CHAT)

> **Mục tiêu**: Chuẩn hóa quyết định kiến trúc dựng Giao diện Client Portal riêng biệt (White-label), tách rời giao diện người dùng khỏi Dify Engine (Headless Dify AI), thiết lập mô hình phân quyền Người dùng - Dự án (User/Project RBAC) và cơ chế khóa phiên chat cố định (`dify_conversation_id`) vĩnh viễn không bị mất ngữ cảnh khi F5 hoặc đổi thiết bị.

---

## 1. BỐI CẢNH VÀ VẤN ĐỀ THỰC TẾ (PROBLEM STATEMENT)

### 1.1. Hiện tượng mất phiên khi F5 trong Dify gốc
- Khi người dùng tương tác trong Dify Studio Preview (Debug Sandbox), mỗi lần F5 trang web thì framework Next.js của Dify nạp lại toàn bộ trạng thái RAM, biến `conversation_id` bị trả về `null`.
- Khi dùng giao diện Dify WebApp chia sẻ (`/chat/{share_code}`), Dify hoạt động theo mô hình người dùng ẩn danh (Consumer Chat), lưu session lỏng lẻo ở client cookie/localStorage. Khi mở link mới hoặc F5 mà không có query state, Dify gọi API `/v1/chat-messages` với `conversation_id: ""`.
- **Hệ quả**: Dify Backend tự động sinh ra một hội thoại mới hoàn toàn trong cơ sở dữ liệu. AI CMO Copilot mất toàn bộ trí nhớ ngữ cảnh trao đổi trước đó (không biết đang làm dự án gì, đã cào đối thủ nào, đã thống nhất dàn ý nào).

### 1.2. Giới hạn của giao diện Dify gốc đối với Thương mại hóa (Commercial SaaS)
1. **Thiếu thực thể Nghiệp vụ Project**: Dify chỉ có khái niệm Workspace và Apps, không có khái niệm "Dự án Website của Khách hàng" (gồm Domain, 10 đối thủ cào được, UVP độc quyền, liên kết WordPress và Search Console).
2. **Khó phân quyền khách hàng**: Giao diện Studio Dify quá phức tạp cho khách hàng phổ thông (dễ làm lộ Prompt, Model, API Key, cấu trúc Tool nội bộ).
3. **Xung đột nâng cấp (Upgrade Conflict)**: Nếu can thiệp sửa trực tiếp mã nguồn Frontend của Dify (`dify/web`), mỗi lần cập nhật phiên bản Dify mới sẽ gây xung đột mã nguồn nghiêm trọng.

---

## 2. QUYẾT ĐỊNH KIẾN TRÚC: HEADLESS DIFY AI & CUSTOM CLIENT PORTAL

Thống nhất áp dụng mô hình **Headless AI Engine**:
- **Dify**: Đóng vai trò là Tầng xử lý Trí tuệ Nhân tạo chạy ngầm (AI Execution Engine & RAG Datasets), hoàn toàn không để lộ giao diện cho khách hàng cuối.
- **Marketing Agent Engine (`:8085`)**: Đóng vai trò API Gateway trung gian, quản lý danh tính, phân quyền, kết nối PostgreSQL `marketing_saas`.
- **Custom Client Portal**: Giao diện độc lập (White-label 100%), thiết kế chuyên nghiệp, thân thiện, chỉ tập trung vào nghiệp vụ của khách hàng.

```
┌────────────────────────────────────────────────────────────────────────┐
│                   CUSTOM CLIENT PORTAL (Frontend)                      │
│   - Đăng nhập User / Multi-Tenant                                      │
│   - Danh sách Dự án (Projects: Brand A, Brand B, Brand C)              │
│   - Màn hình Chat Dự Án Chuyên biệt (URL: /projects/{id}/chat)         │
│   - Tab Bổ trợ: Đối thủ SERP, Báo cáo Gap, Kết nối WP & GSC            │
└──────────────────────────────────┬─────────────────────────────────────┘
                                   │
               (Bearer Token User / Tenant Session)
                                   ▼
┌────────────────────────────────────────────────────────────────────────┐
│            MARKETING AGENT ENGINE GATEWAY (:8085)                      │
│   - Kiểm tra quyền truy cập (RBAC: User có thuộc Project này không)    │
│   - Quản lý trạng thái: Tra cứu `projects.dify_conversation_id`        │
│   - Proxy tin nhắn hai chiều & Quản lý Streaming SSE                   │
└──────────────────────────────────┬─────────────────────────────────────┘
                                   │
         (Server-to-Server Internal API Key: App Secret)
                                   ▼
┌────────────────────────────────────────────────────────────────────────┐
│                   DIFY BACKEND SERVICE (:8090 / :5001)                 │
│   - POST /v1/chat-messages (Luôn truyền conversation_id cố định)       │
│   - GET /v1/messages?conversation_id=... (Đọc toàn bộ lịch sử chat)    │
│   - RAG Datasets kết hợp theo từng Project                             │
└────────────────────────────────────────────────────────────────────────┘
```

---

## 3. MÔ HÌNH DỮ LIỆU & PHÂN QUYỀN (TENANT -> USER -> PROJECT)

### 3.1. Cấu trúc Phân cấp (Hierarchy)
1. **Tenant (Doanh nghiệp)**: Đại diện cho một khách hàng tổ chức hoặc công ty ký hợp đồng (Gói cước, Hạn mức số lượng dự án, Thời hạn sử dụng).
2. **User (Người dùng)**: Tài khoản đăng nhập thuộc một Tenant (`role: admin, manager, client`).
3. **Project (Dự án thương hiệu / Website)**: Thuộc sở hữu của Tenant. Một User có thể tạo hoặc quản lý nhiều Project theo hạn mức của Tenant.
4. **Persistent Conversation (Phiên chat cố định)**: Mỗi Project sở hữu duy nhất **1 ID phiên chat cố định** (`dify_conversation_id`).

### 3.2. Cập nhật Cơ sở dữ liệu (`schema.sql`)
Cập nhật bảng `projects` trong PostgreSQL `marketing_saas`:

```sql
-- Bổ sung trường lưu trữ định danh phiên hội thoại Dify cố định
ALTER TABLE projects
    ADD COLUMN IF NOT EXISTS dify_conversation_id VARCHAR(255);

CREATE INDEX IF NOT EXISTS idx_projects_dify_conversation_id 
    ON projects(dify_conversation_id);
```

---

## 4. CƠ CHẾ DUY TRÌ PHIÊN CHAT CỐ ĐỊNH (PERSISTENT CHAT LIFECYCLE)

### 4.1. Vòng đời khởi tạo và duy trì phiên
```
[User mở Project X trên Portal]
             │
             ▼
[Backend tra cứu DB: project.dify_conversation_id]
             │
    ┌────────┴──────────────────────────┐
    │ Chưa có                          │ Đã có
    ▼                                  ▼
[Tạo tin nhắn khởi động dự án]   [Gọi Dify API lấy toàn bộ tin nhắn]
    │                                  │
    ▼                                  ▼
[Lấy conversation_id từ Dify]     [Render toàn bộ lịch sử lên UI]
    │                                  │
    ▼                                  │
[UPDATE projects SET                  │
 dify_conversation_id = :id]           │
    │                                  │
    └──────────────────┬───────────────┘
                       │
                       ▼
       [Người dùng chat câu hỏi mới]
                       │
                       ▼
[Backend gửi POST /v1/chat-messages]
[Kèm: conversation_id CỐ ĐỊNH]
                       │
                       ▼
    [Dify duy trì 100% ngữ cảnh AI]
    [F5 TRANG WEB -> VẪN NGUYÊN VẸN]
```

### 4.2. Xử lý khi F5 trình duyệt
1. Khi người dùng ấn F5 hoặc mở lại trình duyệt vào URL `/projects/{project_id}/chat`:
   - Frontend gửi yêu cầu: `GET /api/v1/projects/{project_id}/chat-history`.
   - Backend lấy `dify_conversation_id` tương ứng từ bảng `projects`.
   - Backend gọi Dify API: `GET /v1/messages?conversation_id={dify_conversation_id}&user={user_id}`.
   - Frontend render lại toàn bộ lịch sử đối thoại từ ngày đầu tiên.
2. AI CMO Copilot tiếp tục câu chuyện mà không cần hỏi lại bất kỳ thông tin nào về thương hiệu, sản phẩm hay đối thủ.

---

## 5. ĐẶC TẢ API GATEWAY CHO DỰ ÁN TRÊN MARKETING ENGINE (:8085)

### 5.1. Lấy lịch sử chat của Dự án
- **Endpoint**: `GET /api/v1/projects/{project_id}/chat-history`
- **Xác thực**: Bearer User Token
- **Luồng xử lý**:
  1. Kiểm tra User có quyền với `project_id`.
  2. Đọc `dify_conversation_id` trong DB.
  3. Nếu rỗng $\rightarrow$ trả về danh sách rỗng `{"messages": [], "conversation_id": null}`.
  4. Nếu đã có $\rightarrow$ gọi Dify `GET /v1/messages` và trả về danh sách tin nhắn chuẩn hóa cho UI.

### 5.2. Gửi tin nhắn vào Dự án (Streaming SSE)
- **Endpoint**: `POST /api/v1/projects/{project_id}/chat`
- **Body**:
  ```json
  {
    "message": "Hãy phân tích lại bảng giá của 10 đối thủ vừa cào được",
    "inputs": {}
  }
  ```
- **Luồng xử lý**:
  1. Lấy `conversation_id = project.dify_conversation_id`.
  2. Gửi request dạng stream sang Dify API:
     ```json
     {
       "query": "...",
       "response_mode": "streaming",
       "conversation_id": "<ID_CỐ_ĐỊNH_HOẶC_RỖNG>",
       "user": "<user_uuid>"
     }
     ```
  3. Nếu trước đó project chưa có `conversation_id`, ngay khi Dify stream chunk đầu tiên chứa `conversation_id`, Backend tự động cập nhật ngay vào bảng `projects` trong PostgreSQL.
  4. Stream trực tiếp từng token văn bản về cho Client qua giao thức `text/event-stream`.

### 5.3. Đặt lại trí nhớ dự án (Reset Memory - Tùy chọn nâng cao)
- **Endpoint**: `POST /api/v1/projects/{project_id}/chat/reset`
- **Chức năng**: Dành cho quản trị viên khi muốn AI xóa sạch ngữ cảnh hội thoại cũ để bắt đầu chiến dịch mới cho dự án mà không cần tạo lại Project.
- **Hành động**: Đặt `projects.dify_conversation_id = NULL` trong Database. Lần chat tiếp theo sẽ sinh phiên mới.

---

## 6. THIẾT KẾ GIAO DIỆN CLIENT PORTAL THƯƠNG MẠI (UI/UX SPEC)

### 6.1. Bố cục màn hình chuẩn (Workspace Layout)
- **Cột trái (Project Sidebar)**:
  - Bộ chuyển đổi Dự án (Project Switcher Dropdown).
  - Nút "+ Thêm dự án mới".
  - Thẻ tóm tắt thông tin: Tên thương hiệu, Website, Trạng thái CMS Bridge, Số lượng đối thủ đã quét.
  - Các tab điều hướng: **Chat Copilot**, **10 Đối thủ SERP**, **Chiến lược UVP**, **Hiệu suất GSC**.
- **Khu vực trung tâm (Main Chat Canvas)**:
  - Tiêu đề: *CMO AI Copilot — Dự án [Tên Dự Án]*.
  - Khung tin nhắn cuộn mượt mà: Phân biệt rõ tin nhắn User và câu trả lời AI (hỗ trợ Markdown, Code, Bảng biểu giá, Thẻ bài viết).
  - Thanh nhập liệu dưới cùng: Ô nhập câu hỏi, các câu hỏi gợi ý nhanh (Prompts Quick Actions: "Quét lại giá", "Lập dàn ý bài viết", "Xuất bản bài nháp").
- **Cột phải (Project Intelligence Drawer - Thu gọn được)**:
  - Hiển thị thông số nhanh: UVP đã chọn, 3 đối thủ mạnh nhất, danh mục bài viết đang SEO.

---

## 7. LỢI ÍCH THƯƠNG MẠI VÀ BẢO MẬT

1. **Bảo mật tuyệt đối sở hữu trí tuệ (IP Protection)**: Toàn bộ cấu trúc Agent, System Prompts, RAG Datasets và Token kết nối LLM được bảo vệ 100% trong mạng Docker nội bộ. Khách hàng chỉ nhìn thấy giao diện portal và kết quả đầu ra.
2. **Trải nghiệm khách hàng vượt trội (Enterprise UX)**: Không còn hiện tượng "AI ngơ ngác" sau khi tải lại trang; khách hàng cảm nhận được AI là một nhân viên Marketing thực thụ có trí nhớ dài hạn gắn liền với doanh nghiệp của họ.
3. **Sẵn sàng kinh doanh & Thu phí (SaaS Monetization)**: Dễ dàng áp dụng các gói cước (ví dụ: Gói Basic 1 Dự án, Gói Pro 5 Dự án, Gói Enterprise không giới hạn) nhờ kiến trúc phân quyền chặt chẽ.

---

## 8. QUY HOẠCH HẠ TẦNG TRIỂN KHAI (CONTAINER & SERVING STRATEGY)

### 8.1. Quyết định lựa chọn Container: `marketing-agent-engine` (:8085)
- **Tên Service trong compose**: `marketing-agent-engine` (Container `michinhhang-marketing-agent-1`).
- **Lý do KHÔNG dùng chung với container Dify (`docker-web-1`)**:
  - Dify Web là image đóng gói sẵn của hãng (`langgenius/dify-web:latest`), khi chạy `docker compose pull` nâng cấp Dify sẽ bị ghi đè và mất toàn bộ giao diện tùy chỉnh.
  - Tránh xung đột các route nội bộ của Dify Studio (`/apps`, `/datasets`, `/settings`).
- **Lý do KHÔNG cần tạo thêm container riêng**:
  - Tiết kiệm tuyệt đối tài nguyên máy chủ: Không tốn thêm 300MB - 600MB RAM cho một container Node.js/Nginx thừa thãi.
  - Triệt tiêu lỗi CORS: Giao diện Portal và API Gateway nằm chung cổng `8085`, frontend gọi trực tiếp `/api/v1/...` nội bộ mà không bị trình duyệt chặn.

### 8.2. Cơ chế Phân tách Đường dẫn (Route Isolation)
FastAPI trên cổng `8085` phân tách ranh giới rõ ràng:
- `http://<server-ip>:8085/` ➔ **Giao diện Client Portal** (HTML5/CSS/JS Single Page Workspace).
- `http://<server-ip>:8085/api/v1/...` ➔ **Hệ thống API SaaS Gateway** (Onboarding, UVP, Competitors, Chat).
- `http://<server-ip>:8085/health` ➔ **Kiểm tra trạng thái hệ sinh thái 5 tầng**.
- `http://<server-ip>:8085/docs` ➔ **Swagger API Documentation**.

### 8.3. Cơ chế Hot-Reload Không Downtime (Zero-Downtime Serving)
- Do thư mục `agent-engine/` được gắn volume trực tiếp:
  `- ./dify-integration/agent-engine:/app`
- Mọi chỉnh sửa mã nguồn giao diện (HTML/CSS/JS) trong thư mục `portal/` có hiệu lực ngay lập tức khi tải lại trình duyệt. Không cần `docker build`, không cần khởi động lại container, hệ thống phục vụ liên tục 24/7.

