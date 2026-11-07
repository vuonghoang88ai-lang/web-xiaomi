# SOP 50: TỰ ĐỘNG THÁM SÁT CẤU TRÚC WEB & CỔNG TƯƠNG TÁC ĐA PLUGIN TOÀN NĂNG (AUTONOMOUS SITE TOPOLOGY & UNIVERSAL PLUGIN INTEROPERABILITY GATEWAY)

> **Mã quy chuẩn:** SOP 50 — AUTONOMOUS SITE TOPOLOGY & UNIVERSAL PLUGIN INTEROPERABILITY  
> **Áp dụng:** Omni Bridge Connector (v1.4.1+), Agent Engine Gateway, Database Schema & AI Copilot  
> **Mục tiêu:** Khi cài plugin lên website khách hàng, hệ thống tự động bóc tách 100% cấu trúc thực tế (CPTs, Taxonomies, Active Plugins, ERP, REST routes), lưu trữ vào PostgreSQL và kích hoạt cổng tương tác động cho phép AI điều khiển mọi plugin mà không cần viết bot riêng biệt hay hardcode mã nguồn.

---

## 1. NGUYÊN LÝ THIẾT KẾ ĐA NĂNG (UNIVERSAL ARCHETYPE ENGINE)

### 1.1. Giải pháp cho bài toán "Viết Blog vs Đăng Sản Phẩm có cần bot riêng không?"
- **Phản mẫu (Anti-Pattern):** Tạo ra các bot rời rạc (`Bot Blog`, `Bot WooCommerce`, `Bot Truyện`) dẫn đến phân mảnh tri thức, phình to mã nguồn và đòi hỏi người dùng phải chuyển đổi bot thủ công.
- **Chuẩn mực SOP 50:** Sử dụng **1 AI Copilot Toàn Năng duy nhất kết hợp Máy Trạng Thái Động (Dynamic Finite State Router)**. 
- AI Copilot tự động nhận diện ý định và căn cứ vào `site_topology` thực tế của website để ánh xạ sang Post Type tương ứng:
  - Nếu site có WooCommerce (`product`): Xuất bản dạng Product kèm SKU, giá kho, biến thể và thuộc tính.
  - Nếu site là Tin tức/Blog (`post`): Xuất bản bài viết chuẩn SEO SOP 12, mục lục TOC và trích dẫn E-E-A-T.
  - Nếu site là Cổng đọc truyện (`truyen`, `chapter`): Xuất bản chương hồi, phân cấp Silo tác giả, dịch giả.
  - Nếu site có Custom Post Types bất kỳ: AI tự động phân bổ trường dữ liệu vào đúng CPT đó.

---

## 2. KIẾN TRÚC THÁM SÁT CẤU TRÚC WEB TỰ ĐỘNG (AUTONOMOUS SITE TOPOLOGY)

### 2.1. Trait Thám sát Nội bộ (`class-introspection-trait.php`)
Khi plugin được kích hoạt hoặc khi nhận lệnh đồng bộ, Omni Bridge Connector tự động chạy tiến trình thám sát đa tầng:
1. **Core & Theme Environment:** Phiên bản WordPress, PHP, Theme đang kích hoạt (Parent + Child), trạng thái HTTPS, Permalink Structure.
2. **Post Types & Taxonomies Discovery:**
   - Quét toàn bộ `get_post_types(['public' => true], 'objects')`.
   - Liệt kê các tính năng hỗ trợ (`title`, `editor`, `thumbnail`, `custom-fields`).
   - Bóc tách Taxonomies liên kết (`category`, `post_tag`, `product_cat`, `the-loai`).
3. **Ecosystem & ERP Discovery:**
   - Quét danh sách toàn bộ plugin đang kích hoạt (`get_option('active_plugins')`).
   - Tự động nhận diện các hệ sinh thái thương mại và quản trị: WooCommerce, WP ERP, FluentCRM, Rank Math, Yoast SEO, Advanced Custom Fields (ACF), JetEngine.
4. **REST API Endpoints:** Khám phá toàn bộ Namespace REST đang đăng ký trên WordPress core.

### 2.2. Vận chuyển & Lưu trữ Dữ liệu Cấu trúc (`site_topology`)
- Omni Bridge Connector tự động đóng gói payload JSON và gửi về Portal Gateway qua hai kênh:
  - Bắt tay kích hoạt ban đầu: `telemetry-sync` trong luồng kết nối tự động SOP 48.
  - Yêu cầu đồng bộ chủ động: `/api/v1/portal/projects/{id}/topology/sync`.
- Portal Gateway tiếp nhận và lưu trữ trực tiếp vào cột `site_topology JSONB` trong bảng `projects` tại PostgreSQL.

```
+--------------------------+       REST Push (/telemetry-sync)       +--------------------------+
|  Omni Bridge Connector   | --------------------------------------> |      Portal Gateway      |
|  (WordPress Introspect)  |                                         |     (FastAPI Server)     |
+--------------------------+                                         +--------------------------+
             |                                                                     |
             | Bóc tách:                                                           | Lưu trữ:
             | - Post Types & Taxonomies                                           v
             | - Active Plugins & ERP                                +--------------------------+
             | - REST Routes & Theme                                 |    PostgreSQL Projects   |
             |                                                       |   `site_topology JSONB`  |
             +-----------------------------------------------------> +--------------------------+
```

---

## 3. CỔNG TƯƠNG TÁC ĐA PLUGIN TOÀN NĂNG (UNIVERSAL PLUGIN INTEROPERABILITY GATEWAY)

### 3.1. Trait Tương tác Đa Plugin (`class-plugin-interop-trait.php`)
Để hệ sinh thái AI có thể điều khiển và phối hợp với bất kỳ plugin nào trên WordPress (kể cả phần mềm ERP nội bộ), Omni Bridge Connector thiết lập cổng thực thi bảo mật:
- **Endpoint:** `POST /wp-json/omni-bridge/v1/interop/execute`
- **Cơ chế xác thực:** Bắt buộc Header `X-Bridge-Secret` hoặc HMAC token đã thiết lập trong bắt tay SOP 48.
- **3 Chế độ Thực thi Động (Execution Modes):**
  1. `action` (`do_action`): Kích hoạt các Action Hooks của WordPress hoặc plugin bên thứ ba kèm mảng đối số.
  2. `filter` (`apply_filters`): Truy vấn dữ liệu hoặc định dạng chuỗi qua Filter Hooks của plugin khác.
  3. `callable` (`call_user_func_array`): Gọi trực tiếp các hàm API công khai của các plugin khác (ví dụ: `wc_get_product()`, hàm ERP inventory sync).

### 3.2. Cầu nối API Phía Server Gateway
- Portal phơi bày endpoint chuẩn mực:  
  `POST /api/v1/portal/projects/{project_id}/interop/execute`
- Cho phép AI Agent tự động gọi lệnh truy vấn tồn kho ERP, cập nhật trạng thái đơn hàng, hoặc kích hoạt tiến trình nén ảnh qua bất kỳ plugin nào mà không cần viết thêm mã nguồn trên WordPress.

---

## 4. TIÊU CHUẨN BẢO MẬT & KIỂM THỬ AN TOÀN

1. **Khóa cứng Quyền hạn (Capability Guard):** Cổng tương tác chỉ chấp nhận lệnh từ hệ thống Gateway đã bắt tay hợp lệ.
2. **Khử trùng Tên hàm & Hook (Callable Sanitization):** Tuyệt đối cấm thực thi các hàm hệ thống nguy hiểm (`system`, `exec`, `shell_exec`, `passthru`, `eval`). Chỉ cho phép các hàm PHP hợp lệ (`is_callable`) hoặc các hook đã đăng ký.
3. **Kỷ luật Mã nguồn (SOP 23):** Toàn bộ các trait tách nhỏ (`class-introspection-trait.php`, `class-plugin-interop-trait.php`) và route xử lý phía Python (`plugin_interop.py`) BẮT BUỘC duy trì $\le 300$ dòng code.

