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

// Custom redirect after login
function wpmm_login_redirect($redirect_to, $request, $user) {
    // Check if the user is a valid WP_User and not an error
    if (isset($user->roles) && is_array($user->roles)) {
        // Redirect to the homepage after login
        return home_url();
    }

    return $redirect_to;
}
add_filter('login_redirect', 'wpmm_login_redirect', 10, 3);

// Add your Stripe API keys
define('STRIPE_API_KEY', 'sk_test_51PRj4aHrZfxkHCcnjYNK7r3Ev1e1sIlU4R3itbutVSG1fJKAzfEOehjvFZz7B9A8v5Hu0fF0Dh9sv5ZYmbrd9swh00VLTD1J2Q');
define('STRIPE_PUBLISHABLE_KEY', 'pk_test_51PRj4aHrZfxkHCcnhKjEkTIKhaASMGZaE6iDQfHE4MaxcC1xvqfafGBBXEFYOO1AC0In0YwGJbDa4yFeM3DckrGQ00onFkBwh5');
