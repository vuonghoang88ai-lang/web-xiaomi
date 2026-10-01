pipeline {
    agent any

    environment {
        LIVE_IP = "192.168.2.74"
        LIVE_USER = "ubuntu"
        LIVE_DIR = "/home/ubuntu/michinhhang"
    }

    stages {
        stage('🚀 Bước 1: Kéo Code từ GitHub') {
            steps {
                echo "Jenkins đã nhận tín hiệu và kéo bản code mới nhất..."
            }
        }

        stage('🛡️ Bước 2: Sao lưu Database trên Live Server') {
            steps {
                echo "Đang tạo bản sao lưu CSDL trước khi cập nhật..."
                sh """
                ssh -o StrictHostKeyChecking=no ${LIVE_USER}@${LIVE_IP} '
                    mkdir -p /home/ubuntu/backups &&
                    cd ${LIVE_DIR} &&
                    docker compose exec -T db mysqldump -u wp_user -pwp_password wordpress > /home/ubuntu/backups/db_backup_\$(date +%Y%m%d_%H%M%S).sql
                '
                """
            }
        }

        stage('🚢 Bước 3: Truyền Code siêu tốc sang Live Server') {
            steps {
                echo "Mở khóa quyền ghi cho thư mục trên máy Live..."
                sh "ssh -o StrictHostKeyChecking=no ${LIVE_USER}@${LIVE_IP} 'sudo chown -R ${LIVE_USER}:www-data ${LIVE_DIR} && sudo chmod -R 775 ${LIVE_DIR}'"
                
                echo "Đang dùng rsync để đồng bộ các file thay đổi..."
                sh "rsync -avz -e 'ssh -o StrictHostKeyChecking=no' --exclude='.git' --exclude='wp-content/uploads/' ./ ${LIVE_USER}@${LIVE_IP}:${LIVE_DIR}/"
            }
        }

        stage('⚙️ Bước 4: Triển khai Hệ thống (Live)') {
            steps {
                echo "Ra lệnh Live server build và khởi chạy toàn bộ dịch vụ..."
                sh """
                ssh -o StrictHostKeyChecking=no ${LIVE_USER}@${LIVE_IP} '
                    cd ${LIVE_DIR} &&
                    docker compose build &&
                    docker compose up -d --remove-orphans
                '
                """
            }
        }
    }
}
