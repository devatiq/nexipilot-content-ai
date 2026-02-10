<?php
/**
 * Rate Limiter Helper
 *
 * Handles rate limiting for FAQ generation to prevent abuse
 *
 * @package PostPilot
 * @since 1.0.0
 */

namespace NexiPilot\Helpers;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Rate Limiter class
 */
class RateLimiter
{
    /**
     * Get per-post generation limit
     * 
     * @return int
     */
    public static function get_post_limit()
    {
        // Allow premium versions to override via filter
        return apply_filters('nexipilot_rate_limit_post', 2);
    }

    /**
     * Get per-post time window
     *
     * @return int Seconds
     */
    public static function get_post_window()
    {
        // Allow premium versions to override via filter
        return apply_filters('nexipilot_rate_limit_post_window', 300);
    }

    /**
     * Get daily generation limit
     *
     * @return int
     */
    public static function get_daily_limit()
    {
        // Allow premium versions to override via filter
        return apply_filters('nexipilot_rate_limit_daily', 30);
    }

    /**
     * Get daily time window
     *
     * @return int Seconds
     */
    public static function get_daily_window()
    {
        // Allow premium versions to override via filter
        return apply_filters('nexipilot_rate_limit_daily_window', 86400);
    }

    /**
     * Check if user can generate FAQ for a post
     *
     * @param int $user_id User ID
     * @param int $post_id Post ID
     * @return bool True if allowed, false if rate limited
     */
    public static function can_generate_faq($user_id, $post_id)
    {
        // Check per-post limit
        if (!self::check_post_limit($user_id, $post_id)) {
            return false;
        }

        // Check daily limit
        if (!self::check_daily_limit($user_id)) {
            return false;
        }

        return true;
    }

    /**
     * Record a FAQ generation attempt
     *
     * @param int $user_id User ID
     * @param int $post_id Post ID
     * @return void
     */
    public static function record_generation($user_id, $post_id)
    {
        // Record for per-post tracking
        self::record_post_generation($user_id, $post_id);

        // Record for daily tracking
        self::record_daily_generation($user_id);
    }

    /**
     * Check per-post rate limit
     *
     * @param int $user_id User ID
     * @param int $post_id Post ID
     * @return bool True if allowed
     */
    private static function check_post_limit($user_id, $post_id)
    {
        $key = self::get_post_transient_key($user_id, $post_id);
        $attempts = get_transient($key);

        if ($attempts === false) {
            // No attempts recorded, allow
            return true;
        }

        // Check if limit exceeded
        return count($attempts) < self::get_post_limit();
    }

    /**
     * Check daily rate limit
     *
     * @param int $user_id User ID
     * @return bool True if allowed
     */
    private static function check_daily_limit($user_id)
    {
        $key = self::get_daily_transient_key($user_id);
        $count = get_transient($key);

        if ($count === false) {
            // No attempts recorded, allow
            return true;
        }

        // Check if limit exceeded
        return $count < self::get_daily_limit();
    }

    /**
     * Record post generation attempt
     *
     * @param int $user_id User ID
     * @param int $post_id Post ID
     * @return void
     */
    private static function record_post_generation($user_id, $post_id)
    {
        $key = self::get_post_transient_key($user_id, $post_id);
        $attempts = get_transient($key);

        if ($attempts === false) {
            $attempts = [];
        }

        // Add current timestamp
        $attempts[] = time();

        // Keep only attempts within the window
        $cutoff = time() - self::get_post_window();
        $attempts = array_filter($attempts, function ($timestamp) use ($cutoff) {
            return $timestamp > $cutoff;
        });

        // Reset array keys
        $attempts = array_values($attempts);

        // Store with expiration
        set_transient($key, $attempts, self::get_post_window());
    }

    /**
     * Record daily generation attempt
     *
     * @param int $user_id User ID
     * @return void
     */
    private static function record_daily_generation($user_id)
    {
        $key = self::get_daily_transient_key($user_id);
        $count = get_transient($key);

        if ($count === false) {
            $count = 0;
        }

        $count++;

        // Store with 24-hour expiration
        set_transient($key, $count, self::get_daily_window());
    }

    /**
     * Get remaining attempts for a post
     *
     * @param int $user_id User ID
     * @param int $post_id Post ID
     * @return int Remaining attempts
     */
    public static function get_post_remaining($user_id, $post_id)
    {
        $key = self::get_post_transient_key($user_id, $post_id);
        $attempts = get_transient($key);

        if ($attempts === false) {
            return self::get_post_limit();
        }

        $remaining = self::get_post_limit() - count($attempts);
        return max(0, $remaining);
    }

    /**
     * Get remaining daily attempts for user
     *
     * @param int $user_id User ID
     * @return int Remaining attempts
     */
    public static function get_daily_remaining($user_id)
    {
        $key = self::get_daily_transient_key($user_id);
        $count = get_transient($key);

        if ($count === false) {
            return self::get_daily_limit();
        }

        $remaining = self::get_daily_limit() - $count;
        return max(0, $remaining);
    }

    /**
     * Get time until next allowed generation
     *
     * @param int $user_id User ID
     * @param int $post_id Post ID
     * @return int Seconds until next allowed generation
     */
    public static function get_wait_time($user_id, $post_id)
    {
        // Check per-post limit first
        $post_key = self::get_post_transient_key($user_id, $post_id);
        $attempts = get_transient($post_key);

        if ($attempts !== false && count($attempts) >= self::get_post_limit()) {
            // Get oldest attempt timestamp
            $oldest = min($attempts);
            $wait_until = $oldest + self::get_post_window();
            $wait_time = $wait_until - time();

            if ($wait_time > 0) {
                return $wait_time;
            }
        }

        // Check daily limit
        $daily_key = self::get_daily_transient_key($user_id);
        $count = get_transient($daily_key);

        if ($count !== false && $count >= self::get_daily_limit()) {
            // Get transient timeout
            $timeout = get_option('_transient_timeout_' . $daily_key);
            if ($timeout) {
                $wait_time = $timeout - time();
                return max(0, $wait_time);
            }
        }

        return 0;
    }

    /**
     * Get transient key for per-post tracking
     *
     * @param int $user_id User ID
     * @param int $post_id Post ID
     * @return string Transient key
     */
    private static function get_post_transient_key($user_id, $post_id)
    {
        return sprintf('nexipilot_rate_post_%d_%d', $post_id, $user_id);
    }

    /**
     * Get transient key for daily tracking
     *
     * @param int $user_id User ID
     * @return string Transient key
     */
    private static function get_daily_transient_key($user_id)
    {
        return sprintf('nexipilot_rate_daily_%d', $user_id);
    }

    /**
     * Clear rate limits for a user (admin function)
     *
     * @param int $user_id User ID
     * @return void
     */
    public static function clear_user_limits($user_id)
    {
        // Clear daily limit
        $daily_key = self::get_daily_transient_key($user_id);
        delete_transient($daily_key);

        // Note: Per-post limits will expire naturally
    }
}
