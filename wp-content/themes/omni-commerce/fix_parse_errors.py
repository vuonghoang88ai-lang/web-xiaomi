import os
import glob

theme_dir = '/home/ubuntu/michinhhang/wp-content/themes/omni-commerce'

for root, _, files in os.walk(theme_dir):
    for filename in files:
        if not filename.endswith('.php'):
            continue
            
        fpath = os.path.join(root, filename)
        with open(fpath, 'r', encoding='utf-8') as f:
            content = f.read()

        changed = False
        
        # Fix issue 1: <?php </main>\n<?php
        if '<?php </main>\n<?php get_footer();' in content:
            content = content.replace('<?php </main>\n<?php get_footer();', '</main>\n<?php get_footer();')
            changed = True
            
        # Fix issue 2: { exit; // Exit if accessed directly. }
        if '// Exit if accessed directly. }' in content:
            content = content.replace('// Exit if accessed directly. }', '} // Exit if accessed directly.')
            changed = True
            
        # Wait, what if it was not <?php get_footer(); but just get_footer(); inside a larger php block?
        # Actually in standard WP it's usually <?php get_footer();
        # Let's check if there's any `<?php </main>`
        if '<?php </main>' in content:
            content = content.replace('<?php </main>', '</main>')
            changed = True

        if changed:
            with open(fpath, 'w', encoding='utf-8') as f:
                f.write(content)
            print(f"Fixed syntax errors in {filename}")

