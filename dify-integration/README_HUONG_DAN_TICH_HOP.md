# Hướng Dẫn Triển Khai Dify Độc Lập — Universal Marketing AI Portal

> **Phiên bản 2.0** — Dify chạy hoàn toàn độc lập, không phụ thuộc WordPress hay CrewAI Microservice.

---

## KIẾN TRÚC HỆ THỐNG MỚI

```
┌──────────────────────────────────────────────┐
│  Dify AI Portal (port 8090)                  │
│  ├── CMO Agent App (Agent Chat)              │
│  │   ├── Knowledge Base (14 files RAG)       │
│  │   ├── DuckDuckGo Search (trinh sát SERP)  │
│  │   └── WP Publisher Tool (HTTP Request)    │
│  └── Workflow: SEO Article Pipeline          │
│      ├── Node 1: Keyword Research            │
│      ├── Node 2: Competitor Recon            │
│      ├── Node 3: Outline + HITL Approval     │
│      ├── Node 4: Full Article Writing        │
│      ├── Node 5: QA Review                  │
│      └── Node 6: WP Draft Publish           │
└──────────────────────────────────────────────┘
         ↕ X-Omni-Bridge API Key (Omni Bridge Plugin)
┌──────────────────────────────────────────────┐
│  WordPress (port 8080)                       │
│  ├── MariaDB                                 │
│  └── Redis Cache                             │
└──────────────────────────────────────────────┘
```

---

## CẤU TRÚC THƯ MỤC (MODULAR CLEAN ARCHITECTURE)

```
dify-integration/
├── knowledge/                                  # BỘ TRI THỨC NẠP VÀO DIFY DATASETS & RAG (14 SOPS)
│   ├── 01_sop12_content_writing_rules.md      # SOP 12: Quy chuẩn viết bài SEO E-E-A-T
│   ├── 02_sop11_image_processing_standards.md # SOP 11: Chuẩn hóa ảnh (RemBG, WebP)
│   ├── 03_xiaomi_product_specs_and_pricing.md # Bảng giá & thông số Xiaomi chuẩn hóa
│   ├── 04_sop16_competitor_recon_autopilot.md # SOP 16: Giải mã Đối thủ Mẫu & Trinh sát SERP 6 Trụ Cột
│   ├── 05_marketing_department_agents_roles.md# Cơ cấu Phòng Marketing AI & vai trò W1-W6
│   ├── 06_sop17_onboarding_saas_rag_architecture.md # SOP 17: Onboarding SaaS & RAG Architecture
│   ├── 07_sop18_cross_competitor_and_product_batch_pipeline.md # SOP 18: Dây chuyền cào đa đối thủ & batch
│   ├── 08_sop19_gsc_oauth_and_cms_bridge.md   # SOP 19: Kết nối Google Search Console OAuth & CMS Bridge
│   ├── 09_sop20_custom_client_portal_and_persistent_chat_architecture.md # SOP 20: Client Portal & Persistent Chat
│   ├── 10_sop21_deep_competitor_intelligence_and_zero_cost_recon.md # SOP 21: Đột kích 6 trụ cột & SERP 0đ
│   ├── 11_sop22_competitor_keyword_matrix_and_master_keyword_pool.md # SOP 22: Master Pool (>1.200 từ) & Phân trang số
│   ├── 12_sop23_modular_clean_architecture_and_refactoring.md # SOP 23: Tái cấu trúc Modular Clean Architecture
│   ├── 13_sop24_license_management_and_hybrid_cloudflare_bypass.md # SOP 24: Quản lý Bản quyền & Vượt Cloudflare
│   └── 14_sop25_autonomous_boardroom_debate_and_claude_seo_parity.md # SOP 25: Hội đồng Tranh biện C-Suite & Chuẩn Claude 10x
├── tools/                                      # SCHEMA CÔNG CỤ CHO DIFY & UTILITIES
│   ├── marketing_engine_tool.yaml              # Autonomous Marketing Engine v3.0 OpenAPI Spec
│   ├── wp_omni_bridge_tool.yaml                # WP Publisher Tool (Omni Bridge Connector v1.3.0 Spec)
│   ├── register_wp_publisher_tool.py           # Tự động đăng ký tool WordPress Publisher vào Dify DB
│   ├── automate_gsc_onboarding.py              # Script tự động hóa cài đặt & kết nối GSC
│   └── bootstrap_gsc.py                        # Script bootstrap GSC DDL & mã hóa token
├── agent-engine/                               # TẦNG MICROSERVICE BACKEND (:8085) & PORTAL UI
│   ├── server.py                               # FastAPI Gateway Server tinh gọn (138 dòng)
│   ├── models.py                               # Khai báo tập trung toàn bộ Pydantic Models
│   ├── utils.py                                # Bộ nhớ tác vụ nền JOBS_DB & Background Workers
│   ├── routes/                                 # CÁC API ROUTER THEO CHỨC NĂNG RIÊNG BIỆT
│   │   ├── onboarding.py                       # /api/v1/onboarding/analyze & /project/memory
│   │   ├── campaign.py                         # /api/v1/campaign, /product, /article, /batch
│   │   ├── saas.py                             # /api/v1/saas/tenants, /users, /projects
│   │   ├── cms.py                              # /api/v1/projects/{id}/cms-bridge
│   │   ├── gsc.py                              # Google Search Console OAuth & Analytics
│   │   └── portal/                             # SUB-ROUTERS CHO CLIENT PORTAL (14 ROUTERS + SERVICE ≤300 DÒNG)
│   │       ├── projects.py                     # CRUD Dự án Client Portal (138 dòng)
│   │       ├── project_kickoff_service.py      # Điều phối khởi động dự án & ma trận từ khóa (170 dòng)
│   │       ├── analysis.py                     # Đột kích 6 trụ cột Web đích & Đối thủ
│   │       ├── rescan.py                       # Quét & xác minh 10 đối thủ Live SERP 100% Online
│   │       ├── keywords.py                     # Tái tạo & Quét từ khóa Ma trận SERP
│   │       ├── chat.py                         # Streaming SSE Chat Copilot
│   │       ├── settings.py                     # Cài đặt Serper Key Pool & AI BYOK
│   │       ├── settings_test.py                # Kiểm tra kết nối Serper, Moz & AI BYOK
│   │       ├── admin.py                        # Master Admin Quản trị toàn hệ thống
│   │       ├── agentic.py                      # Kích hoạt Agentic Workflow & Autonomous tasks
│   │       ├── bridge.py                       # Đồng bộ trạng thái CMS Bridge từ xa
│   │       ├── brief_and_drift.py              # Kiểm soát trôi dạt nội dung & Báo cáo Brief
│   │       ├── intelligence.py                 # Tình báo chuyên sâu & xếp hạng SERP
│   │       ├── license.py                      # Xác thực & kích hoạt License Key bản quyền
│   │       └── seed.py                         # Điều phối & phân tích đối thủ mẫu
│   ├── seed_analyzer.py                        # Facade Shim điều phối trinh sát đối thủ mẫu (38 dòng)
│   ├── seed_site_analyzer.py                   # Cào Onpage, đo TTFB, CMS, Robots, Sitemap
│   ├── seed_competitor_analyzer.py             # Phân tích 6 trụ cột, Freshness, Entity
│   ├── seed_head_to_head.py                    # Ma trận đối đầu trực diện 10 chỉ số
│   ├── seed_keyword_extractor.py               # Bóc tách từ khóa 4 tầng (Head, Specs, Model, Intent)
│   ├── seed_serp.py                            # Quét Live Google SERP & Master Keyword Pool
│   ├── seed_report.py                          # Sinh Báo cáo Khởi động (Kickoff Briefing)
│   ├── seed_orchestrator.py                    # Điều phối dây chuyền trinh sát mẫu
│   ├── seed_reconnaissance.py                  # Trinh sát đối thủ mẫu liên hoàn & phát hiện tranh chấp thương hiệu
│   ├── seed_deep_audit.py                      # Đột kích chuyên sâu 6 trụ cột đa luồng
│   ├── agent_orchestrator.py                   # Facade Shim dây chuyền 5 Agent (220 dòng)
│   │   ├── orchestrator_llm.py                 # Kết nối 9Router LLM & Fallback models (131 dòng)
│   │   ├── orchestrator_content.py             # Phân rã bài viết 8 phần & bóc tách heading (157 dòng)
│   │   └── orchestrator_publisher.py           # Xuất bản kép WordPress Draft & REST API (112 dòng)
│   ├── scraper_tool.py                         # Facade Shim cào đa đối thủ thích ứng (54 dòng)
│   │   ├── scraper_core.py                     # Cào JSON-LD Schema.org & Heuristic DOM (206 dòng)
│   │   ├── scraper_media.py                    # Bóc tách và tải hình ảnh đối thủ (112 dòng)
│   │   └── scraper_recon.py                    # Bóc tách ma trận giá bán thị trường (80 dòng)
│   ├── serp_discovery_tool.py                  # Facade Shim truy vấn SERP & UVP (85 dòng)
│   │   ├── serp_crawler.py                     # Cào DuckDuckGo SERP & xác minh HTTP đối thủ (292 dòng)
│   │   ├── serp_onboarding.py                  # Điều phối phân tích Onboarding Gap Analysis (224 dòng)
│   │   ├── serp_site_inspector.py              # Đánh giá Onpage website mục tiêu (191 dòng)
│   │   └── serp_uvp_analyzer.py                # Sinh gợi ý lợi thế cạnh tranh UVP độc quyền (216 dòng)
│   ├── image_staging_service.py                # Facade Shim xử lý hình ảnh độc lập (104 dòng)
│   │   ├── image_storage.py                    # Quản lý thư mục lưu trữ & responsive sizes (102 dòng)
│   │   ├── image_rembg.py                      # Tách nền phông cũ U2Net/Rembg (35 dòng)
│   │   ├── image_scene.py                      # Nhận diện bối cảnh & ghép không gian sống (231 dòng)
│   │   └── image_watermark.py                  # Đóng mộc bản quyền & Soft Drop Shadow (156 dòng)
│   ├── cms_autofix_service.py                  # Tự động khắc phục lỗi On-page từ xa qua CMS Bridge (119 dòng)
│   ├── orchestration_manager.py                # Quản lý 3 chế độ điều phối Dify + CrewAI + 9Router
│   ├── output_adapter.py                       # Chuẩn hóa đầu ra bài viết & QA 7 tầng
│   ├── retry_handler.py                        # Fallback chain & retry tự động chống timeout
│   ├── state_manager.py                        # Quản lý trạng thái thực thi tác vụ bền vững (DB/File)
│   ├── bridge_task_service.py                  # Hàng đợi Task Queue & Cloudflare Bypass (SOP 24)
│   ├── license_service.py                      # Xác thực License Key, 7 ngày Trial & Domain FQDN Guard
│   ├── gsc_service.py                          # Dịch vụ OAuth Google Search Console & Analytics
│   ├── seed_serp_discovery.py                  # Tiện ích bóc tách SERP & PAA đa tầng
│   ├── seed_utils.py                           # Hằng số, stopwords & header HTTP chuẩn hóa
│   ├── db_service.py                           # Facade Shim kết nối PostgreSQL marketing_saas (43 dòng)
│   ├── db_base.py                              # Connection pool, UUID validation, Tenant, User, Settings
│   ├── db_projects.py                          # Project CRUD, Active Project, Dify Conversation ID
│   ├── db_intelligence.py                      # Đối thủ SERP, Deep Audit 6 trụ cột, Báo cáo Gap Analysis
│   ├── db_gsc.py                               # Google Search Console OAuth States, Connections, Performance Sync
│   ├── rag_service.py                          # Dify RAG Knowledge Base Weaviate Connector
│   ├── schema.sql                              # DDL Multi-tenant SaaS (10 bảng: Tenants, Projects, Boardroom...)
│   └── portal/                                 # CLIENT PORTAL FRONTEND (MODULAR SPA — 25 MODULES ≤300 DÒNG)
│       ├── index.html                          # Giao diện chính Client Workspace & Modal System
│       ├── admin.html                          # Giao diện Master Admin Quản trị hệ thống
│       ├── portal_base.css                     # CSS Module 1: Design tokens, typography, root variables
│       ├── portal_layout.css                   # CSS Module 2: Sidebar, Topbar, Tabs routing, responsive
│       ├── portal_chat.css                     # CSS Module 3: Khung chat Copilot, bubbles, typing indicator
│       ├── portal_competitors.css              # CSS Module 4: Thẻ 10 đối thủ, badge CMS, DA, TTFB
│       ├── portal_components.css               # CSS Module 5: Modals, Pills, Bảng số liệu, Phân trang số
│       ├── portal_feedback.css                 # CSS Module 6: Toast Notification, Kickoff Briefing, Filter Pills
│       ├── portal_admin.css                    # CSS Module 7: Dashboard Master Admin
│       ├── portal_core.js                      # JS Module 1: Global state, route tabs, escapeHtml, toast
│       ├── portal_project.js                   # JS Module 2: Tải và chuyển đổi dự án
│       ├── portal_project_forms.js             # JS Module 3: Form tạo mới & chỉnh sửa dự án
│       ├── portal_project_loader.js            # JS Module 4: Điều phối nạp trí tuệ dự án toàn diện
│       ├── portal_chat.js                      # JS Module 5: Streaming SSE Chat Copilot & conversation memory
│       ├── portal_chat_commands.js             # JS Module 6: Phím tắt & lệnh điều phối chuyên gia
│       ├── portal_chat_brief_agentic.js        # JS Module 7: Tranh biện C-Suite & Agentic brief planner
│       ├── portal_my_site.js                   # JS Module 8: Báo cáo On-page Web Đích (Điểm 100đ, 6 thẻ kỹ thuật)
│       ├── portal_my_site_clusters.js          # JS Module 9: Dấu chân từ khóa 4 tầng & phân tích khoảng trống
│       ├── portal_my_site_actions.js           # JS Module 10: Tự động sửa On-page qua Omni Bridge & quét lại website
│       ├── portal_competitors_seed.js          # JS Module 11: Phân tích đối thủ mẫu & gợi ý Silo
│       ├── portal_competitors.js               # JS Module 12: Lưới 10 đối thủ SERP (SEO vs ADS)
│       ├── portal_competitor_audit.js          # JS Module 13: Modal phân tích đối thủ mẫu
│       ├── portal_deep_audit_view.js           # JS Module 14: Render đối đầu trực diện 10 chỉ số & modal 6 trụ cột
│       ├── portal_deep_audit.js                # JS Module 15: Controller đột kích 6 trụ cột đối thủ
│       ├── portal_cms.js                       # JS Module 16: Cấu hình Omni Bridge & CMS Connector
│       ├── portal_uvp.js                       # JS Module 17: Lựa chọn 4 phương án UVP thương hiệu
│       ├── portal_keywords_nav.js              # JS Module 18: Điều hướng & bộ lọc ma trận từ khóa
│       ├── portal_keywords_render.js           # JS Module 19: Render bảng ma trận & danh sách master pool
│       ├── portal_keywords_pool.js             # JS Module 20: Bảng tra cứu & lọc 4 chiều cho 1.279 từ khóa bóc tách
│       ├── portal_keywords.js                  # JS Module 21: Dual View (Ma trận 60 từ & Kho 1.279 từ), phân trang số
│       ├── portal_keywords_actions.js          # JS Module 22: Quét lại SERP, đẩy sang Copilot, xóa từ khóa
│       ├── portal_license.js                   # JS Module 23: Kiểm tra hạn dùng & modal kích hoạt bản quyền
│       ├── portal_admin.js                     # JS Module 24: Logic Dashboard Master Admin
│       └── portal_settings.js                  # JS Module 25: Cài đặt Serper Key Pool & AI BYOK
├── autopilot/                                  # ĐỘNG CƠ TỰ HÀNH 30 NGÀY
│   ├── autopilot_runner.py                     # Runner nguyên bản qua Docker CLI container
│   ├── autopilot_runner_v2.py                  # Runner nâng cấp điều phối đa mode & REST API Omni Bridge
│   ├── wp_publisher_cli.php                    # Direct WP-CLI Publisher
│   └── seo_calendar_30days.json                # Lịch biên tập 30 ngày (4 search intents)
├── dsl/                                        # 7 AGENT APPS & WORKFLOW PIPELINE (DIFY)
│   ├── cmo_agent_app_dsl.yml                   # 👑 CMO AI Copilot v3.0 (Đa ngành nghề)
│   ├── w1_market_intel_dsl.yml                 # 🕵️ W1: Tình báo SERP & Đối thủ
│   ├── w2_data_analyst_dsl.yml                 # 📊 W2: Phân tích Intent A/B/C/D & Dữ liệu
│   ├── w3_seo_strategist_dsl.yml               # 🏛️ W3: Kiến trúc sư Silo & Topic Clusters
│   ├── w4_senior_copywriter_dsl.yml            # ✍️ W4: Viết bài SOP 12 (0% sáo rỗng AI)
│   ├── w5_qa_publisher_dsl.yml                 # 🛡️ W5: Kiểm định 7 tầng & Xuất bản WP Draft
│   ├── w6_social_repurposer_dsl.yml            # 📱 W6: Tái chế TikTok 60s & Facebook Post
│   └── full_marketing_pipeline_workflow_dsl.yml # ⚡ Workflow Pipeline tự động khép kín
├── plugins/                                    # WORDPRESS REMOTE CONNECTOR PLUGIN
│   ├── omni-bridge-connector.zip               # Gói zip cài đặt trực tiếp lên WP
│   └── omni-bridge-connector/                  # MÃ NGUỒN MODULAR HÓA THEO 9 TRAITS
│       ├── omni-bridge-connector.php           # Main plugin loader bootstrap
│       └── includes/                           # Thư mục 9 Traits độc lập
│           ├── class-routes-trait.php          # REST routes registration & hooks
│           ├── class-api-trait.php             # X-Omni-Bridge-Key authentication
│           ├── class-admin-trait.php           # WP-Admin settings dashboard
│           ├── class-seo-trait.php             # Rank Math & Yoast SEO meta binding
│           ├── class-helpers-trait.php         # Media sideloading & WooCommerce handler
│           ├── class-license-trait.php         # Xác thực License Key & kiểm tra tính hợp lệ
│           ├── class-silo-legal-trait.php      # Quản lý Silo pages, chính sách & trang pháp lý
│           ├── class-site-repair-trait.php     # Tự động sửa lỗi onpage WordPress từ xa
│           └── class-task-runner-trait.php     # Thực thi tác vụ nền & sync báo cáo
└── README_HUONG_DAN_TICH_HOP.md                # File hướng dẫn tổng quan
```

---

## QUY ĐỊNH BẮT BUỘC: GIỚI HẠN TỐI ĐA ≤ 300 DÒNG MÃ MỖI FILE CODE

> **NGUYÊN TẮC BẤT DI BẤT DỊCH (CARDINAL RULE - SOP 23):**
> **Mọi file code trong toàn bộ hệ thống (Python `.py`, JavaScript `.js`, CSS `.css`, PHP `.php`) tuyệt đối KHÔNG ĐƯỢC VƯỢT QUÁ 300 DÒNG MÃ.**

- **Ngưỡng trần cứng (Hard Limit)**: `≤ 300 dòng` (áp dụng cho cả comment và imports).
- **Ngưỡng cảnh báo (Soft Alert)**: `250 dòng` — Khi chạm mốc 250 dòng, bắt buộc phải phân rã thành các sub-module, component hoặc Trait vệ tinh.
- **Quy chuẩn tách**:
  * **Backend Python**: Tách router theo domain trong `routes/`, tập trung Pydantic schemas vào `models.py`, background jobs vào `utils.py`, micro-engines vào `seed_*`.
  * **Frontend JS/CSS**: Tách theo Component/Tab/Action riêng biệt trong `agent-engine/portal/`.
- **Kiểm soát tuân thủ**: Chạy lệnh `wc -l` trước khi hoàn tất bất kỳ task lập trình nào. Hiện tại 100% tất cả module đang vận hành (toàn bộ 93 module Python, 25 module Frontend JS, 7 CSS, 10 Trait PHP Omni Bridge) đã tuân thủ nghiêm ngặt chuẩn SOP 23 (≤ 300 dòng/file). Cả 4 file lõi (`serp_discovery_tool.py`, `agent_orchestrator.py`, `scraper_tool.py`, `image_staging_service.py`) đều đã được phân rã thành công thành các micro-module chuyên biệt và đóng vai trò Facade Shim tinh gọn.
- **Tương thích Trình duyệt & Inspector**: Gateway `server.py` tự động trả về header `Permissions-Policy: unload=*` và hỗ trợ đầy đủ HTTP `HEAD` methods để dập tắt cảnh báo `[Violation]` của Google Chrome 115+ đối với các công cụ DevTools / Browser Inspector.

### Tenant và hồ sơ thương hiệu

- Onboarding yêu cầu `tenant_id` dạng UUID đã được quản trị hệ thống cấp. Không dùng ID do mô hình tự đoán.
- Có thể đặt `tenant_id` trong form CMO nội bộ; API kiểm tra tenant tồn tại và các API đọc/chốt UVP bắt buộc truyền cùng tenant.
- Endpoint hiện chưa tích hợp danh tính đăng nhập người dùng; chỉ công khai engine sau lớp xác thực của gateway/backend tin cậy. UUID không phải thông tin xác thực.
- Bổ sung hồ sơ khách hàng mục tiêu, dịch vụ chủ lực, mục tiêu chuyển đổi, giọng thương hiệu, bằng chứng quảng cáo và giới hạn tuân thủ vào form onboarding.
- Sau khi cập nhật mã, áp dụng lại `agent-engine/schema.sql` để thêm cột JSONB `projects.brand_profile` (lệnh ALTER trong file có thể chạy lặp lại).

### Hợp đồng dữ liệu đa ngành (bắt buộc cho mọi dự án)

Hệ thống không gắn mặc định với một thương hiệu, ngành hàng, CMS hay loại hình cung cấp nào. Mỗi project phải mang một hồ sơ chuẩn trong `projects.brand_profile`:

```json
{
  "target_language": "vi",
  "industry": "",
  "offer_type": "product | service | saas | content | marketplace",
  "target_audience": "",
  "primary_offers": "",
  "conversion_goal": "",
  "brand_voice": "",
  "claims_evidence": "",
  "compliance_constraints": ""
}
```

`offer_type`, ngành, khách hàng và mục tiêu chuyển đổi là dữ liệu đầu vào; không được suy đoán từ tên miền hoặc từ khóa. `seed_competitor_url` là tùy chọn. Khi không có dữ liệu giá, thông số, review hoặc bằng chứng, pipeline phải trả trạng thái `no_results`/`unverified`, không tự tạo dữ liệu mẫu.

Chiến dịch không có `project_id` chỉ dùng được cho bản nháp thử nghiệm và phải nhận thông tin thương hiệu/website từ request; không được rơi về thương hiệu, hotline, địa chỉ, giá hoặc chính sách của một khách hàng khác.

### SERP và dữ liệu đối thủ

- Onboarding truy vấn DuckDuckGo HTML SERP, ghi nhận URL nguồn/thứ hạng và kiểm tra trạng thái HTTP riêng; kết quả thực tế có thể ít hơn 10.
- Không gọi kết quả là Google SERP và không tự gắn trạng thái online cho dữ liệu do LLM tạo.
- W1 cần tìm URL bằng công cụ tìm kiếm trước, sau đó truyền URL thật vào `researchCompetitor`. Nếu không thu thập được giá, API trả `no_results`, không thay bằng dữ liệu mẫu.
- `status=partial` nghĩa là PostgreSQL đã lưu nhưng đồng bộ RAG hoặc lưu tham chiếu dataset chưa hoàn tất; không thông báo onboarding đã hoàn tất.

---

## BƯỚC 1: KHỞI ĐỘNG DIFY

```bash
cd /home/ubuntu/michinhhang/dify/docker
docker compose up -d
```

Truy cập: `http://<server-ip>:8090/install` để tạo tài khoản admin lần đầu.

> **Lưu ý:** Bước này chỉ khởi động Dify. Để khởi động WordPress (port 8080) và Marketing Engine, chạy thêm:
> ```bash
> cd /home/ubuntu/michinhhang
> docker compose up -d
> ```

---

## BƯỚC 2: NẠP CÁC TẬP TRI THỨC VÀO DIFY KNOWLEDGE

1. Đăng nhập Dify → **Knowledge** → **Create Knowledge**
2. Upload toàn bộ 14 file SOP từ thư mục `dify-integration/knowledge/`:
   - `01_sop12_content_writing_rules.md` → Tên: **SOP 12 - Quy chuẩn viết bài SEO**
   - `02_sop11_image_processing_standards.md` → Tên: **SOP 11 - Quy chuẩn ảnh sản phẩm**
   - `03_xiaomi_product_specs_and_pricing.md` → Tên: **Bảng giá & thông số Xiaomi**
   - `04_sop16_competitor_recon_autopilot.md` → Tên: **SOP 16 - Giải mã Đối thủ Mẫu & Trinh sát SERP 6 Trụ Cột**
   - `05_marketing_department_agents_roles.md` → Tên: **Cơ cấu Phòng Marketing AI**
   - `06_sop17_onboarding_saas_rag_architecture.md` → Tên: **SOP 17 - Onboarding SaaS RAG Architecture**
   - `07_sop18_cross_competitor_and_product_batch_pipeline.md` → Tên: **SOP 18 - Cross-Competitor Batch Pipeline**
   - `08_sop19_gsc_oauth_and_cms_bridge.md` → Tên: **SOP 19 - GSC OAuth & CMS Bridge**
   - `09_sop20_custom_client_portal_and_persistent_chat_architecture.md` → Tên: **SOP 20 - Client Portal & Persistent Chat**
   - `10_sop21_deep_competitor_intelligence_and_zero_cost_recon.md` → Tên: **SOP 21 - Đột kích 6 Trụ cột & SERP 0đ**
   - `11_sop22_competitor_keyword_matrix_and_master_keyword_pool.md` → Tên: **SOP 22 - Master Keyword Pool & Phân trang số**
   - `12_sop23_modular_clean_architecture_and_refactoring.md` → Tên: **SOP 23 - Modular Clean Architecture (≤300 dòng)**
   - `13_sop24_license_management_and_hybrid_cloudflare_bypass.md` → Tên: **SOP 24 - Quản lý Bản quyền & Vượt Cloudflare**
   - `14_sop25_autonomous_boardroom_debate_and_claude_seo_parity.md` → Tên: **SOP 25 - Hội đồng Tranh biện C-Suite & Chuẩn Claude 10x**
3. Mỗi file: Indexing Mode = **Automatic** | Retrieval = **Hybrid Search**
4. Bấm **Save & Process** và chờ indexing hoàn tất.

---

## BƯỚC 3: TẠO CUSTOM TOOL - WORDPRESS PUBLISHER (OMNI BRIDGE)

1. Dify → **Tools** → **Custom** → **Create Custom Tool**
2. Điền:
   - **Tên**: `WordPress Publisher`
   - **Schema**: Import file `dify-integration/tools/wp_omni_bridge_tool.yaml`
   - **Server URL**: Chọn `http://172.20.0.1:8080` (Dify Docker Gateway kết nối sang WordPress cổng 8080)
3. Cấu hình xác thực:
   - Loại: **API Key (Header)**
   - Header: `X-Omni-Bridge-Key`
   - Key: Lấy từ WP Admin → Settings → Omni Bridge → Copy API Secret Key
4. Cài đặt plugin Omni Bridge Connector (file: `plugins/omni-bridge-connector.zip`) trên WordPress trước khi test:
   - WP Admin → Plugins → Add New → Upload Plugin → Chọn file zip → Activate
   - Vào **Settings → Omni Bridge** để lấy **API Secret Key**
5. Bấm **Save** → Test thử tool `get_wp_categories`.

> 💡 **Trạng thái thực tế:** Tool `WordPress Publisher` đã được hệ thống tự động đăng ký sẵn vào Dify DB (`tools/register_wp_publisher_tool.py`). Các Agent (đặc biệt W5) đã có thể gọi trực tiếp. Bạn có thể kiểm tra danh sách Custom Tools tại **Dify → Tools → Custom**.
>
> 🔄 **Thay thế REST API cũ:** Tool này dùng `/wp-json/omni-bridge/v1/*` (API key auth), **không dùng Application Password hay Basic Auth nữa**. REST API trực tiếp (`/wp-json/wp/v2/`) đã được loại bỏ.

---

## BƯỚC 3b: TẠO CUSTOM TOOL - MARKETING ENGINE (BẮT BUỘC)

**Agent CMO và W1 cần tool này. Nếu bỏ qua, import sẽ lỗi mất tool `researchCompetitor`, `analyzeProjectOnboarding`...**

1. Dify → **Tools** → **Custom** → **Create Custom Tool**
2. Điền:
   - **Tên**: `Marketing Engine` (chính xác từng chữ để match với provider_name trong DSL)
   - **Schema**: Import file `dify-integration/tools/marketing_engine_tool.yaml`
   - **Server URL**: Chọn `http://marketing-agent-engine:8085` (Mạng nội bộ Docker)
3. Cấu hình xác thực:
   - Loại: **Bearer Token**
   - Token: Lấy giá trị biến `ENGINE_API_KEY` trong file `dify-integration/.env`
4. **Khởi động Marketing Engine** (nếu container đang Restarting):
   ```bash
   cd /home/ubuntu/michinhhang
   docker compose build marketing-agent-engine
   docker compose up -d marketing-agent-engine
   ```
   > ⚠️ Nếu lỗi `ModuleNotFoundError: No module named cryptography`, rebuild image như trên.

## BƯỚC 4: IMPORT CÁC AGENT VÀO DIFY STUDIO

> ⚠️ **Cấu hình Model trước khi import:** Tất cả DSL dùng model `langgenius/openai_api_compatible/openai_api_compatible` với name `combo-creater`. Thêm provider này tại **Settings → Model Providers → OpenAI API Compatible** trước khi import. Nếu chưa cấu hình, import sẽ lỗi "model not found".
>
> 💡 **Liên kết Knowledge Base sau khi import:** Sau khi import `cmo_agent_app_dsl.yml`, mở Agent trong Studio ➜ tại mục **Knowledge (Ngữ cảnh tri thức)**, xóa dataset ID cũ nếu có và chọn Knowledge Base bạn đã tạo ở **BƯỚC 2** (chứa 14 file SOP) ➜ Bấm **Publish (Phát hành)**.

Bạn có thể import từng Agent hoặc toàn bộ phòng Marketing vào Dify (**Studio ➜ Mũi tên cạnh "Create from Blank" ➜ Import DSL file**):

| Tên File DSL | Tên Agent trong Dify | Vai Trò & Chức Năng |
|---|---|---|
| `cmo_agent_app_dsl.yml` | **👑 CMO AI Copilot** | Giám đốc Marketing điều phối toàn diện, nhận diện thương hiệu |
| `w1_market_intel_dsl.yml` | **🕵️ W1 - Market Intel Agent** | Trinh sát SERP, bóc tách cấu trúc Heading đối thủ, tìm Content Gap |
| `w2_data_analyst_dsl.yml` | **📊 W2 - Data Analyst Agent** | Phân loại Search Intent (A/B/C/D), ưu tiên P1/P2/P3, tối ưu CTR |
| `w3_seo_strategist_dsl.yml` | **🏛️ W3 - SEO Strategist Agent** | Kiến trúc Silo 3 tầng, ma trận Internal Link 4 hướng, chống cannibalization |
| `w4_senior_copywriter_dsl.yml` | **✍️ W4 - Senior Copywriter Agent** | Viết bài 1500-2500 từ chuẩn SOP 12, 0% từ sáo rỗng AI, chuyển hóa CRO |
| `w5_qa_publisher_dsl.yml` | **🛡️ W5 - QA Publisher Agent** | Kiểm định 7 tầng ($\ge 75$đ), xuất bản bài nháp (Draft) lên WordPress |
| `w6_social_repurposer_dsl.yml` | **📱 W6 - Social Media Agent** | Tái chế kịch bản video TikTok 60s, bài đăng Fanpage Facebook |

---

## BƯỚC 5: IMPORT WORKFLOW TỰ ĐỘNG HÓA TOÀN BỘ (MULTI-AGENT PIPELINE)

Nếu muốn chạy một chuỗi tự động khép kín không cần chat từng bước:
1. Dify ➜ **Studio** ➜ **Import DSL file**.
2. Chọn file: **`dsl/full_marketing_pipeline_workflow_dsl.yml`**.
3. Dify sẽ tạo Workflow gồm W1 → W2 → W3 → W4 → W5. Chỉ khi QA đạt mới tạo bản nháp; W6 chỉ tái chế nội dung đã đạt QA:
   * **Node 1: Start** (Nhập từ khóa, link đối thủ, ngành hàng)
   * **Node 2: W1 Intel** (Cào và phân tích khoảng trống nội dung đối thủ)
   * **Node 3: W2 Data Analyst** (Phân tích intent, ưu tiên và chống ăn thịt từ khóa)
   * **Node 4: W3 Strategy** (Lập dàn ý Silo và tháp Heading)
   * **Node 5: W4 Writer** (Chấp bút bài viết chuẩn SOP 12)
   * **Node 6: W5 QA** (Chấm điểm chất lượng 7 tầng; chỉ tạo draft khi đạt)
   * **Node 7: W6 Social** (Chỉ tái chế khi QA_STATUS=PASS)
   * **Node 8: End** (Trả về bài viết + báo cáo QA + nội dung social)

---

> ⚠️ **Lưu ý Workflow (BƯỚC 5):** Toàn bộ node W1-W6 trong workflow đều là `type: llm` **không có tool cấu hình**. Workflow chỉ sinh văn bản qua LLM, không gọi DuckDuckGo, Marketing Engine hay WordPress Publisher. Để workflow thực sự tạo WordPress draft, cần cấu hình node W5 thành `type: custom` với tool `create_wp_post_draft` từ provider "WordPress Publisher".

## BƯỚC 6: LẤY DIFY API KEY ĐỂ TÍCH HỢP VỚI CLIENT PORTAL
 
Sau khi import và publish **👑 CMO AI Copilot**:
1. Vào Dify Studio → Mở App **CMO AI Copilot** → Menu bên trái chọn **API Access**.
2. Bấm **API Key** → **Create New Secret Key** → Copy token (dạng `app-xxxxxxxxxxxxxxxx`).
3. Mở file `dify-integration/.env`, dán token vào biến:
   ```env
   DIFY_APP_API_KEY=app-xxxxxxxxxxxxxxxx
   ```
   *(Bước này giúp khung chat streaming AI trên Client Portal cổng :8085 giao tiếp với Dify mà không bị lỗi 401).*

4. (Tùy chọn) Nếu muốn gọi Dify từ ứng dụng bên ngoài qua curl:
   - Endpoint: `http://<server-ip>:8090/v1/chat-messages`
   - Header: `Authorization: Bearer app-xxxxxxxxxx`

**Ví dụ curl:**
```bash
curl -X POST http://<server-ip>:8090/v1/chat-messages \
  -H "Authorization: Bearer app-xxxxxxxxxx" \
  -H "Content-Type: application/json" \
  -d '{
    "query": "Viết brief SEO cho từ khóa: tivi xiaomi 55 inch 2025",
    "response_mode": "streaming",
    "conversation_id": "",
    "user": "marketer-01"
  }'
```

---

## GHI CHÚ PORTS

| Service | Port | Ghi chú |
|---|---|---|
| Dify (Nginx) | 8090 | Giao diện chính Dify |
| Dify API | 5001 | Internal, qua Nginx |
| Marketing Engine | 8085 | FastAPI Gateway & Client Portal UI |
| WordPress | 8080 | WP Admin + REST API (Omni Bridge) |
| PostgreSQL | 5432 | Database Multi-tenant marketing_saas |
| MariaDB (WP) | 3306 | Internal only |
| Redis (WP) | 6379 | Internal only |

---

## THAY ĐỔI VÀ TIẾN HÓA KIẾN TRÚC

| Thành phần | Phiên bản 1.0 (CrewAI) | Phiên bản 2.0 (Dify Native) | Phiên bản 3.0 (Commercial SaaS & RAG) |
|---|---|---|---|
| **AI Engine** | CrewAI FastAPI (:8000) | Dify built-in LLM | 9Router Gateway + Dify Hybrid |
| **Agent Engine** | Python CrewAI | Dify Agent + Workflow | Marketing Engine Microservice (:8085) |
| **Lưu Trữ Thị Trường** | File tĩnh / SQLite | Không có lưu trữ bền vững | **PostgreSQL Multi-tenant (`marketing_saas`)** |
| **Trí Tuệ Dự Án (Memory)**| Tạm thời theo session | Dataset nạp tay thủ công | **Tự động tạo Dify RAG Dataset per Project** |
| **Onboarding Đối Thủ** | 1 đối thủ cố định | Nhập tay | **Tự động trinh sát TOP 10 Online Google** |
| **Phân Tích Lợi Thế (UVP)**| Không có | Phụ thuộc người dùng | **Cơ chế kép: Gợi ý khi trống & Tối ưu khi điền** |
| **Mô Hình Kinh Doanh** | Đơn dự án (Nội bộ) | Đơn dự án | **Multi-tenant SaaS (Bán tài khoản & gói cước)** |

---

## KIẾN TRÚC PHIÊN BẢN 3.0: COMMERCIAL SAAS & POSTGRESQL RAG

### 1. Cấu Trúc Thư Mục Mở Rộng: `agent-engine/`
- `server.py`: FastAPI Microservice lắng nghe tại cổng `8085` kết nối Dify.
- `db_service.py`: Quản lý kết nối và các câu lệnh SQL tới DB `marketing_saas` (Tenants, Users, Projects, Competitors, Reports).
- `rag_service.py`: Tự động gọi Dify API sinh Dataset RAG (`KB_{client}_{project}`) và nạp hồ sơ đối thủ vào Weaviate.
- `serp_discovery_tool.py`: Trinh sát 10 đối thủ online, phân tích đối đầu và tối ưu hóa UVP.
- `schema.sql`: Kịch bản DDL khởi tạo toàn bộ bảng cơ sở dữ liệu quan hệ Multi-Tenant.

### 2. Các Endpoint SaaS Mới (Port 8085):
- `POST /api/v1/onboarding/analyze`: Tiếp cận dự án mới, quét 10 đối thủ, phân tích đối đầu UVP và lưu DB + RAG.
- `POST /api/v1/saas/tenants`: Đăng ký doanh nghiệp khách hàng mới (Gói cước, số lượng dự án tối đa).
- `POST /api/v1/saas/users`: Cấp tài khoản người dùng cho khách hàng.
- `GET /api/v1/saas/projects/{id}`: Tra cứu toàn bộ hồ sơ trí tuệ của một dự án.
- `POST /api/v1/saas/projects/{id}/select-uvp`: Khách hàng chọn 1 trong các phương án UVP tối ưu, tự động cập nhật Database.
- `POST /api/v1/campaign`: Chạy chiến dịch sinh nội dung đơn lẻ (hỗ trợ `post_type = "post"` hoặc `"product"`, truyền kèm `project_id` để tự động kéo UVP và 10 đối thủ từ Database).
- `POST /api/v1/campaign/batch`: Tiếp nhận danh sách sản phẩm hàng loạt (hỗ trợ `project_id`), chạy ngầm FastAPI BackgroundTasks chống timeout HTTP.
- `GET /api/v1/jobs/{job_id}`: Tra cứu trạng thái tiến trình xử lý hàng loạt theo thời gian thực.
- `POST /api/v1/research`: Trinh sát nhanh đối thủ và bóc tách ma trận giá bán thị trường.
- `POST /api/v1/projects/{id}/cms-bridge`: Cấu hình và kiểm tra kết nối WordPress từ xa qua Omni Bridge Connector Plugin.
- `GET /api/v1/download/omni-bridge-connector`: Tải gói plugin WordPress `omni-bridge-connector.zip` cho khách hàng cài đặt.
- `GET /api/v1/portal/projects/{id}/chat-history` (hoặc alias `/api/v1/projects/{id}/chat-history`): Lấy toàn bộ lịch sử trao đổi của dự án (khóa theo `dify_conversation_id`, F5 không mất).
- `POST /api/v1/portal/projects/{id}/chat` (hoặc alias `/api/v1/projects/{id}/chat`): Gửi câu hỏi vào dự án qua streaming SSE, duy trì ngữ cảnh AI xuyên suốt.
- `POST /api/v1/portal/projects/{id}/chat/reset` (hoặc alias `/api/v1/projects/{id}/chat/reset`): Xóa trắng phiên làm việc của dự án để AI nhận diện ngữ cảnh mới nếu cần.
- `GET /api/v1/portal/projects`: Lấy danh sách dự án của tenant và thông tin người dùng đang hoạt động.
- `POST /api/v1/portal/projects`: Tạo dự án mới, tự động trinh sát 10 đối thủ SERP và chấm điểm On-page web đích.
- `POST /api/v1/portal/projects/{id}/rescan-my-site`: Quét lại và đánh giá lại điểm sức khỏe On-page website mục tiêu.
- `POST /api/v1/portal/projects/{id}/rescan-competitors`: Quét lại SERP 10 đối thủ trực tuyến theo thời gian thực.
- `POST /api/v1/portal/projects/{id}/competitors/deep-audit`: Đột kích chuyên sâu 6 trụ cột đối thủ (Nội dung, Kỹ thuật, E-E-A-T, Schema).
- `POST /api/v1/portal/projects/{id}/keywords/scan`: Quét SERP và phân loại từ khóa ma trận 4 tầng (Head, Specs, Model, Intent).
- `POST /api/v1/portal/projects/{id}/license/activate`: Kích hoạt License Key cho dự án.
- `GET /api/v1/portal/settings`: Lấy thông tin cấu hình SERP (Google Ads & SEO) và AI Copilot (đã che giấu an toàn API keys).
- `POST /api/v1/portal/settings`: Lưu cấu hình SERP (xoay vòng Serper.dev API pool) và AI BYOK (OpenAI, Gemini, Anthropic, Custom).
- `POST /api/v1/portal/settings/test-serp`: Kiểm tra kết nối và hạn ngạch từng key trong pool Serper.dev.
- `POST /api/v1/portal/settings/test-ai`: Ping thử nghiệm mô hình AI tùy chọn của khách hàng (BYOK).


### 3. Google Search Console Performance Connector
GSC được kết nối theo cơ chế ownership transfer: nếu Google Site Kit (hoặc adapter plugin chính thức tương thích) đã có property, Omni Bridge chỉ phát hiện và gửi `property_uri`; Marketing Engine vẫn yêu cầu cấp OAuth riêng một lần để sở hữu refresh token độc lập. Sau khi Engine có token riêng, có thể xóa Yoast, Rank Math, Site Kit hoặc Omni Bridge mà kết nối GSC của Engine vẫn hoạt động. Nếu chỉ có mã xác minh hoặc metadata SEO thì đó chưa phải quyền đọc Search Analytics. Refresh token không bao giờ được trích xuất từ plugin.

Biến môi trường bắt buộc cho `agent-engine`:

```env
GSC_CLIENT_ID=...apps.googleusercontent.com
GSC_CLIENT_SECRET=...
GSC_REDIRECT_URI=https://marketing-engine.example.com/api/v1/gsc/oauth/callback
GSC_TOKEN_ENCRYPTION_KEY=<Fernet key>
ENGINE_API_KEY=<secret dùng để xác thực Dify/WordPress với Marketing Engine>
ROUTER_API_KEY=<secret của LLM Router>
DB_PASS=<mật khẩu PostgreSQL>
DIFY_DATASET_API_KEY=<Dify API key có quyền quản lý Dataset>
```

Có thể tự động kiểm tra cấu hình và chạy DDL idempotent bằng:

```bash
cd /home/ubuntu/michinhhang/dify-integration
python3 tools/bootstrap_gsc.py
```

Lệnh này không in secret, không tự thực hiện Google OAuth và không tự cài plugin WordPress vì hai bước đó cần quyền/xác nhận của người dùng.

Để tự động hóa cả WordPress Bridge bằng WP-CLI/Docker Compose:

```bash
python3 tools/automate_gsc_onboarding.py \
  --engine-url https://engine.example.com \
  --wordpress-url https://website.example.com \
  --tenant-id <TENANT_ID> \
  --project-id <PROJECT_ID> \
  --wp-cli-prefix 'docker compose exec -T wordpress wp --path=/var/www/html' \
  --wp-cli-workdir /path/to/wordpress-compose \
  --install-plugin
```

Script này tự chạy migration, cài/kích hoạt plugin, cấu hình Engine URL/Tenant/Project, lấy API key nội bộ không in ra màn hình, gọi CMS Bridge và in URL OAuth cuối cùng. Nó dừng ở bước Google consent để chủ tài khoản xác nhận quyền.

> 💡 **Lưu ý môi trường Docker:** Container WordPress (`michinhhang-wordpress-1`) đã được cài sẵn WP-CLI (`/usr/local/bin/wp`). Bạn có thể chạy trực tiếp lệnh trên từ thư mục gốc `/home/ubuntu/michinhhang`.

Nếu không dùng WP-CLI, có thể thao tác thủ công trong WordPress: vào **Settings → Omni Bridge**, nhập Marketing Engine URL, Tenant ID, Project ID và Engine API key, bấm **Lưu thiết lập**, sau đó bấm **Kết nối Google Search Console**. Plugin sẽ chuyển trình duyệt sang URL OAuth của Engine. Đăng nhập tài khoản Google có quyền với property, chấp nhận scope readonly và chờ Google chuyển về callback. Plugin chỉ lưu thông tin định tuyến; refresh token được lưu mã hóa tại Marketing Engine.

Tạo `GSC_TOKEN_ENCRYPTION_KEY` bằng `python -c "from cryptography.fernet import Fernet; print(Fernet.generate_key().decode())"`. OAuth client trên Google Cloud phải khai báo đúng redirect URI và cấp scope readonly Search Console.

Luồng kết nối:

1. `GET /api/v1/projects/{project_id}/gsc/connect?tenant_id={tenant_id}` tạo URL OAuth một lần, có state chống CSRF.
2. Google gọi `GET /api/v1/gsc/oauth/callback`; hệ thống đổi code lấy refresh token, mã hóa và dò các property mà tài khoản được quyền truy cập.
3. Nếu có nhiều property, gọi `POST /api/v1/projects/{project_id}/gsc/connections/{connection_id}/property` để chọn `property_uri`.
4. Gọi `POST /api/v1/projects/{project_id}/gsc/performance` với khoảng ngày và dimensions (`date`, `query`, `page`, `country`, `device`, `searchAppearance`) để lấy clicks, impressions, CTR và position.

Chạy bổ sung phần DDL trong `agent-engine/schema.sql` trước khi dùng connector. Tuyệt đối không lấy hoặc log refresh token/access token từ database của plugin SEO.

---

## MÔ HÌNH LAI (HYBRID): CÀO ĐA NỀN TẢNG & XUẤT BẢN WORDPRESS CHUẨN HÓA

### 1. Cơ Chế Cào Thích Ứng (Universal Adaptive Scraper - `scraper_tool.py`)
Giải quyết triệt để bài toán đối thủ sử dụng các nền tảng khác nhau (WordPress, Shopify, Haravan, Magento, Web Custom):
- **Bóc tách JSON-LD Schema (Ưu tiên số 1):** Tự động phát hiện các thẻ `<script type="application/ld+json">`, phân tích cấu trúc dữ liệu chuẩn quốc tế Schema.org cho `@type: Product` (tên, giá niêm yết, giá khuyến mãi, đơn vị tiền tệ, hình ảnh, thông số kỹ thuật) và `@type: Article / BlogPosting`.
- **Heuristic DOM & OpenGraph Fallback (Dự phòng thông minh):** Nếu website đối thủ không khai báo schema, thuật toán Heuristic tự động bóc tách từ `og:title`, `og:image`, `og:description`, các thẻ tiêu đề `h1`/`h2`/`h3` và regex thông minh nhận diện giá bán từ các định dạng tiền tệ VNĐ (`<ins>`, `<del>`, class `.price`, `.amount`).
- **Tương thích 100% URL Đối thủ Động:** Hỗ trợ nhận danh sách website đối thủ theo từng dự án trong PostgreSQL để trinh sát trực tiếp.

### 2. Mô Hình Xuất Bản CMS Song Song (2 Chế Độ)
Hệ thống hỗ trợ cơ chế xuất bản 2 tầng linh hoạt:

#### Chế độ 1: Tiêu Chuẩn (Omni Bridge Connector Plugin - Khuyên Dùng)
Khách hàng cài đặt plugin nhẹ `omni-bridge-connector.zip` (phiên bản v1.3.0, dung lượng tối ưu ~30KB, kiến trúc 9 Trait độc lập không gây chậm website).
- **Tải plugin trực tiếp:** `GET http://<host>:8085/api/v1/download/omni-bridge-connector`
- **Giao diện quản trị:** Vào **WP Admin -> Settings -> Omni Bridge** để sao chép **API Secret Key** (`obc_...`).
- **Endpoint kiểm tra:** `GET /wp-json/omni-bridge/v1/health` (trả về trạng thái, phiên bản WP, WooCommerce, Rank Math, Yoast).
- **Endpoint xuất bản:** `POST /wp-json/omni-bridge/v1/publish` với header `X-Omni-Bridge-Key: <key>`.
- **Lợi ích vượt trội:**
  1. Hỗ trợ 100% **Rank Math SEO** (`rank_math_focus_keyword`, `rank_math_title`, `rank_math_description`, canonical).
  2. Hỗ trợ **WooCommerce**: Đăng sản phẩm với đầy đủ giá gốc (`_regular_price`), giá khuyến mãi (`_sale_price`), SKU và tồn kho.
  3. **Media Sideloading:** Tự động tải ảnh đại diện từ website đối thủ về thư viện Media WordPress của khách hàng, đính kèm làm Featured Image với alt-tag chuẩn SEO.
  4. Hoạt động trơn tru trên mọi môi trường: VPS, Shared Hosting, Cloudways, Kinsta... không yêu cầu mở cổng SSH hay Docker.

#### Chế độ 2: Dự Phòng Nội Bộ (Docker Sandbox CLI)
Dành cho môi trường phát triển (Dev/Sandbox) hoặc khi khách hàng chưa kịp cài plugin: Hệ thống tự động fallback thực thi lệnh trực tiếp qua Docker container `michinhhang-wordpress-1`.

---

## KIẾN TRÚC GIAO DIỆN CLIENT PORTAL THƯƠNG MẠI & CHAT CỐ ĐỊNH (SOP 20)

### 1. Khắc Phục Triệt Để Mất Phiên Khi F5 (Persistent Chat Lifecycle)
- **Vấn đề cũ**: Mặc định Dify Studio Preview hoặc Dify WebApp chia sẻ không có liên kết với dữ liệu Dự án kinh doanh; F5 trình duyệt sẽ khiến `conversation_id` bị rỗng, Dify tạo cuộc trò chuyện mới làm AI "quên sạch" bối cảnh.
- **Giải pháp chuẩn hóa**:
  1. Cột `projects.dify_conversation_id` trong PostgreSQL lưu định danh phiên chat duy nhất của từng dự án.
  2. Giao diện Client Portal chỉ gửi `project_id`, Backend Gateway (`:8085`) tự động tra cứu `dify_conversation_id` và gọi Dify API.
  3. Khi F5 hoặc đổi máy tính: Portal gọi `GET /api/v1/projects/{id}/chat-history`, toàn bộ lịch sử trao đổi được tái hiện 100%, AI duy trì trí nhớ vĩnh viễn cho dự án.

### 2. Mô Hình Phân Quyền Multi-Tenant & Multi-Project
- **Tenant**: Khách hàng doanh nghiệp, quản lý gói cước và hạn mức số lượng dự án.
- **User**: Người dùng trực thuộc Tenant (`admin`, `manager`, `client`), bảo vệ qua Bearer Token / JWT.
- **Project**: Mỗi User có thể tạo nhiều dự án website/thương hiệu khác nhau. Dữ liệu đối thủ, RAG Dataset và phiên chat của từng Project được cách ly độc lập, không bị lẫn lộn.

### 3. Mô Hình Headless AI Engine (White-label 100%)
- **Dify Engine**: Đóng vai trò Backend AI ngầm, bảo vệ tuyệt đối bản quyền System Prompts, RAG Datasets và API Keys bên trong mạng Docker nội bộ.
- **Custom Client Portal**: Giao diện người dùng độc lập, mang trọn vẹn thương hiệu riêng, tích hợp đầy đủ: Quản lý Dự án, Khung chat Streaming mượt mà, Bảng 10 đối thủ cào được, Lợi thế UVP, Kết nối WordPress và Google Search Console.

### 4. Quy Hoạch Hạ Tầng Phục Vụ Portal (Port 8085)
- **Container Host**: Tận dụng trực tiếp container `marketing-agent-engine` (FastAPI cổng `:8085`), không tạo thêm container mới để tiết kiệm RAM máy chủ.
- **Route Isolation**: Giao diện Client Portal chạy tại root `/` và assets `/portal/*`; các API nghiệp vụ chạy tại `/api/v1/*`.
- **Zero-Downtime Hot-Reload**: Thư mục `agent-engine/` được mount trực tiếp từ host, cập nhật giao diện có hiệu lực ngay lập tức khi F5 mà không cần build lại Docker hay restart container.

### 5. Cải Tiến Khởi Tạo Dự Án & Bổ Sung Tab Báo Cáo Website Mục Tiêu
- **Khắc phục triệt để lỗi "treo và không thấy 10 đối thủ"**:
  1. Khi lưu dự án mới: Modal hiển thị trạng thái trinh sát trực quan.
  2. Ngay khi backend phản hồi thành công: Lưu `project_id` vào `localStorage`, tự động reload dữ liệu và **tự động chuyển ngay sang Tab 10 Đối thủ SERP (`switchTab('competitors')`)**, hiển thị tức thì 10 đối thủ được xác minh Online 100% kèm nhãn phân biệt `📢 Chạy Google Ads` và `🌿 Top SEO Tự Nhiên`.
- **Tab Báo Cáo Phân Tích Chi Tiết Website Cần Phát Triển (`data-tab="my-site"`)**:
  1. **Thẻ Điểm Sức Khỏe On-page (0 - 100đ)**: Tự động đánh giá các tiêu chuẩn cốt lõi (Mã HTTP 200, độ dài Title 40-65 ký tự, Meta Description 130-160 ký tự, thẻ H1 duy nhất, cấu trúc H2 và Silo menu).
  2. **6 Thẻ Chỉ Số Kỹ Thuật Trực Quan**:
     - *Trạng Thái Máy Chủ*: Kiểm tra phản hồi HTTP và tốc độ bot Google thu thập dữ liệu.
     - *Tiêu Đề Trang (Title)*: Bóc tách tiêu đề thực tế và cảnh báo độ dài chuẩn SEO.
     - *Thẻ Mô Tả (Meta Description)*: Phát hiện khoảng trống CTR (nếu thiếu thẻ, cảnh báo đỏ và hướng dẫn viết thẻ chuyển đổi).
     - *Thẻ H1 & H2*: Kiểm soát phân cấp tiêu đề trang chủ và trang đích.
     - *Nhận Diện Thương Hiệu*: Bóc tách tự động Logo thương hiệu, Hotline và địa bàn phục vụ.
  3. **Cấu Trúc Điều Hướng Silo & Đối Chiếu Đối Thủ Mẫu**: Hiển thị danh mục menu của web đích và gợi ý các nhóm danh mục Silo cốt lõi từ đối thủ mẫu để bao phủ từ khóa.
  4. **Kế Hoạch Hành Động 5 Bước Khắc Phục On-page**: Đưa ra lộ trình từng bước tối ưu kỹ thuật trước khi sản xuất bài viết chuẩn SEO (SOP 12).
  5. **Nút Thao Tác Nhanh**: Nút `🔄 Quét Lại On-page Web Đích` (gọi endpoint `POST /api/v1/portal/projects/{id}/rescan-my-site`) và nút `💬 Copilot Lên Kế Hoạch Sửa On-page` (tự động điều phối chuyên gia SEO vào khung chat).

### 6. Chuẩn Hóa Giải Mã Đối Thủ Mẫu 6 Trụ Cột Thực Chiến (SOP 16 V2)
Nâng cấp từ kiểm toán bề mặt trang chủ thành bộ máy tình báo toàn diện mô phỏng 100% tư duy Đội ngũ SEO Agency:
1. **Quét đa cấp (Deep Multi-level Crawl)**: Quét song song 3 cấp (Trang chủ, 1 trang Category Hub, 1 trang chi tiết PDP) kết hợp đọc `robots.txt` và `sitemap.xml` để ước lượng quy mô chỉ mục đối thủ.
2. **Độ sâu Nội dung & E-E-A-T**: Phân tích độ dài bài viết trung bình (word count), mật độ Heading H2/H3, bảng so sánh sản phẩm, FAQ Accordion, box tác giả và giấy phép chứng thực.
3. **Kỹ thuật Chuyên sâu & Core Web Vitals**: Nhận diện CMS Tech Stack (WordPress, Shopify, Haravan...) và đo lường thời gian phản hồi máy chủ (TTFB), tốc độ tải trang Mobile.
4. **Hệ thống Từ khóa 3 Tầng**: Phân tách Head Keywords, Secondary Models và Long-tail Commercial Intent mà không cắt vụn n-gram.
5. **Dấu chân Thương mại & CRO**: Bóc tách chính sách giá khuyến mãi vs giá niêm yết, các tiện ích CTA (Zalo, Hotline nổi, bảo hành).
6. **Ma trận Đối đầu Trực diện (Head-to-head Matrix)**: So sánh trực tiếp Website mục tiêu vs Đối thủ mẫu để tự động kích hoạt 5 hành động ưu tiên cho dây chuyền W1–W6.



