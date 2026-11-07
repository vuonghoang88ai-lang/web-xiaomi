// TAB 1: COPILOT CHAT WORKSPACE (SOP 23 compliant <= 300 lines)
let chatAbortController = null;

async function loadChatHistory(projectId, checkBg = true) {
  const feed = document.getElementById('chatFeed');
  if (!feed) return;
  const tr = (k, def) => (typeof t === 'function' ? t(k) : def);
  feed.innerHTML = `<div style="text-align:center;padding:40px;color:var(--text-dim);"><div class="typing-dots"><span></span><span></span><span></span></div><p style="margin-top:10px;font-size:13px;">${tr('chat.loading_history', 'Đang tải lại trí nhớ dự án từ Dify...')}</p></div>`;

  try {
    const res = await fetch(`/api/v1/portal/projects/${projectId}/chat-history?user=${encodeURIComponent(state.currentUser)}`);
    const data = await res.json();
    updateSessionIndicator(data.conversation_id);
    if (state.activeProject) state.activeProject.dify_conversation_id = data.conversation_id;

    feed.innerHTML = '';
    if (!data.messages || data.messages.length === 0) {
      if (typeof renderWelcomeMessage === 'function') renderWelcomeMessage();
    } else {
      data.messages.forEach(msg => appendMessage(msg.role, msg.content, msg.thought, msg.thought_steps, msg.files));
      scrollToBottom(true);
    }
    if (checkBg && typeof checkBackgroundChatStatus === 'function') checkBackgroundChatStatus(projectId);
  } catch (err) {
    feed.innerHTML = `<div style="color:var(--danger);padding:20px;">${tr('chat.loading_history_error', 'Không thể tải lịch sử trò chuyện.')}</div>`;
  }
}

function appendMessage(role, content, thought = null, thoughtSteps = null, files = null) {
  const feed = document.getElementById('chatFeed');
  if (!feed) return;
  const row = document.createElement('div');
  row.className = `message-row ${role}`;
  const avatar = document.createElement('div');
  avatar.className = `avatar ${role === 'user' ? 'user-avatar' : 'ai-avatar'}`;
  avatar.innerHTML = role === 'user' ? 'U' : `<svg fill="currentColor" viewBox="0 0 24 24"><path d="M12 2a2 2 0 0 1 2 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 0 1 7 7h1a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v1a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-1H2a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h1a7 7 0 0 1 7-7h1V5.73c-.6-.34-1-.99-1-1.73a2 2 0 0 1 2-2M7.5 13A2.5 2.5 0 0 0 5 15.5 2.5 2.5 0 0 0 7.5 18a2.5 2.5 0 0 0 2.5-2.5A2.5 2.5 0 0 0 7.5 13m9 0a2.5 2.5 0 0 0-2.5 2.5 2.5 2.5 0 0 0 2.5 2.5 2.5 2.5 0 0 0 2.5-2.5A2.5 2.5 0 0 0 7.5 13"/></svg>`;

  const bubble = document.createElement('div');
  bubble.className = 'bubble';
  let filesHtml = (role === 'user' && typeof renderAttachmentPillsHtml === 'function') ? renderAttachmentPillsHtml(files) : '';
  let thoughtHtml = '';
  if (role === 'assistant') {
    const tBox = renderThoughtBox(thought, thoughtSteps, false);
    if (tBox) thoughtHtml = `<div class="thought-container">${tBox}</div>`;
  }
  let mainHtml = (role === 'assistant' && typeof renderBoardroomDebateMarkup === 'function') ? renderBoardroomDebateMarkup(content || '') : parseMarkdown(content || '');
  if (role === 'assistant' && !mainHtml.trim() && !thoughtHtml) {
    mainHtml = `<div style="color:var(--danger);font-size:12.5px;line-height:1.5;">⚠️ <strong>Không nhận được phản hồi từ AI (Timeout).</strong><br><span style="color:var(--text-dim);font-size:11.5px;">Phiên chat đã tích lũy quá dài. Hãy bấm nút <em>'Làm mới phiên chat'</em> màu đỏ ở góc trên để bắt đầu phiên mới.</span></div>`;
  }
  bubble.innerHTML = `${filesHtml}${thoughtHtml}<div class="answer-container">${mainHtml}</div>`;
  if (role === 'assistant' && (content || '').length > 250 && typeof createActionToolbar === 'function') {
    bubble.appendChild(createActionToolbar(content));
    if (typeof renderNextActionChipsContainer === 'function') {
      renderNextActionChipsContainer(bubble, content);
    }
  }

  row.appendChild(avatar);
  row.appendChild(bubble);
  feed.appendChild(row);
  scrollToBottom(true);
}

function updateSendButtonState(isStreaming) {
  const btn = document.getElementById('btnSend');
  if (!btn) return;
  const tr = (k, def) => (typeof t === 'function' ? t(k) : def);
  btn.classList.toggle('btn-stop', isStreaming);
  btn.title = isStreaming ? tr('chat.btn_stop_title', 'Dừng sinh câu trả lời (Stop)') : tr('chat.send_btn_title', 'Gửi tin nhắn');
  btn.innerHTML = isStreaming ? `<svg fill="currentColor" viewBox="0 0 24 24"><rect x="6" y="6" width="12" height="12" rx="2"/></svg>` :
                                `<svg fill="currentColor" viewBox="0 0 24 24"><path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/></svg>`;
}

function abortChatStream() {
  if (chatAbortController) { chatAbortController.abort(); chatAbortController = null; }
  if (typeof abortServerChatTask === 'function') abortServerChatTask(state.activeProjectId);
  state.isStreaming = false;
  updateSendButtonState(false);
}
window.abortChatStream = abortChatStream;

async function sendMessage() {
  const input = document.getElementById('chatInput');
  const message = input.value.trim();
  if (state.isStreaming) {
    abortChatStream();
    return;
  }
  const pendingFiles = (typeof getPendingAttachments === 'function') ? getPendingAttachments() : [];
  if (typeof isAttachmentsUploading === 'function' && isAttachmentsUploading()) {
    if (typeof showToast === 'function') showToast('Đang tải tệp lên máy chủ, vui lòng đợi giây lát...', 'warning');
    return;
  }
  if ((!message && !pendingFiles.length) || !state.activeProjectId) return;

  input.value = '';
  input.style.height = 'auto';
  appendMessage('user', message, null, null, pendingFiles);
  if (typeof clearAttachments === 'function') clearAttachments();

  if (typeof handleCustomChatCommand === 'function' && handleCustomChatCommand(message)) return;

  state.isStreaming = true;
  updateSendButtonState(true);
  chatAbortController = new AbortController();

  const tr = (k, p, def) => (typeof t === 'function' ? t(k, p) : def);
  const feed = document.getElementById('chatFeed');
  const aiRow = document.createElement('div');
  aiRow.className = 'message-row assistant';
  aiRow.innerHTML = `
    <div class="avatar ai-avatar"><svg fill="currentColor" viewBox="0 0 24 24"><path d="M12 2a2 2 0 0 1 2 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 0 1 7 7h1a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v1a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-1H2a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h1a7 7 0 0 1 7-7h1V5.73c-.6-.34-1-.99-1-1.73a2 2 0 0 1 2-2M7.5 13A2.5 2.5 0 0 0 5 15.5 2.5 2.5 0 0 0 7.5 18a2.5 2.5 0 0 0 2.5-2.5A2.5 2.5 0 0 0 7.5 13m9 0a2.5 2.5 0 0 0-2.5 2.5 2.5 2.5 0 0 0 2.5 2.5 2.5 2.5 0 0 0 2.5-2.5A2.5 2.5 0 0 0 7.5 13"/></svg></div>
    <div class="bubble">
      <div class="thought-container" style="display:none;"></div>
      <div class="answer-container">
        <span class="typing-dots"><span></span><span></span><span></span></span>
        <span class="wait-status-text" style="font-size:12.5px;color:var(--text-muted);margin-left:8px;">${tr('chat.status_hint_1', {}, '🔍 Đang phân tích câu hỏi & đối chiếu bối cảnh...')}</span>
      </div>
    </div>
  `;
  feed.appendChild(aiRow);
  scrollToBottom(true);

  const answerContainer = aiRow.querySelector('.answer-container');
  const thoughtContainer = aiRow.querySelector('.thought-container');
  const statusEl = aiRow.querySelector('.wait-status-text');
  let accumulatedAnswer = '', streamSteps = [];

  const statusHints = [
    tr('chat.status_hint_1', {}, '🔍 Đang phân tích câu hỏi & đối chiếu bối cảnh...'),
    tr('chat.status_hint_2', {}, '🛠️ Đang kích hoạt trinh sát SERP & tra cứu đối thủ...'),
    tr('chat.status_hint_3', {}, '📊 Đang bóc tách thông số kỹ thuật & đối chiếu mã model...'),
    tr('chat.status_hint_4', {}, '✍️ Đang tổng hợp dữ liệu & chuẩn bị câu trả lời...')
  ];
  let hintIdx = 0;
  const statusTimer = setInterval(() => {
    hintIdx = (hintIdx + 1) % statusHints.length;
    if (statusEl && !accumulatedAnswer) statusEl.textContent = statusHints[hintIdx];
  }, 3500);

  try {
    const reqLang = (typeof getCurrentLang === 'function' ? getCurrentLang() : (state.lang || 'vi'));
    const res = await fetch(`/api/v1/portal/projects/${state.activeProjectId}/chat`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ message, user: state.currentUser, lang: reqLang, files: pendingFiles }),
      signal: chatAbortController.signal
    });

    const reader = res.body.getReader();
    const decoder = new TextDecoder('utf-8');
    let buffer = '', isStreamFinished = false;

    while (!isStreamFinished) {
      const { done, value } = await reader.read();
      if (done) break;
      buffer += decoder.decode(value, { stream: true });
      const lines = buffer.split('\n');
      buffer = lines.pop();

      for (const line of lines) {
        if (line.startsWith('data: ')) {
          try {
            const chunk = JSON.parse(line.slice(6));
            if (chunk.conversation_id && state.activeProject.dify_conversation_id !== chunk.conversation_id) {
              state.activeProject.dify_conversation_id = chunk.conversation_id;
              updateSessionIndicator(chunk.conversation_id);
              if (typeof loadProjectChatSessions === 'function') loadProjectChatSessions(state.activeProjectId);
            }
            if (chunk.event === 'agent_message' || chunk.event === 'message') {
              clearInterval(statusTimer);
              accumulatedAnswer += (chunk.answer || '');
              let cleanAns = accumulatedAnswer;
              if (cleanAns.includes('<｜') || cleanAns.includes('<|') || cleanAns.includes('<function_call')) {
                cleanAns = cleanAns.replace(/<[｜|]\s*DSML[\s\S]*?(?=(?:\n# |\n:::boardroom|\n\*\*\[TIER|$))/gi, '');
                cleanAns = cleanAns.replace(/<function_calls?>[\s\S]*?(<\/function_calls?>|$)/gi, '');
                const m = cleanAns.match(/(\n|^)(# [^\n]+|:::boardroom|\*\*\[TIER)/);
                if (m && m.index > 0) {
                  const preamble = cleanAns.slice(0, m.index).trim().toLowerCase();
                  if (['tôi sẽ', 'tôi cần', 'phân tích', 'function_call', 'dsml'].some(k => preamble.includes(k))) {
                    cleanAns = cleanAns.slice(m.index).trim();
                  }
                }
              }
              cleanAns = cleanAns.replace(/<[｜|]\s*DSML\s*[｜|][^>\n]*/gi, '');
              cleanAns = cleanAns.replace(/<function_calls?>[\s\S]*?(<\/function_calls?>|$)/gi, '');
              cleanAns = cleanAns.replace(/\[\d+\s+items\s+compressed\s+to\s+\d+\.\s+Retrieve\s+more:[^\]]+\]/gi, '').trim();
              if (cleanAns) {
                answerContainer.innerHTML = (typeof renderBoardroomDebateMarkup === 'function') ? renderBoardroomDebateMarkup(cleanAns) : parseMarkdown(cleanAns);
                scrollToBottom();
              }
            } else if (chunk.event === 'agent_thought') {
              const toolName = (chunk.tool || '').trim();
              let thText = (chunk.thought || '').replace(/<[｜|]\s*DSML\s*[｜|][^>\n]*/gi, '').trim();
              thText = thText.replace(/\[\d+\s+items\s+compressed\s+to\s+\d+\.\s+Retrieve\s+more:[^\]]+\]/gi, '').trim();
              if (toolName) {
                if (!streamSteps.some(s => s.tool === toolName && s.text === thText)) {
                  streamSteps.push({ tool: toolName, text: thText });
                }
                thoughtContainer.style.display = 'block';
                thoughtContainer.innerHTML = renderThoughtBox('', streamSteps, true);
                if (statusEl) statusEl.textContent = tr('chat.running_tool', { tool: toolName }, `🛠️ Đang chạy: ${toolName}...`);
                scrollToBottom();
              } else if (thText && thText.length > 20) {
                clearInterval(statusTimer);
                accumulatedAnswer = thText;
                answerContainer.innerHTML = (typeof renderBoardroomDebateMarkup === 'function') ? renderBoardroomDebateMarkup(accumulatedAnswer) : parseMarkdown(accumulatedAnswer);
                scrollToBottom();
              }
            } else if (chunk.event === 'message_end') {
              clearInterval(statusTimer);
              isStreamFinished = true;
              break;
            } else if (chunk.event === 'error') {
              clearInterval(statusTimer);
              answerContainer.innerHTML += `<div style="color:var(--danger);margin-top:8px;">⚠️ Lỗi: ${chunk.message}</div>`;
              isStreamFinished = true;
              break;
            }
          } catch (e) {}
        }
      }
    }
    if (!accumulatedAnswer && streamSteps.length === 0) {
      answerContainer.innerHTML = `<div style="color:var(--danger);font-size:12.5px;line-height:1.5;">⚠️ <strong>Máy chủ AI phản hồi quá thời gian (Timeout 300s).</strong><br><span style="color:var(--text-dim);font-size:11.5px;">Vui lòng bấm <em>'Làm mới phiên chat'</em> góc trên để giải phóng bộ nhớ.</span></div>`;
    }
    if (accumulatedAnswer.length > 250) {
      const bubbleEl = aiRow.querySelector('.bubble');
      if (typeof createActionToolbar === 'function') bubbleEl.appendChild(createActionToolbar(accumulatedAnswer));
      if (typeof renderNextActionChipsContainer === 'function') renderNextActionChipsContainer(bubbleEl, accumulatedAnswer);
      if (typeof autoSaveBoardroomMinutes === 'function') autoSaveBoardroomMinutes(state.activeProjectId, accumulatedAnswer, message);
      // Tự động mở Dual-Pane Canvas nếu sinh bài viết dài chuẩn SEO / WooCommerce
      if (accumulatedAnswer.length > 1200 && accumulatedAnswer.match(/^##?\s+/m) && typeof openArtifactCanvas === 'function') {
        openArtifactCanvas(accumulatedAnswer);
      }
    }
  } catch (err) {
    const isStopped = err.name === 'AbortError';
    const errText = isStopped ? tr('chat.stopped_by_user', {}, '⏹️ Bạn đã dừng quá trình sinh câu trả lời.') :
                                tr('chat.network_error', { error: err.message }, `Lỗi kết nối máy chủ: ${err.message}`);
    const errBadge = `<div style="color:${isStopped ? 'var(--text-dim)' : 'var(--danger)'};font-size:12.5px;margin-top:8px;">${escapeHtml(errText)}</div>`;
    answerContainer.innerHTML = accumulatedAnswer ? (answerContainer.innerHTML + errBadge) : errBadge;
  } finally {
    clearInterval(statusTimer);
    if (streamSteps.length > 0 && thoughtContainer) thoughtContainer.innerHTML = renderThoughtBox('', streamSteps, false);
    state.isStreaming = false;
    chatAbortController = null;
    updateSendButtonState(false);
    document.getElementById('chatInput').focus();
  }
}

async function resetChatSession() {
  const tr = (k, p, def) => (typeof t === 'function' ? t(k, p) : def);
  if (!state.activeProjectId || !confirm(tr('chat.confirm_reset', {}, 'Bạn có chắc chắn muốn làm mới phiên chat cho dự án này không?'))) return;
  try {
    await fetch(`/api/v1/portal/projects/${state.activeProjectId}/chat/reset`, { method: 'POST' });
    state.activeProject.dify_conversation_id = null;
    updateSessionIndicator(null);
    renderWelcomeMessage();
    if (typeof loadProjectChatSessions === 'function') loadProjectChatSessions(state.activeProjectId);
    showToast(tr('toasts.session_reset', {}, 'Đã làm mới phiên chat thành công.'), 'success');
  } catch (err) {
    showToast(tr('chat.reset_error', { error: err.message }, 'Lỗi khi làm mới phiên chat: ' + err.message), 'error');
  }
}

function scrollToBottom(force = false) {
  const feed = document.getElementById('chatFeed');
  if (!feed) return;
  if (force || (feed.scrollHeight - feed.scrollTop - feed.clientHeight) < 100) feed.scrollTop = feed.scrollHeight;
}
