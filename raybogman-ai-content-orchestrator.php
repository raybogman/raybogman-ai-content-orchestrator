<?php
/**
 * Plugin Name:       Ray Bogman AI Content Orchestrator
 * Plugin URI:        https://github.com/raybogman/raybogman-ai-content-orchestrator
 * Description:       End-to-end AI content pipeline for WordPress: website scanning, SEO metadata, AI-generated articles, featured images (gpt-image-1), internal linking, and Yoast integration. Supports Claude and OpenAI.
 * Version:           1.0.1
 * Requires at least: 5.9
 * Tested up to:      7.0
 * Requires PHP:      7.4
 * Author:            Ray Bogman
 * Author URI:        https://bogman.info
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       raybogman-ai-content-orchestrator
 * Domain Path:       /languages
 *
 * @package Raybogman_Content_Orchestrator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'RBCO_VERSION', '1.0.1' );
define( 'RBCO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'RBCO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'RBCO_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Capture a PHP-generated inline script body and attach it to an already
 * enqueued/registered script handle through the proper WordPress API.
 *
 * The pair of ob_start() and ob_get_clean() lives entirely inside this
 * single function scope, so static analysers (Plugin Check, WPCS) can
 * verify the buffer is always closed in the same logical flow.
 *
 * @param string   $handle   Registered script handle to attach the inline JS to.
 * @param callable $callback Closure that echoes the JS body (no <script> tag).
 */
function rbco_capture_inline_script( $handle, $callback ) {
	ob_start();
	$callback();
	wp_add_inline_script( $handle, (string) ob_get_clean() );
}

/**
 * Main plugin class. Prefixed to satisfy WordPress.org class-prefix guidance
 * (`RBCO_` is the plugin's chosen short prefix used throughout the codebase).
 */
final class RBCO_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var RBCO_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return RBCO_Plugin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->init_hooks();
	}

	/**
	 * Load required files.
	 */
	private function load_dependencies() {
		require_once RBCO_PLUGIN_DIR . 'includes/class-rbco-settings.php';
		require_once RBCO_PLUGIN_DIR . 'includes/class-rbco-styles.php';
		require_once RBCO_PLUGIN_DIR . 'includes/class-rbco-scanner.php';
		require_once RBCO_PLUGIN_DIR . 'includes/class-rbco-generator.php';
		require_once RBCO_PLUGIN_DIR . 'includes/class-rbco-publisher.php';
		require_once RBCO_PLUGIN_DIR . 'includes/class-rbco-internal-linker.php';
		require_once RBCO_PLUGIN_DIR . 'includes/class-rbco-gutenberg-converter.php';
		require_once RBCO_PLUGIN_DIR . 'includes/class-rbco-image-overlay.php';
		require_once RBCO_PLUGIN_DIR . 'admin/class-rbco-admin.php';
	}

	/**
	 * Register hooks.
	 */
	private function init_hooks() {
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		add_action( 'plugins_loaded', array( $this, 'on_plugins_loaded' ) );

		// Migration: detect old AI Content Creator plugin and offer import + deactivate.
		add_action( 'admin_notices', array( $this, 'maybe_show_migration_notice' ) );
		add_action( 'wp_ajax_rbco_verify_migration', array( $this, 'ajax_verify_migration' ) );

		// Allow TTF/OTF font uploads for the image overlay feature.
		add_filter( 'upload_mimes', array( $this, 'allow_font_uploads' ) );
		add_filter( 'wp_check_filetype_and_ext', array( $this, 'fix_font_filetype' ), 10, 3 );
	}

	/**
	 * Show migration notice if AI Content Creator is still active.
	 */
	public function maybe_show_migration_notice() {
		if ( get_option( 'rbco_migration_verified', false ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || ( false === strpos( $screen->id, 'ai-content' ) && false === strpos( $screen->id, 'rbco' ) ) ) {
			return;
		}

		$old_active   = is_plugin_active( 'ai-content-creator/ai-content-creator.php' );
		$has_settings = '' !== get_option( 'rbco_ai_provider', '' );

		if ( ! $has_settings && ! $old_active ) {
			return;
		}

		$nonce = wp_create_nonce( 'rbco_migration' );
		?>
		<div class="notice notice-info" id="rbco-migration-notice" style="padding: 12px 16px;">
			<p>
				<span class="dashicons dashicons-migrate" style="color: #2271b1; vertical-align: text-bottom; font-size: 20px; margin-right: 6px;"></span>
				<?php if ( $old_active ) : ?>
					<strong><?php esc_html_e( 'AI Content Creator is still active!', 'raybogman-ai-content-orchestrator' ); ?></strong>
					<?php esc_html_e( 'Both plugins share the same settings — your configuration is already active here. You can safely deactivate the old plugin yourself from the Plugins page.', 'raybogman-ai-content-orchestrator' ); ?>
				<?php else : ?>
					<strong><?php esc_html_e( 'Settings from AI Content Creator detected!', 'raybogman-ai-content-orchestrator' ); ?></strong>
					<?php esc_html_e( 'Your previous configuration is automatically active — no migration needed.', 'raybogman-ai-content-orchestrator' ); ?>
				<?php endif; ?>
			</p>
			<p style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
				<button type="button" class="button button-primary" id="rbco-verify-migration-btn">
					<span class="dashicons dashicons-yes" style="vertical-align:text-bottom;font-size:16px;width:16px;height:16px;margin-right:4px;"></span>
					<?php esc_html_e( 'Verify Settings', 'raybogman-ai-content-orchestrator' ); ?>
				</button>
				<?php if ( $old_active ) : ?>
					<a href="<?php echo esc_url( admin_url( 'plugins.php?s=AI%20Content%20Creator&plugin_status=all' ) ); ?>" class="button" style="color:#d63638;border-color:#d63638;">
						<span class="dashicons dashicons-admin-plugins" style="vertical-align:text-bottom;font-size:16px;width:16px;height:16px;margin-right:4px;"></span>
						<?php esc_html_e( 'Go to Plugins page to deactivate it', 'raybogman-ai-content-orchestrator' ); ?>
					</a>
				<?php endif; ?>
				<button type="button" class="button" id="rbco-dismiss-migration-btn">
					<?php esc_html_e( 'Dismiss', 'raybogman-ai-content-orchestrator' ); ?>
				</button>
				<span id="rbco-migration-status"></span>
			</p>
		</div>
		<?php
		// Register an inline-only script handle and attach the notice behaviour
		// through the proper script API instead of printing it inline.
		wp_register_script( 'rbco-migration-notice', false, array( 'jquery' ), RBCO_VERSION, true );
		wp_enqueue_script( 'rbco-migration-notice' );
		wp_localize_script(
			'rbco-migration-notice',
			'rbcoMigration',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => $nonce,
			)
		);
		rbco_capture_inline_script(
			'rbco-migration-notice',
			function () {
				?>
		jQuery(document).ready(function($) {
			var ajaxurl = rbcoMigration.ajaxUrl;
			var nonce = rbcoMigration.nonce;

			$('#rbco-verify-migration-btn').on('click', function() {
				var $btn = $(this);
				$btn.prop('disabled', true);
				$('#rbco-migration-status').html('<span class="spinner is-active" style="float:none;margin:0;"></span>');
				$.post(ajaxurl, { action: 'rbco_verify_migration', nonce: nonce }).done(function(r) {
					if (r.success) {
						$('#rbco-migration-notice').removeClass('notice-info').addClass('notice-success');
						$('#rbco-migration-notice p').first().html(
							'<span class="dashicons dashicons-yes-alt" style="color:#00a32a;vertical-align:text-bottom;font-size:20px;margin-right:6px;"></span><strong>' + r.data.message + '</strong>'
						);
						$btn.remove();
						$('#rbco-migration-status').empty();
						var tbl = '<table class="widefat striped" style="max-width:600px;margin-top:10px;"><tbody>';
						for (var k in r.data.settings) { tbl += '<tr><td style="font-weight:600;width:180px;">' + k + '</td><td>' + r.data.settings[k] + '</td></tr>'; }
						tbl += '</tbody></table>';
						$('#rbco-migration-notice').append(tbl);
					}
				}).always(function() { $btn.prop('disabled', false); });
			});

			$('#rbco-dismiss-migration-btn').on('click', function() {
				$.post(ajaxurl, { action: 'rbco_verify_migration', nonce: nonce, dismiss: '1' });
				$('#rbco-migration-notice').fadeOut();
			});
		});
				<?php
			}
		);
		?>
		<?php
	}

	/**
	 * AJAX: Verify migration settings.
	 */
	public function ajax_verify_migration() {
		check_ajax_referer( 'rbco_migration', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions.' ) );
		}
		if ( ! empty( $_POST['dismiss'] ) ) {
			update_option( 'rbco_migration_verified', true );
			wp_send_json_success();
			return;
		}

		$settings                = array();
		$provider                = get_option( 'rbco_ai_provider', '' );
		$settings['AI Provider'] = $provider ? ucfirst( $provider ) : '—';

		$ck                         = get_option( 'rbco_anthropic_api_key', '' );
		$settings['Claude API Key'] = $ck ? '****' . substr( $ck, -4 ) : '—';

		$ok                         = get_option( 'rbco_openai_api_key', '' );
		$settings['OpenAI API Key'] = $ok ? '****' . substr( $ok, -4 ) : '—';

		$ip                         = get_option( 'rbco_image_provider', '' );
		$settings['Image Provider'] = $ip ? ucfirst( $ip ) : '—';

		$pv                         = get_option( 'rbco_project_vision', '' );
		$settings['Project Vision'] = ! empty( $pv ) ? substr( $pv, 0, 50 ) . ( strlen( $pv ) > 50 ? '...' : '' ) : '—';

		$urls                   = get_option( 'rbco_saved_urls', array() );
		$settings['Saved URLs'] = is_array( $urls ) ? count( $urls ) . ' URL(s)' : '—';

		update_option( 'rbco_migration_verified', true );

		wp_send_json_success(
			array(
				'message'  => __( 'All settings verified! Your configuration is fully active in Ray Bogman AI Content Orchestrator.', 'raybogman-ai-content-orchestrator' ),
				'settings' => $settings,
			)
		);
	}

	/**
	 * Allow TTF/OTF/WOFF font uploads (used by the image-overlay feature).
	 *
	 * @param array $mimes Allowed MIME types keyed by file extension.
	 * @return array Filtered MIME types.
	 */
	public function allow_font_uploads( $mimes ) {
		$mimes['ttf']  = 'font/ttf';
		$mimes['otf']  = 'font/otf';
		$mimes['woff'] = 'font/woff';
		return $mimes;
	}

	/**
	 * Correct the detected file type for uploaded font files.
	 *
	 * @param array  $data     File data array (ext, type, proper_filename).
	 * @param string $file     Full path to the uploaded file.
	 * @param string $filename The name of the uploaded file.
	 * @return array Filtered file data.
	 */
	public function fix_font_filetype( $data, $file, $filename ) {
		$ext = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
		if ( in_array( $ext, array( 'ttf', 'otf', 'woff' ), true ) ) {
			$data['ext']  = $ext;
			$data['type'] = 'font/' . $ext;
		}
		return $data;
	}

	/**
	 * Plugin activation.
	 */
	public function activate() {
		$defaults = array(
			'ai_provider'       => 'claude',
			'anthropic_api_key' => '',
			'claude_model'      => 'claude-sonnet-4-6',
			'openai_api_key'    => '',
			'openai_model'      => 'gpt-4o',
			'max_context_chars' => 18000,
			'request_timeout'   => 15,
		);

		foreach ( $defaults as $key => $value ) {
			if ( false === get_option( 'rbco_' . $key ) ) {
				update_option( 'rbco_' . $key, $value );
			}
		}

		// Clean up any scheduling event left over from an earlier version.
		wp_clear_scheduled_hook( 'rbco_catch_up_scheduled' );

		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation.
	 */
	public function deactivate() {
		wp_clear_scheduled_hook( 'rbco_catch_up_scheduled' );
		flush_rewrite_rules();
	}

	/**
	 * Fires on plugins_loaded.
	 */
	public function on_plugins_loaded() {
		if ( is_admin() ) {
			RBCO_Admin::get_instance();
		}

		// Remove any scheduling event left over from an earlier version.
		if ( wp_next_scheduled( 'rbco_catch_up_scheduled' ) ) {
			wp_clear_scheduled_hook( 'rbco_catch_up_scheduled' );
		}
	}
}

RBCO_Plugin::get_instance();
