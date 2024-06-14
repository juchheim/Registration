<?php

function wpmm_add_user_roles() {
    add_role('basic_member', __('Basic Member', 'wpmm'), array(
        'read' => true,
    ));

    add_role('premium_member', __('Premium Member', 'wpmm'), array(
        'read' => true,
    ));

    add_role('vip_member', __('VIP Member', 'wpmm'), array(
        'read' => true,
    ));
}
add_action('init', 'wpmm_add_user_roles');

