"""
Unified State Manager - Quản lý state tập trung cho tất cả orchestration modes
Đảm bảo data consistency giữa Dify, CrewAI, và 9Router
"""

import logging
from typing import Dict, Any, Optional, List
from datetime import datetime
from dataclasses import dataclass, asdict
import json

logger = logging.getLogger(__name__)


@dataclass
class ExecutionState:
    """State của một execution"""
    project_id: str
    day: int
    keyword: str
    category: str
    orchestration_mode: str
    status: str
    article_id: Optional[str] = None
    post_id: Optional[str] = None
    preview_url: Optional[str] = None
    execution_time: float = 0.0
    error_message: Optional[str] = None
    created_at: str = ""
    updated_at: str = ""
    
    def __post_init__(self):
        if not self.created_at:
            self.created_at = datetime.now().isoformat()
        self.updated_at = datetime.now().isoformat()


class StateManager:
    """Quản lý state thống nhất cho tất cả modes"""
    
    def __init__(self, backend: str = "file", db_url: str = ""):
        """Initialize StateManager"""
        self.backend = backend
        self.db_url = db_url
        self.state_store = {}
        logger.info(f"✅ StateManager initialized with backend: {backend}")
    
    def save_execution(self, state: ExecutionState) -> bool:
        """Save execution state"""
        try:
            state_key = f"{state.project_id}:day_{state.day}:keyword_{state.keyword}"
            state.updated_at = datetime.now().isoformat()
            self.state_store[state_key] = asdict(state)
            
            if self.backend == "file":
                self._save_to_file(state)
            elif self.backend == "postgresql":
                self._save_to_postgresql(state)
            elif self.backend == "redis":
                self._save_to_redis(state)
            
            logger.info(f"✅ Execution state saved: {state_key}")
            return True
        except Exception as e:
            logger.error(f"Failed to save execution state: {str(e)}")
            return False
    
    def get_execution_state(self, project_id: str, day: int, keyword: str) -> Optional[ExecutionState]:
        """Get execution state"""
        state_key = f"{project_id}:day_{day}:keyword_{keyword}"
        
        if state_key in self.state_store:
            state_dict = self.state_store[state_key]
            return ExecutionState(**state_dict)
        
        if self.backend == "file":
            return self._load_from_file(project_id, day, keyword)
        elif self.backend == "postgresql":
            return self._load_from_postgresql(project_id, day, keyword)
        elif self.backend == "redis":
            return self._load_from_redis(project_id, day, keyword)
        
        return None
    
    def get_campaign_history(self, project_id: str) -> List[ExecutionState]:
        """Get campaign execution history"""
        try:
            history = []
            for key, state_dict in self.state_store.items():
                if key.startswith(project_id):
                    history.append(ExecutionState(**state_dict))
            history.sort(key=lambda x: x.created_at, reverse=True)
            logger.info(f"Retrieved {len(history)} campaign records for project: {project_id}")
            return history
        except Exception as e:
            logger.error(f"Failed to get campaign history: {str(e)}")
            return []
    
    def _save_to_file(self, state: ExecutionState):
        """Save state to JSON file"""
        try:
            import os
            state_dir = os.path.expanduser("~/.dify-integration/state")
            os.makedirs(state_dir, exist_ok=True)
            state_file = os.path.join(state_dir, f"{state.project_id}_day_{state.day}.json")
            with open(state_file, "w", encoding="utf-8") as f:
                json.dump(asdict(state), f, ensure_ascii=False, indent=2)
            logger.debug(f"State saved to file: {state_file}")
        except Exception as e:
            logger.error(f"File save failed: {str(e)}")
    
    def _load_from_file(self, project_id: str, day: int, keyword: str) -> Optional[ExecutionState]:
        """Load state from JSON file"""
        try:
            import os
            state_dir = os.path.expanduser("~/.dify-integration/state")
            state_file = os.path.join(state_dir, f"{project_id}_day_{day}.json")
            if not os.path.exists(state_file):
                return None
            with open(state_file, "r", encoding="utf-8") as f:
                state_dict = json.load(f)
                return ExecutionState(**state_dict)
        except Exception as e:
            logger.error(f"File load failed: {str(e)}")
    
    def _save_to_postgresql(self, state: ExecutionState):
        """Save state to PostgreSQL"""
        try:
            import psycopg2
            conn = psycopg2.connect(self.db_url)
            cur = conn.cursor()
            sql = """
            INSERT INTO execution_states (project_id, day, keyword, orchestration_mode, status, 
                post_id, execution_time, created_at, updated_at)
            VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s)
            ON CONFLICT (project_id, day, keyword) DO UPDATE SET
                status = EXCLUDED.status, post_id = EXCLUDED.post_id,
                execution_time = EXCLUDED.execution_time, updated_at = EXCLUDED.updated_at
            """
            cur.execute(sql, (state.project_id, state.day, state.keyword, state.orchestration_mode,
                state.status, state.post_id, state.execution_time, state.created_at, state.updated_at))
            conn.commit()
            cur.close()
            conn.close()
            logger.debug("State saved to PostgreSQL")
        except Exception as e:
            logger.error(f"PostgreSQL save failed: {str(e)}")
    
    def _load_from_postgresql(self, project_id: str, day: int, keyword: str) -> Optional[ExecutionState]:
        """Load state from PostgreSQL"""
        try:
            import psycopg2
            conn = psycopg2.connect(self.db_url)
            cur = conn.cursor()
            sql = "SELECT * FROM execution_states WHERE project_id = %s AND day = %s AND keyword = %s"
            cur.execute(sql, (project_id, day, keyword))
            row = cur.fetchone()
            cur.close()
            conn.close()
            if not row:
                return None
            return ExecutionState(
                project_id=row[0], day=row[1], keyword=row[2], category=row[3],
                orchestration_mode=row[4], status=row[5], post_id=row[6],
                execution_time=row[7], created_at=row[8], updated_at=row[9]
            )
        except Exception as e:
            logger.error(f"PostgreSQL load failed: {str(e)}")
            return None
    
    def _save_to_redis(self, state: ExecutionState):
        """Save state to Redis"""
        try:
            import redis
            r = redis.from_url(self.db_url)
            state_key = f"{state.project_id}:day_{state.day}:keyword_{state.keyword}"
            r.set(state_key, json.dumps(asdict(state), ensure_ascii=False), ex=2592000)
            logger.debug("State saved to Redis")
        except Exception as e:
            logger.error(f"Redis save failed: {str(e)}")
    
    def _load_from_redis(self, project_id: str, day: int, keyword: str) -> Optional[ExecutionState]:
        """Load state from Redis"""
        try:
            import redis
            r = redis.from_url(self.db_url)
            state_key = f"{project_id}:day_{day}:keyword_{keyword}"
            data = r.get(state_key)
            if not data:
                return None
            state_dict = json.loads(data)
            return ExecutionState(**state_dict)
        except Exception as e:
            logger.error(f"Redis load failed: {str(e)}")
            return None
    
    def get_stats(self, project_id: str) -> Dict[str, Any]:
        """Get campaign statistics"""
        history = self.get_campaign_history(project_id)
        total = len(history)
        completed = sum(1 for s in history if s.status == "completed")
        failed = sum(1 for s in history if s.status == "failed")
        avg_execution_time = sum(s.execution_time for s in history) / total if total > 0 else 0
        mode_counts = {}
        for state in history:
            mode_counts[state.orchestration_mode] = mode_counts.get(state.orchestration_mode, 0) + 1
        return {
            "total_executions": total,
            "completed": completed,
            "failed": failed,
            "success_rate": (completed / total * 100) if total > 0 else 0,
            "avg_execution_time": avg_execution_time,
            "by_orchestration_mode": mode_counts,
            "last_execution": history[0].updated_at if history else None
        }

        return None
