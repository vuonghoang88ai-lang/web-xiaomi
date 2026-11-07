/**
 * Portal My Site Tab Renderer
 * Báo Cáo Phân Tích Chi Tiết Website Cần Phát Triển (On-Page SEO Audit)
 * SOP 23 Compliant (< 300 lines)
 */

function renderMySiteTab(mySiteAudit, project, seedAnalysis) {
  const container = document.getElementById('mySiteContent');
  if (!container) return;

  try {
    if (!project) {
      container.innerHTML = `
        <div class="card" style="padding: 40px; text-align: center; color: var(--text-muted); background: var(--bg-card); border-radius: var(--radius-md);">
          <div style="font-size: 32px; margin-bottom: 12px;">📂</div>
          <p style="font-size: 14px;">Vui lòng chọn hoặc tạo dự án để xem Báo cáo phân tích chi tiết website cần phát triển.</p>
        </div>
      `;
      return;
    }

    const targetUrl = project.target_website_url || '';
    const audit = mySiteAudit || {};
    
    // Nếu chưa có dữ liệu audit từ trước, hiển thị thông báo thân thiện và nút quét ngay
    if (!audit || Object.keys(audit).length === 0 || (!audit.title && !audit.http_status && !audit.status)) {
      container.innerHTML = `
        <div class="card" style="padding: 36px 24px; text-align: center; background: rgba(30, 41, 59, 0.6); border: 1px dashed rgba(99, 102, 241, 0.4); border-radius: 12px; margin-top: 10px;">
          <div style="font-size: 40px; margin-bottom: 12px;">🔍</div>
          <h4 style="font-size: 16px; color: #f8fafc; margin-bottom: 8px;">Website chưa có Báo cáo On-page</h4>
          <p style="font-size: 13px; color: var(--text-muted); max-width: 520px; margin: 0 auto 20px; line-height: 1.6;">
            Website <strong>${escapeHtml(targetUrl)}</strong> chưa được trinh sát On-page hoặc dữ liệu đang chờ đồng bộ. Hãy bấm nút bên dưới để AI tiến hành trinh sát trực tiếp ngay bây giờ.
          </p>
          <button type="button" class="btn-primary" onclick="rescanMySite()" style="font-size: 13px; padding: 10px 22px; display: inline-flex; align-items: center; gap: 8px;">
            <span>🔄</span> Quét & Phân Tích On-page Ngay
          </button>
        </div>
      `;
      return;
    }

    const isOnline = audit.status === 'online' || (audit.http_status && audit.http_status >= 200 && audit.http_status < 400);
    const httpCode = audit.http_status || (isOnline ? 200 : 'N/A');
    const title = (audit.title || '').trim();
    const metaDesc = (audit.meta_description || '').trim();
    const h1List = Array.isArray(audit.h1) ? audit.h1 : (audit.h1 ? [audit.h1] : []);
    const h2List = Array.isArray(audit.h2) ? audit.h2 : (audit.h2 ? [audit.h2] : []);
    const navList = Array.isArray(audit.key_services_or_nav) ? audit.key_services_or_nav : [];
    const hotlines = Array.isArray(audit.hotlines) ? audit.hotlines : [];
    const logoUrl = audit.logo_url || project.brand_logo_url || '';

    // Tính điểm sức khỏe On-page (0 - 100)
    let healthScore = 0;
    if (isOnline) healthScore += 20;
    if (title) {
      healthScore += (title.length >= 30 && title.length <= 65) ? 25 : 15;
    }
    if (metaDesc) {
      healthScore += (metaDesc.length >= 100 && metaDesc.length <= 165) ? 25 : 15;
    }
    if (h1List.length === 1) {
      healthScore += 15;
    } else if (h1List.length > 1) {
      healthScore += 5;
    }
    if (h2List.length >= 2) healthScore += 15;

    let scoreColor = '#ef4444';
    let scoreBadge = 'Cần tối ưu cấp bách';
    if (healthScore >= 80) {
      scoreColor = '#10b981';
      scoreBadge = 'Tối ưu Tốt';
    } else if (healthScore >= 50) {
      scoreColor = '#f59e0b';
      scoreBadge = 'Mức Trung bình';
    }

    // Đối chiếu cấu trúc Danh mục Menu của Đối thủ
    let bp = project.brand_profile || {};
    if (typeof bp === 'string') { try { bp = JSON.parse(bp); } catch(e) { bp = {}; } }
    let rawSeedCats = (seedAnalysis && (seedAnalysis.categories || seedAnalysis.category_structure)) || (bp.seed_analysis && (bp.seed_analysis.categories || bp.seed_analysis.category_structure)) || [];
    if (!rawSeedCats.length && Array.isArray(state.competitors) && state.competitors.length) {
      const cSet = new Set();
      state.competitors.forEach(c => {
        const ss = c.scraped_structure || {};
        (ss.nav_categories || ss.categories || ss.key_services_or_nav || []).forEach(k => {
          if (k && k.length >= 3 && k.length <= 35) cSet.add(k);
        });
      });
      if (cSet.size) rawSeedCats = Array.from(cSet);
    }
    // Áp dụng Thuật toán 1 (Lọc rác phi sản phẩm) & Thuật toán 2 (Xếp hạng tương đồng Anchor)
    const nonJunk = rawSeedCats.filter(c => typeof isSpecificationJunk === 'function' ? !isSpecificationJunk(c) : true);
    const anchorText = `${project.business_description || ''} ${navList.join(' ')}`;
    let seedCategories = typeof rankCategoriesByAnchor === 'function' ? rankCategoriesByAnchor(nonJunk, anchorText) : nonJunk;

    const dynamicTopics = seedCategories.length > 0 
      ? seedCategories.slice(0, 4).join(', ') 
      : (navList.length > 0 ? navList.slice(0, 4).join(', ') : 'Chuyên mục cốt lõi & Dịch vụ trọng tâm');
    const eeatLocked = !!bp.eeat_locked;
    const brandNameDisplay = typeof resolveBrandEntity === 'function'
      ? resolveBrandEntity(project, bp, audit)
      : (project.brand_name || bp.brand_name || (project.name ? project.name.split('.')[0].toUpperCase() : 'Chưa đặt'));

    container.innerHTML = `
      <!-- TOP BAR THÔNG TIN KHẢO SÁT -->
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
        <div style="font-size: 13px; color: var(--text-muted);">
          Báo cáo khảo sát thực tế URL: <a href="${escapeHtml(targetUrl)}" target="_blank" style="color: var(--primary); text-decoration: none; font-weight: 600;">${escapeHtml(targetUrl)} ↗</a>
        </div>
        <div style="font-size: 11.5px; color: #34d399; background: rgba(16, 185, 129, 0.12); padding: 4px 10px; border-radius: 20px; border: 1px solid rgba(16, 185, 129, 0.25);">
          ✓ Kết nối Omni Bridge Trực Tiếp 100%
        </div>
      </div>

      <!-- 1. THẺ TỔNG QUAN CHỈ SỐ SỨC KHỎE ON-PAGE -->
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; margin-bottom: 20px;">
        <div class="card" style="background: var(--bg-card); border: 1px solid var(--border-subtle); border-radius: 10px; padding: 16px; display: flex; align-items: center; gap: 14px;">
          <div style="width: 54px; height: 54px; border-radius: 50%; background: rgba(99, 102, 241, 0.1); border: 3px solid ${scoreColor}; display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: 700; color: ${scoreColor}; flex-shrink: 0;">
            ${healthScore}
          </div>
          <div>
            <div style="font-size: 11px; color: var(--text-dim); text-transform: uppercase; font-weight: 600;">Điểm Sức Khỏe On-page</div>
            <div style="font-size: 13.5px; font-weight: 600; color: #fff; margin-top: 2px;">${scoreBadge}</div>
            <div style="font-size: 11px; color: var(--text-muted); margin-top: 2px;">HTTP Status: <strong style="color: ${isOnline ? '#10b981' : '#ef4444'};">${httpCode}</strong></div>
          </div>
        </div>

        <div class="card" style="background: var(--bg-card); border: 1px solid var(--border-subtle); border-radius: 10px; padding: 16px;">
          <div style="font-size: 11px; color: var(--text-dim); text-transform: uppercase; font-weight: 600;">Thẻ Meta Title</div>
          <div style="font-size: 13px; font-weight: 600; color: #fff; margin-top: 4px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${escapeHtml(title || 'Chưa có')}">
            ${escapeHtml(title || 'Chưa có thẻ Title')}
          </div>
          <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 6px;">
            <div style="font-size: 11px; color: var(--text-muted);">
              Độ dài: <strong style="color: ${(title.length >= 40 && title.length <= 65) ? '#10b981' : '#f59e0b'};">${title.length} kt</strong> (Chuẩn: 40 - 65)
            </div>
            <button type="button" onclick="openQuickOptimizeModal('title')" style="font-size: 11px; padding: 3px 9px; background: rgba(99, 102, 241, 0.15); border: 1px solid rgba(99, 102, 241, 0.35); color: #818cf8; border-radius: 5px; cursor: pointer; font-weight: 500;">⚡ Tối ưu Title</button>
          </div>
        </div>

        <div class="card" style="background: var(--bg-card); border: 1px solid var(--border-subtle); border-radius: 10px; padding: 16px;">
          <div style="font-size: 11px; color: var(--text-dim); text-transform: uppercase; font-weight: 600;">Thẻ Meta Description</div>
          <div style="font-size: 12px; color: #cbd5e1; margin-top: 4px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.4;" title="${escapeHtml(metaDesc || 'Chưa có')}">
            ${escapeHtml(metaDesc || '⚠️ Website chưa có thẻ Meta Description!')}
          </div>
          <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 6px;">
            <div style="font-size: 11px; color: var(--text-muted);">
              Độ dài: <strong style="color: ${(metaDesc.length >= 100 && metaDesc.length <= 165) ? '#10b981' : '#ef4444'};">${metaDesc.length} kt</strong> (120-155)
            </div>
            <button type="button" onclick="openQuickOptimizeModal('meta_description')" style="font-size: 11px; padding: 3px 9px; background: rgba(99, 102, 241, 0.15); border: 1px solid rgba(99, 102, 241, 0.35); color: #818cf8; border-radius: 5px; cursor: pointer; font-weight: 500;">⚡ Tối ưu Description</button>
          </div>
        </div>

        <div class="card" style="background: var(--bg-card); border: 1px solid var(--border-subtle); border-radius: 10px; padding: 16px;">
          <div style="font-size: 11px; color: var(--text-dim); text-transform: uppercase; font-weight: 600;">Thẻ Heading (H1 / H2)</div>
          <div style="font-size: 13.5px; font-weight: 600; color: #fff; margin-top: 4px;">
            H1: <strong style="color: ${h1List.length === 1 ? '#10b981' : '#ef4444'};">${h1List.length} thẻ</strong> | H2: <strong style="color: #38bdf8;">${h2List.length} thẻ</strong>
          </div>
          <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 6px;">
            <div style="font-size: 11px; color: var(--text-muted);">${h1List.length === 1 ? '✓ 1 thẻ H1 duy nhất' : (h1List.length === 0 ? '⚠️ Thiếu H1' : '⚠️ Trùng H1')}</div>
            <button type="button" onclick="openQuickOptimizeModal('h1')" style="font-size: 11px; padding: 3px 9px; background: ${h1List.length === 1 ? 'rgba(16, 185, 129, 0.15)' : 'rgba(239, 68, 68, 0.15)'}; border: 1px solid ${h1List.length === 1 ? 'rgba(16, 185, 129, 0.35)' : 'rgba(239, 68, 68, 0.35)'}; color: ${h1List.length === 1 ? '#34d399' : '#f87171'}; border-radius: 5px; cursor: pointer; font-weight: 500;">${h1List.length === 1 ? '✏️ Tinh chỉnh H1' : '⚡ Chuẩn hóa H1'}</button>
          </div>
        </div>
      </div>

      <!-- 2. CHI TIẾT BỐ CỤC ON-PAGE & ĐỀ XUẤT CỦA AI COPILOT -->
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 16px; margin-bottom: 20px;">
        <div class="card" style="background: rgba(15, 23, 42, 0.7); border: 1px solid var(--border-subtle); border-radius: 10px; padding: 18px;">
          <div style="font-size: 13.5px; font-weight: 600; color: #e2e8f0; display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
            <span>📋</span> Bóc Tách Thẻ Tiêu Đề Heading Hiện Tại
          </div>
          
          <div style="margin-bottom: 12px;">
            <div style="font-size: 11.5px; font-weight: 600; color: var(--text-dim); margin-bottom: 6px;">DANH SÁCH THẺ H1:</div>
            ${h1List.length > 0 ? h1List.map(h => `
              <div style="font-size: 12px; color: #f8fafc; background: rgba(30, 41, 59, 0.6); padding: 6px 10px; border-radius: 5px; margin-bottom: 4px; border-left: 3px solid var(--primary);">
                ${escapeHtml(h)}
              </div>
            `).join('') : '<div style="font-size: 12px; color: var(--danger);">Không tìm thấy thẻ H1 nào trên trang chủ.</div>'}
          </div>

          <div>
            <div style="font-size: 11.5px; font-weight: 600; color: var(--text-dim); margin-bottom: 6px;">DANH SÁCH THẺ H2 TIÊU BIỂU:</div>
            <div style="max-height: 160px; overflow-y: auto;">
              ${h2List.length > 0 ? h2List.slice(0, 8).map(h => `
                <div style="font-size: 11.5px; color: #cbd5e1; background: rgba(30, 41, 59, 0.4); padding: 5px 8px; border-radius: 4px; margin-bottom: 4px;">
                  • ${escapeHtml(h)}
                </div>
              `).join('') : '<div style="font-size: 12px; color: var(--text-dim);">Chưa bóc tách được thẻ H2.</div>'}
            </div>
          </div>
        </div>

        <div class="card" style="background: rgba(15, 23, 42, 0.7); border: 1px solid var(--border-subtle); border-radius: 10px; padding: 18px;">
          <div style="font-size: 13.5px; font-weight: 600; color: #e2e8f0; display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
            <span>🧭</span> Cấu Trúc Menu / Dịch Vụ Chính Nhận Diện Được
          </div>
          <div style="display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 14px;">
            ${navList.length > 0 ? navList.map(item => `
              <span style="font-size: 11.5px; background: rgba(99, 102, 241, 0.15); color: #818cf8; border: 1px solid rgba(99, 102, 241, 0.3); padding: 4px 10px; border-radius: 6px;">
                ${escapeHtml(item)}
              </span>
            `).join('') : '<div style="font-size: 12px; color: var(--text-dim);">Chưa bóc tách được menu điều hướng.</div>'}
          </div>

          <div style="border-top: 1px solid rgba(255, 255, 255, 0.05); padding-top: 12px;">
            <div style="font-size: 11.5px; font-weight: 600; color: var(--text-dim); margin-bottom: 6px;">THÔNG TIN LIÊN HỆ & BẢN QUYỀN:</div>
            <div style="font-size: 12px; color: #94a3b8; display: flex; flex-direction: column; gap: 4px;">
              <div>📞 Hotline: <strong style="color: #fff;">${hotlines.length > 0 ? hotlines.join(', ') : 'Đang cập nhật'}</strong></div>
              <div>🏢 Tên Thương hiệu: <strong style="color: #fff;">${escapeHtml(brandNameDisplay)}</strong></div>
              <div>📍 Khu vực mục tiêu: <strong style="color: #fff;">${escapeHtml(project.target_location || 'Toàn quốc')}</strong></div>
            </div>
          </div>
        </div>
      </div>

      <!-- 3A. ĐỐI CHIẾU DANH MỤC VỚI ĐỐI THỦ MẪU -->
      <div class="card" style="background: rgba(15, 23, 42, 0.7); border: 1px solid var(--border-subtle); border-radius: 10px; padding: 18px; margin-bottom: 20px;">
        <div style="font-size: 13.5px; font-weight: 600; color: #e2e8f0; display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
          <span>⚖️</span> Đối Chiếu Danh Mục Ngành Hàng (So Với Đối Thủ Mẫu)
        </div>
        ${seedCategories.length > 0 ? `
          <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 8px;">
            ${seedCategories.map(cat => `
              <span style="font-size: 12px; background: rgba(16, 185, 129, 0.12); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); padding: 5px 12px; border-radius: 6px;">
                📂 ${escapeHtml(cat)}
              </span>
            `).join('')}
          </div>
          <div style="font-size: 11px; color: var(--text-dim); margin-top: 5px;">
            👉 Gợi ý: Website cần phát triển nên thiết lập các chuyên mục này để bao phủ toàn bộ bộ từ khóa của đối thủ.
          </div>
        ` : `
          <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
            <div style="font-size: 12px; color: var(--text-dim);">Chưa có dữ liệu danh mục từ đối thủ mẫu.</div>
            <button type="button" onclick="switchTab('competitors')" style="font-size: 11px; padding: 4px 10px; background: rgba(14, 165, 233, 0.15); border: 1px solid rgba(14, 165, 233, 0.3); color: #38bdf8; border-radius: 4px; cursor: pointer;">🔍 Soi Đối Thủ SERP để đối chiếu</button>
          </div>
        `}
      </div>

      <!-- 3B. DẤU CHÂN TỪ KHÓA ĐA TẦNG CỦA WEBSITE ĐÍCH & PHÂN TÍCH KHOẢNG TRỐNG KEYWORD GAP -->
      ${typeof renderMySiteKeywordGapsHtml === 'function' ? renderMySiteKeywordGapsHtml(project, seedAnalysis, audit) : ''}

      <!-- 4. KẾ HOẠCH HÀNH ĐỘNG 5 BƯỚC TỐI ƯU ON-PAGE (1-CLICK IN-PLACE ACTIONS) -->
      ${typeof renderOnpageFiveStepPlanHtml === 'function' ? renderOnpageFiveStepPlanHtml(project, audit, dynamicTopics, eeatLocked) : ''}
    `;
  } catch (err) {
    console.error('Lỗi khi render MySite Tab:', err);
    container.innerHTML = `
      <div class="card" style="padding: 24px; border: 1px solid var(--danger); background: rgba(239, 68, 68, 0.1); border-radius: 10px; color: #fca5a5; margin-top: 10px;">
        <h4 style="margin-bottom: 8px;">⚠️ Có lỗi khi dựng giao diện Báo cáo On-page</h4>
        <p style="font-size: 13px; margin: 8px 0; color: #fecaca;">Chi tiết lỗi: ${escapeHtml(err.message)}</p>
        <button type="button" class="btn-primary" onclick="rescanMySite()" style="font-size: 12px; padding: 7px 16px; margin-top: 8px;">
          🔄 Quét Lại Website Ngay
        </button>
      </div>
    `;
  }
}
window.renderMySiteTab = renderMySiteTab;
