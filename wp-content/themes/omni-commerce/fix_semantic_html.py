import os

theme_dir = '/home/ubuntu/michinhhang/wp-content/themes/omni-commerce'
templates = ['page.php', 'single.php', 'archive.php', 'search.php', '404.php', 'category.php', 'home.php', 'index.php', 'front-page.php', 'page-lien-he.php', 'page-tin-tuc.php', 'page-tra-cuu-bao-hanh.php']

for filename in templates:
    fpath = os.path.join(theme_dir, filename)
    if not os.path.exists(fpath):
        continue
        
    with open(fpath, 'r', encoding='utf-8') as f:
        content = f.read()

    # Skip if <main is already in content
    if '<main' in content:
        continue

    # Try to find get_header() and insert <main id="primary"> after it
    if 'get_header(); ?>' in content:
        content = content.replace('get_header(); ?>', 'get_header(); ?>\n<main id="primary" class="site-main">\n', 1)
    
    # Try to find get_footer() and insert </main> before it
    if 'get_footer();' in content:
        content = content.replace('get_footer();', '</main>\n<?php get_footer();', 1)
        
    with open(fpath, 'w', encoding='utf-8') as f:
        f.write(content)
        print(f"Added <main> to {filename}")

