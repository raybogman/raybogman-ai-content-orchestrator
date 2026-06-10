<?php
/**
 * Main content creation page template.
 *
 * @package Ray_Bogman_AI_Content_Orchestrator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rbco_is_configured  = RBCO_Settings::is_configured();
$rbco_provider       = RBCO_Settings::get_ai_provider();
$rbco_model          = RBCO_Settings::get_active_model();
$rbco_has_yoast      = defined( 'WPSEO_VERSION' );
$rbco_categories     = RBCO_Publisher::get_categories();
$rbco_saved_urls     = RBCO_Settings::get_saved_urls();
$rbco_project_vision = RBCO_Settings::get_project_vision();
?>
<div class="wrap rbco-wrap">
	<h1 class="wp-heading-inline">
		<span class="dashicons dashicons-edit-large rbco-heading-icon"></span>
		<?php esc_html_e( 'Ray Bogman AI Content Orchestrator — Create Content', 'raybogman-ai-content-orchestrator' ); ?>
	</h1>
	<p class="rbco-subtitle">
		<?php esc_html_e( 'AI-powered content creation with website scanning, SEO optimization, and Yoast SEO integration.', 'raybogman-ai-content-orchestrator' ); ?>
	</p>

	<?php if ( ! $rbco_is_configured ) : ?>
		<div class="notice notice-error">
			<p>
				<strong><?php esc_html_e( 'API key not configured.', 'raybogman-ai-content-orchestrator' ); ?></strong>
				<?php
				printf(
					/* translators: %1$s: opening link tag, %2$s: closing link tag */
					esc_html__( 'Please %1$sconfigure your API key in Settings%2$s before creating content.', 'raybogman-ai-content-orchestrator' ),
					'<a href="' . esc_url( admin_url( 'admin.php?page=rbco-settings' ) ) . '">',
					'</a>'
				);
				?>
			</p>
		</div>
	<?php endif; ?>

	<!-- Status bar -->
	<div class="rbco-status-bar">
		<span class="rbco-status-item">
			<strong><?php esc_html_e( 'Provider:', 'raybogman-ai-content-orchestrator' ); ?></strong>
			<?php echo esc_html( 'openai' === $rbco_provider ? 'OpenAI' : 'Claude' ); ?>
			(<?php echo esc_html( $rbco_model ); ?>)
		</span>
		<span class="rbco-status-item">
			<strong><?php esc_html_e( 'Yoast SEO:', 'raybogman-ai-content-orchestrator' ); ?></strong>
			<?php if ( $rbco_has_yoast ) : ?>
				<span class="rbco-badge rbco-badge-success"><?php esc_html_e( 'Active', 'raybogman-ai-content-orchestrator' ); ?></span>
			<?php else : ?>
				<span class="rbco-badge rbco-badge-warning"><?php esc_html_e( 'Not installed', 'raybogman-ai-content-orchestrator' ); ?></span>
			<?php endif; ?>
		</span>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=rbco-settings' ) ); ?>" class="rbco-status-item rbco-status-link">
			<?php esc_html_e( 'Change Settings', 'raybogman-ai-content-orchestrator' ); ?> &rarr;
		</a>
	</div>

	<?php if ( ! empty( $rbco_project_vision ) ) : ?>
		<div class="notice notice-info" style="margin: 16px 0;">
			<p>
				<span class="dashicons dashicons-lightbulb" style="color: #2271b1; vertical-align: text-bottom;"></span>
				<strong><?php esc_html_e( 'Project Vision active', 'raybogman-ai-content-orchestrator' ); ?></strong> &mdash;
				<?php esc_html_e( 'Your custom writing instructions are active and will be applied to all generated content.', 'raybogman-ai-content-orchestrator' ); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=rbco-settings' ) ); ?>"><?php esc_html_e( 'Edit', 'raybogman-ai-content-orchestrator' ); ?></a>
			</p>
			<p class="description" style="margin-top: 0;">
				<em><?php echo esc_html( strlen( $rbco_project_vision ) > 200 ? substr( $rbco_project_vision, 0, 200 ) . '...' : $rbco_project_vision ); ?></em>
			</p>
		</div>
	<?php endif; ?>

	<div id="rbco-app">

		<!-- ─── Website Scanning ────────────────────────────────── -->
		<div class="rbco-card">
			<div class="rbco-card-header">
				<h2>
					<span class="dashicons dashicons-search" style="margin-right: 6px;"></span>
					<?php esc_html_e( 'Step 1: Website Scanning (optional)', 'raybogman-ai-content-orchestrator' ); ?>
				</h2>
			</div>
			<div class="rbco-card-body">
				<p class="description" style="margin-bottom: 12px;">
					<?php esc_html_e( 'Enter a page URL to read for context. The scanner reads only the single page you enter — it does not crawl, follow links, or read sitemaps — and extracts its content, headings, and metadata. This data is used as context for the AI to generate relevant, on-brand content.', 'raybogman-ai-content-orchestrator' ); ?>
				</p>
				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row">
								<label for="rbco-url"><?php esc_html_e( 'Website URL', 'raybogman-ai-content-orchestrator' ); ?></label>
							</th>
							<td>
								<input type="url" id="rbco-url" class="large-text" placeholder="https://example.com" />
								<p class="description">
									<?php esc_html_e( 'Enter one website URL to scan for context. Leave empty to skip scanning.', 'raybogman-ai-content-orchestrator' ); ?>
								</p>
								<p style="margin-top: 10px;">
									<label>
										<input type="checkbox" id="rbco-save-url" />
										<?php esc_html_e( 'Save URL for next time', 'raybogman-ai-content-orchestrator' ); ?>
										<span class="description">&mdash; <?php esc_html_e( 'quickly reuse it later without re-typing', 'raybogman-ai-content-orchestrator' ); ?></span>
									</label>
								</p>
							</td>
						</tr>
						<tr id="rbco-saved-urls-row" <?php echo empty( $rbco_saved_urls ) ? 'style="display:none;"' : ''; ?>>
							<th scope="row">
								<?php esc_html_e( 'Saved URLs', 'raybogman-ai-content-orchestrator' ); ?>
							</th>
							<td>
								<div id="rbco-saved-urls-list" class="rbco-saved-urls">
									<?php foreach ( $rbco_saved_urls as $rbco_saved_url ) : ?>
										<span class="rbco-url-chip" data-url="<?php echo esc_attr( $rbco_saved_url ); ?>">
											<span class="rbco-url-chip-text"><?php echo esc_html( $rbco_saved_url ); ?></span>
											<button type="button" class="rbco-url-chip-remove" title="<?php esc_attr_e( 'Remove', 'raybogman-ai-content-orchestrator' ); ?>">&times;</button>
										</span>
									<?php endforeach; ?>
								</div>
								<p class="description">
									<?php esc_html_e( 'Click a URL to use it. Click the × to remove it from the saved list.', 'raybogman-ai-content-orchestrator' ); ?>
								</p>
							</td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>

		<!-- ─── Content Creation ────────────────────────────────── -->
		<div class="rbco-card">
			<div class="rbco-card-header">
				<h2>
					<span class="dashicons dashicons-welcome-write-blog" style="margin-right: 6px;"></span>
					<?php esc_html_e( 'Step 2: Content Creation', 'raybogman-ai-content-orchestrator' ); ?>
				</h2>
			</div>
			<div class="rbco-card-body">
				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row">
								<label for="rbco-prompt"><?php esc_html_e( 'Prompt', 'raybogman-ai-content-orchestrator' ); ?></label>
							</th>
							<td>
								<textarea id="rbco-prompt" class="large-text" rows="5" placeholder="<?php esc_attr_e( "Describe what content to create...\ne.g. Write a blog post about the top 10 SEO strategies for small businesses", 'raybogman-ai-content-orchestrator' ); ?>"></textarea>
								<p class="description">
									<?php esc_html_e( 'Be specific about the topic, tone, and target audience. The AI will use this prompt along with any scanned website data.', 'raybogman-ai-content-orchestrator' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Content Type', 'raybogman-ai-content-orchestrator' ); ?></th>
							<td>
								<fieldset>
									<label>
										<input type="radio" name="rbco-type" value="blog" checked="checked" />
										<strong><?php esc_html_e( 'Blog Post', 'raybogman-ai-content-orchestrator' ); ?></strong>
										<span class="description">&mdash; <?php esc_html_e( 'targets 1000-2000 words, includes tags and categories', 'raybogman-ai-content-orchestrator' ); ?></span>
									</label>
									<br>
									<label>
										<input type="radio" name="rbco-type" value="page" />
										<strong><?php esc_html_e( 'Page', 'raybogman-ai-content-orchestrator' ); ?></strong>
										<span class="description">&mdash; <?php esc_html_e( 'targets 500-1000 words, no categories or tags', 'raybogman-ai-content-orchestrator' ); ?></span>
									</label>
								</fieldset>
							</td>
						</tr>
						<!-- Blog Style (visible for Blog Post only) -->
						<?php
						$rbco_styles         = RBCO_Styles::get_styles();
						$rbco_style_previews = RBCO_Styles::get_short_previews();
						?>
						<tr id="rbco-style-row">
							<th scope="row"><?php esc_html_e( 'Blog Style', 'raybogman-ai-content-orchestrator' ); ?></th>
							<td>
								<div style="display: flex; align-items: center; gap: 8px; position: relative;">
									<select id="rbco-blog-style" class="regular-text">
										<?php foreach ( $rbco_styles as $rbco_key => $rbco_style ) : ?>
											<option value="<?php echo esc_attr( $rbco_key ); ?>">
												<?php echo esc_html( $rbco_style['name'] ); ?> &mdash; <?php echo esc_html( $rbco_style['target_words'] ); ?> words
											</option>
										<?php endforeach; ?>
									</select>
									<span id="rbco-style-preview-trigger" class="dashicons dashicons-visibility" title="<?php esc_attr_e( 'Hover to preview this style', 'raybogman-ai-content-orchestrator' ); ?>" style="cursor: help; color: #2271b1; font-size: 20px; width: 20px; height: 20px;"></span>

									<!-- Floating preview panel -->
									<div id="rbco-style-preview-panel" class="rbco-style-preview-panel" style="display: none;">
										<div class="rbco-style-preview-header">
											<strong id="rbco-style-preview-title"></strong>
											<a href="<?php echo esc_url( admin_url( 'admin.php?page=rbco-examples' ) ); ?>" target="_blank" style="font-size: 12px; margin-left: auto;">
												<?php esc_html_e( 'View all examples', 'raybogman-ai-content-orchestrator' ); ?> &rarr;
											</a>
										</div>
										<div id="rbco-style-preview-content" class="rbco-preview" style="display: block; max-height: 350px;"></div>
									</div>
								</div>
								<p class="description" id="rbco-style-description">
									<?php echo esc_html( $rbco_styles['standard']['description'] ); ?>
								</p>

								<!-- Hidden preview data (inert markup, read by admin.js on hover) -->
								<?php foreach ( $rbco_style_previews as $rbco_key => $rbco_html ) : ?>
									<div class="rbco-style-preview-data" id="rbco-style-preview-data-<?php echo esc_attr( $rbco_key ); ?>" style="display:none;"><?php echo wp_kses_post( $rbco_html ); ?></div>
								<?php endforeach; ?>
							</td>
						</tr>

						<tr>
							<th scope="row"><?php esc_html_e( 'Publish Status', 'raybogman-ai-content-orchestrator' ); ?></th>
							<td>
								<fieldset>
									<label>
										<input type="radio" name="rbco-status" value="draft" checked="checked" />
										<strong><?php esc_html_e( 'Draft', 'raybogman-ai-content-orchestrator' ); ?></strong>
										<span class="description">&mdash; <?php esc_html_e( 'save for review before publishing', 'raybogman-ai-content-orchestrator' ); ?></span>
									</label>
									<br>
									<label>
										<input type="radio" name="rbco-status" value="publish" />
										<strong><?php esc_html_e( 'Publish', 'raybogman-ai-content-orchestrator' ); ?></strong>
										<span class="description">&mdash; <?php esc_html_e( 'publish immediately', 'raybogman-ai-content-orchestrator' ); ?></span>
									</label>
								</fieldset>
							</td>
						</tr>

						<!-- Featured Image -->
						<?php $rbco_image_configured = RBCO_Settings::is_image_configured(); ?>
						<tr>
							<th scope="row"><?php esc_html_e( 'Featured Image', 'raybogman-ai-content-orchestrator' ); ?></th>
							<td>
								<fieldset>
									<label>
										<input type="checkbox" id="rbco-generate-image" <?php echo $rbco_image_configured ? '' : 'disabled'; ?> />
										<strong><?php esc_html_e( 'Generate AI featured image', 'raybogman-ai-content-orchestrator' ); ?></strong>
									</label>
									<p class="description">
										<?php if ( $rbco_image_configured ) : ?>
											<?php esc_html_e( 'Uses OpenAI (gpt-image-1) to generate a landscape image based on the blog topic, then sets it as the featured image.', 'raybogman-ai-content-orchestrator' ); ?>
										<?php else : ?>
											<span class="dashicons dashicons-warning" style="color: #dba617; vertical-align: text-bottom;"></span>
											<em><?php esc_html_e( 'Requires an OpenAI API key. Configure it in Settings → General.', 'raybogman-ai-content-orchestrator' ); ?></em>
										<?php endif; ?>
									</p>
								</fieldset>
							</td>
						</tr>

						<!-- SEO Enhancements -->
						<tr>
							<th scope="row"><?php esc_html_e( 'SEO Enhancements', 'raybogman-ai-content-orchestrator' ); ?></th>
							<td>
								<fieldset>
									<label style="display:block; margin-bottom:6px;">
										<input type="checkbox" id="rbco-internal-linking" <?php echo RBCO_Settings::is_internal_linking_enabled() ? 'checked' : ''; ?> />
										<strong><?php esc_html_e( 'Auto-add internal links', 'raybogman-ai-content-orchestrator' ); ?></strong>
										<span class="description"> — <?php esc_html_e( 'links to your existing published posts for better SEO', 'raybogman-ai-content-orchestrator' ); ?></span>
									</label>
								</fieldset>
							</td>
						</tr>

						<!-- Categories (shown for Blog Post only) -->
						<tr id="rbco-categories-row">
							<th scope="row">
								<label for="rbco-categories"><?php esc_html_e( 'Categories', 'raybogman-ai-content-orchestrator' ); ?></label>
							</th>
							<td>
								<div id="rbco-categories-list" class="rbco-checkbox-list">
									<?php if ( ! empty( $rbco_categories ) ) : ?>
										<?php foreach ( $rbco_categories as $rbco_cat ) : ?>
											<label class="rbco-checkbox-item">
												<input type="checkbox" name="rbco-categories[]" value="<?php echo esc_attr( $rbco_cat['id'] ); ?>" />
												<?php echo esc_html( $rbco_cat['name'] ); ?>
											</label>
										<?php endforeach; ?>
									<?php else : ?>
										<p class="description"><?php esc_html_e( 'No categories found. The AI will suggest and create categories automatically.', 'raybogman-ai-content-orchestrator' ); ?></p>
									<?php endif; ?>
								</div>
								<p class="description">
									<?php esc_html_e( 'Select categories to assign. The AI may also suggest additional categories which will be created automatically.', 'raybogman-ai-content-orchestrator' ); ?>
								</p>
							</td>
						</tr>
					</tbody>
				</table>

				<p class="submit">
					<button type="button" id="rbco-submit" class="button button-primary button-hero" <?php echo $rbco_is_configured ? '' : 'disabled'; ?>>
						<span class="dashicons dashicons-admin-post rbco-btn-icon"></span>
						<?php esc_html_e( 'Create Content', 'raybogman-ai-content-orchestrator' ); ?>
					</button>
				</p>
			</div>
		</div>

		<!-- ─── Progress Log ────────────────────────────────────── -->
		<div id="rbco-log-area" class="rbco-card" style="display: none;">
			<div class="rbco-card-header" style="display:flex; justify-content:space-between; align-items:center;">
				<h2 style="margin:0;">
					<span class="spinner is-active" id="rbco-spinner" style="float: none; margin: 0 8px 0 0;"></span>
					<?php esc_html_e( 'Progress', 'raybogman-ai-content-orchestrator' ); ?>
				</h2>
				<button type="button" class="button button-small rbco-save-log" data-log="#rbco-log-box" title="<?php esc_attr_e( 'Download log as text file', 'raybogman-ai-content-orchestrator' ); ?>">
					<span class="dashicons dashicons-download" style="vertical-align:text-bottom; font-size:14px; width:14px; height:14px; margin-right:2px;"></span>
					<?php esc_html_e( 'Save Log', 'raybogman-ai-content-orchestrator' ); ?>
				</button>
			</div>
			<div class="rbco-card-body">
				<div id="rbco-log-box" class="rbco-log-box"></div>
			</div>
		</div>

		<!-- ─── Results ─────────────────────────────────────────── -->
		<div id="rbco-result-card" class="rbco-card rbco-result-card" style="display: none;">
			<div class="rbco-card-header rbco-card-header-success">
				<h2>
					<span class="dashicons dashicons-yes-alt" style="margin-right: 6px;"></span>
					<?php esc_html_e( 'Content Created Successfully', 'raybogman-ai-content-orchestrator' ); ?>
				</h2>
			</div>
			<div class="rbco-card-body">
				<table class="widefat striped" id="rbco-result-table">
					<tbody></tbody>
				</table>

				<div id="rbco-result-actions" style="margin-top: 16px;">
					<a id="rbco-view-post" href="#" class="button button-primary" target="_blank">
						<span class="dashicons dashicons-external" style="vertical-align: text-bottom; font-size: 16px; width: 16px; height: 16px; margin-right: 4px;"></span>
						<?php esc_html_e( 'View Post', 'raybogman-ai-content-orchestrator' ); ?>
					</a>
					<a id="rbco-edit-post" href="#" class="button" target="_blank">
						<span class="dashicons dashicons-edit" style="vertical-align: text-bottom; font-size: 16px; width: 16px; height: 16px; margin-right: 4px;"></span>
						<?php esc_html_e( 'Edit in WordPress', 'raybogman-ai-content-orchestrator' ); ?>
					</a>
					<button type="button" id="rbco-toggle-preview" class="button">
						<span class="dashicons dashicons-visibility" style="vertical-align: text-bottom; font-size: 16px; width: 16px; height: 16px; margin-right: 4px;"></span>
						<?php esc_html_e( 'Toggle Preview', 'raybogman-ai-content-orchestrator' ); ?>
					</button>
				</div>

				<div id="rbco-preview" class="rbco-preview" style="display: none;"></div>
			</div>
		</div>
	</div>
</div>
