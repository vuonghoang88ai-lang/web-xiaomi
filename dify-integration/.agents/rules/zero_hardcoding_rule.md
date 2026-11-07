---
trigger: always_on
description: "SOP 30: Tuyệt đối không fix cứng logic, phải luôn áp dụng thuật toán tương ứng."
---

# SOP 30: NGUYÊN TẮC BẤT BIẾN KHÔNG FIX CỨNG (ZERO HARDCODING)

Khi thiết kế, lập trình hoặc tinh chỉnh bất kỳ thành phần nào trong hệ thống, AI Assistant và kỹ sư BẮT BUỘC tuân thủ:

1. **CẤM HARDCODE TỪ KHÓA & NGƯỠNG:**
   - Tuyệt đối KHÔNG dùng danh sách từ khóa tĩnh `if k in text:` hoặc các ngưỡng tùy tiện như `len(words) >= 10`.
   - Mọi sự phân loại ý định phải được mô hình hóa bằng **Thuật toán Máy Trạng Thái (Finite State Machine)**, **Semantic Vector Routing** hoặc **Goal-Driven Decomposition**.

2. **CẤM TIÊM ÉP CHUỖI CƯỠNG CHẾ (Monolithic String Injection):**
   - Gateway chỉ đóng vai trò cung cấp Context sạch (Tên dự án, Domain, Ngành hàng, UVP).
   - Tuyệt đối không chèn ép các mệnh lệnh cứng làm méo mó ý định người dùng hoặc gây lặp khối định dạng trong mô hình.

3. **PHÂN BIỆT RÕ RÀNG KỲ VỌNG ĐẦU RA (Deliverable Expectation):**
   - Khi người dùng yêu cầu sản phẩm nội dung thực tế (bài viết, trang WooCommerce, landing page, kịch bản...): BẮT BUỘC xuất bản **100% nội dung hoàn chỉnh**. Tuyệt đối cấm dừng lại ở dàn ý hay bản kế hoạch hành động.
   - Khi người dùng yêu cầu bàn bạc, chiến lược: Mới kích hoạt họp bàn C-Suite và đúc kết kế hoạch.

4. **LUÔN TÌM THUẬT TOÁN TƯƠNG ỨNG:**
   - Trước mỗi giải pháp, luôn tự hỏi: *"Cách làm này có khái quát cho mọi trường hợp và mở rộng linh hoạt được không?"*. Nếu là fix cứng, phải chuyển đổi sang thuật toán tương ứng theo chuẩn `knowledge/19_sop30_zero_hardcoding_and_algorithmic_adaptation_principle.md`.

