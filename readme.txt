=== PostPilot AI ===
Contributors: nexibyllc
Tags: ai summarization, faq, internal-links, content generation
Requires at least: 5.8
Tested up to: 6.9
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

AI-powered WordPress plugin that generates FAQs, content summaries, and smart internal links for your posts using OpenAI, Claude, Gemini, or Grok.

== Description ==

PostPilot AI enhances your WordPress content with AI-generated features, helping you create more engaging and SEO-friendly posts automatically.

**Features**

*   **🤖 Multiple AI Provider Support**: Choose from OpenAI (GPT-3.5, GPT-4), Anthropic (Claude 3), Google (Gemini), or xAI (Grok).
*   **🔗 External AI Sharing**: Add buttons to let your readers instantly summarize your content using Microsoft Copilot or Google AI Overview.
*   **❓ FAQ Generator**: Automatically creates relevant frequently asked questions with answers based on your post content.
*   **📝 Content Summary**: Generates concise, engaging summaries of your posts to hook readers.
*   **🔗 Smart Internal Links**: Intelligently suggests and adds internal links to related content within your site to improve SEO and user navigation.
*   **🎨 Modern UI**: Clean, responsive design that integrates seamlessly with your theme.
*   **⚡ Performance**: Built-in caching system ensures optimal performance and minimizes API usage.
*   **🔒 Secure**: Secure API key storage and handling.

== Installation ==

1.  Upload the `postpilot` folder to the `/wp-content/plugins/` directory.
2.  Activate the plugin through the 'Plugins' menu in WordPress.
3.  Go to **PostPilot AI** in the admin menu.
4.  Configure your AI provider (OpenAI, Claude, Gemini, or Grok) and enter your API key.
5.  Enable the features you want to use (FAQ, Summary, Internal Links, External Sharing).

== Frequently Asked Questions ==

= Do I need an API key? =
Yes, you need an API key from one of the supported providers: OpenAI, Anthropic (Claude), Google (Gemini), or xAI (Grok).

= Which AI models are supported? =
- **OpenAI**: GPT-4o, GPT-4 Turbo, GPT-3.5 Turbo
- **Anthropic**: Claude 3 Opus, Claude 3 Sonnet, Claude 3 Haiku
- **Google**: Gemini 1.5 Pro, Gemini 1.5 Flash
- **xAI**: Grok-2, Grok-2 mini, Grok Beta

= Does it slow down my site? =
No. PostPilot AI processes content asynchronously and caches the results for 24 hours. The generated content is served from the cache, so there is no delay for your visitors.

= Can I customize the output? =
Yes! PostPilot AI provides hooks and filters that allow developers to customize the generated HTML output for FAQs, summaries, and internal links.

== Screenshots ==

1.  **General Settings**: Configure your AI provider and API key.
2.  **Feature Settings**: Enable and customize individual AI features.
3.  **Frontend Example**: How the generated AI content looks on your post.

== Changelog ==

= 1.0.0 =
*   Initial release.
*   Support for OpenAI, Claude, Gemini, and Grok providers.
*   External AI Sharing (Microsoft Copilot, Google AI Overview).
*   FAQ Generator, Content Summary, and Smart Internal Links features.
*   Built-in caching system.
*   Modern, responsive admin interface.
