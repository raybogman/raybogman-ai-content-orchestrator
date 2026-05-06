<?php
/**
 * Creates featured images by overlaying the blog title on a base image.
 *
 * Uses PHP GD to composite uppercase text (split across 2 lines) onto a
 * user-selected base image from the media library. Each generated post
 * gets its own unique image with the post's SEO title burned in.
 *
 * Design reference: branded featured image with:
 *   - Line 1: bold, uppercase, large font
 *   - Line 2: italic, uppercase, slightly smaller
 *   - Position: center-center
 *   - Color: configurable dark teal hex
 *
 * @package Raybogman_Content_Orchestrator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RBCO_Image_Overlay {

	/**
	 * Create a featured image with title overlay.
	 *
	 * @param int    $base_attachment_id WordPress attachment ID of the base image.
	 * @param string $title             The text to overlay (typically the SEO title).
	 * @param array  $options {
	 *     @type string $color       Hex color for line 1 (default: #0d5e50).
	 *     @type string $color_line2 Hex color for line 2 (default: same as $color).
	 *     @type string $font_bold   Absolute path to bold TTF font file.
	 *     @type string $font_italic Absolute path to italic TTF font file.
	 *     @type int    $font_size   Font size in points (0 = auto-calculate from image width).
	 *     @type array  $lines       Optional custom 2-line split: [ 'LINE 1', 'LINE 2' ].
	 *                               If set, skips the auto-split of $title.
	 * }
	 * @return string|WP_Error Path to the generated JPEG on success, WP_Error on failure.
	 */
	public static function create( $base_attachment_id, $title, $options = array() ) {
		if ( ! function_exists( 'imagecreatefromjpeg' ) ) {
			return new \WP_Error( 'no_gd', __( 'PHP GD extension is required for image overlay. Contact your hosting provider.', 'raybogman-content-orchestrator' ) );
		}

		$defaults = array(
			'color'       => '#0d5e50',
			'color_line2' => '',
			'font_bold'   => '',
			'font_italic' => '',
			'font_size'   => 0,
			'lines'       => array(),
		);
		$opts = wp_parse_args( $options, $defaults );

		if ( empty( $opts['color_line2'] ) ) {
			$opts['color_line2'] = $opts['color'];
		}

		// Load the base image.
		$base_path = get_attached_file( $base_attachment_id );
		if ( ! $base_path || ! file_exists( $base_path ) ) {
			return new \WP_Error( 'no_base', __( 'Default featured image not found in media library.', 'raybogman-content-orchestrator' ) );
		}

		$info = getimagesize( $base_path );
		if ( ! $info ) {
			return new \WP_Error( 'invalid_image', __( 'Could not read the base image dimensions.', 'raybogman-content-orchestrator' ) );
		}

		$mime = $info['mime'];
		switch ( $mime ) {
			case 'image/jpeg':
				$img = imagecreatefromjpeg( $base_path );
				break;
			case 'image/png':
				$img = imagecreatefrompng( $base_path );
				break;
			case 'image/webp':
				if ( function_exists( 'imagecreatefromwebp' ) ) {
					$img = imagecreatefromwebp( $base_path );
				} else {
					return new \WP_Error( 'unsupported', __( 'WebP support requires PHP 7.1+ with GD compiled with WebP.', 'raybogman-content-orchestrator' ) );
				}
				break;
			case 'image/avif':
				if ( function_exists( 'imagecreatefromavif' ) ) {
					$img = imagecreatefromavif( $base_path );
				} else {
					return new \WP_Error( 'unsupported', __( 'AVIF support requires PHP 8.1+ with GD compiled with AVIF. Please use a JPEG or PNG base image instead.', 'raybogman-content-orchestrator' ) );
				}
				break;
			default:
				return new \WP_Error( 'unsupported', __( 'Only JPEG, PNG, WebP, and AVIF base images are supported.', 'raybogman-content-orchestrator' ) );
		}

		if ( ! $img ) {
			return new \WP_Error( 'load_failed', __( 'Failed to load the base image with GD.', 'raybogman-content-orchestrator' ) );
		}

		$width  = imagesx( $img );
		$height = imagesy( $img );

		// Font files.
		$font_bold = $opts['font_bold'];
		if ( empty( $font_bold ) || ! file_exists( $font_bold ) ) {
			imagedestroy( $img );
			return new \WP_Error( 'no_font', __( 'Bold font file (.ttf) not found. Upload a TTF font and configure it in Settings → Featured Image Provider.', 'raybogman-content-orchestrator' ) );
		}
		$font_italic = ( ! empty( $opts['font_italic'] ) && file_exists( $opts['font_italic'] ) )
			? $opts['font_italic']
			: $font_bold;

		// Auto font size: roughly 1/18 of the image width (visually balanced).
		$font_size       = $opts['font_size'] > 0 ? $opts['font_size'] : max( 16, (int) ( $width / 18 ) );
		$font_size_line2 = (int) ( $font_size * 0.75 );

		// Parse colors.
		$rgb1        = self::hex_to_rgb( $opts['color'] );
		$rgb2        = self::hex_to_rgb( $opts['color_line2'] );
		$text_color1 = imagecolorallocate( $img, $rgb1[0], $rgb1[1], $rgb1[2] );
		$text_color2 = imagecolorallocate( $img, $rgb2[0], $rgb2[1], $rgb2[2] );

		// Use custom lines if provided, otherwise auto-split the title.
		if ( ! empty( $opts['lines'] ) && is_array( $opts['lines'] ) && count( $opts['lines'] ) >= 2 ) {
			$line1 = mb_strtoupper( trim( $opts['lines'][0] ), 'UTF-8' );
			$line2 = mb_strtoupper( trim( $opts['lines'][1] ), 'UTF-8' );
		} else {
			$lines = self::split_title( $title );
			$line1 = mb_strtoupper( $lines[0], 'UTF-8' );
			$line2 = mb_strtoupper( $lines[1], 'UTF-8' );
		}

		// Measure text bounding boxes.
		$bbox1        = imagettfbbox( $font_size, 0, $font_bold, $line1 );
		$text_width1  = abs( $bbox1[4] - $bbox1[0] );
		$text_height1 = abs( $bbox1[5] - $bbox1[1] );

		$bbox2        = imagettfbbox( $font_size_line2, 0, $font_italic, $line2 );
		$text_width2  = abs( $bbox2[4] - $bbox2[0] );
		$text_height2 = abs( $bbox2[5] - $bbox2[1] );

		// If text is too wide, reduce font size until it fits (max 5 reductions).
		$max_text_width = (int) ( $width * 0.85 );
		$shrink_count   = 0;
		while ( ( $text_width1 > $max_text_width || $text_width2 > $max_text_width ) && $shrink_count < 5 ) {
			$font_size       = (int) ( $font_size * 0.85 );
			$font_size_line2 = (int) ( $font_size * 0.75 );
			$bbox1           = imagettfbbox( $font_size, 0, $font_bold, $line1 );
			$text_width1     = abs( $bbox1[4] - $bbox1[0] );
			$text_height1    = abs( $bbox1[5] - $bbox1[1] );
			$bbox2           = imagettfbbox( $font_size_line2, 0, $font_italic, $line2 );
			$text_width2     = abs( $bbox2[4] - $bbox2[0] );
			$text_height2    = abs( $bbox2[5] - $bbox2[1] );
			$shrink_count++;
		}

		// Calculate center-center positioning.
		$line_spacing = (int) ( $font_size * 0.6 );
		$total_height = $text_height1 + $line_spacing + $text_height2;

		$y1 = (int) ( ( $height - $total_height ) / 2 ) + $text_height1;
		$y2 = $y1 + $line_spacing + $text_height2;

		$x1 = (int) ( ( $width - $text_width1 ) / 2 );
		$x2 = (int) ( ( $width - $text_width2 ) / 2 );

		// Render the text.
		imagettftext( $img, $font_size, 0, $x1, $y1, $text_color1, $font_bold, $line1 );
		imagettftext( $img, $font_size_line2, 0, $x2, $y2, $text_color2, $font_italic, $line2 );

		// Save to the WordPress uploads directory.
		$upload_dir = wp_upload_dir();
		$slug       = sanitize_file_name( sanitize_title( $title ) );
		if ( mb_strlen( $slug ) > 60 ) {
			$slug = mb_substr( $slug, 0, 60 );
		}
		$filename = 'rbco-featured-' . $slug . '-' . time() . '.jpg';
		$filepath = trailingslashit( $upload_dir['path'] ) . $filename;

		imagejpeg( $img, $filepath, 92 );
		imagedestroy( $img );

		return $filepath;
	}

	/**
	 * Create the overlay image and attach it to a WordPress post as the
	 * featured image.
	 *
	 * @param int    $post_id             The post to attach to.
	 * @param int    $base_attachment_id  Attachment ID of the base image.
	 * @param string $title              Text to overlay.
	 * @param array  $options            Overlay options (see create()).
	 * @return int|WP_Error Attachment ID on success, WP_Error on failure.
	 */
	public static function create_and_attach( $post_id, $base_attachment_id, $title, $options = array() ) {
		$filepath = self::create( $base_attachment_id, $title, $options );
		if ( is_wp_error( $filepath ) ) {
			return $filepath;
		}

		// Prepare the file for WordPress media handling.
		$upload_dir = wp_upload_dir();
		$filename   = basename( $filepath );
		$filetype   = wp_check_filetype( $filename, null );

		$attachment_data = array(
			'guid'           => trailingslashit( $upload_dir['url'] ) . $filename,
			'post_mime_type' => $filetype['type'],
			'post_title'     => $title,
			'post_content'   => '',
			'post_status'    => 'inherit',
		);

		$attach_id = wp_insert_attachment( $attachment_data, $filepath, $post_id );
		if ( is_wp_error( $attach_id ) ) {
			return $attach_id;
		}

		// Generate metadata (thumbnails, dimensions, etc.).
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$metadata = wp_generate_attachment_metadata( $attach_id, $filepath );
		wp_update_attachment_metadata( $attach_id, $metadata );

		// Set as featured image and mark as AICC-generated.
		set_post_thumbnail( $post_id, $attach_id );
		update_post_meta( $attach_id, '_rbco_generated_image', '1' );
		update_post_meta( $attach_id, '_rbco_overlay_image', '1' );

		// Set alt text.
		update_post_meta( $attach_id, '_wp_attachment_image_alt', $title );

		return $attach_id;
	}

	/**
	 * Public accessor for split_title — used by admin to store auto-split
	 * lines in post meta for the result-view editor.
	 *
	 * @param string $title Full title text.
	 * @return array [ string $line1, string $line2 ]
	 */
	public static function get_split_title( $title ) {
		return self::split_title( $title );
	}

	/**
	 * Split a title into two roughly equal lines by word count.
	 *
	 * @param string $title Full title text.
	 * @return array [ string $line1, string $line2 ]
	 */
	private static function split_title( $title ) {
		$title = trim( $title );
		$words = preg_split( '/\s+/', $title );
		$total = count( $words );

		if ( $total <= 2 ) {
			return array( $title, '' );
		}

		$mid   = (int) ceil( $total / 2 );
		$line1 = implode( ' ', array_slice( $words, 0, $mid ) );
		$line2 = implode( ' ', array_slice( $words, $mid ) );

		return array( $line1, $line2 );
	}

	/**
	 * Convert hex color to RGB array.
	 *
	 * @param string $hex Hex color (with or without #).
	 * @return array [ int $r, int $g, int $b ]
	 */
	private static function hex_to_rgb( $hex ) {
		$hex = ltrim( $hex, '#' );
		if ( strlen( $hex ) === 3 ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		return array(
			hexdec( substr( $hex, 0, 2 ) ),
			hexdec( substr( $hex, 2, 2 ) ),
			hexdec( substr( $hex, 4, 2 ) ),
		);
	}
}
