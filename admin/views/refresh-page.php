<?php
/**
 * Refresh Content page template — tabbed: Overview + Individual.
 *
 * @package Ray_Bogman_AI_Content_Orchestrator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rbco_is_configured = RBCO_Settings::is_configured();
$rbco_published_posts = get_posts( array(
	'post_type'   => 'post',
	'post_status' => 'publish',
	'numberposts' => 100,
	'orderby'     => 'date',
	'order'       => 'DESC',
) );
$rbco_active_tab = isset( $_GET['view'] ) /* phpcs:ignore WordPress.Security.NonceVerification.Recommended */ ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : 'overview';
?>
<div class="wrap rbco-wrap">
	<h1 class="wp-heading-inline">
		<span class="dashicons dashicons-update rbco-heading-icon"></span>
		<?php esc_html_e( 'Ray Bogman AI Content Orchestrator — Refresh Content', 'raybogman-ai-content-orchestrator' ); ?>
	</h1>
	<p class="rbco-subtitle">
		<?php esc_html_e( 'Scan all posts for issues or refresh a specific post with AI improvements — while keeping your existing URL and SEO value.', 'raybogman-ai-content-orchestrator' ); ?>
	</p>

	<?php if ( ! $rbco_is_configured ) : ?>
		<div class="notice notice-error">
			<p>
				<strong><?php esc_html_e( 'API key not configured.', 'raybogman-ai-content-orchestrator' ); ?></strong>
				<?php
				/* translators: 1: opening link tag, 2: closing link tag */
				printf( esc_html__( 'Please %1$sconfigure your API key in Settings%2$s before refreshing content.', 'raybogman-ai-content-orchestrator' ), '<a href="' . esc_url( admin_url( 'admin.php?page=rbco-settings' ) ) . '">', '</a>' );
				?>
			</p>
		</div>
	<?php endif; ?>

	<!-- Tabs -->
	<nav class="nav-tab-wrapper" style="margin-bottom:20px;">
		<a href="<?php echo esc_url( add_query_arg( 'view', 'overview', admin_url( 'admin.php?page=rbco-refresh' ) ) ); ?>" class="nav-tab <?php echo 'overview' === $rbco_active_tab ? 'nav-tab-active' : ''; ?>">
			<span class="dashicons dashicons-chart-bar" style="vertical-align:text-bottom; margin-right:4px;"></span>
			<?php esc_html_e( 'Content Health Overview', 'raybogman-ai-content-orchestrator' ); ?>
		</a>
		<a href="<?php echo esc_url( add_query_arg( 'view', 'individual', admin_url( 'admin.php?page=rbco-refresh' ) ) ); ?>" class="nav-tab <?php echo 'individual' === $rbco_active_tab ? 'nav-tab-active' : ''; ?>">
			<span class="dashicons dashicons-edit" style="vertical-align:text-bottom; margin-right:4px;"></span>
			<?php esc_html_e( 'Refresh Individual Post', 'raybogman-ai-content-orchestrator' ); ?>
		</a>
	</nav>

	<div id="rbco-refresh-app">

	<?php if ( 'overview' === $rbco_active_tab ) : ?>
		<!-- ═══ OVERVIEW TAB ═══ -->
		<div class="rbco-card">
			<div class="rbco-card-header" style="display:flex; justify-content:space-between; align-items:center;">
				<h2 style="margin:0;">
					<span class="dashicons dashicons-chart-bar" style="margin-right:6px;"></span>
					<?php esc_html_e( 'Content Health Overview', 'raybogman-ai-content-orchestrator' ); ?>
				</h2>
				<button type="button" id="rbco-scan-all-btn" class="button button-primary">
					<span class="dashicons dashicons-search" style="vertical-align:text-bottom; font-size:16px; width:16px; height:16px; margin-right:4px;"></span>
					<?php esc_html_e( 'Analyze All Posts', 'raybogman-ai-content-orchestrator' ); ?>
				</button>
			</div>
			<div class="rbco-card-body">
				<p class="description" style="margin-bottom:12px;">
					<?php esc_html_e( 'Scan all published posts for issues: thin content, missing FAQ, few internal links, outdated. No AI calls — runs instantly.', 'raybogman-ai-content-orchestrator' ); ?>
				</p>
				<div id="rbco-scan-all-results" style="display:none;">
					<div id="rbco-scan-all-summary" style="display:flex; gap:8px; margin-bottom:12px; flex-wrap:wrap;"></div>
					<div id="rbco-fix-all-wrap" style="display:none; margin-bottom:12px;">
						<button type="button" id="rbco-fix-selected-btn" class="button button-primary">
							<span class="dashicons dashicons-update" style="vertical-align:text-bottom; font-size:16px; width:16px; height:16px; margin-right:4px;"></span>
							<?php esc_html_e( 'Fix Selected', 'raybogman-ai-content-orchestrator' ); ?> (<span id="rbco-fix-all-count">0</span>)
						</button>
						<button type="button" id="rbco-fix-all-btn" class="button" style="margin-left:4px;">
							<span class="dashicons dashicons-update" style="vertical-align:text-bottom; font-size:16px; width:16px; height:16px; margin-right:4px;"></span>
							<?php esc_html_e( 'Fix All Filtered', 'raybogman-ai-content-orchestrator' ); ?> (<span id="rbco-fix-filtered-count">0</span>)
						</button>
						<span class="description" style="margin-left:8px;"><?php esc_html_e( 'AI will refresh each post sequentially (1-2 min per post).', 'raybogman-ai-content-orchestrator' ); ?></span>
					</div>
					<table class="widefat striped" id="rbco-scan-all-table">
						<thead>
							<tr>
								<th style="width:30px;"><input type="checkbox" id="rbco-scan-check-all" /></th>
								<th style="width:42%;"><?php esc_html_e( 'Title', 'raybogman-ai-content-orchestrator' ); ?></th>
								<th style="width:10%;"><?php esc_html_e( 'Words', 'raybogman-ai-content-orchestrator' ); ?></th>
								<th style="width:10%;"><?php esc_html_e( 'Age', 'raybogman-ai-content-orchestrator' ); ?></th>
								<th style="width:25%;"><?php esc_html_e( 'Issues', 'raybogman-ai-content-orchestrator' ); ?></th>
								<th style="width:10%;"><?php esc_html_e( 'Actions', 'raybogman-ai-content-orchestrator' ); ?></th>
							</tr>
						</thead>
						<tbody></tbody>
					</table>
				</div>
			</div>
		</div>

	<?php else : ?>
		<!-- ═══ INDIVIDUAL TAB ═══ -->
		<div class="rbco-card">
			<div class="rbco-card-header">
				<h2>
					<span class="dashicons dashicons-search" style="margin-right:6px;"></span>
					<?php esc_html_e( 'Select Post to Refresh', 'raybogman-ai-content-orchestrator' ); ?>
				</h2>
			</div>
			<div class="rbco-card-body">
				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row"><label for="rbco-refresh-search"><?php esc_html_e( 'Search Posts', 'raybogman-ai-content-orchestrator' ); ?></label></th>
							<td>
								<input type="text" id="rbco-refresh-search" class="regular-text" placeholder="<?php esc_attr_e( 'Type to filter posts...', 'raybogman-ai-content-orchestrator' ); ?>" />
								<p class="description"><?php esc_html_e( 'Filter the list below by typing part of a post title.', 'raybogman-ai-content-orchestrator' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="rbco-refresh-post"><?php esc_html_e( 'Published Post', 'raybogman-ai-content-orchestrator' ); ?></label></th>
							<td>
								<?php if ( ! empty( $rbco_published_posts ) ) : ?>
									<select id="rbco-refresh-post" class="large-text">
										<option value=""><?php esc_html_e( '— Select a post —', 'raybogman-ai-content-orchestrator' ); ?></option>
										<?php foreach ( $rbco_published_posts as $rbco_p ) : ?>
											<option value="<?php echo esc_attr( $rbco_p->ID ); ?>"><?php echo esc_html( $rbco_p->post_title ); ?> (<?php echo esc_html( get_the_date( 'Y-m-d', $rbco_p ) ); ?>)</option>
										<?php endforeach; ?>
									</select>
									<p class="description"><?php
									/* translators: %d: number of posts */
									printf( esc_html__( 'Showing the %d most recent published posts.', 'raybogman-ai-content-orchestrator' ), count( $rbco_published_posts ) );
									?></p>
								<?php else : ?>
									<p class="description"><?php esc_html_e( 'No published posts found.', 'raybogman-ai-content-orchestrator' ); ?></p>
								<?php endif; ?>
							</td>
						</tr>
					</tbody>
				</table>
				<p class="submit">
					<button type="button" id="rbco-analyze-btn" class="button button-primary button-hero" <?php echo ( $rbco_is_configured && ! empty( $rbco_published_posts ) ) ? '' : 'disabled'; ?>>
						<span class="dashicons dashicons-visibility rbco-btn-icon"></span>
						<?php esc_html_e( 'Analyze', 'raybogman-ai-content-orchestrator' ); ?>
					</button>
				</p>
			</div>
		</div>

		<!-- Analysis Results -->
		<div id="rbco-analysis-card" class="rbco-card" style="display:none;">
			<div class="rbco-card-header"><h2><span class="dashicons dashicons-chart-bar" style="margin-right:6px;"></span><?php esc_html_e( 'Analysis Results', 'raybogman-ai-content-orchestrator' ); ?></h2></div>
			<div class="rbco-card-body">
				<table class="widefat striped" id="rbco-analysis-info">
					<tbody>
						<tr><td><strong><?php esc_html_e( 'Title', 'raybogman-ai-content-orchestrator' ); ?></strong></td><td id="rbco-analysis-title"></td></tr>
						<tr><td><strong><?php esc_html_e( 'Published', 'raybogman-ai-content-orchestrator' ); ?></strong></td><td id="rbco-analysis-published"></td></tr>
						<tr><td><strong><?php esc_html_e( 'Last Updated', 'raybogman-ai-content-orchestrator' ); ?></strong></td><td id="rbco-analysis-updated"></td></tr>
						<tr><td><strong><?php esc_html_e( 'Word Count', 'raybogman-ai-content-orchestrator' ); ?></strong></td><td id="rbco-analysis-wordcount"></td></tr>
					</tbody>
				</table>
				<h3 style="margin:20px 0 12px;"><?php esc_html_e( 'Issues Found', 'raybogman-ai-content-orchestrator' ); ?></h3>
				<p class="description" style="margin-bottom:12px;"><?php esc_html_e( 'Select which issues the AI should address when refreshing this post.', 'raybogman-ai-content-orchestrator' ); ?></p>
				<div id="rbco-issues-list" class="rbco-checkbox-list"></div>
				<p class="submit">
					<button type="button" id="rbco-refresh-btn" class="button button-primary button-hero">
						<span class="dashicons dashicons-update rbco-btn-icon"></span>
						<?php esc_html_e( 'Refresh Post', 'raybogman-ai-content-orchestrator' ); ?>
					</button>
				</p>
			</div>
		</div>

		<!-- Progress Log -->
		<div id="rbco-refresh-log-area" class="rbco-card" style="display:none;">
			<div class="rbco-card-header"><h2><span class="spinner is-active" id="rbco-refresh-spinner" style="float:none; margin:0 8px 0 0;"></span><?php esc_html_e( 'Progress', 'raybogman-ai-content-orchestrator' ); ?></h2></div>
			<div class="rbco-card-body"><div id="rbco-refresh-log-box" class="rbco-log-box"></div></div>
		</div>

		<!-- Results -->
		<div id="rbco-refresh-result-card" class="rbco-card rbco-result-card" style="display:none;">
			<div class="rbco-card-header rbco-card-header-success"><h2><span class="dashicons dashicons-yes-alt" style="margin-right:6px;"></span><?php esc_html_e( 'Post Refreshed Successfully', 'raybogman-ai-content-orchestrator' ); ?></h2></div>
			<div class="rbco-card-body">
				<table class="widefat striped" id="rbco-refresh-result-table"><tbody></tbody></table>
				<div style="margin-top:16px;">
					<a id="rbco-refresh-view-post" href="#" class="button button-primary" target="_blank"><span class="dashicons dashicons-external" style="vertical-align:text-bottom; font-size:16px; width:16px; height:16px; margin-right:4px;"></span><?php esc_html_e( 'View Post', 'raybogman-ai-content-orchestrator' ); ?></a>
					<a id="rbco-refresh-edit-post" href="#" class="button" target="_blank"><span class="dashicons dashicons-edit" style="vertical-align:text-bottom; font-size:16px; width:16px; height:16px; margin-right:4px;"></span><?php esc_html_e( 'Edit in WordPress', 'raybogman-ai-content-orchestrator' ); ?></a>
				</div>
			</div>
		</div>
	<?php endif; ?>

	</div>
</div>

<?php
// Inline JS is registered through the proper script API (attached to the
// already-enqueued 'rbco-admin' handle) instead of printing it inline.
ob_start();
?>
jQuery(document).ready(function($) {
	var ajaxUrl = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';
	var nonce   = '<?php echo esc_js( wp_create_nonce( 'rbco_nonce' ) ); ?>';

	// ═══ OVERVIEW TAB ═══
	$('#rbco-scan-all-btn').on('click', function() {
		var $btn = $(this);
		$btn.prop('disabled', true).html('<span class="spinner is-active" style="float:none; margin:0 4px 0 0;"></span> <?php echo esc_js( __( 'Scanning...', 'raybogman-ai-content-orchestrator' ) ); ?>');

		$.post(ajaxUrl, { action: 'rbco_analyze_all_posts', nonce: nonce }).done(function(res) {
			if (!res.success) { alert(res.data.message || 'Scan failed.'); return; }
			var d = res.data;
			window.rbcoScanData = d.posts;

			var labels = { thin: '<?php echo esc_js( __( 'Thin content', 'raybogman-ai-content-orchestrator' ) ); ?>', no_faq: '<?php echo esc_js( __( 'No FAQ', 'raybogman-ai-content-orchestrator' ) ); ?>', few_links: '<?php echo esc_js( __( 'Few links', 'raybogman-ai-content-orchestrator' ) ); ?>', outdated: '<?php echo esc_js( __( 'Outdated', 'raybogman-ai-content-orchestrator' ) ); ?>' };
			var colors = { thin: '#d63638', no_faq: '#dba617', few_links: '#2271b1', outdated: '#787c82' };
			window.rbcoIssueLabels = labels;
			window.rbcoIssueColors = colors;

			var counts = { thin: 0, no_faq: 0, few_links: 0, outdated: 0 };
			$.each(d.posts, function(_, p) { $.each(p.issues, function(_, i) { counts[i]++; }); });

			$('#rbco-scan-all-summary').html(
				'<div class="rbco-filter-card rbco-filter-active" data-filter="all" style="cursor:pointer; padding:8px 14px; border-radius:4px; border:2px solid #2271b1; background:#f0f6fc;"><strong>' + d.total + '</strong> <?php echo esc_js( __( 'total', 'raybogman-ai-content-orchestrator' ) ); ?></div>' +
				'<div class="rbco-filter-card" data-filter="has_issues" style="cursor:pointer; padding:8px 14px; border-radius:4px; border:2px solid transparent; background:#fff8e5;"><strong>' + d.posts_with_issues + '</strong> <?php echo esc_js( __( 'need attention', 'raybogman-ai-content-orchestrator' ) ); ?></div>' +
				'<div class="rbco-filter-card" data-filter="thin" style="cursor:pointer; padding:8px 14px; border-radius:4px; border:2px solid transparent; background:#d6363822;"><strong>' + counts.thin + '</strong> <?php echo esc_js( __( 'Thin', 'raybogman-ai-content-orchestrator' ) ); ?></div>' +
				'<div class="rbco-filter-card" data-filter="no_faq" style="cursor:pointer; padding:8px 14px; border-radius:4px; border:2px solid transparent; background:#dba61722;"><strong>' + counts.no_faq + '</strong> <?php echo esc_js( __( 'No FAQ', 'raybogman-ai-content-orchestrator' ) ); ?></div>' +
				'<div class="rbco-filter-card" data-filter="few_links" style="cursor:pointer; padding:8px 14px; border-radius:4px; border:2px solid transparent; background:#2271b122;"><strong>' + counts.few_links + '</strong> <?php echo esc_js( __( 'Few links', 'raybogman-ai-content-orchestrator' ) ); ?></div>' +
				'<div class="rbco-filter-card" data-filter="outdated" style="cursor:pointer; padding:8px 14px; border-radius:4px; border:2px solid transparent; background:#78798222;"><strong>' + counts.outdated + '</strong> <?php echo esc_js( __( 'Outdated', 'raybogman-ai-content-orchestrator' ) ); ?></div>'
			);
			renderScanTable('all');
			$('#rbco-scan-all-results').slideDown(200);
		}).always(function() {
			$btn.prop('disabled', false).html('<span class="dashicons dashicons-search" style="vertical-align:text-bottom; font-size:16px; width:16px; height:16px; margin-right:4px;"></span> <?php echo esc_js( __( 'Analyze All Posts', 'raybogman-ai-content-orchestrator' ) ); ?>');
		});
	});

	function renderScanTable(filter) {
		var posts = window.rbcoScanData || [], labels = window.rbcoIssueLabels || {}, colors = window.rbcoIssueColors || {};
		var $tbody = $('#rbco-scan-all-table tbody').empty();
		var filtered = posts.filter(function(p) {
			if (filter === 'all') return true;
			if (filter === 'has_issues') return p.issues.length > 0;
			return p.issues.indexOf(filter) !== -1;
		});

		$.each(filtered, function(_, p) {
			var badges = '';
			if (p.issues.length === 0) {
				badges = '<span style="color:#00a32a;"><span class="dashicons dashicons-yes-alt" style="font-size:14px; width:14px; height:14px; vertical-align:text-bottom;"></span> <?php echo esc_js( __( 'Good', 'raybogman-ai-content-orchestrator' ) ); ?></span>';
			} else {
				$.each(p.issues, function(_, issue) {
					badges += '<span class="rbco-issue-badge" data-issue="' + issue + '" style="display:inline-block; padding:1px 8px; margin:1px 2px; border-radius:10px; font-size:11px; font-weight:600; cursor:pointer; background:' + (colors[issue] || '#787c82') + '22; color:' + (colors[issue] || '#787c82') + ';">' + (labels[issue] || issue) + '</span>';
				});
			}
			var ageStr = p.age_days < 30 ? p.age_days + 'd' : Math.round(p.age_days / 30) + 'mo';
			var fixBtn = p.issues.length > 0 ? '<button type="button" class="button button-small rbco-inline-fix-btn" data-post-id="' + p.id + '" data-issues="' + p.issues.join(',') + '" data-title="' + $('<span>').text(p.title).html() + '"><span class="dashicons dashicons-update" style="vertical-align:text-bottom; font-size:14px; width:14px; height:14px; margin-right:2px;"></span><?php echo esc_js( __( 'Fix', 'raybogman-ai-content-orchestrator' ) ); ?></button>' : '—';
			var checkHtml = p.issues.length > 0 ? '<input type="checkbox" class="rbco-scan-row-check" value="' + p.id + '" data-issues="' + p.issues.join(',') + '" />' : '';
			$tbody.append('<tr><td>' + checkHtml + '</td><td><a href="' + p.url + '" target="_blank" style="text-decoration:none; font-weight:500;">' + $('<span>').text(p.title).html() + '</a></td><td>' + p.word_count + '</td><td>' + ageStr + '</td><td>' + badges + '</td><td>' + fixBtn + '</td></tr>');
		});

		var hasIssues = filtered.filter(function(p) { return p.issues.length > 0; }).length;
		$('#rbco-fix-all-wrap').toggle(hasIssues > 0);
		$('#rbco-fix-filtered-count').text(hasIssues);
		$('#rbco-fix-all-count').text('0');
		$('#rbco-scan-check-all').prop('checked', false);
	}

	$(document).on('click', '.rbco-filter-card', function() {
		$('.rbco-filter-card').css('border-color', 'transparent').removeClass('rbco-filter-active');
		$(this).css('border-color', '#2271b1').addClass('rbco-filter-active');
		renderScanTable($(this).data('filter'));
	});

	$(document).on('click', '.rbco-issue-badge', function() {
		var issue = $(this).data('issue');
		$('.rbco-filter-card').css('border-color', 'transparent').removeClass('rbco-filter-active');
		$('.rbco-filter-card[data-filter="' + issue + '"]').css('border-color', '#2271b1').addClass('rbco-filter-active');
		renderScanTable(issue);
	});

	// Checkbox handlers for scan results.
	$(document).on('change', '#rbco-scan-check-all', function() {
		$('.rbco-scan-row-check').prop('checked', $(this).prop('checked'));
		$('#rbco-fix-all-count').text($('.rbco-scan-row-check:checked').length);
	});
	$(document).on('change', '.rbco-scan-row-check', function() {
		var checked = $('.rbco-scan-row-check:checked').length;
		var total = $('.rbco-scan-row-check').length;
		$('#rbco-fix-all-count').text(checked);
		$('#rbco-scan-check-all').prop('checked', checked === total && total > 0).prop('indeterminate', checked > 0 && checked < total);
	});

	// Fix Selected — only checked posts.
	$('#rbco-fix-selected-btn').on('click', function() {
		var ids = [];
		$('.rbco-scan-row-check:checked').each(function() { ids.push(parseInt($(this).val(), 10)); });
		if (!ids.length) { alert('<?php echo esc_js( __( 'Select posts to fix first.', 'raybogman-ai-content-orchestrator' ) ); ?>'); return; }
		var posts = (window.rbcoScanData || []).filter(function(p) { return ids.indexOf(p.id) !== -1; });
		runFixAll(posts);
	});

	// Fix All Filtered button.
	$('#rbco-fix-all-btn').on('click', function() {
		var filter = $('.rbco-filter-card.rbco-filter-active').data('filter') || 'has_issues';
		var posts = (window.rbcoScanData || []).filter(function(p) {
			if (p.issues.length === 0) return false;
			if (filter === 'all' || filter === 'has_issues') return true;
			return p.issues.indexOf(filter) !== -1;
		});
		if (posts.length) runFixAll(posts);
	});

	function runFixAll(posts) {
		if (!posts.length) return;
		if (!confirm('<?php echo esc_js( __( 'AI-refresh', 'raybogman-ai-content-orchestrator' ) ); ?> ' + posts.length + ' <?php echo esc_js( __( 'posts? Each takes 1-2 minutes.', 'raybogman-ai-content-orchestrator' ) ); ?>')) return;

		$('#rbco-fix-all-btn, #rbco-fix-selected-btn').prop('disabled', true);

		var $progress = $('<div id="rbco-fix-all-progress" style="margin-top:12px; padding:12px 16px; background:#f6f7f7; border:1px solid #e0e0e0; border-radius:4px;"><div class="rbco-fix-all-log" style="max-height:300px; overflow:auto; font-family:monospace; font-size:12px; line-height:1.8;"></div></div>');
		$('#rbco-fix-all-progress').remove();
		$('#rbco-scan-all-table').after($progress);
		var $log = $progress.find('.rbco-fix-all-log');

		var succeeded = 0, total = posts.length;

		function fixNext(idx) {
			if (idx >= total) {
				$log.append('<div style="font-weight:600; margin-top:8px;">━━━ ' + succeeded + '/' + total + ' <?php echo esc_js( __( 'posts refreshed successfully', 'raybogman-ai-content-orchestrator' ) ); ?> ━━━</div>');
				$log.append('<div style="margin-top:6px; color:#2271b1;"><?php echo esc_js( __( 'Re-scanning in 3 seconds...', 'raybogman-ai-content-orchestrator' ) ); ?></div>');
				$log.scrollTop($log[0].scrollHeight);
				setTimeout(function() { $('#rbco-scan-all-btn').trigger('click'); }, 3000);
				return;
			}

			var p = posts[idx];
			$log.append('<div style="margin-top:6px;"><strong><?php echo esc_js( __( 'Refreshing', 'raybogman-ai-content-orchestrator' ) ); ?> ' + (idx + 1) + '/' + total + ':</strong> ' + $('<span>').text(p.title).html() + ' <span class="spinner is-active" style="float:none; margin:0;"></span></div>');
			$log.scrollTop($log[0].scrollHeight);

			var issueKeys = [];
			$.each(p.issues, function(_, i) {
				var map = { thin: 'thin_sections', no_faq: 'missing_faq', few_links: 'missing_internal_links', outdated: 'outdated_content' };
				issueKeys.push(map[i] || i);
			});

			$.ajax({ url: ajaxUrl, type: 'POST', timeout: 300000, data: { action: 'rbco_refresh_post', nonce: nonce, post_id: p.id, 'issues[]': issueKeys }
			}).done(function(res) {
				if (res.success) {
					succeeded++;
					$log.append('<div style="color:#00a32a;"><span class="dashicons dashicons-yes-alt" style="font-size:14px; width:14px; height:14px; vertical-align:text-bottom;"></span> <?php echo esc_js( __( 'Done', 'raybogman-ai-content-orchestrator' ) ); ?> — ' + res.data.word_count + ' <?php echo esc_js( __( 'words', 'raybogman-ai-content-orchestrator' ) ); ?></div>');
				} else {
					$log.append('<div style="color:#d63638;"><span class="dashicons dashicons-warning" style="font-size:14px; width:14px; height:14px; vertical-align:text-bottom;"></span> <?php echo esc_js( __( 'Failed:', 'raybogman-ai-content-orchestrator' ) ); ?> ' + $('<span>').text(res.data.message || 'Error').html() + '</div>');
				}
			}).fail(function() {
				$log.append('<div style="color:#d63638;"><span class="dashicons dashicons-warning" style="font-size:14px; width:14px; height:14px; vertical-align:text-bottom;"></span> <?php echo esc_js( __( 'Request failed (timeout)', 'raybogman-ai-content-orchestrator' ) ); ?></div>');
			}).always(function() {
				$log.find('.spinner').last().remove();
				$log.scrollTop($log[0].scrollHeight);
				fixNext(idx + 1);
			});
		}
		fixNext(0);
	}

	// Inline Fix button — refresh a single post directly on this page.
	$(document).on('click', '.rbco-inline-fix-btn', function() {
		var $btn = $(this);
		var postId = $btn.data('post-id');
		var title = $btn.data('title');
		var issues = String($btn.data('issues')).split(',');
		var issueMap = { thin: 'thin_sections', no_faq: 'missing_faq', few_links: 'missing_internal_links', outdated: 'outdated_content' };
		var issueKeys = issues.map(function(i) { return issueMap[i] || i; });

		$btn.prop('disabled', true).html('<span class="spinner is-active" style="float:none; margin:0;"></span>');

		// Create a progress card below the table.
		var progressId = 'rbco-inline-progress-' + postId;
		$('#' + progressId).remove();
		var $progress = $(
			'<div id="' + progressId + '" class="rbco-card" style="margin-top:12px;">' +
				'<div class="rbco-card-header"><h2><span class="spinner is-active" style="float:none; margin:0 8px 0 0;"></span><?php echo esc_js( __( 'Progress', 'raybogman-ai-content-orchestrator' ) ); ?></h2></div>' +
				'<div class="rbco-card-body"><div class="rbco-log-box rbco-inline-log"><div class="rbco-log-line"><?php echo esc_js( __( 'Sending post to AI for refresh...', 'raybogman-ai-content-orchestrator' ) ); ?></div></div></div>' +
			'</div>'
		);
		$('#rbco-scan-all-table').after($progress);
		$('html, body').animate({ scrollTop: $progress.offset().top - 60 }, 300);

		$.ajax({ url: ajaxUrl, type: 'POST', timeout: 300000, data: { action: 'rbco_refresh_post', nonce: nonce, post_id: postId, 'issues[]': issueKeys }
		}).done(function(res) {
			$progress.find('.spinner').removeClass('is-active');
			$progress.find('.rbco-inline-log').append('<div class="rbco-log-line">' + (res.success ? '<?php echo esc_js( __( 'Post refreshed successfully!', 'raybogman-ai-content-orchestrator' ) ); ?>' : '<?php echo esc_js( __( 'Error:', 'raybogman-ai-content-orchestrator' ) ); ?> ' + $('<span>').text(res.data.message || 'Error').html()) + '</div>');

			if (res.success) {
				var d = res.data;
				var $result = $(
					'<div class="rbco-card rbco-result-card" style="margin-top:12px;">' +
						'<div class="rbco-card-header rbco-card-header-success"><h2><span class="dashicons dashicons-yes-alt" style="margin-right:6px;"></span>' + title + '</h2></div>' +
						'<div class="rbco-card-body">' +
							'<table class="widefat striped"><tbody>' +
								'<tr><td><strong><?php echo esc_js( __( 'Title', 'raybogman-ai-content-orchestrator' ) ); ?></strong></td><td>' + $('<span>').text(d.title).html() + '</td></tr>' +
								'<tr><td><strong><?php echo esc_js( __( 'New Word Count', 'raybogman-ai-content-orchestrator' ) ); ?></strong></td><td>' + d.word_count + ' <?php echo esc_js( __( 'words', 'raybogman-ai-content-orchestrator' ) ); ?></td></tr>' +
								'<tr><td><strong><?php echo esc_js( __( 'Issues Fixed', 'raybogman-ai-content-orchestrator' ) ); ?></strong></td><td>' + d.issues_fixed.length + '</td></tr>' +
								(d.links_added > 0 ? '<tr><td><strong><?php echo esc_js( __( 'Internal Links Added', 'raybogman-ai-content-orchestrator' ) ); ?></strong></td><td>' + d.links_added + '</td></tr>' : '') +
							'</tbody></table>' +
							'<div style="margin-top:16px;">' +
								'<a href="' + d.url + '" target="_blank" class="button button-primary"><span class="dashicons dashicons-external" style="vertical-align:text-bottom; font-size:16px; width:16px; height:16px; margin-right:4px;"></span><?php echo esc_js( __( 'View Post', 'raybogman-ai-content-orchestrator' ) ); ?></a> ' +
								'<a href="' + d.edit_url + '" target="_blank" class="button"><span class="dashicons dashicons-edit" style="vertical-align:text-bottom; font-size:16px; width:16px; height:16px; margin-right:4px;"></span><?php echo esc_js( __( 'Edit in WordPress', 'raybogman-ai-content-orchestrator' ) ); ?></a>' +
							'</div>' +
						'</div>' +
					'</div>'
				);
				$progress.after($result);
				$btn.closest('tr').find('.rbco-scan-row-check').prop('checked', false);
				$('html, body').animate({ scrollTop: $result.offset().top - 40 }, 300);
			}
		}).fail(function() {
			$progress.find('.spinner').removeClass('is-active');
			$progress.find('.rbco-inline-log').append('<div class="rbco-log-line error"><?php echo esc_js( __( 'Request failed (timeout)', 'raybogman-ai-content-orchestrator' ) ); ?></div>');
		}).always(function() {
			$btn.prop('disabled', false).html('<span class="dashicons dashicons-update" style="vertical-align:text-bottom; font-size:14px; width:14px; height:14px; margin-right:2px;"></span><?php echo esc_js( __( 'Fix', 'raybogman-ai-content-orchestrator' ) ); ?>');
		});
	});

	// ═══ INDIVIDUAL TAB ═══
	var $search = $('#rbco-refresh-search'), $select = $('#rbco-refresh-post');
	var $options = $select.length ? $select.find('option').not(':first').clone() : $();

	// Pre-select post if passed via URL.
	var urlParams = new URLSearchParams(window.location.search);
	if (urlParams.get('post_id') && $select.length) {
		$select.val(urlParams.get('post_id'));
		setTimeout(function() { $('#rbco-analyze-btn').trigger('click'); }, 300);
	}

	$search.on('input', function() {
		var term = $(this).val().toLowerCase(), $first = $select.find('option:first');
		$select.empty().append($first);
		$options.each(function() { if ($(this).text().toLowerCase().indexOf(term) !== -1) $select.append($(this).clone()); });
		$select.val('');
	});

	$('#rbco-analyze-btn').on('click', function() {
		var postId = $select.val();
		if (!postId) { alert('<?php echo esc_js( __( 'Select a post first.', 'raybogman-ai-content-orchestrator' ) ); ?>'); return; }
		var $btn = $(this);
		$btn.prop('disabled', true).html('<span class="spinner is-active" style="float:none; margin:0;"></span> <?php echo esc_js( __( 'Analyzing...', 'raybogman-ai-content-orchestrator' ) ); ?>');

		$.post(ajaxUrl, { action: 'rbco_analyze_post', nonce: nonce, post_id: postId }).done(function(res) {
			if (res.success) {
				var d = res.data;
				$('#rbco-analysis-title').text(d.title);
				$('#rbco-analysis-published').text(d.published);
				$('#rbco-analysis-updated').text(d.updated);
				$('#rbco-analysis-wordcount').text(d.word_count + ' <?php echo esc_js( __( 'words', 'raybogman-ai-content-orchestrator' ) ); ?>');
				var $list = $('#rbco-issues-list').empty();
				if (d.issues.length > 0) {
					$.each(d.issues, function(_, issue) {
						$list.append('<label class="rbco-checkbox-item" style="display:block; margin-bottom:8px;"><input type="checkbox" name="rbco-issues[]" value="' + issue.key + '" checked /> <span>' + $('<span>').text(issue.label).html() + '</span></label>');
					});
				} else {
					$list.html('<p style="color:#00a32a; font-weight:600;"><span class="dashicons dashicons-yes-alt" style="vertical-align:text-bottom;"></span> <?php echo esc_js( __( 'No issues detected! This post looks good.', 'raybogman-ai-content-orchestrator' ) ); ?></p>');
				}
				$('#rbco-refresh-btn').data('post-id', d.post_id);
				$('#rbco-analysis-card').slideDown(200);
				$('html, body').animate({ scrollTop: $('#rbco-analysis-card').offset().top - 40 }, 300);
			} else {
				alert(res.data.message || 'Analysis failed.');
			}
		}).fail(function() { alert('Request failed.'); }).always(function() {
			$btn.prop('disabled', false).html('<span class="dashicons dashicons-visibility rbco-btn-icon"></span> <?php echo esc_js( __( 'Analyze', 'raybogman-ai-content-orchestrator' ) ); ?>');
		});
	});

	$('#rbco-refresh-btn').on('click', function() {
		var $btn = $(this), postId = $btn.data('post-id'), issues = [];
		$('input[name="rbco-issues[]"]:checked').each(function() { issues.push($(this).val()); });
		if (!issues.length) { alert('<?php echo esc_js( __( 'Select at least one issue to fix.', 'raybogman-ai-content-orchestrator' ) ); ?>'); return; }
		if (!confirm('<?php echo esc_js( __( 'This will rewrite the post content with AI improvements. The original content will be replaced. Continue?', 'raybogman-ai-content-orchestrator' ) ); ?>')) return;

		$btn.prop('disabled', true).html('<span class="spinner is-active" style="float:none; margin:0;"></span> <?php echo esc_js( __( 'Refreshing... (1-2 minutes)', 'raybogman-ai-content-orchestrator' ) ); ?>');
		$('#rbco-refresh-log-area').slideDown(200);
		$('#rbco-refresh-log-box').html('<div class="rbco-log-line"><?php echo esc_js( __( 'Sending post to AI for refresh...', 'raybogman-ai-content-orchestrator' ) ); ?></div>');

		$.ajax({ url: ajaxUrl, type: 'POST', data: { action: 'rbco_refresh_post', nonce: nonce, post_id: postId, 'issues[]': issues }, timeout: 300000
		}).done(function(res) {
			$('#rbco-refresh-spinner').removeClass('is-active');
			if (res.success) {
				var d = res.data;
				$('#rbco-refresh-log-box').append('<div class="rbco-log-line success"><?php echo esc_js( __( 'Post refreshed successfully!', 'raybogman-ai-content-orchestrator' ) ); ?></div>');
				var $tbody = $('#rbco-refresh-result-table tbody').empty();
				$tbody.append('<tr><td><strong><?php echo esc_js( __( 'Title', 'raybogman-ai-content-orchestrator' ) ); ?></strong></td><td>' + $('<span>').text(d.title).html() + '</td></tr>');
				$tbody.append('<tr><td><strong><?php echo esc_js( __( 'New Word Count', 'raybogman-ai-content-orchestrator' ) ); ?></strong></td><td>' + d.word_count + ' <?php echo esc_js( __( 'words', 'raybogman-ai-content-orchestrator' ) ); ?></td></tr>');
				$tbody.append('<tr><td><strong><?php echo esc_js( __( 'Issues Fixed', 'raybogman-ai-content-orchestrator' ) ); ?></strong></td><td>' + d.issues_fixed.length + '</td></tr>');
				if (d.links_added > 0) $tbody.append('<tr><td><strong><?php echo esc_js( __( 'Internal Links Added', 'raybogman-ai-content-orchestrator' ) ); ?></strong></td><td>' + d.links_added + '</td></tr>');
				$('#rbco-refresh-view-post').attr('href', d.url);
				$('#rbco-refresh-edit-post').attr('href', d.edit_url);
				$('#rbco-refresh-result-card').slideDown(200);
				$('html, body').animate({ scrollTop: $('#rbco-refresh-result-card').offset().top - 40 }, 300);
			} else {
				$('#rbco-refresh-log-box').append('<div class="rbco-log-line error"><?php echo esc_js( __( 'Error:', 'raybogman-ai-content-orchestrator' ) ); ?> ' + $('<span>').text(res.data.message).html() + '</div>');
			}
		}).fail(function(xhr) {
			$('#rbco-refresh-spinner').removeClass('is-active');
			$('#rbco-refresh-log-box').append('<div class="rbco-log-line error"><?php echo esc_js( __( 'Request failed:', 'raybogman-ai-content-orchestrator' ) ); ?> ' + xhr.status + '</div>');
		}).always(function() {
			$btn.prop('disabled', false).html('<span class="dashicons dashicons-update rbco-btn-icon"></span> <?php echo esc_js( __( 'Refresh Post', 'raybogman-ai-content-orchestrator' ) ); ?>');
		});
	});
});
<?php
wp_add_inline_script( 'rbco-admin', ob_get_clean() );
?>
