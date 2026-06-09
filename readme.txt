=== Ray Bogman AI Content Orchestrator ===
Contributors: raybogman
Donate link: https://raybogman.com
Tags: ai, content-generator, seo, openai, claude
Requires at least: 5.9
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI-powered content creation for WordPress. Generates SEO-optimized blog posts using Claude or OpenAI, with featured images and Yoast integration.

== Description ==

Ray Bogman AI Content Orchestrator generates SEO-optimized blog posts and pages for your WordPress site using Claude AI (Anthropic) or OpenAI GPT models. It is a complete, fully functional content pipeline: give it a topic, optionally scan a website for context, and it writes an original article, generates a featured image, adds internal links, and fills in your Yoast SEO fields.

**Key Features:**

* **Dual AI Provider Support** — Choose between Claude (Anthropic) and OpenAI (GPT) for content generation. Switch between providers at any time.
* **Website Context Scanning** — Reads pages from a URL you provide to give the AI background context for writing on-brand content. Only scans the single URL you explicitly enter — no automated or third-party crawling.
* **AI Content Generation** — Two-step process: generates SEO metadata first, then creates original HTML content with a table of contents, FAQ sections, and a call-to-action.
* **4 Blog Styles** — Standard Blog Post, How-To Guide, Listicle, and Beginner's Guide — each with a specialized AI prompt.
* **AI Featured Images** — Generate a custom hero image for each post using OpenAI DALL-E 3, automatically set as the featured image.
* **Title Overlay Images** — Optionally place the post title on a branded background image with custom fonts and colors — a unique featured image per post, no AI image cost.
* **Automatic Internal Linking** — After writing, the plugin scans your existing published posts and adds relevant internal links within the text to strengthen your site's SEO.
* **Project Vision** — Define baseline instructions (brand voice, tone, audience, rules) that the AI always follows before generating any content.
* **SEO Metadata** — Automatically generates SEO titles (max 60 chars), meta descriptions (max 155 chars), slugs, focus keyphrases, tags, and categories.
* **Yoast SEO Integration** — Automatically populates Yoast SEO fields (title, meta description, focus keyphrase) when Yoast is installed.
* **WordPress Category Selection** — Browse and select from existing WordPress categories. The AI also suggests additional categories based on content.
* **Draft or Publish** — Save as draft for review or publish immediately.
* **Blog Post or Page** — Generate blog posts (1000-2000 words with tags/categories) or pages (500-1000 words).
* **Dashboard + Progress Logging** — See content stats and real-time progress updates during scanning and generation. Download a debug log with one click.
* **Works on Shared Hosting** — The generation pipeline is split into four short steps so it completes within typical server timeouts.

**Supported Models:**

* Claude Sonnet 4.6, Opus 4.6, Haiku 4.5 (Anthropic)
* GPT-4o, GPT-4o Mini, GPT-4 Turbo, GPT-4.1, GPT-4.1 Mini, GPT-4.1 Nano (OpenAI)

**How it works:**

1. (Optional) Enter a website URL to scan for context — the scanner reads its pages for background information.
2. Write a prompt describing the content you want.
3. Choose blog post or page, pick a blog style, set draft or publish, and select categories.
4. Click "Create Content" — the AI generates SEO metadata then full HTML content, an optional featured image, and internal links.
5. Content is published to WordPress with tags, categories, and Yoast SEO fields populated.

== Installation ==

1. Upload the `raybogman-ai-content-orchestrator` folder to `/wp-content/plugins/`.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **AI Content > Settings** and select your AI provider (Claude or OpenAI).
4. Enter your API key and click Save Changes.
5. Go to **AI Content > Create Content** to generate your first post.

== Frequently Asked Questions ==

= What AI models does this support? =

Both Claude (Anthropic) and OpenAI GPT models. You can configure both and switch between them in Settings.

= Do I need both a Claude and OpenAI account? =

No. You only need one AI provider. Pick either Claude or OpenAI and enter that provider's API key.

= How much does it cost? =

The plugin is free. You pay only for the AI usage through your provider's API — typically $0.02–$0.10 per blog post, plus about $0.04 per DALL-E 3 featured image.

= Do I need Yoast SEO? =

No, Yoast SEO is optional. If installed, the plugin automatically populates the SEO title, meta description, and focus keyphrase. Without Yoast, metadata is still generated and stored in the post excerpt.

= Can I select which categories to use? =

Yes. Existing WordPress categories are listed as checkboxes on the creation form. The AI may also suggest additional categories which are created automatically.

= Can I scan any website? =

The scanner reads publicly accessible pages from a URL you provide. It is intended for scanning your own website or sites you have permission to scan, to give the AI relevant context. It does not perform automated mass crawling or scraping of third-party sites.

== External services ==

This plugin relies on the following third-party AI services. No data is sent to any of them until the site administrator explicitly configures the relevant API key and initiates an action (for example clicking "Create Content" or generating an image). None of these services are contacted on the front end for normal site visitors.

WordPress 7.0+ — preferred path: When running on WordPress 7.0 or newer and the site owner has configured a provider through the core AI Client, every text generation request is routed through that core AI Client first (wp_ai_client_prompt() → generateText()). In that case the plugin never touches api.anthropic.com or api.openai.com directly — the request goes to whichever provider the site owner has set up at the site level, with credentials managed by WordPress core. The plugin's own direct integrations below are used only as a fallback for older WordPress versions or when no core provider has been configured.

= Anthropic (Claude AI) =
What it is: AI text model API, used for content generation and SEO metadata when Claude is selected as the AI provider.
Domain(s) contacted: api.anthropic.com
What data is sent and when: The user prompt, the scanned website context, and the requested content type — sent only when an administrator clicks "Create Content".
* Terms of service: https://www.anthropic.com/legal/consumer-terms
* Privacy policy: https://www.anthropic.com/privacy

= OpenAI (GPT & DALL-E 3) =
What it is: AI text and image model API, used for content generation, SEO metadata, and AI featured image generation when OpenAI is selected.
Domain(s) contacted: api.openai.com (generated images are then downloaded from the temporary URL OpenAI returns).
What data is sent and when: The user prompt, scanned website context, and image-generation prompts — sent only when an administrator clicks "Create Content" or generates an image.
* Terms of service: https://openai.com/policies/terms-of-use
* Privacy policy: https://openai.com/policies/privacy-policy

== Screenshots ==

1. Dashboard — overview with total posts, monthly count, drafts, and posts needing a refresh.
2. Create Content — enter a topic, scan a website, select a blog style, and generate a full SEO-optimized post.
3. Content result — SEO metadata, tags, categories, featured image options, and internal links.
4. Settings — configure AI providers, featured images, internal linking, and the website scanner.
5. Style Examples — preview the available blog writing styles.

== Upgrade Notice ==

= 1.0.0 =
First public release: a free, fully functional AI content pipeline for WordPress with Claude/OpenAI support, DALL-E 3 featured images, internal linking, and Yoast integration.

== Changelog ==

= 1.0.0 =
* Initial public release on WordPress.org.
* AI content generation with Claude (Anthropic) and OpenAI (GPT), routed through the WordPress 7.0+ core AI Client when available.
* Single-URL website scanning for context.
* Four blog writing styles (Standard, How-To, Listicle, Beginner's Guide).
* SEO metadata generation with Yoast SEO integration.
* AI featured image generation with OpenAI DALL-E 3, plus an optional branded title-overlay image.
* Automatic internal linking to existing published posts.
* Project Vision baseline instructions, category selection, draft/publish, and a content dashboard with progress logging.
