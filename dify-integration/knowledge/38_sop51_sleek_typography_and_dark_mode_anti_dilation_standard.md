# SOP 51: CHUẨN MỰC THẨM MỸ TYPOGRAPHY THANH MẢNH & TỐI GIẢN (GEMINI / CHATGPT SLEEK TYPOGRAPHY & DARK-MODE ANTI-DILATION STANDARD)

> **Mã quy chuẩn:** SOP 51 — SLEEK TYPOGRAPHY & DARK-MODE ANTI-DILATION STANDARD  
> **Áp dụng:** Giao diện Portal Chat, Màn hình Canvas Song Hành (Dual-Pane), Toàn bộ Font chữ & CSS  
> **Mục tiêu:** Thiết lập quy chuẩn thẩm mỹ tối giản, sắc nét, thanh mảnh tương tự trải nghiệm cao cấp của ChatGPT, Gemini và Linear; triệt tiêu hoàn toàn hiện tượng dãn nét chữ (Font Dilation) trên nền tối.

---

## 1. NGUYÊN LÝ QUANG HỌC NỀN TỐI & HIỆN TƯỢNG DÃN CHỮ (DARK-MODE FONT DILATION)

### 1.1. Hiện tượng Dãn Nét (Font Dilation / Optical Bleeding)
- Khi văn bản sáng màu (trắng/xám nhạt `#f1f5f9`) hiển thị trên nền tối (`#090d16` / `#0f172a`), mắt người tiếp nhận luồng sáng trực tiếp từ ký tự, khiến võng mạc cảm nhận nét chữ **dày hơn 15% - 20%** so với văn bản tối trên nền trắng.
- Nếu giao diện sử dụng `font-weight: 400` hoặc mặc định của trình duyệt mà không tinh chỉnh, đoạn văn bản sẽ tạo cảm giác nặng nề, thô ráp và nhanh gây mỏi mắt khi đọc các bài viết dài.

### 1.2. Giải pháp Kỹ thuật Chuẩn mực
1. **Nạp Trọng Số Mảnh Tự Nhiên (Native Weight 300 & 350):**  
   - Bắt buộc nạp Google Font `Inter` với đầy đủ trọng số mảnh:  
     `<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">`
   - Cấm để trình duyệt tự động làm giả nét (faux font weight).
2. **Khử Răng Cưa Cấp Phần Cứng (Subpixel & Hardware Antialiasing):**
   ```css
   -webkit-font-smoothing: antialiased;
   -moz-osx-font-smoothing: grayscale;
   text-rendering: optimizeLegibility;
   ```
   Ngăn chặn trình duyệt render viền xám mờ quanh nét chữ, giúp ký tự sắc lẹm và thanh thoát.

---

## 2. BẢNG THÔNG SỐ TYPOGRAPHY CHUẨN MỰC (TYPOGRAPHIC SCALE)

| Khối giao diện | Kích thước (`font-size`) | Trọng số (`font-weight`) | Độ dãn dòng (`line-height`) | Khoảng cách chữ (`letter-spacing`) |
| :--- | :--- | :--- | :--- | :--- |
| **Bong bóng Chat (`.bubble`, `p`, `li`)** | `13.5px` | `350` | `1.7` | `-0.005em` |
| **Bảng biểu (`.bubble table`)** | `12.5px` | `350` | `1.5` | `0` |
| **Tiêu đề H1 (`.bubble h1`)** | `16px` | `600` | `1.4` | `-0.01em` |
| **Tiêu đề H2 (`.bubble h2`)** | `14.5px` | `550` | `1.4` | `-0.005em` |
| **Tiêu đề H3 (`.bubble h3`)** | `13.5px` | `500` | `1.4` | `0` |
| **Thẻ in đậm (`<strong>`, `<b>`)** | Kế thừa | `550` *(Hạ từ 700)* | Kế thừa | Kế thừa |
| **Khung nhập liệu (`.chat-textarea`)** | `13.5px` | `350` | `1.55` | `0` |
| **Nút hành động & Chip gợi ý** | `11.5px - 12px` | `400 - 450` | `1.4` | `-0.005em` |
| **Nội dung Canvas (`.canvas-body`)** | `14px` | `350` | `1.7` | `-0.005em` |

---

## 3. NGUYÊN TẮC HẠ TÔNG CÁC CẤP TIÊU ĐỀ (VISUAL HIERARCHY DAMPENING)

- **Cấm lạm dụng Font-Weight 700/800:** Trên giao diện Dark-Mode, chữ `font-weight: 700` tạo ra độ tương phản quá gắt, gây đứt gãy nhịp đọc. Toàn bộ tiêu đề bài viết trong chat và Canvas khống chế ở trần `font-weight: 600`.
- **Cân bằng sắc độ màu (Luminance Balancing):**
  - Chữ văn bản chính (`.bubble p`): Dùng mã `#e2e8f0` (Slate-200) thay vì trắng tuyệt đối (`#ffffff`), giảm độ chói lóa.
  - Chữ trích dẫn/bảng nguồn: Dùng mã `#cbd5e1` (Slate-300).
  - Khối mã code: Dùng nền `rgba(0,0,0,0.35)` với màu chữ nổi Cyan nhạt `#38bdf8`.

---

## 4. BẢO CHỨNG PHÁT HÀNH & CHỐNG LỖI CACHE (CACHE-BUSTING DISCIPLINE)

- Mỗi khi có bất kỳ điều chỉnh nào về file CSS (`portal_chat.css`, `portal_base.css`, `portal_chat_canvas.css`), kỹ sư BẮT BUỘC cập nhật query timestamp trên các thẻ `<link rel="stylesheet">` trong `agent-engine/portal/index.html`:
  ```html
  <link rel="stylesheet" href="/portal/portal_chat.css?v=YYYYMMDD_HHMM">
  ```
- Thao tác này giúp điện thoại di động và máy tính của khách hàng tự động nhận diện stylesheet mới ngay lập tức mà không cần xóa cache trình duyệt thủ công.

