<?php
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../includes/audit.php";
require_once __DIR__ . "/../db.php";

if (!empty($_SESSION['admin_id'])) {
    log_audit_action($conn, (int)$_SESSION['admin_id'], 'admin_logout', "Store admin logged out");
}

unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);
unset($_SESSION['admin_role']);

header("Location: login.php");
exit();