/**
 * Portal Project Intelligence Loader
 * Loads and orchestrates project intelligence across tabs.
 * SOP 23 Decoupled Module (< 300 lines)
 */

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
      if (typeof updateHeaderSubscriptionBadge === 'function') {
        updateHeaderSubscriptionBadge(data.project);
      }
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
window.loadProjectIntelligence = loadProjectIntelligence;
