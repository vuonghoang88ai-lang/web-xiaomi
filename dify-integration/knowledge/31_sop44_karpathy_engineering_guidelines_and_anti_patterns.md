# SOP 44: QUY TẮC KỸ NGHỆ KARPATHY & CHỐNG PHỨC TẠP HÓA (KARPATHY ENGINEERING GUIDELINES)

## 1. MỤC TIÊU & BỐI CẢNH NGUYÊN BẢN
- **Nguồn gốc:** Đúc kết từ các quan sát thực chiến của **Andrej Karpathy** (Cựu Giám đốc AI Tesla, Co-founder OpenAI) về các điểm mù cố hữu của mô hình ngôn ngữ lớn (LLM) trong kỹ nghệ phần mềm và suy luận tự động:
  > *"Các mô hình thường tự ý giả định ngầm thay cho người dùng rồi chạy theo chúng mà không kiểm tra. Chúng không biết quản lý sự bối rối của bản thân, không chủ động xin làm rõ, không nêu ra các mâu thuẫn hay đánh đổi, và không biết phản biện khi cần... Chúng rất thích phức tạp hóa code và API, làm phình to các lớp trừu tượng... triển khai một công trình cồng kềnh 1.000 dòng trong khi chỉ cần 100 dòng là xong... Chúng đôi khi tự tiện thay đổi/xóa comments và code mà chúng chưa hiểu rõ như một tác dụng phụ ngoài ý muốn."*
- **Đánh đổi cốt lõi (Tradeoff):** Bộ quy tắc này ưu tiên **Sự thận trọng và chuẩn xác hơn là tốc độ mù quáng (Caution over Speed)**.

---

## 2. BỐN NGUYÊN TẮC CỐT LÕI (THE FOUR PILLARS)

```
[1. Think Before Coding] ────> Công khai giả định ngầm, nêu rõ đánh đổi, hỏi khi bối rối.
        │
        ▼
[2. Simplicity First]    ────> Code tối thiểu giải quyết triệt để vấn đề, cấm suy diễn tương lai.
        │
        ▼
[3. Surgical Changes]    ────> Chỉ sửa đúng điểm cần sửa, cấm drive-by refactoring.
        │
        ▼
[4. Goal-Driven Loop]    ────> Định nghĩa tiêu chí kiểm chứng (Verify Check), lặp đến khi đạt 100%.
```

---

## 3. CHI TIẾT THỰC THI & BẢNG ĐỐI SOÁT ANTI-PATTERNS

### 3.1. Think Before Coding (Suy nghĩ trước khi gõ phím)
| Tình huống | ❌ Sai lầm phổ biến của LLM | ✅ Chuẩn Karpathy bắt buộc |
| :--- | :--- | :--- |
| **Yêu cầu mở rộng:** *"Thêm tính năng xuất dữ liệu"* | Tự ý viết code xuất toàn bộ database ra file JSON/CSV cố định mà không hỏi số lượng, bảo mật, định dạng. | Nêu rõ 4 giả định cần làm rõ: (1) Phạm vi dữ liệu, (2) Định dạng (JSON API hay CSV tải về), (3) Các trường nhạy cảm, (4) Khối lượng ước tính. |
| **Yêu cầu tối ưu:** *"Làm cho tìm kiếm nhanh hơn"* | Tự ý cài đặt cache Redis, connection pooling và async 300 dòng code. | Phân tích 3 khía cạnh: (1) Giảm latency (<100ms), (2) Tăng throughput chịu tải, hay (3) Cải thiện UX (hiển thị trước). Hỏi người dùng ưu tiên khía cạnh nào. |

### 3.2. Simplicity First (Đơn giản là thượng sách)
- **Quy tắc Vàng:** Code tốt nhất là đoạn code giải quyết bài toán hôm nay một cách đơn giản nhất, không giải quyết vấn đề ngày mai một cách vội vã.
- **Tiêu chuẩn cấm kỵ:**
  - 🚫 CẤM tạo Strategy Pattern, Factory, Abstract Base Class cho tác vụ chỉ có 1 trường hợp sử dụng.
  - 🚫 CẤM thêm các tham số cấu hình tùy biến linh hoạt (`config`, `options`, `handlers`) khi người dùng không yêu cầu.
  - 🚫 CẤM viết 200 dòng khi 50 dòng đã giải quyết hoàn hảo bài toán.
  - 🚫 CẤM bắt lỗi (catch exceptions) cho các kịch bản không thể xảy ra trong logic nội bộ.

### 3.3. Surgical Changes (Chỉnh sửa chuẩn xác như dao phẫu thuật)
- **Quy tắc Vàng:** Mỗi dòng thay đổi trong `git diff` phải có lý do truy vết trực tiếp về yêu cầu của người dùng.
- **Tiêu chuẩn kiểm soát thay đổi:**
  - 🚫 CẤM tiện tay đổi dấu nháy đơn `'` thành nháy kép `"` hoặc ngược lại trên các dòng không liên quan.
  - 🚫 CẤM tự ý thêm type hints, docstring hoặc sửa formatting ở những hàm không được yêu cầu chạm vào.
  - 🚫 CẤM tự ý xóa bỏ dead code hoặc code cũ của người khác trừ khi được yêu cầu rõ ràng.
  - ✅ PHẢI dọn dẹp sạch sẽ các biến, hàm, import do chính thay đổi của mình làm mồ côi (orphans).

### 3.4. Goal-Driven Execution (Lặp theo tiêu chí kiểm chứng)
- **Quy tắc Vàng:** LLM có năng lực phi thường trong việc tự sửa lỗi khi được cung cấp tiêu chí thành công rõ ràng. Đừng chỉ ra lệnh làm gì, hãy cung cấp tiêu chuẩn nghiệm thu định lượng.
- **Chuyển hóa mệnh lệnh thành mục tiêu có kiểm chứng:**
  - *"Sửa lỗi sắp xếp trùng điểm"* $\rightarrow$ Viết kịch bản test với 3 phần tử trùng điểm $\rightarrow$ Chứng minh test fail $\rightarrow$ Thay bằng hàm sort ổn định (stable sort) $\rightarrow$ Chứng minh test pass 100%.
  - *"Tích hợp tính năng mới"* $\rightarrow$ Chia thành các bước nhỏ: Mỗi bước kèm một phép thử nghiệm thu độc lập (`Verify Check: curl/test`).

---

## 4. TÍCH HỢP VÀO HỆ THỐNG MARKETING AI COPILOT
1. **Áp dụng cho AI Copilot Gateway & Reasoning Engine:**
   - Khi tiếp nhận yêu cầu từ người dùng: Nếu câu lệnh có nhiều cách diễn giải, Gateway/Copilot phải trình bày các lựa chọn thay vì tự suy diễn.
2. **Áp dụng cho Codebase Refactoring & Feature Development:**
   - Mọi thay đổi trong `agent-engine/` phải tuân thủ nghiêm ngặt tính chất phẫu thuật (Surgical Changes) và giới hạn số dòng [SOP 23](file:///home/ubuntu/michinhhang/dify-integration/AGENTS.md#L3) ($\le 300$ dòng).
3. **Bộ nhớ RAG đồng bộ:**
   - File tài liệu này được lưu trữ tại `knowledge/31_sop44_karpathy_engineering_guidelines_and_anti_patterns.md` và index trực tiếp vào Dify Dataset RAG `KB_Marketing_SOP_Standards`.

