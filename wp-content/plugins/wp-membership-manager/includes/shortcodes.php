<?php

// Registration Form Shortcode
function wpmm_registration_form() {
    ob_start();
    ?>
    <form method="post" id="wpmm-registration-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required>
        
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>
        
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        
        <label for="membership_plan">Membership Plan:</label>
        <select id="membership_plan" name="membership_plan" required>
            <?php
            $plans = get_posts(array('post_type' => 'membership_plan', 'posts_per_page' => -1));
            foreach ($plans as $plan) {
                echo '<option value="' . esc_attr($plan->ID) . '">' . esc_html($plan->post_title) . '</option>';
            }
            ?>
        </select>
        
        <input type="hidden" name="action" value="process_registration">
        <button type="submit" name="wpmm_register" class="wpmm-button">Register and Pay</button>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('wpmm_registration_form', 'wpmm_registration_form');

// Handle registration form submission
function wpmm_handle_registration_form_submission() {
    if (isset($_POST['action']) && $_POST['action'] == 'process_registration' && isset($_POST['username'], $_POST['email'], $_POST['password'], $_POST['membership_plan'])) {
        $username = sanitize_text_field($_POST['username']);
        $email = sanitize_email($_POST['email']);
        $password = sanitize_text_field($_POST['password']);
        $membership_plan = intval($_POST['membership_plan']);

        // Ensure username and email are unique
        if (username_exists($username) || email_exists($email)) {
            wp_die('Username or Email already exists. Please choose another.');
        }

        // Process payment with PayPal
        wpmm_process_paypal_payment($username, $email, $password, $membership_plan);
    }
}
add_action('admin_post_nopriv_process_registration', 'wpmm_handle_registration_form_submission');
add_action('admin_post_process_registration', 'wpmm_handle_registration_form_submission');

// Login Form Shortcode
function wpmm_login_form() {
    if (is_user_logged_in()) {
        wp_redirect(home_url()); // Redirect to homepage or any accessible page after login
        exit;
    } else {
        ob_start();
        wp_login_form(array(
            'redirect' => home_url(), // Redirect to homepage or any accessible page after login
            'label_log_in' => __('Login', 'textdomain'),
            'form_id' => 'wpmm-login-form',
            'id_submit' => 'wpmm-login-submit',
            'class_submit' => 'wpmm-button'
        ));
        return ob_get_clean();
    }
}
add_shortcode('wpmm_login_form', 'wpmm_login_form');


// Shortcode to trigger the webhook test
function wpmm_webhook_test_shortcode() {
    ob_start();
    require_once WPMM_PLUGIN_DIR . 'includes/webhook-test.php';
    return ob_get_clean();
}
add_shortcode('wpmm_webhook_test', 'wpmm_webhook_test_shortcode');
