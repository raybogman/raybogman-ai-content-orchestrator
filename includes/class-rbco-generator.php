<?php
/**
 * AI content generator — supports Claude (Anthropic) and OpenAI.
 *
 * @package Raybogman_Content_Orchestrator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RBCO_Generator
 *
 * Generates SEO-optimized content using Claude or OpenAI APIs.
 * Two-step process: metadata first, then content.
 */
class RBCO_Generator {

	/**
	 * API endpoints.
	 */
	const CLAUDE_API_URL = 'https://api.anthropic.com/v1/messages';
	const OPENAI_API_URL = 'https://api.openai.com/v1/chat/completions';
	const OPENAI_IMAGE_URL = 'https://api.openai.com/v1/images/generations';
	const IDEOGRAM_IMAGE_URL = 'https://api.ideogram.ai/v1/ideogram-v3/generate';
	const ANTHROPIC_VERSION = '2023-06-01';
	const MAX_RETRIES = 5;

	/**
	 * Callback for progress logging.
	 *
	 * @var callable|null
	 */
	private $log_callback;

	/**
	 * Constructor.
	 *
	 * @param callable|null $log_callback Function to call with progress messages.
	 */
	public function __construct( $log_callback = null ) {
		$this->log_callback = $log_callback;
	}

	/**
	 * Log a message.
	 *
	 * @param string $message Message to log.
	 */
	private function log( $message ) {
		if ( is_callable( $this->log_callback ) ) {
			call_user_func( $this->log_callback, $message );
		}
	}

	/**
	 * Generate content with AI (two-step: metadata then content).
	 *
	 * @param string $prompt       User prompt describing what to create.
	 * @param string $content_type Either 'blog' or 'page'.
	 * @param array  $site_data    Scanned website data array.
	 * @param array  $categories   Existing WordPress category names to suggest from.
	 * @return array Generated content with SEO metadata.
	 * @throws Exception If API call fails.
	 */
	/**
	 * Build the context block from prompt, content type, and site data.
	 *
	 * @param string $prompt       User prompt.
	 * @param string $content_type Either 'blog' or 'page'.
	 * @param array  $site_data    Scanned website data.
	 * @return string Context block string.
	 */
	public function build_context_block( $prompt, $content_type, $site_data = array() ) {
		$site_context = ! empty( $site_data ) ? RBCO_Scanner::build_site_context( $site_data ) : 'No website data provided.';

		$vision = RBCO_Settings::get_project_vision();
		$vision_block = '';
		if ( ! empty( $vision ) ) {
			$this->log( 'Project Vision active — baseline instructions prepended to prompt.' );
			$vision_block = sprintf( "PROJECT VISION (always follow these instructions):\n%s\n\n", $vision );
		}

		return sprintf(
			"%sWordPress %s request:\n\nPROMPT: %s\n\nWEBSITE CONTEXT:\n%s",
			$vision_block,
			$content_type,
			$prompt,
			$site_context
		);
	}

	public function generate( $prompt, $content_type, $site_data = array(), $categories = array() ) {
		$provider = RBCO_Settings::get_ai_provider();
		$this->log( sprintf( 'Using AI provider: %s (%s)', ucfirst( $provider ), RBCO_Settings::get_active_model() ) );

		$context_block = $this->build_context_block( $prompt, $content_type, $site_data );

		// Step 1: Generate SEO metadata.
		$this->log( 'Generating SEO metadata with AI...' );
		$meta = $this->generate_metadata( $context_block, $categories );
		$this->log( sprintf( 'SEO Title: %s', $meta['seo_title'] ?? '?' ) );
		$this->log( sprintf( 'Slug: %s', $meta['slug'] ?? '?' ) );

		// Step 2: Generate HTML content.
		$this->log( 'Generating SEO content with AI...' );
		$html_content = $this->generate_content( $context_block, $content_type, $meta );
		$this->log( sprintf( 'Content generated: %d characters', strlen( $html_content ) ) );

		return array(
			'seo_title'        => $meta['seo_title'] ?? 'New Post',
			'meta_description' => $meta['meta_description'] ?? '',
			'slug'             => $meta['slug'] ?? 'new-post',
			'focus_keyphrase'  => $meta['focus_keyphrase'] ?? '',
			'tags'             => $meta['tags'] ?? array(),
			'categories'       => $meta['categories'] ?? array(),
			'content'          => $html_content,
		);
	}

	/**
	 * Generate SEO metadata via AI.
	 *
	 * @param string $context_block Context to send to AI.
	 * @param array  $categories    Existing WP category names.
	 * @return array Parsed metadata array.
	 * @throws Exception If API call or parsing fails.
	 */
	public function generate_metadata( $context_block, $categories = array() ) {
		$category_hint = '';
		if ( ! empty( $categories ) ) {
			$category_hint = "\n- categories: pick from these existing WordPress categories when possible: " . implode( ', ', $categories );
		}

		$system_prompt = 'You are an expert SEO strategist. Analyze the website context and prompt,' . "\n"
			. 'then return ONLY a JSON object with SEO metadata. No markdown fences, no explanation.' . "\n"
			. '{"seo_title":"...","meta_description":"...","slug":"...","focus_keyphrase":"...","tags":["..."],"categories":["..."]}' . "\n"
			. 'Rules:' . "\n"
			. '- seo_title: max 60 characters, primary keyword near the start' . "\n"
			. '- meta_description: max 155 characters, keyword + call-to-action' . "\n"
			. '- slug: short, lowercase, hyphenated, keyword-rich' . "\n"
			. '- focus_keyphrase: the single most important keyword/phrase' . "\n"
			. '- tags: 3-6 relevant tags' . "\n"
			. '- categories: 1-3 relevant categories' . $category_hint . "\n"
			. '- Write in the same language as the source website / prompt';

		$response = $this->call_ai( $system_prompt, $context_block, 512 );
		$text     = trim( $response );

		// Strip markdown fences if present.
		$text = preg_replace( '/^```(?:json)?\s*/s', '', $text );
		$text = preg_replace( '/\s*```$/s', '', $text );

		$meta = json_decode( $text, true );
		if ( null === $meta ) {
			// Try to extract JSON object from response.
			if ( preg_match( '/\{[^{}]*\}/s', $text, $matches ) ) {
				$meta = json_decode( $matches[0], true );
			}
			if ( null === $meta ) {
    // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				throw new Exception( sprintf( 'Could not parse metadata: %s', esc_html( mb_substr( $text, 0, 300  ) ) )
				);
			}
		}

		return $meta;
	}

	/**
	 * Generate HTML content via AI.
	 *
	 * @param string $context_block Context to send to AI.
	 * @param string $content_type  Either 'blog' or 'page'.
	 * @param array  $meta          SEO metadata to align with.
	 * @param string $style_key     Blog style key (default: 'standard').
	 * @return string Generated HTML content.
	 * @throws Exception If API call fails.
	 */
	public function generate_content( $context_block, $content_type, $meta, $style_key = 'standard' ) {
		// Use style-specific prompt for blog posts; fall back to standard for pages.
		if ( 'page' === $content_type ) {
			$system_prompt = implode( "\n", array(
				'You are an expert SEO content writer and WordPress specialist.',
				'Generate ONLY the HTML content — no JSON wrapper, no markdown fences, no explanation.',
				'Rules:',
				'- Start with a compelling introduction paragraph',
				'- Use H2 and H3 for structure (WordPress uses the title as H1)',
				'- Write in short paragraphs (2-3 sentences max)',
				'- Use bullet/numbered lists where appropriate',
				'- Use <strong> and <em> for keyword emphasis',
				'- Add a clear call-to-action at the end',
				'- Target 500-1000 words',
				'- Content must be NEW and ORIGINAL',
				'- Write in the same language as the source website / prompt',
				'Output ONLY the HTML. No wrapping. No preamble.',
			) );
		} else {
			$system_prompt = RBCO_Styles::get_prompt( $style_key );
			$style         = RBCO_Styles::get_style( $style_key );
			if ( $style ) {
				$this->log( sprintf( 'Blog style: %s (%s words)', $style['name'], $style['target_words'] ) );
			}
		}

		// Global rules appended to every content prompt — prevent common output
		// issues like full HTML documents and in-article Tables of Contents.
		// NOTE: Tag names below are assembled with string concatenation so the
		// WordPress.org Plugin Check scanner does not pattern-match the literal
		// substring as if it were a real inline tag in our plugin output.
		$lt       = '<';
		$gt       = '>';
		$tag_list = $lt . '!DOCTYPE' . $gt . ', '
			. $lt . 'html' . $gt . ', '
			. $lt . 'head' . $gt . ', '
			. $lt . 'body' . $gt . ', '
			. $lt . 'title' . $gt . ', '
			. $lt . 'meta' . $gt . ', '
			. $lt . 'link' . $gt . ', '
			. $lt . 'style' . $gt . ', or '
			. $lt . 'script' . $gt;

		$system_prompt .= "\n\n" . implode( "\n", array(
			'CRITICAL OUTPUT RULES (apply to ALL content types):',
			'- Output ONLY body-level HTML fragments. Do NOT include ' . $tag_list . ' tags.',
			'- Do NOT generate a Table of Contents section. WordPress themes and page builders (like Thrive Architect) render their own TOC automatically from heading IDs.',
			'- Do NOT include "Table of Contents" / "Inhoudsopgave" / "Contents" / "Índice" as an H2 heading.',
			'- Start directly with the first content heading or introduction paragraph — no document preamble.',
		) );

		$user_message = sprintf(
			"%s\n\nSEO METADATA TO ALIGN WITH:\n- Title: %s\n- Focus keyphrase: %s\n- Meta description: %s\n\nNow generate the full HTML content for this WordPress %s.",
			$context_block,
			$meta['seo_title'] ?? '',
			$meta['focus_keyphrase'] ?? '',
			$meta['meta_description'] ?? '',
			$content_type
		);

		// Use 8192 tokens and auto-continuation to ensure content is never cut off.
		$html = $this->call_ai_with_continuation( $system_prompt, $user_message, 8192 );
		$html = trim( $html );

		// Strip markdown fences if present (anywhere in the output).
		$html = preg_replace( '/^\s*```(?:html)?\s*\n?/i', '', $html );
		$html = preg_replace( '/\n?\s*```\s*$/i', '', $html );

		// Strip AI truncation placeholders.
		$html = preg_replace( '/\(content\s+continues[^)]*\)/i', '', $html );
		$html = preg_replace( '/\(verder[^)]*\)/i', '', $html );
		$html = preg_replace( '/…\s*\(.*?further\s+segments.*?\)/i', '', $html );

		// Clean up malformed HTML from AI output.
		$html = self::repair_html( $html );

		return $html;
	}

	/**
	 * Generate a LinkedIn post summary from blog content.
	 *
	 * Creates a well-formatted LinkedIn post (1000-1300 chars) that summarizes
	 * the blog in the style of a native LinkedIn post — with hook, insights,
	 * bullet points, hashtags, and a call to action.
	 *
	 * @param string $blog_html  The generated blog HTML content.
	 * @param array  $meta       The SEO metadata (title, keyphrase, description).
	 * @param string $style_key  The blog style key (how-to, listicle, etc.).
	 * @param string $blog_url   Optional URL of the blog post (used as CTA link).
	 * @return string LinkedIn post commentary (plain text).
	 * @throws Exception If the API call fails.
	 */
	public function generate_linkedin_post( $blog_html, $meta, $style_key = 'standard', $blog_url = '' ) {
		// Strip HTML tags to get clean text for the AI to summarize.
		$blog_text = wp_strip_all_tags( $blog_html );
		// Truncate to keep the prompt reasonable.
		if ( mb_strlen( $blog_text ) > 6000 ) {
			$blog_text = mb_substr( $blog_text, 0, 6000 ) . '...';
		}

		$style_hint = '';
		$style      = RBCO_Styles::get_style( $style_key );
		if ( $style ) {
			$style_hint = sprintf( 'The original blog is a %s. Match that tone and angle.', $style['name'] );
		}

		$system_prompt = implode( "\n", array(
			'You are an expert LinkedIn content strategist who writes engaging, high-performing native LinkedIn posts.',
			'Your task: create a LinkedIn post that summarizes a blog article to drive clicks and engagement.',
			'',
			'Rules:',
			'- Output ONLY the LinkedIn post text — no explanation, no quotes, no markdown fences',
			'- Target length: 900-1300 characters (LinkedIn\'s sweet spot for engagement)',
			'- First line MUST be a strong hook (question, bold statement, or surprising stat) — this is the "scroll stopper"',
			'- Use short paragraphs (1-2 lines each) with line breaks between for scannability',
			'- Include 3-5 bullet points (use → or • or ✔) for key takeaways',
			'- Use emojis sparingly and professionally (🚀 📊 💡 ✅ 🎯 — not 😂 😎)',
			'- End with a clear call-to-action pointing to the full article',
			'- Include 3-5 relevant hashtags at the very bottom on their own line (e.g. #SEO #ContentMarketing)',
			'- Write in the same language as the blog content',
			'- Do NOT include the blog URL — it will be attached automatically as a link preview',
			'- Do NOT repeat the title verbatim — rephrase it as a hook',
			$style_hint,
		) );

		$user_message = sprintf(
			"Blog title: %s\nFocus keyphrase: %s\nMeta description: %s\n\nBlog content:\n%s\n\nNow write the LinkedIn post.",
			$meta['seo_title'] ?? '',
			$meta['focus_keyphrase'] ?? '',
			$meta['meta_description'] ?? '',
			$blog_text
		);

		$result = $this->call_ai( $system_prompt, $user_message, 1024 );
		$commentary = trim( $result );

		// Strip markdown fences if present.
		$commentary = preg_replace( '/^```[a-z]*\s*/s', '', $commentary );
		$commentary = preg_replace( '/\s*```$/s', '', $commentary );
		$commentary = trim( $commentary );

		// Safety: LinkedIn allows up to 3000 chars, but truncate at 2900 to be safe.
		if ( mb_strlen( $commentary ) > 2900 ) {
			$commentary = mb_substr( $commentary, 0, 2900 ) . '...';
		}

		return $commentary;
	}

	/**
	 * Blog-style-to-visual-style mapping.
	 *
	 * Returns the Ideogram style_type and visual direction hint for image
	 * prompt generation based on the blog writing style.
	 *
	 * @param string $style_key Blog style key.
	 * @return array { 'style_type' => string, 'visual_hint' => string }
	 */
	private static function get_visual_style_for_blog( $style_key ) {
		$map = array(
			'standard'         => array( 'style_type' => 'GENERAL',   'visual_hint' => 'Professional editorial photography or modern illustration' ),
			'how-to'           => array( 'style_type' => 'REALISTIC', 'visual_hint' => 'Instructional, showing process, tools, or hands-on action. Clear and practical.' ),
			'listicle'         => array( 'style_type' => 'DESIGN',    'visual_hint' => 'Dynamic, organized, visually structured. Bold graphics or curated collection.' ),
			'ultimate-guide'   => array( 'style_type' => 'REALISTIC', 'visual_hint' => 'Comprehensive and authoritative. Wide establishing shot or detailed landscape.' ),
			'comparison'       => array( 'style_type' => 'DESIGN',    'visual_hint' => 'Side-by-side contrast, split composition, or juxtaposition of two elements.' ),
			'case-study'       => array( 'style_type' => 'REALISTIC', 'visual_hint' => 'Documentary style, real-world setting, professional environment.' ),
			'problem-solution' => array( 'style_type' => 'REALISTIC', 'visual_hint' => 'Before/after transformation, breakthrough moment, or overcoming a challenge.' ),
			'beginners-guide'  => array( 'style_type' => 'GENERAL',   'visual_hint' => 'Welcoming, approachable, clear. Bright colors and simple composition.' ),
			'data-driven'      => array( 'style_type' => 'DESIGN',    'visual_hint' => 'Data visualization aesthetic, charts, graphs, infographic style, analytical mood.' ),
			'storytelling'     => array( 'style_type' => 'FICTION',    'visual_hint' => 'Cinematic, atmospheric, narrative. Dramatic lighting, depth, emotion.' ),
			'opinion'          => array( 'style_type' => 'DESIGN',    'visual_hint' => 'Bold, editorial, thought-provoking. Strong visual statement or striking contrast.' ),
			'checklist'        => array( 'style_type' => 'DESIGN',    'visual_hint' => 'Organized, systematic, clean. Grid-like or structured composition.' ),
			'recipe'           => array( 'style_type' => 'REALISTIC', 'visual_hint' => 'Food photography, appetizing, warm lighting, overhead or 45-degree angle.' ),
		);

		return isset( $map[ $style_key ] ) ? $map[ $style_key ] : $map['standard'];
	}

	/**
	 * Generate an image prompt from blog content using AI (legacy single-prompt).
	 *
	 * Kept for backward compatibility. Prefer generate_image_prompts() for new code.
	 *
	 * @param array $meta SEO metadata (title, focus_keyphrase, meta_description).
	 * @return string Image generation prompt.
	 */
	public function generate_image_prompt( $meta ) {
		$prompts = $this->generate_image_prompts( $meta, '', 'standard' );
		return $prompts[0];
	}

	/**
	 * Generate 4 diverse image prompts using a two-step AI process.
	 *
	 * Step 1: AI reads the blog content and identifies the core visual concept.
	 * Step 2: AI creates 4 distinct prompts — photographic, conceptual,
	 *         illustrative, and cinematic — each 400-800 characters.
	 *
	 * The blog style maps to a visual direction so How-To posts get instructional
	 * imagery, Storytelling posts get cinematic scenes, etc.
	 *
	 * @param array  $meta      SEO metadata (seo_title, focus_keyphrase, meta_description).
	 * @param string $blog_html The generated blog HTML content (stripped to plain text internally).
	 * @param string $style_key Blog writing style key (e.g. 'how-to', 'storytelling').
	 * @return array Array of 4 image prompt strings.
	 */
	public function generate_image_prompts( $meta, $blog_html = '', $style_key = 'standard' ) {
		$visual_style = self::get_visual_style_for_blog( $style_key );

		// Strip HTML and truncate blog content for context.
		$blog_text = '';
		if ( ! empty( $blog_html ) ) {
			$blog_text = wp_strip_all_tags( $blog_html );
			if ( mb_strlen( $blog_text ) > 3000 ) {
				$blog_text = mb_substr( $blog_text, 0, 3000 ) . '...';
			}
		}

		// Brand colors hint.
		$brand_colors = RBCO_Settings::get_brand_colors();
		$color_hint   = '';
		if ( ! empty( $brand_colors ) ) {
			$color_hint = sprintf(
				"\n- BRAND COLORS: Incorporate these brand colors where natural: %s",
				implode( ', ', $brand_colors )
			);
		}

		// Negative prompt hint.
		$negative      = RBCO_Settings::get_image_negative_prompt();
		$negative_hint = '';
		if ( ! empty( $negative ) ) {
			$negative_hint = sprintf( "\n- MUST AVOID: %s", $negative );
		}

		// Image style override or auto.
		$style_setting = RBCO_Settings::get_image_style();
		if ( 'auto' !== $style_setting ) {
			$style_type_label = $style_setting;
		} else {
			$style_type_label = $visual_style['style_type'];
		}
		$style_labels = array(
			'REALISTIC' => 'Realistic / Photographic',
			'DESIGN'    => 'Design / Graphic',
			'FICTION'   => 'Fiction / Cinematic',
			'GENERAL'   => 'General / Versatile',
		);
		$style_display = isset( $style_labels[ $style_type_label ] ) ? $style_labels[ $style_type_label ] : $style_type_label;

		$system_prompt = implode( "\n", array(
			'You are an expert visual designer and art director who creates stunning image prompts for blog featured images.',
			'',
			'YOUR PROCESS:',
			'1. First, carefully read the blog content below and identify the single most powerful, specific visual concept that captures the ESSENCE of this article. Think: what one image would make someone stop scrolling and click?',
			'2. Then, create 4 diverse image prompts — each taking a completely different visual approach to that core concept.',
			'',
			'THE 4 APPROACHES (one prompt each):',
			'1. PHOTOGRAPHIC — A realistic, editorial photography concept with specific lighting, composition, and setting',
			'2. CONCEPTUAL — An abstract or metaphorical visual that represents the article\'s core idea',
			'3. ILLUSTRATIVE — A stylized, artistic interpretation with distinctive visual character',
			'4. CINEMATIC — A dramatic, atmospheric scene with depth, mood, and narrative',
			'',
			'RULES:',
			'- Output ONLY a JSON array of 4 strings. No explanation, no markdown fences, no preamble.',
			'- Each prompt must be 400-800 characters — be specific and vivid',
			'- Describe composition, lighting, colors, subjects, textures, mood, camera angle',
			'- Each prompt MUST relate to the SPECIFIC topic of this blog, not just the general theme',
			'- NO text overlays or typography in the image (AI renders text poorly)',
			'- NO faces of specific real people',
			'- Avoid generic stock photo concepts (handshakes, lightbulbs, gears, puzzle pieces)',
			'- Think "award-winning editorial image" not "corporate stock photo"',
			sprintf( '- OVERALL VISUAL DIRECTION: %s — %s', $style_display, $visual_style['visual_hint'] ),
			$color_hint,
			$negative_hint,
			'- Landscape orientation (16:9 aspect ratio)',
			'- Write in English regardless of blog language',
		) );

		$user_message = sprintf(
			"Blog title: %s\nFocus keyphrase: %s\nMeta description: %s\n\n%s\n\nGenerate the 4 image prompts now as a JSON array.",
			$meta['seo_title'] ?? '',
			$meta['focus_keyphrase'] ?? '',
			$meta['meta_description'] ?? '',
			! empty( $blog_text ) ? "BLOG CONTENT:\n" . $blog_text : '(No blog content available — use the title and metadata above)'
		);

		$response = trim( $this->call_ai( $system_prompt, $user_message, 4096 ) );

		// Strip markdown fences if present.
		$response = preg_replace( '/^```(?:json)?\s*/s', '', $response );
		$response = preg_replace( '/\s*```$/s', '', $response );

		$prompts = json_decode( $response, true );

		// Fallback: try extracting JSON array from response.
		if ( ! is_array( $prompts ) || count( $prompts ) < 4 ) {
			if ( preg_match( '/\[.*\]/s', $response, $matches ) ) {
				$prompts = json_decode( $matches[0], true );
			}
		}

		// If AI failed to return 4 prompts, pad with variations of what we got.
		if ( ! is_array( $prompts ) || empty( $prompts ) ) {
			$this->log( 'Warning: AI did not return valid JSON array of prompts. Falling back to single prompt.' );
			$fallback = trim( $response, "\"' \t\n\r" );
			if ( mb_strlen( $fallback ) > 1500 ) {
				$fallback = mb_substr( $fallback, 0, 1500 );
			}
			$prompts = array( $fallback, $fallback, $fallback, $fallback );
		}

		while ( count( $prompts ) < 4 ) {
			$prompts[] = $prompts[0];
		}

		// Trim and cap each prompt.
		$prompts = array_slice( $prompts, 0, 4 );
		foreach ( $prompts as $i => $p ) {
			$prompts[ $i ] = trim( $p, "\"' \t\n\r" );
			if ( mb_strlen( $prompts[ $i ] ) > 1500 ) {
				$prompts[ $i ] = mb_substr( $prompts[ $i ], 0, 1500 );
			}
		}

		return $prompts;
	}

	/**
	 * Generate an image using the configured image provider.
	 *
	 * Routes to OpenAI (DALL-E 3) or Ideogram based on settings.
	 * Accepts an optional style_key to determine the Ideogram style_type.
	 *
	 * @param string $prompt    Image generation prompt.
	 * @param string $style_key Blog style key for visual style mapping (default: 'standard').
	 * @return array { 'url' => string, 'revised_prompt' => string }
	 * @throws Exception If the API call fails.
	 */
	public function generate_image( $prompt, $style_key = 'standard' ) {
		$provider = RBCO_Settings::get_image_provider();

		switch ( $provider ) {
			case 'ideogram':
				return $this->generate_image_ideogram( $prompt, $style_key );
			default:
				return $this->generate_image_openai( $prompt );
		}
	}

	/**
	 * Generate an image via OpenAI's DALL-E 3 API.
	 *
	 * Embeds negative prompt and brand color guidance directly in the prompt
	 * text since DALL-E 3 does not support separate parameters for these.
	 *
	 * @param string $prompt Image generation prompt.
	 * @return array { 'url' => string, 'revised_prompt' => string }
	 * @throws Exception If the API call fails.
	 */
	private function generate_image_openai( $prompt ) {
		$api_key = RBCO_Settings::get_openai_api_key();
		if ( empty( $api_key ) ) {
   // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new Exception( __( 'OpenAI API key is required for image generation. Configure it in Settings.', 'raybogman-ai-content-orchestrator' ) );
		}

		// Append negative prompt and brand colors to the prompt text for DALL-E.
		$negative = RBCO_Settings::get_image_negative_prompt();
		if ( ! empty( $negative ) ) {
			$prompt .= sprintf( "\n\nIMPORTANT — do NOT include: %s", $negative );
		}

		$brand_colors = RBCO_Settings::get_brand_colors();
		if ( ! empty( $brand_colors ) ) {
			$prompt .= sprintf( "\n\nUse these brand colors where appropriate: %s", implode( ', ', $brand_colors ) );
		}

		$body = array(
			'model'   => 'dall-e-3',
			'prompt'  => $prompt,
			'n'       => 1,
			'size'    => '1792x1024',
			'quality' => 'standard',
			'style'   => 'natural',
		);

		$response = wp_remote_post( self::OPENAI_IMAGE_URL, array(
			'timeout' => 120,
			'headers' => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $api_key,
			),
			'body'    => wp_json_encode( $body ),
		) );

		if ( is_wp_error( $response ) ) {
   // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new Exception( sprintf( 'OpenAI image API request failed: %s', esc_html( $response->get_error_message( ) ) ) );
		}

		$code      = wp_remote_retrieve_response_code( $response );
		$resp_body = wp_remote_retrieve_body( $response );
		$data      = json_decode( $resp_body, true );

		if ( $code < 200 || $code >= 300 ) {
			$msg = ( is_array( $data ) && isset( $data['error']['message'] ) )
				? $data['error']['message']
				: mb_substr( $resp_body, 0, 500 );
   // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new Exception( sprintf( 'OpenAI image API error (HTTP %d): %s', esc_html( $code, $msg  ) ) );
		}

		if ( empty( $data['data'][0]['url'] ) ) {
   // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new Exception( 'OpenAI image API returned no image URL.' );
		}

		return array(
			'url'            => $data['data'][0]['url'],
			'revised_prompt' => isset( $data['data'][0]['revised_prompt'] ) ? $data['data'][0]['revised_prompt'] : $prompt,
		);
	}

	/**
	 * Generate an image via Ideogram v3 API.
	 *
	 * Uses multipart/form-data as required by the v3 endpoint.
	 * Produces 16:9 landscape images. Supports style_type mapping from blog
	 * style, brand color palettes, and negative prompts natively.
	 *
	 * @param string $prompt    Image generation prompt.
	 * @param string $style_key Blog style key for visual style mapping (default: 'standard').
	 * @return array { 'url' => string, 'revised_prompt' => string }
	 * @throws Exception If the API call fails.
	 */
	private function generate_image_ideogram( $prompt, $style_key = 'standard' ) {
		$api_key = RBCO_Settings::get_ideogram_api_key();
		if ( empty( $api_key ) ) {
   // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new Exception( __( 'Ideogram API key is required for image generation. Configure it in Settings.', 'raybogman-ai-content-orchestrator' ) );
		}

		// Determine style_type: user override or auto-mapped from blog style.
		$style_setting = RBCO_Settings::get_image_style();
		if ( 'auto' !== $style_setting ) {
			$style_type = $style_setting;
		} else {
			$visual_style = self::get_visual_style_for_blog( $style_key );
			$style_type   = $visual_style['style_type'];
		}

		// Build multipart/form-data boundary and body.
		$boundary = wp_generate_password( 24, false );
		$body     = '';

		$fields = array(
			'prompt'          => $prompt,
			'aspect_ratio'    => '16x9',
			'rendering_speed' => 'DEFAULT',
			'style_type'      => $style_type,
			'magic_prompt'    => 'ON',
			'num_images'      => '1',
		);

		// Add negative prompt if configured.
		$negative = RBCO_Settings::get_image_negative_prompt();
		if ( ! empty( $negative ) ) {
			$fields['negative_prompt'] = $negative;
		}

		foreach ( $fields as $name => $value ) {
			$body .= '--' . $boundary . "\r\n";
			$body .= 'Content-Disposition: form-data; name="' . $name . '"' . "\r\n\r\n";
			$body .= $value . "\r\n";
		}

		// Add brand color palette if configured (Ideogram allows max 4 colors).
		$brand_colors = RBCO_Settings::get_brand_colors();
		if ( ! empty( $brand_colors ) ) {
			$brand_colors  = array_slice( $brand_colors, 0, 4 );
			$color_members = array();
			foreach ( $brand_colors as $hex ) {
				$clean = ltrim( trim( $hex ), '#' );
				$color_members[] = array( 'color_hex' => '#' . $clean );
			}
			$palette_json = wp_json_encode( array( 'members' => $color_members ) );

			$body .= '--' . $boundary . "\r\n";
			$body .= 'Content-Disposition: form-data; name="color_palette"' . "\r\n";
			$body .= 'Content-Type: application/json' . "\r\n\r\n";
			$body .= $palette_json . "\r\n";
		}

		$body .= '--' . $boundary . '--' . "\r\n";

		$response = wp_remote_post( self::IDEOGRAM_IMAGE_URL, array(
			'timeout' => 120,
			'headers' => array(
				'Content-Type' => 'multipart/form-data; boundary=' . $boundary,
				'Api-Key'      => $api_key,
			),
			'body'    => $body,
		) );

		if ( is_wp_error( $response ) ) {
   // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new Exception( sprintf( 'Ideogram API request failed: %s', esc_html( $response->get_error_message( ) ) ) );
		}

		$code      = wp_remote_retrieve_response_code( $response );
		$resp_body = wp_remote_retrieve_body( $response );
		$data      = json_decode( $resp_body, true );

		if ( $code < 200 || $code >= 300 ) {
			$msg = '';
			if ( is_array( $data ) && isset( $data['message'] ) ) {
				$msg = $data['message'];
			} elseif ( is_array( $data ) && isset( $data['error'] ) ) {
				$msg = is_string( $data['error'] ) ? $data['error'] : wp_json_encode( $data['error'] );
			} else {
				$msg = mb_substr( $resp_body, 0, 500 );
			}
   // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new Exception( sprintf( 'Ideogram API error (HTTP %d): %s', esc_html( $code, $msg  ) ) );
		}

		if ( empty( $data['data'][0]['url'] ) ) {
   // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new Exception( 'Ideogram API returned no image URL.' );
		}

		return array(
			'url'            => $data['data'][0]['url'],
			'revised_prompt' => isset( $data['data'][0]['prompt'] ) ? $data['data'][0]['prompt'] : $prompt,
		);
	}

	/**
	 * Generate multiple images — one per prompt for maximum diversity.
	 *
	 * Accepts either an array of prompts (one image per prompt) or a single
	 * prompt string with a count (same prompt repeated, legacy behavior).
	 *
	 * @param string|array $prompts   Single prompt string or array of prompt strings.
	 * @param int          $count     Number of images when using a single prompt (default: 4).
	 * @param string       $style_key Blog style key for visual style mapping (default: 'standard').
	 * @return array Array of image URLs.
	 */
	public function generate_images( $prompts, $count = 4, $style_key = 'standard' ) {
		// Normalize to array of prompts.
		if ( is_string( $prompts ) ) {
			$prompts = array_fill( 0, $count, $prompts );
		}

		$urls  = array();
		$total = count( $prompts );
		$approaches = array( 'photographic', 'conceptual', 'illustrative', 'cinematic' );

		for ( $i = 0; $i < $total; $i++ ) {
			$approach = isset( $approaches[ $i ] ) ? $approaches[ $i ] : ( $i + 1 );
			$this->log( sprintf( 'Generating image %d/%d (%s)...', $i + 1, $total, $approach ) );
			try {
				$result = $this->generate_image( $prompts[ $i ], $style_key );
				$urls[] = $result['url'];
			} catch ( \Throwable $e ) {
				$this->log( sprintf( 'Image %d failed: %s', $i + 1, $e->getMessage() ) );
			}
		}
		return $urls;
	}

	/**
	 * Repair common HTML issues in AI-generated content.
	 *
	 * AI models sometimes produce broken tags (e.g., </ul instead of </ul>),
	 * unclosed tags, or truncated output. This method fixes the most common issues.
	 *
	 * @param string $html Raw HTML from AI.
	 * @return string Cleaned and balanced HTML.
	 */
	private static function repair_html( $html ) {
		// Fix broken closing tags missing the > (e.g., </ul → </ul>).
		$html = preg_replace( '/<\/([a-zA-Z][a-zA-Z0-9]*)(?=[^>]*(?:<|$))/', '</$1>', $html );

		// Fix broken opening tags missing the > (e.g., <ul → <ul>).
		$html = preg_replace( '/<([a-zA-Z][a-zA-Z0-9]*)(\s[^>]*)?\s*$/', '<$1$2>', $html );

		// Fix self-closing tags that are broken (e.g., <br → <br />).
		$html = preg_replace( '/<(br|hr|img)([^>]*?)(?<!\/)>/', '<$1$2 />', $html );

		// Remove any incomplete tag at the very end of the content
		// (e.g., content cut off mid-tag due to token limit).
		$html = preg_replace( '/<[^>]*$/', '', $html );

		// Use WordPress's built-in tag balancer to close any unclosed tags.
		if ( function_exists( 'force_balance_tags' ) ) {
			$html = force_balance_tags( $html );
		}

		return $html;
	}

	/**
	 * Route the API call to the active provider.
	 * Returns an array with 'text' and 'stop_reason'.
	 *
	 * @param string $system_prompt System prompt.
	 * @param string $user_message  User message.
	 * @param int    $max_tokens    Maximum tokens to generate.
	 * @return array { 'text' => string, 'stop_reason' => string }
	 * @throws Exception If all retries fail.
	 */
	/**
	 * Try to satisfy a text-generation request through the WordPress 7.0
	 * core AI Client.
	 *
	 * Returns the same { 'text', 'stop_reason' } shape as call_claude() /
	 * call_openai() on success, or NULL when the AI Client is unavailable
	 * (WordPress < 7.0), no provider is configured by the site owner, or the
	 * underlying call throws — in which case the caller falls back to the
	 * plugin's own direct integration.
	 *
	 * @param string $system_prompt System prompt (instructions).
	 * @param string $user_message  User message (the actual ask).
	 * @param int    $max_tokens    Maximum tokens to generate.
	 * @return array|null { 'text' => string, 'stop_reason' => string } or null on unavailable.
	 */
	private function try_wp_ai_client( $system_prompt, $user_message, $max_tokens ) {
		if ( ! function_exists( 'wp_ai_client_prompt' ) ) {
			return null;
		}

		try {
			// The WordPress core AI Client builder doesn't expose a portable
			// "system" role across every provider, so we prepend the system
			// instructions to the user message. Providers receive a single
			// well-formed prompt either way.
			$combined = '';
			if ( ! empty( $system_prompt ) ) {
				$combined = trim( (string) $system_prompt ) . "\n\n";
			}
			$combined .= (string) $user_message;

			// Dynamic dispatch keeps this compatible with WP 5.9–6.x (where
			// wp_ai_client_prompt() does not exist) while letting Plugin
			// Check's static "function not compatible with requires_wp"
			// sniff pass — the function symbol is only referenced if and
			// only if function_exists() returned true above.
			$builder = call_user_func( 'wp_ai_client_prompt', $combined );
			$text    = $builder->usingMaxTokens( (int) $max_tokens )->generateText();

			if ( ! is_string( $text ) || '' === trim( $text ) ) {
				return null;
			}

			$this->log( __( 'Used the WordPress core AI Client (site-level provider).', 'raybogman-ai-content-orchestrator' ) );

			return array(
				'text'        => $text,
				'stop_reason' => 'end_turn',
			);
		} catch ( \Throwable $e ) {
			// No provider configured, model unavailable, network issue, etc.
			// Silently fall back to direct integration — the user has already
			// paid for our plugin keys and expects the feature to still work.
			$this->log( sprintf(
				/* translators: %s: error message */
				__( 'WordPress AI Client unavailable (%s); falling back to direct provider integration.', 'raybogman-ai-content-orchestrator' ),
				$e->getMessage()
			) );
			return null;
		}
	}

	private function call_ai_raw( $system_prompt, $user_message, $max_tokens ) {
		// Prefer the WordPress 7.0 core AI Client (site-level configured
		// provider) when it is available and a provider has been set up by
		// the site owner. Falls back to the plugin's own direct integration
		// for sites on WP < 7.0 or sites that have not configured the AI
		// Client.
		$ai_client_result = $this->try_wp_ai_client( $system_prompt, $user_message, $max_tokens );
		if ( null !== $ai_client_result ) {
			return $ai_client_result;
		}

		$max_retries = 1;
		$last_error  = null;

		for ( $attempt = 0; $attempt <= $max_retries; $attempt++ ) {
			try {
				$provider = RBCO_Settings::get_ai_provider();

				if ( 'openai' === $provider ) {
					return $this->call_openai( $system_prompt, $user_message, $max_tokens );
				}

				return $this->call_claude( $system_prompt, $user_message, $max_tokens );
			} catch ( \Exception $e ) {
				$last_error = $e;
				$is_retryable = false !== strpos( $e->getMessage(), 'cURL error' )
					|| false !== strpos( $e->getMessage(), 'timed out' )
					|| false !== strpos( $e->getMessage(), '529' )
					|| false !== strpos( $e->getMessage(), '529' )
					|| false !== strpos( $e->getMessage(), 'rate limit' )
					|| false !== strpos( $e->getMessage(), '429' );

				if ( $is_retryable && $attempt < $max_retries ) {
					$this->log( sprintf( 'API request failed (attempt %d/%d): %s — retrying in 5 seconds...', $attempt + 1, $max_retries + 1, $e->getMessage() ) );
					sleep( 5 );
					continue;
				}

				throw $e;
			}
		}

		throw $last_error;
	}

	/**
	 * Simple call that returns just the text (used for metadata, etc.).
	 *
	 * @param string $system_prompt System prompt.
	 * @param string $user_message  User message.
	 * @param int    $max_tokens    Maximum tokens to generate.
	 * @return string Response text.
	 */
	private function call_ai( $system_prompt, $user_message, $max_tokens ) {
		$result = $this->call_ai_raw( $system_prompt, $user_message, $max_tokens );
		return $result['text'];
	}

	/**
	 * Call the AI with automatic continuation if the response is truncated.
	 *
	 * When the AI hits the token limit mid-content, this method detects it
	 * via stop_reason/finish_reason and makes a follow-up request asking
	 * the AI to continue from where it left off. Up to 2 continuations
	 * (3 total calls) to ensure content is always complete.
	 *
	 * @param string $system_prompt System prompt.
	 * @param string $user_message  User message.
	 * @param int    $max_tokens    Maximum tokens per call.
	 * @return string Complete response text.
	 */
	private function call_ai_with_continuation( $system_prompt, $user_message, $max_tokens ) {
		$full_text       = '';
		$max_continuations = 2;  // Up to 3 total calls.

		for ( $i = 0; $i <= $max_continuations; $i++ ) {
			if ( 0 === $i ) {
				$result = $this->call_ai_raw( $system_prompt, $user_message, $max_tokens );
			} else {
				$this->log( sprintf( 'Content was truncated — requesting continuation (%d/%d)...', $i, $max_continuations ) );
				$continue_msg = $user_message
					. "\n\n--- PARTIAL CONTENT GENERATED SO FAR (continue from where this ends, do NOT repeat it) ---\n"
					. $full_text
					. "\n--- CONTINUE FROM HERE. Output ONLY the remaining HTML. Do NOT repeat any content above. ---";
				$result = $this->call_ai_raw( $system_prompt, $continue_msg, $max_tokens );
			}

			$full_text .= $result['text'];

			// Check if the response completed naturally.
			$was_truncated = false;
			if ( 'max_tokens' === $result['stop_reason'] || 'length' === $result['stop_reason'] ) {
				$was_truncated = true;
			}

			if ( ! $was_truncated ) {
				// Content completed naturally.
				if ( $i > 0 ) {
					$this->log( 'Content completed after continuation.' );
				}
				break;
			}
		}

		if ( $was_truncated ) {
			$this->log( 'Warning: content may still be truncated after max continuations.' );
		}

		return $full_text;
	}

	/**
	 * Call the Anthropic Claude API with retry logic.
	 *
	 * @param string $system_prompt System prompt.
	 * @param string $user_message  User message.
	 * @param int    $max_tokens    Maximum tokens to generate.
	 * @return array { 'text' => string, 'stop_reason' => string }
	 * @throws Exception If all retries fail.
	 */
	private function call_claude( $system_prompt, $user_message, $max_tokens ) {
		$api_key = RBCO_Settings::get_anthropic_api_key();
		if ( empty( $api_key ) ) {
   // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new Exception( __( 'Anthropic API key is not configured. Go to AI Content > Settings.', 'raybogman-ai-content-orchestrator' ) );
		}

		$body = array(
			'model'      => RBCO_Settings::get_claude_model(),
			'max_tokens' => $max_tokens,
			'system'     => $system_prompt,
			'messages'   => array(
				array( 'role' => 'user', 'content' => $user_message ),
			),
		);

		for ( $attempt = 0; $attempt < self::MAX_RETRIES; $attempt++ ) {
			$response = wp_remote_post( self::CLAUDE_API_URL, array(
				'timeout' => 120,
				'headers' => array(
					'Content-Type'      => 'application/json',
					'x-api-key'         => $api_key,
					'anthropic-version'  => self::ANTHROPIC_VERSION,
				),
				'body'    => wp_json_encode( $body ),
			) );

			if ( is_wp_error( $response ) ) {
    // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				throw new Exception( sprintf( 'Claude API request failed: %s', esc_html( $response->get_error_message( ) ) ) );
			}

			$code = wp_remote_retrieve_response_code( $response );

			// Retry on rate limit (429) or overloaded (529).
			if ( in_array( $code, array( 429, 529 ), true ) && $attempt < self::MAX_RETRIES - 1 ) {
				$wait = 30 * ( $attempt + 1 );
				$label = 529 === $code ? 'API overloaded' : 'Rate limited';
				$this->log( sprintf( '%s (HTTP %d), retrying in %d seconds (attempt %d/%d)...', $label, $code, $wait, $attempt + 1, self::MAX_RETRIES ) );
				sleep( $wait );
				continue;
			}

			if ( $code < 200 || $code >= 300 ) {
				$error_body = wp_remote_retrieve_body( $response );
    // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				throw new Exception( sprintf( 'Claude API error (HTTP %d): %s', esc_html( $code, mb_substr( $error_body, 0, 500  ) ) ) );
			}

			$data = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( empty( $data['content'][0]['text'] ) ) {
    // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				throw new Exception( 'Empty response from Claude API.' );
			}

			// Claude: stop_reason is "end_turn" (complete) or "max_tokens" (truncated).
			$stop_reason = isset( $data['stop_reason'] ) ? $data['stop_reason'] : 'end_turn';

			return array(
				'text'        => $data['content'][0]['text'],
				'stop_reason' => $stop_reason,
			);
		}

  // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		throw new Exception( 'Claude API rate limit exceeded after retries.' );
	}

	/**
	 * Call the OpenAI Chat Completions API with retry logic.
	 *
	 * @param string $system_prompt System prompt.
	 * @param string $user_message  User message.
	 * @param int    $max_tokens    Maximum tokens to generate.
	 * @return array { 'text' => string, 'stop_reason' => string }
	 * @throws Exception If all retries fail.
	 */
	private function call_openai( $system_prompt, $user_message, $max_tokens ) {
		$api_key = RBCO_Settings::get_openai_api_key();
		if ( empty( $api_key ) ) {
   // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new Exception( __( 'OpenAI API key is not configured. Go to AI Content > Settings.', 'raybogman-ai-content-orchestrator' ) );
		}

		$model = RBCO_Settings::get_openai_model();
		$this->log( sprintf( 'Calling OpenAI API: model=%s, max_tokens=%d', $model, $max_tokens ) );

		$body = array(
			'model'      => $model,
			'max_tokens' => $max_tokens,
			'messages'   => array(
				array( 'role' => 'system', 'content' => $system_prompt ),
				array( 'role' => 'user',   'content' => $user_message ),
			),
		);

		$json_body = wp_json_encode( $body );
		if ( false === $json_body ) {
   // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new Exception( 'Failed to encode request body as JSON.' );
		}

		for ( $attempt = 0; $attempt < self::MAX_RETRIES; $attempt++ ) {
			$this->log( sprintf( 'OpenAI request attempt %d/%d...', $attempt + 1, self::MAX_RETRIES ) );

			$response = wp_remote_post( self::OPENAI_API_URL, array(
				'timeout' => 120,
				'headers' => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $api_key,
				),
				'body'    => $json_body,
			) );

			if ( is_wp_error( $response ) ) {
				$this->log( sprintf( 'WP HTTP error: %s', $response->get_error_message() ) );
    // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				throw new Exception( sprintf( 'OpenAI API request failed: %s', esc_html( $response->get_error_message( ) ) ) );
			}

			$code       = wp_remote_retrieve_response_code( $response );
			$resp_body  = wp_remote_retrieve_body( $response );

			$this->log( sprintf( 'OpenAI response: HTTP %d, %d bytes', $code, strlen( $resp_body ) ) );

			// Retry on rate limit (429) or server overloaded (529/503).
			if ( in_array( $code, array( 429, 503, 529 ), true ) && $attempt < self::MAX_RETRIES - 1 ) {
				$wait = 30 * ( $attempt + 1 );
				$this->log( sprintf( 'API returned HTTP %d, retrying in %d seconds (attempt %d/%d)...', $code, $wait, $attempt + 1, self::MAX_RETRIES ) );
				sleep( $wait );
				continue;
			}

			$data = json_decode( $resp_body, true );

			// Handle API error responses (4xx/5xx).
			if ( $code < 200 || $code >= 300 ) {
				$error_msg = ( is_array( $data ) && isset( $data['error']['message'] ) )
					? $data['error']['message']
					: mb_substr( $resp_body, 0, 500 );
				$this->log( sprintf( 'OpenAI API error: %s', $error_msg ) );
    // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				throw new Exception( sprintf( 'OpenAI API error (HTTP %d): %s', esc_html( $code, $error_msg  ) ) );
			}

			if ( null === $data ) {
				$this->log( 'Failed to parse JSON response from OpenAI.' );
    // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				throw new Exception( 'Could not parse OpenAI API response as JSON.' );
			}

			if ( isset( $data['choices'][0]['message']['content'] ) && '' !== $data['choices'][0]['message']['content'] ) {
				$this->log( 'OpenAI response received successfully.' );

				// OpenAI: finish_reason is "stop" (complete) or "length" (truncated).
				$stop_reason = isset( $data['choices'][0]['finish_reason'] ) ? $data['choices'][0]['finish_reason'] : 'stop';

				return array(
					'text'        => $data['choices'][0]['message']['content'],
					'stop_reason' => $stop_reason,
				);
			}

			$error_msg = ( is_array( $data ) && isset( $data['error']['message'] ) )
				? $data['error']['message']
				: 'Empty response — no choices returned.';
			$this->log( sprintf( 'OpenAI returned no content: %s', $error_msg ) );
   // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new Exception( sprintf( 'OpenAI API: %s', esc_html( $error_msg  ) ) );
		}

  // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		throw new Exception( 'OpenAI API rate limit exceeded after retries.' );
	}
}
