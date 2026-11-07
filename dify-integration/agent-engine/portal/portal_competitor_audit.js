async function analyzeSeedCompetitor() {
  if (!state.activeProjectId) {
    alert('Chưa chọn dự án.');
    return;
  }
  const input = document.getElementById('inputSeedCompetitorUrl');
  let seedUrl = input ? input.value.trim() : '';
  if (!seedUrl) {
    alert('Vui lòng nhập domain hoặc URL đối thủ mẫu (ví dụ: mihanoi.vn)');
    return;
  }
  if (typeof clientNormalizeUrl === 'function') {
    seedUrl = clientNormalizeUrl(seedUrl);
  }

  const btn = document.getElementById('btnAnalyzeSeed');
  if (btn) {
    btn.disabled = true;
    btn.innerHTML = '<span>⏳</span> Đang bóc tách On-page & quét SERP...';
  }

  try {
    const res = await fetch(`/api/v1/portal/projects/${state.activeProjectId}/analyze-seed`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ seed_competitor_url: seedUrl })
    });
    const cType = res.headers.get('content-type') || '';
    const data = cType.includes('application/json') ? await res.json() : { detail: await res.text() || `Lỗi máy chủ (${res.status})` };
    if (res.ok && data.status === 'success') {
      alert(data.message || 'Đã giải mã thành công đối thủ mẫu và cập nhật 10 đối thủ SERP!');
      await loadProjectIntelligence(state.activeProjectId);
    } else {
      alert(data.detail || data.message || 'Không thể phân tích đối thủ mẫu.');
    }
  } catch (err) {
    console.error('Lỗi phân tích đối thủ mẫu:', err);
    alert('Lỗi kết nối khi phân tích: ' + err.message);
  } finally {
    if (btn) {
      btn.disabled = false;
      btn.innerHTML = '<span>🔍</span> Giải Mã & Quét Lại';
    }
  }
}
window.analyzeSeedCompetitor = analyzeSeedCompetitor;

async function rescanCompetitors() {
  if (!state.activeProjectId) {
    alert('Chưa chọn dự án để quét đối thủ.');
    return;
  }
  const btn = document.getElementById('btnRescanCompetitors');
  const icon = document.getElementById('rescanIcon');
  const text = document.getElementById('rescanText');

  if (btn) btn.disabled = true;
  if (icon) icon.textContent = '⏳';
  if (text) text.textContent = 'Đang quét SERP & kiểm tra Online...';

  try {
    const res = await fetch(`/api/v1/portal/projects/${state.activeProjectId}/rescan-competitors`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' }
    });
    const cType = res.headers.get('content-type') || '';
    const data = cType.includes('application/json') ? await res.json() : { detail: await res.text() || `Lỗi máy chủ (${res.status})` };
    if (res.ok && data.status === 'success') {
      alert(data.message || 'Đã quét xong 10 đối thủ SERP Online 100%!');
      await loadProjectIntelligence(state.activeProjectId);
    } else {
      alert(data.detail || data.message || 'Không thể quét đối thủ.');
    }
  } catch (err) {
    console.error('Error rescanning competitors:', err);
    alert('Lỗi kết nối khi quét đối thủ: ' + err.message);
  } finally {
    if (btn) btn.disabled = false;
    if (icon) icon.textContent = '🔄';
    if (text) text.textContent = 'Quét Lại Đối Thủ (Online 100%)';
  }
}
window.rescanCompetitors = rescanCompetitors;

// ==========================================
// TRINH SÁT & ĐỘT KÍCH 6 TRỤ CỘT ĐỐI THỦ & WEB ĐÍCH
// ==========================================

function renderTargetWebsiteSection(container, targetAudit, project) {
  if (!container) return;
  const targetUrl = (project && project.target_website_url) || '';
  if (!targetUrl) {
    container.innerHTML = '';
    return;
  }

  let bp = project ? project.brand_profile : {};
  if (typeof bp === 'string') {
    try { bp = JSON.parse(bp); } catch (e) { bp = {}; }
  }
  const audit = targetAudit || (bp && (bp.target_analysis || bp.my_site_audit)) || null;

  if (audit && (audit.status === 'success' || audit.title || audit.onpage_audit)) {
    const pillars = audit.pillars || {};
    const p1 = pillars.pillar1_architecture || {};
    const p2 = pillars.pillar2_content_eeat || {};
    const p3 = pillars.pillar3_technical_performance || {};
    const p7 = pillars.pillar7_entity_social || {};
    const p8 = pillars.pillar8_authority_backlinks || {};

    const cmsName = p3.cms_platform || audit.onpage_audit?.cms_platform || audit.cms_detected || 'Custom Web';
    const ttfbVal = p3.ttfb_seconds || audit.ttfb_seconds || 0;
    const wordCount = p2.avg_word_count || audit.onpage_audit?.avg_word_count || audit.avg_word_count || 0;
    const altPct = p2.alt_coverage_pct || audit.alt_coverage_pct || 0;
    const ageY = p8.age_years || audit.domain_age_years || 0;
    const mozDa = p8.moz_da || audit.moz_da || 0;
    const schemas = audit.onpage_audit?.schemas || audit.schemas_found || [];

    container.innerHTML = `
      <div class="card" style="background: rgba(15, 23, 42, 0.75); border: 1px solid rgba(59, 130, 246, 0.4); padding: 18px 20px; border-radius: 10px; margin-bottom: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 12px; border-bottom: 1px solid rgba(59, 130, 246, 0.2); padding-bottom: 10px;">
          <div style="display: flex; align-items: center; gap: 8px;">
            <span style="font-size: 20px;">🎯</span>
            <div>
              <h4 style="margin: 0; font-size: 15px; color: #60a5fa;">Phân Tích 6 Trụ Cột: Website Đích (${escapeHtml(project.name || targetUrl)})</h4>
              <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">URL Đích: <a href="${escapeHtml(targetUrl)}" target="_blank" style="color: #93c5fd; text-decoration: none;">${escapeHtml(targetUrl)} ↗</a></div>
            </div>
          </div>
          <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            <span style="font-size: 11px; font-weight: 600; color: #34d399; background: rgba(16, 185, 129, 0.15); padding: 3px 8px; border-radius: 4px; border: 1px solid rgba(16, 185, 129, 0.3);">● Đã Đo Lường Theo Công Thức 6 Trụ Cột</span>
            <button type="button" class="btn-primary" onclick="openCompetitorDeepAuditModal('${escapeHtml(targetUrl)}', '${escapeHtml(targetUrl)}', state.targetAnalysis || (state.activeProject && state.activeProject.brand_profile && state.activeProject.brand_profile.target_analysis))" style="font-size: 11.5px; padding: 5px 12px; background: rgba(99, 102, 241, 0.25); border: 1px solid var(--primary); border-radius: 4px; color: #c7d2fe; cursor: pointer; display: flex; align-items: center; gap: 5px; font-weight: 600;"><span>🔍</span> Xem Chi Tiết 6 Trụ Cột Web Đích</button>
            <button type="button" class="btn-primary" onclick="analyzeTargetWebsite()" style="font-size: 11.5px; padding: 5px 12px; background: rgba(59, 130, 246, 0.25); border: 1px solid #3b82f6; border-radius: 4px; color: #93c5fd; cursor: pointer; display: flex; align-items: center; gap: 5px;"><span>🔄</span> Quét Lại Web Đích</button>
          </div>
        </div>
        <div style="font-size: 12.5px; color: var(--text-muted); line-height: 1.5; margin-bottom: 12px;">
          <div><strong>Thẻ Title:</strong> <span style="color: #cbd5e1;">${escapeHtml(audit.title || 'N/A')}</span> <span style="font-size: 11px; color: #93c5fd;">(${escapeHtml(audit.onpage_audit?.title_evaluation || '')})</span></div>
          <div style="margin-top: 4px;"><strong>Thẻ Meta Description:</strong> <span style="color: #cbd5e1;">${escapeHtml(audit.meta_description || 'Chưa gắn meta description')}</span></div>
          <div style="margin-top: 4px;"><strong>Thẻ Heading H1:</strong> <span style="color: #cbd5e1;">${(audit.h1_tags && audit.h1_tags.length > 0) ? escapeHtml(audit.h1_tags.join(' | ')) : 'Chưa gắn thẻ H1'}</span> <span style="font-size: 11px; color: ${audit.onpage_audit?.h1_count === 1 ? '#34d399' : '#f87171'};">(${escapeHtml(audit.onpage_audit?.h1_evaluation || '')})</span></div>
        </div>
        <!-- 6 THẺ CHỈ SỐ TRỤ CỘT CỦA WEB ĐÍCH -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 10px;">
          <div style="background: rgba(30, 41, 59, 0.5); border: 1px solid rgba(59, 130, 246, 0.25); border-radius: 6px; padding: 10px 12px;">
            <div style="font-size: 11px; color: #94a3b8; font-weight: 600; margin-bottom: 4px;">🖥️ CMS & NỀN TẢNG</div>
            <div style="font-size: 12.5px; font-weight: 700; color: #f8fafc;">${escapeHtml(cmsName)}</div>
            <div style="font-size: 11px; color: #93c5fd; margin-top: 2px;">Plugin: ${escapeHtml(p3.seo_plugin || 'Mặc định')}</div>
          </div>
          <div style="background: rgba(30, 41, 59, 0.5); border: 1px solid rgba(59, 130, 246, 0.25); border-radius: 6px; padding: 10px 12px;">
            <div style="font-size: 11px; color: #94a3b8; font-weight: 600; margin-bottom: 4px;">⚡ TỐC ĐỘ TTFB</div>
            <div style="font-size: 12.5px; font-weight: 700; color: ${ttfbVal < 0.8 ? '#34d399' : '#fbbf24'};">${ttfbVal ? ttfbVal + 's' : 'Đang đo'}</div>
            <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">Tải trang On-page</div>
          </div>
          <div style="background: rgba(30, 41, 59, 0.5); border: 1px solid rgba(59, 130, 246, 0.25); border-radius: 6px; padding: 10px 12px;">
            <div style="font-size: 11px; color: #94a3b8; font-weight: 600; margin-bottom: 4px;">✍️ ĐỘ SÂU NỘI DUNG</div>
            <div style="font-size: 12.5px; font-weight: 700; color: #fbbf24;">${wordCount ? '~' + wordCount.toLocaleString() + ' từ' : 'Đang bóc tách'}</div>
            <div style="font-size: 11px; color: #34d399; margin-top: 2px;">${altPct ? altPct + '% ảnh có Alt' : 'Ảnh có Alt'}</div>
          </div>
          <div style="background: rgba(30, 41, 59, 0.5); border: 1px solid rgba(59, 130, 246, 0.25); border-radius: 6px; padding: 10px 12px;">
            <div style="font-size: 11px; color: #94a3b8; font-weight: 600; margin-bottom: 4px;">🛡️ TUỔI & THẨM QUYỀN</div>
            <div style="font-size: 12.5px; font-weight: 700; color: #38bdf8;">${ageY ? '~' + ageY + ' năm' : 'Đang đo lường'}</div>
            <div style="font-size: 11px; color: #cbd5e1; margin-top: 2px;">${mozDa ? 'Moz DA: ' + mozDa : 'Domain Authority'}</div>
          </div>
          <div style="background: rgba(30, 41, 59, 0.5); border: 1px solid rgba(59, 130, 246, 0.25); border-radius: 6px; padding: 10px 12px;">
            <div style="font-size: 11px; color: #94a3b8; font-weight: 600; margin-bottom: 4px;">🌐 THỰC THỂ SOCIAL</div>
            <div style="font-size: 12.5px; font-weight: 700; color: #a78bfa;">${p7.active_count !== undefined ? p7.active_count + '/5 kênh' : (audit.social_channels ? Object.keys(audit.social_channels).length + ' kênh' : 'Đang bóc tách')}</div>
            <div style="font-size: 11px; color: #34d399; margin-top: 2px;">${p7.has_sameas_schema ? '✓ sameAs Schema' : 'Thực thể Social'}</div>
          </div>
          <div style="background: rgba(30, 41, 59, 0.5); border: 1px solid rgba(59, 130, 246, 0.25); border-radius: 6px; padding: 10px 12px;">
            <div style="font-size: 11px; color: #94a3b8; font-weight: 600; margin-bottom: 4px;">🛒 RICH SCHEMA</div>
            <div style="font-size: 12.5px; font-weight: 700; color: #6ee7b7;">${schemas.length > 0 ? schemas.length + ' loại' : 'Cơ bản'}</div>
            <div style="font-size: 11px; color: #38bdf8; margin-top: 2px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${schemas.length > 0 ? schemas.slice(0, 2).join(', ') : 'JSON-LD'}</div>
          </div>
        </div>
        <!-- DẤU CHÂN TỪ KHÓA ĐA TẦNG CỦA WEB ĐÍCH -->
        ${(() => {
          const tClusters = audit.keyword_clusters || {};
          const tHeads = tClusters.head || [];
          const tSubcats = tClusters.subcategories || [];
          const tSecs = tClusters.secondary || [];
          const tLongs = tClusters.longtail || [];
          const tMetaKws = tClusters.meta_keywords || [];
          const tTotalKws = audit.total_keywords_count || (tHeads.length + tSubcats.length + tSecs.length + tLongs.length + tMetaKws.length);
          if (tTotalKws === 0) return '';
          return `
            <div style="margin-top: 14px; background: rgba(15, 23, 42, 0.65); border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 8px; padding: 12px 14px;">
              <div style="font-size: 13px; font-weight: 700; color: #93c5fd; margin-bottom: 8px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 6px;">
                <span style="display: flex; align-items: center; gap: 6px;"><span>🎯</span> DẤU CHÂN TỪ KHÓA WEB ĐÍCH (${tTotalKws} từ khóa bóc tách được):</span>
                <span style="font-size: 11px; font-weight: 500; color: #34d399; background: rgba(16, 185, 129, 0.15); padding: 2px 7px; border-radius: 4px; border: 1px solid rgba(16, 185, 129, 0.3);">Tầng 1 Cốt lõi • Tầng 2 Ngách • Tầng 3 Model • Tầng 4 Ý định</span>
              </div>
              ${tHeads.length > 0 ? `<div style="margin-bottom: 8px;"><div style="font-size: 11.5px; font-weight: 600; color: #818cf8; margin-bottom: 4px;">📌 1. Ngành Hàng Cốt Lõi (Head - ${tHeads.length} ngành):</div><div style="display: flex; flex-wrap: wrap; gap: 5px;">${tHeads.map(kw => `<span style="font-size: 11px; background: rgba(99, 102, 241, 0.2); color: #c7d2fe; border: 1px solid rgba(99, 102, 241, 0.35); padding: 2px 8px; border-radius: 4px;">🔑 ${escapeHtml(kw)}</span>`).join('')}</div></div>` : ''}
              ${tSubcats.length > 0 ? `<div style="margin-bottom: 8px;"><div style="font-size: 11.5px; font-weight: 600; color: #34d399; margin-bottom: 4px;">📂 2. Landing Pages Phân Khúc / Kích Thước (Subcategories - ${tSubcats.length} phân khúc):</div><div style="display: flex; flex-wrap: wrap; gap: 5px;">${tSubcats.map(kw => `<span style="font-size: 11px; background: rgba(16, 185, 129, 0.15); color: #6ee7b7; border: 1px solid rgba(16, 185, 129, 0.3); padding: 2px 8px; border-radius: 4px;">📂 ${escapeHtml(kw)}</span>`).join('')}</div></div>` : ''}
              ${tSecs.length > 0 ? `<div style="margin-bottom: 8px;"><div style="font-size: 11.5px; font-weight: 600; color: #38bdf8; margin-bottom: 4px;">🏷️ 3. Dòng Model & Công Nghệ (Secondary Specs - ${tSecs.length} model):</div><div style="display: flex; flex-wrap: wrap; gap: 5px;">${tSecs.map(kw => `<span style="font-size: 11px; background: rgba(14, 165, 233, 0.15); color: #7dd3fc; border: 1px solid rgba(56, 189, 248, 0.3); padding: 2px 8px; border-radius: 4px;">🏷️ ${escapeHtml(kw)}</span>`).join('')}</div></div>` : ''}
              ${tLongs.length > 0 ? `<div><div style="font-size: 11.5px; font-weight: 600; color: #fbbf24; margin-bottom: 4px;">🛒 4. Ý Định Mua Hàng & Giá Kho (Long-tail Intent - ${tLongs.length} từ khóa):</div><div style="display: flex; flex-wrap: wrap; gap: 5px;">${tLongs.map(kw => `<span style="font-size: 11px; background: rgba(245, 158, 11, 0.15); color: #fde68a; border: 1px solid rgba(245, 158, 11, 0.3); padding: 2px 8px; border-radius: 4px;">💡 ${escapeHtml(kw)}</span>`).join('')}</div></div>` : ''}
            </div>
          `;
        })()}
      </div>
    `;
  } else {
    container.innerHTML = `
      <div class="card" style="background: rgba(15, 23, 42, 0.6); border: 1px dashed rgba(59, 130, 246, 0.35); padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <div>
          <div style="font-size: 13.5px; font-weight: 700; color: #93c5fd; display: flex; align-items: center; gap: 6px;">
            <span>🎯</span> Website đích chưa phân tích chuyên sâu 6 trụ cột (${escapeHtml(targetUrl)})
          </div>
          <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
            Bấm nút bên cạnh để AI tự động trinh sát 6 trụ cột web đích và lấp đầy ma trận đối đầu với đối thủ.
          </div>
        </div>
        <button type="button" class="btn-primary" onclick="analyzeTargetWebsite()" style="font-size: 12px; padding: 7px 14px; background: rgba(59, 130, 246, 0.2); border: 1px solid #3b82f6; border-radius: 6px; color: #60a5fa; cursor: pointer; font-weight: 600;">
          <span>🎯</span> Phân Tích Web Đích Ngay
        </button>
      </div>
    `;
  }
}

async function analyzeTargetWebsite() {
  if (!state.activeProjectId) {
    alert('Chưa chọn dự án.');
    return;
  }
  const btn = document.getElementById('btnAnalyzeTarget');
  const icon = document.getElementById('targetAuditIcon');
  const text = document.getElementById('targetAuditText');
  if (btn) btn.disabled = true;
  if (icon) icon.textContent = '⏳';
  if (text) text.textContent = 'Đang phân tích 6 trụ cột web đích...';

  try {
    const res = await fetch(`/api/v1/portal/projects/${state.activeProjectId}/analyze-target`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({})
    });
    const data = await res.json();
    if (res.ok && data.status === 'success') {
      alert(data.message || 'Đã phân tích chuyên sâu 6 trụ cột cho Web đích thành công!');
      await loadProjectIntelligence(state.activeProjectId);
    } else {
      alert(data.detail || data.message || 'Không thể phân tích Web đích.');
    }
  } catch (err) {
    console.error('Lỗi phân tích Web đích:', err);
    alert('Lỗi kết nối khi phân tích Web đích: ' + err.message);
  } finally {
    if (btn) btn.disabled = false;
    if (icon) icon.textContent = '🎯';
    if (text) text.textContent = 'Phân Tích Web Đích';
  }
}
window.analyzeTargetWebsite = analyzeTargetWebsite;

async function batchDeepAuditAllCompetitors() {
  if (!state.activeProjectId) {
    alert('Chưa chọn dự án.');
    return;
  }
  const btn = document.getElementById('btnBatchDeepAudit');
  const icon = document.getElementById('batchAuditIcon');
  const text = document.getElementById('batchAuditText');
  if (btn) btn.disabled = true;
  if (icon) icon.textContent = '⏳';
  if (text) text.textContent = 'Đang đột kích chuyên sâu cả 10 đối thủ (đa luồng)...';

  try {
    const res = await fetch(`/api/v1/portal/projects/${state.activeProjectId}/competitors/batch-deep-audit`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' }
    });
    const data = await res.json();
    if (res.ok && data.status === 'success') {
      alert(data.message || 'Đã quét chuyên sâu cả 10 đối thủ thành công!');
      await loadProjectIntelligence(state.activeProjectId);
    } else {
      alert(data.detail || data.message || 'Không thể quét chuyên sâu đối thủ.');
    }
  } catch (err) {
    console.error('Lỗi quét đối thủ theo lô:', err);
    alert('Lỗi kết nối khi quét chuyên sâu: ' + err.message);
  } finally {
    if (btn) btn.disabled = false;
    if (icon) icon.textContent = '🚀';
    if (text) text.textContent = 'Quét Chuyên Sâu Cả 10 Đối Thủ';
  }
}
window.batchDeepAuditAllCompetitors = batchDeepAuditAllCompetitors;

