<?php
/**
 * Settings.php
 *
 * WordPress Settings API implementation for PostPilot.
 *
 * @package NexiPilot\Admin
 * @since 1.0.0
 */

namespace NexiPilot\Admin;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use NexiPilot\Helpers\Sanitizer;
use NexiPilot\AI\Manager as AIManager;

/**
 * Settings Class
 *
 * Handles all plugin settings using WordPress Settings API.
 *
 * @package NexiPilot\Admin
 * @since 1.0.0
 */
class Settings
{
    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));

        // Add validation hooks for API keys
        add_action('update_option_nexipilot_openai_api_key', array($this, 'validate_openai_key'), 10, 2);
        add_action('update_option_nexipilot_claude_api_key', array($this, 'validate_claude_key'), 10, 2);
        add_action('update_option_nexipilot_gemini_api_key', array($this, 'validate_gemini_key'), 10, 2);
        add_action('update_option_nexipilot_grok_api_key', array($this, 'validate_grok_key'), 10, 2);
    }

    /**
     * Add admin menu
     *
     * @since 1.0.0
     * @return void
     */
    public function add_admin_menu()
    {
        add_menu_page(
            __('NexiPilot Content AI', 'nexipilot-content-ai'),
            __('NexiPilot', 'nexipilot-content-ai'),
            'manage_options',
            'nexipilot-settings',
            array($this, 'render_settings_page'),
            'dashicons-superhero-alt',
            30
        );
    }

    /**
     * Register settings
     *
     * @since 1.0.0
     * @return void
     */
    public function register_settings()
    {
        // Register AI Provider Settings
        register_setting(
            'nexipilot_settings',
            'nexipilot_ai_provider',
            array(
                'type' => 'string',
                'sanitize_callback' => array(Sanitizer::class, 'sanitize_ai_provider'),
                'default' => 'openai',
            )
        );

        register_setting(
            'nexipilot_settings',
            'nexipilot_openai_api_key',
            array(
                'type' => 'string',
                'sanitize_callback' => array(Sanitizer::class, 'sanitize_api_key'),
                'default' => '',
            )
        );

        register_setting(
            'nexipilot_settings',
            'nexipilot_claude_api_key',
            array(
                'type' => 'string',
                'sanitize_callback' => array(Sanitizer::class, 'sanitize_api_key'),
                'default' => '',
            )
        );

        register_setting(
            'nexipilot_settings',
            'nexipilot_gemini_api_key',
            array(
                'type' => 'string',
                'sanitize_callback' => array(Sanitizer::class, 'sanitize_api_key'),
                'default' => '',
            )
        );

        // Register Model Selection Settings
        register_setting(
            'nexipilot_settings',
            'nexipilot_openai_model',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'gpt-3.5-turbo',
            )
        );

        register_setting(
            'nexipilot_settings',
            'nexipilot_claude_model',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'claude-3-haiku-20240307',
            )
        );

        register_setting(
            'nexipilot_settings',
            'nexipilot_gemini_model',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'gemini-2.5-flash',
            )
        );

        register_setting(
            'nexipilot_settings',
            'nexipilot_grok_api_key',
            array(
                'type' => 'string',
                'sanitize_callback' => array(Sanitizer::class, 'sanitize_api_key'),
                'default' => '',
            )
        );

        register_setting(
            'nexipilot_settings',
            'nexipilot_grok_model',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'grok-beta',
            )
        );

        // Register Per-Feature Provider Settings (v2.0.0)
        register_setting(
            'nexipilot_settings',
            'nexipilot_faq_provider',
            array(
                'type' => 'string',
                'sanitize_callback' => array(Sanitizer::class, 'sanitize_ai_provider'),
                'default' => 'openai',
            )
        );

        register_setting(
            'nexipilot_settings',
            'nexipilot_summary_provider',
            array(
                'type' => 'string',
                'sanitize_callback' => array(Sanitizer::class, 'sanitize_ai_provider'),
                'default' => 'openai',
            )
        );

        register_setting(
            'nexipilot_settings',
            'nexipilot_internal_links_provider',
            array(
                'type' => 'string',
                'sanitize_callback' => array(Sanitizer::class, 'sanitize_ai_provider'),
                'default' => 'openai',
            )
        );

        // Register Feature Settings
        register_setting(
            'nexipilot_settings',
            'nexipilot_enable_faq',
            array(
                'type' => 'string',
                'sanitize_callback' => array(Sanitizer::class, 'sanitize_checkbox'),
                'default' => '1',
            )
        );

        register_setting(
            'nexipilot_settings',
            'nexipilot_enable_summary',
            array(
                'type' => 'string',
                'sanitize_callback' => array(Sanitizer::class, 'sanitize_checkbox'),
                'default' => '1',
            )
        );

        register_setting(
            'nexipilot_settings',
            'nexipilot_enable_internal_links',
            array(
                'type' => 'string',
                'sanitize_callback' => array(Sanitizer::class, 'sanitize_checkbox'),
                'default' => '1',
            )
        );

        register_setting(
            'nexipilot_settings',
            'nexipilot_faq_position',
            array(
                'type' => 'string',
                'sanitize_callback' => array(Sanitizer::class, 'sanitize_position'),
                'default' => 'after_content',
            )
        );

        register_setting(
            'nexipilot_settings',
            'nexipilot_faq_default_layout',
            array(
                'type' => 'string',
                'sanitize_callback' => array(Sanitizer::class, 'sanitize_faq_layout'),
                'default' => 'accordion',
            )
        );

        register_setting(
            'nexipilot_settings',
            'nexipilot_summary_position',
            array(
                'type' => 'string',
                'sanitize_callback' => array(Sanitizer::class, 'sanitize_position'),
                'default' => 'before_content',
            )
        );

        register_setting(
            'nexipilot_settings',
            'nexipilot_enable_debug_logging',
            array(
                'type' => 'string',
                'sanitize_callback' => array(Sanitizer::class, 'sanitize_checkbox'),
                'default' => '',
            )
        );

        // External AI Sharing Settings
        register_setting(
            'nexipilot_settings',
            'nexipilot_enable_external_ai_sharing',
            array(
                'type' => 'string',
                'sanitize_callback' => array(Sanitizer::class, 'sanitize_checkbox'),
                'default' => '1',
            )
        );

        register_setting(
            'nexipilot_settings',
            'nexipilot_external_ai_position',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'after_content',
            )
        );

        register_setting(
            'nexipilot_settings',
            'nexipilot_external_ai_chatgpt',
            array(
                'type' => 'string',
                'sanitize_callback' => array(Sanitizer::class, 'sanitize_checkbox'),
                'default' => '1',
            )
        );

        register_setting(
            'nexipilot_settings',
            'nexipilot_external_ai_claude',
            array(
                'type' => 'string',
                'sanitize_callback' => array(Sanitizer::class, 'sanitize_checkbox'),
                'default' => '1',
            )
        );

        register_setting(
            'nexipilot_settings',
            'nexipilot_external_ai_perplexity',
            array(
                'type' => 'string',
                'sanitize_callback' => array(Sanitizer::class, 'sanitize_checkbox'),
                'default' => '1',
            )
        );

        register_setting(
            'nexipilot_settings',
            'nexipilot_external_ai_grok',
            array(
                'type' => 'string',
                'sanitize_callback' => array(Sanitizer::class, 'sanitize_checkbox'),
                'default' => '1',
            )
        );

        register_setting(
            'nexipilot_settings',
            'nexipilot_external_ai_copilot',
            array(
                'type' => 'string',
                'sanitize_callback' => array(Sanitizer::class, 'sanitize_checkbox'),
                'default' => '1',
            )
        );

        register_setting(
            'nexipilot_settings',
            'nexipilot_external_ai_google',
            array(
                'type' => 'string',
                'sanitize_callback' => array(Sanitizer::class, 'sanitize_checkbox'),
                'default' => '1',
            )
        );

        register_setting(
            'nexipilot_settings',
            'nexipilot_external_ai_heading',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'Summarize this post with:',
            )
        );

        // Add settings sections
        add_settings_section(
            'nexipilot_ai_section',
            __('AI Provider Configuration', 'nexipilot-content-ai'),
            array($this, 'render_ai_section_description'),
            'nexipilot-settings'
        );

        add_settings_section(
            'nexipilot_features_section',
            __('Feature Settings', 'nexipilot-content-ai'),
            array($this, 'render_features_section_description'),
            'nexipilot-settings'
        );

        // Add settings fields - AI Provider
        add_settings_field(
            'nexipilot_ai_provider',
            __('AI Provider', 'nexipilot-content-ai'),
            array($this, 'render_ai_provider_field'),
            'nexipilot-settings',
            'nexipilot_ai_section'
        );

        add_settings_field(
            'nexipilot_openai_api_key',
            __('OpenAI API Key', 'nexipilot-content-ai'),
            array($this, 'render_openai_api_key_field'),
            'nexipilot-settings',
            'nexipilot_ai_section'
        );

        add_settings_field(
            'nexipilot_claude_api_key',
            __('Claude API Key', 'nexipilot-content-ai'),
            array($this, 'render_claude_api_key_field'),
            'nexipilot-settings',
            'nexipilot_ai_section'
        );

        add_settings_field(
            'nexipilot_gemini_api_key',
            __('Gemini API Key', 'nexipilot-content-ai'),
            array($this, 'render_gemini_api_key_field'),
            'nexipilot-settings',
            'nexipilot_ai_section'
        );

        // Add Model Selection Fields
        add_settings_field(
            'nexipilot_openai_model',
            __('OpenAI Model', 'nexipilot-content-ai'),
            array($this, 'render_openai_model_field'),
            'nexipilot-settings',
            'nexipilot_ai_section'
        );

        add_settings_field(
            'nexipilot_claude_model',
            __('Claude Model', 'nexipilot-content-ai'),
            array($this, 'render_claude_model_field'),
            'nexipilot-settings',
            'nexipilot_ai_section'
        );

        add_settings_field(
            'nexipilot_gemini_model',
            __('Gemini Model', 'nexipilot-content-ai'),
            array($this, 'render_gemini_model_field'),
            'nexipilot-settings',
            'nexipilot_ai_section'
        );

        // Add settings fields - Features
        add_settings_field(
            'nexipilot_enable_faq',
            __('Enable FAQ Generator', 'nexipilot-content-ai'),
            array($this, 'render_enable_faq_field'),
            'nexipilot-settings',
            'nexipilot_features_section'
        );

        add_settings_field(
            'nexipilot_faq_position',
            __('FAQ Position', 'nexipilot-content-ai'),
            array($this, 'render_faq_position_field'),
            'nexipilot-settings',
            'nexipilot_features_section'
        );

        add_settings_field(
            'nexipilot_faq_default_layout',
            __('Default FAQ Display Style', 'nexipilot-content-ai'),
            array($this, 'render_faq_default_layout_field'),
            'nexipilot-settings',
            'nexipilot_features_section'
        );

        add_settings_field(
            'nexipilot_enable_summary',
            __('Enable Content Summary', 'nexipilot-content-ai'),
            array($this, 'render_enable_summary_field'),
            'nexipilot-settings',
            'nexipilot_features_section'
        );

        add_settings_field(
            'nexipilot_summary_position',
            __('Summary Position', 'nexipilot-content-ai'),
            array($this, 'render_summary_position_field'),
            'nexipilot-settings',
            'nexipilot_features_section'
        );

        add_settings_field(
            'nexipilot_enable_internal_links',
            __('Enable Smart Internal Links', 'nexipilot-content-ai'),
            array($this, 'render_enable_internal_links_field'),
            'nexipilot-settings',
            'nexipilot_features_section'
        );
    }

    /**
     * Render settings page
     *
     * @since 1.0.0
     * @return void
     */
    public function render_settings_page()
    {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }

        // Check if settings were updated (for SweetAlert2 notification)
        $settings_updated = false;

        /**
         * settings-updated is a core query arg added by options.php after a successful settings save.
         * It's read-only and only used here to display a UI notice, no state change or action is performed.
         */
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Core read-only redirect flag from options.php, used only for UI.
        // phpcs:disable WordPress.Security.NonceVerification.Recommended
        if (isset($_GET['settings-updated'], $_GET['page'])) {
            $page = sanitize_key(wp_unslash($_GET['page']));

            if ('nexipilot-settings' === $page) {
                $settings_updated = ('true' === sanitize_text_field(wp_unslash($_GET['settings-updated'])));
            }
        }
        // phpcs:enable WordPress.Security.NonceVerification.Recommended


        ?>
        <div class="wrap nexipilot-settings-wrap" <?php echo $settings_updated ? 'data-settings-saved="true"' : ''; ?>>
            <!-- Header Section -->
            <div class="postpilotai-header">
                <div class="postpilotai-header-content">
                    <div class="postpilotai-header-title">
                        <span class="postpilotai-icon">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" />
                                <path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                        </span>
                        <div>
                            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
                            <p class="postpilotai-subtitle">
                                <?php esc_html_e('AI-Powered Content Enhancement for WordPress', 'nexipilot-content-ai'); ?>
                            </p>
                        </div>
                    </div>
                    <div class="postpilotai-header-actions">
                        <?php
                        // Count configured providers and build tooltip
                        $providers = $this->get_available_providers();
                        $configured = array();
                        $not_configured = array();

                        foreach ($providers as $key => $name) {
                            $status = $this->get_provider_status($key);
                            // Only count 'connected' (verified) as configured, not 'saved' (unverified)
                            if ($status['status'] === 'connected') {
                                $configured[] = $name . ' ' . $status['icon'];
                            } else {
                                $not_configured[] = $name;
                            }
                        }

                        $configured_count = count($configured);
                        $total_providers = count($providers);

                        // Build tooltip
                        $tooltip_parts = array();
                        if (!empty($configured)) {
                            $tooltip_parts[] = __('Connected: ', 'nexipilot-content-ai') . implode(', ', $configured);
                        }
                        if (!empty($not_configured)) {
                            $tooltip_parts[] = __('Not configured: ', 'nexipilot-content-ai') . implode(', ', $not_configured);
                        }
                        $tooltip = implode("\n", $tooltip_parts);

                        // Determine badge text and class
                        if ($configured_count === 0) {
                            $status_text = __('Not Connected', 'nexipilot-content-ai');
                            $status_class = 'postpilotai-status-disconnected';
                        } elseif ($configured_count === $total_providers) {

                            $status_text = sprintf(
                                /* translators: 1: number of connected AI providers, 2: total available AI providers */
                                esc_html__('%1$d of %2$d AI Connected', 'nexipilot-content-ai'),
                                $configured_count,
                                $total_providers
                            );
                            $status_class = 'postpilotai-status-connected';
                        } else {
                            $status_text = sprintf(
                                /* translators: 1: number of connected AI providers, 2: total available AI providers */
                                esc_html__('%1$d of %2$d AI Connected', 'nexipilot-content-ai'),
                                $configured_count,
                                $total_providers
                            );
                            $status_class = 'postpilotai-status-partial';
                        }
                        ?>
                        <span class="postpilotai-status-badge <?php echo esc_attr($status_class); ?>"
                            id="postpilotai-api-status" title="<?php echo esc_attr($tooltip); ?>">
                            <span class="status-dot"></span>
                            <?php echo esc_html($status_text); ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="postpilotai-tabs">
                <button type="button" class="postpilotai-tab" data-tab="features">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                        <path d="M12 2v20M2 12h20" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                        <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" />
                    </svg>
                    <?php esc_html_e('Features', 'nexipilot-content-ai'); ?>
                </button>
                <button type="button" class="postpilotai-tab" data-tab="ai-providers">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                        <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                    <?php esc_html_e('AI Providers', 'nexipilot-content-ai'); ?>
                </button>
                <button type="button" class="postpilotai-tab" data-tab="troubleshooting">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                        <path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <?php esc_html_e('Troubleshooting', 'nexipilot-content-ai'); ?>
                </button>
            </div>

            <form action="options.php" method="post" class="nexipilot-settings-form">
                <?php settings_fields('nexipilot_settings'); ?>

                <!-- AI Providers Tab Content -->
                <div class="postpilotai-tab-content" id="ai-providers-tab">
                    <div class="nexipilot-settings-grid">
                        <!-- OpenAI Provider Card -->
                        <div class="postpilotai-card postpilotai-provider-card">
                            <div class="postpilotai-card-header">
                                <div class="postpilotai-card-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                        <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </div>
                                <div>
                                    <h2><?php esc_html_e('OpenAI (ChatGPT)', 'nexipilot-content-ai'); ?></h2>
                                    <p><?php esc_html_e('Configure OpenAI API credentials and model selection', 'nexipilot-content-ai'); ?>
                                    </p>
                                </div>
                                <?php
                                $openai_status = $this->get_provider_status('openai');
                                ?>
                                <span class="postpilotai-badge <?php echo esc_attr($openai_status['class']); ?>"
                                    title="<?php echo esc_attr($openai_status['tooltip']); ?>">
                                    <?php echo esc_html($openai_status['icon'] . ' ' . $openai_status['text']); ?>
                                </span>
                            </div>
                            <div class="postpilotai-card-body">
                                <!-- API Key Field -->
                                <div class="postpilotai-field-group">
                                    <label for="nexipilot_openai_api_key_providers" class="postpilotai-label">
                                        <?php esc_html_e('API Key', 'nexipilot-content-ai'); ?>
                                        <?php if (get_option('nexipilot_openai_api_key')): ?>
                                            <span class="postpilotai-badge postpilotai-badge-success">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none">
                                                    <path d="M20 6L9 17L4 12" stroke="currentColor" stroke-width="2"
                                                        stroke-linecap="round" />
                                                </svg>
                                                <?php esc_html_e('Saved', 'nexipilot-content-ai'); ?>
                                            </span>
                                        <?php endif; ?>
                                    </label>
                                    <div class="postpilotai-input-group">
                                        <?php
                                        $openai_key = get_option('nexipilot_openai_api_key');
                                        $openai_key_decrypted = !empty($openai_key) ? \PostPilotAI\Helpers\Encryption::decrypt($openai_key) : '';
                                        ?>
                                        <input type="password" name="nexipilot_openai_api_key"
                                            id="nexipilot_openai_api_key_providers" class="postpilotai-input"
                                            value="<?php echo esc_attr($openai_key_decrypted); ?>" placeholder="sk-..." />
                                        <button type="button" class="postpilotai-btn-icon toggle-password">
                                            <svg class="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="none">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor"
                                                    stroke-width="2" />
                                                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" />
                                            </svg>
                                        </button>
                                    </div>
                                    <p class="postpilotai-field-description">
                                        <?php esc_html_e('Get your API key from', 'nexipilot-content-ai'); ?>
                                        <a href="https://platform.openai.com/api-keys" target="_blank"
                                            rel="noopener">platform.openai.com/api-keys</a>
                                    </p>
                                </div>

                                <!-- Model Selection -->
                                <div class="postpilotai-field-group">
                                    <label for="nexipilot_openai_model_providers" class="postpilotai-label">
                                        <?php esc_html_e('Model', 'nexipilot-content-ai'); ?>
                                    </label>
                                    <select name="nexipilot_openai_model" id="nexipilot_openai_model_providers"
                                        class="postpilotai-select">
                                        <option value="gpt-4o" <?php selected(get_option('nexipilot_openai_model', 'gpt-4o'), 'gpt-4o'); ?>>
                                            GPT-4o
                                        </option>
                                        <option value="gpt-4-turbo" <?php selected(get_option('nexipilot_openai_model'), 'gpt-4-turbo'); ?>>
                                            GPT-4 Turbo
                                        </option>
                                        <option value="gpt-3.5-turbo" <?php selected(get_option('nexipilot_openai_model'), 'gpt-3.5-turbo'); ?>>
                                            GPT-3.5 Turbo
                                        </option>
                                    </select>
                                    <p class="postpilotai-field-description">
                                        <strong><?php esc_html_e('This model will be used for ALL features that use OpenAI', 'nexipilot-content-ai'); ?></strong>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Claude Provider Card -->
                        <div class="postpilotai-card postpilotai-provider-card">
                            <div class="postpilotai-card-header">
                                <div class="postpilotai-card-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                        <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </div>
                                <div>
                                    <h2><?php esc_html_e('Anthropic Claude', 'nexipilot-content-ai'); ?></h2>
                                    <p><?php esc_html_e('Configure Claude API credentials and model selection', 'nexipilot-content-ai'); ?>
                                    </p>
                                </div>
                                <?php
                                $claude_status = $this->get_provider_status('claude');
                                ?>
                                <span class="postpilotai-badge <?php echo esc_attr($claude_status['class']); ?>"
                                    title="<?php echo esc_attr($claude_status['tooltip']); ?>">
                                    <?php echo esc_html($claude_status['icon'] . ' ' . $claude_status['text']); ?>
                                </span>
                            </div>
                            <div class="postpilotai-card-body">
                                <!-- API Key Field -->
                                <div class="postpilotai-field-group">
                                    <label for="nexipilot_claude_api_key_providers" class="postpilotai-label">
                                        <?php esc_html_e('API Key', 'nexipilot-content-ai'); ?>
                                        <?php if (get_option('nexipilot_claude_api_key')): ?>
                                            <span class="postpilotai-badge postpilotai-badge-success">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none">
                                                    <path d="M20 6L9 17L4 12" stroke="currentColor" stroke-width="2"
                                                        stroke-linecap="round" />
                                                </svg>
                                                <?php esc_html_e('Saved', 'nexipilot-content-ai'); ?>
                                            </span>
                                        <?php endif; ?>
                                    </label>
                                    <div class="postpilotai-input-group">
                                        <?php
                                        $claude_key = get_option('nexipilot_claude_api_key');
                                        $claude_key_decrypted = !empty($claude_key) ? \PostPilotAI\Helpers\Encryption::decrypt($claude_key) : '';
                                        ?>
                                        <input type="password" name="nexipilot_claude_api_key"
                                            id="nexipilot_claude_api_key_providers" class="postpilotai-input"
                                            value="<?php echo esc_attr($claude_key_decrypted); ?>" placeholder="sk-ant-..." />
                                        <button type="button" class="postpilotai-btn-icon toggle-password">
                                            <svg class="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="none">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor"
                                                    stroke-width="2" />
                                                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" />
                                            </svg>
                                        </button>
                                    </div>
                                    <p class="postpilotai-field-description">
                                        <?php esc_html_e('Get your API key from', 'nexipilot-content-ai'); ?>
                                        <a href="https://console.anthropic.com" target="_blank"
                                            rel="noopener">console.anthropic.com</a>
                                    </p>
                                </div>

                                <!-- Model Selection -->
                                <div class="postpilotai-field-group">
                                    <label for="nexipilot_claude_model_providers" class="postpilotai-label">
                                        <?php esc_html_e('Model', 'nexipilot-content-ai'); ?>
                                    </label>
                                    <select name="nexipilot_claude_model" id="nexipilot_claude_model_providers"
                                        class="postpilotai-select">
                                        <option value="claude-3-5-sonnet-20241022" <?php selected(get_option('nexipilot_claude_model', 'claude-3-5-sonnet-20241022'), 'claude-3-5-sonnet-20241022'); ?>>
                                            Claude 3.5 Sonnet
                                        </option>
                                        <option value="claude-3-opus-20240229" <?php selected(get_option('nexipilot_claude_model'), 'claude-3-opus-20240229'); ?>>
                                            Claude 3 Opus
                                        </option>
                                        <option value="claude-3-haiku-20240307" <?php selected(get_option('nexipilot_claude_model'), 'claude-3-haiku-20240307'); ?>>
                                            Claude 3 Haiku
                                        </option>
                                    </select>
                                    <p class="postpilotai-field-description">
                                        <strong><?php esc_html_e('This model will be used for ALL features that use Claude', 'nexipilot-content-ai'); ?></strong>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Gemini Provider Card -->
                        <div class="postpilotai-card postpilotai-provider-card">
                            <div class="postpilotai-card-header">
                                <div class="postpilotai-card-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                        <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </div>
                                <div>
                                    <h2><?php esc_html_e('Google Gemini', 'nexipilot-content-ai'); ?></h2>
                                    <p><?php esc_html_e('Configure Gemini API credentials and model selection', 'nexipilot-content-ai'); ?>
                                    </p>
                                </div>
                                <?php
                                $gemini_status = $this->get_provider_status('gemini');
                                ?>
                                <span class="postpilotai-badge <?php echo esc_attr($gemini_status['class']); ?>"
                                    title="<?php echo esc_attr($gemini_status['tooltip']); ?>">
                                    <?php echo esc_html($gemini_status['icon'] . ' ' . $gemini_status['text']); ?>
                                </span>
                            </div>
                            <div class="postpilotai-card-body">
                                <!-- API Key Field -->
                                <div class="postpilotai-field-group">
                                    <label for="nexipilot_gemini_api_key_providers" class="postpilotai-label">
                                        <?php esc_html_e('API Key', 'nexipilot-content-ai'); ?>
                                        <?php if (get_option('nexipilot_gemini_api_key')): ?>
                                            <span class="postpilotai-badge postpilotai-badge-success">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none">
                                                    <path d="M20 6L9 17L4 12" stroke="currentColor" stroke-width="2"
                                                        stroke-linecap="round" />
                                                </svg>
                                                <?php esc_html_e('Saved', 'nexipilot-content-ai'); ?>
                                            </span>
                                        <?php endif; ?>
                                    </label>
                                    <div class="postpilotai-input-group">
                                        <?php
                                        $gemini_key = get_option('nexipilot_gemini_api_key');
                                        $gemini_key_decrypted = !empty($gemini_key) ? \PostPilotAI\Helpers\Encryption::decrypt($gemini_key) : '';
                                        ?>
                                        <input type="password" name="nexipilot_gemini_api_key"
                                            id="nexipilot_gemini_api_key_providers" class="postpilotai-input"
                                            value="<?php echo esc_attr($gemini_key_decrypted); ?>" placeholder="AIza..." />
                                        <button type="button" class="postpilotai-btn-icon toggle-password">
                                            <svg class="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="none">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor"
                                                    stroke-width="2" />
                                                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" />
                                            </svg>
                                        </button>
                                    </div>
                                    <p class="postpilotai-field-description">
                                        <?php esc_html_e('Get your API key from', 'nexipilot-content-ai'); ?>
                                        <a href="https://aistudio.google.com/app/apikey" target="_blank"
                                            rel="noopener">aistudio.google.com/app/apikey</a>
                                    </p>
                                </div>

                                <!-- Model Selection -->
                                <div class="postpilotai-field-group">
                                    <label for="nexipilot_gemini_model_providers" class="postpilotai-label">
                                        <?php esc_html_e('Model', 'nexipilot-content-ai'); ?>
                                    </label>
                                    <select name="nexipilot_gemini_model" id="nexipilot_gemini_model_providers"
                                        class="postpilotai-select">
                                        <option value="gemini-2.5-flash" <?php selected(get_option('nexipilot_gemini_model', 'gemini-2.5-flash'), 'gemini-2.5-flash'); ?>>
                                            Gemini 2.5 Flash (Recommended)
                                        </option>
                                        <option value="gemini-2.5-pro" <?php selected(get_option('nexipilot_gemini_model'), 'gemini-2.5-pro'); ?>>
                                            Gemini 2.5 Pro (Most Capable)
                                        </option>
                                        <option value="gemini-2.0-flash" <?php selected(get_option('nexipilot_gemini_model'), 'gemini-2.0-flash'); ?>>
                                            Gemini 2.0 Flash
                                        </option>
                                        <option value="gemini-2.5-flash-lite" <?php selected(get_option('nexipilot_gemini_model'), 'gemini-2.5-flash-lite'); ?>>
                                            Gemini 2.5 Flash-Lite (Fastest)
                                        </option>
                                    </select>
                                    <p class="postpilotai-field-description">
                                        <strong><?php esc_html_e('This model will be used for ALL features that use Gemini', 'nexipilot-content-ai'); ?></strong>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Grok Provider Card -->
                        <div class="postpilotai-card postpilotai-provider-card">
                            <div class="postpilotai-card-header">
                                <div class="postpilotai-card-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                        <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </div>
                                <div>
                                    <h2><?php esc_html_e('xAI Grok', 'nexipilot-content-ai'); ?></h2>
                                    <p><?php esc_html_e('Configure Grok API credentials and model selection', 'nexipilot-content-ai'); ?>
                                    </p>
                                </div>
                                <?php
                                $grok_status = $this->get_provider_status('grok');
                                ?>
                                <span class="postpilotai-badge <?php echo esc_attr($grok_status['class']); ?>"
                                    title="<?php echo esc_attr($grok_status['tooltip']); ?>">
                                    <?php echo esc_html($grok_status['icon'] . ' ' . $grok_status['text']); ?>
                                </span>
                            </div>
                            <div class="postpilotai-card-body">
                                <!-- API Key Field -->
                                <div class="postpilotai-field-group">
                                    <label for="nexipilot_grok_api_key_providers" class="postpilotai-label">
                                        <?php esc_html_e('API Key', 'nexipilot-content-ai'); ?>
                                        <?php if (get_option('nexipilot_grok_api_key')): ?>
                                            <span class="postpilotai-badge postpilotai-badge-success">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none">
                                                    <path d="M20 6L9 17L4 12" stroke="currentColor" stroke-width="2"
                                                        stroke-linecap="round" />
                                                </svg>
                                                <?php esc_html_e('Saved', 'nexipilot-content-ai'); ?>
                                            </span>
                                        <?php endif; ?>
                                    </label>
                                    <div class="postpilotai-input-group">
                                        <?php
                                        $grok_key = get_option('nexipilot_grok_api_key');
                                        $grok_key_decrypted = !empty($grok_key) ? \PostPilotAI\Helpers\Encryption::decrypt($grok_key) : '';
                                        ?>
                                        <input type="password" name="nexipilot_grok_api_key"
                                            id="nexipilot_grok_api_key_providers" class="postpilotai-input"
                                            value="<?php echo esc_attr($grok_key_decrypted); ?>" placeholder="xai-..." />
                                        <button type="button" class="postpilotai-btn-icon toggle-password">
                                            <svg class="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="none">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor"
                                                    stroke-width="2" />
                                                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" />
                                            </svg>
                                        </button>
                                    </div>
                                    <p class="postpilotai-field-description">
                                        <?php esc_html_e('Get your API key from', 'nexipilot-content-ai'); ?>
                                        <a href="https://console.x.ai/" target="_blank" rel="noopener">console.x.ai</a>
                                    </p>
                                </div>

                                <!-- Model Selection -->
                                <div class="postpilotai-field-group">
                                    <label for="nexipilot_grok_model_providers" class="postpilotai-label">
                                        <?php esc_html_e('Model', 'nexipilot-content-ai'); ?>
                                    </label>
                                    <select name="nexipilot_grok_model" id="nexipilot_grok_model_providers"
                                        class="postpilotai-select">
                                        <option value="grok-beta" <?php selected(get_option('nexipilot_grok_model', 'grok-beta'), 'grok-beta'); ?>>
                                            Grok Beta (Recommended)
                                        </option>
                                        <option value="grok-vision-beta" <?php selected(get_option('nexipilot_grok_model'), 'grok-vision-beta'); ?>>
                                            Grok Vision Beta
                                        </option>
                                    </select>
                                    <p class="postpilotai-field-description">
                                        <strong><?php esc_html_e('This model will be used for ALL features that use Grok', 'nexipilot-content-ai'); ?></strong>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="postpilotai-submit-wrapper">
                        <?php submit_button(__('Save Settings', 'nexipilot-content-ai'), 'primary postpilotai-btn-primary', 'submit', false); ?>
                    </div>
                </div>


                <!-- Features Tab Content -->
                <div class="postpilotai-tab-content" id="features-tab">
                    <div class="nexipilot-settings-grid">
                        <!-- Feature Settings Card -->
                        <div class="postpilotai-card">
                            <div class="postpilotai-card-header">
                                <div class="postpilotai-card-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                        <path d="M12 2v20M2 12h20" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" />
                                        <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" />
                                    </svg>
                                </div>
                                <div>
                                    <h2><?php esc_html_e('Feature Settings', 'nexipilot-content-ai'); ?></h2>
                                    <p><?php esc_html_e('Enable or disable AI-powered features', 'nexipilot-content-ai'); ?></p>
                                </div>
                            </div>
                            <div class="postpilotai-card-body">
                                <!-- FAQ Generator -->
                                <div class="postpilotai-feature-item">
                                    <div class="postpilotai-feature-header">
                                        <div class="postpilotai-feature-info">
                                            <label class="postpilotai-feature-title">
                                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" />
                                                    <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3" stroke="currentColor"
                                                        stroke-width="2" />
                                                    <circle cx="12" cy="17" r="1" fill="currentColor" />
                                                </svg>
                                                <?php esc_html_e('FAQ Generator', 'nexipilot-content-ai'); ?>
                                            </label>
                                            <p class="postpilotai-feature-description">
                                                <?php esc_html_e('Automatically generate frequently asked questions based on your content', 'nexipilot-content-ai'); ?>
                                            </p>
                                        </div>
                                        <label class="postpilotai-toggle">
                                            <input type="checkbox" name="nexipilot_enable_faq" value="1" <?php checked(get_option('nexipilot_enable_faq'), '1'); ?> />
                                            <span class="postpilotai-toggle-slider"></span>
                                        </label>
                                    </div>
                                    <div class="postpilotai-feature-options" id="faq-options">
                                        <!-- AI Provider Selection -->
                                        <div class="postpilotai-field-group">
                                            <label for="nexipilot_faq_provider" class="postpilotai-label-small">
                                                <?php esc_html_e('AI Provider', 'nexipilot-content-ai'); ?>
                                            </label>
                                            <select name="nexipilot_faq_provider" id="nexipilot_faq_provider"
                                                class="postpilotai-select-small">
                                                <?php
                                                $faq_provider_ts = get_option('nexipilot_faq_provider', 'openai');
                                                foreach ($this->get_available_providers() as $key => $name):
                                                    ?>
                                                    <option value="<?php echo esc_attr($key); ?>" <?php selected($faq_provider_ts, $key); ?>>
                                                        <?php echo esc_html($name); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="postpilotai-field-group" id="faq-model-display">
                                            <label class="postpilotai-label-small">
                                                <?php esc_html_e('Model in Use', 'nexipilot-content-ai'); ?>
                                            </label>
                                            <span class="postpilotai-badge postpilotai-badge-info">
                                                <?php
                                                $faq_provider = get_option('nexipilot_faq_provider', 'openai');
                                                $faq_model = get_option("nexipilot_{$faq_provider}_model", '');
                                                echo esc_html($faq_model ?: __('Not configured', 'nexipilot-content-ai'));
                                                ?>
                                            </span>
                                            <p class="postpilotai-field-description">
                                                <?php esc_html_e('Model is configured in the AI Providers tab', 'nexipilot-content-ai'); ?>
                                            </p>
                                        </div>
                                        <div class="postpilotai-field-group">
                                            <label for="nexipilot_faq_position" class="postpilotai-label-small">
                                                <?php esc_html_e('Display Position', 'nexipilot-content-ai'); ?>
                                            </label>
                                            <select name="nexipilot_faq_position" id="nexipilot_faq_position"
                                                class="postpilotai-select-small">
                                                <option value="after_content" <?php selected(get_option('nexipilot_faq_position', 'after_content'), 'after_content'); ?>>
                                                    <?php esc_html_e('After Content', 'nexipilot-content-ai'); ?>
                                                </option>
                                                <option value="before_content" <?php selected(get_option('nexipilot_faq_position'), 'before_content'); ?>>
                                                    <?php esc_html_e('Before Content', 'nexipilot-content-ai'); ?>
                                                </option>
                                            </select>
                                        </div>
                                        <div class="postpilotai-field-group">
                                            <label for="nexipilot_faq_default_layout" class="postpilotai-label-small">
                                                <?php esc_html_e('Default Display Style', 'nexipilot-content-ai'); ?>
                                            </label>
                                            <select name="nexipilot_faq_default_layout" id="nexipilot_faq_default_layout"
                                                class="postpilotai-select-small">
                                                <option value="accordion" <?php selected(get_option('nexipilot_faq_default_layout', 'accordion'), 'accordion'); ?>>
                                                    <?php esc_html_e('Accordion', 'nexipilot-content-ai'); ?>
                                                </option>
                                                <option value="static" <?php selected(get_option('nexipilot_faq_default_layout', 'accordion'), 'static'); ?>>
                                                    <?php esc_html_e('Static', 'nexipilot-content-ai'); ?>
                                                </option>
                                            </select>
                                            <p class="description" style="margin-top: 5px; font-size: 12px;">
                                                <?php esc_html_e('Can be overridden per post', 'nexipilot-content-ai'); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Content Summary -->
                                <div class="postpilotai-feature-item">
                                    <div class="postpilotai-feature-header">
                                        <div class="postpilotai-feature-info">
                                            <label class="postpilotai-feature-title">
                                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                                    <path d="M4 6h16M4 12h16M4 18h16" stroke="currentColor" stroke-width="2"
                                                        stroke-linecap="round" />
                                                </svg>
                                                <?php esc_html_e('Content Summary', 'nexipilot-content-ai'); ?>
                                            </label>
                                            <p class="postpilotai-feature-description">
                                                <?php esc_html_e('Generate concise summaries of your posts for better engagement', 'nexipilot-content-ai'); ?>
                                            </p>
                                        </div>
                                        <label class="postpilotai-toggle">
                                            <input type="checkbox" name="nexipilot_enable_summary" value="1" <?php checked(get_option('nexipilot_enable_summary'), '1'); ?> />
                                            <span class="postpilotai-toggle-slider"></span>
                                        </label>
                                    </div>
                                    <div class="postpilotai-feature-options" id="summary-options">
                                        <!-- AI Provider Selection -->
                                        <div class="postpilotai-field-group">
                                            <label for="nexipilot_summary_provider" class="postpilotai-label-small">
                                                <?php esc_html_e('AI Provider', 'nexipilot-content-ai'); ?>
                                            </label>
                                            <select name="nexipilot_summary_provider" id="nexipilot_summary_provider"
                                                class="postpilotai-select-small">
                                                <?php
                                                $summary_provider_ts = get_option('nexipilot_summary_provider', 'openai');
                                                foreach ($this->get_available_providers() as $key => $name):
                                                    ?>
                                                    <option value="<?php echo esc_attr($key); ?>" <?php selected($summary_provider_ts, $key); ?>>
                                                        <?php echo esc_html($name); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="postpilotai-field-group" id="summary-model-display">
                                            <label class="postpilotai-label-small">
                                                <?php esc_html_e('Model in Use', 'nexipilot-content-ai'); ?>
                                            </label>
                                            <span class="postpilotai-badge postpilotai-badge-info">
                                                <?php
                                                $summary_provider = get_option('nexipilot_summary_provider', 'openai');
                                                $summary_model = get_option("nexipilot_{$summary_provider}_model", '');
                                                echo esc_html($summary_model ?: __('Not configured', 'nexipilot-content-ai'));
                                                ?>
                                            </span>
                                            <p class="postpilotai-field-description">
                                                <?php esc_html_e('Model is configured in the AI Providers tab', 'nexipilot-content-ai'); ?>
                                            </p>
                                        </div>
                                        <div class="postpilotai-field-group">
                                            <label for="nexipilot_summary_position" class="postpilotai-label-small">
                                                <?php esc_html_e('Display Position', 'nexipilot-content-ai'); ?>
                                            </label>
                                            <select name="nexipilot_summary_position" id="nexipilot_summary_position"
                                                class="postpilotai-select-small">
                                                <option value="before_content" <?php selected(get_option('nexipilot_summary_position', 'before_content'), 'before_content'); ?>>
                                                    <?php esc_html_e('Before Content', 'nexipilot-content-ai'); ?>
                                                </option>
                                                <option value="after_content" <?php selected(get_option('nexipilot_summary_position'), 'after_content'); ?>>
                                                    <?php esc_html_e('After Content', 'nexipilot-content-ai'); ?>
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Smart Internal Links -->
                                <div class="postpilotai-feature-item">
                                    <div class="postpilotai-feature-header">
                                        <div class="postpilotai-feature-info">
                                            <label class="postpilotai-feature-title">
                                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                                    <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"
                                                        stroke="currentColor" stroke-width="2" />
                                                    <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"
                                                        stroke="currentColor" stroke-width="2" />
                                                </svg>
                                                <?php esc_html_e('Smart Internal Links', 'nexipilot-content-ai'); ?>
                                            </label>
                                            <p class="postpilotai-feature-description">
                                                <?php esc_html_e('Automatically suggest relevant internal links to improve SEO', 'nexipilot-content-ai'); ?>
                                            </p>
                                        </div>
                                        <label class="postpilotai-toggle">
                                            <input type="checkbox" name="nexipilot_enable_internal_links" value="1" <?php checked(get_option('nexipilot_enable_internal_links'), '1'); ?> />
                                            <span class="postpilotai-toggle-slider"></span>
                                        </label>
                                    </div>
                                    <!-- AI Provider Selection for Internal Links -->
                                    <div class="postpilotai-feature-options" id="links-options" style="margin-top: 15px;">
                                        <div class="postpilotai-field-group">
                                            <label for="nexipilot_internal_links_provider" class="postpilotai-label-small">
                                                <?php esc_html_e('AI Provider', 'nexipilot-content-ai'); ?>
                                            </label>
                                            <select name="nexipilot_internal_links_provider"
                                                id="nexipilot_internal_links_provider" class="postpilotai-select-small">
                                                <?php
                                                $links_provider_ts = get_option('nexipilot_internal_links_provider', 'openai');
                                                foreach ($this->get_available_providers() as $key => $name):
                                                    ?>
                                                    <option value="<?php echo esc_attr($key); ?>" <?php selected($links_provider_ts, $key); ?>>
                                                        <?php echo esc_html($name); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="postpilotai-field-group" id="links-model-display">
                                            <label class="postpilotai-label-small">
                                                <?php esc_html_e('Model in Use', 'nexipilot-content-ai'); ?>
                                            </label>
                                            <span class="postpilotai-badge postpilotai-badge-info">
                                                <?php
                                                $links_provider = get_option('nexipilot_internal_links_provider', 'openai');
                                                $links_model = get_option("nexipilot_{$links_provider}_model", '');
                                                echo esc_html($links_model ?: __('Not configured', 'nexipilot-content-ai'));
                                                ?>
                                            </span>
                                            <p class="postpilotai-field-description">
                                                <?php esc_html_e('Model is configured in the AI Providers tab', 'nexipilot-content-ai'); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- External AI Sharing -->
                                <div class="postpilotai-feature-item">
                                    <div class="postpilotai-feature-header">
                                        <div class="postpilotai-feature-info">
                                            <label class="postpilotai-feature-title">
                                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                                    <path
                                                        d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"
                                                        stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                                        stroke-linejoin="round" />
                                                </svg>
                                                <?php esc_html_e('External AI Sharing', 'nexipilot-content-ai'); ?>
                                            </label>
                                            <p class="postpilotai-feature-description">
                                                <?php esc_html_e('Allow readers to summarize posts with external AI tools', 'nexipilot-content-ai'); ?>
                                            </p>
                                        </div>
                                        <label class="postpilotai-toggle">
                                            <input type="checkbox" name="nexipilot_enable_external_ai_sharing" value="1" <?php checked(get_option('nexipilot_enable_external_ai_sharing', '1'), '1'); ?> />
                                            <span class="postpilotai-toggle-slider"></span>
                                        </label>
                                    </div>
                                    <div class="postpilotai-feature-options" id="external-ai-sharing-options">
                                        <div class="postpilotai-field-group">
                                            <label for="nexipilot_external_ai_position" class="postpilotai-label-small">
                                                <?php esc_html_e('Display Position', 'nexipilot-content-ai'); ?>
                                            </label>
                                            <select name="nexipilot_external_ai_position" id="nexipilot_external_ai_position"
                                                class="postpilotai-select-small">
                                                <option value="before_content" <?php selected(get_option('nexipilot_external_ai_position', 'before_content'), 'before_content'); ?>>
                                                    <?php esc_html_e('Before Content', 'nexipilot-content-ai'); ?>
                                                </option>
                                                <option value="after_content" <?php selected(get_option('nexipilot_external_ai_position', 'before_content'), 'after_content'); ?>>
                                                    <?php esc_html_e('After Content', 'nexipilot-content-ai'); ?>
                                                </option>
                                                <option value="both" <?php selected(get_option('nexipilot_external_ai_position', 'before_content'), 'both'); ?>>
                                                    <?php esc_html_e('Both', 'nexipilot-content-ai'); ?>
                                                </option>
                                            </select>
                                        </div>
                                        <div class="postpilotai-field-group">
                                            <label for="nexipilot_external_ai_heading" class="postpilotai-label-small">
                                                <?php esc_html_e('Heading Text', 'nexipilot-content-ai'); ?>
                                            </label>
                                            <input type="text" name="nexipilot_external_ai_heading"
                                                id="nexipilot_external_ai_heading" class="postpilotai-input-small"
                                                value="<?php echo esc_attr(get_option('nexipilot_external_ai_heading', 'Summarize this post with:')); ?>"
                                                placeholder="<?php esc_attr_e('Summarize this post with:', 'nexipilot-content-ai'); ?>" />
                                            <p class="postpilotai-field-description">
                                                <?php esc_html_e('Customize the heading text displayed above the AI sharing buttons', 'nexipilot-content-ai'); ?>
                                            </p>
                                        </div>
                                        <div class="postpilotai-field-group">
                                            <label class="postpilotai-label-small">
                                                <?php esc_html_e('Enabled AI Providers', 'nexipilot-content-ai'); ?>
                                            </label>
                                            <div class="postpilotai-checkbox-group">
                                                <?php
                                                $providers = array(
                                                    'chatgpt' => array(
                                                        'name' => 'ChatGPT',
                                                        'logo' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M22.282 9.821a5.985 5.985 0 0 0-.516-4.91 6.046 6.046 0 0 0-6.51-2.9A6.065 6.065 0 0 0 4.981 4.18a5.985 5.985 0 0 0-3.998 2.9 6.046 6.046 0 0 0 .743 7.097 5.98 5.98 0 0 0 .51 4.911 6.051 6.051 0 0 0 6.515 2.9A5.985 5.985 0 0 0 13.26 24a6.056 6.056 0 0 0 5.772-4.206 5.99 5.99 0 0 0 3.997-2.9 6.056 6.056 0 0 0-.747-7.073zM13.26 22.43a4.476 4.476 0 0 1-2.876-1.04l.141-.081 4.779-2.758a.795.795 0 0 0 .392-.681v-6.737l2.02 1.168a.071.071 0 0 1 .038.052v5.583a4.504 4.504 0 0 1-4.494 4.494zM3.6 18.304a4.47 4.47 0 0 1-.535-3.014l.142.085 4.783 2.759a.771.771 0 0 0 .78 0l5.843-3.369v2.332a.08.08 0 0 1-.033.062L9.74 19.95a4.5 4.5 0 0 1-6.14-1.646zM2.34 7.896a4.485 4.485 0 0 1 2.366-1.973V11.6a.766.766 0 0 0 .388.676l5.815 3.355-2.02 1.168a.076.076 0 0 1-.071 0l-4.83-2.786A4.504 4.504 0 0 1 2.34 7.872zm16.597 3.855l-5.833-3.387L15.119 7.2a.076.076 0 0 1 .071 0l4.83 2.791a4.494 4.494 0 0 1-.676 8.105v-5.678a.790.790 0 0 0-.407-.667zm2.01-3.023l-.141-.085-4.774-2.782a.776.776 0 0 0-.785 0L9.409 9.23V6.897a.066.066 0 0 1 .028-.061l4.83-2.787a4.5 4.5 0 0 1 6.68 4.66zm-12.64 4.135l-2.02-1.164a.08.08 0 0 1-.038-.057V6.075a4.5 4.5 0 0 1 7.375-3.453l-.142.08L8.704 5.46a.795.795 0 0 0-.393.681zm1.097-2.365l2.602-1.5 2.607 1.5v2.999l-2.597 1.5-2.607-1.5z"/></svg>'
                                                    ),
                                                    'claude' => array(
                                                        'name' => 'Claude',
                                                        'logo' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M17.45 15.18l-3.23-9.05c-.24-.67-.97-1.01-1.64-.77-.67.24-1.01.97-.77 1.64l2.77 7.77-2.77 7.77c-.24.67.1 1.4.77 1.64.67.24 1.4-.1 1.64-.77l3.23-9.05c.24-.67-.1-1.4-.77-1.64-.14-.05-.29-.08-.43-.08-.39 0-.76.21-.95.54zm-4.9-9.05c-.24-.67-.97-1.01-1.64-.77-.67.24-1.01.97-.77 1.64l2.77 7.77-2.77 7.77c-.24.67.1 1.4.77 1.64.67.24 1.4-.1 1.64-.77l3.23-9.05c.24-.67-.1-1.4-.77-1.64-.14-.05-.29-.08-.43-.08-.39 0-.76.21-.95.54l-3.23-9.05z"/></svg>'
                                                    ),
                                                    'perplexity' => array(
                                                        'name' => 'Perplexity',
                                                        'logo' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" fill="none"/><path d="M12 8v8M8 12h8"/></svg>'
                                                    ),
                                                    'grok' => array(
                                                        'name' => 'Grok',
                                                        'logo' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>'
                                                    ),
                                                    'copilot' => array(
                                                        'name' => 'Microsoft Copilot',
                                                        'logo' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z"/></svg>'
                                                    ),
                                                    'google' => array(
                                                        'name' => 'Google AI Overview',
                                                        'logo' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12.48 10.92v3.28h7.84c-.24 1.84-.853 3.187-1.787 4.133-1.147 1.147-2.933 2.4-6.053 2.4-4.827 0-8.6-3.893-8.6-8.72s3.773-8.72 8.6-8.72c2.6 0 4.507 1.027 5.907 2.347l2.307-2.307C18.747 1.44 16.133 0 12.48 0 5.867 0 .307 5.387.307 12s5.56 12 12.173 12c3.573 0 6.267-1.173 8.373-3.36 2.16-2.16 2.84-5.213 2.84-7.667 0-.76-.053-1.467-.173-2.053H12.48z"/></svg>'
                                                    ),
                                                );
                                                $allowed_svg = array(
                                                    'svg' => array(
                                                        'xmlns' => true,
                                                        'width' => true,
                                                        'height' => true,
                                                        'viewBox' => true,
                                                        'fill' => true,
                                                        'class' => true,
                                                        'aria-hidden' => true,
                                                        'role' => true,
                                                        'focusable' => true,
                                                    ),
                                                    'path' => array(
                                                        'd' => true,
                                                        'fill' => true,
                                                    ),
                                                    'circle' => array(
                                                        'cx' => true,
                                                        'cy' => true,
                                                        'r' => true,
                                                        'fill' => true,
                                                        'stroke' => true,
                                                        'stroke-width' => true,
                                                    ),
                                                );
                                                foreach ($providers as $key => $provider) {
                                                    ?>
                                                    <label
                                                        class="postpilotai-checkbox-card postpilotai-checkbox-card--<?php echo esc_attr($key); ?>">
                                                        <input type="checkbox"
                                                            name="nexipilot_external_ai_<?php echo esc_attr($key); ?>" value="1"
                                                            <?php checked(get_option('nexipilot_external_ai_' . $key, '1'), '1'); ?> />
                                                        <div class="postpilotai-checkbox-card__content">
                                                            <div class="postpilotai-checkbox-card__icon">
                                                                <?php echo wp_kses($provider['logo'] ?? '', $allowed_svg); ?>
                                                            </div>
                                                            <span
                                                                class="postpilotai-checkbox-card__label"><?php echo esc_html($provider['name']); ?></span>
                                                            <div class="postpilotai-checkbox-card__status">
                                                                <svg class="postpilotai-checkmark" width="16" height="16"
                                                                    viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                                    stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                                                                    <polyline points="20 6 9 17 4 12"></polyline>
                                                                </svg>
                                                            </div>
                                                        </div>
                                                    </label>
                                                <?php } ?>
                                            </div>
                                            <p class="postpilotai-field-description">
                                                <?php esc_html_e('Select which external AI tools to show to your readers', 'nexipilot-content-ai'); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- End Features Tab -->

                    <div class="postpilotai-submit-wrapper">
                        <?php submit_button(__('Save Settings', 'nexipilot-content-ai'), 'primary postpilotai-btn-primary', 'submit', false); ?>
                    </div>
                </div>

                <!-- Troubleshooting Tab Content -->
                <div class="postpilotai-tab-content" id="troubleshooting-tab">
                    <div class="nexipilot-settings-grid">
                        <!-- Debug Logging Card -->
                        <div class="postpilotai-card">
                            <div class="postpilotai-card-header">
                                <div class="postpilotai-card-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                        <path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </div>
                                <div>
                                    <h2><?php esc_html_e('Debug Logging', 'nexipilot-content-ai'); ?></h2>
                                    <p><?php esc_html_e('Enable debug logging for troubleshooting API issues', 'nexipilot-content-ai'); ?>
                                    </p>
                                </div>
                            </div>
                            <div class="postpilotai-card-body">
                                <!-- Debug Logging Toggle -->
                                <div class="postpilotai-field-group">
                                    <label class="postpilotai-label">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" style="margin-right: 8px;">
                                            <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor"
                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        <?php esc_html_e('Enable Debug Logging', 'nexipilot-content-ai'); ?>
                                    </label>
                                    <div class="postpilotai-toggle-wrapper">
                                        <label class="postpilotai-toggle">
                                            <input type="checkbox" name="nexipilot_enable_debug_logging" value="1" <?php checked(get_option('nexipilot_enable_debug_logging'), '1'); ?> />
                                            <span class="postpilotai-toggle-slider"></span>
                                        </label>
                                        <span class="postpilotai-toggle-label">
                                            <?php esc_html_e('Log all API requests and responses to debug.log', 'nexipilot-content-ai'); ?>
                                        </span>
                                    </div>
                                    <p class="postpilotai-field-description">
                                        <?php esc_html_e('When enabled, all API requests and responses will be logged to debug.log. This is useful for troubleshooting API connection issues. Keep this disabled in production environments to avoid filling up your debug log.', 'nexipilot-content-ai'); ?>
                                    </p>

                                    <div class="postpilotai-info-box" style="margin-top: 16px;">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" style="flex-shrink: 0;">
                                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" />
                                            <path d="M12 16v-4M12 8h.01" stroke="currentColor" stroke-width="2"
                                                stroke-linecap="round" />
                                        </svg>
                                        <div>
                                            <strong><?php esc_html_e('Debug Log Location:', 'nexipilot-content-ai'); ?></strong>
                                            <p style="margin: 4px 0 0 0; font-size: 13px; opacity: 0.8;">
                                                <?php echo esc_html(WP_CONTENT_DIR . '/debug.log'); ?>
                                            </p>
                                            <p style="margin: 8px 0 0 0; font-size: 13px; opacity: 0.8;">
                                                <?php esc_html_e('Make sure WP_DEBUG and WP_DEBUG_LOG are enabled in wp-config.php for debug logging to work.', 'nexipilot-content-ai'); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- End Troubleshooting Tab -->

                    <div class="postpilotai-submit-wrapper">
                        <?php submit_button(__('Save Settings', 'nexipilot-content-ai'), 'primary postpilotai-btn-primary', 'submit', false); ?>
                    </div>
                </div>

            </form>
        </div>
        <?php
    }


    /**
     * Render AI section description
     *
     * @since 1.0.0
     * @return void
     */
    public function render_ai_section_description()
    {
        echo '<p>' . esc_html__('Configure your AI provider and API credentials.', 'nexipilot-content-ai') . '</p>';
    }

    /**
     * Render features section description
     *
     * @since 1.0.0
     * @return void
     */
    public function render_features_section_description()
    {
        echo '<p>' . esc_html__('Enable or disable AI-powered features and configure their display.', 'nexipilot-content-ai') . '</p>';
    }

    /**
     * Render AI provider field
     *
     * @since 1.0.0
     * @return void
     */
    public function render_ai_provider_field()
    {
        $value = get_option('nexipilot_ai_provider', 'openai');
        ?>
        <select name="nexipilot_ai_provider" id="nexipilot_ai_provider">
            <option value="openai" <?php selected($value, 'openai'); ?>>
                <?php esc_html_e('OpenAI (ChatGPT)', 'nexipilot-content-ai'); ?>
            </option>
            <option value="claude" <?php selected($value, 'claude'); ?>>
                <?php esc_html_e('Claude (Anthropic)', 'nexipilot-content-ai'); ?>
            </option>
        </select>
        <p class="description">
            <?php esc_html_e('Select your preferred AI provider.', 'nexipilot-content-ai'); ?>
        </p>
        <?php
    }

    /**
     * Render OpenAI API key field
     *
     * @since 1.0.0
     * @return void
     */
    public function render_openai_api_key_field()
    {
        $value = get_option('nexipilot_openai_api_key', '');
        $has_key = !empty($value);
        ?>
        <input type="text" name="nexipilot_openai_api_key" id="nexipilot_openai_api_key" value="<?php echo esc_attr($value); ?>"
            class="regular-text"
            placeholder="<?php echo $has_key ? esc_attr__('API key is saved', 'nexipilot-content-ai') : esc_attr__('Enter your OpenAI API key', 'nexipilot-content-ai'); ?>" />
        <?php if ($has_key): ?>
            <span class="dashicons dashicons-yes-alt" style="color: #46b450; margin-left: 5px;"></span>
        <?php endif; ?>
        <p class="description">
            <?php
            printf(
                /* translators: %s: OpenAI API URL */
                esc_html__('Get your API key from %s', 'nexipilot-content-ai'),
                '<a href="https://platform.openai.com/api-keys" target="_blank">OpenAI</a>'
            );
            ?>
        </p>
        <?php
    }

    /**
     * Render Claude API key field
     *
     * @since 1.0.0
     * @return void
     */
    public function render_claude_api_key_field()
    {
        $value = get_option('nexipilot_claude_api_key', '');
        $has_key = !empty($value);
        ?>
        <input type="text" name="nexipilot_claude_api_key" id="nexipilot_claude_api_key" value="<?php echo esc_attr($value); ?>"
            class="regular-text"
            placeholder="<?php echo $has_key ? esc_attr__('API key is saved', 'nexipilot-content-ai') : esc_attr__('Enter your Claude API key', 'nexipilot-content-ai'); ?>" />
        <?php if ($has_key): ?>
            <span class="dashicons dashicons-yes-alt" style="color: #46b450; margin-left: 5px;"></span>
        <?php endif; ?>
        <p class="description">
            <?php
            printf(
                /* translators: %s: Anthropic API URL */
                esc_html__('Get your API key from %s', 'nexipilot-content-ai'),
                '<a href="https://console.anthropic.com/" target="_blank">Anthropic</a>'
            );
            ?>
        </p>
        <?php
    }

    /**
     * Render enable FAQ field
     *
     * @since 1.0.0
     * @return void
     */
    public function render_enable_faq_field()
    {
        $value = get_option('nexipilot_enable_faq', '1');
        ?>
        <label>
            <input type="checkbox" name="nexipilot_enable_faq" value="1" <?php checked($value, '1'); ?> />
            <?php esc_html_e('Enable AI-generated FAQ section', 'nexipilot-content-ai'); ?>
        </label>
        <?php
    }

    /**
     * Render FAQ position field
     *
     * @since 1.0.0
     * @return void
     */
    public function render_faq_position_field()
    {
        $value = get_option('nexipilot_faq_position', 'after_content');
        ?>
        <select name="nexipilot_faq_position" id="nexipilot_faq_position">
            <option value="before_content" <?php selected($value, 'before_content'); ?>>
                <?php esc_html_e('Before Content', 'nexipilot-content-ai'); ?>
            </option>
            <option value="after_content" <?php selected($value, 'after_content'); ?>>
                <?php esc_html_e('After Content', 'nexipilot-content-ai'); ?>
            </option>
        </select>
        <?php
    }

    /**
     * Render enable summary field
     *
     * @since 1.0.0
     * @return void
     */
    public function render_enable_summary_field()
    {
        $value = get_option('nexipilot_enable_summary', '1');
        ?>
        <label>
            <input type="checkbox" name="nexipilot_enable_summary" value="1" <?php checked($value, '1'); ?> />
            <?php esc_html_e('Enable AI-generated content summary', 'nexipilot-content-ai'); ?>
        </label>
        <?php
    }

    /**
     * Render summary position field
     *
     * @since 1.0.0
     * @return void
     */
    public function render_summary_position_field()
    {
        $value = get_option('nexipilot_summary_position', 'before_content');
        ?>
        <select name="nexipilot_summary_position" id="nexipilot_summary_position">
            <option value="before_content" <?php selected($value, 'before_content'); ?>>
                <?php esc_html_e('Before Content', 'nexipilot-content-ai'); ?>
            </option>
            <option value="after_content" <?php selected($value, 'after_content'); ?>>
                <?php esc_html_e('After Content', 'nexipilot-content-ai'); ?>
            </option>
        </select>
        <?php
    }

    /**
     * Render FAQ default layout field
     *
     * @since 1.0.0
     * @return void
     */
    public function render_faq_default_layout_field()
    {
        $value = get_option('nexipilot_faq_default_layout', 'accordion');
        ?>
        <select name="nexipilot_faq_default_layout" id="nexipilot_faq_default_layout">
            <option value="accordion" <?php selected($value, 'accordion'); ?>>
                <?php esc_html_e('Accordion', 'nexipilot-content-ai'); ?>
            </option>
            <option value="static" <?php selected($value, 'static'); ?>>
                <?php esc_html_e('Static', 'nexipilot-content-ai'); ?>
            </option>
        </select>
        <p class="description">
            <?php esc_html_e('Choose the default layout for FAQ display. This can be overridden per post.', 'nexipilot-content-ai'); ?>
        </p>
        <?php
    }

    /**
     * Render enable internal links field
     *
     * @since 1.0.0
     * @return void
     */
    public function render_enable_internal_links_field()
    {
        $value = get_option('nexipilot_enable_internal_links', '1');
        ?>
        <label>
            <input type="checkbox" name="nexipilot_enable_internal_links" value="1" <?php checked($value, '1'); ?> />
            <?php esc_html_e('Enable AI-powered smart internal linking', 'nexipilot-content-ai'); ?>
        </label>
        <?php
    }

    /**
     * Render Gemini API key field
     *
     * @since 1.0.0
     * @return void
     */
    public function render_gemini_api_key_field()
    {
        $value = get_option('nexipilot_gemini_api_key', '');
        $has_key = !empty($value);
        ?>
        <input type="text" name="nexipilot_gemini_api_key" id="nexipilot_gemini_api_key" value="<?php echo esc_attr($value); ?>"
            class="regular-text"
            placeholder="<?php echo $has_key ? esc_attr__('API key is saved', 'nexipilot-content-ai') : esc_attr__('Enter your Gemini API key', 'nexipilot-content-ai'); ?>" />
        <?php if ($has_key): ?>
            <span class="dashicons dashicons-yes-alt" style="color: #46b450; margin-left: 5px;"></span>
        <?php endif; ?>
        <p class="description">
            <?php
            printf(
                /* translators: %s: Google AI Studio URL */
                esc_html__('Get your API key from %s', 'nexipilot-content-ai'),
                '<a href="https://aistudio.google.com/app/apikey" target="_blank">Google AI Studio</a>'
            );
            ?>
        </p>
        <?php
    }

    /**
     * Render OpenAI model field
     *
     * @since 1.0.0
     * @return void
     */
    public function render_openai_model_field()
    {
        $value = get_option('nexipilot_openai_model', 'gpt-3.5-turbo');
        ?>
        <select name="nexipilot_openai_model" id="nexipilot_openai_model">
            <option value="gpt-4o" <?php selected($value, 'gpt-4o'); ?>>
                <?php esc_html_e('GPT-4o (Most Capable)', 'nexipilot-content-ai'); ?>
            </option>
            <option value="gpt-4o-mini" <?php selected($value, 'gpt-4o-mini'); ?>>
                <?php esc_html_e('GPT-4o Mini (Balanced)', 'nexipilot-content-ai'); ?>
            </option>
            <option value="gpt-4-turbo" <?php selected($value, 'gpt-4-turbo'); ?>>
                <?php esc_html_e('GPT-4 Turbo', 'nexipilot-content-ai'); ?>
            </option>
            <option value="gpt-3.5-turbo" <?php selected($value, 'gpt-3.5-turbo'); ?>>
                <?php esc_html_e('GPT-3.5 Turbo (Fastest)', 'nexipilot-content-ai'); ?>
            </option>
        </select>
        <p class="description">
            <?php esc_html_e('Select the OpenAI model to use for content generation.', 'nexipilot-content-ai'); ?>
        </p>
        <?php
    }

    /**
     * Render Claude model field
     *
     * @since 1.0.0
     * @return void
     */
    public function render_claude_model_field()
    {
        $value = get_option('nexipilot_claude_model', 'claude-3-haiku-20240307');
        ?>
        <select name="nexipilot_claude_model" id="nexipilot_claude_model">
            <option value="claude-3-5-sonnet-20241022" <?php selected($value, 'claude-3-5-sonnet-20241022'); ?>>
                <?php esc_html_e('Claude 3.5 Sonnet (Most Capable)', 'nexipilot-content-ai'); ?>
            </option>
            <option value="claude-3-opus-20240229" <?php selected($value, 'claude-3-opus-20240229'); ?>>
                <?php esc_html_e('Claude 3 Opus', 'nexipilot-content-ai'); ?>
            </option>
            <option value="claude-3-haiku-20240307" <?php selected($value, 'claude-3-haiku-20240307'); ?>>
                <?php esc_html_e('Claude 3 Haiku (Fastest)', 'nexipilot-content-ai'); ?>
            </option>
        </select>
        <p class="description">
            <?php esc_html_e('Select the Claude model to use for content generation.', 'nexipilot-content-ai'); ?>
        </p>
        <?php
    }

    /**
     * Render Gemini model field
     *
     * @since 1.0.0
     * @return void
     */
    public function render_gemini_model_field()
    {
        $value = get_option('nexipilot_gemini_model', 'gemini-1.5-flash');
        ?>
        <select name="nexipilot_gemini_model" id="nexipilot_gemini_model">
            <option value="gemini-1.5-pro" <?php selected($value, 'gemini-1.5-pro'); ?>>
                <?php esc_html_e('Gemini 1.5 Pro (Most Capable)', 'nexipilot-content-ai'); ?>
            </option>
            <option value="gemini-1.5-flash" <?php selected($value, 'gemini-1.5-flash'); ?>>
                <?php esc_html_e('Gemini 1.5 Flash (Balanced)', 'nexipilot-content-ai'); ?>
            </option>
            <option value="gemini-1.0-pro" <?php selected($value, 'gemini-1.0-pro'); ?>>
                <?php esc_html_e('Gemini 1.0 Pro', 'nexipilot-content-ai'); ?>
            </option>
        </select>
        <p class="description">
            <?php esc_html_e('Select the Gemini model to use for content generation.', 'nexipilot-content-ai'); ?>
        </p>
        <?php
    }

    /**
     * Get all available AI providers
     *
     * Returns an associative array of all available AI providers.
     * Add new providers here to automatically include them in dropdowns and status counts.
     *
     * @since 1.0.0
     * @return array Associative array of provider_key => provider_name
     */
    private function get_available_providers()
    {
        // Last updated: 2026-01-19 23:59 - Force cache clear
        return array(
            'openai' => 'OpenAI (ChatGPT)',
            'claude' => 'Claude (Anthropic)',
            'gemini' => 'Google Gemini',
            'grok' => 'xAI Grok'
        );
    }

    /**
     * Get provider connection status
     *
     * Returns status information for a given AI provider with 4 states:
     * - missing: No API key configured (neutral, gray)
     * - saved: API key exists but not validated (yellow)
     * - invalid: API key validation failed (red)
     * - connected: API key validated successfully (green)
     *
     * @since 1.0.0
     * @param string $provider_key Provider key (openai, claude, gemini, grok)
     * @return array Status array with icon, text, class, and tooltip
     */
    private function get_provider_status($provider_key)
    {
        $api_key = get_option("nexipilot_{$provider_key}_api_key");

        // State 1: Missing Key (gray, neutral)
        if (empty($api_key)) {
            return array(
                'status' => 'missing',
                'icon' => '⚪',
                'text' => __('Missing Key', 'nexipilot-content-ai'),
                'class' => 'postpilotai-status-gray',
                'tooltip' => __('No API key configured. Add one if you plan to use this provider.', 'nexipilot-content-ai')
            );
        }

        // Check if we have a validation result stored
        $validation_result = get_transient("nexipilot_{$provider_key}_validation");

        // State 2: Key Saved (Not Verified) - yellow
        if ($validation_result === false) {
            return array(
                'status' => 'saved',
                'icon' => '🟡',
                'text' => __('Key Saved', 'nexipilot-content-ai'),
                'class' => 'postpilotai-status-yellow',
                'tooltip' => __('API key saved but not verified. Save settings to validate.', 'nexipilot-content-ai')
            );
        }

        // State 3: Invalid Key - red
        if (is_wp_error($validation_result)) {
            return array(
                'status' => 'invalid',
                'icon' => '❌',
                'text' => __('Invalid Key', 'nexipilot-content-ai'),
                'class' => 'postpilotai-status-red',
                'tooltip' => $validation_result->get_error_message()
            );
        }

        // State 4: Connected - green
        return array(
            'status' => 'connected',
            'icon' => '✅',
            'text' => __('Connected', 'nexipilot-content-ai'),
            'class' => 'postpilotai-status-green',
            'tooltip' => __('API key verified and working', 'nexipilot-content-ai')
        );
    }

    /**
     * Validate OpenAI API key
     *
     * @since 1.0.0
     * @param mixed $old_value Old API key value
     * @param mixed $new_value New API key value
     * @return void
     */
    public function validate_openai_key($old_value, $new_value)
    {
        if (empty($new_value)) {
            delete_transient('nexipilot_openai_validation');
            return;
        }

        // Only validate if key changed
        if ($old_value === $new_value) {
            return;
        }

        // Decrypt the API key before validation (keys are stored encrypted)
        $decrypted_key = \PostPilotAI\Helpers\Encryption::decrypt($new_value);

        $openai = new \PostPilotAI\AI\OpenAI($decrypted_key);
        $result = $openai->validate_api_key($decrypted_key);

        set_transient('nexipilot_openai_validation', $result, WEEK_IN_SECONDS);
    }

    /**
     * Validate Claude API key
     *
     * @since 1.0.0
     * @param mixed $old_value Old API key value
     * @param mixed $new_value New API key value
     * @return void
     */
    public function validate_claude_key($old_value, $new_value)
    {
        if (empty($new_value)) {
            delete_transient('nexipilot_claude_validation');
            return;
        }

        // Only validate if key changed
        if ($old_value === $new_value) {
            return;
        }

        // Decrypt the API key before validation (keys are stored encrypted)
        $decrypted_key = \PostPilotAI\Helpers\Encryption::decrypt($new_value);

        $claude = new \PostPilotAI\AI\Claude($decrypted_key);
        $result = $claude->validate_api_key($decrypted_key);

        set_transient('nexipilot_claude_validation', $result, WEEK_IN_SECONDS);
    }

    /**
     * Validate Gemini API key
     *
     * @since 1.0.0
     * @param mixed $old_value Old API key value
     * @param mixed $new_value New API key value
     * @return void
     */
    public function validate_gemini_key($old_value, $new_value)
    {
        if (empty($new_value)) {
            delete_transient('nexipilot_gemini_validation');
            return;
        }

        // Only validate if key changed
        if ($old_value === $new_value) {
            return;
        }

        // Decrypt the API key before validation (keys are stored encrypted)
        $decrypted_key = \PostPilotAI\Helpers\Encryption::decrypt($new_value);

        $gemini = new \PostPilotAI\AI\Gemini($decrypted_key);
        $result = $gemini->validate_api_key($decrypted_key);

        set_transient('nexipilot_gemini_validation', $result, WEEK_IN_SECONDS);
    }

    /**
     * Validate Grok API key
     *
     * @since 1.0.0
     * @param mixed $old_value Old API key value
     * @param mixed $new_value New API key value
     * @return void
     */
    public function validate_grok_key($old_value, $new_value)
    {
        if (empty($new_value)) {
            delete_transient('nexipilot_grok_validation');
            return;
        }

        // Only validate if key changed
        if ($old_value === $new_value) {
            return;
        }

        // Decrypt the API key before validation (keys are stored encrypted)
        $decrypted_key = \PostPilotAI\Helpers\Encryption::decrypt($new_value);

        $grok = new \PostPilotAI\AI\Grok($decrypted_key);
        $result = $grok->validate_api_key($decrypted_key);

        set_transient('nexipilot_grok_validation', $result, WEEK_IN_SECONDS);
    }
}
