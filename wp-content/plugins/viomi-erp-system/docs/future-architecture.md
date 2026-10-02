# Cấu trúc Kiến trúc Tương lai (Bản Thương mại hóa)

Tài liệu này lưu trữ mô hình cấu trúc plugin theo chuẩn MVC (Model-View-Controller) được đề xuất để áp dụng khi dự án tiến hành tái cấu trúc (Refactoring) phục vụ cho mục đích thương mại hóa (Commercial Release). 

Việc chuyển đổi sang cấu trúc này sẽ giúp hệ thống dễ bảo trì hơn, hỗ trợ Autoloader, tách biệt logic rõ ràng và dễ dàng mở rộng cho các lập trình viên bên thứ 3.

## Đề xuất Cấu trúc Thư mục

```text
viomi-erp-system/
├── viomi-erp-system.php        # File gốc: Khai báo plugin, nạp file core
├── uninstall.php               # Dọn dẹp Database khi người dùng xóa plugin
├── tailwind.config.js          # Cấu hình TailwindCSS
│
├── core/                       # Lõi hệ thống (Dùng chung cho tất cả module)
│   ├── class-db-installer.php  # Quản lý việc tạo/nâng cấp các bảng wp_ (Database Schema)
│   ├── class-security.php      # Helper xử lý Nonce, làm sạch dữ liệu, phân quyền
│   └── class-loader.php        # Autoloader tự động nạp các class khi cần
│
├── modules/                    # Các phân hệ ERP độc lập
│   │
│   ├── inventory/              # Phân hệ Kho (Được đổi tên từ 'scm' hiện tại)
│   │   ├── admin/              # Logic hiển thị trang Admin (Controller)
│   │   ├── ajax/               # Logic xử lý các request AJAX riêng của Kho
│   │   ├── models/             # Tương tác Database (Select/Insert bảng wp_mi_serials)
│   │   └── views/              # File giao diện HTML/PHP (View)
│   │
│   ├── warranty/               # Phân hệ Bảo hành (Được đổi tên từ 'crm' hiện tại)
│   │   ├── shortcodes/         # Chứa logic cho [mi_warranty_check]
│   │   ├── ajax/               # Logic xử lý mi_erp_check_warranty
│   │   └── views/              # Giao diện block tĩnh trả về cho khách
│   │
│   └── finance/                # Phân hệ Tài chính
│       └── ...                 # (Cấu trúc tương tự)
│
└── assets/                     # Tài nguyên tĩnh
    ├── css/                    
    │   ├── admin-style.css     
    │   └── frontend-style.css  # Được build ra từ Tailwind
    ├── js/                     
    │   ├── inventory.js        # Script riêng của Kho (Nhớ dùng __FILE__ khi enqueue)
    │   └── warranty.js         # Xử lý e.preventDefault() cho tra cứu bảo hành
    └── images/
```

## Lộ trình Refactor (Khuyến nghị)
1. **Giai đoạn 1 - Core:** Xây dựng thư mục `core/` trước, tích hợp Autoloader và chuyển các logic chung về Security / DB Install vào đây.
2. **Giai đoạn 2 - Tách Models:** Cấu trúc lại các module hiện hành, tách riêng các câu lệnh truy vấn `$wpdb` ra khỏi các file AJAX và Init, đưa chúng vào thư mục `models/` của từng module.
3. **Giai đoạn 3 - Di dời và Đổi tên:** Chuyển các module từ thư mục `includes/modules/` ra thư mục `modules/` ở ngoài cùng, đồng thời cấu hình lại Autoloader và các hooks.
4. **Giai đoạn 4 - Kiểm thử:** Thực hiện test lại toàn bộ luồng nghiệp vụ vì việc thay đổi cấu trúc sẽ tác động đến các đường dẫn (paths) và AJAX endpoint.
