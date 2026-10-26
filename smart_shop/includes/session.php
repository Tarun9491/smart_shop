```php
<?php
// includes/session.php
// Secure session management

if (session_status() === PHP_SESSION_NONE) {
    $isSecure = (
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (
            !empty($_SERVER['HTTP_X_FORWARDED_PROTO'])
            && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https'
        )
    );

    session_set_cookie_params([
        'lifetime' => 7 * 24 * 60 * 60,
        'path'     => '/',
        'secure'   => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}
```
