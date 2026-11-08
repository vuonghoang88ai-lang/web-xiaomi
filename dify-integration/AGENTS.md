# WORKSPACE RULES & CODING STANDARDS (MARKETING AI INTEGRATION)

## 1. SOP 23: KIẾN TRÚC MODULAR VÀ GIỚI HẠN SỐ DÒNG
- Mọi file mã nguồn (`.py`, `.js`, `.css`) BẮT BUỘC duy trì **dưới hoặc bằng 300 dòng code**.
- Khi file chạm ngưỡng ~280 dòng, phải chủ động phân tách thành các module con chuyên biệt (Single Responsibility Principle).

## 2. SOP 30: NGUYÊN TẮC BẤT BIẾN KHÔNG FIX CỨNG (ZERO HARDCODING)
- **Tuyệt đối không fix cứng (hardcode):** Cấm dùng mảng từ khóa tĩnh `if k in text:` hoặc ngưỡng số học tùy tiện `len(words) >= 10` để điều khiển luồng hệ thống.
- **Luôn tìm thuật toán tương ứng:** 
  - Mọi bài toán phân loại ý định phải giải quyết bằng: **Máy Trạng Thái Thuật Toán (Finite State Machine)**, **Semantic Vector Routing** hoặc **Goal-Driven Decomposition**.
  - Gateway chỉ cung cấp Context sạch; quyền suy luận và điều hướng do AI Core đảm nhiệm.
  - Phân biệt rành mạch giữa *Yêu cầu Chiến lược (Mode Strategy $\rightarrow$ Action Plan)* và *Yêu cầu Sản phẩm Cuối (Mode Deliverable $\rightarrow$ 100% Nội dung hoàn chỉnh, cấm dừng lại ở dàn ý)*.
- Chi tiết xem tại: [`knowledge/19_sop30_zero_hardcoding_and_algorithmic_adaptation_principle.md`](file:///home/ubuntu/michinhhang/dify-integration/knowledge/19_sop30_zero_hardcoding_and_algorithmic_adaptation_principle.md).

## 3. SOP 31: QUẢN TRỊ ĐA PHIÊN CHAT & VÒNG ĐỜI NGỮ CẢNH (GEMINI-STYLE)
- Phân tách phiên chat theo từng `project_id`, bảo toàn lịch sử và cô lập dữ liệu.
- Giám sát độ dài ngữ cảnh (`message_count >= 30` cảnh báo mở phiên mới) để ngăn chặn lỗi Timeout 300s của mô hình LLM.
- Chi tiết xem tại: [`knowledge/20_sop31_multi_session_history_and_gemini_style_chat_architecture.md`](file:///home/ubuntu/michinhhang/dify-integration/knowledge/20_sop31_multi_session_history_and_gemini_style_chat_architecture.md).

## 4. SOP 32: ĐỒNG BỘ ĐA KHO TRI THỨC RAG HAI CHIỀU (DUAL-STREAM RAG)
- Mọi Agent Copilot phải liên kết song hành ít nhất 2 Dataset trong `dataset_configs`:
  1. Kho Tri thức Dự án (`KB_{Client}_{Project}`): dữ liệu 10 đối thủ SERP, giá bán, khoảng trống thị trường.
  2. Kho Quy chuẩn SOP (`KB_Marketing_SOP_Standards`): tiêu chuẩn SOP 12, UECS, SOP 26, SOP 30.
- Chi tiết xem tại: [`knowledge/21_sop32_dual_stream_rag_synchronization_and_knowledge_extraction.md`](file:///home/ubuntu/michinhhang/dify-integration/knowledge/21_sop32_dual_stream_rag_synchronization_and_knowledge_extraction.md).

## 5. SOP 33: NÉN NGỮ CẢNH & TÁCH RỜI SẢN PHẨM (GEMINI-STYLE CONTEXT COMPACTION)
- Giữ người dùng trong **1 phiên chat duy nhất**, tuyệt đối không tự ý ngắt phiên làm gián đoạn trải nghiệm.
- Áp dụng thuật toán 3 lớp: Tách rời sản phẩm (Artifact Pointer Decoupling), Cửa sổ trượt động ($K=6$ messages) và Trần token an toàn ($\le 4.000$ tokens) để triệt tiêu lỗi Timeout 300s của LLM.
- Chi tiết xem tại: [`knowledge/22_sop33_gemini_context_compaction_and_artifact_decoupling.md`](file:///home/ubuntu/michinhhang/dify-integration/knowledge/22_sop33_gemini_context_compaction_and_artifact_decoupling.md).

## 6. SOP 34: KIẾN TRÚC LAI 3 TẦNG TRI THỨC & TIẾP ĐỊA INTERNET (TRI-STREAM HYBRID RAG)
- Mọi Agent Copilot kết hợp song hành 3 tầng dữ liệu: (1) RAG Nội bộ, (2) SERP Trinh sát Đối thủ, và (3) Tiếp địa Internet Thời gian thực (`liveMarketIntelligence`).
- Tuân thủ nguyên tắc Bắt Cầu Thương Mại (Commercial Synthesizer: Fact $\rightarrow$ Customer Insight $\rightarrow$ Action Plan) và Tự động Thu hoạch Tri thức Ngầm (Autonomous Continuous Harvester).
- Chi tiết xem tại: [`knowledge/25_sop34_tri_stream_hybrid_rag_and_live_internet_grounding.md`](file:///home/ubuntu/michinhhang/dify-integration/knowledge/25_sop34_tri_stream_hybrid_rag_and_live_internet_grounding.md).

## 7. SOP 35 & 36: NGÂN SÁCH CÔNG CỤ ĐỘNG & GIAO DIỆN TÁCH ĐÔI ARTIFACT CANVAS
- **SOP 35:** Payload trả về từ các công cụ trinh sát đối thủ và Live Internet bắt buộc đi qua thuật toán tinh lọc `condense_competitor_data` để đảm bảo ngân sách $\le 1.500$ tokens, triệt tiêu hoàn toàn lỗi Timeout 300s.
- **SOP 36:** Khi xuất bản bài viết dài (`> 1.200 ký tự` có cấu trúc đề mục), tự động kích hoạt Dual-Pane Artifact Canvas (tỷ lệ 45% Chat - 55% Canvas) cho phép xem trước WYSIWYG, chỉnh sửa trực tiếp (Live Edit) và gợi ý nút hành động tiếp theo theo ma trận ngữ cảnh MDP.
- Chi tiết xem tại: [`knowledge/26_sop35_dynamic_tool_budgeting_and_artifact_canvas.md`](file:///home/ubuntu/michinhhang/dify-integration/knowledge/26_sop35_dynamic_tool_budgeting_and_artifact_canvas.md).

## 8. SOP 37 & 38: TỐI ƯU PROMPT ĐẦY ĐỦ & ĐỐI SOÁT GIÁ KHO THỰC TẾ
- **SOP 37:** Người dùng nhấn nút `✨ Tối ưu 10x` hoặc phím tắt `Ctrl + E` để tự động làm giàu prompt ngắn thành câu lệnh chuyên sâu 10x kèm SKU kho và cấu trúc WooCommerce.
- **SOP 38:** Tự động đối soát bảng giá, thời gian bảo hành và thông số kỹ thuật bài viết trên Canvas với kho hàng thực tế của khách hàng, gắn huy hiệu `[✅ BẢO CHỨNG 100% THÔNG SỐ KHO]` hoặc cảnh báo sai lệch.
- Chi tiết xem tại: [`knowledge/27_sop37_38_prompt_expansion_and_commercial_guard.md`](file:///home/ubuntu/michinhhang/dify-integration/knowledge/27_sop37_38_prompt_expansion_and_commercial_guard.md).

## 9. SOP 41: NGƯỠNG QUYẾT ĐỊNH HỌP ĐỘNG (DYNAMIC MEETING DECISION MATRIX)
- **Tier 1 (Direct Fast-Path):** Mọi câu hỏi tra cứu thông thường (thời tiết, giá bán, thông số kho) TUYỆT ĐỐI CẤM mở khối họp `:::boardroom-debate`. Phải trả lời thẳng vào thông tin trong 1-3 đoạn văn ngắn gọn (< 5 giây).
- **Tier 2 (Deliverable Mode):** Yêu cầu bài viết/WooCommerce cấm họp toàn thể C-Suite; xuất bản 100% nội dung ngay.
- **Tier 3 (Boardroom Mode):** Chỉ họp khi người dùng gõ `/strategy` hoặc yêu cầu hoạch định chiến lược đa mục tiêu.
- Chi tiết xem tại: [`knowledge/28_sop41_dynamic_meeting_decision_matrix.md`](file:///home/ubuntu/michinhhang/dify-integration/knowledge/28_sop41_dynamic_meeting_decision_matrix.md).

## 10. SOP 42: GEMINI GROUNDING, QUERY REFORMULATION & MINH BẠCH TRÍCH DẪN (INLINE CITATIONS)
- **Query Reformulation:** Tự động bóc tách thực thể địa lý, khung thời gian và danh mục sản phẩm từ câu hỏi ngôn ngữ tự nhiên thành Search Query chuẩn mực.
- **Tiếp địa đa nguồn (Multi-Source Grounding):** Trạm vệ tinh khí tượng Open-Meteo và Bảng giá kho niêm yết TongKhoMi.
- **Inline Citations & Footnotes:** Đánh dấu các số liệu thực tế bằng số trích dẫn `[1]`, `[2]` và liệt kê khối `📚 Nguồn Dữ Liệu Thực Tế (Grounding Sources)` minh bạch ở cuối câu trả lời.
- Chi tiết xem tại: [`knowledge/29_sop42_gemini_grounding_citations_and_query_reformulation.md`](file:///home/ubuntu/michinhhang/dify-integration/knowledge/29_sop42_gemini_grounding_citations_and_query_reformulation.md).

## 11. SOP 43: THU HOẠCH TRI THỨC VẬN HÀNH & HIỆU CHỈNH COPILOT (CONTINUOUS HARVESTING)
- **Rà soát hội thoại định kỳ:** Bóc tách log thực chiến từ PostgreSQL & Dify API để phát hiện các sai lệch hành vi: Over-meeting, Factual Hallucination, Cross-Domain Leakage, Rigid Hardcoding.
- **Tự động đóng gói & Index RAG:** Định kỳ tổng hợp bài học kinh nghiệm và các quy chuẩn mới vào kho tri thức `KB_Marketing_SOP_Standards` để tái đào tạo và bảo chứng chất lượng suy luận của Copilot.
- Chi tiết xem tại: [`knowledge/30_sop43_continuous_knowledge_harvesting_and_drift_correction.md`](file:///home/ubuntu/michinhhang/dify-integration/knowledge/30_sop43_continuous_knowledge_harvesting_and_drift_correction.md).

## 12. SOP 44: BỘ QUY TẮC KỸ NGHỆ KARPATHY (KARPATHY GUIDELINES)
- **Think Before Coding:** Không tự ý giả định ngầm; công khai các phương án và đánh đổi (tradeoffs); hỏi làm rõ khi bối rối; sẵn sàng phản biện khi có giải pháp đơn giản hơn.
- **Simplicity First:** Viết lượng code tối thiểu giải quyết triệt để vấn đề; tuyệt đối cấm suy diễn tính năng tương lai; cấm trừu tượng hóa thừa thãi (Strategy, Factory) cho mã chỉ dùng 1 lần; nếu 200 dòng viết lại được thành 50 dòng thì phải viết lại.
- **Surgical Changes:** Chỉ sửa đúng những dòng phục vụ trực tiếp cho yêu cầu; tuyệt đối cấm "tiện tay cải tiến" (drive-by refactoring), đổi dấu nháy hay reformat code không liên quan; dọn sạch các biến/import mồ côi do chính mình tạo ra.
- **Goal-Driven Execution:** Chuyển hóa mệnh lệnh mơ hồ thành tiêu chí thành công có thể kiểm chứng định lượng (`Step ➔ Verify Check`); lặp kiểm thử độc lập cho đến khi đạt 100%.
- Chi tiết xem tại: [`knowledge/31_sop44_karpathy_engineering_guidelines_and_anti_patterns.md`](file:///home/ubuntu/michinhhang/dify-integration/knowledge/31_sop44_karpathy_engineering_guidelines_and_anti_patterns.md).

## 13. SOP 45: KHỬ TRÙNG TỪ VỰNG ĐA NGHĨA & VỆ SINH NGỮ CẢNH (CONTEXT HYGIENE)
- **Bóc tách Intent thuần túy:** Phân loại ý định chỉ thực hiện trên `pure_user_query`, tuyệt đối không để các thẻ Context Injection hệ thống làm méo mó mục đích thực tế.
- **Khử trùng khái niệm đa nghĩa (Zero Hardcoding):** Cấm dùng từ đơn lẻ đa nghĩa (`lạnh`) rồi fix cứng danh sách loại trừ (`?<!tủ`); phải dùng cụm khái niệm khí tượng (`trời lạnh`, `thời tiết`). Cấm hardcode tên sản phẩm (`tivi`, `tủ lạnh`) vào router; sử dụng 100% so khớp ngữ nghĩa động từ `brand_profile` của dự án.
- **Vệ sinh toàn diện:** Dọn sạch các bản ghi rỗng `length(answer) == 0` tiền trạm và lọc sạch mọi thẻ suy luận LLM (`<｜DSML｜`, `<function_calls>`) trước khi xuất bản.
- Chi tiết xem tại: [`knowledge/32_sop45_semantic_intent_disambiguation_and_context_hygiene.md`](file:///home/ubuntu/michinhhang/dify-integration/knowledge/32_sop45_semantic_intent_disambiguation_and_context_hygiene.md).

## 14. SOP 46: BỘ QUY CHUẨN GIAO DIỆN DI ĐỘNG & GEMINI/CHATGPT MOBILE PARITY
- **3 Điểm Chạm Header Mobile:** Tối đa 3 khối chức năng trên màn hình $\le 768px$: Hamburger (trái), Title thương hiệu căn giữa (max 150px, ellipsis), Tác vụ nhanh (phải: 🕒 Lịch sử + ➕ Tạo mới). Cấm nhồi nhét Language Switcher và Subscription Badge vào Topbar di động.
- **Dynamic Viewport (`100dvh`) & Flexbox Chain:** Triệt tiêu lỗi `100vh` bị phím ảo Android cắt ngang. Mọi container cha con dùng `height: 100dvh; flex: 1 1 0%; min-height: 0;`. Khung gõ lệnh mang `flex-shrink: 0; padding-bottom: calc(14px + env(safe-area-inset-bottom, 0px))` luôn ghim nổi trên màn hình.
- **Floating Capsule Input & Chống Safari Auto-Zoom:** Bo tròn 26px, kính mờ Glassmorphism, nút gửi tròn 36px, textarea cố định `font-size: 16px` để vô hiệu hóa auto-zoom của iOS.
- **Cache-Busting Versioning:** Nâng timestamp `?v=YYYYMMDD_HHMM` trong các thẻ `<link rel="stylesheet">` của file `index.html` mỗi khi cập nhật CSS để điện thoại người dùng nhận diện ngay mã nguồn mới mà không bị dính cache cũ.
- Chi tiết xem tại: [`knowledge/33_sop46_mobile_responsive_ui_and_chat_parity_standard.md`](file:///home/ubuntu/michinhhang/dify-integration/knowledge/33_sop46_mobile_responsive_ui_and_chat_parity_standard.md).

## 15. SOP 47: BẢO MẬT MẠNG LAI TAILSCALE/CGNAT & KHÔNG RÒ RỈ NGÀNH HÀNG
- **Bảo mật mạng riêng ảo CGNAT:** Mở rộng dải IP tin cậy `100.` cho mạng Tailscale Mesh VPN và chuẩn hóa toàn bộ request từ giao diện khách hàng về tiền tố `/api/v1/portal/*`, triệt tiêu hoàn toàn lỗi HTTP 401 khi không mang `x-engine-api-key`.
- **Khử trùng rò rỉ ngữ nghĩa chéo (Cross-Domain Leakage):** Tuyệt đối cấm đưa tên đối thủ bán lẻ điện máy (`dienmayxanh.com`, `cellphones.com.vn`) hoặc kho hàng Xiaomi vào các dự án đọc truyện online hay dịch vụ khác. Dữ liệu fallback phải bóc tách 100% từ chính `project_competitors` của dự án đó.
- Chi tiết xem tại: [`knowledge/34_sop47_tailscale_cgnat_security_and_domain_agnostic_autofix.md`](file:///home/ubuntu/michinhhang/dify-integration/knowledge/34_sop47_tailscale_cgnat_security_and_domain_agnostic_autofix.md).

## 16. SOP 48: BẮT TAY TỰ ĐỘNG 2 CHIỀU & BẢO MẬT KHÓA CỨNG BẢN QUYỀN TÊN MIỀN
- **Zero-Manual Two-Way Handshake:** Khi cài đặt plugin và kết nối, plugin tự động đẩy API Secret Key (`obc_*`), API URL, REST endpoint và SEO provider sang Portal. Portal tự động phản hồi lại GSC Engine URL, Tenant ID, Project ID, Engine API Key để WordPress tự lưu vào `wp_options`. Triệt tiêu hoàn toàn thao tác copy - dán thủ công.
- **Khóa Cứng Tên Miền Độc Lập (Trial Anti-Abuse Registry):** Khóa mốc thời gian dùng thử 7 ngày theo FQDN Domain vào bảng `domain_license_registry` tại PostgreSQL. Ngăn chặn triệt để hành vi xóa plugin cài lại hoặc xóa dự án tạo lại trên Portal để reset chu kỳ 7 ngày miễn phí.
- Chi tiết xem tại: [`knowledge/35_sop48_zero_manual_handshake_and_domain_anti_abuse_registry.md`](file:///home/ubuntu/michinhhang/dify-integration/knowledge/35_sop48_zero_manual_handshake_and_domain_anti_abuse_registry.md).

## 17. SOP 49: BỘ TRANG TÍN NHIỆM E-E-A-T ĐA NGÀNH NGHỀ & TỰ ĐỘNG GẮN CHÂN TRANG
- **Zero Hardcoding Ngành Nghề:** Mô hình hóa Archetype đa ngành (`content_media`, `service_health_b2b`, `commerce_retail`). Tuyệt đối cấm áp đặt trang bảo hành / kho hàng cho website đọc truyện, tin tức hay dịch vụ.
- **5 Trang Tín Nhiệm Chuẩn E-E-A-T Đa Ngành:** Website đọc truyện giải trí sở hữu bộ trang chuẩn hóa: *Điều khoản sử dụng, Chính sách bảo mật độc giả, Tuyên bố bản quyền & DMCA, Giới thiệu nền tảng, Liên hệ & Báo lỗi chương*.
- **Tự Động Gắn Chân Trang (Zero-Manual Footer):** Tự động liên kết vào theme footer nav menu và kích hoạt Footer Trust Bar qua `wp_footer`. Triệt tiêu hoàn toàn thao tác thủ công "Vào Appearance ➔ Menu ➔ Footer Menu".
- Chi tiết xem tại: [`knowledge/36_sop49_multi_industry_eeat_trust_architecture_and_auto_footer.md`](file:///home/ubuntu/michinhhang/dify-integration/knowledge/36_sop49_multi_industry_eeat_trust_architecture_and_auto_footer.md).

## 18. SOP 50: TỰ ĐỘNG THÁM SÁT CẤU TRÚC WEB & CỔNG TƯƠNG TÁC ĐA PLUGIN TOÀN NĂNG
- **Single Universal Copilot Engine:** Cấm tách bot rời rạc cho Blog vs WooCommerce. Dùng 1 Copilot duy nhất kết hợp Máy Trạng Thái Động để xuất bản sang đúng CPT (`post`, `product`, `chapter`) căn cứ theo `site_topology` bóc tách từ web.
- **Autonomous Site Topology Discovery:** Omni Bridge Connector (`class-introspection-trait.php`) tự động quét toàn bộ CPTs, Taxonomies, Active Plugins, hệ sinh thái ERP và REST endpoints, đẩy về lưu trữ tại `projects.site_topology JSONB` ở PostgreSQL.
- **Universal Plugin Interoperability Gateway:** Cổng tương tác `/wp-json/omni-bridge/v1/interop/execute` và API Gateway phía Server cho phép AI kích hoạt Action hooks, Filter hooks hoặc các hàm PHP công khai của bất kỳ plugin nào (ERP, kho hàng, SEO) an toàn qua xác thực hai chiều.
- Chi tiết xem tại: [`knowledge/37_sop50_autonomous_site_topology_and_universal_plugin_interoperability.md`](file:///home/ubuntu/michinhhang/dify-integration/knowledge/37_sop50_autonomous_site_topology_and_universal_plugin_interoperability.md).

## 19. SOP 51: CHUẨN MỰC THẨM MỸ TYPOGRAPHY THANH MẢNH & TỐI GIẢN
- **Khử Hiện Tượng Dãn Nét Nền Tối (Dark-Mode Anti-Dilation):** Kích hoạt khử răng cưa phần cứng `-webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; text-rendering: optimizeLegibility;` để triệt tiêu hiện tượng nét chữ sáng bị dãn nở quang học trên nền tối.
- **Native Light Weights & Typographic Hierarchy:** Nạp Google Font `Inter:wght@300;400;500;600;700`. Văn bản chat và Canvas sử dụng `font-weight: 350; font-size: 13.5px; line-height: 1.7; letter-spacing: -0.005em`. Hạ tông các cấp tiêu đề (`h1`: 600, `h2`: 550, `h3`: 500, `strong`: 550) và nút bấm (`400-450`) để tạo vẻ thanh thoát, hiện đại chuẩn Gemini/ChatGPT.
- **Kỷ Luật Cache-Busting:** Cập nhật query timestamp `?v=YYYYMMDD_HHMM` trên thẻ CSS ở `index.html` mỗi lần hiệu chỉnh để mọi thiết bị người dùng nhận diện ngay giao diện mới.
- Chi tiết xem tại: [`knowledge/38_sop51_sleek_typography_and_dark_mode_anti_dilation_standard.md`](file:///home/ubuntu/michinhhang/dify-integration/knowledge/38_sop51_sleek_typography_and_dark_mode_anti_dilation_standard.md).

## 20. SOP 52: THUẬT TOÁN THẨM MỸ TỰ THÍCH ỨNG & KHỬ TRÙNG LẶP NGỮ NGHĨA DOM
- **Zero Hardcoding Thẩm Mỹ:** Cấm fix cứng class CSS của theme (`.sab-footer-menu`) hoặc mã màu tĩnh (`#f8fafc`).
- **Thuật toán Khử Trùng Lặp Ngữ Nghĩa DOM:** Quét cây DOM `footer`, đếm độ giao cắt tập hợp liên kết với `legalSet` ($\ge 3$ URLs trùng khớp) để tự động ẩn cụm menu thô trùng lặp mà không cần biết class hay id của Theme.
- **Thẩm Mỹ Tự Thích Ứng (Adaptive Frosted Glass):** Sử dụng `color-mix(in srgb, currentColor 4%, transparent)` kết hợp `backdrop-filter: blur(8px)` để tự động tiệp màu 100% với mọi Theme ở cả Light Mode lẫn Dark Mode.
- **Dọn Dẹp Tàn Dư Ngành Hàng Lạc Quẻ:** Tự động loại bỏ các trang bán hàng rác (`chinh-sach-bao-hanh`, `chinh-sach-doi-tra`) khỏi website đọc truyện chữ qua `footer nav { display: none !important; }` và `wp_trash_post()`.
- **Nhận Diện Site Kit & Bắt Tay Tự Động:** Sửa lỗi kiểm tra `googlesitekit_credentials` (dạng chuỗi mã hóa string), tự động đẩy `Tenant ID`, `Project ID`, `GSC Engine URL` từ Portal vào WordPress qua `/interop/execute`.
- Chi tiết xem tại: [`knowledge/39_sop52_adaptive_aesthetic_engine_and_semantic_dom_deduplication.md`](file:///home/ubuntu/michinhhang/dify-integration/knowledge/39_sop52_adaptive_aesthetic_engine_and_semantic_dom_deduplication.md).

## 21. SOP 53: HỆ ĐIỀU HÀNH CAN THIỆP CMS TOÀN NĂNG & VÒNG ĐỜI CHIẾN DỊCH KHUYẾN MÃI TỰ ĐỘNG
- **6 Phân Vùng Can Thiệp CMS Toàn Năng (Headless CMS Operating Layer):** (1) Head & Schema Metadata, (2) Thân trang & Quản trị Nội dung Đa CPT, (3) Kiến trúc Điều hướng Silo, (4) Chân trang & Bộ Tín Nhiệm E-E-A-T, (5) Cổng Tương Tác Đa Plugin Toàn Năng, (6) Kỹ Thuật & Hiệu Năng.
- **Vòng Đời Chiến Dịch Khuyến Mãi Tự Động (Automated Campaign Lifecycle Engine):** Người dùng chỉ cần chat ý tưởng với CMO AI Copilot $\rightarrow$ CMO sáng tạo kịch bản & xuất bản bài viết $\rightarrow$ AI Visual Artisan tự thiết kế Banner đồ họa (chuẩn tỉ lệ vàng WebP $\le 120$ KB) $\rightarrow$ Omni Bridge tự động treo banner lên Slider/Header qua REST/interop $\rightarrow$ Auto-Expiration Scheduler tự động gỡ banner khi hết hạn.
- Chi tiết xem tại: [`knowledge/40_sop53_headless_cms_campaign_lifecycle_and_ai_visual_artisan.md`](file:///home/ubuntu/michinhhang/dify-integration/knowledge/40_sop53_headless_cms_campaign_lifecycle_and_ai_visual_artisan.md).

## 22. SOP 54: CHUẨN MỰC BẢO TOÀN CHUỖI FLEXBOX & TRIỆT TIÊU ĐÓNG BĂNG GIAO DIỆN CUỘN
- **Bảo Toàn Chuỗi Flexbox Với `display: contents;`:** Khi nạp động partials qua container trung gian (như `#portalViewsContainer`), BẮT BUỘC áp dụng `display: contents;` để bỏ qua hộp bọc tĩnh, giữ nguyên chuỗi `flex: 1 1 0%; min-height: 0;` kế thừa từ layout cha `.main-content`. Triệt tiêu hoàn toàn lỗi vỡ layout làm dãn nở chiều cao và bị cắt cụt dưới đáy màn hình.
- **Kỷ Luật Cuộn Đơn Nhất (Single Scroll Container):** Thiết lập `.tab-pane` mang `overflow: hidden; height: 100%;`. Toàn bộ trách nhiệm cuộn nội dung được giao duy nhất cho `.view-content-pane` và `#chatFeed` kèm `overscroll-behavior-y: contain;` và `-webkit-overflow-scrolling: touch;`. Cấm lồng hai cấp `overflow-y: auto` gây xung đột giữ sự kiện cuộn.
- **Khóa Cố Định Header & Trần An Toàn Cho Modal:** Bắt buộc `.topbar { flex-shrink: 0; }` để không bị xẹp và `.modal-card { max-height: 90dvh; overflow-y: auto; }` đảm bảo hộp thoại pop-up luôn cuộn được trên mọi màn hình.
- Chi tiết xem tại: [`knowledge/41_sop54_unbroken_flexbox_chain_and_zero_freeze_scroll_standard.md`](file:///home/ubuntu/michinhhang/dify-integration/knowledge/41_sop54_unbroken_flexbox_chain_and_zero_freeze_scroll_standard.md).

## 23. SOP 55: KIẾN TRÚC XUẤT BẢN HỘI THOẠI TRỰC TIẾP & BỘ ĐIỀU KHIỂN CHAT-TO-CMS
- **Lệnh Nhanh & Nhận Diện Ý Định Hội Thoại:** Phím tắt `/publish` trên thanh phím tắt chat và tự động nhận diện ý định ngôn ngữ tự nhiên (*"đăng bài"*, *"xuất bản lên web"*, *"publish to wordpress"*).
- **Bộ Điều Khiển Xuất Bản Đa Năng Tự Thích Ứng:** Tự động trích xuất bài viết gần nhất trong feed, làm sạch thẻ DSML/monologue/thẻ họp, nạp động danh mục CPTs (`post`, `chapter`, `product`) và taxonomies từ `site_topology` của website mà không hardcode.
- **Thẻ Kết Quả Trực Quan Sau Đăng (Post-Publish Action Card):** Trả về thẻ kết quả xanh ngay trong chat feed kèm nút xem trực tiếp Permalink trên web, nút mở trang sửa bài trong WP Admin, và trạng thái IndexNow ping Google/Bing.
- Chi tiết xem tại: [`knowledge/42_sop55_conversational_chat_to_cms_publishing_architecture.md`](file:///home/ubuntu/michinhhang/dify-integration/knowledge/42_sop55_conversational_chat_to_cms_publishing_architecture.md).

## 24. SOP 56: ĐỐI SOÁT HEADING TỰ ĐỘNG & BẢO CHỨNG DUY NHẤT 1 THẺ H1
- **Tagline-First Reconciliation:** Nhận diện nguồn sinh thẻ Heading trang chủ. Nếu theme WordPress đã có thẻ H1 (thường từ `bloginfo('description')`), tự động cập nhật từ khóa SEO vào `blogdescription` của WordPress và thiết lập `homepage_h1 = ""` trong Omni Bridge. Tuyệt đối cấm tiêm thêm H1 thứ 2 gây lỗi trùng lặp.
- **Dynamic Heading UI State:** Nút heading chuyển trạng thái nhạy bén: `⚡ Chuẩn hóa H1` khi H1 != 1; chuyển thành `💬 Tinh chỉnh H1` khi H1 == 1 đạt chuẩn 100/100.
- Chi tiết xem tại: [`knowledge/43_sop56_autonomous_heading_reconciliation_and_zero_duplicate_h1.md`](file:///home/ubuntu/michinhhang/dify-integration/knowledge/43_sop56_autonomous_heading_reconciliation_and_zero_duplicate_h1.md).

## 25. SOP 57: KIỂM TOÁN THỜI GIAN THỰC MIỄN NHIỄM CACHE & XÓA BỘ NHỚ ĐỆM TỨC THÌ
- **Cache-Immune Crawling:** Bộ thu thập kiểm toán On-page (`serp_site_inspector.py`) tự động chèn timestamp query `?_omni_audit={timestamp}` và anti-cache headers (`no-cache, no-store, must-revalidate`) để vượt qua LiteSpeed Cache, Nginx Microcache và Cloudflare CDN, luôn đọc HTML tươi mới 100%.
- **Active Purge Dispatcher:** Tự động gọi hook xóa cache qua Omni Bridge interop (LiteSpeed `litespeed_purge_all`, WP Rocket, W3TC) ngay sau khi áp dụng thay đổi On-page hoặc xuất bản bài viết.
- Chi tiết xem tại: [`knowledge/44_sop57_cache_immune_realtime_audit_and_instant_purge.md`](file:///home/ubuntu/michinhhang/dify-integration/knowledge/44_sop57_cache_immune_realtime_audit_and_instant_purge.md).

## 26. SOP 58: KHÓA BẤT BIẾN TRANG TÍN NHIỆM & KHỬ TỪ KHÓA MÁY MÓC E-E-A-T
- **Immutability Lock Guard (`eeat_locked`):** Khóa bất biến 5 trang pháp lý E-E-A-T một khi đã khởi tạo hoặc chỉnh sửa thủ công. Tuyệt đối cấm xóa bài viết viết lại khi người dùng bấm tối ưu On-page ở các lần tiếp theo (`$force_overwrite` guard).
- **Anti-Robotic Stylometry:** Lọc sạch mọi biệt ngữ kỹ thuật và huy hiệu tự xưng giả tạo ("Chuẩn E-E-A-T", "Bảo chứng chất lượng Google"). Giữ tiêu đề thuần Việt tự nhiên và phong văn biên tập viên chuyên nghiệp.
- Chi tiết xem tại: [`knowledge/45_sop58_immutability_lock_guard_and_anti_robotic_stylometry.md`](file:///home/ubuntu/michinhhang/dify-integration/knowledge/45_sop58_immutability_lock_guard_and_anti_robotic_stylometry.md).

## 27. SOP 59: ĐỒNG BỘ HÀNH ĐỘNG NGỮ CẢNH HAI CHIỀU & BẢO CHỨNG NHẤT QUÁN GIAO DIỆN
- **Exact Label Parity:** Văn bản hướng dẫn trong chat gọi tên nút nào (ví dụ: `⚡ Tự Động Áp Dụng Heading & On-Page Lên WordPress`) thì nút hành động dưới tin nhắn và thanh tác vụ nhanh BẮT BUỘC mang chính xác nhãn đó 100%.
- **Domain Agnostic Action Synthesis:** Tổng hợp động các gợi ý hành động tiếp theo theo mô hình MDP từ `brand_profile` và dữ liệu đối thủ thực tế của dự án, triệt tiêu hoàn toàn rò rỉ ngữ cảnh chéo (không hiển thị điện máy cho website đọc truyện).
- **Bi-directional Bridge:** Nút hành động trực tiếp tại từng thẻ On-page (Meta Title, Description, Heading) trong trang Báo Cáo Web Mục Tiêu kết nối thông suốt hai chiều với luồng chat CMO Copilot.
- Chi tiết xem tại: [`knowledge/46_sop59_bidirectional_contextual_actions_and_ui_parity.md`](file:///home/ubuntu/michinhhang/dify-integration/knowledge/46_sop59_bidirectional_contextual_actions_and_ui_parity.md).

## 28. SOP 60: PHÂN CẤP TỪ KHÓA ĐỘNG THEO ARCHETYPE & THUẬT TOÁN KHỬ TRÙNG LẶP TỪ TỐ
- **Thuật Toán `deduplicate_phrase`:** Khử triệt để hiện tượng lặp từ tố/thương hiệu dính liền hoặc viết rời (`Truyện 360 Truyen360...` $\rightarrow$ `Truyện 360`), đảm bảo bộ từ khóa tự nhiên 100%.
- **Phân Cấp 4 Tầng Động Theo Archetype:** Tự động điều chỉnh nhãn 4 tầng từ khóa trên UI dựa trên `archetype` (`content_media`: Thể loại, Tuyển tập, Dòng thịnh hành, Ý định độc giả; `service_health_b2b`: Dịch vụ, Gói chuyên sâu, Liệu trình, Đặt lịch; `commerce_retail`: Ngành hàng, Phân khúc, Model, Giá kho).
- **Fallback Đa Đối Thủ SERP & Phân Giải Thương Hiệu:** Tự động gom danh mục từ toàn bộ tập đối thủ SERP trong CSDL khi đối thủ hạt giống chưa bóc tách xong categories, đồng thời chuẩn hóa hiển thị thương hiệu kiên cường tránh fallback "Chưa đặt".
- Chi tiết xem tại: [`knowledge/47_sop60_dynamic_archetype_keyword_taxonomy_and_token_deduplication.md`](file:///home/ubuntu/michinhhang/dify-integration/knowledge/47_sop60_dynamic_archetype_keyword_taxonomy_and_token_deduplication.md).

## 29. SOP 61: COCKPIT TỐI ƯU TẠI CHỖ 1-CLICK & TÁCH RỜI TRÁCH NHIỆM CMO COPILOT
- **In-Place 1-Click Remediation:** Tab *Báo Cáo Web Mục Tiêu* đóng vai trò Cockpit kỹ thuật trực tiếp. Mọi thông số (Meta Title, Description, H1/H2, Silo, E-E-A-T) tối ưu và lưu trực tiếp lên WordPress tại chỗ mà KHÔNG chuyển tab sang Chat làm loãng ngữ cảnh.
- **Dual-Track Decoupling:** Giữ khung chat CMO sạch sẽ 100% (Context Hygiene - SOP 45) chuyên tâm cho 3 nhiệm vụ cốt lõi: Cố vấn chiến lược, Sản xuất bài viết chuyên sâu 2000+ từ (SOP 12), và Lệnh xuất bản `/publish`.
- **Interactive 5-Step Plan:** Từng bước trong kế hoạch tối ưu On-page mang badge trạng thái trực quan và nút hành động riêng (`⚡ Sửa tại chỗ`, `⚡ Chuẩn hóa 1 H1`, `⚡ Đồng bộ Silo`, `👁️ Xem 5 trang`), chỉ riêng Bước 5 mới dẫn vào Chat CMO (`✍️ Viết Bài Với CMO`).
- Chi tiết xem tại: [`knowledge/48_sop61_in_place_1click_optimization_cockpit_and_cmo_decoupling.md`](file:///home/ubuntu/michinhhang/dify-integration/knowledge/48_sop61_in_place_1click_optimization_cockpit_and_cmo_decoupling.md).

## 30. SOP 62: CHUẨN HÓA URL TỰ ĐỘNG KHỬ LỖI GÕ PHÍM & KẾ THỪA NGỮ CẢNH ĐA TẦNG
- **Zero-Protocol Frictionless Input:** Người dùng không cần nhập `https://` hay `http://`. Tự động nhận diện TLD công cộng (`.vn`, `.com`...) $\rightarrow$ `https://`; địa chỉ IP nội bộ / Tailscale CGNAT (`100.122...:8080`, `localhost`) $\rightarrow$ `http://`.
- **Typo-Tolerant Normalization:** Tự động sửa sạch mọi lỗi gõ sai protocol (`htttps://`, `htpps://`, `htt://`, khoảng trắng trước TLD `mihanoi vn`). Thay thế toàn bộ `<input type="url">` thành `type="text" inputmode="url"` để trình duyệt không chặn người dùng.
- **Multi-Layer Context Inheritance:** Triệt tiêu hoàn toàn fallback chung chung `"top website uy tin"`. Kế thừa 4 tầng ngữ cảnh liên hoàn (Đối thủ mẫu $\rightarrow$ Silo/H2/Title Web đích $\rightarrow$ Mô tả kinh doanh $\rightarrow$ Thực thể tên dự án) để đảm bảo 100 đối thủ Google SERP luôn bám sát 100% ngành hàng thực tế.
- Chi tiết xem tại: [`knowledge/49_sop62_zero_protocol_auto_detection_and_multi_layer_context_inheritance.md`](file:///home/ubuntu/michinhhang/dify-integration/knowledge/49_sop62_zero_protocol_auto_detection_and_multi_layer_context_inheritance.md).

## 31. SOP 63: MA TRẬN TỔ HỢP NGỮ NGHĨA B2C, TỈA NHÁNH HẬU TỐ NGỮ PHÁP & KHÓA BẢO TOÀN ĐA TRƯỜNG
- **Triệt tiêu tư duy "Chủ kho / Bán buôn":** Chuyển dịch 100% định hướng On-page và bài viết sang Ma trận Tâm lý Tiêu dùng B2C (Bảo hành 24T, Trả góp 0%, Giao hỏa tốc 2H, Lắp đặt tận nhà, Đồng kiểm khi nhận), cấm sa đà vào "Tổng kho, Giá sỉ, CO/CQ, VAT".
- **Thuật toán Tỉa nhánh Ngữ pháp Hậu tố (Backward Grammar Pruning):** Quét lùi ranh giới từ và chạy vòng lặp hội tụ loại bỏ triệt để mọi từ nối/dấu phân cách treo lơ lửng ở cuối câu (`tại`, `và`, `cho`, `ở`, `là`, `với`...), bảo chứng tiêu đề kết thúc bằng thực thể trọn vẹn.
- **Hòa giải Trạng thái Đơn nhất H1 (Single-Source Reconciliation):** Kích hoạt cờ `OMNI_BRIDGE_H1_RENDERED = true` tại theme WordPress và đọc trực tiếp từ `omni_bridge_homepage_h1` để chặn đứng xung đột inject từ hook chân trang, bảo chứng duy nhất 1 thẻ H1 trên trang chủ.
- **Khóa Bảo Toàn Đa Trường (Multi-Field Immutability Lock):** Khóa bảo toàn Title, Meta Description, H1 khi người dùng bấm Lưu Lên Web (`title_locked`, `meta_description_locked`, `h1_locked`), cấm mọi tác vụ quét ngầm tự ý ghi đè.
- **Thuật toán Hiệu trừ Tập hợp Động:** Nút `✨ AI Sáng Tạo Mới` áp dụng $S_{valid} = S_{candidates} \setminus (S_{existing} \cup \{S_{current}\})$, đảm bảo mỗi lần bấm là 1 góc nhìn mới 100%, không lặp lại và phản hồi tức thì $< 1$ ms.
- **Tiếp Địa Video YouTube Review Thực Tế (YouTube Subtitle Grounding):** Tự động bóc tách phụ đề tiếng Việt từ video đánh giá công nghệ trên YouTube (*Dương Dê, Phan Linh, Tinh Tế...*) để bổ sung cảm nhận người thật (chất âm, độ mượt, góc nhìn, nhược điểm) vào bài viết, triệt tiêu hoàn toàn dấu hiệu AI.
- Chi tiết xem tại: [`knowledge/50_sop63_consumer_centric_matrix_backward_pruning_and_immutability_lock.md`](file:///home/ubuntu/michinhhang/dify-integration/knowledge/50_sop63_consumer_centric_matrix_backward_pruning_and_immutability_lock.md).

## 32. SOP 64: BỘ QUY CHUẨN AGENT-READINESS (GEO/AEO) & KỸ THUẬT COPYWRITING CHUYỂN ĐỔI CAO
- **Dynamic `/llms.txt` & AI-Agent Readiness:** Mọi website liên kết qua Omni Bridge tự động cung cấp endpoint `/llms.txt` và `/llms-full.txt` tại root domain, kèm thẻ `<link rel="alternate" type="text/markdown" href="/llms.txt">` ở `<head>`, chuẩn hóa dữ liệu thực thể thương hiệu, danh mục sản phẩm và bảng giá kho cho các công cụ AI Search (Google AI Overviews, Perplexity, ChatGPT Search, Claude, Gemini).
- **DOM Heading Harmonizer (Bảo chứng duy nhất 1 thẻ H1):** Kích hoạt Output Buffering (`ob_start`) tại Omni Bridge. Tự động giữ nguyên H1 đầu tiên và hạ bậc các H1 phụ thừa thành `<h2>`, triệt tiêu hoàn toàn lỗi `⚠️ Trùng H1` trên mọi theme WordPress.
- **The Three-Question Gate & Copywriting Chuyển Đổi:** Mọi tiêu đề và bài viết phải vượt qua 3 bài kiểm tra: (1) *Can the reader picture it?* (Hình ảnh cụ thể, không trừu tượng); (2) *Can it be proven true/false?* (Sự thật kiểm chứng được: bảo hành 24T, đổi mới 30 ngày); (3) *The Swap Test* (Nếu đối thủ cạnh tranh nói được câu tương tự thì phải viết lại theo lợi thế độc quyền).
- **Bộ Lọc Khử Giới Từ Thừa (Grammar Sanitizer):** Tự động loại bỏ sạch mọi từ nối, giới từ lơ lửng ở đuôi câu (`tại`, `và`, `cho`, `ở`, `-`, `|`), ngay cả khi câu chưa vượt quá trần ký tự tối đa.
- Chi tiết xem tại: [`knowledge/51_sop64_agent_readiness_geo_and_conversion_copywriting.md`](file:///home/ubuntu/michinhhang/dify-integration/knowledge/51_sop64_agent_readiness_geo_and_conversion_copywriting.md).




