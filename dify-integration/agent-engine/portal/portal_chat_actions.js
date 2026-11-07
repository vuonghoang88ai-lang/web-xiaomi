// ============================================================
// TAB 1: CHAT RESPONSE ACTIONS TOOLBAR (SOP 23 compliant <= 300 lines)
// Module chuyên biệt cho các hành động sau khi sinh tin nhắn:
// Xuất bản WordPress (Hybrid Publish), Tái chế Social, Sao chép văn bản
// ============================================================

function createActionToolbar(text) {
  const tr = (k, def) => (typeof t === 'function' ? t(k) : def);
  const bar = document.createElement('div');
  bar.className = 'chat-action-toolbar';
  const isLongDeliverable = (text || '').length > 600;
  const isOnPagePlan = (text && /\b(h1|h2|heading|on-page|onpage|meta\s*description|silo|schema|thẻ|kế\s*hoạch\s*tối\s*ưu)\b/i.test(text));
  const canvasBtn = isLongDeliverable ? `
    <button type="button" class="btn-action-tool" onclick="if(typeof openArtifactCanvas==='function')openArtifactCanvas(this.closest('.chat-action-toolbar')._rawText)" style="background:rgba(99,102,241,0.22); border-color:rgba(99,102,241,0.45); color:#c7d2fe; font-weight:500;" title="Mở bài viết trên màn hình Canvas trực quan">📦 Mở Canvas</button>
  ` : '';
  const onpageBtn = isOnPagePlan ? `
    <button type="button" class="btn-action-tool" onclick="applyOnpageFromChat(this)" style="background:rgba(16,185,129,0.22); border-color:rgba(16,185,129,0.45); color:#6ee7b7; font-weight:600;" title="Tự động áp dụng Title, Meta, H1 và 5 trang E-E-A-T lên WordPress qua Omni Bridge">⚡ Tự Động Áp Dụng Heading & On-Page</button>
  ` : '';
  bar.innerHTML = `
    ${canvasBtn}
    ${onpageBtn}
    <button type="button" class="btn-action-tool publish-hybrid" onclick="publishChatArticle(this)" title="${escapeHtml(tr('actions.btn_publish_wp_title', 'Xuất bản trực tiếp lên WordPress dạng Nháp'))}">${tr('actions.btn_publish_wp', '🚀 Xuất Bản WordPress')}</button>
    <button type="button" class="btn-action-tool" onclick="recycleToSocial()" title="${escapeHtml(tr('actions.btn_recycle_social_title', 'Chuyển đổi bài viết sang kịch bản TikTok/FB'))}">${tr('actions.btn_recycle_social', '📱 Tái Chế Social')}</button>
    <button type="button" class="btn-action-tool" onclick="copyChatText(this)" title="${escapeHtml(tr('actions.btn_copy_title', 'Sao chép bài viết'))}">${tr('actions.btn_copy', '📋 Sao Chép')}</button>
  `;
  bar._rawText = text;
  return bar;
}
window.createActionToolbar = createActionToolbar;

function publishChatArticle(btn) {
  const bar = btn.closest('.chat-action-toolbar');
  const rawText = bar?._rawText || '';
  if (typeof triggerChatPublish === 'function') {
    triggerChatPublish(rawText);
  }
}
window.publishChatArticle = publishChatArticle;

function applyOnpageFromChat(btn) {
  const bar = btn.closest('.chat-action-toolbar');
  const rawText = bar?._rawText || '';
  const h1Match = rawText.match(/H1_SUGGESTED[:\s*]+["“]?([^"”\n]+)["”]?/i) ||
                  rawText.match(/H1(?:\s*\(duy\s*nhất\))?[:\s*]+["“]([^"”]+)["”]/i) ||
                  rawText.match(/thẻ\s*H1[:\s*]+["“]([^"”]+)["”]/i);
  const customSeo = h1Match ? { homepage_h1: h1Match[1].trim() } : null;
  if (typeof autoFixOnpageViaBridge === 'function') {
    autoFixOnpageViaBridge(customSeo);
  }
}
window.applyOnpageFromChat = applyOnpageFromChat;

function recycleToSocial() {
  const input = document.getElementById('chatInput');
  if (input && !state.isStreaming) {
    const isEn = typeof getCurrentLang === 'function' && getCurrentLang() === 'en';
    input.value = isEn ?
      'Recycle the above article into 1 60s TikTok video script and 3 high-converting Facebook posts.' :
      'Tái chế bài viết trên thành 1 kịch bản video TikTok 60s và 3 bài đăng Fanpage Facebook.';
    sendMessage();
  }
}
window.recycleToSocial = recycleToSocial;

function copyChatText(btn) {
  const tr = (k, def) => (typeof t === 'function' ? t(k) : def);
  const bar = btn.closest('.chat-action-toolbar');
  let text = bar?._rawText;

  // Fallback an toàn: nếu không có _rawText, bóc tách text từ thẻ bubble cha
  if (!text) {
    const bubble = btn.closest('.bubble');
    if (bubble) {
      const clone = bubble.cloneNode(true);
      clone.querySelectorAll('.chat-action-toolbar, .thought-container, .boardroom-card').forEach(el => el.remove());
      text = clone.innerText.trim();
    }
  }

  if (!text) {
    showToast('Không có nội dung để sao chép', 'warning');
    return;
  }

  navigator.clipboard.writeText(text).then(() => {
    const origHtml = btn.innerHTML;
    btn.innerHTML = '✅ ' + tr('chat.copied', 'Đã chép!');
    showToast(tr('toasts.copied', 'Đã sao chép vào bộ nhớ tạm!'), 'success');
    setTimeout(() => { btn.innerHTML = origHtml; }, 2000);
  }).catch(() => {
    showToast('Lỗi khi truy cập clipboard trình duyệt', 'error');
  });
}
window.copyChatText = copyChatText;
