# Viomi ERP System - Module Logic Plan

Bản kế hoạch chi tiết và luồng logic cho từng phân hệ (module) của hệ thống ERP dựa trên `AGENTS.md`.

## 1. Phân hệ SCM - Quản lý Kho & Serial (Supply Chain Management)
**Mục tiêu:** Quản lý không gian lưu trữ, mã định danh duy nhất của từng sản phẩm (Serial) và lưu vết toàn bộ hoạt động xuất/nhập/chuyển kho.
**Database Tables:** `wp_mi_warehouses`, `wp_mi_serials`, `wp_mi_inventory_transactions`.

* **Logic Nhập/Xuất kho (Inventory Transactions):**
  - **Nhập kho mới:** Khi nhận hàng từ nhà cung cấp (hoặc nhập lẻ), tạo mới bản ghi trong `wp_mi_inventory_transactions` với loại `in`. Cùng lúc đó, sinh/lưu các mã serial tương ứng vào `wp_mi_serials` với `status = 'instock'` và liên kết `product_id`.
  - **Chuyển kho (Transfer):** Cập nhật kho chứa của các serial được chọn, đồng thời tạo record `transfer` trong bảng log giao dịch.
  - **Xuất kho:** Gán mã Serial thủ công qua admin. Cập nhật `status = 'sold'`, gán `order_id` và `sold_date = NOW()`. Không sử dụng hook tự động cho thao tác này (theo rule Order Automation đang bị tắt).
* **Luồng AJAX:**
  - Xử lý tại `class-scm-ajax.php`. 
  - Các endpoint: `mi_erp_add_serial`, `mi_erp_transfer_stock`, `mi_erp_assign_order_serial`. 
  - Cần check `current_user_can('manage_options')` và Nonce `mi_erp_admin_nonce`.

---

## 2. Phân hệ CRM - Quản lý Khách hàng & Bảo hành (RMA)
**Mục tiêu:** Tra cứu thông tin bảo hành từ phía khách hàng và quản lý các phiếu khiếu nại/sửa chữa (Ticket).
**Database Tables:** `wp_mi_tickets`.

* **Logic Tra cứu bảo hành (Frontend):**
  - **Shortcode:** `[mi_warranty_check]`
  - **AJAX endpoint:** `mi_erp_check_warranty` (No-priv và Priv).
  - **Tìm kiếm linh hoạt:** Truy vấn qua SĐT (`_billing_phone`) cần dùng `LIKE %s` tránh lỗi định dạng SĐT (BUG-03) hoặc quét trực tiếp cột `serial_number`.
  - **Tính hạn bảo hành:** Lấy thời điểm xuất kho `sold_date` + thời hạn bảo hành qua cấu hình ACF `get_field('mi_warranty_info', product_id)`.
* **Logic Quản lý Ticket (Backend):**
  - Khi có yêu cầu sửa chữa, tạo Ticket mới liên kết với `serial_number` và `order_id`. 
  - Các trạng thái: `open`, `processing`, `resolved`, `closed`.
  - Lưu trữ lịch sử cập nhật trạng thái của Ticket.

---

## 3. Phân hệ Finance - Sổ quỹ & Tài chính
**Mục tiêu:** Theo dõi luồng tiền thu/chi, đối soát với đơn hàng WooCommerce và chi phí nội bộ.
**Database Tables:** `wp_mi_transactions`.

* **Logic Ghi nhận Sổ quỹ:**
  - **Tự động thu:** Khi đơn WooCommerce chuyển trạng thái `processing` hoặc `completed`, tự động sinh 1 phiếu Thu (Revenue) liên kết với `order_id`.
  - **Phiếu Chi:** Tạo thủ công qua Admin dashboard (chi trả nhà cung cấp, chi phí vận hành, lương).
* **Báo cáo (Dashboard):**
  - Logic tính tổng thu chi cần query an toàn qua `$wpdb->prepare()`. Tránh query MySQL bên trong các vòng lặp lớn khi thống kê.

---

## 4. Phân hệ Procurement - Mua hàng & PO
**Mục tiêu:** Quản lý quy trình đặt hàng từ Nhà cung cấp (Suppliers), theo dõi linh kiện.
**Database Tables:** `wp_mi_suppliers`, `wp_mi_purchase_orders`, `wp_mi_po_items`.

* **Logic Nhà cung cấp (Suppliers):**
  - CRUD thông tin nhà cung cấp (Tên, MST, Số điện thoại, Email, Địa chỉ).
* **Logic Đơn đặt hàng (PO):**
  - **Tạo PO:** Nhập list các linh kiện cần mua (`product_id` hoặc hàng ngoài), số lượng, đơn giá. Lưu vào `wp_mi_po_items`.
  - **Trạng thái PO:** `draft`, `sent`, `partially_received`, `received`, `cancelled`.
  - Khi PO chuyển sang trạng thái `received`, kích hoạt hook tự động kết nối sang phân hệ SCM để sinh giao dịch **Nhập kho (`in`)** và tạo phiếu chi ở hệ thống **Finance**.

---

## 5. Phân hệ HRM - Nhân sự & KPI
**Mục tiêu:** Theo dõi hoạt động của nhân viên bán hàng/kỹ thuật, tính lương thưởng dựa trên doanh số và năng suất.
**Database Tables:** `wp_mi_employee_metrics`.

* **Logic Lưu vết hiệu suất:**
  - Lắng nghe event khi chốt đơn POS hoặc khi có order meta `_mi_erp_sales_employee`. Tính điểm KPI/Doanh số cho nhân viên đó.
  - Ghi nhận số lượng Ticket (CRM) giải quyết được để cộng điểm thưởng cho Kỹ thuật viên.
* **Logic Báo cáo:**
  - Lấy dữ liệu theo kỳ (tháng/quý) để tính toán lương, xuất báo cáo nhân sự.

---

## 6. Phân hệ Manufacturing - Lắp ráp & Sản xuất
**Mục tiêu:** Định mức nguyên vật liệu (BOM - Bill of Materials), lên lệnh sản xuất, tiêu hao linh kiện.
**Database Tables:** `wp_mi_production_orders`.

* **Logic Lệnh sản xuất (Production Orders):**
  - Chọn 1 sản phẩm cuối (Thành phẩm) và xác lập định mức vật tư.
  - Khi lệnh sản xuất hoàn tất: Hệ thống kích hoạt hook tự động kết nối với SCM để **xuất kho linh kiện** (gán trạng thái `consumed`) và **nhập kho thành phẩm** (tạo Serial mới cho thành phẩm).
  - Trạng thái sản xuất: `planned`, `in_progress`, `completed`.

---

## Bổ sung: Các quy chuẩn kỹ thuật bắt buộc
1. **Frontend HTML/TailwindCSS:** KHÔNG dùng CSS inline. Đảm bảo toàn bộ class Tailwind được dùng trong plugin có liệt kê file `.php` vào mảng `content` của `tailwind.config.js`. Tránh lỗi grid layout khi dùng `absolute` (BUG-04).
2. **Xử lý URL tĩnh:** Bắt buộc sử dụng `plugins_url( 'js/module.js', __FILE__ )` (Tránh BUG-01).
3. **Data Sanitization & Escaping:** PHP xử lý Ajax phải `sanitize_text_field` toàn bộ `$_POST`. Kết quả in ra file template phải dùng `esc_html()`.
4. **Bảo mật AJAX:** Luôn check `check_ajax_referer` và `current_user_can('manage_options')` đối với tác vụ Admin.
5. **Custom Orders & Shipping:** POS tạo đơn phải gọi `wc_create_order()`. Shipping Method dựa cứng vào slug (VD: `cong-kenh`) để tính phí (Mục 7 - `mi_logistics`).
