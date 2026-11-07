// ============================================================
// PORTAL UNIVERSAL VISUAL ARTISAN (SOP 23 <= 300 lines)
// Multi-Archetype Visual Worker UI (Novel, Product, B2B Service)
// ============================================================

function getActiveProjectArchetype() {
  const p = state.activeProject || {};
  let bp = p.brand_profile || {};
  if (typeof bp === 'string') {
    try { bp = JSON.parse(bp); } catch(e) { bp = {}; }
  }
  if (bp.archetype) return bp.archetype;
  const desc = ((p.business_description || '') + ' ' + (p.target_website_url || '') + ' ' + (p.name || '')).toLowerCase();
  if (desc.includes('truyện') || desc.includes('novel') || desc.includes('book') || desc.includes('đọc')) return 'content_media';
  if (desc.includes('phòng khám') || desc.includes('bác sĩ') || desc.includes('clinic') || desc.includes('luật')) return 'service_health_b2b';
  return 'commerce_retail';
}

function showCoverArtisanModal(defaultTitle, defaultSubtitle) {
  const pId = state.activeProjectId;
  if (!pId) {
    showToast('Vui lòng chọn một dự án trước khi tạo hình ảnh.', 'warning');
    return;
  }

  const existing = document.getElementById('coverArtisanModal');
  if (existing) existing.remove();

  const arch = getActiveProjectArchetype();
  const modal = document.createElement('div');
  modal.id = 'coverArtisanModal';
  modal.style.cssText = `
    position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
    background: rgba(0, 0, 0, 0.75); backdrop-filter: blur(8px);
    z-index: 99999; display: flex; align-items: center; justify-content: center;
    padding: 16px; box-sizing: border-box;
  `;

  let formFields = '';
  if (arch === 'content_media') {
    formFields = `
      <div style="grid-column: 1 / -1;">
        <label style="font-size: 12px; color: #cbd5e1; display: block; margin-bottom: 4px;">Tên Tác Phẩm / Truyện:</label>
        <input type="text" id="visualTitle" value="${escapeHtml(defaultTitle || '')}" placeholder="VD: Đấu Phá Thương Khung" style="width: 100%; padding: 8px 12px; background: #1e293b; border: 1px solid #334155; border-radius: 6px; color: #fff; font-size: 13px; box-sizing: border-box;">
      </div>
      <div>
        <label style="font-size: 12px; color: #cbd5e1; display: block; margin-bottom: 4px;">Thể Loại:</label>
        <input type="text" id="visualGenre" value="${escapeHtml(defaultSubtitle || '')}" placeholder="VD: Tiên Hiệp Kỳ Ảo" style="width: 100%; padding: 8px 12px; background: #1e293b; border: 1px solid #334155; border-radius: 6px; color: #fff; font-size: 13px; box-sizing: border-box;">
      </div>
      <div>
        <label style="font-size: 12px; color: #cbd5e1; display: block; margin-bottom: 4px;">Tác Giả:</label>
        <input type="text" id="visualAuthor" value="Sưu tầm" placeholder="Tên tác giả hoặc người dịch" style="width: 100%; padding: 8px 12px; background: #1e293b; border: 1px solid #334155; border-radius: 6px; color: #fff; font-size: 13px; box-sizing: border-box;">
      </div>
    `;
  } else if (arch === 'service_health_b2b') {
    formFields = `
      <div style="grid-column: 1 / -1;">
        <label style="font-size: 12px; color: #cbd5e1; display: block; margin-bottom: 4px;">Tên Dịch Vụ / Liệu Trình:</label>
        <input type="text" id="visualTitle" value="${escapeHtml(defaultTitle || '')}" placeholder="VD: Cấy Ghép Implant Kỹ Thuật Số" style="width: 100%; padding: 8px 12px; background: #1e293b; border: 1px solid #334155; border-radius: 6px; color: #fff; font-size: 13px; box-sizing: border-box;">
      </div>
      <div>
        <label style="font-size: 12px; color: #cbd5e1; display: block; margin-bottom: 4px;">Bác Sĩ / Chuyên Gia:</label>
        <input type="text" id="visualDoctor" value="" placeholder="VD: ThS.BS Chuyên Khoa I" style="width: 100%; padding: 8px 12px; background: #1e293b; border: 1px solid #334155; border-radius: 6px; color: #fff; font-size: 13px; box-sizing: border-box;">
      </div>
      <div>
        <label style="font-size: 12px; color: #cbd5e1; display: block; margin-bottom: 4px;">Phân Nhóm / Khoa:</label>
        <input type="text" id="visualGenre" value="${escapeHtml(defaultSubtitle || '')}" placeholder="VD: Nha Khoa Thẩm Mỹ" style="width: 100%; padding: 8px 12px; background: #1e293b; border: 1px solid #334155; border-radius: 6px; color: #fff; font-size: 13px; box-sizing: border-box;">
      </div>
    `;
  } else {
    formFields = `
      <div style="grid-column: 1 / -1;">
        <label style="font-size: 12px; color: #cbd5e1; display: block; margin-bottom: 4px;">Tên Sản Phẩm / Model:</label>
        <input type="text" id="visualTitle" value="${escapeHtml(defaultTitle || '')}" placeholder="VD: Tên sản phẩm chính thức" style="width: 100%; padding: 8px 12px; background: #1e293b; border: 1px solid #334155; border-radius: 6px; color: #fff; font-size: 13px; box-sizing: border-box;">
      </div>
      <div>
        <label style="font-size: 12px; color: #cbd5e1; display: block; margin-bottom: 4px;">Giá Bán Niêm Yết:</label>
        <input type="text" id="visualPrice" value="" placeholder="VD: 12.990.000₫" style="width: 100%; padding: 8px 12px; background: #1e293b; border: 1px solid #334155; border-radius: 6px; color: #fff; font-size: 13px; box-sizing: border-box;">
      </div>
      <div>
        <label style="font-size: 12px; color: #cbd5e1; display: block; margin-bottom: 4px;">Thông Số Nổi Bật (cách nhau dấu phẩy):</label>
        <input type="text" id="visualSpecs" value="" placeholder="VD: Tiết kiệm điện, Bảo hành 24 tháng, Chính hãng" style="width: 100%; padding: 8px 12px; background: #1e293b; border: 1px solid #334155; border-radius: 6px; color: #fff; font-size: 13px; box-sizing: border-box;">
      </div>
    `;
  }

  modal.innerHTML = `
    <div style="background: #0f172a; border: 1px solid rgba(255,255,255,0.15); border-radius: 14px; width: 100%; max-width: 600px; padding: 22px; box-shadow: 0 20px 40px rgba(0,0,0,0.6); color: #f8fafc; font-family: inherit;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 12px;">
        <div style="display: flex; align-items: center; gap: 8px;">
          <span style="font-size: 22px;">🎨</span>
          <div>
            <div style="font-size: 16px; font-weight: 700; color: #e2e8f0;">Universal Visual Artisan (E-E-A-T Visual Worker)</div>
            <div style="font-size: 11px; color: #94a3b8;">Đang hoạt động cho ngành: <span style="color: #38bdf8; font-weight: 600;">${arch.toUpperCase()}</span></div>
          </div>
        </div>
        <button type="button" onclick="document.getElementById('coverArtisanModal').remove()" style="background: none; border: none; color: #94a3b8; font-size: 20px; cursor: pointer;">&times;</button>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
        ${formFields}
      </div>

      <div id="coverPreviewContainer" style="text-align: center; margin-bottom: 16px; background: #020617; border: 1px dashed rgba(255,255,255,0.15); border-radius: 8px; padding: 14px; min-height: 220px; display: flex; align-items: center; justify-content: center; flex-direction: column;">
        <span style="color: #64748b; font-size: 12px;">Nhấn "✨ Sinh Ảnh Mẫu Ngay" để Worker render trong &lt; 0.1 giây</span>
      </div>

      <div style="display: flex; gap: 8px; justify-content: flex-end;">
        <button type="button" id="btnRunCoverGen" onclick="executeGenerateVisual('${arch}')" style="padding: 8px 16px; background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%); color: #fff; border: none; border-radius: 6px; font-size: 12.5px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px;">
          <span>✨ Sinh Ảnh Mẫu Ngay (&lt; 0.1s)</span>
        </button>
        <button type="button" id="btnBatchRepairCovers" onclick="scanAndRepairMissingCovers()" style="padding: 8px 16px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: #fff; border: none; border-radius: 6px; font-size: 12.5px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px;">
          <span>🚀 Quét & Gán Bìa Tự Động (WordPress)</span>
        </button>
      </div>
    </div>
  `;

  document.body.appendChild(modal);
}

async function executeGenerateVisual(archetype) {
  const pId = state.activeProjectId;
  const title = (document.getElementById('visualTitle') || {}).value || 'Tiêu Đề Mẫu';
  const genre = (document.getElementById('visualGenre') || {}).value || '';
  const author = (document.getElementById('visualAuthor') || {}).value || 'Sưu tầm';
  const doctor = (document.getElementById('visualDoctor') || {}).value || '';
  const price = (document.getElementById('visualPrice') || {}).value || '';
  const specsRaw = (document.getElementById('visualSpecs') || {}).value || '';
  const specs = specsRaw.split(',').map(s => s.trim()).filter(Boolean);

  const prevBox = document.getElementById('coverPreviewContainer');
  const btn = document.getElementById('btnRunCoverGen');

  if (btn) btn.disabled = true;
  if (prevBox) prevBox.innerHTML = '<span style="color:#60a5fa; font-size:12px;">⏳ Đang render hình ảnh chuẩn Typography...</span>';

  try {
    const res = await fetch(`/api/v1/portal/projects/${pId}/covers/generate`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        title, archetype, genre, author, price, specs, doctor, aspect_ratio: 'auto'
      })
    });
    const data = await res.json();
    if (res.ok && data.status === 'success') {
      prevBox.innerHTML = `
        <img src="${data.web_url}" style="max-height: 240px; border-radius: 6px; box-shadow: 0 10px 25px rgba(0,0,0,0.5); margin-bottom: 8px;" alt="${escapeHtml(data.alt_text)}">
        <div style="font-size: 11px; color: #a7f3d0; font-weight: 500;">
          ✅ Đã render thành công! Kích thước: ${data.width}x${data.height}px | Dung lượng: ${data.file_size_kb} KB
        </div>
        <div style="font-size: 10.5px; color: #94a3b8; margin-top: 2px;">
          Alt: <em>${escapeHtml(data.alt_text)}</em>
        </div>
      `;
      showToast('Đã sinh hình ảnh chuẩn E-E-A-T thành công!', 'success');
    } else {
      prevBox.innerHTML = `<span style="color:#f87171; font-size:12px;">Lỗi: ${data.detail || 'Không thể tạo ảnh'}</span>`;
    }
  } catch (err) {
    prevBox.innerHTML = `<span style="color:#f87171; font-size:12px;">Lỗi kết nối: ${err.message}</span>`;
  } finally {
    if (btn) btn.disabled = false;
  }
}

async function scanAndRepairMissingCovers() {
  const pId = state.activeProjectId;
  if (!pId) {
    showToast('Chưa chọn dự án.', 'warning');
    return;
  }
  showToast('Đang quét và tự động bù đắp ảnh bìa trên WordPress qua Omni Bridge...', 'info');
  try {
    const res = await fetch(`/api/v1/portal/projects/${pId}/covers/scan-and-repair`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ limit: 10 })
    });
    const data = await res.json();
    if (res.ok && data.status === 'requires_plugin_update') {
      showPluginUpdateNoticeModal(data);
      return;
    }
    if (res.ok && data.status === 'success') {
      const modal = document.getElementById('coverArtisanModal');
      if (modal) modal.remove();
      showToast(`Đã tự động tạo và gắn ảnh bìa thành công cho ${data.total_repaired}/${data.total_scanned} bài viết trên WordPress!`, 'success');
      if (typeof loadProjectIntelligence === 'function') loadProjectIntelligence(pId);
    } else {
      showToast(data.detail || data.message || 'Chưa thể quét và sửa ảnh bìa. Vui lòng kiểm tra kết nối Omni Bridge.', 'danger');
    }
  } catch (err) {
    showToast('Lỗi kết nối: ' + err.message, 'danger');
  }
}

function showPluginUpdateNoticeModal(data) {
  const existing = document.getElementById('pluginUpdateModal');
  if (existing) existing.remove();

  const modal = document.createElement('div');
  modal.id = 'pluginUpdateModal';
  modal.style.cssText = `
    position: fixed; top: 0; left: 0; width: 100vw; height: 100vh;
    background: rgba(0, 0, 0, 0.75); backdrop-filter: blur(8px);
    z-index: 999999; display: flex; align-items: center; justify-content: center;
    padding: 16px; box-sizing: border-box;
  `;

  modal.innerHTML = `
    <div style="background: #0f172a; border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 14px; width: 100%; max-width: 520px; padding: 22px; box-shadow: 0 20px 40px rgba(0,0,0,0.6); color: #f8fafc; font-family: inherit;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 12px;">
        <div style="display: flex; align-items: center; gap: 8px;">
          <span style="font-size: 22px;">🚀</span>
          <div>
            <div style="font-size: 16px; font-weight: 700; color: #60a5fa;">Yêu cầu Cập nhật Omni Bridge Connector v1.4.0</div>
            <div style="font-size: 11px; color: #94a3b8;">Phiên bản hiện tại trên web: <span style="color:#f59e0b; font-weight:600;">v${escapeHtml(data.current_version || '1.3.0')}</span> ➔ Bản yêu cầu: <span style="color:#10b981; font-weight:600;">v1.4.0</span></div>
          </div>
        </div>
        <button type="button" onclick="document.getElementById('pluginUpdateModal').remove()" style="background: none; border: none; color: #94a3b8; font-size: 20px; cursor: pointer;">&times;</button>
      </div>

      <p style="font-size: 13px; color: #cbd5e1; line-height: 1.5; margin-bottom: 14px;">
        Để cho phép CMO AI tự động quét kho truyện / sản phẩm On-Page và tự gán ảnh bìa trực tiếp vào WordPress Media Library một chạm, bạn chỉ cần tải bản v1.4.0 và tải lên WordPress:
      </p>

      <div style="background: #1e293b; border-radius: 8px; padding: 12px; margin-bottom: 16px; font-size: 12px; color: #94a3b8; line-height: 1.6;">
        1. Bấm nút <strong>"⬇️ Tải Plugin v1.4.0 (.zip)"</strong> bên dưới.<br>
        2. Vào <strong>WP Admin ➔ Plugins ➔ Add New (Cài mới) ➔ Upload Plugin</strong>.<br>
        3. Chọn file vừa tải và bấm <strong>"Replace current with uploaded"</strong> (Thay thế bản hiện tại).
      </div>

      <div style="display: flex; gap: 10px; justify-content: flex-end;">
        <button type="button" onclick="document.getElementById('pluginUpdateModal').remove()" style="padding: 8px 14px; background: #334155; color: #cbd5e1; border: none; border-radius: 6px; font-size: 12.5px; cursor: pointer;">
          Đóng
        </button>
        <a href="${escapeHtml(data.download_url || '/api/v1/download/omni-bridge-connector')}" target="_blank" download style="padding: 8px 16px; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: #fff; text-decoration: none; border-radius: 6px; font-size: 12.5px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
          <span>⬇️ Tải Plugin v1.4.0 (.zip)</span>
        </a>
      </div>
    </div>
  `;

  document.body.appendChild(modal);
}

window.showCoverArtisanModal = showCoverArtisanModal;
window.executeGenerateVisual = executeGenerateVisual;
window.scanAndRepairMissingCovers = scanAndRepairMissingCovers;
window.showPluginUpdateNoticeModal = showPluginUpdateNoticeModal;
