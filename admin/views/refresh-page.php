<?php
/**
 * Refresh Content page template — tabbed: Overview + Individual.
 *
 * @package RayAI_Content_Orchestrator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rayai_is_configured = RAYAI_Settings::is_configured();
$rayai_published_posts = get_posts( array(
	'post_type'   => 'post',
	'post_status' => 'publish',
	'numberposts' => 100,
	'orderby'     => 'date',
	'order'       => 'DESC',
) );
$rayai_active_tab = isset( $_GET['view'] ) /* phpcs:ignore WordPress.Security.NonceVerification.Recommended */ ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : 'overview';
?>
<div class="wrap rayai-wrap">
	<h1 class="wp-heading-inline">
		<span class="dashicons dashicons-update rayai-heading-icon"></span>
		<?php esc_html_e( 'RayAI – Content Orchestrator — Refresh Content', 'rayai-content-orchestrator' ); ?>
	</h1>
	<p class="rayai-subtitle">
		<?php esc_html_e( 'Scan all posts for issues or refresh a specific post with AI improvements — while keeping your existing URL and SEO value.', 'rayai-content-orchestrator' ); ?>
	</p>

	<?php if ( ! $rayai_is_configured ) : ?>
		<div class="notice notice-error">
			<p>
				<strong><?php esc_html_e( 'API key not configured.', 'rayai-content-orchestrator' ); ?></strong>
				<?php
				/* translators: 1: opening link tag, 2: closing link tag */
				printf( esc_html__( 'Please %1$sconfigure your API key in Settings%2$s before refreshing content.', 'rayai-content-orchestrator' ), '<a href="' . esc_url( admin_url( 'admin.php?page=rayai-settings' ) ) . '">', '</a>' );
				?>
			</p>
		</div>
	<?php endif; ?>

	<!-- Tabs -->
	<nav class="nav-tab-wrapper" style="margin-bottom:20px;">
		<a href="<?php echo esc_url( add_query_arg( 'view', 'overview', admin_url( 'admin.php?page=rayai-refresh' ) ) ); ?>" class="nav-tab <?php echo 'overview' === $rayai_active_tab ? 'nav-tab-active' : ''; ?>">
			<span class="dashicons dashicons-chart-bar" style="vertical-align:text-bottom; margin-right:4px;"></span>
			<?php esc_html_e( 'Content Health Overview', 'rayai-content-orchestrator' ); ?>
		</a>
		<a href="<?php echo esc_url( add_query_arg( 'view', 'individual', admin_url( 'admin.php?page=rayai-refresh' ) ) ); ?>" class="nav-tab <?php echo 'individual' === $rayai_active_tab ? 'nav-tab-active' : ''; ?>">
			<span class="dashicons dashicons-edit" style="vertical-align:text-bottom; margin-right:4px;"></span>
			<?php esc_html_e( 'Refresh Individual Post', 'rayai-content-orchestrator' ); ?>
		</a>
	</nav>

	<div id="rayai-refresh-app">

	<?php if ( 'overview' === $rayai_active_tab ) : ?>
		<!-- ═══ OVERVIEW TAB ═══ -->
		<div class="rayai-card">
			<div class="rayai-card-header" style="display:flex; justify-content:space-between; align-items:center;">
				<h2 style="margin:0;">
					<span class="dashicons dashicons-chart-bar" style="margin-right:6px;"></span>
					<?php esc_html_e( 'Content Health Overview', 'rayai-content-orchestrator' ); ?>
				</h2>
				<button type="button" id="rayai-scan-all-btn" class="button button-primary">
					<span class="dashicons dashicons-search" style="vertical-align:text-bottom; font-size:16px; width:16px; height:16px; margin-right:4px;"></span>
					<?php esc_html_e( 'Analyze All Posts', 'rayai-content-orchestrator' ); ?>
				</button>
			</div>
			<div class="rayai-card-body">
				<p class="description" style="margin-bottom:12px;">
					<?php esc_html_e( 'Scan all published posts for issues: thin content, missing FAQ, few internal links, outdated. No AI calls — runs instantly.', 'rayai-content-orchestrator' ); ?>
				</p>
				<div id="rayai-scan-all-results" style="display:none;">
					<div id="rayai-scan-all-summary" style="display:flex; gap:8px; margin-bottom:12px; flex-wrap:wrap;"></div>
					<div id="rayai-fix-all-wrap" style="display:none; margin-bottom:12px;">
						<button type="button" id="rayai-fix-selected-btn" class="button button-primary">
							<span class="dashicons dashicons-update" style="vertical-align:text-bottom; font-size:16px; width:16px; height:16px; margin-right:4px;"></span>
							<?php esc_html_e( 'Fix Selected', 'rayai-content-orchestrator' ); ?> (<span id="rayai-fix-all-count">0</span>)
						</button>
						<button type="button" id="rayai-fix-all-btn" class="button" style="margin-left:4px;">
							<span class="dashicons dashicons-update" style="vertical-align:text-bottom; font-size:16px; width:16px; height:16px; margin-right:4px;"></span>
							<?php esc_html_e( 'Fix All Filtered', 'rayai-content-orchestrator' ); ?> (<span id="rayai-fix-filtered-count">0</span>)
						</button>
						<span class="description" style="margin-left:8px;"><?php esc_html_e( 'AI will refresh each post sequentially (1-2 min per post).', 'rayai-content-orchestrator' ); ?></span>
					</div>
					<table class="widefat striped" id="rayai-scan-all-table">
						<thead>
							<tr>
								<th style="width:30px;"><input type="checkbox" id="rayai-scan-check-all" /></th>
								<th style="width:42%;"><?php esc_html_e( 'Title', 'rayai-content-orchestrator' ); ?></th>
								<th style="width:10%;"><?php esc_html_e( 'Words', 'rayai-content-orchestrator' ); ?></th>
								<th style="width:10%;"><?php esc_html_e( 'Age', 'rayai-content-orchestrator' ); ?></th>
								<th style="width:25%;"><?php esc_html_e( 'Issues', 'rayai-content-orchestrator' ); ?></th>
								<th style="width:10%;"><?php esc_html_e( 'Actions', 'rayai-content-orchestrator' ); ?></th>
							</tr>
						</thead>
						<tbody></tbody>
					</table>
				</div>
			</div>
		</div>

	<?php else : ?>
		<!-- ═══ INDIVIDUAL TAB ═══ -->
		<div class="rayai-card">
			<div class="rayai-card-header">
				<h2>
					<span class="dashicons dashicons-search" style="margin-right:6px;"></span>
					<?php esc_html_e( 'Select Post to Refresh', 'rayai-content-orchestrator' ); ?>
				</h2>
			</div>
			<div class="rayai-card-body">
				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row"><label for="rayai-refresh-search"><?php esc_html_e( 'Search Posts', 'rayai-content-orchestrator' ); ?></label></th>
							<td>
								<input type="text" id="rayai-refresh-search" class="regular-text" placeholder="<?php esc_attr_e( 'Type to filter posts...', 'rayai-content-orchestrator' ); ?>" />
								<p class="description"><?php esc_html_e( 'Filter the list below by typing part of a post title.', 'rayai-content-orchestrator' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="rayai-refresh-post"><?php esc_html_e( 'Published Post', 'rayai-content-orchestrator' ); ?></label></th>
							<td>
								<?php if ( ! empty( $rayai_published_posts ) ) : ?>
									<select id="rayai-refresh-post" class="large-text">
										<option value=""><?php esc_html_e( '— Select a post —', 'rayai-content-orchestrator' ); ?></option>
										<?php foreach ( $rayai_published_posts as $rayai_p ) : ?>
											<option value="<?php echo esc_attr( $rayai_p->ID ); ?>"><?php echo esc_html( $rayai_p->post_title ); ?> (<?php echo esc_html( get_the_date( 'Y-m-d', $rayai_p ) ); ?>)</option>
										<?php endforeach; ?>
									</select>
									<p class="description"><?php
									/* translators: %d: number of posts */
									printf( esc_html__( 'Showing the %d most recent published posts.', 'rayai-content-orchestrator' ), count( $rayai_published_posts ) );
									?></p>
								<?php else : ?>
									<p class="description"><?php esc_html_e( 'No published posts found.', 'rayai-content-orchestrator' ); ?></p>
								<?php endif; ?>
							</td>
						</tr>
					</tbody>
				</table>
				<p class="submit">
					<button type="button" id="rayai-analyze-btn" class="button button-primary button-hero" <?php echo ( $rayai_is_configured && ! empty( $rayai_published_posts ) ) ? '' : 'disabled'; ?>>
						<span class="dashicons dashicons-visibility rayai-btn-icon"></span>
						<?php esc_html_e( 'Analyze', 'rayai-content-orchestrator' ); ?>
					</button>
				</p>
			</div>
		</div>

		<!-- Analysis Results -->
		<div id="rayai-analysis-card" class="rayai-card" style="display:none;">
			<div class="rayai-card-header"><h2><span class="dashicons dashicons-chart-bar" style="margin-right:6px;"></span><?php esc_html_e( 'Analysis Results', 'rayai-content-orchestrator' ); ?></h2></div>
			<div class="rayai-card-body">
				<table class="widefat striped" id="rayai-analysis-info">
					<tbody>
						<tr><td><strong><?php esc_html_e( 'Title', 'rayai-content-orchestrator' ); ?></strong></td><td id="rayai-analysis-title"></td></tr>
						<tr><td><strong><?php esc_html_e( 'Published', 'rayai-content-orchestrator' ); ?></strong></td><td id="rayai-analysis-published"></td></tr>
						<tr><td><strong><?php esc_html_e( 'Last Updated', 'rayai-content-orchestrator' ); ?></strong></td><td id="rayai-analysis-updated"></td></tr>
						<tr><td><strong><?php esc_html_e( 'Word Count', 'rayai-content-orchestrator' ); ?></strong></td><td id="rayai-analysis-wordcount"></td></tr>
					</tbody>
				</table>
				<h3 style="margin:20px 0 12px;"><?php esc_html_e( 'Issues Found', 'rayai-content-orchestrator' ); ?></h3>
				<p class="description" style="margin-bottom:12px;"><?php esc_html_e( 'Select which issues the AI should address when refreshing this post.', 'rayai-content-orchestrator' ); ?></p>
				<div id="rayai-issues-list" class="rayai-checkbox-list"></div>
				<p class="submit">
					<button type="button" id="rayai-refresh-btn" class="button button-primary button-hero">
						<span class="dashicons dashicons-update rayai-btn-icon"></span>
						<?php esc_html_e( 'Refresh Post', 'rayai-content-orchestrator' ); ?>
					</button>
				</p>
			</div>
		</div>

		<!-- Progress Log -->
		<div id="rayai-refresh-log-area" class="rayai-card" style="display:none;">
			<div class="rayai-card-header"><h2><span class="spinner is-active" id="rayai-refresh-spinner" style="float:none; margin:0 8px 0 0;"></span><?php esc_html_e( 'Progress', 'rayai-content-orchestrator' ); ?></h2></div>
			<div class="rayai-card-body"><div id="rayai-refresh-log-box" class="rayai-log-box"></div></div>
		</div>

		<!-- Results -->
		<div id="rayai-refresh-result-card" class="rayai-card rayai-result-card" style="display:none;">
			<div class="rayai-card-header rayai-card-header-success"><h2><span class="dashicons dashicons-yes-alt" style="margin-right:6px;"></span><?php esc_html_e( 'Post Refreshed Successfully', 'rayai-content-orchestrator' ); ?></h2></div>
			<div class="rayai-card-body">
				<table class="widefat striped" id="rayai-refresh-result-table"><tbody></tbody></table>
				<div style="margin-top:16px;">
					<a id="rayai-refresh-view-post" href="#" class="button button-primary" target="_blank"><span class="dashicons dashicons-external" style="vertical-align:text-bottom; font-size:16px; width:16px; height:16px; margin-right:4px;"></span><?php esc_html_e( 'View Post', 'rayai-content-orchestrator' ); ?></a>
					<a id="rayai-refresh-edit-post" href="#" class="button" target="_blank"><span class="dashicons dashicons-edit" style="vertical-align:text-bottom; font-size:16px; width:16px; height:16px; margin-right:4px;"></span><?php esc_html_e( 'Edit in WordPress', 'rayai-content-orchestrator' ); ?></a>
				</div>
			</div>
		</div>
	<?php endif; ?>

	</div>
</div>

<script>
jQuery(document).ready(function($) {
	var ajaxUrl = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';
	var nonce   = '<?php echo esc_js( wp_create_nonce( 'rayai_nonce' ) ); ?>';

	// ═══ OVERVIEW TAB ═══
	$('#rayai-scan-all-btn').on('click', function() {
		var $btn = $(this);
		$btn.prop('disabled', true).html('<span class="spinner is-active" style="float:none; margin:0 4px 0 0;"></span> <?php echo esc_js( __( 'Scanning...', 'rayai-content-orchestrator' ) ); ?>');

		$.post(ajaxUrl, { action: 'rayai_analyze_all_posts', nonce: nonce }).done(function(res) {
			if (!res.success) { alert(res.data.message || 'Scan failed.'); return; }
			var d = res.data;
			window.rayaiScanData = d.posts;

			var labels = { thin: '<?php echo esc_js( __( 'Thin content', 'rayai-content-orchestrator' ) ); ?>', no_faq: '<?php echo esc_js( __( 'No FAQ', 'rayai-content-orchestrator' ) ); ?>', few_links: '<?php echo esc_js( __( 'Few links', 'rayai-content-orchestrator' ) ); ?>', outdated: '<?php echo esc_js( __( 'Outdated', 'rayai-content-orchestrator' ) ); ?>' };
			var colors = { thin: '#d63638', no_faq: '#dba617', few_links: '#2271b1', outdated: '#787c82' };
			window.rayaiIssueLabels = labels;
			window.rayaiIssueColors = colors;

			var counts = { thin: 0, no_faq: 0, few_links: 0, outdated: 0 };
			$.each(d.posts, function(_, p) { $.each(p.issues, function(_, i) { counts[i]++; }); });

			$('#rayai-scan-all-summary').html(
				'<div class="rayai-filter-card rayai-filter-active" data-filter="all" style="cursor:pointer; padding:8px 14px; border-radius:4px; border:2px solid #2271b1; background:#f0f6fc;"><strong>' + d.total + '</strong> <?php echo esc_js( __( 'total', 'rayai-content-orchestrator' ) ); ?></div>' +
				'<div class="rayai-filter-card" data-filter="has_issues" style="cursor:pointer; padding:8px 14px; border-radius:4px; border:2px solid transparent; background:#fff8e5;"><strong>' + d.posts_with_issues + '</strong> <?php echo esc_js( __( 'need attention', 'rayai-content-orchestrator' ) ); ?></div>' +
				'<div class="rayai-filter-card" data-filter="thin" style="cursor:pointer; padding:8px 14px; border-radius:4px; border:2px solid transparent; background:#d6363822;"><strong>' + counts.thin + '</strong> <?php echo esc_js( __( 'Thin', 'rayai-content-orchestrator' ) ); ?></div>' +
				'<div class="rayai-filter-card" data-filter="no_faq" style="cursor:pointer; padding:8px 14px; border-radius:4px; border:2px solid transparent; background:#dba61722;"><strong>' + counts.no_faq + '</strong> <?php echo esc_js( __( 'No FAQ', 'rayai-content-orchestrator' ) ); ?></div>' +
				'<div class="rayai-filter-card" data-filter="few_links" style="cursor:pointer; padding:8px 14px; border-radius:4px; border:2px solid transparent; background:#2271b122;"><strong>' + counts.few_links + '</strong> <?php echo esc_js( __( 'Few links', 'rayai-content-orchestrator' ) ); ?></div>' +
				'<div class="rayai-filter-card" data-filter="outdated" style="cursor:pointer; padding:8px 14px; border-radius:4px; border:2px solid transparent; background:#78798222;"><strong>' + counts.outdated + '</strong> <?php echo esc_js( __( 'Outdated', 'rayai-content-orchestrator' ) ); ?></div>'
			);
			renderScanTable('all');
			$('#rayai-scan-all-results').slideDown(200);
		}).always(function() {
			$btn.prop('disabled', false).html('<span class="dashicons dashicons-search" style="vertical-align:text-bottom; font-size:16px; width:16px; height:16px; margin-right:4px;"></span> <?php echo esc_js( __( 'Analyze All Posts', 'rayai-content-orchestrator' ) ); ?>');
		});
	});

	function renderScanTable(filter) {
		var posts = window.rayaiScanData || [], labels = window.rayaiIssueLabels || {}, colors = window.rayaiIssueColors || {};
		var $tbody = $('#rayai-scan-all-table tbody').empty();
		var filtered = posts.filter(function(p) {
			if (filter === 'all') return true;
			if (filter === 'has_issues') return p.issues.length > 0;
			return p.issues.indexOf(filter) !== -1;
		});

		$.each(filtered, function(_, p) {
			var badges = '';
			if (p.issues.length === 0) {
				badges = '<span style="color:#00a32a;"><span class="dashicons dashicons-yes-alt" style="font-size:14px; width:14px; height:14px; vertical-align:text-bottom;"></span> <?php echo esc_js( __( 'Good', 'rayai-content-orchestrator' ) ); ?></span>';
			} else {
				$.each(p.issues, function(_, issue) {
					badges += '<span class="rayai-issue-badge" data-issue="' + issue + '" style="display:inline-block; padding:1px 8px; margin:1px 2px; border-radius:10px; font-size:11px; font-weight:600; cursor:pointer; background:' + (colors[issue] || '#787c82') + '22; color:' + (colors[issue] || '#787c82') + ';">' + (labels[issue] || issue) + '</span>';
				});
			}
			var ageStr = p.age_days < 30 ? p.age_days + 'd' : Math.round(p.age_days / 30) + 'mo';
			var fixBtn = p.issues.length > 0 ? '<button type="button" class="button button-small rayai-inline-fix-btn" data-post-id="' + p.id + '" data-issues="' + p.issues.join(',') + '" data-title="' + $('<span>').text(p.title).html() + '"><span class="dashicons dashicons-update" style="vertical-align:text-bottom; font-size:14px; width:14px; height:14px; margin-right:2px;"></span><?php echo esc_js( __( 'Fix', 'rayai-content-orchestrator' ) ); ?></button>' : '—';
			var checkHtml = p.issues.length > 0 ? '<input type="checkbox" class="rayai-scan-row-check" value="' + p.id + '" data-issues="' + p.issues.join(',') + '" />' : '';
			$tbody.append('<tr><td>' + checkHtml + '</td><td><a href="' + p.url + '" target="_blank" style="text-decoration:none; font-weight:500;">' + $('<span>').text(p.title).html() + '</a></td><td>' + p.word_count + '</td><td>' + ageStr + '</td><td>' + badges + '</td><td>' + fixBtn + '</td></tr>');
		});

		var hasIssues = filtered.filter(function(p) { return p.issues.length > 0; }).length;
		$('#rayai-fix-all-wrap').toggle(hasIssues > 0);
		$('#rayai-fix-filtered-count').text(hasIssues);
		$('#rayai-fix-all-count').text('0');
		$('#rayai-scan-check-all').prop('checked', false);
	}

	$(document).on('click', '.rayai-filter-card', function() {
		$('.rayai-filter-card').css('border-color', 'transparent').removeClass('rayai-filter-active');
		$(this).css('border-color', '#2271b1').addClass('rayai-filter-active');
		renderScanTable($(this).data('filter'));
	});

	$(document).on('click', '.rayai-issue-badge', function() {
		var issue = $(this).data('issue');
		$('.rayai-filter-card').css('border-color', 'transparent').removeClass('rayai-filter-active');
		$('.rayai-filter-card[data-filter="' + issue + '"]').css('border-color', '#2271b1').addClass('rayai-filter-active');
		renderScanTable(issue);
	});

	// Checkbox handlers for scan results.
	$(document).on('change', '#rayai-scan-check-all', function() {
		$('.rayai-scan-row-check').prop('checked', $(this).prop('checked'));
		$('#rayai-fix-all-count').text($('.rayai-scan-row-check:checked').length);
	});
	$(document).on('change', '.rayai-scan-row-check', function() {
		var checked = $('.rayai-scan-row-check:checked').length;
		var total = $('.rayai-scan-row-check').length;
		$('#rayai-fix-all-count').text(checked);
		$('#rayai-scan-check-all').prop('checked', checked === total && total > 0).prop('indeterminate', checked > 0 && checked < total);
	});

	// Fix Selected — only checked posts.
	$('#rayai-fix-selected-btn').on('click', function() {
		var ids = [];
		$('.rayai-scan-row-check:checked').each(function() { ids.push(parseInt($(this).val(), 10)); });
		if (!ids.length) { alert('<?php echo esc_js( __( 'Select posts to fix first.', 'rayai-content-orchestrator' ) ); ?>'); return; }
		var posts = (window.rayaiScanData || []).filter(function(p) { return ids.indexOf(p.id) !== -1; });
		runFixAll(posts);
	});

	// Fix All Filtered button.
	$('#rayai-fix-all-btn').on('click', function() {
		var filter = $('.rayai-filter-card.rayai-filter-active').data('filter') || 'has_issues';
		var posts = (window.rayaiScanData || []).filter(function(p) {
			if (p.issues.length === 0) return false;
			if (filter === 'all' || filter === 'has_issues') return true;
			return p.issues.indexOf(filter) !== -1;
		});
		if (posts.length) runFixAll(posts);
	});

	function runFixAll(posts) {
		if (!posts.length) return;
		if (!confirm('<?php echo esc_js( __( 'AI-refresh', 'rayai-content-orchestrator' ) ); ?> ' + posts.length + ' <?php echo esc_js( __( 'posts? Each takes 1-2 minutes.', 'rayai-content-orchestrator' ) ); ?>')) return;

		$('#rayai-fix-all-btn, #rayai-fix-selected-btn').prop('disabled', true);

		var $progress = $('<div id="rayai-fix-all-progress" style="margin-top:12px; padding:12px 16px; background:#f6f7f7; border:1px solid #e0e0e0; border-radius:4px;"><div class="rayai-fix-all-log" style="max-height:300px; overflow:auto; font-family:monospace; font-size:12px; line-height:1.8;"></div></div>');
		$('#rayai-fix-all-progress').remove();
		$('#rayai-scan-all-table').after($progress);
		var $log = $progress.find('.rayai-fix-all-log');

		var succeeded = 0, total = posts.length;

		function fixNext(idx) {
			if (idx >= total) {
				$log.append('<div style="font-weight:600; margin-top:8px;">━━━ ' + succeeded + '/' + total + ' <?php echo esc_js( __( 'posts refreshed successfully', 'rayai-content-orchestrator' ) ); ?> ━━━</div>');
				$log.append('<div style="margin-top:6px; color:#2271b1;"><?php echo esc_js( __( 'Re-scanning in 3 seconds...', 'rayai-content-orchestrator' ) ); ?></div>');
				$log.scrollTop($log[0].scrollHeight);
				setTimeout(function() { $('#rayai-scan-all-btn').trigger('click'); }, 3000);
				return;
			}

			var p = posts[idx];
			$log.append('<div style="margin-top:6px;"><strong><?php echo esc_js( __( 'Refreshing', 'rayai-content-orchestrator' ) ); ?> ' + (idx + 1) + '/' + total + ':</strong> ' + $('<span>').text(p.title).html() + ' <span class="spinner is-active" style="float:none; margin:0;"></span></div>');
			$log.scrollTop($log[0].scrollHeight);

			var issueKeys = [];
			$.each(p.issues, function(_, i) {
				var map = { thin: 'thin_sections', no_faq: 'missing_faq', few_links: 'missing_internal_links', outdated: 'outdated_content' };
				issueKeys.push(map[i] || i);
			});

			$.ajax({ url: ajaxUrl, type: 'POST', timeout: 300000, data: { action: 'rayai_refresh_post', nonce: nonce, post_id: p.id, 'issues[]': issueKeys }
			}).done(function(res) {
				if (res.success) {
					succeeded++;
					$log.append('<div style="color:#00a32a;"><span class="dashicons dashicons-yes-alt" style="font-size:14px; width:14px; height:14px; vertical-align:text-bottom;"></span> <?php echo esc_js( __( 'Done', 'rayai-content-orchestrator' ) ); ?> — ' + res.data.word_count + ' <?php echo esc_js( __( 'words', 'rayai-content-orchestrator' ) ); ?></div>');
				} else {
					$log.append('<div style="color:#d63638;"><span class="dashicons dashicons-warning" style="font-size:14px; width:14px; height:14px; vertical-align:text-bottom;"></span> <?php echo esc_js( __( 'Failed:', 'rayai-content-orchestrator' ) ); ?> ' + $('<span>').text(res.data.message || 'Error').html() + '</div>');
				}
			}).fail(function() {
				$log.append('<div style="color:#d63638;"><span class="dashicons dashicons-warning" style="font-size:14px; width:14px; height:14px; vertical-align:text-bottom;"></span> <?php echo esc_js( __( 'Request failed (timeout)', 'rayai-content-orchestrator' ) ); ?></div>');
			}).always(function() {
				$log.find('.spinner').last().remove();
				$log.scrollTop($log[0].scrollHeight);
				fixNext(idx + 1);
			});
		}
		fixNext(0);
	}

	// Inline Fix button — refresh a single post directly on this page.
	$(document).on('click', '.rayai-inline-fix-btn', function() {
		var $btn = $(this);
		var postId = $btn.data('post-id');
		var title = $btn.data('title');
		var issues = String($btn.data('issues')).split(',');
		var issueMap = { thin: 'thin_sections', no_faq: 'missing_faq', few_links: 'missing_internal_links', outdated: 'outdated_content' };
		var issueKeys = issues.map(function(i) { return issueMap[i] || i; });

		$btn.prop('disabled', true).html('<span class="spinner is-active" style="float:none; margin:0;"></span>');

		// Create a progress card below the table.
		var progressId = 'rayai-inline-progress-' + postId;
		$('#' + progressId).remove();
		var $progress = $(
			'<div id="' + progressId + '" class="rayai-card" style="margin-top:12px;">' +
				'<div class="rayai-card-header"><h2><span class="spinner is-active" style="float:none; margin:0 8px 0 0;"></span><?php echo esc_js( __( 'Progress', 'rayai-content-orchestrator' ) ); ?></h2></div>' +
				'<div class="rayai-card-body"><div class="rayai-log-box rayai-inline-log"><div class="rayai-log-line"><?php echo esc_js( __( 'Sending post to AI for refresh...', 'rayai-content-orchestrator' ) ); ?></div></div></div>' +
			'</div>'
		);
		$('#rayai-scan-all-table').after($progress);
		$('html, body').animate({ scrollTop: $progress.offset().top - 60 }, 300);

		$.ajax({ url: ajaxUrl, type: 'POST', timeout: 300000, data: { action: 'rayai_refresh_post', nonce: nonce, post_id: postId, 'issues[]': issueKeys }
		}).done(function(res) {
			$progress.find('.spinner').removeClass('is-active');
			$progress.find('.rayai-inline-log').append('<div class="rayai-log-line">' + (res.success ? '<?php echo esc_js( __( 'Post refreshed successfully!', 'rayai-content-orchestrator' ) ); ?>' : '<?php echo esc_js( __( 'Error:', 'rayai-content-orchestrator' ) ); ?> ' + $('<span>').text(res.data.message || 'Error').html()) + '</div>');

			if (res.success) {
				var d = res.data;
				var $result = $(
					'<div class="rayai-card rayai-result-card" style="margin-top:12px;">' +
						'<div class="rayai-card-header rayai-card-header-success"><h2><span class="dashicons dashicons-yes-alt" style="margin-right:6px;"></span>' + title + '</h2></div>' +
						'<div class="rayai-card-body">' +
							'<table class="widefat striped"><tbody>' +
								'<tr><td><strong><?php echo esc_js( __( 'Title', 'rayai-content-orchestrator' ) ); ?></strong></td><td>' + $('<span>').text(d.title).html() + '</td></tr>' +
								'<tr><td><strong><?php echo esc_js( __( 'New Word Count', 'rayai-content-orchestrator' ) ); ?></strong></td><td>' + d.word_count + ' <?php echo esc_js( __( 'words', 'rayai-content-orchestrator' ) ); ?></td></tr>' +
								'<tr><td><strong><?php echo esc_js( __( 'Issues Fixed', 'rayai-content-orchestrator' ) ); ?></strong></td><td>' + d.issues_fixed.length + '</td></tr>' +
								(d.links_added > 0 ? '<tr><td><strong><?php echo esc_js( __( 'Internal Links Added', 'rayai-content-orchestrator' ) ); ?></strong></td><td>' + d.links_added + '</td></tr>' : '') +
							'</tbody></table>' +
							'<div style="margin-top:16px;">' +
								'<a href="' + d.url + '" target="_blank" class="button button-primary"><span class="dashicons dashicons-external" style="vertical-align:text-bottom; font-size:16px; width:16px; height:16px; margin-right:4px;"></span><?php echo esc_js( __( 'View Post', 'rayai-content-orchestrator' ) ); ?></a> ' +
								'<a href="' + d.edit_url + '" target="_blank" class="button"><span class="dashicons dashicons-edit" style="vertical-align:text-bottom; font-size:16px; width:16px; height:16px; margin-right:4px;"></span><?php echo esc_js( __( 'Edit in WordPress', 'rayai-content-orchestrator' ) ); ?></a>' +
							'</div>' +
						'</div>' +
					'</div>'
				);
				$progress.after($result);
				$btn.closest('tr').find('.rayai-scan-row-check').prop('checked', false);
				$('html, body').animate({ scrollTop: $result.offset().top - 40 }, 300);
			}
		}).fail(function() {
			$progress.find('.spinner').removeClass('is-active');
			$progress.find('.rayai-inline-log').append('<div class="rayai-log-line error"><?php echo esc_js( __( 'Request failed (timeout)', 'rayai-content-orchestrator' ) ); ?></div>');
		}).always(function() {
			$btn.prop('disabled', false).html('<span class="dashicons dashicons-update" style="vertical-align:text-bottom; font-size:14px; width:14px; height:14px; margin-right:2px;"></span><?php echo esc_js( __( 'Fix', 'rayai-content-orchestrator' ) ); ?>');
		});
	});

	// ═══ INDIVIDUAL TAB ═══
	var $search = $('#rayai-refresh-search'), $select = $('#rayai-refresh-post');
	var $options = $select.length ? $select.find('option').not(':first').clone() : $();

	// Pre-select post if passed via URL.
	var urlParams = new URLSearchParams(window.location.search);
	if (urlParams.get('post_id') && $select.length) {
		$select.val(urlParams.get('post_id'));
		setTimeout(function() { $('#rayai-analyze-btn').trigger('click'); }, 300);
	}

	$search.on('input', function() {
		var term = $(this).val().toLowerCase(), $first = $select.find('option:first');
		$select.empty().append($first);
		$options.each(function() { if ($(this).text().toLowerCase().indexOf(term) !== -1) $select.append($(this).clone()); });
		$select.val('');
	});

	$('#rayai-analyze-btn').on('click', function() {
		var postId = $select.val();
		if (!postId) { alert('<?php echo esc_js( __( 'Select a post first.', 'rayai-content-orchestrator' ) ); ?>'); return; }
		var $btn = $(this);
		$btn.prop('disabled', true).html('<span class="spinner is-active" style="float:none; margin:0;"></span> <?php echo esc_js( __( 'Analyzing...', 'rayai-content-orchestrator' ) ); ?>');

		$.post(ajaxUrl, { action: 'rayai_analyze_post', nonce: nonce, post_id: postId }).done(function(res) {
			if (res.success) {
				var d = res.data;
				$('#rayai-analysis-title').text(d.title);
				$('#rayai-analysis-published').text(d.published);
				$('#rayai-analysis-updated').text(d.updated);
				$('#rayai-analysis-wordcount').text(d.word_count + ' <?php echo esc_js( __( 'words', 'rayai-content-orchestrator' ) ); ?>');
				var $list = $('#rayai-issues-list').empty();
				if (d.issues.length > 0) {
					$.each(d.issues, function(_, issue) {
						$list.append('<label class="rayai-checkbox-item" style="display:block; margin-bottom:8px;"><input type="checkbox" name="rayai-issues[]" value="' + issue.key + '" checked /> <span>' + $('<span>').text(issue.label).html() + '</span></label>');
					});
				} else {
					$list.html('<p style="color:#00a32a; font-weight:600;"><span class="dashicons dashicons-yes-alt" style="vertical-align:text-bottom;"></span> <?php echo esc_js( __( 'No issues detected! This post looks good.', 'rayai-content-orchestrator' ) ); ?></p>');
				}
				$('#rayai-refresh-btn').data('post-id', d.post_id);
				$('#rayai-analysis-card').slideDown(200);
				$('html, body').animate({ scrollTop: $('#rayai-analysis-card').offset().top - 40 }, 300);
			} else {
				alert(res.data.message || 'Analysis failed.');
			}
		}).fail(function() { alert('Request failed.'); }).always(function() {
			$btn.prop('disabled', false).html('<span class="dashicons dashicons-visibility rayai-btn-icon"></span> <?php echo esc_js( __( 'Analyze', 'rayai-content-orchestrator' ) ); ?>');
		});
	});

	$('#rayai-refresh-btn').on('click', function() {
		var $btn = $(this), postId = $btn.data('post-id'), issues = [];
		$('input[name="rayai-issues[]"]:checked').each(function() { issues.push($(this).val()); });
		if (!issues.length) { alert('<?php echo esc_js( __( 'Select at least one issue to fix.', 'rayai-content-orchestrator' ) ); ?>'); return; }
		if (!confirm('<?php echo esc_js( __( 'This will rewrite the post content with AI improvements. The original content will be replaced. Continue?', 'rayai-content-orchestrator' ) ); ?>')) return;

		$btn.prop('disabled', true).html('<span class="spinner is-active" style="float:none; margin:0;"></span> <?php echo esc_js( __( 'Refreshing... (1-2 minutes)', 'rayai-content-orchestrator' ) ); ?>');
		$('#rayai-refresh-log-area').slideDown(200);
		$('#rayai-refresh-log-box').html('<div class="rayai-log-line"><?php echo esc_js( __( 'Sending post to AI for refresh...', 'rayai-content-orchestrator' ) ); ?></div>');

		$.ajax({ url: ajaxUrl, type: 'POST', data: { action: 'rayai_refresh_post', nonce: nonce, post_id: postId, 'issues[]': issues }, timeout: 300000
		}).done(function(res) {
			$('#rayai-refresh-spinner').removeClass('is-active');
			if (res.success) {
				var d = res.data;
				$('#rayai-refresh-log-box').append('<div class="rayai-log-line success"><?php echo esc_js( __( 'Post refreshed successfully!', 'rayai-content-orchestrator' ) ); ?></div>');
				var $tbody = $('#rayai-refresh-result-table tbody').empty();
				$tbody.append('<tr><td><strong><?php echo esc_js( __( 'Title', 'rayai-content-orchestrator' ) ); ?></strong></td><td>' + $('<span>').text(d.title).html() + '</td></tr>');
				$tbody.append('<tr><td><strong><?php echo esc_js( __( 'New Word Count', 'rayai-content-orchestrator' ) ); ?></strong></td><td>' + d.word_count + ' <?php echo esc_js( __( 'words', 'rayai-content-orchestrator' ) ); ?></td></tr>');
				$tbody.append('<tr><td><strong><?php echo esc_js( __( 'Issues Fixed', 'rayai-content-orchestrator' ) ); ?></strong></td><td>' + d.issues_fixed.length + '</td></tr>');
				if (d.links_added > 0) $tbody.append('<tr><td><strong><?php echo esc_js( __( 'Internal Links Added', 'rayai-content-orchestrator' ) ); ?></strong></td><td>' + d.links_added + '</td></tr>');
				$('#rayai-refresh-view-post').attr('href', d.url);
				$('#rayai-refresh-edit-post').attr('href', d.edit_url);
				$('#rayai-refresh-result-card').slideDown(200);
				$('html, body').animate({ scrollTop: $('#rayai-refresh-result-card').offset().top - 40 }, 300);
			} else {
				$('#rayai-refresh-log-box').append('<div class="rayai-log-line error"><?php echo esc_js( __( 'Error:', 'rayai-content-orchestrator' ) ); ?> ' + $('<span>').text(res.data.message).html() + '</div>');
			}
		}).fail(function(xhr) {
			$('#rayai-refresh-spinner').removeClass('is-active');
			$('#rayai-refresh-log-box').append('<div class="rayai-log-line error"><?php echo esc_js( __( 'Request failed:', 'rayai-content-orchestrator' ) ); ?> ' + xhr.status + '</div>');
		}).always(function() {
			$btn.prop('disabled', false).html('<span class="dashicons dashicons-update rayai-btn-icon"></span> <?php echo esc_js( __( 'Refresh Post', 'rayai-content-orchestrator' ) ); ?>');
		});
	});
});
</script>
