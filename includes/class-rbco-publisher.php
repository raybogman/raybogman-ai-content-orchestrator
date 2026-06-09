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
	 * @param string $content_type Either 'blog' or 'page'.
	 * @param array  $ai_result    AI-generated content array.
	 * @param string $status       Post status ('draft' or 'publish').
	 * @param array  $category_ids Pre-selected WordPress category IDs.
	 * @return array Result array with success status and post details.
	 */
	public static function create( $content_type, $ai_result, $status = 'draft', $category_ids = array() ) {
		$post_type = ( 'blog' === $content_type ) ? 'post' : 'page';
		$status    = in_array( $status, array( 'draft', 'publish' ), true ) ? $status : 'draft';

		$post_data = array(
			'post_title'   => sanitize_text_field( $ai_result['seo_title'] ),
			'post_content' => wp_kses_post( $ai_result['content'] ),
			'post_name'    => sanitize_title( $ai_result['slug'] ),
			'post_excerpt' => sanitize_text_field( $ai_result['meta_description'] ),
			'post_type'    => $post_type,
			'post_author'  => get_current_user_id(),
			'post_status'  => $status,
		);

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

		update_post_meta( $post_id, '_rbco_generated', '1' );

		// Update Yoast SEO fields.
		$yoast_ok = self::update_yoast_meta( $post_id, $ai_result );

		$post = get_post( $post_id );

		return array(
			'success'  => true,
			'id'       => $post_id,
			'title'    => $post->post_title,
			'slug'     => $post->post_name,
			'status'   => $post->post_status,
			'url'      => get_permalink( $post_id ),
			'edit_url' => get_edit_post_link( $post_id, 'raw' ),
			'yoast'    => $yoast_ok,
		);
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
