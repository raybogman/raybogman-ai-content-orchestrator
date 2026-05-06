<?php
/**
 * WordPress content publisher.
 *
 * @package Raybogman_Content_Orchestrator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RBCO_Publisher
 *
 * Publishes AI-generated content to WordPress with tags, categories,
 * and Yoast SEO support.
 */
class RBCO_Publisher {

	/**
	 * Create a WordPress post or page from AI-generated content.
	 *
	 * @param string $content_type   Either 'blog' or 'page'.
	 * @param array  $ai_result      AI-generated content array.
	 * @param string $status         Post status ('draft' or 'publish').
	 * @param array  $category_ids   Pre-selected WordPress category IDs.
	 * @param int    $schedule_at    Unix timestamp for scheduled publication (0 = immediate).
	 * @return array Result array with success status and post details.
	 */
	public static function create( $content_type, $ai_result, $status = 'draft', $category_ids = array(), $schedule_at = 0 ) {
		$post_type   = ( 'blog' === $content_type ) ? 'post' : 'page';
		$status      = in_array( $status, array( 'draft', 'publish' ), true ) ? $status : 'draft';
		$schedule_at = absint( $schedule_at );
		$is_scheduled = $schedule_at > time();
		$needs_review = $is_scheduled && 'draft' === $status;

		$post_data = array(
			'post_title'   => sanitize_text_field( $ai_result['seo_title'] ),
			'post_content' => wp_kses_post( $ai_result['content'] ),
			'post_name'    => sanitize_title( $ai_result['slug'] ),
			'post_excerpt' => sanitize_text_field( $ai_result['meta_description'] ),
			'post_type'    => $post_type,
			'post_author'  => get_current_user_id(),
		);

		// Determine post status and date.
		if ( $is_scheduled && 'publish' === $status ) {
			// Scheduled publish (no HITL) — use WP's native 'future' status.
			$post_data['post_status']   = 'future';
			$post_data['post_date']     = wp_date( 'Y-m-d H:i:s', $schedule_at );
			$post_data['post_date_gmt'] = gmdate( 'Y-m-d H:i:s', $schedule_at );
		} elseif ( $is_scheduled && 'draft' === $status ) {
			// Draft awaiting human review, will be scheduled after approval.
			$post_data['post_status'] = 'draft';
		} else {
			// Normal draft or immediate publish.
			$post_data['post_status'] = $status;
		}

		// Resolve categories BEFORE inserting the post.
		if ( 'post' === $post_type ) {
			if ( ! empty( $category_ids ) ) {
				$post_data['post_category'] = array_map( 'absint', $category_ids );
			} elseif ( ! empty( $ai_result['categories'] ) ) {
				$ai_cat_ids = self::resolve_categories( $ai_result['categories'] );
				if ( ! empty( $ai_cat_ids ) ) {
					$post_data['post_category'] = $ai_cat_ids;
				}
			}
		}

		// Insert the post.
		$post_id = wp_insert_post( $post_data, true );

		if ( is_wp_error( $post_id ) ) {
			return array(
				'success' => false,
				'error'   => $post_id->get_error_message(),
			);
		}

		// Handle tags (posts only).
		if ( 'post' === $post_type && ! empty( $ai_result['tags'] ) ) {
			$tag_ids = self::resolve_tags( $ai_result['tags'] );
			if ( ! empty( $tag_ids ) ) {
				wp_set_post_tags( $post_id, $tag_ids );
			}
		}

		// Store scheduling meta.
		update_post_meta( $post_id, '_rbco_generated', '1' );
		if ( $is_scheduled ) {
			update_post_meta( $post_id, '_rbco_scheduled_publish_at', $schedule_at );
			update_post_meta( $post_id, '_rbco_needs_review', $needs_review ? '1' : '0' );
		}

		// Update Yoast SEO fields.
		$yoast_ok = self::update_yoast_meta( $post_id, $ai_result );

		$post = get_post( $post_id );

		return array(
			'success'      => true,
			'id'           => $post_id,
			'title'        => $post->post_title,
			'slug'         => $post->post_name,
			'status'       => $post->post_status,
			'url'          => get_permalink( $post_id ),
			'edit_url'     => get_edit_post_link( $post_id, 'raw' ),
			'yoast'        => $yoast_ok,
			'scheduled_at' => $is_scheduled ? $schedule_at : 0,
			'needs_review' => $needs_review,
		);
	}

	/**
	 * Approve a scheduled draft — transitions it to 'future' status with the stored schedule date.
	 *
	 * @param int $post_id The draft post ID.
	 * @return array|WP_Error Result array or WP_Error on failure.
	 */
	public static function approve_scheduled( $post_id ) {
		$post_id = absint( $post_id );
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return new WP_Error( 'not_found', __( 'Post not found.', 'raybogman-ai-content-orchestrator' ) );
		}

		if ( 'draft' !== $post->post_status ) {
			return new WP_Error( 'invalid_status', __( 'Only draft posts can be approved.', 'raybogman-ai-content-orchestrator' ) );
		}

		$schedule_at = (int) get_post_meta( $post_id, '_rbco_scheduled_publish_at', true );

		// If no schedule, publish immediately on approval.
		if ( $schedule_at <= 0 ) {
			$now     = current_time( 'mysql' );
			$now_gmt = current_time( 'mysql', 1 );
			$updated = wp_update_post( array(
				'ID'            => $post_id,
				'post_status'   => 'publish',
				'post_date'     => $now,
				'post_date_gmt' => $now_gmt,
				'edit_date'     => true,
			), true );

			if ( is_wp_error( $updated ) || 0 === $updated ) {
				return is_wp_error( $updated ) ? $updated : new WP_Error( 'update_failed', __( 'Failed to publish post.', 'raybogman-ai-content-orchestrator' ) );
			}

			update_post_meta( $post_id, '_rbco_needs_review', '0' );

			return array(
				'success' => true,
				'id'      => $post_id,
				'status'  => 'publish',
				'url'     => get_permalink( $post_id ),
			);
		}

		if ( $schedule_at <= time() + 60 ) {
			return new WP_Error( 'invalid_schedule', __( 'Scheduled time must be at least 1 minute in the future.', 'raybogman-ai-content-orchestrator' ) );
		}

		// IMPORTANT: wp_update_post() ignores post_date changes for drafts unless
		// edit_date=true is passed. Without this, WordPress keeps the draft's
		// empty post_date_gmt ('0000-00-00 00:00:00'), then sees the date as
		// "past" and silently converts 'future' → 'publish', publishing the
		// post immediately instead of scheduling it.
		$updated = wp_update_post( array(
			'ID'            => $post_id,
			'post_status'   => 'future',
			'post_date'     => wp_date( 'Y-m-d H:i:s', $schedule_at ),
			'post_date_gmt' => gmdate( 'Y-m-d H:i:s', $schedule_at ),
			'edit_date'     => true,
		), true );

		if ( is_wp_error( $updated ) || 0 === $updated ) {
			return is_wp_error( $updated ) ? $updated : new WP_Error( 'update_failed', __( 'Failed to update post.', 'raybogman-ai-content-orchestrator' ) );
		}

		// Verify the status actually stuck. If WordPress converted it back to
		// publish (due to date handling edge cases), report the issue.
		$refreshed = get_post( $post_id );
		if ( 'future' !== $refreshed->post_status ) {
			return new WP_Error(
				'status_not_future',
				sprintf(
					/* translators: %s: actual post status */
					__( 'WordPress did not accept the scheduled status (got: %s). The scheduled date may be too close to now.', 'raybogman-ai-content-orchestrator' ),
					$refreshed->post_status
				)
			);
		}

		update_post_meta( $post_id, '_rbco_needs_review', '0' );

		// Ensure the native publish_future_post cron event is registered.
		wp_clear_scheduled_hook( 'publish_future_post', array( $post_id ) );
		wp_schedule_single_event( $schedule_at, 'publish_future_post', array( $post_id ) );

		return array(
			'success'      => true,
			'id'           => $post_id,
			'status'       => 'future',
			'scheduled_at' => $schedule_at,
		);
	}

	/**
	 * Catch up on overdue scheduled posts that WP cron failed to publish.
	 *
	 * WordPress cron only runs when someone visits the site (unless real cron
	 * is configured). On low-traffic sites or sites with DISABLE_WP_CRON,
	 * scheduled posts can get stuck at 'future' status indefinitely.
	 * This method manually publishes any 'future' posts whose time has passed.
	 *
	 * @return int Number of posts manually published.
	 */
	public static function catch_up_overdue() {
		global $wpdb;

		$query = new WP_Query( array(
			'post_type'      => array( 'post', 'page' ),
			'post_status'    => 'future',
			'posts_per_page' => -1,
   // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Required for querying AI-generated posts.
			'meta_query'     => array(
				array(
					'key'     => '_rbco_scheduled_publish_at',
					'compare' => 'EXISTS',
				),
			),
		) );

		$published = 0;
		$details   = array();
		foreach ( $query->posts as $post ) {
			// Use our stored scheduled timestamp as the source of truth. Fall
			// back to post_date_gmt if the meta is missing for any reason.
			$meta_time = (int) get_post_meta( $post->ID, '_rbco_scheduled_publish_at', true );
			$date_time = strtotime( $post->post_date_gmt . ' GMT' );
			$target    = $meta_time > 0 ? $meta_time : $date_time;

			if ( $target > 0 && $target <= time() ) {
				// Clear any pending cron event for this post.
				wp_clear_scheduled_hook( 'publish_future_post', array( $post->ID ) );

				// Use wp_publish_post which triggers correct transition hooks.
				wp_publish_post( $post->ID );

				// Verify it actually transitioned. If wp_publish_post failed
				// silently (filters, plugins, cache), force it with a direct
				// DB update as a last resort.
				clean_post_cache( $post->ID );
				$refreshed = get_post( $post->ID );
				if ( $refreshed && 'publish' !== $refreshed->post_status ) {
     // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
					$wpdb->update(
      // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
						$wpdb->posts,
						array( 'post_status' => 'publish' ),
						array( 'ID' => $post->ID )
					);
					clean_post_cache( $post->ID );
					$refreshed = get_post( $post->ID );
				}

				if ( $refreshed && 'publish' === $refreshed->post_status ) {
					$published++;
					$details[] = sprintf( '#%d (%s) → published', $post->ID, $post->post_title );
				} else {
					$details[] = sprintf( '#%d (%s) → FAILED (status: %s)', $post->ID, $post->post_title, $refreshed ? $refreshed->post_status : 'null' );
				}
			} else {
				$details[] = sprintf( '#%d (%s) → skipped (not due yet, target=%s)', $post->ID, $post->post_title, $target > 0 ? wp_date( 'Y-m-d H:i:s', $target ) : 'unknown' );
			}
		}

		// Store a debug log of the last run for troubleshooting.
		update_option( 'rbco_last_catchup_log', array(
			'time'      => time(),
			'found'     => count( $query->posts ),
			'published' => $published,
			'details'   => $details,
		), false );

		return $published;
	}

	/**
	 * Manually publish a scheduled or draft post immediately.
	 *
	 * @param int $post_id Post ID.
	 * @return array|WP_Error
	 */
	public static function publish_now( $post_id ) {
		$post_id = absint( $post_id );
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return new WP_Error( 'not_found', __( 'Post not found.', 'raybogman-ai-content-orchestrator' ) );
		}

		if ( ! in_array( $post->post_status, array( 'draft', 'future' ), true ) ) {
			return new WP_Error( 'invalid_status', __( 'Only drafts and scheduled posts can be published.', 'raybogman-ai-content-orchestrator' ) );
		}

		// Clear any pending cron event.
		wp_clear_scheduled_hook( 'publish_future_post', array( $post_id ) );

		// Update the post date to now and publish.
		$now     = current_time( 'mysql' );
		$now_gmt = current_time( 'mysql', 1 );
		$result  = wp_update_post( array(
			'ID'            => $post_id,
			'post_status'   => 'publish',
			'post_date'     => $now,
			'post_date_gmt' => $now_gmt,
			'edit_date'     => true,
		), true );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array(
			'success' => true,
			'id'      => $post_id,
			'url'     => get_permalink( $post_id ),
		);
	}

	/**
	 * Get all scheduled items (drafts awaiting review + future posts).
	 *
	 * @return array List of scheduled items with metadata.
	 */
	public static function get_scheduled_items() {
		// Auto-publish any overdue items whose cron didn't fire.
		self::catch_up_overdue();

		// Get ALL AICC-generated drafts AND any future posts.
		// Drafts (with or without a schedule) appear in the review queue.
		$query = new WP_Query( array(
			'post_type'      => array( 'post', 'page' ),
			'post_status'    => array( 'draft', 'future' ),
			'posts_per_page' => -1,
   // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Required for querying AI-generated posts.
			'meta_query'     => array(
				array(
					'key'     => '_rbco_generated',
					'compare' => 'EXISTS',
				),
			),
			'orderby'        => 'date',
			'order'          => 'DESC',
		) );

		$items = array();
		foreach ( $query->posts as $post ) {
			$schedule_at = (int) get_post_meta( $post->ID, '_rbco_scheduled_publish_at', true );
			// Any draft is awaiting human review (with or without a schedule).
			// Once approved, the post transitions to 'future' (if scheduled) or 'publish' (immediate).
			$needs_review = 'draft' === $post->post_status;

			$categories = array();
			if ( 'post' === $post->post_type ) {
				$terms = wp_get_post_categories( $post->ID, array( 'fields' => 'names' ) );
				if ( ! is_wp_error( $terms ) ) {
					$categories = $terms;
				}
			}

			$items[] = array(
				'id'           => $post->ID,
				'title'        => $post->post_title,
				'type'         => $post->post_type,
				'status'       => $post->post_status,
				'scheduled_at' => $schedule_at,
				'scheduled_at_formatted' => $schedule_at > 0 ? wp_date( 'Y-m-d H:i', $schedule_at ) : __( 'Publish on approval', 'raybogman-ai-content-orchestrator' ),
				'needs_review' => $needs_review,
				'categories'   => $categories,
				'edit_url'     => get_edit_post_link( $post->ID, 'raw' ),
				'preview_url'  => get_preview_post_link( $post ),
				'focus_keyphrase' => get_post_meta( $post->ID, '_yoast_wpseo_focuskw', true ),
				'linkedin'     => (bool) get_post_meta( $post->ID, '_rbco_post_to_linkedin', true ),
			);
		}

		return $items;
	}

	/**
	 * Download an image from a URL and attach it to a post as the featured image.
	 *
	 * @param int    $post_id   The post to attach the image to.
	 * @param string $image_url Remote image URL (e.g., from DALL-E).
	 * @param string $alt_text  Alt text for the image (typically the post title).
	 * @return int|WP_Error Attachment ID on success, WP_Error on failure.
	 */
	public static function attach_image_from_url( $post_id, $image_url, $alt_text = '' ) {
		// Required for media_handle_sideload().
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		// Download to a temp file.
		$tmp = download_url( $image_url, 60 );
		if ( is_wp_error( $tmp ) ) {
			return $tmp;
		}

		// Build the file array for sideload.
		$file_array = array(
			'name'     => 'rbco-' . $post_id . '-' . wp_generate_password( 8, false, false ) . '.png',
			'tmp_name' => $tmp,
		);

		$attachment_id = media_handle_sideload( $file_array, $post_id, $alt_text );

		if ( is_wp_error( $attachment_id ) ) {
			@wp_delete_file( $tmp );
			return $attachment_id;
		}

		// Set as featured image.
		set_post_thumbnail( $post_id, $attachment_id );

		// Set alt text on the attachment.
		if ( ! empty( $alt_text ) ) {
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt_text );
		}

		// Mark as AICC-generated for tracking.
		update_post_meta( $attachment_id, '_rbco_generated_image', '1' );

		return $attachment_id;
	}

	/**
	 * Get recently published AICC posts with LinkedIn sharing status.
	 *
	 * @param int $limit Max number of posts to return.
	 * @return array
	 */
	public static function get_published_with_linkedin_status( $limit = 20 ) {
		$query = new WP_Query( array(
			'post_type'      => array( 'post', 'page' ),
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'orderby'        => 'date',
			'order'          => 'DESC',
   // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Required for querying AI-generated posts.
			'meta_query'     => array(
				'relation' => 'AND',
				array(
					'key'     => '_rbco_generated',
					'compare' => 'EXISTS',
				),
				array(
					'key'     => '_rbco_post_to_linkedin',
					'value'   => '1',
					'compare' => '=',
				),
			),
		) );

		$items = array();
		foreach ( $query->posts as $post ) {
			$shared_at = (int) get_post_meta( $post->ID, '_rbco_linkedin_shared', true );
			$error     = get_post_meta( $post->ID, '_rbco_linkedin_error', true );

			if ( $shared_at > 0 ) {
				$status = 'shared';
			} elseif ( ! empty( $error ) ) {
				$status = 'error';
			} else {
				$status = 'pending';
			}

			$items[] = array(
				'id'                  => $post->ID,
				'title'               => $post->post_title,
				'type'                => $post->post_type,
				'url'                 => get_permalink( $post->ID ),
				'edit_url'            => get_edit_post_link( $post->ID, 'raw' ),
				'published_at'        => strtotime( $post->post_date_gmt . ' GMT' ),
				'shared_at'           => $shared_at,
				'linkedin_status'     => $status,
				'linkedin_error'      => $error,
				'linkedin_commentary' => get_post_meta( $post->ID, '_rbco_linkedin_commentary', true ),
			);
		}

		return $items;
	}

	/**
	 * Resolve tag names to tag IDs, creating them if needed.
	 *
	 * @param array $tag_names Array of tag name strings.
	 * @return array Array of tag term IDs.
	 */
	private static function resolve_tags( $tag_names ) {
		$ids = array();

		foreach ( $tag_names as $name ) {
			$name = sanitize_text_field( $name );
			if ( empty( $name ) ) {
				continue;
			}

			// Search existing (case-insensitive).
			$term = get_term_by( 'name', $name, 'post_tag' );
			if ( $term ) {
				$ids[] = $term->term_id;
				continue;
			}

			// Create new tag.
			$result = wp_insert_term( $name, 'post_tag' );
			if ( ! is_wp_error( $result ) ) {
				$ids[] = $result['term_id'];
			}
		}

		return $ids;
	}

	/**
	 * Resolve category names to category IDs, creating them if needed.
	 *
	 * @param array $cat_names Array of category name strings.
	 * @return array Array of category term IDs.
	 */
	private static function resolve_categories( $cat_names ) {
		$ids = array();

		foreach ( $cat_names as $name ) {
			$name = sanitize_text_field( $name );
			if ( empty( $name ) ) {
				continue;
			}

			// Search existing (case-insensitive).
			$term = get_term_by( 'name', $name, 'category' );
			if ( $term ) {
				$ids[] = $term->term_id;
				continue;
			}

			// Create new category.
			$result = wp_insert_term( $name, 'category' );
			if ( ! is_wp_error( $result ) ) {
				$ids[] = $result['term_id'];
			}
		}

		return $ids;
	}

	/**
	 * Update Yoast SEO meta fields for a post.
	 *
	 * @param int   $post_id   The post ID.
	 * @param array $ai_result AI-generated content with metadata.
	 * @return bool True if Yoast fields were updated successfully.
	 */
	private static function update_yoast_meta( $post_id, $ai_result ) {
		// Check if Yoast SEO is active.
		if ( ! defined( 'WPSEO_VERSION' ) ) {
			return false;
		}

		$fields = array(
			'_yoast_wpseo_title'    => $ai_result['seo_title'] ?? '',
			'_yoast_wpseo_metadesc' => $ai_result['meta_description'] ?? '',
			'_yoast_wpseo_focuskw'  => $ai_result['focus_keyphrase'] ?? '',
		);

		foreach ( $fields as $key => $value ) {
			if ( ! empty( $value ) ) {
				update_post_meta( $post_id, $key, sanitize_text_field( $value ) );
			}
		}

		return true;
	}

	/**
	 * Get all existing WordPress categories.
	 *
	 * @return array Array of [ 'id' => int, 'name' => string, 'slug' => string ].
	 */
	public static function get_categories() {
		$terms = get_terms( array(
			'taxonomy'   => 'category',
			'hide_empty' => false,
			'orderby'    => 'name',
			'order'      => 'ASC',
		) );

		if ( is_wp_error( $terms ) ) {
			return array();
		}

		$categories = array();
		foreach ( $terms as $term ) {
			$categories[] = array(
				'id'   => $term->term_id,
				'name' => $term->name,
				'slug' => $term->slug,
			);
		}

		return $categories;
	}
}
