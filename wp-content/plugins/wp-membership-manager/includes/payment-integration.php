<?php
require_once __DIR__ . '/../stripe/init.php';

// Your Stripe secret key
\Stripe\Stripe::setApiKey('sk_test_51PRj4aHrZfxkHCcnjYNK7r3Ev1e1sIlU4R3itbutVSG1fJKAzfEOehjvFZz7B9A8v5Hu0fF0Dh9sv5ZYmbrd9swh00VLTD1J2Q');

// Process Stripe Payment
function wpmm_process_stripe_payment($custom_data) {
    try {
        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => 'Membership Plan',
                    ],
                    'unit_amount' => 1000,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => home_url('/thank-you') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => home_url('/register'),
            'metadata' => $custom_data,
        ]);

        echo json_encode(['sessionId' => $session->id]);
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    wp_die();
}

// Process Stripe Recurring Payment
function wpmm_process_stripe_recurring_payment($custom_data) {
    try {
        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price' => 'price_1PSQF3HrZfxkHCcn6aPOOtCM', // Replace with your actual Stripe price ID
                'quantity' => 1,
            ]],
            'mode' => 'subscription',
            'success_url' => home_url('/thank-you') . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => home_url('/register'),
            'metadata' => $custom_data,
        ]);

        echo json_encode(['sessionId' => $session->id]);
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
    wp_die();
}

// Stripe webhook handler
function wpmm_handle_stripe_webhook() {
    $payload = @file_get_contents('php://input');
    $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
    $endpoint_secret = 'whsec_kNP7kmke4yorjL837t5vybbFzFjyxXSx';

    try {
        $event = \Stripe\Webhook::constructEvent(
            $payload, $sig_header, $endpoint_secret
        );

        if ($event->type == 'checkout.session.completed') {
            $session = $event->data->object;

            $metadata = $session->metadata;
            $username = sanitize_text_field($metadata->username);
            $email = sanitize_email($metadata->email);
            $password = sanitize_text_field($metadata->password);
            $membership_plan = intval($metadata->membership_plan);

            $user_id = wp_create_user($username, $password, $email);

            if (!is_wp_error($user_id)) {
                $plan = get_post($membership_plan);
                $role = strtolower(str_replace(' ', '_', $plan->post_title)) . '_member';
                $user = new WP_User($user_id);
                $user->set_role($role);
            }
        }

        http_response_code(200);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
}
add_action('rest_api_init', function () {
    register_rest_route('wpmm/v1', '/stripe-webhook', array(
        'methods' => 'POST',
        'callback' => 'wpmm_handle_stripe_webhook',
        'permission_callback' => '__return_true',
    ));
});
