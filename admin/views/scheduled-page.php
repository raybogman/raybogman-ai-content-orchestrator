<?php
/**
 * Scheduled content page — Human in the loop review queue.
 *
 * @package AI_Content_Creator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$aicc_items              = AICC_Publisher::get_scheduled_items();
$aicc_linkedin_items     = AICC_LinkedIn::is_connected() ? AICC_Publisher::get_published_with_linkedin_status( 20 ) : array();
$aicc_pending_count      = 0;
$aicc_future_count       = 0;
foreach ( $aicc_items as $aicc_item ) {
	if ( 'future' === $aicc_item['status'] ) {
		$aicc_future_count++;
	} elseif ( $aicc_item['needs_review'] ) {
		$aicc_pending_count++;
	}
}
$aicc_wp_cron_disabled = defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON;
$aicc_next_cron        = wp_next_scheduled( 'aicc_catch_up_scheduled' );
$aicc_last_catchup     = get_option( 'aicc_last_catchup_log', array() );
?>
<div class="wrap aicc-wrap">
	<h1 class="wp-heading-inline">
		<span class="dashicons dashicons-calendar-alt aicc-heading-icon"></span>
		<?php esc_html_e( 'AI Content Orchestrator — Scheduled', 'ai-content-orchestrator' ); ?>
	</h1>
	<p class="aicc-subtitle">
		<?php esc_html_e( 'Review queue for AI-generated content awaiting human approval, plus content already scheduled for publication.', 'ai-content-orchestrator' ); ?>
	</p>

	<?php if ( $aicc_wp_cron_disabled ) : ?>
		<div class="notice notice-warning inline">
			<p>
				<strong><?php esc_html_e( 'WordPress cron is disabled.', 'ai-content-orchestrator' ); ?></strong>
				<?php esc_html_e( 'DISABLE_WP_CRON is set on this site, so scheduled posts will not publish automatically. Either configure a real cron job, or use the "Publish now" button to publish items manually. This page also auto-publishes overdue items whenever you load it.', 'ai-content-orchestrator' ); ?>
			</p>
		</div>
	<?php endif; ?>

	<!-- Cron debug panel -->
	<div class="aicc-card" style="margin-bottom: 16px;">
		<div class="aicc-card-header">
			<h2>
				<span class="dashicons dashicons-clock" style="margin-right: 6px;"></span>
				<?php esc_html_e( 'Cron Status', 'ai-content-orchestrator' ); ?>
			</h2>
		</div>
		<div class="aicc-card-body">
			<table class="widefat" style="background: transparent; border: none;">
				<tbody>
					<tr>
						<td style="width: 220px; font-weight: 600; border: none;"><?php esc_html_e( 'Cron event:', 'ai-content-orchestrator' ); ?></td>
						<td style="border: none;"><code>aicc_catch_up_scheduled</code> &mdash; <?php esc_html_e( 'every minute', 'ai-content-orchestrator' ); ?></td>
					</tr>
					<tr>
						<td style="font-weight: 600; border: none;"><?php esc_html_e( 'Next scheduled run:', 'ai-content-orchestrator' ); ?></td>
						<td style="border: none;">
							<?php if ( $aicc_next_cron ) : ?>
								<?php echo esc_html( wp_date( 'Y-m-d H:i:s', $aicc_next_cron ) ); ?>
								(<?php echo esc_html( human_time_diff( time(), $aicc_next_cron ) ); ?>)
							<?php else : ?>
								<span style="color: #d63638;"><?php esc_html_e( 'Not scheduled', 'ai-content-orchestrator' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
					<?php if ( ! empty( $aicc_last_catchup ) ) : ?>
						<tr>
							<td style="font-weight: 600; border: none; vertical-align: top;"><?php esc_html_e( 'Last catch-up run:', 'ai-content-orchestrator' ); ?></td>
							<td style="border: none;">
								<?php echo esc_html( wp_date( 'Y-m-d H:i:s', $aicc_last_catchup['time'] ) ); ?>
								(<?php echo esc_html( human_time_diff( $aicc_last_catchup['time'], time() ) ); ?> ago)
								&mdash;
								<?php
								printf(
									/* translators: 1: found count, 2: published count */
									esc_html__( 'found %1$d future posts, published %2$d', 'ai-content-orchestrator' ),
									(int) $aicc_last_catchup['found'],
									(int) $aicc_last_catchup['published']
								);
								?>
								<?php if ( ! empty( $aicc_last_catchup['details'] ) ) : ?>
									<details style="margin-top: 6px;">
										<summary style="cursor: pointer; color: #2271b1;"><?php esc_html_e( 'Show details', 'ai-content-orchestrator' ); ?></summary>
										<pre style="background: #f0f0f1; padding: 10px; border-radius: 3px; font-size: 12px; margin-top: 6px;"><?php echo esc_html( implode( "\n", $aicc_last_catchup['details'] ) ); ?></pre>
									</details>
								<?php endif; ?>
							</td>
						</tr>
					<?php endif; ?>
					<tr>
						<td style="font-weight: 600; border: none;"><?php esc_html_e( 'Manual trigger:', 'ai-content-orchestrator' ); ?></td>
						<td style="border: none;">
							<button type="button" class="button" id="aicc-run-catchup-now">
								<span class="dashicons dashicons-update" style="vertical-align: text-bottom; font-size: 16px; width: 16px; height: 16px;"></span>
								<?php esc_html_e( 'Run catch-up now', 'ai-content-orchestrator' ); ?>
							</button>
							<span id="aicc-catchup-result" style="margin-left: 12px;"></span>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>

	<!-- Stats bar -->
	<div class="aicc-status-bar">
		<span class="aicc-status-item">
			<strong><?php esc_html_e( 'Awaiting review:', 'ai-content-orchestrator' ); ?></strong>
			<span class="aicc-badge <?php echo $aicc_pending_count > 0 ? 'aicc-badge-warning' : 'aicc-badge-success'; ?>">
				<?php echo esc_html( $aicc_pending_count ); ?>
			</span>
		</span>
		<span class="aicc-status-item">
			<strong><?php esc_html_e( 'Scheduled:', 'ai-content-orchestrator' ); ?></strong>
			<span class="aicc-badge aicc-badge-success"><?php echo esc_html( $aicc_future_count ); ?></span>
		</span>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=aicc-create' ) ); ?>" class="aicc-status-item aicc-status-link">
			<?php esc_html_e( 'Create New Content', 'ai-content-orchestrator' ); ?> &rarr;
		</a>
	</div>

	<?php if ( empty( $aicc_items ) ) : ?>
		<div class="aicc-card">
			<div class="aicc-card-body" style="text-align: center; padding: 40px 20px;">
				<span class="dashicons dashicons-calendar" style="font-size: 48px; width: 48px; height: 48px; color: #c3c4c7;"></span>
				<h2 style="margin-top: 16px;"><?php esc_html_e( 'No scheduled content yet', 'ai-content-orchestrator' ); ?></h2>
				<p class="description" style="max-width: 500px; margin: 8px auto 16px;">
					<?php esc_html_e( 'Create new content and select "Schedule for later" to see it appear here. Drafts will wait for your approval; published items schedule automatically.', 'ai-content-orchestrator' ); ?>
				</p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=aicc-create' ) ); ?>" class="button button-primary">
					<?php esc_html_e( 'Create Content', 'ai-content-orchestrator' ); ?>
				</a>
			</div>
		</div>
	<?php else : ?>

		<!-- Upcoming Publications Timeline -->
		<?php
		$aicc_upcoming = array();
		foreach ( $aicc_items as $aicc_item ) {
			if ( 'future' === $aicc_item['status'] || ( $aicc_item['needs_review'] && ! empty( $aicc_item['scheduled_at'] ) ) ) {
				$aicc_upcoming[] = $aicc_item;
			}
		}
		usort( $aicc_upcoming, function( $a, $b ) {
			return ( $a['scheduled_at'] ?? 0 ) - ( $b['scheduled_at'] ?? 0 );
		});
		?>
		<?php if ( ! empty( $aicc_upcoming ) ) : ?>
		<div class="aicc-card" style="margin-bottom:20px;">
			<div class="aicc-card-header">
				<h2>
					<span class="dashicons dashicons-schedule" style="margin-right:6px; color:#2271b1;"></span>
					<?php esc_html_e( 'Upcoming Publications', 'ai-content-orchestrator' ); ?>
				</h2>
			</div>
			<div class="aicc-card-body">
				<div style="position:relative; padding-left:24px;">
					<?php foreach ( $aicc_upcoming as $idx => $up ) :
						$is_future  = ( 'future' === $up['status'] );
						$is_pending = $up['needs_review'];
						$dot_color  = $is_future ? '#00a32a' : '#dba617';
						$status_label = $is_future
							? __( 'Scheduled', 'ai-content-orchestrator' )
							: __( 'Awaiting approval', 'ai-content-orchestrator' );
						$date_str = ! empty( $up['scheduled_at_formatted'] ) ? $up['scheduled_at_formatted'] : __( 'Publish on approval', 'ai-content-orchestrator' );

						$days_until = '';
						if ( ! empty( $up['scheduled_at'] ) && $up['scheduled_at'] > 0 ) {
							$diff = $up['scheduled_at'] - time();
							if ( $diff > 0 ) {
								$days = ceil( $diff / DAY_IN_SECONDS );
								$days_until = sprintf(
									/* translators: %s: human-readable time */
									__( 'in %s', 'ai-content-orchestrator' ),
									human_time_diff( time(), $up['scheduled_at'] )
								);
							}
						}
					?>
					<div style="position:relative; padding-bottom:<?php echo $idx < count( $aicc_upcoming ) - 1 ? '20px' : '0'; ?>; <?php echo $idx < count( $aicc_upcoming ) - 1 ? 'border-left:2px solid #e0e0e0; margin-left:6px; padding-left:22px;' : 'margin-left:6px; padding-left:22px;'; ?>">
						<div style="position:absolute; left:-8px; top:2px; width:14px; height:14px; background:<?php echo esc_attr( $dot_color ); ?>; border-radius:50%; border:2px solid #fff;"></div>
						<div style="display:flex; align-items:baseline; gap:8px; flex-wrap:wrap;">
							<strong style="font-size:13px;"><?php echo esc_html( $date_str ); ?></strong>
							<?php if ( $days_until ) : ?>
								<span style="color:#787c82; font-size:12px;">(<?php echo esc_html( $days_until ); ?>)</span>
							<?php endif; ?>
							<span style="display:inline-block; padding:1px 8px; border-radius:10px; font-size:11px; font-weight:600; background:<?php echo $is_future ? '#e7f5e7' : '#fff8e5'; ?>; color:<?php echo $is_future ? '#00a32a' : '#996800'; ?>;">
								<?php echo esc_html( $status_label ); ?>
							</span>
						</div>
						<div style="margin-top:2px;">
							<a href="<?php echo esc_url( get_permalink( $up['id'] ) ); ?>" target="_blank" style="text-decoration:none; font-weight:500;">
								<?php echo esc_html( $up['title'] ); ?>
							</a>
							<?php if ( ! empty( $up['linkedin'] ) ) : ?>
								<span class="dashicons dashicons-linkedin" style="color:#0a66c2; vertical-align:text-bottom; font-size:14px; width:14px; height:14px; margin-left:4px;" title="<?php esc_attr_e( 'LinkedIn', 'ai-content-orchestrator' ); ?>"></span>
							<?php endif; ?>
						</div>
					</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
		<?php endif; ?>

		<!-- Pending review items -->
		<?php if ( $aicc_pending_count > 0 ) : ?>
			<div class="aicc-card">
				<div class="aicc-card-header">
					<h2>
						<span class="dashicons dashicons-clock" style="margin-right: 6px; color: #dba617;"></span>
						<?php esc_html_e( 'Awaiting Human Review', 'ai-content-orchestrator' ); ?>
					</h2>
				</div>
				<div class="aicc-card-body" style="padding: 0;">
					<div id="aicc-bulk-actions-bar" style="display:none; padding:8px 12px; background:#f0f6fc; border-bottom:1px solid #c3c4c7;">
						<label style="margin-right:8px;"><input type="checkbox" id="aicc-review-check-all" /> <?php esc_html_e( 'Select all', 'ai-content-orchestrator' ); ?></label>
						<button type="button" class="button" id="aicc-bulk-delete-btn">
							<span class="dashicons dashicons-trash" style="vertical-align:text-bottom; font-size:16px; width:16px; height:16px; color:#d63638; margin-right:2px;"></span>
							<?php esc_html_e( 'Delete Selected', 'ai-content-orchestrator' ); ?>
						</button>
						<button type="button" class="button" id="aicc-bulk-approve-btn" style="margin-left:4px;">
							<span class="dashicons dashicons-yes" style="vertical-align:text-bottom; font-size:16px; width:16px; height:16px; color:#00a32a; margin-right:2px;"></span>
							<?php esc_html_e( 'Approve Selected', 'ai-content-orchestrator' ); ?>
						</button>
						<span id="aicc-bulk-action-status" style="margin-left:12px;"></span>
					</div>
					<table class="widefat striped aicc-scheduled-table">
						<thead>
							<tr>
								<th style="width:30px;"><input type="checkbox" id="aicc-review-check-all-th" /></th>
								<th><?php esc_html_e( 'Title', 'ai-content-orchestrator' ); ?></th>
								<th style="width: 90px;"><?php esc_html_e( 'Type', 'ai-content-orchestrator' ); ?></th>
								<th><?php esc_html_e( 'Categories', 'ai-content-orchestrator' ); ?></th>
								<th style="width: 180px;"><?php esc_html_e( 'Scheduled For', 'ai-content-orchestrator' ); ?></th>
								<th class="aicc-actions-col"><?php esc_html_e( 'Actions', 'ai-content-orchestrator' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $aicc_items as $aicc_item ) : ?>
								<?php if ( ! $aicc_item['needs_review'] ) continue; ?>
								<tr id="aicc-row-<?php echo esc_attr( $aicc_item['id'] ); ?>" data-post-id="<?php echo esc_attr( $aicc_item['id'] ); ?>" data-schedule-at="<?php echo esc_attr( $aicc_item['scheduled_at'] ); ?>">
									<td><input type="checkbox" class="aicc-review-check" value="<?php echo esc_attr( $aicc_item['id'] ); ?>" /></td>
									<td>
										<strong><?php echo esc_html( $aicc_item['title'] ); ?></strong>
										<?php if ( ! empty( $aicc_item['linkedin'] ) ) : ?>
											<span class="dashicons dashicons-linkedin" style="color: #0a66c2; vertical-align: text-bottom; font-size: 18px; width: 18px; height: 18px; margin-left: 4px;" title="<?php esc_attr_e( 'Will be shared to LinkedIn when published', 'ai-content-orchestrator' ); ?>"></span>
										<?php endif; ?>
										<?php if ( ! empty( $aicc_item['focus_keyphrase'] ) ) : ?>
											<br><small class="description"><?php esc_html_e( 'Focus:', 'ai-content-orchestrator' ); ?> <?php echo esc_html( $aicc_item['focus_keyphrase'] ); ?></small>
										<?php endif; ?>
									</td>
									<td>
										<span class="aicc-tag">
											<?php echo 'post' === $aicc_item['type'] ? esc_html__( 'Blog', 'ai-content-orchestrator' ) : esc_html__( 'Page', 'ai-content-orchestrator' ); ?>
										</span>
									</td>
									<td>
										<?php if ( ! empty( $aicc_item['categories'] ) ) : ?>
											<?php foreach ( $aicc_item['categories'] as $cat ) : ?>
												<span class="aicc-tag"><?php echo esc_html( $cat ); ?></span>
											<?php endforeach; ?>
										<?php else : ?>
											<span class="description">&mdash;</span>
										<?php endif; ?>
									</td>
									<td class="aicc-schedule-cell">
										<span class="aicc-schedule-display"><?php echo esc_html( $aicc_item['scheduled_at_formatted'] ); ?></span>
									</td>
									<td class="aicc-actions-cell">
										<button type="button" class="button button-primary aicc-approve-btn" data-post-id="<?php echo esc_attr( $aicc_item['id'] ); ?>">
											<span class="dashicons dashicons-yes" style="vertical-align: text-bottom; font-size: 16px; width: 16px; height: 16px;"></span>
											<?php esc_html_e( 'Approve', 'ai-content-orchestrator' ); ?>
										</button>
										<a href="<?php echo esc_url( $aicc_item['edit_url'] ); ?>" class="button" target="_blank" title="<?php esc_attr_e( 'Edit', 'ai-content-orchestrator' ); ?>">
											<span class="dashicons dashicons-edit" style="vertical-align: text-bottom; font-size: 16px; width: 16px; height: 16px;"></span>
										</a>
										<button type="button" class="button aicc-reschedule-btn" data-post-id="<?php echo esc_attr( $aicc_item['id'] ); ?>" title="<?php esc_attr_e( 'Reschedule', 'ai-content-orchestrator' ); ?>">
											<span class="dashicons dashicons-calendar-alt" style="vertical-align: text-bottom; font-size: 16px; width: 16px; height: 16px;"></span>
										</button>
										<button type="button" class="button aicc-delete-btn" data-post-id="<?php echo esc_attr( $aicc_item['id'] ); ?>" title="<?php esc_attr_e( 'Delete', 'ai-content-orchestrator' ); ?>">
											<span class="dashicons dashicons-trash" style="vertical-align: text-bottom; font-size: 16px; width: 16px; height: 16px; color: #d63638;"></span>
										</button>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
		<?php endif; ?>

		<!-- Already scheduled items (future status) -->
		<?php if ( $aicc_future_count > 0 ) : ?>
			<div class="aicc-card">
				<div class="aicc-card-header">
					<h2>
						<span class="dashicons dashicons-calendar-alt" style="margin-right: 6px; color: #00a32a;"></span>
						<?php esc_html_e( 'Scheduled for Publication', 'ai-content-orchestrator' ); ?>
					</h2>
				</div>
				<div class="aicc-card-body" style="padding: 0;">
					<table class="widefat striped aicc-scheduled-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Title', 'ai-content-orchestrator' ); ?></th>
								<th style="width: 90px;"><?php esc_html_e( 'Type', 'ai-content-orchestrator' ); ?></th>
								<th><?php esc_html_e( 'Categories', 'ai-content-orchestrator' ); ?></th>
								<th style="width: 180px;"><?php esc_html_e( 'Publishes At', 'ai-content-orchestrator' ); ?></th>
								<th class="aicc-actions-col"><?php esc_html_e( 'Actions', 'ai-content-orchestrator' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $aicc_items as $aicc_item ) : ?>
								<?php if ( 'future' !== $aicc_item['status'] ) continue; ?>
								<tr id="aicc-row-<?php echo esc_attr( $aicc_item['id'] ); ?>" data-post-id="<?php echo esc_attr( $aicc_item['id'] ); ?>" data-schedule-at="<?php echo esc_attr( $aicc_item['scheduled_at'] ); ?>">
									<td>
										<strong><?php echo esc_html( $aicc_item['title'] ); ?></strong>
										<?php if ( ! empty( $aicc_item['linkedin'] ) ) : ?>
											<span class="dashicons dashicons-linkedin" style="color: #0a66c2; vertical-align: text-bottom; font-size: 18px; width: 18px; height: 18px; margin-left: 4px;" title="<?php esc_attr_e( 'Will be shared to LinkedIn when published', 'ai-content-orchestrator' ); ?>"></span>
										<?php endif; ?>
										<?php if ( ! empty( $aicc_item['focus_keyphrase'] ) ) : ?>
											<br><small class="description"><?php esc_html_e( 'Focus:', 'ai-content-orchestrator' ); ?> <?php echo esc_html( $aicc_item['focus_keyphrase'] ); ?></small>
										<?php endif; ?>
									</td>
									<td>
										<span class="aicc-tag">
											<?php echo 'post' === $aicc_item['type'] ? esc_html__( 'Blog', 'ai-content-orchestrator' ) : esc_html__( 'Page', 'ai-content-orchestrator' ); ?>
										</span>
									</td>
									<td>
										<?php if ( ! empty( $aicc_item['categories'] ) ) : ?>
											<?php foreach ( $aicc_item['categories'] as $cat ) : ?>
												<span class="aicc-tag"><?php echo esc_html( $cat ); ?></span>
											<?php endforeach; ?>
										<?php else : ?>
											<span class="description">&mdash;</span>
										<?php endif; ?>
									</td>
									<td class="aicc-schedule-cell">
										<span class="aicc-schedule-display"><?php echo esc_html( $aicc_item['scheduled_at_formatted'] ); ?></span>
									</td>
									<td class="aicc-actions-cell">
										<button type="button" class="button button-primary aicc-publish-now-btn" data-post-id="<?php echo esc_attr( $aicc_item['id'] ); ?>" title="<?php esc_attr_e( 'Publish immediately (skip waiting for scheduled time)', 'ai-content-orchestrator' ); ?>">
											<span class="dashicons dashicons-megaphone" style="vertical-align: text-bottom; font-size: 16px; width: 16px; height: 16px;"></span>
											<?php esc_html_e( 'Publish Now', 'ai-content-orchestrator' ); ?>
										</button>
										<a href="<?php echo esc_url( $aicc_item['edit_url'] ); ?>" class="button" target="_blank" title="<?php esc_attr_e( 'Edit', 'ai-content-orchestrator' ); ?>">
											<span class="dashicons dashicons-edit" style="vertical-align: text-bottom; font-size: 16px; width: 16px; height: 16px;"></span>
										</a>
										<button type="button" class="button aicc-reschedule-btn" data-post-id="<?php echo esc_attr( $aicc_item['id'] ); ?>" title="<?php esc_attr_e( 'Reschedule', 'ai-content-orchestrator' ); ?>">
											<span class="dashicons dashicons-calendar-alt" style="vertical-align: text-bottom; font-size: 16px; width: 16px; height: 16px;"></span>
										</button>
										<button type="button" class="button aicc-delete-btn" data-post-id="<?php echo esc_attr( $aicc_item['id'] ); ?>" title="<?php esc_attr_e( 'Delete', 'ai-content-orchestrator' ); ?>">
											<span class="dashicons dashicons-trash" style="vertical-align: text-bottom; font-size: 16px; width: 16px; height: 16px; color: #d63638;"></span>
										</button>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
		<?php endif; ?>

	<?php endif; ?>

	<!-- LinkedIn sharing status -->
	<?php if ( ! empty( $aicc_linkedin_items ) ) : ?>
		<div class="aicc-card" style="margin-top: 20px;">
			<div class="aicc-card-header">
				<h2>
					<span class="dashicons dashicons-linkedin" style="margin-right: 6px; color: #0a66c2;"></span>
					<?php esc_html_e( 'LinkedIn Sharing Status', 'ai-content-orchestrator' ); ?>
				</h2>
			</div>
			<div class="aicc-card-body" style="padding: 0;">
				<!-- Bulk action toolbar -->
				<div class="aicc-li-bulk-toolbar" style="padding: 10px 12px; border-bottom: 1px solid #c3c4c7; background: #f6f7f7; display: flex; align-items: center; gap: 10px;">
					<button type="button" class="button aicc-li-bulk-delete-btn" disabled>
						<span class="dashicons dashicons-trash" style="vertical-align: text-bottom; font-size: 16px; width: 16px; height: 16px; color: #d63638;"></span>
						<?php esc_html_e( 'Delete Selected', 'ai-content-orchestrator' ); ?>
						(<span class="aicc-li-bulk-count">0</span>)
					</button>
					<span class="description" style="margin-left: auto; font-size: 12px;">
						<?php esc_html_e( 'Bulk delete removes posts from this dashboard only. Does not delete WordPress posts or LinkedIn shares.', 'ai-content-orchestrator' ); ?>
					</span>
				</div>
				<table class="widefat striped">
					<thead>
						<tr>
							<th style="width: 30px; padding-left: 12px;">
								<input type="checkbox" class="aicc-li-select-all" title="<?php esc_attr_e( 'Select all', 'ai-content-orchestrator' ); ?>" />
							</th>
							<th><?php esc_html_e( 'Title', 'ai-content-orchestrator' ); ?></th>
							<th style="width: 160px;"><?php esc_html_e( 'Published', 'ai-content-orchestrator' ); ?></th>
							<th style="width: 200px;"><?php esc_html_e( 'LinkedIn Status', 'ai-content-orchestrator' ); ?></th>
							<th style="width: 180px;"><?php esc_html_e( 'Actions', 'ai-content-orchestrator' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $aicc_linkedin_items as $li ) : ?>
							<tr id="aicc-li-row-<?php echo esc_attr( $li['id'] ); ?>">
								<td style="padding-left: 12px;">
									<input type="checkbox" class="aicc-li-row-check" value="<?php echo esc_attr( $li['id'] ); ?>" />
								</td>
								<td>
									<strong><?php echo esc_html( $li['title'] ); ?></strong>
									<br>
									<a href="<?php echo esc_url( $li['url'] ); ?>" target="_blank" class="description">
										<?php esc_html_e( 'View on WordPress', 'ai-content-orchestrator' ); ?> &rarr;
									</a>
									<?php if ( ! empty( $li['linkedin_commentary'] ) ) : ?>
										<br>
										<a href="#" class="aicc-li-toggle-preview" data-post-id="<?php echo esc_attr( $li['id'] ); ?>" style="font-size: 12px;">
											<span class="dashicons dashicons-visibility" style="font-size: 14px; width: 14px; height: 14px; vertical-align: text-bottom;"></span>
											<?php esc_html_e( 'Show LinkedIn post preview', 'ai-content-orchestrator' ); ?>
										</a>
										<div class="aicc-li-preview" id="aicc-li-preview-<?php echo esc_attr( $li['id'] ); ?>" style="display: none; margin-top: 8px;" data-post-id="<?php echo esc_attr( $li['id'] ); ?>">
											<!-- View mode -->
											<div class="aicc-li-preview-view">
												<div class="aicc-li-preview-text" style="background: #f6f7f7; border-left: 3px solid #0a66c2; padding: 10px; white-space: pre-wrap; font-family: -apple-system, BlinkMacSystemFont, sans-serif; font-size: 13px; line-height: 1.5; max-width: 500px; border-radius: 2px;"><?php echo esc_html( $li['linkedin_commentary'] ); ?></div>
												<p style="margin: 6px 0 0; font-size: 11px; color: #646970;">
													<span class="aicc-li-char-count">
														<?php
														printf(
															/* translators: %d: character count */
															esc_html__( '%d characters', 'ai-content-orchestrator' ),
															mb_strlen( $li['linkedin_commentary'] )
														);
														?>
													</span>
												</p>
												<p style="margin: 8px 0 0;">
													<button type="button" class="button button-small aicc-li-edit-btn" data-post-id="<?php echo esc_attr( $li['id'] ); ?>">
														<span class="dashicons dashicons-edit" style="font-size: 14px; width: 14px; height: 14px; vertical-align: text-bottom;"></span>
														<?php esc_html_e( 'Edit', 'ai-content-orchestrator' ); ?>
													</button>
													<button type="button" class="button button-small aicc-li-regen-btn" data-post-id="<?php echo esc_attr( $li['id'] ); ?>">
														<span class="dashicons dashicons-update" style="font-size: 14px; width: 14px; height: 14px; vertical-align: text-bottom;"></span>
														<?php esc_html_e( 'Regenerate', 'ai-content-orchestrator' ); ?>
													</button>
												</p>
											</div>
											<!-- Edit mode (hidden by default) -->
											<div class="aicc-li-preview-edit" style="display: none;">
												<textarea class="aicc-li-edit-textarea" rows="10" maxlength="2900" style="width: 100%; max-width: 500px; font-family: -apple-system, BlinkMacSystemFont, sans-serif; font-size: 13px; line-height: 1.5; border-left: 3px solid #0a66c2;"><?php echo esc_textarea( $li['linkedin_commentary'] ); ?></textarea>
												<p style="margin: 4px 0 0; font-size: 11px; color: #646970;">
													<span class="aicc-li-edit-count">
														<?php
														printf(
															/* translators: %d: character count */
															esc_html__( '%d / 2900 characters', 'ai-content-orchestrator' ),
															mb_strlen( $li['linkedin_commentary'] )
														);
														?>
													</span>
												</p>
												<p style="margin: 8px 0 0;">
													<button type="button" class="button button-primary button-small aicc-li-save-btn" data-post-id="<?php echo esc_attr( $li['id'] ); ?>">
														<?php esc_html_e( 'Save', 'ai-content-orchestrator' ); ?>
													</button>
													<button type="button" class="button button-small aicc-li-cancel-btn" data-post-id="<?php echo esc_attr( $li['id'] ); ?>">
														<?php esc_html_e( 'Cancel', 'ai-content-orchestrator' ); ?>
													</button>
												</p>
											</div>
										</div>
									<?php endif; ?>
								</td>
								<td>
									<?php echo esc_html( wp_date( 'Y-m-d H:i', $li['published_at'] ) ); ?>
								</td>
								<td class="aicc-li-status-<?php echo esc_attr( $li['id'] ); ?>">
									<?php if ( 'shared' === $li['linkedin_status'] ) : ?>
										<span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span>
										<strong style="color: #00a32a;"><?php esc_html_e( 'Shared', 'ai-content-orchestrator' ); ?></strong>
										<br><small class="description"><?php echo esc_html( wp_date( 'Y-m-d H:i', $li['shared_at'] ) ); ?></small>
									<?php elseif ( 'error' === $li['linkedin_status'] ) : ?>
										<span class="dashicons dashicons-warning" style="color: #d63638;"></span>
										<strong style="color: #d63638;"><?php esc_html_e( 'Failed', 'ai-content-orchestrator' ); ?></strong>
										<br><small class="description" style="color: #d63638;"><?php echo esc_html( mb_substr( $li['linkedin_error'], 0, 100 ) ); ?></small>
									<?php else : ?>
										<span class="dashicons dashicons-minus" style="color: #646970;"></span>
										<em class="description"><?php esc_html_e( 'Not shared yet', 'ai-content-orchestrator' ); ?></em>
									<?php endif; ?>
								</td>
								<td>
									<button type="button" class="button aicc-li-share-btn" data-post-id="<?php echo esc_attr( $li['id'] ); ?>">
										<span class="dashicons dashicons-linkedin" style="vertical-align: text-bottom; font-size: 16px; width: 16px; height: 16px;"></span>
										<?php
										if ( 'shared' === $li['linkedin_status'] ) {
											esc_html_e( 'Re-share', 'ai-content-orchestrator' );
										} elseif ( 'error' === $li['linkedin_status'] ) {
											esc_html_e( 'Retry', 'ai-content-orchestrator' );
										} else {
											esc_html_e( 'Share Now', 'ai-content-orchestrator' );
										}
										?>
									</button>
									<button type="button" class="button aicc-li-remove-btn" data-post-id="<?php echo esc_attr( $li['id'] ); ?>" title="<?php esc_attr_e( 'Remove from LinkedIn dashboard (does not delete the WordPress post or LinkedIn share)', 'ai-content-orchestrator' ); ?>">
										<span class="dashicons dashicons-trash" style="vertical-align: text-bottom; font-size: 16px; width: 16px; height: 16px; color: #d63638;"></span>
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<p class="description" style="padding: 12px 16px; margin: 0; background: #f6f7f7; border-top: 1px solid #c3c4c7;">
					<span class="dashicons dashicons-info" style="color: #2271b1; vertical-align: text-bottom;"></span>
					<?php esc_html_e( 'Check your LinkedIn profile feed at', 'ai-content-orchestrator' ); ?>
					<a href="https://www.linkedin.com/in/me/recent-activity/all/" target="_blank">linkedin.com/in/me/recent-activity</a>
					<?php esc_html_e( 'to see shared posts. Posts may take a few seconds to appear.', 'ai-content-orchestrator' ); ?>
				</p>
			</div>
		</div>
	<?php endif; ?>
</div>

<!-- Reschedule modal -->
<div id="aicc-reschedule-modal" class="aicc-modal" style="display: none;">
	<div class="aicc-modal-backdrop"></div>
	<div class="aicc-modal-content">
		<h2><?php esc_html_e( 'Reschedule', 'ai-content-orchestrator' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Select a new date and time for this item.', 'ai-content-orchestrator' ); ?></p>
		<input type="datetime-local" id="aicc-reschedule-input" class="regular-text" />
		<p style="margin-top: 16px; text-align: right;">
			<button type="button" class="button" id="aicc-reschedule-cancel"><?php esc_html_e( 'Cancel', 'ai-content-orchestrator' ); ?></button>
			<button type="button" class="button button-primary" id="aicc-reschedule-save"><?php esc_html_e( 'Save', 'ai-content-orchestrator' ); ?></button>
		</p>
	</div>
</div>

<script>
(function($) {
	var ajaxUrl = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';
	var nonce   = '<?php echo esc_js( wp_create_nonce( 'aicc_nonce' ) ); ?>';
	var rescheduleTargetId = null;

	// Approve button
	$('.aicc-approve-btn').on('click', function() {
		var $btn    = $(this);
		var postId  = $btn.data('post-id');
		var $row    = $('#aicc-row-' + postId);

		$btn.prop('disabled', true).text('Approving...');

		$.post(ajaxUrl, {
			action:  'aicc_approve_scheduled',
			nonce:   nonce,
			post_id: postId,
		}, function(response) {
			if (response.success) {
				$row.fadeOut(400, function() { $row.remove(); });
				// Reload after a short delay to refresh counts.
				setTimeout(function() { location.reload(); }, 600);
			} else {
				alert('Error: ' + (response.data.message || 'Unknown error'));
				$btn.prop('disabled', false).html('<span class="dashicons dashicons-yes"></span> Approve');
			}
		}).fail(function() {
			alert('Request failed.');
			$btn.prop('disabled', false).html('<span class="dashicons dashicons-yes"></span> Approve');
		});
	});

	// Run catch-up now button (cron debug)
	$('#aicc-run-catchup-now').on('click', function() {
		var $btn    = $(this);
		var $result = $('#aicc-catchup-result');

		$btn.prop('disabled', true);
		$result.html('<span class="spinner is-active" style="float:none; margin:0;"></span>');

		$.post(ajaxUrl, {
			action: 'aicc_run_catchup',
			nonce:  nonce,
		}, function(response) {
			if (response.success) {
				var msg = 'Ran catch-up: found ' + response.data.found + ' future posts, published ' + response.data.published + '.';
				$result.html('<span style="color:#00a32a; font-weight:600;">' + msg + '</span>');
				setTimeout(function() { location.reload(); }, 1500);
			} else {
				$result.html('<span style="color:#d63638;">Error: ' + (response.data.message || 'Unknown error') + '</span>');
				$btn.prop('disabled', false);
			}
		}).fail(function() {
			$result.html('<span style="color:#d63638;">Request failed.</span>');
			$btn.prop('disabled', false);
		});
	});

	// Publish now button
	$('.aicc-publish-now-btn').on('click', function() {
		if (!confirm('<?php echo esc_js( __( 'Publish this post immediately, ignoring the scheduled time?', 'ai-content-orchestrator' ) ); ?>')) return;

		var $btn    = $(this);
		var postId  = $btn.data('post-id');
		var $row    = $('#aicc-row-' + postId);

		$btn.prop('disabled', true).text('Publishing...');

		$.post(ajaxUrl, {
			action:  'aicc_publish_now',
			nonce:   nonce,
			post_id: postId,
		}, function(response) {
			if (response.success) {
				$row.fadeOut(400, function() {
					$row.remove();
					setTimeout(function() { location.reload(); }, 400);
				});
			} else {
				alert('Error: ' + (response.data.message || 'Unknown error'));
				$btn.prop('disabled', false).html('<span class="dashicons dashicons-megaphone"></span> Publish Now');
			}
		}).fail(function() {
			alert('Request failed.');
			$btn.prop('disabled', false).html('<span class="dashicons dashicons-megaphone"></span> Publish Now');
		});
	});

	// Delete button
	$('.aicc-delete-btn').on('click', function() {
		if (!confirm('<?php echo esc_js( __( 'Move this item to the trash?', 'ai-content-orchestrator' ) ); ?>')) return;

		var $btn   = $(this);
		var postId = $btn.data('post-id');
		var $row   = $('#aicc-row-' + postId);

		$btn.prop('disabled', true);

		$.post(ajaxUrl, {
			action:  'aicc_delete_scheduled',
			nonce:   nonce,
			post_id: postId,
		}, function(response) {
			if (response.success) {
				$row.fadeOut(400, function() { $row.remove(); setTimeout(function() { location.reload(); }, 400); });
			} else {
				alert('Error: ' + (response.data.message || 'Unknown error'));
				$btn.prop('disabled', false);
			}
		});
	});

	// LinkedIn bulk select — update count and toggle button.
	function aiccUpdateLiBulkState() {
		var count = $('.aicc-li-row-check:checked').length;
		$('.aicc-li-bulk-count').text(count);
		$('.aicc-li-bulk-delete-btn').prop('disabled', count === 0);

		// Sync select-all checkbox state.
		var total = $('.aicc-li-row-check').length;
		var $all  = $('.aicc-li-select-all');
		if (count === 0) {
			$all.prop('checked', false).prop('indeterminate', false);
		} else if (count === total) {
			$all.prop('checked', true).prop('indeterminate', false);
		} else {
			$all.prop('checked', false).prop('indeterminate', true);
		}
	}

	// Select-all checkbox toggles all row checkboxes.
	$(document).on('change', '.aicc-li-select-all', function() {
		var checked = $(this).prop('checked');
		$('.aicc-li-row-check').prop('checked', checked);
		aiccUpdateLiBulkState();
	});

	// Row checkbox change syncs bulk count + select-all state.
	$(document).on('change', '.aicc-li-row-check', function() {
		aiccUpdateLiBulkState();
	});

	// Bulk delete button.
	$(document).on('click', '.aicc-li-bulk-delete-btn', function() {
		var $btn    = $(this);
		var ids     = [];
		$('.aicc-li-row-check:checked').each(function() {
			ids.push($(this).val());
		});

		if (ids.length === 0) return;

		if (!confirm('<?php echo esc_js( __( 'Remove the selected posts from the LinkedIn Sharing Status dashboard? The WordPress posts and any LinkedIn shares will NOT be deleted.', 'ai-content-orchestrator' ) ); ?>'.replace('the selected posts', ids.length + ' selected post' + (ids.length === 1 ? '' : 's')))) {
			return;
		}

		var originalHtml = $btn.html();
		$btn.prop('disabled', true).html('<span class="spinner is-active" style="float:none; margin:0;"></span> Deleting...');

		$.post(ajaxUrl, {
			action:     'aicc_linkedin_bulk_remove',
			nonce:      nonce,
			'post_ids[]': ids,
		}, function(response) {
			if (response.success) {
				$.each(response.data.post_ids, function(i, id) {
					$('#aicc-li-row-' + id).fadeOut(300, function() { $(this).remove(); });
				});
				setTimeout(function() {
					aiccUpdateLiBulkState();
					// Reload if empty to refresh the empty state.
					if ($('.aicc-li-row-check').length === 0) {
						location.reload();
					}
				}, 400);
				$btn.html(originalHtml);
			} else {
				alert('Error: ' + (response.data && response.data.message ? response.data.message : 'Unknown error'));
				$btn.prop('disabled', false).html(originalHtml);
			}
		}).fail(function(xhr) {
			alert('Request failed: ' + xhr.status);
			$btn.prop('disabled', false).html(originalHtml);
		});
	});

	// Remove from LinkedIn Sharing Status dashboard
	$(document).on('click', '.aicc-li-remove-btn', function() {
		if (!confirm('<?php echo esc_js( __( 'Remove this post from the LinkedIn Sharing Status dashboard? The WordPress post and any existing LinkedIn share will NOT be deleted — this only hides it from this list.', 'ai-content-orchestrator' ) ); ?>')) {
			return;
		}

		var $btn   = $(this);
		var postId = $btn.data('post-id');
		var $row   = $('#aicc-li-row-' + postId);

		$btn.prop('disabled', true);

		$.post(ajaxUrl, {
			action:  'aicc_linkedin_remove_from_dashboard',
			nonce:   nonce,
			post_id: postId,
		}, function(response) {
			if (response.success) {
				$row.fadeOut(300, function() { $row.remove(); });
			} else {
				alert('Error: ' + (response.data && response.data.message ? response.data.message : 'Unknown error'));
				$btn.prop('disabled', false);
			}
		}).fail(function(xhr) {
			alert('Request failed: ' + xhr.status);
			$btn.prop('disabled', false);
		});
	});

	// Toggle LinkedIn post preview
	$(document).on('click', '.aicc-li-toggle-preview', function(e) {
		e.preventDefault();
		var postId = $(this).data('post-id');
		$('#aicc-li-preview-' + postId).slideToggle(150);
	});

	// Edit LinkedIn commentary — switch to edit mode
	$(document).on('click', '.aicc-li-edit-btn', function() {
		var postId = $(this).data('post-id');
		var $wrap  = $('#aicc-li-preview-' + postId);
		$wrap.find('.aicc-li-preview-view').hide();
		$wrap.find('.aicc-li-preview-edit').show();
		$wrap.find('.aicc-li-edit-textarea').focus();
	});

	// Cancel edit — restore view mode
	$(document).on('click', '.aicc-li-cancel-btn', function() {
		var postId = $(this).data('post-id');
		var $wrap  = $('#aicc-li-preview-' + postId);
		// Restore original text in textarea.
		var original = $wrap.find('.aicc-li-preview-text').text();
		$wrap.find('.aicc-li-edit-textarea').val(original);
		$wrap.find('.aicc-li-preview-edit').hide();
		$wrap.find('.aicc-li-preview-view').show();
	});

	// Live char count while editing
	$(document).on('input', '.aicc-li-edit-textarea', function() {
		var len = $(this).val().length;
		$(this).closest('.aicc-li-preview-edit').find('.aicc-li-edit-count').text(len + ' / 2900 characters');
	});

	// Save edited commentary
	$(document).on('click', '.aicc-li-save-btn', function() {
		var $btn       = $(this);
		var postId     = $btn.data('post-id');
		var $wrap      = $('#aicc-li-preview-' + postId);
		var commentary = $wrap.find('.aicc-li-edit-textarea').val();

		$btn.prop('disabled', true).text('Saving...');

		$.post(ajaxUrl, {
			action:     'aicc_linkedin_save_commentary',
			nonce:      nonce,
			post_id:    postId,
			commentary: commentary,
		}, function(response) {
			if (response.success) {
				$wrap.find('.aicc-li-preview-text').text(response.data.commentary);
				$wrap.find('.aicc-li-char-count').text(response.data.length + ' characters');
				$wrap.find('.aicc-li-preview-edit').hide();
				$wrap.find('.aicc-li-preview-view').show();
				$btn.prop('disabled', false).text('<?php echo esc_js( __( 'Save', 'ai-content-orchestrator' ) ); ?>');
			} else {
				alert('Error: ' + (response.data.message || 'Unknown error'));
				$btn.prop('disabled', false).text('<?php echo esc_js( __( 'Save', 'ai-content-orchestrator' ) ); ?>');
			}
		}).fail(function(xhr) {
			alert('Request failed: ' + xhr.status);
			$btn.prop('disabled', false).text('<?php echo esc_js( __( 'Save', 'ai-content-orchestrator' ) ); ?>');
		});
	});

	// Regenerate commentary via AI
	$(document).on('click', '.aicc-li-regen-btn', function() {
		var $btn   = $(this);
		var postId = $btn.data('post-id');
		var $wrap  = $('#aicc-li-preview-' + postId);

		if (!confirm('<?php echo esc_js( __( 'Regenerate the LinkedIn post via AI? The current text will be replaced.', 'ai-content-orchestrator' ) ); ?>')) {
			return;
		}

		var originalHtml = $btn.html();
		$btn.prop('disabled', true).html('<span class="spinner is-active" style="float:none; margin:0;"></span> Generating...');

		$.post({
			url:     ajaxUrl,
			data:    {
				action:  'aicc_linkedin_regenerate_commentary',
				nonce:   nonce,
				post_id: postId,
			},
			timeout: 120000,
		}).done(function(response) {
			if (response.success) {
				$wrap.find('.aicc-li-preview-text').text(response.data.commentary);
				$wrap.find('.aicc-li-edit-textarea').val(response.data.commentary);
				$wrap.find('.aicc-li-char-count').text(response.data.length + ' characters');
				$wrap.find('.aicc-li-edit-count').text(response.data.length + ' / 2900 characters');
				$btn.prop('disabled', false).html(originalHtml);
			} else {
				alert('Regeneration failed: ' + (response.data.message || 'Unknown error'));
				$btn.prop('disabled', false).html(originalHtml);
			}
		}).fail(function(xhr) {
			alert('Request failed: ' + xhr.status + ' ' + xhr.statusText);
			$btn.prop('disabled', false).html(originalHtml);
		});
	});

	// Share to LinkedIn button
	$(document).on('click', '.aicc-li-share-btn', function() {
		var $btn    = $(this);
		var postId  = $btn.data('post-id');
		var $status = $('.aicc-li-status-' + postId);

		$btn.prop('disabled', true).html('<span class="spinner is-active" style="float:none; margin:0;"></span> Sharing...');

		$.post(ajaxUrl, {
			action:  'aicc_linkedin_share_now',
			nonce:   nonce,
			post_id: postId,
		}, function(response) {
			if (response.success) {
				$status.html(
					'<span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span>' +
					' <strong style="color: #00a32a;"><?php echo esc_js( __( 'Shared', 'ai-content-orchestrator' ) ); ?></strong>' +
					'<br><small class="description"><?php echo esc_js( __( 'Just now', 'ai-content-orchestrator' ) ); ?></small>'
				);
				$btn.prop('disabled', false).html('<span class="dashicons dashicons-linkedin" style="vertical-align: text-bottom; font-size: 16px; width: 16px; height: 16px;"></span> <?php echo esc_js( __( 'Re-share', 'ai-content-orchestrator' ) ); ?>');
				alert('<?php echo esc_js( __( 'Successfully shared to LinkedIn! Check your profile feed.', 'ai-content-orchestrator' ) ); ?>');
			} else {
				var msg = (response.data && response.data.message) ? response.data.message : 'Unknown error';
				$status.html(
					'<span class="dashicons dashicons-warning" style="color: #d63638;"></span>' +
					' <strong style="color: #d63638;"><?php echo esc_js( __( 'Failed', 'ai-content-orchestrator' ) ); ?></strong>' +
					'<br><small class="description" style="color: #d63638;">' + $('<div>').text(msg).html() + '</small>'
				);
				$btn.prop('disabled', false).html('<span class="dashicons dashicons-linkedin" style="vertical-align: text-bottom; font-size: 16px; width: 16px; height: 16px;"></span> <?php echo esc_js( __( 'Retry', 'ai-content-orchestrator' ) ); ?>');
				alert('LinkedIn error: ' + msg);
			}
		}).fail(function(xhr) {
			alert('Request failed: ' + xhr.status + ' ' + xhr.statusText);
			$btn.prop('disabled', false).html('<span class="dashicons dashicons-linkedin"></span> Retry');
		});
	});

	// Reschedule button
	$('.aicc-reschedule-btn').on('click', function() {
		var postId     = $(this).data('post-id');
		var $row       = $('#aicc-row-' + postId);
		var scheduleAt = parseInt($row.data('schedule-at'), 10);

		rescheduleTargetId = postId;

		// Convert timestamp to datetime-local value (YYYY-MM-DDTHH:mm).
		if (scheduleAt) {
			var d = new Date(scheduleAt * 1000);
			var pad = function(n) { return n < 10 ? '0' + n : n; };
			var val = d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate())
				+ 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
			$('#aicc-reschedule-input').val(val);
		}
		$('#aicc-reschedule-modal').show();
	});

	// Reschedule modal — cancel
	$('#aicc-reschedule-cancel, .aicc-modal-backdrop').on('click', function() {
		$('#aicc-reschedule-modal').hide();
		rescheduleTargetId = null;
	});

	// Review table — bulk select.
	function updateReviewBulkState() {
		var count = $('.aicc-review-check:checked').length;
		var total = $('.aicc-review-check').length;
		$('#aicc-bulk-actions-bar').toggle(count > 0);
		$('#aicc-review-check-all-th, #aicc-review-check-all').prop('checked', count === total && total > 0);
		if (count > 0 && count < total) $('#aicc-review-check-all-th').prop('indeterminate', true);
	}

	$('#aicc-review-check-all-th, #aicc-review-check-all').on('change', function() {
		$('.aicc-review-check').prop('checked', $(this).prop('checked'));
		$('#aicc-review-check-all-th, #aicc-review-check-all').prop('checked', $(this).prop('checked'));
		updateReviewBulkState();
	});

	$(document).on('change', '.aicc-review-check', function() {
		updateReviewBulkState();
	});

	// Bulk delete.
	$('#aicc-bulk-delete-btn').on('click', function() {
		var ids = [];
		$('.aicc-review-check:checked').each(function() { ids.push($(this).val()); });
		if (!ids.length) return;
		if (!confirm('<?php echo esc_js( __( 'Move', 'ai-content-orchestrator' ) ); ?> ' + ids.length + ' <?php echo esc_js( __( 'items to the trash?', 'ai-content-orchestrator' ) ); ?>')) return;

		var $btn = $(this);
		$btn.prop('disabled', true).html('<span class="spinner is-active" style="float:none; margin:0;"></span> <?php echo esc_js( __( 'Deleting...', 'ai-content-orchestrator' ) ); ?>');
		var done = 0;
		$.each(ids, function(_, id) {
			$.post(ajaxUrl, { action: 'aicc_delete_scheduled', nonce: nonce, post_id: id }, function() {
				$('#aicc-row-' + id).fadeOut(300, function() { $(this).remove(); });
				done++;
				if (done >= ids.length) { setTimeout(function() { location.reload(); }, 500); }
			});
		});
	});

	// Bulk approve.
	$('#aicc-bulk-approve-btn').on('click', function() {
		var ids = [];
		$('.aicc-review-check:checked').each(function() { ids.push($(this).val()); });
		if (!ids.length) return;
		if (!confirm('<?php echo esc_js( __( 'Approve', 'ai-content-orchestrator' ) ); ?> ' + ids.length + ' <?php echo esc_js( __( 'items?', 'ai-content-orchestrator' ) ); ?>')) return;

		var $btn = $(this);
		$btn.prop('disabled', true).html('<span class="spinner is-active" style="float:none; margin:0;"></span> <?php echo esc_js( __( 'Approving...', 'ai-content-orchestrator' ) ); ?>');
		var done = 0;
		$.each(ids, function(_, id) {
			$.post(ajaxUrl, { action: 'aicc_approve_scheduled', nonce: nonce, post_id: id }, function() {
				$('#aicc-row-' + id).fadeOut(300, function() { $(this).remove(); });
				done++;
				if (done >= ids.length) { setTimeout(function() { location.reload(); }, 500); }
			});
		});
	});

	// Reschedule modal — save
	$('#aicc-reschedule-save').on('click', function() {
		var newSchedule = $('#aicc-reschedule-input').val();
		if (!newSchedule) { alert('Please select a date and time.'); return; }

		var $btn = $(this);
		$btn.prop('disabled', true).text('Saving...');

		$.post(ajaxUrl, {
			action:      'aicc_reschedule',
			nonce:       nonce,
			post_id:     rescheduleTargetId,
			schedule_at: newSchedule,
		}, function(response) {
			if (response.success) {
				location.reload();
			} else {
				alert('Error: ' + (response.data.message || 'Unknown error'));
				$btn.prop('disabled', false).text('Save');
			}
		});
	});
})(jQuery);
</script>
