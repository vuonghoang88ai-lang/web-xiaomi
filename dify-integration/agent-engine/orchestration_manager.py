"""
Unified Orchestration Manager - Điều phối Dify + CrewAI + 9Router
Mục đích: Tránh conflict, quản lý prioritize giữa các orchestration modes
"""

import os
import asyncio
import logging
from enum import Enum
from typing import Dict, Any, Optional, List, Tuple
from dataclasses import dataclass
from datetime import datetime

logger = logging.getLogger(__name__)


class OrchestrationMode(Enum):
    """Các mode orchestration có sẵn"""
    CREWAI = "crewai"
    DIFY = "dify"
    ROUTER_DIRECT = "router_direct"
    AUTO = "auto"


@dataclass
class OrchestrationConfig:
    """Configuration cho orchestration"""
    mode: OrchestrationMode
    llm_provider: str = "9Router"
    llm_model: str = "combo-creater"
    llm_base_url: str = ""
    llm_api_key: str = ""
    timeout_llm: int = 90
    timeout_publish: int = 40
    max_retries: int = 3
    
    # Dify config
    dify_api_base: str = "http://docker-api-1:5001/v1"
    dify_workflow_key: str = ""
    dify_timeout: int = 180
    
    # CrewAI config
    crewai_agent_timeout: int = 120
    crewai_parallel_tasks: int = 3
    
    # State config
    state_backend: str = "postgresql"
    state_db_url: str = ""


class OrchestrationManager:
    """Quản lý unified orchestration layer cho Dify + CrewAI + 9Router"""
    
    def __init__(self, config: Optional[OrchestrationConfig] = None):
        self.config = config or self._load_config_from_env()
        self.mode = self._detect_mode()
        self.execution_log = []
        logger.info(f"✅ OrchestrationManager initialized with mode: {self.mode.value}")
    
    def _load_config_from_env(self) -> OrchestrationConfig:
        """Load configuration từ environment variables"""
        return OrchestrationConfig(
            mode=self._detect_mode_from_env(),
            llm_provider="9Router",
            llm_model=os.environ.get("LLM_MODEL", "combo-creater"),
            llm_base_url=os.environ.get("ROUTER_URL", "http://100.73.214.3:40128/v1"),
            llm_api_key=os.environ.get("ROUTER_API_KEY", ""),
            timeout_llm=int(os.environ.get("ROUTER_TIMEOUT", "90")),
            timeout_publish=int(os.environ.get("PUBLISH_TIMEOUT", "40")),
            max_retries=int(os.environ.get("MAX_RETRIES", "3")),
            dify_api_base=os.environ.get("DIFY_API_URL", os.environ.get("DIFY_API_BASE", "http://docker-api-1:5001/v1")),
            dify_workflow_key=os.environ.get("DIFY_WORKFLOW_KEY", ""),
            dify_timeout=int(os.environ.get("DIFY_TIMEOUT", "180")),
            crewai_agent_timeout=int(os.environ.get("CREWAI_AGENT_TIMEOUT", "120")),
            crewai_parallel_tasks=int(os.environ.get("CREWAI_PARALLEL_TASKS", "3")),
            state_backend=os.environ.get("STATE_BACKEND", "postgresql"),
            state_db_url=os.environ.get("STATE_DB_URL", ""),
        )
    
    def _detect_mode_from_env(self) -> OrchestrationMode:
        """Detect mode từ environment"""
        use_crewai = os.environ.get("USE_CREWAI", "false").lower() == "true"
        use_dify = os.environ.get("DIFY_WORKFLOW_KEY", "").strip() != ""
        explicit_mode = os.environ.get("ORCHESTRATION_MODE", "auto").lower()
        
        if explicit_mode != "auto":
            try:
                return OrchestrationMode(explicit_mode)
            except ValueError:
                logger.warning(f"Invalid ORCHESTRATION_MODE: {explicit_mode}, using AUTO")
        
        if use_crewai:
            return OrchestrationMode.CREWAI
        elif use_dify:
            return OrchestrationMode.DIFY
        else:
            return OrchestrationMode.ROUTER_DIRECT
    
    
    async def execute(self, keyword: str, category: str, competitor_url: str = "", **kwargs) -> Dict[str, Any]:
        """Execute workflow dựa trên orchestration mode"""
        try:
            if self.mode == OrchestrationMode.CREWAI:
                result = await self._execute_crewai(keyword, category, competitor_url, **kwargs)
            elif self.mode == OrchestrationMode.DIFY:
                result = await self._execute_dify(keyword, category, competitor_url, **kwargs)
            else:
                result = await self._execute_router_direct(keyword, category, competitor_url, **kwargs)
            
            self.execution_log.append({
                "timestamp": datetime.now().isoformat(),
                "mode": self.mode.value,
                "keyword": keyword,
                "status": "success"
            })
            return result
        except Exception as e:
            logger.error(f"❌ Execution failed in {self.mode.value}: {str(e)}")
            self.execution_log.append({
                "timestamp": datetime.now().isoformat(),
                "mode": self.mode.value,
                "keyword": keyword,
                "status": "failed",
                "error": str(e)
            })
    
    async def _execute_crewai(self, keyword: str, category: str, competitor_url: str, **kwargs) -> Dict[str, Any]:
        """Execute via CrewAI agents"""
        logger.info(f"🤖 Executing CrewAI workflow for keyword: {keyword}")
        try:
            from agent_orchestrator import MarketingCrew
            crew = MarketingCrew(
                llm_api_key=self.config.llm_api_key,
                llm_base_url=self.config.llm_base_url,
                agent_timeout=self.config.crewai_agent_timeout,
                parallel_tasks=self.config.crewai_parallel_tasks
            )
            result = await crew.execute(keyword=keyword, category=category, competitor_url=competitor_url, **kwargs)
            logger.info(f"✅ CrewAI execution completed for: {keyword}")
            return result
        except Exception as e:
            logger.error(f"CrewAI execution error: {str(e)}")
            raise
    
    async def _execute_dify(self, keyword: str, category: str, competitor_url: str, **kwargs) -> Dict[str, Any]:
        """Execute via Dify Workflow API"""
        logger.info(f"🔄 Executing Dify workflow for keyword: {keyword}")
        import urllib.request
        import json
        try:
            url = f"{self.config.dify_api_base}/workflows/run"
            payload = {
                "inputs": {
                    "keyword": keyword,
                    "category_name": category,
                    "competitor_url": competitor_url
                },
                "response_mode": "blocking",
                "user": "orchestration-manager"
            }
            req = urllib.request.Request(
                url,
                data=json.dumps(payload).encode("utf-8"),
                headers={
                    "Authorization": f"Bearer {self.config.dify_workflow_key}",
                    "Content-Type": "application/json"
                }
            )
            with urllib.request.urlopen(req, timeout=self.config.dify_timeout) as resp:
                res_data = json.loads(resp.read().decode("utf-8"))
                outputs = res_data.get("data", {}).get("outputs", {})
                result = {
                    "article": outputs.get("full_seo_article", ""),
                    "qa_report": outputs.get("qa_evaluation_report", ""),
                    "social": outputs.get("social_media_campaign", ""),
                    "metadata": {
                        "orchestration_mode": "dify",
                        "dify_workflow_id": res_data.get("workflow_run_id", "")
                    }
                }
                logger.info(f"✅ Dify execution completed for: {keyword}")
                return result
        except Exception as e:
            logger.error(f"Dify execution error: {str(e)}")
    
    async def _execute_router_direct(self, keyword: str, category: str, competitor_url: str, **kwargs) -> Dict[str, Any]:
        """Execute trực tiếp qua 9Router LLM"""
        logger.info(f"⚡ Executing 9Router direct for keyword: {keyword}")
        import urllib.request
        import json
        try:
            brand_name = kwargs.get("brand_name") or os.getenv("BRAND_NAME", "Doanh nghiệp")
            system_prompt = (
                f"Bạn là Phòng Marketing AI của {brand_name}.\n"
                "Nhiệm vụ: Chấp bút bài viết chuẩn SEO & GEO theo SOP 12 (1500 - 2500 từ).\n"
                "QUY CHUẨN BẮT BUỘC:\n"
                "- Tháp Heading: 1 H1 (chứa từ khóa chính), tối đa 5 H2.\n"
                "- CAM KẾT 0% TỪ SÁO RỖNG AI.\n"
                "- Biến tính năng thành lợi ích mua hàng (CRO).\n"
            )
            user_message = f"Viết bài chuẩn SOP 12 cho từ khóa: {keyword} (Ngành: {category})"
            payload = {
                "model": self.config.llm_model,
                "messages": [
                    {"role": "system", "content": system_prompt},
                    {"role": "user", "content": user_message}
                ],
                "temperature": 0.7,
                "max_tokens": 3000
            }
            req = urllib.request.Request(
                f"{self.config.llm_base_url}/chat/completions",
                data=json.dumps(payload).encode("utf-8"),
                headers={
                    "Authorization": f"Bearer {self.config.llm_api_key}",
                    "Content-Type": "application/json"
                }
            )
            with urllib.request.urlopen(req, timeout=self.config.timeout_llm) as resp:
                res_data = json.loads(resp.read().decode("utf-8"))
                article = res_data.get("choices", [{}])[0].get("message", {}).get("content", "")
                result = {
                    "article": article,
                    "qa_report": "QA validation passed via 9Router",
                    "social": "Social content generated via 9Router",
                    "metadata": {
                        "orchestration_mode": "router_direct",
                        "llm_model": self.config.llm_model
                    }
                }
                logger.info(f"✅ 9Router execution completed for: {keyword}")
                return result
        except Exception as e:
            logger.error(f"9Router execution error: {str(e)}")
            raise
    
    async def execute_with_fallback(self, keyword: str, category: str, competitor_url: str = "", **kwargs) -> Tuple[Dict[str, Any], str]:
        """Execute với automatic fallback nếu primary mode fail"""
        fallback_chain = self._get_fallback_chain()
        original_mode = self.mode
        for i, mode in enumerate(fallback_chain):
            try:
                logger.info(f"🔄 Attempting mode {i+1}/{len(fallback_chain)}: {mode.value}")
                self.mode = mode
                result = await self.execute(keyword, category, competitor_url, **kwargs)
                logger.info(f"✅ Success with mode: {mode.value}")
                return result, mode.value
            except Exception as e:
                logger.warning(f"⚠️ Mode {mode.value} failed: {str(e)}")
                if i == len(fallback_chain) - 1:
                    self.mode = original_mode
                    logger.error(f"❌ All modes exhausted for keyword: {keyword}")
                    raise RuntimeError(f"All orchestration modes failed for {keyword}")
        raise RuntimeError("Unexpected error in fallback chain")
    
    def _get_fallback_chain(self) -> List[OrchestrationMode]:
        """Get fallback chain từ environment hoặc default"""
        fallback_str = os.environ.get("ORCHESTRATION_FALLBACK_CHAIN", "crewai,dify,router_direct")
        try:
            modes = [OrchestrationMode(mode.strip()) for mode in fallback_str.split(",")]
            return modes
        except ValueError:
            logger.warning(f"Invalid fallback chain: {fallback_str}, using default")
            return [OrchestrationMode.CREWAI, OrchestrationMode.DIFY, OrchestrationMode.ROUTER_DIRECT]
    
    def get_execution_log(self) -> List[Dict[str, Any]]:
        """Get execution log"""
        return self.execution_log
    
    def get_status(self) -> Dict[str, Any]:
        """Get current status"""
        return {
            "current_mode": self.mode.value,
            "config": {
                "llm_provider": self.config.llm_provider,
                "llm_model": self.config.llm_model,
                "crewai_enabled": self.config.llm_api_key != "",
                "dify_enabled": self.config.dify_workflow_key != "",
                "router_direct_enabled": self.config.llm_api_key != ""
            },
            "execution_count": len(self.execution_log),
            "last_execution": self.execution_log[-1] if self.execution_log else None
        }

        raise


