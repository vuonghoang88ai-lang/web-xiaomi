pipeline {
    agent any

    environment {
        // Chỉ giữ lại thông tin Telegram để gửi cảnh báo
        TELEGRAM_TOKEN = "7834830282:AAGupEEZ4IYjfmO_FkNFFsmBVzd6F1JpxPg"
        TELEGRAM_CHAT_ID = "5094340711"
    }

    stages {
        stage('🚦 Phân luồng Môi trường') {
            steps {
                script {
                    if (env.BRANCH_NAME == 'main') {
                        env.ANSIBLE_TARGET = "live"
                        env.ENV_NAME = "LIVE (Chính thức)"
                        env.SHOULD_DEPLOY = "true"
                    } else if (env.BRANCH_NAME == 'staging') {
                        env.ANSIBLE_TARGET = "staging"
                        env.ENV_NAME = "STAGING (Thử nghiệm)"
                        env.SHOULD_DEPLOY = "true"
                    } else {
                        env.ENV_NAME = "TESTING"
                        env.SHOULD_DEPLOY = "false"
                        echo "ℹ️ Nhánh ${env.BRANCH_NAME} là nhánh tính năng. Chỉ test, không Deploy."
                    }
                }
            }
        }

        stage('🛡 Chuỗi tự động hóa: Bảo mật & 🚀 Triển khai') {
            when { environment name: 'SHOULD_DEPLOY', value: 'true' }
            steps {
                sh """
                # 0. Tự động tải các thư viện Ansible (ví dụ: Proxmox API) từ file requirements
                ansible-galaxy install -r requirements.yml

                # 1. Củng cố bảo mật hạ tầng trước (Trụ cột 5 - Security)
                ansible-playbook -i inventory.ini 2_security.yml -e "target_env=${env.ANSIBLE_TARGET}"

                # 2. Gọi lệnh sao lưu DB an toàn trên máy gốc trước khi Deploy
                ssh -o StrictHostKeyChecking=no ubuntu@\$(if [ "${env.ANSIBLE_TARGET}" = "live" ]; then echo "192.168.2.74"; else echo "192.168.2.80"; fi) '
                    mkdir -p /home/ubuntu/backups &&
                    (docker compose -f /home/ubuntu/michinhhang/docker-compose.yml exec -T db mysqldump -u wp_user -pwp_password wordpress > /home/ubuntu/backups/db_backup_\$(date +%Y%m%d_%H%M%S).sql || echo "Bỏ qua backup")
                '

                # 3. Chạy Nhạc trưởng kiểm tra tình trạng hệ thống (Trụ cột 4 - Đang ẩn chờ tách DB)
                # ansible-playbook -i inventory.ini 3_orchestration.yml

                # 4. Triển khai Rolling Update Không Gián Đoạn (Trụ cột 2 - Deployment)
                ansible-playbook -i inventory.ini 4_deploy_rolling.yml -e "target_env=${env.ANSIBLE_TARGET}"
                """
            }
        }
    }

    post {
        success {
            sh """
            curl -s -X POST https://api.telegram.org/bot${TELEGRAM_TOKEN}/sendMessage \\
            -d chat_id=${TELEGRAM_CHAT_ID} \\
            -d text="✅ [SUCCESS] Triển khai bản cập nhật nhánh ${env.BRANCH_NAME} lên môi trường ${env.ENV_NAME} bằng (Full Ansible) THÀNH CÔNG!"
            """
        }
        failure {
            sh """
            curl -s -X POST https://api.telegram.org/bot${TELEGRAM_TOKEN}/sendMessage \\
            -d chat_id=${TELEGRAM_CHAT_ID} \\
            -d text="❌ [FAILED] Quá trình triển khai nhánh ${env.BRANCH_NAME} bằng Ansible gặp lỗi!"
            """
        }
    }
}
