<?php
/**
 * Automatic internal linking for AI-generated content.
 *
 * @package Raybogman_Content_Orchestrator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RBCO_Internal_Linker {

	/**
	 * Find relevant published posts and insert internal links into the content.
	 *
	 * Links are spread evenly across paragraphs (max one link per paragraph).
	 * The first paragraph (intro) is skipped to keep the opening clean.
	 *
	 * @param string $html          AI-generated HTML content.
	 * @param array  $meta          SEO metadata (seo_title, focus_keyphrase, meta_description).
	 * @param int    $max_links     Maximum number of internal links to insert (default: 5).
	 * @param int    $exclude_id    Post ID to exclude (the post being created).
	 * @return array { 'html' => string, 'links_added' => int, 'linked_posts' => array }
	 */
	public static function add_links( $html, $meta, $max_links = 5, $exclude_id = 0, $log_callback = null ) {
		$log = function( $msg ) use ( $log_callback ) {
			if ( is_callable( $log_callback ) ) {
				call_user_func( $log_callback, '  [linker] ' . $msg );
			}
		};

		if ( empty( $html ) || $max_links <= 0 ) {
			$log( 'Skipped: empty content or max_links=0.' );
			return array(
				'html'         => $html,
				'links_added'  => 0,
				'linked_posts' => array(),
			);
		}

		$keywords = self::extract_keywords( $html, $meta );
		$log( sprintf( 'Extracted %d keywords: %s', count( $keywords ), implode( ', ', array_slice( $keywords, 0, 10 ) ) ) );
		if ( empty( $keywords ) ) {
			return array(
				'html'         => $html,
				'links_added'  => 0,
				'linked_posts' => array(),
			);
		}

		$candidates = self::find_relevant_posts( $keywords, $exclude_id, $max_links * 3 );
		$log( sprintf( 'Found %d candidate posts to link to.', count( $candidates ) ) );
		foreach ( array_slice( $candidates, 0, 5 ) as $c ) {
			$log( sprintf( '  → score %d: %s', $c['score'], $c['title'] ) );
		}
		if ( empty( $candidates ) ) {
			$log( 'No candidates found.' );
			return array(
				'html'         => $html,
				'links_added'  => 0,
				'linked_posts' => array(),
			);
		}

		$placement = RBCO_Settings::get_link_placement();
		$log( sprintf( 'Link placement mode: %s', $placement ) );

		$paragraphs = self::index_paragraphs( $html );
		$total_p    = count( $paragraphs );
		$log( sprintf( 'Found %d paragraphs in content.', $total_p ) );

		// Calculate smart inline/footer split based on content length.
		$max_inline = $max_links;
		$min_footer = 0;

		if ( 'both' === $placement ) {
			// Adaptive: content length determines the split.
			if ( $total_p < 10 ) {
				// Short content: fewer inline, more footer.
				$max_inline = max( 2, (int) floor( $max_links * 0.4 ) );
			} elseif ( $total_p < 20 ) {
				// Medium content.
				$max_inline = max( 2, (int) floor( $max_links * 0.6 ) );
			} else {
				// Long content: more inline capacity.
				$max_inline = max( 3, (int) floor( $max_links * 0.65 ) );
			}
			$min_footer = max( 2, $max_links - $max_inline );

			// Never place inline links more often than 1 per 4 paragraphs.
			$density_cap = max( 1, (int) floor( ( $total_p - 1 ) / 4 ) );
			$max_inline  = min( $max_inline, $density_cap );

			$log( sprintf( 'Smart split: %d inline (max) + %d footer (min) for %d paragraphs.', $max_inline, $min_footer, $total_p ) );
		} elseif ( 'footer' === $placement ) {
			$max_inline = 0;
		}

		$linked_p_indices = array();
		$inline_added     = 0;
		$links_added      = 0;
		$linked_posts     = array();
		$footer_candidates = array();

		// Phase 1: Inline links.
		if ( $max_inline > 0 ) {
			foreach ( $candidates as $idx => $candidate ) {
				if ( $inline_added >= $max_inline ) {
					break;
				}

				$result = self::insert_link_distributed( $html, $candidate, $paragraphs, $linked_p_indices );
				if ( null !== $result ) {
					$html                                = $result['html'];
					$linked_p_indices[ $result['p_idx'] ] = true;
					$inline_added++;
					$links_added++;
					$linked_posts[] = array(
						'id'    => $candidate['id'],
						'title' => $candidate['title'],
						'url'   => $candidate['url'],
					);
					$log( sprintf( 'Inline link added in paragraph %d/%d: %s', $result['p_idx'] + 1, $total_p, $candidate['title'] ) );
					$paragraphs = self::index_paragraphs( $html );
					unset( $candidates[ $idx ] );
				}
			}
		}

		// Remaining candidates are available for the footer.
		$footer_candidates = array_values( $candidates );

		// Phase 2: Related Articles section.
		if ( 'inline' !== $placement && ! empty( $footer_candidates ) ) {
			$footer_slots = $max_links - $links_added;
			if ( 'both' === $placement ) {
				$footer_slots = max( $min_footer, $footer_slots );
				$footer_slots = min( $footer_slots, count( $footer_candidates ) );
			}

			$related_links = array();
			$footer_added  = 0;
			foreach ( $footer_candidates as $r ) {
				if ( $footer_added >= $footer_slots ) {
					break;
				}
				// Skip posts already linked inline.
				$already_linked = false;
				foreach ( $linked_posts as $lp ) {
					if ( $lp['id'] === $r['id'] ) {
						$already_linked = true;
						break;
					}
				}
				if ( $already_linked ) {
					continue;
				}

				$related_links[] = sprintf(
					'<li style="padding:6px 0; border-bottom:1px solid #eee;"><a href="%s" style="text-decoration:none; font-weight:500;">%s</a></li>',
					esc_url( $r['url'] ),
					esc_html( $r['title'] )
				);
				$footer_added++;
				$links_added++;
				$linked_posts[] = array(
					'id'    => $r['id'],
					'title' => $r['title'],
					'url'   => $r['url'],
				);
			}

			if ( ! empty( $related_links ) ) {
				$log( sprintf( 'Adding %d links via "Related Articles" section.', count( $related_links ) ) );
				$html .= "\n" . '<div class="rbco-related-box" style="margin-top:2em; padding:20px 24px; background:#f8f9fa; border-left:4px solid #2271b1; border-radius:4px;">'
					. "\n" . '<h3 style="margin:0 0 12px 0; font-size:1.1em;">Related Articles</h3>'
					. "\n" . '<ul style="list-style:none; margin:0; padding:0;">' . "\n" . implode( "\n", $related_links ) . "\n</ul>\n</div>";
			}
		}

		return array(
			'html'         => $html,
			'links_added'  => $links_added,
			'linked_posts' => $linked_posts,
		);
	}

	/**
	 * Index all paragraphs in the HTML with their offsets and text content.
	 *
	 * @param string $html HTML content.
	 * @return array Array of { 'html' => string, 'offset' => int, 'text' => string, 'has_link' => bool }.
	 */
	private static function index_paragraphs( $html ) {
		$paragraphs = array();
		if ( preg_match_all( '/<p\b[^>]*>.*?<\/p>/is', $html, $matches, PREG_OFFSET_CAPTURE ) ) {
			foreach ( $matches[0] as $m ) {
				$p_html = $m[0];
				$paragraphs[] = array(
					'html'     => $p_html,
					'offset'   => $m[1],
					'text'     => mb_strtolower( wp_strip_all_tags( $p_html ), 'UTF-8' ),
					'has_link' => false !== strpos( $p_html, '</a>' ),
				);
			}
		}
		return $paragraphs;
	}

	/**
	 * Insert a link into the best available paragraph (distributed).
	 *
	 * Rules:
	 *   - Skip the first paragraph (intro should stay clean)
	 *   - Skip paragraphs that already contain a link
	 *   - Skip paragraphs already used by a previous candidate
	 *   - Try anchor text match first, then fall back to appending
	 *
	 * @param string $html              HTML content.
	 * @param array  $candidate         Candidate post data.
	 * @param array  $paragraphs        Indexed paragraphs.
	 * @param array  $linked_p_indices  Paragraph indices already used.
	 * @return array|null { 'html' => string, 'p_idx' => int } or null if no match.
	 */
	private static function insert_link_distributed( $html, $candidate, $paragraphs, $linked_p_indices ) {
		$total_p = count( $paragraphs );

		// Strategy 1: Find anchor text literally in an available paragraph.
		foreach ( $candidate['anchor_words'] as $anchor ) {
			$escaped_anchor = preg_quote( $anchor, '/' );
			$pattern = '/(<p[^>]*>(?:(?!<\/p>).)*?)(' . $escaped_anchor . ')((?:(?!<\/p>).)*?<\/p>)/ius';

			if ( preg_match_all( $pattern, $html, $all_matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE ) ) {
				foreach ( $all_matches as $m ) {
					$match_offset = $m[0][1];
					$p_idx = self::find_paragraph_index( $paragraphs, $match_offset );

					// Skip first paragraph (intro).
					if ( $p_idx <= 0 ) {
						continue;
					}

					// Skip already-linked paragraphs.
					if ( isset( $linked_p_indices[ $p_idx ] ) || $paragraphs[ $p_idx ]['has_link'] ) {
						continue;
					}

					$before  = $m[1][0];
					$matched = $m[2][0];
					$after   = $m[3][0];

					// Skip if inside an HTML tag attribute.
					if ( preg_match( '/<[^>]*$/s', $before ) ) {
						continue;
					}

					// Skip if inside an <a> tag.
					if ( preg_match( '/<a\b[^>]*>[^<]*$/i', $before ) ) {
						continue;
					}

					$link = sprintf(
						'<a href="%s">%s</a>',
						esc_url( $candidate['url'] ),
						$matched
					);

					$new_html = substr_replace( $html, $before . $link . $after, $m[0][1], strlen( $m[0][0] ) );
					return array( 'html' => $new_html, 'p_idx' => $p_idx );
				}
			}
		}

		// Strategy 2: Find a domain-specific keyword from the post title in a
		// paragraph and turn it into a natural inline link.
		$title_clean = mb_strtolower( wp_strip_all_tags( $candidate['title'] ), 'UTF-8' );
		$title_words = preg_split( '/\s+/', $title_clean );
		$stopwords   = self::get_stopwords();
		$weak_words  = self::get_weak_anchor_words();

		// Build multi-word phrases (2 consecutive meaningful words) first,
		// then single domain-specific words. Longer = better anchor text.
		$meaningful = array();
		foreach ( $title_words as $tw ) {
			$tw = trim( $tw, ',:;.!?()[]"\'' );
			if ( mb_strlen( $tw ) >= 3 && ! isset( $stopwords[ $tw ] ) && ! isset( $weak_words[ $tw ] ) ) {
				$meaningful[] = $tw;
			}
		}

		$match_phrases = array();
		// Try 2-word combinations from consecutive meaningful words.
		for ( $j = 0; $j < count( $meaningful ) - 1; $j++ ) {
			$match_phrases[] = $meaningful[ $j ] . ' ' . $meaningful[ $j + 1 ];
		}
		// Then individual words (5+ chars, sorted longest first for specificity).
		$single_words = array_filter( $meaningful, function( $w ) { return mb_strlen( $w ) >= 5; } );
		usort( $single_words, function( $a, $b ) { return mb_strlen( $b ) - mb_strlen( $a ); } );
		$match_phrases = array_merge( $match_phrases, $single_words );

		if ( empty( $match_phrases ) ) {
			return null;
		}

		for ( $i = 1; $i < $total_p; $i++ ) {
			if ( isset( $linked_p_indices[ $i ] ) || $paragraphs[ $i ]['has_link'] ) {
				continue;
			}

			foreach ( $match_phrases as $kw ) {
				if ( false === mb_strpos( $paragraphs[ $i ]['text'], $kw ) ) {
					continue;
				}

				$p_html     = $paragraphs[ $i ]['html'];
				$p_offset   = $paragraphs[ $i ]['offset'];
				$escaped_kw = preg_quote( $kw, '/' );
				$pattern    = '/(<p[^>]*>(?:(?!<\/p>).)*?)(' . $escaped_kw . ')((?:(?!<\/p>).)*?<\/p>)/ius';

				if ( ! preg_match( $pattern, $p_html, $m, PREG_OFFSET_CAPTURE ) ) {
					continue;
				}

				$before  = $m[1][0];
				$matched = $m[2][0];
				$after   = $m[3][0];

				if ( preg_match( '/<[^>]*$/s', $before ) ) {
					continue;
				}
				if ( preg_match( '/<a\b[^>]*>[^<]*$/i', $before ) ) {
					continue;
				}

				$link     = sprintf( '<a href="%s">%s</a>', esc_url( $candidate['url'] ), $matched );
				$new_p    = $before . $link . $after;
				$new_html = substr_replace( $html, $new_p, $p_offset, strlen( $p_html ) );
				return array( 'html' => $new_html, 'p_idx' => $i );
			}
		}

		return null;
	}

	/**
	 * Find which paragraph index contains a given byte offset.
	 *
	 * @param array $paragraphs Indexed paragraphs.
	 * @param int   $offset     Byte offset in the HTML.
	 * @return int Paragraph index, or -1 if not found.
	 */
	private static function find_paragraph_index( $paragraphs, $offset ) {
		foreach ( $paragraphs as $i => $p ) {
			if ( $offset >= $p['offset'] && $offset < $p['offset'] + strlen( $p['html'] ) ) {
				return $i;
			}
		}
		return -1;
	}

	/**
	 * Extract keywords from the article's metadata and H2/H3 headings.
	 *
	 * @param string $html HTML content.
	 * @param array  $meta SEO metadata.
	 * @return array Unique keywords (lowercased, 3+ characters).
	 */
	private static function extract_keywords( $html, $meta ) {
		$text_parts = array();

		if ( ! empty( $meta['focus_keyphrase'] ) ) {
			$text_parts[] = $meta['focus_keyphrase'];
			$text_parts[] = $meta['focus_keyphrase'];
		}

		if ( ! empty( $meta['seo_title'] ) ) {
			$text_parts[] = $meta['seo_title'];
		}

		if ( preg_match_all( '/<h[23][^>]*>(.*?)<\/h[23]>/is', $html, $matches ) ) {
			foreach ( $matches[1] as $heading ) {
				$text_parts[] = wp_strip_all_tags( $heading );
			}
		}

		if ( ! empty( $meta['tags'] ) && is_array( $meta['tags'] ) ) {
			$text_parts = array_merge( $text_parts, $meta['tags'] );
		}

		$all_text = implode( ' ', $text_parts );
		$all_text = mb_strtolower( $all_text, 'UTF-8' );
		$words    = preg_split( '/[\s,.\-:;!?()\[\]"\']+/u', $all_text );

		$stopwords = self::get_stopwords();
		$keywords  = array();
		foreach ( $words as $word ) {
			$word = trim( $word );
			if ( mb_strlen( $word ) >= 3 && ! isset( $stopwords[ $word ] ) ) {
				$keywords[ $word ] = true;
			}
		}

		return array_keys( $keywords );
	}

	/**
	 * Find published posts matching the given keywords.
	 *
	 * @param array $keywords  Keywords to search for.
	 * @param int   $exclude   Post ID to exclude.
	 * @param int   $limit     Max candidates to return.
	 * @return array Scored candidates sorted by relevance.
	 */
	private static function find_relevant_posts( $keywords, $exclude, $limit ) {
		$query = new \WP_Query( array(
			'post_type'        => array( 'post', 'page' ),
			'post_status'      => 'publish',
			'posts_per_page'   => 200,
			// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in
			'post__not_in'     => $exclude > 0 ? array( $exclude ) : array(),
			'orderby'          => 'date',
			'order'            => 'DESC',
			'no_found_rows'    => true,
			'suppress_filters' => false,
		) );

		if ( ! $query->have_posts() ) {
			return array();
		}

		$candidates = array();
		foreach ( $query->posts as $post ) {
			$title_lower   = mb_strtolower( $post->post_title, 'UTF-8' );
			$content_lower = mb_strtolower( wp_strip_all_tags( $post->post_content ), 'UTF-8' );
			$score         = 0;

			foreach ( $keywords as $kw ) {
				if ( false !== mb_strpos( $title_lower, $kw ) ) {
					$score += 3;
				}
			}

			foreach ( $keywords as $kw ) {
				if ( false !== mb_strpos( $content_lower, $kw ) ) {
					$score += 1;
				}
			}

			if ( $score >= 2 ) {
				$candidates[] = array(
					'id'           => $post->ID,
					'title'        => $post->post_title,
					'url'          => get_permalink( $post->ID ),
					'score'        => $score,
					'anchor_words' => self::extract_anchor_words( $post->post_title ),
				);
			}
		}

		usort( $candidates, function ( $a, $b ) {
			return $b['score'] - $a['score'];
		} );

		return array_slice( $candidates, 0, $limit );
	}

	/**
	 * Extract anchor text candidates from a post title.
	 *
	 * @param string $title Post title.
	 * @return array Array of candidate anchor text strings (longest first).
	 */
	private static function extract_anchor_words( $title ) {
		$title = wp_strip_all_tags( $title );
		$words = preg_split( '/\s+/', $title );

		$anchors   = array();
		$stopwords = self::get_stopwords();

		// Full title (for short titles, up to 6 words).
		if ( count( $words ) <= 6 ) {
			$anchors[] = $title;
		}

		// Multi-word combinations from meaningful words only.
		$meaningful = array();
		foreach ( $words as $w ) {
			if ( ! isset( $stopwords[ mb_strtolower( $w, 'UTF-8' ) ] ) && mb_strlen( $w ) >= 3 ) {
				$meaningful[] = $w;
			}
		}
		if ( count( $meaningful ) >= 2 ) {
			$anchors[] = implode( ' ', array_slice( $meaningful, 0, 4 ) );
			if ( count( $meaningful ) >= 3 ) {
				$anchors[] = implode( ' ', array_slice( $meaningful, 0, 3 ) );
			}
			$anchors[] = implode( ' ', array_slice( $meaningful, 0, 2 ) );
		}

		// No single-word anchors — they produce poor SEO anchor text.
		// If none of the multi-word phrases match in the content,
		// Strategy 2 will use the full post title as the link text.

		return array_values( array_unique( $anchors ) );
	}

	/**
	 * Common stopwords (EN + NL + DE + FR + ES).
	 *
	 * @return array Stopwords as keys (for fast isset lookups).
	 */
	private static function get_stopwords() {
		static $stopwords = null;
		if ( null !== $stopwords ) {
			return $stopwords;
		}

		$list = array(
			// English.
			'the', 'and', 'for', 'are', 'but', 'not', 'you', 'all', 'can', 'her', 'was', 'one',
			'our', 'out', 'has', 'have', 'had', 'this', 'that', 'with', 'from', 'they', 'been',
			'will', 'would', 'could', 'should', 'what', 'when', 'where', 'which', 'their', 'there',
			'about', 'into', 'more', 'your', 'also', 'how', 'its', 'than', 'them', 'each', 'does',
			// Dutch.
			'het', 'een', 'van', 'dat', 'die', 'niet', 'zijn', 'aan', 'met', 'als', 'ook', 'maar',
			'nog', 'wel', 'door', 'voor', 'dan', 'naar', 'bij', 'uit', 'dit', 'deze', 'wat', 'hoe',
			'haar', 'hun', 'werd', 'zou', 'worden', 'heeft', 'over', 'veel', 'jouw', 'jij', 'wij',
			// German.
			'der', 'die', 'das', 'und', 'ist', 'ein', 'eine', 'nicht', 'mit', 'auf', 'den', 'von',
			'sich', 'des', 'dem', 'dass', 'auch', 'als', 'wird', 'bei', 'nach', 'wie', 'oder',
			// French.
			'les', 'des', 'une', 'est', 'pas', 'que', 'dans', 'qui', 'par', 'sur', 'pour', 'avec',
			'son', 'sont', 'mais', 'ont', 'aux', 'ses', 'ces', 'tout', 'elle', 'nous', 'vous',
			// Spanish.
			'los', 'las', 'del', 'por', 'con', 'una', 'como', 'sus', 'que', 'pero', 'son', 'todo',
		);

		$stopwords = array();
		foreach ( $list as $w ) {
			$stopwords[ $w ] = true;
		}
		return $stopwords;
	}

	/**
	 * Generic words that are technically not stopwords but make terrible
	 * anchor text because they carry no topical SEO value.
	 *
	 * @return array Words as keys (for fast isset lookups).
	 */
	private static function get_weak_anchor_words() {
		static $weak = null;
		if ( null !== $weak ) {
			return $weak;
		}

		$list = array(
			// English generic.
			'best', 'complete', 'guide', 'simple', 'easy', 'good', 'great', 'right',
			'find', 'make', 'take', 'need', 'help', 'know', 'just', 'look', 'come',
			'work', 'want', 'give', 'tell', 'call', 'keep', 'let', 'begin', 'show',
			'start', 'turn', 'move', 'play', 'real', 'full', 'free', 'open', 'must',
			'part', 'long', 'very', 'much', 'next', 'last', 'back', 'only', 'most',
			'new', 'first', 'every', 'modern', 'business', 'company', 'companies',
			'today', 'world', 'things', 'years', 'people', 'time', 'ways', 'many',
			'most', 'important', 'different', 'better', 'biggest', 'small', 'large',
			'top', 'key', 'main', 'common', 'future', 'ultimate', 'essential',
			'comprehensive', 'definitive', 'effective', 'powerful', 'step', 'steps',
			'tips', 'tricks', 'signs', 'reasons', 'benefits', 'challenges',
			// Dutch generic.
			'beste', 'gids', 'compleet', 'complete', 'goed', 'goede', 'groot', 'grote',
			'nieuw', 'nieuwe', 'belangrijk', 'belangrijke', 'eerste', 'laatste',
			'verschillende', 'andere', 'mogelijk', 'mogelijk', 'bedrijf', 'bedrijven',
			'stappen', 'redenen', 'voordelen', 'tips',
			// German generic.
			'beste', 'wichtig', 'wichtige', 'groß', 'große', 'klein', 'kleine',
			'unternehmen', 'schritte', 'tipps',
		);

		$weak = array();
		foreach ( $list as $w ) {
			$weak[ $w ] = true;
		}
		return $weak;
	}
}
