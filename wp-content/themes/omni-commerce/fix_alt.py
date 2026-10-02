import os

base_dir = '/home/ubuntu/michinhhang/wp-content/themes/omni-commerce/template-parts/home'

# product-row.php
fpath = os.path.join(base_dir, 'product-row.php')
with open(fpath, 'r') as f:
    content = f.read()
content = content.replace('src="<?php echo esc_url($banner_img); ?>"/>', 'src="<?php echo esc_url($banner_img); ?>" alt="<?php echo esc_attr(strip_tags($banner_title)); ?>"/>')
with open(fpath, 'w') as f:
    f.write(content)

# media-services.php
fpath = os.path.join(base_dir, 'media-services.php')
with open(fpath, 'r') as f:
    content = f.read()
content = content.replace('src="<?php echo esc_url($img_url); ?>"/>', 'src="<?php echo esc_url($img_url); ?>" alt="Video review"/>')
with open(fpath, 'w') as f:
    f.write(content)

# hero.php
fpath = os.path.join(base_dir, 'hero.php')
with open(fpath, 'r') as f:
    content = f.read()
content = content.replace('text=News"/>', 'text=News" alt="News banner"/>')
content = content.replace('src="https://lh3.googleusercontent.com/aida-public/AB6AXuAs7q1NOzItCLJpsrFy80wNNA0bG0Z4gFzxxZ122yJi5VoUp0slrXKoB8rYmtE6qoZ6A0qWb2Ou_DxY_j_0ViBXCdq4QUos47NK4Nv8xpDa9WiO987KNUzU4qs-nzucsV4ij5UWoXhaYEO274vbq2wEjvwrVs8V9ksmRFfH_l33676dVkQX-xkGFIVa3umIP0N8-RZS4FLjZiP84lC7rm1TEsDetmSSBv4xa1NzcJv_rROrSGSlmkCP"/>', 'src="https://lh3.googleusercontent.com/aida-public/AB6AXuAs7q1NOzItCLJpsrFy80wNNA0bG0Z4gFzxxZ122yJi5VoUp0slrXKoB8rYmtE6qoZ6A0qWb2Ou_DxY_j_0ViBXCdq4QUos47NK4Nv8xpDa9WiO987KNUzU4qs-nzucsV4ij5UWoXhaYEO274vbq2wEjvwrVs8V9ksmRFfH_l33676dVkQX-xkGFIVa3umIP0N8-RZS4FLjZiP84lC7rm1TEsDetmSSBv4xa1NzcJv_rROrSGSlmkCP" alt="Promo banner"/>')
content = content.replace('src="https://lh3.googleusercontent.com/aida-public/AB6AXuB9kb5CZbP7UBqwxNtd69C0kIzqURwlEV-zR0c5kBeq00QMaZVa9WMn2d4PoAAhEuojA1iH4796JHQsIRb8kwnZPztQoU34ElyFHX4x9fmdgZd_kwZGwniHXUEwHK_qsv6L18ipeaHIZQAB5alXdxkJHclRKy6pECnJj7_ILak4Dqnu9ZAynXPu6TDZwAhxE37vxu2btWpszQG5EvbZ7tuOWsOruJuhznehDrqk4MheaGiihOUVW7Ll"/>', 'src="https://lh3.googleusercontent.com/aida-public/AB6AXuB9kb5CZbP7UBqwxNtd69C0kIzqURwlEV-zR0c5kBeq00QMaZVa9WMn2d4PoAAhEuojA1iH4796JHQsIRb8kwnZPztQoU34ElyFHX4x9fmdgZd_kwZGwniHXUEwHK_qsv6L18ipeaHIZQAB5alXdxkJHclRKy6pECnJj7_ILak4Dqnu9ZAynXPu6TDZwAhxE37vxu2btWpszQG5EvbZ7tuOWsOruJuhznehDrqk4MheaGiihOUVW7Ll" alt="Promo banner"/>')
with open(fpath, 'w') as f:
    f.write(content)
