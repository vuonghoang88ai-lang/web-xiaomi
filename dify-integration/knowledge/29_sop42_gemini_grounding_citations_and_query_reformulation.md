# SOP 42: GEMINI GROUNDING, QUERY REFORMULATION & MINH BẠCH TRÍCH DẪN (INLINE CITATIONS)

## 1. MỤC TIÊU & BỐI CẢNH (TUÂN THỦ TRIỆT ĐỂ SOP 30 ZERO HARDCODING)
- **Vấn đề cốt lõi:** Các mô hình ngôn ngữ lớn (LLM) thường có xu hướng "bịa đặt" (hallucination) khi gặp các câu hỏi thời vụ, thời tiết hay thông số sản phẩm, hoặc tự động gán ghép các sản phẩm của dự án này sang dự án khác.
- **Nguyên tắc bất biến (SOP 30):** Tuyệt đối KHÔNG fix cứng danh sách từ khóa tĩnh, KHÔNG fix cứng tên sản phẩm/giá tiền của một ngành cụ thể, và KHÔNG fix cứng vị trí địa lý. Mọi logic bắt buộc phải vận hành bằng **Thuật toán Tương ứng Thích ứng Linh hoạt**.

---

## 2. QUY TRÌNH 4 BƯỚC THUẬT TOÁN THÍCH ỨNG (ALGORITHMIC GROUNDING PIPELINE)

```
[User Query Ngôn Ngữ Tự Nhiên]
        │
        ▼
[1. Dynamic Geospatial & Temporal Resolver]
    ├─> Phân giải địa danh động: Query NLP Regex ➔ Project Profile `target_location`
    ├─> Geocoding động: Open-Meteo API (Toạ độ mọi thành phố toàn cầu)
    └─> Khung thời gian: Regex mốc thời gian tương đối
        │
        ▼
[2. Dynamic Project Catalog Synthesizer]
    ├─> Truy vấn Database Hồ sơ Dự án đang kích hoạt (`brand_profile`, UVP, ngành hàng)
    └─> Thuật toán Overlap Jaccard: Lọc đúng ngành hàng thực tế của khách hàng
        │
        ▼
[3. Goal-Driven Semantic Intent FSM (SOP 41)]
    ├─> Tier 1: Direct Fast-Path (Tra cứu thông tin nhanh)
    ├─> Tier 2: Deliverable Execution (Xuất bản 100% nội dung ngay)
    └─> Tier 3: Strategic Advisory (Chỉ họp C-Suite khi cần tham vấn đa chiều)
        │
        ▼
[4. Transparent Output with Grounding Citations]
    ├─> Inline Footnotes `[1]`, `[2]` sau mỗi thông số xác thực
    └─> Khối danh mục nguồn minh bạch `📚 Nguồn Dữ Liệu Thực Tế` ở cuối câu trả lời
```

---

## 3. CÁC THUẬT TOÁN THỰC THI CHI TIẾT

### 3.1. Thuật toán Geocoding & Khí tượng Động
- Sử dụng hàm `resolve_dynamic_geocoding(location_name)` kết nối Open-Meteo Geocoding API:
  - Tự động lấy vĩ độ/kinh độ chính xác của bất kỳ địa danh nào (Hà Nội, Đà Lạt, Cần Thơ, Tokyo, Paris...).
  - Lưu cache bộ nhớ để tối ưu độ trễ xử lý $\le 5$ms.

### 3.2. Thuật toán Tổng hợp Danh mục Sản phẩm Động (Zero Hardcoding)
- Sử dụng hàm `synthesize_dynamic_catalog(project, query)`:
  - Nếu dự án là Nha Khoa (`nhakhoanucuoixinh.vn`): Trích xuất dịch vụ Niềng răng, Trồng răng Implant.
  - Nếu dự án là Nền tảng Truyện (`truyen360.top`): Trích xuất danh mục Truyện chữ, Tiểu thuyết.
  - Nếu dự án là Điện máy (`tongkhomi.com`): Trích xuất Tivi, Tủ lạnh, Máy giặt.
  - Tuyệt đối không có bất kỳ sản phẩm hay con số giá tiền fix cứng nào trong mã nguồn Python.

### 3.3. Thuật toán Phân loại Ý định theo Cú pháp Hành động (Semantic Intent FSM)
- Sử dụng hàm `classify_intent_fsm(query)`:
  - Nhận diện cặp (Action Verb + Output Entity) để kích hoạt `DELIVERABLE_MODE`.
  - Nhận diện lệnh `/strategy` hoặc mẫu phân tích đa tiêu chí đồng thời để kích hoạt `BOARDROOM_MODE`.
  - Mặc định các câu hỏi tra cứu thông tin chuyển về `DIRECT_FAST_PATH`.

---

## 4. QUY CHUẨN INLINE CITATIONS & KHỐI NGUỒN MINH BẠCH
- Mọi phát biểu chứa nhiệt độ, thời tiết từ trạm vệ tinh bắt buộc gắn `[1]` liền kề.
- Mọi thông số, phân khúc sản phẩm hoặc chính sách từ hồ sơ dự án bắt buộc gắn `[2]` liền kề.
- Cuối câu trả lời bắt buộc in khối:
```markdown
---
### 📚 Nguồn Dữ Liệu Thực Tế (Grounding Sources)
- `[1]` Trạm Khí tượng Vệ tinh Open-Meteo ({Địa điểm} - Dữ liệu thời gian thực)
- `[2]` Hồ sơ Kho Tri thức Dự án {Tên Dự Án} (Xác thực thực tế)
```

---

## 5. TIÊU CHUẨN MÃ NGUỒN (SOP 23 & SOP 30)
- Module thực thi: [`agent-engine/routes/portal/pre_routing_grounder.py`](file:///home/ubuntu/michinhhang/dify-integration/agent-engine/routes/portal/pre_routing_grounder.py) (248 dòng, $\le 300$ dòng).
- Toàn bộ tham số và bối cảnh được bóc tách động, không sử dụng danh sách từ khóa tĩnh hay chuỗi cưỡng chế thô bạo.
