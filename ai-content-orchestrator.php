<?php
/**
 * Plugin Name:       AI Content Orchestrator
 * Plugin URI:        https://github.com/raybogman/ai-content-orchestrator
 * Description:       AI Content Orchestrator — End-to-end AI content pipeline for WordPress: website scanning, SEO, featured images (DALL-E 3 / Ideogram), LinkedIn auto-share, and Yoast integration. Supports Claude and OpenAI.
 * Version:           2.5.6
 * Requires at least: 5.8
 * Tested up to:      6.8
 * Requires PHP:      7.4
 * Author:            Ray Bogman
 * Author URI:        https://bogman.info
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ai-content-orchestrator
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AICC_VERSION', '2.5.6' );
define( 'AICC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AICC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AICC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Initialize Freemius SDK.
 */
if ( ! function_exists( 'aco_fs' ) ) {
	function aco_fs() {
		global $aco_fs;

		if ( ! isset( $aco_fs ) ) {
			require_once dirname( __FILE__ ) . '/vendor/freemius/start.php';

			$aco_fs = fs_dynamic_init( array(
				'id'                  => '28680',
				'slug'                => 'ai-content-orchestrator',
				'type'                => 'plugin',
				'public_key'          => 'pk_cbfd6cc1050eaf4acbcb63111d9b5',
				'is_premium'          => false,
				'has_premium_version' => true,
				'premium_suffix'      => 'Enterprise',
				'has_addons'          => false,
				'has_paid_plans'      => true,
				'is_org_compliant'    => true,
				'menu'                => array(
					'slug'    => 'ai-content-orchestrator',
					'support' => false,
					'account' => true,
					'pricing' => true,
				),
			) );
		}

		return $aco_fs;
	}

	aco_fs();
	do_action( 'aco_fs_loaded' );
}

/**
 * Helper: check if Pro plan is active.
 *
 * @return bool
 */
function aicc_is_pro() {
	return aco_fs()->is_plan( 'enterprise', true );
}

/**
 * Main plugin class.
 */
final class AI_Content_Orchestrator {

	/**
	 * Singleton instance.
	 *
	 * @var AI_Content_Orchestrator|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return AI_Content_Orchestrator
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
		require_once AICC_PLUGIN_DIR . 'includes/class-aicc-settings.php';
		require_once AICC_PLUGIN_DIR . 'includes/class-aicc-styles.php';
		require_once AICC_PLUGIN_DIR . 'includes/class-aicc-pdf-extractor.php';
		require_once AICC_PLUGIN_DIR . 'includes/class-aicc-pdf-library.php';
		require_once AICC_PLUGIN_DIR . 'includes/class-aicc-scanner.php';
		require_once AICC_PLUGIN_DIR . 'includes/class-aicc-generator.php';
		require_once AICC_PLUGIN_DIR . 'includes/class-aicc-publisher.php';
		require_once AICC_PLUGIN_DIR . 'includes/class-aicc-internal-linker.php';
		require_once AICC_PLUGIN_DIR . 'includes/class-aicc-repurposer.php';
		require_once AICC_PLUGIN_DIR . 'includes/class-aicc-gutenberg-converter.php';
		require_once AICC_PLUGIN_DIR . 'includes/class-aicc-thrive-converter.php';
		require_once AICC_PLUGIN_DIR . 'includes/class-aicc-image-overlay.php';
		require_once AICC_PLUGIN_DIR . 'includes/class-aicc-linkedin.php';
		require_once AICC_PLUGIN_DIR . 'includes/class-aicc-instagram.php';
		require_once AICC_PLUGIN_DIR . 'admin/class-aicc-admin.php';
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
		add_action( 'wp_ajax_aicc_verify_migration', array( $this, 'ajax_verify_migration' ) );
		add_action( 'wp_ajax_aicc_deactivate_old_plugin', array( $this, 'ajax_deactivate_old_plugin' ) );

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
		add_action( 'aicc_catch_up_scheduled', array( $this, 'run_catch_up_now' ) );
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
		if ( get_option( 'aicc_migration_verified', false ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || ( false === strpos( $screen->id, 'ai-content' ) && false === strpos( $screen->id, 'aicc' ) ) ) {
			return;
		}

		$old_active = is_plugin_active( 'ai-content-creator/ai-content-creator.php' );
		$has_settings = '' !== get_option( 'aicc_ai_provider', '' );

		if ( ! $has_settings && ! $old_active ) {
			return;
		}

		$nonce = wp_create_nonce( 'aicc_migration' );
		?>
		<div class="notice notice-info" id="aicc-migration-notice" style="padding: 12px 16px;">
			<p>
				<span class="dashicons dashicons-migrate" style="color: #2271b1; vertical-align: text-bottom; font-size: 20px; margin-right: 6px;"></span>
				<?php if ( $old_active ) : ?>
					<strong><?php esc_html_e( 'AI Content Creator is still active!', 'ai-content-orchestrator' ); ?></strong>
					<?php esc_html_e( 'Both plugins share the same settings — your configuration is already active here. You can safely deactivate the old plugin.', 'ai-content-orchestrator' ); ?>
				<?php else : ?>
					<strong><?php esc_html_e( 'Settings from AI Content Creator detected!', 'ai-content-orchestrator' ); ?></strong>
					<?php esc_html_e( 'Your previous configuration is automatically active — no migration needed.', 'ai-content-orchestrator' ); ?>
				<?php endif; ?>
			</p>
			<p style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
				<button type="button" class="button button-primary" id="aicc-verify-migration-btn">
					<span class="dashicons dashicons-yes" style="vertical-align:text-bottom;font-size:16px;width:16px;height:16px;margin-right:4px;"></span>
					<?php esc_html_e( 'Verify Settings', 'ai-content-orchestrator' ); ?>
				</button>
				<?php if ( $old_active ) : ?>
					<button type="button" class="button" id="aicc-deactivate-old-btn" style="color:#d63638;border-color:#d63638;">
						<span class="dashicons dashicons-no" style="vertical-align:text-bottom;font-size:16px;width:16px;height:16px;margin-right:4px;"></span>
						<?php esc_html_e( 'Deactivate AI Content Creator', 'ai-content-orchestrator' ); ?>
					</button>
				<?php endif; ?>
				<button type="button" class="button" id="aicc-dismiss-migration-btn">
					<?php esc_html_e( 'Dismiss', 'ai-content-orchestrator' ); ?>
				</button>
				<span id="aicc-migration-status"></span>
			</p>
		</div>
		<script>
		jQuery(document).ready(function($) {
			var nonce = '<?php echo esc_js( $nonce ); ?>';

			$('#aicc-verify-migration-btn').on('click', function() {
				var $btn = $(this);
				$btn.prop('disabled', true);
				$('#aicc-migration-status').html('<span class="spinner is-active" style="float:none;margin:0;"></span>');
				$.post(ajaxurl, { action: 'aicc_verify_migration', nonce: nonce }).done(function(r) {
					if (r.success) {
						$('#aicc-migration-notice').removeClass('notice-info').addClass('notice-success');
						$('#aicc-migration-notice p').first().html(
							'<span class="dashicons dashicons-yes-alt" style="color:#00a32a;vertical-align:text-bottom;font-size:20px;margin-right:6px;"></span><strong>' + r.data.message + '</strong>'
						);
						$btn.remove();
						$('#aicc-migration-status').empty();
						var tbl = '<table class="widefat striped" style="max-width:600px;margin-top:10px;"><tbody>';
						for (var k in r.data.settings) { tbl += '<tr><td style="font-weight:600;width:180px;">' + k + '</td><td>' + r.data.settings[k] + '</td></tr>'; }
						tbl += '</tbody></table>';
						$('#aicc-migration-notice').append(tbl);
					}
				}).always(function() { $btn.prop('disabled', false); });
			});

			$('#aicc-deactivate-old-btn').on('click', function() {
				if (!confirm('<?php echo esc_js( __( 'Deactivate AI Content Creator? Your settings will be preserved.', 'ai-content-orchestrator' ) ); ?>')) return;
				var $btn = $(this);
				$btn.prop('disabled', true).text('<?php echo esc_js( __( 'Deactivating...', 'ai-content-orchestrator' ) ); ?>');
				$.post(ajaxurl, { action: 'aicc_deactivate_old_plugin', nonce: nonce }).done(function(r) {
					if (r.success) {
						$btn.replaceWith('<span class="dashicons dashicons-yes-alt" style="color:#00a32a;vertical-align:text-bottom;"></span> <strong style="color:#00a32a;"><?php echo esc_js( __( 'AI Content Creator deactivated!', 'ai-content-orchestrator' ) ); ?></strong>');
					} else {
						$btn.prop('disabled', false).text('<?php echo esc_js( __( 'Deactivate AI Content Creator', 'ai-content-orchestrator' ) ); ?>');
						alert(r.data ? r.data.message : 'Failed.');
					}
				});
			});

			$('#aicc-dismiss-migration-btn').on('click', function() {
				$.post(ajaxurl, { action: 'aicc_verify_migration', nonce: nonce, dismiss: '1' });
				$('#aicc-migration-notice').fadeOut();
			});
		});
		</script>
		<?php
	}

	/**
	 * AJAX: Verify migration settings.
	 */
	public function ajax_verify_migration() {
		check_ajax_referer( 'aicc_migration', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions.' ) );
		}
		if ( ! empty( $_POST['dismiss'] ) ) {
			update_option( 'aicc_migration_verified', true );
			wp_send_json_success();
			return;
		}

		$settings = array();
		$provider = get_option( 'aicc_ai_provider', '' );
		$settings['AI Provider'] = $provider ? ucfirst( $provider ) : '—';

		$ck = get_option( 'aicc_anthropic_api_key', '' );
		$settings['Claude API Key'] = $ck ? '****' . substr( $ck, -4 ) : '—';

		$ok = get_option( 'aicc_openai_api_key', '' );
		$settings['OpenAI API Key'] = $ok ? '****' . substr( $ok, -4 ) : '—';

		$ip = get_option( 'aicc_image_provider', '' );
		$settings['Image Provider'] = $ip ? ucfirst( $ip ) : '—';

		$pv = get_option( 'aicc_project_vision', '' );
		$settings['Project Vision'] = ! empty( $pv ) ? substr( $pv, 0, 50 ) . ( strlen( $pv ) > 50 ? '...' : '' ) : '—';

		$li = get_option( 'aicc_linkedin_client_id', '' );
		$settings['LinkedIn'] = ! empty( $li ) ? 'Configured' : '—';

		$urls = get_option( 'aicc_saved_urls', array() );
		$settings['Saved URLs'] = is_array( $urls ) ? count( $urls ) . ' URL(s)' : '—';

		update_option( 'aicc_migration_verified', true );

		wp_send_json_success( array(
			'message'  => __( 'All settings verified! Your configuration is fully active in AI Content Orchestrator.', 'ai-content-orchestrator' ),
			'settings' => $settings,
		) );
	}

	/**
	 * AJAX: Deactivate the old AI Content Creator plugin.
	 */
	public function ajax_deactivate_old_plugin() {
		check_ajax_referer( 'aicc_migration', 'nonce' );
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions.' ) );
		}

		$old_plugin = 'ai-content-creator/ai-content-creator.php';
		if ( ! is_plugin_active( $old_plugin ) ) {
			wp_send_json_success( array( 'message' => 'Already deactivated.' ) );
			return;
		}

		deactivate_plugins( $old_plugin );
		update_option( 'aicc_migration_verified', true );

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
		if ( ! isset( $schedules['aicc_every_minute'] ) ) {
			$schedules['aicc_every_minute'] = array(
				'interval' => 60,
				'display'  => __( 'Every Minute (AI Content Orchestrator)', 'ai-content-orchestrator' ),
			);
		}
		return $schedules;
	}

	/**
	 * Rate-limited catch-up. Used for admin_init and init hooks (fires on every
	 * request). Limited to once per minute via transient.
	 */
	public function maybe_catch_up_scheduled() {
		$last_run = (int) get_transient( 'aicc_catch_up_last_run' );
		if ( $last_run && ( time() - $last_run ) < 60 ) {
			return;
		}
		set_transient( 'aicc_catch_up_last_run', time(), 300 );

		$this->run_catch_up_now();
	}

	/**
	 * Unconditional catch-up. Used for the WP cron event directly so manual
	 * triggers from WP Crontrol always execute regardless of recent runs.
	 */
	public function run_catch_up_now() {
		if ( ! class_exists( 'AICC_Publisher' ) ) {
			return;
		}
		$count = AICC_Publisher::catch_up_overdue();
		update_option( 'aicc_last_catchup_run', time(), false );
		update_option( 'aicc_last_catchup_count', (int) $count, false );
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
			if ( false === get_option( 'aicc_' . $key ) ) {
				update_option( 'aicc_' . $key, $value );
			}
		}

		// Ensure custom interval filter is registered before scheduling.
		add_filter( 'cron_schedules', array( $this, 'add_cron_interval' ) );

		// Clear any pre-existing event (previously registered as hourly) and
		// re-schedule using the 1-minute interval.
		$timestamp = wp_next_scheduled( 'aicc_catch_up_scheduled' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'aicc_catch_up_scheduled' );
		}
		wp_schedule_event( time() + 60, 'aicc_every_minute', 'aicc_catch_up_scheduled' );

		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation.
	 */
	public function deactivate() {
		wp_clear_scheduled_hook( 'aicc_catch_up_scheduled' );
		flush_rewrite_rules();
	}

	/**
	 * Fires on plugins_loaded.
	 */
	public function on_plugins_loaded() {
		if ( is_admin() ) {
			AICC_Admin::get_instance();
		}

		// Ensure the 1-minute cron event is registered. If an old hourly event
		// exists from a previous version, upgrade it to 1-minute.
		$next = wp_next_scheduled( 'aicc_catch_up_scheduled' );
		if ( ! $next ) {
			wp_schedule_event( time() + 60, 'aicc_every_minute', 'aicc_catch_up_scheduled' );
		} else {
			// Check if the schedule is still 'hourly' from an old version.
			$event = wp_get_scheduled_event( 'aicc_catch_up_scheduled' );
			if ( $event && isset( $event->schedule ) && 'hourly' === $event->schedule ) {
				wp_clear_scheduled_hook( 'aicc_catch_up_scheduled' );
				wp_schedule_event( time() + 60, 'aicc_every_minute', 'aicc_catch_up_scheduled' );
			}
		}
	}
}

AI_Content_Orchestrator::get_instance();
