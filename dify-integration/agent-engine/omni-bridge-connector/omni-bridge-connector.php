<?php
/**
 * Plugin Name: Omni Bridge Connector
 * Plugin URI: https://michinhhang.vn
 * Description: High-performance, lightweight REST API Bridge for Autonomous Marketing AI Engine (Dify / CrewAI). Enables secure remote publishing to WordPress & WooCommerce with Rank Math / Yoast SEO metadata and media sideloading.
 * Version: 1.4.4
 * Author: Omni Marketing Engineering Team
 * Text Domain: omni-bridge-connector
 * License: GPLv2 or later
 */

if (!defined('ABSPATH')) {
    exit;
}

// ─── Load Trait Modules (refactored from monolithic 1045-line file) ───
require_once __DIR__ . '/includes/class-routes-trait.php';
require_once __DIR__ . '/includes/class-api-trait.php';
require_once __DIR__ . '/includes/class-admin-trait.php';
require_once __DIR__ . '/includes/class-seo-trait.php';
require_once __DIR__ . '/includes/class-helpers-trait.php';
require_once __DIR__ . '/includes/class-site-repair-trait.php';
require_once __DIR__ . '/includes/class-silo-legal-trait.php';
require_once __DIR__ . '/includes/class-eeat-fallback-data.php';
require_once __DIR__ . '/includes/class-license-trait.php';
require_once __DIR__ . '/includes/class-task-runner-trait.php';
require_once __DIR__ . '/includes/class-introspection-trait.php';
require_once __DIR__ . '/includes/class-plugin-interop-trait.php';

class Omni_Bridge_Connector {
    use RoutesTrait;
    use ApiTrait;
    use AdminTrait;
    use SeoTrait;
    use HelpersTrait;
    use SiteRepairTrait;
    use SiloLegalTrait;
    use Omni_Bridge_License_Trait;
    use Omni_Bridge_Task_Runner_Trait;
    use Omni_Bridge_Introspection_Trait;
    use Omni_Bridge_Plugin_Interop_Trait;

    const VERSION = '1.4.4';
    const OPTION_KEY = 'omni_bridge_api_key';
    const OPTION_STATUS = 'omni_bridge_default_status';
    const OPTION_SEO_PROVIDER = 'omni_bridge_seo_provider';
    const OPTION_GSC_ENGINE_URL = 'omni_bridge_gsc_engine_url';
    const OPTION_GSC_TENANT_ID = 'omni_bridge_gsc_tenant_id';
    const OPTION_GSC_PROJECT_ID = 'omni_bridge_gsc_project_id';
    const OPTION_GSC_ENGINE_API_KEY = 'omni_bridge_gsc_engine_api_key';
    const REST_NAMESPACE = 'omni-bridge/v1';

    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        if (!get_option(self::OPTION_KEY)) {
            $key = 'obc_' . bin2hex(random_bytes(20));
            update_option(self::OPTION_KEY, $key);
        }
        if (is_admin()) {
            add_action('admin_init', array($this, 'auto_sync_embedded_license'));
        }
        $this->init_task_runner();
        add_action('rest_api_init', array($this, 'register_routes'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_post_omni_bridge_gsc_connect', array($this, 'handle_admin_gsc_connect'));

        // Hooks tự động hóa On-Page & Schema E-E-A-T
        add_action('wp_head', array($this, 'render_homepage_seo_head'), 1);
        add_action('wp_footer', array($this, 'render_homepage_semantic_h1'), 99);
        add_action('wp_footer', array($this, 'render_eeat_footer_trust_links'), 100);
        add_filter('pre_get_document_title', array($this, 'filter_homepage_title'), 9999);
        add_filter('wp_title', array($this, 'filter_homepage_wp_title'), 9999, 2);
        add_filter('rank_math/frontend/title', array($this, 'filter_homepage_title'), 9999);
        add_filter('wpseo_title', array($this, 'filter_homepage_title'), 9999);

        // Hooks tương thích Theme Phổ quát (Universal Theme Compatibility)
        add_filter('woocommerce_product_tabs', array($this, 'register_universal_specs_tab'), 25);
        add_shortcode('product_technical_specs', array($this, 'shortcode_technical_specs'));
    }

    public function activate() {
        if (!get_option(self::OPTION_KEY)) {
            $key = 'obc_' . bin2hex(random_bytes(20));
            update_option(self::OPTION_KEY, $key);
        }
        if (!get_option(self::OPTION_STATUS)) {
            update_option(self::OPTION_STATUS, 'draft');
        }
        if (!get_option(self::OPTION_SEO_PROVIDER)) {
            update_option(self::OPTION_SEO_PROVIDER, 'auto');
        }
        $this->auto_sync_embedded_license();
        $this->push_site_topology_to_portal();
    }
}

// Initialize
if (class_exists('Omni_Bridge_Connector')) {
    new Omni_Bridge_Connector();
}

