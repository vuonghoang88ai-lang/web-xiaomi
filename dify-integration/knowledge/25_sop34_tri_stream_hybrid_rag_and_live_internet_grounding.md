# SOP 34: KIẾN TRÚC LAI 3 TẦNG TRI THỨC VÀ TIẾP ĐỊA INTERNET THỜI GIAN THỰC (TRI-STREAM HYBRID RAG & LIVE INTERNET GROUNDING)

## 1. BỐI CẢNH VÀ ĐỘNG LỰC CỦA KIẾN TRÚC LAI

### 1.1 Điểm Nghẽn Của RAG Tĩnh (Knowledge Cutoff)
- Khi AI Agent chỉ dựa vào kho tài liệu Vector nội bộ (Static RAG), AI bị "mù" trước các biến động thời gian thực của thế giới thực:
  * Thời tiết thay đổi bất thường (đợt rét đậm, mưa lũ, nồm ẩm, nắng nóng kỷ lục).
  * Đối thủ bất ngờ tung chương trình khuyến mãi/giảm giá trong ngày.
  * Các xu hướng thịnh hành mới trên mạng xã hội (Viral audio TikTok, trend chế meme, hashtag hot).
  * Sự kiện văn hóa/thể thao lớn (World Cup, Euro, lễ hội cuối năm, Tết Nguyên Đán).
- Phản xạ thông thường của chatbot cũ là từ chối cứng nhắc: *"Tôi không có công cụ thời tiết, tôi chỉ là AI marketing..."*. Điều này làm đứt gãy mạch tư duy và gây thất vọng cho người dùng.

### 1.2 Triết Lý Giám Đốc Marketing Thực Địa (Grounded CMO)
- Một Giám Đốc Marketing thực thụ không bao giờ ngồi trong phòng kín. Mọi quyết định thương mại sắc bén đều bắt nguồn từ việc **kết hợp dữ kiện thực địa ngoài xã hội với năng lực sản phẩm và giá vốn của doanh nghiệp**.
- Do đó, hệ thống BẮT BUỘC vận hành theo **Mô hình Lai 3 Tầng Tri Thức (Tri-Stream Hybrid Architecture)**.

---

## 2. NGUYÊN LÝ KIẾN TRÚC 3 TẦNG TRI THỨC (TRI-STREAM ARCHITECTURE)

```
                              [CÂU HỎI TỪ NGƯỜI DÙNG]
                                         │
                                         ▼
                     [Semantic Intent & Freshness Router]
                      (Bộ định tuyến độ tươi & ý định động)
                                   /     |     \
       ┌──────────────────────────┘      │      └──────────────────────────┐
       ▼                                 ▼                                 ▼
 [TẦNG 1: RAG NỘI BỘ]           [TẦNG 2: SERP TRINH SÁT]       [TẦNG 3: LIVE INTERNET]
  Dataset Shop & SOP             researchCompetitor Tool        liveMarketIntelligence
  • Giá vốn, UVP, Brand Voice    • Cào 10 đối thủ thực tế      • Thời tiết 7 ngày (Open-Meteo)
  • Lịch sử khách hàng           • Bóc tách giá bán, quà tặng   • Tin tức công nghệ, xu hướng
  • Bảo mật dữ liệu riêng        • Điểm mù thị trường           • TTL Caching 1h - 6h
       │                                 │                                 │
       └──────────────────────────┬──────┴─────────────────────────────────┘
                                  ▼
                [BỘ TỔNG HỢP LĂNG KÍNH THƯƠNG MẠI]
                 (Commercial Synthesizer Engine)
             Chuỗi: Fact ➔ Customer Insight ➔ Action Plan
                                  │
                                  ▼
           [100% KẾ HOẠCH HÀNH ĐỘNG & BÀI VIẾT THỰC TIỄN]
```

### Tầng 1: Kho Tri Thức Nội Bộ (Internal Private RAG)
- Lưu trữ toàn bộ dữ liệu nội bộ của dự án: Hồ sơ thương hiệu, USP, chính sách bảo hành, cấu trúc COGS (giá vốn), và các bộ quy chuẩn SOP của phòng marketing.
- Phục vụ truy xuất tức thì với độ trễ 0s, bảo mật tuyệt đối.

### Tầng 2: Trinh sát Chuyên Sâu Đối Thủ (SERP & Competitor Recon)
- Kích hoạt qua tool `researchCompetitor`: Cào quét trực tiếp các website đối thủ cạnh tranh trên SERP Google theo từ khóa.
- Trả về giá bán, quà tặng, cấu trúc bài viết và điểm yếu của đối thủ.

### Tầng 3: Tiếp Địa Internet Thời Gian Thực (Live Internet Grounding)
- Kích hoạt qua tool `liveMarketIntelligence`:
  1. **Weather Forecasting**: Tích hợp Open-Meteo API trả về nhiệt độ tối đa/tối thiểu, lượng mưa, độ ẩm trong 7 ngày tới cho các thành phố trọng điểm (Hà Nội, TP.HCM, Đà Nẵng).
  2. **Live Web & News**: Quét tin tức công nghệ mới, sự kiện lễ hội, xu hướng tiêu dùng qua SERP & DuckDuckGo.
  3. **Smart TTL Caching**: Lưu đệm bộ nhớ trong 1 giờ để phản hồi $0.1$ giây cho các câu hỏi cùng chủ đề.

---

## 3. NGUYÊN TẮC BẮT CẦU THƯƠNG MẠI (COMMERCIAL SYNTHESIZER PRINCIPLE)

Dữ liệu Internet chỉ là nguyên liệu thô (Fact). CMO AI **TUYỆT ĐỐI CẤM** trả lời dưới dạng tin tức bách khoa toàn thư vô thưởng vô phạt. Mọi dữ liệu phải đi qua chuỗi giá trị 3 bước:

$$\text{Dữ kiện thực tế (Fact)} \longrightarrow \text{Tâm lý & Hành vi khách hàng (Insight)} \longrightarrow \text{Chiến dịch bán hàng của Shop (Action Plan)}$$

* **Ví dụ mẫu:**
  * *Fact:* Hà Nội đón không khí lạnh tăng cường, nhiệt độ giảm xuống 16°C kèm mưa rải rác.
  * *Insight:* Người dân ngại ra đường, sinh hoạt gia đình tại nhà tăng vọt, quần áo lâu khô.
  * *Action Plan:* Đẩy mạnh chiến dịch *"Đông ấm tại nhà - Tivi Xiaomi 4K & Máy sấy quần áo 10kg"*, cam kết giao lắp trong 2 giờ và tặng kèm combo giá treo tường.

---

## 4. ĐƯỜNG RAY ƯU TIÊN SẢN PHẨM CUỐI (FAST-TRACK DELIVERABLE)

Khi người dùng ra lệnh hướng đến sản phẩm cuối (*"viết bài... để đăng", "viết bài hoàn chỉnh"*) kể cả khi có kèm yêu cầu trinh sát (*"hãy tham khảo đối thủ và viết bài..."*):
1. Hệ thống tự động kích hoạt `researchCompetitor`.
2. Gom toàn bộ tranh biện C-Suite vào **1 thẻ Badge Consensus ngắn gọn ($\le 4$ dòng)**.
3. **XUẤT BẢN NGAY 100% NỘI DUNG SẢN PHẨM WOOCOMMERCE ĐẦY ĐỦ** trong cùng một lượt, triệt tiêu việc người dùng phải nhắc 2 lần.

---

## 5. TỰ ĐỘNG THU HOẠCH TRI THỨC NGẦM (AUTONOMOUS CONTINUOUS KNOWLEDGE HARVESTER)

- Ngay khi một lượt sinh hoàn tất trong Gateway (`chat_background.py`):
  * Nếu nội dung câu trả lời đạt tiêu chuẩn tài sản: $\text{length} \ge 2.500$ ký tự hoặc là bản kế hoạch chiến lược.
  * Worker ngầm tự động trích xuất tiêu đề Markdown và nạp thẳng tài liệu vào Dify Dataset của dự án (`KB_{Project}`).
  * Kho tri thức Vector của dự án luôn được "bồi đắp" liên tục và tự động theo thời gian thực.

