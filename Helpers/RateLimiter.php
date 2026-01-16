<?php
/**
 * Rate Limiter Helper
 *
 * Handles rate limiting for FAQ generation to prevent abuse
 *
 * @package PostPilot
 * @since 1.0.0
 */

namespace PostPilot\Helpers;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Rate Limiter class
 */
class RateLimiter
{
    /**
     * Rate limit configuration
     */
    const POST_LIMIT = 2;           // Generations per post
    const POST_WINDOW = 300;        // 5 minutes in seconds
    const DAILY_LIMIT = 30;         // Generations per day
    const DAILY_WINDOW = 86400;     // 24 hours in seconds

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
        return count($attempts) < self::POST_LIMIT;
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
        return $count < self::DAILY_LIMIT;
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
        $cutoff = time() - self::POST_WINDOW;
        $attempts = array_filter($attempts, function($timestamp) use ($cutoff) {
            return $timestamp > $cutoff;
        });

        // Reset array keys
        $attempts = array_values($attempts);

        // Store with expiration
        set_transient($key, $attempts, self::POST_WINDOW);
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
        set_transient($key, $count, self::DAILY_WINDOW);
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
            return self::POST_LIMIT;
        }

        $remaining = self::POST_LIMIT - count($attempts);
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
            return self::DAILY_LIMIT;
        }

        $remaining = self::DAILY_LIMIT - $count;
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

        if ($attempts !== false && count($attempts) >= self::POST_LIMIT) {
            // Get oldest attempt timestamp
            $oldest = min($attempts);
            $wait_until = $oldest + self::POST_WINDOW;
            $wait_time = $wait_until - time();
            
            if ($wait_time > 0) {
                return $wait_time;
            }
        }

        // Check daily limit
        $daily_key = self::get_daily_transient_key($user_id);
        $count = get_transient($daily_key);

        if ($count !== false && $count >= self::DAILY_LIMIT) {
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
        return sprintf('postpilot_rate_post_%d_%d', $post_id, $user_id);
    }

    /**
     * Get transient key for daily tracking
     *
     * @param int $user_id User ID
     * @return string Transient key
     */
    private static function get_daily_transient_key($user_id)
    {
        return sprintf('postpilot_rate_daily_%d', $user_id);
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
