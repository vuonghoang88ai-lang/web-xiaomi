# Viomi E-Commerce Project

Dự án website thương mại điện tử bán đồ gia dụng sử dụng WordPress, được thiết lập trên môi trường Docker.

## Hướng dẫn cài đặt và chạy hệ thống

1. Mở terminal tại thư mục gốc của dự án.
2. Cấp quyền thực thi cho file setup (nếu chưa có):
   \`\`\`bash
   chmod +x setup.sh
   \`\`\`
3. Chạy file setup:
   \`\`\`bash
   ./setup.sh
   \`\`\`

## Hướng dẫn truy cập web

- Sau khi chạy \`setup.sh\` thành công, mở trình duyệt và truy cập: [http://localhost:8080](http://localhost:8080)
- Tiến hành các bước cài đặt WordPress cơ bản (chọn ngôn ngữ, đặt tên site, tạo tài khoản admin).

## Cài đặt các Plugin bắt buộc

Sau khi cài đặt xong WordPress và đăng nhập vào Dashboard (trang quản trị), bạn cần cài đặt các plugin sau từ kho plugin của WordPress:

1. **WooCommerce**:
   - Truy cập **Plugins** -> **Add New**.
   - Tìm kiếm "WooCommerce".
   - Cài đặt và Kích hoạt.
   - Làm theo trình hướng dẫn cài đặt cơ bản của WooCommerce để thiết lập cửa hàng.

2. **Advanced Custom Fields (ACF)**:
   - Truy cập **Plugins** -> **Add New**.
   - Tìm kiếm "Advanced Custom Fields".
   - Cài đặt và Kích hoạt. Plugin này giúp tạo các trường dữ liệu tùy chỉnh (thông số kỹ thuật, bảo hành...) cho sản phẩm gia dụng.

3. **Redis Object Cache**:
   - Truy cập **Plugins** -> **Add New**.
   - Tìm kiếm "Redis Object Cache".
   - Cài đặt và Kích hoạt.
   - Truy cập **Settings** -> **Redis** và nhấn nút **Enable Object Cache**. 
   *(Hệ thống đã cấu hình sẵn biến môi trường kết nối tới Redis container trong \`docker-compose.yml\` nên bạn không cần sửa file wp-config.php)*.

## Môi trường Phát triển (Development)

- Thư mục \`./wp-content\` ở máy host đã được mount thẳng vào \`/var/www/html/wp-content\` trong container.
- Theme chính của dự án nằm ở: \`wp-content/themes/viomi-theme\`.
- Plugin ERP nội bộ nằm ở: \`wp-content/plugins/viomi-erp-system\`.
- Bạn có thể mở trực tiếp dự án trên IDE và viết code, mọi thay đổi sẽ được cập nhật ngay lập tức vào container mà không cần restart lại.
