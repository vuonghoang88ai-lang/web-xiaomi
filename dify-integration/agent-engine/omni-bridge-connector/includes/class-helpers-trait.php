<?php
/**
 * Trait: helpers_trait
 * Auto-generated from omni-bridge-connector.php refactor.
 * Contains split methods for the Omni_Bridge_Connector class.
 */

trait HelpersTrait {
    private function get_site_kit_gsc_connection() {
        $result = array(
            'source' => 'none',
            'connection_detected' => false,
            'property_uri' => '',
        );

        $plugin_class = 'Google\\Site_Kit\\Plugin';
        if (!class_exists($plugin_class)) {
            return $result;
        }

        $settings = get_option('googlesitekit_search-console_settings', array());
        $credentials = get_option('googlesitekit_credentials', '');
        $property_uri = is_array($settings) ? sanitize_text_field($settings['propertyID'] ?? '') : '';
        $is_connected = !empty($credentials) && !empty($property_uri);
        if ($is_connected) {
            $result['source'] = 'google_site_kit';
            $result['connection_detected'] = true;
            $result['property_uri'] = $property_uri;
        }
        return $result;
    }

    private function sideload_image_to_post($image_url, $post_id, $alt_text = '') {
        if (empty($image_url)) {
            return false;
        }

        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        // 1. Kiểm tra nếu URL là file cục bộ đã tồn tại trong wp-content/uploads/
        $upload_dir = wp_upload_dir();
        $parsed_path = parse_url($image_url, PHP_URL_PATH);
        if ($parsed_path && strpos($parsed_path, '/wp-content/uploads/') !== false) {
            $rel_path = substr($parsed_path, strpos($parsed_path, '/wp-content/uploads/') + strlen('/wp-content/uploads/'));
            $local_file = trailingslashit($upload_dir['basedir']) . ltrim($rel_path, '/');
            if (file_exists($local_file)) {
                // Kiểm tra xem attachment đã đăng ký chưa
                global $wpdb;
                $existing_att = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value = %s LIMIT 1", $rel_path));
                if ($existing_att) {
                    if (!empty($alt_text)) {
                        update_post_meta($existing_att, '_wp_attachment_image_alt', sanitize_text_field($alt_text));
                    }
                    return intval($existing_att);
                }

                // Nếu file đã có nhưng chưa có attachment post, tạo ngay
                $file_type = wp_check_filetype(basename($local_file), null);
                $attachment = array(
                    'guid'           => $upload_dir['baseurl'] . '/' . _wp_relative_upload_path($local_file),
                    'post_mime_type' => $file_type['type'],
                    'post_title'     => preg_replace('/\.[^.]+$/', '', basename($local_file)),
                    'post_content'   => '',
                    'post_status'    => 'inherit'
                );
                $attach_id = wp_insert_attachment($attachment, $local_file, $post_id);
                if (!is_wp_error($attach_id)) {
                    $attach_data = wp_generate_attachment_metadata($attach_id, $local_file);
                    wp_update_attachment_metadata($attach_id, $attach_data);
                    if (!empty($alt_text)) {
                        update_post_meta($attach_id, '_wp_attachment_image_alt', sanitize_text_field($alt_text));
                    }
                    return $attach_id;
                }
            }
        }

        // 2. Kiểm tra nếu ảnh đã có trong Media Library qua attachment_url_to_postid
        $existing_id = attachment_url_to_postid($image_url);
        if ($existing_id) {
            if (!empty($alt_text)) {
                update_post_meta($existing_id, '_wp_attachment_image_alt', sanitize_text_field($alt_text));
            }
            return $existing_id;
        }

        // 3. Tải từ xa (fallback) với timeout ngắn & bypass SSRF chặn local IP
        add_filter('http_request_args', function($args) {
            $args['timeout'] = 15;
            $args['reject_unsafe_urls'] = false;
            return $args;
        });
        $att_id = media_sideload_image($image_url, $post_id, $alt_text, 'id');
        if (!is_wp_error($att_id)) {
            if (!empty($alt_text)) {
                update_post_meta($att_id, '_wp_attachment_image_alt', sanitize_text_field($alt_text));
            }
            return $att_id;
        }
        return false;
    }

    /**
     * Tương thích Theme Phổ quát (Universal Theme Compatibility):
     * Tự động đăng ký Tab "Thông số kỹ thuật" chuẩn WooCommerce Product Tabs.
     * Tương thích 100% với Flatsome, WoodMart, Astra, Storefront, Kadence, GeneratePress...
     */
    public function shortcode_technical_specs($atts) {
        $atts = shortcode_atts(array(
            'id' => 0,
        ), $atts, 'product_technical_specs');

        $post_id = intval($atts['id']);
        if (!$post_id) {
            $post_id = get_the_ID();
        }

        if (!$post_id) {
            return '';
        }

        return $this->get_specs_table_html($post_id);
    }

    /**
     * Tạo HTML bảng thông số kỹ thuật trung tính, tự thích ứng màu sắc và độ rộng của mọi theme
     */
    public function get_specs_table_html($post_id) {
        $specs = get_post_meta($post_id, 'technical_specs', true);
        if (empty($specs) || !is_array($specs)) {
            return '';
        }

        ob_start();
        ?>
        <div class="obc-specs-wrapper" style="margin: 20px 0; overflow-x: auto;">
            <table class="obc-specs-table" style="width: 100%; border-collapse: collapse; font-size: 14px; border: 1px solid #e2e8f0; line-height: 1.6; text-align: left;">
                <tbody>
                    <?php foreach ($specs as $idx => $spec) :
                        $lbl = !empty($spec['spec_label']) ? esc_html($spec['spec_label']) : '';
                        $val = !empty($spec['spec_value']) ? wp_kses_post($spec['spec_value']) : '';
                        if (empty($lbl)) continue;
                        $bg = ($idx % 2 === 0) ? '#f8fafc' : '#ffffff';
                    ?>
                    <tr style="background-color: <?php echo esc_attr($bg); ?>; border-bottom: 1px solid #e2e8f0;">
                        <td style="padding: 10px 14px; font-weight: 600; color: #475569; width: 35%; border-right: 1px solid #e2e8f0; vertical-align: top;"><?php echo $lbl; ?></td>
                        <td style="padding: 10px 14px; color: #1e293b; vertical-align: top;"><?php echo $val; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Đồng bộ thuộc tính sản phẩm WooCommerce & thông số kỹ thuật (ACF & Core)
     */
    public function sync_woocommerce_product_meta($post_id, $product_data, $title = '') {
        $stock = !empty($product_data['stock_status']) ? sanitize_text_field($product_data['stock_status']) : 'instock';
        update_post_meta($post_id, '_stock_status', $stock);
        wp_set_object_terms($post_id, 'simple', 'product_type');

        if (!empty($product_data['technical_specs']) && is_array($product_data['technical_specs'])) {
            $clean_specs = array();
            foreach ($product_data['technical_specs'] as $spec) {
                if (!empty($spec['spec_label'])) {
                    $clean_specs[] = array(
                        'spec_label' => sanitize_text_field($spec['spec_label']),
                        'spec_value' => sanitize_text_field($spec['spec_value'] ?? '')
                    );
                }
            }
            if (!empty($clean_specs)) {
                update_post_meta($post_id, 'technical_specs', $clean_specs);
                foreach ($clean_specs as $idx => $spec) {
                    update_post_meta($post_id, "technical_specs_{$idx}_spec_label", $spec['spec_label']);
                    update_post_meta($post_id, "technical_specs_{$idx}_spec_value", $spec['spec_value']);
                }
                if (function_exists('update_field')) {
                    update_field('technical_specs', $clean_specs, $post_id);
                }
                $wc_attributes = array();
                foreach ($clean_specs as $idx => $spec) {
                    $attr_slug = sanitize_title($spec['spec_label']);
                    $wc_attributes[$attr_slug] = array(
                        'name' => $spec['spec_label'], 'value' => $spec['spec_value'],
                        'position' => $idx, 'is_visible' => 1, 'is_variation' => 0, 'is_taxonomy' => 0,
                    );
                }
                update_post_meta($post_id, '_product_attributes', $wc_attributes);
            }
        }

        if (!empty($product_data['gallery_image_ids']) && is_array($product_data['gallery_image_ids'])) {
            update_post_meta($post_id, '_product_image_gallery', implode(',', array_map('intval', $product_data['gallery_image_ids'])));
        }
        if (!empty($product_data['gallery_image_urls']) && is_array($product_data['gallery_image_urls'])) {
            $g_ids = !empty($product_data['gallery_image_ids']) && is_array($product_data['gallery_image_ids']) ? $product_data['gallery_image_ids'] : array();
            foreach ($product_data['gallery_image_urls'] as $g_url) {
                $g_id = $this->sideload_image_to_post($g_url, $post_id, $title);
                if ($g_id && !is_wp_error($g_id)) $g_ids[] = $g_id;
            }
            if (!empty($g_ids)) {
                update_post_meta($post_id, '_product_image_gallery', implode(',', array_unique(array_map('intval', $g_ids))));
            }
        }
        if (function_exists('wc_delete_product_transients')) {
            wc_delete_product_transients($post_id);
        }
    }
}

