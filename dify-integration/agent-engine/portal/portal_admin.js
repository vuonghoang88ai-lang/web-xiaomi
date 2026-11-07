// Super Admin Console Logic (SOP 23 <= 300 lines)
let adminToken = localStorage.getItem('omni_admin_token') || '';
let allLicenses = [];
let currentAdminProject = null;

document.addEventListener('DOMContentLoaded', () => {
  if (!adminToken) {
    showLoginModal();
  } else {
    loadAdminData();
  }
});

function showLoginModal() {
  const modal = document.getElementById('adminLoginModal');
  if (modal) modal.classList.add('active');
}

function hideLoginModal() {
  const modal = document.getElementById('adminLoginModal');
  if (modal) modal.classList.remove('active');
}

async function handleAdminLogin(event) {
  if (event) event.preventDefault();
  const input = document.getElementById('inputMasterKey');
  const key = input ? input.value.trim() : '';
  if (!key) return alert('Vui lòng nhập Master Key.');

  try {
    const res = await fetch('/api/v1/portal/admin/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ master_key: key })
    });
    const data = await res.json();
    if (res.ok && data.status === 'success') {
      adminToken = data.token;
      localStorage.setItem('omni_admin_token', adminToken);
      hideLoginModal();
      loadAdminData();
    } else {
      alert(data.detail || 'Master Key không chính xác.');
    }
  } catch (err) {
    alert('Lỗi đăng nhập: ' + err.message);
  }
}

function handleAdminLogout() {
  localStorage.removeItem('omni_admin_token');
  adminToken = '';
  location.reload();
}

async function fetchAdminApi(endpoint, options = {}) {
  const headers = options.headers || {};
  headers['X-Admin-Key'] = adminToken;
  options.headers = headers;
  const res = await fetch(endpoint, options);
  if (res.status === 401) {
    localStorage.removeItem('omni_admin_token');
    adminToken = '';
    showLoginModal();
    throw new Error('Hết phiên đăng nhập Admin.');
  }
  return res;
}

async function loadAdminData() {
  try {
    // 1. Load Stats
    const statsRes = await fetchAdminApi('/api/v1/portal/admin/stats');
    const statsData = await statsRes.json();
    if (statsData.stats) renderStats(statsData.stats);

    // 2. Load Licenses
    const licRes = await fetchAdminApi('/api/v1/portal/admin/licenses');
    const licData = await licRes.json();
    allLicenses = licData.licenses || [];
    renderLicenseTable(allLicenses);
  } catch (err) {
    console.error('Lỗi tải dữ liệu Admin:', err);
  }
}

function renderStats(s) {
  document.getElementById('statTotalSites').textContent = s.total_sites || 0;
  document.getElementById('statTrialSites').textContent = s.trial_sites || 0;
  document.getElementById('statPaidSites').textContent = s.paid_sites || 0;
  document.getElementById('statExpiringSoon').textContent = s.expiring_soon || 0;
  document.getElementById('statExpiredSites').textContent = s.expired_sites || 0;
}

function renderLicenseTable(items) {
  const tbody = document.getElementById('adminLicenseTbody');
  if (!tbody) return;

  if (items.length === 0) {
    tbody.innerHTML = '<tr><td colspan="7" style="text-align: center; color: var(--admin-muted); padding: 30px;">Không tìm thấy website nào.</td></tr>';
    return;
  }

  tbody.innerHTML = items.map(item => {
    const isExp = item.is_expired;
    const statusColor = isExp ? '#ef4444' : (item.plan_status === 'suspended' ? '#f59e0b' : '#10b981');
    const statusBg = isExp ? 'rgba(239, 68, 68, 0.15)' : (item.plan_status === 'suspended' ? 'rgba(245, 158, 11, 0.15)' : 'rgba(16, 185, 129, 0.15)');

    return `
      <tr>
        <td>
          <strong style="color: #60a5fa; font-size: 13.5px;">${item.domain}</strong>
          <div style="font-size: 11px; color: var(--admin-muted); display:flex; align-items:center; gap:4px; margin-top:2px;">
            <span>${item.project_name || ''}</span>
            <span style="font-size: 10px; background: rgba(99,102,241,0.2); color: #a5b4fc; padding: 1px 5px; border-radius: 4px;">🏢 ${item.tenant_name || 'Enterprise'}</span>
          </div>
        </td>
        <td>
          <span class="admin-badge" style="background: ${statusBg}; color: ${statusColor};">
            ${item.plan_label}
          </span>
        </td>
        <td>
          <strong style="color: ${item.days_left <= 3 ? '#ef4444' : '#fff'};">${item.days_left} ngày</strong>
          <div style="font-size: 11px; color: var(--admin-muted);">${item.expires_at ? new Date(item.expires_at).toLocaleDateString('vi-VN') : 'Vô hạn'}</div>
        </td>
        <td>
          <code style="font-size: 11.5px; color: #fde047;">${item.license_key}</code>
          <button type="button" onclick="navigator.clipboard.writeText('${item.license_key}'); alert('Đã sao chép License Key!');" style="background:none; border:none; color:#38bdf8; cursor:pointer; font-size:12px;" title="Sao chép">📋</button>
        </td>
        <td>
          <span style="font-size: 11.5px; color: ${item.last_heartbeat_at ? '#34d399' : '#94a3b8'};">
            ${item.last_heartbeat_at ? '🟢 ' + new Date(item.last_heartbeat_at).toLocaleTimeString('vi-VN') : '⚪ Chưa kết nối'}
          </span>
        </td>
        <td>
          <div style="display: flex; gap: 6px; flex-wrap: wrap;">
            <button type="button" class="admin-btn admin-btn-primary" onclick="openAdminPlanModal('${item.project_id}', '${item.domain}', '${item.plan_type}')" title="Cấp gói / Gia hạn">
              ⚡ Gói Cước
            </button>
            <a href="/api/v1/portal/projects/${item.project_id}/license/download-plugin" class="admin-btn admin-btn-secondary" style="text-decoration:none;" title="Tải Zip cá nhân hóa">
              📥 Zip
            </a>
            <button type="button" class="admin-btn ${item.plan_status === 'suspended' ? 'admin-btn-primary' : 'admin-btn-danger'}" onclick="toggleSiteStatus('${item.project_id}', '${item.plan_status}')">
              ${item.plan_status === 'suspended' ? 'Mở Khóa' : 'Khóa'}
            </button>
            <button type="button" class="admin-btn" style="background:rgba(239,68,68,0.2); border:1px solid #ef4444; color:#fca5a5;" onclick="deleteSiteAdmin('${item.project_id}', '${item.domain}')" title="Xóa vĩnh viễn dự án">
              🗑️ Xóa
            </button>
          </div>
        </td>
      </tr>
    `;
  }).join('');
}

function filterLicenses() {
  const query = (document.getElementById('searchDomainInput')?.value || '').toLowerCase();
  const filter = document.getElementById('filterPlanStatus')?.value || 'all';

  let filtered = allLicenses.filter(item => {
    const matchQuery = item.domain.toLowerCase().includes(query) || (item.project_name || '').toLowerCase().includes(query);
    if (!matchQuery) return false;

    if (filter === 'trial') return item.plan_type === 'trial_7d' && !item.is_expired;
    if (filter === 'paid') return item.plan_type !== 'trial_7d' && !item.is_expired;
    if (filter === 'expiring') return !item.is_expired && item.days_left <= 7;
    if (filter === 'expired') return item.is_expired || item.plan_status === 'expired';
    return true;
  });

  renderLicenseTable(filtered);
}

function openAdminPlanModal(projectId, domain, planType) {
  currentAdminProject = { id: projectId, domain: domain };
  document.getElementById('adminModalDomain').textContent = domain;
  document.getElementById('selectAdminPlan').value = planType || 'trial_7d';
  const extendCheck = document.getElementById('checkAdminExtendExisting');
  if (extendCheck) extendCheck.checked = false;
  toggleAdminCustomDays();
  document.getElementById('adminPlanModal').classList.add('active');
}

function closeAdminPlanModal() {
  document.getElementById('adminPlanModal').classList.remove('active');
  currentAdminProject = null;
}

function toggleAdminCustomDays() {
  const sel = document.getElementById('selectAdminPlan').value;
  document.getElementById('adminCustomDaysBox').style.display = sel === 'custom' ? 'block' : 'none';
}

async function saveAdminPlan() {
  if (!currentAdminProject) return;
  const planType = document.getElementById('selectAdminPlan').value;
  const customDays = parseInt(document.getElementById('inputAdminCustomDays').value, 10) || 30;
  const extendExisting = document.getElementById('checkAdminExtendExisting')?.checked || false;

  try {
    const res = await fetchAdminApi(`/api/v1/portal/admin/licenses/${currentAdminProject.id}/configure`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        plan_type: planType,
        custom_days: customDays,
        extend_existing: extendExisting
      })
    });
    const data = await res.json();
    if (res.ok && data.status === 'success') {
      alert(data.message || 'Cập nhật gói thành công!');
      closeAdminPlanModal();
      loadAdminData();
    } else {
      alert(data.detail || 'Lỗi lưu gói cước.');
    }
  } catch (err) {
    alert('Lỗi: ' + err.message);
  }
}

async function toggleSiteStatus(projectId, currentStatus) {
  const newStatus = currentStatus === 'suspended' ? 'active' : 'suspended';
  const confirmMsg = newStatus === 'suspended' ? 'Bạn có chắc chắn muốn TẠM KHÓA website này không?' : 'Kích hoạt lại website này?';
  if (!confirm(confirmMsg)) return;

  try {
    const res = await fetchAdminApi(`/api/v1/portal/admin/licenses/${projectId}/toggle-status`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ status: newStatus })
    });
    const data = await res.json();
    if (res.ok && data.status === 'success') {
      alert(data.message);
      loadAdminData();
    } else {
      alert(data.detail || 'Không thể đổi trạng thái.');
    }
  } catch (err) {
    alert('Lỗi: ' + err.message);
  }
}

async function deleteSiteAdmin(projectId, domain) {
  if (!confirm(`CẢNH BÁO NGUY HIỂM:\nBạn có chắc chắn muốn XÓA VĨNH VIỄN website '${domain}' khỏi toàn bộ hệ thống không?\nThao tác này sẽ xóa toàn bộ dữ liệu đối thủ, ma trận từ khóa và license liên quan!`)) return;

  try {
    const res = await fetchAdminApi(`/api/v1/portal/admin/licenses/${projectId}`, { method: 'DELETE' });
    const data = await res.json();
    if (res.ok && data.status === 'success') {
      alert(data.message);
      loadAdminData();
    } else {
      alert(data.detail || 'Không thể xóa website.');
    }
  } catch (err) {
    alert('Lỗi: ' + err.message);
  }
}
