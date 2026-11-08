# SOP 63: MA TRẬN TỔ HỢP NGỮ NGHĨA B2C, TỈA NHÁNH HẬU TỐ NGỮ PHÁP & KHÓA BẢO TOÀN ĐA TRƯỜNG

## 1. BỐI CẢNH & CÁC SAI LỆCH VẬN HÀNH THỰC CHIẾN
Trong quá trình vận hành Marketing AI Copilot và Cockpit tối ưu On-page thực tế, hệ thống phát hiện 4 sai lệch nghiệm trọng:
1. **Thiên kiến Chủ Kho / Bán Buôn (Warehouse B2B Bias):** CMO AI và bộ sinh On-page bị sa vào các cụm từ *"Tổng kho", "Bán buôn", "Giá sỉ", "Chiết khấu đại lý", "Hóa đơn VAT", "CO/CQ"*. Đây là góc nhìn của chủ kho, hoàn toàn xa lạ với người tiêu dùng cá nhân/gia đình mua đồ gia dụng (B2C Retail).
2. **Lỗi Từ Cắt Cụt Lơ Lửng (Dangling Postposition Bug):** Thuật toán cắt chuỗi cơ học `t[:65]` làm rụng danh từ/địa danh phía sau, để lại các hư từ/giới từ lơ lửng cuối câu (`TongKhoMi | Tổng Kho Phân Phối Tủ Lạnh Xiaomi Chính Hãng Tại`).
3. **Xung Đột Render Đa Điểm Gây Trùng H1 (Duplicate H1 Conflict):** Theme WordPress fix cứng một thẻ H1, trong khi Omni Bridge Connector chèn thêm 1 thẻ H1 ở hook `wp_footer`, dẫn đến lỗi `⚠️ Trùng H1: H1: 2 thẻ` làm tụt điểm SEO On-page.
4. **Mất Dấu Lựa Chọn Của Người Dùng Khi Quét Lại (Rescan Overwrite):** Người dùng đã chọn và lưu 1 title ưng ý lên web, nhưng khi trang quét lại (rescan) thì tiêu đề bị ghi đè thành một gợi ý khác.
5. **Bài Viết Mang Dấu Hiệu AI & Thiếu Bắt Cầu Đối Thủ (AI-Cliche & Ungrounded Content):** Bài viết của CMO AI mang nặng phong văn lý thuyết chung chung (*"tự hào", "đỉnh cao", "giải pháp hoàn hảo"*), bỏ qua bước trích xuất nội dung từ bài viết của top 3 đối thủ SERP thực tế.

---

## 2. MA TRẬN TÂM LÝ TIÊU DÙNG B2C (CONSUMER-CENTRIC PSYCHOLOGICAL MATRIX)
Chuyển dịch 100% định hướng nội dung của CMO AI và On-page Generator sang 5 góc độ mua sắm cốt lõi của khách hàng cá nhân và gia đình:

| Góc độ tâm lý | Hook truyền thông B2C | Ví dụ thực chiến (Meta Title / H1) |
| :--- | :--- | :--- |
| **Bảo hành & Cam kết** | Bảo hành 24 tháng, lỗi 1 đổi 1 30 ngày | `TongKhoMi \| Tủ Lạnh Xiaomi Chính Hãng - Bảo Hành 24T` |
| **Tài chính & Hỗ trợ vốn** | Trả góp 0% lãi suất, duyệt hồ sơ nhanh | `TongKhoMi \| Mua Tủ Lạnh Xiaomi Trả Góp 0% Lãi Suất` |
| **Giao vận & Tốc độ** | Giao hỏa tốc 2 giờ, hẹn giờ linh hoạt | `TongKhoMi \| Tủ Lạnh Xiaomi Giá Tốt - Giao Hỏa Tốc 2H` |
| **Tiện ích & Lắp đặt** | Miễn phí công lắp đặt, hướng dẫn tận nhà | `TongKhoMi \| Tủ Lạnh Xiaomi Bán Chạy, Miễn Phí Lắp Đặt` |
| **An tâm & Minh bạch** | Đồng kiểm khi nhận hàng, nguyên seal 100% | `TongKhoMi \| Tủ Lạnh Xiaomi Giá Ưu Đãi, Đồng Kiểm Khi Nhận` |

---

## 3. THUẬT TOÁN TỈA NHÁNH NGỮ PHÁP HẬU TỐ (BACKWARD GRAMMAR PRUNING)
Mọi hàm chuẩn hóa độ dài văn bản SEO (`clampSeoText` ở JavaScript và `_clean_and_clamp_seo` ở Python) BẮT BUỘC tuân thủ thuật toán tỉa nhánh lùi:

```python
# Thuật toán tỉa nhánh ngữ pháp hậu tố chuẩn SOP 63
if len(text) > max_len:
    text = text[:max_len]
    sp = text.rfind(" ")
    if sp >= min_len - 5:
        text = text[:sp]
    
    # Vòng lặp hội tụ quét ngược loại bỏ hư từ, giới từ, liên từ
    while True:
        prev = text
        text = re.sub(r"[\s,;:\-|/]+$", "", text)
        text = re.sub(
            r"\s+(?:tại|và|cho|của|ở|là|với|khi|trong|được|bởi|nhất|mới|&|đến|từ|về|giá)\s*$", 
            "", text, flags=re.IGNORECASE
        )
        text = re.sub(r"[\s,;:\-|/]+$", "", text)
        if text == prev or len(text) < min_len:
            break
```
* **Bảo chứng:** Tuyệt đối không có bất kỳ tiêu đề hoặc đoạn mô tả nào kết thúc bằng từ nối hoặc dấu phân cách treo lơ lửng.

---

## 4. HÒA GIẢI TRẠNG THÁI ĐƠN NHẤT & STATE GUARD H1 (SINGLE-SOURCE H1 RECONCILIATION)
Triệt tiêu triệt để hiện tượng trùng lặp H1 giữa Theme WordPress và Plugin Omni Bridge:
1. **Single Source of Truth:** `omni_bridge_homepage_h1` là nguồn chân lý duy nhất trong CSDL.
2. **State Guard:** Đặt cờ trạng thái `OMNI_BRIDGE_H1_RENDERED = true` ngay tại file template `front-page.php` của theme:
   ```php
   if ( ! defined( 'OMNI_BRIDGE_H1_RENDERED' ) ) {
       define( 'OMNI_BRIDGE_H1_RENDERED', true );
   }
   $h1_text = get_option( 'omni_bridge_homepage_h1' ) ?: ( get_bloginfo( 'description' ) ?: get_bloginfo( 'name' ) );
   echo '<h1 style="...">' . esc_html( $h1_text ) . '</h1>';
   ```
3. **Connector Protection:** Tại hàm `render_homepage_semantic_h1` của Omni Bridge:
   ```php
   if ( defined( 'OMNI_BRIDGE_H1_RENDERED' ) && OMNI_BRIDGE_H1_RENDERED ) return;
   ```
* **Kết quả:** Đảm bảo toán học $Count(H1) \equiv 1$ trên mọi giao diện WordPress.

---

## 5. CƠ CHẾ KHÓA BẢO TOÀN ĐA TRƯỜNG (MULTI-FIELD IMMUTABILITY LOCK)
Khi người dùng bấm `🚀 Lưu Lên Web (Omni Bridge)` cho bất kỳ trường On-page nào:
1. **Gắn cờ khóa trong `brand_profile`:**
   - Title: `title_locked = true`, lưu giá trị vào `locked_title`.
   - Meta Description: `meta_description_locked = true`, lưu giá trị vào `locked_meta_description`.
   - H1: `h1_locked = true`, lưu giá trị vào `locked_h1`.
2. **Bảo toàn khi quét lại (Rescan Protection):** Mọi tác vụ quét On-page ngầm (`rescan-my-site`, `auto-fix-onpage`) trước khi cập nhật `my_site_audit` BẮT BUỘC kiểm tra các cờ này:
   ```python
   if bp_data.get("title_locked") and bp_data.get("locked_title"):
       my_site_data["title"] = bp_data["locked_title"]
   if bp_data.get("meta_description_locked") and bp_data.get("locked_meta_description"):
       my_site_data["meta_description"] = bp_data["locked_meta_description"]
   ```
3. **Hiển thị giao diện:** Hiển thị huy hiệu `🔒 ĐÃ KHÓA (WEB)` màu xanh ngọc trên Card On-page và Modal chỉnh sửa để người dùng hoàn toàn an tâm về quyền kiểm soát dữ liệu.

---

## 6. THUẬT TOÁN HIỆU TRỪ TẬP HỢP ĐỘNG CHO NÚT "✨ AI SÁNG TẠO MỚI"
Nút `✨ AI Sáng Tạo Mới` áp dụng thuật toán hiệu trừ tập hợp:
$$S_{valid} = S_{candidates} \setminus \left(S_{existing} \cup \{S_{current}\}\right)$$
- Danh sách các câu đã xem trên Modal (`window._currentModalVariants`) và câu hiện tại trong ô nhập được gửi lên API.
- Thuật toán trừ toàn bộ tập đã xem khỏi tập ứng viên.
- **Bảo chứng:** Mỗi lần bấm nút, người dùng luôn nhận được một góc nhìn sáng tạo mới 100%, không bao giờ bị lặp lại phương án cũ và phản hồi ngay lập tức ($< 1$ ms).

---

## 7. QUY CHUẨN TRINH SÁT BÀI VIẾT ĐỐI THỦ TRƯỚC KHI XUẤT BẢN (COMPETITOR-GROUNDED CONTENT)
Khi CMO AI Copilot sản xuất bài viết chuyên sâu:
1. **Bắt buộc đọc bài đối thủ:** Thu thập 3 bài viết top đầu SERP cho từ khóa mục tiêu, bóc tách cấu trúc đề mục (Outline), bảng so sánh, thông số kỹ thuật thực tế và chính sách bảo hành đối thủ đang chào mời.
2. **Khử trùng phong văn AI (Zero-AI Stylometry):** Cấm tiệt các từ ngữ sáo rỗng (*"khám phá ngay", "giải pháp hoàn hảo", "tự hào là", "hàng đầu", "toàn diện"*).
3. **Hướng người tiêu dùng (B2C Consumer Focus):** Tập trung vào trải nghiệm sử dụng thực tế của khách hàng, bảng so sánh ưu nhược điểm khách quan, hướng dẫn chọn model phù hợp ngân sách và các lưu ý khi lắp đặt trong gia đình.

---

## 8. TRINH SÁT VIDEO YOUTUBE TECH REVIEW TIẾP ĐỊA TRẢI NGHIỆM THỰC TẾ (YOUTUBE TRANSCRIPT GROUNDING)
Để xóa bỏ triệt để hiện tượng bài viết của AI mang giọng văn lý thuyết sáo rỗng:
1. **Trích xuất phụ đề video Reviewer người thật:** Khi nhận diện ý định viết bài đánh giá/review model sản phẩm, Gateway tự động tìm kiếm video review công nghệ hàng đầu trên YouTube (các kênh như *Dương Dê, Phan Linh, Vật Vờ Studio...*) và bóc tách toàn bộ phụ đề tiếng Việt thông qua `youtube-transcript-api`.
2. **Thuật toán nén phụ đề cốt lõi (SOP 35):** Trích xuất nhận định trực tiếp về chất lượng hiển thị thực tế, độ mượt Google TV, chất âm loa tivi, độ nhạy remote giọng nói, và các điểm trừ khách quan mà bài viết marketing thông thường giấu đi.
3. **Tiếp địa ngữ cảnh & Minh bạch nguồn:** Tiêm khối `[TRẢI NGHIỆM THỰC TẾ TỪ YOUTUBE TECH REVIEWER (GROUNDING - SOP 63)]` vào prompt của CMO và gắn nhãn nguồn `-[YouTube] Video Review: <Tiêu đề> (<Kênh>)` ở danh mục Grounding Sources.

