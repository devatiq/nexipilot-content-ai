<?php
/**
 * Sanitizer.php
 *
 * Provides sanitization utilities for the plugin.
 *
 * @package PostPilot\Helpers
 * @since 1.0.0
 */

namespace PostPilot\Helpers;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Sanitizer Class
 *
 * Provides methods for sanitizing various types of input data.
 *
 * @package PostPilot\Helpers
 * @since 1.0.0
 */
class Sanitizer
{
    /**
     * Sanitize API key
     *
     * @since 1.0.0
     * @param string $api_key The API key to sanitize.
     * @return string Sanitized API key
     */
    public static function sanitize_api_key($api_key)
    {
        // Only trim whitespace, preserve all other characters (API keys can have various formats)
        return trim(sanitize_text_field($api_key));
    }

    /**
     * Sanitize checkbox value
     *
     * @since 1.0.0
     * @param mixed $value The value to sanitize.
     * @return string '1' or '0'
     */
    public static function sanitize_checkbox($value)
    {
        return ($value === '1' || $value === 1 || $value === true) ? '1' : '0';
    }

    /**
     * Sanitize select option
     *
     * @since 1.0.0
     * @param string $value The value to sanitize.
     * @param array  $allowed_values Array of allowed values.
     * @param string $default Default value if not in allowed values.
     * @return string Sanitized value
     */
    public static function sanitize_select($value, $allowed_values, $default = '')
    {
        return in_array($value, $allowed_values, true) ? $value : $default;
    }

    /**
     * Sanitize AI provider
     *
     * @since 1.0.0
     * @param string $provider The provider to sanitize.
     * @return string Sanitized provider
     */
    public static function sanitize_ai_provider($provider)
    {
        $allowed_providers = array('openai', 'claude');
        return self::sanitize_select($provider, $allowed_providers, 'openai');
    }

    /**
     * Sanitize position option
     *
     * @since 1.0.0
     * @param string $position The position to sanitize.
     * @return string Sanitized position
     */
    public static function sanitize_position($position)
    {
        $allowed_positions = array('before_content', 'after_content');
        return self::sanitize_select($position, $allowed_positions, 'after_content');
    }

    /**
     * Sanitize HTML content
     *
     * @since 1.0.0
     * @param string $content The content to sanitize.
     * @return string Sanitized content
     */
    public static function sanitize_html_content($content)
    {
        $allowed_tags = array(
            'p' => array(),
            'br' => array(),
            'strong' => array(),
            'em' => array(),
            'a' => array(
                'href' => array(),
                'title' => array(),
                'target' => array(),
            ),
            'ul' => array(),
            'ol' => array(),
            'li' => array(),
            'h2' => array(),
            'h3' => array(),
            'h4' => array(),
            'div' => array(
                'class' => array(),
            ),
        );

        return wp_kses($content, $allowed_tags);
    }
}
