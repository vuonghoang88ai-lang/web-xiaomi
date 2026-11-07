# 🔧 Cấu hình ENGINE_API_KEY - Hướng dẫn Chi tiết

## 📋 Tình hình hiện tại

Khi bạn chạy phân tích đối thủ trong Dify chat, lỗi sau xuất hiện:
```
Hệ thống chưa cấu hình ENGINE_API_KEY. Lỗi từ bên server.
```

## 🎯 Nguyên nhân

Marketing Engine (container `michinhhang-marketing-agent-1`) yêu cầu **ENGINE_API_KEY** để:
- Xác thực các request từ Dify đến các tool như `researchCompetitor`, `analyzeProjectOnboarding`
- Bảo vệ API khỏi truy cập không được phép
- Đảm bảo tính bảo mật giữa các service

## ✅ Giải pháp đã áp dụng

### 1. File cấu hình đã được tạo

**Đường dẫn:** `/home/ubuntu/michinhhang/dify-integration/.env`

```bash
# =============================================================================
# MARKETING ENGINE API KEY
# =============================================================================
ENGINE_API_KEY=mk_engine_secret_2026_81bfe4c0f23947b08b5c8895bb16a58b
```

### 2. Docker Compose đã được cập nhật

**File:** `/home/ubuntu/michinhhang/docker-compose.yml`

```yaml
marketing-agent-engine:
  env_file:
    - /home/ubuntu/michinhhang/dify-integration/.env
  # ... other configs
```

### 3. Marketing Engine đã nhận ENGINE_API_KEY

Kiểm tra:
```bash
docker exec michinhhang-marketing-agent-1 printenv ENGINE_API_KEY
# Output: mk_engine_secret_2026_81bfe4c0f23947b08b5c8895bb16a58b
```

## 🚀 Cách sử dụng trong Dify

### Bước 1: Cấu hình Tool trong Dify Studio

1. Truy cập **Dify Studio** → **Tools** → **Custom**
2. Tìm tool **"Marketing Engine"**
3. Click **"Edit"** → Tab **"Authentication"**
4. Cấu hình:
   - **Type**: Bearer Token
   - **Token**: `mk_engine_secret_2026_81bfe4c0f23947b08b5c8895bb16a58b`

### Bước 2: Test API

```bash
# Health check
curl -s -H "x-engine-api-key: mk_engine_secret_2026_81bfe4c0f23947b08b5c8895bb16a58b" \
  http://localhost:8085/health

# Output:
# {
#   "status": "healthy",
#   "service": "Autonomous Marketing Multi-Agent Engine",
#   "port": 8085,
#   "connected_tiers": ["1.Dify", "2.MultiAgent", "3.PythonScraper", "4.9Router", "5.WordPress"]
# }
```

## 📦 Cấu trúc file sau khi tách riêng

Khi tách **dify-integration** ra thành project độc lập:

```
dify-integration/
├── .env                          # ← Cấu hình cho Marketing Engine
├── .env.example                  # ← Template (git tracked)
├── docker-compose.yml            # ← Chỉ cần load .env này
├── agent-engine/
│   ├── server.py
│   ├── Dockerfile
│   └── ...
├── tools/
└── dsl/
```

Khi đó chỉ cần:
```bash
cd dify-integration
cp .env.example .env
# Điền các giá trị thực tế vào .env
docker compose up -d
```

## 🔐 Bảo mật

### Cách tạo ENGINE_API_KEY mới (nếu cần)

```bash
openssl rand -hex 16
# Output: 81bfe4c0f23947b08b5c8895bb16a58b
```

Sau đó:
1. Cập nhật giá trị trong `/home/ubuntu/michinhhang/dify-integration/.env`
2. Khởi động lại container: `docker compose restart marketing-agent-engine`
3. Cập nhật token trong Dify Studio tool configuration

### File .env không nên commit vào Git

Thêm vào `.gitignore`:
```
dify-integration/.env
```

## 🐛 Troubleshooting

### Lỗi: ENGINE_API_KEY vẫn rỗng sau restart

**Nguyên nhân:** Docker compose không tìm thấy file .env

**Giải pháp:**
```bash
# Kiểm tra đường dẫn file
ls -la /home/ubuntu/michinhhang/dify-integration/.env

# Nếu không tìm thấy, tạo lại từ .env.example
cd /home/ubuntu/michinhhang/dify-integration
cp .env.example .env

# Sau đó restart
docker compose restart marketing-agent-engine
```

### Lỗi: API returns 500 "ENGINE_API_KEY mismatch"

**Nguyên nhân:** Token trong Dify không khớp với giá trị trong .env

**Giải pháp:**
1. Kiểm tra giá trị trong container:
   ```bash
   docker exec michinhhang-marketing-agent-1 printenv ENGINE_API_KEY
   ```
2. Cập nhật token trong Dify tool configuration với giá trị trên

## 📞 Liên hệ & Support

Nếu vẫn gặp lỗi sau khi cấu hình, kiểm tra:
1. Marketing Engine container có running không: `docker ps | grep marketing-agent`
2. Logs của container: `docker logs michinhhang-marketing-agent-1`
3. Token đã được load vào container: `docker exec michinhhang-marketing-agent-1 printenv ENGINE_API_KEY`
