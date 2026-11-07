// ============================================================
// LIGHTWEIGHT MULTI-LANGUAGE (i18n) ENGINE
// Strictly complies with SOP 23 (<= 300 lines)
// ============================================================

(function() {
  const STORAGE_KEY = 'omni_portal_lang';
  const SUPPORTED_LANGS = ['vi', 'en'];
  const localesCache = {};
  let currentLang = localStorage.getItem(STORAGE_KEY) || 'vi';
  if (!SUPPORTED_LANGS.includes(currentLang)) currentLang = 'vi';

  function getNestedValue(obj, path) {
    if (!obj || !path) return null;
    return path.split('.').reduce((prev, curr) => (prev && prev[curr] !== undefined) ? prev[curr] : null, obj);
  }

  function t(path, params = {}) {
    const dict = localesCache[currentLang] || {};
    let val = getNestedValue(dict, path);
    if (!val && currentLang !== 'vi') val = getNestedValue(localesCache['vi'], path);
    if (!val) return path;

    if (typeof val === 'string' && Object.keys(params).length > 0) {
      Object.keys(params).forEach(k => {
        val = val.replace(new RegExp(`\\{${k}\\}`, 'g'), params[k]);
      });
    }
    return val;
  }
  window.t = t;

  async function loadLocale(lang) {
    if (localesCache[lang]) return localesCache[lang];
    try {
      const res = await fetch(`/portal/locales/${lang}.json?v=20261102`);
      if (!res.ok) throw new Error(`HTTP ${res.status}`);
      const data = await res.json();
      localesCache[lang] = data;
      return data;
    } catch (e) {
      console.warn(`[i18n] Failed to load locale '${lang}':`, e);
      return {};
    }
  }

  function applyTranslations() {
    document.documentElement.lang = currentLang;

    document.querySelectorAll('[data-i18n]').forEach(el => {
      const key = el.getAttribute('data-i18n');
      const translated = t(key);
      if (translated && translated !== key) {
        if (el.hasAttribute('data-i18n-html')) el.innerHTML = translated;
        else el.textContent = translated;
      }
    });

    document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
      const key = el.getAttribute('data-i18n-placeholder');
      const translated = t(key);
      if (translated && translated !== key) el.placeholder = translated;
    });

    document.querySelectorAll('[data-i18n-title]').forEach(el => {
      const key = el.getAttribute('data-i18n-title');
      const translated = t(key);
      if (translated && translated !== key) el.title = translated;
    });

    updateSwitcherUI();
    window.dispatchEvent(new CustomEvent('portal:lang_changed', { detail: { lang: currentLang } }));
  }
  window.applyTranslations = applyTranslations;

  function updateSwitcherUI() {
    document.querySelectorAll('.btn-lang-choice').forEach(btn => {
      const bLang = btn.getAttribute('data-lang');
      if (bLang === currentLang) btn.classList.add('active');
      else btn.classList.remove('active');
    });
  }

  async function setLanguage(lang) {
    if (!SUPPORTED_LANGS.includes(lang)) return;
    currentLang = lang;
    localStorage.setItem(STORAGE_KEY, lang);
    if (typeof state !== 'undefined') state.lang = lang;
    await loadLocale(lang);
    applyTranslations();
  }
  window.setLanguage = setLanguage;
  window.getCurrentLang = () => currentLang;

  function renderLanguageSwitcher() {
    const target = document.getElementById('langSwitcherContainer');
    if (!target) return;
    target.innerHTML = `
      <div class="lang-switcher-pill" title="Chuyển đổi ngôn ngữ / Switch Language">
        <span class="lang-globe-icon">🌐</span>
        <button type="button" class="btn-lang-choice ${currentLang === 'vi' ? 'active' : ''}" data-lang="vi" onclick="setLanguage('vi')">VI</button>
        <span class="lang-sep">|</span>
        <button type="button" class="btn-lang-choice ${currentLang === 'en' ? 'active' : ''}" data-lang="en" onclick="setLanguage('en')">EN</button>
      </div>
    `;
  }

  async function initI18n() {
    renderLanguageSwitcher();
    await loadLocale(currentLang);
    if (currentLang !== 'vi') await loadLocale('vi');
    if (typeof state !== 'undefined') state.lang = currentLang;
    applyTranslations();
  }
  window.initI18n = initI18n;

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initI18n);
  } else {
    initI18n();
  }
})();
