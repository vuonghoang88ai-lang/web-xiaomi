// ==========================================
// PORTAL LICENSE & SUBSCRIPTION MODULE (SOP 23 <= 300 lines)
// RBAC Client View: Read-Only Badge + Key Activation
// ==========================================

let activeLicenseData = null;

async function fetchProjectLicense(projectId) {
  try {
    const res = await fetch(`/api/v1/portal/projects/${projectId}/license`);
    const data = await res.json();
    return data.status === 'success' ? data.license : null;
  } catch (err) {
    console.error('Lỗi tải license:', err);
    return null;
  }
}

async function updateHeaderSubscriptionBadge(project) {
  const p = project || state.activeProject;
  if (!p) return;

  const badgeEl = document.getElementById('headerSubscriptionBadge');
  const labelEl = document.getElementById('headerPlanLabel');
  const daysEl = document.getElementById('headerDaysLeft');
  if (!badgeEl || !labelEl || !daysEl) return;

  const lic = await fetchProjectLicense(p.id);
  activeLicenseData = lic;

  if (!lic) {
    labelEl.textContent = 'Bản quyền: Đang xác thực';
    daysEl.textContent = '--';
    return;
  }

  // Cập nhật trạng thái và màu sắc
  if (lic.is_expired) {
    badgeEl.style.borderColor = 'rgba(239, 68, 68, 0.4)';
    badgeEl.style.color = '#ef4444';
    labelEl.textContent = '🔴 ' + (lic.plan_label || 'Hết Hạn');
    daysEl.textContent = 'Hết hạn';
    daysEl.style.background = 'rgba(239, 68, 68, 0.2)';
    daysEl.style.color = '#fca5a5';
  } else {
    const isTrial = lic.plan_type === 'trial_7d';
    const isEndingSoon = lic.days_left <= 3;

    if (isEndingSoon) {
      badgeEl.style.borderColor = 'rgba(245, 158, 11, 0.4)';
      badgeEl.style.color = '#f59e0b';
      daysEl.style.background = 'rgba(245, 158, 11, 0.2)';
      daysEl.style.color = '#fde68a';
    } else {
      badgeEl.style.borderColor = 'rgba(16, 185, 129, 0.35)';
      badgeEl.style.color = '#34d399';
      daysEl.style.background = 'rgba(16, 185, 129, 0.2)';
      daysEl.style.color = '#a7f3d0';
    }

    const icon = isTrial ? '🎁' : '👑';
    labelEl.textContent = `${icon} ${lic.plan_label || 'Đang hoạt động'}`;
    daysEl.textContent = `Còn ${lic.days_left} ngày`;
  }
}
window.updateHeaderSubscriptionBadge = updateHeaderSubscriptionBadge;

async function openClientLicenseModal(project) {
  const p = project || state.activeProject;
  if (!p) {
    alert('Vui lòng chọn một dự án trước.');
    return;
  }

  const modal = document.getElementById('clientLicenseModal');
  if (!modal) return;

  const domainEl = document.getElementById('clientModalDomain');
  const planBadgeEl = document.getElementById('clientModalPlanBadge');
  const expiresEl = document.getElementById('clientModalExpires');
  const daysLeftEl = document.getElementById('clientModalDaysLeft');

  const domainHost = (p.target_website_url || p.name || '').replace(/^https?:\/\//, '').replace(/^www\./, '').split('/')[0];
  if (domainEl) domainEl.textContent = domainHost;

  const lic = await fetchProjectLicense(p.id);
  activeLicenseData = lic;

  if (lic) {
    if (planBadgeEl) {
      planBadgeEl.textContent = lic.plan_label || lic.plan_type;
      planBadgeEl.style.background = lic.is_expired ? 'rgba(239, 68, 68, 0.2)' : 'rgba(16, 185, 129, 0.2)';
      planBadgeEl.style.color = lic.is_expired ? '#ef4444' : '#34d399';
    }
    if (expiresEl) {
      expiresEl.textContent = lic.expires_at ? new Date(lic.expires_at).toLocaleDateString('vi-VN') : 'Chưa kích hoạt';
    }
    if (daysLeftEl) {
      daysLeftEl.textContent = lic.is_expired ? 'Đã hết hạn' : `${lic.days_left} ngày`;
      daysLeftEl.style.color = lic.is_expired ? '#ef4444' : '#fde047';
    }
  }

  modal.classList.add('active');
}
window.openClientLicenseModal = openClientLicenseModal;

function closeClientLicenseModal() {
  const modal = document.getElementById('clientLicenseModal');
  if (modal) modal.classList.remove('active');
}
window.closeClientLicenseModal = closeClientLicenseModal;

async function activateClientLicenseKey() {
  if (!state.activeProjectId) return;
  const input = document.getElementById('inputClientLicenseKey');
  const btn = document.getElementById('btnSubmitClientKey');
  const key = input ? input.value.trim() : '';

  if (!key) {
    alert('Vui lòng nhập License Key hợp lệ.');
    return;
  }

  if (btn) btn.disabled = true;

  try {
    const res = await fetch(`/api/v1/portal/projects/${state.activeProjectId}/license/activate`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ license_key: key })
    });
    const data = await res.json();
    if (res.ok && data.status === 'success') {
      alert(data.message || 'Kích hoạt bản quyền thành công!');
      input.value = '';
      closeClientLicenseModal();
      if (typeof loadProjectIntelligence === 'function') {
        await loadProjectIntelligence(state.activeProjectId);
      }
    } else {
      alert(data.detail || data.message || 'License Key không hợp lệ hoặc không thuộc về tên miền này.');
    }
  } catch (err) {
    alert('Lỗi gửi yêu cầu kích hoạt: ' + err.message);
  } finally {
    if (btn) btn.disabled = false;
  }
}
window.activateClientLicenseKey = activateClientLicenseKey;

function copyLicenseKey(key) {
  if (!key) return;
  navigator.clipboard.writeText(key).then(() => {
    alert('Đã sao chép License Key vào Clipboard!');
  }).catch(() => {
    prompt('Sao chép License Key:', key);
  });
}
window.copyLicenseKey = copyLicenseKey;
