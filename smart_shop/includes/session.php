<?php
// includes/session.php - Secure session management

if (session_status() === PHP_SESSION_NONE) {

    // Detect HTTPS behind Render proxy
    $isSecure = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (
            !empty($_SERVER['HTTP_X_FORWARDED_PROTO'])
            && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https'
        )
    );

    // Configure secure session cookies before session_start()
    session_set_cookie_params([
        'lifetime' => 604800,
        'path' => '/',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);

    session_start();
}
