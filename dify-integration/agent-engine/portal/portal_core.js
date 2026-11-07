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
const VALID_PORTAL_TABS = ['chat', 'my-site', 'competitors', 'keywords', 'uvp', 'cms', 'settings'];

function getInitialTab() {
  const hash = (window.location.hash || '').replace(/^#/, '').trim();
  if (VALID_PORTAL_TABS.includes(hash)) {
    return hash;
  }
  const savedTab = localStorage.getItem('portal_active_tab');
  if (VALID_PORTAL_TABS.includes(savedTab)) {
    return savedTab;
  }
  return 'chat';
}

function switchTab(tabId, updateHash = true) {
  if (!VALID_PORTAL_TABS.includes(tabId)) {
    tabId = 'chat';
  }
  state.currentTab = tabId;
  try {
    localStorage.setItem('portal_active_tab', tabId);
  } catch (e) {}

  if (updateHash && window.location.hash !== `#${tabId}`) {
    history.replaceState(null, '', `#${tabId}`);
  }

  document.querySelectorAll('.nav-item').forEach(btn => {
    btn.classList.toggle('active', btn.getAttribute('data-tab') === tabId);
  });
  document.querySelectorAll('.tab-pane').forEach(pane => {
    pane.classList.toggle('active', pane.id === `tab-${tabId}`);
  });

  if (typeof closeMobileSidebar === 'function') {
    closeMobileSidebar();
  }

  // Nếu chuyển sang bất kỳ tab nào mà đã load xong nhưng không có dự án nào
  if (Array.isArray(state.projects) && state.projects.length === 0 && (!state.activeProjectId || !state.activeProject) && tabId !== 'settings') {
    if (typeof renderEmptyState === 'function') renderEmptyState();
  }

  // Tự động tải dữ liệu cấu hình khi bấm vào tab Cài đặt
  if (tabId === 'settings') {
    if (typeof loadSettings === 'function') loadSettings();
  }
  if (tabId === 'chat') {
    if (typeof initChatAttachment === 'function') initChatAttachment();
    if (typeof initPromptExpanderButton === 'function') initPromptExpanderButton();
  }

  // Tự động render tab Báo Cáo Web Mục Tiêu khi bấm vào tab này
  if (tabId === 'my-site' && state.activeProjectId) {
    const container = document.getElementById('mySiteContent');
    if (container && (!container.children || container.children.length === 0 || container.innerHTML.trim() === '')) {
      if (state.activeProject) {
        let bp = state.activeProject.brand_profile || {};
        if (typeof bp === 'string') { try { bp = JSON.parse(bp); } catch (e) { bp = {}; } }
        if (typeof renderMySiteTab === 'function') {
          renderMySiteTab(bp.my_site_audit, state.activeProject, bp.seed_analysis);
        }
      }
      if (typeof loadProjectIntelligence === 'function') {
        loadProjectIntelligence(state.activeProjectId);
      }
    }
  }
}

document.addEventListener('DOMContentLoaded', async () => {
  if (typeof window.loadPortalPartials === 'function') {
    await window.loadPortalPartials();
  }
  setupEventListeners();
  initApp();
});

// Lắng nghe sự kiện đổi hash (Back / Forward trình duyệt)
window.addEventListener('hashchange', () => {
  const hash = (window.location.hash || '').replace(/^#/, '').trim();
  if (hash && hash !== state.currentTab && VALID_PORTAL_TABS.includes(hash)) {
    switchTab(hash, false);
  }
});

async function initApp() {
  await loadProjects();
  // Khôi phục lại tab sau khi dữ liệu projects được tải xong
  const currentTab = getInitialTab();
  switchTab(currentTab, true);
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
      if (e.isComposing || e.keyCode === 229) return;
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

  // Send Button (Tự động chuyển đổi giữa Gửi tin nhắn và Dừng sinh câu trả lời)
  const sendBtn = document.getElementById('btnSend');
  if (sendBtn) {
    sendBtn.addEventListener('click', () => {
      if (state.isStreaming && typeof abortChatStream === 'function') {
        abortChatStream();
      } else {
        sendMessage();
      }
    });
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
function escapeHtml(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/'/g, '&#39;')
    .replace(/"/g, '&quot;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;');
}
window.escapeHtml = escapeHtml;
