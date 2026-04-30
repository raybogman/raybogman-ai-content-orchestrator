<?php
/**
 * Lightweight PDF text extractor.
 *
 * Extracts text content from PDF files using pure PHP — no external libraries
 * or system commands required. Handles standard text PDFs with optional
 * FlateDecode compression.
 *
 * Limitations: image-only PDFs (scans) cannot be extracted. Complex font
 * encodings may produce garbled output. For these cases, the user should
 * paste the text manually.
 *
 * @package RayAI_Content_Orchestrator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RAYAI_PDF_Extractor {

	/**
	 * Extract text from a PDF file.
	 *
	 * @param string $file_path Absolute path to the PDF file.
	 * @return string Extracted text, or empty string on failure.
	 */
	public static function extract( $file_path ) {
		if ( ! file_exists( $file_path ) || ! is_readable( $file_path ) ) {
			return '';
		}

		$content = file_get_contents( $file_path );
		if ( empty( $content ) ) {
			return '';
		}

		// Verify it's a PDF.
		if ( 0 !== strpos( $content, '%PDF' ) ) {
			return '';
		}

		$text = '';

		// Extract from all stream objects.
		$streams = self::extract_streams( $content );
		foreach ( $streams as $stream ) {
			$extracted = self::extract_text_from_stream( $stream );
			if ( ! empty( $extracted ) ) {
				$text .= $extracted . "\n";
			}
		}

		return self::clean_text( $text );
	}

	/**
	 * Extract all stream content blocks from the PDF.
	 *
	 * @param string $pdf_content Raw PDF content.
	 * @return array Array of decompressed stream strings.
	 */
	private static function extract_streams( $pdf_content ) {
		$streams = array();

		// Find stream...endstream blocks.
		$offset = 0;
		while ( false !== ( $start = strpos( $pdf_content, 'stream', $offset ) ) ) {
			// Skip past "stream\r\n" or "stream\n".
			$stream_start = $start + 6;
			if ( isset( $pdf_content[ $stream_start ] ) && "\r" === $pdf_content[ $stream_start ] ) {
				$stream_start++;
			}
			if ( isset( $pdf_content[ $stream_start ] ) && "\n" === $pdf_content[ $stream_start ] ) {
				$stream_start++;
			}

			$end = strpos( $pdf_content, 'endstream', $stream_start );
			if ( false === $end ) {
				break;
			}

			$raw = substr( $pdf_content, $stream_start, $end - $stream_start );

			// Check if this stream is FlateDecode compressed.
			$header_region = substr( $pdf_content, max( 0, $start - 200 ), 200 );
			if ( false !== strpos( $header_region, '/FlateDecode' ) ) {
				$decompressed = @gzuncompress( $raw );
				if ( false === $decompressed ) {
					// Try with raw deflate (no zlib header).
					$decompressed = @gzinflate( $raw );
				}
				if ( false !== $decompressed ) {
					$streams[] = $decompressed;
				}
			} else {
				// Uncompressed stream.
				$streams[] = $raw;
			}

			$offset = $end + 9;
		}

		return $streams;
	}

	/**
	 * Extract readable text from a single PDF stream using text operators.
	 *
	 * @param string $stream Decompressed stream content.
	 * @return string Extracted text.
	 */
	private static function extract_text_from_stream( $stream ) {
		$text  = '';
		$parts = array();

		// Find text blocks (BT...ET).
		if ( preg_match_all( '/BT(.*?)ET/s', $stream, $blocks ) ) {
			foreach ( $blocks[1] as $block ) {
				$block_text = self::parse_text_block( $block );
				if ( ! empty( $block_text ) ) {
					$parts[] = $block_text;
				}
			}
		}

		return implode( ' ', $parts );
	}

	/**
	 * Parse text operators within a BT...ET block.
	 *
	 * @param string $block Content between BT and ET.
	 * @return string Extracted text.
	 */
	private static function parse_text_block( $block ) {
		$text = '';

		// Handle Tj operator: (text) Tj
		if ( preg_match_all( '/\(([^)]*)\)\s*Tj/s', $block, $matches ) ) {
			foreach ( $matches[1] as $t ) {
				$text .= self::decode_pdf_string( $t ) . ' ';
			}
		}

		// Handle TJ operator: [(text) num (text) num ...] TJ
		if ( preg_match_all( '/\[(.*?)\]\s*TJ/s', $block, $matches ) ) {
			foreach ( $matches[1] as $array_str ) {
				if ( preg_match_all( '/\(([^)]*)\)/', $array_str, $items ) ) {
					foreach ( $items[1] as $t ) {
						$text .= self::decode_pdf_string( $t );
					}
					// Check for large negative kerning values (word space indicators).
					if ( preg_match_all( '/\)\s*(-?\d+)\s*\(/', $array_str, $kern ) ) {
						// We already concatenated — just add context.
					}
				}
				$text .= ' ';
			}
		}

		// Handle ' operator (move to next line and show text): (text) '
		if ( preg_match_all( "/\(([^)]*)\)\s*'/s", $block, $matches ) ) {
			foreach ( $matches[1] as $t ) {
				$text .= self::decode_pdf_string( $t ) . "\n";
			}
		}

		// Handle " operator (set spacing, move, show text).
		if ( preg_match_all( '/\(([^)]*)\)\s*"/s', $block, $matches ) ) {
			foreach ( $matches[1] as $t ) {
				$text .= self::decode_pdf_string( $t ) . "\n";
			}
		}

		return trim( $text );
	}

	/**
	 * Decode a PDF string, handling escape sequences.
	 *
	 * @param string $str PDF-encoded string.
	 * @return string Decoded string.
	 */
	private static function decode_pdf_string( $str ) {
		$replacements = array(
			'\\n'  => "\n",
			'\\r'  => "\r",
			'\\t'  => "\t",
			'\\b'  => "\x08",
			'\\f'  => "\x0C",
			'\\('  => '(',
			'\\)'  => ')',
			'\\\\' => '\\',
		);
		$str = str_replace( array_keys( $replacements ), array_values( $replacements ), $str );

		// Handle octal escape sequences \nnn.
		$str = preg_replace_callback( '/\\\\([0-7]{1,3})/', function ( $m ) {
			return chr( octdec( $m[1] ) );
		}, $str );

		return $str;
	}

	/**
	 * Clean up extracted text.
	 *
	 * @param string $text Raw extracted text.
	 * @return string Cleaned text.
	 */
	private static function clean_text( $text ) {
		// Remove non-printable characters except newlines and tabs.
		$text = preg_replace( '/[^\x20-\x7E\xA0-\xFF\n\t]/', ' ', $text );

		// Collapse multiple spaces.
		$text = preg_replace( '/[ \t]+/', ' ', $text );

		// Collapse multiple newlines.
		$text = preg_replace( '/\n{3,}/', "\n\n", $text );

		// Trim lines.
		$lines = explode( "\n", $text );
		$lines = array_map( 'trim', $lines );
		$text  = implode( "\n", $lines );

		// Remove empty lines at start/end.
		$text = trim( $text );

		// Filter out very short "noise" lines (< 3 chars).
		$lines   = explode( "\n", $text );
		$cleaned = array();
		foreach ( $lines as $line ) {
			if ( strlen( trim( $line ) ) >= 3 || empty( trim( $line ) ) ) {
				$cleaned[] = $line;
			}
		}

		return trim( implode( "\n", $cleaned ) );
	}
}
