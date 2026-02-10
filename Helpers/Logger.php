<?php
/**
 * Logger.php
 *
 * Provides logging functionality for the plugin.
 *
 * @package NexiPilot\Helpers
 * @since 1.0.0
 */

namespace NexiPilot\Helpers;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logger Class
 *
 * Provides methods for logging debug information and errors.
 *
 * @package NexiPilot\Helpers
 * @since 1.0.0
 */
class Logger
{
    /**
     * Log a debug message
     *
     * @since 1.0.0
     * @param string $message The message to log.
     * @param array  $context Additional context data.
     * @return void
     */
    public static function debug($message, $context = array())
    {
        // Check if plugin debug logging is enabled
        $debug_enabled = get_option('nexipilot_enable_debug_logging', '');

        if (defined('WP_DEBUG') && WP_DEBUG && $debug_enabled === '1') {
            self::log('DEBUG', $message, $context);
        }
    }

    /**
     * Log an info message
     *
     * @since 1.0.0
     * @param string $message The message to log.
     * @param array  $context Additional context data.
     * @return void
     */
    public static function info($message, $context = array())
    {
        // Check if plugin debug logging is enabled
        $debug_enabled = get_option('nexipilot_enable_debug_logging', '');

        if ($debug_enabled === '1') {
            self::log('INFO', $message, $context);
        }
    }

    /**
     * Log an error message
     *
     * @since 1.0.0
     * @param string $message The message to log.
     * @param array  $context Additional context data.
     * @return void
     */
    public static function error($message, $context = array())
    {
        // Check if plugin debug logging is enabled
        $debug_enabled = get_option('nexipilot_enable_debug_logging', '');

        if ($debug_enabled === '1') {
            self::log('ERROR', $message, $context);
        }
    }

    /**
     * Log a message
     *
     * @since 1.0.0
     * @param string $level The log level.
     * @param string $message The message to log.
     * @param array  $context Additional context data.
     * @return void
     */
    private static function log($level, $message, $context = array())
    {
        if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            $log_message = sprintf(
                '[PostPilot][%s] %s',
                $level,
                $message
            );

            if (!empty($context)) {
                $log_message .= ' | Context: ' . wp_json_encode($context);
            }

            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
            error_log($log_message);
        }
    }

    /**
     * Log API request
     *
     * @since 1.0.0
     * @param string $provider The AI provider.
     * @param string $endpoint The API endpoint.
     * @param array  $request_data The request data.
     * @return void
     */
    public static function log_api_request($provider, $endpoint, $request_data = array())
    {
        self::debug(
            sprintf('API Request to %s', $provider),
            array(
                'endpoint' => $endpoint,
                'request_data' => $request_data,
            )
        );
    }

    /**
     * Log API response
     *
     * @since 1.0.0
     * @param string $provider The AI provider.
     * @param mixed  $response The API response.
     * @param bool   $is_error Whether this is an error response.
     * @return void
     */
    public static function log_api_response($provider, $response, $is_error = false)
    {
        $method = $is_error ? 'error' : 'debug';
        self::$method(
            sprintf('API Response from %s', $provider),
            array(
                'response' => $response,
            )
        );
    }
}
