# SOP 25: Autonomous Multi-Agent Boardroom Debate & Claude SEO Parity Architecture

> **Phiên bản**: 3.5 — Ban hành ngày 31/10/2026  
> **Áp dụng**: Omni Marketing SaaS Engine, Dify AI Copilot Gateway, Client Portal & Super Admin Console.

---

## 1. BỐI CẢNH & MỤC TIÊU CỐT LÕI

Trước SOP 25, các phản hồi từ AI Copilot thường dựa trên một góc nhìn đơn lẻ (Single LLM Prompt), dễ dẫn đến tình trạng:
1. **Lệch về kỹ thuật SEO**: Nhồi nhét từ khóa, bài viết dài lê thê khiến người đọc thoát trang, tỷ lệ chuyển đổi (CRO) thấp.
2. **Lệch về thương mại/bán hàng**: Quên mất các tiêu chuẩn xếp hạng Googlebot và cấu trúc Heading Silo.
3. **Thiếu định vị độc quyền**: Đua giá rẻ với các chuỗi bán lẻ khổng lồ thay vì khai thác lợi thế dịch vụ tại nhà.

**SOP 25 ra đời nhằm chuẩn hóa**:
- **Cơ chế Ban Giám Đốc Tranh Luận Mặc Định (Autonomous Multi-Agent Boardroom Debate)**: Tự động phản biện chéo giữa 3 chuyên gia trước khi chốt giải pháp.
- **Đạt chuẩn tương thích với các năng lực tinh hoa của Claude SEO 2026**: AI Agent Readiness (`llms.txt`), Strategic Content Brief, SEO Drift Monitoring và IndexNow Instant Indexing.
- **Kiểm soát tính toàn vẹn dữ liệu đa doanh nghiệp (Domain Deduplication & Super Admin Project Lifecycle)**.

---

## 2. KIẾN TRÚC BAN GIÁM ĐỐC TRANH BIỆN ĐA TÁC TỬ (MULTI-AGENT BOARDROOM)

```
                            [Người dùng gửi yêu cầu]
                                       │
                                       ▼
                       ┌───────────────────────────────┐
                       │   FastAPI Chat SSE Stream     │
                       └───────────────┬───────────────┘
                                       │
             ┌─────────────────────────┴─────────────────────────┐
             ▼                                                   ▼
┌───────────────────────────┐                       ┌───────────────────────────┐
│   🔍 SEO & SERP Lead      │                       │ 🛒 CRO & Pricing Lead     │
│   (Thuật toán, H2/H3,     │◄─────Phản biện chéo──►│ (Tâm lý mua, tỷ lệ thoát, │
│    độ bao phủ từ khóa)    │                       │  CTA 3s đầu, bảng giá)    │
└────────────┬──────────────┘                       └─────────────┬─────────────┘
             │                                                    │
             └─────────────────────────┬──────────────────────────┘
                                       ▼
                       ┌───────────────────────────────┐
                       │ 💎 Brand & UVP Strategist     │
                       │ (Đòn bẩy bảo hành tại nhà,    │
                       │  khai thác điểm mù đối thủ)   │
                       └───────────────┬───────────────┘
                                       ▼
                       ┌───────────────────────────────┐
                       │ 👑 CMO AI (Chủ Tọa / Trọng Tài)│
                       │ BẢN KẾ HOẠCH ĐỒNG THUẬN TỐI ƯU│
                       └───────────────────────────────┘
```

### Cấu trúc dữ liệu Streaming chuẩn:
Mô hình sinh luồng văn bản bắt đầu bằng khối định danh:
```markdown
:::boardroom-debate
🔍 **SEO & SERP Lead**: [Đề xuất góc nhìn kỹ thuật, tháp Heading H2/H3, phủ từ khóa để đè bẹp đối thủ]
🛒 **CRO & Pricing Specialist**: [Phản biện: cảnh báo rủi ro khách thoát trang, rào cản giá, đòi hỏi Bảng so sánh và Hotline/CTA 3s đầu]
💎 **Brand & UVP Strategist**: [Đưa ra đòn bẩy độc quyền: khai thác điểm mù của 10 đối thủ, chính sách bảo hành/dịch vụ tại nhà]
:::

👑 **CMO AI • BẢN KẾ HOẠCH ĐỒNG THUẬN TỐI ƯU**:
[Kế hoạch hành động sắc bén, dung hòa toàn diện và có thể thực thi ngay]
```

### Xử lý Frontend (`portal_chat_brief_agentic.js`):
Hàm `renderBoardroomDebateMarkup(text)` bóc tách khối `:::boardroom-debate ... :::` thành thẻ Accordion tương tác:
- Header hiển thị trạng thái `🟢 Đã đạt đồng thuận 100%` kèm nút Thu gọn/Mở rộng.
- 3 bong bóng phát biểu có viền màu riêng biệt: Xanh dương (SEO), Vàng cam (CRO), Tím (Brand).
- Phần Kế hoạch đồng thuận của CMO AI được gắn badge hoàng gia `.consensus-heading`.

---

## 3. BỘ 4 NĂNG LỰC ĐỘT PHÁ TƯƠNG THÍCH CLAUDE SEO 2026

### 1. AI Agent Readiness & `llms.txt` Generator (`/agentic`)
- **Tập tin**: `routes/portal/agentic.py` (157 dòng).
- Bóc tách `robots.txt` phát hiện các User-Agent bị chặn (`GPTBot`, `ClaudeBot`, `PerplexityBot`, `Google-Extended`, `Amazonbot`).
- Kiểm tra sự hiện diện của `/llms.txt` và tự động sinh tệp Markdown chuẩn [llmstxt.org](https://llmstxt.org) dựa trên cấu trúc danh mục thực tế của dự án.

### 2. IndexNow Instant Search Indexing Protocol
- **Tập tin**: `routes/portal/bridge.py` (168 dòng).
- Khi người dùng bấm xuất bản bài viết lên WordPress qua Omni Bridge:
  - Tự động gửi ping tới `https://api.indexnow.org/indexnow` kèm domain và URL bài viết mới.
  - Rút ngắn thời gian lập chỉ mục từ 3 - 7 ngày xuống còn **5 - 15 phút** trên Bing, Yandex, Seznam và các AI Search Engine.

### 3. Strategic Content Brief & Topic Cluster (`/brief <từ_khóa>`)
- **Tập tin**: `routes/portal/brief_and_drift.py` (165 dòng).
- Sinh bản dàn ý chiến lược 5 phần: Persona/Search Intent, LSI Keyword Cluster, Tháp Heading Silo bắt buộc, Bộ câu hỏi People Also Ask (PAA) kèm gợi ý Direct Answer, và Bản đồ liên kết nội bộ (Internal Links).

### 4. SEO Drift Monitoring — "Git cho SEO" (`/drift`)
- **Tập tin**: `routes/portal/brief_and_drift.py`.
- Tự động lưu snapshot định kỳ vào bảng `seo_baselines` (PostgreSQL).
- So sánh phiên bản hiện tại với mốc lịch sử gần nhất, phát hiện các biến động (`CRITICAL`, `WARNING`, `INFO`) để cảnh báo kịp thời khi tụt điểm On-page hoặc bị sửa đổi thẻ Title/H1.

---

## 4. QUY CHUẨN KIỂM SOÁT HỆ THỐNG & BẢN QUYỀN ĐA DOANH NGHIỆP

1. **Domain Deduplication Guard (`projects.py`)**:
   - Trước khi tạo dự án mới, tự động chuẩn hóa URL FQDN (`normalize_domain_fqdn`).
   - Nếu tên miền đã tồn tại trong cơ sở dữ liệu (dù có hoặc không có `www.`, `https://`), từ chối tạo trùng lặp với mã lỗi `400 Bad Request`.
2. **Quản trị vòng đời Dự án trên Super Admin (`admin.py` & `portal_admin.js`)**:
   - Endpoint `DELETE /api/v1/portal/admin/licenses/{project_id}` cho phép Super Admin xóa vĩnh viễn các dự án thử nghiệm hoặc hết hạn.
   - Bảng quản trị hiển thị rõ tên Doanh nghiệp sở hữu (`tenant_name`).

---

## 5. TIẾN HÓA BAN HỘI ĐỒNG C-SUITE TOÀN NĂNG (C-SUITE EVOLUTION)
1. **Giám Đốc Tài Chính AI (CFO & Budget Specialist)**:
   - Avatar `💰`, viền xanh ngọc bích `#10b981`.
   - Chuyên gia phản biện rủi ro biên lợi nhuận, chi phí quảng cáo/sản xuất, điểm hòa vốn (Break-even Point) và bảo vệ dòng tiền.
2. **Chỉ Số Biểu Quyết Định Lượng (Consensus Voting Index - CVI)**:
   - Công thức tổng hợp điểm số 4 chuyên gia: $\text{CVI} = \frac{\text{SEO} + \text{CRO} + \text{Brand} + \text{Finance}}{40} \times 100\%$.
   - Hiển thị trực quan qua thanh tiến trình (`.cvi-bar-fill`) và huy hiệu phân cấp:
     + $\ge 85\%$: 🟢 Đồng thuận cao (Khuyến nghị phê duyệt).
     + $70\% - 84\%$: 🟡 Đồng thuận có điều kiện.
     + $< 70\%$: 🔴 Tranh chấp / Rủi ro cao.
3. **Quyền Can Thiệp Của Chủ Tịch HĐQT (Human-in-the-Loop Chairman Interjection)**:
   - Thanh nút tương tác 1-chạm (`.chairman-interject-bar`): `✅ Phê Duyệt`, `💰 Theo CFO`, `🛒 Theo CRO`, `🔍 Theo SEO`, `💬 Tranh Luận Lại`.
   - Cho phép người dùng trực tiếp định hướng hoặc yêu cầu phiên họp phản biện lại theo trọng tâm chiến lược.
4. **Lưu Trữ Biên Bản Cuộc Họp Xuyên Suốt (Boardroom Minutes Archive)**:
   - Bảng CSDL PostgreSQL `boardroom_minutes`: Lưu trữ `topic`, `debate_summary`, `consensus_plan`, `cvi_score`.
   - Tự động đồng bộ và truy vấn để tích lũy ký ức dài hạn cho các phiên họp tiếp theo.

---

## 6. CAM KẾT KIỂM SOÁT DÒNG MÃ (SOP 23 COMPLIANCE)

Tất cả các tập tin trong hệ thống bắt buộc tuân thủ quy tắc $\le 300$ dòng mã:

| Tập tin | Số dòng thực tế | Trạng thái SOP 23 |
|---|:---:|:---:|
| `routes/portal/admin.py` | 169 dòng | ✅ Đạt chuẩn |
| `routes/portal/projects.py` | 297 dòng | ✅ Đạt chuẩn |
| `routes/portal/agentic.py` | 157 dòng | ✅ Đạt chuẩn |
| `routes/portal/seed.py` | 70 dòng | ✅ Đạt chuẩn |
| `routes/portal/bridge.py` | 168 dòng | ✅ Đạt chuẩn |
| `routes/portal/brief_and_drift.py` | 214 dòng | ✅ Đạt chuẩn |
| `routes/portal/intelligence.py` | 257 dòng | ✅ Đạt chuẩn |
| `routes/portal/chat.py` | 224 dòng | ✅ Đạt chuẩn |
| `portal/portal_admin.js` | 262 dòng | ✅ Đạt chuẩn |
| `portal/portal_chat.js` | 297 dòng | ✅ Đạt chuẩn |
| `portal/portal_chat_commands.js` | 193 dòng | ✅ Đạt chuẩn |
| `portal/portal_chat_brief_agentic.js` | 279 dòng | ✅ Đạt chuẩn |
| `portal/portal_chat.css` | 174 dòng | ✅ Đạt chuẩn |
| `dsl/cmo_agent_app_dsl.yml` | 272 dòng | ✅ Đạt chuẩn |

---

## 7. BẢN ĐỒ CẤU TRÚC MÃ NGUỒN TOÀN HỆ THỐNG (SYSTEM CODEBASE DIRECTORY MAP)

```
dify-integration/
├── agent-engine/
│   ├── server.py                       # FastAPI App Factory & Root Router Mount (138 lines)
│   ├── db_service.py                   # PostgreSQL Connection Pool & Context Managers
│   ├── models.py                       # Pydantic Schemas & Data Transfer Objects
│   ├── utils.py                        # Helper functions, Slugify & Security
│   ├── routes/
│   │   ├── portal/
│   │   │   ├── __init__.py             # Portal Sub-router Aggregator
│   │   │   ├── projects.py             # Projects CRUD & Domain Deduplication Guard (297 lines)
│   │   │   ├── seed.py                 # Competitor Seed Onpage Analyzer (70 lines)
│   │   │   ├── brief_and_drift.py      # /brief, /drift & Boardroom Minutes API (214 lines)
│   │   │   ├── agentic.py              # /agentic, robots.txt scanner & llms.txt (157 lines)
│   │   │   ├── bridge.py               # WordPress Omni Bridge & IndexNow Ping (168 lines)
│   │   │   ├── admin.py                # Super Admin License & Project Delete (169 lines)
│   │   │   ├── chat.py                 # Dify SSE Proxy & Session Memory (224 lines)
│   │   │   ├── intelligence.py         # Deep Competitor Recon Worker (257 lines)
│   │   │   ├── keywords.py             # Master Keyword Pool & SERP Matrix (246 lines)
│   │   │   ├── license.py              # HMAC License Verification (96 lines)
│   │   │   └── settings.py             # Tenant Settings & API Keys (362 lines)
│   │   ├── cms.py                      # External CMS Sync
│   │   ├── gsc.py                      # Google Search Console OAuth2 Bridge
│   │   └── onboarding.py               # First-time Setup Wizard
│   └── portal/                         # Frontend SPA Component Architecture
│       ├── index.html                  # Client Portal SPA Main Entry
│       ├── admin.html                  # Super Admin Dashboard Entry
│       ├── portal_chat.css             # Chat & Boardroom Accordion Styles (174 lines)
│       ├── portal_chat.js              # SSE Stream Parser & Minutes Auto-save (297 lines)
│       ├── portal_chat_brief_agentic.js# C-Suite Debate Parser, CVI & Slash Commands (279 lines)
│       ├── portal_chat_commands.js     # Chat Slash Command Registry (193 lines)
│       ├── portal_admin.js             # Master License Console (262 lines)
│       ├── portal_core.js              # State Management, Toast UI & Storage (201 lines)
│       ├── portal_project.js           # Multi-tenant Project Selector (192 lines)
│       ├── portal_competitors.js       # Competitors Grid & Modal (591 lines)
│       ├── portal_keywords.js          # Dual View Keyword Switcher (403 lines)
│       └── portal_license.js           # License View Modal (160 lines)
├── dsl/
│   └── cmo_agent_app_dsl.yml           # Multi-Agent Debate C-Suite Prompt App DSL (272 lines)
└── knowledge/                          # Hệ Thống Tri Thức Chuẩn Hóa SOP 11 -> SOP 25
    ├── 01_sop12_content_writing_rules.md
    ├── 02_sop11_image_processing_standards.md
    ├── 03_xiaomi_product_specs_and_pricing.md
    ├── 04_sop16_competitor_recon_autopilot.md
    ├── 05_marketing_department_agents_roles.md
    ├── 06_sop17_onboarding_saas_rag_architecture.md
    ├── 07_sop18_cross_competitor_and_product_batch_pipeline.md
    ├── 08_sop19_gsc_oauth_and_cms_bridge.md
    ├── 09_sop20_custom_client_portal_and_persistent_chat_architecture.md
    ├── 10_sop21_deep_competitor_intelligence_and_zero_cost_recon.md
    ├── 11_sop22_competitor_keyword_matrix_and_master_keyword_pool.md
    ├── 12_sop23_modular_clean_architecture_and_refactoring.md
    ├── 13_sop24_license_management_and_hybrid_cloudflare_bypass.md
    └── 14_sop25_autonomous_boardroom_debate_and_claude_seo_parity.md
```
