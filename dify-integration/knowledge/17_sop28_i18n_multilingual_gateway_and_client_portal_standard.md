# SOP 28: TIÊU CHUẨN QUỐC TẾ HÓA ĐA NGÔN NGỮ (i18n) CHO AI COPILOT GATEWAY VÀ SAAS CLIENT PORTAL

> **Phiên bản**: 1.0 — Ban hành ngày 02/11/2026  
> **Phạm vi áp dụng**: Toàn bộ hệ thống SaaS Client Portal, AI Copilot Gateway, Dify Multi-Agent Engine và các nhà phát triển.  
> **Tôn chỉ thiết kế**: "Bản địa hóa 0ms không phụ thuộc thư viện nặng; kết hợp đồng bộ giữa nhãn giao diện tĩnh và chỉ thị ngôn ngữ cưỡng bức cho mô hình ngôn ngữ lớn (LLM)."

---

## 1. NGUYÊN TẮC THIẾT KẾ ĐA NGÔN NGỮ (i18n PRINCIPLES)

Trong các ứng dụng AI tương tác thời gian thực, việc chuyển ngữ không chỉ dừng lại ở dịch nhãn giao diện (UI Labels), mà cốt lõi là **sự đồng bộ ngữ cảnh ngôn ngữ của AI Agent**:
1. **Zero-Dependency & Light Footprint:** Không sử dụng các thư viện cồng kềnh (như `i18next` hay framework khổng lồ) gây phình to bundle. Engine i18n phải là Vanilla JS thuần $\le 150$ dòng, nạp từ điển JSON tĩnh độc lập.
2. **0ms Latency Switching:** Chuyển đổi ngôn ngữ tức thời tại client mà không cần reload trang (F5) hay ngắt kết nối websocket/SSE hiện có.
3. **Bi-directional Gateway Sync:** Ngôn ngữ người dùng lựa chọn trên giao diện phải được truyền tải xuống API Gateway để Gateway tự động tiêm chỉ thị ngôn ngữ (Language Prompt Directive) vào Dify/LLM.

---

## 2. KIẾN TRÚC 3 TẦNG ĐA NGÔN NGỮ (3-TIER i18n ARCHITECTURE)

```
┌────────────────────────────────────────────────────────────────────────┐
│ TẦNG 1: CLIENT UI ENGINE (portal_i18n.js & locales/*.json)             │
│  - Lưu ngôn ngữ tại localStorage (khóa: omni_portal_lang)             │
│  - Quét DOM tự động: [data-i18n], [data-i18n-placeholder], [title]     │
│  - Phát sự kiện portal:lang_changed để các view động tự cập nhật       │
├────────────────────────────────────────────────────────────────────────┤
│ TẦNG 2: API GATEWAY ROUTER (routes/portal/chat.py & models.py)        │
│  - Pydantic model nhận tham số: lang: Optional[str] = "vi"             │
│  - Tiêm Language Directive vào System Prompt gửi sang Dify             │
├────────────────────────────────────────────────────────────────────────┤
│ TẦNG 3: AI COPILOT & MULTI-AGENT BOARDROOM (Dify Engine)               │
│  - Tranh biện đối kháng C-Suite hoàn toàn bằng ngôn ngữ chỉ định       │
│  - Xuất bản Action Plan & Báo cáo chuẩn thuật ngữ bản địa              │
└────────────────────────────────────────────────────────────────────────┘
```

---

## 3. TIÊU CHUẨN ĐẶT TÊN VÀ CẤU TRÚC TỪ ĐIỂN LOCALES

Mỗi ngôn ngữ được đóng gói trong 1 tệp JSON độc lập tại thư mục `locales/{lang}.json`:
- Cấu trúc khóa phân tầng rõ ràng theo domain nghiệp vụ:
  - `brand.*`: Nhãn thương hiệu và phiên bản.
  - `nav.*`: Menu điều hướng, danh mục dự án.
  - `header.*`: Thanh trạng thái, bộ nhớ phiên, gói bản quyền.
  - `chat.*`: Trạng thái chờ, Thought box, phòng họp Boardroom, CVI metric.
  - `actions.*`: Nút hành động (Publish WordPress, Social Recycle, Copy).
  - `prompts.*`: Các câu lệnh mẫu và gợi ý nhanh.
  - `toasts.*`: Thông báo hệ thống thành công / cảnh báo / lỗi.
- Hỗ trợ tham số hóa động thông qua cú pháp `{placeholder}`:
  ```json
  "session_memory": "Bộ nhớ vĩnh cửu: {id}",
  "running_tool": "🛠️ Đang chạy: {tool}..."
  ```
  Hàm `t(key, params)` tự động regex thay thế an toàn các biến số mà không làm hỏng cấu trúc HTML.

---

## 4. QUY TẮC TIÊM CHỈ THỊ NGÔN NGỮ TẠI API GATEWAY

Để tránh tình trạng người dùng chọn giao diện Tiếng Anh nhưng AI Copilot lại trả lời bằng Tiếng Việt (hoặc ngược lại), API Gateway bắt buộc phải tiêm chỉ thị cưỡng bức vào đầu chuỗi prompt gửi Dify:

```python
# Chuẩn hóa quy tắc Gateway Language Injection:
is_en = (getattr(payload, "lang", "vi") == "en")
if is_en:
    lang_directive = (
        "[MANDATORY LANGUAGE: ENGLISH. All C-Suite Boardroom debates, "
        "Consensus Voting Index (CVI) metrics, and CMO Action Plans MUST be in professional English.]\n"
    )
else:
    lang_directive = "" # Mặc định tiếng bản địa hệ thống
```

---

## 5. BẢO TRÌ VÀ MỞ RỘNG NGÔN NGỮ MỚI

Khi bổ sung ngôn ngữ thứ ba (ví dụ: `ja`, `zh`, `fr`):
1. Tạo tệp `locales/{lang}.json` sao chép cấu trúc khóa từ `en.json`.
2. Khai báo mã ngôn ngữ vào mảng `SUPPORTED_LANGS` trong `portal_i18n.js`.
3. Bổ sung nút lựa chọn trên `lang-switcher-pill`.
4. Không cần chỉnh sửa bất kỳ logic lõi nào của Gateway hay Database.

