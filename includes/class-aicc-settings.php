<?php
/**
 * Settings management.
 *
 * @package AI_Content_Creator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class AICC_Settings
 *
 * Handles plugin settings registration and sanitization.
 */
class AICC_Settings {

	/**
	 * Option group name.
	 *
	 * @var string
	 */
	const OPTION_GROUP = 'aicc_settings';

	/**
	 * Settings page slug.
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'aicc-settings';

	const TAB_GENERAL  = 'aicc-tab-general';
	const TAB_CONTENT  = 'aicc-tab-content';
	const TAB_IMAGES   = 'aicc-tab-images';
	const TAB_THRIVE   = 'aicc-tab-thrive';
	const TAB_LINKEDIN  = 'aicc-tab-linkedin';
	const TAB_INSTAGRAM = 'aicc-tab-instagram';
	const TAB_SCANNER   = 'aicc-tab-scanner';
	const TAB_FAQ       = 'aicc-tab-faq';
	const TAB_ABOUT    = 'aicc-tab-about';

	/**
	 * Register settings.
	 */
	public static function register() {
		// ── Project Vision Section ──────────────────────────────────
		add_settings_section(
			'aicc_project_vision_section',
			__( 'Project Vision', 'ai-content-orchestrator' ),
			array( __CLASS__, 'render_project_vision_section' ),
			self::TAB_CONTENT
		);

		register_setting( 'aicc_settings_content', 'aicc_project_vision', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_textarea_field',
			'default'           => '',
		) );

		add_settings_field(
			'aicc_project_vision',
			__( 'Baseline Instructions', 'ai-content-orchestrator' ),
			array( __CLASS__, 'render_textarea_field' ),
			self::TAB_CONTENT,
			'aicc_project_vision_section',
			array(
				'id'          => 'aicc_project_vision',
				'rows'        => 8,
				'placeholder' => __( 'e.g. Always write in a friendly, professional tone. Our brand name is "Acme Corp". Target audience is small business owners...', 'ai-content-orchestrator' ),
				'description' => __( 'These instructions are automatically included with every AI content generation request. Use this to define your brand voice, tone, audience, or any rules the AI should always follow.', 'ai-content-orchestrator' ),
			)
		);

		// ── AI Provider Section ──────────────────────────────────────
		add_settings_section(
			'aicc_provider_section',
			__( 'AI Provider', 'ai-content-orchestrator' ),
			array( __CLASS__, 'render_provider_section' ),
			self::TAB_GENERAL
		);

		// AI Provider selector.
		register_setting( 'aicc_settings_general', 'aicc_ai_provider', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'claude',
		) );

		add_settings_field(
			'aicc_ai_provider',
			__( 'Active Provider', 'ai-content-orchestrator' ),
			array( __CLASS__, 'render_select_field' ),
			self::TAB_GENERAL,
			'aicc_provider_section',
			array(
				'id'      => 'aicc_ai_provider',
				'options' => array(
					'claude' => 'Claude (Anthropic)',
					'openai' => 'OpenAI (GPT)',
				),
				'description' => __( 'Choose which AI provider to use for content generation.', 'ai-content-orchestrator' ),
			)
		);

		// ── Claude (Anthropic) Section ──────────────────────────────
		add_settings_section(
			'aicc_claude_section',
			__( 'Claude (Anthropic)', 'ai-content-orchestrator' ),
			array( __CLASS__, 'render_claude_section' ),
			self::TAB_GENERAL
		);

		// Anthropic API Key.
		register_setting( 'aicc_settings_general', 'aicc_anthropic_api_key', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		) );

		add_settings_field(
			'aicc_anthropic_api_key',
			__( 'Anthropic API Key', 'ai-content-orchestrator' ),
			array( __CLASS__, 'render_password_field' ),
			self::TAB_GENERAL,
			'aicc_claude_section',
			array(
				'id'          => 'aicc_anthropic_api_key',
				'placeholder' => 'sk-ant-api03-...',
				'description' => __( 'Your Claude API key. Sign up free at console.anthropic.com to get one.', 'ai-content-orchestrator' ),
			)
		);

		// Claude Model.
		register_setting( 'aicc_settings_general', 'aicc_claude_model', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'claude-sonnet-4-6',
		) );

		add_settings_field(
			'aicc_claude_model',
			__( 'Claude Model', 'ai-content-orchestrator' ),
			array( __CLASS__, 'render_select_field' ),
			self::TAB_GENERAL,
			'aicc_claude_section',
			array(
				'id'      => 'aicc_claude_model',
				'options' => array(
					'claude-sonnet-4-6'         => 'Claude Sonnet 4.6 (recommended)',
					'claude-opus-4-6'           => 'Claude Opus 4.6',
					'claude-haiku-4-5-20251001' => 'Claude Haiku 4.5',
				),
				'description' => __( 'Which Claude model to use. Sonnet is recommended for most users (fast and high quality). Opus is the most capable but slower. Haiku is the fastest and cheapest.', 'ai-content-orchestrator' ),
			)
		);

		// ── OpenAI Section ──────────────────────────────────────────
		add_settings_section(
			'aicc_openai_section',
			__( 'OpenAI (GPT)', 'ai-content-orchestrator' ),
			array( __CLASS__, 'render_openai_section' ),
			self::TAB_GENERAL
		);

		// OpenAI API Key.
		register_setting( 'aicc_settings_general', 'aicc_openai_api_key', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		) );

		add_settings_field(
			'aicc_openai_api_key',
			__( 'OpenAI API Key', 'ai-content-orchestrator' ),
			array( __CLASS__, 'render_password_field' ),
			self::TAB_GENERAL,
			'aicc_openai_section',
			array(
				'id'          => 'aicc_openai_api_key',
				'placeholder' => 'sk-...',
				'description' => __( 'Your OpenAI API key. Sign up free at platform.openai.com to get one.', 'ai-content-orchestrator' ),
			)
		);

		// OpenAI Model.
		register_setting( 'aicc_settings_general', 'aicc_openai_model', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'gpt-4o',
		) );

		add_settings_field(
			'aicc_openai_model',
			__( 'OpenAI Model', 'ai-content-orchestrator' ),
			array( __CLASS__, 'render_select_field' ),
			self::TAB_GENERAL,
			'aicc_openai_section',
			array(
				'id'      => 'aicc_openai_model',
				'options' => array(
					'gpt-4o'      => 'GPT-4o (recommended)',
					'gpt-4o-mini' => 'GPT-4o Mini',
					'gpt-4-turbo' => 'GPT-4 Turbo',
					'gpt-4.1'     => 'GPT-4.1',
					'gpt-4.1-mini' => 'GPT-4.1 Mini',
					'gpt-4.1-nano' => 'GPT-4.1 Nano',
				),
				'description' => __( 'Which OpenAI model to use. GPT-4o is recommended for most users (fast and high quality). Mini and Nano versions are faster and cheaper but less capable.', 'ai-content-orchestrator' ),
			)
		);

		// ── Image Provider Section ──────────────────────────────────
		add_settings_section(
			'aicc_image_provider_section',
			__( 'Featured Image Provider', 'ai-content-orchestrator' ),
			array( __CLASS__, 'render_image_provider_section' ),
			self::TAB_IMAGES
		);

		// Image Provider selector.
		register_setting( 'aicc_settings_images', 'aicc_image_provider', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'openai',
		) );

		add_settings_field(
			'aicc_image_provider',
			__( 'Image Provider', 'ai-content-orchestrator' ),
			array( __CLASS__, 'render_select_field' ),
			self::TAB_IMAGES,
			'aicc_image_provider_section',
			array(
				'id'      => 'aicc_image_provider',
				'options' => array(
					'openai'   => 'OpenAI (DALL-E 3)',
					'ideogram' => aicc_is_pro() ? 'Ideogram' : 'Ideogram (Enterprise)',
				),
				'description' => __( 'Choose which service creates your featured images. OpenAI (DALL-E 3) produces realistic images. Ideogram produces stylized, high-quality images. Each requires its own API key.', 'ai-content-orchestrator' ),
			)
		);

		// Ideogram API Key.
		register_setting( 'aicc_settings_images', 'aicc_ideogram_api_key', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		) );

		add_settings_field(
			'aicc_ideogram_api_key',
			__( 'Ideogram API Key', 'ai-content-orchestrator' ),
			array( __CLASS__, 'render_password_field' ),
			self::TAB_IMAGES,
			'aicc_image_provider_section',
			array(
				'id'          => 'aicc_ideogram_api_key',
				'placeholder' => 'ig-...',
				'description' => __( 'Your Ideogram API key. Sign up at ideogram.ai/manage-api to get one.', 'ai-content-orchestrator' ),
			)
		);

		// Image Visual Style (Ideogram style_type).
		register_setting( 'aicc_settings_images', 'aicc_image_style', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'auto',
		) );

		add_settings_field(
			'aicc_image_style',
			__( 'Image Visual Style', 'ai-content-orchestrator' ),
			array( __CLASS__, 'render_select_field' ),
			self::TAB_IMAGES,
			'aicc_image_provider_section',
			array(
				'id'      => 'aicc_image_style',
				'options' => array(
					'auto'      => 'Auto (match blog style)',
					'REALISTIC' => 'Realistic / Photographic',
					'GENERAL'   => 'General',
					'DESIGN'    => 'Design / Graphic',
					'FICTION'   => 'Fiction / Cinematic',
				),
				'description' => __( 'The look and feel of generated images. "Auto" picks the best style based on your blog format (e.g. Storytelling gets cinematic images, Data-Driven gets infographic-style images). Works with both image providers.', 'ai-content-orchestrator' ),
			)
		);

		// Brand Colors.
		register_setting( 'aicc_settings_images', 'aicc_brand_colors', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		) );

		add_settings_field(
			'aicc_brand_colors',
			__( 'Brand Colors', 'ai-content-orchestrator' ),
			array( __CLASS__, 'render_brand_colors_field' ),
			self::TAB_IMAGES,
			'aicc_image_provider_section',
			array(
				'id'          => 'aicc_brand_colors',
				'placeholder' => '#1a73e8, #34a853, #ea4335',
				'description' => __( 'Your brand colors as hex codes (e.g. #1a73e8), separated by commas. The AI will try to use these colors in your featured images. Leave empty to let the AI choose. Use the "Scan Theme Colors" button to auto-detect your website\'s colors.', 'ai-content-orchestrator' ),
			)
		);

		// Negative Prompt.
		register_setting( 'aicc_settings_images', 'aicc_image_negative_prompt', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_textarea_field',
			'default'           => '',
		) );

		add_settings_field(
			'aicc_image_negative_prompt',
			__( 'Avoid in Images', 'ai-content-orchestrator' ),
			array( __CLASS__, 'render_textarea_field' ),
			self::TAB_IMAGES,
			'aicc_image_provider_section',
			array(
				'id'          => 'aicc_image_negative_prompt',
				'rows'        => 3,
				'placeholder' => __( 'e.g. clipart, stock photo, generic office, blurry, low quality, text overlay', 'ai-content-orchestrator' ),
				'description' => __( 'Describe what you do NOT want in your images. For example: clipart, blurry, stock photo, text overlay. The AI will try to avoid these things.', 'ai-content-orchestrator' ),
			)
		);

		// ── Default Featured Image Section ──────────────────────────
		add_settings_section(
			'aicc_default_image_section',
			__( 'Default Featured Image', 'ai-content-orchestrator' ),
			array( __CLASS__, 'render_default_image_section' ),
			self::TAB_IMAGES
		);

		register_setting( 'aicc_settings_images', 'aicc_default_featured_image', array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 0,
		) );

		add_settings_field(
			'aicc_default_featured_image',
			__( 'Base Image', 'ai-content-orchestrator' ),
			array( __CLASS__, 'render_media_upload_field' ),
			self::TAB_IMAGES,
			'aicc_default_image_section',
			array(
				'id'          => 'aicc_default_featured_image',
				'description' => __( 'Choose a background image from your media library. This image is used as the featured image when AI image generation is turned off. If title overlay is enabled, the blog title is placed on top of this image.', 'ai-content-orchestrator' ),
			)
		);

		register_setting( 'aicc_settings_images', 'aicc_overlay_enabled', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		) );

		add_settings_field(
			'aicc_overlay_enabled',
			__( 'Title Overlay', 'ai-content-orchestrator' ),
			array( __CLASS__, 'render_select_field' ),
			self::TAB_IMAGES,
			'aicc_default_image_section',
			array(
				'id'      => 'aicc_overlay_enabled',
				'options' => array(
					'1' => __( 'Enabled — overlay blog title on image', 'ai-content-orchestrator' ),
					''  => __( 'Disabled', 'ai-content-orchestrator' ),
				),
				'description' => __( 'The blog title is automatically split across 2 lines, displayed in uppercase, and centered on your background image. Each post gets its own unique featured image.', 'ai-content-orchestrator' ),
			)
		);

		register_setting( 'aicc_settings_images', 'aicc_overlay_text_color', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '#0d5e50',
		) );

		add_settings_field(
			'aicc_overlay_text_color',
			__( 'Overlay Text Color', 'ai-content-orchestrator' ),
			array( __CLASS__, 'render_color_field' ),
			self::TAB_IMAGES,
			'aicc_default_image_section',
			array(
				'id'          => 'aicc_overlay_text_color',
				'default'     => '#0d5e50',
				'description' => __( 'Text color for the title overlay. Used for both lines.', 'ai-content-orchestrator' ),
			)
		);

		register_setting( 'aicc_settings_images', 'aicc_overlay_font_bold', array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 0,
		) );

		add_settings_field(
			'aicc_overlay_font_bold',
			__( 'Bold Font File', 'ai-content-orchestrator' ),
			array( __CLASS__, 'render_media_upload_field' ),
			self::TAB_IMAGES,
			'aicc_default_image_section',
			array(
				'id'          => 'aicc_overlay_font_bold',
				'button_text' => __( 'Upload Font', 'ai-content-orchestrator' ),
				'media_type'  => 'application',
				'description' => __( 'Upload a font file (.ttf) for the first line of text. We recommend downloading Poppins-Bold.ttf from Google Fonts for a clean, modern look.', 'ai-content-orchestrator' ),
			)
		);

		register_setting( 'aicc_settings_images', 'aicc_overlay_font_italic', array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 0,
		) );

		add_settings_field(
			'aicc_overlay_font_italic',
			__( 'Italic Font File', 'ai-content-orchestrator' ),
			array( __CLASS__, 'render_media_upload_field' ),
			self::TAB_IMAGES,
			'aicc_default_image_section',
			array(
				'id'          => 'aicc_overlay_font_italic',
				'button_text' => __( 'Upload Font', 'ai-content-orchestrator' ),
				'media_type'  => 'application',
				'description' => __( 'Upload a font file (.ttf) for the second line of text. If not set, the bold font is used for both lines.', 'ai-content-orchestrator' ),
			)
		);

		// Default Output Format (pre-selects the dropdown on Create Content).
		register_setting( 'aicc_settings_content', 'aicc_default_output_format', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'wordpress',
		) );

		add_settings_field(
			'aicc_default_output_format',
			__( 'Default Output Format', 'ai-content-orchestrator' ),
			array( __CLASS__, 'render_select_field' ),
			self::TAB_CONTENT,
			'aicc_project_vision_section',
			array(
				'id'      => 'aicc_default_output_format',
				'options' => array(
					'wordpress' => 'WordPress (Standard)',
					'thrive'    => 'Thrive Architect',
				),
				'description' => __( 'The output format that is selected by default when you create new content. You can still change it for each individual post.', 'ai-content-orchestrator' ),
			)
		);

		// Internal Linking toggle.
		register_setting( 'aicc_settings_content', 'aicc_internal_linking', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '1',
		) );

		add_settings_field(
			'aicc_internal_linking',
			__( 'Automatic Internal Links', 'ai-content-orchestrator' ),
			array( __CLASS__, 'render_select_field' ),
			self::TAB_CONTENT,
			'aicc_project_vision_section',
			array(
				'id'      => 'aicc_internal_linking',
				'options' => array(
					'1' => __( 'Enabled — automatically add internal links', 'ai-content-orchestrator' ),
					'0' => __( 'Disabled', 'ai-content-orchestrator' ),
				),
				'description' => __( 'After the AI writes the article, the plugin scans your existing published posts and adds 3-5 relevant links within the text. This boosts your SEO by strengthening your site\'s internal link structure — no manual effort needed.', 'ai-content-orchestrator' ),
			)
		);

		// Max internal links.
		register_setting( 'aicc_settings_content', 'aicc_max_internal_links', array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 5,
		) );

		add_settings_field(
			'aicc_max_internal_links',
			__( 'Max Internal Links', 'ai-content-orchestrator' ),
			array( __CLASS__, 'render_number_field' ),
			self::TAB_CONTENT,
			'aicc_project_vision_section',
			array(
				'id'          => 'aicc_max_internal_links',
				'min'         => 1,
				'max'         => 15,
				'description' => __( 'Maximum number of internal links to add per post. 3-5 is recommended for most blogs. Too many links can look spammy.', 'ai-content-orchestrator' ),
			)
		);

		// Link Placement.
		register_setting( 'aicc_settings_content', 'aicc_link_placement', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'both',
		) );

		add_settings_field(
			'aicc_link_placement',
			__( 'Link Placement', 'ai-content-orchestrator' ),
			array( __CLASS__, 'render_select_field' ),
			self::TAB_CONTENT,
			'aicc_project_vision_section',
			array(
				'id'      => 'aicc_link_placement',
				'options' => array(
					'both'   => __( 'Inline + Related Articles section (recommended)', 'ai-content-orchestrator' ),
					'inline' => __( 'Inline only — links spread naturally through paragraphs', 'ai-content-orchestrator' ),
					'footer' => __( 'Related Articles section only — clean list at the bottom', 'ai-content-orchestrator' ),
				),
				'description' => __( 'Where to place internal links. "Inline" inserts links within your paragraphs (best for SEO). "Related Articles" adds a styled section at the bottom (best for readers). "Both" tries inline first and puts remaining links in the footer section.', 'ai-content-orchestrator' ),
			)
		);

		// Competitor Gap Analysis toggle.
		register_setting( 'aicc_settings_content', 'aicc_competitor_analysis', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '0',
		) );

		add_settings_field(
			'aicc_competitor_analysis',
			__( 'Competitor Gap Analysis', 'ai-content-orchestrator' ),
			array( __CLASS__, 'render_select_field' ),
			self::TAB_CONTENT,
			'aicc_project_vision_section',
			array(
				'id'      => 'aicc_competitor_analysis',
				'options' => array(
					'1' => __( 'Enabled — analyze competitors for every post', 'ai-content-orchestrator' ),
					'0' => __( 'Disabled', 'ai-content-orchestrator' ),
				),
				'description' => __( 'Before writing, the AI scans what top Google results typically cover for your keyword, then identifies 2-3 topics they miss. Your article is written to fill those gaps — giving you a competitive edge. You can still toggle this per post on the Create Content page.', 'ai-content-orchestrator' ),
			)
		);

		// Publishing Schedule defaults.
		register_setting( 'aicc_settings_content', 'aicc_schedule_frequency', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => 'none',
		) );

		add_settings_field(
			'aicc_schedule_frequency',
			__( 'Publishing Schedule', 'ai-content-orchestrator' ),
			array( __CLASS__, 'render_select_field' ),
			self::TAB_CONTENT,
			'aicc_project_vision_section',
			array(
				'id'      => 'aicc_schedule_frequency',
				'options' => array(
					'none'      => __( 'No schedule — publish dates set manually', 'ai-content-orchestrator' ),
					'daily'     => __( 'Daily — one post per day', 'ai-content-orchestrator' ),
					'every2'    => __( 'Every 2 days', 'ai-content-orchestrator' ),
					'every3'    => __( 'Every 3 days', 'ai-content-orchestrator' ),
					'weekly'    => __( 'Weekly — one post per week', 'ai-content-orchestrator' ),
					'biweekly'  => __( 'Bi-weekly — every two weeks', 'ai-content-orchestrator' ),
					'monthly'   => __( 'Monthly — one post per month', 'ai-content-orchestrator' ),
				),
				'description' => __( 'Default publishing frequency for Bulk Create. When set, the "Auto-fill Dates" button on the Bulk Create page uses this interval. You can override it per batch.', 'ai-content-orchestrator' ),
			)
		);

		register_setting( 'aicc_settings_content', 'aicc_schedule_time', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '09:00',
		) );

		add_settings_field(
			'aicc_schedule_time',
			__( 'Default Publish Time', 'ai-content-orchestrator' ),
			array( __CLASS__, 'render_time_field' ),
			self::TAB_CONTENT,
			'aicc_project_vision_section',
			array(
				'id'          => 'aicc_schedule_time',
				'description' => __( 'The time of day to publish posts. Research shows Tuesday-Wednesday between 9-11 AM gets the most organic traffic.', 'ai-content-orchestrator' ),
			)
		);

		register_setting( 'aicc_settings_content', 'aicc_schedule_skip_weekends', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '0',
		) );

		add_settings_field(
			'aicc_schedule_skip_weekends',
			__( 'Skip Weekends', 'ai-content-orchestrator' ),
			array( __CLASS__, 'render_select_field' ),
			self::TAB_CONTENT,
			'aicc_project_vision_section',
			array(
				'id'      => 'aicc_schedule_skip_weekends',
				'options' => array(
					'1' => __( 'Yes — only schedule on weekdays (Mon-Fri)', 'ai-content-orchestrator' ),
					'0' => __( 'No — include weekends', 'ai-content-orchestrator' ),
				),
				'description' => __( 'When enabled, auto-filled dates skip Saturday and Sunday.', 'ai-content-orchestrator' ),
			)
		);

		// Publish notification email.
		register_setting( 'aicc_settings_content', 'aicc_notify_emails', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		) );

		add_settings_field(
			'aicc_notify_emails',
			__( 'Publish Notification', 'ai-content-orchestrator' ),
			array( __CLASS__, 'render_text_field' ),
			self::TAB_CONTENT,
			'aicc_project_vision_section',
			array(
				'id'          => 'aicc_notify_emails',
				'placeholder' => 'you@example.com, team@example.com',
				'description' => __( 'Email addresses to notify when a scheduled post is published (comma-separated). Leave empty to disable notifications.', 'ai-content-orchestrator' ),
			)
		);

		// ── Thrive Architect Section ────────────────────────────────
		add_settings_section(
			'aicc_thrive_section',
			__( 'Thrive Architect', 'ai-content-orchestrator' ),
			array( __CLASS__, 'render_thrive_section' ),
			self::TAB_THRIVE
		);

		register_setting( 'aicc_settings_thrive', 'aicc_thrive_toc_id', array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 0,
		) );

		add_settings_field(
			'aicc_thrive_toc_id',
			__( 'Table of Contents', 'ai-content-orchestrator' ),
			array( __CLASS__, 'render_thrive_library_dropdown' ),
			self::TAB_THRIVE,
			'aicc_thrive_section',
			array(
				'id'          => 'aicc_thrive_toc_id',
				'description' => __( 'Choose a saved Table of Contents block from your Thrive library. It will be placed right after the introduction paragraph. Leave empty if you don\'t want a Table of Contents.', 'ai-content-orchestrator' ),
			)
		);

		register_setting( 'aicc_settings_thrive', 'aicc_thrive_cta_symbol_id', array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 0,
		) );

		add_settings_field(
			'aicc_thrive_cta_symbol_id',
			__( 'Call-to-Action Button', 'ai-content-orchestrator' ),
			array( __CLASS__, 'render_thrive_library_dropdown' ),
			self::TAB_THRIVE,
			'aicc_thrive_section',
			array(
				'id'          => 'aicc_thrive_cta_symbol_id',
				'description' => __( 'Choose a saved call-to-action block from your Thrive library (e.g. your "Download" button). It appears after the Table of Contents and at the bottom of every post.', 'ai-content-orchestrator' ),
			)
		);

		// ── Scanner Section ─────────────────────────────────────────
		add_settings_section(
			'aicc_scanner_section',
			__( 'Website Scanner', 'ai-content-orchestrator' ),
			array( __CLASS__, 'render_scanner_section' ),
			self::TAB_SCANNER
		);

		// Max Pages to Crawl.
		register_setting( 'aicc_settings_scanner', 'aicc_max_pages_to_crawl', array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 25,
		) );

		add_settings_field(
			'aicc_max_pages_to_crawl',
			__( 'Max Pages to Scan', 'ai-content-orchestrator' ),
			array( __CLASS__, 'render_number_field' ),
			self::TAB_SCANNER,
			'aicc_scanner_section',
			array(
				'id'          => 'aicc_max_pages_to_crawl',
				'min'         => 1,
				'max'         => 100,
				'description' => __( 'How many pages the plugin will scan from the website you enter. More pages = more context for the AI, but takes longer.', 'ai-content-orchestrator' ),
			)
		);

		// Max Context Characters.
		register_setting( 'aicc_settings_scanner', 'aicc_max_context_chars', array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 18000,
		) );

		add_settings_field(
			'aicc_max_context_chars',
			__( 'Max Text to Send to AI', 'ai-content-orchestrator' ),
			array( __CLASS__, 'render_number_field' ),
			self::TAB_SCANNER,
			'aicc_scanner_section',
			array(
				'id'          => 'aicc_max_context_chars',
				'min'         => 5000,
				'max'         => 50000,
				'step'        => 1000,
				'description' => __( 'Maximum amount of scanned text (in characters) sent to the AI. Higher values give the AI more context but cost more. Default (18,000) works well for most sites.', 'ai-content-orchestrator' ),
			)
		);

		// Request Timeout.
		register_setting( 'aicc_settings_scanner', 'aicc_request_timeout', array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 15,
		) );

		add_settings_field(
			'aicc_request_timeout',
			__( 'Scan Timeout (seconds)', 'ai-content-orchestrator' ),
			array( __CLASS__, 'render_number_field' ),
			self::TAB_SCANNER,
			'aicc_scanner_section',
			array(
				'id'          => 'aicc_request_timeout',
				'min'         => 5,
				'max'         => 60,
				'description' => __( 'How long (in seconds) to wait for each page to load during scanning. Increase this if scanning fails on slow websites.', 'ai-content-orchestrator' ),
			)
		);

		// ── LinkedIn Section ────────────────────────────────────────
		add_settings_section(
			'aicc_linkedin_section',
			__( 'LinkedIn Integration', 'ai-content-orchestrator' ),
			array( __CLASS__, 'render_linkedin_section' ),
			self::TAB_LINKEDIN
		);

		// LinkedIn Client ID.
		register_setting( 'aicc_settings_linkedin', 'aicc_linkedin_client_id', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		) );

		add_settings_field(
			'aicc_linkedin_client_id',
			__( 'LinkedIn Client ID', 'ai-content-orchestrator' ),
			array( __CLASS__, 'render_text_field' ),
			self::TAB_LINKEDIN,
			'aicc_linkedin_section',
			array(
				'id'          => 'aicc_linkedin_client_id',
				'placeholder' => '86abc123def456',
				'description' => __( 'From your LinkedIn Developer App.', 'ai-content-orchestrator' ),
			)
		);

		// LinkedIn Client Secret.
		register_setting( 'aicc_settings_linkedin', 'aicc_linkedin_client_secret', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		) );

		add_settings_field(
			'aicc_linkedin_client_secret',
			__( 'LinkedIn Client Secret', 'ai-content-orchestrator' ),
			array( __CLASS__, 'render_password_field' ),
			self::TAB_LINKEDIN,
			'aicc_linkedin_section',
			array(
				'id'          => 'aicc_linkedin_client_secret',
				'placeholder' => '',
				'description' => __( 'From your LinkedIn Developer App. Keep this secret.', 'ai-content-orchestrator' ),
			)
		);
		// ── Instagram Section ───────────────────────────────────
		add_settings_section(
			'aicc_instagram_section',
			__( 'Instagram', 'ai-content-orchestrator' ),
			array( __CLASS__, 'render_instagram_section' ),
			self::TAB_INSTAGRAM
		);

		register_setting( 'aicc_settings_instagram', 'aicc_instagram_app_id', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		) );

		add_settings_field(
			'aicc_instagram_app_id',
			__( 'Meta App ID', 'ai-content-orchestrator' ),
			array( __CLASS__, 'render_text_field' ),
			self::TAB_INSTAGRAM,
			'aicc_instagram_section',
			array(
				'id'          => 'aicc_instagram_app_id',
				'placeholder' => '',
				'description' => __( 'From your Meta App at developers.facebook.com. This is the App ID, not the Instagram account ID.', 'ai-content-orchestrator' ),
			)
		);

		register_setting( 'aicc_settings_instagram', 'aicc_instagram_app_secret', array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
		) );

		add_settings_field(
			'aicc_instagram_app_secret',
			__( 'Meta App Secret', 'ai-content-orchestrator' ),
			array( __CLASS__, 'render_password_field' ),
			self::TAB_INSTAGRAM,
			'aicc_instagram_section',
			array(
				'id'          => 'aicc_instagram_app_secret',
				'placeholder' => '',
				'description' => __( 'From your Meta App. Keep this secret.', 'ai-content-orchestrator' ),
			)
		);
	}

	/* ── Section descriptions ──────────────────────────────────── */

	public static function render_project_vision_section() {
		echo '<p>' . esc_html__( 'Set instructions that the AI always follows when writing content. These are included with every content generation.', 'ai-content-orchestrator' ) . '</p>';
	}

	public static function render_provider_section() {
		echo '<p>' . esc_html__( 'Select which AI provider to use for generating content. You can configure both and switch between them.', 'ai-content-orchestrator' ) . '</p>';
	}

	public static function render_claude_section() {
		echo '<p>' . esc_html__( 'Configure your Anthropic Claude API credentials and model.', 'ai-content-orchestrator' ) . '</p>';
	}

	public static function render_openai_section() {
		echo '<p>' . esc_html__( 'Configure your OpenAI API credentials and model.', 'ai-content-orchestrator' ) . '</p>';
	}

	public static function render_default_image_section() {
		echo '<p>' . esc_html__( 'Configure a default featured image that is used when AI image generation is not enabled. Optionally overlay the blog title on the image to create a unique branded featured image for each post.', 'ai-content-orchestrator' ) . '</p>';
	}

	/**
	 * Render a media library upload field with preview.
	 */
	public static function render_media_upload_field( $args ) {
		$value       = (int) get_option( $args['id'], 0 );
		$button_text = isset( $args['button_text'] ) ? $args['button_text'] : __( 'Select Image', 'ai-content-orchestrator' );
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
			'<button type="button" class="button aicc-media-upload-btn" data-target="%s">%s</button>',
			esc_attr( $field_id ),
			esc_html( $button_text )
		);

		if ( $value > 0 ) {
			printf(
				' <button type="button" class="button aicc-media-remove-btn" data-target="%s">%s</button>',
				esc_attr( $field_id ),
				esc_html__( 'Remove', 'ai-content-orchestrator' )
			);
		}

		// Preview area.
		echo '<div class="aicc-media-preview" data-target="' . esc_attr( $field_id ) . '" style="margin-top:8px;">';
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
			?>
			<script>
			jQuery(document).ready(function($) {
				$('.aicc-media-upload-btn').on('click', function(e) {
					e.preventDefault();
					var $btn    = $(this);
					var target  = $btn.data('target');
					var $input  = $('#' + target);
					var $preview = $('.aicc-media-preview[data-target="' + target + '"]');

					var frame = wp.media({
						title: '<?php echo esc_js( __( 'Select or Upload', 'ai-content-orchestrator' ) ); ?>',
						button: { text: '<?php echo esc_js( __( 'Use This', 'ai-content-orchestrator' ) ); ?>' },
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
						if (!$btn.next('.aicc-media-remove-btn').length) {
							$btn.after(' <button type="button" class="button aicc-media-remove-btn" data-target="' + target + '"><?php echo esc_js( __( 'Remove', 'ai-content-orchestrator' ) ); ?></button>');
						}
					});

					frame.open();
				});

				$(document).on('click', '.aicc-media-remove-btn', function(e) {
					e.preventDefault();
					var target = $(this).data('target');
					$('#' + target).val('0');
					$('.aicc-media-preview[data-target="' + target + '"]').empty();
					$(this).remove();
				});
			});
			</script>
			<?php
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

	public static function render_thrive_section() {
		echo '<p>' . esc_html__( 'Settings for Thrive Architect integration. When Thrive Architect is selected as the output format, each heading, paragraph, and list becomes its own editable block in Thrive\'s visual editor.', 'ai-content-orchestrator' ) . '</p>';
		echo '<p>' . esc_html__( 'Choose a saved Thrive block to use as your call-to-action. It appears in two places: right after the Table of Contents and at the bottom of every post.', 'ai-content-orchestrator' ) . '</p>';
	}

	/**
	 * Render a generic Thrive library item dropdown.
	 *
	 * Used for both the TOC Block and CTA Block fields. Queries all Thrive
	 * library post types and presents them grouped with friendly names.
	 */
	public static function render_thrive_library_dropdown( $args ) {
		$value       = (int) get_option( $args['id'], 0 );
		$description = isset( $args['description'] ) ? $args['description'] : '';
		$groups      = class_exists( 'AICC_Thrive_Converter' ) ? AICC_Thrive_Converter::get_available_library_items() : array();

		$total = 0;
		foreach ( $groups as $items ) {
			$total += count( $items );
		}

		if ( $total > 0 ) {
			printf( '<select id="%1$s" name="%1$s">', esc_attr( $args['id'] ) );
			printf(
				'<option value="0" %s>%s</option>',
				selected( $value, 0, false ),
				esc_html__( '— None —', 'ai-content-orchestrator' )
			);
			foreach ( $groups as $group_label => $items ) {
				if ( empty( $items ) ) {
					continue;
				}
				printf( '<optgroup label="%s">', esc_attr( $group_label ) );
				foreach ( $items as $item ) {
					printf(
						'<option value="%d" %s>%s (#%d)</option>',
						intval( $item['id'] ),
						selected( $value, $item['id'], false ),
						esc_html( $item['title'] ),
						intval( $item['id'] )
					);
				}
				echo '</optgroup>';
			}
			echo '</select>';
		} else {
			printf(
				'<input type="number" id="%1$s" name="%1$s" value="%2$d" min="0" class="small-text" />',
				esc_attr( $args['id'] ),
				intval( $value )
			);
		}

		if ( ! empty( $description ) ) {
			printf( '<p class="description">%s</p>', esc_html( $description ) );
		}
	}

	public static function render_image_provider_section() {
		echo '<p>' . esc_html__( 'Choose which AI service creates your blog\'s featured images, and configure its settings.', 'ai-content-orchestrator' ) . '</p>';
	}

	public static function render_scanner_section() {
		echo '<p>' . esc_html__( 'Control how the plugin scans websites. When you enter a URL on the Create Content page, the plugin reads pages from that website to give the AI useful background information.', 'ai-content-orchestrator' ) . '</p>';
	}

	public static function render_linkedin_section() {
		echo '<p>' . esc_html__( 'Connect your LinkedIn account to automatically share your blog posts when they are published. Follow the setup guide below to connect your account.', 'ai-content-orchestrator' ) . '</p>';
	}

	public static function render_instagram_section() {
		echo '<p>' . esc_html__( 'Connect your Instagram Business account to automatically share posts with a featured image and AI-generated caption when they are published.', 'ai-content-orchestrator' ) . '</p>';
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
		echo '<div id="aicc-brand-swatches" style="display:flex; gap:4px; flex-wrap:wrap;">';

		if ( ! empty( $value ) ) {
			$colors = array_map( 'trim', explode( ',', $value ) );
			foreach ( $colors as $color ) {
				if ( preg_match( '/^#[0-9a-fA-F]{3,6}$/', $color ) ) {
					printf(
						'<span class="aicc-color-swatch aicc-color-selected" data-color="%1$s" style="display:inline-block;width:28px;height:28px;background:%1$s;border:3px solid #2271b1;border-radius:4px;cursor:pointer;position:relative;" title="%1$s"><span style="position:absolute;bottom:-2px;right:-2px;background:#2271b1;color:#fff;width:12px;height:12px;border-radius:50%%;font-size:9px;line-height:12px;text-align:center;">&#10003;</span></span>',
						esc_attr( $color )
					);
				}
			}
		}

		echo '</div>';
		echo ' <button type="button" class="button" id="aicc-scan-theme-colors">';
		echo '<span class="dashicons dashicons-art" style="vertical-align:text-bottom; font-size:16px; width:16px; height:16px; margin-right:4px;"></span>';
		esc_html_e( 'Scan Theme Colors', 'ai-content-orchestrator' );
		echo '</button>';
		echo '<span style="color:#787c82; font-size:12px;" id="aicc-color-count">';
		if ( ! empty( $value ) ) {
   /* translators: %s: dynamic value */
			printf( esc_html__( '%d/4 selected', 'ai-content-orchestrator' ), intval( min( count( array_filter( $colors ) ), 4 ) ) );
		}
		echo '</span>';
		echo '</div>';

		echo '<div id="aicc-scan-colors-result" style="margin-top:8px;"></div>';

		if ( ! empty( $args['description'] ) ) {
			printf( '<p class="description">%s</p>', wp_kses( $args['description'], array( 'strong' => array(), 'em' => array(), 'a' => array( 'href' => array() ) ) ) );
		}
	}

	/* ── Getters ───────────────────────────────────────────────── */

	public static function get_project_vision() {
		return trim( get_option( 'aicc_project_vision', '' ) );
	}

	public static function get_ai_provider() {
		return get_option( 'aicc_ai_provider', 'claude' );
	}

	public static function get_anthropic_api_key() {
		return get_option( 'aicc_anthropic_api_key', '' );
	}

	public static function get_claude_model() {
		return get_option( 'aicc_claude_model', 'claude-sonnet-4-6' );
	}

	public static function get_openai_api_key() {
		return get_option( 'aicc_openai_api_key', '' );
	}

	public static function get_openai_model() {
		return get_option( 'aicc_openai_model', 'gpt-4o' );
	}

	public static function is_internal_linking_enabled() {
		return '1' === get_option( 'aicc_internal_linking', '1' );
	}

	public static function get_max_internal_links() {
		$max = (int) get_option( 'aicc_max_internal_links', 5 );
		if ( ! aicc_is_pro() ) {
			return min( $max, 3 );
		}
		return $max;
	}

	public static function get_default_output_format() {
		$format = get_option( 'aicc_default_output_format', 'wordpress' );
		if ( 'thrive' === $format && ! aicc_is_pro() ) {
			return 'wordpress';
		}
		return $format;
	}

	public static function get_link_placement() {
		if ( ! aicc_is_pro() ) {
			return 'inline';
		}
		return get_option( 'aicc_link_placement', 'both' );
	}

	public static function get_schedule_frequency() {
		if ( ! aicc_is_pro() ) {
			return 'none';
		}
		return get_option( 'aicc_schedule_frequency', 'none' );
	}

	public static function get_schedule_time() {
		return get_option( 'aicc_schedule_time', '09:00' );
	}

	public static function get_schedule_skip_weekends() {
		return '1' === get_option( 'aicc_schedule_skip_weekends', '0' );
	}

	public static function get_notify_emails() {
		if ( ! aicc_is_pro() ) {
			return array();
		}
		$value = get_option( 'aicc_notify_emails', '' );
		if ( empty( $value ) ) {
			return array();
		}
		return array_filter( array_map( 'trim', explode( ',', $value ) ) );
	}

	public static function get_competitor_analysis_enabled() {
		return '1' === get_option( 'aicc_competitor_analysis', '0' );
	}

	public static function get_default_featured_image() {
		return (int) get_option( 'aicc_default_featured_image', 0 );
	}

	public static function is_overlay_enabled() {
		return '1' === get_option( 'aicc_overlay_enabled', '' );
	}

	public static function get_overlay_text_color() {
		return get_option( 'aicc_overlay_text_color', '#0d5e50' );
	}

	public static function get_overlay_font_bold_path() {
		$id = (int) get_option( 'aicc_overlay_font_bold', 0 );
		return $id > 0 ? get_attached_file( $id ) : '';
	}

	public static function get_overlay_font_italic_path() {
		$id = (int) get_option( 'aicc_overlay_font_italic', 0 );
		return $id > 0 ? get_attached_file( $id ) : '';
	}

	public static function get_thrive_toc_id() {
		return (int) get_option( 'aicc_thrive_toc_id', 0 );
	}

	public static function get_thrive_cta_symbol_id() {
		return (int) get_option( 'aicc_thrive_cta_symbol_id', 0 );
	}

	public static function get_image_provider() {
		$provider = get_option( 'aicc_image_provider', 'openai' );
		if ( 'ideogram' === $provider && ! aicc_is_pro() ) {
			return 'openai';
		}
		return $provider;
	}

	public static function get_ideogram_api_key() {
		return get_option( 'aicc_ideogram_api_key', '' );
	}

	public static function get_image_style() {
		return get_option( 'aicc_image_style', 'auto' );
	}

	public static function get_brand_colors() {
		$raw = trim( get_option( 'aicc_brand_colors', '' ) );
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
		return trim( get_option( 'aicc_image_negative_prompt', '' ) );
	}

	/**
	 * Get the API key for the active image provider.
	 *
	 * @return string
	 */
	public static function get_image_api_key() {
		$provider = self::get_image_provider();
		switch ( $provider ) {
			case 'ideogram':
				return self::get_ideogram_api_key();
			default:
				return self::get_openai_api_key();
		}
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
		return (int) get_option( 'aicc_max_pages_to_crawl', 25 );
	}

	public static function get_max_context_chars() {
		return (int) get_option( 'aicc_max_context_chars', 18000 );
	}

	public static function get_request_timeout() {
		return (int) get_option( 'aicc_request_timeout', 15 );
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
		$urls = get_option( 'aicc_saved_urls', array() );
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
			update_option( 'aicc_saved_urls', $urls );
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
			update_option( 'aicc_saved_urls', $new );
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
			'general'  => __( 'General', 'ai-content-orchestrator' ),
			'content'  => __( 'Content', 'ai-content-orchestrator' ),
			'images'   => __( 'Images', 'ai-content-orchestrator' ),
			'thrive'   => __( 'Thrive Architect (Beta)', 'ai-content-orchestrator' ),
			'linkedin'  => __( 'LinkedIn', 'ai-content-orchestrator' ),
			'instagram' => __( 'Instagram', 'ai-content-orchestrator' ),
			'scanner'  => __( 'Scanner', 'ai-content-orchestrator' ),
			'faq'      => __( 'FAQ', 'ai-content-orchestrator' ),
			'about'    => __( 'About', 'ai-content-orchestrator' ),
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
				'aicc_ai_provider',
				'aicc_anthropic_api_key',
				'aicc_claude_model',
				'aicc_openai_api_key',
				'aicc_openai_model',
			),
			'content' => array(
				'aicc_project_vision',
				'aicc_default_output_format',
				'aicc_internal_linking',
				'aicc_max_internal_links',
				'aicc_link_placement',
				'aicc_competitor_analysis',
				'aicc_schedule_frequency',
				'aicc_schedule_time',
				'aicc_schedule_skip_weekends',
				'aicc_notify_emails',
			),
			'images' => array(
				'aicc_image_provider',
				'aicc_ideogram_api_key',
				'aicc_image_style',
				'aicc_brand_colors',
				'aicc_image_negative_prompt',
				'aicc_default_featured_image',
				'aicc_overlay_enabled',
				'aicc_overlay_text_color',
				'aicc_overlay_font_bold',
				'aicc_overlay_font_italic',
			),
			'thrive' => array(
				'aicc_thrive_toc_id',
				'aicc_thrive_cta_symbol_id',
			),
			'scanner' => array(
				'aicc_max_pages_to_crawl',
				'aicc_max_context_chars',
				'aicc_request_timeout',
			),
			'linkedin' => array(
				'aicc_linkedin_client_id',
				'aicc_linkedin_client_secret',
			),
			'instagram' => array(
				'aicc_instagram_app_id',
				'aicc_instagram_app_secret',
			),
		);
	}

	/**
	 * Handle custom settings save (bypasses options.php).
	 */
	public static function handle_save() {
		if ( empty( $_POST['aicc_save_tab'] ) || empty( $_POST['aicc_settings_nonce'] ) ) {
			return;
		}

		$tab = sanitize_text_field( wp_unslash( $_POST['aicc_save_tab'] ) );

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['aicc_settings_nonce'] ) ), 'aicc_save_settings_' . $tab ) ) {
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
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized by the registered sanitize_callback on the next line.
			$value = isset( $_POST[ $option ] ) ? wp_unslash( $_POST[ $option ] ) : '';

			if ( isset( $registered[ $option ]['sanitize_callback'] ) ) {
				$value = call_user_func( $registered[ $option ]['sanitize_callback'], $value );
			}

			update_option( $option, $value );
		}

		$redirect = add_query_arg(
			array(
				'page'             => 'aicc-settings',
				'tab'              => $tab,
				'settings-updated' => 'true',
			),
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $redirect );
		exit;
	}
}
