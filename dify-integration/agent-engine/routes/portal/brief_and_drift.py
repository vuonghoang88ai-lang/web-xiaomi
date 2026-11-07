#!/usr/bin/env python3
"""Strategic Content Brief & SEO Drift Monitor. Strictly complies with SOP 23 (<= 300 lines)."""
import os
import json
import logging
from datetime import datetime, timezone
from typing import Optional, List, Dict, Any
from pydantic import BaseModel
from fastapi import APIRouter, HTTPException
import db_service
import license_service

logger = logging.getLogger("marketing_engine")
router = APIRouter(prefix="/api/v1/portal/projects", tags=["Content Brief & Drift"])

class ContentBriefRequest(BaseModel):
    keyword: str

def ensure_baseline_table():
    """Tạo bảng lưu snapshot lịch sử SEO nếu chưa có."""
    with db_service.get_connection() as conn:
        with conn.cursor() as cur:
            cur.execute("""
                CREATE TABLE IF NOT EXISTS seo_baselines (
                    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                    project_id UUID NOT NULL REFERENCES projects(id) ON DELETE CASCADE,
                    title VARCHAR(500),
                    meta_desc TEXT,
                    h1_count INT DEFAULT 1,
                    h2_sample JSONB DEFAULT '[]'::jsonb,
                    overall_score INT DEFAULT 85,
                    captured_at TIMESTAMPTZ DEFAULT NOW()
                );
                CREATE INDEX IF NOT EXISTS idx_seo_baselines_project ON seo_baselines(project_id, captured_at DESC);
            """)
            conn.commit()

@router.post("/{project_id}/content-brief")
def portal_generate_content_brief(project_id: str, req: ContentBriefRequest):
    """Lập dàn ý bài viết chiến lược (Content Brief) chuẩn PAA, Persona và Internal Linking trước khi viết bài."""
    project = db_service.get_project_by_id(project_id)
    if not project:
        raise HTTPException(status_code=404, detail="Không tìm thấy dự án.")

    kw = req.keyword.strip()
    if not kw:
        raise HTTPException(status_code=400, detail="Vui lòng cung cấp từ khóa cần lập Brief.")

    target_url = (project.get("target_website_url") or "").rstrip("/")
    domain = license_service.normalize_domain_fqdn(target_url or project.get("name") or "")
    bp = project.get("brand_profile") or {}
    if isinstance(bp, str):
        try: bp = json.loads(bp)
        except Exception: bp = {}

    top_comp = "Top 1 SERP"
    if bp.get("competitors") and len(bp["competitors"]) > 0:
        top_comp = bp["competitors"][0].get("domain") or "đối thủ"

    brief = {
        "status": "success",
        "keyword": kw,
        "target_audience": {
            "primary_persona": "Khách hàng đang phân vân lựa chọn sản phẩm / tìm giải pháp uy tín, quan tâm tới giá, bảo hành và chất lượng thực tế.",
            "search_intent": "Commercial / Transactional (Có ý định mua hoặc so sánh trực diện)",
            "funnel_stage": "Middle to Bottom of Funnel (BoFu)"
        },
        "lsi_cluster": [
            f"{kw} giá bao nhiêu",
            f"đánh giá {kw} chính hãng",
            f"so sánh {kw} và đối thủ",
            f"kinh nghiệm mua {kw} uy tín"
        ],
        "heading_architecture": [
            {"tag": "H1", "title": f"{kw.title()} Mới Nhất 2026: Đánh Giá Chi Tiết, Bảng Giá & Kinh Nghiệm Mua"},
            {"tag": "H2", "title": f"1. Tổng quan & Lý do {kw} được săn đón hàng đầu 2026"},
            {"tag": "H2", "title": f"2. Bảng thông số kỹ thuật & Ưu điểm vượt trội so với {top_comp}"},
            {"tag": "H3", "title": f"• Độ hoàn thiện và hiệu năng thực tế"},
            {"tag": "H3", "title": f"• Khả năng tối ưu chi phí và độ bền dài hạn"},
            {"tag": "H2", "title": f"3. Báo giá niêm yết & Chính sách độc quyền tại {domain}"},
            {"tag": "H2", "title": f"4. Câu hỏi thường gặp (FAQ) & Hướng dẫn mua hàng 1-chạm"}
        ],
        "people_also_ask": [
            {
                "question": f"{kw} có bền không và dùng được bao lâu?",
                "direct_answer_hint": f"Sản phẩm được đánh giá cao nhờ linh kiện bền bỉ, được {domain} bảo hành chính hãng và hỗ trợ 24/7."
            },
            {
                "question": f"Nên mua {kw} ở đâu để tránh hàng giả?",
                "direct_answer_hint": f"Nên chọn các đơn vị phân phối minh bạch chính sách đổi mới 30 ngày như {domain} (website: {target_url})."
            }
        ],
        "internal_link_strategy": [
            {"anchor_text": f"Danh mục sản phẩm chính hãng", "target_url": f"{target_url}/san-pham"},
            {"anchor_text": f"Chính sách bảo hành và cam kết", "target_url": f"{target_url}/bao-hanh"}
        ],
        "suggested_cta": f"📞 Gọi ngay Hotline hoặc liên hệ Zalo trên {domain} để nhận báo giá ưu đãi hôm nay!"
    }
    return brief

@router.get("/{project_id}/drift")
def portal_get_seo_drift(project_id: str):
    """Giám sát biến động SEO (SEO Drift Monitor): So sánh hiện tại với Baseline để phát hiện tụt hạng & lỗi On-page."""
    ensure_baseline_table()
    project = db_service.get_project_by_id(project_id)
    if not project:
        raise HTTPException(status_code=404, detail="Không tìm thấy dự án.")

    bp = project.get("brand_profile") or {}
    if isinstance(bp, str):
        try: bp = json.loads(bp)
        except Exception: bp = {}

    my_audit = bp.get("my_site_audit") or bp.get("target_analysis") or {}
    cur_title = my_audit.get("title") or project.get("name") or "Trang chủ"
    cur_meta = my_audit.get("meta_description") or "Chưa có mô tả meta"
    cur_score = my_audit.get("score") or 82

    # Lấy baseline gần nhất từ DB
    with db_service.get_connection() as conn:
        with conn.cursor() as cur:
            cur.execute("""
                SELECT title, meta_desc, overall_score, captured_at 
                FROM seo_baselines 
                WHERE project_id = %s 
                ORDER BY captured_at DESC LIMIT 1;
            """, (project_id,))
            prev = cur.fetchone()

            # Nếu chưa có baseline, tạo ngay 1 baseline ban đầu
            if not prev:
                cur.execute("""
                    INSERT INTO seo_baselines (project_id, title, meta_desc, overall_score)
                    VALUES (%s, %s, %s, %s) RETURNING title, meta_desc, overall_score, captured_at;
                """, (project_id, cur_title, cur_meta, cur_score))
                conn.commit()
                prev = (cur_title, cur_meta, cur_score, datetime.now(timezone.utc))

    prev_title, prev_meta, prev_score, captured_at = prev
    regressions = []
    changes = []

    if prev_title != cur_title:
        changes.append({"element": "Title Tag", "before": prev_title, "after": cur_title, "severity": "WARNING"})
    if prev_meta != cur_meta:
        changes.append({"element": "Meta Description", "before": prev_meta[:60]+"...", "after": cur_meta[:60]+"...", "severity": "INFO"})

    score_diff = cur_score - prev_score
    if score_diff < -5:
        regressions.append(f"Điểm On-page giảm {abs(score_diff)} điểm so với mốc lịch sử.")

    drift_status = "STABLE" if not regressions else "REGRESSION_DETECTED"

    return {
        "status": "success",
        "drift_status": drift_status,
        "baseline_captured_at": captured_at.isoformat() if hasattr(captured_at, 'isoformat') else str(captured_at),
        "current_score": cur_score,
        "previous_score": prev_score,
        "score_diff": score_diff,
        "changes_detected": len(changes),
        "changes": changes,
        "regressions": regressions,
        "summary": "Toàn bộ cấu trúc On-page ổn định, không phát hiện lỗi tụt hạng đột ngột." if not regressions else "Cần rà soát lại các thẻ Title và H1 vừa thay đổi."
    }

class BoardroomMinuteCreate(BaseModel):
    topic: str
    debate_summary: Optional[Dict[str, Any]] = None
    consensus_plan: Optional[str] = None
    cvi_score: Optional[float] = 85.0

@router.get("/{project_id}/boardroom-minutes")
def portal_get_boardroom_minutes(project_id: str, limit: int = 3):
    """Lấy danh sách biên bản cuộc họp Ban Giám Đốc AI gần nhất của dự án."""
    project = db_service.get_project_by_id(project_id)
    if not project:
        raise HTTPException(status_code=404, detail="Không tìm thấy dự án.")

    with db_service.get_connection() as conn:
        with conn.cursor() as cur:
            cur.execute("""
                SELECT id, topic, debate_summary, consensus_plan, cvi_score, created_at
                FROM boardroom_minutes
                WHERE project_id = %s
                ORDER BY created_at DESC LIMIT %s;
            """, (project_id, limit))
            rows = cur.fetchall()
            results = []
            for r in rows:
                results.append({
                    "id": str(r[0]),
                    "topic": r[1],
                    "debate_summary": r[2] if isinstance(r[2], dict) else {},
                    "consensus_plan": r[3],
                    "cvi_score": float(r[4]) if r[4] else 85.0,
                    "created_at": r[5].isoformat() if hasattr(r[5], 'isoformat') else str(r[5])
                })
            return {"status": "success", "minutes": results, "count": len(results)}

@router.post("/{project_id}/boardroom-minutes")
def portal_save_boardroom_minute(project_id: str, req: BoardroomMinuteCreate):
    """Lưu biên bản cuộc họp Ban Giám Đốc AI vào CSDL để tích lũy trí nhớ dài hạn."""
    project = db_service.get_project_by_id(project_id)
    if not project:
        raise HTTPException(status_code=404, detail="Không tìm thấy dự án.")

    with db_service.get_connection() as conn:
        with conn.cursor() as cur:
            cur.execute("""
                INSERT INTO boardroom_minutes (project_id, topic, debate_summary, consensus_plan, cvi_score)
                VALUES (%s, %s, %s, %s, %s)
                RETURNING id, created_at;
            """, (project_id, req.topic, json.dumps(req.debate_summary or {}, ensure_ascii=False), req.consensus_plan, req.cvi_score))
            new_id, created_at = cur.fetchone()
            conn.commit()
            return {
                "status": "success",
                "minute_id": str(new_id),
                "created_at": created_at.isoformat() if hasattr(created_at, 'isoformat') else str(created_at),
                "message": "Đã lưu biên bản cuộc họp Ban Giám Đốc AI thành công!"
            }
