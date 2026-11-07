// portal_chat_sessions.js - GEMINI MULTI-SESSION MANAGER (SOP 23 compliant <= 300 lines)
let chatSessionsState = {
  sessions: [],
  activeConversationId: null,
  isDrawerOpen: window.innerWidth > 900 ? localStorage.getItem('chat_drawer_open') !== 'false' : false
};

function toggleChatSessionsDrawer(forceState = null) {
  const drawer = document.getElementById('chatSessionsDrawer');
  const btn = document.getElementById('btnToggleSessions');
  if (!drawer) return;
  chatSessionsState.isDrawerOpen = (forceState !== null) ? forceState : !chatSessionsState.isDrawerOpen;
  drawer.classList.toggle('collapsed', !chatSessionsState.isDrawerOpen);
  if (btn) btn.classList.toggle('active', chatSessionsState.isDrawerOpen);
  localStorage.setItem('chat_drawer_open', chatSessionsState.isDrawerOpen);
}
window.toggleChatSessionsDrawer = toggleChatSessionsDrawer;

async function loadProjectChatSessions(projectId) {
  const container = document.getElementById('sessionsListContainer');
  if (!container || !projectId) return;
  const tr = (k, p, def) => (typeof t === 'function' ? t(k, p) : def);
  container.innerHTML = `<div style="text-align:center;padding:20px;color:var(--text-dim);font-size:12px;">${tr('chat.loading_sessions', {}, 'Đang nạp lịch sử phiên...')}</div>`;

  try {
    const res = await fetch(`/api/v1/portal/projects/${projectId}/conversations`);
    if (!res.ok) throw new Error('Không thể tải danh sách phiên.');
    const data = await res.json();
    chatSessionsState.sessions = data.conversations || [];
    chatSessionsState.activeConversationId = data.active_conversation_id;
    renderChatSessionsList(chatSessionsState.sessions, chatSessionsState.activeConversationId);
  } catch (err) {
    container.innerHTML = `<div class="session-empty-state" style="color:var(--danger);">${err.message}</div>`;
  }
}
window.loadProjectChatSessions = loadProjectChatSessions;

function groupSessionsByDate(sessions) {
  const tr = (k, p, def) => (typeof t === 'function' ? t(k, p) : def);
  const now = new Date();
  const startToday = new Date(now.getFullYear(), now.getMonth(), now.getDate()).getTime();
  const start7Days = startToday - (7 * 86400000);

  const groups = {
    today: { title: tr('chat.group_today', {}, 'Hôm nay'), items: [] },
    past7Days: { title: tr('chat.group_7days', {}, '7 ngày qua'), items: [] },
    older: { title: tr('chat.group_older', {}, 'Cũ hơn'), items: [] }
  };

  (sessions || []).forEach(s => {
    const time = new Date(s.updated_at || s.created_at).getTime();
    if (time >= startToday) groups.today.items.push(s);
    else if (time >= start7Days) groups.past7Days.items.push(s);
    else groups.older.items.push(s);
  });
  return groups;
}

function renderChatSessionsList(sessions, activeDifyId) {
  const container = document.getElementById('sessionsListContainer');
  if (!container) return;
  const tr = (k, p, def) => (typeof t === 'function' ? t(k, p) : def);

  if (!sessions || sessions.length === 0) {
    container.innerHTML = `<div class="session-empty-state">${tr('chat.empty_sessions', {}, 'Chưa có phiên trò chuyện nào.<br>Hãy bấm <strong>Đoạn chat mới</strong> để bắt đầu!')}</div>`;
    return;
  }

  const groups = groupSessionsByDate(sessions);
  let html = '';

  ['today', 'past7Days', 'older'].forEach(key => {
    const grp = groups[key];
    if (grp.items.length === 0) return;
    html += `<div class="session-group-label">${grp.title}</div>`;
    grp.items.forEach(s => {
      const isActive = (s.dify_conversation_id === activeDifyId);
      const msgCount = s.message_count || 1;
      const isWarn = msgCount >= 30;
      const badgeTitle = isWarn ? tr('chat.session_warn_tooltip', {}, 'Phiên đã dài (>30 tin). Nên mở phiên mới để AI phản hồi nhanh.') : '';
      const badgeCls = isWarn ? 'session-item-badge warning' : 'session-item-badge';

      html += `
        <div class="session-item ${isActive ? 'active' : ''}" data-id="${s.id}" data-dify-id="${s.dify_conversation_id}" onclick="selectChatSession('${s.id}', '${s.dify_conversation_id}')">
          <div class="session-item-content">
            <span class="session-item-icon">💬</span>
            <span class="session-item-title" title="${escapeHtml(s.title || '')}">${escapeHtml(s.title || 'Cuộc trò chuyện')}</span>
            <span class="${badgeCls}" title="${badgeTitle}">${msgCount}${isWarn ? ' ⚠️' : ''}</span>
          </div>
          <div class="session-item-actions">
            <button type="button" class="session-action-btn" title="${tr('chat.rename_session', {}, 'Đổi tên')}" onclick="event.stopPropagation(); promptRenameSession('${s.id}', '${escapeHtml(s.title || '')}')">✏️</button>
            <button type="button" class="session-action-btn btn-delete" title="${tr('chat.delete_session', {}, 'Xóa phiên')}" onclick="event.stopPropagation(); deleteChatSession('${s.id}')">🗑️</button>
          </div>
        </div>
      `;
    });
  });
  container.innerHTML = html;
}

async function selectChatSession(sessionId, difyConversationId) {
  if (state.activeProject?.dify_conversation_id === difyConversationId) return;
  const tr = (k, p, def) => (typeof t === 'function' ? t(k, p) : def);
  try {
    const res = await fetch(`/api/v1/portal/projects/${state.activeProjectId}/conversations/switch`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ dify_conversation_id: difyConversationId })
    });
    if (!res.ok) throw new Error('Không thể chuyển phiên.');
    state.activeProject.dify_conversation_id = difyConversationId;
    chatSessionsState.activeConversationId = difyConversationId;
    renderChatSessionsList(chatSessionsState.sessions, difyConversationId);
    if (typeof loadChatHistory === 'function') loadChatHistory(state.activeProjectId);
    if (window.innerWidth <= 900 && typeof toggleChatSessionsDrawer === 'function') {
      toggleChatSessionsDrawer(false);
    }
  } catch (err) {
    showToast(tr('chat.switch_session_error', { err: err.message }, 'Lỗi khi chuyển phiên: ' + err.message), 'error');
  }
}
window.selectChatSession = selectChatSession;

async function createNewChatSession() {
  const tr = (k, p, def) => (typeof t === 'function' ? t(k, p) : def);
  if (!state.activeProjectId) return;
  try {
    const res = await fetch(`/api/v1/portal/projects/${state.activeProjectId}/conversations/new`, { method: 'POST' });
    if (!res.ok) throw new Error('Không thể tạo phiên mới.');
    state.activeProject.dify_conversation_id = null;
    chatSessionsState.activeConversationId = null;
    updateSessionIndicator(null);
    if (typeof renderWelcomeMessage === 'function') renderWelcomeMessage();
    await loadProjectChatSessions(state.activeProjectId);
    if (window.innerWidth <= 900 && typeof toggleChatSessionsDrawer === 'function') {
      toggleChatSessionsDrawer(false);
    }
    showToast(tr('chat.new_session_ready', {}, 'Đã sẵn sàng phiên hội thoại mới.'), 'success');
  } catch (err) {
    showToast(tr('chat.create_session_error', { err: err.message }, 'Lỗi tạo phiên mới: ' + err.message), 'error');
  }
}
window.createNewChatSession = createNewChatSession;

async function promptRenameSession(sessionId, oldTitle) {
  const tr = (k, p, def) => (typeof t === 'function' ? t(k, p) : def);
  const newTitle = prompt(tr('chat.prompt_new_title', {}, 'Nhập tiêu đề mới cho phiên trò chuyện:'), oldTitle);
  if (!newTitle || newTitle.trim() === oldTitle) return;
  try {
    const res = await fetch(`/api/v1/portal/projects/${state.activeProjectId}/conversations/${sessionId}`, {
      method: 'PATCH',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ title: newTitle.trim() })
    });
    if (!res.ok) throw new Error('Không thể đổi tên.');
    await loadProjectChatSessions(state.activeProjectId);
    showToast(tr('chat.rename_success', {}, 'Đã đổi tên phiên thành công.'), 'success');
  } catch (err) {
    showToast(err.message, 'error');
  }
}
window.promptRenameSession = promptRenameSession;

async function deleteChatSession(sessionId) {
  const tr = (k, p, def) => (typeof t === 'function' ? t(k, p) : def);
  if (!confirm(tr('chat.confirm_delete_session', {}, 'Bạn có chắc chắn muốn xóa phiên trò chuyện này không? Toàn bộ lịch sử trao đổi của phiên sẽ bị xóa.'))) return;
  try {
    const res = await fetch(`/api/v1/portal/projects/${state.activeProjectId}/conversations/${sessionId}`, { method: 'DELETE' });
    if (!res.ok) throw new Error('Không thể xóa phiên.');
    showToast(tr('chat.delete_session_success', {}, 'Đã xóa phiên trò chuyện thành công.'), 'success');
    await loadProjectChatSessions(state.activeProjectId);
    if (typeof loadChatHistory === 'function') loadChatHistory(state.activeProjectId);
  } catch (err) {
    showToast(err.message, 'error');
  }
}
window.deleteChatSession = deleteChatSession;

// Khởi tạo drawer theo trạng thái đã lưu
document.addEventListener('DOMContentLoaded', () => {
  toggleChatSessionsDrawer(chatSessionsState.isDrawerOpen);
});

