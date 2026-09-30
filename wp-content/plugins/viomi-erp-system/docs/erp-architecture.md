# Mi ERP System - Architecture & Development Plan

Bản kế hoạch phát triển dựa trên kiến trúc Modular và Event-driven.

- [x] Stage 1: Database & Core Models
  - Tạo hàm `mi_erp_install()` kích hoạt khi active plugin để khởi tạo bảng `wp_mi_serials` (cột: `id`, `product_id`, `serial_number`, `status`, `order_id`, `sold_date`).

- [x] Stage 2: Admin Dashboard (View)
  - Đăng ký menu "Mi ERP" vào wp-admin.
  - Xây dựng form nhập kho (chọn sản phẩm AJAX + textarea nhập mã hàng loạt).

- [x] Stage 3: Order Automation (Event-driven)
  - Hook vào trạng thái đơn hàng (Processing/Completed).
  - Thuật toán Auto-pick Serial trong kho và gán vào Order Meta.

- [x] Stage 4: Advanced Shipping (Logistics)
  - Extend class `WC_Shipping_Method` để tạo "Mi Logistics".

- [x] Stage 5: Frontend Warranty Portal
  - Shortcode `[mi_warranty_check]` và AJAX endpoint xử lý truy vấn bảo hành.
