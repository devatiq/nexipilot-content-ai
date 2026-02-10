<?php
/**
 * Cache.php
 *
 * Cache utilities (transients) for PostPilot.
 *
 * IMPORTANT:
 * - This avoids direct DB queries ($wpdb) to satisfy PHPCS.
 * - Add your transient keys in get_transient_keys().
 *
 * @package NexiPilot\Helpers
 * @since 1.0.0
 */

namespace NexiPilot\Helpers;

if (!defined('ABSPATH')) {
    exit;
}

class Cache
{

    /**
     * Transient prefix (optional for consistency).
     *
     * @since 1.0.0
     * @var string
     */
    private static $prefix = 'nexipilot_';

    /**
     * Return all transient keys used by the plugin.
     *
     * NOTE: You must list the keys you actually set via set_transient()/set_site_transient().
     *
     * @since 1.0.0
     * @return string[]
     */
    public static function get_transient_keys(): array
    {
        return array(
            self::$prefix . 'openai_validation',
            self::$prefix . 'claude_validation',
            self::$prefix . 'faq_generation_lock',
            self::$prefix . 'summary_generation_lock',
            self::$prefix . 'internal_links_lock',
            self::$prefix . 'rate_limit_daily',
            self::$prefix . 'rate_limit_post',
        );
    }

    /**
     * Clear plugin transients safely (no direct DB queries).
     *
     * @since 1.0.0
     * @return void
     */
    public static function clear_all(): void
    {
        $keys = self::get_transient_keys();

        if (empty($keys)) {
            return;
        }

        foreach ($keys as $key) {
            delete_transient($key);
            delete_site_transient($key);
        }
    }
}