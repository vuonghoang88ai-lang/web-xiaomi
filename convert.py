import os
import re

theme_dir = "/var/www/html/wp-content/themes/viomi-theme"
html_dir = os.path.join(theme_dir, "html-templates")

# 1. Update style.css
style_path = os.path.join(theme_dir, "style.css")
with open(style_path, "r") as f:
    style_content = f.read()

if "Template: astra" not in style_content:
    style_content = style_content.replace("*/", "Template: astra\n*/")
    with open(style_path, "w") as f:
        f.write(style_content)

# 2. Update functions.php
functions_path = os.path.join(theme_dir, "functions.php")
with open(functions_path, "w") as f:
    f.write('''<?php
// Nơi thêm các functions cho theme

function viomi_theme_enqueue_styles() {
    wp_enqueue_style( 'astra-theme-css', get_template_directory_uri() . '/style.css' );
    wp_enqueue_style( 'viomi-theme-css', get_stylesheet_uri(), array('astra-theme-css') );
    
    wp_enqueue_script( 'tailwind-css', 'https://cdn.tailwindcss.com?plugins=forms,container-queries', array(), null, false );
    wp_enqueue_style( 'google-fonts-open-sans', 'https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600;700;800&display=swap', false );
    wp_enqueue_style( 'google-fonts-public-sans', 'https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;700&display=swap', false );
    wp_enqueue_style( 'google-fonts-be-vietnam-pro', 'https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;700&display=swap', false );
    wp_enqueue_style( 'material-symbols', 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap', false );
}
add_action( 'wp_enqueue_scripts', 'viomi_theme_enqueue_styles', 20 );

function viomi_theme_woocommerce_setup() {
    add_theme_support( 'woocommerce' );
}
add_action( 'after_setup_theme', 'viomi_theme_woocommerce_setup' );
''')

# 3. Create front-page.php
homepage_path = os.path.join(html_dir, "homepage.html")
with open(homepage_path, "r") as f:
    homepage_html = f.read()

body_match = re.search(r'<body[^>]*>(.*)</body>', homepage_html, re.DOTALL)
if body_match:
    body_content = body_match.group(1)
    body_content = re.sub(r'<header.*?</header>', '', body_content, flags=re.DOTALL)
    body_content = re.sub(r'<footer.*?</footer>', '', body_content, flags=re.DOTALL)
    
    grid_pattern = r'(<div class="lg:w-4/5 grid grid-cols-2 md:grid-cols-4 gap-4">).*?(</div>\s*</div>\s*<div class="mt-10 text-center">)'
    woo_loop = r'''\1
    <?php
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => 8
    );
    $loop = new WP_Query( $args );
    if ( $loop->have_posts() ) {
        while ( $loop->have_posts() ) : $loop->the_post();
            wc_get_template_part( 'content', 'product' );
        endwhile;
    } else {
        echo __( 'No products found' );
    }
    wp_reset_postdata();
    ?>
    \2'''
    body_content = re.sub(grid_pattern, woo_loop, body_content, flags=re.DOTALL)
    
    front_page_php = "<?php get_header(); ?>\n" + body_content + "\n<?php get_footer(); ?>"
    with open(os.path.join(theme_dir, "front-page.php"), "w") as f:
        f.write(front_page_php)

# 4. Create woocommerce/archive-product.php
woo_dir = os.path.join(theme_dir, "woocommerce")
os.makedirs(woo_dir, exist_ok=True)

category_path = os.path.join(html_dir, "category.html")
with open(category_path, "r") as f:
    category_html = f.read()

cat_body_match = re.search(r'<body[^>]*>(.*)</body>', category_html, re.DOTALL)
if cat_body_match:
    cat_body = cat_body_match.group(1)
    cat_body = re.sub(r'<div class="w-full">.*?<!-- Navigation Bar -->.*?</nav>\s*</div>', '', cat_body, flags=re.DOTALL)
    cat_body = re.sub(r'<footer.*?</footer>', '', cat_body, flags=re.DOTALL)
    
    grid_pattern = r'(<!-- Product Grid -->\s*<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">).*?(</div>\s*<div class="flex justify-center mt-12">)'
    woo_loop = r'''\1
    <?php
    if ( have_posts() ) {
        while ( have_posts() ) {
            the_post();
            wc_get_template_part( 'content', 'product' );
        }
    }
    ?>
    \2'''
    cat_body = re.sub(grid_pattern, woo_loop, cat_body, flags=re.DOTALL)
    
    archive_php = "<?php get_header(); ?>\n" + cat_body + "\n<?php get_footer(); ?>"
    with open(os.path.join(woo_dir, "archive-product.php"), "w") as f:
        f.write(archive_php)

# 5. Create woocommerce/single-product.php
product_path = os.path.join(html_dir, "product_detail.html")
with open(product_path, "r") as f:
    product_html = f.read()

prod_body_match = re.search(r'<body[^>]*>(.*)</body>', product_html, re.DOTALL)
if prod_body_match:
    prod_body = prod_body_match.group(1)
    prod_body = re.sub(r'<!-- Header & Breadcrumb.*?<!-- Main Content Wrapper -->', '<!-- Main Content Wrapper -->', prod_body, flags=re.DOTALL)
    prod_body = re.sub(r'<footer.*?</footer>', '', prod_body, flags=re.DOTALL)
    
    prod_body = re.sub(r'Tivi Xiaomi Redmi A Pro 75 inch 2025', '<?php echo $product->get_name(); ?>', prod_body)
    prod_body = re.sub(r'<img class="w-full h-full object-cover" data-alt="A premium 75-inch ultra-high-definition.*?" src=".*?"/>', '<?php echo $product->get_image(); ?>', prod_body)
    prod_body = re.sub(r'<span class="text-3xl font-bold text-secondary">15\.490\.000đ</span>', '<?php echo $product->get_price_html(); ?>', prod_body)
    
    single_php = "<?php get_header(); ?>\n<?php global $product; ?>\n" + prod_body + "\n<?php get_footer(); ?>"
    with open(os.path.join(woo_dir, "single-product.php"), "w") as f:
        f.write(single_php)

print("Conversion complete!")
