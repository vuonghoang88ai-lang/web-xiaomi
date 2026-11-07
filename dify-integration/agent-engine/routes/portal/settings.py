#!/usr/bin/env python3
"""Portal route module - auto-generated from server.py refactor."""
import os
import sys
import json
import logging
import re
import uuid
from uuid import UUID
from typing import Optional
from datetime import datetime, timezone, timedelta
from fastapi import APIRouter, HTTPException, Query, BackgroundTasks, Request
from fastapi.responses import JSONResponse, StreamingResponse, FileResponse, Response
from pydantic import BaseModel, Field, field_validator

CURRENT_DIR = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, os.path.dirname(os.path.dirname(CURRENT_DIR)))

from models import *
from utils import JOBS_DB, _gsc_access_token, _project_gsc_connector, _mask_api_key
import db_service
import rag_service
import gsc_service
from scraper_tool import scrape_competitor_recon
from serp_discovery_tool import (
    SerpDiscoveryError,
    normalize_website_url,
    run_onboarding_gap_analysis,
    discover_top_10_competitors,
    inspect_website,
    generate_uvp_suggestions,
)
from seed_analyzer import (
    run_continuous_seed_reconnaissance,
    generate_kickoff_briefing,
    analyze_seed_competitor,
    deep_audit_competitor_batch,
)
import urllib.request
import urllib.error

logger = logging.getLogger('marketing_engine')

router = APIRouter()

@router.get("/api/v1/portal/settings")
def portal_get_settings(tenant_id: Optional[str] = None):
    """Lấy thông tin cấu hình SERP, AI và Thẩm quyền Authority của hệ thống/tenant (đã che giấu API keys)."""
    import db_service
    serp_cfg = db_service.get_system_setting("serp_config", tenant_id) or {}
    ai_cfg = db_service.get_system_setting("ai_config", tenant_id) or {}
    auth_cfg = db_service.get_system_setting("authority_config", tenant_id) or {}

    raw_serp_keys = serp_cfg.get("serper_api_keys", "")
    if isinstance(raw_serp_keys, list):
        serp_keys_list = raw_serp_keys
    elif isinstance(raw_serp_keys, str):
        serp_keys_list = [k.strip() for k in re.split(r"[,;\n\r]+", raw_serp_keys) if k.strip()]
    else:
        serp_keys_list = []

    # Kiểm tra fallback env nếu trong DB chưa có
    env_serp = os.environ.get("SERPER_API_KEYS", os.environ.get("SERPER_API_KEY", ""))
    if not serp_keys_list and env_serp:
        serp_keys_list = [k.strip() for k in re.split(r"[,;\n\r]+", env_serp) if k.strip()]

    masked_serp_keys = [_mask_api_key(k) for k in serp_keys_list]

    ai_key = ai_cfg.get("api_key", "")

    # Moz Keys
    raw_moz_keys = auth_cfg.get("moz_api_keys", "")
    if isinstance(raw_moz_keys, list):
        moz_keys_list = raw_moz_keys
    elif isinstance(raw_moz_keys, str):
        moz_keys_list = [k.strip() for k in re.split(r"[,;\n\r]+", raw_moz_keys) if ":" in k.strip()]
    else:
        moz_keys_list = []

    masked_moz_keys = [_mask_api_key(k) for k in moz_keys_list]

    return {
        "status": "success",
        "serp_config": {
            "provider": serp_cfg.get("provider", "serper" if serp_keys_list else "duckduckgo"),
            "has_keys": len(serp_keys_list) > 0,
            "key_count": len(serp_keys_list),
            "masked_keys": masked_serp_keys,
            "raw_keys_display": ", ".join(masked_serp_keys) if masked_serp_keys else "",
            "gl": serp_cfg.get("gl", "vn"),
            "hl": serp_cfg.get("hl", "vi")
        },
        "ai_config": {
            "mode": ai_cfg.get("mode", "system"),
            "provider": ai_cfg.get("provider", "openai"),
            "has_api_key": bool(ai_key),
            "api_key_masked": _mask_api_key(ai_key),
            "base_url": ai_cfg.get("base_url", ""),
            "model_name": ai_cfg.get("model_name", "gpt-4o")
        },
        "authority_config": {
            "mode": auth_cfg.get("mode", "free_rdap_dork"),
            "has_moz_keys": len(moz_keys_list) > 0,
            "moz_key_count": len(moz_keys_list),
            "masked_keys": masked_moz_keys,
            "raw_keys_display": "\n".join(masked_moz_keys) if masked_moz_keys else "",
            "enable_dorking": auth_cfg.get("enable_dorking", True)
        }
    }



@router.post("/api/v1/portal/settings")
def portal_save_settings(payload: PortalSaveSettingsRequest, tenant_id: Optional[str] = None):
    """Lưu cấu hình SERP (Google Ads & SEO) và AI Copilot (BYOK)."""
    import db_service

    # 1. Xử lý SERP Config
    curr_serp = db_service.get_system_setting("serp_config", tenant_id) or {}
    in_serp_keys = payload.serper_api_keys.strip()

    # Nếu người dùng không nhập key mới (hoặc chứa mask ...), giữ nguyên key cũ trong DB
    if in_serp_keys and "..." not in in_serp_keys:
        final_serp_keys = in_serp_keys
    else:
        final_serp_keys = curr_serp.get("serper_api_keys", "")

    serp_data = {
        "provider": payload.serp_provider,
        "serper_api_keys": final_serp_keys,
        "gl": payload.gl or "vn",
        "hl": payload.hl or "vi"
    }
    db_service.save_system_setting("serp_config", serp_data, tenant_id)

    # 2. Xử lý AI Config
    curr_ai = db_service.get_system_setting("ai_config", tenant_id) or {}
    in_ai_key = payload.ai_api_key.strip()
    if in_ai_key and "..." not in in_ai_key:
        final_ai_key = in_ai_key
    else:
        final_ai_key = curr_ai.get("api_key", "")

    ai_data = {
        "mode": payload.ai_mode,
        "provider": payload.ai_provider,
        "api_key": final_ai_key,
        "base_url": payload.ai_base_url.strip(),
        "model_name": payload.ai_model_name.strip()
    }
    db_service.save_system_setting("ai_config", ai_data, tenant_id)

    # 3. Xử lý Thẩm Quyền Authority Config (Moz Free Pool & RDAP/Dorking)
    curr_auth = db_service.get_system_setting("authority_config", tenant_id) or {}
    in_moz_keys = payload.moz_api_keys.strip()
    if in_moz_keys and "..." not in in_moz_keys:
        final_moz_keys = in_moz_keys
    else:
        final_moz_keys = curr_auth.get("moz_api_keys", "")

    auth_data = {
        "mode": payload.authority_mode,
        "moz_api_keys": final_moz_keys,
        "enable_dorking": payload.enable_dorking
    }
    db_service.save_system_setting("authority_config", auth_data, tenant_id)

    return {
        "status": "success",
        "message": "Đã lưu toàn bộ cấu hình Cài đặt & API thành công!"
    }







