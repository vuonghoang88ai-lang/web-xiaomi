import os
import json
import uuid
import psycopg2
from psycopg2.extras import RealDictCursor

DB_HOST = os.getenv("DB_HOST", "docker-db_postgres-1")
DB_PORT = int(os.getenv("DB_PORT", "5432"))
DB_NAME = os.getenv("DB_NAME", "marketing_saas")
DB_USER = os.getenv("DB_USER", "postgres")
DB_PASS = os.getenv("DB_PASS", "difyai123456")

def get_connection():
    hosts_to_try = [
        os.getenv("DB_HOST", "docker-db_postgres-1"),
        "docker-db_postgres-1",
        "db_postgres",
        "127.0.0.1"
    ]
    last_err = None
    for h in hosts_to_try:
        try:
            return psycopg2.connect(
                host=h,
                port=DB_PORT,
                dbname=DB_NAME,
                user=DB_USER,
                password=DB_PASS,
                connect_timeout=3,
                sslmode="disable"
            )
        except Exception as e:
            last_err = e
            continue
    raise last_err or RuntimeError("Cannot connect to PostgreSQL")

def _is_valid_uuid(val) -> bool:
    if not val:
        return False
    try:
        uuid.UUID(str(val))
        return True
    except (ValueError, AttributeError, TypeError):
        return False

def get_default_tenant_and_user():
    """Lấy tenant và user mặc định cho các tác vụ chưa gắn tenant_id cụ thể"""
    with get_connection() as conn:
        with conn.cursor(cursor_factory=RealDictCursor) as cur:
            cur.execute("SELECT id, name FROM tenants ORDER BY created_at ASC LIMIT 1;")
            tenant = cur.fetchone()
            if not tenant:
                return None, None
            cur.execute("SELECT id, email, role FROM users WHERE tenant_id = %s LIMIT 1;", (tenant["id"],))
            user = cur.fetchone()
            return tenant, user

def get_tenant(tenant_id: str):
    """Fetch a tenant by its explicit identifier."""
    with get_connection() as conn:
        with conn.cursor(cursor_factory=RealDictCursor) as cur:
            cur.execute("SELECT id, name FROM tenants WHERE id = %s;", (tenant_id,))
            return cur.fetchone()

def create_tenant(name: str, plan: str = "pro", max_projects: int = 5):
    """Tạo mới một Tenant (Doanh nghiệp / Khách hàng trả phí)"""
    with get_connection() as conn:
        with conn.cursor(cursor_factory=RealDictCursor) as cur:
            cur.execute("""
                INSERT INTO tenants (name, subscription_plan, max_projects)
                VALUES (%s, %s, %s)
                RETURNING id, name, subscription_plan, max_projects, created_at;
            """, (name, plan, max_projects))
            tenant = cur.fetchone()
            conn.commit()
            return tenant

def create_user(tenant_id: str, email: str, full_name: str, role: str = "client"):
    """Tạo tài khoản người dùng gắn với một Tenant"""
    with get_connection() as conn:
        with conn.cursor(cursor_factory=RealDictCursor) as cur:
            cur.execute("""
                INSERT INTO users (tenant_id, email, full_name, role)
                VALUES (%s, %s, %s, %s)
                RETURNING id, tenant_id, email, full_name, role, created_at;
            """, (tenant_id, email, full_name, role))
            user = cur.fetchone()
            conn.commit()
            return user

def get_system_setting(setting_key: str, tenant_id: str = None) -> dict:
    """Lấy cấu hình hệ thống (SERP, AI BYOK...)."""
    with get_connection() as conn:
        with conn.cursor(cursor_factory=RealDictCursor) as cur:
            if tenant_id:
                cur.execute("""
                    SELECT setting_value FROM system_settings 
                    WHERE tenant_id = %s AND setting_key = %s
                    LIMIT 1;
                """, (tenant_id, setting_key))
                row = cur.fetchone()
                if row and row.get("setting_value"):
                    return row["setting_value"]

            cur.execute("""
                SELECT setting_value FROM system_settings 
                WHERE tenant_id IS NULL AND setting_key = %s
                LIMIT 1;
            """, (setting_key,))
            row = cur.fetchone()
            return row["setting_value"] if row and row.get("setting_value") else {}

def save_system_setting(setting_key: str, setting_value: dict, tenant_id: str = None) -> bool:
    """Lưu hoặc cập nhật cấu hình hệ thống / tenant vào Database."""
    with get_connection() as conn:
        with conn.cursor() as cur:
            if tenant_id:
                cur.execute("""
                    INSERT INTO system_settings (tenant_id, setting_key, setting_value, updated_at)
                    VALUES (%s, %s, %s, NOW())
                    ON CONFLICT (COALESCE(tenant_id, '00000000-0000-0000-0000-000000000000'::uuid), setting_key)
                    DO UPDATE SET setting_value = EXCLUDED.setting_value, updated_at = NOW();
                """, (tenant_id, setting_key, json.dumps(setting_value, ensure_ascii=False)))
            else:
                cur.execute("""
                    INSERT INTO system_settings (tenant_id, setting_key, setting_value, updated_at)
                    VALUES (NULL, %s, %s, NOW())
                    ON CONFLICT (COALESCE(tenant_id, '00000000-0000-0000-0000-000000000000'::uuid), setting_key)
                    DO UPDATE SET setting_value = EXCLUDED.setting_value, updated_at = NOW();
                """, (setting_key, json.dumps(setting_value, ensure_ascii=False)))
            conn.commit()
            return True
