#!/bin/bash

echo "Bắt đầu thiết lập môi trường Docker cho dự án Viomi..."

# Đảm bảo thư mục tồn tại để mount không bị lỗi quyền
mkdir -p ./wp-content/plugins
mkdir -p ./wp-content/themes
mkdir -p ./wp-content/uploads

# Chạy Docker Compose
docker-compose up -d

echo "Đang đợi các container khởi động..."
sleep 5

# Thiết lập quyền chown cho thư mục wp-content để WordPress không bị lỗi permission
# Chạy lệnh bên trong container wordpress để dùng user www-data
docker-compose exec -T wordpress chown -R www-data:www-data /var/www/html/wp-content

echo "Hoàn tất! Hệ thống đã sẵn sàng."
