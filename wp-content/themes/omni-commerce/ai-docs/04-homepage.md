# Tri thức sâu — Trang chủ (Homepage Architecture)

## Luồng component (bất biến)
```
get_header()
  └─ template-parts/home/hero.php
  └─ template-parts/home/media-services.php
  └─ #product-row-section (front-page.php)
       ├─ product-row.php [Tivi Xiaomi]
       ├─ product-row.php [Tủ Lạnh Xiaomi]
       └─ product-row.php [Thiết Bị Gia Đình]
  └─ template-parts/home/news.php
get_footer()
  └─ Pre-Footer Trust Badges  ← NẰM TRONG footer.php (lines 3–47), KHÔNG gọi riêng
  └─ Main Footer 4 cột
  └─ FAB (Floating Action Bar)
```

---

## hero.php — Layout 3 cột

| Cột | Nội dung | ACF field |
|-----|---------|-----------|
| Trái | Feature list + Địa chỉ + Hotline | `mi_hero_features`, `mi_hero_address_text`, `mi_hotline_number` |
| Giữa | Hero Slider + Tab menu cuộn ngang | `mi_hero_slider` / 3 banner fields + `mi_hero_tabs` |
| Phải | Banner mini + 2 tin tức mới nhất | `mi_hero_sidebar` + `WP_Query(post, 2)` |

### Cột trái (Features, Địa chỉ & Hotline) — Quy tắc Co giãn & Bố cục
1. **Chỉ hiển thị trên Desktop (`>= 900px`):** Dùng class `.mi-hero-secondary` với rule `@media (max-width: 899px) { display: none !important; }` để ẩn hoàn toàn trên Mobile/Tablet dọc, tránh gây nhiễu luồng mua sắm chính.
2. **Co giãn theo chiều cao Slider (Equal Height Proportions):**
   - Container 4 thẻ cam kết: `flex-1 flex flex-col justify-between gap-2 xl:gap-2.5 mb-2.5 min-h-0`.
   - Mỗi thẻ cam kết: `flex-1 min-h-0 flex items-center`. Nhờ đó 4 thẻ tự động kéo giãn tỉ lệ đều đặn theo chiều cao của slider giữa, **triệt tiêu hoàn toàn khoảng trắng chết**.
3. **Bảo vệ hiển thị trên màn hình Desktop nhỏ (900px - 1100px):**
   - Icon Material Symbols bắt buộc có `shrink-0` để không bị méo.
   - Chữ tiêu đề và mô tả có `min-w-0 flex-1 line-clamp-1` cùng font size thích ứng (`text-[12px] xl:text-[13px]`, `text-[10px] xl:text-[11px]`).
4. **Nút Hotline tương tác trực tiếp:**
   - Cố định ở đáy với `shrink-0`.
   - Nút Hotline được cấu trúc dạng thẻ `<a href="tel:...">` với hover micro-interaction (`group-hover:scale-105 transition-transform`).

### Slider — Thứ tự ưu tiên
1. `mi_home_main_banner` / `mi_home_sub_banner_1` / `mi_home_sub_banner_2`
2. `mi_hero_slider` (repeater: image + link)
3. Static placeholder images (fallback cuối)

### Hotline field đúng
```php
// ĐÚNG — Hỗ trợ fallback giữa mi_hotline_number và mi_hero_hotline_text
$hotline_text = get_field('mi_hotline_number', 'option') ?: get_field('mi_hero_hotline_text', 'option') ?: '0822.83.4444';

// SAI — Chỉ đọc 1 field không có fallback
$hotline_text = get_field('mi_hero_hotline_text', 'option');
```

### Tab menu cuộn ngang
- Scroll auto khi không hover (`setInterval` với `scrollLeft += 0.5`)
- Đảo chiều khi chạm cuối (`scrollDirection = -1`)
- Dừng khi hover / touchstart

---

## media-services.php

| Khối | Nội dung | ACF |
|------|---------|-----|
| Marquee | Sale banner chạy vô tận | `mi_videos_sale_banner` |
| Video grid | 3 YouTube embed | `mi_video_1/2/3` → `maxresdefault.jpg` |
| Value bar | 3 cam kết dịch vụ | `mi_value_props` (repeater: icon, title, desc) |

**Marquee:** Kỹ thuật duplicate text (xem `02-frontend-rules.md §4`)

---

## product-row.php — Anti-Duplication Knowledge

### Query sản phẩm (thứ tự ưu tiên)
1. `tax_query` theo `product_cat` với `term_id` từ `$args['product_cat']`
2. Fallback: `search_keywords` đặc thù từng danh mục:
   - Tivi: `['tivi', 'tv', 'redmi']`
   - Tủ Lạnh: `['tủ lạnh', 'tu lanh', '430l']`
   - Thiết bị gia đình: `['robot', 'hút bụi', 'máy lọc', 'quạt', 'nồi']`
3. Nếu vẫn rỗng: `WP_Query(['post__in' => [0]])` — **CẤM** query không điều kiện

**CẤM** `posts_per_page=8` không có `tax_query` → Tivi xuất hiện tràn sang hàng Tủ Lạnh.

### $args — các key bắt buộc
```php
// product-row.php nhận từ front-page.php
$product_cat     = $args['product_cat'];     // term_id (int)
$cat_title       = $args['cat_title'];       // "Tivi Xiaomi"
$cat_link        = $args['cat_link'];        // URL danh mục
$banner_title    = $args['banner_title'];    // HTML (có <br/>)
$banner_desc     = $args['banner_desc'];     // Plain text
$banner_img      = $args['banner_img'];      // URL ảnh
$banner_btn_text = $args['banner_btn_text']; // "MUA NGAY"
$banner_btn_link = $args['banner_btn_link']; // URL
$banner_bg_color = $args['banner_bg_color']; // "from-xiaomi-yellow to-tertiary-fixed"
$search_keywords = $args['search_keywords']; // array
// get_sub_field() là LEGACY FALLBACK — không dùng trong luồng mới
```

### Cột Banner Dọc & Responsive Layout (Desktop-First Preservation)
1. **Desktop (`>= 900px`):** Cột banner dọc bên trái (`lg:w-1/5`) hiển thị đầy đủ 100% kèm ảnh sản phẩm lớn và nút Mua ngay. Giữ cấu trúc `class="mi-product-row-banner lg:w-1/5 flex flex-col ..."`, tuyệt đối không dùng class `hidden` hay `lg:flex`.
2. **Mobile (`< 900px`):** Ẩn hoàn toàn cột banner bằng `@media (max-width: 899px) { .mi-product-row-banner { display: none !important; } }`. Khối lưới sản phẩm bên phải nhận `w-full lg:w-4/5 grid grid-cols-2 md:grid-cols-4 gap-3`, tự động bung rộng 100% full-width màn hình 2 cột.

### Chuẩn hóa Card sản phẩm trang chủ (Visual Hierarchy & Social Proof)
1. **Giá bán & Giảm giá:**
   - Giá sau khuyến mãi: Hiển thị đầu tiên, to nhất (`text-base xl:text-lg`), cực đậm (`font-black text-secondary`).
   - Giá trước khuyến mãi: Hiển thị sau, màu xám (`text-slate-400 line-through text-xs xl:text-sm font-normal`).
   - Tag giảm giá: Hiển thị `-X%` cạnh giá cũ và badge góc trên ảnh.
2. **Cụm Đánh giá sao & Lượt bán phong cách Shopee / Lazada:**
   - Dùng `mt-auto pt-2.5` để căn gióng hoàn hảo mép đáy của tất cả card trong cùng một hàng.
   - Bên trái: 5 ngôi sao Material Symbols vàng (`text-xiaomi-yellow`) + số lượt đánh giá `(X)` màu xám.
   - Bên phải: `Đã bán X` (hoặc `1.3k` nếu >= 1000) với `whitespace-nowrap shrink-0 text-slate-500`.

---

## news.php

- Query: `WP_Query(['post_type' => 'post', 'posts_per_page' => 4])`
- Tiêu đề: `border-l-4 border-secondary pl-3` (nhất quán các section)
- Fallback: **đúng 4 mock cards** (khớp `grid-cols-4`)
- Card link: thẻ `<a>` với `the_permalink()`; ảnh: `the_post_thumbnail('medium')` hoặc placeholder

---

## Homepage Premium Catalog Rules

### 1) Nhịp khoảng cách phải có logic, không phải “bừa bộn”
- Section nào cũng cần một nhịp spacing rõ: nhóm nội dung, khoảng cách giữa block, CTA cuối cùng phải có “điểm dừng”.
- Không để hero / product-row / news ngăn cách quá xa làm mất cảm giác catalog chuyên nghiệp.
- Nếu khoảng cách tăng thì phải do hierarchy, không phải do dàn trải không có mục tiêu.

### 2) Hierarchy phải rõ: ưu tiên sản phẩm > thông tin phụ
- Ảnh sản phẩm phải là điểm kéo mắt chính.
- Tiêu đề sản phẩm nên rõ, ngắn, có trọng lượng chữ mạnh hơn nội dung phụ.
- Giá, badge sale, stock và CTA phải nhìn thấy ngay trong lần quét đầu tiên.

### 3) Card phải “đọc được” trong 1 giây
- Card không được quá nặng chữ, không đặt nhiều line dài, không làm tiêu đề quá dài hệt body text.
- Border, rounded, shadow phải hài hòa; không “đẹp” bằng cách thêm quá nhiều hiệu ứng.
- Màu nền card nên tối giản; chỉ dùng accent color ở điểm trọng tâm.

### 4) Section title phải thống nhất và rõ ràng
- Tất cả section heading nên cùng hướng: border left, uppercase hoặc title case, trọng lượng mạnh, nhịp kích thước đồng đều.
- Không để title quá lớn hoặc quá mỏng so với block bên dưới.
- Title phải thể hiện brand và category mạnh hơn là chỉ là plain text.

### 5) Không để homepage bị “riêng rẽ từng block”
- Mỗi block phải cảm giác cùng một hệ thống design language.
- Shared tokens: radius, border, shadow, spacing, heading scale, button style.
- Nếu mỗi section khác hẳn kiểu chữ, màu và shadow thì homepage sẽ mất tính premium ngay.

### 6) Logic mới: UI cuối cùng sau khi logic đã ổn
- Trước khi làm đẹp, phải chắc chắn section đang render đúng source data và fallback logic.
- Sau đó mới tinh chỉnh nhịp spacing, độ nổi, border, CTA, hover, hierarchy.
- Không chỉnh UI bằng cách phá vỡ ACF, query hay fallback.

### 7) Fallback phải đủ đẹp nhưng không lộ vẻ “mẫu”
- Nếu không có dữ liệu thực, fallback vẫn nên gọn, đồng nhất và chuyên nghiệp.
- Mock content phải có độ hoàn chỉnh tương đương một catalog thật, không bị “đẹp sai kiểu” hay quá lạ.

### 8) Premium catalog check trước khi merge
- Cần kiểm tra 5 điểm: section rhythm, hierarchy, density, brand consistency, mobile coherence.
- Homepage chỉ gọi là “đẹp” khi khách hàng nhìn vào cũng hiểu ngay mục tiêu của từng block mà không bị rối mắt.

---

## footer.php — Cấu trúc đầy đủ

### Pre-Footer Trust Badges (lines 3–47)
4 badge cố định hoặc từ ACF `mi_trust_badges`:
- 100% Chính hãng (`verified_user`)
- Giao hàng siêu tốc (`local_shipping`)
- Bảo hành tận tâm (`build`)
- Đổi trả 7 ngày (`published_with_changes`)

### Main Footer 4 cột
| Cột | Nội dung | ACF |
|-----|---------|-----|
| 1 | Giới thiệu + Social | `mi_hf_footer_about`, `mi_hf_fab_messenger`, `mi_hf_fab_zalo` |
| 2 | Chính sách (menu `footer-policy`) | fallback: 4 link tĩnh |
| 3 | Fanpage Facebook embed | `mi_hf_footer_facebook_html` |
| 4 | Thanh toán + Chứng nhận | `mi_hf_footer_payment_icons`, `mi_hf_footer_certificate` |

### Address Bar
- Danh sách Showroom: `mi_hf_footer_showrooms` hoặc 3 cơ sở tĩnh chính thức:
  1. Trụ sở & Showroom Đống Đa: Số 65 Hồ Ba Mẫu, Phường Phương Liên, Quận Đống Đa, Hà Nội
  2. Cơ sở Văn Quán - Hà Đông: Khu Sân bóng Hồ Văn Quán, Đường 19/5, KĐT Văn Quán, Quận Hà Đông, Hà Nội
  3. Tổng Kho Cảng Hà Nội - Hai Bà Trưng: Kho 86, Cảng Hà Nội, Số 838 Bạch Đằng, Phường Thanh Lương, Quận Hai Bà Trưng, Hà Nội

### FAB (Floating Action Bar)
- Hotline: `tel:` link, màu đỏ `#e60012`
- Messenger: `mi_hf_fab_messenger`, màu xanh `#0084ff`
- Zalo: `mi_hf_fab_zalo`, màu xanh `#0068ff`
- Showroom: `home_url('/lien-he/')`, màu xanh lá `#00a651`
- Back to Top: ẩn khi `scrollY < 300`, hiện khi cuộn

---

## header.php — Thành phần & ACF fields

| Thành phần | ACF field đúng | Ghi chú |
|-----------|---------------|---------|
| Topbar text | `mi_hf_topbar_text` | |
| Địa chỉ topbar | `mi_hf_topbar_address` | |
| Hotline topbar | `mi_hotline_number` | |
| Logo | `mi_hf_logo` | Fallback: SVG Xiaomi Store cam |
| Navigation | `mi_render_header_navigation()` | |
| Cart URL | `wc_get_cart_url()` | |
| Cart count | `WC()->cart->get_cart_contents_count()` | |
