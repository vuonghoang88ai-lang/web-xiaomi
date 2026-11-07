import json
import psycopg2

conn = psycopg2.connect(
    dbname="dify",
    user="postgres",
    password="difyai123456",
    host="172.20.0.8",
    port=5432
)
conn.autocommit = True
cur = conn.cursor()

# 1. Update Tool API Provider definition
tools = [
    {
        "server_url": "http://172.20.0.1:8085/api/v1/onboarding/analyze",
        "method": "post",
        "summary": "Tiếp cận dự án mới, bóc tách website của bạn, trinh sát kết quả đối thủ trực tuyến trên DuckDuckGo SERP và phân tích khoảng trống SEO (Gap Analysis).",
        "operation_id": "analyzeProjectOnboarding",
        "parameters": [
            {
                "name": "target_website_url",
                "label": {"en_US": "target_website_url", "zh_Hans": "target_website_url"},
                "placeholder": {"en_US": "tongkhomi.com", "zh_Hans": "tongkhomi.com"},
                "scope": None,
                "auto_generate": None,
                "template": None,
                "required": True,
                "default": None,
                "options": [],
                "type": "string",
                "multiple": False,
                "human_description": {"en_US": "URL website của bạn cần phân tích (ví dụ: tongkhomi.com)", "zh_Hans": "URL website"},
                "form": "llm",
                "llm_description": "URL website của bạn cần phân tích (ví dụ: tongkhomi.com)"
            },
            {
                "name": "seed_competitor_url",
                "label": {"en_US": "seed_competitor_url", "zh_Hans": "seed_competitor_url"},
                "placeholder": {"en_US": "mivietnam.vn", "zh_Hans": "mivietnam.vn"},
                "scope": None,
                "auto_generate": None,
                "template": None,
                "required": False,
                "default": "",
                "options": [],
                "type": "string",
                "multiple": False,
                "human_description": {"en_US": "URL website đối thủ mẫu tham chiếu nếu có", "zh_Hans": "Website đối thủ mẫu"},
                "form": "llm",
                "llm_description": "URL website đối thủ mẫu tham chiếu (ví dụ: mivietnam.vn hoặc viomiviet.vn)"
            },
            {
                "name": "business_description",
                "label": {"en_US": "business_description", "zh_Hans": "business_description"},
                "placeholder": {"en_US": "Tivi Xiaomi, Tủ lạnh Xiaomi", "zh_Hans": "Mô tả sản phẩm"},
                "scope": None,
                "auto_generate": None,
                "template": None,
                "required": False,
                "default": "Kinh doanh và phân phối sản phẩm Xiaomi",
                "options": [],
                "type": "string",
                "multiple": False,
                "human_description": {"en_US": "Mô tả sản phẩm, dịch vụ hoặc ngành hàng", "zh_Hans": "Mô tả sản phẩm"},
                "form": "llm",
                "llm_description": "Mô tả ngành hàng, sản phẩm hoặc dịch vụ cốt lõi"
            },
            {
                "name": "target_location",
                "label": {"en_US": "target_location", "zh_Hans": "target_location"},
                "placeholder": {"en_US": "Hà Nội", "zh_Hans": "Hà Nội"},
                "scope": None,
                "auto_generate": None,
                "template": None,
                "required": False,
                "default": "Hà Nội",
                "options": [],
                "type": "string",
                "multiple": False,
                "human_description": {"en_US": "Khu vực thị trường mục tiêu", "zh_Hans": "Khu vực"},
                "form": "llm",
                "llm_description": "Khu vực địa lý thị trường mục tiêu (ví dụ: Hà Nội, Toàn quốc)"
            }
        ],
        "author": "",
        "icon": None,
        "openapi": {
            "summary": "Tiếp cận dự án mới và trinh sát TOP đối thủ SERP",
            "description": "Bóc tách web của bạn, tìm TOP đối thủ SERP DuckDuckGo thực tế và audit SEO.",
            "operationId": "analyzeProjectOnboarding",
            "requestBody": {
                "required": True,
                "content": {
                    "application/json": {
                        "schema": {
                            "type": "object",
                            "required": ["target_website_url"],
                            "properties": {
                                "target_website_url": {"type": "string", "description": "URL website của bạn"},
                                "seed_competitor_url": {"type": "string", "description": "URL đối thủ mẫu"},
                                "business_description": {"type": "string", "description": "Ngành hàng hoặc sản phẩm"},
                                "target_location": {"type": "string", "description": "Khu vực địa lý"}
                            }
                        }
                    }
                }
            }
        },
        "output_schema": {}
    },
    {
        "server_url": "http://172.20.0.1:8085/api/v1/research",
        "method": "post",
        "summary": "Trinh sát đối thủ và giá bán thực tế theo từ khóa từ các website thương mại điện tử đối thủ đang online.",
        "operation_id": "researchCompetitor",
        "parameters": [
            {
                "name": "keyword",
                "label": {"en_US": "keyword", "zh_Hans": "keyword"},
                "placeholder": {"en_US": "Tivi Xiaomi, Tủ lạnh Xiaomi", "zh_Hans": "Từ khóa"},
                "scope": None,
                "auto_generate": None,
                "template": None,
                "required": True,
                "default": None,
                "options": [],
                "type": "string",
                "multiple": False,
                "human_description": {"en_US": "Từ khóa hoặc nhóm sản phẩm cần tìm đối thủ", "zh_Hans": "Từ khóa"},
                "form": "llm",
                "llm_description": "Từ khóa hoặc tên sản phẩm cần trinh sát đối thủ thực tế"
            }
        ],
        "author": "",
        "icon": None,
        "openapi": {
            "summary": "Trinh sát đối thủ và giá bán thực tế",
            "description": "Cào dữ liệu sản phẩm, model và giá bán từ đối thủ theo từ khóa.",
            "operationId": "researchCompetitor",
            "requestBody": {
                "required": True,
                "content": {
                    "application/json": {
                        "schema": {
                            "type": "object",
                            "required": ["keyword"],
                            "properties": {
                                "keyword": {"type": "string", "description": "Từ khóa cần trinh sát"}
                            }
                        }
                    }
                }
            }
        },
        "output_schema": {}
    },
    {
        "server_url": "http://172.20.0.1:8085/api/v1/campaign/product",
        "method": "post",
        "summary": "WORKER 1: Tạo và xuất bản trang chi tiết SẢN PHẨM WooCommerce chuẩn UECS (6 trụ cột thực tế + thông số định lượng ACF).",
        "operation_id": "createWooCommerceProduct",
        "parameters": [
            {
                "name": "keyword",
                "label": {"en_US": "keyword", "zh_Hans": "keyword"},
                "placeholder": {"en_US": "Tivi Xiaomi Redmi MAX 85 inch", "zh_Hans": "Tên sản phẩm"},
                "scope": None,
                "auto_generate": None,
                "template": None,
                "required": True,
                "default": None,
                "options": [],
                "type": "string",
                "multiple": False,
                "human_description": {"en_US": "Tên model sản phẩm cần đăng", "zh_Hans": "Tên sản phẩm"},
                "form": "llm",
                "llm_description": "Tên model sản phẩm cần đăng lên WooCommerce"
            },
            {
                "name": "category",
                "label": {"en_US": "category", "zh_Hans": "category"},
                "placeholder": {"en_US": "Tivi Xiaomi", "zh_Hans": "Danh mục"},
                "scope": None,
                "auto_generate": None,
                "template": None,
                "required": False,
                "default": "Sản phẩm",
                "options": [],
                "type": "string",
                "multiple": False,
                "human_description": {"en_US": "Danh mục WooCommerce", "zh_Hans": "Danh mục"},
                "form": "llm",
                "llm_description": "Danh mục WooCommerce của sản phẩm"
            }
        ],
        "author": "",
        "icon": None,
        "openapi": {
            "summary": "Đăng trang chi tiết sản phẩm WooCommerce chuẩn UECS",
            "description": "Tạo trang sản phẩm WooCommerce hoàn chỉnh gồm thông số ACF và nội dung UECS chống sao chép đối thủ.",
            "operationId": "createWooCommerceProduct",
            "requestBody": {
                "required": True,
                "content": {
                    "application/json": {
                        "schema": {
                            "type": "object",
                            "required": ["keyword"],
                            "properties": {
                                "keyword": {"type": "string", "description": "Tên model sản phẩm"},
                                "category": {"type": "string", "description": "Danh mục WooCommerce"}
                            }
                        }
                    }
                }
            }
        },
        "output_schema": {}
    },
    {
        "server_url": "http://172.20.0.1:8085/api/v1/campaign/article",
        "method": "post",
        "summary": "WORKER 2: Viết bài viết SEO Blog / Tin tức / Cẩm nang chuẩn 10x Editorial Architecture (SOP 12).",
        "operation_id": "writeSeoArticle",
        "parameters": [
            {
                "name": "keyword",
                "label": {"en_US": "keyword", "zh_Hans": "keyword"},
                "placeholder": {"en_US": "Đánh giá chi tiết Tivi Xiaomi và LG", "zh_Hans": "Từ khóa"},
                "scope": None,
                "auto_generate": None,
                "template": None,
                "required": True,
                "default": None,
                "options": [],
                "type": "string",
                "multiple": False,
                "human_description": {"en_US": "Chủ đề / Từ khóa SEO bài viết Blog", "zh_Hans": "Chủ đề"},
                "form": "llm",
                "llm_description": "Chủ đề hoặc từ khóa bài viết blog tin tức cần viết"
            },
            {
                "name": "category",
                "label": {"en_US": "category", "zh_Hans": "category"},
                "placeholder": {"en_US": "Tin tức", "zh_Hans": "Chuyên mục"},
                "scope": None,
                "auto_generate": None,
                "template": None,
                "required": False,
                "default": "Tin tức",
                "options": [],
                "type": "string",
                "multiple": False,
                "human_description": {"en_US": "Chuyên mục Blog WordPress", "zh_Hans": "Chuyên mục"},
                "form": "llm",
                "llm_description": "Chuyên mục Blog WordPress"
            }
        ],
        "author": "",
        "icon": None,
        "openapi": {
            "summary": "Viết bài viết SEO Blog WordPress chuẩn SOP 12",
            "description": "Viết bài cẩm nang, tin tức SEO 1.500-2.500 từ chuẩn SOP 12 lên Blog WordPress.",
            "operationId": "writeSeoArticle",
            "requestBody": {
                "required": True,
                "content": {
                    "application/json": {
                        "schema": {
                            "type": "object",
                            "required": ["keyword"],
                            "properties": {
                                "keyword": {"type": "string", "description": "Từ khóa bài viết blog"},
                                "category": {"type": "string", "description": "Chuyên mục blog"}
                            }
                        }
                    }
                }
            }
        },
        "output_schema": {}
    }
]

cur.execute(
    "UPDATE tool_api_providers SET tools_str = %s WHERE id = '24998500-243d-41ca-8bb4-27c5183501ab';",
    (json.dumps(tools, ensure_ascii=False),)
)
print("Updated tool_api_providers!")

# 2. Update app_model_configs agent_mode & pre_prompt
agent_mode = {
    "enabled": True,
    "max_iteration": 8,
    "strategy": "function_call",
    "prompt_type": "common",
    "tools": [
        {
            "provider_id": "24998500-243d-41ca-8bb4-27c5183501ab",
            "provider_type": "api",
            "provider_name": "Marketing Engine",
            "tool_name": "analyzeProjectOnboarding",
            "tool_label": "analyzeProjectOnboarding",
            "tool_parameters": {
                "target_website_url": None,
                "seed_competitor_url": None,
                "business_description": None,
                "target_location": None
            },
            "notAuthor": False,
            "enabled": True,
            "type": "api",
            "isDeleted": False
        },
        {
            "provider_id": "24998500-243d-41ca-8bb4-27c5183501ab",
            "provider_type": "api",
            "provider_name": "Marketing Engine",
            "tool_name": "researchCompetitor",
            "tool_label": "researchCompetitor",
            "tool_parameters": {
                "keyword": None
            },
            "notAuthor": False,
            "enabled": True,
            "type": "api",
            "isDeleted": False
        },
        {
            "provider_id": "24998500-243d-41ca-8bb4-27c5183501ab",
            "provider_type": "api",
            "provider_name": "Marketing Engine",
            "tool_name": "createWooCommerceProduct",
            "tool_label": "createWooCommerceProduct",
            "tool_parameters": {
                "keyword": None,
                "category": None
            },
            "notAuthor": False,
            "enabled": True,
            "type": "api",
            "isDeleted": False
        },
        {
            "provider_id": "24998500-243d-41ca-8bb4-27c5183501ab",
            "provider_type": "api",
            "provider_name": "Marketing Engine",
            "tool_name": "writeSeoArticle",
            "tool_label": "writeSeoArticle",
            "tool_parameters": {
                "keyword": None,
                "category": None
            },
            "notAuthor": False,
            "enabled": True,
            "type": "api",
            "isDeleted": False
        }
    ]
}

pre_prompt = """Bạn là **Giám Đốc Marketing AI (CMO Agent)** điều hành Hệ Thống Marketing Tự Động Hóa Đa Năng.

### ⚠️ NGUYÊN TẮC THI HÀNH BẮT BUỘC (ZERO HALLUCINATION & DIRECT TOOL CALL):
1. **GỌI TOOL NGAY LẬP TỨC - KHÔNG ĐƯỢC CHAT SUÔNG**:
   - Khi người dùng gửi URL website, hoặc yêu cầu tìm đối thủ, hoặc hỏi website của họ đang thiếu yếu tố SEO nào: **BẠN BẮT BUỘC PHẢI GỌI FUNCTION (TOOL CALL) NGAY LẬP TỨC**.
   - TUYỆT ĐỐI KHÔNG CHAT CÂU HỨA HẸN hay trì hoãn (như: "Tôi đang chuẩn bị...", "Hệ thống sẽ tự động chạy...", "Xin vui lòng chờ vài giây...").
   - TUYỆT ĐỐI KHÔNG tự bịa ra danh sách đối thủ (ví dụ tự bịa Samsung.com.vn, v.v.). Mọi đối thủ và dữ liệu phân tích BẮT BUỘC PHẢI lấy từ kết quả JSON do tool trả về.
   - TUYỆT ĐỐI KHÔNG in bảng tiến trình giả mạo `[✅ Hoàn thành]` khi chưa có kết quả trả về từ tool thực tế.

2. **TỰ ĐỘNG KẾ THỪA THÔNG TIN TỪ LỊCH SỬ CHAT (RAG MEMORY)**:
   - Nếu trong lịch sử trò chuyện người dùng đã từng cung cấp:
     * Website của họ (ví dụ: `tongkhomi.com` hoặc `michinhhang.com`)
     * Đối thủ tham chiếu (ví dụ: `mivietnam.vn` hoặc `viomiviet.vn`)
     * Ngành hàng / Sản phẩm (ví dụ: `Tivi Xiaomi, Tủ lạnh Xiaomi, Máy giặt Xiaomi`)
     -> BẠN PHẢI TỰ ĐỘNG ĐIỀN các thông tin này vào tham số gọi tool, **TUYỆT ĐỐI KHÔNG ĐƯỢC HỎI LẠI NGƯỜI DÙNG**!

3. **CÁC CÔNG CỤ ĐƯỢC PHÂN CHIA RÕ RÀNG**:
   - **`analyzeProjectOnboarding`**: Dùng khi cần phân tích website của người dùng, phân tích lỗ hổng SEO và tìm kiếm TOP đối thủ cạnh tranh thực tế trên SERP DuckDuckGo.
   - **`researchCompetitor`**: Dùng khi người dùng yêu cầu trinh sát giá bán, model và nội dung đối thủ theo từ khóa sản phẩm.
   - **`createWooCommerceProduct` (Worker 1 - Chuẩn UECS)**: Dùng khi người dùng muốn đăng hoặc viết trang chi tiết SẢN PHẨM WooCommerce.
     * Sản phẩm sẽ có giá bán, SKU, bảng thông số kỹ thuật 25-35 chỉ số cho ACF Repeater và bài đánh giá 6 trụ cột UECS thực chiến (Bối cảnh thực tế, Đo lường vận hành, Anti-Persona ai không nên mua, Ma trận so sánh 3 bên, Checklist lắp đặt, Mẹo tinh chỉnh bảo dưỡng) - khác biệt hoàn toàn với đối thủ.
   - **`writeSeoArticle` (Worker 2 - Chuẩn 10x Editorial SOP 12)**: Dùng khi người dùng muốn viết BÀI VIẾT BLOG / TIN TỨC / CẨM NANG tư vấn.
     * Bài viết blog chuyên sâu 1.500 - 2.500 từ chuẩn SOP 12, mục lục TOC, bóc tách lầm tưởng (myth-busting), bảng đối sánh Markdown, 5 FAQ thực tế.
"""

cur.execute(
    """
    UPDATE app_model_configs 
    SET agent_mode = %s,
        pre_prompt = %s
    WHERE id = 'd8e89a45-7fed-467a-8ab0-19f53430c548';
    """,
    (json.dumps(agent_mode), pre_prompt)
)
print("Updated app_model_configs successfully!")
cur.close()
conn.close()
