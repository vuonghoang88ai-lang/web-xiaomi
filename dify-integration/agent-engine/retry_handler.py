"""
Unified Retry Handler - Xử lý retry và fallback logic thống nhất
Tránh double retry, optimize timeout settings
"""

import logging
import asyncio
from typing import Dict, Any, List, Callable, Tuple
from enum import Enum
from datetime import datetime

logger = logging.getLogger(__name__)


class RetryStrategy(Enum):
    """Retry strategy options"""
    EXPONENTIAL_BACKOFF = "exponential_backoff"
    LINEAR_BACKOFF = "linear_backoff"
    FIXED_DELAY = "fixed_delay"
    NO_RETRY = "no_retry"


class RetryConfig:
    """Configuration cho retry logic"""
    def __init__(
        self,
        max_retries: int = 3,
        initial_delay: float = 1.0,
        max_delay: float = 60.0,
        strategy: RetryStrategy = RetryStrategy.EXPONENTIAL_BACKOFF,
        backoff_multiplier: float = 2.0
    ):
        self.max_retries = max_retries
        self.initial_delay = initial_delay
        self.max_delay = max_delay
        self.strategy = strategy
        self.backoff_multiplier = backoff_multiplier


class RetryHandler:
    """Xử lý retry thống nhất cho orchestration"""
    
    def __init__(self, config: RetryConfig = None):
        self.config = config or RetryConfig()
        self.retry_history = []
    
    async def execute_with_retry(
        self,
        func: Callable,
        *args,
        **kwargs
    ) -> Tuple[Any, bool, int]:
        """Execute function với retry logic"""
        retry_count = 0
        last_error = None
        
        for attempt in range(self.config.max_retries + 1):
            try:
                logger.info(f"🔄 Executing attempt {attempt + 1}/{self.config.max_retries + 1}")
                result = await func(*args, **kwargs)
                
                status_msg = f"Success on retry attempt {attempt}" if attempt > 0 else "Success on first attempt"
                logger.info(f"✅ {status_msg}")
                
                self.retry_history.append({
                    "timestamp": datetime.now().isoformat(),
                    "attempt": attempt + 1,
                    "status": "success",
                    "error": None
                })
                
                return result, True, retry_count
            
            except Exception as e:
                last_error = e
                retry_count = attempt
                
                self.retry_history.append({
                    "timestamp": datetime.now().isoformat(),
                    "attempt": attempt + 1,
                    "status": "failed",
                    "error": str(e)
                })
                
                if attempt == self.config.max_retries:
                    logger.error(f"❌ Max retries ({self.config.max_retries}) exhausted")
                    break
                
                delay = self._calculate_delay(attempt)
                logger.warning(f"⚠️ Attempt {attempt + 1} failed: {str(e)}. Retrying in {delay}s...")
                await asyncio.sleep(delay)
        
        logger.error(f"❌ All retry attempts failed. Last error: {str(last_error)}")
        return None, False, retry_count
    
    def _calculate_delay(self, attempt: int) -> float:
        """Calculate delay based on retry strategy"""
        if self.config.strategy == RetryStrategy.EXPONENTIAL_BACKOFF:
            delay = self.config.initial_delay * (self.config.backoff_multiplier ** attempt)
        elif self.config.strategy == RetryStrategy.LINEAR_BACKOFF:
            delay = self.config.initial_delay * (attempt + 1)
        else:
            delay = self.config.initial_delay
        
        return min(delay, self.config.max_delay)
    
    def get_retry_history(self) -> List[Dict[str, Any]]:
        """Get retry history"""
        return self.retry_history
    
    def clear_history(self):
        """Clear retry history"""
        self.retry_history = []



class OrchestrationRetryHandler:
    """Specialized retry handler cho orchestration fallback chain"""
    
    def __init__(self, max_retries_per_mode: int = 2):
        self.max_retries_per_mode = max_retries_per_mode
        self.execution_log = []
    
    async def execute_with_fallback_chain(
        self,
        modes: List[str],
        execute_func: Callable,
        keyword: str,
        category: str,
        competitor_url: str = "",
        **kwargs
    ) -> Tuple[Any, str, int]:
        """Execute với fallback chain (tự động switch modes nếu fail)"""
        total_attempts = 0
        
        for mode_idx, mode in enumerate(modes):
            logger.info(f"🔄 Attempting mode {mode_idx + 1}/{len(modes)}: {mode}")
            
            retry_handler = RetryHandler(RetryConfig(max_retries=self.max_retries_per_mode))
            
            try:
                result, success, retry_count = await retry_handler.execute_with_retry(
                    execute_func,
                    mode=mode,
                    keyword=keyword,
                    category=category,
                    competitor_url=competitor_url,
                    **kwargs
                )
                
                total_attempts += retry_count + 1
                
                if success:
                    logger.info(f"✅ Success with mode: {mode} (after {retry_count} retries)")
                    self.execution_log.append({
                        "timestamp": datetime.now().isoformat(),
                        "mode": mode,
                        "keyword": keyword,
                        "status": "success",
                        "total_attempts": total_attempts,
                        "retry_count": retry_count
                    })
                    return result, mode, total_attempts
            
            except Exception as e:
                logger.warning(f"⚠️ Mode {mode} failed after retries: {str(e)}")
                total_attempts += self.max_retries_per_mode + 1
        
        logger.error(f"❌ All modes exhausted after {total_attempts} total attempts")
        raise RuntimeError(f"All orchestration modes failed for keyword: {keyword}")
    
    def get_execution_log(self) -> List[Dict[str, Any]]:
        """Get execution log"""
        return self.execution_log
