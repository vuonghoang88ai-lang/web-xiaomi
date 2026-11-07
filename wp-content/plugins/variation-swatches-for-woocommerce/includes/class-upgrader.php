<?php
if ( ! class_exists( 'VSWC_Upgrader' ) ) {
	class VSWC_Upgrader {
		/**
		 * The single instance of the class
		 *
		 * @var VSWC_Upgrader
		 */
		protected static $instance = null;

		private $previous_version_need_to_compare = '1.0.11';
		private $saved_plugin_version;

		/**
		 * Main instance
		 *
		 * @return VSWC_Upgrader
		 */
		public static function instance() {
			if ( null == self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		public function __construct() {
			add_action( 'admin_notices', array( $this, 'show_warning_message_after_upgrading' ) );
			add_action( 'admin_init', array( $this, 'set_current_plugin_version' ) );
		}

		function set_current_plugin_version() {
			if ( ! get_option( 'wcvs_current_version', false ) ) {
				update_option( 'wcvs_current_version', WCVS_PLUGIN_VERSION, true );
			}
		}

		/**
		 * Showing the warning message after upgrading plugin
		 */
		public function show_warning_message_after_upgrading() {
			global $pagenow;

			// Only on the Plugins screen and on the plugin's own settings page, never across the whole admin.
			$is_settings_page = TA_WC_Variation_Swatches::is_in_plugin_settings_page();
			if ( ! $is_settings_page && 'plugins.php' !== $pagenow ) {
				return;
			}

			if ( ! $this->is_admin_notice_should_be_shown() ) {
				return;
			}
			?>
            <div class="notice-warning notice is-dismissible">
                <p>
					<?php
					if ( $is_settings_page ) {
						esc_html_e( 'Click Save to store your Variation Swatches settings.', 'wcvs' );
					} else {
						printf(
							/* translators: %s: URL of the plugin's settings page */
							wp_kses( __( 'You need to update new settings for the Variation Swatches plugin <a href="%s">here</a>.', 'wcvs' ), array( 'a' => array( 'href' => array() ) ) ),
							esc_url( admin_url( 'admin.php?page=variation-swatches-settings' ) )
						);
					}
					?>
                </p>
            </div>
			<?php
		}

		/**
		 * Check if the admin notice should be show
		 *
		 * @return bool
		 */
		public function is_admin_notice_should_be_shown() {
			$this->saved_plugin_version = $this->get_current_plugin_version();

			return $this->is_notice_allowed_to_show() && ! $this->is_met_admin_notice_requirements();
		}

		/**
		 * @return bool
		 */
		private function is_notice_allowed_to_show() {
			if ( WCVS_NEED_TO_SHOW_NOTICE_AFTER_UPGRADED ) {
				return ! empty( $this->saved_plugin_version ) && $this->is_current_version_newer_than_compared_version();
			}

			return false;
		}

		/**
		 * @return bool|int
		 */
		private function is_current_version_newer_than_compared_version() {
			return $this->compare_plugin_version( $this->saved_plugin_version, $this->previous_version_need_to_compare, '>' );
		}

		/**
		 * @return false|mixed|void
		 */
		private function get_current_plugin_version() {
			return get_option( 'wcvs_current_version', false );
		}

		/**
		 * Checking if the new settings has been update after upgrading
		 *
		 * @return bool
		 */
		private function is_met_admin_notice_requirements() {
			$new_settings_value = get_option( 'woosuite_variation_swatches_option', false );
			if ( empty( $new_settings_value ) || ! isset( $new_settings_value['general'] ) ) {
				return false;
			}

			return true;
		}

		/**
		 *
		 * @param $old_version
		 * @param $new_version
		 * @param $operator
		 *
		 * @return bool|int
		 */
		private function compare_plugin_version( $old_version, $new_version, $operator ) {
			$p    = '#(\.0+)+($|-)#';
			$ver1 = preg_replace( $p, '', $old_version );
			$ver2 = preg_replace( $p, '', $new_version );

			return isset( $operator ) ?
				version_compare( $ver1, $ver2, $operator ) :
				version_compare( $ver1, $ver2 );
		}

	}
}