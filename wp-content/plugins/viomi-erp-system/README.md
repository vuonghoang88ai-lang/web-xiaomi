# Viomi ERP System (`viomi-erp-system`)

Plugin Quản trị Doanh nghiệp nội bộ (ERP) độc lập dành cho hệ thống phân phối Xiaomi chính hãng kết hợp WooCommerce.

---

## 1. Tổng Quan 8 Phân Hệ Cốt Lõi

1. **SCM (Chuỗi Cung Ứng & Quản Lý Kho)**:
   - Quản lý đa kho hàng (`wp_mi_warehouses`), hỗ trợ tự động điều phối Serial vào kho active.
   - Quản lý mã Serial/IMEI (`wp_mi_serials`), theo dõi vòng đời xuất/nhập/chuyển kho.
   - Tương thích HPOS, xuất kho hàng loạt qua file `.txt` và quét barcode.
2. **CRM & Quản Lý Bảo Hành**:
   - Cổng tra cứu bảo hành điện tử theo Serial hoặc Số điện thoại người mua: `[mi_warranty_check]`.
   - Quản lý phiếu tiếp nhận bảo hành, đổi trả và sửa chữa RMA (`wp_mi_tickets`).
3. **Finance (Tài Chính & Sổ Quỹ)**:
   - Sổ quỹ thu/chi nội bộ (`wp_mi_transactions`), tự động ghi nhận doanh thu từ đơn hàng hoàn thành và chi phí mua hàng PO.
   - Báo cáo lưu chuyển tiền tệ và đối soát tài chính theo ngày/tháng/năm.
4. **Procurement (Mua Hàng & Nhà Cung Cấp)**:
   - Quản lý danh bạ Nhà cung cấp linh kiện/thiết bị (`wp_mi_suppliers`).
   - Lập đơn đặt hàng PO (`wp_mi_purchase_orders` & `wp_mi_po_items`), tự động sinh Serial nhập kho khi PO chuyển trạng thái `received`.
5. **HRM (Nhân Sự, Lương Thưởng & Phân Quyền)**:
   - Quản lý hồ sơ nhân sự, bảng lương và chỉ số KPI (`wp_mi_employee_metrics`).
   - Tính hoa hồng bán hàng POS động theo User Meta (`_mi_erp_commission_rate`), tự động đối soát trừ phí ship vượt ngưỡng > 20km.
   - Phân quyền module độc lập theo Capabilities thay vì fix cứng Role.
6. **Manufacturing (Sản Xuất & Lắp Ráp)**:
   - Quản lý định mức nguyên vật liệu (BOM - Bill of Materials).
   - Lệnh sản xuất lắp ráp thành phẩm (`wp_mi_production_orders`), tự động trừ tồn kho linh kiện và cộng kho thành phẩm khi hoàn tất.
7. **Orders (Quản Lý Đơn Hàng Nâng Cao & Bàn Giao)**:
   - Bảng điều phối đơn hàng nâng cao, theo dõi trạng thái đóng gói, in phiếu giao hàng và bàn giao đơn vị vận chuyển.
8. **POS (Bán Hàng Tại Quầy & Quét Barcode)**:
   - Điểm bán lẻ POS nhanh, quét mã vạch sản phẩm & Serial trực tiếp, tự động trừ tồn kho và ghi nhận hoa hồng nhân viên.
9. **Shipping & SEO Integration**:
   - Tự động tính toán chi phí vận chuyển, cảnh báo và đối soát khi bán kính giao hàng vượt quá 20km.
   - Cơ chế Schema Yield: Tự động tắt cấu trúc Schema JSON-LD nội bộ khi phát hiện plugin SEO chuyên nghiệp (Rank Math) hoạt động.

---

## 2. Tiêu Chuẩn Kỹ Thuật

- **Độc lập Logic & CSS**: Sở hữu quy trình build Tailwind riêng (`assets/css/tailwind-admin.css`), tuyệt đối không phụ thuộc vào Theme.
- **Tương thích HPOS**: Tra cứu đơn hàng qua `wc_get_orders()` và JOIN bảng `wp_wc_order_addresses` theo SĐT.
- **Bảo mật**: Mọi truy vấn DB sử dụng `$wpdb->prepare()`, kiểm tra Nonce và Capability `manage_options`.
- **Cơ chế nhường quyền Schema**: Tự động tắt sinh Schema JSON-LD khi phát hiện plugin SEO chuyên nghiệp (Rank Math).
