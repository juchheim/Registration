<?php
// This file will manually trigger the webhook handler for testing

// Include WordPress environment
require_once('../../../wp-load.php');

function test_wpmm_handle_paypal_webhook() {
    // Output debug message directly to the browser
    echo "Webhook Handler Triggered<br>";

    // Log to a custom file to ensure the handler is being called
    $log_file_path = __DIR__ . '/webhook_log.txt';
    $log_file = fopen($log_file_path, 'a');
    if ($log_file) {
        fwrite($log_file, "Webhook Handler Triggered at " . date('Y-m-d H:i:s') . "\n");
        fclose($log_file);
    } else {
        echo "Failed to open log file at $log_file_path<br>";
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        file_put_contents($log_file_path, "Request Method is not POST\n", FILE_APPEND);
        echo "Request Method is not POST<br>";
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
                echo "Failed to create user: " . $user_id->get_error_message() . "<br>";
                return;
            }

            // Assign the selected membership plan role to the new user
            $plan = get_post($membership_plan);
            $role = strtolower(str_replace(' ', '_', $plan->post_title)) . '_member';
            $user = new WP_User($user_id);
            $user->set_role($role);

            file_put_contents($log_file_path, "User created with ID: $user_id and assigned role: $role\n", FILE_APPEND);
            echo "User created with ID: $user_id and assigned role: $role<br>";
        } else {
            file_put_contents($log_file_path, "Custom data missing in resource\n", FILE_APPEND);
            echo "Custom data missing in resource<br>";
        }
    } else {
        file_put_contents($log_file_path, "Webhook verification failed\n", FILE_APPEND);
        echo "Webhook verification failed<br>";
    }
}

// Manually trigger the webhook handler
test_wpmm_handle_paypal_webhook();
