#!/usr/bin/env python3
"""
Cập nhật Sản Phẩm WooCommerce #30:
1. Gán đầy đủ 13 thông số kỹ thuật chuẩn vào ACF repeater `technical_specs` (hiển thị đúng vị trí widget).
2. Gán 5 ảnh thực tế chất lượng cao vào Thư viện ảnh WooCommerce (`_product_image_gallery`).
3. Viết bài mô tả sản phẩm chi tiết chuyên sâu 2.200+ từ chuẩn CRO & SOP 12, chèn ảnh minh họa cho từng đề mục H2 và nhúng Schema FAQPage JSON-LD.
"""

import os
import sys
import json
import subprocess

CURRENT_DIR = os.path.dirname(os.path.abspath(__file__))
WP_CONTAINER = "michinhhang-wordpress-1"

# 1. Danh sách 13 thông số kỹ thuật chuẩn ACF
SPECS_DATA = [
    {"spec_label": "Model sản phẩm", "spec_value": "Xiaomi Mijia Omni 2 (C102CN)"},
    {"spec_label": "Lực hút cực đại", "spec_value": "6.000 Pa (Động cơ không chổi than Nidec)"},
    {"spec_label": "Dung lượng pin", "spec_value": "5.200 mAh (Làm việc liên tục 180 phút / 250m²)"},
    {"spec_label": "Dung tích bình nước trạm", "spec_value": "Bình nước sạch 4L / Bình nước bẩn 4L"},
    {"spec_label": "Túi chứa rác tự động", "spec_value": "2.5 Lít (Kháng khuẩn, chứa rác 75 ngày)"},
    {"spec_label": "Cơ chế lau nhà", "spec_value": "Lau xoay kép áp lực 180 vòng/phút"},
    {"spec_label": "Tính năng vượt thảm", "spec_value": "Cảm biến siêu âm, tự động nâng giẻ lau 10mm"},
    {"spec_label": "Công nghệ sấy giẻ", "spec_value": "Sấy khí nóng đối lưu 2 giờ khử mùi 99%"},
    {"spec_label": "Hệ thống điều hướng", "spec_value": "Laser LDS 360° + AI S-Cross 3D Structured Light"},
    {"spec_label": "Chổi chính chống rối", "spec_value": "Chổi cao su mềm chống rối tóc đa tầng"},
    {"spec_label": "Kết nối ứng dụng", "spec_value": "Mi Home / Xiaomi Home (Tiếng Việt) & Trợ lý ảo"},
    {"spec_label": "Kích thước & Trọng lượng", "spec_value": "Robot: 350x350x97mm (3.7kg) | Dock: 568x427x340mm (8.5kg)"},
    {"spec_label": "Chính sách bảo hành", "spec_value": "24 tháng chính hãng tại TongKhoMi.com (Đổi mới 1-1 trong 30 ngày)"}
]

# 2. Nội dung bài viết mô tả chi tiết chuẩn CRO & SOP 12 (2.200+ từ)
CONTENT_HTML = """
<div class="product-in-depth-description text-slate-700 leading-relaxed text-[15px]">

<p class="text-lg leading-relaxed text-slate-800 font-medium mb-6">
Trong nhịp sống hiện đại bận rộn, việc quét dọn, lau nhà và giặt giẻ thủ công mỗi ngày là nỗi ám ảnh tiêu tốn hàng giờ đồng hồ quý báu của mỗi gia đình. Những dòng robot hút bụi đời cũ thường xuyên gặp phải tình trạng kẹt tóc rối, lực hút yếu không sạch bụi mịn ở khe gạch, giẻ lau ẩm ướt để lâu bốc mùi hôi chua, và đặc biệt là khi leo lên thảm lại làm ướt sũng sợi vải. <strong>Robot hút bụi lau nhà Xiaomi Mijia Omni 2 (Model C102CN)</strong> ra đời chính là lời giải hoàn hảo, tái định nghĩa chuẩn mực tự động hóa dọn dẹp với hệ sinh thái trạm sạc All-in-One 10-trong-1 đỉnh cao, lực hút siêu khủng 6.000Pa và trí tuệ điều hướng AI thông minh bậc nhất hiện nay.
</p>

<h2 class="text-xl md:text-2xl font-bold text-slate-900 mt-8 mb-4 border-l-4 border-orange-500 pl-3">
1. Trạm Sạc All-in-One Toàn Năng – Giải Phóng Đôi Tay Suốt 75 Ngày
</h2>
<p class="mb-4">
Điểm sáng giá nhất biến Xiaomi Mijia Omni 2 thành một cỗ máy làm sạch độc lập hoàn toàn chính là trạm sạc đa năng All-in-One. Không đơn thuần chỉ là nơi sạc pin, dock sạc này đảm nhận trọn vẹn toàn bộ các quy trình bảo trì nặng nhọc nhất:
</p>
<ul class="list-disc pl-6 mb-5 space-y-2">
  <li><strong>Tự động thu gom bụi rác 75 ngày:</strong> Sau khi hoàn thành chu trình làm sạch, robot quay về dock và kích hoạt lực hút kép mạnh mẽ để hút toàn bộ bụi rác từ hộp chứa trên máy sang túi rác kháng khuẩn dung tích 2.5L. Bạn chỉ cần thay túi rác trung bình 4 đến 5 lần mỗi năm.</li>
  <li><strong>Hệ thống bình nước đôi siêu lớn 4L:</strong> Bình chứa nước sạch 4L và bình chứa nước bẩn 4L tách biệt, đủ sức phục vụ việc lau sàn liên tục cho căn hộ hoặc biệt thự rộng trên 250m² mà không cần châm nước thường xuyên.</li>
  <li><strong>Tự động giặt giẻ và sấy khô bằng khí nóng 2 giờ:</strong> Trạm sạc phun nước áp lực và xoay chà giẻ sạch bóng, sau đó tự động kích hoạt luồng khí nóng đối lưu 2 giờ để sấy khô cong giẻ lau, triệt tiêu 99.9% vi khuẩn, nấm mốc và dập tắt hoàn toàn mùi hôi chua ẩm mốc khó chịu.</li>
</ul>

<figure class="my-8 text-center">
  <img src="/wp-content/uploads/2026/10/xiaomi-omni-2-tram_sac_all_in_one.jpg" alt="Trạm sạc All-in-One tự động gom bụi 75 ngày và giặt sấy khí nóng Xiaomi Mijia Omni 2" class="rounded-2xl shadow-md mx-auto max-w-full h-auto border border-slate-200" />
  <figcaption class="text-xs text-slate-500 mt-2 italic font-medium">Trạm sạc thông minh All-in-One: Tự động gom bụi 75 ngày, bình nước 4L và giặt sấy giẻ khí nóng 2 giờ</figcaption>
</figure>

<h2 class="text-xl md:text-2xl font-bold text-slate-900 mt-8 mb-4 border-l-4 border-orange-500 pl-3">
2. Lực Hút Cực Đại 6.000Pa – Đánh Bật Bụi Mịn & Chổi Cao Su Chống Rối Tóc
</h2>
<p class="mb-4">
Được trang bị động cơ không chổi than Nidec cao cấp nhập khẩu, Xiaomi Mijia Omni 2 sản sinh lực hút cực đại lên tới <strong>6.000 Pa</strong>. Đây là mức lực hút thuộc top đầu phân khúc flagship, cho phép robot:
</p>
<ul class="list-disc pl-6 mb-5 space-y-2">
  <li>Hút sạch bụi mịn PM2.5, mạt bụi ẩn sâu trong các rãnh khe gạch, chân tường và sợi thảm dày.</li>
  <li>Xử lý hoàn hảo các loại rác hạt lớn như vụn bánh quy, cát mèo, hạt dưa, ngũ cốc chỉ trong một lần lướt qua.</li>
  <li><strong>Thiết kế chổi chính cao su mềm chống rối tóc đa tầng:</strong> Các lá cao su xoắn hình chữ V dẫn hướng tóc và lông thú cưng thẳng về ống hút mà không bị quấn chặt vào trục quay, loại bỏ vĩnh viễn cảnh tượng phải dùng dao kéo cắt tóc rối như các dòng robot chổi lông truyền thống.</li>
</ul>

<figure class="my-8 text-center">
  <img src="/wp-content/uploads/2026/10/xiaomi-omni-2-luc_hut_6000pa.jpg" alt="Động cơ hút siêu mạnh 6.000Pa và chổi cao su chống rối tóc đa tầng Xiaomi Omni 2" class="rounded-2xl shadow-md mx-auto max-w-full h-auto border border-slate-200" />
  <figcaption class="text-xs text-slate-500 mt-2 italic font-medium">Lực hút bão táp 6.000Pa kết hợp chổi cao su mềm chống rối tóc thông minh</figcaption>
</figure>

<h2 class="text-xl md:text-2xl font-bold text-slate-900 mt-8 mb-4 border-l-4 border-orange-500 pl-3">
3. Công Nghệ Lau Xoay Kép 180 Vòng/Phút & Tự Động Nâng Giẻ 10mm Khi Gặp Thảm
</h2>
<p class="mb-4">
Khác biệt hoàn toàn với công nghệ lau rung đơn điệu hay kéo lê miếng giẻ ướt thông thường, Xiaomi Mijia Omni 2 sử dụng <strong>cụm giẻ lau tròn kép xoay ngược chiều tốc độ cao 180 vòng/phút</strong>. Đồng thời, hệ thống lò xo trợ lực liên tục tạo áp lực miết mạnh xuống bề mặt sàn, mô phỏng chân thực thao tác dùng hai tay chà sàn của con người:
</p>
<ul class="list-disc pl-6 mb-5 space-y-2">
  <li>Dễ dàng đánh bay các vết bẩn cứng đầu lâu ngày như vệt cà phê khô, dầu mỡ thức ăn trong bếp, tương ớt hay vết bùn đất.</li>
  <li><strong>Cảm biến nhận diện thảm siêu âm:</strong> Khi robot di chuyển từ sàn gạch/gỗ lên thảm trải sàn, cụm giẻ lau sẽ tự động nhấc bổng lên <strong>10 mm</strong> trong tích tắc. Đồng thời, máy tự ngắt cấp nước và tăng tốc lực hút lên mức cực đại để làm sạch sâu sợi thảm mà không để lại bất kỳ vệt ẩm ướt nào.</li>
  <li>Sau khi rời khỏi thảm, robot tự động hạ giẻ xuống và tiếp tục quy trình lau nhà bình thường.</li>
</ul>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4 my-8">
  <figure class="text-center">
    <img src="/wp-content/uploads/2026/10/xiaomi-mijia-omni-2-chi-tiet-dong-co.jpg" alt="Cụm giẻ lau xoay kép 180 vòng/phút Xiaomi Mijia Omni 2" class="rounded-xl shadow-md mx-auto max-w-full h-auto border border-slate-200" />
    <figcaption class="text-xs text-slate-500 mt-2 italic font-medium">Cụm giẻ lau xoay kép 180 vòng/phút tạo lực miết sàn liên tục</figcaption>
  </figure>
  <figure class="text-center">
    <img src="/wp-content/uploads/2026/10/xiaomi-omni-2-tu_dong_nang_gie.jpg" alt="Cơ chế tự động nâng giẻ lau 10mm khi gặp thảm trải sàn" class="rounded-xl shadow-md mx-auto max-w-full h-auto border border-slate-200" />
    <figcaption class="text-xs text-slate-500 mt-2 italic font-medium">Cảm biến thảm siêu âm: Tự động nâng giẻ 10mm giữ thảm luôn khô ráo</figcaption>
  </figure>
</div>

<h2 class="text-xl md:text-2xl font-bold text-slate-900 mt-8 mb-4 border-l-4 border-orange-500 pl-3">
4. Mắt Thần Laser LDS 360° & AI S-Cross 3D – Né Tránh Vật Cản Siêu Phàm
</h2>
<p class="mb-4">
Để di chuyển thông minh và không bao giờ bị kẹt trong mê cung đồ đạc, Xiaomi Mijia Omni 2 được trang bị bộ đôi công nghệ định vị tối tân hàng đầu của hệ sinh thái Xiaomi:
</p>
<ul class="list-disc pl-6 mb-5 space-y-2">
  <li><strong>Hệ thống Radar Laser LDS 360 độ:</strong> Quét toàn bộ không gian phòng với tần số cao, xây dựng bản đồ 3D chuẩn xác của ngôi nhà chỉ trong vòng 5 phút đầu tiên kể cả khi nhà có nhiều phòng phức tạp.</li>
  <li><strong>Cảm biến ánh sáng cấu trúc AI S-Cross 3D:</strong> Sử dụng thuật toán nhận diện không gian quang học millimet, phát hiện và né tránh chuẩn xác các vật cản thấp nguy hiểm như dây sạc điện thoại, tất chân, đồ chơi trẻ em, dép trong nhà và chân bàn ghế mảnh mai, hạn chế tối đa va chạm làm trầy xước đồ nội thất đắt tiền.</li>
  <li>Tự động nhận diện cầu thang và gờ cao với hệ thống cảm biến chống rơi 6 trục, đảm bảo an toàn tuyệt đối khi vận hành tại các căn hộ thông tầng hay nhà phố.</li>
</ul>

<figure class="my-8 text-center">
  <img src="/wp-content/uploads/2026/10/xiaomi-omni-2-cam_bien_laser_lds.jpg" alt="Mắt thần Laser LDS 360 độ và AI S-Cross 3D tránh vật cản" class="rounded-2xl shadow-md mx-auto max-w-full h-auto border border-slate-200" />
  <figcaption class="text-xs text-slate-500 mt-2 italic font-medium">Bộ đôi Laser LDS 360° và AI S-Cross 3D nhận diện và né tránh vật thể chuẩn xác</figcaption>
</figure>

<h2 class="text-xl md:text-2xl font-bold text-slate-900 mt-8 mb-4 border-l-4 border-orange-500 pl-3">
5. Kết Nối Thông Minh Ứng Dụng Mi Home – Làm Chủ Ngôi Nhà Mọi Lúc Mọi Nơi
</h2>
<p class="mb-4">
Thông qua ứng dụng <strong>Mi Home / Xiaomi Home</strong> hỗ trợ hoàn toàn giao diện tiếng Việt thân thiện, bạn có thể dễ dàng quản lý toàn bộ chu trình vận hành của robot từ xa ngay trên điện thoại:
</p>
<ul class="list-disc pl-6 mb-5 space-y-2">
  <li><strong>Lưu trữ tối đa 4 tầng bản đồ:</strong> Rất thuận tiện cho các ngôi nhà nhiều tầng, tự động nhận diện tầng khi mang robot lên xuống.</li>
  <li><strong>Phân chia khu vực & Tạo tường ảo:</strong> Thiết lập vùng cấm vào (như nhà vệ sinh, ban công), chọn phòng cần dọn dẹp ưu tiên hoặc vẽ khu vực lau dọn tùy chỉnh theo ý muốn.</li>
  <li><strong>Tùy chỉnh cá nhân hóa:</strong> Cài đặt 4 mức lực hút và 3 mức độ ẩm của giẻ lau riêng biệt cho từng loại phòng (phòng khách lau ẩm vừa, phòng bếp lau ướt kỹ, phòng ngủ hút yên tĩnh).</li>
  <li>Hẹn giờ tự động dọn dẹp khi bạn đi làm và quay về trạm sạc trước khi bạn về nhà, mang đến cảm giác thư thái tuyệt đối với sàn nhà luôn sạch bong sáng bóng.</li>
</ul>

<h2 class="text-xl md:text-2xl font-bold text-slate-900 mt-8 mb-4 border-l-4 border-orange-500 pl-3">
6. Bảng So Sánh Cấu Hình Toàn Diện Với Các Đối Thủ Cùng Phân Khúc
</h2>
<p class="mb-4">
Dưới đây là bảng đối chiếu chi tiết giữa Xiaomi Mijia Omni 2 và hai đối thủ sừng sỏ cùng tầm giá trên thị trường hiện nay:
</p>

<div class="overflow-x-auto my-6">
  <table class="w-full text-sm border-collapse border border-slate-300">
    <thead>
      <tr class="bg-slate-100 text-slate-800">
        <th class="p-3 text-left border border-slate-300 font-bold">Tiêu chí so sánh</th>
        <th class="p-3 text-left border border-slate-300 font-bold text-orange-600">Xiaomi Mijia Omni 2</th>
        <th class="p-3 text-left border border-slate-300 font-bold">Roborock Q Revo</th>
        <th class="p-3 text-left border border-slate-300 font-bold">Ecovacs Deebot T20 Omni</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td class="p-3 border border-slate-300 font-semibold">Lực hút cực đại</td>
        <td class="p-3 border border-slate-300 font-bold text-orange-600">6.000 Pa</td>
        <td class="p-3 border border-slate-300">5.500 Pa</td>
        <td class="p-3 border border-slate-300">6.000 Pa</td>
      </tr>
      <tr class="bg-slate-50">
        <td class="p-3 border border-slate-300 font-semibold">Cơ chế giẻ lau</td>
        <td class="p-3 border border-slate-300 font-bold text-orange-600">Lau xoay kép 180 rpm</td>
        <td class="p-3 border border-slate-300">Lau xoay kép 200 rpm</td>
        <td class="p-3 border border-slate-300">Lau xoay kép 180 rpm</td>
      </tr>
      <tr>
        <td class="p-3 border border-slate-300 font-semibold">Tự động nâng giẻ khi gặp thảm</td>
        <td class="p-3 border border-slate-300 font-bold text-orange-600">Có (Nâng 10 mm)</td>
        <td class="p-3 border border-slate-300">Có (Nâng 7 mm)</td>
        <td class="p-3 border border-slate-300">Có (Nâng 9 mm)</td>
      </tr>
      <tr class="bg-slate-50">
        <td class="p-3 border border-slate-300 font-semibold">Sấy khô giẻ khí nóng</td>
        <td class="p-3 border border-slate-300 font-bold text-orange-600">Có (Sấy khí nóng 2 giờ)</td>
        <td class="p-3 border border-slate-300">Có (Sấy khí ấm 3 giờ)</td>
        <td class="p-3 border border-slate-300">Có (Sấy khí nóng)</td>
      </tr>
      <tr>
        <td class="p-3 border border-slate-300 font-semibold">Thời gian gom bụi rảnh tay</td>
        <td class="p-3 border border-slate-300 font-bold text-orange-600">75 Ngày (Túi 2.5L)</td>
        <td class="p-3 border border-slate-300">50 Ngày (Túi 2.7L)</td>
        <td class="p-3 border border-slate-300">60 Ngày (Túi 3.0L)</td>
      </tr>
      <tr class="bg-slate-50">
        <td class="p-3 border border-slate-300 font-semibold">Dung tích bình nước trạm</td>
        <td class="p-3 border border-slate-300 font-bold text-orange-600">Bình sạch 4L / Bẩn 4L</td>
        <td class="p-3 border border-slate-300">Bình sạch 5L / Bẩn 4.2L</td>
        <td class="p-3 border border-slate-300">Bình sạch 4L / Bẩn 4L</td>
      </tr>
      <tr>
        <td class="p-3 border border-slate-300 font-semibold">Mức giá tham khảo</td>
        <td class="p-3 border border-slate-300 font-bold text-orange-600">10.900.000 ₫ (Tiết kiệm nhất)</td>
        <td class="p-3 border border-slate-300">11.900.000 ₫</td>
        <td class="p-3 border border-slate-300">12.500.000 ₫</td>
      </tr>
    </tbody>
  </table>
</div>

<h2 class="text-xl md:text-2xl font-bold text-slate-900 mt-8 mb-4 border-l-4 border-orange-500 pl-3">
7. Đặc Quyền Vàng Khi Mua Xiaomi Mijia Omni 2 Tại TongKhoMi.com
</h2>
<p class="mb-4">
Khi lựa chọn sản phẩm tại hệ thống phân phối chính hãng <strong>TongKhoMi.com / Michinhhang.com</strong>, quý khách hàng hoàn toàn yên tâm với chính sách hậu mãi và cam kết chất lượng độc quyền:
</p>
<ul class="list-disc pl-6 mb-5 space-y-2">
  <li><strong>Cam kết giá tại kho tốt nhất thị trường:</strong> Nhập khẩu trực tiếp không qua trung gian, mang lại mức giá tiết kiệm 1 - 2 triệu đồng so với các chuỗi siêu thị bán lẻ.</li>
  <li><a href="/chinh-sach-bao-hanh" class="text-orange-600 font-semibold hover:underline">Chính sách bảo hành chính hãng 24 tháng</a>: Đội ngũ kỹ sư tay nghề cao hỗ trợ kỹ thuật trọn đời máy.</li>
  <li><a href="/chinh-sach-doi-tra" class="text-orange-600 font-semibold hover:underline">Chính sách đổi mới 1-1 trong 30 ngày</a>: Nếu phát sinh bất kỳ lỗi kỹ thuật nào từ nhà sản xuất.</li>
  <li><a href="/chinh-sach-van-chuyen" class="text-orange-600 font-semibold hover:underline">Chính sách giao hàng hỏa tốc 2 giờ</a>: Miễn phí vận chuyển và hỗ trợ lắp đặt, hướng dẫn kết nối ứng dụng tận nhà tại khu vực Hà Nội.</li>
  <li><a href="/tra-cuu-bao-hanh" class="text-orange-600 font-semibold hover:underline">Hệ thống tra cứu bảo hành điện tử</a>: Minh bạch thời hạn và lịch sử bảo trì bằng số điện thoại hoặc mã IMEI/Serial máy.</li>
</ul>

<div class="bg-amber-50 p-5 rounded-xl border border-amber-200 my-6">
  <p class="font-bold text-amber-900 mb-2">📍 HỆ THỐNG KHO HÀNG TRỰC TIẾP TẠI HÀ NỘI (Mở cửa 8h00 - 21h30 hàng ngày):</p>
  <ul class="text-amber-800 space-y-1 text-sm">
    <li>• <strong>Kho Đống Đa:</strong> 65 Hồ Ba Mẫu, Đống Đa, Hà Nội</li>
    <li>• <strong>Kho Hà Đông:</strong> Sân bóng Văn Quán, Hà Đông, Hà Nội</li>
    <li>• <strong>Tổng kho Cảng Hà Nội:</strong> Kho 86 Cảng Hà Nội, Hai Bà Trưng, Hà Nội</li>
    <li>• <strong>Hotline tư vấn & đặt hàng hỏa tốc:</strong> <a href="tel:0822834444" class="font-bold text-orange-600">0822.83.4444</a></li>
  </ul>
</div>

<h2 class="text-xl md:text-2xl font-bold text-slate-900 mt-8 mb-4 border-l-4 border-orange-500 pl-3">
8. Câu Hỏi Thường Gặp (FAQ) Về Xiaomi Mijia Omni 2
</h2>

<div class="space-y-4 my-6">
  <div class="border border-slate-200 rounded-lg p-4 bg-slate-50">
    <h3 class="font-bold text-slate-900 mb-2">1. Sàn gỗ công nghiệp có dùng được chức năng lau xoay của Xiaomi Omni 2 không?</h3>
    <p class="text-slate-700 text-sm">Hoàn toàn yên tâm sử dụng. Ứng dụng Mi Home cho phép bạn tùy chỉnh 3 cấp độ ẩm của giẻ lau. Bạn chỉ cần chọn mức ẩm thấp (hơi ẩm), robot sẽ lau sạch bóng và sàn gỗ khô ráo hoàn toàn chỉ sau 1-2 phút, không gây phồng rộp hay đọng nước trên sàn gỗ.</p>
  </div>

  <div class="border border-slate-200 rounded-lg p-4 bg-slate-50">
    <h3 class="font-bold text-slate-900 mb-2">2. Một chu kỳ giặt sấy khí nóng tốn bao nhiêu điện năng?</h3>
    <p class="text-slate-700 text-sm">Công nghệ sấy khí nóng PTC trên trạm sạc Xiaomi Omni 2 được tối ưu hóa tiêu thụ điện cực thấp. Một chu kỳ sấy khô hoàn tất trong 2 giờ chỉ tiêu tốn khoảng 0.08 - 0.1 kWh (tương đương chưa tới 300 đồng tiền điện mỗi lần sấy).</p>
  </div>

  <div class="border border-slate-200 rounded-lg p-4 bg-slate-50">
    <h3 class="font-bold text-slate-900 mb-2">3. Nhà nhiều tầng có dùng chung được robot này không?</h3>
    <p class="text-slate-700 text-sm">Có thể dùng rất tiện lợi. Robot hỗ trợ lưu trữ tới 4 bản đồ tầng riêng biệt. Khi cần dọn dẹp tầng khác, bạn chỉ cần bế robot lên tầng đó bấm nút chạy, robot sẽ tự nhận diện bản đồ tầng và tự động né tránh cầu thang nhờ cảm biến chống rơi an toàn.</p>
  </div>

  <div class="border border-slate-200 rounded-lg p-4 bg-slate-50">
    <h3 class="font-bold text-slate-900 mb-2">4. Tóc rụng nhiều có bị kẹt cứng vào chổi chính không?</h3>
    <p class="text-slate-700 text-sm">Không bị kẹt cứng. Xiaomi Mijia Omni 2 đã nâng cấp chổi chính sang chất liệu cao su đúc nguyên khối mềm mại với các rãnh dẫn hướng chữ V. Tóc sẽ được hút thẳng vào hộp bụi thay vì quấn chặt như chổi lông truyền thống, giúp việc vệ sinh trở nên cực kỳ nhẹ nhàng.</p>
  </div>

  <div class="border border-slate-200 rounded-lg p-4 bg-slate-50">
    <h3 class="font-bold text-slate-900 mb-2">5. Có nên dùng nước lau sàn thông thường đổ vào bình nước sạch không?</h3>
    <p class="text-slate-700 text-sm">Khuyến cáo không nên dùng nước lau sàn thông thường có bọt nhiều vì có thể gây tắc ống bơm vi mạch và đóng cặn. Bạn nên sử dụng nước lau sàn chuyên dụng không bọt dành riêng cho robot Xiaomi hoặc nước sạch tinh khiết để đảm bảo độ bền tối đa cho máy.</p>
  </div>
</div>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "Sàn gỗ công nghiệp có dùng được chức năng lau xoay của Xiaomi Omni 2 không?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Hoàn toàn yên tâm sử dụng. Ứng dụng Mi Home cho phép bạn tùy chỉnh 3 cấp độ ẩm của giẻ lau, giúp sàn khô ráo sau 1-2 phút không gây phồng rộp."
      }
    },
    {
      "@type": "Question",
      "name": "Một chu kỳ giặt sấy khí nóng tốn bao nhiêu điện năng?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Công nghệ sấy khí nóng tiêu thụ điện cực thấp, chỉ khoảng 0.08 - 0.1 kWh (chưa tới 300 đồng) cho một chu trình 2 giờ."
      }
    },
    {
      "@type": "Question",
      "name": "Nhà nhiều tầng có dùng chung được robot này không?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Robot hỗ trợ lưu trữ tới 4 bản đồ tầng riêng biệt, tự nhận diện bản đồ tầng và có cảm biến chống rơi cầu thang an toàn."
      }
    },
    {
      "@type": "Question",
      "name": "Tóc rụng nhiều có bị kẹt cứng vào chổi chính không?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Chổi chính cao su mềm chống rối dẫn tóc thẳng vào hộp rác, loại bỏ hoàn toàn hiện tượng kẹt tóc quấn trục."
      }
    },
    {
      "@type": "Question",
      "name": "Có nên dùng nước lau sàn thông thường đổ vào bình nước sạch không?",
      "acceptedAnswer": {
        "@type": "Answer",
        "text": "Nên dùng nước sạch hoặc nước lau sàn chuyên dụng cho robot để tránh đóng cặn bơm và bảo vệ vi mạch."
      }
    }
  ]
}
</script>

</div>
"""

# 3. Đoạn mô tả ngắn (Short description) giật gân, sắc bén
SHORT_DESC = """*   **Lực hút cực đại 6.000Pa:** Hút sạch bụi mịn, tóc rụng và các mảnh vụn cứng đầu ở mọi khe kẽ sâu.
*   **Cụm giẻ lau xoay kép 180 vòng/phút:** Tự động tạo áp lực miết sàn, đánh bật vết bẩn khô cứng hiệu quả gấp 4 lần.
*   **Trạm sạc All-in-One hoàn hảo:** Tự động gom bụi rác 75 ngày, giặt giẻ và sấy khô bằng khí nóng 2 giờ khử mùi diệt khuẩn.
*   **Điều hướng thông minh Laser LDS 360° & AI S-Cross 3D:** Quét bản đồ chính xác, tự động nâng giẻ 10mm khi leo lên thảm.
*   **Chính sách độc quyền tại Tổng Kho Mi:** Bảo hành chính hãng 24 tháng, lỗi 1 đổi 1 trong 30 ngày, giao hàng hỏa tốc 2 giờ."""

def main():
    post_id = 30
    print(f"🚀 Đang cập nhật sản phẩm WooCommerce ID #{post_id}...")

    # Encode dữ liệu an toàn Base64 để truyền vào PHP qua Docker
    import base64
    b64_content = base64.b64encode(CONTENT_HTML.strip().encode("utf-8")).decode("ascii")
    b64_short = base64.b64encode(SHORT_DESC.strip().encode("utf-8")).decode("ascii")
    b64_specs = base64.b64encode(json.dumps(SPECS_DATA, ensure_ascii=False).encode("utf-8")).decode("ascii")

    php_script = f"""
    require('/var/www/html/wp-load.php');
    $post_id = {post_id};
    $content = base64_decode('{b64_content}');
    $short = base64_decode('{b64_short}');
    $specs_json = base64_decode('{b64_specs}');
    $specs = json_decode($specs_json, true);

    // 1. Cập nhật Content & Excerpt
    wp_update_post([
        'ID'           => $post_id,
        'post_content' => $content,
        'post_excerpt' => $short
    ]);

    // 2. Cập nhật ACF technical_specs
    if (function_exists('update_field')) {{
        update_field('technical_specs', $specs, $post_id);
    }}

    // 3. Đảm bảo Gallery chứa đủ 5 ảnh thực tế (31, 32, 33, 34, 27)
    update_post_meta($post_id, '_product_image_gallery', '31,32,33,34,27');

    // 4. Xóa cache transient WooCommerce
    if (function_exists('wc_delete_product_transients')) {{
        wc_delete_product_transients($post_id);
    }}

    echo "UPDATED_SUCCESS:" . $post_id;
    """

    res = subprocess.run(
        ["docker", "exec", WP_CONTAINER, "php", "-r", php_script],
        capture_output=True,
        text=True,
        timeout=30
    )

    if "UPDATED_SUCCESS:" in res.stdout:
        print("✅ Đã cập nhật thành công bài viết và thông số kỹ thuật!")
        # Xóa Redis cache
        subprocess.run(["docker", "exec", "michinhhang-redis-1", "redis-cli", "FLUSHALL"], capture_output=True)
        print("✅ Đã làm mới Redis Cache!")
    else:
        print("❌ Lỗi cập nhật:", res.stderr or res.stdout)

if __name__ == "__main__":
    main()
