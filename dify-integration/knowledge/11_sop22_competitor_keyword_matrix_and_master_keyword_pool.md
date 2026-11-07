# SOP 22: TỔNG LỰC BÓC TÁCH TỪ KHÓA ĐỐI THỦ (MASTER KEYWORD POOL), MA TRẬN SERP TRỌNG ĐIỂM & HỆ THỐNG PHÂN TRANG SỐ THÔNG MINH
> Chuẩn hóa quy trình thu thập, phân tầng và khai thác toàn diện toàn bộ kho từ khóa bóc tách từ 10 đối thủ Google SERP và Website đích (> 1.200 từ khóa), cơ chế chế độ xem kép (Dual View) và hệ thống phân trang số thứ tự thông minh.

---

## 1. Bối Cảnh & Vấn Đề Thực Chiến (The Problem)

Trong quá trình phân tích đối thủ SEO, có một mâu thuẫn lớn giữa **Số lượng từ khóa bóc tách được** và **Năng lực hiển thị / Quét live SERP**:
1. **Số lượng từ khóa thực tế của đối thủ rất lớn**: Mỗi đối thủ tầm trung hoặc lớn (như Điện Máy Xanh, CellphoneS, Mi Hà Nội, Mi Việt Nam...) sở hữu hàng trăm cụm từ khóa (danh mục chính, danh mục con, thuộc tính specs, tên model, ý định mua hàng). Khi quét sâu 10 đối thủ + web đích, tổng kho từ khóa đạt tới **1.200 - 1.500 từ khóa độc bản**.
2. **Nghẽn cổ chai khi Quét Live SERP (Live Scraping Bottleneck)**: Nếu đưa toàn bộ 1.200 từ khóa này vào quét Google SERP thời gian thực:
   - Sẽ bị Google phát hiện lưu lượng bất thường, kích hoạt CAPTCHA hoặc chặn IP.
   - Gây độ trễ tải trang khổng lồ (vài phút), làm tê liệt trải nghiệm người dùng trên Client Portal.
3. **Thiếu phân trang số thứ tự (Numbered Pagination)**: Khi số lượng từ khóa lớn mà giao diện chỉ có cuộn vô tận hoặc 2 nút "Trang trước / Trang sau", người dùng sẽ cảm thấy từ khóa bị ẩn, khó định vị trang và không thể tra cứu toàn diện kho từ khóa của từng đối thủ.

---

## 2. Thống Kê & Đối Soát Từ Khóa Thực Tế Trên Toàn Hệ Thống

Dữ liệu bóc tách độc bản (đã chuẩn hóa có dấu và loại bỏ trùng lặp) từ 10 website đối thủ thực tế trên Google SERP và Website đích:

| Thứ tự | Tên miền Đối thủ / Website | Số lượng từ khóa độc bản | Nhóm từ khóa On-page chủ lực |
| :---: | :--- | :---: | :--- |
| 1 | **dienmayxanh.com** | **301 từ** | Danh mục gia dụng, specs kích thước màn hình, model tivi, máy hút bụi, phụ kiện |
| 2 | **cellphones.com.vn** | **170 từ** | Điện thoại Xiaomi, tablet, smartwatch, robot hút bụi thông minh |
| 3 | **mihanoi.vn** | **161 từ** | Hệ sinh thái gia dụng thông minh Xiaomi, máy lọc không khí, máy lau sàn |
| 4 | **mivietnam.vn** | **158 từ** | Đồ gia dụng thông minh, camera an ninh, thiết bị nhà bếp Mijia |
| 5 | **mi.com** *(Hãng)* | **140 từ** | Danh mục chuẩn hãng, tên model quốc tế, thông số kỹ thuật |
| 6 | **fptshop.com.vn** | **132 từ** | Thiết bị thông minh, điện thoại, máy tính bảng Xiaomi |
| 7 | **mistore.com.vn** | **81 từ** | Phụ kiện, thiết bị smarthome, đồ dùng tiện ích gia đình |
| 8 | **viomiviet.vn** | **72 từ** | Robot hút bụi Viomi, máy lọc nước, đồ gia dụng cao cấp |
| 9 | **xiaomistorehanoi.com.vn** | **71 từ** | Bản xách tay/quốc tế, thiết bị gia đình thông minh |
| 10 | **gigadigital.vn** | **44 từ** | Thiết bị số, máy lau sàn, nồi chiên không dầu |
| 11 | **tongkhomi.com** *(Web đích)* | **23 từ** | Danh mục ngành hàng hiện có của dự án |
| — | **TỔNG KHO MASTER KEYWORD POOL** | **1.279 TỪ ĐỘC BẢN** | *(Toàn bộ không trùng lặp, phủ đều 6 Silo ngành hàng)* |

---

## 3. Kiến Trúc Phân Tầng & Đo Lường Độ Nóng Cạnh Tranh

Toàn bộ kho từ khóa được tự động gắn nhãn đa chiều theo 3 thuộc tính:

### A. Kiến Trúc 4 Tầng Chiến Lược (4-Tier Strategy)
1. **Tầng 1 - Ngành hàng cốt lõi (Head / Category)**: Các từ khóa rễ bao quát toàn ngành (VD: `tivi xiaomi`, `tủ lạnh xiaomi`, `robot hút bụi xiaomi`).
2. **Tầng 2 - Landing Pages ngách (Specs / Sub-categories)**: Nhắm vào thuộc tính kỹ thuật người dùng hay lọc (VD: `tivi xiaomi 65 inch`, `tivi 75 inch`, `tủ lạnh 4 cánh`, `máy giặt 10kg inverter`).
3. **Tầng 3 - Dòng Model & Công nghệ (Specs & Tech)**: Tên dòng sản phẩm cụ thể và công nghệ hiển thị (VD: `redmi a pro`, `qled mini led`, `mijia 606l`, `gen 2`).
4. **Tầng 4 - Ý định mua hàng & Chuyển đổi (High-Intent / Transactional)**: Cụm từ nhắm thẳng vào quyết định chi tiền (VD: `bảng giá tivi xiaomi mới nhất tại kho`, `mua tủ lạnh xiaomi trả góp 0%`, `địa chỉ tổng kho phân phối xiaomi chính hãng`).

### B. Chỉ Số Độ Nóng Cạnh Tranh (Competitor Heat Index)
Hệ thống theo dõi mảng `found_in` (danh sách các đối thủ cùng sở hữu từ khóa đó On-page):
- 🔥 **Cực nóng (`>= 4 đối thủ`)**: Đấu trường đỏ (Red Ocean). Bắt buộc phải đầu tư Pillar Page chuyên sâu và cấu trúc internal link vững chắc mới có thể cạnh tranh Top 3.
- ⚡ **Nóng vừa (`2 - 3 đối thủ`)**: Mức độ cạnh tranh trung bình.
- ✓ **Cơ hội ngách (`1 đối thủ`)**: Thị trường ngách vàng (Blue Ocean) - đối thủ đang chiếm lĩnh mà các bên khác bỏ ngỏ, cơ hội vàng để web đích vượt Top thần tốc.

---

## 4. Giải Pháp Kiến Trúc: Chế Độ Xem Kép (Dual View Switcher)

Để dung hòa giữa **Quét Live SERP nhanh** và **Khai thác toàn bộ 1.279 từ khóa**, Client Portal phân tách thành 2 chế độ xem:

```
┌────────────────────────────────────────────────────────────────────────┐
│                   DUAL VIEW SWITCHER (PORTAL TAB 3)                     │
├───────────────────────────────────┬────────────────────────────────────┤
│ 🎯 Ma Trận SERP Trọng Điểm         │ 📚 Kho Từ Khóa Tổng Lực Bóc Tách   │
│ - Quy mô: 60 từ khóa chiến lược   │ - Quy mô: 1.279 từ khóa độc bản    │
│ - Phân bổ: 10 từ/Silo x 6 Silo   │ - Nguồn: 10 Đối thủ SERP + Web đích│
│ - Quét Live SERP & Top 1-2-3      │ - Bộ lọc 4 chiều (Đối thủ, Silo...)│
│ - Phân trang số thứ tự [1]...[4]  │ - Phân trang số thứ tự [1]...[26]  │
└───────────────────────────────────┴────────────────────────────────────┘
```

---

## 5. Chuẩn Hóa Thành Phần Phân Trang Số Thứ Tự (Numbered Pagination Component)

Component `renderPaginationHtml` được tái sử dụng đồng nhất cho cả 2 chế độ:
- **Hiển thị số trang thông minh**:
  - Nếu tổng số trang `<= 7`: Hiển thị đầy đủ tất cả các số `[ 1 ] [ 2 ] [ 3 ] [ 4 ] [ 5 ] [ 6 ] [ 7 ]`.
  - Nếu tổng số trang `> 7`: Tự động co gọn dạng sliding window `[ 1 ] ... [ 8 ] [ 9 ] [ 10 ] [ 11 ] [ 12 ] ... [ 26 ]` với active button mang màu chủ đạo (`var(--primary)`).
- **Phím chuyển hướng toàn diện**: `[« Đầu]`, `[‹ Trước]`, `[Sau ›]`, `[Cuối »]` có trạng thái disabled khi ở trang biên.
- **Ô nhảy trang nhanh (Jump Input)**: `<input type="number" min="1" max="26">` kích hoạt khi bấm Enter hoặc rời chuột.
- **Menu chọn số lượng mỗi trang (Page Size Selector)**:
  - Ma trận SERP: 10, 15, 30, 60 từ / trang.
  - Kho Tổng Lực: 25, 50, 100, 200 từ / trang.
- **Vị trí hiển thị**: Cả ở đầu bảng (dưới bộ lọc) và cuối bảng để trải nghiệm lướt web không bị gián đoạn.

---

## 6. Dây Chuyền Backend & Cơ Sở Dữ Liệu

1. **Hàm bóc tách tổng hợp `generate_keyword_serp_matrix(...)` (`seed_analyzer.py`)**:
   - Nhận diện toàn diện: `competitors` (10 đối thủ từ DB), `target_analysis` (web đích), `seed_audit`, `business_description`.
   - Chuẩn hóa Silo: Tivi, Tủ Lạnh, Máy Giặt & Sấy, Điều Hòa, Gia Dụng Smarthome, Ý Định Mua Hàng.
   - Tuyển chọn 60 từ chiến lược bằng thuật toán Round-Robin cân bằng.
   - Trả về tuple/dict gồm cả `keyword_matrix` (60 từ) và `master_keyword_pool` (1.279 từ).
2. **Lưu trữ CSDL PostgreSQL `marketing_saas`**:
   - Lưu vào trường `projects.brand_profile["keyword_matrix"]` và `projects.brand_profile["master_keyword_pool"]`.
   - Tự động bảo toàn dữ liệu khi người dùng quét thêm từ khóa lẻ (`rescanSingleKeyword`).
3. **Cơ chế Cache-Busting**:
   - Cập nhật tham số phiên bản `portal.js?v=YYYYMMDD_HHMM` trong `index.html`.
   - Kết hợp middleware FastAPI HTTP headers `no-cache, no-store, must-revalidate`.
