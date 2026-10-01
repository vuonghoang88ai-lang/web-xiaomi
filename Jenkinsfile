pipeline {
    agent any

    environment {
        LIVE_IP = "192.168.2.74"
        LIVE_USER = "ubuntu"
        LIVE_DIR = "/home/ubuntu/michinhhang"
        
        // Khai báo thông tin Telegram tại đây
        TELEGRAM_TOKEN = "ĐIỀN_TOKEN_CỦA_BẠN_VÀO_ĐÂY"
        TELEGRAM_CHAT_ID = "ĐIỀN_CHAT_ID_CỦA_BẠN_VÀO_ĐÂY"
    }

    stages {
        stage('🚀 Bước 1: Kéo Code từ GitHub') {
            steps { echo "Kéo bản code mới nhất..." }
        }
        stage('🛡 Bước 2: Sao lưu Database') {
            steps {
                sh "ssh -o StrictHostKeyChecking=no ${LIVE_USER}@${LIVE_IP} 'mkdir -p /home/ubuntu/backups && cd ${LIVE_DIR} && docker compose exec -T db mysqldump -u wp_user -pwp_password wordpress > /home/ubuntu/backups/db_backup_\$(date +%Y%m%d_%H%M%S).sql'"
            }
        }
        stage('🚢 Bước 3: Truyền Code sang Live') {
            steps {
                sh "ssh -o StrictHostKeyChecking=no ${LIVE_USER}@${LIVE_IP} 'sudo chown -R ${LIVE_USER}:www-data ${LIVE_DIR} && sudo chmod -R 775 ${LIVE_DIR}'"
                sh "rsync -avz -e 'ssh -o StrictHostKeyChecking=no' --exclude='.git' --exclude='wp-content/uploads/' ./ ${LIVE_USER}@${LIVE_IP}:${LIVE_DIR}/"
            }
        }
        stage('⚙️ Bước 4: Triển khai (Live)') {
            steps {
                sh "ssh -o StrictHostKeyChecking=no ${LIVE_USER}@${LIVE_IP} 'cd ${LIVE_DIR} && docker compose build && docker compose up -d --remove-orphans'"
            }
        }
    }

    // KHỐI HẬU XỬ LÝ: TỰ ĐỘNG BÁO CÁO KẾT QUẢ
    post {
        success {
            sh """
            curl -s -X POST https://api.telegram.org/bot${TELEGRAM_TOKEN}/sendMessage \
            -d chat_id=${TELEGRAM_CHAT_ID} \
            -d text="✅ [SUCCESS] Triển khai bản cập nhật lên Live Server THÀNH CÔNG! Website đang hoạt động ổn định."
            """
        }
        failure {
            sh """
            curl -s -X POST https://api.telegram.org/bot${TELEGRAM_TOKEN}/sendMessage \
            -d chat_id=${TELEGRAM_CHAT_ID} \
            -d text="❌ [FAILED] CẢNH BÁO: Quá trình CI/CD thất bại! Vui lòng truy cập Jenkins để kiểm tra lỗi ngay."
            """
        }
    }
}
