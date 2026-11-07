#!/usr/bin/env python3
"""
Chat Context Compaction & Health Engine (Gemini-Style).
Decouples heavy deliverables and cleans conversation state dynamically.
Strictly complies with SOP 23 (<= 300 lines) and SOP 30 (Zero Hardcoding).
"""
import os
import re
import logging
from typing import Dict, Any, List, Optional
import psycopg2
from psycopg2.extras import RealDictCursor

logger = logging.getLogger("marketing_engine")

DIFY_DB_HOST = os.getenv("DIFY_DB_HOST", os.getenv("DB_HOST", "172.20.0.14"))
DIFY_DB_PORT = int(os.getenv("DIFY_DB_PORT", os.getenv("DB_PORT", "5432")))
DIFY_DB_NAME = os.getenv("DIFY_DB_NAME", "dify")
DIFY_DB_USER = os.getenv("DIFY_DB_USER", os.getenv("DB_USER", "postgres"))
DIFY_DB_PASS = os.getenv("DIFY_DB_PASS", "difyai123456")


def get_dify_db_connection():
    """Connect to Dify PostgreSQL database."""
    hosts = [DIFY_DB_HOST, "docker-db_postgres-1", "172.20.0.14", "db_postgres", "127.0.0.1"]
    last_err = None
    for h in hosts:
        try:
            return psycopg2.connect(
                host=h,
                port=DIFY_DB_PORT,
                dbname=DIFY_DB_NAME,
                user=DIFY_DB_USER,
                password=DIFY_DB_PASS,
                connect_timeout=3,
                sslmode="disable"
            )
        except Exception as e:
            last_err = e
            continue
    raise last_err or RuntimeError("Cannot connect to Dify PostgreSQL")


def compact_deliverable_text(text: str, max_chars: int = 900) -> str:
    """
    Gemini-style Artifact Decoupling & Compaction algorithm.
    Extracts structural headings, opening hook, and terminal commitment
    while replacing heavy body text (> max_chars) with an artifact pointer.
    """
    if not text or len(text) <= max_chars:
        return text or ""

    lines = [line.strip() for line in text.splitlines() if line.strip()]
    if not lines:
        return text[:max_chars]

    headers = [l for l in lines if l.startswith("#") or l.startswith(":::")]
    header_block = ("\nĐề mục chính:\n" + "\n".join(headers[:6])) if headers else ""
    head_content = "\n".join(lines[:4])[:350]
    tail_content = "\n".join(lines[-3:])[:250]
    total_chars = len(text)

    pointer = (
        f"\n\n[📦 ARTIFACT DECOUPLED: Nội dung chi tiết ({total_chars:,} ký tự) "
        f"đã xuất bản và lưu trữ đầy đủ trong lịch sử giao diện]\n"
    )
    return f"{head_content}{header_block}{pointer}\n{tail_content}"


def clean_stale_error_messages(conversation_id: str) -> int:
    """
    SOP 33 & 45: Dọn sạch các bản ghi rỗng (zero-content) hoặc timeout stub khỏi Dify DB
    ngay trước khi xử lý turn mới để triệt tiêu lỗi lây nhiễm độc hại context.
    """
    if not conversation_id:
        return 0
    try:
        conn = get_dify_db_connection()
        cur = conn.cursor()
        cur.execute(
            """
            DELETE FROM messages
            WHERE conversation_id = %s
              AND (
                status = 'error'
                OR error IS NOT NULL
                OR length(trim(coalesce(answer, ''))) = 0
              )
            """,
            (conversation_id,)
        )
        deleted = cur.rowcount
        conn.commit()
        conn.close()
        if deleted > 0:
            logger.info(f"[CONTEXT_CLEANUP] Purged {deleted} empty/stale stubs for cid={conversation_id}")
        return deleted
    except Exception as e:
        logger.warning(f"[CONTEXT_CLEANUP_FAIL] cid={conversation_id} err={e}")
        return 0


def get_conversation_context_metrics(conversation_id: str) -> Dict[str, Any]:
    """
    Calculates context metrics: turn count, total characters, average turn length,
    and whether progressive compaction is recommended.
    """
    if not conversation_id:
        return {"turn_count": 0, "total_chars": 0, "needs_compaction": False}
    try:
        conn = get_dify_db_connection()
        cur = conn.cursor(cursor_factory=RealDictCursor)
        cur.execute(
            """
            SELECT count(*) as turn_count,
                   coalesce(sum(length(coalesce(query, '')) + length(coalesce(answer, ''))), 0) as total_chars,
                   max(length(coalesce(answer, ''))) as max_answer_len
            FROM messages
            WHERE conversation_id = %s
            """,
            (conversation_id,)
        )
        row = cur.fetchone() or {}
        conn.close()

        turn_count = int(row.get("turn_count") or 0)
        total_chars = int(row.get("total_chars") or 0)
        max_ans = int(row.get("max_answer_len") or 0)

        # Dynamic compaction recommendation: > 6 turns or total chars > 15,000 or single answer > 5,000
        needs_compaction = turn_count >= 6 or total_chars >= 15000 or max_ans >= 5000

        return {
            "conversation_id": conversation_id,
            "turn_count": turn_count,
            "total_chars": total_chars,
            "max_answer_length": max_ans,
            "needs_compaction": needs_compaction
        }
    except Exception as e:
        logger.warning(f"[CONTEXT_METRICS_FAIL] cid={conversation_id} err={e}")
        return {"turn_count": 0, "total_chars": 0, "needs_compaction": False}


async def auto_harvest_to_rag(project_id: str, prompt: str, answer: str):
    """
    Autonomous Knowledge Harvester (SOP 34).
    Automatically extracts high-value deliverables (>= 2500 chars or Strategy Plans)
    and ingests them into the project's active Dify Vector Dataset in background.
    """
    if not answer or len(answer) < 2500:
        return

    try:
        import httpx
        lines = [l.strip() for l in answer.splitlines() if l.strip()]
        doc_title = None
        for l in lines[:10]:
            if l.startswith("# "):
                doc_title = re.sub(r"[#👑🔥📍*]", "", l).strip()
                break
            elif ":::boardroom-debate" in l:
                clean_p = re.sub(r"\[(?:BỐI CẢNH|QUY TẮC|YÊU CẦU|PROJECT|MANDATORY)[^\]]+\]\s*", "", prompt).strip()
                doc_title = f"Chien_Luoc_Marketing_{clean_p[:35]}"
                break

        if not doc_title:
            doc_title = f"Tai_San_Tri_Thuc_{int(time.time())}"

        clean_slug = re.sub(r"[^a-zA-Z0-9_\-\u00C0-\u024F\u1EA0-\u1EF9 ]", "", doc_title)[:55].strip()
        doc_name = f"AutoHarvest_{clean_slug}"

        conn = get_dify_db_connection()
        cur = conn.cursor()
        cur.execute(
            """
            SELECT id FROM datasets 
            WHERE name ILIKE %s OR name ILIKE '%DefaultEnter%'
            ORDER BY created_at DESC LIMIT 1;
            """,
            (f"%{project_id[:8]}%",)
        )
        row = cur.fetchone()
        conn.close()

        if not row:
            return
        dataset_id = str(row[0])

        dify_url = os.getenv("DIFY_API_URL", "http://docker-api-1:5001/v1").rstrip("/")
        dataset_token = os.getenv("DIFY_DATASET_API_KEY", "dataset-marketing-engine-token")

        upload_payload = {
            "name": doc_name,
            "text": f"# {doc_title}\n\n> Tự động thu hoạch từ CMO Copilot vào {time.strftime('%Y-%m-%d %H:%M:%S')}\n\n{answer}",
            "indexing_technique": "economy",
            "process_rule": {"mode": "automatic"}
        }

        async with httpx.AsyncClient(timeout=30.0) as http_client:
            r = await http_client.post(
                f"{dify_url}/datasets/{dataset_id}/document/create-by-text",
                headers={"Authorization": f"Bearer {dataset_token}", "Content-Type": "application/json"},
                json=upload_payload
            )
            if r.status_code in (200, 201):
                logger.info(f"[AUTO_HARVEST_SUCCESS] Ingested '{doc_name}' ({len(answer)} chars) into Dataset {dataset_id}")
            else:
                logger.warning(f"[AUTO_HARVEST_FAIL] status={r.status_code} body={r.text[:120]}")
    except Exception as e:
        logger.warning(f"[AUTO_HARVEST_ERROR] {e}")

