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

        stage('🚢 Bước 2: Truyền Code siêu tốc sang Live Server') {
            steps {
                echo "Đang dùng rsync để đồng bộ các file thay đổi sang máy Live..."
                sh "ssh -o StrictHostKeyChecking=no ${LIVE_USER}@${LIVE_IP} 'mkdir -p ${LIVE_DIR}'"
                sh "rsync -avz -e 'ssh -o StrictHostKeyChecking=no' --exclude='.git' ./ ${LIVE_USER}@${LIVE_IP}:${LIVE_DIR}/"
            }
        }

        stage('⚙️ Bước 3: Triển khai Hệ thống (Live)') {
            steps {
                echo "Ra lệnh Live server build lại AI Agent và khởi chạy toàn bộ dịch vụ..."
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
