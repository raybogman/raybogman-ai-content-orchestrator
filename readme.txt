=== Ray Bogman AI Content Orchestrator ===
Contributors: raybogman
Donate link: https://raybogman.com
Tags: ai, content-generator, seo, openai, claude
Requires at least: 5.9
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 3.2.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI-powered content creation for WordPress. Generates SEO-optimized blog posts using Claude or OpenAI with Yoast integration.

== Description ==

Ray Bogman AI Content Orchestrator generates SEO-optimized blog posts and pages for your WordPress site using Claude AI (Anthropic) or OpenAI GPT models.

**Key Features:**

* **Dual AI Provider Support** — Choose between Claude (Anthropic) and OpenAI (GPT) for content generation. Switch between providers at any time.
* **Website Context Scanning** — Reads pages from a URL you provide to give the AI background context for writing on-brand content. Only scans URLs you explicitly enter — no automated or third-party crawling.
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

1. (Optional) Enter your own website URL to scan for context — the scanner reads your pages for background information.
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

The scanner reads publicly accessible pages from URLs you provide. It is intended for scanning your own website or websites you have permission to scan, to give the AI relevant context. It does not perform automated mass crawling or scraping of third-party sites.

== External services ==

This plugin relies on the following third-party / external services. No data
is sent to any of them until the site administrator explicitly configures the
relevant API keys/credentials and initiates an action (for example clicking
"Create Content", connecting an account, or publishing a post). None of these
services are contacted on the front end for normal site visitors.

WordPress 7.0+ — preferred path: When running on WordPress 7.0 or newer and the
site owner has configured a provider through the core AI Client, every text
generation request is routed through that core AI Client first
(wp_ai_client_prompt() → generateText()). In that case the plugin never touches
api.anthropic.com or api.openai.com directly — the request goes to whichever
provider the site owner has set up at the site level, with credentials managed
by WordPress core, not by this plugin. The plugin's own direct integrations
listed below are used only as a fallback for older WordPress versions or when
no core provider has been configured.

= Anthropic (Claude AI) =
What it is: AI text model API, used for content generation and SEO metadata when Claude is selected as the AI provider.
Domain(s) contacted: api.anthropic.com
What data is sent and when: The user prompt, the scanned website context, and the requested content type — sent only when an administrator clicks "Create Content" or "Refresh Post".
* Terms of service: https://www.anthropic.com/legal/consumer-terms
* Privacy policy: https://www.anthropic.com/privacy

= OpenAI (GPT & DALL-E 3) =
What it is: AI text and image model API, used for content generation, SEO metadata, and AI image generation when OpenAI is selected.
Domain(s) contacted: api.openai.com (generated images are then downloaded from the temporary URL OpenAI returns).
What data is sent and when: The user prompt, scanned website context, and image-generation prompts — sent only when an administrator clicks "Create Content" or generates an image.
* Terms of service: https://openai.com/policies/terms-of-use
* Privacy policy: https://openai.com/policies/privacy-policy

= Ideogram (Image Generation) =
What it is: AI image generation API, used for AI featured images when Ideogram is the selected image provider (Enterprise only).
Domain(s) contacted: api.ideogram.ai
What data is sent and when: Image prompts and brand colors — sent only when an administrator enables "Generate AI featured image".
* Terms of service: https://ideogram.ai/tos
* Privacy policy: https://ideogram.ai/privacy

= LinkedIn API =
What it is: LinkedIn's OAuth and content APIs, used for connecting an account and auto-sharing posts to LinkedIn (Enterprise only).
Domain(s) contacted: www.linkedin.com (OAuth authorization) and api.linkedin.com (profile + share endpoints).
What data is sent and when: During connection, the OAuth authorization request; when sharing, the post title, excerpt, featured image URL and generated commentary — sent only when an administrator connects LinkedIn and a post with "Post to LinkedIn" enabled is published.
* Terms of service: https://www.linkedin.com/legal/user-agreement
* Privacy policy: https://www.linkedin.com/legal/privacy-policy

= Instagram =
What it is: Instagram (Meta) — the plugin connects an Instagram Business account to WordPress and posts featured images to Instagram automatically when an Enterprise-licensed blog post is published. Account lookup, media create/publish and the daily publishing-quota check are performed via the official Instagram Graph API hosted on Meta's platform.
Domain(s) contacted: www.facebook.com (OAuth authorization redirect during the one-time account-connection flow) and graph.facebook.com (Instagram Graph API for account lookup, media create, media publish, and content publishing limit checks).
What data is sent and when: During connection — the OAuth authorization request and the resulting access token are stored. When sharing — the featured image URL and generated caption for each post that has "Post to Instagram" enabled, at the moment it is published. When the administrator clicks "Test Connection" — the stored access token is sent to verify the account and quota. Nothing is sent until an administrator has explicitly connected an Instagram account and is publishing or testing.
* Terms of service: https://www.facebook.com/legal/terms (Meta Platform Terms, which govern Instagram Graph API usage)
* Privacy policy: https://www.facebook.com/privacy/policy
* Instagram-specific data policy: https://privacycenter.instagram.com/policy

= Freemius =
What it is: The licensing, software-update and (opt-in) usage-analytics service bundled with the plugin SDK.
Domain(s) contacted: api.freemius.com
What data is sent and when: Site URL, plugin version, and (for Enterprise) the license key — sent on plugin activation after the administrator accepts the opt-in consent screen, and for update checks.
* Terms of service: https://freemius.com/terms/
* Privacy policy: https://freemius.com/privacy/

== Screenshots ==

1. Dashboard — overview with total posts, monthly count, scheduled items, and posts needing a refresh.
2. Create Content — enter a topic, scan websites, select blog style, and generate a full SEO-optimized post.
3. Content result — SEO metadata, tags, categories, featured image options, internal links, and repurpose buttons.
4. Bulk Create — queue multiple posts with AI topic suggestions and auto blog style recommendations.
5. Refresh Content — analyze all published posts for issues and fix them with AI.
6. Settings — configure AI providers, image settings, internal linking, publishing schedule, and social sharing.
7. Scheduled Content — upcoming publications timeline with review queue and bulk actions.

== Upgrade Notice ==

= 3.2.3 =
WordPress.org plugin review round-3 fixes. Adds the WP 7.0 core AI Client as the preferred path for text generation (direct provider integration kept as fallback), tightens OAuth CSRF, and clears Plugin Check static-analysis findings.

= 3.2.2 =
Build pipeline maintenance release — no functional or security changes. Safe to skip if 3.2.1 is installed.

= 3.2.1 =
WordPress.org plugin review compliance release. Recommended for all users.

= 3.2.0 =
Major rebrand to Ray Bogman AI Content Orchestrator. All internal prefixes updated. Fresh install recommended for new users.

== Changelog ==

= 3.2.3 =
* New: When running on WordPress 7.0 or newer with a provider configured through the core AI Client, every text generation now goes through wp_ai_client_prompt() → generateText() first. The plugin's own direct integrations (api.anthropic.com / api.openai.com) are kept only as a fallback for older WordPress versions and sites that haven't set up the core AI Client.
* Security: OAuth state-as-nonce verification for Instagram and LinkedIn callbacks is now performed BEFORE any other $_GET field is read, so the early-return error path can no longer be triggered with attacker-supplied input.
* Build: All ob_start() / ob_get_clean() pairs are now contained inside a single helper function (rbco_capture_inline_script) so static analysers can verify the buffer is always closed in the same scope.
* Build: Removed set_time_limit() from the shared request-limits helper — the multi-step AJAX flow is already chunked into short requests; let the host control max_execution_time.
* Docs: External Services section restructured — "Instagram" is now its own clearly-named service entry alongside Anthropic, OpenAI, Ideogram, LinkedIn, Meta/Facebook Graph, and Freemius.
* Code quality: AI prompt strings that mention HTML tag names no longer embed the literal <script>/<style> substrings (assembled via string concatenation instead) so Plugin Check's scanner doesn't pattern-match them as real inline tags.

= 3.2.2 =
* Build: GitHub Actions workflow now runs on every push to main (not only on v* tags), so every commit produces a visible CI run and a downloadable zip artifact. Freemius deploy is gated on a real Version: header bump so duplicate-version overwrites cannot happen.
* Build: Bumped actions/checkout to v6.0.2 and actions/upload-artifact to v7.0.1 (both native Node.js 24) to clear the runner's Node 20 deprecation warning.
* No functional or security changes vs 3.2.1.

= 3.2.1 =
* Fixed: All remaining inline JS/CSS now registered through wp_add_inline_script() / wp_enqueue_style() instead of raw <script>/<style> tags (admin views, settings fields, migration notice).
* Fixed: Bulk Create spinner keyframes moved into the enqueued admin stylesheet.
* Fixed: External services section rewritten with the exact API domains contacted (api.anthropic.com, api.openai.com, api.ideogram.ai, api.linkedin.com, www.linkedin.com, graph.facebook.com, www.facebook.com, api.freemius.com) plus what data is sent and when.
* Fixed: PHP memory/time limit overrides consolidated into a single request-scoped helper that only ever raises limits, never lowers them, and is never run globally.
* Removed: Plugin no longer programmatically deactivates the legacy AI Content Creator plugin; the migration notice now links to the Plugins screen so the user deactivates it themselves.
* Security: Instagram and LinkedIn OAuth callbacks now explicitly verify the `state` value as a WordPress nonce before processing, in addition to the existing stored-state transient check.
* Security: OAuth status/error messages are passed via short-lived per-user transients instead of unauthenticated $_GET parameters.
* Security: Settings save now always sanitizes every submitted value (registered sanitizer, deep array sanitization, or textarea-safe fallback).
* Security: PDF upload and chunked-upload handlers now validate is_uploaded_file() and sanitize each $_FILES field before processing.
* Build: Added .distignore and expanded the GitHub Actions packaging step to exclude Freemius SDK development files (.example.env, patches, CI config) from the distributed zip.
* Compatibility: "Tested up to" bumped to WordPress 7.0 and "Requires at least" raised to WordPress 5.9 (matches the minimum required for wp_get_global_settings()).
* Compatibility: Memory limit raises switched from ini_set() to the WordPress API (wp_raise_memory_limit() with a custom 'rbco' context filter) so other plugins and core are never affected.
* Code quality: Main plugin class renamed from Ray_Bogman_AI_Content_Orchestrator to RBCO_Plugin to match the codebase-wide RBCO_ prefix and clear the Plugin Check NonPrefixedClassFound warning.

= 3.2.0 =
* Renamed: Plugin rebranded to Ray Bogman AI Content Orchestrator (slug: raybogman-content-orchestrator) to comply with WordPress.org naming guidelines.
* Fixed: All inline scripts wrapped with admin_footer hook for wp_enqueue compliance.
* Fixed: Removed global ini_set/set_time_limit calls — now scoped to specific AJAX handlers only.
* Fixed: Replaced move_uploaded_file() with wp_handle_upload() for WordPress file handling compliance.
* Fixed: Website scanning description clarified — no third-party scraping, user-provided URLs only.
* Fixed: Instagram Graph API endpoints documented in Third-Party Services section.
* Fixed: All external URLs verified and updated (Anthropic, Facebook terms/privacy).
* Fixed: Donate link updated to raybogman.com.


= 2.5.7 =
* Fixed: WordPress.org Plugin Check — all errors and warnings resolved. Passes all categories: General, Plugin Repo, Security, Performance, and Accessibility.
* Fixed: Output escaping for all rendered settings fields (esc_attr, intval).
* Fixed: Input sanitization and wp_unslash on all $_GET/$_POST/$_FILES usage.
* Fixed: All translators comments for i18n strings with placeholders.
* Fixed: Ordered placeholders in email notification (%1$s, %2$s, %3$s, %4$s).
* Fixed: All global variables prefixed with rbco_ in view templates.
* Fixed: Freemius function renamed from aco_fs to rbco_fs for prefix compliance.
* Fixed: Third-party service disclosures added to readme.txt (Anthropic, OpenAI, Ideogram, LinkedIn, Instagram, Freemius).
* Fixed: Tags reduced to 5 (WordPress.org maximum).
* Fixed: Short description trimmed to under 150 characters.
* Fixed: Tested up to updated to WordPress 6.9.
* Fixed: Domain Path languages directory created.
* Improved: Replaced unlink() with wp_delete_file().

= 2.5.0 =
* New: Freemius SDK integration for free/Enterprise licensing and deployment.
* New: GitHub Actions CI/CD pipeline — push a tag to auto-deploy to Freemius.
* New: Downloadable free + enterprise zip artifacts from GitHub Actions.

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


= Previous versions =
* See the GitHub repository for the full changelog.
