<?php
require 'wp-load.php';
$acf_rows = array();
$acf_data = get_field( 'mi_home_product_row', 'option' );
if ( empty( $acf_data ) ) {
    $acf_data = get_field( 'mi_home_product_rows', 'option' );
}
if ( is_array( $acf_data ) ) {
    $acf_rows = $acf_data;
}
echo "ACF ROWS COUNT: " . count($acf_rows) . "\n";
