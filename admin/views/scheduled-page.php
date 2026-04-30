<?php
/**
 * Scheduled content page — Human in the loop review queue.
 *
 * @package RayAI_Content_Orchestrator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$rayai_items              = RAYAI_Publisher::get_scheduled_items();
$rayai_linkedin_items     = RAYAI_LinkedIn::is_connected() ? RAYAI_Publisher::get_published_with_linkedin_status( 20 ) : array();
$rayai_pending_count      = 0;
$rayai_future_count       = 0;
foreach ( $rayai_items as $rayai_item ) {
	if ( 'future' === $rayai_item['status'] ) {
		$rayai_future_count++;
	} elseif ( $rayai_item['needs_review'] ) {
		$rayai_pending_count++;
	}
}
$rayai_wp_cron_disabled = defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON;
$rayai_next_cron        = wp_next_scheduled( 'rayai_catch_up_scheduled' );
$rayai_last_catchup     = get_option( 'rayai_last_catchup_log', array() );
?>
<div class="wrap rayai-wrap">
	<h1 class="wp-heading-inline">
		<span class="dashicons dashicons-calendar-alt rayai-heading-icon"></span>
		<?php esc_html_e( 'RayAI – Content Orchestrator — Scheduled', 'rayai-content-orchestrator' ); ?>
	</h1>
	<p class="rayai-subtitle">
		<?php esc_html_e( 'Review queue for AI-generated content awaiting human approval, plus content already scheduled for publication.', 'rayai-content-orchestrator' ); ?>
	</p>

	<?php if ( $rayai_wp_cron_disabled ) : ?>
		<div class="notice notice-warning inline">
			<p>
				<strong><?php esc_html_e( 'WordPress cron is disabled.', 'rayai-content-orchestrator' ); ?></strong>
				<?php esc_html_e( 'DISABLE_WP_CRON is set on this site, so scheduled posts will not publish automatically. Either configure a real cron job, or use the "Publish now" button to publish items manually. This page also auto-publishes overdue items whenever you load it.', 'rayai-content-orchestrator' ); ?>
			</p>
		</div>
	<?php endif; ?>

	<!-- Cron debug panel -->
	<div class="rayai-card" style="margin-bottom: 16px;">
		<div class="rayai-card-header">
			<h2>
				<span class="dashicons dashicons-clock" style="margin-right: 6px;"></span>
				<?php esc_html_e( 'Cron Status', 'rayai-content-orchestrator' ); ?>
			</h2>
		</div>
		<div class="rayai-card-body">
			<table class="widefat" style="background: transparent; border: none;">
				<tbody>
					<tr>
						<td style="width: 220px; font-weight: 600; border: none;"><?php esc_html_e( 'Cron event:', 'rayai-content-orchestrator' ); ?></td>
						<td style="border: none;"><code>rayai_catch_up_scheduled</code> &mdash; <?php esc_html_e( 'every minute', 'rayai-content-orchestrator' ); ?></td>
					</tr>
					<tr>
						<td style="font-weight: 600; border: none;"><?php esc_html_e( 'Next scheduled run:', 'rayai-content-orchestrator' ); ?></td>
						<td style="border: none;">
							<?php if ( $rayai_next_cron ) : ?>
								<?php echo esc_html( wp_date( 'Y-m-d H:i:s', $rayai_next_cron ) ); ?>
								(<?php echo esc_html( human_time_diff( time(), $rayai_next_cron ) ); ?>)
							<?php else : ?>
								<span style="color: #d63638;"><?php esc_html_e( 'Not scheduled', 'rayai-content-orchestrator' ); ?></span>
							<?php endif; ?>
						</td>
					</tr>
					<?php if ( ! empty( $rayai_last_catchup ) ) : ?>
						<tr>
							<td style="font-weight: 600; border: none; vertical-align: top;"><?php esc_html_e( 'Last catch-up run:', 'rayai-content-orchestrator' ); ?></td>
							<td style="border: none;">
								<?php echo esc_html( wp_date( 'Y-m-d H:i:s', $rayai_last_catchup['time'] ) ); ?>
								(<?php echo esc_html( human_time_diff( $rayai_last_catchup['time'], time() ) ); ?> ago)
								&mdash;
								<?php
								printf(
									/* translators: 1: found count, 2: published count */
									esc_html__( 'found %1$d future posts, published %2$d', 'rayai-content-orchestrator' ),
									(int) $rayai_last_catchup['found'],
									(int) $rayai_last_catchup['published']
								);
								?>
								<?php if ( ! empty( $rayai_last_catchup['details'] ) ) : ?>
									<details style="margin-top: 6px;">
										<summary style="cursor: pointer; color: #2271b1;"><?php esc_html_e( 'Show details', 'rayai-content-orchestrator' ); ?></summary>
										<pre style="background: #f0f0f1; padding: 10px; border-radius: 3px; font-size: 12px; margin-top: 6px;"><?php echo esc_html( implode( "\n", $rayai_last_catchup['details'] ) ); ?></pre>
									</details>
								<?php endif; ?>
							</td>
						</tr>
					<?php endif; ?>
					<tr>
						<td style="font-weight: 600; border: none;"><?php esc_html_e( 'Manual trigger:', 'rayai-content-orchestrator' ); ?></td>
						<td style="border: none;">
							<button type="button" class="button" id="rayai-run-catchup-now">
								<span class="dashicons dashicons-update" style="vertical-align: text-bottom; font-size: 16px; width: 16px; height: 16px;"></span>
								<?php esc_html_e( 'Run catch-up now', 'rayai-content-orchestrator' ); ?>
							</button>
							<span id="rayai-catchup-result" style="margin-left: 12px;"></span>
						</td>
					</tr>
				</tbody>
			</table>
		</div>
	</div>

	<!-- Stats bar -->
	<div class="rayai-status-bar">
		<span class="rayai-status-item">
			<strong><?php esc_html_e( 'Awaiting review:', 'rayai-content-orchestrator' ); ?></strong>
			<span class="rayai-badge <?php echo $rayai_pending_count > 0 ? 'rayai-badge-warning' : 'rayai-badge-success'; ?>">
				<?php echo esc_html( $rayai_pending_count ); ?>
			</span>
		</span>
		<span class="rayai-status-item">
			<strong><?php esc_html_e( 'Scheduled:', 'rayai-content-orchestrator' ); ?></strong>
			<span class="rayai-badge rayai-badge-success"><?php echo esc_html( $rayai_future_count ); ?></span>
		</span>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=rayai-create' ) ); ?>" class="rayai-status-item rayai-status-link">
			<?php esc_html_e( 'Create New Content', 'rayai-content-orchestrator' ); ?> &rarr;
		</a>
	</div>

	<?php if ( empty( $rayai_items ) ) : ?>
		<div class="rayai-card">
			<div class="rayai-card-body" style="text-align: center; padding: 40px 20px;">
				<span class="dashicons dashicons-calendar" style="font-size: 48px; width: 48px; height: 48px; color: #c3c4c7;"></span>
				<h2 style="margin-top: 16px;"><?php esc_html_e( 'No scheduled content yet', 'rayai-content-orchestrator' ); ?></h2>
				<p class="description" style="max-width: 500px; margin: 8px auto 16px;">
					<?php esc_html_e( 'Create new content and select "Schedule for later" to see it appear here. Drafts will wait for your approval; published items schedule automatically.', 'rayai-content-orchestrator' ); ?>
				</p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=rayai-create' ) ); ?>" class="button button-primary">
					<?php esc_html_e( 'Create Content', 'rayai-content-orchestrator' ); ?>
				</a>
			</div>
		</div>
	<?php else : ?>

		<!-- Upcoming Publications Timeline -->
		<?php
		$rayai_upcoming = array();
		foreach ( $rayai_items as $rayai_item ) {
			if ( 'future' === $rayai_item['status'] || ( $rayai_item['needs_review'] && ! empty( $rayai_item['scheduled_at'] ) ) ) {
				$rayai_upcoming[] = $rayai_item;
			}
		}
		usort( $rayai_upcoming, function( $a, $b ) {
			return ( $a['scheduled_at'] ?? 0 ) - ( $b['scheduled_at'] ?? 0 );
		});
		?>
		<?php if ( ! empty( $rayai_upcoming ) ) : ?>
		<div class="rayai-card" style="margin-bottom:20px;">
			<div class="rayai-card-header">
				<h2>
					<span class="dashicons dashicons-schedule" style="margin-right:6px; color:#2271b1;"></span>
					<?php esc_html_e( 'Upcoming Publications', 'rayai-content-orchestrator' ); ?>
				</h2>
			</div>
			<div class="rayai-card-body">
				<div style="position:relative; padding-left:24px;">
					<?php foreach ( $rayai_upcoming as $rayai_idx => $rayai_up ) :
						$rayai_is_future  = ( 'future' === $rayai_up['status'] );
						$rayai_is_pending = $rayai_up['needs_review'];
						$rayai_dot_color  = $rayai_is_future ? '#00a32a' : '#dba617';
						$rayai_status_label = $rayai_is_future
							? __( 'Scheduled', 'rayai-content-orchestrator' )
							: __( 'Awaiting approval', 'rayai-content-orchestrator' );
						$rayai_date_str = ! empty( $rayai_up['scheduled_at_formatted'] ) ? $rayai_up['scheduled_at_formatted'] : __( 'Publish on approval', 'rayai-content-orchestrator' );

						$rayai_days_until = '';
						if ( ! empty( $rayai_up['scheduled_at'] ) && $rayai_up['scheduled_at'] > 0 ) {
							$rayai_diff = $rayai_up['scheduled_at'] - time();
							if ( $rayai_diff > 0 ) {
								$rayai_days = ceil( $rayai_diff / DAY_IN_SECONDS );
								$rayai_days_until = sprintf(
									/* translators: %s: human-readable time */
									__( 'in %s', 'rayai-content-orchestrator' ),
									human_time_diff( time(), $rayai_up['scheduled_at'] )
								);
							}
						}
					?>
					<div style="position:relative; padding-bottom:<?php echo $rayai_idx < count( $rayai_upcoming ) - 1 ? '20px' : '0'; ?>; <?php echo $rayai_idx < count( $rayai_upcoming ) - 1 ? 'border-left:2px solid #e0e0e0; margin-left:6px; padding-left:22px;' : 'margin-left:6px; padding-left:22px;'; ?>">
						<div style="position:absolute; left:-8px; top:2px; width:14px; height:14px; background:<?php echo esc_attr( $rayai_dot_color ); ?>; border-radius:50%; border:2px solid #fff;"></div>
						<div style="display:flex; align-items:baseline; gap:8px; flex-wrap:wrap;">
							<strong style="font-size:13px;"><?php echo esc_html( $rayai_date_str ); ?></strong>
							<?php if ( $rayai_days_until ) : ?>
								<span style="color:#787c82; font-size:12px;">(<?php echo esc_html( $rayai_days_until ); ?>)</span>
							<?php endif; ?>
							<span style="display:inline-block; padding:1px 8px; border-radius:10px; font-size:11px; font-weight:600; background:<?php echo $rayai_is_future ? '#e7f5e7' : '#fff8e5'; ?>; color:<?php echo $rayai_is_future ? '#00a32a' : '#996800'; ?>;">
								<?php echo esc_html( $rayai_status_label ); ?>
							</span>
						</div>
						<div style="margin-top:2px;">
							<a href="<?php echo esc_url( get_permalink( $rayai_up['id'] ) ); ?>" target="_blank" style="text-decoration:none; font-weight:500;">
								<?php echo esc_html( $rayai_up['title'] ); ?>
							</a>
							<?php if ( ! empty( $rayai_up['linkedin'] ) ) : ?>
								<span class="dashicons dashicons-linkedin" style="color:#0a66c2; vertical-align:text-bottom; font-size:14px; width:14px; height:14px; margin-left:4px;" title="<?php esc_attr_e( 'LinkedIn', 'rayai-content-orchestrator' ); ?>"></span>
							<?php endif; ?>
						</div>
					</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
		<?php endif; ?>

		<!-- Pending review items -->
		<?php if ( $rayai_pending_count > 0 ) : ?>
			<div class="rayai-card">
				<div class="rayai-card-header">
					<h2>
						<span class="dashicons dashicons-clock" style="margin-right: 6px; color: #dba617;"></span>
						<?php esc_html_e( 'Awaiting Human Review', 'rayai-content-orchestrator' ); ?>
					</h2>
				</div>
				<div class="rayai-card-body" style="padding: 0;">
					<div id="rayai-bulk-actions-bar" style="display:none; padding:8px 12px; background:#f0f6fc; border-bottom:1px solid #c3c4c7;">
						<label style="margin-right:8px;"><input type="checkbox" id="rayai-review-check-all" /> <?php esc_html_e( 'Select all', 'rayai-content-orchestrator' ); ?></label>
						<button type="button" class="button" id="rayai-bulk-delete-btn">
							<span class="dashicons dashicons-trash" style="vertical-align:text-bottom; font-size:16px; width:16px; height:16px; color:#d63638; margin-right:2px;"></span>
							<?php esc_html_e( 'Delete Selected', 'rayai-content-orchestrator' ); ?>
						</button>
						<button type="button" class="button" id="rayai-bulk-approve-btn" style="margin-left:4px;">
							<span class="dashicons dashicons-yes" style="vertical-align:text-bottom; font-size:16px; width:16px; height:16px; color:#00a32a; margin-right:2px;"></span>
							<?php esc_html_e( 'Approve Selected', 'rayai-content-orchestrator' ); ?>
						</button>
						<span id="rayai-bulk-action-status" style="margin-left:12px;"></span>
					</div>
					<table class="widefat striped rayai-scheduled-table">
						<thead>
							<tr>
								<th style="width:30px;"><input type="checkbox" id="rayai-review-check-all-th" /></th>
								<th><?php esc_html_e( 'Title', 'rayai-content-orchestrator' ); ?></th>
								<th style="width: 90px;"><?php esc_html_e( 'Type', 'rayai-content-orchestrator' ); ?></th>
								<th><?php esc_html_e( 'Categories', 'rayai-content-orchestrator' ); ?></th>
								<th style="width: 180px;"><?php esc_html_e( 'Scheduled For', 'rayai-content-orchestrator' ); ?></th>
								<th class="rayai-actions-col"><?php esc_html_e( 'Actions', 'rayai-content-orchestrator' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $rayai_items as $rayai_item ) : ?>
								<?php if ( ! $rayai_item['needs_review'] ) continue; ?>
								<tr id="rayai-row-<?php echo esc_attr( $rayai_item['id'] ); ?>" data-post-id="<?php echo esc_attr( $rayai_item['id'] ); ?>" data-schedule-at="<?php echo esc_attr( $rayai_item['scheduled_at'] ); ?>">
									<td><input type="checkbox" class="rayai-review-check" value="<?php echo esc_attr( $rayai_item['id'] ); ?>" /></td>
									<td>
										<strong><?php echo esc_html( $rayai_item['title'] ); ?></strong>
										<?php if ( ! empty( $rayai_item['linkedin'] ) ) : ?>
											<span class="dashicons dashicons-linkedin" style="color: #0a66c2; vertical-align: text-bottom; font-size: 18px; width: 18px; height: 18px; margin-left: 4px;" title="<?php esc_attr_e( 'Will be shared to LinkedIn when published', 'rayai-content-orchestrator' ); ?>"></span>
										<?php endif; ?>
										<?php if ( ! empty( $rayai_item['focus_keyphrase'] ) ) : ?>
											<br><small class="description"><?php esc_html_e( 'Focus:', 'rayai-content-orchestrator' ); ?> <?php echo esc_html( $rayai_item['focus_keyphrase'] ); ?></small>
										<?php endif; ?>
									</td>
									<td>
										<span class="rayai-tag">
											<?php echo 'post' === $rayai_item['type'] ? esc_html__( 'Blog', 'rayai-content-orchestrator' ) : esc_html__( 'Page', 'rayai-content-orchestrator' ); ?>
										</span>
									</td>
									<td>
										<?php if ( ! empty( $rayai_item['categories'] ) ) : ?>
											<?php foreach ( $rayai_item['categories'] as $cat ) : ?>
												<span class="rayai-tag"><?php echo esc_html( $cat ); ?></span>
											<?php endforeach; ?>
										<?php else : ?>
											<span class="description">&mdash;</span>
										<?php endif; ?>
									</td>
									<td class="rayai-schedule-cell">
										<span class="rayai-schedule-display"><?php echo esc_html( $rayai_item['scheduled_at_formatted'] ); ?></span>
									</td>
									<td class="rayai-actions-cell">
										<button type="button" class="button button-primary rayai-approve-btn" data-post-id="<?php echo esc_attr( $rayai_item['id'] ); ?>">
											<span class="dashicons dashicons-yes" style="vertical-align: text-bottom; font-size: 16px; width: 16px; height: 16px;"></span>
											<?php esc_html_e( 'Approve', 'rayai-content-orchestrator' ); ?>
										</button>
										<a href="<?php echo esc_url( $rayai_item['edit_url'] ); ?>" class="button" target="_blank" title="<?php esc_attr_e( 'Edit', 'rayai-content-orchestrator' ); ?>">
											<span class="dashicons dashicons-edit" style="vertical-align: text-bottom; font-size: 16px; width: 16px; height: 16px;"></span>
										</a>
										<button type="button" class="button rayai-reschedule-btn" data-post-id="<?php echo esc_attr( $rayai_item['id'] ); ?>" title="<?php esc_attr_e( 'Reschedule', 'rayai-content-orchestrator' ); ?>">
											<span class="dashicons dashicons-calendar-alt" style="vertical-align: text-bottom; font-size: 16px; width: 16px; height: 16px;"></span>
										</button>
										<button type="button" class="button rayai-delete-btn" data-post-id="<?php echo esc_attr( $rayai_item['id'] ); ?>" title="<?php esc_attr_e( 'Delete', 'rayai-content-orchestrator' ); ?>">
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
		<?php if ( $rayai_future_count > 0 ) : ?>
			<div class="rayai-card">
				<div class="rayai-card-header">
					<h2>
						<span class="dashicons dashicons-calendar-alt" style="margin-right: 6px; color: #00a32a;"></span>
						<?php esc_html_e( 'Scheduled for Publication', 'rayai-content-orchestrator' ); ?>
					</h2>
				</div>
				<div class="rayai-card-body" style="padding: 0;">
					<table class="widefat striped rayai-scheduled-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Title', 'rayai-content-orchestrator' ); ?></th>
								<th style="width: 90px;"><?php esc_html_e( 'Type', 'rayai-content-orchestrator' ); ?></th>
								<th><?php esc_html_e( 'Categories', 'rayai-content-orchestrator' ); ?></th>
								<th style="width: 180px;"><?php esc_html_e( 'Publishes At', 'rayai-content-orchestrator' ); ?></th>
								<th class="rayai-actions-col"><?php esc_html_e( 'Actions', 'rayai-content-orchestrator' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $rayai_items as $rayai_item ) : ?>
								<?php if ( 'future' !== $rayai_item['status'] ) continue; ?>
								<tr id="rayai-row-<?php echo esc_attr( $rayai_item['id'] ); ?>" data-post-id="<?php echo esc_attr( $rayai_item['id'] ); ?>" data-schedule-at="<?php echo esc_attr( $rayai_item['scheduled_at'] ); ?>">
									<td>
										<strong><?php echo esc_html( $rayai_item['title'] ); ?></strong>
										<?php if ( ! empty( $rayai_item['linkedin'] ) ) : ?>
											<span class="dashicons dashicons-linkedin" style="color: #0a66c2; vertical-align: text-bottom; font-size: 18px; width: 18px; height: 18px; margin-left: 4px;" title="<?php esc_attr_e( 'Will be shared to LinkedIn when published', 'rayai-content-orchestrator' ); ?>"></span>
										<?php endif; ?>
										<?php if ( ! empty( $rayai_item['focus_keyphrase'] ) ) : ?>
											<br><small class="description"><?php esc_html_e( 'Focus:', 'rayai-content-orchestrator' ); ?> <?php echo esc_html( $rayai_item['focus_keyphrase'] ); ?></small>
										<?php endif; ?>
									</td>
									<td>
										<span class="rayai-tag">
											<?php echo 'post' === $rayai_item['type'] ? esc_html__( 'Blog', 'rayai-content-orchestrator' ) : esc_html__( 'Page', 'rayai-content-orchestrator' ); ?>
										</span>
									</td>
									<td>
										<?php if ( ! empty( $rayai_item['categories'] ) ) : ?>
											<?php foreach ( $rayai_item['categories'] as $cat ) : ?>
												<span class="rayai-tag"><?php echo esc_html( $cat ); ?></span>
											<?php endforeach; ?>
										<?php else : ?>
											<span class="description">&mdash;</span>
										<?php endif; ?>
									</td>
									<td class="rayai-schedule-cell">
										<span class="rayai-schedule-display"><?php echo esc_html( $rayai_item['scheduled_at_formatted'] ); ?></span>
									</td>
									<td class="rayai-actions-cell">
										<button type="button" class="button button-primary rayai-publish-now-btn" data-post-id="<?php echo esc_attr( $rayai_item['id'] ); ?>" title="<?php esc_attr_e( 'Publish immediately (skip waiting for scheduled time)', 'rayai-content-orchestrator' ); ?>">
											<span class="dashicons dashicons-megaphone" style="vertical-align: text-bottom; font-size: 16px; width: 16px; height: 16px;"></span>
											<?php esc_html_e( 'Publish Now', 'rayai-content-orchestrator' ); ?>
										</button>
										<a href="<?php echo esc_url( $rayai_item['edit_url'] ); ?>" class="button" target="_blank" title="<?php esc_attr_e( 'Edit', 'rayai-content-orchestrator' ); ?>">
											<span class="dashicons dashicons-edit" style="vertical-align: text-bottom; font-size: 16px; width: 16px; height: 16px;"></span>
										</a>
										<button type="button" class="button rayai-reschedule-btn" data-post-id="<?php echo esc_attr( $rayai_item['id'] ); ?>" title="<?php esc_attr_e( 'Reschedule', 'rayai-content-orchestrator' ); ?>">
											<span class="dashicons dashicons-calendar-alt" style="vertical-align: text-bottom; font-size: 16px; width: 16px; height: 16px;"></span>
										</button>
										<button type="button" class="button rayai-delete-btn" data-post-id="<?php echo esc_attr( $rayai_item['id'] ); ?>" title="<?php esc_attr_e( 'Delete', 'rayai-content-orchestrator' ); ?>">
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
	<?php if ( ! empty( $rayai_linkedin_items ) ) : ?>
		<div class="rayai-card" style="margin-top: 20px;">
			<div class="rayai-card-header">
				<h2>
					<span class="dashicons dashicons-linkedin" style="margin-right: 6px; color: #0a66c2;"></span>
					<?php esc_html_e( 'LinkedIn Sharing Status', 'rayai-content-orchestrator' ); ?>
				</h2>
			</div>
			<div class="rayai-card-body" style="padding: 0;">
				<!-- Bulk action toolbar -->
				<div class="rayai-li-bulk-toolbar" style="padding: 10px 12px; border-bottom: 1px solid #c3c4c7; background: #f6f7f7; display: flex; align-items: center; gap: 10px;">
					<button type="button" class="button rayai-li-bulk-delete-btn" disabled>
						<span class="dashicons dashicons-trash" style="vertical-align: text-bottom; font-size: 16px; width: 16px; height: 16px; color: #d63638;"></span>
						<?php esc_html_e( 'Delete Selected', 'rayai-content-orchestrator' ); ?>
						(<span class="rayai-li-bulk-count">0</span>)
					</button>
					<span class="description" style="margin-left: auto; font-size: 12px;">
						<?php esc_html_e( 'Bulk delete removes posts from this dashboard only. Does not delete WordPress posts or LinkedIn shares.', 'rayai-content-orchestrator' ); ?>
					</span>
				</div>
				<table class="widefat striped">
					<thead>
						<tr>
							<th style="width: 30px; padding-left: 12px;">
								<input type="checkbox" class="rayai-li-select-all" title="<?php esc_attr_e( 'Select all', 'rayai-content-orchestrator' ); ?>" />
							</th>
							<th><?php esc_html_e( 'Title', 'rayai-content-orchestrator' ); ?></th>
							<th style="width: 160px;"><?php esc_html_e( 'Published', 'rayai-content-orchestrator' ); ?></th>
							<th style="width: 200px;"><?php esc_html_e( 'LinkedIn Status', 'rayai-content-orchestrator' ); ?></th>
							<th style="width: 180px;"><?php esc_html_e( 'Actions', 'rayai-content-orchestrator' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $rayai_linkedin_items as $rayai_li ) : ?>
							<tr id="rayai-li-row-<?php echo esc_attr( $rayai_li['id'] ); ?>">
								<td style="padding-left: 12px;">
									<input type="checkbox" class="rayai-li-row-check" value="<?php echo esc_attr( $rayai_li['id'] ); ?>" />
								</td>
								<td>
									<strong><?php echo esc_html( $rayai_li['title'] ); ?></strong>
									<br>
									<a href="<?php echo esc_url( $rayai_li['url'] ); ?>" target="_blank" class="description">
										<?php esc_html_e( 'View on WordPress', 'rayai-content-orchestrator' ); ?> &rarr;
									</a>
									<?php if ( ! empty( $rayai_li['linkedin_commentary'] ) ) : ?>
										<br>
										<a href="#" class="rayai-li-toggle-preview" data-post-id="<?php echo esc_attr( $rayai_li['id'] ); ?>" style="font-size: 12px;">
											<span class="dashicons dashicons-visibility" style="font-size: 14px; width: 14px; height: 14px; vertical-align: text-bottom;"></span>
											<?php esc_html_e( 'Show LinkedIn post preview', 'rayai-content-orchestrator' ); ?>
										</a>
										<div class="rayai-li-preview" id="rayai-li-preview-<?php echo esc_attr( $rayai_li['id'] ); ?>" style="display: none; margin-top: 8px;" data-post-id="<?php echo esc_attr( $rayai_li['id'] ); ?>">
											<!-- View mode -->
											<div class="rayai-li-preview-view">
												<div class="rayai-li-preview-text" style="background: #f6f7f7; border-left: 3px solid #0a66c2; padding: 10px; white-space: pre-wrap; font-family: -apple-system, BlinkMacSystemFont, sans-serif; font-size: 13px; line-height: 1.5; max-width: 500px; border-radius: 2px;"><?php echo esc_html( $rayai_li['linkedin_commentary'] ); ?></div>
												<p style="margin: 6px 0 0; font-size: 11px; color: #646970;">
													<span class="rayai-li-char-count">
														<?php
														printf(
															/* translators: %d: character count */
															esc_html__( '%d characters', 'rayai-content-orchestrator' ),
															esc_html( mb_strlen( $rayai_li['linkedin_commentary'] ) )
														);
														?>
													</span>
												</p>
												<p style="margin: 8px 0 0;">
													<button type="button" class="button button-small rayai-li-edit-btn" data-post-id="<?php echo esc_attr( $rayai_li['id'] ); ?>">
														<span class="dashicons dashicons-edit" style="font-size: 14px; width: 14px; height: 14px; vertical-align: text-bottom;"></span>
														<?php esc_html_e( 'Edit', 'rayai-content-orchestrator' ); ?>
													</button>
													<button type="button" class="button button-small rayai-li-regen-btn" data-post-id="<?php echo esc_attr( $rayai_li['id'] ); ?>">
														<span class="dashicons dashicons-update" style="font-size: 14px; width: 14px; height: 14px; vertical-align: text-bottom;"></span>
														<?php esc_html_e( 'Regenerate', 'rayai-content-orchestrator' ); ?>
													</button>
												</p>
											</div>
											<!-- Edit mode (hidden by default) -->
											<div class="rayai-li-preview-edit" style="display: none;">
												<textarea class="rayai-li-edit-textarea" rows="10" maxlength="2900" style="width: 100%; max-width: 500px; font-family: -apple-system, BlinkMacSystemFont, sans-serif; font-size: 13px; line-height: 1.5; border-left: 3px solid #0a66c2;"><?php echo esc_textarea( $rayai_li['linkedin_commentary'] ); ?></textarea>
												<p style="margin: 4px 0 0; font-size: 11px; color: #646970;">
													<span class="rayai-li-edit-count">
														<?php
														printf(
															/* translators: %d: character count */
															esc_html__( '%d / 2900 characters', 'rayai-content-orchestrator' ),
															esc_html( mb_strlen( $rayai_li['linkedin_commentary'] ) )
														);
														?>
													</span>
												</p>
												<p style="margin: 8px 0 0;">
													<button type="button" class="button button-primary button-small rayai-li-save-btn" data-post-id="<?php echo esc_attr( $rayai_li['id'] ); ?>">
														<?php esc_html_e( 'Save', 'rayai-content-orchestrator' ); ?>
													</button>
													<button type="button" class="button button-small rayai-li-cancel-btn" data-post-id="<?php echo esc_attr( $rayai_li['id'] ); ?>">
														<?php esc_html_e( 'Cancel', 'rayai-content-orchestrator' ); ?>
													</button>
												</p>
											</div>
										</div>
									<?php endif; ?>
								</td>
								<td>
									<?php echo esc_html( wp_date( 'Y-m-d H:i', $rayai_li['published_at'] ) ); ?>
								</td>
								<td class="rayai-li-status-<?php echo esc_attr( $rayai_li['id'] ); ?>">
									<?php if ( 'shared' === $rayai_li['linkedin_status'] ) : ?>
										<span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span>
										<strong style="color: #00a32a;"><?php esc_html_e( 'Shared', 'rayai-content-orchestrator' ); ?></strong>
										<br><small class="description"><?php echo esc_html( wp_date( 'Y-m-d H:i', $rayai_li['shared_at'] ) ); ?></small>
									<?php elseif ( 'error' === $rayai_li['linkedin_status'] ) : ?>
										<span class="dashicons dashicons-warning" style="color: #d63638;"></span>
										<strong style="color: #d63638;"><?php esc_html_e( 'Failed', 'rayai-content-orchestrator' ); ?></strong>
										<br><small class="description" style="color: #d63638;"><?php echo esc_html( mb_substr( $rayai_li['linkedin_error'], 0, 100 ) ); ?></small>
									<?php else : ?>
										<span class="dashicons dashicons-minus" style="color: #646970;"></span>
										<em class="description"><?php esc_html_e( 'Not shared yet', 'rayai-content-orchestrator' ); ?></em>
									<?php endif; ?>
								</td>
								<td>
									<button type="button" class="button rayai-li-share-btn" data-post-id="<?php echo esc_attr( $rayai_li['id'] ); ?>">
										<span class="dashicons dashicons-linkedin" style="vertical-align: text-bottom; font-size: 16px; width: 16px; height: 16px;"></span>
										<?php
										if ( 'shared' === $rayai_li['linkedin_status'] ) {
											esc_html_e( 'Re-share', 'rayai-content-orchestrator' );
										} elseif ( 'error' === $rayai_li['linkedin_status'] ) {
											esc_html_e( 'Retry', 'rayai-content-orchestrator' );
										} else {
											esc_html_e( 'Share Now', 'rayai-content-orchestrator' );
										}
										?>
									</button>
									<button type="button" class="button rayai-li-remove-btn" data-post-id="<?php echo esc_attr( $rayai_li['id'] ); ?>" title="<?php esc_attr_e( 'Remove from LinkedIn dashboard (does not delete the WordPress post or LinkedIn share)', 'rayai-content-orchestrator' ); ?>">
										<span class="dashicons dashicons-trash" style="vertical-align: text-bottom; font-size: 16px; width: 16px; height: 16px; color: #d63638;"></span>
									</button>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<p class="description" style="padding: 12px 16px; margin: 0; background: #f6f7f7; border-top: 1px solid #c3c4c7;">
					<span class="dashicons dashicons-info" style="color: #2271b1; vertical-align: text-bottom;"></span>
					<?php esc_html_e( 'Check your LinkedIn profile feed at', 'rayai-content-orchestrator' ); ?>
					<a href="https://www.linkedin.com/in/me/recent-activity/all/" target="_blank">linkedin.com/in/me/recent-activity</a>
					<?php esc_html_e( 'to see shared posts. Posts may take a few seconds to appear.', 'rayai-content-orchestrator' ); ?>
				</p>
			</div>
		</div>
	<?php endif; ?>
</div>

<!-- Reschedule modal -->
<div id="rayai-reschedule-modal" class="rayai-modal" style="display: none;">
	<div class="rayai-modal-backdrop"></div>
	<div class="rayai-modal-content">
		<h2><?php esc_html_e( 'Reschedule', 'rayai-content-orchestrator' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Select a new date and time for this item.', 'rayai-content-orchestrator' ); ?></p>
		<input type="datetime-local" id="rayai-reschedule-input" class="regular-text" />
		<p style="margin-top: 16px; text-align: right;">
			<button type="button" class="button" id="rayai-reschedule-cancel"><?php esc_html_e( 'Cancel', 'rayai-content-orchestrator' ); ?></button>
			<button type="button" class="button button-primary" id="rayai-reschedule-save"><?php esc_html_e( 'Save', 'rayai-content-orchestrator' ); ?></button>
		</p>
	</div>
</div>

<script>
(function($) {
	var ajaxUrl = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';
	var nonce   = '<?php echo esc_js( wp_create_nonce( 'rayai_nonce' ) ); ?>';
	var rescheduleTargetId = null;

	// Approve button
	$('.rayai-approve-btn').on('click', function() {
		var $btn    = $(this);
		var postId  = $btn.data('post-id');
		var $row    = $('#rayai-row-' + postId);

		$btn.prop('disabled', true).text('Approving...');

		$.post(ajaxUrl, {
			action:  'rayai_approve_scheduled',
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
	$('#rayai-run-catchup-now').on('click', function() {
		var $btn    = $(this);
		var $result = $('#rayai-catchup-result');

		$btn.prop('disabled', true);
		$result.html('<span class="spinner is-active" style="float:none; margin:0;"></span>');

		$.post(ajaxUrl, {
			action: 'rayai_run_catchup',
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
	$('.rayai-publish-now-btn').on('click', function() {
		if (!confirm('<?php echo esc_js( __( 'Publish this post immediately, ignoring the scheduled time?', 'rayai-content-orchestrator' ) ); ?>')) return;

		var $btn    = $(this);
		var postId  = $btn.data('post-id');
		var $row    = $('#rayai-row-' + postId);

		$btn.prop('disabled', true).text('Publishing...');

		$.post(ajaxUrl, {
			action:  'rayai_publish_now',
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
	$('.rayai-delete-btn').on('click', function() {
		if (!confirm('<?php echo esc_js( __( 'Move this item to the trash?', 'rayai-content-orchestrator' ) ); ?>')) return;

		var $btn   = $(this);
		var postId = $btn.data('post-id');
		var $row   = $('#rayai-row-' + postId);

		$btn.prop('disabled', true);

		$.post(ajaxUrl, {
			action:  'rayai_delete_scheduled',
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
		var count = $('.rayai-li-row-check:checked').length;
		$('.rayai-li-bulk-count').text(count);
		$('.rayai-li-bulk-delete-btn').prop('disabled', count === 0);

		// Sync select-all checkbox state.
		var total = $('.rayai-li-row-check').length;
		var $all  = $('.rayai-li-select-all');
		if (count === 0) {
			$all.prop('checked', false).prop('indeterminate', false);
		} else if (count === total) {
			$all.prop('checked', true).prop('indeterminate', false);
		} else {
			$all.prop('checked', false).prop('indeterminate', true);
		}
	}

	// Select-all checkbox toggles all row checkboxes.
	$(document).on('change', '.rayai-li-select-all', function() {
		var checked = $(this).prop('checked');
		$('.rayai-li-row-check').prop('checked', checked);
		aiccUpdateLiBulkState();
	});

	// Row checkbox change syncs bulk count + select-all state.
	$(document).on('change', '.rayai-li-row-check', function() {
		aiccUpdateLiBulkState();
	});

	// Bulk delete button.
	$(document).on('click', '.rayai-li-bulk-delete-btn', function() {
		var $btn    = $(this);
		var ids     = [];
		$('.rayai-li-row-check:checked').each(function() {
			ids.push($(this).val());
		});

		if (ids.length === 0) return;

		if (!confirm('<?php echo esc_js( __( 'Remove the selected posts from the LinkedIn Sharing Status dashboard? The WordPress posts and any LinkedIn shares will NOT be deleted.', 'rayai-content-orchestrator' ) ); ?>'.replace('the selected posts', ids.length + ' selected post' + (ids.length === 1 ? '' : 's')))) {
			return;
		}

		var originalHtml = $btn.html();
		$btn.prop('disabled', true).html('<span class="spinner is-active" style="float:none; margin:0;"></span> Deleting...');

		$.post(ajaxUrl, {
			action:     'rayai_linkedin_bulk_remove',
			nonce:      nonce,
			'post_ids[]': ids,
		}, function(response) {
			if (response.success) {
				$.each(response.data.post_ids, function(i, id) {
					$('#rayai-li-row-' + id).fadeOut(300, function() { $(this).remove(); });
				});
				setTimeout(function() {
					aiccUpdateLiBulkState();
					// Reload if empty to refresh the empty state.
					if ($('.rayai-li-row-check').length === 0) {
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
	$(document).on('click', '.rayai-li-remove-btn', function() {
		if (!confirm('<?php echo esc_js( __( 'Remove this post from the LinkedIn Sharing Status dashboard? The WordPress post and any existing LinkedIn share will NOT be deleted — this only hides it from this list.', 'rayai-content-orchestrator' ) ); ?>')) {
			return;
		}

		var $btn   = $(this);
		var postId = $btn.data('post-id');
		var $row   = $('#rayai-li-row-' + postId);

		$btn.prop('disabled', true);

		$.post(ajaxUrl, {
			action:  'rayai_linkedin_remove_from_dashboard',
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
	$(document).on('click', '.rayai-li-toggle-preview', function(e) {
		e.preventDefault();
		var postId = $(this).data('post-id');
		$('#rayai-li-preview-' + postId).slideToggle(150);
	});

	// Edit LinkedIn commentary — switch to edit mode
	$(document).on('click', '.rayai-li-edit-btn', function() {
		var postId = $(this).data('post-id');
		var $wrap  = $('#rayai-li-preview-' + postId);
		$wrap.find('.rayai-li-preview-view').hide();
		$wrap.find('.rayai-li-preview-edit').show();
		$wrap.find('.rayai-li-edit-textarea').focus();
	});

	// Cancel edit — restore view mode
	$(document).on('click', '.rayai-li-cancel-btn', function() {
		var postId = $(this).data('post-id');
		var $wrap  = $('#rayai-li-preview-' + postId);
		// Restore original text in textarea.
		var original = $wrap.find('.rayai-li-preview-text').text();
		$wrap.find('.rayai-li-edit-textarea').val(original);
		$wrap.find('.rayai-li-preview-edit').hide();
		$wrap.find('.rayai-li-preview-view').show();
	});

	// Live char count while editing
	$(document).on('input', '.rayai-li-edit-textarea', function() {
		var len = $(this).val().length;
		$(this).closest('.rayai-li-preview-edit').find('.rayai-li-edit-count').text(len + ' / 2900 characters');
	});

	// Save edited commentary
	$(document).on('click', '.rayai-li-save-btn', function() {
		var $btn       = $(this);
		var postId     = $btn.data('post-id');
		var $wrap      = $('#rayai-li-preview-' + postId);
		var commentary = $wrap.find('.rayai-li-edit-textarea').val();

		$btn.prop('disabled', true).text('Saving...');

		$.post(ajaxUrl, {
			action:     'rayai_linkedin_save_commentary',
			nonce:      nonce,
			post_id:    postId,
			commentary: commentary,
		}, function(response) {
			if (response.success) {
				$wrap.find('.rayai-li-preview-text').text(response.data.commentary);
				$wrap.find('.rayai-li-char-count').text(response.data.length + ' characters');
				$wrap.find('.rayai-li-preview-edit').hide();
				$wrap.find('.rayai-li-preview-view').show();
				$btn.prop('disabled', false).text('<?php echo esc_js( __( 'Save', 'rayai-content-orchestrator' ) ); ?>');
			} else {
				alert('Error: ' + (response.data.message || 'Unknown error'));
				$btn.prop('disabled', false).text('<?php echo esc_js( __( 'Save', 'rayai-content-orchestrator' ) ); ?>');
			}
		}).fail(function(xhr) {
			alert('Request failed: ' + xhr.status);
			$btn.prop('disabled', false).text('<?php echo esc_js( __( 'Save', 'rayai-content-orchestrator' ) ); ?>');
		});
	});

	// Regenerate commentary via AI
	$(document).on('click', '.rayai-li-regen-btn', function() {
		var $btn   = $(this);
		var postId = $btn.data('post-id');
		var $wrap  = $('#rayai-li-preview-' + postId);

		if (!confirm('<?php echo esc_js( __( 'Regenerate the LinkedIn post via AI? The current text will be replaced.', 'rayai-content-orchestrator' ) ); ?>')) {
			return;
		}

		var originalHtml = $btn.html();
		$btn.prop('disabled', true).html('<span class="spinner is-active" style="float:none; margin:0;"></span> Generating...');

		$.post({
			url:     ajaxUrl,
			data:    {
				action:  'rayai_linkedin_regenerate_commentary',
				nonce:   nonce,
				post_id: postId,
			},
			timeout: 120000,
		}).done(function(response) {
			if (response.success) {
				$wrap.find('.rayai-li-preview-text').text(response.data.commentary);
				$wrap.find('.rayai-li-edit-textarea').val(response.data.commentary);
				$wrap.find('.rayai-li-char-count').text(response.data.length + ' characters');
				$wrap.find('.rayai-li-edit-count').text(response.data.length + ' / 2900 characters');
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
	$(document).on('click', '.rayai-li-share-btn', function() {
		var $btn    = $(this);
		var postId  = $btn.data('post-id');
		var $status = $('.rayai-li-status-' + postId);

		$btn.prop('disabled', true).html('<span class="spinner is-active" style="float:none; margin:0;"></span> Sharing...');

		$.post(ajaxUrl, {
			action:  'rayai_linkedin_share_now',
			nonce:   nonce,
			post_id: postId,
		}, function(response) {
			if (response.success) {
				$status.html(
					'<span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span>' +
					' <strong style="color: #00a32a;"><?php echo esc_js( __( 'Shared', 'rayai-content-orchestrator' ) ); ?></strong>' +
					'<br><small class="description"><?php echo esc_js( __( 'Just now', 'rayai-content-orchestrator' ) ); ?></small>'
				);
				$btn.prop('disabled', false).html('<span class="dashicons dashicons-linkedin" style="vertical-align: text-bottom; font-size: 16px; width: 16px; height: 16px;"></span> <?php echo esc_js( __( 'Re-share', 'rayai-content-orchestrator' ) ); ?>');
				alert('<?php echo esc_js( __( 'Successfully shared to LinkedIn! Check your profile feed.', 'rayai-content-orchestrator' ) ); ?>');
			} else {
				var msg = (response.data && response.data.message) ? response.data.message : 'Unknown error';
				$status.html(
					'<span class="dashicons dashicons-warning" style="color: #d63638;"></span>' +
					' <strong style="color: #d63638;"><?php echo esc_js( __( 'Failed', 'rayai-content-orchestrator' ) ); ?></strong>' +
					'<br><small class="description" style="color: #d63638;">' + $('<div>').text(msg).html() + '</small>'
				);
				$btn.prop('disabled', false).html('<span class="dashicons dashicons-linkedin" style="vertical-align: text-bottom; font-size: 16px; width: 16px; height: 16px;"></span> <?php echo esc_js( __( 'Retry', 'rayai-content-orchestrator' ) ); ?>');
				alert('LinkedIn error: ' + msg);
			}
		}).fail(function(xhr) {
			alert('Request failed: ' + xhr.status + ' ' + xhr.statusText);
			$btn.prop('disabled', false).html('<span class="dashicons dashicons-linkedin"></span> Retry');
		});
	});

	// Reschedule button
	$('.rayai-reschedule-btn').on('click', function() {
		var postId     = $(this).data('post-id');
		var $row       = $('#rayai-row-' + postId);
		var scheduleAt = parseInt($row.data('schedule-at'), 10);

		rescheduleTargetId = postId;

		// Convert timestamp to datetime-local value (YYYY-MM-DDTHH:mm).
		if (scheduleAt) {
			var d = new Date(scheduleAt * 1000);
			var pad = function(n) { return n < 10 ? '0' + n : n; };
			var val = d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate())
				+ 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
			$('#rayai-reschedule-input').val(val);
		}
		$('#rayai-reschedule-modal').show();
	});

	// Reschedule modal — cancel
	$('#rayai-reschedule-cancel, .rayai-modal-backdrop').on('click', function() {
		$('#rayai-reschedule-modal').hide();
		rescheduleTargetId = null;
	});

	// Review table — bulk select.
	function updateReviewBulkState() {
		var count = $('.rayai-review-check:checked').length;
		var total = $('.rayai-review-check').length;
		$('#rayai-bulk-actions-bar').toggle(count > 0);
		$('#rayai-review-check-all-th, #rayai-review-check-all').prop('checked', count === total && total > 0);
		if (count > 0 && count < total) $('#rayai-review-check-all-th').prop('indeterminate', true);
	}

	$('#rayai-review-check-all-th, #rayai-review-check-all').on('change', function() {
		$('.rayai-review-check').prop('checked', $(this).prop('checked'));
		$('#rayai-review-check-all-th, #rayai-review-check-all').prop('checked', $(this).prop('checked'));
		updateReviewBulkState();
	});

	$(document).on('change', '.rayai-review-check', function() {
		updateReviewBulkState();
	});

	// Bulk delete.
	$('#rayai-bulk-delete-btn').on('click', function() {
		var ids = [];
		$('.rayai-review-check:checked').each(function() { ids.push($(this).val()); });
		if (!ids.length) return;
		if (!confirm('<?php echo esc_js( __( 'Move', 'rayai-content-orchestrator' ) ); ?> ' + ids.length + ' <?php echo esc_js( __( 'items to the trash?', 'rayai-content-orchestrator' ) ); ?>')) return;

		var $btn = $(this);
		$btn.prop('disabled', true).html('<span class="spinner is-active" style="float:none; margin:0;"></span> <?php echo esc_js( __( 'Deleting...', 'rayai-content-orchestrator' ) ); ?>');
		var done = 0;
		$.each(ids, function(_, id) {
			$.post(ajaxUrl, { action: 'rayai_delete_scheduled', nonce: nonce, post_id: id }, function() {
				$('#rayai-row-' + id).fadeOut(300, function() { $(this).remove(); });
				done++;
				if (done >= ids.length) { setTimeout(function() { location.reload(); }, 500); }
			});
		});
	});

	// Bulk approve.
	$('#rayai-bulk-approve-btn').on('click', function() {
		var ids = [];
		$('.rayai-review-check:checked').each(function() { ids.push($(this).val()); });
		if (!ids.length) return;
		if (!confirm('<?php echo esc_js( __( 'Approve', 'rayai-content-orchestrator' ) ); ?> ' + ids.length + ' <?php echo esc_js( __( 'items?', 'rayai-content-orchestrator' ) ); ?>')) return;

		var $btn = $(this);
		$btn.prop('disabled', true).html('<span class="spinner is-active" style="float:none; margin:0;"></span> <?php echo esc_js( __( 'Approving...', 'rayai-content-orchestrator' ) ); ?>');
		var done = 0;
		$.each(ids, function(_, id) {
			$.post(ajaxUrl, { action: 'rayai_approve_scheduled', nonce: nonce, post_id: id }, function() {
				$('#rayai-row-' + id).fadeOut(300, function() { $(this).remove(); });
				done++;
				if (done >= ids.length) { setTimeout(function() { location.reload(); }, 500); }
			});
		});
	});

	// Reschedule modal — save
	$('#rayai-reschedule-save').on('click', function() {
		var newSchedule = $('#rayai-reschedule-input').val();
		if (!newSchedule) { alert('Please select a date and time.'); return; }

		var $btn = $(this);
		$btn.prop('disabled', true).text('Saving...');

		$.post(ajaxUrl, {
			action:      'rayai_reschedule',
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
