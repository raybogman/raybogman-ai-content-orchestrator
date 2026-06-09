<?php
/**
 * Settings management.
 *
 * @package Raybogman_Content_Orchestrator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RBCO_Settings
 *
 * Handles plugin settings registration and sanitization.
 */
class RBCO_Settings {

	/**
	 * Option group name.
	 *
	 * @var string
	 */
	const OPTION_GROUP = 'rbco_settings';

	/**
	 * Settings page slug.
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'rbco-settings';

	const TAB_GENERAL  = 'rbco-tab-general';
	const TAB_CONTENT  = 'rbco-tab-content';
	const TAB_IMAGES   = 'rbco-tab-images';
	const TAB_SCANNER   = 'rbco-tab-scanner';
	const TAB_FAQ       = 'rbco-tab-faq';
	const TAB_ABOUT    = 'rbco-tab-about';

	/**
	 * Register settings.
	 */
	public static function register() {
		// ── Project Vision Section ──────────────────────────────────
		add_settings_section(
			'rbco_project_vision_section',
			__( 'Project Vision', 'raybogman-ai-content-orchestrator' ),
			array( __CLASS__, 'render_project_vision_section' ),
			self::TAB_CONTENT
		);

		register_setting( 'rbco_settings_content', 'rbco_project_vision', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_textarea_field',
			'default'           => '',
		) );

		add_settings_field(
			'rbco_project_vision',
			__( 'Baseline Instructions', 'raybogman-ai-content-orchestrator' ),
			array( __CLASS__, 'render_textarea_field' ),
			self::TAB_CONTENT,
			'rbco_project_vision_section',
			array(
				'id'          => 'rbco_project_vision',
				'rows'        => 8,
				'placeholder' => __( 'e.g. Always write in a friendly, professional tone. Our brand name is "Acme Corp". Target audience is small business owners...', 'raybogman-ai-content-orchestrator' ),
				'description' => __( 'These instructions are automatically included with every AI content generation request. Use this to define your brand voice, tone, audience, or any rules the AI should always follow.', 'raybogman-ai-content-orchestrator' ),
			)
		);

		// ── AI Provider Section ──────────────────────────────────────
		add_settings_section(
			'rbco_provider_section',
			__( 'AI Provider', 'raybogman-ai-content-orchestrator' ),
			array( __CLASS__, 'render_provider_section' ),
			self::TAB_GENERAL
		);

		// AI Provider selector.
		register_setting( 'rbco_settings_general', 'rbco_ai_provider', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'claude',
		) );

		add_settings_field(
			'rbco_ai_provider',
			__( 'Active Provider', 'raybogman-ai-content-orchestrator' ),
			array( __CLASS__, 'render_select_field' ),
			self::TAB_GENERAL,
			'rbco_provider_section',
			array(
				'id'      => 'rbco_ai_provider',
				'options' => array(
					'claude' => 'Claude (Anthropic)',
					'openai' => 'OpenAI (GPT)',
				),
				'description' => __( 'Choose which AI provider to use for content generation.', 'raybogman-ai-content-orchestrator' ),
			)
		);

		// ── Claude (Anthropic) Section ──────────────────────────────
		add_settings_section(
			'rbco_claude_section',
			__( 'Claude (Anthropic)', 'raybogman-ai-content-orchestrator' ),
			array( __CLASS__, 'render_claude_section' ),
			self::TAB_GENERAL
		);

		// Anthropic API Key.
		register_setting( 'rbco_settings_general', 'rbco_anthropic_api_key', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		) );

		add_settings_field(
			'rbco_anthropic_api_key',
			__( 'Anthropic API Key', 'raybogman-ai-content-orchestrator' ),
			array( __CLASS__, 'render_password_field' ),
			self::TAB_GENERAL,
			'rbco_claude_section',
			array(
				'id'          => 'rbco_anthropic_api_key',
				'placeholder' => 'sk-ant-api03-...',
				'description' => __( 'Your Claude API key. Sign up free at console.anthropic.com to get one.', 'raybogman-ai-content-orchestrator' ),
			)
		);

		// Claude Model.
		register_setting( 'rbco_settings_general', 'rbco_claude_model', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'claude-sonnet-4-6',
		) );

		add_settings_field(
			'rbco_claude_model',
			__( 'Claude Model', 'raybogman-ai-content-orchestrator' ),
			array( __CLASS__, 'render_select_field' ),
			self::TAB_GENERAL,
			'rbco_claude_section',
			array(
				'id'      => 'rbco_claude_model',
				'options' => array(
					'claude-sonnet-4-6'         => 'Claude Sonnet 4.6 (recommended)',
					'claude-opus-4-6'           => 'Claude Opus 4.6',
					'claude-haiku-4-5-20251001' => 'Claude Haiku 4.5',
				),
				'description' => __( 'Which Claude model to use. Sonnet is recommended for most users (fast and high quality). Opus is the most capable but slower. Haiku is the fastest and cheapest.', 'raybogman-ai-content-orchestrator' ),
			)
		);

		// ── OpenAI Section ──────────────────────────────────────────
		add_settings_section(
			'rbco_openai_section',
			__( 'OpenAI (GPT)', 'raybogman-ai-content-orchestrator' ),
			array( __CLASS__, 'render_openai_section' ),
			self::TAB_GENERAL
		);

		// OpenAI API Key.
		register_setting( 'rbco_settings_general', 'rbco_openai_api_key', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		) );

		add_settings_field(
			'rbco_openai_api_key',
			__( 'OpenAI API Key', 'raybogman-ai-content-orchestrator' ),
			array( __CLASS__, 'render_password_field' ),
			self::TAB_GENERAL,
			'rbco_openai_section',
			array(
				'id'          => 'rbco_openai_api_key',
				'placeholder' => 'sk-...',
				'description' => __( 'Your OpenAI API key. Sign up free at platform.openai.com to get one.', 'raybogman-ai-content-orchestrator' ),
			)
		);

		// OpenAI Model.
		register_setting( 'rbco_settings_general', 'rbco_openai_model', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'gpt-4o',
		) );

		add_settings_field(
			'rbco_openai_model',
			__( 'OpenAI Model', 'raybogman-ai-content-orchestrator' ),
			array( __CLASS__, 'render_select_field' ),
			self::TAB_GENERAL,
			'rbco_openai_section',
			array(
				'id'      => 'rbco_openai_model',
				'options' => array(
					'gpt-4o'      => 'GPT-4o (recommended)',
					'gpt-4o-mini' => 'GPT-4o Mini',
					'gpt-4-turbo' => 'GPT-4 Turbo',
					'gpt-4.1'     => 'GPT-4.1',
					'gpt-4.1-mini' => 'GPT-4.1 Mini',
					'gpt-4.1-nano' => 'GPT-4.1 Nano',
				),
				'description' => __( 'Which OpenAI model to use. GPT-4o is recommended for most users (fast and high quality). Mini and Nano versions are faster and cheaper but less capable.', 'raybogman-ai-content-orchestrator' ),
			)
		);

		// ── Image Provider Section ──────────────────────────────────
		add_settings_section(
			'rbco_image_provider_section',
			__( 'Featured Images', 'raybogman-ai-content-orchestrator' ),
			array( __CLASS__, 'render_image_provider_section' ),
			self::TAB_IMAGES
		);

		// Image Visual Style.
		register_setting( 'rbco_settings_images', 'rbco_image_style', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'auto',
		) );

		add_settings_field(
			'rbco_image_style',
			__( 'Image Visual Style', 'raybogman-ai-content-orchestrator' ),
			array( __CLASS__, 'render_select_field' ),
			self::TAB_IMAGES,
			'rbco_image_provider_section',
			array(
				'id'      => 'rbco_image_style',
				'options' => array(
					'auto'      => 'Auto (match blog style)',
					'REALISTIC' => 'Realistic / Photographic',
					'GENERAL'   => 'General',
					'DESIGN'    => 'Design / Graphic',
					'FICTION'   => 'Fiction / Cinematic',
				),
				'description' => __( 'The look and feel of generated images. "Auto" picks the best style based on your blog format (e.g. Storytelling gets cinematic images, Data-Driven gets infographic-style images).', 'raybogman-ai-content-orchestrator' ),
			)
		);

		// Brand Colors.
		register_setting( 'rbco_settings_images', 'rbco_brand_colors', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		) );

		add_settings_field(
			'rbco_brand_colors',
			__( 'Brand Colors', 'raybogman-ai-content-orchestrator' ),
			array( __CLASS__, 'render_brand_colors_field' ),
			self::TAB_IMAGES,
			'rbco_image_provider_section',
			array(
				'id'          => 'rbco_brand_colors',
				'placeholder' => '#1a73e8, #34a853, #ea4335',
				'description' => __( 'Your brand colors as hex codes (e.g. #1a73e8), separated by commas. The AI will try to use these colors in your featured images. Leave empty to let the AI choose. Use the "Scan Theme Colors" button to auto-detect your website\'s colors.', 'raybogman-ai-content-orchestrator' ),
			)
		);

		// Negative Prompt.
		register_setting( 'rbco_settings_images', 'rbco_image_negative_prompt', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_textarea_field',
			'default'           => '',
		) );

		add_settings_field(
			'rbco_image_negative_prompt',
			__( 'Avoid in Images', 'raybogman-ai-content-orchestrator' ),
			array( __CLASS__, 'render_textarea_field' ),
			self::TAB_IMAGES,
			'rbco_image_provider_section',
			array(
				'id'          => 'rbco_image_negative_prompt',
				'rows'        => 3,
				'placeholder' => __( 'e.g. clipart, stock photo, generic office, blurry, low quality, text overlay', 'raybogman-ai-content-orchestrator' ),
				'description' => __( 'Describe what you do NOT want in your images. For example: clipart, blurry, stock photo, text overlay. The AI will try to avoid these things.', 'raybogman-ai-content-orchestrator' ),
			)
		);

		// ── Default Featured Image Section ──────────────────────────
		add_settings_section(
			'rbco_default_image_section',
			__( 'Default Featured Image', 'raybogman-ai-content-orchestrator' ),
			array( __CLASS__, 'render_default_image_section' ),
			self::TAB_IMAGES
		);

		register_setting( 'rbco_settings_images', 'rbco_default_featured_image', array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 0,
		) );

		add_settings_field(
			'rbco_default_featured_image',
			__( 'Base Image', 'raybogman-ai-content-orchestrator' ),
			array( __CLASS__, 'render_media_upload_field' ),
			self::TAB_IMAGES,
			'rbco_default_image_section',
			array(
				'id'          => 'rbco_default_featured_image',
				'description' => __( 'Choose a background image from your media library. This image is used as the featured image when AI image generation is turned off. If title overlay is enabled, the blog title is placed on top of this image.', 'raybogman-ai-content-orchestrator' ),
			)
		);

		register_setting( 'rbco_settings_images', 'rbco_overlay_enabled', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		) );

		add_settings_field(
			'rbco_overlay_enabled',
			__( 'Title Overlay', 'raybogman-ai-content-orchestrator' ),
			array( __CLASS__, 'render_select_field' ),
			self::TAB_IMAGES,
			'rbco_default_image_section',
			array(
				'id'      => 'rbco_overlay_enabled',
				'options' => array(
					'1' => __( 'Enabled — overlay blog title on image', 'raybogman-ai-content-orchestrator' ),
					''  => __( 'Disabled', 'raybogman-ai-content-orchestrator' ),
				),
				'description' => __( 'The blog title is automatically split across 2 lines, displayed in uppercase, and centered on your background image. Each post gets its own unique featured image.', 'raybogman-ai-content-orchestrator' ),
			)
		);

		register_setting( 'rbco_settings_images', 'rbco_overlay_text_color', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '#0d5e50',
		) );

		add_settings_field(
			'rbco_overlay_text_color',
			__( 'Overlay Text Color', 'raybogman-ai-content-orchestrator' ),
			array( __CLASS__, 'render_color_field' ),
			self::TAB_IMAGES,
			'rbco_default_image_section',
			array(
				'id'          => 'rbco_overlay_text_color',
				'default'     => '#0d5e50',
				'description' => __( 'Text color for the title overlay. Used for both lines.', 'raybogman-ai-content-orchestrator' ),
			)
		);

		register_setting( 'rbco_settings_images', 'rbco_overlay_font_bold', array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 0,
		) );

		add_settings_field(
			'rbco_overlay_font_bold',
			__( 'Bold Font File', 'raybogman-ai-content-orchestrator' ),
			array( __CLASS__, 'render_media_upload_field' ),
			self::TAB_IMAGES,
			'rbco_default_image_section',
			array(
				'id'          => 'rbco_overlay_font_bold',
				'button_text' => __( 'Upload Font', 'raybogman-ai-content-orchestrator' ),
				'media_type'  => 'application',
				'description' => __( 'Upload a font file (.ttf) for the first line of text. We recommend downloading Poppins-Bold.ttf from Google Fonts for a clean, modern look.', 'raybogman-ai-content-orchestrator' ),
			)
		);

		register_setting( 'rbco_settings_images', 'rbco_overlay_font_italic', array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 0,
		) );

		add_settings_field(
			'rbco_overlay_font_italic',
			__( 'Italic Font File', 'raybogman-ai-content-orchestrator' ),
			array( __CLASS__, 'render_media_upload_field' ),
			self::TAB_IMAGES,
			'rbco_default_image_section',
			array(
				'id'          => 'rbco_overlay_font_italic',
				'button_text' => __( 'Upload Font', 'raybogman-ai-content-orchestrator' ),
				'media_type'  => 'application',
				'description' => __( 'Upload a font file (.ttf) for the second line of text. If not set, the bold font is used for both lines.', 'raybogman-ai-content-orchestrator' ),
			)
		);

		// Internal Linking toggle.
		register_setting( 'rbco_settings_content', 'rbco_internal_linking', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '1',
		) );

		add_settings_field(
			'rbco_internal_linking',
			__( 'Automatic Internal Links', 'raybogman-ai-content-orchestrator' ),
			array( __CLASS__, 'render_select_field' ),
			self::TAB_CONTENT,
			'rbco_project_vision_section',
			array(
				'id'      => 'rbco_internal_linking',
				'options' => array(
					'1' => __( 'Enabled — automatically add internal links', 'raybogman-ai-content-orchestrator' ),
					'0' => __( 'Disabled', 'raybogman-ai-content-orchestrator' ),
				),
				'description' => __( 'After the AI writes the article, the plugin scans your existing published posts and adds 3-5 relevant links within the text. This boosts your SEO by strengthening your site\'s internal link structure — no manual effort needed.', 'raybogman-ai-content-orchestrator' ),
			)
		);

		// Max internal links.
		register_setting( 'rbco_settings_content', 'rbco_max_internal_links', array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 5,
		) );

		add_settings_field(
			'rbco_max_internal_links',
			__( 'Max Internal Links', 'raybogman-ai-content-orchestrator' ),
			array( __CLASS__, 'render_number_field' ),
			self::TAB_CONTENT,
			'rbco_project_vision_section',
			array(
				'id'          => 'rbco_max_internal_links',
				'min'         => 1,
				'max'         => 15,
				'description' => __( 'Maximum number of internal links to add per post. 3-5 is recommended for most blogs. Too many links can look spammy.', 'raybogman-ai-content-orchestrator' ),
			)
		);

		// ── Scanner Section ─────────────────────────────────────────
		add_settings_section(
			'rbco_scanner_section',
			__( 'Website Scanner', 'raybogman-ai-content-orchestrator' ),
			array( __CLASS__, 'render_scanner_section' ),
			self::TAB_SCANNER
		);

		// Max Pages to Crawl.
		register_setting( 'rbco_settings_scanner', 'rbco_max_pages_to_crawl', array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 25,
		) );

		add_settings_field(
			'rbco_max_pages_to_crawl',
			__( 'Max Pages to Scan', 'raybogman-ai-content-orchestrator' ),
			array( __CLASS__, 'render_number_field' ),
			self::TAB_SCANNER,
			'rbco_scanner_section',
			array(
				'id'          => 'rbco_max_pages_to_crawl',
				'min'         => 1,
				'max'         => 100,
				'description' => __( 'How many pages the plugin will scan from the website you enter. More pages = more context for the AI, but takes longer.', 'raybogman-ai-content-orchestrator' ),
			)
		);

		// Max Context Characters.
		register_setting( 'rbco_settings_scanner', 'rbco_max_context_chars', array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 18000,
		) );

		add_settings_field(
			'rbco_max_context_chars',
			__( 'Max Text to Send to AI', 'raybogman-ai-content-orchestrator' ),
			array( __CLASS__, 'render_number_field' ),
			self::TAB_SCANNER,
			'rbco_scanner_section',
			array(
				'id'          => 'rbco_max_context_chars',
				'min'         => 5000,
				'max'         => 50000,
				'step'        => 1000,
				'description' => __( 'Maximum amount of scanned text (in characters) sent to the AI. Higher values give the AI more context but cost more. Default (18,000) works well for most sites.', 'raybogman-ai-content-orchestrator' ),
			)
		);

		// Request Timeout.
		register_setting( 'rbco_settings_scanner', 'rbco_request_timeout', array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 15,
		) );

		add_settings_field(
			'rbco_request_timeout',
			__( 'Scan Timeout (seconds)', 'raybogman-ai-content-orchestrator' ),
			array( __CLASS__, 'render_number_field' ),
			self::TAB_SCANNER,
			'rbco_scanner_section',
			array(
				'id'          => 'rbco_request_timeout',
				'min'         => 5,
				'max'         => 60,
				'description' => __( 'How long (in seconds) to wait for each page to load during scanning. Increase this if scanning fails on slow websites.', 'raybogman-ai-content-orchestrator' ),
			)
		);

	}

	/* ── Section descriptions ──────────────────────────────────── */

	public static function render_project_vision_section() {
		echo '<p>' . esc_html__( 'Set instructions that the AI always follows when writing content. These are included with every content generation.', 'raybogman-ai-content-orchestrator' ) . '</p>';
	}

	public static function render_provider_section() {
		echo '<p>' . esc_html__( 'Select which AI provider to use for generating content. You can configure both and switch between them.', 'raybogman-ai-content-orchestrator' ) . '</p>';
	}

	public static function render_claude_section() {
		echo '<p>' . esc_html__( 'Configure your Anthropic Claude API credentials and model.', 'raybogman-ai-content-orchestrator' ) . '</p>';
	}

	public static function render_openai_section() {
		echo '<p>' . esc_html__( 'Configure your OpenAI API credentials and model.', 'raybogman-ai-content-orchestrator' ) . '</p>';
	}

	public static function render_default_image_section() {
		echo '<p>' . esc_html__( 'Configure a default featured image that is used when AI image generation is not enabled. Optionally overlay the blog title on the image to create a unique branded featured image for each post.', 'raybogman-ai-content-orchestrator' ) . '</p>';
	}

	/**
	 * Render a media library upload field with preview.
	 */
	public static function render_media_upload_field( $args ) {
		$value       = (int) get_option( $args['id'], 0 );
		$button_text = isset( $args['button_text'] ) ? $args['button_text'] : __( 'Select Image', 'raybogman-ai-content-orchestrator' );
		$description = isset( $args['description'] ) ? $args['description'] : '';
		$preview_url = '';
		$file_name   = '';

		if ( $value > 0 ) {
			$url = wp_get_attachment_url( $value );
			if ( $url ) {
				$preview_url = $url;
				$file_name   = basename( get_attached_file( $value ) );
			}
		}

		$field_id = esc_attr( $args['id'] );
  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Values are from trusted plugin settings.
		printf( '<input type="hidden" id="%s" name="%s" value="%d" />', $field_id, $field_id, $value );

		printf(
			'<button type="button" class="button rbco-media-upload-btn" data-target="%s">%s</button>',
			esc_attr( $field_id ),
			esc_html( $button_text )
		);

		if ( $value > 0 ) {
			printf(
				' <button type="button" class="button rbco-media-remove-btn" data-target="%s">%s</button>',
				esc_attr( $field_id ),
				esc_html__( 'Remove', 'raybogman-ai-content-orchestrator' )
			);
		}

		// Preview area.
		echo '<div class="rbco-media-preview" data-target="' . esc_attr( $field_id ) . '" style="margin-top:8px;">';
		if ( ! empty( $preview_url ) ) {
			if ( preg_match( '/\.(jpe?g|png|gif|webp|svg)$/i', $preview_url ) ) {
				printf( '<img src="%s" style="max-width:300px;max-height:150px;border:1px solid #ccc;border-radius:4px;" />', esc_url( $preview_url ) );
			} else {
				printf(
					'<span class="dashicons dashicons-media-default" style="vertical-align:text-bottom;"></span> <code>%s</code> (#%d)',
					esc_html( $file_name ),
					intval( $value )
				);
			}
		}
		echo '</div>';

		if ( ! empty( $description ) ) {
			printf( '<p class="description">%s</p>', esc_html( $description ) );
		}

		// Inline JS for the WordPress media library modal.
		static $media_js_printed = false;
		if ( ! $media_js_printed ) {
			$media_js_printed = true;
			// Registered through the proper script API (attached to the
			// already-enqueued 'rbco-admin' handle) rather than a raw tag.
			// ob_start()/ob_get_clean() paired inside rbco_capture_inline_script().
			rbco_capture_inline_script( 'rbco-admin', function () {
				?>
			jQuery(document).ready(function($) {
				$('.rbco-media-upload-btn').on('click', function(e) {
					e.preventDefault();
					var $btn    = $(this);
					var target  = $btn.data('target');
					var $input  = $('#' + target);
					var $preview = $('.rbco-media-preview[data-target="' + target + '"]');

					var frame = wp.media({
						title: '<?php echo esc_js( __( 'Select or Upload', 'raybogman-ai-content-orchestrator' ) ); ?>',
						button: { text: '<?php echo esc_js( __( 'Use This', 'raybogman-ai-content-orchestrator' ) ); ?>' },
						multiple: false
					});

					frame.on('select', function() {
						var attachment = frame.state().get('selection').first().toJSON();
						$input.val(attachment.id);
						if (attachment.type === 'image') {
							var url = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;
							$preview.html('<img src="' + url + '" style="max-width:300px;max-height:150px;border:1px solid #ccc;border-radius:4px;" />');
						} else {
							$preview.html('<span class="dashicons dashicons-media-default" style="vertical-align:text-bottom;"></span> <code>' + attachment.filename + '</code> (#' + attachment.id + ')');
						}
						// Show remove button.
						if (!$btn.next('.rbco-media-remove-btn').length) {
							$btn.after(' <button type="button" class="button rbco-media-remove-btn" data-target="' + target + '"><?php echo esc_js( __( 'Remove', 'raybogman-ai-content-orchestrator' ) ); ?></button>');
						}
					});

					frame.open();
				});

				$(document).on('click', '.rbco-media-remove-btn', function(e) {
					e.preventDefault();
					var target = $(this).data('target');
					$('#' + target).val('0');
					$('.rbco-media-preview[data-target="' + target + '"]').empty();
					$(this).remove();
				});
			});
				<?php
			} );
		}
	}

	/**
	 * Render a checkbox field.
	 */
	public static function render_checkbox_field( $args ) {
		$value       = get_option( $args['id'], '' );
		$label       = isset( $args['label'] ) ? $args['label'] : '';
		$description = isset( $args['description'] ) ? $args['description'] : '';

		printf(
			'<input type="hidden" name="%1$s" value="0" /><label><input type="checkbox" id="%1$s" name="%1$s" value="1" %2$s /> %3$s</label>',
			esc_attr( $args['id'] ),
			checked( $value, '1', false ),
			esc_html( $label )
		);

		if ( ! empty( $description ) ) {
			printf( '<p class="description">%s</p>', esc_html( $description ) );
		}
	}

	/**
	 * Render a color picker field.
	 */
	public static function render_color_field( $args ) {
		$value       = get_option( $args['id'], $args['default'] ?? '#000000' );
		$description = isset( $args['description'] ) ? $args['description'] : '';

		printf(
			'<input type="color" id="%1$s" name="%1$s" value="%2$s" style="width:60px;height:34px;padding:2px;cursor:pointer;" />',
			esc_attr( $args['id'] ),
			esc_attr( $value )
		);
		printf(
			' <input type="text" value="%s" class="small-text" style="vertical-align:top;" oninput="document.getElementById(\'%s\').value=this.value" />',
			esc_attr( $value ),
			esc_attr( $args['id'] )
		);

		if ( ! empty( $description ) ) {
			printf( '<p class="description">%s</p>', esc_html( $description ) );
		}
	}

	public static function render_image_provider_section() {
		echo '<p>' . esc_html__( 'Featured images are generated with OpenAI (DALL-E 3) using your OpenAI API key from the General tab. Configure the look and feel below.', 'raybogman-ai-content-orchestrator' ) . '</p>';
	}

	public static function render_scanner_section() {
		echo '<p>' . esc_html__( 'Control how the plugin scans websites. When you enter a URL on the Create Content page, the plugin reads pages from that website to give the AI useful background information.', 'raybogman-ai-content-orchestrator' ) . '</p>';
	}

	/* ── Field renderers ───────────────────────────────────────── */

	/**
	 * Render a text input field.
	 *
	 * @param array $args Field arguments.
	 */
	public static function render_text_field( $args ) {
		$value = get_option( $args['id'], '' );
		printf(
			'<input type="text" id="%1$s" name="%1$s" value="%2$s" class="regular-text" placeholder="%3$s" />',
			esc_attr( $args['id'] ),
			esc_attr( $value ),
			esc_attr( $args['placeholder'] ?? '' )
		);
		if ( ! empty( $args['description'] ) ) {
			printf( '<p class="description">%s</p>', esc_html( $args['description'] ) );
		}
	}

	/**
	 * Render a password input field.
	 *
	 * @param array $args Field arguments.
	 */
	public static function render_password_field( $args ) {
		$value = get_option( $args['id'], '' );
		printf(
			'<input type="password" id="%1$s" name="%1$s" value="%2$s" class="regular-text" placeholder="%3$s" autocomplete="off" />',
			esc_attr( $args['id'] ),
			esc_attr( $value ),
			esc_attr( $args['placeholder'] ?? '' )
		);
		if ( ! empty( $args['description'] ) ) {
			printf( '<p class="description">%s</p>', esc_html( $args['description'] ) );
		}
	}

	/**
	 * Render a select field.
	 *
	 * @param array $args Field arguments.
	 */
	public static function render_select_field( $args ) {
		$value = get_option( $args['id'], '' );
		printf( '<select id="%1$s" name="%1$s">', esc_attr( $args['id'] ) );
		foreach ( $args['options'] as $key => $label ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $key ),
				selected( $value, $key, false ),
				esc_html( $label )
			);
		}
		echo '</select>';
		if ( ! empty( $args['description'] ) ) {
			printf( '<p class="description">%s</p>', esc_html( $args['description'] ) );
		}
	}

	/**
	 * Render a number input field.
	 *
	 * @param array $args Field arguments.
	 */
	public static function render_time_field( $args ) {
		$value = get_option( $args['id'], '09:00' );
		printf(
			'<input type="time" id="%1$s" name="%1$s" value="%2$s" />',
			esc_attr( $args['id'] ),
			esc_attr( $value )
		);
		if ( ! empty( $args['description'] ) ) {
			printf( '<p class="description">%s</p>', esc_html( $args['description'] ) );
		}
	}

	public static function render_number_field( $args ) {
		$value = get_option( $args['id'], '' );
		printf(
			'<input type="number" id="%1$s" name="%1$s" value="%2$s" class="small-text" min="%3$s" max="%4$s" step="%5$s" />',
			esc_attr( $args['id'] ),
			esc_attr( $value ),
			esc_attr( $args['min'] ?? 0 ),
			esc_attr( $args['max'] ?? '' ),
			esc_attr( $args['step'] ?? 1 )
		);
		if ( ! empty( $args['description'] ) ) {
			printf( '<p class="description">%s</p>', esc_html( $args['description'] ) );
		}
	}

	/**
	 * Render a textarea field.
	 *
	 * @param array $args Field arguments.
	 */
	public static function render_textarea_field( $args ) {
		$value = get_option( $args['id'], '' );
		printf(
			'<textarea id="%1$s" name="%1$s" rows="%2$s" class="large-text" placeholder="%3$s">%4$s</textarea>',
			esc_attr( $args['id'] ),
			esc_attr( $args['rows'] ?? 6 ),
			esc_attr( $args['placeholder'] ?? '' ),
			esc_textarea( $value )
		);
		if ( ! empty( $args['description'] ) ) {
			printf( '<p class="description">%s</p>', esc_html( $args['description'] ) );
		}
	}

	/* ── Getters ───────────────────────────────────────────────── */

	/**
	 * Render the Brand Colors field with a "Scan Theme Colors" button.
	 *
	 * @param array $args Field arguments.
	 */
	public static function render_brand_colors_field( $args ) {
		$value = get_option( $args['id'], '' );
		printf(
			'<input type="hidden" id="%1$s" name="%1$s" value="%2$s" />',
			esc_attr( $args['id'] ),
			esc_attr( $value )
		);

		echo '<div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:8px;">';
		echo '<div id="rbco-brand-swatches" style="display:flex; gap:4px; flex-wrap:wrap;">';

		if ( ! empty( $value ) ) {
			$colors = array_map( 'trim', explode( ',', $value ) );
			foreach ( $colors as $color ) {
				if ( preg_match( '/^#[0-9a-fA-F]{3,6}$/', $color ) ) {
					printf(
						'<span class="rbco-color-swatch rbco-color-selected" data-color="%1$s" style="display:inline-block;width:28px;height:28px;background:%1$s;border:3px solid #2271b1;border-radius:4px;cursor:pointer;position:relative;" title="%1$s"><span style="position:absolute;bottom:-2px;right:-2px;background:#2271b1;color:#fff;width:12px;height:12px;border-radius:50%%;font-size:9px;line-height:12px;text-align:center;">&#10003;</span></span>',
						esc_attr( $color )
					);
				}
			}
		}

		echo '</div>';
		echo ' <button type="button" class="button" id="rbco-scan-theme-colors">';
		echo '<span class="dashicons dashicons-art" style="vertical-align:text-bottom; font-size:16px; width:16px; height:16px; margin-right:4px;"></span>';
		esc_html_e( 'Scan Theme Colors', 'raybogman-ai-content-orchestrator' );
		echo '</button>';
		echo '<span style="color:#787c82; font-size:12px;" id="rbco-color-count">';
		if ( ! empty( $value ) ) {
   /* translators: %s: dynamic value */
			printf( esc_html__( '%d/4 selected', 'raybogman-ai-content-orchestrator' ), intval( min( count( array_filter( $colors ) ), 4 ) ) );
		}
		echo '</span>';
		echo '</div>';

		echo '<div id="rbco-scan-colors-result" style="margin-top:8px;"></div>';

		if ( ! empty( $args['description'] ) ) {
			printf( '<p class="description">%s</p>', wp_kses( $args['description'], array( 'strong' => array(), 'em' => array(), 'a' => array( 'href' => array() ) ) ) );
		}
	}

	/* ── Getters ───────────────────────────────────────────────── */

	public static function get_project_vision() {
		return trim( get_option( 'rbco_project_vision', '' ) );
	}

	public static function get_ai_provider() {
		return get_option( 'rbco_ai_provider', 'claude' );
	}

	public static function get_anthropic_api_key() {
		return get_option( 'rbco_anthropic_api_key', '' );
	}

	public static function get_claude_model() {
		return get_option( 'rbco_claude_model', 'claude-sonnet-4-6' );
	}

	public static function get_openai_api_key() {
		return get_option( 'rbco_openai_api_key', '' );
	}

	public static function get_openai_model() {
		return get_option( 'rbco_openai_model', 'gpt-4o' );
	}

	public static function is_internal_linking_enabled() {
		return '1' === get_option( 'rbco_internal_linking', '1' );
	}

	public static function get_max_internal_links() {
		return (int) get_option( 'rbco_max_internal_links', 5 );
	}

	public static function get_default_featured_image() {
		return (int) get_option( 'rbco_default_featured_image', 0 );
	}

	public static function is_overlay_enabled() {
		return '1' === get_option( 'rbco_overlay_enabled', '' );
	}

	public static function get_overlay_text_color() {
		return get_option( 'rbco_overlay_text_color', '#0d5e50' );
	}

	public static function get_overlay_font_bold_path() {
		$id = (int) get_option( 'rbco_overlay_font_bold', 0 );
		return $id > 0 ? get_attached_file( $id ) : '';
	}

	public static function get_overlay_font_italic_path() {
		$id = (int) get_option( 'rbco_overlay_font_italic', 0 );
		return $id > 0 ? get_attached_file( $id ) : '';
	}

	public static function get_image_provider() {
		return 'openai';
	}

	public static function get_image_style() {
		return get_option( 'rbco_image_style', 'auto' );
	}

	public static function get_brand_colors() {
		$raw = trim( get_option( 'rbco_brand_colors', '' ) );
		if ( empty( $raw ) ) {
			return array();
		}
		$colors = array_map( 'trim', explode( ',', $raw ) );
		// Validate hex codes.
		return array_values( array_filter( $colors, function ( $c ) {
			return preg_match( '/^#[0-9a-fA-F]{6}$/', $c );
		} ) );
	}

	public static function get_image_negative_prompt() {
		return trim( get_option( 'rbco_image_negative_prompt', '' ) );
	}

	/**
	 * Get the API key for the active image provider.
	 *
	 * @return string
	 */
	public static function get_image_api_key() {
		return self::get_openai_api_key();
	}

	/**
	 * Check if the active image provider is configured.
	 *
	 * @return bool
	 */
	public static function is_image_configured() {
		return ! empty( self::get_image_api_key() );
	}

	public static function get_max_pages() {
		return (int) get_option( 'rbco_max_pages_to_crawl', 25 );
	}

	public static function get_max_context_chars() {
		return (int) get_option( 'rbco_max_context_chars', 18000 );
	}

	public static function get_request_timeout() {
		return (int) get_option( 'rbco_request_timeout', 15 );
	}

	/**
	 * Get the active API key based on provider selection.
	 *
	 * @return string
	 */
	public static function get_active_api_key() {
		$provider = self::get_ai_provider();
		return 'openai' === $provider ? self::get_openai_api_key() : self::get_anthropic_api_key();
	}

	/**
	 * Get the active model based on provider selection.
	 *
	 * @return string
	 */
	public static function get_active_model() {
		$provider = self::get_ai_provider();
		return 'openai' === $provider ? self::get_openai_model() : self::get_claude_model();
	}

	/**
	 * Check if the active provider is configured.
	 *
	 * @return bool
	 */
	public static function is_configured() {
		return ! empty( self::get_active_api_key() );
	}

	/* ── Saved URLs ─────────────────────────────────────────────── */

	/**
	 * Get all saved URLs for quick reuse.
	 *
	 * @return array Array of URL strings.
	 */
	public static function get_saved_urls() {
		$urls = get_option( 'rbco_saved_urls', array() );
		if ( ! is_array( $urls ) ) {
			return array();
		}
		sort( $urls );
		return array_values( $urls );
	}

	/**
	 * Save a URL for future reuse. Deduplicates automatically.
	 *
	 * @param string $url URL to save.
	 * @return bool True if saved (or already saved), false on invalid URL.
	 */
	public static function save_url( $url ) {
		$url = esc_url_raw( trim( $url ) );
		if ( empty( $url ) ) {
			return false;
		}
		$url = untrailingslashit( $url );

		$urls = self::get_saved_urls();
		if ( ! in_array( $url, $urls, true ) ) {
			$urls[] = $url;
			sort( $urls );
			update_option( 'rbco_saved_urls', $urls );
		}
		return true;
	}

	/**
	 * Remove a saved URL.
	 *
	 * @param string $url URL to remove.
	 * @return bool True if removed, false if not found.
	 */
	public static function remove_url( $url ) {
		$url  = untrailingslashit( esc_url_raw( trim( $url ) ) );
		$urls = self::get_saved_urls();
		$new  = array_values( array_filter( $urls, function ( $u ) use ( $url ) {
			return untrailingslashit( $u ) !== $url;
		} ) );

		if ( count( $new ) !== count( $urls ) ) {
			update_option( 'rbco_saved_urls', $new );
			return true;
		}
		return false;
	}

	/**
	 * Get tab definitions for the settings page.
	 *
	 * @return array Associative array of tab slug => label.
	 */
	public static function get_tabs() {
		return array(
			'general'  => __( 'General', 'raybogman-ai-content-orchestrator' ),
			'content'  => __( 'Content', 'raybogman-ai-content-orchestrator' ),
			'images'   => __( 'Images', 'raybogman-ai-content-orchestrator' ),
			'scanner'  => __( 'Scanner', 'raybogman-ai-content-orchestrator' ),
			'faq'      => __( 'FAQ', 'raybogman-ai-content-orchestrator' ),
			'about'    => __( 'About', 'raybogman-ai-content-orchestrator' ),
		);
	}

	/**
	 * Get the option names that belong to each settings tab.
	 *
	 * @return array Tab slug => array of option names.
	 */
	public static function get_tab_options() {
		return array(
			'general' => array(
				'rbco_ai_provider',
				'rbco_anthropic_api_key',
				'rbco_claude_model',
				'rbco_openai_api_key',
				'rbco_openai_model',
			),
			'content' => array(
				'rbco_project_vision',
				'rbco_internal_linking',
				'rbco_max_internal_links',
			),
			'images' => array(
				'rbco_image_style',
				'rbco_brand_colors',
				'rbco_image_negative_prompt',
				'rbco_default_featured_image',
				'rbco_overlay_enabled',
				'rbco_overlay_text_color',
				'rbco_overlay_font_bold',
				'rbco_overlay_font_italic',
			),
			'scanner' => array(
				'rbco_max_pages_to_crawl',
				'rbco_max_context_chars',
				'rbco_request_timeout',
			),
		);
	}

	/**
	 * Handle custom settings save (bypasses options.php).
	 */
	public static function handle_save() {
		if ( empty( $_POST['rbco_save_tab'] ) || empty( $_POST['rbco_settings_nonce'] ) ) {
			return;
		}

		$tab = sanitize_text_field( wp_unslash( $_POST['rbco_save_tab'] ) );

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['rbco_settings_nonce'] ) ), 'rbco_save_settings_' . $tab ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$tab_options = self::get_tab_options();
		if ( ! isset( $tab_options[ $tab ] ) ) {
			return;
		}

		$registered = get_registered_settings();

		foreach ( $tab_options[ $tab ] as $option ) {
			if ( ! isset( $_POST[ $option ] ) ) {
				continue;
			}

			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Raw value is sanitized below before use; never stored unsanitized.
			$raw = wp_unslash( $_POST[ $option ] );

			if ( isset( $registered[ $option ]['sanitize_callback'] ) ) {
				// Use the option's own registered sanitizer.
				$value = call_user_func( $registered[ $option ]['sanitize_callback'], $raw );
			} elseif ( is_array( $raw ) ) {
				// Fallback: deep-sanitize every array element.
				$value = map_deep( $raw, 'sanitize_text_field' );
			} else {
				// Fallback: textarea-safe sanitization (preserves newlines for
				// multi-line fields such as the project vision).
				$value = sanitize_textarea_field( $raw );
			}

			update_option( $option, $value );
		}

		$redirect = add_query_arg(
			array(
				'page'             => 'rbco-settings',
				'tab'              => $tab,
				'settings-updated' => 'true',
			),
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $redirect );
		exit;
	}
}
