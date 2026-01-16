<?php
/**
 * FAQMetaBox.php
 *
 * Handles FAQ meta box in post editor.
 *
 * @package PostPilot\Admin\MetaBox
 * @since 1.0.0
 */

namespace PostPilot\Admin\MetaBox;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use PostPilot\AI\Manager as AIManager;
use PostPilot\Helpers\Sanitizer;
use PostPilot\Helpers\Logger;

/**
 * FAQ Meta Box Class
 *
 * Manages FAQ generation, editing, and display in post editor.
 *
 * @package PostPilot\Admin\MetaBox
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
        add_action('wp_ajax_postpilot_generate_faq', array($this, 'ajax_generate_faq'));
        add_action('wp_ajax_postpilot_generate_demo_faq', array($this, 'ajax_generate_demo_faq'));
        add_action('wp_ajax_postpilot_check_api_status', array($this, 'ajax_check_api_status'));
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
            'postpilot_faq_metabox',
            __('PostPilot AI - FAQ Generator', 'postpilot'),
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
        wp_nonce_field('postpilot_faq_metabox', 'postpilot_faq_nonce');

        // Get saved data
        $faq_enabled = get_post_meta($post->ID, '_postpilot_faq_enabled', true);
        $faqs = get_post_meta($post->ID, '_postpilot_faqs', true);
        
        // Default to enabled if not set
        if ($faq_enabled === '') {
            $faq_enabled = '1';
        }

        $has_faqs = !empty($faqs) && is_array($faqs);
        ?>
        <div class="postpilot-faq-metabox">
            <!-- Enable/Disable FAQ -->
            <div class="postpilot-faq-enable">
                <label>
                    <input type="checkbox" 
                           name="postpilot_faq_enabled" 
                           value="1" 
                           <?php checked($faq_enabled, '1'); ?> />
                    <strong><?php esc_html_e('Display FAQ on this post?', 'postpilot'); ?></strong>
                </label>
                <p class="description">
                    <?php esc_html_e('Check this to show AI-generated FAQs on the frontend.', 'postpilot'); ?>
                </p>
            </div>

            <!-- FAQ Repeater Fields -->
            <div class="postpilot-faq-fields" style="margin-top: 20px;">
                <div class="postpilot-faq-header">
                    <h4><?php esc_html_e('FAQ Items', 'postpilot'); ?></h4>
                    <button type="button" 
                            class="button button-secondary postpilot-add-faq-item">
                        <?php esc_html_e('+ Add FAQ Item', 'postpilot'); ?>
                    </button>
                </div>

                <div class="postpilot-faq-items">
                    <?php
                    if ($has_faqs) {
                        foreach ($faqs as $index => $faq) {
                            $this->render_faq_item($index, $faq);
                        }
                    } else {
                        echo '<p class="postpilot-no-faqs">' . esc_html__('No FAQs yet. Click "Generate FAQ" to create them automatically.', 'postpilot') . '</p>';
                    }
                    ?>
                </div>
            </div>

            <!-- Generate/Regenerate Button -->
            <div class="postpilot-faq-actions" style="margin-top: 20px;">
                <button type="button" 
                        class="button button-primary postpilot-generate-faq" 
                        data-post-id="<?php echo esc_attr($post->ID); ?>">
                    <?php echo $has_faqs ? esc_html__('Regenerate FAQ', 'postpilot') : esc_html__('Generate FAQ', 'postpilot'); ?>
                </button>
                <span class="spinner"></span>
                <p class="description">
                    <?php esc_html_e('Generate FAQs using AI based on your post content.', 'postpilot'); ?>
                </p>
            </div>
        </div>

        <!-- Hidden template for new FAQ items -->
        <script type="text/template" id="postpilot-faq-item-template">
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
        <div class="postpilot-faq-item" data-index="<?php echo esc_attr($index); ?>">
            <div class="postpilot-faq-item-header">
                <span class="postpilot-faq-item-number"><?php echo esc_html(sprintf(__('FAQ #%s', 'postpilot'), is_numeric($index) ? ($index + 1) : '{{NUMBER}}')); ?></span>
                <button type="button" class="button-link postpilot-remove-faq-item" title="<?php esc_attr_e('Remove this FAQ', 'postpilot'); ?>">
                    <span class="dashicons dashicons-trash"></span>
                </button>
            </div>
            <div class="postpilot-faq-item-fields">
                <div class="postpilot-faq-field">
                    <label><?php esc_html_e('Question:', 'postpilot'); ?></label>
                    <input type="text" 
                           name="postpilot_faqs[<?php echo esc_attr($index); ?>][question]" 
                           value="<?php echo esc_attr($question); ?>" 
                           class="widefat" 
                           placeholder="<?php esc_attr_e('Enter question...', 'postpilot'); ?>" />
                </div>
                <div class="postpilot-faq-field">
                    <label><?php esc_html_e('Answer:', 'postpilot'); ?></label>
                    <textarea name="postpilot_faqs[<?php echo esc_attr($index); ?>][answer]" 
                              class="widefat" 
                              rows="3" 
                              placeholder="<?php esc_attr_e('Enter answer...', 'postpilot'); ?>"><?php echo esc_textarea($answer); ?></textarea>
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
        if (!isset($_POST['postpilot_faq_nonce']) || !wp_verify_nonce($_POST['postpilot_faq_nonce'], 'postpilot_faq_metabox')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save enabled status
        $faq_enabled = isset($_POST['postpilot_faq_enabled']) ? '1' : '0';
        update_post_meta($post_id, '_postpilot_faq_enabled', $faq_enabled);

        // Save FAQ items
        if (isset($_POST['postpilot_faqs']) && is_array($_POST['postpilot_faqs'])) {
            $faqs = array();
            
            foreach ($_POST['postpilot_faqs'] as $faq) {
                if (!empty($faq['question']) || !empty($faq['answer'])) {
                    $faqs[] = array(
                        'question' => sanitize_text_field($faq['question']),
                        'answer' => wp_kses_post($faq['answer']),
                    );
                }
            }

            update_post_meta($post_id, '_postpilot_faqs', $faqs);
        } else {
            // If no FAQs submitted, check if we should auto-generate
            $existing_faqs = get_post_meta($post_id, '_postpilot_faqs', true);
            
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
            update_post_meta($post_id, '_postpilot_faqs', $faq_data);
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
        check_ajax_referer('postpilot_generate_faq', 'nonce');

        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;

        if (!$post_id || !current_user_can('edit_post', $post_id)) {
            wp_send_json_error(array(
                'message' => __('Permission denied.', 'postpilot'),
            ));
        }

        $post = get_post($post_id);
        
        if (!$post) {
            wp_send_json_error(array(
                'message' => __('Post not found.', 'postpilot'),
            ));
        }

        // Generate FAQ
        $faq_data = $this->ai_manager->get_faq($post_id, $post->post_content);
        
        if (is_wp_error($faq_data)) {
            wp_send_json_error(array(
                'message' => $faq_data->get_error_message(),
            ));
        }

        // Save to post meta
        update_post_meta($post_id, '_postpilot_faqs', $faq_data);

        // Return HTML for FAQ items
        ob_start();
        foreach ($faq_data as $index => $faq) {
            $this->render_faq_item($index, $faq);
        }
        $html = ob_get_clean();

        wp_send_json_success(array(
            'message' => __('FAQ generated successfully!', 'postpilot'),
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
        check_ajax_referer('postpilot_generate_faq', 'nonce');

        $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;

        if (!$post_id || !current_user_can('edit_post', $post_id)) {
            wp_send_json_error(array(
                'message' => __('Permission denied.', 'postpilot'),
            ));
        }

        // Get demo FAQ from AI Manager
        $demo_faq = $this->ai_manager->get_demo_faq();

        // Save to post meta
        update_post_meta($post_id, '_postpilot_faqs', $demo_faq);

        // Return HTML for FAQ items
        ob_start();
        foreach ($demo_faq as $index => $faq) {
            $this->render_faq_item($index, $faq);
        }
        $html = ob_get_clean();

        wp_send_json_success(array(
            'message' => __('Demo FAQ added successfully!', 'postpilot'),
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
        // Security checks
        check_ajax_referer('postpilot_generate_faq', 'nonce');

        // Check if AI provider is available
        if (!$this->ai_manager->is_provider_available()) {
            wp_send_json_success(array(
                'available' => false,
                'message' => __('AI service is not configured. Please add your API key in PostPilot settings.', 'postpilot'),
            ));
        }

        // Try to make a test API call to check for quota/errors
        // We'll use a minimal test prompt to check API status
        $test_result = $this->ai_manager->test_api_connection();
        
        if (is_wp_error($test_result)) {
            // API call failed - return the specific error message
            wp_send_json_success(array(
                'available' => false,
                'message' => $test_result->get_error_message(),
            ));
        }

        // API is working fine
        wp_send_json_success(array(
            'available' => true,
            'message' => __('AI service is available.', 'postpilot'),
        ));
    }
}
