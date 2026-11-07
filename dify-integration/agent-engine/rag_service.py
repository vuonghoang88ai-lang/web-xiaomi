import os
import requests
import json
import logging

logger = logging.getLogger("rag_service")

DIFY_API_URL = os.getenv("DIFY_API_URL", "http://docker-api-1:5001/v1")
DATASET_API_KEY = os.getenv("DIFY_DATASET_API_KEY", "")

def _headers():
    if not DATASET_API_KEY:
        raise RuntimeError("Thiếu DIFY_DATASET_API_KEY; không được dùng token mặc định.")
    return {
        "Authorization": f"Bearer {DATASET_API_KEY}",
        "Content-Type": "application/json"
    }

def create_or_get_project_dataset(project_name: str, client_name: str = "Client") -> str:
    """Tự động tạo hoặc lấy ID kho tri thức RAG trên Dify cho dự án"""
    clean_client = "".join(c for c in client_name if c.isalnum() or c in ['_', '-'])[:12]
    clean_proj = "".join(c for c in project_name if c.isalnum() or c in ['_', '-'])[:22]
    dataset_name = f"KB_{clean_client}_{clean_proj}"[:40]
    
    # 1. Kiểm tra dataset đã tồn tại chưa
    try:
        r = requests.get(f"{DIFY_API_URL}/datasets?limit=100", headers=_headers(), timeout=10)
        if r.status_code == 200:
            data = r.json().get("data", [])
            for ds in data:
                if ds.get("name") == dataset_name:
                    logger.info(f"Found existing dataset: {ds['name']} ({ds['id']})")
                    return ds["id"]
    except Exception as e:
        logger.warning(f"Error checking existing datasets: {e}")

    # 2. Tạo mới dataset
    payload = {
        "name": dataset_name,
        "permission": "only_me"
    }
    try:
        r = requests.post(f"{DIFY_API_URL}/datasets", headers=_headers(), json=payload, timeout=10)
        if r.status_code in [200, 201]:
            ds_data = r.json()
            logger.info(f"Created new dataset: {dataset_name} ({ds_data.get('id')})")
            return ds_data.get("id")
        else:
            logger.error(f"Failed to create dataset: HTTP {r.status_code} - {r.text}")
    except Exception as e:
        logger.error(f"Exception creating dataset: {e}")

    return None

def upload_document_to_dataset(dataset_id: str, doc_name: str, text_content: str) -> dict:
    """Nạp tài liệu dạng văn bản Markdown vào kho tri thức RAG"""
    if not dataset_id:
        return {"error": "No dataset_id provided"}

    url = f"{DIFY_API_URL}/datasets/{dataset_id}/document/create-by-text"
    payload = {
        "name": doc_name,
        "text": text_content,
        "indexing_technique": "economy",
        "process_rule": {
            "mode": "automatic"
        }
    }
    try:
        r = requests.post(url, headers=_headers(), json=payload, timeout=15)
        if r.status_code in [200, 201]:
            logger.info(f"Uploaded document '{doc_name}' to dataset {dataset_id}")
            return r.json()
        else:
            logger.error(f"Failed to upload doc: HTTP {r.status_code} - {r.text}")
            return {"error": r.text}
    except Exception as e:
        logger.error(f"Exception uploading doc: {e}")
        return {"error": str(e)}

def sync_project_intelligence_to_rag(project_name: str, client_name: str, target_website: str,
                                     business_desc: str, competitors: list, gap_report: dict,
                                     core_strengths: str = "", suggested_uvps: list = None) -> str:
    """Tự động đồng bộ toàn bộ trí tuệ trinh sát vào kho tri thức RAG của dự án"""
    dataset_id = create_or_get_project_dataset(project_name, client_name)
    if not dataset_id:
        logger.error("Could not obtain dataset_id for RAG sync")
        return None

    # Biên soạn tài liệu Trí Tuệ Dự Án (Dossier) chuẩn Markdown
    lines = [
        f"# HỒ SƠ TRÍ TUỆ DỰ ÁN & BÁO CÁO THỊ TRƯỜNG: {project_name.upper()}",
        f"- **Website Mục Tiêu**: {target_website}",
        f"- **Mô Tả Sản Phẩm / Dịch Vụ**: {business_desc}",
        f"- **Lợi Thế Cạnh Tranh (UVP)**: {core_strengths if core_strengths else 'Chưa chọn (Dùng phương án AI gợi ý bên dưới)'}",
        "",
        "## I. GỢI Ý ĐỊNH VỊ ĐỘT PHÁ (UVP OPTIONS DO AI TỔNG HỢP TỪ ĐIỂM MÙ ĐỐI THỦ)",
    ]
    if suggested_uvps:
        for idx, uvp in enumerate(suggested_uvps, 1):
            lines.append(f"### Phương Án {idx}: {uvp.get('title', 'Định Vị')}")
            lines.append(f"- **Khẩu hiệu / Cam kết**: {uvp.get('slogan', '')}")
            lines.append(f"- **Lý do lựa chọn**: {uvp.get('rationale', '')}")
            lines.append(f"- **Đòn bẩy vượt đối thủ**: {uvp.get('tactics', '')}")
            lines.append("")
    else:
        lines.append("- Doanh nghiệp đã có sẵn lợi thế độc quyền được chỉ định.\n")

    lines.extend([
        "## II. KẾT QUẢ SERP ĐÃ THU THẬP VÀ TRẠNG THÁI KIỂM TRA",
        "| Top | Tên Miền | Trạng Thái | Dịch Vụ / Ngành Hàng | Điểm Mạnh | Khoảng Trống Yếu |",
        "| :--- | :--- | :--- | :--- | :--- | :--- |"
    ])

    for comp in competitors:
        rank = comp.get("top_rank", "-")
        domain = comp.get("domain", "")
        status = "Online (đã kiểm tra)" if comp.get("is_online", False) else "Không xác minh online"
        structure = comp.get("scraped_structure", {})
        services = structure.get("title", "Chưa có dữ liệu")[:40]
        strengths = comp.get("strengths") or "Chưa có bằng chứng"
        weaknesses = comp.get("weaknesses") or "Chưa có bằng chứng"
        lines.append(f"| #{rank} | {domain} | {status} | {services} | {strengths[:40]} | {weaknesses[:40]} |")

    lines.extend([
        "",
        "## III. MA TRẬN KHOẢNG TRỐNG THỊ TRƯỜNG (GAP ANALYSIS)",
        "### 1. Khoảng trống Danh Mục & Sản Phẩm (Category Gaps):",
        json.dumps(gap_report.get("category_gap", {}), ensure_ascii=False, indent=2),
        "",
        "### 2. Khoảng trống Độ Uy Tín & Chuyên Môn (E-E-A-T Gaps):",
        json.dumps(gap_report.get("eeat_gap", {}), ensure_ascii=False, indent=2),
        "",
        "### 3. Khoảng trống Chuyển Đổi & Bảng Giá (CRO & Pricing Gaps):",
        json.dumps(gap_report.get("cro_gap", {}), ensure_ascii=False, indent=2),
        "",
        "## IV. LỘ TRÌNH CHIẾN LƯỢC HÀNH ĐỘNG CHO 6 AGENTS (W1 - W6)",
        gap_report.get("strategic_roadmap", "Thực hiện bóc tách từ khóa P1 và viết bài SEO chất lượng cao.")
    ])

    full_markdown = "\n".join(lines)
    
    # Nạp vào Dataset Dify
    doc_title = f"Ho_So_Thi_Truong_Va_Top_10_Doi_Thu_{project_name}"
    upload_result = upload_document_to_dataset(dataset_id, doc_title, full_markdown)
    if not upload_result or upload_result.get("error"):
        raise RuntimeError("Unable to upload project intelligence to the Dify dataset")
    
    return dataset_id
