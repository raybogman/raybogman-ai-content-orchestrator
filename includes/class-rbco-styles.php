<?php
/**
 * Blog style definitions.
 *
 * Each style provides a unique content generation prompt tailored to a specific
 * blog format. These are based on SEO and content marketing best practices.
 *
 * @package Raybogman_Content_Orchestrator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RBCO_Styles
 *
 * Defines available blog post styles with their display metadata and
 * AI content generation prompts.
 */
class RBCO_Styles {

	/**
	 * Get all available blog styles.
	 *
	 * @return array Associative array of style_key => style definition.
	 */
	public static function get_styles() {
		return array(

			'standard' => array(
				'name'        => __( 'Standard Blog Post', 'raybogman-ai-content-orchestrator' ),
				'description' => __( 'Well-rounded article with introduction, table of contents, key sections, and FAQ. Good all-purpose format.', 'raybogman-ai-content-orchestrator' ),
				'icon'        => 'dashicons-admin-post',
				'target_words' => '1000-2000',
				'prompt'      => implode( "\n", array(
					'You are an expert SEO content writer and WordPress specialist.',
					'Generate ONLY the HTML content — no JSON wrapper, no markdown fences, no explanation before or after.',
					'Just output raw HTML for the WordPress post body.',
					'Rules:',
					'- Start with a compelling introduction paragraph (2-3 sentences)',
					'- Use H2 and H3 for structure (WordPress uses the title as H1)',
					'- Include a Table of Contents at the top using anchor links',
					'- Write in short paragraphs (2-3 sentences max)',
					'- Use bullet/numbered lists where appropriate',
					'- Include FAQ section at the end with H2 + individual H3 per question',
					'- Use <strong> and <em> for keyword emphasis',
					'- Include relevant statistics or data points',
					'- Add a clear call-to-action at the end',
					'- Target 1000-2000 words',
					'- Use transition words for readability',
					'- Content must be NEW and ORIGINAL',
					'- Write in the same language as the source website / prompt',
					'Output ONLY the HTML. No wrapping. No preamble.',
				) ),
			),

			'how-to' => array(
				'name'        => __( 'How-To Guide', 'raybogman-ai-content-orchestrator' ),
				'description' => __( 'Step-by-step instructions. Optimized for featured snippets and "how to" search queries.', 'raybogman-ai-content-orchestrator' ),
				'icon'        => 'dashicons-editor-ol',
				'target_words' => '1500-2500',
				'prompt'      => implode( "\n", array(
					'You are an expert SEO content writer specializing in how-to guides.',
					'Generate ONLY the HTML content — no JSON wrapper, no markdown fences, no explanation.',
					'Rules:',
					'- Start with a brief introduction explaining what the reader will learn and why it matters (2-3 sentences)',
					'- Include a "What You Will Need" or "Prerequisites" section if relevant',
					'- Use H2 for the main "How to [Topic]" heading',
					'- Use H3 for each numbered step: "Step 1: [Action]", "Step 2: [Action]", etc.',
					'- Each step should have 2-4 sentences of clear, actionable instruction',
					'- Include pro tips in <strong>Pro tip:</strong> callouts within steps',
					'- Add a "Common Mistakes to Avoid" section using an unordered list',
					'- End with a "Summary" section that recaps all steps in a numbered list',
					'- Include FAQ section (H2) with 3-5 related questions (H3 each)',
					'- Use <strong> and <em> for key terms and important warnings',
					'- Add a call-to-action at the end',
					'- Target 1500-2500 words',
					'- Write in imperative/instructional tone',
					'- Content must be NEW and ORIGINAL',
					'- Write in the same language as the source website / prompt',
					'Output ONLY the HTML. No wrapping. No preamble.',
				) ),
			),

			'listicle' => array(
				'name'        => __( 'Listicle', 'raybogman-ai-content-orchestrator' ),
				'description' => __( 'Numbered list format (e.g., "10 Best..."). Highly scannable with 36% higher CTR in search results.', 'raybogman-ai-content-orchestrator' ),
				'icon'        => 'dashicons-list-view',
				'target_words' => '1000-2000',
				'prompt'      => implode( "\n", array(
					'You are an expert SEO content writer specializing in listicle-format articles.',
					'Generate ONLY the HTML content — no JSON wrapper, no markdown fences, no explanation.',
					'Rules:',
					'- Start with a compelling introduction (2-3 sentences) that hooks the reader and previews the list',
					'- Include a Table of Contents with anchor links to each list item',
					'- Use H2 for each numbered list item: "1. [Item Title]", "2. [Item Title]", etc.',
					'- Each list item should have 100-200 words with a clear explanation',
					'- Within each item, use <strong> for the key takeaway',
					'- Include a "Why this matters:" or "Key benefit:" highlight per item',
					'- Use bullet points within items for sub-details',
					'- Add a "Bonus" or "Honorable Mention" item at the end',
					'- Include a "Conclusion" section that summarizes the top 3 picks',
					'- Include FAQ section (H2) with 3-4 related questions (H3 each)',
					'- Add a call-to-action at the end',
					'- Target 1000-2000 words',
					'- Keep energy high and tone enthusiastic but professional',
					'- Content must be NEW and ORIGINAL',
					'- Write in the same language as the source website / prompt',
					'Output ONLY the HTML. No wrapping. No preamble.',
				) ),
			),

			'beginners-guide' => array(
				'name'        => __( 'Beginner\'s Guide', 'raybogman-ai-content-orchestrator' ),
				'description' => __( 'Entry-level explainer. Captures "what is" and "how to start" top-of-funnel search traffic.', 'raybogman-ai-content-orchestrator' ),
				'icon'        => 'dashicons-welcome-learn-more',
				'target_words' => '1500-2500',
				'prompt'      => implode( "\n", array(
					'You are an expert SEO content writer specializing in beginner-friendly educational content.',
					'Generate ONLY the HTML content — no JSON wrapper, no markdown fences, no explanation.',
					'Rules:',
					'- Start with a welcoming, non-intimidating introduction that tells the reader "this guide is for you"',
					'- Include a "What You Will Learn" section near the top using a bullet list',
					'- Include a Table of Contents with anchor links',
					'- H2 "What is [Topic]?" — define the concept in simple, jargon-free language',
					'- H2 "Why [Topic] Matters" — explain relevance and benefits',
					'- H2 "How [Topic] Works" — break down the mechanics simply',
					'- H2 "Getting Started" — practical first steps',
					'- Include "Jargon Buster" callouts: <p><strong>What does [term] mean?</strong> [simple explanation]</p>',
					'- Use analogies and real-world examples to explain complex concepts',
					'- Add a "Common Beginner Mistakes" section',
					'- Include a "Next Steps" or "Where to Go From Here" section',
					'- Include FAQ section (H2) with 4-6 beginner questions (H3 each)',
					'- End with encouragement and a call-to-action',
					'- Target 1500-2500 words',
					'- Tone: friendly, patient, encouraging — like a mentor',
					'- NEVER assume prior knowledge',
					'- Content must be NEW and ORIGINAL',
					'- Write in the same language as the source website / prompt',
					'Output ONLY the HTML. No wrapping. No preamble.',
				) ),
			),

		);
	}

	/**
	 * Get a single style by key.
	 *
	 * @param string $key Style key.
	 * @return array|null Style definition or null if not found.
	 */
	public static function get_style( $key ) {
		$styles = self::get_styles();
		return isset( $styles[ $key ] ) ? $styles[ $key ] : null;
	}

	/**
	 * Get the content generation prompt for a style.
	 * Falls back to 'standard' if the key is invalid.
	 *
	 * @param string $key Style key.
	 * @return string The system prompt for content generation.
	 */
	public static function get_prompt( $key ) {
		$style = self::get_style( $key );
		if ( ! $style ) {
			$style = self::get_style( 'standard' );
		}
		return $style['prompt'];
	}

	/**
	 * Get styles formatted for the admin JS.
	 *
	 * @return array Simplified array for wp_localize_script.
	 */
	public static function get_styles_for_js() {
		$styles = self::get_styles();
		$output = array();
		foreach ( $styles as $key => $style ) {
			$output[] = array(
				'key'          => $key,
				'name'         => $style['name'],
				'description'  => $style['description'],
				'icon'         => $style['icon'],
				'target_words' => $style['target_words'],
			);
		}
		return $output;
	}

	/**
	 * Get short preview examples for each style (used for hover tooltips).
	 * These are truncated versions showing just the structure — not full examples.
	 *
	 * @return array Associative array of style_key => short HTML preview.
	 */
	public static function get_short_previews() {
		return array(
			'standard' => '<h2>Table of Contents</h2><ul><li><a href="#">Section 1</a></li><li><a href="#">Section 2</a></li></ul><h2>Section Title</h2><p>Introduction paragraph with <strong>keyword emphasis</strong> and relevant data points.</p><h3>Subsection</h3><p>Short paragraphs (2-3 sentences). Uses bullet lists and transition words.</p><h2>FAQ</h2><h3>Common question?</h3><p>Clear, helpful answer.</p><p><strong>Call-to-action at the end.</strong></p>',

			'how-to' => '<h2>What You Will Need</h2><ul><li>Prerequisite item 1</li><li>Prerequisite item 2</li></ul><h2>How to [Topic]</h2><h3>Step 1: Action Verb</h3><p>Clear instruction (1-3 sentences).</p><p><strong>Pro tip:</strong> Insider hint for better results.</p><h3>Step 2: Next Action</h3><p>Detailed actionable guidance.</p><h2>Common Mistakes to Avoid</h2><ul><li>Mistake #1</li></ul><h2>Summary</h2><ol><li>Recap step 1</li><li>Recap step 2</li></ol>',

			'listicle' => '<h2>Table of Contents</h2><h2>1. First Item Title</h2><p><strong>Key benefit:</strong> Why this matters.</p><p>100-200 word explanation.</p><h2>2. Second Item Title</h2><p><strong>Key benefit:</strong> Highlight.</p><h2>Bonus: Extra Pick</h2><p>Honorable mention.</p><h2>Conclusion</h2><p>Summary of top 3 picks + CTA.</p>',

			'beginners-guide' => '<h2>What You Will Learn</h2><ul><li>Topic basics</li><li>How it works</li></ul><h2>What is [Topic]?</h2><p>Simple, jargon-free explanation.</p><p><strong>What does [term] mean?</strong> Plain language definition.</p><h2>Getting Started</h2><ol><li>First step</li><li>Second step</li></ol><h2>Common Beginner Mistakes</h2><h2>Next Steps</h2>',

		);
	}
}
