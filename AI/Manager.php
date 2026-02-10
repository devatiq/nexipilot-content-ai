<?php
/**
 * Manager.php
 *
 * AI Manager for provider abstraction and caching.
 *
 * @package NexiPilot\AI
 * @since 1.0.0
 * @author Md Abul Bashar <hmbashar@gmail.com>
 */

namespace NexiPilot\AI;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use NexiPilot\Helpers\Logger;

/**
 * AI Manager Class
 *
 * Manages AI provider selection, caching, and request handling.
 *
 * @package NexiPilot\AI
 * @since 1.0.0
 */
class Manager
{
    /**
     * Current provider instance
     *
     * @var ProviderInterface|null
     */
    private $provider = null;

    /**
     * Cache expiration time (in seconds)
     *
     * @var int
     */
    private $cache_expiration = 86400; // 24 hours

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->init_provider();
    }

    /**
     * Initialize the AI provider (legacy - for backward compatibility)
     *
     * @since 1.0.0
     * @deprecated 2.0.0 Use get_provider_for_feature() instead
     * @return void
     */
    private function init_provider()
    {
        // Try to get provider from FAQ feature first (most commonly used)
        $provider_name = get_option('nexipilot_faq_provider', '');

        // If FAQ provider not set, try summary
        if (empty($provider_name)) {
            $provider_name = get_option('nexipilot_summary_provider', '');
        }

        // If summary provider not set, try internal links
        if (empty($provider_name)) {
            $provider_name = get_option('nexipilot_internal_links_provider', '');
        }

        // Default to openai if nothing is configured
        if (empty($provider_name)) {
            $provider_name = 'openai';
        }

        $this->provider = $this->init_provider_by_name($provider_name);
    }

    /**
     * Get provider instance for a specific feature
     *
     * @since 2.0.0
     * @param string $feature Feature name: 'faq', 'summary', or 'internal_links'
     * @return ProviderInterface|null
     */
    private function get_provider_for_feature($feature)
    {
        $provider_name = get_option("nexipilot_{$feature}_provider", 'openai');
        return $this->init_provider_by_name($provider_name);
    }

    /**
     * Initialize a specific provider by name
     *
     * @since 2.0.0
     * @param string $provider_name Provider name: 'openai', 'claude', or 'gemini'
     * @return ProviderInterface|null
     */
    private function init_provider_by_name($provider_name)
    {
        switch ($provider_name) {
            case 'gemini':
                $api_key = get_option('nexipilot_gemini_api_key', '');
                $model = get_option('nexipilot_gemini_model', 'gemini-2.5-flash');
                if (!empty($api_key)) {
                    $decrypted_key = \PostPilotAI\Helpers\Encryption::decrypt($api_key);
                    return new Gemini($decrypted_key, $model);
                }
                break;

            case 'claude':
                $api_key = get_option('nexipilot_claude_api_key', '');
                $model = get_option('nexipilot_claude_model', 'claude-3-5-sonnet-20241022');
                if (!empty($api_key)) {
                    $decrypted_key = \PostPilotAI\Helpers\Encryption::decrypt($api_key);
                    return new Claude($decrypted_key, $model);
                }
                break;

            case 'grok':
                $api_key = get_option('nexipilot_grok_api_key', '');
                $model = get_option('nexipilot_grok_model', 'grok-beta');
                if (!empty($api_key)) {
                    $decrypted_key = \PostPilotAI\Helpers\Encryption::decrypt($api_key);
                    return new Grok($decrypted_key, $model);
                }
                break;

            case 'openai':
            default:
                $api_key = get_option('nexipilot_openai_api_key', '');
                $model = get_option('nexipilot_openai_model', 'gpt-4o');
                if (!empty($api_key)) {
                    $decrypted_key = \PostPilotAI\Helpers\Encryption::decrypt($api_key);
                    return new OpenAI($decrypted_key, $model);
                }
                break;
        }

        return null;
    }

    /**
     * Check if provider is available
     *
     * @since 1.0.0
     * @return bool
     */
    public function is_provider_available()
    {
        return $this->provider !== null;
    }

    /**
     * Generate FAQ with caching
     *
     * @since 1.0.0
     * @param int    $post_id The post ID.
     * @param string $content The post content.
     * @return array|WP_Error
     */
    public function get_faq($post_id, $content)
    {
        // Get provider for FAQ feature
        $provider = $this->get_provider_for_feature('faq');

        if (!$provider) {
            // Return demo FAQ when no provider is configured
            Logger::debug('No AI provider configured for FAQ, returning demo FAQ', array('post_id' => $post_id));
            return $this->get_demo_faq();
        }

        // Check cache
        $cache_key = 'nexipilot_faq_' . $post_id;
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            Logger::debug('FAQ retrieved from cache', array('post_id' => $post_id));
            return $cached;
        }

        // Generate new FAQ
        $faq = $provider->generate_faq($content);

        if (is_wp_error($faq)) {
            Logger::error('FAQ generation failed', array(
                'post_id' => $post_id,
                'error' => $faq->get_error_message()
            ));
            // Return demo FAQ on error
            return $this->get_demo_faq();
        }

        if (!empty($faq)) {
            set_transient($cache_key, $faq, $this->cache_expiration);
            Logger::debug('FAQ generated and cached', array('post_id' => $post_id));
        }

        return $faq;
    }

    /**
     * Generate summary with caching
     *
     * @since 1.0.0
     * @param int    $post_id The post ID.
     * @param string $content The post content.
     * @return string|WP_Error
     */
    public function get_summary($post_id, $content)
    {
        // Get provider for Summary feature
        $provider = $this->get_provider_for_feature('summary');

        if (!$provider) {
            // Return demo summary when no provider is configured
            Logger::debug('No AI provider configured for Summary, returning demo summary', array('post_id' => $post_id));
            return __('This is a demo summary. Configure your AI provider API key in PostPilot settings to generate real AI-powered summaries.', 'nexipilot-content-ai');
        }

        // Check cache
        $cache_key = 'nexipilot_summary_' . $post_id;
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            Logger::debug('Summary retrieved from cache', array('post_id' => $post_id));
            return $cached;
        }

        // Generate new summary
        $summary = $provider->generate_summary($content);

        if (is_wp_error($summary)) {
            Logger::error('Summary generation failed', array(
                'post_id' => $post_id,
                'error' => $summary->get_error_message()
            ));
            // Return the actual error message from the API
            return sprintf(
                /* translators: %1$s: error message returned while generating the summary */
                __('Summary generation failed: %1$s', 'nexipilot-content-ai'),
                $summary->get_error_message()
            );

        }

        if (!empty($summary)) {
            set_transient($cache_key, $summary, $this->cache_expiration);
            Logger::debug('Summary generated and cached', array('post_id' => $post_id));
        }

        return $summary;
    }

    /**
     * Get internal link suggestions with caching
     *
     * @since 1.0.0
     * @param int    $post_id The post ID.
     * @param string $content The post content.
     * @return array|WP_Error
     */
    public function get_internal_links($post_id, $content)
    {
        // Get provider for Internal Links feature
        $provider = $this->get_provider_for_feature('internal_links');

        if (!$provider) {
            return new \WP_Error(
                'no_provider',
                __('AI provider is not configured for Internal Links.', 'nexipilot-content-ai')
            );
        }

        // Check cache
        $cache_key = 'nexipilot_links_' . $post_id;
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            Logger::debug('Internal links retrieved from cache', array('post_id' => $post_id));
            return $cached;
        }

        // Get available posts for linking
        $available_posts = $this->get_available_posts($post_id);

        if (empty($available_posts)) {
            return array();
        }

        // Generate link suggestions
        $links = $provider->suggest_internal_links($content, $available_posts);

        if (!is_wp_error($links)) {
            set_transient($cache_key, $links, $this->cache_expiration);
            Logger::debug('Internal links generated and cached', array('post_id' => $post_id));
        }

        return $links;
    }

    /**
     * Get available posts for internal linking
     *
     * @since 1.0.0
     * @param int $exclude_post_id Post ID to exclude.
     * @return array
     */
    private function get_available_posts($exclude_post_id)
    {
        $args = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 20,
            'orderby' => 'date',
            'order' => 'DESC',
            // Excluding a single known post ID (current post).
            // This is safe and intentional.
            // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in
            'post__not_in' => array((int) $exclude_post_id),
        );

        return get_posts($args);
    }

    /**
     * Clear cache for a specific post
     *
     * @since 1.0.0
     * @param int $post_id The post ID.
     * @return void
     */
    public function clear_post_cache($post_id)
    {
        delete_transient('nexipilot_faq_' . $post_id);
        delete_transient('nexipilot_summary_' . $post_id);
        delete_transient('nexipilot_links_' . $post_id);

        Logger::debug('Cache cleared for post', array('post_id' => $post_id));
    }

    /**
     * Validate current provider's API key
     *
     * @since 1.0.0
     * @param string $api_key The API key to validate.
     * @return bool|WP_Error
     */
    public function validate_api_key($api_key)
    {
        if (!$this->is_provider_available()) {
            return new \WP_Error(
                'no_provider',
                __('AI provider is not configured.', 'nexipilot-content-ai')
            );
        }

        return $this->provider->validate_api_key($api_key);
    }

    /**
     * Get demo FAQ for testing
     *
     * @since 1.0.0
     * @return array
     */
    public function get_demo_faq()
    {
        return array(
            array(
                'question' => __('How do I configure PostPilot AI?', 'nexipilot-content-ai'),
                'answer' => __('Go to PostPilot AI in your WordPress admin menu, select your AI provider (OpenAI or Claude), enter your API key, and enable the features you want to use.', 'nexipilot-content-ai'),
            ),
            array(
                'question' => __('What AI providers are supported?', 'nexipilot-content-ai'),
                'answer' => __('PostPilot AI currently supports OpenAI (ChatGPT), Claude (Anthropic), and Google Gemini. You can switch between providers in the settings.', 'nexipilot-content-ai'),
            ),
            array(
                'question' => __('Is this a demo FAQ?', 'nexipilot-content-ai'),
                'answer' => __('Yes! This is demo content shown because no AI provider is configured. Add your API key in the settings to generate real AI-powered FAQs.', 'nexipilot-content-ai'),
            ),
            array(
                'question' => __('How do I get an API key?', 'nexipilot-content-ai'),
                'answer' => __('For OpenAI, visit platform.openai.com/api-keys. For Claude, visit console.anthropic.com. Both services require account registration.', 'nexipilot-content-ai'),
            ),
        );
    }

    /**
     * Test API connection
     *
     * @since 1.0.0
     * @return true|WP_Error True if connection successful, WP_Error on failure
     */
    public function test_api_connection()
    {
        if (!$this->is_provider_available()) {
            return new \WP_Error(
                'no_provider',
                __('No AI provider configured.', 'nexipilot-content-ai')
            );
        }

        // Make a minimal test request using generate_summary
        $test_prompt = 'Test content for API validation.';
        $result = $this->provider->generate_summary($test_prompt);

        if (is_wp_error($result)) {
            return $result; // Return the specific error from the provider
        }

        return true;
    }
}
