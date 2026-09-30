<?php
// Script to convert HTML templates to WP Theme PHP files

$theme_dir = '/home/ubuntu/michinhhang/wp-content/themes/viomi-theme';
$html_dir = $theme_dir . '/html-templates';

// 1. Update style.css (ensure Template: astra) - it already has it, so skip or verify
// 2. Update functions.php - add inline styles/scripts from head
$functions = file_get_contents($theme_dir . '/functions.php');
if (strpos($functions, 'tailwind.config =') === false) {
    $functions .= "
function viomi_add_tailwind_config() {
    ?>
    <script id=\"tailwind-config\">
        tailwind.config = {
            darkMode: \"class\",
            theme: {
                extend: {
                    colors: {
                        \"primary\": \"#006779\",
                        \"secondary\": \"#e3001b\",
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
";
    file_put_contents($theme_dir . '/functions.php', $functions);
}

// 2. front-page.php
$homepage = file_get_contents($html_dir . '/homepage.html');
// Extract body
preg_match('/<body[^>]*>(.*?)<\/body>/is', $homepage, $body_matches);
$body_content = $body_matches[1];
// Remove header (<!-- 1. Header Section --> to <!-- 2. Hero Section -->)
$body_content = preg_replace('/<!-- 1\. Header Section -->.*?<!-- 2\. Hero Section -->/is', '<!-- 2. Hero Section -->', $body_content);
// Remove footer (<!-- 6. Footer --> to <!-- 7. Floating Action Bar -->)
$body_content = preg_replace('/<!-- 6\. Footer -->.*?<!-- 7\. Floating Action Bar -->/is', '<!-- 7. Floating Action Bar -->', $body_content);

// Replace product grid
$product_loop = '<?php
                $args = array( "post_type" => "product", "posts_per_page" => 4 );
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
                ?>';

$body_content = preg_replace('/<!-- Right Split: Product Grid -->.*?<\/div>\s*<\/div>\s*<div class="mt-10 text-center">/is', '<!-- Right Split: Product Grid --><div class="lg:w-4/5 grid grid-cols-2 md:grid-cols-4 gap-4">' . $product_loop . '</div></div><div class="mt-10 text-center">', $body_content);

$front_page = "<?php get_header(); ?>\n" . $body_content . "\n<?php get_footer(); ?>";
file_put_contents($theme_dir . '/front-page.php', $front_page);

// 3. WooCommerce Override
@mkdir($theme_dir . '/woocommerce', 0755, true);

// archive-product.php
$category = file_get_contents($html_dir . '/category.html');
preg_match('/<body[^>]*>(.*?)<\/body>/is', $category, $cat_body_matches);
$cat_content = $cat_body_matches[1];
$cat_content = preg_replace('/<!-- Header Section \(Brand Anchors Logic\) -->.*?<main/is', '<main', $cat_content);
$cat_content = preg_replace('/<!-- Footer Section -->.*?<\/footer>/is', '', $cat_content);

$archive_loop = '<?php if ( have_posts() ) {
    while ( have_posts() ) {
        the_post();
        wc_get_template_part( "content", "product" );
    }
} ?>';
$cat_content = preg_replace('/<!-- Product Grid -->.*?<div class="flex justify-center mt-12">/is', '<!-- Product Grid --><div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4 gap-4">' . $archive_loop . '</div><div class="flex justify-center mt-12">', $cat_content);

$archive_product = "<?php get_header('shop'); ?>\n" . $cat_content . "\n<?php get_footer('shop'); ?>";
file_put_contents($theme_dir . '/woocommerce/archive-product.php', $archive_product);

// single-product.php
$product_html = file_get_contents($html_dir . '/product_detail.html');
preg_match('/<body[^>]*>(.*?)<\/body>/is', $product_html, $prod_body_matches);
$prod_content = $prod_body_matches[1];
$prod_content = preg_replace('/<!-- Header & Breadcrumb \(Shared Component Integrity\) -->.*?<!-- Main Content Wrapper -->/is', '<!-- Main Content Wrapper -->', $prod_content);
$prod_content = preg_replace('/<!-- Footer -->.*?<\/footer>/is', '', $prod_content);

$prod_content = str_replace('<h1 class="text-3xl font-bold text-on-surface mb-2">Tivi Xiaomi Redmi A Pro 75 inch 2025</h1>', '<h1 class="text-3xl font-bold text-on-surface mb-2"><?php global $product; echo $product->get_name(); ?></h1>', $prod_content);
$prod_content = preg_replace('/<div class="flex items-center gap-3">\s*<span class="text-3xl font-bold text-secondary">.*?<\/span>\s*<span class="text-lg text-outline line-through">.*?<\/span>\s*<span class="bg-secondary text-white px-2 py-0.5 rounded-lg text-sm font-bold">.*?<\/span>\s*<\/div>/is', '<div class="flex items-center gap-3"><span class="text-3xl font-bold text-secondary"><?php echo $product->get_price_html(); ?></span></div>', $prod_content);
$prod_content = preg_replace('/<img class="w-full h-full object-cover" data-alt="A premium 75-inch ultra-high-definition.*?">/is', '<?php echo $product->get_image("full", array("class" => "w-full h-full object-cover")); ?>', $prod_content);
$prod_content = preg_replace('/<div class="prose max-w-none text-on-surface-variant line-clamp-fade" id="description-content">.*?<\/div>/is', '<div class="prose max-w-none text-on-surface-variant line-clamp-fade" id="description-content"><?php echo apply_filters( "the_content", $product->get_description() ); ?></div>', $prod_content);
$prod_content = preg_replace('/<h4 class="font-bold text-on-surface line-clamp-1">Tivi Xiaomi Redmi A Pro 75 inch 2025<\/h4>\s*<p class="text-secondary font-bold">15\.490\.000đ<\/p>/is', '<h4 class="font-bold text-on-surface line-clamp-1"><?php echo $product->get_name(); ?></h4><p class="text-secondary font-bold"><?php echo $product->get_price_html(); ?></p>', $prod_content);

$single_product = "<?php get_header('shop'); ?>\n" . $prod_content . "\n<?php get_footer('shop'); ?>";
file_put_contents($theme_dir . '/woocommerce/single-product.php', $single_product);

echo "Success";
?>
