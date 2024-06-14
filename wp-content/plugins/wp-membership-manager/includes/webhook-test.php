<?php
// Dynamically include the wp-load.php file
$wp_load_path = dirname(__FILE__, 5) . '/wp-load.php';

if (file_exists($wp_load_path)) {
    require_once($wp_load_path);
} else {
    die("wp-load.php not found.");
}

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

    if (isset($_POST['payload'])) {
        $raw_post_data = stripslashes($_POST['payload']);
    } else {
        $raw_post_data = file_get_contents('php://input');
    }

    if ($raw_post_data === false) {
        file_put_contents($log_file_path, "Failed to get raw post data\n", FILE_APPEND);
        echo "Failed to get raw post data<br>";
        return;
    }

    $post_data = json_decode($raw_post_data, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        file_put_contents($log_file_path, "JSON decode error: " . json_last_error_msg() . "\n", FILE_APPEND);
        echo "JSON decode error: " . json_last_error_msg() . "<br>";
        return;
    }

    // Log the received webhook data
    file_put_contents($log_file_path, "Webhook Data: " . print_r($post_data, true) . "\n", FILE_APPEND);

    if (isset($post_data['event_type']) && $post_data['event_type'] === 'PAYMENT.SALE.COMPLETED') {
        $resource = $post_data['resource'];

        // Log the resource data
        file_put_contents($log_file_path, "Resource Data: " . print_r($resource, true) . "\n", FILE_APPEND);

        if (isset($resource['custom'])) {
            $custom = json_decode($resource['custom'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                file_put_contents($log_file_path, "Custom JSON decode error: " . json_last_error_msg() . "\n", FILE_APPEND);
                echo "Custom JSON decode error: " . json_last_error_msg() . "<br>";
                return;
            }

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
            if (!$plan) {
                file_put_contents($log_file_path, "Failed to get membership plan\n", FILE_APPEND);
                echo "Failed to get membership plan<br>";
                return;
            }

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    test_wpmm_handle_paypal_webhook();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Webhook Test</title>
</head>
<body>
    <h1>Webhook Test</h1>
    <form method="post" action="">
        <input type="hidden" name="payload" value='{
            "event_type": "PAYMENT.SALE.COMPLETED",
            "resource": {
                "custom": "{\"username\": \"testuser\", \"email\": \"testuser@example.com\", \"password\": \"password123\", \"membership_plan\": 1}"
            }
        }'>
        <button type="submit">Send POST Request</button>
    </form>
</body>
</html>
