# SOP 43: THU HOẠCH TRI THỨC VẬN HÀNH & TỰ ĐỘNG HIỆU CHỈNH COPILOT (CONTINUOUS KNOWLEDGE HARVESTING & DRIFT CORRECTION)

## 1. MỤC TIÊU & BỐI CẢNH VẬN HÀNH THỰC CHIẾN
- Trong quá trình triển khai thực tế trên hệ thống CMO AI Copilot (tháng 11/2026), việc giám sát và đọc lại lịch sử chat thực tế là điều kiện tiên quyết để phát hiện các **Sai Lệch Hành Vi (Behavioral Drifts)** của mô hình LLM.
- **Mục tiêu SOP 43:** Thiết lập quy trình chuẩn mực để liên tục thu hoạch tri thức ngầm từ các cuộc hội thoại, nhận diện các "bệnh lý" phổ biến của AI Agent và tự động đóng gói thành tài liệu tri thức RAG để tái đào tạo hệ thống.

---

## 2. BỐN "BỆNH LÝ" PHỔ BIẾN CỦA AI COPILOT & PHƯƠNG ÁN ĐIỀU TRỊ

| Bệnh lý phát hiện từ lịch sử chat | Biểu hiện thực tế | Thuật toán điều trị (SOP áp dụng) |
| :--- | :--- | :--- |
| **1. Hội chứng Cuồng Họp (Over-Meeting Syndrome)** | Câu hỏi tra cứu ngắn (*"mai thời tiết như nào"*) vẫn kích hoạt khối `:::boardroom-debate` dài 6.000 ký tự làm người dùng sốt ruột. | **SOP 41 (Dynamic Meeting Decision Matrix)**: Áp dụng FSM 3 Tier. Tier 1 (Direct Fast-Path) cấm họp 100%, trả lời thẳng trong 1-3 đoạn văn. |
| **2. Hội chứng Bịa Đặt Dữ Liệu (Factual Hallucination)** | Khi hỏi thời tiết hoặc giá bán, AI tự bịa số liệu AccuWeather 19-26°C hoặc tính nhẩm sai giá combo. | **SOP 42 (Gemini Grounding & Inline Citations)**: Cưỡng chế tiếp địa vệ tinh Open-Meteo và trích dẫn số `[1]`, `[2]` minh bạch kèm bảng nguồn ở chân trang. |
| **3. Hội chứng Gán Ghép Ngoài Ngành (Cross-Domain Leakage)** | Nhúng thông tin Tivi/Máy giặt Xiaomi vào cả các dự án Đọc truyện chữ (`truyen360.top`) hoặc Nha khoa. | **SOP 30 (Dynamic Project Catalog Synthesizer)**: Bóc tách danh mục động từ chính `brand_profile` của dự án đang chọn; cấm tuyệt đối hardcode sản phẩm trong mã nguồn. |
| **4. Hội chứng Cứng Nhắc Cố Định (Rigid Hardcoding)** | Fix cứng địa điểm Hà Nội hoặc quét mảng từ khóa tĩnh `if k in query` khiến người dùng hỏi địa danh khác bị sai lệch. | **SOP 30 & 42 (Dynamic Geospatial Geocoding)**: Tự động phân giải toạ độ mọi thành phố toàn cầu qua Open-Meteo Geocoding API. |

---

## 3. QUY TRÌNH 4 BƯỚC THU HOẠCH TRI THỨC NGẦM (CONTINUOUS HARVESTING PIPELINE)

```
[1. Log Tracing & Pattern Mining]
    └─> Quét lịch sử chat định kỳ từ PostgreSQL & Dify API (lọc các cuộc hội thoại mới nhất)
        │
        ▼
[2. Gap Analysis & Behavioral Diagnostics]
    └─> Đối chiếu câu hỏi của người dùng và câu trả lời của AI:
        - Có bị mở boardroom thừa thãi không?
        - Có bịa đặt số liệu không?
        - Có bị rò rỉ mã injection kỹ thuật không?
        │
        ▼
[3. Algorithmic Refactoring & Knowledge Synthesis]
    ├─> Thay thế toàn bộ logic quét từ khóa tĩnh bằng Finite State Machine & Geocoding động
    └─> Đóng gói bài học kinh nghiệm thành tài liệu SOP chuẩn mực (`knowledge/XX_sop...`)
        │
        ▼
[4. Dual-Stream RAG Indexing & Closed-Loop Verification]
    ├─> Index ngay lập tức vào Dify Dataset RAG `KB_Marketing_SOP_Standards`
    └─> Chạy kịch bản kiểm thử tự động xác nhận câu trả lời đã sạch sẽ và chính xác 100%
```

---

## 4. BẢNG TIÊU CHUẨN ĐỐI SOÁT CHẤT LƯỢNG HỘI THOẠI (AUDIT CHECKLIST)

Mỗi chu kỳ rà soát lịch sử chat bắt buộc kiểm tra 5 tiêu chí:
1. ✅ **Bong bóng chat người dùng (User Message Cleanliness):** Đã loại bỏ 100% các khối injection kỹ thuật (`[BỐI CẢNH...]`, `[INTENT_MODE...]`, `[DỮ LIỆU...]`) chưa?
2. ✅ **Độ trễ phản hồi (Response Latency):** Câu hỏi Tier 1 (tra cứu nhanh) có phản hồi trong ngưỡng $< 15$ giây không?
3. ✅ **Minh bạch trích dẫn (Inline Citations):** Các số liệu thời tiết và bảng giá có gắn đầy đủ footnote `[1]`, `[2]` và khối `📚 Nguồn Dữ Liệu Thực Tế` không?
4. ✅ **Tính khái quát đa ngành (Domain Agility):** Khi đổi dự án sang ngành nghề khác (Nha khoa, SaaS, Giáo dục, F&B), hệ thống có tự động thích ứng hoàn toàn mà không bị rò rỉ dữ liệu của dự án cũ không?
5. ✅ **Kỷ luật mã nguồn (SOP 23):** Mọi file chỉnh sửa (`pre_routing_grounder.py`, `chat.py`) có duy trì $\le 300$ dòng code không?

---

## 5. TÍCH HỢP VÀO HỆ THỐNG
- Module thực thi: [`agent-engine/routes/portal/pre_routing_grounder.py`](file:///home/ubuntu/michinhhang/dify-integration/agent-engine/routes/portal/pre_routing_grounder.py).
- Dataset RAG lưu trữ: `KB_Marketing_SOP_Standards` (ID: `002d6b9a-ea14-4b30-99bc-8339a6f81c26`).
- Quy chuẩn này có hiệu lực vĩnh viễn trên toàn bộ các phiên bản CMO AI Copilot tiếp theo.

