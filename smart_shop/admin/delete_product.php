<?php
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/csrf.php";
require_once __DIR__ . "/../includes/audit.php";
require_once __DIR__ . "/../db.php";

require_admin_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: dashboard.php");
    exit();
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    die("Security verification failed. Please return to dashboard and try again.");
}

$id = (int)($_POST['id'] ?? 0);

if ($id > 0) {
    // Fetch product name for audit
    $pStmt = $conn->prepare("SELECT name FROM products WHERE id = ? LIMIT 1");
    $pStmt->bind_param("i", $id);
    $pStmt->execute();
    $name = $pStmt->get_result()->fetch_assoc()['name'] ?? "Unknown";
    $pStmt->close();

    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    log_audit_action($conn, (int)$_SESSION['admin_id'], 'delete_product', "Deleted product #{$id} ({$name})");
}

header("Location: dashboard.php?msg=" . urlencode("Product deleted successfully."));
exit();