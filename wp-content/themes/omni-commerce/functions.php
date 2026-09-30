<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Nơi thêm các functions cho theme


require_once get_template_directory() . '/inc/theme-setup.php';
require_once get_template_directory() . '/inc/acf-fields.php';
require_once get_template_directory() . '/inc/woocommerce.php';
require_once get_template_directory() . '/inc/customizer.php';
require_once get_template_directory() . '/inc/performance.php';
require_once get_template_directory() . '/inc/plugins-require.php';
