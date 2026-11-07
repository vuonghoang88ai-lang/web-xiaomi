# SOP 18: ĐỘNG CƠ HỢP NHẤT ĐA ĐỐI THỦ & XỬ LÝ SẢN PHẨM WOOCOMMERCE HÀNG LOẠT

> **Tóm tắt quy chuẩn**: Hướng dẫn cho AI Agents về logic đối chiếu thông số giữa nhiều đối thủ (Cross-Competitor Synthesis), phân biệt 2 loại bài viết (SEO Blog vs Chi tiết sản phẩm WooCommerce), và cơ chế xếp hàng xử lý danh sách sản phẩm hàng loạt (Async Batch Queue).

---

## 1. QUY TẮC ĐỐI CHIẾU ĐA ĐỐI THỦ
1. **Bù đắp thông số thiếu sót**: Nếu đối thủ A chỉ ghi thông số sơ sài, hãy lấy thông số chi tiết từ đối thủ B (tấm nền QLED/MiniLED, RAM/ROM, tần số quét 144Hz, âm thanh Dolby Audio) để tạo ra bảng thông số hoàn chỉnh nhất.
2. **Bóc tách giá bán thực tế**: Luôn lấy Giá bán khuyến mãi (Sale Price) làm căn cứ so sánh, không lấy giá niêm yết ảo chưa giảm.
3. **Đề xuất giá TongKhoMi**: Cạnh tranh số 1 kèm lợi thế bảo hành 2 năm và giao hàng hỏa tốc 2h tại 3 kho Hà Nội.

---

## 2. PHÂN TÁCH 2 LOẠI NỘI DUNG (POST_TYPE)
- **`post` (Bài viết SEO Blog)**: 1500 - 2500 từ chuẩn SOP 12, tháp H1-H3, 0% từ sáo rỗng AI, Bảng Markdown so sánh GEO Schema cho ChatGPT trích dẫn, 5 câu hỏi FAQ, 4 link chính sách bắt buộc.
- **`product` (Chi tiết Sản phẩm WooCommerce)**:
  - H1 Tiêu đề chuẩn hóa: `[Loại SP] + [Model] + [Kích thước] + [Tính năng nổi bật] + [Chính Hãng]`.
  - Tóm tắt USP ngắn (`===SHORT_DESCRIPTION===`): 4-5 bullet points.
  - Mô tả chi tiết (`===FULL_DESCRIPTION===`): 800-1200 từ chuẩn CRO.
  - Lưu vào WooCommerce: Post type `product`, meta giá `_price`, `_regular_price`, `_sale_price`.

---

## 3. CƠ CHẾ XỬ LÝ HÀNG LOẠT (ASYNC BATCH QUEUE)
- Khi nhận danh sách nhiều sản phẩm từ người dùng, gọi `POST /api/v1/campaign/batch`.
- Hệ thống trả về `batch_job_id` trong 200ms và chạy ngầm duyệt qua từng sản phẩm.
- Tra cứu tiến độ qua `GET /api/v1/jobs/{job_id}` cho đến khi hoàn tất 100%.
