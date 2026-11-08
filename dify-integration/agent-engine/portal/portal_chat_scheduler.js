// ============================================================
// TAB 1: CONVERSATIONAL CHAT SCHEDULER & APPROVAL CONTROLLER
// SOP 23 Compliant (<= 300 lines) & SOP 30 Zero Hardcoding
// ============================================================

function extractLatestAssistantContent() {
  const feed = document.getElementById('chatFeed');
  if (!feed) return null;
  const rows = Array.from(feed.querySelectorAll('.message-row.assistant'));
  for (let i = rows.length - 1; i >= 0; i--) {
    const row = rows[i];
    if (row.classList.contains('bg-task-row') || row.classList.contains('publish-result-row') || row.classList.contains('schedule-card-row')) continue;
    const bar = row.querySelector('.chat-action-toolbar');
    let text = bar?._rawText;
    if (!text) {
      const bubble = row.querySelector('.bubble');
      if (bubble) {
        const clone = bubble.cloneNode(true);
        clone.querySelectorAll('.chat-action-toolbar, .thought-container, .boardroom-card, .next-action-chips-bar').forEach(el => el.remove());
        text = clone.innerText.trim();
      }
    }
    if (text && text.length > 100) return text;
  }
  return null;
}

async function handleChatScheduleCommand(userCommand) {
  if (!state.activeProjectId) {
    showToast('Vui lòng chọn một dự án trước khi lập lịch.', 'warning');
    return;
  }

  const feed = document.getElementById('chatFeed');
  const contextText = (typeof extractLatestArticleFromChat === 'function' ? extractLatestArticleFromChat() : null) || extractLatestAssistantContent();
  if (!contextText) {
    showToast('Chưa có nội dung bài viết hoặc kế hoạch nào trong chat để lập lịch. Hãy yêu cầu CMO tạo nội dung trước!', 'warning');
    return;
  }

  const row = document.createElement('div');
  row.className = 'message-row assistant schedule-card-row';
  row.innerHTML = `
    <div class="avatar ai-avatar"><svg fill="currentColor" viewBox="0 0 24 24"><path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20a2 2 0 0 0 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2m0 16H5V10h14zM9 14H7v-2h2zm4 0h-2v-2h2zm4 0h-2v-2h2zm-8 4H7v-2h2zm4 4h-2v-2h2zm4 0h-2v-2h2z"/></svg></div>
    <div class="bubble">
      <div style="display:flex; align-items:center; gap:8px; font-size:13px; color:var(--text-muted);">
        <span class="typing-dots"><span></span><span></span><span></span></span>
        <span>🔍 Đang phân tích bối cảnh & trích xuất thời điểm lập lịch...</span>
      </div>
    </div>
  `;
  feed.appendChild(row);
  if (typeof scrollToBottom === 'function') scrollToBottom(true);

  try {
    const res = await fetch(`/api/v1/portal/projects/${state.activeProjectId}/parse-schedule-intent`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        message: userCommand,
        context_text: contextText
      })
    });
    const data = await res.json();
    if (!res.ok) throw new Error(data.detail || 'Không thể phân tích yêu cầu lập lịch.');

    renderScheduleApprovalCard(row, data, contextText);
  } catch (err) {
    const bubble = row.querySelector('.bubble');
    if (bubble) {
      bubble.innerHTML = `<div style="color:#f87171; font-size:13px;">❌ Không thể lập lịch: ${escapeHtml(err.message)}</div>`;
    }
  }
}
window.handleChatScheduleCommand = handleChatScheduleCommand;

function renderScheduleApprovalCard(row, data, rawContent) {
  const bubble = row.querySelector('.bubble');
  if (!bubble) return;

  const cardId = 'sched_' + Date.now();
  window._scheduledCardData = window._scheduledCardData || {};
  window._scheduledCardData[cardId] = {
    ...data,
    content: rawContent
  };

  const isDeliverable = data.asset_type === 'article_deliverable';
  const assetBadge = isDeliverable ? '📄 Bài Viết SEO Hoàn Chỉnh' : '📋 Kế Hoạch Chiến Lược On-Page';
  const assetColor = isDeliverable ? '#38bdf8' : '#a78bfa';

  bubble.innerHTML = `
    <div class="schedule-card-wrapper" id="${cardId}" style="border: 1px solid rgba(56, 189, 248, 0.3); border-radius: 12px; background: rgba(15, 23, 42, 0.75); padding: 14px 16px; margin: 4px 0; backdrop-filter: blur(8px);">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 10px; border-bottom: 1px solid var(--border-subtle); padding-bottom: 8px;">
        <div style="display:flex; align-items:center; gap:8px;">
          <span style="font-size:16px;">📅</span>
          <strong style="color: #f8fafc; font-size: 13.5px; letter-spacing: -0.2px;">LẬP LỊCH & CHỜ PHÊ DUYỆT</strong>
        </div>
        <span class="sched-status-badge" style="background: rgba(245, 158, 11, 0.15); border: 1px solid rgba(245, 158, 11, 0.4); color: #fbbf24; font-size: 11.5px; padding: 2px 8px; border-radius: 999px; font-weight: 500;">
          🟡 Chờ Phê Duyệt
        </span>
      </div>

      <div style="margin-bottom: 10px;">
        <div style="display:inline-block; font-size:11px; padding:2px 6px; border-radius:4px; background:rgba(255,255,255,0.06); color:${assetColor}; margin-bottom:4px; font-weight:500;">
          ${escapeHtml(assetBadge)}
        </div>
        <h4 style="margin: 0 0 6px; font-size: 14.5px; color: #f1f5f9; line-height: 1.4;">
          ${escapeHtml(data.title || 'Tiêu đề nội dung')}
        </h4>
        <div style="font-size: 12.5px; color: var(--text-muted); line-height: 1.5; font-style: italic;">
          "${escapeHtml(data.summary || '')}"
        </div>
      </div>

      <div class="mission-grid" style="margin: 10px 0; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 12px;">
        <div class="mission-item" style="background: rgba(255,255,255,0.03); padding: 6px 10px; border-radius: 6px;">
          <span style="color:var(--text-muted); display:block; margin-bottom:2px;">Thời điểm đăng tải</span>
          <strong class="sched-time-display" style="color: #34d399; font-size: 12.5px;">⏰ ${escapeHtml(data.display_time)}</strong>
        </div>
        <div class="mission-item" style="background: rgba(255,255,255,0.03); padding: 6px 10px; border-radius: 6px;">
          <span style="color:var(--text-muted); display:block; margin-bottom:2px;">Định dạng (CPT)</span>
          <strong style="color: #93c5fd; font-size: 12.5px;">🌐 WordPress (${escapeHtml(data.post_type || 'post')})</strong>
        </div>
      </div>

      <div class="chat-action-toolbar" style="margin-top: 12px; display:flex; gap: 8px; flex-wrap: wrap;">
        <button type="button" class="btn-action-tool btn-sched-approve" onclick="executeCardApproval('${cardId}', this)" style="background: #10b981; color: #fff; font-weight: 600; border-color: #059669;">
          ✅ Phê Duyệt Lập Lịch
        </button>
        <button type="button" class="btn-action-tool" onclick="executeCardInstantPublish('${cardId}', this)" style="background: rgba(59, 130, 246, 0.2); color: #93c5fd; border-color: rgba(59, 130, 246, 0.4);">
          ⚡ Xuất Bản Ngay
        </button>
        <button type="button" class="btn-action-tool" onclick="promptEditScheduleTime('${cardId}')" style="background: rgba(255, 255, 255, 0.05); color: #cbd5e1;">
          ✏️ Đổi Giờ
        </button>
        <button type="button" class="btn-action-tool" onclick="cancelScheduleCard('${cardId}')" style="background: transparent; color: #94a3b8; border-color: transparent;">
          ❌ Hủy
        </button>
      </div>
    </div>
  `;
  if (typeof scrollToBottom === 'function') scrollToBottom(true);
}

async function executeCardApproval(cardId, btn) {
  const item = window._scheduledCardData?.[cardId];
  if (!item) return;

  if (btn) {
    btn.disabled = true;
    btn.innerHTML = '⏳ Đang lập lịch...';
  }

  try {
    const res = await fetch(`/api/v1/portal/projects/${state.activeProjectId}/schedule-task`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        title: item.title,
        content: item.content || item.raw_content,
        scheduled_at: item.scheduled_at,
        post_type: item.post_type || 'post',
        auto_approve: true,
        task_type: 'scheduled_publish'
      })
    });
    const d = await res.json();
    if (!res.ok) throw new Error(d.detail || 'Không thể lưu phê duyệt lập lịch.');

    const card = document.getElementById(cardId);
    if (card) {
      card.style.borderColor = 'rgba(16, 185, 129, 0.45)';
      const badge = card.querySelector('.sched-status-badge');
      if (badge) {
        badge.style.background = 'rgba(16, 185, 129, 0.15)';
        badge.style.borderColor = 'rgba(16, 185, 129, 0.4)';
        badge.style.color = '#34d399';
        badge.innerHTML = '🟢 Đã Phê Duyệt Lập Lịch';
      }
      const actions = card.querySelector('.chat-action-toolbar');
      if (actions) {
        const wpData = d.wp_result?.data || {};
        const permalink = wpData.permalink || '';
        const postId = wpData.post_id || '';
        const p = state.activeProject || {};
        const siteUrl = (p.cms_api_url || p.target_website_url || '').replace(/\/+$/, '');
        const editUrl = postId && siteUrl ? `${siteUrl}/wp-admin/post.php?post=${postId}&action=edit` : '';

        actions.innerHTML = `
          <div style="font-size:12px; color:#34d399; font-weight:500; display:flex; align-items:center; gap:6px; width:100%; margin-bottom:4px;">
            <span>🎉 Bài viết đã được lên lịch thành công trên WordPress. Hệ thống sẽ tự động kích hoạt xuất bản vào thời điểm đã định.</span>
          </div>
          ${editUrl ? `<a href="${escapeHtml(editUrl)}" target="_blank" class="btn-action-tool" style="background:rgba(99,102,241,0.25); border-color:rgba(99,102,241,0.45); color:#c7d2fe; text-decoration:none;">✏️ Xem Bài Trong WP Admin</a>` : ''}
          ${permalink ? `<a href="${escapeHtml(permalink)}" target="_blank" class="btn-action-tool" style="background:#2563eb; color:#fff; text-decoration:none;">🔗 Xem Trước URL</a>` : ''}
          <button type="button" class="btn-action-tool" onclick="copyChatText(this)">📋 Sao Chép Nội Dung</button>
        `;
        actions._rawText = item.content || item.raw_content;
      }
    }
    showToast('🎉 Đã phê duyệt và lập lịch bài viết thành công!', 'success');
  } catch (err) {
    showToast(`Lỗi phê duyệt: ${err.message}`, 'error');
    if (btn) {
      btn.disabled = false;
      btn.innerHTML = '✅ Phê Duyệt Lập Lịch';
    }
  }
}
window.executeCardApproval = executeCardApproval;

function executeCardInstantPublish(cardId, btn) {
  const item = window._scheduledCardData?.[cardId];
  if (!item) return;
  if (typeof triggerChatPublish === 'function') {
    triggerChatPublish(item.content || item.raw_content);
  }
}
window.executeCardInstantPublish = executeCardInstantPublish;

async function promptEditScheduleTime(cardId) {
  const item = window._scheduledCardData?.[cardId];
  if (!item) return;
  const newTimeStr = prompt('Nhập thời gian mới muốn lên lịch (Ví dụ: 14h chiều mai, 9h thứ hai tuần sau, hoặc YYYY-MM-DD HH:MM):', item.display_time);
  if (!newTimeStr) return;

  try {
    const res = await fetch(`/api/v1/portal/projects/${state.activeProjectId}/parse-schedule-intent`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        message: newTimeStr,
        context_text: item.title
      })
    });
    const d = await res.json();
    if (!res.ok) throw new Error(d.detail || 'Lỗi phân tích giờ.');

    item.scheduled_at = d.scheduled_at;
    item.display_time = d.display_time;
    const card = document.getElementById(cardId);
    if (card) {
      const timeEl = card.querySelector('.sched-time-display');
      if (timeEl) timeEl.innerHTML = `⏰ ${escapeHtml(d.display_time)}`;
    }
    showToast(`Đã đổi giờ hẹn sang: ${d.display_time}`, 'info');
  } catch (err) {
    showToast(`Không thể cập nhật giờ: ${err.message}`, 'error');
  }
}
window.promptEditScheduleTime = promptEditScheduleTime;

function cancelScheduleCard(cardId) {
  const card = document.getElementById(cardId);
  if (card) {
    card.style.opacity = '0.5';
    const badge = card.querySelector('.sched-status-badge');
    if (badge) {
      badge.style.background = 'rgba(239, 68, 68, 0.15)';
      badge.style.borderColor = 'rgba(239, 68, 68, 0.4)';
      badge.style.color = '#f87171';
      badge.innerHTML = '⚪ Đã Hủy';
    }
    const actions = card.querySelector('.chat-action-toolbar');
    if (actions) actions.remove();
  }
  showToast('Đã hủy thẻ lập lịch này.', 'info');
}
window.cancelScheduleCard = cancelScheduleCard;
