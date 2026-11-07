<?php
/**
 * Trait: seo_trait
 * Auto-generated from omni-bridge-connector.php refactor.
 * Contains split methods for the Omni_Bridge_Connector class.
 */

trait SeoTrait {
    public function sanitize_seo_provider($value) {
        $allowed = array('auto', 'rank_math', 'yoast', 'aioseo', 'seopress', 'none');
        return in_array($value, $allowed, true) ? $value : 'auto';
    }

    public function sanitize_post_status_option($value) {
        return 'draft';
    }

    private function get_detected_seo_plugins() {
        return array(
            'rank_math' => defined('RANK_MATH_VERSION') || class_exists('RankMath'),
            'yoast' => defined('WPSEO_VERSION') || class_exists('WPSEO_Frontend'),
            'aioseo' => defined('AIOSEO_VERSION') || function_exists('aioseo'),
            'seopress' => defined('SEOPRESS_VERSION') || function_exists('seopress_init'),
        );
    }

    private function resolve_seo_provider() {
        $configured = get_option(self::OPTION_SEO_PROVIDER, 'auto');
        if ($configured !== 'auto') {
            return $configured;
        }

        foreach ($this->get_detected_seo_plugins() as $provider => $is_active) {
            if ($is_active) {
                return $provider;
            }
        }
        return 'none';
    }

    private function get_seo_meta_map($provider) {
        $maps = array(
            'rank_math' => array(
                'focus_keyword' => 'rank_math_focus_keyword',
                'seo_title' => 'rank_math_title',
                'seo_description' => 'rank_math_description',
                'canonical_url' => 'rank_math_canonical_url',
            ),
            'yoast' => array(
                'focus_keyword' => '_yoast_wpseo_focuskw',
                'seo_title' => '_yoast_wpseo_title',
                'seo_description' => '_yoast_wpseo_metadesc',
                'canonical_url' => '_yoast_wpseo_canonical',
            ),
            'aioseo' => array(
                'focus_keyword' => '_aioseo_keywords',
                'seo_title' => '_aioseo_title',
                'seo_description' => '_aioseo_description',
                'canonical_url' => '_aioseo_canonical_url',
            ),
            'seopress' => array(
                'focus_keyword' => '_seopress_analysis_target_kw',
                'seo_title' => '_seopress_titles_title',
                'seo_description' => '_seopress_titles_desc',
                'canonical_url' => '_seopress_robots_canonical',
            ),
        );
        return isset($maps[$provider]) ? $maps[$provider] : array();
    }

    private function sync_seo_meta($post_id, $seo) {
        $provider = $this->resolve_seo_provider();
        $map = $this->get_seo_meta_map($provider);
        if ($provider === 'none' || empty($map) || !is_array($seo)) {
            return array('provider' => 'none', 'synced' => false);
        }

        if (!isset($seo['seo_description'])) {
            if (isset($seo['meta_description'])) {
                $seo['seo_description'] = $seo['meta_description'];
            } elseif (isset($seo['description'])) {
                $seo['seo_description'] = $seo['description'];
            }
        }
        if (!isset($seo['seo_title']) && isset($seo['title'])) {
            $seo['seo_title'] = $seo['title'];
        }

        foreach ($map as $field => $meta_key) {
            if (!array_key_exists($field, $seo)) {
                continue;
            }
            $value = $seo[$field];
            if ($field === 'canonical_url') {
                $value = esc_url_raw($value);
            } elseif ($field === 'seo_description') {
                $value = sanitize_textarea_field($value);
            } else {
                $value = sanitize_text_field($value);
            }
            update_post_meta($post_id, $meta_key, $value);
        }

        return array('provider' => $provider, 'synced' => true);
    }

    public function filter_homepage_title($title) {
        if (is_front_page() || is_home()) {
            $custom_title = get_option('omni_bridge_homepage_title');
            if (!empty($custom_title)) {
                return $custom_title;
            }
        }
        return $title;
    }

    public function filter_homepage_wp_title($title, $sep = '') {
        if (is_front_page() || is_home()) {
            $custom_title = get_option('omni_bridge_homepage_title');
            if (!empty($custom_title)) {
                return $custom_title;
            }
        }
        return $title;
    }

}
