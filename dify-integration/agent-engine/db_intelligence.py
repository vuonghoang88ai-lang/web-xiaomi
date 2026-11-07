import json
from psycopg2.extras import RealDictCursor
from db_base import get_connection, _is_valid_uuid

def save_competitors(project_id: str, competitors_list: list):
    """Lưu danh sách 10 đối thủ cào được vào Database"""
    with get_connection() as conn:
        with conn.cursor() as cur:
            cur.execute("DELETE FROM project_competitors WHERE project_id = %s;", (project_id,))

            for comp in competitors_list:
                comp_type = comp.get("competitor_type") or ("paid_ads" if comp.get("is_ad") else "organic_seo")
                ad_info = comp.get("ad_details") or {}
                if not ad_info and comp.get("is_ad"):
                    ad_info = {
                        "headline": comp.get("title", ""),
                        "snippet": comp.get("serp_snippet", ""),
                        "position": comp.get("top_rank", 0)
                    }

                cur.execute("""
                    INSERT INTO project_competitors (
                        project_id, domain, url, top_rank, is_online,
                        competitor_type, ad_details,
                        scraped_structure, pricing_data, strengths, weaknesses
                    ) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);
                """, (
                    project_id,
                    comp.get("domain", ""),
                    comp.get("url", ""),
                    comp.get("top_rank", 0),
                    comp.get("is_online", True),
                    comp_type,
                    json.dumps(ad_info, ensure_ascii=False),
                    json.dumps(comp.get("scraped_structure", {}), ensure_ascii=False),
                    json.dumps(comp.get("pricing_data", {}), ensure_ascii=False),
                    comp.get("strengths", ""),
                    comp.get("weaknesses", "")
                ))
            conn.commit()

def update_competitor_deep_audit(project_id: str, competitor_domain_or_url: str, deep_audit: dict) -> bool:
    """Cập nhật kết quả phân tích 6 trụ cột chuyên sâu cho một đối thủ trong dự án."""
    with get_connection() as conn:
        with conn.cursor(cursor_factory=RealDictCursor) as cur:
            clean_domain = competitor_domain_or_url.replace("https://", "").replace("http://", "").split("/")[0].replace("www.", "")
            cur.execute("""
                SELECT id, scraped_structure FROM project_competitors 
                WHERE project_id = %s AND (domain ILIKE %s OR url ILIKE %s)
                LIMIT 1;
            """, (project_id, f"%{clean_domain}%", f"%{clean_domain}%"))
            row = cur.fetchone()
            if row:
                struct = row["scraped_structure"]
                if isinstance(struct, str):
                    try:
                        struct = json.loads(struct)
                    except Exception:
                        struct = {}
                elif not isinstance(struct, dict):
                    struct = {}
                struct["deep_audit"] = deep_audit
                kws_cnt = len(deep_audit.get("extracted_keywords", []))
                kws_str = f"Bóc tách {kws_cnt} từ khóa 4 tầng | " if kws_cnt > 0 else ""
                cur.execute("""
                    UPDATE project_competitors 
                    SET scraped_structure = %s,
                        strengths = COALESCE(%s, strengths),
                        weaknesses = COALESCE(%s, weaknesses)
                    WHERE id = %s;
                """, (
                    json.dumps(struct, ensure_ascii=False),
                    f"{kws_str}DA: {deep_audit.get('moz_da', 0)} | CMS: {deep_audit.get('cms_detected', 'N/A')} | Tuổi: {deep_audit.get('domain_age_years', 0)} năm",
                    f"TTFB: {deep_audit.get('ttfb_seconds', 0)}s | Schema: {len(deep_audit.get('schemas_found', []))} loại",
                    row["id"]
                ))
                conn.commit()
                return True
            return False

def save_project_target_analysis(project_id: str, target_analysis: dict, head_to_head: list = None) -> bool:
    """Lưu phân tích 6 trụ cột cho website đích (Target Website) và ma trận đối đầu vào brand_profile."""
    with get_connection() as conn:
        with conn.cursor(cursor_factory=RealDictCursor) as cur:
            cur.execute("SELECT brand_profile FROM projects WHERE id = %s;", (project_id,))
            row = cur.fetchone()
            if not row:
                return False
            bp = row["brand_profile"]
            if isinstance(bp, str):
                try:
                    bp = json.loads(bp)
                except Exception:
                    bp = {}
            elif not isinstance(bp, dict):
                bp = {}
            
            bp["target_analysis"] = target_analysis
            bp["my_site_audit"] = target_analysis
            if head_to_head:
                bp["head_to_head"] = head_to_head
            
            cur.execute("""
                UPDATE projects 
                SET brand_profile = %s, updated_at = NOW() 
                WHERE id = %s;
            """, (json.dumps(bp, ensure_ascii=False), project_id))
            conn.commit()
            return True

def save_market_report(project_id: str, report_type: str, category_gap: dict,
                       eeat_gap: dict, cro_gap: dict, strategic_roadmap: str):
    """Lưu báo cáo phân tích khoảng trống và chiến lược thị trường"""
    with get_connection() as conn:
        with conn.cursor(cursor_factory=RealDictCursor) as cur:
            cur.execute("""
                INSERT INTO market_intelligence_reports (
                    project_id, report_type, category_gap, eeat_gap, cro_gap, strategic_roadmap
                ) VALUES (%s, %s, %s, %s, %s, %s)
                RETURNING id;
            """, (
                project_id,
                report_type,
                json.dumps(category_gap or {}, ensure_ascii=False),
                json.dumps(eeat_gap or {}, ensure_ascii=False),
                json.dumps(cro_gap or {}, ensure_ascii=False),
                strategic_roadmap
            ))
            report_id = cur.fetchone()["id"]
            conn.commit()
            return str(report_id)

def get_project_full_intelligence(project_id: str, tenant_id: str = None):
    """Lấy toàn bộ trí tuệ dự án (Thông tin web, 10 đối thủ, Báo cáo Gap Analysis)"""
    if not _is_valid_uuid(project_id):
        return None
    if tenant_id and not _is_valid_uuid(tenant_id):
        return None
    with get_connection() as conn:
        with conn.cursor(cursor_factory=RealDictCursor) as cur:
            if tenant_id:
                cur.execute(
                    "SELECT * FROM projects WHERE id = %s AND tenant_id = %s;",
                    (project_id, tenant_id),
                )
            else:
                cur.execute("SELECT * FROM projects WHERE id = %s;", (project_id,))
            project = cur.fetchone()
            if not project:
                return None

            cur.execute("SELECT * FROM project_competitors WHERE project_id = %s ORDER BY top_rank ASC;", (project_id,))
            competitors = cur.fetchall()

            cur.execute("""
                SELECT * FROM market_intelligence_reports 
                WHERE project_id = %s 
                ORDER BY created_at DESC LIMIT 1;
            """, (project_id,))
            latest_report = cur.fetchone()

            cur.execute("""
                SELECT id, property_uri, status, last_sync_at, last_error, connected_at 
                FROM gsc_connections 
                WHERE project_id = %s 
                ORDER BY connected_at DESC;
            """, (project_id,))
            gsc_connections = cur.fetchall()

            return {
                "project": project,
                "competitors": competitors,
                "latest_report": latest_report,
                "gsc_connections": gsc_connections
            }
