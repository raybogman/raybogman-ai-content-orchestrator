<?php
/**
 * Main content creation page template.
 *
 * @package RayAI_Content_Orchestrator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rayai_is_configured  = RAYAI_Settings::is_configured();
$rayai_provider       = RAYAI_Settings::get_ai_provider();
$rayai_model          = RAYAI_Settings::get_active_model();
$rayai_has_yoast      = defined( 'WPSEO_VERSION' );
$rayai_categories     = RAYAI_Publisher::get_categories();
$rayai_saved_urls     = RAYAI_Settings::get_saved_urls();
$rayai_pdf_library    = RAYAI_PDF_Library::get_for_js();
$rayai_project_vision = RAYAI_Settings::get_project_vision();
?>
<div class="wrap rayai-wrap">
	<h1 class="wp-heading-inline">
		<span class="dashicons dashicons-edit-large rayai-heading-icon"></span>
		<?php esc_html_e( 'RayAI – Content Orchestrator — Create Content', 'rayai-content-orchestrator' ); ?>
	</h1>
	<p class="rayai-subtitle">
		<?php esc_html_e( 'AI-powered content creation with website scanning, SEO optimization, and Yoast SEO integration.', 'rayai-content-orchestrator' ); ?>
	</p>

	<?php if ( ! $rayai_is_configured ) : ?>
		<div class="notice notice-error">
			<p>
				<strong><?php esc_html_e( 'API key not configured.', 'rayai-content-orchestrator' ); ?></strong>
				<?php
				printf(
					/* translators: %1$s: opening link tag, %2$s: closing link tag */
					esc_html__( 'Please %1$sconfigure your API key in Settings%2$s before creating content.', 'rayai-content-orchestrator' ),
					'<a href="' . esc_url( admin_url( 'admin.php?page=rayai-settings' ) ) . '">',
					'</a>'
				);
				?>
			</p>
		</div>
	<?php endif; ?>

	<!-- Status bar -->
	<div class="rayai-status-bar">
		<span class="rayai-status-item">
			<strong><?php esc_html_e( 'Provider:', 'rayai-content-orchestrator' ); ?></strong>
			<?php echo esc_html( 'openai' === $rayai_provider ? 'OpenAI' : 'Claude' ); ?>
			(<?php echo esc_html( $rayai_model ); ?>)
		</span>
		<span class="rayai-status-item">
			<strong><?php esc_html_e( 'Yoast SEO:', 'rayai-content-orchestrator' ); ?></strong>
			<?php if ( $rayai_has_yoast ) : ?>
				<span class="rayai-badge rayai-badge-success"><?php esc_html_e( 'Active', 'rayai-content-orchestrator' ); ?></span>
			<?php else : ?>
				<span class="rayai-badge rayai-badge-warning"><?php esc_html_e( 'Not installed', 'rayai-content-orchestrator' ); ?></span>
			<?php endif; ?>
		</span>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=rayai-settings' ) ); ?>" class="rayai-status-item rayai-status-link">
			<?php esc_html_e( 'Change Settings', 'rayai-content-orchestrator' ); ?> &rarr;
		</a>
	</div>

	<?php if ( ! empty( $rayai_project_vision ) ) : ?>
		<div class="notice notice-info" style="margin: 16px 0;">
			<p>
				<span class="dashicons dashicons-lightbulb" style="color: #2271b1; vertical-align: text-bottom;"></span>
				<strong><?php esc_html_e( 'Project Vision active', 'rayai-content-orchestrator' ); ?></strong> &mdash;
				<?php esc_html_e( 'Your custom writing instructions are active and will be applied to all generated content.', 'rayai-content-orchestrator' ); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=rayai-settings' ) ); ?>"><?php esc_html_e( 'Edit', 'rayai-content-orchestrator' ); ?></a>
			</p>
			<p class="description" style="margin-top: 0;">
				<em><?php echo esc_html( strlen( $rayai_project_vision ) > 200 ? substr( $rayai_project_vision, 0, 200 ) . '...' : $rayai_project_vision ); ?></em>
			</p>
		</div>
	<?php endif; ?>

	<div id="rayai-app">

		<!-- ─── Website Scanning ────────────────────────────────── -->
		<div class="rayai-card">
			<div class="rayai-card-header">
				<h2>
					<span class="dashicons dashicons-search" style="margin-right: 6px;"></span>
					<?php esc_html_e( 'Step 1: Website Scanning (optional)', 'rayai-content-orchestrator' ); ?>
				</h2>
			</div>
			<div class="rayai-card-body">
				<p class="description" style="margin-bottom: 12px;">
					<?php esc_html_e( 'Enter a website URL to scan for context. The scanner will crawl sitemaps and internal links to extract content, headings, and metadata. This scanned data is used as context for the AI to generate relevant, on-brand content.', 'rayai-content-orchestrator' ); ?>
				</p>
				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row">
								<label for="rayai-url"><?php esc_html_e( 'Website URL', 'rayai-content-orchestrator' ); ?></label>
							</th>
							<td>
								<input type="<?php echo rayai_is_pro() ? 'text' : 'url'; ?>" id="rayai-url" class="large-text" placeholder="https://example.com" />
								<p class="description">
									<?php if ( rayai_is_pro() ) : ?>
										<?php esc_html_e( 'Separate multiple URLs with commas. Leave empty to skip scanning.', 'rayai-content-orchestrator' ); ?>
									<?php else : ?>
										<?php esc_html_e( 'Enter one website URL to scan for context. Leave empty to skip scanning.', 'rayai-content-orchestrator' ); ?>
										<span style="background:#E4405F;color:#fff;padding:1px 6px;border-radius:8px;font-size:10px;font-weight:600;">ENT</span> <?php esc_html_e( 'Multiple URLs', 'rayai-content-orchestrator' ); ?>
									<?php endif; ?>
								</p>
								<p style="margin-top: 10px;">
									<label>
										<input type="checkbox" id="rayai-save-url" />
										<?php esc_html_e( 'Save URL for next time', 'rayai-content-orchestrator' ); ?>
										<span class="description">&mdash; <?php esc_html_e( 'quickly reuse it later without re-typing', 'rayai-content-orchestrator' ); ?></span>
									</label>
								</p>
							</td>
						</tr>
						<tr id="rayai-saved-urls-row" <?php echo empty( $rayai_saved_urls ) ? 'style="display:none;"' : ''; ?>>
							<th scope="row">
								<?php esc_html_e( 'Saved URLs', 'rayai-content-orchestrator' ); ?>
							</th>
							<td>
								<div id="rayai-saved-urls-list" class="rayai-saved-urls">
									<?php foreach ( $rayai_saved_urls as $rayai_saved_url ) : ?>
										<span class="rayai-url-chip" data-url="<?php echo esc_attr( $rayai_saved_url ); ?>">
											<span class="rayai-url-chip-text"><?php echo esc_html( $rayai_saved_url ); ?></span>
											<button type="button" class="rayai-url-chip-remove" title="<?php esc_attr_e( 'Remove', 'rayai-content-orchestrator' ); ?>">&times;</button>
										</span>
									<?php endforeach; ?>
								</div>
								<p class="description">
									<?php esc_html_e( 'Click a URL to use it. Click the × to remove it from the saved list.', 'rayai-content-orchestrator' ); ?>
								</p>
							</td>
						</tr>
					</tbody>
						<!-- PDF Sources (Enterprise) -->
						<?php if ( rayai_is_pro() ) : ?>
						<tr>
							<th scope="row">
								<?php esc_html_e( 'PDF Sources', 'rayai-content-orchestrator' ); ?>
							</th>
							<td>
								<div class="rayai-pdf-upload-area">
									<input type="file" id="rayai-pdf-file" accept=".pdf" style="display:none;" />
									<button type="button" id="rayai-pdf-upload-btn" class="button">
										<span class="dashicons dashicons-pdf" style="vertical-align: text-bottom; font-size: 16px; width: 16px; height: 16px; margin-right: 4px;"></span>
										<?php esc_html_e( 'Upload PDF', 'rayai-content-orchestrator' ); ?>
									</button>
									<span id="rayai-pdf-upload-status" style="margin-left: 12px;"></span>
								</div>
								<p class="description" style="margin-top: 6px;">
									<?php
									printf(
										/* translators: %s: max upload size */
										esc_html__( 'Upload a PDF to use as context for content generation. Max file size: %s. Text will be extracted automatically.', 'rayai-content-orchestrator' ),
										esc_html( RAYAI_PDF_Library::get_max_upload_size_formatted() )
									);
									?>
								</p>

								<?php if ( ! empty( $rayai_pdf_library ) ) : ?>
								<div id="rayai-pdf-library" class="rayai-pdf-library" style="margin-top: 12px;">
									<p class="description" style="margin-bottom: 8px;">
										<strong><?php esc_html_e( 'Saved PDFs — check to use as source:', 'rayai-content-orchestrator' ); ?></strong>
									</p>
									<?php foreach ( $rayai_pdf_library as $rayai_pdf ) : ?>
										<div class="rayai-pdf-item" data-pdf-id="<?php echo esc_attr( $rayai_pdf['id'] ); ?>">
											<label class="rayai-pdf-label">
												<input type="checkbox" name="rayai-pdf-ids[]" value="<?php echo esc_attr( $rayai_pdf['id'] ); ?>" class="rayai-pdf-checkbox" />
												<span class="dashicons dashicons-pdf" style="color: #d63638; vertical-align: text-bottom;"></span>
												<strong><?php echo esc_html( $rayai_pdf['name'] ); ?></strong>
												<span class="description">
													&mdash; <?php echo esc_html( $rayai_pdf['upload_date'] ); ?>
													&middot; <?php echo esc_html( number_format( $rayai_pdf['text_length'] ) ); ?> chars
												</span>
											</label>
											<button type="button" class="rayai-pdf-delete-btn" data-pdf-id="<?php echo esc_attr( $rayai_pdf['id'] ); ?>" title="<?php esc_attr_e( 'Delete', 'rayai-content-orchestrator' ); ?>">
												<span class="dashicons dashicons-trash" style="color: #d63638; font-size: 14px; width: 14px; height: 14px;"></span>
											</button>
											<div class="rayai-pdf-preview description"><?php echo esc_html( $rayai_pdf['text_preview'] ); ?></div>
										</div>
									<?php endforeach; ?>
								</div>
								<?php else : ?>
								<div id="rayai-pdf-library" class="rayai-pdf-library" style="margin-top: 12px; display: none;"></div>
								<?php endif; ?>
							</td>
						</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>

		<!-- ─── Content Creation ────────────────────────────────── -->
		<div class="rayai-card">
			<div class="rayai-card-header">
				<h2>
					<span class="dashicons dashicons-welcome-write-blog" style="margin-right: 6px;"></span>
					<?php esc_html_e( 'Step 2: Content Creation', 'rayai-content-orchestrator' ); ?>
				</h2>
			</div>
			<div class="rayai-card-body">
				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row">
								<label for="rayai-prompt"><?php esc_html_e( 'Prompt', 'rayai-content-orchestrator' ); ?></label>
							</th>
							<td>
								<textarea id="rayai-prompt" class="large-text" rows="5" placeholder="<?php esc_attr_e( "Describe what content to create...\ne.g. Write a blog post about the top 10 SEO strategies for small businesses", 'rayai-content-orchestrator' ); ?>"></textarea>
								<p class="description">
									<?php esc_html_e( 'Be specific about the topic, tone, and target audience. The AI will use this prompt along with any scanned website data.', 'rayai-content-orchestrator' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Content Type', 'rayai-content-orchestrator' ); ?></th>
							<td>
								<fieldset>
									<label>
										<input type="radio" name="rayai-type" value="blog" checked="checked" />
										<strong><?php esc_html_e( 'Blog Post', 'rayai-content-orchestrator' ); ?></strong>
										<span class="description">&mdash; <?php esc_html_e( 'targets 1000-2000 words, includes tags and categories', 'rayai-content-orchestrator' ); ?></span>
									</label>
									<br>
									<label>
										<input type="radio" name="rayai-type" value="page" />
										<strong><?php esc_html_e( 'Page', 'rayai-content-orchestrator' ); ?></strong>
										<span class="description">&mdash; <?php esc_html_e( 'targets 500-1000 words, no categories or tags', 'rayai-content-orchestrator' ); ?></span>
									</label>
								</fieldset>
							</td>
						</tr>
						<!-- Blog Style (visible for Blog Post only) -->
						<?php
						$rayai_styles          = RAYAI_Styles::get_styles();
						$rayai_style_previews  = RAYAI_Styles::get_short_previews();
						?>
						<tr id="rayai-style-row">
							<th scope="row"><?php esc_html_e( 'Blog Style', 'rayai-content-orchestrator' ); ?></th>
							<td>
								<div style="display: flex; align-items: center; gap: 8px; position: relative;">
									<select id="rayai-blog-style" class="regular-text">
										<?php foreach ( $rayai_styles as $rayai_key => $rayai_style ) : ?>
											<option value="<?php echo esc_attr( $rayai_key ); ?>">
												<?php echo esc_html( $rayai_style['name'] ); ?> &mdash; <?php echo esc_html( $rayai_style['target_words'] ); ?> words
											</option>
										<?php endforeach; ?>
									</select>
									<span id="rayai-style-preview-trigger" class="dashicons dashicons-visibility" title="<?php esc_attr_e( 'Hover to preview this style', 'rayai-content-orchestrator' ); ?>" style="cursor: help; color: #2271b1; font-size: 20px; width: 20px; height: 20px;"></span>

									<!-- Floating preview panel -->
									<div id="rayai-style-preview-panel" class="rayai-style-preview-panel" style="display: none;">
										<div class="rayai-style-preview-header">
											<strong id="rayai-style-preview-title"></strong>
											<a href="<?php echo esc_url( admin_url( 'admin.php?page=rayai-examples' ) ); ?>" target="_blank" style="font-size: 12px; margin-left: auto;">
												<?php esc_html_e( 'View all examples', 'rayai-content-orchestrator' ); ?> &rarr;
											</a>
										</div>
										<div id="rayai-style-preview-content" class="rayai-preview" style="display: block; max-height: 350px;"></div>
									</div>
								</div>
								<p class="description" id="rayai-style-description">
									<?php echo esc_html( $rayai_styles['standard']['description'] ); ?>
								</p>

								<!-- Hidden preview data -->
								<?php foreach ( $rayai_style_previews as $rayai_key => $rayai_html ) : ?>
									<script type="text/html" id="rayai-style-preview-data-<?php echo esc_attr( $rayai_key ); ?>"><?php echo wp_kses_post( $rayai_html ); ?></script>
								<?php endforeach; ?>
							</td>
						</tr>

						<tr>
							<th scope="row"><?php esc_html_e( 'Publish Status', 'rayai-content-orchestrator' ); ?></th>
							<td>
								<fieldset>
									<label>
										<input type="radio" name="rayai-status" value="draft" checked="checked" />
										<strong><?php esc_html_e( 'Draft', 'rayai-content-orchestrator' ); ?></strong>
										<span class="description">&mdash; <?php esc_html_e( 'save for review before publishing', 'rayai-content-orchestrator' ); ?></span>
									</label>
									<br>
									<label>
										<input type="radio" name="rayai-status" value="publish" />
										<strong><?php esc_html_e( 'Publish', 'rayai-content-orchestrator' ); ?></strong>
										<span class="description">&mdash; <?php esc_html_e( 'publish immediately (or at scheduled time)', 'rayai-content-orchestrator' ); ?></span>
									</label>
								</fieldset>
							</td>
						</tr>

						<tr>
							<th scope="row"><?php esc_html_e( 'Schedule', 'rayai-content-orchestrator' ); ?></th>
							<td>
								<fieldset>
									<label>
										<input type="checkbox" id="rayai-schedule-enabled" />
										<strong><?php esc_html_e( 'Schedule for later', 'rayai-content-orchestrator' ); ?></strong>
									</label>
									<div id="rayai-schedule-fields" style="display:none; margin-top: 10px;">
										<input type="datetime-local" id="rayai-schedule-at" class="regular-text" min="<?php echo esc_attr( wp_date( 'Y-m-d\TH:i', time() + 300 ) ); ?>" />
										<p class="description">
											<?php esc_html_e( 'Site time zone:', 'rayai-content-orchestrator' ); ?>
											<code><?php echo esc_html( wp_timezone_string() ); ?></code>
											&middot;
											<?php esc_html_e( 'Current time:', 'rayai-content-orchestrator' ); ?>
											<code><?php echo esc_html( wp_date( 'Y-m-d H:i' ) ); ?></code>
										</p>
										<p class="description" id="rayai-schedule-help-draft">
											<span class="dashicons dashicons-info" style="color:#2271b1;"></span>
											<?php esc_html_e( 'Draft + Schedule = Review before publishing. The post will appear in your Scheduled list for you to approve first.', 'rayai-content-orchestrator' ); ?>
										</p>
										<p class="description" id="rayai-schedule-help-publish" style="display:none;">
											<span class="dashicons dashicons-info" style="color:#2271b1;"></span>
											<?php esc_html_e( 'Publish + Schedule = Direct scheduled publication. WordPress will publish it automatically at the scheduled time.', 'rayai-content-orchestrator' ); ?>
										</p>
									</div>
								</fieldset>
							</td>
						</tr>

						<!-- LinkedIn (Enterprise) -->
						<?php if ( rayai_is_pro() && RAYAI_LinkedIn::is_connected() ) : ?>
							<?php $rayai_li_profile = RAYAI_LinkedIn::get_profile(); ?>
							<tr>
								<th scope="row"><?php esc_html_e( 'LinkedIn', 'rayai-content-orchestrator' ); ?></th>
								<td>
									<fieldset>
										<label>
											<input type="checkbox" id="rayai-linkedin" />
											<strong><?php esc_html_e( 'Post to LinkedIn when published', 'rayai-content-orchestrator' ); ?></strong>
										</label>
										<p class="description">
											<?php
											printf(
												/* translators: %s: LinkedIn account name */
												esc_html__( 'Will share to LinkedIn as %s when the content is published (immediately or after approval).', 'rayai-content-orchestrator' ),
												'<strong>' . esc_html( $rayai_li_profile['name'] ) . '</strong>'
											);
											?>
										</p>
									</fieldset>
								</td>
							</tr>
						<?php endif; ?>

						<!-- Instagram (Enterprise) -->
						<?php if ( rayai_is_pro() && RAYAI_Instagram::is_connected() ) : ?>
							<?php $rayai_ig_profile = RAYAI_Instagram::get_profile(); ?>
							<tr>
								<th scope="row"><?php esc_html_e( 'Instagram', 'rayai-content-orchestrator' ); ?></th>
								<td>
									<fieldset>
										<label>
											<input type="checkbox" id="rayai-instagram" />
											<strong><?php esc_html_e( 'Post to Instagram when published', 'rayai-content-orchestrator' ); ?></strong>
										</label>
										<p class="description">
											<?php
											printf(
            /* translators: %s: dynamic value */
												esc_html__( 'Will share to Instagram as @%s with the featured image and an AI-generated caption.', 'rayai-content-orchestrator' ),
												esc_html( $rayai_ig_profile['username'] ?? '' )
											);
											?>
										</p>
									</fieldset>
								</td>
							</tr>
						<?php endif; ?>

						<!-- Featured Image -->
						<?php
						$rayai_image_configured = RAYAI_Settings::is_image_configured();
						$rayai_image_provider   = RAYAI_Settings::get_image_provider();
						$rayai_image_labels     = array( 'openai' => 'OpenAI (DALL-E 3)', 'ideogram' => 'Ideogram' );
						$rayai_image_label      = isset( $rayai_image_labels[ $rayai_image_provider ] ) ? $rayai_image_labels[ $rayai_image_provider ] : $rayai_image_provider;
						?>
						<tr>
							<th scope="row"><?php esc_html_e( 'Featured Image', 'rayai-content-orchestrator' ); ?></th>
							<td>
								<fieldset>
									<label>
										<input type="checkbox" id="rayai-generate-image" <?php echo $rayai_image_configured ? '' : 'disabled'; ?> />
										<strong><?php esc_html_e( 'Generate AI featured image', 'rayai-content-orchestrator' ); ?></strong>
									</label>
									<p class="description">
										<?php if ( $rayai_image_configured ) : ?>
											<?php
											printf(
												/* translators: %s: image provider name (e.g. "OpenAI (DALL-E 3)" or "Ideogram") */
												esc_html__( 'Uses %s to generate a landscape image based on the blog topic. Set as the featured image — LinkedIn will automatically show this image when sharing.', 'rayai-content-orchestrator' ),
												'<strong>' . esc_html( $rayai_image_label ) . '</strong>'
											);
											?>
										<?php else : ?>
											<span class="dashicons dashicons-warning" style="color: #dba617; vertical-align: text-bottom;"></span>
											<?php
											printf(
												/* translators: %s: image provider name */
												'<em>' . esc_html__( 'Requires an API key for %s. Configure it in Settings → Featured Image Provider section.', 'rayai-content-orchestrator' ) . '</em>',
												esc_html( $rayai_image_label )
											);
											?>
										<?php endif; ?>
									</p>
								</fieldset>
							</td>
						</tr>

						<!-- SEO Enhancements -->
						<tr>
							<th scope="row"><?php esc_html_e( 'SEO Enhancements', 'rayai-content-orchestrator' ); ?></th>
							<td>
								<fieldset>
									<label style="display:block; margin-bottom:6px;">
										<input type="checkbox" id="rayai-internal-linking" <?php echo RAYAI_Settings::is_internal_linking_enabled() ? 'checked' : ''; ?> />
										<strong><?php esc_html_e( 'Auto-add internal links', 'rayai-content-orchestrator' ); ?></strong>
										<span class="description"> — <?php esc_html_e( 'links to your existing published posts for better SEO', 'rayai-content-orchestrator' ); ?></span>
									</label>
									<?php if ( rayai_is_pro() ) : ?>
									<label style="display:block;">
										<input type="checkbox" id="rayai-competitor-analysis" <?php echo RAYAI_Settings::get_competitor_analysis_enabled() ? 'checked' : ''; ?> />
										<strong><?php esc_html_e( 'Analyze competitors first', 'rayai-content-orchestrator' ); ?></strong>
										<span class="description"> — <?php esc_html_e( 'scans top Google results for your keyword and writes content that covers more topics', 'rayai-content-orchestrator' ); ?></span>
									</label>
									<?php else : ?>
									<label style="display:block; opacity:0.5;">
										<input type="checkbox" disabled />
										<strong><?php esc_html_e( 'Analyze competitors first', 'rayai-content-orchestrator' ); ?></strong>
										<span style="background:#E4405F;color:#fff;padding:1px 6px;border-radius:8px;font-size:10px;font-weight:600;">ENT</span>
									</label>
									<?php endif; ?>
								</fieldset>
							</td>
						</tr>

						<!-- Output Format -->
						<tr>
							<th scope="row"><?php esc_html_e( 'Output Format', 'rayai-content-orchestrator' ); ?></th>
							<td>
								<fieldset>
									<?php $rayai_default_format = RAYAI_Settings::get_default_output_format(); ?>
									<select id="rayai-output-format" style="min-width:260px;">
										<option value="wordpress" <?php selected( $rayai_default_format, 'wordpress' ); ?>><?php esc_html_e( 'WordPress (Standard)', 'rayai-content-orchestrator' ); ?></option>
										<?php if ( rayai_is_pro() ) : ?>
										<option value="thrive" <?php selected( $rayai_default_format, 'thrive' ); ?>><?php esc_html_e( 'Thrive Architect (compatible)', 'rayai-content-orchestrator' ); ?></option>
									<?php else : ?>
										<option value="thrive" disabled><?php esc_html_e( 'Thrive Architect (Enterprise)', 'rayai-content-orchestrator' ); ?></option>
									<?php endif; ?>
									</select>
									<p class="description" id="rayai-output-format-desc">
										<?php esc_html_e( 'Choose how the content is formatted. WordPress (Standard) works with any theme. Thrive Architect creates content that\'s fully editable in Thrive\'s visual editor.', 'rayai-content-orchestrator' ); ?>
									</p>
									<p class="description" id="rayai-thrive-warning" style="display:none; color:#b26200; background:#fff8e5; padding:8px 12px; border-left:3px solid #dba617; margin-top:8px;">
										<span class="dashicons dashicons-info-outline" style="vertical-align:text-bottom;"></span>
										<strong><?php esc_html_e( 'About Thrive Architect mode:', 'rayai-content-orchestrator' ); ?></strong>
										<?php esc_html_e( 'Each heading, paragraph, and list becomes its own editable block in Thrive. The first time you open the post in Thrive Architect, the editor may rearrange things slightly — this is normal. Advanced Thrive elements (buttons, forms, timers) need to be added manually inside Thrive\'s editor.', 'rayai-content-orchestrator' ); ?>
									</p>
								</fieldset>
							</td>
						</tr>

						<!-- Categories (shown for Blog Post only) -->
						<tr id="rayai-categories-row">
							<th scope="row">
								<label for="rayai-categories"><?php esc_html_e( 'Categories', 'rayai-content-orchestrator' ); ?></label>
							</th>
							<td>
								<div id="rayai-categories-list" class="rayai-checkbox-list">
									<?php if ( ! empty( $rayai_categories ) ) : ?>
										<?php foreach ( $rayai_categories as $rayai_cat ) : ?>
											<label class="rayai-checkbox-item">
												<input type="checkbox" name="rayai-categories[]" value="<?php echo esc_attr( $rayai_cat['id'] ); ?>" />
												<?php echo esc_html( $rayai_cat['name'] ); ?>
											</label>
										<?php endforeach; ?>
									<?php else : ?>
										<p class="description"><?php esc_html_e( 'No categories found. The AI will suggest and create categories automatically.', 'rayai-content-orchestrator' ); ?></p>
									<?php endif; ?>
								</div>
								<p class="description">
									<?php esc_html_e( 'Select categories to assign. The AI may also suggest additional categories which will be created automatically.', 'rayai-content-orchestrator' ); ?>
								</p>
							</td>
						</tr>
					</tbody>
				</table>

				<p class="submit">
					<button type="button" id="rayai-submit" class="button button-primary button-hero" <?php echo $rayai_is_configured ? '' : 'disabled'; ?>>
						<span class="dashicons dashicons-admin-post rayai-btn-icon"></span>
						<?php esc_html_e( 'Create Content', 'rayai-content-orchestrator' ); ?>
					</button>
				</p>
			</div>
		</div>

		<!-- ─── Progress Log ────────────────────────────────────── -->
		<div id="rayai-log-area" class="rayai-card" style="display: none;">
			<div class="rayai-card-header" style="display:flex; justify-content:space-between; align-items:center;">
				<h2 style="margin:0;">
					<span class="spinner is-active" id="rayai-spinner" style="float: none; margin: 0 8px 0 0;"></span>
					<?php esc_html_e( 'Progress', 'rayai-content-orchestrator' ); ?>
				</h2>
				<button type="button" class="button button-small rayai-save-log" data-log="#rayai-log-box" title="<?php esc_attr_e( 'Download log as text file', 'rayai-content-orchestrator' ); ?>">
					<span class="dashicons dashicons-download" style="vertical-align:text-bottom; font-size:14px; width:14px; height:14px; margin-right:2px;"></span>
					<?php esc_html_e( 'Save Log', 'rayai-content-orchestrator' ); ?>
				</button>
			</div>
			<div class="rayai-card-body">
				<div id="rayai-log-box" class="rayai-log-box"></div>
			</div>
		</div>

		<!-- ─── Results ─────────────────────────────────────────── -->
		<div id="rayai-result-card" class="rayai-card rayai-result-card" style="display: none;">
			<div class="rayai-card-header rayai-card-header-success">
				<h2>
					<span class="dashicons dashicons-yes-alt" style="margin-right: 6px;"></span>
					<?php esc_html_e( 'Content Created Successfully', 'rayai-content-orchestrator' ); ?>
				</h2>
			</div>
			<div class="rayai-card-body">
				<table class="widefat striped" id="rayai-result-table">
					<tbody></tbody>
				</table>

				<div id="rayai-result-actions" style="margin-top: 16px;">
					<a id="rayai-view-scheduled" href="<?php echo esc_url( admin_url( 'admin.php?page=rayai-scheduled' ) ); ?>" class="button button-primary" style="display:none;">
						<span class="dashicons dashicons-calendar-alt" style="vertical-align: text-bottom; font-size: 16px; width: 16px; height: 16px; margin-right: 4px;"></span>
						<?php esc_html_e( 'View Scheduled', 'rayai-content-orchestrator' ); ?>
					</a>
					<a id="rayai-view-post" href="#" class="button button-primary" target="_blank">
						<span class="dashicons dashicons-external" style="vertical-align: text-bottom; font-size: 16px; width: 16px; height: 16px; margin-right: 4px;"></span>
						<?php esc_html_e( 'View Post', 'rayai-content-orchestrator' ); ?>
					</a>
					<a id="rayai-edit-post" href="#" class="button" target="_blank">
						<span class="dashicons dashicons-edit" style="vertical-align: text-bottom; font-size: 16px; width: 16px; height: 16px; margin-right: 4px;"></span>
						<?php esc_html_e( 'Edit in WordPress', 'rayai-content-orchestrator' ); ?>
					</a>
					<button type="button" id="rayai-toggle-preview" class="button">
						<span class="dashicons dashicons-visibility" style="vertical-align: text-bottom; font-size: 16px; width: 16px; height: 16px; margin-right: 4px;"></span>
						<?php esc_html_e( 'Toggle Preview', 'rayai-content-orchestrator' ); ?>
					</button>
				</div>

				<div id="rayai-preview" class="rayai-preview" style="display: none;"></div>
			</div>
		</div>
	</div>
</div>

<script>
// Toggle Thrive Architect warning based on Output Format selection.
jQuery(document).ready(function($) {
	var $format  = $('#rayai-output-format');
	var $warning = $('#rayai-thrive-warning');
	function updateWarning() {
		if ($format.val() === 'thrive') {
			$warning.show();
		} else {
			$warning.hide();
		}
	}
	$format.on('change', updateWarning);
	updateWarning();
});
</script>
