/**
 * PORTAL CHAT PROMPT EXPANDER - SOP 37: SPECULATIVE PROMPT EXPANSION
 * Tự động tối ưu hóa và làm giàu câu lệnh ngắn thành Prompt chuyên sâu 10x.
 * Hỗ trợ phím tắt Ctrl + E hoặc click nút icon '✨'.
 * Tuân thủ SOP 23: <= 300 dòng code.
 */

function initPromptExpanderButton() {
  const inputBox = document.querySelector('.chat-input-box');
  const chatInput = document.getElementById('chatInput');
  if (!inputBox || !chatInput || document.getElementById('btnExpandPrompt')) return;

  const btn = document.createElement('button');
  btn.type = 'button';
  btn.id = 'btnExpandPrompt';
  btn.className = 'btn-expand-prompt';
  btn.title = 'Tối ưu hóa Prompt chuyên sâu (Phím tắt: Ctrl + E)';
  btn.innerHTML = `<span class="sparkle-icon">✨</span> <span class="expand-label">Tối ưu 10x</span>`;
  btn.style.cssText = `
    position: absolute;
    right: 56px;
    bottom: 12px;
    background: rgba(99, 102, 241, 0.18);
    border: 1px solid rgba(99, 102, 241, 0.4);
    color: #c7d2fe;
    font-size: 11.5px;
    font-weight: 600;
    padding: 4px 10px;
    border-radius: 6px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: all 0.2s ease;
    z-index: 5;
  `;

  btn.onmouseover = () => {
    btn.style.background = 'rgba(99, 102, 241, 0.35)';
    btn.style.color = '#ffffff';
  };
  btn.onmouseout = () => {
    btn.style.background = 'rgba(99, 102, 241, 0.18)';
    btn.style.color = '#c7d2fe';
  };

  btn.onclick = () => triggerPromptExpansion();

  // Đặt position relative cho container nếu chưa có
  inputBox.style.position = 'relative';
  inputBox.appendChild(btn);

  // Lắng nghe phím tắt Ctrl + E
  chatInput.addEventListener('keydown', (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'e') {
      e.preventDefault();
      triggerPromptExpansion();
    }
  });
}

async function triggerPromptExpansion() {
  const input = document.getElementById('chatInput');
  const btn = document.getElementById('btnExpandPrompt');
  if (!input || !state.activeProjectId) return;

  const rawText = input.value.trim();
  if (!rawText) {
    if (typeof showToast === 'function') {
      showToast('Vui lòng nhập ý tưởng hoặc câu lệnh ngắn trước khi tối ưu (ví dụ: "viết bài tivi 55 inch").', 'info');
    } else {
      alert('Vui lòng nhập câu lệnh trước!');
    }
    input.focus();
    return;
  }

  const originalBtnHtml = btn ? btn.innerHTML : '';
  if (btn) {
    btn.disabled = true;
    btn.innerHTML = `<span class="sparkle-icon">⏳</span> <span class="expand-label">Đang làm giàu...</span>`;
  }

  try {
    const res = await fetch(`/api/v1/portal/projects/${state.activeProjectId}/expand-prompt`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ prompt: rawText, target_mode: 'auto' })
    });

    if (!res.ok) throw new Error(`Lỗi máy chủ (${res.status})`);
    const data = await res.json();

    if (data.enhanced_prompt) {
      input.value = data.enhanced_prompt;
      input.style.height = 'auto';
      input.style.height = Math.min(220, input.scrollHeight) + 'px';
      input.focus();

      if (typeof showToast === 'function') {
        showToast(`✨ ${data.expansion_summary}! Nhấn Enter để gửi.`, 'success');
      }
    }
  } catch (err) {
    if (typeof showToast === 'function') {
      showToast(`Không thể tối ưu prompt: ${err.message}`, 'error');
    }
  } finally {
    if (btn) {
      btn.disabled = false;
      btn.innerHTML = originalBtnHtml;
    }
  }
}

// Tự động khởi tạo khi DOM sẵn sàng
document.addEventListener('DOMContentLoaded', () => {
  setTimeout(initPromptExpanderButton, 300);
});

