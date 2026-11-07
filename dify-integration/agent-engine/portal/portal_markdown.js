// ============================================================
// PORTAL MARKDOWN PARSER MODULE (SOP 23 compliant <= 300 lines)
// Cung cấp hàm parseMarkdown(text) chuẩn hóa cho toàn bộ Portal:
// Hỗ trợ Markdown Tables, Code blocks, Headers, Lists và Links an toàn
// ============================================================

function parseMarkdown(text) {
  if (!text) return '';

  // 0. Làm sạch token rò rỉ từ mô hình (DeepSeek / Dify DSML tokens) & chuẩn hóa ngắt dòng
  let normalized = String(text);
  if (normalized.includes('<｜') || normalized.includes('<|') || normalized.includes('<function_call')) {
    normalized = normalized.replace(/<[｜|]\s*DSML[\s\S]*?(?=(?:\n# |\n:::boardroom|\n\*\*\[TIER|$))/gi, '');
    normalized = normalized.replace(/<function_calls?>[\s\S]*?(<\/function_calls?>|$)/gi, '');
    const m = normalized.match(/(\n|^)(# [^\n]+|:::boardroom|\*\*\[TIER)/);
    if (m && m.index > 0) {
      const preamble = normalized.slice(0, m.index).trim().toLowerCase();
      if (['tôi sẽ', 'tôi cần', 'phân tích', 'function_call', 'dsml'].some(k => preamble.includes(k))) {
        normalized = normalized.slice(m.index).trim();
      }
    }
  }
  normalized = normalized
    .replace(/<[｜|]\s*DSML\s*[｜|][^>\n]*/gi, '')
    .replace(/<function_calls?>[\s\S]*?(<\/function_calls?>|$)/gi, '')
    .replace(/\[\d+\s+items\s+compressed\s+to\s+\d+\.\s+Retrieve\s+more:[^\]]+\]/gi, '')
    .replace(/([^\n])\s+(\*\*\d+\.\s+)/g, '$1\n\n$2')
    .replace(/([^\n])\s+(-\s+\*\*)/g, '$1\n$2')
    .replace(/([^\n])\s+(\*\*[A-ZÀ-Ỹ\s0-9_:\-]{4,}\*\*)/g, '$1\n\n$2')
    .replace(/([^\n])\s+(\d+\.\s+\*\*)/g, '$1\n$2');

  // 0.1 Tự động bù thẻ đóng ảo cho fenced code blocks đang stream dở dang để chống layout shift
  const fenceCount = (normalized.match(/```/g) || []).length;
  if (fenceCount % 2 !== 0) {
    normalized += '\n```';
  }

  // 1. Lưu trữ và bảo vệ các khối fenced code blocks (```...```)
  const codeBlocks = [];
  let processed = normalized.replace(/```([a-z0-9_-]*)\n([\s\S]*?)```/gi, (match, lang, code) => {
    const escapedCode = code
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;');
    const idx = codeBlocks.length;
    codeBlocks.push(`<pre><code class="language-${lang || 'text'}">${escapedCode}</code></pre>`);
    return `%%CODEBLOCK_${idx}%%`;
  });

  // 2. Escape HTML cơ bản trên phần text còn lại
  processed = processed
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;');

  // 3. Phân tích các khối bảng Markdown Table (| ... |)
  processed = processed.replace(/((\r?\n|^)\|[^\n]+\|(\r?\n\|[-:\s|]+\|)(\r?\n\|[^\n]+\|)+)/g, (tableText) => {
    const lines = tableText.trim().split('\n').map(l => l.trim()).filter(Boolean);
    if (lines.length < 2) return tableText;

    const parseRow = (line) => line.replace(/^\|/, '').replace(/\|$/, '').split('|').map(c => c.trim());
    const headerCells = parseRow(lines[0]);
    // Dòng 1 là separator (|---|---|), bỏ qua
    const dataLines = lines.slice(2);

    const thead = `<thead><tr>${headerCells.map(c => `<th>${c}</th>`).join('')}</tr></thead>`;
    const tbody = `<tbody>${dataLines.map(rowLine => {
      const cells = parseRow(rowLine);
      return `<tr>${cells.map(c => `<td>${c}</td>`).join('')}</tr>`;
    }).join('')}</tbody>`;

    return `\n<table class="bubble-table">${thead}${tbody}</table>\n`;
  });

  // 4. Định dạng khối danh sách (Unordered Lists) theo từng cụm riêng biệt
  processed = processed.replace(/((?:^|\n)[ \t]*[-*] [^\n]+(?:\n[ \t]*[-*] [^\n]+)*)/g, (listBlock) => {
    const items = listBlock.trim().split('\n').map(line => {
      const clean = line.replace(/^[ \t]*[-*] /, '').trim();
      return `<li>${clean}</li>`;
    }).join('');
    return `\n<ul>${items}</ul>\n`;
  });

  // 5. Định dạng Inline Code, Bold, Italic, Link
  processed = processed.replace(/`([^`]+)`/g, '<code>$1</code>');
  processed = processed.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
  processed = processed.replace(/\*([^*]+)\*/g, '<em>$1</em>');
  processed = processed.replace(/\[([^\]]+)\]\((https?:\/\/[^\s\)]+)\)/g, '<a href="$2" target="_blank" rel="noopener noreferrer" style="color:#38bdf8;text-decoration:underline;">$1</a>');

  // 6. Định dạng Tiêu đề Headers
  processed = processed.replace(/^### (.*$)/gim, '<h3>$1</h3>');
  processed = processed.replace(/^## (.*$)/gim, '<h2>$1</h2>');
  processed = processed.replace(/^# (.*$)/gim, '<h1>$1</h1>');

  // 7. Định dạng đoạn văn bản và ngắt dòng (tránh chèn thừa vào table hoặc ul)
  const paragraphs = processed.split(/\n{2,}/).map(para => {
    const trimmed = para.trim();
    if (!trimmed) return '';
    if (trimmed.startsWith('<table') || trimmed.startsWith('<ul>') || trimmed.startsWith('<h') || trimmed.startsWith('%%CODEBLOCK')) {
      return trimmed;
    }
    return `<p>${trimmed.replace(/\n/g, '<br>')}</p>`;
  }).filter(Boolean).join('');

  // 8. Khôi phục lại các khối Code blocks nguyên bản
  let finalHtml = paragraphs;
  codeBlocks.forEach((block, idx) => {
    finalHtml = finalHtml.replace(`%%CODEBLOCK_${idx}%%`, block);
  });

  return finalHtml;
}
if (typeof window !== 'undefined') window.parseMarkdown = parseMarkdown;
if (typeof module !== 'undefined') module.exports = { parseMarkdown };
