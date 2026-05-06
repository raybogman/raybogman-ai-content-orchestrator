<?php
/**
 * Content repurposing — generates multi-platform versions from a blog post.
 *
 * From one AI-generated blog post, creates ready-to-use content for:
 *   - Email newsletter (shorter, CTA link back to blog)
 *   - X/Twitter thread (key points as numbered thread)
 *   - Instagram caption (with hashtags)
 *   - Pinterest pin description (SEO-optimized for Pinterest search)
 *
 * LinkedIn is handled separately by RBCO_Generator::generate_linkedin_post().
 *
 * @package Raybogman_Content_Orchestrator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RBCO_Repurposer {

	/**
	 * Generate all repurposed content formats from a blog post.
	 *
	 * @param string $blog_html The blog HTML content.
	 * @param array  $meta      SEO metadata (seo_title, focus_keyphrase, meta_description).
	 * @param string $blog_url  URL of the published blog post (for CTAs).
	 * @return array Keyed array: 'email', 'twitter', 'instagram', 'pinterest'.
	 */
	public static function generate_all( $blog_html, $meta, $blog_url = '' ) {
		$generator = new RBCO_Generator();
		$blog_text = wp_strip_all_tags( $blog_html );
		if ( mb_strlen( $blog_text ) > 5000 ) {
			$blog_text = mb_substr( $blog_text, 0, 5000 ) . '...';
		}

		$results = array(
			'email'     => '',
			'twitter'   => '',
			'instagram' => '',
			'pinterest' => '',
		);

		// Generate each format. Catch individual failures so one doesn't block others.
		try {
			$results['email'] = self::generate_email( $generator, $blog_text, $meta, $blog_url );
		} catch ( \Throwable $e ) {
			$results['email'] = '(Generation failed: ' . $e->getMessage() . ')';
		}

		try {
			$results['twitter'] = self::generate_twitter( $generator, $blog_text, $meta, $blog_url );
		} catch ( \Throwable $e ) {
			$results['twitter'] = '(Generation failed: ' . $e->getMessage() . ')';
		}

		try {
			$results['instagram'] = self::generate_instagram( $generator, $blog_text, $meta );
		} catch ( \Throwable $e ) {
			$results['instagram'] = '(Generation failed: ' . $e->getMessage() . ')';
		}

		try {
			$results['pinterest'] = self::generate_pinterest( $generator, $blog_text, $meta );
		} catch ( \Throwable $e ) {
			$results['pinterest'] = '(Generation failed: ' . $e->getMessage() . ')';
		}

		return $results;
	}

	/**
	 * Email newsletter version.
	 */
	private static function generate_email( $generator, $blog_text, $meta, $blog_url ) {
		$system = implode( "\n", array(
			'You write engaging email newsletter summaries from blog posts.',
			'Rules:',
			'- Output ONLY the email body text — no subject line, no HTML tags, no markdown',
			'- Start with a friendly greeting: "Hey,"',
			'- Summarize the blog in 3-4 short paragraphs (conversational, personal tone)',
			'- Include 2-3 key takeaways as bullet points',
			'- End with a clear call-to-action pointing to the full article',
			'- Target length: 150-250 words',
			'- Write in the same language as the blog content',
		) );

		$user = sprintf(
			"Blog title: %s\nBlog URL: %s\n\nBlog content:\n%s\n\nWrite the email newsletter body now.",
			$meta['seo_title'] ?? '', $blog_url, $blog_text
		);

		return trim( self::call_ai( $generator, $system, $user ) );
	}

	/**
	 * X/Twitter thread version.
	 */
	private static function generate_twitter( $generator, $blog_text, $meta, $blog_url ) {
		$system = implode( "\n", array(
			'You write engaging X (Twitter) threads from blog posts.',
			'Rules:',
			'- Output a numbered thread (1/, 2/, 3/, etc.)',
			'- First tweet: strong hook that stops the scroll (question or bold statement)',
			'- 5-8 tweets total, each under 280 characters',
			'- Last tweet: call-to-action with the article link',
			'- Use line breaks between tweets for readability',
			'- Include 2-3 relevant hashtags on the last tweet only',
			'- Output ONLY the thread text — no explanation, no markdown',
			'- Write in the same language as the blog content',
		) );

		$user = sprintf(
			"Blog title: %s\nBlog URL: %s\n\nBlog content:\n%s\n\nWrite the X/Twitter thread now.",
			$meta['seo_title'] ?? '', $blog_url, $blog_text
		);

		return trim( self::call_ai( $generator, $system, $user ) );
	}

	/**
	 * Instagram caption version.
	 */
	private static function generate_instagram( $generator, $blog_text, $meta ) {
		$system = implode( "\n", array(
			'You write engaging Instagram captions from blog posts.',
			'Rules:',
			'- First line: strong hook (question or bold statement) — this is what shows before "more"',
			'- 2-3 short paragraphs with key insights from the blog',
			'- Use emoji sparingly (2-4 per caption, professional tone)',
			'- End with a call-to-action: "Link in bio" or similar',
			'- Add 15-20 relevant hashtags at the bottom (mix of broad and niche)',
			'- Target length: 150-300 words (Instagram allows up to 2200 characters)',
			'- Output ONLY the caption — no explanation, no markdown',
			'- Write in the same language as the blog content',
		) );

		$user = sprintf(
			"Blog title: %s\nFocus keyphrase: %s\n\nBlog content:\n%s\n\nWrite the Instagram caption now.",
			$meta['seo_title'] ?? '', $meta['focus_keyphrase'] ?? '', $blog_text
		);

		return trim( self::call_ai( $generator, $system, $user ) );
	}

	/**
	 * Pinterest pin description.
	 */
	private static function generate_pinterest( $generator, $blog_text, $meta ) {
		$system = implode( "\n", array(
			'You write SEO-optimized Pinterest pin descriptions from blog posts.',
			'Rules:',
			'- Output ONLY the pin description — no title, no markdown',
			'- Start with the most important keyword/phrase (Pinterest SEO)',
			'- 2-3 sentences that describe the blog content enticingly',
			'- Include relevant keywords naturally (Pinterest is a search engine)',
			'- End with a subtle CTA like "Click to read more" or "Save this pin"',
			'- Target length: 100-200 words',
			'- Add 3-5 relevant hashtags at the end',
			'- Write in the same language as the blog content',
		) );

		$user = sprintf(
			"Blog title: %s\nFocus keyphrase: %s\nMeta description: %s\n\nBlog content:\n%s\n\nWrite the Pinterest pin description now.",
			$meta['seo_title'] ?? '', $meta['focus_keyphrase'] ?? '', $meta['meta_description'] ?? '', $blog_text
		);

		return trim( self::call_ai( $generator, $system, $user ) );
	}

	/**
	 * Call the AI provider via the generator's internal method.
	 *
	 * Uses reflection to access the private call_ai method since the
	 * generator doesn't expose a public "generate any text" method.
	 * Falls back to a new generator instance if needed.
	 */
	private static function call_ai( $generator, $system_prompt, $user_message ) {
		// Use the generator's build to call AI with proper provider routing.
		$method = new \ReflectionMethod( $generator, 'call_ai' );
		$method->setAccessible( true );
		return $method->invoke( $generator, $system_prompt, $user_message, 1024 );
	}
}
