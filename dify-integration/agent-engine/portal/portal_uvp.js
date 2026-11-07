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

