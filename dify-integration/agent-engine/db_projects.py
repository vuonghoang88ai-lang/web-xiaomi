import json
from psycopg2.extras import RealDictCursor
from db_base import get_connection, _is_valid_uuid

def save_project(tenant_id: str, name: str, target_website_url: str, business_description: str,
                 seed_competitor_url: str = None, target_location: str = "",
                 core_strengths: str = None, suggested_uvp_options: list = None,
                 dify_dataset_id: str = None, brand_logo_url: str = None, brand_logo_path: str = None,
                 brand_profile: dict = None):
    """Lưu hoặc cập nhật thông tin Dự án Website vào Database"""
    with get_connection() as conn:
        with conn.cursor(cursor_factory=RealDictCursor) as cur:
            cur.execute("""
                SELECT id FROM projects 
                WHERE tenant_id = %s AND target_website_url = %s
                LIMIT 1;
            """, (tenant_id, target_website_url))
            existing = cur.fetchone()

            if existing:
                project_id = existing["id"]
                cur.execute("""
                    UPDATE projects
                    SET name = %s,
                        business_description = %s,
                        seed_competitor_url = %s,
                        target_location = %s,
                        brand_profile = %s,
                        core_strengths = COALESCE(%s, core_strengths),
                        suggested_uvp_options = %s,
                        dify_dataset_id = COALESCE(%s, dify_dataset_id),
                        brand_logo_url = COALESCE(%s, brand_logo_url),
                        brand_logo_path = COALESCE(%s, brand_logo_path),
                        updated_at = NOW()
                    WHERE id = %s
                    RETURNING id;
                """, (name, business_description, seed_competitor_url, target_location,
                      json.dumps(brand_profile or {}, ensure_ascii=False),
                      core_strengths, json.dumps(suggested_uvp_options or [], ensure_ascii=False),
                      dify_dataset_id, brand_logo_url, brand_logo_path, project_id))
            else:
                cur.execute("""
                    INSERT INTO projects (tenant_id, name, target_website_url, business_description,
                                          seed_competitor_url, target_location, brand_profile, core_strengths,
                                          suggested_uvp_options, dify_dataset_id, brand_logo_url, brand_logo_path)
                    VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s)
                    RETURNING id;
                """, (tenant_id, name, target_website_url, business_description,
                      seed_competitor_url, target_location,
                      json.dumps(brand_profile or {}, ensure_ascii=False), core_strengths,
                      json.dumps(suggested_uvp_options or [], ensure_ascii=False),
                      dify_dataset_id, brand_logo_url, brand_logo_path))
                project_id = cur.fetchone()["id"]

            conn.commit()
            return str(project_id)

def update_project_uvp(project_id: str, tenant_id: str, chosen_text: str) -> bool:
    """Update UVP only when the project belongs to the specified tenant."""
    with get_connection() as conn:
        with conn.cursor() as cur:
            cur.execute("""
                UPDATE projects
                SET core_strengths = %s, updated_at = NOW()
                WHERE id = %s AND tenant_id = %s;
            """, (chosen_text, project_id, tenant_id))
            updated = cur.rowcount == 1
            conn.commit()
            return updated

def update_project_info(project_id: str, name: str, target_website_url: str,
                        business_description: str, seed_competitor_url: str = None,
                        target_location: str = "") -> bool:
    """Cập nhật các thông tin cơ bản của Dự án từ giao diện Sửa Dự Án"""
    with get_connection() as conn:
        with conn.cursor() as cur:
            cur.execute("""
                UPDATE projects
                SET name = %s,
                    target_website_url = %s,
                    business_description = %s,
                    seed_competitor_url = %s,
                    target_location = %s,
                    updated_at = NOW()
                WHERE id = %s;
            """, (name, target_website_url, business_description, seed_competitor_url, target_location, project_id))
            updated = cur.rowcount >= 1
            conn.commit()
            return updated

def delete_project(project_id: str) -> bool:
    """Xóa Dự án và toàn bộ dữ liệu phụ thuộc (cascade xóa competitors, reports, gsc)"""
    with get_connection() as conn:
        with conn.cursor() as cur:
            cur.execute("DELETE FROM projects WHERE id = %s;", (project_id,))
            deleted = cur.rowcount >= 1
            conn.commit()
            return deleted

def get_or_create_active_project(tenant_id: str) -> dict:
    """Lấy dự án đang hoạt động gần nhất của tenant hoặc khởi tạo mặc định."""
    with get_connection() as conn:
        with conn.cursor(cursor_factory=RealDictCursor) as cur:
            cur.execute("""
                SELECT * FROM projects 
                WHERE tenant_id = %s 
                ORDER BY updated_at DESC LIMIT 1;
            """, (tenant_id,))
            proj = cur.fetchone()
            if proj:
                return dict(proj)
            cur.execute("""
                INSERT INTO projects (tenant_id, name, target_website_url, business_description, seed_competitor_url, target_location)
                VALUES (%s, 'Dự Án Mặc Định', 'https://example.com', 'Dự án kinh doanh trực tuyến', NULL, 'Toàn quốc')
                RETURNING *;
            """, (tenant_id,))
            new_proj = cur.fetchone()
            conn.commit()
            return dict(new_proj)

def update_project_fields(tenant_id: str, fields: dict) -> dict:
    """Cập nhật linh hoạt các trường thông tin bóc tách được vào Project."""
    with get_connection() as conn:
        with conn.cursor(cursor_factory=RealDictCursor) as cur:
            proj = get_or_create_active_project(tenant_id)
            project_id = proj["id"]

            updates = []
            params = []
            allowed = [
                "target_website_url", "seed_competitor_url", "business_description",
                "core_strengths", "target_location", "name"
            ]
            for key in allowed:
                val = fields.get(key)
                if val:
                    updates.append(f"{key} = %s")
                    params.append(val.strip())

            if updates:
                updates.append("updated_at = NOW()")
                sql = f"UPDATE projects SET {', '.join(updates)} WHERE id = %s RETURNING *;"
                params.append(project_id)
                cur.execute(sql, tuple(params))
                updated_proj = cur.fetchone()
                conn.commit()
                return dict(updated_proj)
            return dict(proj)

def list_projects_by_tenant(tenant_id: str) -> list:
    """Lấy danh sách tất cả các Project thuộc về một Tenant."""
    with get_connection() as conn:
        with conn.cursor(cursor_factory=RealDictCursor) as cur:
            cur.execute("""
                SELECT p.*,
                       (SELECT COUNT(*) FROM project_competitors pc WHERE pc.project_id = p.id) as competitors_count
                FROM projects p
                WHERE p.tenant_id = %s
                ORDER BY p.updated_at DESC;
            """, (tenant_id,))
            return [dict(row) for row in cur.fetchall()]

def get_project_by_id(project_id: str, tenant_id: str = None) -> dict:
    """Lấy thông tin chi tiết của 1 Project theo ID."""
    if not _is_valid_uuid(project_id):
        return None
    if tenant_id and not _is_valid_uuid(tenant_id):
        return None
    with get_connection() as conn:
        with conn.cursor(cursor_factory=RealDictCursor) as cur:
            if tenant_id:
                cur.execute("SELECT * FROM projects WHERE id = %s AND tenant_id = %s LIMIT 1;", (project_id, tenant_id))
            else:
                cur.execute("SELECT * FROM projects WHERE id = %s LIMIT 1;", (project_id,))
            row = cur.fetchone()
            return dict(row) if row else None

def update_project_conversation_id(project_id: str, conversation_id: str) -> bool:
    """Lưu gắn chặt conversation_id của Dify vào Project để F5 không mất phiên."""
    if not _is_valid_uuid(project_id):
        return False
    with get_connection() as conn:
        with conn.cursor() as cur:
            cur.execute("""
                UPDATE projects 
                SET dify_conversation_id = %s, updated_at = NOW() 
                WHERE id = %s;
            """, (conversation_id, project_id))
            conn.commit()
            return cur.rowcount > 0

def reset_project_conversation_id(project_id: str) -> bool:
    """Xóa trắng conversation_id để AI bắt đầu một phiên hội thoại mới khi người dùng yêu cầu."""
    if not _is_valid_uuid(project_id):
        return False
    with get_connection() as conn:
        with conn.cursor() as cur:
            cur.execute("""
                UPDATE projects 
                SET dify_conversation_id = NULL, updated_at = NOW() 
                WHERE id = %s;
            """, (project_id,))
            conn.commit()
            return cur.rowcount > 0
