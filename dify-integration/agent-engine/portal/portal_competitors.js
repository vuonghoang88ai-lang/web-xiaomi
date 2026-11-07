/**
 * portal_competitors.js
 * Renders the 10 SERP Competitors Tab and competitor cards grid
 * Fully compliant with SOP 23 (<= 300 lines)
 */

function renderCompetitorsTab(competitors, latestReport, seedAnalysis, project, targetAnalysis) {
  state.competitors = competitors || [];
  const targetContainer = document.getElementById('targetWebsiteSection');
  const seedContainer = document.getElementById('seedCompetitorSection');
  const compGrid = document.getElementById('competitorsGrid');
  if (!compGrid) return;

  const currentSeedUrl = (project && project.seed_competitor_url) || (seedAnalysis && seedAnalysis.seed_url) || '';

  // 0. Render Target Website Deep Audit Section
  if (typeof renderTargetWebsiteSection === 'function') {
    renderTargetWebsiteSection(targetContainer, targetAnalysis, project);
  }

  // 1. Render Seed Competitor Section
  if (seedContainer && typeof renderSeedCompetitorCard === 'function') {
    seedContainer.innerHTML = renderSeedCompetitorCard(seedAnalysis, project, currentSeedUrl);
  }

  // Cập nhật số lượng đối thủ Ads vs SEO
  const adsCount = state.competitors.filter(c => c.competitor_type === 'paid_ads' || c.is_ad).length;
  const seoCount = state.competitors.filter(c => c.competitor_type !== 'paid_ads' && !c.is_ad).length;
  const cntAll = document.getElementById('cntAllComp');
  const cntAds = document.getElementById('cntAdsComp');
  const cntSeo = document.getElementById('cntSeoComp');
  if (cntAll) cntAll.textContent = state.competitors.length;
  if (cntAds) cntAds.textContent = adsCount;
  if (cntSeo) cntSeo.textContent = seoCount;

  // 2. Render lưới đối thủ
  if (state.competitors.length === 0) {
    compGrid.innerHTML = `
      <div class="card" style="grid-column: 1 / -1; text-align: center; padding: 40px 20px;">
        <div style="font-size: 32px; margin-bottom: 12px;">🕵️‍♂️</div>
        <h4 style="font-size: 16px; margin-bottom: 8px;">Chưa có dữ liệu đối thủ cho dự án này</h4>
        <p style="color: var(--text-muted); font-size: 13px; max-width: 520px; margin: 0 auto 16px;">
          Hãy nhập URL đối thủ mẫu ở khung phía trên và bấm <em>"Giải Mã & Quét Lại Đối Thủ"</em> để AI tự động cào và bóc tách dữ liệu.
        </p>
      </div>
    `;
    return;
  }

  let html = state.competitors.map((c, idx) => {
    const isOnline = c.is_online !== false;
    const onlineBadge = isOnline 
      ? '<span style="color: #10b981; font-size: 11px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;"><span style="width: 6px; height: 6px; border-radius: 50%; background: #10b981;"></span>Online</span>'
      : '<span style="color: #ef4444; font-size: 11px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;"><span style="width: 6px; height: 6px; border-radius: 50%; background: #ef4444;"></span>Offline</span>';

    const isBrandCollision = c.is_brand_competitor || (c.scraped_structure && c.scraped_structure.is_brand_competitor);
    const brandBadge = isBrandCollision
      ? '<span style="background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.4); font-size: 10.5px; font-weight: 600; padding: 2px 7px; border-radius: 4px;">🚨 Tranh chấp</span>'
      : '';

    const isSeedBadge = c.is_seed
      ? '<span style="background: rgba(168, 85, 247, 0.2); color: #c084fc; border: 1px solid rgba(168, 85, 247, 0.4); font-size: 10.5px; font-weight: 600; padding: 2px 7px; border-radius: 4px;">🎯 Đối thủ mẫu</span>'
      : '';

    const isPaidAd = c.competitor_type === 'paid_ads' || c.is_ad;
    const adBadge = isPaidAd
      ? '<span style="background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.4); font-size: 10.5px; font-weight: 700; padding: 2px 7px; border-radius: 4px;">📢 Google Ads</span>'
      : '<span style="background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.4); font-size: 10.5px; font-weight: 700; padding: 2px 7px; border-radius: 4px;">🌿 Top SEO</span>';

    let adDetailsHtml = '';
    const adInfo = c.ad_details || (c.scraped_structure && c.scraped_structure.ad_details);
    if (isPaidAd && adInfo && (adInfo.headline || adInfo.snippet)) {
      adDetailsHtml = `
        <div style="margin-top: 10px; padding: 9px 12px; background: rgba(239, 68, 68, 0.06); border: 1px solid rgba(239, 68, 68, 0.25); border-radius: 6px; font-size: 12px;">
          <div style="font-weight: 700; color: #f87171; margin-bottom: 3px;">📢 Ad Copy Google Ads:</div>
          <div style="color: #cbd5e1; font-weight: 600;">${escapeHtml(adInfo.headline || c.title || '')}</div>
          <div style="color: var(--text-muted); font-size: 11.5px; margin-top: 3px;">${escapeHtml(adInfo.snippet || '')}</div>
        </div>
      `;
    }

    let priceHtml = '';
    if (c.pricing_data && Object.keys(c.pricing_data).length > 0) {
      priceHtml = `
        <div style="margin-top: 10px; padding: 8px 10px; background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 6px; font-size: 12px; color: #10b981;">
          💰 <strong>Giá bóc tách:</strong> ${JSON.stringify(c.pricing_data).slice(0, 90)}...
        </div>
      `;
    }

    let deepAuditHtml = '';
    const struct = c.scraped_structure || {};
    const deepAudit = struct.deep_audit || null;
    if (deepAudit) {
      const cms = deepAudit.cms_detected || deepAudit.onpage_audit?.cms_platform || 'N/A';
      const daVal = deepAudit.moz_da !== undefined ? deepAudit.moz_da : deepAudit.pillars?.pillar8_authority_backlinks?.moz_da;
      const da = daVal ? `DA: ${daVal}` : null;
      const ageVal = deepAudit.domain_age_years || deepAudit.onpage_audit?.domain_age_years;
      const age = ageVal ? `${ageVal} năm` : null;

      deepAuditHtml = `
        <div style="margin-top: 10px; padding: 10px; background: rgba(30, 41, 59, 0.5); border: 1px solid rgba(99, 102, 241, 0.25); border-radius: 6px; font-size: 11.5px;">
          <div style="display: flex; flex-wrap: wrap; gap: 5px; margin-bottom: 8px;">
            <span style="padding: 2px 6px; background: rgba(59, 130, 246, 0.15); border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 4px; color: #93c5fd;">CMS: ${escapeHtml(cms)}</span>
            ${da ? `<span style="padding: 2px 6px; background: rgba(168, 85, 247, 0.15); border: 1px solid rgba(168, 85, 247, 0.3); border-radius: 4px; color: #d8b4fe;">${escapeHtml(da)}</span>` : ''}
            ${age ? `<span style="padding: 2px 6px; background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 4px; color: #6ee7b7;">Tuổi: ${escapeHtml(age)}</span>` : ''}
          </div>
          <button type="button" class="btn-primary" onclick="openCompetitorDeepAuditModal('${escapeHtml(c.domain)}', '${escapeHtml(c.url)}', ${idx})" style="width: 100%; font-size: 11.5px; padding: 6px 10px; background: rgba(99, 102, 241, 0.25); border: 1px solid var(--primary); border-radius: 4px; color: #c7d2fe; cursor: pointer;">
            <span>🔍</span> Xem Chi Tiết 6 Trụ Cột Đột Kích
          </button>
        </div>
      `;
    } else {
      deepAuditHtml = `
        <div style="margin-top: 10px;">
          <button type="button" class="btn-secondary" id="btnAudit_${idx}" onclick='deepAuditSingleCompetitor("${escapeHtml(c.url)}", "${escapeHtml(c.domain)}", "btnAudit_${idx}")' style="width: 100%; font-size: 11.5px; padding: 6px 10px; background: rgba(16, 185, 129, 0.12); border: 1px solid rgba(16, 185, 129, 0.4); border-radius: 4px; color: #34d399; cursor: pointer;">
            <span>⚡</span> Đột Kích 6 Trụ Cột Đối Thủ
          </button>
        </div>
      `;
    }

    return `
      <div class="card comp-card" data-comp-type="${isPaidAd ? 'paid_ads' : 'organic_seo'}" style="${isBrandCollision ? 'border-color: rgba(239, 68, 68, 0.5); background: rgba(239, 68, 68, 0.03);' : ''}">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; flex-wrap: wrap; gap: 6px;">
          <div style="display: flex; gap: 6px; align-items: center; flex-wrap: wrap;">
            <span class="card-badge rank">TOP ${c.top_rank || (idx + 1)} SERP</span>
            ${adBadge}
            ${isSeedBadge}
            ${brandBadge}
          </div>
          ${onlineBadge}
        </div>
        <h4 class="card-title">${escapeHtml(c.domain)}</h4>
        <p class="card-subtitle"><a href="${escapeHtml(c.url)}" target="_blank" style="color: var(--primary); text-decoration: none; word-break: break-all;">${escapeHtml(c.url)}</a></p>
        <div style="font-size: 13px; color: var(--text-muted); line-height: 1.5; margin-top: 10px;">
          <p><strong>Điểm mạnh:</strong> ${escapeHtml(c.strengths || 'Đang phân tích...')}</p>
          <p style="margin-top: 6px;"><strong>Điểm yếu / Khoảng trống:</strong> ${escapeHtml(c.weaknesses || 'Đang phân tích...')}</p>
        </div>
        ${adDetailsHtml}
        ${priceHtml}
        ${deepAuditHtml}
      </div>
    `;
  }).join('');

  if (latestReport && latestReport.strategic_roadmap) {
    html += `
      <div class="card" style="grid-column: 1 / -1; margin-top: 16px; background: rgba(99, 102, 241, 0.05); border-color: rgba(99, 102, 241, 0.3);">
        <div style="display: flex; justify-content: space-between; align-items: center; cursor: pointer;" onclick="document.getElementById('roadmapBody').classList.toggle('hidden')">
          <div style="display: flex; align-items: center; gap: 8px;">
            <span style="font-size: 18px;">📋</span>
            <h4 style="color: #818cf8; font-size: 15px; margin: 0;">Báo Cáo Chiến Lược & Khoảng Trống Thị Trường (Gap Analysis)</h4>
          </div>
          <span style="font-size: 12px; color: var(--primary); font-weight: 600;">Xem / Ẩn báo cáo ▼</span>
        </div>
        <div id="roadmapBody" style="margin-top: 16px; font-size: 13px; color: var(--text-muted); line-height: 1.6; border-top: 1px solid rgba(99, 102, 241, 0.2); padding-top: 14px;">
          ${parseMarkdown(latestReport.strategic_roadmap)}
        </div>
      </div>
    `;
  }

  compGrid.innerHTML = html;
}
