/**
 * Portal My Site Keyword Clusters & Gaps Renderer
 * SOP 23 Decoupled Module (< 300 lines)
 * SOP 30 & SOP 49 Archetype-Aware Adaptation
 */

function cleanKeywordToken(text) {
  if (!text) return "";
  let clean = text.replace(/\s+/g, ' ').trim();
  // Khử lặp cụm từ (e.g. 'Truyện 360 Truyen360' -> 'Truyện 360')
  clean = clean.replace(/\b(\w+(?:\s+\w+){0,2})\s+\1\b/gi, '$1');
  const tokens = clean.split(' ');
  const flat = tokens.map(w => w.replace(/[^a-zA-Z0-9]/g, '').toLowerCase());
  const keep = [];
  for (let i = 0; i < tokens.length; i++) {
    const t = flat[i];
    let isDupe = false;
    for (let j = 0; j < i; j++) {
      if (flat[j] === t && t.length >= 3) { isDupe = true; break; }
    }
    if (!isDupe) keep.push(tokens[i]);
  }
  return keep.join(' ').replace(/\s+/g, ' ').trim();
}

function renderMySiteKeywordGapsHtml(project, seedAnalysis, audit) {
  let bp = project ? project.brand_profile : {};
  if (typeof bp === 'string') {
    try { bp = JSON.parse(bp); } catch (e) { bp = {}; }
  }
  let arch = (bp && bp.archetype) || (project && project.archetype);
  if (!arch) {
    const corpus = `${project ? (project.business_description || '') + ' ' + (project.name || '') + ' ' + (project.target_website_url || '') : ''}`.toLowerCase();
    if (/truyen|truyện|tiểu thuyết|novel|manga|doc online|đọc online|xianxia|chuong|chương/.test(corpus)) {
      arch = 'content_media';
    } else if (/phòng khám|nha khoa|bác sĩ|chữa|thẩm mỹ|tư vấn|luật|đào tạo|dịch vụ|clinic|agency/.test(corpus)) {
      arch = 'service_health_b2b';
    } else {
      arch = 'commerce_retail';
    }
  }
  const isContent = arch === 'content_media';
  const isB2B = arch === 'service_health_b2b';

  const tAudit = state.targetAnalysis || (bp && (bp.target_analysis || bp.my_site_audit)) || audit || {};
  const tClusters = tAudit.keyword_clusters || {};
  const tHeads = (tClusters.head || []).map(cleanKeywordToken).filter(Boolean);
  const tSubcats = (tClusters.subcategories || []).map(cleanKeywordToken).filter(Boolean);
  const tSecs = (tClusters.secondary || []).map(cleanKeywordToken).filter(Boolean);
  const tLongs = (tClusters.longtail || []).map(cleanKeywordToken).filter(Boolean);
  const tTotal = tAudit.total_keywords_count || (tHeads.length + tSubcats.length + tSecs.length + tLongs.length);

  const sClusters = (seedAnalysis && seedAnalysis.keyword_clusters) || {};
  const sHeads = (sClusters.head || []).map(cleanKeywordToken).filter(Boolean);
  const sSubcats = (sClusters.subcategories || []).map(cleanKeywordToken).filter(Boolean);

  // Áp dụng Thuật toán 1: Lọc sạch rác kỹ thuật & spec entropy khỏi Keyword Gaps
  const cleanSHeads = sHeads.filter(sh => typeof isSpecificationJunk === 'function' ? !isSpecificationJunk(sh) : true);
  const cleanSSubcats = sSubcats.filter(ss => typeof isSpecificationJunk === 'function' ? !isSpecificationJunk(ss) : true);

  // Tính toán khoảng trống từ khóa
  const missingHeads = cleanSHeads.filter(sh => !tHeads.some(th => th.toLowerCase() === sh.toLowerCase()));
  const missingSubcats = cleanSSubcats.filter(ss => !tSubcats.some(ts => ts.toLowerCase() === ss.toLowerCase()));

  // Tiêu đề 4 tầng thích ứng theo Archetype (SOP 30 / SOP 49)
  const t1Label = isContent 
    ? `📌 1. Thể Loại Cốt Lõi & Trụ Cột Đề Mục (Head Genres - ${tHeads.length} thể loại):`
    : (isB2B ? `📌 1. Dịch Vụ Cốt Lõi & Giải Pháp (Head Services - ${tHeads.length} dịch vụ):` : `📌 1. Ngành Hàng Cốt Lõi (Head Keywords - ${tHeads.length} ngành):`);

  const t2Label = isContent
    ? `📂 2. Tuyển Tập / Danh Mục Phân Khúc (Subcategories - ${tSubcats.length} phân khúc):`
    : `📂 2. Landing Pages Phân Khúc / Kích Thước / Kiểu Dáng (Subcategories - ${tSubcats.length} phân khúc):`;

  const t3Label = isContent
    ? `🏷️ 3. Dòng Thể Loại & Tuyển Tập Thịnh Hành (Trending Collections - ${tSecs.length} dòng):`
    : (isB2B ? `🏷️ 3. Gói Dịch Vụ & Liệu Trình Chuyên Sâu (Specialized Specs - ${tSecs.length} gói):` : `🏷️ 3. Dòng Model Sản Phẩm Đã Nhận Diện (Secondary Specs - ${tSecs.length} model):`);

  const t4Label = isContent
    ? `📖 4. Từ Khóa Ý Định Độc Giả & Khám Phá Truyện (Reading Intent - ${tLongs.length} từ khóa):`
    : (isB2B ? `💡 4. Từ Khóa Ý Định Tư Vấn & Đặt Lịch (Consulting Intent - ${tLongs.length} từ khóa):` : `🛒 4. Từ Khóa Ý Định Mua Hàng & Giá Kho (Long-tail Intent - ${tLongs.length} từ khóa):`);

  return `
    <div class="card" style="background: rgba(15, 23, 42, 0.7); border: 1px solid var(--border-subtle); border-radius: 10px; padding: 18px; margin-bottom: 22px;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
        <div style="font-size: 13.5px; font-weight: 600; color: #e2e8f0; display: flex; align-items: center; gap: 8px;">
          <span>🎯</span> Hệ Thống Từ Khóa Đa Tầng Của Website Đích (${tTotal} từ khóa bóc tách được)
        </div>
        <span style="font-size: 11px; font-weight: 600; color: #38bdf8; background: rgba(14, 165, 233, 0.15); padding: 3px 8px; border-radius: 4px; border: 1px solid rgba(14, 165, 233, 0.3);">
          4 Tầng Chiến Lược
        </span>
      </div>

      ${tHeads.length > 0 ? `
        <div style="margin-bottom: 10px;">
          <div style="font-size: 12px; font-weight: 600; color: #818cf8; margin-bottom: 6px;">${t1Label}</div>
          <div style="display: flex; flex-wrap: wrap; gap: 6px;">
            ${tHeads.map(kw => `<span style="font-size: 11.5px; background: rgba(99, 102, 241, 0.2); color: #c7d2fe; border: 1px solid rgba(99, 102, 241, 0.35); padding: 3px 9px; border-radius: 4px;">🔑 ${escapeHtml(kw)}</span>`).join('')}
          </div>
        </div>
      ` : ''}

      ${tSubcats.length > 0 ? `
        <div style="margin-bottom: 10px;">
          <div style="font-size: 12px; font-weight: 600; color: #34d399; margin-bottom: 6px;">${t2Label}</div>
          <div style="display: flex; flex-wrap: wrap; gap: 6px;">
            ${tSubcats.map(kw => `<span style="font-size: 11.5px; background: rgba(16, 185, 129, 0.15); color: #6ee7b7; border: 1px solid rgba(16, 185, 129, 0.3); padding: 3px 9px; border-radius: 4px;">📂 ${escapeHtml(kw)}</span>`).join('')}
          </div>
        </div>
      ` : ''}

      ${tSecs.length > 0 ? `
        <div style="margin-bottom: 10px;">
          <div style="font-size: 12px; font-weight: 600; color: #38bdf8; margin-bottom: 6px;">${t3Label}</div>
          <div style="display: flex; flex-wrap: wrap; gap: 6px;">
            ${tSecs.map(kw => `<span style="font-size: 11px; background: rgba(14, 165, 233, 0.15); color: #7dd3fc; border: 1px solid rgba(56, 189, 248, 0.3); padding: 3px 8px; border-radius: 4px;">🏷️ ${escapeHtml(kw)}</span>`).join('')}
          </div>
        </div>
      ` : ''}

      ${tLongs.length > 0 ? `
        <div style="margin-bottom: 12px;">
          <div style="font-size: 12px; font-weight: 600; color: #fbbf24; margin-bottom: 6px;">${t4Label}</div>
          <div style="display: flex; flex-wrap: wrap; gap: 6px;">
            ${tLongs.map(kw => `<span style="font-size: 11px; background: rgba(245, 158, 11, 0.15); color: #fde68a; border: 1px solid rgba(245, 158, 11, 0.3); padding: 3px 8px; border-radius: 4px;">💡 ${escapeHtml(kw)}</span>`).join('')}
          </div>
        </div>
      ` : ''}

      ${(missingHeads.length > 0 || missingSubcats.length > 0) ? `
        <div style="margin-top: 14px; padding: 12px; background: rgba(239, 68, 68, 0.08); border: 1px solid rgba(239, 68, 68, 0.25); border-radius: 6px;">
          <div style="font-size: 12px; font-weight: 700; color: #f87171; margin-bottom: 6px; display: flex; align-items: center; gap: 5px;">
            <span>🚨</span> KHOẢNG TRỐNG TỪ KHÓA (KEYWORD GAPS): Đối thủ mẫu đang phủ mạnh mà Web Đích chưa có
          </div>
          <div style="display: flex; flex-wrap: wrap; gap: 5px;">
            ${missingHeads.map(kw => `<span style="font-size: 11px; background: rgba(239, 68, 68, 0.15); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.3); padding: 2px 7px; border-radius: 4px;">⚠️ Thiếu ngành: ${escapeHtml(kw)}</span>`).join('')}
            ${missingSubcats.slice(0, 5).map(kw => `<span style="font-size: 10.5px; background: rgba(239, 68, 68, 0.12); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.25); padding: 2px 7px; border-radius: 4px;">⚠️ Thiếu phân khúc: ${escapeHtml(kw)}</span>`).join('')}
          </div>
          <div style="font-size: 11px; color: #cbd5e1; margin-top: 6px;">
            👉 <strong>Hành động đề xuất:</strong> Tạo chuyên mục và trang đích ngách tương ứng theo SOP 21 & SOP 12 để chặn đứng lưu lượng tìm kiếm của đối thủ.
          </div>
        </div>
      ` : ''}
    </div>
  `;
}
window.renderMySiteKeywordGapsHtml = renderMySiteKeywordGapsHtml;
