<?php
/**
 * Website scanner.
 *
 * @package Raybogman_Content_Orchestrator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RBCO_Scanner
 *
 * Fetches and extracts content from the single URL the administrator enters,
 * for use as background context for the AI. It does not crawl, follow links,
 * or read sitemaps — only the exact page entered is requested.
 */
class RBCO_Scanner {

	/**
	 * User agent string for HTTP requests.
	 *
	 * @var string
	 */
	const USER_AGENT = 'Mozilla/5.0 (compatible; WPCreatorBot/1.0)';

	/**
	 * Callback for progress logging.
	 *
	 * @var callable|null
	 */
	private $log_callback;

	/**
	 * Constructor.
	 *
	 * @param callable|null $log_callback Function to call with progress messages.
	 */
	public function __construct( $log_callback = null ) {
		$this->log_callback = $log_callback;
	}

	/**
	 * Log a message.
	 *
	 * @param string $message Message to log.
	 */
	private function log( $message ) {
		if ( is_callable( $this->log_callback ) ) {
			call_user_func( $this->log_callback, $message );
		}
	}

	/**
	 * Fetch and extract content from the single URL the administrator entered.
	 *
	 * Only the exact URL provided is requested. The plugin does not crawl,
	 * follow links, or read sitemaps — no automated discovery of any kind.
	 *
	 * @param string $url The exact page URL to read.
	 * @return array Array containing the single page's data (or empty on failure).
	 */
	public function scan( $url ) {
		$url = untrailingslashit( $url );
		$this->log( sprintf( 'Reading the page you entered: %s', $url ) );

		$html = $this->fetch_page( $url );
		if ( ! $html ) {
			$this->log( 'Could not retrieve the page (it may be unreachable or blocking requests).' );
			return array();
		}

		$page_data = $this->extract_page_data( $html, $url );

		$site_data = array();
		if ( ! empty( $page_data['paragraphs'] ) || ! empty( $page_data['headings'] ) ) {
			$site_data[] = $page_data;
		}

		$this->log(
			sprintf(
				'Read 1 page (%d paragraphs, %d headings).',
				count( $page_data['paragraphs'] ),
				count( $page_data['headings'] )
			)
		);
		return $site_data;
	}

	/**
	 * Fetch a page's HTML content.
	 *
	 * @param string $url URL to fetch.
	 * @return string|false HTML content or false on failure.
	 */
	private function fetch_page( $url ) {
		$response = wp_remote_get(
			$url,
			array(
				'timeout'    => RBCO_Settings::get_request_timeout(),
				'user-agent' => self::USER_AGENT,
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code < 200 || $code >= 300 ) {
			return false;
		}

		return wp_remote_retrieve_body( $response );
	}

	/**
	 * Extract structured data from a page's HTML.
	 *
	 * @param string $html Raw HTML content.
	 * @param string $url  The page URL.
	 * @return array Extracted page data.
	 */
	private function extract_page_data( $html, $url ) {
		$data = array(
			'url'              => $url,
			'title'            => '',
			'meta_description' => '',
			'headings'         => array(),
			'paragraphs'       => array(),
		);

		// Extract title.
		if ( preg_match( '/<title[^>]*>(.*?)<\/title>/si', $html, $matches ) ) {
			$data['title'] = trim( wp_strip_all_tags( $matches[1] ) );
		}

		// Extract meta description.
		if ( preg_match( '/<meta\s+name=["\']description["\']\s+content=["\'](.*?)["\']/si', $html, $matches ) ) {
			$data['meta_description'] = trim( $matches[1] );
		}

		// Extract headings (h1-h3).
		for ( $level = 1; $level <= 3; $level++ ) {
			if ( preg_match_all( "/<h{$level}[^>]*>(.*?)<\/h{$level}>/si", $html, $matches ) ) {
				foreach ( $matches[1] as $heading_html ) {
					$text = trim( wp_strip_all_tags( $heading_html ) );
					if ( $text ) {
						$data['headings'][] = array(
							'level' => $level,
							'text'  => $text,
						);
					}
				}
			}
		}
		$data['headings'] = array_slice( $data['headings'], 0, 30 );

		// Strip non-content elements for paragraph extraction.
		$clean_html = preg_replace( '/<(script|style|nav|footer|header|aside|form|iframe)[^>]*>.*?<\/\1>/si', '', $html );

		// Extract paragraphs.
		if ( preg_match_all( '/<p[^>]*>(.*?)<\/p>/si', $clean_html, $matches ) ) {
			foreach ( $matches[1] as $p_html ) {
				$text = trim( wp_strip_all_tags( $p_html ) );
				if ( strlen( $text ) > 30 ) {
					$data['paragraphs'][] = $text;
				}
			}
		}
		$data['paragraphs'] = array_slice( $data['paragraphs'], 0, 30 );

		return $data;
	}

	/**
	 * Build a text context string from scanned page data.
	 *
	 * @param array $site_data Array of page data arrays.
	 * @return string Formatted context string.
	 */
	public static function build_site_context( $site_data ) {
		$parts      = array();
		$char_count = 0;
		$max_chars  = RBCO_Settings::get_max_context_chars();
		$total      = count( $site_data );

		foreach ( $site_data as $index => $page ) {
			$section   = array();
			$section[] = sprintf( '--- Page %d: %s ---', $index + 1, $page['url'] );
			$section[] = sprintf( 'Title: %s', $page['title'] );

			if ( ! empty( $page['meta_description'] ) ) {
				$section[] = sprintf( 'Meta: %s', $page['meta_description'] );
			}

			if ( ! empty( $page['headings'] ) ) {
				$headings_parts = array();
				foreach ( array_slice( $page['headings'], 0, 8 ) as $h ) {
					$headings_parts[] = sprintf( 'H%d: %s', $h['level'], $h['text'] );
				}
				$section[] = 'Headings: ' . implode( ' | ', $headings_parts );
			}

			if ( ! empty( $page['paragraphs'] ) ) {
				$trimmed = array();
				foreach ( array_slice( $page['paragraphs'], 0, 5 ) as $p ) {
					$trimmed[] = mb_substr( $p, 0, 200 );
				}
				$section[] = "Content:\n" . implode( "\n", $trimmed );
			}

			$block = implode( "\n", $section );
			if ( $char_count + strlen( $block ) > $max_chars ) {
				$remaining = $total - $index;
				$parts[]   = sprintf( '--- (skipped remaining %d pages) ---', $remaining );
				break;
			}

			$parts[]     = $block;
			$char_count += strlen( $block );
		}

		return implode( "\n\n", $parts );
	}
}
