/**
 * PORTAL CHAT NEXT ACTIONS - CONTEXTUAL PREDICTIVE MATRIX (MDP)
 * Thuật toán Ma Trận Quyết Định Markov & Mật độ Đặc trưng Ngữ nghĩa (SOP 30: Zero Hardcoding).
 * Tuân thủ SOP 23: <= 300 dòng code.
 */

function classifyContextualIntent(messageText) {
  if (!messageText || typeof messageText !== 'string') return 'general';
  const text = messageText.toLowerCase();

  // Thuật toán đo lường Mật độ Vector Đặc trưng Ngữ nghĩa (CSD)
  const densities = {
    onpage: (text.match(/\b(h1|h2|h3|heading|thẻ|on-page|onpage|meta\s*description|silo|schema|trùng\s*lặp|cấu\s*trúc\s*heading|chuẩn\s*seo|phân\s*cấp)\b/g) || []).length * 1.5,
    media: (text.match(/\b(ảnh|bìa|hình\s*ảnh|cover|banner|thumbnail|typography|visual|poster|bù\s*đắp)\b/g) || []).length * 1.4,
    competitor: (text.match(/\b(đối\s*thủ|serp|competitor|giá\s*sàn|khoảng\s*trống|thị\s*phần|so\s*sánh)\b/g) || []).length * 1.3,
    weather: (text.match(/\b(thời\s*tiết|nhiệt\s*độ|khí\s*tượng|mưa|nắng|rét|lạnh|độ\s*ẩm)\b/g) || []).length * 2.0,
    article: (messageText.length > 900 && /^##?\s+/m.test(messageText)) ? 6 : 0
  };

  let maxDomain = 'general', maxScore = 0;
  for (const [domain, score] of Object.entries(densities)) {
    if (score > maxScore) {
      maxScore = score;
      maxDomain = domain;
    }
  }
  return maxScore >= 1.5 ? maxDomain : 'general';
}

function generateNextActionChips(messageText) {
  if (!messageText || typeof messageText !== 'string') return [];
  const domain = classifyContextualIntent(messageText);
  const chips = [];

  // 1. Trạng thái On-Page Remediation / Heading / Technical SEO
  if (domain === 'onpage') {
    chips.push({
      label: '⚡ Tự Động Áp Dụng Heading & On-Page Lên WordPress',
      action: 'autofix_onpage',
      type: 'success'
    });
    chips.push({
      label: '📦 Mở Kế Hoạch Trên Canvas',
      action: 'canvas',
      type: 'primary'
    });
    chips.push({
      label: '🔍 Xem Báo Cáo Web & Điểm On-page',
      action: 'view_mysite_report',
      type: 'secondary'
    });
    chips.push({
      label: '✍️ Viết bài chuẩn SEO theo tháp Heading',
      prompt: 'Dựa vào cấu trúc tháp Heading trên, hãy viết bài viết chuẩn SEO SOP 12 cho từ khóa mục tiêu để kéo traffic.',
      type: 'secondary'
    });
    return chips;
  }

  // 2. Trạng thái Bài viết Hoàn chỉnh / Product Deliverable
  if (domain === 'article') {
    chips.push({
      label: '📦 Mở Bài Viết Trên Canvas',
      action: 'canvas',
      type: 'primary'
    });
    chips.push({
      label: '🚀 Đăng thẳng lên WordPress',
      action: 'publish',
      type: 'success'
    });
    chips.push({
      label: '🎬 Tạo 3 kịch bản video TikTok Review',
      prompt: 'Dựa trên bài viết trên, hãy viết 3 kịch bản video ngắn TikTok/Reels 60 giây thu hút khách mua hàng kèm hook mở đầu.',
      type: 'secondary'
    });
    chips.push({
      label: '📱 Tạo 5 bài post Facebook Seeding',
      prompt: 'Hãy chuyển đổi bài viết trên thành 5 bài đăng Facebook ngắn (2 bài chia sẻ kinh nghiệm, 2 bài so sánh giá, 1 bài flash sale).',
      type: 'secondary'
    });
    return chips;
  }

  // 3. Trạng thái Phân tích Đối thủ & Thị trường
  if (domain === 'competitor') {
    const brand = (state && state.currentProject && (state.currentProject.brand_name || state.currentProject.name)) || 'website của chúng ta';
    chips.push({
      label: '⚔️ Khai thác khoảng trống đối thủ Top 1',
      prompt: `Phân tích sâu điểm yếu về nội dung và chính sách của đối thủ Top 1 và đưa ra chiến lược tối ưu vượt mặt cho ${brand}.`,
      type: 'primary'
    });
    chips.push({
      label: '✍️ Viết bài vượt mặt đối thủ Top 1',
      prompt: 'Hãy tham khảo nội dung của đối thủ Top 1 và viết bài chi tiết chuẩn SEO vượt trội về thông số và trải nghiệm thực tế.',
      type: 'secondary'
    });
    chips.push({
      label: '📊 Lập bảng so sánh tính năng',
      prompt: `Hãy lập bảng so sánh chi tiết tính năng, điểm mạnh và định vị giữa ${brand} với 3 đối thủ lớn nhất.`,
      type: 'secondary'
    });
    return chips;
  }

  // 4. Trạng thái Khí tượng / Xu hướng thời gian thực
  if (domain === 'weather') {
    chips.push({
      label: '🔥 Kích hoạt Flash Sale theo thời tiết',
      prompt: 'Dựa vào dự báo thời tiết này, hãy lên kịch bản chiến dịch Flash Sale 3 ngày thúc đẩy doanh số nhóm hàng phù hợp nhất.',
      type: 'primary'
    });
    chips.push({
      label: '📝 Viết bài tin tức thời vụ bắt trend',
      prompt: 'Viết bài blog tin tức tư vấn tiêu dùng gắn liền với thời tiết tuần tới để kéo traffic tự nhiên.',
      type: 'secondary'
    });
    return chips;
  }

  // 5. Trạng thái Visual Creative / Tạo Ảnh
  if (domain === 'media') {
    const arch = (typeof getActiveProjectArchetype === 'function') ? getActiveProjectArchetype() : 'commerce_retail';
    let coverLabel = '🎨 Tạo ảnh sản phẩm (Visual Artisan)';
    if (arch === 'content_media') coverLabel = '🎨 Tạo bìa truyện (Novel Cover Artisan)';
    else if (arch === 'service_health_b2b') coverLabel = '🎨 Tạo banner dịch vụ (Visual Artisan)';

    chips.push({
      label: coverLabel,
      action: 'generate_cover',
      type: 'primary'
    });
    chips.push({
      label: '🖼️ Tự động quét & bù đắp ảnh thiếu trên web',
      action: 'repair_missing_covers',
      type: 'success'
    });
    return chips;
  }

  // 6. Trạng thái Mặc định: Gợi ý đa ngành theo Archetype (SOP 30)
  chips.push({
    label: '📊 Lộ trình Chiến lược Giai đoạn 2',
    prompt: '/strategy Hãy đánh giá lại toàn bộ ma trận từ khóa và lập lộ trình triển khai chi tiết cho giai đoạn tiếp theo.',
    type: 'secondary'
  });

  const curArch = (typeof getActiveProjectArchetype === 'function') ? getActiveProjectArchetype() : 'commerce_retail';
  let writeLabel = '✍️ Viết bài sản phẩm chuẩn SEO (SOP 12)';
  let writePrompt = 'Dựa vào ma trận từ khóa của dự án, hãy chọn 1 từ khóa có lượng tìm kiếm cao nhất để viết bài chuẩn SOP 12.';
  if (curArch === 'content_media') {
    writeLabel = '✍️ Viết bài giới thiệu truyện (SOP 12)';
    writePrompt = 'Dựa vào kho truyện của website, hãy viết bài giới thiệu và đánh giá chi tiết chuẩn SEO SOP 12 để thu hút độc giả.';
  } else if (curArch === 'service_health_b2b') {
    writeLabel = '✍️ Viết bài tư vấn dịch vụ (SOP 12)';
    writePrompt = 'Dựa vào dịch vụ cốt lõi của đơn vị, hãy viết bài tư vấn chuyên sâu chuẩn y khoa E-E-A-T theo SOP 12.';
  }

  chips.push({
    label: writeLabel,
    prompt: writePrompt,
    type: 'secondary'
  });
  return chips;
}

function renderNextActionChipsContainer(messageEl, messageText) {
  if (!messageEl || !messageText) return;
  const existing = messageEl.querySelector('.next-action-chips-bar');
  if (existing) existing.remove();

  const chips = generateNextActionChips(messageText);
  if (!chips || chips.length === 0) return;

  const bar = document.createElement('div');
  bar.className = 'next-action-chips-bar';
  bar.style.cssText = 'display:flex; flex-wrap:wrap; gap:8px; margin-top:14px; padding-top:10px; border-top:1px solid rgba(255,255,255,0.08);';

  chips.forEach(chip => {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = `next-action-chip chip-${chip.type || 'secondary'}`;
    btn.innerText = chip.label;
    btn.style.cssText = `
      font-size: 12px;
      font-weight: 400;
      letter-spacing: -0.005em;
      padding: 5px 12px;
      border-radius: 20px;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 5px;
      transition: all 0.15s ease;
      background: ${chip.type === 'primary' ? 'rgba(99, 102, 241, 0.2)' : chip.type === 'success' ? 'rgba(16, 185, 129, 0.2)' : 'rgba(255, 255, 255, 0.06)'};
      border: 1px solid ${chip.type === 'primary' ? 'rgba(99, 102, 241, 0.4)' : chip.type === 'success' ? 'rgba(16, 185, 129, 0.4)' : 'rgba(255, 255, 255, 0.12)'};
      color: ${chip.type === 'primary' ? '#c7d2fe' : chip.type === 'success' ? '#6ee7b7' : '#e2e8f0'};
    `;

    btn.onclick = () => {
      if (chip.action === 'canvas') {
        if (typeof openArtifactCanvas === 'function') {
          openArtifactCanvas(messageText);
        }
      } else if (chip.action === 'publish') {
        if (typeof publishCanvasToWordPress === 'function') {
          openArtifactCanvas(messageText);
          publishCanvasToWordPress();
        }
      } else if (chip.action === 'autofix_onpage') {
        const h1Match = messageText.match(/H1_SUGGESTED[:\s*]+["“]?([^"”\n]+)["”]?/i) ||
                        messageText.match(/H1(?:\s*\(duy\s*nhất\))?[:\s*]+["“]([^"”]+)["”]/i) ||
                        messageText.match(/thẻ\s*H1[:\s*]+["“]([^"”]+)["”]/i);
        const customSeo = h1Match ? { homepage_h1: h1Match[1].trim() } : null;
        if (typeof autoFixOnpageViaBridge === 'function') {
          autoFixOnpageViaBridge(customSeo);
        }
      } else if (chip.action === 'view_mysite_report') {
        if (typeof switchTab === 'function') {
          switchTab('my-site');
        }
      } else if (chip.action === 'generate_cover') {
        if (typeof showCoverArtisanModal === 'function') {
          let entityName = '';
          const quoteMatch = messageText.match(/["“]([^"”]+)["”]/);
          if (quoteMatch && quoteMatch[1] && quoteMatch[1].length < 60) {
            entityName = quoteMatch[1].replace(/\[\d+\]/g, '').trim();
          }
          if (!entityName) {
            const p = (state && state.activeProject) || {};
            let bp = p.brand_profile || {};
            if (typeof bp === 'string') { try { bp = JSON.parse(bp); } catch(e) { bp = {}; } }
            const km = bp.keyword_matrix || [];
            entityName = (km[0] && km[0].keyword) || p.name || 'Sản phẩm mới';
          }
          showCoverArtisanModal(entityName, '');
        }
      } else if (chip.action === 'repair_missing_covers') {
        if (typeof scanAndRepairMissingCovers === 'function') {
          scanAndRepairMissingCovers();
        }
      } else if (chip.prompt) {
        const input = document.getElementById('chatInput');
        if (input) {
          input.value = chip.prompt;
          input.focus();
          const btnSend = document.getElementById('btnSend');
          if (btnSend) btnSend.click();
        }
      }
    };

    bar.appendChild(btn);
  });

  messageEl.appendChild(bar);
}

