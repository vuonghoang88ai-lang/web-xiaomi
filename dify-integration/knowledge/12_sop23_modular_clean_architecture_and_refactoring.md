# SOP 23: KIẾN TRÚC MÃ NGUỒN PHÂN TẦNG MODULAR (MODULAR CLEAN ARCHITECTURE) & QUY CHUẨN REFACTORING
> Chuẩn hóa quy trình phân tách mã nguồn từ các file monolithic khổng lồ (> 2.000 dòng) sang cấu trúc module chuyên trách cao (High Cohesion, Low Coupling), áp dụng cho FastAPI Gateway, Seed Intelligence Engine, Client Portal SPA và WordPress Plugin.

---

## 1. Bối Cảnh & Mục Tiêu Tái Cấu Trúc (Why Refactor?)

Trong giai đoạn đầu phát triển nhanh (MVP), mã nguồn thường bị dồn vào các file lớn:
- `server.py`: Dài **2.536 dòng** (chứa đồng thời Pydantic models, FastAPI routes, background workers, GSC OAuth, Client Portal CRUD, chat streaming, và settings).
- `seed_analyzer.py`: Dài **2.054 dòng** (chứa On-page scraper, đo TTFB, CMS detector, Moz API pool, 4-tier keyword extraction, live SERP scraping, và briefing generator).
- `portal.js`: Nặng **228 KB** (chứa toàn bộ logic render của 5 tab giao diện, các modal, form validation, và SSE chat).
- `omni-bridge-connector.php`: Nặng **52 KB** gộp chung 1 file PHP trong plugin WordPress.

### Hậu quả của cấu trúc Monolithic cũ:
1. **Khó bảo trì và kiểm thử**: Rất khó viết unit test cho từng tính năng riêng biệt.
2. **Xung đột mã nguồn (Merge Conflicts)**: Khi nhiều kỹ sư hoặc agent cùng chỉnh sửa một file duy nhất.
3. **Hiệu năng & Tải trang**: File frontend quá lớn gây chậm trễ việc nạp script trên trình duyệt.

---

## 2. QUY ĐỊNH BẮT BUỘC: GIỚI HẠN TỐI ĐA 300 DÒNG MÃ CHO MỖI FILE CODE (STRICT HARD LIMIT: ≤ 300 LINES)

> **NGUYÊN TẮC BẤT DI BẤT DỊCH (CARDINAL RULE):**
> **Mọi file code trong toàn bộ codebase (Python `.py`, JavaScript `.js`, CSS `.css`, PHP `.php`) tuyệt đối KHÔNG ĐƯỢC VƯỢT QUÁ 300 DÒNG MÃ.**

### Chi Tiết Quy Chuẩn 300 Dòng:
1. **Ngưỡng Giới Hạn (Line Count Thresholds)**:
   - **Mục tiêu lý tưởng (Optimal)**: 80 – 180 dòng.
   - **Ngưỡng cảnh báo (Soft Alert)**: **250 dòng** — Lập trình viên / Agent bắt buộc phải dừng lại, rà soát và chuẩn bị phương án tách module.
   - **Ngưỡng trần cứng (Hard Limit)**: **300 dòng** — Nghiêm cấm tạo hoặc commit bất kỳ file code nào vượt quá 300 dòng. Mọi file vượt quá 300 dòng đều bị coi là vi phạm tiêu chuẩn kiến trúc dự án.
2. **Chiến Lược Tách File Khi Tiếp Cận Ngưỡng 300 Dòng**:
   - **Python (Backend / Gateway)**: 
     - Phân rã theo Domain Service hoặc Router: Tạo các sub-module trong thư mục riêng (ví dụ: `routes/portal/`, `modules/`).
     - Tách rời Schema (`models.py`), Tiện ích chung (`utils.py`), Nghiệp vụ tính toán (`engine.py`).
     - Dùng **Facade Shim** (`__all__`) nếu cần giữ tương thích ngược cho các import cũ.
   - **JavaScript / CSS (Frontend Portal)**:
     - Tách theo Component / Tab / Trách nhiệm (UI state, Action handlers, Form validations, Chat SSE).
     - Tách CSS theo module chức năng (`portal_base.css`, `portal_layout.css`, `portal_components.css`...).
   - **PHP (WordPress Plugin)**:
     - Sử dụng **PHP Trait Composition Pattern** trong thư mục `includes/` (`class-*-trait.php`), mỗi Trait chỉ đảm nhiệm đúng một phạm vi hẹp (Routes, Auth, SEO, Admin, Helpers).
3. **Trách Nhiệm Kiểm Tra (Compliance Enforcement)**:
   - Trước khi lưu file hoặc hoàn thành task, luôn kiểm tra số dòng lệnh bằng: `wc -l <file_path>`.
   - Nếu số dòng chạm mốc 250 – 300 dòng, phải lập tức chia nhỏ thành các file vệ tinh chuyên trách (Single Responsibility Principle).

---

## 3. Kiến Trúc 4 Trụ Cột Tái Cấu Trúc (The 4 Refactoring Pillars)

```
┌────────────────────────────────────────────────────────────────────────┐
│             KIẾN TRÚC MODULAR HÓA TOÀN DIỆN (SOP 23)                   │
├───────────────────────────────────┬────────────────────────────────────┤
│ 1. FastAPI Gateway Router Model   │ 2. Seed Intelligence Micro-Modules │
│ - server.py: 138 dòng             │ - seed_analyzer.py: 37 dòng (Shim) │
│ - models.py: Tập trung schema     │ - 8 module nghiệp vụ chuyên biệt   │
│ - utils.py: JOBS_DB & workers     │ - Giữ tương thích ngược 100%       │
│ - routes/: 6 router theo domain   │ - Tách Onpage, SERP, Keyword, Rep  │
├───────────────────────────────────┼────────────────────────────────────┤
│ 3. Client Portal Component SPA    │ 4. WordPress Plugin Trait Pattern  │
│ - 5 CSS modules theo layout/tab   │ - omni-bridge-connector.php (Core) │
│ - 12 JS modules theo chức năng    │ - includes/: 5 Traits độc lập      │
│ - Độc lập state & vòng đời modal  │ - Tách routes, api, admin, seo, hl │
└───────────────────────────────────┴────────────────────────────────────┘
```

---

## 4. Chi Tiết Từng Thành Phần Đã Modular Hóa

### A. Tầng Backend Gateway (`agent-engine/`)
1. **`server.py` (Core Gateway)**:
   - Giảm từ 2.536 dòng xuống còn **138 dòng**.
   - Chỉ giữ vai trò App Factory, CORS middleware, API Key auth middleware, Cache-busting middleware, static files mount và đăng ký APIRouters.
2. **`models.py` (Data Layer Schema)**:
   - Gom toàn bộ class kế thừa `BaseModel` của Pydantic: `OnboardingRequest`, `CampaignRequest`, `BatchCampaignRequest`, `GSCPerformanceRequest`, `PortalProjectCreate`...
3. **`utils.py` (Execution Utilities)**:
   - Lưu trữ state bộ nhớ `JOBS_DB` và các hàm worker chạy nền: `background_campaign_worker`, `background_batch_worker`.
4. **Thư mục `routes/` (Domain-Driven Routing)**:
   - `onboarding.py`: Trinh sát khởi tạo dự án và bộ nhớ ngữ cảnh RAG.
   - `campaign.py`: Dây chuyền kích hoạt chiến dịch bài viết/sản phẩm và tra cứu job tiến độ.
   - `saas.py`: Quản trị đa khách hàng (Tenants, Users, Projects, UVP selection).
   - `cms.py`: Cấu hình kết nối Omni Bridge Connector và tải file zip.
   - `gsc.py`: Toàn bộ luồng Google Search Console OAuth2, property picker và query performance.
   - `portal/` (Sub-routers chuyên sâu cho UI):
     - `projects.py`: CRUD dự án Client Portal.
     - `analysis.py`: Đột kích 6 trụ cột On-page Web Đích & Đối thủ SERP.
     - `keywords.py`: Quản lý Ma trận SERP & quét từ khóa đơn lẻ.
     - `chat.py`: Khung chat SSE Streaming Copilot và lịch sử hội thoại vĩnh cửu.
     - `settings.py`: Quản lý hồ bơi Serper API keys và kết nối AI BYOK.

---

### B. Tầng Bộ Máy Trinh Sát Đối Thủ Mẫu (`seed_analyzer.py`)
Áp dụng **Facade Shim Pattern** để đảm bảo tất cả file import cũ (`from seed_analyzer import ...`) vẫn hoạt động hoàn hảo mà không phải sửa lại code gọi:
- `seed_analyzer.py`: Shim facade (37 dòng) import và export `__all__` từ 8 micro-modules.
- `seed_utils.py`: Hằng số headers, stopwords tiếng Việt, modifiers ý định.
- `seed_site_analyzer.py`: Cào HTML, đo TTFB, nhận diện CMS, Robots.txt, Sitemap.xml.
- `seed_competitor_analyzer.py`: Bóc tách 6 trụ cột, Content Freshness, Entity sameAs, Domain Age & Citations, Moz DA.
- `seed_head_to_head.py`: Ma trận đối đầu trực diện 10 chỉ số (Head-to-Head Table).
- `seed_keyword_extractor.py`: Bóc tách từ khóa 4 tầng (Head, Specs Landing, Model, Intent Long-tail).
- `seed_serp.py`: Live SERP scraping, tổng hợp Master Keyword Pool (>1.200 từ) và Ma trận 60 từ chiến lược.
- `seed_report.py`: Sinh Báo cáo Khởi động Dự án (Executive Kickoff Briefing).
- `seed_orchestrator.py`: Điều phối dây chuyền trinh sát mẫu liên tục.

---

### C. Tầng Giao Diện Client Portal (`agent-engine/portal/`)
Phân rã file nguyên khối `portal.js` và `portal.css` thành các component nhỏ:
- **5 CSS Modules**:
  - `portal_base.css`: Design tokens, typography, root variables, toast.
  - `portal_layout.css`: Sidebar, Topbar, Tabs routing, responsive.
  - `portal_chat.css`: Khung chat Copilot, streaming bubble, typing indicator.
  - `portal_competitors.css`: Thẻ 10 đối thủ, badge CMS, DA, TTFB, buttons.
  - `portal_components.css`: Modals, Pills, Bảng số liệu, Phân trang.
- **12 JavaScript Modules**:
  - `portal_core.js`: Global state, route tabs, escapeHtml.
  - `portal_project.js`: Tải và chuyển đổi dự án.
  - `portal_project_forms.js`: Form tạo mới & sửa dự án.
  - `portal_chat.js`: Streaming SSE Chat Copilot & conversation memory.
  - `portal_my_site.js`: Báo cáo On-page Web Đích (Điểm 100đ, 6 thẻ kỹ thuật).
  - `portal_competitors.js`: Lưới 10 đối thủ SERP (SEO vs ADS).
  - `portal_competitor_audit.js`: Modal phân tích đối thủ mẫu.
  - `portal_deep_audit.js`: Modal Đột kích 6 Trụ cột & Bảng đối đầu.
  - `portal_uvp_cms.js`: Lựa chọn 4 UVP & Cấu hình Omni Bridge.
  - `portal_keywords.js`: Dual View (Ma trận 60 từ & Kho 1.279 từ), bộ lọc 4 chiều, phân trang số.
  - `portal_keywords_actions.js`: Quét lại SERP, đẩy sang Copilot, xóa từ khóa.
  - `portal_settings.js`: Cài đặt Serper Key Pool & AI BYOK.

---

### D. Tầng WordPress Plugin (`plugins/omni-bridge-connector/`)
Áp dụng **PHP Trait Composition Pattern**:
- `omni-bridge-connector.php`: Main class `Omni_Bridge_Connector` chỉ khai báo `use` các Traits.
- `includes/class-routes-trait.php`: Đăng ký REST endpoints (`/health`, `/publish`, `/gsc/config`).
- `includes/class-api-trait.php`: Xác thực bảo mật header `X-Omni-Bridge-Key`.
- `includes/class-admin-trait.php`: Giao diện Admin Settings trong WP-Admin.
- `includes/class-seo-trait.php`: Tích hợp Rank Math SEO & Yoast SEO meta.
- `includes/class-helpers-trait.php`: Media Sideloading & WooCommerce handler.

---

## 5. Quy Chuẩn Kiểm Tra Sau Khi Refactor (Verification Checklist)

Mỗi khi thực hiện tái cấu trúc, bắt buộc kiểm tra:
1. **Tuân Thủ Ngưỡng 300 Dòng Mã (Line Count Compliance)**: Dùng `wc -l` kiểm tra mọi file mới hoặc file sửa đổi, tuyệt đối không cho phép file nào vượt quá 300 dòng.
2. **FastAPI Route Inspection**: Đảm bảo tất cả endpoint trong `routes/` được `app.include_router(...)` đầy đủ.
3. **Reverse Compatibility**: Không phá vỡ các hàm đã định nghĩa trong `__all__` của facade shim.
4. **DOM Event Binding**: Toàn bộ hàm gọi từ inline HTML (`onclick`, `onchange`) trên Portal UI phải được gắn vào `window.*` (như `window.setKeywordViewMode = setKeywordViewMode;`).
5. **Cache-Busting Header**: Giữ nguyên middleware `Cache-Control: no-cache, no-store, must-revalidate` và cập nhật version `?v=...` trên `index.html`.
