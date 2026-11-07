#!/usr/bin/env python3
"""
Portal Settings Test Route Module — BYOK & Serper Key Pool Diagnostics.
Strictly complies with SOP 23 (<= 300 lines).
"""
import os
import re
import json
import base64
import logging
import urllib.request
import urllib.error
from typing import Optional
from fastapi import APIRouter, HTTPException

from models import (
    PortalTestAuthorityRequest,
    PortalTestSerpRequest,
    PortalTestAiRequest
)
from utils import _mask_api_key
import db_service
from seed_analyzer import extract_domain_age_and_citations

logger = logging.getLogger("marketing_engine")
router = APIRouter()


@router.post("/api/v1/portal/settings/test-authority")
def portal_test_authority(payload: PortalTestAuthorityRequest, tenant_id: Optional[str] = None):
    """Kiểm tra cụm khóa Moz Free API và kiểm tra tính năng bóc tách Thẩm quyền (Tuổi tên miền / Trích dẫn)."""
    raw_keys = payload.moz_api_keys.strip()
    if not raw_keys or "..." in raw_keys:
        curr = db_service.get_system_setting("authority_config", tenant_id) or {}
        raw_keys = curr.get("moz_api_keys", "")

    test_domain = payload.test_domain.strip() or "viomiviet.vn"
    key_pairs = [k.strip() for k in re.split(r"[,;\n\r]+", raw_keys) if ":" in k.strip()]

    moz_results = []
    if key_pairs:
        for idx, pair in enumerate(key_pairs, start=1):
            acc_id, sec_key = pair.split(":", 1)
            auth_str = base64.b64encode(f"{acc_id.strip()}:{sec_key.strip()}".encode()).decode()
            t_payload = {"targets": [f"https://{test_domain}"]}
            try:
                req = urllib.request.Request(
                    "https://lsapi.seomoz.com/v2/url_metrics",
                    data=json.dumps(t_payload).encode(),
                    headers={
                        "Authorization": f"Basic {auth_str}",
                        "Content-Type": "application/json"
                    }
                )
                with urllib.request.urlopen(req, timeout=5) as resp:
                    if resp.status == 200:
                        m_data = json.loads(resp.read().decode())
                        m_res = m_data.get("results", [])
                        if m_res:
                            da = m_res[0].get("domain_authority", 0)
                            pa = m_res[0].get("page_authority", 0)
                            moz_results.append(f"Moz Key #{idx} ({_mask_api_key(acc_id)}): OK (DA: {da}/100, PA: {pa}/100)")
                        else:
                            moz_results.append(f"Moz Key #{idx}: OK (Không có dữ liệu cho domain test)")
            except urllib.error.HTTPError as he:
                moz_results.append(f"Moz Key #{idx}: Lỗi HTTP {he.code} (Hạn ngạch hoặc key không hợp lệ)")
            except Exception as e:
                moz_results.append(f"Moz Key #{idx}: {str(e)}")

    sample_age_info = extract_domain_age_and_citations(test_domain, "", tenant_id=tenant_id)
    dork_text = f"Cơ chế 0đ (RDAP / Copyright / Dorking): Tuổi đời ~{sample_age_info.get('age_years', 0)} năm, Trích dẫn ước tính ~{sample_age_info.get('citations_count', 0)}."

    msg_lines = []
    if moz_results:
        msg_lines.extend(moz_results)
    else:
        msg_lines.append("Chưa cấu hình Moz API Keys (Hệ thống sẽ chạy hoàn toàn bằng cơ chế 0đ miễn phí).")
    msg_lines.append(dork_text)

    return {
        "status": "success",
        "message": "\n".join(msg_lines)
    }


@router.post("/api/v1/portal/settings/test-serp")
def portal_test_serp(payload: PortalTestSerpRequest, tenant_id: Optional[str] = None):
    """Kiểm tra tính hợp lệ và hạn ngạch của Serper.dev API Keys."""
    raw_keys = payload.serper_api_keys.strip()

    if not raw_keys or "..." in raw_keys:
        curr = db_service.get_system_setting("serp_config", tenant_id) or {}
        raw_keys = curr.get("serper_api_keys", "")

    if not raw_keys:
        raw_keys = os.environ.get("SERPER_API_KEYS", os.environ.get("SERPER_API_KEY", ""))

    keys = [k.strip() for k in re.split(r"[,;\n\r]+", raw_keys) if k.strip()]
    if not keys:
        raise HTTPException(status_code=400, detail="Vui lòng nhập ít nhất 1 Serper.dev API Key để kiểm tra.")

    success_keys = []
    failed_keys = []

    for idx, k in enumerate(keys, start=1):
        test_payload = {
            "q": "tivi xiaomi",
            "gl": payload.gl or "vn",
            "hl": payload.hl or "vi"
        }
        req = urllib.request.Request(
            "https://google.serper.dev/search",
            data=json.dumps(test_payload).encode("utf-8"),
            headers={
                "X-API-KEY": k,
                "Content-Type": "application/json"
            }
        )
        try:
            with urllib.request.urlopen(req, timeout=8) as resp:
                if resp.status == 200:
                    data = json.loads(resp.read().decode("utf-8"))
                    ad_cnt = len(data.get("ads", []))
                    org_cnt = len(data.get("organic", []))
                    success_keys.append(f"Key #{idx} ({_mask_api_key(k)}): OK (Bóc tách được {ad_cnt} Google Ads, {org_cnt} Organic SEO)")
                else:
                    failed_keys.append(f"Key #{idx} ({_mask_api_key(k)}): Lỗi HTTP {resp.status}")
        except urllib.error.HTTPError as he:
            failed_keys.append(f"Key #{idx} ({_mask_api_key(k)}): Lỗi HTTP {he.code} (Hết hạn mức hoặc key sai)")
        except Exception as e:
            failed_keys.append(f"Key #{idx} ({_mask_api_key(k)}): {str(e)}")

    if not success_keys:
        raise HTTPException(
            status_code=400, 
            detail=f"Tất cả {len(keys)} key đều thất bại:\n" + "\n".join(failed_keys)
        )

    return {
        "status": "success",
        "message": f"Kiểm tra hoàn tất ({len(success_keys)}/{len(keys)} keys hoạt động tốt):\n" + "\n".join(success_keys) + (f"\n\nLưu ý:\n" + "\n".join(failed_keys) if failed_keys else "")
    }


@router.post("/api/v1/portal/settings/test-ai")
def portal_test_ai(payload: PortalTestAiRequest, tenant_id: Optional[str] = None):
    """Kiểm tra kết nối tới nhà cung cấp AI tùy chọn (BYOK)."""
    api_key = payload.api_key.strip()
    if not api_key or "..." in api_key:
        curr = db_service.get_system_setting("ai_config", tenant_id) or {}
        api_key = curr.get("api_key", "")

    if not api_key:
        raise HTTPException(status_code=400, detail="Vui lòng nhập API Key của AI để kiểm tra.")

    provider = payload.provider or "openai"
    base_url = payload.base_url.strip()
    model = payload.model_name.strip()

    if not base_url:
        if provider == "openai":
            base_url = "https://api.openai.com/v1/chat/completions"
        elif provider == "gemini":
            base_url = "https://generativelanguage.googleapis.com/v1beta/openai/chat/completions"
        else:
            base_url = "https://api.openai.com/v1/chat/completions"
    elif not base_url.endswith("/chat/completions"):
        base_url = base_url.rstrip("/") + "/chat/completions"

    if not model:
        model = "gpt-4o" if provider == "openai" else ("gemini-1.5-flash" if provider == "gemini" else "default")

    test_body = {
        "model": model,
        "messages": [
            {"role": "user", "content": "Xin chào, hãy trả lời đúng 1 từ: 'CONNECTED'"}
        ],
        "max_tokens": 15,
        "temperature": 0.1
    }

    req = urllib.request.Request(
        base_url,
        data=json.dumps(test_body).encode("utf-8"),
        headers={
            "Authorization": f"Bearer {api_key}",
            "Content-Type": "application/json"
        }
    )

    try:
        with urllib.request.urlopen(req, timeout=12) as resp:
            data = json.loads(resp.read().decode("utf-8"))
            reply = data["choices"][0]["message"]["content"].strip()
            return {
                "status": "success",
                "message": f"Kết nối AI thành công! Phản hồi từ model [{model}]: \"{reply}\""
            }
    except urllib.error.HTTPError as he:
        err_msg = he.read().decode("utf-8", errors="replace")
        raise HTTPException(status_code=400, detail=f"Lỗi kết nối AI (HTTP {he.code}): {err_msg[:200]}")
    except Exception as e:
        raise HTTPException(status_code=400, detail=f"Lỗi kiểm tra AI: {str(e)}")
