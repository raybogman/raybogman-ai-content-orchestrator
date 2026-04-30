<?php
/**
 * Dashboard page — overview of AI-generated content, stats, and quick actions.
 *
 * @package RayAI_Content_Orchestrator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

// Count AI-generated posts.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$rayai_total_posts = (int) $wpdb->get_var(
	"SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_rayai_generated' AND meta_value = '1'"
);

// Count posts this month.
$rayai_month_start = gmdate( 'Y-m-01 00:00:00' );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$rayai_month_posts = (int) $wpdb->get_var( $wpdb->prepare(
	"SELECT COUNT(*) FROM {$wpdb->posts} p
	 JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
	 WHERE pm.meta_key = '_rayai_generated' AND pm.meta_value = '1'
	   AND p.post_date >= %s",
	$rayai_month_start
) );

// Scheduled / pending review.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$rayai_scheduled_count = (int) $wpdb->get_var(
	"SELECT COUNT(*) FROM {$wpdb->posts} p
	 JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
	 WHERE pm.meta_key = '_rayai_generated' AND pm.meta_value = '1'
	   AND p.post_status IN ('draft', 'future')"
);

// Top performing posts (by comment count as a proxy; pageviews need analytics).
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$rayai_top_posts = $wpdb->get_results(
	"SELECT p.ID, p.post_title, p.post_date, p.comment_count, p.post_status
	 FROM {$wpdb->posts} p
	 JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
	 WHERE pm.meta_key = '_rayai_generated' AND pm.meta_value = '1'
	   AND p.post_status = 'publish'
	 ORDER BY p.comment_count DESC, p.post_date DESC
	 LIMIT 10"
);

// Posts needing refresh (published more than 6 months ago).
$rayai_six_months_ago = gmdate( 'Y-m-d H:i:s', strtotime( '-6 months' ) );
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$rayai_stale_posts = $wpdb->get_results( $wpdb->prepare(
	"SELECT p.ID, p.post_title, p.post_date, p.post_modified,
	        CHAR_LENGTH(p.post_content) AS content_length
	 FROM {$wpdb->posts} p
	 JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID
	 WHERE pm.meta_key = '_rayai_generated' AND pm.meta_value = '1'
	   AND p.post_status = 'publish'
	   AND p.post_modified < %s
	 ORDER BY p.post_modified ASC
	 LIMIT 10",
	$rayai_six_months_ago
) );
?>
<div class="wrap">
	<h1>
		<span class="dashicons dashicons-chart-area" style="font-size: 28px; width: 28px; height: 28px; margin-right: 8px; vertical-align: text-bottom;"></span>
		<?php esc_html_e( 'RayAI – Content Orchestrator — Dashboard', 'rayai-content-orchestrator' ); ?>
	</h1>

	<!-- Stats cards -->
	<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; max-width: 900px; margin-top: 20px;">
		<div class="rayai-card" style="text-align: center; padding: 20px;">
			<div style="font-size: 36px; font-weight: 700; color: #2271b1;"><?php echo esc_html( $rayai_total_posts ); ?></div>
			<div style="color: #50575e; margin-top: 4px;"><?php esc_html_e( 'Total AI Posts', 'rayai-content-orchestrator' ); ?></div>
		</div>
		<div class="rayai-card" style="text-align: center; padding: 20px;">
			<div style="font-size: 36px; font-weight: 700; color: #00a32a;"><?php echo esc_html( $rayai_month_posts ); ?></div>
			<div style="color: #50575e; margin-top: 4px;"><?php esc_html_e( 'This Month', 'rayai-content-orchestrator' ); ?></div>
		</div>
		<div class="rayai-card" style="text-align: center; padding: 20px;">
			<div style="font-size: 36px; font-weight: 700; color: #dba617;"><?php echo esc_html( $rayai_scheduled_count ); ?></div>
			<div style="color: #50575e; margin-top: 4px;"><?php esc_html_e( 'Scheduled / Pending', 'rayai-content-orchestrator' ); ?></div>
		</div>
	</div>

	<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; max-width: 1200px; margin-top: 20px;">
		<!-- Recent AI Posts -->
		<div class="rayai-card">
			<div class="rayai-card-header">
				<h2><?php esc_html_e( 'Recent AI-Generated Posts', 'rayai-content-orchestrator' ); ?></h2>
			</div>
			<div class="rayai-card-body">
				<?php if ( ! empty( $rayai_top_posts ) ) : ?>
					<table class="widefat striped" style="margin: 0;">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Title', 'rayai-content-orchestrator' ); ?></th>
								<th style="width:100px;"><?php esc_html_e( 'Date', 'rayai-content-orchestrator' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $rayai_top_posts as $post ) : ?>
								<tr>
									<td>
										<a href="<?php echo esc_url( get_permalink( $post->ID ) ); ?>" target="_blank">
											<?php echo esc_html( $post->post_title ); ?>
										</a>
									</td>
									<td><?php echo esc_html( wp_date( 'M j', strtotime( $post->post_date ) ) ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php else : ?>
					<p class="description"><em><?php esc_html_e( 'No published AI-generated posts yet. Create your first one!', 'rayai-content-orchestrator' ); ?></em></p>
				<?php endif; ?>
			</div>
		</div>

		<!-- Posts needing refresh -->
		<div class="rayai-card">
			<div class="rayai-card-header">
				<h2><?php esc_html_e( 'Posts Needing a Refresh', 'rayai-content-orchestrator' ); ?></h2>
			</div>
			<div class="rayai-card-body">
				<p class="description" style="margin-top: 0;">
					<?php esc_html_e( 'These posts haven\'t been updated in over 6 months. Refreshing old content can boost your search rankings.', 'rayai-content-orchestrator' ); ?>
				</p>
				<?php if ( ! empty( $rayai_stale_posts ) ) : ?>
					<table class="widefat striped" style="margin: 0;">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Title', 'rayai-content-orchestrator' ); ?></th>
								<th style="width:120px;"><?php esc_html_e( 'Last Updated', 'rayai-content-orchestrator' ); ?></th>
								<th style="width:80px;"><?php esc_html_e( 'Words', 'rayai-content-orchestrator' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $rayai_stale_posts as $post ) : ?>
								<tr>
									<td>
										<a href="<?php echo esc_url( get_edit_post_link( $post->ID ) ); ?>">
											<?php echo esc_html( $post->post_title ); ?>
										</a>
									</td>
									<td><?php echo esc_html( human_time_diff( strtotime( $post->post_modified ), time() ) ); ?> <?php esc_html_e( 'ago', 'rayai-content-orchestrator' ); ?></td>
									<td style="text-align: center;"><?php echo esc_html( (int) ( $post->content_length / 6 ) ); ?></td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php else : ?>
					<p class="description"><em><?php esc_html_e( 'All posts are up to date! No stale content found.', 'rayai-content-orchestrator' ); ?></em></p>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<!-- Quick Actions -->
	<div class="rayai-card" style="max-width: 1200px; margin-top: 20px;">
		<div class="rayai-card-header">
			<h2><?php esc_html_e( 'Quick Actions', 'rayai-content-orchestrator' ); ?></h2>
		</div>
		<div class="rayai-card-body" style="display: flex; gap: 12px; flex-wrap: wrap;">
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=rayai-create' ) ); ?>" class="button button-primary button-large">
				<span class="dashicons dashicons-edit-large" style="vertical-align: text-bottom; font-size: 18px; width: 18px; height: 18px; margin-right: 4px;"></span>
				<?php esc_html_e( 'Create Content', 'rayai-content-orchestrator' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=rayai-bulk-create' ) ); ?>" class="button button-large">
				<span class="dashicons dashicons-admin-page" style="vertical-align: text-bottom; font-size: 18px; width: 18px; height: 18px; margin-right: 4px;"></span>
				<?php esc_html_e( 'Bulk Create', 'rayai-content-orchestrator' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=rayai-refresh' ) ); ?>" class="button button-large">
				<span class="dashicons dashicons-update" style="vertical-align: text-bottom; font-size: 18px; width: 18px; height: 18px; margin-right: 4px;"></span>
				<?php esc_html_e( 'Refresh Content', 'rayai-content-orchestrator' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=rayai-scheduled' ) ); ?>" class="button button-large">
				<span class="dashicons dashicons-calendar-alt" style="vertical-align: text-bottom; font-size: 18px; width: 18px; height: 18px; margin-right: 4px;"></span>
				<?php esc_html_e( 'Scheduled', 'rayai-content-orchestrator' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=rayai-settings' ) ); ?>" class="button button-large">
				<span class="dashicons dashicons-admin-generic" style="vertical-align: text-bottom; font-size: 18px; width: 18px; height: 18px; margin-right: 4px;"></span>
				<?php esc_html_e( 'Settings', 'rayai-content-orchestrator' ); ?>
			</a>
		</div>
	</div>
</div>
