// Universal Marketing AI Portal v3.0 - Frontend Engine
let state = {
  projects: [],
  activeProjectId: null,
  activeProject: null,
  currentTab: 'chat',
  isStreaming: false,
  currentUser: 'admin@marketing-ai.internal',
  keywordMatrix: [],
  selectedKeywordCategory: 'all'
};

// Initialize Application
document.addEventListener('DOMContentLoaded', () => {
  initApp();
  setupEventListeners();
});

async function initApp() {
  await loadProjects();
}

function setupEventListeners() {
  // Project Switcher
  const selector = document.getElementById('projectSelector');
  if (selector) {
    selector.addEventListener('change', (e) => {
      switchProject(e.target.value);
    });
  }

  // Navigation Tabs
  document.querySelectorAll('.nav-item').forEach(btn => {
    btn.addEventListener('click', () => {
      const tab = btn.getAttribute('data-tab');
      switchTab(tab);
    });
  });

  // Chat Textarea
  const textarea = document.getElementById('chatInput');
  if (textarea) {
    textarea.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
      }
    });
    textarea.addEventListener('input', () => {
      textarea.style.height = 'auto';
      textarea.style.height = Math.min(textarea.scrollHeight, 140) + 'px';
    });
  }

  // Send Button
  const sendBtn = document.getElementById('btnSend');
  if (sendBtn) {
    sendBtn.addEventListener('click', sendMessage);
  }

  // Reset Chat Button
  const resetBtn = document.getElementById('btnResetChat');
  if (resetBtn) {
    resetBtn.addEventListener('click', resetChatSession);
  }

  // Modal Dialogs - New Project
  const btnNewProj = document.getElementById('btnNewProject');
  const modal = document.getElementById('newProjectModal');
  const closeModal = document.getElementById('closeModal');
  const btnCancel = document.getElementById('btnCancelProject');
  const form = document.getElementById('newProjectForm');

  if (btnNewProj && modal) {
    btnNewProj.addEventListener('click', () => modal.classList.add('active'));
  }
  if (closeModal && modal) {
    closeModal.addEventListener('click', () => modal.classList.remove('active'));
  }
  if (btnCancel && modal) {
    btnCancel.addEventListener('click', () => modal.classList.remove('active'));
  }
  if (form) {
    form.addEventListener('submit', handleCreateProject);
  }

  // Modal Dialogs - Edit & Delete Project
  const btnEditProj = document.getElementById('btnEditProject');
  const btnDelProj = document.getElementById('btnDeleteProject');
  const editModal = document.getElementById('editProjectModal');
  const closeEditModal = document.getElementById('closeEditModal');
  const btnCancelEdit = document.getElementById('btnCancelEditProject');
  const editForm = document.getElementById('editProjectForm');
  const btnDeleteFromModal = document.getElementById('btnDeleteFromEditModal');

  if (btnEditProj) {
    btnEditProj.addEventListener('click', openEditProjectModal);
  }
  if (btnDelProj) {
    btnDelProj.addEventListener('click', handleDeleteProject);
  }
  if (closeEditModal && editModal) {
    closeEditModal.addEventListener('click', () => editModal.classList.remove('active'));
  }
  if (btnCancelEdit && editModal) {
    btnCancelEdit.addEventListener('click', () => editModal.classList.remove('active'));
  }
  if (editForm) {
    editForm.addEventListener('submit', handleUpdateProject);
  }
  if (btnDeleteFromModal) {
    btnDeleteFromModal.addEventListener('click', handleDeleteProject);
  }

  // Báo Cáo Web Mục Tiêu Actions
  const btnRescanSite = document.getElementById('btnRescanMySite');
  if (btnRescanSite) {
    btnRescanSite.addEventListener('click', rescanMySite);
  }
  const btnAskOnpage = document.getElementById('btnAskCopilotFixOnpage');
  if (btnAskOnpage) {
    btnAskOnpage.addEventListener('click', askCopilotFixOnpage);
  }
}

// 1. Projects Management
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
  state.selectedKeywordCategory = 'all';
  localStorage.removeItem('portal_active_project_id');

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

function updateKeywordCategoryDropdown(project, matrix, seedAnalysis) {
  const catSelect = document.getElementById('newKeywordCategory');
  if (!catSelect) return;

  if (!project) {
    catSelect.innerHTML = '<option value="">-- Chưa có danh mục --</option>';
    return;
  }

  // 1. Hàm chuẩn hóa viết hoa Title Case cho tiếng Việt
  const toTitleCase = (str) => {
    if (!str) return '';
    return str.trim().split(/\s+/).map(word => {
      if (word.length === 0) return '';
      return word.charAt(0).toUpperCase() + word.slice(1).toLowerCase();
    }).join(' ');
  };

  // 2. Hàm lọc và làm sạch rác (email, tel, trang hệ thống, kích thước inch)
  const cleanCategoryText = (text) => {
    if (!text || typeof text !== 'string') return null;
    const t = text.trim();
    const tl = t.toLowerCase();

    // Bỏ email, hotline, link nội bộ, text hệ thống
    if (tl.includes('@') || tl.includes('email') || tl.includes('tel:') || tl.includes('hotline') || tl.includes('[email')) return null;
    if (tl.includes('chính sách') || tl.includes('tin tức') || tl.includes('liên hệ') || tl.includes('giới thiệu') || tl.includes('trang chủ') || tl.includes('xem tất cả')) return null;
    if (t.length < 2 || t.length > 40) return null;

    // Không để biến thể kích cỡ màn hình Tivi (32 inch, 43 inch, 55 inch...) làm nghẽn danh mục cấp 1
    if (/\b\d{2,3}\s*(?:inch|"|in)\b/i.test(tl) && (tl.includes('tivi') || tl.includes('tv'))) {
      return null;
    }

    return toTitleCase(t);
  };

  const candidateCategories = [];

  // 3. NGUỒN 1: Bóc tách ĐỘNG 100% từ Mô tả kinh doanh (NLP Universal Chunker - Áp dụng cho mọi ngành nghề)
  const rawDesc = project.business_description || '';
  if (rawDesc.trim()) {
    // Tách theo dấu phẩy, chấm phẩy, gạch đầu dòng hoặc từ nối
    const chunks = rawDesc.split(/[,;\n\r\t•|/]|(?:\s+(?:và|hoặc|kèm|chuyên)\s+)/i);
    const stopPrefixes = ['bán', 'chuyên bán', 'chuyên', 'cung cấp', 'phân phối', 'kinh doanh', 'dịch vụ', 'cửa hàng', 'shop', 'tổng kho', 'hệ thống', 'đại lý', 'chúng tôi'];
    
    chunks.forEach(chunk => {
      let c = chunk.trim();
      if (!c || c.length < 2 || c.length > 40) return;
      let cl = c.toLowerCase();
      for (const pfx of stopPrefixes) {
        if (cl.startsWith(pfx + ' ')) {
          cl = cl.slice(pfx.length).trim();
          break;
        }
      }
      const cleaned = cleanCategoryText(cl);
      if (cleaned) candidateCategories.push(cleaned);
    });
  }

  // 4. NGUỒN 2: Lấy từ cấu trúc danh mục bóc tách từ đối thủ mẫu thực tế của dự án
  const seedCats = (seedAnalysis && seedAnalysis.category_structure) || 
                   (project.brand_profile && project.brand_profile.seed_analysis && project.brand_profile.seed_analysis.category_structure) || [];
  if (Array.isArray(seedCats)) {
    seedCats.forEach(c => {
      const cleaned = cleanCategoryText(c);
      if (cleaned) candidateCategories.push(cleaned);
    });
  }

  // 5. NGUỒN 3: Lấy từ Ma trận từ khóa hiện có của dự án
  if (matrix && Array.isArray(matrix)) {
    matrix.forEach(item => {
      const cleaned = cleanCategoryText(item.category);
      if (cleaned) candidateCategories.push(cleaned);
    });
  }

  // 6. NGUỒN 4: Lấy từ menu của website chính mình
  const myNav = (project.brand_profile && project.brand_profile.my_site_audit && project.brand_profile.my_site_audit.key_services_or_nav) || [];
  if (Array.isArray(myNav)) {
    myNav.forEach(c => {
      const cleaned = cleanCategoryText(c);
      if (cleaned) candidateCategories.push(cleaned);
    });
  }

  // 7. THUẬT TOÁN KHỬ TRÙNG LẶP & ĐỒNG BỘ HÓA TÊN DANH MỤC (Deduplication & Canonicalization)
  // Xử lý các cặp bị trùng lặp như: "Tivi" và "Tivi Xiaomi", "Máy Rửa Bát Xiaomi" và "máy rửa bát xiaomi"
  const uniqueList = [];
  const normalizedMap = new Map(); // lowercase -> canonical name

  candidateCategories.forEach(cat => {
    const lower = cat.toLowerCase();
    if (!normalizedMap.has(lower)) {
      normalizedMap.set(lower, cat);
    }
  });

  const allNames = Array.from(normalizedMap.values());

  allNames.forEach(cat => {
    const lower = cat.toLowerCase();
    // Nếu trong danh sách đã có tên đầy đủ hơn chứa từ khóa này (ví dụ đã có "Tivi Xiaomi" thì bỏ từ "Tivi" lẻ)
    const hasMoreSpecific = allNames.some(other => {
      const otherLower = other.toLowerCase();
      return otherLower !== lower && otherLower.includes(lower) && (otherLower.length - lower.length <= 15);
    });

    if (!hasMoreSpecific) {
      if (!uniqueList.includes(cat)) {
        uniqueList.push(cat);
      }
    }
  });

  // 8. RENDER DROPDOWN (Nếu chưa có dữ liệu thì dùng Fallback trung tính, tuyệt đối không fix cứng tên sản phẩm nào)
  if (uniqueList.length === 0) {
    catSelect.innerHTML = `
      <option value="Sản phẩm chủ lực">📦 Sản phẩm chủ lực</option>
      <option value="Dịch vụ trọng tâm">⚙️ Dịch vụ trọng tâm</option>
      <option value="Danh mục chung">🌐 Danh mục chung</option>
      <option value="Khác">➕ Khác...</option>
    `;
  } else {
    catSelect.innerHTML = uniqueList.map(cat => {
      let icon = '📂';
      const cl = cat.toLowerCase();
      if (cl.includes('tivi') || cl.includes('tv')) icon = '📺';
      else if (cl.includes('robot')) icon = '🤖';
      else if (cl.includes('giặt') || cl.includes('sấy')) icon = '🧺';
      else if (cl.includes('điều hòa') || (cl.includes('lạnh') && !cl.includes('tủ lạnh'))) icon = '❄️';
      else if (cl.includes('quạt')) icon = '💨';
      else if (cl.includes('tủ lạnh')) icon = '🧊';
      else if (cl.includes('nước') || cl.includes('lọc nước')) icon = '💧';
      else if (cl.includes('khí tươi') || cl.includes('không khí')) icon = '🍃';
      else if (cl.includes('bếp') || cl.includes('nồi') || cl.includes('gia dụng')) icon = '🍳';
      else if (cl.includes('xe') || cl.includes('ô tô')) icon = '🚗';
      else if (cl.includes('nhà') || cl.includes('bất động sản') || cl.includes('căn hộ')) icon = '🏢';
      else if (cl.includes('thời trang') || cl.includes('quần') || cl.includes('áo') || cl.includes('váy')) icon = '👗';
      else if (cl.includes('mỹ phẩm') || cl.includes('kem') || cl.includes('da') || cl.includes('spa')) icon = '💄';
      else if (cl.includes('nha khoa') || cl.includes('răng') || cl.includes('y tế') || cl.includes('khám')) icon = '🦷';
      else if (cl.includes('luật') || cl.includes('pháp lý')) icon = '⚖️';
      else if (cl.includes('giáo dục') || cl.includes('khóa học') || cl.includes('đào tạo')) icon = '🎓';

      return `<option value="${cat}">${icon} ${cat}</option>`;
    }).join('') + '<option value="Khác">➕ Khác...</option>';
  }
}


function switchProject(projectId) {
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
    updateQuickPrompts(state.activeProject);
  }

  // Tải lịch sử chat & toàn bộ thông tin trí tuệ các tab
  loadChatHistory(projectId);
  loadProjectIntelligence(projectId);
}

function updateSessionIndicator(cid) {
  const indicator = document.getElementById('sessionIndicator');
  if (!indicator) return;
  if (cid) {
    indicator.innerHTML = `
      <span class="pulse-dot"></span>
      <span>Bộ nhớ vĩnh cửu: <strong>${cid.slice(0, 8)}...</strong></span>
    `;
    indicator.title = `ID hội thoại Dify: ${cid} (F5 không mất)`;
  } else {
    indicator.innerHTML = `
      <span style="width: 8px; height: 8px; border-radius: 50%; background: #94a3b8;"></span>
      <span>Phiên mới (Chưa có ID)</span>
    `;
  }
}

function updateQuickPrompts(project) {
  const container = document.querySelector('.quick-prompts');
  if (!container || !project) return;

  const projName = project.name || 'Dự án';
  const urlClean = (project.target_website_url || '').replace('https://', '').replace(/\/$/, '');

  const prompts = [
    {
      label: `📊 So sánh 10 đối thủ của ${projName}`,
      prompt: `Hãy so sánh bảng giá và các điểm mạnh/yếu của 10 đối thủ cạnh tranh với website ${urlClean}`
    },
    {
      label: `✍️ Lên bài viết chuẩn SOP 12`,
      prompt: `Viết brief SEO và lập dàn ý Silo bài viết theo chuẩn SOP 12 cho dự án ${projName}`
    },
    {
      label: `🔍 Trinh sát liên kết đối thủ`,
      prompt: `Trinh sát các website đối thủ hàng đầu đang bán sản phẩm tương tự ${projName}`
    },
    {
      label: `🎯 Xem lại 4 phương án UVP`,
      prompt: `Hãy tóm tắt và đánh giá 4 phương án UVP độc quyền của dự án ${projName}`
    }
  ];

  container.innerHTML = prompts.map(p => `
    <div class="prompt-pill" data-prompt="${p.prompt}">
      ${p.label}
    </div>
  `).join('');

  container.querySelectorAll('.prompt-pill').forEach(pill => {
    pill.addEventListener('click', () => {
      const promptText = pill.getAttribute('data-prompt');
      if (promptText && !state.isStreaming) {
        document.getElementById('chatInput').value = promptText;
        sendMessage();
      }
    });
  });
}

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

async function handleCreateProject(e) {
  e.preventDefault();
  const name = document.getElementById('projName').value.trim();
  const url = document.getElementById('projUrl').value.trim();
  const desc = document.getElementById('projDesc').value.trim();
  const seed = document.getElementById('projSeed').value.trim();
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
  const url = document.getElementById('editProjUrl').value.trim();
  const desc = document.getElementById('editProjDesc').value.trim();
  const seed = document.getElementById('editProjSeed').value.trim();
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
async function loadChatHistory(projectId) {
  const feed = document.getElementById('chatFeed');
  if (!feed) return;

  feed.innerHTML = `
    <div style="text-align: center; padding: 40px; color: var(--text-dim);">
      <div class="typing-dots"><span></span><span></span><span></span></div>
      <p style="margin-top: 10px; font-size: 13px;">Đang tải lại trí nhớ dự án từ Dify...</p>
    </div>
  `;

  try {
    const res = await fetch(`/api/v1/portal/projects/${projectId}/chat-history?user=${encodeURIComponent(state.currentUser)}`);
    const data = await res.json();
    
    updateSessionIndicator(data.conversation_id);
    if (state.activeProject) {
      state.activeProject.dify_conversation_id = data.conversation_id;
    }

    feed.innerHTML = '';

    if (!data.messages || data.messages.length === 0) {
      renderWelcomeMessage();
    } else {
      data.messages.forEach(msg => {
        appendMessage(msg.role, msg.content);
      });
      scrollToBottom();
    }
  } catch (err) {
    console.error('Lỗi tải lịch sử chat:', err);
    feed.innerHTML = '<div style="color: var(--danger); padding: 20px;">Không thể tải lịch sử trò chuyện.</div>';
  }
}

function renderWelcomeMessage() {
  const feed = document.getElementById('chatFeed');
  if (!feed) return;
  const p = state.activeProject;
  const projName = p ? p.name : 'Dự án';
  
  let bp = p ? p.brand_profile : null;
  if (typeof bp === 'string') {
    try { bp = JSON.parse(bp); } catch (e) { bp = {}; }
  }
  const briefing = (bp && bp.kickoff_briefing) || (p && p.kickoff_briefing) || (state.activeIntelligence && state.activeIntelligence.kickoff_briefing);

  if (briefing) {
    feed.innerHTML = `
      <div class="message-row assistant">
        <div class="avatar ai-avatar">
          <svg fill="currentColor" viewBox="0 0 24 24"><path d="M12 2a2 2 0 0 1 2 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 0 1 7 7h1a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v1a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-1H2a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h1a7 7 0 0 1 7-7h1V5.73c-.6-.34-1-.99-1-1.73a2 2 0 0 1 2-2M7.5 13A2.5 2.5 0 0 0 5 15.5 2.5 2.5 0 0 0 7.5 18a2.5 2.5 0 0 0 2.5-2.5A2.5 2.5 0 0 0 7.5 13m9 0a2.5 2.5 0 0 0-2.5 2.5 2.5 2.5 0 0 0 2.5 2.5 2.5 2.5 0 0 0 2.5-2.5 2.5 2.5 0 0 0-2.5-2.5"/></svg>
        </div>
        <div class="bubble kickoff-briefing-bubble">
          ${parseMarkdown(briefing)}
        </div>
      </div>
    `;

    // Gắn sự kiện cho các action link tương tác trong bản tin báo cáo
    feed.querySelectorAll('a[href^="#action-"]').forEach(link => {
      link.addEventListener('click', (e) => {
        e.preventDefault();
        const href = link.getAttribute('href');
        let promptText = '';
        if (href === '#action-uvp1') {
          promptText = `Tôi muốn phê duyệt Phương án Định vị UVP số 1 làm lợi thế độc quyền cho dự án ${projName}. Hãy lưu vào hồ sơ và hướng dẫn các bước tiếp theo.`;
        } else if (href === '#action-prices') {
          promptText = `Hãy quét và bóc tách bảng giá chi tiết các sản phẩm chủ lực của 10 đối thủ cạnh tranh với ${projName}.`;
        } else if (href === '#action-write-seo') {
          promptText = `Hãy lập dàn ý Silo và chấp bút ngay bài viết chuẩn SEO 2000+ từ theo SOP 12 cho từ khóa tiềm năng nhất của ${projName}.`;
        } else if (href === '#action-plan30') {
          promptText = `Hãy lập kế hoạch xuất bản nội dung 30 ngày cho dự án ${projName} theo cấu trúc cụm chủ đề Topic Cluster.`;
        }
        if (promptText && !state.isStreaming) {
          const chatInput = document.getElementById('chatInput');
          if (chatInput) {
            chatInput.value = promptText;
            sendMessage();
          }
        }
      });
    });
  } else {
    feed.innerHTML = `
      <div class="message-row assistant">
        <div class="avatar ai-avatar">
          <svg fill="currentColor" viewBox="0 0 24 24"><path d="M12 2a2 2 0 0 1 2 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 0 1 7 7h1a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v1a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-1H2a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h1a7 7 0 0 1 7-7h1V5.73c-.6-.34-1-.99-1-1.73a2 2 0 0 1 2-2M7.5 13A2.5 2.5 0 0 0 5 15.5 2.5 2.5 0 0 0 7.5 18a2.5 2.5 0 0 0 2.5-2.5A2.5 2.5 0 0 0 7.5 13m9 0a2.5 2.5 0 0 0-2.5 2.5 2.5 2.5 0 0 0 2.5 2.5 2.5 2.5 0 0 0 2.5-2.5 2.5 2.5 0 0 0-2.5-2.5"/></svg>
        </div>
        <div class="bubble">
          <p>Xin chào! Tôi là <strong>CMO AI Copilot</strong> trực thuộc dự án <strong>${projName}</strong>.</p>
          <p>Toàn bộ trao đổi của chúng ta sẽ được lưu trữ cố định vào phiên làm việc của dự án này (<strong>F5 không bao giờ mất</strong>). Tôi có thể hỗ trợ bạn:</p>
          <ul>
            <li>🔍 <strong>Trinh sát đối thủ</strong>: Cào và so sánh giá bán, cấu trúc Heading, khoảng trống nội dung.</li>
            <li>🎯 <strong>Định vị UVP</strong>: Hoàn thiện lợi thế cạnh tranh độc quyền chống cạnh tranh giá.</li>
            <li>✍️ <strong>Viết bài chuẩn SOP 12</strong>: Lập dàn ý Silo, viết bài 2000+ từ, kiểm định 7 tầng điểm cao.</li>
            <li>🚀 <strong>Xuất bản WordPress</strong>: Đăng bài tự động qua Omni Bridge Connector.</li>
          </ul>
          <p>Hôm nay chúng ta sẽ bắt đầu với chiến dịch nào?</p>
        </div>
      </div>
    `;
  }
}

function appendMessage(role, content) {
  const feed = document.getElementById('chatFeed');
  if (!feed) return;
  const row = document.createElement('div');
  row.className = `message-row ${role}`;

  const avatar = document.createElement('div');
  avatar.className = `avatar ${role === 'user' ? 'user-avatar' : 'ai-avatar'}`;
  if (role === 'user') {
    avatar.textContent = 'U';
  } else {
    avatar.innerHTML = `<svg fill="currentColor" viewBox="0 0 24 24"><path d="M12 2a2 2 0 0 1 2 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 0 1 7 7h1a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v1a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-1H2a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h1a7 7 0 0 1 7-7h1V5.73c-.6-.34-1-.99-1-1.73a2 2 0 0 1 2-2M7.5 13A2.5 2.5 0 0 0 5 15.5 2.5 2.5 0 0 0 7.5 18a2.5 2.5 0 0 0 2.5-2.5A2.5 2.5 0 0 0 7.5 13m9 0a2.5 2.5 0 0 0-2.5 2.5 2.5 2.5 0 0 0 2.5 2.5 2.5 2.5 0 0 0 2.5-2.5 2.5 2.5 0 0 0-2.5-2.5"/></svg>`;
  }

  const bubble = document.createElement('div');
  bubble.className = 'bubble';
  bubble.innerHTML = parseMarkdown(content);

  row.appendChild(avatar);
  row.appendChild(bubble);
  feed.appendChild(row);
  scrollToBottom();
}

async function sendMessage() {
  const input = document.getElementById('chatInput');
  const message = input.value.trim();
  if (!message || state.isStreaming || !state.activeProjectId) return;

  input.value = '';
  input.style.height = 'auto';

  // Render user message
  appendMessage('user', message);

  // Chuẩn bị khung tin nhắn AI dạng streaming
  state.isStreaming = true;
  document.getElementById('btnSend').disabled = true;

  const feed = document.getElementById('chatFeed');
  const aiRow = document.createElement('div');
  aiRow.className = 'message-row assistant';
  aiRow.innerHTML = `
    <div class="avatar ai-avatar">
      <svg fill="currentColor" viewBox="0 0 24 24"><path d="M12 2a2 2 0 0 1 2 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 0 1 7 7h1a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v1a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-1H2a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h1a7 7 0 0 1 7-7h1V5.73c-.6-.34-1-.99-1-1.73a2 2 0 0 1 2-2M7.5 13A2.5 2.5 0 0 0 5 15.5 2.5 2.5 0 0 0 7.5 18a2.5 2.5 0 0 0 2.5-2.5A2.5 2.5 0 0 0 7.5 13m9 0a2.5 2.5 0 0 0-2.5 2.5 2.5 2.5 0 0 0 2.5 2.5 2.5 2.5 0 0 0 2.5-2.5 2.5 2.5 0 0 0-2.5-2.5"/></svg>
    </div>
    <div class="bubble">
      <div class="thought-container" style="display: none;"></div>
      <div class="answer-container"><span class="typing-dots"><span></span><span></span><span></span></span></div>
    </div>
  `;
  feed.appendChild(aiRow);
  scrollToBottom();

  const answerContainer = aiRow.querySelector('.answer-container');
  const thoughtContainer = aiRow.querySelector('.thought-container');
  let accumulatedAnswer = '';
  let accumulatedThought = '';

  try {
    const response = await fetch(`/api/v1/portal/projects/${state.activeProjectId}/chat`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        message: message,
        user: state.currentUser
      })
    });

    const reader = response.body.getReader();
    const decoder = new TextDecoder('utf-8');
    let buffer = '';

    while (true) {
      const { done, value } = await reader.read();
      if (done) break;

      buffer += decoder.decode(value, { stream: true });
      const lines = buffer.split('\n');
      buffer = lines.pop();

      for (const line of lines) {
        const trimmed = line.trim();
        if (trimmed.startsWith('data: ')) {
          const jsonStr = trimmed.slice(6);
          try {
            const chunk = JSON.parse(jsonStr);
            if (chunk.conversation_id && (!state.activeProject.dify_conversation_id || state.activeProject.dify_conversation_id !== chunk.conversation_id)) {
              state.activeProject.dify_conversation_id = chunk.conversation_id;
              updateSessionIndicator(chunk.conversation_id);
            }

            if (chunk.event === 'agent_message' || chunk.event === 'message') {
              accumulatedAnswer += (chunk.answer || '');
              answerContainer.innerHTML = parseMarkdown(accumulatedAnswer);
              scrollToBottom();
            } else if (chunk.event === 'agent_thought') {
              if (chunk.thought) {
                accumulatedThought += chunk.thought + '\n';
                thoughtContainer.style.display = 'block';
                thoughtContainer.innerHTML = `
                  <div class="thought-box">
                    <div class="thought-header">🧠 Suy nghĩ của AI Agent</div>
                    <div class="thought-content">${accumulatedThought}</div>
                  </div>
                `;
              }
            } else if (chunk.event === 'error') {
              answerContainer.innerHTML += `<div style="color: var(--danger); margin-top: 8px;">⚠️ Lỗi: ${chunk.message}</div>`;
            }
          } catch (e) {
            // bỏ qua parse json không đúng định dạng
          }
        }
      }
    }
  } catch (err) {
    answerContainer.innerHTML = `<div style="color: var(--danger);">Không thể kết nối đến máy chủ: ${err.message}</div>`;
  } finally {
    state.isStreaming = false;
    document.getElementById('btnSend').disabled = false;
    document.getElementById('chatInput').focus();
  }
}

async function resetChatSession() {
  if (!state.activeProjectId) return;
  if (!confirm('Bạn có chắc chắn muốn làm mới phiên chat cho dự án này không? Ngữ cảnh trao đổi cũ sẽ được xóa trắng để bắt đầu chiến dịch mới.')) {
    return;
  }

  try {
    await fetch(`/api/v1/portal/projects/${state.activeProjectId}/chat/reset`, { method: 'POST' });
    state.activeProject.dify_conversation_id = null;
    updateSessionIndicator(null);
    renderWelcomeMessage();
  } catch (err) {
    alert('Lỗi khi làm mới phiên chat: ' + err.message);
  }
}

function scrollToBottom() {
  const feed = document.getElementById('chatFeed');
  if (feed) {
    feed.scrollTop = feed.scrollHeight;
  }
}

// 3. Tab Navigation & Extra Views
function switchTab(tabId) {
  state.currentTab = tabId;
  document.querySelectorAll('.nav-item').forEach(btn => {
    btn.classList.toggle('active', btn.getAttribute('data-tab') === tabId);
  });
  document.querySelectorAll('.tab-pane').forEach(pane => {
    pane.classList.toggle('active', pane.id === `tab-${tabId}`);
  });

  // Nếu chuyển sang bất kỳ tab nào mà chưa có dự án, luôn kích hoạt Empty State sạch sẽ (trừ tab Cài đặt)
  if ((!state.activeProjectId || !state.activeProject) && tabId !== 'settings') {
    renderEmptyState();
  }

  // Tự động tải dữ liệu cấu hình khi bấm vào tab Cài đặt
  if (tabId === 'settings') {
    loadSettings();
  }

  // Tự động render tab Báo Cáo Web Mục Tiêu khi bấm vào tab này
  if (tabId === 'my-site' && state.activeProjectId) {
    const container = document.getElementById('mySiteContent');
    if (container && (!container.children || container.children.length === 0 || container.innerHTML.trim() === '')) {
      if (state.activeProject) {
        let bp = state.activeProject.brand_profile || {};
        if (typeof bp === 'string') { try { bp = JSON.parse(bp); } catch (e) { bp = {}; } }
        renderMySiteTab(bp.my_site_audit, state.activeProject, bp.seed_analysis);
      }
      loadProjectIntelligence(state.activeProjectId);
    }
  }
}

// Tải tập trung trí tuệ dự án (Đồng bộ cả 5 Tab: Báo cáo Web Mục Tiêu, Đối thủ, Ma trận Từ khóa, UVP, CMS & GSC)
async function loadProjectIntelligence(projectId) {
  const mySiteContainer = document.getElementById('mySiteContent');
  const compContainer = document.getElementById('competitorsGrid');
  const kwContainer = document.getElementById('keywordMatrixContainer');
  const uvpContainer = document.getElementById('uvpContent');
  const cmsContainer = document.getElementById('cmsContent');

  if (mySiteContainer) mySiteContainer.innerHTML = '<div style="color: var(--text-dim); padding: 20px;">Đang phân tích On-page website mục tiêu...</div>';
  if (compContainer) compContainer.innerHTML = '<div style="color: var(--text-dim); padding: 20px;">Đang tải danh sách 10 đối thủ...</div>';
  if (kwContainer) kwContainer.innerHTML = '<div style="color: var(--text-dim); padding: 20px;">Đang tải ma trận từ khóa SERP...</div>';
  if (uvpContainer) uvpContainer.innerHTML = '<div style="color: var(--text-dim); padding: 20px;">Đang tải chiến lược UVP...</div>';
  if (cmsContainer) cmsContainer.innerHTML = '<div style="color: var(--text-dim); padding: 20px;">Đang kiểm tra kết nối CMS & GSC...</div>';

  try {
    const res = await fetch(`/api/v1/portal/projects/${projectId}`);
    const data = await res.json();
    if (!res.ok || data.detail) {
      throw new Error(data.detail || 'Không thể tải dữ liệu dự án.');
    }

    if (data.project) {
      state.activeProject = data.project;
      if (typeof state.activeProject.brand_profile === 'string') {
        try { state.activeProject.brand_profile = JSON.parse(state.activeProject.brand_profile); } catch (e) {}
      }
      document.getElementById('topbarProjectName').textContent = data.project.name;
      const webLink = document.getElementById('topbarWebsiteLink');
      webLink.textContent = data.project.target_website_url.replace('https://', '').replace(/\/$/, '');
      webLink.href = data.project.target_website_url;
      updateSessionIndicator(data.project.dify_conversation_id);
      updateQuickPrompts(data.project);
    }

    // Trích xuất brand_profile an toàn
    let bp = (data.project && data.project.brand_profile) || (state.activeProject && state.activeProject.brand_profile) || {};
    if (typeof bp === 'string') {
      try { bp = JSON.parse(bp); } catch (e) { bp = {}; }
    }
    const mySiteAudit = data.my_site_audit || bp.my_site_audit;

    const targetAnalysis = data.target_analysis || data.my_site_audit || bp.target_analysis || bp.my_site_audit;
    state.competitors = data.competitors || [];
    state.targetAnalysis = targetAnalysis;

    // Render Báo Cáo Phân Tích Chi Tiết Website Cần Phát Triển
    renderMySiteTab(mySiteAudit, data.project || state.activeProject, data.seed_analysis || bp.seed_analysis);

    // Render 10 Đối Thủ SERP & Khung Web Đích
    renderCompetitorsTab(data.competitors || [], data.latest_report, data.seed_analysis || bp.seed_analysis, data.project || state.activeProject, targetAnalysis);

    // Render Ma Trận Từ Khóa & UVP & CMS
    const masterPool = data.master_keyword_pool || bp.master_keyword_pool || [];
    state.masterKeywordPool = masterPool;
    renderKeywordsTab(data.keyword_matrix || [], data.project || state.activeProject, masterPool);
    renderUvpTab(data.project || state.activeProject);
    renderCmsTab(data.project || state.activeProject, data.gsc_connections || []);
  } catch (err) {
    console.error('Lỗi tải thông tin trí tuệ dự án:', err);
    if (mySiteContainer) mySiteContainer.innerHTML = `<div style="color: var(--danger); padding: 20px;">Lỗi tải dữ liệu audit: ${err.message}</div>`;
    if (compContainer) compContainer.innerHTML = `<div style="color: var(--danger); padding: 20px;">Lỗi tải dữ liệu: ${err.message}</div>`;
  }
}

// BÁO CÁO PHÂN TÍCH CHI TIẾT WEBSITE CẦN PHÁT TRIỂN (ON-PAGE SEO AUDIT)
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
      healthScore += 8;
    }
    if (h2List.length >= 2) healthScore += 10;
    if (navList.length >= 3) healthScore += 5;

    const scoreColor = healthScore >= 75 ? '#10b981' : (healthScore >= 50 ? '#f59e0b' : '#ef4444');
    const scoreLabel = healthScore >= 75 ? 'Khá Tốt • Sẵn sàng đua Top SEO' : (healthScore >= 50 ? 'Trung Bình • Cần khắc phục một số khoảng trống' : 'Cần Tối Ưu Gấp • Chưa đạt chuẩn On-page');

    container.innerHTML = `
    <!-- 1. HEALTH SCORECARD BANNER -->
    <div class="card" style="background: linear-gradient(135deg, rgba(15, 23, 42, 0.9) 0%, rgba(30, 41, 59, 0.8) 100%); border: 1px solid rgba(99, 102, 241, 0.3); border-radius: 12px; padding: 22px; margin-bottom: 20px;">
      <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 18px;">
        <div style="flex: 1; min-width: 280px;">
          <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
            <span style="font-size: 24px;">🌐</span>
            <h3 style="margin: 0; font-size: 18px; color: #f8fafc;">Báo Cáo Sức Khỏe On-page: <span style="color: #60a5fa;">${escapeHtml(project.name)}</span></h3>
          </div>
          <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 10px;">
            Website đích: <a href="${escapeHtml(targetUrl)}" target="_blank" style="color: #38bdf8; text-decoration: underline;">${escapeHtml(targetUrl)}</a>
            • Địa bàn: <strong>${escapeHtml(project.target_location || 'Toàn quốc')}</strong>
          </div>
          <div style="display: inline-flex; align-items: center; gap: 8px; background: rgba(15, 23, 42, 0.6); padding: 5px 12px; border-radius: 20px; border: 1px solid rgba(148, 163, 184, 0.2);">
            <span style="width: 8px; height: 8px; border-radius: 50%; background: ${scoreColor};"></span>
            <span style="font-size: 12px; font-weight: 600; color: ${scoreColor};">${scoreLabel}</span>
          </div>
        </div>

        <div style="display: flex; align-items: center; gap: 16px;">
          <div style="text-align: center; background: rgba(15, 23, 42, 0.75); border: 2px solid ${scoreColor}; border-radius: 14px; padding: 12px 22px;">
            <div style="font-size: 34px; font-weight: 800; color: ${scoreColor}; line-height: 1;">${healthScore}</div>
            <div style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); margin-top: 4px;">Điểm On-page / 100</div>
          </div>
        </div>
      </div>
    </div>

    <!-- 2. GRID 6 THẺ CHỈ SỐ KỸ THUẬT ON-PAGE -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(310px, 1fr)); gap: 16px; margin-bottom: 22px;">
      
      <!-- Card: Trạng Thái Truy Cập -->
      <div class="card" style="background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-subtle); border-radius: 10px; padding: 16px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
          <div style="font-size: 13px; font-weight: 600; color: #e2e8f0; display: flex; align-items: center; gap: 6px;">
            <span>⚡</span> Trạng Thái Máy Chủ
          </div>
          <span style="font-size: 11px; padding: 2px 8px; border-radius: 4px; background: ${isOnline ? 'rgba(16, 185, 129, 0.15)' : 'rgba(239, 68, 68, 0.15)'}; color: ${isOnline ? '#34d399' : '#f87171'}; border: 1px solid ${isOnline ? 'rgba(16, 185, 129, 0.3)' : 'rgba(239, 68, 68, 0.3)'};">
            HTTP ${httpCode} ${isOnline ? 'Online' : 'Lỗi'}
          </span>
        </div>
        <div style="font-size: 12px; color: var(--text-muted);">
          URL kiểm tra: <code style="color: #a5b4fc; word-break: break-all;">${escapeHtml(audit.url || targetUrl)}</code>
        </div>
        <div style="font-size: 11.5px; color: ${isOnline ? '#10b981' : '#ef4444'}; margin-top: 6px;">
          ${isOnline ? '✓ Tốc độ phản hồi đạt chuẩn, Google bot có thể thu thập dữ liệu thuận lợi.' : '⚠️ Website phản hồi chậm hoặc không thể kết nối, cần kiểm tra lại hosting.'}
        </div>
      </div>

      <!-- Card: Tiêu Đề Title -->
      <div class="card" style="background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-subtle); border-radius: 10px; padding: 16px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
          <div style="font-size: 13px; font-weight: 600; color: #e2e8f0; display: flex; align-items: center; gap: 6px;">
            <span>🏷️</span> Tiêu Đề Trang (Title)
          </div>
          <span style="font-size: 11px; padding: 2px 8px; border-radius: 4px; background: rgba(99, 102, 241, 0.15); color: #a5b4fc; border: 1px solid rgba(99, 102, 241, 0.3);">
            ${title.length} ký tự
          </span>
        </div>
        <div style="font-size: 12.5px; font-weight: 600; color: #f8fafc; margin-bottom: 4px; line-height: 1.4;">
          ${title ? escapeHtml(title) : '<span style="color: #ef4444;">(Chưa có thẻ Title)</span>'}
        </div>
        <div style="font-size: 11.5px; color: var(--text-muted);">
          ${title.length >= 35 && title.length <= 65 ? '✓ Độ dài lý tưởng (40-65 ký tự), không bị Google cắt ngắn.' : '⚠️ Tiêu đề nên dài 45-60 ký tự, chứa từ khóa chính + tên thương hiệu.'}
        </div>
      </div>

      <!-- Card: Thẻ Meta Description -->
      <div class="card" style="background: rgba(15, 23, 42, 0.6); border: 1px solid ${metaDesc ? 'var(--border-subtle)' : 'rgba(239, 68, 68, 0.35)'}; border-radius: 10px; padding: 16px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
          <div style="font-size: 13px; font-weight: 600; color: #e2e8f0; display: flex; align-items: center; gap: 6px;">
            <span>📝</span> Thẻ Mô Tả (Meta Description)
          </div>
          <span style="font-size: 11px; padding: 2px 8px; border-radius: 4px; background: ${metaDesc ? 'rgba(99, 102, 241, 0.15)' : 'rgba(239, 68, 68, 0.15)'}; color: ${metaDesc ? '#a5b4fc' : '#f87171'}; border: 1px solid ${metaDesc ? 'rgba(99, 102, 241, 0.3)' : 'rgba(239, 68, 68, 0.3)'};">
            ${metaDesc ? metaDesc.length + ' ký tự' : 'Trống'}
          </span>
        </div>
        <div style="font-size: 12px; color: ${metaDesc ? '#e2e8f0' : '#fca5a5'}; margin-bottom: 4px; line-height: 1.4;">
          ${metaDesc ? escapeHtml(metaDesc) : '🚨 Chưa có thẻ Meta Description! Google sẽ trích xuất chữ ngẫu nhiên, làm giảm mạnh CTR.'}
        </div>
        <div style="font-size: 11.5px; color: var(--text-muted);">
          ${metaDesc ? '✓ Giúp tóm tắt nội dung và tăng tỷ lệ click (CTR).' : '👉 Khuyến nghị: Bổ sung đoạn mô tả 140-155 ký tự có từ khóa và cam kết dịch vụ.'}
        </div>
      </div>

      <!-- Card: Thẻ H1 -->
      <div class="card" style="background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-subtle); border-radius: 10px; padding: 16px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
          <div style="font-size: 13px; font-weight: 600; color: #e2e8f0; display: flex; align-items: center; gap: 6px;">
            <span>📌</span> Thẻ Tiêu Đề Chính (H1)
          </div>
          <span style="font-size: 11px; padding: 2px 8px; border-radius: 4px; background: rgba(99, 102, 241, 0.15); color: #a5b4fc; border: 1px solid rgba(99, 102, 241, 0.3);">
            ${h1List.length} thẻ
          </span>
        </div>
        <div style="font-size: 12px; color: #f8fafc; margin-bottom: 4px;">
          ${h1List.length > 0 ? h1List.map(h => `<div style="margin-bottom: 3px; padding-left: 6px; border-left: 2px solid #6366f1;"><code>${escapeHtml(h)}</code></div>`).join('') : '<span style="color: #ef4444;">Chưa tìm thấy thẻ H1</span>'}
        </div>
        <div style="font-size: 11.5px; color: var(--text-muted);">
          ${h1List.length === 1 ? '✓ Chuẩn cấu trúc: Có đúng 1 thẻ H1 duy nhất trên trang.' : '⚠️ Nên giữ đúng 1 thẻ H1 duy nhất chứa thông điệp giá trị cốt lõi.'}
        </div>
      </div>

      <!-- Card: Thẻ H2 Phân Cấp -->
      <div class="card" style="background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-subtle); border-radius: 10px; padding: 16px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
          <div style="font-size: 13px; font-weight: 600; color: #e2e8f0; display: flex; align-items: center; gap: 6px;">
            <span>📑</span> Cấu Trúc Đề Mục (H2)
          </div>
          <span style="font-size: 11px; padding: 2px 8px; border-radius: 4px; background: rgba(99, 102, 241, 0.15); color: #a5b4fc; border: 1px solid rgba(99, 102, 241, 0.3);">
            ${h2List.length} mục
          </span>
        </div>
        <div style="font-size: 11.5px; color: #cbd5e1; max-height: 80px; overflow-y: auto;">
          ${h2List.length > 0 ? h2List.map(h => `<div style="margin-bottom: 2px;">• ${escapeHtml(h)}</div>`).join('') : '<div style="color: var(--text-dim);">Chưa phát hiện thẻ H2.</div>'}
        </div>
      </div>

      <!-- Card: Nhận Diện Thương Hiệu & Logo -->
      <div class="card" style="background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-subtle); border-radius: 10px; padding: 16px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
          <div style="font-size: 13px; font-weight: 600; color: #e2e8f0; display: flex; align-items: center; gap: 6px;">
            <span>🎨</span> Nhận Diện Thương Hiệu
          </div>
          <span style="font-size: 11px; padding: 2px 8px; border-radius: 4px; background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3);">
            ${logoUrl ? 'Đã có Logo' : 'Chưa có Logo'}
          </span>
        </div>
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
          ${logoUrl ? `<img src="${escapeHtml(logoUrl)}" alt="Logo" style="max-height: 36px; max-width: 90px; object-fit: contain; background: white; padding: 2px 4px; border-radius: 4px;">` : `<div style="width: 36px; height: 36px; border-radius: 6px; background: #334155; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 12px;">TK</div>`}
          <div style="font-size: 12px; color: var(--text-muted);">
            <div>Hotline: <strong style="color: #f8fafc;">${hotlines.length > 0 ? hotlines.join(', ') : 'Chưa bóc tách'}</strong></div>
            <div>Dự án: <strong style="color: #f8fafc;">${escapeHtml(project.name)}</strong></div>
          </div>
        </div>
      </div>

    </div>

    <!-- 3. SILO DANH MỤC & CẤU TRÚC ĐIỀU HƯỚNG BÓC TÁCH ĐƯỢC -->
    <div class="card" style="background: rgba(15, 23, 42, 0.7); border: 1px solid var(--border-subtle); border-radius: 10px; padding: 18px; margin-bottom: 22px;">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
        <div style="font-size: 13.5px; font-weight: 600; color: #e2e8f0; display: flex; align-items: center; gap: 8px;">
          <span>🗂️</span> Cấu Trúc Điều Hướng & Danh Mục Silo Của Website Đích
        </div>
        <span style="font-size: 11.5px; color: var(--text-dim);">${navList.length} mục menu</span>
      </div>

      <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px;">
        ${navList.length > 0 ? navList.map(item => `
          <span style="font-size: 12px; padding: 4px 10px; background: rgba(30, 41, 59, 0.8); border: 1px solid rgba(99, 102, 241, 0.25); border-radius: 6px; color: #e2e8f0;">
            📁 ${escapeHtml(item)}
          </span>
        `).join('') : `
          <div style="color: var(--text-dim); font-size: 12px;">Chưa bóc tách được menu phân cấp từ trang chủ (Có thể trang đang ở trạng thái Landing Page hoặc Sắp ra mắt).</div>
        `}
      </div>

      ${seedAnalysis && seedAnalysis.category_structure && seedAnalysis.category_structure.length > 0 ? `
        <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid rgba(148, 163, 184, 0.1);">
          <div style="font-size: 12px; font-weight: 600; color: #a5b4fc; margin-bottom: 6px;">
            💡 Danh mục Silo cốt lõi của Đối thủ mẫu (${escapeHtml(seedAnalysis.seed_url || 'Tham chiếu')}):
          </div>
          <div style="display: flex; flex-wrap: wrap; gap: 6px;">
            ${seedAnalysis.category_structure.map(cat => `
              <span style="font-size: 11px; padding: 3px 8px; background: rgba(15, 23, 42, 0.6); border: 1px dashed rgba(16, 185, 129, 0.35); border-radius: 4px; color: #34d399;">
                + ${escapeHtml(cat)}
              </span>
            `).join('')}
          </div>
          <div style="font-size: 11px; color: var(--text-dim); margin-top: 5px;">
            👉 Gợi ý: Website cần phát triển nên thiết lập các chuyên mục này để bao phủ toàn bộ bộ từ khóa của đối thủ.
          </div>
        </div>
      ` : ''}
    </div>

    <!-- 3B. DẤU CHÂN TỪ KHÓA ĐA TẦNG CỦA WEBSITE ĐÍCH & PHÂN TÍCH KHOẢNG TRỐNG KEYWORD GAP -->
    ${(() => {
      let bp = project ? project.brand_profile : {};
      if (typeof bp === 'string') { try { bp = JSON.parse(bp); } catch (e) { bp = {}; } }
      const tAudit = state.targetAnalysis || (bp && (bp.target_analysis || bp.my_site_audit)) || audit || {};
      const tClusters = tAudit.keyword_clusters || {};
      const tHeads = tClusters.head || [];
      const tSubcats = tClusters.subcategories || [];
      const tSecs = tClusters.secondary || [];
      const tLongs = tClusters.longtail || [];
      const tTotal = tAudit.total_keywords_count || (tHeads.length + tSubcats.length + tSecs.length + tLongs.length);

      const sClusters = (seedAnalysis && seedAnalysis.keyword_clusters) || {};
      const sHeads = sClusters.head || [];
      const sSubcats = sClusters.subcategories || [];
      const sSecs = sClusters.secondary || [];

      // Tính toán khoảng trống từ khóa: Những từ khóa cốt lõi & ngách đối thủ đang có mà web đích chưa có
      const missingHeads = sHeads.filter(sh => !tHeads.some(th => th.toLowerCase() === sh.toLowerCase()));
      const missingSubcats = sSubcats.filter(ss => !tSubcats.some(ts => ts.toLowerCase() === ss.toLowerCase()));

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
              <div style="font-size: 12px; font-weight: 600; color: #818cf8; margin-bottom: 6px;">📌 1. Ngành Hàng Cốt Lõi (Head Keywords - ${tHeads.length} ngành):</div>
              <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                ${tHeads.map(kw => `<span style="font-size: 11.5px; background: rgba(99, 102, 241, 0.2); color: #c7d2fe; border: 1px solid rgba(99, 102, 241, 0.35); padding: 3px 9px; border-radius: 4px;">🔑 ${escapeHtml(kw)}</span>`).join('')}
              </div>
            </div>
          ` : ''}

          ${tSubcats.length > 0 ? `
            <div style="margin-bottom: 10px;">
              <div style="font-size: 12px; font-weight: 600; color: #34d399; margin-bottom: 6px;">📂 2. Landing Pages Phân Khúc / Kích Thước / Kiểu Dáng (Subcategories - ${tSubcats.length} phân khúc):</div>
              <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                ${tSubcats.map(kw => `<span style="font-size: 11.5px; background: rgba(16, 185, 129, 0.15); color: #6ee7b7; border: 1px solid rgba(16, 185, 129, 0.3); padding: 3px 9px; border-radius: 4px;">📂 ${escapeHtml(kw)}</span>`).join('')}
              </div>
            </div>
          ` : ''}

          ${tSecs.length > 0 ? `
            <div style="margin-bottom: 10px;">
              <div style="font-size: 12px; font-weight: 600; color: #38bdf8; margin-bottom: 6px;">🏷️ 3. Dòng Model Sản Phẩm Đã Nhận Diện (Secondary Specs - ${tSecs.length} model):</div>
              <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                ${tSecs.map(kw => `<span style="font-size: 11px; background: rgba(14, 165, 233, 0.15); color: #7dd3fc; border: 1px solid rgba(56, 189, 248, 0.3); padding: 3px 8px; border-radius: 4px;">🏷️ ${escapeHtml(kw)}</span>`).join('')}
              </div>
            </div>
          ` : ''}

          ${tLongs.length > 0 ? `
            <div style="margin-bottom: 12px;">
              <div style="font-size: 12px; font-weight: 600; color: #fbbf24; margin-bottom: 6px;">🛒 4. Từ Khóa Ý Định Mua Hàng & Giá Kho (Long-tail Intent - ${tLongs.length} từ khóa):</div>
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
    })()}

    <!-- 4. KẾ HOẠCH HÀNH ĐỘNG 5 BƯỚC TỐI ƯU ON-PAGE -->
    <div class="card" style="background: rgba(15, 23, 42, 0.7); border: 1px solid var(--border-subtle); border-radius: 10px; padding: 18px;">
      <div style="font-size: 13.5px; font-weight: 600; color: #e2e8f0; display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
        <span>🎯</span> Kế Hoạch 5 Bước Tối Ưu On-page Trước Khi Viết Bài Chuẩn SEO
      </div>

      <div style="display: flex; flex-direction: column; gap: 8px;">
        <div style="display: flex; gap: 10px; align-items: flex-start; background: rgba(30, 41, 59, 0.4); padding: 10px 12px; border-radius: 6px;">
          <span style="background: #6366f1; color: white; width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: bold; flex-shrink: 0;">1</span>
          <div>
            <div style="font-size: 12.5px; font-weight: 600; color: #f8fafc;">Bổ sung thẻ Meta Description 140 - 155 ký tự</div>
            <div style="font-size: 11.5px; color: var(--text-muted);">Viết mô tả nêu rõ sản phẩm chính hãng, giá sỉ tại kho, bảo hành 24 tháng, địa bàn phục vụ tại ${escapeHtml(project.target_location || 'Hà Nội')}.</div>
          </div>
        </div>

        <div style="display: flex; gap: 10px; align-items: flex-start; background: rgba(30, 41, 59, 0.4); padding: 10px 12px; border-radius: 6px;">
          <span style="background: #6366f1; color: white; width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: bold; flex-shrink: 0;">2</span>
          <div>
            <div style="font-size: 12.5px; font-weight: 600; color: #f8fafc;">Chuẩn hóa 1 thẻ H1 duy nhất chứa Định Vị UVP</div>
            <div style="font-size: 11.5px; color: var(--text-muted);">Thay thế tiêu đề H1 mặc định bằng định vị độc quyền (duyệt từ Tab UVP) để Google xác định rõ trọng tâm website.</div>
          </div>
        </div>

        <div style="display: flex; gap: 10px; align-items: flex-start; background: rgba(30, 41, 59, 0.4); padding: 10px 12px; border-radius: 6px;">
          <span style="background: #6366f1; color: white; width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: bold; flex-shrink: 0;">3</span>
          <div>
            <div style="font-size: 12.5px; font-weight: 600; color: #f8fafc;">Xây dựng cấu trúc Silo danh mục cấp 1 & cấp 2</div>
            <div style="font-size: 11.5px; color: var(--text-muted);">Tạo cây danh mục theo các nhóm từ khóa vàng: Tivi Xiaomi, Tủ lạnh Xiaomi, Máy giặt Xiaomi, Robot hút bụi.</div>
          </div>
        </div>

        <div style="display: flex; gap: 10px; align-items: flex-start; background: rgba(30, 41, 59, 0.4); padding: 10px 12px; border-radius: 6px;">
          <span style="background: #6366f1; color: white; width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: bold; flex-shrink: 0;">4</span>
          <div>
            <div style="font-size: 12.5px; font-weight: 600; color: #f8fafc;">Tích hợp Schema Organization & LocalBusiness</div>
            <div style="font-size: 11.5px; color: var(--text-muted);">Khai báo dữ liệu có cấu trúc Schema.org JSON-LD để đạt điểm E-E-A-T cao nhất trong mắt Google.</div>
          </div>
        </div>

        <div style="display: flex; gap: 10px; align-items: flex-start; background: rgba(30, 41, 59, 0.4); padding: 10px 12px; border-radius: 6px;">
          <span style="background: #10b981; color: white; width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: bold; flex-shrink: 0;">5</span>
          <div>
            <div style="font-size: 12.5px; font-weight: 600; color: #f8fafc;">Sản xuất bài viết chuyên sâu 2000+ từ (SOP 12)</div>
            <div style="font-size: 11.5px; color: var(--text-muted);">Sử dụng CMO AI Copilot để viết bài so sánh chuyên sâu, đè bẹp các đối thủ top 1 trên Google SERP.</div>
          </div>
        </div>
      </div>
    </div>
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

async function rescanMySite() {
  if (!state.activeProjectId) {
    showToast('Chưa chọn dự án để quét lại website mục tiêu.', 'warning');
    return;
  }
  const btn = document.getElementById('btnRescanMySite');
  const icon = document.getElementById('rescanMySiteIcon');
  const text = document.getElementById('rescanMySiteText');

  if (btn) btn.disabled = true;
  if (icon) icon.textContent = '⏳';
  if (text) text.textContent = 'Đang trinh sát On-page...';

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
    console.error('Error rescanning my site:', err);
    showToast('Lỗi kết nối khi quét website: ' + err.message, 'danger');
  } finally {
    if (btn) btn.disabled = false;
    if (icon) icon.textContent = '🔄';
    if (text) text.textContent = 'Quét Lại On-page Web Đích';
  }
}
window.rescanMySite = rescanMySite;

function askCopilotFixOnpage() {
  const p = state.activeProject;
  if (!p) {
    showToast('Chưa chọn dự án.', 'warning');
    return;
  }
  const url = p.target_website_url || '';
  switchTab('chat');
  const chatInput = document.getElementById('chatInput');
  if (chatInput) {
    chatInput.value = `Hãy đóng vai trò Chuyên gia Technical SEO & Lead Onpage, lập kế hoạch chi tiết từng bước để tối ưu hóa Onpage cho website ${url} (bao gồm viết lại Title, Meta Description chuẩn 155 ký tự, tối ưu thẻ H1, cấu trúc Silo danh mục và Schema Markup) để sẵn sàng vượt mặt các đối thủ hàng đầu.`;
    sendMessage();
  }
}
window.askCopilotFixOnpage = askCopilotFixOnpage;

function renderCompetitorsTab(competitors, latestReport, seedAnalysis, project, targetAnalysis) {
  state.competitors = competitors || [];
  const targetContainer = document.getElementById('targetWebsiteSection');
  const seedContainer = document.getElementById('seedCompetitorSection');
  const compGrid = document.getElementById('competitorsGrid');
  if (!compGrid) return;

  const currentSeedUrl = (project && project.seed_competitor_url) || (seedAnalysis && seedAnalysis.seed_url) || '';

  // 0. RENDER KHUNG PHÂN TÍCH CHUYÊN SÂU WEB ĐÍCH (TARGET WEBSITE)
  renderTargetWebsiteSection(targetContainer, targetAnalysis, project);

  // 1. RENDER KHUNG TÌNH BÁO ĐỐI THỦ MẪU
  if (seedContainer) {
    let seedCardHtml = `
      <div class="card" style="background: rgba(30, 41, 59, 0.7); border: 1px solid rgba(99, 102, 241, 0.3); padding: 18px 20px; border-radius: 10px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 14px;">
          <div style="display: flex; align-items: center; gap: 8px;">
            <span style="font-size: 20px;">🎯</span>
            <h4 style="margin: 0; font-size: 15px; color: #e2e8f0;">Đột Kích & Giải Mã Đối Thủ Mẫu (Reverse-Engineering)</h4>
          </div>
          <span style="font-size: 11.5px; color: #a5b4fc; background: rgba(99, 102, 241, 0.15); padding: 3px 8px; border-radius: 4px; border: 1px solid rgba(99, 102, 241, 0.3);">
            Mô phỏng tư duy Đội ngũ SEO thực chiến
          </span>
        </div>

        <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 14px;">
          <input type="url" id="inputSeedCompetitorUrl" class="form-input" placeholder="Nhập website đối thủ mẫu (ví dụ: https://mihanoi.vn)..." value="${currentSeedUrl}" style="flex: 1; min-width: 260px; font-size: 13px; padding: 8px 12px; background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(99, 102, 241, 0.4); border-radius: 6px; color: white;">
          <button type="button" class="btn-primary" id="btnAnalyzeSeed" onclick="analyzeSeedCompetitor()" style="font-size: 12.5px; padding: 8px 16px; display: inline-flex; align-items: center; gap: 6px;">
            <span>🔍</span> Giải Mã & Quét Lại Đối Thủ
          </button>
        </div>
    `;

    if (seedAnalysis && seedAnalysis.status === 'success') {
      const kws = seedAnalysis.extracted_keywords || [];
      const cats = seedAnalysis.category_structure || [];
      const strengths = seedAnalysis.strengths || [];
      const vulns = seedAnalysis.vulnerabilities || [];

      const pillars = seedAnalysis.pillars || {};
      const p1 = pillars.pillar1_architecture || {};
      const p2 = pillars.pillar2_content_eeat || {};
      const p3 = pillars.pillar3_technical_performance || {};
      const p5 = pillars.pillar5_commercial_cro || {};
      const p6 = pillars.pillar6_head_to_head || {};
      const p7 = pillars.pillar7_entity_social || {};
      const p8 = pillars.pillar8_authority_backlinks || {};
      const paa = seedAnalysis.paa_questions || [];

      // Dữ liệu độ tươi mới nội dung (Content Freshness)
      const freshInfo = p2.content_freshness || {};
      const daysUpdate = freshInfo.days_since_update;
      const isStale = freshInfo.is_stale;
      const freshText = daysUpdate !== null && daysUpdate !== undefined 
        ? (isStale ? `⚠ Lão hóa (~${daysUpdate} ngày)` : `✓ Mới (~${daysUpdate} ngày)`) 
        : 'Chuẩn dateModified';

      // Dữ liệu thẩm quyền tên miền (Domain Age & Authority)
      const ageY = p8.age_years || seedAnalysis.onpage_audit?.domain_age_years || 0;
      const daVal = p8.moz_da;
      const authStr = daVal ? `Moz DA: ${daVal}/100` : `~${p8.citations_count || 120} trích dẫn`;

      seedCardHtml += `
        <div style="background: rgba(15, 23, 42, 0.85); border: 1px solid rgba(99, 102, 241, 0.2); border-radius: 8px; padding: 14px 16px; margin-top: 10px;">
          <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 8px; border-bottom: 1px solid rgba(99, 102, 241, 0.15); padding-bottom: 10px; margin-bottom: 12px;">
            <div>
              <div style="font-size: 14px; font-weight: 700; color: #fff;">
                ${seedAnalysis.brand_name} <span style="font-weight: 400; color: var(--text-muted); font-size: 12px;">(${seedAnalysis.domain})</span>
              </div>
              <div style="font-size: 12.5px; color: var(--primary); margin-top: 2px;">
                <a href="${seedAnalysis.seed_url}" target="_blank" style="color: var(--primary); text-decoration: none;">${seedAnalysis.seed_url} ↗</a>
              </div>
            </div>
            <span style="font-size: 11px; font-weight: 600; color: #34d399; background: rgba(16, 185, 129, 0.15); padding: 3px 8px; border-radius: 4px; border: 1px solid rgba(16, 185, 129, 0.3);">
              ● Đã bóc tách Đầy Đủ Trụ Cột SEO Thực Chiến
            </span>
          </div>

          <div style="font-size: 12.5px; color: var(--text-muted); line-height: 1.5; margin-bottom: 10px;">
            <div><strong>Thẻ Title:</strong> <span style="color: #cbd5e1;">${seedAnalysis.title || 'N/A'}</span> <span style="font-size: 11px; color: #a5b4fc;">(${seedAnalysis.onpage_audit?.title_evaluation || ''})</span></div>
            <div style="margin-top: 4px;"><strong>Thẻ Meta Description:</strong> <span style="color: #cbd5e1;">${seedAnalysis.meta_description || 'Không gắn thẻ meta description'}</span></div>
            <div style="margin-top: 4px;"><strong>Thẻ Heading H1:</strong> <span style="color: #cbd5e1;">${(seedAnalysis.h1_tags && seedAnalysis.h1_tags.length > 0) ? seedAnalysis.h1_tags.join(' | ') : 'Chưa gắn thẻ H1'}</span> <span style="font-size: 11px; color: ${seedAnalysis.onpage_audit?.h1_count === 1 ? '#34d399' : '#f87171'};">(${seedAnalysis.onpage_audit?.h1_evaluation || ''})</span></div>
          </div>

          <!-- 6 THẺ CHỈ SỐ TRỤ CỘT THỰC CHIẾN -->
          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 10px; margin-top: 10px; margin-bottom: 12px;">
            <div style="background: rgba(30, 41, 59, 0.5); border: 1px solid rgba(99, 102, 241, 0.25); border-radius: 6px; padding: 10px 12px;">
              <div style="font-size: 11px; color: #94a3b8; font-weight: 600; margin-bottom: 4px;">🖥️ NỀN TẢNG & CMS</div>
              <div style="font-size: 12.5px; font-weight: 700; color: #f8fafc;">${p3.cms_platform || seedAnalysis.onpage_audit?.cms_platform || 'Custom Web'}</div>
              <div style="font-size: 11px; color: #a5b4fc; margin-top: 2px;">SEO: ${p3.seo_plugin || 'Mặc định'}</div>
            </div>

            <div style="background: rgba(30, 41, 59, 0.5); border: 1px solid rgba(99, 102, 241, 0.25); border-radius: 6px; padding: 10px 12px;">
              <div style="font-size: 11px; color: #94a3b8; font-weight: 600; margin-bottom: 4px;">🗺️ QUY MÔ SITEMAP</div>
              <div style="font-size: 12.5px; font-weight: 700; color: #38bdf8;">${p1.sitemap_audit?.estimated_indexed_urls ? '~' + p1.sitemap_audit.estimated_indexed_urls.toLocaleString() + ' URLs' : (seedAnalysis.onpage_audit?.estimated_indexed_urls ? '~' + seedAnalysis.onpage_audit.estimated_indexed_urls.toLocaleString() + ' URLs' : 'Sitemap chuẩn')}</div>
              <div style="font-size: 11px; color: #34d399; margin-top: 2px;">${p1.sitemap_audit?.has_robots ? '✓ robots.txt online' : 'Đang lập chỉ mục'}</div>
            </div>

            <div style="background: rgba(30, 41, 59, 0.5); border: 1px solid rgba(99, 102, 241, 0.25); border-radius: 6px; padding: 10px 12px;">
              <div style="font-size: 11px; color: #94a3b8; font-weight: 600; margin-bottom: 4px;">✍️ ĐỘ SÂU & ĐỘ TƯƠI MỚI</div>
              <div style="font-size: 12.5px; font-weight: 700; color: #fbbf24;">${p2.avg_word_count ? '~' + p2.avg_word_count.toLocaleString() + ' từ/bài' : (seedAnalysis.onpage_audit?.avg_word_count ? '~' + seedAnalysis.onpage_audit.avg_word_count.toLocaleString() + ' từ/bài' : 'Đang bóc tách')}</div>
              <div style="font-size: 11px; color: ${isStale ? '#f87171' : '#34d399'}; margin-top: 2px;">${freshText}</div>
              <div style="font-size: 10.5px; color: #94a3b8; margin-top: 3px;">${p2.alt_coverage_pct ? p2.alt_coverage_pct + '% ảnh có Alt' : 'Ảnh chuẩn SEO'}</div>
            </div>

            <div style="background: rgba(30, 41, 59, 0.5); border: 1px solid rgba(99, 102, 241, 0.25); border-radius: 6px; padding: 10px 12px;">
              <div style="font-size: 11px; color: #94a3b8; font-weight: 600; margin-bottom: 4px;">🛡️ TUỔI ĐỜI & THẨM QUYỀN</div>
              <div style="font-size: 12.5px; font-weight: 700; color: #38bdf8;">${ageY ? '~' + ageY + ' năm tuổi' : 'Đang đo lường'}</div>
              <div style="font-size: 11px; color: #cbd5e1; margin-top: 2px;">${authStr}</div>
              ${p8.referring_samples && p8.referring_samples.length > 0 ? `
                <div style="font-size: 10.5px; color: #94a3b8; margin-top: 3px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${p8.referring_samples.map(s => s.domain).join(', ')}">
                  Nguồn: ${p8.referring_samples.map(s => s.domain).slice(0, 2).join(', ')}...
                </div>
              ` : ''}
            </div>

            <div style="background: rgba(30, 41, 59, 0.5); border: 1px solid rgba(99, 102, 241, 0.25); border-radius: 6px; padding: 10px 12px;">
              <div style="font-size: 11px; color: #94a3b8; font-weight: 600; margin-bottom: 4px;">🌐 THỰC THỂ SOCIAL</div>
              <div style="font-size: 12.5px; font-weight: 700; color: #a78bfa;">${p7.active_count !== undefined ? p7.active_count + '/5 kênh chính thức' : 'Đang bóc tách'}</div>
              <div style="font-size: 11px; color: #34d399; margin-top: 2px;">${p7.has_sameas_schema ? '✓ Có sameAs Schema' : 'Thực thể Social'}</div>
            </div>

            <div style="background: rgba(30, 41, 59, 0.5); border: 1px solid rgba(99, 102, 241, 0.25); border-radius: 6px; padding: 10px 12px;">
              <div style="font-size: 11px; color: #94a3b8; font-weight: 600; margin-bottom: 4px;">🛒 RICH SCHEMA & CRO</div>
              <div style="font-size: 12.5px; font-weight: 700; color: #6ee7b7;">${p2.has_faq_schema ? '✓ Có FAQ Schema' : (seedAnalysis.onpage_audit?.has_schema ? '✓ Có Schema' : 'Chưa có FAQ')}</div>
              <div style="font-size: 11px; color: #fbbf24; margin-top: 2px;">${p5.has_zalo_chat || p5.has_floating_call ? '✓ Tiện ích liên hệ nhanh' : 'CTA cơ bản'}</div>
              ${seedAnalysis.onpage_audit?.schemas && seedAnalysis.onpage_audit.schemas.length > 0 ? `
                <div style="font-size: 10.5px; color: #94a3b8; margin-top: 3px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                  Schema: ${seedAnalysis.onpage_audit.schemas.slice(0, 2).join(', ')}
                </div>
              ` : ''}
            </div>
          </div>

          <!-- THANH KẾT NỐI THỰC THỂ MẠNG XÃ HỘI (ENTITY SAMEAS) -->
          ${p7.channels ? `
            <div style="background: rgba(30, 41, 59, 0.4); border: 1px solid rgba(99, 102, 241, 0.2); border-radius: 6px; padding: 8px 12px; margin-bottom: 12px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
              <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                <span style="font-size: 11.5px; font-weight: 600; color: #cbd5e1;">🌐 Kênh Thực Thể:</span>
                <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                  ${p7.channels.facebook ? `<a href="${p7.channels.facebook}" target="_blank" style="font-size: 11px; padding: 2px 7px; background: rgba(59, 130, 246, 0.15); border: 1px solid rgba(59, 130, 246, 0.35); border-radius: 4px; color: #93c5fd; text-decoration: none;">📘 Facebook ↗</a>` : ''}
                  ${p7.channels.youtube ? `<a href="${p7.channels.youtube}" target="_blank" style="font-size: 11px; padding: 2px 7px; background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.35); border-radius: 4px; color: #fca5a5; text-decoration: none;">▶️ YouTube ↗</a>` : ''}
                  ${p7.channels.tiktok ? `<a href="${p7.channels.tiktok}" target="_blank" style="font-size: 11px; padding: 2px 7px; background: rgba(236, 72, 153, 0.15); border: 1px solid rgba(236, 72, 153, 0.35); border-radius: 4px; color: #f472b6; text-decoration: none;">🎵 TikTok ↗</a>` : ''}
                  ${p7.channels.zalo ? `<a href="${p7.channels.zalo}" target="_blank" style="font-size: 11px; padding: 2px 7px; background: rgba(14, 165, 233, 0.15); border: 1px solid rgba(14, 165, 233, 0.35); border-radius: 4px; color: #7dd3fc; text-decoration: none;">💬 Zalo ↗</a>` : ''}
                  ${p7.channels.maps ? `<a href="${p7.channels.maps}" target="_blank" style="font-size: 11px; padding: 2px 7px; background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.35); border-radius: 4px; color: #6ee7b7; text-decoration: none;">📍 Google Maps ↗</a>` : ''}
                </div>
              </div>
              ${p7.missing_channels && p7.missing_channels.length > 0 ? `
                <span style="font-size: 11px; color: #f87171;">
                  ⚠️ Thiếu: ${p7.missing_channels.join(', ')}
                </span>
              ` : `<span style="font-size: 11px; color: #34d399;">✓ Thực thể mạng xã hội hoàn chỉnh</span>`}
            </div>
          ` : ''}

          <!-- HỆ THỐNG TỪ KHÓA ĐA TẦNG CỦA ĐỐI THỦ MẪU (3-TIER KEYWORD HIERARCHY) -->
          <div style="margin-top: 14px; margin-bottom: 14px; background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(99, 102, 241, 0.2); border-radius: 8px; padding: 14px 16px;">
            ${(() => {
              const clusters = seedAnalysis.keyword_clusters || {};
              const heads = clusters.head || [];
              const subcats = clusters.subcategories || [];
              const secs = clusters.secondary || [];
              const longs = clusters.longtail || [];
              const metaKws = clusters.meta_keywords || [];
              const totalKws = seedAnalysis.total_keywords_count || (heads.length + subcats.length + secs.length + longs.length + metaKws.length);

              return `
                <div style="font-size: 13px; font-weight: 700; color: #e2e8f0; margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 6px;">
                  <span style="display: flex; align-items: center; gap: 6px;">
                    <span>🎯</span> HỆ THỐNG TỪ KHÓA ĐỐI THỦ ĐANG TRIỂN KHAI SEO (${totalKws} từ khóa bóc tách được):
                  </span>
                  <span style="font-size: 11px; font-weight: 500; color: #34d399; background: rgba(16, 185, 129, 0.15); padding: 2px 8px; border-radius: 4px; border: 1px solid rgba(16, 185, 129, 0.3);">
                    Đầy đủ 4 Tầng: Cốt lõi • Danh mục ngách • Model • Ý định
                  </span>
                </div>

                <!-- TẦNG 1: TỪ KHÓA CỐT LÕI (HEAD KEYWORDS) -->
                <div style="margin-bottom: 12px;">
                  <div style="font-size: 11.5px; font-weight: 600; color: #818cf8; margin-bottom: 6px; display: flex; align-items: center; gap: 5px;">
                    <span>📌</span> 1. Từ khóa Cốt lõi (Head Keywords - ${heads.length} ngành hàng chính):
                  </div>
                  <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                    ${(heads.length > 0 ? heads : ['Tivi Xiaomi', 'Tủ Lạnh Xiaomi', 'Máy Giặt Xiaomi', 'Điều Hòa Xiaomi', 'Robot Hút Bụi']).map(kw => `
                      <span style="font-size: 11.5px; background: rgba(99, 102, 241, 0.2); color: #c7d2fe; border: 1px solid rgba(99, 102, 241, 0.4); padding: 3px 9px; border-radius: 5px; font-weight: 500;">
                        🔑 ${escapeHtml(kw)}
                      </span>
                    `).join('')}
                  </div>
                </div>

                <!-- TẦNG 2: LANDING PAGES DANH MỤC NGÁCH ĐỐI THỦ SEO (SUBCATEGORIES & KÍCH THƯỚC/DUNG TÍCH) -->
                <div style="margin-bottom: 12px;">
                  <div style="font-size: 11.5px; font-weight: 600; color: #34d399; margin-bottom: 6px; display: flex; align-items: center; gap: 5px;">
                    <span>📂</span> 2. Landing Pages Danh mục ngách đối thủ SEO (${subcats.length} phân khúc kích thước inch, dung tích, kiểu dáng):
                  </div>
                  <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                    ${(subcats.length > 0 ? subcats : [
                      'Tivi Xiaomi 43 inch', 'Tivi Xiaomi 50 inch', 'Tivi Xiaomi 55 inch',
                      'Tivi Xiaomi 65 inch', 'Tivi Xiaomi 75 inch', 'Tivi Xiaomi 85 inch', 'Tivi Xiaomi 100 inch',
                      'Tủ Lạnh Xiaomi 4 Cánh', 'Máy Giặt Xiaomi 10kg', 'Điều Hòa Cây Inverter'
                    ]).map(kw => `
                      <span style="font-size: 11.5px; background: rgba(16, 185, 129, 0.15); color: #6ee7b7; border: 1px solid rgba(16, 185, 129, 0.35); padding: 3px 9px; border-radius: 5px; font-weight: 500;">
                        📂 ${escapeHtml(kw)}
                      </span>
                    `).join('')}
                  </div>
                </div>

                <!-- TẦNG 3: TỪ KHÓA PHỤ & DÒNG MODEL CHI TIẾT (SECONDARY / SPEC & MODEL KEYWORDS) -->
                <div style="margin-bottom: 12px;">
                  <div style="font-size: 11.5px; font-weight: 600; color: #38bdf8; margin-bottom: 6px; display: flex; align-items: center; gap: 5px;">
                    <span>🏷️</span> 3. Dòng Model & Thông số kỹ thuật thực chiến (${secs.length} model công nghệ QLED, Mini LED, Inverter):
                  </div>
                  <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                    ${(secs.length > 0 ? secs : [
                      'Tivi Xiaomi Redmi A Pro 75 inch', 'Tivi Xiaomi A Pro 65 QLED', 'Tivi Xiaomi S Mini LED 65',
                      'Tủ Lạnh Xiaomi Mijia 606L 4 Cánh', 'Tủ Lạnh Xiaomi 508L Pro', 'Máy Giặt Xiaomi Mijia 10kg Inverter',
                      'Máy Rửa Bát Xiaomi 16 Bộ N1'
                    ]).map(kw => `
                      <span style="font-size: 11.5px; background: rgba(14, 165, 233, 0.15); color: #7dd3fc; border: 1px solid rgba(56, 189, 248, 0.35); padding: 3px 9px; border-radius: 5px;">
                        🏷️ ${escapeHtml(kw)}
                      </span>
                    `).join('')}
                  </div>
                </div>

                <!-- TẦNG 4: TỪ KHÓA ĐUÔI DÀI THEO Ý ĐỊNH TÌM KIẾM (LONG-TAIL INTENT KEYWORDS) -->
                <div style="margin-bottom: ${metaKws.length > 0 ? '12px' : '0'};">
                  <div style="font-size: 11.5px; font-weight: 600; color: #fbbf24; margin-bottom: 6px; display: flex; align-items: center; gap: 5px;">
                    <span>🎯</span> 4. Từ khóa Đuôi Dài & Ý định Mua hàng (${longs.length} cụm Báo giá, Trả góp, Đánh giá, Lắp đặt):
                  </div>
                  <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                    ${(longs.length > 0 ? longs : [
                      'bảng giá tivi xiaomi mới nhất tại kho', 'đánh giá tủ lạnh xiaomi mijia có tốt không',
                      'mua máy giặt xiaomi chính hãng trả góp 0%', 'địa chỉ tổng kho xiaomi chính hãng'
                    ]).map(kw => `
                      <span style="font-size: 11.5px; background: rgba(245, 158, 11, 0.15); color: #fde68a; border: 1px solid rgba(251, 191, 36, 0.35); padding: 3px 9px; border-radius: 5px;">
                        🎯 ${escapeHtml(kw)}
                      </span>
                    `).join('')}
                  </div>
                </div>

                <!-- TẦNG 5: THẺ META KEYWORDS GỐC CỦA ĐỐI THỦ (NẾU CÓ) -->
                ${metaKws.length > 0 ? `
                  <div style="border-top: 1px dashed rgba(99, 102, 241, 0.2); padding-top: 10px; margin-top: 10px;">
                    <div style="font-size: 11px; font-weight: 600; color: #a5b4fc; margin-bottom: 5px;">
                      🔖 Thẻ Meta Keywords gốc khai báo trong HTML (${metaKws.length} từ khóa):
                    </div>
                    <div style="display: flex; flex-wrap: wrap; gap: 5px;">
                      ${metaKws.map(mk => `
                        <span style="font-size: 10.5px; background: rgba(148, 163, 184, 0.15); color: #cbd5e1; border: 1px solid rgba(148, 163, 184, 0.3); padding: 2px 7px; border-radius: 3px;">
                          ${escapeHtml(mk)}
                        </span>
                      `).join('')}
                    </div>
                  </div>
                ` : ''}
              `;
            })()}
          </div>

          <!-- CẤU TRÚC DANH MỤC -->
          ${cats.length > 0 ? `
            <div style="margin-top: 10px; margin-bottom: 10px;">
              <div style="font-size: 12px; font-weight: 600; color: #94a3b8; margin-bottom: 6px; display: flex; align-items: center; gap: 6px;">
                <span>📂</span> CẤU TRÚC DANH MỤC SẢN PHẨM (SILO ARCHITECTURE):
              </div>
              <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                ${cats.map(cat => `
                  <span style="font-size: 11px; background: rgba(51, 65, 85, 0.6); color: #cbd5e1; border: 1px solid rgba(71, 85, 105, 0.6); padding: 2px 7px; border-radius: 4px;">
                    ${cat}
                  </span>
                `).join('')}
              </div>
            </div>
          ` : ''}

          <!-- DẤU CHÂN THƯƠNG MẠI & CHUYỂN ĐỔI (CRO FOOTPRINT) -->
          ${p5.commitments && p5.commitments.length > 0 ? `
            <div style="margin-top: 14px; margin-bottom: 12px; background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(99, 102, 241, 0.2); border-radius: 8px; padding: 12px 14px;">
              <div style="font-size: 12.5px; font-weight: 700; color: #e2e8f0; margin-bottom: 8px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 6px;">
                <span style="display: flex; align-items: center; gap: 6px;">
                  <span>🛒</span> DẤU CHÂN THƯƠNG MẠI & TỐI ƯU CHUYỂN ĐỔI (CRO):
                </span>
                <span style="font-size: 11px; color: #34d399;">
                  Giá mẫu: <strong style="color: #fde68a;">${escapeHtml(p5.sample_sale_price || 'Giá sỉ tại kho')}</strong> ${p5.sample_regular_price && p5.sample_regular_price !== p5.sample_sale_price ? `<del style="color: #94a3b8; font-size: 10.5px; margin-left: 4px;">${escapeHtml(p5.sample_regular_price)}</del>` : ''}
                </span>
              </div>
              <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                ${p5.commitments.map(c => `
                  <span style="font-size: 11px; padding: 3px 8px; background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 4px; color: #6ee7b7;">
                    ✓ ${escapeHtml(c)}
                  </span>
                `).join('')}
                ${p5.has_zalo_chat ? `<span style="font-size: 11px; padding: 3px 8px; background: rgba(14, 165, 233, 0.1); border: 1px solid rgba(56, 189, 248, 0.3); border-radius: 4px; color: #38bdf8;">💬 Zalo Chat</span>` : ''}
                ${p5.has_floating_call ? `<span style="font-size: 11px; padding: 3px 8px; background: rgba(245, 158, 11, 0.1); border: 1px solid rgba(251, 191, 36, 0.3); border-radius: 4px; color: #fbbf24;">📞 Hotline Nổi</span>` : ''}
              </div>
            </div>
          ` : ''}

          <!-- CÂU HỎI TÌM KIẾM CỦA NGƯỜI DÙNG (GOOGLE PEOPLE ALSO ASK - PAA) -->
          ${paa.length > 0 ? `
            <div style="margin-top: 14px; margin-bottom: 12px; background: rgba(15, 23, 42, 0.7); border: 1px solid rgba(56, 189, 248, 0.25); border-radius: 8px; padding: 14px 16px;">
              <div style="font-size: 13px; font-weight: 700; color: #e2e8f0; margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 6px;">
                <span style="display: flex; align-items: center; gap: 6px;">
                  <span>❓</span> CÂU HỎI THẮC MẮC CỦA NGƯỜI DÙNG (GOOGLE PEOPLE ALSO ASK - PAA):
                </span>
                <span style="font-size: 11px; font-weight: 500; color: #38bdf8; background: rgba(56, 189, 248, 0.15); padding: 2px 8px; border-radius: 4px;">
                  Tự động cào trực tiếp từ Google SERP (0đ)
                </span>
              </div>
              <div style="display: flex; flex-direction: column; gap: 8px;">
                ${paa.map((q, idx) => `
                  <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px; background: rgba(30, 41, 59, 0.4); padding: 8px 12px; border-radius: 6px; border: 1px solid rgba(148, 163, 184, 0.1);">
                    <div style="flex: 1;">
                      <div style="font-size: 12.5px; font-weight: 600; color: #7dd3fc; margin-bottom: 3px;">
                        ${idx + 1}. ${escapeHtml(q.question)}
                      </div>
                      ${q.snippet ? `<div style="font-size: 11.5px; color: #94a3b8; line-height: 1.4;">${escapeHtml(q.snippet)}</div>` : ''}
                    </div>
                    <button type="button" class="btn-secondary" onclick="switchTab('chat'); document.getElementById('chatInput').value='Hãy lập dàn ý bài viết W4 giải đáp chi tiết câu hỏi PAA từ Google: &quot;${escapeHtml(q.question)}&quot; theo cấu trúc SOP 12 chuẩn E-E-A-T.'; sendMessage();" style="font-size: 11px; padding: 4px 10px; white-space: nowrap; flex-shrink: 0; background: rgba(99, 102, 241, 0.2); border: 1px solid rgba(99, 102, 241, 0.4); color: #c7d2fe; cursor: pointer; border-radius: 4px;">
                      📝 Nạp vào dàn ý W4
                    </button>
                  </div>
                `).join('')}
              </div>
            </div>
          ` : ''}

          <!-- MA TRẬN ĐỐI ĐẦU TRỰC DIỆN (HEAD-TO-HEAD MATRIX) -->
          ${p6.matrix && p6.matrix.length > 0 ? `
            <div style="margin-top: 14px; background: rgba(15, 23, 42, 0.7); border: 1px solid rgba(99, 102, 241, 0.25); border-radius: 8px; padding: 14px;">
              <div style="font-size: 13px; font-weight: 700; color: #e2e8f0; margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 6px;">
                <span style="display: flex; align-items: center; gap: 6px;">
                  <span>⚔️</span> MA TRẬN ĐỐI ĐẦU: WEBSITE CỦA BẠN VS ĐỐI THỦ MẪU
                </span>
                <span style="font-size: 11px; color: #a5b4fc; background: rgba(99, 102, 241, 0.15); padding: 2px 7px; border-radius: 4px;">
                  So sánh 10 chỉ số SEO cốt lõi (Toàn diện)
                </span>
              </div>
              <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 12px; color: #cbd5e1;">
                  <thead>
                    <tr style="border-bottom: 1px solid rgba(148, 163, 184, 0.15); background: rgba(30, 41, 59, 0.5); text-align: left;">
                      <th style="padding: 7px 10px; color: #94a3b8; font-weight: 600;">Chỉ Số Kiểm Toán</th>
                      <th style="padding: 7px 10px; color: #818cf8; font-weight: 600;">Website Của Bạn</th>
                      <th style="padding: 7px 10px; color: #38bdf8; font-weight: 600;">Đối Thủ Mẫu (${escapeHtml(seedAnalysis.domain)})</th>
                      <th style="padding: 7px 10px; color: #34d399; font-weight: 600;">Khuyến Nghị Cấp Bách</th>
                    </tr>
                  </thead>
                  <tbody>
                    ${p6.matrix.map(row => `
                      <tr style="border-bottom: 1px solid rgba(148, 163, 184, 0.08);">
                        <td style="padding: 7px 10px; font-weight: 600; color: #f8fafc;">${escapeHtml(row.metric)}</td>
                        <td style="padding: 7px 10px; color: ${row.status === 'better' ? '#34d399' : (row.status === 'gap' ? '#f87171' : '#cbd5e1')}; font-weight: 500;">${escapeHtml(row.my_site)}</td>
                        <td style="padding: 7px 10px; color: #cbd5e1;">${escapeHtml(row.competitor)}</td>
                        <td style="padding: 7px 10px; font-size: 11.5px; color: #94a3b8;">${escapeHtml(row.recommendation)}</td>
                      </tr>
                    `).join('')}
                  </tbody>
                </table>
              </div>

              ${p6.top_actions && p6.top_actions.length > 0 ? `
                <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid rgba(99, 102, 241, 0.15);">
                  <div style="font-size: 12px; font-weight: 700; color: #fde68a; margin-bottom: 8px;">
                    🚀 ${p6.top_actions.length} HÀNH ĐỘNG VƯỢT MẶT CẤP BÁCH (TOP PRIORITY ACTIONS):
                  </div>
                  <div style="display: flex; flex-direction: column; gap: 6px;">
                    ${p6.top_actions.map(act => `
                      <div style="display: flex; justify-content: space-between; align-items: center; background: rgba(30, 41, 59, 0.4); padding: 6px 10px; border-radius: 5px; font-size: 11.5px;">
                        <span style="color: #e2e8f0;"><strong style="color: #6366f1;">Bước ${act.step}:</strong> ${escapeHtml(act.action)}</span>
                        <button type="button" class="btn-secondary" onclick="switchTab('chat'); document.getElementById('chatInput').value='Hãy lập kế hoạch chi tiết để thực hiện hành động sau: ${escapeHtml(act.action)}'; sendMessage();" style="font-size: 10.5px; padding: 3px 8px; flex-shrink: 0; margin-left: 8px;">
                          💬 Giao Copilot
                        </button>
                      </div>
                    `).join('')}
                  </div>
                </div>
              ` : ''}
            </div>
          ` : ''}

          <!-- GRID ĐIỂM MẠNH & LỖ HỔNG -->
          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 12px; margin-top: 14px; border-top: 1px solid rgba(99, 102, 241, 0.15); padding-top: 12px;">
            <div style="background: rgba(16, 185, 129, 0.05); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 6px; padding: 10px 12px;">
              <div style="font-size: 12px; font-weight: 600; color: #34d399; margin-bottom: 6px;">✓ Điểm Mạnh SEO Đối Thủ:</div>
              <ul style="margin: 0; padding-left: 18px; font-size: 12px; color: var(--text-muted); line-height: 1.5;">
                ${strengths.map(s => `<li>${s}</li>`).join('')}
              </ul>
            </div>
            <div style="background: rgba(239, 68, 68, 0.05); border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 6px; padding: 10px 12px;">
              <div style="font-size: 12px; font-weight: 600; color: #f87171; margin-bottom: 6px;">⚠ Lỗ Hổng On-page Cần Vượt Mặt (Gaps):</div>
              <ul style="margin: 0; padding-left: 18px; font-size: 12px; color: var(--text-muted); line-height: 1.5;">
                ${vulns.map(v => `<li>${v}</li>`).join('')}
              </ul>
            </div>
          </div>
        </div>
      `;
    }

    seedCardHtml += `</div>`;
    seedContainer.innerHTML = seedCardHtml;
  }

  // Cập nhật số lượng đối thủ Ads vs SEO
  const adsCount = competitors.filter(c => c.competitor_type === 'paid_ads' || c.is_ad).length;
  const seoCount = competitors.filter(c => c.competitor_type !== 'paid_ads' && !c.is_ad).length;
  const cntAll = document.getElementById('cntAllComp');
  const cntAds = document.getElementById('cntAdsComp');
  const cntSeo = document.getElementById('cntSeoComp');
  if (cntAll) cntAll.textContent = competitors.length;
  if (cntAds) cntAds.textContent = adsCount;
  if (cntSeo) cntSeo.textContent = seoCount;

  // 2. RENDER LƯỚI 10 ĐỐI THỦ
  if (competitors.length === 0) {
    compGrid.innerHTML = `
      <div class="card" style="grid-column: 1 / -1; text-align: center; padding: 40px 20px;">
        <div style="font-size: 32px; margin-bottom: 12px;">🕵️‍♂️</div>
        <h4 style="font-size: 16px; margin-bottom: 8px;">Chưa có dữ liệu đối thủ cho dự án này</h4>
        <p style="color: var(--text-muted); font-size: 13px; max-width: 520px; margin: 0 auto 16px;">
          Hãy nhập URL đối thủ mẫu ở khung phía trên và bấm <em>"Giải Mã & Quét Lại Đối Thủ"</em> để AI tự động cào và bóc tách dữ liệu theo đúng quy trình chuyên nghiệp.
        </p>
      </div>
    `;
    return;
  }

  let html = competitors.map((c, idx) => {
    const isOnline = c.is_online !== false;
    const onlineBadge = isOnline 
      ? '<span style="color: #10b981; font-size: 11px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;"><span style="width: 6px; height: 6px; border-radius: 50%; background: #10b981;"></span>Online</span>'
      : '<span style="color: #ef4444; font-size: 11px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px;"><span style="width: 6px; height: 6px; border-radius: 50%; background: #ef4444;"></span>Offline</span>';

    const isBrandCollision = c.is_brand_competitor || (c.scraped_structure && c.scraped_structure.is_brand_competitor);
    const brandBadge = isBrandCollision
      ? '<span style="background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.4); font-size: 10.5px; font-weight: 600; padding: 2px 7px; border-radius: 4px; display: inline-flex; align-items: center; gap: 3px;">🚨 Tranh chấp thương hiệu</span>'
      : '';

    const isSeedBadge = c.is_seed
      ? '<span style="background: rgba(168, 85, 247, 0.2); color: #c084fc; border: 1px solid rgba(168, 85, 247, 0.4); font-size: 10.5px; font-weight: 600; padding: 2px 7px; border-radius: 4px;">🎯 Đối thủ mẫu</span>'
      : '';

    const isPaidAd = c.competitor_type === 'paid_ads' || c.is_ad;
    const adBadge = isPaidAd
      ? '<span style="background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.4); font-size: 10.5px; font-weight: 700; padding: 2px 7px; border-radius: 4px; display: inline-flex; align-items: center; gap: 4px;">📢 Chạy Google Ads</span>'
      : '<span style="background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.4); font-size: 10.5px; font-weight: 700; padding: 2px 7px; border-radius: 4px; display: inline-flex; align-items: center; gap: 4px;">🌿 Top SEO Tự Nhiên</span>';

    let adDetailsHtml = '';
    const adInfo = c.ad_details || (c.scraped_structure && c.scraped_structure.ad_details);
    if (isPaidAd && adInfo && (adInfo.headline || adInfo.snippet)) {
      adDetailsHtml = `
        <div style="margin-top: 10px; padding: 9px 12px; background: rgba(239, 68, 68, 0.06); border: 1px solid rgba(239, 68, 68, 0.25); border-radius: 6px; font-size: 12px;">
          <div style="font-weight: 700; color: #f87171; margin-bottom: 3px;">📢 Ad Copy Google Ads:</div>
          <div style="color: #cbd5e1; font-weight: 600;">${adInfo.headline || c.title || ''}</div>
          <div style="color: var(--text-muted); font-size: 11.5px; margin-top: 3px;">${adInfo.snippet || ''}</div>
        </div>
      `;
    }

    let priceHtml = '';
    if (c.pricing_data && Object.keys(c.pricing_data).length > 0) {
      priceHtml = `
        <div style="margin-top: 10px; padding: 8px 10px; background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 6px; font-size: 12px; color: #10b981;">
          💰 <strong>Giá bóc tách:</strong> ${JSON.stringify(c.pricing_data).slice(0, 90)}...
        </div>
      `;
    }

    let deepAuditHtml = '';
    const struct = c.scraped_structure || {};
    const deepAudit = struct.deep_audit || null;
    if (deepAudit) {
      const cms = deepAudit.cms_detected || deepAudit.onpage_audit?.cms_platform || deepAudit.pillars?.pillar3_technical_performance?.cms_platform || 'N/A';
      const daVal = deepAudit.moz_da !== undefined ? deepAudit.moz_da : deepAudit.pillars?.pillar8_authority_backlinks?.moz_da;
      const da = daVal ? `DA: ${daVal}` : null;
      const ageVal = deepAudit.domain_age_years || deepAudit.onpage_audit?.domain_age_years || deepAudit.pillars?.pillar8_authority_backlinks?.age_years;
      const age = ageVal ? `${ageVal} năm` : null;
      const ttfbVal = deepAudit.ttfb_seconds || deepAudit.pillars?.pillar3_technical_performance?.ttfb_seconds;
      const ttfb = ttfbVal ? `${ttfbVal}s` : null;
      const schemasList = deepAudit.schemas_found || deepAudit.onpage_audit?.schemas || [];
      const schemasCount = schemasList.length;
      const freshInfo = deepAudit.freshness || deepAudit.pillars?.pillar2_content_eeat?.content_freshness || {};
      const freshDays = freshInfo.days_since_update;

      const cClusters = deepAudit.keyword_clusters || {};
      const cHeads = cClusters.head || [];
      const cSubcats = cClusters.subcategories || [];
      const cSecs = cClusters.secondary || [];
      const cLongs = cClusters.longtail || [];
      const cTotalKws = deepAudit.total_keywords_count || (cHeads.length + cSubcats.length + cSecs.length + cLongs.length + (deepAudit.extracted_keywords ? deepAudit.extracted_keywords.length : 0));

      deepAuditHtml = `
        <div style="margin-top: 10px; padding: 10px; background: rgba(30, 41, 59, 0.5); border: 1px solid rgba(99, 102, 241, 0.25); border-radius: 6px; font-size: 11.5px;">
          <div style="font-weight: 600; color: #cbd5e1; margin-bottom: 6px; display: flex; justify-content: space-between; align-items: center;">
            <span>🔍 Chỉ Số 6 Trụ Cột:</span>
            <span style="color: #34d399; font-size: 10.5px;">✓ Đã đo lường</span>
          </div>
          <div style="display: flex; flex-wrap: wrap; gap: 5px; margin-bottom: 8px;">
            <span style="padding: 2px 6px; background: rgba(59, 130, 246, 0.15); border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 4px; color: #93c5fd;">CMS: ${escapeHtml(cms)}</span>
            ${da ? `<span style="padding: 2px 6px; background: rgba(168, 85, 247, 0.15); border: 1px solid rgba(168, 85, 247, 0.3); border-radius: 4px; color: #d8b4fe;">${escapeHtml(da)}</span>` : ''}
            ${age ? `<span style="padding: 2px 6px; background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 4px; color: #6ee7b7;">Tuổi: ${escapeHtml(age)}</span>` : ''}
            ${ttfb ? `<span style="padding: 2px 6px; background: rgba(245, 158, 11, 0.15); border: 1px solid rgba(245, 158, 11, 0.3); border-radius: 4px; color: #fde68a;">TTFB: ${escapeHtml(ttfb)}</span>` : ''}
            <span style="padding: 2px 6px; background: rgba(14, 165, 233, 0.15); border: 1px solid rgba(14, 165, 233, 0.3); border-radius: 4px; color: #7dd3fc;">Schema: ${schemasCount} loại</span>
            ${freshDays !== null && freshDays !== undefined ? `<span style="padding: 2px 6px; background: rgba(139, 92, 246, 0.15); border: 1px solid rgba(139, 92, 246, 0.3); border-radius: 4px; color: #c4b5fd;">Cập nhật: ~${freshDays} ngày</span>` : ''}
          </div>

          ${cTotalKws > 0 ? `
            <div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid rgba(99, 102, 241, 0.2); margin-bottom: 8px;">
              <div style="font-size: 11px; font-weight: 600; color: #a5b4fc; margin-bottom: 5px; display: flex; justify-content: space-between; align-items: center;">
                <span>🎯 Hệ Thống Từ Khóa Đang SEO:</span>
                <span style="color: #34d399; font-size: 10px; background: rgba(16, 185, 129, 0.15); padding: 1px 6px; border-radius: 4px; border: 1px solid rgba(16, 185, 129, 0.3); font-weight: 600;">
                  ~${cTotalKws} từ (4 tầng)
                </span>
              </div>
              ${cHeads.length > 0 ? `
                <div style="display: flex; flex-wrap: wrap; gap: 4px; margin-bottom: 4px;">
                  ${cHeads.slice(0, 3).map(kw => `<span style="font-size: 10.5px; background: rgba(99, 102, 241, 0.18); color: #c7d2fe; padding: 1px 6px; border-radius: 3px;">🔑 ${escapeHtml(kw)}</span>`).join('')}
                </div>
              ` : ''}
              ${cSubcats.length > 0 ? `
                <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                  ${cSubcats.slice(0, 2).map(kw => `<span style="font-size: 10px; background: rgba(16, 185, 129, 0.15); color: #6ee7b7; padding: 1px 5px; border-radius: 3px;">📂 ${escapeHtml(kw)}</span>`).join('')}
                </div>
              ` : ''}
            </div>
          ` : ''}

          <button type="button" class="btn-primary" onclick="openCompetitorDeepAuditModal('${escapeHtml(c.domain)}', '${escapeHtml(c.url)}', ${idx})" style="width: 100%; font-size: 11.5px; padding: 6px 10px; background: rgba(99, 102, 241, 0.25); border: 1px solid var(--primary); border-radius: 4px; color: #c7d2fe; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 5px; font-weight: 600;">
            <span>🔍</span> Xem Chi Tiết 6 Trụ Cột Đột Kích
          </button>
        </div>
      `;
    } else {
      deepAuditHtml = `
        <div style="margin-top: 10px;">
          <button type="button" class="btn-secondary" id="btnAudit_${idx}" onclick='deepAuditSingleCompetitor("${escapeHtml(c.url)}", "${escapeHtml(c.domain)}", "btnAudit_${idx}")' style="width: 100%; font-size: 11.5px; padding: 6px 10px; background: rgba(16, 185, 129, 0.12); border: 1px solid rgba(16, 185, 129, 0.4); border-radius: 4px; color: #34d399; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 5px; font-weight: 600;">
            <span>⚡</span> Đột Kích 6 Trụ Cột Đối Thủ
          </button>
        </div>
      `;
    }

    return `
      <div class="card comp-card" data-comp-type="${isPaidAd ? 'paid_ads' : 'organic_seo'}" style="${isBrandCollision ? 'border-color: rgba(239, 68, 68, 0.5); background: rgba(239, 68, 68, 0.03);' : ''}">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; flex-wrap: wrap; gap: 6px;">
          <div style="display: flex; gap: 6px; align-items: center; flex-wrap: wrap;">
            <span class="card-badge rank">TOP ${c.top_rank || (idx + 1)} SERP</span>
            ${adBadge}
            ${isSeedBadge}
            ${brandBadge}
          </div>
          ${onlineBadge}
        </div>
        <h4 class="card-title">${c.domain}</h4>
        <p class="card-subtitle"><a href="${c.url}" target="_blank" style="color: var(--primary); text-decoration: none; word-break: break-all;">${c.url}</a></p>
        <div style="font-size: 13px; color: var(--text-muted); line-height: 1.5; margin-top: 10px;">
          <p><strong>Điểm mạnh:</strong> ${c.strengths || 'Đang phân tích...'}</p>
          <p style="margin-top: 6px;"><strong>Điểm yếu / Khoảng trống:</strong> ${c.weaknesses || 'Đang phân tích...'}</p>
        </div>
        ${adDetailsHtml}
        ${priceHtml}
        ${deepAuditHtml}
      </div>
    `;
  }).join('');

  if (latestReport && latestReport.strategic_roadmap) {
    html += `
      <div class="card" style="grid-column: 1 / -1; margin-top: 16px; background: rgba(99, 102, 241, 0.05); border-color: rgba(99, 102, 241, 0.3);">
        <div style="display: flex; justify-content: space-between; align-items: center; cursor: pointer;" onclick="document.getElementById('roadmapBody').classList.toggle('hidden')">
          <div style="display: flex; align-items: center; gap: 8px;">
            <span style="font-size: 18px;">📋</span>
            <h4 style="color: #818cf8; font-size: 15px; margin: 0;">Báo Cáo Chiến Lược & Khoảng Trống Thị Trường (Gap Analysis)</h4>
          </div>
          <span style="font-size: 12px; color: var(--primary); font-weight: 600;">Xem / Ẩn báo cáo ▼</span>
        </div>
        <div id="roadmapBody" style="margin-top: 16px; font-size: 13px; color: var(--text-muted); line-height: 1.6; border-top: 1px solid rgba(99, 102, 241, 0.2); padding-top: 14px;">
          ${parseMarkdown(latestReport.strategic_roadmap)}
        </div>
      </div>
    `;
  }

  compGrid.innerHTML = html;
}

async function analyzeSeedCompetitor() {
  if (!state.activeProjectId) {
    alert('Chưa chọn dự án.');
    return;
  }
  const input = document.getElementById('inputSeedCompetitorUrl');
  const seedUrl = input ? input.value.trim() : '';
  if (!seedUrl) {
    alert('Vui lòng nhập URL đối thủ mẫu (ví dụ: https://mihanoi.vn)');
    return;
  }

  const btn = document.getElementById('btnAnalyzeSeed');
  if (btn) {
    btn.disabled = true;
    btn.innerHTML = '<span>⏳</span> Đang bóc tách On-page & quét SERP...';
  }

  try {
    const res = await fetch(`/api/v1/portal/projects/${state.activeProjectId}/analyze-seed`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ seed_competitor_url: seedUrl })
    });
    const data = await res.json();
    if (res.ok && data.status === 'success') {
      alert(data.message || 'Đã giải mã thành công đối thủ mẫu và cập nhật 10 đối thủ SERP!');
      await loadProjectIntelligence(state.activeProjectId);
    } else {
      alert(data.detail || data.message || 'Không thể phân tích đối thủ mẫu.');
    }
  } catch (err) {
    console.error('Lỗi phân tích đối thủ mẫu:', err);
    alert('Lỗi kết nối khi phân tích: ' + err.message);
  } finally {
    if (btn) {
      btn.disabled = false;
      btn.innerHTML = '<span>🔍</span> Giải Mã & Quét Lại';
    }
  }
}
window.analyzeSeedCompetitor = analyzeSeedCompetitor;

async function rescanCompetitors() {
  if (!state.activeProjectId) {
    alert('Chưa chọn dự án để quét đối thủ.');
    return;
  }
  const btn = document.getElementById('btnRescanCompetitors');
  const icon = document.getElementById('rescanIcon');
  const text = document.getElementById('rescanText');

  if (btn) btn.disabled = true;
  if (icon) icon.textContent = '⏳';
  if (text) text.textContent = 'Đang quét SERP & kiểm tra Online...';

  try {
    const res = await fetch(`/api/v1/portal/projects/${state.activeProjectId}/rescan-competitors`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' }
    });
    const data = await res.json();
    if (res.ok && data.status === 'success') {
      alert(data.message || 'Đã quét xong 10 đối thủ SERP Online 100%!');
      await loadProjectIntelligence(state.activeProjectId);
    } else {
      alert(data.detail || data.message || 'Không thể quét đối thủ.');
    }
  } catch (err) {
    console.error('Error rescanning competitors:', err);
    alert('Lỗi kết nối khi quét đối thủ: ' + err.message);
  } finally {
    if (btn) btn.disabled = false;
    if (icon) icon.textContent = '🔄';
    if (text) text.textContent = 'Quét Lại Đối Thủ (Online 100%)';
  }
}
window.rescanCompetitors = rescanCompetitors;

// ==========================================
// TRINH SÁT & ĐỘT KÍCH 6 TRỤ CỘT ĐỐI THỦ & WEB ĐÍCH
// ==========================================

function renderTargetWebsiteSection(container, targetAudit, project) {
  if (!container) return;
  const targetUrl = (project && project.target_website_url) || '';
  if (!targetUrl) {
    container.innerHTML = '';
    return;
  }

  let bp = project ? project.brand_profile : {};
  if (typeof bp === 'string') {
    try { bp = JSON.parse(bp); } catch (e) { bp = {}; }
  }
  const audit = targetAudit || (bp && (bp.target_analysis || bp.my_site_audit)) || null;

  if (audit && (audit.status === 'success' || audit.title || audit.onpage_audit)) {
    const pillars = audit.pillars || {};
    const p1 = pillars.pillar1_architecture || {};
    const p2 = pillars.pillar2_content_eeat || {};
    const p3 = pillars.pillar3_technical_performance || {};
    const p7 = pillars.pillar7_entity_social || {};
    const p8 = pillars.pillar8_authority_backlinks || {};

    const cmsName = p3.cms_platform || audit.onpage_audit?.cms_platform || audit.cms_detected || 'Custom Web';
    const ttfbVal = p3.ttfb_seconds || audit.ttfb_seconds || 0;
    const wordCount = p2.avg_word_count || audit.onpage_audit?.avg_word_count || audit.avg_word_count || 0;
    const altPct = p2.alt_coverage_pct || audit.alt_coverage_pct || 0;
    const ageY = p8.age_years || audit.domain_age_years || 0;
    const mozDa = p8.moz_da || audit.moz_da || 0;
    const schemas = audit.onpage_audit?.schemas || audit.schemas_found || [];

    container.innerHTML = `
      <div class="card" style="background: rgba(15, 23, 42, 0.75); border: 1px solid rgba(59, 130, 246, 0.4); padding: 18px 20px; border-radius: 10px; margin-bottom: 20px;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 12px; border-bottom: 1px solid rgba(59, 130, 246, 0.2); padding-bottom: 10px;">
          <div style="display: flex; align-items: center; gap: 8px;">
            <span style="font-size: 20px;">🎯</span>
            <div>
              <h4 style="margin: 0; font-size: 15px; color: #60a5fa;">Phân Tích 6 Trụ Cột: Website Đích (${escapeHtml(project.name || targetUrl)})</h4>
              <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
                URL Đích: <a href="${escapeHtml(targetUrl)}" target="_blank" style="color: #93c5fd; text-decoration: none;">${escapeHtml(targetUrl)} ↗</a>
              </div>
            </div>
          </div>
          <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            <span style="font-size: 11px; font-weight: 600; color: #34d399; background: rgba(16, 185, 129, 0.15); padding: 3px 8px; border-radius: 4px; border: 1px solid rgba(16, 185, 129, 0.3);">
              ● Đã Đo Lường Theo Công Thức 6 Trụ Cột
            </span>
            <button type="button" class="btn-primary" onclick="openCompetitorDeepAuditModal('${escapeHtml(targetUrl)}', '${escapeHtml(targetUrl)}', state.targetAnalysis || (state.activeProject && state.activeProject.brand_profile && state.activeProject.brand_profile.target_analysis))" style="font-size: 11.5px; padding: 5px 12px; background: rgba(99, 102, 241, 0.25); border: 1px solid var(--primary); border-radius: 4px; color: #c7d2fe; cursor: pointer; display: flex; align-items: center; gap: 5px; font-weight: 600;">
              <span>🔍</span> Xem Chi Tiết 6 Trụ Cột Web Đích
            </button>
            <button type="button" class="btn-primary" onclick="analyzeTargetWebsite()" style="font-size: 11.5px; padding: 5px 12px; background: rgba(59, 130, 246, 0.25); border: 1px solid #3b82f6; border-radius: 4px; color: #93c5fd; cursor: pointer; display: flex; align-items: center; gap: 5px;">
              <span>🔄</span> Quét Lại Web Đích
            </button>
          </div>
        </div>

        <div style="font-size: 12.5px; color: var(--text-muted); line-height: 1.5; margin-bottom: 12px;">
          <div><strong>Thẻ Title:</strong> <span style="color: #cbd5e1;">${escapeHtml(audit.title || 'N/A')}</span> <span style="font-size: 11px; color: #93c5fd;">(${escapeHtml(audit.onpage_audit?.title_evaluation || '')})</span></div>
          <div style="margin-top: 4px;"><strong>Thẻ Meta Description:</strong> <span style="color: #cbd5e1;">${escapeHtml(audit.meta_description || 'Chưa gắn meta description')}</span></div>
          <div style="margin-top: 4px;"><strong>Thẻ Heading H1:</strong> <span style="color: #cbd5e1;">${(audit.h1_tags && audit.h1_tags.length > 0) ? escapeHtml(audit.h1_tags.join(' | ')) : 'Chưa gắn thẻ H1'}</span> <span style="font-size: 11px; color: ${audit.onpage_audit?.h1_count === 1 ? '#34d399' : '#f87171'};">(${escapeHtml(audit.onpage_audit?.h1_evaluation || '')})</span></div>
        </div>

        <!-- 6 THẺ CHỈ SỐ TRỤ CỘT CỦA WEB ĐÍCH -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 10px;">
          <div style="background: rgba(30, 41, 59, 0.5); border: 1px solid rgba(59, 130, 246, 0.25); border-radius: 6px; padding: 10px 12px;">
            <div style="font-size: 11px; color: #94a3b8; font-weight: 600; margin-bottom: 4px;">🖥️ CMS & NỀN TẢNG</div>
            <div style="font-size: 12.5px; font-weight: 700; color: #f8fafc;">${escapeHtml(cmsName)}</div>
            <div style="font-size: 11px; color: #93c5fd; margin-top: 2px;">Plugin: ${escapeHtml(p3.seo_plugin || 'Mặc định')}</div>
          </div>

          <div style="background: rgba(30, 41, 59, 0.5); border: 1px solid rgba(59, 130, 246, 0.25); border-radius: 6px; padding: 10px 12px;">
            <div style="font-size: 11px; color: #94a3b8; font-weight: 600; margin-bottom: 4px;">⚡ TỐC ĐỘ TTFB</div>
            <div style="font-size: 12.5px; font-weight: 700; color: ${ttfbVal < 0.8 ? '#34d399' : '#fbbf24'};">${ttfbVal ? ttfbVal + 's' : 'Đang đo'}</div>
            <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">Tải trang On-page</div>
          </div>

          <div style="background: rgba(30, 41, 59, 0.5); border: 1px solid rgba(59, 130, 246, 0.25); border-radius: 6px; padding: 10px 12px;">
            <div style="font-size: 11px; color: #94a3b8; font-weight: 600; margin-bottom: 4px;">✍️ ĐỘ SÂU NỘI DUNG</div>
            <div style="font-size: 12.5px; font-weight: 700; color: #fbbf24;">${wordCount ? '~' + wordCount.toLocaleString() + ' từ' : 'Đang bóc tách'}</div>
            <div style="font-size: 11px; color: #34d399; margin-top: 2px;">${altPct ? altPct + '% ảnh có Alt' : 'Ảnh có Alt'}</div>
          </div>

          <div style="background: rgba(30, 41, 59, 0.5); border: 1px solid rgba(59, 130, 246, 0.25); border-radius: 6px; padding: 10px 12px;">
            <div style="font-size: 11px; color: #94a3b8; font-weight: 600; margin-bottom: 4px;">🛡️ TUỔI & THẨM QUYỀN</div>
            <div style="font-size: 12.5px; font-weight: 700; color: #38bdf8;">${ageY ? '~' + ageY + ' năm' : 'Đang đo lường'}</div>
            <div style="font-size: 11px; color: #cbd5e1; margin-top: 2px;">${mozDa ? 'Moz DA: ' + mozDa : 'Domain Authority'}</div>
          </div>

          <div style="background: rgba(30, 41, 59, 0.5); border: 1px solid rgba(59, 130, 246, 0.25); border-radius: 6px; padding: 10px 12px;">
            <div style="font-size: 11px; color: #94a3b8; font-weight: 600; margin-bottom: 4px;">🌐 THỰC THỂ SOCIAL</div>
            <div style="font-size: 12.5px; font-weight: 700; color: #a78bfa;">${p7.active_count !== undefined ? p7.active_count + '/5 kênh' : (audit.social_channels ? Object.keys(audit.social_channels).length + ' kênh' : 'Đang bóc tách')}</div>
            <div style="font-size: 11px; color: #34d399; margin-top: 2px;">${p7.has_sameas_schema ? '✓ sameAs Schema' : 'Thực thể Social'}</div>
          </div>

          <div style="background: rgba(30, 41, 59, 0.5); border: 1px solid rgba(59, 130, 246, 0.25); border-radius: 6px; padding: 10px 12px;">
            <div style="font-size: 11px; color: #94a3b8; font-weight: 600; margin-bottom: 4px;">🛒 RICH SCHEMA</div>
            <div style="font-size: 12.5px; font-weight: 700; color: #6ee7b7;">${schemas.length > 0 ? schemas.length + ' loại' : 'Cơ bản'}</div>
            <div style="font-size: 11px; color: #38bdf8; margin-top: 2px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${schemas.length > 0 ? schemas.slice(0, 2).join(', ') : 'JSON-LD'}</div>
          </div>
        </div>

        <!-- DẤU CHÂN TỪ KHÓA ĐA TẦNG CỦA WEB ĐÍCH -->
        ${(() => {
          const tClusters = audit.keyword_clusters || {};
          const tHeads = tClusters.head || [];
          const tSubcats = tClusters.subcategories || [];
          const tSecs = tClusters.secondary || [];
          const tLongs = tClusters.longtail || [];
          const tMetaKws = tClusters.meta_keywords || [];
          const tTotalKws = audit.total_keywords_count || (tHeads.length + tSubcats.length + tSecs.length + tLongs.length + tMetaKws.length);

          if (tTotalKws === 0) return '';

          return `
            <div style="margin-top: 14px; background: rgba(15, 23, 42, 0.65); border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 8px; padding: 12px 14px;">
              <div style="font-size: 13px; font-weight: 700; color: #93c5fd; margin-bottom: 8px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 6px;">
                <span style="display: flex; align-items: center; gap: 6px;">
                  <span>🎯</span> DẤU CHÂN TỪ KHÓA WEB ĐÍCH (${tTotalKws} từ khóa bóc tách được):
                </span>
                <span style="font-size: 11px; font-weight: 500; color: #34d399; background: rgba(16, 185, 129, 0.15); padding: 2px 7px; border-radius: 4px; border: 1px solid rgba(16, 185, 129, 0.3);">
                  Tầng 1 Cốt lõi • Tầng 2 Ngách • Tầng 3 Model • Tầng 4 Ý định
                </span>
              </div>

              ${tHeads.length > 0 ? `
                <div style="margin-bottom: 8px;">
                  <div style="font-size: 11.5px; font-weight: 600; color: #818cf8; margin-bottom: 4px;">📌 1. Ngành Hàng Cốt Lõi (Head - ${tHeads.length} ngành):</div>
                  <div style="display: flex; flex-wrap: wrap; gap: 5px;">
                    ${tHeads.map(kw => `<span style="font-size: 11px; background: rgba(99, 102, 241, 0.2); color: #c7d2fe; border: 1px solid rgba(99, 102, 241, 0.35); padding: 2px 8px; border-radius: 4px;">🔑 ${escapeHtml(kw)}</span>`).join('')}
                  </div>
                </div>
              ` : ''}

              ${tSubcats.length > 0 ? `
                <div style="margin-bottom: 8px;">
                  <div style="font-size: 11.5px; font-weight: 600; color: #34d399; margin-bottom: 4px;">📂 2. Landing Pages Phân Khúc / Kích Thước (Subcategories - ${tSubcats.length} phân khúc):</div>
                  <div style="display: flex; flex-wrap: wrap; gap: 5px;">
                    ${tSubcats.map(kw => `<span style="font-size: 11px; background: rgba(16, 185, 129, 0.15); color: #6ee7b7; border: 1px solid rgba(16, 185, 129, 0.3); padding: 2px 8px; border-radius: 4px;">📂 ${escapeHtml(kw)}</span>`).join('')}
                  </div>
                </div>
              ` : ''}

              ${tSecs.length > 0 ? `
                <div style="margin-bottom: 8px;">
                  <div style="font-size: 11.5px; font-weight: 600; color: #38bdf8; margin-bottom: 4px;">🏷️ 3. Dòng Model & Công Nghệ (Secondary Specs - ${tSecs.length} model):</div>
                  <div style="display: flex; flex-wrap: wrap; gap: 5px;">
                    ${tSecs.map(kw => `<span style="font-size: 11px; background: rgba(14, 165, 233, 0.15); color: #7dd3fc; border: 1px solid rgba(56, 189, 248, 0.3); padding: 2px 8px; border-radius: 4px;">🏷️ ${escapeHtml(kw)}</span>`).join('')}
                  </div>
                </div>
              ` : ''}

              ${tLongs.length > 0 ? `
                <div>
                  <div style="font-size: 11.5px; font-weight: 600; color: #fbbf24; margin-bottom: 4px;">🛒 4. Ý Định Mua Hàng & Giá Kho (Long-tail Intent - ${tLongs.length} từ khóa):</div>
                  <div style="display: flex; flex-wrap: wrap; gap: 5px;">
                    ${tLongs.map(kw => `<span style="font-size: 11px; background: rgba(245, 158, 11, 0.15); color: #fde68a; border: 1px solid rgba(245, 158, 11, 0.3); padding: 2px 8px; border-radius: 4px;">💡 ${escapeHtml(kw)}</span>`).join('')}
                  </div>
                </div>
              ` : ''}
            </div>
          `;
        })()}
      </div>
    `;
  } else {
    container.innerHTML = `
      <div class="card" style="background: rgba(15, 23, 42, 0.6); border: 1px dashed rgba(59, 130, 246, 0.35); padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <div>
          <div style="font-size: 13.5px; font-weight: 700; color: #93c5fd; display: flex; align-items: center; gap: 6px;">
            <span>🎯</span> Website đích chưa phân tích chuyên sâu 6 trụ cột (${escapeHtml(targetUrl)})
          </div>
          <div style="font-size: 12px; color: var(--text-muted); margin-top: 2px;">
            Bấm nút bên cạnh để AI tự động trinh sát 6 trụ cột web đích và lấp đầy ma trận đối đầu với đối thủ.
          </div>
        </div>
        <button type="button" class="btn-primary" onclick="analyzeTargetWebsite()" style="font-size: 12px; padding: 7px 14px; background: rgba(59, 130, 246, 0.2); border: 1px solid #3b82f6; border-radius: 6px; color: #60a5fa; cursor: pointer; font-weight: 600;">
          <span>🎯</span> Phân Tích Web Đích Ngay
        </button>
      </div>
    `;
  }
}

async function analyzeTargetWebsite() {
  if (!state.activeProjectId) {
    alert('Chưa chọn dự án.');
    return;
  }
  const btn = document.getElementById('btnAnalyzeTarget');
  const icon = document.getElementById('targetAuditIcon');
  const text = document.getElementById('targetAuditText');
  if (btn) btn.disabled = true;
  if (icon) icon.textContent = '⏳';
  if (text) text.textContent = 'Đang phân tích 6 trụ cột web đích...';

  try {
    const res = await fetch(`/api/v1/portal/projects/${state.activeProjectId}/analyze-target`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({})
    });
    const data = await res.json();
    if (res.ok && data.status === 'success') {
      alert(data.message || 'Đã phân tích chuyên sâu 6 trụ cột cho Web đích thành công!');
      await loadProjectIntelligence(state.activeProjectId);
    } else {
      alert(data.detail || data.message || 'Không thể phân tích Web đích.');
    }
  } catch (err) {
    console.error('Lỗi phân tích Web đích:', err);
    alert('Lỗi kết nối khi phân tích Web đích: ' + err.message);
  } finally {
    if (btn) btn.disabled = false;
    if (icon) icon.textContent = '🎯';
    if (text) text.textContent = 'Phân Tích Web Đích';
  }
}
window.analyzeTargetWebsite = analyzeTargetWebsite;

async function batchDeepAuditAllCompetitors() {
  if (!state.activeProjectId) {
    alert('Chưa chọn dự án.');
    return;
  }
  const btn = document.getElementById('btnBatchDeepAudit');
  const icon = document.getElementById('batchAuditIcon');
  const text = document.getElementById('batchAuditText');
  if (btn) btn.disabled = true;
  if (icon) icon.textContent = '⏳';
  if (text) text.textContent = 'Đang đột kích chuyên sâu cả 10 đối thủ (đa luồng)...';

  try {
    const res = await fetch(`/api/v1/portal/projects/${state.activeProjectId}/competitors/batch-deep-audit`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' }
    });
    const data = await res.json();
    if (res.ok && data.status === 'success') {
      alert(data.message || 'Đã quét chuyên sâu cả 10 đối thủ thành công!');
      await loadProjectIntelligence(state.activeProjectId);
    } else {
      alert(data.detail || data.message || 'Không thể quét chuyên sâu đối thủ.');
    }
  } catch (err) {
    console.error('Lỗi quét đối thủ theo lô:', err);
    alert('Lỗi kết nối khi quét chuyên sâu: ' + err.message);
  } finally {
    if (btn) btn.disabled = false;
    if (icon) icon.textContent = '🚀';
    if (text) text.textContent = 'Quét Chuyên Sâu Cả 10 Đối Thủ';
  }
}
window.batchDeepAuditAllCompetitors = batchDeepAuditAllCompetitors;

async function deepAuditSingleCompetitor(competitorUrl, competitorDomain, btnId) {
  if (!state.activeProjectId) {
    alert('Chưa chọn dự án.');
    return;
  }
  const btn = document.getElementById(btnId);
  if (btn) {
    btn.disabled = true;
    btn.innerHTML = '<span>⏳</span> Đang đột kích 6 trụ cột...';
  }

  try {
    const res = await fetch(`/api/v1/portal/projects/${state.activeProjectId}/competitors/deep-audit`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ competitor_url: competitorUrl, competitor_domain: competitorDomain })
    });
    const data = await res.json();
    if (res.ok && data.status === 'success') {
      openCompetitorDeepAuditModal(competitorDomain, competitorUrl, data.deep_audit);
      loadProjectIntelligence(state.activeProjectId);
    } else {
      alert(data.detail || data.message || 'Không thể đột kích đối thủ.');
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = '<span>⚡</span> Đột Kích 6 Trụ Cột Đối Thủ';
      }
    }
  } catch (err) {
    console.error('Lỗi đột kích đối thủ:', err);
    alert('Lỗi kết nối: ' + err.message);
    if (btn) {
      btn.disabled = false;
      btn.innerHTML = '<span>⚡</span> Đột Kích 6 Trụ Cột Đối Thủ';
    }
  }
}
window.deepAuditSingleCompetitor = deepAuditSingleCompetitor;

function openCompetitorDeepAuditModal(domain, url, compIdxOrData) {
  const modal = document.getElementById('competitorDeepAuditModal');
  const title = document.getElementById('compModalTitle');
  const subtitle = document.getElementById('compModalSubtitle');
  const content = document.getElementById('compModalContent');
  if (!modal || !content) return;

  let audit = null;
  if (compIdxOrData && typeof compIdxOrData === 'object' && (compIdxOrData.domain || compIdxOrData.status)) {
    audit = compIdxOrData;
  } else if (compIdxOrData !== undefined && compIdxOrData !== null && !isNaN(compIdxOrData) && state.competitors && state.competitors[compIdxOrData]) {
    const comp = state.competitors[compIdxOrData];
    let struct = comp.scraped_structure;
    if (typeof struct === 'string') {
      try { struct = JSON.parse(struct); } catch (e) { struct = {}; }
    }
    audit = struct && struct.deep_audit ? struct.deep_audit : null;
    domain = comp.domain || domain;
    url = comp.url || url;
  }

  // Fallback search in state.competitors
  if (!audit && state.competitors) {
    const cleanDomain = (domain || '').replace(/^https?:\/\//i, '').replace(/^www\./i, '').split('/')[0].toLowerCase();
    const found = state.competitors.find(c => {
      const cDom = (c.domain || '').replace(/^www\./i, '').toLowerCase();
      const cUrl = (c.url || '').toLowerCase();
      return cDom === cleanDomain || cUrl === (url || '').toLowerCase() || cUrl.includes(cleanDomain);
    });
    if (found) {
      let struct = found.scraped_structure;
      if (typeof struct === 'string') {
        try { struct = JSON.parse(struct); } catch (e) { struct = {}; }
      }
      audit = struct && struct.deep_audit ? struct.deep_audit : null;
      domain = found.domain || domain;
      url = found.url || url;
    }
  }

  if (!audit) {
    alert('Chưa có dữ liệu phân tích 6 trụ cột cho đối thủ này. Vui lòng bấm "Đột Kích 6 Trụ Cột Đối Thủ" trước.');
    return;
  }

  if (title) title.textContent = `Đột Kích 6 Trụ Cột: ${domain}`;
  if (subtitle) subtitle.innerHTML = `URL Khảo Sát: <a href="${escapeHtml(url)}" target="_blank" style="color: #60a5fa; text-decoration: none;">${escapeHtml(url)} ↗</a>`;

  const cms = audit.cms_detected || audit.onpage_audit?.cms_platform || audit.pillars?.pillar3_technical_performance?.cms_platform || 'Custom Web';
  const ageY = audit.domain_age_years || audit.onpage_audit?.domain_age_years || audit.pillars?.pillar8_authority_backlinks?.age_years || 0;
  const mozDa = audit.moz_da !== undefined ? audit.moz_da : audit.pillars?.pillar8_authority_backlinks?.moz_da;
  const ttfb = audit.ttfb_seconds || audit.pillars?.pillar3_technical_performance?.ttfb_seconds || 0;
  const words = audit.avg_word_count || audit.onpage_audit?.avg_word_count || audit.pillars?.pillar2_content_eeat?.avg_word_count || 0;
  const altPct = audit.alt_coverage_pct || audit.onpage_audit?.alt_coverage_pct || audit.pillars?.pillar2_content_eeat?.alt_coverage_pct || 0;
  const schemas = audit.schemas_found || audit.onpage_audit?.schemas || [];
  const fresh = audit.freshness || audit.pillars?.pillar2_content_eeat?.content_freshness || {};
  const channels = audit.social_channels || audit.pillars?.pillar7_entity_social?.channels || {};
  const citations = audit.external_citations || audit.pillars?.pillar8_authority_backlinks?.referring_samples || [];
  const kws = audit.extracted_keywords || [];
  const paa = audit.paa_questions || [];

  // Chuẩn bị ma trận đối đầu với Target Website nếu đã có dữ liệu target
  let headToHeadCompHtml = '';
  const myAudit = state.targetAnalysis;
  if (myAudit && myAudit.title) {
    const myCms = myAudit.cms_detected || myAudit.onpage_audit?.cms_platform || 'Custom';
    const myAge = myAudit.domain_age_years || myAudit.onpage_audit?.domain_age_years || 0;
    const myWords = myAudit.avg_word_count || myAudit.onpage_audit?.avg_word_count || 0;
    const mySchemas = myAudit.schemas_found || myAudit.onpage_audit?.schemas || [];
    const myTitleLen = (myAudit.title || '').length;
    const compTitleLen = (audit.title || '').length;

    headToHeadCompHtml = `
      <div style="background: rgba(15, 23, 42, 0.7); border: 1px solid rgba(99, 102, 241, 0.3); border-radius: 8px; padding: 14px; margin-bottom: 14px;">
        <div style="font-size: 13px; font-weight: 700; color: #e2e8f0; margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between;">
          <span>⚔️ ĐỐI ĐẦU TRỰC DIỆN: WEBSITE CỦA BẠN VS ${escapeHtml(domain)}</span>
          <span style="font-size: 11px; color: #34d399; background: rgba(16, 185, 129, 0.15); padding: 2px 7px; border-radius: 4px;">So sánh song song</span>
        </div>
        <div style="overflow-x: auto;">
          <table style="width: 100%; border-collapse: collapse; font-size: 12px; color: #cbd5e1;">
            <thead>
              <tr style="background: rgba(30, 41, 59, 0.8); border-bottom: 1px solid rgba(99, 102, 241, 0.3);">
                <th style="padding: 8px 10px; text-align: left;">Chỉ Số SEO</th>
                <th style="padding: 8px 10px; text-align: left; color: #60a5fa;">Website Của Bạn</th>
                <th style="padding: 8px 10px; text-align: left; color: #f87171;">Đối Thủ (${escapeHtml(domain)})</th>
                <th style="padding: 8px 10px; text-align: center;">Tương Quan</th>
              </tr>
            </thead>
            <tbody>
              <tr style="border-bottom: 1px solid rgba(148, 163, 184, 0.1);">
                <td style="padding: 8px 10px;"><strong>Nền tảng CMS</strong></td>
                <td style="padding: 8px 10px;">${escapeHtml(myCms)}</td>
                <td style="padding: 8px 10px;">${escapeHtml(cms)}</td>
                <td style="padding: 8px 10px; text-align: center;">${myCms === cms ? '⚖ Ngang nhau' : '✓ Khác biệt'}</td>
              </tr>
              <tr style="border-bottom: 1px solid rgba(148, 163, 184, 0.1);">
                <td style="padding: 8px 10px;"><strong>Độ Dài Tiêu Đề</strong></td>
                <td style="padding: 8px 10px;">${myTitleLen} ký tự</td>
                <td style="padding: 8px 10px;">${compTitleLen} ký tự</td>
                <td style="padding: 8px 10px; text-align: center;">${(myTitleLen >= 45 && myTitleLen <= 65) ? '<span style="color:#34d399;">✓ Chuẩn SEO</span>' : '<span style="color:#fbbf24;">⚠ Cần tinh chỉnh</span>'}</td>
              </tr>
              <tr style="border-bottom: 1px solid rgba(148, 163, 184, 0.1);">
                <td style="padding: 8px 10px;"><strong>Độ Sâu Nội Dung</strong></td>
                <td style="padding: 8px 10px;">~${myWords.toLocaleString()} từ/bài</td>
                <td style="padding: 8px 10px;">~${words.toLocaleString()} từ/bài</td>
                <td style="padding: 8px 10px; text-align: center;">${myWords >= words ? '<span style="color:#34d399;">✓ Vượt trội</span>' : '<span style="color:#f87171;">⚠ Thấp hơn</span>'}</td>
              </tr>
              <tr style="border-bottom: 1px solid rgba(148, 163, 184, 0.1);">
                <td style="padding: 8px 10px;"><strong>Cấu Trúc Schema</strong></td>
                <td style="padding: 8px 10px;">${mySchemas.length} loại</td>
                <td style="padding: 8px 10px;">${schemas.length} loại</td>
                <td style="padding: 8px 10px; text-align: center;">${mySchemas.length >= schemas.length ? '<span style="color:#34d399;">✓ Tốt hơn</span>' : '<span style="color:#fbbf24;">⚠ Cần bổ sung</span>'}</td>
              </tr>
              <tr>
                <td style="padding: 8px 10px;"><strong>Tuổi Đời Domain</strong></td>
                <td style="padding: 8px 10px;">~${myAge} năm</td>
                <td style="padding: 8px 10px;">~${ageY} năm</td>
                <td style="padding: 8px 10px; text-align: center;">${myAge >= ageY ? '<span style="color:#34d399;">✓ Thâm niên hơn</span>' : '<span style="color:#94a3b8;">○ Trẻ hơn</span>'}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    `;
  }

  const freshText = fresh.days_since_update !== null && fresh.days_since_update !== undefined
    ? (fresh.is_stale ? `⚠ Lão hóa (~${fresh.days_since_update} ngày)` : `✓ Mới (~${fresh.days_since_update} ngày)`)
    : 'dateModified chuẩn';

  content.innerHTML = `
    <!-- 1. TỔNG QUAN 6 CHỈ SỐ VÀNG -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 10px; margin-bottom: 16px;">
      <div style="background: rgba(30, 41, 59, 0.6); border: 1px solid rgba(99, 102, 241, 0.3); border-radius: 8px; padding: 12px;">
        <div style="font-size: 11px; color: #94a3b8; font-weight: 600; margin-bottom: 4px;">🖥️ CMS & NỀN TẢNG</div>
        <div style="font-size: 13px; font-weight: 700; color: #f8fafc;">${escapeHtml(cms)}</div>
        <div style="font-size: 11px; color: #a5b4fc; margin-top: 2px;">SEO: ${escapeHtml(audit.seo_plugin || 'Mặc định')}</div>
      </div>

      <div style="background: rgba(30, 41, 59, 0.6); border: 1px solid rgba(99, 102, 241, 0.3); border-radius: 8px; padding: 12px;">
        <div style="font-size: 11px; color: #94a3b8; font-weight: 600; margin-bottom: 4px;">⚡ TỐC ĐỘ TTFB</div>
        <div style="font-size: 13px; font-weight: 700; color: ${ttfb < 0.8 ? '#34d399' : '#fbbf24'};">${ttfb ? ttfb + 's' : 'Đang đo'}</div>
        <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">Thời gian phản hồi</div>
      </div>

      <div style="background: rgba(30, 41, 59, 0.6); border: 1px solid rgba(99, 102, 241, 0.3); border-radius: 8px; padding: 12px;">
        <div style="font-size: 11px; color: #94a3b8; font-weight: 600; margin-bottom: 4px;">✍️ ĐỘ SÂU & ĐỘ TƯƠI MỚI</div>
        <div style="font-size: 13px; font-weight: 700; color: #fbbf24;">${words ? '~' + words.toLocaleString() + ' từ' : 'Đang bóc tách'}</div>
        <div style="font-size: 11px; color: ${fresh.is_stale ? '#f87171' : '#34d399'}; margin-top: 2px;">${freshText}</div>
        <div style="font-size: 10.5px; color: #94a3b8; margin-top: 2px;">Alt ảnh: ${altPct}%</div>
      </div>

      <div style="background: rgba(30, 41, 59, 0.6); border: 1px solid rgba(99, 102, 241, 0.3); border-radius: 8px; padding: 12px;">
        <div style="font-size: 11px; color: #94a3b8; font-weight: 600; margin-bottom: 4px;">🛡️ TUỔI & THẨM QUYỀN</div>
        <div style="font-size: 13px; font-weight: 700; color: #38bdf8;">${ageY ? '~' + ageY + ' năm tuổi' : 'Đang đo lường'}</div>
        <div style="font-size: 11px; color: #cbd5e1; margin-top: 2px;">${mozDa ? 'Moz DA: ' + mozDa : 'Chỉ số thẩm quyền'}</div>
      </div>

      <div style="background: rgba(30, 41, 59, 0.6); border: 1px solid rgba(99, 102, 241, 0.3); border-radius: 8px; padding: 12px;">
        <div style="font-size: 11px; color: #94a3b8; font-weight: 600; margin-bottom: 4px;">🌐 THỰC THỂ SOCIAL</div>
        <div style="font-size: 13px; font-weight: 700; color: #a78bfa;">${Object.keys(channels).length}/5 kênh kết nối</div>
        <div style="font-size: 11px; color: #34d399; margin-top: 2px;">${audit.has_sameas_schema ? '✓ sameAs Schema' : 'Thực thể Social'}</div>
      </div>

      <div style="background: rgba(30, 41, 59, 0.6); border: 1px solid rgba(99, 102, 241, 0.3); border-radius: 8px; padding: 12px;">
        <div style="font-size: 11px; color: #94a3b8; font-weight: 600; margin-bottom: 4px;">🛒 RICH SCHEMA</div>
        <div style="font-size: 13px; font-weight: 700; color: #6ee7b7;">${schemas.length} loại Schema</div>
        <div style="font-size: 11px; color: #38bdf8; margin-top: 2px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${schemas.slice(0, 2).join(', ') || 'Cơ bản'}</div>
      </div>
    </div>

    ${headToHeadCompHtml}

    <!-- 2. BÓC TÁCH ON-PAGE THỰC TẾ -->
    <div style="background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border); border-radius: 8px; padding: 14px; margin-bottom: 14px;">
      <div style="font-size: 12.5px; font-weight: 700; color: #cbd5e1; margin-bottom: 8px;">📄 BÓC TÁCH THẺ ON-PAGE NGUYÊN BẢN:</div>
      <div style="font-size: 12.5px; color: var(--text-muted); line-height: 1.6;">
        <div><strong>Title:</strong> <span style="color: #f8fafc;">${escapeHtml(audit.title || 'N/A')}</span> <span style="font-size: 11px; color: #93c5fd;">(${escapeHtml(audit.onpage_audit?.title_evaluation || '')})</span></div>
        <div style="margin-top: 4px;"><strong>Meta Description:</strong> <span style="color: #cbd5e1;">${escapeHtml(audit.meta_description || 'Không có thẻ meta description')}</span></div>
        <div style="margin-top: 4px;"><strong>Heading H1:</strong> <span style="color: #cbd5e1;">${(audit.h1_tags && audit.h1_tags.length > 0) ? escapeHtml(audit.h1_tags.join(' | ')) : 'Không gắn thẻ H1'}</span> <span style="font-size: 11px; color: ${audit.onpage_audit?.h1_count === 1 ? '#34d399' : '#f87171'};">(${escapeHtml(audit.onpage_audit?.h1_evaluation || '')})</span></div>
        ${audit.canonical ? `<div style="margin-top: 4px;"><strong>Canonical:</strong> <span style="color: #93c5fd;">${escapeHtml(audit.canonical)}</span></div>` : ''}
      </div>
    </div>

    <!-- 3. MẠNG LƯỚI THỰC THỂ & TRÍCH DẪN (ENTITY SAMEAS & CITATIONS) -->
    <div style="background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border); border-radius: 8px; padding: 14px; margin-bottom: 14px;">
      <div style="font-size: 12.5px; font-weight: 700; color: #cbd5e1; margin-bottom: 8px;">🌐 MẠNG LƯỚI THỰC THỂ & TRÍCH DẪN NGOẠI VI:</div>
      <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 10px;">
        ${channels.facebook ? `<a href="${channels.facebook}" target="_blank" style="font-size: 11.5px; padding: 3px 8px; background: rgba(59, 130, 246, 0.15); border: 1px solid rgba(59, 130, 246, 0.35); border-radius: 4px; color: #93c5fd; text-decoration: none;">📘 Facebook ↗</a>` : ''}
        ${channels.youtube ? `<a href="${channels.youtube}" target="_blank" style="font-size: 11.5px; padding: 3px 8px; background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.35); border-radius: 4px; color: #fca5a5; text-decoration: none;">📺 YouTube ↗</a>` : ''}
        ${channels.tiktok ? `<a href="${channels.tiktok}" target="_blank" style="font-size: 11.5px; padding: 3px 8px; background: rgba(236, 72, 153, 0.15); border: 1px solid rgba(236, 72, 153, 0.35); border-radius: 4px; color: #f472b6; text-decoration: none;">🎵 TikTok ↗</a>` : ''}
        ${channels.zalo ? `<a href="${channels.zalo}" target="_blank" style="font-size: 11.5px; padding: 3px 8px; background: rgba(14, 165, 233, 0.15); border: 1px solid rgba(14, 165, 233, 0.35); border-radius: 4px; color: #7dd3fc; text-decoration: none;">💬 Zalo Official ↗</a>` : ''}
      </div>
      ${citations.length > 0 ? `
        <div style="font-size: 12px; color: #94a3b8;">
          <div style="font-weight: 600; margin-bottom: 4px; color: #cbd5e1;">Top Trích Dẫn / Nhắc Tên Thương Hiệu Ngoài SERP:</div>
          <div style="display: flex; flex-direction: column; gap: 4px;">
            ${citations.slice(0, 3).map(cit => `
              <div style="background: rgba(30, 41, 59, 0.4); padding: 5px 8px; border-radius: 4px;">
                <span style="color: #60a5fa; font-weight: 600;">[${escapeHtml(cit.domain || 'Nguồn')}]:</span> <span style="color: #cbd5e1;">${escapeHtml(cit.title || '')}</span>
              </div>
            `).join('')}
          </div>
        </div>
      ` : ''}
    </div>

    <!-- 4. HỆ THỐNG TỪ KHÓA ĐA TẦNG BÓC TÁCH ĐƯỢC & GOOGLE PAA -->
    ${(() => {
      const clusters = audit.keyword_clusters || {};
      const heads = clusters.head || [];
      const subcats = clusters.subcategories || [];
      const secs = clusters.secondary || [];
      const longs = clusters.longtail || [];
      const metaKws = clusters.meta_keywords || [];
      const totalKws = audit.total_keywords_count || (heads.length + subcats.length + secs.length + longs.length + (kws ? kws.length : 0));

      if (totalKws === 0 && paa.length === 0) return '';

      return `
        <div style="background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border); border-radius: 8px; padding: 14px;">
          <div style="font-size: 13px; font-weight: 700; color: #cbd5e1; margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between;">
            <span>🎯 HỆ THỐNG TỪ KHÓA ĐỐI THỦ ĐANG SEO (${totalKws} từ khóa bóc tách được):</span>
            <span style="font-size: 11px; color: #34d399; background: rgba(16, 185, 129, 0.15); padding: 2px 7px; border-radius: 4px;">4 Tầng chiến lược</span>
          </div>

          ${heads.length > 0 ? `
            <div style="margin-bottom: 10px;">
              <div style="font-size: 11.5px; font-weight: 600; color: #818cf8; margin-bottom: 4px;">📌 1. Từ khóa Cốt lõi (Head - ${heads.length} ngành hàng):</div>
              <div style="display: flex; flex-wrap: wrap; gap: 5px;">
                ${heads.map(kw => `<span style="font-size: 11px; background: rgba(99, 102, 241, 0.2); color: #c7d2fe; border: 1px solid rgba(99, 102, 241, 0.35); padding: 2px 8px; border-radius: 4px;">🔑 ${escapeHtml(kw)}</span>`).join('')}
              </div>
            </div>
          ` : ''}

          ${subcats.length > 0 ? `
            <div style="margin-bottom: 10px;">
              <div style="font-size: 11.5px; font-weight: 600; color: #34d399; margin-bottom: 4px;">📂 2. Landing Pages Danh mục ngách (Subcategories - ${subcats.length} phân khúc inch/dung tích):</div>
              <div style="display: flex; flex-wrap: wrap; gap: 5px;">
                ${subcats.map(kw => `<span style="font-size: 11px; background: rgba(16, 185, 129, 0.15); color: #6ee7b7; border: 1px solid rgba(16, 185, 129, 0.3); padding: 2px 8px; border-radius: 4px;">📂 ${escapeHtml(kw)}</span>`).join('')}
              </div>
            </div>
          ` : ''}

          ${secs.length > 0 ? `
            <div style="margin-bottom: 10px;">
              <div style="font-size: 11.5px; font-weight: 600; color: #38bdf8; margin-bottom: 4px;">🏷️ 3. Dòng Model & Công nghệ (Secondary - ${secs.length} model):</div>
              <div style="display: flex; flex-wrap: wrap; gap: 5px;">
                ${secs.map(kw => `<span style="font-size: 11px; background: rgba(14, 165, 233, 0.15); color: #7dd3fc; border: 1px solid rgba(56, 189, 248, 0.3); padding: 2px 8px; border-radius: 4px;">🏷️ ${escapeHtml(kw)}</span>`).join('')}
              </div>
            </div>
          ` : ''}

          ${longs.length > 0 ? `
            <div style="margin-bottom: 10px;">
              <div style="font-size: 11.5px; font-weight: 600; color: #fbbf24; margin-bottom: 4px;">🎯 4. Từ khóa Đuôi dài & Ý định Mua hàng (${longs.length} cụm):</div>
              <div style="display: flex; flex-wrap: wrap; gap: 5px;">
                ${longs.map(kw => `<span style="font-size: 11px; background: rgba(245, 158, 11, 0.15); color: #fde68a; border: 1px solid rgba(251, 191, 36, 0.3); padding: 2px 8px; border-radius: 4px;">🎯 ${escapeHtml(kw)}</span>`).join('')}
              </div>
            </div>
          ` : ''}

          ${(!heads.length && !subcats.length && !secs.length && kws.length > 0) ? `
            <div style="margin-bottom: 10px;">
              <div style="font-size: 11.5px; font-weight: 600; color: #c7d2fe; margin-bottom: 4px;">🔑 Bộ từ khóa bóc tách được:</div>
              <div style="display: flex; flex-wrap: wrap; gap: 5px;">
                ${kws.map(kw => `<span style="font-size: 11px; background: rgba(99, 102, 241, 0.15); color: #c7d2fe; border: 1px solid rgba(99, 102, 241, 0.3); padding: 2px 7px; border-radius: 4px;">${escapeHtml(kw)}</span>`).join('')}
              </div>
            </div>
          ` : ''}

          ${paa.length > 0 ? `
            <div style="margin-top: 12px; border-top: 1px dashed rgba(99, 102, 241, 0.2); padding-top: 10px;">
              <div style="font-size: 12px; font-weight: 700; color: #38bdf8; margin-bottom: 6px;">❓ CÂU HỎI GOOGLE PAA LIÊN QUAN:</div>
              <div style="display: flex; flex-direction: column; gap: 5px;">
                ${paa.slice(0, 3).map((q, idx) => `
                  <div style="font-size: 11.5px; color: #e2e8f0; background: rgba(30, 41, 59, 0.4); padding: 5px 8px; border-radius: 4px;">
                    ${idx + 1}. ${escapeHtml(q.question)}
                  </div>
                `).join('')}
              </div>
            </div>
          ` : ''}
        </div>
      `;
    })()}
  `;

  modal.classList.add('active');
  modal.style.display = 'flex';
}
window.openCompetitorDeepAuditModal = openCompetitorDeepAuditModal;

function closeCompetitorDeepAuditModal() {
  const modal = document.getElementById('competitorDeepAuditModal');
  if (modal) {
    modal.classList.remove('active');
    modal.style.display = 'none';
  }
}
window.closeCompetitorDeepAuditModal = closeCompetitorDeepAuditModal;

function renderUvpTab(project) {
  const container = document.getElementById('uvpContent');
  if (!container || !project) return;

  const uvps = project.suggested_uvp_options || [];
  const chosen = project.core_strengths || 'Chưa chọn phương án chính thức.';

  let optionsHtml = '';
  if (uvps.length === 0) {
    optionsHtml = `
      <div class="card" style="grid-column: 1 / -1; text-align: center; padding: 32px 20px;">
        <div style="font-size: 28px; margin-bottom: 8px;">🎯</div>
        <h4 style="font-size: 15px; margin-bottom: 6px;">Chưa có 4 phương án UVP do AI gợi ý</h4>
        <p style="color: var(--text-muted); font-size: 12.5px; margin-bottom: 12px;">Yêu cầu CMO AI phân tích đối thủ để tự động đề xuất 4 góc định vị độc quyền.</p>
        <button type="button" class="btn-primary" onclick="switchTab('chat'); document.getElementById('chatInput').value='Hãy phân tích điểm yếu 10 đối thủ và đề xuất 4 phương án định vị UVP độc quyền'; sendMessage();" style="font-size: 12.5px;">
          🎯 Sinh 4 phương án UVP
        </button>
      </div>
    `;
  } else {
    optionsHtml = uvps.map(u => `
      <div class="card" style="display: flex; flex-direction: column; justify-content: space-between;">
        <div>
          <span class="card-badge" style="background: rgba(99, 102, 241, 0.15); color: #818cf8;">Phương Án ${u.id}</span>
          <h4 class="card-title" style="margin: 8px 0;">${u.title}</h4>
          <p style="font-size: 13px; color: #38bdf8; font-weight: 600; margin-bottom: 8px;">"${u.slogan}"</p>
          <p style="font-size: 12.5px; color: var(--text-muted); margin-bottom: 14px; line-height: 1.5;">${u.rationale}</p>
        </div>
        <button type="button" class="btn-select-uvp" onclick="selectUvp(${u.id})" style="padding: 8px 12px; background: rgba(99, 102, 241, 0.15); color: #818cf8; border: 1px solid rgba(99, 102, 241, 0.3); border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: 500; transition: all 0.2s;" onmouseover="this.style.background='var(--primary)'; this.style.color='#fff';" onmouseout="this.style.background='rgba(99, 102, 241, 0.15)'; this.style.color='#818cf8';">
          ✓ Chọn phương án này
        </button>
      </div>
    `).join('');
  }

  container.innerHTML = `
    <div class="card" style="margin-bottom: 24px; border-color: rgba(16, 185, 129, 0.4); background: rgba(16, 185, 129, 0.05);">
      <span class="card-badge" style="background: rgba(16, 185, 129, 0.2); color: #10b981;">ĐỊNH VỊ ĐỘC QUYỀN ĐÃ CHỌN</span>
      <h4 style="font-size: 16px; margin: 8px 0; color: #fff;" id="activeUvpBadge">${chosen}</h4>
      <p style="font-size: 12.5px; color: var(--text-muted);">Tất cả bài viết và chiến dịch do AI sinh ra sẽ được định hình theo UVP độc quyền này.</p>
    </div>

    <!-- Custom UVP Input Section -->
    <div class="card" style="margin-bottom: 24px; background: rgba(30, 41, 59, 0.4); border-color: var(--border-subtle);">
      <h4 style="font-size: 14px; margin-bottom: 8px; color: #e2e8f0;">✏️ Tùy Chỉnh Định Vị Của Bạn:</h4>
      <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <input type="text" id="customUvpInput" class="form-input" placeholder="Nhập slogan / định vị độc quyền tùy chỉnh của bạn..." style="flex: 1; min-width: 260px; padding: 8px 14px; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-subtle); border-radius: 6px; color: #fff; font-size: 13px;">
        <button type="button" class="btn-primary" onclick="saveCustomUvp()" style="font-size: 13px; padding: 8px 16px;">
          Áp dụng
        </button>
      </div>
    </div>

    <h4 style="font-size: 15px; margin-bottom: 14px; color: var(--text-muted);">4 PHƯƠNG ÁN UVP DO AI ĐỀ XUẤT TỪ ĐIỂM YẾU ĐỐI THỦ:</h4>
    <div class="grid-cards">
      ${optionsHtml}
    </div>
  `;
}

async function selectUvp(uvpId) {
  if (!state.activeProjectId) return;
  try {
    const res = await fetch(`/api/v1/portal/projects/${state.activeProjectId}/select-uvp`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ selected_uvp_id: uvpId })
    });
    const data = await res.json();
    if (data.core_strengths) {
      if (state.activeProject) {
        state.activeProject.core_strengths = data.core_strengths;
      }
      const badge = document.getElementById('activeUvpBadge');
      if (badge) badge.textContent = data.core_strengths;
      alert(`Đã chọn thành công Phương án ${uvpId} làm định vị UVP độc quyền cho dự án!`);
    }
  } catch (err) {
    alert('Lỗi chọn UVP: ' + err.message);
  }
}

async function saveCustomUvp() {
  const input = document.getElementById('customUvpInput');
  const text = input ? input.value.trim() : '';
  if (!text || !state.activeProjectId) return;

  try {
    const res = await fetch(`/api/v1/portal/projects/${state.activeProjectId}/select-uvp`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ custom_uvp_text: text })
    });
    const data = await res.json();
    if (data.core_strengths) {
      if (state.activeProject) {
        state.activeProject.core_strengths = data.core_strengths;
      }
      const badge = document.getElementById('activeUvpBadge');
      if (badge) badge.textContent = data.core_strengths;
      input.value = '';
      alert('Đã cập nhật thành công định vị UVP tùy chỉnh cho dự án!');
    }
  } catch (err) {
    alert('Lỗi cập nhật UVP: ' + err.message);
  }
}

function renderCmsTab(project, gscConnections) {
  const container = document.getElementById('cmsContent');
  if (!container || !project) return;

  const hasCms = Boolean(project.cms_api_url && project.cms_auth_config && project.cms_auth_config.api_key);
  const apiKey = (project.cms_auth_config && project.cms_auth_config.api_key) || '';
  const maskedKey = apiKey ? `${apiKey.slice(0, 6)}...${apiKey.slice(-4)}` : '(Chưa có)';

  const gscConn = (gscConnections && gscConnections.length > 0) ? gscConnections[0] : null;
  const isGscConnected = Boolean(gscConn && gscConn.status === 'active');

  container.innerHTML = `
    <div class="grid-cards">
      <!-- 1. WORDPRESS OMNI BRIDGE CARD -->
      <div class="card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
          <span class="card-badge" style="background: rgba(16, 185, 129, 0.15); color: #10b981;">WordPress Omni Bridge</span>
          <span style="font-size: 11px; font-weight: 600; color: ${hasCms ? '#10b981' : '#f59e0b'};">
            ${hasCms ? '🟢 ĐÃ KẾT NỐI API KEY' : '🟡 CHƯA KẾT NỐI (DÙNG SANDBOX CLI)'}
          </span>
        </div>
        <h4 class="card-title">Kết nối Xuất bản Từ xa</h4>
        <div style="font-size: 13px; color: var(--text-muted); line-height: 1.6; margin-bottom: 14px;">
          <p><strong>Website CMS:</strong> <span style="color: #fff;">${project.cms_api_url || '(Chưa cấu hình URL)'}</span></p>
          <p><strong>API Secret Key:</strong> <code>${maskedKey}</code></p>
          <p><strong>Hỗ trợ:</strong> Rank Math SEO, WooCommerce Products, Media Sideloading.</p>
        </div>
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
          <a href="/api/v1/download/omni-bridge-connector" download class="btn-secondary" style="font-size: 12px; padding: 8px 12px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
            📥 Tải Plugin (.zip)
          </a>
          <button type="button" class="btn-secondary" onclick="document.getElementById('cmsConfigBox').classList.toggle('hidden')" style="font-size: 12px; padding: 8px 12px;">
            ⚙️ Cấu hình kết nối
          </button>
        </div>

        <!-- Inline Config Form -->
        <div id="cmsConfigBox" class="hidden" style="margin-top: 14px; padding-top: 14px; border-top: 1px solid var(--border-subtle);">
          <div style="display: flex; flex-direction: column; gap: 10px;">
            <div>
              <label style="font-size: 11.5px; color: var(--text-muted); display: block; margin-bottom: 4px;">URL Website WordPress của bạn</label>
              <input type="url" id="inputCmsUrl" class="form-input" value="${project.cms_api_url || project.target_website_url || ''}" placeholder="https://tongkhomi.com" style="width: 100%; padding: 8px; font-size: 12.5px; background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border-subtle); border-radius: 6px; color: #fff;">
            </div>
            <div>
              <label style="font-size: 11.5px; color: var(--text-muted); display: block; margin-bottom: 4px;">API Secret Key (Lấy từ WP Admin -> Settings -> Omni Bridge)</label>
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

// 4. Simple Markdown Parser
function parseMarkdown(text) {
  if (!text) return '';
  let html = text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;');

  // Code blocks ```code```
  html = html.replace(/```([a-z]*)\n([\s\S]*?)```/g, '<pre><code>$2</code></pre>');
  // Inline code `code`
  html = html.replace(/`([^`]+)`/g, '<code>$1</code>');
  // Bold **text**
  html = html.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
  // Italic *text*
  html = html.replace(/\*([^*]+)\*/g, '<em>$1</em>');
  // Headers ###
  html = html.replace(/^### (.*$)/gim, '<h3>$1</h3>');
  html = html.replace(/^## (.*$)/gim, '<h2>$1</h2>');
  html = html.replace(/^# (.*$)/gim, '<h1>$1</h1>');
  // Lists
  html = html.replace(/^\- (.*$)/gim, '<li>$1</li>');
  html = html.replace(/(<li>.*<\/li>)/s, '<ul>$1</ul>');
  // Paragraphs
  html = html.replace(/\n\n/g, '</p><p>');
  html = html.replace(/\n/g, '<br>');

  return `<p>${html}</p>`;
}

// ============================================================
// TAB 3: MA TRẬN TỪ KHÓA & XẾP HẠNG SERP ĐỐI THỦ
// ============================================================

function setKeywordViewMode(mode) {
  state.keywordViewMode = mode;
  state.masterPoolPage = 1;
  state.serpMatrixPage = 1;
  renderKeywordsTab(state.keywordMatrix, state.activeProject, state.masterKeywordPool);
}
window.setKeywordViewMode = setKeywordViewMode;

function filterMasterPoolBySource(src) {
  state.masterKeywordPoolFilterSource = src;
  state.masterPoolPage = 1;
  renderKeywordsTab(state.keywordMatrix, state.activeProject, state.masterKeywordPool);
}
window.filterMasterPoolBySource = filterMasterPoolBySource;

function filterMasterPoolByTier(tier) {
  state.masterKeywordPoolFilterTier = tier;
  state.masterPoolPage = 1;
  renderKeywordsTab(state.keywordMatrix, state.activeProject, state.masterKeywordPool);
}
window.filterMasterPoolByTier = filterMasterPoolByTier;

function filterMasterPoolByCat(cat) {
  state.masterKeywordPoolFilterCat = cat;
  state.masterPoolPage = 1;
  renderKeywordsTab(state.keywordMatrix, state.activeProject, state.masterKeywordPool);
}
window.filterMasterPoolByCat = filterMasterPoolByCat;

function searchMasterPool(query) {
  state.masterKeywordPoolSearchQuery = query;
  state.masterPoolPage = 1;
  renderKeywordsTab(state.keywordMatrix, state.activeProject, state.masterKeywordPool);
}
window.searchMasterPool = searchMasterPool;

function setMasterPoolPage(p) {
  state.masterPoolPage = Math.max(1, parseInt(p) || 1);
  renderKeywordsTab(state.keywordMatrix, state.activeProject, state.masterKeywordPool);
}
window.setMasterPoolPage = setMasterPoolPage;

function setMasterPoolPageSize(size) {
  state.masterPoolPageSize = Math.max(10, parseInt(size) || 50);
  state.masterPoolPage = 1;
  renderKeywordsTab(state.keywordMatrix, state.activeProject, state.masterKeywordPool);
}
window.setMasterPoolPageSize = setMasterPoolPageSize;

function changeMasterPoolPage(delta) {
  state.masterPoolPage = Math.max(1, (state.masterPoolPage || 1) + delta);
  renderKeywordsTab(state.keywordMatrix, state.activeProject, state.masterKeywordPool);
}
window.changeMasterPoolPage = changeMasterPoolPage;

function setSerpMatrixPage(p) {
  state.serpMatrixPage = Math.max(1, parseInt(p) || 1);
  renderKeywordsTab(state.keywordMatrix, state.activeProject, state.masterKeywordPool);
}
window.setSerpMatrixPage = setSerpMatrixPage;

function setSerpMatrixPageSize(size) {
  state.serpMatrixPageSize = Math.max(5, parseInt(size) || 15);
  state.serpMatrixPage = 1;
  renderKeywordsTab(state.keywordMatrix, state.activeProject, state.masterKeywordPool);
}
window.setSerpMatrixPageSize = setSerpMatrixPageSize;

function changeSerpMatrixPage(delta) {
  state.serpMatrixPage = Math.max(1, (state.serpMatrixPage || 1) + delta);
  renderKeywordsTab(state.keywordMatrix, state.activeProject, state.masterKeywordPool);
}
window.changeSerpMatrixPage = changeSerpMatrixPage;

// HÀM TẠO THANH PHÂN TRANG CÓ SỐ THỨ TỰ (NUMBERED PAGINATION COMPONENT)
function renderPaginationHtml(currentPage, totalPages, totalItems, pageSize, changePageFnName, changePageSizeFnName, sizeOptions = [15, 30, 50, 100]) {
  if (totalItems === 0) return '';
  const safePage = Math.min(Math.max(1, currentPage), totalPages || 1);

  // Sinh danh sách số trang hiển thị thông minh (VD: 1, 2, 3, 4, 5... hoặc 1 ... 4 5 6 ... 26)
  let pageNumbers = [];
  if (totalPages <= 7) {
    for (let i = 1; i <= totalPages; i++) pageNumbers.push(i);
  } else {
    pageNumbers.push(1);
    let start = Math.max(2, safePage - 2);
    let end = Math.min(totalPages - 1, safePage + 2);

    if (start > 2) pageNumbers.push('...');
    for (let i = start; i <= end; i++) pageNumbers.push(i);
    if (end < totalPages - 1) pageNumbers.push('...');
    pageNumbers.push(totalPages);
  }

  const buttonsHtml = pageNumbers.map(p => {
    if (p === '...') {
      return `<span style="padding: 4px 6px; color: var(--text-dim); font-size: 13px; user-select: none;">...</span>`;
    }
    const isActive = p === safePage;
    return `
      <button type="button" onclick="${changePageFnName}(${p})" 
        style="min-width: 32px; height: 32px; padding: 0 8px; font-size: 12px; font-weight: ${isActive ? '700' : '500'}; 
        background: ${isActive ? 'var(--primary)' : 'rgba(30, 41, 59, 0.8)'}; 
        color: ${isActive ? '#ffffff' : '#cbd5e1'}; 
        border: 1px solid ${isActive ? 'var(--primary)' : 'rgba(148, 163, 184, 0.25)'}; 
        border-radius: 6px; cursor: pointer; transition: all 0.2s; box-shadow: ${isActive ? '0 2px 8px var(--primary-glow)' : 'none'};">
        ${p}
      </button>
    `;
  }).join('');

  const startIdx = Math.min((safePage - 1) * pageSize + 1, totalItems);
  const endIdx = Math.min(safePage * pageSize, totalItems);

  return `
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; margin: 16px 0; padding: 12px 16px; background: rgba(15, 23, 42, 0.65); border: 1px solid var(--border-subtle); border-radius: 8px;">
      <div style="font-size: 12px; color: var(--text-muted); display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
        <span>Hiển thị <strong>${startIdx} - ${endIdx}</strong> trong <strong>${totalItems.toLocaleString()}</strong> từ khóa</span>
        ${changePageSizeFnName ? `
          <span style="color: var(--text-dim);">|</span>
          <label style="display: flex; align-items: center; gap: 4px; color: #cbd5e1;">
            <span>Mỗi trang:</span>
            <select onchange="${changePageSizeFnName}(this.value)" style="padding: 3px 6px; background: rgba(30, 41, 59, 0.95); border: 1px solid rgba(148, 163, 184, 0.3); border-radius: 4px; color: #fff; font-size: 11.5px; cursor: pointer;">
              ${sizeOptions.map(sz => `<option value="${sz}" ${pageSize === sz ? 'selected' : ''}>${sz} từ</option>`).join('')}
            </select>
          </label>
        ` : ''}
      </div>

      <div style="display: flex; gap: 5px; align-items: center; flex-wrap: wrap;">
        <button type="button" onclick="${changePageFnName}(1)" ${safePage <= 1 ? 'disabled style="opacity: 0.35; cursor: not-allowed;"' : 'style="cursor: pointer;"'} 
          style="padding: 4px 10px; height: 32px; font-size: 11.5px; background: rgba(30, 41, 59, 0.8); border: 1px solid rgba(148, 163, 184, 0.2); border-radius: 6px; color: #cbd5e1;">
          « Đầu
        </button>
        <button type="button" onclick="${changePageFnName}(${safePage - 1})" ${safePage <= 1 ? 'disabled style="opacity: 0.35; cursor: not-allowed;"' : 'style="cursor: pointer;"'} 
          style="padding: 4px 10px; height: 32px; font-size: 11.5px; background: rgba(30, 41, 59, 0.8); border: 1px solid rgba(148, 163, 184, 0.2); border-radius: 6px; color: #cbd5e1;">
          ‹ Trước
        </button>
        
        ${buttonsHtml}

        <button type="button" onclick="${changePageFnName}(${safePage + 1})" ${safePage >= totalPages ? 'disabled style="opacity: 0.35; cursor: not-allowed;"' : 'style="cursor: pointer;"'} 
          style="padding: 4px 10px; height: 32px; font-size: 11.5px; background: rgba(30, 41, 59, 0.8); border: 1px solid rgba(148, 163, 184, 0.2); border-radius: 6px; color: #cbd5e1;">
          Sau ›
        </button>
        <button type="button" onclick="${changePageFnName}(${totalPages})" ${safePage >= totalPages ? 'disabled style="opacity: 0.35; cursor: not-allowed;"' : 'style="cursor: pointer;"'} 
          style="padding: 4px 10px; height: 32px; font-size: 11.5px; background: rgba(30, 41, 59, 0.8); border: 1px solid rgba(148, 163, 184, 0.2); border-radius: 6px; color: #cbd5e1;">
          Cuối »
        </button>
      </div>

      <div style="display: flex; align-items: center; gap: 6px; font-size: 12px; color: var(--text-muted);">
        <span>Trang: <strong>${safePage}/${totalPages}</strong></span>
        <span style="color: var(--text-dim);">|</span>
        <span>Đi tới:</span>
        <input type="number" min="1" max="${totalPages}" value="${safePage}" 
          onkeydown="if(event.key==='Enter'){ ${changePageFnName}(parseInt(this.value)); }"
          onchange="${changePageFnName}(parseInt(this.value))"
          style="width: 48px; padding: 4px 6px; text-align: center; background: rgba(30, 41, 59, 0.95); border: 1px solid rgba(148, 163, 184, 0.3); border-radius: 4px; color: #fff; font-size: 12px;">
      </div>
    </div>
  `;
}

function renderKeywordsTab(matrix, project, masterKeywordPool) {
  const p = project || state.activeProject;
  if (!p || !state.activeProjectId) {
    renderEmptyState();
    return;
  }

  // Đảm bảo thanh công cụ thêm từ khóa hiển thị khi có dự án
  const kwAddBar = document.getElementById('keywordAddBar');
  if (kwAddBar) kwAddBar.style.display = 'flex';

  const kwActionBtns = document.getElementById('keywordActionButtons');
  if (kwActionBtns) kwActionBtns.style.display = 'flex';

  const kwInput = document.getElementById('newKeywordInput');
  if (kwInput) {
    kwInput.placeholder = `Nhập từ khóa mới cần phân tích cho ${p.name || 'dự án'} (VD: model, dòng sản phẩm)...`;
  }

  state.keywordMatrix = matrix || state.keywordMatrix || [];
  state.masterKeywordPool = masterKeywordPool || state.masterKeywordPool || [];
  state.keywordViewMode = state.keywordViewMode || 'serp_matrix';

  const container = document.getElementById('keywordMatrixContainer');
  const filterBar = document.getElementById('keywordCategoryFilterBar');
  if (!container) return;

  // Cập nhật dropdown danh mục động 100% theo dữ liệu của dự án này
  updateKeywordCategoryDropdown(p, state.keywordMatrix, state.activeIntelligence?.seed_analysis);

  // 1. THANH ĐIỀU HƯỚNG CHUYỂN ĐỔI CHẾ ĐỘ XEM (DUAL VIEW SWITCHER)
  const poolLen = state.masterKeywordPool.length;
  const matrixLen = state.keywordMatrix.length;

  let modeSwitcherHtml = `
    <div style="background: rgba(15, 23, 42, 0.85); border: 1px solid var(--border-subtle); border-radius: 10px; padding: 14px 18px; margin-bottom: 20px;">
      <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
          <button type="button" class="btn-primary" onclick="setKeywordViewMode('serp_matrix')" 
            style="padding: 9px 18px; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.2s;
            background: ${state.keywordViewMode === 'serp_matrix' ? 'linear-gradient(135deg, #10b981 0%, #059669 100%)' : 'rgba(30, 41, 59, 0.8)'};
            color: #fff; border: 1px solid ${state.keywordViewMode === 'serp_matrix' ? '#10b981' : 'rgba(148, 163, 184, 0.25)'};
            box-shadow: ${state.keywordViewMode === 'serp_matrix' ? '0 2px 10px rgba(16, 185, 129, 0.35)' : 'none'};">
            <span>🎯</span> 
            <span>Ma Trận SERP Trọng Điểm</span>
            <span style="background: rgba(0,0,0,0.3); padding: 2px 8px; border-radius: 12px; font-size: 11px;">${matrixLen} từ</span>
          </button>

          <button type="button" class="btn-primary" onclick="setKeywordViewMode('master_pool')" 
            style="padding: 9px 18px; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.2s;
            background: ${state.keywordViewMode === 'master_pool' ? 'linear-gradient(135deg, #6366f1 0%, #4f46e5 100%)' : 'rgba(30, 41, 59, 0.8)'};
            color: #fff; border: 1px solid ${state.keywordViewMode === 'master_pool' ? '#6366f1' : 'rgba(148, 163, 184, 0.25)'};
            box-shadow: ${state.keywordViewMode === 'master_pool' ? '0 2px 10px rgba(99, 102, 241, 0.35)' : 'none'};">
            <span>📚</span> 
            <span>Kho Từ Khóa Tổng Lực Bóc Tách</span>
            <span style="background: rgba(0,0,0,0.3); padding: 2px 8px; border-radius: 12px; font-size: 11px;">${poolLen.toLocaleString()} từ (10 đối thủ)</span>
          </button>
        </div>

        <div style="font-size: 12px; color: var(--text-muted); display: flex; align-items: center; gap: 8px;">
          <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: ${state.keywordViewMode === 'master_pool' ? '#6366f1' : '#10b981'};"></span>
          <span>Chế độ: <strong style="color: #fff;">${state.keywordViewMode === 'master_pool' ? 'Kho 1.279 Từ Khóa (Có phân trang số 1, 2, 3...)' : 'Ma Trận SERP Trọng Điểm (Có phân trang số 1, 2, 3...)'}</strong></span>
        </div>
      </div>
    </div>
  `;

  // NẾU ĐANG Ở CHẾ ĐỘ 2: KHO TỪ KHÓA TỔNG LỰC BÓC TÁCH (MASTER KEYWORD POOL)
  if (state.keywordViewMode === 'master_pool') {
    if (filterBar) filterBar.innerHTML = '';
    container.innerHTML = modeSwitcherHtml;
    const poolContainer = document.createElement('div');
    container.appendChild(poolContainer);
    renderMasterKeywordPool(poolContainer, state.masterKeywordPool);
    return;
  }

  // NẾU ĐANG Ở CHẾ ĐỘ 1: MA TRẬN SERP TRỌNG ĐIỂM
  // 2. Render Thanh Lọc Category (Category Filter Bar)
  if (filterBar) {
    const rawCategories = state.keywordMatrix.map(m => m.category).filter(Boolean);
    const uniqueCategories = ['all', ...Array.from(new Set(rawCategories))];
    filterBar.innerHTML = uniqueCategories.map(cat => {
      const count = cat === 'all' ? state.keywordMatrix.length : state.keywordMatrix.filter(m => m.category === cat).length;
      const label = cat === 'all' ? `🌐 Tất cả (${count})` : `${cat} (${count})`;
      const isActive = (state.selectedKeywordCategory || 'all') === cat;
      return `
        <button type="button" class="cat-filter-pill ${isActive ? 'active' : ''}" onclick="filterKeywordsByCategory('${cat}')">
          ${label}
        </button>
      `;
    }).join('');
  }

  // 3. Lọc dữ liệu theo Category được chọn
  const filtered = (state.selectedKeywordCategory || 'all') === 'all' 
    ? state.keywordMatrix 
    : state.keywordMatrix.filter(m => m.category === state.selectedKeywordCategory);

  if (filtered.length === 0) {
    container.innerHTML = modeSwitcherHtml + `
      <div style="text-align: center; padding: 30px; color: var(--text-muted); background: var(--bg-card); border-radius: var(--radius-md); border: 1px dashed var(--border-subtle);">
        Không có từ khóa nào thuộc danh mục "${state.selectedKeywordCategory}".
      </div>
    `;
    return;
  }

  // 4. PHÂN TRANG CÓ SỐ THỨ TỰ CHO MA TRẬN SERP
  const matrixPage = state.serpMatrixPage || 1;
  const matrixPageSize = state.serpMatrixPageSize || 15;
  const matrixTotalPages = Math.ceil(filtered.length / matrixPageSize) || 1;
  const safeMatrixPage = Math.min(matrixPage, matrixTotalPages);
  const pagedMatrixItems = filtered.slice((safeMatrixPage - 1) * matrixPageSize, safeMatrixPage * matrixPageSize);

  const topPaginationHtml = renderPaginationHtml(
    safeMatrixPage, 
    matrixTotalPages, 
    filtered.length, 
    matrixPageSize, 
    'setSerpMatrixPage', 
    'setSerpMatrixPageSize',
    [10, 15, 30, 60]
  );

  const bottomPaginationHtml = renderPaginationHtml(
    safeMatrixPage, 
    matrixTotalPages, 
    filtered.length, 
    matrixPageSize, 
    'setSerpMatrixPage', 
    'setSerpMatrixPageSize',
    [10, 15, 30, 60]
  );

  // 5. Render danh sách thẻ Ma Trận Từ Khóa & SERP
  const cardsHtml = pagedMatrixItems.map(item => {
    let intentClass = 'intent-commercial';
    let intentText = '🔍 Thương mại';
    if (item.search_intent && (item.search_intent.includes('Giao dịch') || item.search_intent.includes('Transactional'))) {
      intentClass = 'intent-transactional';
      intentText = '🛒 Giao dịch';
    } else if (item.search_intent && (item.search_intent.includes('Thông tin') || item.search_intent.includes('Informational'))) {
      intentClass = 'intent-informational';
      intentText = '💡 Thông tin';
    }

    const comps = item.top_competitors || [];
    const topDomain = comps[0]?.domain || '';

    return `
      <div class="kw-matrix-card" id="kw-card-${item.id}">
        <div class="kw-header">
          <div class="kw-title-group">
            <span class="kw-keyword-name">🔑 ${item.keyword}</span>
            <span class="kw-intent-badge ${intentClass}">${intentText}</span>
            <span class="kw-category-badge">📂 ${item.category || 'Gia dụng thông minh'}</span>
            <span class="kw-volume-badge">📊 ${item.search_volume_est || 'Ước tính SERP'}</span>
            <span style="font-size: 11px; color: var(--text-dim);">Độ cạnh tranh: <strong style="color: #cbd5e1;">${item.competition_level || 'Trung bình'}</strong></span>
          </div>
          <div style="font-size: 11.5px; color: var(--text-dim);">
            Quét lúc: ${item.last_scanned_at || 'Mới cập nhật'}
          </div>
        </div>

        <!-- DANH SÁCH TOP ĐỐI THỦ GIỮ THỨ HẠNG TỪ KHÓA NÀY -->
        <div class="kw-competitor-list">
          <div style="font-size: 11px; font-weight: 600; color: var(--text-dim); text-transform: uppercase; margin-bottom: 6px; display: flex; justify-content: space-between; border-bottom: 1px solid rgba(255,255,255,0.08); padding-bottom: 4px;">
            <span>Top Đối thủ đang giữ thứ hạng Google (100% Online)</span>
            <span>Landing Page đối thủ dùng để SEO</span>
          </div>
          ${comps.length > 0 ? comps.map(c => {
            const rankClass = c.rank === 1 ? 'rank-1' : (c.rank === 2 ? 'rank-2' : (c.rank === 3 ? 'rank-3' : 'rank-other'));
            const isBrandWarning = c.is_brand_competitor ? '<span style="color: #ef4444; font-size: 10.5px; margin-left: 6px;">🚨 Trùng Brand</span>' : '';
            const isCompAd = c.is_ad || c.competitor_type === 'paid_ads';
            const compTypeBadge = isCompAd
              ? '<span style="background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.35); font-size: 9.5px; font-weight: 700; padding: 1px 5px; border-radius: 3px;">📢 ADS</span>'
              : '<span style="background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.35); font-size: 9.5px; font-weight: 700; padding: 1px 5px; border-radius: 3px;">🌿 SEO</span>';

            return `
              <div class="kw-comp-item">
                <div style="display: flex; align-items: center; gap: 8px; min-width: 240px;">
                  <span class="kw-rank-pill ${rankClass}">#${c.rank}</span>
                  ${compTypeBadge}
                  <span style="font-weight: 600; color: #fff;">${c.domain}</span>
                  ${isBrandWarning}
                </div>
                <div style="flex: 1; overflow: hidden; padding: 0 10px;">
                  <span style="color: var(--text-muted); font-size: 12px; display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                    ${c.title || c.domain}
                  </span>
                </div>
                <div style="flex-shrink: 0;">
                  <a href="${c.landing_url || c.url}" target="_blank" rel="noopener noreferrer" class="kw-comp-link" title="${c.landing_url || c.url}">
                    ${(c.landing_url || c.url) ? ((c.landing_url || c.url).length > 45 ? (c.landing_url || c.url).substring(0, 42) + '...' : (c.landing_url || c.url)) : 'Trang chủ'} ↗
                  </a>
                </div>
              </div>
            `;
          }).join('') : '<div style="color: var(--text-dim); font-size: 12px; padding: 4px 0;">Chưa phát hiện đối thủ trực tiếp cho từ khóa này.</div>'}
        </div>

        ${item.recommended_action ? `
          <div class="kw-recommendation">
            <strong>🎯 Đề xuất Content SEO:</strong> ${item.recommended_action}
          </div>
        ` : ''}

        <div class="kw-actions">
          <button type="button" class="btn-kw-action btn-kw-copilot" onclick="sendKeywordToCopilot('${escapeHtml(item.keyword)}', '${escapeHtml(topDomain)}')">
            <span>✍️ Đẩy sang Copilot Lập Bài SEO SOP 12</span>
          </button>
          <button type="button" class="btn-kw-action btn-kw-rescan" onclick="rescanSingleKeyword('${escapeHtml(item.keyword)}', '${escapeHtml(item.category || '')}')">
            <span>🔄 Quét Lại SERP Live</span>
          </button>
          <button type="button" class="btn-kw-action btn-kw-delete" onclick="deleteKeyword('${item.id || item.keyword}')" title="Xóa từ khóa khỏi ma trận">
            <span>🗑️ Xóa</span>
          </button>
        </div>
      </div>
    `;
  }).join('');

  container.innerHTML = modeSwitcherHtml + topPaginationHtml + cardsHtml + bottomPaginationHtml;
}

// BẢNG KHO TỪ KHÓA TỔNG LỰC BÓC TÁCH TỪ 10 ĐỐI THỦ
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

function filterKeywordsByCategory(cat) {
  state.selectedKeywordCategory = cat;
  state.serpMatrixPage = 1;
  renderKeywordsTab(state.keywordMatrix, state.activeProject, state.masterKeywordPool);
}
window.filterKeywordsByCategory = filterKeywordsByCategory;

async function submitNewKeywordScan() {
  const input = document.getElementById('newKeywordInput');
  const catSelect = document.getElementById('newKeywordCategory');
  const btn = document.getElementById('btnSubmitKeyword');
  if (!input) return;

  const kw = input.value.trim();
  const cat = catSelect ? catSelect.value : 'Gia dụng thông minh';
  if (!kw) {
    alert('Vui lòng nhập từ khóa cần quét SERP!');
    input.focus();
    return;
  }

  if (!state.activeProjectId) {
    alert('Vui lòng chọn dự án trước!');
    return;
  }

  const originalBtnHtml = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<span>⏳ Đang quét Google SERP...</span>';

  try {
    const res = await fetch(`/api/v1/portal/projects/${state.activeProjectId}/keywords/scan`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ keyword: kw, category: cat })
    });
    const data = await res.json();
    if (data.status === 'success') {
      input.value = '';
      state.keywordMatrix = data.keyword_matrix || [];
      state.selectedKeywordCategory = 'all';
      renderKeywordsTab(state.keywordMatrix, state.activeProject);
    } else {
      alert('Không thể quét từ khóa: ' + (data.detail || 'Lỗi không xác định'));
    }
  } catch (err) {
    alert('Lỗi kết nối khi quét SERP: ' + err.message);
  } finally {
    btn.disabled = false;
    btn.innerHTML = originalBtnHtml;
  }
}

async function rescanSingleKeyword(keyword, category) {
  if (!state.activeProjectId || !keyword) return;

  const cards = Array.from(document.querySelectorAll('.kw-matrix-card'));
  const card = cards.find(el => el.textContent.includes(keyword));
  if (card) {
    card.style.opacity = '0.5';
  }

  try {
    const res = await fetch(`/api/v1/portal/projects/${state.activeProjectId}/keywords/scan`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ keyword, category })
    });
    const data = await res.json();
    if (data.status === 'success') {
      state.keywordMatrix = data.keyword_matrix || [];
      renderKeywordsTab(state.keywordMatrix, state.activeProject);
    }
  } catch (err) {
    alert('Lỗi khi quét lại từ khóa: ' + err.message);
    if (card) card.style.opacity = '1';
  }
}

async function deleteKeyword(keywordId) {
  if (!confirm('Bạn có chắc chắn muốn xóa từ khóa này khỏi ma trận theo dõi?')) return;
  try {
    const res = await fetch(`/api/v1/portal/projects/${state.activeProjectId}/keywords/${encodeURIComponent(keywordId)}`, {
      method: 'DELETE'
    });
    const data = await res.json();
    if (data.status === 'success') {
      state.keywordMatrix = data.keyword_matrix || [];
      renderKeywordsTab(state.keywordMatrix, state.activeProject);
    }
  } catch (err) {
    alert('Lỗi xóa từ khóa: ' + err.message);
  }
}

function sendKeywordToCopilot(keyword, topDomain) {
  switchTab('chat');
  const chatInput = document.getElementById('chatInput');
  if (chatInput) {
    const website = state.activeProject?.target_website_url || 'tongkhomi.com';
    chatInput.value = `Hãy nghiên cứu và lập dàn ý, viết một bài viết chuẩn SEO SOP 12 cho từ khóa "${keyword}". 
Mục tiêu: Đánh bại đối thủ ${topDomain || 'dẫn đầu trên Google'}, nêu bật lợi thế độc quyền (giá sỉ tại kho, bảo hành chính hãng của ${website}) và cấu trúc heading giải quyết triệt để nỗi đau của khách hàng.`;
    chatInput.focus();
  }
}

async function refreshKeywordsTab() {
  if (!state.activeProjectId) return;
  const btn = document.querySelector('#keywordActionButtons button');
  if (btn) btn.innerHTML = '<span>⏳</span> <span>Đang Tái Tạo Ma Trận 60+ & Kho 1.200+ Từ Khóa...</span>';
  try {
    const res = await fetch(`/api/v1/portal/projects/${state.activeProjectId}/keywords/regenerate`, {
      method: 'POST'
    });
    const data = await res.json();
    if (data.status === 'success' && data.keyword_matrix) {
      state.keywordMatrix = data.keyword_matrix;
      state.masterKeywordPool = data.master_keyword_pool || [];
      renderKeywordsTab(state.keywordMatrix, state.activeProject, state.masterKeywordPool);
      if (window.toast) window.toast.success(`Đã tái tạo Ma trận ${data.count} từ khóa & Kho ${(data.master_pool_count || state.masterKeywordPool.length).toLocaleString()} từ khóa!`);
    } else {
      await loadProjectIntelligence(state.activeProjectId);
    }
  } catch (err) {
    console.error('Lỗi tái tạo ma trận từ khóa:', err);
    await loadProjectIntelligence(state.activeProjectId);
  } finally {
    if (btn) btn.innerHTML = '<span>🔄</span> <span>Làm Mới Ma Trận</span>';
  }
}

function escapeHtml(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/'/g, '&#39;')
    .replace(/"/g, '&quot;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;');
}

// ============================================================
// TAB 5: QUẢN LÝ CÀI ĐẶT & API (SERPER.DEV & AI BYOK)
// ============================================================
function filterCompetitorsByType(type) {
  const allBtns = document.querySelectorAll('.btn-filter-comp');
  allBtns.forEach(btn => {
    const isActive = btn.getAttribute('data-filter') === type;
    btn.classList.toggle('active', isActive);
    if (isActive) {
      btn.style.background = 'rgba(99, 102, 241, 0.3)';
    } else {
      btn.style.background = 'transparent';
    }
  });

  const cards = document.querySelectorAll('#competitorsGrid .comp-card');
  cards.forEach(card => {
    const cardType = card.getAttribute('data-comp-type');
    if (type === 'all' || cardType === type) {
      card.style.display = 'block';
    } else {
      card.style.display = 'none';
    }
  });
}
window.filterCompetitorsByType = filterCompetitorsByType;

function toggleSerpFields() {
  const provider = document.getElementById('settingSerpProvider')?.value;
  const box = document.getElementById('serperConfigContainer');
  if (box) {
    box.style.display = (provider === 'serper') ? 'block' : 'none';
  }
}
window.toggleSerpFields = toggleSerpFields;

function toggleAiModeFields() {
  const isCustom = document.getElementById('radioAiCustom')?.checked;
  const byokBox = document.getElementById('aiByokContainer');
  const badge = document.getElementById('aiStatusBadge');
  if (byokBox) {
    byokBox.style.display = isCustom ? 'block' : 'none';
  }
  if (badge) {
    if (isCustom) {
      badge.textContent = '⚡ BYOK Tự Cung Cấp';
      badge.style.color = '#38bdf8';
      badge.style.borderColor = 'rgba(56, 189, 248, 0.4)';
      badge.style.background = 'rgba(56, 189, 248, 0.15)';
    } else {
      badge.textContent = '💎 Hệ Thống 9Router';
      badge.style.color = '#34d399';
      badge.style.borderColor = 'rgba(16, 185, 129, 0.4)';
      badge.style.background = 'rgba(16, 185, 129, 0.15)';
    }
  }
}
window.toggleAiModeFields = toggleAiModeFields;

function toggleAuthorityFields() {
  const mode = document.getElementById('settingAuthorityMode')?.value;
  const mozContainer = document.getElementById('mozConfigContainer');
  if (mozContainer) {
    mozContainer.style.display = (mode === 'moz_pool') ? 'block' : 'none';
  }
}
window.toggleAuthorityFields = toggleAuthorityFields;

function updateDefaultAiEndpoints() {
  const provider = document.getElementById('settingAiProvider')?.value;
  const baseInput = document.getElementById('settingAiBaseUrl');
  const modelInput = document.getElementById('settingAiModelName');
  if (!baseInput || !modelInput) return;

  if (provider === 'openai') {
    baseInput.value = 'https://api.openai.com/v1';
    modelInput.value = 'gpt-4o';
  } else if (provider === 'gemini') {
    baseInput.value = 'https://generativelanguage.googleapis.com/v1beta/openai';
    modelInput.value = 'gemini-1.5-flash';
  } else if (provider === 'anthropic') {
    baseInput.value = 'https://api.anthropic.com/v1';
    modelInput.value = 'claude-3-5-sonnet-20241022';
  }
}
window.updateDefaultAiEndpoints = updateDefaultAiEndpoints;

async function loadSettings() {
  const badgeSerp = document.getElementById('serpStatusBadge');
  try {
    const res = await fetch('/api/v1/portal/settings');
    const data = await res.json();
    if (!res.ok || data.status !== 'success') return;

    // 1. Điền SERP Config
    const sc = data.serp_config || {};
    const provSelect = document.getElementById('settingSerpProvider');
    if (provSelect && sc.provider) provSelect.value = sc.provider;

    const keysInput = document.getElementById('settingSerperKeys');
    if (keysInput && sc.raw_keys_display) {
      keysInput.value = sc.raw_keys_display;
    }

    if (sc.gl) document.getElementById('settingSerpGl').value = sc.gl;
    if (sc.hl) document.getElementById('settingSerpHl').value = sc.hl;

    if (badgeSerp) {
      if (sc.has_keys) {
        badgeSerp.textContent = `🟢 Serper.dev (${sc.key_count} keys)`;
        badgeSerp.style.color = '#34d399';
        badgeSerp.style.borderColor = 'rgba(16, 185, 129, 0.4)';
      } else {
        badgeSerp.textContent = '⚪ Chưa nhập key (Dùng DuckDuckGo)';
        badgeSerp.style.color = '#94a3b8';
      }
    }
    toggleSerpFields();

    // 2. Điền AI Config
    const ac = data.ai_config || {};
    if (ac.mode === 'custom') {
      document.getElementById('radioAiCustom').checked = true;
    } else {
      document.getElementById('radioAiSystem').checked = true;
    }

    if (ac.provider) document.getElementById('settingAiProvider').value = ac.provider;
    if (ac.api_key_masked) document.getElementById('settingAiApiKey').value = ac.api_key_masked;
    if (ac.base_url) document.getElementById('settingAiBaseUrl').value = ac.base_url;
    if (ac.model_name) document.getElementById('settingAiModelName').value = ac.model_name;

    toggleAiModeFields();

    // 3. Điền Thẩm Quyền Authority Config
    const authCfg = data.authority_config || {};
    const authModeSelect = document.getElementById('settingAuthorityMode');
    if (authModeSelect && authCfg.mode) {
      authModeSelect.value = authCfg.mode;
    }
    const mozKeysInput = document.getElementById('settingMozKeys');
    if (mozKeysInput && authCfg.raw_keys_display) {
      mozKeysInput.value = authCfg.raw_keys_display;
    }
    const dorkingCb = document.getElementById('settingEnableDorking');
    if (dorkingCb && typeof authCfg.enable_dorking === 'boolean') {
      dorkingCb.checked = authCfg.enable_dorking;
    }
    const badgeAuth = document.getElementById('authorityStatusBadge');
    if (badgeAuth) {
      if (authCfg.mode === 'moz_pool' && authCfg.has_moz_keys) {
        badgeAuth.textContent = `🟢 Moz Pool (${authCfg.moz_key_count} keys)`;
        badgeAuth.style.color = '#38bdf8';
        badgeAuth.style.borderColor = 'rgba(56, 189, 248, 0.4)';
      } else {
        badgeAuth.textContent = '⚪ Cơ chế 0đ (RDAP & Dorking)';
        badgeAuth.style.color = '#94a3b8';
      }
    }
    toggleAuthorityFields();
  } catch (err) {
    console.error('Lỗi tải cài đặt:', err);
  }
}
window.loadSettings = loadSettings;

async function saveAllSettings() {
  const btnTop = document.getElementById('btnSaveTopSettings');
  const btnBottom = document.getElementById('btnSaveBottomSettings');
  if (btnTop) btnTop.disabled = true;
  if (btnBottom) btnBottom.disabled = true;

  const serpProvider = document.getElementById('settingSerpProvider')?.value || 'serper';
  const serperKeys = document.getElementById('settingSerperKeys')?.value || '';
  const gl = document.getElementById('settingSerpGl')?.value || 'vn';
  const hl = document.getElementById('settingSerpHl')?.value || 'vi';

  const aiMode = document.querySelector('input[name="aiModeRadio"]:checked')?.value || 'system';
  const aiProvider = document.getElementById('settingAiProvider')?.value || 'openai';
  const aiApiKey = document.getElementById('settingAiApiKey')?.value || '';
  const aiBaseUrl = document.getElementById('settingAiBaseUrl')?.value || '';
  const aiModelName = document.getElementById('settingAiModelName')?.value || '';

  const authMode = document.getElementById('settingAuthorityMode')?.value || 'free_rdap_dork';
  const mozKeys = document.getElementById('settingMozKeys')?.value || '';
  const enableDorking = document.getElementById('settingEnableDorking')?.checked ?? true;

  try {
    const res = await fetch('/api/v1/portal/settings', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        serp_provider: serpProvider,
        serper_api_keys: serperKeys,
        gl: gl,
        hl: hl,
        ai_mode: aiMode,
        ai_provider: aiProvider,
        ai_api_key: aiApiKey,
        ai_base_url: aiBaseUrl,
        ai_model_name: aiModelName,
        authority_mode: authMode,
        moz_api_keys: mozKeys,
        enable_dorking: enableDorking
      })
    });
    const data = await res.json();
    if (res.ok && data.status === 'success') {
      alert('✅ ' + (data.message || 'Đã lưu cấu hình thành công!'));
      await loadSettings();
    } else {
      alert('❌ Lỗi khi lưu cấu hình: ' + (data.detail || data.message || 'Lỗi không xác định'));
    }
  } catch (err) {
    alert('❌ Lỗi kết nối khi lưu cấu hình: ' + err.message);
  } finally {
    if (btnTop) btnTop.disabled = false;
    if (btnBottom) btnBottom.disabled = false;
  }
}
window.saveAllSettings = saveAllSettings;

async function testAuthorityConnection() {
  const btn = document.getElementById('btnTestAuthority');
  const result = document.getElementById('authorityTestResult');
  const mozKeys = document.getElementById('settingMozKeys')?.value || '';

  btn.disabled = true;
  result.textContent = '⏳ Đang kiểm tra...';
  result.style.color = '#38bdf8';

  try {
    const res = await fetch('/api/v1/portal/settings/test-authority', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ moz_api_keys: mozKeys, test_domain: 'viomiviet.vn' })
    });
    const data = await res.json();
    if (res.ok && data.status === 'success') {
      result.textContent = '✅ Hoàn tất!';
      result.style.color = '#34d399';
      alert(data.message);
    } else {
      result.textContent = '❌ Thất bại';
      result.style.color = '#ef4444';
      alert('Lỗi: ' + (data.detail || data.message));
    }
  } catch (err) {
    result.textContent = '❌ Lỗi kết nối';
    result.style.color = '#ef4444';
    alert('Lỗi kết nối: ' + err.message);
  } finally {
    btn.disabled = false;
  }
}
window.testAuthorityConnection = testAuthorityConnection;

async function testSerpConnection() {
  const btn = document.getElementById('btnTestSerp');
  const result = document.getElementById('serpTestResult');
  const keys = document.getElementById('settingSerperKeys')?.value || '';
  const gl = document.getElementById('settingSerpGl')?.value || 'vn';
  const hl = document.getElementById('settingSerpHl')?.value || 'vi';

  if (!keys) {
    alert('Vui lòng nhập Serper.dev API Key trước khi kiểm tra!');
    return;
  }

  btn.disabled = true;
  result.textContent = '⏳ Đang kiểm tra...';
  result.style.color = '#38bdf8';

  try {
    const res = await fetch('/api/v1/portal/settings/test-serp', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ serper_api_keys: keys, gl: gl, hl: hl })
    });
    const data = await res.json();
    if (res.ok && data.status === 'success') {
      result.textContent = '✅ Kết nối thành công!';
      result.style.color = '#34d399';
      alert(data.message);
    } else {
      result.textContent = '❌ Kiểm tra thất bại';
      result.style.color = '#ef4444';
      alert('Lỗi kiểm tra Serper: ' + (data.detail || data.message));
    }
  } catch (err) {
    result.textContent = '❌ Lỗi kết nối';
    result.style.color = '#ef4444';
    alert('Lỗi kết nối: ' + err.message);
  } finally {
    btn.disabled = false;
  }
}
window.testSerpConnection = testSerpConnection;

async function testAiConnection() {
  const btn = document.getElementById('btnTestAi');
  const result = document.getElementById('aiTestResult');
  const provider = document.getElementById('settingAiProvider')?.value || 'openai';
  const apiKey = document.getElementById('settingAiApiKey')?.value || '';
  const baseUrl = document.getElementById('settingAiBaseUrl')?.value || '';
  const model = document.getElementById('settingAiModelName')?.value || '';

  if (!apiKey) {
    alert('Vui lòng nhập AI API Key trước khi kiểm tra!');
    return;
  }

  btn.disabled = true;
  result.textContent = '⏳ Đang gửi ping thử nghiệm...';
  result.style.color = '#38bdf8';

  try {
    const res = await fetch('/api/v1/portal/settings/test-ai', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        provider: provider,
        api_key: apiKey,
        base_url: baseUrl,
        model_name: model
      })
    });
    const data = await res.json();
    if (res.ok && data.status === 'success') {
      result.textContent = '✅ Kết nối AI thành công!';
      result.style.color = '#34d399';
      alert(data.message);
    } else {
      result.textContent = '❌ Kết nối AI thất bại';
      result.style.color = '#ef4444';
      alert('Lỗi kết nối AI: ' + (data.detail || data.message));
    }
  } catch (err) {
    result.textContent = '❌ Lỗi kết nối';
    result.style.color = '#ef4444';
    alert('Lỗi kết nối: ' + err.message);
  } finally {
    btn.disabled = false;
  }
}
window.testAiConnection = testAiConnection;


