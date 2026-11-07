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

def send_chat(query: str, conversation_id: str = "", user: str = "tester_advanced"):
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

    print(f"\n🤖 AI ANSWER:\n{final_answer[:500]}..." if len(final_answer) > 500 else f"\n🤖 AI ANSWER:\n{final_answer}")
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
    print("🚀 BẮT ĐẦU CHẠY BỘ KIỂM THỬ NÂNG CAO (ADVANCED EDGE CASES)")

    # Test 5: Người dùng chỉ gửi 1 URL website duy nhất
    print("\n--- TEST CASE 5: Người dùng chỉ gửi đúng 1 URL duy nhất không kèm mô tả ---")
    send_chat("https://tongkhomi.com", user="suite2_case5")

    # Test 6: Chuyển đổi ngành hàng từ Trinh sát -> Tạo sản phẩm ngay sau đó (2 lượt liên hoàn)
    print("\n--- TEST CASE 6: Trinh sát đối thủ dòng sản phẩm mới -> Đăng ngay sản phẩm WooCommerce ---")
    conv_id = ""
    res6_1 = send_chat("Tôi muốn bán thêm Máy giặt sấy Xiaomi Mijia 10kg, hãy tìm đối thủ và khảo sát giá thị trường", user="suite2_case6")
    if res6_1:
        conv_id = res6_1["conversation_id"]
        time.sleep(2)
        res6_2 = send_chat("Từ dữ liệu vừa tìm được, hãy tạo ngay trang chi tiết sản phẩm WooCommerce chuẩn UECS cho máy giặt này", conversation_id=conv_id, user="suite2_case6")

    # Test 7: Yêu cầu viết bài cẩm nang bóc tách sự thật / tư vấn gây tranh luận
    print("\n--- TEST CASE 7: Yêu cầu viết bài cẩm nang bóc tách lầm tưởng (Myth-busting) ---")
    send_chat("Hãy viết bài cẩm nang tư vấn: Có nên mua Tủ lạnh Xiaomi Mijia 606L không, bóc tách ưu nhược điểm so với Panasonic và Toshiba", user="suite2_case7")

    # Test 8: Truy vấn thị trường theo khu vực địa lý cụ thể (TP.HCM / Miền Nam)
    print("\n--- TEST CASE 8: Khảo sát đối thủ cạnh tranh tại khu vực TP.HCM ---")
    send_chat("Trinh sát các đối thủ bán Tivi Xiaomi 100 inch tại khu vực TP.HCM", user="suite2_case8")

    print("\n🎉 HOÀN TẤT TOÀN BỘ 4 TEST CASES NÂNG CAO!")
