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
7. **Product Card CRO & Social Proof** — Giá sale đứng trước (đỏ đậm), giá gốc đứng sau (xám gạch ngang). Đáy card dùng `mt-auto` gióng thẳng hàng sao và `Đã bán X`. Tự động sinh dữ liệu mẫu ngẫu nhiên khi đăng sản phẩm mới.
8. **SEO & Rank Math Integration** — Tích hợp module `inc/seo.php` làm giàu dữ liệu Rank Math qua filter (`rank_math/json_ld`, `rank_math/frontend/description`), gỡ bỏ schema thừa từ WooCommerce footer, tuyệt đối không hardcode thẻ SEO gây trùng lặp.
9. **Flat URL & Permalinks Beautification** — Cấu trúc đường dẫn rút gọn tối đa không tiền tố: `/ten-san-pham/`, `/ten-danh-muc/`, `/ten-chuyen-muc/`. Rank Math tự động 301 Redirect mọi URL cũ chứa `/san-pham/`, `/product-category/`, `/category/` về URL mới.
10. **Uniform Container Width** — Mọi trang (Header, Body, Footer) dùng chung chuẩn `max-w-[1440px] mx-auto px-4`. Không dùng class `container` của Tailwind kết hợp `max-w-[1440px]` vì sẽ bị ép co hẹp ở 1280px; không dùng padding Header dị biệt (`px-12`, `px-20`).
11. **SOP 11 Media Automation** — Ảnh sản phẩm bắt buộc chuẩn tỷ lệ 1:1, xuất đồng thời 5 kích cỡ (`1000px`, `600px`, `300px`, `150px`, `100px`) định dạng WebP + JPG fallback. Không dùng AI giả lập CGI, tẩy sạch text đối thủ bằng Telea inpainting viền 5-8%, đóng watermark chìm TongKhoMi (opacity 85-90%, góc trên).
12. **SOP 12 Content Writing & LLM-as-a-Judge Evals** — Cấu trúc kim tự tháp H1 -> H2 -> H3, không nhồi ảnh vào Heading, tâm lý học chuyển đổi CRO (Feature ➔ Benefit), bắt buộc lồng ghép 4 link chính sách (`/chinh-sach-bao-hanh/`, `/chinh-sach-doi-tra/`, `/tra-cuu-bao-hanh/`, `/chinh-sach-van-chuyen/`), lọc sạch 8 mẫu câu sáo rỗng AI, vượt qua bài kiểm tra định lượng $\ge 75.0$ điểm (SOP 30%, Policy 25%, CRO 25%, Brand 20%).
13. **Copilot Meta Box & Quick Actions** — Trình soạn thảo Post / Product Editor tích hợp sẵn widget Omni CrewAI Copilot (Sidebar `post.php`), cho phép kích hoạt 1-chạm: tối ưu On-page SEO (`omni_crewai_optimize_post_seo`) và chuẩn hóa 5 ảnh WebP chuẩn SOP 11 (`omni_crewai_generate_post_images`).
14. **Template Hierarchy & Trang Chuyên Dụng (Specialized Page Templates)**:
    - `page-tin-tuc.php` (Trang Tin Tức): Layout Magazine Grid chuẩn tạp chí công nghệ: 5 bài nổi bật (1 bài lớn cao 320px + 4 bài nhỏ 4/3), thanh lọc category dạng pill badges bo tròn, danh sách bài viết ảnh 16/9 kèm phân trang `paginate_links()` và sticky sidebar "TIN XEM NHIỀU" lấy top 5 bài nhiều bình luận nhất.
    - `page-lien-he.php` (Trang Liên Hệ): Form tiếp nhận yêu cầu khách hàng với Nonce validation (`mi_contact_action`), làm sạch dữ liệu (`sanitize_text_field`, `sanitize_email`, `sanitize_textarea_field`), gửi email tức thời qua `wp_mail()`, nhúng Google Maps iframe và địa chỉ các showroom Tổng Kho Xiaomi.
    - `page-tra-cuu-bao-hanh.php` (Trang Tra Cứu Bảo Hành): Wrapper giao diện chuyên dụng nhúng shortcode ERP `[mi_warranty_check]` (tra cứu tức thời qua SĐT hoặc Serial/IMEI).
    - `page.php` (Trang Tĩnh & Chính Sách Pháp Lý): Tự động phát hiện trang chính sách (`is_policy`) để áp dụng bố cục 2 cột chuyên nghiệp: Sticky sidebar liên kết các chính sách liên quan (Bảo mật, Bảo hành, Đổi trả, Vận chuyển) bên trái và thẻ nội dung typography viền accent cam Xiaomi, badge chứng nhận bên phải.
    - `single.php` (Chi Tiết Bài Viết Blog/Tin Tức): Bố cục 2 cột với typography `prose prose-slate` thoáng đạt, tiêu đề H1 chuẩn mực, meta tác giả/ngày đăng, hỗ trợ phân trang bài viết dài (`wp_link_pages`) và sticky sidebar "TIN XEM NHIỀU".
    - `search.php` & `404.php`: Trang tìm kiếm an toàn vòng lặp với `rewind_posts()` và trang 404 thân thiện người dùng dẫn về trang chủ và danh mục sản phẩm chính.
15. **Biên Dịch Tailwind CSS Qua Docker (Zero Binary Bloat)**:
    - Tuyệt đối không commit các file nhị phân lớn (`tailwindcss-linux-x64`) vào kho lưu trữ (BUG-018 / LESSON-08).
    - Mọi thao tác biên dịch CSS Tailwind bắt buộc thực thi qua Docker container:
      `docker run --rm -v $(pwd):/app -w /app node:20-alpine sh -c "npx tailwindcss -i ./src/input.css -o ./style.css --minify"`



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
| Hotline | `mi_hf_topbar_hotline` | ~~mi_hotline_number~~, ~~mi_hero_hotline_text~~ |
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
| [ai-docs/05-buglog.md](ai-docs/05-buglog.md) | Nhật ký bug & anti-patterns (65 bugs đã ghi nhận) |
| [ai-docs/06-infrastructure.md](ai-docs/06-infrastructure.md) | Docker, hạ tầng, môi trường thực thi |
| [ai-docs/07-seo-json-ld-checklist.md](ai-docs/07-seo-json-ld-checklist.md) & [07-seo-pagespeed-audit.md](ai-docs/07-seo-pagespeed-audit.md) | SEO & Tốc độ |
| [ai-docs/08-theme-standardization.md](ai-docs/08-theme-standardization.md) | Chuẩn hóa cấu trúc WordPress Theme (Refactoring & Decoupling) |
| [ai-docs/09-theme-commercialization.md](ai-docs/09-theme-commercialization.md) | Tiêu chuẩn Thương mại hóa Theme (i18n, TGMPA, Customizer) |
| [ai-docs/10-commercialization-plan.md](ai-docs/10-commercialization-plan.md) | Kế hoạch Thương mại hóa và Checklist phát hành sản phẩm |
| [ai-docs/11-image-processing-sop.md](ai-docs/11-image-processing-sop.md) | SOP xử lý hình ảnh sản phẩm & bài viết tự động (SOP 11) |
| [ai-docs/12-content-writing-sop.md](ai-docs/12-content-writing-sop.md) | SOP viết bài chuẩn SEO & tối ưu tỷ lệ chuyển đổi CRO (SOP 12) |
| [docs/category.html](docs/category.html) | File giao diện HTML gốc của trang danh mục sản phẩm |
