"""
Unified Output Adapter - Chuẩn hóa output từ tất cả orchestration modes
Đảm bảo Dify, CrewAI, và 9Router trả về cùng format
"""

from dataclasses import dataclass, asdict
from typing import Dict, Any, Optional, List
from datetime import datetime
import logging

logger = logging.getLogger(__name__)


@dataclass
class UnifiedOutput:
    """Unified output format từ bất kỳ orchestration mode nào"""
    article: str
    qa_report: str
    social_campaign: str
    metadata: Dict[str, Any]
    
    def __post_init__(self):
        """Validate và chuẩn hóa output"""
        if not self.article:
            raise ValueError("Article không thể trống")
        if not self.metadata:
            self.metadata = {}
        if "timestamp" not in self.metadata:
            self.metadata["timestamp"] = datetime.now().isoformat()
    
    def to_wordpress_payload(self) -> Dict[str, Any]:
        """Convert to WordPress format (dual-compatible with WP Core REST & Omni Bridge)"""
        title = self._extract_title()
        keywords = self.metadata.get("keywords", [])
        focus_kw = keywords[0] if isinstance(keywords, list) and keywords else str(keywords or "")
        return {
            "title": title,
            "post_title": title,
            "content": self.article,
            "post_content": self.article,
            "status": "draft",
            "post_status": "draft",
            "categories": [self.metadata.get("category")] if self.metadata.get("category") else [],
            "tags": keywords if isinstance(keywords, list) else ([keywords] if keywords else []),
            "seo_meta": {
                "focus_keyword": focus_kw,
                "seo_title": title,
                "meta_description": (title + ". Đánh giá chi tiết và hướng dẫn sử dụng.")[:160],
                "seo_description": (title + ". Đánh giá chi tiết và hướng dẫn sử dụng.")[:160]
            },
            "meta": {
                "qa_report": self.qa_report,
                "source_orchestration": self.metadata.get("orchestration_mode", "unknown"),
                "seo_keywords": keywords,
                "execution_time": self.metadata.get("execution_time", "")
            }
        }
    
    def to_campaign_record(self) -> Dict[str, Any]:
        """Convert to campaign database record"""
        return {
            "article_body": self.article,
            "article_title": self._extract_title(),
            "qa_validation": self.qa_report,
            "social_content": self.social_campaign,
            "source_orchestration": self.metadata.get("orchestration_mode"),
            "execution_time": self.metadata.get("execution_time"),
            "llm_model": self.metadata.get("llm_model", ""),
            "timestamp": self.metadata.get("timestamp"),
            "metadata_json": asdict(self)
        }
    
    def to_state_record(self) -> Dict[str, Any]:
        """Convert to state manager record"""
        return {
            "article_id": self.metadata.get("article_id", ""),
            "keyword": self.metadata.get("keyword", ""),
            "category": self.metadata.get("category", ""),
            "orchestration_mode": self.metadata.get("orchestration_mode"),
            "execution_time": self.metadata.get("execution_time"),
            "status": "completed",
            "timestamp": self.metadata.get("timestamp")
        }
    
    def _extract_title(self) -> str:
        """Extract H1 title từ article"""
        lines = self.article.split("\n")
        for line in lines:
            if line.startswith("# "):
                return line.replace("# ", "").strip()
        if "keyword" in self.metadata:
            return self.metadata["keyword"].title()
        return "Untitled Article"
    
    def to_dict(self) -> Dict[str, Any]:
        """Convert to dictionary"""
        return asdict(self)
    
    def to_json(self) -> str:
        """Convert to JSON string."""
        from json import dumps
        return dumps(self.to_dict(), ensure_ascii=False, indent=2)


class OutputAdapter:
    """Adapter để convert output từ các orchestration modes thành UnifiedOutput"""
    
    @staticmethod
    def from_crewai(result: Dict[str, Any], metadata: Dict[str, Any] = None) -> UnifiedOutput:
        """Convert CrewAI output to UnifiedOutput"""
        logger.info("Converting CrewAI output to UnifiedOutput")
        metadata = metadata or {}
        metadata.setdefault("orchestration_mode", "crewai")
        
        return UnifiedOutput(
            article=result.get("article", ""),
            qa_report=result.get("qa_report", ""),
            social_campaign=result.get("social", ""),
            metadata=metadata
        )
    
    @staticmethod
    def from_dify(result: Dict[str, Any], metadata: Dict[str, Any] = None) -> UnifiedOutput:
        """Convert Dify Workflow output to UnifiedOutput"""
        logger.info("Converting Dify output to UnifiedOutput")
        metadata = metadata or {}
        metadata.setdefault("orchestration_mode", "dify")
        if "metadata" in result:
            metadata.update(result["metadata"])
        
        return UnifiedOutput(
            article=result.get("article", ""),
            qa_report=result.get("qa_report", ""),
            social_campaign=result.get("social", ""),
            metadata=metadata
        )
    
    @staticmethod
    def from_router_direct(result: Dict[str, Any], metadata: Dict[str, Any] = None) -> UnifiedOutput:
        """Convert 9Router direct output to UnifiedOutput"""
        logger.info("Converting 9Router output to UnifiedOutput")
        metadata = metadata or {}
        metadata.setdefault("orchestration_mode", "router_direct")
        if "metadata" in result:
            metadata.update(result["metadata"])
        
        return UnifiedOutput(
            article=result.get("article", ""),
            qa_report=result.get("qa_report", ""),
            social_campaign=result.get("social", ""),
            metadata=metadata
        )
    
    @staticmethod
    def from_orchestration_result(result: Dict[str, Any], mode: str, metadata: Dict[str, Any] = None) -> UnifiedOutput:
        """Generic method để convert orchestration result dựa trên mode"""
        metadata = metadata or {}
        metadata["orchestration_mode"] = mode
        
        if mode == "crewai":
            return OutputAdapter.from_crewai(result, metadata)
        elif mode == "dify":
            return OutputAdapter.from_dify(result, metadata)
        elif mode == "router_direct":
            return OutputAdapter.from_router_direct(result, metadata)
        else:
            logger.warning(f"Unknown mode: {mode}, using generic conversion")
            return UnifiedOutput(
                article=result.get("article", ""),
                qa_report=result.get("qa_report", ""),
                social_campaign=result.get("social", ""),
                metadata=metadata
            )
    
    @staticmethod
    def validate_output(output: UnifiedOutput) -> bool:
        """Validate UnifiedOutput completeness"""
        validation_errors = []
        
        if not output.article or len(output.article.strip()) < 100:
            validation_errors.append("Article quá ngắn hoặc trống")
        
        if not output.qa_report or len(output.qa_report.strip()) < 10:
            validation_errors.append("QA report quá ngắn hoặc trống")
        
        if not output.metadata:
            validation_errors.append("Metadata trống")
        
        if "orchestration_mode" not in output.metadata:
            validation_errors.append("Orchestration mode không rõ")
        
        if validation_errors:
            logger.warning(f"Output validation failed: {', '.join(validation_errors)}")
            return False
        
        logger.info("✅ Output validation passed")
        return True
