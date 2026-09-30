<?php
require_once('wp-load.php');
$taxonomies = get_taxonomies(array('object_type' => array('product')), 'objects');
$output = [];
foreach ($taxonomies as $tax) {
    if ($tax->name === 'product_cat' || strpos($tax->name, 'pa_') === 0) {
        $terms = get_terms(array('taxonomy' => $tax->name, 'hide_empty' => false));
        $term_names = array_map(function($t) { return $t->name; }, $terms);
        $output[$tax->name] = $term_names;
    }
}
echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
