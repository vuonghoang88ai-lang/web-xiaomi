import os

theme_dir = '/home/ubuntu/michinhhang/wp-content/themes/omni-commerce'
abspath_str = "\nif ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly. }\n"

for root, dirs, files in os.walk(theme_dir):
    for file in files:
        if file.endswith('.php'):
            fpath = os.path.join(root, file)
            with open(fpath, 'r', encoding='utf-8') as f:
                content = f.read()
            
            if "defined( 'ABSPATH'" not in content and "defined('ABSPATH'" not in content:
                if content.startswith('<?php'):
                    content = content.replace('<?php', '<?php' + abspath_str, 1)
                    with open(fpath, 'w', encoding='utf-8') as f:
                        f.write(content)
                    print(f"Added ABSPATH to {fpath}")
