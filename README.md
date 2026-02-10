# NexiPilot Content AI

AI-powered WordPress plugin that generates FAQs, content summaries, and smart internal links for your posts.

## Description

NexiPilot Content AI enhances your WordPress content with AI-generated features:

- **FAQ Generator**: Automatically creates relevant frequently asked questions with answers
- **Content Summary**: Generates concise, engaging summaries of your posts
- **Smart Internal Links**: Intelligently suggests and adds internal links to related content

## Features

- 🤖 Multiple AI provider support (OpenAI, Claude, **Gemini**, **Grok**)
- 🔗 **External AI Sharing**: Share content directly with Microsoft Copilot and Google AI Overview
- 🎨 Clean, modern UI for generated content
- ⚡ Built-in caching for optimal performance
- 🎯 Hook-based architecture for maximum extensibility
- 🔒 Secure API key storage
- 🌐 Fully translatable
- 📱 Responsive design with dark mode support

## External Services

NexiPilot Content AI connects to third-party AI APIs to generate content features such as FAQs, summaries, and internal link suggestions. These services are required for the plugin's core functionality when AI generation is enabled.

**Important**: The plugin only sends data to these services when you (the administrator) manually trigger content generation or when auto-generation features are enabled. No visitor personal data is transmitted to these services.

The plugin may connect to one or more of the following services depending on the AI provider you select in the plugin settings:

### OpenAI (ChatGPT API)

**What it is used for:**
- Generates FAQs, content summaries, and internal link suggestions using OpenAI's GPT models (GPT-4o, GPT-4 Turbo, GPT-3.5 Turbo).

**What data is sent and when:**
- Your post/page content is sent to OpenAI only when you manually click "Generate FAQ" or when auto-generation is enabled for published posts.
- The request includes: post content, selected model name, and the feature type (FAQ/Summary/Links).
- No visitor personal data, IP addresses, or user information is sent.

**Service terms and privacy policy:**
- Terms of Service: https://openai.com/terms
- Privacy Policy: https://openai.com/privacy

### Anthropic (Claude API)

**What it is used for:**
- Generates FAQs, content summaries, and internal link suggestions using Anthropic's Claude models (Claude 3.5 Sonnet, Claude 3 Opus, Claude 3 Haiku).

**What data is sent and when:**
- Your post/page content is sent to Anthropic only when you manually click "Generate FAQ" or when auto-generation is enabled for published posts.
- The request includes: post content, selected model name, and the feature type (FAQ/Summary/Links).
- No visitor personal data, IP addresses, or user information is sent.

**Service terms and privacy policy:**
- Terms of Service: https://www.anthropic.com/terms
- Privacy Policy: https://www.anthropic.com/privacy

### Google (Gemini API)

**What it is used for:**
- Generates FAQs, content summaries, and internal link suggestions using Google's Gemini models (Gemini 1.5 Pro, Gemini 1.5 Flash).

**What data is sent and when:**
- Your post/page content is sent to Google only when you manually click "Generate FAQ" or when auto-generation is enabled for published posts.
- The request includes: post content, selected model name, and the feature type (FAQ/Summary/Links).
- No visitor personal data, IP addresses, or user information is sent.

**Service terms and privacy policy:**
- Terms of Service: https://policies.google.com/terms
- Privacy Policy: https://policies.google.com/privacy

### xAI (Grok API)

**What it is used for:**
- Generates FAQs, content summaries, and internal link suggestions using xAI's Grok models (Grok-2, Grok-2 mini, Grok Beta).

**What data is sent and when:**
- Your post/page content is sent to xAI only when you manually click "Generate FAQ" or when auto-generation is enabled for published posts.
- The request includes: post content, selected model name, and the feature type (FAQ/Summary/Links).
- No visitor personal data, IP addresses, or user information is sent.

**Service terms and privacy policy:**
- Terms of Service: https://x.ai/legal
- Privacy Policy: https://x.ai/privacy-policy

**Note**: You must obtain your own API keys from these providers. The plugin does not include API keys and does not send any data to these services unless you configure them and enable the features.

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- An API key from one of the supported providers (OpenAI, Anthropic, Google, xAI)

## Installation

1. Upload the `nexipilot-content-ai` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to **NexiPilot Content AI** in the admin menu
4. Configure your AI provider and enter your API key
5. Enable the features you want to use

## Configuration

### AI Provider Setup

1. Choose your AI provider (OpenAI, Claude, Gemini, or Grok)
2. Enter your API key:
   - **OpenAI**: Get your key from [OpenAI Platform](https://platform.openai.com/api-keys)
   - **Claude**: Get your key from [Anthropic Console](https://console.anthropic.com/)
   - **Gemini**: Get your key from [Google AI Studio](https://aistudio.google.com/)
   - **Grok**: Get your key from [xAI Console](https://console.x.ai/)

### Feature Settings

- **FAQ Generator**: Enable/disable and choose position (before or after content)
- **Content Summary**: Enable/disable and choose position (before or after content)
- **Smart Internal Links**: Enable/disable automatic internal linking
- **External AI Sharing**: Enable buttons to let users summarize your content with Copilot or Google AI Overview

## Usage

Once configured, NexiPilot Content AI automatically processes your published posts and adds the enabled features. The content is cached for 24 hours to optimize performance and reduce API costs.

### Clearing Cache

The cache is automatically cleared when you update a post. You can also manually clear transients using WordPress tools.

## Hooks & Filters

NexiPilot Content AI is built with extensibility in mind. Use these filters to customize output:

```php
// Customize FAQ output
add_filter('nexipilot_faq_output', function($output, $post_id, $faq_data) {
    // Modify $output
    return $output;
}, 10, 3);

// Customize summary output
add_filter('nexipilot_summary_output', function($output, $post_id, $summary_text) {
    // Modify $output
    return $output;
}, 10, 3);

// Customize internal links output
add_filter('nexipilot_internal_links_output', function($content, $post_id, $link_suggestions) {
    // Modify $content
    return $content;
}, 10, 3);
```

## Development

### Folder Structure

```
nexipilot-content-ai/
├── Admin/              # Admin dashboard components
├── AI/                 # AI provider implementations
├── Frontend/           # Frontend content injection
├── Helpers/            # Utility classes
├── Inc/                # Core classes
└── languages/          # Translation files
```

### Composer Commands

```bash
# Install dependencies
composer install

# Run WordPress Coding Standards check
composer run lint:wpcs

# Auto-fix coding standards
composer run lint:autofix

# Generate translation file
composer run make-pot
```

## Support

For issues, questions, or contributions:

- **GitHub**: [https://github.com/devatiq/nexipilot-content-ai](https://github.com/devatiq/nexipilot-content-ai)
- **Issues**: [https://github.com/devatiq/nexipilot-content-ai/issues](https://github.com/devatiq/nexipilot-content-ai/issues)

## License

This plugin is licensed under the GPL-2.0-or-later license.

## Credits

- **Author**: Nexiby LLC
- **Website**: [https://nexiby.com](https://nexiby.com)

## Changelog

### 1.0.0
- Initial release
- Multiple AI provider support: OpenAI, Claude, Gemini, Grok
- External AI Sharing: Microsoft Copilot, Google AI Overview
- FAQ Generator feature
- Content Summary feature
- Smart Internal Links feature
- Caching system
- Modern, responsive admin UI
- Robust error handling and API key validation
