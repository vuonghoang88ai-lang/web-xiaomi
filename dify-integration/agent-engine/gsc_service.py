"""Google Search Console OAuth and Search Analytics client.

The connector deliberately uses Google's official OAuth flow. SEO plugins may
provide on-page metadata, but they are not a supported source of GSC tokens.
"""

import os
from datetime import date, datetime, timezone
from typing import Any, Optional
from urllib.parse import urlencode

import requests
from cryptography.fernet import Fernet, InvalidToken


AUTH_ENDPOINT = "https://accounts.google.com/o/oauth2/v2/auth"
TOKEN_ENDPOINT = "https://oauth2.googleapis.com/token"
SITES_ENDPOINT = "https://www.googleapis.com/webmasters/v3/sites"
SEARCH_ANALYTICS_ENDPOINT = "https://www.googleapis.com/webmasters/v3/sites/{site_url}/searchAnalytics/query"
GSC_READONLY_SCOPE = "https://www.googleapis.com/auth/webmasters.readonly"


class GSCConfigurationError(RuntimeError):
    pass


class GSCAPIError(RuntimeError):
    pass


def _setting(name: str, required: bool = True) -> str:
    value = os.getenv(name, "").strip()
    if required and not value:
        raise GSCConfigurationError(f"Thiếu biến môi trường {name}.")
    return value


def _fernet() -> Fernet:
    key = _setting("GSC_TOKEN_ENCRYPTION_KEY")
    try:
        return Fernet(key.encode("ascii"))
    except Exception as exc:
        raise GSCConfigurationError(
            "GSC_TOKEN_ENCRYPTION_KEY phải là Fernet key hợp lệ (32 bytes dạng base64)."
        ) from exc


def encrypt_refresh_token(refresh_token: str) -> str:
    return _fernet().encrypt(refresh_token.encode("utf-8")).decode("ascii")


def decrypt_refresh_token(encrypted_token: str) -> str:
    try:
        return _fernet().decrypt(encrypted_token.encode("ascii")).decode("utf-8")
    except (InvalidToken, ValueError, UnicodeError) as exc:
        raise GSCConfigurationError("Không thể giải mã refresh token GSC.") from exc


def build_authorization_url(state: str) -> str:
    params = {
        "client_id": _setting("GSC_CLIENT_ID"),
        "redirect_uri": _setting("GSC_REDIRECT_URI"),
        "response_type": "code",
        "scope": GSC_READONLY_SCOPE,
        "access_type": "offline",
        "prompt": "consent",
        "state": state,
    }
    return f"{AUTH_ENDPOINT}?{urlencode(params)}"


def exchange_code(code: str) -> dict[str, Any]:
    response = requests.post(
        TOKEN_ENDPOINT,
        data={
            "code": code,
            "client_id": _setting("GSC_CLIENT_ID"),
            "client_secret": _setting("GSC_CLIENT_SECRET"),
            "redirect_uri": _setting("GSC_REDIRECT_URI"),
            "grant_type": "authorization_code",
        },
        timeout=20,
    )
    if not response.ok:
        raise GSCAPIError(f"Google OAuth token exchange thất bại: {response.text[:500]}")
    token = response.json()
    if not token.get("refresh_token"):
        raise GSCAPIError(
            "Google không trả refresh_token. Hãy cấp quyền lại với access_type=offline và prompt=consent."
        )
    token["obtained_at"] = datetime.now(timezone.utc).isoformat()
    return token


def refresh_access_token(encrypted_refresh_token: str) -> dict[str, Any]:
    response = requests.post(
        TOKEN_ENDPOINT,
        data={
            "client_id": _setting("GSC_CLIENT_ID"),
            "client_secret": _setting("GSC_CLIENT_SECRET"),
            "refresh_token": decrypt_refresh_token(encrypted_refresh_token),
            "grant_type": "refresh_token",
        },
        timeout=20,
    )
    if not response.ok:
        raise GSCAPIError(f"Google access token refresh thất bại: {response.text[:500]}")
    return response.json()


def _get_json(url: str, access_token: str) -> dict[str, Any]:
    response = requests.get(
        url,
        headers={"Authorization": f"Bearer {access_token}"},
        timeout=20,
    )
    if not response.ok:
        raise GSCAPIError(f"Google Search Console API lỗi {response.status_code}: {response.text[:500]}")
    return response.json()


def list_sites(access_token: str) -> list[dict[str, Any]]:
    return _get_json(SITES_ENDPOINT, access_token).get("siteEntry", [])


def query_search_analytics(
    access_token: str,
    site_url: str,
    start_date: date,
    end_date: date,
    dimensions: Optional[list[str]] = None,
    row_limit: int = 25000,
    start_row: int = 0,
    dimension_filter_groups: Optional[list[dict[str, Any]]] = None,
) -> dict[str, Any]:
    if start_date > end_date:
        raise ValueError("start_date phải nhỏ hơn hoặc bằng end_date.")
    payload: dict[str, Any] = {
        "startDate": start_date.isoformat(),
        "endDate": end_date.isoformat(),
        "dimensions": dimensions or ["date"],
        "rowLimit": min(max(row_limit, 1), 25000),
        "startRow": max(start_row, 0),
    }
    if dimension_filter_groups:
        payload["dimensionFilterGroups"] = dimension_filter_groups

    encoded_site_url = requests.utils.quote(site_url, safe="")
    response = requests.post(
        SEARCH_ANALYTICS_ENDPOINT.format(site_url=encoded_site_url),
        headers={
            "Authorization": f"Bearer {access_token}",
            "Content-Type": "application/json",
        },
        json=payload,
        timeout=30,
    )
    if not response.ok:
        raise GSCAPIError(f"Search Analytics lỗi {response.status_code}: {response.text[:500]}")
    return response.json()
