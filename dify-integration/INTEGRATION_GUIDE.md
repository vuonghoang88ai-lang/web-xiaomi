# 📚 INTEGRATION GUIDE: Dify + CrewAI Orchestration

## 🚀 Quick Start

### 1. Setup Environment
```bash
cd /home/ubuntu/michinhhang/dify-integration
cp .env.example .env
nano .env
```

### 2. Install Dependencies
```bash
cd agent-engine
pip install -r requirements.txt
```

### 3. Run Autopilot
```bash
cd ../autopilot
python3 autopilot_runner_v2.py
```

---

## 🔧 Configuration

### Mode Selection
```bash
ORCHESTRATION_MODE=auto
ORCHESTRATION_FALLBACK_CHAIN=crewai,dify,router_direct
```

### CrewAI Setup
```bash
USE_CREWAI=true
CREWAI_AGENT_TIMEOUT=120
ROUTER_API_KEY=your_api_key
```

### Dify Setup
```bash
DIFY_API_BASE=http://localhost:8090/v1
DIFY_WORKFLOW_KEY=your_key
```

### State Management
```bash
STATE_BACKEND=file
# STATE_BACKEND=postgresql
# STATE_DB_URL=postgresql://user:pass@localhost/db
```

---

## 💡 Usage Examples

### Example 1: Direct Execution
```python
import asyncio
from agent_engine.orchestration_manager import OrchestrationManager

async def main():
    manager = OrchestrationManager()
    result = await manager.execute(
        keyword="xiaomi 12 pro",
        category="điện thoại"
    )
    print(result)

asyncio.run(main())
```

### Example 2: With Fallback
```python
from agent_engine.retry_handler import OrchestrationRetryHandler

retry_handler = OrchestrationRetryHandler(max_retries_per_mode=2)
result, mode, attempts = await retry_handler.execute_with_fallback_chain(
    modes=["crewai", "dify", "router_direct"],
    execute_func=manager.execute,
    keyword="xiaomi",
    category="điện thoại"
)
print(f"Success with {mode} after {attempts} attempts")
```

### Example 3: State Tracking
```python
from agent_engine.state_manager import StateManager, ExecutionState

state_mgr = StateManager()
exec_state = ExecutionState(
    project_id="autopilot_main",
    day=1,
    keyword="xiaomi",
    category="điện thoại",
    orchestration_mode="crewai",
    status="completed"
)
state_mgr.save_execution(exec_state)
stats = state_mgr.get_stats("autopilot_main")
print(f"Success rate: {stats['success_rate']}%")
```

---

## 📊 Monitoring

### Check Logs
```bash
tail -f agent-engine/orchestration.log
```

### Get Stats
```python
from agent_engine.state_manager import StateManager
state_mgr = StateManager()
stats = state_mgr.get_stats("autopilot_main")
print(stats)
```

---

## 🔄 Orchestration Modes

| Mode | Speed | Complexity | Best For |
|------|-------|-----------|----------|
| CrewAI | Medium (~120s) | High | Complex workflows |
| Dify | Fast (~180s) | Medium | Linear workflows |
| 9Router | Fastest (~90s) | Low | Fallback/Testing |

---

## ⚠️ Troubleshooting

### CrewAI Not Found
```bash
python -c "import crewai; print(crewai.__version__)"
pip install crewai>=0.30.0
```

### Dify Connection Failed
```bash
curl http://localhost:8090/v1/workflows/list
```

### 9Router Failed
```bash
curl -X POST http://100.73.214.3:40128/v1/chat/completions
```

---

## ✅ Verification Checklist
- [ ] `.env` configured
- [ ] CrewAI installed
- [ ] Dify running
- [ ] 9Router accessible
- [ ] Test run successful
