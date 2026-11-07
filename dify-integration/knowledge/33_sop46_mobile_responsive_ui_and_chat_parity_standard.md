# SOP 46: BỘ QUY CHUẨN GIAO DIỆN DI ĐỘNG & TRẢI NGHIỆM CHAT PARITY THEO GEMINI & CHATGPT MOBILE

> **Mục tiêu**: Chuẩn hóa toàn bộ kiến trúc giao diện người dùng trên thiết bị di động (Smartphones, Phablets & Tablets), triệt tiêu hoàn toàn các lỗi xô lệch Topbar, tràn phím ảo Android (`100vh` bug), tự động phóng to Safari iOS và đảm bảo trải nghiệm tương đương ứng dụng Google Gemini / OpenAI ChatGPT native.

---

## 1. BỐI CẢNH VÀ CÁC ĐIỂM NGHẼN TRÊN MOBILE THỰC TẾ

### 1.1. Lỗi chèn ép Header (Topbar Overcrowding)
- **Hiện tượng**: Khi hiển thị trên màn hình chiều rộng $360\text{px} - 390\text{px}$, thanh Topbar bị nhồi nhét cùng lúc 7 phần tử: Nút Menu, Tên thương hiệu, Chấm trạng thái kết nối, Bộ chọn ngôn ngữ (`VI | EN`), Huy hiệu bản quyền (`Còn 176.6 ngày`), Nút lịch sử chat và Nút làm mới phiên.
- **Hệ quả**: Tên thương hiệu bị co giật thành `t..`, huy hiệu bản quyền bị bẻ thành 2 dòng đè lên nội dung chat, giao diện trông hỗn loạn và thiếu chuyên nghiệp.
- **Nguyên nhân gốc rễ**: Lạm dụng `style="display: flex;"` nội dòng (inline style) trong mã HTML, ngăn cản CSS media queries ghi đè thuộc tính ẩn/hiện, kết hợp với việc trình duyệt di động lưu cache file CSS cũ.

### 1.2. Lỗi trôi khung gõ lệnh dưới thanh phím ảo Android (`100vh` Bug)
- **Hiện tượng**: Ô nhập văn bản bị cắt ngang một nửa (`Nhập câu hỏi hoặc gõ lệnh...`), nút Gửi bị biến mất hoàn toàn dưới thanh điều hướng 3 nút ảo của Android (`[ ◂  ○  ■ ]`).
- **Nguyên nhân gốc rễ**:
  1. Trình duyệt di động tính toán `100vh` dựa trên kích thước màn hình khi thanh địa chỉ URL và thanh điều hướng hệ thống đang ẩn. Khi chúng xuất hiện, không gian thực tế bị thu hẹp lại khiến các phần tử ở đáy trang bị đẩy xuống dưới nếp gấp màn hình (screen fold).
  2. Thiếu ràng buộc `min-height: 0;` trong chuỗi Flexbox cha con, khiến danh sách tin nhắn (`chat-feed`) phình to đẩy khung nhập liệu (`chat-input-wrapper`) ra khỏi khung nhìn.

---

## 2. 5 NGUYÊN TẮC BẤT BIẾN CỦA QUY CHUẨN SOP 46

### 2.1. Quy tắc 1: Cấu trúc 3 Điểm Chạm Header Mobile (3-Touchpoint Minimal Header)
Trên màn hình di động ($\le 768\text{px}$), Header **TUYỆT ĐỐI CHỈ ĐƯỢC PHÉP** xuất hiện 3 khối chức năng:
1. **Mép trái:** Nút Hamburger Menu (☰) kích thước $38\text{px} \times 38\text{px}$, bo góc $10\text{px}$.
2. **Chính giữa:** Tên dự án/thương hiệu căn giữa (`text-overflow: ellipsis`, tối đa $150\text{px}$ - $170\text{px}$).
3. **Mép phải:** 2 nút tác vụ nhanh dạng icon đơn sắc: 🕒 (Lịch sử phiên chat Gemini) và 🔄/➕ (Làm mới / Đoạn chat mới).

> [!IMPORTANT]
> **Tuyệt đối CẤM** nhồi nhét Bộ chọn ngôn ngữ (`langSwitcherContainer`), Huy hiệu bản quyền (`headerSubscriptionBadge`), hoặc Link website phụ lên thanh Topbar di động. Toàn bộ các thông tin này BẮT BUỘC phải quy hoạch về chân trang của Sidebar Drawer (khi bấm ☰).

### 2.2. Quy tắc 2: Chuỗi Flexbox Khép Kín & Dynamic Viewport (`100dvh`)
- **Triệt tiêu `100vh`**: Thay thế 100% bằng `height: 100%; height: 100dvh;`.
- **Chuỗi kế thừa Flexbox**: Mọi tầng container từ `html`, `body`, `.app-layout`, `.main-content`, `.tab-pane.active` đến `.chat-container` BẮT BUỘC khai báo `flex: 1 1 0%; min-height: 0; overflow: hidden;`.
- **Ghim chắc chắn Input Bar**: Khung gõ lệnh `.chat-input-wrapper` BẮT BUỘC mang thuộc tính `flex-shrink: 0;` kèm vùng đệm an toàn `padding-bottom: calc(14px + env(safe-area-inset-bottom, 0px));`. Khi đó, danh sách tin nhắn tự động co giãn trong không gian còn lại, khung nhập lệnh vĩnh viễn không bao giờ bị che khuất bởi phím ảo.

### 2.3. Quy tắc 3: Khung Gõ Lệnh Viên Thuốc Nổi (Signature Floating Capsule Input)
- **Thiết kế viên thuốc**: Bo cong viền `border-radius: 26px;`, phủ hiệu ứng kính mờ `backdrop-filter: blur(16px);`, nền `rgba(30, 41, 59, 0.92);`, bóng đổ `box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);`.
- **Chống Auto-Zoom trên iOS Safari**: Thẻ `textarea` BẮT BUỘC duy trì `font-size: 16px;`. Nếu đặt font chữ nhỏ hơn 16px, iOS Safari sẽ tự động phóng to toàn trang gây hỏng layout.
- **Nút gửi tròn**: Kích thước $36\text{px} \times 36\text{px}$, bo tròn 50%, có hiệu ứng xúc giác `active:scale(0.92);`.

### 2.4. Quy tắc 4: Đắm Chìm Tin Nhắn & Vuốt Ngang Không Vỡ Layout
- **Tin nhắn User (ChatGPT Style)**: Căn lề phải, bo góc `border-radius: 20px 20px 4px 20px;`, nền gradient xanh tím, chiều rộng tối đa 86%. Ẩn avatar người dùng trên mobile để tối đa diện tích đọc.
- **Tin nhắn AI Copilot (Gemini Style)**: Tràn màn hình 100%, nền mờ phẳng không đóng hộp ngột ngạt. Bảng biểu (Tables) và khối mã (Code) có thanh trượt ngang cảm ứng mượt mà (`-webkit-overflow-scrolling: touch;`).
- **Gợi ý Thông minh & Lệnh Nhanh**: Các nút `/strategy`, `/agentic`, `/brief` xếp thành dải chip vuốt ngang vô tận, ẩn thanh cuộn thô cứng (`scrollbar-width: none;`).

### 2.5. Quy tắc 5: Cấm Inline Style & Bắt Buộc Cache-Busting Versioning
- **Cấm Inline Style**: Tuyệt đối không viết `style="display: flex; ..."` cứng trong file HTML. Mọi định dạng bố cục phải nằm trong file `.css` để media queries kiểm soát triệt để.
- **Bắt buộc Cache-Busting**: Khi cập nhật giao diện mobile, BẮT BUỘC nâng tham số phiên bản `?v=YYYYMMDD_HHMM` trong các thẻ `<link rel="stylesheet">` của file `index.html` để điện thoại của khách hàng lập tức tải mã mới mà không cần xóa cache thủ công.

---

## 3. BẢNG ĐỐI CHIẾU THÔNG SỐ RESPONSIVE DI ĐỘNG

| Vị trí / Thành phần | Quy cách Máy tính (Desktop) | Quy cách Di động (Mobile $\le 768\text{px}$) | Lý do thiết kế (Parity) |
| :--- | :--- | :--- | :--- |
| **Topbar Header** | $64\text{px}$, đầy đủ 7 nút & badge | $52\text{px} - 54\text{px}$, chỉ giữ 3 điểm chạm | Tránh đè chữ `t..`, giao diện sạch như Gemini |
| **Huy hiệu Bản quyền** | Nổi bật góc trên bên phải Topbar | Chuyển vào menu chân trang Sidebar | Giải phóng 100px không gian quý giá trên mobile |
| **Chiều cao Viewport** | `height: 100vh` | `height: 100dvh; flex: 1 1 0%; min-height: 0;` | Triệt tiêu lỗi thụt đáy thanh 3 phím Android |
| **Khung Gõ Chat** | Chữ nhật bo góc 16px ở đáy | Viên thuốc nổi bo 26px + safe-area padding | Chuẩn Floating Pill của ChatGPT & Gemini |
| **Cỡ chữ Textarea** | $14\text{px}$ | $16\text{px}$ (bắt buộc) | Ngăn chặn Safari iOS tự động zoom màn hình |
| **Tin nhắn User** | Có avatar người dùng, rộng 75% | Ẩn avatar, căn phải bo 20px, rộng 86% | Tối ưu hóa bề ngang màn hình nhỏ |
| **Cử chỉ điều hướng** | Click chuột | Vuốt cạnh trái sang phải để mở Menu | Cảm giác mượt mà như ứng dụng native |

---

## 4. QUY CHUẨN MÃ NGUỒN (SOP 23 COMPLIANCE)
- Mọi module xử lý di động:
  - `agent-engine/portal/portal_responsive.css`: Duy trì $\le 300$ dòng code.
  - `agent-engine/portal/portal_responsive.js`: Duy trì $\le 300$ dòng code.
- Tuyệt đối không viết dồn mã CSS media query vào file chính làm phình to vượt ngưỡng 300 dòng.

