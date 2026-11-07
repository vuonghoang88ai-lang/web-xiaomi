// ============================================================
// TAB 3: MA TRẬN TỪ KHÓA & XẾP HẠNG SERP ĐỐI THỦ (NAV & PAGINATION)
// ============================================================

function setKeywordViewMode(mode) {
  state.keywordViewMode = mode;
  state.masterPoolPage = 1;
  state.serpMatrixPage = 1;
  renderKeywordsTab(state.keywordMatrix, state.activeProject, state.masterKeywordPool);
}
window.setKeywordViewMode = setKeywordViewMode;

function filterMasterPoolBySource(src) {
  state.masterKeywordPoolFilterSource = src;
  state.masterPoolPage = 1;
  renderKeywordsTab(state.keywordMatrix, state.activeProject, state.masterKeywordPool);
}
window.filterMasterPoolBySource = filterMasterPoolBySource;

function filterMasterPoolByTier(tier) {
  state.masterKeywordPoolFilterTier = tier;
  state.masterPoolPage = 1;
  renderKeywordsTab(state.keywordMatrix, state.activeProject, state.masterKeywordPool);
}
window.filterMasterPoolByTier = filterMasterPoolByTier;

function filterMasterPoolByCat(cat) {
  state.masterKeywordPoolFilterCat = cat;
  state.masterPoolPage = 1;
  renderKeywordsTab(state.keywordMatrix, state.activeProject, state.masterKeywordPool);
}
window.filterMasterPoolByCat = filterMasterPoolByCat;

function searchMasterPool(query) {
  state.masterKeywordPoolSearchQuery = query;
  state.masterPoolPage = 1;
  renderKeywordsTab(state.keywordMatrix, state.activeProject, state.masterKeywordPool);
}
window.searchMasterPool = searchMasterPool;

function setMasterPoolPage(p) {
  state.masterPoolPage = Math.max(1, parseInt(p) || 1);
  renderKeywordsTab(state.keywordMatrix, state.activeProject, state.masterKeywordPool);
}
window.setMasterPoolPage = setMasterPoolPage;

function setMasterPoolPageSize(size) {
  state.masterPoolPageSize = Math.max(10, parseInt(size) || 50);
  state.masterPoolPage = 1;
  renderKeywordsTab(state.keywordMatrix, state.activeProject, state.masterKeywordPool);
}
window.setMasterPoolPageSize = setMasterPoolPageSize;

function changeMasterPoolPage(delta) {
  state.masterPoolPage = Math.max(1, (state.masterPoolPage || 1) + delta);
  renderKeywordsTab(state.keywordMatrix, state.activeProject, state.masterKeywordPool);
}
window.changeMasterPoolPage = changeMasterPoolPage;

function setSerpMatrixPage(p) {
  state.serpMatrixPage = Math.max(1, parseInt(p) || 1);
  renderKeywordsTab(state.keywordMatrix, state.activeProject, state.masterKeywordPool);
}
window.setSerpMatrixPage = setSerpMatrixPage;

function setSerpMatrixPageSize(size) {
  state.serpMatrixPageSize = Math.max(5, parseInt(size) || 15);
  state.serpMatrixPage = 1;
  renderKeywordsTab(state.keywordMatrix, state.activeProject, state.masterKeywordPool);
}
window.setSerpMatrixPageSize = setSerpMatrixPageSize;

function changeSerpMatrixPage(delta) {
  state.serpMatrixPage = Math.max(1, (state.serpMatrixPage || 1) + delta);
  renderKeywordsTab(state.keywordMatrix, state.activeProject, state.masterKeywordPool);
}
window.changeSerpMatrixPage = changeSerpMatrixPage;

// HÀM TẠO THANH PHÂN TRANG CÓ SỐ THỨ TỰ (NUMBERED PAGINATION COMPONENT)
function renderPaginationHtml(currentPage, totalPages, totalItems, pageSize, changePageFnName, changePageSizeFnName, sizeOptions = [15, 30, 50, 100]) {
  if (totalItems === 0) return '';
  const safePage = Math.min(Math.max(1, currentPage), totalPages || 1);

  let pageNumbers = [];
  if (totalPages <= 7) {
    for (let i = 1; i <= totalPages; i++) pageNumbers.push(i);
  } else {
    pageNumbers.push(1);
    let start = Math.max(2, safePage - 2);
    let end = Math.min(totalPages - 1, safePage + 2);

    if (start > 2) pageNumbers.push('...');
    for (let i = start; i <= end; i++) pageNumbers.push(i);
    if (end < totalPages - 1) pageNumbers.push('...');
    pageNumbers.push(totalPages);
  }

  const buttonsHtml = pageNumbers.map(p => {
    if (p === '...') {
      return `<span style="padding: 4px 6px; color: var(--text-dim); font-size: 13px; user-select: none;">...</span>`;
    }
    const isActive = p === safePage;
    return `
      <button type="button" onclick="${changePageFnName}(${p})" 
        style="min-width: 32px; height: 32px; padding: 0 8px; font-size: 12px; font-weight: ${isActive ? '700' : '500'}; 
        background: ${isActive ? 'var(--primary)' : 'rgba(30, 41, 59, 0.8)'}; 
        color: ${isActive ? '#ffffff' : '#cbd5e1'}; 
        border: 1px solid ${isActive ? 'var(--primary)' : 'rgba(148, 163, 184, 0.25)'}; 
        border-radius: 6px; cursor: pointer; transition: all 0.2s; box-shadow: ${isActive ? '0 2px 8px var(--primary-glow)' : 'none'};">
        ${p}
      </button>
    `;
  }).join('');

  const startIdx = Math.min((safePage - 1) * pageSize + 1, totalItems);
  const endIdx = Math.min(safePage * pageSize, totalItems);

  return `
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin: 16px 0; padding: 12px 16px; background: rgba(15, 23, 42, 0.65); border: 1px solid var(--border-subtle); border-radius: 8px;">
      <div style="font-size: 12px; color: var(--text-muted); display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
        <span>Hiển thị <strong>${startIdx} - ${endIdx}</strong> trong <strong>${totalItems.toLocaleString()}</strong> từ khóa</span>
        ${changePageSizeFnName ? `
          <span style="color: var(--text-dim);">|</span>
          <label style="display: flex; align-items: center; gap: 4px; color: #cbd5e1;">
            <span>Mỗi trang:</span>
            <select onchange="${changePageSizeFnName}(this.value)" style="padding: 3px 6px; background: rgba(30, 41, 59, 0.95); border: 1px solid rgba(148, 163, 184, 0.3); border-radius: 4px; color: #fff; font-size: 11.5px; cursor: pointer;">
              ${sizeOptions.map(sz => `<option value="${sz}" ${pageSize === sz ? 'selected' : ''}>${sz} từ</option>`).join('')}
            </select>
          </label>
        ` : ''}
      </div>

      <div style="display: flex; gap: 5px; align-items: center; flex-wrap: wrap;">
        <button type="button" onclick="${changePageFnName}(1)" ${safePage <= 1 ? 'disabled style="opacity: 0.35; cursor: not-allowed;"' : 'style="cursor: pointer;"'} 
          style="padding: 4px 10px; height: 32px; font-size: 11.5px; background: rgba(30, 41, 59, 0.8); border: 1px solid rgba(148, 163, 184, 0.2); border-radius: 6px; color: #cbd5e1;">
          « Đầu
        </button>
        <button type="button" onclick="${changePageFnName}(${safePage - 1})" ${safePage <= 1 ? 'disabled style="opacity: 0.35; cursor: not-allowed;"' : 'style="cursor: pointer;"'} 
          style="padding: 4px 10px; height: 32px; font-size: 11.5px; background: rgba(30, 41, 59, 0.8); border: 1px solid rgba(148, 163, 184, 0.2); border-radius: 6px; color: #cbd5e1;">
          ‹ Trước
        </button>
        
        ${buttonsHtml}

        <button type="button" onclick="${changePageFnName}(${safePage + 1})" ${safePage >= totalPages ? 'disabled style="opacity: 0.35; cursor: not-allowed;"' : 'style="cursor: pointer;"'} 
          style="padding: 4px 10px; height: 32px; font-size: 11.5px; background: rgba(30, 41, 59, 0.8); border: 1px solid rgba(148, 163, 184, 0.2); border-radius: 6px; color: #cbd5e1;">
          Sau ›
        </button>
        <button type="button" onclick="${changePageFnName}(${totalPages})" ${safePage >= totalPages ? 'disabled style="opacity: 0.35; cursor: not-allowed;"' : 'style="cursor: pointer;"'} 
          style="padding: 4px 10px; height: 32px; font-size: 11.5px; background: rgba(30, 41, 59, 0.8); border: 1px solid rgba(148, 163, 184, 0.2); border-radius: 6px; color: #cbd5e1;">
          Cuối »
        </button>
      </div>

      <div style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--text-muted);">
        <span>Trang: <strong>${safePage}/${totalPages}</strong></span>
        <span style="color: var(--text-dim);">|</span>
        <span>Đi tới:</span>
        <input type="number" min="1" max="${totalPages}" value="${safePage}" 
          onkeydown="if(event.key==='Enter'){ ${changePageFnName}(parseInt(this.value)); }"
          onchange="${changePageFnName}(parseInt(this.value))"
          style="width: 48px; padding: 4px 6px; text-align: center; background: rgba(30, 41, 59, 0.95); border: 1px solid rgba(148, 163, 184, 0.3); border-radius: 4px; color: #fff; font-size: 12px;">
      </div>
    </div>
  `;
}
