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

def send_chat(query: str, conversation_id: str = "", user: str = "tester_auto"):
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
        return None, conversation_id

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
                        print(f"🔧 [TOOL CALLED] {tool} with input: {tool_input}")
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
    print("🚀 BẮT ĐẦU CHẠY CÁC KỊCH BẢN TEST DIFY AI COPILOT")
    
    # Kịch bản 1: Đưa thông tin website & đối thủ, sau đó yêu cầu phân tích SEO
    print("\n--- TEST SCENARIO 1: Onboarding & Phân tích SEO Đa lượt (Multi-turn Context) ---")
    conv_id = ""
    res1 = send_chat("Website của tôi là tongkhomi.com, đối thủ là mivietnam.vn. Chúng tôi bán Tivi Xiaomi và Tủ lạnh Xiaomi tại Hà Nội.", user="test_suite_1")
    if res1:
        conv_id = res1["conversation_id"]
        time.sleep(2)
        # Lượt 2: Không nhắc lại URL, yêu cầu phân tích SEO
        res2 = send_chat("Hãy phân tích website của tôi đang thiếu những yếu tố SEO nào so với đối thủ", conversation_id=conv_id, user="test_suite_1")

    # Kịch bản 2: Trinh sát đối thủ và giá bán
    print("\n--- TEST SCENARIO 2: Trinh sát giá bán và đối thủ theo từ khóa ---")
    send_chat("Hãy trinh sát đối thủ và giá bán của sản phẩm Tivi Xiaomi 65 inch hiện nay", user="test_suite_2")

    # Kịch bản 3: Worker 1 - Tạo sản phẩm WooCommerce chuẩn UECS
    print("\n--- TEST SCENARIO 3: Yêu cầu đăng sản phẩm WooCommerce (Worker 1 UECS) ---")
    send_chat("Hãy tạo trang chi tiết sản phẩm WooCommerce cho Tivi Xiaomi Redmi MAX 85 inch", user="test_suite_3")

    # Kịch bản 4: Worker 2 - Viết bài viết SEO Blog chuẩn SOP 12
    print("\n--- TEST SCENARIO 4: Yêu cầu viết bài blog tin tức SEO (Worker 2 10x Editorial) ---")
    send_chat("Hãy viết một bài viết blog cẩm nang so sánh Tivi Xiaomi và Tivi Samsung nên mua loại nào", user="test_suite_4")

    print("\n✅ HOÀN TẤT BỘ TEST KỊCH BẢN!")
