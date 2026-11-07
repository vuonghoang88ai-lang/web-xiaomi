import json
import requests
import time

import os
BASE_URL = os.environ.get("DIFY_API_URL", "http://localhost:8090/v1").rstrip("/") + "/chat-messages"
API_TOKEN = "app-cmo-test-token-tongkhomi"
HEADERS = {
    "Authorization": f"Bearer {API_TOKEN}",
    "Content-Type": "application/json"
}

def send_chat(query: str, conversation_id: str = "", user: str = "tester_suite3"):
    payload = {
        "inputs": {},
        "query": query,
        "response_mode": "streaming",
        "conversation_id": conversation_id,
        "user": user
    }
    
    print(f"\n=======================================================")
    print(f"👉 USER QUERY: {query}")
    print(f"   (conversation_id: {conversation_id or 'NEW'})")
    print(f"=======================================================")
    
    resp = requests.post(BASE_URL, headers=HEADERS, json=payload, stream=True, timeout=120)
    if resp.status_code != 200:
        print(f"❌ Error {resp.status_code}: {resp.text}")
        return None

    final_answer = ""
    tools_called = []
    thoughts = []
    ret_conv_id = conversation_id

    for line in resp.iter_lines():
        if not line:
            continue
        line_str = line.decode('utf-8')
        if line_str.startswith("data: "):
            try:
                data = json.loads(line_str[6:])
                event = data.get("event")
                if event == "agent_thought":
                    tool = data.get("tool")
                    tool_input = data.get("tool_input")
                    thought = data.get("thought")
                    if tool:
                        print(f"🔧 [TOOL CALLED] {tool}")
                        print(f"   Parameters: {tool_input}")
                        tools_called.append({"tool": tool, "input": tool_input})
                    elif thought:
                        thoughts.append(thought)
                elif event == "agent_message":
                    ans_chunk = data.get("answer", "")
                    final_answer += ans_chunk
                elif event == "message_end":
                    ret_conv_id = data.get("conversation_id", ret_conv_id)
            except Exception:
                pass

    print(f"\n🤖 AI ANSWER:\n{final_answer[:600]}..." if len(final_answer) > 600 else f"\n🤖 AI ANSWER:\n{final_answer}")
    print(f"\n📊 SUMMARY:")
    print(f"   - Tools called ({len(tools_called)}): {[t['tool'] for t in tools_called]}")
    print(f"   - Conversation ID: {ret_conv_id}")
    return {
        "final_answer": final_answer,
        "tools_called": tools_called,
        "thoughts": thoughts,
        "conversation_id": ret_conv_id
    }

if __name__ == "__main__":
    print("🚀 BẮT ĐẦU CHẠY BỘ KIỂM THỬ SUITE 3 (NGHIỆP VỤ & ĐA NGÀNH)")

    # Test Case 9: Đánh vào tử huyệt đối thủ (Hàng xách tay vs Bảo hành 24 tháng chính hãng)
    print("\n--- TEST CASE 9: Chiến lược chống phá giá & Định vị Bảo hành 24 tháng tại nhà ---")
    send_chat(
        "Hiện tại đối thủ bán Tivi Xiaomi hay phá giá bằng hàng xách tay không bảo hành. "
        "Web tongkhomi.com của tôi muốn nhấn mạnh bảo hành 24 tháng tại nhà và xuất hóa đơn VAT giá gốc tại kho. "
        "Hãy khảo sát đối thủ và đề xuất giải pháp nội dung chi tiết sản phẩm chuẩn UECS để đập tan nỗi sợ này của khách.",
        user="suite3_case9"
    )

    # Test Case 10: Multi-turn với yêu cầu bổ sung cấu trúc nội dung chuyên sâu
    print("\n--- TEST CASE 10: Viết bài so sánh Robot hút bụi và Tinh chỉnh theo lượt 2 ---")
    res10_1 = send_chat("Hãy viết bài blog SEO so sánh Robot hút bụi Xiaomi X20+ và Dreame L10s Ultra", user="suite3_case10")
    if res10_1:
        conv_id = res10_1["conversation_id"]
        time.sleep(2)
        send_chat(
            "Bài viết này cần lưu ý nhấn mạnh bảng so sánh lực hút Pa thực tế và checklist 5 lỗi thường gặp khi dùng nước lau sàn sai cách",
            conversation_id=conv_id,
            user="suite3_case10"
        )

    # Test Case 11: Tạo sản phẩm WooCommerce với đầy đủ thông số định lượng (SKU, Giá niêm yết, Giá sale)
    print("\n--- TEST CASE 11: Yêu cầu tạo sản phẩm với SKU, giá niêm yết và giá khuyến mãi cụ thể ---")
    send_chat(
        "Hãy tạo trang chi tiết sản phẩm WooCommerce cho Tủ lạnh Xiaomi Mijia 4 cánh 518L mã SKU: TL-XIAOMI-518L với giá niêm yết 16.990.000đ và giá khuyến mãi 14.490.000đ theo chuẩn UECS",
        user="suite3_case11"
    )

    # Test Case 12: Thử nghiệm tính đa năng sang ngành mới (Nha khoa tại Hà Nội)
    print("\n--- TEST CASE 12: Kiểm thử tính đa năng sang ngành Nha Khoa Thẩm Mỹ ---")
    send_chat(
        "Ngoài Xiaomi, tôi có thêm dự án Nha khoa Thẩm mỹ SmileCare tại Cầu Giấy Hà Nội, website là nhakhoasmilecare.vn. "
        "Hãy trinh sát các đối thủ nha khoa cùng khu vực và phân tích khoảng trống để thu hút khách niềng răng.",
        user="suite3_case12"
    )

    print("\n🎉 HOÀN TẤT BỘ TEST SUITE 3!")
