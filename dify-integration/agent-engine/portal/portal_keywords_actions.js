function filterKeywordsByCategory(cat) {
  state.selectedKeywordCategory = cat;
  state.serpMatrixPage = 1;
  renderKeywordsTab(state.keywordMatrix, state.activeProject, state.masterKeywordPool);
}
window.filterKeywordsByCategory = filterKeywordsByCategory;

async function submitNewKeywordScan() {
  const input = document.getElementById('newKeywordInput');
  const catSelect = document.getElementById('newKeywordCategory');
  const btn = document.getElementById('btnSubmitKeyword');
  if (!input) return;

  const kw = input.value.trim();
  const cat = catSelect ? catSelect.value : 'Gia dụng thông minh';
  if (!kw) {
    alert('Vui lòng nhập từ khóa cần quét SERP!');
    input.focus();
    return;
  }

  if (!state.activeProjectId) {
    alert('Vui lòng chọn dự án trước!');
    return;
  }

  const originalBtnHtml = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<span>⏳ Đang quét Google SERP...</span>';

  try {
    const res = await fetch(`/api/v1/portal/projects/${state.activeProjectId}/keywords/scan`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ keyword: kw, category: cat })
    });
    const data = await res.json();
    if (data.status === 'success') {
      input.value = '';
      state.keywordMatrix = data.keyword_matrix || [];
      state.selectedKeywordCategory = 'all';
      renderKeywordsTab(state.keywordMatrix, state.activeProject);
    } else {
      alert('Không thể quét từ khóa: ' + (data.detail || 'Lỗi không xác định'));
    }
  } catch (err) {
    alert('Lỗi kết nối khi quét SERP: ' + err.message);
  } finally {
    btn.disabled = false;
    btn.innerHTML = originalBtnHtml;
  }
}

async function rescanSingleKeyword(keyword, category) {
  if (!state.activeProjectId || !keyword) return;

  const cards = Array.from(document.querySelectorAll('.kw-matrix-card'));
  const card = cards.find(el => el.textContent.includes(keyword));
  if (card) {
    card.style.opacity = '0.5';
  }

  try {
    const res = await fetch(`/api/v1/portal/projects/${state.activeProjectId}/keywords/scan`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ keyword, category })
    });
    const data = await res.json();
    if (data.status === 'success') {
      state.keywordMatrix = data.keyword_matrix || [];
      renderKeywordsTab(state.keywordMatrix, state.activeProject);
    }
  } catch (err) {
    alert('Lỗi khi quét lại từ khóa: ' + err.message);
    if (card) card.style.opacity = '1';
  }
}

async function deleteKeyword(keywordId) {
  if (!confirm('Bạn có chắc chắn muốn xóa từ khóa này khỏi ma trận theo dõi?')) return;
  try {
    const res = await fetch(`/api/v1/portal/projects/${state.activeProjectId}/keywords/${encodeURIComponent(keywordId)}`, {
      method: 'DELETE'
    });
    const data = await res.json();
    if (data.status === 'success') {
      state.keywordMatrix = data.keyword_matrix || [];
      renderKeywordsTab(state.keywordMatrix, state.activeProject);
    }
  } catch (err) {
    alert('Lỗi xóa từ khóa: ' + err.message);
  }
}

function sendKeywordToCopilot(keyword, topDomain) {
  switchTab('chat');
  const chatInput = document.getElementById('chatInput');
  if (chatInput) {
    const website = state.activeProject?.target_website_url || 'website của chúng tôi';
    const brand = state.activeProject?.name || 'thương hiệu';
    chatInput.value = `Hãy nghiên cứu và lập dàn ý, viết một bài viết chuẩn SEO SOP 12 cho từ khóa "${keyword}". 
Mục tiêu: Đánh bại đối thủ ${topDomain || 'dẫn đầu trên Google'}, nêu bật lợi thế cạnh tranh của ${brand} (${website}) và cấu trúc heading giải quyết triệt để nỗi đau của khách hàng.`;
    chatInput.focus();
  }
}

async function refreshKeywordsTab() {
  if (!state.activeProjectId) return;
  const btn = document.querySelector('#keywordActionButtons button');
  if (btn) btn.innerHTML = '<span>⏳</span> <span>Đang Tái Tạo Ma Trận 60+ & Kho 1.200+ Từ Khóa...</span>';
  try {
    const res = await fetch(`/api/v1/portal/projects/${state.activeProjectId}/keywords/regenerate`, {
      method: 'POST'
    });
    const data = await res.json();
    if (data.status === 'success' && data.keyword_matrix) {
      state.keywordMatrix = data.keyword_matrix;
      state.masterKeywordPool = data.master_keyword_pool || [];
      renderKeywordsTab(state.keywordMatrix, state.activeProject, state.masterKeywordPool);
      if (window.toast) window.toast.success(`Đã tái tạo Ma trận ${data.count} từ khóa & Kho ${(data.master_pool_count || state.masterKeywordPool.length).toLocaleString()} từ khóa!`);
    } else {
      await loadProjectIntelligence(state.activeProjectId);
    }
  } catch (err) {
    console.error('Lỗi tái tạo ma trận từ khóa:', err);
    await loadProjectIntelligence(state.activeProjectId);
  } finally {
    if (btn) btn.innerHTML = '<span>🔄</span> <span>Làm Mới Ma Trận</span>';
  }
}

function escapeHtml(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/'/g, '&#39;')
    .replace(/"/g, '&quot;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;');
}

// ============================================================
// TAB 5: QUẢN LÝ CÀI ĐẶT & API (SERPER.DEV & AI BYOK)
// ============================================================
function filterCompetitorsByType(type) {
  const allBtns = document.querySelectorAll('.btn-filter-comp');
  allBtns.forEach(btn => {
    const isActive = btn.getAttribute('data-filter') === type;
    btn.classList.toggle('active', isActive);
    if (isActive) {
      btn.style.background = 'rgba(99, 102, 241, 0.3)';
    } else {
      btn.style.background = 'transparent';
    }
  });

  const cards = document.querySelectorAll('#competitorsGrid .comp-card');
  cards.forEach(card => {
    const cardType = card.getAttribute('data-comp-type');
    if (type === 'all' || cardType === type) {
      card.style.display = 'block';
    } else {
      card.style.display = 'none';
    }
  });
}
window.filterCompetitorsByType = filterCompetitorsByType;

function toggleSerpFields() {
  const provider = document.getElementById('settingSerpProvider')?.value;
  const box = document.getElementById('serperConfigContainer');
  if (box) {
    box.style.display = (provider === 'serper') ? 'block' : 'none';
  }
}
window.toggleSerpFields = toggleSerpFields;

function toggleAiModeFields() {
  const isCustom = document.getElementById('radioAiCustom')?.checked;
  const byokBox = document.getElementById('aiByokContainer');
  const badge = document.getElementById('aiStatusBadge');
  if (byokBox) {
    byokBox.style.display = isCustom ? 'block' : 'none';
  }
  if (badge) {
    if (isCustom) {
      badge.textContent = '⚡ BYOK Tự Cung Cấp';
      badge.style.color = '#38bdf8';
      badge.style.borderColor = 'rgba(56, 189, 248, 0.4)';
      badge.style.background = 'rgba(56, 189, 248, 0.15)';
    } else {
      badge.textContent = '💎 Hệ Thống 9Router';
      badge.style.color = '#34d399';
      badge.style.borderColor = 'rgba(16, 185, 129, 0.4)';
      badge.style.background = 'rgba(16, 185, 129, 0.15)';
    }
  }
}
window.toggleAiModeFields = toggleAiModeFields;

function toggleAuthorityFields() {
  const mode = document.getElementById('settingAuthorityMode')?.value;
  const mozContainer = document.getElementById('mozConfigContainer');
  if (mozContainer) {
    mozContainer.style.display = (mode === 'moz_pool') ? 'block' : 'none';
  }
}
window.toggleAuthorityFields = toggleAuthorityFields;

