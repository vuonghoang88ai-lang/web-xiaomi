// ============================================================
// TAB 3: KEYWORD CATEGORY NLP EXTRACTION & DROPDOWN RENDER
// Strictly complies with SOP 23 (<= 300 lines)
// ============================================================

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

    if (/\b\d{2,3}\s*(?:inch|"|in)\b/i.test(tl) && (tl.includes('tivi') || tl.includes('tv'))) {
      return null;
    }

    return toTitleCase(t);
  };

  const candidateCategories = [];

  // 3. NGUỒN 1: Bóc tách ĐỘNG 100% từ Mô tả kinh doanh
  const rawDesc = project.business_description || '';
  if (rawDesc.trim()) {
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

  // 4. NGUỒN 2: Lấy từ cấu trúc danh mục bóc tách từ đối thủ mẫu thực tế
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

  // 7. THUẬT TOÁN KHỬ TRÙNG LẶP & ĐỒNG BỘ HÓA TÊN DANH MỤC
  const uniqueList = [];
  const normalizedMap = new Map();

  candidateCategories.forEach(cat => {
    const lower = cat.toLowerCase();
    if (!normalizedMap.has(lower)) {
      normalizedMap.set(lower, cat);
    }
  });

  const allNames = Array.from(normalizedMap.values());
  allNames.forEach(cat => {
    const lower = cat.toLowerCase();
    const hasMoreSpecific = allNames.some(other => {
      const otherLower = other.toLowerCase();
      return otherLower !== lower && otherLower.includes(lower) && (otherLower.length - lower.length <= 15);
    });

    if (!hasMoreSpecific && !uniqueList.includes(cat)) {
      uniqueList.push(cat);
    }
  });

  // 8. RENDER DROPDOWN
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
window.updateKeywordCategoryDropdown = updateKeywordCategoryDropdown;
