/**
 * portal_competitors_seed.js
 * Renders the Seed Competitor Reverse-Engineering Analysis Card (4-tier keywords, EEAT, CRO, PAA, Head-to-Head)
 * Compliant with SOP 23 (<= 300 lines)
 */

function renderSeedCompetitorCard(seedAnalysis, project, currentSeedUrl) {
  let seedCardHtml = `
    <div class="card" style="background: rgba(30, 41, 59, 0.7); border: 1px solid rgba(99, 102, 241, 0.3); padding: 18px 20px; border-radius: 10px;">
      <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; margin-bottom: 14px;">
        <div style="display: flex; align-items: center; gap: 8px;">
          <span style="font-size: 20px;">🎯</span>
          <h4 style="margin: 0; font-size: 15px; color: #e2e8f0;">Đột Kích & Giải Mã Đối Thủ Mẫu (Reverse-Engineering)</h4>
        </div>
        <span style="font-size: 11.5px; color: #a5b4fc; background: rgba(99, 102, 241, 0.15); padding: 3px 8px; border-radius: 4px; border: 1px solid rgba(99, 102, 241, 0.3);">
          Mô phỏng tư duy Đội ngũ SEO thực chiến
        </span>
      </div>

      <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 14px;">
        <input type="text" inputmode="url" id="inputSeedCompetitorUrl" class="form-input" placeholder="Nhập domain hoặc URL đối thủ mẫu (ví dụ: mihanoi.vn)..." value="${currentSeedUrl}" style="flex: 1; min-width: 260px; font-size: 13px; padding: 8px 12px; background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(99, 102, 241, 0.4); border-radius: 6px; color: white;">
        <button type="button" class="btn-primary" id="btnAnalyzeSeed" onclick="analyzeSeedCompetitor()" style="font-size: 12.5px; padding: 8px 16px; display: inline-flex; align-items: center; gap: 6px;">
          <span>🔍</span> Giải Mã & Quét Lại Đối Thủ
        </button>
      </div>
  `;

  if (!seedAnalysis || seedAnalysis.status !== 'success') {
    return seedCardHtml + `</div>`;
  }

  const kws = seedAnalysis.extracted_keywords || [];
  const cats = seedAnalysis.category_structure || [];
  const strengths = seedAnalysis.strengths || [];
  const vulns = seedAnalysis.vulnerabilities || [];

  const pillars = seedAnalysis.pillars || {};
  const p1 = pillars.pillar1_architecture || {};
  const p2 = pillars.pillar2_content_eeat || {};
  const p3 = pillars.pillar3_technical_performance || {};
  const p5 = pillars.pillar5_commercial_cro || {};
  const p6 = pillars.pillar6_head_to_head || {};
  const p7 = pillars.pillar7_entity_social || {};
  const p8 = pillars.pillar8_authority_backlinks || {};
  const paa = seedAnalysis.paa_questions || [];

  const freshInfo = p2.content_freshness || {};
  const daysUpdate = freshInfo.days_since_update;
  const isStale = freshInfo.is_stale;
  const freshText = daysUpdate !== null && daysUpdate !== undefined 
    ? (isStale ? `⚠ Lão hóa (~${daysUpdate} ngày)` : `✓ Mới (~${daysUpdate} ngày)`) 
    : 'Chuẩn dateModified';

  const ageY = p8.age_years || seedAnalysis.onpage_audit?.domain_age_years || 0;
  const clusters = seedAnalysis.keyword_clusters || {};
  const extKws = seedAnalysis.extracted_keywords || [];
  const rawCats = seedAnalysis.category_structure || [];
  const bName = seedAnalysis.brand_name || (project && project.name) || 'thương hiệu';

  const heads = (clusters.head && clusters.head.length > 0)
    ? clusters.head
    : (rawCats.slice(0, 6).length > 0 ? rawCats.slice(0, 6) : extKws.filter(k => k.split(' ').length <= 2).slice(0, 5));

  const subcats = (clusters.subcategories && clusters.subcategories.length > 0)
    ? clusters.subcategories
    : (rawCats.slice(6, 14).length > 0 ? rawCats.slice(6, 14) : extKws.filter(k => k.split(' ').length === 3).slice(0, 6));

  const secs = (clusters.secondary && clusters.secondary.length > 0)
    ? clusters.secondary
    : (extKws.length > 0 ? extKws.slice(0, 8) : [`${bName} mới nhất`, `${bName} tuyển chọn`, `${bName} nổi bật`]);

  const longs = (clusters.longtail && clusters.longtail.length > 0)
    ? clusters.longtail
    : (extKws.filter(k => k.split(' ').length >= 3).length > 0 ? extKws.filter(k => k.split(' ').length >= 3).slice(0, 6) : [`bảng giá ${bName} mới nhất`, `đánh giá ${bName} có tốt không`, `hướng dẫn truy cập ${bName}`]);

  const metaKws = clusters.meta_keywords || [];
  const totalKws = seedAnalysis.total_keywords_count || (heads.length + subcats.length + secs.length + longs.length + metaKws.length);

  seedCardHtml += `
    <div style="background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(99, 102, 241, 0.2); border-radius: 8px; padding: 14px 16px; margin-bottom: 12px;">
      <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
        <span style="font-size: 14px; font-weight: 700; color: #f8fafc;">🌐 Đối thủ: <span style="color: #60a5fa;">${escapeHtml(seedAnalysis.domain)}</span></span>
        <div style="display: flex; gap: 6px; flex-wrap: wrap;">
          <span style="font-size: 11px; padding: 2px 7px; background: rgba(59, 130, 246, 0.2); border: 1px solid rgba(59, 130, 246, 0.4); border-radius: 4px; color: #93c5fd;">CMS: ${p3.cms_detected || 'Tự phát triển'}</span>
          <span style="font-size: 11px; padding: 2px 7px; background: rgba(168, 85, 247, 0.2); border: 1px solid rgba(168, 85, 247, 0.4); border-radius: 4px; color: #d8b4fe;">DA: ${p8.moz_da || 35}</span>
          <span style="font-size: 11px; padding: 2px 7px; background: rgba(16, 185, 129, 0.2); border: 1px solid rgba(16, 185, 129, 0.4); border-radius: 4px; color: #6ee7b7;">Tuổi Domain: ${ageY} năm</span>
          <span style="font-size: 11px; padding: 2px 7px; background: rgba(245, 158, 11, 0.2); border: 1px solid rgba(245, 158, 11, 0.4); border-radius: 4px; color: #fde68a;">TTFB: ${p3.ttfb_seconds || 0.45}s</span>
          <span style="font-size: 11px; padding: 2px 7px; background: rgba(139, 92, 246, 0.2); border: 1px solid rgba(139, 92, 246, 0.4); border-radius: 4px; color: #c4b5fd;">Cập nhật: ${freshText}</span>
        </div>
      </div>
    </div>

    <!-- HỆ THỐNG TỪ KHÓA ĐA TẦNG CỦA ĐỐI THỦ MẪU -->
    <div style="margin-top: 14px; margin-bottom: 14px; background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(99, 102, 241, 0.2); border-radius: 8px; padding: 14px 16px;">
      <div style="font-size: 13px; font-weight: 700; color: #e2e8f0; margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 6px;">
        <span style="display: flex; align-items: center; gap: 6px;">🎯 HỆ THỐNG TỪ KHÓA ĐỐI THỦ ĐANG TRIỂN KHAI SEO (${totalKws} từ):</span>
        <span style="font-size: 11px; font-weight: 500; color: #34d399; background: rgba(16, 185, 129, 0.15); padding: 2px 8px; border-radius: 4px; border: 1px solid rgba(16, 185, 129, 0.3);">Đầy đủ 4 Tầng: Cốt lõi • Danh mục • Chi tiết • Ý định</span>
      </div>

      <div style="margin-bottom: 10px;">
        <div style="font-size: 11.5px; font-weight: 600; color: #818cf8; margin-bottom: 5px;">📌 1. Từ khóa Cốt lõi (Head Keywords - ${heads.length}):</div>
        <div style="display: flex; flex-wrap: wrap; gap: 5px;">
          ${heads.map(kw => `<span style="font-size: 11px; background: rgba(99, 102, 241, 0.2); color: #c7d2fe; border: 1px solid rgba(99, 102, 241, 0.4); padding: 2px 8px; border-radius: 4px;">🔑 ${escapeHtml(kw)}</span>`).join('')}
        </div>
      </div>

      <div style="margin-bottom: 10px;">
        <div style="font-size: 11.5px; font-weight: 600; color: #34d399; margin-bottom: 5px;">📂 2. Landing Pages Danh mục (${subcats.length}):</div>
        <div style="display: flex; flex-wrap: wrap; gap: 5px;">
          ${subcats.map(kw => `<span style="font-size: 11px; background: rgba(16, 185, 129, 0.15); color: #6ee7b7; border: 1px solid rgba(16, 185, 129, 0.35); padding: 2px 8px; border-radius: 4px;">📂 ${escapeHtml(kw)}</span>`).join('')}
        </div>
      </div>

      <div style="margin-bottom: 10px;">
        <div style="font-size: 11.5px; font-weight: 600; color: #38bdf8; margin-bottom: 5px;">🏷️ 3. Thể loại / Model Chi tiết (${secs.length}):</div>
        <div style="display: flex; flex-wrap: wrap; gap: 5px;">
          ${secs.map(kw => `<span style="font-size: 11px; background: rgba(14, 165, 233, 0.15); color: #7dd3fc; border: 1px solid rgba(56, 189, 248, 0.35); padding: 2px 8px; border-radius: 4px;">🏷️ ${escapeHtml(kw)}</span>`).join('')}
        </div>
      </div>

      <div>
        <div style="font-size: 11.5px; font-weight: 600; color: #fbbf24; margin-bottom: 5px;">🎯 4. Từ khóa Đuôi Dài & Ý định (${longs.length}):</div>
        <div style="display: flex; flex-wrap: wrap; gap: 5px;">
          ${longs.map(kw => `<span style="font-size: 11px; background: rgba(245, 158, 11, 0.15); color: #fde68a; border: 1px solid rgba(251, 191, 36, 0.35); padding: 2px 8px; border-radius: 4px;">🎯 ${escapeHtml(kw)}</span>`).join('')}
        </div>
      </div>
    </div>

    <!-- CẤU TRÚC SILO ARCHITECTURE -->
    ${cats.length > 0 ? `
      <div style="margin-bottom: 12px; background: rgba(15, 23, 42, 0.5); padding: 10px 14px; border-radius: 6px; border: 1px solid rgba(99, 102, 241, 0.15);">
        <div style="font-size: 12px; font-weight: 600; color: #fbbf24; margin-bottom: 6px;">📁 CẤU TRÚC DANH MỤC (SILO ARCHITECTURE):</div>
        <div style="display: flex; flex-wrap: wrap; gap: 5px;">
          ${cats.map(c => `<span style="font-size: 11px; background: rgba(245, 158, 11, 0.1); color: #fde68a; border: 1px solid rgba(245, 158, 11, 0.25); padding: 2px 7px; border-radius: 4px;">${escapeHtml(c)}</span>`).join('')}
        </div>
      </div>
    ` : ''}

    <!-- GOOGLE PAA -->
    ${paa.length > 0 ? `
      <div style="margin-top: 12px; margin-bottom: 12px; background: rgba(15, 23, 42, 0.7); border: 1px solid rgba(56, 189, 248, 0.25); border-radius: 8px; padding: 12px 14px;">
        <div style="font-size: 12.5px; font-weight: 700; color: #e2e8f0; margin-bottom: 8px;">❓ CÂU HỎI THẮC MẮC (GOOGLE PEOPLE ALSO ASK - PAA):</div>
        <div style="display: flex; flex-direction: column; gap: 6px;">
          ${paa.map((q, idx) => `
            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; background: rgba(30, 41, 59, 0.4); padding: 6px 10px; border-radius: 5px;">
              <div style="flex: 1; font-size: 12px; color: #7dd3fc; font-weight: 500;">${idx + 1}. ${escapeHtml(q.question)}</div>
              <button type="button" class="btn-secondary" onclick="switchTab('chat'); document.getElementById('chatInput').value='Hãy lập dàn ý bài viết W4 giải đáp chi tiết câu hỏi PAA từ Google: &quot;${escapeHtml(q.question)}&quot; theo cấu trúc SOP 12 chuẩn E-E-A-T.'; sendMessage();" style="font-size: 10.5px; padding: 3px 8px; white-space: nowrap; flex-shrink: 0; background: rgba(99, 102, 241, 0.2); border: 1px solid rgba(99, 102, 241, 0.4); color: #c7d2fe; cursor: pointer; border-radius: 4px;">📝 Nạp vào dàn ý W4</button>
            </div>
          `).join('')}
        </div>
      </div>
    ` : ''}

    <!-- ĐIỂM MẠNH & LỖ HỔNG -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 10px; margin-top: 12px; border-top: 1px solid rgba(99, 102, 241, 0.15); padding-top: 10px;">
      <div style="background: rgba(16, 185, 129, 0.05); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 6px; padding: 8px 10px;">
        <div style="font-size: 11.5px; font-weight: 600; color: #34d399; margin-bottom: 4px;">✓ Điểm Mạnh SEO Đối Thủ:</div>
        <ul style="margin: 0; padding-left: 16px; font-size: 11.5px; color: var(--text-muted); line-height: 1.4;">${strengths.map(s => `<li>${s}</li>`).join('')}</ul>
      </div>
      <div style="background: rgba(239, 68, 68, 0.05); border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 6px; padding: 8px 10px;">
        <div style="font-size: 11.5px; font-weight: 600; color: #f87171; margin-bottom: 4px;">⚠ Lỗ Hổng On-page Cần Vượt Mặt:</div>
        <ul style="margin: 0; padding-left: 16px; font-size: 11.5px; color: var(--text-muted); line-height: 1.4;">${vulns.map(v => `<li>${v}</li>`).join('')}</ul>
      </div>
    </div>
  `;

  return seedCardHtml + `</div>`;
}
