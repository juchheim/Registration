<?php

// Add meta box
function wpmm_add_meta_boxes() {
    add_meta_box(
        'wpmm_restriction_meta_box',      // ID of the meta box
        'Content Restriction',            // Title of the meta box
        'wpmm_restriction_meta_box_cb',   // Callback function
        'page',                           // Post type where the meta box will appear
        'side',                           // Context (side, normal, advanced)
        'default'                         // Priority
    );
}
add_action('add_meta_boxes', 'wpmm_add_meta_boxes');

// Callback function to render the meta box
function wpmm_restriction_meta_box_cb($post) {
    // Retrieve current value
    $value = get_post_meta($post->ID, '_wpmm_restricted', true);
    wp_nonce_field('wpmm_save_meta_box_data', 'wpmm_meta_box_nonce');
    ?>
    <label for="wpmm_restricted">Restrict to:</label>
    <select name="wpmm_restricted" id="wpmm_restricted">
        <option value="">-- None --</option>
        <option value="basic_member" <?php selected($value, 'basic_member'); ?>>Basic Member</option>
        <option value="premium_member" <?php selected($value, 'premium_member'); ?>>Premium Member</option>
        <option value="vip_member" <?php selected($value, 'vip_member'); ?>>VIP Member</option>
    </select>
    <?php
}

// Save the meta box data
function wpmm_save_meta_box_data($post_id) {
    // Check if nonce is set
    if (!isset($_POST['wpmm_meta_box_nonce'])) {
        return;
    }

    // Verify the nonce
    if (!wp_verify_nonce($_POST['wpmm_meta_box_nonce'], 'wpmm_save_meta_box_data')) {
        return;
    }

    // Check autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    // Check user permissions
    if (isset($_POST['post_type']) && 'page' === $_POST['post_type']) {
        if (!current_user_can('edit_page', $post_id)) {
            return;
        }
    } else {
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    }

    // Sanitize and save the data
    if (isset($_POST['wpmm_restricted'])) {
        $restricted = sanitize_text_field($_POST['wpmm_restricted']);
        update_post_meta($post_id, '_wpmm_restricted', $restricted);
    }
}
add_action('save_post', 'wpmm_save_meta_box_data');

// Restrict content based on membership levels
function wpmm_restrict_content($content) {
    if (is_singular('page') || is_singular('post')) {
        $post_id = get_the_ID();
        $restricted = get_post_meta($post_id, '_wpmm_restricted', true);

        if ($restricted && !current_user_can($restricted)) {
            return '<p>You do not have permission to view this content. Please <a href="' . wp_login_url() . '">log in</a> or <a href="' . home_url('/register') . '">register</a>.</p>';
        }
    }
    return $content;
}
add_filter('the_content', 'wpmm_restrict_content');
