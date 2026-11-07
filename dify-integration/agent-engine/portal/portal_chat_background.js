// ============================================================
// TAB 1: COPILOT BACKGROUND CHAT & RESILIENT SESSION RESUME
// Strictly complies with SOP 23 (<= 300 lines)
// ============================================================

window.activeProjectStreams = window.activeProjectStreams || new Map();
let bgPollingTimer = null;

async function checkBackgroundChatStatus(projectId) {
  if (!projectId) return;
  if (bgPollingTimer) {
    clearInterval(bgPollingTimer);
    bgPollingTimer = null;
  }

  try {
    const res = await fetch(`/api/v1/portal/projects/${projectId}/chat-status`);
    const d = await res.json();
    if (!d || !d.is_generating) {
      if (d && (d.status === 'completed' || d.status === 'idle')) {
        fetch(`/api/v1/portal/projects/${projectId}/chat-ack`, { method: 'POST' }).catch(() => {});
      }
      return;
    }

    renderBackgroundResumeCard(projectId, d);
  } catch (e) {
    console.warn('[BG_STATUS_CHECK] Error:', e);
  }
}
window.checkBackgroundChatStatus = checkBackgroundChatStatus;

function renderBackgroundResumeCard(projectId, taskData) {
  const feed = document.getElementById('chatFeed');
  if (!feed) return;
  const tr = (k, p, def) => (typeof t === 'function' ? t(k, p) : def);

  if (bgPollingTimer) {
    clearInterval(bgPollingTimer);
    bgPollingTimer = null;
  }

  const existingRow = feed.querySelector('.bg-task-row');
  if (existingRow) existingRow.remove();

  const bgRow = document.createElement('div');
  bgRow.className = 'message-row assistant bg-task-row';
  bgRow.innerHTML = `
    <div class="avatar ai-avatar"><svg fill="currentColor" viewBox="0 0 24 24"><path d="M12 2a2 2 0 0 1 2 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 0 1 7 7h1a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v1a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-1H2a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h1a7 7 0 0 1 7-7h1V5.73c-.6-.34-1-.99-1-1.73a2 2 0 0 1 2-2M7.5 13A2.5 2.5 0 0 0 5 15.5 2.5 2.5 0 0 0 7.5 18a2.5 2.5 0 0 0 2.5-2.5A2.5 2.5 0 0 0 7.5 13m9 0a2.5 2.5 0 0 0-2.5 2.5 2.5 2.5 0 0 0 2.5 2.5 2.5 2.5 0 0 0 2.5-2.5A2.5 2.5 0 0 0 7.5 13"/></svg></div>
    <div class="bubble">
      <div style="display:flex;align-items:center;gap:10px;font-size:13px;color:#c7d2fe;">
        <span class="typing-dots"><span></span><span></span><span></span></span>
        <span id="bgStatusText">${tr('chat.bg_running', { elapsed: taskData.elapsed_seconds }, `🤖 AI đang tiếp tục xử lý chiến lược ngầm tại máy chủ... (${taskData.elapsed_seconds}s)`)}</span>
      </div>
      <div style="font-size:11.5px;color:var(--text-muted);margin-top:6px;">
        💡 Bạn có thể tắt máy tính hoặc chuyển sang làm việc ở các Tab/Dự án khác, máy chủ vẫn hoàn tất 100%.
      </div>
    </div>
  `;
  feed.appendChild(bgRow);
  if (typeof scrollToBottom === 'function') scrollToBottom(true);
  state.isStreaming = true;
  if (typeof updateSendButtonState === 'function') updateSendButtonState(true);

  let isFetching = false;
  const timerInstance = setInterval(async () => {
    if (state.activeProjectId !== projectId || bgPollingTimer !== timerInstance) {
      clearInterval(timerInstance);
      if (bgPollingTimer === timerInstance) bgPollingTimer = null;
      return;
    }
    if (isFetching) return;
    isFetching = true;
    try {
      const sRes = await fetch(`/api/v1/portal/projects/${projectId}/chat-status`);
      const sData = await sRes.json();
      if (bgPollingTimer !== timerInstance) return;

      const statusTxt = document.getElementById('bgStatusText');

      if (sData.is_generating && statusTxt) {
        statusTxt.textContent = tr('chat.bg_running', { elapsed: sData.elapsed_seconds }, `🤖 AI đang tiếp tục xử lý chiến lược ngầm tại máy chủ... (${sData.elapsed_seconds}s)`);
      } else if (sData.status === 'completed') {
        clearInterval(timerInstance);
        if (bgPollingTimer === timerInstance) bgPollingTimer = null;
        if (bgRow && bgRow.parentNode) bgRow.remove();

        state.isStreaming = false;
        if (typeof updateSendButtonState === 'function') updateSendButtonState(false);
        fetch(`/api/v1/portal/projects/${projectId}/chat-ack`, { method: 'POST' }).catch(() => {});

        if (typeof loadChatHistory === 'function') loadChatHistory(projectId, false);
        showToast(tr('chat.bg_completed', {}, '✅ Bản kế hoạch chạy ngầm đã hoàn tất!'), 'success');
      } else if (['error', 'timeout', 'aborted'].includes(sData.status)) {
        clearInterval(timerInstance);
        if (bgPollingTimer === timerInstance) bgPollingTimer = null;
        state.isStreaming = false;
        if (typeof updateSendButtonState === 'function') updateSendButtonState(false);
        fetch(`/api/v1/portal/projects/${projectId}/chat-ack`, { method: 'POST' }).catch(() => {});
        if (bgRow && bgRow.querySelector('.bubble')) {
          bgRow.querySelector('.bubble').innerHTML = `<div style="color:var(--danger);font-size:12.5px;">⚠️ ${sData.error || 'Tác vụ chạy ngầm đã kết thúc.'}</div>`;
        }
      }
    } catch (e) {
      console.warn('[BG_POLLING_ERROR]', e);
    } finally {
      isFetching = false;
    }
  }, 2500);
  bgPollingTimer = timerInstance;
}

async function abortServerChatTask(projectId) {
  const pId = projectId || state.activeProjectId;
  if (!pId) return;
  if (bgPollingTimer) {
    clearInterval(bgPollingTimer);
    bgPollingTimer = null;
  }
  try {
    await fetch(`/api/v1/portal/projects/${pId}/chat-abort`, { method: 'POST' });
  } catch (e) {}
}
window.abortServerChatTask = abortServerChatTask;

