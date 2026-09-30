# Hạ tầng & Môi trường thực thi

## Kiến trúc Docker
Toàn bộ hệ thống chạy 100% trong Docker containers:

| Service | Container |
|---------|----------|
| WordPress + PHP | `michinhhang-wordpress-1` (hoặc tên tương tự) |
| MariaDB | `michinhhang-db-1` |
| Redis | `michinhhang-redis-1` |

Máy chủ Ubuntu host **KHÔNG** cài PHP, WP-CLI, MySQL, Composer ở môi trường ngoài.

---

## Quy tắc thực thi lệnh

### ❌ NGHIÊM CẤM (chạy trực tiếp trên Terminal host)
```bash
php -r "..."
wp cli ...
mysql -u root -p ...
composer install
```

### ✅ Cách đúng
| Tình huống | Lệnh chuẩn |
|-----------|-----------|
| WP CLI | `docker exec -it michinhhang-wordpress-1 wp [command]` |
| MySQL | `docker exec -it michinhhang-db-1 mysql -u root -p` |
| PHP debug | Đọc code PHP để tìm logic — ưu tiên không chạy lệnh |

> **Quy trình bắt buộc:** AI in ra lệnh → User copy vào Terminal → User báo lại kết quả.

### Ví dụ lệnh Docker chuẩn
```bash
# Kiểm tra site URL
docker exec -it michinhhang-wordpress-1 wp option get siteurl

# Liệt kê sản phẩm
docker exec -it michinhhang-wordpress-1 wp post list --post_type=product --fields=ID,post_title

# Flush cache
docker exec -it michinhhang-wordpress-1 wp cache flush

# Kiểm tra ACF fields
docker exec -it michinhhang-wordpress-1 wp option get acf_pro_license
```

---

## Fix quyền upload Media Library trong Docker

### Nguyên nhân thực tế đã gặp
WordPress chạy trong container dưới user `www-data` (`uid=33`), nhưng thư mục mount `wp-content` trên host có owner `1000:1000`. Khi đó PHP không thể ghi file mới vào `wp-content/uploads`, dẫn đến lỗi:

```text
The uploaded file could not be moved to wp-content/uploads/...
```

### Fix chuẩn đã chứng minh
```bash
docker exec michinhhang-wordpress-1 sh -lc 'chown -R www-data:www-data /var/www/html/wp-content && chmod 775 /var/www/html/wp-content && chmod 775 /var/www/html/wp-content/uploads'
```

### Verification chuẩn
```bash
docker exec michinhhang-wordpress-1 sh -lc 'su -s /bin/sh www-data -c "touch /var/www/html/wp-content/uploads/.www-data-test && ls -l /var/www/html/wp-content/uploads/.www-data-test && rm -f /var/www/html/wp-content/uploads/.www-data-test"'
```

Nếu lệnh trên chạy thành công, WordPress có quyền ghi vào uploads. Đây là bước bắt buộc khi fix lỗi upload ảnh / media library trong môi trường Docker.

> Chú ý: Host không cần cài `php`, `wp`, `mysql`; mọi thao tác liên quan tới WordPress runtime phải diễn ra trong container.

---

## Compile Tailwind CSS

Bắt buộc chạy sau khi thêm hoặc sửa class Tailwind:
```bash
# Chạy từ thư mục theme
cd /home/ubuntu/michinhhang/wp-content/themes/omni-commerce
./tailwindcss-linux-x64 -i ./src/input.css -o ./style.css --minify
```

> **Cảnh báo:** Nếu quên compile → class mới không sinh CSS → UI chết hoàn toàn, không có error message.

### Khi nào phải compile
- Thêm class Tailwind mới (đặc biệt `group-hover`, `before:`, `translate`, gradient)
- Copy class từ HTML mẫu sang PHP template
- Thêm custom CSS vào `src/input.css`

---

## Enqueue Assets (functions.php)

```php
// Thứ tự load (priority 20 trong wp_enqueue_scripts)
wp_enqueue_style('astra-theme-css', '.../style.css');
wp_enqueue_style('omni-commerce-css', get_stylesheet_uri(), ['astra-theme-css'], filemtime(...));
wp_enqueue_style('tailwind-css',    '.../assets/css/tailwind.css');
wp_enqueue_style('local-fonts',     '.../assets/css/fonts.css', [], null);
```

---

## Auto Setup Hook (init)

`functions.php` có hook `init` tự khởi tạo khi DB trắng (container mới):
- Tạo 3 `product_cat` terms: Tivi Xiaomi, Tủ Lạnh Xiaomi, Thiết Bị Gia Đình
- Gán ACF rows cho trang chủ
- Tạo Header Menu và gán vào `header-menu` location

**Guard:** `get_option('mi_auto_setup_done')` → chỉ chạy 1 lần.
**Sau setup:** `update_option('mi_auto_setup_done', 1)`.

---

## Cart Fragment (AJAX Cart Count)

```php
// functions.php — filter woocommerce_add_to_cart_fragments
add_filter( 'woocommerce_add_to_cart_fragments', 'viomi_theme_cart_count_fragments' );
function viomi_theme_cart_count_fragments( $fragments ) {
    ob_start();
    ?>
    <span class="cart-contents-count ...">
    <?php echo function_exists('WC') ? WC()->cart->get_cart_contents_count() : '0'; ?>
    </span>
    <?php
    $fragments['span.cart-contents-count'] = ob_get_clean();
    return $fragments;
}
```

AJAX Add to Cart trong `single-product.php` dùng `DOMParser` backup (không phụ thuộc fragment API).
