/**
 * Portal Keywords Master Pool Renderer
 * BẢNG KHO TỪ KHÓA TỔNG LỰC BÓC TÁCH TỪ 10 ĐỐI THỦ
 * SOP 23 Decoupled Module (< 300 lines)
 */

function renderMasterKeywordPool(container, pool) {
  if (!pool || pool.length === 0) {
    container.innerHTML = `
      <div style="text-align: center; padding: 40px; color: var(--text-muted); background: var(--bg-card); border-radius: var(--radius-md); border: 1px dashed var(--border-subtle);">
        <div style="font-size: 32px; margin-bottom: 12px;">📚</div>
        <h4 style="color: var(--text-main); margin-bottom: 6px;">Kho Từ Khóa Đang Chờ Đồng Bộ</h4>
        <p style="font-size: 13px;">Hãy bấm nút <strong>"Làm Mới Ma Trận"</strong> ở góc trên để AI bóc tách toàn bộ từ khóa từ 10 đối thủ SERP.</p>
      </div>
    `;
    return;
  }

  // Tách danh sách nguồn đối thủ duy nhất
  const sourceSet = new Set();
  pool.forEach(it => (it.found_in || []).forEach(s => sourceSet.add(s)));
  const uniqueSources = ['all', ...Array.from(sourceSet).sort()];

  const uniqueCats = ['all', 'Tivi Xiaomi', 'Tủ Lạnh Xiaomi', 'Máy Giặt & Máy Sấy', 'Điều Hòa & Máy Lạnh', 'Gia Dụng & Smarthome', 'Ý Định Mua Hàng & Giá Kho', 'Sản Phẩm Trọng Tâm'];
  const uniqueTiers = ['all', 'Tầng 1: Ngành hàng cốt lõi (Head)', 'Tầng 2: Landing Pages ngách (Specs)', 'Tầng 3: Dòng Model & Công nghệ', 'Tầng 4: Ý định mua hàng (Intent)'];

  const currentSrc = state.masterKeywordPoolFilterSource || 'all';
  const currentCat = state.masterKeywordPoolFilterCat || 'all';
  const currentTier = state.masterKeywordPoolFilterTier || 'all';
  const currentQuery = (state.masterKeywordPoolSearchQuery || '').trim().toLowerCase();

  let filtered = pool.filter(it => {
    if (currentSrc !== 'all' && !(it.found_in || []).includes(currentSrc)) return false;
    if (currentCat !== 'all' && it.category !== currentCat) return false;
    if (currentTier !== 'all' && it.tier !== currentTier) return false;
    if (currentQuery && !it.keyword.toLowerCase().includes(currentQuery)) return false;
    return true;
  });

  const page = state.masterPoolPage || 1;
  const pageSize = state.masterPoolPageSize || 50;
  const totalPages = Math.ceil(filtered.length / pageSize) || 1;
  const safePage = Math.min(page, totalPages);
  const pagedItems = filtered.slice((safePage - 1) * pageSize, safePage * pageSize);

  const topPaginationHtml = renderPaginationHtml(
    safePage, 
    totalPages, 
    filtered.length, 
    pageSize, 
    'setMasterPoolPage', 
    'setMasterPoolPageSize',
    [25, 50, 100, 200]
  );

  const bottomPaginationHtml = renderPaginationHtml(
    safePage, 
    totalPages, 
    filtered.length, 
    pageSize, 
    'setMasterPoolPage', 
    'setMasterPoolPageSize',
    [25, 50, 100, 200]
  );

  container.innerHTML = `
    <div style="background: rgba(15, 23, 42, 0.75); border: 1px solid var(--border-subtle); border-radius: 10px; padding: 18px; margin-bottom: 20px;">
      <!-- BỘ LỌC 4 CHIỀU CỦA KHO TỪ KHÓA -->
      <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center; justify-content: space-between; margin-bottom: 14px;">
        <div style="flex: 1; min-width: 240px;">
          <input type="text" id="masterPoolSearchInput" value="${escapeHtml(state.masterKeywordPoolSearchQuery || '')}" oninput="searchMasterPool(this.value)" placeholder="🔍 Tìm nhanh trong ${pool.length.toLocaleString()} từ khóa đối thủ..." style="width: 100%; padding: 8px 12px; background: rgba(30, 41, 59, 0.8); border: 1px solid rgba(99, 102, 241, 0.3); border-radius: 6px; color: #fff; font-size: 13px;">
        </div>
        <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
          <select onchange="filterMasterPoolBySource(this.value)" style="padding: 7px 10px; background: rgba(30, 41, 59, 0.8); border: 1px solid rgba(99, 102, 241, 0.3); border-radius: 6px; color: #cbd5e1; font-size: 12px; cursor: pointer;">
            <option value="all">🌐 Tất cả đối thủ (${pool.length.toLocaleString()} từ)</option>
            ${uniqueSources.filter(s => s !== 'all').map(s => `
              <option value="${escapeHtml(s)}" ${currentSrc === s ? 'selected' : ''}>${escapeHtml(s)}</option>
            `).join('')}
          </select>

          <select onchange="filterMasterPoolByCat(this.value)" style="padding: 7px 10px; background: rgba(30, 41, 59, 0.8); border: 1px solid rgba(99, 102, 241, 0.3); border-radius: 6px; color: #cbd5e1; font-size: 12px; cursor: pointer;">
            <option value="all">📁 Tất cả Silo</option>
            ${uniqueCats.filter(c => c !== 'all').map(c => `
              <option value="${escapeHtml(c)}" ${currentCat === c ? 'selected' : ''}>${escapeHtml(c)}</option>
            `).join('')}
          </select>

          <select onchange="filterMasterPoolByTier(this.value)" style="padding: 7px 10px; background: rgba(30, 41, 59, 0.8); border: 1px solid rgba(99, 102, 241, 0.3); border-radius: 6px; color: #cbd5e1; font-size: 12px; cursor: pointer;">
            <option value="all">🎯 Tất cả 4 Tầng</option>
            ${uniqueTiers.filter(t => t !== 'all').map(t => `
              <option value="${escapeHtml(t)}" ${currentTier === t ? 'selected' : ''}>${escapeHtml(t)}</option>
            `).join('')}
          </select>
        </div>
      </div>

      <!-- THANH PHÂN TRANG PHÍA TRÊN -->
      ${topPaginationHtml}

      <!-- BẢNG KHO TỪ KHÓA TỔNG LỰC -->
      <div style="overflow-x: auto; margin-top: 10px;">
        <table style="width: 100%; border-collapse: collapse; font-size: 12.5px; color: #cbd5e1;">
          <thead>
            <tr style="background: rgba(30, 41, 59, 0.9); border-bottom: 1px solid rgba(99, 102, 241, 0.3); color: #94a3b8; font-size: 11.5px;">
              <th style="padding: 8px 10px; text-align: center; width: 45px;">#</th>
              <th style="padding: 8px 10px; text-align: left;">Từ Khóa Đối Thủ Đang SEO</th>
              <th style="padding: 8px 10px; text-align: left;">Silo Ngành</th>
              <th style="padding: 8px 10px; text-align: left;">Tầng Chiến Lược</th>
              <th style="padding: 8px 10px; text-align: left;">Ý Định (Intent)</th>
              <th style="padding: 8px 10px; text-align: left;">Đối Thủ Sở Hữu</th>
              <th style="padding: 8px 10px; text-align: center;">Độ Cạnh Tranh</th>
              <th style="padding: 8px 10px; text-align: center; width: 140px;">Thao Tác</th>
            </tr>
          </thead>
          <tbody>
            ${pagedItems.map((item, idx) => {
              const globalIdx = (safePage - 1) * pageSize + idx + 1;
              const sources = item.found_in || [];
              const compCount = item.competitor_count || sources.length;
              const heatBadge = compCount >= 4 
                ? `<span style="background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.35); padding: 2px 6px; border-radius: 4px; font-size: 10.5px; font-weight: 700;">🔥 ${compCount} đối thủ</span>`
                : (compCount >= 2 
                  ? `<span style="background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); padding: 2px 6px; border-radius: 4px; font-size: 10.5px; font-weight: 600;">⚡ ${compCount} đối thủ</span>`
                  : `<span style="background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); padding: 2px 6px; border-radius: 4px; font-size: 10.5px;">✓ Ngách</span>`);

              let intentBadge = `<span style="color: #60a5fa;">💡 Thông tin</span>`;
              if (item.search_intent && item.search_intent.includes('Giao dịch')) intentBadge = `<span style="color: #34d399; font-weight: 600;">🛒 Giao dịch</span>`;
              else if (item.search_intent && item.search_intent.includes('Thương mại')) intentBadge = `<span style="color: #fbbf24;">🔍 Thương mại</span>`;

              return `
                <tr style="border-bottom: 1px solid rgba(148, 163, 184, 0.08);">
                  <td style="padding: 8px 10px; text-align: center; color: var(--text-dim);">${globalIdx}</td>
                  <td style="padding: 8px 10px; font-weight: 600; color: #f8fafc;">
                    🔑 ${escapeHtml(item.keyword)}
                  </td>
                  <td style="padding: 8px 10px;">
                    <span style="font-size: 11px; padding: 2px 6px; background: rgba(99, 102, 241, 0.15); border: 1px solid rgba(99, 102, 241, 0.25); border-radius: 4px; color: #c7d2fe;">
                      ${escapeHtml(item.category)}
                    </span>
                  </td>
                  <td style="padding: 8px 10px; font-size: 11.5px; color: #94a3b8;">
                    ${escapeHtml(item.tier)}
                  </td>
                  <td style="padding: 8px 10px; font-size: 11.5px;">
                    ${intentBadge}
                  </td>
                  <td style="padding: 8px 10px;">
                    <div style="display: flex; flex-wrap: wrap; gap: 4px; max-width: 260px;">
                      ${sources.slice(0, 3).map(s => `
                        <span style="font-size: 10px; padding: 1px 5px; background: rgba(30, 41, 59, 0.8); border: 1px solid rgba(148, 163, 184, 0.2); border-radius: 3px; color: #cbd5e1;">
                          ${escapeHtml(s)}
                        </span>
                      `).join('')}
                      ${sources.length > 3 ? `<span style="font-size: 10px; color: #93c5fd;">+${sources.length - 3}</span>` : ''}
                    </div>
                  </td>
                  <td style="padding: 8px 10px; text-align: center;">
                    ${heatBadge}
                  </td>
                  <td style="padding: 8px 10px; text-align: center;">
                    <div style="display: flex; gap: 4px; justify-content: center;">
                      <button type="button" class="btn-primary" onclick="rescanSingleKeyword('${escapeHtml(item.keyword)}', '${escapeHtml(item.category)}')" title="Đưa vào Ma Trận & Quét SERP Live" style="font-size: 11px; padding: 3px 8px; background: rgba(59, 130, 246, 0.2); border: 1px solid #3b82f6; border-radius: 4px; color: #93c5fd; cursor: pointer;">
                        🔍 Quét
                      </button>
                      <button type="button" class="btn-primary" onclick="sendKeywordToCopilot('${escapeHtml(item.keyword)}', '${escapeHtml(sources[0] || 'Google Top 1')}')" title="Lập dàn ý & Viết bài SEO SOP 12" style="font-size: 11px; padding: 3px 8px; background: rgba(99, 102, 241, 0.25); border: 1px solid var(--primary); border-radius: 4px; color: #c7d2fe; cursor: pointer;">
                        ✍️ Viết
                      </button>
                    </div>
                  </td>
                </tr>
              `;
            }).join('')}
          </tbody>
        </table>
      </div>

      <!-- THANH PHÂN TRANG PHÍA DƯỚI -->
      ${bottomPaginationHtml}
    </div>
  `;
}
window.renderMasterKeywordPool = renderMasterKeywordPool;
