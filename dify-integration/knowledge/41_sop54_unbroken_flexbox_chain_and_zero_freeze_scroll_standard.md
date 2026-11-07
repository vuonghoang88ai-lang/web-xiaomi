# SOP 54: CHUẨN MỰC BẢO TOÀN CHUỖI FLEXBOX & TRIỆT TIÊU ĐÓNG BĂNG GIAO DIỆN CUỘN (UNBROKEN FLEXBOX CHAIN & ZERO-FREEZE SCROLL STANDARD)

## 1. BỐI CẢNH & NGUY CƠ ĐÓNG BĂNG GIAO DIỆN (THE FROZEN UI DILEMMA)

Khi kiến trúc giao diện khách hàng (Client Workspace / SaaS Portal) mở rộng theo mô hình Modular Clean Architecture (SOP 23 $\le 300$ dòng code), mã nguồn HTML tĩnh thường được phân tách thành các file template con (Partials) như `portal_tab_views.html`, `portal_tab_settings.html` và nạp động bằng JavaScript (`fetch` + `innerHTML`).

Một cạm bẫy kỹ thuật nghiêm trọng xuất hiện khi đưa vào các thẻ `div` bọc trung gian (ví dụ: `<div id="portalViewsContainer"></div>`):
- Thẻ `div` trung gian không được định nghĩa kiểu dáng CSS sẽ nhận giá trị mặc định `display: block; height: auto; flex: 0 1 auto;`.
- Thẻ này lập tức **bẻ gãy chuỗi Flexbox** kế thừa từ layout cha (`.main-content { display: flex; flex-direction: column; height: 100dvh; overflow: hidden; }`).
- Các tab con (`.tab-pane.active`) nằm bên trong container trung gian dù được khai báo `flex: 1 1 0%;` nhưng bị vô hiệu hóa vì cha của nó không còn là flex container. Chiều cao của tab tự do dãn nở theo toàn bộ nội dung (3.000px – 10.000px).
- Vì container cha và con đều không bị giới hạn chiều cao cố định, trình duyệt không kích hoạt thanh cuộn nội bộ (`overflow-y: auto`).
- Trong khi đó, khung nhìn gốc `.main-content` lại mang `overflow: hidden; height: 100dvh;`, dẫn đến việc **toàn bộ nội dung phía dưới bị cắt cụt (clipped)**. Người dùng lăn chuột, dùng phím mũi tên hoặc vuốt chạm di động đều không phản hồi, gây ra hiện tượng giao diện "bị treo cứng hoàn toàn" (Stuck / Frozen UI).

---

## 2. NGUYÊN TẮC BẤT BIẾN: BẢO TOÀN CHUỖI FLEXBOX VỚI `display: contents`

> [!IMPORTANT]
> Mọi container trung gian được tạo ra thuần túy phục vụ nạp partials hoặc gom nhóm DOM BẮT BUỘC phải sử dụng thuộc tính `display: contents;`.

### 2.1. Cơ chế `display: contents`
Thuộc tính `display: contents` chỉ định trình duyệt bỏ qua hộp hiển thị (box generation) của chính phần tử đó, biến các phần tử con trực tiếp của nó trở thành con trực tiếp của container cha trong cây flexbox:

```css
/* Partial Views Container (SOP 23) — Bảo toàn chuỗi Flexbox */
#portalViewsContainer {
  display: contents;
}

/* Fallback phòng ngừa cho trình duyệt không hỗ trợ display: contents */
@supports not (display: contents) {
  #portalViewsContainer {
    flex: 1 1 0%;
    min-height: 0;
    height: 100%;
    display: flex;
    flex-direction: column;
    overflow: hidden;
  }
}
```

### 2.2. Lợi ích kiến trúc
1. **Không chiếm không gian rỗng:** Khi các tab bên trong đều mang `display: none;` (ví dụ lúc đang ở tab Chat), `#portalViewsContainer` không tạo ra bất kỳ khối hộp nào, không gây xê dịch hay chiếm tỷ lệ % không gian của Chat.
2. **Kế thừa tỷ lệ hoàn hảo:** Khi một tab bất kỳ được kích hoạt (`.tab-pane.active`), nó lập tức nhận `flex: 1 1 0%; min-height: 0;` trực tiếp từ `.main-content`, khít 100% với chiều cao màn hình khả dụng trừ đi header `.topbar`.

---

## 3. TRIỆT TIÊU XUNG ĐỘT CUỘN LỒNG NHAU (SINGLE SCROLL CONTAINER DISCIPLINE)

Một phản mẫu (anti-pattern) phổ biến khác gây tê liệt cuộn là khai báo `overflow-y: auto` ở nhiều cấp cha con lồng nhau.

### 3.1. Phân định rạch ròi trách nhiệm cuộn
- **Cấp Tab Wrapper (`.tab-pane`):** Khóa cứng khung ngoài, tuyệt đối không tự ý cuộn:
  ```css
  .tab-pane {
    display: none;
    flex: 1 1 0%;
    min-height: 0;
    height: 100%;
    position: relative;
    overflow: hidden;
  }
  .tab-pane.active {
    display: flex;
    flex-direction: column;
  }
  ```

- **Cấp View Content (`.view-content-pane`):** Container duy nhất sở hữu thanh cuộn cho các tab dữ liệu (10 Đối thủ SERP, Audit Web, Ma trận Từ khóa, Cài đặt):
  ```css
  .view-content-pane {
    padding: 28px 36px;
    flex: 1 1 0%;
    min-height: 0;
    height: 100%;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    overscroll-behavior-y: contain;
  }
  ```

- **Cấp Chat Stream (`#chatFeed`):** Duy nhất feed tin nhắn được phép cuộn, input capsule và quick chips được ghim nổi cố định:
  ```css
  .chat-feed {
    flex: 1 1 0%;
    min-height: 0;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
    overscroll-behavior-y: contain;
    padding: 24px 32px;
    display: flex;
    flex-direction: column;
    gap: 20px;
  }
  ```

> [!TIP]
> Thuộc tính `overscroll-behavior-y: contain;` ngăn chặn triệt để hiện tượng cuộn xuyên thấu (Scroll Chaining) và khóa cảm ứng dính màn hình (Rubber-banding lock) trên iOS Safari và Android Chrome.

---

## 4. BẢO VỆ PHÂN TỬ DI ĐỘNG & HỘP THOẠI POP-UP (RESPONSIVE & MODALS)

### 4.1. Khóa cố định Header không bị bẹp
Topbar mang `height: 64px;` (hoặc `54px` trên mobile) BẮT BUỘC khai báo `flex-shrink: 0;` để ngăn chặn flexbox co rút thanh tiêu đề khi nội dung bên dưới quá lớn.

### 4.2. Giới hạn trần an toàn cho Modal Dialogs
Mọi modal (`.modal-card`) phải có trần chiều cao tối đa kèm thanh cuộn nội bộ để đảm bảo trên màn hình xoay ngang hoặc điện thoại nhỏ người dùng luôn bấm được nút hành động:
```css
.modal-card {
  max-height: 90dvh;
  overflow-y: auto;
}
```

### 4.3. Loại trừ giới hạn chiều rộng tĩnh (`min-width: 0 !important`)
Trên mobile ($\le 768px$), khi Artifact Canvas mở song song, bắt buộc ép `min-width: 0 !important;` trên `.chat-container` để loại trừ hoàn toàn tràn thanh cuộn ngang gây kẹt màn hình.

---

## 5. QUY TRÌNH KIỂM THỬ ĐỊNH LƯỢNG TỰ ĐỘNG (AUTOMATED ZERO-FREEZE VERIFICATION)

Trước khi nghiệm thu bất kỳ thay đổi nào liên quan đến CSS layout hoặc thêm tab mới, kỹ sư BẮT BUỘC chạy script kiểm thử tự động với Playwright / Puppeteer:

```python
# Kiểm tra chiều cao cuộn thực tế tại mọi tab
for tab in ['chat', 'my-site', 'competitors', 'keywords', 'uvp', 'cms', 'settings']:
    # Chuyển tab
    page.evaluate(f'switchTab("{tab}")')
    
    # Đo đạc thông số viewport và nội dung
    info = page.evaluate('''() => {
        const pane = document.getElementById('tab-' + tab);
        const scrollEl = pane.querySelector('.view-content-pane') || pane.querySelector('.chat-feed');
        return {
            paneHeight: pane.clientHeight,
            viewHeight: scrollEl.clientHeight,
            scrollHeight: scrollEl.scrollHeight,
            canScroll: scrollEl.scrollHeight > scrollEl.clientHeight
        };
    }''')
    
    # Tiêu chuẩn nghiệm thu:
    # 1. viewHeight phải được giới hạn vừa khít khung nhìn (< window.innerHeight)
    # 2. Với nội dung dài (scrollHeight > viewHeight), canScroll BẮT BUỘC là True
    # 3. Thử lệnh scroll: el.scrollTop = 500 => giá trị đọc lại phải chính xác bằng 500
```

