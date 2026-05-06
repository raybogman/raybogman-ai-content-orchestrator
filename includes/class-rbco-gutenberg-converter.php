<?php
/**
 * Converts AI-generated HTML into WordPress Gutenberg block markup.
 *
 * Each supported HTML element is wrapped in <!-- wp:* --> comments so the
 * post opens in the block editor with individually editable blocks instead
 * of one "Classic" block containing the entire article.
 *
 * @package Raybogman_Content_Orchestrator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RBCO_Gutenberg_Converter {

	/**
	 * Convert HTML to Gutenberg block markup.
	 *
	 * @param string $html AI-generated HTML content.
	 * @return string Gutenberg-formatted content.
	 */
	public static function convert( $html ) {
		$html = trim( $html );
		if ( empty( $html ) ) {
			return '';
		}

		// Load HTML into DOM for reliable parsing.
		$dom = new DOMDocument( '1.0', 'UTF-8' );
		libxml_use_internal_errors( true );
		// Wrap in a root element and declare UTF-8 so DOMDocument doesn't mangle non-ASCII.
		$wrapped = '<?xml encoding="UTF-8"><div id="rbco-gb-root">' . $html . '</div>';
		$dom->loadHTML( $wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		libxml_clear_errors();

		$root = $dom->getElementById( 'rbco-gb-root' );
		if ( ! $root ) {
			return $html; // Fallback: return original if parsing failed.
		}

		$blocks = array();
		foreach ( $root->childNodes as $node ) {
			$block = self::convert_node( $node, $dom );
			if ( ! empty( $block ) ) {
				$blocks[] = $block;
			}
		}

		return implode( "\n\n", $blocks );
	}

	/**
	 * Convert a single DOM node into a Gutenberg block.
	 *
	 * @param DOMNode     $node The node to convert.
	 * @param DOMDocument $dom  Parent document.
	 * @return string Gutenberg block markup or empty string.
	 */
	private static function convert_node( $node, $dom ) {
		if ( XML_TEXT_NODE === $node->nodeType ) {
			$text = trim( $node->nodeValue );
			if ( '' === $text ) {
				return '';
			}
			return sprintf( "<!-- wp:paragraph -->\n<p>%s</p>\n<!-- /wp:paragraph -->", esc_html( $text ) );
		}

		if ( XML_ELEMENT_NODE !== $node->nodeType ) {
			return '';
		}

		$tag  = strtolower( $node->nodeName );
		$html = self::node_inner_html( $node, $dom );

		switch ( $tag ) {
			case 'h1':
			case 'h2':
			case 'h3':
			case 'h4':
			case 'h5':
			case 'h6':
				$level = (int) substr( $tag, 1 );
				return sprintf(
					"<!-- wp:heading {\"level\":%d} -->\n<%s>%s</%s>\n<!-- /wp:heading -->",
					$level,
					$tag,
					$html,
					$tag
				);

			case 'p':
				return sprintf( "<!-- wp:paragraph -->\n<p>%s</p>\n<!-- /wp:paragraph -->", $html );

			case 'ul':
				$inner = self::outer_html( $node, $dom );
				return sprintf( "<!-- wp:list -->\n%s\n<!-- /wp:list -->", $inner );

			case 'ol':
				$inner = self::outer_html( $node, $dom );
				return sprintf( "<!-- wp:list {\"ordered\":true} -->\n%s\n<!-- /wp:list -->", $inner );

			case 'blockquote':
				return sprintf(
					"<!-- wp:quote -->\n<blockquote class=\"wp-block-quote\">%s</blockquote>\n<!-- /wp:quote -->",
					$html
				);

			case 'img':
				$src = $node->getAttribute( 'src' );
				$alt = $node->getAttribute( 'alt' );
				return sprintf(
					"<!-- wp:image -->\n<figure class=\"wp-block-image\"><img src=\"%s\" alt=\"%s\"/></figure>\n<!-- /wp:image -->",
					esc_url( $src ),
					esc_attr( $alt )
				);

			case 'figure':
				$inner = self::outer_html( $node, $dom );
				return sprintf( "<!-- wp:image -->\n%s\n<!-- /wp:image -->", $inner );

			case 'table':
				$inner = self::outer_html( $node, $dom );
				return sprintf(
					"<!-- wp:table -->\n<figure class=\"wp-block-table\">%s</figure>\n<!-- /wp:table -->",
					$inner
				);

			case 'pre':
				return sprintf( "<!-- wp:code -->\n<pre class=\"wp-block-code\">%s</pre>\n<!-- /wp:code -->", $html );

			case 'hr':
				return "<!-- wp:separator -->\n<hr class=\"wp-block-separator\"/>\n<!-- /wp:separator -->";

			case 'div':
			case 'section':
			case 'article':
				// Recurse into container elements — they aren't blocks themselves.
				$nested = array();
				foreach ( $node->childNodes as $child ) {
					$nested_block = self::convert_node( $child, $dom );
					if ( ! empty( $nested_block ) ) {
						$nested[] = $nested_block;
					}
				}
				return implode( "\n\n", $nested );

			default:
				// Fallback: wrap unknown elements in an HTML block.
				$outer = self::outer_html( $node, $dom );
				if ( empty( trim( $outer ) ) ) {
					return '';
				}
				return sprintf( "<!-- wp:html -->\n%s\n<!-- /wp:html -->", $outer );
		}
	}

	/**
	 * Get inner HTML of a DOM node.
	 *
	 * @param DOMNode     $node Node.
	 * @param DOMDocument $dom  Parent document.
	 * @return string
	 */
	private static function node_inner_html( $node, $dom ) {
		$html = '';
		foreach ( $node->childNodes as $child ) {
			$html .= $dom->saveHTML( $child );
		}
		return trim( $html );
	}

	/**
	 * Get outer HTML of a DOM node.
	 *
	 * @param DOMNode     $node Node.
	 * @param DOMDocument $dom  Parent document.
	 * @return string
	 */
	private static function outer_html( $node, $dom ) {
		return trim( $dom->saveHTML( $node ) );
	}
}
