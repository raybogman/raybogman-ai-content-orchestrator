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

			'ultimate-guide' => array(
				'name'        => __( 'Ultimate Guide', 'raybogman-ai-content-orchestrator' ),
				'description' => __( 'Comprehensive deep-dive pillar content. Ranks for hundreds of long-tail keywords and builds topical authority.', 'raybogman-ai-content-orchestrator' ),
				'icon'        => 'dashicons-book',
				'target_words' => '2500-4000',
				'prompt'      => implode( "\n", array(
					'You are an expert SEO content writer specializing in comprehensive pillar content.',
					'Generate ONLY the HTML content — no JSON wrapper, no markdown fences, no explanation.',
					'Rules:',
					'- Start with a powerful introduction that establishes authority and explains why this is THE definitive guide',
					'- Include a detailed Table of Contents with anchor links to all H2 sections',
					'- Structure with 6-10 H2 sections, each with 2-3 H3 subsections',
					'- Cover the topic from beginner to advanced level',
					'- Include "Key Takeaway" boxes: <p><strong>Key Takeaway:</strong> [summary]</p> after major sections',
					'- Add data, statistics, and expert insights throughout',
					'- Include comparison tables using <table> where relevant',
					'- Use bullet and numbered lists extensively for scannability',
					'- Include a "Quick Summary" section near the top for skimmers',
					'- Add an "Advanced Tips" or "Expert Strategies" section',
					'- Include comprehensive FAQ section (H2) with 5-8 questions (H3 each)',
					'- End with a strong conclusion and call-to-action',
					'- Target 2500-4000 words (this is pillar content)',
					'- Use <strong> and <em> generously for keywords and emphasis',
					'- Content must be NEW and ORIGINAL',
					'- Write in the same language as the source website / prompt',
					'Output ONLY the HTML. No wrapping. No preamble.',
				) ),
			),

			'comparison' => array(
				'name'        => __( 'Comparison / Versus', 'raybogman-ai-content-orchestrator' ),
				'description' => __( '"X vs Y" format. Captures high-intent decision-stage searches with structured comparisons.', 'raybogman-ai-content-orchestrator' ),
				'icon'        => 'dashicons-columns',
				'target_words' => '1500-2500',
				'prompt'      => implode( "\n", array(
					'You are an expert SEO content writer specializing in comparison articles.',
					'Generate ONLY the HTML content — no JSON wrapper, no markdown fences, no explanation.',
					'Rules:',
					'- Start with an introduction explaining what is being compared and why the reader should care',
					'- Include a "Quick Comparison" summary table near the top using <table> with headers for each option and key criteria rows',
					'- Use H2 for each comparison category (e.g., "Features", "Pricing", "Ease of Use", "Pros and Cons")',
					'- For each category, discuss both/all options with equal depth and fairness',
					'- Include a "Pros and Cons" section for each option using bullet lists with checkmarks/crosses',
					'- Add a "Who Should Choose [Option A]" and "Who Should Choose [Option B]" section',
					'- Include a "Our Verdict" or "The Bottom Line" section with a clear recommendation',
					'- Use <strong> for key differentiators and winner highlights',
					'- Include FAQ section (H2) with 3-5 comparison-related questions (H3 each)',
					'- Add a call-to-action at the end',
					'- Target 1500-2500 words',
					'- Maintain objective, balanced tone while still giving a recommendation',
					'- Content must be NEW and ORIGINAL',
					'- Write in the same language as the source website / prompt',
					'Output ONLY the HTML. No wrapping. No preamble.',
				) ),
			),

			'case-study' => array(
				'name'        => __( 'Case Study', 'raybogman-ai-content-orchestrator' ),
				'description' => __( 'Real-world success story with data. Builds trust and credibility. Great for conversions and backlinks.', 'raybogman-ai-content-orchestrator' ),
				'icon'        => 'dashicons-chart-bar',
				'target_words' => '1500-2500',
				'prompt'      => implode( "\n", array(
					'You are an expert SEO content writer specializing in case studies.',
					'Generate ONLY the HTML content — no JSON wrapper, no markdown fences, no explanation.',
					'Rules:',
					'- Start with a compelling hook: the key result or transformation achieved',
					'- Include a "Quick Results" summary box near the top with 3-4 key metrics/outcomes in bold',
					'- Follow the Challenge → Solution → Results framework:',
					'  - H2 "The Challenge" — describe the problem, pain points, context',
					'  - H2 "The Solution" / "The Approach" — what was done, step by step',
					'  - H2 "The Results" — quantified outcomes, before/after data, metrics',
					'- Include specific numbers, percentages, and timeframes where possible',
					'- Add direct quotes or testimonial-style excerpts in <blockquote> tags',
					'- Include a "Key Lessons Learned" section with bullet points',
					'- Add a "How to Apply This" section for the reader',
					'- Include FAQ section (H2) with 3-4 related questions (H3 each)',
					'- End with a call-to-action related to achieving similar results',
					'- Target 1500-2500 words',
					'- Use storytelling elements to make data engaging',
					'- Content must be NEW and ORIGINAL',
					'- Write in the same language as the source website / prompt',
					'Output ONLY the HTML. No wrapping. No preamble.',
				) ),
			),

			'problem-solution' => array(
				'name'        => __( 'Problem-Solution', 'raybogman-ai-content-orchestrator' ),
				'description' => __( 'Identifies a pain point, then provides the fix. Directly matches search intent for problem-based queries.', 'raybogman-ai-content-orchestrator' ),
				'icon'        => 'dashicons-lightbulb',
				'target_words' => '1000-2000',
				'prompt'      => implode( "\n", array(
					'You are an expert SEO content writer specializing in problem-solution articles.',
					'Generate ONLY the HTML content — no JSON wrapper, no markdown fences, no explanation.',
					'Rules:',
					'- Start with empathy: acknowledge the reader\'s frustration or pain point (2-3 sentences)',
					'- H2 "The Problem" — clearly define the issue, who it affects, and why it matters',
					'- Include symptoms or signs the reader can identify with (bullet list)',
					'- H2 "Why This Happens" — explain root causes briefly',
					'- H2 "The Solution" — present the fix or approach with clear steps',
					'- Use H3 for sub-steps within the solution',
					'- Include a "Quick Fix" or "Immediate Action" callout for readers who need fast results',
					'- Add a "Prevention" or "How to Avoid This in the Future" section',
					'- Use <strong> for key actions and <em> for important warnings',
					'- Include FAQ section (H2) with 3-5 related questions (H3 each)',
					'- End with reassurance and a call-to-action',
					'- Target 1000-2000 words',
					'- Tone: empathetic, knowledgeable, reassuring',
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

			'data-driven' => array(
				'name'        => __( 'Data-Driven / Research', 'raybogman-ai-content-orchestrator' ),
				'description' => __( 'Statistics and research-backed analysis. Natural link magnet — content with data gets 2x more backlinks.', 'raybogman-ai-content-orchestrator' ),
				'icon'        => 'dashicons-chart-area',
				'target_words' => '1500-2500',
				'prompt'      => implode( "\n", array(
					'You are an expert SEO content writer specializing in data-driven analysis articles.',
					'Generate ONLY the HTML content — no JSON wrapper, no markdown fences, no explanation.',
					'Rules:',
					'- Start with the most surprising or impactful statistic as a hook',
					'- Include a "Key Findings" summary box near the top with 4-5 bullet points',
					'- Structure with H2 sections, each centered around a data point or trend',
					'- Include specific numbers, percentages, year-over-year comparisons',
					'- Present data in comparison tables using <table> where appropriate',
					'- After each data point, add "What This Means" analysis paragraphs',
					'- Include "Source:" references (can be general like "Industry reports show..." since we cannot link)',
					'- Use <strong> for key statistics and findings',
					'- Add a "Trends to Watch" or "What\'s Next" forward-looking section',
					'- Include an "Actionable Insights" section translating data into reader advice',
					'- Include FAQ section (H2) with 3-5 data-related questions (H3 each)',
					'- End with a data-backed conclusion and call-to-action',
					'- Target 1500-2500 words',
					'- Tone: authoritative, analytical, insightful',
					'- Content must be NEW and ORIGINAL',
					'- Write in the same language as the source website / prompt',
					'Output ONLY the HTML. No wrapping. No preamble.',
				) ),
			),

			'storytelling' => array(
				'name'        => __( 'Storytelling / Narrative', 'raybogman-ai-content-orchestrator' ),
				'description' => __( 'Personal or brand narrative. High engagement — stories are 22x more memorable than facts alone.', 'raybogman-ai-content-orchestrator' ),
				'icon'        => 'dashicons-format-status',
				'target_words' => '1000-2000',
				'prompt'      => implode( "\n", array(
					'You are an expert SEO content writer specializing in narrative-driven blog posts.',
					'Generate ONLY the HTML content — no JSON wrapper, no markdown fences, no explanation.',
					'Rules:',
					'- Start with a vivid scene, moment, or hook that pulls the reader in immediately',
					'- Follow a narrative arc: Setup → Conflict/Challenge → Journey → Resolution → Lesson',
					'- Use H2 sections to mark major story beats (don\'t number them — use descriptive titles)',
					'- Write in first person or close third person for intimacy',
					'- Include sensory details, dialogue, and emotional moments',
					'- Weave the SEO topic naturally into the story — don\'t force keywords',
					'- Include 1-2 <blockquote> sections for powerful quotes or realizations',
					'- After the story, add an H2 "What This Means for You" section connecting the story to the reader',
					'- Include practical takeaways in a bullet list',
					'- Include a brief FAQ section (H2) with 2-3 questions (H3 each)',
					'- End with a reflective conclusion and soft call-to-action',
					'- Target 1000-2000 words',
					'- Tone: authentic, vulnerable, engaging — like a conversation',
					'- Content must be NEW and ORIGINAL',
					'- Write in the same language as the source website / prompt',
					'Output ONLY the HTML. No wrapping. No preamble.',
				) ),
			),

			'opinion' => array(
				'name'        => __( 'Opinion / Thought Leadership', 'raybogman-ai-content-orchestrator' ),
				'description' => __( 'Expert perspective with a strong take. Builds authority, sparks discussion, and drives social shares.', 'raybogman-ai-content-orchestrator' ),
				'icon'        => 'dashicons-megaphone',
				'target_words' => '1000-1500',
				'prompt'      => implode( "\n", array(
					'You are an expert SEO content writer specializing in thought leadership articles.',
					'Generate ONLY the HTML content — no JSON wrapper, no markdown fences, no explanation.',
					'Rules:',
					'- Start with a bold, provocative opening statement or contrarian take',
					'- H2 "The Conventional Wisdom" — briefly present the common view',
					'- H2 "Why I Disagree" or "A Different Perspective" — present the argument with conviction',
					'- Support the opinion with evidence, data, examples, and logical reasoning',
					'- Acknowledge counterarguments fairly, then rebut them',
					'- Include 1-2 <blockquote> sections for impactful statements',
					'- H2 "What This Means for [Industry/Audience]" — practical implications',
					'- H2 "What Should We Do About It" — actionable recommendations',
					'- Use <strong> for thesis statements and key arguments',
					'- Include a brief FAQ section (H2) with 2-3 questions (H3 each)',
					'- End with a strong closing statement and call-to-action inviting discussion',
					'- Target 1000-1500 words (keep it punchy)',
					'- Tone: confident, authoritative, respectful but firm',
					'- Content must be NEW and ORIGINAL',
					'- Write in the same language as the source website / prompt',
					'Output ONLY the HTML. No wrapping. No preamble.',
				) ),
			),

			'checklist' => array(
				'name'        => __( 'Checklist', 'raybogman-ai-content-orchestrator' ),
				'description' => __( 'Actionable to-do list. High utility format that drives saves, bookmarks, and return visits.', 'raybogman-ai-content-orchestrator' ),
				'icon'        => 'dashicons-yes-alt',
				'target_words' => '1000-1500',
				'prompt'      => implode( "\n", array(
					'You are an expert SEO content writer specializing in checklist-format articles.',
					'Generate ONLY the HTML content — no JSON wrapper, no markdown fences, no explanation.',
					'Rules:',
					'- Start with a brief introduction explaining who this checklist is for and when to use it',
					'- Include a "Quick Overview" section listing all checklist categories',
					'- Group checklist items under H2 category headings',
					'- Format each checklist item as: <p>&#9744; <strong>[Action item]</strong> — [brief explanation, 1-2 sentences]</p>',
					'- Include 15-25 total checklist items across 3-5 categories',
					'- Add "Bonus" or "Advanced" items at the end for overachievers',
					'- Include a "Downloadable Version" mention or "Save this page" prompt',
					'- Add a "Common Oversights" section listing items people often forget',
					'- Include FAQ section (H2) with 3-4 related questions (H3 each)',
					'- End with a motivating conclusion and call-to-action',
					'- Target 1000-1500 words',
					'- Tone: practical, organized, empowering',
					'- Content must be NEW and ORIGINAL',
					'- Write in the same language as the source website / prompt',
					'Output ONLY the HTML. No wrapping. No preamble.',
				) ),
			),
			'recipe' => array(
				'name'        => __( 'Recipe', 'raybogman-ai-content-orchestrator' ),
				'description' => __( 'SEO-optimized recipe format with structured data hints. Targets rich snippets, "how to make" queries, and Google Recipe carousel.', 'raybogman-ai-content-orchestrator' ),
				'icon'        => 'dashicons-carrot',
				'target_words' => '1000-2000',
				'prompt'      => implode( "\n", array(
					'You are an expert food blogger and SEO specialist who writes recipe posts optimized for Google rich snippets and the Recipe carousel.',
					'Generate ONLY the HTML content — no JSON wrapper, no markdown fences, no explanation.',
					'Follow this proven SEO recipe blog structure:',
					'',
					'1. COMPELLING INTRODUCTION (2-3 paragraphs):',
					'   - Open with a personal or sensory hook (smell, taste, memory)',
					'   - Explain what makes this recipe special or unique',
					'   - Mention who it is perfect for and what occasion',
					'   - Naturally include the primary keyword in the first paragraph',
					'',
					'2. H2 "Why You Will Love This Recipe"',
					'   - 4-6 bullet points with <strong>bold benefit</strong> followed by explanation',
					'   - E.g., <strong>Ready in 30 minutes</strong> — perfect for busy weeknight dinners',
					'',
					'3. H2 "Ingredients"',
					'   - Use an unordered list (<ul>) with each ingredient on its own <li>',
					'   - Bold the main ingredient in each line: <strong>2 cups flour</strong> — all-purpose works best',
					'   - Group into sub-sections with H3 if the recipe has multiple components (e.g., "For the Sauce", "For the Filling")',
					'   - Include exact measurements',
					'',
					'4. H2 "Substitutions and Variations"',
					'   - Bullet list of common swaps (dietary restrictions, preferences)',
					'   - Use <strong> for the ingredient name and <em> for the substitute',
					'',
					'5. H2 "Instructions" / "How to Make [Recipe Name]"',
					'   - Use an ordered list (<ol>) with numbered steps',
					'   - Each step should be 1-3 sentences, clear and actionable',
					'   - Start each step with a bold action verb: <strong>Preheat</strong>, <strong>Mix</strong>, <strong>Bake</strong>',
					'   - Include time and temperature for every cooking step',
					'   - Add <p><strong>Pro tip:</strong> [helpful hint]</p> after key steps',
					'',
					'6. H2 "Expert Tips for Best Results"',
					'   - 4-6 bullet points with insider knowledge',
					'   - Include make-ahead tips, storage instructions, reheating advice',
					'',
					'7. H2 "Nutrition Information" (approximate per serving)',
					'   - Present in a simple HTML table: Calories, Protein, Carbs, Fat, Fiber, Sodium',
					'   - Add a note: "Nutrition values are approximate estimates"',
					'',
					'8. H2 "Storage and Meal Prep"',
					'   - How to store (fridge, freezer)',
					'   - How long it lasts',
					'   - How to reheat',
					'',
					'9. H2 "Frequently Asked Questions" (FAQ)',
					'   - 4-6 questions as H3 headings',
					'   - Common questions: "Can I make this ahead?", "Can I freeze this?", "What to serve with this?", substitution questions',
					'',
					'10. RECIPE CARD SUMMARY at the end:',
					'   - Wrap in a styled div: <div style="background:#f9f9f9; border:2px solid #e0e0e0; border-radius:8px; padding:24px; margin:24px 0;">',
					'   - H2 with the recipe name',
					'   - <p><strong>Prep Time:</strong> X min | <strong>Cook Time:</strong> X min | <strong>Total Time:</strong> X min</p>',
					'   - <p><strong>Servings:</strong> X | <strong>Difficulty:</strong> Easy/Medium/Hard</p>',
					'   - Brief 1-sentence description',
					'   - Condensed ingredient list (unordered)',
					'   - Condensed numbered steps (ordered)',
					'   - This card format helps Google extract structured recipe data',
					'',
					'SEO rules:',
					'- Use the recipe name in H1 (title), first paragraph, at least 2 H2 headings, and conclusion',
					'- Include long-tail variations naturally: "easy [recipe]", "best [recipe]", "homemade [recipe]"',
					'- Use <strong> and <em> for keyword emphasis throughout',
					'- Internal linking prompts: "If you enjoyed this, try our [related recipe]" (use placeholder text)',
					'- Target 1000-2000 words (Google favors detailed recipe posts)',
					'- Write in warm, encouraging, conversational tone',
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
		$styles     = self::get_styles();
		$free_keys  = array( 'standard', 'how-to', 'listicle', 'beginners-guide' );
		$output     = array();
		foreach ( $styles as $key => $style ) {
			if ( ! rbco_is_pro() && ! in_array( $key, $free_keys, true ) ) {
				continue;
			}
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

			'ultimate-guide' => '<h2>Quick Summary</h2><p>Key stats and what this guide covers.</p><h2>Table of Contents</h2><h2>Section 1</h2><h3>Subsection A</h3><p>Deep content.</p><p><strong>Key Takeaway:</strong> Summary of this section.</p><h3>Subsection B</h3><table><tr><th>Metric</th><th>Value</th></tr><tr><td>Example</td><td>Data</td></tr></table><h2>Advanced Tips</h2><h2>FAQ</h2><h3>Question?</h3><p>Answer.</p>',

			'comparison' => '<h2>Quick Comparison</h2><table><tr><th>Feature</th><th>Option A</th><th>Option B</th></tr><tr><td>Price</td><td>$X</td><td>$Y</td></tr></table><h2>Category: Ease of Use</h2><p>Analysis of both options.</p><h2>Pros and Cons</h2><h3>Option A</h3><ul><li>&#10004; Pro</li><li>&#10008; Con</li></ul><h2>Our Verdict</h2><p>Recommendation + who should choose what.</p>',

			'case-study' => '<p><strong>Headline result achieved.</strong></p><div style="background:#f0f7ff;border-left:4px solid #2271b1;padding:12px;"><strong>Quick Results:</strong> 340% increase, $18K revenue</div><h2>The Challenge</h2><p>Problem description.</p><h2>The Solution</h2><p>What was done.</p><blockquote>"Testimonial quote."</blockquote><h2>The Results</h2><p>Data and metrics.</p><h2>Key Lessons</h2><ul><li>Lesson 1</li></ul>',

			'problem-solution' => '<p>Empathetic opening acknowledging the pain point.</p><h2>The Problem</h2><ul><li>Symptom 1</li><li>Symptom 2</li></ul><h2>Why This Happens</h2><p>Root cause explanation.</p><h2>The Solution</h2><h3>Fix Step 1</h3><p>Actionable instruction.</p><p><strong>Quick fix:</strong> Immediate action.</p><h2>Prevention</h2><h2>FAQ</h2>',

			'beginners-guide' => '<h2>What You Will Learn</h2><ul><li>Topic basics</li><li>How it works</li></ul><h2>What is [Topic]?</h2><p>Simple, jargon-free explanation.</p><p><strong>What does [term] mean?</strong> Plain language definition.</p><h2>Getting Started</h2><ol><li>First step</li><li>Second step</li></ol><h2>Common Beginner Mistakes</h2><h2>Next Steps</h2>',

			'data-driven' => '<p><strong>78% of consumers trust custom content.</strong></p><div style="background:#f0f7ff;border-left:4px solid #2271b1;padding:12px;"><h3>Key Findings</h3><ul><li>Stat 1</li><li>Stat 2</li></ul></div><h2>Topic: Data Analysis</h2><table><tr><th>Metric</th><th>Value</th></tr><tr><td>Example</td><td>42%</td></tr></table><p><strong>What this means:</strong> Analysis.</p><h2>Actionable Insights</h2>',

			'storytelling' => '<p><em>Vivid opening scene that pulls the reader in.</em></p><h2>The Beginning</h2><p>Setup with sensory details and emotional hooks.</p><h2>The Turning Point</h2><p>Challenge or conflict.</p><blockquote>"Powerful realization."</blockquote><h2>The Resolution</h2><p>What changed and why.</p><h2>What This Means for You</h2><ul><li>Takeaway 1</li></ul>',

			'opinion' => '<p><strong>Bold, provocative opening statement.</strong></p><h2>The Conventional Wisdom</h2><p>What most people believe.</p><h2>Why I Disagree</h2><p>Evidence-backed contrarian argument.</p><blockquote>"Key thesis statement."</blockquote><h2>What Should We Do About It</h2><ul><li>Recommendation</li></ul><p><strong>What\'s your take?</strong></p>',

			'checklist' => '<h2>Quick Overview</h2><ul><li>Category 1 (X items)</li></ul><h2>Category 1</h2><p>&#9744; <strong>Action item</strong> — brief explanation.</p><p>&#9744; <strong>Action item</strong> — brief explanation.</p><h2>Category 2</h2><p>&#9744; <strong>Action item</strong> — brief explanation.</p><h2>Common Oversights</h2><ul><li>Often forgotten item</li></ul>',

			'recipe' => '<h2>Why You\'ll Love This</h2><ul><li><strong>Ready in 30 min</strong></li></ul><h2>Ingredients</h2><ul><li><strong>2 cups flour</strong></li></ul><h2>Instructions</h2><ol><li><strong>Preheat</strong> oven to 350°F.</li><li><strong>Mix</strong> ingredients.</li></ol><h2>Nutrition</h2><table><tr><th>Calories</th><td>196</td></tr></table><div style="background:#f9f9f9;border:2px solid #e0e0e0;border-radius:8px;padding:16px;"><strong>Prep:</strong> 10 min | <strong>Cook:</strong> 50 min</div>',
		);
	}
}
