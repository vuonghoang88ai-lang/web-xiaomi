/**
 * PORTAL CHAT ATTACHMENTS - MULTIMODAL FILE & IMAGE UPLOAD
 * SOP 23 Compliant (< 300 lines) | SOP 46 Mobile & Desktop Parity
 */

(function() {
  window.activeChatAttachments = [];

  function formatFileSize(bytes) {
    if (!bytes || bytes === 0) return '0 B';
    const k = 1024, dm = 1;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
  }

  function initChatAttachment() {
    const inputBox = document.querySelector('.chat-input-box');
    const chatInput = document.getElementById('chatInput');
    if (!inputBox || !chatInput || document.getElementById('btnAttachFile')) return;

    // 1. Tạo khay hiển thị tệp đính kèm (Attachment Tray) ngay trên input
    let tray = document.getElementById('chatAttachmentTray');
    if (!tray) {
      tray = document.createElement('div');
      tray.id = 'chatAttachmentTray';
      tray.className = 'chat-attachment-tray';
      tray.style.cssText = `
        display: none;
        flex-wrap: wrap;
        gap: 8px;
        padding: 8px 12px;
        background: rgba(15, 23, 42, 0.85);
        border: 1px solid rgba(99, 102, 241, 0.3);
        border-bottom: none;
        border-radius: 12px 12px 0 0;
        backdrop-filter: blur(8px);
        width: 100%;
        box-sizing: border-box;
      `;
      inputBox.parentNode.insertBefore(tray, inputBox);
    }

    // 2. Tạo input file ẩn
    let fileInput = document.getElementById('chatFileInput');
    if (!fileInput) {
      fileInput = document.createElement('input');
      fileInput.type = 'file';
      fileInput.id = 'chatFileInput';
      fileInput.multiple = true;
      fileInput.accept = 'image/png,image/jpeg,image/webp,image/gif,.pdf,.docx,.xlsx,.csv,.txt';
      fileInput.style.display = 'none';
      document.body.appendChild(fileInput);
      fileInput.addEventListener('change', (e) => {
        if (e.target.files && e.target.files.length) {
          handleFilesSelected(Array.from(e.target.files));
          fileInput.value = '';
        }
      });
    }

    // 3. Tạo nút kẹp giấy đính kèm (Attachment Button)
    const btnAttach = document.createElement('button');
    btnAttach.type = 'button';
    btnAttach.id = 'btnAttachFile';
    btnAttach.className = 'btn-attach-file';
    btnAttach.title = 'Đính kèm hình ảnh hoặc tài liệu (Ctrl + V để dán)';
    btnAttach.innerHTML = `
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"></path>
      </svg>
    `;
    btnAttach.style.cssText = `
      position: absolute;
      left: 12px;
      bottom: 12px;
      background: transparent;
      border: none;
      color: var(--text-dim, #94a3b8);
      padding: 6px;
      border-radius: 50%;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      transition: all 0.2s ease;
      z-index: 5;
    `;
    btnAttach.onmouseover = () => {
      btnAttach.style.color = '#818cf8';
      btnAttach.style.background = 'rgba(99, 102, 241, 0.15)';
    };
    btnAttach.onmouseout = () => {
      btnAttach.style.color = 'var(--text-dim, #94a3b8)';
      btnAttach.style.background = 'transparent';
    };
    btnAttach.onclick = () => fileInput.click();

    inputBox.style.position = 'relative';
    inputBox.appendChild(btnAttach);

    // Canh lề trái cho textarea để không bị nút đính kèm đè lên chữ
    chatInput.style.paddingLeft = '42px';

    // 4. Lắng nghe phím tắt dán ảnh từ Clipboard (Ctrl + V)
    chatInput.addEventListener('paste', (e) => {
      const items = (e.clipboardData || e.originalEvent?.clipboardData)?.items;
      if (!items) return;
      const pastedFiles = [];
      for (let i = 0; i < items.length; i++) {
        if (items[i].kind === 'file') {
          const blob = items[i].getAsFile();
          if (blob) pastedFiles.push(blob);
        }
      }
      if (pastedFiles.length > 0) {
        handleFilesSelected(pastedFiles);
      }
    });

    // 5. Hỗ trợ Kéo & Thả (Drag & Drop)
    [inputBox, document.getElementById('chatFeed')].forEach(el => {
      if (!el) return;
      el.addEventListener('dragover', (e) => { e.preventDefault(); el.style.boxShadow = '0 0 0 2px #6366f1'; });
      el.addEventListener('dragleave', () => { el.style.boxShadow = ''; });
      el.addEventListener('drop', (e) => {
        e.preventDefault();
        el.style.boxShadow = '';
        if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length) {
          handleFilesSelected(Array.from(e.dataTransfer.files));
        }
      });
    });
  }

  async function handleFilesSelected(files) {
    if (!files || !files.length) return;
    const tray = document.getElementById('chatAttachmentTray');
    if (tray) tray.style.display = 'flex';

    for (const file of files) {
      if (window.activeChatAttachments.length >= 5) {
        if (typeof showToast === 'function') showToast('Chỉ đính kèm tối đa 5 tệp mỗi lần gửi', 'warning');
        break;
      }

      const isImg = file.type.startsWith('image/');
      const tempItem = {
        name: file.name,
        size: file.size,
        mime_type: file.type,
        type: isImg ? 'image' : 'document',
        uploading: true,
        dataUrl: '',
        id: null
      };

      if (isImg) {
        const reader = new FileReader();
        reader.onload = (ev) => {
          tempItem.dataUrl = ev.target.result;
          renderAttachmentTray();
        };
        reader.readAsDataURL(file);
      }

      window.activeChatAttachments.push(tempItem);
      renderAttachmentTray();

      // Tiến hành upload ngay lên Gateway
      uploadFileToServer(file, tempItem);
    }
  }

  async function uploadFileToServer(file, itemRef) {
    const pId = state.currentProjectId || '';
    const formData = new FormData();
    formData.append('file', file);
    formData.append('user', state.currentUserEmail || 'admin@marketing-ai.internal');

    try {
      const url = pId ? `/api/v1/portal/projects/${pId}/chat-upload` : '/api/v1/portal/chat/upload';
      const resp = await fetch(url, { method: 'POST', body: formData });
      const data = await resp.json();
      if (resp.ok && data.status === 'success' && data.file) {
        itemRef.id = data.file.id;
        itemRef.preview_url = data.file.preview_url;
        itemRef.uploading = false;
      } else {
        itemRef.error = data.detail || 'Lỗi tải tệp';
        itemRef.uploading = false;
      }
    } catch (err) {
      itemRef.error = err.message || 'Lỗi mạng';
      itemRef.uploading = false;
    }
    renderAttachmentTray();
  }

  function removeAttachment(index) {
    window.activeChatAttachments.splice(index, 1);
    renderAttachmentTray();
  }

  function renderAttachmentTray() {
    const tray = document.getElementById('chatAttachmentTray');
    if (!tray) return;

    if (!window.activeChatAttachments.length) {
      tray.style.display = 'none';
      tray.innerHTML = '';
      return;
    }

    tray.style.display = 'flex';
    tray.innerHTML = window.activeChatAttachments.map((item, idx) => `
      <div style="display: flex; align-items: center; gap: 8px; background: rgba(30, 41, 59, 0.9); border: 1px solid ${item.error ? '#ef4444' : 'rgba(99, 102, 241, 0.4)'}; border-radius: 8px; padding: 4px 8px; font-size: 11.5px; color: #f1f5f9; position: relative;">
        ${item.type === 'image' && item.dataUrl ? `
          <img src="${item.dataUrl}" style="width: 32px; height: 32px; object-fit: cover; border-radius: 4px; border: 1px solid rgba(255, 255, 255, 0.1);" />
        ` : `
          <span style="font-size: 16px;">${item.type === 'image' ? '🖼️' : '📄'}</span>
        `}
        <div style="max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
          <div style="font-weight: 500; font-size: 11px;">${escapeHtml(item.name)}</div>
          <div style="font-size: 9.5px; color: ${item.error ? '#f87171' : 'var(--text-dim, #94a3b8)'};">
            ${item.uploading ? '⏳ Đang tải...' : (item.error ? '❌ Lỗi' : formatFileSize(item.size))}
          </div>
        </div>
        <button type="button" onclick="window.removeAttachmentItem(${idx})" style="background: none; border: none; color: #94a3b8; cursor: pointer; padding: 2px 4px; font-size: 12px; margin-left: 2px;" title="Xóa tệp">✕</button>
      </div>
    `).join('');
  }

  window.removeAttachmentItem = removeAttachment;
  window.getPendingAttachments = function() {
    return (window.activeChatAttachments || []).filter(a => a.id && !a.uploading);
  };
  window.isAttachmentsUploading = function() {
    return (window.activeChatAttachments || []).some(a => a.uploading);
  };
  window.clearAttachments = function() {
    window.activeChatAttachments = [];
    renderAttachmentTray();
  };

  // Helper render hình ảnh/tệp trong bong bóng chat feed
  window.renderAttachmentPillsHtml = function(files) {
    if (!files || !files.length) return '';
    return `
      <div class="chat-attachment-pills" style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 8px;">
        ${files.map(f => {
          const isImg = f.type === 'image' || (f.mime_type && f.mime_type.startsWith('image/'));
          const url = f.dataUrl || f.preview_url || f.url || '';
          if (isImg && url) {
            return `
              <a href="${escapeHtml(url)}" target="_blank" style="display: block; border-radius: 8px; overflow: hidden; border: 1px solid rgba(255, 255, 255, 0.15); max-width: 160px; max-height: 120px;">
                <img src="${escapeHtml(url)}" alt="${escapeHtml(f.name || 'Ảnh đính kèm')}" style="width: 100%; height: 100%; object-fit: cover; display: block;" />
              </a>
            `;
          }
          return `
            <div style="display: inline-flex; align-items: center; gap: 6px; background: rgba(30, 41, 59, 0.7); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 6px; padding: 4px 10px; font-size: 11.5px; color: #c7d2fe;">
              <span>📄</span>
              <span style="max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${escapeHtml(f.name || 'Tài liệu')}</span>
            </div>
          `;
        }).join('')}
      </div>
    `;
  };

  // Khởi tạo tự động khi trang load hoặc chuyển tab
  window.initChatAttachment = initChatAttachment;
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initChatAttachment);
  } else {
    setTimeout(initChatAttachment, 100);
  }
})();

