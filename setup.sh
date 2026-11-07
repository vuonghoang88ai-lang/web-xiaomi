#!/bin/bash
set -e

echo "Bắt đầu thiết lập môi trường Docker cho dự án Viomi..."

# Tự động nhận diện lệnh Docker Compose
if docker compose version >/dev/null 2>&1; then
    DOCKER_COMPOSE="docker compose"
elif command -v docker-compose >/dev/null 2>&1; then
    DOCKER_COMPOSE="docker-compose"
else
    echo "Lỗi: Không tìm thấy docker compose hoặc docker-compose trên hệ thống!" >&2
    exit 1
fi

echo "Sử dụng công cụ: $DOCKER_COMPOSE"

# Đảm bảo thư mục tồn tại để mount không bị lỗi quyền
mkdir -p ./wp-content/plugins
mkdir -p ./wp-content/themes
mkdir -p ./wp-content/uploads
mkdir -p ./backups

# Khởi chạy các container
$DOCKER_COMPOSE up -d

echo "Đang đợi các container khởi động..."
sleep 5

# Thiết lập quyền chown cho thư mục wp-content để WordPress không bị lỗi permission
$DOCKER_COMPOSE exec -T wordpress chown -R www-data:www-data /var/www/html/wp-content
$DOCKER_COMPOSE exec -T wordpress chmod -R 775 /var/www/html/wp-content/uploads || true

echo "Hoàn tất! Hệ thống đã sẵn sàng tại http://localhost:8080"
