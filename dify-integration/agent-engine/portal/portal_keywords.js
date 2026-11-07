function renderKeywordsTab(matrix, project, masterKeywordPool) {
  const p = project || state.activeProject;
  if (!p || !state.activeProjectId) {
    renderEmptyState();
    return;
  }

  // Đảm bảo thanh công cụ thêm từ khóa hiển thị khi có dự án
  const kwAddBar = document.getElementById('keywordAddBar');
  if (kwAddBar) kwAddBar.style.display = 'flex';

  const kwActionBtns = document.getElementById('keywordActionButtons');
  if (kwActionBtns) kwActionBtns.style.display = 'flex';

  const kwInput = document.getElementById('newKeywordInput');
  if (kwInput) {
    kwInput.placeholder = `Nhập từ khóa mới cần phân tích cho ${p.name || 'dự án'} (VD: model, dòng sản phẩm)...`;
  }

  state.keywordMatrix = matrix || state.keywordMatrix || [];
  state.masterKeywordPool = masterKeywordPool || state.masterKeywordPool || [];
  state.keywordViewMode = state.keywordViewMode || 'serp_matrix';

  const container = document.getElementById('keywordMatrixContainer');
  const filterBar = document.getElementById('keywordCategoryFilterBar');
  if (!container) return;

  // Cập nhật dropdown danh mục động 100% theo dữ liệu của dự án này
  updateKeywordCategoryDropdown(p, state.keywordMatrix, state.activeIntelligence?.seed_analysis);

  // 1. THANH ĐIỀU HƯỚNG CHUYỂN ĐỔI CHẾ ĐỘ XEM (DUAL VIEW SWITCHER)
  const poolLen = state.masterKeywordPool.length;
  const matrixLen = state.keywordMatrix.length;

  let modeSwitcherHtml = `
    <div style="background: rgba(15, 23, 42, 0.85); border: 1px solid var(--border-subtle); border-radius: 10px; padding: 14px 18px; margin-bottom: 20px;">
      <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
          <button type="button" class="btn-primary" onclick="setKeywordViewMode('serp_matrix')" 
            style="padding: 9px 18px; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.2s;
            background: ${state.keywordViewMode === 'serp_matrix' ? 'linear-gradient(135deg, #10b981 0%, #059669 100%)' : 'rgba(30, 41, 59, 0.8)'};
            color: #fff; border: 1px solid ${state.keywordViewMode === 'serp_matrix' ? '#10b981' : 'rgba(148, 163, 184, 0.25)'};
            box-shadow: ${state.keywordViewMode === 'serp_matrix' ? '0 2px 10px rgba(16, 185, 129, 0.35)' : 'none'};">
            <span>🎯</span> 
            <span>Ma Trận SERP Trọng Điểm</span>
            <span style="background: rgba(0,0,0,0.3); padding: 2px 8px; border-radius: 12px; font-size: 11px;">${matrixLen} từ</span>
          </button>

          <button type="button" class="btn-primary" onclick="setKeywordViewMode('master_pool')" 
            style="padding: 9px 18px; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.2s;
            background: ${state.keywordViewMode === 'master_pool' ? 'linear-gradient(135deg, #6366f1 0%, #4f46e5 100%)' : 'rgba(30, 41, 59, 0.8)'};
            color: #fff; border: 1px solid ${state.keywordViewMode === 'master_pool' ? '#6366f1' : 'rgba(148, 163, 184, 0.25)'};
            box-shadow: ${state.keywordViewMode === 'master_pool' ? '0 2px 10px rgba(99, 102, 241, 0.35)' : 'none'};">
            <span>📚</span> 
            <span>Kho Từ Khóa Tổng Lực Bóc Tách</span>
            <span style="background: rgba(0,0,0,0.3); padding: 2px 8px; border-radius: 12px; font-size: 11px;">${poolLen.toLocaleString()} từ (10 đối thủ)</span>
          </button>
        </div>

        <div style="font-size: 12px; color: var(--text-muted); display: flex; align-items: center; gap: 8px;">
          <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: ${state.keywordViewMode === 'master_pool' ? '#6366f1' : '#10b981'};"></span>
          <span>Chế độ: <strong style="color: #fff;">${state.keywordViewMode === 'master_pool' ? 'Kho 1.279 Từ Khóa (Có phân trang số 1, 2, 3...)' : 'Ma Trận SERP Trọng Điểm (Có phân trang số 1, 2, 3...)'}</strong></span>
        </div>
      </div>
    </div>
  `;

  // NẾU ĐANG Ở CHẾ ĐỘ 2: KHO TỪ KHÓA TỔNG LỰC BÓC TÁCH (MASTER KEYWORD POOL)
  if (state.keywordViewMode === 'master_pool') {
    if (filterBar) filterBar.innerHTML = '';
    container.innerHTML = modeSwitcherHtml;
    const poolContainer = document.createElement('div');
    container.appendChild(poolContainer);
    renderMasterKeywordPool(poolContainer, state.masterKeywordPool);
    return;
  }

  // NẾU ĐANG Ở CHẾ ĐỘ 1: MA TRẬN SERP TRỌNG ĐIỂM
  // 2. Render Thanh Lọc Category (Category Filter Bar)
  if (filterBar) {
    const rawCategories = state.keywordMatrix.map(m => m.category).filter(Boolean);
    const uniqueCategories = ['all', ...Array.from(new Set(rawCategories))];
    filterBar.innerHTML = uniqueCategories.map(cat => {
      const count = cat === 'all' ? state.keywordMatrix.length : state.keywordMatrix.filter(m => m.category === cat).length;
      const label = cat === 'all' ? `🌐 Tất cả (${count})` : `${cat} (${count})`;
      const isActive = (state.selectedKeywordCategory || 'all') === cat;
      return `
        <button type="button" class="cat-filter-pill ${isActive ? 'active' : ''}" onclick="filterKeywordsByCategory('${cat}')">
          ${label}
        </button>
      `;
    }).join('');
  }

  // 3. Lọc dữ liệu theo Category được chọn
  const filtered = (state.selectedKeywordCategory || 'all') === 'all' 
    ? state.keywordMatrix 
    : state.keywordMatrix.filter(m => m.category === state.selectedKeywordCategory);

  if (filtered.length === 0) {
    container.innerHTML = modeSwitcherHtml + `
      <div style="text-align: center; padding: 30px; color: var(--text-muted); background: var(--bg-card); border-radius: var(--radius-md); border: 1px dashed var(--border-subtle);">
        Không có từ khóa nào thuộc danh mục "${state.selectedKeywordCategory}".
      </div>
    `;
    return;
  }

  // 4. PHÂN TRANG CÓ SỐ THỨ TỰ CHO MA TRẬN SERP
  const matrixPage = state.serpMatrixPage || 1;
  const matrixPageSize = state.serpMatrixPageSize || 15;
  const matrixTotalPages = Math.ceil(filtered.length / matrixPageSize) || 1;
  const safeMatrixPage = Math.min(matrixPage, matrixTotalPages);
  const pagedMatrixItems = filtered.slice((safeMatrixPage - 1) * matrixPageSize, safeMatrixPage * matrixPageSize);

  const topPaginationHtml = renderPaginationHtml(
    safeMatrixPage, 
    matrixTotalPages, 
    filtered.length, 
    matrixPageSize, 
    'setSerpMatrixPage', 
    'setSerpMatrixPageSize',
    [10, 15, 30, 60]
  );

  const bottomPaginationHtml = renderPaginationHtml(
    safeMatrixPage, 
    matrixTotalPages, 
    filtered.length, 
    matrixPageSize, 
    'setSerpMatrixPage', 
    'setSerpMatrixPageSize',
    [10, 15, 30, 60]
  );

  // 5. Render danh sách thẻ Ma Trận Từ Khóa & SERP
  const cardsHtml = pagedMatrixItems.map(item => {
    let intentClass = 'intent-commercial';
    let intentText = '🔍 Thương mại';
    if (item.search_intent && (item.search_intent.includes('Giao dịch') || item.search_intent.includes('Transactional'))) {
      intentClass = 'intent-transactional';
      intentText = '🛒 Giao dịch';
    } else if (item.search_intent && (item.search_intent.includes('Thông tin') || item.search_intent.includes('Informational'))) {
      intentClass = 'intent-informational';
      intentText = '💡 Thông tin';
    }

    const comps = item.top_competitors || [];
    const topDomain = comps[0]?.domain || '';

    return `
      <div class="kw-matrix-card" id="kw-card-${item.id}">
        <div class="kw-header">
          <div class="kw-title-group">
            <span class="kw-keyword-name">🔑 ${item.keyword}</span>
            <span class="kw-intent-badge ${intentClass}">${intentText}</span>
            <span class="kw-category-badge">📂 ${item.category || 'Gia dụng thông minh'}</span>
            <span class="kw-volume-badge">📊 ${item.search_volume_est || 'Ước tính SERP'}</span>
            <span style="font-size: 11px; color: var(--text-dim);">Độ cạnh tranh: <strong style="color: #cbd5e1;">${item.competition_level || 'Trung bình'}</strong></span>
          </div>
          <div style="font-size: 11.5px; color: var(--text-dim);">
            Quét lúc: ${item.last_scanned_at || 'Mới cập nhật'}
          </div>
        </div>

        <!-- DANH SÁCH TOP ĐỐI THỦ GIỮ THỨ HẠNG TỪ KHÓA NÀY -->
        <div class="kw-competitor-list">
          <div style="font-size: 11px; font-weight: 600; color: var(--text-dim); text-transform: uppercase; margin-bottom: 6px; display: flex; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 4px;">
            <span>Top Đối thủ đang giữ thứ hạng Google (100% Online)</span>
            <span>Landing Page đối thủ dùng để SEO</span>
          </div>
          ${comps.length > 0 ? comps.map(c => {
            const rankClass = c.rank === 1 ? 'rank-1' : (c.rank === 2 ? 'rank-2' : (c.rank === 3 ? 'rank-3' : 'rank-other'));
            const isBrandWarning = c.is_brand_competitor ? '<span style="color: #ef4444; font-size: 10.5px; margin-left: 6px;">🚨 Trùng Brand</span>' : '';
            const isCompAd = c.is_ad || c.competitor_type === 'paid_ads';
            const compTypeBadge = isCompAd
              ? '<span style="background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.35); font-size: 9.5px; font-weight: 700; padding: 1px 5px; border-radius: 3px;">📢 ADS</span>'
              : '<span style="background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.35); font-size: 9.5px; font-weight: 700; padding: 1px 5px; border-radius: 3px;">🌿 SEO</span>';

            return `
              <div class="kw-comp-item">
                <div style="display: flex; align-items: center; gap: 8px; min-width: 240px;">
                  <span class="kw-rank-pill ${rankClass}">#${c.rank}</span>
                  ${compTypeBadge}
                  <span style="font-weight: 600; color: #fff;">${c.domain}</span>
                  ${isBrandWarning}
                </div>
                <div style="flex: 1; overflow: hidden; padding: 0 10px;">
                  <span style="color: var(--text-muted); font-size: 12px; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                    ${c.title || c.domain}
                  </span>
                </div>
                <div style="flex-shrink: 0;">
                  <a href="${c.landing_url || c.url}" target="_blank" rel="noopener noreferrer" class="kw-comp-link" title="${c.landing_url || c.url}">
                    ${(c.landing_url || c.url) ? ((c.landing_url || c.url).length > 45 ? (c.landing_url || c.url).substring(0, 42) + '...' : (c.landing_url || c.url)) : 'Trang chủ'} ↗
                  </a>
                </div>
              </div>
            `;
          }).join('') : '<div style="color: var(--text-dim); font-size: 12px; padding: 4px 0;">Chưa phát hiện đối thủ trực tiếp cho từ khóa này.</div>'}
        </div>

        ${item.recommended_action ? `
          <div class="kw-recommendation">
            <strong>🎯 Đề xuất Content SEO:</strong> ${item.recommended_action}
          </div>
        ` : ''}

        <div class="kw-actions">
          <button type="button" class="btn-kw-action btn-kw-copilot" onclick="sendKeywordToCopilot('${escapeHtml(item.keyword)}', '${escapeHtml(topDomain)}')">
            <span>✍️ Đẩy sang Copilot Lập Bài SEO SOP 12</span>
          </button>
          <button type="button" class="btn-kw-action btn-kw-rescan" onclick="rescanSingleKeyword('${escapeHtml(item.keyword)}', '${escapeHtml(item.category || '')}')">
            <span>🔄 Quét Lại SERP Live</span>
          </button>
          <button type="button" class="btn-kw-action btn-kw-delete" onclick="deleteKeyword('${item.id || item.keyword}')" title="Xóa từ khóa khỏi ma trận">
            <span>🗑️ Xóa</span>
          </button>
        </div>
      </div>
    `;
  }).join('');

  container.innerHTML = modeSwitcherHtml + topPaginationHtml + cardsHtml + bottomPaginationHtml;
}

window.renderKeywordsTab = renderKeywordsTab;
