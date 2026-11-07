# TIÊU CHUẨN CẤU TRÚC DANH MỤC SẢN PHẨM, GIÁ VỐN (COGS) & THUỘC TÍNH ĐA NGÀNH (SOP 15)

> **Phiên bản**: 2.0 — Ban hành ngày 01/11/2026  
> **Phạm vi áp dụng**: Toàn bộ hệ thống Omni Marketing SaaS Engine & C-Suite Boardroom.  
> **Nguyên tắc**: Chuẩn hóa cấu trúc dữ liệu danh mục sản phẩm và giá vốn (COGS) cho mọi ngành hàng E-Commerce/Dịch vụ.

---

## 1. MỤC TIÊU & NGUYÊN TẮC THIẾT KẾ SCHEMA
- **Độc lập nền tảng & ngành hàng**: Cung cấp cấu trúc chuẩn để nhập liệu sản phẩm từ bất kỳ ngành nào (Điện máy, Thời trang, Mỹ phẩm, Y tế/Nha khoa, Nội thất).
- **Phục vụ tự động hóa CFO & Boardroom**: Cung cấp đầy đủ 3 trường tài chính bắt buộc: Giá nhập sỉ (COGS), Giá bán lẻ khuyến nghị (MSRP) và Biên lợi nhuận gộp (Gross Margin) để CFO Agent có thể tính toán bài toán dòng tiền tự động.

---

## 2. CẤU TRÚC BẢNG DỮ LIỆU SẢN PHẨM TIÊU CHUẨN

Mỗi dự án khi đưa sản phẩm vào hệ thống cần tuân thủ cấu trúc 6 trường sau:

| Tên Trường (Field) | Kiểu Dữ Liệu | Bắt Buộc | Mô Tả & Ý Nghĩa |
| :--- | :--- | :--- | :--- |
| `sku_code` | Chuỗi (String) | Có | Mã định danh duy nhất của sản phẩm hoặc phiên bản (Model Code / SKU). |
| `product_name` | Chuỗi (String) | Có | Tên thương mại hiển thị trên website và tài liệu bán hàng. |
| `variant_specs` | Chuỗi / JSON | Có | Thuộc tính kích thước, màu sắc, công nghệ hoặc phân khúc (VD: 55 inch, Size L, 50ml). |
| `cogs_import_price` | Số (Decimal) | Có | Giá vốn nhập kho thực tế (Cost of Goods Sold) của doanh nghiệp. |
| `msrp_selling_price`| Số (Decimal) | Có | Giá bán lẻ niêm yết dự kiến trên thị trường. |
| `target_audience` | Chuỗi (String) | Tùy chọn | Không gian, đối tượng hoặc bối cảnh sử dụng phù hợp nhất. |

---

## 3. VÍ DỤ MINH HỌA ÁP DỤNG TRÊN CÁC NGÀNH NGHỀ

### Ví dụ 1: Ngành Điện Tử / Gia Dụng (Consumer Electronics)
| SKU / Model | Tên Sản Phẩm | Thuộc Tính Kỹ Thuật | Giá Vốn (COGS) | Giá Bán (MSRP) | Biên Lợi Nhuận Gộp |
|---|---|---|---|---|---|
| `TV-43-4K-01` | Smart TV 43 inch | 4K UHD 60Hz MEMC, Android TV | 5.350.000₫ | 6.490.000₫ | ~17.5% (1.140.000₫) |
| `TV-55-QLED-02`| Smart TV 55 inch QLED | 4K Quantum Dot 120Hz Dolby Vision | 7.900.000₫ | 9.690.000₫ | ~18.4% (1.790.000₫) |
| `ROBOT-X20` | Robot Hút Bụi Tự Giặt | Lực hút 6000Pa, Trạm sạc All-in-One | 8.200.000₫ | 11.490.000₫ | ~28.6% (3.290.000₫) |

### Ví dụ 2: Ngành Thời Trang & Phụ Kiện (Fashion & Apparel)
| SKU / Model | Tên Sản Phẩm | Thuộc Tính Kỹ Thuật | Giá Vốn (COGS) | Giá Bán (MSRP) | Biên Lợi Nhuận Gộp |
|---|---|---|---|---|---|
| `SHIRT-OXF-01` | Áo Sơ Mi Oxford Dài Tay | 100% Cotton Chống Nhăn, Size M-XL | 110.000₫ | 350.000₫ | ~68.5% (240.000₫) |
| `JEAN-SLIM-02` | Quần Jean Nam Co Giãn | Vải Denim 12oz, Dáng Slim-fit | 180.000₫ | 490.000₫ | ~63.2% (310.000₫) |

### Ví dụ 3: Ngành Dịch Vụ Nha Khoa / Y Tế (Dental & Health Services)
| Mã Dịch Vụ | Tên Dịch Vụ / Gói | Quy Cách & Phạm Vi | Chi Phí Vốn (Vật Liệu + Labo) | Giá Niêm Yết | Biên Lợi Nhuận Gộp |
|---|---|---|---|---|---|
| `IMPLANT-KR-01`| Trồng Răng Implant Hàn Quốc | Trụ Dentium + Abutment + Mão sứ | 4.500.000₫ | 14.000.000₫ | ~67.8% (9.500.000₫) |
| `BRACES-METAL` | Niềng Răng Mắc Cài Kim Loại | Trọn gói chỉnh nha 2 hàm 18 - 24 tháng | 6.000.000₫ | 28.000.000₫ | ~78.5% (22.000.000₫) |

---

## 4. QUY TẮC LƯU TRỮ TRONG HỆ THỐNG
- Dữ liệu sản phẩm cụ thể của từng khách hàng được lưu vào `projects.brand_profile.cogs_catalog` trong cơ sở dữ liệu riêng của dự án.
- Tuyệt đối không lưu dữ liệu sản phẩm đơn lẻ vào kho tài liệu SOP dùng chung của hệ thống.

