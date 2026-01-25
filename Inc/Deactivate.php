<?php
/**
 * Deactivate.php
 *
 * Handles plugin deactivation tasks.
 *
 * @package PostPilot\Inc
 * @since 1.0.0
 */

namespace PostPilot;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
use PostPilot\Helpers\Cache;
/**
 * Deactivate Class
 *
 * Handles all plugin deactivation tasks including cleanup.
 *
 * @package PostPilot\Inc
 * @since 1.0.0
 */
class Deactivate
{
    /**
     * Plugin deactivation callback
     *
     * @since 1.0.0
     * @return void
     */
    public static function deactivate()
    {
        // Clear any cached data
        self::clear_cache();

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Clear plugin cache
     *
     * @since 1.0.0
     * @return void
     */
    private static function clear_cache()
    {
        if (class_exists('\PostPilot\Helpers\Cache')) {
            Cache::clear_all();
        }
    }
}