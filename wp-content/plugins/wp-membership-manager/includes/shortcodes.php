<?php

// Enqueue scripts and styles
function wpmm_enqueue_scripts() {
    wp_enqueue_style('wpmm-style', plugins_url('assets/css/wpmm-style.css', __FILE__));
    wp_enqueue_script('wpmm-script', plugins_url('assets/js/wpmm-script.js', __FILE__), array('jquery'), false, true);
    wp_enqueue_script('stripe-js', 'https://js.stripe.com/v3/');
}
add_action('wp_enqueue_scripts', 'wpmm_enqueue_scripts');

// Registration Form Shortcode
function wpmm_registration_form() {
    // Replace 'your-stripe-publishable-key' with your actual Stripe publishable key
    $stripe_publishable_key = 'pk_test_51PRj4aHrZfxkHCcnhKjEkTIKhaASMGZaE6iDQfHE4MaxcC1xvqfafGBBXEFYOO1AC0In0YwGJbDa4yFeM3DckrGQ00onFkBwh5';
    ob_start();
    ?>
    <form method="post" id="wpmm-registration-form">
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

        <button type="button" id="stripe-pay-button" class="wpmm-button">Pay with Stripe</button>
    </form>

    <script>
        document.getElementById('stripe-pay-button').addEventListener('click', function() {
            var form = document.getElementById('wpmm-registration-form');
            var formData = new FormData(form);
            formData.append('action', 'wpmm_handle_registration_form_submission');
            var xhr = new XMLHttpRequest();
            xhr.open('POST', '<?php echo admin_url('admin-ajax.php'); ?>');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);
                    if (response.sessionId) {
                        var stripe = Stripe('<?php echo $stripe_publishable_key; ?>');
                        stripe.redirectToCheckout({ sessionId: response.sessionId });
                    } else {
                        alert('Payment failed. Please try again.');
                    }
                }
            };
            xhr.send(formData);
        });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('wpmm_registration_form', 'wpmm_registration_form');

// Handle registration form submission
function wpmm_handle_registration_form_submission() {
    if (isset($_POST['username'], $_POST['email'], $_POST['password'], $_POST['membership_plan'])) {
        $username = sanitize_text_field($_POST['username']);
        $email = sanitize_email($_POST['email']);
        $password = sanitize_text_field($_POST['password']);
        $membership_plan = intval($_POST['membership_plan']);

        // Ensure username and email are unique
        if (username_exists($username) || email_exists($email)) {
            echo json_encode(array('error' => 'Username or Email already exists. Please choose another.'));
            wp_die();
        }

        // Create custom data to send to Stripe
        $custom_data = array(
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'membership_plan' => $membership_plan
        );

        // Process Stripe payment
        wpmm_process_stripe_payment($custom_data);
    }
}
add_action('wp_ajax_nopriv_wpmm_handle_registration_form_submission', 'wpmm_handle_registration_form_submission');
add_action('wp_ajax_wpmm_handle_registration_form_submission', 'wpmm_handle_registration_form_submission');

// Login Form Shortcode
function wpmm_login_form() {
    if (is_user_logged_in()) {
        wp_redirect(home_url()); // Redirect to the homepage or any accessible page after login
        exit;
    } else {
        ob_start();
        ?>
        <div class="wpmm-login-form">
            <?php
            wp_login_form(array(
                'redirect' => home_url(), // Redirect to the homepage or any accessible page after login
                'label_log_in' => __('Login', 'textdomain'),
                'form_id' => 'wpmm-login-form',
                'id_submit' => 'wpmm-login-submit',
                'class_submit' => 'wpmm-button'
            ));
            ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
add_shortcode('wpmm_login_form', 'wpmm_login_form');

