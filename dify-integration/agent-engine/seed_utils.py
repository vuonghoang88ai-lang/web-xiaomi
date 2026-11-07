#!/usr/bin/env python3
"""Utility functions for seed analysis - split from seed_analyzer.py."""
import json
import logging
import re
import subprocess
import urllib.parse
from concurrent.futures import ThreadPoolExecutor
from datetime import datetime, timezone
from typing import Dict, List, Optional, Any, Union
from bs4 import BeautifulSoup
from serp_discovery_tool import get_latest_paa_questions

logger = logging.getLogger("seed_analyzer")

logger = logging.getLogger("seed_analyzer")

HEADERS = [
    "-H", "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36",
    "-H", "Accept-Language: vi-VN,vi;q=0.9,en-US;q=0.8,en;q=0.7",
    "-H", "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
]

VIETNAMESE_STOPWORDS = {
    "va", "và", "cua", "của", "cho", "cac", "các", "nhung", "những", "la", "là",
    "voi", "với", "tai", "tại", "trong", "duoc", "được", "nay", "này", "do", "đó",
    "trang", "chu", "trang chủ", "lien", "he", "liên hệ", "gioi", "thieu", "giới thiệu",
    "tin", "tuc", "tin tức", "blog", "xem", "them", "thêm", "xem thêm", "chinh", "sach", "chính sách",
    "huong", "dan", "hướng dẫn", "ve", "về", "chung", "toi", "chúng tôi", "gio", "hang",
    "giỏ hàng", "tai", "khoan", "tài khoản", "dang", "nhap", "đăng nhập", "tim", "kiếm",
    "hotline", "email", "dia", "chi", "địa chỉ", "tat", "ca", "tất cả", "xem tất cả", "menu", "danh muc",
}

INTENT_MODIFIERS = [
    "chinh hang", "chính hãng", "gia re", "giá rẻ", "tai kho", "tại kho",
    "bao hanh", "bảo hành", "tra gop", "trả góp", "mua", "bán", "ban",
    "cao cap", "cao cấp", "tot nhat", "tốt nhất", "gia tot", "giá tốt",
]



def normalize_url(url: str) -> str:
    """Chuẩn hóa đường dẫn URL website, tự động sửa lỗi typo protocol và khoảng trắng."""
    val = (url or "").strip()
    if not val:
        raise ValueError("URL đối thủ mẫu không được để trống")
    has_explicit_https = bool(re.match(r"^https://", val, re.I))
    is_ip_or_local = bool(re.search(r"^(?:https?://)?(?:\d+\.\d+\.\d+\.\d+|localhost|127\.)(?::\d+)?", val, re.I))
    scheme = "https" if has_explicit_https or (not is_ip_or_local) else "http"
    for _ in range(3):
        val = re.sub(r'^(?:https?|ht+p+s?|h+tp+s?)\s*[:/]+\s*', '', val, flags=re.I).strip()
    val = re.sub(r'\s+(vn|com|top|net|org|edu|info|pro|online|xyz)\b', r'.\1', val, flags=re.I)
    val = val.replace(" ", "")
    full_url = f"{scheme}://{val}"
    parsed = urllib.parse.urlsplit(full_url)
    if not parsed.hostname or ("." not in parsed.hostname and not re.match(r'^\d+\.\d+\.\d+\.\d+$', parsed.hostname)):
        raise ValueError("URL không hợp lệ")
    path = parsed.path.rstrip('/')
    port_str = f":{parsed.port}" if parsed.port and parsed.port not in (80, 443) else ""
    return f"{parsed.scheme}://{parsed.hostname}{port_str}{path}" if path else f"{parsed.scheme}://{parsed.hostname}{port_str}/"



def fetch_html_with_meta(url: str, timeout: int = 5) -> dict:
    """Fetch HTML kèm đo lường TTFB và HTTP Status qua curl với User-Agent chuẩn desktop."""
    norm = normalize_url(url)
    cmd = ["curl", "-sS", "-L", "--max-time", str(timeout),
           "-w", "\n__TTFB__%{time_starttransfer}\n__HTTP__%{http_code}\n__SIZE__%{size_download}\n__EFFECTIVE__%{url_effective}"] + HEADERS + [norm]
    try:
        res = subprocess.run(cmd, capture_output=True, text=True, timeout=timeout + 2)
        if res.returncode == 0 and res.stdout and "\n__TTFB__" in res.stdout:
            html_part, meta_part = res.stdout.rsplit("\n__TTFB__", 1)
            lines = meta_part.strip().split("\n")
            ttfb_s = float(lines[0]) if len(lines) > 0 and lines[0] else 0.5
            code = int(lines[1].replace("__HTTP__", "")) if len(lines) > 1 and lines[1] else 200
            size_b = int(lines[2].replace("__SIZE__", "")) if len(lines) > 2 and lines[2] else len(html_part)
            eff_url = lines[3].replace("__EFFECTIVE__", "") if len(lines) > 3 and lines[3] else norm
            return {
                "html": html_part,
                "ttfb_ms": int(ttfb_s * 1000),
                "http_status": code,
                "size_kb": round(size_b / 1024, 1),
                "effective_url": eff_url
            }
        elif res.stdout and len(res.stdout) > 200:
            return {
                "html": res.stdout,
                "ttfb_ms": 400,
                "http_status": 200,
                "size_kb": round(len(res.stdout) / 1024, 1),
                "effective_url": norm
            }
    except Exception as e:
        logger.warning(f"Lỗi fetch_html_with_meta {url}: {e}")
    return {"html": None, "ttfb_ms": 0, "http_status": 0, "size_kb": 0, "effective_url": norm}



def fetch_html(url: str, timeout: int = 7) -> Optional[str]:
    """Cào nhanh mã HTML của trang bằng curl với User-Agent chuẩn desktop."""
    meta = fetch_html_with_meta(url, timeout=timeout)
    return meta.get("html")



