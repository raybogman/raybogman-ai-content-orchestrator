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
 * Crawls and extracts content from websites for use as AI context.
 */
class RBCO_Scanner {

	/**
	 * User agent string for HTTP requests.
	 *
	 * @var string
	 */
	const USER_AGENT = 'Mozilla/5.0 (compatible; WPCreatorBot/1.0)';

	/**
	 * Paths to skip during scanning.
	 *
	 * @var array
	 */
	const SKIP_PATHS = array(
		'/wp-admin',
		'/wp-login',
		'/wp-content',
		'/feed',
		'/xmlrpc',
		'/wp-json',
		'/cart',
		'/checkout',
		'/my-account',
		'/mijn-account',
		'/winkelmand',
		'/afrekenen',
	);

	/**
	 * Exact paths to skip.
	 *
	 * @var array
	 */
	const SKIP_EXACT = array(
		'/cart',
		'/winkelmand',
		'/checkout',
		'/afrekenen',
		'/my-account',
		'/mijn-account',
		'/wp-admin',
		'/wp-login',
		'/feed',
		'/xmlrpc',
		'/wp-json',
	);

	/**
	 * Patterns to skip.
	 *
	 * @var array
	 */
	const SKIP_PATTERNS = array(
		'/leden/',
		'/wp-admin',
		'/wp-login',
		'/wp-content',
		'/feed',
		'/xmlrpc',
		'/wp-json',
		'/cart',
		'/checkout',
		'/my-account',
		'/mijn-account',
		'/winkelmand',
		'/afrekenen',
		'/author/',
		'/tag/',
		'/category/',
	);

	/**
	 * High-value path keywords for URL prioritization.
	 *
	 * @var array
	 */
	const HIGH_VALUE_KEYWORDS = array(
		'diensten',
		'services',
		'about',
		'over-ons',
		'over-mij',
		'contact',
		'blog',
		'training',
		'coaching',
		'cursus',
		'course',
		'aanbod',
		'programma',
		'workshop',
		'masterclass',
		'therapie',
		'begeleiding',
		'kennismaken',
		'wie-ben-ik',
		'missie',
		'visie',
		'werkwijze',
		'aanpak',
		'tarieven',
		'prices',
		'team',
		'reviews',
		'testimonials',
		'ervaringen',
	);

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
	 * Scan a website and return extracted page data.
	 *
	 * @param string $url The website URL to scan.
	 * @return array Array of page data.
	 */
	public function scan( $url ) {
		$this->log( sprintf( 'Scanning website: %s', $url ) );
		$url = untrailingslashit( $url );

		// Fetch sitemap URLs.
		$this->log( 'Checking sitemap...' );
		$sitemap_urls = $this->fetch_sitemap_urls( $url );

		// Get homepage links.
		$homepage_links = array();
		$homepage_html  = $this->fetch_page( $url );
		if ( $homepage_html ) {
			$homepage_links = $this->discover_internal_links( $homepage_html, $url );
		}

		// Merge all discovered URLs.
		$all_urls = array_unique( array_merge( $sitemap_urls, $homepage_links, array( $url ) ) );
		$this->log( sprintf(
			'Discovered %d URLs (%d from sitemap, %d from homepage)',
			count( $all_urls ),
			count( $sitemap_urls ),
			count( $homepage_links )
		) );

		// Prioritize and limit.
		$prioritized = $this->prioritize_urls( $all_urls );
		$max_pages   = RBCO_Settings::get_max_pages();
		$to_scan     = array_slice( $prioritized, 0, $max_pages );
		$this->log( sprintf( 'Selected top %d most relevant pages', count( $to_scan ) ) );

		// Scan each page.
		$site_data = array();
		$total     = count( $to_scan );
		foreach ( $to_scan as $index => $page_url ) {
			$this->log( sprintf( 'Scanning page [%d/%d] %s', $index + 1, $total, $page_url ) );
			$html = $this->fetch_page( $page_url );
			if ( ! $html ) {
				continue;
			}
			$page_data = $this->extract_page_data( $html, $page_url );
			if ( ! empty( $page_data['paragraphs'] ) || ! empty( $page_data['headings'] ) ) {
				$site_data[] = $page_data;
			}
		}

		$this->log( sprintf( 'Scanned %d pages with content', count( $site_data ) ) );
		return $site_data;
	}

	/**
	 * Fetch a page's HTML content.
	 *
	 * @param string $url URL to fetch.
	 * @return string|false HTML content or false on failure.
	 */
	private function fetch_page( $url ) {
		$response = wp_remote_get( $url, array(
			'timeout'    => RBCO_Settings::get_request_timeout(),
			'user-agent' => self::USER_AGENT,
			'sslverify'  => false,
		) );

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
	 * Discover internal links from HTML content.
	 *
	 * @param string $html     HTML content.
	 * @param string $base_url Base URL for resolving relative links.
	 * @return array Array of absolute internal URLs.
	 */
	private function discover_internal_links( $html, $base_url ) {
		$parsed_base = wp_parse_url( $base_url );
		$base_domain = $parsed_base['host'] ?? '';
		$base_scheme = $parsed_base['scheme'] ?? 'https';
		$links       = array();

		if ( ! preg_match_all( '/<a\s[^>]*href=["\']([^"\'#?]+)/i', $html, $matches ) ) {
			return $links;
		}

		foreach ( $matches[1] as $href ) {
			$href = rtrim( $href, '/' );
			if ( empty( $href ) ) {
				continue;
			}

			// Resolve relative URLs.
			if ( 0 === strpos( $href, '/' ) ) {
				$full_url = $base_scheme . '://' . $base_domain . $href;
			} elseif ( 0 === strpos( $href, 'http' ) ) {
				$full_url = $href;
			} else {
				continue;
			}

			$parsed = wp_parse_url( $full_url );
			if ( empty( $parsed['host'] ) || $parsed['host'] !== $base_domain ) {
				continue;
			}

			$path = $parsed['path'] ?? '/';
			$skip = false;
			foreach ( self::SKIP_PATHS as $skip_path ) {
				if ( 0 === strpos( $path, $skip_path ) ) {
					$skip = true;
					break;
				}
			}

			if ( ! $skip ) {
				$links[] = rtrim( $full_url, '/' );
			}
		}

		return array_unique( $links );
	}

	/**
	 * Fetch URLs from XML sitemaps.
	 *
	 * @param string $base_url Website base URL.
	 * @return array Array of URLs found in sitemaps.
	 */
	private function fetch_sitemap_urls( $base_url ) {
		$urls     = array();
		$to_check = array(
			$base_url . '/sitemap.xml',
			$base_url . '/sitemap_index.xml',
			$base_url . '/wp-sitemap.xml',
		);
		$checked  = array();

		while ( ! empty( $to_check ) ) {
			$sitemap_url = array_shift( $to_check );
			if ( in_array( $sitemap_url, $checked, true ) ) {
				continue;
			}
			$checked[] = $sitemap_url;

			$response = wp_remote_get( $sitemap_url, array(
				'timeout'    => RBCO_Settings::get_request_timeout(),
				'user-agent' => self::USER_AGENT,
				'sslverify'  => false,
			) );

			if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
				continue;
			}

			$body = wp_remote_retrieve_body( $response );

			// Check for sub-sitemaps.
			if ( preg_match_all( '/<sitemap>\s*<loc>\s*(.*?)\s*<\/loc>/si', $body, $matches ) ) {
				foreach ( $matches[1] as $loc ) {
					$loc = trim( $loc );
					if ( $loc ) {
						$to_check[] = $loc;
					}
				}
			}

			// Extract URLs.
			if ( preg_match_all( '/<url>\s*<loc>\s*(.*?)\s*<\/loc>/si', $body, $matches ) ) {
				foreach ( $matches[1] as $loc ) {
					$loc = trim( $loc );
					if ( $loc ) {
						$urls[] = $loc;
					}
				}
			}
		}

		return $urls;
	}

	/**
	 * Prioritize and filter URLs for scanning.
	 *
	 * @param array $urls Array of URLs to prioritize.
	 * @return array Sorted array of URLs (highest priority first).
	 */
	private function prioritize_urls( $urls ) {
		$scored     = array();
		$seen_paths = array();

		foreach ( $urls as $url ) {
			$parsed = wp_parse_url( rtrim( $url, '/' ) );
			$path   = strtolower( rtrim( $parsed['path'] ?? '', '/' ) );

			if ( isset( $seen_paths[ $path ] ) ) {
				continue;
			}
			$seen_paths[ $path ] = true;

			// Skip exact matches.
			if ( in_array( $path, self::SKIP_EXACT, true ) ) {
				continue;
			}

			// Skip pattern matches.
			$skip = false;
			foreach ( self::SKIP_PATTERNS as $pattern ) {
				if ( 0 === strpos( $path, $pattern ) ) {
					$skip = true;
					break;
				}
			}
			if ( $skip ) {
				continue;
			}

			// Calculate score.
			$score = 0;
			if ( '' === $path || '/' === $path ) {
				$score = 1000;
			} else {
				$depth = substr_count( $path, '/' );
				$score = max( 0, 50 - $depth * 10 );

				foreach ( self::HIGH_VALUE_KEYWORDS as $keyword ) {
					if ( false !== strpos( $path, $keyword ) ) {
						$score += 100;
						break;
					}
				}

				if ( false !== strpos( $path, '/blog/' ) ) {
					$score += 40;
				}
			}

			$scored[] = array(
				'score' => $score,
				'url'   => $url,
			);
		}

		// Sort by score descending.
		usort( $scored, function ( $a, $b ) {
			return $b['score'] - $a['score'];
		} );

		return array_column( $scored, 'url' );
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
			$section = array();
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
