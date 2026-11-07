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


