/**
 * PORTAL CHAT CANVAS - SOP 36: DUAL-PANE ARTIFACT CANVAS
 * Quản lý giao diện tách đôi màn hình cho sản phẩm bài viết Marketing/WooCommerce.
 * Tuân thủ SOP 23: <= 300 dòng code.
 */

let currentArtifact = {
  rawContent: '',
  editedContent: '',
  title: 'Bài viết chưa đặt tên',
  activeTab: 'preview',
  isFullscreen: false
};

function initArtifactCanvas() {
  const wrapper = document.getElementById('chatAppWrapper');
  if (!wrapper || document.getElementById('artifactCanvasPanel')) return;

  const panel = document.createElement('aside');
  panel.id = 'artifactCanvasPanel';
  panel.className = 'artifact-canvas-panel';
  panel.style.display = 'none';

  panel.innerHTML = `
    <div class="canvas-header">
      <div class="canvas-title-box">
        <span class="canvas-badge" id="canvasBadgeType">📦 BÀI VIẾT</span>
        <span class="canvas-title" id="canvasTitleText">Chi Tiết Bài Viết</span>
      </div>
      <div class="canvas-controls">
        <button type="button" class="canvas-tab-btn active" id="tabBtnPreview" onclick="switchCanvasTab('preview')">Xem Trước</button>
        <button type="button" class="canvas-tab-btn" id="tabBtnEdit" onclick="switchCanvasTab('edit')">Chỉnh Sửa</button>
        <button type="button" class="canvas-tab-btn" id="tabBtnRaw" onclick="switchCanvasTab('raw')">Markdown</button>
        <button type="button" class="canvas-action-btn" onclick="copyCanvasContent()" title="Sao chép nội dung">📋 Sao Chép</button>
        <button type="button" class="canvas-action-btn btn-publish" onclick="publishCanvasToWordPress()" title="Đăng lên WordPress">🚀 Xuất Bản</button>
        <button type="button" class="canvas-action-btn" onclick="toggleCanvasFullscreen()" title="Toàn màn hình" id="btnFullscreen">⛶</button>
        <button type="button" class="canvas-action-btn" onclick="closeArtifactCanvas()" title="Đóng Canvas">✕</button>
      </div>
    </div>
    <div class="canvas-body" id="canvasBodyContainer">
      <div class="canvas-preview-pane" id="canvasPreviewPane"></div>
      <textarea class="canvas-editor-textarea" id="canvasEditorTextarea" style="display:none;" oninput="onCanvasContentEdited(this.value)"></textarea>
      <pre class="canvas-editor-textarea" id="canvasRawPane" style="display:none; white-space:pre-wrap;"></pre>
    </div>
    <div class="canvas-footer">
      <div class="canvas-metrics" id="canvasMetrics">
        <span class="canvas-metric-item">Từ: <strong id="canvasWordCount">0</strong></span>
        <span class="canvas-metric-item">Ký tự: <strong id="canvasCharCount">0</strong></span>
        <span class="canvas-metric-item">Ước tính: <strong id="canvasReadTime">0 phút</strong></span>
      </div>
      <div id="canvasGuardBadgeBox">
        <span style="color:#10b981; font-weight:600;" id="canvasGuardBadge">✅ Bảo chứng thông số kho 100%</span>
      </div>
    </div>
  `;
  wrapper.appendChild(panel);
}

function extractDeliverableFromText(fullText) {
  if (!fullText) return '';
  // Tách bỏ thẻ rác DSML, function calls và monologue mở đầu nếu có
  let clean = fullText;
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
  return clean;
}

function openArtifactCanvas(content, customTitle) {
  initArtifactCanvas();
  const wrapper = document.getElementById('chatAppWrapper');
  const panel = document.getElementById('artifactCanvasPanel');
  if (!wrapper || !panel) return;

  const deliverable = extractDeliverableFromText(content);
  currentArtifact.rawContent = deliverable;
  currentArtifact.editedContent = deliverable;

  // Trích xuất tiêu đề từ H1 đầu tiên
  let extractedTitle = customTitle || '';
  if (!extractedTitle) {
    const h1Match = deliverable.match(/^#\s+(.+)$/m);
    extractedTitle = h1Match ? h1Match[1].trim() : 'Bài Viết Marketing / WooCommerce';
  }
  currentArtifact.title = extractedTitle;

  document.getElementById('canvasTitleText').innerText = extractedTitle;
  wrapper.classList.add('dual-pane-active');
  panel.style.display = 'flex';

  updateCanvasMetrics(deliverable);
  switchCanvasTab(currentArtifact.activeTab || 'preview');
  verifyCanvasCommercialFacts(deliverable);
}

async function verifyCanvasCommercialFacts(content) {
  const badgeEl = document.getElementById('canvasGuardBadge');
  if (!badgeEl || !state.activeProjectId) return;
  badgeEl.innerHTML = '<span style="color:#94a3b8;">⏳ Đang đối soát giá...</span>';
  try {
    const res = await fetch(`/api/v1/portal/projects/${state.activeProjectId}/verify-facts`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ content: content })
    });
    if (!res.ok) return;
    const data = await res.json();
    if (data.is_verified) {
      badgeEl.innerHTML = `<span style="color:#10b981; font-weight:600;" title="${data.summary}">${data.verification_badge}</span>`;
    } else {
      badgeEl.innerHTML = `<span style="color:#f59e0b; font-weight:600; cursor:pointer;" onclick="alert('${data.summary}')" title="Bấm để xem chi tiết sai lệch">${data.verification_badge}</span>`;
    }
  } catch (e) {
    badgeEl.innerHTML = `<span style="color:#94a3b8;">⚪ Chưa đối soát</span>`;
  }
}

function closeArtifactCanvas() {
  const wrapper = document.getElementById('chatAppWrapper');
  const panel = document.getElementById('artifactCanvasPanel');
  if (wrapper) wrapper.classList.remove('dual-pane-active');
  if (panel) {
    panel.style.display = 'none';
    panel.classList.remove('fullscreen');
  }
  currentArtifact.isFullscreen = false;
}

function toggleCanvasFullscreen() {
  const panel = document.getElementById('artifactCanvasPanel');
  const btn = document.getElementById('btnFullscreen');
  if (!panel) return;
  currentArtifact.isFullscreen = !currentArtifact.isFullscreen;
  if (currentArtifact.isFullscreen) {
    panel.classList.add('fullscreen');
    if (btn) btn.innerText = '🗗';
  } else {
    panel.classList.remove('fullscreen');
    if (btn) btn.innerText = '⛶';
  }
}

function switchCanvasTab(tab) {
  currentArtifact.activeTab = tab;
  const tabs = ['preview', 'edit', 'raw'];
  tabs.forEach(t => {
    const btn = document.getElementById('tabBtn' + t.charAt(0).toUpperCase() + t.slice(1));
    if (btn) btn.classList.toggle('active', t === tab);
  });

  const previewPane = document.getElementById('canvasPreviewPane');
  const editorTextarea = document.getElementById('canvasEditorTextarea');
  const rawPane = document.getElementById('canvasRawPane');

  const contentToRender = currentArtifact.editedContent || currentArtifact.rawContent;

  if (tab === 'preview') {
    previewPane.style.display = 'block';
    editorTextarea.style.display = 'none';
    rawPane.style.display = 'none';
    if (typeof renderMarkdown === 'function') {
      previewPane.innerHTML = renderMarkdown(contentToRender);
    } else {
      previewPane.innerText = contentToRender;
    }
  } else if (tab === 'edit') {
    previewPane.style.display = 'none';
    editorTextarea.style.display = 'block';
    rawPane.style.display = 'none';
    editorTextarea.value = contentToRender;
    editorTextarea.focus();
  } else if (tab === 'raw') {
    previewPane.style.display = 'none';
    editorTextarea.style.display = 'none';
    rawPane.style.display = 'block';
    rawPane.innerText = contentToRender;
  }
}

function onCanvasContentEdited(val) {
  currentArtifact.editedContent = val;
  updateCanvasMetrics(val);
}

function updateCanvasMetrics(text) {
  const wordCount = (text.match(/\S+/g) || []).length;
  const charCount = text.length;
  const readTime = Math.max(1, Math.round(wordCount / 200));

  const wcEl = document.getElementById('canvasWordCount');
  const ccEl = document.getElementById('canvasCharCount');
  const rtEl = document.getElementById('canvasReadTime');

  if (wcEl) wcEl.innerText = wordCount.toLocaleString();
  if (ccEl) ccEl.innerText = charCount.toLocaleString();
  if (rtEl) rtEl.innerText = `${readTime} phút`;
}

function copyCanvasContent() {
  const text = currentArtifact.editedContent || currentArtifact.rawContent;
  navigator.clipboard.writeText(text).then(() => {
    if (typeof showToast === 'function') {
      showToast('Đã sao chép nội dung bài viết từ Canvas vào Clipboard!', 'success');
    } else {
      alert('Đã sao chép nội dung bài viết!');
    }
  });
}

function publishCanvasToWordPress() {
  const content = currentArtifact.editedContent || currentArtifact.rawContent;
  if (!content) return;
  if (typeof triggerChatPublish === 'function') {
    triggerChatPublish(content);
  }
}

