import os
import glob

theme_dir = '/home/ubuntu/michinhhang/wp-content/themes/viomi-theme/'
php_files = glob.glob(os.path.join(theme_dir, '*.php'))

abspath_str = "\nif ( ! defined( 'ABSPATH' ) ) { exit; }\n"

for fpath in php_files:
    with open(fpath, 'r', encoding='utf-8') as f:
        content = f.read()
    
    if "defined( 'ABSPATH' )" not in content and content.startswith('<?php'):
        # insert after <?php
        content = content.replace('<?php', '<?php' + abspath_str, 1)
        with open(fpath, 'w', encoding='utf-8') as f:
            f.write(content)
        print(f"Added ABSPATH to {os.path.basename(fpath)}")
