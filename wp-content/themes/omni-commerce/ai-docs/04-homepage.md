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

### Slider — Thứ tự ưu tiên
1. `mi_home_main_banner` / `mi_home_sub_banner_1` / `mi_home_sub_banner_2`
2. `mi_hero_slider` (repeater: image + link)
3. Static placeholder images (fallback cuối)

### Hotline field đúng
```php
// ĐÚNG
$hotline_text = get_field('mi_hotline_number', 'option') ?: '0822.83.4444';

// SAI — field không tồn tại trong ACF group nào
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
- Danh sách Showroom: `mi_hf_footer_showrooms` hoặc 3 showroom tĩnh (Thanh Xuân, Long Biên, Cầu Giấy)

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
