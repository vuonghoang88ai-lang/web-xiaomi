import os
import re

theme_dir = '/home/ubuntu/michinhhang/wp-content/themes/viomi-theme'
html_dir = os.path.join(theme_dir, 'html-templates')

# 1. functions.php
functions_file = os.path.join(theme_dir, 'functions.php')
with open(functions_file, 'r') as f:
    functions_content = f.read()

if 'tailwind.config =' not in functions_content:
    tailwind_script = """
function viomi_add_tailwind_config() {
    ?>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#006779",
                        "secondary": "#e3001b",
                    }
                }
            }
        }
    </script>
    <style>
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        .product-card:hover { transform: translateY(-3px); box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1); }
        .tab-active { border-bottom: 3px solid #008198; color: #0c1a1d; font-weight: 700; }
        .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    </style>
    <?php
}
add_action('wp_head', 'viomi_add_tailwind_config', 999);
"""
    with open(functions_file, 'w') as f:
        f.write(functions_content + tailwind_script)

# 2. front-page.php
with open(os.path.join(html_dir, 'homepage.html'), 'r') as f:
    homepage = f.read()

body_match = re.search(r'<body[^>]*>(.*?)</body>', homepage, re.IGNORECASE | re.DOTALL)
if body_match:
    body_content = body_match.group(1)
    # Remove header
    body_content = re.sub(r'<!-- 1\. Header Section -->.*?<!-- 2\. Hero Section -->', '<!-- 2. Hero Section -->', body_content, flags=re.IGNORECASE | re.DOTALL)
    # Remove footer
    body_content = re.sub(r'<!-- 6\. Footer -->.*?<!-- 7\. Floating Action Bar -->', '<!-- 7. Floating Action Bar -->', body_content, flags=re.IGNORECASE | re.DOTALL)

    # Replace product grid
    product_loop = """<?php
                $args = array( 'post_type' => 'product', 'posts_per_page' => 4 );
                $loop = new WP_Query( $args );
                if ( $loop->have_posts() ) {
                    while ( $loop->have_posts() ) : $loop->the_post();
                        global $product;
                ?>
                <div class="product-card bg-white p-4 rounded border border-outline-variant relative flex flex-col transition-all cursor-pointer">
                    <?php if ( $product->is_on_sale() ) : ?>
                        <span class="absolute top-2 right-2 bg-secondary text-white text-[10px] font-bold px-2 py-1 rounded-full z-10">Sale</span>
                    <?php endif; ?>
                    <a href="<?php echo get_permalink(); ?>" class="aspect-square w-full mb-4 block">
                        <?php echo $product->get_image("woocommerce_thumbnail", array("class" => "w-full h-full object-contain")); ?>
                    </a>
                    <h3 class="text-body-md font-bold text-on-surface line-clamp-2 min-h-[40px]">
                        <a href="<?php echo get_permalink(); ?>"><?php echo get_the_title(); ?></a>
                    </h3>
                    <div class="mt-2 flex items-baseline gap-2">
                        <span class="text-secondary font-black text-body-lg"><?php echo $product->get_price_html(); ?></span>
                    </div>
                </div>
                <?php
                    endwhile;
                }
                wp_reset_postdata();
                ?>"""

    body_content = re.sub(r'<!-- Right Split: Product Grid -->.*?</div>\s*</div>\s*<div class="mt-10 text-center">', 
                          f'<!-- Right Split: Product Grid --><div class="lg:w-4/5 grid grid-cols-2 md:grid-cols-4 gap-4">{product_loop}</div></div><div class="mt-10 text-center">', 
                          body_content, flags=re.IGNORECASE | re.DOTALL)

    front_page = f"<?php get_header(); ?>\n{body_content}\n<?php get_footer(); ?>"
    with open(os.path.join(theme_dir, 'front-page.php'), 'w') as f:
        f.write(front_page)

# 3. archive-product.php and single-product.php
woo_dir = os.path.join(theme_dir, 'woocommerce')
os.makedirs(woo_dir, exist_ok=True)

with open(os.path.join(html_dir, 'category.html'), 'r') as f:
    category = f.read()

cat_body_match = re.search(r'<body[^>]*>(.*?)</body>', category, re.IGNORECASE | re.DOTALL)
if cat_body_match:
    cat_content = cat_body_match.group(1)
    cat_content = re.sub(r'<!-- Header Section \(Brand Anchors Logic\) -->.*?<main', '<main', cat_content, flags=re.IGNORECASE | re.DOTALL)
    cat_content = re.sub(r'<!-- Footer Section -->.*?</footer?>', '', cat_content, flags=re.IGNORECASE | re.DOTALL)

    archive_loop = """<?php if ( have_posts() ) {
        while ( have_posts() ) {
            the_post();
            wc_get_template_part( "content", "product" );
        }
    } ?>"""
    cat_content = re.sub(r'<!-- Product Grid -->.*?<div class="flex justify-center mt-12">', 
                         f'<!-- Product Grid --><div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">{archive_loop}</div><div class="flex justify-center mt-12">', 
                         cat_content, flags=re.IGNORECASE | re.DOTALL)

    archive_product = f"<?php get_header('shop'); ?>\n{cat_content}\n<?php get_footer('shop'); ?>"
    with open(os.path.join(woo_dir, 'archive-product.php'), 'w') as f:
        f.write(archive_product)

with open(os.path.join(html_dir, 'product_detail.html'), 'r') as f:
    product_html = f.read()

prod_body_match = re.search(r'<body[^>]*>(.*?)</body>', product_html, re.IGNORECASE | re.DOTALL)
if prod_body_match:
    prod_content = prod_body_match.group(1)
    prod_content = re.sub(r'<!-- Header & Breadcrumb \(Shared Component Integrity\) -->.*?<!-- Main Content Wrapper -->', '<!-- Main Content Wrapper -->', prod_content, flags=re.IGNORECASE | re.DOTALL)
    prod_content = re.sub(r'<!-- Footer -->.*?</footer?>', '', prod_content, flags=re.IGNORECASE | re.DOTALL)

    prod_content = prod_content.replace('<h1 class="text-3xl font-bold text-on-surface mb-2">Tivi Xiaomi Redmi A Pro 75 inch 2025</h1>', '<h1 class="text-3xl font-bold text-on-surface mb-2"><?php global $product; echo $product->get_name(); ?></h1>')
    prod_content = re.sub(r'<div class="flex items-center gap-3">\s*<span class="text-3xl font-bold text-secondary">.*?</span>\s*<span class="text-lg text-outline line-through">.*?</span>\s*<span class="bg-secondary text-white px-2 py-0.5 rounded-lg text-sm font-bold">.*?</span>\s*</div>', '<div class="flex items-center gap-3"><span class="text-3xl font-bold text-secondary"><?php echo $product->get_price_html(); ?></span></div>', prod_content, flags=re.IGNORECASE | re.DOTALL)
    prod_content = re.sub(r'<img class="w-full h-full object-cover" data-alt="A premium 75-inch ultra-high-definition.*?">', '<?php echo $product->get_image("full", array("class" => "w-full h-full object-cover")); ?>', prod_content, flags=re.IGNORECASE | re.DOTALL)
    prod_content = re.sub(r'<div class="prose max-w-none text-on-surface-variant line-clamp-fade" id="description-content">.*?</div>', '<div class="prose max-w-none text-on-surface-variant line-clamp-fade" id="description-content"><?php echo apply_filters( "the_content", $product->get_description() ); ?></div>', prod_content, flags=re.IGNORECASE | re.DOTALL)
    prod_content = re.sub(r'<h4 class="font-bold text-on-surface line-clamp-1">Tivi Xiaomi Redmi A Pro 75 inch 2025</h4>\s*<p class="text-secondary font-bold">15\.490\.000đ</p>', '<h4 class="font-bold text-on-surface line-clamp-1"><?php echo $product->get_name(); ?></h4><p class="text-secondary font-bold"><?php echo $product->get_price_html(); ?></p>', prod_content, flags=re.IGNORECASE | re.DOTALL)

    single_product = f"<?php get_header('shop'); ?>\n{prod_content}\n<?php get_footer('shop'); ?>"
    with open(os.path.join(woo_dir, 'single-product.php'), 'w') as f:
        f.write(single_product)

print("Success")
