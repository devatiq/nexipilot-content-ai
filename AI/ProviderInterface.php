<?php
/**
 * ProviderInterface.php
 *
 * Interface for AI providers.
 *
 * @package PostPilot\AI
 * @since 1.0.0
 */

namespace PostPilot\AI;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * ProviderInterface
 *
 * Defines the contract that all AI providers must implement.
 *
 * @package PostPilot\AI
 * @since 1.0.0
 */
interface ProviderInterface
{
    /**
     * Generate FAQ from content
     *
     * @since 1.0.0
     * @param string $content The post content.
     * @return array|WP_Error Array of FAQ items or WP_Error on failure
     */
    public function generate_faq($content);

    /**
     * Generate summary from content
     *
     * @since 1.0.0
     * @param string $content The post content.
     * @return string|WP_Error Summary text or WP_Error on failure
     */
    public function generate_summary($content);

    /**
     * Suggest internal links
     *
     * @since 1.0.0
     * @param string $content The post content.
     * @param array  $available_posts Available posts for linking.
     * @return array|WP_Error Array of link suggestions or WP_Error on failure
     */
    public function suggest_internal_links($content, $available_posts);

    /**
     * Validate API key
     *
     * @since 1.0.0
     * @param string $api_key The API key to validate.
     * @return bool|WP_Error True if valid, WP_Error on failure
     */
    public function validate_api_key($api_key);
}
