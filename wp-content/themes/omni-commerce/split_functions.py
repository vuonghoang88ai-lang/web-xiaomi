import re
import os

with open('/home/ubuntu/michinhhang/wp-content/themes/omni-commerce/functions.php', 'r') as f:
    lines = f.readlines()

def get_block(start_line, end_line_exclusive):
    return "".join(lines[start_line-1:end_line_exclusive-1])

# Header (1-7)
header = get_block(1, 8)

# theme-setup.php
# Lines 8 to 91 (init setup, enqueue, after_setup)
# Lines 567 to 853 (menus, widgets)
theme_setup = "<?php\nif ( ! defined( 'ABSPATH' ) ) exit;\n\n" + get_block(8, 92) + "\n" + get_block(567, 854)

# acf-fields.php
# Lines 92 to 566
acf_fields = "<?php\nif ( ! defined( 'ABSPATH' ) ) exit;\n\n" + get_block(92, 567)

# woocommerce.php
# Lines 854 to end (1364)
woocommerce = "<?php\nif ( ! defined( 'ABSPATH' ) ) exit;\n\n" + get_block(854, len(lines)+1)

inc_dir = '/home/ubuntu/michinhhang/wp-content/themes/omni-commerce/inc'
os.makedirs(inc_dir, exist_ok=True)

with open(os.path.join(inc_dir, 'theme-setup.php'), 'w') as f:
    f.write(theme_setup)

with open(os.path.join(inc_dir, 'acf-fields.php'), 'w') as f:
    f.write(acf_fields)

with open(os.path.join(inc_dir, 'woocommerce.php'), 'w') as f:
    f.write(woocommerce)

new_functions = header + """
require_once get_template_directory() . '/inc/theme-setup.php';
require_once get_template_directory() . '/inc/acf-fields.php';
require_once get_template_directory() . '/inc/woocommerce.php';
"""

with open('/home/ubuntu/michinhhang/wp-content/themes/omni-commerce/functions.php', 'w') as f:
    f.write(new_functions)

print("Split completed successfully!")
