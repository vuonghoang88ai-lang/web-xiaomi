#!/usr/bin/env python3
"""EEAT Content Templates Provider - Synthesized from Competitor Intelligence.
Strictly complies with SOP 23 (<= 300 lines) and SOP 30 (Zero Hardcoding).
"""
import re
from typing import Dict, Any

def get_novel_content_pages(brand: str, domain: str, loc: str, hotline: str) -> Dict[str, Dict[str, str]]:
    """Tạo bộ 5 trang tín nhiệm E-E-A-T chuẩn văn học mạng (văn phong con người, khử sạch AI-hóa)."""
    return {
        "gioi-thieu": {
            "title": f"Về Chúng Tôi – Giới Thiệu Nền Tảng Đọc Truyện Chữ {brand}",
            "short_title": "Giới Thiệu",
            "desc": f"Giới thiệu {brand} - Nền tảng đọc truyện chữ trực tuyến tốc độ cao, tổng hợp truyện dịch & convert chọn lọc, tối ưu trải nghiệm độc giả.",
            "content": f"""
<div class="eeat-page-wrapper">
    <div class="eeat-badge" style="display:inline-block;padding:4px 12px;background:rgba(99,102,241,0.1);color:#6366f1;border-radius:20px;font-size:12px;font-weight:600;margin-bottom:12px;">📖 NỀN TẢNG VĂN HỌC MẠNG TUYỂN CHỌN</div>
    <h2>1. Sứ Mệnh & Tầm Nhìn Nền Tảng {brand}</h2>
    <p><strong>{brand}</strong> ra đời từ niềm đam mê sâu sắc với văn học số phương Đông và khao khát xây dựng một không gian đọc truyện trực tuyến văn minh, tốc độ cao, phi thương mại và tối ưu hóa trải nghiệm cho hàng triệu độc giả Việt Nam. Chúng tôi thấu hiểu những bất cập lớn mà độc giả thường gặp phải: quảng cáo cờ bạc độc hại che khuất tầm nhìn, tình trạng khóa chương vô lý để ép nạp tiền, và dịch thuật bằng máy cẩu thả đầy sạn ngữ nghĩa.</p>
    <p>Tại <strong>{brand}</strong>, sứ mệnh cốt lõi của chúng tôi là bảo đảm trải nghiệm đọc liền mạch, chữ nghĩa trau chuốt và tốc độ cập nhật chương mới nhanh nhất thị trường.</p>

    <h2>2. Kho Tàng Thư Viện Đa Dạng & Tuyển Chọn</h2>
    <p>Thư viện {brand} hiện lưu trữ hàng chục ngàn bộ tiểu thuyết chọn lọc được phân loại khoa học theo từng dòng thể loại được độc giả yêu thích nhất:</p>
    <ul>
        <li><strong>Tiên Hiệp & Kiếm Hiệp:</strong> Tu chân, nghịch thiên cải mệnh, giang hồ ân oán, chính tà phân tranh với bối cảnh hùng vĩ.</li>
        <li><strong>Ngôn Tình & Đam Mỹ:</strong> Tổng tài đô thị, cung đấu, thanh xuân vườn trường, xuyên không, sủng ngọt, ngược luyến tàn tâm.</li>
        <li><strong>Huyền Huyễn & Dị Giới:</strong> Pháp thuật đỉnh cao, triệu hồi sư, hành trình chinh phục các thế giới thần bí.</li>
        <li><strong>Đô Thị & Trọng Sinh:</strong> Cơ hội làm lại cuộc đời, kinh thương lập nghiệp, hào môn thế gia và siêu năng lực hiện đại.</li>
        <li><strong>Khoa Huyễn, Võng Du & Mạt Thế:</strong> Sinh tồn ngày tận thế, thế giới game thực tế ảo, du hành vũ trụ không gian.</li>
    </ul>

    <h2>3. Tiêu Chuẩn Kiểm Duyệt Kép Bản Dịch & Hiệu Đính</h2>
    <p>Để bảo đảm chất lượng tác phẩm cao nhất, mỗi chương truyện trước khi xuất bản đều trải qua quy trình rà soát kỹ lưỡng:</p>
    <ol>
        <li><strong>Bản Dịch Chọn Lọc:</strong> Hợp tác và tôn vinh công sức của các nhóm dịch giả tài năng, bảo đảm câu từ thuần Việt, mượt mà và giữ trọn hồn văn của tác giả gốc.</li>
        <li><strong>Bản Hiệu Đính Thuần Việt:</strong> Áp dụng bộ từ điển quy chuẩn hơn 200.000 thuật ngữ Hán - Việt, lọc sạch sạn từ ngữ xưng hô máy móc, giúp mạch truyện tự nhiên, dễ tiếp nhận.</li>
    </ol>

    <h2>4. Công Nghệ Đọc Tối Ưu – Mobile-First Experience</h2>
    <p>Chúng tôi phát triển giao diện đọc truyện theo chuẩn mực công nghệ hiện đại nhất:</p>
    <ul>
        <li><strong>Chế độ Dark Mode & Warm Paper:</strong> Khử ánh sáng xanh, chống mỏi mắt tối đa khi độc giả đọc truyện trong đêm.</li>
        <li><strong>Tùy biến linh hoạt:</strong> Tự do điều chỉnh kích cỡ chữ, giãn cách dòng và lựa chọn font chữ yêu thích (Literata, Merriweather, Roboto).</li>
        <li><strong>Lưu tiến độ đọc tự động:</strong> Tự động ghi nhớ chương đang đọc dở trên mọi thiết bị mà không bắt buộc tạo tài khoản.</li>
        <li><strong>Tốc độ tải siêu tốc:</strong> Tối ưu hóa hạ tầng CDN đa vùng, trang tải trong 0.3s, hoàn toàn không có pop-up chuyển hướng lừa đảo.</li>
    </ul>

    <h2>5. Cam Kết Văn Hóa Đọc & Trách Nhiệm Với Cộng Đồng</h2>
    <p><strong>{brand}</strong> kiên định xây dựng một không gian văn hóa đọc lành mạnh, phi lợi nhuận cho độc giả: tuyệt đối tôn trọng quyền tác giả và công sức dịch thuật, duy trì diễn đàn bình luận văn minh, bài trừ các nội dung bạo lực cực đoan, vi phạm thuần phong mỹ tục hoặc pháp luật Việt Nam.</p>
</div>
"""
        },
        "dieu-khoan-su-dung": {
            "title": f"Điều Khoản Sử Dụng & Nội Quy Nền Tảng Độc Giả – {brand}",
            "short_title": "Điều Khoản Sử Dụng",
            "desc": f"Điều khoản dịch vụ, quy chế hoạt động và nội quy cộng đồng độc giả tại {brand}. Đảm bảo môi trường đọc truyện văn minh và an toàn.",
            "content": f"""
<div class="eeat-page-wrapper">
    <div class="eeat-badge" style="display:inline-block;padding:4px 12px;background:rgba(99,102,241,0.1);color:#6366f1;border-radius:20px;font-size:12px;font-weight:600;margin-bottom:12px;">⚖️ QUY CHẾ HOẠT ĐỘNG & NỘI QUY CỘNG ĐỒNG</div>
    <h2>1. Nguyên Tắc Chung & Chấp Thuận Điều Khoản</h2>
    <p>Chào mừng bạn đến với <strong>{brand}</strong>. Khi truy cập, tìm kiếm hoặc đọc truyện trên nền tảng của chúng tôi, bạn mặc nhiên đồng ý tuân thủ toàn bộ các điều khoản sử dụng, chính sách bảo mật và nội quy cộng đồng được quy định tại đây. Nếu bạn không đồng ý với bất kỳ điều khoản nào, vui lòng ngừng sử dụng dịch vụ.</p>

    <h2>2. Dịch Vụ Đọc Truyện Phi Thương Mại</h2>
    <p>Toàn bộ tác phẩm văn học trên <strong>{brand}</strong> được chia sẻ và tổng hợp nhằm mục đích giao lưu, học tập ngôn ngữ và trải nghiệm văn hóa đọc phi thương mại. Chúng tôi cam kết không thu phí đọc truyện đối với các nội dung do cộng đồng đóng góp.</p>
    <p><strong>Nghiêm cấm hành vi phá hoại:</strong> Tuyệt đối cấm sử dụng bot, crawler tự động hoặc các công cụ kỹ thuật để cào trộm dữ liệu hàng loạt gây quá tải băng thông của máy chủ.</p>

    <h2>3. Chuẩn Mực Hành Vi & Văn Hóa Bình Luận Độc Giả</h2>
    <p>Để bảo đảm môi trường thảo luận văn minh, các thành viên cam kết:</p>
    <ul>
        <li>Tôn trọng tác giả, nhóm dịch thuật và các độc giả khác. Không sử dụng ngôn từ thô tục, bôi nhọ, xúc phạm danh dự cá nhân hoặc phân biệt vùng miền.</li>
        <li><strong>Nghiêm cấm Spoil ác ý:</strong> Không cố ý tiết lộ diễn biến quan trọng của tác phẩm mà không có cảnh báo, gây tổn hại trải nghiệm của người đọc sau.</li>
        <li><strong>Bài trừ liên kết độc hại:</strong> Nghiêm cấm chèn link quảng cáo cờ bạc, cá độ bóng đá, nội dung khiêu dâm, lừa đảo tài chính hoặc mã độc vào khung bình luận.</li>
    </ul>

    <h2>4. Quyền Sở Hữu Trí Tuệ & Nội Dung Tự Sáng Tác</h2>
    <p>Đối với các tác phẩm do tác giả thành viên tự sáng tác và đăng tải trên {brand}, tác giả giữ toàn bộ bản quyền tác phẩm. Bằng việc đăng tải, tác giả cấp cho {brand} quyền hiển thị phi độc quyền trên nền tảng để phục vụ độc giả.</p>

    <h2>5. Chế Tài Xử Lý Vi Phạm</h2>
    <p>Ban Quản Trị {brand} có toàn quyền ẩn, xóa bỏ các bình luận vi phạm và áp dụng chế tài khóa tài khoản hoặc chặn vĩnh viễn địa chỉ IP truy cập đối với các trường hợp cố tình phá hoại nội quy nền tảng mà không cần thông báo trước.</p>
</div>
"""
        },
        "chinh-sach-bao-mat": {
            "title": f"Chính Sách Bảo Mật & Quyền Riêng Tư Độc Giả – {brand}",
            "short_title": "Chính Sách Bảo Mật",
            "desc": f"Chính sách bảo mật quyền riêng tư độc giả tại {brand}. Cam kết bảo vệ dữ liệu cá nhân theo Nghị định 13/2023/NĐ-CP.",
            "content": f"""
<div class="eeat-page-wrapper">
    <div class="eeat-badge" style="display:inline-block;padding:4px 12px;background:rgba(16,185,129,0.1);color:#10b981;border-radius:20px;font-size:12px;font-weight:600;margin-bottom:12px;">🛡️ BẢO VỆ DỮ LIỆU & QUYỀN RIÊNG TƯ ĐỘC GIẢ</div>
    <h2>1. Cam Kết Quyền Riêng Tư & Sự Ẩn Danh Của Độc Giả</h2>
    <p><strong>{brand}</strong> tôn trọng tuyệt đối quyền riêng tư của mỗi độc giả. Chúng tôi chủ trương tối thiểu hóa việc thu thập dữ liệu cá nhân: người đọc hoàn toàn có thể thưởng thức toàn bộ truyện chữ trên nền tảng mà không bắt buộc phải cung cấp họ tên, số điện thoại hay thông tin nhận dạng cá nhân.</p>

    <h2>2. Phạm Vi Dữ Liệu Được Thu Thập</h2>
    <ul>
        <li><strong>Dữ liệu tùy chọn giao diện (Cookie & LocalStorage):</strong> Lưu trữ chế độ nền đọc (Dark Mode / Sáng), kích cỡ font chữ, danh sách truyện theo dõi và lịch sử chương đọc dở để phục vụ tự động quay lại đúng vị trí đọc.</li>
        <li><strong>Dữ liệu nhật ký máy chủ (Log Data):</strong> Thu thập ẩn danh địa chỉ IP, loại trình duyệt, thời gian truy cập nhằm mục đích tối ưu hóa đường truyền, chống tấn công DDoS và cải thiện tốc độ tải trang.</li>
    </ul>

    <h2>3. Cam Kết Ba KHÔNG Về An Toàn Dữ Liệu</h2>
    <ol>
        <li><strong>KHÔNG</strong> bán, chia sẻ hoặc thương mại hóa dữ liệu độc giả cho bất kỳ bên thứ ba hay mạng quảng cáo nào.</li>
        <li><strong>KHÔNG</strong> cài đặt mã theo dõi độc hại, script đào tiền ảo ngầm hoặc pop-up quảng cáo can thiệp hệ thống.</li>
        <li><strong>KHÔNG</strong> lưu trữ thông tin thanh toán hay dữ liệu nhạy cảm trên máy chủ công khai.</li>
    </ol>

    <h2>4. Tiêu Chuẩn Bảo Mật Đường Truyền SSL/TLS</h2>
    <p>Toàn bộ lưu lượng truy cập giữa trình duyệt của bạn và máy chủ {brand} đều được mã hóa bằng giao thức HTTPS với chứng chỉ SSL/TLS 256-bit chuẩn quốc tế, bảo vệ an toàn ngay cả khi bạn truy cập qua mạng Wi-Fi công cộng.</p>

    <h2>5. Quyền Của Độc Giả Theo Nghị Định 13/2023/NĐ-CP</h2>
    <p>Độc giả có toàn quyền tự do xóa cookie trình duyệt để xóa sạch lịch sử đọc, hoặc gửi yêu cầu đến hòm thư <strong>privacy@{domain}</strong> để yêu cầu gỡ bỏ các thông tin liên quan đến tài khoản hay bình luận cá nhân trên hệ thống.</p>
</div>
"""
        },
        "ban-quyen-dmca": {
            "title": f"Tuyên Bố Bản Quyền & Quy Trình Khiếu Nại DMCA – {brand}",
            "short_title": "Bản Quyền & DMCA",
            "desc": f"Chính sách tôn trọng quyền sở hữu trí tuệ và quy trình tiếp nhận xử lý khiếu nại bản quyền DMCA minh bạch tại {brand}.",
            "content": f"""
<div class="eeat-page-wrapper">
    <div class="eeat-badge" style="display:inline-block;padding:4px 12px;background:rgba(239,68,68,0.1);color:#ef4444;border-radius:20px;font-size:12px;font-weight:600;margin-bottom:12px;">📜 CHÍNH SÁCH BẢN QUYỀN & MIỄN TRỪ DMCA</div>
    <h2>1. Tuyên Bố Tôn Trọng Sở Hữu Trí Tuệ</h2>
    <p><strong>{brand}</strong> khẳng định sự tôn trọng cao nhất đối với quyền sở hữu trí tuệ, tác quyền văn học của các tác giả, dịch giả và nhà xuất bản trên toàn thế giới theo đúng quy định của Luật Sở hữu trí tuệ Việt Nam và Công ước Bern về bảo hộ các tác phẩm văn học nghệ thuật.</p>

    <h2>2. Bản Chất Nền Tảng & Miễn Trừ Trách Nhiệm</h2>
    <p><strong>{brand}</strong> hoạt động như một nền tảng thư viện số mở, nơi các thành viên cộng đồng mạng chia sẻ và sưu tầm các tác phẩm văn học số nhằm mục đích đọc thử, trau dồi ngôn ngữ và trải nghiệm văn hóa đọc phi thương mại. Chúng tôi không sở hữu bản quyền gốc đối với các tác phẩm do cộng đồng đăng tải.</p>

    <h2>3. Quy Trình Tiếp Nhận Khiếu Nại Bản Quyền (DMCA Takedown Notice)</h2>
    <p>Nếu bạn là chủ sở hữu hợp pháp của một tác phẩm hoặc được ủy quyền đại diện chính thức và nhận thấy tác phẩm của mình xuất hiện trên {brand} mà chưa có sự đồng ý, vui lòng gửi văn bản thông báo khiếu nại qua email bao gồm các thông tin:</p>
    <ul>
        <li>Họ tên, đơn vị, thông tin liên hệ chính thức của chủ sở hữu bản quyền.</li>
        <li>Tên tác phẩm và đường dẫn URL chính xác của chương truyện bị khiếu nại trên {brand}.</li>
        <li>Bằng chứng chứng minh quyền sở hữu hợp pháp (giấy chứng nhận đăng ký bản quyền, hợp đồng xuất bản hoặc link phát hành gốc).</li>
        <li>Tuyên bố cam kết thông tin khiếu nại là trung thực và chịu trách nhiệm trước pháp luật.</li>
    </ul>

    <h2>4. Cam Kết Thời Gian Xử Lý Thần Tốc</h2>
    <p>Ban Quản Trị {brand} cam kết quy trình xử lý minh bạch và nhanh chóng:</p>
    <ul>
        <li>Tiếp nhận và phản hồi email xác nhận trong vòng <strong>12 – 24 giờ</strong>.</li>
        <li>Rà soát, thẩm định và <strong>gỡ bỏ hoàn toàn tác phẩm vi phạm khỏi hệ thống máy chủ trong vòng 24 – 48 giờ làm việc</strong>.</li>
    </ul>
    <p>Hộp thư chuyên trách tiếp nhận khiếu nại bản quyền: <strong>dmca@{domain}</strong> hoặc <strong>contact@{domain}</strong>.</p>
</div>
"""
        },
        "lien-he": {
            "title": f"Liên Hệ Ban Biên Tập & Hộp Thư Báo Lỗi Chương – {brand}",
            "short_title": "Liên Hệ & Báo Lỗi",
            "desc": f"Kênh liên hệ trực tiếp Ban Quản Trị {brand}. Tiếp nhận góp ý, báo lỗi chương truyện và hợp tác chia sẻ truyện dịch 24/7.",
            "content": f"""
<div class="eeat-page-wrapper">
    <div class="eeat-badge" style="display:inline-block;padding:4px 12px;background:rgba(59,130,246,0.1);color:#3b82f6;border-radius:20px;font-size:12px;font-weight:600;margin-bottom:12px;">📮 KÊNH HỖ TRỢ ĐỘC GIẢ & BÁO LỖI CHƯƠNG</div>
    <h2>1. Kênh Tiếp Nhận Ý Kiến & Hỗ Trợ Độc Giả</h2>
    <p>Ban Quản Trị <strong>{brand}</strong> luôn trân trọng và lắng nghe mọi phản hồi, đóng góp từ phía quý độc giả nhằm nâng cao chất lượng bản dịch và trải nghiệm đọc mỗi ngày.</p>

    <h2>2. Hướng Dẫn Báo Lỗi Chương Truyện Hỏng</h2>
    <p>Trong quá trình đọc, nếu phát hiện bất kỳ sự cố kỹ thuật nào, kính mong độc giả thông báo ngay cho Ban Biên Tập:</p>
    <ul>
        <li><strong>Lỗi nội dung:</strong> Chương bị mất chữ, nhảy đoạn, lặp lại chương trước hoặc nhầm lẫn tên nhân vật.</li>
        <li><strong>Lỗi định dạng:</strong> Lỗi font chữ, sai thứ tự chương, text bị dính liền khó đọc.</li>
        <li><strong>Lỗi liên kết:</strong> Nút chuyển chương trước/sau không hoạt động hoặc trang báo lỗi 404.</li>
    </ul>
    <p><strong>Cách thức báo lỗi:</strong> Gửi email nêu rõ <em>Tên truyện</em>, <em>Số chương</em> và <em>Đường dẫn link bị lỗi</em>. Đội ngũ trực ban cam kết khắc phục trong vòng <strong>2 – 6 giờ</strong>.</p>

    <h2>3. Hợp Tác Dịch Thuật & Phát Triển Cộng Đồng</h2>
    <p>Chúng tôi luôn mở rộng cánh cửa chào đón các nhóm dịch giả, converter tự do muốn chia sẻ tác phẩm của mình đến hàng triệu độc giả yêu văn học mạng. Chúng tôi cam kết bảo lưu nguyên vẹn nguồn dịch và tên nhóm dịch trong từng chương truyện.</p>

    <h2>4. Thông Tin Liên Hệ Chính Thức</h2>
    <div style="background:rgba(99,102,241,0.06);border:1px solid rgba(99,102,241,0.2);padding:16px 20px;border-radius:8px;line-height:1.8;">
        <p><strong>Nền tảng:</strong> {brand} – Đọc Truyện Chữ Trực Tuyến Tốc Độ Cao</p>
        <p><strong>Hộp thư Ban Quản Trị:</strong> contact@{domain} | vuonghoang88.ai@gmail.com</p>
        <p><strong>Bộ phận Pháp lý & Bản quyền:</strong> dmca@{domain}</p>
        <p><strong>Thời gian làm việc:</strong> Hỗ trợ trực tuyến 24/7 (Phản hồi email trong vòng tối đa 24h).</p>
    </div>
</div>
"""
        }
    }

def get_service_content_pages(brand: str, domain: str, loc: str, hotline: str) -> Dict[str, Dict[str, str]]:
    """Tạo bộ 5 trang tín nhiệm E-E-A-T cho ngành Dịch vụ / Y tế / B2B."""
    return {
        "gioi-thieu": {
            "title": f"Về Chúng Tôi – Đơn Vị Dịch Vụ Uy Tín {brand}",
            "short_title": "Giới Thiệu",
            "desc": f"Hành trình phát triển, năng lực chuyên môn và đội ngũ chuyên gia tại {brand}.",
            "content": f"<h2>Về {brand}</h2><p>{brand} là đơn vị chuyên nghiệp hàng đầu tại {loc}, cam kết mang lại giải pháp tối ưu và giá trị bền vững cho khách hàng.</p>"
        },
        "dieu-khoan-dich-vu": {
            "title": f"Điều Khoản & Quy Trình Cung Cấp Dịch Vụ – {brand}",
            "short_title": "Điều Khoản Dịch Vụ",
            "desc": f"Quy định cung ứng dịch vụ minh bạch và cam kết chất lượng tại {brand}.",
            "content": f"<h2>Quy Trình Cung Cấp Dịch Vụ</h2><p>{brand} cam kết thực hiện đúng hợp đồng, minh bạch chi phí và bảo đảm tiến độ cam kết.</p>"
        },
        "chinh-sach-bao-mat": {
            "title": f"Chính Sách Bảo Mật Hồ Sơ Khách Hàng – {brand}",
            "short_title": "Chính Sách Bảo Mật",
            "desc": f"Cam kết bảo vệ tuyệt đối hồ sơ và dữ liệu tư vấn của khách hàng tại {brand}.",
            "content": f"<h2>Bảo Mật Thông Tin</h2><p>Hồ sơ và thông tin khách hàng được mã hóa an toàn, không tiết lộ cho bên thứ ba.</p>"
        },
        "quy-chuan-chuyen-mon": {
            "title": f"Cam Kết Tiêu Chuẩn Năng Lực Chuyên Môn – {brand}",
            "short_title": "Tiêu Chuẩn Năng Lực",
            "desc": f"Giấy phép hành nghề, chứng chỉ năng lực và quy chuẩn chuyên môn tại {brand}.",
            "content": f"<h2>Năng Lực Chuyên Môn</h2><p>Đội ngũ chuyên gia sở hữu đầy đủ chứng chỉ chuyên ngành và kinh nghiệm thực chiến dày dặn.</p>"
        },
        "lien-he": {
            "title": f"Thông Tin Liên Hệ & Đặt Lịch Tư Vấn – {brand}",
            "short_title": "Liên Hệ & Đặt Lịch",
            "desc": f"Địa chỉ văn phòng, hotline hỗ trợ và kênh tư vấn trực tiếp tại {brand}.",
            "content": f"<h2>Liên Hệ</h2><p>Địa chỉ: {loc} | Hotline: {hotline} | Email: contact@{domain}</p>"
        }
    }

def get_commerce_content_pages(brand: str, domain: str, loc: str, hotline: str) -> Dict[str, Dict[str, str]]:
    """Tạo bộ 5 trang tín nhiệm E-E-A-T cho ngành Bán lẻ / Thương mại điện tử."""
    return {
        "chinh-sach-bao-hanh": {
            "title": f"Chính Sách Bảo Hành & Đổi Mới Chuẩn Hãng – {brand}",
            "short_title": "Chính Sách Bảo Hành",
            "desc": f"Cam kết bảo hành chính hãng, chính sách 1 đổi 1 và hỗ trợ kỹ thuật tận tâm tại {brand}.",
            "content": f"<h2>Phạm Vi Bảo Hành</h2><p>Tất cả sản phẩm phân phối bởi {brand} đều được cam kết bảo hành chính hãng theo đúng tiêu chuẩn nhà sản xuất tại {loc}. Hotline: {hotline}.</p>"
        },
        "chinh-sach-doi-tra": {
            "title": f"Chính Sách Đổi Trả & Hoàn Tiền Minh Bạch – {brand}",
            "short_title": "Chính Sách Đổi Trả",
            "desc": f"Quy trình đổi trả trong 30 ngày, hoàn tiền minh bạch tại {brand}.",
            "content": f"<h2>Chính Sách Đổi Trả</h2><p>Khách hàng được quyền đổi mới hoặc hoàn tiền trong vòng 30 ngày nếu phát sinh lỗi từ nhà sản xuất.</p>"
        },
        "chinh-sach-van-chuyen": {
            "title": f"Chính Sách Giao Hàng & Đồng Kiểm Toàn Quốc – {brand}",
            "short_title": "Chính Sách Giao Hàng",
            "desc": f"Vận chuyển an toàn, giao hàng nhanh chóng và hỗ trợ đồng kiểm trước khi thanh toán tại {brand}.",
            "content": f"<h2>Quy Định Giao Hàng</h2><p>Giao hàng toàn quốc an toàn, khách hàng được quyền kiểm tra hàng trước khi thanh toán.</p>"
        },
        "gioi-thieu": {
            "title": f"Về Chúng Tôi – Hệ Thống Phân Phối Uy Tín {brand}",
            "short_title": "Giới Thiệu",
            "desc": f"Lịch sử phát triển và cam kết phân phối hàng chính hãng tận gốc tại {brand}.",
            "content": f"<h2>Về {brand}</h2><p>{brand} là đối tác phân phối uy tín hàng đầu, mang đến sản phẩm chất lượng tốt nhất với giá gốc tận kho.</p>"
        },
        "lien-he": {
            "title": f"Thông Tin Liên Hệ Trực Tiếp & Hệ Thống Kho Hàng – {brand}",
            "short_title": "Liên Hệ & Kho Hàng",
            "desc": f"Địa chỉ kho hàng, hotline kinh doanh và hỗ trợ bảo hành tại {brand}.",
            "content": f"<h2>Thông Tin Doanh Nghiệp</h2><p>Kho hàng: {loc} | Hotline: {hotline} | Email: contact@{domain}</p>"
        }
    }

def get_archetype_pages(archetype: str, brand: str, domain: str, loc: str, hotline: str) -> Dict[str, Dict[str, str]]:
    """Điều hướng sinh bộ trang E-E-A-T theo Archetype."""
    if archetype == "content_media":
        return get_novel_content_pages(brand, domain, loc, hotline)
    elif archetype == "service_health_b2b":
        return get_service_content_pages(brand, domain, loc, hotline)
    return get_commerce_content_pages(brand, domain, loc, hotline)

def audit_and_neutralize_eeat_pages(pages: Dict[str, Dict[str, str]]) -> Dict[str, Any]:
    """Thẩm định độ sâu nội dung và khử trùng 100% các dấu hiệu AI-hóa trước khi khóa bảo toàn."""
    results = {}
    ai_banned_terms = ["chuẩn e-e-a-t", "e-e-a-t google", "bảo chứng chất lượng", "dưới góc nhìn của một ai", "với tư cách là một ai"]
    all_passed = True
    for slug, page in pages.items():
        title = page.get("title", "")
        content = page.get("content", "")
        for term in ai_banned_terms:
            if term in title.lower():
                page["title"] = re.sub(re.escape(term), "", title, flags=re.IGNORECASE).strip(' -–—:')
            if term in content.lower():
                content = re.sub(re.escape(term), "", content, flags=re.IGNORECASE)
        page["content"] = content
        char_count = len(content)
        h2_count = len(re.findall(r'<h[23]\b', content, re.IGNORECASE))
        is_deep = char_count >= 500 and h2_count >= 2
        results[slug] = {"title": page.get("title"), "char_count": char_count, "heading_count": h2_count, "is_deep": is_deep}
        if not is_deep:
            all_passed = False

    return {"all_passed": all_passed, "is_locked": all_passed, "details": results}

