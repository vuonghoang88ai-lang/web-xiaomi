# Hướng Dẫn Sử Dụng Plugin Mi ERP System

Chào mừng bạn đến với tài liệu hướng dẫn sử dụng hệ thống **Mi ERP System**. Plugin này được thiết kế theo kiến trúc Modular và Event-driven, giúp tự động hóa tối đa quy trình quản lý kho Serial, đơn hàng, vận chuyển và bảo hành dành cho cửa hàng WooCommerce.

---

## 1. Cài đặt và Kích hoạt (Stage 1: Database & Core)

1. Tải toàn bộ thư mục `viomi-erp-system` lên thư mục `wp-content/plugins/` của WordPress.
2. Truy cập **Bảng điều khiển (Dashboard) > Plugins**.
3. Tìm **Mi ERP System** và nhấn **Kích hoạt (Activate)**.
4. *Lưu ý: Ngay khi kích hoạt, plugin sẽ tự động:*
   - *Khởi tạo bảng cơ sở dữ liệu `wp_mi_serials` để sẵn sàng quản lý kho hàng.*
   - *Tự động tạo trang **Tra cứu bảo hành** và thêm vào Menu chính của website.*

---

## 2. Nhập Kho Hàng Loạt (Stage 2: Admin Dashboard)

Tính năng này giúp bạn nạp danh sách mã Serial vào kho cho các sản phẩm WooCommerce.

1. Trong menu bên trái của WordPress Admin, tìm menu **Mi ERP** -> chọn **Quản lý Tồn kho (SCM)**.
2. Tại màn hình nhập kho:
   - **Sản phẩm:** Gõ tên hoặc ID của sản phẩm (hệ thống sẽ tự động tìm kiếm qua AJAX, hỗ trợ tìm cả sản phẩm cha và biến thể).
   - **Mã Serial:** Nhập danh sách các mã Serial (Mỗi mã nằm trên 1 dòng).
3. Nhấn nút **Cập nhật kho**. 
4. Hệ thống sẽ báo cáo số lượng mã đã thêm thành công, và số lượng mã bị trùng lặp (nếu có). Trạng thái mặc định của các mã này trong hệ thống sẽ là `in_stock`.

---

## 3. Quản Lý Đơn Hàng & Gán Serial (Stage 3: Order Management)

Quy trình tự động gán Serial hiện tại đang được **TẠM TẮT** để đảm bảo quy trình kiểm soát kho chính xác theo thực tế (nhân viên kho quét mã thực tế trước khi giao).

1. Khi khách hàng đặt mua một sản phẩm, đơn hàng sẽ hiển thị trong WooCommerce và menu **Đơn hàng ERP**.
2. **Gán mã Serial thủ công:**
   - Truy cập vào chi tiết đơn hàng trong wp-admin.
   - Tại cột bên phải, bạn sẽ thấy Metabox **"Mã Serial (Mi ERP)"**.
   - Tìm kiếm mã Serial đang có sẵn trong kho (`in_stock`) và bấm gán cho đơn hàng.
   - Trạng thái mã Serial trong kho sẽ tự động chuyển thành `sold` (đã bán), ghi nhận ID đơn hàng và ngày bán.
3. **Theo dõi đơn hàng:** Bạn và khách hàng đều có thể xem Số Serial được giao ngay trong chi tiết đơn hàng (và trên email/hóa đơn) dưới mục thông tin sản phẩm.

4. Hệ thống có cơ chế kiểm tra (HPOS Ready) đảm bảo mỗi đơn hàng chỉ được gán Serial duy nhất 1 lần, dù chuyển trạng thái qua lại.

---

## 4. Bán Hàng Tại Quầy (POS)

Phân hệ POS giúp nhân viên bán hàng tạo đơn hàng nhanh chóng cho khách mua trực tiếp tại cửa hàng.

1. Truy cập menu **Mi ERP** -> chọn **Bán hàng (POS)**.
2. Tại màn hình bán hàng:
   - **Tìm kiếm sản phẩm:** Gõ tên sản phẩm và chọn sản phẩm khách hàng muốn mua.
   - **Thông tin khách hàng:** Nhập tên và số điện thoại của khách hàng.
   - **Tạo đơn:** Bấm tạo đơn hàng.
3. Đơn hàng sẽ được tạo thẳng vào WooCommerce với trạng thái `processing` (Đang xử lý) và lưu lại thông tin nhân viên (người tạo đơn) để phục vụ cho các thống kê nội bộ sau này.

---

## 5. Cấu Hình Vận Chuyển "Mi Logistics" (Stage 4: Advanced Shipping)

Mi ERP System cung cấp thêm một phương thức vận chuyển độc quyền giúp bạn dễ dàng tuỳ biến cước phí.

1. Truy cập **WooCommerce > Cài đặt (Settings) > Giao hàng (Shipping)**.
2. Chọn hoặc tạo mới một **Khu vực giao hàng (Shipping Zone)** mong muốn.
3. Nhấn **Thêm phương thức giao hàng**, và bạn sẽ thấy lựa chọn **Mi Logistics**.
4. Chọn **Mi Logistics**, sau đó bấm **Chỉnh sửa** để cấu hình:
   - **Tiêu đề:** Tên hiển thị cho khách hàng khi thanh toán (Ví dụ: "Giao hàng hỏa tốc Mi").
   - **Phí vận chuyển cơ bản:** Điền số tiền (Ví dụ: `30000` cho 30.000 VNĐ).
5. *Tính năng tự động (Routing Logic):* 
   - Hệ thống sẽ quét giỏ hàng, nếu có chứa sản phẩm **cồng kềnh** (tivi 85 inch,...), phí giao hàng sẽ tự động x2.
   - Nếu địa chỉ nhận hàng là **ngoại thành** hoặc **huyện**, hệ thống sẽ tự động cộng thêm phụ phí 50.000 VNĐ.

---

## 6. Cổng Tra Cứu Bảo Hành (Stage 5: Frontend Warranty Portal)

Hệ thống đã tự động tạo trang **Tra cứu bảo hành** và đưa vào Menu của bạn lúc kích hoạt plugin. Tuy nhiên, nếu bạn muốn tuỳ biến thêm, bạn có thể tạo trang mới và sử dụng shortcode:

1. Dán đoạn shortcode sau vào nội dung bất kỳ trang nào:
   ```text
   [mi_warranty_check]
   ```
2. Khi khách hàng truy cập trang này, họ sẽ thấy khung nhập tìm kiếm. 
   - Khách hàng có thể tìm kiếm bằng **Mã Serial** hoặc **Số điện thoại** (đã dùng để đặt hàng).
   - Hệ thống sẽ trả về tên sản phẩm, mã serial, ngày kích hoạt bảo hành, và ngày hết hạn cho tất cả các sản phẩm tìm thấy.
   - Thời gian bảo hành được tính dựa trên **số tháng bảo hành** cấu hình trong sản phẩm (thông qua custom field ACF `mi_warranty_info`). Nếu sản phẩm chưa có cấu hình, hệ thống lấy mặc định là 12 tháng.

---

## 7. Quản Lý Nhân Sự & Lương Thưởng (HRM)

Phân hệ HRM giúp quản trị viên theo dõi hiệu suất, tính lương và hoa hồng cho nhân viên một cách tự động.

1. Truy cập menu **Mi ERP** -> chọn **Nhân sự (HRM)** (nếu có menu riêng) hoặc thông qua Dashboard chung.
2. **Quản lý danh sách nhân sự:**
   - Tại tab "Quản lý Nhân sự", bạn có thể thêm mới hoặc chỉnh sửa thông tin của các tài khoản nhân viên.
   - Khi tạo/sửa tài khoản, bạn có thể phân quyền vai trò (Role), chọn phòng ban và thiết lập cơ chế tính lương (lương cơ bản, % hoa hồng).
   - **Tài khoản đặc thù (Admin):** Đối với các tài khoản không nhận lương qua hệ thống (như tài khoản Admin tối cao), bạn có thể tích chọn ô **"Tài khoản này không tính lương"**. Tài khoản này sẽ vẫn có trong danh sách quản lý để phân quyền nhưng sẽ được ẩn khỏi các bảng tính toán KPI và quỹ lương.
3. **Hiệu suất & Lương (KPIs):**
   - Hệ thống sẽ tự động quét các đơn hàng thành công trong tháng và tính toán tổng doanh thu, giá vốn, phí vận chuyển để đưa ra mức hoa hồng thực nhận cho từng nhân sự dựa trên cơ chế đã cài đặt.
   - Quản trị viên có thể nhập thêm các khoản "Khấu trừ/Tạm ứng" và lưu lại. Hệ thống sẽ tính ra mức "Thực nhận" cuối cùng.

---

**Cần hỗ trợ thêm?**
Nếu có bất kỳ vấn đề gì trong quá trình sử dụng, xin vui lòng kiểm tra lại log lỗi (debug.log) hoặc liên hệ đội ngũ phát triển. Chúc bạn sử dụng hệ thống hiệu quả!
