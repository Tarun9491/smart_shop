```php
<?php
// includes/auth.php - Authentication and access control guards

require_once __DIR__ . '/session.php';

/**
 * Redirect to the login page safely.
 */
function redirect_to_login(): void
{
    if (headers_sent()) {
        error_log(
            'Authentication redirect failed: headers already sent.'
        );
        exit('A page initialization error occurred. Please refresh the page.');
    }

    header('Location: /login.php');
    exit;
}

/**
 * Require a logged-in customer.
 */
function require_user_login(): void
{
    if (empty($_SESSION['user_id'])) {
        redirect_to_login();
    }
}

/**
 * Require a logged-in administrator.
 */
function require_admin_login(): void
{
    if (empty($_SESSION['admin_id'])) {
        redirect_to_login();
    }
}

/**
 * Require a logged-in super administrator.
 */
function require_superadmin_login(): void
{
    if (empty($_SESSION['superadmin_id'])) {
        redirect_to_login();
    }
}
```
