// ============================================================
// TAB 1: ADVANCED SLASH COMMANDS (AGENTIC, BRIEF & DRIFT)
// Strictly complies with SOP 23 (<= 300 lines)
// ============================================================

function handleAgenticBriefCommand(message) {
  const m = message.trim();
  if (m === '/agentic' || m.startsWith('/agentic')) {
    executeAgenticAudit();
    return true;
  }
  if (m.startsWith('/brief ') || m.startsWith('/brief:')) {
    executeContentBrief(m.replace(/^\/brief[:\s]+/, '').trim());
    return true;
  }
  if (m === '/drift' || m.startsWith('/drift')) {
    executeSeoDrift();
    return true;
  }
  return false;
}
window.handleAgenticBriefCommand = handleAgenticBriefCommand;

async function executeAgenticAudit() {
  const feed = document.getElementById('chatFeed');
  const row = createAssistantRow('🤖 Đang kiểm tra mức độ sẵn sàng cho AI Bot (Lighthouse Agentic Browsing, llms.txt & WAF)...');
  feed.appendChild(row);
  scrollToBottom();

  try {
    const res = await fetch(`/api/v1/portal/projects/${state.activeProjectId}/agentic-audit`, { method: 'POST' });
    const d = await res.json();
    if (!res.ok) throw new Error(d.detail || 'Không thể audit agentic.');

    const bubble = row.querySelector('.bubble');
    const blockedBadges = (d.robots_txt.blocked_ai_bots || []).map(b => `<span style="background:rgba(239,68,68,0.2);color:#fca5a5;padding:2px 6px;border-radius:4px;font-size:11px;margin:2px;">🚫 ${escapeHtml(b)}</span>`).join(' ') || '<span style="color:#34d399;">🟢 Không chặn bot nào</span>';
    const allowedBadges = (d.robots_txt.allowed_ai_bots || []).map(b => `<span style="background:rgba(16,185,129,0.2);color:#86efac;padding:2px 6px;border-radius:4px;font-size:11px;margin:2px;">✅ ${escapeHtml(b)}</span>`).join(' ');

    bubble.innerHTML = `
      <div style="border-left: 3px solid #8b5cf6; padding-left: 12px; margin-bottom: 10px;">
        <h4 style="color:#c084fc; margin:0 0 4px;">🤖 KẾT QUẢ ĐÁNH GIÁ AI AGENT READINESS 2026</h4>
        <div style="font-size:12px; color:var(--text-muted);">Website: <strong>${escapeHtml(d.domain)}</strong></div>
      </div>
      <div class="mission-grid" style="margin: 8px 0;">
        <div class="mission-item"><span>Điểm Sẵn Sàng AI</span><strong style="color:${d.agentic_score >= 70 ? '#34d399' : '#f59e0b'};font-size:16px;">${d.agentic_score}/100</strong></div>
        <div class="mission-item"><span>Tệp /llms.txt</span><strong>${d.llms_txt.exists ? '🟢 Đã có tệp' : '🟡 Chưa có (Cần tạo)'}</strong></div>
      </div>
      <div style="margin: 8px 0; font-size:13px;">
        <strong>Trạng thái AI Crawlers trong robots.txt:</strong>
        <div style="margin-top:4px;">Chặn: ${blockedBadges}</div>
        <div style="margin-top:4px;">Cho phép: ${allowedBadges}</div>
      </div>
      <div style="margin: 8px 0; font-size:13px;">
        <strong>Kiến nghị tối ưu:</strong>
        <ul style="margin:4px 0 8px 18px; color:#cbd5e1;">${(d.recommendations || []).map(r => `<li>💡 ${escapeHtml(r)}</li>`).join('')}</ul>
      </div>
      <div class="chat-action-toolbar">
        <button type="button" class="btn-action-tool" onclick="copyLlmsTxtContent(this)">📋 Sao Chép Nội Dung llms.txt Chuẩn</button>
      </div>
    `;
    bubble._llmsContent = d.llms_txt.recommended_content || '';
  } catch (e) {
    row.querySelector('.bubble').innerHTML = `<div style="color:var(--danger);">⚠️ Lỗi audit AI Agent: ${e.message}</div>`;
  }
}

function copyLlmsTxtContent(btn) {
  const content = btn.closest('.bubble')?._llmsContent || '';
  navigator.clipboard.writeText(content).then(() => {
    const orig = btn.innerHTML;
    btn.innerHTML = '✅ ' + (typeof t === 'function' ? t('chat.copied', 'Đã sao chép!') : 'Đã sao chép!');
    setTimeout(() => { btn.innerHTML = orig; }, 2000);
  });
}
window.copyLlmsTxtContent = copyLlmsTxtContent;

async function executeContentBrief(keyword) {
  let kw = (keyword || '').trim();
  if (!kw) {
    const intel = state.activeIntelligence || {};
    kw = (intel.extracted_keywords && intel.extracted_keywords[0]) || (intel.keyword_matrix && intel.keyword_matrix[0]?.keyword) || 'sản phẩm chủ lực';
    showToast(`Đã tự động chọn từ khóa "${kw}" cho bài Brief!`, 'info');
  }
  const feed = document.getElementById('chatFeed');
  const row = createAssistantRow(`📋 Đang lập Dàn Ý Chiến Lược (Content Brief) cho từ khóa "${escapeHtml(kw)}"...`);
  feed.appendChild(row);
  scrollToBottom();

  try {
    const res = await fetch(`/api/v1/portal/projects/${state.activeProjectId}/content-brief`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ keyword: keyword })
    });
    const d = await res.json();
    if (!res.ok) throw new Error(d.detail || 'Không thể lập Brief.');

    const bubble = row.querySelector('.bubble');
    const headingsHtml = (d.heading_architecture || []).map(h => `<div style="margin:2px 0;font-size:12.5px;"><code style="color:#60a5fa;">${h.tag}</code> ${escapeHtml(h.title)}</div>`).join('');
    const paaHtml = (d.people_also_ask || []).map(p => `<li><strong>${escapeHtml(p.question)}</strong><br><span style="color:#94a3b8;font-size:12px;">👉 ${escapeHtml(p.direct_answer_hint)}</span></li>`).join('');
    const internalLinksHtml = (d.internal_link_strategy || []).map(l => `<li>Anchor: <code>${escapeHtml(l.anchor_text)}</code> ➔ <a href="${escapeHtml(l.target_url)}" target="_blank" style="color:#38bdf8;">${escapeHtml(l.target_url)}</a></li>`).join('');

    bubble.innerHTML = `
      <div style="border-left: 3px solid #10b981; padding-left: 12px; margin-bottom: 10px;">
        <h4 style="color:#34d399; margin:0 0 4px;">📋 BẢN DÀN Ý CHIẾN LƯỢC (CONTENT BRIEF): "${escapeHtml(d.keyword)}"</h4>
        <div style="font-size:12px; color:var(--text-muted);">${escapeHtml(d.target_audience.search_intent)} • ${escapeHtml(d.target_audience.funnel_stage)}</div>
      </div>
      <div style="margin: 8px 0; font-size:13px;">
        <strong>Tháp Heading Silo Khung Xương Bắt Buộc:</strong>
        <div style="background:rgba(15,23,42,0.6);padding:8px 12px;border-radius:6px;margin-top:4px;">${headingsHtml}</div>
      </div>
      <div style="margin: 8px 0; font-size:13px;">
        <strong>Bộ Câu Hỏi Thực Tế (People Also Ask):</strong>
        <ul style="margin:4px 0 8px 18px;">${paaHtml}</ul>
      </div>
      <div style="margin: 8px 0; font-size:13px;">
        <strong>Bản Đồ Internal Link Gợi Ý:</strong>
        <ul style="margin:4px 0 8px 18px;">${internalLinksHtml}</ul>
      </div>
      <div class="chat-action-toolbar">
        <button type="button" class="btn-action-tool" onclick="insertSlashCommand('/seo Viết bài hoàn chỉnh theo dàn ý Content Brief trên cho từ khóa &quot;${escapeHtml(d.keyword)}&quot;')">✍️ Viết Bài SOP 12 Theo Brief Này</button>
      </div>
    `;
  } catch (e) {
    row.querySelector('.bubble').innerHTML = `<div style="color:var(--danger);">⚠️ Lỗi: ${e.message}</div>`;
  }
}

async function executeSeoDrift() {
  const feed = document.getElementById('chatFeed');
  const row = createAssistantRow('📈 Đang đối chiếu lịch sử On-page & kiểm tra biến động thứ hạng (SEO Drift Monitor)...');
  feed.appendChild(row);
  scrollToBottom();

  try {
    const res = await fetch(`/api/v1/portal/projects/${state.activeProjectId}/drift`);
    const d = await res.json();
    if (!res.ok) throw new Error(d.detail || 'Không thể kiểm tra Drift.');

    const bubble = row.querySelector('.bubble');
    const statusColor = d.drift_status === 'STABLE' ? '#34d399' : '#ef4444';
    const statusText = d.drift_status === 'STABLE' ? '🟢 Ổn định (Không phát hiện lỗi tụt hạng)' : '⚠️ Cảnh báo: Có biến động On-page';

    bubble.innerHTML = `
      <div style="border-left: 3px solid ${statusColor}; padding-left: 12px; margin-bottom: 10px;">
        <h4 style="color:${statusColor}; margin:0 0 4px;">📈 BÁO CÁO BIẾN ĐỘNG SEO (DRIFT MONITOR)</h4>
        <div style="font-size:12px; color:var(--text-muted);">Mốc lịch sử gần nhất: ${new Date(d.baseline_captured_at).toLocaleString('vi-VN')}</div>
      </div>
      <div class="mission-grid" style="margin: 8px 0;">
        <div class="mission-item"><span>Trạng Thái</span><strong style="color:${statusColor};">${statusText}</strong></div>
        <div class="mission-item"><span>Điểm On-page Hiện Tại</span><strong>${d.current_score}/100 (${d.score_diff >= 0 ? '+' : ''}${d.score_diff})</strong></div>
      </div>
      <p style="font-size:13px;margin:8px 0;">${escapeHtml(d.summary)}</p>
    `;
  } catch (e) {
    row.querySelector('.bubble').innerHTML = `<div style="color:var(--danger);">⚠️ Lỗi: ${e.message}</div>`;
  }
}

function interjectChairman(text) {
  const input = document.getElementById('chatInput');
  if (!input) return;
  input.value = text;
  input.focus();
  const sendBtn = document.getElementById('btnSend') || document.getElementById('chatSendBtn');
  if (sendBtn && !sendBtn.disabled) sendBtn.click();
}
window.interjectChairman = interjectChairman;

function renderBoardroomDebateMarkup(text) {
  if (!text || !text.includes(':::boardroom-debate')) return parseMarkdown(text);
  let debateContent = '', restText = '', isComplete = false;
  const closedMatch = text.match(/:::boardroom-debate([\s\S]*?):::([\s\S]*)/i);
  if (closedMatch) {
    debateContent = closedMatch[1].trim();
    restText = closedMatch[2].trim();
    isComplete = true;
  } else {
    const cmoMatch = text.match(/:::boardroom-debate([\s\S]*?)(👑\s*(?:\*\*)?CMO AI[\s\S]*)/i);
    if (cmoMatch) {
      debateContent = cmoMatch[1].trim();
      restText = cmoMatch[2].trim();
      isComplete = true;
    } else {
      const streamMatch = text.match(/:::boardroom-debate([\s\S]*)$/i);
      if (!streamMatch) return parseMarkdown(text);
      debateContent = streamMatch[1].trim();
      isComplete = debateContent.includes('Consensus Voting Index') || debateContent.includes('CVI');
    }
  }
  const normalized = debateContent.replace(/\s*(🔍|🛒|💎|💰|📊|👑)/g, '\n$1').trim();
  const tr = (k, def) => (typeof t === 'function' ? t(k) : def);
  const isEn = typeof getCurrentLang === 'function' && getCurrentLang() === 'en';
  const lines = normalized.split('\n').filter(l => l.trim().length > 0);
  let speakersHtml = '', cviHtml = '';

  lines.forEach(line => {
    if (line.includes('Consensus Voting Index') || line.includes('CVI')) {
      const scoreMatch = line.match(/(\d+(?:\.\d+)?)\s*%/);
      const score = scoreMatch ? parseFloat(scoreMatch[1]) : 88.0;
      const statusClass = score >= 85 ? 'high' : (score >= 70 ? 'mid' : 'low');
      const statusText = score >= 85 ? tr('chat.cvi_high', '🟢 Đồng thuận cao') : (score >= 70 ? tr('chat.cvi_mid', '🟡 Có điều kiện') : tr('chat.cvi_low', '🔴 Cần điều chỉnh'));
      const parts = line.match(/(SEO|CRO|Brand|Finance|Ngân sách|Tài chính)[^\:\d]*\:\s*([\d\.]+(?:\/10)?)/gi);
      const chips = (parts || []).map(p => `<span class="cvi-chip">${escapeHtml(p.trim())}</span>`);
      
      cviHtml = `
        <div class="cvi-container">
          <div class="cvi-header">
            <span class="cvi-label">${tr('chat.cvi_label', '📊 Consensus Voting Index (CVI)')}</span>
            <span class="cvi-score-badge ${statusClass}">${score}% • ${statusText}</span>
          </div>
          <div class="cvi-bar-bg"><div class="cvi-bar-fill" style="width:${score}%;background:${score >= 85 ? '#10b981' : (score >= 70 ? '#f59e0b' : '#ef4444')};"></div></div>
          ${chips.length ? `<div class="cvi-breakdown">${chips.join('')}</div>` : ''}
        </div>
      `;
      return;
    }

    let speakerClass = 'speaker-seo', icon = '🔍', title = 'SEO & SERP Lead', cleanText = line;
    if (line.includes('CFO') || line.includes('Budget') || line.includes('Finance') || line.includes('Tài chính')) {
      speakerClass = 'speaker-cfo'; icon = '💰'; title = 'CFO & Budget Specialist';
    } else if (line.includes('CRO') || line.includes('Pricing')) {
      speakerClass = 'speaker-cro'; icon = '🛒'; title = 'CRO & Pricing Specialist';
    } else if (line.includes('Brand') || line.includes('UVP')) {
      speakerClass = 'speaker-brand'; icon = '💎'; title = 'Brand & UVP Strategist';
    }
    cleanText = cleanText.replace(/^[🔍🛒💎💰👑\*\s]+(SEO|CRO|Brand|UVP|CFO|Budget|Finance)[^\:]*\:\s*/i, '').replace(/^\*\*[^\*]+\*\*\:\s*/, '');

    speakersHtml += `
      <div class="debate-speaker ${speakerClass}">
        <div class="speaker-avatar">${icon}</div>
        <div class="speaker-content">
          <div class="speaker-name">${title}</div>
          <div class="speaker-text">${parseMarkdown(cleanText)}</div>
        </div>
      </div>
    `;
  });

  const chairmanActionsHtml = isComplete ? `
    <div class="chairman-interject-bar">
      <span class="chairman-label">${tr('chat.chairman_label', '👑 Chủ Tịch HĐQT:')}</span>
      <button type="button" class="btn-chairman" onclick="interjectChairman('${isEn ? 'I approve the executive consensus plan above. Proceed with execution.' : 'Tôi phê duyệt bản kế hoạch đồng thuận trên, hãy tiến hành triển khai chi tiết.'}')">${tr('chat.btn_approve', '✅ Phê Duyệt')}</button>
      <button type="button" class="btn-chairman cfo" onclick="interjectChairman('${isEn ? 'I prioritize CFO: cut non-essential costs and protect gross margin!' : 'Tôi ưu tiên đề xuất của CFO: hãy cắt giảm chi phí và bảo toàn biên lợi nhuận an toàn!'}')">${tr('chat.btn_follow_cfo', '💰 Theo CFO')}</button>
      <button type="button" class="btn-chairman cro" onclick="interjectChairman('${isEn ? 'I prioritize CRO: maximize conversion rate and checkout urgency!' : 'Tôi ưu tiên đề xuất CRO: hãy tập trung tỷ lệ chuyển đổi và chốt đơn ngay!'}')">${tr('chat.btn_follow_cro', '🛒 Theo CRO')}</button>
      <button type="button" class="btn-chairman" onclick="interjectChairman('${isEn ? 'I prioritize SEO: dominate competitor Top 1 with superior heading silo!' : 'Tôi ưu tiên đề xuất SEO: hãy phủ cấu trúc Heading đè bẹp Top 1 đối thủ!'}')">${tr('chat.btn_follow_seo', '🔍 Theo SEO')}</button>
      <button type="button" class="btn-chairman" onclick="interjectChairman('${isEn ? 'C-Suite Boardroom is requested to re-debate feasibility and unit economics.' : 'Yêu cầu Ban Giám Đốc tranh luận lại về tính khả thi và chi phí.'}')">${tr('chat.btn_redebate', '💬 Tranh Luận Lại')}</button>
    </div>
  ` : '';

  const cardHtml = `
    <div class="boardroom-card">
      <div class="boardroom-header" onclick="this.nextElementSibling.classList.toggle('collapsed')">
        <span class="boardroom-title">${tr('chat.boardroom_title', '🏛️ Ban Giám Đốc C-Suite AI (4 Chuyên Gia Tranh Biện Đối Kháng)')}</span>
        <span class="boardroom-status">${isComplete ? tr('chat.boardroom_status_voted', '🟢 Đã biểu quyết CVI') : tr('chat.boardroom_status_debating', '⏳ Đang tranh luận...')}</span>
        <button type="button" class="boardroom-toggle-btn">${tr('chat.boardroom_toggle', 'Thu gọn ▾')}</button>
      </div>
      <div class="boardroom-body">
        ${speakersHtml || `<div style="color:var(--text-muted);font-size:12px;">${isEn ? 'Convening Boardroom session...' : 'Đang triệu tập cuộc họp...'}</div>`}
        ${cviHtml}
        ${chairmanActionsHtml}
      </div>
    </div>
  `;

  let finalRestHtml = parseMarkdown(restText);
  finalRestHtml = finalRestHtml.replace(/👑\s*(?:\*\*|<strong>)CMO AI[^<*]*(?:BẢN KẾ HOẠCH ĐỒNG THUẬN TỐI ƯU|CONSENSUS ACTION PLAN)(?:\*\*|<\/strong>)[\:]?/gi, 
    `<div class="consensus-heading">👑 CMO AI • ${isEn ? 'CONSENSUS ACTION PLAN' : 'BẢN KẾ HOẠCH ĐỒNG THUẬN TỐI ƯU'}</div>`);

  return cardHtml + finalRestHtml;
}
window.renderBoardroomDebateMarkup = renderBoardroomDebateMarkup;

async function autoSaveBoardroomMinutes(projectId, text, topic) {
  if (!projectId || !text || !text.includes(':::boardroom-debate') || !text.includes(':::')) return;
  try {
    const match = text.match(/:::boardroom-debate([\s\S]*?):::/i);
    if (!match) return;
    const debate = match[1].trim();
    const consensusPlan = text.replace(match[0], '').trim();
    const scoreMatch = debate.match(/(\d+(?:\.\d+)?)\s*%/);
    const cviScore = scoreMatch ? parseFloat(scoreMatch[1]) : 85.0;

    await fetch(`/api/v1/portal/projects/${projectId}/boardroom-minutes`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        topic: topic ? topic.slice(0, 150) : 'Chiến lược tăng trưởng & Tối ưu hoá',
        debate_summary: { raw: debate.slice(0, 800) },
        consensus_plan: consensusPlan.slice(0, 2000),
        cvi_score: cviScore
      })
    });
  } catch (e) {
    console.warn('Auto-save boardroom minutes error:', e);
  }
}
window.autoSaveBoardroomMinutes = autoSaveBoardroomMinutes;
