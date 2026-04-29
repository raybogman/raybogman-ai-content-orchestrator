<?php
/**
 * Main content creation page template.
 *
 * @package AI_Content_Creator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$is_configured  = AICC_Settings::is_configured();
$provider       = AICC_Settings::get_ai_provider();
$model          = AICC_Settings::get_active_model();
$has_yoast      = defined( 'WPSEO_VERSION' );
$categories     = AICC_Publisher::get_categories();
$saved_urls     = AICC_Settings::get_saved_urls();
$pdf_library    = AICC_PDF_Library::get_for_js();
$project_vision = AICC_Settings::get_project_vision();
?>
<div class="wrap aicc-wrap">
	<h1 class="wp-heading-inline">
		<span class="dashicons dashicons-edit-large aicc-heading-icon"></span>
		<?php esc_html_e( 'AI Content Orchestrator — Create Content', 'ai-content-orchestrator' ); ?>
	</h1>
	<p class="aicc-subtitle">
		<?php esc_html_e( 'AI-powered content creation with website scanning, SEO optimization, and Yoast SEO integration.', 'ai-content-orchestrator' ); ?>
	</p>

	<?php if ( ! $is_configured ) : ?>
		<div class="notice notice-error">
			<p>
				<strong><?php esc_html_e( 'API key not configured.', 'ai-content-orchestrator' ); ?></strong>
				<?php
				printf(
					/* translators: %1$s: opening link tag, %2$s: closing link tag */
					esc_html__( 'Please %1$sconfigure your API key in Settings%2$s before creating content.', 'ai-content-orchestrator' ),
					'<a href="' . esc_url( admin_url( 'admin.php?page=aicc-settings' ) ) . '">',
					'</a>'
				);
				?>
			</p>
		</div>
	<?php endif; ?>

	<!-- Status bar -->
	<div class="aicc-status-bar">
		<span class="aicc-status-item">
			<strong><?php esc_html_e( 'Provider:', 'ai-content-orchestrator' ); ?></strong>
			<?php echo esc_html( 'openai' === $provider ? 'OpenAI' : 'Claude' ); ?>
			(<?php echo esc_html( $model ); ?>)
		</span>
		<span class="aicc-status-item">
			<strong><?php esc_html_e( 'Yoast SEO:', 'ai-content-orchestrator' ); ?></strong>
			<?php if ( $has_yoast ) : ?>
				<span class="aicc-badge aicc-badge-success"><?php esc_html_e( 'Active', 'ai-content-orchestrator' ); ?></span>
			<?php else : ?>
				<span class="aicc-badge aicc-badge-warning"><?php esc_html_e( 'Not installed', 'ai-content-orchestrator' ); ?></span>
			<?php endif; ?>
		</span>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=aicc-settings' ) ); ?>" class="aicc-status-item aicc-status-link">
			<?php esc_html_e( 'Change Settings', 'ai-content-orchestrator' ); ?> &rarr;
		</a>
	</div>

	<?php if ( ! empty( $project_vision ) ) : ?>
		<div class="notice notice-info" style="margin: 16px 0;">
			<p>
				<span class="dashicons dashicons-lightbulb" style="color: #2271b1; vertical-align: text-bottom;"></span>
				<strong><?php esc_html_e( 'Project Vision active', 'ai-content-orchestrator' ); ?></strong> &mdash;
				<?php esc_html_e( 'Your custom writing instructions are active and will be applied to all generated content.', 'ai-content-orchestrator' ); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=aicc-settings' ) ); ?>"><?php esc_html_e( 'Edit', 'ai-content-orchestrator' ); ?></a>
			</p>
			<p class="description" style="margin-top: 0;">
				<em><?php echo esc_html( strlen( $project_vision ) > 200 ? substr( $project_vision, 0, 200 ) . '...' : $project_vision ); ?></em>
			</p>
		</div>
	<?php endif; ?>

	<div id="aicc-app">

		<!-- ─── Website Scanning ────────────────────────────────── -->
		<div class="aicc-card">
			<div class="aicc-card-header">
				<h2>
					<span class="dashicons dashicons-search" style="margin-right: 6px;"></span>
					<?php esc_html_e( 'Step 1: Website Scanning (optional)', 'ai-content-orchestrator' ); ?>
				</h2>
			</div>
			<div class="aicc-card-body">
				<p class="description" style="margin-bottom: 12px;">
					<?php esc_html_e( 'Enter a website URL to scan for context. The scanner will crawl sitemaps and internal links to extract content, headings, and metadata. This scanned data is used as context for the AI to generate relevant, on-brand content.', 'ai-content-orchestrator' ); ?>
				</p>
				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row">
								<label for="aicc-url"><?php esc_html_e( 'Website URL', 'ai-content-orchestrator' ); ?></label>
							</th>
							<td>
								<input type="<?php echo aicc_is_pro() ? 'text' : 'url'; ?>" id="aicc-url" class="large-text" placeholder="https://example.com" />
								<p class="description">
									<?php if ( aicc_is_pro() ) : ?>
										<?php esc_html_e( 'Separate multiple URLs with commas. Leave empty to skip scanning.', 'ai-content-orchestrator' ); ?>
									<?php else : ?>
										<?php esc_html_e( 'Enter one website URL to scan for context. Leave empty to skip scanning.', 'ai-content-orchestrator' ); ?>
										<span style="background:#E4405F;color:#fff;padding:1px 6px;border-radius:8px;font-size:10px;font-weight:600;">ENT</span> <?php esc_html_e( 'Multiple URLs', 'ai-content-orchestrator' ); ?>
									<?php endif; ?>
								</p>
								<p style="margin-top: 10px;">
									<label>
										<input type="checkbox" id="aicc-save-url" />
										<?php esc_html_e( 'Save URL for next time', 'ai-content-orchestrator' ); ?>
										<span class="description">&mdash; <?php esc_html_e( 'quickly reuse it later without re-typing', 'ai-content-orchestrator' ); ?></span>
									</label>
								</p>
							</td>
						</tr>
						<tr id="aicc-saved-urls-row" <?php echo empty( $saved_urls ) ? 'style="display:none;"' : ''; ?>>
							<th scope="row">
								<?php esc_html_e( 'Saved URLs', 'ai-content-orchestrator' ); ?>
							</th>
							<td>
								<div id="aicc-saved-urls-list" class="aicc-saved-urls">
									<?php foreach ( $saved_urls as $saved_url ) : ?>
										<span class="aicc-url-chip" data-url="<?php echo esc_attr( $saved_url ); ?>">
											<span class="aicc-url-chip-text"><?php echo esc_html( $saved_url ); ?></span>
											<button type="button" class="aicc-url-chip-remove" title="<?php esc_attr_e( 'Remove', 'ai-content-orchestrator' ); ?>">&times;</button>
										</span>
									<?php endforeach; ?>
								</div>
								<p class="description">
									<?php esc_html_e( 'Click a URL to use it. Click the × to remove it from the saved list.', 'ai-content-orchestrator' ); ?>
								</p>
							</td>
						</tr>
					</tbody>
						<!-- PDF Sources (Enterprise) -->
						<?php if ( aicc_is_pro() ) : ?>
						<tr>
							<th scope="row">
								<?php esc_html_e( 'PDF Sources', 'ai-content-orchestrator' ); ?>
							</th>
							<td>
								<div class="aicc-pdf-upload-area">
									<input type="file" id="aicc-pdf-file" accept=".pdf" style="display:none;" />
									<button type="button" id="aicc-pdf-upload-btn" class="button">
										<span class="dashicons dashicons-pdf" style="vertical-align: text-bottom; font-size: 16px; width: 16px; height: 16px; margin-right: 4px;"></span>
										<?php esc_html_e( 'Upload PDF', 'ai-content-orchestrator' ); ?>
									</button>
									<span id="aicc-pdf-upload-status" style="margin-left: 12px;"></span>
								</div>
								<p class="description" style="margin-top: 6px;">
									<?php
									printf(
										/* translators: %s: max upload size */
										esc_html__( 'Upload a PDF to use as context for content generation. Max file size: %s. Text will be extracted automatically.', 'ai-content-orchestrator' ),
										esc_html( AICC_PDF_Library::get_max_upload_size_formatted() )
									);
									?>
								</p>

								<?php if ( ! empty( $pdf_library ) ) : ?>
								<div id="aicc-pdf-library" class="aicc-pdf-library" style="margin-top: 12px;">
									<p class="description" style="margin-bottom: 8px;">
										<strong><?php esc_html_e( 'Saved PDFs — check to use as source:', 'ai-content-orchestrator' ); ?></strong>
									</p>
									<?php foreach ( $pdf_library as $pdf ) : ?>
										<div class="aicc-pdf-item" data-pdf-id="<?php echo esc_attr( $pdf['id'] ); ?>">
											<label class="aicc-pdf-label">
												<input type="checkbox" name="aicc-pdf-ids[]" value="<?php echo esc_attr( $pdf['id'] ); ?>" class="aicc-pdf-checkbox" />
												<span class="dashicons dashicons-pdf" style="color: #d63638; vertical-align: text-bottom;"></span>
												<strong><?php echo esc_html( $pdf['name'] ); ?></strong>
												<span class="description">
													&mdash; <?php echo esc_html( $pdf['upload_date'] ); ?>
													&middot; <?php echo esc_html( number_format( $pdf['text_length'] ) ); ?> chars
												</span>
											</label>
											<button type="button" class="aicc-pdf-delete-btn" data-pdf-id="<?php echo esc_attr( $pdf['id'] ); ?>" title="<?php esc_attr_e( 'Delete', 'ai-content-orchestrator' ); ?>">
												<span class="dashicons dashicons-trash" style="color: #d63638; font-size: 14px; width: 14px; height: 14px;"></span>
											</button>
											<div class="aicc-pdf-preview description"><?php echo esc_html( $pdf['text_preview'] ); ?></div>
										</div>
									<?php endforeach; ?>
								</div>
								<?php else : ?>
								<div id="aicc-pdf-library" class="aicc-pdf-library" style="margin-top: 12px; display: none;"></div>
								<?php endif; ?>
							</td>
						</tr>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>

		<!-- ─── Content Creation ────────────────────────────────── -->
		<div class="aicc-card">
			<div class="aicc-card-header">
				<h2>
					<span class="dashicons dashicons-welcome-write-blog" style="margin-right: 6px;"></span>
					<?php esc_html_e( 'Step 2: Content Creation', 'ai-content-orchestrator' ); ?>
				</h2>
			</div>
			<div class="aicc-card-body">
				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row">
								<label for="aicc-prompt"><?php esc_html_e( 'Prompt', 'ai-content-orchestrator' ); ?></label>
							</th>
							<td>
								<textarea id="aicc-prompt" class="large-text" rows="5" placeholder="<?php esc_attr_e( "Describe what content to create...\ne.g. Write a blog post about the top 10 SEO strategies for small businesses", 'ai-content-orchestrator' ); ?>"></textarea>
								<p class="description">
									<?php esc_html_e( 'Be specific about the topic, tone, and target audience. The AI will use this prompt along with any scanned website data.', 'ai-content-orchestrator' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Content Type', 'ai-content-orchestrator' ); ?></th>
							<td>
								<fieldset>
									<label>
										<input type="radio" name="aicc-type" value="blog" checked="checked" />
										<strong><?php esc_html_e( 'Blog Post', 'ai-content-orchestrator' ); ?></strong>
										<span class="description">&mdash; <?php esc_html_e( 'targets 1000-2000 words, includes tags and categories', 'ai-content-orchestrator' ); ?></span>
									</label>
									<br>
									<label>
										<input type="radio" name="aicc-type" value="page" />
										<strong><?php esc_html_e( 'Page', 'ai-content-orchestrator' ); ?></strong>
										<span class="description">&mdash; <?php esc_html_e( 'targets 500-1000 words, no categories or tags', 'ai-content-orchestrator' ); ?></span>
									</label>
								</fieldset>
							</td>
						</tr>
						<!-- Blog Style (visible for Blog Post only) -->
						<?php
						$styles          = AICC_Styles::get_styles();
						$style_previews  = AICC_Styles::get_short_previews();
						?>
						<tr id="aicc-style-row">
							<th scope="row"><?php esc_html_e( 'Blog Style', 'ai-content-orchestrator' ); ?></th>
							<td>
								<div style="display: flex; align-items: center; gap: 8px; position: relative;">
									<select id="aicc-blog-style" class="regular-text">
										<?php foreach ( $styles as $key => $style ) : ?>
											<option value="<?php echo esc_attr( $key ); ?>">
												<?php echo esc_html( $style['name'] ); ?> &mdash; <?php echo esc_html( $style['target_words'] ); ?> words
											</option>
										<?php endforeach; ?>
									</select>
									<span id="aicc-style-preview-trigger" class="dashicons dashicons-visibility" title="<?php esc_attr_e( 'Hover to preview this style', 'ai-content-orchestrator' ); ?>" style="cursor: help; color: #2271b1; font-size: 20px; width: 20px; height: 20px;"></span>

									<!-- Floating preview panel -->
									<div id="aicc-style-preview-panel" class="aicc-style-preview-panel" style="display: none;">
										<div class="aicc-style-preview-header">
											<strong id="aicc-style-preview-title"></strong>
											<a href="<?php echo esc_url( admin_url( 'admin.php?page=aicc-examples' ) ); ?>" target="_blank" style="font-size: 12px; margin-left: auto;">
												<?php esc_html_e( 'View all examples', 'ai-content-orchestrator' ); ?> &rarr;
											</a>
										</div>
										<div id="aicc-style-preview-content" class="aicc-preview" style="display: block; max-height: 350px;"></div>
									</div>
								</div>
								<p class="description" id="aicc-style-description">
									<?php echo esc_html( $styles['standard']['description'] ); ?>
								</p>

								<!-- Hidden preview data -->
								<?php foreach ( $style_previews as $key => $html ) : ?>
									<script type="text/html" id="aicc-style-preview-data-<?php echo esc_attr( $key ); ?>"><?php echo wp_kses_post( $html ); ?></script>
								<?php endforeach; ?>
							</td>
						</tr>

						<tr>
							<th scope="row"><?php esc_html_e( 'Publish Status', 'ai-content-orchestrator' ); ?></th>
							<td>
								<fieldset>
									<label>
										<input type="radio" name="aicc-status" value="draft" checked="checked" />
										<strong><?php esc_html_e( 'Draft', 'ai-content-orchestrator' ); ?></strong>
										<span class="description">&mdash; <?php esc_html_e( 'save for review before publishing', 'ai-content-orchestrator' ); ?></span>
									</label>
									<br>
									<label>
										<input type="radio" name="aicc-status" value="publish" />
										<strong><?php esc_html_e( 'Publish', 'ai-content-orchestrator' ); ?></strong>
										<span class="description">&mdash; <?php esc_html_e( 'publish immediately (or at scheduled time)', 'ai-content-orchestrator' ); ?></span>
									</label>
								</fieldset>
							</td>
						</tr>

						<tr>
							<th scope="row"><?php esc_html_e( 'Schedule', 'ai-content-orchestrator' ); ?></th>
							<td>
								<fieldset>
									<label>
										<input type="checkbox" id="aicc-schedule-enabled" />
										<strong><?php esc_html_e( 'Schedule for later', 'ai-content-orchestrator' ); ?></strong>
									</label>
									<div id="aicc-schedule-fields" style="display:none; margin-top: 10px;">
										<input type="datetime-local" id="aicc-schedule-at" class="regular-text" min="<?php echo esc_attr( wp_date( 'Y-m-d\TH:i', time() + 300 ) ); ?>" />
										<p class="description">
											<?php esc_html_e( 'Site time zone:', 'ai-content-orchestrator' ); ?>
											<code><?php echo esc_html( wp_timezone_string() ); ?></code>
											&middot;
											<?php esc_html_e( 'Current time:', 'ai-content-orchestrator' ); ?>
											<code><?php echo esc_html( wp_date( 'Y-m-d H:i' ) ); ?></code>
										</p>
										<p class="description" id="aicc-schedule-help-draft">
											<span class="dashicons dashicons-info" style="color:#2271b1;"></span>
											<?php esc_html_e( 'Draft + Schedule = Review before publishing. The post will appear in your Scheduled list for you to approve first.', 'ai-content-orchestrator' ); ?>
										</p>
										<p class="description" id="aicc-schedule-help-publish" style="display:none;">
											<span class="dashicons dashicons-info" style="color:#2271b1;"></span>
											<?php esc_html_e( 'Publish + Schedule = Direct scheduled publication. WordPress will publish it automatically at the scheduled time.', 'ai-content-orchestrator' ); ?>
										</p>
									</div>
								</fieldset>
							</td>
						</tr>

						<!-- LinkedIn (Enterprise) -->
						<?php if ( aicc_is_pro() && AICC_LinkedIn::is_connected() ) : ?>
							<?php $li_profile = AICC_LinkedIn::get_profile(); ?>
							<tr>
								<th scope="row"><?php esc_html_e( 'LinkedIn', 'ai-content-orchestrator' ); ?></th>
								<td>
									<fieldset>
										<label>
											<input type="checkbox" id="aicc-linkedin" />
											<strong><?php esc_html_e( 'Post to LinkedIn when published', 'ai-content-orchestrator' ); ?></strong>
										</label>
										<p class="description">
											<?php
											printf(
												/* translators: %s: LinkedIn account name */
												esc_html__( 'Will share to LinkedIn as %s when the content is published (immediately or after approval).', 'ai-content-orchestrator' ),
												'<strong>' . esc_html( $li_profile['name'] ) . '</strong>'
											);
											?>
										</p>
									</fieldset>
								</td>
							</tr>
						<?php endif; ?>

						<!-- Instagram (Enterprise) -->
						<?php if ( aicc_is_pro() && AICC_Instagram::is_connected() ) : ?>
							<?php $ig_profile = AICC_Instagram::get_profile(); ?>
							<tr>
								<th scope="row"><?php esc_html_e( 'Instagram', 'ai-content-orchestrator' ); ?></th>
								<td>
									<fieldset>
										<label>
											<input type="checkbox" id="aicc-instagram" />
											<strong><?php esc_html_e( 'Post to Instagram when published', 'ai-content-orchestrator' ); ?></strong>
										</label>
										<p class="description">
											<?php
											printf(
												esc_html__( 'Will share to Instagram as @%s with the featured image and an AI-generated caption.', 'ai-content-orchestrator' ),
												esc_html( $ig_profile['username'] ?? '' )
											);
											?>
										</p>
									</fieldset>
								</td>
							</tr>
						<?php endif; ?>

						<!-- Featured Image -->
						<?php
						$image_configured = AICC_Settings::is_image_configured();
						$image_provider   = AICC_Settings::get_image_provider();
						$image_labels     = array( 'openai' => 'OpenAI (DALL-E 3)', 'ideogram' => 'Ideogram' );
						$image_label      = isset( $image_labels[ $image_provider ] ) ? $image_labels[ $image_provider ] : $image_provider;
						?>
						<tr>
							<th scope="row"><?php esc_html_e( 'Featured Image', 'ai-content-orchestrator' ); ?></th>
							<td>
								<fieldset>
									<label>
										<input type="checkbox" id="aicc-generate-image" <?php echo $image_configured ? '' : 'disabled'; ?> />
										<strong><?php esc_html_e( 'Generate AI featured image', 'ai-content-orchestrator' ); ?></strong>
									</label>
									<p class="description">
										<?php if ( $image_configured ) : ?>
											<?php
											printf(
												/* translators: %s: image provider name (e.g. "OpenAI (DALL-E 3)" or "Ideogram") */
												esc_html__( 'Uses %s to generate a landscape image based on the blog topic. Set as the featured image — LinkedIn will automatically show this image when sharing.', 'ai-content-orchestrator' ),
												'<strong>' . esc_html( $image_label ) . '</strong>'
											);
											?>
										<?php else : ?>
											<span class="dashicons dashicons-warning" style="color: #dba617; vertical-align: text-bottom;"></span>
											<?php
											printf(
												/* translators: %s: image provider name */
												'<em>' . esc_html__( 'Requires an API key for %s. Configure it in Settings → Featured Image Provider section.', 'ai-content-orchestrator' ) . '</em>',
												esc_html( $image_label )
											);
											?>
										<?php endif; ?>
									</p>
								</fieldset>
							</td>
						</tr>

						<!-- SEO Enhancements -->
						<tr>
							<th scope="row"><?php esc_html_e( 'SEO Enhancements', 'ai-content-orchestrator' ); ?></th>
							<td>
								<fieldset>
									<label style="display:block; margin-bottom:6px;">
										<input type="checkbox" id="aicc-internal-linking" <?php echo AICC_Settings::is_internal_linking_enabled() ? 'checked' : ''; ?> />
										<strong><?php esc_html_e( 'Auto-add internal links', 'ai-content-orchestrator' ); ?></strong>
										<span class="description"> — <?php esc_html_e( 'links to your existing published posts for better SEO', 'ai-content-orchestrator' ); ?></span>
									</label>
									<?php if ( aicc_is_pro() ) : ?>
									<label style="display:block;">
										<input type="checkbox" id="aicc-competitor-analysis" <?php echo AICC_Settings::get_competitor_analysis_enabled() ? 'checked' : ''; ?> />
										<strong><?php esc_html_e( 'Analyze competitors first', 'ai-content-orchestrator' ); ?></strong>
										<span class="description"> — <?php esc_html_e( 'scans top Google results for your keyword and writes content that covers more topics', 'ai-content-orchestrator' ); ?></span>
									</label>
									<?php else : ?>
									<label style="display:block; opacity:0.5;">
										<input type="checkbox" disabled />
										<strong><?php esc_html_e( 'Analyze competitors first', 'ai-content-orchestrator' ); ?></strong>
										<span style="background:#E4405F;color:#fff;padding:1px 6px;border-radius:8px;font-size:10px;font-weight:600;">ENT</span>
									</label>
									<?php endif; ?>
								</fieldset>
							</td>
						</tr>

						<!-- Output Format -->
						<tr>
							<th scope="row"><?php esc_html_e( 'Output Format', 'ai-content-orchestrator' ); ?></th>
							<td>
								<fieldset>
									<?php $default_format = AICC_Settings::get_default_output_format(); ?>
									<select id="aicc-output-format" style="min-width:260px;">
										<option value="wordpress" <?php selected( $default_format, 'wordpress' ); ?>><?php esc_html_e( 'WordPress (Standard)', 'ai-content-orchestrator' ); ?></option>
										<?php if ( aicc_is_pro() ) : ?>
										<option value="thrive" <?php selected( $default_format, 'thrive' ); ?>><?php esc_html_e( 'Thrive Architect (compatible)', 'ai-content-orchestrator' ); ?></option>
									<?php else : ?>
										<option value="thrive" disabled><?php esc_html_e( 'Thrive Architect (Enterprise)', 'ai-content-orchestrator' ); ?></option>
									<?php endif; ?>
									</select>
									<p class="description" id="aicc-output-format-desc">
										<?php esc_html_e( 'Choose how the content is formatted. WordPress (Standard) works with any theme. Thrive Architect creates content that\'s fully editable in Thrive\'s visual editor.', 'ai-content-orchestrator' ); ?>
									</p>
									<p class="description" id="aicc-thrive-warning" style="display:none; color:#b26200; background:#fff8e5; padding:8px 12px; border-left:3px solid #dba617; margin-top:8px;">
										<span class="dashicons dashicons-info-outline" style="vertical-align:text-bottom;"></span>
										<strong><?php esc_html_e( 'About Thrive Architect mode:', 'ai-content-orchestrator' ); ?></strong>
										<?php esc_html_e( 'Each heading, paragraph, and list becomes its own editable block in Thrive. The first time you open the post in Thrive Architect, the editor may rearrange things slightly — this is normal. Advanced Thrive elements (buttons, forms, timers) need to be added manually inside Thrive\'s editor.', 'ai-content-orchestrator' ); ?>
									</p>
								</fieldset>
							</td>
						</tr>

						<!-- Categories (shown for Blog Post only) -->
						<tr id="aicc-categories-row">
							<th scope="row">
								<label for="aicc-categories"><?php esc_html_e( 'Categories', 'ai-content-orchestrator' ); ?></label>
							</th>
							<td>
								<div id="aicc-categories-list" class="aicc-checkbox-list">
									<?php if ( ! empty( $categories ) ) : ?>
										<?php foreach ( $categories as $cat ) : ?>
											<label class="aicc-checkbox-item">
												<input type="checkbox" name="aicc-categories[]" value="<?php echo esc_attr( $cat['id'] ); ?>" />
												<?php echo esc_html( $cat['name'] ); ?>
											</label>
										<?php endforeach; ?>
									<?php else : ?>
										<p class="description"><?php esc_html_e( 'No categories found. The AI will suggest and create categories automatically.', 'ai-content-orchestrator' ); ?></p>
									<?php endif; ?>
								</div>
								<p class="description">
									<?php esc_html_e( 'Select categories to assign. The AI may also suggest additional categories which will be created automatically.', 'ai-content-orchestrator' ); ?>
								</p>
							</td>
						</tr>
					</tbody>
				</table>

				<p class="submit">
					<button type="button" id="aicc-submit" class="button button-primary button-hero" <?php echo $is_configured ? '' : 'disabled'; ?>>
						<span class="dashicons dashicons-admin-post aicc-btn-icon"></span>
						<?php esc_html_e( 'Create Content', 'ai-content-orchestrator' ); ?>
					</button>
				</p>
			</div>
		</div>

		<!-- ─── Progress Log ────────────────────────────────────── -->
		<div id="aicc-log-area" class="aicc-card" style="display: none;">
			<div class="aicc-card-header" style="display:flex; justify-content:space-between; align-items:center;">
				<h2 style="margin:0;">
					<span class="spinner is-active" id="aicc-spinner" style="float: none; margin: 0 8px 0 0;"></span>
					<?php esc_html_e( 'Progress', 'ai-content-orchestrator' ); ?>
				</h2>
				<button type="button" class="button button-small aicc-save-log" data-log="#aicc-log-box" title="<?php esc_attr_e( 'Download log as text file', 'ai-content-orchestrator' ); ?>">
					<span class="dashicons dashicons-download" style="vertical-align:text-bottom; font-size:14px; width:14px; height:14px; margin-right:2px;"></span>
					<?php esc_html_e( 'Save Log', 'ai-content-orchestrator' ); ?>
				</button>
			</div>
			<div class="aicc-card-body">
				<div id="aicc-log-box" class="aicc-log-box"></div>
			</div>
		</div>

		<!-- ─── Results ─────────────────────────────────────────── -->
		<div id="aicc-result-card" class="aicc-card aicc-result-card" style="display: none;">
			<div class="aicc-card-header aicc-card-header-success">
				<h2>
					<span class="dashicons dashicons-yes-alt" style="margin-right: 6px;"></span>
					<?php esc_html_e( 'Content Created Successfully', 'ai-content-orchestrator' ); ?>
				</h2>
			</div>
			<div class="aicc-card-body">
				<table class="widefat striped" id="aicc-result-table">
					<tbody></tbody>
				</table>

				<div id="aicc-result-actions" style="margin-top: 16px;">
					<a id="aicc-view-scheduled" href="<?php echo esc_url( admin_url( 'admin.php?page=aicc-scheduled' ) ); ?>" class="button button-primary" style="display:none;">
						<span class="dashicons dashicons-calendar-alt" style="vertical-align: text-bottom; font-size: 16px; width: 16px; height: 16px; margin-right: 4px;"></span>
						<?php esc_html_e( 'View Scheduled', 'ai-content-orchestrator' ); ?>
					</a>
					<a id="aicc-view-post" href="#" class="button button-primary" target="_blank">
						<span class="dashicons dashicons-external" style="vertical-align: text-bottom; font-size: 16px; width: 16px; height: 16px; margin-right: 4px;"></span>
						<?php esc_html_e( 'View Post', 'ai-content-orchestrator' ); ?>
					</a>
					<a id="aicc-edit-post" href="#" class="button" target="_blank">
						<span class="dashicons dashicons-edit" style="vertical-align: text-bottom; font-size: 16px; width: 16px; height: 16px; margin-right: 4px;"></span>
						<?php esc_html_e( 'Edit in WordPress', 'ai-content-orchestrator' ); ?>
					</a>
					<button type="button" id="aicc-toggle-preview" class="button">
						<span class="dashicons dashicons-visibility" style="vertical-align: text-bottom; font-size: 16px; width: 16px; height: 16px; margin-right: 4px;"></span>
						<?php esc_html_e( 'Toggle Preview', 'ai-content-orchestrator' ); ?>
					</button>
				</div>

				<div id="aicc-preview" class="aicc-preview" style="display: none;"></div>
			</div>
		</div>
	</div>
</div>

<script>
// Toggle Thrive Architect warning based on Output Format selection.
jQuery(document).ready(function($) {
	var $format  = $('#aicc-output-format');
	var $warning = $('#aicc-thrive-warning');
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
