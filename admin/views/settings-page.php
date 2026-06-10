<?php
/**
 * Settings page template.
 *
 * @package Ray_Bogman_AI_Content_Orchestrator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rbco_has_yoast  = defined( 'WPSEO_VERSION' );
$rbco_provider   = RBCO_Settings::get_ai_provider();
$rbco_claude_set = ! empty( RBCO_Settings::get_anthropic_api_key() );
$rbco_openai_set = ! empty( RBCO_Settings::get_openai_api_key() );
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only tab/param check.
$rbco_active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'general';
?>
<div class="wrap">
	<h1>
		<span class="dashicons dashicons-edit-large" style="font-size: 28px; width: 28px; height: 28px; margin-right: 8px; vertical-align: text-bottom;"></span>
		<?php esc_html_e( 'Ray Bogman AI Content Orchestrator — Settings', 'raybogman-ai-content-orchestrator' ); ?>
	</h1>

	<nav class="nav-tab-wrapper" style="margin-bottom: 20px;">
		<?php foreach ( RBCO_Settings::get_tabs() as $rbco_slug => $rbco_label ) : ?>
			<a href="<?php echo esc_url( add_query_arg( 'tab', $rbco_slug, admin_url( 'admin.php?page=rbco-settings' ) ) ); ?>"
				class="nav-tab <?php echo $rbco_active_tab === $rbco_slug ? 'nav-tab-active' : ''; ?>">
				<?php echo esc_html( $rbco_label ); ?>
			</a>
		<?php endforeach; ?>
	</nav>

	<?php if ( ! in_array( $rbco_active_tab, array( 'faq', 'about' ), true ) ) : ?>
		<?php
		// Read-only display flag set by RBCO_Settings::handle_save() after its own
		// nonce + capability check and an internal wp_safe_redirect().
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$rbco_settings_saved = isset( $_GET['settings-updated'] ) && 'true' === sanitize_text_field( wp_unslash( $_GET['settings-updated'] ) );
		?>
		<?php if ( $rbco_settings_saved ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'raybogman-ai-content-orchestrator' ); ?></p></div>
	<?php endif; ?>
	<form method="post" action="">
		<?php wp_nonce_field( 'rbco_save_settings_' . $rbco_active_tab, 'rbco_settings_nonce' ); ?>
		<input type="hidden" name="rbco_save_tab" value="<?php echo esc_attr( $rbco_active_tab ); ?>" />
		<?php
		do_settings_sections( 'rbco-tab-' . $rbco_active_tab );
		submit_button();
		?>
	</form>
	<?php endif; ?>

	<?php if ( 'faq' === $rbco_active_tab ) : ?>
	<!-- FAQ Tab -->
	<div style="max-width: 900px;">
		<h2><?php esc_html_e( 'Frequently Asked Questions', 'raybogman-ai-content-orchestrator' ); ?></h2>

		<?php
		$rbco_faq_items = array(
			'what-does-it-do'  => __( 'What does this plugin do?', 'raybogman-ai-content-orchestrator' ),
			'ai-providers'     => __( 'Do I need both a Claude and OpenAI account?', 'raybogman-ai-content-orchestrator' ),
			'cost'             => __( 'How much does it cost to generate a blog post?', 'raybogman-ai-content-orchestrator' ),
			'website-scanner'  => __( 'What is the "Website Scanner" and do I need it?', 'raybogman-ai-content-orchestrator' ),
			'internal-linking' => __( 'How does the automatic internal linking work?', 'raybogman-ai-content-orchestrator' ),
			'image-overlay'    => __( 'How does the featured image overlay work?', 'raybogman-ai-content-orchestrator' ),
			'shared-hosting'   => __( 'Does this plugin work on shared hosting?', 'raybogman-ai-content-orchestrator' ),
			'curl-timeout'     => __( 'I get a "cURL error 28: Operation timed out" — what does this mean?', 'raybogman-ai-content-orchestrator' ),
			'save-log'         => __( 'How can I save the progress log for debugging?', 'raybogman-ai-content-orchestrator' ),
		);
		?>
		<div class="rbco-card" style="margin-bottom:20px;">
			<div class="rbco-card-body">
				<h3 style="margin-top:0;"><?php esc_html_e( 'Table of Contents', 'raybogman-ai-content-orchestrator' ); ?></h3>
				<ol style="column-count:2; column-gap:24px; line-height:2;">
					<?php foreach ( $rbco_faq_items as $rbco_id => $rbco_label ) : ?>
						<li><a href="#faq-<?php echo esc_attr( $rbco_id ); ?>" style="text-decoration:none;"><?php echo esc_html( $rbco_label ); ?></a></li>
					<?php endforeach; ?>
				</ol>
			</div>
		</div>

		<div class="rbco-card" style="margin-bottom: 12px;" id="faq-what-does-it-do">
			<div class="rbco-card-body">
				<h3 style="margin-top:0;"><?php esc_html_e( 'What does this plugin do?', 'raybogman-ai-content-orchestrator' ); ?></h3>
				<p><?php esc_html_e( 'Ray Bogman AI Content Orchestrator writes full blog posts for your WordPress website using AI (Claude or OpenAI). You give it a topic, it optionally reads a single page you choose for background information, then writes an SEO-optimized article with headings, paragraphs, lists, and a FAQ section. It also generates a featured image, adds internal links, and fills in your Yoast SEO fields.', 'raybogman-ai-content-orchestrator' ); ?></p>
			</div>
		</div>

		<div class="rbco-card" style="margin-bottom: 12px;" id="faq-ai-providers">
			<div class="rbco-card-body">
				<h3 style="margin-top:0;"><?php esc_html_e( 'Do I need both a Claude and OpenAI account?', 'raybogman-ai-content-orchestrator' ); ?></h3>
				<p><?php esc_html_e( 'No. You only need one AI provider. Pick either Claude (Anthropic) or OpenAI in the General tab and enter that provider\'s API key. You can configure both and switch between them anytime, but only one is used at a time for content generation.', 'raybogman-ai-content-orchestrator' ); ?></p>
			</div>
		</div>

		<div class="rbco-card" style="margin-bottom: 12px;" id="faq-cost">
			<div class="rbco-card-body">
				<h3 style="margin-top:0;"><?php esc_html_e( 'How much does it cost to generate a blog post?', 'raybogman-ai-content-orchestrator' ); ?></h3>
				<p><?php esc_html_e( 'The plugin itself is free to use. You pay for the AI usage through your provider\'s API. A typical blog post costs roughly $0.02–$0.10 with Claude Sonnet or GPT-4o. Featured images add roughly $0.06 per image with gpt-image-1 (medium quality).', 'raybogman-ai-content-orchestrator' ); ?></p>
			</div>
		</div>

		<div class="rbco-card" style="margin-bottom: 12px;" id="faq-website-scanner">
			<div class="rbco-card-body">
				<h3 style="margin-top:0;"><?php esc_html_e( 'What is the "Website Scanner" and do I need it?', 'raybogman-ai-content-orchestrator' ); ?></h3>
				<p><?php esc_html_e( 'The scanner reads the single page you specify and gives that information to the AI as background context. This helps the AI write content that matches your brand, uses the right terminology, and references real information. It\'s optional — you can skip it and just give the AI a prompt instead.', 'raybogman-ai-content-orchestrator' ); ?></p>
			</div>
		</div>

		<div class="rbco-card" style="margin-bottom: 12px;" id="faq-internal-linking">
			<div class="rbco-card-body">
				<h3 style="margin-top:0;"><?php esc_html_e( 'How does the automatic internal linking work?', 'raybogman-ai-content-orchestrator' ); ?></h3>
				<p><?php esc_html_e( 'After the AI writes your article, the plugin scans all your existing published posts for related content. It then adds 3-5 links within the text that point to those related posts. This helps with SEO (Google uses internal links to understand your site structure) and keeps readers on your site longer. You need at least a few published posts for this to work — it won\'t find links if your site is brand new.', 'raybogman-ai-content-orchestrator' ); ?></p>
			</div>
		</div>

		<div class="rbco-card" style="margin-bottom: 12px;" id="faq-image-overlay">
			<div class="rbco-card-body">
				<h3 style="margin-top:0;"><?php esc_html_e( 'How does the featured image overlay work?', 'raybogman-ai-content-orchestrator' ); ?></h3>
				<p><?php esc_html_e( 'You upload a background image (like a branded template) and two font files in the Images tab. When you create a post without AI image generation, the plugin takes your background image and places the blog title on top of it in two lines of uppercase text — creating a unique, branded featured image for every post. You can edit the text after generation if you want to customize it.', 'raybogman-ai-content-orchestrator' ); ?></p>
			</div>
		</div>

		<div class="rbco-card" style="margin-bottom: 12px;" id="faq-shared-hosting">
			<div class="rbco-card-body">
				<h3 style="margin-top:0;"><?php esc_html_e( 'Does this plugin work on shared hosting?', 'raybogman-ai-content-orchestrator' ); ?></h3>
				<p><?php esc_html_e( 'Yes. The content creation pipeline is split into 4 small steps, each completing within typical server timeouts (30-60 seconds). This avoids the "504 Gateway Timeout" errors that other AI plugins suffer from on shared hosting with strict time limits.', 'raybogman-ai-content-orchestrator' ); ?></p>
			</div>
		</div>

		<div class="rbco-card" style="margin-bottom: 12px;" id="faq-curl-timeout">
			<div class="rbco-card-body">
				<h3 style="margin-top:0;"><?php esc_html_e( 'I get a "cURL error 28: Operation timed out" — what does this mean?', 'raybogman-ai-content-orchestrator' ); ?></h3>
				<p><?php esc_html_e( 'This means your server waited too long (120 seconds) for the AI provider to respond and gave up. The AI request was sent but the response didn\'t arrive in time. This is usually caused by:', 'raybogman-ai-content-orchestrator' ); ?></p>
				<ul style="list-style:disc; padding-left:20px;">
					<li><?php esc_html_e( 'Large context — reading a very long page creates a big prompt that takes the AI longer to process', 'raybogman-ai-content-orchestrator' ); ?></li>
					<li><?php esc_html_e( 'Server firewall or proxy throttling outbound HTTPS connections', 'raybogman-ai-content-orchestrator' ); ?></li>
					<li><?php esc_html_e( 'PHP max_execution_time set too low on your hosting (check with your host)', 'raybogman-ai-content-orchestrator' ); ?></li>
					<li><?php esc_html_e( 'AI provider experiencing high load (temporary — try again later)', 'raybogman-ai-content-orchestrator' ); ?></li>
				</ul>
				<p><strong><?php esc_html_e( 'How to fix:', 'raybogman-ai-content-orchestrator' ); ?></strong></p>
				<ol style="padding-left:20px;">
					<li><?php esc_html_e( 'Reduce "Max Context Characters" in Settings → Scanner so a long page sends less text to the AI. Smaller context = faster AI response.', 'raybogman-ai-content-orchestrator' ); ?></li>
					<li><?php esc_html_e( 'Try again — the plugin automatically retries once after a 5-second pause. Temporary API slowdowns often resolve on the second attempt.', 'raybogman-ai-content-orchestrator' ); ?></li>
				</ol>
			</div>
		</div>

		<div class="rbco-card" style="margin-bottom: 12px;" id="faq-save-log">
			<div class="rbco-card-body">
				<h3 style="margin-top:0;"><?php esc_html_e( 'How can I save the progress log for debugging?', 'raybogman-ai-content-orchestrator' ); ?></h3>
				<p><?php esc_html_e( 'The Create Content page has a "Save Log" button in the top-right corner of the progress window. Click it to download a timestamped .txt file with all log lines, the date, and the page URL. This is useful for sharing with support or debugging issues.', 'raybogman-ai-content-orchestrator' ); ?></p>
			</div>
		</div>
	</div>
	<?php endif; ?>

	<?php if ( 'about' === $rbco_active_tab ) : ?>
	<!-- About Tab -->
	<div style="max-width: 900px;">
		<div class="rbco-card" style="margin-bottom: 20px;">
			<div class="rbco-card-body">
				<h2 style="margin-top:0;">
					<span class="dashicons dashicons-edit-large" style="font-size:24px;width:24px;height:24px;margin-right:8px;vertical-align:text-bottom;color:#2271b1;"></span>
					<?php esc_html_e( 'About Ray Bogman AI Content Orchestrator', 'raybogman-ai-content-orchestrator' ); ?>
				</h2>
				<p style="font-size:14px;line-height:1.6;">
					<?php esc_html_e( 'Ray Bogman AI Content Orchestrator is a complete content pipeline for WordPress. It\'s not just another "AI writer" — it orchestrates the entire journey from research to publication.', 'raybogman-ai-content-orchestrator' ); ?>
				</p>
				<p style="font-size:14px;line-height:1.6;">
					<?php esc_html_e( 'Here\'s what it does: you give it a topic and optionally a website to scan for background information. The AI then writes a full, SEO-optimized blog post with proper headings, paragraphs, lists, and a FAQ section. It generates a custom featured image, adds internal links to your existing posts, and publishes everything to WordPress with Yoast SEO fields filled in — all in about 2 minutes.', 'raybogman-ai-content-orchestrator' ); ?>
				</p>
				<p style="font-size:14px;line-height:1.6;">
					<?php esc_html_e( 'The plugin supports two AI providers (Claude by Anthropic and OpenAI\'s GPT models), featured image generation with gpt-image-1, four blog writing styles, automatic internal linking, and an optional branded title overlay for featured images.', 'raybogman-ai-content-orchestrator' ); ?>
				</p>
				<p style="font-size:14px;line-height:1.6;">
					<?php esc_html_e( 'Everything is designed to work on any hosting — including shared hosting with strict timeouts — thanks to a 4-step pipeline that breaks the work into manageable chunks.', 'raybogman-ai-content-orchestrator' ); ?>
				</p>

			</div>
		</div>

		<div class="rbco-card">
			<div class="rbco-card-body">
				<h2 style="margin-top:0;">
					<span class="dashicons dashicons-admin-users" style="font-size:24px;width:24px;height:24px;margin-right:8px;vertical-align:text-bottom;color:#2271b1;"></span>
					<?php esc_html_e( 'About the Author', 'raybogman-ai-content-orchestrator' ); ?>
				</h2>
				<div style="display:flex;gap:20px;align-items:flex-start;flex-wrap:wrap;">
					<div style="flex:1;min-width:300px;">
						<h3 style="margin-top:0;">Ray Bogman</h3>
						<p style="font-size:14px;line-height:1.6;">
							<?php esc_html_e( 'Fractional CTO, AI Innovator, and Head of Innovation at Alumio. Based in Amstelveen, Netherlands.', 'raybogman-ai-content-orchestrator' ); ?>
						</p>
						<p style="font-size:14px;line-height:1.6;">
							<?php esc_html_e( 'Ray combines deep technical expertise with practical AI strategy. With an Oxford AI Programme certificate, AWS AI Practitioner certification, and 20+ years of experience in cloud architecture, digital commerce, and web performance, he builds tools that make AI accessible to everyday business users.', 'raybogman-ai-content-orchestrator' ); ?>
						</p>
						<p style="font-size:14px;line-height:1.6;">
							<?php esc_html_e( 'He was awarded Adobe Global Consultant of the Year 2021, is the author of "Magento 2 Cookbook" (Packt Publishing), and is a certified Ethical Hacker, Scrum Master, and Red Hat Engineer. He speaks at international conferences about AI, cloud architecture, and innovation.', 'raybogman-ai-content-orchestrator' ); ?>
						</p>
						<p style="margin-top:12px;">
							<a href="https://raybogman.com" target="_blank" rel="noopener" class="button">
								<span class="dashicons dashicons-admin-links" style="vertical-align:text-bottom;font-size:16px;width:16px;height:16px;margin-right:4px;"></span>
								raybogman.com
							</a>
							<a href="https://www.linkedin.com/in/raybogman/" target="_blank" rel="noopener" class="button" style="margin-left:6px;">
								<span class="dashicons dashicons-linkedin" style="vertical-align:text-bottom;font-size:16px;width:16px;height:16px;margin-right:4px;"></span>
								LinkedIn
							</a>
							<a href="https://github.com/raybogman" target="_blank" rel="noopener" class="button" style="margin-left:6px;">
								<span class="dashicons dashicons-github" style="vertical-align:text-bottom;font-size:16px;width:16px;height:16px;margin-right:4px;"></span>
								GitHub
							</a>
						</p>

						<h4 style="margin-top:20px;"><?php esc_html_e( 'Certifications', 'raybogman-ai-content-orchestrator' ); ?></h4>
						<ul style="margin-left:16px;line-height:1.8;">
							<li><?php esc_html_e( 'Oxford Artificial Intelligence Programme', 'raybogman-ai-content-orchestrator' ); ?></li>
							<li><?php esc_html_e( 'AWS Certified AI Practitioner', 'raybogman-ai-content-orchestrator' ); ?></li>
							<li><?php esc_html_e( 'AWS Certified Cloud Practitioner', 'raybogman-ai-content-orchestrator' ); ?></li>
							<li><?php esc_html_e( 'CTO Academy Certified Fractional CTO', 'raybogman-ai-content-orchestrator' ); ?></li>
							<li><?php esc_html_e( 'Certified Ethical Hacker', 'raybogman-ai-content-orchestrator' ); ?></li>
							<li><?php esc_html_e( 'Professional Scrum Master I', 'raybogman-ai-content-orchestrator' ); ?></li>
							<li><?php esc_html_e( 'Red Hat Certified Engineer', 'raybogman-ai-content-orchestrator' ); ?></li>
						</ul>
					</div>
				</div>
			</div>
		</div>

		<div class="rbco-card" style="margin-top:20px;">
			<div class="rbco-card-body" style="text-align:center;padding:20px;">
				<p style="color:#50575e;font-size:13px;margin:0;">
					Ray Bogman AI Content Orchestrator v<?php echo esc_html( RBCO_VERSION ); ?> &middot;
					<a href="https://raybogman.com" target="_blank" rel="noopener">&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> Ray Bogman</a>
				</p>
			</div>
		</div>
	</div>
	<?php endif; ?>

	<?php if ( 'general' === $rbco_active_tab ) : ?>
	<!-- API Key Validation -->
	<div class="rbco-card" style="max-width: 700px; margin-top: 20px;">
		<div class="rbco-card-header">
			<h2><?php esc_html_e( 'Test Your Connections', 'raybogman-ai-content-orchestrator' ); ?></h2>
		</div>
		<div class="rbco-card-body">
			<p class="description" style="margin-bottom: 16px;">
				<?php esc_html_e( 'Check that your API keys are working correctly. Make sure to save your settings first.', 'raybogman-ai-content-orchestrator' ); ?>
			</p>
			<table class="widefat striped">
				<tbody>
					<tr>
						<td style="width: 200px; font-weight: 600;"><?php esc_html_e( 'Claude (Anthropic)', 'raybogman-ai-content-orchestrator' ); ?></td>
						<td>
							<button type="button" class="button rbco-validate-btn" data-provider="claude" <?php echo $rbco_claude_set ? '' : 'disabled'; ?>>
								<?php esc_html_e( 'Test Claude Connection', 'raybogman-ai-content-orchestrator' ); ?>
							</button>
							<?php if ( ! $rbco_claude_set ) : ?>
								<span class="description" style="margin-left: 8px;"><?php esc_html_e( 'No key saved yet.', 'raybogman-ai-content-orchestrator' ); ?></span>
							<?php endif; ?>
							<span class="rbco-validate-result" data-provider="claude" style="margin-left: 12px;"></span>
						</td>
					</tr>
					<tr>
						<td style="font-weight: 600;"><?php esc_html_e( 'OpenAI (GPT)', 'raybogman-ai-content-orchestrator' ); ?></td>
						<td>
							<button type="button" class="button rbco-validate-btn" data-provider="openai" <?php echo $rbco_openai_set ? '' : 'disabled'; ?>>
								<?php esc_html_e( 'Test OpenAI Connection', 'raybogman-ai-content-orchestrator' ); ?>
							</button>
							<?php if ( ! $rbco_openai_set ) : ?>
								<span class="description" style="margin-left: 8px;"><?php esc_html_e( 'No key saved yet.', 'raybogman-ai-content-orchestrator' ); ?></span>
							<?php endif; ?>
							<span class="rbco-validate-result" data-provider="openai" style="margin-left: 12px;"></span>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
	<?php endif; ?>

	<?php if ( 'general' === $rbco_active_tab ) : ?>
	<!-- Status overview -->
	<div class="rbco-card" style="max-width: 700px; margin-top: 20px;">
		<div class="rbco-card-header">
			<h2><?php esc_html_e( 'Status', 'raybogman-ai-content-orchestrator' ); ?></h2>
		</div>
		<div class="rbco-card-body">
			<table class="widefat striped">
				<tbody>
					<tr>
						<td style="width: 200px; font-weight: 600;"><?php esc_html_e( 'Active Provider', 'raybogman-ai-content-orchestrator' ); ?></td>
						<td>
							<?php echo esc_html( 'openai' === $rbco_provider ? 'OpenAI (GPT)' : 'Claude (Anthropic)' ); ?>
							<?php if ( RBCO_Settings::is_configured() ) : ?>
								<span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span>
							<?php else : ?>
								<span class="dashicons dashicons-warning" style="color: #d63638;"></span>
								<em><?php esc_html_e( 'API key missing', 'raybogman-ai-content-orchestrator' ); ?></em>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<td style="font-weight: 600;"><?php esc_html_e( 'Claude API Key', 'raybogman-ai-content-orchestrator' ); ?></td>
						<td>
							<?php if ( $rbco_claude_set ) : ?>
								<span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span> <?php esc_html_e( 'Configured', 'raybogman-ai-content-orchestrator' ); ?>
							<?php else : ?>
								<span class="dashicons dashicons-minus" style="color: #646970;"></span> <?php esc_html_e( 'Not set', 'raybogman-ai-content-orchestrator' ); ?>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<td style="font-weight: 600;"><?php esc_html_e( 'OpenAI API Key', 'raybogman-ai-content-orchestrator' ); ?></td>
						<td>
							<?php if ( $rbco_openai_set ) : ?>
								<span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span> <?php esc_html_e( 'Configured', 'raybogman-ai-content-orchestrator' ); ?>
							<?php else : ?>
								<span class="dashicons dashicons-minus" style="color: #646970;"></span> <?php esc_html_e( 'Not set', 'raybogman-ai-content-orchestrator' ); ?>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<td style="font-weight: 600;"><?php esc_html_e( 'Image Provider', 'raybogman-ai-content-orchestrator' ); ?></td>
						<td>
							<?php echo esc_html( 'OpenAI (gpt-image-1)' ); ?>
							<?php if ( RBCO_Settings::is_image_configured() ) : ?>
								<span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span>
							<?php else : ?>
								<span class="dashicons dashicons-warning" style="color: #d63638;"></span>
								<em><?php esc_html_e( 'API key missing', 'raybogman-ai-content-orchestrator' ); ?></em>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<td style="font-weight: 600;"><?php esc_html_e( 'Yoast SEO', 'raybogman-ai-content-orchestrator' ); ?></td>
						<td>
							<?php if ( $rbco_has_yoast ) : ?>
								<span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span>
								<?php
								printf(
									/* translators: %s: Yoast version */
									esc_html__( 'Detected (v%s) — SEO title, meta description, and focus keyphrase will be set automatically.', 'raybogman-ai-content-orchestrator' ),
									esc_html( WPSEO_VERSION )
								);
								?>
							<?php else : ?>
								<span class="dashicons dashicons-info-outline" style="color: #dba617;"></span>
								<?php esc_html_e( 'Not detected. Install Yoast SEO for automatic SEO field population.', 'raybogman-ai-content-orchestrator' ); ?>
							<?php endif; ?>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>

	<!-- Getting started -->
	<div class="rbco-card" style="max-width: 700px; margin-top: 20px;">
		<div class="rbco-card-header">
			<h2><?php esc_html_e( 'Getting Started', 'raybogman-ai-content-orchestrator' ); ?></h2>
		</div>
		<div class="rbco-card-body">
			<ol style="margin-left: 20px;">
				<li>
					<?php esc_html_e( 'Choose your AI provider above (Claude or OpenAI).', 'raybogman-ai-content-orchestrator' ); ?>
				</li>
				<li>
					<strong><?php esc_html_e( 'Claude:', 'raybogman-ai-content-orchestrator' ); ?></strong>
					<?php esc_html_e( 'Sign up at console.anthropic.com to get your API key', 'raybogman-ai-content-orchestrator' ); ?><br>
					<strong><?php esc_html_e( 'OpenAI:', 'raybogman-ai-content-orchestrator' ); ?></strong>
					<?php esc_html_e( 'Sign up at platform.openai.com to get your API key', 'raybogman-ai-content-orchestrator' ); ?>
				</li>
				<li><?php esc_html_e( 'Paste your API key in the field above and click Save Changes.', 'raybogman-ai-content-orchestrator' ); ?></li>
				<li><?php esc_html_e( 'Click the Test buttons above to make sure everything is connected.', 'raybogman-ai-content-orchestrator' ); ?></li>
				<li>
					<?php
					printf(
						/* translators: %1$s: opening link, %2$s: closing link */
						esc_html__( 'Go to %1$sCreate Content%2$s to generate your first AI-powered post or page.', 'raybogman-ai-content-orchestrator' ),
						'<a href="' . esc_url( admin_url( 'admin.php?page=rbco-create' ) ) . '">',
						'</a>'
					);
					?>
				</li>
			</ol>
		</div>
	</div>
	<?php endif; ?>
</div>

<?php
// Inline JS registered via the proper script API instead of printing it inline.
// ob_start() and ob_get_clean() are paired inside rbco_capture_inline_script().
rbco_capture_inline_script(
	'rbco-admin',
	function () {
		?>
jQuery(document).ready(function($) {
	$('.rbco-validate-btn').on('click', function() {
		var $btn    = $(this);
		var provider = $btn.data('provider');
		var $result = $('.rbco-validate-result[data-provider="' + provider + '"]');

		$btn.prop('disabled', true).text('Validating...');
		$result.html('<span class="spinner is-active" style="float:none; margin:0;"></span>');

		$.ajax({
			url:      '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
			type:     'POST',
			dataType: 'json',
			data: {
				action:   'rbco_validate_api_key',
				nonce:    '<?php echo esc_js( wp_create_nonce( 'rbco_nonce' ) ); ?>',
				provider: provider,
			},
			success: function(response) {
				if (response.success) {
					$result.html(
						'<span class="dashicons dashicons-yes-alt" style="color:#00a32a; vertical-align:text-bottom;"></span> ' +
						'<strong style="color:#00a32a;">' + response.data.message + '</strong>'
					);
				} else {
					var msg = (response.data && response.data.message) ? response.data.message : 'Validation failed.';
					$result.html(
						'<span class="dashicons dashicons-dismiss" style="color:#d63638; vertical-align:text-bottom;"></span> ' +
						'<strong style="color:#d63638;">' + msg + '</strong>'
					);
				}
				var labels = { claude: 'Test Claude Connection', openai: 'Test OpenAI Connection' };
				$btn.prop('disabled', false).text(labels[provider] || 'Test');
			},
			error: function(xhr, textStatus, errorThrown) {
				$result.html(
					'<span class="dashicons dashicons-dismiss" style="color:#d63638; vertical-align:text-bottom;"></span> ' +
					'<strong style="color:#d63638;">Request failed: ' + (errorThrown || textStatus) + '</strong>'
				);
				var labels = { claude: 'Test Claude Connection', openai: 'Test OpenAI Connection' };
				$btn.prop('disabled', false).text(labels[provider] || 'Test');
			},
			timeout: 30000,
		});
	});

	// ── Brand Colors — clickable swatches ─────────────────────
	function updateBrandColorInput() {
		var selected = [];
		$('#rbco-brand-swatches .rbco-color-selected').each(function() {
			selected.push($(this).data('color'));
		});
		$('#rbco_brand_colors').val(selected.join(', '));
		$('#rbco-color-count').text(selected.length + '/4 <?php echo esc_js( __( 'selected', 'raybogman-ai-content-orchestrator' ) ); ?>');
	}

	function buildSwatch(color, isSelected) {
		var border = isSelected ? '3px solid #2271b1' : '2px solid #ccc';
		var check  = isSelected ? '<span style="position:absolute;bottom:-2px;right:-2px;background:#2271b1;color:#fff;width:12px;height:12px;border-radius:50%;font-size:9px;line-height:12px;text-align:center;">&#10003;</span>' : '';
		var cls    = isSelected ? 'rbco-color-swatch rbco-color-selected' : 'rbco-color-swatch';
		return '<span class="' + cls + '" data-color="' + color + '" style="display:inline-block;width:28px;height:28px;background:' + color + ';border:' + border + ';border-radius:4px;cursor:pointer;position:relative;" title="' + color + '">' + check + '</span>';
	}

	$(document).on('click', '.rbco-color-swatch', function() {
		var $swatch = $(this);
		if ($swatch.hasClass('rbco-color-selected')) {
			$swatch.removeClass('rbco-color-selected').css('border', '2px solid #ccc').find('span').remove();
		} else {
			var count = $('#rbco-brand-swatches .rbco-color-selected').length;
			if (count >= 4) {
				alert('<?php echo esc_js( __( 'Maximum 4 colors. Deselect one first.', 'raybogman-ai-content-orchestrator' ) ); ?>');
				return;
			}
			$swatch.addClass('rbco-color-selected').css('border', '3px solid #2271b1');
			$swatch.append('<span style="position:absolute;bottom:-2px;right:-2px;background:#2271b1;color:#fff;width:12px;height:12px;border-radius:50%;font-size:9px;line-height:12px;text-align:center;">&#10003;</span>');
		}
		updateBrandColorInput();
	});

	// ── Scan Theme Colors button ──────────────────────────────
	$('#rbco-scan-theme-colors').on('click', function() {
		var $btn    = $(this);
		var $result = $('#rbco-scan-colors-result');

		$btn.prop('disabled', true).html('<span class="spinner is-active" style="float:none; margin:0 4px 0 0; vertical-align:text-bottom;"></span> <?php echo esc_js( __( 'Scanning...', 'raybogman-ai-content-orchestrator' ) ); ?>');
		$result.empty();

		$.ajax({
			url:      '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
			type:     'POST',
			dataType: 'json',
			data: {
				action: 'rbco_scan_theme_colors',
				nonce:  '<?php echo esc_js( wp_create_nonce( 'rbco_nonce' ) ); ?>',
			},
			success: function(response) {
				if (response.success && response.data.colors) {
					var colors = response.data.colors;
					var $swatches = $('#rbco-brand-swatches');
					$swatches.empty();

					// Show all scanned colors as clickable swatches, pre-select first 4.
					for (var i = 0; i < colors.length; i++) {
						$swatches.append(buildSwatch(colors[i], i < 4));
					}
					updateBrandColorInput();

					$result.html(
						'<span class="dashicons dashicons-yes-alt" style="color:#00a32a; vertical-align:text-bottom;"></span> ' +
						'<strong>' + response.data.message + '</strong> ' +
						'<?php echo esc_js( __( 'First 4 colors pre-selected. Click to change. Save to persist.', 'raybogman-ai-content-orchestrator' ) ); ?>'
					);
				} else {
					var msg = (response.data && response.data.message) ? response.data.message : '<?php echo esc_js( __( 'No colors found.', 'raybogman-ai-content-orchestrator' ) ); ?>';
					$result.html('<span class="dashicons dashicons-info-outline" style="color:#dba617; vertical-align:text-bottom;"></span> ' + msg);
				}
				$btn.prop('disabled', false).html('<span class="dashicons dashicons-art" style="vertical-align:text-bottom; font-size:16px; width:16px; height:16px; margin-right:4px;"></span> <?php echo esc_js( __( 'Scan Theme Colors', 'raybogman-ai-content-orchestrator' ) ); ?>');
			},
			error: function(xhr, textStatus, errorThrown) {
				$result.html('<span class="dashicons dashicons-dismiss" style="color:#d63638; vertical-align:text-bottom;"></span> <strong style="color:#d63638;">Request failed: ' + (errorThrown || textStatus) + '</strong>');
				$btn.prop('disabled', false).html('<span class="dashicons dashicons-art" style="vertical-align:text-bottom; font-size:16px; width:16px; height:16px; margin-right:4px;"></span> <?php echo esc_js( __( 'Scan Theme Colors', 'raybogman-ai-content-orchestrator' ) ); ?>');
			},
			timeout: 30000,
		});
	});
});
		<?php
	}
);
?>
