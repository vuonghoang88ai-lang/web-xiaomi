#!/usr/bin/env python3
"""
Nâng cấp nội dung chuyên sâu chuẩn SOP 12 và bổ sung hình ảnh thực tế cho:
Tivi Xiaomi MAX 98 inch Mini LED 2026 Đẳng Cấp Biệt Thự (L98MC-STWN)
Post ID: 79
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

RICH_HTML_CONTENT = """<div class="product-in-depth-description text-slate-800 leading-relaxed text-[15px] space-y-6">

<p class="text-lg md:text-xl leading-relaxed text-slate-900 font-medium">
<strong>Tivi Xiaomi S Mini LED 98 inch 2026 (Mã model: L98MC-STWN)</strong> là tuyệt tác nghe nhìn cao cấp nhất của Xiaomi trong năm 2026, được thiết kế chuyên biệt cho các không gian sống đẳng cấp như <strong>biệt thự, căn hộ penthouse, phòng khách master diện tích lớn và phòng chiếu phim gia đình hi-end</strong>. Với kích thước màn hình khổng lồ lên tới <strong>98 inch (đường chéo ~2,5 mét)</strong>, kết hợp cùng tấm nền <strong>QD-Mini LED tiên tiến với 880 vùng làm mờ cục bộ (Local Dimming)</strong> và tần số quét thực <strong>144Hz</strong>, siêu phẩm này định nghĩa lại hoàn toàn chuẩn mực giải trí nghe nhìn thượng lưu ngay tại tư gia.
</p>

<!-- 1. THIẾT KẾ & KHÔNG GIAN BIỆT THỰ -->
<h2 class="text-xl md:text-2xl font-bold text-slate-900 mt-10 mb-4 border-l-4 border-orange-500 pl-3">
1. Tuyệt Tác Thiết Kế Khung Kim Loại Hàng Không & Kích Thước 98 Inch Khổng Lồ
</h2>

<p>
Điểm nhấn đầu tiên khiến bất kỳ ai cũng phải choáng ngợp trước <strong>Xiaomi S Mini LED 98 inch (L98MC-STWN)</strong> chính là kích thước màn hình tương đương một rạp chiếu phim mini. Với chiều ngang xấp xỉ 2,2 mét và chiều cao hơn 1,25 mét, chiếc tivi này không chỉ là một thiết bị giải trí mà còn là một tác phẩm nội thất khẳng định vị thế và gu thẩm mỹ đỉnh cao của gia chủ.
</p>

<div class="my-6 text-center">
    <img src="/wp-content/uploads/2026/10/tivi-xiaomi-s-mini-led-98-inch-hero.jpg" 
         alt="Tivi Xiaomi S Mini LED 98 inch L98MC-STWN lắp đặt trong không gian penthouse sang trọng" 
         class="rounded-xl shadow-lg mx-auto w-full max-w-4xl object-cover hover:scale-[1.01] transition-transform duration-300" 
         loading="lazy" />
    <p class="text-sm text-slate-500 italic mt-2">Tivi Xiaomi S Mini LED 98 inch (L98MC-STWN) kiến tạo không gian rạp chiếu phim chuẩn điện ảnh trong biệt thự và penthouse.</p>
</div>

<ul class="list-disc pl-6 space-y-2">
    <li><strong>Khung viền kim loại Unibody nguyên khối:</strong> Sử dụng hợp kim nhôm hàng không phay CNC siêu tinh xảo, tỷ lệ màn hình trên thân máy đạt tới <strong>97.8%</strong>, mang lại hiệu ứng hình ảnh tràn viền vô cực, xóa nhòa ranh giới giữa nội dung hiển thị và không gian thực tế.</li>
    <li><strong>Chân đế kim loại kép vững chãi:</strong> Chân đế chữ V kim loại kép được đúc áp lực nguyên khối, đảm bảo độ vững chắc tuyệt đối cho màn hình cỡ đại 98 inch trên kệ tivi. Ngoài ra, mặt lưng hỗ trợ chuẩn ngàm VESA chuyên dụng 800 x 400mm, sẵn sàng cho các giải pháp giá treo tường chịu lực kiên cố.</li>
    <li><strong>Giải pháp giấu dây thông minh:</strong> Khay quản lý cáp âm khéo léo phía sau giúp không gian phòng khách luôn gọn gàng, tinh tế và sang trọng.</li>
</ul>

<!-- 2. CÔNG NGHỆ HIỂN THỊ MINI LED -->
<h2 class="text-xl md:text-2xl font-bold text-slate-900 mt-10 mb-4 border-l-4 border-orange-500 pl-3">
2. Đột Phá Công Nghệ QD-Mini LED 2026: 880 Vùng Làm Mờ Cục Bộ Độc Lập
</h2>

<p>
Khác biệt hoàn toàn với các dòng tivi LED hoặc QLED viền truyền thống thường gặp tình trạng hở sáng (blooming) và màu đen ngả xám, <strong>Xiaomi Smart Display S98 Mini LED</strong> áp dụng kiến trúc đèn nền <strong>QD-Mini LED thế hệ 2026</strong> với ma trận gồm hàng nghìn bóng đèn Micro LED siêu nhỏ được chia thành <strong>880 phân vùng kiểm soát ánh sáng độc lập (Local Dimming Zones)</strong>.
</p>

<div class="my-6 text-center">
    <img src="/wp-content/uploads/2026/10/cong-nghe-qd-mini-led-xiaomi-98-inch.jpg" 
         alt="Sơ đồ cấu trúc công nghệ màn hình QD-Mini LED trên Tivi Xiaomi 98 inch 2026" 
         class="rounded-xl shadow-lg mx-auto w-full max-w-4xl object-cover hover:scale-[1.01] transition-transform duration-300" 
         loading="lazy" />
    <p class="text-sm text-slate-500 italic mt-2">Cấu trúc đa lớp quang học QD-Mini LED giúp phân bổ độ sáng chính xác tới từng điểm ảnh, loại bỏ hiện tượng quầng sáng.</p>
</div>

<ul class="list-disc pl-6 space-y-2">
    <li><strong>Độ sáng cực đại 1.200 Nits:</strong> Cho phép màn hình hiển thị rực rỡ, rõ nét ngay cả trong những không gian phòng khách có nhiều cửa kính lớn và ánh sáng tự nhiên chan hòa vào ban ngày.</li>
    <li><strong>Độ tương phản ấn tượng 12.000.000:1:</strong> Nhờ khả năng tắt hoàn toàn ánh sáng tại các vùng hiển thị màu đen, các cảnh đêm hay dải ngân hà trong phim khoa học viễn tưởng đạt độ sâu thăm thẳm chuẩn mực tương đương màn hình OLED nhưng bền bỉ hơn gấp nhiều lần mà không lo hiện tượng lưu ảnh (burn-in).</li>
    <li><strong>Chipset xử lý hình ảnh độc quyền AI-Master:</strong> Tự động nhận diện cảnh quay (phim ảnh, bóng đá, tin tức, game) để điều tiết độ sáng và tương phản theo thời gian thực với độ trễ micro-giây.</li>
</ul>

<!-- 3. MÀU SẮC & CHUẨN ĐIỆN ẢNH -->
<h2 class="text-xl md:text-2xl font-bold text-slate-900 mt-10 mb-4 border-l-4 border-orange-500 pl-3">
3. Dải Màu Rộng 1.07 Tỷ Sắc Thái & Chuẩn Điện Ảnh Dolby Vision IQ, IMAX Enhanced
</h2>

<p>
Tivi Xiaomi 98 inch S Mini LED được trang bị lớp phủ chấm lượng tử <strong>Quantum Dot (QD)</strong> cao cấp, mở rộng dải không gian màu đạt tới <strong>94% chuẩn điện ảnh DCI-P3</strong> và khả năng tái hiện 1.07 tỷ sắc thái màu sắc chân thực:
</p>

<ul class="list-disc pl-6 space-y-2">
    <li><strong>Độ chính xác màu chuyên nghiệp ΔE ≈ 2:</strong> Mỗi chiếc tivi xuất xưởng đều được cân chỉnh màu sắc nghiêm ngặt theo tiêu chuẩn phòng thu hollywood, đảm bảo màu da diễn viên tự nhiên, màu xanh lá cây của sân cỏ và sắc đỏ hoàng hôn chuẩn xác không bị rực màu ảo.</li>
    <li><strong>Hỗ trợ toàn diện định dạng HDR cao cấp:</strong> Tương thích hoàn hảo với <strong>Dolby Vision IQ, HDR10+, HDR10 và HLG</strong>. Cảm biến ánh sáng môi trường tích hợp giúp tivi tự động tối ưu độ sáng của thước phim theo độ sáng thực tế trong phòng.</li>
    <li><strong>Chứng nhận IMAX Enhanced & Filmmaker Mode:</strong> Cho phép bạn thưởng thức các bộ phim bom tấn đúng với tỷ lệ khung hình và ý đồ nghệ thuật nguyên bản của đạo diễn điện ảnh thế giới.</li>
</ul>

<div class="my-6 text-center">
    <img src="/wp-content/uploads/2026/10/tivi-xiaomi-s-mini-led-98-inch-studio.jpg" 
         alt="Góc nhìn cận cảnh khung viền kim loại và độ tương phản của Tivi Xiaomi 98 inch" 
         class="rounded-xl shadow-lg mx-auto w-full max-w-4xl object-cover hover:scale-[1.01] transition-transform duration-300" 
         loading="lazy" />
    <p class="text-sm text-slate-500 italic mt-2">Khung kim loại phay xước cao cấp cùng dải màu Quantum Dot chân thực đến từng chi tiết nhỏ.</p>
</div>

<!-- 4. GAMING & THỂ THAO 144HZ -->
<h2 class="text-xl md:text-2xl font-bold text-slate-900 mt-10 mb-4 border-l-4 border-orange-500 pl-3">
4. Tần Số Quét Thực 144Hz & Công Nghệ Bù Chuyển Động MEMC 120Hz Chuyên Nghiệp
</h2>

<p>
Đối với những tín đồ đam mê bóng đá Ngoại Hạng Anh, giải đấu World Cup và game thủ console PlayStation 5 / Xbox Series X, Xiaomi 98 inch S Mini LED là cỗ máy giải trí tối thượng:
</p>

<ul class="list-disc pl-6 space-y-2">
    <li><strong>Tần số quét bản địa 144Hz Native:</strong> Khung hình được làm mới 144 lần mỗi giây, loại bỏ triệt để hiện tượng bóng ma, giật lag hay vệt mờ khi cầu thủ sút bóng tốc độ cao hay những pha rượt đuổi tốc độ trong phim hành động.</li>
    <li><strong>Chế độ Game Boost 240Hz:</strong> Khi kích hoạt chế độ chơi game chuyên nghiệp, màn hình nâng tần số quét lên tới 240Hz, kết hợp độ trễ đầu vào siêu thấp chỉ <strong>4ms</strong>.</li>
    <li><strong>Trang bị 2 cổng HDMI 2.1 eARC băng thông 48Gbps:</strong> Hỗ trợ đầy đủ các tính năng gaming đỉnh cao nhất hiện nay: <strong>VRR (Variable Refresh Rate), ALLM (Auto Low Latency Mode)</strong> và công nghệ đồng bộ chống xé hình <strong>AMD FreeSync™ Premium</strong>.</li>
</ul>

<!-- 5. ÂM THANH DOLBY ATMOS -->
<h2 class="text-xl md:text-2xl font-bold text-slate-900 mt-10 mb-4 border-l-4 border-orange-500 pl-3">
5. Hệ Thống Âm Thanh Vòm Dolby Atmos 3D & DTS:X Rung Chuyển Không Gian
</h2>

<p>
Không dừng lại ở phần nhìn xuất chúng, phần nghe của Xiaomi S98 Mini LED cũng được đầu tư tương xứng với hệ thống loa stereo công suất lớn <strong>30W (2 x 15W)</strong> sử dụng cấu trúc khoang âm độc lập và màng loa sợi thủy tinh cao cấp:
</p>
<ul class="list-disc pl-6 space-y-2">
    <li><strong>Âm thanh vòm Dolby Atmos & DTS:X 3D:</strong> Tái tạo không gian âm thanh đa chiều 360 độ sống động, cho bạn cảm giác như tiếng trực thăng bay lượn trên đầu hay tiếng bước chân kẻ địch tiếp cận ngay phía sau lưng.</li>
    <li><strong>Thuật toán tối ưu giọng thoại Voice Clarity:</strong> Tự động làm rõ giọng nói của biên tập viên tin tức và lời thoại diễn viên ngay cả trong những cảnh hành động có tiếng cháy nổ ồn ào.</li>
    <li><strong>Kết nối không dây thuận tiện:</strong> Trang bị kết nối Bluetooth 5.0 và ngõ quang học Optical, dễ dàng xuất tín hiệu âm thanh Hi-Res chất lượng cao ra hệ thống dàn loa soundbar hoặc âm ly chuyên nghiệp của gia đình.</li>
</ul>

<!-- 6. GOOGLE TV & HYPEROS -->
<h2 class="text-xl md:text-2xl font-bold text-slate-900 mt-10 mb-4 border-l-4 border-orange-500 pl-3">
6. Hệ Điều Hành Google TV Bản Quyền & Trợ Lý Ảo Tiếng Việt Thông Minh
</h2>

<p>
Sản phẩm chạy trên nền tảng <strong>Google TV™</strong> chính hãng mới nhất, giao diện trực quan được cá nhân hóa thông minh cho từng thành viên trong gia đình:
</p>
<ul class="list-disc pl-6 space-y-2">
    <li><strong>Kho ứng dụng khổng lồ:</strong> Cài đặt dễ dàng hơn 10.000 ứng dụng xem phim giải trí trực tuyến phổ biến nhất tại Việt Nam: Netflix 4K, YouTube, K+, FPT Play, VieON, TV360, Galaxy Play, Spotify...</li>
    <li><strong>Điều khiển giọng nói tiếng Việt rảnh tay (Hands-free Voice):</strong> Tích hợp micro thu âm trường xa (Far-field Mic) ngay trên thân tivi, bạn chỉ cần nói <em>"Ok Google, mở YouTube"</em> hoặc tìm kiếm bài hát mà không cần cầm vào điều khiển.</li>
    <li><strong>Trình chiếu không dây tiện lợi:</strong> Hỗ trợ đồng thời <strong>Google Cast (Chromecast built-in)</strong> cho điện thoại Android và <strong>Apple AirPlay 2</strong> cho iPhone, iPad, Macbook truyền phát hình ảnh 4K tức thì.</li>
    <li><strong>Cấu hình phần cứng mạnh mẽ:</strong> Vi xử lý lõi tứ Cortex-A73 thế hệ mới, bộ nhớ RAM 4GB chạy đa nhiệm mượt mà và bộ nhớ trong ROM 64GB rộng rãi để cài đặt mọi tựa game, ứng dụng.</li>
</ul>

<!-- 7. BẢNG SO SÁNH ĐỐI THỦ -->
<h2 class="text-xl md:text-2xl font-bold text-slate-900 mt-10 mb-4 border-l-4 border-orange-500 pl-3">
7. Bảng So Sánh Xiaomi S Mini LED 98" (L98MC-STWN) Với Các Đối Thủ Cùng Phân Khúc
</h2>

<div class="overflow-x-auto my-6">
<table class="min-w-full border-collapse border border-slate-200 text-sm md:text-base">
    <thead class="bg-slate-100 text-slate-800 font-bold">
        <tr>
            <th class="border border-slate-300 p-3 text-left">Tiêu chí so sánh</th>
            <th class="border border-slate-300 p-3 text-left text-orange-600 bg-orange-50">Xiaomi S Mini LED 98" (L98MC-STWN)</th>
            <th class="border border-slate-300 p-3 text-left">Samsung Neo QLED 98" (QN90D)</th>
            <th class="border border-slate-300 p-3 text-left">TCL QD-Mini LED 98" (C855)</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-slate-200">
        <tr>
            <td class="border border-slate-300 p-3 font-medium">Công nghệ đèn nền</td>
            <td class="border border-slate-300 p-3 text-orange-600 font-bold bg-orange-50">QD-Mini LED (880 Dimming Zones)</td>
            <td class="border border-slate-300 p-3">Neo QLED Mini LED</td>
            <td class="border border-slate-300 p-3">QD-Mini LED (1344 Zones)</td>
        </tr>
        <tr>
            <td class="border border-slate-300 p-3 font-medium">Độ sáng cực đại</td>
            <td class="border border-slate-300 p-3 text-orange-600 font-bold bg-orange-50">1.200 Nits HDR</td>
            <td class="border border-slate-300 p-3">1.500 Nits</td>
            <td class="border border-slate-300 p-3">2.000 Nits</td>
        </tr>
        <tr>
            <td class="border border-slate-300 p-3 font-medium">Tần số quét</td>
            <td class="border border-slate-300 p-3 text-orange-600 font-bold bg-orange-50">144Hz Native / 240Hz Boost</td>
            <td class="border border-slate-300 p-3">120Hz / 144Hz</td>
            <td class="border border-slate-300 p-3">144Hz Native</td>
        </tr>
        <tr>
            <td class="border border-slate-300 p-3 font-medium">Hệ điều hành</td>
            <td class="border border-slate-300 p-3 text-orange-600 font-bold bg-orange-50">Google TV (Dễ dùng, kho app mở)</td>
            <td class="border border-slate-300 p-3">Tizen OS (Đóng, ít app ngoài)</td>
            <td class="border border-slate-300 p-3">Google TV</td>
        </tr>
        <tr>
            <td class="border border-slate-300 p-3 font-medium">Giá bán lẻ thực tế</td>
            <td class="border border-slate-300 p-3 text-red-600 font-bold bg-orange-50">~ 42.990.000 đ (Tối ưu nhất)</td>
            <td class="border border-slate-300 p-3">~ 110.000.000 đ - 139.000.000 đ</td>
            <td class="border border-slate-300 p-3">~ 58.000.000 đ - 65.000.000 đ</td>
        </tr>
        <tr>
            <td class="border border-slate-300 p-3 font-medium">Chính sách bảo hành</td>
            <td class="border border-slate-300 p-3 text-orange-600 font-bold bg-orange-50">24 tháng chính hãng tại nhà</td>
            <td class="border border-slate-300 p-3">24 tháng</td>
            <td class="border border-slate-300 p-3">24 tháng</td>
        </tr>
    </tbody>
</table>
</div>
<p class="text-sm text-slate-600 italic">
=> <strong>Đánh giá thực tế:</strong> Với mức giá chỉ xấp xỉ <strong>42.990.000đ</strong>, Xiaomi S Mini LED 98" mang lại hiệu năng trên giá thành (P/P) vô đối, chỉ bằng 1/3 giá của các thương hiệu Hàn Quốc trong khi trải nghiệm tấm nền Mini LED và hệ điều hành Google TV tương đương, thậm chí vượt trội về độ tương thích.
</p>

<!-- 8. QUY TRÌNH VẬN CHUYỂN & LẮP ĐẶT CHUYÊN NGHIỆP -->
<h2 class="text-xl md:text-2xl font-bold text-slate-900 mt-10 mb-4 border-l-4 border-orange-500 pl-3">
8. Dịch Vụ Khảo Sát, Vận Chuyển & Lắp Đặt Chuyên Nghiệp Tivi 98 Inch Của TongKhoMi
</h2>

<p>
Màn hình 98 inch có kích thước bao bì hộp đựng lên tới <strong>2,4m x 1,5m x 0,3m</strong> và trọng lượng hơn 70kg. Để đảm bảo an toàn tuyệt đối cho thiết bị và nội thất của quý khách, <strong>Tổng Kho Mi (TongKhoMi.com / Michinhhang.com)</strong> áp dụng quy trình dịch vụ VIP 5 sao:
</p>

<ol class="list-decimal pl-6 space-y-3">
    <li><strong>Khảo sát lộ trình miễn phí trước khi giao hàng:</strong> Đội ngũ kỹ thuật viên sẽ liên hệ khảo sát trước kích thước cửa thang máy chung cư, cầu thang bộ, hành lang và góc quay để lên phương án vận chuyển tối ưu (thang máy, thang bộ hoặc sử dụng xe cẩu chuyên dụng cho các căn penthouse tầng cao).</li>
    <li><strong>Đội ngũ vận chuyển chuyên trách:</strong> Mỗi ca giao tivi 98 inch đều bố trí tối thiểu 4 kỹ thuật viên lành nghề với trang thiết bị đai nâng trợ lực chuyên dụng.</li>
    <li><strong>Dịch vụ lắp đặt treo tường chịu lực kiên cố:</strong> Chúng tôi cung cấp các mẫu giá treo tivi 98 inch nhập khẩu chịu tải trọng lên tới 120kg, sử dụng tắc kê bê tông nở sắt chuyên dụng, cam kết an toàn tuyệt đối 100%.</li>
    <li><strong>Bàn giao & Hướng dẫn sử dụng tận tâm:</strong> Kỹ thuật viên cài đặt đầy đủ tài khoản Google, các ứng dụng xem tivi truyền hình miễn phí trọn đời, tinh chỉnh thông số màu sắc tối ưu theo sở thích của gia chủ và dọn dẹp vệ sinh sạch sẽ khu vực thi công trước khi ra về.</li>
</ol>

<!-- 9. CÂU HỎI THƯỜNG GẶP (FAQ) -->
<h2 class="text-xl md:text-2xl font-bold text-slate-900 mt-10 mb-4 border-l-4 border-orange-500 pl-3">
9. Câu Hỏi Thường Gặp Về Tivi Xiaomi S Mini LED 98 Inch (FAQ)
</h2>

<div class="space-y-4">
    <div class="bg-slate-50 p-4 rounded-lg border border-slate-200">
        <h3 class="font-bold text-slate-900 mb-1">Khoảng cách xem an toàn cho tivi 98 inch là bao nhiêu mét?</h3>
        <p class="text-slate-700">Với độ phân giải 4K sắc nét và công nghệ bảo vệ mắt chống nhấp nháy, khoảng cách xem lý tưởng cho tivi 98 inch là từ <strong>3,5 mét đến 5,5 mét</strong>. Khoảng cách này giúp mắt bạn bao quát trọn vẹn màn hình mà không bị mỏi hay căng thẳng mắt.</p>
    </div>
    <div class="bg-slate-50 p-4 rounded-lg border border-slate-200">
        <h3 class="font-bold text-slate-900 mb-1">Tivi có vào vừa thang máy chung cư không?</h3>
        <p class="text-slate-700">Đa số thang máy tải hàng hoặc thang máy phòng cháy chữa cháy của các khu căn hộ cao cấp hiện nay đều có chiều cao từ 2,3m - 2,5m và độ sâu trên 1,5m, vừa vặn cho thùng tivi 98 inch khi đặt chéo. Kỹ thuật viên của TongKhoMi sẽ đến tận nơi đo đạc thang máy trước khi giao hàng để quý khách hoàn toàn an tâm.</p>
    </div>
    <div class="bg-slate-50 p-4 rounded-lg border border-slate-200">
        <h3 class="font-bold text-slate-900 mb-1">Chính sách bảo hành tại nhà diễn ra như thế nào?</h3>
        <p class="text-slate-700">Sản phẩm được bảo hành chính hãng <strong>24 tháng tại nhà</strong>. Khi cần hỗ trợ kỹ thuật, quý khách chỉ cần liên hệ hotline của TongKhoMi hoặc trung tâm bảo hành ủy quyền của Xiaomi, kỹ thuật viên chính hãng sẽ đến tận nơi xử lý trong vòng 24h - 48h.</p>
    </div>
</div>

</div>"""

def update_product():
    print("🚀 Đang cập nhật sản phẩm Tivi Xiaomi S Mini LED 98 inch (ID: 79) qua Omni Bridge Connector...")
    payload = {
        "post_id": 79,
        "post_title": "Tivi Xiaomi S Mini LED 98 inch 144Hz 2026 (L98MC-STWN) – Đẳng Cấp Biệt Thự",
        "post_type": "product",
        "post_status": "publish",
        "post_slug": "tivi-xiaomi-max-98-inch-mini-led-l98mc-stwn",
        "post_content": RICH_HTML_CONTENT,
        "post_excerpt": "Siêu phẩm Tivi Xiaomi S Mini LED 98 inch (L98MC-STWN) model mới 2026. Công nghệ QD-Mini LED 880 vùng sáng độc lập, tần số quét thực 144Hz Native, âm thanh Dolby Atmos 30W, Google TV bản quyền. Bảo hành 24 tháng chính hãng tại nhà, miễn phí khảo sát lắp đặt Hà Nội.",
        "featured_image_url": FEATURED_IMAGE,
        "categories": ["Tivi Xiaomi"],
        "product_data": {
            "sku": "L98MC-STWN",
            "regular_price": "52000000",
            "sale_price": "42990000",
            "stock_status": "instock",
            "gallery_image_urls": GALLERY_IMAGES,
            "technical_specs": [
                {"spec_label": "Kích thước màn hình", "spec_value": "98 inch (~2.5 mét, viền kim loại 97.8%)"},
                {"spec_label": "Công nghệ tấm nền", "spec_value": "QD-Mini LED 880 vùng làm mờ cục bộ (Local Dimming)"},
                {"spec_label": "Độ sáng đỉnh", "spec_value": "1.200 Nits HDR, Tương phản 12.000.000:1"},
                {"spec_label": "Độ phân giải & Màu sắc", "spec_value": "4K Ultra HD (3840 x 2160), 94% DCI-P3, 1.07 tỷ màu"},
                {"spec_label": "Tần số quét", "spec_value": "144Hz Native, MEMC 120Hz, Game Boost 240Hz"},
                {"spec_label": "Chuẩn hình ảnh", "spec_value": "Dolby Vision IQ, HDR10+, IMAX Enhanced, Filmmaker Mode"},
                {"spec_label": "Hệ thống âm thanh", "spec_value": "Stereo 30W (2x15W), Dolby Atmos 3D, DTS:X"},
                {"spec_label": "Bộ vi xử lý & Bộ nhớ", "spec_value": "Quad-Core Cortex-A73, RAM 4GB, ROM 64GB"},
                {"spec_label": "Hệ điều hành", "spec_value": "Google TV tiếng Việt, Trợ lý ảo Google Assistant rảnh tay"},
                {"spec_label": "Cổng kết nối", "spec_value": "2 x HDMI 2.1 (VRR, ALLM, eARC), 1 x HDMI 2.0, 2 x USB, Wi-Fi 6, Bluetooth 5.0"},
                {"spec_label": "Chính sách bảo hành", "spec_value": "24 tháng chính hãng tại nhà, 1 đổi 1 trong 30 ngày"},
                {"spec_label": "Dịch vụ vận chuyển", "spec_value": "Khảo sát thang máy miễn phí, đội kỹ thuật 4 người lắp đặt tận nơi"}
            ]
        },
        "seo_meta": {
            "seo_title": "Tivi Xiaomi S Mini LED 98 inch (L98MC-STWN) Giá Tốt Nhất 2026 | TongKhoMi",
            "seo_description": "Mua Tivi Xiaomi S Mini LED 98 inch (L98MC-STWN) chính hãng giá chỉ 42.990.000đ. Đèn nền QD-Mini LED 880 vùng sáng, 144Hz Gaming, Dolby Atmos. Khảo sát lắp đặt tận nhà biệt thự.",
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
            print("✅ Cập nhật thành công!")
            print(json.dumps(data, indent=2, ensure_ascii=False))
    except Exception as e:
        print(f"❌ Lỗi cập nhật: {e}")

if __name__ == "__main__":
    update_product()
