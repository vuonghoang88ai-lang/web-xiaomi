"""
Module: db_chat_sessions.py
Mục đích: Quản lý lưu trữ Đa phiên trò chuyện (Multi-session Conversations) cho từng Dự án.
Tuân thủ nghiêm ngặt SOP 23 (<= 300 dòng code).
"""
import logging
from psycopg2.extras import RealDictCursor
from db_base import get_connection, _is_valid_uuid

logger = logging.getLogger("db_chat_sessions")

def get_project_conversations(project_id: str) -> list:
    """Lấy danh sách các phiên trò chuyện của một dự án, sắp xếp mới nhất lên đầu."""
    if not _is_valid_uuid(project_id):
        return []
    with get_connection() as conn:
        with conn.cursor(cursor_factory=RealDictCursor) as cur:
            cur.execute("""
                SELECT id, project_id, dify_conversation_id, title,
                       message_count, is_pinned, created_at, updated_at
                FROM chat_conversations
                WHERE project_id = %s
                ORDER BY is_pinned DESC, updated_at DESC;
            """, (project_id,))
            rows = cur.fetchall()
            return [dict(r) for r in (rows or [])]

def get_conversation_by_id(conversation_id: str) -> dict:
    """Lấy chi tiết 1 phiên trò chuyện theo ID khoá chính."""
    if not _is_valid_uuid(conversation_id):
        return None
    with get_connection() as conn:
        with conn.cursor(cursor_factory=RealDictCursor) as cur:
            cur.execute("""
                SELECT id, project_id, dify_conversation_id, title,
                       message_count, is_pinned, created_at, updated_at
                FROM chat_conversations
                WHERE id = %s
                LIMIT 1;
            """, (conversation_id,))
            row = cur.fetchone()
            return dict(row) if row else None

def get_conversation_by_dify_id(project_id: str, dify_conversation_id: str) -> dict:
    """Lấy phiên trò chuyện dựa trên dify_conversation_id."""
    if not _is_valid_uuid(project_id) or not dify_conversation_id:
        return None
    with get_connection() as conn:
        with conn.cursor(cursor_factory=RealDictCursor) as cur:
            cur.execute("""
                SELECT id, project_id, dify_conversation_id, title,
                       message_count, is_pinned, created_at, updated_at
                FROM chat_conversations
                WHERE project_id = %s AND dify_conversation_id = %s
                LIMIT 1;
            """, (project_id, dify_conversation_id))
            row = cur.fetchone()
            return dict(row) if row else None

def create_or_get_conversation(project_id: str, dify_conversation_id: str, title: str = "Cuộc trò chuyện mới") -> dict:
    """Đảm bảo phiên trò chuyện tồn tại; nếu chưa có thì tạo mới."""
    if not _is_valid_uuid(project_id) or not dify_conversation_id:
        return None
    existing = get_conversation_by_dify_id(project_id, dify_conversation_id)
    if existing:
        return existing
    clean_title = (title or "Cuộc trò chuyện mới").strip()[:200]
    with get_connection() as conn:
        with conn.cursor(cursor_factory=RealDictCursor) as cur:
            cur.execute("""
                INSERT INTO chat_conversations (project_id, dify_conversation_id, title, message_count)
                VALUES (%s, %s, %s, 1)
                ON CONFLICT DO NOTHING
                RETURNING id, project_id, dify_conversation_id, title, message_count, is_pinned, created_at, updated_at;
            """, (project_id, dify_conversation_id, clean_title))
            row = cur.fetchone()
            if not row:
                return get_conversation_by_dify_id(project_id, dify_conversation_id)
            return dict(row)

def update_conversation_title(conversation_id: str, new_title: str) -> bool:
    """Cập nhật tiêu đề hiển thị cho phiên trò chuyện."""
    if not _is_valid_uuid(conversation_id) or not new_title:
        return False
    clean_title = new_title.strip()[:200]
    with get_connection() as conn:
        with conn.cursor() as cur:
            cur.execute("""
                UPDATE chat_conversations
                SET title = %s, updated_at = NOW()
                WHERE id = %s;
            """, (clean_title, conversation_id))
            return cur.rowcount > 0

def touch_conversation(project_id: str, dify_conversation_id: str, title: str = None, delta_msgs: int = 1) -> dict:
    """Cập nhật thời gian hoạt động mới nhất và tăng số lượng tin nhắn cho phiên."""
    if not _is_valid_uuid(project_id) or not dify_conversation_id:
        return None
    conv = get_conversation_by_dify_id(project_id, dify_conversation_id)
    if not conv:
        return create_or_get_conversation(project_id, dify_conversation_id, title or "Cuộc trò chuyện mới")
    
    with get_connection() as conn:
        with conn.cursor(cursor_factory=RealDictCursor) as cur:
            if title and conv.get("title") in ("Cuộc trò chuyện mới", "New conversation"):
                cur.execute("""
                    UPDATE chat_conversations
                    SET message_count = message_count + %s,
                        title = %s,
                        updated_at = NOW()
                    WHERE id = %s
                    RETURNING *;
                """, (max(0, delta_msgs), title.strip()[:200], conv["id"]))
            else:
                cur.execute("""
                    UPDATE chat_conversations
                    SET message_count = message_count + %s,
                        updated_at = NOW()
                    WHERE id = %s
                    RETURNING *;
                """, (max(0, delta_msgs), conv["id"]))
            row = cur.fetchone()
            return dict(row) if row else conv

def delete_conversation(conversation_id: str) -> dict:
    """Xóa 1 phiên trò chuyện khỏi hệ thống, trả về dify_conversation_id đã xóa."""
    if not _is_valid_uuid(conversation_id):
        return None
    with get_connection() as conn:
        with conn.cursor(cursor_factory=RealDictCursor) as cur:
            cur.execute("""
                DELETE FROM chat_conversations
                WHERE id = %s
                RETURNING id, project_id, dify_conversation_id, title;
            """, (conversation_id,))
            row = cur.fetchone()
            return dict(row) if row else None

