import os
import re

theme_dir = '/home/ubuntu/michinhhang/wp-content/themes/omni-commerce'

for root, _, files in os.walk(theme_dir):
    for filename in files:
        if not filename.endswith('.php'):
            continue
            
        fpath = os.path.join(root, filename)
        with open(fpath, 'r', encoding='utf-8') as f:
            content = f.read()

        if '<main' in content and 'id="primary"' not in content:
            content = re.sub(r'<main([^>]*)>', r'<main id="primary"\1>', content)
            with open(fpath, 'w', encoding='utf-8') as f:
                f.write(content)
            print(f"Added id='primary' to <main> in {filename}")
