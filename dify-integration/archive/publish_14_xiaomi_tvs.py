#!/usr/bin/env python3
"""
Tự động đăng 14 Sản Phẩm Tivi Xiaomi 2026 vào WooCommerce qua Omni Bridge Connector:
- Đầy đủ 4 phân khúc: FHD, 4K, QLED, S Mini LED 2026
- Gắn vào danh mục có sẵn: 'Tivi Xiaomi' (ID 17)
- Chuẩn hóa SEO Rank Math Title, Focus Keyword, Meta Description
- Cấu hình SKU, Giá niêm yết (regular_price) và Giá khuyến mãi (sale_price)
"""

import urllib.request
import json

BRIDGE_URL = "http://localhost:8080/wp-json/omni-bridge/v1/publish"
BRIDGE_KEY = "obc_f0e92571a466e4bd96b8bbdb3ffc33db40090024"

TV_PRODUCTS = [
    # 1. Dòng FHD
    {
        "sku": "L43MB-AFSEA",
        "title": "Tivi Xiaomi 43 inch Full HD A Series (L43MB-AFSEA) – Chính Hãng",
        "slug": "tivi-xiaomi-43-inch-fhd-l43mb-afsea",
        "inch": "43 inch",
        "tech": "FHD",
        "net_price": 4900000,
        "sale_price": 5490000,
        "regular_price": 6990000,
        "specs": [
            {"spec_label": "Kích thước màn hình", "spec_value": "43 inch (108 cm)"},
            {"spec_label": "Độ phân giải", "spec_value": "Full HD (1920 x 1080)"},
            {"spec_label": "Công nghệ hình ảnh", "spec_value": "HDR10, Giảm nhiễu DNR, Tần số quét 60Hz"},
            {"spec_label": "Hệ điều hành", "spec_value": "Google TV (Hỗ trợ tiếng Việt)"},
            {"spec_label": "Âm thanh", "spec_value": "2 loa 8W, Dolby Audio, DTS-HD"},
            {"spec_label": "Cổng kết nối", "spec_value": "2 x HDMI, 2 x USB 2.0, LAN, Wi-Fi 2.4/5GHz, Bluetooth 5.0"},
            {"spec_label": "Bảo hành", "spec_value": "24 tháng chính hãng tại nhà, 1 đổi 1 trong 30 ngày"}
        ]
    },
    # 2. Dòng 4K UHD
    {
        "sku": "L43MB-AUSEA",
        "title": "Smart Tivi Xiaomi 43 inch 4K HDR A Series (L43MB-AUSEA)",
        "slug": "tivi-xiaomi-43-inch-4k-l43mb-ausea",
        "inch": "43 inch",
        "tech": "4K UHD",
        "net_price": 5350000,
        "sale_price": 5990000,
        "regular_price": 7490000,
        "specs": [
            {"spec_label": "Kích thước màn hình", "spec_value": "43 inch (Viền kim loại siêu mỏng)"},
            {"spec_label": "Độ phân giải", "spec_value": "4K Ultra HD (3840 x 2160)"},
            {"spec_label": "Công nghệ hình ảnh", "spec_value": "HDR10+, HLG, 1.07 tỷ màu hiển thị"},
            {"spec_label": "Hệ điều hành", "spec_value": "Google TV bản quyền, Google Assistant"},
            {"spec_label": "Âm thanh", "spec_value": "2 x 10W, DTS Virtual:X, Dolby Audio"},
            {"spec_label": "Bảo hành", "spec_value": "24 tháng chính hãng tại nhà"}
        ]
    },
    {
        "sku": "L55MB-ASEA",
        "title": "Smart Tivi Xiaomi 55 inch 4K A Series (L55MB-ASEA) – Mới 2026",
        "slug": "tivi-xiaomi-55-inch-4k-l55mb-asea",
        "inch": "55 inch",
        "tech": "4K UHD",
        "net_price": 6950000,
        "sale_price": 7890000,
        "regular_price": 9990000,
        "specs": [
            {"spec_label": "Kích thước màn hình", "spec_value": "55 inch"},
            {"spec_label": "Độ phân giải", "spec_value": "4K UHD (3840 x 2160)"},
            {"spec_label": "Công nghệ hình ảnh", "spec_value": "HDR10, MEMC bù chuyển động mượt mà"},
            {"spec_label": "Hệ điều hành", "spec_value": "Google TV thông minh"},
            {"spec_label": "Âm thanh", "spec_value": "20W Stereo Sound, Dolby Audio"},
            {"spec_label": "Bảo hành", "spec_value": "24 tháng chính hãng"}
        ]
    },
    {
        "sku": "L65MB-ASEA",
        "title": "Smart Tivi Xiaomi 65 inch 4K A Series (L65MB-ASEA) – Màn Lớn Đẳng Cấp",
        "slug": "tivi-xiaomi-65-inch-4k-l65mb-asea",
        "inch": "65 inch",
        "tech": "4K UHD",
        "net_price": 9400000,
        "sale_price": 10690000,
        "regular_price": 13490000,
        "specs": [
            {"spec_label": "Kích thước màn hình", "spec_value": "65 inch (164 cm)"},
            {"spec_label": "Độ phân giải", "spec_value": "4K UHD (3840 x 2160)"},
            {"spec_label": "Công nghệ màn hình", "spec_value": "Tràn viền 97%, HDR10, MEMC 60Hz"},
            {"spec_label": "Hệ thống loa", "spec_value": "Kép 24W, Hi-Res Audio, Dolby Audio"},
            {"spec_label": "Bảo hành", "spec_value": "24 tháng chính hãng tại nhà"}
        ]
    },
    # 3. Dòng QLED A Pro
    {
        "sku": "L32MB-APSEA",
        "title": "Tivi Xiaomi QLED 32 inch A Pro (L32MB-APSEA) – Nhỏ Gọn Sắc Nét",
        "slug": "tivi-xiaomi-qled-32-inch-l32mb-apsea",
        "inch": "32 inch",
        "tech": "QLED",
        "net_price": 3350000,
        "sale_price": 3890000,
        "regular_price": 4990000,
        "specs": [
            {"spec_label": "Kích thước màn hình", "spec_value": "32 inch"},
            {"spec_label": "Công nghệ màn hình", "spec_value": "Chấm lượng tử Quantum Dot QLED 94% DCI-P3"},
            {"spec_label": "Độ phân giải", "spec_value": "HD Ready sắc nét"},
            {"spec_label": "Hệ điều hành", "spec_value": "Google TV (Hỗ trợ giọng nói)"},
            {"spec_label": "Bảo hành", "spec_value": "24 tháng chính hãng"}
        ]
    },
    {
        "sku": "L43MB-APSEA",
        "title": "Tivi Xiaomi QLED 43 inch A Pro 4K (L43MB-APSEA) – Màu Sắc Trung Thực",
        "slug": "tivi-xiaomi-qled-43-inch-l43mb-apsea",
        "inch": "43 inch",
        "tech": "QLED",
        "net_price": 5900000,
        "sale_price": 6690000,
        "regular_price": 8290000,
        "specs": [
            {"spec_label": "Kích thước màn hình", "spec_value": "43 inch"},
            {"spec_label": "Công nghệ màn hình", "spec_value": "QLED Quantum Dot 4K UHD"},
            {"spec_label": "Hệ điều hành", "spec_value": "Google TV"},
            {"spec_label": "Bảo hành", "spec_value": "24 tháng chính hãng tại nhà"}
        ]
    },
    {
        "sku": "L55MB-APSEA",
        "title": "Tivi Xiaomi QLED 55 inch A Pro (L55MB-APSEA) – Chuẩn Điện Ảnh",
        "slug": "tivi-xiaomi-qled-55-inch-l55mb-apsea",
        "inch": "55 inch",
        "tech": "QLED",
        "net_price": 7900000,
        "sale_price": 8990000,
        "regular_price": 11490000,
        "specs": [
            {"spec_label": "Kích thước màn hình", "spec_value": "55 inch"},
            {"spec_label": "Công nghệ màn hình", "spec_value": "QLED Quantum Dot 4K, MEMC 60Hz"},
            {"spec_label": "Âm thanh", "spec_value": "Loa đôi 24W, Dolby Atmos tương thích"},
            {"spec_label": "Bảo hành", "spec_value": "24 tháng chính hãng"}
        ]
    },
    {
        "sku": "L65MB-APSEA",
        "title": "Tivi Xiaomi QLED 65 inch A Pro 4K (L65MB-APSEA) – Phòng Khách Đẳng Cấp",
        "slug": "tivi-xiaomi-qled-65-inch-l65mb-apsea",
        "inch": "65 inch",
        "tech": "QLED",
        "net_price": 10500000,
        "sale_price": 11990000,
        "regular_price": 14990000,
        "specs": [
            {"spec_label": "Kích thước màn hình", "spec_value": "65 inch"},
            {"spec_label": "Công nghệ màn hình", "spec_value": "QLED 4K, Dolby Vision, DCI-P3 94%"},
            {"spec_label": "Bảo hành", "spec_value": "24 tháng chính hãng tại nhà"}
        ]
    },
    {
        "sku": "L75MB-APSEA",
        "title": "Tivi Xiaomi QLED 75 inch A Pro Siêu Lớn (L75MB-APSEA) – Rạp Chiếu Tại Gia",
        "slug": "tivi-xiaomi-qled-75-inch-l75mb-apsea",
        "inch": "75 inch",
        "tech": "QLED",
        "net_price": 13600000,
        "sale_price": 15490000,
        "regular_price": 19990000,
        "specs": [
            {"spec_label": "Kích thước màn hình", "spec_value": "75 inch (190 cm)"},
            {"spec_label": "Công nghệ hiển thị", "spec_value": "Quantum Dot QLED 4K HDR10+"},
            {"spec_label": "Bảo hành", "spec_value": "24 tháng chính hãng tại nhà"}
        ]
    },
    # 4. Dòng S MINI LED 2026 Flagship
    {
        "sku": "L55MC-SSEA",
        "title": "Tivi Xiaomi S55 Mini LED 55 inch 144Hz (L55MC-SSEA) – Siêu Phẩm 2026",
        "slug": "tivi-xiaomi-s55-mini-led-l55mc-ssea",
        "inch": "55 inch",
        "tech": "S Mini LED 2026",
        "net_price": 9400000,
        "sale_price": 10990000,
        "regular_price": 13990000,
        "specs": [
            {"spec_label": "Kích thước màn hình", "spec_value": "55 inch"},
            {"spec_label": "Công nghệ màn hình", "spec_value": "Mini LED 308 vùng sáng độc lập, Độ sáng 1.200 nits"},
            {"spec_label": "Tần số quét", "spec_value": "144Hz Native (Hỗ trợ 240Hz Gaming Mode)"},
            {"spec_label": "Cổng kết nối", "spec_value": "HDMI 2.1 x 2 (VRR, ALLM, FreeSync Premium)"},
            {"spec_label": "Bảo hành", "spec_value": "24 tháng chính hãng"}
        ]
    },
    {
        "sku": "L65MC-SSEA",
        "title": "Tivi Xiaomi S65 Mini LED 65 inch 144Hz (L65MC-SSEA) – Trải Nghiệm Đỉnh Cao",
        "slug": "tivi-xiaomi-s65-mini-led-l65mc-ssea",
        "inch": "65 inch",
        "tech": "S Mini LED 2026",
        "net_price": 12400000,
        "sale_price": 14290000,
        "regular_price": 17990000,
        "specs": [
            {"spec_label": "Kích thước màn hình", "spec_value": "65 inch"},
            {"spec_label": "Công nghệ màn hình", "spec_value": "Mini LED 392 vùng sáng, 1.200 nits đỉnh sáng"},
            {"spec_label": "Tần số quét", "spec_value": "144Hz, Vi xử lý Quad-Core A73 mạnh mẽ"},
            {"spec_label": "Bảo hành", "spec_value": "24 tháng chính hãng"}
        ]
    },
    {
        "sku": "L75MC-SSEA",
        "title": "Tivi Xiaomi S75 Mini LED 75 inch Flagship (L75MC-SSEA) – Chuẩn Rạp Chiếu",
        "slug": "tivi-xiaomi-s75-mini-led-l75mc-ssea",
        "inch": "75 inch",
        "tech": "S Mini LED 2026",
        "net_price": 17200000,
        "sale_price": 19890000,
        "regular_price": 24990000,
        "specs": [
            {"spec_label": "Kích thước màn hình", "spec_value": "75 inch"},
            {"spec_label": "Công nghệ hiển thị", "spec_value": "Mini LED 512 vùng làm mờ cục bộ, Dolby Vision IQ"},
            {"spec_label": "Tần số quét", "spec_value": "144Hz Gaming, Âm thanh 30W công suất lớn"},
            {"spec_label": "Bảo hành", "spec_value": "24 tháng chính hãng tại nhà"}
        ]
    },
    {
        "sku": "L85MC-STWN",
        "title": "Tivi Xiaomi S85 Mini LED 85 inch Màn Hình Khổng Lồ (L85MC-STWN)",
        "slug": "tivi-xiaomi-s85-mini-led-l85mc-stwn",
        "inch": "85 inch",
        "tech": "S Mini LED 2026",
        "net_price": 26000000,
        "sale_price": 29990000,
        "regular_price": 36990000,
        "specs": [
            {"spec_label": "Kích thước màn hình", "spec_value": "85 inch (216 cm)"},
            {"spec_label": "Công nghệ hiển thị", "spec_value": "Mini LED 640 vùng sáng, 1.400 nits, IMAX Enhanced"},
            {"spec_label": "Tần số quét", "spec_value": "144Hz VRR Native"},
            {"spec_label": "Bảo hành", "spec_value": "24 tháng chính hãng tại nhà"}
        ]
    },
    {
        "sku": "L98MC-STWN",
        "title": "Tivi Xiaomi MAX 98 inch Mini LED 2026 Đẳng Cấp Biệt Thự (L98MC-STWN)",
        "slug": "tivi-xiaomi-max-98-inch-mini-led-l98mc-stwn",
        "inch": "98 inch",
        "tech": "S Mini LED 2026",
        "net_price": 37500000,
        "sale_price": 42990000,
        "regular_price": 52000000,
        "specs": [
            {"spec_label": "Kích thước màn hình", "spec_value": "98 inch (Khổng lồ 2.5 mét)"},
            {"spec_label": "Công nghệ hiển thị", "spec_value": "Mini LED Flagship 1024 vùng sáng, 1.600 nits"},
            {"spec_label": "Hệ thống âm thanh", "spec_value": "Subwoofer tích hợp 50W, Dolby Atmos 3D"},
            {"spec_label": "Tần số quét", "spec_value": "144Hz Gaming HDMI 2.1 eARC"},
            {"spec_label": "Bảo hành", "spec_value": "24 tháng chính hãng tại nhà, Lắp đặt chuyên biệt"}
        ]
    }
]

def generate_product_html(item):
    return f"""<div class="product-in-depth-description text-slate-700 leading-relaxed text-[15px]">
<p class="text-lg leading-relaxed text-slate-800 font-medium mb-6">
<strong>{item['title']}</strong> là model tivi thông minh thế hệ mới 2026 được phân phối chính hãng tại <strong>TongKhoMi.com / Michinhhang.com</strong>. Sở hữu công nghệ hiển thị <strong>{item['tech']}</strong> đỉnh cao trên màn hình <strong>{item['inch']}</strong>, sản phẩm mang lại trải nghiệm giải trí điện ảnh sống động cùng độ tương phản chân thực bậc nhất trong phân khúc.
</p>

<h2 class="text-xl md:text-2xl font-bold text-slate-900 mt-8 mb-4 border-l-4 border-orange-500 pl-3">
1. Đặc Điểm Nổi Bật Của {item['title']}
</h2>
<ul class="list-disc pl-6 mb-5 space-y-2">
    <li><strong>Màn hình {item['inch']} chuẩn {item['tech']}:</strong> Tối ưu hóa màu sắc rực rỡ, độ phân giải sắc nét từng chi tiết nhỏ.</li>
    <li><strong>Hệ điều hành Google TV bản quyền:</strong> Kho ứng dụng khổng lồ (YouTube, Netflix, VieON, FPT Play, TV360) điều khiển giọng nói tiếng Việt mượt mà.</li>
    <li><strong>Thiết kế tràn viền kim loại sang trọng:</strong> Tỉ lệ màn hình trên thân máy lên tới 97%, tôn vinh vẻ đẹp hiện đại của mọi không gian phòng khách.</li>
    <li><strong>Âm thanh vòm đa chiều:</strong> Tích hợp công nghệ Dolby Audio và DTS-HD cho âm bass uy lực, lời thoại trong trẻo.</li>
</ul>

<h2 class="text-xl md:text-2xl font-bold text-slate-900 mt-8 mb-4 border-l-4 border-orange-500 pl-3">
2. Chính Sách Bán Hàng & Hậu Mãi Vượt Trội Tại TongKhoMi.com
</h2>
<ul class="list-disc pl-6 mb-5 space-y-2">
    <li><strong>Cam kết 100% chính hãng:</strong> Bảo hành 24 tháng chính hãng tại nhà toàn quốc.</li>
    <li><strong>1 đổi 1 trong 30 ngày:</strong> Đổi mới ngay lập tức nếu phát sinh lỗi từ nhà sản xuất.</li>
    <li><strong>Giao hàng & Lắp đặt siêu tốc:</strong> Hỗ trợ giao hỏa tốc trong 1h - 2h tại nội thành Hà Nội.</li>
    <li><strong>Trả góp 0% lãi suất:</strong> Thủ tục xét duyệt online nhanh chóng trong 5 phút.</li>
</ul>
</div>"""

def publish_all():
    print(f"🚀 Bắt đầu xuất bản {len(TV_PRODUCTS)} sản phẩm Tivi Xiaomi vào WooCommerce qua Omni Bridge Connector...")
    results = []
    
    for idx, p in enumerate(TV_PRODUCTS, 1):
        payload = {
            "post_title": p["title"],
            "post_type": "product",
            "post_status": "publish",
            "post_slug": p["slug"],
            "post_content": generate_product_html(p),
            "post_excerpt": f"Mua {p['title']} giá rẻ nhất thị trường. Công nghệ {p['tech']} đỉnh cao, bảo hành 24 tháng chính hãng, miễn phí giao lắp tận nhà Hà Nội.",
            "categories": ["Tivi Xiaomi"],
            "product_data": {
                "sku": p["sku"],
                "regular_price": str(p["regular_price"]),
                "sale_price": str(p["sale_price"]),
                "stock_status": "instock",
                "technical_specs": p["specs"]
            },
            "seo_meta": {
                "seo_title": f"{p['title']} Giá Tốt Nhất 2026 | TongKhoMi",
                "seo_description": f"Mua {p['title']} chính hãng giá chỉ {p['sale_price']:,}đ. Bảo hành 2 năm tại nhà, trả góp 0%, miễn phí giao hàng lắp đặt Hà Nội.",
                "focus_keyword": f"{p['sku'].lower()}"
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
            with urllib.request.urlopen(req, timeout=15) as resp:
                res_data = json.loads(resp.read().decode("utf-8"))
                post_id = res_data.get("post_id")
                print(f"[{idx}/{len(TV_PRODUCTS)}] ✅ Thành công: #{post_id} - {p['sku']} ({p['inch']} - {p['tech']}) -> {p['sale_price']:,} đ")
                results.append({"status": "success", "id": post_id, "sku": p["sku"], "title": p["title"]})
        except Exception as e:
            print(f"[{idx}/{len(TV_PRODUCTS)}] ❌ Thất bại: {p['sku']} -> {e}")
            results.append({"status": "failed", "sku": p["sku"], "error": str(e)})

    print(f"\n🎉 Hoàn tất xuất bản {len([r for r in results if r.get('status') == 'success'])}/{len(TV_PRODUCTS)} sản phẩm vào danh mục 'Tivi Xiaomi' (ID 17)!")

if __name__ == "__main__":
    publish_all()
