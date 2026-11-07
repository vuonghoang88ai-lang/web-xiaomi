// Universal Marketing AI Portal v3.0 - Modular Component Partial Loader (SOP 23)
(function () {
  async function loadPortalPartials() {
    try {
      const [viewsRes, settingsRes, modalsRes] = await Promise.all([
        fetch('/portal/portal_tab_views.html'),
        fetch('/portal/portal_tab_settings.html'),
        fetch('/portal/portal_modals.html')
      ]);

      const [viewsHtml, settingsHtml, modalsHtml] = await Promise.all([
        viewsRes.ok ? viewsRes.text() : Promise.resolve(''),
        settingsRes.ok ? settingsRes.text() : Promise.resolve(''),
        modalsRes.ok ? modalsRes.text() : Promise.resolve('')
      ]);

      const viewsEl = document.getElementById('portalViewsContainer');
      const settingsEl = document.getElementById('tab-settings');
      const modalsEl = document.getElementById('portalModalsContainer');

      if (viewsEl && viewsHtml) viewsEl.innerHTML = viewsHtml;
      if (settingsEl && settingsHtml) settingsEl.innerHTML = settingsHtml;
      if (modalsEl && modalsHtml) modalsEl.innerHTML = modalsHtml;

      if (typeof window.applyTranslations === 'function') {
        window.applyTranslations();
      }
    } catch (err) {
      console.error('[Portal Partials] Lỗi tải component templates:', err);
    }
  }

  window.loadPortalPartials = loadPortalPartials;
})();

