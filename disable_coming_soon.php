<?php
require_once('wp-load.php');
update_option('woocommerce_coming_soon', 'no');
update_option('woocommerce_store_pages_only', 'no');
echo "Coming soon mode disabled.\n";
