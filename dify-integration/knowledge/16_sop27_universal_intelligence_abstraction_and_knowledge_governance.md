# SOP 27: TIÊU CHUẨN TRỪU TƯỢNG HÓA TRÍ TUỆ CHUNG & QUẢN TRỊ TRI THỨC HỆ THỐNG (UNIVERSAL INTELLIGENCE ABSTRACTION & KNOWLEDGE GOVERNANCE)

> **Phiên bản**: 1.0 — Ban hành ngày 01/11/2026  
> **Phạm vi áp dụng**: Toàn bộ hệ sinh thái Omni Marketing SaaS, Multi-Agent Engine, Dify AI Copilot Gateway & Đội ngũ Kỹ sư / AI Agents.  
> **Tôn chỉ tối cao**: "Không bao giờ dùng dữ liệu của một dự án đơn lẻ để gắn cứng cho toàn bộ hệ thống; mọi bài học thực tế phải được trừu tượng hóa thành thuật toán và quy luật kinh tế tổng quát."

---

## 1. BỐI CẢNH & NGUYÊN TẮC PHÂN TẦNG TRI THỨC (THE DIKW MODEL)

Trong quá trình vận hành hệ thống AI đa người dùng (Multi-Tenant SaaS), lỗi nghiêm trọng nhất là **Ô Nhiễm Tri Thức (Knowledge Pollution)**: Khi giải quyết một vấn đề cụ thể của Khách hàng A (ví dụ: bán Tivi Xiaomi, tặng mã FPT Play, chặn báo Thanh Niên), hệ thống lại mang nguyên tên miền, mã model và số tiền đó lưu vào kho tri thức chung, khiến Khách hàng B (bán Quần áo, Nha khoa, Mỹ phẩm) bị sai lệch tư duy.

Để ngăn chặn triệt để, hệ thống áp dụng mô hình phân tầng **DIKW**:

```
┌────────────────────────────────────────────────────────────────────────┐
│ 1. TẦNG TRÍ TUỆ CHUNG HỆ THỐNG (Wisdom & Knowledge - Thư viện knowledge/)│
│    - Các định luật toán học, thuật toán phân loại ý định (Intent)      │
│    - Quy trình phản biện đối kháng (MAD Protocol), ma trận phân tầng   │
│    - ÁP DỤNG CHO MỌI NGÀNH HÀNG, MỌI DỰ ÁN TOÀN CẦU                    │
├────────────────────────────────────────────────────────────────────────┤
│ 2. TẦNG DỮ LIỆU CÁ THỂ DỰ ÁN (Data & Information - Database Projects) │
│    - Tên thương hiệu, website URL, số hotline, địa chỉ showroom        │
│    - Bảng giá nhập cụ thể (COGS), mã SKU, chương trình quà tặng riêng  │
│    - LƯU RIÊNG TẠI projects.brand_profile & RAG DATASET CỦA DỰ ÁN     │
└────────────────────────────────────────────────────────────────────────┘
```

---

## 2. BỘ 4 ĐIỀU KIỆN TIÊN QUYẾT ĐỂ CHUẨN HÓA TRÍ TUỆ CHUNG

Bất kể khi giải quyết công việc gì (Viết bài, Trinh sát đối thủ, Định giá, Cấu hình RAG, Tối ưu SEO, Quản trị code...), thông tin chỉ được phép đưa vào kho tri thức chung khi thỏa mãn **toàn bộ 4 điều kiện**:

### Điều Kiện 1: Tính Độc Lập Ngành Hàng (Cross-Domain Universality)
* **Quy tắc:** Tri thức phải có giá trị đúng đắn trên **mọi ngành hàng**.
* **Câu hỏi kiểm tra:** *"Nếu áp dụng quy tắc này cho một phòng khám nha khoa hoặc một thương hiệu thời trang, quy tắc có còn đúng không?"*
* **Ví dụ:**
  * ❌ *Vi phạm (Dữ liệu riêng):* "Khi bán Tivi Xiaomi 55 inch thì tặng mã FPT Play 45K".
  * ✅ *Đạt chuẩn (Trí tuệ chung):* "Khi bán sản phẩm có màn hình hoặc thiết bị giải trí, ưu tiên tặng dịch vụ nội dung số (Digital Content/Subscription) có chi phí biên $\approx 0$ nhưng giá trị cảm nhận cao để kích thích chốt đơn ngay".

### Điều Kiện 2: Tính Tham Số Hóa (Parametrization over Hardcoding)
* **Quy tắc:** Không bao giờ lưu con số tuyệt đối cố định, mọi số liệu phải được tham số hóa thành biến số, công thức hoặc tỷ lệ phần trăm ($\%$).
* **Ví dụ:**
  * ❌ *Vi phạm:* "Quà tặng giá không quá 100.000đ".
  * ✅ *Đạt chuẩn:* 
    $$\text{Gift Budget} \le \min\left(\text{Trần ngân sách khách chỉ định}, \text{Giá bán} \times \text{Tỷ lệ an toàn } 3\% - 5\%\right)$$
    $$\text{Điều kiện Veto}: \text{Net Margin} = \frac{\text{Giá Bán} - \text{COGS} - \text{Quà} - \text{Giao Vận}}{\text{Giá Bán}} \ge \text{Ngưỡng an toàn ngành}$$

### Điều Kiện 3: Nhận Diện Bản Chất qua Hành Vi/Cấu Trúc (Behavioral Heuristics over Identifiers)
* **Quy tắc:** Không quản lý hệ thống bằng danh sách tên miền hay tên thương hiệu tĩnh (Blacklist/Whitelist), mà nhận diện dựa trên cấu trúc dữ liệu và ý định (Schema & Intent Heuristics).
* **Ví dụ:**
  * ❌ *Vi phạm:* "Chặn thanhnien.vn, tuoitre.vn, cafef.vn khỏi danh sách đối thủ".
  * ✅ *Đạt chuẩn:* "Phát hiện trang web là báo chí đưa tin PR khi có thẻ `NewsArticle`, tên tác giả và từ khóa đưa tin; nhận diện là đối thủ bán lẻ khi có thẻ `Product`, giỏ hàng và giá bán".

### Điều Kiện 4: Cơ Chế Phản Biện Đối Kháng & Phủ Quyết (Dialectical Guardrails & Veto)
* **Quy tắc:** Mọi đề xuất chiến lược của AI phải luôn được đặt trong thế đối kháng giữa 2 lực lượng:
  * Bên Thúc Đẩy Tăng Trưởng (Growth/SEO/CRO: đòi hỏi traffic, click, khuyến mại lớn).
  * Bên Phòng Vệ Rủi Ro (Risk/CFO/Brand: kiểm soát biên lợi nhuận, dòng tiền, uy tín).
  * Luôn có **Quyền Phủ Quyết (Veto)** khi đề xuất vi phạm ranh giới an toàn tài chính.

---

## 3. QUY TRÌNH 4 BƯỚC CHUYỂN HÓA MỌI CASE THÀNH TRÍ TUỆ CHUNG (DISTILLATION PIPELINE)

```
[1. Observation: Giải quyết dứt điểm ca cụ thể của dự án]
                         │
                         ▼
[2. Deconstruction: Bóc tách nguyên lý gốc (Bản chất bài toán là gì?)]
                         │
                         ▼
[3. Parameterization: Thay thế tên riêng & số cứng bằng Công thức / Heuristic]
                         │
                         ▼
[4. SOP Codification: Ban hành văn bản chuẩn hóa vào knowledge/ theo SOP 23]
```

---

## 4. DANH MỤC THẨM ĐỊNH 5 GIÂY (5-SECOND VALIDATION CHECKLIST)

Trước khi lưu bất kỳ thông tin nào vào thư viện tri thức chung `knowledge/`, kỹ sư và AI Agents bắt buộc phải tự chấm điểm:

| # | Câu Hỏi Thẩm Định | Đạt (Yes) | Không Đạt (No) |
|---|---|:---:|:---:|
| 1 | Tài liệu này có chứa tên thương hiệu riêng, hotline, địa chỉ kho cụ thể nào không? | Phải Không (0) | Bị loại |
| 2 | Nếu khách hàng đổi sang ngành nghề hoàn toàn khác, tài liệu này có dùng được không? | Phải Có (1) | Bị loại |
| 3 | Các con số trong tài liệu có được biểu diễn dưới dạng công thức hoặc tỷ lệ $\%$ không? | Phải Có (1) | Bị loại |
| 4 | Dung lượng tài liệu có tuân thủ quy chuẩn SOP 23 ($\le 300$ dòng) không? | Phải Có (1) | Bị loại |

---

## 5. MA TRẬN PHÂN VÙNG LƯU TRỮ CHÍNH THỨC

1. **Thư mục `knowledge/` (Hệ thống SOPs dùng chung)**:
   - Chỉ chứa các tài liệu SOP quy chuẩn nghiệp vụ, thuật toán, kiến trúc hệ thống và công thức toán học.
   - Tuyệt đối không chứa tệp danh mục sản phẩm của khách hàng đơn lẻ.
2. **Cơ sở dữ liệu `marketing_saas.projects`**:
   - `target_website_url`, `business_description`, `brand_profile`: Lưu trữ thông tin cá thể hóa của từng dự án.
3. **Dify Knowledge Datasets (`KB_<Client>_<Project>`)**:
   - Chứa tài liệu tri thức riêng biệt được RAG hóa độc lập cho từng khách hàng, bảo mật và phân lập tuyệt đối giữa các tenant.

