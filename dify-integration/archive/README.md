# Lưu Trữ Các File Monolithic & Backup Cũ (Archive)

Thư mục này lưu trữ an toàn các file nguyên khối (monolithic) và backup trước khi hệ thống được tái cấu trúc thành Modular Clean Architecture (SOP 23 - giới hạn ≤ 300 dòng):

- `server_original.py`: Bản monolithic của server (2.536 dòng) trước khi tách thành `models.py`, `utils.py`, `routes/`.
- `seed_analyzer_original.py`: Bản monolithic của seed analyzer (2.057 dòng) trước khi tách thành `seed_site_analyzer.py`, `seed_competitor_analyzer.py`, `seed_serp.py`, v.v.
- `portal_original.js` & `portal.js`: Bản monolithic frontend JS (4.101 dòng) trước khi tách thành các module trong `agent-engine/portal/`.
- `portal_original.css` & `portal.css`: Bản monolithic CSS (1.260 dòng) trước khi tách thành các module CSS.
- `omni-bridge-connector_original.php`: Bản monolithic plugin (1.045 dòng) trước khi tách thành 9 Traits trong `includes/`.
