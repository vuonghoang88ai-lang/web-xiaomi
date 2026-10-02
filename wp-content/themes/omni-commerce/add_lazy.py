import os

base_dir = '/home/ubuntu/michinhhang/wp-content/themes/omni-commerce/template-parts/home'

def add_lazy_to_images(fpath):
    with open(fpath, 'r') as f:
        content = f.read()
    
    # Simple replace for <img tags that don't have loading="lazy" and don't have fetchpriority="high"
    new_content = ""
    for line in content.splitlines():
        if '<img ' in line and 'loading=' not in line and 'fetchpriority=' not in line:
            line = line.replace('<img ', '<img loading="lazy" ')
        new_content += line + "\n"
        
    with open(fpath, 'w') as f:
        f.write(new_content)

for filename in os.listdir(base_dir):
    if filename.endswith('.php'):
        add_lazy_to_images(os.path.join(base_dir, filename))

# For WooCommerce product images, WC handles them natively (adds loading="lazy" automatically since WP 5.5).
