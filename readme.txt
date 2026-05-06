=== Ray Bogman Content Orchestrator ===
Contributors: raybogman
Donate link: https://raybogman.com
Tags: ai, content-generator, seo, openai, claude
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 3.1.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI-powered content creation for WordPress. Generates SEO-optimized blog posts using Claude or OpenAI with Yoast integration.

== Description ==

Ray Bogman Content Orchestrator generates SEO-optimized blog posts and pages for your WordPress site using Claude AI (Anthropic) or OpenAI GPT models.

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

== Third-Party Services ==

This plugin connects to external services to provide its functionality. No data is sent until the user explicitly configures API keys and initiates an action (e.g., clicking "Create Content").

= Anthropic (Claude AI) =
Used for content generation and SEO metadata when Claude is selected as the AI provider.
* Service: [anthropic.com](https://www.anthropic.com)
* Terms: [anthropic.com/legal/consumer-terms](https://www.anthropic.com/legal/consumer-terms)
* Privacy: [anthropic.com/privacy](https://www.anthropic.com/privacy)
* Data sent: User prompt, website scan context, content type
* When: Only when the user clicks "Create Content" or "Refresh Post"

= OpenAI (GPT & DALL-E) =
Used for content generation, SEO metadata, and AI image generation when OpenAI is selected.
* Service: [openai.com](https://openai.com)
* Terms: [openai.com/policies/terms-of-use](https://openai.com/policies/terms-of-use)
* Privacy: [openai.com/policies/privacy-policy](https://openai.com/policies/privacy-policy)
* Data sent: User prompt, website scan context, image generation prompts
* When: Only when the user clicks "Create Content" or generates images

= Ideogram (Image Generation) =
Used for AI featured image generation when Ideogram is selected as the image provider (Enterprise only).
* Service: [ideogram.ai](https://ideogram.ai)
* Terms: [ideogram.ai/tos](https://ideogram.ai/tos)
* Privacy: [ideogram.ai/privacy](https://ideogram.ai/privacy)
* Data sent: Image prompts, brand colors
* When: Only when the user enables "Generate AI featured image"

= LinkedIn API =
Used for auto-sharing blog posts to LinkedIn (Enterprise only).
* Service: [linkedin.com](https://www.linkedin.com)
* Terms: [linkedin.com/legal/user-agreement](https://www.linkedin.com/legal/user-agreement)
* Privacy: [linkedin.com/legal/privacy-policy](https://www.linkedin.com/legal/privacy-policy)
* Data sent: Post title, excerpt, featured image URL, generated commentary
* When: Only when the user enables "Post to LinkedIn" and the post is published

= Instagram Graph API (via Meta) =
Used for auto-sharing blog posts to Instagram and checking publishing quota (Enterprise only).
* Service: [developers.facebook.com](https://developers.facebook.com)
* Terms: [facebook.com/legal/terms](https://www.facebook.com/legal/terms)
* Privacy: [facebook.com/privacy/policy](https://www.facebook.com/privacy/policy)
* Data sent: Featured image URL, generated caption, access token for publishing and quota checks
* Endpoints used: media (create post), media_publish (publish post), content_publishing_limit (check daily quota)
* When: Only when the user enables "Post to Instagram" and the post is published

= Freemius =
Used for license management, usage analytics (opt-in), and plugin updates.
* Service: [freemius.com](https://freemius.com)
* Terms: [freemius.com/terms](https://freemius.com/terms/)
* Privacy: [freemius.com/privacy](https://freemius.com/privacy/)
* Data sent: Site URL, plugin version, license key (if Enterprise)
* When: On plugin activation (opt-in consent screen shown first)

== Screenshots ==

1. Dashboard — overview with total posts, monthly count, scheduled items, and posts needing a refresh.
2. Create Content — enter a topic, scan websites, select blog style, and generate a full SEO-optimized post.
3. Content result — SEO metadata, tags, categories, featured image options, internal links, and repurpose buttons.
4. Bulk Create — queue multiple posts with AI topic suggestions and auto blog style recommendations.
5. Refresh Content — analyze all published posts for issues and fix them with AI.
6. Settings — configure AI providers, image settings, internal linking, publishing schedule, and social sharing.
7. Scheduled Content — upcoming publications timeline with review queue and bulk actions.

== Upgrade Notice ==

= 3.1.1 =
Major rebrand to Ray Bogman Content Orchestrator. All internal prefixes updated. Fresh install recommended for new users.

== Changelog ==

= 3.1.1 =
* Renamed: Plugin rebranded to Ray Bogman Content Orchestrator (slug: raybogman-content-orchestrator) to comply with WordPress.org naming guidelines.
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
