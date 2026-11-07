import sys
import json
import db_service
from seed_analyzer import analyze_seed_competitor

sys.stdout.reconfigure(line_buffering=True)

if __name__ == "__main__":
    project_id = '59350544-800b-43d2-b53a-d8ffbe663209'
    project = db_service.get_project_by_id(project_id)
    if not project:
        print('Project not found!')
        sys.exit(1)

    seed_url = 'https://viomiviet.vn'
    print(f'1. Đang bóc tách sâu On-page đối thủ mẫu {seed_url}...')
    seed_analysis = analyze_seed_competitor(seed_url, timeout=8)

    print('=== TRÍCH XUẤT THÀNH CÔNG ===')
    clusters = seed_analysis.get('keyword_clusters', {})
    print('Head Keywords:', clusters.get('head', []))
    print('Secondary Model Keywords:', clusters.get('secondary', []))
    print('Longtail Intent Keywords:', clusters.get('longtail', []))
    print('Flat List:', seed_analysis.get('extracted_keywords', []))

    bp = project.get('brand_profile') or {}
    if isinstance(bp, str):
        bp = json.loads(bp)

    bp['seed_analysis'] = seed_analysis
    bp['extracted_keywords'] = seed_analysis.get('extracted_keywords', [])

    with db_service.get_connection() as conn:
        with conn.cursor() as cur:
            cur.execute('UPDATE projects SET brand_profile = %s, updated_at = NOW() WHERE id = %s;',
                        (json.dumps(bp, ensure_ascii=False), project_id))
            conn.commit()

    print('2. ĐÃ CẬP NHẬT TRỌN VẸN VÀO CSDL POSTGRESQL!')

