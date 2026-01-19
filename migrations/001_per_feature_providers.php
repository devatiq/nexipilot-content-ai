<?php
/**
 * Migration: Single provider to per-feature providers
 * 
 * Migrates from postpilot_ai_provider to feature-specific providers
 * 
 * @package PostPilot\Migrations
 * @since 2.0.0
 */

namespace PostPilot\Migrations;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Migrate to per-feature AI providers
 * 
 * This migration runs once to convert the old single-provider system
 * to the new per-feature provider system.
 * 
 * @since 2.0.0
 * @return bool True on success, false on failure
 */
function migrate_to_per_feature_providers()
{
    $current_version = get_option('postpilot_db_version', '1.0.0');

    // Check if already migrated
    if (version_compare($current_version, '2.0.0', '>=')) {
        return true; // Already migrated
    }

    // Get current provider (fallback to openai if not set)
    $current_provider = get_option('postpilot_ai_provider', 'openai');

    // Assign current provider to all features
    update_option('postpilot_faq_provider', $current_provider);
    update_option('postpilot_summary_provider', $current_provider);
    update_option('postpilot_internal_links_provider', $current_provider);

    // Update database version
    update_option('postpilot_db_version', '2.0.0');

    // Log migration for debugging
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log(sprintf(
            '[PostPilot Migration] Successfully migrated to per-feature providers. All features assigned to: %s',
            $current_provider
        ));
    }

    return true;
}

/**
 * Rollback migration (for development/testing)
 * 
 * @since 2.0.0
 * @return bool True on success
 */
function rollback_per_feature_providers()
{
    delete_option('postpilot_faq_provider');
    delete_option('postpilot_summary_provider');
    delete_option('postpilot_internal_links_provider');
    update_option('postpilot_db_version', '1.0.0');

    return true;
}
