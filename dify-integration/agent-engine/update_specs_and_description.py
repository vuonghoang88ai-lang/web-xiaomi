#!/usr/bin/env python3
"""
Nâng cấp toàn diện Bảng Thông Số Kỹ Thuật (37 tiêu chí kỹ thuật chuẩn)
và làm gọn nội dung giải trình súc tích, đanh thép, không rườm rà cho:
Tivi Xiaomi MAX 98 inch Mini LED 2026 (L98MC-STWN) - Post ID 79
"""

import urllib.request
import json

BRIDGE_URL = "http://localhost:8080/wp-json/omni-bridge/v1/publish"
BRIDGE_KEY = "obc_f0e92571a466e4bd96b8bbdb3ffc33db40090024"

FEATURED_IMAGE = "http://localhost:8080/wp-content/uploads/2026/10/tivi-xiaomi-s-mini-led-98-inch-hero.jpg"
GALLERY_IMAGES = [
    "http://localhost:8080/wp-content/uploads/2026/10/tivi-xiaomi-s-mini-led-98-inch-studio.jpg",
    "http://localhost:8080/wp-content/uploads/2026/10/cong-nghe-qd-mini-led-xiaomi-98-inch.jpg"
]

# 37 THÔNG SỐ KỸ THUẬT CHUẨN ĐIỆN MÁY - SÚC TÍCH, ĐỊNH LƯỢNG RÕ RÀNG, KHÔNG RƯỜM RÀ
DETAILED_TECHNICAL_SPECS = [
    # Màn hình & Hiển thị
    {"spec_label": "Kích thước màn hình", "spec_value": "98 inch (Đường chéo 248 cm)"},
    {"spec_label": "Công nghệ tấm nền", "spec_value": "QD-Mini LED (Quantum Dot kết hợp Mini LED)"},
    {"spec_label": "Độ phân giải", "spec_value": "4K Ultra HD (3.840 x 2.160 pixels)"},
    {"spec_label": "Số vùng làm mờ cục bộ", "spec_value": "880 phân vùng độc lập (Local Dimming)"},
    {"spec_label": "Độ sáng cực đại (Peak)", "spec_value": "1.200 nits"},
    {"spec_label": "Độ tương phản động", "spec_value": "12.000.000 : 1"},
    {"spec_label": "Tần số quét thực (Native)", "spec_value": "144Hz"},
    {"spec_label": "Chế độ Game Boost", "spec_value": "240Hz"},
    {"spec_label": "Công nghệ bù chuyển động", "spec_value": "MEMC 120Hz 4K"},
    {"spec_label": "Dải không gian màu", "spec_value": "94% DCI-P3 (1.07 tỷ màu, 10-bit)"},
    {"spec_label": "Độ chính xác màu sắc", "spec_value": "ΔE ≈ 2 (Cân chỉnh chuẩn phòng thu)"},
    {"spec_label": "Định dạng HDR hỗ trợ", "spec_value": "Dolby Vision IQ, HDR10+, HDR10, HLG"},
    {"spec_label": "Chứng nhận hình ảnh", "spec_value": "IMAX Enhanced, Filmmaker Mode"},
    {"spec_label": "Góc nhìn hiển thị", "spec_value": "178° ngang / 178° dọc"},

    # Cấu hình & Hệ điều hành
    {"spec_label": "Hệ điều hành", "spec_value": "Google TV™ (Giao diện tiếng Việt)"},
    {"spec_label": "Bộ vi xử lý (CPU)", "spec_value": "Quad-Core Cortex-A73 (64-bit)"},
    {"spec_label": "Bộ xử lý đồ họa (GPU)", "spec_value": "Mali-G52 MC1"},
    {"spec_label": "Bộ nhớ RAM", "spec_value": "4 GB"},
    {"spec_label": "Bộ nhớ trong (ROM)", "spec_value": "64 GB"},
    {"spec_label": "Điều khiển giọng nói", "spec_value": "Google Assistant tiếng Việt (Hỗ trợ ra lệnh rảnh tay)"},
    {"spec_label": "Trình chiếu không dây", "spec_value": "Google Cast, Apple AirPlay 2, Miracast"},

    # Âm thanh
    {"spec_label": "Tổng công suất loa", "spec_value": "30W (2 loa stereo 15W khoang âm độc lập)"},
    {"spec_label": "Công nghệ âm thanh", "spec_value": "Dolby Atmos, DTS:X, DTS Virtual:X"},

    # Kết nối & Cổng giao tiếp
    {"spec_label": "Cổng HDMI", "spec_value": "3 cổng: 2 x HDMI 2.1 (4K@144Hz, eARC, ALLM, VRR) + 1 x HDMI 2.0"},
    {"spec_label": "Cổng USB", "spec_value": "2 cổng: 1 x USB 3.0 + 1 x USB 2.0"},
    {"spec_label": "Cổng âm thanh số", "spec_value": "Optical (Digital Audio Out)"},
    {"spec_label": "Cổng âm thanh analog", "spec_value": "3.5 mm AUX Headphone"},
    {"spec_label": "Cổng mạng LAN", "spec_value": "Ethernet RJ-45"},
    {"spec_label": "Cổng Anten / Truyền hình", "spec_value": "RF (Hỗ trợ DVB-T2 / C)"},
    {"spec_label": "Kết nối Wi-Fi", "spec_value": "Wi-Fi 6 Băng tần kép (2.4GHz / 5GHz)"},
    {"spec_label": "Kết nối Bluetooth", "spec_value": "Bluetooth 5.0 Low Energy"},
    {"spec_label": "Hỗ trợ Gaming", "spec_value": "AMD FreeSync Premium, VRR, ALLM, Độ trễ 4ms"},

    # Kích thước, Trọng lượng & Lắp đặt
    {"spec_label": "Kích thước có chân đế", "spec_value": "2.179 x 1.308 x 441 mm (Ngang x Cao x Sâu)"},
    {"spec_label": "Kích thước không chân đế", "spec_value": "2.179 x 1.253 x 85 mm"},
    {"spec_label": "Kích thước thùng carton", "spec_value": "2.400 x 1.500 x 300 mm"},
    {"spec_label": "Trọng lượng máy có chân", "spec_value": "68.5 kg"},
    {"spec_label": "Trọng lượng nguyên thùng", "spec_value": "88.0 kg"},
    {"spec_label": "Chất liệu khung viền", "spec_value": "Hợp kim nhôm nguyên khối Unibody phay CNC"},
    {"spec_label": "Chuẩn ngàm treo tường (VESA)", "spec_value": "800 x 400 mm"},
    {"spec_label": "Điện áp hoạt động", "spec_value": "220V - 240V ~ 50/60Hz"},
    {"spec_label": "Công suất tiêu thụ", "spec_value": "450W (Chế độ chờ ≤ 0.5W)"}
]

# NỘI DUNG MÔ TẢ ĐANH THÉP, TRỌNG TÂM, KHÔNG RƯỜM RÀ SÁO RỖNG
CONCISE_IN_DEPTH_HTML = """<div class="product-in-depth-description text-slate-800 leading-relaxed text-[15px] space-y-6">

<p class="text-base md:text-lg leading-relaxed text-slate-900 font-medium">
<strong>Tivi Xiaomi S Mini LED 98 inch (Mã model: L98MC-STWN)</strong> là dòng tivi màn hình lớn cao cấp nhất của Xiaomi trong phân khúc 98 inch. Thiết bị tích hợp tấm nền <strong>QD-Mini LED với 880 vùng làm mờ độc lập</strong>, tần số quét <strong>144Hz Native</strong> và hệ điều hành <strong>Google TV bản quyền</strong>, mang lại trải nghiệm xem phim điện ảnh và thể thao tốc độ cao cho phòng khách rộng từ 35m² trở lên, căn hộ penthouse và phòng chiếu gia đình.
</p>

<!-- 1. KÍCH THƯỚC VÀ KHÔNG GIAN LẮP ĐẶT -->
<h2 class="text-xl md:text-2xl font-bold text-slate-900 mt-8 mb-4 border-l-4 border-orange-500 pl-3">
1. Khung Hình 98 Inch Khổng Lồ & Thiết Kế Viền Nhôm Unibody
</h2>

<p>
Màn hình 98 inch có chiều rộng 2.18 mét và chiều cao 1.25 mét, diện tích hiển thị tương đương 4 chiếc tivi 50 inch ghép lại. Khung viền sử dụng hợp kim nhôm phay CNC nguyên khối với tỷ lệ hiển thị đạt <strong>97.8%</strong>, giúp tối ưu tối đa tầm nhìn mà không bị phân tâm bởi viền máy.
</p>

<div class="my-6 text-center">
    <img src="/wp-content/uploads/2026/10/tivi-xiaomi-s-mini-led-98-inch-hero.jpg" 
         alt="Tivi Xiaomi S Mini LED 98 inch lắp đặt phòng khách hiện đại" 
         class="rounded-xl shadow-md mx-auto w-full max-w-4xl object-cover" 
         loading="lazy" />
    <p class="text-xs text-slate-500 italic mt-2">Màn hình 98 inch tỷ lệ tràn viền 97.8% lắp đặt trên kệ và giá treo tường chuyên dụng.</p>
</div>

<ul class="list-disc pl-6 space-y-1.5">
    <li><strong>Khoảng cách xem khuyến nghị:</strong> Từ 3.5 mét đến 5.5 mét để mắt bao quát trọn vẹn độ phân giải 4K mà không mỏi cơ mắt.</li>
    <li><strong>Khả năng lắp đặt linh hoạt:</strong> Kèm cặp chân đế kim loại chữ V chịu lực; mặt lưng bố trí ngàm VESA 800 x 400 mm tương thích các loại giá treo nghiêng hoặc áp tường chịu tải từ 80kg - 100kg.</li>
</ul>

<!-- 2. CÔNG NGHỆ MINI LED -->
<h2 class="text-xl md:text-2xl font-bold text-slate-900 mt-8 mb-4 border-l-4 border-orange-500 pl-3">
2. Tấm Nền QD-Mini LED 880 Vùng Sáng & Độ Sáng Đỉnh 1.200 Nits
</h2>

<p>
Khác với đèn nền LED cạnh thông thường dễ bị hở sáng, hệ thống đèn nền Mini LED của Xiaomi chia tách thành <strong>880 vùng kiểm soát ánh sáng cục bộ (Local Dimming)</strong>. Mỗi vùng có khả năng tự động bật/tắt hoặc tăng giảm độ sáng độc lập tùy theo khung hình hiển thị.
</p>

<div class="my-6 text-center">
    <img src="/wp-content/uploads/2026/10/cong-nghe-qd-mini-led-xiaomi-98-inch.jpg" 
         alt="Cấu trúc đa lớp QD-Mini LED với 880 phân vùng ánh sáng độc lập" 
         class="rounded-xl shadow-md mx-auto w-full max-w-4xl object-cover" 
         loading="lazy" />
    <p class="text-xs text-slate-500 italic mt-2">Ma trận Mini LED kiểm soát độ đen sâu và triệt tiêu quầng sáng xung quanh phụ đề.</p>
</div>

<ul class="list-disc pl-6 space-y-1.5">
    <li><strong>Độ tương phản 12.000.000:1:</strong> Vùng tối đạt mức đen sâu tiệm cận OLED mà không gặp rủi ro lưu ảnh (burn-in).</li>
    <li><strong>Độ sáng 1.200 nits:</strong> Đảm bảo hình ảnh rõ nét, không bị lóa khi xem vào ban ngày ở phòng khách có nhiều cửa sổ hoặc ánh sáng mạnh.</li>
    <li><strong>Lớp chấm lượng tử Quantum Dot:</strong> Tái tạo 94% dải màu điện ảnh DCI-P3 với 1.07 tỷ màu hiển thị, hỗ trợ các chuẩn HDR khắt khe: <strong>Dolby Vision IQ, HDR10+, HLG</strong> và <strong>IMAX Enhanced</strong>.</li>
</ul>

<!-- 3. TẦN SỐ QUÉT 144HZ & GAMING -->
<h2 class="text-xl md:text-2xl font-bold text-slate-900 mt-8 mb-4 border-l-4 border-orange-500 pl-3">
3. Tần Số Quét Thực 144Hz & Cổng HDMI 2.1 Băng Thông Cao Cho Game Thủ
</h2>

<p>
Tivi trang bị tấm nền tần số quét bản địa <strong>144Hz Native</strong> cùng công nghệ bù khung hình <strong>MEMC 120Hz 4K</strong>, giải quyết triệt để tình trạng bóng ma khi xem bóng đá, quần vợt hoặc các pha đua xe hành động nhanh.
</p>

<ul class="list-disc pl-6 space-y-1.5">
    <li><strong>Cổng kết nối HDMI 2.1 kép:</strong> Băng thông đầy đủ hỗ trợ truyền hình ảnh 4K@144Hz từ máy chơi game PlayStation 5, Xbox Series X hoặc PC Gaming cao cấp.</li>
    <li><strong>Hỗ trợ VRR & ALLM:</strong> Tự động kích hoạt chế độ độ trễ thấp (4ms) và đồng bộ khung hình với công nghệ <strong>AMD FreeSync™ Premium</strong>, ngăn ngừa hiện tượng xé hình khi chơi game đồ họa nặng.</li>
    <li><strong>Chế độ Game Boost 240Hz:</strong> Cho phép tăng tốc tần số quét lên 240Hz khi kết nối máy tính ở độ phân giải 1080p.</li>
</ul>

<!-- 4. ÂM THANH VÀ PHẦN CỨNG -->
<h2 class="text-xl md:text-2xl font-bold text-slate-900 mt-8 mb-4 border-l-4 border-orange-500 pl-3">
4. Hệ Thống Âm Thanh Dolby Atmos 30W & Cấu Hình Google TV Mượt Mà
</h2>

<div class="my-6 text-center">
    <img src="/wp-content/uploads/2026/10/tivi-xiaomi-s-mini-led-98-inch-studio.jpg" 
         alt="Góc chụp thực tế mặt trước và chi tiết hoàn thiện của Tivi Xiaomi 98 inch" 
         class="rounded-xl shadow-md mx-auto w-full max-w-4xl object-cover" 
         loading="lazy" />
    <p class="text-xs text-slate-500 italic mt-2">Loa kép 30W tích hợp công nghệ giải mã âm thanh Dolby Atmos và DTS:X 3D.</p>
</div>

<ul class="list-disc pl-6 space-y-1.5">
    <li><strong>Loa Stereo kép 30W (2 x 15W):</strong> Khoang âm độc lập hỗ trợ giải mã âm thanh vòm <strong>Dolby Atmos</strong> và <strong>DTS:X</strong>. Hỗ trợ cổng xuất âm thanh quang học Optical và HDMI eARC để kết nối dàn soundbar rời.</li>
    <li><strong>Chipset Cortex-A73 Quad-Core, RAM 4GB, ROM 64GB:</strong> Xử lý nhanh các tác vụ mở app, chuyển kênh, chạy mượt các ứng dụng 4K nặng mà không bị giật lag.</li>
    <li><strong>Hệ điều hành Google TV chính hãng:</strong> Cung cấp kho nội dung hơn 10.000 ứng dụng (YouTube, Netflix, K+, VieON, FPT Play). Hỗ trợ điều khiển giọng nói tiếng Việt rảnh tay qua micro tích hợp trên thân tivi (Hands-free Voice Control).</li>
    <li><strong>Kết nối không dây Wi-Fi 6:</strong> Đảm bảo truyền phát video 4K HDR ổn định, không bị đứt đoạn qua mạng không dây nội bộ.</li>
</ul>

<!-- 5. SO SÁNH VỚI ĐỐI THỦ -->
<h2 class="text-xl md:text-2xl font-bold text-slate-900 mt-8 mb-4 border-l-4 border-orange-500 pl-3">
5. Bảng So Sánh Thông Số Trực Tiếp Trong Phân Khúc Tivi 98 Inch
</h2>

<div class="overflow-x-auto my-4">
<table class="min-w-full border-collapse border border-slate-200 text-sm">
    <thead class="bg-slate-100 text-slate-800 font-bold">
        <tr>
            <th class="border border-slate-300 p-2.5 text-left">Tiêu chí kỹ thuật</th>
            <th class="border border-slate-300 p-2.5 text-left text-orange-600 bg-orange-50 font-bold">Xiaomi S Mini LED 98" (L98MC-STWN)</th>
            <th class="border border-slate-300 p-2.5 text-left">Samsung Neo QLED 98" (98QN90D)</th>
            <th class="border border-slate-300 p-2.5 text-left">TCL QD-Mini LED 98" (98C855)</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-slate-200">
        <tr>
            <td class="border border-slate-300 p-2.5 font-medium">Công nghệ hiển thị</td>
            <td class="border border-slate-300 p-2.5 text-orange-600 bg-orange-50 font-semibold">QD-Mini LED (880 zones)</td>
            <td class="border border-slate-300 p-2.5">Neo QLED Mini LED</td>
            <td class="border border-slate-300 p-2.5">QD-Mini LED (1.344 zones)</td>
        </tr>
        <tr>
            <td class="border border-slate-300 p-2.5 font-medium">Tần số quét</td>
            <td class="border border-slate-300 p-2.5 text-orange-600 bg-orange-50 font-semibold">144Hz Native / 240Hz Boost</td>
            <td class="border border-slate-300 p-2.5">120Hz / 144Hz</td>
            <td class="border border-slate-300 p-2.5">144Hz Native</td>
        </tr>
        <tr>
            <td class="border border-slate-300 p-2.5 font-medium">Hệ điều hành</td>
            <td class="border border-slate-300 p-2.5 text-orange-600 bg-orange-50 font-semibold">Google TV (Mở, nhiều ứng dụng)</td>
            <td class="border border-slate-300 p-2.5">Tizen OS (Đóng)</td>
            <td class="border border-slate-300 p-2.5">Google TV</td>
        </tr>
        <tr>
            <td class="border border-slate-300 p-2.5 font-medium">Bộ nhớ (RAM / ROM)</td>
            <td class="border border-slate-300 p-2.5 text-orange-600 bg-orange-50 font-semibold">4GB RAM / 64GB ROM</td>
            <td class="border border-slate-300 p-2.5">Không công bố</td>
            <td class="border border-slate-300 p-2.5">3GB RAM / 64GB ROM</td>
        </tr>
        <tr>
            <td class="border border-slate-300 p-2.5 font-medium">Giá bán niêm yết</td>
            <td class="border border-slate-300 p-2.5 text-red-600 bg-orange-50 font-bold">42.990.000 đ</td>
            <td class="border border-slate-300 p-2.5">119.000.000 đ</td>
            <td class="border border-slate-300 p-2.5">62.000.000 đ</td>
        </tr>
    </tbody>
</table>
</div>

<!-- 6. DỊCH VỤ VẬN CHUYỂN VÀ BẢO HÀNH -->
<h2 class="text-xl md:text-2xl font-bold text-slate-900 mt-8 mb-4 border-l-4 border-orange-500 pl-3">
6. Quy Trình Khảo Sát & Lắp Đặt Chuyên Nghiệp Tại TongKhoMi.com
</h2>

<ul class="list-disc pl-6 space-y-2">
    <li><strong>Khảo sát trước khi giao hàng:</strong> Kỹ thuật viên đo đạc cửa thang máy, hành lang và góc quay cầu thang để lên phương án vận chuyển an toàn.</li>
    <li><strong>Đội ngũ chuyên trách:</strong> Bố trí 4 nhân sự chuyên môn cùng thiết bị trợ lực vận chuyển trực tiếp đến vị trí phòng khách.</li>
    <li><strong>Lắp đặt giá treo chịu tải:</strong> Sử dụng bu lông nở thép chịu lực cho tường bê tông, kiểm tra cân bằng laser trước khi bàn giao.</li>
    <li><strong>Chính sách bảo hành:</strong> Bảo hành chính hãng <strong>24 tháng tại nhà</strong>. Đổi mới trong 30 ngày đầu tiên nếu phát sinh lỗi từ nhà sản xuất.</li>
</ul>

</div>"""

def update_product():
    print("🚀 Đang cập nhật Bảng Thông Số Kỹ Thuật (37 thông số chuẩn) & Nội dung tinh gọn cho Post #79...")
    payload = {
        "post_id": 79,
        "post_title": "Tivi Xiaomi S Mini LED 98 inch 144Hz 2026 (L98MC-STWN) – Đẳng Cấp Biệt Thự",
        "post_type": "product",
        "post_status": "publish",
        "post_slug": "tivi-xiaomi-max-98-inch-mini-led-l98mc-stwn",
        "post_content": CONCISE_IN_DEPTH_HTML,
        "post_excerpt": "Smart Tivi Xiaomi S Mini LED 98 inch (L98MC-STWN) chính hãng model 2026. Màn hình QD-Mini LED 880 vùng sáng, 144Hz Native, chip Cortex-A73, RAM 4GB, ROM 64GB, âm thanh Dolby Atmos 30W. Bảo hành 24 tháng tại nhà.",
        "featured_image_url": FEATURED_IMAGE,
        "categories": ["Tivi Xiaomi"],
        "product_data": {
            "sku": "L98MC-STWN",
            "regular_price": "52000000",
            "sale_price": "42990000",
            "stock_status": "instock",
            "gallery_image_urls": GALLERY_IMAGES,
            "technical_specs": DETAILED_TECHNICAL_SPECS
        },
        "seo_meta": {
            "seo_title": "Tivi Xiaomi S Mini LED 98 inch (L98MC-STWN) Giá Tốt Nhất 2026 | TongKhoMi",
            "seo_description": "Mua Tivi Xiaomi S Mini LED 98 inch (L98MC-STWN) chính hãng giá 42.990.000đ. QD-Mini LED 880 vùng sáng, 144Hz, RAM 4GB, ROM 64GB, Dolby Atmos. Khảo sát lắp đặt tận nơi.",
            "focus_keyword": "tivi xiaomi s mini led 98 inch"
        }
    }

    req = urllib.request.Request(
        BRIDGE_URL,
        data=json.dumps(payload).encode("utf-8"),
        headers={
            "X-Omni-Bridge-Key": BRIDGE_KEY,
            "Content-Type": "application/json"
        }
    )

    try:
        with urllib.request.urlopen(req, timeout=30) as resp:
            data = json.loads(resp.read().decode("utf-8"))
            print("✅ Cập nhật thành công 37 thông số kỹ thuật chuẩn và nội dung tinh gọn!")
            print(json.dumps(data, indent=2, ensure_ascii=False))
    except Exception as e:
        print(f"❌ Lỗi cập nhật: {e}")

if __name__ == "__main__":
    update_product()
