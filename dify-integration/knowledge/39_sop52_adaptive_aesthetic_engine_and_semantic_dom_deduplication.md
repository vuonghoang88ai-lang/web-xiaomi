# SOP 52: THUẬT TOÁN THẨM MỸ TỰ THÍCH ỨNG, KHỬ TRÙNG LẶP NGỮ NGHĨA DOM & DỌN DẸP TÀN DƯ NGÀNH HÀNG (ADAPTIVE AESTHETIC ENGINE & DOM SEMANTIC DEDUPLICATION)

> **Mã quy chuẩn:** SOP 52 — ADAPTIVE AESTHETIC ENGINE & DOM SEMANTIC DEDUPLICATION  
> **Áp dụng:** Omni Bridge Connector, Hệ thống Xuất bản CMS, Tích hợp Footer E-E-A-T, Đồng bộ Google Site Kit  
> **Mục tiêu:** Xóa bỏ hoàn toàn mã fix cứng giao diện (Zero Hardcoded Selectors/Colors); thiết lập thuật toán nhận diện thẩm mỹ tự động, thuật toán khử trùng lặp ngữ nghĩa DOM và dọn sạch tàn dư trang không đúng ngành nghề.

---

## 1. NGUYÊN TẮC BẤT BIẾN: KHÔNG FIX CỨNG THẨM MỸ (ZERO HARDCODED AESTHETICS)

### 1.1. Anti-Pattern Thường Gặp
- Khi phát hiện khối menu chân trang bị trùng lặp trên một theme cụ thể (ví dụ: `Vuong Story Theme`), lập trình viên thường có xu hướng tiện tay fix cứng class selector của theme đó:  
  `❌ .sab-footer-menu { display: none !important; }`
- Khi cần tạo nền cho thanh Khiên Tín Nhiệm (Shield Trust Bar), fix cứng mã màu hex:  
  `❌ background: #f8fafc; color: #334155;`
- **Hậu quả:**  
  1. Khi người dùng đổi theme sang Astra, Flatsome, Kadence hay GeneratePress, selector `.sab-footer-menu` hoàn toàn vô dụng, lỗi trùng lặp menu lập tức tái xuất hiện.  
  2. Mã màu hex cố định sẽ bị chói lóa trên theme nền tối (Dark Mode) hoặc bị chìm màu trên theme nền sáng, làm vỡ thẩm mỹ tổng thể.

### 1.2. Chuẩn Thuật Toán Thay Thế
Mọi bài toán giao diện và tương thích Theme BẮT BUỘC phải giải quyết bằng **Thuật toán Ngữ nghĩa (Semantic Traversal)** và **Hàm Màu Động (CSS Dynamic Environment Sensing)**.

---

## 2. THUẬT TOÁN KHỬ TRÙNG LẶP NGỮ NGHĨA DOM (DOM SEMANTIC SET INTERSECTION)

### 2.1. Bản chất Kỹ thuật
Thay vì dựa vào tên class CSS tĩnh của theme, hệ thống sử dụng thuật toán **Tập hợp Giao cắt Ngữ nghĩa (Set Intersection)** trên cây DOM phía client:

```javascript
(function() {
    var legalUrls = <?php echo json_encode(array_values(array_filter(array_map(function($i){ return untrailingslashit($i['url'] ?? ''); }, $links)))); ?>;
    if (!legalUrls || !legalUrls.length) return;
    
    // 1. Chuẩn hóa tập hợp URL đích chuẩn mực
    var legalSet = new Set(legalUrls.map(function(u){ return (u || '').toLowerCase(); }));
    
    // 2. Quét toàn bộ khối chân trang
    var footers = document.querySelectorAll('footer, [role="contentinfo"], .site-footer');
    footers.forEach(function(f) {
        f.querySelectorAll('nav, ul').forEach(function(el) {
            // Không ẩn chính thanh Khiên Tín Nhiệm E-E-A-T
            if (el.closest('#omni-eeat-trust-footer')) return;
            
            // 3. Đếm số liên kết giao cắt với tập legalSet
            var matchCount = 0;
            el.querySelectorAll('a').forEach(function(a) {
                var href = (a.getAttribute('href') || '').replace(/\/+$/, '').toLowerCase();
                if (legalSet.has(href)) matchCount++;
            });
            
            // Ngưỡng quyết định: Nếu chứa >= 3 URL pháp lý trùng lặp -> Ẩn cụm menu thô
            if (matchCount >= 3) {
                el.style.setProperty('display', 'none', 'important');
            }
        });
    });
})();
```

### 2.2. Ưu việt
- Hoạt động đúng trên **100% mọi Theme WordPress** mà không cần biết cấu trúc class hay id của theme đó.
- Phân biệt chính xác giữa menu điều hướng chung của website (chỉ có 0 - 1 link pháp lý) với cụm menu pháp lý trùng lặp (chứa $\ge 3$ link pháp lý).

---

## 3. THUẬT TOÁN THẨM MỸ TỰ THÍCH ỨNG (ADAPTIVE FROSTED GLASS & COLOR-MIX)

### 3.1. Cơ chế Cảm biến Màu Môi trường qua `currentColor`
Thay vì chỉ định màu nền cố định, sử dụng hàm `color-mix` của CSS Color Module Level 4:

```css
#omni-eeat-trust-footer {
    width: 100%;
    /* Tự động hòa trộn 4% màu chữ hiện tại với độ trong suốt */
    background: color-mix(in srgb, currentColor 4%, transparent);
    border-top: 1px solid color-mix(in srgb, currentColor 10%, transparent);
    backdrop-filter: blur(8px);
    -webkit-backdrop-filter: blur(8px);
    padding: 14px 20px;
    font-size: 13px;
    color: inherit;
    text-align: center;
    line-height: 1.8;
    margin-top: 30px;
    box-sizing: border-box;
    clear: both;
}

#omni-eeat-trust-footer a {
    color: inherit;
    opacity: 0.75;
    text-decoration: none;
    font-weight: 500;
    transition: opacity 0.2s ease, color 0.2s ease;
}

#omni-eeat-trust-footer a:hover {
    opacity: 1;
    color: #0284c7;
}

#omni-eeat-trust-footer .eeat-shield-label {
    font-weight: 600;
    color: inherit;
    opacity: 0.95;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
```

### 3.2. Động lực học Thẩm mỹ
- **Light Mode (Nền trắng, chữ tối):** `currentColor` mang giá trị tối $\rightarrow$ Nền tự hóa thành lớp kính xám khói 4% nhạt cao cấp, chữ tối mờ 75%, viền mờ 10%.
- **Dark Mode (Nền đen/tối, chữ sáng):** `currentColor` mang giá trị sáng $\rightarrow$ Nền tự hóa thành kính mờ tối (Dark Frosted Glass), tiệp 100% với độ sâu của mọi sắc độ đen `#000000`, than chì `#121212`, xanh đậm `#0f172a`.

---

## 4. DỌN DẸP TÀN DƯ NGÀNH HÀNG LẠC QUẺ (MISMATCHED ARCHETYPE RESIDUAL PURGE)

### 4.1. Vấn đề Phát sinh
- Khi chuyển đổi mục đích website (hoặc sử dụng bản mẫu ban đầu là E-Commerce), trên website đọc truyện chữ (`sab_story`) có thể còn tồn tại các trang:
  - `chinh-sach-bao-hanh` (Chính sách bảo hành & đổi mới chuẩn hãng)
  - `chinh-sach-doi-tra` (Chính sách đổi trả & hoàn tiền)
- Theme có thể có cơ chế fallback tự quét các trang này ra chân trang nếu chưa cấu hình menu, gây phản cảm cho độc giả đọc truyện.

### 4.2. Cơ chế Bảo vệ Hai Lớp
1. **Lớp Giao diện Tức thì:**
   ```css
   footer nav, [role="contentinfo"] nav { display: none !important; }
   ```
   Ẩn toàn bộ thẻ `<nav>` thô của theme nằm trong chân trang, nhường chỗ hoàn toàn cho thanh Khiên Tín Nhiệm E-E-A-T độc quyền.
2. **Lớp Dữ liệu Chuẩn SEO (Data Hygiene):**
   - Tự động nhận diện Archetype `content_media` qua tên miền (`truyen`, `novel`, `manga`...).
   - Tự động chuyển các trang bán hàng không liên quan (`chinh-sach-bao-hanh`, `chinh-sach-doi-tra`) sang trạng thái `trash` (thùng rác) qua `wp_trash_post()`, ngăn chặn triệt để Googlebot index các trang rác.

---

## 5. BẮT TAY HAI CHIỀU GOOGLE SEARCH CONSOLE & NHẬN DIỆN SITE KIT (SOP 48 EXPANDED)

### 5.1. Sửa Lỗi Nhận Diện Google Site Kit Credentials
- **Nguyên nhân gốc rễ:** Plugin Google Site Kit lưu `googlesitekit_credentials` trong `wp_options` dưới dạng **chuỗi mã hóa (string)**, không phải mảng (array).
- **Quy tắc kiểm tra chuẩn:**
  ```php
  // ❌ SAI: khiến Site Kit đã kết nối nhưng vẫn bị báo chưa kết nối
  $is_connected = is_array($credentials) && !empty($credentials) && !empty($property_uri);

  // ✅ ĐÚNG: hỗ trợ cả chuỗi mã hóa lẫn mảng
  $is_connected = !empty($credentials) && !empty($property_uri);
  ```

### 5.2. Tự Động Hóa Bắt Tay Hai Chiều (Zero-Manual Handshake)
- Portal AI tự động đẩy 3 thông số định danh:
  - `omni_bridge_gsc_engine_url`: Endpoint máy chủ AI SaaS
  - `omni_bridge_gsc_tenant_id`: ID định danh tổ chức
  - `omni_bridge_gsc_project_id`: ID định danh dự án
- Đẩy trực tiếp vào `wp_options` thông qua endpoint `/wp-json/omni-bridge/v1/interop/execute` (`action_type: update_option`), triệt tiêu 100% việc yêu cầu khách hàng phải copy-paste thủ công.

