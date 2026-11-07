#!/usr/bin/env python3
"""
Portal Route: Universal Plugin Interoperability Gateway (SOP 23 <= 300 lines).
Allows Marketing AI to interact with ANY WordPress plugin (ERP, CRM, POS, Booking...)
via options reading, action hooks triggering, and safe database table queries.
"""
import os, json, logging, urllib.request
from fastapi import APIRouter, HTTPException
from pydantic import BaseModel, Field
from typing import Optional, Dict, Any, List
import db_service

logger = logging.getLogger("marketing_engine.interop")
router = APIRouter()

class InteropExecuteRequest(BaseModel):
    action_type: str = Field(..., description="get_option, do_action, apply_filters, query_table, call_method")
    option_key: Optional[str] = Field(None, description="Khóa cấu hình trong wp_options")
    hook: Optional[str] = Field(None, description="Tên Action/Filter Hook của plugin")
    table: Optional[str] = Field(None, description="Tên bảng dữ liệu riêng của plugin (có prefix wp_)")
    class_name: Optional[str] = Field(None, description="Tên lớp PHP của plugin")
    method_name: Optional[str] = Field(None, description="Tên phương thức cần gọi")
    args: Optional[List[Any]] = Field(default_factory=list, description="Tham số truyền vào hook hoặc method")
    where: Optional[Dict[str, Any]] = Field(default_factory=dict, description="Điều kiện truy vấn WHERE")
    limit: Optional[int] = Field(20, description="Số lượng dòng tối đa")

@router.post("/api/v1/portal/projects/{project_id}/interop/execute")
@router.post("/api/v1/projects/{project_id}/interop/execute")
def execute_plugin_interop(project_id: str, req: InteropExecuteRequest):
    """
    Cổng Giao Tiếp Phổ Quát: Kết nối và điều khiển MỌI plugin trên WordPress từ AI Copilot (SOP 30).
    """
    project = db_service.get_project_by_id(project_id)
    if not project:
        raise HTTPException(status_code=404, detail="Không tìm thấy dự án.")

    cms_url = (project.get("cms_api_url") or "").rstrip("/")
    if os.path.exists("/.dockerenv") and ("127.0.0.1:8080" in cms_url or "localhost:8080" in cms_url):
        cms_url = cms_url.replace("127.0.0.1:8080", "wordpress:80").replace("localhost:8080", "wordpress:80")

    auth = json.loads(project.get("cms_auth_config") or "{}") if isinstance(project.get("cms_auth_config"), str) else (project.get("cms_auth_config") or {})
    api_key = auth.get("api_key", "").strip()
    if not cms_url or not api_key:
        raise HTTPException(status_code=400, detail="Website chưa kết nối Omni Bridge Connector hợp lệ.")

    headers = {
        "X-Omni-Bridge-Key": api_key,
        "Content-Type": "application/json",
        "User-Agent": "Autonomous-Marketing-Agent/1.0"
    }

    try:
        bridge_req = urllib.request.Request(
            f"{cms_url}/wp-json/omni-bridge/v1/interop/execute",
            data=json.dumps(req.dict(), ensure_ascii=False).encode("utf-8"),
            headers=headers
        )
        with urllib.request.urlopen(bridge_req, timeout=15) as resp:
            data = json.loads(resp.read().decode("utf-8"))
            return data
    except Exception as e:
        logger.error(f"[INTEROP_EXEC_ERR] {e}")
        raise HTTPException(status_code=500, detail=f"Không thể giao tiếp với plugin trên WordPress: {e}")

