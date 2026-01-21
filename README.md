# PostPilot AI

AI-powered WordPress plugin that generates FAQs, content summaries, and smart internal links for your posts.

## Description

PostPilot AI enhances your WordPress content with AI-generated features:

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

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- An API key from one of the supported providers (OpenAI, Anthropic, Google, xAI)

## Installation

1. Upload the `postpilot` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to **PostPilot AI** in the admin menu
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

Once configured, PostPilot AI automatically processes your published posts and adds the enabled features. The content is cached for 24 hours to optimize performance and reduce API costs.

### Clearing Cache

The cache is automatically cleared when you update a post. You can also manually clear transients using WordPress tools.

## Hooks & Filters

PostPilot AI is built with extensibility in mind. Use these filters to customize output:

```php
// Customize FAQ output
add_filter('postpilot_faq_output', function($output, $post_id, $faq_data) {
    // Modify $output
    return $output;
}, 10, 3);

// Customize summary output
add_filter('postpilot_summary_output', function($output, $post_id, $summary_text) {
    // Modify $output
    return $output;
}, 10, 3);

// Customize internal links output
add_filter('postpilot_internal_links_output', function($content, $post_id, $link_suggestions) {
    // Modify $content
    return $content;
}, 10, 3);
```

## Development

### Folder Structure

```
postpilot/
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

- **GitHub**: [https://github.com/devatiq/postpilot](https://github.com/devatiq/postpilot)
- **Issues**: [https://github.com/devatiq/postpilot/issues](https://github.com/devatiq/postpilot/issues)

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
