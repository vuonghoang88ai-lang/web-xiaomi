# SOP 44: QUY TẮC KỸ NGHỆ KARPATHY (KARPATHY-INSPIRED AI CODING & REASONING GUIDELINES)

Quy tắc cốt lõi được đúc kết từ Andrej Karpathy (Cựu Giám đốc AI Tesla, Nhà đồng sáng lập OpenAI) nhằm triệt tiêu các sai lầm kinh điển của mô hình LLM khi lập trình và suy luận logic.

---

## 1. BỐN NGUYÊN TẮC BẤT BIẾN (THE FOUR PILLARS)

### 1.1. THINK BEFORE CODING (Suy nghĩ thấu đáo trước khi viết code)
> *"Đừng tự ý giả định ngầm. Đừng giấu sự bối rối. Hãy nêu rõ các đánh đổi (tradeoffs)."*

- **Công khai giả định:** Nếu có điểm chưa chắc chắn, PHẢI hỏi người dùng thay vì tự tiện đoán bừa và chạy theo giả định sai.
- **Trình bày đa phương án:** Khi một yêu cầu có nhiều cách hiểu hoặc nhiều kiến trúc khả dĩ, phải nêu rõ các phương án kèm đánh đổi (thời gian, độ phức tạp, rủi ro) - CẤM âm thầm chọn một cách.
- **Phản biện khi cần thiết:** Nếu có giải pháp đơn giản hơn, hãy thẳng thắn đề xuất.
- **Dừng lại khi bối rối:** Nếu phát hiện yêu cầu mâu thuẫn hoặc thiếu thông tin, dừng lại, gọi đúng tên điểm mơ hồ và xin chỉ dẫn.

---

### 1.2. SIMPLICITY FIRST (Ưu tiên sự đơn giản tối đa)
> *"Lượng code tối thiểu giải quyết triệt để vấn đề. Tuyệt đối không suy diễn tương lai."*

- **Không tính năng thừa (No speculative features):** Không viết tính năng người dùng không yêu cầu.
- **Không trừu tượng hóa quá sớm (No over-abstraction):** Không tạo class, interface, design pattern phức tạp cho đoạn mã chỉ dùng 1 lần.
- **Không cấu hình giả định:** Không thêm tham số, config linh hoạt mà không ai cần.
- **Không bắt lỗi viển vông:** Không viết exception handling cho các kịch bản không thể xảy ra trong thực tế.
- **Thu gọn dòng:** Nếu đoạn mã 200 dòng có thể viết trong 50 dòng tinh gọn, BẮT BUỘC viết lại 50 dòng.
- **Senior Engineer Test:** *"Một kỹ sư Senior có phàn nàn rằng giải pháp này quá rườm rà không?"* $\rightarrow$ Nếu có, hãy đơn giản hóa ngay.

---

### 1.3. SURGICAL CHANGES (Chỉnh sửa chuẩn xác như dao phẫu thuật)
> *"Chỉ chạm vào những gì bắt buộc. Chỉ dọn dẹp những gì do chính mình tạo ra."*

- **Không tiện tay refactor (No drive-by refactoring):** Khi sửa một hàm hoặc một file, TUYỆT ĐỐI CẤM tự ý format lại code, đổi dấu nháy (`'` sang `"`), sửa docstring, hay sửa các đoạn code lân cận đang chạy ổn định.
- **Tôn trọng phong cách hiện có:** Tuân thủ 100% coding style hiện tại của dự án.
- **Xử lý Dead Code:** Nếu thấy code chết có sẵn từ trước không liên quan, chỉ nêu cảnh báo, KHÔNG tự ý xóa.
- **Dọn dẹp tàn dư của chính mình:** Xóa sạch các import, biến, hàm mồ côi (orphan) do chính thay đổi của bạn tạo ra.
- **Traceability Test:** Mọi dòng code thay đổi trong git diff BẮT BUỘC phải truy vết trực tiếp về yêu cầu của người dùng.

---

### 1.4. GOAL-DRIVEN EXECUTION (Thực thi theo mục tiêu có kiểm chứng)
> *"Xác định tiêu chí thành công cụ thể. Lặp cho đến khi kiểm chứng đạt 100%."*

- Chuyển đổi mệnh lệnh mơ hồ thành tiêu chí kiểm chứng:
  - Thay vì *"Sửa lỗi bug"*: Viết kịch bản test tái hiện lỗi (Verify: Fails) $\rightarrow$ Sửa code $\rightarrow$ Kiểm chứng test pass (Verify: Passes) $\rightarrow$ Kiểm chứng không hồi quy.
  - Thay vì *"Thêm validation"*: Viết test cho input lỗi $\rightarrow$ Làm cho test pass.
- Với tác vụ nhiều bước, luôn lập kế hoạch kiểm chứng 3 cột:
  ```
  1. [Bước thực hiện] ➔ Tiêu chí kiểm chứng (Verify Check)
  2. [Bước thực hiện] ➔ Tiêu chí kiểm chứng (Verify Check)
  3. [Bước thực hiện] ➔ Tiêu chí kiểm chứng (Verify Check)
  ```
- **Triệt tiêu tiêu chí mơ hồ:** Tiêu chí lỏng lẻo (*"làm cho nó chạy"*) khiến LLM lạc hướng; tiêu chí định lượng giúp LLM tự sửa sai (self-correcting loop) chính xác.

