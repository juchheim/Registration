<?php

// Process PayPal Payment
function wpmm_process_paypal_payment($username, $email, $password, $membership_plan) {
    // Sandbox or live URL
    $paypal_url = "https://www.sandbox.paypal.com/cgi-bin/webscr";

    $return_url = home_url('/thank-you');
    $cancel_url = home_url('/cancel');
    $notify_url = home_url('/ipn'); // Instant Payment Notification

    $plan = get_post($membership_plan);
    $amount = 10.00; // Assume a fixed price for now

    $query_args = array(
        'cmd' => '_xclick',
        'business' => 'sb-e8ba131205897@business.example.com',
        'item_name' => $plan->post_title,
        'amount' => $amount,
        'currency_code' => 'USD',
        'return' => $return_url,
        'cancel_return' => $cancel_url,
        'notify_url' => $notify_url,
        'custom' => json_encode(array('username' => $username, 'email' => $email, 'password' => $password, 'membership_plan' => $membership_plan)) // Custom field to pass user details
    );

    $paypal_url = add_query_arg($query_args, $paypal_url);

    // Redirect to PayPal
    wp_redirect($paypal_url);
    exit;
}

// PayPal IPN handler
function wpmm_handle_paypal_ipn() {
    // Log to a custom file to ensure the handler is being called
    $log_file = fopen(__DIR__ . '/ipn_log.txt', 'a');
    fwrite($log_file, "IPN Handler Triggered at " . date('Y-m-d H:i:s') . "\n");
    fclose($log_file);

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return;
    }

    $raw_post_data = file_get_contents('php://input');
    $raw_post_array = explode('&', $raw_post_data);
    $post_data = array();
    foreach ($raw_post_array as $keyval) {
        $keyval = explode('=', $keyval);
        if (count($keyval) == 2) {
            $post_data[$keyval[0]] = urldecode($keyval[1]);
        }
    }

    // Log the received IPN data
    error_log('PayPal IPN received: ' . print_r($post_data, true));
    file_put_contents(__DIR__ . '/ipn_log.txt', "IPN Data: " . print_r($post_data, true) . "\n", FILE_APPEND);

    // Validate IPN with PayPal
    $paypal_url = 'https://ipnpb.sandbox.paypal.com/cgi-bin/webscr';
    $response = wp_remote_post($paypal_url, array(
        'method' => 'POST',
        'body' => array_merge(array('cmd' => '_notify-validate'), $post_data),
        'timeout' => 45,
        'httpversion' => '1.1',
        'blocking' => true,
        'headers' => array(),
    ));

    if (is_wp_error($response)) {
        error_log('PayPal IPN validation request failed: ' . $response->get_error_message());
        file_put_contents(__DIR__ . '/ipn_log.txt', "IPN validation request failed: " . $response->get_error_message() . "\n", FILE_APPEND);
        return;
    }

    if (wp_remote_retrieve_body($response) === 'VERIFIED') {
        // Payment verified
        error_log('PayPal IPN verified.');
        file_put_contents(__DIR__ . '/ipn_log.txt', "IPN Verified\n", FILE_APPEND);

        $custom = json_decode($post_data['custom'], true);
        $username = sanitize_text_field($custom['username']);
        $email = sanitize_email($custom['email']);
        $password = sanitize_text_field($custom['password']);
        $membership_plan = intval($custom['membership_plan']);

        error_log("Creating user with username: $username, email: $email, membership_plan: $membership_plan");
        file_put_contents(__DIR__ . '/ipn_log.txt', "Creating user with username: $username, email: $email, membership_plan: $membership_plan\n", FILE_APPEND);

        // Create a new user
        $user_id = wp_create_user($username, $password, $email);

        if (is_wp_error($user_id)) {
            error_log('Failed to create user: ' . $user_id->get_error_message());
            file_put_contents(__DIR__ . '/ipn_log.txt', "Failed to create user: " . $user_id->get_error_message() . "\n", FILE_APPEND);
            return;
        }

        // Assign the selected membership plan role to the new user
        $plan = get_post($membership_plan);
        $role = strtolower(str_replace(' ', '_', $plan->post_title)) . '_member';
        $user = new WP_User($user_id);
        $user->set_role($role);

        error_log("User created with ID: $user_id and assigned role: $role");
        file_put_contents(__DIR__ . '/ipn_log.txt', "User created with ID: $user_id and assigned role: $role\n", FILE_APPEND);
    } else {
        error_log('PayPal IPN verification failed.');
        file_put_contents(__DIR__ . '/ipn_log.txt', "IPN Verification Failed\n", FILE_APPEND);
    }
}
add_action('wp_loaded', 'wpmm_handle_paypal_ipn');



// Webhook handler for PayPal
function wpmm_handle_paypal_webhook() {
    // Output debug message directly to the browser
    echo "Webhook Handler Triggered";

    // Log to a custom file to ensure the handler is being called
    $log_file_path = __DIR__ . '/webhook_log.txt';
    $log_file = fopen($log_file_path, 'a');
    if ($log_file) {
        fwrite($log_file, "Webhook Handler Triggered at " . date('Y-m-d H:i:s') . "\n");
        fclose($log_file);
    } else {
        echo "Failed to open log file at $log_file_path";
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        file_put_contents($log_file_path, "Request Method is not POST\n", FILE_APPEND);
        return;
    }

    $raw_post_data = file_get_contents('php://input');
    $post_data = json_decode($raw_post_data, true);

    // Log the received webhook data
    file_put_contents($log_file_path, "Webhook Data: " . print_r($post_data, true) . "\n", FILE_APPEND);

    if (isset($post_data['event_type']) && $post_data['event_type'] === 'PAYMENT.SALE.COMPLETED') {
        $resource = $post_data['resource'];

        // Log the resource data
        file_put_contents($log_file_path, "Resource Data: " . print_r($resource, true) . "\n", FILE_APPEND);

        if (isset($resource['custom'])) {
            $custom = json_decode($resource['custom'], true);
            $username = sanitize_text_field($custom['username']);
            $email = sanitize_email($custom['email']);
            $password = sanitize_text_field($custom['password']);
            $membership_plan = intval($custom['membership_plan']);

            file_put_contents($log_file_path, "Creating user with username: $username, email: $email, membership_plan: $membership_plan\n", FILE_APPEND);

            // Create a new user
            $user_id = wp_create_user($username, $password, $email);

            if (is_wp_error($user_id)) {
                file_put_contents($log_file_path, "Failed to create user: " . $user_id->get_error_message() . "\n", FILE_APPEND);
                return;
            }

            // Assign the selected membership plan role to the new user
            $plan = get_post($membership_plan);
            $role = strtolower(str_replace(' ', '_', $plan->post_title)) . '_member';
            $user = new WP_User($user_id);
            $user->set_role($role);

            file_put_contents($log_file_path, "User created with ID: $user_id and assigned role: $role\n", FILE_APPEND);
        } else {
            file_put_contents($log_file_path, "Custom data missing in resource\n", FILE_APPEND);
        }
    } else {
        file_put_contents($log_file_path, "Webhook verification failed\n", FILE_APPEND);
    }
}
add_action('admin_post_nopriv_handle_paypal_webhook', 'wpmm_handle_paypal_webhook');
add_action('admin_post_handle_paypal_webhook', 'wpmm_handle_paypal_webhook');
