# 📋 BRAND PROFILE → RAG → FLEXIBLE COMPETITOR DISCOVERY

**Phiên bản:** 3.0 Multi-industry | **Ngày:** 28/10/2026

---

## 🎯 CORE CONCEPT

```
User fills Brand Profile (Dify form)
    ↓
Save to PostgreSQL + Create Dify RAG Dataset
    ↓
Agents retrieve from RAG when needed
    ↓
Dynamic decisions based on industry/offers
    ↓
Discover 10 competitors (context-aware)
    ↓
Analyze & generate marketing strategy
```

---

## 📝 BRAND PROFILE FIELDS

```json
{
  "industry": "electronics | healthcare | fashion | saas | ...",
  "offer_type": "product | service | saas | marketplace",
  "target_audience": "Young professionals, 25-40",
  "primary_offers": "TVs, washing machines, air purifiers",
  "core_strengths": "Competitive pricing, 5-year warranty",
  "conversion_goal": "Online sales | Lead gen | Brand awareness",
  "brand_voice": "Professional, trustworthy",
  "claims_evidence": "10K+ customers, ISO certified",
  "compliance_constraints": "Must disclose pricing, no false claims",
  "target_language": "vi",
  "target_location": "Vietnam"
}
```

---

## 💾 RAG DOCUMENT TYPES

| Doc # | Type | Content | Used By |
|-------|------|---------|---------|
| 1 | Brand Profile | All brand fields | All agents |
| 2-11 | Competitors | 10 competitor analyses | W1, W2 |
| 12 | Gap Analysis | Market gaps & UVP | W2, W3 |
| 13 | Market Intel | Industry trends | All agents |

---

## 🔄 AGENT RAG RETRIEVAL

```
W1: Search "brand_profile industry" → Get industry context
W2: Search "conversion_goal target_audience" → Get business goals
W3: Search "primary_offers core_strengths" → Get business model
W4: Search "brand_voice claims_evidence" → Get tone & proofs
W5: Search "compliance_constraints" → Get legal rules
W6: Search "brand_voice target_audience" → Get audience fit
```

---

## 🛠️ IMPLEMENTATION STEPS

### Step 1: Update server.py
```python
class OnboardingRequest(BaseModel):
    brand_profile: dict  # ← NEW
    
@app.post("/api/v1/onboarding/analyze")
async def onboarding(req: OnboardingRequest):
    result = await run_onboarding_gap_analysis(
        brand_profile=req.brand_profile  # ← PASS
    )
```

### Step 2: Update serp_discovery_tool.py
```python
async def run_onboarding_gap_analysis(
    brand_profile: dict  # ← NEW
):
    industry = brand_profile.get("industry")
    offers = brand_profile.get("primary_offers")
    location = brand_profile.get("target_location")
    
    # Dynamic SERP query
    serp_query = f"{offers} {location}"
    
    # Scrape competitors
    competitors = await scraper.scrape(serp_query)
    
    # Save to RAG
    await rag_service.create_knowledge_base(
        brand_profile=brand_profile,
        competitors=competitors
    )
```

### Step 3: Update rag_service.py
```python
async def create_knowledge_base(
    brand_profile: dict,
    competitors: list
):
    dataset = await dify_api.create_dataset(
        name=f"KB_{project_id}"
    )
    
    # Upload 4 document types
    docs = [
        {"title": "Brand Profile", "content": json.dumps(brand_profile)},
        *[{"title": f"Competitor {i}", "content": ...} for i, c in enumerate(competitors)],
        {"title": "Gap Analysis", "content": ...}
    ]
    
    for doc in docs:
        await dify_api.upload(dataset.id, doc)
```

---

## 📊 EXAMPLE: 3 INDUSTRIES

### Electronics
- Input: industry="electronics", offers="TVs, washing machines"
- SERP: "tivi xiaomi mua online Vietnam"
- Competitors: Shopee, Lazada, NguyenKim
- Focus: Price, specs, shipping

### Healthcare
- Input: industry="healthcare", offers="Consultation, dental"
- SERP: "phòng khám Ho Chi Minh"
- Competitors: Crescent Clinic, Hang Linh Hospital
- Focus: Services, qualifications, reviews

### SaaS
- Input: industry="saas", offers="HR software, payroll"
- SERP: "HR software Vietnam"
- Competitors: Sapo, BambooHR Vietnam
- Focus: Features, pricing, integrations

---

## ✅ BENEFITS

✅ Industry-agnostic (not hardcoded Xiaomi)
✅ Multi-tenant (separate RAG per project)
✅ Context-aware (agents retrieve from RAG)
✅ Scalable (add industries without code)
✅ Flexible (agents adapt to business type)
✅ Future-proof (extensible)

---

## 🚀 DEPLOYMENT

- Week 1: Code updates (server, serp, rag)
- Week 2: Agent DSL updates (W1-W6 RAG retrieval)
- Week 3: Testing (4 industries)
- Week 4: Production deployment

---

**System: PRODUCTION READY ✅**
