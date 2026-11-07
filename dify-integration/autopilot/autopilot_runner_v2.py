#!/usr/bin/env python3
"""
TongKhoMi AutoSEO & GEO Autopilot Engine v2 (với Orchestration Manager)
Tự động hóa 100% quy trình sản xuất nội dung SEO & GEO hàng ngày
"""

import os
import sys
import json
import asyncio
import logging
from datetime import datetime
from pathlib import Path

# Add agent-engine to path
sys.path.insert(0, os.path.join(os.path.dirname(__file__), '..', 'agent-engine'))

from orchestration_manager import OrchestrationManager, OrchestrationConfig
from output_adapter import OutputAdapter
from state_manager import StateManager, ExecutionState
from retry_handler import OrchestrationRetryHandler

# Setup logging
logging.basicConfig(
    level=logging.INFO,
    format='[%(asctime)s] %(name)s - %(levelname)s - %(message)s'
)
logger = logging.getLogger(__name__)

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
CALENDAR_FILE = os.path.join(BASE_DIR, "seo_calendar_30days.json")
STATE_FILE = os.path.join(BASE_DIR, "autopilot_state.json")
CAMPAIGNS_DIR = os.path.join(BASE_DIR, "campaigns")
LOG_FILE = os.path.join(BASE_DIR, "autopilot.log")


def load_calendar():
    """Load 30-day SEO calendar"""
    if not os.path.exists(CALENDAR_FILE):
        raise FileNotFoundError(f"Không tìm thấy file lịch: {CALENDAR_FILE}")
    with open(CALENDAR_FILE, "r", encoding="utf-8") as f:
        return json.load(f)


def load_state():
    """Load autopilot state"""
    if os.path.exists(STATE_FILE):
        try:
            with open(STATE_FILE, "r", encoding="utf-8") as f:
                return json.load(f)
        except Exception as e:
            logger.error(f"Lỗi đọc state, tạo mới: {e}")
    return {"current_day": 1, "history": []}


def save_state(state):
    """Save autopilot state"""
    with open(STATE_FILE, "w", encoding="utf-8") as f:
        json.dump(state, f, ensure_ascii=False, indent=2)


def get_current_task(calendar, state):
    """Get current task from calendar"""
    current_day = state.get("current_day", 1)
    for item in calendar:
        if item.get("day") == current_day:
            return item
    state["current_day"] = 1
    save_state(state)
    return calendar[0]


async def publish_to_wordpress(title: str, article: str, category: str, keyword: str) -> dict:
    """Publish article to WordPress as draft via Omni Bridge Connector"""
    logger.info(f"📝 Publishing to WordPress: {title}")
    
    wp_api_base = os.environ.get("WP_API_BASE", os.environ.get("OMNI_BRIDGE_URL", "http://wordpress:80/wp-json/omni-bridge/v1")).rstrip("/")
    wp_api_key = os.environ.get("WP_API_KEY", os.environ.get("OMNI_BRIDGE_KEY", ""))
    
    if not wp_api_key:
        logger.warning("⚠️ WP_API_KEY / OMNI_BRIDGE_KEY not set, skipping WordPress publish")
        return {"post_id": None, "preview_url": None}
    
    import urllib.request
    
    try:
        if not wp_api_base.endswith("/publish"):
            if not wp_api_base.endswith("/wp-json/omni-bridge/v1"):
                url = f"{wp_api_base}/wp-json/omni-bridge/v1/publish"
            else:
                url = f"{wp_api_base}/publish"
        else:
            url = wp_api_base

        payload = {
            "post_title": title,
            "post_content": article,
            "post_status": "draft",
            "categories": [category] if category else [],
            "tags": [keyword] if keyword else [],
            "seo_meta": {
                "focus_keyword": keyword,
                "seo_description": (title + ". Hướng dẫn và đánh giá chi tiết.")[:160]
            }
        }
        
        req = urllib.request.Request(
            url,
            data=json.dumps(payload).encode("utf-8"),
            headers={
                "X-Omni-Bridge-Key": wp_api_key,
                "Content-Type": "application/json"
            }
        )
        
        with urllib.request.urlopen(req, timeout=40) as resp:
            res_data = json.loads(resp.read().decode("utf-8"))
            post_id = res_data.get("post_id") or res_data.get("id")
            preview_url = res_data.get("preview_url") or res_data.get("link")
            logger.info(f"✅ Published to WordPress via Omni Bridge: Post ID {post_id}")
            return {"post_id": post_id, "preview_url": preview_url}
    
    except Exception as e:
        logger.error(f"❌ Failed to publish to WordPress via Omni Bridge: {e}")
        return {"error": str(e), "status": "failed"}


async def run_autopilot_day(day: int, keyword: str, category: str, competitor_url: str = ""):
    """Run autopilot for a specific day"""
    logger.info(f"🚀 Starting Autopilot Day {day}: '{keyword}' ({category})")
    
    try:
        # Initialize managers
        orchestration_mgr = OrchestrationManager()
        state_mgr = StateManager(
            backend=os.environ.get("STATE_BACKEND", "file"),
            db_url=os.environ.get("STATE_DB_URL", "")
        )
        retry_handler = OrchestrationRetryHandler(max_retries_per_mode=2)
        
        # Execute with fallback chain
        result, mode_used, total_attempts = await retry_handler.execute_with_fallback_chain(
            modes=["crewai", "dify", "router_direct"],
            execute_func=orchestration_mgr.execute,
            keyword=keyword,
            category=category,
            competitor_url=competitor_url
        )
        
        # Convert output to unified format
        unified_output = OutputAdapter.from_orchestration_result(
            result,
            mode_used,
            metadata={
                "keyword": keyword,
                "category": category,
                "orchestration_mode": mode_used,
                "total_attempts": total_attempts
            }
        )
        
        # Validate output
        if not OutputAdapter.validate_output(unified_output):
            logger.error("❌ Output validation failed")
            raise RuntimeError("Output validation failed")
        
        logger.info(f"✅ Content generation completed (Mode: {mode_used}, Attempts: {total_attempts})")
        
        # Extract title & publish to WordPress
        h1_match = None
        for line in unified_output.article.split("\n"):
            if line.startswith("# "):
                h1_match = line.replace("# ", "").strip()
                break
        
        title = h1_match or keyword.title()
        wp_result = await publish_to_wordpress(title, unified_output.article, category, keyword)
        
        # Save campaign record
        os.makedirs(CAMPAIGNS_DIR, exist_ok=True)
        campaign_file = os.path.join(CAMPAIGNS_DIR, f"day_{day:02d}_{category.lower().replace(' ', '_')}.md")
        
        with open(campaign_file, "w", encoding="utf-8") as f:
            f.write(f"# CHIẾN DỊCH NGÀY {day}\n\n")
            f.write(f"- **Từ khóa**: {keyword}\n")
            f.write(f"- **Ngành hàng**: {category}\n")
            f.write(f"- **Orchestration Mode**: {mode_used}\n")
            f.write(f"- **Thời gian**: {datetime.now().strftime('%Y-%m-%d %H:%M:%S')}\n")
            if wp_result.get("post_id"):
                f.write(f"- **WordPress Post ID**: {wp_result['post_id']}\n")
                f.write(f"- **Link xem trước**: {wp_result['preview_url']}\n")
            f.write("\n---\n\n## BÀI VIẾT\n\n")
            f.write(unified_output.article)
            f.write("\n\n---\n\n## KỊCH BẢN TIKTOK & FACEBOOK\n\n")
            f.write(unified_output.social_campaign)
            f.write("\n\n---\n\n## BÁNG CÁO QA\n\n")
            f.write(unified_output.qa_report)
        
        logger.info(f"📁 Campaign saved: {campaign_file}")
        
        # Save to state manager
        exec_state = ExecutionState(
            project_id="autopilot_main",
            day=day,
            keyword=keyword,
            category=category,
            orchestration_mode=mode_used,
            status="completed",
            post_id=wp_result.get("post_id"),
            preview_url=wp_result.get("preview_url"),
            execution_time=total_attempts
        )
        state_mgr.save_execution(exec_state)
        
        logger.info(f"🎉 Day {day} completed successfully!")
        return True
    
    except Exception as e:
        logger.error(f"❌ Day {day} failed: {str(e)}")
        return False


async def run_autopilot():
    """Main autopilot runner"""
    try:
        calendar = load_calendar()
        state = load_state()
        task = get_current_task(calendar, state)
        
        day = task["day"]
        keyword = task["keyword"]
        category = task.get("category", "Sản phẩm")
        competitor_url = task.get("competitor_url", "")
        
        # Run day
        success = await run_autopilot_day(day, keyword, category, competitor_url)
        
        # Update state
        if success:
            state["current_day"] = day + 1 if day < 30 else 1
            state["history"].append({
                "day": day,
                "keyword": keyword,
                "date": datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                "status": "completed"
            })
        else:
            state["history"].append({
                "day": day,
                "keyword": keyword,
                "date": datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
                "status": "failed"
            })
        
        save_state(state)
        logger.info(f"📋 State updated. Next day: {state['current_day']}")
    
    except Exception as e:
        logger.error(f"Fatal autopilot error: {str(e)}")
        sys.exit(1)


if __name__ == "__main__":
    asyncio.run(run_autopilot())
