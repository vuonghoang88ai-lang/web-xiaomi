# Nhật ký Bug & Anti-patterns

*Cập nhật lần cuối: 2026-09-22 — Đúc kết từ 2 vòng audit trang chủ + 3 vòng audit single-product.php*

---

## Danh sách Bug đã ghi nhận

### [BUG-001] Lỗi Sidebar đè nội dung trên Mobile (Đã cập nhật theo BUG-026)
**File:** `woocommerce/archive-product.php`
**Nguyên nhân cũ:** Từng bỏ `hidden lg:block` do lỗi Display Scaling. Nhưng khi có Mobile Drawer, việc bỏ `hidden` khiến Sidebar cứng trồi lên chiếm hết Mobile UX.
**Fix MỚI NHẤT:** BẮT BUỘC dùng **CSS Media Query thuần** (tham khảo `BUG-026`) để ẩn `<aside>` cứng trên màn hình nhỏ. Không dùng class Tailwind `hidden lg:block` vì nguy cơ trình duyệt lưu cache file CSS cũ khiến Sidebar biến mất luôn trên Desktop. Mobile/Tablet sẽ dùng nút "LỌC SẢN PHẨM" để mở Drawer. Tuyệt đối không để Sidebar cứng và Drawer cùng hiển thị trên Mobile.

### [BUG-002] Menu header rỗng khi gán menu chưa có item
**File:** `header.php`, `functions.php`
**Nguyên nhân:** Chỉ kiểm tra `has_nav_menu()` mà không kiểm tra số item thực tế.
**Fix:**
```php
$menu_items = wp_get_nav_menu_items( get_nav_menu_locations()['header-menu'] ?? 0 );
if ( ! empty( $menu_items ) ) { wp_nav_menu([...]); }
else { /* Fallback 5 mục chuẩn */ }
```

### [BUG-003] 3 hàng sản phẩm đều hiển thị "Tivi Xiaomi"
**File:** `front-page.php`, `template-parts/home/product-row.php`
**Nguyên nhân:** `while(have_rows('mi_home_product_row')) { get_template_part('product-row'); }` không truyền `$args`. ACF fallback về `default_value` cho tất cả hàng.
**Fix:** Duyệt mảng `$home_categories` cứng, truyền `$row_args` tường minh qua param thứ 3.

### [BUG-004] Marquee có khoảng trắng trên màn hình rộng
**File:** `template-parts/home/media-services.php`
**Nguyên nhân:** `padding-left: 100%` + `translateX(100% → -100%)` — 1 bản text, màn hình rộng thấy khoảng trống.
**Fix:** Duplicate text + `translateX(0 → -50%)` (xem `02-frontend-rules.md §4`).

### [BUG-005] Hotline không lấy được từ ACF Options
**File:** `template-parts/home/hero.php`
**Nguyên nhân:** `get_field('mi_hero_hotline_text')` — field không khai báo trong ACF group nào.
**Fix:** `get_field('mi_hotline_number', 'option') ?: '0822.83.4444'`.

### [BUG-006] Logo, Topbar text, Địa chỉ Admin cấu hình không có tác dụng
**File:** `header.php`
**Nguyên nhân:** 3 field name sai:

| Sai | Đúng |
|-----|------|
| `mi_header_topbar_text` | `mi_hf_topbar_text` |
| `mi_hero_address_text` | `mi_hf_topbar_address` |
| `mi_header_logo` | `mi_hf_logo` |

### [BUG-007] Trust Badges bị nhân đôi trên trang chủ
**File:** `front-page.php`
**Nguyên nhân:** Gọi `get_template_part('trust-badges')` từ `front-page.php` trong khi `footer.php` đã có Pre-Footer Trust Badges (lines 3–47).
**Fix:** Xóa lệnh gọi khỏi `front-page.php`. `footer.php` là nguồn duy nhất.

### [BUG-008] Fallback news chỉ có 1 card / grid-cols-4
**File:** `template-parts/home/news.php`
**Nguyên nhân:** Copy-paste 1 mock card, quên đếm số cột grid.
**Fix:** Cung cấp đúng 4 mock cards khớp `grid-cols-4`.

### [BUG-009] Duplicate filter `woocommerce_catalog_orderby`
**File:** `functions.php`
**Nguyên nhân:** 2 hàm khác nhau đăng ký cùng filter — behavior không đoán được.
**Fix:** Chỉ giữ `mi_custom_catalog_orderby` (bản mới), xóa `mi_custom_woocommerce_catalog_orderby`.

### [BUG-010] Syntax Error / Lỗi 500 khi Replace Content
**File:** Bất kỳ file PHP nào
**Nguyên nhân:** `TargetContent` quá ngắn/phổ biến → bắt nhầm dòng → cắt đứt hàm PHP.
**Fix:** `TargetContent` phải **unique** trong file. Bao gồm thẻ đóng/mở HTML hoặc comment đánh dấu khối. Không bao giờ để hàm PHP ở trạng thái chưa đóng ngoặc.

### [BUG-011] `get_the_title()` không tự escape
**File:** `template-parts/home/product-row.php` và nhiều nơi khác
**Nguyên nhân:** `the_title()` và `get_the_title()` không escape output.
**Fix:** `echo esc_html( get_the_title() )`.

### [BUG-012] ACF `have_rows()` không reset con trỏ nếu gọi lần 2
**File:** `woocommerce/single-product.php`
**Nguyên nhân:** Gọi cùng `while(have_rows(...))` hai lần → lần 2 không trả về data.
**Fix:** `ob_start()` buffer lần 1, `echo` lại ở vị trí 2 (xem `03-backend-wpcs.md §2`).

### [BUG-013] Variable Product: giá hiển thị sai / lỗi 0₫ (Vi phạm dây chuyền)
**File:** `woocommerce/content-product.php`, `woocommerce/single-product.php`, `template-parts/home/product-row.php`
**Nguyên nhân:** Dùng chung `$product->get_price_html()` hoặc gọi `get_sale_price()` trực tiếp mà không check loại sản phẩm, dẫn tới lỗi giá 0₫ hoặc range giá không mong muốn.
**Fix chuẩn:**
```php
if ( $product->is_type('variable') ) {
    $price = $product->get_variation_sale_price('min', true) ?: $product->get_variation_regular_price('min', true);
    echo $price > 0 ? wp_kses_post( wc_price( $price ) ) : esc_html__( 'Liên hệ', 'omni-commerce' );
} else {
    $price = $product->get_price();
    echo $price > 0 ? wp_kses_post( $product->get_price_html() ) : esc_html__( 'Liên hệ', 'omni-commerce' );
}
```

### [BUG-014] Dead code JS — flag.value không được PHP đọc
**File:** `woocommerce/single-product.php`
**Nguyên nhân:** Tạo DOM element nhưng không có API backend xử lý giá trị.
**Fix:** Xóa dead code. Form chưa có API: chỉ giữ `event.preventDefault()` + reset UI.

### [BUG-015] Tên danh mục sai case → tạo duplicate WooCommerce terms
**File:** `functions.php` vs `front-page.php`
**Nguyên nhân:** `'Tủ lạnh Xiaomi'` (functions.php) ≠ `'Tủ Lạnh Xiaomi'` (front-page.php).
**Fix:** Chuẩn Title Case: `'Tivi Xiaomi'`, `'Tủ Lạnh Xiaomi'`, `'Thiết Bị Gia Đình'`.

### [BUG-016] Homepage spacing drift → mất cảm giác catalog premium
**File:** `front-page.php`, `template-parts/home/hero.php`, `template-parts/home/media-services.php`, `template-parts/home/product-row.php`, `template-parts/home/news.php`
**Nguyên nhân:** Mỗi block được “sửa riêng” mà không thống nhất nhịp spacing và hierarchy chung, dẫn đến homepage rời rạc về mặt thẩm mỹ.
**Fix:** Áp dụng chuẩn nhịp spacing đồng nhất, hierarchy rõ, title section đồng bộ, card sản phẩm/tin tức có độ nổi và khoảng cách hợp lý, đồng thời vẫn giữ nguyên logic dữ liệu và fallback.

**Rule mới:**
```
Homepage Premium Catalog Rule
1. Logic phải ổn định trước khi polish UI.
2. Mỗi section phải có nhịp spacing rõ ràng, không dàn trải quá xa.
3. Hierarchy ưu tiên: hero/banner > product cards > info phụ > CTA phụ.
4. Section title phải cùng hệ thống type scale và border style.
5. Card phải dễ đọc trong 1 giây: hình ảnh nổi bật, tiêu đề rõ, giá dễ nhìn, badge theo cấp độ quan trọng.
6. Không lạm dụng màu sắc, border, shadow trong cùng một block.
7. Fallback vẫn phải đẹp và đồng nhất ngay cả khi dữ liệu chưa có.
8. Cuối cùng kiểm tra đồng bộ desktop/mobile; nếu section đẹp ở một màn hình nhưng xấu ở màn hình còn lại, chưa được merge.
```

### [BUG-017] Page Tin tức: shared `have_posts()` loop làm mất dữ liệu giữa featured, list và sidebar
**File:** `page-tin-tuc.php`
**Nguyên nhân:** Dùng chung một `have_posts()` / `the_post()` cho nhiều block trong cùng page, dẫn đến dữ liệu bị “consume” sau vòng lặp đầu, khiến featured hoặc list rỗng.
**Fix:** Dùng `WP_Query` riêng cho từng nhánh dữ liệu:
- `featured_query` cho hero và mini cards ở page 1
- `list_query` cho danh sách bài viết chính
- `popular_query` cho sidebar “Tin xem nhiều”
Sau mỗi query gọi `wp_reset_postdata()` nếu cần. Không tái sử dụng `have_posts()` của page global cho nhiều section khác nhau.

**Rule mới:**
```
Query partitioning for page templates
1. Một page không nên dùng chung query global cho nhiều section.
2. Featured, list, sidebar phải có query riêng.
3. Page 1: featured + list; Page > 1: list only.
4. Sau khi dùng `WP_Query`, luôn `wp_reset_postdata()` để tránh lệch context.
5. Nếu cần tổng hợp nhiều block, ưu tiên `query_posts`/loop chung chỉ ở 1 vị trí, không lặp lại `have_posts()` ở 2 chỗ.
```

### [BUG-018] Upload Media Library thất bại vì thư mục wp-content/uploads thuộc user host khác với Apache user trong Docker
**File:** Docker runtime / WordPress Media Library
**Nguyên nhân:** Host mount `wp-content` thuộc `1000:1000`, trong khi WordPress chạy trong container dưới user `www-data` (`uid=33`). PHP không có quyền ghi vào `wp-content/uploads`.
**Fix:** Chạy trong container:
```bash
docker exec michinhhang-wordpress-1 sh -lc 'chown -R www-data:www-data /var/www/html/wp-content && chmod 775 /var/www/html/wp-content && chmod 775 /var/www/html/wp-content/uploads'
```
**Verification:**
```bash
docker exec michinhhang-wordpress-1 sh -lc 'su -s /bin/sh www-data -c "touch /var/www/html/wp-content/uploads/.www-data-test && rm -f /var/www/html/wp-content/uploads/.www-data-test"'
```
Nếu lệnh thành công, Media Library upload sẽ hoạt động bình thường.

### [BUG-019] Tràn CSS ngầm phá vỡ Responsive Tailwind
**File:** `header.php`
**Nguyên nhân:** Dùng thẻ `<style>` nội tuyến chứa `display: flex !important;` cho class menu. Lệnh này đè bẹp các class Responsive của Tailwind (như `hidden lg:flex`), làm khung giao diện bị vỡ nát trên màn hình nhỏ.
**Fix:** Tuyệt đối không dùng `!important` trong CSS để ép layout nếu đang dùng Tailwind. Để Tailwind (`hidden lg:flex`) tự lo việc responsive.

### [BUG-020] Phân trang (Pagination) URL sai cấu trúc SEO
**File:** `woocommerce/archive-product.php`
**Nguyên nhân:** Dùng hàm `add_query_arg('paged', $i)` sinh ra link có tham số dạng `?paged=2` gây bất lợi cho SEO (không chuẩn Pretty Permalinks).
**Fix:** Bắt buộc dùng `get_pagenum_link($i)` để WordPress sinh ra URL chuẩn `/page/2/` (và tự động giữ lại tham số filter như `?min_price`).

### [BUG-021] Lưới sản phẩm (Grid) quá to trên điện thoại
**File:** `woocommerce/archive-product.php`
**Nguyên nhân:** Dùng `grid-cols-1 sm:grid-cols-2` khiến sản phẩm bung to 100% màn hình dọc, khách phải vuốt rất mỏi tay.
**Fix:** Chuẩn TMĐT phải luôn bắt đầu từ 2 cột: `grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-4`.

---

## Anti-patterns Tổng hợp

### PHP Anti-patterns
| ❌ SAI | ✅ ĐÚNG |
|--------|---------|
| `the_title()` | `echo esc_html( get_the_title() )` |
| `get_sale_price()` trên variable | `get_variation_sale_price('min', true)` |
| `while(have_rows())` 2 lần | `ob_start()` buffer + echo lại |
| Biến khai báo ngoài nhưng chỉ dùng trong nhánh | Khai báo trong nhánh cần dùng |
| `json_encode()` trong `data-*` | `esc_attr( wp_json_encode($data) )` |
| Giá = 0 in ra `0₫` | Hiển thị "Liên hệ" |
| Fallback literal không escape | `esc_html__( 'Liên hệ', 'omni-commerce' )` |
| Block tĩnh (Trust Badge) gọi trùng lặp | Chỉ load từ một nguồn (VD: `footer.php`) |
| Hardcode Tiêu đề & Nội dung trong Page Template | Dùng vòng lặp WP (`the_content()`) để Admin có thể tự sửa |
| Hardcode Mã nhúng (Iframe Bản đồ, Video) | Đọc từ ACF Options, chỉ dùng hardcode làm fallback dự phòng |

### JS Anti-patterns
| ❌ SAI | ✅ ĐÚNG |
|--------|---------|
| `.catch(err => { showToast(...) })` khi không dùng `err` | `.catch(() => { showToast(...) })` |
| `console.log()` trong production | Xóa trước deploy |
| `alert()` cho form tạm | `console.log()` + `/* TODO: API */` |
| DOM element tạo nhưng không có API đọc | Xóa dead code |

---

## Checklist trước mỗi commit
- [ ] Mỗi `get_field('name')` đã grep verify trong `functions.php`
- [ ] Mọi `<section>` có nhánh `else` fallback tĩnh
- [ ] Fallback mock items khớp đúng số cột `grid-cols-N`
- [ ] Compile Tailwind: `./tailwindcss-linux-x64 -i ./src/input.css -o ./style.css --minify`
- [ ] Không có `console.log()` / `/* TODO: API */` trong production template
- [ ] Không có 2 hàm cùng hook vào 1 filter
- [ ] `get_the_title()` đã wrap trong `esc_html()`
- [ ] Tên danh mục dùng đúng Title Case chuẩn
- [ ] Không có TargetContent quá ngắn khi dùng replace_file_content
- [ ] Không gọi `trust-badges.php` từ `front-page.php` (đã có trong footer.php)

---

## Deep Audit: Archive Product Integrity (đúc kết từ review UI & logic danh mục)

### 1) Mục tiêu đúng của page danh mục
Trang danh mục sản phẩm không chỉ là “list sản phẩm”, mà là một mini storefront với 4 mục tiêu song song:
1. Hiển thị đúng product taxonomy + filters + sorting
2. Giữ layout sạch, dễ đọc và có nhịp thẩm mỹ
3. Tạo cảm giác thương hiệu premium cho khách hàng
4. Dễ bảo trì và không bị rơi vào code thừa / hardcode

### 2) Bản chất bug bản địa trong trang danh mục
Các lỗi không nằm ở “layout xấu” mà ở các pattern lặp lại sau:
- Hardcode fallback quá nhiều khi chưa có ACF hoặc term data
- Logic banner / SEO / filter bị rải rác trong template thay vì gom vào helper dùng chung
- Sử dụng nhiều HTML redundant, khiến template dài và khó đồng bộ giữa desktop/mobile
- Chồng logic fallback menu + sidebar + filter trong cùng file mà không một nguồn dữ liệu duy nhất
- Dùng inline style cho layout chính dù Tailwind đã có thể làm được
- Copy SEO chung chung, dài và generic, không phù hợp từng danh mục cụ thể

### 3) Yếu tố browser-level cần kiểm tra trước khi gọi là “đã đẹp”
Sau khi xem trên browser, cần đánh giá theo 5 tầng sau:
- Tầm nhìn đầu tiên: banner có tạo điểm nhấn hay chỉ “ảnh nền trơn”?
- Hierarchy: đâu là phần quan trọng nhất? Giá, sản phẩm, filter, CTA?
- Density: sidebar có nặng chữ / nặng block quá mức không?
- Premium feel: hover, shadow, badge, spacing có đủ “catalog quality” không?
- Responsive coherence: desktop/mobile cùng loại logic, cùng UI cảm giác, không bị lệch thẩm mỹ

### 4) Rule sửa UI đã học từ thực tế
- Banner 3 cột phải có overlay + label + hierarchy rõ, không để tản cảm giác.
- Sidebar filter chỉ nên tập trung vào chức năng, không biến thành “bảng quảng cáo”.
- Card sản phẩm phải ưu tiên giá, ảnh, badge trạng thái, nhấn mạnh thương hiệu hơn text dài.
- Text dài trên các block SEO hoặc thông tin phụ phải được rút gọn, không làm trang trông nặng.
- Màu trạng thái phải rõ: available / sold out / sale / active filter.
- Sort control và mobile filter phải có cùng cảm giác trải nghiệm với UI desktop.

### 5) Rule kỹ thuật bắt buộc cho trang danh mục
- Không hardcode banner/SEO URL/text nếu đã có ACF hoặc taxonomy dữ liệu sẵn.
- Mọi helper reusable phải nằm trong `functions.php` hoặc một file shared utility.
- Một trang danh mục chỉ nên có một nguồn dữ liệu cho filter và banner, không để logic rải rác trên nhiều chỗ.
- Tối ưu ưu tiên: logic trước, code thừa sau, giao diện cuối cùng mới chỉnh.
- Không dùng inline style cho layout chính nếu Tailwind có thể xử lý.
- Tránh tạo “fallback quá lạ” khi data thực tế chưa có; phải có logic rõ ràng theo priority order.

### 6) Rule mới chuẩn hóa: Archive Product Integrity
```
Archive Product Integrity
1. Không hardcode banner, SEO text, URL fallback nếu có dữ liệu ACF / taxonomy.
2. Luôn gom logic dùng chung vào helper / utility; không để template chứa quá nhiều biến phụ thuộc.
3. Mỗi filter, mỗi badge, mỗi section phải có một “nguyên nhân” rõ ràng và không bị duplicate.
4. Không xem trang danh mục là đơn thuần list; phải tạo trải nghiệm storefront có hierarchy + premium feel.
5. UI phải ưu tiên: sản phẩm → giá → trạng thái → filter → nội dung phụ.
6. Khi chỉnh UI, phải đồng bộ desktop/mobile, không làm đẹp ở một phía rồi hỏng phía còn lại.
7. Nếu page có thể làm bằng Tailwind, phải ưu tiên Tailwind, không dùng inline CSS cho layout chính.
8. Khi phát hiện hardcode / duplicate / generic copy, phải refactor ngay trước khi thêm tính năng mới.
9. **Sidebar Consistency:** Các khối chức năng nằm cùng nhau trên Sidebar (ví dụ: Danh mục, Bộ lọc) bắt buộc phải chia sẻ chung một bộ style (cùng padding, border, background, shadow) để đảm bảo tính đồng nhất (Consistency) và tránh cảm giác chắp vá.
```

### 7) Kết luận tri thức
Trang danh mục sản phẩm phải đạt 3 trạng thái đồng thời:
- Logic đúng
- Code sạch
- UI đẹp / premium

Nếu thiếu một trong ba, quá trình “đã chạy” chưa đủ tính ổn định cho sản phẩm thương mại điện tử. Cần kiểm tra bằng hai phương pháp đồng thời:
- review logic code
- review trải nghiệm trên browser
Đây là tiêu chuẩn mới mà toàn bộ dự án nên dùng khi làm tiếp trang danh mục hoặc các page thương mại tương tự.

---

### [BUG-022] Giao diện Mobile trang chủ quá dài, che lấp sản phẩm (UX Bloat)
**File:** `hero.php`, `product-row.php`, `media-services.php`, `news.php`
**Nguyên nhân:** Bê nguyên cấu trúc Desktop xuống Mobile khiến các khối phụ (Features, Ads, News, Video, Value Props, Banner dọc quá to) đẩy lùi danh sách sản phẩm xuống quá xa.
**Fix chuẩn:** 
- Lược bỏ triệt để bằng `hidden md:block` hoặc `hidden lg:flex` cho toàn bộ section phụ trên trang chủ khi xem trên Mobile.
- Giảm `min-h` banner dọc (vd: `min-h-[160px] lg:min-h-[340px]`) và ẩn ảnh minh họa to.
- Đảo thứ tự grid `order-2 lg:order-1` để đẩy Slider lên ưu tiên số 1, ép khối tính năng phụ xuống dưới.
- Triết lý Mobile-first: **Hành trình đến sản phẩm phải ngắn nhất, không bắt user cuộn qua nội dung nhiễu.**

---

### [BUG-023] Fallback danh mục rỗng hiển thị sai sản phẩm (False positive fallback)
**File:** `template-parts/home/product-row.php`
**Nguyên nhân:** Khi danh mục "Thiết bị gia đình" chưa có sản phẩm, code sử dụng fallback query theo danh sách từ khóa `$search_keywords = ['gia đình', 'robot', ...]`. Từ khóa "gia đình" quá rộng nên vô tình match trúng Tivi Xiaomi (có từ "gia đình" trong description), dẫn tới lỗi UI làm khách hàng lầm tưởng khối Thiết Bị Gia Đình bị lỗi lặp lại khối Tivi.
**Fix chuẩn:** 
- Tuyệt đối KHÔNG dùng fallback query theo từ khóa (keyword search) để nhét đầy danh mục rỗng, rất dễ gây nhầm lẫn hiển thị sai ngành hàng.
- Tuân thủ chuẩn WooCommerce: Nếu danh mục rỗng, cho phép query rỗng và hiển thị `<p>Chưa có sản phẩm nào trong danh mục này.</p>`. Đã gỡ bỏ block `foreach ($search_keywords as $kw)` trong `product-row.php`.

---

### [BUG-024] Thẻ sản phẩm bị kéo dài, khoảng trắng khổng lồ (CSS Grid Stretch)
**File:** `template-parts/home/product-row.php`
**Nguyên nhân:** Lạm dụng `h-full` trên thẻ sản phẩm `.product-card`. Do nằm trong CSS Grid, thẻ tự động bị stretch (kéo giãn) bằng chiều cao của phần tử cao nhất trong hàng, hoặc bằng row track. Khi kết hợp với `mt-auto` ở tiêu đề, nó tạo ra khoảng trắng khổng lồ. Kể cả khi bỏ `h-full`, grid item vẫn mặc định stretch.
**Fix chuẩn:** 
- Xóa `h-full` và `mt-auto`.
- Thêm **`self-start h-fit`** vào thẻ `.product-card` để ép thẻ không bị stretch theo lưới, mà tự co lại vừa đúng bằng nội dung bên trong nó. Khoảng trống (nếu có) sẽ đẩy ra phía ngoài thẻ thay vì tạo mảng trắng bên trong.

---

### [BUG-025] Vùng bấm chuột (Clickable area) biến mất sau khi xóa nút "Thêm vào giỏ"
**File:** `template-parts/home/product-row.php`
**Nguyên nhân:** Khi làm gọn UI mobile bằng cách xóa nút "Thêm vào giỏ", thẻ sản phẩm mất đi CTA chính. Vùng nền trắng của thẻ không có tag `<a>` bao quanh, nên user bấm vào vùng trắng không có tác dụng.
**Fix chuẩn:** 
- Không bọc toàn bộ thẻ bằng `<a>` vì dễ gây lỗi lồng thẻ `<a>` (HTML invalid).
- Sử dụng CSS trick: Thêm class `after:absolute after:inset-0 after:z-20` vào thẻ `<a>` chứa tiêu đề sản phẩm. Vùng bấm chuột sẽ tự động mở rộng bao phủ toàn bộ thẻ cha gần nhất có `relative` (tức là toàn bộ thẻ sản phẩm).

---

### [BUG-026] Giao diện Desktop bị vỡ/ẩn thành phần khi dùng class Responsive của Tailwind (Tailwind Compiler Sync Issue)
**File:** `header.php` và các file template chứa cấu trúc quan trọng
**Nguyên nhân:** Khi thêm trực tiếp class responsive của Tailwind (vd: `hidden md:block`) vào mã HTML, nếu Tailwind chưa được biên dịch lại (compile) hoặc bị cache, trình duyệt sẽ chỉ nhận class `hidden` (ẩn toàn bộ) mà không nhận class `md:block` (hiển thị trên Desktop). Hậu quả là thành phần đó bị biến mất trên Desktop gây lỗi hiển thị nghiêm trọng.
**Fix chuẩn:** 
- Đối với các thành phần cấu trúc cốt lõi (như Header Topbar), khi cần tinh chỉnh responsive độc lập cho Mobile mà muốn giữ nguyên Desktop: **Bắt buộc dùng CSS Media Query thuần** (ví dụ `@media (max-width: 899px) { ... }`) chèn trực tiếp vào block `<style>` của file đó.
- Cách này đảm bảo Desktop giữ nguyên layout gốc 100% không phụ thuộc vào tình trạng của trình biên dịch Tailwind, đồng thời chỉnh sửa Mobile có tác dụng ngay lập tức mà không có rủi ro sập layout hệ thống.

---

### [BUG-027] Ảo giác "CSS cập nhật nhưng UI không đổi" do Filter chặn Cache-busting
**File:** `inc/performance.php`, `src/input.css`
**Nguyên nhân:**
- File `inc/performance.php` có hàm tối ưu tốc độ bằng cách xóa toàn bộ đuôi `?ver=` của các file tĩnh (như CSS, JS).
- Khi biên dịch CSS từ Tailwind, dù ta chủ động gọi `time()` hay `filemtime()` ở backend để tạo version string mới, frontend vẫn trả về đường dẫn URL cũ vì bị filter trên loại bỏ mất. Do đó, Trình duyệt hoặc CDN tiếp tục dùng file CSS cũ.
- Ngoài ra, có những phần tử bắt buộc phải sử dụng CSS nội tuyến để ép hiển thị trên màn hình lớn (ví dụ `.main-nav-header`), nhưng khi làm vậy, nếu không có Tailwind config tốt hoặc thiếu `style.css` Theme Header, cấu trúc theme sẽ bị lỗi nghiêm trọng hoặc menu tiếp tục ẩn.
**Fix chuẩn:**
- Phải thiết lập **ngoại lệ** trong hàm xóa query string cho những tệp cần cache buster:
  ```php
  if ( strpos( $src, 'tailwind.css' ) !== false ) { return $src; }
  ```
- Nếu bắt buộc ép hiển thị Layout trên Desktop (không tin tưởng breakpoint của Tailwind khi có rủi ro caching), hãy viết CSS thuần vào `src/input.css` (bọc trong `@media`) và đặc biệt khi build đè `style.css` thì phải giữ lại Theme Header Comments để không làm gãy chuẩn WordPress.

---

### [BUG-028] Vỡ Layout Topbar trên Mobile (Thiếu tối ưu Responsive Cục bộ)
**File:** `header.php`
**Nguyên nhân:**
- Bê nguyên bộ khung Desktop (`flex justify-between items-center`) của Topbar áp dụng cho Mobile. 
- Màn hình Mobile (dưới 768px) không đủ chiều ngang chứa dòng chữ chào mừng cực dài kèm cụm Địa chỉ & Hotline, dẫn đến hiện tượng bóp méo nội dung, đè text hoặc tràn viền màn hình (overflow-x).
**Fix chuẩn:**
- Áp dụng triệt để "Responsive Hiding": Thêm class `hidden lg:block` để giấu hẳn dòng chữ "Chào mừng quý khách..." trên Mobile.
- Tái cấu trúc cụm Địa chỉ + Hotline: Chuyển từ hàng ngang sang xếp cột dọc bằng `flex-col md:flex-row`, ép chiều rộng `w-full lg:w-auto` và căn giữa `justify-center text-center`.
- Dùng thủ thuật Clamp Text: Thêm `line-clamp-1 md:line-clamp-none` để giới hạn chiều dài của Địa chỉ trên màn nhỏ, đồng thời giấu bớt các icon rườm rà (vd: `hidden md:inline-block`).

---

### [BUG-029] Hỏng Desktop Grid khi chuẩn hoá Responsive Hiding bằng Tailwind (Liên quan BUG-026)
**File:** `hero.php`
**Nguyên nhân:** Khi có chủ đích dùng `<style>` nội tuyến chứa `@media (max-width: 899px) { display: none !important; }` để ép ẩn các khối trên Mobile (tránh UX Bloat) mà Desktop vẫn an toàn. Việc cố tình "chuẩn hoá" thay bằng class Tailwind (`hidden lg:flex`) có thể khiến trình duyệt đánh giá sai nếu file `style.css` chưa được recompile để sinh ra `lg:flex` (do thiếu safelist hoặc thiếu build), dẫn đến class `hidden` nuốt chửng khối nội dung ngay cả trên Desktop.
**Fix chuẩn:** Tôn trọng ý đồ giấu UX Bloat trên Mobile bằng CSS nội tuyến nếu nó đang hoạt động ổn định và chưa có cơ chế recompile Tailwind an toàn. Không mù quáng refactor sang Tailwind class khi điều đó đe dọa trực tiếp layout Desktop.

---

### [BUG-030] Chặn Thương Mại Hoá Theme do Thiếu Hàm Đa Ngôn Ngữ (i18n)
**File:** Các template (vd: `hero.php`, `news.php`)
**Nguyên nhân:** Viết cứng chuỗi văn bản (hardcode string) như `"100% Chính hãng"`, `"Tin tức nổi bật"` vào thẳng mã HTML để làm fallback khi chưa có dữ liệu ACF. Theme Check sẽ đánh trượt 100%, khách hàng mua theme cũng không thể dịch web (qua WPML / Loco Translate).
**Fix chuẩn:** Mọi chuỗi văn bản tĩnh, dù chỉ là text hiển thị tạm (fallback), BẮT BUỘC phải bọc qua hàm dịch chuẩn của WordPress như `__()`, `esc_html__()`, `esc_html_e()` kèm theo Text Domain của theme. (VD: `esc_html_e('100% Chính hãng', 'omni-commerce');`).

---

### [BUG-031] Giảm điểm Technical SEO do nhảy bậc Heading (Heading Skipping)
**File:** `template-parts/home/media-services.php`
**Nguyên nhân:** Đặt thẻ `<h4>` (vd: "Mua hàng dễ dàng") trực tiếp bên trong một section mà không có thẻ `<h2>` hay `<h3>` cha nào đứng trước nó. Các công cụ Audit SEO (Lighthouse, Ahrefs) sẽ đánh dấu lỗi "Heading is not sequentially-descending".
**Fix chuẩn:** Hệ thống Heading phải liên tục (H1 -> H2 -> H3 -> H4). Nếu khối thiết kế không có H2/H3 trực quan, cần dùng thẻ `<h3>` thay cho `<h4>`, hoặc chèn 1 thẻ `<h2>` tàng hình (visually hidden) cho section đó. (Lưu ý: kỹ thuật thẻ H1 tàng hình trong `front-page.php` bằng `clip: rect(0,0,0,0)` là một Standard RẤT TỐT cần duy trì).

---

### 💡 [KIẾN TRÚC] Sự đánh đổi giữa UX Mobile và Mobile-First Indexing (Google SEO)
Khi quyết định dùng `hidden md:block` (CSS `display: none`) để giấu bớt các section như *Tin tức (News)* hay *Giá trị cốt lõi (Value Props)* trên giao diện điện thoại nhằm làm gọn trải nghiệm (tránh UX Bloat):
- **Cần biết:** Google hiện tại quét web bằng **Mobile-First Indexing** (dùng bot giả lập điện thoại di động). Nếu bot thấy nội dung bị `display: none`, nó có xu hướng bỏ qua, không lập chỉ mục (index) hoặc giảm trọng số từ khóa của các đoạn text đó.
- **Quyết định hệ thống:** Đối với TMĐT, hành trình mua hàng nhanh gọn quan trọng hơn một chút điểm SEO từ khối tin tức phụ. Vì vậy, **việc ẩn các khối phụ trên Mobile là một sự đánh đổi có chủ đích (Intentional Trade-off)**. Các agent sau này không được tự ý bỏ class ẩn trên Mobile với lý do "tối ưu SEO" nếu điều đó làm hỏng UX tinh gọn của dự án.

---

### [BUG-032] Lỗi vỡ giao diện khối lọc (Sidebar) do Widget mặc định của WordPress
**File:** `woocommerce/archive-product.php`
**Nguyên nhân:** Lệnh `is_active_sidebar('shop-sidebar')` tự động gọi các widget mặc định của WordPress (như Bài viết mới, Lưu trữ, Danh mục...) đè lên bộ lọc tĩnh HTML/Tailwind, gây vỡ khối bộ lọc sản phẩm.
**Fix chuẩn:** Bỏ logic gọi `dynamic_sidebar()` ở khu vực này, bắt buộc render thẳng khối HTML tĩnh (đã tích hợp JS) để giữ vững giao diện bộ lọc.

---

### [BUG-033] Class Tailwind mới không hoạt động do trình biên dịch (JIT) không kích hoạt
**File:** Các file template (`archive-product.php`, `footer.php`...)
**Nguyên nhân:** Khi thêm class mới của Tailwind (vd: `max-w-md`, `md:right-24`), trình duyệt không render được do file `style.css` chưa được recompile sinh mã tương ứng.
**Fix chuẩn:** Trong tình huống hotfix/không có compiler, hãy sử dụng `<style>` nội tuyến + Media query thuần (`@media (min-width: ...)`) kết hợp custom CSS (vd: `max-width: 320px !important`) bám sát triết lý của BUG-026 và BUG-029.

---

### [BUG-034] Lệch chiều cao nội dung trong các khối Grid (Equal Height Columns)
**File:** `footer.php`, `archive-product.php` (hoặc các layout dùng CSS Grid)
**Nguyên nhân:** CSS Grid mặc định kéo giãn chiều cao của các cột bằng với cột cao nhất (ví dụ cột chứa Iframe Facebook). Tuy nhiên, nội dung bên trong các cột thấp hơn sẽ không tự động dồn xuống đáy, tạo ra các khoảng trống thiếu cân đối.
**Fix chuẩn:** 
- Gán `flex flex-col h-full` vào thẻ bọc nội dung của từng cột để ép khối nội dung giãn ra hết toàn bộ chiều cao của ô Grid.
- Dùng `mt-auto` ở phần tử cuối cùng (vd: các nút Social, Badge chứng nhận) để đẩy chúng xuống sát mép dưới cùng, giúp tạo ra một đường gióng ngang hoàn hảo ở đáy tất cả các cột.

---

### [BUG-035] Lỗi tràn thẻ sản phẩm và bóp méo chữ trên màn hình hẹp (CSS Flexbox)
**File:** `woocommerce/content-product.php`
**Nguyên nhân:** Trên giao diện điện thoại (chia 2 cột hẹp), không gian không đủ khiến khối giá tiền (Price) bị đùn văng ra ngoài khung viền, đồng thời cụm từ "Đã bán X" bị bóp thành nhiều dòng xếp chồng lên nhau do thiếu diện tích. Việc bổ sung vội các class Tailwind (như `gap-x-2`) sẽ thất bại do BUG-033 (JIT chưa chạy).
**Fix chuẩn:** 
- Bổ sung `flex-wrap` để giá an toàn rớt xuống dòng dưới thay vì đâm xuyên viền.
- Đặt `whitespace-nowrap shrink-0` cho các cụm text cực kỳ quan trọng (như "Đã bán") để ép trình duyệt tuyệt đối không được cắt/ép méo chữ.
- Nếu không có JIT compiler, BẮT BUỘC tái sử dụng các class khoảng cách (`gap-2`, `gap-1`...) ĐÃ tồn tại sẵn trong bản build cũ thay vì viết class mới.
