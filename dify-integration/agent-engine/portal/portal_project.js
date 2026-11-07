async function loadProjects() {
  try {
    const res = await fetch('/api/v1/portal/projects');
    const data = await res.json();
    state.projects = data.projects || [];
    
    if (data.user && data.user.email) {
      state.currentUser = data.user.email;
      const displayEmail = data.user.email;
      const displayName = data.user.full_name || displayEmail.split('@')[0].toUpperCase();
      const userNameEl = document.getElementById('userName');
      const userEmailEl = document.getElementById('userEmail');
      const userAvatarEl = document.querySelector('.sidebar-footer .user-avatar');

      if (userNameEl) userNameEl.textContent = displayName;
      if (userEmailEl) userEmailEl.textContent = displayEmail;
      if (userAvatarEl) userAvatarEl.textContent = displayName.slice(0, 2).toUpperCase();
    }

    renderProjectSelector();

    // Khôi phục project cũ từ localStorage khi F5
    const savedProjectId = localStorage.getItem('portal_active_project_id');
    const defaultProject = state.projects.find(p => p.id === savedProjectId) || state.projects[0];

    if (defaultProject) {
      switchProject(defaultProject.id);
    } else {
      renderEmptyState();
    }
  } catch (err) {
    console.error('Lỗi tải danh sách projects:', err);
  }
}

function renderProjectSelector() {
  const selector = document.getElementById('projectSelector');
  const btnEdit = document.getElementById('btnEditProject');
  const btnDel = document.getElementById('btnDeleteProject');
  if (!selector) return;

  if (!state.projects || state.projects.length === 0) {
    selector.innerHTML = '<option value="">-- Chưa có dự án nào --</option>';
    if (btnEdit) btnEdit.style.display = 'none';
    if (btnDel) btnDel.style.display = 'none';
    return;
  }

  if (btnEdit) btnEdit.style.display = 'inline-flex';
  if (btnDel) btnDel.style.display = 'inline-flex';

  selector.innerHTML = state.projects.map(p => `
    <option value="${p.id}">${p.name} (${p.target_website_url.replace('https://', '').replace(/\/$/, '')})</option>
  `).join('');
}

function renderEmptyState() {
  state.activeProjectId = null;
  state.activeProject = null;
  state.keywordMatrix = [];
  if (!state.projects || state.projects.length === 0) {
    localStorage.removeItem('portal_active_project_id');
  }

  const btnEdit = document.getElementById('btnEditProject');
  if (btnEdit) btnEdit.style.display = 'none';

  const btnDel = document.getElementById('btnDeleteProject');
  if (btnDel) btnDel.style.display = 'none';

  const selector = document.getElementById('projectSelector');
  if (selector) selector.value = '';

  const topbarName = document.getElementById('topbarProjectName');
  if (topbarName) topbarName.textContent = 'Chưa chọn dự án';

  const webLink = document.getElementById('topbarWebsiteLink');
  if (webLink) {
    webLink.textContent = '';
    webLink.href = '#';
  }

  const indicator = document.getElementById('sessionIndicator');
  if (indicator) {
    indicator.innerHTML = '<span class="pulse-dot" style="background: var(--text-dim);"></span><span>Chưa kết nối dự án</span>';
  }

  const feed = document.getElementById('chatFeed');
  if (feed) {
    feed.innerHTML = `
      <div style="text-align: center; padding: 60px 20px; color: var(--text-muted); max-width: 600px; margin: 40px auto; background: var(--bg-card); border-radius: var(--radius-lg); border: 1px dashed var(--border-subtle);">
        <div style="font-size: 48px; margin-bottom: 16px;">🚀</div>
        <h3 style="color: var(--text-main); margin-bottom: 10px; font-size: 18px;">Chào mừng bạn đến với Universal Marketing AI Portal</h3>
        <p style="font-size: 13.5px; line-height: 1.6; margin-bottom: 24px; color: var(--text-muted);">
          Hiện tại chưa có dự án nào trong hệ thống. Hãy nhấp vào nút <strong>"+ Tạo mới"</strong> ở thanh menu bên trái để bắt đầu thiết lập thương hiệu, trinh sát đối thủ On-page và kích hoạt CMO AI Copilot!
        </p>
        <button type="button" class="btn-primary" onclick="document.getElementById('newProjectModal').classList.add('active')" style="padding: 10px 24px; font-size: 13.5px; cursor: pointer;">
          + Khởi Tạo Dự Án Mới Ngay
        </button>
      </div>
    `;
  }

  // 1. Dọn dẹp sạch Tab 2: 10 Đối Thủ SERP
  const compContainer = document.getElementById('competitorsGrid');
  if (compContainer) compContainer.innerHTML = '<div style="color: var(--text-dim); padding: 40px; text-align: center;">Chưa có dự án nào. Vui lòng tạo dự án mới để trinh sát đối thủ.</div>';

  const seedSection = document.getElementById('seedCompetitorSection');
  if (seedSection) seedSection.innerHTML = '';

  // 2. Dọn dẹp sạch Tab 3: Ma Trận Từ Khóa & SERP
  const kwAddBar = document.getElementById('keywordAddBar');
  if (kwAddBar) kwAddBar.style.display = 'none';

  const kwActionBtns = document.getElementById('keywordActionButtons');
  if (kwActionBtns) kwActionBtns.style.display = 'none';

  const filterBar = document.getElementById('keywordCategoryFilterBar');
  if (filterBar) filterBar.innerHTML = '';

  const catSelect = document.getElementById('newKeywordCategory');
  if (catSelect) catSelect.innerHTML = '<option value="">-- Chưa có danh mục --</option>';

  const kwInput = document.getElementById('newKeywordInput');
  if (kwInput) kwInput.value = '';

  const kwContainer = document.getElementById('keywordMatrixContainer');
  if (kwContainer) {
    kwContainer.innerHTML = `
      <div style="text-align: center; padding: 50px 20px; color: var(--text-muted); background: var(--bg-card); border-radius: var(--radius-md); border: 1px dashed var(--border-subtle);">
        <div style="font-size: 36px; margin-bottom: 12px;">🔑</div>
        <h4 style="color: var(--text-main); margin-bottom: 8px;">Chưa chọn dự án</h4>
        <p style="font-size: 13px;">Vui lòng tạo hoặc chọn một dự án để xem Ma Trận Từ Khóa & Thứ Hạng SERP Đối Thủ theo từng sản phẩm.</p>
      </div>
    `;
  }

  // 3. Dọn dẹp sạch Tab 4: Chiến Lược UVP
  const uvpContainer = document.getElementById('uvpContent');
  if (uvpContainer) uvpContainer.innerHTML = '<div style="color: var(--text-dim); padding: 40px; text-align: center;">Chưa có dự án nào. Vui lòng tạo dự án để thiết lập Định vị UVP độc quyền.</div>';

  // 4. Dọn dẹp sạch Tab 5: CMS & GSC
  const cmsContainer = document.getElementById('cmsContent');
  if (cmsContainer) cmsContainer.innerHTML = '<div style="color: var(--text-dim); padding: 40px; text-align: center;">Chưa có dự án nào.</div>';
}

function switchProject(projectId) {
  state.isStreaming = false;
  if (typeof updateSendButtonState === 'function') updateSendButtonState(false);
  state.activeProjectId = projectId;
  state.activeProject = state.projects.find(p => p.id === projectId);
  localStorage.setItem('portal_active_project_id', projectId);

  const selector = document.getElementById('projectSelector');
  if (selector) selector.value = projectId;

  // Cập nhật Topbar cơ bản
  if (state.activeProject) {
    if (typeof state.activeProject.brand_profile === 'string') {
      try { state.activeProject.brand_profile = JSON.parse(state.activeProject.brand_profile); } catch (e) {}
    }
    document.getElementById('topbarProjectName').textContent = state.activeProject.name;
    const webLink = document.getElementById('topbarWebsiteLink');
    webLink.textContent = state.activeProject.target_website_url.replace('https://', '').replace(/\/$/, '');
    webLink.href = state.activeProject.target_website_url;
    
    updateSessionIndicator(state.activeProject.dify_conversation_id);
    if (typeof updateQuickPrompts === 'function') {
      updateQuickPrompts(state.activeProject);
    }
  }

  // Tải lịch sử chat & toàn bộ thông tin trí tuệ các tab
  loadChatHistory(projectId);
  if (typeof loadProjectChatSessions === 'function') loadProjectChatSessions(projectId);
  loadProjectIntelligence(projectId);
}

function updateSessionIndicator(cid) {
  const indicator = document.getElementById('sessionIndicator');
  if (!indicator) return;
  const tr = (k, p, def) => (typeof t === 'function' ? t(k, p) : def);
  if (cid) {
    const memLabel = tr('header.session_memory', { id: `<strong>${cid.slice(0, 8)}...</strong>` }, `Bộ nhớ vĩnh cửu: <strong>${cid.slice(0, 8)}...</strong>`);
    indicator.innerHTML = `<span class="pulse-dot"></span><span>${memLabel}</span>`;
    indicator.title = `ID: ${cid}`;
  } else {
    indicator.innerHTML = `
      <span style="width: 8px; height: 8px; border-radius: 50%; background: #94a3b8;"></span>
      <span>${tr('header.session_new', {}, 'Phiên mới (Chưa có ID)')}</span>
    `;
  }
}


