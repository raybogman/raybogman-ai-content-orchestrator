<?php
/**
 * Admin functionality.
 *
 * @package Ray_Bogman_AI_Content_Orchestrator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RBCO_Admin
 *
 * Handles admin menu pages, asset enqueuing, and AJAX endpoints.
 */
class RBCO_Admin {

	/**
	 * Singleton instance.
	 *
	 * @var RBCO_Admin|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return RBCO_Admin
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
		add_action( 'admin_init', array( 'RBCO_Settings', 'handle_save' ), 5 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

		// AJAX handlers.
		add_action( 'wp_ajax_rbco_create_content', array( $this, 'ajax_create_content_step' ) );
		add_action( 'wp_ajax_rbco_get_categories', array( $this, 'ajax_get_categories' ) );
		add_action( 'wp_ajax_rbco_validate_api_key', array( $this, 'ajax_validate_api_key' ) );
		add_action( 'wp_ajax_rbco_remove_saved_url', array( $this, 'ajax_remove_saved_url' ) );
		add_action( 'wp_ajax_rbco_select_featured_image', array( $this, 'ajax_select_featured_image' ) );
		add_action( 'wp_ajax_rbco_regenerate_featured_images', array( $this, 'ajax_regenerate_featured_images' ) );
		add_action( 'wp_ajax_rbco_scan_theme_colors', array( $this, 'ajax_scan_theme_colors' ) );
		add_action( 'wp_ajax_rbco_regenerate_overlay', array( $this, 'ajax_regenerate_overlay' ) );

		// Plugin action links.
		add_filter( 'plugin_action_links_' . RBCO_PLUGIN_BASENAME, array( $this, 'add_action_links' ) );
	}

	/**
	 * Add admin menu pages.
	 */
	public function add_menu_pages() {
		// Main menu — Dashboard is the landing page.
		add_menu_page(
			__( 'Ray Bogman AI Content Orchestrator', 'raybogman-ai-content-orchestrator' ),
			__( 'AI Content', 'raybogman-ai-content-orchestrator' ),
			'edit_posts',
			'raybogman-ai-content-orchestrator',
			array( $this, 'render_dashboard_page' ),
			'dashicons-edit-large',
			30
		);

		add_submenu_page(
			'raybogman-ai-content-orchestrator',
			__( 'Dashboard', 'raybogman-ai-content-orchestrator' ),
			__( 'Dashboard', 'raybogman-ai-content-orchestrator' ),
			'edit_posts',
			'raybogman-ai-content-orchestrator',
			array( $this, 'render_dashboard_page' )
		);

		add_submenu_page(
			'raybogman-ai-content-orchestrator',
			__( 'Create Content', 'raybogman-ai-content-orchestrator' ),
			__( 'Create Content', 'raybogman-ai-content-orchestrator' ),
			'edit_posts',
			'rbco-create',
			array( $this, 'render_main_page' )
		);

		add_submenu_page(
			'raybogman-ai-content-orchestrator',
			__( 'Style Examples', 'raybogman-ai-content-orchestrator' ),
			__( 'Style Examples', 'raybogman-ai-content-orchestrator' ),
			'edit_posts',
			'rbco-examples',
			array( $this, 'render_examples_page' )
		);

		add_submenu_page(
			'raybogman-ai-content-orchestrator',
			__( 'Settings', 'raybogman-ai-content-orchestrator' ),
			__( 'Settings', 'raybogman-ai-content-orchestrator' ),
			'manage_options',
			'rbco-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Render the Dashboard page.
	 */
	public function render_dashboard_page() {
		include RBCO_PLUGIN_DIR . 'admin/views/dashboard-page.php';
	}

	/**
	 * Render the style examples page.
	 */
	public function render_examples_page() {
		include RBCO_PLUGIN_DIR . 'admin/views/examples-page.php';
	}

	/**
	 * Register plugin settings.
	 */
	public function register_settings() {
		RBCO_Settings::register();
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( false === strpos( $hook_suffix, 'raybogman-ai-content-orchestrator' ) && false === strpos( $hook_suffix, 'rbco-' ) ) {
			return;
		}

		// Load WordPress media library modal (required for image/font selectors on Settings page).
		if ( false !== strpos( $hook_suffix, 'rbco-settings' ) ) {
			wp_enqueue_media();
		}

		wp_enqueue_style(
			'rbco-admin',
			RBCO_PLUGIN_URL . 'admin/css/admin.css',
			array(),
			RBCO_VERSION
		);

		wp_enqueue_script(
			'rbco-admin',
			RBCO_PLUGIN_URL . 'admin/js/admin.js',
			array( 'jquery' ),
			RBCO_VERSION,
			true
		);

		$categories  = RBCO_Publisher::get_categories();
		$provider    = RBCO_Settings::get_ai_provider();
		$blog_styles = RBCO_Styles::get_styles_for_js();

		wp_localize_script(
			'rbco-admin',
			'rbco',
			array(
				'ajax_url'    => admin_url( 'admin-ajax.php' ),
				'nonce'       => wp_create_nonce( 'rbco_nonce' ),
				'provider'    => $provider,
				'model'       => RBCO_Settings::get_active_model(),
				'configured'  => RBCO_Settings::is_configured(),
				'categories'  => $categories,
				'saved_urls'  => RBCO_Settings::get_saved_urls(),
				'blog_styles' => $blog_styles,
				'has_yoast'   => defined( 'WPSEO_VERSION' ),
				'i18n'        => array(
					'working'         => __( 'Working...', 'raybogman-ai-content-orchestrator' ),
					'create_content'  => __( 'Create Content', 'raybogman-ai-content-orchestrator' ),
					'prompt_required' => __( 'Please enter a prompt.', 'raybogman-ai-content-orchestrator' ),
					'not_configured'  => __( 'Please configure your AI provider API key in Settings first.', 'raybogman-ai-content-orchestrator' ),
					'error'           => __( 'Error', 'raybogman-ai-content-orchestrator' ),
					'done'            => __( 'Done!', 'raybogman-ai-content-orchestrator' ),
					'published'       => __( 'Published', 'raybogman-ai-content-orchestrator' ),
					'draft'           => __( 'Draft', 'raybogman-ai-content-orchestrator' ),
					'updated'         => __( 'Updated', 'raybogman-ai-content-orchestrator' ),
					'not_available'   => __( 'Not available', 'raybogman-ai-content-orchestrator' ),
					'request_failed'  => __( 'Request failed', 'raybogman-ai-content-orchestrator' ),
				),
			)
		);
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
				admin_url( 'admin.php?page=rbco-create' ),
				__( 'Create Content', 'raybogman-ai-content-orchestrator' )
			),
			sprintf(
				'<a href="%s">%s</a>',
				admin_url( 'admin.php?page=rbco-settings' ),
				__( 'Settings', 'raybogman-ai-content-orchestrator' )
			),
		);
		return array_merge( $plugin_links, $links );
	}

	/**
	 * Render the main content creation page.
	 */
	public function render_main_page() {
		include RBCO_PLUGIN_DIR . 'admin/views/main-page.php';
	}

	/**
	 * Render the settings page.
	 */
	public function render_settings_page() {
		include RBCO_PLUGIN_DIR . 'admin/views/settings-page.php';
	}

	/**
	 * AJAX handler: Get WordPress categories.
	 */
	public function ajax_get_categories() {
		check_ajax_referer( 'rbco_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'raybogman-ai-content-orchestrator' ) ) );
		}

		wp_send_json_success(
			array(
				'categories' => RBCO_Publisher::get_categories(),
			)
		);
	}

	/**
	 * AJAX handler: Validate an API key by making a minimal test request.
	 */
	public function ajax_validate_api_key() {
		check_ajax_referer( 'rbco_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'raybogman-ai-content-orchestrator' ) ) );
		}

		$provider = isset( $_POST['provider'] ) ? sanitize_text_field( wp_unslash( $_POST['provider'] ) ) : '';

		if ( 'claude' === $provider ) {
			$api_key = RBCO_Settings::get_anthropic_api_key();
			if ( empty( $api_key ) ) {
				wp_send_json_error( array( 'message' => __( 'No Anthropic API key saved. Enter the key and click Save Changes first.', 'raybogman-ai-content-orchestrator' ) ) );
			}

			$response = wp_remote_post(
				'https://api.anthropic.com/v1/messages',
				array(
					'timeout' => 30,
					'headers' => array(
						'Content-Type'      => 'application/json',
						'x-api-key'         => $api_key,
						'anthropic-version' => '2023-06-01',
					),
					'body'    => wp_json_encode(
						array(
							'model'      => RBCO_Settings::get_claude_model(),
							'max_tokens' => 10,
							'messages'   => array(
								array(
									'role'    => 'user',
									'content' => 'Say "ok"',
								),
							),
						)
					),
				)
			);

		} elseif ( 'openai' === $provider ) {
			$api_key = RBCO_Settings::get_openai_api_key();
			if ( empty( $api_key ) ) {
				wp_send_json_error( array( 'message' => __( 'No OpenAI API key saved. Enter the key and click Save Changes first.', 'raybogman-ai-content-orchestrator' ) ) );
			}

			$response = wp_remote_post(
				'https://api.openai.com/v1/chat/completions',
				array(
					'timeout' => 30,
					'headers' => array(
						'Content-Type'  => 'application/json',
						'Authorization' => 'Bearer ' . $api_key,
					),
					'body'    => wp_json_encode(
						array(
							'model'      => RBCO_Settings::get_openai_model(),
							'max_tokens' => 10,
							'messages'   => array(
								array(
									'role'    => 'user',
									'content' => 'Say "ok"',
								),
							),
						)
					),
				)
			);

		} else {
			wp_send_json_error( array( 'message' => __( 'Invalid provider.', 'raybogman-ai-content-orchestrator' ) ) );
			return;
		}

		if ( is_wp_error( $response ) ) {
			wp_send_json_error(
				array(
					'message' => sprintf( 'Connection failed: %s', $response->get_error_message() ),
				)
			);
			return;
		}

		$code      = wp_remote_retrieve_response_code( $response );
		$resp_body = wp_remote_retrieve_body( $response );
		$data      = json_decode( $resp_body, true );

		if ( 401 === $code ) {
			wp_send_json_error( array( 'message' => __( 'Invalid API key (HTTP 401 Unauthorized).', 'raybogman-ai-content-orchestrator' ) ) );
		} elseif ( 403 === $code ) {
			wp_send_json_error( array( 'message' => __( 'Access denied (HTTP 403 Forbidden). Check your API key permissions.', 'raybogman-ai-content-orchestrator' ) ) );
		} elseif ( $code >= 200 && $code < 300 ) {
			$model_used = '';
			if ( is_array( $data ) && isset( $data['model'] ) ) {
				$model_used = $data['model'];
			}
			wp_send_json_success(
				array(
					'message' => sprintf(
					/* translators: %s: dynamic value */
						__( 'Connection successful! Using model: %s', 'raybogman-ai-content-orchestrator' ),
						$model_used
					),
				)
			);
		} else {
			$error_msg = '';
			if ( is_array( $data ) && isset( $data['error']['message'] ) ) {
				$error_msg = $data['error']['message'];
			} elseif ( is_array( $data ) && isset( $data['message'] ) ) {
				$error_msg = $data['message'];
			} else {
				$error_msg = mb_substr( $resp_body, 0, 300 );
			}
			wp_send_json_error(
				array(
					'message' => sprintf( 'API error (HTTP %d): %s', $code, $error_msg ),
				)
			);
		}
	}

	/**
	 * Register a shutdown handler that catches fatal errors during AJAX
	 * and returns them as JSON so the browser can display them.
	 *
	 * @param array $log_messages Reference to log messages array.
	 */
	private static function register_fatal_error_handler( &$log_messages ) {
		register_shutdown_function(
			function () use ( &$log_messages ) {
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
			}
		);
	}

	/**
	 * Raise PHP execution/memory limits for a single long-running request.
	 *
	 * This is intentionally NOT called globally (no init/__construct usage).
	 * It is invoked only inside the specific AJAX handlers that genuinely need
	 * extended limits (AI generation and large PDF assembly). Memory is only
	 * ever raised, never lowered, so a host with a higher limit is untouched.
	 *
	 * @param string $memory Target memory limit, e.g. '256M'.
	 */
	private function raise_limits_for_request( $memory = '256M' ) {
		// The multi-step AJAX handler already chunks work into short
		// requests, so we intentionally do NOT override the host's PHP
		// execution-time limit here — let max_execution_time stay as the
		// host configured it.
		//
		// We only raise memory, and we do it through the WordPress API rather
		// than the raw PHP setting. A custom context ('rbco') means our filter
		// only fires for this call and never affects other plugins or core.
		$filter = function () use ( $memory ) {
			return $memory;
		};
		add_filter( 'rbco_memory_limit', $filter );
		if ( function_exists( 'wp_raise_memory_limit' ) ) {
			wp_raise_memory_limit( 'rbco' );
		}
		remove_filter( 'rbco_memory_limit', $filter );
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
		check_ajax_referer( 'rbco_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'raybogman-ai-content-orchestrator' ) ) );
		}

		// Increase limits only for content generation steps (AI API calls can
		// take 60-120s). Scoped to this AJAX handler only — not set globally.
		$this->raise_limits_for_request( '256M' );

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
					$content_type     = isset( $_POST['content_type'] ) ? sanitize_text_field( wp_unslash( $_POST['content_type'] ) ) : 'blog';
					$url              = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';
					$prompt           = isset( $_POST['prompt'] ) ? sanitize_textarea_field( wp_unslash( $_POST['prompt'] ) ) : '';
					$status           = isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : 'draft';
					$category_ids     = isset( $_POST['categories'] ) ? array_map( 'absint', (array) $_POST['categories'] ) : array();
					$blog_style       = isset( $_POST['blog_style'] ) ? sanitize_text_field( wp_unslash( $_POST['blog_style'] ) ) : 'standard';
					$save_url         = isset( $_POST['save_url'] ) && '1' === $_POST['save_url'];
					$generate_image   = isset( $_POST['generate_image'] ) && '1' === $_POST['generate_image'];
					$internal_linking = ( isset( $_POST['internal_linking'] ) && '1' === $_POST['internal_linking'] ) ? '1' : '0';

					if ( empty( $prompt ) ) {
						wp_send_json_error( array( 'message' => __( 'Prompt is required.', 'raybogman-ai-content-orchestrator' ) ) );
					}
					if ( ! RBCO_Settings::is_configured() ) {
						wp_send_json_error( array( 'message' => __( 'API key is not configured.', 'raybogman-ai-content-orchestrator' ) ) );
					}

					// Save URL if requested.
					if ( $save_url && ! empty( $url ) ) {
						RBCO_Settings::save_url( $url );
					}

					// Generate a unique job ID.
					$job_id = 'rbco_' . wp_generate_uuid4();

					$log_callback(
						sprintf(
							'Step 1/4: Scanning | PHP %s | Provider: %s (%s)',
							PHP_VERSION,
							ucfirst( RBCO_Settings::get_ai_provider() ),
							RBCO_Settings::get_active_model()
						)
					);

					// Scan website.
					$all_site_data = array();
					if ( ! empty( $url ) ) {
						$scanner       = new RBCO_Scanner( $log_callback );
						$all_site_data = $scanner->scan( $url );
					} else {
						$log_callback( 'No URL provided — skipping website scan.' );
					}

					// Store job data in transient (30 min expiry).
					$job_data = array(
						'content_type'     => $content_type,
						'prompt'           => $prompt,
						'status'           => $status,
						'category_ids'     => $category_ids,
						'blog_style'       => $blog_style,
						'site_data'        => $all_site_data,
						'generate_image'   => $generate_image,
						'internal_linking' => $internal_linking,
					);
					set_transient( $job_id, $job_data, 1800 );

					wp_send_json_success(
						array(
							'step'      => 1,
							'job_id'    => $job_id,
							'next_step' => 2,
							'log'       => $log_messages,
						)
					);
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

					$project_vision = RBCO_Settings::get_project_vision();
					if ( ! empty( $project_vision ) ) {
						$preview = strlen( $project_vision ) > 100 ? substr( $project_vision, 0, 100 ) . '...' : $project_vision;
						$log_callback( sprintf( 'Project Vision: %s', $preview ) );
						$log_callback( sprintf( 'Prompt: %s', $job_data['prompt'] ) );
					}

					$generator     = new RBCO_Generator( $log_callback );
					$context_block = $generator->build_context_block( $job_data['prompt'], $job_data['content_type'], $job_data['site_data'] );

					$existing_categories = RBCO_Publisher::get_categories();
					$category_names      = wp_list_pluck( $existing_categories, 'name' );

					$meta = $generator->generate_metadata( $context_block, $category_names );
					$log_callback( sprintf( 'SEO Title: %s', isset( $meta['seo_title'] ) ? $meta['seo_title'] : '?' ) );
					$log_callback( sprintf( 'Slug: %s', isset( $meta['slug'] ) ? $meta['slug'] : '?' ) );

					// Store metadata in job.
					$job_data['meta']          = $meta;
					$job_data['context_block'] = $context_block;
					set_transient( $job_id, $job_data, 1800 );

					wp_send_json_success(
						array(
							'step'      => 2,
							'job_id'    => $job_id,
							'next_step' => 3,
							'log'       => $log_messages,
						)
					);
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

					$generator    = new RBCO_Generator( $log_callback );
					$blog_style   = isset( $job_data['blog_style'] ) ? $job_data['blog_style'] : 'standard';
					$html_content = $generator->generate_content( $job_data['context_block'], $job_data['content_type'], $job_data['meta'], $blog_style );
					$log_callback( sprintf( 'Content generated: %d characters', strlen( $html_content ) ) );

					// Auto-add internal links to related published posts.
					$do_linking = false;
					if ( isset( $job_data['internal_linking'] ) ) {
						$do_linking = in_array( $job_data['internal_linking'], array( true, '1', 1 ), true );
					} else {
						$do_linking = RBCO_Settings::is_internal_linking_enabled();
					}
					$log_callback( sprintf( 'Internal linking: %s', $do_linking ? 'enabled' : 'disabled' ) );
					if ( $do_linking ) {
						$log_callback( 'Scanning your site for internal linking opportunities...' );
						$link_result  = RBCO_Internal_Linker::add_links(
							$html_content,
							$job_data['meta'],
							RBCO_Settings::get_max_internal_links(),
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

							$log_callback( 'Generating 4 image options with gpt-image-1 (this may take 1-2 minutes)...' );
							$image_urls = $generator->generate_images( $image_prompts, 4 );
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

					wp_send_json_success(
						array(
							'step'      => 3,
							'job_id'    => $job_id,
							'next_step' => 4,
							'log'       => $log_messages,
						)
					);
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

					$meta      = $job_data['meta'];
					$ai_result = array(
						'seo_title'        => isset( $meta['seo_title'] ) ? $meta['seo_title'] : 'New Post',
						'meta_description' => isset( $meta['meta_description'] ) ? $meta['meta_description'] : '',
						'slug'             => isset( $meta['slug'] ) ? $meta['slug'] : 'new-post',
						'focus_keyphrase'  => isset( $meta['focus_keyphrase'] ) ? $meta['focus_keyphrase'] : '',
						'tags'             => isset( $meta['tags'] ) ? $meta['tags'] : array(),
						'categories'       => isset( $meta['categories'] ) ? $meta['categories'] : array(),
						'content'          => $job_data['html_content'],
						'project_vision'   => RBCO_Settings::get_project_vision(),
						'prompt'           => $job_data['prompt'],
						'image_urls'       => isset( $job_data['image_urls'] ) ? $job_data['image_urls'] : array(),
						'image_prompt'     => isset( $job_data['image_prompt'] ) ? $job_data['image_prompt'] : '',
						'linked_posts'     => isset( $job_data['linked_posts'] ) ? $job_data['linked_posts'] : array(),
					);

					$category_ids = $job_data['category_ids'];
					$status       = $job_data['status'];

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

					$log_callback( sprintf( 'Publishing %s to WordPress as %s...', $job_data['content_type'], $status ) );

					$wp_result = RBCO_Publisher::create( $job_data['content_type'], $ai_result, $status, $category_ids );

					if ( $wp_result['success'] ) {
						$log_callback( sprintf( 'Created %s #%d: %s', $job_data['content_type'], $wp_result['id'], $wp_result['title'] ) );

						// Attach AI-generated featured image if we have one (first of 4).
						if ( ! empty( $job_data['image_urls'] ) && is_array( $job_data['image_urls'] ) ) {
							$first_url = $job_data['image_urls'][0];
							$log_callback( 'Downloading and attaching first image as featured...' );
							$attach_result = RBCO_Publisher::attach_image_from_url(
								$wp_result['id'],
								$first_url,
								$wp_result['title']
							);
							if ( is_wp_error( $attach_result ) ) {
								$log_callback( sprintf( 'Featured image attach failed: %s', $attach_result->get_error_message() ) );
							} else {
								$log_callback( sprintf( 'Featured image attached (attachment #%d).', $attach_result ) );
								if ( ! empty( $job_data['image_prompt'] ) ) {
									update_post_meta( $wp_result['id'], '_rbco_image_prompt', $job_data['image_prompt'] );
								}
								if ( ! empty( $job_data['image_prompts'] ) ) {
									update_post_meta( $wp_result['id'], '_rbco_image_prompts', $job_data['image_prompts'] );
								}
								// Store all 4 URLs so user can switch via the result view.
								// Note: OpenAI image URLs expire ~1 hour after generation.
								update_post_meta( $wp_result['id'], '_rbco_image_options', $job_data['image_urls'] );
								update_post_meta( $wp_result['id'], '_rbco_image_selected', 0 );
							}
						}

						// If no AI images were generated, use the default featured image (with optional title overlay).
						if ( empty( $job_data['image_urls'] ) || ! is_array( $job_data['image_urls'] ) || empty( $job_data['image_urls'] ) ) {
							$default_image_id = RBCO_Settings::get_default_featured_image();
							if ( $default_image_id > 0 ) {
								if ( RBCO_Settings::is_overlay_enabled() ) {
									$log_callback( 'Creating featured image with title overlay...' );
									$overlay_result = RBCO_Image_Overlay::create_and_attach(
										$wp_result['id'],
										$default_image_id,
										isset( $meta['seo_title'] ) ? $meta['seo_title'] : $wp_result['title'],
										array(
											'color'       => RBCO_Settings::get_overlay_text_color(),
											'font_bold'   => RBCO_Settings::get_overlay_font_bold_path(),
											'font_italic' => RBCO_Settings::get_overlay_font_italic_path(),
										)
									);
									if ( is_wp_error( $overlay_result ) ) {
										$log_callback( sprintf( 'Overlay image failed: %s. Using base image as fallback.', $overlay_result->get_error_message() ) );
										set_post_thumbnail( $wp_result['id'], $default_image_id );
									} else {
										$log_callback( sprintf( 'Featured image with title overlay attached (attachment #%d).', $overlay_result ) );
										$overlay_title = isset( $meta['seo_title'] ) ? $meta['seo_title'] : $wp_result['title'];
										$auto_lines    = RBCO_Image_Overlay::get_split_title( $overlay_title );
										update_post_meta( $wp_result['id'], '_rbco_overlay_line1', $auto_lines[0] );
										update_post_meta( $wp_result['id'], '_rbco_overlay_line2', $auto_lines[1] );
									}
								} else {
									set_post_thumbnail( $wp_result['id'], $default_image_id );
									$log_callback( 'Default featured image set.' );
								}
							}
						}

						// Store blog style for later image regeneration.
						if ( ! empty( $job_data['blog_style'] ) ) {
							update_post_meta( $wp_result['id'], '_rbco_blog_style', $job_data['blog_style'] );
						}

						if ( $wp_result['yoast'] ) {
							$log_callback( 'Yoast SEO fields updated (title, meta description, focus keyphrase).' );
						}
					}

					// Include the WordPress-hosted featured image URL if set.
					if ( $wp_result['success'] && has_post_thumbnail( $wp_result['id'] ) ) {
						$wp_result['featured_image'] = get_the_post_thumbnail_url( $wp_result['id'], 'large' );

						// Include overlay text lines so the result view can show edit fields.
						$overlay_l1 = get_post_meta( $wp_result['id'], '_rbco_overlay_line1', true );
						if ( '' !== $overlay_l1 ) {
							$wp_result['overlay_line1'] = $overlay_l1;
							$wp_result['overlay_line2'] = get_post_meta( $wp_result['id'], '_rbco_overlay_line2', true );
						}
					}

					// Clean up transient.
					delete_transient( $job_id );

					wp_send_json_success(
						array(
							'step'      => 4,
							'job_id'    => $job_id,
							'ai_result' => $ai_result,
							'wp_result' => $wp_result,
							'log'       => $log_messages,
						)
					);
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
			wp_send_json_error(
				array(
					'message' => $e->getMessage(),
					'log'     => $log_messages,
					'debug'   => sprintf(
						'Step %d | %s in %s:%d | Provider: %s | Model: %s | PHP %s',
						$step,
						get_class( $e ),
						basename( $e->getFile() ),
						$e->getLine(),
						RBCO_Settings::get_ai_provider(),
						RBCO_Settings::get_active_model(),
						PHP_VERSION
					),
				)
			);
		}
	}

	/**
	 * AJAX handler: Remove a saved URL from the quick-reuse list.
	 */
	public function ajax_remove_saved_url() {
		check_ajax_referer( 'rbco_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'raybogman-ai-content-orchestrator' ) ) );
		}

		$url = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';
		if ( empty( $url ) ) {
			wp_send_json_error( array( 'message' => __( 'URL is required.', 'raybogman-ai-content-orchestrator' ) ) );
		}

		$removed = RBCO_Settings::remove_url( $url );

		wp_send_json_success(
			array(
				'removed' => $removed,
				'urls'    => RBCO_Settings::get_saved_urls(),
			)
		);
	}

	/**
	 * AJAX handler: Select a featured image from the 4 generated options.
	 */
	public function ajax_select_featured_image() {
		check_ajax_referer( 'rbco_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'raybogman-ai-content-orchestrator' ) ) );
		}

		$post_id   = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$image_idx = isset( $_POST['image_index'] ) ? absint( $_POST['image_index'] ) : 0;

		if ( ! $post_id ) {
			wp_send_json_error( array( 'message' => __( 'Missing post ID.', 'raybogman-ai-content-orchestrator' ) ) );
		}

		$options = get_post_meta( $post_id, '_rbco_image_options', true );
		if ( ! is_array( $options ) || empty( $options[ $image_idx ] ) ) {
			wp_send_json_error( array( 'message' => __( 'Image option not found or expired.', 'raybogman-ai-content-orchestrator' ) ) );
		}

		// Remove the previous AICC-generated featured image attachment.
		$old_attachment_id = get_post_thumbnail_id( $post_id );
		if ( $old_attachment_id && get_post_meta( $old_attachment_id, '_rbco_generated_image', true ) ) {
			wp_delete_attachment( $old_attachment_id, true );
		}
		delete_post_thumbnail( $post_id );

		// Download and attach the newly selected image.
		$post          = get_post( $post_id );
		$attach_result = RBCO_Publisher::attach_image_from_url(
			$post_id,
			$options[ $image_idx ],
			$post ? $post->post_title : ''
		);

		if ( is_wp_error( $attach_result ) ) {
			wp_send_json_error( array( 'message' => $attach_result->get_error_message() ) );
		}

		update_post_meta( $post_id, '_rbco_image_selected', $image_idx );

		wp_send_json_success(
			array(
				'attachment_id'  => $attach_result,
				'featured_image' => get_the_post_thumbnail_url( $post_id, 'large' ),
				'selected_index' => $image_idx,
			)
		);
	}

	/**
	 * AJAX handler: Regenerate 4 new featured image options for a post.
	 */
	public function ajax_regenerate_featured_images() {
		check_ajax_referer( 'rbco_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'raybogman-ai-content-orchestrator' ) ) );
		}

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		if ( ! $post_id ) {
			wp_send_json_error( array( 'message' => __( 'Missing post ID.', 'raybogman-ai-content-orchestrator' ) ) );
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			wp_send_json_error( array( 'message' => __( 'Post not found.', 'raybogman-ai-content-orchestrator' ) ) );
		}

		if ( ! RBCO_Settings::is_image_configured() ) {
			wp_send_json_error(
				array(
					'message' => __( 'An OpenAI API key is required for image generation. Configure it in Settings.', 'raybogman-ai-content-orchestrator' ),
				)
			);
		}

		try {
			$generator = new RBCO_Generator();

			// Build metadata from the post for the prompt generator.
			$meta = array(
				'seo_title'        => $post->post_title,
				'meta_description' => $post->post_excerpt,
				'focus_keyphrase'  => get_post_meta( $post_id, '_yoast_wpseo_focuskw', true ),
			);

			// Use the post content and stored blog style for context-aware prompts.
			$blog_style    = get_post_meta( $post_id, '_rbco_blog_style', true );
			$blog_style    = ! empty( $blog_style ) ? $blog_style : 'standard';
			$image_prompts = $generator->generate_image_prompts( $meta, $post->post_content, $blog_style );
			$image_urls    = $generator->generate_images( $image_prompts, 4 );

			if ( empty( $image_urls ) ) {
				wp_send_json_error( array( 'message' => __( 'No images were generated.', 'raybogman-ai-content-orchestrator' ) ) );
			}

			update_post_meta( $post_id, '_rbco_image_options', $image_urls );
			update_post_meta( $post_id, '_rbco_image_prompt', $image_prompts[0] );
			update_post_meta( $post_id, '_rbco_image_prompts', $image_prompts );
			update_post_meta( $post_id, '_rbco_image_selected', 0 );

			// Also auto-attach the first new image as featured (replacing existing).
			$old_attachment_id = get_post_thumbnail_id( $post_id );
			if ( $old_attachment_id && get_post_meta( $old_attachment_id, '_rbco_generated_image', true ) ) {
				wp_delete_attachment( $old_attachment_id, true );
			}
			delete_post_thumbnail( $post_id );
			RBCO_Publisher::attach_image_from_url( $post_id, $image_urls[0], $post->post_title );

			wp_send_json_success(
				array(
					'image_urls'     => $image_urls,
					'image_prompt'   => $image_prompts[0],
					'featured_image' => get_the_post_thumbnail_url( $post_id, 'large' ),
				)
			);
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
		check_ajax_referer( 'rbco_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'raybogman-ai-content-orchestrator' ) ) );
		}

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$line1   = isset( $_POST['line1'] ) ? sanitize_text_field( wp_unslash( $_POST['line1'] ) ) : '';
		$line2   = isset( $_POST['line2'] ) ? sanitize_text_field( wp_unslash( $_POST['line2'] ) ) : '';

		if ( ! $post_id || empty( $line1 ) ) {
			wp_send_json_error( array( 'message' => __( 'Post ID and at least line 1 are required.', 'raybogman-ai-content-orchestrator' ) ) );
		}

		$default_image_id = RBCO_Settings::get_default_featured_image();
		if ( $default_image_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'No default featured image configured in Settings.', 'raybogman-ai-content-orchestrator' ) ) );
		}

		// Delete the old overlay image if it was AICC-generated.
		$old_thumb_id = get_post_thumbnail_id( $post_id );
		if ( $old_thumb_id && get_post_meta( $old_thumb_id, '_rbco_overlay_image', true ) ) {
			wp_delete_attachment( $old_thumb_id, true );
		}
		delete_post_thumbnail( $post_id );

		$result = RBCO_Image_Overlay::create_and_attach(
			$post_id,
			$default_image_id,
			$line1 . ' ' . $line2,
			array(
				'color'       => RBCO_Settings::get_overlay_text_color(),
				'font_bold'   => RBCO_Settings::get_overlay_font_bold_path(),
				'font_italic' => RBCO_Settings::get_overlay_font_italic_path(),
				'lines'       => array( $line1, $line2 ),
			)
		);

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		// Store custom text in post meta for future reference.
		update_post_meta( $post_id, '_rbco_overlay_line1', $line1 );
		update_post_meta( $post_id, '_rbco_overlay_line2', $line2 );

		wp_send_json_success(
			array(
				'featured_image' => get_the_post_thumbnail_url( $post_id, 'large' ),
				'attachment_id'  => $result,
				'line1'          => $line1,
				'line2'          => $line2,
			)
		);
	}

	/**
	 * AJAX: Scan the active theme for brand colors (header, links, style.css).
	 *
	 * @return void Sends a JSON response.
	 */
	public function ajax_scan_theme_colors() {
		check_ajax_referer( 'rbco_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'raybogman-ai-content-orchestrator' ) ) );
		}

		$colors  = array();
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
			global $wp_filesystem;
			if ( empty( $wp_filesystem ) ) {
				require_once ABSPATH . 'wp-admin/includes/file.php';
				WP_Filesystem();
			}
			$css = $wp_filesystem ? $wp_filesystem->get_contents( $stylesheet_path ) : '';
			if ( ! empty( $css ) ) {
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
		$colors = array_values(
			array_filter(
				$colors,
				function ( $c ) {
					return ! in_array( $c, array( '#000000', '#ffffff', '#fff', '#000' ), true );
				}
			)
		);

		// Limit to 10 most relevant colors.
		$colors = array_slice( $colors, 0, 10 );

		if ( empty( $colors ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'No colors detected from the active theme. You can enter brand colors manually.', 'raybogman-ai-content-orchestrator' ),
				)
			);
			return;
		}

		wp_send_json_success(
			array(
				'colors'  => $colors,
				'sources' => array_unique( $sources ),
				'message' => sprintf(
					/* translators: 1: number of colors, 2: sources list */
					__( 'Found %1$d colors from: %2$s', 'raybogman-ai-content-orchestrator' ),
					count( $colors ),
					implode( ', ', array_unique( $sources ) )
				),
			)
		);
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
}
