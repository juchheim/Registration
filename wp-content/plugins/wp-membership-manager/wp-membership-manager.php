<?php
/*
Plugin Name: WP Membership Manager
Description: A custom plugin to manage memberships and restrict content.
Version: 1.0
Author: Your Name
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Define constants
define( 'WPMM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPMM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Include required files
require_once WPMM_PLUGIN_DIR . 'includes/custom-post-types.php';
require_once WPMM_PLUGIN_DIR . 'includes/user-roles.php';
require_once WPMM_PLUGIN_DIR . 'includes/shortcodes.php';
require_once WPMM_PLUGIN_DIR . 'includes/content-restriction.php';
require_once WPMM_PLUGIN_DIR . 'includes/payment-integration.php';

// Enqueue scripts and styles
function wpmm_enqueue_scripts() {
    wp_enqueue_style('wpmm-style', plugins_url('assets/css/wpmm-style.css', __FILE__));
    wp_enqueue_script('wpmm-script', plugins_url('assets/js/wpmm-script.js', __FILE__), array('jquery'), false, true);
    wp_enqueue_script('stripe-js', 'https://js.stripe.com/v3/');
}
add_action('wp_enqueue_scripts', 'wpmm_enqueue_scripts');

// Redirect after login based on user role
function wpmm_login_redirect($redirect_to, $request, $user) {
    if (isset($user->roles) && is_array($user->roles)) {
        // Redirect to the homepage after login
        return home_url();
    }

    return $redirect_to;
}
add_filter('login_redirect', 'wpmm_login_redirect', 10, 3);

// Handle login errors
function wpmm_login_failed() {
    $referrer = wp_get_referer();
    if ($referrer && !strstr($referrer, 'wp-login') && !strstr($referrer, 'wp-admin')) {
        wp_redirect($referrer . '?login=failed');
        exit;
    }
}
add_action('wp_login_failed', 'wpmm_login_failed');


// Add your Stripe API keys
define('STRIPE_API_KEY', 'sk_test_51PRj4aHrZfxkHCcnjYNK7r3Ev1e1sIlU4R3itbutVSG1fJKAzfEOehjvFZz7B9A8v5Hu0fF0Dh9sv5ZYmbrd9swh00VLTD1J2Q');
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_51PRj4aHrZfxkHCcnhKjEkTIKhaASMGZaE6iDQfHE4MaxcC1xvqfafGBBXEFYOO1AC0In0YwGJbDa4yFeM3DckrGQ00onFkBwh5');


// Handle login nonce validation
function wpmm_validate_login_nonce($user, $username, $password) {
    if (isset($_POST['wpmm_login_nonce']) && !wp_verify_nonce($_POST['wpmm_login_nonce'], 'wpmm_login_action')) {
        return new WP_Error('nonce_verification_failed', 'Nonce verification failed');
    }
    return $user;
}
add_filter('authenticate', 'wpmm_validate_login_nonce', 10, 3);
