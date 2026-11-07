#!/usr/bin/env python3
"""Onboarding gap analysis workflow: combines website inspection, SERP discovery, UVP suggestions & RAG sync."""
import json
import logging
from typing import Optional, Dict

import db_service
import rag_service
from serp_site_inspector import normalize_website_url, inspect_website
from serp_crawler import discover_top_10_competitors
from serp_uvp_analyzer import call_llm, generate_uvp_suggestions, analyze_and_optimize_user_uvp

logger = logging.getLogger("serp_discovery")

def run_onboarding_gap_analysis(
    target_website_url: str,
    business_description: str,
    seed_competitor_url: str = None,
    target_location: str = "",
    core_strengths: str = "",
    brand_logo_url: str = None,
    tenant_id: str = None,
    brand_profile: dict = None,
) -> dict:
    """Hàm thực thi tổng hợp Bước 1: Trinh sát web & đối thủ, phân tích UVP, Gap Analysis, lưu PostgreSQL & RAG."""
    import serp_discovery_tool
    db_mod = getattr(serp_discovery_tool, "db_service", db_service)
    rag_mod = getattr(serp_discovery_tool, "rag_service", rag_service)
    inspect_fn = getattr(serp_discovery_tool, "inspect_website", inspect_website)
    discover_fn = getattr(serp_discovery_tool, "discover_top_10_competitors", discover_top_10_competitors)
    call_llm_fn = getattr(serp_discovery_tool, "call_llm", call_llm)
    uvp_gen_fn = getattr(serp_discovery_tool, "generate_uvp_suggestions", generate_uvp_suggestions)
    uvp_opt_fn = getattr(serp_discovery_tool, "analyze_and_optimize_user_uvp", analyze_and_optimize_user_uvp)

    if not tenant_id:
        raise ValueError("tenant_id là bắt buộc khi tạo hồ sơ dự án")
    target_website_url = normalize_website_url(target_website_url)
    seed_competitor_url = normalize_website_url(seed_competitor_url) if seed_competitor_url else None
    brand_profile = brand_profile or {}
    tenant = db_mod.get_tenant(tenant_id)
    if not tenant:
        raise ValueError("Không tìm thấy tenant_id được chỉ định")

    print(f"\n=======================================================")
    print(f"🚀 [BƯỚC 1 - GIAI ĐOẠN 1] Bóc tách web bạn: {target_website_url} & đối thủ mẫu: {seed_competitor_url}...")
    my_site_data = inspect_fn(target_website_url)
    seed_site_data = inspect_fn(seed_competitor_url) if seed_competitor_url else {
        "status": "not_provided",
        "summary": "Không có website đối thủ mẫu; chỉ sử dụng các kết quả SERP đã xác minh."
    }

    final_logo_url = brand_logo_url
    if not final_logo_url:
        final_logo_url = my_site_data.get("logo_url")
        if final_logo_url:
            print(f"🎯 [BƯỚC 1 - LOGO] Đã tự động cào Logo thương hiệu từ website: {final_logo_url}")
        else:
            print(f"ℹ️ [BƯỚC 1 - LOGO] Chưa có logo người dùng và web chưa gắn logo -> Không gắn logo mặc định của tenant khác.")
    else:
        print(f"🎯 [BƯỚC 1 - LOGO] Sử dụng Logo do người dùng cung cấp trực tiếp: {final_logo_url}")

    print(f"🚀 [BƯỚC 1 - GIAI ĐOẠN 2] Truy vấn DuckDuckGo SERP tại '{target_location}' (Chỉ lấy đối thủ đang Online)...")
    top_competitors = discover_fn(
        business_description,
        seed_competitor_url,
        target_location,
        exclude_url=target_website_url,
        only_online=True
    )
    print(f"   -> Đã tìm thấy {len(top_competitors)} đối thủ SERP đã xác minh Online 100%.")

    is_uvp_empty = not core_strengths or not core_strengths.strip()
    suggested_uvps = []
    uvp_benchmark_data = {}

    if is_uvp_empty:
        print(f"💡 [BƯỚC 1 - GIAI ĐOẠN 2.5] Đang tạo 4 phương án UVP; mức độ bằng chứng phụ thuộc dữ liệu SERP...")
        suggested_uvps = uvp_gen_fn(business_description, target_location, top_competitors)
    else:
        print(f"🔍 [BƯỚC 1 - GIAI ĐOẠN 2.5] Đang đánh giá bằng chứng cho lợi thế người dùng cung cấp...")
        uvp_benchmark_data = uvp_opt_fn(core_strengths, business_description, target_location, top_competitors)
        suggested_uvps = uvp_benchmark_data.get("suggested_options", [])

    print(f"🚀 [BƯỚC 1 - GIAI ĐOẠN 3] Tổng hợp Báo cáo Khoảng trống (Gap Analysis) qua 9Router...")
    analysis_prompt = (
        f"THÔNG TIN DỰ ÁN MỚI:\n"
        f"- Website cần phát triển: {target_website_url}\n"
        f"- Dữ liệu hiện tại của web: {json.dumps(my_site_data, ensure_ascii=False)}\n"
        f"- Mục tiêu kinh doanh: {business_description}\n"
        f"- Khu vực thị trường: {target_location}\n"
        f"- Hồ sơ thương hiệu bổ sung: {json.dumps(brand_profile, ensure_ascii=False)}\n"
        f"- Lợi thế cạnh tranh (UVP) ban đầu: {core_strengths if not is_uvp_empty else 'Người dùng để trống'}\n"
        f"- Dữ liệu phân tích & Gợi ý tối ưu UVP từ AI:\n{json.dumps(uvp_benchmark_data if not is_uvp_empty else suggested_uvps, ensure_ascii=False, indent=2)}\n\n"
        f"DỮ LIỆU ĐỐI THỦ THAM CHIẾU MẪU:\n{json.dumps(seed_site_data, ensure_ascii=False)}\n\n"
        f"KẾT QUẢ SERP THỰC TẾ ĐÃ KIỂM TRA:\n{json.dumps(top_competitors, ensure_ascii=False)}\n\n"
        "Hãy xuất một Bản Báo Cáo Tiếp Cận Dự Án & Chiến Lược Đột Phá Chuẩn Quốc Tế gồm các mục:\n"
        "1. MA TRẬN KẾT QUẢ SERP DUCKDUCKGO ĐÃ XÁC MINH.\n"
        + ("2. GỢI Ý 4 PHƯƠNG ÁN ĐỊNH VỊ ĐỘC QUYỀN (UVP).\n" if is_uvp_empty else
           "2. ĐÁNH GIÁ LỢI THẾ & GỢI Ý TỐI ƯU.\n") +
        "3. BÁO CÁO KHOẢNG TRỐNG (Cấu trúc Danh mục, Tín nhiệm E-E-A-T, Tối ưu Chuyển đổi).\n"
        "4. KẾ HOẠCH HÀNH ĐỘNG PHÂN BỔ CHO 6 AGENT CON (W1-W6)."
    )

    strategic_report = call_llm_fn(
        "Bạn là Giám Đốc Marketing AI (CMO Agent) đại diện cho tổ chức, sắc sảo, chiến lược, thực tế.",
        analysis_prompt,
        max_tokens=1000,
        timeout=45
    )

    project_id = None
    dataset_id = None
    tenant_name = tenant["name"]
    project_name = target_website_url.replace("https://", "").replace("http://", "").split("/")[0]
    business_profile_text = (
        business_description
        + "\n\nThông tin hồ sơ thương hiệu bổ sung:\n"
        + json.dumps(brand_profile, ensure_ascii=False)
    )
    try:
        project_id = db_mod.save_project(
            tenant_id=tenant_id,
            name=project_name,
            target_website_url=target_website_url,
            business_description=business_profile_text,
            seed_competitor_url=seed_competitor_url,
            target_location=target_location,
            core_strengths=core_strengths or "",
            suggested_uvp_options=suggested_uvps,
            brand_logo_url=final_logo_url,
            brand_profile=brand_profile,
        )

        db_comps = []
        for c in top_competitors:
            db_comps.append({
                "domain": c.get("domain", ""),
                "url": c.get("url", ""),
                "top_rank": c.get("rank", 0),
                "is_online": c.get("is_online", False),
                "scraped_structure": {
                    "title": c.get("brand_name", ""),
                    "snippet": c.get("serp_snippet", ""),
                    "source": c.get("source", ""),
                },
                "pricing_data": {},
                "strengths": c.get("core_strength", ""),
                "weaknesses": c.get("vulnerability", "")
            })
        db_mod.save_competitors(project_id, db_comps)

        db_mod.save_market_report(
            project_id=project_id,
            report_type="onboarding_gap_analysis",
            category_gap={"status": "analyzed"},
            eeat_gap={"status": "analyzed"},
            cro_gap={"status": "analyzed"},
            strategic_roadmap=strategic_report
        )
    except Exception as db_err:
        logger.exception("Failed to persist onboarding data to PostgreSQL")
        raise RuntimeError("Không thể lưu hồ sơ onboarding vào PostgreSQL") from db_err

    rag_reference_saved = False
    try:
        dataset_id = rag_mod.sync_project_intelligence_to_rag(
            project_name=project_name,
            client_name=tenant_name,
            target_website=target_website_url,
            business_desc=business_profile_text,
            competitors=db_comps,
            gap_report={
                "category_gap": {},
                "eeat_gap": {},
                "cro_gap": {},
                "strategic_roadmap": strategic_report
            },
            core_strengths=core_strengths or "",
            suggested_uvps=suggested_uvps
        )
        if dataset_id:
            try:
                db_mod.save_project(
                    tenant_id=tenant_id,
                    name=project_name,
                    target_website_url=target_website_url,
                    business_description=business_profile_text,
                    seed_competitor_url=seed_competitor_url,
                    target_location=target_location,
                    core_strengths=core_strengths or "",
                    suggested_uvp_options=suggested_uvps,
                    dify_dataset_id=dataset_id,
                    brand_logo_url=final_logo_url,
                    brand_profile=brand_profile,
                )
                rag_reference_saved = True
                print(f"✅ Đã đồng bộ Trí Tuệ Dự Án vào Dify Knowledge Base: {dataset_id}")
            except Exception:
                logger.exception("RAG content was synced, but its dataset ID could not be saved to PostgreSQL")
    except Exception:
        logger.exception("PostgreSQL onboarding data was saved, but Dify RAG sync failed")

    return {
        "status": "complete" if dataset_id and rag_reference_saved else "partial",
        "persistence": {
            "postgresql": "saved",
            "rag": "synced" if dataset_id else "failed",
            "rag_dataset_reference": "saved" if rag_reference_saved else "failed",
        },
        "project_id": project_id,
        "dify_dataset_id": dataset_id,
        "target_website_url": target_website_url,
        "brand_logo_url": final_logo_url,
        "brand_profile": brand_profile,
        "business_description": business_description,
        "seed_competitor_url": seed_competitor_url,
        "target_location": target_location,
        "core_strengths": core_strengths,
        "suggested_uvp_options": suggested_uvps,
        "top_10_competitors": top_competitors,
        "serp_source": "DuckDuckGo",
        "verified_competitor_count": sum(1 for item in top_competitors if item["is_online"]),
        "strategic_report": strategic_report
    }
