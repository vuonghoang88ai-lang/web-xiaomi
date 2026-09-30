import os

fpath = '/home/ubuntu/michinhhang/wp-content/themes/omni-commerce/inc/woocommerce.php'
with open(fpath, 'r') as f:
    lines = f.readlines()

# Extract lines 4 to 26
menu_setup = "".join(lines[3:26])

# Remove lines 4 to 26 from woocommerce.php
new_woo_lines = lines[:3] + ["if ( class_exists( 'WooCommerce' ) ) {\n"] + lines[27:] + ["\n}\n"]
with open(fpath, 'w') as f:
    f.writelines(new_woo_lines)

# Append menu_setup to theme-setup.php
setup_path = '/home/ubuntu/michinhhang/wp-content/themes/omni-commerce/inc/theme-setup.php'
with open(setup_path, 'a') as f:
    f.write("\n" + menu_setup)
