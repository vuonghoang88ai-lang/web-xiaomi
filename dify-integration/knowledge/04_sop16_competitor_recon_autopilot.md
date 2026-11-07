# SOP 16: TÌNH BÁO ĐỐI THỦ SERP & GIẢI MÃ ĐỐI THỦ MẪU TOÀN DIỆN (REVERSE-ENGINEERING)
> Tiêu chuẩn vàng phân tích đối thủ cạnh tranh 6 trụ cột, bóc tách cấu trúc đa tầng, kiểm toán On-page/Kỹ thuật và tự động hóa chiến lược nội dung SEO tại TongKhoMi.com & Universal Marketing AI.

---

## 1. Mục Tiêu & Cơ Chế Hoạt Động Cốt Lõi
- **Mô phỏng tư duy Đội ngũ SEO Agency Thực chiến**: Không chỉ quét bề mặt một trang web đơn lẻ, mà tiến hành trinh sát đa tầng (Trang chủ, Danh mục Silo, Chi tiết sản phẩm/bài viết, Bản đồ chỉ mục Sitemap & Robots).
- **Phân loại kênh SERP thời gian thực**: Sử dụng Serper.dev API pool (hoặc DuckDuckGo fallback) để bóc tách rõ ràng 2 nhóm:
  - `paid_ads`: Đối thủ đang chi ngân sách đấu giá từ khóa Google Ads (nghiên cứu thông điệp quảng cáo, sitelinks, USP chuyển đổi).
  - `organic_seo`: Đối thủ đang chiếm lĩnh Top 10 tìm kiếm tự nhiên (nghiên cứu cấu trúc On-page, Silo, độ sâu bài viết và thẩm quyền thực thể).
- **Phát hiện Khoảng trống Cạnh tranh (Competitor Gaps)**: Nhận diện lỗ hổng On-page, thiếu sót Schema, nội dung nông cạn và thiếu hụt từ khóa mà đối thủ chưa bao phủ để xây dựng chiến lược "Content Vượt Top" (Skyscraper & 10x Content).
- **Ma trận Đối đầu Trực diện (Head-to-head Comparison Matrix)**: Đối chiếu song song website mục tiêu cần phát triển với đối thủ mẫu để tìm ra những việc cần làm ngay (Action Items).

---

## 2. Tiêu Chuẩn 6 Trụ Cột Phân Tích Đối Thủ Mẫu Thực Chiến

```
┌────────────────────────────────────────────────────────────────────────┐
│         HỆ THỐNG 6 TRỤ CỘT GIẢI MÃ ĐỐI THỦ MẪU (REVERSE-ENGINEERING)   │
├────────────────────────────────────────────────────────────────────────┤
│ 1. Deep Site Architecture & Silo   │ Quét sâu Trang chủ, Danh mục, PDP │
│ 2. Content Depth & E-E-A-T Signals │ Đếm Word count, FAQ, Media, Trust │
│ 3. Technical SEO & Core Web Vitals │ Tốc độ Mobile, Canonical, CMS     │
│ 4. Multi-Tier Keyword Intelligence │ Head - Secondary - Longtail Intent│
│ 5. Commercial Footprint & CRO/UX   │ Bóc tách giá KM/Niêm yết, CTA, UVP│
│ 6. Head-to-Head Gap Analysis       │ So sánh trực tiếp Website vs Mẫu  │
└────────────────────────────────────────────────────────────────────────┘
```

### Trụ Cột 1: Kiến Trúc Website Đa Tầng & Điều Hướng Silo (Deep Site Architecture)
1. **Quét đa cấp (Multi-level Crawling)**:
   - *Cấp 1 - Homepage*: Bóc tách Branding, Header, Footer, Top Navigation, Tổng quan sản phẩm chủ lực.
   - *Cấp 2 - Category Hub (Trang Danh mục)*: Chọn ngẫu nhiên hoặc chỉ định 1 URL danh mục tiêu biểu (ví dụ: `/tivi-xiaomi/`) để phân tích breadcrumbs, phân cấp H1-H3, đoạn mô tả SEO danh mục (Category SEO Description) và bộ lọc sản phẩm.
   - *Cấp 3 - Single Product / Article (Trang Chi tiết)*: Quét 1 trang chi tiết để kiểm tra cấu trúc tab thông số, đánh giá review, cấu trúc mua hàng.
2. **Kiểm toán Chỉ mục (Indexability Footprint)**:
   - Kiểm tra `robots.txt`: Bóc tách đường dẫn `Sitemap:`, các thư mục bị `Disallow`.
   - Kiểm tra `sitemap.xml`: Ước tính tổng số lượng URL đối thủ đang triển khai (phân loại số lượng bài viết vs sản phẩm).
   - Kiểm tra Canonical Tag: Phát hiện lỗi duplicate content hoặc URL trỏ sai canonical.

### Trụ Cột 2: Chiều Sâu Nội Dung & Tín Hiệu Thẩm Quyền E-E-A-T
1. **Độ sâu bài viết (Content Depth & Word Count)**:
   - Đo lường dung lượng từ trung bình của đối thủ ở các bài viết đứng top (ví dụ: 1200 từ, 1800 từ hay 2500 từ).
   - Đánh giá mật độ phân đoạn: Số lượng thẻ `H2`, `H3` hỗ trợ bài viết.
2. **Yếu tố Đa phương tiện & Định dạng Tương tác (Rich Media)**:
   - Tỷ lệ hình ảnh và tối ưu thẻ `alt`: Đối thủ có dùng ảnh sắc nét, ảnh thực tế, hay ảnh gắn logo bản quyền không.
   - Bảng so sánh (Comparison Tables): Kiểm tra có bảng thông số đối đầu giữa các model hay không.
   - Khối câu hỏi thường gặp (FAQ Block): Bóc tách các câu hỏi đối thủ giải đáp thắc mắc khách hàng.
3. **Tín hiệu Tin cậy & Trải nghiệm (E-E-A-T Footprint)**:
   - Box tác giả (Author Profile): Có thông tin chuyên viên kỹ thuật/biên tập viên chịu trách nhiệm nội dung không.
   - Thông tin pháp lý & Showroom: Bóc tách giấy phép kinh doanh, số hotline, địa chỉ kho/cửa hàng thực tế tại Hà Nội, TP.HCM, Đà Nẵng...

### Trụ Cột 3: Kỹ Thuật Chuyên Sâu, Nền Tảng CMS & Hiệu Năng (Technical & Core Web Vitals)
1. **Nhận diện Nền tảng CMS (CMS Tech Stack Detection)**:
   - Xác định nền tảng đối thủ đang sử dụng: WordPress (WooCommerce), Shopify, Haravan, Magento, hoặc Web Custom (Next.js, Nuxt, Laravel).
   - Phát hiện các plugin SEO đối thủ cài đặt: Rank Math, Yoast SEO, Schema Pro...
2. **Hiệu năng & Tốc độ Tải trang (Core Web Vitals)**:
   - Kiểm tra thời gian phản hồi máy chủ (TTFB - Time To First Byte).
   - Đánh giá điểm Mobile & Desktop qua Google PageSpeed Insights API (LCP, INP, CLS).
   - Xác định "tử huyệt" kỹ thuật: Nếu đối thủ có tốc độ trang chậm (> 3.5s) hoặc cấu trúc DOM cồng kềnh, đây là cơ hội chiến lược để vượt mặt.

### Trụ Cột 4: Hệ Thống Từ Khóa Vàng Đa Tầng (Multi-Tier Keyword Intelligence)
Không sử dụng n-gram cắt vụn từ ngữ tiếng Việt. Bóc tách và nhóm thành 3 tầng:
1. **Tầng 1 - Head Keywords (Từ khóa cốt lõi)**: Ngành hàng + Thương hiệu (`Tivi Xiaomi`, `Tủ Lạnh Xiaomi`, `Máy Giặt Xiaomi`...).
2. **Tầng 2 - Secondary Keywords (Từ khóa phụ & Dòng Model thực chiến)**: Bóc tách từ H2, H3 và tên sản phẩm thực tế kèm thông số inch, lít, kg, công nghệ (`Tivi Xiaomi 65 inch QLED`, `Tivi Redmi Max 85 inch`, `Tủ lạnh Mijia 606L`...).
3. **Tầng 3 - Long-tail Intent Keywords (Từ khóa đuôi dài theo Ý định Mua hàng)**:
   - *Transactional Intent*: Báo giá mới nhất tại kho, mua chính hãng trả góp 0%, địa chỉ bán uy tín.
   - *Commercial / Comparison Intent*: Đánh giá có tốt và bền không, so sánh model A với model B, tư vấn nên mua kích thước nào.
   - *Informational Intent*: Hướng dẫn cài đặt tiếng Việt, cách kết nối điện thoại, mã lỗi thường gặp.

### Trụ Cột 5: Dấu Chân Thương Mại & Tối Ưu Chuyển Đổi (Commercial Footprint & CRO/UX)
1. **Chiến lược Định giá (Pricing Strategy)**:
   - Bóc tách cấu trúc giá niêm yết (`<del>`) và giá khuyến mãi (`<ins>`, `.sale-price`).
   - Biên độ chiết khấu trung bình của đối thủ trên từng phân khúc sản phẩm.
2. **Kêu gọi hành động (Call To Action - CTA)**:
   - Nhận diện các widget tương tác: Nút gọi hotline nổi (Floating Call), Zalo Chat, Messenger Chat, Form đăng ký tư vấn nhanh.
   - Chính sách cam kết: Giao hàng hỏa tốc trong 2h, bảo hành chính hãng 24 tháng, lỗi 1 đổi 1 trong 30 ngày.

### Trụ Cột 6: Ma Trận Đối Đầu Trực Diện & Khoảng Trống Vượt Mặt (Head-to-Head Gap Analysis)
Thiết lập bảng đối chiếu trực quan song song giữa **Website Mục Tiêu (My Site)** và **Đối Thủ Mẫu (Seed Competitor)**:
- So sánh chỉ số On-page (Title, Meta, Heading, Schema).
- So sánh số lượng danh mục Silo bao phủ.
- So sánh tốc độ tải trang Mobile.
- Tự động xuất ra danh sách **5 Hành động Vượt mặt Cấp bách (Top 5 Priority Actions)** để giao việc cho các Agent W1–W6.

---

## 3. Quy Trình Vận Hành & Tích Hợp Dây Chuyền Multi-Agent

```
[Nhập URL Đối Thủ Mẫu & Ngành Hàng]
               │
               ▼
   [Seed Competitor Analyzer] ────► [Quét sâu 3 cấp: Home + Cat + PDP + Robots/Sitemap]
               │
               ├────────────────────────────────────────┐
               ▼                                        ▼
    [Bóc tách 6 Trụ Cột On-page]          [Bóc tách Bộ Từ Khóa Vàng 3 Tầng]
               │                                        │
               ▼                                        ▼
    [Báo cáo Lỗ hổng & Điểm mạnh]         [Truy kích Serper.dev Google SERP]
               │                                        │
               └───────────────────┬────────────────────┘
                                   │
                                   ▼
                   [Ma trận Đối đầu Head-to-Head]
                                   │
                                   ▼
        ┌──────────────────────────┴──────────────────────────┐
        ▼                                                     ▼
 [W1: Lập Hồ sơ SERP & UVP]                         [W2: Chống Cannibalization]
        │                                                     │
        ▼                                                     ▼
 [W3: Thiết kế Dàn ý Silo]                          [Lịch Biên Tập 30 Ngày Autopilot]
        │
        ▼
 [W4: Chấp bút SOP 12 - Vượt trội 100% On-page đối thủ]
```

---

## 4. Quy Chuẩn Vận Hành Trong Môi Trường Container Docker
- **Hạ tầng thực thi**: Chạy hoàn toàn trong container `michinhhang-marketing-agent-1` (FastAPI cổng `:8085`).
- **Lưu trữ CSDL bền vững**: Kết quả phân tích đối thủ và ma trận đối đầu được lưu vào bảng `project_competitors` và `projects.brand_profile` trong PostgreSQL (`marketing_saas`).
- **Đồng bộ Dify RAG**: Bản báo cáo phân tích đối thủ mẫu tự động được nạp vào Dify Knowledge Base Dataset (`KB_{client}_{project}`) qua [rag_service.py](file:///home/ubuntu/michinhhang/dify-integration/agent-engine/rag_service.py).
- **Cơ chế chịu lỗi (Graceful Fallback)**:
  - Nếu đối thủ chặn bot Cloudflare/WAF: Tự động thử lại với Desktop User-Agent xoay vòng và bóc tách dữ liệu từ bản lưu cache Google / DuckDuckGo.
  - Xoay vòng API Serper.dev tự động (BYOK Multi-key Pool): Tự động chuyển key mới khi gặp mã lỗi 403 / 429.

---

## 5. Mở Rộng: Phân Tích Chuyên Sâu 10 Đối Thủ & Web Đích (SOP 21)
Xem chi tiết toàn bộ kiến trúc mở rộng tại [10_sop21_deep_competitor_intelligence_and_zero_cost_recon.md](file:///home/ubuntu/michinhhang/dify-integration/knowledge/10_sop21_deep_competitor_intelligence_and_zero_cost_recon.md):
- Áp dụng công thức 6 trụ cột cho cả **10 đối thủ trên SERP** và **Website đích**.
- Quét theo lô đa luồng song song (`ThreadPoolExecutor`, 8-12s).
- Lưu trữ siêu nhẹ (2-4 KB JSON / site) không phình dữ liệu.
- Modal đột kích 6 trụ cột & Bảng đối đầu trực diện song song trên Portal UI.


