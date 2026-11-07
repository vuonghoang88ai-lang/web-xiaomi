function renderCmsTab(project, gscConnections) {
  const container = document.getElementById('cmsContent');
  if (!container || !project) return;

  const hasCms = Boolean(project.cms_api_url && project.cms_auth_config && project.cms_auth_config.api_key);
  const apiKey = (project.cms_auth_config && project.cms_auth_config.api_key) || '';

  const gscConn = (gscConnections && gscConnections.length > 0) ? gscConnections[0] : null;
  const isGscConnected = Boolean(gscConn && gscConn.status === 'active');

  const domainHost = (project.target_website_url || project.name || '').replace(/^https?:\/\//, '').replace(/^www\./, '').split('/')[0];
  const licKey = project.license_key || '(Tự động cấp khi tải plugin)';
  const planType = project.plan_type || 'trial_7d';
  const planLabel = (typeof PLANS_CONFIG !== 'undefined' && PLANS_CONFIG[planType]?.label) || (planType === 'trial_7d' ? '🎁 Dùng Thử 7 Ngày' : '📦 Gói Cước');
  const isExpired = project.plan_status === 'expired';

  container.innerHTML = `
    <div class="grid-cards">
      <!-- 1. WORDPRESS OMNI BRIDGE CARD WITH LICENSE -->
      <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
          <span class="card-badge" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">WordPress Omni Bridge v1.2</span>
          <div style="display: flex; gap: 6px; align-items: center;">
            <span style="font-size: 11px; font-weight: 700; color: ${isExpired ? '#ef4444' : '#34d399'}; background: ${isExpired ? 'rgba(239, 68, 68, 0.15)' : 'rgba(16, 185, 129, 0.15)'}; padding: 3px 8px; border-radius: 4px; border: 1px solid ${isExpired ? 'rgba(239, 68, 68, 0.3)' : 'rgba(16, 185, 129, 0.3)'};">
              ${isExpired ? '🔴 HẾT HẠN GÓI' : `🟢 ${planLabel}`}
            </span>
          </div>
        </div>

        <h4 class="card-title" style="margin-bottom: 8px;">Cổng Xuất Bản & Sửa On-page Tự Động</h4>
        <div style="font-size: 12.5px; color: var(--text-muted); line-height: 1.6; margin-bottom: 14px; background: rgba(15, 23, 42, 0.5); padding: 10px 12px; border-radius: 6px; border: 1px solid var(--border-subtle);">
          <div><strong>Tên miền khóa cứng:</strong> <span style="color: #60a5fa; font-weight: 600;">${domainHost}</span> <span style="font-size: 10.5px; color: #94a3b8;">(1 License = 1 Domain duy nhất)</span></div>
          <div style="margin-top: 3px;"><strong>License Key:</strong> <code style="color: #fde047;">${licKey}</code> ${project.license_key ? `<button type="button" onclick="copyLicenseKey('${project.license_key}')" style="background: none; border: none; cursor: pointer; color: #38bdf8; font-size: 12px; padding: 0 4px;" title="Sao chép License Key">📋</button>` : ''}</div>
          <div style="margin-top: 3px;"><strong>Hạn dùng:</strong> <span style="color: #fff;">${project.plan_expires_at ? new Date(project.plan_expires_at).toLocaleDateString('vi-VN') : '7 ngày kể từ khi cài đặt'}</span></div>
          <div style="margin-top: 3px;"><strong>Kết nối API:</strong> ${hasCms ? '<span style="color: #34d399;">🟢 Đã xác thực Secret Key</span>' : '<span style="color: #f59e0b;">🟡 Chưa cấu hình Secret Key</span>'}</div>
        </div>

        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
          <a href="/api/v1/portal/projects/${project.id}/license/download-plugin" class="btn-primary" style="font-size: 12px; padding: 8px 12px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; background: #2563eb; color: #fff;">
            📥 Tải Plugin Tự Động Kích Hoạt (.zip)
          </a>
          <button type="button" class="btn-secondary" onclick="openClientLicenseModal(state.activeProject)" style="font-size: 12px; padding: 8px 12px; display: inline-flex; align-items: center; gap: 5px;">
            🔑 Kích Hoạt / Gia Hạn Key
          </button>
          <button type="button" class="btn-secondary" onclick="document.getElementById('cmsConfigBox').classList.toggle('hidden')" style="font-size: 12px; padding: 8px 12px;">
            ⚙️ Cấu hình API thủ công
          </button>
        </div>

        <!-- Inline Config Form -->
        <div id="cmsConfigBox" class="hidden" style="margin-top: 14px; padding-top: 14px; border-top: 1px solid var(--border-subtle);">
          <div style="display: flex; flex-direction: column; gap: 10px;">
            <div>
              <label style="font-size: 11.5px; color: var(--text-muted); display: block; margin-bottom: 4px;">URL Website WordPress của bạn</label>
              <input type="url" id="inputCmsUrl" class="form-input" value="${project.cms_api_url || project.target_website_url || ''}" placeholder="https://your-domain.com" style="width: 100%; padding: 8px; font-size: 12.5px; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-subtle); border-radius: 6px; color: #fff;">
            </div>
            <div>
              <label style="font-size: 11.5px; color: var(--text-muted); display: block; margin-bottom: 4px;">API Secret Key (Lấy từ WP Admin -> Cài đặt -> Omni Bridge)</label>
              <input type="text" id="inputCmsKey" class="form-input" value="${apiKey}" placeholder="obc_..." style="width: 100%; padding: 8px; font-size: 12.5px; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-subtle); border-radius: 6px; color: #fff;">
            </div>
            <button type="button" class="btn-primary" onclick="saveCmsBridge()" style="font-size: 12.5px; padding: 8px 14px; margin-top: 4px;">
              Kiểm tra & Lưu Kết Nối
            </button>
          </div>
        </div>
      </div>

      <!-- 2. GOOGLE SEARCH CONSOLE CARD -->
      <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
          <span class="card-badge" style="background: rgba(99, 102, 241, 0.15); color: #818cf8;">Google Search Console</span>
          <span style="font-size: 11px; font-weight: 600; color: ${isGscConnected ? '#10b981' : '#94a3b8'};">
            ${isGscConnected ? '🟢 ĐÃ KẾT NỐI OAUTH 2.0' : '⚪ CHƯA LIÊN KẾT'}
          </span>
        </div>
        <h4 class="card-title">Hiệu Suất Tìm Kiếm Thực Tế</h4>
        <div style="font-size: 13px; color: var(--text-muted); line-height: 1.6; margin-bottom: 14px;">
          <p><strong>Property URL:</strong> <span style="color: #fff;">${(gscConn && gscConn.property_uri) || project.target_website_url}</span></p>
          <p><strong>Trạng thái:</strong> ${(gscConn && gscConn.status) ? gscConn.status.toUpperCase() : 'Chưa cấp quyền OAuth'}</p>
          <p><strong>Đồng bộ lần cuối:</strong> ${(gscConn && gscConn.last_sync_at) ? gscConn.last_sync_at : (gscConn ? 'Vừa kết nối' : 'Chưa đồng bộ')}</p>
        </div>
        <div>
          <a href="/api/v1/projects/${project.id}/gsc/connect?tenant_id=${project.tenant_id}" target="_blank" class="btn-primary" style="font-size: 12px; padding: 8px 14px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
            🔗 Kết Nối Google Search Console
          </a>
        </div>
      </div>
    </div>
  `;
}

async function saveCmsBridge() {
  const urlInput = document.getElementById('inputCmsUrl');
  const keyInput = document.getElementById('inputCmsKey');
  const url = urlInput ? urlInput.value.trim() : '';
  const key = keyInput ? keyInput.value.trim() : '';

  if (!url || !key || !state.activeProjectId) {
    alert('Vui lòng nhập đầy đủ URL website và API Secret Key.');
    return;
  }

  try {
    const res = await fetch(`/api/v1/portal/projects/${state.activeProjectId}/cms-bridge`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        cms_api_url: url,
        api_key: key
      })
    });
    const data = await res.json();
    if (res.ok) {
      alert('Kết nối WordPress Omni Bridge thành công!');
      loadProjectIntelligence(state.activeProjectId);
    } else {
      alert('Lỗi kết nối: ' + (data.detail || data.message || 'Không thể kết nối'));
    }
  } catch (err) {
    alert('Lỗi gửi yêu cầu kết nối: ' + err.message);
  }
}
