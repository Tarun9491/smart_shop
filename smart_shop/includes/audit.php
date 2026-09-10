<?php
// includes/audit.php - Action audit logging for administrative tracking

function log_audit_action($conn, $admin_id, $action, $details = '') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ips[0]);
    }

    $stmt = $conn->prepare("INSERT INTO `audit_logs` (admin_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    if ($stmt) {
        $stmt->bind_param("isss", $admin_id, $action, $details, $ip);
        $stmt->execute();
        $stmt->close();
    }
}
