<?php
/**
 * ContentInjector.php
 *
 * Handles content injection for AI-generated features.
 *
 * @package PostPilot\Frontend
 * @since 1.0.0
 */

namespace PostPilot\Frontend;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use PostPilot\AI\Manager as AIManager;
use PostPilot\Helpers\Logger;

/**
 * ContentInjector Class
 *
 * Injects AI-generated content into posts using WordPress hooks.
 *
 * @package PostPilot\Frontend
 * @since 1.0.0
 */
class ContentInjector
{
    /**
     * AI Manager instance
     *
     * @var AIManager
     */
    private $ai_manager;

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->ai_manager = new AIManager();
        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks
     *
     * @since 1.0.0
     * @return void
     */
    private function init_hooks()
    {
        add_filter('the_content', array($this, 'inject_ai_content'), 10);
        add_action('save_post', array($this, 'clear_post_cache'), 10, 1);
    }

    /**
     * Inject AI-generated content
     *
     * @since 1.0.0
     * @param string $content The post content.
     * @return string Modified content
     */
    public function inject_ai_content($content)
    {
        // Only process single posts
        if (!is_singular('post') || !in_the_loop() || !is_main_query()) {
            return $content;
        }

        global $post;

        if (!$post) {
            return $content;
        }

        $modified_content = $content;

        // Inject Summary
        if (get_option('postpilot_enable_summary', '1') === '1') {
            $summary_position = get_option('postpilot_summary_position', 'before_content');
            $summary = $this->get_summary($post->ID, $post->post_content);

            if ($summary_position === 'before_content') {
                $modified_content = $summary . $modified_content;
            }
        }

        // Inject Internal Links
        if (get_option('postpilot_enable_internal_links', '1') === '1') {
            $modified_content = $this->inject_internal_links($post->ID, $modified_content);
        }

        // Inject FAQ
        if (get_option('postpilot_enable_faq', '1') === '1') {
            $faq_position = get_option('postpilot_faq_position', 'after_content');
            $faq = $this->get_faq($post->ID, $post->post_content);

            if ($faq_position === 'after_content') {
                $modified_content .= $faq;
            } else {
                $modified_content = $faq . $modified_content;
            }
        }

        // Inject Summary (if after content)
        if (get_option('postpilot_enable_summary', '1') === '1') {
            $summary_position = get_option('postpilot_summary_position', 'before_content');

            if ($summary_position === 'after_content') {
                $summary = $this->get_summary($post->ID, $post->post_content);
                $modified_content .= $summary;
            }
        }

        // Inject External AI Sharing
        if (get_option('postpilot_enable_external_ai_sharing', '1') === '1') {
            $external_ai_position = get_option('postpilot_external_ai_position', 'after_content');
            $external_ai_html = $this->get_external_ai_sharing_html($post->ID);

            if ($external_ai_position === 'before_content') {
                $modified_content = $external_ai_html . $modified_content;
            } else {
                $modified_content .= $external_ai_html;
            }
        }

        return $modified_content;
    }

    /**
     * Get FAQ HTML
     *
     * @since 1.0.0
     * @param int    $post_id The post ID.
     * @param string $content The post content (unused, kept for compatibility).
     * @return string FAQ HTML
     */
    private function get_faq($post_id, $content)
    {
        // Check if FAQ is enabled for this post
        $faq_enabled = get_post_meta($post_id, '_postpilot_faq_enabled', true);

        if ($faq_enabled !== '1') {
            return '';
        }

        // Get FAQ data from post meta
        $faq_data = get_post_meta($post_id, '_postpilot_faqs', true);

        if (empty($faq_data) || !is_array($faq_data)) {
            Logger::debug('No FAQ data found in post meta', array('post_id' => $post_id));
            return '';
        }

        // Determine layout style
        $layout = $this->get_faq_layout($post_id);

        ob_start();
        ?>
        <div class="postpilot-faq postpilot-faq--<?php echo esc_attr($layout); ?>">
            <h2 class="postpilot-faq__title"><?php esc_html_e('Frequently Asked Questions', 'postpilot'); ?></h2>
            <div class="postpilot-faq__items">
                <?php foreach ($faq_data as $index => $faq_item): ?>
                    <?php if (isset($faq_item['question']) && isset($faq_item['answer'])): ?>
                        <div class="postpilot-faq__item">
                            <?php if ($layout === 'accordion'): ?>
                                <button class="postpilot-faq__question" type="button">
                                    <?php echo esc_html($faq_item['question']); ?>
                                </button>
                                <div class="postpilot-faq__answer">
                                    <p><?php echo wp_kses_post($faq_item['answer']); ?></p>
                                </div>
                            <?php else: ?>
                                <h3 class="postpilot-faq__question"><?php echo esc_html($faq_item['question']); ?></h3>
                                <div class="postpilot-faq__answer">
                                    <p><?php echo wp_kses_post($faq_item['answer']); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        $output = ob_get_clean();

        /**
         * Filter the FAQ output
         *
         * @since 1.0.0
         * @param string $output The FAQ HTML output.
         * @param int    $post_id The post ID.
         * @param array  $faq_data The FAQ data array.
         * @param string $layout The layout style (accordion or static).
         */
        return apply_filters('postpilot_faq_output', $output, $post_id, $faq_data, $layout);
    }

    /**
     * Get FAQ layout style for a post
     *
     * @since 1.0.0
     * @param int $post_id The post ID.
     * @return string The layout style (accordion or static).
     */
    private function get_faq_layout($post_id)
    {
        // Get per-post layout setting
        $post_layout = get_post_meta($post_id, '_postpilot_faq_display_style', true);

        // If default or empty, use global setting
        if (empty($post_layout) || $post_layout === 'default') {
            return get_option('postpilot_faq_default_layout', 'accordion');
        }

        return $post_layout;
    }

    /**
     * Get Summary HTML
     *
     * @since 1.0.0
     * @param int    $post_id The post ID.
     * @param string $content The post content.
     * @return string Summary HTML
     */
    private function get_summary($post_id, $content)
    {
        $summary_text = $this->ai_manager->get_summary($post_id, $content);

        if (is_wp_error($summary_text) || empty($summary_text)) {
            Logger::debug('Summary generation failed or empty', array('post_id' => $post_id));
            return '';
        }

        ob_start();
        ?>
        <div class="postpilot-summary">
            <div class="postpilot-summary-content">
                <strong><?php esc_html_e('Summary:', 'postpilot'); ?></strong>
                <?php echo wp_kses_post($summary_text); ?>
            </div>
        </div>
        <?php
        $output = ob_get_clean();

        /**
         * Filter the summary output
         *
         * @since 1.0.0
         * @param string $output The summary HTML output.
         * @param int    $post_id The post ID.
         * @param string $summary_text The summary text.
         */
        return apply_filters('postpilot_summary_output', $output, $post_id, $summary_text);
    }

    /**
     * Inject internal links into content
     *
     * @since 1.0.0
     * @param int    $post_id The post ID.
     * @param string $content The post content.
     * @return string Modified content with internal links
     */
    private function inject_internal_links($post_id, $content)
    {
        $link_suggestions = $this->ai_manager->get_internal_links($post_id, $content);

        if (is_wp_error($link_suggestions) || empty($link_suggestions)) {
            Logger::debug('Internal link generation failed or empty', array('post_id' => $post_id));
            return $content;
        }

        $modified_content = $content;

        foreach ($link_suggestions as $suggestion) {
            if (!isset($suggestion['keyword']) || !isset($suggestion['post_id'])) {
                continue;
            }

            $keyword = $suggestion['keyword'];
            $linked_post_id = absint($suggestion['post_id']);
            $permalink = get_permalink($linked_post_id);

            if (!$permalink) {
                continue;
            }

            // Create the link
            $link = sprintf(
                '<a href="%s" class="postpilot-internal-link">%s</a>',
                esc_url($permalink),
                esc_html($keyword)
            );

            // Replace first occurrence of the keyword (case-insensitive)
            $modified_content = preg_replace(
                '/\b' . preg_quote($keyword, '/') . '\b/i',
                $link,
                $modified_content,
                1
            );
        }

        /**
         * Filter the internal links output
         *
         * @since 1.0.0
         * @param string $modified_content The content with internal links.
         * @param int    $post_id The post ID.
         * @param array  $link_suggestions The link suggestions array.
         */
        return apply_filters('postpilot_internal_links_output', $modified_content, $post_id, $link_suggestions);
    }

    /**
     * Clear post cache when post is saved
     *
     * @since 1.0.0
     * @param int $post_id The post ID.
     * @return void
     */
    public function clear_post_cache($post_id)
    {
        // Avoid autosaves and revisions
        if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
            return;
        }

        $this->ai_manager->clear_post_cache($post_id);
    }

    /**
     * Get External AI Sharing HTML
     *
     * @since 1.0.0
     * @param int $post_id The post ID.
     * @return string External AI sharing HTML
     */
    private function get_external_ai_sharing_html($post_id)
    {
        // Get enabled providers
        $enabled_providers = array();

        if (get_option('postpilot_external_ai_chatgpt', '1') === '1') {
            $enabled_providers['chatgpt'] = 'ChatGPT';
        }
        if (get_option('postpilot_external_ai_claude', '1') === '1') {
            $enabled_providers['claude'] = 'Claude';
        }
        if (get_option('postpilot_external_ai_perplexity', '1') === '1') {
            $enabled_providers['perplexity'] = 'Perplexity';
        }
        if (get_option('postpilot_external_ai_grok', '1') === '1') {
            $enabled_providers['grok'] = 'Grok';
        }

        // If no providers enabled, return empty
        if (empty($enabled_providers)) {
            return '';
        }

        $post_url = get_permalink($post_id);

        if (!$post_url) {
            return '';
        }

        ob_start();
        ?>
                <div class="postpilot-external-ai-sharing">
                    <div class="postpilot-external-ai-sharing__header">
                        <span class="postpilot-external-ai-sharing__icon">🔗</span>
                        <h3 class="postpilot-external-ai-sharing__title"><?php esc_html_e('Summarize this post with:', 'postpilot'); ?></h3>
                    </div>
                    <div class="postpilot-external-ai-sharing__buttons">
                        <?php foreach ($enabled_providers as $key => $name): ?>
                                <a href="<?php echo esc_url($this->get_external_ai_url($key, $post_url)); ?>" 
                                   class="postpilot-external-ai-sharing__button postpilot-external-ai-sharing__button--<?php echo esc_attr($key); ?>"
                                   target="_blank"
                                   rel="noopener noreferrer">
                                    <?php echo esc_html($name); ?>
                                </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php
                $output = ob_get_clean();

                /**
                 * Filter the external AI sharing output
                 *
                 * @since 1.0.0
                 * @param string $output The external AI sharing HTML output.
                 * @param int    $post_id The post ID.
                 * @param array  $enabled_providers The enabled providers array.
                 */
                return apply_filters('postpilot_external_ai_sharing_output', $output, $post_id, $enabled_providers);
    }

    /**
     * Get External AI URL
     *
     * @since 1.0.0
     * @param string $provider The provider key (chatgpt, claude, perplexity, grok).
     * @param string $post_url The post URL.
     * @return string The external AI URL
     */
    private function get_external_ai_url($provider, $post_url)
    {
        // Build the prompt
        $prompt = sprintf(
            'Summarize the content at %s. Focus on key ideas, actionable insights, and clarity.',
            $post_url
        );

        // URL encode the prompt
        $encoded_prompt = rawurlencode($prompt);

        // Build provider-specific URLs
        $urls = array(
            'chatgpt' => 'https://chat.openai.com/?q=' . $encoded_prompt,
            'claude' => 'https://claude.ai/new?q=' . $encoded_prompt,
            'perplexity' => 'https://www.perplexity.ai/?q=' . $encoded_prompt,
            'grok' => 'https://grok.x.ai/?q=' . $encoded_prompt,
        );

        return isset($urls[$provider]) ? $urls[$provider] : '';
    }
}
