# Trạm Điều Phối AI — Dự án michinhhang

## Thông tin dự án
| Mục | Giá trị |
|-----|---------|
| **Tên** | michinhhang |
| **Nền tảng** | WordPress + WooCommerce + MariaDB + Redis |
| **Loại hình** | TMĐT phân phối thiết bị Xiaomi chính hãng |
| **Plugin ERP** | `mi-erp-system` (kho, vận chuyển, serial, bảo hành) |
| **Hạ tầng** | 100% Docker — **TUYỆT ĐỐI KHÔNG** chạy `php`, `wp`, `mysql` ngoài container |

## Triết lý cốt lõi (đọc trước khi code)
1. **YAGNI** — Không cài plugin bên thứ 3 cho tính năng nhỏ. Ưu tiên viết PHP thuần.
2. **Classic Theme** — Không dùng Gutenberg / FSE / Page Builder.
3. **Tailwind first** — Dùng class Tailwind theo `tailwind.config.js`, không inline CSS.
4. **ACF dynamic** — Không hardcode text. Đọc từ `get_field()` / `have_rows()`.
5. **Prefix `mi_`** — Mọi hàm tự định nghĩa phải có prefix `mi_`.
6. **Escape output** — Mọi echo phải qua `esc_html()`, `esc_attr()`, `esc_url()`.

## Luồng trang chủ (thứ tự bắt buộc)
```
get_header() → hero.php → media-services.php → #product-row-section (3 danh mục) → news.php → get_footer()
```
> **footer.php đã chứa Pre-Footer Trust Badges** — KHÔNG gọi trust-badges.php riêng từ front-page.php.

## 3 Danh mục sản phẩm bắt buộc (Title Case chuẩn)
| Danh mục | Slug | Màu banner |
|----------|------|-----------|
| `Tivi Xiaomi` | `tivi-xiaomi` | `from-xiaomi-yellow to-tertiary-fixed` |
| `Tủ Lạnh Xiaomi` | `tu-lanh-xiaomi` | `from-cyan-500 to-blue-600` |
| `Thiết Bị Gia Đình` | `thiet-bi-gia-dinh` | `from-amber-500 to-orange-600` |

## ACF Field Map chuẩn (tra cứu trước khi dùng get_field)
| Vị trí | Field name đúng | Sai phổ biến |
|--------|----------------|--------------|
| Topbar text | `mi_hf_topbar_text` | ~~mi_header_topbar_text~~ |
| Địa chỉ topbar | `mi_hf_topbar_address` | ~~mi_hero_address_text~~ |
| Logo header | `mi_hf_logo` | ~~mi_header_logo~~ |
| Hotline | `mi_hotline_number` | ~~mi_hero_hotline_text~~ |
| FAB hotline | `mi_hf_fab_hotline` | |
| FAB Zalo | `mi_hf_fab_zalo` | |
| FAB Messenger | `mi_hf_fab_messenger` | |

## Sổ phụ — Đọc khi cần chuyên sâu
| File | Nội dung |
|------|---------|
| [ai-docs/01-architecture.md](ai-docs/01-architecture.md) | Template mapping, file structure, menu system |
| [ai-docs/02-frontend-rules.md](ai-docs/02-frontend-rules.md) | Tailwind, CSS, UI/UX, micro-interactions, menu hover |
| [ai-docs/03-backend-wpcs.md](ai-docs/03-backend-wpcs.md) | WPCS, ACF patterns, WooCommerce, ERP integration |
| [ai-docs/04-homepage.md](ai-docs/04-homepage.md) | Tri thức sâu từng component trang chủ |
| [ai-docs/05-buglog.md](ai-docs/05-buglog.md) | Nhật ký bug & anti-patterns (28 bugs đã ghi nhận) |
| [ai-docs/06-infrastructure.md](ai-docs/06-infrastructure.md) | Docker, hạ tầng, môi trường thực thi |
| [ai-docs/07-seo-json-ld-checklist.md](ai-docs/07-seo-json-ld-checklist.md) & [07-seo-pagespeed-audit.md](ai-docs/07-seo-pagespeed-audit.md) | SEO & Tốc độ |
| [ai-docs/08-theme-standardization.md](ai-docs/08-theme-standardization.md) | Chuẩn hóa cấu trúc WordPress Theme (Refactoring & Decoupling) |
| [ai-docs/09-theme-commercialization.md](ai-docs/09-theme-commercialization.md) | Tiêu chuẩn Thương mại hóa Theme (i18n, TGMPA, Customizer) |
| [docs/category.html](docs/category.html) | File giao diện HTML gốc của trang danh mục sản phẩm |
