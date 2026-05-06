<?php
/**
 * PDF library management — upload, store, retrieve, and delete PDFs.
 *
 * PDFs are uploaded to wp-content/uploads/rbco-pdfs/. Metadata and extracted
 * text are stored in the rbco_pdf_library WordPress option.
 *
 * @package Raybogman_Content_Orchestrator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RBCO_PDF_Library {

	/**
	 * Option key for the PDF library index.
	 */
	const OPTION_KEY = 'rbco_pdf_library';

	/**
	 * Get the upload directory for PDFs.
	 *
	 * @return string Absolute path to the PDF storage directory.
	 */
	public static function get_upload_dir() {
		$upload = wp_upload_dir();
		$dir    = trailingslashit( $upload['basedir'] ) . 'rbco-pdfs';
		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
			// Prevent directory listing.
			$index = $dir . '/index.php';
			if ( ! file_exists( $index ) ) {
				file_put_contents( $index, '<?php // Silence is golden.' );
			}
		}
		return $dir;
	}

	/**
	 * Get the URL base for the PDF directory.
	 *
	 * @return string
	 */
	public static function get_upload_url() {
		$upload = wp_upload_dir();
		return trailingslashit( $upload['baseurl'] ) . 'rbco-pdfs';
	}

	/**
	 * Get all PDFs in the library.
	 *
	 * @return array Associative array of id => PDF metadata.
	 */
	public static function get_all() {
		$library = get_option( self::OPTION_KEY, array() );
		return is_array( $library ) ? $library : array();
	}

	/**
	 * Get a simplified list for the admin JS.
	 *
	 * @return array Array of PDFs with id, name, upload_date, text_length.
	 */
	public static function get_for_js() {
		$library = self::get_all();
		$output  = array();
		foreach ( $library as $id => $pdf ) {
			$output[] = array(
				'id'            => $id,
				'name'          => $pdf['original_name'],
				'upload_date'   => wp_date( 'Y-m-d H:i', $pdf['upload_date'] ),
				'text_length'   => $pdf['text_length'],
				'text_preview'  => mb_substr( $pdf['text'], 0, 150 ) . ( mb_strlen( $pdf['text'] ) > 150 ? '...' : '' ),
			);
		}
		return $output;
	}

	/**
	 * Upload a PDF, extract its text, and add it to the library.
	 *
	 * @param array $file The $_FILES entry for the uploaded file.
	 * @return array|WP_Error PDF metadata on success, WP_Error on failure.
	 */
	/**
	 * Get the server's maximum upload size in bytes.
	 *
	 * @return int Maximum upload size in bytes.
	 */
	public static function get_max_upload_size() {
		// Chunked upload bypasses PHP upload_max_filesize, so our plugin limit is the ceiling.
		return 100 * 1024 * 1024; // 100MB.
	}

	/**
	 * Get a human-readable max upload size string.
	 *
	 * @return string E.g. "8 MB".
	 */
	public static function get_max_upload_size_formatted() {
		return size_format( self::get_max_upload_size() );
	}

	public static function upload( $file ) {
		// Check for PHP upload errors first — these happen when the file
		// exceeds upload_max_filesize or post_max_size BEFORE our code runs.
		if ( isset( $file['error'] ) && UPLOAD_ERR_OK !== $file['error'] ) {
			$messages = array(
				UPLOAD_ERR_INI_SIZE   => sprintf(
     /* translators: %s: dynamic value */
					__( 'File exceeds the server upload limit (%s). Ask your hosting provider to increase upload_max_filesize and post_max_size in php.ini.', 'raybogman-content-orchestrator' ),
					self::get_max_upload_size_formatted()
				),
				UPLOAD_ERR_FORM_SIZE  => __( 'File exceeds the form upload limit.', 'raybogman-content-orchestrator' ),
				UPLOAD_ERR_PARTIAL    => __( 'File was only partially uploaded. Please try again.', 'raybogman-content-orchestrator' ),
				UPLOAD_ERR_NO_FILE    => __( 'No file was uploaded.', 'raybogman-content-orchestrator' ),
				UPLOAD_ERR_NO_TMP_DIR => __( 'Server missing temporary folder. Contact your hosting provider.', 'raybogman-content-orchestrator' ),
				UPLOAD_ERR_CANT_WRITE => __( 'Failed to write file to disk. Contact your hosting provider.', 'raybogman-content-orchestrator' ),
				UPLOAD_ERR_EXTENSION  => __( 'Upload blocked by a server extension.', 'raybogman-content-orchestrator' ),
			);
			$msg = isset( $messages[ $file['error'] ] )
				? $messages[ $file['error'] ]
    /* translators: %s: dynamic value */
				: sprintf( __( 'Upload error code: %d', 'raybogman-content-orchestrator' ), $file['error'] );
			return new WP_Error( 'upload_error', $msg );
		}

		// Validate file type.
		$file_type = wp_check_filetype( $file['name'], array( 'pdf' => 'application/pdf' ) );
		if ( empty( $file_type['ext'] ) ) {
			return new WP_Error( 'invalid_type', __( 'Only PDF files are allowed.', 'raybogman-content-orchestrator' ) );
		}

		// Validate file size against our limit.
		$max_size = self::get_max_upload_size();
		if ( $file['size'] > $max_size ) {
			return new WP_Error( 'too_large', sprintf(
				__( /* translators: 1: file size, 2: max size */
			'PDF is too large (%1\$s). Maximum allowed: %2\$s.', 'raybogman-content-orchestrator' ),
				size_format( $file['size'] ),
				size_format( $max_size )
			) );
		}

		// Generate unique ID and filename.
		$id       = wp_generate_uuid4();
		$dir      = self::get_upload_dir();
		$filename = $id . '.pdf';
		$filepath = trailingslashit( $dir ) . $filename;

		$upload_overrides = array(
			'test_form' => false,
			'test_type' => false,
			'unique_filename_callback' => function() use ( $filename ) { return $filename; },
		);
		$uploaded = wp_handle_upload( $file, $upload_overrides );
		if ( isset( $uploaded['error'] ) ) {
			return new WP_Error( 'upload_failed', $uploaded['error'] );
		}
		// Move to our custom directory if wp_handle_upload placed it elsewhere.
		if ( $uploaded['file'] !== $filepath && file_exists( $uploaded['file'] ) ) {
			rename( $uploaded['file'], $filepath );
		}

		// Extract text from PDF.
		$text = RBCO_PDF_Extractor::extract( $filepath );
		if ( empty( $text ) ) {
			$text = '(Could not extract text from this PDF. It may be image-based or use complex encoding.)';
		}

		// Store in library.
		$pdf_data = array(
			'id'            => $id,
			'filename'      => $filename,
			'original_name' => sanitize_file_name( $file['name'] ),
			'upload_date'   => time(),
			'file_size'     => $file['size'],
			'text'          => $text,
			'text_length'   => mb_strlen( $text ),
		);

		$library        = self::get_all();
		$library[ $id ] = $pdf_data;
		update_option( self::OPTION_KEY, $library );

		return $pdf_data;
	}

	/**
	 * Delete a PDF from the library and disk.
	 *
	 * @param string $id PDF ID.
	 * @return bool True if deleted, false if not found.
	 */
	public static function delete( $id ) {
		$library = self::get_all();
		if ( ! isset( $library[ $id ] ) ) {
			return false;
		}

		// Delete file from disk.
		$filepath = trailingslashit( self::get_upload_dir() ) . $library[ $id ]['filename'];
		if ( file_exists( $filepath ) ) {
			wp_delete_file( $filepath );
		}

		// Remove from library.
		unset( $library[ $id ] );
		update_option( self::OPTION_KEY, $library );

		return true;
	}

	/**
	 * Get the extracted text for one or more PDFs.
	 *
	 * @param array $ids Array of PDF IDs to include.
	 * @return string Combined text from all selected PDFs.
	 */
	public static function get_combined_text( $ids ) {
		$library = self::get_all();
		$parts   = array();

		foreach ( $ids as $id ) {
			if ( isset( $library[ $id ] ) && ! empty( $library[ $id ]['text'] ) ) {
				$parts[] = sprintf(
					"--- PDF: %s ---\n%s",
					$library[ $id ]['original_name'],
					$library[ $id ]['text']
				);
			}
		}

		return implode( "\n\n", $parts );
	}

	/**
	 * Build site-context-style data from PDF text for the AI.
	 *
	 * @param array $ids Array of PDF IDs.
	 * @return array Array of "page data" arrays compatible with RBCO_Scanner format.
	 */
	public static function get_as_site_data( $ids ) {
		$library   = self::get_all();
		$site_data = array();
		$max_chars = RBCO_Settings::get_max_context_chars();

		foreach ( $ids as $id ) {
			if ( ! isset( $library[ $id ] ) || empty( $library[ $id ]['text'] ) ) {
				continue;
			}

			$pdf  = $library[ $id ];
			$text = $pdf['text'];

			// Limit text per PDF to fit within context budget.
			if ( mb_strlen( $text ) > $max_chars / max( count( $ids ), 1 ) ) {
				$text = mb_substr( $text, 0, (int) ( $max_chars / max( count( $ids ), 1 ) ) );
			}

			// Split text into paragraph-sized chunks.
			$paragraphs = array_filter(
				preg_split( '/\n{2,}/', $text ),
				function ( $p ) {
					return mb_strlen( trim( $p ) ) > 30;
				}
			);

			$site_data[] = array(
				'url'              => 'pdf://' . $pdf['original_name'],
				'title'            => pathinfo( $pdf['original_name'], PATHINFO_FILENAME ),
				'meta_description' => '',
				'headings'         => array(),
				'paragraphs'       => array_values( array_slice( $paragraphs, 0, 50 ) ),
			);
		}

		return $site_data;
	}
}
