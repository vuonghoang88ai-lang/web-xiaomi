# SOP 21: TÌNH BÁO ĐỐI THỦ CHUYÊN SÂU 6 TRỤ CỘT & KIẾN TRÚC TRINH SÁT SERP 0 ĐỒNG
> Chuẩn hóa quy trình phân tích chuyên sâu đồng thời 10 đối thủ Google SERP và Website đích, cơ chế xoay vòng API không phát sinh chi phí, lưu trữ siêu nhẹ 2-4 KB và tích hợp ma trận đối đầu trực diện trên Portal.

---

## 1. Bối Cảnh & Mục Tiêu Chiến Lược
Trước đây, hệ thống chỉ hỗ trợ phân tích một đối thủ mẫu (Seed Competitor). Tuy nhiên, trong thực chiến SEO:
- Một đối thủ mẫu không đại diện cho toàn bộ cục diện thị trường.
- Người làm SEO cần nhìn thấy **bức tranh toàn cảnh của cả 10 đối thủ đang đứng Top Google** cùng với **Website đích của chính mình (Target Website)** trên cùng một thước đo tiêu chuẩn.
- Cần giải pháp **chi phí 0 đồng** thay vì phụ thuộc các gói API đắt đỏ hàng ngàn USD (DataForSEO, Semrush, Ahrefs API).
- Cần **giải pháp lưu trữ tinh gọn**: không cào bulk toàn bộ website gây phình dữ liệu hay tràn disk VPS, mà chỉ bóc tách siêu dữ liệu tinh hoa (metadata).

---

## 2. Tiêu Chuẩn 6 Trụ Cột Phân Tích Thực Chiến (Đồng Nhất Cho Đối Thủ & Web Đích)

Mỗi website (dù là Website Đích hay 10 Đối thủ SERP) đều được quét qua 6 trụ cột cốt lõi:

```
┌────────────────────────────────────────────────────────────────────────┐
│     TIÊU CHUẨN 6 TRỤ CỘT ĐO LƯỜNG ĐỐI THỦ & WEBSITE ĐÍCH (SOP 21)       │
├────────────────────────────────────────────────────────────────────────┤
│ 1. Nền Tảng Công Nghệ & CMS       │ WordPress (WooCommerce), Magento..│
│ 2. Tốc Độ Phản Hồi Kỹ Thuật (TTFB) │ Đo trực tiếp thời gian phản hồi máy chủ│
│ 3. Độ Sâu & Độ Tươi Mới Nội Dung  │ Word count, Alt coverage, dateModified│
│ 4. Tuổi Đời Tên Miền & Thẩm Quyền │ WHOIS/RDAP, Moz DA, Brand Citations│
│ 5. Thực Thể Mạng Xã Hội (Entity)  │ sameAs Schema, 5 mạng XH chính thức│
│ 6. Rich Schema & Chuyển Đổi (CRO) │ Product, FAQPage, Organization, CTA│
└────────────────────────────────────────────────────────────────────────┘
```

### Chi Tiết Từng Trụ Cột:
1. **Trụ Cột 1 - Nền tảng CMS & Công nghệ:** 
   - Tự động nhận diện CMS qua HTML markers, headers và asset links: WordPress (WooCommerce), Magento, Shopify, Haravan, Next.js / Code tay.
   - Bóc tách plugin SEO đang vận hành (Yoast SEO, Rank Math...).
2. **Trụ Cột 2 - Tốc độ & Kỹ thuật (TTFB):**
   - Đo thời gian phản hồi máy chủ On-page (Time To First Byte). Tiêu chuẩn xanh: `< 400ms`.
3. **Trụ Cột 3 - Độ Sâu & Độ Tươi Mới (Content Freshness):**
   - Đếm số từ trung bình của bài viết chi tiết / mô tả sản phẩm.
   - Tính tỷ lệ ảnh có thẻ `alt` (`alt_coverage_pct`).
   - Bóc tách thẻ `dateModified` trong JSON-LD / HTML để đo chính xác `days_since_update`. Gắn cờ cảnh báo `is_stale = true` nếu bài viết không được cập nhật trong > 180 ngày (tạo cơ hội xuất bản bài viết dateModified 2026 vượt Top).
4. **Trụ Cột 4 - Tuổi Đời & Thẩm Quyền Tên Miền (Domain Authority):**
   - Bóc tách tuổi đời tên miền qua RDAP (cho gTLD như `.com`, `.net`) và Regex Copyright Footer (cho ccTLD như `.vn`).
   - Tích hợp đo Moz Domain Authority (DA) và số lượng nhắc tên thương hiệu (Brand Citations).
5. **Trụ Cột 5 - Thực Thể Mạng Xã Hội (Entity sameAs):**
   - Quét 5 kênh chính thức: Facebook, YouTube, TikTok, Zalo OA, Google Maps.
   - Kiểm tra liên kết `sameAs` trong Schema Organization để đánh giá mức độ tin cậy Knowledge Graph của Google.
6. **Trụ Cột 6 - Cấu Trúc Dữ Liệu Rich Schema & Tối Ưu Chuyển Đổi (CRO):**
   - Bóc tách mảng schema: `BreadcrumbList`, `Product`, `FAQPage`, `Organization`.
   - Nhận diện widget tăng tỷ lệ chuyển đổi: Zalo Chat, Hotline nổi, bảng giá khuyến mãi `<del>` / `<ins>`.

---

## 3. Kiến Trúc Chi Phí 0 Đồng (Zero-Cost Intelligence Architecture)

Thay vì chi trả API đắt đỏ, hệ thống áp dụng kiến trúc 4 lớp miễn phí:
1. **Xoay Vòng Key Serper.dev Tự Động (API Rotation Pool):**
   - Cho phép nhập danh sách nhiều API key dạng mảng hoặc chuỗi phân tách dấu phẩy.
   - Khi key hiện tại cạn hạn mức (HTTP 403 hoặc 429), hệ thống tự động phát hiện, tăng chỉ số `_SERPER_KEY_INDEX` và chuyển sang key kế tiếp ngay lập tức mà không làm đứt gãy luồng cào.
2. **Khai Thác Câu Hỏi Google People Also Ask (PAA) Miễn Phí:**
   - Khi Serper.dev trả về kết quả tìm kiếm, tự động thu thập mảng `peopleAlsoAsk` (câu hỏi + câu trả lời tóm tắt).
   - Nạp trực tiếp câu hỏi PAA vào giao diện để chuyên viên hoặc Agent W4 lập tức đưa vào dàn ý bài viết E-E-A-T.
3. **Đo Lường Tuổi Tên Miền Thông Minh:**
   - Tên miền quốc tế (`.com`, `.net`, `.org`...): Truy vấn RDAP miễn phí không giới hạn.
   - Tên miền Việt Nam (`.vn`): Tự động phân tích năm bản quyền footer (Copyright `2018 - 2026`) để tính toán tuổi đời chính xác mà không bị chặn port 43 WHOIS.
4. **Đo Lường Trích Dẫn Thương Hiệu Ngoài SERP:**
   - Dùng truy vấn dorking thông minh loại trừ tên miền gốc để đếm số lượng website khác đang nhắc đến thương hiệu đối thủ.

---

## 4. Giải Pháp Lưu Trữ Tinh Gọn (Lightweight Metadata Storage)

> **Nguyên tắc vàng:** Tuyệt đối không cào bulk toàn bộ website đối thủ hay lưu trữ HTML thô / file media nhị phân vào Database.

- **Mô hình 2 trang mẫu đại diện:** Mỗi đối thủ chỉ cào:
  1. *Trang đại diện 1:* Trang chủ hoặc Trang Danh mục (bóc tách Silo, CMS, TTFB, Schema, Social).
  2. *Trang đại diện 2:* Trang chi tiết sản phẩm / bài viết đứng top (bóc tách Word count, Alt, dateModified, giá niêm yết).
- **Kích thước lưu trữ:**
  - Siêu dữ liệu của mỗi website được gói gọn trong cấu trúc JSON tinh lọc từ **2 đến 4 KB**.
  - 10 đối thủ chỉ tiêu tốn tổng cộng **~25 - 40 KB** cho một dự án.
- **Vị trí lưu trữ Database:**
  - Dữ liệu 10 đối thủ: Lưu vào trường `project_competitors.scraped_structure["deep_audit"]` (JSONB).
  - Dữ liệu Web Đích: Lưu vào `projects.brand_profile["target_analysis"]` và `projects.brand_profile["head_to_head"]`.
  - **Không cần chạy migration DDL**: Tận dụng triệt để kiến trúc JSONB linh hoạt của PostgreSQL `marketing_saas`.

---

## 5. Dây Chuyền Thực Thi Đa Luồng (Concurrency Pipeline)

Để đảm bảo hiệu năng cao khi phân tích đồng thời 10 đối thủ:
- Triển khai `deep_audit_competitor_batch(competitor_urls, max_workers=5)` bằng `ThreadPoolExecutor`.
- Thời gian quét hoàn tất cho toàn bộ 10 đối thủ: **~8-12 giây**.
- Bản đồ kết quả lưu trữ song song cả theo `domain` và `url`, đảm bảo đối soát chính xác 100% khi cập nhật vào cơ sở dữ liệu.

---

## 6. Danh Mục API Gateway & Hàm Nghiệp Vụ

### Backend APIs (`server.py`):
| Endpoint | Phương thức | Chức năng |
| :--- | :--- | :--- |
| `/api/v1/portal/projects/{id}/analyze-target` | `POST` | Phân tích 6 trụ cột cho Web Đích & dựng Ma trận Đối đầu Head-to-Head |
| `/api/v1/portal/projects/{id}/competitors/deep-audit` | `POST` | Đột kích chuyên sâu 6 trụ cột theo yêu cầu cho 1 đối thủ cụ thể |
| `/api/v1/portal/projects/{id}/competitors/batch-deep-audit` | `POST` | Quét chuyên sâu đồng loạt đa luồng cho toàn bộ 10 đối thủ trong dự án |

### Database Services (`db_service.py`):
- `update_competitor_deep_audit(project_id, competitor_domain_or_url, deep_audit)`: Cập nhật kết quả phân tích chuyên sâu vào đối thủ tương ứng.
- `save_project_target_analysis(project_id, target_analysis, head_to_head)`: Lưu trữ kết quả phân tích Web đích và ma trận đối đầu vào `brand_profile`.

---

## 7. Giao Diện Người Dùng & Bài Học Kinh Nghiệm (Portal UI Lessons Learned)

1. **Khung Web Đích Độc Lập (`#targetWebsiteSection`):**
   - Đặt nổi bật phía trên lưới đối thủ trong tab *10 Đối Thủ SERP & Tình Báo*.
   - Cho phép người dùng theo dõi chỉ số On-page của chính mình ngay cạnh đối thủ.
2. **Thẻ Đối Thủ Tương Tác:**
   - Hiển thị các badge: CMS, DA, Tuổi domain, TTFB, Schema, Freshness.
   - Nút `[🔍 Xem Chi Tiết 6 Trụ Cột Đột Kích]` bật modal phân tích chuyên sâu.
3. **Cửa Sổ Modal Đột Kích 6 Trụ Cột (`#competitorDeepAuditModal`):**
   - **Bảng đối đầu trực diện song song (Head-to-Head Table):** So sánh từng chỉ số giữa Web của bạn và đối thủ (Ngang nhau, Chuẩn SEO, Vượt trội hay Thấp hơn).
   - Bóc tách Title, Meta Description, Heading H1, Danh mục Silo, Thực thể Social, Từ khóa vàng và Google PAA.
4. **Kinh Nghiệm Debug CSS / DOM Modal:**
   - `.modal-overlay` của Portal sử dụng class `.active` để chuyển đổi trạng thái `opacity: 1; pointer-events: auto;`. Khi mở modal, JavaScript bắt buộc phải gọi `modal.classList.add('active')` thay vì chỉ thay đổi inline `style.display`.
   - Luôn đảm bảo thẻ đóng `</div>` của các modal liền trước không bị sót, tránh tình trạng modal này bị lồng bên trong modal khác đang bị ẩn.
   - Truyền tham số `idx` từ mảng `state.competitors` vào sự kiện `onclick` giúp truy xuất đối tượng dữ liệu an toàn tuyệt đối, tránh lỗi escape ký tự đặc biệt trong URL.
