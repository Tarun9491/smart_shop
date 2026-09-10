<?php
// includes/auth.php - Authentication and access control guards

require_once __DIR__ . '/session.php';

function require_user_login() {
    if (empty($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
}

function require_admin_login() {
    if (empty($_SESSION['admin_id'])) {
        header("Location: login.php");
        exit();
    }
}

function require_superadmin_login() {
    if (empty($_SESSION['superadmin_id'])) {
        header("Location: login.php");
        exit();
    }
}
