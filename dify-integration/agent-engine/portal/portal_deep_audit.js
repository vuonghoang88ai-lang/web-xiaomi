/**
 * Portal Deep Audit Controller
 * 6-Pillar Competitor Deep Audit
 * SOP 23 Compliant (< 300 lines)
 */

async function deepAuditSingleCompetitor(competitorUrl, competitorDomain, btnId) {
  if (!state.activeProjectId) {
    alert('Chưa chọn dự án.');
    return;
  }
  const btn = document.getElementById(btnId);
  if (btn) {
    btn.disabled = true;
    btn.innerHTML = '<span>⏳</span> Đang đột kích 6 trụ cột...';
  }

  try {
    const res = await fetch(`/api/v1/portal/projects/${state.activeProjectId}/competitors/deep-audit`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ competitor_url: competitorUrl, competitor_domain: competitorDomain })
    });
    const data = await res.json();
    if (res.ok && data.status === 'success') {
      openCompetitorDeepAuditModal(competitorDomain, competitorUrl, data.deep_audit);
      loadProjectIntelligence(state.activeProjectId);
    } else {
      alert(data.detail || data.message || 'Không thể đột kích đối thủ.');
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = '<span>⚡</span> Đột Kích 6 Trụ Cột Đối Thủ';
      }
    }
  } catch (err) {
    console.error('Lỗi đột kích đối thủ:', err);
    alert('Lỗi kết nối: ' + err.message);
    if (btn) {
      btn.disabled = false;
      btn.innerHTML = '<span>⚡</span> Đột Kích 6 Trụ Cột Đối Thủ';
    }
  }
}
window.deepAuditSingleCompetitor = deepAuditSingleCompetitor;

function openCompetitorDeepAuditModal(domain, url, compIdxOrData) {
  const modal = document.getElementById('competitorDeepAuditModal');
  const title = document.getElementById('compModalTitle');
  const subtitle = document.getElementById('compModalSubtitle');
  const content = document.getElementById('compModalContent');
  if (!modal || !content) return;

  let audit = null;
  if (compIdxOrData && typeof compIdxOrData === 'object' && (compIdxOrData.domain || compIdxOrData.status)) {
    audit = compIdxOrData;
  } else if (compIdxOrData !== undefined && compIdxOrData !== null && !isNaN(compIdxOrData) && state.competitors && state.competitors[compIdxOrData]) {
    const comp = state.competitors[compIdxOrData];
    let struct = comp.scraped_structure;
    if (typeof struct === 'string') {
      try { struct = JSON.parse(struct); } catch (e) { struct = {}; }
    }
    audit = struct && struct.deep_audit ? struct.deep_audit : null;
    domain = comp.domain || domain;
    url = comp.url || url;
  }

  // Fallback search in state.competitors
  if (!audit && state.competitors) {
    const cleanDomain = (domain || '').replace(/^https?:\/\//i, '').replace(/^www\./i, '').split('/')[0].toLowerCase();
    const found = state.competitors.find(c => {
      const cDom = (c.domain || '').replace(/^www\./i, '').toLowerCase();
      const cUrl = (c.url || '').toLowerCase();
      return cDom === cleanDomain || cUrl === (url || '').toLowerCase() || cUrl.includes(cleanDomain);
    });
    if (found) {
      let struct = found.scraped_structure;
      if (typeof struct === 'string') {
        try { struct = JSON.parse(struct); } catch (e) { struct = {}; }
      }
      audit = struct && struct.deep_audit ? struct.deep_audit : null;
      domain = found.domain || domain;
      url = found.url || url;
    }
  }

  if (!audit) {
    alert('Chưa có dữ liệu phân tích 6 trụ cột cho đối thủ này. Vui lòng bấm "Đột Kích 6 Trụ Cột Đối Thủ" trước.');
    return;
  }

  if (title) title.textContent = `Đột Kích 6 Trụ Cột: ${domain}`;
  if (subtitle) subtitle.innerHTML = `URL Khảo Sát: <a href="${escapeHtml(url)}" target="_blank" style="color: #60a5fa; text-decoration: none;">${escapeHtml(url)} ↗</a>`;

  const cms = audit.cms_detected || audit.onpage_audit?.cms_platform || audit.pillars?.pillar3_technical_performance?.cms_platform || 'Custom Web';
  const ageY = audit.domain_age_years || audit.onpage_audit?.domain_age_years || audit.pillars?.pillar8_authority_backlinks?.age_years || 0;
  const mozDa = audit.moz_da !== undefined ? audit.moz_da : audit.pillars?.pillar8_authority_backlinks?.moz_da;
  const ttfb = audit.ttfb_seconds || audit.pillars?.pillar3_technical_performance?.ttfb_seconds || 0;
  const words = audit.avg_word_count || audit.onpage_audit?.avg_word_count || audit.pillars?.pillar2_content_eeat?.avg_word_count || 0;
  const altPct = audit.alt_coverage_pct || audit.onpage_audit?.alt_coverage_pct || audit.pillars?.pillar2_content_eeat?.alt_coverage_pct || 0;
  const schemas = audit.schemas_found || audit.onpage_audit?.schemas || [];
  const fresh = audit.freshness || audit.pillars?.pillar2_content_eeat?.content_freshness || {};
  const channels = audit.social_channels || audit.pillars?.pillar7_entity_social?.channels || {};
  const citations = audit.external_citations || audit.pillars?.pillar8_authority_backlinks?.referring_samples || [];
  const kws = audit.extracted_keywords || [];
  const paa = audit.paa_questions || [];

  const headToHeadCompHtml = typeof renderHeadToHeadComparison === 'function'
    ? renderHeadToHeadComparison(state.targetAnalysis, audit, domain, cms, ageY, words, schemas)
    : '';

  if (typeof renderDeepAuditModalContent === 'function') {
    content.innerHTML = renderDeepAuditModalContent(
      audit, domain, headToHeadCompHtml, cms, ageY, mozDa, ttfb, words, altPct, schemas, fresh, channels, citations, kws, paa
    );
  }

  modal.classList.add('active');
  modal.style.display = 'flex';
}
window.openCompetitorDeepAuditModal = openCompetitorDeepAuditModal;

function closeCompetitorDeepAuditModal() {
  const modal = document.getElementById('competitorDeepAuditModal');
  if (modal) {
    modal.classList.remove('active');
    modal.style.display = 'none';
  }
}
window.closeCompetitorDeepAuditModal = closeCompetitorDeepAuditModal;
