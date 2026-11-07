/**
 * portal_my_site_suggestions.js
 * SOP 57: Bộ 4 thuật toán On-page & Kiến trúc Lai Hybrid AI (SOP 23 < 300 lines).
 */

function isSpecificationJunk(text) {
  if (!text || typeof text !== 'string') return true;
  const tl = text.trim().toLowerCase();
  const docPatterns = ['catalog', 'catalogue', 'tài liệu', 'bảng giá', 'báo giá', 'tải về', 'download', 'brochure', 'hướng dẫn', 'chính sách', 'quy định', 'sơ đồ', 'pdf'];
  if (docPatterns.some(dp => tl.includes(dp))) return true;
  if (/\b\d+\s*in\s*\d+\b|\d+\s*m[³3]|\b\d+\s*(?:w|kw|mah|kpa|rpm|hz)\b/i.test(tl)) return true;
  const digitsAndSyms = (text.match(/[0-9\/%³+*()[\]]/g) || []).length;
  return (text.length > 0 && (digitsAndSyms / text.length) > 0.22);
}

function rankCategoriesByAnchor(candidates, anchorText) {
  if (!anchorText || !candidates.length) return candidates;
  const anchorTokens = {};
  anchorText.toLowerCase().replace(/[^\w\sÀ-ỹ]/g, ' ').split(/\s+/).forEach(w => {
    if (w.length >= 2) anchorTokens[w] = (anchorTokens[w] || 0) + 1;
  });
  const seen = new Set(), scored = [];
  candidates.forEach(c => {
    const norm = c.trim(), lower = norm.toLowerCase();
    if (seen.has(lower)) return;
    seen.add(lower);
    const cTokens = lower.replace(/[^\w\sÀ-ỹ]/g, ' ').split(/\s+/).filter(w => w.length >= 2);
    let score = 0;
    cTokens.forEach(t => { if (anchorTokens[t]) score += anchorTokens[t] * 2; });
    if (anchorText.toLowerCase().includes(lower)) score += 6;
    scored.push({ cat: norm, score: score / Math.sqrt(Math.max(1, cTokens.length)) });
  });
  return scored.sort((a, b) => b.score - a.score).map(x => x.cat);
}

function resolveBrandEntity(p, bp, audit) {
  if (p && p.brand_name) return p.brand_name.trim();
  if (bp && bp.brand_name) return bp.brand_name.trim();
  const rawName = (p && p.name ? p.name : '').split('(')[0].trim();
  const isAdmin = /\b(?:test|demo|staging|dự\s*án|web|site)\b/i.test(rawName);

  const siteTitle = (audit && audit.title) || (bp && bp.my_site_audit && bp.my_site_audit.title) || '';
  if (siteTitle) {
    const chunks = siteTitle.split(/[|–—\-•]/).map(c => c.trim()).filter(Boolean);
    const stems = {};
    chunks.forEach(c => {
      const lead = (c.split(/\s+/)[0] || '').replace(/[^\wÀ-ỹ]/g, '');
      if (lead.length >= 3 && !/^(chuyên|tổng|kho|phân|phối|điện|tử|web|site|store|shop|bán)$/i.test(lead)) {
        stems[lead] = (stems[lead] || 0) + 1;
      }
    });
    const sortedStems = Object.keys(stems).sort((a, b) => stems[b] - stems[a]);
    if (sortedStems.length && (stems[sortedStems[0]] >= 2 || isAdmin)) return sortedStems[0];
  }

  let clean = rawName.replace(/\b(?:test|demo|staging|dự\s*án)\b/gi, '').trim().replace(/\.(com|vn|top|net|org|edu)$/i, '').trim();
  return clean ? (clean.charAt(0).toUpperCase() + clean.slice(1)) : 'Thương Hiệu';
}

function clampSeoText(s, min, max, pad, isSentence) {
  s = (s || '').replace(/\s+/g, ' ').trim();
  if (s.length > max) {
    s = s.slice(0, max - 1);
    const sp = s.lastIndexOf(' ');
    if (sp > min - 10) s = s.slice(0, sp);
    s = s.replace(/[,;:\-\s|]+$/, '');
    if (isSentence && !/[.!?]$/.test(s)) s += '.';
  }
  if (s.length < min && pad) {
    s = (s + ' ' + pad).trim();
    if (s.length > max) {
      s = s.slice(0, max - 1).replace(/\s+\S*$/, '').replace(/[,;:\-\s|]+$/, '');
      if (isSentence && !/[.!?]$/.test(s)) s += '.';
    }
  }
  return s;
}

function buildDiverseVariants(brand, cats, loc, archetype, isXiaomi) {
  cats = cats || [];
  const c1 = cats[0] || (isXiaomi ? 'Tivi Xiaomi' : 'Sản Phẩm');
  const c2 = cats[1] || (isXiaomi ? 'Tủ Lạnh Xiaomi' : 'Thiết Bị');
  const pair = `${c1} & ${c2.replace(new RegExp(brand, 'gi'), '').replace(/xiaomi/gi, '').trim() || c2}`;
  let rawTitles = [], rawDescs = [], rawH1s = [];

  if (archetype === 'content_media') {
    rawTitles = [
      `${brand} - Đọc Truyện Chữ Online Tuyển Chọn Hay Nhất`, `${brand} | Kho Truyện Full Miễn Phí, Cập Nhật Liên Tục`,
      `${brand} - Đọc Truyện Tiên Hiệp, Ngôn Tình Dịch Chuẩn`, `${brand} | Trang Đọc Truyện Chữ Tốc Độ Cao Mới Nhất`,
      `${brand} - Đọc Truyện Chữ Online Chuẩn Font Đẹp`, `${brand} | Đọc Truyện Online Không Giới Hạn Miễn Phí`
    ];
    rawDescs = [
      `Đọc truyện chữ online miễn phí tại ${brand}. Cập nhật liên tục các bộ tiên hiệp, kiếm hiệp, ngôn tình hot nhất, đọc truyện nhanh không quảng cáo rác.`,
      `Kho truyện chữ ${brand} với hàng ngàn đầu truyện hoàn thành full chương. Đọc truyện chữ online mượt mà trên mọi thiết bị, cập nhật chương mới mỗi ngày.`,
      `${brand} tuyển chọn truyện dịch và convert chất lượng cao. Đọc full bộ không giới hạn, giao diện tối ưu cho điện thoại, tốc độ tải trang cực nhanh.`,
      `Cập nhật chương mới nhanh nhất các tác phẩm hot tại ${brand}. Đọc truyện chữ online chuẩn font, lọc chương lỗi, trải nghiệm đọc trọn vẹn miễn phí.`
    ];
    rawH1s = [
      `${brand} - Nền Tảng Đọc Truyện Chữ Online Tuyển Chọn`, `${brand} - Kho Truyện Chữ Full Bộ Miễn Phí Mới Nhất`,
      `${brand} - Trang Đọc Truyện Tiên Hiệp & Ngôn Tình Dịch Chuẩn`
    ];
  } else if (archetype === 'service_health_b2b') {
    rawTitles = [
      `${brand} - Dịch Vụ ${c1} Chuyên Nghiệp Tại ${loc}`, `${brand} | Tư Vấn & Triển Khai ${c1} Uy Tín Tại ${loc}`,
      `${brand} - Bảng Giá Dịch Vụ ${c1} Chi Phí Minh Bạch`, `${brand} | Dịch Vụ ${pair} Trọn Gói, Tận Tâm`
    ];
    rawDescs = [
      `${brand} cung cấp dịch vụ ${c1} chuyên nghiệp, uy tín tại ${loc}. Đội ngũ tận tâm, quy trình chuẩn mực, cam kết chi phí minh bạch và bảo hành dài hạn.`,
      `Trung tâm tư vấn và cung cấp dịch vụ ${pair} tại ${loc}. Chi phí minh bạch, tư vấn chi tiết, hỗ trợ nhanh chóng 24/7 cho mọi khách hàng.`
    ];
    rawH1s = [`${brand} - Hệ Thống Dịch Vụ ${c1} Chuyên Nghiệp Chuẩn Uy Tín`, `${brand} - Dịch Vụ ${pair} Trọn Gói Uy Tín Tại ${loc}`];
  } else {
    rawTitles = [
      `${brand} - Tổng Kho Phân Phối ${c1} Giá Tận Gốc`, `${brand} | ${c1} Chính Hãng, Bảo Hành 24 Tháng Tại ${loc}`,
      `${brand} - Đại Lý Phân Phối ${c1} Giá Rẻ Tại Kho`, `${brand} | ${pair} Chính Hãng, Trả Góp 0%`,
      `${brand} - Chuyên ${c1} Chính Hãng Uy Tín Tại ${loc}`, `${brand} | Bảng Giá ${c1} Mới Nhất, Giao 2H ${loc}`,
      `${brand} - Tổng Kho ${pair} Giá Sỉ Chiết Khấu Cao`, `${brand} | Mua ${c1} Giá Gốc, Lỗi 1 Đổi 1 30 Ngày`,
      `${brand} - Phân Phối ${c1} Hàng Mới Nguyên Seal 100%`, `${brand} | Đại Lý ${c1} Chính Hãng, Hóa Đơn VAT Đầy Đủ`,
      `${brand} - Tổng Kho Điện Máy ${c1} Miễn Phí Lắp Đặt`, `${brand} | ${c1} Giá Rẻ Hơn Đối Thủ, Giao Nhanh ${loc}`
    ];
    rawDescs = [
      `${brand} - Tổng kho phân phối ${pair} chính hãng. Cam kết giá rẻ hơn đối thủ, bảo hành 24 tháng, giao hỏa tốc 2h tại ${loc}.`,
      `Mua ${c1} chính hãng 100% tại ${brand}. Bảo hành 24 tháng chính hãng, lỗi 1 đổi 1 trong 30 ngày, miễn phí vận chuyển tại ${loc}.`,
      `Đại lý phân phối ${pair} chính hãng tại ${loc}. Hỗ trợ trả góp 0%, giao hàng hỏa tốc trong 2 giờ, kiểm tra hàng trước khi thanh toán.`,
      `Bảng giá ${c1} mới nhất tại kho ${brand}. Cam kết hàng mới nguyên seal 100%, chiết khấu cao cho đại lý, giao ngay tại ${loc}.`,
      `${brand} cung cấp ${pair} cam kết giá sỉ tại kho. Đầy đủ hóa đơn VAT, hỗ trợ lắp đặt tận nơi, kiểm tra hàng trước khi nhận.`,
      `Tổng kho phân phối ${c1} chính hãng giá gốc tại ${loc}. Cam kết hàng mới 100%, bảo hành dài hạn, tư vấn kỹ thuật chuyên sâu 24/7.`,
      `Mua ${pair} giá sỉ tại tổng kho ${brand}. Miễn phí vận chuyển nội thành ${loc}, hỗ trợ trả góp lãi suất 0%, nhận hàng thanh toán.`
    ];
    rawH1s = [
      `${brand} - Tổng Kho Phân Phối ${c1} Chính Hãng Tại ${loc}`, `${brand} - Hệ Thống Bán Lẻ ${pair} Giá Sỉ Tại Kho`,
      `${brand} - Đại Lý Phân Phối ${c1} Chính Hãng Bảo Hành 24 Tháng`, `${brand} - Phân Phối ${c1} Giá Gốc, Hỗ Trợ Trả Góp 0%`
    ];
  }

  const titles = rawTitles.map(t => clampSeoText(t, 40, 65, 'Chính Hãng', false)).filter(t => t.length >= 40 && t.length <= 65);
  const descs = rawDescs.map(d => clampSeoText(d, 120, 155, 'Cam kết uy tín, hỗ trợ 24/7.', true)).filter(d => d.length >= 120 && d.length <= 155);
  const h1s = rawH1s.map(h => clampSeoText(h, 20, 80, '', false)).filter(h => h.length >= 20 && h.length <= 80);
  return { titles, descs, h1s };
}

function generateOnpageSuggestions(p, bp, audit) {
  if (!p) return { title: '', meta_description: '', h1: '' };
  bp = bp || {}; audit = audit || {};

  const brand = resolveBrandEntity(p, bp, audit);
  const loc = (p.target_location || 'Toàn quốc').trim();
  const url = (p.target_website_url || '').toLowerCase();
  const bizDesc = (p.business_description || '').toLowerCase();

  const rawList = [];
  const myNav = audit.key_services_or_nav || [];
  if (Array.isArray(myNav)) rawList.push(...myNav);
  const seedAudit = bp.seed_analysis || {};
  const seedCats = seedAudit.category_structure || seedAudit.categories || [];
  if (Array.isArray(seedCats)) rawList.push(...seedCats);

  if (Array.isArray(state.competitors) && state.competitors.length) {
    state.competitors.forEach(c => {
      const ss = c.scraped_structure || {};
      const cCats = ss.nav_categories || ss.categories || ss.key_services_or_nav || [];
      if (Array.isArray(cCats)) rawList.push(...cCats);
    });
  }

  const stopWords = ['home', 'trang chủ', 'giỏ hàng', 'tài khoản', 'liên hệ', 'tin tức', 'tra cứu', 'bảo hành', 'hotline', 'cart', '0', 'mua hàng', 'trải nghiệm'];
  const nonJunkCats = [];
  rawList.forEach(c => {
    if (!c || typeof c !== 'string') return;
    let text = c.replace(/^[a-z_]+(?=[A-ZÀ-Ỹ])/g, '').replace(/[^\w\sÀ-ỹ]/gi, ' ').replace(/\s+/g, ' ').trim();
    if (text.length >= 3 && text.length <= 30 && !stopWords.some(sw => text.toLowerCase().startsWith(sw)) && !isSpecificationJunk(text)) {
      nonJunkCats.push(text);
    }
  });

  const anchorCorpus = `${bizDesc} ${brand} ${(audit.key_services_or_nav || []).join(' ')}`;
  const rankedCats = rankCategoriesByAnchor(nonJunkCats, anchorCorpus);

  const uniqueRanked = [];
  rankedCats.forEach(c => {
    const cl = c.toLowerCase();
    const isSubsumed = uniqueRanked.some(ex => ex.toLowerCase().includes(cl) || cl.includes(ex.toLowerCase()));
    if (!isSubsumed) uniqueRanked.push(c);
  });

  const corpus = `${bizDesc} ${brand} ${url} ${uniqueRanked.join(' ')}`.toLowerCase();
  const isNovel = /truyen|truyện|tiểu thuyết|novel|manga|doc online|đọc online|xianxia|chuong|chương/.test(corpus);
  const isService = /phòng khám|nha khoa|bác sĩ|chữa|thẩm mỹ|tư vấn|luật|đào tạo|dịch vụ|clinic|agency/.test(corpus);
  const archetype = isNovel ? 'content_media' : (isService ? 'service_health_b2b' : 'commerce_retail');
  const isXiaomi = /xiaomi|mi|tongkhomi/.test(corpus);

  const variants = buildDiverseVariants(brand, uniqueRanked, loc, archetype, isXiaomi);

  return {
    title: variants.titles[0] || `${brand} - Phân Phối Chính Hãng Giá Sỉ`,
    meta_description: variants.descs[0] || `${brand} chuyên phân phối chính hãng uy tín tại ${loc}. Cam kết giá sỉ tại kho, bảo hành chính hãng, hỗ trợ giao hàng hỏa tốc.`,
    h1: variants.h1s[0] || `${brand} - Phân Phối Sản Phẩm Chính Hãng`,
    title_variants: variants.titles,
    meta_description_variants: variants.descs,
    h1_variants: variants.h1s,
    archetype: archetype,
    categories: uniqueRanked,
    brand: brand
  };
}

window.cycleQuickOptimizeVariant = function() {
  const list = window._currentModalVariants || [];
  if (!list.length) return;
  window._currentModalIndex = (window._currentModalIndex + 1) % list.length;
  const nextVal = list[window._currentModalIndex];
  const inputEl = document.getElementById('quickOptInput');
  if (inputEl) {
    inputEl.value = nextVal;
    if (typeof window.updateQuickOptCounter === 'function') window.updateQuickOptCounter();
  }
  const btn = document.getElementById('btnCycleQuickOpt');
  if (btn) btn.innerHTML = `💡 Đổi Gợi Ý Tiếp Theo (${window._currentModalIndex + 1}/${list.length})`;
};

window.requestCreativeAiOnpageSuggestion = async function() {
  const p = state.activeProject;
  if (!p) { showToast('Vui lòng chọn dự án trước.', 'warning'); return; }
  const btn = document.getElementById('btnCreativeAiOpt');
  const type = window._currentModalType || 'title';
  const fieldKey = (type === 'title') ? 'title' : ((type === 'meta_description') ? 'meta_description' : 'h1');
  
  if (btn) { btn.disabled = true; btn.innerHTML = '⏳ AI Đang Sáng Tạo...'; }
  try {
    const brand = window._currentModalBrand || p.brand_name || p.name || 'Thương Hiệu';
    const loc = p.target_location || 'Toàn quốc';
    const cats = window._currentModalCats || [];
    const curVal = document.getElementById('quickOptInput') ? document.getElementById('quickOptInput').value : '';
    const res = await fetch(`/api/v1/portal/projects/${p.id}/onpage-ai-suggest`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        field_type: fieldKey,
        brand_name: brand,
        target_location: loc,
        core_categories: cats,
        current_value: curVal,
        existing_variants: window._currentModalVariants || [],
        archetype: window._currentModalArchetype || 'commerce_retail'
      })
    });
    const data = await res.json();
    if (data.status === 'success' && data.suggestion) {
      if (!window._currentModalVariants) window._currentModalVariants = [];
      window._currentModalVariants.push(data.suggestion);
      window._currentModalIndex = window._currentModalVariants.length - 1;
      const inputEl = document.getElementById('quickOptInput');
      if (inputEl) {
        inputEl.value = data.suggestion;
        inputEl.style.transition = 'all 0.3s ease';
        inputEl.style.borderColor = '#10b981';
        inputEl.style.boxShadow = '0 0 14px rgba(16, 185, 129, 0.4)';
        setTimeout(() => {
          inputEl.style.borderColor = 'var(--border-subtle, rgba(255,255,255,0.15))';
          inputEl.style.boxShadow = 'none';
        }, 800);
        if (typeof window.updateQuickOptCounter === 'function') window.updateQuickOptCounter();
      }
      const cycleBtn = document.getElementById('btnCycleQuickOpt');
      if (cycleBtn) {
        cycleBtn.innerHTML = `💡 Đổi Gợi Ý Tiếp Theo (${window._currentModalVariants.length}/${window._currentModalVariants.length})`;
      }
      showToast('✨ AI đã sáng tạo thêm 1 góc nhìn mới!', 'success');
    } else {
      showToast('Chưa thể kết nối AI, vui lòng thử lại.', 'warning');
    }
  } catch (err) {
    showToast('Lỗi khi gọi AI: ' + err.message, 'danger');
  } finally {
    if (btn) { btn.disabled = false; btn.innerHTML = '✨ AI Sáng Tạo Mới'; }
  }
};

window.generateOnpageSuggestions = generateOnpageSuggestions;
window.isSpecificationJunk = isSpecificationJunk;
window.rankCategoriesByAnchor = rankCategoriesByAnchor;
window.resolveBrandEntity = resolveBrandEntity;
