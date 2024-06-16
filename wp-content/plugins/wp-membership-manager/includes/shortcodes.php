<?php

// Registration Form Shortcode
function wpmm_registration_form() {
    $stripe_publishable_key = 'pk_test_51PRj4aHrZfxkHCcnhKjEkTIKhaASMGZaE6iDQfHE4MaxcC1xvqfafGBBXEFYOO1AC0In0YwGJbDa4yFeM3DckrGQ00onFkBwh5';
    ob_start();
    ?>
    <form method="post" id="wpmm-registration-form" class="wpmm-form">
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

        <label for="payment_type">Payment Type:</label>
        <select id="payment_type" name="payment_type" required>
            <option value="single">Single Payment</option>
            <option value="recurring">Recurring Payment</option>
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
    if (isset($_POST['username'], $_POST['email'], $_POST['password'], $_POST['membership_plan'], $_POST['payment_type'])) {
        $username = sanitize_text_field($_POST['username']);
        $email = sanitize_email($_POST['email']);
        $password = sanitize_text_field($_POST['password']);
        $membership_plan = intval($_POST['membership_plan']);
        $payment_type = sanitize_text_field($_POST['payment_type']);

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

        if ($payment_type === 'recurring') {
            wpmm_process_stripe_recurring_payment($custom_data);
        } else {
            wpmm_process_stripe_payment($custom_data);
        }
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
        $login_errors = isset($_GET['login']) ? $_GET['login'] : '';
        ob_start();
        ?>
        <div class="wpmm-login-form wpmm-form">
            <?php if ($login_errors == 'failed') : ?>
                <div class="wpmm-error">
                    <p>Invalid username or password. Please try again.</p>
                </div>
            <?php endif; ?>
            <form method="post" action="<?php echo esc_url(wp_login_url()); ?>">
                <?php wp_nonce_field('wpmm_login_action', 'wpmm_login_nonce'); ?>
                <label for="username">Username:</label>
                <input type="text" id="username" name="log" required>

                <label for="password">Password:</label>
                <input type="password" id="password" name="pwd" required>

                <input type="hidden" name="redirect_to" value="<?php echo esc_url(home_url()); ?>">

                <button type="submit" id="wpmm-login-submit" class="wpmm-button">Login</button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
}
add_shortcode('wpmm_login_form', 'wpmm_login_form');
