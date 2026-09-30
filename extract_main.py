import re

with open('wp-content/themes/viomi-theme/html-templates/category.html', 'r', encoding='utf-8') as f:
    content = f.read()

match = re.search(r'(<main.*?</main>)', content, re.DOTALL)
if match:
    main_content = match.group(1)
    with open('wp-content/themes/viomi-theme/woocommerce/archive-product.php', 'w', encoding='utf-8') as out:
        out.write("<?php get_header(); ?>\n")
        out.write(main_content)
        out.write("\n<?php get_footer(); ?>\n")
