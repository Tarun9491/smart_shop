<?php
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../includes/audit.php";
require_once __DIR__ . "/../db.php";

if (!empty($_SESSION['superadmin_id'])) {
    log_audit_action($conn, (int)$_SESSION['superadmin_id'], 'superadmin_logout', "Superadmin logged out");
}

unset($_SESSION['superadmin_id']);
unset($_SESSION['superadmin_username']);

header("Location: login.php");
exit();
