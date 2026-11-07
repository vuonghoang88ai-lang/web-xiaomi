# 🏗️ KIẾN TRÚC HỆ THỐNG: DIFY + MARKETING ENGINE HYBRID

**Phiên bản:** 2.0 Hybrid (Dify Native + FastAPI Microservice)  
**Ngày:** 28/10/2026

---

## 📐 3 TẦNG KIẾN TRÚC CHÍNH

```
TIER 1: DIFY (:8090)
├─ CMO Agent (Master controller)
├─ 6 Sub-agents: W1-W6
├─ Knowledge Base (8 SOP files)
└─ Custom Tools (WordPress + Marketing Engine)

TIER 2: MARKETING ENGINE (:8085)
├─ FastAPI Gateway (server.py)
├─ Agent Orchestrator (run_full_pipeline)
├─ Universal Scraper (JSON-LD + OpenGraph + DOM)
├─ SERP Discovery + UVP Analysis
├─ RAG Manager (Dify Datasets)
├─ Multi-tenant PostgreSQL
└─ Google Search Console OAuth

TIER 3: WORDPRESS + DB (:8080)
├─ WordPress (Apache + PHP 8.2)
├─ MariaDB + Redis
└─ Omni Bridge Plugin
```

---

## 🤖 7 AGENTS HIERARCHY

### CMO AI Copilot (👑 Master)
- Validates tenant_id, validates input
- Routes to W1-W6 based on workflow type
- Returns consolidated output

### W1: Market Intel Agent (🕵️)
- Query SERP (DuckDuckGo TOP 10)
- Scrape competitors (JSON-LD + OpenGraph)
- Extract: pricing, structure, images
- Output: competitor profiles + content gaps

### W2: Data Analyst (📊)
- Classify intent: A/B/C/D
- Assign priority: P1/P2/P3
- Determine content type
- Output: strategy + CRO tips

### W3: SEO Strategist (🏛️)
- Design silo structure (3-tier)
- Create heading hierarchy (H1-H3)
- Plan internal links (4-direction)
- Output: outline + link strategy

### W4: Senior Copywriter (✍️)
- Write 1500-2500 word article
- Follow SOP 12 (0% AI filler)
- Real data, real examples
- Output: full article markdown

### W5: QA Publisher (🛡️)
- Validate 7 layers (≥75 points pass)
- Publish to WordPress draft
- Output: QA score + draft URL

### W6: Social Repurposer (📱)
- Create TikTok 60s script
- Create Facebook post
- Trigger: only if W5 QA ≥ 75
- Output: social content

---

## 🔧 MARKETING ENGINE: 7 MODULES

### 1. server.py (FastAPI Gateway :8085)

**Endpoints:**
- `POST /api/v1/onboarding/analyze` → serp_discovery_tool
- `POST /api/v1/research` → scraper_tool
- `POST /api/v1/campaign` → agent_orchestrator
- `POST /api/v1/campaign/batch` → background worker
- `GET /api/v1/jobs/{job_id}` → job status
- `GET /health` → server status

### 2. agent_orchestrator.py (Pipeline Controller)

**Orchestration:**
```
W1 → W2 → W3 → W4 → [HITL?] → W5 → W6
```

Each stage calls Dify API, passes output to next stage.

### 3. scraper_tool.py (Universal Scraper)

**Multi-level parsing:**
1. JSON-LD Schema (Priority 1)
2. OpenGraph meta (Priority 2)
3. Heuristic DOM (Priority 3)
4. Price regex extraction
5. Image curation (remove watermarks)
6. De-duplication & aggregation

### 4. serp_discovery_tool.py (SERP + Gap Analysis)

**Phases:**
1. Inspect your website
2. Query SERP (DuckDuckGo)
3. Scrape TOP 10 competitors
4. Analyze gaps
5. Generate UVP suggestions
6. Create Dify RAG dataset

### 5. rag_service.py (RAG Manager)

**Functions:**
- Create Dify dataset per project
- Upload competitor profiles
- Index into Weaviate
- Enable agents to retrieve context

### 6. db_service.py (PostgreSQL)

**Tables:**
- tenants, users, projects
- project_competitors
- market_intelligence_reports
- gsc_connections, gsc_performance_rows

**All queries use tenant_id for isolation**

### 7. gsc_service.py (Google Search Console)

**OAuth Flow:**
- Build authorization URL
- Exchange code for refresh token
- Refresh access tokens
- Query Search Analytics
- Cache performance data

---

## 📚 KNOWLEDGE BASE (8 SOP FILES)

| File | Purpose | Agent |
|------|---------|-------|
| SOP 12 | Content writing (0% filler) | W4 |
| SOP 11 | Image processing | W4/W6 |
| Xiaomi Specs | Product database | All |
| SOP 16 | 30-day autopilot | W1/W2 |
| Marketing Roles | Team structure | CMO |
| SOP 17 | RAG architecture | System |
| SOP 18 | Batch pipeline | W1/W2 |
| SOP 19 | GSC + CMS bridge | System |

---

## 🔄 DATA FLOW EXAMPLE

```
User: "SEO article for 'tivi xiaomi 55 inch'"
    ↓
CMO Agent: Validate input, route to W1
    ↓
W1: SERP query → scrape 10 competitors → pricing/structure
    ↓
W2: Classify intent (B/C) → priority P1 → content type
    ↓
W3: Design outline, heading hierarchy, internal links
    ↓
W4: Write 2000-word article (SOP 12)
    ↓
[Optional HITL: Human review]
    ↓
W5: QA 7-layer validation → if ≥75: WordPress draft
    ↓
W6: Create TikTok + Facebook (if QA passed)
    ↓
Output: Article + QA score + draft URL + social content
```

---

## ✅ DEPLOYMENT STATUS

✅ Server :8085 running  
✅ Dify :8090 running  
✅ WordPress :8080 running  
✅ All 7 agents configured  
✅ Custom tools integrated  
✅ Knowledge base ready  
✅ PostgreSQL multi-tenant  
✅ Autopilot 30-day calendar  
🚀 **Production ready**

