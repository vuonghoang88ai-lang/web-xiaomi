# Viomi ERP System — AI Agent Rules & Knowledge

## Thông tin dự án
| Mục | Giá trị |
|-----|---------|
| **Tên** | viomi-erp-system |
| **Loại hình** | WordPress Plugin (Sản phẩm độc lập) |
| **Nền tảng** | WordPress + WooCommerce + MariaDB |
| **Chức năng** | Quản lý kho, Xuất/nhập, POS, CRM, Tài chính (Sổ quỹ), Mua hàng (PO), Nhân sự (KPI), Sản xuất. |
| **Tình trạng** | Đã hoàn thiện toàn bộ 6/6 Giai đoạn (Master Roadmap). |

## 1. Nguyên tắc cốt lõi (Core Principles)
- **Độc lập Logic:** Plugin phải tự đóng gói logic xử lý (PHP, AJAX, Database). Không phụ thuộc cứng vào hàm riêng của Theme ngoại trừ các tiêu chuẩn của WordPress/WooCommerce.
- **Tích hợp TailwindCSS độc lập:** Plugin phải có quy trình build TailwindCSS riêng biệt (`tailwind.config.js` riêng trong thư mục plugin) và tạo ra file CSS độc lập. Tuyệt đối không share hoặc liệt kê file giao diện vào cấu hình Tailwind của Theme. Các quy chuẩn CSS inline vẫn được giữ nguyên (Không viết CSS inline).
- **Bảo mật & Tốc độ (Core):** Mọi truy vấn DB phải dùng `$wpdb->prepare()`. Mọi AJAX phải kiểm tra `check_ajax_referer` (Nonce). Tránh vòng lặp query trong vòng lặp lớn. Tuyệt đối không query toàn bộ data rồi dùng vòng lặp PHP để tính tổng (VD: Lấy toàn bộ giao dịch để tính doanh thu), bắt buộc dùng `SUM()` và `GROUP BY` trực tiếp trên MySQL.
- **Chuẩn Mã hóa (WordPress Coding Standards - WPCS):**
  - **Escaping Output (XSS Protection):** Bắt buộc sử dụng `esc_html()`, `esc_attr()`, `esc_url()`, hoặc `wp_kses_post()` khi in dữ liệu động ra HTML.
  - **Sanitization (Input Data):** Mọi dữ liệu từ `$_POST`, `$_GET` trước khi xử lý phải đi qua `sanitize_text_field()`, `sanitize_textarea_field()`, hoặc `intval()`, `floatval()`.
  - **Đa ngôn ngữ (Translation Ready):** Khuyến khích bọc các chuỗi ký tự tĩnh hiển thị cho người dùng vào hàm `__()` hoặc `_e()` với text domain là `'mi-erp-system'`.

## 2. Kiến trúc CSDL (Database Schema - 11 Bảng CSDL)
- **Bảng chính:** 
  - `wp_mi_serials` (Quản lý mã Serial).
  - `wp_mi_inventory_logs` (Lưu lịch sử thay đổi trạng thái và vết xuất/nhập/chuyển kho/mã Serial).
  - `wp_mi_warehouses` (Quản lý các kho chứa).
  - `wp_mi_inventory_transactions` (Lưu vết giao dịch xuất/nhập/chuyển kho).
  - `wp_mi_tickets` (Quản lý khiếu nại, bảo hành, RMA).
  - `wp_mi_transactions` (Sổ quỹ Tài chính - Thu/Chi).
  - `wp_mi_suppliers` (Danh sách Nhà cung cấp).
  - `wp_mi_purchase_orders` (Đơn đặt hàng PO).
  - `wp_mi_po_items` (Chi tiết linh kiện/sản phẩm trong PO).
  - `wp_mi_employee_metrics` (Bảng lương thưởng, KPI nhân sự).
  - `wp_mi_production_orders` (Lệnh sản xuất, lắp ráp thành phẩm).
- **Các trường quan trọng:**
  - `serial_number` (varchar): Mã định danh duy nhất của sản phẩm.
  - `product_id` (int): Liên kết tới WooCommerce Product.
  - `status` (varchar): Trạng thái (ví dụ: `sold`, `instock`).
  - `order_id` (int): Liên kết tới WooCommerce Order (khi đã bán).
  - `sold_date` (datetime): Ngày kích hoạt / xuất kho.

## 3. Quy chuẩn Frontend & Hệ Thống 10 Giao Diện Quản Trị (UI/UX Dashboards)
- **Kiến trúc Modular 8 phân hệ cốt lõi (Đã hoàn thiện):** Toàn bộ 8 phân hệ ERP đã được đóng gói thành công tại `includes/modules/`. Mỗi module đều có file `{module}-init.php`, thư mục `views/` và `class-{module}-ajax.php`. Tích hợp giao diện Dashboard tổng qua file `includes/admin/views/html-dashboard.php`.
- **Chi tiết 10 Màn Hình Giao Diện Nghiệp Vụ Cốt Lõi:**
  1. **Bán Hàng Nhanh Tại Quầy (POS Dashboard - `pos/views/pos-dashboard.php`)**:
     - *Bố cục 2 Cột Chuẩn Bán Lẻ Hiện Đại*: Cột trái quản lý Khách hàng & Điều phối vận chuyển; Cột phải quản lý Giỏ hàng, Quét Serial & Thanh toán.
     - *Phân Cấp Địa Chỉ 3 Cấp*: Tỉnh/Thành (`#pos_customer_province`), Quận/Huyện (`#pos_customer_district`), Phường/Xã (`#pos_customer_ward`) và Số nhà tên đường (`#pos_customer_street`).
     - *Tích Hợp Bản Đồ Leaflet / OSM & Đo Khoảng Cách Tự Động*: Nút `#pos_calc_distance_btn` mở bản đồ tương tác `#pos-map`, tự động tính khoảng cách giao hàng từ Kho xuất hàng (`#pos_origin_warehouse` từ `wp_mi_warehouses`) đến địa chỉ khách hàng.
     - *Chính Sách Phí Vận Chuyển Động*: **Miễn phí 20km đầu tiên**, từ km thứ 21 trở đi tự động tính **10.000đ/km**.
     - *Chọn Nhân Viên & Tính Hoa Hồng*: Dropdown `#pos_sales_employee` liên kết trực tiếp với cơ chế tính hoa hồng động của phân hệ HRM.
     - *Quét Serial Trực Tiếp & Tạo Đơn Siêu Tốc*: Hỗ trợ quét mã vạch/Serial trực tiếp tại quầy, tự động trừ kho, đổi trạng thái Serial thành `sold`, tạo đơn hàng WooCommerce và đồng bộ ngay vào sổ quỹ Finance.
  2. **Quản Lý Tồn Kho & Xuất Nhập (SCM Inventory - `scm/views/inventory-dashboard.php`)**:
     - *Hệ Thống 4 Tabs Nghiệp Vụ*:
       - Tab 1: *Danh Sách Tồn Kho (`tab-list`)*: Bảng dữ liệu theo thời gian thực hiển thị Sản phẩm, Mã Serial, Vị trí kho và Trạng thái.
       - Tab 2: *Nhập Kho Inbound (`tab-inbound`)*: Chọn Kho, Sản phẩm WooCommerce, Giá nhập (lưu thành Giá vốn Cost of Goods để tính KPI). Hỗ trợ nhập Serial hàng loạt qua textarea hoặc tải file `.txt` tự động đọc qua `FileReader`.
       - Tab 3: *Chuyển Kho Transfer (`tab-transfer`)*: Điều phối luồng hàng giữa các kho, quét mã Serial, lưu vết lịch sử giao dịch.
       - Tab 4: *Xuất Kho Outbound (`tab-outbound`)*: Xử lý xuất hàng cho mục đích xuất hủy, bảo hành hoặc hàng mẫu.
  3. **Quản Lý Hệ Thống Kho Hàng Master (SCM Warehouse - `scm/views/warehouse-dashboard.php`)**:
     - Quản trị danh mục kho vật lý (Tên kho, Địa chỉ, Trạng thái hoạt động active/inactive).
     - Đóng vai trò là **Master Data duy nhất** của hệ thống kho cho tất cả phân hệ (POS, Orders, SCM) truy vấn đồng bộ, loại bỏ phân mảnh dữ liệu (BUG-014).
  4. **Quản Lý Khách Hàng & CRM (Customer Dashboard - `crm/views/customer-dashboard.php`)**:
     - *Tra cứu khách hàng 1-chạm* qua Số điện thoại (`#crm-phone`).
     - *Thẻ Hồ Sơ Khách Hàng*: Hiển thị Họ tên, SĐT, Địa chỉ và Tổng chi tiêu tích lũy (Lifetime Value - LTV).
     - *Lịch Sử Thiết Bị & Bảo Hành*: Bảng tra cứu toàn bộ mã Serial khách đã mua, đơn hàng liên kết, ngày kích hoạt và trạng thái bảo hành (Còn hạn / Hết hạn).
     - *Hệ Thống Vé Khiếu Nại & RMA (Ticket System)*: Bảng theo dõi vé bảo hành kèm Modal tạo Ticket mới, phân công kỹ thuật viên, theo dõi tiến trình sửa chữa (`open`, `in_progress`, `resolved`, `closed`).
  5. **Giao Diện Tra Cứu Bảo Hành Cho Khách Hàng (Warranty Shortcode - `crm/views/warranty-shortcode.php`)**:
     - Giao diện tra cứu công khai phía frontend nhúng qua shortcode `[mi_warranty_check]` (container bo góc `rounded-xl`, shadow nhẹ).
     - Tra cứu tức thời qua **Số điện thoại** hoặc **Mã Serial/IMEI**, hiển thị thẻ kết quả với badge trạng thái (Còn bảo hành màu xanh / Hết bảo hành màu đỏ), ngày mua và hạn bảo hành.
  6. **Sổ Quỹ Tài Chính & Thu Chi (Finance Dashboard - `finance/views/finance-dashboard.php`)**:
     - *3 Thẻ Chỉ Số Tài Chính Thời Gian Thực*: Tổng Doanh Thu (`#fin-total-income` viền xanh lá), Tổng Chi Phí (`#fin-total-expense` viền đỏ), Lợi Nhuận Ròng (`#fin-net-profit` viền xanh dương).
     - *Nút Đồng Bộ Đơn Hàng Cũ (`#btn-sync-finance`)*: Tự động quét các đơn hàng hoàn thành trong quá khứ qua `wc_get_orders()` để ghi nhận bổ sung doanh thu (khắc phục BUG-012).
     - *Sổ Quỹ Tiền Mặt & Ngân Hàng*: Bảng nhật ký giao dịch tài chính (`wp_mi_transactions`) kèm Modal ghi chép thu/chi phát sinh ngoài đơn hàng.
  7. **Mua Hàng & Nhà Cung Cấp (Procurement Dashboard - `procurement/views/procurement-dashboard.php`)**:
     - *Quản Lý Danh Bạ Nhà Cung Cấp*: Bảng DataTables đối tác cung ứng kèm Form thêm mới (Tên, SĐT, Email).
     - *Quản Lý Đơn Đặt Hàng (PO)*: Form tạo PO mới chọn Nhà cung cấp, nhập tổng tiền ước tính, theo dõi tiến trình giao hàng.
     - *Liên Thông Tự Động*: Khi PO chuyển sang trạng thái `received`, hệ thống tự động sinh phiếu chi trong Finance và nhập mã serial vào kho SCM.
  8. **Quản Lý Nhân Sự, Phân Quyền & Bảng Lương (HRM Dashboard - `hrm/views/hrm-dashboard.php`)**:
     - *Hệ Thống 4 Tabs Chuyên Biệt*:
       - Tab 1: *Hiệu suất & Lương (KPIs)*: Bộ lọc theo phòng ban, trạng thái làm việc, chọn tháng/kỳ tính lương (`input type="month"`). Bảng tính lương tự động: Doanh thu bán, Giá vốn, Phí ship, Lương cứng, Hoa hồng, Khấu trừ/Tạm ứng (nhập trực tiếp), Thực nhận.
       - Tab 2: *Cài đặt Cơ chế Lương*: Thiết lập Lương cơ bản (`_mi_erp_base_salary`), Tỷ lệ hoa hồng (`_mi_erp_commission_rate`), cách tính (% Doanh thu hoặc % Lợi nhuận gộp).
       - Tab 3: *Quản lý Nhân sự*: Modal thêm/sửa nhân viên, tùy chọn "Không tính lương" (`_mi_erp_exclude_payroll`), phân quyền Module động qua checkboxes (SCM, POS, Finance, HRM, Procurement, Manufacturing, Orders).
       - Tab 4: *Phòng ban & Cơ cấu*: Quản lý danh mục phòng ban động (lưu trữ trong `wp_options`).
  9. **Lệnh Sản Xuất & Lắp Ráp (Manufacturing - `manufacturing/views/manufacturing-dashboard.php`)**:
     - Quản lý danh sách Lệnh sản xuất (Work Orders - WO), chọn ID sản phẩm WooCommerce thành phẩm và số lượng cần đóng gói/lắp ráp.
     - *Tự Động Trừ Kho & Nhập Thành Phẩm*: Khi WO chuyển trạng thái `completed`, tự động trừ các linh kiện tiêu hao (`consumed`) theo định mức BOM và sinh thành phẩm mới nhập kho SCM.
  10. **Điều Phối Đơn Hàng & Gán Serial (Orders Dashboard - `orders/views/orders-dashboard.php`)**:
      - Bảng điều khiển DataTables tập trung theo dõi trạng thái xuất Serial cho tất cả đơn hàng WooCommerce.
      - Modal gán mã Serial (`#mi-erp-assign-modal`): Cho phép nhân viên kho chọn hoặc quét mã Serial có sẵn trong kho tương ứng cho từng mặt hàng trong đơn hàng trước khi đóng gói bàn giao vận chuyển.
- **Chuẩn hóa Giao diện (TailwindCSS & UX):** Tuyệt đối **không sử dụng CSS inline** (`style="..."`). Toàn bộ giao diện (bao gồm `html-dashboard.php` và các file `views/`) phải được xây dựng bằng TailwindCSS. Áp dụng Responsive (Flex/Grid) và các hiệu ứng tương tác nhỏ (Micro-Interactions như `hover:`, `focus:`) để tăng tính cao cấp.
- **Trạng thái xử lý (Loading States & Feedback):** Mọi thao tác AJAX (Submit form, tải dữ liệu) phải đi kèm hiệu ứng Loading (Spinner/Skeleton) và vô hiệu hóa nút bấm tạm thời. Kết quả trả về phải được hiển thị qua Toast Message hoặc Alert Box rõ ràng thay vì chỉ in lỗi ra console.
- **Shortcode Tra cứu Bảo hành:** `[mi_warranty_check]`
  - Người dùng có thể tìm kiếm bằng **Số điện thoại** (tra qua `_billing_phone` của Order) hoặc **Mã Serial**.
  - Kết quả trả về qua AJAX (`action: mi_erp_check_warranty`).
  - Logic tính hạn bảo hành: Ưu tiên dùng ACF `get_field('mi_warranty_info', product_id)`, fallback về `get_post_meta`. Hạn bảo hành tính bằng: `sold_date + X tháng`.
- **Định tuyến (Routing):** Khi dùng `plugins_url` cho các file tĩnh nằm sâu trong thư mục con, **LUÔN DÙNG** `__FILE__` làm tham số thứ 2 thay vì `dirname(__FILE__)` để tránh lỗi 404 (Ví dụ: `plugins_url( 'js/script.js', __FILE__ )`).

## 4. Nhật ký lỗi (Bug Log) & Bài học kinh nghiệm
- **BUG-01 (Đường dẫn JS):** Việc dùng `dirname(__FILE__)` trong `plugins_url` khiến WP cắt mất thư mục cha, dẫn tới lỗi 404 cho file JS tĩnh. Cách giải quyết: Truyền thẳng `__FILE__`.
- **BUG-02 (AJAX Form Submit):** Nút submit trong form nếu không bị JS chặn (`e.preventDefault()`) sẽ làm tải lại trang (chớp màn hình) thay vì chạy AJAX. Luôn đảm bảo script được nạp thành công và bound đúng sự kiện.
- **BUG-03 (Tìm kiếm SĐT):** Truy vấn meta data (`_billing_phone`) cần dùng `LIKE %s` thay vì `=` vì định dạng SĐT có thể biến động. Kết hợp dùng `IN ()` với chuỗi placeholder chuẩn xác trong `$wpdb->prepare()`.
- **BUG-04 (CSS Grid & Absolute):** Khi thiết kế hộp kết quả (chứa nhiều sản phẩm), tuyệt đối không dùng `absolute inset-0` nếu không muốn cột bị sụp độ cao (làm hộp kết quả bị thu nhỏ, xuất hiện thanh cuộn gò bó). Chuyển sang dạng block tĩnh để cha giãn nở tự nhiên.
- **BUG-05 (Thao tác mã PHP bằng Script ngoài):** Khi dùng shell/python scripts để thao tác (find/replace) code PHP, ký tự `$` trong biến PHP (`$this`, `$wpdb`) rất dễ bị shell hoặc trình biên dịch nội suy làm mất tích, dẫn tới lỗi cú pháp PHP 500 Internal Server Error (VD: `->check_permission()` thay vì `$this->check_permission()`). Cách giải quyết: Hạn chế dùng regex/sed để sửa file PHP lớn, ưu tiên ghi đè file (`write_to_file`) an toàn hoặc phải escape `\$` kỹ lưỡng.
- **BUG-06 (JS Rendering wc_price):** Dữ liệu tiền tệ sinh ra từ hàm `wc_price()` của WooCommerce trả về là chuỗi HTML chứa các thẻ `<span class="woocommerce-Price-amount...">`. Nếu AJAX đẩy về frontend bằng JS/jQuery, tuyệt đối không được dùng hàm `.text()` vì sẽ làm hiển thị mã HTML thô. Bắt buộc phải dùng `.html()` để trình duyệt render đúng giao diện.
- **BUG-07 (DataTables - Showing 0 entries):** Nếu DataTables hiển thị dữ liệu nhưng góc dưới báo `Showing 0 to 0 of 0 entries`, nguyên nhân là do backend trả về `recordsTotal = 0`. Tránh dùng `wc_orders_count('all')` vì đôi khi WP/WC trả về 0 ở một số phiên bản. Thay vào đó, dùng vòng lặp qua `wc_get_order_statuses()` và đếm/cộng dồn từng trạng thái.
- **BUG-08 (DataTables - Lỗi CSS Form Control):** Khi nhúng DataTables vào màn hình WP Admin, class CSS mặc định của WP làm thẻ `<select>` (Show entries) bị thu hẹp bất thường và thẻ `<input>` (Search) bị mất style. Cách giải quyết: Không viết CSS inline rải rác ở từng file view, mà dùng hook `admin_head` tại `class-mi-erp-admin.php` để in thẻ `<style>` toàn cục (ép `width: 60px` cho select và định dạng border/focus cho input).
- **BUG-09 (Thiếu TailwindCSS trong WP Admin):** Mặc dù TailwindCSS đã được tích hợp qua Theme cho giao diện frontend, nhưng trong giao diện WP Admin (các trang Dashboard của Plugin), Tailwind mặc định KHÔNG được nạp. Điều này khiến các class như `hidden`, `flex`, `fixed` không hoạt động (làm vỡ form, Modal bị hiện nguyên hình). Cách giải quyết: Phải luôn đảm bảo dùng hook `admin_enqueue_scripts` để nhúng script CDN của Tailwind vào các trang backend của riêng ERP.
- **BUG-10 (Lỗi 0 kết quả khi dùng SQL thuần query Đơn hàng WC):** Kể từ WooCommerce 8.2, cấu trúc lưu trữ HPOS (High-Performance Order Storage) được bật mặc định. Việc dùng `$wpdb` query trực tiếp vào bảng `wp_posts` với `post_type = 'shop_order'` sẽ trả về 0 kết quả. Cách giải quyết: **TUYỆT ĐỐI không dùng raw SQL để lấy đơn hàng**, bắt buộc dùng class `wc_get_orders()`.
- **BUG-11 (Lỗi mất đơn khi dùng meta_query trong wc_get_orders với HPOS):** Khi dùng `wc_get_orders()` trên hệ thống HPOS, việc truyền tham số `meta_query` (đặc biệt là so sánh `EXISTS`) có thể không hoạt động ổn định và lọc trượt kết quả. Cách giải quyết: Ưu tiên query đơn giản theo trạng thái/thời gian (`status`, `date_created`), sau đó dùng vòng lặp PHP (`$order->get_meta()`) để kiểm tra dữ liệu meta thay vì ép vào args của `wc_get_orders`.
- **BUG-12 (Mất dữ liệu quá khứ khi dùng Action Hook):** Các module (như Finance) nếu chỉ dựa vào hook như `woocommerce_order_status_completed` để phát sinh dữ liệu (ghi doanh thu) sẽ bị bỏ sót toàn bộ đơn hàng hoàn thành trong quá khứ. Cách giải quyết: Khi xây dựng các tính năng chốt số liệu, luôn phải thiết kế kèm một chức năng "Đồng bộ (Sync)" sử dụng `wc_get_orders()` để quét và điền bù dữ liệu lịch sử.
- **LESSON-01 (UX Tìm kiếm Sản phẩm WooCommerce):** Tránh yêu cầu người dùng nhập thủ công ID sản phẩm. Hãy luôn sử dụng thư viện `Select2` (hoặc `selectWoo` của WooCommerce) kết hợp với custom AJAX endpoint (VD: `mi_erp_search_products`) để cho phép tìm kiếm theo Tên, Mã SKU và ID.
- **LESSON-02 (Xử lý dữ liệu hàng loạt - Bulk & File Reader):** Đối với các tác vụ yêu cầu nhập Mã Serial (Nhập/Chuyển/Xuất kho), luôn dùng `<textarea>` thay vì `<input text>` để có thể nhập nhiều dòng. Cần tích hợp input `<input type="file" accept=".txt">` và dùng JS `FileReader` đọc nội dung file đổ thẳng vào textarea (tránh việc upload file dư thừa lên server). Ở Backend, tách dòng `explode("\n")`, xử lý lặp và "bỏ qua lỗi cục bộ" (ghi nhận lỗi từng mã và continue) để không làm đứt gãy toàn bộ tiến trình lưu trữ của các mã đúng.
- **BUG-13 (Tràn khung giao diện do Flexbox và Select Box):** Khi dùng Flexbox để xếp ngang nhiều thẻ `<select>` chứa văn bản dài (như Phường/Xã), thẻ select sẽ tự giãn ra làm vỡ khung giao diện vì không thể co nhỏ hơn nội dung text bên trong. Cách giải quyết: Chuyển sang dùng Grid Layout (VD: `grid-cols-1 xl:grid-cols-3`) và bắt buộc thêm các class Tailwind `min-w-0 text-ellipsis overflow-hidden` để ép trình duyệt cắt ngắn chữ bằng dấu "..." nếu không đủ không gian.
- **BUG-14 (Dữ liệu phân mảnh/lệch pha giữa các module):** Xảy ra khi các module khác nhau sử dụng nguồn lưu trữ rời rạc cho cùng một loại dữ liệu (VD: POS đọc danh sách kho từ option JSON, trong khi SCM quản lý kho qua bảng SQL). Cách giải quyết: Loại bỏ hoàn toàn các trang cài đặt riêng lẻ, quy hoạch 1 module làm Master Data (VD: SCM làm Master cho Kho). Mọi module khác (như POS) bắt buộc phải `$wpdb` query trực tiếp vào bảng Master (`wp_mi_warehouses`) để đảm bảo tính đồng bộ tuyệt đối theo thời gian thực.
- **LESSON-03 (Cá nhân hóa logic hệ thống qua User Meta - HRM):** Tuyệt đối không "hardcode" (fix cứng) các công thức tính toán (như % hoa hồng, tiền lương cơ bản) vào trong code Backend vì thực tế doanh nghiệp luôn có nhiều phòng ban và chính sách khác nhau. Bắt buộc phải lưu trữ các thông số này riêng biệt cho từng nhân sự bằng bảng User Meta (VD: `_mi_erp_base_salary`, `_mi_erp_commission_rate`). Từ đó backend sẽ gọi động các thông số này để đưa vào công thức tính tổng kết.
- **BUG-15 (Lỗi 500 trắng trang do cú pháp PHP):** Khi khai báo hoặc sửa phương thức trong các Class xử lý (như `class-hrm-ajax.php`), nếu thiếu sót dấu ngoặc nhọn đóng `}`, toàn bộ module sẽ bị sập trả về lỗi 500 (Internal Server Error) chứ không chỉ riêng tính năng đó.
- **BUG-16 (JavaScript Syntax Error gây hỏng chức năng UI):** Việc copy/paste code đôi khi để lại dư thừa ký tự đóng khối (`});`) ở cuối các file `.js`. Điều này gây ra `Uncaught SyntaxError`, làm sập toàn bộ luồng event listener phía dưới (chẳng hạn làm nút Submit AJAX chuyển sang tải lại trang thay vì gửi ngầm).
- **LESSON-04 (Biên dịch Tailwind CSS bằng Docker tự động):** Nếu môi trường server (host/container) bị lỗi `Segmentation fault` khi chạy Node.js hoặc không tiện cài đặt Node, hãy dùng luôn image Docker để biên dịch CSS độc lập, giữ sạch môi trường: `docker run --rm -v $(pwd):/app -w /app node:20 sh -c "npm init -y && npm i -D tailwindcss@3 && npx tailwindcss -i ./src/input.css -o ./assets/css/tailwind-admin.css --minify"`.
- **BUG-17 (Vỡ giao diện Bảng WP Admin do xung đột Tailwind):** Khi tạo các bảng chuẩn WP Admin, class `fixed` (như trong `wp-list-table fixed`) sẽ bị Tailwind CSS ghi đè thành thuộc tính `position: fixed;`. Điều này làm bảng thoát khỏi document flow, gây sập layout khối chứa và chèn đè lên các nút bấm xung quanh (Ví dụ trong Modal). Cách giải quyết: Tuyệt đối không dùng class `fixed` cho các thẻ `<table>` trong vùng có nạp Tailwind. Thay thế bằng CSS nội tuyến `style="table-layout: fixed; width: 100%;"` để giữ nguyên cấu trúc độ rộng cột mà không gây lỗi hiển thị.
- **LESSON-05 (Quản lý Nhân sự & Phân quyền HRM):** Hệ thống HRM của ERP sử dụng trực tiếp core User của WordPress nhưng cung cấp giao diện quản lý riêng (Popup Modal để Thêm/Sửa). Không sửa đổi trực tiếp hàm auth của WP mà quản lý qua `_mi_erp_status` (active/inactive). Nếu nhân sự nghỉ việc, set status thành inactive và gọi `WP_Session_Tokens::destroy_all()` để đăng xuất lập tức. Phải luôn đưa role `subscriber` vào danh sách truy vấn hiển thị ở bảng điều khiển HRM để đảm bảo các tài khoản nhân viên được cấp quyền hạn chế không bị ẩn khỏi danh sách quản lý.
- **LESSON-06 (Cơ cấu Phòng ban & Phân quyền Module động):** Tuyệt đối KHÔNG hardcode danh sách phòng ban trong mã nguồn, vì mỗi doanh nghiệp sẽ có cấu trúc riêng. Danh sách phòng ban phải được người dùng cấu hình động (lưu vào `wp_options`). Việc phân quyền truy cập các module ERP (POS, Kho, Kế toán...) KHÔNG phụ thuộc hoàn toàn vào phòng ban hay Role, mà phải dùng cơ chế checkbox tick chọn từng quyền độc lập cho mỗi user (lưu thành mảng meta `_mi_erp_permissions` và ánh xạ sang WordPress Capabilities).
- **LESSON-07 (Tài khoản không tính lương trong HRM):** Đối với các tài khoản Admin hoặc nhân viên đặc thù không nhận lương qua hệ thống ERP nhưng vẫn phải có mặt trong danh sách quản lý nhân sự, cần thiết lập User Meta `_mi_erp_exclude_payroll = 1` (Thông qua UI checkbox "Không tính lương" ở Modal Thêm/Sửa). Khi meta này được bật, các hàm API sẽ dùng lệnh `continue` để bỏ qua nhân viên đó khỏi danh sách đổ ra bảng KPI & Cài đặt lương, nhưng vẫn giữ lại họ ở bảng Quản lý Nhân sự để có thể thao tác phân quyền.
- **BUG-18 (Lỗi Corrupted Tailwind Binary):** Nếu khi biên dịch Tailwind qua Docker CLI gặp lỗi `Bus error (core dumped)` hoặc file thực thi `tailwindcss-linux-x64` có dung lượng quá nhỏ (khoảng 3MB), điều đó chứng tỏ file nhị phân đã bị hỏng. Cách giải quyết: Copy lại file thực thi chuẩn (khoảng ~40MB) từ theme sang và cấp quyền thực thi `chmod +x` trước khi build lại.
- **BUG-19 (Lỗi 404 Stylesheet do lùi thư mục quá sâu bằng dirname):** Trong `class-mi-erp-frontend.php`, việc gọi `plugins_url( 'assets/css/style.css', dirname( __FILE__, 2 ) )` làm WordPress hiểu sai đường dẫn gốc của plugin, sinh ra URL `/wp-content/assets/css/style.css` (bị mất thư mục plugin `viomi-erp-system`) dẫn tới lỗi 404 cho giao diện tra cứu bảo hành. Cách giải quyết: Sử dụng đường dẫn tương đối trực tiếp từ file hiện tại bằng `__FILE__`: `plugins_url( '../../assets/css/style.css', __FILE__ )`.
- **BUG-20 (Lỗ hổng thực thi tùy tiện trong Installer CSDL):** File `install-db.php` kích hoạt khởi tạo bảng CSDL ERP nhưng thiếu kiểm tra quyền quản trị, có thể bị kích hoạt ngoài ý muốn nếu bị require hoặc gọi qua ngữ cảnh không phù hợp. Cách giải quyết: Luôn bọc điều kiện kiểm tra `if ( ! current_user_can( 'manage_options' ) && ! ( defined( 'WP_CLI' ) && WP_CLI ) ) wp_die(...)`.
- **LESSON-08 (Không lưu trữ file nhị phân nặng & file backup trong Repo):** Các file nhị phân độc lập như `tailwindcss-linux-x64` (~40MB - 80MB) và `wp-cli.phar` (~7MB) cùng các file sao lưu CSDL `.sql`, `.tar.gz` tuyệt đối không được đưa vào Git Repo. Chúng làm phình kích thước repo, gây chậm tiến trình clone/pull và tiềm ẩn nguy cơ lộ dữ liệu sản phẩm/khách hàng. Bắt buộc phải đưa vào `.gitignore` và chỉ chạy build/quản lý thông qua Docker container.
- **BUG-21 (Xung đột Schema JSON-LD đa nguồn giữa Plugin ERP và Plugin SEO):** Plugin ERP có class `Mi_ERP_SEO_Schema` tự động render schema `Article` và `BreadcrumbList` độc lập vào `wp_head`. Khi trang web cài đặt plugin SEO chuyên nghiệp (Rank Math), website bị xuất hiện 2 khối JSON-LD cạnh tranh nhau, dẫn đến cảnh báo lỗi cấu trúc nghiêm trọng từ Google Search Console. Cách giải quyết: Thêm điều kiện kiểm tra ở đầu hàm `render_schema()`: nếu phát hiện `defined('RANK_MATH_VERSION') || function_exists('rank_math') || defined('WPSEO_VERSION')`, ERP phải lập tức `return` sớm để nhường toàn quyền sinh Schema cho plugin SEO.
- **BUG-22 (Trùng lặp khai báo Action Hook AJAX):** Trong constructor của các lớp AJAX (như `class-procurement-ajax.php`), việc copy hoặc khai báo lặp lại cùng một action `wp_ajax_mi_erp_add_supplier` hai lần liên tiếp khiến WordPress đăng ký callback 2 lần vào mảng toàn cục `$wp_filter`. Khi hook được kích hoạt, callback có thể bị chạy đúp hoặc tiêu tốn bộ nhớ vô ích. Cách giải quyết: Rà soát và loại bỏ các dòng `add_action` bị trùng lặp.
- **LESSON-09 (Quy chuẩn Audit Toàn Diện Mã Nguồn ERP):** Khi audit plugin ERP độc lập kết hợp WooCommerce, cần thực hiện quy trình 4 bước chuẩn:
  1. *Phân tích tĩnh cú pháp (Static Lint):* Chạy `find ... -name "*.php" -exec php -l {} \;` trong Docker container đảm bảo 100% không có lỗi PHP syntax trên toàn bộ 39 tệp mã nguồn.
  2. *Kiểm tra CSDL thực tế (Database Schema Verification):* Query trực tiếp vào MariaDB (`SHOW TABLES LIKE 'wp_mi_%'`) để đối chiếu đầy đủ 11/11 bảng CSDL thực tế.
  3. *Kiểm tra cơ chế bảo mật (Security Penetration Test):* Gửi POST request không Nonce tới `admin-ajax.php` để xác thực phản hồi `-1` (Permission denied).
  4. *Kiểm tra liên thông liên phân hệ (Cross-Module Workflows):* Đối soát logic tự động hóa (PO `received` ➔ sinh chi phí Finance + sinh serial SCM; Lệnh sản xuất `completed` ➔ trừ linh kiện `consumed` + nhập kho thành phẩm SCM).


## 5. Quyền hạn & Admin (Security & Menu)
- **Capability Check:** Mọi request AJAX thuộc admin panel ngoài việc check `nonce` (`mi_erp_admin_nonce`) bắt buộc phải kiểm tra quyền bằng `if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error( 'Permission denied' );`.
- **Admin Menu Order (Vị trí Submenu):** Tuyệt đối không dựa vào thứ tự đăng ký hook `admin_menu` hay tham số `$priority` để sắp xếp menu. Luôn truyền tham số thứ 7 (`$position` kiểu integer, ví dụ 1, 2, 99) vào hàm `add_submenu_page()` ở mỗi module để "đóng đinh" thứ tự hiển thị menu một cách chủ động theo nghiệp vụ ERP (Từ Bán hàng tới Kế toán, và Cài đặt nằm cuối cùng).

## 6. Order Meta & Tự động hóa (Automation)
- **Meta Keys (Order):** 
  - `_mi_erp_serials_assigned` (yes): Đánh dấu đơn hàng đã được xuất kho và gán mã Serial thành công.
  - `_mi_erp_sales_employee` (User ID): ID nhân viên chốt đơn/bán hàng tại POS. Dùng để đối soát KPI doanh thu.
  - `_mi_erp_employee_note` (string): Ghi chú nội bộ của nhân viên.
  - `_mi_erp_delivery_distance` (float): Khoảng cách giao hàng (km) lưu từ POS. Dùng để HRM tính toán chi phí vận chuyển thực tế công ty phải chịu.
  - `Số Serial` (Item Meta): Lưu danh sách Serial hiển thị cho khách hàng/admin trên từng sản phẩm.
- **Triết lý tính toán KPI (HRM):** Không dùng các hook rời rạc để cộng dồn KPI vào Database mỗi khi có đơn hàng. Thay vào đó, áp dụng cơ chế **Dynamic Sync (Đồng bộ động)** quét theo thời gian thực (ví dụ `Y-m`).
  - **Giá vốn (COGS):** Không lưu phân mảnh ở từng đơn, mà tự động bóc tách từ Meta Sản phẩm WooCommerce (`_wc_cog_cost` hoặc `_mi_cost`). SCM Nhập Kho có nhiệm vụ cập nhật trường Meta này mỗi khi có lô hàng mới.
  - **Chi phí vận chuyển & Lợi nhuận:** Bất kỳ khoản phụ thu phí ship nào từ màn hình POS đều được lưu dưới dạng `WC_Order_Item_Fee`, làm tăng trực tiếp Tổng doanh thu. 
    - **Dưới 20km**: Phí ship mặc định là chi phí công ty chịu. Tuy nhiên, tùy cơ chế (nếu nhân viên sửa phí ship thu của khách), phần chênh lệch sẽ được cộng/trừ vào lợi nhuận của nhân viên đó.
    - **Trên 20km**: Nếu nhân viên chủ động giảm phí ship cho khách (VD: Ship thực tế 200k nhưng chỉ thu 100k), khoản chênh lệch (100k) bắt buộc phải trừ thẳng vào lợi nhuận cuối cùng của nhân viên đó trong kỳ đánh giá KPI.
- **Order Automation:** Tính năng tự động bốc kho và gán Serial hiện đang bị **TẮT** (chuyển sang gán thủ công qua admin). Các module mới không được phép can thiệp tự động đổi trạng thái serial sang `sold` nếu không thông qua flow gán thủ công.
- **Cross-module Automation:** Khi hoàn tất thủ tục liên phòng ban (Ví dụ: Đơn đặt hàng PO chuyển sang `received`, hoặc Lệnh sản xuất chuyển sang `completed`), hệ thống PHẢI tự động kích hoạt logic phát sinh dữ liệu ở các module liên quan (Như sinh giao dịch trừ tiền ở Finance, tạo Serial tự động và cộng kho ở SCM) để đảm bảo tính toàn vẹn.
  - **Dynamic Warehouse Resolution:** Tuyệt đối không hardcode ID kho (ví dụ `warehouse_id = 1`) khi sinh Serial tự động từ PO hoặc Lệnh Sản Xuất, mà luôn truy vấn kho đang kích hoạt từ `wp_mi_warehouses` (`WHERE status = 'active' ORDER BY id ASC LIMIT 1`) với fallback ID 2 (Kho HĐ1) để chống lỗi mồ côi Serial (Orphaned Serials).

## 7. Nghiệp vụ nâng cao (POS & Shipping)
- **Tạo đơn POS (Manual Order):** Khi tạo đơn trực tiếp, bắt buộc dùng hàm chuẩn `wc_create_order()`. Phí vận chuyển được thêm qua object `WC_Order_Item_Fee`. Cần chuyển trạng thái đơn sang `processing` sau khi tạo để thống nhất luồng.
- **Vận chuyển Mi Logistics:** Tích hợp custom shipping method `mi_logistics`. Logic tính phí đang phụ thuộc cứng vào slug của Shipping Class (VD: `cong-kenh`, `85-inch`) và chuỗi tên Thành phố (`ngoại thành`, `huyện`) để tính phụ phí.

## 8. Tài liệu tham chiếu (References)
Để hiểu rõ bối cảnh, lịch sử phát triển và kiến trúc ban đầu của hệ thống, vui lòng tham khảo các tài liệu sau:
- [Hướng dẫn Vận Hành & Kiến Trúc Module (README.md)](./README.md): Hướng dẫn cài đặt, danh sách 8 modules và cấu trúc 11 bảng CSDL MariaDB.
- [Kiến trúc & Kế hoạch (Architecture & Development Plan)](./docs/erp-architecture.md): Bản thiết kế toàn diện 5 giai đoạn (Stages) hình thành nên bộ khung lõi của hệ thống.
- [Lộ trình Hybrid ERP (Hybrid Architecture Plan)](./docs/hybrid-erp-plan.md): Bản phác thảo giai đoạn 1 (Planning) tập trung vào các bài toán nền tảng như Menu, Form nhập kho và Hook sự kiện.
- [Hướng dẫn sử dụng (User Manual)](./docs/user-manual.md): Tài liệu hướng dẫn sử dụng các tính năng hiện có của ERP.
- [Cấu trúc Tương lai (Future Architecture)](./docs/future-architecture.md): Bản nháp mô hình MVC chuẩn phục vụ cho việc tái cấu trúc (Refactor) khi thương mại hóa dự án.
- [Master System Architecture Guide](../../../.gemini/antigravity-ide/knowledge/michinhhang_wordpress_architecture/artifacts/architecture.md): Toàn cảnh kiến trúc toàn hệ thống Michinhhang.
