<?php

$theme_dir = "/var/www/html/wp-content/themes/viomi-theme";
$html_dir = $theme_dir . "/html-templates";

// 1. Update style.css
$style_path = $theme_dir . "/style.css";
$style_content = file_get_contents($style_path);

if (strpos($style_content, "Template: astra") === false) {
    $style_content = str_replace("*/", "Template: astra\n*/", $style_content);
    file_put_contents($style_path, $style_content);
}

// 2. Update functions.php
$functions_path = $theme_dir . "/functions.php";
$functions_content = <<<EOD
<?php
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
EOD;
file_put_contents($functions_path, $functions_content);

// 3. Create front-page.php
$homepage_path = $html_dir . "/homepage.html";
$homepage_html = file_get_contents($homepage_path);

if (preg_match('/<body[^>]*>(.*)<\/body>/is', $homepage_html, $matches)) {
    $body_content = $matches[1];
    $body_content = preg_replace('/<header.*?<\/header>/is', '', $body_content);
    $body_content = preg_replace('/<footer.*?<\/footer>/is', '', $body_content);
    
    $grid_pattern = '/(<div class="lg:w-4\/5 grid grid-cols-2 md:grid-cols-4 gap-4">).*?(<\/div>\s*<\/div>\s*<div class="mt-10 text-center">)/is';
    $woo_loop = <<<EOD
$1
    <?php
    \$args = array(
        'post_type' => 'product',
        'posts_per_page' => 8
    );
    \$loop = new WP_Query( \$args );
    if ( \$loop->have_posts() ) {
        while ( \$loop->have_posts() ) : \$loop->the_post();
            wc_get_template_part( 'content', 'product' );
        endwhile;
    } else {
        echo __( 'No products found' );
    }
    wp_reset_postdata();
    ?>
    $2
EOD;
    $body_content = preg_replace($grid_pattern, $woo_loop, $body_content);
    
    $front_page_php = "<?php get_header(); ?>\n" . $body_content . "\n<?php get_footer(); ?>";
    file_put_contents($theme_dir . "/front-page.php", $front_page_php);
}

// 4. Create woocommerce/archive-product.php
$woo_dir = $theme_dir . "/woocommerce";
if (!file_exists($woo_dir)) {
    mkdir($woo_dir, 0777, true);
}

$category_path = $html_dir . "/category.html";
$category_html = file_get_contents($category_path);

if (preg_match('/<body[^>]*>(.*)<\/body>/is', $category_html, $matches)) {
    $cat_body = $matches[1];
    $cat_body = preg_replace('/<div class="w-full">.*?<!-- Navigation Bar -->.*?<\/nav>\s*<\/div>/is', '', $cat_body);
    $cat_body = preg_replace('/<footer.*?<\/footer>/is', '', $cat_body);
    
    $grid_pattern = '/(<!-- Product Grid -->\s*<div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">).*?(<\/div>\s*<div class="flex justify-center mt-12">)/is';
    $woo_loop = <<<EOD
$1
    <?php
    if ( have_posts() ) {
        while ( have_posts() ) {
            the_post();
            wc_get_template_part( 'content', 'product' );
        }
    }
    ?>
    $2
EOD;
    $cat_body = preg_replace($grid_pattern, $woo_loop, $cat_body);
    
    $archive_php = "<?php get_header(); ?>\n" . $cat_body . "\n<?php get_footer(); ?>";
    file_put_contents($woo_dir . "/archive-product.php", $archive_php);
}

// 5. Create woocommerce/single-product.php
$product_path = $html_dir . "/product_detail.html";
$product_html = file_get_contents($product_path);

if (preg_match('/<body[^>]*>(.*)<\/body>/is', $product_html, $matches)) {
    $prod_body = $matches[1];
    $prod_body = preg_replace('/<!-- Header & Breadcrumb.*?<!-- Main Content Wrapper -->/is', '<!-- Main Content Wrapper -->', $prod_body);
    $prod_body = preg_replace('/<footer.*?<\/footer>/is', '', $prod_body);
    
    $prod_body = str_replace('Tivi Xiaomi Redmi A Pro 75 inch 2025', '<?php echo $product->get_name(); ?>', $prod_body);
    $prod_body = preg_replace('/<img class="w-full h-full object-cover" data-alt="A premium 75-inch ultra-high-definition.*?" src=".*?"\/>/is', '<?php echo $product->get_image(); ?>', $prod_body);
    $prod_body = str_replace('<span class="text-3xl font-bold text-secondary">15.490.000đ</span>', '<?php echo $product->get_price_html(); ?>', $prod_body);
    
    $single_php = "<?php get_header(); ?>\n<?php global \$product; ?>\n" . $prod_body . "\n<?php get_footer(); ?>";
    file_put_contents($woo_dir . "/single-product.php", $single_php);
}

echo "Conversion complete!";
