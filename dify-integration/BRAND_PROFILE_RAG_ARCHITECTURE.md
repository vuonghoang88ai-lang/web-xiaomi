# 🔄 FLEXIBLE RAG ARCHITECTURE - MULTI-INDUSTRY

## 🎯 NGUYÊN LÝ: BRAND PROFILE → RAG → DYNAMIC COMPETITOR DISCOVERY

Thay vì hardcode Xiaomi, hệ thống phải:
1. **Nhận Brand Profile từ người dùng** (form Dify)
2. **Lưu vào RAG** (Dify Knowledge Base + Weaviate)
3. **Agents truy xuất từ RAG** khi cần context
4. **Động xây dựng SERP query** dựa trên industry/offers
5. **Phân tích đối thủ** context-aware (không generic)

---

## 📋 BRAND PROFILE SCHEMA

```yaml
brand_profile:
  industry: "electronics" | "healthcare" | "fashion" | "saas" | "realestate" | ...
  offer_type: "product" | "service" | "saas" | "marketplace"
  target_audience: "Chuyên gia trẻ, 25-40 tuổi" | ...
  primary_offers: "Tivi Xiaomi, máy giặt, điều hòa" | ...
  core_strengths: "Giá cạnh tranh, bảo hành 5 năm" | ...
  conversion_goal: "Bán hàng online" | "Lead gen" | ...
  brand_voice: "Chuyên nghiệp, thân thiện" | ...
  claims_evidence: "10K+ khách hàng, ISO certified" | ...
  compliance_constraints: "Phải khai báo giá, không quảng cáo sai" | ...
  target_language: "vi"
  target_location: "Vietnam" | "Ha Noi" | "Ho Chi Minh" | ...
```

---

## 🔍 DYNAMIC SERP QUERY CONSTRUCTION

```python
def construct_serp_query(industry, primary_offers, target_location):
    """
    Build contextual SERP query based on industry & offers
    """
    
    if industry == "electronics":
        return f"{primary_offers} mua online {target_location}"
    
    elif industry == "healthcare":
        return f"{primary_offers} phòng khám {target_location}"
    
    elif industry == "fashion":
        return f"{primary_offers} shop {target_location}"
    
    elif industry == "saas":
        return f"{primary_offers} phần mềm {target_location}"
    
    elif industry == "realestate":
        return f"{primary_offers} bất động sản {target_location}"
    
    else:
        return f"{primary_offers} {target_location}"
```

---

## 💾 RAG DOCUMENTS (4 TYPES)

### Doc 1: Brand Profile
```
Title: Brand Profile - {project_name}
Content: All brand_profile fields
Type: brand_profile
Used by: All agents (context retrieval)
```

### Doc 2: Competitor Profiles (10 docs)
```
Title: Competitor - {domain}
Content: Domain, rank, strengths, weaknesses, pricing, UX
Type: competitor_profile
Used by: W1, W2 (competitive analysis)
```

### Doc 3: Gap Analysis
```
Title: Market Gap Analysis - {industry}
Content: Your gaps vs 10 competitors, UVP suggestions
Type: gap_analysis
Used by: W2, W3 (strategy)
```

### Doc 4: Market Intelligence
```
Title: Market Intelligence - {industry} {location}
Content: Market trends, customer behavior, regulations
Type: market_intelligence
Used by: All agents (context)
```

---

## 🧠 RAG RETRIEVAL PATTERN

### W1: Market Intel
```
RAG Search: "brand_profile, industry, offer_type"
Use: Construct SERP query
```

### W2: Data Analyst
```
RAG Search: "conversion_goal, target_audience"
Use: Prioritize keywords by business goals
```

### W3: SEO Strategist
```
RAG Search: "primary_offers, core_strengths"
Use: Design silo aligned with business
```

### W4: Copywriter
```
RAG Search: "brand_voice, claims_evidence, compliance"
Use: Write brand-aligned, compliant content
```

### W5: QA Publisher
```
RAG Search: "compliance_constraints"
Use: Validate legal compliance
```

### W6: Social Repurposer
```
RAG Search: "brand_voice, target_audience"
Use: Create audience-aligned social content
```

---

## 🚀 IMPLEMENTATION ROADMAP

### Phase 1: Update serp_discovery_tool.py
- Extract brand_profile from input
- Construct dynamic SERP query
- Load industry-specific knowledge

### Phase 2: Update rag_service.py
- Create 4 document types
- Upload to Dify dataset
- Index in Weaviate

### Phase 3: Update W1-W6 Agents
- Add RAG retrieval calls
- Use brand_profile in prompts
- Make context-aware decisions

### Phase 4: Testing
- Test electronics (Xiaomi)
- Test healthcare (clinic)
- Test SaaS (HR platform)
- Test fashion
- Verify flexibility

---

## ✅ BENEFITS

✅ Industry-agnostic (electronics, healthcare, fashion, saas, etc)
✅ Multi-tenant (each tenant has separate brand profile + RAG)
✅ Context-aware agents (not hardcoded logic)
✅ Scalable (easily add new industries)
✅ Flexible (agents retrieve context from RAG)

---

**This makes system truly FLEXIBLE & MULTI-INDUSTRY! 🎯**
