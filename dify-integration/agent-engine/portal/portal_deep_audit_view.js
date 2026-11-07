/**
 * Portal Deep Audit Modal Views
 * SOP 23 Decoupled Module (< 300 lines)
 */

function renderHeadToHeadComparison(myAudit, audit, domain, cms, ageY, words, schemas) {
  if (!myAudit || !myAudit.title) return '';

  const myCms = myAudit.cms_detected || myAudit.onpage_audit?.cms_platform || 'Custom';
  const myAge = myAudit.domain_age_years || myAudit.onpage_audit?.domain_age_years || 0;
  const myWords = myAudit.avg_word_count || myAudit.onpage_audit?.avg_word_count || 0;
  const mySchemas = myAudit.schemas_found || myAudit.onpage_audit?.schemas || [];
  const myTitleLen = (myAudit.title || '').length;
  const compTitleLen = (audit.title || '').length;

  return `
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
window.renderHeadToHeadComparison = renderHeadToHeadComparison;

function renderDeepAuditModalContent(audit, domain, headToHeadCompHtml, cms, ageY, mozDa, ttfb, words, altPct, schemas, fresh, channels, citations, kws, paa) {
  const freshText = fresh.days_since_update !== null && fresh.days_since_update !== undefined
    ? (fresh.is_stale ? `⚠ Lão hóa (~${fresh.days_since_update} ngày)` : `✓ Mới (~${fresh.days_update} ngày)`)
    : 'dateModified chuẩn';

  const clusters = audit.keyword_clusters || {};
  const heads = clusters.head || [];
  const subcats = clusters.subcategories || [];
  const secs = clusters.secondary || [];
  const longs = clusters.longtail || [];
  const totalKws = audit.total_keywords_count || (heads.length + subcats.length + secs.length + longs.length + (kws ? kws.length : 0));

  return `
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

    <div style="background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border); border-radius: 8px; padding: 14px; margin-bottom: 14px;">
      <div style="font-size: 12.5px; font-weight: 700; color: #cbd5e1; margin-bottom: 8px;">📄 BÓC TÁCH THẺ ON-PAGE NGUYÊN BẢN:</div>
      <div style="font-size: 12.5px; color: var(--text-muted); line-height: 1.6;">
        <div><strong>Title:</strong> <span style="color: #f8fafc;">${escapeHtml(audit.title || 'N/A')}</span> <span style="font-size: 11px; color: #93c5fd;">(${escapeHtml(audit.onpage_audit?.title_evaluation || '')})</span></div>
        <div style="margin-top: 4px;"><strong>Meta Description:</strong> <span style="color: #cbd5e1;">${escapeHtml(audit.meta_description || 'Không có thẻ meta description')}</span></div>
        <div style="margin-top: 4px;"><strong>Heading H1:</strong> <span style="color: #cbd5e1;">${(audit.h1_tags && audit.h1_tags.length > 0) ? escapeHtml(audit.h1_tags.join(' | ')) : 'Không gắn thẻ H1'}</span> <span style="font-size: 11px; color: ${audit.onpage_audit?.h1_count === 1 ? '#34d399' : '#f87171'};">(${escapeHtml(audit.onpage_audit?.h1_evaluation || '')})</span></div>
        ${audit.canonical ? `<div style="margin-top: 4px;"><strong>Canonical:</strong> <span style="color: #93c5fd;">${escapeHtml(audit.canonical)}</span></div>` : ''}
      </div>
    </div>

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

    ${totalKws > 0 || paa.length > 0 ? `
      <div style="background: rgba(15, 23, 42, 0.6); border: 1px solid var(--border); border-radius: 8px; padding: 14px;">
        <div style="font-size: 13px; font-weight: 700; color: #cbd5e1; margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between;">
          <span>🎯 HỆ THỐNG TỪ KHÓA ĐỐI THỦ ĐANG SEO (${totalKws} từ khóa):</span>
          <span style="font-size: 11px; color: #34d399; background: rgba(16, 185, 129, 0.15); padding: 2px 7px; border-radius: 4px;">4 Tầng chiến lược</span>
        </div>
        ${heads.length > 0 ? `
          <div style="margin-bottom: 10px;">
            <div style="font-size: 11.5px; font-weight: 600; color: #818cf8; margin-bottom: 4px;">📌 1. Cốt lõi (Head - ${heads.length}):</div>
            <div style="display: flex; flex-wrap: wrap; gap: 5px;">
              ${heads.map(kw => `<span style="font-size: 11px; background: rgba(99, 102, 241, 0.2); color: #c7d2fe; border: 1px solid rgba(99, 102, 241, 0.35); padding: 2px 8px; border-radius: 4px;">🔑 ${escapeHtml(kw)}</span>`).join('')}
            </div>
          </div>
        ` : ''}
        ${subcats.length > 0 ? `
          <div style="margin-bottom: 10px;">
            <div style="font-size: 11.5px; font-weight: 600; color: #34d399; margin-bottom: 4px;">📂 2. Landing Pages ngách (${subcats.length}):</div>
            <div style="display: flex; flex-wrap: wrap; gap: 5px;">
              ${subcats.map(kw => `<span style="font-size: 11px; background: rgba(16, 185, 129, 0.15); color: #6ee7b7; border: 1px solid rgba(16, 185, 129, 0.3); padding: 2px 8px; border-radius: 4px;">📂 ${escapeHtml(kw)}</span>`).join('')}
            </div>
          </div>
        ` : ''}
        ${secs.length > 0 ? `
          <div style="margin-bottom: 10px;">
            <div style="font-size: 11.5px; font-weight: 600; color: #38bdf8; margin-bottom: 4px;">🏷️ 3. Dòng Model (${secs.length}):</div>
            <div style="display: flex; flex-wrap: wrap; gap: 5px;">
              ${secs.map(kw => `<span style="font-size: 11px; background: rgba(14, 165, 233, 0.15); color: #7dd3fc; border: 1px solid rgba(56, 189, 248, 0.3); padding: 2px 8px; border-radius: 4px;">🏷️ ${escapeHtml(kw)}</span>`).join('')}
            </div>
          </div>
        ` : ''}
        ${longs.length > 0 ? `
          <div style="margin-bottom: 10px;">
            <div style="font-size: 11.5px; font-weight: 600; color: #fbbf24; margin-bottom: 4px;">🎯 4. Đuôi dài (${longs.length}):</div>
            <div style="display: flex; flex-wrap: wrap; gap: 5px;">
              ${longs.map(kw => `<span style="font-size: 11px; background: rgba(245, 158, 11, 0.15); color: #fde68a; border: 1px solid rgba(251, 191, 36, 0.3); padding: 2px 8px; border-radius: 4px;">🎯 ${escapeHtml(kw)}</span>`).join('')}
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
    ` : ''}
  `;
}
window.renderDeepAuditModalContent = renderDeepAuditModalContent;
