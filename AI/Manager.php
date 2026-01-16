<?php
/**
 * Manager.php
 *
 * AI Manager for provider abstraction and caching.
 *
 * @package PostPilot\AI
 * @since 1.0.0
 */

namespace PostPilot\AI;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use PostPilot\Helpers\Logger;

/**
 * AI Manager Class
 *
 * Manages AI provider selection, caching, and request handling.
 *
 * @package PostPilot\AI
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
     * Initialize the AI provider
     *
     * @since 1.0.0
     * @return void
     */
    private function init_provider()
    {
        $provider_name = get_option('postpilot_ai_provider', 'openai');
        
        switch ($provider_name) {
            case 'claude':
                $api_key = get_option('postpilot_claude_api_key', '');
                if (!empty($api_key)) {
                    $this->provider = new Claude($api_key);
                }
                break;
                
            case 'openai':
            default:
                $api_key = get_option('postpilot_openai_api_key', '');
                if (!empty($api_key)) {
                    $this->provider = new OpenAI($api_key);
                }
                break;
        }
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
        if (!$this->is_provider_available()) {
            return new \WP_Error(
                'no_provider',
                __('AI provider is not configured.', 'postpilot')
            );
        }

        // Check cache
        $cache_key = 'postpilot_faq_' . $post_id;
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            Logger::debug('FAQ retrieved from cache', array('post_id' => $post_id));
            return $cached;
        }

        // Generate new FAQ
        $faq = $this->provider->generate_faq($content);
        
        if (!is_wp_error($faq)) {
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
        if (!$this->is_provider_available()) {
            return new \WP_Error(
                'no_provider',
                __('AI provider is not configured.', 'postpilot')
            );
        }

        // Check cache
        $cache_key = 'postpilot_summary_' . $post_id;
        $cached = get_transient($cache_key);
        
        if ($cached !== false) {
            Logger::debug('Summary retrieved from cache', array('post_id' => $post_id));
            return $cached;
        }

        // Generate new summary
        $summary = $this->provider->generate_summary($content);
        
        if (!is_wp_error($summary)) {
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
        if (!$this->is_provider_available()) {
            return new \WP_Error(
                'no_provider',
                __('AI provider is not configured.', 'postpilot')
            );
        }

        // Check cache
        $cache_key = 'postpilot_links_' . $post_id;
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
        $links = $this->provider->suggest_internal_links($content, $available_posts);
        
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
            'post__not_in' => array($exclude_post_id),
            'orderby' => 'date',
            'order' => 'DESC',
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
        delete_transient('postpilot_faq_' . $post_id);
        delete_transient('postpilot_summary_' . $post_id);
        delete_transient('postpilot_links_' . $post_id);
        
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
                __('AI provider is not configured.', 'postpilot')
            );
        }

        return $this->provider->validate_api_key($api_key);
    }
}
