<?php
/**
 * OpenAI.php
 *
 * OpenAI/ChatGPT provider implementation.
 *
 * @package PostPilot\AI
 * @since 1.0.0
 * @author Md Abul Bashar <hmbashar@gmail.com>
 */

namespace PostPilot\AI;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use PostPilot\Helpers\Logger;

/**
 * OpenAI Provider Class
 *
 * Implements the ProviderInterface for OpenAI/ChatGPT.
 *
 * @package PostPilot\AI
 * @since 1.0.0
 */
class OpenAI implements ProviderInterface
{
    /**
     * API key
     *
     * @var string
     */
    private $api_key;

    /**
     * API endpoint
     *
     * @var string
     */
    private $api_endpoint = 'https://api.openai.com/v1/chat/completions';

    /**
     * Model to use
     *
     * @var string
     */
    private $model = 'gpt-3.5-turbo';

    /**
     * Constructor
     *
     * @since 1.0.0
     * @param string $api_key The OpenAI API key.
     */
    public function __construct($api_key)
    {
        $this->api_key = $api_key;
    }

    /**
     * Generate FAQ from content
     *
     * @since 1.0.0
     * @param string $content The post content.
     * @return array|WP_Error Array of FAQ items or WP_Error on failure
     */
    public function generate_faq($content)
    {
        $prompt = sprintf(
            'Based on the following content, generate 4-5 frequently asked questions with answers. Return the response as a JSON array with objects containing "question" and "answer" keys. Content: %s',
            wp_strip_all_tags($content)
        );

        $response = $this->make_request($prompt);

        if (is_wp_error($response)) {
            return $response;
        }

        // Parse JSON response
        $faq_data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            // If not valid JSON, create a simple structure
            return array(
                array(
                    'question' => __('What is this content about?', 'postpilot'),
                    'answer' => $response,
                ),
            );
        }

        return $faq_data;
    }

    /**
     * Generate summary from content
     *
     * @since 1.0.0
     * @param string $content The post content.
     * @return string|WP_Error Summary text or WP_Error on failure
     */
    public function generate_summary($content)
    {
        $prompt = sprintf(
            'Create a concise, engaging summary (2-3 sentences) of the following content: %s',
            wp_strip_all_tags($content)
        );

        return $this->make_request($prompt);
    }

    /**
     * Suggest internal links
     *
     * @since 1.0.0
     * @param string $content The post content.
     * @param array  $available_posts Available posts for linking.
     * @return array|WP_Error Array of link suggestions or WP_Error on failure
     */
    public function suggest_internal_links($content, $available_posts)
    {
        $posts_list = array();
        foreach ($available_posts as $post) {
            $posts_list[] = sprintf(
                'ID: %d, Title: %s, URL: %s',
                $post->ID,
                $post->post_title,
                get_permalink($post->ID)
            );
        }

        $prompt = sprintf(
            'Analyze this content and suggest 3-5 relevant internal links from the available posts. Return as JSON array with "keyword" and "post_id" keys. Content: %s. Available posts: %s',
            wp_strip_all_tags($content),
            implode('; ', $posts_list)
        );

        $response = $this->make_request($prompt);

        if (is_wp_error($response)) {
            return $response;
        }

        // Parse JSON response
        $links_data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return array();
        }

        return $links_data;
    }

    /**
     * Validate API key
     *
     * @since 1.0.0
     * @param string $api_key The API key to validate.
     * @return bool|WP_Error True if valid, WP_Error on failure
     */
    public function validate_api_key($api_key)
    {
        $response = wp_remote_get(
            'https://api.openai.com/v1/models',
            array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $api_key,
                ),
                'timeout' => 10,
            )
        );

        if (is_wp_error($response)) {
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code === 200) {
            return true;
        }

        return new \WP_Error(
            'invalid_api_key',
            __('Invalid OpenAI API key.', 'postpilot')
        );
    }

    /**
     * Make API request
     *
     * @since 1.0.0
     * @param string $prompt The prompt to send.
     * @return string|WP_Error Response text or WP_Error on failure
     */
    private function make_request($prompt)
    {
        if (empty($this->api_key)) {
            return new \WP_Error(
                'missing_api_key',
                __('OpenAI API key is not configured.', 'postpilot')
            );
        }

        $body = array(
            'model' => $this->model,
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $prompt,
                ),
            ),
            'temperature' => 0.7,
            'max_tokens' => 500,
        );

        Logger::log_api_request('OpenAI', $this->api_endpoint, $body);

        $response = wp_remote_post(
            $this->api_endpoint,
            array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $this->api_key,
                    'Content-Type' => 'application/json',
                ),
                'body' => wp_json_encode($body),
                'timeout' => 30,
            )
        );

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            $error_body = wp_remote_retrieve_body($response);
            $error_data = json_decode($error_body, true);
            
            $error_code = wp_remote_retrieve_response_code($response);
            $error_message = isset($error_data['error']['message']) ? $error_data['error']['message'] : 'Unknown error';
            $error_type = isset($error_data['error']['type']) ? $error_data['error']['type'] : '';
            
            Logger::error('API Response from OpenAI', array('response' => $error_body));
            
            // Provide user-friendly error messages for common errors
            if ($error_type === 'insufficient_quota' || $error_code === 429) {
                return new \WP_Error(
                    'openai_quota_exceeded',
                    __('OpenAI quota exceeded. Please add credits to your OpenAI account at platform.openai.com/account/billing', 'postpilot')
                );
            } elseif ($error_code === 401) {
                return new \WP_Error(
                    'openai_invalid_key',
                    __('Invalid OpenAI API key. Please check your API key in PostPilot settings.', 'postpilot')
                );
            } else {
                return new \WP_Error(
                    'openai_api_error',
                    sprintf(__('OpenAI API error (Code: %d): %s', 'postpilot'), $error_code, $error_message)
                );
            }
        }

        $response_body = wp_remote_retrieve_body($response);
        $data = json_decode($response_body, true);
        
        if (isset($data['choices'][0]['message']['content'])) {
            $content = $data['choices'][0]['message']['content'];
            Logger::log_api_response('OpenAI', $content);
            return $content;
        }

        return new \WP_Error(
            'invalid_response',
            __('Invalid response from OpenAI API.', 'postpilot')
        );
    }
}
