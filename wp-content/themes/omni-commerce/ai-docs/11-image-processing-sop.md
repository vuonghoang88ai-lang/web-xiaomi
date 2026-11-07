# Standard Operating Procedure (SOP): Quy Trình Xử Lý Hình Ảnh Sản Phẩm & Bài Viết Tự Động (Dành Cho CrewAI & Đội Ngũ Vận Hành)

> **Mục tiêu:** Tài liệu tri thức này chuẩn hóa 100% quy trình xử lý, làm sạch, đóng mộc thương hiệu và tích hợp hình ảnh vào WordPress / WooCommerce. Được thiết kế chuyên biệt để làm nguồn tri thức (Knowledge Base) cho **CrewAI Multi-Agent System** (đặc biệt là `Image Processing Agent`, `Product Ingestion Agent`, `SEO Agent`).

---

## 1. Nguyên Tắc Cốt Lõi: Chân Thực & Bảo Vệ Bản Quyền (Authenticity First)

1. **Tuyệt đối không dùng AI tạo sinh (No AI Hallucination/CGI):**
   - Không dùng AI để vẽ phòng khách, kệ tivi hoặc tạo dựng hình ảnh giả lập nếu chưa có chỉ định rõ ràng từ người quản trị.
   - Luôn sử dụng nguồn ảnh thật: Ảnh chụp thực tế của nhà sản xuất (Xiaomi, Viomi...), ảnh phòng studio hoặc ảnh chụp thực tế tại showroom/kho hàng.
2. **Quy tắc làm sạch thương hiệu đối thủ (Brand Cleansing):**
   - Khi cào dữ liệu (scrape) hoặc nhập sản phẩm từ bên ngoài, mọi watermark, logo, text của đối thủ (ví dụ: `tivixiaomihanoi`, `sudospaces`, `dienmay...`) bắt buộc phải được tẩy sạch bằng thuật toán nội suy ánh sáng (*gradient interpolation*), clone vùng lân cận hoặc crop bố cục hợp lý, không để lại vệt mờ hay vết cắt ghép thô.

---

## 2. Tiêu Chuẩn Đóng Mộc Thương Hiệu (Watermarking Standard)

### A. Nghiêm cấm:
- ❌ **Không đóng khung hộp sticker:** Tuyệt đối không tạo hộp chữ nhật trắng có viền cam/xám dày cộp dán đè lên góc ảnh (hiệu ứng "tem dán đè" gây cảm giác rẻ tiền, nghiệp dư).
- ❌ **Không che khuất chủ thể:** Không để logo đè lên màn hình tivi, bề mặt sản phẩm hoặc các chi tiết kỹ thuật quan trọng.

### B. Tiêu chuẩn bắt buộc:
- ✅ **Logo chìm không viền (Minimalist Watermark):**
  - **Tỷ lệ:** Chiều ngang logo chiếm **20% – 25%** chiều rộng của ảnh.
  - **Vị trí:** Đặt tại góc trên bên trái (**Top-Left**) hoặc góc trên bên phải (**Top-Right**), cách mép ảnh biên lề an toàn (`margin = 4%` kích thước ảnh).
  - **Màu sắc & Độ trong suốt (Alpha Blending):**
    - **Nền tối / Nền gỗ / Phòng khách:** Đổi màu chữ `TongKhoMi.com` sang trắng `#FFFFFF` (opacity 85%), giữ nguyên khối lập phương cam thương hiệu `#FF6700` (opacity 90%), thêm bóng đổ mềm (*soft drop shadow*, blur 3-4px, alpha 40%).
    - **Nền trắng / Nền Studio:** Giữ màu chữ xám đậm nguyên bản `#2D3748` kết hợp khối lập phương cam `#FF6700` với độ trong suốt 85%.

---

## 3. Quy Chuẩn Kích Thước & Responsive Cho WooCommerce

Mọi ảnh sản phẩm đại diện (Featured Image) bắt buộc xuất bản chuẩn tỷ lệ vuông **1:1** và đồng bộ 5 kích thước chuẩn:

| Kích thước | Chiều rộng x Chiều cao | Mục đích sử dụng trong Theme omni-commerce |
| :--- | :--- | :--- |
| **Master HD** | `1000 x 1000 px` | Ảnh gốc phóng to, ảnh lightbox, Retina displays |
| **Single Product** | `600 x 600 px` | Ảnh hiển thị chính tại trang chi tiết sản phẩm (`single-product.php`) |
| **Catalog / Archive** | `300 x 300 px` | Card sản phẩm tại trang chủ, trang danh mục, widget gợi ý |
| **Admin Thumbnail** | `150 x 150 px` | Danh sách sản phẩm trong WP-Admin & POS Dashboard |
| **Gallery Thumbnail** | `100 x 100 px` | Dải ảnh trượt bên dưới ảnh chính (`#gallery-thumbs-container`) |

- **Định dạng file:** `JPEG` (Quality 90-95%, Optimize=True) hoặc `WebP`.
- **Dung lượng mục tiêu:** Dưới **250 KB** cho ảnh Master 1000px, dưới **50 KB** cho các ảnh thumbnail để tối ưu 100 điểm Google PageSpeed Insights.

---

## 4. Tiêu Chuẩn Đặt Tên File Chuẩn SEO & Cache-Busting

1. **Quy tắc đặt tên file (Slug Convention):**
   ```text
   {loai-san-pham}-{thuong-hieu}-{dong-san-pham}-{kich-thuoc/dung-tich}-{nam}-{brand}.jpg
   ```
   *Ví dụ chuẩn:* `tivi-xiaomi-redmi-a-pro-75-inch-2025-tongkhomi.jpg`

2. **Cơ chế chống lưu đệm trình duyệt (Cache-Busting):**
   - Khi chỉnh sửa hoặc làm mới ảnh sản phẩm cũ, **không ghi đè trực tiếp lên tên file cũ** nếu tên file đó đã được Google hoặc trình duyệt người dùng truy cập.
   - Thêm hậu tố nhận diện thương hiệu hoặc versioning (ví dụ: `-tongkhomi.jpg` hoặc `-v2.jpg`) để ép buộc trình duyệt và CDN tải ngay lập tức ảnh mới.

---

## 5. Quy Trình Đồng Bộ Vào CSDL WordPress (Database & Attachment Meta)

Khi tự động hóa qua CrewAI hoặc Script Python:
1. **Lưu tệp:** Đưa ảnh vào thư mục upload chuẩn `/wp-content/uploads/YYYY/MM/`.
2. **Cập nhật Bảng `wp_posts`:**
   - Cập nhật `guid` của attachment trỏ về URL công khai đầy đủ.
   - Gán ID attachment vào meta `_thumbnail_id` của sản phẩm.
3. **Cập nhật Bảng `wp_postmeta`:**
   - Cập nhật key `_wp_attached_file` với relative path: `YYYY/MM/{filename}.jpg`.
   - Cập nhật key `_wp_attachment_metadata` với chuỗi PHP Serialized Array khai báo chính xác mảng `sizes` (`medium`, `thumbnail`, `woocommerce_thumbnail`, `woocommerce_gallery_thumbnail`).
4. **Cập nhật Bài Viết Mô Tả (`post_content`):**
   - Đảm bảo thẻ `<img src="...">` mở đầu bài viết mô tả cũng trỏ về file ảnh mới này để tạo sự nhất quán từ ảnh đại diện đến nội dung chi tiết.
5. **Kích hoạt Xóa Cache Đối Tượng (Redis Flush):**
   - Thực thi ngay lệnh: `docker exec michinhhang-redis-1 redis-cli flushall` để làm mới bộ nhớ đệm.

---

## 6. Mẫu Prompt & Tool Contract Cho CrewAI Agent

Khi cấu hình `CrewAI`, định nghĩa Agent và Task theo hợp đồng mẫu sau:

```python
from crewai import Agent, Task, Crew
from crewai.knowledge.source.string_knowledge_source import StringKnowledgeSource

# Image Processing Agent Configuration
image_agent = Agent(
    role="Chuyên viên Xử lý Đồ họa & Chuẩn hóa Ảnh TMĐT",
    goal="Tiếp nhận ảnh sản phẩm thô, tẩy sạch đối thủ, đóng mộc TongKhoMi chìm tinh tế, xuất đủ kích thước 1:1 chuẩn SEO và nạp vào WordPress",
    backstory="""Bạn là chuyên gia đồ họa TMĐT hàng đầu của hệ sinh thái TongKhoMi.com.
    Bạn tuân thủ nghiêm ngặt SOP tại '11-image-processing-sop.md'. Tuyệt đối không tự vẽ ảnh AI giả lập,
    không đóng khung hộp sticker thô, luôn tạo watermark chìm sang trọng và đảm bảo hình ảnh nhẹ, sắc nét, chuẩn SEO Google.""",
    verbose=True
)
```
