#!/usr/bin/env python3
"""License & Subscription Management Service for WordPress Omni Bridge Connector.
Strictly complies with SOP 23 (<= 300 lines).
"""
import os, io, json, uuid, hmac, hashlib, zipfile, urllib.parse, logging
from datetime import datetime, timezone, timedelta
from typing import Dict, Optional, Tuple, Any
from psycopg2.extras import RealDictCursor
import db_service

logger = logging.getLogger("marketing_engine")

PLANS_CONFIG = {
    "trial_7d": {"days": 7, "label": "Dùng Thử Miễn Phí (7 Ngày)"},
    "plan_1m":  {"days": 30, "label": "Gói 1 Tháng (30 Ngày)"},
    "plan_3m":  {"days": 90, "label": "Gói 3 Tháng (90 Ngày)"},
    "plan_6m":  {"days": 180, "label": "Gói 6 Tháng (180 Ngày)"},
    "plan_9m":  {"days": 270, "label": "Gói 9 Tháng (270 Ngày)"},
    "plan_12m": {"days": 365, "label": "Gói 12 Tháng (365 Ngày)"},
    "custom":   {"days": 30, "label": "Gói Tùy Chỉnh"}
}

def get_secret_salt() -> str:
    return os.getenv("ENGINE_API_KEY", "omni_bridge_secure_salt_2026_x9")

def normalize_domain_fqdn(url_or_domain: str) -> str:
    clean = (url_or_domain or "").strip().lower()
    if "://" in clean:
        clean = urllib.parse.urlsplit(clean).hostname or clean
    clean = clean.split("/")[0].split(":")[0]
    return clean.removeprefix("www.")

def generate_signature(domain: str, license_key: str, plan_type: str, expires_at_iso: str) -> str:
    raw = f"{normalize_domain_fqdn(domain)}|{license_key}|{plan_type}|{expires_at_iso}".encode("utf-8")
    return hmac.new(get_secret_salt().encode("utf-8"), raw, hashlib.sha256).hexdigest()

def ensure_license_schema():
    """Tự động đảm bảo schema bảng projects và registry bản quyền tên miền."""
    try:
        with db_service.get_connection() as conn:
            with conn.cursor() as cur:
                cur.execute("""
                    ALTER TABLE projects ADD COLUMN IF NOT EXISTS license_key VARCHAR(128) UNIQUE,
                    ADD COLUMN IF NOT EXISTS plan_type VARCHAR(32) DEFAULT 'trial_7d', ADD COLUMN IF NOT EXISTS plan_status VARCHAR(32) DEFAULT 'active',
                    ADD COLUMN IF NOT EXISTS trial_started_at TIMESTAMPTZ, ADD COLUMN IF NOT EXISTS plan_started_at TIMESTAMPTZ,
                    ADD COLUMN IF NOT EXISTS plan_expires_at TIMESTAMPTZ, ADD COLUMN IF NOT EXISTS license_signature VARCHAR(256),
                    ADD COLUMN IF NOT EXISTS last_heartbeat_at TIMESTAMPTZ;
                    CREATE INDEX IF NOT EXISTS idx_projects_license_key ON projects(license_key);
                    CREATE TABLE IF NOT EXISTS domain_license_registry (
                        domain VARCHAR(255) PRIMARY KEY, first_trial_started_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
                        trial_expires_at TIMESTAMPTZ NOT NULL, current_plan_type VARCHAR(32) NOT NULL DEFAULT 'trial_7d',
                        current_expires_at TIMESTAMPTZ, license_key VARCHAR(128), project_id VARCHAR(64),
                        created_at TIMESTAMPTZ DEFAULT NOW(), updated_at TIMESTAMPTZ DEFAULT NOW()
                    );
                """)
                conn.commit()
    except Exception as e:
        logger.warning(f"ensure_license_schema note: {e}")

def get_domain_registry(domain: str) -> Optional[Dict[str, Any]]:
    """Tra cứu bản ghi khóa cứng bản quyền theo FQDN Domain."""
    with db_service.get_connection() as conn:
        with conn.cursor(cursor_factory=RealDictCursor) as cur:
            cur.execute("SELECT * FROM domain_license_registry WHERE domain = %s LIMIT 1;", (domain,))
            return cur.fetchone()

def get_or_create_project_license(project_id: str) -> Dict[str, Any]:
    """Lấy hoặc khởi tạo bản quyền, khóa cứng FQDN chống gian lận gỡ cài lại plugin."""
    ensure_license_schema()
    project = db_service.get_project_by_id(project_id)
    if not project:
        raise ValueError("Không tìm thấy dự án.")

    domain = normalize_domain_fqdn(project.get("target_website_url") or project.get("name") or "")
    lic_key = project.get("license_key")
    plan_type = project.get("plan_type") or "trial_7d"
    expires_at = project.get("plan_expires_at")
    now = datetime.now(timezone.utc)

    reg = get_domain_registry(domain)
    if reg:
        first_trial = reg["first_trial_started_at"]
        lic_key = lic_key or reg.get("license_key") or f"OMNI-{domain.replace('.', '-').upper()[:10]}-{uuid.uuid4().hex[:12].upper()}"
        plan_type = reg.get("current_plan_type") or plan_type
        expires_at = reg.get("current_expires_at") or reg["trial_expires_at"]
        sig = generate_signature(domain, lic_key, plan_type, expires_at.isoformat())

        with db_service.get_connection() as conn:
            with conn.cursor() as cur:
                cur.execute("""
                    UPDATE projects 
                    SET license_key = %s, plan_type = %s, trial_started_at = %s,
                        plan_started_at = %s, plan_expires_at = %s, license_signature = %s, updated_at = NOW()
                    WHERE id = %s;
                    UPDATE domain_license_registry SET project_id = %s, license_key = %s, updated_at = NOW() WHERE domain = %s;
                """, (lic_key, plan_type, first_trial, first_trial, expires_at, sig, project_id, project_id, lic_key, domain))
                conn.commit()
    elif not lic_key or not expires_at:
        lic_key = f"OMNI-{domain.replace('.', '-').upper()[:10]}-{uuid.uuid4().hex[:12].upper()}"
        expires_at = now + timedelta(days=7)
        sig = generate_signature(domain, lic_key, plan_type, expires_at.isoformat())

        with db_service.get_connection() as conn:
            with conn.cursor() as cur:
                cur.execute("""
                    UPDATE projects 
                    SET license_key = %s, plan_type = %s, plan_status = 'active',
                        trial_started_at = %s, plan_started_at = %s, plan_expires_at = %s,
                        license_signature = %s, updated_at = NOW()
                    WHERE id = %s;
                    INSERT INTO domain_license_registry 
                        (domain, first_trial_started_at, trial_expires_at, current_plan_type, current_expires_at, license_key, project_id)
                    VALUES (%s, %s, %s, %s, %s, %s, %s)
                    ON CONFLICT (domain) DO UPDATE SET project_id = EXCLUDED.project_id, license_key = EXCLUDED.license_key, updated_at = NOW();
                """, (lic_key, plan_type, now, now, expires_at, sig, project_id, domain, now, expires_at, plan_type, expires_at, lic_key, project_id))
                conn.commit()

    is_expired = now > expires_at if expires_at else False
    days_left = max(0, (expires_at - now).total_seconds() / 86400) if not is_expired else 0

    return {
        "project_id": project_id, "domain": domain, "license_key": lic_key,
        "plan_type": plan_type, "plan_label": PLANS_CONFIG.get(plan_type, {}).get("label", "Gói Tùy Chỉnh"),
        "plan_status": "expired" if is_expired else (project.get("plan_status") or "active"),
        "is_expired": is_expired, "days_left": round(days_left, 1),
        "expires_at": expires_at.isoformat() if expires_at else None,
        "last_heartbeat_at": project.get("last_heartbeat_at").isoformat() if project.get("last_heartbeat_at") else None,
        "signature": sig if 'sig' in locals() else project.get("license_signature")
    }

def configure_project_plan(project_id: str, plan_type: str, custom_days: Optional[int] = None, extend_existing: bool = False) -> Dict[str, Any]:
    """Cập nhật hoặc gia hạn gói cước cho dự án, đồng bộ bảng registry."""
    ensure_license_schema()
    project = db_service.get_project_by_id(project_id)
    if not project:
        raise ValueError("Không tìm thấy dự án.")

    domain = normalize_domain_fqdn(project.get("target_website_url") or project.get("name") or "")
    lic_key = project.get("license_key") or f"OMNI-{domain.replace('.', '-').upper()[:10]}-{uuid.uuid4().hex[:12].upper()}"
    now = datetime.now(timezone.utc)
    current_expires = project.get("plan_expires_at")

    days = PLANS_CONFIG.get(plan_type, {}).get("days", 7)
    if plan_type == "custom" and custom_days and custom_days > 0:
        days = int(custom_days)

    if plan_type == "trial_7d" or not extend_existing or not current_expires or current_expires <= now:
        new_expires = now + timedelta(days=days)
    else:
        new_expires = current_expires + timedelta(days=days)

    sig = generate_signature(domain, lic_key, plan_type, new_expires.isoformat())
    with db_service.get_connection() as conn:
        with conn.cursor() as cur:
            cur.execute("""
                UPDATE projects 
                SET license_key = %s, plan_type = %s, plan_status = 'active',
                    plan_started_at = %s, plan_expires_at = %s, license_signature = %s, updated_at = NOW()
                WHERE id = %s;
                INSERT INTO domain_license_registry 
                    (domain, first_trial_started_at, trial_expires_at, current_plan_type, current_expires_at, license_key, project_id)
                VALUES (%s, %s, %s, %s, %s, %s, %s)
                ON CONFLICT (domain) DO UPDATE SET
                    current_plan_type = EXCLUDED.current_plan_type, current_expires_at = EXCLUDED.current_expires_at,
                    license_key = EXCLUDED.license_key, project_id = EXCLUDED.project_id, updated_at = NOW();
            """, (lic_key, plan_type, now, new_expires, sig, project_id, domain, now, now + timedelta(days=7), plan_type, new_expires, lic_key, project_id))
            conn.commit()

    return get_or_create_project_license(project_id)

def verify_wp_heartbeat(license_key: str, client_domain: str, sync_data: Optional[Dict[str, Any]] = None) -> Dict[str, Any]:
    """WordPress xác thực bản quyền, chống reset trial và tự động đồng bộ 2 chiều CMS."""
    ensure_license_schema()
    norm_client = normalize_domain_fqdn(client_domain)

    with db_service.get_connection() as conn:
        with conn.cursor(cursor_factory=RealDictCursor) as cur:
            cur.execute("""
                SELECT id, name, target_website_url, license_key, plan_type, plan_status,
                       plan_expires_at, license_signature, tenant_id
                FROM projects WHERE license_key = %s LIMIT 1;
            """, (license_key.strip(),))
            proj = cur.fetchone()

    if not proj:
        return {"valid": False, "status": "invalid_key", "message": "License Key không tồn tại trên hệ thống."}

    allowed_domain = normalize_domain_fqdn(proj.get("target_website_url") or proj.get("name") or "")
    if norm_client != allowed_domain:
        return {"valid": False, "status": "domain_mismatch", "message": f"License Key bị khóa cho '{allowed_domain}', không hợp lệ cho '{norm_client}'."}

    now = datetime.now(timezone.utc)
    reg = get_domain_registry(allowed_domain)
    expires_at = (reg.get("current_expires_at") if reg else None) or proj.get("plan_expires_at")
    plan_type = (reg.get("current_plan_type") if reg else None) or proj.get("plan_type") or "trial_7d"

    if not expires_at or now > expires_at:
        msg = "Thời hạn dùng thử 7 ngày đã kết thúc. Việc cài lại plugin không gia hạn dùng thử." if plan_type == "trial_7d" else "Gói cước Omni Bridge đã hết hạn. Vui lòng gia hạn để tiếp tục."
        return {"valid": False, "status": "expired", "message": msg}

    sync_fields = {}
    if sync_data:
        cms_key = sync_data.get("cms_api_key")
        cms_url = sync_data.get("cms_api_url") or sync_data.get("publish_endpoint")
        seo_prov = sync_data.get("seo_provider") or "auto"
        if cms_key:
            sync_fields["cms_auth_config"] = json.dumps({"api_key": cms_key, "provider": "wordpress", "seo_plugin": seo_prov})
            sync_fields["cms_type"] = "wordpress"
        if cms_url:
            sync_fields["cms_api_url"] = cms_url.split("/wp-json/")[0].rstrip("/") if "/wp-json/" in cms_url else cms_url.rstrip("/")

    with db_service.get_connection() as conn:
        with conn.cursor() as cur:
            if sync_fields:
                sets = ", ".join([f"{k} = %s" for k in sync_fields.keys()])
                vals = list(sync_fields.values()) + [proj["id"]]
                cur.execute(f"UPDATE projects SET last_heartbeat_at = NOW(), {sets} WHERE id = %s;", vals)
            else:
                cur.execute("UPDATE projects SET last_heartbeat_at = NOW() WHERE id = %s;", (proj["id"],))
            conn.commit()

    portal_url = os.getenv("PORTAL_BASE_URL", "https://seo.vuonghoang.name.vn").rstrip("/")
    days_left = max(0, (expires_at - now).total_seconds() / 86400)
    return {
        "valid": True, "status": "active", "domain": allowed_domain, "plan_type": plan_type,
        "plan_label": PLANS_CONFIG.get(plan_type, {}).get("label", "Gói Dịch Vụ"),
        "days_left": round(days_left, 1), "expires_at": expires_at.isoformat(),
        "project_id": str(proj["id"]), "tenant_id": str(proj.get("tenant_id") or "default"),
        "gsc_engine_url": portal_url, "engine_api_key": os.getenv("ENGINE_API_KEY", "")
    }

def bundle_customized_plugin_zip(project_id: str, portal_base_url: str = "") -> Tuple[str, bytes]:
    """Tạo file zip cài đặt cá nhân hóa có nhúng sẵn omni-license.json khóa cứng cho tên miền dự án."""
    lic_info = get_or_create_project_license(project_id)
    domain = lic_info["domain"]
    project = db_service.get_project_by_id(project_id) or {}
    cms_cfg = project.get("cms_auth_config") or {}
    if isinstance(cms_cfg, str):
        try: cms_cfg = json.loads(cms_cfg)
        except Exception: cms_cfg = {}
    api_key = cms_cfg.get("api_key") or f"obc_{uuid.uuid4().hex}"

    target_portal = portal_base_url or os.getenv("PORTAL_BASE_URL", "https://seo.vuonghoang.name.vn/")
    if any(h in target_portal for h in ["100.", "127.0.0.1", "localhost", "172."]):
        target_portal = "https://seo.vuonghoang.name.vn/"

    plugin_dir = next((c for c in [
        os.path.join(os.path.dirname(os.path.abspath(__file__)), "omni-bridge-connector"),
        "/app/omni-bridge-connector", "/var/www/html/wp-content/plugins/omni-bridge-connector",
        "/home/ubuntu/michinhhang/dify-integration/plugins/omni-bridge-connector"
    ] if os.path.exists(c)), None)
    if not plugin_dir:
        raise FileNotFoundError("Không tìm thấy thư mục mã nguồn omni-bridge-connector.")
    license_payload = {
        "domain": domain, "license_key": lic_info["license_key"], "api_key": api_key,
        "plan_type": lic_info["plan_type"], "plan_label": lic_info["plan_label"],
        "expires_at": lic_info["expires_at"], "portal_url": target_portal,
        "signature": lic_info["signature"], "generated_at": datetime.now(timezone.utc).isoformat()
    }
    zip_buffer = io.BytesIO()
    with zipfile.ZipFile(zip_buffer, "w", zipfile.ZIP_DEFLATED) as zf:
        for root, _, files in os.walk(plugin_dir):
            for file in files:
                if not file.endswith((".zip", ".tar.gz", ".git")):
                    full_path = os.path.join(root, file)
                    zf.write(full_path, os.path.relpath(full_path, os.path.dirname(plugin_dir)))
        zf.writestr("omni-bridge-connector/omni-license.json", json.dumps(license_payload, indent=2, ensure_ascii=False))
    return f"omni-bridge-connector-{domain}.zip", zip_buffer.getvalue()

def activate_license_key_for_project(project_id: str, license_key: str) -> Dict[str, Any]:
    """Kích hoạt bản quyền cho dự án thông qua License Key."""
    ensure_license_schema()
    key_clean = license_key.strip()
    with db_service.get_connection() as conn:
        with conn.cursor(cursor_factory=RealDictCursor) as cur:
            cur.execute("SELECT id, name, target_website_url FROM projects WHERE id = %s;", (project_id,))
            proj = cur.fetchone()
            if not proj:
                raise ValueError("Không tìm thấy dự án.")
            domain = normalize_domain_fqdn(proj.get("target_website_url") or proj.get("name") or "")
            prefix_check = f"OMNI-{domain.split('.')[0].upper()}"
            if not key_clean.startswith("OMNI-") or prefix_check not in key_clean:
                raise ValueError(f"License Key này không dành cho tên miền '{domain}'.")
            cur.execute("UPDATE projects SET license_key = %s, plan_status = 'active', updated_at = NOW() WHERE id = %s;", (key_clean, project_id))
            conn.commit()
    return get_or_create_project_license(project_id)

