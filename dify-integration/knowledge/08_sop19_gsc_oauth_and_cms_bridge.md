# SOP 19 — Google Search Console OAuth và CMS Bridge

## 1. Mục tiêu

Kết nối dữ liệu Google Search Console (GSC) với Marketing Engine theo mô hình đa tenant, bảo đảm Engine vẫn hoạt động nếu Yoast, Rank Math, Google Site Kit hoặc Omni Bridge bị tắt/gỡ sau khi kết nối hoàn tất.

## 2. Nguyên tắc bắt buộc

1. Plugin SEO/Site Kit chỉ là nguồn phát hiện property hoặc adapter dữ liệu khi được hỗ trợ chính thức.
2. Không đọc, sao chép hoặc trích xuất refresh token/access token/API secret nội bộ từ database của plugin SEO.
3. GSC Search Analytics là dữ liệu riêng tư và phải dùng OAuth 2.0. Scope mặc định là `https://www.googleapis.com/auth/webmasters.readonly`.
4. Marketing Engine phải sở hữu refresh token riêng (`connection_owner = marketing_engine`) trước khi coi kết nối là độc lập.
5. Property đã kết nối bởi Site Kit không đồng nghĩa Marketing Engine đã được cấp quyền. Site Kit chỉ giúp điền/gợi ý `property_uri`.
6. Không ghi token, authorization code, client secret hoặc encryption key vào log, prompt, báo cáo hoặc nội dung WordPress.

## 3. Hai trạng thái cần phân biệt

### 3.1. Đã phát hiện kết nối plugin

Thông tin có thể nhận:

- `source`: ví dụ `google_site_kit`.
- `property_uri`.
- `connection_detected: true`.

Trạng thái này chỉ có nghĩa website đã từng kết nối GSC qua plugin. Chưa được phép gọi Search Analytics bằng quyền của Marketing Engine.

### 3.2. Marketing Engine đã sở hữu kết nối

Kết nối chỉ được coi là độc lập khi database có:

```text
status = active
connection_owner = marketing_engine
provider = google_search_console
encrypted_refresh_token != empty
```

Khi đạt trạng thái này, Engine gọi trực tiếp Google Search Console API. Plugin SEO/Site Kit có thể bị gỡ mà không ảnh hưởng dữ liệu GSC của Engine.

## 4. Quy trình thủ công trong WordPress

1. Cài và kích hoạt `omni-bridge-connector.zip`.
2. Vào **WordPress Admin → Settings → Omni Bridge**.
3. Nhập:
   - Marketing Engine URL.
   - Tenant ID.
   - Project ID.
4. Bấm **Lưu thiết lập**.
5. Bấm **Kết nối Google Search Console**.
6. Đăng nhập Google account có quyền đọc property.
7. Xác nhận scope readonly.
8. Sau callback, Engine lưu refresh token đã mã hóa.

Plugin chỉ chuyển hướng OAuth và hiển thị trạng thái. Plugin không lưu Google token.

## 5. Quy trình tự động hóa

Có thể chạy:

```bash
python3 tools/automate_gsc_onboarding.py \
  --engine-url https://engine.example.com \
  --wordpress-url https://site.example.com \
  --tenant-id TENANT_ID \
  --project-id PROJECT_ID \
  --wp-cli-prefix 'docker compose exec -T wordpress wp --path=/var/www/html' \
  --wp-cli-workdir /path/to/wordpress-compose \
  --install-plugin
```

Script tự chạy migration, cài plugin, kích hoạt plugin, cấu hình Bridge và tạo URL OAuth. Bước Google consent luôn cần chủ tài khoản xác nhận.

## 6. API chính

```text
GET  /api/v1/projects/{project_id}/gsc/connect?tenant_id={tenant_id}
GET  /api/v1/gsc/oauth/callback
GET  /api/v1/projects/{project_id}/gsc/connections?tenant_id={tenant_id}
POST /api/v1/projects/{project_id}/gsc/connections/{connection_id}/property
POST /api/v1/projects/{project_id}/gsc/performance
```

`/gsc/performance` chỉ được dùng khi connection thuộc Marketing Engine ở trạng thái `active`. Không fallback âm thầm sang dữ liệu mẫu hoặc dữ liệu của tenant khác.

## 7. Quy tắc cho Worker 2 — Data Analyst

- Chỉ phân tích dữ liệu có `source = google_search_console` hoặc connection Engine-owned.
- Ghi rõ khoảng ngày, property, dimensions và thời điểm đồng bộ.
- Phân biệt `clicks`, `impressions`, `ctr`, `position` theo đúng dữ liệu API.
- Nếu chưa có quyền GSC, trả `gsc_authorization_required`; không tự tạo số liệu.
- Nếu dữ liệu đang trễ hoặc rỗng, trả `no_results`/`data_pending`, không suy đoán.
- Chuyển các nhóm query/page có CTR thấp, impression cao và position phù hợp thành cơ hội tối ưu cho W3/W4.

## 8. Lỗi thường gặp

- `redirect_uri_mismatch`: redirect URI trong Google Cloud khác `GSC_REDIRECT_URI`.
- `access_denied`: người dùng từ chối hoặc chưa nằm trong Test users.
- `insufficientPermissions`: Google account không có quyền đọc property.
- `pending_property`: account có nhiều property, cần gọi endpoint chọn property.
- `gsc_engine_authorization_required`: plugin đã phát hiện GSC nhưng Engine chưa sở hữu token riêng.

## 9. Tiêu chí hoàn tất

GSC onboarding chỉ được đánh dấu hoàn tất khi:

1. `gsc_connections.status = active`.
2. `connection_owner = marketing_engine`.
3. Engine lấy được access token mới từ refresh token.
4. Engine gọi được Search Analytics API.
5. Dữ liệu được lưu đúng `tenant_id`, `project_id`, `property_uri`.
6. Tắt/gỡ plugin SEO không làm hỏng truy vấn hiệu suất.
