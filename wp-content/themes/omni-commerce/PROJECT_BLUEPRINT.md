# Project Intelligence Blueprint

## 1. Mục tiêu & triết lý

### 1.1. Nguyên tắc cốt lõi
- YAGNI: không thêm plugin hoặc framework phức tạp cho tính năng nhỏ; ưu tiên giải pháp PHP/WordPress thuần.
- Classic Theme: không sử dụng Gutenberg, FSE, Page Builder cho layout chính.
- Tailwind CLI first: xây dựng UI bằng class Tailwind theo `tailwind.config.js`, biên dịch qua CLI thay vì phụ thuộc tooling khác; không inline CSS không cần thiết.
- ACF dynamic: dữ liệu phải đọc qua `get_field()`, `have_rows()`, không hardcode text nhầm nơi.
- Prefix `mi_`: mọi hàm tự định nghĩa phải có tiền tố `mi_`.
- Escape output: mọi output phải qua `esc_html()`, `esc_attr()`, `esc_url()`.
- Fallback must be real: nếu dữ liệu rỗng, phải render dữ liệu giả hoặc UI chuẩn, không để trắng hoặc text lỗi.

### 1.2. Yêu cầu môi trường
- Hạ tầng 100% Docker.
- Không chạy `php`, `wp`, `mysql` trực tiếp ngoài container.
- Luôn kiểm tra cache / purge khi đổi CSS.

---

## 2. Stack & kiến trúc dự án

### 2.1. Stack chuẩn
- WordPress + WooCommerce
- MariaDB
- Redis
- ACF Pro / custom fields
- Theme classic PHP
- TailwindCSS
- Docker-based environment

### 2.2. Kiến trúc theme chuẩn
```text
theme/
├── front-page.php
├── header.php
├── footer.php
├── functions.php
├── page.php
├── style.css
├── tailwind.config.js
├── src/
│   └── input.css
├── template-parts/
│   └── home/
│       ├── hero.php
│       ├── media-services.php
│       ├── product-row.php
│       └── news.php
├── woocommerce/
│   ├── archive-product.php
│   ├── content-product.php
│   ├── single-product.php
│   ├── cart/
│   └── global/
├── ai-docs/
└── docs/
```

### 2.3. Template mapping
- `front-page.php` → điều phối homepage
- `woocommerce/archive-product.php` → danh mục sản phẩm
- `woocommerce/single-product.php` → chi tiết sản phẩm
- `page.php` → trang tĩnh, bắt buộc có layout khung `max-w-[1440px] mx-auto`

---

## 3. Luồng triển khai chuẩn cho dự án mới

### 3.1. Mẫu workflow khởi đầu
1. Xác định mô hình business: thương mại điện tử, nội dung, landing page, service, portal.
2. Xác định stack và cấu trúc theme classic.
3. Thiết kế homepage theo trình tự bắt buộc:
   - `get_header()`
   - `hero.php`
   - `media-services.php`
   - `#product-row-section` (3 danh mục hoặc block tương đương)
   - `news.php`
   - `get_footer()`
4. Kiểm tra menu, navigation, fields ACF, custom post types.
5. Thiết kế template WooCommerce nếu cần.
6. Mỗi section phải có `else` fallback rõ ràng khi data rỗng.
7. Compile Tailwind sau khi thêm class mới.

### 3.2. Thứ tự homepage bắt buộc
```php
get_header();
get_template_part('template-parts/home/hero');
get_template_part('template-parts/home/media-services');
// product row section
get_template_part('template-parts/home/news');
get_footer();
```

> Yếu tố cấm: gọi trust badge riêng từ `front-page.php` nếu `footer.php` đã chứa pre-footer badges.

---

## 4. Frontend rules (Tailwind & layout)

### 4.1. Tailwind CLI
- Dùng class theo `tailwind.config.js`.
- Không phát minh màu mới nếu chưa có trong HTML mẫu.
- Compile bắt buộc bằng Tailwind CLI sau khi sửa class hoặc cấu hình:
```bash
./tailwindcss-linux-x64 -i ./src/input.css -o ./style.css --minify
```
- Nếu sửa cấu trúc class, phải compile lại trước khi QA để browser không dùng CSS cũ.
- Quy ước dự án: mọi UI phải được viết bằng class Tailwind, còn CSS bổ sung chỉ dùng khi thật sự cần thiết và được giữ tối giản.

### 4.2. Layout hàng đầu
- Header phải `sticky top-0 z-50 shadow-sm transition-all duration-300`.
- Bắt buộc CSS tối thiểu:
```css
.main-nav-header {
  display: flex !important;
  visibility: visible !important;
  opacity: 1 !important;
}
```
- Không ẩn menu bằng `hidden lg:flex` nếu mục đích là layout sticky.

### 4.3. Layout toàn trang
- Giữ ranh giới content `max-w-[1440px] mx-auto` cho phần thân và footer.
- Header có background full width nhưng nội dung trong container như logo, topbar, search phải được gói trong max-width chuẩn.

### 4.4. Responsive product-first
- Mobile ưu tiên sản phẩm hơn nội dung phụ.
- Dùng `hidden md:block` / `hidden lg:flex` để ẩn block gây nhiễu.
- Có thể dùng `order-2 lg:order-1` để đẩy phần ưu tiên cao lên trên.
- Banner dọc trên mobile nên giảm chiều cao: `min-h-[160px] lg:min-h-[340px]`.

### 4.5. Micro-interactions chuẩn
- Hover: `hover:-translate-y-1`, `hover:shadow-[...]`
- Active: `active:scale-95`
- Cart badge scale-up 300ms khi cập nhật
- Trust badge và button phải có transition rõ ràng

---

## 5. WooCommerce & template chuyên biệt

### 5.1. Archive / Category page
- Layout 2 phần: sidebar + grid sản phẩm.
- Sidebar đúng: `w-full md:w-1/3 lg:w-1/4 shrink-0`
- Grid sản phẩm: `grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4`
- Không dùng `hidden` cứng trên sidebar để tránh mất layout.
- Không dùng `lg:grid-cols-4` nếu sidebar đang chiếm 25% không gian.

### 5.2. Product card
- Card sản phẩm có badge, giá, rating, nút thao tác.
- Không hardcode text; lấy dữ liệu từ WooCommerce / ACF / taxonomy.

### 5.3. Single product
- Gallery, giá & khuyến mãi, CTA chính.
- Mua ngay: AJAX add to cart → modal cảm ơn → user tiếp tục mua hoặc checkout.
- Validation biến thể: nếu thiếu `variation_id`, scroll đến khung option và báo lỗi.

### 5.4. Cart
- Không dùng CSS `display:none` để sửa giao diện mặc định; ưu tiên override template `woocommerce/cart/cart.php`.
- Layout 2 cột: `flex-col lg:flex-row`.
- Quantity buttons nên tích hợp auto-submit khi đổi số lượng.

---

## 6. ACF field map & menu rules

### 6.1. ACF field map chuẩn
| Vị trí | Field name đúng | Sai phổ biến |
|--------|----------------|--------------|
| Topbar text | `mi_hf_topbar_text` | `mi_header_topbar_text` |
| Địa chỉ topbar | `mi_hf_topbar_address` | `mi_hero_address_text` |
| Logo header | `mi_hf_logo` | `mi_header_logo` |
| Hotline | `mi_hotline_number` | `mi_hero_hotline_text` |
| FAB hotline | `mi_hf_fab_hotline` | |
| FAB Zalo | `mi_hf_fab_zalo` | |
| FAB Messenger | `mi_hf_fab_messenger` | |

### 6.2. Menu cấp 1 chuẩn
- Render qua `mi_render_header_navigation(false)` desktop và mobile tương ứng.
- Có fallback 5 mục chuẩn khi menu WP chưa được cấu hình.
- Tra cứu bảo hành là mục bắt buộc, không tự ý xóa.

### 6.3. Menu cấp 2 category bar
- Nằm trong `woocommerce/archive-product.php`.
- Dropdown và hover cần dùng CSS thuần, không JS.

---

## 7. Homepage section template chuẩn

### 7.1. Hero
- 3 cột: features + slider + sidebar.
- Không dùng `aspect-[16/9]` nếu layout grid cần co giãn chiều cao.
- Nên dùng wrapper `flex-1 w-full relative` + ảnh `absolute inset-0 object-cover`.

### 7.2. Media services
- Marquee vô hạn theo kỹ thuật dua text, không dùng logic phá vỡ khoảng trắng.
- 3 video hoặc 3 service card theo mô hình chuẩn.

### 7.3. Product row
- Chuẩn: 3 danh mục tại homepage, mỗi row có banner màu riêng.
- Duyệt mảng category rõ ràng, truyền `$args` cho `get_template_part`.

### 7.4. News
- Grid 4 item hoặc tương đương.
- Dữ liệu từ `WP_Query` hoặc ACF custom fields nếu có.

---

## 8. CSS / JS pattern bắt buộc

### 8.1. Marquee CSS chuẩn
```css
@keyframes marquee {
  0% { transform: translateX(0); }
  100% { transform: translateX(-50%); }
}

.mi-marquee-text {
  display: flex;
  flex-shrink: 0;
  min-width: 100%;
  animation: marquee 20s linear infinite;
}
```

### 8.2. Dropdown arrow CSS
```html
<ul class="... before:absolute before:-top-[8px] before:left-1/2 before:-translate-x-1/2 before:border-[8px] before:border-transparent before:border-b-white">
```

### 8.3. AJAX add-to-cart zero-endpoint
```js
const formData = new FormData(form);
const res = await fetch(window.location.href, {
  method: 'POST',
  body: formData,
});
const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
document.querySelector('.cart-contents-count').innerHTML =
  doc.querySelector('.cart-contents-count').innerHTML;
```

### 8.4. Event handling gallery
- `e.stopPropagation()` + `e.preventDefault()` cho nút gallery
- Popup zoom phải nằm bên phải ảnh, không đè lên toàn canvas
- Không dùng `transform: scale` đè lên ảnh gốc

---

## 9. Anti-patterns phải tránh

### 9.1. CSS / layout
- Không dùng inline CSS lạ khi Tailwind đã có class phù hợp.
- Không tạo section trắng vì data trống.
- Không để `overflow-hidden` vô nghĩa làm mất layout.
- Không giữ CSS rác của plugin khác để che lỗi.

### 9.2. PHP / WordPress
- Không hardcode text trong template nếu đã có ACF / taxonomy.
- Không sử dụng `while ( have_rows('...') )` mà thiếu truyền `$args` đúng.
- Không dùng `has_nav_menu()` như dấu hiệu đủ điều kiện; phải kiểm tra menu items thực tế.
- Không bỏ qua `esc_*` khi output data.

### 9.3. UX / content
- Không để mobile rơi vào các block nội dung phụ quá nhiều.
- Không đẩy banner dọc chiếm cả viewport điện thoại.
- Không để footer tràn 100% width trên màn 4K.

---

## 10. Dự án mới: checklist khởi động

### 10.1. Checklist kiến trúc
- [ ] Xác định stack và môi trường Docker
- [ ] Chuẩn hóa theme classic + template hierarchy
- [ ] Khai báo menu / fallback / category
- [ ] Tạo ACF field map và naming convention
- [ ] Xác định homepage sections + thứ tự
- [ ] Cấu hình WooCommerce if needed

### 10.2. Checklist frontend
- [ ] Khởi tạo Tailwind config
- [ ] Biến màu / spacing / typography theo chuẩn
- [ ] Thiết kế mobile-first
- [ ] Compile CSS sau khi sửa
- [ ] Xác minh sticky header / FAB / menu hover

### 10.3. Checklist QA
- [ ] Không white section khi data rỗng
- [ ] Không lỗi layout trên mobile
- [ ] Không mất định dạng `the_content()`
- [ ] Không hardcode text sai field name
- [ ] Cache đã purge sau khi compile CSS

---

## 11. Góc nhìn triển khai cho bất kỳ dự án mới nào

### 11.1. Nguyên lý phát triển
- Thỏa mãn business requirement trước, nhưng tối ưu cho maintainability.
- Mỗi component phải có fallback rõ ràng, không phụ thuộc hoàn toàn vào database.
- Mỗi source data phải có một nơi chuẩn để đọc và xử lý.
- Mỗi page phải chạy trên mobile-first và desktop đồng thời.

### 11.2. Mẫu khởi tạo nhanh
```php
// 1. Khai báo dữ liệu / ACF
$items = get_field('mi_section_items');

// 2. Nếu rỗng thì render fallback UI chuẩn
if ( ! empty( $items ) ) {
    // render dynamic content
} else {
    // render fallback mock layout
}

// 3. Trả output an toàn
echo esc_html( $title );
```

---

## 12. Kết luận

Blueprint này là một “bộ khung tri thức khởi nghiệp” cho mọi dự án WordPress/ WooCommerce mới. Nó không chỉ là hướng dẫn kỹ thuật, mà còn là bộ quy chuẩn để giữ tính nhất quán, giảm bug, và tăng khả năng scale. Khi dự án mới bắt đầu, hãy luôn bắt đầu từ đây: triết lý, kiến trúc, layout, ACF, UX, và QA.
