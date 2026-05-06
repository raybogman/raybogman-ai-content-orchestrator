<?php
/**
 * Plugin Name:       RayBogman Content Orchestrator
 * Plugin URI:        https://github.com/raybogman/raybogman-content-orchestrator
 * Description:       End-to-end AI content pipeline for WordPress: website scanning, SEO, featured images (DALL-E 3 / Ideogram), LinkedIn auto-share, and Yoast integration. Supports Claude and OpenAI.
 * Version:           3.1.0
 * Requires at least: 5.8
 * Tested up to:      6.9
 * Requires PHP:      7.4
 * Author:            Ray Bogman
 * Author URI:        https://bogman.info
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       raybogman-content-orchestrator
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'RBCO_VERSION', '3.1.0' );
define( 'RBCO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'RBCO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'RBCO_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Initialize Freemius SDK.
 */
if ( ! function_exists( 'rbco_fs' ) ) {
	function rbco_fs() {
		global $rbco_fs;

		if ( ! isset( $rbco_fs ) ) {
			require_once dirname( __FILE__ ) . '/vendor/freemius/start.php';

			$rbco_fs = fs_dynamic_init( array(
				'id'                  => '28680',
				'slug'                => 'raybogman-content-orchestrator',
				'type'                => 'plugin',
				'public_key'          => 'pk_cbfd6cc1050eaf4acbcb63111d9b5',
				'is_premium'          => false,
				'has_premium_version' => true,
				'premium_suffix'      => 'Enterprise',
				'has_addons'          => false,
				'has_paid_plans'      => true,
				'is_org_compliant'    => true,
				'menu'                => array(
					'slug'    => 'raybogman-content-orchestrator',
					'support' => false,
					'account' => true,
					'pricing' => true,
				),
			) );
		}

		return $rbco_fs;
	}

	rbco_fs();
	do_action( 'rbco_fs_loaded' );
}

/**
 * Helper: check if Pro plan is active.
 *
 * @return bool
 */
function rbco_is_pro() {
	return rbco_fs()->is_plan( 'enterprise', true );
}

/**
 * Main plugin class.
 */
final class RayBogman_Content_Orchestrator {

	/**
	 * Singleton instance.
	 *
	 * @var RayBogman_Content_Orchestrator|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return RayBogman_Content_Orchestrator
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
		require_once RBCO_PLUGIN_DIR . 'includes/class-rbco-pdf-extractor.php';
		require_once RBCO_PLUGIN_DIR . 'includes/class-rbco-pdf-library.php';
		require_once RBCO_PLUGIN_DIR . 'includes/class-rbco-scanner.php';
		require_once RBCO_PLUGIN_DIR . 'includes/class-rbco-generator.php';
		require_once RBCO_PLUGIN_DIR . 'includes/class-rbco-publisher.php';
		require_once RBCO_PLUGIN_DIR . 'includes/class-rbco-internal-linker.php';
		require_once RBCO_PLUGIN_DIR . 'includes/class-rbco-repurposer.php';
		require_once RBCO_PLUGIN_DIR . 'includes/class-rbco-gutenberg-converter.php';
		require_once RBCO_PLUGIN_DIR . 'includes/class-rbco-thrive-converter.php';
		require_once RBCO_PLUGIN_DIR . 'includes/class-rbco-image-overlay.php';
		require_once RBCO_PLUGIN_DIR . 'includes/class-rbco-linkedin.php';
		require_once RBCO_PLUGIN_DIR . 'includes/class-rbco-instagram.php';
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
		add_action( 'wp_ajax_rbco_deactivate_old_plugin', array( $this, 'ajax_deactivate_old_plugin' ) );

		// Allow TTF/OTF font uploads for the image overlay feature.
		add_filter( 'upload_mimes', array( $this, 'allow_font_uploads' ) );
		add_filter( 'wp_check_filetype_and_ext', array( $this, 'fix_font_filetype' ), 10, 4 );

		// Custom 1-minute cron interval.
		add_filter( 'cron_schedules', array( $this, 'add_cron_interval' ) );

		// On every admin/frontend request, run rate-limited catch-up.
		// This is the primary mechanism — runs whenever anyone visits.
		add_action( 'admin_init', array( $this, 'maybe_catch_up_scheduled' ) );
		add_action( 'init', array( $this, 'maybe_catch_up_scheduled' ) );

		// Dedicated cron event — runs catch-up directly with NO rate limit,
		// so manual triggers from WP Crontrol always actually execute.
		add_action( 'rbco_catch_up_scheduled', array( $this, 'run_catch_up_now' ) );
	}

	/**
	 * Register a custom 1-minute cron interval.
	 *
	 * @param array $schedules Existing cron schedules.
	 * @return array Modified schedules.
	 */
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

		$old_active = is_plugin_active( 'ai-content-creator/ai-content-creator.php' );
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
					<strong><?php esc_html_e( 'AI Content Creator is still active!', 'raybogman-content-orchestrator' ); ?></strong>
					<?php esc_html_e( 'Both plugins share the same settings — your configuration is already active here. You can safely deactivate the old plugin.', 'raybogman-content-orchestrator' ); ?>
				<?php else : ?>
					<strong><?php esc_html_e( 'Settings from AI Content Creator detected!', 'raybogman-content-orchestrator' ); ?></strong>
					<?php esc_html_e( 'Your previous configuration is automatically active — no migration needed.', 'raybogman-content-orchestrator' ); ?>
				<?php endif; ?>
			</p>
			<p style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
				<button type="button" class="button button-primary" id="rbco-verify-migration-btn">
					<span class="dashicons dashicons-yes" style="vertical-align:text-bottom;font-size:16px;width:16px;height:16px;margin-right:4px;"></span>
					<?php esc_html_e( 'Verify Settings', 'raybogman-content-orchestrator' ); ?>
				</button>
				<?php if ( $old_active ) : ?>
					<button type="button" class="button" id="rbco-deactivate-old-btn" style="color:#d63638;border-color:#d63638;">
						<span class="dashicons dashicons-no" style="vertical-align:text-bottom;font-size:16px;width:16px;height:16px;margin-right:4px;"></span>
						<?php esc_html_e( 'Deactivate AI Content Creator', 'raybogman-content-orchestrator' ); ?>
					</button>
				<?php endif; ?>
				<button type="button" class="button" id="rbco-dismiss-migration-btn">
					<?php esc_html_e( 'Dismiss', 'raybogman-content-orchestrator' ); ?>
				</button>
				<span id="rbco-migration-status"></span>
			</p>
		</div>
		<?php add_action( 'admin_footer', function() { ?>
<script type="text/javascript">
		jQuery(document).ready(function($) {
			var nonce = '<?php echo esc_js( $nonce ); ?>';

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

			$('#rbco-deactivate-old-btn').on('click', function() {
				if (!confirm('<?php echo esc_js( __( 'Deactivate AI Content Creator? Your settings will be preserved.', 'raybogman-content-orchestrator' ) ); ?>')) return;
				var $btn = $(this);
				$btn.prop('disabled', true).text('<?php echo esc_js( __( 'Deactivating...', 'raybogman-content-orchestrator' ) ); ?>');
				$.post(ajaxurl, { action: 'rbco_deactivate_old_plugin', nonce: nonce }).done(function(r) {
					if (r.success) {
						$btn.replaceWith('<span class="dashicons dashicons-yes-alt" style="color:#00a32a;vertical-align:text-bottom;"></span> <strong style="color:#00a32a;"><?php echo esc_js( __( 'AI Content Creator deactivated!', 'raybogman-content-orchestrator' ) ); ?></strong>');
					} else {
						$btn.prop('disabled', false).text('<?php echo esc_js( __( 'Deactivate AI Content Creator', 'raybogman-content-orchestrator' ) ); ?>');
						alert(r.data ? r.data.message : 'Failed.');
					}
				});
			});

			$('#rbco-dismiss-migration-btn').on('click', function() {
				$.post(ajaxurl, { action: 'rbco_verify_migration', nonce: nonce, dismiss: '1' });
				$('#rbco-migration-notice').fadeOut();
			});
		});
		</script>
<?php } ); ?>
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

		$settings = array();
		$provider = get_option( 'rbco_ai_provider', '' );
		$settings['AI Provider'] = $provider ? ucfirst( $provider ) : '—';

		$ck = get_option( 'rbco_anthropic_api_key', '' );
		$settings['Claude API Key'] = $ck ? '****' . substr( $ck, -4 ) : '—';

		$ok = get_option( 'rbco_openai_api_key', '' );
		$settings['OpenAI API Key'] = $ok ? '****' . substr( $ok, -4 ) : '—';

		$ip = get_option( 'rbco_image_provider', '' );
		$settings['Image Provider'] = $ip ? ucfirst( $ip ) : '—';

		$pv = get_option( 'rbco_project_vision', '' );
		$settings['Project Vision'] = ! empty( $pv ) ? substr( $pv, 0, 50 ) . ( strlen( $pv ) > 50 ? '...' : '' ) : '—';

		$li = get_option( 'rbco_linkedin_client_id', '' );
		$settings['LinkedIn'] = ! empty( $li ) ? 'Configured' : '—';

		$urls = get_option( 'rbco_saved_urls', array() );
		$settings['Saved URLs'] = is_array( $urls ) ? count( $urls ) . ' URL(s)' : '—';

		update_option( 'rbco_migration_verified', true );

		wp_send_json_success( array(
			'message'  => __( 'All settings verified! Your configuration is fully active in RayBogman Content Orchestrator.', 'raybogman-content-orchestrator' ),
			'settings' => $settings,
		) );
	}

	/**
	 * AJAX: Deactivate the old AI Content Creator plugin.
	 */
	public function ajax_deactivate_old_plugin() {
		check_ajax_referer( 'rbco_migration', 'nonce' );
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions.' ) );
		}

		$old_plugin = 'ai-content-creator/ai-content-creator.php';
		if ( ! is_plugin_active( $old_plugin ) ) {
			wp_send_json_success( array( 'message' => 'Already deactivated.' ) );
			return;
		}

		deactivate_plugins( $old_plugin );
		update_option( 'rbco_migration_verified', true );

		wp_send_json_success( array( 'message' => 'AI Content Creator has been deactivated.' ) );
	}

	public function allow_font_uploads( $mimes ) {
		$mimes['ttf']  = 'font/ttf';
		$mimes['otf']  = 'font/otf';
		$mimes['woff'] = 'font/woff';
		return $mimes;
	}

	public function fix_font_filetype( $data, $file, $filename, $mimes ) {
		$ext = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
		if ( in_array( $ext, array( 'ttf', 'otf', 'woff' ), true ) ) {
			$data['ext']  = $ext;
			$data['type'] = 'font/' . $ext;
		}
		return $data;
	}

	public function add_cron_interval( $schedules ) {
		if ( ! isset( $schedules['rbco_every_minute'] ) ) {
			$schedules['rbco_every_minute'] = array(
				'interval' => 60,
				'display'  => __( 'Every Minute (RayBogman Content Orchestrator)', 'raybogman-content-orchestrator' ),
			);
		}
		return $schedules;
	}

	/**
	 * Rate-limited catch-up. Used for admin_init and init hooks (fires on every
	 * request). Limited to once per minute via transient.
	 */
	public function maybe_catch_up_scheduled() {
		$last_run = (int) get_transient( 'rbco_catch_up_last_run' );
		if ( $last_run && ( time() - $last_run ) < 60 ) {
			return;
		}
		set_transient( 'rbco_catch_up_last_run', time(), 300 );

		$this->run_catch_up_now();
	}

	/**
	 * Unconditional catch-up. Used for the WP cron event directly so manual
	 * triggers from WP Crontrol always execute regardless of recent runs.
	 */
	public function run_catch_up_now() {
		if ( ! class_exists( 'RBCO_Publisher' ) ) {
			return;
		}
		$count = RBCO_Publisher::catch_up_overdue();
		update_option( 'rbco_last_catchup_run', time(), false );
		update_option( 'rbco_last_catchup_count', (int) $count, false );
	}

	/**
	 * Plugin activation.
	 */
	public function activate() {
		$defaults = array(
			'ai_provider'        => 'claude',
			'anthropic_api_key'  => '',
			'claude_model'       => 'claude-sonnet-4-6',
			'openai_api_key'     => '',
			'openai_model'       => 'gpt-4o',
			'max_pages_to_crawl' => 25,
			'max_context_chars'  => 18000,
			'request_timeout'    => 15,
		);

		foreach ( $defaults as $key => $value ) {
			if ( false === get_option( 'rbco_' . $key ) ) {
				update_option( 'rbco_' . $key, $value );
			}
		}

		// Ensure custom interval filter is registered before scheduling.
		add_filter( 'cron_schedules', array( $this, 'add_cron_interval' ) );

		// Clear any pre-existing event (previously registered as hourly) and
		// re-schedule using the 1-minute interval.
		$timestamp = wp_next_scheduled( 'rbco_catch_up_scheduled' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'rbco_catch_up_scheduled' );
		}
		wp_schedule_event( time() + 60, 'rbco_every_minute', 'rbco_catch_up_scheduled' );

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

		// Ensure the 1-minute cron event is registered. If an old hourly event
		// exists from a previous version, upgrade it to 1-minute.
		$next = wp_next_scheduled( 'rbco_catch_up_scheduled' );
		if ( ! $next ) {
			wp_schedule_event( time() + 60, 'rbco_every_minute', 'rbco_catch_up_scheduled' );
		} else {
			// Check if the schedule is still 'hourly' from an old version.
			$event = wp_get_scheduled_event( 'rbco_catch_up_scheduled' );
			if ( $event && isset( $event->schedule ) && 'hourly' === $event->schedule ) {
				wp_clear_scheduled_hook( 'rbco_catch_up_scheduled' );
				wp_schedule_event( time() + 60, 'rbco_every_minute', 'rbco_catch_up_scheduled' );
			}
		}
	}
}

RayBogman_Content_Orchestrator::get_instance();
