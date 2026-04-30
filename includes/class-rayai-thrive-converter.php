<?php
/**
 * Converts AI-generated HTML into Thrive Architect (TCB2) compatible markup.
 *
 * Based on real Thrive Architect output samples, modern TAr wraps every
 * top-level element (heading, paragraph, list, blockquote, image) in its
 * OWN `<div class="thrv_wrapper thrv_text_element">` block so each is
 * independently editable in the TAr editor.
 *
 * Distinguishing marks in real Thrive output:
 *   - Each h2/h3/p/ul/ol in its own `thrv_wrapper thrv_text_element` div
 *   - Headings get `id="t-{unix-ms-timestamp}"` and `class=""`
 *   - Anchor tags get `style="outline: none;"`
 *   - Images wrapped in `thrv_wrapper thrv_image_shortcode thrv_image`
 *   - TOC entries generated dynamically from actual headings, with
 *     `data-css` values taken from the template's `data-heading-style`
 *
 * @package RayAI_Content_Orchestrator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RAYAI_Thrive_Converter {

	/**
	 * Counter used to generate unique heading IDs for a single conversion.
	 *
	 * @var int
	 */
	private static $heading_counter = 0;

	/**
	 * Convert AI HTML into Thrive-compatible wrapped markup.
	 *
	 * Each top-level element is wrapped in its own `thrv_wrapper thrv_text_element`
	 * so TAr treats them as independent editable blocks. Headings get `t-{ms}` IDs,
	 * anchors get `outline: none;` inline style.
	 *
	 * @param string $html AI-generated HTML content.
	 * @return string Thrive-wrapped HTML content.
	 */
	public static function convert( $html ) {
		$html = trim( $html );
		if ( empty( $html ) ) {
			return '';
		}

		// Strip document-level boilerplate some AI models emit.
		$html = self::strip_document_wrapper( $html );

		// Remove any AI-generated Table of Contents — the Thrive theme handles
		// TOC rendering from heading IDs, so we don't want a duplicate.
		$html = self::strip_ai_toc( $html );

		self::$heading_counter = (int) ( microtime( true ) * 1000 );

		$dom = new DOMDocument( '1.0', 'UTF-8' );
		libxml_use_internal_errors( true );
		$wrapped = '<?xml encoding="UTF-8"><div id="rayai-thrive-root">' . $html . '</div>';
		$dom->loadHTML( $wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		libxml_clear_errors();

		$root = $dom->getElementById( 'rayai-thrive-root' );
		if ( ! $root ) {
			return $html;
		}

		$blocks = array();
		foreach ( $root->childNodes as $node ) {
			$block = self::convert_node( $node, $dom );
			if ( ! empty( $block ) ) {
				$blocks[] = $block;
			}
		}

		return implode( "\n", $blocks );
	}

	/**
	 * Convert a single DOM node into a Thrive-wrapped element.
	 *
	 * @param DOMNode     $node Node to convert.
	 * @param DOMDocument $dom  Parent document.
	 * @return string Thrive-wrapped HTML or empty string.
	 */
	private static function convert_node( $node, $dom ) {
		if ( XML_TEXT_NODE === $node->nodeType ) {
			$text = trim( $node->nodeValue );
			if ( '' === $text ) {
				return '';
			}
			return sprintf(
				'<div class="thrv_wrapper thrv_text_element"><p>%s</p></div>',
				esc_html( $text )
			);
		}

		if ( XML_ELEMENT_NODE !== $node->nodeType ) {
			return '';
		}

		$tag = strtolower( $node->nodeName );

		// Add `style="outline: none;"` to any anchors inside this node.
		self::add_anchor_outline( $node );

		// Auto-assign `t-{timestamp}` IDs and empty `class` attribute to headings.
		if ( preg_match( '/^h[1-6]$/', $tag ) ) {
			if ( ! $node->hasAttribute( 'id' ) || '' === $node->getAttribute( 'id' ) ) {
				self::$heading_counter++;
				$node->setAttribute( 'id', 't-' . self::$heading_counter );
			}
			if ( ! $node->hasAttribute( 'class' ) ) {
				$node->setAttribute( 'class', '' );
			}
		}

		// Thrive normalizes lists by adding `class=""` on save. Set it
		// upfront so our output matches exactly what Thrive expects.
		if ( in_array( $tag, array( 'ul', 'ol' ), true ) ) {
			if ( ! $node->hasAttribute( 'class' ) ) {
				$node->setAttribute( 'class', '' );
			}
		}

		switch ( $tag ) {
			case 'h1':
			case 'h2':
			case 'h3':
			case 'h4':
			case 'h5':
			case 'h6':
			case 'p':
			case 'ul':
			case 'ol':
			case 'blockquote':
			case 'pre':
			case 'table':
				$outer = self::outer_html( $node, $dom );
				return sprintf( '<div class="thrv_wrapper thrv_text_element">%s</div>', $outer );

			case 'img':
				$src = $node->getAttribute( 'src' );
				$alt = $node->getAttribute( 'alt' );
				return sprintf(
					'<div class="thrv_wrapper thrv_image_shortcode thrv_image"><img src="%s" alt="%s" /></div>',
					esc_url( $src ),
					esc_attr( $alt )
				);

			case 'figure':
				$outer = self::outer_html( $node, $dom );
				return sprintf( '<div class="thrv_wrapper thrv_image_shortcode thrv_image">%s</div>', $outer );

			case 'hr':
				return '<div class="thrv_wrapper thrv-divider"><hr class="tve_sep" /></div>';

			case 'div':
			case 'section':
			case 'article':
			case 'html':
			case 'body':
			case 'main':
			case 'header':
			case 'footer':
			case 'aside':
			case 'nav':
				// Containers — recurse into children and flatten each into its
				// own Thrive wrapper. This handles AI output that wraps the
				// whole article in <html>/<body> or uses <section> for each H2.
				$nested = array();
				foreach ( $node->childNodes as $child ) {
					$nested_block = self::convert_node( $child, $dom );
					if ( ! empty( $nested_block ) ) {
						$nested[] = $nested_block;
					}
				}
				return implode( "\n", $nested );

			case 'head':
			case 'title':
			case 'meta':
			case 'link':
			case 'style':
			case 'script':
			case 'noscript':
				// Document-level elements — drop entirely. These should never
				// appear in body content but some AI models emit full HTML docs.
				return '';

			default:
				// Unknown elements: wrap in a generic text element so Thrive can still render them.
				$outer = self::outer_html( $node, $dom );
				if ( '' === trim( $outer ) ) {
					return '';
				}
				return sprintf( '<div class="thrv_wrapper thrv_text_element">%s</div>', $outer );
		}
	}

	/**
	 * Strip full-document HTML wrappers that some AI models emit.
	 *
	 * Some models wrap their output in `<!DOCTYPE>`, `<html>`, `<head>`,
	 * `<body>` — we only want the body content. This extracts it so the
	 * DOM walker doesn't treat `<body>` as a single giant text block.
	 *
	 * @param string $html Raw AI HTML.
	 * @return string HTML without document-level boilerplate.
	 */
	private static function strip_document_wrapper( $html ) {
		// Remove DOCTYPE declarations.
		$html = preg_replace( '/<!DOCTYPE[^>]*>/i', '', $html );

		// If there's a <body>...</body>, extract just the inner content.
		if ( preg_match( '/<body\b[^>]*>(.*?)<\/body>/is', $html, $m ) ) {
			$html = $m[1];
		}

		// Strip any stray <html>, <head>, <title>, <meta>, <link>, <style>, <script> tags.
		$html = preg_replace( '/<\/?html\b[^>]*>/i', '', $html );
		$html = preg_replace( '/<head\b[^>]*>.*?<\/head>/is', '', $html );
		$html = preg_replace( '/<title\b[^>]*>.*?<\/title>/is', '', $html );
		$html = preg_replace( '/<meta\b[^>]*\/?>/i', '', $html );
		$html = preg_replace( '/<link\b[^>]*\/?>/i', '', $html );
		$html = preg_replace( '/<style\b[^>]*>.*?<\/style>/is', '', $html );
		$html = preg_replace( '/<script\b[^>]*>.*?<\/script>/is', '', $html );

		return trim( $html );
	}

	/**
	 * Remove AI-generated Table of Contents from the article.
	 *
	 * AI models often produce an `<h2>Table of Contents</h2>` followed by
	 * a `<ul>` of anchor links. Thrive themes generate their own TOC from
	 * the heading IDs, so an in-article TOC causes visible duplicates.
	 * This strips the first such TOC (including multilingual variants).
	 *
	 * @param string $html HTML content.
	 * @return string HTML with any AI-written TOC removed.
	 */
	private static function strip_ai_toc( $html ) {
		// Match <h2>TOC-like label</h2> followed by a <ul> of links.
		// Covers English, Dutch, German, French, Spanish common labels.
		$toc_label_pattern = '(?:Table\s+of\s+Contents|Inhoudsopgave|Inhalt|Table\s+des\s+mati[eè]res|[ÍI]ndice|Contents|Contenido)';

		$pattern = '/<h[1-6]\b[^>]*>\s*' . $toc_label_pattern . '\s*<\/h[1-6]>\s*<(?:ul|ol)\b[^>]*>.*?<\/(?:ul|ol)>/is';

		return preg_replace( $pattern, '', $html );
	}

	/**
	 * Add `style="outline: none;"` to all descendant anchor tags that don't
	 * already have a style attribute.
	 *
	 * @param DOMNode $node Node whose descendants to process.
	 */
	private static function add_anchor_outline( $node ) {
		if ( ! ( $node instanceof DOMElement ) ) {
			return;
		}
		$anchors = $node->getElementsByTagName( 'a' );
		foreach ( $anchors as $a ) {
			if ( ! $a->hasAttribute( 'style' ) || '' === $a->getAttribute( 'style' ) ) {
				$a->setAttribute( 'style', 'outline: none;' );
			}
		}
	}

	/**
	 * Insert a TOC block after the first thrv_wrapper element (the intro paragraph).
	 *
	 * Finds the boundary between the 1st and 2nd `<div class="thrv_wrapper`
	 * and inserts the TOC template content there. This places the Table of
	 * Contents right after the introduction, before the first H2 — matching
	 * the standard Thrive blog layout.
	 *
	 * @param string $content Thrive-wrapped AI content.
	 * @return string Content with TOC inserted, or original content if no TOC configured.
	 */
	public static function insert_toc_after_intro( $content ) {
		$toc_id = (int) RAYAI_Settings::get_thrive_toc_id();
		if ( $toc_id <= 0 ) {
			return $content;
		}

		$toc_html = self::build_library_reference( $toc_id );
		if ( empty( $toc_html ) ) {
			return $content;
		}

		// Find the start of the SECOND thrv_wrapper block — insert TOC before it.
		$marker = '<div class="thrv_wrapper';
		$first  = strpos( $content, $marker );
		if ( false === $first ) {
			return $content;
		}

		$second = strpos( $content, $marker, $first + strlen( $marker ) );
		if ( false === $second ) {
			return $content . "\n" . $toc_html;
		}

		// Ensure the TOC widget scans H3 headings too (needed for the CTA
		// heading to appear as the last TOC entry). The saved template may
		// only have data-headers="h2" — force it to include h3.
		$toc_html = preg_replace(
			'/data-headers="h2"/',
			'data-headers="h2,h3"',
			$toc_html
		);

		// Insert the CTA block right after the TOC (same button also
		// appears at the bottom via append_cta).
		$cta_after_toc = '';
		$cta_id = (int) RAYAI_Settings::get_thrive_cta_symbol_id();
		if ( $cta_id > 0 ) {
			$cta_after_toc = self::build_library_reference( $cta_id );
			if ( ! empty( $cta_after_toc ) ) {
				$cta_after_toc = "\n" . $cta_after_toc;
			}
		}

		return substr( $content, 0, $second ) . $toc_html . $cta_after_toc . "\n" . substr( $content, $second );
	}

	/**
	 * Append a CTA section to AI content.
	 *
	 * Injects (in order):
	 *   1. An H3 heading with the configured CTA heading text (e.g. "Vraag
	 *      jouw gratis chart aan"). This H3 is a real heading in the post
	 *      content, so Thrive's TOC widget automatically picks it up as the
	 *      last entry in the Table of Contents.
	 *   2. The CTA template/symbol HTML (button, paragraph, etc.).
	 *
	 * @param string $ai_content Thrive-wrapped AI content.
	 * @return string Content with CTA heading + block appended.
	 */
	public static function append_cta( $ai_content ) {
		$cta_id = (int) RAYAI_Settings::get_thrive_cta_symbol_id();
		if ( $cta_id <= 0 ) {
			return $ai_content;
		}

		$cta_html = self::build_library_reference( $cta_id );
		if ( empty( $cta_html ) ) {
			return $ai_content;
		}

		return rtrim( $ai_content ) . "\n" . $cta_html;
	}

	/**
	 * Build a reference div for any Thrive library item.
	 *
	 * Different Thrive library types render via different mechanisms:
	 *
	 *   - Symbol (`tcb_symbol`): rendered dynamically via reference.
	 *     Edits to the symbol propagate to all posts automatically.
	 *
	 *   - User Template (`tve_user_template`): the `template_content` post
	 *     meta IS the HTML. Thrive copies it inline into posts at insert
	 *     time — it's NOT a dynamic reference. We inject the content
	 *     directly so the template actually renders.
	 *
	 *   - Other (sections, content templates): tried via symbol-style
	 *     reference as a fallback.
	 *
	 * @param int $post_id Post ID of the library item.
	 * @return string Reference HTML or empty string on unknown type.
	 */
	public static function build_library_reference( $post_id ) {
		$post_id = (int) $post_id;
		if ( $post_id <= 0 ) {
			return '';
		}
		$post = get_post( $post_id );
		if ( ! $post ) {
			return '';
		}

		switch ( $post->post_type ) {
			case 'tcb_symbol':
				// Dynamic symbol reference — Thrive renders at runtime.
				return self::build_symbol_reference( $post_id, $post->post_title );

			case 'tve_user_template':
				// User templates store HTML in `template_content` meta.
				// Copy it inline — this is how Thrive itself inserts them.
				$content = get_post_meta( $post_id, 'template_content', true );
				if ( ! is_string( $content ) ) {
					return '';
				}
				// Strip hidden config divs whose content leaks as visible text
				// before Thrive processes the page (the __CONFIG_*__ blocks).
				$content = self::strip_thrive_config_blocks( $content );
				return trim( $content );

			default:
				// Fallback for saved sections / content templates — try a
				// symbol-style reference and let Thrive handle the lookup.
				$ct_prefix_map = array(
					'tcb_content_template' => 'content_template',
					'thrive_section'       => 'section',
					'tcb_saved_section'    => 'saved_section',
				);
				$ct_prefix = isset( $ct_prefix_map[ $post->post_type ] ) ? $ct_prefix_map[ $post->post_type ] : $post->post_type;

				return sprintf(
					'<div class="thrv_wrapper thrv_symbol thrv_symbol_%1$d" data-id="%1$d" data-shared-styles="0" data-ct="%2$s-%1$d" data-ct-name="%3$s" data-new-content="1"></div>',
					$post_id,
					esc_attr( $ct_prefix ),
					esc_attr( $post->post_title )
				);
		}
	}

	/**
	 * Build a Thrive Symbol reference div.
	 *
	 * Thrive renders this as the symbol's live content at display time —
	 * meaning edits to the symbol in Thrive's library propagate to every
	 * post using this reference without needing to re-edit each post.
	 *
	 * @param int    $symbol_id   Post ID of the Thrive symbol (tcb_symbol post type).
	 * @param string $symbol_name Optional display name for data-ct-name attribute.
	 * @return string Symbol reference HTML.
	 */
	public static function build_symbol_reference( $symbol_id, $symbol_name = '' ) {
		$symbol_id = (int) $symbol_id;
		if ( $symbol_id <= 0 ) {
			return '';
		}

		if ( empty( $symbol_name ) ) {
			$post = get_post( $symbol_id );
			if ( $post ) {
				$symbol_name = $post->post_title;
			}
		}

		return sprintf(
			'<div class="thrv_wrapper thrv_symbol thrv_symbol_%1$d" data-id="%1$d" data-shared-styles="1" data-ct="symbol-%1$d" data-ct-name="%2$s" data-new-content="1"></div>',
			$symbol_id,
			esc_attr( $symbol_name )
		);
	}

	/**
	 * Get available Thrive library items grouped by type.
	 *
	 * Queries all Thrive-specific post types that can be used as reusable
	 * blocks. The "Templates" tab in Thrive Architect's library UI maps to
	 * `tve_user_template` (user-saved templates). Symbols are `tcb_symbol`.
	 *
	 * @return array Grouped map: 'Group Label' => array of { id, title, post_type }.
	 */
	public static function get_available_library_items() {
		$groups = array(
			'Thrive Symbols (dynamic)' => array( 'post_type' => 'tcb_symbol' ),
			'Thrive Templates'         => array( 'post_type' => 'tve_user_template' ),
			'Thrive Sections'          => array( 'post_type' => 'thrive_section' ),
			'Thrive Saved Sections'    => array( 'post_type' => 'tcb_saved_section' ),
			'Thrive Content Templates' => array( 'post_type' => 'tcb_content_template' ),
		);

		$out = array();
		foreach ( $groups as $label => $cfg ) {
			// Skip if the post type isn't registered OR has no posts.
			if ( ! post_type_exists( $cfg['post_type'] ) ) {
				continue;
			}

			$posts = get_posts( array(
				'post_type'        => $cfg['post_type'],
				'post_status'      => array( 'publish', 'private' ),
				'numberposts'      => 200,
				'orderby'          => 'title',
				'order'            => 'ASC',
				'suppress_filters' => false,
			) );

			if ( empty( $posts ) ) {
				continue;
			}

			$items = array();
			foreach ( $posts as $p ) {
				// For user templates, Thrive stores the friendly name in the
				// `template_name` post meta and the category (button/section/toc)
				// in `template_type`. The post_title is the generic "User template".
				if ( 'tve_user_template' === $p->post_type ) {
					$name = get_post_meta( $p->ID, 'template_name', true );
					$type = get_post_meta( $p->ID, 'template_type', true );
					if ( ! empty( $name ) ) {
						$title = ! empty( $type )
							? sprintf( '%s [%s]', $name, $type )
							: $name;
					} else {
						$title = $p->post_title ? $p->post_title : sprintf( '(no title) #%d', $p->ID );
					}
				} else {
					$title = $p->post_title ? $p->post_title : sprintf( '(no title) #%d', $p->ID );
				}

				$items[] = array(
					'id'        => (int) $p->ID,
					'title'     => $title,
					'post_type' => $p->post_type,
				);
			}
			$out[ $label ] = $items;
		}

		return $out;
	}

	/**
	 * Strip Thrive config blocks that leak as visible text on the frontend.
	 *
	 * Thrive templates contain hidden `<div class="thrive-colors-palette-config">`
	 * elements with `__CONFIG_colors_palette__...json...__CONFIG_colors_palette__`
	 * content. These are supposed to be hidden with `display:none !important` but
	 * WordPress's normal content rendering (without Thrive active) shows them as
	 * raw text. Thrive rebuilds these on first editor save, so stripping them
	 * from the injected template is safe.
	 *
	 * @param string $html Template HTML.
	 * @return string HTML with config blocks removed.
	 */
	private static function strip_thrive_config_blocks( $html ) {
		// Remove <div class="thrive-colors-palette-config" ...>...</div> blocks.
		$html = preg_replace(
			'/<div\s+class="thrive-colors-palette-config"[^>]*>.*?<\/div>/is',
			'',
			$html
		);
		// Remove any stray __CONFIG_*__ blocks that might exist outside divs.
		$html = preg_replace(
			'/__CONFIG_[a-z_]+__.*?__CONFIG_[a-z_]+__/is',
			'',
			$html
		);
		return $html;
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

	/**
	 * Set all post meta keys required for Thrive Architect to recognize
	 * this post as a TCB-built post and open it directly in the editor
	 * (bypassing the "Upgrade this post to Thrive Architect" prompt).
	 *
	 * Keys chosen based on a real-world meta inventory from a production
	 * Thrive install. Critical flags:
	 *   - tve_updated_post (content storage)
	 *   - thrive_content_set = '1' (primary "has Thrive content" flag)
	 *   - tcb_editor_enabled = 'enabled' (modern TCB2 editor flag)
	 *   - tcb2_ready = '1'
	 *
	 * @param int    $post_id        Post ID.
	 * @param string $thrive_content The Thrive-compatible HTML content.
	 */
	public static function set_thrive_meta( $post_id, $thrive_content ) {
		// The main content store — lang-less key (what modern Thrive reads).
		update_post_meta( $post_id, 'tve_updated_post', $thrive_content );

		// The three CRITICAL flags that tell Thrive this post is TCB-built.
		update_post_meta( $post_id, 'thrive_content_set', '1' );
		update_post_meta( $post_id, 'tcb_editor_enabled', 'enabled' );
		update_post_meta( $post_id, 'tcb2_ready', '1' );

		// Commonly-set meta that appears on real Thrive posts (defensive defaults).
		update_post_meta( $post_id, 'thrive_element_visibility', array() );
		update_post_meta( $post_id, 'thrive_icon_pack', '' );
		update_post_meta( $post_id, 'thrive_tcb_post_fonts', array() );

		// Boolean feature flags — all "0" means none of these features are in use.
		update_post_meta( $post_id, 'tve_has_masonry', '0' );
		update_post_meta( $post_id, 'tve_has_typefocus', '0' );
		update_post_meta( $post_id, 'tve_has_wistia_popover', '0' );

		// "More" tag split state — no more tag in AI-generated content.
		update_post_meta( $post_id, 'tve_content_before_more', '' );
		update_post_meta( $post_id, 'tve_content_more_found', '0' );

		// CSS placeholders.
		if ( ! get_post_meta( $post_id, 'tve_custom_css', true ) ) {
			update_post_meta( $post_id, 'tve_custom_css', '' );
		}
		if ( ! get_post_meta( $post_id, 'tve_user_custom_css', true ) ) {
			update_post_meta( $post_id, 'tve_user_custom_css', '' );
		}

		// Page events (animations) and globals (phone/email/icons).
		update_post_meta( $post_id, 'tve_page_events', array() );
		update_post_meta( $post_id, 'tve_globals', array(
			'e_phone'    => '',
			'e_email'    => '',
			'used_icons' => array(),
		) );
		update_post_meta( $post_id, 'tve_global_scripts', array() );

		// Save timestamp used by Thrive's internal state tracking.
		update_post_meta( $post_id, 'tve_save_post', time() );

		// Inject CSS from Thrive user templates (TOC styling, button colors, etc.).
		self::inject_user_template_css( $post_id, (int) RAYAI_Settings::get_thrive_toc_id() );
		self::inject_user_template_css( $post_id, (int) RAYAI_Settings::get_thrive_cta_symbol_id() );

		// Mark this post as AICC-generated in Thrive mode so we can identify it later.
		update_post_meta( $post_id, '_rayai_thrive_generated', '1' );
	}

	/**
	 * Copy a Thrive user template's CSS into the post's tve_custom_css.
	 *
	 * Thrive user templates store their styling in `template_media_css`
	 * (a serialized PHP array of CSS rules keyed by media query). When
	 * we inject the template's HTML via template_content, we must also
	 * inject this CSS — otherwise the element falls back to Thrive's
	 * default styling (wrong colors, wrong spacing).
	 *
	 * Called once per template (TOC, CTA, etc.) during set_thrive_meta().
	 *
	 * @param int $post_id    The post to inject CSS into.
	 * @param int $template_id Post ID of the Thrive user template.
	 */
	private static function inject_user_template_css( $post_id, $template_id ) {
		if ( $template_id <= 0 ) {
			return;
		}

		$tpl_post = get_post( $template_id );
		if ( ! $tpl_post || 'tve_user_template' !== $tpl_post->post_type ) {
			return;
		}

		// Merge template_css (plain CSS string) if present.
		$template_css = get_post_meta( $template_id, 'template_css', true );

		// Merge template_media_css (serialized array of CSS rules per breakpoint).
		$media_css_raw = get_post_meta( $template_id, 'template_media_css', true );
		$media_css     = maybe_unserialize( $media_css_raw );

		$css_parts = array();

		if ( ! empty( $template_css ) && is_string( $template_css ) ) {
			$css_parts[] = $template_css;
		}

		if ( is_array( $media_css ) ) {
			foreach ( $media_css as $key => $value ) {
				if ( empty( $value ) || ! is_string( $value ) ) {
					continue;
				}
				if ( is_int( $key ) ) {
					$css_parts[] = $value;
				} else {
					$css_parts[] = '@media ' . $key . ' { ' . $value . ' }';
				}
			}
		}

		if ( empty( $css_parts ) ) {
			return;
		}

		$tpl_css  = implode( "\n", $css_parts );
		$existing = get_post_meta( $post_id, 'tve_custom_css', true );
		$merged   = is_string( $existing ) ? trim( $existing . "\n" . $tpl_css ) : $tpl_css;
		update_post_meta( $post_id, 'tve_custom_css', $merged );
	}
}
