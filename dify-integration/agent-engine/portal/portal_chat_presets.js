// ============================================================
// TAB 1: CHAT WELCOME CARDS & PRESET PROMPTS (SOP 23 compliant <= 300 lines)
// Module chuyên biệt quản lý thẻ mở đầu, gợi ý thông minh và tiến trình suy nghĩ
// ============================================================

function toggleThoughtBox(headerEl) {
  headerEl.classList.toggle('open');
  headerEl.nextElementSibling?.classList.toggle('collapsed');
}
window.toggleThoughtBox = toggleThoughtBox;

function renderThoughtBox(thought, thoughtSteps = null, isStreaming = false) {
  const tr = (k, p, def) => (typeof t === 'function' ? t(k, p) : def);
  let steps = Array.isArray(thoughtSteps) ? [...thoughtSteps] : [];
  if (steps.length === 0 && thought) {
    const parts = String(thought).split(/🛠️\s*\[(?:Công cụ|Tool):\s*([^\]]+)\]/i);
    if (parts.length > 1) {
      for (let i = 1; i < parts.length; i += 2) {
        steps.push({ tool: parts[i].trim(), text: (parts[i + 1] || '').trim() });
      }
    } else {
      steps.push({ tool: null, text: String(thought).trim() });
    }
  }

  steps = steps.filter(s => s.tool || (s.text && s.text.length > 0));
  if (steps.length === 0) return '';

  const toolNames = [...new Set(steps.filter(s => s.tool).map(s => s.tool))];
  const label = isStreaming ? tr('chat.thought_loading', {}, '⏳ Đang phân tích & xử lý...') : tr('chat.thought_done', {}, '✨ Tiến trình suy nghĩ');
  let badgeHtml = '';
  if (toolNames.length > 0) {
    badgeHtml = toolNames.map(tl => `<span class="thought-tool-chip">🛠️ ${escapeHtml(tl)}</span>`).join(' ');
  } else if (!isStreaming) {
    badgeHtml = `<span class="thought-tool-chip">${tr('chat.thought_completed_chip', {}, '✓ Hoàn tất')}</span>`;
  }

  const stepsHtml = steps.map(s => {
    const hdr = s.tool ? `<div class="thought-step-header">🛠️ ${tr('chat.thought_tool_label', { tool: escapeHtml(s.tool) }, `Công cụ: <code>${escapeHtml(s.tool)}</code>`)}</div>` : '';
    const desc = s.text ? `<div class="thought-step-desc">${escapeHtml(s.text)}</div>` : '';
    return `<div class="thought-step">${hdr}${desc}</div>`;
  }).join('');

  return `
    <div class="thought-box">
      <div class="thought-header ${isStreaming ? 'open' : ''}" onclick="toggleThoughtBox(this)">
        <div class="thought-title-group">
          <span class="thought-icon">${isStreaming ? '⏳' : '✨'}</span>
          <span class="thought-label">${label}</span>
          ${badgeHtml}
        </div>
        <span class="thought-arrow">▾</span>
      </div>
      <div class="thought-content ${isStreaming ? '' : 'collapsed'}">${stepsHtml}</div>
    </div>
  `;
}
window.renderThoughtBox = renderThoughtBox;

function renderWelcomeMessage() {
  const feed = document.getElementById('chatFeed');
  if (!feed) return;
  const p = state.activeProject || {};
  const projName = p.name || (typeof t === 'function' ? t('nav.current_project') : 'Dự án');
  const domain = (p.target_website_url || '').replace(/^https?:\/\//, '').replace(/\/.*$/, '') || 'website';
  const intel = state.activeIntelligence || {};
  const compCount = (intel.competitors || []).length || 10;
  const kwCount = (intel.keyword_matrix || []).length || (intel.extracted_keywords || []).length || '1.200+';
  const hasCms = Boolean(p.cms_api_url && p.cms_auth_config);

  const tr = (k, params = {}, def = '') => (typeof t === 'function' ? t(k, params) : def);
  const badgeTxt = tr('welcome.cmo_badge', {}, '🟢 Đã Tự Động Kích Hoạt 100%');
  const greetingTxt = tr('welcome.greeting', { domain: escapeHtml(domain) }, `Xin chào! Mọi dữ liệu trinh sát On-page, Radar 10 đối thủ SERP và Ma trận từ khóa của website <strong>${escapeHtml(domain)}</strong> đã sẵn sàng tại các Tab nghiệp vụ.`);
  const radarVal = tr('welcome.radar_val', { count: compCount }, `${compCount} Đối thủ Online`);
  const kwVal = tr('welcome.kw_val', { count: kwCount }, `${kwCount} Từ khóa`);
  const cmsVal = hasCms ? tr('welcome.cms_connected', {}, '🟢 Omni Bridge') : tr('welcome.cms_waiting', {}, '🟡 Chờ kết nối');

  feed.innerHTML = `
    <div class="message-row assistant">
      <div class="avatar ai-avatar"><svg fill="currentColor" viewBox="0 0 24 24"><path d="M12 2a2 2 0 0 1 2 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 0 1 7 7h1a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v1a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-1H2a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h1a7 7 0 0 1 7-7h1V5.73c-.6-.34-1-.99-1-1.73a2 2 0 0 1 2-2M7.5 13A2.5 2.5 0 0 0 5 15.5 2.5 2.5 0 0 0 7.5 18a2.5 2.5 0 0 0 2.5-2.5A2.5 2.5 0 0 0 7.5 13m9 0a2.5 2.5 0 0 0-2.5 2.5 2.5 2.5 0 0 0 2.5 2.5 2.5 2.5 0 0 0 2.5-2.5A2.5 2.5 0 0 0 7.5 13"/></svg></div>
      <div class="bubble mission-control-card">
        <div class="mission-header">
          <div class="mission-title">👑 CMO AI Copilot • ${escapeHtml(projName)}</div>
          <span class="mission-badge">${badgeTxt}</span>
        </div>
        <p style="margin-bottom:8px;">${greetingTxt}</p>
        <div class="mission-grid">
          <div class="mission-item"><span>${tr('welcome.target_web', {}, '🌐 Website Mục Tiêu')}</span><strong>${escapeHtml(domain)}</strong></div>
          <div class="mission-item"><span>${tr('welcome.radar', {}, '🎯 Radar SERP')}</span><strong>${radarVal}</strong></div>
          <div class="mission-item"><span>${tr('welcome.kw_matrix', {}, '🔑 Ma Trận Từ Khóa')}</span><strong>${kwVal}</strong></div>
          <div class="mission-item"><span>${tr('welcome.cms_bridge', {}, '🚀 Kết Nối CMS')}</span><strong>${cmsVal}</strong></div>
        </div>
        <p style="font-size:13px;color:var(--text-muted);margin-bottom:10px;">${tr('welcome.role_desc', {}, 'Tôi là Giám Đốc Tiếp Thị & Chuyên Gia Phân Tích Dữ Liệu AI. Bạn muốn triển khai chiến dịch nào ngay bây giờ?')}</p>
        <div class="mission-actions">
          <button type="button" class="mission-btn" onclick="triggerPresetAction('strategy')">${tr('welcome.btn_strategy', {}, '📊 Phân Tích & Chiến Lược Giai Đoạn 2')}</button>
          <button type="button" class="mission-btn" onclick="triggerPresetAction('seo')">${tr('welcome.btn_seo', {}, '✍️ Viết Bài SEO SOP 12')}</button>
          <button type="button" class="mission-btn" onclick="triggerPresetAction('recon')">${tr('welcome.btn_recon', {}, '⚔️ Soi Khoảng Trống Đối Thủ')}</button>
          <button type="button" class="mission-btn" onclick="triggerPresetAction('social')">${tr('welcome.btn_social', {}, '📱 Kịch Bản Video TikTok 60s')}</button>
        </div>
      </div>
    </div>
  `;
}
window.renderWelcomeMessage = renderWelcomeMessage;

function updateQuickPrompts(project) {
  const container = document.getElementById('quickPromptsContainer');
  if (!container || !project) return;
  const pName = project.name || 'Dự án';
  const intel = state.activeIntelligence || {};
  const topKw = (intel.extracted_keywords && intel.extracted_keywords[0]) || (intel.keyword_matrix && intel.keyword_matrix[0]?.keyword) || 'chủ lực';
  const topComp = (intel.competitors && intel.competitors[0]?.domain) || 'đối thủ Top 1';
  const isEn = typeof getCurrentLang === 'function' && getCurrentLang() === 'en';

  const prompts = isEn ? [
    { label: `📊 Phase 2 Strategic Roadmap`, prompt: `Analyze On-page data, 10 SERP competitors and keyword matrix to formulate a multi-phase Strategic Roadmap with quantitative KPIs.` },
    { label: `✍️ SOP 12 Post: "${topKw}"`, prompt: `Write a 2000+ words SEO-optimized article following SOP 12 for keyword "${topKw}". Optimize H2/H3 Silo headings and CRO.` },
    { label: `⚔️ Spy Competitor: ${topComp}`, prompt: `Analyze pricing strategy, heading structures and content gaps of competitor ${topComp}.` },
    { label: `📱 TikTok & Social Script`, prompt: `Create a 60-second TikTok video script (hook first 3s) and 3 high-converting Facebook posts for project ${pName}.` }
  ] : [
    { label: `📊 Lộ trình Chiến lược Giai đoạn 2`, prompt: `Hãy phân tích dữ liệu Onpage, 10 đối thủ và bộ từ khóa để lập Lộ trình Chiến lược Đa giai đoạn chi tiết cho thời gian tới kèm KPI định lượng.` },
    { label: `✍️ Viết bài SOP 12: "${topKw}"`, prompt: `Viết bài chuẩn SEO 2000+ từ theo SOP 12 cho từ khóa "${topKw}". Tối ưu tháp Heading Silo H2/H3 và chuyển đổi CRO.` },
    { label: `⚔️ Soi đối thủ: ${topComp}`, prompt: `Mổ xẻ chiến lược giá, Heading và khoảng trống nội dung của đối thủ ${topComp} đang cạnh tranh thứ hạng.` },
    { label: `📱 Kịch bản TikTok & Social`, prompt: `Tạo 1 kịch bản video TikTok 60 giây (hook 3s đầu) và 3 bài đăng Fanpage Facebook chuyển đổi cao cho dự án ${pName}.` }
  ];

  container.innerHTML = prompts.map(p => `<div class="prompt-pill" data-prompt="${escapeHtml(p.prompt)}">${p.label}</div>`).join('');
  container.querySelectorAll('.prompt-pill').forEach(pill => {
    pill.addEventListener('click', () => {
      const txt = pill.getAttribute('data-prompt');
      if (txt && !state.isStreaming) {
        document.getElementById('chatInput').value = txt;
        sendMessage();
      }
    });
  });
}
window.updateQuickPrompts = updateQuickPrompts;

function triggerPresetAction(type) {
  const p = state.activeProject || {};
  const intel = state.activeIntelligence || {};
  const topKw = (intel.extracted_keywords && intel.extracted_keywords[0]) || 'chủ lực';
  const topComp = (intel.competitors && intel.competitors[0]?.domain) || 'đối thủ Top 1';
  const isEn = typeof getCurrentLang === 'function' && getCurrentLang() === 'en';
  let cmd = '';

  if (type === 'strategy' || type === 'analyst') {
    cmd = isEn ?
      `Act as Senior CMO & Lead Data Strategist. Based on Onpage audit, 10 SERP competitors and keyword matrix for ${p.target_website_url}, analyze bottlenecks and formulate a multi-phase Strategic Roadmap with quantitative KPIs.` :
      `Hãy đóng vai trò Chuyên Gia Phân Tích Dữ Liệu & Hoạch Định Chiến Lược Cấp Cao. Dựa trên hiện trạng Onpage, 10 đối thủ SERP và ma trận từ khóa của website ${p.target_website_url}, hãy phân tích chuyên sâu các điểm nghẽn, cơ hội thắng nhanh (Quick Wins) và lập Lộ trình Chiến lược Đa giai đoạn chi tiết cho thời gian tới kèm dự báo KPI định lượng.`;
  } else if (type === 'seo') {
    cmd = isEn ? `Write a 2000+ words SEO article following SOP 12 for keyword "${topKw}" on website ${p.target_website_url}.` : `Viết bài chuẩn SEO 2000+ từ theo SOP 12 cho từ khóa "${topKw}" của website ${p.target_website_url}.`;
  } else if (type === 'recon') {
    cmd = isEn ? `Dissect content strategy and heading hierarchy of competitor ${topComp}.` : `Mổ xẻ chiến lược nội dung và cấu trúc Heading của đối thủ ${topComp}.`;
  } else if (type === 'social') {
    cmd = isEn ? `Create 1 60s TikTok short script and 3 high-converting Facebook posts for project ${p.name}.` : `Tạo 1 kịch bản video ngắn TikTok 60s và 3 bài đăng Fanpage Facebook cho dự án ${p.name}.`;
  } else if (type === 'onpage') {
    cmd = isEn ? `Analyze On-page gaps (Title, Meta Description, H1) for ${p.target_website_url} and provide action steps.` : `Phân tích các khoảng trống On-page (Title, Meta Description, H1) của ${p.target_website_url} và hướng dẫn khắc phục.`;
  }

  if (cmd && !state.isStreaming) {
    document.getElementById('chatInput').value = cmd;
    sendMessage();
  }
}
window.triggerPresetAction = triggerPresetAction;

function insertSlashCommand(cmd) {
  const input = document.getElementById('chatInput');
  if (!input) return;
  input.value = cmd;
  input.focus();
}
window.insertSlashCommand = insertSlashCommand;

window.addEventListener('portal:lang_changed', () => {
  const feed = document.getElementById('chatFeed');
  if (feed && (feed.children.length === 0 || feed.querySelector('.mission-control-card'))) {
    renderWelcomeMessage();
  }
  if (state.activeProject) updateQuickPrompts(state.activeProject);
});
