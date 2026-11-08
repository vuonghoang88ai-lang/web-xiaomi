// ============================================================
// TAB 1: COPILOT SLASH COMMANDS (LIVE SPY, GSC STRIKE & GEO)
// Strictly complies with SOP 23 (<= 300 lines)
// ============================================================

function handleCustomChatCommand(message) {
  const m = message.trim();
  if (m === '/seo' || m === '/seo ') {
    const intel = state.activeIntelligence || {};
    const topKw = (intel.extracted_keywords && intel.extracted_keywords[0]) || (intel.keyword_matrix && intel.keyword_matrix[0]?.keyword) || 'sản phẩm chủ lực';
    insertSlashCommand(`/seo ${topKw}`);
    showToast('Đã tự động điền từ khóa mục tiêu. Bấm Enter để bắt đầu tạo bài viết!', 'info');
    return true;
  }
  if (m === '/social' || m === '/social ') {
    const pName = state.activeProject?.name || 'Dự án';
    insertSlashCommand(`/social Kịch bản TikTok 60s và bài đăng Facebook cho ${pName}`);
    showToast('Đã tự động điền kịch bản Social. Bấm Enter để AI tạo nội dung!', 'info');
    return true;
  }
  if (m.startsWith('/spy ') || m.startsWith('/spy:')) {
    executeSpyUrl(m.replace(/^\/spy[:\s]+/, '').trim());
    return true;
  }
  if (m === '/gsc-strike' || m.startsWith('/gsc-strike')) {
    executeGscStrike();
    return true;
  }
  if (m.startsWith('/geo ') || m.startsWith('/geo:')) {
    executeGeoOptimize(m.replace(/^\/geo[:\s]+/, '').trim());
    return true;
  }
  // Lệnh lập lịch và phê duyệt bài viết / kế hoạch (SOP 65 & SOP 30)
  const isScheduleSlash = m === '/schedule' || m.startsWith('/schedule ') || m === '/plan' || m.startsWith('/plan ') || m === '/duyet' || m.startsWith('/duyet ') || m === '/laplich';
  const isScheduleIntent = ['lập lịch', 'lên lịch', 'hẹn giờ', 'duyệt bài', 'phê duyệt', 'đăng vào lúc', 'đăng sáng mai', 'đăng chiều nay'].some(k => lowerMsg.includes(k));
  if (isScheduleSlash || isScheduleIntent) {
    if (typeof handleChatScheduleCommand === 'function') {
      handleChatScheduleCommand(m);
      return true;
    }
  }

  // Lệnh xuất bản trực tiếp hoặc phân tích ý định đăng bài (SOP 30 Semantic Routing)
  const lowerMsg = m.toLowerCase();
  const isPublishSlash = m === '/publish' || m.startsWith('/publish ') || m === '/post' || m === '/dang-bai';
  const isPublishIntent = ['đăng lên web', 'đăng bài này', 'xuất bản lên web', 'xuất bản bài', 'đăng lên wordpress', 'publish to wordpress'].some(k => lowerMsg.includes(k));
  if (isPublishSlash || isPublishIntent) {
    if (typeof triggerChatPublish === 'function') {
      triggerChatPublish();
    }
    return true;
  }
  if (typeof handleAgenticBriefCommand === 'function' && handleAgenticBriefCommand(message)) {
    return true;
  }
  return false;
}
window.handleCustomChatCommand = handleCustomChatCommand;

async function executeSpyUrl(url) {
  if (!url) {
    showToast('Vui lòng nhập đường dẫn website đối thủ sau lệnh /spy', 'error');
    return;
  }
  const feed = document.getElementById('chatFeed');
  const row = createAssistantRow('⚔️ Đang đột kích và bóc tách dữ liệu thời gian thực từ URL đối thủ...');
  feed.appendChild(row);
  scrollToBottom();

  try {
    const res = await fetch(`/api/v1/portal/projects/${state.activeProjectId}/spy-url`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ url: url })
    });
    const d = await res.json();
    if (!res.ok) throw new Error(d.detail || 'Không thể cào dữ liệu.');

    const bubble = row.querySelector('.bubble');
    const h2Tags = (d.h2_sample || []).map(h => `<code style="display:inline-block;margin:2px;">${escapeHtml(h)}</code>`).join(' ');
    const gapsList = (d.detected_gaps || []).map(g => `<li>⚠️ ${escapeHtml(g)}</li>`).join('');
    const tacticsList = (d.counter_tactics || []).map(t => `<li>🎯 ${escapeHtml(t)}</li>`).join('');
    const safeTitle = (d.title || d.domain || 'đối thủ').replace(/['"\\`]/g, ' ').slice(0, 80);

    bubble.innerHTML = `
      <div style="border-left: 3px solid #f59e0b; padding-left: 12px; margin-bottom: 10px;">
        <h4 style="color:#fde047; margin:0 0 4px;">⚔️ KẾT QUẢ ĐỘT KÍCH ĐỐI THỦ: ${escapeHtml(d.domain || '')}</h4>
        <div style="font-size:12px; color:var(--text-muted);"><a href="${escapeHtml(d.url)}" target="_blank" style="color:#38bdf8;">${escapeHtml(d.url)}</a></div>
      </div>
      <div class="mission-grid" style="margin: 8px 0;">
        <div class="mission-item"><span>Tiêu đề</span><strong>${escapeHtml(d.title || 'N/A')}</strong></div>
        <div class="mission-item"><span>Giá thực tế</span><strong style="color:#34d399;">${escapeHtml(d.price || 'Liên hệ')}</strong></div>
      </div>
      <div style="margin: 8px 0; font-size:13px;">
        <strong>Cấu trúc Heading đối thủ:</strong>
        <div style="margin-top:4px;">${h2Tags || '<em>Chưa phát hiện thẻ H2</em>'}</div>
      </div>
      <div style="margin: 8px 0; font-size:13px;">
        <strong>Lỗ hổng On-page đối thủ:</strong>
        <ul style="margin:4px 0 8px 18px; color:#fca5a5;">${gapsList}</ul>
      </div>
      <div style="margin: 8px 0; font-size:13px;">
        <strong>3 Chiêu thức phản công ngay:</strong>
        <ul style="margin:4px 0 8px 18px; color:#86efac;">${tacticsList}</ul>
      </div>
      <div class="chat-action-toolbar">
        <button type="button" class="btn-action-tool btn-spy-seo" data-cmd="/seo Bài viết đè bẹp ${escapeHtml(safeTitle)}">✍️ Viết Bài Đè Bẹp URL Này</button>
        <button type="button" class="btn-action-tool" onclick="copyChatText(this)">📋 Sao Chép Phân Tích</button>
      </div>
    `;

    const bar = bubble.querySelector('.chat-action-toolbar');
    if (bar) {
      bar._rawText = `Đột kích đối thủ: ${d.domain}\n- Tiêu đề: ${d.title}\n- Giá: ${d.price}\n- Lỗ hổng:\n${(d.detected_gaps || []).map(g => '  • ' + g).join('\n')}\n- Phản công:\n${(d.counter_tactics || []).map(t => '  • ' + t).join('\n')}`;
      const btnSeo = bar.querySelector('.btn-spy-seo');
      if (btnSeo) {
        btnSeo.addEventListener('click', () => insertSlashCommand(btnSeo.getAttribute('data-cmd')));
      }
    }
    scrollToBottom();
  } catch (err) {
    row.querySelector('.bubble').innerHTML = `<div style="color:var(--danger);">⚠️ Lỗi cào URL: ${err.message}</div>`;
  }
}

async function executeGscStrike() {
  const feed = document.getElementById('chatFeed');
  const row = createAssistantRow('🎯 Đang quét Google Search Console & ma trận từ khóa để lọc các vị trí đòn bẩy Top 4–10...');
  feed.appendChild(row);
  scrollToBottom();

  try {
    const res = await fetch(`/api/v1/portal/projects/${state.activeProjectId}/gsc-strike`);
    const data = await res.json();
    if (!res.ok) throw new Error(data.detail || 'Không thể tải dữ liệu GSC.');

    const opps = data.opportunities || [];
    const isLive = data.mode === 'gsc_live';

    let tableRows = opps.map((o, idx) => `
      <tr>
        <td><strong>#${idx + 1}</strong></td>
        <td><code>${escapeHtml(o.keyword)}</code></td>
        <td style="color:#fde047; font-weight:700;">Top ${o.position}</td>
        <td>${o.impressions.toLocaleString()}</td>
        <td style="color:#34d399;">${o.ctr}</td>
        <td><button type="button" class="slash-chip" onclick="insertSlashCommand('/seo ${escapeHtml(o.keyword)}')">Tối ưu ngay</button></td>
      </tr>
    `).join('');

    row.querySelector('.bubble').innerHTML = `
      <div style="border-left: 3px solid #10b981; padding-left: 12px; margin-bottom: 10px;">
        <h4 style="color:#34d399; margin:0 0 4px;">🎯 RADAR TỪ KHÓA ĐÒN BẨY (STRIKE DISTANCE OPPORTUNITIES)</h4>
        <div style="font-size:12px; color:var(--text-muted);">
          Nguồn dữ liệu: <strong>${isLive ? '🟢 Google Search Console Live OAuth' : '🟡 Mô Phỏng Ma Trận SERP'}</strong> • ${opps.length} từ khóa sắp vào Top 3
        </div>
      </div>
      <p style="font-size:13px; color:var(--text-muted); margin-bottom:10px;">
        Các từ khóa đang đứng ở vị trí #4–#10 có lượt hiển thị cao nhưng CTR còn thấp. Chỉ cần tối ưu lại Title Hook và bổ sung 1 đoạn H2 là có thể leo ngay vào Top 3 Google!
      </p>
      <table class="bubble-table">
        <thead>
          <tr><th>STT</th><th>Từ Khóa</th><th>Thứ Hạng</th><th>Lượt Xem</th><th>CTR</th><th>Hành Động</th></tr>
        </thead>
        <tbody>${tableRows}</tbody>
      </table>
      <div style="margin-top:10px; font-size:12.5px; background:rgba(30,41,59,0.5); padding:8px 12px; border-radius:6px;">
        💡 <strong>Gợi ý Title Hook đè bẹp đối thủ:</strong> <em>"${escapeHtml(opps[0]?.title_suggestion || '')}"</em>
      </div>
    `;
    scrollToBottom();
  } catch (err) {
    row.querySelector('.bubble').innerHTML = `<div style="color:var(--danger);">⚠️ Lỗi tải cơ hội GSC: ${err.message}</div>`;
  }
}

async function executeGeoOptimize(kw) {
  const queryKw = kw || 'sản phẩm chủ lực';
  const feed = document.getElementById('chatFeed');
  const row = createAssistantRow(`🤖 Đang phân tích và tối ưu chuẩn GEO (Generative Engine Optimization) cho từ khóa "${queryKw}"...`);
  feed.appendChild(row);
  scrollToBottom();

  try {
    const res = await fetch(`/api/v1/portal/projects/${state.activeProjectId}/geo-optimize`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ keyword: queryKw })
    });
    const d = await res.json();
    if (!res.ok) throw new Error(d.detail || 'Lỗi tối ưu GEO.');

    const faqs = (d.geo_components?.suggested_faqs || []).map(f => `
      <div style="margin-bottom:6px;">
        <strong style="color:#c7d2fe;">Q: ${escapeHtml(f.q)}</strong>
        <div style="color:var(--text-muted); margin-left:10px;">A: ${escapeHtml(f.a)}</div>
      </div>
    `).join('');

    const directAns = d.geo_components?.direct_answer_block || '';
    row.querySelector('.bubble').innerHTML = `
      <div style="border-left: 3px solid #8b5cf6; padding-left: 12px; margin-bottom: 10px;">
        <h4 style="color:#a78bfa; margin:0 0 4px;">🤖 BỘ TỐI ƯU HÓA TÌM KIẾM AI — GEO ENGINE OPTIMIZER</h4>
        <div style="font-size:12px; color:var(--text-muted);">Tối ưu để được ChatGPT Search, Perplexity AI & Google Gemini trích dẫn nguồn uy tín</div>
      </div>
      <div class="mission-grid" style="margin: 8px 0;">
        <div class="mission-item"><span>Từ khóa</span><strong>${escapeHtml(d.keyword)}</strong></div>
        <div class="mission-item"><span>Điểm GEO sẵn sàng</span><strong style="color:#34d399;">${d.geo_score}/100đ</strong></div>
      </div>
      <div style="margin: 10px 0; background:rgba(15,23,42,0.6); padding:10px 14px; border-radius:8px; border:1px solid rgba(99,102,241,0.25);">
        <strong style="color:#38bdf8;">📌 Khối Trả Lời Trực Diện (Direct Answer Block):</strong>
        <div style="margin-top:6px; font-size:13px; line-height:1.6; color:#f1f5f9;">${parseMarkdown(directAns)}</div>
      </div>
      <div style="margin: 10px 0; font-size:13px;">
        <strong style="color:#34d399;">💬 Câu hỏi đàm thoại chuẩn AI (Conversational FAQ Accordion):</strong>
        <div style="margin-top:8px;">${faqs}</div>
      </div>
      <div class="chat-action-toolbar">
        <button type="button" class="btn-action-tool" onclick="insertSlashCommand('/seo Bài viết chuẩn GEO ${escapeHtml(d.keyword)}')">✍️ Chấp Bút Bài Chuẩn GEO</button>
        <button type="button" class="btn-action-tool" onclick="copyChatText(this)">📋 Sao Chép Direct Answer</button>
      </div>
    `;

    const bar = row.querySelector('.chat-action-toolbar');
    if (bar) bar._rawText = directAns;
    scrollToBottom();
  } catch (err) {
    row.querySelector('.bubble').innerHTML = `<div style="color:var(--danger);">⚠️ Lỗi tối ưu GEO: ${err.message}</div>`;
  }
}

function createAssistantRow(loadingText) {
  const row = document.createElement('div');
  row.className = 'message-row assistant';
  row.innerHTML = `
    <div class="avatar ai-avatar"><svg fill="currentColor" viewBox="0 0 24 24"><path d="M12 2a2 2 0 0 1 2 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 0 1 7 7h1a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v1a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-1H2a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h1a7 7 0 0 1 7-7h1V5.73c-.6-.34-1-.99-1-1.73a2 2 0 0 1 2-2M7.5 13A2.5 2.5 0 0 0 5 15.5 2.5 2.5 0 0 0 7.5 18a2.5 2.5 0 0 0 2.5-2.5A2.5 2.5 0 0 0 7.5 13m9 0a2.5 2.5 0 0 0-2.5 2.5 2.5 2.5 0 0 0 2.5 2.5 2.5 2.5 0 0 0 2.5-2.5A2.5 2.5 0 0 0 7.5 13"/></svg></div>
    <div class="bubble"><div class="typing-dots"><span></span><span></span><span></span></div> <span style="font-size:13px; color:var(--text-muted);">${loadingText}</span></div>
  `;
  return row;
}
