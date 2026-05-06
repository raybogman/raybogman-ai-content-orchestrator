<?php
/**
 * Scheduled content page — Human in the loop review queue.
 *
 * @package Ray_Bogman_Content_Orchestrator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rbco_items              = RBCO_Publisher::get_scheduled_items();
$rbco_linkedin_items     = RBCO_LinkedIn::is_connected() ? RBCO_Publisher::get_published_with_linkedin_status( 20 ) : array();
$rbco_pending_count      = 0;
$rbco_future_count       = 0;
foreach ( $rbco_items as $rbco_item ) {
	if ( 'future' === $rbco_item['status'] ) {
		$rbco_future_count++;
	} elseif ( $rbco_item['needs_review'] ) {
		$rbco_pending_count++;
	}
}
$rbco_wp_cron_disabled = defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON;
$rbco_next_cron        = wp_next_scheduled( 'rbco_catch_up_scheduled' );
$rbco_last_catchup     = get_option( 'rbco_last_catchup_log', array() );
?>
<div class="wrap rbco-wrap">
	<h1 class="wp-heading-inline">
		<span class="dashicons dashicons-calendar-alt rbco-heading-icon"></span>
		<?php esc_html_e( 'Ray Bogman Content Orchestrator — Scheduled', 'raybogman-content-orchestrator' ); ?>
	</h1>
	<p class="rbco-subtitle">
		<?php esc_html_e( 'Review queue for AI-generated content awaiting human approval, plus content already scheduled for publication.', 'raybogman-content-orchestrator' ); ?>
	</p>

	<?php if ( $rbco_wp_cron_disabled ) : ?>
		<div class="notice notice-warning inline">
			<p>
				<strong><?php esc_html_e( 'WordPress cron is disabled.', 'raybogman-content-orchestrator' ); ?></strong>
				<?php esc_html_e( 'DISABLE_WP_CRON is set on this site, so scheduled posts will not publish automatically. Either configure a real cron job, or use the "Publish now" button to publish items manually. This page also auto-publishes overdue items whenever you load it.', 'raybogman-content-orchestrator' ); ?>
			</p>
		</div>
	<?php endif; ?>

	<!-- Cron debug panel -->
	<div class="rbco-card" style="margin-bottom: 16px;">
		<div class="rbco-card-header">
			<h2>
				<span class="dashicons dashicons-clock" style="margin-right: 6px;"></span>
				<?php esc_html_e( 'Cron Status', 'raybogman-content-orchestrator' ); ?>
			</h2>
		</div>
		<div class="rbco-card-body">
			<table class="widefat" style="background: transparent; border: none;">
				<tbody>
					<tr>
						<td style="width: 220px; font-weight: 600; border: none;"><?php esc_html_e( 'Cron event:', 'raybogman-content-orchestrator' ); ?></td>
						<td style="border: none;"><code>rbco_catch_up_scheduled</code> &mdash; <?php esc_html_e( 'every minute', 'raybogman-content-orchestrator' ); ?></td>
					</tr>
					<tr>
						<td style="font-weight: 600; border: none;"><?php esc_html_e( 'Next scheduled run:', 'raybogman-content-orchestrator' ); ?></td>
						<td style="border: none;">
							<?php if ( $rbco_next_cron ) : ?>
								<?php echo esc_html( wp_date( 'Y-m-d H:i:s', $rbco_next_cron ) ); ?>
								(<?php echo esc_html( human_time_diff( time(), $rbco_next_cron ) ); ?>)
							<?php else : ?>
								<span style="color: #d63638;"><?php esc_html_e( 'Not scheduled', 'raybogman-content-orchestrator' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
					<?php if ( ! empty( $rbco_last_catchup ) ) : ?>
						<tr>
							<td style="font-weight: 600; border: none; vertical-align: top;"><?php esc_html_e( 'Last catch-up run:', 'raybogman-content-orchestrator' ); ?></td>
							<td style="border: none;">
								<?php echo esc_html( wp_date( 'Y-m-d H:i:s', $rbco_last_catchup['time'] ) ); ?>
								(<?php echo esc_html( human_time_diff( $rbco_last_catchup['time'], time() ) ); ?> ago)
								&mdash;
								<?php
								printf(
									/* translators: 1: found count, 2: published count */
									esc_html__( 'found %1$d future posts, published %2$d', 'raybogman-content-orchestrator' ),
									(int) $rbco_last_catchup['found'],
									(int) $rbco_last_catchup['published']
								);
								?>
								<?php if ( ! empty( $rbco_last_catchup['details'] ) ) : ?>
									<details style="margin-top: 6px;">
										<summary style="cursor: pointer; color: #2271b1;"><?php esc_html_e( 'Show details', 'raybogman-content-orchestrator' ); ?></summary>
										<pre style="background: #f0f0f1; padding: 10px; border-radius: 3px; font-size: 12px; margin-top: 6px;"><?php echo esc_html( implode( "\n", $rbco_last_catchup['details'] ) ); ?></pre>
									</details>
								<?php endif; ?>
							</td>
						</tr>
					<?php endif; ?>
					<tr>
						<td style="font-weight: 600; border: none;"><?php esc_html_e( 'Manual trigger:', 'raybogman-content-orchestrator' ); ?></td>
						<td style="border: none;">
							<button type="button" class="button" id="rbco-run-catchup-now">
								<span class="dashicons dashicons-update" style="vertical-align: text-bottom; font-size: 16px; width: 16px; height: 16px;"></span>
								<?php esc_html_e( 'Run catch-up now', 'raybogman-content-orchestrator' ); ?>
							</button>
							<span id="rbco-catchup-result" style="margin-left: 12px;"></span>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>

	<!-- Stats bar -->
	<div class="rbco-status-bar">
		<span class="rbco-status-item">
			<strong><?php esc_html_e( 'Awaiting review:', 'raybogman-content-orchestrator' ); ?></strong>
			<span class="rbco-badge <?php echo $rbco_pending_count > 0 ? 'rbco-badge-warning' : 'rbco-badge-success'; ?>">
				<?php echo esc_html( $rbco_pending_count ); ?>
			</span>
		</span>
		<span class="rbco-status-item">
			<strong><?php esc_html_e( 'Scheduled:', 'raybogman-content-orchestrator' ); ?></strong>
			<span class="rbco-badge rbco-badge-success"><?php echo esc_html( $rbco_future_count ); ?></span>
		</span>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=rbco-create' ) ); ?>" class="rbco-status-item rbco-status-link">
			<?php esc_html_e( 'Create New Content', 'raybogman-content-orchestrator' ); ?> &rarr;
		</a>
	</div>

	<?php if ( empty( $rbco_items ) ) : ?>
		<div class="rbco-card">
			<div class="rbco-card-body" style="text-align: center; padding: 40px 20px;">
				<span class="dashicons dashicons-calendar" style="font-size: 48px; width: 48px; height: 48px; color: #c3c4c7;"></span>
				<h2 style="margin-top: 16px;"><?php esc_html_e( 'No scheduled content yet', 'raybogman-content-orchestrator' ); ?></h2>
				<p class="description" style="max-width: 500px; margin: 8px auto 16px;">
					<?php esc_html_e( 'Create new content and select "Schedule for later" to see it appear here. Drafts will wait for your approval; published items schedule automatically.', 'raybogman-content-orchestrator' ); ?>
				</p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=rbco-create' ) ); ?>" class="button button-primary">
					<?php esc_html_e( 'Create Content', 'raybogman-content-orchestrator' ); ?>
				</a>
			</div>
		</div>
	<?php else : ?>

		<!-- Upcoming Publications Timeline -->
		<?php
		$rbco_upcoming = array();
		foreach ( $rbco_items as $rbco_item ) {
			if ( 'future' === $rbco_item['status'] || ( $rbco_item['needs_review'] && ! empty( $rbco_item['scheduled_at'] ) ) ) {
				$rbco_upcoming[] = $rbco_item;
			}
		}
		usort( $rbco_upcoming, function( $a, $b ) {
			return ( $a['scheduled_at'] ?? 0 ) - ( $b['scheduled_at'] ?? 0 );
		});
		?>
		<?php if ( ! empty( $rbco_upcoming ) ) : ?>
		<div class="rbco-card" style="margin-bottom:20px;">
			<div class="rbco-card-header">
				<h2>
					<span class="dashicons dashicons-schedule" style="margin-right:6px; color:#2271b1;"></span>
					<?php esc_html_e( 'Upcoming Publications', 'raybogman-content-orchestrator' ); ?>
				</h2>
			</div>
			<div class="rbco-card-body">
				<div style="position:relative; padding-left:24px;">
					<?php foreach ( $rbco_upcoming as $rbco_idx => $rbco_up ) :
						$rbco_is_future  = ( 'future' === $rbco_up['status'] );
						$rbco_is_pending = $rbco_up['needs_review'];
						$rbco_dot_color  = $rbco_is_future ? '#00a32a' : '#dba617';
						$rbco_status_label = $rbco_is_future
							? __( 'Scheduled', 'raybogman-content-orchestrator' )
							: __( 'Awaiting approval', 'raybogman-content-orchestrator' );
						$rbco_date_str = ! empty( $rbco_up['scheduled_at_formatted'] ) ? $rbco_up['scheduled_at_formatted'] : __( 'Publish on approval', 'raybogman-content-orchestrator' );

						$rbco_days_until = '';
						if ( ! empty( $rbco_up['scheduled_at'] ) && $rbco_up['scheduled_at'] > 0 ) {
							$rbco_diff = $rbco_up['scheduled_at'] - time();
							if ( $rbco_diff > 0 ) {
								$rbco_days = ceil( $rbco_diff / DAY_IN_SECONDS );
								$rbco_days_until = sprintf(
									/* translators: %s: human-readable time */
									__( 'in %s', 'raybogman-content-orchestrator' ),
									human_time_diff( time(), $rbco_up['scheduled_at'] )
								);
							}
						}
					?>
					<div style="position:relative; padding-bottom:<?php echo $rbco_idx < count( $rbco_upcoming ) - 1 ? '20px' : '0'; ?>; <?php echo $rbco_idx < count( $rbco_upcoming ) - 1 ? 'border-left:2px solid #e0e0e0; margin-left:6px; padding-left:22px;' : 'margin-left:6px; padding-left:22px;'; ?>">
						<div style="position:absolute; left:-8px; top:2px; width:14px; height:14px; background:<?php echo esc_attr( $rbco_dot_color ); ?>; border-radius:50%; border:2px solid #fff;"></div>
						<div style="display:flex; align-items:baseline; gap:8px; flex-wrap:wrap;">
							<strong style="font-size:13px;"><?php echo esc_html( $rbco_date_str ); ?></strong>
							<?php if ( $rbco_days_until ) : ?>
								<span style="color:#787c82; font-size:12px;">(<?php echo esc_html( $rbco_days_until ); ?>)</span>
							<?php endif; ?>
							<span style="display:inline-block; padding:1px 8px; border-radius:10px; font-size:11px; font-weight:600; background:<?php echo $rbco_is_future ? '#e7f5e7' : '#fff8e5'; ?>; color:<?php echo $rbco_is_future ? '#00a32a' : '#996800'; ?>;">
								<?php echo esc_html( $rbco_status_label ); ?>
							</span>
						</div>
						<div style="margin-top:2px;">
							<a href="<?php echo esc_url( get_permalink( $rbco_up['id'] ) ); ?>" target="_blank" style="text-decoration:none; font-weight:500;">
								<?php echo esc_html( $rbco_up['title'] ); ?>
							</a>
							<?php if ( ! empty( $rbco_up['linkedin'] ) ) : ?>
								<span class="dashicons dashicons-linkedin" style="color:#0a66c2; vertical-align:text-bottom; font-size:14px; width:14px; height:14px; margin-left:4px;" title="<?php esc_attr_e( 'LinkedIn', 'raybogman-content-orchestrator' ); ?>"></span>
							<?php endif; ?>
						</div>
					</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
		<?php endif; ?>

		<!-- Pending review items -->
		<?php if ( $rbco_pending_count > 0 ) : ?>
			<div class="rbco-card">
				<div class="rbco-card-header">
					<h2>
						<span class="dashicons dashicons-clock" style="margin-right: 6px; color: #dba617;"></span>
						<?php esc_html_e( 'Awaiting Human Review', 'raybogman-content-orchestrator' ); ?>
					</h2>
				</div>
				<div class="rbco-card-body" style="padding: 0;">
					<div id="rbco-bulk-actions-bar" style="display:none; padding:8px 12px; background:#f0f6fc; border-bottom:1px solid #c3c4c7;">
						<label style="margin-right:8px;"><input type="checkbox" id="rbco-review-check-all" /> <?php esc_html_e( 'Select all', 'raybogman-content-orchestrator' ); ?></label>
						<button type="button" class="button" id="rbco-bulk-delete-btn">
							<span class="dashicons dashicons-trash" style="vertical-align:text-bottom; font-size:16px; width:16px; height:16px; color:#d63638; margin-right:2px;"></span>
							<?php esc_html_e( 'Delete Selected', 'raybogman-content-orchestrator' ); ?>
						</button>
						<button type="button" class="button" id="rbco-bulk-approve-btn" style="margin-left:4px;">
							<span class="dashicons dashicons-yes" style="vertical-align:text-bottom; font-size:16px; width:16px; height:16px; color:#00a32a; margin-right:2px;"></span>
							<?php esc_html_e( 'Approve Selected', 'raybogman-content-orchestrator' ); ?>
						</button>
						<span id="rbco-bulk-action-status" style="margin-left:12px;"></span>
					</div>
					<table class="widefat striped rbco-scheduled-table">
						<thead>
							<tr>
								<th style="width:30px;"><input type="checkbox" id="rbco-review-check-all-th" /></th>
								<th><?php esc_html_e( 'Title', 'raybogman-content-orchestrator' ); ?></th>
								<th style="width: 90px;"><?php esc_html_e( 'Type', 'raybogman-content-orchestrator' ); ?></th>
								<th><?php esc_html_e( 'Categories', 'raybogman-content-orchestrator' ); ?></th>
								<th style="width: 180px;"><?php esc_html_e( 'Scheduled For', 'raybogman-content-orchestrator' ); ?></th>
								<th class="rbco-actions-col"><?php esc_html_e( 'Actions', 'raybogman-content-orchestrator' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $rbco_items as $rbco_item ) : ?>
								<?php if ( ! $rbco_item['needs_review'] ) continue; ?>
								<tr id="rbco-row-<?php echo esc_attr( $rbco_item['id'] ); ?>" data-post-id="<?php echo esc_attr( $rbco_item['id'] ); ?>" data-schedule-at="<?php echo esc_attr( $rbco_item['scheduled_at'] ); ?>">
									<td><input type="checkbox" class="rbco-review-check" value="<?php echo esc_attr( $rbco_item['id'] ); ?>" /></td>
									<td>
										<strong><?php echo esc_html( $rbco_item['title'] ); ?></strong>
										<?php if ( ! empty( $rbco_item['linkedin'] ) ) : ?>
											<span class="dashicons dashicons-linkedin" style="color: #0a66c2; vertical-align: text-bottom; font-size: 18px; width: 18px; height: 18px; margin-left: 4px;" title="<?php esc_attr_e( 'Will be shared to LinkedIn when published', 'raybogman-content-orchestrator' ); ?>"></span>
										<?php endif; ?>
										<?php if ( ! empty( $rbco_item['focus_keyphrase'] ) ) : ?>
											<br><small class="description"><?php esc_html_e( 'Focus:', 'raybogman-content-orchestrator' ); ?> <?php echo esc_html( $rbco_item['focus_keyphrase'] ); ?></small>
										<?php endif; ?>
									</td>
									<td>
										<span class="rbco-tag">
											<?php echo 'post' === $rbco_item['type'] ? esc_html__( 'Blog', 'raybogman-content-orchestrator' ) : esc_html__( 'Page', 'raybogman-content-orchestrator' ); ?>
										</span>
									</td>
									<td>
										<?php if ( ! empty( $rbco_item['categories'] ) ) : ?>
											<?php foreach ( $rbco_item['categories'] as $cat ) : ?>
												<span class="rbco-tag"><?php echo esc_html( $cat ); ?></span>
											<?php endforeach; ?>
										<?php else : ?>
											<span class="description">&mdash;</span>
										<?php endif; ?>
									</td>
									<td class="rbco-schedule-cell">
										<span class="rbco-schedule-display"><?php echo esc_html( $rbco_item['scheduled_at_formatted'] ); ?></span>
									</td>
									<td class="rbco-actions-cell">
										<button type="button" class="button button-primary rbco-approve-btn" data-post-id="<?php echo esc_attr( $rbco_item['id'] ); ?>">
											<span class="dashicons dashicons-yes" style="vertical-align: text-bottom; font-size: 16px; width: 16px; height: 16px;"></span>
											<?php esc_html_e( 'Approve', 'raybogman-content-orchestrator' ); ?>
										</button>
										<a href="<?php echo esc_url( $rbco_item['edit_url'] ); ?>" class="button" target="_blank" title="<?php esc_attr_e( 'Edit', 'raybogman-content-orchestrator' ); ?>">
											<span class="dashicons dashicons-edit" style="vertical-align: text-bottom; font-size: 16px; width: 16px; height: 16px;"></span>
										</a>
										<button type="button" class="button rbco-reschedule-btn" data-post-id="<?php echo esc_attr( $rbco_item['id'] ); ?>" title="<?php esc_attr_e( 'Reschedule', 'raybogman-content-orchestrator' ); ?>">
											<span class="dashicons dashicons-calendar-alt" style="vertical-align: text-bottom; font-size: 16px; width: 16px; height: 16px;"></span>
										</button>
										<button type="button" class="button rbco-delete-btn" data-post-id="<?php echo esc_attr( $rbco_item['id'] ); ?>" title="<?php esc_attr_e( 'Delete', 'raybogman-content-orchestrator' ); ?>">
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
		<?php if ( $rbco_future_count > 0 ) : ?>
			<div class="rbco-card">
				<div class="rbco-card-header">
					<h2>
						<span class="dashicons dashicons-calendar-alt" style="margin-right: 6px; color: #00a32a;"></span>
						<?php esc_html_e( 'Scheduled for Publication', 'raybogman-content-orchestrator' ); ?>
					</h2>
				</div>
				<div class="rbco-card-body" style="padding: 0;">
					<table class="widefat striped rbco-scheduled-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Title', 'raybogman-content-orchestrator' ); ?></th>
								<th style="width: 90px;"><?php esc_html_e( 'Type', 'raybogman-content-orchestrator' ); ?></th>
								<th><?php esc_html_e( 'Categories', 'raybogman-content-orchestrator' ); ?></th>
								<th style="width: 180px;"><?php esc_html_e( 'Publishes At', 'raybogman-content-orchestrator' ); ?></th>
								<th class="rbco-actions-col"><?php esc_html_e( 'Actions', 'raybogman-content-orchestrator' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $rbco_items as $rbco_item ) : ?>
								<?php if ( 'future' !== $rbco_item['status'] ) continue; ?>
								<tr id="rbco-row-<?php echo esc_attr( $rbco_item['id'] ); ?>" data-post-id="<?php echo esc_attr( $rbco_item['id'] ); ?>" data-schedule-at="<?php echo esc_attr( $rbco_item['scheduled_at'] ); ?>">
									<td>
										<strong><?php echo esc_html( $rbco_item['title'] ); ?></strong>
										<?php if ( ! empty( $rbco_item['linkedin'] ) ) : ?>
											<span class="dashicons dashicons-linkedin" style="color: #0a66c2; vertical-align: text-bottom; font-size: 18px; width: 18px; height: 18px; margin-left: 4px;" title="<?php esc_attr_e( 'Will be shared to LinkedIn when published', 'raybogman-content-orchestrator' ); ?>"></span>
										<?php endif; ?>
										<?php if ( ! empty( $rbco_item['focus_keyphrase'] ) ) : ?>
											<br><small class="description"><?php esc_html_e( 'Focus:', 'raybogman-content-orchestrator' ); ?> <?php echo esc_html( $rbco_item['focus_keyphrase'] ); ?></small>
										<?php endif; ?>
									</td>
									<td>
										<span class="rbco-tag">
											<?php echo 'post' === $rbco_item['type'] ? esc_html__( 'Blog', 'raybogman-content-orchestrator' ) : esc_html__( 'Page', 'raybogman-content-orchestrator' ); ?>
										</span>
									</td>
									<td>
										<?php if ( ! empty( $rbco_item['categories'] ) ) : ?>
											<?php foreach ( $rbco_item['categories'] as $cat ) : ?>
												<span class="rbco-tag"><?php echo esc_html( $cat ); ?></span>
											<?php endforeach; ?>
										<?php else : ?>
											<span class="description">&mdash;</span>
										<?php endif; ?>
									</td>
									<td class="rbco-schedule-cell">
										<span class="rbco-schedule-display"><?php echo esc_html( $rbco_item['scheduled_at_formatted'] ); ?></span>
									</td>
									<td class="rbco-actions-cell">
										<button type="button" class="button button-primary rbco-publish-now-btn" data-post-id="<?php echo esc_attr( $rbco_item['id'] ); ?>" title="<?php esc_attr_e( 'Publish immediately (skip waiting for scheduled time)', 'raybogman-content-orchestrator' ); ?>">
											<span class="dashicons dashicons-megaphone" style="vertical-align: text-bottom; font-size: 16px; width: 16px; height: 16px;"></span>
											<?php esc_html_e( 'Publish Now', 'raybogman-content-orchestrator' ); ?>
										</button>
										<a href="<?php echo esc_url( $rbco_item['edit_url'] ); ?>" class="button" target="_blank" title="<?php esc_attr_e( 'Edit', 'raybogman-content-orchestrator' ); ?>">
											<span class="dashicons dashicons-edit" style="vertical-align: text-bottom; font-size: 16px; width: 16px; height: 16px;"></span>
										</a>
										<button type="button" class="button rbco-reschedule-btn" data-post-id="<?php echo esc_attr( $rbco_item['id'] ); ?>" title="<?php esc_attr_e( 'Reschedule', 'raybogman-content-orchestrator' ); ?>">
											<span class="dashicons dashicons-calendar-alt" style="vertical-align: text-bottom; font-size: 16px; width: 16px; height: 16px;"></span>
										</button>
										<button type="button" class="button rbco-delete-btn" data-post-id="<?php echo esc_attr( $rbco_item['id'] ); ?>" title="<?php esc_attr_e( 'Delete', 'raybogman-content-orchestrator' ); ?>">
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
	<?php if ( ! empty( $rbco_linkedin_items ) ) : ?>
		<div class="rbco-card" style="margin-top: 20px;">
			<div class="rbco-card-header">
				<h2>
					<span class="dashicons dashicons-linkedin" style="margin-right: 6px; color: #0a66c2;"></span>
					<?php esc_html_e( 'LinkedIn Sharing Status', 'raybogman-content-orchestrator' ); ?>
				</h2>
			</div>
			<div class="rbco-card-body" style="padding: 0;">
				<!-- Bulk action toolbar -->
				<div class="rbco-li-bulk-toolbar" style="padding: 10px 12px; border-bottom: 1px solid #c3c4c7; background: #f6f7f7; display: flex; align-items: center; gap: 10px;">
					<button type="button" class="button rbco-li-bulk-delete-btn" disabled>
						<span class="dashicons dashicons-trash" style="vertical-align: text-bottom; font-size: 16px; width: 16px; height: 16px; color: #d63638;"></span>
						<?php esc_html_e( 'Delete Selected', 'raybogman-content-orchestrator' ); ?>
						(<span class="rbco-li-bulk-count">0</span>)
					</button>
					<span class="description" style="margin-left: auto; font-size: 12px;">
						<?php esc_html_e( 'Bulk delete removes posts from this dashboard only. Does not delete WordPress posts or LinkedIn shares.', 'raybogman-content-orchestrator' ); ?>
					</span>
				</div>
				<table class="widefat striped">
					<thead>
						<tr>
							<th style="width: 30px; padding-left: 12px;">
								<input type="checkbox" class="rbco-li-select-all" title="<?php esc_attr_e( 'Select all', 'raybogman-content-orchestrator' ); ?>" />
							</th>
							<th><?php esc_html_e( 'Title', 'raybogman-content-orchestrator' ); ?></th>
							<th style="width: 160px;"><?php esc_html_e( 'Published', 'raybogman-content-orchestrator' ); ?></th>
							<th style="width: 200px;"><?php esc_html_e( 'LinkedIn Status', 'raybogman-content-orchestrator' ); ?></th>
							<th style="width: 180px;"><?php esc_html_e( 'Actions', 'raybogman-content-orchestrator' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $rbco_linkedin_items as $rbco_li ) : ?>
							<tr id="rbco-li-row-<?php echo esc_attr( $rbco_li['id'] ); ?>">
								<td style="padding-left: 12px;">
									<input type="checkbox" class="rbco-li-row-check" value="<?php echo esc_attr( $rbco_li['id'] ); ?>" />
								</td>
								<td>
									<strong><?php echo esc_html( $rbco_li['title'] ); ?></strong>
									<br>
									<a href="<?php echo esc_url( $rbco_li['url'] ); ?>" target="_blank" class="description">
										<?php esc_html_e( 'View on WordPress', 'raybogman-content-orchestrator' ); ?> &rarr;
									</a>
									<?php if ( ! empty( $rbco_li['linkedin_commentary'] ) ) : ?>
										<br>
										<a href="#" class="rbco-li-toggle-preview" data-post-id="<?php echo esc_attr( $rbco_li['id'] ); ?>" style="font-size: 12px;">
											<span class="dashicons dashicons-visibility" style="font-size: 14px; width: 14px; height: 14px; vertical-align: text-bottom;"></span>
											<?php esc_html_e( 'Show LinkedIn post preview', 'raybogman-content-orchestrator' ); ?>
										</a>
										<div class="rbco-li-preview" id="rbco-li-preview-<?php echo esc_attr( $rbco_li['id'] ); ?>" style="display: none; margin-top: 8px;" data-post-id="<?php echo esc_attr( $rbco_li['id'] ); ?>">
											<!-- View mode -->
											<div class="rbco-li-preview-view">
												<div class="rbco-li-preview-text" style="background: #f6f7f7; border-left: 3px solid #0a66c2; padding: 10px; white-space: pre-wrap; font-family: -apple-system, BlinkMacSystemFont, sans-serif; font-size: 13px; line-height: 1.5; max-width: 500px; border-radius: 2px;"><?php echo esc_html( $rbco_li['linkedin_commentary'] ); ?></div>
												<p style="margin: 6px 0 0; font-size: 11px; color: #646970;">
													<span class="rbco-li-char-count">
														<?php
														printf(
															/* translators: %d: character count */
															esc_html__( '%d characters', 'raybogman-content-orchestrator' ),
															esc_html( mb_strlen( $rbco_li['linkedin_commentary'] ) )
														);
														?>
													</span>
												</p>
												<p style="margin: 8px 0 0;">
													<button type="button" class="button button-small rbco-li-edit-btn" data-post-id="<?php echo esc_attr( $rbco_li['id'] ); ?>">
														<span class="dashicons dashicons-edit" style="font-size: 14px; width: 14px; height: 14px; vertical-align: text-bottom;"></span>
														<?php esc_html_e( 'Edit', 'raybogman-content-orchestrator' ); ?>
													</button>
													<button type="button" class="button button-small rbco-li-regen-btn" data-post-id="<?php echo esc_attr( $rbco_li['id'] ); ?>">
														<span class="dashicons dashicons-update" style="font-size: 14px; width: 14px; height: 14px; vertical-align: text-bottom;"></span>
														<?php esc_html_e( 'Regenerate', 'raybogman-content-orchestrator' ); ?>
													</button>
												</p>
											</div>
											<!-- Edit mode (hidden by default) -->
											<div class="rbco-li-preview-edit" style="display: none;">
												<textarea class="rbco-li-edit-textarea" rows="10" maxlength="2900" style="width: 100%; max-width: 500px; font-family: -apple-system, BlinkMacSystemFont, sans-serif; font-size: 13px; line-height: 1.5; border-left: 3px solid #0a66c2;"><?php echo esc_textarea( $rbco_li['linkedin_commentary'] ); ?></textarea>
												<p style="margin: 4px 0 0; font-size: 11px; color: #646970;">
													<span class="rbco-li-edit-count">
														<?php
														printf(
															/* translators: %d: character count */
															esc_html__( '%d / 2900 characters', 'raybogman-content-orchestrator' ),
															esc_html( mb_strlen( $rbco_li['linkedin_commentary'] ) )
														);
														?>
													</span>
												</p>
												<p style="margin: 8px 0 0;">
													<button type="button" class="button button-primary button-small rbco-li-save-btn" data-post-id="<?php echo esc_attr( $rbco_li['id'] ); ?>">
														<?php esc_html_e( 'Save', 'raybogman-content-orchestrator' ); ?>
													</button>
													<button type="button" class="button button-small rbco-li-cancel-btn" data-post-id="<?php echo esc_attr( $rbco_li['id'] ); ?>">
														<?php esc_html_e( 'Cancel', 'raybogman-content-orchestrator' ); ?>
													</button>
												</p>
											</div>
										</div>
									<?php endif; ?>
								</td>
								<td>
									<?php echo esc_html( wp_date( 'Y-m-d H:i', $rbco_li['published_at'] ) ); ?>
								</td>
								<td class="rbco-li-status-<?php echo esc_attr( $rbco_li['id'] ); ?>">
									<?php if ( 'shared' === $rbco_li['linkedin_status'] ) : ?>
										<span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span>
										<strong style="color: #00a32a;"><?php esc_html_e( 'Shared', 'raybogman-content-orchestrator' ); ?></strong>
										<br><small class="description"><?php echo esc_html( wp_date( 'Y-m-d H:i', $rbco_li['shared_at'] ) ); ?></small>
									<?php elseif ( 'error' === $rbco_li['linkedin_status'] ) : ?>
										<span class="dashicons dashicons-warning" style="color: #d63638;"></span>
										<strong style="color: #d63638;"><?php esc_html_e( 'Failed', 'raybogman-content-orchestrator' ); ?></strong>
										<br><small class="description" style="color: #d63638;"><?php echo esc_html( mb_substr( $rbco_li['linkedin_error'], 0, 100 ) ); ?></small>
									<?php else : ?>
										<span class="dashicons dashicons-minus" style="color: #646970;"></span>
										<em class="description"><?php esc_html_e( 'Not shared yet', 'raybogman-content-orchestrator' ); ?></em>
									<?php endif; ?>
								</td>
								<td>
									<button type="button" class="button rbco-li-share-btn" data-post-id="<?php echo esc_attr( $rbco_li['id'] ); ?>">
										<span class="dashicons dashicons-linkedin" style="vertical-align: text-bottom; font-size: 16px; width: 16px; height: 16px;"></span>
										<?php
										if ( 'shared' === $rbco_li['linkedin_status'] ) {
											esc_html_e( 'Re-share', 'raybogman-content-orchestrator' );
										} elseif ( 'error' === $rbco_li['linkedin_status'] ) {
											esc_html_e( 'Retry', 'raybogman-content-orchestrator' );
										} else {
											esc_html_e( 'Share Now', 'raybogman-content-orchestrator' );
										}
										?>
									</button>
									<button type="button" class="button rbco-li-remove-btn" data-post-id="<?php echo esc_attr( $rbco_li['id'] ); ?>" title="<?php esc_attr_e( 'Remove from LinkedIn dashboard (does not delete the WordPress post or LinkedIn share)', 'raybogman-content-orchestrator' ); ?>">
										<span class="dashicons dashicons-trash" style="vertical-align: text-bottom; font-size: 16px; width: 16px; height: 16px; color: #d63638;"></span>
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<p class="description" style="padding: 12px 16px; margin: 0; background: #f6f7f7; border-top: 1px solid #c3c4c7;">
					<span class="dashicons dashicons-info" style="color: #2271b1; vertical-align: text-bottom;"></span>
					<?php esc_html_e( 'Check your LinkedIn profile feed at', 'raybogman-content-orchestrator' ); ?>
					<a href="https://www.linkedin.com/in/me/recent-activity/all/" target="_blank">linkedin.com/in/me/recent-activity</a>
					<?php esc_html_e( 'to see shared posts. Posts may take a few seconds to appear.', 'raybogman-content-orchestrator' ); ?>
				</p>
			</div>
		</div>
	<?php endif; ?>
</div>

<!-- Reschedule modal -->
<div id="rbco-reschedule-modal" class="rbco-modal" style="display: none;">
	<div class="rbco-modal-backdrop"></div>
	<div class="rbco-modal-content">
		<h2><?php esc_html_e( 'Reschedule', 'raybogman-content-orchestrator' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Select a new date and time for this item.', 'raybogman-content-orchestrator' ); ?></p>
		<input type="datetime-local" id="rbco-reschedule-input" class="regular-text" />
		<p style="margin-top: 16px; text-align: right;">
			<button type="button" class="button" id="rbco-reschedule-cancel"><?php esc_html_e( 'Cancel', 'raybogman-content-orchestrator' ); ?></button>
			<button type="button" class="button button-primary" id="rbco-reschedule-save"><?php esc_html_e( 'Save', 'raybogman-content-orchestrator' ); ?></button>
		</p>
	</div>
</div>

<?php add_action( 'admin_footer', function() { ?>
<script type="text/javascript">
(function($) {
	var ajaxUrl = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';
	var nonce   = '<?php echo esc_js( wp_create_nonce( 'rbco_nonce' ) ); ?>';
	var rescheduleTargetId = null;

	// Approve button
	$('.rbco-approve-btn').on('click', function() {
		var $btn    = $(this);
		var postId  = $btn.data('post-id');
		var $row    = $('#rbco-row-' + postId);

		$btn.prop('disabled', true).text('Approving...');

		$.post(ajaxUrl, {
			action:  'rbco_approve_scheduled',
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
	$('#rbco-run-catchup-now').on('click', function() {
		var $btn    = $(this);
		var $result = $('#rbco-catchup-result');

		$btn.prop('disabled', true);
		$result.html('<span class="spinner is-active" style="float:none; margin:0;"></span>');

		$.post(ajaxUrl, {
			action: 'rbco_run_catchup',
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
	$('.rbco-publish-now-btn').on('click', function() {
		if (!confirm('<?php echo esc_js( __( 'Publish this post immediately, ignoring the scheduled time?', 'raybogman-content-orchestrator' ) ); ?>')) return;

		var $btn    = $(this);
		var postId  = $btn.data('post-id');
		var $row    = $('#rbco-row-' + postId);

		$btn.prop('disabled', true).text('Publishing...');

		$.post(ajaxUrl, {
			action:  'rbco_publish_now',
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
	$('.rbco-delete-btn').on('click', function() {
		if (!confirm('<?php echo esc_js( __( 'Move this item to the trash?', 'raybogman-content-orchestrator' ) ); ?>')) return;

		var $btn   = $(this);
		var postId = $btn.data('post-id');
		var $row   = $('#rbco-row-' + postId);

		$btn.prop('disabled', true);

		$.post(ajaxUrl, {
			action:  'rbco_delete_scheduled',
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
		var count = $('.rbco-li-row-check:checked').length;
		$('.rbco-li-bulk-count').text(count);
		$('.rbco-li-bulk-delete-btn').prop('disabled', count === 0);

		// Sync select-all checkbox state.
		var total = $('.rbco-li-row-check').length;
		var $all  = $('.rbco-li-select-all');
		if (count === 0) {
			$all.prop('checked', false).prop('indeterminate', false);
		} else if (count === total) {
			$all.prop('checked', true).prop('indeterminate', false);
		} else {
			$all.prop('checked', false).prop('indeterminate', true);
		}
	}

	// Select-all checkbox toggles all row checkboxes.
	$(document).on('change', '.rbco-li-select-all', function() {
		var checked = $(this).prop('checked');
		$('.rbco-li-row-check').prop('checked', checked);
		aiccUpdateLiBulkState();
	});

	// Row checkbox change syncs bulk count + select-all state.
	$(document).on('change', '.rbco-li-row-check', function() {
		aiccUpdateLiBulkState();
	});

	// Bulk delete button.
	$(document).on('click', '.rbco-li-bulk-delete-btn', function() {
		var $btn    = $(this);
		var ids     = [];
		$('.rbco-li-row-check:checked').each(function() {
			ids.push($(this).val());
		});

		if (ids.length === 0) return;

		if (!confirm('<?php echo esc_js( __( 'Remove the selected posts from the LinkedIn Sharing Status dashboard? The WordPress posts and any LinkedIn shares will NOT be deleted.', 'raybogman-content-orchestrator' ) ); ?>'.replace('the selected posts', ids.length + ' selected post' + (ids.length === 1 ? '' : 's')))) {
			return;
		}

		var originalHtml = $btn.html();
		$btn.prop('disabled', true).html('<span class="spinner is-active" style="float:none; margin:0;"></span> Deleting...');

		$.post(ajaxUrl, {
			action:     'rbco_linkedin_bulk_remove',
			nonce:      nonce,
			'post_ids[]': ids,
		}, function(response) {
			if (response.success) {
				$.each(response.data.post_ids, function(i, id) {
					$('#rbco-li-row-' + id).fadeOut(300, function() { $(this).remove(); });
				});
				setTimeout(function() {
					aiccUpdateLiBulkState();
					// Reload if empty to refresh the empty state.
					if ($('.rbco-li-row-check').length === 0) {
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
	$(document).on('click', '.rbco-li-remove-btn', function() {
		if (!confirm('<?php echo esc_js( __( 'Remove this post from the LinkedIn Sharing Status dashboard? The WordPress post and any existing LinkedIn share will NOT be deleted — this only hides it from this list.', 'raybogman-content-orchestrator' ) ); ?>')) {
			return;
		}

		var $btn   = $(this);
		var postId = $btn.data('post-id');
		var $row   = $('#rbco-li-row-' + postId);

		$btn.prop('disabled', true);

		$.post(ajaxUrl, {
			action:  'rbco_linkedin_remove_from_dashboard',
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
	$(document).on('click', '.rbco-li-toggle-preview', function(e) {
		e.preventDefault();
		var postId = $(this).data('post-id');
		$('#rbco-li-preview-' + postId).slideToggle(150);
	});

	// Edit LinkedIn commentary — switch to edit mode
	$(document).on('click', '.rbco-li-edit-btn', function() {
		var postId = $(this).data('post-id');
		var $wrap  = $('#rbco-li-preview-' + postId);
		$wrap.find('.rbco-li-preview-view').hide();
		$wrap.find('.rbco-li-preview-edit').show();
		$wrap.find('.rbco-li-edit-textarea').focus();
	});

	// Cancel edit — restore view mode
	$(document).on('click', '.rbco-li-cancel-btn', function() {
		var postId = $(this).data('post-id');
		var $wrap  = $('#rbco-li-preview-' + postId);
		// Restore original text in textarea.
		var original = $wrap.find('.rbco-li-preview-text').text();
		$wrap.find('.rbco-li-edit-textarea').val(original);
		$wrap.find('.rbco-li-preview-edit').hide();
		$wrap.find('.rbco-li-preview-view').show();
	});

	// Live char count while editing
	$(document).on('input', '.rbco-li-edit-textarea', function() {
		var len = $(this).val().length;
		$(this).closest('.rbco-li-preview-edit').find('.rbco-li-edit-count').text(len + ' / 2900 characters');
	});

	// Save edited commentary
	$(document).on('click', '.rbco-li-save-btn', function() {
		var $btn       = $(this);
		var postId     = $btn.data('post-id');
		var $wrap      = $('#rbco-li-preview-' + postId);
		var commentary = $wrap.find('.rbco-li-edit-textarea').val();

		$btn.prop('disabled', true).text('Saving...');

		$.post(ajaxUrl, {
			action:     'rbco_linkedin_save_commentary',
			nonce:      nonce,
			post_id:    postId,
			commentary: commentary,
		}, function(response) {
			if (response.success) {
				$wrap.find('.rbco-li-preview-text').text(response.data.commentary);
				$wrap.find('.rbco-li-char-count').text(response.data.length + ' characters');
				$wrap.find('.rbco-li-preview-edit').hide();
				$wrap.find('.rbco-li-preview-view').show();
				$btn.prop('disabled', false).text('<?php echo esc_js( __( 'Save', 'raybogman-content-orchestrator' ) ); ?>');
			} else {
				alert('Error: ' + (response.data.message || 'Unknown error'));
				$btn.prop('disabled', false).text('<?php echo esc_js( __( 'Save', 'raybogman-content-orchestrator' ) ); ?>');
			}
		}).fail(function(xhr) {
			alert('Request failed: ' + xhr.status);
			$btn.prop('disabled', false).text('<?php echo esc_js( __( 'Save', 'raybogman-content-orchestrator' ) ); ?>');
		});
	});

	// Regenerate commentary via AI
	$(document).on('click', '.rbco-li-regen-btn', function() {
		var $btn   = $(this);
		var postId = $btn.data('post-id');
		var $wrap  = $('#rbco-li-preview-' + postId);

		if (!confirm('<?php echo esc_js( __( 'Regenerate the LinkedIn post via AI? The current text will be replaced.', 'raybogman-content-orchestrator' ) ); ?>')) {
			return;
		}

		var originalHtml = $btn.html();
		$btn.prop('disabled', true).html('<span class="spinner is-active" style="float:none; margin:0;"></span> Generating...');

		$.post({
			url:     ajaxUrl,
			data:    {
				action:  'rbco_linkedin_regenerate_commentary',
				nonce:   nonce,
				post_id: postId,
			},
			timeout: 120000,
		}).done(function(response) {
			if (response.success) {
				$wrap.find('.rbco-li-preview-text').text(response.data.commentary);
				$wrap.find('.rbco-li-edit-textarea').val(response.data.commentary);
				$wrap.find('.rbco-li-char-count').text(response.data.length + ' characters');
				$wrap.find('.rbco-li-edit-count').text(response.data.length + ' / 2900 characters');
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
	$(document).on('click', '.rbco-li-share-btn', function() {
		var $btn    = $(this);
		var postId  = $btn.data('post-id');
		var $status = $('.rbco-li-status-' + postId);

		$btn.prop('disabled', true).html('<span class="spinner is-active" style="float:none; margin:0;"></span> Sharing...');

		$.post(ajaxUrl, {
			action:  'rbco_linkedin_share_now',
			nonce:   nonce,
			post_id: postId,
		}, function(response) {
			if (response.success) {
				$status.html(
					'<span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span>' +
					' <strong style="color: #00a32a;"><?php echo esc_js( __( 'Shared', 'raybogman-content-orchestrator' ) ); ?></strong>' +
					'<br><small class="description"><?php echo esc_js( __( 'Just now', 'raybogman-content-orchestrator' ) ); ?></small>'
				);
				$btn.prop('disabled', false).html('<span class="dashicons dashicons-linkedin" style="vertical-align: text-bottom; font-size: 16px; width: 16px; height: 16px;"></span> <?php echo esc_js( __( 'Re-share', 'raybogman-content-orchestrator' ) ); ?>');
				alert('<?php echo esc_js( __( 'Successfully shared to LinkedIn! Check your profile feed.', 'raybogman-content-orchestrator' ) ); ?>');
			} else {
				var msg = (response.data && response.data.message) ? response.data.message : 'Unknown error';
				$status.html(
					'<span class="dashicons dashicons-warning" style="color: #d63638;"></span>' +
					' <strong style="color: #d63638;"><?php echo esc_js( __( 'Failed', 'raybogman-content-orchestrator' ) ); ?></strong>' +
					'<br><small class="description" style="color: #d63638;">' + $('<div>').text(msg).html() + '</small>'
				);
				$btn.prop('disabled', false).html('<span class="dashicons dashicons-linkedin" style="vertical-align: text-bottom; font-size: 16px; width: 16px; height: 16px;"></span> <?php echo esc_js( __( 'Retry', 'raybogman-content-orchestrator' ) ); ?>');
				alert('LinkedIn error: ' + msg);
			}
		}).fail(function(xhr) {
			alert('Request failed: ' + xhr.status + ' ' + xhr.statusText);
			$btn.prop('disabled', false).html('<span class="dashicons dashicons-linkedin"></span> Retry');
		});
	});

	// Reschedule button
	$('.rbco-reschedule-btn').on('click', function() {
		var postId     = $(this).data('post-id');
		var $row       = $('#rbco-row-' + postId);
		var scheduleAt = parseInt($row.data('schedule-at'), 10);

		rescheduleTargetId = postId;

		// Convert timestamp to datetime-local value (YYYY-MM-DDTHH:mm).
		if (scheduleAt) {
			var d = new Date(scheduleAt * 1000);
			var pad = function(n) { return n < 10 ? '0' + n : n; };
			var val = d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate())
				+ 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
			$('#rbco-reschedule-input').val(val);
		}
		$('#rbco-reschedule-modal').show();
	});

	// Reschedule modal — cancel
	$('#rbco-reschedule-cancel, .rbco-modal-backdrop').on('click', function() {
		$('#rbco-reschedule-modal').hide();
		rescheduleTargetId = null;
	});

	// Review table — bulk select.
	function updateReviewBulkState() {
		var count = $('.rbco-review-check:checked').length;
		var total = $('.rbco-review-check').length;
		$('#rbco-bulk-actions-bar').toggle(count > 0);
		$('#rbco-review-check-all-th, #rbco-review-check-all').prop('checked', count === total && total > 0);
		if (count > 0 && count < total) $('#rbco-review-check-all-th').prop('indeterminate', true);
	}

	$('#rbco-review-check-all-th, #rbco-review-check-all').on('change', function() {
		$('.rbco-review-check').prop('checked', $(this).prop('checked'));
		$('#rbco-review-check-all-th, #rbco-review-check-all').prop('checked', $(this).prop('checked'));
		updateReviewBulkState();
	});

	$(document).on('change', '.rbco-review-check', function() {
		updateReviewBulkState();
	});

	// Bulk delete.
	$('#rbco-bulk-delete-btn').on('click', function() {
		var ids = [];
		$('.rbco-review-check:checked').each(function() { ids.push($(this).val()); });
		if (!ids.length) return;
		if (!confirm('<?php echo esc_js( __( 'Move', 'raybogman-content-orchestrator' ) ); ?> ' + ids.length + ' <?php echo esc_js( __( 'items to the trash?', 'raybogman-content-orchestrator' ) ); ?>')) return;

		var $btn = $(this);
		$btn.prop('disabled', true).html('<span class="spinner is-active" style="float:none; margin:0;"></span> <?php echo esc_js( __( 'Deleting...', 'raybogman-content-orchestrator' ) ); ?>');
		var done = 0;
		$.each(ids, function(_, id) {
			$.post(ajaxUrl, { action: 'rbco_delete_scheduled', nonce: nonce, post_id: id }, function() {
				$('#rbco-row-' + id).fadeOut(300, function() { $(this).remove(); });
				done++;
				if (done >= ids.length) { setTimeout(function() { location.reload(); }, 500); }
			});
		});
	});

	// Bulk approve.
	$('#rbco-bulk-approve-btn').on('click', function() {
		var ids = [];
		$('.rbco-review-check:checked').each(function() { ids.push($(this).val()); });
		if (!ids.length) return;
		if (!confirm('<?php echo esc_js( __( 'Approve', 'raybogman-content-orchestrator' ) ); ?> ' + ids.length + ' <?php echo esc_js( __( 'items?', 'raybogman-content-orchestrator' ) ); ?>')) return;

		var $btn = $(this);
		$btn.prop('disabled', true).html('<span class="spinner is-active" style="float:none; margin:0;"></span> <?php echo esc_js( __( 'Approving...', 'raybogman-content-orchestrator' ) ); ?>');
		var done = 0;
		$.each(ids, function(_, id) {
			$.post(ajaxUrl, { action: 'rbco_approve_scheduled', nonce: nonce, post_id: id }, function() {
				$('#rbco-row-' + id).fadeOut(300, function() { $(this).remove(); });
				done++;
				if (done >= ids.length) { setTimeout(function() { location.reload(); }, 500); }
			});
		});
	});

	// Reschedule modal — save
	$('#rbco-reschedule-save').on('click', function() {
		var newSchedule = $('#rbco-reschedule-input').val();
		if (!newSchedule) { alert('Please select a date and time.'); return; }

		var $btn = $(this);
		$btn.prop('disabled', true).text('Saving...');

		$.post(ajaxUrl, {
			action:      'rbco_reschedule',
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
<?php } ); ?>
