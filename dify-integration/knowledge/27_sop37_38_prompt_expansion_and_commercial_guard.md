# SOP 37 & 38: SPECULATIVE PROMPT EXPANSION & COMMERCIAL REALITY GUARD

## 1. SOP 37: TỰ ĐỘNG LÀM GIÀU & TỐI ƯU HÓA LỆNH (SPECULATIVE PROMPT EXPANSION)

### Bối cảnh & Thách thức
Người dùng thường nhập prompt ngắn (ví dụ: *"viết bài tivi 55 inch"*, *"soi đối thủ"*). Khi đó AI phải suy đoán bối cảnh, dễ sinh nội dung chung chung, thiếu mã model sản phẩm và không đạt chuẩn cấu trúc bài viết 10x.

### Cơ chế Thuật toán Prompt Expander
1. **Phím tắt & Nút bấm trực quan:**
   - Người dùng bấm nút **`✨ Tối ưu 10x`** tại ô chat hoặc nhấn phím tắt **`Ctrl + E`**.
2. **Phân tích Intent & Ánh xạ SKU kho:**
   - Thuật toán bóc tách Goal-Driven Decomposition: Tự động phân loại sang `deliverable_product`, `competitor_recon`, `campaign_strategy` hoặc `deliverable_blog`.
   - Quét ma trận từ khóa dự án để tự khớp model sản phẩm chính xác trong kho (ví dụ: *"tivi 55"* $\rightarrow$ `Tivi Xiaomi Google TV 55 Inch A Pro 2026`).
3. **Kiến tạo Prompt 10x Đầy Đủ:**
   - Bổ sung vai trò CMO, thương hiệu `tongkhomi.com`, 4 đề mục H2 giải quyết nỗi đau khách hàng, bảng so sánh giá sàn, thông số kỹ thuật cốt lõi và CTA chuyển đổi dứt khoát.

---

## 2. SOP 38: KIỂM ĐỊNH THƯƠNG MẠI & ĐỐI SOÁT GIÁ KHO (COMMERCIAL REALITY & PRICE GUARD)

### Bối cảnh & Thách thức
AI dễ gặp hiện tượng ảo giác (hallucination), bịa sai giá bán (ví dụ ghi giá 3.200.000đ thay vì 7.490.000đ) hoặc bảo hành 3 tháng thay vì 24 tháng chính hãng. Nếu xuất bản lên website sẽ gây tổn thất uy tín và thiệt hại doanh thu.

### Cơ chế Thuật toán Commercial Reality Guard
1. **Bóc tách Thực thể Số (Named Entity Regex - NER):**
   - Tự động quét toàn bộ bài viết để lấy: Đơn giá (`x.xxx.000đ`), Thời hạn bảo hành, Kích thước màn hình, Độ phân giải.
2. **Đối soát Thực tế (Ground-Truth Cross-Check):**
   - So sánh trực tiếp giá phát hiện với bảng giá niêm yết kho và dữ liệu đối thủ SERP của dự án.
   - Cho phép sai số chấp nhận được trong biên độ $\pm 25\%$ (chiết khấu hoặc khuyến mãi). Nếu vượt ngưỡng, hệ thống kích hoạt cảnh báo sai lệch.
3. **Hiển thị Huy Hiệu & Cảnh Báo Trực Quan:**
   - **Khớp đúng:** Gắn huy hiệu xanh `[✅ BẢO CHỨNG 100% THÔNG SỐ KHO]`.
   - **Sai lệch:** Gắn cờ vàng `[⚠️ PHÁT HIỆN SAI LỆCH GIÁ KHO]`, chú thích rõ giá kho thực tế để người dùng chỉnh sửa trước khi xuất bản.

