<?php
/**
 * FAQMetaBox.php
 *
 * Handles FAQ meta box in post editor.
 *
 * @package NexiPilot\Admin\MetaBox
 * @since 1.0.0
 */

namespace NexiPilot\Admin\MetaBox;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use NexiPilot\AI\Manager as AIManager;
use NexiPilot\Helpers\Sanitizer;
use NexiPilot\Helpers\Logger;

/**
 * FAQ Meta Box Class
 *
 * Manages FAQ generation, editing, and display in post editor.
 *
 * @package NexiPilot\Admin\MetaBox
 * @since 1.0.0
 */
class FAQMetaBox
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
     * Initialize hooks
     *
     * @since 1.0.0
     * @return void
     */
    private function init_hooks()
    {
        add_action('add_meta_boxes', array($this, 'register_meta_box'));
        add_action('save_post', array($this, 'save_meta_box'), 10, 2);
        add_action('wp_ajax_nexipilot_generate_faq', array($this, 'ajax_generate_faq'));
        add_action('wp_ajax_nexipilot_generate_demo_faq', array($this, 'ajax_generate_demo_faq'));
        add_action('wp_ajax_nexipilot_check_api_status', array($this, 'ajax_check_api_status'));
    }

    /**
     * Register meta box
     *
     * @since 1.0.0
     * @return void
     */
    public function register_meta_box()
    {
        add_meta_box(
            'nexipilot_faq_metabox',
            __('NexiPilot Content AI - FAQ Generator', 'nexipilot-content-ai'),
            array($this, 'render_meta_box'),
            'post',
            'normal',
            'high'
        );
    }

    /**
     * Render meta box content
     *
     * @since 1.0.0
     * @param \WP_Post $post The post object.
     * @return void
     */
    public function render_meta_box($post)
    {
        // Add nonce for security
        wp_nonce_field('nexipilot_faq_metabox', 'nexipilot_faq_nonce');

        // Get saved data
        $faq_enabled = get_post_meta($post->ID, '_nexipilot_faq_enabled', true);
        $faqs = get_post_meta($post->ID, '_nexipilot_faqs', true);

        // Default to enabled if not set
        if ($faq_enabled === '') {
            $faq_enabled = '1';
        }

        $has_faqs = !empty($faqs) && is_array($faqs);
        ?>
        <div class="nexipilot-faq-metabox">
            <!-- Enable/Disable FAQ -->
            <div class="nexipilot-faq-enable">
                <label>
                    <input type="checkbox" name="nexipilot_faq_enabled" value="1" <?php checked($faq_enabled, '1'); ?> />
                    <strong><?php esc_html_e('Display FAQ on this post?', 'nexipilot-content-ai'); ?></strong>
                </label>
                <p class="description">
                    <?php esc_html_e('Check this to show AI-generated FAQs on the frontend.', 'nexipilot-content-ai'); ?>
                </p>
            </div>

            <!-- FAQ Display Style -->
            <div class="nexipilot-faq-layout" style="margin-top: 15px;">
                <?php
                $faq_display_style = get_post_meta($post->ID, '_nexipilot_faq_display_style', true);
                $global_default = get_option('nexipilot_faq_default_layout', 'accordion');
                $global_default_label = ucfirst($global_default);

                // Set default value if empty
                if (empty($faq_display_style)) {
                    $faq_display_style = 'default';
                }
                ?>
                <label for="nexipilot_faq_display_style" style="display: block; margin-bottom: 8px;">
                    <strong><?php esc_html_e('FAQ Display Style:', 'nexipilot-content-ai'); ?></strong>
                </label>
                <select name="nexipilot_faq_display_style" id="nexipilot_faq_display_style"
                    style="width: 100%; max-width: 300px;">
                    <option value="default" <?php selected($faq_display_style, 'default'); ?>>
                        <?php
                        printf(
                            /* translators: %s: current global default layout */
                            esc_html__('Use Default (%s)', 'nexipilot-content-ai'),
                            esc_html($global_default_label)
                        );
                        ?>
                    </option>
                    <option value="accordion" <?php selected($faq_display_style, 'accordion'); ?>>
                        <?php esc_html_e('Accordion', 'nexipilot-content-ai'); ?>
                    </option>
                    <option value="static" <?php selected($faq_display_style, 'static'); ?>>
                        <?php esc_html_e('Static', 'nexipilot-content-ai'); ?>
                    </option>
                </select>
                <p class="description" style="margin-top: 5px;">
                    <?php esc_html_e('Choose how FAQs should be displayed on the frontend.', 'nexipilot-content-ai'); ?>
                </p>
            </div>

            <!-- FAQ Repeater Fields -->
            <div class="nexipilot-faq-fields" style="margin-top: 20px;">
                <div class="nexipilot-faq-header">
                    <h4><?php esc_html_e('FAQ Items', 'nexipilot-content-ai'); ?></h4>
                    <button type="button" class="button button-secondary nexipilot-add-faq-item">
                        <?php esc_html_e('+ Add FAQ Item', 'nexipilot-content-ai'); ?>
                    </button>
                </div>

                <div class="nexipilot-faq-items">
                    <?php
                    if ($has_faqs) {
                        foreach ($faqs as $index => $faq) {
                            $this->render_faq_item($index, $faq);
                        }
                    } else {
                        echo '<p class="nexipilot-no-faqs">' . esc_html__('No FAQs yet. Click "Generate FAQ" to create them automatically.', 'nexipilot-content-ai') . '</p>';
                    }
                    ?>
                </div>
            </div>

            <!-- Generate/Regenerate Button -->
            <div class="nexipilot-faq-actions" style="margin-top: 20px;">
                <button type="button" class="button button-primary nexipilot-generate-faq"
                    data-post-id="<?php echo esc_attr($post->ID); ?>">
                    <?php echo $has_faqs ? esc_html__('Regenerate FAQ', 'nexipilot-content-ai') : esc_html__('Generate FAQ', 'nexipilot-content-ai'); ?>
                </button>
                <span class="spinner"></span>
                <p class="description">
                    <?php esc_html_e('Generate FAQs using AI based on your post content.', 'nexipilot-content-ai'); ?>
                </p>
            </div>
        </div>

        <!-- Hidden template for new FAQ items -->
        <script type="text/template" id="nexipilot-faq-item-template">
                                                                                                                                                                                            <?php $this->render_faq_item('{{INDEX}}', array('question' => '', 'answer' => '')); ?>
                                                                                                                                                                                        </script>
        <?php
    }

    /**
     * Render single FAQ item
     *
     * @since 1.0.0
     * @param int|string $index The item index.
     * @param array      $faq The FAQ data.
     * @return void
     */
    private function render_faq_item($index, $faq)
    {
        $question = isset($faq['question']) ? $faq['question'] : '';
        $answer = isset($faq['answer']) ? $faq['answer'] : '';
        ?>
        <div class="nexipilot-faq-item" data-index="<?php echo esc_attr($index); ?>">
            <div class="nexipilot-faq-item-header">
                <span class="nexipilot-faq-item-number">
                    <?php
                    echo sprintf(
                        /* translators: %1$s: FAQ item number */
                        esc_html__('FAQ #%1$s', 'nexipilot-content-ai'),
                        esc_html(
                            is_numeric($index)
                            ? (string) ($index + 1)
                            : '{{NUMBER}}'
                        )
                    );
                    ?>
                </span>
                <button type="button" class="button-link nexipilot-remove-faq-item"
                    title="<?php esc_attr_e('Remove this FAQ', 'nexipilot-content-ai'); ?>">
                    <span class="dashicons dashicons-trash"></span>
                </button>
            </div>
            <div class="nexipilot-faq-item-fields">
                <div class="nexipilot-faq-field">
                    <label><?php esc_html_e('Question:', 'nexipilot-content-ai'); ?></label>
                    <input type="text" name="nexipilot_faqs[<?php echo esc_attr($index); ?>][question]"
                        value="<?php echo esc_attr($question); ?>" class="widefat"
                        placeholder="<?php esc_attr_e('Enter question...', 'nexipilot-content-ai'); ?>" />
                </div>
                <div class="nexipilot-faq-field">
                    <label><?php esc_html_e('Answer:', 'nexipilot-content-ai'); ?></label>
                    <textarea name="nexipilot_faqs[<?php echo esc_attr($index); ?>][answer]" class="widefat" rows="3"
                        placeholder="<?php esc_attr_e('Enter answer...', 'nexipilot-content-ai'); ?>"><?php echo esc_textarea($answer); ?></textarea>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Save meta box data
     *
     * @since 1.0.0
     * @param int      $post_id The post ID.
     * @param \WP_Post $post The post object.
     * @return void
     */
    public function save_meta_box($post_id, $post)
    {

        // Security checks
        if (
            !isset($_POST['nexipilot_faq_nonce']) ||
            !wp_verify_nonce(
                sanitize_text_field(wp_unslash($_POST['nexipilot_faq_nonce'])),
                'nexipilot_faq_metabox'
            )
        ) {
            return;
        }


        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save enabled status
        $faq_enabled = isset($_POST['nexipilot_faq_enabled']) ? '1' : '0';
        update_post_meta($post_id, '_nexipilot_faq_enabled', $faq_enabled);

        // Save FAQ display style
        if (isset($_POST['nexipilot_faq_display_style'])) {
            $display_style = sanitize_text_field(wp_unslash($_POST['nexipilot_faq_display_style']));
            // Validate the value
            $allowed_styles = array('default', 'accordion', 'static');
            if (in_array($display_style, $allowed_styles, true)) {
                update_post_meta($post_id, '_nexipilot_faq_display_style', $display_style);
            }
        }

        // Save FAQ items
        $raw_faqs = isset($_POST['nexipilot_faqs'])
            ? wp_unslash($_POST['nexipilot_faqs']) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized field-by-field below.
            : array();
        if (is_array($raw_faqs)) {
            $faqs = array();

            foreach ($raw_faqs as $faq) {

                $question = isset($faq['question']) ? sanitize_text_field($faq['question']) : '';
                $answer = isset($faq['answer']) ? wp_kses_post($faq['answer']) : '';

                if ('' !== $question || '' !== $answer) {
                    $faqs[] = array(
                        'question' => $question,
                        'answer' => $answer,
                    );
                }
            }

            update_post_meta($post_id, '_nexipilot_faqs', $faqs);
        } else {
            // If no FAQs submitted, check if we should auto-generate
            $existing_faqs = get_post_meta($post_id, '_nexipilot_faqs', true);

            // Auto-generate only if: enabled, no existing FAQs, and post is published
            if ($faq_enabled === '1' && empty($existing_faqs) && $post->post_status === 'publish') {
                $this->auto_generate_faq($post_id, $post->post_content);
            }
        }
    }

    /**
     * Auto-generate FAQ on publish
     *
     * @since 1.0.0
     * @param int    $post_id The post ID.
     * @param string $content The post content.
     * @return void
     */
    private function auto_generate_faq($post_id, $content)
    {
        $faq_data = $this->ai_manager->get_faq($post_id, $content);

        if (!is_wp_error($faq_data) && !empty($faq_data)) {
            update_post_meta($post_id, '_nexipilot_faqs', $faq_data);
            Logger::info('FAQ auto-generated on publish', array('post_id' => $post_id));
        }
    }

    /**
     * AJAX handler for FAQ generation
     *
     * @since 1.0.0
     * @return void
     */
    public function ajax_generate_faq()
    {
        // Security checks
        check_ajax_referer('nexipilot_generate_faq', 'nonce');

        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
        $user_id = get_current_user_id();

        if (!$post_id || !current_user_can('edit_post', $post_id)) {
            wp_send_json_error(array(
                'message' => esc_html__('Permission denied.', 'nexipilot-content-ai'),
            ));
        }

        // Rate limit check
        if (!\PostPilotAI\Helpers\RateLimiter::can_generate_faq($user_id, $post_id)) {
            $wait_time = \PostPilotAI\Helpers\RateLimiter::get_wait_time($user_id, $post_id);
            $post_remaining = \PostPilotAI\Helpers\RateLimiter::get_post_remaining($user_id, $post_id);
            $daily_remaining = \PostPilotAI\Helpers\RateLimiter::get_daily_remaining($user_id);

            // Determine which limit was hit
            if ($post_remaining === 0) {
                $message = sprintf(
                    /* translators: %1$s: remaining wait time before FAQ generation is allowed again */
                    esc_html__(
                        'You have generated FAQ for this post recently. Please wait %1$s before trying again.',
                        'nexipilot-content-ai'
                    ),
                    human_time_diff(time(), time() + $wait_time)
                );
            } else {
                $message = sprintf(
                    /* translators: %1$d: maximum number of FAQ generations allowed per day */
                    esc_html__(
                        'You have reached your daily FAQ generation limit (%1$d per day). Please try again tomorrow.',
                        'nexipilot-content-ai'
                    ),
                    \PostPilotAI\Helpers\RateLimiter::get_daily_limit()
                );
            }

            wp_send_json_error(array(
                'message' => $message,
                'rate_limited' => true,
                'wait_time' => $wait_time,
                'post_remaining' => $post_remaining,
                'daily_remaining' => $daily_remaining,
            ));
        }

        $post = get_post($post_id);

        if (!$post) {
            wp_send_json_error(array(
                'message' => esc_html__('Post not found.', 'nexipilot-content-ai'),
            ));
        }

        // Generate FAQ
        $faq_data = $this->ai_manager->get_faq($post_id, $post->post_content);

        Logger::debug('FAQ AJAX: Data received from AI Manager', array(
            'post_id' => $post_id,
            'is_error' => is_wp_error($faq_data),
            'is_array' => is_array($faq_data),
            'type' => gettype($faq_data),
            'count' => is_array($faq_data) ? count($faq_data) : 0,
            'data_preview' => is_array($faq_data)
                ? wp_json_encode(array_slice($faq_data, 0, 2))
                : wp_trim_words(wp_strip_all_tags((string) $faq_data), 40, '…'),
        ));

        if (is_wp_error($faq_data)) {
            wp_send_json_error(array(
                'message' => $faq_data->get_error_message(),
            ));
        }

        // Record successful generation
        \PostPilotAI\Helpers\RateLimiter::record_generation($user_id, $post_id);

        // Save to post meta
        update_post_meta($post_id, '_nexipilot_faqs', $faq_data);

        Logger::debug('FAQ AJAX: Data saved to post meta', array(
            'post_id' => $post_id,
            'saved_data' => json_encode($faq_data)
        ));

        // Return HTML for FAQ items
        ob_start();
        foreach ($faq_data as $index => $faq) {
            $this->render_faq_item($index, $faq);
        }
        $html = ob_get_clean();

        wp_send_json_success(array(
            'message' => esc_html__('FAQ generated successfully!', 'nexipilot-content-ai'),
            'html' => $html,
            'count' => count($faq_data),
        ));
    }

    /**
     * AJAX handler for demo FAQ generation
     *
     * @since 1.0.0
     * @return void
     */
    public function ajax_generate_demo_faq()
    {
        // Security checks
        check_ajax_referer('nexipilot_generate_faq', 'nonce');

        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
        $user_id = get_current_user_id();

        if (!$post_id || !current_user_can('edit_post', $post_id)) {
            wp_send_json_error(array(
                'message' => esc_html__('Permission denied.', 'nexipilot-content-ai'),
            ));
        }

        // Rate limit check (same as regular generation)
        if (!\PostPilotAI\Helpers\RateLimiter::can_generate_faq($user_id, $post_id)) {
            $wait_time = \PostPilotAI\Helpers\RateLimiter::get_wait_time($user_id, $post_id);
            $post_remaining = \PostPilotAI\Helpers\RateLimiter::get_post_remaining($user_id, $post_id);
            $daily_remaining = \PostPilotAI\Helpers\RateLimiter::get_daily_remaining($user_id);

            // Determine which limit was hit
            if ($post_remaining === 0) {

                $message = sprintf(
                    /* translators: %1$s: remaining wait time before FAQ generation is allowed again */
                    esc_html__(
                        'You have generated FAQ for this post recently. Please wait %1$s before trying again.',
                        'nexipilot-content-ai'
                    ),
                    human_time_diff(time(), time() + $wait_time)
                );

            } else {

                $message = sprintf(
                    /* translators: %1$d: maximum number of FAQ generations allowed per day */
                    esc_html__(
                        'You have reached your daily FAQ generation limit (%1$d per day). Please try again tomorrow.',
                        'nexipilot-content-ai'
                    ),
                    \PostPilotAI\Helpers\RateLimiter::get_daily_limit()
                );
            }

            wp_send_json_error(array(
                'message' => $message,
                'rate_limited' => true,
                'wait_time' => $wait_time,
                'post_remaining' => $post_remaining,
                'daily_remaining' => $daily_remaining,
            ));
        }

        // Get demo FAQ from AI Manager
        $demo_faq = $this->ai_manager->get_demo_faq();

        // Record successful generation
        \PostPilotAI\Helpers\RateLimiter::record_generation($user_id, $post_id);

        // Save to post meta
        update_post_meta($post_id, '_nexipilot_faqs', $demo_faq);

        // Return HTML for FAQ items
        ob_start();
        foreach ($demo_faq as $index => $faq) {
            $this->render_faq_item($index, $faq);
        }
        $html = ob_get_clean();

        wp_send_json_success(array(
            'message' => esc_html__('Demo FAQ added successfully!', 'nexipilot-content-ai'),
            'html' => $html,
            'count' => count($demo_faq),
        ));
    }

    /**
     * AJAX handler to check API status
     *
     * @since 1.0.0
     * @return void
     */
    public function ajax_check_api_status()
    {
        try {
            // Security checks
            check_ajax_referer('nexipilot_generate_faq', 'nonce');

            // Check if user has permission
            if (!current_user_can('edit_posts')) {
                wp_send_json_error(array(
                    'message' => esc_html__('Permission denied.', 'nexipilot-content-ai'),
                ));
                return;
            }

            // Check if AI provider is available
            if (!$this->ai_manager->is_provider_available()) {
                wp_send_json_success(array(
                    'available' => false,
                    'message' => esc_html__('AI service is not configured. Please add your API key in PostPilot settings.', 'nexipilot-content-ai'),
                ));
                return;
            }

            // Try to make a test API call to check for quota/errors
            $test_result = $this->ai_manager->test_api_connection();

            if (is_wp_error($test_result)) {
                // API call failed - return the specific error message
                Logger::info('API status check failed', array(
                    'error' => $test_result->get_error_message(),
                ));

                wp_send_json_success(array(
                    'available' => false,
                    'message' => $test_result->get_error_message(),
                ));
                return;
            }

            // API is working fine
            wp_send_json_success(array(
                'available' => true,
                'message' => esc_html__('AI service is available.', 'nexipilot-content-ai'),
            ));

        } catch (\Exception $e) {
            // Catch any PHP errors and return them
            Logger::error('API status check exception', array(
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ));

            wp_send_json_error(array(
                'message' => 'Error checking API status: ' . $e->getMessage(),
            ));
        }
    }
}
