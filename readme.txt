=== AI Content Orchestrator ===
Contributors: raybogman
Tags: ai, content, seo, claude, openai, gpt, anthropic, yoast, content-generator, linkedin
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 2.5.6
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI-powered WordPress content creation with website scanning, SEO optimization, and Yoast SEO integration. Supports Claude (Anthropic) and OpenAI GPT. Works on any hosting with a multi-step pipeline that avoids server timeouts.

== Description ==

AI Content Orchestrator generates SEO-optimized blog posts and pages for your WordPress site using Claude AI (Anthropic) or OpenAI GPT models.

**Key Features:**

* **Dual AI Provider Support** — Choose between Claude (Anthropic) and OpenAI (GPT) for content generation. Switch between providers at any time.
* **Website Scanning** — Crawls any website to extract content, headings, and metadata.
* **PDF Sources** — Upload PDFs as context for content generation. Saved in a library for future reuse. Text is extracted automatically. Uses sitemaps and internal link discovery for comprehensive context gathering.
* **AI Featured Images** — Generate a custom hero image for each post using OpenAI DALL-E 3 or Ideogram. Choose your preferred image provider in Settings. Automatically set as the featured image; LinkedIn picks it up via Open Graph tags.
* **LinkedIn Integration** — Automatically share published content to your LinkedIn profile. Connect via OAuth 2.0, toggle per post, and content is shared when published (immediately or after approval).
* **Project Vision** — Define baseline instructions (brand voice, tone, audience, rules) that the AI always follows before generating any content. Set once in Settings, applied to every request.
* **AI Content Generation** — Two-step process: generates SEO metadata first, then creates original HTML content with table of contents, FAQ sections, and calls-to-action.
* **SEO Metadata** — Automatically generates SEO titles (max 60 chars), meta descriptions (max 155 chars), slugs, focus keyphrases, tags, and categories.
* **Yoast SEO Integration** — Automatically populates Yoast SEO fields (title, meta description, focus keyphrase) when Yoast is installed.
* **WordPress Category Selection** — Browse and select from existing WordPress categories. The AI also suggests additional categories based on content.
* **Draft or Publish** — Save as draft for review or publish immediately.
* **Scheduling Mode** — Schedule content for future publication with a human-in-the-loop review queue. Drafts wait for manual approval; published items schedule automatically via WordPress cron.
* **Blog Post or Page** — Generate blog posts (1000-2000 words with tags/categories) or pages (500-1000 words).
* **12 Blog Styles** — Choose from How-To Guide, Listicle, Ultimate Guide, Comparison, Case Study, Problem-Solution, Beginner's Guide, Data-Driven, Storytelling, Opinion, Checklist, or Standard — each with a specialized AI prompt.
* **Smart URL Prioritization** — Prioritizes high-value pages (services, about, blog, etc.) during scanning.
* **Progress Logging** — Real-time progress updates during scanning and generation.
* **Content Preview** — Preview generated HTML content directly in the admin.

**Supported Models:**

* Claude Sonnet 4.6, Opus 4.6, Haiku 4.5 (Anthropic)
* GPT-4o, GPT-4o Mini, GPT-4 Turbo, GPT-4.1, GPT-4.1 Mini, GPT-4.1 Nano (OpenAI)

**How it works:**

1. (Optional) Enter a website URL to scan for context — the scanner crawls sitemaps and pages.
2. Write a prompt describing the content you want.
3. Choose blog post or page, draft or publish, and select categories.
4. Click "Create Content" — the AI generates SEO metadata then full HTML content.
5. Content is published to WordPress with tags, categories, and Yoast SEO fields populated.

== Installation ==

1. Upload the `ai-content-orchestrator` folder to `/wp-content/plugins/`.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **AI Content > Settings** and select your AI provider (Claude or OpenAI).
4. Enter your API key and click Save Changes.
5. Go to **AI Content > Create Content** to generate your first post.

== Frequently Asked Questions ==

= What AI models does this support? =

Both Claude (Anthropic) and OpenAI GPT models. You can configure both and switch between them in Settings.

= Do I need Yoast SEO? =

No, Yoast SEO is optional. If installed, the plugin automatically populates SEO title, meta description, and focus keyphrase. Without Yoast, metadata is still generated and stored in the post excerpt.

= Can I select which categories to use? =

Yes. Existing WordPress categories are listed as checkboxes on the creation form. The AI may also suggest additional categories which are created automatically.

= Can I scan any website? =

Yes, the scanner can crawl any publicly accessible website via sitemaps and internal links.

== Changelog ==

= 2.4.0 =
* New: Full free/Enterprise feature gating. Free version: 4 blog styles, DALL-E images only, inline linking (max 3), 1 URL scanning, WordPress output, content repurposing, manual scheduling. Enterprise version adds: all 13 blog styles, Ideogram, competitor analysis, LinkedIn & Instagram sharing, Thrive Architect, PDF sources, adaptive linking (max 15), multiple URLs, auto-fill schedule, email notifications, Bulk Create, and Refresh Content.

= 2.3.0 =
* New: Freemius SDK integration for free/Enterprise licensing. Enterprise features gated behind license. Menu items show ENT badges. Upgrade prompts for gated pages.

= 2.2.0 =
* New: Instagram Integration — auto-post to Instagram Business/Creator accounts when content is published. OAuth via Meta/Facebook, AI-generated captions, featured image as post image. Toggle on Create Content and Bulk Create pages. Settings tab with setup guide.

= 2.0.0 =
* New: "Upcoming Publications" visual timeline on the Scheduled Content page. Shows all scheduled and pending posts in chronological order with color-coded status dots (green = scheduled, yellow = awaiting approval), countdown timers ("in 3 days"), and LinkedIn indicators.
* New: Publish Notification setting in Settings → Content. Enter email addresses (comma-separated) to receive an email when a scheduled AI-generated post is published. Includes post title, URL, and edit link.
* New: 3 FAQ entries added for cURL timeout troubleshooting, optimal page scan count, and Ideogram color palette errors.

= 1.9.1 =
* New: Refresh Content page is now fully functional. Select a published post, click Analyze to detect issues (thin content, missing FAQ, missing internal links, outdated content), then click Refresh Post to have the AI rewrite and improve the content. Internal links are automatically added if enabled. Original URL and SEO value are preserved.

= 1.9.0 =
* New: Publishing Schedule feature. Set a default frequency in Settings → Content: Daily, Every 2/3 days, Weekly, Bi-weekly, or Monthly. Default publish time and skip weekends options included. On the Bulk Create page, click "Fill Dates" to auto-fill publish dates for all checked rows based on the schedule — starting from tomorrow, advancing by the chosen interval.
* New: Navigation warning when content generation is running (single + bulk create). Browser confirms before leaving the page to prevent lost work.
* New: Bulk actions (Delete Selected, Approve Selected) on the Scheduled Content review queue with select-all checkboxes.

= 1.8.3 =
* New: Adaptive inline/footer link split algorithm for "Both" mode. The plugin now calculates the optimal split based on content length: short posts get fewer inline links (40/60 split), medium posts get balanced (60/40), long posts get more inline capacity (65/35). A density cap ensures no more than 1 inline link per 4 paragraphs. Footer always gets at least 2 links in "Both" mode. No duplicate links between inline and footer. Progress log shows the calculated split.

= 1.8.0 =
* New: Bulk Create detail panel now matches Single Create result card — shows AI Provider, Featured Image grid (4 options, click to select), and Repurpose buttons (Email, Twitter, Instagram, Pinterest with copy to clipboard). Each row has its own independent image selector and repurpose generator.
* Improved: Internal links now use natural anchor text. Instead of appending "(Full Post Title)" in parentheses, the linker finds a relevant keyword from the linked post's title within the paragraph text and turns that word into a link. Example: "iPaaS" becomes a clickable link rather than "(iPaaS Solutions for Business Growth)".
* Fixed: Image selection and repurpose handlers refactored from ID-based to class-based selectors, ensuring they work for both single and bulk create without conflicts.

= 1.7.1 =
* New: Link Placement setting in Settings → Content tab. Choose where internal links appear: "Inline only" (spread through paragraphs — best for SEO), "Related Articles section only" (styled box at the bottom — best for readers), or "Both" (inline first, overflow to footer — recommended). Default is "Both".
* Improved: Related Articles footer section now renders as a styled card with blue left border, light background, and clean list formatting instead of a plain HTML list.

= 1.6.1 =
* Improved: Bulk Create results table now updates live as each post is created. The "Created Posts" table appears immediately when generation starts and rows are added one by one as each post finishes. Header shows real-time counter (e.g., "Created Posts (2/5)") with a spinner while running and a checkmark when complete. View and Edit links open in new windows.

= 1.6.0 =
* Improved: AI API calls now automatically retry once on timeout, connection, or rate limit errors. Waits 5 seconds between attempts. If the retry also fails, the error is logged and the pipeline moves on to the next post (bulk) or reports the error (single). This significantly improves bulk create reliability on servers with occasional network issues.

= 1.5.9 =
* New: "Save Log" button on both Create Content and Bulk Create progress windows. Downloads the full progress log as a timestamped .txt file (e.g., aicc-log-2026-04-21-14-30-00.txt) for debugging and sharing. Includes date, page URL, and all log lines.

= 1.5.8 =
* Improved: "Suggest Topics" now also recommends the best blog style for each topic. The AI picks the most suitable format (How-To, Listicle, Comparison, etc.) — both the topic and style dropdown are filled automatically. No extra API cost — done in a single AI call.

= 1.5.2 =
* New: "Generate All" button on Bulk Create now works. Creates posts sequentially using the same 4-step pipeline as single Create Content (scan → SEO metadata → content generation → publish). Each post shows full progress logging with a separator line between posts. Results table shows created posts with View/Edit links. Supports all shared settings: URL scanning, output format, internal linking, competitor analysis, categories, LinkedIn sharing, and featured images.

= 1.5.1 =
* Improved: Bulk Create shared settings now match Create Content page — saved URLs with click-to-use and remove, SEO Enhancements section (internal linking + competitor analysis toggles), Output Format dropdown (WordPress/Thrive), and Categories selection.
* Improved: "Suggest Topics" now shows a visible progress bar with spinner and status text while the AI generates topics. Success shows a green checkmark with topic count; errors show a warning icon with the message.

= 1.5.0 =
* New: "Suggest Topics" button on Bulk Create page now works. Enter a seed keyword (e.g., "ipaas"), click the button, and the AI generates 5 blog topic ideas that auto-fill into the table. The button shows a spinning icon while working. Topics are written in the same language as your keyword.

= 1.4.9 =
* New: Competitor Gap Analysis is now a persistent setting in Settings → Content tab. When enabled, every new post automatically runs competitor analysis — no need to check the box on the Create Content page each time. The per-post checkbox still works as an override.

= 1.4.8 =
* Improved: Anchor text now always uses 2+ words for better SEO. Single-word anchors like "basis" or "echte" are no longer used — they provide no context to search engines. If no multi-word phrase from the post title matches in the content, the full post title is used as the link text instead.

= 1.4.7 =
* Improved: Internal links are now spread evenly across paragraphs instead of clustering in the first few. Each paragraph gets at most one link. The intro paragraph is always kept clean (no links). Links are distributed from paragraph 2 onward, matching SEO best practices for natural link placement.
* Improved: "Related Articles" fallback section uses CSS classes (aicc-related) for better Thrive Architect compatibility.
* Improved: Progress log now shows which paragraph each link was placed in (e.g., "paragraph 3/12").

= 1.4.6 =
* Fixed: Settings not saving on some sites. Replaced WordPress options.php form handler with a custom save handler. The plugin now saves each option directly via update_option(), bypassing options.php entirely. This fixes compatibility issues with certain hosting configurations, security plugins, and caching setups that interfere with WordPress's default settings save flow.

= 1.4.5 =
* Fixed: "Automatic Internal Links" and "Title Overlay" settings not saving on some sites. Replaced checkboxes with dropdown selects (Enabled/Disabled) to guarantee a value is always submitted in the form, regardless of browser behavior, caching plugins, or server configuration.

= 1.4.4 =
* Fixed: Checkbox settings (like "Automatic Internal Links") not saving on some sites. Added a hidden field to ensure the value is always submitted in the form, even when unchecked. Without this, unchecking and saving would clear the value, and on some WordPress configurations, checking and saving wouldn't persist either.

= 1.4.3 =
* Fixed: Internal links showing as raw HTML text on the frontend. The link insertion regex was matching keywords inside HTML attributes (e.g., title="...") of previously inserted links, creating broken nested anchor tags. Added an HTML tag boundary check and removed the title attribute from inserted links to prevent cascading breakage.

= 1.4.2 =
* Fixed: Internal linking now finds relevant posts. The previous WP_Query search-based approach returned 0 results when keywords contained multiple languages or many words. Replaced with a manual scoring system that queries all published posts and scores each by keyword hits in the title (+3 points) and content (+1 point). Posts need a minimum score of 2 to qualify as link candidates.

= 5.0.0 =
* MAJOR RELEASE: 5 new features, restructured navigation, and new admin pages.
* New: Dashboard page — overview with total posts, monthly count, scheduled items, top performing posts, and posts needing a refresh (older than 6 months). Quick action buttons for all plugin pages.
* New: Automatic Internal Linking — after AI writes the article, the plugin scans your published posts and inserts 3-5 relevant internal links. Configurable toggle and max links in Settings → Content tab. Huge SEO benefit.
* New: Bulk Create page — queue multiple blog posts (enter topics, pick styles, set dates) and generate them all in batch. AI topic suggestions from a seed keyword.
* New: Content Repurposing — after creating content, click buttons to generate ready-to-use versions for Email Newsletter, X/Twitter thread, Instagram caption, and Pinterest pin description. Copy to clipboard with one click.
* New: Competitor Gap Analysis — toggle on the Create Content page. The AI analyzes what top-ranking content typically covers for your focus keyphrase and identifies 2-3 gaps. These gaps are fed to the content generator so your article covers topics competitors miss.
* New: Refresh Content page — select an existing published post, analyze it for thin sections, missing FAQs, and outdated content, then refresh it with AI while keeping your URL and SEO value.
* Restructured navigation: Dashboard is now the landing page. Menu order: Dashboard → Create Content → Bulk Create → Refresh Content → Scheduled → Settings.
* Settings now use tabs: General, Content, Images, Thrive Architect, LinkedIn, Scanner. Each tab has 3-6 fields max — clean and fast to navigate.
* New SEO Enhancements section on the Create Content form with toggles for internal linking and competitor analysis.
* New classes: AICC_Internal_Linker (keyword extraction, post matching, contextual link insertion), AICC_Repurposer (multi-platform content generation).

= 4.20.0 =
* Rewrote all settings titles, descriptions, and help text in plain language. Every field now uses clear, non-technical wording that any WordPress user can understand — no jargon like "crawl," "propagate," "markup," "scope," or "prepend."
* Renamed labels for clarity: "Negative Prompt" → "Avoid in Images", "TOC Block" → "Table of Contents", "CTA Block" → "Call-to-Action Button", "Max Pages to Crawl" → "Max Pages to Scan", "Max Context Characters" → "Max Text to Send to AI", "Request Timeout" → "Scan Timeout", "Bold Font (.ttf)" → "Bold Font File", "Validate API Keys" → "Test Your Connections", "Validate Claude Key" → "Test Claude Connection".
* Added model guidance: Claude and OpenAI model dropdowns now explain what each model is best for (quality vs. speed vs. cost).
* "WordPress (Classic HTML)" renamed to "WordPress (Standard)" everywhere.
* Simplified image provider descriptions, scanner settings, LinkedIn setup text, and Thrive Architect explanations.

= 4.19.4 =
* Removed CTA Heading Text setting — no more H3 heading injection.
* CTA Block now appears in TWO places: (1) right after the TOC widget, (2) at the bottom of the post. Both use the same library template.
* Simplified `append_cta()` to only handle the button block.

= 4.19.3 =
* Moved CTA heading position: the H3 "Vraag jouw gratis chart aan" is now inserted directly BELOW the TOC widget (before the article content), not at the bottom of the post. The TOC entry links to this heading. The CTA button stays at the bottom of the post. This matches the layout of the user's reference posts.

= 4.19.2 =
* Fixed: CTA heading "Vraag jouw gratis chart aan" now appears in the TOC. The injected TOC template's `data-headers` attribute is force-updated from `"h2"` to `"h2,h3"` so Thrive's TOC widget scans H3 headings too. This ensures the CTA heading (which is an H3) is always included as the last TOC entry, regardless of how the TOC template was originally saved.

= 4.19.1 =
* Fixed: TOC is back. The TOC template injection was incorrectly removed in 4.19.0 — the TOC comes from the injected template, NOT from Thrive's theme. Re-enabled `insert_toc_after_intro()` and TOC CSS injection.
* Fixed: `__CONFIG_colors_palette__` JSON text no longer leaks as visible text on the frontend. New `strip_thrive_config_blocks()` method removes hidden Thrive config divs from injected templates before they're stored in post content. Thrive rebuilds these configs on first editor save.
* Both TOC and CTA heading now work together: the TOC shows all H2/H3 headings including "Vraag jouw gratis chart aan" which links down to the CTA button section.

= 4.19.0 =
* New feature: CTA Heading Text setting. Enter your CTA heading (e.g. "Vraag jouw gratis chart aan") and the plugin injects a real H3 heading before the CTA button in every Thrive post. Because it's a real H3 in the post content, Thrive's TOC widget automatically picks it up as the last entry — solving the missing "Vraag jouw gratis chart aan" in the Table of Contents.
* Removed: TOC Block template injection. Thrive's built-in TOC widget handles TOC generation natively (it regenerates entries from actual headings on every edit). The injected template was causing visible `__CONFIG_colors_palette__` JSON text leaking on the frontend before Thrive processed the post, and Thrive replaced the static entries anyway. Let Thrive handle the TOC; the plugin now focuses on ensuring the right headings are in the content.
* The CTA section now injects two elements: (1) the H3 heading (with proper `t-{timestamp}` ID and `class=""`), (2) the CTA button/template from the library dropdown.
* TOC Block dropdown and `insert_toc_after_intro()` remain in the code but are no longer called from the admin pipeline.

= 4.18.0 =
* New feature: Edit overlay text after generation. The result view now shows two editable text fields (Line 1 bold, Line 2 italic) pre-filled with the auto-split SEO title. Click "Regenerate Image with Custom Text" to create a new featured image with your custom text — no need to regenerate the entire post.
* New AJAX endpoint `aicc_regenerate_overlay` — deletes the old overlay image, creates a new one with custom text, and updates the featured image.
* Custom overlay text stored in post meta `_aicc_overlay_line1` and `_aicc_overlay_line2` for persistence.
* Fixed: AVIF base image support for title overlay (backported from 4.17.2).
* Removed: Gutenberg Blocks output format. This was untested and not production-ready. The Output Format dropdown now has two options: WordPress (Classic HTML) and Thrive Architect. The Gutenberg converter class file remains in the codebase but is no longer called or selectable.

= 4.17.2 =
* Fixed: Title overlay now works with AVIF base images. Root cause: the AVIF format (`image/avif`) was not handled in the GD image loader. Added `imagecreatefromavif()` support (requires PHP 8.1+ with GD compiled with AVIF — PHP 8.2 has this by default). Falls back with a clear error message on older PHP versions.

= 4.17.1 =
* Fixed: "Select Image" and "Upload Font" buttons on the Settings page did nothing when clicked. Root cause: `wp_enqueue_media()` was not called on the Settings page, so the WordPress media library modal (`wp.media`) was not loaded. Now enqueued specifically for the `aicc-settings` page.

= 4.17.0 =
* New feature: Default Featured Image with Title Overlay. Select a base image from your media library and the plugin automatically composites the blog's SEO title onto it — creating a unique branded featured image for every post, no AI image generation needed.
* New Settings section: "Default Featured Image" with 5 fields:
  * Base Image — media library selector for the background image
  * Title Overlay — toggle to enable/disable text compositing
  * Overlay Text Color — color picker (default: #0d5e50 dark teal)
  * Bold Font (.ttf) — media library upload for the first line font
  * Italic Font (.ttf) — media library upload for the second line font
* Title is automatically split into 2 lines at the word midpoint, rendered in UPPERCASE, and centered on the image. Line 1 uses the bold font (larger), line 2 uses the italic font (smaller). Auto-shrinks if text exceeds 85% of image width.
* New class `AICC_Image_Overlay` — GD-based image compositing with `create()` and `create_and_attach()` methods.
* Supports JPEG, PNG, and WebP base images.
* Each post gets its own unique JPEG (saved to wp-content/uploads) so WordPress generates proper thumbnails.
* New WordPress media library field renderer `render_media_upload_field()` with live preview and remove button.
* New checkbox field renderer `render_checkbox_field()`.
* New color picker field renderer `render_color_field()` with hex input.
* TTF/OTF/WOFF font uploads now allowed in the WordPress media library via `upload_mimes` and `wp_check_filetype_and_ext` filters.
* Falls back to setting the base image directly (without overlay) if the font file is missing or GD fails.

= 4.16.0 =
* New feature: TOC Block dropdown in the Thrive Architect settings section. Pick a Thrive Table of Contents template from your library — the plugin auto-inserts it after the introduction paragraph (between the 1st and 2nd content block), matching the standard Thrive blog layout.
* New setting: `aicc_thrive_toc_id` with the same grouped dropdown (Symbols, Templates, Sections) as the CTA Block.
* New method `AICC_Thrive_Converter::insert_toc_after_intro()` — locates the boundary between the first and second `thrv_wrapper` blocks and injects the TOC template there.
* TOC template CSS (`template_media_css`) is now also injected into `tve_custom_css` so the TOC renders with the correct styling (matching the CTA CSS injection from 4.15.5).
* Generalized `inject_cta_template_css()` into `inject_user_template_css($post_id, $template_id)` — called once for the TOC and once for the CTA, keeping both properly styled.
* Refactored the Thrive library dropdown renderer into a generic `render_thrive_library_dropdown()` shared by both TOC and CTA fields.

= 4.15.5 =
* Fixed: CTA button now renders with the correct colors and styling when using a Thrive user template. Root cause: the template's CSS rules (stored in `template_media_css` and `template_css` post meta) were not being injected into the post's `tve_custom_css`. Without this CSS, the button fell back to Thrive's default styling — wrong color, wrong gradient, wrong spacing.
* New method `AICC_Thrive_Converter::inject_cta_template_css()` — reads the serialized CSS array from the user template's `template_media_css` meta, merges it into the post's `tve_custom_css` on generation. Supports media-query-scoped rules and global rules (like @import for fonts).
* CSS injection is called from `set_thrive_meta()` so it runs on every Thrive post creation.

= 4.15.4 =
* Fixed: Thrive output now adds `class=""` to `<ul>` and `<ol>` elements, matching Thrive Architect's exact normalization behavior. Previously Thrive would add this attribute on first save, causing a minor diff between plugin output and Thrive-normalized output. Now the stored content is byte-identical to what Thrive produces — no normalization needed on first open/save.

= 4.15.3 =
* Fixed: AI content appearing as ONE big Thrive block instead of individual editable blocks. Root cause: some AI models emit full HTML documents (`<html>`, `<head>`, `<body>` wrappers). The converter was treating `<body>` as an unknown element and stuffing the entire article into one `thrv_text_element`. Now `<html>`, `<body>`, `<main>`, `<header>`, `<footer>`, `<aside>`, `<nav>` are correctly flattened — each inner element gets its own Thrive wrapper.
* Fixed: duplicate Table of Contents visible on published Thrive posts. Root cause: the AI was generating its own in-article TOC (`<h2>Table of Contents</h2>` + `<ul>` of anchor links) in addition to the Thrive theme's auto-TOC. Now:
  1. The AI system prompt explicitly forbids generating a TOC section (covers English, Dutch, German, French, Spanish label variants).
  2. The converter strips any in-article TOC before conversion as a safety net.
* Document-level elements (`<head>`, `<title>`, `<meta>`, `<link>`, `<style>`, `<script>`, `<noscript>`) are now stripped entirely from AI output before conversion, preventing DOCTYPE and head metadata from leaking into post content.
* New helpers: `AICC_Thrive_Converter::strip_document_wrapper()` and `strip_ai_toc()`.
* Added "CRITICAL OUTPUT RULES" section to all AI content prompts (both page and blog styles): "Output ONLY body-level HTML fragments" and "Do NOT generate a Table of Contents section".

= 4.15.2 =
* Fixed: Thrive user templates now display their real names in the CTA Block dropdown instead of the generic "User template". Root cause: Thrive stores user-visible names in the `template_name` post meta, not in `post_title`. The dropdown now reads `template_name` and also shows the `template_type` (button/section/toc) in brackets for context — e.g. "CTO Chart [button]".
* Fixed: User templates now render correctly when appended as CTAs. Root cause: Thrive stores the template HTML in the `template_content` post meta and expects it to be copied INLINE into posts (not referenced as a symbol). The plugin now injects `template_content` directly for `tve_user_template` items, so your "Download jouw gratis chart hier" button (or any user template) actually renders.
* Symbols continue to use dynamic references (edits propagate); user templates are copied in at generation time.

= 4.15.1 =
* Fixed: Thrive "Templates" (from the Templates tab in Thrive Architect's library UI) now appear in the CTA Block dropdown. Root cause: Thrive stores them under the `tve_user_template` post type, which wasn't in the query.
* Expanded CTA dropdown to include 5 Thrive library post types: `tcb_symbol` (Symbols), `tve_user_template` (Templates), `thrive_section` (Sections), `tcb_saved_section` (Saved Sections), `tcb_content_template` (Content Templates).
* `build_library_reference()` now picks the correct `data-ct` prefix per post type (`symbol-`, `user_template-`, `content_template-`, `section-`) so Thrive renders the right library item.
* Symbols continue to use `data-shared-styles="1"` (dynamic updates); all other library types use `data-shared-styles="0"` (one-time copy).

= 4.15.0 =
* Simplified Thrive mode — removed the Thrive Post Template textarea, placeholder system, and template import feature. These added complexity without delivering reliable results.
* New approach: Thrive output is just the AI content wrapped in Thrive blocks (each heading, paragraph, list in its own thrv_wrapper). Your Thrive theme handles the TOC (most themes already auto-generate one from the headings).
* CTA dropdown expanded to show multiple Thrive library post types: Thrive Symbols (dynamic — edits propagate), Thrive Saved Sections, and Thrive Content Templates. Pick one item; the plugin appends it after the AI content.
* Settings section renamed from "Thrive Architect Template" to "Thrive Architect".
* Field renamed from "CTA Symbol (dynamic)" to "CTA Block".
* Removed methods: `AICC_Thrive_Converter::apply_template()`, `auto_template_from_raw()`, `generate_toc_entries()`, `extract_heading_style_css()`, `split_content_at_first_h2()`. Removed `AICC_Settings::get_thrive_template()` and `sanitize_thrive_template()`. Removed AJAX endpoint `aicc_import_thrive_template`.
* New methods: `AICC_Thrive_Converter::append_cta()`, `build_library_reference()`, `get_available_library_items()`.
* Note: existing `aicc_thrive_template` option values remain in the DB but are no longer read or used.

= 4.14.1 =
* New feature: "Import & Auto-Build Template" button on the Thrive Post Template setting. Enter any post ID of a Thrive-built post and the plugin reads its `tve_updated_post` meta, auto-identifies the TOC widget + CTA section, and generates a complete template with all 4 placeholders in the right places.
* No more hand-building 8000-char templates — one click produces a working template from an existing post.
* New method `AICC_Thrive_Converter::auto_template_from_raw()` — converts raw Thrive content to a template by locating the TOC widget via balanced `<div>` parsing and substituting placeholders.
* New AJAX endpoint `aicc_import_thrive_template`.
* The CTA section is preserved as raw HTML in the imported template so you can swap it with `{CTA}` only if you've set up a Thrive Symbol.

= 4.14.0 =
* New Thrive template placeholder: `{AI_CONTENT_intro}` — the opening paragraph of the article (everything before the first H2). Use this to place your intro ABOVE the Table of Contents, while the rest of the article goes via `{AI_CONTENT}` BELOW the TOC.
* New Thrive template placeholder: `{CTA}` — short alias for `{CTA_SYMBOL}`. Simpler to remember and type.
* When `{AI_CONTENT_intro}` is used, `{AI_CONTENT}` automatically receives only the body (from the first H2 onwards) — no content is duplicated.
* New helper method `AICC_Thrive_Converter::split_content_at_first_h2()` — splits Thrive-wrapped content at the first H2 wrapper.
* Backward-compatible — existing templates that only use `{AI_CONTENT}` continue to work unchanged.

= 4.13.0 =
* New feature: Thrive Symbol CTA support. Edits to your CTA in Thrive's Symbol library now propagate to every AI-generated post automatically — no regeneration needed.
* New setting: "CTA Symbol (dynamic)" on the Thrive Architect settings section. Auto-populated dropdown listing all published `tcb_symbol` posts on your site. If no symbols exist, falls back to a manual ID input.
* New template placeholder `{CTA_SYMBOL}` — put this in your Thrive Post Template where the CTA should appear. Plugin replaces it with a proper Thrive Symbol reference at generation time.
* New template placeholder `{THRIVE_SYMBOL:123}` — inline reference to a specific Thrive Symbol by post ID. Use this if you want multiple different symbols on one post.
* New method `AICC_Thrive_Converter::build_symbol_reference( $id, $name )` — produces the `<div class="thrv_wrapper thrv_symbol" data-id="..." ...></div>` markup that Thrive renders dynamically at display time.
* New method `AICC_Thrive_Converter::get_available_symbols()` — queries the tcb_symbol post type and returns available symbols for the settings dropdown.
* Documentation note: the TOC itself is inherently per-post (entries must link to specific heading IDs in the current article), so it stays as a `{TOC_ENTRIES}` template placeholder with plugin-generated entries. Only truly-shared blocks like CTAs can be made fully dynamic via Symbols.

= 4.12.0 =
* New feature: Thrive Post Template. Paste your complete Thrive Architect template in Settings (Table of Contents widget, CTA section, button, etc.) and the plugin will auto-stitch AI content into it on every Thrive-mode post.
* Two template placeholders:
  * `{AI_CONTENT}` — replaced with the AI article (each heading/paragraph/list wrapped in its own `thrv_wrapper thrv_text_element` div for full in-editor editability).
  * `{TOC_ENTRIES}` — auto-generated Table of Contents entries for every H2/H3 in the article. The plugin reads the `data-heading-style` JSON from your TOC widget so each level uses the correct `data-css` class for visual consistency with your theme.
* Fixed: Thrive output now correctly wraps EACH element (heading, paragraph, list) in its own `thrv_wrapper thrv_text_element` div — matching how modern Thrive Architect (TCB2) actually stores editable content. Previously we output clean unwrapped HTML which Thrive treated as a single non-editable block.
* New method `AICC_Thrive_Converter::apply_template()` — handles `{AI_CONTENT}` and `{TOC_ENTRIES}` substitution.
* New method `AICC_Thrive_Converter::generate_toc_entries()` — scans content for H2/H3 with IDs and produces Thrive-native TOC heading divs.
* Template sanitizer strips `<script>` tags and inline `on*` event handlers for safety while preserving all Thrive HTML (TOC widget markup, SVG icons, data attributes, color palette JSON).

= 4.11.3 =
* Fixed: "Upgrade this post to Thrive Architect" dialog no longer appears. Root cause: we were setting `tve_editor_enabled` (legacy Thrive v1, barely used) instead of `tcb_editor_enabled` (modern TCB2) and missing the critical `thrive_content_set` flag.
* Meta keys now precisely match a real Thrive production install:
  * Added `thrive_content_set` = '1' (primary "this post has Thrive content" flag).
  * Added `tcb_editor_enabled` = 'enabled' (modern TCB2 editor flag — replaces the legacy `tve_editor_enabled`).
  * Added `thrive_element_visibility`, `thrive_icon_pack`, `thrive_tcb_post_fonts` empty defaults.
  * Added boolean feature flags: `tve_has_masonry`, `tve_has_typefocus`, `tve_has_wistia_popover` (all "0").
  * Added `tve_content_before_more`, `tve_content_more_found`, `tve_global_scripts` placeholders.
  * Added `tve_save_post` timestamp.
* Removed locale-based keys (`tve_updated_post_en`, `tve_updated_post_nl_NL`, etc.) — Thrive actually uses the lang-less `tve_updated_post` key on modern single-language installs.

= 4.11.2 =
* Rewrote Thrive Architect converter based on real Thrive output samples.
* Thrive output is now CLEAN HTML (no `thrv_wrapper` divs) — matching how modern Thrive Architect (TCB2) actually stores content in `post_content`.
* Added `id="t-{timestamp}"` to every heading, matching Thrive's auto-anchor pattern for TOCs and internal linking.
* Added `style="outline: none;"` to anchor tags, matching Thrive's default link styling.
* Expanded Thrive meta key coverage to fix "upgrade this post" prompt: now sets `tve_updated_post`, `tve_updated_post_{locale}` (e.g. `tve_updated_post_nl_NL`), and `tve_updated_post_{lang_prefix}` (e.g. `tve_updated_post_nl`) based on the site's `get_locale()`.
* Added `tcb2_ready` flag, `tve_user_custom_css`, and `tve_page_events` placeholder meta for broader Thrive version compatibility.

= 4.11.1 =
* Fixed: "Call to undefined method AICC_Thrive_Converter::outer_html()" fatal error when publishing in Thrive Architect mode. Added the missing private `outer_html()` helper method.

= 4.11.0 =
* New feature: Output Format selector on the Create Content page with three options — WordPress (Classic HTML), Gutenberg Blocks (Block Editor), and Thrive Architect (compatible).
* Gutenberg output: AI content is converted into individually editable blocks (heading, paragraph, list, image, quote, code, table, separator) so the post opens in the block editor with real blocks instead of a single Classic block.
* Thrive Architect output: Each HTML element is wrapped in a `thrv_wrapper` div so Thrive Architect treats them as independent editable blocks. Thrive-specific post meta (`tve_updated_post`, `tve_updated_post_en`, `tve_editor_enabled`, `tve_custom_css`, `tve_globals`) is set automatically so Thrive recognizes the post as TCB-built.
* New class `AICC_Gutenberg_Converter` — converts HTML to Gutenberg block markup via DOMDocument parsing.
* New class `AICC_Thrive_Converter` — converts HTML to Thrive-wrapped markup and provides `set_thrive_meta()` helper.
* Best-effort disclaimer shown in the UI when Thrive mode is selected — Thrive Architect's format is proprietary and the first edit in Thrive may restructure the content. Only text, headings, lists, images, and quotes are converted; visual elements (CTAs, forms, countdowns) must be added inside Thrive.
* Output format stored in post meta `_aicc_output_format` for future reference.
* Plugin works without Thrive Architect installed — the converter just produces the expected markup and meta, which Thrive picks up if/when it is activated.

= 4.10.2 =
* Fixed: "Scan Theme Colors" button is now rendered directly in PHP via a dedicated `render_brand_colors_field()` renderer instead of being injected via JavaScript. This ensures the button always appears next to the Brand Colors field.
* Added: Color swatches now display inline next to the Brand Colors field on page load when colors are already configured, giving immediate visual feedback.
* Improved: Scan button uses the WordPress `dashicons-art` icon for better visual clarity.

= 4.10.1 =
* New feature: "Scan Theme Colors" button on the Settings page. Automatically detects your active WordPress theme's color palette and populates the Brand Colors field.
* Scans multiple sources: block theme palette (theme.json), Customizer settings, WordPress background/header colors, and CSS custom properties from the theme stylesheet.
* Two options after scanning: "Apply" (replace existing colors) or "Merge" (add new colors without removing existing ones).
* Color preview swatches shown after scan so you can verify the detected colors before applying.
* Filters out pure black (#000000) and white (#ffffff) as they are rarely useful as brand colors.
* New AJAX endpoint `aicc_scan_theme_colors` with `normalize_hex()` helper for robust color parsing (handles 3-digit, 6-digit, and prefixed hex values).

= 4.10.0 =
* MAJOR image quality upgrade: Featured images are now generated from the actual blog content instead of just the title — producing highly relevant, topic-specific images.
* New: 4 diverse image prompts. AI creates 4 distinct visual concepts (photographic, conceptual, illustrative, cinematic) so each of the 4 image options looks genuinely different.
* New: Two-step prompt generation. AI first identifies the core visual concept from the blog, then writes 4 targeted prompts — avoiding generic "stock photo" results.
* New: Blog style to visual style mapping. Image style automatically matches the blog writing style (e.g. Storytelling → cinematic, Data-Driven → design/infographic, How-To → realistic/instructional, Recipe → food photography). 13 blog styles mapped.
* New Settings: "Image Visual Style" dropdown — Auto (matches blog style), Realistic, Design, General, or Fiction. Auto is recommended.
* New Settings: "Brand Colors" — comma-separated hex codes (e.g. #1a73e8, #34a853). Ideogram uses these as a native color palette; OpenAI embeds them in the prompt.
* New Settings: "Negative Prompt" — describe what to avoid (e.g. clipart, stock photo, blurry). Ideogram uses this as a native negative prompt; OpenAI embeds it as exclusions.
* Expanded prompt budget from 400 to 800 characters per prompt for richer, more detailed image descriptions.
* Ideogram integration now uses style_type mapping, native negative_prompt field, and color_palette API for higher quality results.
* OpenAI DALL-E 3 now embeds negative prompts and brand colors directly in the prompt text.
* Image regeneration now also uses the post content and blog style for context-aware prompts.
* All 4 individual prompts stored in post meta `_aicc_image_prompts` for transparency.
* Progress log now shows each of the 4 prompt approaches (photographic, conceptual, illustrative, cinematic) during generation.

= 4.9.2 =
* Fixed: Create Content page now shows the active image provider name (e.g. "Ideogram" or "OpenAI (DALL-E 3)") in the Featured Image description instead of hardcoded "OpenAI DALL-E 3".
* Fixed: Featured image checkbox is now enabled/disabled based on the configured image provider's API key, not just the OpenAI key.
* Fixed: Missing API key warning now points to the correct Settings section ("Featured Image Provider") and names the active provider.

= 4.9.1 =
* Removed Canva as an image provider option. The Featured Image Provider dropdown now offers OpenAI (DALL-E 3) and Ideogram only.
* Removed Canva API key field, getter method, validation button, and status row from Settings.

= 4.9.0 =
* New feature: Multi-provider featured image generation. Choose between OpenAI (DALL-E 3) or Ideogram for AI-generated featured images.
* New Settings section: "Featured Image Provider" with a provider dropdown and separate API key fields for each service.
* Ideogram integration uses the v3 API with 16:9 landscape images, REALISTIC style, and magic prompt enabled — producing high-quality, stylized images.
* Image generation, regeneration, and the 4-image selector all respect the configured image provider.
* New getter methods: `get_image_provider()`, `get_ideogram_api_key()`, `get_image_api_key()`, `is_image_configured()`.
* Validate API Keys card now includes an Ideogram validation button.
* Status overview on Settings page shows the active image provider and key status for all providers.
* Progress log during content creation now displays the actual image provider name instead of hardcoded "DALL-E 3".

= 4.8.2 =
* Added: Bulk delete in the LinkedIn Sharing Status table. New checkbox column on each row, a "select all" checkbox in the header, and a "Delete Selected" button in a toolbar above the table. Removes selected items from the dashboard in one request.
* New AJAX endpoint `aicc_linkedin_bulk_remove` accepts an array of post IDs and clears their LinkedIn-related meta.
* Indeterminate state on the select-all checkbox when only some rows are selected.

= 4.8.1 =
* Added: "View Scheduled" button now appears next to "View Post" on the Create Content result view whenever LinkedIn sharing was enabled — regardless of whether the post was scheduled. Lets users quickly jump to the LinkedIn Sharing Status dashboard to monitor or manage the share.

= 4.8.0 =
* Fixed: Featured image now ALWAYS appears in LinkedIn posts. Previously the plugin used an ARTICLE share that relied on LinkedIn's link preview scraper to fetch og:image — which often failed due to caching or missing OG tags. Now when a post has a featured image, the plugin uploads it directly to LinkedIn via the assets API and shares as an IMAGE post with the article URL appended to the commentary text.
* Falls back to ARTICLE share automatically when no featured image is set on the post.
* Added: Delete (trash) button on each row of the LinkedIn Sharing Status dashboard. Removes the post from the dashboard list by clearing LinkedIn-related meta. Does NOT delete the WordPress post or any existing LinkedIn share — purely a dashboard cleanup action.
* New AJAX endpoint `aicc_linkedin_remove_from_dashboard`.
* New helper `AICC_LinkedIn::upload_image()` — handles the two-step LinkedIn assets upload flow (register + binary PUT).

= 4.7.0 =
* New feature: 4 featured image options. When "Generate AI featured image" is enabled, the plugin now generates 4 DALL-E 3 image variations instead of 1. The first option is auto-selected as the featured image, and you can click any other thumbnail to swap it instantly.
* New feature: "Regenerate 4 New Images" button on the result view. Click to make the AI generate 4 fresh image options (replaces the existing ones, takes 1-2 minutes).
* New feature: Auto-scroll the page to the progress log when content creation starts, and auto-follow the log as new entries appear so you always see the latest update without manual scrolling.
* New AJAX endpoint `aicc_select_featured_image` — switches the post's featured image to a chosen option from the 4-image set.
* New AJAX endpoint `aicc_regenerate_featured_images` — generates 4 new image options, attaches the first as featured, returns all 4 URLs.
* New helper `AICC_Generator::generate_images()` — generates N images sequentially via DALL-E 3.
* Image options are stored in post meta `_aicc_image_options` (URLs) and `_aicc_image_selected` (index).
* When switching selection, the previous AICC-generated featured image is deleted from the media library to keep things tidy.

= 4.6.0 =
* New feature: AI-generated featured image. Enable "Generate AI featured image" on the Create Content page and the plugin will:
  - Use AI (the active provider) to write a smart DALL-E prompt based on the blog title and metadata
  - Call OpenAI DALL-E 3 to generate a 1792x1024 landscape image
  - Download and attach it to the post as the featured image
  - LinkedIn automatically picks up the image via Open Graph tags when sharing
* Image is shown in the result view alongside the prompt used.
* Image is stored in WP media library and can be reused/edited like any other image.
* Image attachment is tagged with `_aicc_generated_image` post meta for tracking.
* Image prompt is stored as post meta `_aicc_image_prompt` on the post.
* Requires an OpenAI API key (works whether your content provider is Claude or OpenAI). The toggle is disabled and shows guidance if no OpenAI key is configured.
* Failure during image generation does NOT block content publishing — content still publishes without an image.
* New: `AICC_Generator::generate_image_prompt()` and `AICC_Generator::generate_image()` methods.
* New: `AICC_Publisher::attach_image_from_url()` helper.

= 4.5.1 =
* Added: Edit and Regenerate buttons on the LinkedIn Post Preview that appears on the Create Content result view (immediately after generating content) — same workflow as the Scheduled Content page.
* Edit mode opens an inline textarea with live character count (capped at 2900). Save persists to post meta `_aicc_linkedin_commentary`.
* Regenerate re-runs the AI summary using the original blog content and stored blog style.
* Both views (result view + Scheduled Content page) read/write the same post meta, so edits persist across pages.

= 4.5.0 =
* Added: Edit and Regenerate buttons on the LinkedIn Post Preview in the Scheduled Content page.
* Edit: Inline textarea editor with live character count (capped at 2900 chars). Save persists to post meta `_aicc_linkedin_commentary`.
* Regenerate: Re-runs the AI to create a fresh LinkedIn post summary using the original blog content, metadata, and stored blog style.
* Stored `_aicc_blog_style` post meta when content is created so regeneration uses the same style.
* New AJAX endpoints: `aicc_linkedin_save_commentary` and `aicc_linkedin_regenerate_commentary`.
* Regenerated commentary is saved to post meta and immediately reflected in the preview.

= 4.4.0 =
* New feature: AI-generated LinkedIn post summaries. When LinkedIn sharing is enabled, the plugin now generates a dedicated, well-written LinkedIn post (900-1300 characters) that summarizes the blog article in the style of a native LinkedIn post with hook, bullet points, emojis, and hashtags — no longer just the short meta description.
* New method: `AICC_Generator::generate_linkedin_post()` creates LinkedIn commentary using the same AI model as content generation, with a style-aware prompt (matches the selected blog style).
* The generated LinkedIn post is stored as post meta `_aicc_linkedin_commentary` and used automatically when sharing to LinkedIn.
* LinkedIn post preview shown in the result view after content creation so users can see exactly what will be posted.
* Scheduled Content page's LinkedIn Sharing Status now shows a collapsible "Show LinkedIn post preview" link for each item with the formatted post and character count.
* LinkedIn commentary can be edited directly in WordPress post meta if needed.
* Fall back to meta description if AI summary generation fails, ensuring LinkedIn sharing still works.

= 4.3.3 =
* Added: "Granted Scopes" row on Settings page now shows the exact scopes LinkedIn included in the access token. If `w_member_social` is missing, a red warning appears telling the user to reconnect.
* Added: OAuth scopes are captured from the token exchange response and stored in `aicc_linkedin_scopes` option for diagnostics.
* Improved: LinkedIn error messages now include granted scopes and a "disconnect/reconnect to refresh" hint when scope-related errors occur.
* Fixed: Cleaned up error handling around the /v2/ugcPosts call.

= 4.3.2 =
* Fixed: LinkedIn "HTTP 403: Not enough permissions to access ugcPosts.CREATE.NO_VERSION" error. Added the required `LinkedIn-Version: 202603` header to the `/v2/ugcPosts` API call. LinkedIn now requires versioning on all API endpoints, even the legacy v2 routes.

= 4.3.1 =
* Fixed: LinkedIn "HTTP 403: Not enough permissions to access partnerApiPostsExternal" error. Switched from the newer `/rest/posts` endpoint (which requires Partner API approval) to the `/v2/ugcPosts` endpoint that works with the self-serve "Share on LinkedIn" product and `w_member_social` scope.
* Updated: Request body format changed to match the UGC Posts API schema with `specificContent.com.linkedin.ugc.ShareContent` structure and `ARTICLE` media category for link shares.

= 4.3.0 =
* Added: New "LinkedIn Sharing Status" section on the Scheduled Content page showing all published AICC posts with LinkedIn sharing enabled and their status (Shared, Failed with error message, or Not shared yet).
* Added: Manual "Share Now" / "Retry" / "Re-share" button next to each post to trigger or retry LinkedIn sharing on demand.
* Added: New AJAX endpoint `aicc_linkedin_share_now` for manual LinkedIn sharing with full error reporting.
* Added: Link to LinkedIn profile feed to verify posts appeared.
* Added: `get_published_with_linkedin_status()` method returns published posts with their sharing state.
* Fixed: LinkedIn errors are now visible to the user instead of being silently stored in post meta.

= 4.2.0 =
* Fixed: Drafts WITHOUT a scheduled time now appear in the Scheduled Content review queue. Previously only scheduled drafts were shown — regular drafts created with LinkedIn enabled (or any AICC-generated draft) are now listed for human review and approval.
* Improved: `approve_scheduled()` now publishes immediately if no schedule is set, instead of requiring a future date. Drafts without a schedule show "Publish on approval" in the Scheduled For column.
* Added: LinkedIn icon (blue) shown next to posts in the review queue that have LinkedIn sharing enabled — visual indicator that the post will be cross-posted when approved/published.
* Improved: Query now uses `_aicc_generated` meta key instead of `_aicc_scheduled_publish_at` to find all AI-generated draft posts.

= 4.1.3 =
* Added: New Step in setup guide explaining the mandatory app verification step (must be done before products can be added).
* Added: New Step in setup guide for verifying that the OAuth 2.0 scopes appear in the Auth tab — with the exact 4 scopes that must be listed (openid, profile, email, w_member_social).
* Added: Tier names shown next to each required product (Standard Tier for Sign In, Default Tier for Share on LinkedIn).
* Clarified: Setup guide now warns explicitly that "No permissions added" in the Auth tab means products are not correctly added.

= 4.1.2 =
* Added: Diagnostic test buttons on the LinkedIn settings page to identify which LinkedIn product is missing when getting "permission scope is not valid" errors. Tests Sign In and Share scopes individually.
* Added: get_auth_url() now accepts an optional scope parameter for diagnostic testing.

= 4.1.1 =
* Added: Setup guide now explains the LinkedIn "Company Page required" gotcha — apps must be linked to a Company Page even if posting only to a personal profile. Includes a direct link to create a placeholder Company Page in 30 seconds.
* Added: New Step 1 in the setup guide for creating a placeholder Company Page (only needed if you don't have one).
* Clarified: Posts always go to your personal profile, never to the Company Page — the Company Page is only an administrative link.

= 4.1.0 =
* Improved: LinkedIn Setup Guide on the Settings page now shows clear, numbered step-by-step instructions for creating a LinkedIn App, enabling required products, adding the redirect URL, and connecting your account.
* Added: Direct link to LinkedIn Developer Portal "Create app" page in the setup guide.
* Added: Click-to-select redirect URL display box for easy copying.
* Added: Inline guidance about what each scope does and which LinkedIn product grants it.
* Improved: Connect button only appears after Client ID and Secret are saved, with clear messaging when they are missing.

= 4.0.3 =
* Updated: LinkedIn API version header from `202401` to `202603` to use the latest Community Management API. Endpoint, scopes, and request format are unchanged.

= 4.0.2 =
* Fixed: LinkedIn OAuth "permission scope is not valid" error. Built authorization URL manually with proper `%20` scope separators instead of using `add_query_arg()` which encoded spaces as `+`.

= 4.0.1 =
* Fixed: LinkedIn OAuth redirect_uri mismatch error caused by double URL-encoding. `add_query_arg()` already encodes values, so the extra `rawurlencode()` wrapper was producing an incorrect redirect URI.

= 4.0.0 =
* MAJOR: LinkedIn integration — automatically share published content to your LinkedIn profile.
* New Settings section: LinkedIn Integration with Client ID and Client Secret fields.
* OAuth 2.0 flow: "Connect LinkedIn Account" button on Settings page redirects to LinkedIn for authorization.
* Shows connected account name, email, and profile picture on Settings page after connecting.
* "Disconnect LinkedIn" button to revoke access.
* Redirect URI displayed in Settings for easy LinkedIn App configuration.
* New toggle on Create Content page: "Post to LinkedIn when published" (only shown when LinkedIn is connected).
* LinkedIn sharing triggers automatically when a post transitions to 'publish' status — works with immediate publish, scheduled publish, and human-in-the-loop approval.
* Shares post as an article link with SEO title, meta description, and permalink.
* Token auto-refresh: access tokens (60-day) are refreshed automatically using the refresh token (365-day).
* LinkedIn sharing status shown in the progress log and result view after creation.
* Post meta `_aicc_post_to_linkedin` and `_aicc_linkedin_shared` track sharing intent and completion.
* LinkedIn status added to the Settings Status overview table.
* New file: includes/class-aicc-linkedin.php — OAuth, token management, and LinkedIn Posts API integration.

= 3.5.4 =
* Added: Project Vision and Prompt are now shown in the progress log during Step 2 (metadata generation) so users can verify both were included.
* Added: Result view now displays "Project Vision" and "Prompt" rows at the top, confirming the combined instructions used to generate the content.

= 3.5.3 =
* Fixed: Plugin zip structure now includes the required `ai-content-orchestrator/` wrapper directory, matching WordPress plugin packaging standards. This resolves the "fatal error on activation" caused by incorrect zip layout.
* Fixed: Replaced mb_strimwidth() with substr() in Project Vision preview for broader PHP compatibility.

= 3.5.2 =
* Fixed: Replaced mb_strimwidth() with substr() in Project Vision preview to avoid fatal errors on servers without the mbstring PHP extension.

= 3.5.1 =
* Added "Project Vision active" info notice on the Create Content page showing a preview of the baseline instructions with an Edit link.
* Added progress log message when Project Vision is active during content generation.

= 3.5.0 =
* New feature: Project Vision — a baseline prompt field in Settings that the AI always follows before generating content.
* Define your brand voice, tone, target audience, writing rules, or any persistent instructions in a single textarea.
* Project Vision is automatically prepended to every AI request (both metadata and content generation).
* When empty, behavior is unchanged — no extra context is sent.
* Added textarea field renderer to the Settings class for reuse.

= 3.4.2 =
* Fixed: Content is now always complete — never cut off mid-sentence or mid-section.
* Increased max_tokens for content generation from 4096 to 8192.
* Added auto-continuation: when the AI hits the token limit, the plugin automatically requests a follow-up to complete the content (up to 2 continuations, 3 total calls).
* Detects truncation via Claude's stop_reason ("max_tokens") and OpenAI's finish_reason ("length").
* Progress log shows "Content was truncated — requesting continuation..." when triggered.
* Combined with repair_html(), content is always structurally complete.

= 3.4.1 =
* Fixed: AI-generated HTML with broken/malformed tags (e.g., `</ul` missing `>`) is now automatically repaired after generation.
* Added `repair_html()` post-processor that fixes broken closing tags, broken opening tags, incomplete tags cut off by token limits, and self-closing tag issues.
* Uses WordPress's `force_balance_tags()` to close any unclosed HTML elements.

= 3.4.0 =
* New: Hover preview for blog styles on the Create Content page. Hover the eye icon next to the Blog Style dropdown to see a live layout preview of the selected style.
* Preview shows the HTML structure, headings, lists, tables, and formatting specific to each of the 13 styles.
* Preview panel includes a link to the full Style Examples page.
* Short preview examples added to AICC_Styles class for reuse across pages.

= 3.3.0 =
* New blog style: Recipe (SEO-optimized for Google rich snippets and Recipe carousel).
* New admin page: "Style Examples" — preview the output format of all 13 blog styles before using them. Accessible from AI Content > Style Examples menu.
* Each style has a full HTML example showing headings, structure, lists, tables, blockquotes, and formatting specific to that format.
* Expandable/collapsible examples with style description and word count target.

= 3.2.3 =
* Previous: Recipe style added. SEO-optimized recipe format targeting Google rich snippets, Recipe carousel, and "how to make" queries. Includes structured sections for ingredients, instructions, nutrition, storage, FAQ, and a recipe card summary.

= 3.2.2 =
* Replaced single-request PDF upload with chunked upload (1MB per chunk). Now works on ANY hosting regardless of upload_max_filesize or post_max_size settings.
* Max file size raised to 100MB. Client-side and server-side validation.
* Progress percentage shown during upload ("Uploading... 45%").
* Chunk failure shows which chunk failed for easier debugging.
* The old single-request upload handler is kept as fallback.

= 3.2.1 =
* Fixed PDF upload failures on servers with low upload limits. Now detects and reports the actual server limit (upload_max_filesize / post_max_size).
* Shows server max upload size in the UI next to the Upload PDF button.
* Specific error messages for all PHP upload error codes (UPLOAD_ERR_INI_SIZE, partial upload, no temp dir, etc.).
* Attempts to increase PHP limits with @ini_set before processing.
* Plugin limit raised from 20MB to 50MB (actual limit is the lower of plugin and server).

= 3.2.0 =
* New feature: PDF source uploads. Upload PDFs to use as context for AI content generation.
* PDF text extraction in pure PHP — no external libraries or system commands required.
* PDF library with persistent storage — uploaded PDFs are saved for future reuse.
* Check/uncheck saved PDFs to select which ones to use as source for each content creation.
* Delete PDFs from the library when no longer needed.
* Newly uploaded PDFs are auto-checked for immediate use.
* PDF text preview shown in the library (first 150 characters).
* PDFs and URL scanning can be used together — context is merged.
* New files: class-aicc-pdf-extractor.php, class-aicc-pdf-library.php.
* Storage: wp-content/uploads/aicc-pdfs/ with index.php protection.

= 3.1.0 =
* New feature: Blog Style selector with 12 SEO-optimized content formats.
* Available styles: Standard, How-To Guide, Listicle, Ultimate Guide, Comparison, Case Study, Problem-Solution, Beginner's Guide, Data-Driven, Storytelling, Opinion/Thought Leadership, Checklist.
* Each style has a unique AI system prompt tailored to its format, structure, tone, and word count target.
* Style selector with description shown on the Create Content page (hidden for pages).
* New includes/class-aicc-styles.php with all style definitions.

= 3.0.1 =
* Fixed: Claude API HTTP 529 (Overloaded) is now retried automatically instead of failing immediately. Up to 5 retries with exponential backoff (30s, 60s, 90s, 120s).
* Also retries OpenAI HTTP 503 (Service Unavailable) with same logic.
* Increased MAX_RETRIES from 3 to 5 for better resilience during API load spikes.
* Progress log now shows retry status with attempt count.

= 3.0.0 =
* BREAKING: Refactored content creation from a single long-running AJAX request into a 4-step multi-request pipeline. This eliminates 504 Gateway Timeout errors on hosting with strict timeouts (e.g. 60s nginx).
* Step 1: Scan website(s) — ~30-60s
* Step 2: Generate SEO metadata — ~10-30s (single AI call)
* Step 3: Generate HTML content — ~15-60s (single AI call)
* Step 4: Publish to WordPress — <5s
* Each step completes within a typical server timeout and stores intermediate data in a transient (30 min expiry).
* The progress log now shows real-time updates as each step completes.
* Generator methods (generate_metadata, generate_content) are now public for step-by-step access.
* Per-step timeout set to 120s (down from 300s single request) with @set_time_limit(120).
* Works on any hosting: shared, managed, nginx, Apache, LiteSpeed, etc.

= 2.7.7 =
* Added "View Scheduled" button to the result view after content creation. Appears to the left of "View Post" when a draft+scheduled item is created (awaiting human-in-the-loop approval) or when the item is scheduled for future publication. Links directly to the Scheduled admin page.

= 2.7.6 =
* Changed Publishes At / Scheduled For date format to European 24-hour format (Y-m-d H:i), e.g. "2026-04-05 18:00" instead of "April 5, 2026 6:00 pm".

= 2.7.5 =
* Fixed: action buttons on the Scheduled Content page no longer wrap onto multiple lines. The Actions column now uses white-space: nowrap and auto-sizes to fit all buttons on one row.

= 2.7.4 =
* Changed cron event `aicc_catch_up_scheduled` from hourly to every minute (custom WP cron interval).
* Fixed: manual cron triggers from WP Crontrol now always execute. The rate-limit transient was blocking manual runs — split into two callbacks: rate-limited for admin_init/init hooks, unconditional for the cron event itself.
* Hardened `catch_up_overdue()` with direct DB fallback if wp_publish_post fails silently.
* Added Cron Status panel on the Scheduled page showing next run time, last run time, counts, and per-post details.
* Added "Run catch-up now" button on the Scheduled page for instant manual trigger.
* Automatically migrates existing hourly cron events to the new 1-minute interval on upgrade.

= 2.7.3 =
* Fixed: drafts with scheduled time now correctly appear in the "Awaiting Human Review" section. The needs_review flag is now determined by post_status directly (any draft with a scheduled time is awaiting review) rather than relying on a separate meta flag that could get out of sync.
* Full pipeline confirmed: Draft (AI-generated) → Human Approval → Scheduled (future status) → Auto-Published at scheduled time.

= 2.7.2 =
* Fixed: auto-publish catch-up now runs on every admin and frontend request (rate-limited to once per minute), not just the Scheduled page. Approved items will publish automatically as soon as the scheduled time passes — no manual intervention needed beyond the initial human-in-the-loop approval.
* Added backup hourly WP cron event as additional failsafe.
* Registers/unregisters the cron event on activation/deactivation.

= 2.7.1 =
* Fixed: scheduled posts now publish correctly when their time arrives, even on sites with low traffic or disabled WP cron.
* Added auto catch-up: visiting the Scheduled page now publishes any overdue items immediately.
* Added "Publish Now" button for manual override on scheduled items.
* Added warning banner when DISABLE_WP_CRON is set on the site.

= 2.7.0 =
* New feature: Save URLs for quick reuse. Check "Save URL for next time" to store the scanned URL.
* Saved URLs appear as clickable chips on the Create Content page.
* Click a chip to populate the URL field instantly.
* Click the × on any chip to remove it from the saved list.
* Supports multiple URLs per chip selection (appends with commas).

= 2.6.1 =
* Fixed: approved drafts are now correctly scheduled for the future instead of being published immediately. WordPress was silently clearing post_date because edit_date=true was missing.
* Fixed: Scheduled counter now correctly updates after approval.
* Added status verification after wp_update_post to catch WordPress auto-conversions.
* Explicitly registers publish_future_post cron event on approval.

= 2.6.0 =
* New feature: Scheduling mode for content creation.
* Add option on the Create Content page to schedule publication with a date and time.
* New "Scheduled" admin page with a human-in-the-loop review queue.
* Draft + Schedule: item goes to review queue, awaits manual approval before scheduling.
* Publish + Schedule: item is scheduled directly via WordPress 'future' status (no review needed).
* Review queue supports Approve, Reschedule, and Delete actions.
* Menu badge shows pending review count.
* Uses WordPress native cron (publish_future_post) — no custom cron setup required.

= 2.5.0 =
* Fixed: result view now shows the actual selected category names instead of empty.

= 2.4.0 =
* Fixed: when user selects categories, AI-suggested categories are now fully discarded.
* Fixed jQuery category array serialization for reliable PHP parsing.

= 2.3.0 =
* Added PHP shutdown handler to catch fatal errors and return them as JSON.
* Shows actual PHP error message, file, line, memory_limit, and max_execution_time.
* Added @set_time_limit(300) and @ini_set memory_limit to 256M.
* Logs PHP environment info at start of every content generation.
* Prevents WordPress critical error page from hiding the real error.

= 2.2.0 =
* Added debug output in the progress log for OpenAI errors.
* Shows HTTP status, server message, and raw response on AJAX failures.
* Added verbose logging to OpenAI API calls (model, attempt, response size, errors).
* Debug info includes exception class, file, line, provider, model, and PHP version.
* Added JSON encoding validation before sending API requests.

= 2.1.0 =
* Fixed OpenAI Internal Server Error caused by PHP execution timeout.
* Fixed uncaught PHP errors (catch Throwable instead of Exception).
* Fixed unsafe array access in OpenAI error handling.
* Reverted to standard max_tokens parameter for all OpenAI GPT models.
* Extended PHP execution time to 300 seconds for long-running operations.
* Improved OpenAI error messages — now shows actual API error details.

= 2.0.0 =
* Added OpenAI GPT support alongside Claude (Anthropic).
* Added AI provider selector in Settings.
* Added WordPress category selection on the content creation form.
* Added status bar showing active provider, model, and Yoast status.
* Improved website scanning step with prominent URL input.
* Improved draft/publish toggle with descriptions.
* Improved blog/page toggle with word count targets.
* Added status overview table on Settings page.
* AI now receives existing WordPress categories to pick from.
* Improved Yoast SEO integration with status detection.
* UI improvements throughout.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 5.0.0 =
Major release: Dashboard, Automatic Internal Linking, Bulk Create, Content Repurposing (Email/X/Instagram/Pinterest), Competitor Gap Analysis, Refresh Content, tabbed Settings. Complete navigation restructure.

= 4.20.0 =
All settings rewritten in plain language — clear, non-technical descriptions that any WordPress user can understand.

= 4.19.4 =
CTA button now appears in two places: after the TOC and at the bottom. Removed CTA Heading Text setting.

= 4.19.3 =
CTA heading now inserted right after the TOC (before content), matching the reference post layout. Button stays at the bottom.

= 4.19.2 =
Fixes CTA heading missing from TOC — force-enables H3 scanning in the injected TOC widget.

= 4.19.1 =
Fixes missing TOC (was incorrectly removed in 4.19.0) and eliminates the __CONFIG__ JSON text leak. TOC + CTA heading now work together.

= 4.19.0 =
New CTA Heading Text setting — adds "Vraag jouw gratis chart aan" (or any custom text) as a real H3 before the CTA button, making it appear automatically in Thrive's TOC. Removed TOC template injection (Thrive handles it natively).

= 4.18.0 =
Edit overlay text after generation — customize both lines and regenerate the featured image instantly. Also removes untested Gutenberg output format.

= 4.17.2 =
Adds AVIF base image support for the title overlay feature.

= 4.17.1 =
Fixes media library buttons (Select Image, Upload Font) not opening on Settings page. Required update for 4.17.0 users.

= 4.17.0 =
New Default Featured Image with Title Overlay — select a branded base image, and the plugin composites the blog title onto it for every post. No AI generation needed.

= 4.16.0 =
New TOC Block dropdown — pick a Thrive TOC template from your library and the plugin auto-inserts it after the intro paragraph on every generated post.

= 4.15.5 =
Fixes CTA button color mismatch — template CSS is now injected into the post. Recommended update for Thrive users.

= 4.15.4 =
Thrive output is now byte-identical to Thrive Architect's normalized format. Adds `class=""` to `<ul>`/`<ol>` elements.

= 4.15.3 =
Fixes AI content rendering as one giant Thrive block and the duplicate Table of Contents issue. Recommended update.

= 4.15.2 =
Thrive user templates now show their real names in the CTA Block dropdown and are rendered correctly as HTML (not as empty references).

= 4.15.1 =
Fixes missing Thrive Templates in the CTA Block dropdown — now queries the `tve_user_template` post type used by Thrive's Templates library.

= 4.15.0 =
Simplified Thrive mode — removed the template system entirely. CTA dropdown now shows Thrive Symbols, Saved Sections, and Content Templates.

= 4.14.1 =
New "Import & Auto-Build Template" button — enter a post ID of an existing Thrive-built post and the plugin auto-generates the template with all placeholders in the right places.

= 4.14.0 =
New Thrive template placeholders: {AI_CONTENT_intro} (everything before the first H2) and {CTA} (short alias for {CTA_SYMBOL}).

= 4.13.0 =
New Thrive Symbol CTA support — use a Thrive Symbol for the CTA block and updates in Thrive's library automatically propagate to all AI-generated posts.

= 4.12.0 =
New Thrive Post Template setting — paste your Thrive template once (with TOC and CTA) and every generated Thrive post uses it with auto-generated TOC entries.

= 4.11.3 =
Fixes "Upgrade this post to Thrive Architect" dialog by setting the correct modern meta keys (tcb_editor_enabled, thrive_content_set, etc.) based on real Thrive install analysis.

= 4.11.2 =
Thrive Architect output rewritten to match real Thrive format (clean HTML, heading anchor IDs) with locale-aware meta keys to fix "upgrade this post" prompt.

= 4.11.1 =
Fixes fatal error when publishing content in Thrive Architect output mode. Recommended update for anyone using Thrive mode.

= 4.11.0 =
New Output Format selector — choose WordPress, Gutenberg Blocks, or Thrive Architect (compatible) when creating content. Thrive mode works without Thrive installed.

= 4.10.2 =
"Scan Theme Colors" button now rendered server-side for reliable display, plus inline color swatches on page load.

= 4.10.1 =
"Scan Theme Colors" button auto-detects your WordPress theme's color palette for brand-consistent featured images.

= 4.10.0 =
Major image quality upgrade — images now generated from blog content with 4 diverse visual concepts, blog style matching, brand colors, and negative prompts.

= 4.9.2 =
Create Content page now shows the selected image provider name and checks the correct API key for enabling the featured image toggle.

= 4.9.1 =
Removed Canva image provider option (not yet available). OpenAI and Ideogram remain as featured image providers.

= 4.9.0 =
Multi-provider featured images — choose OpenAI DALL-E 3 or Ideogram for AI-generated hero images. Configure in Settings.

= 4.8.2 =
Bulk delete on the LinkedIn Sharing Status dashboard with checkboxes and Delete Selected button.

= 4.8.1 =
"View Scheduled" button now appears after content creation when LinkedIn sharing is enabled.

= 4.8.0 =
LinkedIn shares now always include the featured image (uploaded directly via assets API). Plus delete button on the LinkedIn dashboard.

= 4.7.0 =
4 featured image options to choose from + Regenerate button + auto-scroll progress log.

= 4.6.0 =
AI-generated featured image via OpenAI DALL-E 3 — automatically creates and attaches a hero image. LinkedIn picks it up via Open Graph.

= 4.5.1 =
Edit and Regenerate buttons now also appear on the LinkedIn Post Preview right after content creation, not only on the Scheduled Content page.

= 4.5.0 =
Edit and Regenerate buttons on the LinkedIn Post Preview — refine the AI summary or create a fresh one with one click.

= 4.4.0 =
AI-generated LinkedIn post summaries — well-written 900-1300 char posts in the blog's style, not just the short meta description.

= 4.3.3 =
Shows granted OAuth scopes in Settings and warns if w_member_social is missing.

= 4.3.2 =
Adds required LinkedIn-Version header to /v2/ugcPosts requests to fix NO_VERSION 403 errors.

= 4.3.1 =
Fixes LinkedIn HTTP 403 partnerApiPostsExternal error by switching to the self-serve /v2/ugcPosts endpoint.

= 4.3.0 =
LinkedIn sharing status dashboard with manual Share Now / Retry buttons and error visibility.

= 4.2.0 =
All AI-generated drafts now appear in the Scheduled Content review queue. LinkedIn icon shows on posts with sharing enabled.

= 4.1.3 =
Setup guide now covers app verification step and OAuth scope verification with exact required scopes.

= 4.1.2 =
Adds diagnostic test buttons to identify which LinkedIn product is missing when scope errors occur.

= 4.1.1 =
Setup guide now explains the LinkedIn Company Page requirement and how to create a placeholder one.

= 4.1.0 =
Adds clear step-by-step LinkedIn setup guide on the Settings page.

= 4.0.3 =
Updates LinkedIn API version header to the latest 202603.

= 4.0.2 =
Fixes LinkedIn OAuth scope validation error.

= 4.0.1 =
Fixes LinkedIn OAuth redirect_uri mismatch error.

= 4.0.0 =
Major release: LinkedIn integration — share AI-generated content to your LinkedIn profile automatically when published.

= 3.5.4 =
Project Vision and Prompt are now visible in the progress log and result view for verification.

= 3.5.3 =
Fixes fatal error on activation caused by incorrect zip packaging structure.

= 3.5.2 =
Fixes potential fatal error on activation related to mbstring dependency.

= 3.5.1 =
Shows Project Vision status on Create Content page and in progress log during generation.

= 3.5.0 =
Adds Project Vision — set baseline AI instructions once in Settings, applied to every content generation request.

= 2.7.4 =
Cron now runs every minute, manual triggers work, and the Scheduled page shows cron debug info.

= 2.7.3 =
Fixes draft items not appearing in the Awaiting Human Review section.

= 2.7.2 =
Scheduled posts now publish fully automatically at their scheduled time, no manual action required.

= 2.7.1 =
Critical fix for scheduled posts that were stuck at "Scheduled" status.

= 2.7.0 =
Adds saved URLs for quick reuse on the Create Content page.

= 2.6.1 =
Critical fix: approved scheduled drafts no longer publish immediately.

= 2.6.0 =
Adds scheduling mode with human-in-the-loop review queue for AI-generated content.

= 2.5.0 =
Stable release. Shows selected category names in the result view after content creation.

= 2.4.0 =
Fixes category assignment when user selects specific categories — AI suggestions no longer override.

= 2.3.0 =
Catches PHP fatal errors and shows the actual error in the progress log instead of a generic 500 page.

= 2.2.0 =
Added debug output for OpenAI errors and API key validation buttons.

= 2.1.0 =
Fixes OpenAI integration errors. Recommended update for all OpenAI users.

= 2.0.0 =
Major update: adds OpenAI support, category selection, improved UI, and better Yoast integration.
