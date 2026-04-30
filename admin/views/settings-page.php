<?php
/**
 * Settings page template.
 *
 * @package AI_Content_Creator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$has_yoast          = defined( 'WPSEO_VERSION' );
$provider           = AICC_Settings::get_ai_provider();
$image_provider     = AICC_Settings::get_image_provider();
$claude_set         = ! empty( AICC_Settings::get_anthropic_api_key() );
$openai_set         = ! empty( AICC_Settings::get_openai_api_key() );
$ideogram_set       = ! empty( AICC_Settings::get_ideogram_api_key() );
$linkedin_connected = AICC_LinkedIn::is_connected();
$linkedin_profile   = AICC_LinkedIn::get_profile();
$linkedin_client_id = get_option( 'aicc_linkedin_client_id', '' );
$active_tab         = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'general';
?>
<div class="wrap">
	<h1>
		<span class="dashicons dashicons-edit-large" style="font-size: 28px; width: 28px; height: 28px; margin-right: 8px; vertical-align: text-bottom;"></span>
		<?php esc_html_e( 'AI Content Orchestrator — Settings', 'ai-content-orchestrator' ); ?>
	</h1>

	<nav class="nav-tab-wrapper" style="margin-bottom: 20px;">
		<?php foreach ( AICC_Settings::get_tabs() as $slug => $label ) : ?>
			<a href="<?php echo esc_url( add_query_arg( 'tab', $slug, admin_url( 'admin.php?page=aicc-settings' ) ) ); ?>"
			   class="nav-tab <?php echo $active_tab === $slug ? 'nav-tab-active' : ''; ?>">
				<?php echo esc_html( $label ); ?>
			</a>
		<?php endforeach; ?>
	</nav>

	<?php if ( ! in_array( $active_tab, array( 'faq', 'about' ), true ) ) : ?>
	<?php if ( isset( $_GET['settings-updated'] ) && 'true' === $_GET['settings-updated'] ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'ai-content-orchestrator' ); ?></p></div>
	<?php endif; ?>
	<form method="post" action="">
		<?php wp_nonce_field( 'aicc_save_settings_' . $active_tab, 'aicc_settings_nonce' ); ?>
		<input type="hidden" name="aicc_save_tab" value="<?php echo esc_attr( $active_tab ); ?>" />
		<?php
		do_settings_sections( 'aicc-tab-' . $active_tab );
		submit_button();
		?>
	</form>
	<?php endif; ?>

	<?php if ( 'instagram' === $active_tab ) : ?>
	<!-- Instagram Tab -->
	<div style="max-width: 900px;">
		<?php
		$ig_connected = AICC_Instagram::is_connected();
		$ig_profile   = AICC_Instagram::get_profile();
		$ig_app_id    = AICC_Instagram::get_client_id();
		?>

		<?php if ( $ig_connected && ! empty( $ig_profile ) ) : ?>
		<div class="aicc-card" style="margin-bottom:16px;">
			<div class="aicc-card-header"><h2><span class="dashicons dashicons-camera" style="margin-right:6px; color:#E4405F;"></span><?php esc_html_e( 'Connected Account', 'ai-content-orchestrator' ); ?></h2></div>
			<div class="aicc-card-body">
				<table class="widefat striped"><tbody>
					<tr><td style="width:180px;"><strong><?php esc_html_e( 'Status', 'ai-content-orchestrator' ); ?></strong></td><td><span class="dashicons dashicons-yes-alt" style="color:#00a32a;"></span> <strong style="color:#00a32a;"><?php esc_html_e( 'Connected', 'ai-content-orchestrator' ); ?></strong></td></tr>
					<tr><td><strong><?php esc_html_e( 'Username', 'ai-content-orchestrator' ); ?></strong></td><td>@<?php echo esc_html( $ig_profile['username'] ?? '' ); ?></td></tr>
					<?php if ( ! empty( $ig_profile['name'] ) ) : ?>
					<tr><td><strong><?php esc_html_e( 'Name', 'ai-content-orchestrator' ); ?></strong></td><td><?php echo esc_html( $ig_profile['name'] ); ?></td></tr>
					<?php endif; ?>
					<tr><td><strong><?php esc_html_e( 'Meta App ID', 'ai-content-orchestrator' ); ?></strong></td><td><?php echo esc_html( $ig_app_id ); ?></td></tr>
					<tr id="aicc-ig-test-row" style="display:none;"><td><strong><?php esc_html_e( 'Test Results', 'ai-content-orchestrator' ); ?></strong></td><td id="aicc-ig-test-results"></td></tr>
				</tbody></table>
				<p style="margin-top:12px;">
					<button type="button" class="button button-primary" id="aicc-test-instagram-btn" style="margin-right:8px;">
						<span class="dashicons dashicons-yes" style="vertical-align:text-bottom; font-size:16px; width:16px; height:16px; margin-right:4px;"></span>
						<?php esc_html_e( 'Test Connection', 'ai-content-orchestrator' ); ?>
					</button>
					<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=aicc-settings&tab=instagram&aicc_instagram_disconnect=1' ), 'aicc_instagram_disconnect', 'aicc_instagram_disconnect' ) ); ?>" class="button" onclick="return confirm('<?php echo esc_js( __( 'Disconnect Instagram?', 'ai-content-orchestrator' ) ); ?>');">
						<span class="dashicons dashicons-dismiss" style="vertical-align:text-bottom; color:#d63638; margin-right:4px;"></span><?php esc_html_e( 'Disconnect', 'ai-content-orchestrator' ); ?>
					</a>
				</p>
			</div>
		</div>
		<script>
		jQuery(document).ready(function($) {
			$('#aicc-test-instagram-btn').on('click', function() {
				var $btn = $(this);
				$btn.prop('disabled', true).html('<span class="spinner is-active" style="float:none; margin:0;"></span> <?php echo esc_js( __( 'Testing...', 'ai-content-orchestrator' ) ); ?>');
				$.post('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
					action: 'aicc_test_instagram',
					nonce: '<?php echo esc_js( wp_create_nonce( 'aicc_nonce' ) ); ?>'
				}).done(function(res) {
					var $results = $('#aicc-ig-test-results');
					$('#aicc-ig-test-row').show();
					if (res.success) {
						var d = res.data;
						var html = '<div style="line-height:2;">';
						html += '<span class="dashicons dashicons-yes-alt" style="color:#00a32a; vertical-align:text-bottom;"></span> <strong style="color:#00a32a;"><?php echo esc_js( __( 'All tests passed', 'ai-content-orchestrator' ) ); ?></strong><br>';
						if (d.app) html += '<strong><?php echo esc_js( __( 'Meta App:', 'ai-content-orchestrator' ) ); ?></strong> ' + d.app.name + ' (ID: ' + d.app.id + ')<br>';
						if (d.account) html += '<strong><?php echo esc_js( __( 'Account:', 'ai-content-orchestrator' ) ); ?></strong> @' + d.account.username + ' — ' + d.account.followers + ' <?php echo esc_js( __( 'followers', 'ai-content-orchestrator' ) ); ?>, ' + d.account.posts + ' <?php echo esc_js( __( 'posts', 'ai-content-orchestrator' ) ); ?><br>';
						if (d.token) html += '<strong><?php echo esc_js( __( 'Token expires:', 'ai-content-orchestrator' ) ); ?></strong> ' + d.token.expires_at + ' (' + d.token.days_left + ' <?php echo esc_js( __( 'days left', 'ai-content-orchestrator' ) ); ?>)<br>';
						if (d.publishing) html += '<strong><?php echo esc_js( __( 'Publish quota:', 'ai-content-orchestrator' ) ); ?></strong> ' + d.publishing.quota_usage + '/25 <?php echo esc_js( __( 'used today', 'ai-content-orchestrator' ) ); ?>';
						html += '</div>';
						$results.html(html);
					} else {
						$results.html('<span class="dashicons dashicons-warning" style="color:#d63638; vertical-align:text-bottom;"></span> <strong style="color:#d63638;">' + (res.data.message || 'Test failed') + '</strong>');
					}
				}).fail(function() {
					$('#aicc-ig-test-row').show();
					$('#aicc-ig-test-results').html('<span style="color:#d63638;"><?php echo esc_js( __( 'Request failed.', 'ai-content-orchestrator' ) ); ?></span>');
				}).always(function() {
					$btn.prop('disabled', false).html('<span class="dashicons dashicons-yes" style="vertical-align:text-bottom; font-size:16px; width:16px; height:16px; margin-right:4px;"></span> <?php echo esc_js( __( 'Test Connection', 'ai-content-orchestrator' ) ); ?>');
				});
			});
		});
		</script>
		<?php endif; ?>

		<?php if ( isset( $_GET['aicc_instagram_error'] ) ) : ?>
			<div class="notice notice-error"><p><strong><?php esc_html_e( 'Error:', 'ai-content-orchestrator' ); ?></strong> <?php echo esc_html( sanitize_text_field( wp_unslash( $_GET['aicc_instagram_error'] ) ) ); ?></p></div>
		<?php elseif ( isset( $_GET['aicc_instagram_success'] ) ) : ?>
			<div class="notice notice-success"><p><?php esc_html_e( 'Instagram connected successfully!', 'ai-content-orchestrator' ); ?></p></div>
		<?php elseif ( isset( $_GET['aicc_instagram_disconnected'] ) ) : ?>
			<div class="notice notice-info"><p><?php esc_html_e( 'Instagram disconnected.', 'ai-content-orchestrator' ); ?></p></div>
		<?php endif; ?>

		<div class="aicc-card" style="margin-bottom:16px;">
			<div class="aicc-card-header"><h2><?php esc_html_e( 'Setup Guide', 'ai-content-orchestrator' ); ?></h2></div>
			<div class="aicc-card-body">
				<h3 style="margin-top:0;"><?php esc_html_e( 'Prerequisites', 'ai-content-orchestrator' ); ?></h3>
			<ul style="list-style:disc; padding-left:20px; line-height:2;">
				<li><?php esc_html_e( 'An Instagram Business or Creator account (not personal). To switch: Instagram app > Settings > Account > Switch to Professional Account.', 'ai-content-orchestrator' ); ?></li>
				<li><?php esc_html_e( 'A Facebook Page connected to that Instagram account. To link: Facebook Page > Settings > Linked Accounts > Instagram.', 'ai-content-orchestrator' ); ?></li>
			</ul>
			<p style="display:none;"><?php esc_html_e( 'To connect Instagram, you need:', 'ai-content-orchestrator' ); ?></p>
				<ul style="list-style:disc; padding-left:20px; line-height:2;">
					<li><?php esc_html_e( 'An Instagram Business or Creator account (not personal)', 'ai-content-orchestrator' ); ?></li>
					<li><?php esc_html_e( 'A Facebook Page connected to that Instagram account', 'ai-content-orchestrator' ); ?></li>
					<li><?php esc_html_e( 'A Meta App at developers.facebook.com', 'ai-content-orchestrator' ); ?></li>
				</ul>
				<h3><?php esc_html_e( 'Step 1: Create a Meta App', 'ai-content-orchestrator' ); ?></h3>
			<ol style="line-height:2.2;">
				<li><?php esc_html_e( 'Go to', 'ai-content-orchestrator' ); ?> <a href="https://developers.facebook.com/apps/" target="_blank">developers.facebook.com/apps</a></li>
				<li><?php esc_html_e( 'Click "Create App" > "Other" > "Business" type > name it (e.g., "AI Content Orchestrator")', 'ai-content-orchestrator' ); ?></li>
			</ol>
			<h3><?php esc_html_e( 'Step 2: Add products', 'ai-content-orchestrator' ); ?></h3>
			<ol style="line-height:2.2;">
				<li><?php esc_html_e( 'In the App dashboard, scroll to "Add products" > click "Set up" on "Instagram Graph API"', 'ai-content-orchestrator' ); ?></li>
				<li><?php esc_html_e( 'Also "Set up" on "Facebook Login for Business" (needed for OAuth)', 'ai-content-orchestrator' ); ?></li>
			</ol>
			<h3><?php esc_html_e( 'Steps 3-5:', 'ai-content-orchestrator' ); ?></h3>
				<ol style="line-height:2;">
					<li><?php esc_html_e( 'Go to developers.facebook.com and create a new App (type: "Business")', 'ai-content-orchestrator' ); ?></li>
					<li><?php esc_html_e( 'Add the "Instagram Graph API" product to your App', 'ai-content-orchestrator' ); ?></li>
					<li><?php esc_html_e( 'Go to App Settings > Basic — copy the App ID and App Secret', 'ai-content-orchestrator' ); ?></li>
					<li><?php esc_html_e( 'In the left sidebar under Products, expand "Facebook Login for Business" > click "Settings" > paste this URL in "Valid OAuth Redirect URIs". If you don\'t see Facebook Login, click "Add Product" first and add it. Alternative: you can also paste the URL in App Settings > Advanced > "Authorize callback URL".', 'ai-content-orchestrator' ); ?>
						<br><code style="display:inline-block; background:#f0f0f1; padding:6px 12px; border-radius:3px; margin:4px 0; font-size:13px; user-select:all;"><?php echo esc_html( admin_url( 'admin.php?page=aicc-settings&tab=instagram' ) ); ?></code>
					<button type="button" class="button button-small" style="margin-left:4px; vertical-align:middle;" onclick="navigator.clipboard.writeText('<?php echo esc_js( admin_url( 'admin.php?page=aicc-settings&tab=instagram' ) ); ?>'); this.textContent='Copied!'; var b=this; setTimeout(function(){b.textContent='Copy URL';}, 2000);">Copy URL</button>
					</li>
					<li><?php esc_html_e( 'Paste App ID and App Secret in the fields below, click "Save Changes", then click "Connect Instagram"', 'ai-content-orchestrator' ); ?></li>
				</ol>
				<div style="margin-top:12px; padding:12px 16px; background:#fff8e5; border-left:3px solid #dba617; border-radius:3px;">
					<strong><?php esc_html_e( 'Important:', 'ai-content-orchestrator' ); ?></strong>
					<?php esc_html_e( 'Your Meta App must be in "Live" mode for publishing to work. In the App dashboard top bar, toggle from "In development" to "Live". You may need to complete App Review for the instagram_content_publish permission.', 'ai-content-orchestrator' ); ?>
				</div>
			</div>
		</div>

		<?php if ( ! $ig_connected && ! empty( $ig_app_id ) ) : ?>
		<p style="margin-bottom:16px;">
			<a href="<?php echo esc_url( AICC_Instagram::get_auth_url() ); ?>" class="button button-primary button-hero">
				<span class="dashicons dashicons-camera" style="vertical-align:text-bottom; font-size:20px; width:20px; height:20px; margin-right:6px;"></span>
				<?php esc_html_e( 'Connect Instagram', 'ai-content-orchestrator' ); ?>
			</a>
		</p>
		<?php elseif ( ! $ig_connected ) : ?>
		<div class="notice notice-warning inline"><p><?php esc_html_e( 'Enter your Meta App ID and App Secret above, then Save Changes to enable the Connect button.', 'ai-content-orchestrator' ); ?></p></div>
		<?php endif; ?>
	</div>
	<?php endif; ?>

	<?php if ( 'faq' === $active_tab ) : ?>
	<!-- FAQ Tab -->
	<div style="max-width: 900px;">
		<h2><?php esc_html_e( 'Frequently Asked Questions', 'ai-content-orchestrator' ); ?></h2>

		<?php
		$faq_items = array(
			'what-does-it-do'      => __( 'What does this plugin do?', 'ai-content-orchestrator' ),
			'ai-providers'         => __( 'Do I need both a Claude and OpenAI account?', 'ai-content-orchestrator' ),
			'cost'                 => __( 'How much does it cost to generate a blog post?', 'ai-content-orchestrator' ),
			'website-scanner'      => __( 'What is the "Website Scanner" and do I need it?', 'ai-content-orchestrator' ),
			'internal-linking'     => __( 'How does the automatic internal linking work?', 'ai-content-orchestrator' ),
			'competitor-analysis'  => __( 'What does "Analyze competitors first" do?', 'ai-content-orchestrator' ),
			'thrive-broken'        => __( 'Thrive Architect: my post looks broken on first preview', 'ai-content-orchestrator' ),
			'thrive-toc'           => __( 'Thrive Architect: why does my Table of Contents look different after saving?', 'ai-content-orchestrator' ),
			'without-thrive'       => __( 'Can I use this plugin without Thrive Architect?', 'ai-content-orchestrator' ),
			'image-overlay'        => __( 'How does the featured image overlay work?', 'ai-content-orchestrator' ),
			'shared-hosting'       => __( 'Does this plugin work on shared hosting?', 'ai-content-orchestrator' ),
			'scheduling'           => __( 'Can I schedule posts for later?', 'ai-content-orchestrator' ),
			'repurposing'          => __( 'What is the "Content Repurposing" feature?', 'ai-content-orchestrator' ),
			'curl-timeout'         => __( 'I get a "cURL error 28: Operation timed out" — what does this mean?', 'ai-content-orchestrator' ),
			'scan-pages'           => __( 'How many pages should I scan for best results?', 'ai-content-orchestrator' ),
			'ideogram-colors'      => __( 'Why did my Ideogram images fail with "color_palette" error?', 'ai-content-orchestrator' ),
			'bulk-create'          => __( 'How does Bulk Create work?', 'ai-content-orchestrator' ),
			'refresh-content'      => __( 'How does "Refresh Content" work?', 'ai-content-orchestrator' ),
			'publish-schedule'     => __( 'How does the publishing schedule work?', 'ai-content-orchestrator' ),
			'navigate-away'        => __( 'What happens if I navigate away during Bulk Create?', 'ai-content-orchestrator' ),
			'link-placement'       => __( 'How does internal link placement work?', 'ai-content-orchestrator' ),
			'save-log'             => __( 'How can I save the progress log for debugging?', 'ai-content-orchestrator' ),
			'free-vs-enterprise'   => __( 'What is the difference between Free and Enterprise?', 'ai-content-orchestrator' ),
		);
		?>
		<div class="aicc-card" style="margin-bottom:20px;">
			<div class="aicc-card-body">
				<h3 style="margin-top:0;"><?php esc_html_e( 'Table of Contents', 'ai-content-orchestrator' ); ?></h3>
				<ol style="column-count:2; column-gap:24px; line-height:2;">
					<?php foreach ( $faq_items as $id => $label ) : ?>
						<li><a href="#faq-<?php echo esc_attr( $id ); ?>" style="text-decoration:none;"><?php echo esc_html( $label ); ?></a></li>
					<?php endforeach; ?>
				</ol>
			</div>
		</div>

		<div class="aicc-card" style="margin-bottom: 12px;" id="faq-what-does-it-do">
			<div class="aicc-card-body">
				<h3 style="margin-top:0;"><?php esc_html_e( 'What does this plugin do?', 'ai-content-orchestrator' ); ?></h3>
				<p><?php esc_html_e( 'AI Content Orchestrator writes full blog posts for your WordPress website using AI (Claude or OpenAI). You give it a topic, it scans a website for background information, then writes an SEO-optimized article with headings, paragraphs, lists, and a FAQ section. It also generates a featured image, shares to LinkedIn, and integrates with Thrive Architect.', 'ai-content-orchestrator' ); ?></p>
			</div>
		</div>

		<div class="aicc-card" style="margin-bottom: 12px;" id="faq-ai-providers">
			<div class="aicc-card-body">
				<h3 style="margin-top:0;"><?php esc_html_e( 'Do I need both a Claude and OpenAI account?', 'ai-content-orchestrator' ); ?></h3>
				<p><?php esc_html_e( 'No. You only need one AI provider. Pick either Claude (Anthropic) or OpenAI in the General tab and enter that provider\'s API key. You can configure both and switch between them anytime, but only one is used at a time for content generation.', 'ai-content-orchestrator' ); ?></p>
			</div>
		</div>

		<div class="aicc-card" style="margin-bottom: 12px;" id="faq-cost">
			<div class="aicc-card-body">
				<h3 style="margin-top:0;"><?php esc_html_e( 'How much does it cost to generate a blog post?', 'ai-content-orchestrator' ); ?></h3>
				<p><?php esc_html_e( 'The plugin itself is free to use. You pay for the AI usage through your provider\'s API. A typical blog post costs roughly $0.02–$0.10 with Claude Sonnet or GPT-4o. Featured images add about $0.04 per image (DALL-E 3) or vary with Ideogram. LinkedIn sharing is free.', 'ai-content-orchestrator' ); ?></p>
			</div>
		</div>

		<div class="aicc-card" style="margin-bottom: 12px;" id="faq-website-scanner">
			<div class="aicc-card-body">
				<h3 style="margin-top:0;"><?php esc_html_e( 'What is the "Website Scanner" and do I need it?', 'ai-content-orchestrator' ); ?></h3>
				<p><?php esc_html_e( 'The scanner reads pages from a website you specify and gives that information to the AI as background context. This helps the AI write content that matches your brand, uses the right terminology, and references real information. It\'s optional — you can skip it and just give the AI a prompt instead.', 'ai-content-orchestrator' ); ?></p>
			</div>
		</div>

		<div class="aicc-card" style="margin-bottom: 12px;" id="faq-internal-linking">
			<div class="aicc-card-body">
				<h3 style="margin-top:0;"><?php esc_html_e( 'How does the automatic internal linking work?', 'ai-content-orchestrator' ); ?></h3>
				<p><?php esc_html_e( 'After the AI writes your article, the plugin scans all your existing published posts for related content. It then adds 3-5 links within the text that point to those related posts. This helps with SEO (Google uses internal links to understand your site structure) and keeps readers on your site longer. You need at least a few published posts for this to work — it won\'t find links if your site is brand new.', 'ai-content-orchestrator' ); ?></p>
			</div>
		</div>

		<div class="aicc-card" style="margin-bottom: 12px;" id="faq-competitor-analysis">
			<div class="aicc-card-body">
				<h3 style="margin-top:0;"><?php esc_html_e( 'What does "Analyze competitors first" do?', 'ai-content-orchestrator' ); ?></h3>
				<p><?php esc_html_e( 'When enabled, the AI analyzes what topics and questions the top-ranking articles typically cover for your keyword. It then identifies gaps — things competitors miss — and includes those in your article. This helps your content be more comprehensive than what\'s already ranking, giving you a better chance at higher search positions.', 'ai-content-orchestrator' ); ?></p>
			</div>
		</div>

		<div class="aicc-card" style="margin-bottom: 12px;" id="faq-thrive-broken">
			<div class="aicc-card-body">
				<h3 style="margin-top:0;">
					<?php esc_html_e( 'Thrive Architect: my post looks broken on first preview', 'ai-content-orchestrator' ); ?>
					<span style="background:#dba617;color:#fff;padding:2px 8px;border-radius:10px;font-size:11px;margin-left:8px;vertical-align:middle;">BETA</span>
				</h3>
				<p><?php esc_html_e( 'This is a known issue with the Thrive Architect integration (currently in Beta). When you generate a new post in Thrive mode, the first time you preview it the page may look broken — you might see raw HTML, misaligned elements, or unstyled text.', 'ai-content-orchestrator' ); ?></p>
				<p><strong><?php esc_html_e( 'Workaround:', 'ai-content-orchestrator' ); ?></strong></p>
				<ol>
					<li><?php esc_html_e( 'After creating the post, open it in Thrive Architect (click "Edit with Thrive Architect" on the post).', 'ai-content-orchestrator' ); ?></li>
					<li><?php esc_html_e( 'Don\'t change anything — just click the Save button (green button, bottom left).', 'ai-content-orchestrator' ); ?></li>
					<li><?php esc_html_e( 'Close Thrive Architect and go back to WordPress.', 'ai-content-orchestrator' ); ?></li>
					<li><?php esc_html_e( 'Now preview the post — it will look correct.', 'ai-content-orchestrator' ); ?></li>
				</ol>
				<p><?php esc_html_e( 'This "open and save" step is needed because Thrive Architect processes the content and applies its internal styling on the first save. After that initial save, the post works perfectly — previews, front-end display, and further editing all work as expected.', 'ai-content-orchestrator' ); ?></p>
			</div>
		</div>

		<div class="aicc-card" style="margin-bottom: 12px;" id="faq-thrive-toc">
			<div class="aicc-card-body">
				<h3 style="margin-top:0;"><?php esc_html_e( 'Thrive Architect: why does my Table of Contents look different after saving?', 'ai-content-orchestrator' ); ?></h3>
				<p><?php esc_html_e( 'Thrive\'s Table of Contents widget is dynamic — it automatically rebuilds its entries from the actual headings in your post every time you open and save in Thrive Architect. The first time you save, Thrive replaces the initial entries with ones it generates from your article\'s H2 and H3 headings. This is normal behavior and the final result is correct.', 'ai-content-orchestrator' ); ?></p>
			</div>
		</div>

		<div class="aicc-card" style="margin-bottom: 12px;" id="faq-without-thrive">
			<div class="aicc-card-body">
				<h3 style="margin-top:0;"><?php esc_html_e( 'Can I use this plugin without Thrive Architect?', 'ai-content-orchestrator' ); ?></h3>
				<p><?php esc_html_e( 'Yes! Thrive Architect is completely optional. The default output format is "WordPress (Standard)" which works with any theme and page builder. The Thrive integration is only for users who specifically use Thrive Architect as their content editor.', 'ai-content-orchestrator' ); ?></p>
			</div>
		</div>

		<div class="aicc-card" style="margin-bottom: 12px;" id="faq-image-overlay">
			<div class="aicc-card-body">
				<h3 style="margin-top:0;"><?php esc_html_e( 'How does the featured image overlay work?', 'ai-content-orchestrator' ); ?></h3>
				<p><?php esc_html_e( 'You upload a background image (like a branded template) and two font files in the Images tab. When you create a post without AI image generation, the plugin takes your background image and places the blog title on top of it in two lines of uppercase text — creating a unique, branded featured image for every post. You can edit the text after generation if you want to customize it.', 'ai-content-orchestrator' ); ?></p>
			</div>
		</div>

		<div class="aicc-card" style="margin-bottom: 12px;" id="faq-shared-hosting">
			<div class="aicc-card-body">
				<h3 style="margin-top:0;"><?php esc_html_e( 'Does this plugin work on shared hosting?', 'ai-content-orchestrator' ); ?></h3>
				<p><?php esc_html_e( 'Yes. The content creation pipeline is split into 4 small steps, each completing within typical server timeouts (30-60 seconds). This avoids the "504 Gateway Timeout" errors that other AI plugins suffer from on shared hosting with strict time limits.', 'ai-content-orchestrator' ); ?></p>
			</div>
		</div>

		<div class="aicc-card" style="margin-bottom: 12px;" id="faq-scheduling">
			<div class="aicc-card-body">
				<h3 style="margin-top:0;"><?php esc_html_e( 'Can I schedule posts for later?', 'ai-content-orchestrator' ); ?></h3>
				<p><?php esc_html_e( 'Yes. Choose a date and time on the Create Content page. If you select "Draft + Schedule", the post goes to a review queue where you approve it before it\'s scheduled. If you select "Publish + Schedule", WordPress schedules it directly. There\'s also a Bulk Create page where you can queue multiple posts with different dates.', 'ai-content-orchestrator' ); ?></p>
			</div>
		</div>

		<div class="aicc-card" style="margin-bottom: 12px;" id="faq-repurposing">
			<div class="aicc-card-body">
				<h3 style="margin-top:0;"><?php esc_html_e( 'What is the "Content Repurposing" feature?', 'ai-content-orchestrator' ); ?></h3>
				<p><?php esc_html_e( 'After creating a blog post, you can click buttons to instantly generate versions of your content for other platforms: Email Newsletter, X/Twitter thread, Instagram caption, and Pinterest pin description. Each version is optimized for that platform\'s format and best practices. Copy to clipboard with one click.', 'ai-content-orchestrator' ); ?></p>
			</div>
		</div>

		<div class="aicc-card" style="margin-bottom: 12px;" id="faq-curl-timeout">
			<div class="aicc-card-body">
				<h3 style="margin-top:0;"><?php esc_html_e( 'I get a "cURL error 28: Operation timed out" — what does this mean?', 'ai-content-orchestrator' ); ?></h3>
				<p><?php esc_html_e( 'This means your server waited too long (120 seconds) for the AI provider to respond and gave up. The AI request was sent but the response didn\'t arrive in time. This is usually caused by:', 'ai-content-orchestrator' ); ?></p>
				<ul style="list-style:disc; padding-left:20px;">
					<li><?php esc_html_e( 'Large context — scanning many pages creates a big prompt that takes the AI longer to process', 'ai-content-orchestrator' ); ?></li>
					<li><?php esc_html_e( 'Server firewall or proxy throttling outbound HTTPS connections', 'ai-content-orchestrator' ); ?></li>
					<li><?php esc_html_e( 'PHP max_execution_time set too low on your hosting (check with your host)', 'ai-content-orchestrator' ); ?></li>
					<li><?php esc_html_e( 'AI provider experiencing high load (temporary — try again later)', 'ai-content-orchestrator' ); ?></li>
				</ul>
				<p><strong><?php esc_html_e( 'How to fix:', 'ai-content-orchestrator' ); ?></strong></p>
				<ol style="padding-left:20px;">
					<li><?php esc_html_e( 'Reduce "Max Pages to Scan" in Settings → Scanner from 25 to 10-15. Fewer pages = smaller context = faster AI response.', 'ai-content-orchestrator' ); ?></li>
					<li><?php esc_html_e( 'Disable "Analyze competitors first" to skip the extra AI call that adds processing time.', 'ai-content-orchestrator' ); ?></li>
					<li><?php esc_html_e( 'Try again — the plugin automatically retries once after a 5-second pause. Temporary API slowdowns often resolve on the second attempt.', 'ai-content-orchestrator' ); ?></li>
					<li><?php esc_html_e( 'If using Bulk Create, failed posts can be retried individually via Create Content.', 'ai-content-orchestrator' ); ?></li>
				</ol>
			</div>
		</div>

		<div class="aicc-card" style="margin-bottom: 12px;" id="faq-scan-pages">
			<div class="aicc-card-body">
				<h3 style="margin-top:0;"><?php esc_html_e( 'How many pages should I scan for best results?', 'ai-content-orchestrator' ); ?></h3>
				<p><?php esc_html_e( 'For most websites, 8-15 pages provides a good balance between context quality and speed. Scanning too many pages (25+) creates a very large prompt that:', 'ai-content-orchestrator' ); ?></p>
				<ul style="list-style:disc; padding-left:20px;">
					<li><?php esc_html_e( 'Takes longer for the AI to process (increasing timeout risk)', 'ai-content-orchestrator' ); ?></li>
					<li><?php esc_html_e( 'Uses more API tokens (higher cost per post)', 'ai-content-orchestrator' ); ?></li>
					<li><?php esc_html_e( 'May dilute the focus — the AI has more information but less clarity on what matters', 'ai-content-orchestrator' ); ?></li>
				</ul>
				<p><?php esc_html_e( 'The scanner automatically picks the most relevant pages based on your prompt, so even 10 pages usually captures the key information. You can adjust this in Settings → Scanner → "Max Pages to Scan".', 'ai-content-orchestrator' ); ?></p>
			</div>
		</div>

		<div class="aicc-card" style="margin-bottom: 12px;" id="faq-ideogram-colors">
			<div class="aicc-card-body">
				<h3 style="margin-top:0;"><?php esc_html_e( 'Why did my Ideogram images fail with "color_palette" error?', 'ai-content-orchestrator' ); ?></h3>
				<p><?php esc_html_e( 'Ideogram\'s API accepts a maximum of 4 brand colors. If your theme has more colors configured (from the "Scan Theme Colors" button), only the first 4 selected colors are sent to Ideogram. Go to Settings → Images → Brand Colors and make sure you have 4 or fewer colors selected. Click colors to select/deselect them.', 'ai-content-orchestrator' ); ?></p>
			</div>
		</div>

		<div class="aicc-card" style="margin-bottom: 12px;" id="faq-bulk-create">
			<div class="aicc-card-body">
				<h3 style="margin-top:0;"><?php esc_html_e( 'How does Bulk Create work?', 'ai-content-orchestrator' ); ?></h3>
				<p><?php esc_html_e( 'Bulk Create lets you generate multiple blog posts at once. Enter a seed keyword and click "Suggest Topics" — the AI generates topic ideas and recommends the best blog style for each. You can add rows, set publish dates (manually or with the auto-fill schedule), and click "Generate All" to process them one by one. Each post goes through the same 4-step pipeline as single Create Content. Results appear live in a table with Details, View, and Edit buttons.', 'ai-content-orchestrator' ); ?></p>
			</div>
		</div>

		<div class="aicc-card" style="margin-bottom: 12px;" id="faq-refresh-content">
			<div class="aicc-card-body">
				<h3 style="margin-top:0;"><?php esc_html_e( 'How does "Refresh Content" work?', 'ai-content-orchestrator' ); ?></h3>
				<p><?php esc_html_e( 'The Refresh Content page has two tabs:', 'ai-content-orchestrator' ); ?></p>
				<ul style="list-style:disc; padding-left:20px;">
					<li><?php esc_html_e( 'Content Health Overview — click "Analyze All Posts" to scan every published post for issues (thin content, missing FAQ, few internal links, outdated). Click the filter cards to narrow down by issue type. Use "Fix" per post or "Fix Selected" / "Fix All Filtered" to refresh multiple posts with AI.', 'ai-content-orchestrator' ); ?></li>
					<li><?php esc_html_e( 'Refresh Individual Post — select a specific post, analyze it, choose which issues to fix, and let the AI rewrite and improve the content while keeping your URL and SEO value.', 'ai-content-orchestrator' ); ?></li>
				</ul>
			</div>
		</div>

		<div class="aicc-card" style="margin-bottom: 12px;" id="faq-publish-schedule">
			<div class="aicc-card-body">
				<h3 style="margin-top:0;"><?php esc_html_e( 'How does the publishing schedule work?', 'ai-content-orchestrator' ); ?></h3>
				<p><?php esc_html_e( 'In Settings → Content, you can set a default publishing frequency (daily, every 2-3 days, weekly, bi-weekly, or monthly), a default publish time, and whether to skip weekends. On the Bulk Create page, click "Fill Dates" to auto-fill all checked rows with dates based on this schedule. You can still override individual dates. When posts are published, you\'ll receive email notifications if configured in the "Publish Notification" setting.', 'ai-content-orchestrator' ); ?></p>
			</div>
		</div>

		<div class="aicc-card" style="margin-bottom: 12px;" id="faq-navigate-away">
			<div class="aicc-card-body">
				<h3 style="margin-top:0;"><?php esc_html_e( 'What happens if I navigate away during Bulk Create?', 'ai-content-orchestrator' ); ?></h3>
				<p><?php esc_html_e( 'The plugin shows a warning before you leave: "Bulk generation is still running. If you leave, remaining posts won\'t be created." Posts already completed are saved, but the remaining queue is lost. There is no background processing — all generation runs in your browser session.', 'ai-content-orchestrator' ); ?></p>
			</div>
		</div>

		<div class="aicc-card" style="margin-bottom: 12px;" id="faq-link-placement">
			<div class="aicc-card-body">
				<h3 style="margin-top:0;"><?php esc_html_e( 'How does internal link placement work?', 'ai-content-orchestrator' ); ?></h3>
				<p><?php esc_html_e( 'In Settings → Content → Link Placement, you can choose:', 'ai-content-orchestrator' ); ?></p>
				<ul style="list-style:disc; padding-left:20px;">
					<li><?php esc_html_e( 'Inline only — links are placed naturally within paragraphs using relevant anchor text (best for SEO)', 'ai-content-orchestrator' ); ?></li>
					<li><?php esc_html_e( 'Related Articles only — a styled "Related Articles" section at the bottom of the post (best for readers)', 'ai-content-orchestrator' ); ?></li>
					<li><?php esc_html_e( 'Both (recommended) — the plugin uses an adaptive algorithm: short posts get fewer inline links with more in the footer, longer posts get more inline. Max 1 link per 4 paragraphs to avoid spam. Footer always gets at least 2 links.', 'ai-content-orchestrator' ); ?></li>
				</ul>
			</div>
		</div>

		<div class="aicc-card" style="margin-bottom: 12px;" id="faq-save-log">
			<div class="aicc-card-body">
				<h3 style="margin-top:0;"><?php esc_html_e( 'How can I save the progress log for debugging?', 'ai-content-orchestrator' ); ?></h3>
				<p><?php esc_html_e( 'Both the Create Content and Bulk Create pages have a "Save Log" button in the top-right corner of the progress window. Click it to download a timestamped .txt file with all log lines, the date, and the page URL. This is useful for sharing with support or debugging issues.', 'ai-content-orchestrator' ); ?></p>
			</div>
		</div>
		<div class="aicc-card" style="margin-bottom: 12px;" id="faq-free-vs-enterprise">
			<div class="aicc-card-body">
				<h3 style="margin-top:0;"><?php esc_html_e( 'What is the difference between Free and Enterprise?', 'ai-content-orchestrator' ); ?></h3>
				<p><?php esc_html_e( 'The free version includes single content creation with 4 blog styles, DALL-E 3 images, basic internal linking (inline, max 3), content repurposing, scheduling, and Yoast SEO integration — everything you need to create great content.', 'ai-content-orchestrator' ); ?></p>
				<p><?php esc_html_e( 'Enterprise unlocks power features for teams and agencies: Bulk Create (batch posts with AI topic suggestions), Refresh Content (analyze and fix all posts), all 13 blog styles, Ideogram images, competitor gap analysis, LinkedIn and Instagram auto-sharing, Thrive Architect output, PDF sources, adaptive internal linking (max 15), multiple URL scanning, auto-fill publish dates, and email notifications.', 'ai-content-orchestrator' ); ?></p>
				<p><?php esc_html_e( 'See the full comparison table in the About tab.', 'ai-content-orchestrator' ); ?></p>
			</div>
		</div>
	</div>
	<?php endif; ?>

	<?php if ( 'about' === $active_tab ) : ?>
	<!-- About Tab -->
	<div style="max-width: 900px;">
		<div class="aicc-card" style="margin-bottom: 20px;">
			<div class="aicc-card-body">
				<h2 style="margin-top:0;">
					<span class="dashicons dashicons-edit-large" style="font-size:24px;width:24px;height:24px;margin-right:8px;vertical-align:text-bottom;color:#2271b1;"></span>
					<?php esc_html_e( 'About AI Content Orchestrator', 'ai-content-orchestrator' ); ?>
				</h2>
				<p style="font-size:14px;line-height:1.6;">
					<?php esc_html_e( 'AI Content Orchestrator is a complete content pipeline for WordPress. It\'s not just another "AI writer" — it orchestrates the entire journey from research to publication.', 'ai-content-orchestrator' ); ?>
				</p>
				<p style="font-size:14px;line-height:1.6;">
					<?php esc_html_e( 'Here\'s what it does: you give it a topic and optionally a website to scan for background information. The AI then writes a full, SEO-optimized blog post with proper headings, paragraphs, lists, and a FAQ section. It generates a custom featured image, adds internal links to your existing posts, creates a LinkedIn summary, and publishes everything to WordPress with Yoast SEO fields filled in — all in about 2 minutes.', 'ai-content-orchestrator' ); ?>
				</p>
				<p style="font-size:14px;line-height:1.6;">
					<?php esc_html_e( 'The plugin supports two AI providers (Claude by Anthropic and OpenAI\'s GPT models), two image generators (DALL-E 3 and Ideogram), 12 different blog writing styles, LinkedIn auto-sharing, Thrive Architect integration, scheduling with a review queue, and content repurposing for Email, Twitter, Instagram, and Pinterest.', 'ai-content-orchestrator' ); ?>
				</p>
				<p style="font-size:14px;line-height:1.6;">
					<?php esc_html_e( 'Everything is designed to work on any hosting — including shared hosting with strict timeouts — thanks to a 4-step pipeline that breaks the work into manageable chunks.', 'ai-content-orchestrator' ); ?>
				</p>

				<h3><?php esc_html_e( 'Free vs Enterprise', 'ai-content-orchestrator' ); ?></h3>
				<?php
				$check = '<span class="dashicons dashicons-yes-alt" style="color:#00a32a; vertical-align:text-bottom;"></span>';
				$cross = '<span class="dashicons dashicons-minus" style="color:#c3c4c7; vertical-align:text-bottom;"></span>';
				$ent   = '<span style="background:#E4405F;color:#fff;padding:1px 6px;border-radius:8px;font-size:10px;font-weight:600;">ENT</span>';
				?>
				<table class="widefat striped" style="max-width:700px;">
					<thead>
						<tr>
							<th style="width:50%;"><?php esc_html_e( 'Feature', 'ai-content-orchestrator' ); ?></th>
							<th style="width:25%; text-align:center;"><?php esc_html_e( 'Free', 'ai-content-orchestrator' ); ?></th>
							<th style="width:25%; text-align:center;"><?php echo esc_html__( 'Enterprise', 'ai-content-orchestrator' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr><td><?php esc_html_e( 'AI Content Creation', 'ai-content-orchestrator' ); ?></td><td style="text-align:center;"><?php echo $check; ?></td><td style="text-align:center;"><?php echo $check; ?></td></tr>
						<tr><td><?php esc_html_e( 'Blog Styles', 'ai-content-orchestrator' ); ?></td><td style="text-align:center;">4</td><td style="text-align:center;"><?php esc_html_e( 'All 13', 'ai-content-orchestrator' ); ?></td></tr>
						<tr><td><?php esc_html_e( 'SEO Metadata + Yoast', 'ai-content-orchestrator' ); ?></td><td style="text-align:center;"><?php echo $check; ?></td><td style="text-align:center;"><?php echo $check; ?></td></tr>
						<tr><td><?php esc_html_e( 'Website Scanning', 'ai-content-orchestrator' ); ?></td><td style="text-align:center;"><?php esc_html_e( '1 URL', 'ai-content-orchestrator' ); ?></td><td style="text-align:center;"><?php esc_html_e( 'Unlimited', 'ai-content-orchestrator' ); ?></td></tr>
						<tr><td><?php esc_html_e( 'Featured Images (DALL-E 3)', 'ai-content-orchestrator' ); ?></td><td style="text-align:center;"><?php echo $check; ?></td><td style="text-align:center;"><?php echo $check; ?></td></tr>
						<tr><td><?php esc_html_e( 'Featured Images (Ideogram)', 'ai-content-orchestrator' ); ?></td><td style="text-align:center;"><?php echo $cross; ?></td><td style="text-align:center;"><?php echo $check; ?></td></tr>
						<tr><td><?php esc_html_e( 'Image Title Overlay', 'ai-content-orchestrator' ); ?></td><td style="text-align:center;"><?php echo $check; ?></td><td style="text-align:center;"><?php echo $check; ?></td></tr>
						<tr><td><?php esc_html_e( 'Internal Linking', 'ai-content-orchestrator' ); ?></td><td style="text-align:center;"><?php esc_html_e( 'Inline, max 3', 'ai-content-orchestrator' ); ?></td><td style="text-align:center;"><?php esc_html_e( 'Inline + Footer, max 15', 'ai-content-orchestrator' ); ?></td></tr>
						<tr><td><?php esc_html_e( 'Competitor Gap Analysis', 'ai-content-orchestrator' ); ?></td><td style="text-align:center;"><?php echo $cross; ?></td><td style="text-align:center;"><?php echo $check; ?></td></tr>
						<tr><td><?php esc_html_e( 'Content Repurposing', 'ai-content-orchestrator' ); ?></td><td style="text-align:center;"><?php echo $check; ?></td><td style="text-align:center;"><?php echo $check; ?></td></tr>
						<tr><td><?php esc_html_e( 'Bulk Create', 'ai-content-orchestrator' ); ?></td><td style="text-align:center;"><?php echo $cross; ?></td><td style="text-align:center;"><?php echo $check; ?></td></tr>
						<tr><td><?php esc_html_e( 'Refresh Content (Analyze + Fix)', 'ai-content-orchestrator' ); ?></td><td style="text-align:center;"><?php echo $cross; ?></td><td style="text-align:center;"><?php echo $check; ?></td></tr>
						<tr><td><?php esc_html_e( 'PDF Sources', 'ai-content-orchestrator' ); ?></td><td style="text-align:center;"><?php echo $cross; ?></td><td style="text-align:center;"><?php echo $check; ?></td></tr>
						<tr><td><?php esc_html_e( 'LinkedIn Auto-Share', 'ai-content-orchestrator' ); ?></td><td style="text-align:center;"><?php echo $cross; ?></td><td style="text-align:center;"><?php echo $check; ?></td></tr>
						<tr><td><?php esc_html_e( 'Instagram Auto-Share', 'ai-content-orchestrator' ); ?></td><td style="text-align:center;"><?php echo $cross; ?></td><td style="text-align:center;"><?php echo $check; ?></td></tr>
						<tr><td><?php esc_html_e( 'Thrive Architect Output', 'ai-content-orchestrator' ); ?></td><td style="text-align:center;"><?php echo $cross; ?></td><td style="text-align:center;"><?php esc_html_e( 'Beta', 'ai-content-orchestrator' ); ?></td></tr>
						<tr><td><?php esc_html_e( 'Publishing Schedule (Auto-fill)', 'ai-content-orchestrator' ); ?></td><td style="text-align:center;"><?php echo $cross; ?></td><td style="text-align:center;"><?php echo $check; ?></td></tr>
						<tr><td><?php esc_html_e( 'Publish Notifications (Email)', 'ai-content-orchestrator' ); ?></td><td style="text-align:center;"><?php echo $cross; ?></td><td style="text-align:center;"><?php echo $check; ?></td></tr>
						<tr><td><?php esc_html_e( 'Scheduling + Review Queue', 'ai-content-orchestrator' ); ?></td><td style="text-align:center;"><?php echo $check; ?></td><td style="text-align:center;"><?php echo $check; ?></td></tr>
						<tr><td><?php esc_html_e( 'Dashboard + Progress Log', 'ai-content-orchestrator' ); ?></td><td style="text-align:center;"><?php echo $check; ?></td><td style="text-align:center;"><?php echo $check; ?></td></tr>
					</tbody>
				</table>
				<?php if ( ! aicc_is_pro() ) : ?>
				<p style="margin-top:16px; text-align:center;">
					<a href="<?php echo esc_url( aco_fs()->get_upgrade_url() ); ?>" class="button button-primary button-hero" style="background:#E4405F; border-color:#E4405F;">
						<span class="dashicons dashicons-star-filled" style="vertical-align:text-bottom; font-size:20px; width:20px; height:20px; margin-right:6px;"></span>
						<?php esc_html_e( 'Upgrade to Enterprise', 'ai-content-orchestrator' ); ?>
					</a>
				</p>
				<?php endif; ?>
			</div>
		</div>

		<div class="aicc-card">
			<div class="aicc-card-body">
				<h2 style="margin-top:0;">
					<span class="dashicons dashicons-admin-users" style="font-size:24px;width:24px;height:24px;margin-right:8px;vertical-align:text-bottom;color:#2271b1;"></span>
					<?php esc_html_e( 'About the Author', 'ai-content-orchestrator' ); ?>
				</h2>
				<div style="display:flex;gap:20px;align-items:flex-start;flex-wrap:wrap;">
					<div style="flex:1;min-width:300px;">
						<h3 style="margin-top:0;">Ray Bogman</h3>
						<p style="font-size:14px;line-height:1.6;">
							<?php esc_html_e( 'Fractional CTO, AI Innovator, and Head of Innovation at Alumio. Based in Amstelveen, Netherlands.', 'ai-content-orchestrator' ); ?>
						</p>
						<p style="font-size:14px;line-height:1.6;">
							<?php esc_html_e( 'Ray combines deep technical expertise with practical AI strategy. With an Oxford AI Programme certificate, AWS AI Practitioner certification, and 20+ years of experience in cloud architecture, digital commerce, and web performance, he builds tools that make AI accessible to everyday business users.', 'ai-content-orchestrator' ); ?>
						</p>
						<p style="font-size:14px;line-height:1.6;">
							<?php esc_html_e( 'He was awarded Adobe Global Consultant of the Year 2021, is the author of "Magento 2 Cookbook" (Packt Publishing), and is a certified Ethical Hacker, Scrum Master, and Red Hat Engineer. He speaks at international conferences about AI, cloud architecture, and innovation.', 'ai-content-orchestrator' ); ?>
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

						<h4 style="margin-top:20px;"><?php esc_html_e( 'Certifications', 'ai-content-orchestrator' ); ?></h4>
						<ul style="margin-left:16px;line-height:1.8;">
							<li><?php esc_html_e( 'Oxford Artificial Intelligence Programme', 'ai-content-orchestrator' ); ?></li>
							<li><?php esc_html_e( 'AWS Certified AI Practitioner', 'ai-content-orchestrator' ); ?></li>
							<li><?php esc_html_e( 'AWS Certified Cloud Practitioner', 'ai-content-orchestrator' ); ?></li>
							<li><?php esc_html_e( 'CTO Academy Certified Fractional CTO', 'ai-content-orchestrator' ); ?></li>
							<li><?php esc_html_e( 'Certified Ethical Hacker', 'ai-content-orchestrator' ); ?></li>
							<li><?php esc_html_e( 'Professional Scrum Master I', 'ai-content-orchestrator' ); ?></li>
							<li><?php esc_html_e( 'Red Hat Certified Engineer', 'ai-content-orchestrator' ); ?></li>
						</ul>
					</div>
				</div>
			</div>
		</div>

		<div class="aicc-card" style="margin-top:20px;">
			<div class="aicc-card-body" style="text-align:center;padding:20px;">
				<p style="color:#50575e;font-size:13px;margin:0;">
					AI Content Orchestrator v<?php echo esc_html( AICC_VERSION ); ?> &middot;
					<a href="https://raybogman.com" target="_blank" rel="noopener">&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> Ray Bogman</a>
				</p>
			</div>
		</div>
	</div>
	<?php endif; ?>

	<?php if ( 'general' === $active_tab ) : ?>
	<!-- API Key Validation -->
	<div class="aicc-card" style="max-width: 700px; margin-top: 20px;">
		<div class="aicc-card-header">
			<h2><?php esc_html_e( 'Test Your Connections', 'ai-content-orchestrator' ); ?></h2>
		</div>
		<div class="aicc-card-body">
			<p class="description" style="margin-bottom: 16px;">
				<?php esc_html_e( 'Check that your API keys are working correctly. Make sure to save your settings first.', 'ai-content-orchestrator' ); ?>
			</p>
			<table class="widefat striped">
				<tbody>
					<tr>
						<td style="width: 200px; font-weight: 600;"><?php esc_html_e( 'Claude (Anthropic)', 'ai-content-orchestrator' ); ?></td>
						<td>
							<button type="button" class="button aicc-validate-btn" data-provider="claude" <?php echo $claude_set ? '' : 'disabled'; ?>>
								<?php esc_html_e( 'Test Claude Connection', 'ai-content-orchestrator' ); ?>
							</button>
							<?php if ( ! $claude_set ) : ?>
								<span class="description" style="margin-left: 8px;"><?php esc_html_e( 'No key saved yet.', 'ai-content-orchestrator' ); ?></span>
							<?php endif; ?>
							<span class="aicc-validate-result" data-provider="claude" style="margin-left: 12px;"></span>
						</td>
					</tr>
					<tr>
						<td style="font-weight: 600;"><?php esc_html_e( 'OpenAI (GPT)', 'ai-content-orchestrator' ); ?></td>
						<td>
							<button type="button" class="button aicc-validate-btn" data-provider="openai" <?php echo $openai_set ? '' : 'disabled'; ?>>
								<?php esc_html_e( 'Test OpenAI Connection', 'ai-content-orchestrator' ); ?>
							</button>
							<?php if ( ! $openai_set ) : ?>
								<span class="description" style="margin-left: 8px;"><?php esc_html_e( 'No key saved yet.', 'ai-content-orchestrator' ); ?></span>
							<?php endif; ?>
							<span class="aicc-validate-result" data-provider="openai" style="margin-left: 12px;"></span>
						</td>
					</tr>
					<tr>
						<td style="font-weight: 600;"><?php esc_html_e( 'Ideogram', 'ai-content-orchestrator' ); ?></td>
						<td>
							<button type="button" class="button aicc-validate-btn" data-provider="ideogram" <?php echo $ideogram_set ? '' : 'disabled'; ?>>
								<?php esc_html_e( 'Test Ideogram Connection', 'ai-content-orchestrator' ); ?>
							</button>
							<?php if ( ! $ideogram_set ) : ?>
								<span class="description" style="margin-left: 8px;"><?php esc_html_e( 'No key saved yet.', 'ai-content-orchestrator' ); ?></span>
							<?php endif; ?>
							<span class="aicc-validate-result" data-provider="ideogram" style="margin-left: 12px;"></span>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>
	<?php endif; ?>

	<?php if ( 'linkedin' === $active_tab ) : ?>
	<!-- LinkedIn Connection -->
	<div class="aicc-card" style="max-width: 700px; margin-top: 20px;">
		<div class="aicc-card-header">
			<h2><?php esc_html_e( 'LinkedIn Connection', 'ai-content-orchestrator' ); ?></h2>
		</div>
		<div class="aicc-card-body">
			<?php if ( $linkedin_connected && ! empty( $linkedin_profile['name'] ) ) : ?>
				<table class="widefat striped">
					<tbody>
						<tr>
							<td style="width: 200px; font-weight: 600;"><?php esc_html_e( 'Status', 'ai-content-orchestrator' ); ?></td>
							<td>
								<span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span>
								<?php esc_html_e( 'Connected', 'ai-content-orchestrator' ); ?>
							</td>
						</tr>
						<tr>
							<td style="font-weight: 600;"><?php esc_html_e( 'Account', 'ai-content-orchestrator' ); ?></td>
							<td>
								<?php if ( ! empty( $linkedin_profile['picture'] ) ) : ?>
									<img src="<?php echo esc_url( $linkedin_profile['picture'] ); ?>" alt="" style="width: 24px; height: 24px; border-radius: 50%; vertical-align: middle; margin-right: 6px;" />
								<?php endif; ?>
								<strong><?php echo esc_html( $linkedin_profile['name'] ); ?></strong>
								<?php if ( ! empty( $linkedin_profile['email'] ) ) : ?>
									<span class="description">(<?php echo esc_html( $linkedin_profile['email'] ); ?>)</span>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<td style="font-weight: 600;"><?php esc_html_e( 'Granted Scopes', 'ai-content-orchestrator' ); ?></td>
							<td>
								<?php $granted = get_option( 'aicc_linkedin_scopes', '' ); ?>
								<?php if ( ! empty( $granted ) ) : ?>
									<code style="font-size: 12px;"><?php echo esc_html( $granted ); ?></code>
									<?php if ( false === strpos( $granted, 'w_member_social' ) ) : ?>
										<br><span class="dashicons dashicons-warning" style="color: #d63638;"></span>
										<strong style="color: #d63638;"><?php esc_html_e( 'w_member_social is MISSING — you cannot post to LinkedIn!', 'ai-content-orchestrator' ); ?></strong>
										<br><em class="description"><?php esc_html_e( 'Disconnect and reconnect to request the correct scopes. Make sure "Share on LinkedIn" product is added to your LinkedIn App.', 'ai-content-orchestrator' ); ?></em>
									<?php endif; ?>
								<?php else : ?>
									<em class="description"><?php esc_html_e( 'Not recorded (reconnect to capture).', 'ai-content-orchestrator' ); ?></em>
								<?php endif; ?>
							</td>
						</tr>
					</tbody>
				</table>
				<p style="margin-top: 12px;">
					<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=aicc-settings&aicc_linkedin_disconnect=1' ), 'aicc_linkedin_disconnect' ) ); ?>" class="button" onclick="return confirm('<?php esc_attr_e( 'Disconnect LinkedIn account?', 'ai-content-orchestrator' ); ?>');">
						<?php esc_html_e( 'Disconnect LinkedIn', 'ai-content-orchestrator' ); ?>
					</a>
				</p>
			<?php else : ?>
				<?php if ( ! empty( $_GET['aicc_linkedin_error'] ) ) : ?>
					<div class="notice notice-error inline" style="margin-top: 0; margin-bottom: 16px;">
						<p><?php echo esc_html( sanitize_text_field( wp_unslash( $_GET['aicc_linkedin_error'] ) ) ); ?></p>
					</div>
				<?php endif; ?>

				<h3 style="margin-top: 0;"><?php esc_html_e( 'Setup Guide — Follow these steps in order', 'ai-content-orchestrator' ); ?></h3>

				<div class="notice notice-warning inline" style="margin: 0 0 16px 0;">
					<p style="margin: 8px 0;">
						<strong><?php esc_html_e( 'Important — about the "LinkedIn Company Page" requirement:', 'ai-content-orchestrator' ); ?></strong><br>
						<?php esc_html_e( 'LinkedIn requires every developer app to be linked to a Company Page, even if you only want to post to your personal profile. This is a LinkedIn rule — there is no way around it. However, your posts will still go to your PERSONAL profile, not the Company Page. The Company Page is only an administrative link to the app.', 'ai-content-orchestrator' ); ?><br>
						<em><?php esc_html_e( 'Tip: Create a simple placeholder Company Page (e.g. with your name) — it takes 30 seconds and is free.', 'ai-content-orchestrator' ); ?>
						<a href="https://www.linkedin.com/company/setup/new/" target="_blank" rel="noopener"><?php esc_html_e( 'Create a Company Page →', 'ai-content-orchestrator' ); ?></a></em>
					</p>
				</div>

				<ol style="margin-left: 20px; line-height: 1.8;">
					<li>
						<strong><?php esc_html_e( 'Create a placeholder Company Page (if you don\'t already have one)', 'ai-content-orchestrator' ); ?></strong><br>
						<?php
						printf(
							/* translators: %s: link to LinkedIn Company setup */
							esc_html__( 'Go to %s. Choose "Self-employed", enter your name or brand, and click Create page. Required only because LinkedIn forces apps to be linked to a Company Page.', 'ai-content-orchestrator' ),
							'<a href="https://www.linkedin.com/company/setup/new/" target="_blank" rel="noopener">https://www.linkedin.com/company/setup/new/</a>'
						);
						?>
					</li>
					<li>
						<strong><?php esc_html_e( 'Create a LinkedIn App', 'ai-content-orchestrator' ); ?></strong><br>
						<?php
						printf(
							/* translators: %s: link to LinkedIn Developer Portal */
							esc_html__( 'Go to %s and click "Create app". When asked for "LinkedIn Page", select the Company Page you just created. Posts will still go to your personal profile.', 'ai-content-orchestrator' ),
							'<a href="https://www.linkedin.com/developers/apps/new" target="_blank" rel="noopener">https://www.linkedin.com/developers/apps/new</a>'
						);
						?>
					</li>
					<li>
						<strong><?php esc_html_e( 'Verify your app (one-time required step)', 'ai-content-orchestrator' ); ?></strong><br>
						<?php esc_html_e( 'In your app, click the "Settings" tab. Find the "App Verification" or "Verify" section and click "Generate URL". Open the generated URL in a new tab — you must be logged in as the admin of the linked Company Page (which is you). Click "Verify" on that page. This is mandatory before any product can be added.', 'ai-content-orchestrator' ); ?>
					</li>
					<li>
						<strong><?php esc_html_e( 'Add the required products', 'ai-content-orchestrator' ); ?></strong><br>
						<?php esc_html_e( 'Go to the "Products" tab. Under "Available products", click "Request access" / "Add" on BOTH of these:', 'ai-content-orchestrator' ); ?>
						<ul style="margin: 6px 0 6px 20px; list-style: disc;">
							<li><strong><?php esc_html_e( 'Sign In with LinkedIn using OpenID Connect', 'ai-content-orchestrator' ); ?></strong> <?php esc_html_e( '(Standard Tier) — grants openid, profile, email scopes', 'ai-content-orchestrator' ); ?></li>
							<li><strong><?php esc_html_e( 'Share on LinkedIn', 'ai-content-orchestrator' ); ?></strong> <?php esc_html_e( '(Default Tier) — grants the w_member_social scope (required to post)', 'ai-content-orchestrator' ); ?></li>
						</ul>
						<em class="description"><?php esc_html_e( 'Both products are auto-approved instantly after app verification — no waiting required. After adding, scroll to "Added products" at the bottom of the page to confirm both are listed there.', 'ai-content-orchestrator' ); ?>
					</li>
					<li>
						<strong><?php esc_html_e( 'Verify the OAuth scopes appeared', 'ai-content-orchestrator' ); ?></strong><br>
						<?php esc_html_e( 'Go to the "Auth" tab and scroll to "OAuth 2.0 scopes". You MUST see these 4 scopes listed:', 'ai-content-orchestrator' ); ?>
						<ul style="margin: 6px 0 6px 20px; list-style: disc; font-family: monospace;">
							<li>openid</li>
							<li>profile</li>
							<li>email</li>
							<li>w_member_social</li>
						</ul>
						<em class="description"><?php esc_html_e( 'If "OAuth 2.0 scopes" says "No permissions added", the products are not added correctly — go back to the Products tab and add them again.', 'ai-content-orchestrator' ); ?></em>
					</li>
					<li>
						<strong><?php esc_html_e( 'Add the Redirect URL', 'ai-content-orchestrator' ); ?></strong><br>
						<?php esc_html_e( 'In your app, go to the "Auth" tab. Under "OAuth 2.0 settings → Authorized redirect URLs for your app", click "+ Add redirect URL" and paste this exact URL:', 'ai-content-orchestrator' ); ?>
						<div style="background: #f0f0f1; padding: 8px 12px; border-radius: 4px; margin: 8px 0; font-family: monospace; word-break: break-all; user-select: all;">
							<?php echo esc_html( AICC_LinkedIn::get_redirect_uri() ); ?>
						</div>
						<em class="description"><?php esc_html_e( 'Click anywhere in the box above to select the URL, then copy it.', 'ai-content-orchestrator' ); ?></em>
					</li>
					<li>
						<strong><?php esc_html_e( 'Copy your Client ID and Client Secret', 'ai-content-orchestrator' ); ?></strong><br>
						<?php esc_html_e( 'Still in the "Auth" tab, copy the Client ID and Client Secret. Paste them into the fields above on this page and click "Save Changes".', 'ai-content-orchestrator' ); ?>
					</li>
					<li>
						<strong><?php esc_html_e( 'Connect your account', 'ai-content-orchestrator' ); ?></strong><br>
						<?php esc_html_e( 'After saving, a "Connect LinkedIn Account" button will appear here. Click it to authorize the plugin to post to your personal profile.', 'ai-content-orchestrator' ); ?>
					</li>
				</ol>

				<?php if ( ! empty( $linkedin_client_id ) ) : ?>
					<hr style="margin: 20px 0;">
					<p style="margin-bottom: 8px;">
						<strong><?php esc_html_e( 'Ready to connect!', 'ai-content-orchestrator' ); ?></strong>
						<?php esc_html_e( 'Your Client ID and Secret are saved. Click below to authorize:', 'ai-content-orchestrator' ); ?>
					</p>
					<?php
					$auth_url = AICC_LinkedIn::get_auth_url();
					if ( $auth_url ) : ?>
						<a href="<?php echo esc_url( $auth_url ); ?>" class="button button-primary button-hero">
							<span class="dashicons dashicons-linkedin" style="vertical-align: text-bottom; font-size: 18px; width: 18px; height: 18px; margin-right: 4px;"></span>
							<?php esc_html_e( 'Connect LinkedIn Account', 'ai-content-orchestrator' ); ?>
						</a>
					<?php endif; ?>

					<div style="margin-top: 20px; padding: 12px; background: #fff8e5; border-left: 4px solid #dba617;">
						<p style="margin: 0 0 8px 0;">
							<strong><?php esc_html_e( 'Getting "permission scope is not valid" error?', 'ai-content-orchestrator' ); ?></strong>
						</p>
						<p style="margin: 0 0 8px 0;">
							<?php esc_html_e( 'This means a required product is not added to your LinkedIn App. Use these diagnostic buttons to find which one:', 'ai-content-orchestrator' ); ?>
						</p>
						<?php
						$auth_signin_only = AICC_LinkedIn::get_auth_url( AICC_LinkedIn::SCOPES_SIGNIN_ONLY );
						$auth_share_only  = AICC_LinkedIn::get_auth_url( AICC_LinkedIn::SCOPES_SHARE_ONLY );
						?>
						<p style="margin: 8px 0;">
							<a href="<?php echo esc_url( $auth_signin_only ); ?>" class="button">
								<?php esc_html_e( 'Test 1: Sign In only (openid profile email)', 'ai-content-orchestrator' ); ?>
							</a>
							<span class="description"><?php esc_html_e( 'If this fails: "Sign In with LinkedIn using OpenID Connect" product is missing.', 'ai-content-orchestrator' ); ?></span>
						</p>
						<p style="margin: 8px 0;">
							<a href="<?php echo esc_url( $auth_share_only ); ?>" class="button">
								<?php esc_html_e( 'Test 2: Share only (w_member_social)', 'ai-content-orchestrator' ); ?>
							</a>
							<span class="description"><?php esc_html_e( 'If this fails: "Share on LinkedIn" product is missing.', 'ai-content-orchestrator' ); ?></span>
						</p>
						<p style="margin: 8px 0 0 0;">
							<em><?php esc_html_e( 'If both tests work individually but the main "Connect" button fails, try refreshing this page and trying again. LinkedIn sometimes caches OAuth state.', 'ai-content-orchestrator' ); ?></em>
						</p>
					</div>
				<?php else : ?>
					<hr style="margin: 20px 0;">
					<p>
						<span class="dashicons dashicons-info" style="color: #2271b1; vertical-align: text-bottom;"></span>
						<em><?php esc_html_e( 'Enter your Client ID and Client Secret in the fields above and click "Save Changes" to enable the Connect button.', 'ai-content-orchestrator' ); ?></em>
					</p>
				<?php endif; ?>
			<?php endif; ?>

			<?php if ( ! empty( $_GET['aicc_linkedin_success'] ) ) : ?>
				<div class="notice notice-success inline" style="margin-top: 12px;">
					<p><?php esc_html_e( 'LinkedIn account connected successfully!', 'ai-content-orchestrator' ); ?></p>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<?php endif; ?>

	<?php if ( 'general' === $active_tab ) : ?>
	<!-- Status overview -->
	<div class="aicc-card" style="max-width: 700px; margin-top: 20px;">
		<div class="aicc-card-header">
			<h2><?php esc_html_e( 'Status', 'ai-content-orchestrator' ); ?></h2>
		</div>
		<div class="aicc-card-body">
			<table class="widefat striped">
				<tbody>
					<tr>
						<td style="width: 200px; font-weight: 600;"><?php esc_html_e( 'Active Provider', 'ai-content-orchestrator' ); ?></td>
						<td>
							<?php echo esc_html( 'openai' === $provider ? 'OpenAI (GPT)' : 'Claude (Anthropic)' ); ?>
							<?php if ( AICC_Settings::is_configured() ) : ?>
								<span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span>
							<?php else : ?>
								<span class="dashicons dashicons-warning" style="color: #d63638;"></span>
								<em><?php esc_html_e( 'API key missing', 'ai-content-orchestrator' ); ?></em>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<td style="font-weight: 600;"><?php esc_html_e( 'Claude API Key', 'ai-content-orchestrator' ); ?></td>
						<td>
							<?php if ( $claude_set ) : ?>
								<span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span> <?php esc_html_e( 'Configured', 'ai-content-orchestrator' ); ?>
							<?php else : ?>
								<span class="dashicons dashicons-minus" style="color: #646970;"></span> <?php esc_html_e( 'Not set', 'ai-content-orchestrator' ); ?>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<td style="font-weight: 600;"><?php esc_html_e( 'OpenAI API Key', 'ai-content-orchestrator' ); ?></td>
						<td>
							<?php if ( $openai_set ) : ?>
								<span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span> <?php esc_html_e( 'Configured', 'ai-content-orchestrator' ); ?>
							<?php else : ?>
								<span class="dashicons dashicons-minus" style="color: #646970;"></span> <?php esc_html_e( 'Not set', 'ai-content-orchestrator' ); ?>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<td style="font-weight: 600;"><?php esc_html_e( 'Image Provider', 'ai-content-orchestrator' ); ?></td>
						<td>
							<?php
							$image_labels = array( 'openai' => 'OpenAI (DALL-E 3)', 'ideogram' => 'Ideogram' );
							echo esc_html( isset( $image_labels[ $image_provider ] ) ? $image_labels[ $image_provider ] : $image_provider );
							?>
							<?php if ( AICC_Settings::is_image_configured() ) : ?>
								<span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span>
							<?php else : ?>
								<span class="dashicons dashicons-warning" style="color: #d63638;"></span>
								<em><?php esc_html_e( 'API key missing', 'ai-content-orchestrator' ); ?></em>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<td style="font-weight: 600;"><?php esc_html_e( 'Ideogram API Key', 'ai-content-orchestrator' ); ?></td>
						<td>
							<?php if ( $ideogram_set ) : ?>
								<span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span> <?php esc_html_e( 'Configured', 'ai-content-orchestrator' ); ?>
							<?php else : ?>
								<span class="dashicons dashicons-minus" style="color: #646970;"></span> <?php esc_html_e( 'Not set', 'ai-content-orchestrator' ); ?>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<td style="font-weight: 600;"><?php esc_html_e( 'Yoast SEO', 'ai-content-orchestrator' ); ?></td>
						<td>
							<?php if ( $has_yoast ) : ?>
								<span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span>
								<?php
								printf(
									/* translators: %s: Yoast version */
									esc_html__( 'Detected (v%s) — SEO title, meta description, and focus keyphrase will be set automatically.', 'ai-content-orchestrator' ),
									esc_html( WPSEO_VERSION )
								);
								?>
							<?php else : ?>
								<span class="dashicons dashicons-info-outline" style="color: #dba617;"></span>
								<?php esc_html_e( 'Not detected. Install Yoast SEO for automatic SEO field population.', 'ai-content-orchestrator' ); ?>
							<?php endif; ?>
						</td>
					</tr>
					<tr>
						<td style="font-weight: 600;"><?php esc_html_e( 'LinkedIn', 'ai-content-orchestrator' ); ?></td>
						<td>
							<?php if ( $linkedin_connected && ! empty( $linkedin_profile['name'] ) ) : ?>
								<span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span>
								<?php
								printf(
									/* translators: %s: LinkedIn account name */
									esc_html__( 'Connected (%s)', 'ai-content-orchestrator' ),
									esc_html( $linkedin_profile['name'] )
								);
								?>
							<?php else : ?>
								<span class="dashicons dashicons-minus" style="color: #646970;"></span>
								<?php esc_html_e( 'Not connected', 'ai-content-orchestrator' ); ?>
							<?php endif; ?>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>

	<!-- Getting started -->
	<div class="aicc-card" style="max-width: 700px; margin-top: 20px;">
		<div class="aicc-card-header">
			<h2><?php esc_html_e( 'Getting Started', 'ai-content-orchestrator' ); ?></h2>
		</div>
		<div class="aicc-card-body">
			<ol style="margin-left: 20px;">
				<li>
					<?php esc_html_e( 'Choose your AI provider above (Claude or OpenAI).', 'ai-content-orchestrator' ); ?>
				</li>
				<li>
					<strong><?php esc_html_e( 'Claude:', 'ai-content-orchestrator' ); ?></strong>
					<?php esc_html_e( 'Sign up at console.anthropic.com to get your API key', 'ai-content-orchestrator' ); ?><br>
					<strong><?php esc_html_e( 'OpenAI:', 'ai-content-orchestrator' ); ?></strong>
					<?php esc_html_e( 'Sign up at platform.openai.com to get your API key', 'ai-content-orchestrator' ); ?>
				</li>
				<li><?php esc_html_e( 'Paste your API key in the field above and click Save Changes.', 'ai-content-orchestrator' ); ?></li>
				<li><?php esc_html_e( 'Click the Test buttons above to make sure everything is connected.', 'ai-content-orchestrator' ); ?></li>
				<li>
					<?php
					printf(
						/* translators: %1$s: opening link, %2$s: closing link */
						esc_html__( 'Go to %1$sCreate Content%2$s to generate your first AI-powered post or page.', 'ai-content-orchestrator' ),
						'<a href="' . esc_url( admin_url( 'admin.php?page=aicc-create' ) ) . '">',
						'</a>'
					);
					?>
				</li>
			</ol>
		</div>
	</div>
	<?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
	$('.aicc-validate-btn').on('click', function() {
		var $btn    = $(this);
		var provider = $btn.data('provider');
		var $result = $('.aicc-validate-result[data-provider="' + provider + '"]');

		$btn.prop('disabled', true).text('Validating...');
		$result.html('<span class="spinner is-active" style="float:none; margin:0;"></span>');

		$.ajax({
			url:      '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
			type:     'POST',
			dataType: 'json',
			data: {
				action:   'aicc_validate_api_key',
				nonce:    '<?php echo esc_js( wp_create_nonce( 'aicc_nonce' ) ); ?>',
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
				var labels = { claude: 'Test Claude Connection', openai: 'Test OpenAI Connection', ideogram: 'Test Ideogram Connection' };
				$btn.prop('disabled', false).text(labels[provider] || 'Test');
			},
			error: function(xhr, textStatus, errorThrown) {
				$result.html(
					'<span class="dashicons dashicons-dismiss" style="color:#d63638; vertical-align:text-bottom;"></span> ' +
					'<strong style="color:#d63638;">Request failed: ' + (errorThrown || textStatus) + '</strong>'
				);
				var labels = { claude: 'Test Claude Connection', openai: 'Test OpenAI Connection', ideogram: 'Test Ideogram Connection' };
				$btn.prop('disabled', false).text(labels[provider] || 'Test');
			},
			timeout: 30000,
		});
	});

	// ── Brand Colors — clickable swatches ─────────────────────
	function updateBrandColorInput() {
		var selected = [];
		$('#aicc-brand-swatches .aicc-color-selected').each(function() {
			selected.push($(this).data('color'));
		});
		$('#aicc_brand_colors').val(selected.join(', '));
		$('#aicc-color-count').text(selected.length + '/4 <?php echo esc_js( __( 'selected', 'ai-content-orchestrator' ) ); ?>');
	}

	function buildSwatch(color, isSelected) {
		var border = isSelected ? '3px solid #2271b1' : '2px solid #ccc';
		var check  = isSelected ? '<span style="position:absolute;bottom:-2px;right:-2px;background:#2271b1;color:#fff;width:12px;height:12px;border-radius:50%;font-size:9px;line-height:12px;text-align:center;">&#10003;</span>' : '';
		var cls    = isSelected ? 'aicc-color-swatch aicc-color-selected' : 'aicc-color-swatch';
		return '<span class="' + cls + '" data-color="' + color + '" style="display:inline-block;width:28px;height:28px;background:' + color + ';border:' + border + ';border-radius:4px;cursor:pointer;position:relative;" title="' + color + '">' + check + '</span>';
	}

	$(document).on('click', '.aicc-color-swatch', function() {
		var $swatch = $(this);
		if ($swatch.hasClass('aicc-color-selected')) {
			$swatch.removeClass('aicc-color-selected').css('border', '2px solid #ccc').find('span').remove();
		} else {
			var count = $('#aicc-brand-swatches .aicc-color-selected').length;
			if (count >= 4) {
				alert('<?php echo esc_js( __( 'Maximum 4 colors. Deselect one first.', 'ai-content-orchestrator' ) ); ?>');
				return;
			}
			$swatch.addClass('aicc-color-selected').css('border', '3px solid #2271b1');
			$swatch.append('<span style="position:absolute;bottom:-2px;right:-2px;background:#2271b1;color:#fff;width:12px;height:12px;border-radius:50%;font-size:9px;line-height:12px;text-align:center;">&#10003;</span>');
		}
		updateBrandColorInput();
	});

	// ── Scan Theme Colors button ──────────────────────────────
	$('#aicc-scan-theme-colors').on('click', function() {
		var $btn    = $(this);
		var $result = $('#aicc-scan-colors-result');

		$btn.prop('disabled', true).html('<span class="spinner is-active" style="float:none; margin:0 4px 0 0; vertical-align:text-bottom;"></span> <?php echo esc_js( __( 'Scanning...', 'ai-content-orchestrator' ) ); ?>');
		$result.empty();

		$.ajax({
			url:      '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>',
			type:     'POST',
			dataType: 'json',
			data: {
				action: 'aicc_scan_theme_colors',
				nonce:  '<?php echo esc_js( wp_create_nonce( 'aicc_nonce' ) ); ?>',
			},
			success: function(response) {
				if (response.success && response.data.colors) {
					var colors = response.data.colors;
					var $swatches = $('#aicc-brand-swatches');
					$swatches.empty();

					// Show all scanned colors as clickable swatches, pre-select first 4.
					for (var i = 0; i < colors.length; i++) {
						$swatches.append(buildSwatch(colors[i], i < 4));
					}
					updateBrandColorInput();

					$result.html(
						'<span class="dashicons dashicons-yes-alt" style="color:#00a32a; vertical-align:text-bottom;"></span> ' +
						'<strong>' + response.data.message + '</strong> ' +
						'<?php echo esc_js( __( 'First 4 colors pre-selected. Click to change. Save to persist.', 'ai-content-orchestrator' ) ); ?>'
					);
				} else {
					var msg = (response.data && response.data.message) ? response.data.message : '<?php echo esc_js( __( 'No colors found.', 'ai-content-orchestrator' ) ); ?>';
					$result.html('<span class="dashicons dashicons-info-outline" style="color:#dba617; vertical-align:text-bottom;"></span> ' + msg);
				}
				$btn.prop('disabled', false).html('<span class="dashicons dashicons-art" style="vertical-align:text-bottom; font-size:16px; width:16px; height:16px; margin-right:4px;"></span> <?php echo esc_js( __( 'Scan Theme Colors', 'ai-content-orchestrator' ) ); ?>');
			},
			error: function(xhr, textStatus, errorThrown) {
				$result.html('<span class="dashicons dashicons-dismiss" style="color:#d63638; vertical-align:text-bottom;"></span> <strong style="color:#d63638;">Request failed: ' + (errorThrown || textStatus) + '</strong>');
				$btn.prop('disabled', false).html('<span class="dashicons dashicons-art" style="vertical-align:text-bottom; font-size:16px; width:16px; height:16px; margin-right:4px;"></span> <?php echo esc_js( __( 'Scan Theme Colors', 'ai-content-orchestrator' ) ); ?>');
			},
			timeout: 30000,
		});
	});
});
</script>
