<?php
/**
 * Blog style examples page — shows sample output for each style.
 *
 * @package Raybogman_Content_Orchestrator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rbco_styles = RBCO_Styles::get_styles();

// Static example HTML for each style. These demonstrate the structure
// and format the AI will generate when that style is selected.
$rbco_examples = array(

	'standard'        => '
<p>In today\'s competitive digital landscape, understanding <strong>content marketing fundamentals</strong> is essential for any business looking to grow online. This guide explores the core strategies that drive real results.</p>

<h2>Table of Contents</h2>
<ul>
<li><a href="#what-is">What is Content Marketing?</a></li>
<li><a href="#benefits">Key Benefits</a></li>
<li><a href="#faq">FAQ</a></li>
</ul>

<h2 id="what-is">What is Content Marketing?</h2>
<p>Content marketing is a strategic approach focused on creating valuable, relevant content to attract and retain a defined audience. According to recent studies, <strong>70% of consumers prefer learning about a company through articles</strong> rather than advertisements.</p>

<h2 id="benefits">Key Benefits for Your Business</h2>
<h3>Increased Organic Traffic</h3>
<p>Companies that blog regularly receive <em>55% more website visitors</em> than those that don\'t. Consistent content builds long-term SEO value.</p>

<h3>Building Trust and Authority</h3>
<p>Quality content positions your brand as an industry expert. When readers find genuine value, they return — and they convert.</p>

<h2 id="faq">Frequently Asked Questions</h2>
<h3>How often should I publish content?</h3>
<p>Aim for consistency over frequency. Two quality posts per week outperform daily low-effort content.</p>

<h3>How long before I see results?</h3>
<p>Most content strategies take 3-6 months to show significant organic traffic growth. Patience and consistency are key.</p>

<p><strong>Ready to transform your content strategy?</strong> Start by auditing your existing content and identifying gaps in your audience\'s journey.</p>',

	'how-to'          => '
<p>Want to <strong>improve your website\'s loading speed</strong>? A fast website isn\'t just a nice-to-have — it directly impacts your search rankings and conversion rates. Let\'s walk through it step by step.</p>

<h2>What You Will Need</h2>
<ul>
<li>Access to your website\'s hosting panel</li>
<li>A speed testing tool (GTmetrix or PageSpeed Insights)</li>
<li>30-60 minutes of focused time</li>
</ul>

<h2>How to Speed Up Your Website</h2>

<h3>Step 1: Test Your Current Speed</h3>
<p>Run your website through <strong>Google PageSpeed Insights</strong>. Note your current score for both mobile and desktop. This is your baseline.</p>
<p><strong>Pro tip:</strong> Test 3-4 key pages, not just the homepage. Product and blog pages often have different bottlenecks.</p>

<h3>Step 2: Optimize Your Images</h3>
<p><strong>Compress all images</strong> using WebP format. Images account for 50-80% of page weight on most websites. Use lazy loading for below-the-fold images.</p>

<h3>Step 3: Enable Browser Caching</h3>
<p><strong>Set cache headers</strong> so returning visitors load assets from their local cache. This can reduce load time by 50% for repeat visits.</p>

<h2>Common Mistakes to Avoid</h2>
<ul>
<li>Installing too many plugins that each add their own CSS/JS files</li>
<li>Using unoptimized hero images (often 3-5MB each)</li>
<li>Forgetting to enable GZIP compression on the server</li>
</ul>

<h2>Summary</h2>
<ol>
<li>Test your current speed and establish a baseline</li>
<li>Optimize and compress all images</li>
<li>Enable browser caching and GZIP compression</li>
</ol>

<h2>Frequently Asked Questions</h2>
<h3>What is a good page speed score?</h3>
<p>Aim for 90+ on mobile and desktop in PageSpeed Insights. Anything above 70 is acceptable.</p>

<p><strong>Start with Step 1 today</strong> — knowing your baseline is the first step to a faster website.</p>',

	'listicle'        => '
<p>Looking for the <strong>best productivity tools</strong> to supercharge your workflow? We\'ve tested dozens of options and narrowed it down to the top picks that actually deliver results.</p>

<h2>Table of Contents</h2>
<ul>
<li><a href="#1">1. Notion</a></li>
<li><a href="#2">2. Todoist</a></li>
<li><a href="#3">3. Slack</a></li>
</ul>

<h2 id="1">1. Notion — Best All-in-One Workspace</h2>
<p><strong>Key benefit: Replaces 5+ tools</strong> — notes, project management, wikis, databases, and docs all in one platform.</p>
<p>Notion\'s flexibility is unmatched. Whether you\'re a solopreneur or managing a team of 50, it adapts to your workflow. The free tier is generous enough for most individuals.</p>

<h2 id="2">2. Todoist — Best for Simple Task Management</h2>
<p><strong>Key benefit: Zero learning curve</strong> — add a task in seconds with natural language input like "Call dentist tomorrow at 3pm".</p>
<p>When you just need a reliable to-do list that stays out of your way, Todoist is the gold standard. The Karma system gamifies productivity.</p>

<h2 id="3">3. Slack — Best for Team Communication</h2>
<p><strong>Key benefit: Reduces email by 48%</strong> — organized channels replace chaotic email threads.</p>
<p>With 750,000+ organizations using it daily, Slack has become the de facto standard for team chat. The integration ecosystem is massive.</p>

<h2>Bonus: Obsidian — Best for Knowledge Management</h2>
<p>If you\'re serious about building a personal knowledge base, Obsidian\'s linked-note approach is a game-changer. It\'s free and works offline.</p>

<h2>Conclusion</h2>
<p>For most people, start with <strong>Notion</strong> for project management and <strong>Todoist</strong> for daily tasks. Add Slack when you\'re working with a team.</p>',

	'beginners-guide' => '
<p>Welcome! If you\'re new to <strong>search engine optimization (SEO)</strong>, you\'re in the right place. This guide will walk you through everything you need to know — in plain language, no jargon.</p>

<h2>What You Will Learn</h2>
<ul>
<li>What SEO actually is and why it matters</li>
<li>How search engines find and rank your content</li>
<li>The 3 core areas of SEO you need to know</li>
<li>Your first 5 action steps to start today</li>
</ul>

<h2>What is SEO?</h2>
<p>SEO stands for Search Engine Optimization. Think of it like this: <strong>Google is a librarian, and SEO is how you make sure your book gets recommended.</strong> When someone searches for something, Google decides which pages to show first. SEO helps your page be one of them.</p>

<p><strong>What does "organic traffic" mean?</strong> It\'s visitors who find your website through search engines (not ads). It\'s free and tends to be higher quality than paid traffic.</p>

<h2>Why SEO Matters</h2>
<p>93% of online experiences start with a search engine. If your website doesn\'t appear on the first page of Google, you\'re invisible to most potential customers.</p>

<h2>How Search Engines Work</h2>
<p>Imagine Google as a spider crawling through the internet. It visits your pages, reads the content, and files it away in a massive index. When someone searches, Google checks its index and shows the most relevant, trustworthy results.</p>

<h2>Getting Started: Your First 5 Steps</h2>
<ol>
<li><strong>Claim your Google Business Profile</strong> (if you have a local business)</li>
<li><strong>Install Yoast SEO</strong> plugin on your WordPress site</li>
<li><strong>Research one keyword</strong> your customers actually search for</li>
<li><strong>Write one helpful blog post</strong> targeting that keyword</li>
<li><strong>Share it</strong> on your social media channels</li>
</ol>

<h2>Common Beginner Mistakes</h2>
<ul>
<li>Trying to rank for broad terms like "shoes" instead of specific ones like "best running shoes for flat feet"</li>
<li>Stuffing keywords unnaturally into every sentence</li>
<li>Ignoring mobile-friendliness (60% of searches are on phones)</li>
</ul>

<p>Don\'t feel overwhelmed. <strong>Everyone starts here.</strong> Focus on creating helpful content for real people, and the rankings will follow.</p>',

);
?>
<div class="wrap rbco-wrap" style="max-width: 1000px;">
	<h1 class="wp-heading-inline">
		<span class="dashicons dashicons-layout rbco-heading-icon"></span>
		<?php esc_html_e( 'Blog Style Examples', 'raybogman-ai-content-orchestrator' ); ?>
	</h1>
	<p class="rbco-subtitle">
		<?php esc_html_e( 'Preview what each blog style produces. Click any style to expand its example. Select your preferred style on the Create Content page.', 'raybogman-ai-content-orchestrator' ); ?>
	</p>

	<?php foreach ( $rbco_styles as $rbco_key => $rbco_style ) : ?>
		<div class="rbco-card" style="margin-bottom: 12px;">
			<div class="rbco-card-header rbco-example-toggle" data-target="rbco-example-<?php echo esc_attr( $rbco_key ); ?>" style="cursor: pointer;">
				<h2 style="justify-content: space-between; width: 100%;">
					<span>
						<span class="dashicons <?php echo esc_attr( $rbco_style['icon'] ); ?>" style="margin-right: 6px;"></span>
						<?php echo esc_html( $rbco_style['name'] ); ?>
						<span class="description" style="font-weight: normal; margin-left: 8px;">
							<?php echo esc_html( $rbco_style['target_words'] ); ?> words &mdash;
							<?php echo esc_html( $rbco_style['description'] ); ?>
						</span>
					</span>
					<span class="dashicons dashicons-arrow-down-alt2" style="color: #646970;"></span>
				</h2>
			</div>
			<div id="rbco-example-<?php echo esc_attr( $rbco_key ); ?>" class="rbco-card-body rbco-example-body" style="display: none;">
				<div class="rbco-preview" style="display: block; max-height: none;">
					<?php
					if ( isset( $rbco_examples[ $rbco_key ] ) ) {
						echo wp_kses_post( $rbco_examples[ $rbco_key ] );
					} else {
						echo '<p class="description"><em>' . esc_html__( 'Example coming soon.', 'raybogman-ai-content-orchestrator' ) . '</em></p>';
					}
					?>
				</div>
			</div>
		</div>
	<?php endforeach; ?>
</div>

<?php
// Inline JS is registered through the proper script API (attached to the
// already-enqueued 'rbco-admin' handle) instead of printing it inline.
// ob_start() and ob_get_clean() are paired inside rbco_capture_inline_script().
rbco_capture_inline_script(
	'rbco-admin',
	function () {
		?>
jQuery(document).ready(function($) {
	$('.rbco-example-toggle').on('click', function() {
		var target = $(this).data('target');
		var $body  = $('#' + target);
		var $arrow = $(this).find('.dashicons-arrow-down-alt2, .dashicons-arrow-up-alt2');

		if ($body.is(':visible')) {
			$body.slideUp(200);
			$arrow.removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');
		} else {
			$body.slideDown(200);
			$arrow.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');
		}
	});
});
		<?php
	}
);
?>
