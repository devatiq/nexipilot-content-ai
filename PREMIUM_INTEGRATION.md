# NexiPilot Pro - Premium Version Integration Guide

## Overview

This document explains how to create a premium version of NexiPilot that increases rate limits using WordPress filters.

---

## How Rate Limits Work

The free version has these limits **hardcoded** in the plugin:
- **Per-Post**: 2 generations every 5 minutes
- **Daily**: 30 generations per 24 hours

These limits are **NOT visible** in any settings or constants that users can easily find. They are embedded directly in method return values.

---

## Creating a Premium Version

### Method 1: Separate Premium Plugin

Create a separate plugin (e.g., `nexipilot-pro`) that uses WordPress filters to override limits:

```php
<?php
/**
 * Plugin Name: NexiPilot Pro
 * Description: Premium features for NexiPilot
 * Version: 1.0.0
 */

// Increase per-post limit to 10
add_filter('nexipilot_rate_limit_post', function($limit) {
    return 10; // Premium: 10 generations per post
});

// Increase per-post window to 10 minutes
add_filter('nexipilot_rate_limit_post_window', function($window) {
    return 600; // Premium: 10 minutes
});

// Increase daily limit to 200
add_filter('nexipilot_rate_limit_daily', function($limit) {
    return 200; // Premium: 200 per day
});

// Keep daily window at 24 hours
add_filter('nexipilot_rate_limit_daily_window', function($window) {
    return 86400; // 24 hours
});
```

### Method 2: License-Based Activation

Check for a valid license before applying premium limits:

```php
<?php
// In your premium plugin or theme

function nexipilot_pro_apply_limits() {
    // Check if user has valid license
    $license_key = get_option('nexipilot_pro_license_key');
    
    if (nexipilot_pro_verify_license($license_key)) {
        // Apply premium limits
        add_filter('nexipilot_rate_limit_post', function() {
            return 10; // Premium limit
        });
        
        add_filter('nexipilot_rate_limit_daily', function() {
            return 200; // Premium limit
        });
    }
}
add_action('plugins_loaded', 'nexipilot_pro_apply_limits');

function nexipilot_pro_verify_license($license_key) {
    // Your license verification logic
    // Check against your licensing server
    return true; // or false
}
```

### Method 3: Tiered Plans

Different limits for different plan levels:

```php
<?php
function nexipilot_apply_plan_limits() {
    $plan = get_option('nexipilot_plan', 'free'); // free, starter, pro, enterprise
    
    switch ($plan) {
        case 'starter':
            add_filter('nexipilot_rate_limit_post', fn() => 5);
            add_filter('nexipilot_rate_limit_daily', fn() => 100);
            break;
            
        case 'pro':
            add_filter('nexipilot_rate_limit_post', fn() => 10);
            add_filter('nexipilot_rate_limit_daily', fn() => 200);
            break;
            
        case 'enterprise':
            add_filter('nexipilot_rate_limit_post', fn() => 50);
            add_filter('nexipilot_rate_limit_daily', fn() => 1000);
            break;
            
        default: // free
            // Use default limits (no filters needed)
            break;
    }
}
add_action('plugins_loaded', 'nexipilot_apply_plan_limits');
```

---

## Available Filters

| Filter Name | Default Value | Description |
|------------|---------------|-------------|
| `nexipilot_rate_limit_post` | `2` | Generations per post |
| `nexipilot_rate_limit_post_window` | `300` | Time window in seconds (5 min) |
| `nexipilot_rate_limit_daily` | `30` | Generations per day |
| `nexipilot_rate_limit_daily_window` | `86400` | Daily window in seconds (24 hours) |

---

## Example: Complete Premium Plugin

```php
<?php
/**
 * Plugin Name: NexiPilot Pro
 * Plugin URI: https://yoursite.com/nexipilot-pro
 * Description: Premium features for NexiPilot with increased rate limits
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL-2.0+
 */

if (!defined('ABSPATH')) {
    exit;
}

class nexipilot_Pro {
    
    private $license_key;
    
    public function __construct() {
        $this->license_key = get_option('nexipilot_pro_license');
        
        add_action('plugins_loaded', [$this, 'init']);
        add_action('admin_menu', [$this, 'add_license_page']);
    }
    
    public function init() {
        if ($this->is_license_valid()) {
            $this->apply_premium_limits();
        }
    }
    
    public function apply_premium_limits() {
        // 10x the free limits
        add_filter('nexipilot_rate_limit_post', function() {
            return 20; // 10x free version
        });
        
        add_filter('nexipilot_rate_limit_daily', function() {
            return 300; // 10x free version
        });
    }
    
    public function is_license_valid() {
        if (empty($this->license_key)) {
            return false;
        }
        
        // Check license with your server
        $response = wp_remote_post('https://yoursite.com/api/verify-license', [
            'body' => [
                'license_key' => $this->license_key,
                'domain' => home_url(),
            ],
        ]);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        return isset($data['valid']) && $data['valid'] === true;
    }
    
    public function add_license_page() {
        add_submenu_page(
            'nexipilot-settings',
            'NexiPilot Pro License',
            'Pro License',
            'manage_options',
            'nexipilot-pro-license',
            [$this, 'render_license_page']
        );
    }
    
    public function render_license_page() {
        ?>
        <div class="wrap">
            <h1>NexiPilot Pro License</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('nexipilot_pro');
                do_settings_sections('nexipilot_pro');
                ?>
                <table class="form-table">
                    <tr>
                        <th>License Key</th>
                        <td>
                            <input type="text" 
                                   name="nexipilot_pro_license" 
                                   value="<?php echo esc_attr($this->license_key); ?>" 
                                   class="regular-text" />
                            <p class="description">
                                Enter your NexiPilot Pro license key to unlock premium features.
                            </p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            
            <?php if ($this->is_license_valid()): ?>
                <div class="notice notice-success">
                    <p><strong>✓ License Active</strong> - Premium rate limits enabled!</p>
                    <ul>
                        <li>Per-Post Limit: 20 generations (vs 2 in free)</li>
                        <li>Daily Limit: 300 generations (vs 30 in free)</li>
                    </ul>
                </div>
            <?php else: ?>
                <div class="notice notice-warning">
                    <p><strong>Free Version Active</strong> - Upgrade to Pro for higher limits!</p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}

new nexipilot_Pro();
```

---

## Why Users Can't Easily Change Limits

1. **No Constants**: Limits are not defined as class constants anymore
2. **Inline Values**: Limits are hardcoded in method return values
3. **No Settings UI**: No admin interface to change limits
4. **Obfuscated**: Values are just numbers in code, not labeled
5. **Filter-Based**: Only way to change is via WordPress filters (requires coding knowledge)

---

## Monetization Strategy

### Free Version
- 2 generations per post / 5 minutes
- 30 generations per day
- Perfect for small blogs

### Starter Plan ($9/month)
- 5 generations per post
- 100 generations per day
- For growing sites

### Pro Plan ($29/month)
- 10 generations per post
- 200 generations per day
- For professional sites

### Enterprise Plan ($99/month)
- 50 generations per post
- 1000 generations per day
- For agencies and large sites

---

## Testing Premium Features

To test premium limits during development:

```php
// Add to functions.php temporarily
add_filter('nexipilot_rate_limit_post', fn() => 999);
add_filter('nexipilot_rate_limit_daily', fn() => 9999);
```

---

## Security Considerations

1. **License Verification**: Always verify licenses server-side
2. **Domain Locking**: Tie licenses to specific domains
3. **Expiration**: Check license expiration dates
4. **Tampering**: Don't trust client-side license data
5. **Rate Limiting**: Even premium users should have some limits

---

## Summary

The free version has **hidden, hardcoded limits** that users cannot easily find or change. Premium versions use **WordPress filters** to override these limits based on license validation. This creates a clear upgrade path and protects your revenue model.
