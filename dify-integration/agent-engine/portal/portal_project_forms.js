function showToast(message, type = 'info') {
  let container = document.getElementById('toastContainer');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toastContainer';
    document.body.appendChild(container);
  }
  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  toast.innerHTML = `<span>${message}</span>`;
  container.appendChild(toast);
  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateX(50px)';
    toast.style.transition = 'all 0.3s ease';
    setTimeout(() => toast.remove(), 300);
  }, 4500);
}

function clientNormalizeUrl(raw) {
  let val = (raw || '').trim();
  if (!val) return '';
  const hasExplicitHttps = /^https:\/\//i.test(val);
  const isIpOrLocal = /^(?:https?:\/\/)?(?:\d+\.\d+\.\d+\.\d+|localhost|127\.)(?::\d+)?/i.test(val);
  const scheme = (hasExplicitHttps || !isIpOrLocal) ? 'https' : 'http';
  val = val.replace(/^(?:https?|ht+p+s?|h+tp+s?)\s*[:/]+\s*/gi, '').trim();
  val = val.replace(/\s+(vn|com|top|net|org|edu|info|pro|online|xyz)\b/gi, '.$1');
  val = val.replace(/\s+/g, '');
  return `${scheme}://${val}`;
}
window.clientNormalizeUrl = clientNormalizeUrl;

async function handleCreateProject(e) {
  e.preventDefault();
  const name = document.getElementById('projName').value.trim();
  const rawUrl = document.getElementById('projUrl').value.trim();
  const url = clientNormalizeUrl(rawUrl);
  const desc = document.getElementById('projDesc').value.trim();
  const rawSeed = document.getElementById('projSeed').value.trim();
  const seed = rawSeed ? clientNormalizeUrl(rawSeed) : null;
  const location = document.getElementById('projLocation')?.value.trim() || 'Toàn quốc';

  if (!name || !url) {
    showToast('Vui lòng nhập đầy đủ Tên Dự án và Website đích!', 'warning');
    return;
  }

  const submitBtn = document.getElementById('btnSubmitNewProject');
  const cancelBtn = document.getElementById('btnCancelProject');
  const loadingNotice = document.getElementById('newProjectLoadingNotice');

  if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.textContent = '⏳ Đang phân tích web & quét đối thủ...';
  }
  if (cancelBtn) cancelBtn.disabled = true;
  if (loadingNotice) {
    loadingNotice.style.display = 'block';
    loadingNotice.innerHTML = `
      <div style="font-size: 13px; font-weight: 600; color: #a5b4fc; margin-bottom: 4px;">🚀 Đang kích hoạt trinh sát tự động...</div>
      <div style="font-size: 12px; color: var(--text-muted); line-height: 1.5;">
        Hệ thống đang cào On-page website đích, bóc tách đối thủ mẫu và xác minh 10 đối thủ SERP trực tiếp trên Google. Quá trình này mất khoảng 10-15 giây.
      </div>
    `;
  }

  try {
    const res = await fetch('/api/v1/portal/projects', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        name,
        target_website_url: url,
        business_description: desc,
        seed_competitor_url: seed || null,
        target_location: location
      })
    });
    
    if (!res.ok) {
      const errData = await res.json().catch(() => ({}));
      throw new Error(errData.detail || 'Không thể tạo dự án.');
    }

    const result = await res.json();
    document.getElementById('newProjectModal').classList.remove('active');
    document.getElementById('newProjectForm').reset();

    showToast('🎉 Dự án đã khởi tạo! Bộ máy Marketing và trinh sát Onpage đã kích hoạt thành công!', 'success');
    
    // Tải lại danh sách và chuyển ngay sang project mới
    if (result.project_id) {
      localStorage.setItem('portal_active_project_id', result.project_id);
    }
    await loadProjects();
    if (result.project_id) {
      switchProject(result.project_id);
      // Tự động chuyển ngay sang Tab 10 Đối thủ để người dùng thấy kết quả tức thì
      switchTab('competitors');
    }
  } catch (err) {
    showToast('Lỗi khởi tạo dự án: ' + err.message, 'danger');
  } finally {
    if (submitBtn) {
      submitBtn.disabled = false;
      submitBtn.textContent = '🚀 Tạo Dự Án & Kích Hoạt Chiến Dịch';
    }
    if (cancelBtn) cancelBtn.disabled = false;
    if (loadingNotice) loadingNotice.style.display = 'none';
  }
}

function openEditProjectModal() {
  if (!state.activeProject || !state.activeProjectId) {
    showToast('Chưa có dự án nào được chọn để chỉnh sửa!', 'warning');
    return;
  }
  const exists = state.projects && state.projects.some(p => p.id === state.activeProjectId);
  if (!exists) {
    showToast('Dự án này không còn tồn tại trên hệ thống.', 'warning');
    renderEmptyState();
    return;
  }
  const p = state.activeProject;
  document.getElementById('editProjName').value = p.name || '';
  document.getElementById('editProjUrl').value = p.target_website_url || '';
  document.getElementById('editProjDesc').value = p.business_description || '';
  document.getElementById('editProjSeed').value = p.seed_competitor_url || '';
  document.getElementById('editProjReanalyze').checked = false;

  const modal = document.getElementById('editProjectModal');
  if (modal) modal.classList.add('active');
}

async function handleUpdateProject(e) {
  e.preventDefault();
  if (!state.activeProjectId) {
    showToast('Không tìm thấy mã dự án cần cập nhật!', 'warning');
    return;
  }

  const name = document.getElementById('editProjName').value.trim();
  const rawUrl = document.getElementById('editProjUrl').value.trim();
  const url = clientNormalizeUrl(rawUrl);
  const desc = document.getElementById('editProjDesc').value.trim();
  const rawSeed = document.getElementById('editProjSeed').value.trim();
  const seed = rawSeed ? clientNormalizeUrl(rawSeed) : null;
  const triggerReanalyze = document.getElementById('editProjReanalyze').checked;

  const submitBtn = document.getElementById('btnSubmitEditProject');
  const originalBtnText = submitBtn.innerHTML;
  submitBtn.disabled = true;
  submitBtn.innerHTML = '<span>⏳ Đang lưu...</span>';

  try {
    const res = await fetch(`/api/v1/portal/projects/${state.activeProjectId}`, {
      method: 'PUT',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        name,
        target_website_url: url,
        business_description: desc,
        seed_competitor_url: seed || null,
        trigger_reanalyze: triggerReanalyze
      })
    });

    if (res.status === 404) {
      document.getElementById('editProjectModal').classList.remove('active');
      showToast('⚠️ Dự án này đã bị xóa hoặc không còn tồn tại trên hệ thống. Đang làm mới...', 'warning');
      localStorage.removeItem('portal_active_project_id');
      await loadProjects();
      if (!state.projects || state.projects.length === 0) {
        renderEmptyState();
      }
      return;
    }

    const result = await res.json();
    if (result.status === 'success') {
      document.getElementById('editProjectModal').classList.remove('active');
      showToast('🎉 Đã cập nhật thông tin dự án thành công!', 'success');
      await loadProjects();
      switchProject(state.activeProjectId);
    } else {
      showToast('Lỗi cập nhật: ' + (result.detail || 'Không xác định'), 'danger');
    }
  } catch (err) {
    showToast('Lỗi kết nối khi cập nhật dự án: ' + err.message, 'danger');
  } finally {
    submitBtn.disabled = false;
    submitBtn.innerHTML = originalBtnText;
  }
}

async function handleDeleteProject() {
  if (!state.activeProject || !state.activeProjectId) {
    alert('Vui lòng chọn dự án cần xóa!');
    return;
  }

  const projName = state.activeProject.name;
  const isConfirmed = confirm(
    `⚠️ CẢNH BÁO QUAN TRỌNG:\n\nBạn có chắc chắn muốn XÓA VĨNH VIỄN dự án "${projName}"?\n\nToàn bộ dữ liệu đối thủ cào được, ma trận từ khóa SERP, chiến lược UVP và lịch sử chat của dự án này sẽ bị xóa sạch khỏi hệ thống và không thể khôi phục!`
  );

  if (!isConfirmed) return;

  try {
    const res = await fetch(`/api/v1/portal/projects/${state.activeProjectId}`, {
      method: 'DELETE'
    });
    const result = await res.json();

    if (result.status === 'success') {
      const editModal = document.getElementById('editProjectModal');
      if (editModal) editModal.classList.remove('active');

      alert(`Đã xóa hoàn toàn dự án "${projName}"!`);
      localStorage.removeItem('portal_active_project_id');

      await loadProjects();
      if (state.projects && state.projects.length > 0) {
        switchProject(state.projects[0].id);
      } else {
        renderEmptyState();
      }
    } else {
      alert('Không thể xóa dự án: ' + (result.detail || 'Lỗi không xác định'));
    }
  } catch (err) {
    alert('Lỗi khi xóa dự án: ' + err.message);
  }
}

// 2. Chat & Persistent History Engine
