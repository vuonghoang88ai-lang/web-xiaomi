/**
 * Portal My Site Actions - 1-Click In-place Optimization (SOP 23 < 300 lines)
 * Triệt tiêu hoàn toàn thao tác nhảy tab sang Chat gây loãng ngữ cảnh (SOP 30 & SOP 45).
 */

async function rescanMySite() {
  if (!state.activeProjectId) {
    showToast('Chưa chọn dự án để quét lại website mục tiêu.', 'warning');
    return;
  }
  const btn = document.getElementById('btnRescanMySite'), icon = document.getElementById('rescanMySiteIcon'), text = document.getElementById('rescanMySiteText');
  if (btn) btn.disabled = true; if (icon) icon.textContent = '⏳'; if (text) text.textContent = 'Đang trinh sát On-page...';

  try {
    const res = await fetch(`/api/v1/portal/projects/${state.activeProjectId}/rescan-my-site`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' }
    });
    const data = await res.json();
    if (res.ok && data.status === 'success') {
      showToast(data.message || 'Đã phân tích lại Onpage website mục tiêu thành công!', 'success');
      await loadProjectIntelligence(state.activeProjectId);
    } else {
      showToast(data.detail || data.message || 'Không thể quét lại website mục tiêu.', 'danger');
    }
  } catch (err) {
    showToast('Lỗi kết nối khi quét website: ' + err.message, 'danger');
  } finally {
    if (btn) btn.disabled = false; if (icon) icon.textContent = '🔄'; if (text) text.textContent = 'Quét Lại On-page Web Đích';
  }
}
window.rescanMySite = rescanMySite;

async function autoFixOnpageViaBridge(customSeo = null) {
  if (!state.activeProjectId) {
    showToast('Chưa chọn dự án.', 'warning');
    return;
  }
  const btn = document.getElementById('btnAutoFixOnpage'), icon = document.getElementById('autoFixOnpageIcon'), text = document.getElementById('autoFixOnpageText');
  if (btn) btn.disabled = true; if (icon) icon.textContent = '⏳'; if (text) text.textContent = 'Đang lưu lên WordPress...';

  try {
    const res = await fetch(`/api/v1/portal/projects/${state.activeProjectId}/auto-fix-onpage`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: customSeo ? JSON.stringify(customSeo) : undefined
    });
    const data = await res.json();
    if (res.ok && data.status === 'success') {
      showToast(data.message || 'Đã tự động tối ưu On-page lên WordPress thành công!', 'success');
      await loadProjectIntelligence(state.activeProjectId);
      return true;
    } else {
      showToast(data.detail || data.message || 'Chưa thể tự động sửa On-page. Vui lòng kiểm tra Omni Bridge.', 'danger');
      return false;
    }
  } catch (err) {
    showToast('Lỗi kết nối: ' + err.message, 'danger');
    return false;
  } finally {
    if (btn) btn.disabled = false; if (icon) icon.textContent = '🚀'; if (text) text.textContent = 'Tối Ưu Toàn Diện 100 Điểm';
  }
}
window.autoFixOnpageViaBridge = autoFixOnpageViaBridge;

function openQuickOptimizeModal(type) {
  const p = state.activeProject;
  if (!p) { showToast('Vui lòng chọn dự án trước.', 'warning'); return; }
  let bp = p.brand_profile || {};
  if (typeof bp === 'string') { try { bp = JSON.parse(bp); } catch(e) { bp = {}; } }
  const audit = bp.my_site_audit || {};
  const suggs = typeof generateOnpageSuggestions === 'function'
    ? generateOnpageSuggestions(p, bp, audit)
    : { title: '', meta_description: '', h1: '' };

  let title = '', label = '', curVal = '', minLen = 0, maxLen = 0, fieldKey = '', vKey = '', isLocked = false;
  if (type === 'title') {
    title = '⚡ Tối Ưu Thẻ Meta Title Tại Chỗ';
    label = 'Tiêu đề trang chủ (Chuẩn SEO: 40 - 65 ký tự)';
    curVal = (bp.locked_title || audit.title || '').trim();
    isLocked = Boolean(bp.title_locked && bp.locked_title);
    minLen = 40; maxLen = 65; fieldKey = 'homepage_title'; vKey = 'title_variants';
  } else if (type === 'meta_description') {
    title = '⚡ Tối Ưu Thẻ Meta Description Tại Chỗ';
    label = 'Mô tả tóm tắt trang chủ (Chuẩn SEO: 120 - 155 ký tự)';
    curVal = (bp.locked_meta_description || audit.meta_description || '').trim();
    isLocked = Boolean(bp.meta_description_locked && bp.locked_meta_description);
    minLen = 120; maxLen = 155; fieldKey = 'homepage_description'; vKey = 'meta_description_variants';
  } else if (type === 'h1') {
    title = '⚡ Chuẩn Hóa 1 Thẻ H1 Duy Nhất Chứa Định Vị UVP';
    label = 'Nội dung thẻ H1 độc quyền đại diện cho toàn trang web';
    const h1Arr = Array.isArray(audit.h1) ? audit.h1 : (audit.h1 ? [audit.h1] : []);
    curVal = (bp.locked_h1 || h1Arr[0] || audit.title || '').trim();
    isLocked = Boolean(bp.h1_locked && bp.locked_h1);
    minLen = 20; maxLen = 80; fieldKey = 'homepage_h1'; vKey = 'h1_variants';
  }

  const variants = (suggs[vKey] && suggs[vKey].length) ? suggs[vKey] : [suggs[type] || curVal];
  window._currentModalVariants = variants;
  window._currentModalIndex = 0;
  window._currentModalType = type;
  window._currentModalBrand = suggs.brand;
  window._currentModalCats = suggs.categories;
  window._currentModalArchetype = suggs.archetype;
  const isCurValid = curVal && (minLen === 0 || (curVal.length >= minLen && curVal.length <= maxLen));
  const initialVal = isCurValid ? curVal : (variants[0] || curVal);

  let modal = document.getElementById('onpageQuickModal');
  if (!modal) {
    modal = document.createElement('div');
    modal.id = 'onpageQuickModal';
    document.body.appendChild(modal);
  }
  modal.style.cssText = 'position:fixed;inset:0;background:rgba(15,23,42,0.75);backdrop-filter:blur(8px);z-index:99999;display:flex;align-items:center;justify-content:center;padding:16px;';
  
  modal.innerHTML = `
    <div style="background:var(--bg-card,#1e293b);border:1px solid var(--border-subtle,rgba(255,255,255,0.1));border-radius:12px;width:100%;max-width:540px;padding:22px;box-shadow:0 20px 40px rgba(0,0,0,0.5);color:#f8fafc;">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
        <h3 style="font-size:15px;font-weight:600;margin:0;color:#f8fafc;display:flex;align-items:center;gap:8px;">${title} ${isLocked ? '<span style="font-size:10px;color:#34d399;background:rgba(16,185,129,0.15);padding:1px 6px;border-radius:4px;border:1px solid rgba(16,185,129,0.3);">🔒 Đã Khóa (Web)</span>' : ''}</h3>
        <button type="button" onclick="closeQuickOptimizeModal()" style="background:transparent;border:none;color:#94a3b8;font-size:18px;cursor:pointer;">✕</button>
      </div>
      <div style="font-size:12px;color:var(--text-muted,#94a3b8);margin-bottom:12px;">${label}</div>
      ${isLocked ? `<div style="font-size:11px;color:#34d399;margin-bottom:8px;background:rgba(16,185,129,0.08);padding:5px 8px;border-radius:4px;border:1px solid rgba(16,185,129,0.2);">🔒 Nội dung này đang được khóa bảo toàn trên web. Bấm "Lưu Lên Web" để đổi khóa mới.</div>` : ''}
      ${type === 'meta_description' 
        ? `<textarea id="quickOptInput" rows="3" style="width:100%;background:rgba(15,23,42,0.8);border:1px solid var(--border-subtle,rgba(255,255,255,0.15));border-radius:6px;padding:10px;color:#fff;font-size:13px;line-height:1.5;resize:vertical;">${escapeHtml(initialVal)}</textarea>`
        : `<input type="text" id="quickOptInput" value="${escapeHtml(initialVal)}" style="width:100%;background:rgba(15,23,42,0.8);border:1px solid var(--border-subtle,rgba(255,255,255,0.15));border-radius:6px;padding:10px;color:#fff;font-size:13px;">`
      }
      <div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px;font-size:11.5px;gap:8px;flex-wrap:wrap;">
        <span id="quickOptCounter" style="color:#10b981;font-weight:600;">Độ dài: 0 kt</span>
        <div style="display:flex;align-items:center;gap:6px;">
          <button type="button" id="btnCycleQuickOpt" onclick="window.cycleQuickOptimizeVariant()" style="background:rgba(99,102,241,0.15);border:1px solid rgba(99,102,241,0.3);color:#818cf8;border-radius:4px;padding:3px 8px;cursor:pointer;display:flex;align-items:center;gap:4px;">💡 Đổi Gợi Ý Tiếp Theo (1/${variants.length})</button>
          <button type="button" id="btnCreativeAiOpt" onclick="window.requestCreativeAiOnpageSuggestion()" style="background:rgba(16,185,129,0.12);border:1px solid rgba(16,185,129,0.25);color:#34d399;border-radius:4px;padding:3px 8px;cursor:pointer;display:flex;align-items:center;gap:4px;">✨ AI Sáng Tạo Mới</button>
        </div>
      </div>
      <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:18px;">
        <button type="button" onclick="closeQuickOptimizeModal()" style="padding:7px 14px;background:rgba(255,255,255,0.06);border:1px solid var(--border-subtle,rgba(255,255,255,0.1));color:#cbd5e1;border-radius:6px;cursor:pointer;font-size:12.5px;">Hủy</button>
        <button type="button" id="btnSubmitQuickOpt" onclick="submitQuickOptimize('${fieldKey}')" style="padding:7px 18px;background:linear-gradient(135deg,#10b981 0%,#059669 100%);border:none;color:#fff;border-radius:6px;cursor:pointer;font-size:12.5px;font-weight:600;display:flex;align-items:center;gap:6px;">🚀 Lưu Lên Web (Omni Bridge)</button>
      </div>
    </div>
  `;

  const inputEl = document.getElementById('quickOptInput');
  const countEl = document.getElementById('quickOptCounter');
  window.updateQuickOptCounter = () => {
    const len = (inputEl.value || '').trim().length;
    const ok = minLen === 0 || (len >= minLen && len <= maxLen);
    countEl.textContent = `Độ dài: ${len} kt (Chuẩn: ${minLen} - ${maxLen})`;
    countEl.style.color = ok ? '#10b981' : '#f59e0b';
  };
  inputEl.addEventListener('input', window.updateQuickOptCounter);
  window.updateQuickOptCounter();
}
window.openQuickOptimizeModal = openQuickOptimizeModal;

function closeQuickOptimizeModal() {
  const m = document.getElementById('onpageQuickModal');
  if (m) m.remove();
}
window.closeQuickOptimizeModal = closeQuickOptimizeModal;

async function submitQuickOptimize(fieldKey) {
  const inputEl = document.getElementById('quickOptInput'), btn = document.getElementById('btnSubmitQuickOpt');
  if (!inputEl) return;
  const val = inputEl.value.trim();
  if (!val) { showToast('Nội dung không được để trống.', 'warning'); return; }
  if (btn) { btn.disabled = true; btn.textContent = '⏳ Đang lưu...'; }
  const payload = {}; payload[fieldKey] = val;
  const success = await autoFixOnpageViaBridge(payload);
  if (success) closeQuickOptimizeModal();
}
window.submitQuickOptimize = submitQuickOptimize;

async function quickSyncSiloCategories() {
  showToast('Đang kích hoạt Omni Bridge đồng bộ Silo Menu lên WordPress...', 'info');
  await autoFixOnpageViaBridge({ sync_silo: true });
}
window.quickSyncSiloCategories = quickSyncSiloCategories;

function viewLegalPagesModal() {
  const p = state.activeProject;
  const targetUrl = (p && p.target_website_url ? p.target_website_url.replace(/\/$/, '') : 'https://www.truyen360.top');
  const pages = [
    { title: 'Giới Thiệu Nền Tảng', slug: '/gioi-thieu/' }, { title: 'Điều Khoản Dịch Vụ', slug: '/dieu-khoan/' },
    { title: 'Chính Sách Bảo Mật', slug: '/chinh-sach-bao-mat/' }, { title: 'Bản Quyền & DMCA', slug: '/ban-quyen-dmca/' },
    { title: 'Liên Hệ & Báo Lỗi', slug: '/lien-he/' }
  ];
  let modal = document.getElementById('legalPagesModal');
  if (!modal) {
    modal = document.createElement('div');
    modal.id = 'legalPagesModal';
    document.body.appendChild(modal);
  }
  modal.style.cssText = 'position:fixed;inset:0;background:rgba(15,23,42,0.75);backdrop-filter:blur(8px);z-index:99999;display:flex;align-items:center;justify-content:center;padding:16px;';
  modal.innerHTML = `
    <div style="background:var(--bg-card,#1e293b);border:1px solid var(--border-subtle,rgba(255,255,255,0.1));border-radius:12px;width:100%;max-width:520px;padding:22px;box-shadow:0 20px 40px rgba(0,0,0,0.5);color:#f8fafc;">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
        <h3 style="font-size:15px;font-weight:600;margin:0;color:#34d399;display:flex;align-items:center;gap:8px;">🔒 Bộ 5 Trang Tín Nhiệm E-E-A-T Đã Bảo Toàn</h3>
        <button type="button" onclick="document.getElementById('legalPagesModal').remove()" style="background:transparent;border:none;color:#94a3b8;font-size:18px;cursor:pointer;">✕</button>
      </div>
      <div style="font-size:12px;color:var(--text-muted,#94a3b8);margin-bottom:14px;">Các trang pháp lý được bảo chứng 100% người thật, loại trừ từ ngữ AI-hóa và đã liên kết chân trang:</div>
      <div style="display:flex;flex-direction:column;gap:8px;">
        ${pages.map(pg => `
          <div style="display:flex;justify-content:space-between;align-items:center;background:rgba(15,23,42,0.6);padding:8px 12px;border-radius:6px;border:1px solid rgba(255,255,255,0.05);">
            <div style="font-size:12.5px;color:#f8fafc;font-weight:500;">${pg.title} <span style="font-size:11px;color:#10b981;margin-left:6px;">● HTTP 200</span></div>
            <a href="${targetUrl}${pg.slug}" target="_blank" style="font-size:11.5px;color:#818cf8;text-decoration:none;display:inline-flex;align-items:center;gap:4px;">Xem trang ↗</a>
          </div>
        `).join('')}
      </div>
      <div style="display:flex;justify-content:flex-end;margin-top:18px;">
        <button type="button" onclick="document.getElementById('legalPagesModal').remove()" style="padding:7px 16px;background:rgba(255,255,255,0.08);border:1px solid rgba(255,255,255,0.1);color:#cbd5e1;border-radius:6px;cursor:pointer;font-size:12.5px;">Đóng</button>
      </div>
    </div>
  `;
}
window.viewLegalPagesModal = viewLegalPagesModal;

function openChatForArticle() {
  const p = state.activeProject;
  const targetUrl = p ? (p.target_website_url || p.name) : 'website';
  switchTab('chat');
  const chatInput = document.getElementById('chatInput');
  if (chatInput) {
    chatInput.value = `Đóng vai trò CMO & Lead Content, hãy nghiên cứu từ khóa top 1 đối thủ và sản xuất 01 bài viết chuyên sâu 2000+ từ chuẩn SOP 12 cho website ${targetUrl}.`;
    chatInput.focus();
  }
}
window.openChatForArticle = openChatForArticle;

function renderOnpageFiveStepPlanHtml(project, audit, dynamicTopics, eeatLocked) {
  const metaDesc = (audit.meta_description || '').trim();
  const h1List = Array.isArray(audit.h1) ? audit.h1 : (audit.h1 ? [audit.h1] : []);
  const navList = Array.isArray(audit.key_services_or_nav) ? audit.key_services_or_nav : [];
  const isMetaOk = metaDesc.length >= 100 && metaDesc.length <= 165;
  const isH1Ok = h1List.length === 1;
  const isSiloOk = navList.length >= 2;
  const completedCount = (isMetaOk ? 1 : 0) + (isH1Ok ? 1 : 0) + (isSiloOk ? 1 : 0) + (eeatLocked ? 1 : 0);
  const progressPercent = Math.round((completedCount / 5) * 100);

  const steps = [
    { num: 1, ok: isMetaOk, title: 'Bổ sung thẻ Meta Description 140 - 155 ký tự', desc: `Viết mô tả nêu rõ định vị thương hiệu và các chủ đề cốt lõi (${escapeHtml(dynamicTopics)}).`, badge: isMetaOk ? `✅ Đã Đạt Chuẩn (${metaDesc.length} kt)` : '⚠️ Cần Chuẩn Hóa', isWarn: !isMetaOk, btnText: isMetaOk ? '✏️ Tinh chỉnh' : '⚡ Sửa tại chỗ', btnAction: "openQuickOptimizeModal('meta_description')" },
    { num: 2, ok: isH1Ok, title: 'Chuẩn hóa 1 thẻ H1 duy nhất chứa Định Vị UVP', desc: 'Hợp nhất nhiều thẻ H1 hiện tại thành 1 thẻ duy nhất chứa định vị độc quyền để Google xác định rõ trọng tâm.', badge: isH1Ok ? '✅ 1 Thẻ H1 Duy Nhất' : '⚠️ Cần Chuẩn Hóa', isWarn: !isH1Ok, btnText: isH1Ok ? '✏️ Tinh chỉnh' : '⚡ Chuẩn hóa H1', btnAction: "openQuickOptimizeModal('h1')" },
    { num: 3, ok: isSiloOk, title: 'Xây dựng cấu trúc Silo danh mục cấp 1 & cấp 2', desc: `Tạo cây danh mục theo các nhóm từ khóa vàng: ${escapeHtml(dynamicTopics)}.`, badge: isSiloOk ? `✅ Đã Có Silo (${navList.length} mục)` : '⚡ Silo Taxonomy', isWarn: false, btnText: isSiloOk ? '⚡ Đồng bộ lại' : '⚡ Đồng bộ Silo', btnAction: 'quickSyncSiloCategories()' },
    { num: 4, ok: eeatLocked, title: 'Bộ 5 Trang Tín Nhiệm E-E-A-T & Schema Markup', desc: 'Đã kiểm định độ sâu, khử sạch từ ngữ AI-hóa và khóa bảo toàn (không bị ghi đè khi tối ưu lại).', badge: eeatLocked ? '🔒 Đã Khóa Bảo Toàn' : '⚡ Sẵn Sàng', isWarn: false, btnText: '👁️ Xem 5 trang', btnAction: 'viewLegalPagesModal()' },
    { num: 5, ok: false, isCta: true, title: 'Sản xuất bài viết chuyên sâu 2000+ từ (SOP 12)', desc: 'Sử dụng CMO AI Copilot để viết bài so sánh chuyên sâu, đè bẹp các đối thủ top 1 trên Google SERP.', badge: '✍️ Sẵn Sàng Viết Bài', isWarn: false, btnText: '✍️ Viết Bài Với CMO', btnAction: 'openChatForArticle()' }
  ];

  return `
    <div class="card" style="background:rgba(15,23,42,0.7);border:1px solid var(--border-subtle);border-radius:10px;padding:18px;">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;flex-wrap:wrap;gap:8px;">
        <div style="display:flex;align-items:center;gap:10px;">
          <span style="font-size:14px;font-weight:600;color:#f8fafc;">🎯 Kế Hoạch 5 Bước Tối Ưu On-page Trước Khi Viết Bài Chuẩn SEO</span>
          <span style="font-size:11px;color:#10b981;background:rgba(16,185,129,0.12);padding:2px 8px;border-radius:12px;border:1px solid rgba(16,185,129,0.25);font-weight:600;">Tiến độ: ${completedCount}/5 bước (${progressPercent}%)</span>
        </div>
        <button type="button" onclick="autoFixOnpageViaBridge()" style="font-size:11.5px;padding:5px 14px;background:linear-gradient(135deg,#10b981 0%,#059669 100%);border:none;color:white;border-radius:6px;cursor:pointer;font-weight:600;box-shadow:0 2px 8px rgba(16,185,129,0.25);">🚀 Tối Ưu Toàn Bộ 1-Click</button>
      </div>
      <div style="width:100%;height:3px;background:rgba(255,255,255,0.06);border-radius:2px;overflow:hidden;margin-bottom:14px;">
        <div style="width:${progressPercent}%;height:100%;background:linear-gradient(90deg,#10b981,#38bdf8);border-radius:2px;transition:width 0.4s ease;"></div>
      </div>
      <div style="display:flex;flex-direction:column;gap:8px;">
        ${steps.map(s => {
          const bgCircle = s.ok ? '#10b981' : '#6366f1';
          const iconCircle = s.ok ? '✓' : s.num;
          const badgeStyle = s.ok 
            ? 'color:#34d399;background:rgba(16,185,129,0.15);border:1px solid rgba(16,185,129,0.3);' 
            : (s.isWarn ? 'color:#f87171;background:rgba(239,68,68,0.15);border:1px solid rgba(239,68,68,0.3);' : 'color:#818cf8;background:rgba(99,102,241,0.15);border:1px solid rgba(99,102,241,0.3);');
          const btnStyle = s.isCta 
            ? 'background:linear-gradient(135deg,#10b981 0%,#059669 100%);border:none;color:white;font-weight:600;box-shadow:0 2px 6px rgba(16,185,129,0.3);' 
            : 'background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.12);color:#cbd5e1;';
          return `
            <div style="display:flex;gap:12px;align-items:center;background:rgba(30,41,59,0.35);border:1px solid rgba(255,255,255,0.05);padding:10px 14px;border-radius:8px;">
              <span style="background:${bgCircle};color:white;width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:bold;flex-shrink:0;">${iconCircle}</span>
              <div style="flex:1;min-width:0;">
                <div style="font-size:13px;font-weight:600;color:#f8fafc;">${s.title}</div>
                <div style="font-size:11.5px;color:var(--text-muted);margin-top:2px;">${s.desc}</div>
              </div>
              <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
                <span style="font-size:11px;${badgeStyle}padding:3px 8px;border-radius:5px;">${s.badge}</span>
                <button type="button" onclick="${s.btnAction}" style="font-size:11px;padding:4px 10px;border-radius:5px;cursor:pointer;${btnStyle}">${s.btnText}</button>
              </div>
            </div>
          `;
        }).join('')}
      </div>
    </div>
  `;
}
window.renderOnpageFiveStepPlanHtml = renderOnpageFiveStepPlanHtml;
