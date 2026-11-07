import json
from psycopg2.extras import RealDictCursor
from db_base import get_connection

def create_gsc_oauth_state(state: str, tenant_id: str, project_id: str, expires_at,
                           detected_property_uri: str = None):
    """Create a one-time, tenant-scoped OAuth state to prevent CSRF/project mixups."""
    with get_connection() as conn:
        with conn.cursor() as cur:
            cur.execute("""
                INSERT INTO gsc_oauth_states (
                    state, tenant_id, project_id, detected_property_uri, expires_at
                ) VALUES (%s, %s, %s, %s, %s)
                RETURNING state;
            """, (state, tenant_id, project_id, detected_property_uri, expires_at))
            result = cur.fetchone()[0]
            conn.commit()
            return result

def consume_gsc_oauth_state(state: str):
    """Atomically consume a valid OAuth state and return its tenant/project."""
    with get_connection() as conn:
        with conn.cursor(cursor_factory=RealDictCursor) as cur:
            cur.execute("""
                UPDATE gsc_oauth_states
                SET used_at = NOW()
                WHERE state = %s
                  AND used_at IS NULL
                  AND expires_at > NOW()
                RETURNING tenant_id, project_id, detected_property_uri;
            """, (state,))
            result = cur.fetchone()
            conn.commit()
            return result

def save_gsc_connection(tenant_id: str, project_id: str, encrypted_refresh_token: str,
                        scopes: list, google_account_email: str = None,
                        property_uri: str = None, status: str = "pending_property",
                        provider: str = "google_oauth", connection_owner: str = "marketing_engine"):
    with get_connection() as conn:
        with conn.cursor(cursor_factory=RealDictCursor) as cur:
            cur.execute("""
                INSERT INTO gsc_connections (
                    tenant_id, project_id, google_account_email, property_uri,
                    provider, connection_owner, encrypted_refresh_token, scopes, status, updated_at
                ) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, NOW())
                RETURNING id, tenant_id, project_id, google_account_email,
                          property_uri, provider, connection_owner, status, connected_at, updated_at;
            """, (tenant_id, project_id, google_account_email, property_uri,
                  provider, connection_owner, encrypted_refresh_token,
                  json.dumps(scopes or []), status))
            connection = cur.fetchone()
            conn.commit()
            return connection

def get_gsc_connection(project_id: str, tenant_id: str, connection_id: str = None,
                       property_uri: str = None):
    with get_connection() as conn:
        with conn.cursor(cursor_factory=RealDictCursor) as cur:
            conditions = ["project_id = %s", "tenant_id = %s", "connection_owner = 'marketing_engine'"]
            params = [project_id, tenant_id]
            if connection_id:
                conditions.append("id = %s")
                params.append(connection_id)
            if property_uri:
                conditions.append("property_uri = %s")
                params.append(property_uri)
            cur.execute(f"""
                SELECT * FROM gsc_connections
                WHERE {' AND '.join(conditions)}
                ORDER BY connected_at DESC LIMIT 1;
            """, tuple(params))
            return cur.fetchone()

def list_gsc_connections(project_id: str, tenant_id: str):
    with get_connection() as conn:
        with conn.cursor(cursor_factory=RealDictCursor) as cur:
            cur.execute("""
                SELECT id, tenant_id, project_id, google_account_email,
                       property_uri, provider, connection_owner, scopes, status, last_sync_at,
                       last_error, revoked_at, connected_at, updated_at
                FROM gsc_connections
                WHERE project_id = %s AND tenant_id = %s
                ORDER BY connected_at DESC;
            """, (project_id, tenant_id))
            return cur.fetchall()

def activate_gsc_connection(connection_id: str, tenant_id: str, project_id: str,
                            property_uri: str):
    with get_connection() as conn:
        with conn.cursor(cursor_factory=RealDictCursor) as cur:
            cur.execute("""
                UPDATE gsc_connections
                SET property_uri = %s, status = 'active', last_error = NULL, updated_at = NOW()
                WHERE id = %s AND tenant_id = %s AND project_id = %s
                RETURNING id, tenant_id, project_id, google_account_email,
                          property_uri, scopes, status, connected_at, updated_at;
            """, (property_uri, connection_id, tenant_id, project_id))
            result = cur.fetchone()
            conn.commit()
            return result

def mark_gsc_connection_error(connection_id: str, error: str):
    with get_connection() as conn:
        with conn.cursor() as cur:
            cur.execute(
                "UPDATE gsc_connections SET status = 'error', last_error = %s, updated_at = NOW() WHERE id = %s;",
                (error[:2000], connection_id),
            )
            conn.commit()

def save_gsc_performance_rows(tenant_id: str, project_id: str, property_uri: str,
                              start_date, end_date, dimensions: list, rows: list):
    with get_connection() as conn:
        with conn.cursor() as cur:
            cur.execute("""
                DELETE FROM gsc_performance_rows
                WHERE tenant_id = %s AND project_id = %s AND property_uri = %s
                  AND start_date = %s AND end_date = %s AND dimensions = %s::jsonb;
            """, (tenant_id, project_id, property_uri, start_date, end_date,
                 json.dumps(dimensions or [])))
            for row in rows:
                cur.execute("""
                    INSERT INTO gsc_performance_rows (
                        tenant_id, project_id, property_uri, start_date, end_date,
                        dimensions, row_data
                    ) VALUES (%s, %s, %s, %s, %s, %s, %s);
                """, (tenant_id, project_id, property_uri, start_date, end_date,
                     json.dumps(dimensions or []), json.dumps(row or {})))
            cur.execute(
                "UPDATE gsc_connections SET last_sync_at = NOW(), updated_at = NOW() WHERE project_id = %s AND tenant_id = %s AND property_uri = %s;",
                (project_id, tenant_id, property_uri),
            )
            conn.commit()

def get_gsc_performance_rows(tenant_id: str, project_id: str, property_uri: str,
                             start_date, end_date, dimensions: list = None):
    with get_connection() as conn:
        with conn.cursor(cursor_factory=RealDictCursor) as cur:
            query = """
                SELECT row_data, dimensions, start_date, end_date, fetched_at
                FROM gsc_performance_rows
                WHERE tenant_id = %s AND project_id = %s AND property_uri = %s
                  AND start_date >= %s AND end_date <= %s
            """
            params = [tenant_id, project_id, property_uri, start_date, end_date]
            if dimensions:
                query += " AND dimensions = %s::jsonb"
                params.append(json.dumps(dimensions))
            query += " ORDER BY fetched_at DESC;"
            cur.execute(query, tuple(params))
            return cur.fetchall()
