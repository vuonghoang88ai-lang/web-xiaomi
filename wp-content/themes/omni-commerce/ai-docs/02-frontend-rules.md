# Frontend Rules — Tailwind, CSS & UI/UX

## 1. Tailwind CSS
- Dùng class từ `tailwind.config.js` — KHÔNG inline CSS, KHÔNG tạo file CSS phụ
- **Compile bắt buộc** sau mỗi khi thêm class mới:
  ```bash
  ./tailwindcss-linux-x64 -i ./src/input.css -o ./style.css --minify
  ```
- **Đồng bộ token màu:** Trước khi bóc tách HTML, đọc `<style>` trong `<head>` của file HTML gốc, copy tất cả custom class vào `src/input.css` và compile trước khi viết PHP
- **Cấm phát minh màu lạ:** Không dùng gradient lạ nếu không có trong HTML mẫu → CSS không compile → white-on-white bug

## 2. Quy tắc Layout Sidebar (archive-product.php)
- **CẤM** `hidden`, `hidden lg:block`, `hidden md:block` cho `<aside>` sidebar
- Sidebar đúng: `w-full md:w-1/3 lg:w-1/4 shrink-0`
- Lưới sản phẩm bên cạnh sidebar: `grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4`
- **CẤM** `lg:grid-cols-4` — ở 1024px với sidebar 25%, còn ~720px cho 4 cột → vỡ layout

## 3. Header cố định (Sticky Header)
- Thẻ `<header>` phải có: `sticky top-0 z-50 shadow-sm transition-all duration-300`
- **Bắt buộc** CSS cưỡng chế:
  ```css
  .main-nav-header { display: flex !important; visibility: visible !important; opacity: 1 !important; }
  ```
- **CẤM** ẩn menu bằng `hidden lg:flex` hay can thiệp media query

## 4. CSS Marquee vô tận (Kỹ thuật chuẩn)
```css
/* ĐÚNG — Duplicate text + animate translateX(0 → -50%) */
@keyframes marquee {
    0%   { transform: translateX(0); }
    100% { transform: translateX(-50%); }
}
.mi-marquee-text {
    display: flex;
    flex-shrink: 0;
    min-width: 100%;
    animation: marquee 20s linear infinite;
}
```
HTML: 2 bản text trong container flex, bản 2 có `aria-hidden="true"`

```css
/* SAI — khoảng trắng trên màn hình rộng */
/* padding-left: 100% + translateX(100% → -100%) */
```

## 5. Menu Hover Art (3 lớp hiệu ứng — không dùng JS)
1. **Persistent Highlight:** `group-hover:text-primary` (cả icon + text)
2. **Floating Icon:** `group-hover:-translate-y-1`
3. **Animated Bottom Bar:** `before:w-0 group-hover:before:w-[80%]` (pseudo-element)
4. **Submenu item:** `hover:translate-x-1`
5. **Dropdown control:** CSS thuần `group-hover:opacity-100 group-hover:visible` — KHÔNG dùng JS

## 6. CSS Dropdown Arrow (CSS Border Triangle)
```html
<!-- Mũi tên tam giác trỏ lên, nối Menu cấp 1 và cấp 2 -->
<ul class="... before:absolute before:-top-[8px] before:left-1/2 before:-translate-x-1/2
           before:border-[8px] before:border-transparent before:border-b-white">
```

## 7. Hiệu ứng Vi tương tác (Micro-interactions)
- Cart badge: `scale-125` trong 300ms khi cập nhật count
- Product card: `hover:-translate-y-1 hover:shadow-[0_10px_25px...]`
- Button: `active:scale-95`
- Trust badge: `hover:-translate-y-1 transition-all duration-300`

## 8. Fallback Section — Quy tắc bắt buộc
- Mọi `<section>` phải có nhánh `else` fallback tĩnh — **CẤM** section ẩn trắng khi DB rỗng
- Số fallback mock items phải **khớp** `grid-cols-N`:
  - `grid-cols-4` → phải có đúng 4 mock items
  - `grid-cols-2` → phải có đúng 2 mock items

## 9. Gallery Zoom & Event Handling
- **CẤM** `transform: scale` đè lên ảnh gốc
- Popup zoom phải nằm **BÊN PHẢI** khung ảnh (500×500px fixed position)
- Tọa độ popup tính theo tỷ lệ con trỏ chuột, trừ hao offset từ `object-contain`
- Nút điều hướng đè lên ảnh: bắt buộc `e.stopPropagation()` + `e.preventDefault()`

## 10. Action Buttons & AJAX Cart

### Layout nút
- 2 nút song song: Container `w-full`, mỗi nút `flex-1` (không dùng `w-1/2`)

### AJAX Add to Cart (Zero-Endpoint)
```js
// ĐÚNG — Không cần tạo wp_ajax_ endpoint
const formData = new FormData(form); // form có trường add-to-cart=[product_id]
const res = await fetch(window.location.href, { method: 'POST', body: formData });
const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
document.querySelector('.cart-contents-count').innerHTML =
    doc.querySelector('.cart-contents-count').innerHTML;
```

### Luồng "Mua Ngay" (Modal)
- Thêm vào giỏ qua AJAX → Modal cảm ơn
- **Nút 1:** "Tiếp tục mua hàng" → đóng Modal + smooth scroll xuống `related.products`
- **Nút 2:** "Thanh toán ngay" → redirect `/checkout/`

### Validation biến thể
- Gỡ WooCommerce default alert
- Nếu `variation_id` rỗng: smooth scroll đến khung phiên bản + `ring-red-500` + Toast

## 11. Mobile-First Product Focus (UX Trang chủ & Danh mục)
- **Triết lý tinh gọn:** Trên mobile, bắt buộc lược bỏ toàn bộ khối thông tin phụ (Features, Ads, News, Video, Value Props) để rút ngắn hành trình chạm đến sản phẩm.
- **Responsive Hiding:** Sử dụng triệt để Media Query `@media (max-width: 899px)` cho các khối banner phụ gây nhiễu và làm dài trang.
- **Grid Ordering:** Tận dụng `order-2 lg:order-1` để hạ cấp độ ưu tiên của cột phụ trên mobile, đẩy Slider/Sản phẩm lên trên cùng.
- **Loại bỏ Banner Dọc trên Mobile:** Các cột banner dọc trong `product-row` (`.mi-product-row-banner`) phải được **ẩn hoàn toàn trên mobile** (`@media (max-width: 899px) { display: none !important; }`) để lưới sản phẩm 2 cột bung rộng full-width 100% màn hình, mang lại trải nghiệm app TMĐT chuẩn (Shopee/Lazada). Trên Desktop (`>= 900px`), banner hiển thị 100% trọn vẹn với bố cục 1/5 và 4/5.

## 12. Mở rộng Layout & Căn lề Toàn Trang
- **Header:** Background trải dài vô tận (`w-full`), nhưng khối nội dung (Topbar, Logo, Search) **PHẢI** được bọc trong `max-w-[1440px] mx-auto` để đồng bộ hoàn toàn với thân trang và Footer trên màn hình lớn.
- **Thân trang & Footer:** Giữ chuẩn `max-w-[1440px]`. Tuyệt đối không xóa giới hạn này. Cấm để Footer chạy tràn 100% width gây vỡ bố cục trên màn 4K.

## 13. Thanh công cụ nổi (Floating Action Bar - FAB)
- **Desktop (`md:`):** Các nút liên hệ xếp dọc (flex-col) bên góc phải màn hình (`bottom-8 right-8`). Nút "Cuộn lên đầu trang" phải đứng tách biệt ở một vùng không gian riêng (ví dụ `bottom-[280px]`) để không bị vướng víu hay đè lên các nút liên hệ khi hiển thị (lỗi Z-index / Flex box order).
- **Mobile (dưới 768px):** Cụm FAB biến thành một **Bottom App Bar** bám dính đáy màn hình (`bottom-0 w-full`), dàn ngang (`flex-row`). Bắt buộc phải bổ sung `padding-bottom: 70px` (hoặc tương đương) cho `<body>` trên thiết bị di động để nội dung cuối trang không bị thanh App Bar này che lấp.

## 14. Dọn dẹp Code Thừa (Fallback & Third-party)
- **Fallback gọn gàng:** Khi hàm ACF `get_field` rỗng, vế `else` phải chứa dữ liệu thực tế (VD: iframe thật của Fanpage Xiaomi) hoặc giao diện giả lập chuẩn (VD: icon thẻ VISA, VNPAY đẹp mắt) thay vì những dòng chữ thông báo lỗi nghèo nàn.
- **CSS Rác:** Không giữ lại CSS cố tình ẩn các nút cuộn trang của plugin/theme khác (như Astra, Elementor) trong file theme của mình. Nếu xuất hiện rác từ plugin, cần xác định nguyên nhân tắt plugin thay vì dùng CSS bịt miệng một cách mù quáng, gây nhiễu loạn với chính code của mình.

## 15. Định dạng Văn bản Cổ điển (the_content)
- Khi sử dụng Tailwind CSS, toàn bộ thẻ HTML cơ bản (h1, h2, ul, ol, strong...) sẽ bị reset mất định dạng. 
- Nếu không cài đặt plugin `@tailwindcss/typography` (vì lý do tối ưu nhẹ theme), bắt buộc phải có một thẻ bọc ngoài (VD: `.mi-page-content`) và viết CSS tùy chỉnh bổ sung ở cuối file hoặc trong `style.css` để khôi phục định dạng: `font-size, font-weight, list-style, margin` cho các thành phần được sinh ra bởi hàm `the_content()`.

## 16. Lỗi Flex-wrap trên Header
- **Nguyên nhân:** Khi sử dụng `flex-wrap` với `justify-between` trên màn hình có kích thước tầm trung (1024px - 1366px), nếu Padding quá lớn (`px-20`) và `gap` quá rộng, Menu (`flex-1`) sẽ đẩy Thanh tìm kiếm xuống dòng dưới.
- **Giải pháp bắt buộc:** 
  - Cấm rớt dòng bằng `lg:flex-nowrap`.
  - Tinh chỉnh Padding và Gap theo từng breakpoint: `lg:px-8 xl:px-12` thay vì `md:px-20` cứng nhắc.
  - Box Tìm kiếm phải ép `shrink-0` và thu hẹp trên màn nhỏ: `lg:w-[15%] xl:w-[180px]`.
- **LUÔN LUÔN** chạy `./tailwindcss-linux-x64 -i ./src/input.css -o ./style.css --minify` sau khi thêm/sửa class cấu trúc, nếu không trình duyệt sẽ sử dụng cấu hình cũ gây lỗi giao diện.
- **Lưu ý:** Trang có sử dụng Object Cache / LiteSpeed Cache, phải dặn dò người dùng Purge Cache sau khi biên dịch CSS.

## 17. Đồng bộ Chiều cao Layout dạng Grid (Hero Section)
- **Vấn đề:** Khi sử dụng CSS Grid nhiều cột, nếu một cột chứa thẻ có tỷ lệ cứng (ví dụ Slider dùng `aspect-[16/9]`), cột đó sẽ không thể co giãn chiều cao theo cột dài nhất (ví dụ cột Sidebar). Kết quả là tạo ra khoảng trống hoặc đẩy lệch các thành phần (Tab menu) xuống dưới.
- **Giải pháp:** Xóa bỏ `aspect-[16/9]`. Cho thẻ bọc ngoài `flex-1 w-full relative` để ép giãn theo chiều cao của Grid row. Thẻ chứa ảnh bên trong dùng `absolute inset-0` và `object-cover`.

## 18. Tối ưu Trang Giỏ hàng (Cart)
- **Không dùng CSS đè mặc định:** Thay vì viết CSS `display:none` để sửa giao diện mặc định của WooCommerce, **phải** tạo file ghi đè template tại `woocommerce/cart/cart.php` để kiểm soát hoàn toàn HTML.
- **Layout 2 cột:** Sử dụng `flex-col lg:flex-row`.
- **Nút Số lượng (Quantity):** Tích hợp Javascript nội tuyến để submit form tự động khi bấm `+` hoặc `-`: 
  `document.querySelector('[name=update_cart]').disabled=false; document.querySelector('[name=update_cart]').click();`

## 19. Phân định Trách nhiệm: ACF Data Layer vs Theme Responsive Layout Layer
- **ACF chỉ là Data/Storage Layer:** Plugin ACF (Advanced Custom Fields) chỉ lưu trữ và trả về chuỗi/mảng giá trị (options, post meta) thông qua các hàm như `get_field()`, `the_field()`. ACF **tuyệt đối không** kiểm soát breakpoint màn hình, **không** can thiệp vào CSS hay quyết định phần tử hiển thị ra sao trên Mobile/Desktop.
- **Theme CSS/HTML là Layout & Responsive Layer:** Việc một phần tử ẩn/hiện, căn giữa hay sang ngang, chuyển từ dạng cột (flex-col) sang hàng (flex-row) hoàn toàn do HTML markup và CSS Media Queries của Theme phụ trách. Khi gặp lỗi "sửa mobile mà desktop bị ảnh hưởng" hoặc "desktop bị dính giao diện mobile", nguyên nhân là do thiếu class responsive Tailwind tương ứng trong file `style.css` tĩnh, cần dùng Media Query chuẩn (`@media (min-width: 900px)`) để định tuyến chuẩn xác.

## 20. Kỹ thuật Phân bổ Tỉ lệ Đều (Equal Height Proportions) Cột phụ Hero
- Khi một cột phụ (Aside) nằm cạnh một thành phần có chiều cao linh hoạt lớn (như Slider 16:9 / 4:3), tuyệt đối không để các card có chiều cao cứng rồi dùng `mt-auto` đẩy chân trang xuống đáy (gây ra khoảng trắng chết ở giữa).
- **Kỹ thuật chuẩn:**
  1. Container bọc các thẻ: `flex-1 flex flex-col justify-between gap-2 xl:gap-2.5 min-h-0`.
  2. Mỗi thẻ bên trong: `flex-1 min-h-0 flex items-center`.
  3. Icon: Luôn bổ sung `shrink-0` để ngăn trình duyệt bóp méo icon trên màn hình hẹp (900px - 1100px).
  4. Typography: Bổ sung `line-clamp-1` và font size thích ứng (`text-[12px] xl:text-[13px]`) kèm `min-w-0 flex-1`.
  5. Cụm chân cột: Gán `shrink-0` để giữ kích thước ổn định, chuyển các số hotline thành link `<a>` bấm gọi trực tiếp (`tel:...`).

## 21. Quy chuẩn Phân cấp Thị giác Giá Sản phẩm (Product Price Visual Hierarchy & CRO)
- **Sai lầm phổ biến:** Bọc khối giá trong `text-secondary font-black` và xuất `$product->get_price_html()`. Vì thẻ `<del>` (giá gốc) đứng trước thẻ `<ins>` (giá sale) và cả hai cùng thừa hưởng màu đỏ đậm, giá cũ bị gạch ngang nhưng vẫn to chói, lấn át giá bán thực tế. Khi co hẹp trên mobile, giá cũ rớt dòng lại nằm ở trên đầu giá mới.
- **Quy tắc phân cấp thị giác bắt buộc:**
  1. **Giá sau khuyến mãi (Sale Price):** Nằm đầu tiên, to nhất (`text-base xl:text-lg`), cực đậm (`font-black`), mang màu đỏ thương hiệu (`text-secondary` hoặc `text-red-600`).
  2. **Giá trước khuyến mãi (Regular Price):** Nằm sau giá bán, chìm xuống màu xám trung tính (`text-slate-400`), cỡ chữ nhỏ hơn (`text-xs xl:text-sm font-normal`), gạch ngang mờ (`line-through`). Tuyệt đối không để màu đỏ cạnh tranh thị giác với giá bán.
  3. **Tag & Badge giảm giá:** Tính toán phần trăm tiết kiệm cụ thể `-X%` đặt cạnh giá gốc và gắn badge góc trên ảnh sản phẩm (thay cho chữ "SALE" tĩnh chung chung).
  4. **Fallback ảnh sản phẩm:** Luôn dùng `wc_placeholder_img_src('woocommerce_thumbnail')` của WooCommerce thay vì đường dẫn file tĩnh không tồn tại.

## 22. Quy tắc Bảo toàn Banner Desktop khi Ẩn trên Mobile (Desktop-First Preservation)
- **Sai lầm gây lỗi mất banner desktop:** Thêm class `hidden` hoặc `hidden lg:flex` trực tiếp vào thẻ HTML của banner khi stylesheet chưa được biên dịch linh hoạt (BUG-029 & BUG-064). Trình duyệt sẽ ưu tiên class `.hidden` (`display: none`) và nuốt chửng khối nội dung ngay trên Desktop.
- **Quy chuẩn bắt buộc:**
  1. Thẻ HTML của phần tử Desktop luôn giữ cấu trúc hiển thị mặc định: `class="mi-product-row-banner lg:w-1/5 flex flex-col items-center ..."`. Tuyệt đối không thêm class `hidden`.
  2. Dùng CSS Media Query bọc riêng theo đúng Breakpoint của theme (`899px` tương ứng với breakpoint `@media (min-width: 900px)` của `omni-commerce`):
     ```css
     @media (max-width: 899px) {
         .mi-product-row-banner {
             display: none !important;
         }
     }
     ```
  3. Đặt CSS media query 1 lần duy nhất tại file template cha (`front-page.php`), không in lặp lại trong vòng lặp template part.

## 23. Quy chuẩn Kiến trúc Trang Chính sách & Văn bản Pháp lý (Policy Pages Architecture & Single H1 Rule)
- **Bố cục 2 cột chuyên nghiệp (Desktop Sticky Sidebar + Reader-Optimized Content):**
  1. **Bộ tứ chính sách nòng cốt:** `chinh-sach-bao-mat`, `chinh-sach-bao-hanh`, `chinh-sach-doi-tra`, `chinh-sach-van-chuyen`.
  2. **Cột trái (`lg:col-span-1`):** Sticky sidebar hiển thị menu điều hướng 4 chính sách có icon trực quan, trạng thái `is_active` (`bg-primary/10 text-primary font-bold shadow-xs`) kèm mũi tên chỉ thị, Box hotline hỗ trợ `0822.83.4444` và nút Tra cứu bảo hành.
  3. **Cột phải (`lg:col-span-3`):** Khung đọc bài viết tối ưu chiều dài dòng (không để trải rộng 1440px gây mỏi mắt), tích hợp Breadcrumbs (`Trang chủ > Chính sách & Quy định > [Tên chính sách]`), badge chứng nhận, metadata ngày cập nhật lần cuối, tình trạng hiệu lực và thời gian đọc ước tính.
  4. **Mobile (< 900px):** Tự động xếp chồng dọc mượt mà, các bảng biểu so sánh tự động cuộn ngang (`overflow-x: auto`) không gây vỡ khung hình.
- **Quy tắc Single H1 tuyệt đối (Tránh lỗi BUG-065):**
  - File `page.php` đã tự động render tiêu đề trang trong thẻ `<h1><?php the_title(); ?></h1>`.
  - Trong nội dung bài viết soạn thảo bằng Gutenberg hoặc HTML, **tuyệt đối không** chèn thêm thẻ `<h1>`. Tiêu đề các mục lớn bắt buộc bắt đầu từ `<h2>`, các mục con là `<h3>`. Đảm bảo mỗi trang chỉ có **duy nhất 1 thẻ H1** chuẩn Onpage SEO.
- **Đồng bộ hóa 3 địa chỉ thực tế của hệ thống:**
  - Cơ sở 1: Số 65 Hồ Ba Mẫu, Phường Phương Liên, Quận Đống Đa, Hà Nội
  - Cơ sở 2: Khu Sân bóng Hồ Văn Quán, Đường 19/5, KĐT Văn Quán, Quận Hà Đông, Hà Nội
  - Cơ sở 3: Kho 86, Cảng Hà Nội, Số 838 Bạch Đằng, Phường Thanh Lương, Quận Hai Bà Trưng, Hà Nội




