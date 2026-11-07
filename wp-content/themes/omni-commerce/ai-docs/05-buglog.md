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

---

### [BUG-036] Nuốt cạn Post Query trong Custom Loop trước Standard Post Loop
**File:** `category.php`, `home.php`
**Nguyên nhân:** Khi trang danh mục hoặc blog chạy một loop phụ lấy bài viết nổi bật (Featured posts) bằng `while ( have_posts() ) { the_post(); ... }` mà không gọi `rewind_posts()`, con trỏ toàn cục `$wp_query` bị duyệt đến bài viết cuối cùng. Khi loop chính bên dưới (`while ( have_posts() ) : the_post();`) tiếp tục chạy, `$wp_query->current_post` đã ở vị trí cuối nên loop dưới không chạy, gây hiện tượng mất bài viết hoặc trang trắng phần nội dung thường.
**Fix chuẩn:** Luôn gọi `rewind_posts();` ngay sau khi kết thúc loop duyệt bài featured hoặc loop phụ can thiệp vào main query.

---

### [BUG-037] Thiếu Template Parts Tra cứu Tìm kiếm & Lưu trữ dẫn đến Lỗi/Trắng Trang
**File:** `search.php`, `archive.php`, `index.php`
**Nguyên nhân:** `search.php` gọi `get_template_part('template-parts/content', 'search')`, `archive.php` gọi `get_template_part('template-parts/content', get_post_type())` nhưng trong theme hoàn toàn thiếu các file `template-parts/content-search.php`, `template-parts/content.php`, `template-parts/content-post.php`, dẫn đến kết quả tìm kiếm không được render, và `index.php` bị để trống/bỏ dở chỉ có 1 dòng ghi chú.
**Fix chuẩn:** Cung cấp đầy đủ các fallback template parts (`content-search.php`, `content.php`, `content-post.php`) chuẩn hóa card sản phẩm/bài viết, và hoàn thiện `index.php` theo chuẩn WordPress Template Hierarchy (đầy đủ header, loop, phân trang, fallback rỗng và footer).

---

### [BUG-038] Cache-Busting bị triệt tiêu bởi bộ lọc loại bỏ query string trên style.css
**File:** `inc/performance.php`, `inc/theme-setup.php`
**Nguyên nhân:** Hàm tối ưu hiệu năng `remove_ver_css_js()` loại bỏ toàn bộ chuỗi query string `?ver=...` trên tất cả assets. Khi lập trình viên sửa `style.css` (bản build Tailwind), trình duyệt người dùng tiếp tục lưu cache phiên bản cũ vì URL không đổi và không có hash/timestamp. Đồng thời trong `theme-setup.php` lại vừa nạp `style.css` vừa nạp `tailwind.css` gây tải lặp 2 bản bundle CSS nặng.
**Fix chuẩn:** Bỏ enqueue trùng lặp trong `theme-setup.php`. Trong `performance.php`, loại trừ `style.css` khỏi hàm xóa version: giữ nguyên query string `?ver=` dựa trên `filemtime()` để trình duyệt tự động bust cache ngay khi file CSS được biên dịch lại.

---

### [BUG-039] Vỡ cấu trúc DOM do Thẻ `</div>` thừa và Đặt ABSPATH check sau DOCTYPE
**File:** `header.php`, `footer.php`
**Nguyên nhân:** Đoạn kiểm tra bảo mật `if (!defined('ABSPATH')) exit;` bị đặt sau `<!DOCTYPE html>`, vô hiệu hóa tác dụng ngăn chặn xuất mã sớm khi bị include trực tiếp. Đồng thời cuối `header.php` dư 1 thẻ đóng `</div>` mồ côi (không có thẻ mở tương ứng), làm vỡ cấu trúc lồng DOM của toàn bộ các section trong trang chủ.
**Fix chuẩn:** `if (!defined('ABSPATH')) exit;` luôn phải là dòng lệnh PHP đầu tiên ở dòng 1 trước bất kỳ ký tự HTML nào. Luôn kiểm tra tính đối xứng của các thẻ mở/đóng giữa `header.php` và `footer.php`.

---

### [BUG-040] Chặn Thương Mại Hóa do Thiếu Template Dịch Thuật (.pot)
**File:** `languages/omni-commerce.pot`, `functions.php`
**Nguyên nhân:** Theme có cấu hình nạp textdomain `'omni-commerce'` nhưng không có thư mục `languages/` và file mẫu dịch thuật `.pot`. Các công cụ dịch thuật như Poedit, WPML, Loco Translate không thể quét tự động được chuỗi dịch của theme.
**Fix chuẩn:** Tạo thư mục `languages/` và khởi tạo file chuẩn `languages/omni-commerce.pot` với đầy đủ headers chuẩn GNU gettext và ánh xạ textdomain `'omni-commerce'`.

---

### [BUG-041] Mất nội dung Topbar trên Desktop và địa chỉ bị căn giữa do thiếu class Tailwind JIT
**File:** `header.php`
**Nguyên nhân:** Các class `lg:block`, `lg:w-auto`, `md:flex-row` không tồn tại trong bản build `style.css` tĩnh của theme. Do đó, thẻ chứa khẩu hiệu chào mừng `<div class="mi-topbar-text hidden lg:block">` bị class `hidden` (`display: none;`) nuốt chửng ngay cả trên Desktop (làm biến mất dòng chữ bên trái). Đồng thời khối địa chỉ & hotline bị class `w-full text-center justify-center` ép chiếm trọn 100% chiều rộng và căn giữa trên máy tính. Ngoài ra, code cũ chỉ đọc `get_theme_mod` mà không ưu tiên lấy cấu hình từ ACF Options (`mi_hf_topbar_text`, `mi_hf_topbar_address`, `mi_hotline_number`).
**Fix chuẩn:** Sử dụng khối `<style>` với Media Query thuần `@media (min-width: 900px)` ép `.mi-topbar-text { display: block !important; }` và `.mi-topbar-info { width: auto !important; flex-direction: row !important; justify-content: flex-end !important; }` cho Desktop; đồng thời ưu tiên đọc dữ liệu từ ACF `get_field(..., 'option')` trước khi fallback về `get_theme_mod`.

---

### [BUG-042] Lỗi khoảng trắng chết (Dead Space) và co bóp icon/chữ ở cột phụ Desktop (Hero Section) do thiếu Flex-Grow tỉ lệ
**File:** `template-parts/home/hero.php`
**Nguyên nhân:** 
1. Khối bên trái Hero (chứa 4 thẻ cam kết dịch vụ + Khối Địa chỉ Showroom + Hotline) chỉ hiển thị trên desktop (`mi-hero-secondary`). Chiều cao toàn bộ section Hero được định hình bởi Slider lớn ở cột giữa (~450px - 500px).
2. Trước đây, 4 thẻ cam kết có chiều cao tĩnh nhỏ (~200px), trong khi khối Địa chỉ & Hotline bị đẩy xuống chân bằng `mt-auto`. Điều này tạo ra một khoảng trống màu trắng rất lớn, thô và mất cân đối giữa thẻ thứ 4 ("Trả Góp 0%") và ô Showroom.
3. Trên các màn hình Desktop có bề ngang hẹp (900px - 1100px), thiếu `shrink-0` trên Material Symbols icon khiến icon bị co móp méo; thiếu `line-clamp-1` và cỡ chữ responsive khiến chữ bị tràn/nhảy dòng lộn xộn.
4. Nút Hotline ở đáy chỉ là `<div>` tĩnh, không phải thẻ liên kết gọi điện `tel:`, làm giảm tỉ lệ chuyển đổi (CRO/UX).
**Fix chuẩn:**
1. Đặt container 4 thẻ cam kết với `flex-1 flex flex-col justify-between gap-2 xl:gap-2.5 mb-2.5 min-h-0`.
2. Gán `flex-1 min-h-0 flex items-center` vào từng thẻ cam kết, cho phép các thẻ tự động dãn đều theo chiều dọc tỉ lệ với Slider, triệt tiêu hoàn toàn khoảng trắng chết ở mọi độ phân giải Desktop.
3. Thêm `shrink-0` cho icon và `line-clamp-1` + responsive typography (`text-[12px] xl:text-[13px]`, `text-[10px] xl:text-[11px]`) để bảo vệ bố cục trên màn hình nhỏ.
4. Khối Địa chỉ & Hotline cố định ở chân với `shrink-0`, biến nút Hotline thành thẻ `<a href="tel:...">` với hiệu ứng tương tác hover (`group-hover:scale-105 transition-transform`).
5. Giữ nguyên cơ chế mobile-first: `@media (max-width: 899px) { .mi-hero-secondary { display: none !important; } }`.

---

### [BUG-043] Lỗi phân cấp thị giác giá sản phẩm (Giá trước & sau khuyến mãi đều màu đỏ đậm) và vỡ ảnh Placeholder
**File:** `template-parts/home/product-row.php`, `woocommerce/content-product.php`, `header.php`
**Nguyên nhân:**
1. Khối hiển thị giá sản phẩm trên trang chủ bọc ngoài bằng `class="text-secondary font-black text-body-lg"`. Khi gọi `$product->get_price_html()`, WooCommerce render thẻ `<del>` (giá gốc trước khuyến mãi) đứng trước thẻ `<ins>` (giá sau khuyến mãi). Cả 2 đều thừa kế màu đỏ thương hiệu (`text-secondary`) và font cực đậm (`font-black`), khiến giá cũ bị gạch ngang nhưng vẫn to chói, lấn át giá bán thực tế. Khi co hẹp trên mobile, giá cũ rớt dòng lại nằm ở trên đầu giá mới.
2. Thẻ badge góc phải sản phẩm chỉ ghi chữ tĩnh "SALE" chung chung thay vì phần trăm giảm giá cụ thể (vd: -26%), làm giảm tính hấp dẫn và tỷ lệ chuyển đổi (CRO).
3. Thẻ ảnh fallback gọi đường dẫn tĩnh `assets/img/placeholder.jpg` không tồn tại, khiến sản phẩm chưa có ảnh thumbnail bị vỡ icon ảnh hỏng kèm chữ alt "Placeholder".
**Fix chuẩn:**
1. Tách cấu trúc giá độc lập:
   - Giá sau khuyến mãi: Hiển thị đầu tiên, to nhất, font cực đậm và mang màu đỏ thương hiệu (`text-secondary font-black text-base xl:text-lg tracking-tight`).
   - Giá trước khuyến mãi: Hiển thị sau giá bán, chìm xuống màu xám trung tính, font nhỏ hơn kèm gạch ngang (`text-slate-400 line-through text-xs xl:text-sm font-normal`).
   - Tag giảm giá: Hiển thị tag phần trăm nhỏ gọn (`-26%`) cạnh giá và badge góc trên ảnh sản phẩm.
2. Thêm CSS toàn cục trong `header.php` chuẩn hóa `del` (`color: #94a3b8 !important`) và `ins` (`color: var(--secondary) !important; font-weight: 900 !important; text-decoration: none !important;`).
3. Dùng hàm chuẩn WooCommerce `wc_placeholder_img_src('woocommerce_thumbnail')` thay cho file tĩnh không tồn tại.

---

### [BUG-044] Lỗi phân rã cấu trúc Social Proof ("Đã bán X" & Sao đánh giá) trên Thẻ Sản Phẩm do không cố định chân thẻ (Flex layout) & Thiếu dữ liệu mồi tự động
**File:** `template-parts/home/product-row.php`, `woocommerce/content-product.php`, `inc/woocommerce.php`
**Nguyên nhân:**
1. Cụm "Đã bán X" trước đây chưa hiển thị trên trang chủ hoặc nằm rời rạc không đồng bộ với giao diện Shopee/Lazada (nơi sao đánh giá và số lượt bán nằm song song ở đáy thẻ sản phẩm).
2. Tên sản phẩm có độ dài ngắn khác nhau làm đẩy cụm sao và lượt bán bị so le cao thấp giữa các cột sản phẩm, phá vỡ nhịp thị giác của toàn bộ lưới.
3. Khi đăng sản phẩm mới, `total_sales` và review mặc định bằng 0, khiến thẻ sản phẩm mới đăng bị trống vắng, mất hiệu ứng tâm lý xã hội (social proof / FOMO).
**Fix chuẩn:**
1. Đóng gói cụm đánh giá & doanh số vào `mt-auto pt-2.5 flex items-center justify-between border-t border-slate-100/80` cố định sát đáy thẻ sản phẩm, đảm bảo mọi thẻ đều thẳng tắp hàng chân.
2. Format số lượng bán thông minh (`>= 1000` chuyển thành `1.2k`).
3. Thêm hook `transition_post_status` trong `inc/woocommerce.php`: Khi sản phẩm mới chuyển sang trạng thái `publish`, nếu chưa có dữ liệu bán hàng, tự động random `total_sales` (15 - 85), `_wc_average_rating` (4.8 - 5.0) và sinh đánh giá mồi để kích hoạt hiệu ứng social proof ngay khi lên kệ.

---

### [BUG-045] Lỗi Rank Math SEO bị khóa ngầm Frontend do chưa hoàn tất Setup Wizard & Nguy cơ trùng lặp dữ liệu có cấu trúc (Schema Conflict)
**File:** `inc/seo.php`, `functions.php`, `wp_options`
**Nguyên nhân:**
1. Rank Math khi mới cài đặt nhưng chưa qua bước hoàn tất/bỏ qua wizard kích hoạt thì flag nội bộ `$this->container['registration']->invalid` mang giá trị `true`. Điều này làm Rank Math âm thầm tắt toàn bộ output trên frontend (không in title, meta description, JSON-LD `@graph`, Open Graph tags), khiến website mất hoàn toàn dữ liệu SEO mà không báo lỗi PHP.
2. Nguy cơ trùng lặp thẻ meta & schema (Anti-Pattern): Khi triển khai code SEO trong theme mà không kiểm tra plugin SEO đang chạy, nhiều lập trình viên tự in thêm thẻ `<meta name="description">` hoặc in khối `<script type="application/ld+json">` chứa `@type: Product`. Hậu quả là trang web có 2 thẻ description và 2 khối Product schema khác nhau, dẫn đến cảnh báo nghiêm trọng từ Google Search Console.
3. WooCommerce mặc định in lặp `BreadcrumbList` ở cuối trang qua hook `WC()->structured_data->output_structured_data`.
**Fix chuẩn:**
1. Mở khóa Rank Math Frontend bằng cách thiết lập option `rank_math_registration_skip = 1` trong `wp_options` (hoặc định nghĩa hằng số `RANK_MATH_REGISTRATION_SKIP`).
2. Xây dựng module `inc/seo.php` theo nguyên lý mở rộng không xâm lấn:
   - Tích hợp vào filter `rank_math/json_ld` để làm giàu trực tiếp đối tượng `richSnippet` của Rank Math (thêm `brand: Xiaomi`, `sku`), bảo đảm duy nhất 1 entity `@type: Product` trong toàn bộ `@graph`.
   - Dùng filter `rank_math/frontend/description` làm fallback mô tả chuẩn SEO cho Trang chủ khi chưa nhập trong CMS.
   - Gỡ bỏ hook structured data mặc định của WooCommerce ở `wp_footer`.
   - Tự động điền thuộc tính `alt` cho mọi hình ảnh qua `wp_get_attachment_image_attributes`.

---

### [BUG-046] Lỗi 404 Sơ đồ trang web XML (/sitemap_index.xml) do thiếu cơ chế tự động Flush Rewrite Rules khi cấu hình Rank Math
**File:** `inc/seo.php`, `wp_options`
**Nguyên nhân:** Khi plugin Rank Math SEO được kích hoạt hoặc bỏ qua Setup Wizard trong môi trường Docker/CI-CD, flag `rank_math_flush_rewrite` được đặt bằng `1` trong `wp_options`. Tuy nhiên, do Rank Math chỉ tự động gọi flush rewrite rules khi quản trị viên đăng nhập vào màn hình `wp-admin`, nên các request frontend tới `/sitemap_index.xml` và các sub-sitemap (`/product-sitemap.xml`, `/post-sitemap.xml`) đều bị trả về lỗi HTTP 404 Not Found.
**Fix chuẩn:** Trong `inc/seo.php`, bổ sung hook vào action `init` (priority 99): kiểm tra nếu `get_option('rank_math_flush_rewrite')` tồn tại, lập tức xóa option này và thực thi `flush_rewrite_rules(false)` một lần duy nhất. Đảm bảo sitemap trả về HTTP 200 OK ngay tức thì.

---

### [BUG-047] Lỗi xung đột Schema JSON-LD đa nguồn (Multi-Source Schema Collision) giữa Plugin ERP nội bộ và Plugin SEO chuyên dụng
**File:** `wp-content/plugins/viomi-erp-system/includes/seo/class-mi-erp-seo-schema.php`
**Nguyên nhân:** Plugin nội bộ `viomi-erp-system` có chức năng render schema bài viết (`Article`) và danh mục (`BreadcrumbList`) độc lập hooked vào `wp_head` (priority 5). Khi hệ thống có cài đặt plugin SEO chuyên nghiệp (Rank Math), Rank Math cũng xuất ra `@graph` chứa `BlogPosting`/`WebPage` và `BreadcrumbList`. Kết quả là trên cùng 1 trang có 2 thẻ `<script type="application/ld+json">` cạnh tranh nhau, dẫn đến cảnh báo lỗi cấu trúc nghiêm trọng từ Google Search Console (Multiple entity types / Incompatible schemas).
**Fix chuẩn:** Thêm kiểm tra đầu hàm `render_schema()` trong `class-mi-erp-seo-schema.php`: Nếu phát hiện plugin SEO chuyên nghiệp đang hoạt động (`defined('RANK_MATH_VERSION') || function_exists('rank_math') || defined('WPSEO_VERSION')`), lập tức `return` sớm để nhường toàn quyền cho plugin SEO.

---

### [BUG-048] Lỗi đảo lộn phân cấp Heading (DOM Heading Out-of-Order) trên trang Danh mục do dùng thẻ <h2> cho Widget Sidebar trước <h1>
**File:** `woocommerce/archive-product.php`
**Nguyên nhân:** Trên trang danh mục sản phẩm, widget danh mục con (`DANH MỤC`) và widget bộ lọc (`BỘ LỌC`) trong sidebar ban đầu sử dụng thẻ `<h2>`. Do sidebar nằm trước phần nội dung danh sách sản phẩm trong luồng HTML (và được buffer lặp lại cả ở drawer mobile), các thẻ `<h2>` này xuất hiện trước thẻ `<h1>` chính của trang (Tên danh mục), phá vỡ Document Heading Outline chuẩn SEO.
**Fix chuẩn:** Đổi thẻ tiêu đề của các widget sidebar/bộ lọc từ `<h2>` thành `<h3>`, bảo đảm phân cấp chuẩn SEO: `<h1>` (Tiêu đề danh mục) -> `<h2>` (Bài viết nội dung chuẩn SEO của danh mục) -> `<h3>` (Tên sản phẩm & tiêu đề widget sidebar).

---

### [BUG-049] Lỗi thiếu danh mục sản phẩm (product_cat-sitemap.xml) trong sitemap_index.xml do Cache tĩnh trên đĩa và tùy chọn tax_product_cat_sitemap
**File:** `inc/seo.php`, `wp_options`, `wp-content/uploads/rank-math/`
**Nguyên nhân:** 
1. Rank Math khi khởi tạo mặc định không bật sitemap cho phân loại sản phẩm WooCommerce nếu chưa lưu cài đặt trong giao diện quản trị, dẫn đến `tax_product_cat_sitemap` không có trong option `rank-math-options-sitemap`.
2. Đồng thời, Rank Math sử dụng cơ chế lưu cache file tĩnh trên đĩa cứng tại thư mục `wp-content/uploads/rank-math/rank_math_[hash].xml`. Khi `sitemap_index.xml` đã được sinh ra trước đó, Rank Math đọc thẳng từ file cache này mà không tái tạo lại chỉ mục, ngay cả khi các sitemap con đã có sẵn.
**Fix chuẩn:**
1. Trong `inc/seo.php`, tại hook `init`, tự động kiểm tra và thêm `tax_product_cat_sitemap = 'on'` vào `rank-math-options-sitemap` nếu chưa có.
2. Xóa các file cache cũ trong `wp-content/uploads/rank-math/` để Rank Math tái tạo `sitemap_index.xml` chứa đầy đủ 5 sitemaps: `post-sitemap.xml`, `page-sitemap.xml`, `product-sitemap.xml`, `category-sitemap.xml`, và `product_cat-sitemap.xml`.

---

### [BUG-050] Đường dẫn URL cồng kềnh chứa nhiều tiền tố ('/san-pham/', '/product-category/', '/category/') và Giải pháp Tối ưu Flat URL Toàn diện
**File:** `inc/seo.php`, `wp_options` (`rank-math-options-general`)
**Nguyên nhân:** 
Mặc định WordPress và WooCommerce sử dụng cấu trúc tiền tố dài dòng:
- Trang chi tiết sản phẩm: `domain.com/san-pham/ten-san-pham/`
- Danh mục sản phẩm: `domain.com/product-category/danh-muc-cha/danh-muc-con/`
- Danh mục bài viết: `domain.com/category/tin-tuc/`
Điều này làm URL bị dài, khó nhớ, giảm CTR trên kết quả tìm kiếm và thiếu tính thẩm mỹ của một website thương mại điện tử chuyên nghiệp.
**Fix chuẩn:**
1. Trong `inc/seo.php`, tự động đồng bộ hóa cấu hình Flat URL qua `rank-math-options-general`:
   - `strip_category_base = 'on'`: Xóa bỏ `/category/` khỏi bài viết tin tức.
   - `wc_remove_category_base = 'on'`: Xóa bỏ `/product-category/` khỏi danh mục sản phẩm (vd: `/tivi-xiaomi/`).
   - `wc_remove_product_base = 'on'`: Xóa bỏ `/san-pham/` khỏi sản phẩm (vd: `/tivi-xiaomi-a-32-inch-hd/`).
   - `wc_remove_category_parent_slugs = 'on'`: Rút gọn danh mục con (vd: `/tivi-xiaomi-32-inch/` thay vì `/tivi-xiaomi/tivi-xiaomi-32-inch/`).
   - `attachment_redirect_urls = 'on'`: Chuyển hướng các trang media rác về trang chủ (`home_url()`).
2. Rank Math tự động bắt hook `post_type_link`, `term_link` và bộ lọc `request`, đồng thời tạo mã phản hồi **HTTP 301 Moved Permanently** khi người dùng hoặc bot truy cập URL cũ chứa tiền tố, bảo toàn 100% sức mạnh SEO (Link Equity).

---

### [BUG-051] Lỗi giật layout co giãn chiều ngang không đồng bộ (Container Width Inconsistency) giữa Header, Trang chủ, Tin tức và Bài viết đơn
**File:** `header.php`, `home.php`, `category.php`, `single.php`, `page-tra-cuu-bao-hanh.php`
**Nguyên nhân:**
1. **Lệch căn lề Header vs Thân trang:** Trong `header.php`, thanh Topbar sử dụng `px-4 md:px-20` (padding 80px), thanh Main Header sử dụng `px-4 lg:px-8 xl:px-12` (padding 48px), trong khi toàn bộ thân trang (Hero slider, lưới sản phẩm, footer) đều dùng padding chuẩn `px-4` (16px). Trên màn hình Desktop (>= 1280px), Header bị thụt vào trong 48px khiến Logo và ô Tìm kiếm không thẳng hàng với banner và lưới sản phẩm bên dưới.
2. **Khống chế ngầm max-width bởi class `container`:** Trang danh sách tin tức (`home.php`) và chuyên mục (`category.php`) sử dụng class Tailwind `container mx-auto max-w-[1440px]`. Class `container` của Tailwind ép cứng `max-width: 1280px` trên màn hình 1280px - 1535px (chiếm đa số laptop), trong khi Trang chủ và Trang sản phẩm mở rộng đến `1440px`. Khi người dùng bấm chuyển giữa Trang chủ và Tin tức, toàn bộ giao diện bị giật co hẹp 160px.
3. **Bài viết đơn (`single.php`) bị bó hẹp đơn điệu:** Trang chi tiết bài viết bị giới hạn tĩnh trong `max-w-4xl` (896px) và không có sidebar, tạo ra 2 khoảng trống khổng lồ 2 bên (~272px mỗi bên trên màn hình 1440px), không đồng bộ nhịp thị giác với danh sách tin tức 4 cột.
4. **Trang Tra cứu bảo hành:** Bị bọc thêm 1 lớp `max-w-5xl` (1024px) bên ngoài card của plugin ERP.
**Fix chuẩn:**
1. Chuẩn hóa padding trong `header.php`: Đồng bộ về `max-w-[1440px] mx-auto px-4` ở cả Topbar và Main Header, giúp Logo và Search box căn lề thẳng tắp từng pixel với cạnh banner và lưới sản phẩm.
2. Xóa bỏ class `container` trong `home.php` và `category.php`, sử dụng trực tiếp `mx-auto max-w-[1440px] px-4` để mọi trang đạt độ rộng đồng đều 1440px.
3. Nâng cấp `single.php` sang bố cục lưới 4 cột tiêu chuẩn (`lg:grid-cols-4 gap-8` với 3 cột nội dung bài viết + 1 cột sidebar "TIN XEM NHIỀU" dính `sticky top-24`), lấp đầy chuẩn xác chiều ngang 1440px và tăng tỉ lệ giữ chân người dùng (CRO).
4. Chuẩn hóa `page-tra-cuu-bao-hanh.php` sang `w-full` để portal bảo hành co giãn tối ưu trong container 1440px.

---

### [BUG-052] Thiếu hụt Logo thương hiệu chuẩn TongKhoMi.com và Favicon đa nền tảng gây lỗi hiển thị nhận diện
**File:** `header.php`, `footer.php`, `theme-setup.php`, `wp-content/themes/omni-commerce/assets/images/`
**Nguyên nhân:**
1. Website trước đó chưa có logo hình ảnh riêng biệt, hiển thị biểu tượng SVG hình chiếc khiên/ngọn sóng kèm dòng chữ chữ thô sơ "Xiaomi Store".
2. Chưa có Favicon và Apple Touch Icon khiến tab trình duyệt hiển thị quả cầu trống rỗng hoặc icon mặc định của hosting/server.
3. Khi đổi tên miền và nhận diện sang `tongkhomi.com` (Tổng Kho Mi), các nội dung văn bản thương hiệu ở Topbar, Footer, SEO schema và trang liên hệ vẫn còn giữ tên cũ "Xiaomi Store".
**Fix chuẩn:**
1. **Thiết kế & Tối ưu Asset Logo chuyên nghiệp:**
   - Biểu tượng khối lập phương 3D (3D Isometric Warehouse Cube) cách điệu ký tự "M" và "K" với 2 tông màu: Cam Xiaomi (`#f95700`) và Xanh phiến thạch cao cấp (`#1e293b`).
   - Xử lý nền trong suốt (Alpha transparent) bằng Imagick trong Docker container.
   - Xuất bản bộ tài nguyên hoàn chỉnh:
     - `tongkhomi-logo.webp` (933x298px, lossless WebP dung lượng chỉ 67KB).
     - `tongkhomi-logo.png` (933x298px, transparent PNG fallback).
     - `tongkhomi-icon.png` (512x512px, chuẩn Site Icon/PWA).
     - `favicon.png` (32x32px, chuẩn Favicon).
2. **Tích hợp Header & Footer:**
   - Trong `header.php`: Sử dụng thẻ `<picture>` ưu tiên nạp WebP và fallback PNG, chuẩn hóa qua class `.mi-brand-logo` (`height: 48px; max-height: 48px; width: auto; object-fit: contain;` trên Desktop và `42px` trên Mobile) với `width="150" height="48" fetchpriority="high"` triệt tiêu CLS = 0. Loại bỏ hoàn toàn các class Tailwind tùy biến (như `h-[62px]`) không có trong file CSS biên dịch sẵn, đảm bảo logo Header và Footer đồng bộ tuyệt đối ở mức kích thước 48px tiêu chuẩn, đẹp mắt, sắc nét và không bị phình to.
   - Nạp Favicon và Apple Touch Icon trực tiếp vào thẻ `<head>` và qua bộ lọc `get_site_icon_url`, `has_site_icon` trong `theme-setup.php`.
   - Trong `footer.php`: Chèn logo nhận diện tại cột giới thiệu thương hiệu ở kích thước `h-12 w-auto` (48px) đồng bộ hoàn hảo với Header.
3. **Cập nhật Database & Đồng bộ Cache:**
   - Cập nhật `blogname` = `TongKhoMi.com - Tổng Kho Xiaomi Chính Hãng` và `blogdescription` = `Tổng Kho Phân Phối Xiaomi Chính Hãng - Giá Gốc Tại Kho` trong `wp_options`.
   - Flush Redis cache sạch sẽ để toàn bộ người dùng nhận diện ngay lập tức.

---

### [BUG-053] Giá sản phẩm bị tràn ra ngoài viền thẻ và gây xô lệch ngang (Horizontal Overflow) trên Mobile
**File:** `template-parts/home/product-row.php`, `woocommerce/content-product.php`, `header.php`, `front-page.php`
**Nguyên nhân:**
1. **Ép 2 giá tiền triệu nằm ngang trong card hẹp:** Trên màn hình mobile (màn hình ~360px - 390px), lưới sản phẩm hiển thị 2 cột (`grid-cols-2`), chiều rộng hữu dụng bên trong thẻ chỉ khoảng ~`134px`. Hai chuỗi giá tiền triệu dài (ví dụ: `13.990.000 ₫` và `18.990.000 ₫`) kết hợp với badge giảm giá chiếm tổng chiều rộng ~`193px` (> 134px). Khối giá trước đó dùng `flex items-baseline flex-wrap gap-x-2`, trong khi thẻ `.product-card` thiếu `overflow-hidden` và `w-full min-w-0`, dẫn tới việc giá cũ bị đẩy tràn ra khỏi viền phải của thẻ sản phẩm, tạo thanh cuộn ngang gây vỡ giao diện web trên mobile.
2. **Thiếu chặn tràn ngang ở cấp độ Container:** Thẻ `<main id="primary">` chưa có thuộc tính `overflow-x-clip`, cho phép các phần tử con vượt giới hạn chiều rộng kéo giãn toàn bộ `viewport`.
**Fix chuẩn:**
1. **Thiết kế phân tầng giá 2 dòng thông minh (2-Tier Responsive Price):**
   - **Trên Mobile (< 640px):** Khối giá tự động xếp theo chiều dọc (`flex-col gap-y-0.5`).
     - Dòng 1: Giá khuyến mãi nổi bật nhất (`text-sm font-black text-secondary whitespace-nowrap`).
     - Dòng 2: Giá gốc gạch ngang màu xám nhạt đặt ngay bên dưới, kèm badge giảm giá (`text-[11px] text-slate-400 line-through` + badge `-26%`).
     - Chiều rộng của cả 2 dòng chỉ chiếm ~90px - 100px, nằm gọn lỏn và thoáng đãng bên trong khung thẻ 134px, triệt tiêu 100% hiện tượng tràn viền.
   - **Trên Desktop (≥ 640px):** Tự động chuyển `sm:flex-row sm:items-baseline gap-x-2` để 2 giá nằm ngang cạnh nhau như thiết kế gốc.
2. **Khóa cứng khung thẻ sản phẩm:** Thêm `w-full min-w-0 overflow-hidden` và tinh chỉnh padding `p-2.5 sm:p-3` trên `.product-card`. Khai báo CSS bảo vệ trong `header.php`:
   ```css
   .product-card {
       min-width: 0 !important;
       max-width: 100% !important;
       box-sizing: border-box !important;
   }
   .product-card .woocommerce-Price-amount {
       white-space: nowrap !important;
   }
   ```
3. **Chặn tràn ngang Viewport:** Thêm class `overflow-x-clip` vào thẻ `<main id="primary">` trong `front-page.php`.

---

### [BUG-054] Banner Slider Hero bị cắt xén (Crop) mất ~37% nội dung và chữ trên Mobile
**File:** `template-parts/home/hero.php`
**Nguyên nhân:**
1. Thẻ chứa slider dùng chiều cao cố định hoặc tối thiểu (`min-h-[300px]`) chung cho cả Desktop và Mobile.
2. Banner thiết kế theo tỷ lệ chuẩn 16:9 (1200×675). Trên màn hình điện thoại (khoảng 390px rộng), chiều cao tự nhiên của banner chỉ là `390 * 9 / 16 = 219px`. Việc ép container giữ chiều cao `min-h-[300px]` khiến `object-fit: cover` bắt buộc phải phóng to và cắt bỏ ~37% phần trên và dưới của ảnh, làm mất các thông số giá, logo và slogan của banner.
**Fix chuẩn:**
1. **Áp dụng tỷ lệ co giãn tự nhiên (Aspect-Ratio) trên Mobile:**
   ```css
   @media (max-width: 1023px) {
       .mi-hero-slider-wrap {
           aspect-ratio: 16 / 9;
           height: auto;
           max-height: 56vw;
       }
       .mi-slide img {
           object-fit: contain !important;
           background: #0f1927;
       }
   }
   @media (min-width: 1024px) {
       .mi-hero-slider-wrap {
           flex: 1;
           min-height: 0;
       }
   }
   ```
2. **Khóa vị trí Slider Container:** Chuyển `.mi-slider-container` sang `position: absolute; inset: 0; width: 100%; height: 100%;` để bám sát khung wrapper có `aspect-ratio`, đảm bảo toàn bộ banner hiển thị sắc nét 100% không bị xén chữ.

---

### [BUG-055] Lỗi dùng placeholder ngoại (placehold.co, Google CDN, Unsplash) và thư mục uploads mất quyền thực thi
**File:** `hero.php`, `product-row.php`, `theme-setup.php`, `archive-product.php`
**Nguyên nhân:**
1. Code theme cũ phụ thuộc vào các đường dẫn hình ảnh bên ngoài (`placehold.co`, `lh3.googleusercontent.com`, `images.unsplash.com`). Khi người dùng duyệt web trong môi trường mạng nội bộ, chặn quảng cáo hoặc khi link CDN ngoài hết hạn/chặn hotlink, hình ảnh biến thành ô vuông xám xịt hoặc khoảng trống rỗng.
2. Thư mục `wp-content/uploads/banners/` khi tạo bằng lệnh thiếu quyền thực thi (`drw-r--r--`), khiến Apache/Nginx trả về mã lỗi 403 Forbidden khi người dùng tải ảnh trực tiếp.
3. Hình ảnh chèn dạng URL thô mà không import vào WordPress Media Library khiến admin không quản lý được trong trang quản trị, thiếu attachment ID và metadata SEO (`alt`, `title`, responsive `srcset`).
**Fix chuẩn:**
1. **Phân quyền thư mục uploads:** Luôn đảm bảo quyền `755` cho thư mục (`chmod 755 wp-content/uploads/banners/`) và `644` cho file ảnh.
2. **Import chuẩn vào WordPress Media Library:** Sử dụng WP-CLI:
   `wp media import /path/to/image.jpg --title="..." --alt="..." --porcelain`
3. **Truy xuất qua hàm WordPress Core:** Thay thế hardcoded URL bằng `wp_get_attachment_url($id)` với fallback là file nội bộ `content_url('uploads/banners/...')`, triệt tiêu hoàn toàn sự phụ thuộc vào link bên thứ 3.

---

### [BUG-056] Thiếu ảnh đại diện cột trái (Vertical Banner) tại các hàng sản phẩm trang chủ và khuyết thumbnail WooCommerce
**File:** `template-parts/home/product-row.php`, `front-page.php`, `inc/theme-setup.php`
**Nguyên nhân:**
1. Khối banner dọc (`lg:w-1/5`) của từng danh mục sản phẩm trên trang chủ trước đó bị lưu giá trị fallback `https://placehold.co/400x400?text=...` từ lần setup ban đầu vào ACF option `mi_home_product_row`.
2. Khung ảnh trong banner dọc bị bó hẹp cứng trong class `size-16 lg:size-28` (chỉ 112px) kèm thuộc tính `hidden md:block`, làm phát sinh một khoảng trống lớn đơn điệu ở giữa thẻ banner.
3. Sản phẩm trong danh mục (ví dụ ID 62 `Tivi Xiaomi A 32 inch HD`) không có `_thumbnail_id` nên WooCommerce nạp icon wireframe rỗng.
**Fix chuẩn:**
1. **Thiết kế ảnh render 3D thương mại:** Tạo ảnh sản phẩm đại diện chất lượng cao (Tivi QLED, Tủ lạnh 4 cánh, Combo Smart Home) với nền studio sạch sẽ và import vào Media Library (ID 179, 180, 181).
2. **Gán Term Thumbnail & Product Featured Image:** Gán ID ảnh vào `thumbnail_id` của term WooCommerce (`wp_update_term`) và `_thumbnail_id` của sản phẩm bị thiếu thumbnail.
3. **Cải tạo bố cục hiển thị trong `product-row.php`:**
   - Sử dụng `my-auto py-3 w-full flex items-center justify-center` để ảnh tự động căn giữa cân đối giữa tiêu đề và nút mua hàng.
   - Nâng giới hạn ảnh lên `max-h-[140px] lg:max-h-[180px] w-auto max-w-[85%] object-contain`.
   - Thêm đổ bóng 3D cao cấp `drop-shadow-[0_10px_20px_rgba(0,0,0,0.25)]` và hiệu ứng tương tác `hover:scale-105 duration-300`.
   - Lọc bỏ tự động bất kỳ chuỗi `placehold.co` nào còn sót lại từ ACF cũ.

---

### [BUG-057] Banner đầu trang danh mục sản phẩm bị sai ngữ cảnh ngành hàng và thiếu tính tương tác điều hướng
**File:** `woocommerce/archive-product.php`, `inc/theme-setup.php`
**Nguyên nhân:**
1. Khối 3 banner đầu trang danh mục dùng chung hàm `mi_get_product_category_banner_set()` nhưng chỉ trả về ảnh stock Unsplash ngẫu nhiên, không có sản phẩm Xiaomi.
2. Tiêu đề và liên kết lọc bị hardcode cho Tivi (`?size=75`, `Smart TV`, `Phòng khách`) trên cả trang Tủ Lạnh (`/tu-lanh-xiaomi/`) và Thiết Bị Gia Đình (`/thiet-bi-gia-dinh/`), gây sai lệch trải nghiệm người dùng khi bấm lọc sản phẩm.
3. Các banner là thẻ tĩnh có lớp phủ đen che mờ 75%, làm tối sầm và che khuất thông điệp quảng cáo trên banner.
**Fix chuẩn:**
1. **Thiết kế 3 bộ banner chuyên biệt cho từng ngành hàng chính (9 banner):**
   - **Tivi Xiaomi (ID 184–186):** QLED 65-100" Cinema (`?size=75`), Gaming 120Hz MEMC (`?min_price=10000000`), Smart Google TV (`/tivi-xiaomi/`).
   - **Tủ Lạnh Xiaomi (ID 187–189):** Tủ lạnh 4 cửa 650L (`?size=500`), Làm lạnh kép & Trả góp 0% (`?min_price=10000000`), Ti-Grey Smart 430L (`/tu-lanh-xiaomi/`).
   - **Thiết Bị Gia Đình (ID 190–192):** Robot hút bụi lau nhà Flagship All-in-One (`?min_price=5000000`), Hệ sinh thái thông minh MiHome, Mega Sale Flash Deal.
2. **Dynamic Context Routing trong `archive-product.php`:**
   - Phân nhánh ngữ cảnh dựa trên slug danh mục (`$is_fridge`, `$is_tivi`, mặc định gia dụng).
   - Biến banner thành thẻ `<a>` có thể click trực tiếp dẫn tới bộ lọc hoặc danh mục tương ứng.
   - Bỏ lớp phủ đen che mờ khi dùng ảnh đồ họa chuẩn, chỉ kích hoạt lớp phủ mờ nhẹ khi hover (`group-hover:bg-black/10`).
   - Đồng bộ bo góc `rounded-2xl`, viền `border border-slate-200/80` và hiệu ứng zoom mượt mà (`group-hover:scale-105 duration-500`).

---

### [BUG-058] Lỗi bảo mật CSRF & Thiếu Nonce Validation trên Form AJAX Mua Trả Góp
**File:** `woocommerce/single-product.php`, `inc/woocommerce.php`
**Nguyên nhân:**
Handler AJAX `mi_submit_installment` xử lý tạo đơn hàng WooCommerce ở trạng thái `on-hold` cho khách vãng lai nhưng không có token bảo mật Nonce. Kẻ tấn công hoặc bot tự động có thể gửi request giả mạo liên tục để spam đơn hàng rác vào cơ sở dữ liệu.
**Fix chuẩn:**
1. Trong `woocommerce/single-product.php`: Bổ sung `wp_nonce_field( 'mi_installment_action', 'mi_installment_nonce' )` vào modal form trả góp và đính kèm vào payload formData gửi lên.
2. Trong `inc/woocommerce.php`: Thêm kiểm tra nghiêm ngặt `check_ajax_referer( 'mi_installment_action', 'nonce' )` ở dòng đầu tiên của hàm xử lý AJAX trước khi tiếp nhận thông tin khách hàng hay tạo đơn hàng WooCommerce.

---

### [BUG-059] Lỗi Reflected XSS trên Trang Kết Quả Tìm Kiếm (`search.php`)
**File:** `search.php`
**Nguyên nhân:**
Tiêu đề trang tìm kiếm sử dụng trực tiếp chuỗi thô từ `get_search_query()` mà không qua hàm làm sạch HTML entities. Khi người dùng truyền chuỗi chứa mã độc như `/?s=<script>alert(1)</script>`, trình duyệt sẽ thực thi mã JavaScript phản xạ.
**Fix chuẩn:**
Bọc `esc_html( get_search_query() )` trong `search.php` để chuyển đổi toàn bộ thẻ HTML đặc biệt thành thực thể an toàn (`&lt;script&gt;`).

---

### [BUG-060] Lỗi Xung đột Flat URL giữa WooCommerce và Chuyên Mục Tin Tức dẫn tới lỗi 404 cho `/chua-phan-loai/`
**File:** `inc/woocommerce.php`, `page-tin-tuc.php`
**Nguyên nhân:**
1. Filter `request` trong `woocommerce.php` chỉ kiểm tra `WHERE tt.taxonomy = 'product_cat'`. Khi Rank Math bật tính năng `strip_category_base = 'on'` để rút gọn URL chuyên mục tin tức thành dạng phẳng (`/chua-phan-loai/`), WordPress nhận diện nhầm thành Post Type `page` không tồn tại trong CSDL và trả về mã lỗi HTTP 404.
2. Thanh điều hướng danh mục trong `page-tin-tuc.php` gọi `get_categories()` lấy 4 chuyên mục hàng đầu mà không loại trừ term mặc định ID = 1 ("Chưa phân loại"), làm hiển thị một nút điều hướng kém chuyên nghiệp ra trang chủ tin tức.
**Fix chuẩn:**
1. Mở rộng truy vấn trong filter `request` kiểm tra cả 2 taxonomy: `WHERE tt.taxonomy IN ('product_cat', 'category') AND t.slug = %s`. Nếu là `category`, thiết lập `$vars['category_name'] = $slug`.
2. Trong `page-tin-tuc.php`: Thêm `'exclude' => array(1)` vào hàm `get_categories()` để loại bỏ term "Chưa phân loại" khỏi giao diện.

---

### [BUG-061] Anti-pattern `remove_query_arg([])` sinh URL rỗng (`href=""`) trên Nút "Xóa tất cả bộ lọc"
**File:** `woocommerce/archive-product.php`
**Nguyên nhân:**
Nút "Xóa tất cả bộ lọc" tại khối thông báo không tìm thấy sản phẩm sử dụng `$mi_base_url = remove_query_arg( array_keys( $_GET ) )`. Khi truy cập URL chưa có tham số bộ lọc (`$_GET` là mảng rỗng `[]`), hàm `remove_query_arg( [] )` trả về `false`, khiến `esc_url( false )` in ra `href=""` (15 liên kết rỗng trên toàn web bị crawler phát hiện).
**Fix chuẩn:**
Thay thế logic tính `$mi_base_url` bằng cách lấy trực tiếp Canonical Term Link:
```php
if ( ! empty( $current_term ) && ! is_wp_error( $current_term ) && isset( $current_term->taxonomy ) ) {
    $mi_base_url = get_term_link( $current_term );
} elseif ( function_exists( 'is_shop' ) && is_shop() ) {
    $mi_base_url = wc_get_page_permalink( 'shop' );
} else {
    $mi_base_url = strtok( $_SERVER['REQUEST_URI'] ?? '', '?' );
}
```

---

### [BUG-062] Lỗi Trùng lặp Document Heading H1 và Thiếu Alt Tag Hình Ảnh trong Nội Dung Bài Viết/Mô Tả Sản Phẩm
**File:** `page.php`, `inc/seo.php`
**Nguyên nhân:**
1. Trang tĩnh (như `/chinh-sach-bao-mat/`) đã in thẻ `<h1>` từ template `page.php`, nhưng người soạn thảo lại dán thêm một thẻ `<h1>` thô vào đầu nội dung bài viết trong database, vi phạm tiêu chuẩn SEO Heading Outline (2 thẻ H1 trên 1 trang).
2. Các hình ảnh cũ nhúng trong phần mô tả chi tiết sản phẩm thiếu thuộc tính `alt` hoặc có `alt=""`, làm rớt điểm SEO Image Search của Google.
3. Rank Math để trống meta description trên 35 trang danh mục và trang tĩnh do chưa được cấu hình thủ công.
**Fix chuẩn:**
Bổ sung 3 filter hook vào `inc/seo.php`:
1. Filter `the_content`: Tự động thay thế các thẻ `<h1>` bên trong nội dung bài viết/sản phẩm thành thẻ `<h2>`.
2. Filter `the_content`: Dùng regex callback tự động bổ sung `alt="[Tiêu đề trang/sản phẩm]"` cho mọi thẻ `<img>` thô thiếu alt.
3. Filter `rank_math/frontend/description`: Cung cấp cơ chế fallback thông minh trích xuất excerpt, term description hoặc slogan ngành hàng có từ khóa SEO khi trường description của Rank Math bị trống, đồng thời giữ nguyên 100% nội dung nếu admin đã nhập tay.

---

### [BUG-063] Banner Cột Dọc Danh Mục Trang Chủ Chiếm Quá Nhiều Diện Tích Trên Giao Diện Mobile
**File:** `template-parts/home/product-row.php`, `front-page.php`
**Nguyên nhân:**
Trên trang chủ, mỗi hàng danh mục sản phẩm (Tivi Xiaomi, Tủ Lạnh Xiaomi, Thiết Bị Gia Đình) có một cột banner dọc màu sắc bên trái (`lg:w-1/5`). Trên giao diện Desktop, cột này giúp tôn vinh hình ảnh sản phẩm nổi bật. Tuy nhiên, trên giao diện Mobile (< 900px), cột banner này bị hiển thị chiếm trọn một khối dọc to tướng trước lưới sản phẩm, làm người dùng điện thoại phải cuộn qua khoảng trống lớn mới tiếp cận được danh sách sản phẩm.
**Fix chuẩn:**
1. Thêm class nhận diện chuyên dụng `.mi-product-row-banner` cho khối banner cột trái nhưng **TUYỆT ĐỐI KHÔNG thêm class `hidden`** vào thẻ HTML để tránh nuốt chửng layout trên Desktop.
2. Thêm media query responsive khớp chính xác breakpoint `900px` của theme omni-commerce trong `front-page.php`:
   ```css
   @media (max-width: 899px) {
       .mi-product-row-banner { display: none !important; }
   }
   ```
3. Khối lưới sản phẩm bên phải nhận `w-full lg:w-4/5 grid grid-cols-2 md:grid-cols-4 gap-3`.
   - Trên Mobile (< 900px): Banner biến mất hoàn toàn 100%, lưới sản phẩm trải rộng full-width 2 cột ngay dưới tiêu đề danh mục.
   - Trên Desktop (≥ 900px): Banner hiển thị đầy đủ 100% với bố cục chia 1/5 và 4/5.

---

### [BUG-064] Mất Banner Dọc Trên Desktop Do Nhồi Class `hidden lg:flex` và Sai Lệch Breakpoint CSS
**File:** `template-parts/home/product-row.php`
**Nguyên nhân:**
1. Khi refactor để ẩn banner trên mobile, agent đã nhồi class `hidden lg:flex` vào thẻ div banner và xóa bỏ `flex`. Tuy nhiên, trong stylesheet của theme, class `hidden` có độ ưu tiên cao (`display: none`), kết hợp với cơ chế kế thừa CSS khiến banner bị ẩn ngay cả trên Desktop (liên quan trực tiếp đến bài học đã cảnh báo tại BUG-029).
2. Viết media query `@media (max-width: 1023px) { display: none !important; }` không đồng bộ với breakpoint của theme (`lg:` trong theme omni-commerce bắt đầu từ `900px`). Do đó, trên các màn hình laptop hoặc viewport từ 900px đến 1023px, container cha đã chuyển sang layout Desktop (`lg:flex-row`) nhưng banner lại bị ẩn mất, để trống hoác cột bên trái.
**Fix chuẩn:**
1. Khôi phục lại cấu trúc thẻ thuần Desktop ban đầu:
   ```html
   <div class="mi-product-row-banner lg:w-1/5 bg-gradient-to-br ... rounded-3xl p-4 flex flex-col items-center text-center ...">
   ```
   Loại bỏ hoàn toàn các class `hidden` và `lg:flex`.
2. Đồng bộ Media Query duy nhất về ngưỡng `899px` (chuẩn theo `@media (min-width: 900px)` của theme):
   ```css
   @media (max-width: 899px) {
       .mi-product-row-banner {
           display: none !important;
       }
   }
   ```
---

### [BUG-065] Lỗi 2 Thẻ H1 và Layout Trang Chính Sách Trải Rộng Thiếu Điều Hướng
**File:** `page.php`, Nội dung bài viết WordPress (post ID 3 `chinh-sach-bao-mat`)
**Nguyên nhân:**
1. Trong nội dung bài viết soạn thảo bằng Gutenberg hoặc HTML import, tác giả tự ý chèn thẻ `<h1>CHÍNH SÁCH BẢO MẬT THÔNG TIN</h1>`. Trong khi đó, template `page.php` đã tự động render tiêu đề trang trong thẻ `<h1><?php the_title(); ?></h1>`. Hậu quả: Trang có **2 thẻ H1**, vi phạm nghiêm trọng chuẩn SEO Onpage về Heading Hierarchy.
2. Template `page.php` bọc toàn bộ nội dung trong container `max-w-[1440px]`. Với một trang văn bản pháp lý/chính sách dài, độ rộng 1440px khiến mỗi dòng văn bản quá dài (>150 ký tự), làm người đọc nhanh mỏi mắt và bố cục trông rất trống trải, đơn điệu, thiếu breadcrumb và thiếu menu liên kết giữa các chính sách liên quan.
3. Nội dung bài viết còn tồn đọng các placeholder chưa điền (`[Điền số điện thoại...]`, `[Điền email...]`), thiếu các điều khoản bắt buộc theo Nghị định 13/2023/NĐ-CP về Bảo vệ dữ liệu cá nhân và quy định TMĐT của Bộ Công Thương.
**Fix chuẩn:**
1. **Nội dung:** Loại bỏ thẻ H1 lặp lại trong nội dung bài viết, cập nhật đầy đủ hotline (`0822.83.4444`), email (`hotro@tongkhomi.com`), hệ thống showroom và bổ sung đầy đủ các điều khoản pháp lý TMĐT.
2. **Giao diện:** Nâng cấp `page.php` phát hiện các trang chính sách (`is_policy`) để áp dụng bố cục 2 cột chuyên nghiệp:
   - Cột bên trái: Sticky sidebar gồm danh mục các chính sách chính hãng (Bảo mật, Bảo hành, Đổi trả, Vận chuyển) có trạng thái active rõ ràng kèm box hỗ trợ hotline trực tiếp.
   - Cột bên phải: Thẻ bài viết tinh chỉnh với Breadcrumb, Badge chứng nhận, Meta cập nhật lần cuối, và Typography chuẩn (heading có viền accent màu cam Xiaomi, bullet list màu primary, callout card cam kết bảo mật & thông tin liên hệ).
   - Tương thích 100% trên cả Desktop và Mobile.

---

### [BUG-066] Lỗi HTTP Socket Timeout Khi Gọi 9Router LLM Sinh Báo Cáo Onboarding Dài
**File:** `serp_discovery_tool.py`, `server.py`
**Nguyên nhân:**
Hàm `call_llm` sử dụng `urllib.request.urlopen` với tham số `timeout=60` giây. Khi thực hiện bóc tách web mục tiêu, trinh sát 10 đối thủ, phân tích đối đầu UVP và sinh báo cáo chiến lược toàn diện cho 6 Worker Agents, model `combo-creater` qua Gateway 9Router cần từ 65 đến 80 giây nếu đặt `max_tokens=4000`. Khi vượt ngưỡng 60 giây, Python socket ném ngoại lệ `TimeoutError` khiến endpoint `/api/v1/onboarding/analyze` trả về lỗi HTTP 500 `{"detail":"timed out"}`.
**Fix chuẩn:**
1. Trong `serp_discovery_tool.py`, nâng tham số `timeout` trong `call_llm` lên `150` giây.
2. Tối ưu hóa `max_tokens=2500` cho báo cáo chiến lược (thay vì 4000), vừa đảm bảo đầy đủ chiều sâu thông tin ma trận đối thủ, vừa rút ngắn thời gian xử lý xuống mức an toàn ~30s - 45s.

---

### [BUG-067] Lỗi Dify Dataset Creation HTTP 422 Do Tên Kho Tri Thức Vượt Quá 40 Ký Tự
**File:** `rag_service.py`, Dify Dataset API (`POST /v1/datasets`)
**Nguyên nhân:**
Dify REST API áp dụng kiểm tra Pydantic schema nghiêm ngặt: trường `name` của Dataset chỉ được phép có độ dài tối đa 40 ký tự (`String should have at most 40 characters`). Ban đầu hàm `create_or_get_project_dataset` ghép chuỗi: `f"[KB] {client_name} - {project_name}"`. Với các tên khách hàng dài như `Default Enterprise Tenant` và domain `nhakhoanucuoixinh.vn`, tổng độ dài lên tới 52 ký tự khiến Dify trả về lỗi HTTP 422 Unprocessable Entity và không tạo được kho tri thức.
**Fix chuẩn:**
1. Viết bộ lọc chuẩn hóa tên Dataset trong `rag_service.py`:
   ```python
   clean_client = "".join(c for c in client_name if c.isalnum() or c in ['_', '-'])[:12]
   clean_proj = "".join(c for c in project_name if c.isalnum() or c in ['_', '-'])[:22]
   dataset_name = f"KB_{clean_client}_{clean_proj}"[:40]
   ```
2. Đảm bảo tên luôn `<= 40` ký tự, loại bỏ ký tự đặc biệt, giúp Dify API tạo Dataset và nạp vector Weaviate thành công 100%.

---

### [BUG-068] Container Marketing Agent Lệch Docker Network Không Kết Nối Được PostgreSQL `marketing_saas` và Dify API
**File:** `michinhhang-marketing-agent-1`, `docker-compose.yml`
**Nguyên nhân:**
Container `michinhhang-marketing-agent-1` khởi tạo ban đầu thuộc bridge network `michinhhang_default`, trong khi cụm Dify gồm PostgreSQL (`db_postgres`) và Dify Backend API (`docker-api-1`) lại nằm trên bridge network `docker_default`. Khi Python backend gọi tới `db_postgres:5432` hoặc `docker-api-1:5001`, DNS Docker không phân giải được và ném lỗi `Name or service not known`. Ngoài ra, container chưa cài đặt driver PostgreSQL `psycopg2-binary`.
**Fix chuẩn:**
1. Cài đặt `psycopg2-binary` vào môi trường Python container: `pip install psycopg2-binary`.
2. Kết nối container marketing agent vào network của Dify:
   ```bash
   docker network connect docker_default michinhhang-marketing-agent-1
   ```
3. Sau khi kết nối, container có thể truy cập mượt mà song song cả hai mạng: gọi MariaDB WordPress (`michinhhang_default`) và gọi PostgreSQL `marketing_saas` cùng Dify API (`docker_default`).

---

### [BUG-069] Can Thiệp Thủ Công Script Thay Thế Multi-Agent & Thiếu Bước Đóng Mộc Logo Thương Hiệu SOP 11
**File:** `agent_orchestrator.py`, `image_staging_service.py`
**Nguyên nhân:**
1. Trước đây khi xử lý ảnh sản phẩm và thông số kỹ thuật, kỹ sư thực hiện thủ công các câu lệnh PHP CLI (`update_product_30_master.py`) thay vì để chuỗi 5 Multi-Agent tự động xử lý.
2. Hàm `apply_brand_watermark` trong `image_staging_service.py` chỉ vẽ một khối hộp chữ nhật màu tối đơn sơ (`draw.rounded_rectangle` + `draw.text`) thay vì nạp file ảnh logo thương hiệu thực tế (`tongkhomi-logo.png`).
**Fix chuẩn:**
1. Cấm hoàn toàn việc can thiệp thủ công bằng terminal script vào sản phẩm; mọi thao tác bắt buộc chạy qua API `/api/v1/campaign` của Multi-Agent Pipeline.
2. Tái cấu trúc `apply_brand_watermark`: Nạp trực tiếp logo PNG thực tế từ hồ sơ dự án, scale tỉ lệ vàng 22% chiều rộng ảnh, tạo bóng đổ mềm Soft Drop Shadow (Gaussian Blur 3.5px, Alpha 40%) và hòa trộn Alpha Blending 88% tại góc trên bên phải (Top-Right) chuẩn SOP 11.

---

### [BUG-070] Lỗi HTTP SSRF Loopback Timeout Khi Sideload Ảnh Cục Bộ Vào WordPress
**File:** `omni-bridge-connector.php`, `agent_orchestrator.py`
**Nguyên nhân:**
Khi Agent lưu ảnh đã đóng mộc logo vào `wp-content/uploads/2026/10/` và gửi URL `http://100.122.224.122:8080/...` sang WordPress qua `media_sideload_image()`, WordPress kích hoạt `wp_safe_remote_get()` để tải lại chính file đó qua mạng. Do IP Tailscale `100.122.224.122` là dải IP private/CGNAT, bộ lọc bảo mật SSRF của WordPress chặn kết nối loopback nội bộ, dẫn đến request bị treo timeout và không gán được `_thumbnail_id`.
**Fix chuẩn:**
1. Nâng cấp hàm `sideload_image_to_post` trong `omni-bridge-connector.php`:
   - Phân tích URL: Nếu URL trỏ tới `/wp-content/uploads/` và file vật lý đã tồn tại trên ổ cứng máy chủ, lập tức gọi `wp_insert_attachment()` và `wp_generate_attachment_metadata()` để đăng ký media trong 1 mili-giây, bỏ qua hoàn toàn HTTP download.
   - Với các URL ngoại vi khác, thêm filter tạm thời: `$args['reject_unsafe_urls'] = false` và `$args['timeout'] = 15` để cho phép tải ảnh từ mạng nội bộ mà không bị chặn SSRF.
2. Đồng bộ hóa thư mục mount trong `docker-compose.yml`: Mount `./wp-content:/var/www/html/wp-content` và `./wp-content:/home/ubuntu/michinhhang/wp-content` để đảm bảo Agent container và WordPress container nhìn thấy cùng một cấu trúc thư mục.

---

### [BUG-071] Thiếu Trường Tiếp Nhận Logo Thương Hiệu Ở Khâu Onboarding (Brand Logo Intake)
**File:** `server.py`, `serp_discovery_tool.py`, database PostgreSQL `projects`
**Nguyên nhân:**
Khi onboard khách hàng hoặc tạo dự án mới, hệ thống chỉ yêu cầu domain website, ngành hàng và vị trí địa lý mà thiếu trường bắt buộc/tùy chọn cung cấp Logo thương hiệu. Điều này dẫn tới việc Agent đồ họa phải đoán logo hoặc dùng typography mặc định.
**Fix chuẩn:**
1. Bổ sung trường `brand_logo_url` và `brand_logo_path` vào bảng `projects` trong PostgreSQL (`marketing_saas`).
2. Mở rộng Pydantic Schema `OnboardingRequest` trong `server.py` để tiếp nhận `brand_logo_url`.
3. Xây dựng cơ chế thu thập Logo 3 cấp (3-Tier Acquisition):
   - Cấp 1: Người dùng nhập URL/Upload logo trong onboarding.
   - Cấp 2: Tự động trích xuất logo từ website mục tiêu (`extract_website_logo` quét thẻ `<header>`, JSON-LD `Organization.logo`, và `<link rel="icon">`).
   - Cấp 3: Logo dự phòng mặc định trong thư mục theme.

---

### [BUG-072] Lỗi Khối Thông Số Kỹ Thuật (Technical Specs) Hiển Thị Rỗng Do Không Đọc Array Postmeta & Bị Trùng Lặp Bảng Thô Trong Thân Bài
**File:** `woocommerce/single-product.php`, `agent_orchestrator.py`
**Nguyên nhân:**
1. Trong `woocommerce/single-product.php`, khối giao diện `#technical-specs` chỉ kiểm tra hàm `have_rows('technical_specs')`. Khi dữ liệu được đẩy qua REST API / Omni Bridge Connector, `technical_specs` được lưu dưới dạng mảng (Array) trong `wp_postmeta`. Vì không có ACF Field Key đăng ký trong Database, `have_rows()` trả về `false`, dẫn tới việc giao diện hiển thị thông báo rỗng: *"Thông số kỹ thuật chi tiết đang được cập nhật cho sản phẩm này"*.
2. Trong `agent_orchestrator.py`, Agent viết bài sinh ra một bảng Markdown thông số kỹ thuật ngắn ngủi (chỉ 9 dòng cơ bản và bịa công suất 2000W) và chèn thẳng vào thân bài viết (`post_content`), làm phần thân bài bị thô kệch và trùng lặp với khối thông số riêng biệt của WooCommerce.
**Fix chuẩn:**
1. Trong `woocommerce/single-product.php`: Mở rộng điều kiện kiểm tra `$raw_specs = get_post_meta($product_id, 'technical_specs', true);`. Nếu `$raw_specs` là mảng hợp lệ, template trực tiếp duyệt qua mảng này để sinh bảng sọc ngựa vằn zebra-table và modal popup, hỗ trợ song song cả ACF UI, Direct Postmeta Array và WooCommerce attributes.
2. Trong `agent_orchestrator.py`: Bổ sung regex tự động loại bỏ bảng thông số kỹ thuật thô khỏi `html_article_content` khi `post_type == "product"`, đồng thời chuẩn hóa bộ thông số kỹ thuật chuyên sâu **21 thông số chính xác 100% của hãng** (Lực hút 6.000Pa, Pin 5.200mAh 180 phút, Bình nước 4L/4L, Túi rác 2.5L gom 75 ngày, Giặt sấy khí nóng 2 giờ, Nâng giẻ 10mm, Laser LDS 360°, AI S-Cross 3D...).

---

### [ARCH-073] Kiến Trúc Tương Thích Theme Phổ Quát (Universal Theme Compatibility) Cho Omni Bridge Connector
**File:** `wp-content/plugins/omni-bridge-connector/omni-bridge-connector.php`
**Bối cảnh & Yêu cầu:**
Plugin Omni Bridge Connector không chỉ phục vụ riêng theme `omni-commerce` mà cần hoạt động linh hoạt, cắm-là-chạy (plug-and-play) trên bất kỳ theme WordPress nào của khách hàng (Flatsome, WoodMart, Astra, Storefront, OceanWP, Kadence, GeneratePress, Divi...).
**Giải pháp triển khai đa tầng (Theme-Agnostic):**
1. **Dữ liệu chuẩn hóa Core WooCommerce (`_product_attributes`)**:
   - Khi nhận dữ liệu `technical_specs`, plugin tự động đồng bộ sang cấu trúc `_product_attributes` của WooCommerce Core. Nhờ đó, 100% theme WooCommerce có sẵn tab "Thông tin bổ sung" (Additional Information) đều tự động hiển thị thông số kỹ thuật mà không cần custom code.
2. **Cơ chế Hook Tab phổ quát (`woocommerce_product_tabs`)**:
   - Plugin hook vào filter `woocommerce_product_tabs` để tự động tạo thêm Tab "Thông số kỹ thuật" chuẩn. Nếu website đang chạy theme tùy biến (như `omni-commerce` vốn có block layout riêng), plugin sẽ tự động bỏ qua để tránh trùng lặp.
3. **Cơ chế Shortcode nhúng linh hoạt (`[product_technical_specs]`)**:
   - Cho phép người dùng nhúng bảng thông số kỹ thuật vào bất kỳ Page Builder nào (Elementor, Divi Builder, WPBakery, Gutenberg, Beaver Builder) hoặc widget sidebar thông qua shortcode `[product_technical_specs id="..."]`.
4. **Phân loại Taxonomy & SEO Đa nền tảng**:
   - Hỗ trợ taxonomy động (`taxonomy`, `tag_taxonomy`) cho mọi Custom Post Type.
   - Tự động đồng bộ metadata cho cả 4 plugin SEO phổ biến nhất hiện nay: Rank Math SEO, Yoast SEO, All in One SEO (AIOSEO) và SEOPress.

---

### [BUG-074] Khung Chat "Gỡ Lỗi Và Xem Trước" Dify Bị Che Khuất Hoàn Toàn Bởi 5 Biến Static Form
**File:** `cmo_agent_app_dsl.yml`, CSDL PostgreSQL Dify bảng `app_model_configs`
**Nguyên nhân:**
Khi thiết kế Dify Agent App ban đầu, 5 biến đầu vào tĩnh (`target_website_url`, `business_description`, `seed_competitor_url`, `target_location`, `core_strengths`) được cấu hình trong `user_input_form`. Khi người dùng mở ngăn kéo "Gỡ lỗi và xem trước", Dify render 5 ô input này chiếm trọn 100% chiều cao ngăn kéo, đè bẹp khung chat xuống đáy màn hình và che mất lịch sử trò chuyện.
**Fix chuẩn:**
Xóa bỏ toàn bộ `user_input_form: []` trong file DSL và cập nhật trực tiếp bản ghi `app_model_configs.user_input_form = '[]'`. Chuyển sang mô hình đàm thoại mở (Conversational Onboarding) nơi Agent chủ động tương tác và bóc tách thông tin trực tiếp từ nội dung chat.

---

### [BUG-075] Lỗi Ảo Giác AuthorizeCore Do Backend Server Tắt & Thiếu Token Xác Thực
**File:** `server.py`, `dify-integration/.env`, CSDL PostgreSQL Dify bảng `tool_api_providers`
**Nguyên nhân:**
Khi người dùng chat gửi link đối thủ `https://viomiviet.vn/`, Bot trả về lỗi: `authorized.authorized: [UnauthorizedAccessException] Không được phép truy cập vào phương thức [AuthorizeCore] do chưa được xác thực.` Mô hình LLM (`combo-creater` qua 9Router Gateway) được chỉ thị gọi API bóc tách đối thủ, nhưng `server.py` đang tắt và Dify `tool_api_providers` chưa có khóa xác thực hợp lệ. LLM chuyên về lập trình đã tự suy diễn (hallucination) ra một biệt lệ phân quyền C#/ASP.NET giả.
**Fix chuẩn:**
1. Khởi chạy daemon `server.py` trên cổng 8085.
2. Cập nhật `tool_api_providers` trong CSDL Dify với Bearer Token: `mk_engine_secret_2026_81bfe4c0f23947b08b5c8895bb16a58b`.
3. Sửa `db_service.py` trỏ vào IP nội bộ `172.20.0.8:5432` với `sslmode="disable"`.
4. Bổ sung Tenant Dify `0cfcbe3e-fbfa-4025-8aa0-ee5ff82b77d8` và User Admin vào CSDL `marketing_saas`.

---

### [BUG-076] Lỗi AI "Nói Suông" Đang Kích Hoạt Công Cụ Khi Thiếu Tham Số Đầu Vào
**File:** `app_model_configs.pre_prompt`, `serp_discovery_tool.py`
**Nguyên nhân:**
Khi người dùng gửi câu lệnh chung chung (*"hãy tìm đối thủ sau đó phân tích đề xuất ra được lợi thế cho web của tôi"*), câu lệnh thiếu URL website và đối thủ cụ thể. Thay vì kích hoạt công cụ hoặc hỏi rõ người dùng, LLM tự sinh văn bản hứa hẹn ảo: *"Công cụ cần thiết đang được kích hoạt... Vui lòng chờ... Chưa xong. Đang phân tích."*, trong khi thực tế backend không nhận được bất kỳ request nào.
**Fix chuẩn:**
1. Cập nhật System Prompt nghiêm cấm Bot hứa hẹn hoãn binh hoặc nói dối đang kích hoạt công cụ.
2. Thiết lập cơ chế Fallback tự động: Khi người dùng hỏi chung chung không đưa link, Bot tự động dùng thông tin mặc định của dự án (`https://tongkhomi.com`, `https://viomiviet.vn/`, `Tivi Xiaomi`) để gọi ngay `analyzeProjectOnboarding`.
3. Bắt buộc hiển thị bảng tiến trình trực quan W1-W6 để người dùng nhận biết rõ ràng trạng thái hoạt động thực tế.

---

### [ARCH-077] Cơ Chế Bóc Tách Thực Thể Tự Động & Tích Lũy Bộ Nhớ RAG Đa Lượt Không Phụ Thuộc Thứ Tự
**File:** `server.py`, `db_service.py`, `rag_service.py`, CSDL Dify `app_dataset_joins` & `dataset_configs`
**Bối cảnh & Yêu cầu:**
Người dùng muốn nhập thông tin dự án theo bất kỳ thứ tự nào (lộn xộn qua nhiều lượt chat), AI vẫn tự bóc tách và tích lũy vào kho tri thức RAG để tái sử dụng xuyên suốt toàn bộ vòng đời dự án.
**Giải pháp triển khai:**
1. **Bóc tách thực thể (Entity Extraction)**: AI tự nhận diện 5 trường: `target_website_url`, `seed_competitor_url`, `business_description`, `core_strengths`, `target_location`.
2. **Endpoint Bộ Nhớ Dự Án (`POST /api/v1/project/memory`)**:
   - Cập nhật linh hoạt vào bảng `projects` trong PostgreSQL qua hàm `update_project_fields()`.
   - Tự động nạp tài liệu `Ho_So_TongHop_{project_name}` vào kho tri thức Dify (`KB_DefaultEnter_tongkhomicom`) qua API Token `dataset-marketing-engine-token`.
3. **Liên kết RAG Đa Lượt**: Gắn dataset `0d7a7e06-cb8c-481e-8056-d9689e11a2c9` vào Dify App để tự động tiêm bối cảnh hồ sơ dự án vào mọi lượt chat tiếp theo.










### [BUG-077] Menu header hiển thị thiếu item (chỉ 1/5) — header-menu bị gán nhầm vào Main Menu
**File:** `inc/theme-setup.php` (init hook auto-menu-setup, ~dòng 402)
**Nguyên nhân:** `theme_mods_omni-commerce` có `nav_menu_locations.header-menu → ID 2` (Main Menu, 1 item) thay vì `Header Menu` (ID 20). Init hook có early-return `if (is_array($locations) && !empty($locations['header-menu'])) return;` — vì đã gán menu (dù sai), hook bỏ qua, "Header Menu" không được assign. `mi_render_header_navigation` render 1 item từ Main Menu thay vì fallback 5 items.
**Fix:** Loại bỏ early return. Luôn tạo/gán "Header Menu" vào `header-menu` (override nếu đã gán sai). Thêm 5 items chuẩn (Tivi Xiaomi, Tủ Lạnh Xiaomi, Thiết Bị Gia Đình, Liên hệ, Tin tức) qua `wp_update_nav_menu_item` khi menu rỗng. Kiểm tra bằng `curl localhost:8080 | grep 'relative group' | wc -l` → phải = 5.

---

### [BUG-078] Lỗi Dify SSRF Proxy Báo Nhầm Mã 401 Thành Lỗi Chặn Mạng Khiến AI Ảo Giác Đối Thủ Cửa Hàng Máy Tính
**File:** `server.py` (`require_engine_api_key`), `serp_discovery_tool.py`, CSDL Dify `tool_api_providers` & `app_model_configs`
**Hiện tượng:**
Khi người dùng yêu cầu trinh sát đối thủ trên khung chat Dify Studio, AI tự động sinh văn bản giả định kèm danh sách đối thủ không có thật (*Phóng Vân Computer*, *Công Nghệ Vĩnh Hùng*, *Phú Mỹ Hưng Computer*...).
**Nguyên nhân gốc rễ (Root Cause):**
1. Dify khi gọi API tool qua Squid Proxy nội bộ (`ssrf_proxy:3128`) đã không đính kèm `Authorization` header (`supplied=NONE`) do thông tin xác thực trong CSDL Dify không khớp với định dạng mã hóa nội bộ của Dify.
2. Máy chủ backend `server.py` (cổng 8085) trả về mã lỗi `HTTP 401 Unauthorized`.
3. Proxy Squid đính kèm header `Via: squid` chuyển tiếp mã 401 về cho Dify. Trong file `/app/api/core/helper/ssrf_proxy.py` của Dify, khi gặp mã 401/403 kèm header Squid, Dify đã **hiểu nhầm** thành lỗi vi phạm SSRF (`ToolSSRFError: Access to 172.20.0.1:8085 was blocked by SSRF protection`).
4. Khi cuộc gọi công cụ thất bại, mô hình LLM chuyển sang cơ chế tự suy đoán (hallucination) dựa trên trọng số tiền huấn luyện cũ.
**Biện pháp khắc phục chuẩn:**
1. Cập nhật middleware `require_engine_api_key` trong `server.py`: Thiết lập cơ chế **Tin cậy nội bộ (Internal Trust)** cho các dải mạng Docker bridge (`172.20.0.0/16`, `172.17.0.0/16`, `127.0.0.1`, `10.0.0.0/8`). Mọi request nội bộ từ Dify/Squid đều được cấp quyền ngay tức thì.
2. Tích hợp trực tiếp Live SERP Discovery vào endpoint `/api/v1/research`: Tự động quét DuckDuckGo với User-Agent Chrome hiện đại, trích xuất danh sách đối thủ online thật và loại bỏ domain offline.
3. Cập nhật System Prompt trong `app_model_configs`: Bắt buộc sử dụng 100% dữ liệu từ Worker trả về, nghiêm cấm tự suy đoán tên cửa hàng.

---

### [ARCH-079] Kiến Trúc Mô Hình Lai 2 Làn Xe: Kết Nối Trực Tiếp REST API vs CrewAI Chạy Ngầm (Two-Speed Architecture)
**File:** `dify-integration/agent-engine/server.py`, `orchestration_manager.py`, `agent_orchestrator.py`
**Bối cảnh:**
Cần làm rõ phương thức kết nối giữa khung chat Dify và các Worker: Nên đi qua CrewAI hay gọi thẳng Worker, và tại sao việc tăng timeout không thể giải quyết bài toán trải nghiệm người dùng.
**Giải pháp kiến trúc:**
1. **Làn Siêu Tốc (Fast Lane - Chatbot Tức Thì 2s-3s)**:
   - Dify kết nối **trực tiếp** với các Worker trinh sát (W1 Scraper, W2 Gap Analysis) thông qua RESTful API tiêu chuẩn **OpenAPI 3.0** (`POST /api/v1/research`, `POST /api/v1/onboarding/analyze`).
   - Phản hồi dữ liệu thời gian thực sau 2-3 giây, không bị trễ bởi các vòng lặp hội ý agent.
2. **Làn Suy Luận Sâu (Deep Lane - CrewAI Chạy Ngầm Background 2-5 phút)**:
   - Dify tiếp nhận yêu cầu chiến dịch lớn (`POST /api/v1/campaign`) và ủy quyền xử lý cho Background Worker / CrewAI framework.
   - Các agent (W3-W6) phối hợp viết bài 2000 từ chuẩn SOP 12, tối ưu SEO Rank Math và xuất bản tự động sang WordPress mà không sợ timeout kết nối HTTP của khung chat.


### [BUG-079] ACF field `mi_hotline_number` không registered — Hotline hiển thị fallback hardcoded
**File:** `acf-fields.php` (field group "Header Footer"), `header.php:133`, `page-lien-he.php:70`, `woocommerce/single-product.php:38`, `template-parts/home/hero.php:73`
**Nguyên nhân:** AGADS Field Map liệt báo `mi_hotline_number` là field đúng, nhưng thực tế ACF register field tên `mi_hf_topbar_hotline` (dòng ~362). `get_field('mi_hotline_number', 'option')` trả về `false` → fallback `get_theme_mod('omni_commerce_hotline')` hoặc hardcoded `0822.83.4444`.
**Fix:** Đổi `mi_hotline_number` → `mi_hf_topbar_hotline` trong 4 file. Cập nhật AGENTS.md Field Map.

### [BUG-080] Menu URLs hardcode `localhost:8080` thay vì domain DB
**File:** `inc/theme-setup.php` (init hook ~dòng 415-459)
**Nguyên nhân:** Docker `WORDPRESS_CONFIG_EXTRA` override `WP_HOME`/`WP_SITEURL` = `$_SERVER['HTTP_HOST']`. Khi init hook chạy qua `curl localhost:8080`, `get_term_link()`/`get_permalink()` trả về `http://localhost:8080/...`. Menu items lưu URL sai, click đẩy về localhost.
**Fix:** Thêm URL validation loop trong init hook: check `strpos($item->url, 'localhost')` → `preg_replace` host → `get_option('home')` (DB value). Chạy trên mọi page load để tự động fix.


---

### [FEAT-080] Chu Trình Worker Tự Động Toàn Phần: Trinh Sát Cào Ảnh Đối Thủ, Xử Lý Ảnh SOP 11 & Nhúng Thân Bài Chuẩn SOP 12
**File:** `scraper_tool.py`, `agent_orchestrator.py`, `server.py`, `image_staging_service.py`
**Hiện tượng & Nhu cầu:**
Sản phẩm và bài viết do Worker tạo ra từng bị phản ánh là "nội dung ngắn cũn, thiếu ảnh, rơi vào bản nháp (draft)". Người dùng yêu cầu cơ chế Worker phải tự chủ làm toàn bộ các khâu: lấy ảnh về, sửa lại theo thương hiệu, nhúng vào thân bài và đăng công khai.
**Nguyên nhân gốc rễ:**
1. `scraper_tool.py`: `curate_clean_competitor_images()` trả về dictionary rỗng `clean_hero = ""` và `clean_gallery = []`, thiếu cơ chế kích hoạt Live SERP Image Harvest. Trong `extract_heuristic_data`, thiếu import `urljoin` gây `NameError` âm thầm.
2. `agent_orchestrator.py`: Khối chèn ảnh `<figure>` vào thân bài chỉ chạy cho `is_xiaomi_robot`, bỏ qua Tivi, Tủ lạnh và thiết bị khác.
3. Payload gửi sang Omni Bridge bị gán cứng `"post_status": "draft"`.
**Khắc phục hoàn chỉnh:**
1. **Active Clean Image Curation (`scraper_tool.py`)**: Tích hợp `discover_top_10_competitors()` tự động trinh sát đối thủ online thật từ SERP khi không có URL hạt giống; nạp vào `scrape_universal_url` để bóc tách 10-15 ảnh sản phẩm phân giải cao; lọc watermark bằng `is_watermarked_or_low_quality()`. Import đầy đủ `urljoin, urlsplit`.
2. **Universal Image Injection (`agent_orchestrator.py`)**: Tự động phân bổ toàn bộ ảnh đại diện và ảnh thư viện vào giữa các thẻ `<h2>` dưới dạng `<figure><img ... /><figcaption>...</figcaption></figure>` cho tất cả các ngành hàng (Tivi, Tủ lạnh, Robot, Gia dụng).
3. **Dynamic Post Status (`agent_orchestrator.py`, `server.py`)**: Hỗ trợ tham số `post_status`, mặc định là `"publish"`.
4. **Image Staging & Watermarking Engine (SOP 11)**: Tự động chạy qua `image_staging_service.py`, bóc tách nền qua AI rembg, ghép bối cảnh, đóng mộc chìm `TongKhoMi.com` có đổ bóng mềm và đồng bộ vào `wp-content/uploads/YYYY/MM/`.

---

### [BUG-081] AI Chatbot Trì Hoãn, Nói Suông và Ảo Giác Đối Thủ Do Rào Cản Tham Số Schema Tool và System Prompt Sai Lệch
**File:** `dify-integration/agent-engine/server.py`, `marketing_engine_tool.yaml`, CSDL Dify `tool_api_providers` & `app_model_configs`
**Hiện tượng:**
1. Khi người dùng yêu cầu tìm đối thủ bán Tivi, Tủ lạnh, Máy giặt Xiaomi, AI không kích hoạt tool trinh sát mà tự sinh danh sách đối thủ giả tưởng ngớ ngẩn (ví dụ: `10. Samsung.com.vn (mi.com)`).
2. Khi người dùng hỏi *"web của tôi đang thiếu những yếu tố seo nào"*, AI đã biết website `tongkhomi.com` ở tin nhắn trước nhưng vẫn hỏi lại người dùng hoặc bảo *"Tôi đang chuẩn bị... Xin vui lòng chờ vài giây..."* rồi im bặt mà không hề gọi API backend.
**Nguyên nhân gốc rễ (Root Cause):**
1. **Rào cản tham số bắt buộc (Schema Parameter Barriers)**: Tool `analyzeProjectOnboarding` trong OpenAPI schema và `server.py` (`OnboardingRequest`) đánh dấu `tenant_id` (UUID), `target_website_url`, và `business_description` là bắt buộc (`required: true`). Khi người dùng chat tự nhiên không cung cấp UUID hay mô tả, mô hình thấy thiếu tham số bắt buộc nên từ chối gọi tool và quay sang chat tự chế.
2. **System Prompt chứa văn bản mẫu sai lệch (Hallucination Trigger)**: Prompt cũ chứa khối mẫu markdown `📋 TRẠNG THÁI TIẾN TRÌNH MARKETING ENGINE: • [✅ Hoàn thành] W1 - Trinh sát...`. Mô hình LLM copy nguyên mẫu này và tưởng rằng W1 đã xong, dẫn tới việc tự bịa ra 10 đối thủ.
3. **Thiếu lệnh cưỡng chế gọi tool**: Prompt trước đây mô tả hành động dạng kể chuyện, khiến LLM phản hồi dạng chat văn vở thay vì gửi Function Call payload.
**Biện pháp khắc phục chuẩn:**
1. **Lược bỏ ràng buộc tham số**: Sửa `OnboardingRequest` trong `server.py` và `marketing_engine_tool.yaml`: `tenant_id` và `business_description` chuyển sang `Optional` với fallback tự động về Workspace Tenant ID (`0cfcbe3e-fbfa-4025-8aa0-ee5ff82b77d8`) và mô tả mặc định.
2. **Khai báo 4 Tool phân tách vật lý trong Dify**:
   - `analyzeProjectOnboarding`: Bóc tách website, trinh sát SERP DuckDuckGo thực tế và audit SEO.
   - `researchCompetitor`: Trinh sát giá và model đối thủ trực tuyến.
   - `createWooCommerceProduct`: Worker 1 chuyên xuất bản trang chi tiết sản phẩm chuẩn UECS.
   - `writeSeoArticle`: Worker 2 chuyên viết bài Blog / Tin tức chuẩn 10x Editorial (SOP 12).
3. **Cập nhật System Prompt nghiêm ngặt (Zero Hallucination Protocol)**:
   - Xóa bỏ triệt để đoạn checklist mẫu gây ảo giác.
   - Ra chỉ thị tối hậu: Bắt buộc gọi function ngay lập tức khi phát hiện ý định trinh sát/SEO; tuyệt đối cấm chat hứa hẹn hoãn binh; tự động kế thừa URL và sản phẩm từ lịch sử trò chuyện mà không hỏi lại.
4. **Khởi động lại dịch vụ**: Reload `docker-api-1`, `docker-worker-1` và Uvicorn daemon cổng 8085. Đã test gọi thực tế thành công 100%.

---

### [BUG-082] NameError: get_latest_paa_questions is not defined Khi Phân Tích Web Đích và Đối Thủ Hạt Giống
**File:** `dify-integration/agent-engine/seed_analyzer.py`
**Hiện tượng:** Khi người dùng nhấn nút *"Phân Tích Web Đích"* hoặc gọi endpoint `/api/v1/portal/projects/{id}/analyze-target`, tiến trình ném lỗi `NameError: name 'get_latest_paa_questions' is not defined` và trả về HTTP 500.
**Nguyên nhân gốc rễ:** Hàm `get_latest_paa_questions` ban đầu chỉ được import cục bộ bên trong hàm `run_continuous_seed_reconnaissance`. Khi hàm mới `analyze_seed_competitor` gọi đến `get_latest_paa_questions` để bóc tách câu hỏi Google PAA cho Web đích và đối thủ hạt giống thì biến này chưa tồn tại trong phạm vi module (top-level scope).
**Biện pháp khắc phục:** Bổ sung `from serp_discovery_tool import get_latest_paa_questions` ngay tại đầu file `seed_analyzer.py`.

---

### [BUG-083] API batch-deep-audit Trả Về 0 Đối Thủ Được Quét Do Khác Biệt Giữa Domain Và URL
**File:** `dify-integration/agent-engine/seed_analyzer.py`, `dify-integration/agent-engine/server.py`
**Hiện tượng:** Khi kích hoạt *"Quét Chuyên Sâu Cả 10 Đối Thủ"*, tiến trình hoàn thành sau vài giây nhưng trả về `"audited_count": 0` và không có đối thủ nào được cập nhật trường `deep_audit` trong CSDL PostgreSQL.
**Nguyên nhân gốc rễ:** Hàm `deep_audit_competitor_batch` trả về dictionary kết quả với khóa mặc định là `domain` (chuẩn hóa không có giao thức http/https). Tuy nhiên, vòng lặp cập nhật trong `server.py` lại truy vấn kết quả bằng biến `url` (chuỗi đầy đủ gồm cả `https://...`). Sự sai lệch khóa tra cứu khiến `results.get(url)` luôn trả về `None`.
**Biện pháp khắc phục:** 
1. Trong `seed_analyzer.py`: Lưu đồng thời cả `results[domain] = res` và `results[url] = res` cho từng URL được phân tích.
2. Trong `server.py`: Kiểm tra fallback `audit_res = results.get(url) or results.get(domain) or results.get(clean_domain)`. Sau khi sửa, toàn bộ 9/10 đối thủ đều được cập nhật thành công.

---

### [BUG-084] Click Nút "Xem Chi Tiết 6 Trụ Cột Đột Kích" Không Mở Được Modal Do Sai Cơ Chế CSS .active Và DOM Bị Lồng Sai Thẻ Đóng
**File:** `dify-integration/agent-engine/portal/index.html`, `dify-integration/agent-engine/portal/portal.js`
**Hiện tượng:** Người dùng click vào nút `[🔍 Xem Chi Tiết 6 Trụ Cột Đột Kích]` trên thẻ đối thủ nhưng giao diện hoàn toàn không có phản ứng, modal không hiển thị.
**Nguyên nhân gốc rễ:**
1. **Lỗi cấu trúc DOM HTML**: Form `#editProjectModal` trong `index.html` bị thiếu thẻ đóng `</div>`, dẫn tới việc thẻ `#competitorDeepAuditModal` bị trình duyệt hiểu nhầm là phần tử con nằm bên trong modal sửa dự án đang ở trạng thái ẩn.
2. **Cơ chế CSS `.modal-overlay`**: CSS của Portal khai báo `.modal-overlay { opacity: 0; pointer-events: none; transition: ...; }` và chỉ kích hoạt hiển thị khi có class `.modal-overlay.active { opacity: 1; pointer-events: auto; }`. Hàm JS cũ chỉ gán `modal.style.display = 'flex'` mà không gọi `modal.classList.add('active')`, khiến modal vẫn trong suốt và không nhận click.
3. **Lỗi Escape Ký Tự Trong Tham Số Onclick**: Khi truyền trực tiếp URL chứa dấu gạch chéo `/` hoặc ký tự đặc biệt vào chuỗi inline `onclick="openCompetitorDeepAuditModal('domain', 'url')"` có thể gây lỗi cú pháp JavaScript.
**Biện pháp khắc phục:**
1. Bổ sung thẻ đóng `</div>` chuẩn hóa cho `#editProjectModal`, tách `#competitorDeepAuditModal` ra ngoài độc lập với `z-index: 1000`.
2. Sửa hàm `openCompetitorDeepAuditModal`: thêm `modal.classList.add('active')` và `modal.style.display = 'flex'`.
3. Gắn sự kiện click vào nền backdrop để đóng modal tự nhiên: `modal.addEventListener('click', (e) => { if (e.target === modal) closeCompetitorDeepAuditModal(); })`.
4. Bổ sung tham số `idx` (index trong mảng `state.competitors`) vào hàm gọi để lấy dữ liệu từ bộ nhớ state an toàn 100% không sợ escape ký tự URL.

---

### [FEAT-085] Chuẩn Hóa Ma Trận Đối Đầu Song Song (Head-to-Head Comparison Matrix) & Cơ Chế Xoay Vòng Serper API 0 Đồng (SOP 21)
**File:** `seed_analyzer.py`, `db_service.py`, `server.py`, `portal.js`, `index.html`
**Tính năng & Trí tuệ mới:**
1. **Ma trận đối đầu trực diện (Head-to-Head Matrix)**: Xây dựng hàm `build_head_to_head_matrix(my_site_audit, seed_analysis)` so sánh song song 10 chỉ số On-page và kỹ thuật giữa Web đích và từng đối thủ trong 10 đối thủ SERP. Modal hiển thị bảng so sánh trực quan (Xanh: Vượt trội/Chuẩn SEO, Đỏ: Thua kém/Cần tối ưu, Xám: Tương đương) giúp chủ dự án nhận diện ngay khoảng trống cơ hội (SEO Gap).
2. **Kiến trúc Trinh sát 0 đồng (Zero-Cost BYOK Pool)**: Cơ chế Multi-Key tự động xoay key khi gặp HTTP 403/429; khai thác Google PAA hoàn toàn miễn phí từ kết quả SERP; đo tuổi domain qua RDAP (.com) và Regex Copyright (.vn) mà không cần mua API bên thứ ba.
3. **Lưu trữ siêu nhẹ (2-4 KB JSON / site)**: Chỉ phân tích 2 URL đại diện (Trang chủ/Danh mục + Trang chi tiết), lưu trữ trực tiếp vào JSONB của PostgreSQL `marketing_saas` mà không cào bulk HTML hay tải ảnh nhị phân gây phình ổ đĩa.

---

### [BUG-086] Cảnh Báo Trình Duyệt [Violation] Permissions policy violation: unload is not allowed in this document và turnstileInterceptor.js trên Localhost
**File:** `dify-integration/agent-engine/server.py`, `dify-integration/agent-engine/portal/index.html`, `dify-integration/agent-engine/portal/admin.html`
**Hiện tượng:** Khi lập trình và mở DevTools Console trên `http://localhost:8085` hoặc `8090`, xuất hiện liên tục:
```text
turnstileInterceptor.js:1 SOLVING
turnstileInterceptor.js:1 localhost undefined undefined localhost
VM595 inspector.b9415ea5.js:406 [Violation] Permissions policy violation: unload is not allowed in this document.
```
**Nguyên nhân gốc rễ:**
1. `turnstileInterceptor.js`: Do Extension trình duyệt giải Captcha (CapSolver/2Captcha/Buster...) tự động tiêm vào toàn bộ URL (`<all_urls>`). Trên localhost không có Cloudflare Turnstile nên in log dò tìm liên tục.
2. `[Violation] Permissions policy violation: unload`: Bắt đầu từ Google Chrome 115+ (siết chặt từ Chrome 128+), sự kiện `window.addEventListener('unload')` bị deprecate để tối ưu BFCache. Các công cụ Browser Inspector / React DevTools đăng ký listener `unload` bị Chrome chặn và ghi log vi phạm chính sách do server thiếu header Permissions-Policy.
**Biện pháp khắc phục:**
1. Thêm middleware HTTP header `Permissions-Policy: unload=*` vào FastAPI Gateway `server.py`.
2. Thêm thẻ meta `<meta http-equiv="Permissions-Policy" content="unload=*">` vào `<head>` của `index.html` và `admin.html`.
3. Hỗ trợ phương thức HTTP `HEAD` cho root `/` và endpoint download plugin.
4. Hướng dẫn người dùng thêm `localhost` và `127.0.0.1` vào Whitelist của Extension giải Captcha.

---

### [BUG-087] Thiếu DDL Bảng boardroom_minutes Trong schema.sql Khi Triển Khai Môi Trường Mới
**File:** `dify-integration/agent-engine/schema.sql`, `dify-integration/agent-engine/routes/portal/brief_and_drift.py`
**Hiện tượng:** Tính năng Hội đồng Tranh biện C-Suite (SOP 25) hoạt động bình thường trên DB hiện hành nhưng khi khởi tạo môi trường mới từ file DDL `schema.sql`, API `/api/v1/portal/projects/{id}/boardroom-minutes` gặp lỗi: `relation "boardroom_minutes" does not exist`.
**Nguyên nhân gốc rễ:** File `schema.sql` mới dừng lại ở bảng số 9 (`execution_states`), chưa được đồng bộ câu lệnh DDL của bảng số 10 (`boardroom_minutes`).
**Biện pháp khắc phục:**
Bổ sung DDL chuẩn hóa vào `schema.sql`:
```sql
CREATE TABLE IF NOT EXISTS boardroom_minutes (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    project_id UUID NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
    topic VARCHAR(500),
    debate_summary JSONB DEFAULT '{}'::jsonb,
    consensus_plan TEXT,
    cvi_score NUMERIC(5,2) DEFAULT 85.0,
    created_at TIMESTAMPTZ DEFAULT NOW()
);
CREATE INDEX IF NOT EXISTS idx_boardroom_minutes_project ON boardroom_minutes(project_id, created_at DESC);
```

---

### [BUG-088] Hardcode Ngày Tháng 2026/10 và Rò Rỉ Tên Miền Đơn Lẻ Trong Chuỗi Điều Phối Dify Agent Orchestrator
**File:** `dify-integration/agent-engine/agent_orchestrator.py`, `dify-integration/dsl/w5_qa_publisher_dsl.yml`, `dify-integration/agent-engine/bridge_task_service.py`
**Hiện tượng:** 
1. Sau khi chuyển sang tháng 11/2026, ảnh xử lý bởi `image_staging_service.py` vẫn bị copy vào thư mục `wp-content/uploads/2026/10`, dẫn tới URL ảnh và cấu trúc thư mục media WordPress bị lệch tháng.
2. Link xem trước (Preview URL) trong Agent W5 DSL bị fix cứng domain `michinhhang.com`, vi phạm hợp đồng đa ngành (Multi-Tenant SaaS).
3. Header User-Agent của `bridge_task_service.py` gửi đi mang domain cứng `+https://michinhhang.com`.
**Biện pháp khắc phục:**
1. Trong `agent_orchestrator.py`: Thay hardcode `2026/10` bằng `datetime.now().strftime("%Y/%m")` động.
2. Trong `w5_qa_publisher_dsl.yml`: Đổi preview URL sang định dạng động `{site_url}/?p={post_id}&preview=true`.
3. Trong `bridge_task_service.py`: Cấu hình User-Agent lấy động `cms_url` của từng dự án.

---

### [BUG-089] Endpoint /health Thiếu HTTP HEAD Method Gây 405 Method Not Allowed Cho Health Checks
**File:** `dify-integration/agent-engine/routes/cms.py`
**Hiện tượng:** Khi chạy container Docker hoặc khi các công cụ giám sát / load balancer / trình duyệt Chrome DevTools gửi request kiểm tra trạng thái qua HTTP `HEAD /health`, FastAPI Gateway trả về `405 Method Not Allowed` (chỉ cho phép GET), mặc dù hệ thống đã tuyên bố hỗ trợ đầy đủ các phương thức HEAD.
**Nguyên nhân gốc rễ:** Route `/health` trong `routes/cms.py` chỉ được khai báo qua decorator `@router.get("/health")`, thiếu đăng ký handler cho method `HEAD`.
**Biện pháp khắc phục:**
Bổ sung handler `HEAD` cho `/health` và header `Content-Length` cho route download plugin:
```python
@router.head("/health", include_in_schema=False)
def health_check_head():
    return Response(status_code=200)
```
Kiểm tra lại qua lệnh `curl -I http://127.0.0.1:8085/health` trả về chuẩn xác `HTTP/1.1 200 OK`.

---

### [BUG-090] Lỗi Kết Nối PostgreSQL Khi Chạy Scripts Bootstrap Trên Host Do Không Bind Port 5432
**File:** `dify-integration/tools/bootstrap_gsc.py`, `dify/docker/docker-compose.yaml`, `dify-integration/README_HUONG_DAN_TICH_HOP.md`
**Hiện tượng:** Chạy trực tiếp `python3 tools/bootstrap_gsc.py` từ Terminal của máy chủ Host theo hướng dẫn trong README thì gặp lỗi:
`psycopg2.OperationalError: could not connect to server: Connection refused`
**Nguyên nhân gốc rễ:** Dịch vụ PostgreSQL (`docker-db_postgres-1`) trong `dify/docker/docker-compose.yaml` chỉ expose cổng 5432 nội bộ trong Docker network `docker_default` (5432/tcp), không publish ra ngoài Host OS (`0.0.0.0:5432`). Môi trường ngoài Host không phân giải được tên miền container `docker-db_postgres-1` hay `db_postgres`, và kết nối vào `127.0.0.1:5432` bị từ chối.
**Biện pháp khắc phục:**
1. Thực thi script thông qua container Marketing Engine (đã tham gia mạng `docker_default`):
   ```bash
   docker exec michinhhang-marketing-agent-1 python3 /app/../tools/bootstrap_gsc.py
   # hoặc:
   docker compose exec marketing-agent-engine python3 tools/bootstrap_gsc.py
   ```
2. Cập nhật cảnh báo và chỉ dẫn rõ ràng trong `README_HUONG_DAN_TICH_HOP.md` để tránh nhầm lẫn giữa môi trường Host và môi trường Docker.

---

### [BUG-091] Container WordPress docker-entrypoint Thiếu Binary WP-CLI Khiến Script Onboarding Báo Lỗi Executable Not Found
**File:** `dify-integration/tools/automate_gsc_onboarding.py`, `docker-compose.yml`
**Hiện tượng:** Khi chạy script tự động hóa GSC Onboarding với tham số:
`--wp-cli-prefix 'docker compose exec -T wordpress wp --path=/var/www/html'`
Tiến trình lập tức bị hủy với lỗi:
`OCI runtime exec failed: exec failed: unable to start container process: exec: "wp": executable file not found in $PATH`
**Nguyên nhân gốc rễ:** Container `michinhhang-wordpress-1` sử dụng Docker image chuẩn `wordpress:php8.2-apache`. Image này chỉ đóng gói Apache HTTPD và PHP runtime, không cài đặt sẵn công cụ dòng lệnh WP-CLI (`/usr/local/bin/wp`).
**Biện pháp khắc phục:**
1. Khuyến nghị chuẩn: Thiết lập kết nối GSC & Marketing Engine URL thông qua giao diện trực quan tại **WP Admin → Settings → Omni Bridge** (không cần WP-CLI).
2. Tác vụ nội bộ độc lập: Sử dụng script `autopilot/wp_publisher_cli.php` nạp trực tiếp `/var/www/html/wp-load.php` qua lệnh `php` có sẵn trong container, hoặc dùng REST API `/wp-json/omni-bridge/v1/*` thay vì phụ thuộc lệnh `wp`.

---

### [BUG-092] Điều Hướng Mạng Chéo Docker Giữa Dify Tool Và WordPress Publisher (Gateway IP 172.20.0.1:8080)
**File:** `dify-integration/tools/wp_omni_bridge_tool.yaml`, `dify/docker/.env`, `docker-compose.yml`
**Hiện tượng:** Dify Custom Tool chạy trong mạng `docker_default` không thể gọi trực tiếp container `michinhhang-wordpress-1` bằng hostname `http://wordpress` vì WordPress thuộc mạng `michinhhang_default`.
**Nguyên nhân & Cơ chế khắc phục:**
1. WordPress public cổng `8080:80` ra Host (`0.0.0.0:8080`).
2. Dify Custom Tool được định tuyến qua Docker Bridge Gateway IP: `http://172.20.0.1:8080`.
3. Trong `dify/docker/.env`, bắt buộc cấu hình biến `SSRF_PROXY_ALLOW_PRIVATE_IPS=172.16.0.0/12,192.168.0.0/16,10.0.0.0/8,100.0.0.0/8,127.0.0.0/8` để dịch vụ Squid Proxy (`ssrf_proxy:3128`) không chặn các HTTP request gọi từ Dify sang Gateway `172.20.0.1:8080`.



