<?php
/**
 * Admin functionality.
 *
 * @package AI_Content_Creator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AICC_Admin
 *
 * Handles admin menu pages, asset enqueuing, and AJAX endpoints.
 */
class AICC_Admin {

	/**
	 * Singleton instance.
	 *
	 * @var AICC_Admin|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return AICC_Admin
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
		add_action( 'admin_menu', array( $this, 'add_menu_pages' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_init', array( 'AICC_Settings', 'handle_save' ), 5 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		// AJAX handlers.
		add_action( 'wp_ajax_aicc_create_content', array( $this, 'ajax_create_content_step' ) );
		add_action( 'wp_ajax_aicc_get_categories', array( $this, 'ajax_get_categories' ) );
		add_action( 'wp_ajax_aicc_validate_api_key', array( $this, 'ajax_validate_api_key' ) );
		add_action( 'wp_ajax_aicc_approve_scheduled', array( $this, 'ajax_approve_scheduled' ) );
		add_action( 'wp_ajax_aicc_delete_scheduled', array( $this, 'ajax_delete_scheduled' ) );
		add_action( 'wp_ajax_aicc_reschedule', array( $this, 'ajax_reschedule' ) );
		add_action( 'wp_ajax_aicc_remove_saved_url', array( $this, 'ajax_remove_saved_url' ) );
		add_action( 'wp_ajax_aicc_upload_pdf', array( $this, 'ajax_upload_pdf' ) );
		add_action( 'wp_ajax_aicc_upload_pdf_chunk', array( $this, 'ajax_upload_pdf_chunk' ) );
		add_action( 'wp_ajax_aicc_delete_pdf', array( $this, 'ajax_delete_pdf' ) );
		add_action( 'wp_ajax_aicc_publish_now', array( $this, 'ajax_publish_now' ) );
		add_action( 'wp_ajax_aicc_run_catchup', array( $this, 'ajax_run_catchup' ) );
		add_action( 'wp_ajax_aicc_linkedin_disconnect', array( $this, 'ajax_linkedin_disconnect' ) );
		add_action( 'wp_ajax_aicc_linkedin_share_now', array( $this, 'ajax_linkedin_share_now' ) );
		add_action( 'wp_ajax_aicc_linkedin_save_commentary', array( $this, 'ajax_linkedin_save_commentary' ) );
		add_action( 'wp_ajax_aicc_linkedin_regenerate_commentary', array( $this, 'ajax_linkedin_regenerate_commentary' ) );
		add_action( 'wp_ajax_aicc_select_featured_image', array( $this, 'ajax_select_featured_image' ) );
		add_action( 'wp_ajax_aicc_regenerate_featured_images', array( $this, 'ajax_regenerate_featured_images' ) );
		add_action( 'wp_ajax_aicc_linkedin_remove_from_dashboard', array( $this, 'ajax_linkedin_remove_from_dashboard' ) );
		add_action( 'wp_ajax_aicc_linkedin_bulk_remove', array( $this, 'ajax_linkedin_bulk_remove' ) );
		add_action( 'wp_ajax_aicc_scan_theme_colors', array( $this, 'ajax_scan_theme_colors' ) );
		add_action( 'wp_ajax_aicc_regenerate_overlay', array( $this, 'ajax_regenerate_overlay' ) );
		add_action( 'wp_ajax_aicc_repurpose_content', array( $this, 'ajax_repurpose_content' ) );
		add_action( 'wp_ajax_aicc_suggest_topics', array( $this, 'ajax_suggest_topics' ) );
		add_action( 'wp_ajax_aicc_test_instagram', array( $this, 'ajax_test_instagram' ) );
		add_action( 'wp_ajax_aicc_analyze_post', array( $this, 'ajax_analyze_post' ) );
		add_action( 'wp_ajax_aicc_analyze_all_posts', array( $this, 'ajax_analyze_all_posts' ) );
		add_action( 'wp_ajax_aicc_refresh_post', array( $this, 'ajax_refresh_post' ) );

		// LinkedIn OAuth callback handler.
		add_action( 'admin_init', array( $this, 'handle_linkedin_callback' ) );

		// Auto-post to LinkedIn when a post transitions to 'publish'.
		add_action( 'transition_post_status', array( $this, 'maybe_share_to_linkedin' ), 10, 3 );

		// Auto-post to Instagram when a post transitions to 'publish'.
		add_action( 'transition_post_status', array( $this, 'maybe_share_to_instagram' ), 12, 3 );

		// Send email notification when a scheduled AI post is published.
		add_action( 'transition_post_status', array( $this, 'maybe_notify_published' ), 20, 3 );

		// Instagram OAuth callback handler.
		add_action( 'admin_init', array( $this, 'handle_instagram_callback' ) );

		// Plugin action links.
		add_filter( 'plugin_action_links_' . AICC_PLUGIN_BASENAME, array( $this, 'add_action_links' ) );
	}

	/**
	 * Add admin menu pages.
	 */
	public function add_menu_pages() {
		// Main menu — Dashboard is the landing page.
		add_menu_page(
			__( 'AI Content Orchestrator', 'ai-content-orchestrator' ),
			__( 'AI Content', 'ai-content-orchestrator' ),
			'edit_posts',
			'ai-content-orchestrator',
			array( $this, 'render_dashboard_page' ),
			'dashicons-edit-large',
			30
		);

		add_submenu_page(
			'ai-content-orchestrator',
			__( 'Dashboard', 'ai-content-orchestrator' ),
			__( 'Dashboard', 'ai-content-orchestrator' ),
			'edit_posts',
			'ai-content-orchestrator',
			array( $this, 'render_dashboard_page' )
		);

		add_submenu_page(
			'ai-content-orchestrator',
			__( 'Create Content', 'ai-content-orchestrator' ),
			__( 'Create Content', 'ai-content-orchestrator' ),
			'edit_posts',
			'aicc-create',
			array( $this, 'render_main_page' )
		);

		$pro_badge = aicc_is_pro() ? '' : ' <span style="background:#E4405F;color:#fff;padding:1px 6px;border-radius:8px;font-size:10px;font-weight:600;vertical-align:middle;">ENT</span>';

		add_submenu_page(
			'ai-content-orchestrator',
			__( 'Bulk Create', 'ai-content-orchestrator' ),
			__( 'Bulk Create', 'ai-content-orchestrator' ) . $pro_badge,
			'edit_posts',
			'aicc-bulk-create',
			array( $this, 'render_bulk_create_page' )
		);

		add_submenu_page(
			'ai-content-orchestrator',
			__( 'Refresh Content', 'ai-content-orchestrator' ),
			__( 'Refresh Content', 'ai-content-orchestrator' ) . $pro_badge,
			'edit_posts',
			'aicc-refresh',
			array( $this, 'render_refresh_page' )
		);

		// Scheduled / review queue submenu (with count badge).
		$scheduled_items = AICC_Publisher::get_scheduled_items();
		$pending_count   = 0;
		foreach ( $scheduled_items as $item ) {
			if ( $item['needs_review'] ) {
				$pending_count++;
			}
		}
		$scheduled_label = __( 'Scheduled', 'ai-content-orchestrator' );
		if ( $pending_count > 0 ) {
			$scheduled_label .= sprintf(
				' <span class="awaiting-mod count-%d"><span class="pending-count">%d</span></span>',
				$pending_count,
				$pending_count
			);
		}

		add_submenu_page(
			'ai-content-orchestrator',
			__( 'Scheduled', 'ai-content-orchestrator' ),
			$scheduled_label,
			'edit_posts',
			'aicc-scheduled',
			array( $this, 'render_scheduled_page' )
		);

		add_submenu_page(
			'ai-content-orchestrator',
			__( 'Settings', 'ai-content-orchestrator' ),
			__( 'Settings', 'ai-content-orchestrator' ),
			'manage_options',
			'aicc-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Render the Dashboard page.
	 */
	public function render_dashboard_page() {
		include AICC_PLUGIN_DIR . 'admin/views/dashboard-page.php';
	}

	/**
	 * Render the Bulk Create page.
	 */
	public function render_bulk_create_page() {
		if ( ! aicc_is_pro() ) {
			$this->render_pro_upgrade_page( __( 'Bulk Create', 'ai-content-orchestrator' ), __( 'Generate multiple blog posts at once with AI topic suggestions, auto blog style recommendations, and scheduled publishing.', 'ai-content-orchestrator' ) );
			return;
		}
		include AICC_PLUGIN_DIR . 'admin/views/bulk-create-page.php';
	}

	/**
	 * Render the Refresh Content page.
	 */
	public function render_refresh_page() {
		if ( ! aicc_is_pro() ) {
			$this->render_pro_upgrade_page( __( 'Refresh Content', 'ai-content-orchestrator' ), __( 'Analyze all published posts for SEO issues and refresh them with AI — fix thin content, add FAQ sections, update outdated posts.', 'ai-content-orchestrator' ) );
			return;
		}
		include AICC_PLUGIN_DIR . 'admin/views/refresh-page.php';
	}

	/**
	 * Render the scheduled/review page.
	 */
	public function render_scheduled_page() {
		include AICC_PLUGIN_DIR . 'admin/views/scheduled-page.php';
	}

	/**
	 * Render the style examples page.
	 */
	public function render_examples_page() {
		include AICC_PLUGIN_DIR . 'admin/views/examples-page.php';
	}

	/**
	 * Render a Pro upgrade prompt page for gated features.
	 */
	private function render_pro_upgrade_page( $feature_name, $description ) {
		$upgrade_url = aco_fs()->get_upgrade_url();
		?>
		<div class="wrap aicc-wrap">
			<h1 class="wp-heading-inline">
				<span class="dashicons dashicons-lock aicc-heading-icon"></span>
				<?php echo esc_html( sprintf( __( 'AI Content Orchestrator — %s', 'ai-content-orchestrator' ), $feature_name ) ); ?>
			</h1>
			<div class="aicc-card" style="max-width:700px; margin-top:20px;">
				<div class="aicc-card-body" style="text-align:center; padding:40px 30px;">
					<span class="dashicons dashicons-star-filled" style="font-size:48px; width:48px; height:48px; color:#E4405F;"></span>
					<h2 style="margin-top:16px;"><?php echo esc_html( $feature_name ); ?> <span style="background:#E4405F;color:#fff;padding:2px 10px;border-radius:10px;font-size:14px;vertical-align:middle;">ENT</span></h2>
					<p style="font-size:15px; color:#50575e; max-width:500px; margin:12px auto 24px;"><?php echo esc_html( $description ); ?></p>
					<a href="<?php echo esc_url( $upgrade_url ); ?>" class="button button-primary button-hero" style="background:#E4405F; border-color:#E4405F;">
						<span class="dashicons dashicons-star-filled" style="vertical-align:text-bottom; font-size:20px; width:20px; height:20px; margin-right:6px;"></span>
						<?php esc_html_e( 'Upgrade to Enterprise', 'ai-content-orchestrator' ); ?>
					</a>
					<p style="margin-top:16px; font-size:13px; color:#787c82;"><?php esc_html_e( 'Unlock all features including Bulk Create, Refresh Content, AI Images, Competitor Analysis, Content Repurposing, LinkedIn & Instagram sharing, and more.', 'ai-content-orchestrator' ); ?></p>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Register plugin settings.
	 */
	public function register_settings() {
		AICC_Settings::register();
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( false === strpos( $hook_suffix, 'ai-content-orchestrator' ) && false === strpos( $hook_suffix, 'aicc-' ) ) {
			return;
		}

		// Load WordPress media library modal (required for image/font selectors on Settings page).
		if ( false !== strpos( $hook_suffix, 'aicc-settings' ) ) {
			wp_enqueue_media();
		}

		wp_enqueue_style(
			'aicc-admin',
			AICC_PLUGIN_URL . 'admin/css/admin.css',
			array(),
			AICC_VERSION
		);

		wp_enqueue_script(
			'aicc-admin',
			AICC_PLUGIN_URL . 'admin/js/admin.js',
			array( 'jquery' ),
			AICC_VERSION,
			true
		);

		$categories  = AICC_Publisher::get_categories();
		$provider    = AICC_Settings::get_ai_provider();
		$blog_styles = AICC_Styles::get_styles_for_js();
		$pdf_library = AICC_PDF_Library::get_for_js();

		wp_localize_script( 'aicc-admin', 'aicc', array(
			'ajax_url'   => admin_url( 'admin-ajax.php' ),
			'nonce'      => wp_create_nonce( 'aicc_nonce' ),
			'provider'   => $provider,
			'model'      => AICC_Settings::get_active_model(),
			'configured' => AICC_Settings::is_configured(),
			'categories' => $categories,
			'saved_urls'  => AICC_Settings::get_saved_urls(),
			'blog_styles'  => $blog_styles,
			'pdf_library'  => $pdf_library,
			'has_yoast'           => defined( 'WPSEO_VERSION' ),
			'linkedin_connected'  => AICC_LinkedIn::is_connected(),
			'i18n'       => array(
				'working'          => __( 'Working...', 'ai-content-orchestrator' ),
				'create_content'   => __( 'Create Content', 'ai-content-orchestrator' ),
				'prompt_required'  => __( 'Please enter a prompt.', 'ai-content-orchestrator' ),
				'not_configured'   => __( 'Please configure your AI provider API key in Settings first.', 'ai-content-orchestrator' ),
				'error'            => __( 'Error', 'ai-content-orchestrator' ),
				'done'             => __( 'Done!', 'ai-content-orchestrator' ),
				'published'        => __( 'Published', 'ai-content-orchestrator' ),
				'draft'            => __( 'Draft', 'ai-content-orchestrator' ),
				'updated'          => __( 'Updated', 'ai-content-orchestrator' ),
				'not_available'    => __( 'Not available', 'ai-content-orchestrator' ),
				'request_failed'   => __( 'Request failed', 'ai-content-orchestrator' ),
			),
		) );
	}

	/**
	 * Add plugin action links.
	 *
	 * @param array $links Existing links.
	 * @return array Modified links.
	 */
	public function add_action_links( $links ) {
		$plugin_links = array(
			sprintf(
				'<a href="%s">%s</a>',
				admin_url( 'admin.php?page=aicc-create' ),
				__( 'Create Content', 'ai-content-orchestrator' )
			),
			sprintf(
				'<a href="%s">%s</a>',
				admin_url( 'admin.php?page=aicc-settings' ),
				__( 'Settings', 'ai-content-orchestrator' )
			),
		);
		return array_merge( $plugin_links, $links );
	}

	/**
	 * Render the main content creation page.
	 */
	public function render_main_page() {
		include AICC_PLUGIN_DIR . 'admin/views/main-page.php';
	}

	/**
	 * Render the settings page.
	 */
	public function render_settings_page() {
		include AICC_PLUGIN_DIR . 'admin/views/settings-page.php';
	}

	/**
	 * AJAX handler: Get WordPress categories.
	 */
	public function ajax_get_categories() {
		check_ajax_referer( 'aicc_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'ai-content-orchestrator' ) ) );
		}

		wp_send_json_success( array(
			'categories' => AICC_Publisher::get_categories(),
		) );
	}

	/**
	 * AJAX handler: Validate an API key by making a minimal test request.
	 */
	public function ajax_validate_api_key() {
		check_ajax_referer( 'aicc_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'ai-content-orchestrator' ) ) );
		}

		$provider = isset( $_POST['provider'] ) ? sanitize_text_field( wp_unslash( $_POST['provider'] ) ) : '';

		if ( 'claude' === $provider ) {
			$api_key = AICC_Settings::get_anthropic_api_key();
			if ( empty( $api_key ) ) {
				wp_send_json_error( array( 'message' => __( 'No Anthropic API key saved. Enter the key and click Save Changes first.', 'ai-content-orchestrator' ) ) );
			}

			$response = wp_remote_post( 'https://api.anthropic.com/v1/messages', array(
				'timeout' => 30,
				'headers' => array(
					'Content-Type'      => 'application/json',
					'x-api-key'         => $api_key,
					'anthropic-version'  => '2023-06-01',
				),
				'body' => wp_json_encode( array(
					'model'      => AICC_Settings::get_claude_model(),
					'max_tokens' => 10,
					'messages'   => array(
						array( 'role' => 'user', 'content' => 'Say "ok"' ),
					),
				) ),
			) );

		} elseif ( 'openai' === $provider ) {
			$api_key = AICC_Settings::get_openai_api_key();
			if ( empty( $api_key ) ) {
				wp_send_json_error( array( 'message' => __( 'No OpenAI API key saved. Enter the key and click Save Changes first.', 'ai-content-orchestrator' ) ) );
			}

			$response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', array(
				'timeout' => 30,
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $api_key,
				),
				'body' => wp_json_encode( array(
					'model'      => AICC_Settings::get_openai_model(),
					'max_tokens' => 10,
					'messages'   => array(
						array( 'role' => 'user', 'content' => 'Say "ok"' ),
					),
				) ),
			) );

		} elseif ( 'ideogram' === $provider ) {
			$api_key = AICC_Settings::get_ideogram_api_key();
			if ( empty( $api_key ) ) {
				wp_send_json_error( array( 'message' => __( 'No Ideogram API key saved. Enter the key and click Save Changes first.', 'ai-content-orchestrator' ) ) );
			}

			// Ideogram v3 uses multipart/form-data. Send a minimal generate request.
			$boundary = wp_generate_password( 24, false );
			$body     = '';
			$fields   = array(
				'prompt'          => 'A simple blue circle on a white background',
				'aspect_ratio'    => '1x1',
				'rendering_speed' => 'FLASH',
				'num_images'      => '1',
			);
			foreach ( $fields as $name => $value ) {
				$body .= '--' . $boundary . "\r\n";
				$body .= 'Content-Disposition: form-data; name="' . $name . '"' . "\r\n\r\n";
				$body .= $value . "\r\n";
			}
			$body .= '--' . $boundary . '--' . "\r\n";

			$response = wp_remote_post( AICC_Generator::IDEOGRAM_IMAGE_URL, array(
				'timeout' => 60,
				'headers' => array(
					'Content-Type' => 'multipart/form-data; boundary=' . $boundary,
					'Api-Key'      => $api_key,
				),
				'body'    => $body,
			) );

		} else {
			wp_send_json_error( array( 'message' => __( 'Invalid provider.', 'ai-content-orchestrator' ) ) );
			return;
		}

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( array(
				'message' => sprintf( 'Connection failed: %s', $response->get_error_message() ),
			) );
			return;
		}

		$code      = wp_remote_retrieve_response_code( $response );
		$resp_body = wp_remote_retrieve_body( $response );
		$data      = json_decode( $resp_body, true );

		if ( 401 === $code ) {
			wp_send_json_error( array( 'message' => __( 'Invalid API key (HTTP 401 Unauthorized).', 'ai-content-orchestrator' ) ) );
		} elseif ( 403 === $code ) {
			wp_send_json_error( array( 'message' => __( 'Access denied (HTTP 403 Forbidden). Check your API key permissions.', 'ai-content-orchestrator' ) ) );
		} elseif ( $code >= 200 && $code < 300 ) {
			$model_used = '';
			if ( is_array( $data ) && isset( $data['model'] ) ) {
				$model_used = $data['model'];
			}
			if ( 'ideogram' === $provider ) {
				wp_send_json_success( array(
					'message' => __( 'Connection successful! Ideogram is working.', 'ai-content-orchestrator' ),
				) );
			} else {
				wp_send_json_success( array(
					'message' => sprintf(
						__( 'Connection successful! Using model: %s', 'ai-content-orchestrator' ),
						$model_used
					),
				) );
			}
		} else {
			$error_msg = '';
			if ( is_array( $data ) && isset( $data['error']['message'] ) ) {
				$error_msg = $data['error']['message'];
			} elseif ( is_array( $data ) && isset( $data['message'] ) ) {
				$error_msg = $data['message'];
			} else {
				$error_msg = mb_substr( $resp_body, 0, 300 );
			}
			wp_send_json_error( array(
				'message' => sprintf( 'API error (HTTP %d): %s', $code, $error_msg ),
			) );
		}
	}

	/**
	 * Register a shutdown handler that catches fatal errors during AJAX
	 * and returns them as JSON so the browser can display them.
	 *
	 * @param array $log_messages Reference to log messages array.
	 */
	private static function register_fatal_error_handler( &$log_messages ) {
		register_shutdown_function( function () use ( &$log_messages ) {
			$error = error_get_last();
			if ( null === $error ) {
				return;
			}
			// Only handle fatal errors.
			$fatal_types = array( E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR );
			if ( ! in_array( $error['type'], $fatal_types, true ) ) {
				return;
			}

			// Clear any output that WordPress's error handler may have sent.
			if ( ob_get_length() ) {
				ob_end_clean();
			}

			$log_messages[] = sprintf(
				'PHP Fatal Error: %s in %s on line %d',
				$error['message'],
				basename( $error['file'] ),
				$error['line']
			);

			$response = array(
				'success' => false,
				'data'    => array(
					'message' => sprintf( 'PHP Fatal Error: %s', $error['message'] ),
					'log'     => $log_messages,
					'debug'   => sprintf(
						'%s in %s:%d | PHP %s | memory_limit=%s | max_execution_time=%s',
						$error['message'],
						$error['file'],
						$error['line'],
						PHP_VERSION,
						ini_get( 'memory_limit' ),
						ini_get( 'max_execution_time' )
					),
				),
			);

			header( 'Content-Type: application/json; charset=utf-8' );
			header( 'HTTP/1.1 200 OK' );
			echo wp_json_encode( $response );
			exit;
		} );
	}

	/**
	 * Multi-step AJAX handler: breaks the pipeline into 4 short requests
	 * so it works within any hosting timeout (typically 60 seconds).
	 *
	 * Step 1: Scan website(s)       — ~30-60s
	 * Step 2: Generate SEO metadata — ~10-30s (single AI call)
	 * Step 3: Generate HTML content — ~15-60s (single AI call)
	 * Step 4: Publish to WordPress  — <5s
	 *
	 * Intermediate data is stored in a transient between steps.
	 */
	public function ajax_create_content_step() {
		check_ajax_referer( 'aicc_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'ai-content-orchestrator' ) ) );
		}

		@set_time_limit( 120 );
		@ini_set( 'memory_limit', '256M' );

		$step   = isset( $_POST['step'] ) ? absint( $_POST['step'] ) : 1;
		$job_id = isset( $_POST['job_id'] ) ? sanitize_text_field( wp_unslash( $_POST['job_id'] ) ) : '';

		$log_messages = array();
		$log_callback = function ( $message ) use ( &$log_messages ) {
			$log_messages[] = $message;
		};

		self::register_fatal_error_handler( $log_messages );

		try {
			switch ( $step ) {

				// ── Step 1: Initialize + Scan ───────────────────────
				case 1:
					$content_type = isset( $_POST['content_type'] ) ? sanitize_text_field( wp_unslash( $_POST['content_type'] ) ) : 'blog';
					$url          = isset( $_POST['url'] ) ? sanitize_text_field( wp_unslash( $_POST['url'] ) ) : '';
					$prompt       = isset( $_POST['prompt'] ) ? sanitize_textarea_field( wp_unslash( $_POST['prompt'] ) ) : '';
					$status       = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : 'draft';
					$category_ids = isset( $_POST['categories'] ) ? array_map( 'absint', (array) $_POST['categories'] ) : array();
					$schedule_at  = isset( $_POST['schedule_at'] ) ? self::parse_datetime_local( sanitize_text_field( wp_unslash( $_POST['schedule_at'] ) ) ) : 0;
					$blog_style   = isset( $_POST['blog_style'] ) ? sanitize_text_field( wp_unslash( $_POST['blog_style'] ) ) : 'standard';
					$pdf_ids      = isset( $_POST['pdf_ids'] ) ? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['pdf_ids'] ) ) : array();
					$save_url     = isset( $_POST['save_url'] ) && '1' === $_POST['save_url'];
					$linkedin     = isset( $_POST['linkedin'] ) && '1' === $_POST['linkedin'];
					$instagram    = isset( $_POST['instagram'] ) && '1' === $_POST['instagram'];
					$generate_image = isset( $_POST['generate_image'] ) && '1' === $_POST['generate_image'];
					$internal_linking    = ( isset( $_POST['internal_linking'] ) && '1' === $_POST['internal_linking'] ) ? '1' : '0';
					$competitor_analysis = isset( $_POST['competitor_analysis'] ) && '1' === $_POST['competitor_analysis'];
					$output_format       = isset( $_POST['output_format'] ) ? sanitize_text_field( wp_unslash( $_POST['output_format'] ) ) : 'wordpress';
					if ( ! in_array( $output_format, array( 'wordpress', 'thrive' ), true ) ) {
						$output_format = 'wordpress';
					}

					if ( empty( $prompt ) ) {
						wp_send_json_error( array( 'message' => __( 'Prompt is required.', 'ai-content-orchestrator' ) ) );
					}
					if ( ! AICC_Settings::is_configured() ) {
						wp_send_json_error( array( 'message' => __( 'API key is not configured.', 'ai-content-orchestrator' ) ) );
					}

					// Save URL if requested.
					if ( $save_url && ! empty( $url ) ) {
						foreach ( array_filter( array_map( 'trim', explode( ',', $url ) ) ) as $u ) {
							AICC_Settings::save_url( $u );
						}
					}

					// Generate a unique job ID.
					$job_id = 'aicc_' . wp_generate_uuid4();

					$log_callback( sprintf(
						'Step 1/4: Scanning | PHP %s | Provider: %s (%s)',
						PHP_VERSION,
						ucfirst( AICC_Settings::get_ai_provider() ),
						AICC_Settings::get_active_model()
					) );

					// Scan website(s).
					$all_site_data = array();
					if ( ! empty( $url ) ) {
						$urls    = array_filter( array_map( 'trim', explode( ',', $url ) ) );
						$scanner = new AICC_Scanner( $log_callback );
						foreach ( $urls as $scan_url ) {
							$data = $scanner->scan( esc_url_raw( $scan_url ) );
							$all_site_data = array_merge( $all_site_data, $data );
						}
					} else {
						$log_callback( 'No URL provided — skipping website scan.' );
					}

					// Add PDF sources.
					if ( ! empty( $pdf_ids ) ) {
						$pdf_data      = AICC_PDF_Library::get_as_site_data( $pdf_ids );
						$all_site_data = array_merge( $all_site_data, $pdf_data );
						$log_callback( sprintf( 'Added %d PDF source(s) as context.', count( $pdf_data ) ) );
					}

					// Store job data in transient (30 min expiry).
					$job_data = array(
						'content_type'   => $content_type,
						'prompt'         => $prompt,
						'status'         => $status,
						'category_ids'   => $category_ids,
						'schedule_at'    => $schedule_at,
						'blog_style'     => $blog_style,
						'site_data'      => $all_site_data,
						'linkedin'       => $linkedin,
						'instagram'      => $instagram,
						'generate_image'      => $generate_image,
						'internal_linking'    => $internal_linking,
						'competitor_analysis' => $competitor_analysis,
						'output_format'       => $output_format,
					);
					set_transient( $job_id, $job_data, 1800 );

					wp_send_json_success( array(
						'step'      => 1,
						'job_id'    => $job_id,
						'next_step' => 2,
						'log'       => $log_messages,
					) );
					break;

				// ── Step 2: Generate SEO metadata ───────────────────
				case 2:
					if ( empty( $job_id ) ) {
						wp_send_json_error( array( 'message' => 'Missing job_id.' ) );
					}
					$job_data = get_transient( $job_id );
					if ( ! $job_data ) {
						wp_send_json_error( array( 'message' => 'Job expired. Please try again.' ) );
					}

					$log_callback( 'Step 2/4: Generating SEO metadata with AI...' );

					$project_vision = AICC_Settings::get_project_vision();
					if ( ! empty( $project_vision ) ) {
						$preview = strlen( $project_vision ) > 100 ? substr( $project_vision, 0, 100 ) . '...' : $project_vision;
						$log_callback( sprintf( 'Project Vision: %s', $preview ) );
						$log_callback( sprintf( 'Prompt: %s', $job_data['prompt'] ) );
					}

					$generator     = new AICC_Generator( $log_callback );
					$context_block = $generator->build_context_block( $job_data['prompt'], $job_data['content_type'], $job_data['site_data'] );

					$existing_categories = AICC_Publisher::get_categories();
					$category_names      = wp_list_pluck( $existing_categories, 'name' );

					$meta = $generator->generate_metadata( $context_block, $category_names );
					$log_callback( sprintf( 'SEO Title: %s', isset( $meta['seo_title'] ) ? $meta['seo_title'] : '?' ) );
					$log_callback( sprintf( 'Slug: %s', isset( $meta['slug'] ) ? $meta['slug'] : '?' ) );

					// Competitor gap analysis — enriches the context for Step 3.
					if ( ! empty( $job_data['competitor_analysis'] ) && ! empty( $meta['focus_keyphrase'] ) ) {
						$log_callback( sprintf( 'Analyzing competitor content for "%s"...', $meta['focus_keyphrase'] ) );
						try {
							$gap_prompt = sprintf(
								'You are an SEO content strategist. For the keyword "%s", list the topics, questions, and angles that the top-ranking articles on Google typically cover. Then identify 2-3 GAPS — topics or questions that competitors miss but searchers want answered. Output a concise bullet list (no explanation). Write in the same language as this keyword.',
								$meta['focus_keyphrase']
							);
							$gap_analysis = $generator->build_context_block( $gap_prompt, 'blog', array() );
							$gaps = trim( $generator->generate_metadata( $gap_analysis, array() )['meta_description'] ?? '' );

							// Use reflection to call AI directly for the gap analysis.
							$gap_method = new \ReflectionMethod( $generator, 'call_ai' );
							$gap_method->setAccessible( true );
							$gaps = $gap_method->invoke( $generator, 'You are an SEO competitor analyst. Output ONLY a bullet list — no introduction, no conclusion.', $gap_prompt, 512 );
							$gaps = trim( $gaps );

							if ( ! empty( $gaps ) ) {
								$context_block .= "\n\nCOMPETITOR GAP ANALYSIS (cover these topics that competitors miss):\n" . $gaps;
								$log_callback( 'Competitor gaps identified:' );
								$gap_lines = explode( "\n", $gaps );
								foreach ( array_slice( $gap_lines, 0, 8 ) as $gl ) {
									$gl = trim( $gl );
									if ( ! empty( $gl ) ) {
										$log_callback( '  ' . $gl );
									}
								}
							}
						} catch ( \Throwable $e ) {
							$log_callback( sprintf( 'Competitor analysis failed (non-critical): %s', $e->getMessage() ) );
						}
					}

					// Store metadata in job.
					$job_data['meta']          = $meta;
					$job_data['context_block'] = $context_block;
					set_transient( $job_id, $job_data, 1800 );

					wp_send_json_success( array(
						'step'      => 2,
						'job_id'    => $job_id,
						'next_step' => 3,
						'log'       => $log_messages,
					) );
					break;

				// ── Step 3: Generate HTML content ───────────────────
				case 3:
					if ( empty( $job_id ) ) {
						wp_send_json_error( array( 'message' => 'Missing job_id.' ) );
					}
					$job_data = get_transient( $job_id );
					if ( ! $job_data || empty( $job_data['meta'] ) ) {
						wp_send_json_error( array( 'message' => 'Job expired or metadata missing. Please try again.' ) );
					}

					$log_callback( 'Step 3/4: Generating SEO content with AI...' );

					$generator    = new AICC_Generator( $log_callback );
					$blog_style   = isset( $job_data['blog_style'] ) ? $job_data['blog_style'] : 'standard';
					$html_content = $generator->generate_content( $job_data['context_block'], $job_data['content_type'], $job_data['meta'], $blog_style );
					$log_callback( sprintf( 'Content generated: %d characters', strlen( $html_content ) ) );

					// Auto-add internal links to related published posts.
					$do_linking = false;
					if ( isset( $job_data['internal_linking'] ) ) {
						$do_linking = in_array( $job_data['internal_linking'], array( true, '1', 1 ), true );
					} else {
						$do_linking = AICC_Settings::is_internal_linking_enabled();
					}
					$log_callback( sprintf( 'Internal linking: %s', $do_linking ? 'enabled' : 'disabled' ) );
					if ( $do_linking ) {
						$log_callback( 'Scanning your site for internal linking opportunities...' );
						$link_result = AICC_Internal_Linker::add_links(
							$html_content,
							$job_data['meta'],
							AICC_Settings::get_max_internal_links(),
							0,
							$log_callback
						);
						$html_content = $link_result['html'];
						if ( $link_result['links_added'] > 0 ) {
							$log_callback( sprintf( 'Added %d internal links:', $link_result['links_added'] ) );
							foreach ( $link_result['linked_posts'] as $lp ) {
								$log_callback( sprintf( '  → %s', $lp['title'] ) );
							}
							$job_data['linked_posts'] = $link_result['linked_posts'];
						} else {
							$log_callback( 'No relevant published posts found for internal linking.' );
						}
					}

					// Store content in job.
					$job_data['html_content'] = $html_content;

					// If LinkedIn sharing is enabled, also generate a LinkedIn post summary.
					if ( ! empty( $job_data['linkedin'] ) ) {
						try {
							$log_callback( 'Generating LinkedIn post summary...' );
							$linkedin_commentary = $generator->generate_linkedin_post(
								$html_content,
								$job_data['meta'],
								$blog_style
							);
							$job_data['linkedin_commentary'] = $linkedin_commentary;
							$log_callback( sprintf( 'LinkedIn post generated: %d characters', mb_strlen( $linkedin_commentary ) ) );
						} catch ( \Throwable $e ) {
							$log_callback( sprintf( 'LinkedIn summary generation failed: %s (will fall back to meta description)', $e->getMessage() ) );
						}
					}

					// If Instagram sharing is enabled, generate an Instagram caption.
					if ( ! empty( $job_data['instagram'] ) ) {
						try {
							$log_callback( 'Generating Instagram caption...' );
							$ig_ref = new \ReflectionMethod( 'AICC_Repurposer', 'generate_instagram' );
							$ig_ref->setAccessible( true );
							$ig_caption = $ig_ref->invoke( null, $generator, wp_strip_all_tags( $html_content ), $job_data['meta'] );
							$job_data['instagram_caption'] = $ig_caption;
							$log_callback( sprintf( 'Instagram caption generated: %d characters', mb_strlen( $ig_caption ) ) );
						} catch ( \Throwable $e ) {
							$log_callback( sprintf( 'Instagram caption generation failed: %s', $e->getMessage() ) );
						}
					}

					// If featured image generation is enabled, generate 4 options.
					if ( ! empty( $job_data['generate_image'] ) ) {
						try {
							$log_callback( 'Generating 4 diverse image prompts with AI (analyzing blog content)...' );
							$image_prompts = $generator->generate_image_prompts( $job_data['meta'], $html_content, $blog_style );
							$approaches    = array( 'photographic', 'conceptual', 'illustrative', 'cinematic' );
							foreach ( $image_prompts as $idx => $ip ) {
								$approach = isset( $approaches[ $idx ] ) ? $approaches[ $idx ] : ( $idx + 1 );
								$log_callback( sprintf( 'Prompt %d (%s): %s', $idx + 1, $approach, mb_substr( $ip, 0, 100 ) . ( mb_strlen( $ip ) > 100 ? '...' : '' ) ) );
							}

							$image_provider  = AICC_Settings::get_image_provider();
							$provider_labels = array( 'openai' => 'DALL-E 3', 'ideogram' => 'Ideogram' );
							$provider_label  = isset( $provider_labels[ $image_provider ] ) ? $provider_labels[ $image_provider ] : $image_provider;
							$log_callback( sprintf( 'Generating 4 image options with %s (this may take 1-2 minutes)...', $provider_label ) );
							$image_urls = $generator->generate_images( $image_prompts, 4, $blog_style );
							if ( ! empty( $image_urls ) ) {
								$job_data['image_urls']    = $image_urls;
								$job_data['image_prompt']  = $image_prompts[0]; // Primary prompt for post meta.
								$job_data['image_prompts'] = $image_prompts;    // All 4 prompts.
								$log_callback( sprintf( '%d image options generated.', count( $image_urls ) ) );
							} else {
								$log_callback( 'No images were generated successfully.' );
							}
						} catch ( \Throwable $e ) {
							$log_callback( sprintf( 'Image generation failed: %s', $e->getMessage() ) );
						}
					}

					set_transient( $job_id, $job_data, 1800 );

					wp_send_json_success( array(
						'step'      => 3,
						'job_id'    => $job_id,
						'next_step' => 4,
						'log'       => $log_messages,
					) );
					break;

				// ── Step 4: Publish to WordPress ────────────────────
				case 4:
					if ( empty( $job_id ) ) {
						wp_send_json_error( array( 'message' => 'Missing job_id.' ) );
					}
					$job_data = get_transient( $job_id );
					if ( ! $job_data || empty( $job_data['html_content'] ) ) {
						wp_send_json_error( array( 'message' => 'Job expired or content missing. Please try again.' ) );
					}

					$log_callback( 'Step 4/4: Publishing to WordPress...' );

					$meta = $job_data['meta'];
					$ai_result = array(
						'seo_title'        => isset( $meta['seo_title'] ) ? $meta['seo_title'] : 'New Post',
						'meta_description' => isset( $meta['meta_description'] ) ? $meta['meta_description'] : '',
						'slug'             => isset( $meta['slug'] ) ? $meta['slug'] : 'new-post',
						'focus_keyphrase'  => isset( $meta['focus_keyphrase'] ) ? $meta['focus_keyphrase'] : '',
						'tags'             => isset( $meta['tags'] ) ? $meta['tags'] : array(),
						'categories'       => isset( $meta['categories'] ) ? $meta['categories'] : array(),
						'content'              => $job_data['html_content'],
						'project_vision'       => AICC_Settings::get_project_vision(),
						'prompt'               => $job_data['prompt'],
						'linkedin_requested'   => ! empty( $job_data['linkedin'] ),
						'linkedin_commentary'  => isset( $job_data['linkedin_commentary'] ) ? $job_data['linkedin_commentary'] : '',
						'image_urls'           => isset( $job_data['image_urls'] ) ? $job_data['image_urls'] : array(),
						'image_prompt'         => isset( $job_data['image_prompt'] ) ? $job_data['image_prompt'] : '',
						'linked_posts'         => isset( $job_data['linked_posts'] ) ? $job_data['linked_posts'] : array(),
					);

					$category_ids = $job_data['category_ids'];
					$status       = $job_data['status'];
					$schedule_at  = $job_data['schedule_at'];

					// If user selected categories, replace AI-suggested ones.
					if ( ! empty( $category_ids ) ) {
						$selected_names = array();
						foreach ( $category_ids as $cat_id ) {
							$term = get_term( $cat_id, 'category' );
							if ( $term && ! is_wp_error( $term ) ) {
								$selected_names[] = $term->name;
							}
						}
						$ai_result['categories'] = $selected_names;
						$log_callback( sprintf( 'Using %d user-selected categories: %s', count( $selected_names ), implode( ', ', $selected_names ) ) );
					}

					// Convert content to the requested output format.
					$output_format  = isset( $job_data['output_format'] ) ? $job_data['output_format'] : 'wordpress';
					$thrive_content = ''; // Populated only when Thrive mode is selected.
					if ( 'thrive' === $output_format ) {
						$log_callback( 'Converting content to Thrive Architect compatible markup...' );
						$thrive_content = AICC_Thrive_Converter::convert( $ai_result['content'] );

						// Insert TOC block after intro paragraph if configured.
						$toc_id = (int) AICC_Settings::get_thrive_toc_id();
						if ( $toc_id > 0 ) {
							$log_callback( sprintf( 'Inserting Thrive TOC block #%d after introduction...', $toc_id ) );
							$thrive_content = AICC_Thrive_Converter::insert_toc_after_intro( $thrive_content );
						}

						// Append CTA heading + block if configured.
						$cta_id = (int) AICC_Settings::get_thrive_cta_symbol_id();
						if ( $cta_id > 0 ) {
							$log_callback( sprintf( 'Appending Thrive CTA library item #%d after content...', $cta_id ) );
							$thrive_content = AICC_Thrive_Converter::append_cta( $thrive_content );
						}

						// Store the Thrive-wrapped content in post_content too, so the post
						// renders correctly even if Thrive is deactivated.
						$ai_result['content'] = $thrive_content;
					}

					// Log schedule info.
					if ( $schedule_at > 0 ) {
						$log_callback( sprintf( 'Publishing %s (status: %s, scheduled for: %s)...', $job_data['content_type'], $status, wp_date( 'Y-m-d H:i', $schedule_at ) ) );
					} else {
						$log_callback( sprintf( 'Publishing %s to WordPress as %s...', $job_data['content_type'], $status ) );
					}

					$wp_result = AICC_Publisher::create( $job_data['content_type'], $ai_result, $status, $category_ids, $schedule_at );

					// If Thrive mode, set the Thrive-specific post meta so the post
					// opens in Thrive Architect with the content preloaded.
					if ( $wp_result['success'] && 'thrive' === $output_format && ! empty( $thrive_content ) ) {
						AICC_Thrive_Converter::set_thrive_meta( $wp_result['id'], $thrive_content );
						update_post_meta( $wp_result['id'], '_aicc_output_format', 'thrive' );
						$log_callback( 'Thrive Architect meta keys set (tve_updated_post, tve_editor_enabled).' );
					}

					if ( $wp_result['success'] ) {
						$log_callback( sprintf( 'Created %s #%d: %s', $job_data['content_type'], $wp_result['id'], $wp_result['title'] ) );

						// Attach AI-generated featured image if we have one (first of 4).
						if ( ! empty( $job_data['image_urls'] ) && is_array( $job_data['image_urls'] ) ) {
							$first_url = $job_data['image_urls'][0];
							$log_callback( 'Downloading and attaching first image as featured...' );
							$attach_result = AICC_Publisher::attach_image_from_url(
								$wp_result['id'],
								$first_url,
								$wp_result['title']
							);
							if ( is_wp_error( $attach_result ) ) {
								$log_callback( sprintf( 'Featured image attach failed: %s', $attach_result->get_error_message() ) );
							} else {
								$log_callback( sprintf( 'Featured image attached (attachment #%d).', $attach_result ) );
								if ( ! empty( $job_data['image_prompt'] ) ) {
									update_post_meta( $wp_result['id'], '_aicc_image_prompt', $job_data['image_prompt'] );
								}
								if ( ! empty( $job_data['image_prompts'] ) ) {
									update_post_meta( $wp_result['id'], '_aicc_image_prompts', $job_data['image_prompts'] );
								}
								// Store all 4 URLs so user can switch via the result view.
								// Note: OpenAI/Ideogram image URLs expire ~1 hour after generation.
								update_post_meta( $wp_result['id'], '_aicc_image_options', $job_data['image_urls'] );
								update_post_meta( $wp_result['id'], '_aicc_image_selected', 0 );
							}
						}

						// If no AI images were generated, use the default featured image (with optional title overlay).
						if ( empty( $job_data['image_urls'] ) || ! is_array( $job_data['image_urls'] ) || empty( $job_data['image_urls'] ) ) {
							$default_image_id = AICC_Settings::get_default_featured_image();
							if ( $default_image_id > 0 ) {
								if ( AICC_Settings::is_overlay_enabled() ) {
									$log_callback( 'Creating featured image with title overlay...' );
									$overlay_result = AICC_Image_Overlay::create_and_attach(
										$wp_result['id'],
										$default_image_id,
										isset( $meta['seo_title'] ) ? $meta['seo_title'] : $wp_result['title'],
										array(
											'color'       => AICC_Settings::get_overlay_text_color(),
											'font_bold'   => AICC_Settings::get_overlay_font_bold_path(),
											'font_italic' => AICC_Settings::get_overlay_font_italic_path(),
										)
									);
									if ( is_wp_error( $overlay_result ) ) {
										$log_callback( sprintf( 'Overlay image failed: %s. Using base image as fallback.', $overlay_result->get_error_message() ) );
										set_post_thumbnail( $wp_result['id'], $default_image_id );
									} else {
										$log_callback( sprintf( 'Featured image with title overlay attached (attachment #%d).', $overlay_result ) );
										$overlay_title = isset( $meta['seo_title'] ) ? $meta['seo_title'] : $wp_result['title'];
										$auto_lines    = AICC_Image_Overlay::get_split_title( $overlay_title );
										update_post_meta( $wp_result['id'], '_aicc_overlay_line1', $auto_lines[0] );
										update_post_meta( $wp_result['id'], '_aicc_overlay_line2', $auto_lines[1] );
									}
								} else {
									set_post_thumbnail( $wp_result['id'], $default_image_id );
									$log_callback( 'Default featured image set.' );
								}
							}
						}

						// Store LinkedIn flag and commentary on the post.
						if ( ! empty( $job_data['linkedin'] ) ) {
							update_post_meta( $wp_result['id'], '_aicc_post_to_linkedin', '1' );
							if ( ! empty( $job_data['linkedin_commentary'] ) ) {
								update_post_meta( $wp_result['id'], '_aicc_linkedin_commentary', $job_data['linkedin_commentary'] );
							}
							// Store blog style for future regeneration of LinkedIn post.
							if ( ! empty( $job_data['blog_style'] ) ) {
								update_post_meta( $wp_result['id'], '_aicc_blog_style', $job_data['blog_style'] );
							}
							$log_callback( 'LinkedIn sharing enabled for this post.' );
						}

						// Store Instagram flag and caption on the post.
						if ( ! empty( $job_data['instagram'] ) ) {
							update_post_meta( $wp_result['id'], '_aicc_post_to_instagram', '1' );
							if ( ! empty( $job_data['instagram_caption'] ) ) {
								update_post_meta( $wp_result['id'], '_aicc_instagram_caption', $job_data['instagram_caption'] );
							}
							$log_callback( 'Instagram sharing enabled for this post.' );
						}

						if ( ! empty( $wp_result['needs_review'] ) ) {
							$log_callback( 'Added to Scheduled review queue. Approve it from AI Content > Scheduled.' );
						} elseif ( 'future' === $wp_result['status'] ) {
							$log_callback( sprintf( 'Scheduled for publication on %s.', wp_date( 'Y-m-d H:i', $wp_result['scheduled_at'] ) ) );
						}
						if ( $wp_result['yoast'] ) {
							$log_callback( 'Yoast SEO fields updated (title, meta description, focus keyphrase).' );
						}
					}

					// Include the WordPress-hosted featured image URL if set.
					if ( $wp_result['success'] && has_post_thumbnail( $wp_result['id'] ) ) {
						$wp_result['featured_image'] = get_the_post_thumbnail_url( $wp_result['id'], 'large' );

						// Include overlay text lines so the result view can show edit fields.
						$overlay_l1 = get_post_meta( $wp_result['id'], '_aicc_overlay_line1', true );
						if ( '' !== $overlay_l1 ) {
							$wp_result['overlay_line1'] = $overlay_l1;
							$wp_result['overlay_line2'] = get_post_meta( $wp_result['id'], '_aicc_overlay_line2', true );
						}
					}

					// Check if LinkedIn sharing happened (for immediate publish).
					if ( $wp_result['success'] && ! empty( $job_data['linkedin'] ) ) {
						$linkedin_shared = get_post_meta( $wp_result['id'], '_aicc_linkedin_shared', true );
						$linkedin_error  = get_post_meta( $wp_result['id'], '_aicc_linkedin_error', true );
						if ( $linkedin_shared ) {
							$wp_result['linkedin'] = 'Shared to LinkedIn';
							$log_callback( 'Content shared to LinkedIn successfully.' );
						} elseif ( $linkedin_error ) {
							$wp_result['linkedin'] = false;
							$log_callback( sprintf( 'LinkedIn sharing failed: %s', $linkedin_error ) );
						} elseif ( 'publish' !== $wp_result['status'] ) {
							$wp_result['linkedin'] = false;
							$log_callback( 'LinkedIn sharing will happen when the post is published.' );
						}
					}

					// Clean up transient.
					delete_transient( $job_id );

					wp_send_json_success( array(
						'step'      => 4,
						'job_id'    => $job_id,
						'ai_result' => $ai_result,
						'wp_result' => $wp_result,
						'log'       => $log_messages,
					) );
					break;

				default:
					wp_send_json_error( array( 'message' => 'Invalid step.' ) );
			}

		} catch ( \Throwable $e ) {
			$log_messages[] = 'Error: ' . $e->getMessage();
			// Clean up transient on error.
			if ( ! empty( $job_id ) ) {
				delete_transient( $job_id );
			}
			wp_send_json_error( array(
				'message' => $e->getMessage(),
				'log'     => $log_messages,
				'debug'   => sprintf(
					'Step %d | %s in %s:%d | Provider: %s | Model: %s | PHP %s',
					$step,
					get_class( $e ),
					basename( $e->getFile() ),
					$e->getLine(),
					AICC_Settings::get_ai_provider(),
					AICC_Settings::get_active_model(),
					PHP_VERSION
				),
			) );
		}
	}

	/**
	 * Parse a datetime-local input value (e.g. "2026-04-10T14:30") into a Unix timestamp.
	 * Respects the WordPress site timezone.
	 *
	 * @param string $value Raw datetime-local string.
	 * @return int Unix timestamp, or 0 on failure / empty.
	 */
	private static function parse_datetime_local( $value ) {
		$value = sanitize_text_field( $value );
		if ( empty( $value ) ) {
			return 0;
		}

		// Accept both "2026-04-10T14:30" and "2026-04-10 14:30".
		$value = str_replace( 'T', ' ', $value );

		try {
			$timezone = wp_timezone();
			$dt       = new DateTime( $value, $timezone );
			return $dt->getTimestamp();
		} catch ( \Throwable $e ) {
			return 0;
		}
	}

	/**
	 * AJAX handler: Approve a scheduled draft, transitioning it to 'future' status.
	 */
	public function ajax_approve_scheduled() {
		check_ajax_referer( 'aicc_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'ai-content-orchestrator' ) ) );
		}

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		if ( empty( $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid post ID.', 'ai-content-orchestrator' ) ) );
		}

		$result = AICC_Publisher::approve_scheduled( $post_id );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler: Delete a scheduled item (draft or future).
	 */
	public function ajax_delete_scheduled() {
		check_ajax_referer( 'aicc_nonce', 'nonce' );

		if ( ! current_user_can( 'delete_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'ai-content-orchestrator' ) ) );
		}

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		if ( empty( $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid post ID.', 'ai-content-orchestrator' ) ) );
		}

		if ( ! current_user_can( 'delete_post', $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'You cannot delete this post.', 'ai-content-orchestrator' ) ) );
		}

		$deleted = wp_trash_post( $post_id );
		if ( ! $deleted ) {
			wp_send_json_error( array( 'message' => __( 'Failed to delete post.', 'ai-content-orchestrator' ) ) );
		}

		wp_send_json_success( array( 'id' => $post_id ) );
	}

	/**
	 * AJAX handler: Run the catch-up process immediately (bypasses rate limit).
	 */
	public function ajax_run_catchup() {
		check_ajax_referer( 'aicc_nonce', 'nonce' );

		if ( ! current_user_can( 'publish_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'ai-content-orchestrator' ) ) );
		}

		// Clear rate-limit transient so a subsequent request-hook run also works.
		delete_transient( 'aicc_catch_up_last_run' );

		$published = AICC_Publisher::catch_up_overdue();
		$log       = get_option( 'aicc_last_catchup_log', array() );

		wp_send_json_success( array(
			'published' => (int) $published,
			'found'     => isset( $log['found'] ) ? (int) $log['found'] : 0,
			'details'   => isset( $log['details'] ) ? $log['details'] : array(),
		) );
	}

	/**
	 * AJAX handler: Publish a scheduled post immediately (manual override for WP cron issues).
	 */
	public function ajax_publish_now() {
		check_ajax_referer( 'aicc_nonce', 'nonce' );

		if ( ! current_user_can( 'publish_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'ai-content-orchestrator' ) ) );
		}

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		if ( empty( $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid post ID.', 'ai-content-orchestrator' ) ) );
		}

		$result = AICC_Publisher::publish_now( $post_id );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler: Remove a saved URL from the quick-reuse list.
	 */
	public function ajax_remove_saved_url() {
		check_ajax_referer( 'aicc_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'ai-content-orchestrator' ) ) );
		}

		$url = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';
		if ( empty( $url ) ) {
			wp_send_json_error( array( 'message' => __( 'URL is required.', 'ai-content-orchestrator' ) ) );
		}

		$removed = AICC_Settings::remove_url( $url );

		wp_send_json_success( array(
			'removed' => $removed,
			'urls'    => AICC_Settings::get_saved_urls(),
		) );
	}

	/**
	 * AJAX handler: Upload a PDF file and add it to the library.
	 */
	public function ajax_upload_pdf() {
		check_ajax_referer( 'aicc_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'ai-content-orchestrator' ) ) );
		}

		// Try to increase upload limits.
		@ini_set( 'upload_max_filesize', '50M' );
		@ini_set( 'post_max_size', '50M' );
		@ini_set( 'memory_limit', '256M' );

		// When the file exceeds post_max_size, PHP silently drops the entire
		// POST body — $_FILES and $_POST become empty arrays.
		if ( empty( $_FILES['pdf_file'] ) ) {
			wp_send_json_error( array(
				'message' => sprintf(
					__( 'No file received. The file likely exceeds your server upload limit (%s). Ask your hosting provider to increase upload_max_filesize and post_max_size in php.ini.', 'ai-content-orchestrator' ),
					AICC_PDF_Library::get_max_upload_size_formatted()
				),
			) );
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- File upload handled by WordPress functions in AICC_PDF_Library::upload().
		$result = AICC_PDF_Library::upload( $_FILES['pdf_file'] );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array(
			'pdf' => array(
				'id'           => $result['id'],
				'name'         => $result['original_name'],
				'upload_date'  => wp_date( 'Y-m-d H:i', $result['upload_date'] ),
				'text_length'  => $result['text_length'],
				'text_preview' => mb_substr( $result['text'], 0, 150 ) . ( mb_strlen( $result['text'] ) > 150 ? '...' : '' ),
			),
		) );
	}

	/**
	 * AJAX handler: Receive a single chunk of a PDF file.
	 *
	 * JavaScript splits large PDFs into 1MB pieces and sends them one at a
	 * time. This works even when upload_max_filesize is 2MB. When the last
	 * chunk arrives, the server concatenates all chunks and processes the PDF.
	 */
	public function ajax_upload_pdf_chunk() {
		check_ajax_referer( 'aicc_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'ai-content-orchestrator' ) ) );
		}

		$chunk_number = isset( $_POST['chunk_number'] ) ? absint( $_POST['chunk_number'] ) : 0;
		$total_chunks = isset( $_POST['total_chunks'] ) ? absint( $_POST['total_chunks'] ) : 1;
		$temp_id      = isset( $_POST['temp_id'] ) ? sanitize_text_field( wp_unslash( $_POST['temp_id'] ) ) : '';
		$filename     = isset( $_POST['filename'] ) ? sanitize_file_name( wp_unslash( $_POST['filename'] ) ) : 'upload.pdf';

		if ( empty( $temp_id ) || empty( $_FILES['chunk'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Missing chunk data.', 'ai-content-orchestrator' ) ) );
		}

		// Check for upload errors on this chunk.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Comparing integer error code.
		if ( isset( $_FILES['chunk']['error'] ) && UPLOAD_ERR_OK !== (int) $_FILES['chunk']['error'] ) {
			wp_send_json_error( array(
				/* translators: %d: upload error code number */
				'message' => sprintf( __( 'Chunk upload error (code %d). Try reducing chunk size.', 'ai-content-orchestrator' ), (int) $_FILES['chunk']['error'] ),
			) );
		}

		// Create temp directory for this upload.
		$temp_dir = trailingslashit( AICC_PDF_Library::get_upload_dir() ) . 'tmp_' . $temp_id;
		if ( ! file_exists( $temp_dir ) ) {
			wp_mkdir_p( $temp_dir );
		}

		// Save this chunk using WordPress filesystem.
		$chunk_file = trailingslashit( $temp_dir ) . sprintf( 'chunk_%05d', $chunk_number );
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, Generic.PHP.ForbiddenFunctions.Found -- File upload tmp_name is handled by PHP.
		if ( ! move_uploaded_file( $_FILES['chunk']['tmp_name'], $chunk_file ) ) {
			wp_send_json_error( array( 'message' => __( 'Failed to save chunk.', 'ai-content-orchestrator' ) ) );
		}

		// Check if all chunks have been received.
		$received = count( glob( trailingslashit( $temp_dir ) . 'chunk_*' ) );
		if ( $received < $total_chunks ) {
			// More chunks to come.
			wp_send_json_success( array(
				'complete'     => false,
				'chunk'        => $chunk_number,
				'total_chunks' => $total_chunks,
				'received'     => $received,
			) );
			return;
		}

		// All chunks received — concatenate into final PDF.
		$final_dir  = AICC_PDF_Library::get_upload_dir();
		$final_id   = wp_generate_uuid4();
		$final_file = trailingslashit( $final_dir ) . $final_id . '.pdf';
		$out        = fopen( $final_file, 'wb' );

		if ( ! $out ) {
			wp_send_json_error( array( 'message' => __( 'Failed to create output file.', 'ai-content-orchestrator' ) ) );
		}

		for ( $i = 0; $i < $total_chunks; $i++ ) {
			$chunk_path = trailingslashit( $temp_dir ) . sprintf( 'chunk_%05d', $i );
			if ( file_exists( $chunk_path ) ) {
				$chunk_data = file_get_contents( $chunk_path );
				fwrite( $out, $chunk_data );
			}
		}
		fclose( $out );

		// Clean up temp chunks.
		$chunk_files = glob( trailingslashit( $temp_dir ) . 'chunk_*' );
		if ( $chunk_files ) {
			foreach ( $chunk_files as $cf ) {
				wp_delete_file( $cf );
			}
		}
		@rmdir( $temp_dir );

		// Extract text from the assembled PDF.
		$text = AICC_PDF_Extractor::extract( $final_file );
		if ( empty( $text ) ) {
			$text = '(Could not extract text from this PDF. It may be image-based or use complex encoding.)';
		}

		// Store in library.
		$pdf_data = array(
			'id'            => $final_id,
			'filename'      => $final_id . '.pdf',
			'original_name' => $filename,
			'upload_date'   => time(),
			'file_size'     => filesize( $final_file ),
			'text'          => $text,
			'text_length'   => mb_strlen( $text ),
		);

		$library                = AICC_PDF_Library::get_all();
		$library[ $final_id ]   = $pdf_data;
		update_option( AICC_PDF_Library::OPTION_KEY, $library );

		wp_send_json_success( array(
			'complete' => true,
			'pdf'      => array(
				'id'           => $final_id,
				'name'         => $filename,
				'upload_date'  => wp_date( 'Y-m-d H:i', $pdf_data['upload_date'] ),
				'text_length'  => $pdf_data['text_length'],
				'text_preview' => mb_substr( $text, 0, 150 ) . ( mb_strlen( $text ) > 150 ? '...' : '' ),
			),
		) );
	}

	/**
	 * AJAX handler: Delete a PDF from the library.
	 */
	public function ajax_delete_pdf() {
		check_ajax_referer( 'aicc_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'ai-content-orchestrator' ) ) );
		}

		$pdf_id = isset( $_POST['pdf_id'] ) ? sanitize_text_field( wp_unslash( $_POST['pdf_id'] ) ) : '';
		if ( empty( $pdf_id ) ) {
			wp_send_json_error( array( 'message' => __( 'PDF ID is required.', 'ai-content-orchestrator' ) ) );
		}

		$deleted = AICC_PDF_Library::delete( $pdf_id );
		if ( ! $deleted ) {
			wp_send_json_error( array( 'message' => __( 'PDF not found.', 'ai-content-orchestrator' ) ) );
		}

		wp_send_json_success( array( 'id' => $pdf_id ) );
	}

	/**
	 * AJAX handler: Reschedule an item to a new date/time.
	 */
	public function ajax_reschedule() {
		check_ajax_referer( 'aicc_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'ai-content-orchestrator' ) ) );
		}

		$post_id     = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$schedule_at = isset( $_POST['schedule_at'] ) ? self::parse_datetime_local( sanitize_text_field( wp_unslash( $_POST['schedule_at'] ) ) ) : 0;

		if ( empty( $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid post ID.', 'ai-content-orchestrator' ) ) );
		}
		if ( $schedule_at <= time() ) {
			wp_send_json_error( array( 'message' => __( 'Scheduled time must be in the future.', 'ai-content-orchestrator' ) ) );
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			wp_send_json_error( array( 'message' => __( 'Post not found.', 'ai-content-orchestrator' ) ) );
		}

		update_post_meta( $post_id, '_aicc_scheduled_publish_at', $schedule_at );

		// If already in 'future' status, also update post_date to match.
		if ( 'future' === $post->post_status ) {
			wp_update_post( array(
				'ID'            => $post_id,
				'post_date'     => wp_date( 'Y-m-d H:i:s', $schedule_at ),
				'post_date_gmt' => gmdate( 'Y-m-d H:i:s', $schedule_at ),
			) );
		}

		wp_send_json_success( array(
			'id'                     => $post_id,
			'scheduled_at'           => $schedule_at,
			'scheduled_at_formatted' => wp_date( 'Y-m-d H:i', $schedule_at ),
		) );
	}

	/**
	 * Handle LinkedIn OAuth callback (redirect from LinkedIn).
	 */
	public function handle_linkedin_callback() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Handle disconnect.
		if ( ! empty( $_GET['aicc_linkedin_disconnect'] ) ) {
			check_admin_referer( 'aicc_linkedin_disconnect' );
			AICC_LinkedIn::disconnect();
			wp_safe_redirect( admin_url( 'admin.php?page=aicc-settings' ) );
			exit;
		}

		// Handle OAuth callback.
		if ( empty( $_GET['aicc_linkedin_callback'] ) ) {
			return;
		}

		// Handle OAuth code exchange.
		if ( empty( $_GET['code'] ) ) {
			$error = isset( $_GET['error_description'] ) ? sanitize_text_field( wp_unslash( $_GET['error_description'] ) ) : 'Authorization was denied or failed.';
			wp_safe_redirect( admin_url( 'admin.php?page=aicc-settings&aicc_linkedin_error=' . rawurlencode( $error ) ) );
			exit;
		}

		$code  = sanitize_text_field( wp_unslash( $_GET['code'] ) );
		$state = isset( $_GET['state'] ) ? sanitize_text_field( wp_unslash( $_GET['state'] ) ) : '';

		$result = AICC_LinkedIn::handle_callback( $code, $state );

		if ( is_wp_error( $result ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=aicc-settings&aicc_linkedin_error=' . rawurlencode( $result->get_error_message() ) ) );
			exit;
		}

		wp_safe_redirect( admin_url( 'admin.php?page=aicc-settings&aicc_linkedin_success=1' ) );
		exit;
	}

	/**
	 * AJAX handler: Manually share a post to LinkedIn.
	 */
	public function ajax_linkedin_share_now() {
		check_ajax_referer( 'aicc_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'ai-content-orchestrator' ) ) );
		}

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		if ( ! $post_id ) {
			wp_send_json_error( array( 'message' => __( 'Missing post ID.', 'ai-content-orchestrator' ) ) );
		}

		if ( ! AICC_LinkedIn::is_connected() ) {
			wp_send_json_error( array( 'message' => __( 'LinkedIn is not connected. Go to Settings and connect your account first.', 'ai-content-orchestrator' ) ) );
		}

		// Clear any previous error.
		delete_post_meta( $post_id, '_aicc_linkedin_error' );
		// Clear shared flag to allow retry.
		delete_post_meta( $post_id, '_aicc_linkedin_shared' );

		$result = AICC_LinkedIn::share_post( $post_id );

		if ( is_wp_error( $result ) ) {
			update_post_meta( $post_id, '_aicc_linkedin_error', $result->get_error_message() );
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array(
			'message'   => __( 'Successfully shared to LinkedIn!', 'ai-content-orchestrator' ),
			'shared_at' => time(),
		) );
	}

	/**
	 * AJAX handler: Remove a post from the LinkedIn Sharing Status dashboard.
	 *
	 * Clears the LinkedIn-related post meta so the post no longer appears in the
	 * dashboard list. Does NOT delete the WordPress post itself or the LinkedIn share.
	 */
	public function ajax_linkedin_remove_from_dashboard() {
		check_ajax_referer( 'aicc_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'ai-content-orchestrator' ) ) );
		}

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		if ( ! $post_id ) {
			wp_send_json_error( array( 'message' => __( 'Missing post ID.', 'ai-content-orchestrator' ) ) );
		}

		// Clear all LinkedIn-related meta — this removes the post from the dashboard
		// query (which filters by _aicc_post_to_linkedin = '1').
		delete_post_meta( $post_id, '_aicc_post_to_linkedin' );
		delete_post_meta( $post_id, '_aicc_linkedin_shared' );
		delete_post_meta( $post_id, '_aicc_linkedin_error' );
		delete_post_meta( $post_id, '_aicc_linkedin_commentary' );

		wp_send_json_success();
	}

	/**
	 * AJAX handler: Bulk remove multiple posts from the LinkedIn Sharing Status dashboard.
	 */
	public function ajax_linkedin_bulk_remove() {
		check_ajax_referer( 'aicc_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'ai-content-orchestrator' ) ) );
		}

		$post_ids = isset( $_POST['post_ids'] ) ? array_map( 'absint', (array) $_POST['post_ids'] ) : array();
		$post_ids = array_filter( $post_ids );

		if ( empty( $post_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No posts selected.', 'ai-content-orchestrator' ) ) );
		}

		$removed = 0;
		foreach ( $post_ids as $post_id ) {
			delete_post_meta( $post_id, '_aicc_post_to_linkedin' );
			delete_post_meta( $post_id, '_aicc_linkedin_shared' );
			delete_post_meta( $post_id, '_aicc_linkedin_error' );
			delete_post_meta( $post_id, '_aicc_linkedin_commentary' );
			$removed++;
		}

		wp_send_json_success( array(
			'removed'  => $removed,
			'post_ids' => $post_ids,
		) );
	}

	/**
	 * AJAX handler: Save edited LinkedIn commentary.
	 */
	public function ajax_linkedin_save_commentary() {
		check_ajax_referer( 'aicc_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'ai-content-orchestrator' ) ) );
		}

		$post_id    = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$commentary = isset( $_POST['commentary'] ) ? sanitize_textarea_field( wp_unslash( $_POST['commentary'] ) ) : '';

		if ( ! $post_id ) {
			wp_send_json_error( array( 'message' => __( 'Missing post ID.', 'ai-content-orchestrator' ) ) );
		}

		// Cap at LinkedIn's 3000 char limit.
		if ( mb_strlen( $commentary ) > 2900 ) {
			$commentary = mb_substr( $commentary, 0, 2900 );
		}

		update_post_meta( $post_id, '_aicc_linkedin_commentary', $commentary );

		wp_send_json_success( array(
			'commentary' => $commentary,
			'length'     => mb_strlen( $commentary ),
		) );
	}

	/**
	 * AJAX handler: Regenerate LinkedIn commentary via AI.
	 */
	public function ajax_linkedin_regenerate_commentary() {
		check_ajax_referer( 'aicc_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'ai-content-orchestrator' ) ) );
		}

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		if ( ! $post_id ) {
			wp_send_json_error( array( 'message' => __( 'Missing post ID.', 'ai-content-orchestrator' ) ) );
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			wp_send_json_error( array( 'message' => __( 'Post not found.', 'ai-content-orchestrator' ) ) );
		}

		if ( ! AICC_Settings::is_configured() ) {
			wp_send_json_error( array( 'message' => __( 'AI provider not configured.', 'ai-content-orchestrator' ) ) );
		}

		// Build metadata array from the post.
		$meta = array(
			'seo_title'        => $post->post_title,
			'meta_description' => $post->post_excerpt,
			'focus_keyphrase'  => get_post_meta( $post_id, '_yoast_wpseo_focuskw', true ),
		);

		$blog_style = get_post_meta( $post_id, '_aicc_blog_style', true );
		if ( empty( $blog_style ) ) {
			$blog_style = 'standard';
		}

		try {
			$generator  = new AICC_Generator();
			$commentary = $generator->generate_linkedin_post(
				$post->post_content,
				$meta,
				$blog_style,
				get_permalink( $post_id )
			);

			update_post_meta( $post_id, '_aicc_linkedin_commentary', $commentary );

			wp_send_json_success( array(
				'commentary' => $commentary,
				'length'     => mb_strlen( $commentary ),
			) );
		} catch ( \Throwable $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * AJAX handler: Select a featured image from the 4 generated options.
	 */
	public function ajax_select_featured_image() {
		check_ajax_referer( 'aicc_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'ai-content-orchestrator' ) ) );
		}

		$post_id   = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$image_idx = isset( $_POST['image_index'] ) ? absint( $_POST['image_index'] ) : 0;

		if ( ! $post_id ) {
			wp_send_json_error( array( 'message' => __( 'Missing post ID.', 'ai-content-orchestrator' ) ) );
		}

		$options = get_post_meta( $post_id, '_aicc_image_options', true );
		if ( ! is_array( $options ) || empty( $options[ $image_idx ] ) ) {
			wp_send_json_error( array( 'message' => __( 'Image option not found or expired.', 'ai-content-orchestrator' ) ) );
		}

		// Remove the previous AICC-generated featured image attachment.
		$old_attachment_id = get_post_thumbnail_id( $post_id );
		if ( $old_attachment_id && get_post_meta( $old_attachment_id, '_aicc_generated_image', true ) ) {
			wp_delete_attachment( $old_attachment_id, true );
		}
		delete_post_thumbnail( $post_id );

		// Download and attach the newly selected image.
		$post = get_post( $post_id );
		$attach_result = AICC_Publisher::attach_image_from_url(
			$post_id,
			$options[ $image_idx ],
			$post ? $post->post_title : ''
		);

		if ( is_wp_error( $attach_result ) ) {
			wp_send_json_error( array( 'message' => $attach_result->get_error_message() ) );
		}

		update_post_meta( $post_id, '_aicc_image_selected', $image_idx );

		wp_send_json_success( array(
			'attachment_id'   => $attach_result,
			'featured_image'  => get_the_post_thumbnail_url( $post_id, 'large' ),
			'selected_index'  => $image_idx,
		) );
	}

	/**
	 * AJAX handler: Regenerate 4 new featured image options for a post.
	 */
	public function ajax_regenerate_featured_images() {
		check_ajax_referer( 'aicc_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'ai-content-orchestrator' ) ) );
		}

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		if ( ! $post_id ) {
			wp_send_json_error( array( 'message' => __( 'Missing post ID.', 'ai-content-orchestrator' ) ) );
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			wp_send_json_error( array( 'message' => __( 'Post not found.', 'ai-content-orchestrator' ) ) );
		}

		if ( ! AICC_Settings::is_image_configured() ) {
			$provider_labels = array( 'openai' => 'OpenAI', 'ideogram' => 'Ideogram' );
			$provider        = AICC_Settings::get_image_provider();
			$label           = isset( $provider_labels[ $provider ] ) ? $provider_labels[ $provider ] : $provider;
			wp_send_json_error( array(
				'message' => sprintf(
					/* translators: %s: image provider name */
					__( '%s API key is required for image generation. Configure it in Settings.', 'ai-content-orchestrator' ),
					$label
				),
			) );
		}

		try {
			$generator = new AICC_Generator();

			// Build metadata from the post for the prompt generator.
			$meta = array(
				'seo_title'        => $post->post_title,
				'meta_description' => $post->post_excerpt,
				'focus_keyphrase'  => get_post_meta( $post_id, '_yoast_wpseo_focuskw', true ),
			);

			// Use the post content and stored blog style for context-aware prompts.
			$blog_style    = get_post_meta( $post_id, '_aicc_blog_style', true );
			$blog_style    = ! empty( $blog_style ) ? $blog_style : 'standard';
			$image_prompts = $generator->generate_image_prompts( $meta, $post->post_content, $blog_style );
			$image_urls    = $generator->generate_images( $image_prompts, 4, $blog_style );

			if ( empty( $image_urls ) ) {
				wp_send_json_error( array( 'message' => __( 'No images were generated.', 'ai-content-orchestrator' ) ) );
			}

			update_post_meta( $post_id, '_aicc_image_options', $image_urls );
			update_post_meta( $post_id, '_aicc_image_prompt', $image_prompts[0] );
			update_post_meta( $post_id, '_aicc_image_prompts', $image_prompts );
			update_post_meta( $post_id, '_aicc_image_selected', 0 );

			// Also auto-attach the first new image as featured (replacing existing).
			$old_attachment_id = get_post_thumbnail_id( $post_id );
			if ( $old_attachment_id && get_post_meta( $old_attachment_id, '_aicc_generated_image', true ) ) {
				wp_delete_attachment( $old_attachment_id, true );
			}
			delete_post_thumbnail( $post_id );
			AICC_Publisher::attach_image_from_url( $post_id, $image_urls[0], $post->post_title );

			wp_send_json_success( array(
				'image_urls'    => $image_urls,
				'image_prompt'  => $image_prompts[0],
				'featured_image' => get_the_post_thumbnail_url( $post_id, 'large' ),
			) );
		} catch ( \Throwable $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	/**
	 * AJAX handler: Scan the active theme for brand colors.
	 *
	 * Extracts colors from multiple sources:
	 * 1. Block theme palette (theme.json via wp_get_global_settings)
	 * 2. Customizer theme mods (any value that looks like a hex color)
	 * 3. WordPress core: background_color, header_textcolor
	 * 4. Theme stylesheet CSS custom properties (--wp--preset--color--)
	 *
	 * Returns deduplicated hex color codes.
	 */
	/**
	 * AJAX handler: Regenerate featured image overlay with custom text.
	 */
	public function ajax_regenerate_overlay() {
		check_ajax_referer( 'aicc_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'ai-content-orchestrator' ) ) );
		}

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$line1   = isset( $_POST['line1'] ) ? sanitize_text_field( wp_unslash( $_POST['line1'] ) ) : '';
		$line2   = isset( $_POST['line2'] ) ? sanitize_text_field( wp_unslash( $_POST['line2'] ) ) : '';

		if ( ! $post_id || empty( $line1 ) ) {
			wp_send_json_error( array( 'message' => __( 'Post ID and at least line 1 are required.', 'ai-content-orchestrator' ) ) );
		}

		$default_image_id = AICC_Settings::get_default_featured_image();
		if ( $default_image_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'No default featured image configured in Settings.', 'ai-content-orchestrator' ) ) );
		}

		// Delete the old overlay image if it was AICC-generated.
		$old_thumb_id = get_post_thumbnail_id( $post_id );
		if ( $old_thumb_id && get_post_meta( $old_thumb_id, '_aicc_overlay_image', true ) ) {
			wp_delete_attachment( $old_thumb_id, true );
		}
		delete_post_thumbnail( $post_id );

		$result = AICC_Image_Overlay::create_and_attach(
			$post_id,
			$default_image_id,
			$line1 . ' ' . $line2,
			array(
				'color'       => AICC_Settings::get_overlay_text_color(),
				'font_bold'   => AICC_Settings::get_overlay_font_bold_path(),
				'font_italic' => AICC_Settings::get_overlay_font_italic_path(),
				'lines'       => array( $line1, $line2 ),
			)
		);

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		// Store custom text in post meta for future reference.
		update_post_meta( $post_id, '_aicc_overlay_line1', $line1 );
		update_post_meta( $post_id, '_aicc_overlay_line2', $line2 );

		wp_send_json_success( array(
			'featured_image' => get_the_post_thumbnail_url( $post_id, 'large' ),
			'attachment_id'  => $result,
			'line1'          => $line1,
			'line2'          => $line2,
		) );
	}

	/**
	 * AJAX handler: Generate repurposed content (email, twitter, instagram, pinterest).
	 */
	public function ajax_repurpose_content() {
		check_ajax_referer( 'aicc_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'ai-content-orchestrator' ) ) );
		}

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$format  = isset( $_POST['format'] ) ? sanitize_text_field( wp_unslash( $_POST['format'] ) ) : '';

		if ( ! $post_id || empty( $format ) ) {
			wp_send_json_error( array( 'message' => __( 'Post ID and format are required.', 'ai-content-orchestrator' ) ) );
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			wp_send_json_error( array( 'message' => __( 'Post not found.', 'ai-content-orchestrator' ) ) );
		}

		$meta = array(
			'seo_title'        => $post->post_title,
			'meta_description' => $post->post_excerpt,
			'focus_keyphrase'  => get_post_meta( $post_id, '_yoast_wpseo_focuskw', true ),
		);

		$blog_url = get_permalink( $post_id );

		try {
			$generator = new AICC_Generator();
			$blog_text = wp_strip_all_tags( $post->post_content );
			if ( mb_strlen( $blog_text ) > 5000 ) {
				$blog_text = mb_substr( $blog_text, 0, 5000 ) . '...';
			}

			$method = 'generate_' . $format;
			$repurposer = new \ReflectionClass( 'AICC_Repurposer' );
			$gen_method = $repurposer->getMethod( $method );
			$gen_method->setAccessible( true );
			$result = $gen_method->invoke( null, $generator, $blog_text, $meta, $blog_url );

			wp_send_json_success( array(
				'format'  => $format,
				'content' => $result,
			) );
		} catch ( \Throwable $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	public function ajax_suggest_topics() {
		check_ajax_referer( 'aicc_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'ai-content-orchestrator' ) ) );
		}

		$seed  = isset( $_POST['seed'] ) ? sanitize_text_field( wp_unslash( $_POST['seed'] ) ) : '';
		$count = isset( $_POST['count'] ) ? min( absint( $_POST['count'] ), 20 ) : 5;
		$count = max( $count, 1 );
		if ( empty( $seed ) ) {
			wp_send_json_error( array( 'message' => __( 'Enter a seed keyword first.', 'ai-content-orchestrator' ) ) );
		}

		try {
			$generator  = new AICC_Generator();
			$ref_method = new \ReflectionMethod( $generator, 'call_ai' );
			$ref_method->setAccessible( true );

			$style_keys = 'standard, how-to, listicle, ultimate-guide, comparison, case-study, problem-solution, beginners-guide, data-driven, storytelling, opinion, checklist';

			$prompt = sprintf(
				'Generate exactly %d unique blog post topic ideas for the keyword "%s". For each topic, also recommend the best blog style from this list: %s. Return ONLY %d lines, each in this exact format: NUMBER. TITLE | STYLE_KEY. Example: 1. How to Build an API Gateway | how-to. No explanations, no extra text. Write the TITLE in the same language as the keyword. The STYLE_KEY must be in English from the list above.',
				$count,
				$seed,
				$style_keys,
				$count
			);

			$result = $ref_method->invoke(
				$generator,
				'You are a content strategist. Output ONLY numbered lines in the format: NUMBER. TITLE | STYLE_KEY. Nothing else.',
				$prompt,
				1024
			);

			$valid_styles = array_flip( explode( ', ', $style_keys ) );
			$lines  = array_filter( array_map( 'trim', explode( "\n", trim( $result ) ) ) );
			$topics = array();
			foreach ( $lines as $line ) {
				$clean = preg_replace( '/^\d+[\.\)\-:\s]+/', '', $line );
				$clean = trim( $clean, '"\'* ' );
				if ( empty( $clean ) || mb_strlen( $clean ) < 5 ) {
					continue;
				}

				$style = 'standard';
				if ( false !== strpos( $clean, '|' ) ) {
					$parts = explode( '|', $clean, 2 );
					$clean = trim( $parts[0], ' "\'*' );
					$suggested_style = trim( strtolower( $parts[1] ), ' "\'*' );
					if ( isset( $valid_styles[ $suggested_style ] ) ) {
						$style = $suggested_style;
					}
				}

				$topics[] = array(
					'title' => $clean,
					'style' => $style,
				);
			}

			if ( empty( $topics ) ) {
				wp_send_json_error( array( 'message' => __( 'AI returned no usable topics. Try a different keyword.', 'ai-content-orchestrator' ) ) );
			}

			wp_send_json_success( array( 'topics' => array_slice( $topics, 0, $count ) ) );
		} catch ( \Throwable $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	public function ajax_analyze_post() {
		check_ajax_referer( 'aicc_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'ai-content-orchestrator' ) ) );
		}

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		if ( ! $post_id ) {
			wp_send_json_error( array( 'message' => __( 'Select a post first.', 'ai-content-orchestrator' ) ) );
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			wp_send_json_error( array( 'message' => __( 'Post not found.', 'ai-content-orchestrator' ) ) );
		}

		$content    = wp_strip_all_tags( $post->post_content );
		$word_count = str_word_count( $content );
		$published  = get_the_date( 'Y-m-d', $post );
		$updated    = get_the_modified_date( 'Y-m-d', $post );
		$age_days   = ( time() - strtotime( $post->post_date ) ) / DAY_IN_SECONDS;

		// Detect issues.
		$issues = array();

		// Check for thin sections (short post or few headings).
		$heading_count = preg_match_all( '/<h[23][^>]*>/i', $post->post_content );
		if ( $word_count < 800 || $heading_count < 3 ) {
			$issues[] = array(
				'key'   => 'thin_sections',
				'label' => __( 'Thin content — post has only', 'ai-content-orchestrator' ) . ' ' . $word_count . ' ' . __( 'words and', 'ai-content-orchestrator' ) . ' ' . $heading_count . ' ' . __( 'headings. Recommended: 1000+ words, 5+ headings.', 'ai-content-orchestrator' ),
			);
		}

		// Check for FAQ section.
		$has_faq = preg_match( '/faq|frequently\s+asked|veelgestelde\s+vragen|häufig\s+gestellte/i', $post->post_content );
		if ( ! $has_faq ) {
			$issues[] = array(
				'key'   => 'missing_faq',
				'label' => __( 'No FAQ section detected. Adding one improves SEO and featured snippets.', 'ai-content-orchestrator' ),
			);
		}

		// Check for internal links.
		$link_count = preg_match_all( '/<a\s[^>]*href=["\'][^"\']*' . preg_quote( wp_parse_url( home_url(), PHP_URL_HOST ), '/' ) . '/i', $post->post_content );
		if ( $link_count < 2 ) {
			$issues[] = array(
				'key'   => 'missing_internal_links',
				'label' => sprintf( __( 'Only %d internal links found. Recommended: 3-5 for SEO.', 'ai-content-orchestrator' ), $link_count ),
			);
		}

		// Check for outdated content.
		if ( $age_days > 180 ) {
			$months = round( $age_days / 30 );
			$issues[] = array(
				'key'   => 'outdated_content',
				'label' => sprintf( __( 'Post is %d months old. Refreshing outdated content can recover lost rankings.', 'ai-content-orchestrator' ), $months ),
			);
		}

		wp_send_json_success( array(
			'post_id'    => $post_id,
			'title'      => $post->post_title,
			'published'  => $published,
			'updated'    => $updated,
			'word_count' => $word_count,
			'issues'     => $issues,
			'url'        => get_permalink( $post_id ),
			'edit_url'   => get_edit_post_link( $post_id, 'raw' ),
		) );
	}

	public function ajax_analyze_all_posts() {
		check_ajax_referer( 'aicc_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'ai-content-orchestrator' ) ) );
		}

		$posts = get_posts( array(
			'post_type'   => 'post',
			'post_status' => 'publish',
			'numberposts' => 200,
			'orderby'     => 'date',
			'order'       => 'DESC',
		) );

		$home_host  = wp_parse_url( home_url(), PHP_URL_HOST );
		$results    = array();
		$total_issues = 0;

		foreach ( $posts as $post ) {
			$content    = wp_strip_all_tags( $post->post_content );
			$word_count = str_word_count( $content );
			$age_days   = ( time() - strtotime( $post->post_date ) ) / DAY_IN_SECONDS;
			$heading_count = preg_match_all( '/<h[23][^>]*>/i', $post->post_content );
			$has_faq    = preg_match( '/faq|frequently\s+asked|veelgestelde\s+vragen/i', $post->post_content );
			$link_count = preg_match_all( '/<a\s[^>]*href=["\'][^"\']*' . preg_quote( $home_host, '/' ) . '/i', $post->post_content );

			$issues = array();
			if ( $word_count < 800 || $heading_count < 3 ) {
				$issues[] = 'thin';
			}
			if ( ! $has_faq ) {
				$issues[] = 'no_faq';
			}
			if ( $link_count < 2 ) {
				$issues[] = 'few_links';
			}
			if ( $age_days > 180 ) {
				$issues[] = 'outdated';
			}

			$total_issues += count( $issues );

			$results[] = array(
				'id'          => $post->ID,
				'title'       => $post->post_title,
				'url'         => get_permalink( $post->ID ),
				'edit_url'    => get_edit_post_link( $post->ID, 'raw' ),
				'word_count'  => $word_count,
				'age_days'    => (int) $age_days,
				'issues'      => $issues,
				'issue_count' => count( $issues ),
			);
		}

		// Sort: most issues first, then by age descending.
		usort( $results, function ( $a, $b ) {
			if ( $a['issue_count'] !== $b['issue_count'] ) {
				return $b['issue_count'] - $a['issue_count'];
			}
			return $b['age_days'] - $a['age_days'];
		} );

		$posts_with_issues = count( array_filter( $results, function( $r ) { return $r['issue_count'] > 0; } ) );

		wp_send_json_success( array(
			'posts'             => $results,
			'total'             => count( $results ),
			'posts_with_issues' => $posts_with_issues,
			'total_issues'      => $total_issues,
		) );
	}

	public function ajax_refresh_post() {
		check_ajax_referer( 'aicc_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'ai-content-orchestrator' ) ) );
		}

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$issues  = isset( $_POST['issues'] ) ? array_map( 'sanitize_text_field', wp_unslash( (array) $_POST['issues'] ) ) : array();

		if ( ! $post_id ) {
			wp_send_json_error( array( 'message' => __( 'No post selected.', 'ai-content-orchestrator' ) ) );
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			wp_send_json_error( array( 'message' => __( 'Post not found.', 'ai-content-orchestrator' ) ) );
		}

		try {
			$generator  = new AICC_Generator();
			$ref_method = new \ReflectionMethod( $generator, 'call_ai' );
			$ref_method->setAccessible( true );

			$existing_content = wp_strip_all_tags( $post->post_content );
			if ( mb_strlen( $existing_content ) > 8000 ) {
				$existing_content = mb_substr( $existing_content, 0, 8000 ) . '...';
			}

			$issue_instructions = array();
			if ( in_array( 'thin_sections', $issues, true ) ) {
				$issue_instructions[] = 'Expand thin sections with more detail, examples, and data. Target 1500+ words total.';
			}
			if ( in_array( 'missing_faq', $issues, true ) ) {
				$issue_instructions[] = 'Add a comprehensive FAQ section at the end with 5-8 relevant questions and answers.';
			}
			if ( in_array( 'missing_internal_links', $issues, true ) ) {
				$issue_instructions[] = 'The internal links will be added automatically after content generation — focus on the content quality.';
			}
			if ( in_array( 'outdated_content', $issues, true ) ) {
				$issue_instructions[] = 'Update any outdated information, statistics, or references. Use current data and trends.';
			}

			$prompt = sprintf(
				"You are an expert SEO content writer. Refresh and improve the following blog post.\n\n" .
				"CURRENT TITLE: %s\n\n" .
				"CURRENT CONTENT:\n%s\n\n" .
				"INSTRUCTIONS:\n%s\n\n" .
				"RULES:\n" .
				"- Keep the same topic, angle, and URL slug\n" .
				"- Improve and expand the content, don't just rewrite\n" .
				"- Use H2 and H3 headings for structure\n" .
				"- Write in short paragraphs (2-3 sentences)\n" .
				"- Use bullet/numbered lists where appropriate\n" .
				"- Include relevant statistics or data points\n" .
				"- Output ONLY raw HTML (no markdown, no explanation)\n" .
				"- Write in the same language as the original post\n" .
				"- Start with the intro paragraph (no H1 — WordPress provides the title)",
				$post->post_title,
				$existing_content,
				implode( "\n", $issue_instructions )
			);

			$ref_cont = new \ReflectionMethod( $generator, 'call_ai_with_continuation' );
			$ref_cont->setAccessible( true );
			$new_content = $ref_cont->invoke( $generator, 'You are an SEO content writer. Output ONLY raw HTML — no markdown fences, no explanation.', $prompt, 4096 );
			$new_content = trim( $new_content );

			// Strip markdown fences and truncation placeholders.
			$new_content = preg_replace( '/^\s*```(?:html)?\s*\n?/i', '', $new_content );
			$new_content = preg_replace( '/\n?\s*```\s*$/i', '', $new_content );
			$new_content = preg_replace( '/\(content\s+continues[^)]*\)/i', '', $new_content );

			if ( empty( $new_content ) ) {
				wp_send_json_error( array( 'message' => __( 'AI returned empty content.', 'ai-content-orchestrator' ) ) );
			}

			// Update the post content.
			$update_result = wp_update_post( array(
				'ID'           => $post_id,
				'post_content' => wp_kses_post( $new_content ),
			), true );

			if ( is_wp_error( $update_result ) ) {
				wp_send_json_error( array( 'message' => $update_result->get_error_message() ) );
			}

			// Run internal linking if the issue was selected.
			$links_added = 0;
			if ( in_array( 'missing_internal_links', $issues, true ) && AICC_Settings::is_internal_linking_enabled() ) {
				$meta = array(
					'seo_title'       => $post->post_title,
					'focus_keyphrase' => get_post_meta( $post_id, '_yoast_wpseo_focuskw', true ),
				);
				$link_result = AICC_Internal_Linker::add_links( $new_content, $meta, AICC_Settings::get_max_internal_links(), $post_id );
				if ( $link_result['links_added'] > 0 ) {
					wp_update_post( array(
						'ID'           => $post_id,
						'post_content' => wp_kses_post( $link_result['html'] ),
					) );
					$links_added = $link_result['links_added'];
				}
			}

			$new_word_count = str_word_count( wp_strip_all_tags( $new_content ) );

			wp_send_json_success( array(
				'post_id'        => $post_id,
				'title'          => $post->post_title,
				'url'            => get_permalink( $post_id ),
				'edit_url'       => get_edit_post_link( $post_id, 'raw' ),
				'word_count'     => $new_word_count,
				'links_added'    => $links_added,
				'issues_fixed'   => $issues,
			) );
		} catch ( \Throwable $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ) );
		}
	}

	public function ajax_scan_theme_colors() {
		check_ajax_referer( 'aicc_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'ai-content-orchestrator' ) ) );
		}

		$colors = array();
		$sources = array();

		// 1. Block theme palette (WordPress 5.9+ with theme.json).
		if ( function_exists( 'wp_get_global_settings' ) ) {
			$palette = wp_get_global_settings( array( 'color', 'palette', 'theme' ) );
			if ( is_array( $palette ) && ! empty( $palette ) ) {
				foreach ( $palette as $entry ) {
					if ( ! empty( $entry['color'] ) ) {
						$hex = self::normalize_hex( $entry['color'] );
						if ( $hex ) {
							$colors[] = $hex;
						}
					}
				}
				if ( ! empty( $colors ) ) {
					$sources[] = 'theme.json palette';
				}
			}

			// Also check custom palette (user overrides in Global Styles).
			$custom_palette = wp_get_global_settings( array( 'color', 'palette', 'custom' ) );
			if ( is_array( $custom_palette ) && ! empty( $custom_palette ) ) {
				foreach ( $custom_palette as $entry ) {
					if ( ! empty( $entry['color'] ) ) {
						$hex = self::normalize_hex( $entry['color'] );
						if ( $hex ) {
							$colors[] = $hex;
						}
					}
				}
				$sources[] = 'Global Styles custom palette';
			}
		}

		// 2. Customizer theme mods — scan all mods for hex color values.
		$mods = get_theme_mods();
		if ( is_array( $mods ) ) {
			$found_mods = false;
			foreach ( $mods as $key => $value ) {
				if ( ! is_string( $value ) ) {
					continue;
				}
				$hex = self::normalize_hex( $value );
				if ( $hex ) {
					$colors[]   = $hex;
					$found_mods = true;
				}
			}
			if ( $found_mods ) {
				$sources[] = 'Customizer';
			}
		}

		// 3. WordPress core color settings.
		$bg = get_background_color();
		if ( ! empty( $bg ) ) {
			$hex = self::normalize_hex( '#' . $bg );
			if ( $hex ) {
				$colors[]  = $hex;
				$sources[] = 'background color';
			}
		}

		$header_text = get_header_textcolor();
		if ( ! empty( $header_text ) && 'blank' !== $header_text ) {
			$hex = self::normalize_hex( '#' . $header_text );
			if ( $hex ) {
				$colors[]  = $hex;
				$sources[] = 'header text color';
			}
		}

		// 4. Parse the active theme's style.css for CSS custom properties.
		$stylesheet_path = get_stylesheet_directory() . '/style.css';
		if ( file_exists( $stylesheet_path ) ) {
			$css = file_get_contents( $stylesheet_path );
			if ( false !== $css ) {
				// Extract --wp--preset--color-- and other common color variables.
				if ( preg_match_all( '/:\s*(#[0-9a-fA-F]{3,8})\b/', $css, $matches ) ) {
					$css_found = false;
					foreach ( $matches[1] as $match ) {
						$hex = self::normalize_hex( $match );
						if ( $hex ) {
							$colors[]  = $hex;
							$css_found = true;
						}
					}
					if ( $css_found ) {
						$sources[] = 'theme stylesheet';
					}
				}
			}
		}

		// Deduplicate and remove near-black (#000000) and near-white (#ffffff)
		// which are rarely useful as brand colors.
		$colors = array_unique( array_map( 'strtolower', $colors ) );
		$colors = array_values( array_filter( $colors, function ( $c ) {
			return ! in_array( $c, array( '#000000', '#ffffff', '#fff', '#000' ), true );
		} ) );

		// Limit to 10 most relevant colors.
		$colors = array_slice( $colors, 0, 10 );

		if ( empty( $colors ) ) {
			wp_send_json_error( array(
				'message' => __( 'No colors detected from the active theme. You can enter brand colors manually.', 'ai-content-orchestrator' ),
			) );
			return;
		}

		wp_send_json_success( array(
			'colors'  => $colors,
			'sources' => array_unique( $sources ),
			'message' => sprintf(
				/* translators: 1: number of colors, 2: sources list */
				__( 'Found %1$d colors from: %2$s', 'ai-content-orchestrator' ),
				count( $colors ),
				implode( ', ', array_unique( $sources ) )
			),
		) );
	}

	/**
	 * Normalize a color value to a 6-digit hex code.
	 *
	 * @param string $color Color string (hex with or without #).
	 * @return string|false Normalized #rrggbb hex code, or false if invalid.
	 */
	private static function normalize_hex( $color ) {
		$color = trim( $color );

		// Strip # prefix.
		$hex = ltrim( $color, '#' );

		// Expand 3-digit hex to 6-digit.
		if ( preg_match( '/^[0-9a-fA-F]{3}$/', $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}

		// Validate 6-digit hex (ignore 8-digit alpha hex).
		if ( preg_match( '/^[0-9a-fA-F]{6}$/', $hex ) ) {
			return '#' . strtolower( $hex );
		}

		return false;
	}

	/**
	 * AJAX handler: Disconnect LinkedIn.
	 */
	public function ajax_linkedin_disconnect() {
		check_ajax_referer( 'aicc_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'ai-content-orchestrator' ) ) );
		}

		AICC_LinkedIn::disconnect();
		wp_send_json_success();
	}

	/**
	 * Auto-share to LinkedIn when a post transitions to 'publish'.
	 *
	 * Only fires for AICC-generated posts that have the LinkedIn flag set.
	 *
	 * @param string  $new_status New post status.
	 * @param string  $old_status Old post status.
	 * @param WP_Post $post       Post object.
	 */
	public function maybe_share_to_linkedin( $new_status, $old_status, $post ) {
		if ( ! aicc_is_pro() ) {
			return;
		}
		// Only act on transition TO publish.
		if ( 'publish' !== $new_status || 'publish' === $old_status ) {
			return;
		}

		// Only for AICC-generated posts.
		if ( ! get_post_meta( $post->ID, '_aicc_generated', true ) ) {
			return;
		}

		// Only if LinkedIn sharing was requested for this post.
		if ( ! get_post_meta( $post->ID, '_aicc_post_to_linkedin', true ) ) {
			return;
		}

		// Only if not already shared.
		if ( get_post_meta( $post->ID, '_aicc_linkedin_shared', true ) ) {
			return;
		}

		// Only if LinkedIn is connected.
		if ( ! AICC_LinkedIn::is_connected() ) {
			return;
		}

		// Share it.
		$result = AICC_LinkedIn::share_post( $post->ID );

		if ( is_wp_error( $result ) ) {
			update_post_meta( $post->ID, '_aicc_linkedin_error', $result->get_error_message() );
		}
	}

	public function ajax_test_instagram() {
		check_ajax_referer( 'aicc_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'ai-content-orchestrator' ) ) );
		}

		$access_token = AICC_Instagram::get_access_token();
		if ( ! $access_token ) {
			wp_send_json_error( array( 'message' => __( 'Not connected. Connect Instagram first.', 'ai-content-orchestrator' ) ) );
		}

		$profile = AICC_Instagram::get_profile();
		$results = array();

		// Test 1: Token validity — fetch Instagram account info.
		$ig_response = wp_remote_get( AICC_Instagram::GRAPH_URL . '/' . $profile['ig_user_id'] . '?' . http_build_query( array(
			'access_token' => $access_token,
			'fields'       => 'id,username,name,profile_picture_url,followers_count,media_count',
		) ) );

		if ( is_wp_error( $ig_response ) ) {
			wp_send_json_error( array( 'message' => $ig_response->get_error_message() ) );
		}

		$ig_data = json_decode( wp_remote_retrieve_body( $ig_response ), true );
		if ( ! empty( $ig_data['error'] ) ) {
			wp_send_json_error( array( 'message' => $ig_data['error']['message'] ?? 'API error' ) );
		}

		$results['account'] = array(
			'username'   => $ig_data['username'] ?? '?',
			'name'       => $ig_data['name'] ?? '',
			'followers'  => $ig_data['followers_count'] ?? 0,
			'posts'      => $ig_data['media_count'] ?? 0,
		);

		// Test 2: Check Meta App info.
		$app_response = wp_remote_get( AICC_Instagram::GRAPH_URL . '/app?' . http_build_query( array(
			'access_token' => $access_token,
		) ) );

		if ( ! is_wp_error( $app_response ) ) {
			$app_data = json_decode( wp_remote_retrieve_body( $app_response ), true );
			$results['app'] = array(
				'id'   => $app_data['id'] ?? '?',
				'name' => $app_data['name'] ?? '?',
			);
		}

		// Test 3: Check token expiry.
		$tokens = get_option( 'aicc_instagram_tokens', array() );
		$results['token'] = array(
			'expires_at' => isset( $tokens['expires_at'] ) ? wp_date( 'Y-m-d H:i', $tokens['expires_at'] ) : '?',
			'days_left'  => isset( $tokens['expires_at'] ) ? max( 0, (int) ceil( ( $tokens['expires_at'] - time() ) / DAY_IN_SECONDS ) ) : 0,
		);

		// Test 4: Check publish permission by verifying content_publishing_limit.
		$limit_response = wp_remote_get( AICC_Instagram::GRAPH_URL . '/' . $profile['ig_user_id'] . '/content_publishing_limit?' . http_build_query( array(
			'access_token' => $access_token,
			'fields'       => 'quota_usage,config',
		) ) );

		if ( ! is_wp_error( $limit_response ) ) {
			$limit_data = json_decode( wp_remote_retrieve_body( $limit_response ), true );
			$results['publishing'] = array(
				'can_publish' => empty( $limit_data['error'] ),
				'quota_usage' => $limit_data['data'][0]['quota_usage'] ?? 0,
			);
		}

		wp_send_json_success( $results );
	}

	public function handle_instagram_callback() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Disconnect.
		if ( isset( $_GET['aicc_instagram_disconnect'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['aicc_instagram_disconnect'] ) ), 'aicc_instagram_disconnect' ) ) {
			AICC_Instagram::disconnect();
			wp_safe_redirect( admin_url( 'admin.php?page=aicc-settings&tab=instagram&aicc_instagram_disconnected=1' ) );
			exit;
		}

		// OAuth callback.
		if ( isset( $_GET['tab'] ) && 'instagram' === $_GET['tab'] && isset( $_GET['code'] ) && isset( $_GET['state'] ) ) {
			$result = AICC_Instagram::handle_callback( sanitize_text_field( wp_unslash( $_GET['code'] ) ), sanitize_text_field( wp_unslash( $_GET['state'] ) ) );
			if ( is_wp_error( $result ) ) {
				wp_safe_redirect( admin_url( 'admin.php?page=aicc-settings&tab=instagram&aicc_instagram_error=' . urlencode( $result->get_error_message() ) ) );
			} else {
				wp_safe_redirect( admin_url( 'admin.php?page=aicc-settings&tab=instagram&aicc_instagram_success=1' ) );
			}
			exit;
		}
	}

	public function maybe_share_to_instagram( $new_status, $old_status, $post ) {
		if ( ! aicc_is_pro() ) {
			return;
		}
		if ( 'publish' !== $new_status || 'publish' === $old_status ) {
			return;
		}
		if ( ! get_post_meta( $post->ID, '_aicc_generated', true ) ) {
			return;
		}
		if ( ! get_post_meta( $post->ID, '_aicc_post_to_instagram', true ) ) {
			return;
		}
		if ( get_post_meta( $post->ID, '_aicc_instagram_shared', true ) ) {
			return;
		}
		if ( ! AICC_Instagram::is_connected() ) {
			return;
		}

		$result = AICC_Instagram::share_post( $post->ID );
		if ( is_wp_error( $result ) ) {
			update_post_meta( $post->ID, '_aicc_instagram_error', $result->get_error_message() );
		}
	}

	public function maybe_notify_published( $new_status, $old_status, $post ) {
		if ( 'publish' !== $new_status || 'publish' === $old_status ) {
			return;
		}

		if ( ! get_post_meta( $post->ID, '_aicc_generated', true ) ) {
			return;
		}

		$emails = AICC_Settings::get_notify_emails();
		if ( empty( $emails ) ) {
			return;
		}

		$site_name = get_bloginfo( 'name' );
		$post_url  = get_permalink( $post->ID );
		$edit_url  = get_edit_post_link( $post->ID, 'raw' );

		$subject = sprintf(
			/* translators: 1: site name, 2: post title */
			__( '[%1$s] Post published: %2$s', 'ai-content-orchestrator' ),
			$site_name,
			$post->post_title
		);

		$body = sprintf(
			__( "A scheduled post has been published on %s.\n\nTitle: %s\nURL: %s\nEdit: %s\n\nThis notification was sent by AI Content Orchestrator.", 'ai-content-orchestrator' ),
			$site_name,
			$post->post_title,
			$post_url,
			$edit_url
		);

		$headers = array( 'Content-Type: text/plain; charset=UTF-8' );

		foreach ( $emails as $email ) {
			if ( is_email( $email ) ) {
				wp_mail( $email, $subject, $body, $headers );
			}
		}
	}
}
