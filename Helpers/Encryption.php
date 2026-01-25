<?php
/**
 * Encryption Helper
 *
 * Handles encryption and decryption of sensitive data like API keys
 *
 * @package PostPilot
 * @since 1.0.0
 */

namespace PostPilotAI\Helpers;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Encryption class
 */
class Encryption
{
    /**
     * Encryption method
     *
     * @var string
     */
    private static $method = 'AES-256-CBC';

    /**
     * Get encryption key
     *
     * Uses WordPress salts for key generation
     *
     * @return string
     */
    private static function get_key()
    {
        // Use WordPress AUTH_KEY and SECURE_AUTH_KEY for encryption
        // These are unique per WordPress installation
        $key = AUTH_KEY . SECURE_AUTH_KEY;

        // Hash to get consistent 32-byte key for AES-256
        return hash('sha256', $key, true);
    }

    /**
     * Encrypt data
     *
     * @param string $data Data to encrypt
     * @return string|false Encrypted data or false on failure
     */
    public static function encrypt($data)
    {
        if (empty($data)) {
            return $data;
        }

        try {
            // Generate a random IV (Initialization Vector)
            $iv_length = openssl_cipher_iv_length(self::$method);
            $iv = openssl_random_pseudo_bytes($iv_length);

            // Encrypt the data
            $encrypted = openssl_encrypt(
                $data,
                self::$method,
                self::get_key(),
                OPENSSL_RAW_DATA,
                $iv
            );

            if ($encrypted === false) {
                return false;
            }

            // Combine IV and encrypted data, then base64 encode
            return base64_encode($iv . $encrypted);
        } catch (\Exception $e) {
            if ('1' === get_option('postpilotai_enable_debug_logging', '')) {
                Logger::error(
                    'PostPilot Encryption Error',
                    array(
                        'exception' => $e->getMessage(),
                    )
                );
            }

            return false;
        }
    }

    /**
     * Decrypt data
     *
     * @param string $data Data to decrypt
     * @return string|false Decrypted data or false on failure
     */
    public static function decrypt($data)
    {
        if (empty($data)) {
            return $data;
        }

        try {
            // Base64 decode
            $decoded = base64_decode($data, true);

            if ($decoded === false) {
                // If base64 decode fails, data might not be encrypted
                // Return original data for backward compatibility
                return $data;
            }

            // Extract IV and encrypted data
            $iv_length = openssl_cipher_iv_length(self::$method);
            $iv = substr($decoded, 0, $iv_length);
            $encrypted = substr($decoded, $iv_length);

            // Decrypt the data
            $decrypted = openssl_decrypt(
                $encrypted,
                self::$method,
                self::get_key(),
                OPENSSL_RAW_DATA,
                $iv
            );

            if ($decrypted === false) {
                // Decryption failed, might be unencrypted legacy data
                return $data;
            }

            return $decrypted;
        } catch (\Exception $e) {
            Logger::error(
                'PostPilot Decryption Error',
                array(
                    'exception' => $e->getMessage(),
                )
            );
            // Return original data if decryption fails (backward compatibility)
            return $data;
        }
    }

    /**
     * Check if data is encrypted
     *
     * @param string $data Data to check
     * @return bool
     */
    public static function is_encrypted($data)
    {
        if (empty($data)) {
            return false;
        }

        // Check if data is base64 encoded (encrypted data is base64)
        $decoded = base64_decode($data, true);

        if ($decoded === false) {
            return false;
        }

        // Check if decoded data has correct IV length
        $iv_length = openssl_cipher_iv_length(self::$method);

        return strlen($decoded) > $iv_length;
    }
}
