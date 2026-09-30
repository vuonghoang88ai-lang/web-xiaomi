<?php
require_once('../../../wp-load.php');
ob_start();
mi_render_header_navigation(false);
$out = ob_get_clean();
echo "OUTPUT LENGTH: " . strlen($out) . "\n";
echo "OUTPUT: \n" . $out;
