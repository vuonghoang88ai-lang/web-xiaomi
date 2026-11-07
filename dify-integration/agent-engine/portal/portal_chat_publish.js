// ============================================================
// TAB 1: CONVERSATIONAL CHAT-TO-CMS PUBLISHING CONTROLLER
// SOP 23 Compliant (<= 300 lines) & SOP 30 Zero Hardcoding
// ============================================================

function extractLatestArticleFromChat() {
  const feed = document.getElementById('chatFeed');
  if (!feed) return null;
  const rows = Array.from(feed.querySelectorAll('.message-row.assistant'));
  for (let i = rows.length - 1; i >= 0; i--) {
    const row = rows[i];
    if (row.classList.contains('bg-task-row') || row.classList.contains('publish-result-row')) continue;
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
    if (text && text.length > 250) {
      return text;
    }
  }
  return null;
}

function cleanArticleDeliverable(raw) {
  if (!raw) return { title: 'Bài viết mới', content: '' };
  let clean = raw;
  if (clean.includes('<｜') || clean.includes('<|') || clean.includes('<function_call')) {
    clean = clean.replace(/<[｜|]\s*DSML[\s\S]*?(?=(?:\n# |\n:::boardroom|\n\*\*\[TIER|$))/gi, '');
    clean = clean.replace(/<function_calls?>[\s\S]*?(<\/function_calls?>|$)/gi, '');
    const m = clean.match(/(\n|^)(# [^\n]+|:::boardroom|\*\*\[TIER)/);
    if (m && m.index > 0) {
      const preamble = clean.slice(0, m.index).trim().toLowerCase();
      if (['tôi sẽ', 'tôi cần', 'phân tích', 'function_call', 'dsml'].some(k => preamble.includes(k))) {
        clean = clean.slice(m.index).trim();
      }
    }
  }
  clean = clean.replace(/<[｜|]\s*DSML\s*[｜|][^>\n]*/gi, '').trim();
  const debateEnd = clean.indexOf(':::\n\n');
  if (clean.includes(':::boardroom-debate') && debateEnd !== -1) {
    clean = clean.substring(debateEnd + 5).trim();
  }

  const h1Match = clean.match(/^#\s+(.+)$/m);
  let title = h1Match ? h1Match[1].trim() : '';
  if (!title) {
    const lines = clean.split('\n').map(l => l.trim()).filter(l => l.length > 0);
    title = (lines[0] || 'Bài viết từ CMO AI').replace(/^[#\*\s]+/, '').slice(0, 100);
  }
  return { title, content: clean };
}

function getAvailableCpts() {
  const p = state.activeProject || {};
  let topology = p.site_topology || {};
  if (typeof topology === 'string') {
    try { topology = JSON.parse(topology); } catch (e) { topology = {}; }
  }
  const postTypes = topology.post_types || {};
  const cpts = [{ key: 'post', label: 'Bài viết Blog (post)' }];
  Object.keys(postTypes).forEach(k => {
    if (k !== 'post' && k !== 'page' && k !== 'attachment' && k !== 'revision' && k !== 'nav_menu_item') {
      const lbl = k === 'sab_story' ? 'Bộ truyện (sab_story)' :
                  k === 'sab_chapter' ? 'Chương truyện (sab_chapter)' :
                  k === 'product' ? 'Sản phẩm WooCommerce (product)' : `${k}`;
      cpts.push({ key: k, label: lbl });
    }
  });
  cpts.push({ key: 'page', label: 'Trang tĩnh (page)' });
  return cpts;
}

function triggerChatPublish(specificContent = null) {
  const tr = (k, p, def) => (typeof t === 'function' ? t(k, p) : def);
  if (!state.activeProjectId) {
    showToast('Vui lòng chọn một dự án trước khi xuất bản.', 'warning');
    return;
  }

  const raw = specificContent || extractLatestArticleFromChat();
  if (!raw) {
    showToast(tr('chat.no_article_to_publish', {}, 'Chưa có bài viết nào trong cuộc trò chuyện để xuất bản. Hãy yêu cầu CMO AI viết bài trước!'), 'warning');
    return;
  }

  const { title, content } = cleanArticleDeliverable(raw);
  const cpts = getAvailableCpts();
  const cptOptions = cpts.map(c => `<option value="${c.key}">${escapeHtml(c.label)}</option>`).join('');

  let modal = document.getElementById('chatPublishModal');
  if (!modal) {
    modal = document.createElement('div');
    modal.id = 'chatPublishModal';
    modal.className = 'modal-overlay';
    modal.style.zIndex = '1100';
    document.body.appendChild(modal);
  }

  modal.innerHTML = `
    <div class="modal-card" style="max-width: 620px;">
      <div class="modal-header" style="border-bottom:1px solid var(--border); padding-bottom:12px; margin-bottom:14px; display:flex; justify-content:space-between; align-items:center;">
        <h3 style="margin:0; font-size:16px; color:#38bdf8; display:flex; align-items:center; gap:8px;">
          🚀 Xuất Bản Lên Website WordPress
        </h3>
        <button type="button" class="modal-close" onclick="closeChatPublishModal()">&times;</button>
      </div>
      <div class="modal-body" style="display:flex; flex-direction:column; gap:12px;">
        <div>
          <label style="font-size:12px; color:var(--text-muted); display:block; margin-bottom:4px; font-weight:600;">Tiêu đề bài viết:</label>
          <input type="text" id="publishTitleInput" class="form-input" style="width:100%; font-size:13.5px;" value="${escapeHtml(title)}">
        </div>
        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px;">
          <div>
            <label style="font-size:12px; color:var(--text-muted); display:block; margin-bottom:4px; font-weight:600;">Định dạng (Post Type):</label>
            <select id="publishCptSelect" class="form-input" style="width:100%; font-size:13px;">${cptOptions}</select>
          </div>
          <div>
            <label style="font-size:12px; color:var(--text-muted); display:block; margin-bottom:4px; font-weight:600;">Trạng thái:</label>
            <select id="publishStatusSelect" class="form-input" style="width:100%; font-size:13px;">
              <option value="draft" selected>🟡 Bản Nháp (Draft - Khuyến nghị)</option>
              <option value="publish">🟢 Xuất bản công khai (Publish)</option>
            </select>
          </div>
        </div>
        <div>
          <label style="font-size:12px; color:var(--text-muted); display:block; margin-bottom:4px; font-weight:600;">Chuyên mục (Tùy chọn):</label>
          <input type="text" id="publishCategoryInput" class="form-input" placeholder="Ví dụ: Tin tức, Review, Tiên hiệp..." style="width:100%; font-size:13px;">
        </div>
        <div style="background:rgba(15,23,42,0.6); padding:10px 14px; border-radius:8px; border:1px solid var(--border-subtle); font-size:12px; color:var(--text-muted);">
          📊 Độ dài nội dung: <strong>${content.length.toLocaleString()}</strong> ký tự • Tự động gửi tín hiệu <strong>IndexNow</strong> tới Google/Bing ngay khi xuất bản.
        </div>
      </div>
      <div class="modal-actions" style="margin-top:18px; display:flex; justify-content:space-between; align-items:center;">
        <button type="button" class="btn-secondary" onclick="if(typeof openArtifactCanvas==='function'){closeChatPublishModal();openArtifactCanvas(window._pendingPublishContent, document.getElementById('publishTitleInput').value);}" style="font-size:12px;">
          📦 Mở Trên Canvas Để Sửa
        </button>
        <div style="display:flex; gap:8px;">
          <button type="button" class="btn-secondary" onclick="closeChatPublishModal()">Hủy</button>
          <button type="button" class="btn-primary" id="btnExecutePublish" onclick="executeChatPublish()" style="background:#2563eb; color:#fff; font-weight:600;">
            🚀 Tiến Hành Đăng Tải
          </button>
        </div>
      </div>
    </div>
  `;
  window._pendingPublishContent = content;
  modal.classList.add('active');
  modal.style.display = 'flex';
}
window.triggerChatPublish = triggerChatPublish;

function closeChatPublishModal() {
  const modal = document.getElementById('chatPublishModal');
  if (modal) {
    modal.classList.remove('active');
    modal.style.display = 'none';
  }
}
window.closeChatPublishModal = closeChatPublishModal;

async function executeChatPublish() {
  const btn = document.getElementById('btnExecutePublish');
  const titleInput = document.getElementById('publishTitleInput');
  const cptSelect = document.getElementById('publishCptSelect');
  const statusSelect = document.getElementById('publishStatusSelect');
  const catInput = document.getElementById('publishCategoryInput');

  const title = (titleInput?.value || '').trim();
  const postType = cptSelect?.value || 'post';
  const postStatus = statusSelect?.value || 'draft';
  const category = (catInput?.value || '').trim();
  const content = window._pendingPublishContent || '';

  if (!title) {
    showToast('Vui lòng nhập tiêu đề bài viết.', 'warning');
    return;
  }

  if (btn) {
    btn.disabled = true;
    btn.innerHTML = '⏳ Đang đăng tải...';
  }

  try {
    const res = await fetch(`/api/v1/portal/projects/${state.activeProjectId}/publish-hybrid`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        title: title,
        content: content,
        post_type: postType,
        status: postStatus,
        category: category || undefined,
        focus_keyword: title
      })
    });
    const d = await res.json();
    if (!res.ok) throw new Error(d.detail || 'Lỗi khi gửi yêu cầu xuất bản.');

    closeChatPublishModal();
    renderPublishSuccessCard(d, title, postType, postStatus);
    showToast('🚀 Đã xuất bản bài viết lên website thành công!', 'success');
  } catch (err) {
    showToast(`Lỗi xuất bản: ${err.message}`, 'error');
  } finally {
    if (btn) {
      btn.disabled = false;
      btn.innerHTML = '🚀 Tiến Hành Đăng Tải';
    }
  }
}
window.executeChatPublish = executeChatPublish;

function renderPublishSuccessCard(data, title, postType, postStatus) {
  const feed = document.getElementById('chatFeed');
  if (!feed) return;
  const p = state.activeProject || {};
  const resData = data.result?.data || {};
  const permalink = resData.permalink || data.result?.post_url || p.target_website_url || '';
  const postId = resData.post_id || data.result?.post_id;
  const siteUrl = (p.cms_api_url || p.target_website_url || '').replace(/\/+$/, '');
  const editUrl = postId && siteUrl ? `${siteUrl}/wp-admin/post.php?post=${postId}&action=edit` : '';

  const row = document.createElement('div');
  row.className = 'message-row assistant publish-result-row';
  row.innerHTML = `
    <div class="avatar ai-avatar"><svg fill="currentColor" viewBox="0 0 24 24"><path d="M12 2a2 2 0 0 1 2 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 0 1 7 7h1a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v1a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-1H2a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h1a7 7 0 0 1 7-7h1V5.73c-.6-.34-1-.99-1-1.73a2 2 0 0 1 2-2M7.5 13A2.5 2.5 0 0 0 5 15.5 2.5 2.5 0 0 0 7.5 18a2.5 2.5 0 0 0 2.5-2.5A2.5 2.5 0 0 0 7.5 13m9 0a2.5 2.5 0 0 0-2.5 2.5 2.5 2.5 0 0 0 2.5 2.5 2.5 2.5 0 0 0 2.5-2.5A2.5 2.5 0 0 0 7.5 13"/></svg></div>
    <div class="bubble">
      <div style="border-left: 3px solid #10b981; padding-left: 12px; margin-bottom: 8px;">
        <h4 style="color:#34d399; margin:0 0 4px; font-size:14.5px;">🚀 ĐÃ XUẤT BẢN THÀNH CÔNG LÊN WEBSITE!</h4>
        <div style="font-size:12.5px; color:#cbd5e1;">Tiêu đề: <strong>${escapeHtml(title)}</strong></div>
      </div>
      <div class="mission-grid" style="margin:8px 0; font-size:12px;">
        <div class="mission-item"><span>Trạng thái</span><strong style="color:${postStatus === 'publish' ? '#34d399' : '#f59e0b'};">${postStatus === 'publish' ? '🟢 Công Khai' : '🟡 Bản Nháp (Draft)'}</strong></div>
        <div class="mission-item"><span>Định dạng</span><strong>${escapeHtml(postType)}</strong></div>
        <div class="mission-item"><span>IndexNow</span><strong style="color:#38bdf8;">${data.indexnow_ping ? '🟢 Đã ping Bot' : '⚪ Hàng đợi'}</strong></div>
      </div>
      <div class="chat-action-toolbar" style="margin-top:10px; display:flex; gap:8px; flex-wrap:wrap;">
        ${permalink ? `<a href="${escapeHtml(permalink)}" target="_blank" class="btn-action-tool" style="background:#2563eb; color:#fff; font-weight:500; text-decoration:none;">🔗 Xem Trên Web</a>` : ''}
        ${editUrl ? `<a href="${escapeHtml(editUrl)}" target="_blank" class="btn-action-tool" style="background:rgba(99,102,241,0.25); border-color:rgba(99,102,241,0.45); color:#c7d2fe; text-decoration:none;">✏️ Chỉnh Sửa Trong WP</a>` : ''}
        <button type="button" class="btn-action-tool" onclick="copyChatText(this)">📋 Sao Chép Nội Dung</button>
      </div>
    </div>
  `;
  const bar = row.querySelector('.chat-action-toolbar');
  if (bar) bar._rawText = window._pendingPublishContent || title;
  feed.appendChild(row);
  if (typeof scrollToBottom === 'function') scrollToBottom(true);
}

