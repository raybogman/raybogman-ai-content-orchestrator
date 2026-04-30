<?php
/**
 * Bulk Create page template.
 *
 * @package RayAI_Content_Orchestrator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rayai_styles          = RAYAI_Styles::get_styles_for_js();
$rayai_li_connected    = RAYAI_LinkedIn::is_connected();
$rayai_img_configured  = RAYAI_Settings::is_image_configured();
$rayai_saved_urls      = RAYAI_Settings::get_saved_urls();
$rayai_categories      = RAYAI_Publisher::get_categories();
$rayai_default_format  = RAYAI_Settings::get_default_output_format();
?>
<div class="wrap rayai-wrap">
	<h1 class="wp-heading-inline">
		<span class="dashicons dashicons-admin-page rayai-heading-icon"></span>
		<?php esc_html_e( 'RayAI – Content Orchestrator — Bulk Create', 'rayai-content-orchestrator' ); ?>
	</h1>
	<p class="rayai-subtitle">
		<?php esc_html_e( 'Create multiple blog posts at once. Enter your topics, pick a style and schedule, and generate them all in one go.', 'rayai-content-orchestrator' ); ?>
	</p>

	<!-- ─── Seed Keyword / Suggest Topics ──────────────────── -->
	<div class="rayai-card">
		<div class="rayai-card-header">
			<h2>
				<span class="dashicons dashicons-lightbulb" style="margin-right: 6px;"></span>
				<?php esc_html_e( 'Topic Ideas (optional)', 'rayai-content-orchestrator' ); ?>
			</h2>
		</div>
		<div class="rayai-card-body">
			<p class="description" style="margin-bottom: 12px;">
				<?php esc_html_e( 'Enter a seed keyword and let AI suggest topics for your batch. You can also fill in topics manually below.', 'rayai-content-orchestrator' ); ?>
			</p>
			<div style="display: flex; align-items: center; gap: 8px;">
				<input type="text" id="rayai-bulk-seed" class="regular-text" placeholder="<?php esc_attr_e( 'e.g. sustainable fashion', 'rayai-content-orchestrator' ); ?>" />
				<button type="button" id="rayai-bulk-suggest" class="button">
					<span class="dashicons dashicons-update" style="vertical-align: text-bottom; font-size: 16px; width: 16px; height: 16px; margin-right: 4px;"></span>
					<?php esc_html_e( 'Suggest Topics', 'rayai-content-orchestrator' ); ?>
				</button>
			</div>
			<div id="rayai-bulk-suggest-progress" style="display: none; margin-top: 12px; padding: 10px 14px; background: #f0f6fc; border-left: 3px solid #2271b1; border-radius: 3px;">
				<span class="spinner is-active" style="float: none; margin: 0 8px 0 0; vertical-align: middle;"></span>
				<span id="rayai-bulk-suggest-status"><?php esc_html_e( 'Asking AI to generate topic ideas...', 'rayai-content-orchestrator' ); ?></span>
			</div>
		</div>
	</div>

	<!-- ─── Topics Table ───────────────────────────────────── -->
	<div class="rayai-card">
		<div class="rayai-card-header">
			<h2>
				<span class="dashicons dashicons-list-view" style="margin-right: 6px;"></span>
				<?php esc_html_e( 'Topics', 'rayai-content-orchestrator' ); ?>
			</h2>
		</div>
		<div class="rayai-card-body">
			<?php
			$rayai_sched_freq = RAYAI_Settings::get_schedule_frequency();
			$rayai_sched_time = RAYAI_Settings::get_schedule_time();
			$rayai_sched_skip = RAYAI_Settings::get_schedule_skip_weekends();
			?>
			<div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:12px; padding:10px 14px; background:#f6f7f7; border:1px solid #e0e0e0; border-radius:4px;">
				<label style="font-weight:600; margin-right:4px;">
					<span class="dashicons dashicons-calendar-alt" style="vertical-align:text-bottom; margin-right:2px;"></span>
					<?php esc_html_e( 'Auto-fill Dates:', 'rayai-content-orchestrator' ); ?>
				</label>
				<select id="rayai-schedule-freq" style="min-width:140px;">
					<option value="none" <?php selected( $rayai_sched_freq, 'none' ); ?>><?php esc_html_e( 'Manual', 'rayai-content-orchestrator' ); ?></option>
					<option value="daily" <?php selected( $rayai_sched_freq, 'daily' ); ?>><?php esc_html_e( 'Daily', 'rayai-content-orchestrator' ); ?></option>
					<option value="every2" <?php selected( $rayai_sched_freq, 'every2' ); ?>><?php esc_html_e( 'Every 2 days', 'rayai-content-orchestrator' ); ?></option>
					<option value="every3" <?php selected( $rayai_sched_freq, 'every3' ); ?>><?php esc_html_e( 'Every 3 days', 'rayai-content-orchestrator' ); ?></option>
					<option value="weekly" <?php selected( $rayai_sched_freq, 'weekly' ); ?>><?php esc_html_e( 'Weekly', 'rayai-content-orchestrator' ); ?></option>
					<option value="biweekly" <?php selected( $rayai_sched_freq, 'biweekly' ); ?>><?php esc_html_e( 'Bi-weekly', 'rayai-content-orchestrator' ); ?></option>
					<option value="monthly" <?php selected( $rayai_sched_freq, 'monthly' ); ?>><?php esc_html_e( 'Monthly', 'rayai-content-orchestrator' ); ?></option>
				</select>
				<label><?php esc_html_e( 'at', 'rayai-content-orchestrator' ); ?></label>
				<input type="time" id="rayai-schedule-time" value="<?php echo esc_attr( $rayai_sched_time ); ?>" style="width:100px;" />
				<label style="margin-left:8px;">
					<input type="checkbox" id="rayai-schedule-skip-weekends" <?php checked( $rayai_sched_skip ); ?> />
					<?php esc_html_e( 'Skip weekends', 'rayai-content-orchestrator' ); ?>
				</label>
				<button type="button" id="rayai-autofill-dates" class="button" style="margin-left:auto;">
					<span class="dashicons dashicons-calendar-alt" style="vertical-align:text-bottom; font-size:14px; width:14px; height:14px; margin-right:2px;"></span>
					<?php esc_html_e( 'Fill Dates', 'rayai-content-orchestrator' ); ?>
				</button>
			</div>

			<table class="widefat striped" id="rayai-bulk-table">
				<thead>
					<tr>
						<th style="width: 40px;">
							<input type="checkbox" id="rayai-bulk-check-all" title="<?php esc_attr_e( 'Select all', 'rayai-content-orchestrator' ); ?>" />
						</th>
						<th><?php esc_html_e( 'Topic', 'rayai-content-orchestrator' ); ?></th>
						<th style="width: 220px;"><?php esc_html_e( 'Blog Style', 'rayai-content-orchestrator' ); ?></th>
						<th style="width: 170px;"><?php esc_html_e( 'Publish Date', 'rayai-content-orchestrator' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php for ( $rayai_i = 1; $rayai_i <= 5; $rayai_i++ ) : ?>
						<tr class="rayai-bulk-row">
							<td><input type="checkbox" class="rayai-bulk-check" checked /></td>
							<td><input type="text" class="large-text rayai-bulk-topic" placeholder="<?php esc_attr_e( 'Enter topic...', 'rayai-content-orchestrator' ); ?>" /></td>
							<td>
								<select class="rayai-bulk-style">
									<?php foreach ( $rayai_styles as $rayai_style ) : ?>
										<option value="<?php echo esc_attr( $style['key'] ); ?>">
											<?php echo esc_html( $style['name'] ); ?> &mdash; <?php echo esc_html( $style['target_words'] ); ?> words
										</option>
									<?php endforeach; ?>
								</select>
							</td>
							<td><input type="date" class="rayai-bulk-date" /></td>
						</tr>
					<?php endfor; ?>
				</tbody>
			</table>
			<p style="margin-top: 12px;">
				<button type="button" id="rayai-bulk-add-row" class="button">
					<span class="dashicons dashicons-plus-alt2" style="vertical-align: text-bottom; font-size: 16px; width: 16px; height: 16px; margin-right: 4px;"></span>
					<?php esc_html_e( 'Add Row', 'rayai-content-orchestrator' ); ?>
				</button>
			</p>
		</div>
	</div>

	<!-- ─── Shared Settings ────────────────────────────────── -->
	<div class="rayai-card">
		<div class="rayai-card-header">
			<h2>
				<span class="dashicons dashicons-admin-generic" style="margin-right: 6px;"></span>
				<?php esc_html_e( 'Shared Settings', 'rayai-content-orchestrator' ); ?>
			</h2>
		</div>
		<div class="rayai-card-body">
			<table class="form-table" role="presentation">
				<tbody>
					<!-- URL to Scan -->
					<tr>
						<th scope="row">
							<label for="rayai-bulk-url"><?php esc_html_e( 'URL to Scan', 'rayai-content-orchestrator' ); ?></label>
						</th>
						<td>
							<input type="text" id="rayai-bulk-url" class="large-text" placeholder="https://example.com" />
							<p class="description">
								<?php esc_html_e( 'Separate multiple URLs with commas. The same URLs are used for all posts in this batch. Leave empty to skip scanning.', 'rayai-content-orchestrator' ); ?>
							</p>
							<p style="margin-top: 10px;">
								<label>
									<input type="checkbox" id="rayai-bulk-save-url" />
									<?php esc_html_e( 'Save URL for next time', 'rayai-content-orchestrator' ); ?>
									<span class="description">&mdash; <?php esc_html_e( 'quickly reuse it later without re-typing', 'rayai-content-orchestrator' ); ?></span>
								</label>
							</p>
						</td>
					</tr>
					<?php if ( ! empty( $rayai_saved_urls ) ) : ?>
					<tr id="rayai-bulk-saved-urls-row">
						<th scope="row">
							<?php esc_html_e( 'Saved URLs', 'rayai-content-orchestrator' ); ?>
						</th>
						<td>
							<div id="rayai-bulk-saved-urls-list" class="rayai-saved-urls">
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
					<?php endif; ?>

					<!-- Publish Status -->
					<tr>
						<th scope="row">
							<label for="rayai-bulk-status"><?php esc_html_e( 'Publish Status', 'rayai-content-orchestrator' ); ?></label>
						</th>
						<td>
							<select id="rayai-bulk-status">
								<option value="draft"><?php esc_html_e( 'Draft', 'rayai-content-orchestrator' ); ?></option>
								<option value="publish"><?php esc_html_e( 'Publish', 'rayai-content-orchestrator' ); ?></option>
							</select>
						</td>
					</tr>

					<!-- Featured Image -->
					<tr>
						<th scope="row"><?php esc_html_e( 'Featured Image', 'rayai-content-orchestrator' ); ?></th>
						<td>
							<label>
								<input type="checkbox" id="rayai-bulk-image" <?php echo $rayai_img_configured ? '' : 'disabled'; ?> />
								<strong><?php esc_html_e( 'Generate AI featured image for each post', 'rayai-content-orchestrator' ); ?></strong>
							</label>
							<?php if ( ! $rayai_img_configured ) : ?>
								<p class="description">
									<span class="dashicons dashicons-warning" style="color: #dba617; vertical-align: text-bottom;"></span>
									<em><?php esc_html_e( 'Requires an image provider API key. Configure it in Settings.', 'rayai-content-orchestrator' ); ?></em>
								</p>
							<?php endif; ?>
						</td>
					</tr>

					<?php if ( $rayai_li_connected ) : ?>
					<!-- LinkedIn -->
					<tr>
						<th scope="row"><?php esc_html_e( 'LinkedIn', 'rayai-content-orchestrator' ); ?></th>
						<td>
							<label>
								<input type="checkbox" id="rayai-bulk-linkedin" />
								<strong><?php esc_html_e( 'Share each post to LinkedIn when published', 'rayai-content-orchestrator' ); ?></strong>
							</label>
						</td>
					</tr>
					<?php endif; ?>

					<?php if ( RAYAI_Instagram::is_connected() ) : ?>
					<!-- Instagram -->
					<tr>
						<th scope="row"><?php esc_html_e( 'Instagram', 'rayai-content-orchestrator' ); ?></th>
						<td>
							<label>
								<input type="checkbox" id="rayai-bulk-instagram" />
								<strong><?php esc_html_e( 'Share each post to Instagram when published', 'rayai-content-orchestrator' ); ?></strong>
							</label>
						</td>
					</tr>
					<?php endif; ?>

					<!-- SEO Enhancements -->
					<tr>
						<th scope="row"><?php esc_html_e( 'SEO Enhancements', 'rayai-content-orchestrator' ); ?></th>
						<td>
							<fieldset>
								<label style="display:block; margin-bottom:6px;">
									<input type="checkbox" id="rayai-bulk-internal-linking" <?php echo RAYAI_Settings::is_internal_linking_enabled() ? 'checked' : ''; ?> />
									<strong><?php esc_html_e( 'Auto-add internal links', 'rayai-content-orchestrator' ); ?></strong>
									<span class="description"> — <?php esc_html_e( 'links to your existing published posts for better SEO', 'rayai-content-orchestrator' ); ?></span>
								</label>
								<label style="display:block;">
									<input type="checkbox" id="rayai-bulk-competitor-analysis" <?php echo RAYAI_Settings::get_competitor_analysis_enabled() ? 'checked' : ''; ?> />
									<strong><?php esc_html_e( 'Analyze competitors first', 'rayai-content-orchestrator' ); ?></strong>
									<span class="description"> — <?php esc_html_e( 'scans top Google results for your keyword and writes content that covers more topics', 'rayai-content-orchestrator' ); ?></span>
								</label>
							</fieldset>
						</td>
					</tr>

					<!-- Output Format -->
					<tr>
						<th scope="row"><?php esc_html_e( 'Output Format', 'rayai-content-orchestrator' ); ?></th>
						<td>
							<select id="rayai-bulk-output-format" style="min-width:260px;">
								<option value="wordpress" <?php selected( $rayai_default_format, 'wordpress' ); ?>><?php esc_html_e( 'WordPress (Standard)', 'rayai-content-orchestrator' ); ?></option>
								<option value="thrive" <?php selected( $rayai_default_format, 'thrive' ); ?>><?php esc_html_e( 'Thrive Architect (compatible)', 'rayai-content-orchestrator' ); ?></option>
							</select>
						</td>
					</tr>

					<!-- Categories -->
					<tr>
						<th scope="row"><?php esc_html_e( 'Categories', 'rayai-content-orchestrator' ); ?></th>
						<td>
							<div class="rayai-checkbox-list">
								<?php if ( ! empty( $rayai_categories ) ) : ?>
									<?php foreach ( $rayai_categories as $cat ) : ?>
										<label class="rayai-checkbox-item">
											<input type="checkbox" name="rayai-bulk-categories[]" value="<?php echo esc_attr( $cat['id'] ); ?>" />
											<?php echo esc_html( $cat['name'] ); ?>
										</label>
									<?php endforeach; ?>
								<?php else : ?>
									<p class="description"><?php esc_html_e( 'No categories found. The AI will suggest and create categories automatically.', 'rayai-content-orchestrator' ); ?></p>
								<?php endif; ?>
							</div>
							<p class="description" style="margin-top: 6px;">
								<?php esc_html_e( 'Select categories for all posts, or leave empty to let the AI decide.', 'rayai-content-orchestrator' ); ?>
							</p>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>

	<!-- ─── Generate Button ────────────────────────────────── -->
	<p class="submit">
		<button type="button" id="rayai-bulk-generate" class="button button-primary button-hero">
			<span class="dashicons dashicons-admin-post rayai-btn-icon"></span>
			<?php esc_html_e( 'Generate All (0 posts)', 'rayai-content-orchestrator' ); ?>
		</button>
	</p>

	<!-- ─── Progress Area ──────────────────────────────────── -->
	<div id="rayai-bulk-progress" class="rayai-card" style="display: none;">
		<div class="rayai-card-header" style="display:flex; justify-content:space-between; align-items:center;">
			<h2 style="margin:0;">
				<span class="spinner is-active" id="rayai-bulk-spinner" style="float: none; margin: 0 8px 0 0;"></span>
				<?php esc_html_e( 'Generating...', 'rayai-content-orchestrator' ); ?>
			</h2>
			<button type="button" class="button button-small rayai-save-log" data-log="#rayai-bulk-progress-log" title="<?php esc_attr_e( 'Download log as text file', 'rayai-content-orchestrator' ); ?>">
				<span class="dashicons dashicons-download" style="vertical-align:text-bottom; font-size:14px; width:14px; height:14px; margin-right:2px;"></span>
				<?php esc_html_e( 'Save Log', 'rayai-content-orchestrator' ); ?>
			</button>
		</div>
		<div class="rayai-card-body">
			<div id="rayai-bulk-progress-log" class="rayai-log-box"></div>
		</div>
	</div>

	<!-- ─── Results Area ───────────────────────────────────── -->
	<div id="rayai-bulk-results" class="rayai-card rayai-result-card" style="display: none;">
		<div class="rayai-card-header rayai-card-header-success">
			<h2 id="rayai-bulk-results-header">
				<span class="dashicons dashicons-yes-alt" style="margin-right: 6px;"></span>
				<?php esc_html_e( 'Created Posts', 'rayai-content-orchestrator' ); ?>
			</h2>
		</div>
		<div class="rayai-card-body">
			<table class="widefat striped" id="rayai-bulk-results-table">
				<thead>
					<tr>
						<th style="width:55%;"><?php esc_html_e( 'Title', 'rayai-content-orchestrator' ); ?></th>
						<th style="width:12%;"><?php esc_html_e( 'Status', 'rayai-content-orchestrator' ); ?></th>
						<th style="width:33%;"><?php esc_html_e( 'Actions', 'rayai-content-orchestrator' ); ?></th>
					</tr>
				</thead>
				<tbody id="rayai-bulk-results-body"></tbody>
			</table>
		</div>
	</div>
</div>

<script>
jQuery(document).ready(function($) {

	/**
	 * Build a new row HTML string using the styles dropdown already in the DOM.
	 */
	function buildRow() {
		var styleOptions = $('#rayai-bulk-table tbody tr:first .rayai-bulk-style').html();
		return '<tr class="rayai-bulk-row">' +
			'<td><input type="checkbox" class="rayai-bulk-check" checked /></td>' +
			'<td><input type="text" class="large-text rayai-bulk-topic" placeholder="<?php echo esc_js( __( 'Enter topic...', 'rayai-content-orchestrator' ) ); ?>" /></td>' +
			'<td><select class="rayai-bulk-style">' + styleOptions + '</select></td>' +
			'<td><input type="date" class="rayai-bulk-date" /></td>' +
			'</tr>';
	}

	/**
	 * Update the generate button label with the checked row count.
	 */
	function updateCount() {
		var count = $('#rayai-bulk-table .rayai-bulk-check:checked').length;
		$('#rayai-bulk-generate').html(
			'<span class="dashicons dashicons-admin-post rayai-btn-icon"></span> ' +
			'<?php echo esc_js( __( 'Generate All', 'rayai-content-orchestrator' ) ); ?>' +
			' (' + count + ' ' + (count === 1 ? '<?php echo esc_js( __( 'post', 'rayai-content-orchestrator' ) ); ?>' : '<?php echo esc_js( __( 'posts', 'rayai-content-orchestrator' ) ); ?>') + ')'
		);
	}

	// Auto-fill Dates button.
	$('#rayai-autofill-dates').on('click', function() {
		var freq = $('#rayai-schedule-freq').val();
		if (freq === 'none') {
			alert('<?php echo esc_js( __( 'Select a frequency first (Daily, Weekly, etc.).', 'rayai-content-orchestrator' ) ); ?>');
			return;
		}

		var time = $('#rayai-schedule-time').val() || '09:00';
		var skipWeekends = $('#rayai-schedule-skip-weekends').is(':checked');

		var intervalDays = { daily: 1, every2: 2, every3: 3, weekly: 7, biweekly: 14, monthly: 30 };
		var days = intervalDays[freq] || 1;

		// Start from tomorrow.
		var current = new Date();
		current.setDate(current.getDate() + 1);
		current.setHours(parseInt(time.split(':')[0], 10), parseInt(time.split(':')[1], 10), 0, 0);

		// Skip to next weekday if needed.
		function nextWeekday(d) {
			while (d.getDay() === 0 || d.getDay() === 6) {
				d.setDate(d.getDate() + 1);
			}
			return d;
		}

		if (skipWeekends) current = nextWeekday(current);

		// Fill checked rows.
		var $rows = $('#rayai-bulk-table .rayai-bulk-row').filter(function() {
			return $(this).find('.rayai-bulk-check').is(':checked');
		});

		$rows.each(function() {
			var pad = function(n) { return n < 10 ? '0' + n : n; };
			var dateStr = current.getFullYear() + '-' + pad(current.getMonth() + 1) + '-' + pad(current.getDate());
			$(this).find('.rayai-bulk-date').val(dateStr);

			// Advance to next date.
			if (freq === 'monthly') {
				current.setMonth(current.getMonth() + 1);
			} else {
				current.setDate(current.getDate() + days);
			}
			if (skipWeekends) current = nextWeekday(current);
		});
	});

	// Add Row button.
	$('#rayai-bulk-add-row').on('click', function() {
		$('#rayai-bulk-table tbody').append(buildRow());
		updateCount();
	});

	// Check-all toggle.
	$('#rayai-bulk-check-all').on('change', function() {
		$('#rayai-bulk-table .rayai-bulk-check').prop('checked', this.checked);
		updateCount();
	});

	// Individual checkbox change.
	$('#rayai-bulk-table').on('change', '.rayai-bulk-check', function() {
		updateCount();
	});

	// Toggle detail panel via Details button.
	$('#rayai-bulk-results-table').on('click', '.rayai-detail-toggle', function() {
		var $btn     = $(this);
		var detailId = $btn.data('detail');
		var $content = $('#' + detailId + ' .rayai-detail-content');
		if ($content.is(':visible')) {
			$content.slideUp(200);
			$btn.removeClass('button-primary');
		} else {
			$content.slideDown(200);
			$btn.addClass('button-primary');
		}
	});

	// Saved URL chip click — add to URL field (append with comma if field has value).
	$('#rayai-bulk-saved-urls-list').on('click', '.rayai-url-chip-text', function() {
		var url = $(this).parent().data('url');
		var $field = $('#rayai-bulk-url');
		var current = $.trim($field.val());
		if (current && current.indexOf(url) === -1) {
			$field.val(current + ', ' + url);
		} else if (!current) {
			$field.val(url);
		}
	});

	// Saved URL chip remove.
	$('#rayai-bulk-saved-urls-list').on('click', '.rayai-url-chip-remove', function() {
		var $chip = $(this).parent();
		var url   = $chip.data('url');
		$.post(ajaxurl, {
			action: 'rayai_remove_saved_url',
			nonce:  rayai.nonce,
			url:    url
		});
		$chip.fadeOut(200, function() { $(this).remove(); });
	});

	// Enter key in seed input triggers Suggest Topics.
	$('#rayai-bulk-seed').on('keypress', function(e) {
		if (e.which === 13) {
			e.preventDefault();
			$('#rayai-bulk-suggest').trigger('click');
		}
	});

	// Suggest Topics button.
	$('#rayai-bulk-suggest').on('click', function() {
		var seed = $('#rayai-bulk-seed').val().trim();
		if (!seed) {
			alert('<?php echo esc_js( __( 'Enter a seed keyword first.', 'rayai-content-orchestrator' ) ); ?>');
			return;
		}

		var $btn = $(this);
		var $progress = $('#rayai-bulk-suggest-progress');
		var $status = $('#rayai-bulk-suggest-status');
		var originalHtml = $btn.html();

		$btn.prop('disabled', true).html(
			'<span class="dashicons dashicons-update" style="vertical-align: text-bottom; font-size: 16px; width: 16px; height: 16px; margin-right: 4px; animation: rotation 1s linear infinite;"></span> ' +
			'<?php echo esc_js( __( 'Generating...', 'rayai-content-orchestrator' ) ); ?>'
		);
		$status.text('Asking AI to generate topic ideas for \u201C' + seed + '\u201D...');
		$progress.slideDown(200);

		// Count empty + checked rows to request the right number of topics.
		var $checkedEmpty = $('#rayai-bulk-table .rayai-bulk-row').filter(function() {
			return $(this).find('.rayai-bulk-check').is(':checked') && !$(this).find('.rayai-bulk-topic').val().trim();
		});
		var count = $checkedEmpty.length;
		if (count === 0) {
			alert('<?php echo esc_js( __( 'No empty checked rows available. Check the rows you want to fill, then click Suggest Topics.', 'rayai-content-orchestrator' ) ); ?>');
			$btn.prop('disabled', false).html(originalHtml);
			$progress.slideUp(200);
			return;
		}

		$.post(ajaxurl, {
			action: 'rayai_suggest_topics',
			nonce:  rayai.nonce,
			seed:   seed,
			count:  count
		}).done(function(res) {
			if (res.success && res.data.topics) {
				var topics = res.data.topics;

				// Fill only checked empty rows.
				var $emptyRows = $('#rayai-bulk-table .rayai-bulk-row').filter(function() {
					return $(this).find('.rayai-bulk-check').is(':checked') && !$(this).find('.rayai-bulk-topic').val().trim();
				});
				var filled = 0;
				$emptyRows.each(function() {
					if (filled < topics.length) {
						var t = topics[filled];
						$(this).find('.rayai-bulk-topic').val(t.title || t);
						if (t.style) {
							$(this).find('.rayai-bulk-style').val(t.style);
						}
						filled++;
					}
				});
				updateCount();
				$status.html('<span class="dashicons dashicons-yes-alt" style="color:#00a32a; vertical-align:text-bottom;"></span> ' +
					'<?php echo esc_js( __( 'Done! ', 'rayai-content-orchestrator' ) ); ?>' + topics.length + ' <?php echo esc_js( __( 'topics added to the table.', 'rayai-content-orchestrator' ) ); ?>');
				$progress.find('.spinner').removeClass('is-active');
				setTimeout(function() { $progress.slideUp(300); }, 4000);
			} else {
				var msg = res.data && res.data.message ? res.data.message : '<?php echo esc_js( __( 'Failed to generate topics.', 'rayai-content-orchestrator' ) ); ?>';
				$status.html('<span class="dashicons dashicons-warning" style="color:#d63638; vertical-align:text-bottom;"></span> ' + msg);
				$progress.find('.spinner').removeClass('is-active');
				setTimeout(function() { $progress.slideUp(300); }, 5000);
			}
		}).fail(function() {
			$status.html('<span class="dashicons dashicons-warning" style="color:#d63638; vertical-align:text-bottom;"></span> <?php echo esc_js( __( 'Request failed. Please try again.', 'rayai-content-orchestrator' ) ); ?>');
			$progress.find('.spinner').removeClass('is-active');
			setTimeout(function() { $progress.slideUp(300); }, 5000);
		}).always(function() {
			$btn.prop('disabled', false).html(originalHtml);
		});
	});

	// ── Generate All — bulk pipeline ─────────────────────
	var bulkRunning = false;

	// Warn before navigating away during generation.
	$(window).on('beforeunload', function() {
		if (bulkRunning) {
			return '<?php echo esc_js( __( 'Bulk generation is still running. If you leave, remaining posts won\'t be created. Are you sure?', 'rayai-content-orchestrator' ) ); ?>';
		}
	});

	function bulkLog(msg, cls) {
		var $log = $('#rayai-bulk-progress-log');
		var $line = $('<div class="rayai-log-line"></div>').text(msg);
		if (cls) $line.addClass(cls);
		$log.append($line);
		$log.scrollTop($log[0].scrollHeight);
	}

	function runBulkStep(jobId, step, job, callback) {
		var postData = { action: 'rayai_create_content', nonce: rayai.nonce, step: step };
		if (jobId) postData.job_id = jobId;
		if (step === 1) {
			postData.content_type     = 'blog';
			postData.url              = $('#rayai-bulk-url').val() || '';
			postData.prompt           = job.topic;
			postData.status           = $('#rayai-bulk-status').val();
			postData.blog_style       = job.style || 'standard';
			postData.save_url         = job._saveUrl ? '1' : '0';
			postData.linkedin         = $('#rayai-bulk-linkedin').is(':checked') ? '1' : '0';
			postData.instagram        = $('#rayai-bulk-instagram').is(':checked') ? '1' : '0';
			postData.generate_image   = $('#rayai-bulk-image').is(':checked') ? '1' : '0';
			postData.internal_linking = $('#rayai-bulk-internal-linking').is(':checked') ? '1' : '0';
			postData.competitor_analysis = $('#rayai-bulk-competitor-analysis').is(':checked') ? '1' : '0';
			postData.output_format    = $('#rayai-bulk-output-format').val() || 'wordpress';
			if (job.date) postData.schedule_at = job.date;
			var cats = [];
			$('input[name="rayai-bulk-categories[]"]:checked').each(function() { cats.push($(this).val()); });
			if (cats.length) postData['categories[]'] = cats;
		}
		$.post(ajaxurl, postData).done(function(res) {
			if (res.success) {
				if (res.data.log) {
					$.each(res.data.log, function(_, m) { bulkLog(m); });
				}
				if (res.data.next_step) {
					runBulkStep(res.data.job_id, res.data.next_step, job, callback);
				} else {
					callback(true, res.data);
				}
			} else {
				var errMsg = res.data && res.data.message ? res.data.message : 'Unknown error';
				bulkLog('ERROR: ' + errMsg, 'error');
				callback(false, { error: errMsg });
			}
		}).fail(function() {
			bulkLog('ERROR: Request failed — server timeout or connection issue.', 'error');
			callback(false, { error: 'Request failed' });
		});
	}

	$('#rayai-bulk-generate').on('click', function() {
		if (bulkRunning) return;

		// Collect checked rows with topics.
		var jobs = [];
		$('#rayai-bulk-table .rayai-bulk-row').each(function() {
			var $row  = $(this);
			if (!$row.find('.rayai-bulk-check').is(':checked')) return;
			var topic = $row.find('.rayai-bulk-topic').val().trim();
			if (!topic) return;
			jobs.push({
				topic: topic,
				style: $row.find('.rayai-bulk-style').val(),
				date:  $row.find('.rayai-bulk-date').val()
			});
		});

		if (!jobs.length) {
			alert('<?php echo esc_js( __( 'No topics to generate. Enter at least one topic and make sure the row is checked.', 'rayai-content-orchestrator' ) ); ?>');
			return;
		}

		// Only save URL on the first job to avoid duplicates.
		if ($('#rayai-bulk-save-url:checked').length && jobs.length) {
			jobs[0]._saveUrl = true;
		}

		bulkRunning = true;
		var $btn = $(this);
		$btn.prop('disabled', true);
		$('#rayai-bulk-progress-log').empty();
		$('#rayai-bulk-progress').slideDown(200, function() {
			$('html, body').animate({ scrollTop: $('#rayai-bulk-progress').offset().top - 40 }, 300);
		});
		$('#rayai-bulk-results-body').empty();
		$('#rayai-bulk-results-header').html(
			'<span class="spinner is-active" style="float:none; margin:0 8px 0 0;"></span> ' +
			'<?php echo esc_js( __( 'Created Posts', 'rayai-content-orchestrator' ) ); ?> (0/' + jobs.length + ')'
		);
		$('#rayai-bulk-results').slideDown(200);

		var completed = 0;
		var succeeded = 0;
		var total = jobs.length;

		function updateResultsHeader() {
			var icon = completed < total
				? '<span class="spinner is-active" style="float:none; margin:0 8px 0 0;"></span> '
				: '<span class="dashicons dashicons-yes-alt" style="color:#00a32a; margin-right:6px;"></span> ';
			var label = completed < total
				? '<?php echo esc_js( __( 'Created Posts', 'rayai-content-orchestrator' ) ); ?>'
				: '<?php echo esc_js( __( 'Bulk Generation Complete', 'rayai-content-orchestrator' ) ); ?>';
			$('#rayai-bulk-results-header').html(icon + label + ' (' + succeeded + '/' + total + ')');
		}

		function processNext(index) {
			if (index >= total) {
				$('#rayai-bulk-spinner').removeClass('is-active');
				$('#rayai-bulk-progress .rayai-card-header h2').html(
					'<span class="dashicons dashicons-yes-alt" style="color:#00a32a; margin-right:6px;"></span> ' +
					'<?php echo esc_js( __( 'Complete', 'rayai-content-orchestrator' ) ); ?>'
				);
				bulkLog('');
				bulkLog('━━━ ' + succeeded + '/' + total + ' <?php echo esc_js( __( 'posts created successfully', 'rayai-content-orchestrator' ) ); ?> ━━━', 'step');
				updateResultsHeader();
				$btn.prop('disabled', false);
				bulkRunning = false;
				$('#rayai-bulk-save-url').prop('checked', false);
				return;
			}

			var job = jobs[index];
			var num = index + 1;
			bulkLog('');
			bulkLog('━━━ <?php echo esc_js( __( 'Post', 'rayai-content-orchestrator' ) ); ?> ' + num + '/' + total + ': ' + job.topic + ' ━━━', 'step');

			runBulkStep(null, 1, job, function(success, data) {
				completed++;
				var $tbody = $('#rayai-bulk-results-body');
				var rowId = 'rayai-detail-' + completed;
				if (success && data.wp_result) {
					succeeded++;
					var postUrl  = data.wp_result.url || '#';
					var editUrl  = data.wp_result.edit_url || '#';
					var postId   = data.wp_result.id || '';
					var ai       = data.ai_result || {};
					var wp       = data.wp_result || {};
					var title    = ai.seo_title || job.topic;

					$tbody.append(
						'<tr>' +
						'<td><a href="' + postUrl + '" target="_blank" style="font-weight:600; text-decoration:none;">' + $('<span>').text(title).html() + '</a><div style="margin-top:2px; color:#787c82; font-size:12px;">#' + postId + '</div></td>' +
						'<td><span class="dashicons dashicons-yes-alt" style="color:#00a32a; vertical-align:text-bottom; margin-right:4px;"></span><?php echo esc_js( __( 'Created', 'rayai-content-orchestrator' ) ); ?></td>' +
						'<td style="white-space:nowrap;"><button type="button" class="button button-small rayai-detail-toggle" data-detail="' + rowId + '" style="margin-right:2px;"><span class="dashicons dashicons-info-outline" style="vertical-align:text-bottom; font-size:14px; width:14px; height:14px; margin-right:2px;"></span><?php echo esc_js( __( 'Details', 'rayai-content-orchestrator' ) ); ?></button>' +
						'<a href="' + postUrl + '" target="_blank" class="button button-small" style="margin-right:2px;"><span class="dashicons dashicons-visibility" style="vertical-align:text-bottom; font-size:14px; width:14px; height:14px; margin-right:2px;"></span><?php echo esc_js( __( 'View', 'rayai-content-orchestrator' ) ); ?></a>' +
						'<a href="' + editUrl + '" target="_blank" class="button button-small"><span class="dashicons dashicons-edit" style="vertical-align:text-bottom; font-size:14px; width:14px; height:14px; margin-right:2px;"></span><?php echo esc_js( __( 'Edit', 'rayai-content-orchestrator' ) ); ?></a></td>' +
						'</tr>'
					);

					// Build detail panel — use a div wrapper for slideToggle (tr slideDown is unreliable).
					var detailHtml = '<tr id="' + rowId + '" class="rayai-detail-row"><td colspan="3" style="padding:0;"><div class="rayai-detail-content" style="display:none; background:#f9f9f9; border-top:1px solid #e0e0e0; padding:16px 20px;">';
					detailHtml += '<table class="form-table" style="margin:0;"><tbody>';
					detailHtml += '<tr><th style="width:160px; padding:6px 10px 6px 0; font-weight:600;"><?php echo esc_js( __( 'Prompt', 'rayai-content-orchestrator' ) ); ?></th><td style="padding:6px 0;">' + $('<span>').text(ai.prompt || job.topic).html() + '</td></tr>';
					detailHtml += '<tr><th style="padding:6px 10px 6px 0; font-weight:600;"><?php echo esc_js( __( 'Post ID', 'rayai-content-orchestrator' ) ); ?></th><td style="padding:6px 0;">' + postId + '</td></tr>';
					detailHtml += '<tr><th style="padding:6px 10px 6px 0; font-weight:600;"><?php echo esc_js( __( 'Title', 'rayai-content-orchestrator' ) ); ?></th><td style="padding:6px 0;">' + $('<span>').text(title).html() + '</td></tr>';
					detailHtml += '<tr><th style="padding:6px 10px 6px 0; font-weight:600;"><?php echo esc_js( __( 'Slug', 'rayai-content-orchestrator' ) ); ?></th><td style="padding:6px 0;"><code>' + $('<span>').text(ai.slug || wp.slug || '').html() + '</code></td></tr>';
					detailHtml += '<tr><th style="padding:6px 10px 6px 0; font-weight:600;"><?php echo esc_js( __( 'URL', 'rayai-content-orchestrator' ) ); ?></th><td style="padding:6px 0;"><a href="' + postUrl + '" target="_blank">' + $('<span>').text(postUrl).html() + '</a></td></tr>';
					detailHtml += '<tr><th style="padding:6px 10px 6px 0; font-weight:600;"><?php echo esc_js( __( 'Meta Description', 'rayai-content-orchestrator' ) ); ?></th><td style="padding:6px 0;">' + $('<span>').text(ai.meta_description || '').html() + '</td></tr>';
					detailHtml += '<tr><th style="padding:6px 10px 6px 0; font-weight:600;"><?php echo esc_js( __( 'Focus Keyphrase', 'rayai-content-orchestrator' ) ); ?></th><td style="padding:6px 0;">' + $('<span>').text(ai.focus_keyphrase || '').html() + '</td></tr>';

					if (ai.tags && ai.tags.length) {
						var tagsHtml = '';
						$.each(ai.tags, function(_, t) { tagsHtml += '<span style="display:inline-block; background:#e7e8ea; padding:2px 10px; border-radius:12px; margin:2px 4px 2px 0; font-size:12px;">' + $('<span>').text(t).html() + '</span>'; });
						detailHtml += '<tr><th style="padding:6px 10px 6px 0; font-weight:600;"><?php echo esc_js( __( 'Tags', 'rayai-content-orchestrator' ) ); ?></th><td style="padding:6px 0;">' + tagsHtml + '</td></tr>';
					}
					if (ai.categories && ai.categories.length) {
						var catsHtml = '';
						$.each(ai.categories, function(_, c) { catsHtml += '<span style="display:inline-block; background:#d5e5f7; padding:2px 10px; border-radius:12px; margin:2px 4px 2px 0; font-size:12px;">' + $('<span>').text(c).html() + '</span>'; });
						detailHtml += '<tr><th style="padding:6px 10px 6px 0; font-weight:600;"><?php echo esc_js( __( 'Categories', 'rayai-content-orchestrator' ) ); ?></th><td style="padding:6px 0;">' + catsHtml + '</td></tr>';
					}

					if (wp.yoast) {
						detailHtml += '<tr><th style="padding:6px 10px 6px 0; font-weight:600;"><?php echo esc_js( __( 'Yoast SEO', 'rayai-content-orchestrator' ) ); ?></th><td style="padding:6px 0;"><span class="dashicons dashicons-yes-alt" style="color:#00a32a; vertical-align:text-bottom;"></span> <?php echo esc_js( __( 'Updated', 'rayai-content-orchestrator' ) ); ?></td></tr>';
					}

					if (ai.linked_posts && ai.linked_posts.length) {
						var linksHtml = '';
						$.each(ai.linked_posts, function(_, lp) { linksHtml += '<div style="margin:2px 0;"><span class="dashicons dashicons-admin-links" style="font-size:14px; width:14px; height:14px; vertical-align:text-bottom; margin-right:4px; color:#2271b1;"></span><a href="' + lp.url + '" target="_blank">' + $('<span>').text(lp.title).html() + '</a></div>'; });
						detailHtml += '<tr><th style="padding:6px 10px 6px 0; font-weight:600;"><?php echo esc_js( __( 'Internal Links', 'rayai-content-orchestrator' ) ); ?> (' + ai.linked_posts.length + ')</th><td style="padding:6px 0;">' + linksHtml + '</td></tr>';
					}

					// AI Provider.
					detailHtml += '<tr><th style="padding:6px 10px 6px 0; font-weight:600;"><?php echo esc_js( __( 'AI Provider', 'rayai-content-orchestrator' ) ); ?></th><td style="padding:6px 0;">' + $('<span>').text(rayai.provider.charAt(0).toUpperCase() + rayai.provider.slice(1) + ' (' + rayai.model + ')').html() + '</td></tr>';

					// Featured Image (4 options).
					if (ai.image_urls && ai.image_urls.length) {
						var imgHtml = '<div class="rayai-image-options" data-post-id="' + postId + '">';
						imgHtml += '<div class="rayai-image-grid" style="display:grid; grid-template-columns:repeat(2,minmax(140px,1fr)); gap:8px; max-width:500px;">';
						for (var ii = 0; ii < ai.image_urls.length; ii++) {
							var isSel = (ii === 0);
							imgHtml += '<div class="rayai-image-option' + (isSel ? ' selected' : '') + '" data-index="' + ii + '" data-url="' + ai.image_urls[ii] + '" style="position:relative; cursor:pointer; border:3px solid ' + (isSel ? '#2271b1' : 'transparent') + '; border-radius:6px; overflow:hidden;">';
							imgHtml += '<img src="' + ai.image_urls[ii] + '" alt="Option ' + (ii+1) + '" style="width:100%; height:auto; display:block;" />';
							imgHtml += '<div style="position:absolute; top:4px; left:4px; background:rgba(0,0,0,0.7); color:#fff; padding:1px 6px; border-radius:10px; font-size:10px; font-weight:600;">' + (ii+1) + '</div>';
							if (isSel) imgHtml += '<div class="rayai-image-check" style="position:absolute; top:4px; right:4px; background:#2271b1; color:#fff; width:20px; height:20px; border-radius:50%; display:flex; align-items:center; justify-content:center;"><span class="dashicons dashicons-yes" style="font-size:14px; width:14px; height:14px;"></span></div>';
							imgHtml += '</div>';
						}
						imgHtml += '</div>';
						imgHtml += '<p style="margin:8px 0 4px;"><button type="button" class="button button-small rayai-regen-images-btn" data-post-id="' + postId + '"><span class="dashicons dashicons-update" style="font-size:14px; width:14px; height:14px; vertical-align:text-bottom; margin-right:2px;"></span><?php echo esc_js( __( 'Regenerate 4 New Images', 'rayai-content-orchestrator' ) ); ?></button></p>';
						imgHtml += '<p style="margin:4px 0 0; font-size:11px; color:#646970;"><span class="dashicons dashicons-info" style="vertical-align:text-bottom; color:#2271b1; font-size:14px; width:14px; height:14px;"></span> <?php echo esc_js( __( 'Click any image to set it as the featured image.', 'rayai-content-orchestrator' ) ); ?></p>';
						imgHtml += '</div>';
						detailHtml += '<tr><th style="padding:6px 10px 6px 0; font-weight:600;"><?php echo esc_js( __( 'Featured Image', 'rayai-content-orchestrator' ) ); ?> (' + ai.image_urls.length + ' <?php echo esc_js( __( 'options', 'rayai-content-orchestrator' ) ); ?>)</th><td style="padding:6px 0;">' + imgHtml + '</td></tr>';
					}

					// Repurpose buttons.
					if (postId) {
						var repHtml = '<div class="rayai-repurpose-wrap" data-post-id="' + postId + '">';
						repHtml += '<div style="display:flex; gap:6px; flex-wrap:wrap; margin-bottom:8px;">';
						repHtml += '<button type="button" class="button rayai-repurpose-btn" data-format="email"><span class="dashicons dashicons-email-alt" style="vertical-align:text-bottom; font-size:14px; width:14px; height:14px; margin-right:3px;"></span><?php echo esc_js( __( 'Email Newsletter', 'rayai-content-orchestrator' ) ); ?></button>';
						repHtml += '<button type="button" class="button rayai-repurpose-btn" data-format="twitter"><span class="dashicons dashicons-twitter" style="vertical-align:text-bottom; font-size:14px; width:14px; height:14px; margin-right:3px;"></span><?php echo esc_js( __( 'X / Twitter', 'rayai-content-orchestrator' ) ); ?></button>';
						repHtml += '<button type="button" class="button rayai-repurpose-btn" data-format="instagram"><span class="dashicons dashicons-camera" style="vertical-align:text-bottom; font-size:14px; width:14px; height:14px; margin-right:3px;"></span><?php echo esc_js( __( 'Instagram', 'rayai-content-orchestrator' ) ); ?></button>';
						repHtml += '<button type="button" class="button rayai-repurpose-btn" data-format="pinterest"><span class="dashicons dashicons-share" style="vertical-align:text-bottom; font-size:14px; width:14px; height:14px; margin-right:3px;"></span><?php echo esc_js( __( 'Pinterest', 'rayai-content-orchestrator' ) ); ?></button>';
						repHtml += '</div>';
						repHtml += '<div class="rayai-repurpose-result" style="display:none;">';
						repHtml += '<div class="rayai-repurpose-text" style="background:#f6f7f7; border-left:3px solid #2271b1; padding:12px; white-space:pre-wrap; font-size:13px; line-height:1.5; border-radius:2px; max-height:300px; overflow:auto;"></div>';
						repHtml += '<div style="margin-top:6px;"><button type="button" class="button rayai-repurpose-copy-btn"><?php echo esc_js( __( 'Copy to Clipboard', 'rayai-content-orchestrator' ) ); ?></button> <span class="rayai-repurpose-status" style="margin-left:6px;"></span></div>';
						repHtml += '</div></div>';
						detailHtml += '<tr><th style="padding:6px 10px 6px 0; font-weight:600;"><?php echo esc_js( __( 'Repurpose This Content', 'rayai-content-orchestrator' ) ); ?></th><td style="padding:6px 0;">' + repHtml + '</td></tr>';
					}

					detailHtml += '</tbody></table></div></td></tr>';
					$tbody.append(detailHtml);
				} else {
					var errMsg = data && data.error ? data.error : '<?php echo esc_js( __( 'Failed', 'rayai-content-orchestrator' ) ); ?>';
					$tbody.append(
						'<tr>' +
						'<td style="font-weight:600; padding-left:28px;">' + $('<span>').text(job.topic).html() + '</td>' +
						'<td><span class="dashicons dashicons-warning" style="color:#d63638; vertical-align:text-bottom; margin-right:4px;"></span><span style="color:#d63638;"><?php echo esc_js( __( 'Failed', 'rayai-content-orchestrator' ) ); ?></span></td>' +
						'<td><span style="color:#787c82; font-size:12px;">' + $('<span>').text(errMsg).html() + '</span></td>' +
						'</tr>'
					);
				}
				updateResultsHeader();
				// Scroll to show the results table.
				var $results = $('#rayai-bulk-results');
				if ($results.length) {
					$('html, body').stop().animate({
						scrollTop: $results.offset().top - 40
					}, 300);
				}
				processNext(index + 1);
			});
		}

		processNext(0);
	});

	// Initial count.
	updateCount();
});
</script>
<style>
@keyframes rotation { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
</style>
