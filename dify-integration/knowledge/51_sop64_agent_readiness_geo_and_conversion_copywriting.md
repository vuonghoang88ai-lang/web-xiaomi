# SOP 64: BỘ QUY CHUẨN AGENT-READINESS (GEO/AEO) & KỸ THUẬT COPYWRITING CHUYỂN ĐỔI CAO

## 1. BỐI CẢNH & NGUYÊN TẮC CỐT LÕI (DERIVED FROM COREYHAINES31/MARKETINGSKILLS)
Hệ sinh thái Marketing AI trong kỷ nguyên mới chuyển dịch từ việc chỉ phục vụ người đọc sang song hành phục vụ cả **AI Agent & LLM Search Engines** (Google AI Overviews, Perplexity, ChatGPT Search, Claude, Gemini).
Đồng thời, nội dung bài viết và On-page phải vượt qua bài kiểm tra **The Three-Question Gate** và triệt tiêu toàn bộ dấu vết AI sáo rỗng.

## 2. TIÊU CHUẨN AGENT-READINESS & GENERATIVE ENGINE OPTIMIZATION (GEO)
1. **Dynamic `/llms.txt` & `/llms-full.txt` Endpoint:**
   - Mọi website kết nối qua Omni Bridge tự động cung cấp file Markdown chuẩn hóa tại domain root:
     - Tóm tắt thực thể thương hiệu, UVP và định vị độc quyền.
     - Danh sách liên kết Silo danh mục sản phẩm/dịch vụ cốt lõi kèm giá kho niêm yết.
     - Bộ liên kết pháp lý & trang tín nhiệm E-E-A-T (Giới thiệu, Điều khoản, Bảo mật, Tra cứu bảo hành).
   - Tự động tiêm thẻ `<link rel="alternate" type="text/markdown" href="/llms.txt" title="LLM Agent Manifest">` vào thẻ `<head>` của trang chủ.
2. **First-Party Truth & Pricing Extraction:**
   - Dữ liệu thông số kỹ thuật, bảng giá và chính sách đổi trả phải nằm trực tiếp dưới dạng văn bản (plain HTML text), tuyệt đối không giấu trong ảnh đồ họa hay script ẩn, đảm bảo AI Agents trích dẫn với độ chính xác 100%.

## 3. BỘ KIỂM TRA 3 CÂU HỎI (THE THREE-QUESTION GATE FOR COPYWRITING)
Mọi câu văn xuất bản bởi AI Copilot hoặc gợi ý On-page (Title, Meta Description, H1) BẮT BUỘC vượt qua 3 câu hỏi:
1. **Can the reader picture it? (Hình tượng hóa cụ thể):**
   - Loại bỏ các từ ngữ trừu tượng ("giải pháp tối ưu", "chất lượng vượt trội").
   - Thay thế bằng thông số và hành vi cụ thể (ví dụ: *"làm lạnh đa chiều bảo quản thực phẩm 7 ngày tươi ngon"*, *"giao hỏa tốc 2 giờ nội thành"*).
2. **Can it be proven true or false? (Mệnh đề có thể kiểm chứng):**
   - Cấm các tính từ vô căn cứ như "hàng đầu", "tự hào", "đỉnh cao".
   - Bắt buộc là sự thật kiểm chứng được: *"Bảo hành chính hãng 24 tháng tận nơi"*, *"Lỗi 1 đổi 1 trong 30 ngày"*.
3. **The Swap Test (Bài kiểm tra đối thủ cạnh tranh):**
   - Nếu câu văn có thể copy - paste sang website của Điện Máy Xanh hay đối thủ bất kỳ mà không sai lệch $\rightarrow$ CÂU ĐÓ THẤT BẠI.
   - Phải lồng ghép bằng chứng độc quyền của thương hiệu (giá sỉ tại kho, hỗ trợ kỹ thuật trực tiếp).

## 4. BỘ HÒA GIẢI HEADING DOM (DOM HEADING HARMONIZER) & TƯƠNG THÍCH PHỔ QUÁT
1. **Bảo chứng duy nhất 1 thẻ H1:**
   - Áp dụng Output Buffering (`ob_start`) tại Omni Bridge trên hook `template_redirect` (loại trừ admin, feed, REST API và AJAX).
   - Nếu Theme đã có sẵn thẻ `<h1>`: Omni Bridge tuyệt đối không chèn thêm thẻ H1 ở footer.
   - Nếu Theme hoặc Page Builder (Elementor, Divi) có $> 1$ thẻ `<h1>`: Tự động giữ nguyên H1 đầu tiên và hạ bậc (downgrade) các thẻ H1 thừa thành `<h2>`.
2. **Bảo vệ mã nguồn JavaScript & Template Engine (Placeholder Shielding):**
   - Tự động mã hóa và cách ly toàn bộ thẻ `<script>`, `<style>`, `<template>`, `<svg>` vào mảng placeholder trước khi quét DOM heading, sau đó khôi phục nguyên vẹn 100%. Ngăn chặn tuyệt đối việc regex làm biến dạng các template JS của WooCommerce hay theme.
3. **Bảo toàn giao diện & CSS của Theme/Page Builder:**
   - Khi hạ bậc H1 thừa thành H2, toàn bộ thuộc tính (`class`, `style`, `id`, `data-*`) của Elementor, Divi, Astra, Flatsome, WoodMart được giữ nguyên 100%, bảo đảm không làm vỡ layout hay typography.
   - Tự động phát hiện và loại bỏ các thẻ H1 rỗng (`<h1></h1>`, `<h1>&nbsp;</h1>`) do slider hoặc carousel sinh ra, chuyển thành thẻ ẩn không làm sai lệch điểm On-Page.
4. **Khử trùng giới từ cụt lửng (Grammar Sanitizer):**
   - Cắt bỏ triệt để các giới từ hoặc ký tự nối lơ lửng ở cuối câu (`tại`, `và`, `cho`, `ở`, `-`, `|`), ngay cả khi chuỗi chưa chạm ngưỡng trần ký tự.

## 5. MA TRẬN TƯƠNG THÍCH ĐA PLUGIN SEO & CACHING
1. **Đồng bộ đa Plugin SEO:**
   - Tự động móc vào các filter Title và Description của 4 plugin SEO hàng đầu:
     - *Rank Math:* `rank_math/frontend/title`, `rank_math/frontend/description`.
     - *Yoast SEO:* `wpseo_title`, `wpseo_metadesc`.
     - *All in One SEO (AIOSEO):* `aioseo_title`, `aioseo_description`.
     - *SEOPress:* `seopress_titles_title`, `seopress_titles_desc`.
     - *WordPress Core:* `pre_get_document_title`, `wp_title`.
2. **Xóa cache đa tầng tự động (Universal Multi-Tier Cache Flush):**
   - Hàm `purge_all_environment_caches()` tự động kích hoạt lệnh xóa cache tương ứng: LiteSpeed Cache (`litespeed_purge_all`), WP Rocket (`rocket_clean_domain`), W3 Total Cache (`w3tc_flush_all`), WP Super Cache (`wp_cache_clear_cache`), Autoptimize (`autoptimizeCache::clearall`), và Redis/Memcached (`wp_cache_flush`).


