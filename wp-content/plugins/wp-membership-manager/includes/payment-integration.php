<?php

// Include Stripe PHP library files manually
require_once __DIR__ . '/../stripe/init.php';
require_once __DIR__ . '/../stripe/lib/Stripe.php';
require_once __DIR__ . '/../stripe/lib/ApiRequestor.php';
require_once __DIR__ . '/../stripe/lib/ApiResource.php';
require_once __DIR__ . '/../stripe/lib/StripeObject.php';
require_once __DIR__ . '/../stripe/lib/Collection.php';
require_once __DIR__ . '/../stripe/lib/ApiResponse.php';
require_once __DIR__ . '/../stripe/lib/HttpClient/ClientInterface.php';
require_once __DIR__ . '/../stripe/lib/HttpClient/CurlClient.php';

// Replace 'your-stripe-secret-key' with your actual Stripe secret key
\Stripe\Stripe::setApiKey('sk_test_51PRj4aHrZfxkHCcnjYNK7r3Ev1e1sIlU4R3itbutVSG1fJKAzfEOehjvFZz7B9A8v5Hu0fF0Dh9sv5ZYmbrd9swh00VLTD1J2Q');

function wpmm_process_stripe_payment($custom_data) {
    $custom_data_json = json_encode($custom_data);

    // Create a new Stripe Checkout session
    $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [[
            'price_data' => [
                'currency' => 'usd',
                'product_data' => [
                    'name' => 'Membership Plan',
                ],
                'unit_amount' => 1000, // $10.00
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => home_url('/thank-you') . '?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => home_url('/cancel'),
        'metadata' => ['custom_data' => $custom_data_json],
    ]);

    // Return session ID for Stripe Checkout redirect
    echo json_encode(['sessionId' => $session->id]);
    wp_die();
}

// Webhook handler for Stripe
function wpmm_handle_stripe_webhook(WP_REST_Request $request) {
    $log_file_path = __DIR__ . '/webhook_log.txt';
    $log_file = fopen($log_file_path, 'a');
    if ($log_file) {
        fwrite($log_file, "Webhook Handler Triggered at " . date('Y-m-d H:i:s') . "\n");
        fclose($log_file);
    } else {
        error_log("Failed to open log file at $log_file_path");
    }

    $endpoint_secret = 'whsec_kNP7kmke4yorjL837t5vybbFzFjyxXSx';
    $payload = @file_get_contents('php://input');
    $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
    $event = null;

    try {
        $event = \Stripe\Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
    } catch (\UnexpectedValueException $e) {
        file_put_contents($log_file_path, "Invalid payload\n", FILE_APPEND);
        http_response_code(400);
        exit();
    } catch (\Stripe\Exception\SignatureVerificationException $e) {
        file_put_contents($log_file_path, "Invalid signature\n", FILE_APPEND);
        http_response_code(400);
        exit();
    }

    if ($event->type == 'checkout.session.completed') {
        $session = $event->data->object;
        $custom_data = json_decode($session->metadata->custom_data, true);

        $username = sanitize_text_field($custom_data['username']);
        $email = sanitize_email($custom_data['email']);
        $password = sanitize_text_field($custom_data['password']);
        $membership_plan = intval($custom_data['membership_plan']);

        // Create a new user
        $user_id = wp_create_user($username, $password, $email);

        if (is_wp_error($user_id)) {
            file_put_contents($log_file_path, "Failed to create user: " . $user_id->get_error_message() . "\n", FILE_APPEND);
            return;
        }

        // Assign the selected membership plan role to the new user
        $plan = get_post($membership_plan);
        if (!$plan) {
            file_put_contents($log_file_path, "Failed to get membership plan\n", FILE_APPEND);
            return;
        }

        $role = strtolower(str_replace(' ', '_', $plan->post_title)) . '_member';
        $user = new WP_User($user_id);
        $user->set_role($role);

        file_put_contents($log_file_path, "User created with ID: $user_id and assigned role: $role\n", FILE_APPEND);
        
        // Redirect to login page
        wp_redirect(home_url('/login'));
        exit;
    }

    http_response_code(200);
    return new WP_REST_Response('Webhook received', 200);
}

add_action('rest_api_init', function () {
    register_rest_route('wpmm/v1', '/stripe-webhook', array(
        'methods' => 'POST',
        'callback' => 'wpmm_handle_stripe_webhook',
        'permission_callback' => '__return_true',
    ));
});