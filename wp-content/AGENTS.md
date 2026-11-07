# Trạm Điều Phối AI Tổng — Dự án Michinhhang

Đây là file tri thức tổng (Master Knowledge File) cho toàn bộ dự án `michinhhang`. File này có nhiệm vụ điều phối và liên kết tới các file tri thức cụ thể của từng thành phần trong hệ thống, bao gồm Theme và Plugin ERP.

## 1. Thông tin tổng quan dự án
- **Tên dự án:** Michinhhang
- **Loại hình:** Hệ thống TMĐT phân phối thiết bị Xiaomi chính hãng + Quản trị ERP nội bộ.
- **Nền tảng:** WordPress + WooCommerce + MariaDB + Redis.
- **Hạ tầng:** 100% Docker (không chạy trực tiếp trên host).

## 2. Liên kết đến các file tri thức thành phần

Khi cần phát triển hoặc sửa đổi các thành phần cụ thể, AI **BẮT BUỘC** phải đọc và tuân thủ các quy tắc trong các file tri thức tương ứng dưới đây:

### 2.1. Frontend & Giao diện (Theme)
Chịu trách nhiệm hiển thị giao diện người dùng (TMĐT), tích hợp TailwindCSS, quản lý ACF, và logic hiển thị frontend.
- **File tri thức Theme:** [omni-commerce/AGENTS.md](./themes/omni-commerce/AGENTS.md) & [omni-commerce/README.md](./themes/omni-commerce/README.md)
- **Vị trí:** `wp-content/themes/omni-commerce/`
- **Các quy tắc cốt lõi:** YAGNI, Classic Theme, Tailwind first, ACF dynamic, Escape output, Khung chứa đồng nhất `max-w-[1440px]`, Thẻ sản phẩm 2-tier responsive price.

### 2.2. Backend & Nghiệp vụ (Plugin ERP)
Hệ thống ERP nội bộ độc lập gồm 8 phân hệ cốt lõi: SCM (Quản lý đa kho & Serial/IMEI), CRM (Bảo hành điện tử & RMA), Finance (Sổ quỹ & Thu chi), Procurement (Mua hàng PO & auto-serial), HRM (Nhân sự, lương & KPI), Manufacturing (Sản xuất & BOM), Orders (Điều phối đơn & bàn giao giao vận), POS (Bán lẻ tại quầy & quét barcode), cùng Shipping (>20km) và SEO Schema Yield.
- **File tri thức Plugin ERP:** [viomi-erp-system/AGENTS.md](./plugins/viomi-erp-system/AGENTS.md)
- **Vị trí:** `wp-content/plugins/viomi-erp-system/`
- **Các quy tắc cốt lõi:** Độc lập logic, Thiết lập và build TailwindCSS độc lập (không share với theme), Bảo mật dữ liệu (prepare, nonce), Tối ưu truy vấn (HPOS).

### 2.3. Trí Tuệ Nhân Tạo & Điều Phối Đa Tác Tử (CrewAI Enterprise & Omni CrewAI Portal)
Hệ thống trí tuệ nhân tạo Multi-Agent tự động hóa tiếp thị, cào dữ liệu đối thủ, tạo bài viết chuẩn SEO (SOP 12) và nhập liệu sản phẩm WooCommerce kèm 5 kích thước ảnh responsive 1:1 chuẩn SOP 11.
- **File tri thức Plugin WordPress:** [omni-crewai-portal/AGENTS.md](./plugins/omni-crewai-portal/AGENTS.md)
- **File tri thức Microservice Python:** [crewai-service/AGENTS.md](../../crewai-service/AGENTS.md)
- **Vị trí Plugin:** `wp-content/plugins/omni-crewai-portal/`
- **Vị trí Microservice:** `crewai-service/`
- **Các quy tắc & kiến trúc cốt lõi:**
  - **Portal SaaS Full-Screen 8 tab:** `html-dashboard.php` là router 100 dòng nạp các component trong `views/parts/`, `views/tabs/` và `views/modals/`; CSS riêng của plugin có 559 dòng, còn Tailwind 3.4.1 và Inter được nạp từ CDN. Sidebar 8 tab: Chat/Execution, Discovery, Agents, Competitor, Terminal, History, RAG, Settings.
  - **CMO Chat & luồng Marketing:** Tab Execution gọi chat request/response theo thread tới `/api/v1/chat-manager`; profile doanh nghiệp lưu trong `wp_options`, thread ID lưu trong `localStorage` theo user, lịch sử do service quản lý. Người dùng xác nhận kế hoạch trước khi UI gọi `/api/v1/execute-crew`; trạng thái job được poll. Đây không phải luồng streaming token.
  - **Discovery & tác vụ thường:** Discovery quản lý 10 tool; dispatch cổ điển có thể mở EventSource `/api/v1/crews/{uuid}/stream` và poll AJAX để đồng bộ trạng thái/log. Tab Agents, Competitor, Terminal, History (jobs/DLQ/scheduler), RAG và Settings là các panel modular riêng.
  - **Slide-over Drawer & 9 Modals:** Drawer job ở History; 9 modal được nạp từ `views/modals/` (HITL, schedule, prompt, benchmark, MCP, flows, A/B, GDPR, Prometheus).
  - **Hệ thống 81 AJAX hooks:** 78 action trong `Omni_CrewAI_Admin`, 2 trong `Omni_CrewAI_MetaBox`, 1 trong `Omni_CrewAI_Cron`. Admin/MetaBox xác thực nonce và capability theo từng handler; Cron dismiss health alert hiện chưa có kiểm tra nonce/capability tường minh. API client có 74 public static methods.
  - **JavaScript & điều hướng:** `admin-dashboard.js` dài 5.838 dòng, `toast-ui.js` cung cấp `OmniToast` và override `alert()` trong portal. Tab được khôi phục từ query `?tab=` hoặc `localStorage`, không phải URL hash.
  - **Hệ Thống Phân Hệ WP-CLI (15 Lệnh) & Socket Fallback:** 15 lệnh quản trị chuyên sâu (`health`, `active`, `list-jobs`, `status`, `logs`, `dispatch`, `checkpoint`, `approve`, `reject`, `retry-dlq`, `purge-logs`, `sync-rag`, `process-image`, `test-alert`, `check-guardrails`). Lệnh `health` tích hợp cơ chế Socket Fallback (`fsockopen` tới `redis:6379`) tự động nhận diện Redis khi PHP CLI không có `ext-redis`.
  - **Chuẩn 32 Trụ cột CrewAI Enterprise:** Đầy đủ hạ tầng sản xuất, RBAC, Data Isolation, và Kubernetes / K3s production deployment manifests (`deploy/k8s/`).

### 2.4. Hạ tầng & Docker Compose
Toàn bộ dự án được quản lý qua `docker-compose.yml`. Các Agent cần nắm rõ cấu trúc này để thực thi lệnh (chủ yếu thao tác thông qua `docker exec`):
- **Container WordPress (`michinhhang-wordpress-1`):** Nơi chứa mã nguồn PHP và chạy WP-CLI, TailwindCSS. Đường dẫn nội bộ được mount là `/var/www/html/wp-content`.
- **Container Database (`michinhhang-db-1`):** Chạy MariaDB.
- **Container Redis (`michinhhang-redis-1`):** Chạy Redis Cache.
- **Microservice FastAPI Host (`crewai-service`):** Chạy trên host port 8000 (Python 3.10+, Uvicorn), kết nối an toàn với container WordPress qua mạng Docker Bridge `172.18.0.1:8000`.
- **Service Graph Memory (`graph-memory`):** Chạy trong Docker Compose (Profile: `dev`, Port 3000, Node.js 22+), mount dữ liệu véc-tơ và đồ thị SQLite vào `./.memory:/root/.dsh/graph-memory`.
*Lưu ý: Bất kỳ thao tác cài đặt gói (npm, composer) hoặc build assets nào cũng phải gọi vào đúng container WordPress thay vì chạy trực tiếp trên host.*

### 2.5. Dịch Vụ Bộ Nhớ Đồ Thị Tác Tử AI (Graph Memory Service)
Plugin và dịch vụ lưu trữ bộ nhớ đồ thị tri thức dài hạn đa phiên (Cross-session Knowledge Graph Memory) dành cho DeepSeek Harness, Cordis và OpenClaw.
- **File tri thức Graph Memory:** [graph-memory-service/README.md](../../graph-memory-service/README.md)
- **Vị trí:** `graph-memory-service/`
- **Các quy tắc cốt lõi:**
  - Context Bounding Takeover: Chỉ giữ 5 turn hoàn thành gần nhất trên bề mặt mô hình, cắt tỉa 60-80% reasoning traces cũ mà không xóa event log máy chủ.
  - Turn-Memory Navigation: Tóm tắt turn thành 1 câu duy nhất, trích xuất bộ ba SPO (Subject-Predicate-Object). Nodes đồ thị chỉ dẫn đường, câu hỏi và đáp án gốc là bằng chứng sự thật khách quan.
  - Phân cụm cộng đồng Local LPA & Định tuyến xếp hạng Query-time Personalized PageRank (PPR).

### 2.6. Hạ Tầng CI/CD, Tự Động Hóa Vận Hành & DevOps (Jenkins, Ansible & K3s)
Hệ thống đường ống phân phối liên tục và triển khai cuộn không gián đoạn (Zero-Downtime Rolling Deployment).
- **File tri thức CI/CD:** [Jenkinsfile](../../Jenkinsfile) & [4_deploy_rolling.yml](../../4_deploy_rolling.yml)
- **Vị trí kịch bản:** Thư mục gốc (`Jenkinsfile`, `4_deploy_rolling.yml`, `inventory.ini`, `setup.sh`) và `crewai-service/deploy/k8s/`
- **Các quy tắc cốt lõi:**
  - Sao lưu CSDL MariaDB tự động trước mỗi lần deploy (`mysqldump` nén gzip vào `/home/ubuntu/backups/`).
  - Ansible rolling update với `serial: 1`, Drain node khỏi Load Balancer, kiểm tra HTTP 200 health probe loop trước khi chuyển node kế tiếp.
  - Kubernetes/K3s deployment với Pod Security restricted, Strategy `Recreate` bảo vệ SQLite persistence.

## 3. Quy tắc làm việc chung toàn dự án
1. **Luôn ưu tiên đọc tài liệu con:** Nếu yêu cầu liên quan đến sửa đổi giao diện, hãy đọc tài liệu của Theme. Nếu liên quan đến nghiệp vụ ERP, hãy đọc tài liệu của Plugin.
2. **Tính độc lập (Decoupling):** Cả Theme và Plugin phải hoạt động như 2 sản phẩm hoàn toàn độc lập để có thể tách ra triển khai riêng lẻ trong tương lai. Hệ thống CSS/TailwindCSS của mỗi thành phần phải được thiết lập riêng, build riêng, tuyệt đối không share chéo cấu hình hoặc phụ thuộc chéo vào file CSS của nhau.
3. **Môi trường Docker:** Mọi thao tác biên dịch (ví dụ build TailwindCSS) hoặc chạy lệnh hệ thống liên quan đến PHP/WP-CLI phải được thực thi thông qua các container Docker tương ứng, không can thiệp trực tiếp lên host.

## 4. Tiêu chuẩn Bảo mật & Quản lý Kho Lưu Trữ (Security & Repository Standards)
1. **Cô lập Tệp Nhạy cảm & Cơ sở Dữ liệu:**
   - Tuyệt đối KHÔNG lưu file backup database (`.sql`), bản nén archive (`.tar.gz`), log trong webroot công khai (`wp-content/`). Luôn chuyển vào thư mục riêng biệt (như `backups/`) và duy trì file [wp-content/.htaccess](.htaccess) chặn truy cập HTTP trực tiếp vào các định dạng `.sql`, `.log`, `.phar`, `.sh`, `.bak`, `.tar.gz`.
2. **Không commit Tệp Nhị Phân Lớn (Binary Bloat):**
   - Các file nhị phân độc lập như `tailwindcss-linux-x64` (~40MB-80MB), `wp-cli.phar` (~7MB) phải được loại trừ bằng `.gitignore`. Việc build CSS hoặc chạy lệnh phải tận dụng Docker image (`node:alpine` hoặc container WordPress).
3. **An toàn Bí mật (Secrets Management):**
   - Tuyệt đối không hardcode API tokens (Telegram Bot Token, Cloudflare Tunnel Token, DB credentials) trong `Jenkinsfile` hoặc `docker-compose.yml`. Luôn sử dụng biến môi trường hoặc Jenkins Credentials Provider.
4. **Bảo mật Hạ tầng Container:**
   - Không mount socket nhạy cảm của máy chủ (`/var/run/docker.sock`) vào các container ứng dụng thông thường nhằm ngăn ngừa leo thang đặc quyền từ container ra host.
5. **Đầy đủ Template Hierarchy & Loop Safety:**
   - Khi tùy biến query trong theme, phải bảo đảm tính toàn vẹn của vòng lặp bằng `rewind_posts()`. Luôn tạo đầy đủ các template parts phụ trợ (`content-search.php`, `content.php`, `index.php`) để tránh lỗi trắng trang khi người dùng tìm kiếm hoặc truy cập lưu trữ.
6. **Phân Định Trách Nhiệm ACF Data Layer vs Theme Responsive Layer:**
   - Plugin ACF thuần túy là tầng dữ liệu (Data/Storage Layer qua `get_field()`), không quản lý Breakpoint, không can thiệp vào CSS hay hành vi responsive. Mọi chuyển đổi giao diện giữa Mobile và Desktop hoàn toàn do Theme CSS/HTML đảm trách. Tránh nhầm lẫn lỗi thiếu class CSS của Theme với lỗi do ACF sinh ra.
7. **Quy Chuẩn Thẻ Sản Phẩm (Giá Khuyến Mãi & Social Proof Tự Động):**
   - Giá sau khuyến mãi luôn đứng trước, to nhất và mang màu đỏ thương hiệu; giá gốc trước khuyến mãi đứng sau và chìm xuống màu xám gạch ngang (`line-through`).
   - Chân thẻ sản phẩm dùng `mt-auto pt-2.5 flex items-center justify-between` để gióng thẳng hàng cụm sao đánh giá và `Đã bán X` (chuyển đổi `>= 1000` thành dạng `1.3k`).
   - Tự động sinh dữ liệu mẫu ngẫu nhiên (`total_sales`, `_wc_average_rating`, `_wc_review_count`) qua hook `transition_post_status` khi đăng sản phẩm mới nếu chưa có dữ liệu bán.
8. **Chiến Lược Tối Ưu SEO & Tích Hợp Rank Math (Rank Math SEO Integration & Anti-Duplication):**
   - **Mở khóa Frontend Rank Math:** Khi plugin `seo-by-rank-math` chưa hoàn tất wizard, flag `$this->container['registration']->invalid` sẽ âm thầm vô hiệu hóa toàn bộ frontend output. Bắt buộc duy trì `rank_math_registration_skip = 1` trong bảng `wp_options` hoặc định nghĩa hằng số `RANK_MATH_REGISTRATION_SKIP = true`.
   - **Tuyệt đối không trùng lặp (Anti-Duplication Principle):** Không hardcode thẻ `<meta name="description">` hay in thẻ `<script type="application/ld+json">` riêng cho Product/Organization trong `header.php` khi plugin SEO đang hoạt động.
   - **Tích hợp module `inc/seo.php`:**
     - Dùng filter `rank_math/json_ld` để làm giàu trực tiếp đối tượng `$data['richSnippet']` của Rank Math (thêm `brand: Xiaomi`, `sku`), giữ vững cấu trúc 1 entity `@type: Product` duy nhất trong `@graph`.
     - Gỡ bỏ hook `WC()->structured_data->output_structured_data` ở `wp_footer` của WooCommerce để chống in lặp BreadcrumbList.
     - Dùng filter `rank_math/frontend/description` cho fallback meta description của Trang chủ.
     - Dùng filter `wp_get_attachment_image_attributes` để tự động điền `alt` thông minh cho mọi hình ảnh thiếu `alt`.
     - Tự động kiểm tra cờ `rank_math_flush_rewrite` trong action `init` (priority 99) để thực thi `flush_rewrite_rules(false)`, triệt tiêu lỗi 404 của `/sitemap_index.xml`.
   - **Cơ chế Nhường Quyền ở Plugin Nội Bộ (ERP Schema Yield):** Trong plugin nội bộ như `viomi-erp-system`, hàm `render_schema()` phải kiểm tra `defined('RANK_MATH_VERSION') || function_exists('rank_math')` để return sớm, tránh in lặp schema `Article` và `BreadcrumbList` cạnh tranh với plugin SEO.
   - **Chuẩn hóa Phân cấp Thẻ Heading (Document Outline):** Mọi trang chỉ có duy nhất 1 thẻ `<h1>`. Trên trang danh mục (`archive-product.php`), các tiêu đề widget thanh bên và bộ lọc bắt buộc dùng `<h3>`, không dùng `<h2>` đứng trước `<h1>` gây đảo lộn thứ tự DOM. Thẻ `add_theme_support('rank-math-breadcrumbs')` phải được đăng ký trong `theme-setup.php`.
9. **Chuẩn Hóa Đường Dẫn Rút Gọn Tối Đa (Flat URL & Permalinks Beautification):**
   - Loại bỏ triệt để các tiền tố rườm rà:
     - URL sản phẩm: `domain.com/ten-san-pham/` (loại bỏ `/san-pham/` qua `wc_remove_product_base = 'on'`).
     - URL danh mục sản phẩm: `domain.com/ten-danh-muc/` (loại bỏ `/product-category/` qua `wc_remove_category_base = 'on'`).
     - URL danh mục con: `domain.com/ten-danh-muc-con/` (loại bỏ slug cha qua `wc_remove_category_parent_slugs = 'on'`).
     - URL danh mục bài viết: `domain.com/ten-chuyen-muc/` (loại bỏ `/category/` qua `strip_category_base = 'on'`).
   - Mọi URL cũ chứa tiền tố phải tự động được Rank Math chuyển hướng 301 (`HTTP 301 Moved Permanently`) sang URL rút gọn mới để bảo vệ 100% thứ hạng SEO và trải nghiệm người dùng.
10. **Quy Chuẩn Chiều Rộng Khung Chứa Thống Nhất (Uniform Container Width):**
   - Mọi trang và component (Header, Thân trang, Chân trang) bắt buộc dùng chung chuẩn container duy nhất: `max-w-[1440px] mx-auto px-4`.
   - Tuyệt đối **KHÔNG** dùng class `container` của Tailwind kết hợp với `max-w-[1440px]`, vì class `container` sẽ ép cứng max-width tại các breakpoint (1280px), gây hiện tượng giật co hẹp giao diện giữa các trang.
   - Tuyệt đối **KHÔNG** dùng padding dị biệt ở Header (`px-12`, `px-20`) làm lệch mép Logo / Search box so với mép banner và lưới sản phẩm bên dưới.
11. **Quy chuẩn Bộ Nhận Diện Thương Hiệu & Logo (TongKhoMi.com Brand Assets):**
   - Thương hiệu chính thức: **TongKhoMi.com** (Tổng Kho Xiaomi Chính Hãng).
   - Màu sắc chủ đạo: Cam Xiaomi (`#f95700` / `#ff6900`), Xanh phiến thạch / Slate navy cao cấp (`#1e293b`), Trắng (`#ffffff`).
   - Tài nguyên logo chuẩn đặt tại `wp-content/themes/omni-commerce/assets/images/`:
     - `tongkhomi-logo.webp` (933x298px, lossless WebP nền trong suốt, dung lượng siêu nhẹ ~67KB).
     - `tongkhomi-logo.png` (933x298px, transparent PNG fallback).
     - `tongkhomi-icon.png` (512x512px, chuẩn WordPress Site Icon, PWA & Apple Touch Icon).
     - `favicon.png` (32x32px, chuẩn Favicon đa nền tảng).
   - Tối ưu hiệu năng hiển thị: Logo Header bắt buộc sử dụng class chuyên dụng `.mi-brand-logo` (`height: 48px; max-height: 48px; width: auto; object-fit: contain;` trên Desktop và `42px` trên Mobile) kèm thẻ `<picture>` nạp WebP ưu tiên (fallback PNG) và thuộc tính `width="150" height="48" fetchpriority="high"`. Tuyệt đối không dùng class Tailwind tùy biến (như `h-[62px]`) vì không có trong precompiled CSS sẽ làm mất ràng buộc chiều cao khiến logo bị phình to bất thường. Kích thước 48px đảm bảo đồng bộ hoàn hảo với logo chân trang (Footer).
12. **Quy Chuẩn Hiển Thị Thẻ Sản Phẩm & Giá Cả Trên Mobile (Mobile Product Card & Price Standards):**
   - **Cấu trúc Thẻ Sản phẩm (`.product-card`):** Bắt buộc có các class `w-full min-w-0 overflow-hidden` và padding `p-2.5 sm:p-3` để thẻ sản phẩm luôn bị giới hạn chuẩn xác 100% trong cột lưới (grid cell 50% màn hình), tuyệt đối không để nội dung bên trong làm phình to hoặc tràn viền card.
   - **Phân tầng giá linh hoạt (2-Tier Responsive Price):**
     - Trên Mobile (< 640px): Khối giá phải dùng `flex-col items-start`. Dòng 1 hiển thị giá khuyến mãi nổi bật (`text-sm font-black text-secondary whitespace-nowrap`). Dòng 2 hiển thị giá gốc gạch ngang kèm badge giảm giá (`text-[11px] text-slate-400 line-through` + `-26%`). Tuyệt đối không ép 2 giá tiền triệu (9-12 ký tự) nằm ngang cùng một dòng trên mobile vì chiều rộng khả dụng của card chỉ ~134px, sẽ làm tràn chữ ra ngoài viền phải và gây vỡ layout (horizontal scroll).
     - Trên Desktop (≥ 640px): Tự động chuyển `sm:flex-row sm:items-baseline` để 2 giá nằm cạnh nhau trên cùng một dòng ngang.
   - **Chống tràn ngang toàn trang:** Thẻ `<main id="primary">` sử dụng `class="overflow-x-clip"` để chặn triệt để hiện tượng trôi lệch ngang trên màn hình di động mà không ảnh hưởng tới `sticky top-0` của Header.

13. **Chuẩn Hóa Đa Tác Tử CrewAI Enterprise & Cổng Omni CrewAI Management Portal**:
    - **Tự động đồng bộ Rank Math SEO & CSDL MariaDB**: Bổ sung trường `quality_score` và `cost_usd` trong `wp_crewai_jobs`. Tự động map meta key `rank_math_title`, `description`, `focus_keyword`, `seo_score` ngay khi hoàn tất nhiệm vụ.
    - **Custom Admin Columns & Quick Filter**: Hiển thị điểm chất lượng và chi phí tại màn hình danh sách Post (`edit.php`) và Product (`edit.php?post_type=product`).
    - **Vector RAG Sync sang ChromaDB**: Đồng bộ bài viết WordPress sang vector collection của ChromaDB qua lệnh `wp crewai sync-rag` và endpoint `/api/v1/rag/sync-wordpress`.
    - **Tự Động Hóa Xử Lý Hình Ảnh Chuẩn SOP 11**: Endpoint `/api/v1/tools/process-image` tự động crop vuông 1:1, xóa text rác bằng thuật toán Telea inpaint viền 5-8%, đóng watermark chìm TongKhoMi (độ mờ 30%), và xuất đồng thời 5 kích cỡ WebP + 5 file JPG dự phòng.
    - **AI Safety & Input Guardrails Inspector**: Endpoint `/api/v1/guardrails/inspect` quét phát hiện Prompt Injection và tự động che giấu thông tin nhạy cảm PII (CCCD 12 số, SĐT VN, Thẻ tín dụng, Email).
    - **Omnichannel Incident Alerting Gateway**: Endpoint `/api/v1/notifications/test-alert` & `/recent` phát cảnh báo tức thời tới Telegram Bot / Webhook và lưu lịch sử sự cố gần nhất.
    - **WP-CLI Subsystem (11 Lệnh)**: Cung cấp bộ lệnh CLI hoàn chỉnh: `wp crewai health`, `dispatch`, `list-jobs`, `status`, `logs`, `retry-dlq`, `purge-logs`, `sync-rag`, `process-image`, `test-alert`, `check-guardrails`.
    - **WP-Cron Health Daemon**: Tự động ping microservice hàng giờ (`omni_crewai_hourly_health_check`) và dọn dẹp log quá hạn hàng tuần (`omni_crewai_weekly_log_cleanup`).
    - **Xác Thực 2 Chiều Application Passwords**: Hook `add_filter('wp_is_application_passwords_available', '__return_true');` bảo đảm thông suốt 2 chiều giữa WordPress và FastAPI với độ trễ siêu thấp (8.8ms - 447ms).
    - **Copilot Meta Box & AJAX hooks**: Nhúng widget `Omni CrewAI Copilot` vào sidebar bài viết/sản phẩm (`post.php`), cung cấp 2 hành động nhanh (tối ưu SEO và tạo ảnh SOP 11). Toàn plugin hiện đăng ký 81 AJAX hooks (78 Admin, 2 MetaBox, 1 Cron); xem [AGENTS.md của plugin](plugins/omni-crewai-portal/AGENTS.md) để biết phạm vi và ngoại lệ xác thực.
    - **LLM-as-a-Judge Automated Evaluation Engine**: Thẩm định tự động 4 tiêu chí định lượng (SOP 12 Compliance 30%, Policy Links 25%, Readability/CRO Tone 25%, Brand Guard 20%), phạt điểm 8 mẫu câu sáo rỗng AI, ngưỡng xuất bản $\ge 75.0/100$ điểm và tập dữ liệu kiểm chuẩn vàng (Golden Benchmark Dataset) 5 sản phẩm Xiaomi.
14. **Quy Chuẩn Dịch Vụ Bộ Nhớ Đồ Thị Tác Tử AI (Graph Memory Service & Knowledge Graph Navigation)**:
    - **Context Bounding & Surface Takeover**: Chỉ duy trì tối đa 5 turn hội thoại hoàn thành gần nhất trên bề mặt hiển thị mô hình, loại bỏ toàn bộ reasoning và tool traces của các turn đã xong nhằm tiết kiệm 60-80% token, lưu giữ toàn bộ event log gốc trong máy chủ.
    - **Turn-Memory Navigation (SPO)**: Mỗi turn hoàn thành được tóm tắt thành 1 câu duy nhất và dẫn xuất bộ ba Chủ-Vị-Tân (SPO). Các Node đồ thị chỉ đóng vai trò điều hướng, câu hỏi và câu trả lời hoàn chỉnh được bảo toàn nguyên vẹn làm bằng chứng thật.
    - **Cộng Đồng LPA & Personalized PageRank**: Sử dụng thuật toán Local Label Propagation để phát hiện cộng đồng tri thức cục bộ và Personalized PageRank tại thời điểm truy vấn để xếp hạng node chính xác theo ngữ cảnh.
    - **Phân Định 2 Tầng Bộ Nhớ**: Tầng 1 (ChromaDB SQLite) lưu trữ vector bài viết SEO và WooCommerce; Tầng 2 (Graph Memory SQLite `./.memory/`) lưu trữ ngữ cảnh hội thoại, quan hệ thực thể và truy hồi đa phiên cho AI Agent.
15. **Quy Chuẩn CI/CD, Sao Lưu MariaDB & Triển Khai Không Gián Đoạn (Zero-Downtime Deployment & K3s)**:
    - **Tự động sao lưu trước khi cập nhật**: Jenkins pipeline luôn thực thi `mysqldump` xuất bản ghi MariaDB nén gzip vào `/home/ubuntu/backups/` trước khi áp dụng bất kỳ thay đổi nào lên production.
    - **Rolling deployment cuộn từng node**: Kịch bản Ansible `4_deploy_rolling.yml` cấu hình `serial: 1`, rút từng node khỏi load balancer, cập nhật container và kiểm tra health check probe loop (`http://localhost:8080` mã 200) tối đa 10 lần trước khi kết nối lại và chuyển sang node tiếp theo.
    - **Phân tách Secret**: Tuyệt đối không lưu mật khẩu hoặc token trong `Jenkinsfile` hoặc `docker-compose.yml`, sử dụng biến môi trường hoặc Jenkins Credentials Provider.
    - **Kubernetes / K3s Production Readiness**: Triển khai `crewai-service` qua cụm manifests tại `deploy/k8s/` với Strategy `Recreate` để bảo toàn tính toàn vẹn của tệp CSDL SQLite, giới hạn tài nguyên và RBAC chặt chẽ cho in-cluster sandbox runner.
16. **Quy Chuẩn Phân Cấp Template & Trang Chuyên Dụng Theme Omni-Commerce**:
    - **Trang Tin Tức (`page-tin-tuc.php`)**: Layout Magazine Grid chuẩn tạp chí công nghệ gồm 5 bài nổi bật (1 bài lớn cao 320px + 4 bài nhỏ 4/3), thanh lọc category dạng pill badges bo tròn với hiệu ứng hover mượt mà, danh sách bài viết ảnh 16/9 kèm phân trang chuẩn `paginate_links()` và sticky sidebar "TIN XEM NHIỀU" lấy top 5 bài nhiều bình luận nhất.
    - **Trang Liên Hệ (`page-lien-he.php`)**: Form gửi yêu cầu an toàn xác thực Nonce (`mi_contact_action`), làm sạch dữ liệu (`sanitize_text_field`, `sanitize_email`, `sanitize_textarea_field`), gửi email tức thời qua `wp_mail()`, nhúng bản đồ Google Maps và danh sách showroom Tổng Kho Xiaomi.
    - **Trang Tra Cứu Bảo Hành (`page-tra-cuu-bao-hanh.php`)**: Wrapper chuyên dụng cho shortcode ERP `[mi_warranty_check]` tra cứu tức thời qua SĐT hoặc Serial/IMEI.
    - **Trang Tĩnh & Chính Sách (`page.php`)**: Tự động nhận diện trang chính sách (`is_policy`) để áp dụng bố cục 2 cột chuyên nghiệp: Sticky sidebar liên kết các chính sách liên quan bên trái và thẻ nội dung typography viền accent cam Xiaomi, badge chứng nhận bên phải.
    - **Chi Tiết Bài Viết (`single.php`)**: Bố cục 2 cột với typography `prose prose-slate` thoáng đạt, tiêu đề H1 chuẩn mực, meta tác giả/ngày đăng, hỗ trợ phân trang bài viết dài (`wp_link_pages`) và sticky sidebar "TIN XEM NHIỀU".
    - **Biên dịch Tailwind CSS qua Docker**: Tuyệt đối không commit file nhị phân lớn (`tailwindcss-linux-x64`) vào repo (BUG-018 / LESSON-08). Mọi thao tác biên dịch thực thi qua container Docker: `docker run --rm -v $(pwd):/app -w /app node:20-alpine sh -c "npx tailwindcss -i ./src/input.css -o ./style.css --minify"`.
17. **Hệ Thống 10 Giao Diện Quản Trị Nghiệp Vụ Viomi ERP System**:
    - **POS Dashboard (`pos-dashboard.php`)**: Giao diện bán lẻ 2 cột, chọn kho Master, 3 cấp địa chỉ, tích hợp bản đồ Leaflet/OSM tự động đo khoảng cách (miễn phí 20km đầu, trên 20km tính 10.000đ/km), quét Serial trực tiếp, tạo đơn WooCommerce và đồng bộ tài chính tức thì.
    - **SCM Inventory Dashboard (`inventory-dashboard.php`)**: 4 Tabs (Danh Sách Tồn Kho, Nhập Kho Inbound hỗ trợ textarea/file .txt gán giá vốn, Chuyển Kho Transfer, Xuất Kho Khác Outbound).
    - **SCM Warehouse Dashboard (`warehouse-dashboard.php`)**: Quản trị Master Data kho hàng vật lý cho toàn bộ hệ sinh thái.
    - **CRM Customer Dashboard (`customer-dashboard.php`)**: Tra cứu SĐT 1-chạm, hồ sơ khách hàng Lifetime Value (LTV), lịch sử thiết bị & bảo hành, Ticket & RMA System.
    - **Warranty Shortcode (`warranty-shortcode.php`)**: Giao diện tra cứu công khai `[mi_warranty_check]` theo SĐT hoặc Serial/IMEI với badge trạng thái trực quan.
    - **Finance Dashboard (`finance-dashboard.php`)**: 3 Thẻ chỉ số thời gian thực (Thu, Chi, Lợi nhuận ròng), nút đồng bộ đơn hàng cũ `wc_get_orders()`, Sổ Quỹ Tiền Mặt & Ngân Hàng kèm Modal thu/chi.
    - **Procurement Dashboard (`procurement-dashboard.php`)**: Quản trị nhà cung cấp, Đơn đặt hàng PO (khi `received` tự động tạo phiếu chi Finance và nhập mã serial kho SCM).
    - **HRM Dashboard (`hrm-dashboard.php`)**: 4 Tabs (KPIs & Bảng Lương tự động, Cài đặt cơ chế lương cứng & hoa hồng, Quản lý nhân sự & phân quyền module động, Quản lý phòng ban).
    - **Manufacturing Dashboard (`manufacturing-dashboard.php`)**: Quản lý Lệnh sản xuất (WO), tự động trừ linh kiện tiêu hao BOM và nhập kho thành phẩm mới vào SCM khi `completed`.
    - **Orders Dashboard (`orders-dashboard.php`)**: Bảng điều khiển DataTables tập trung và Modal gán mã Serial (`#mi-erp-assign-modal`) cho từng sản phẩm trong đơn hàng WooCommerce.

## 5. Ma Trận Tài Liệu Kỹ Thuật Độc Lập Toàn Bộ Hệ Thống (Master Documentation Suite)

| Phân Hệ | Tài Liệu Hướng Dẫn (`README.md`) | Sổ Tay AI Agent (`AGENTS.md`) | Tài Liệu Kỹ Thuật Chuyên Sâu |
| :--- | :--- | :--- | :--- |
| **Toàn Hệ Thống** | [README.md](../README.md) | [wp-content/AGENTS.md](AGENTS.md) | [architecture.md](../../.gemini/antigravity-ide/knowledge/michinhhang_wordpress_architecture/artifacts/architecture.md) |
| **Theme Bán Hàng (Omni-Commerce)** | [omni-commerce/README.md](themes/omni-commerce/README.md) | [omni-commerce/AGENTS.md](themes/omni-commerce/AGENTS.md) | [PROJECT_BLUEPRINT.md](themes/omni-commerce/PROJECT_BLUEPRINT.md), 13 file `ai-docs/`, 6 trang chuyên dụng |
| **Plugin ERP (Viomi ERP System)** | [viomi-erp-system/README.md](plugins/viomi-erp-system/README.md) | [viomi-erp-system/AGENTS.md](plugins/viomi-erp-system/AGENTS.md) | 11 bảng MariaDB (`wp_mi_*`), 10 UI Dashboards, 8 modules cốt lõi |
| **Cổng Đa Tác Tử (Omni CrewAI Portal)** | [omni-crewai-portal/README.md](plugins/omni-crewai-portal/README.md) | [omni-crewai-portal/AGENTS.md](plugins/omni-crewai-portal/AGENTS.md) | 81 AJAX hooks, 15 lệnh WP-CLI, 8 SPA Tabs, 9 Modals, Copilot Meta Box |
| **Lõi AI Microservice (crewai-service)** | [crewai-service/README.md](../../crewai-service/README.md) | [crewai-service/AGENTS.md](../../crewai-service/AGENTS.md) | 32 Trụ Cột Enterprise, 22 Files Core, 22 Test Suites |
| **Trí Nhớ Đồ Thị (graph-memory-service)** | [graph-memory-service/README.md](../../graph-memory-service/README.md) | [Takeover Specs](../../graph-memory-service/README.md#bounded-model-surface-takeover-specs) | 5-turn Takeover, SPO Navigation, Local LPA & PPR |
| **Hạ Tầng CI/CD & DevOps** | [Jenkinsfile](../../Jenkinsfile) | [4_deploy_rolling.yml](../../4_deploy_rolling.yml) | `inventory.ini`, `setup.sh`, k3s manifests |
