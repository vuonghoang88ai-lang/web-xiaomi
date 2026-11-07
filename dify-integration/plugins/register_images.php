<?php
require('/var/www/html/wp-load.php');
require_once(ABSPATH . 'wp-admin/includes/image.php');

$file1 = '/var/www/html/wp-content/uploads/2026/10/xiaomi-mijia-omni-2-robot-hut-bui.jpg';
$att_id1 = wp_insert_attachment(array(
    'guid'           => home_url('/wp-content/uploads/2026/10/xiaomi-mijia-omni-2-robot-hut-bui.jpg'),
    'post_mime_type' => 'image/jpeg',
    'post_title'     => 'Robot hút bụi lau nhà Xiaomi Mijia Omni 2 kèm trạm sạc All-in-One',
    'post_status'    => 'inherit'
), $file1);

if ($att_id1 && !is_wp_error($att_id1)) {
    $data = wp_generate_attachment_metadata($att_id1, $file1);
    wp_update_attachment_metadata($att_id1, $data);
    update_post_meta($att_id1, '_wp_attachment_image_alt', 'Robot hút bụi lau nhà Xiaomi Mijia Omni 2 kèm trạm sạc All-in-One chính hãng');
    echo "Registered robot img ID: " . $att_id1 . "\n";
}

$file2 = '/var/www/html/wp-content/uploads/2026/10/xiaomi-mijia-omni-2-chi-tiet-dong-co.jpg';
$att_id2 = wp_insert_attachment(array(
    'guid'           => home_url('/wp-content/uploads/2026/10/xiaomi-mijia-omni-2-chi-tiet-dong-co.jpg'),
    'post_mime_type' => 'image/jpeg',
    'post_title'     => 'Cụm giẻ lau xoay kép và chổi cao su chống rối Xiaomi Mijia Omni 2',
    'post_status'    => 'inherit'
), $file2);

if ($att_id2 && !is_wp_error($att_id2)) {
    $data2 = wp_generate_attachment_metadata($att_id2, $file2);
    wp_update_attachment_metadata($att_id2, $data2);
    update_post_meta($att_id2, '_wp_attachment_image_alt', 'Chi tiết cụm giẻ lau kép xoay 180 vòng/phút Xiaomi Mijia Omni 2');
    echo "Registered features img ID: " . $att_id2 . "\n";
}
