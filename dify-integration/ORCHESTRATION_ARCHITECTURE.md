# 🏗️ ORCHESTRATION ARCHITECTURE: Dify + CrewAI + 9Router

## 📋 Tóm Tắt

Hệ thống đã được tái cấu trúc để **tránh conflict** giữa Dify, CrewAI, và 9Router fallback. Kiến trúc mới sử dụng **Unified Orchestration Layer** với:

- ✅ **Single Entry Point** - OrchestrationManager
- ✅ **Standardized Output** - UnifiedOutput format
- ✅ **Centralized State** - StateManager (file/PostgreSQL/Redis)
- ✅ **Smart Retry** - OrchestrationRetryHandler
- ✅ **Auto Fallback** - Graceful mode switching

---

## 🔌 ARCHITECTURE DIAGRAM

```
┌─────────────────────────────────────────────────────────────┐
│          AUTOPILOT SCHEDULER (autopilot_runner_v2.py)       │
│              Entry Point - Orchestrates Daily Tasks          │
└────────────────┬────────────────────────────────────────────┘
                 │
                 ▼
        ┌────────────────────────────┐
        │ OrchestrationManager       │
        │ - Unified orchestration    │
        │ - Mode detection           │
        │ - Config management        │
        └────────────────┬───────────┘
                         │
         ┌───────────────┼──────────────┬──────────────┐
         │               │              │              │
         ▼               ▼              ▼              ▼
    ┌─────────┐   ┌──────────┐   ┌──────────────┐  ┌─────────┐
    │ CrewAI  │   │  Dify    │   │  9Router     │  │Fallback │
    │ Agents  │   │Workflow  │   │ Direct Call  │  │ Chain   │
    │ (async) │   │ (API)    │   │ (LLM)        │  │ (Auto)  │
    └────┬────┘   └────┬─────┘   └──────┬───────┘  └─────────┘
         │             │                │
         └─────────────┼────────────────┘
                       ▼
        ┌──────────────────────────────┐
        │ OutputAdapter                │
        │ - Standardize to UnifiedOutput
        │ - Validate completeness      │
        └──────────────┬───────────────┘
                       │
         ┌─────────────┼──────────────┐
         │             │              │
         ▼             ▼              ▼
    ┌──────────┐  ┌────────────┐  ┌──────────┐
    │WordPress │  │State Mgr   │  │Campaign  │
    │Publisher │  │(DB/File)   │  │Tracker   │
    └──────────┘  └────────────┘  └──────────┘
```

---

## 🔑 CORE COMPONENTS

### 1. OrchestrationManager
Điều phối tất cả modes. Auto-detect từ environment.

### 2. OutputAdapter  
Convert bất kỳ mode nào thành UnifiedOutput format.

### 3. StateManager
Quản lý state tập trung (file/PostgreSQL/Redis).

### 4. RetryHandler
Retry logic với exponential backoff.

---

## ⚙️ CONFIGURATION

```bash
# Orchestration Mode
ORCHESTRATION_MODE=auto                          # auto|crewai|dify|router_direct
ORCHESTRATION_FALLBACK_CHAIN=crewai,dify,router_direct

# CrewAI
USE_CREWAI=true
CREWAI_AGENT_TIMEOUT=120

# Dify
DIFY_WORKFLOW_KEY=your_key
DIFY_TIMEOUT=180

# 9Router
ROUTER_API_KEY=your_key
ROUTER_TIMEOUT=90

# State Management
STATE_BACKEND=file                               # file|postgresql|redis
STATE_DB_URL=postgresql://...
```

---

## 🚫 CONFLICT PREVENTION SOLUTIONS

### Problem: LLM Provider Duplication
**Solution:** Single unified 9Router config, all modes use same endpoint

### Problem: Tool Conflicts  
**Solution:** Isolated tool sets per mode (crewai_tools.py, dify_tools.py, router_tools.py)

### Problem: State Inconsistency
**Solution:** StateManager centralizes all state

### Problem: Double Retry
**Solution:** OrchestrationRetryHandler manages unified retry logic
