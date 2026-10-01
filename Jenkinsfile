pipeline {
    agent any

    environment {
        DEPLOY_USER = "ubuntu"
        DEPLOY_DIR = "/home/ubuntu/michinhhang"
        TELEGRAM_TOKEN = "7834830282:AAGupEEZ4IYjfmO_FkNFFsmBVzd6F1JpxPg"
        TELEGRAM_CHAT_ID = "5094340711"
    }

    stages {
        stage('🚦 Phân luồng Môi trường') {
            steps {
                script {
                    if (env.BRANCH_NAME == 'main') {
                        env.TARGET_IP = "192.168.2.74"
                        env.ENV_NAME = "LIVE (Chính thức)"
                    } else if (env.BRANCH_NAME == 'staging') {
                        env.TARGET_IP = "192.168.2.80"
                        env.ENV_NAME = "STAGING (Thử nghiệm)"
                    } else {
                        error("Nhánh ${env.BRANCH_NAME} không được phép Deploy tự động!")
                    }
                }
                echo "🚀 Đang triển khai code từ nhánh [${env.BRANCH_NAME}] lên môi trường [${env.ENV_NAME}] tại IP: ${env.TARGET_IP}"
            }
        }

        stage('🛡 Sao lưu Database') {
            steps {
                sh """
                ssh -o StrictHostKeyChecking=no ${DEPLOY_USER}@${TARGET_IP} '
                    mkdir -p /home/ubuntu/backups &&
                    cd ${DEPLOY_DIR} &&
                    (docker compose exec -T db mysqldump -u wp_user -pwp_password wordpress > /home/ubuntu/backups/db_backup_\$(date +%Y%m%d_%H%M%S).sql || echo "Bỏ qua backup vì Database chưa khởi tạo")
                '
                """
            }
        }

        stage('🚢 Truyền Code siêu tốc') {
            steps {
                sh "ssh -o StrictHostKeyChecking=no ${DEPLOY_USER}@${TARGET_IP} 'sudo chown -R ${DEPLOY_USER}:www-data ${DEPLOY_DIR} && sudo chmod -R 775 ${DEPLOY_DIR}' || true"
                sh "rsync -avz -e 'ssh -o StrictHostKeyChecking=no' --exclude='.git' --exclude='wp-content/uploads/' ./ ${DEPLOY_USER}@${TARGET_IP}:${DEPLOY_DIR}/"
            }
        }

        stage('⚙️ Triển khai Hệ thống') {
            steps {
                sh """
                ssh -o StrictHostKeyChecking=no ${DEPLOY_USER}@${TARGET_IP} '
                    cd ${DEPLOY_DIR} &&
                    docker compose build &&
                    docker compose up -d --remove-orphans
                '
                """
            }
        }
    }

    post {
        success {
            sh """
            curl -s -X POST https://api.telegram.org/bot${TELEGRAM_TOKEN}/sendMessage \
            -d chat_id=${TELEGRAM_CHAT_ID} \
            -d text="✅ [SUCCESS] Triển khai bản cập nhật nhánh ${env.BRANCH_NAME} lên môi trường ${env.ENV_NAME} THÀNH CÔNG!"
            """
        }
        failure {
            sh """
            curl -s -X POST https://api.telegram.org/bot${TELEGRAM_TOKEN}/sendMessage \
            -d chat_id=${TELEGRAM_CHAT_ID} \
            -d text="❌ [FAILED] Quá trình triển khai nhánh ${env.BRANCH_NAME} gặp lỗi!"
            """
        }
    }
}
