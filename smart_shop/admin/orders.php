<?php
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/csrf.php";
require_once __DIR__ . "/../includes/audit.php";
require_once __DIR__ . "/../db.php";

require_admin_login();

// Handle status update
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = "Error: Invalid security token.";
    } else {
        $order_id = (int)($_POST['order_id'] ?? 0);
        $new_status = $_POST['status'] ?? '';
        $allowed = ['pending', 'processing', 'completed', 'cancelled'];

        if ($order_id > 0 && in_array($new_status, $allowed)) {
            $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $new_status, $order_id);
            $stmt->execute();
            $stmt->close();

            log_audit_action($conn, (int)$_SESSION['admin_id'], 'update_order_status', "Changed order #{$order_id} status to '{$new_status}'");
            $message = "Order #{$order_id} status updated to " . ucfirst($new_status);
        }
    }
}

// Fetch orders with customer details
$sql = "
    SELECT o.id, o.user_id, o.total, o.status, o.created_at,
           u.name as customer_name, u.email as customer_email
    FROM orders o
    JOIN users u ON o.user_id = u.id
    ORDER BY o.id DESC
";
$orders = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);

// Fetch items for each order
foreach ($orders as &$ord) {
    $oid = (int)$ord['id'];
    $itemStmt = $conn->prepare("
        SELECT oi.qty, oi.price, oi.wood_type, oi.size, p.name 
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id = ?
    ");
    $itemStmt->bind_param("i", $oid);
    $itemStmt->execute();
    $ord['items'] = $itemStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $itemStmt->close();
}
unset($ord);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Orders - Store Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        .status-select {
            background: #0f172a;
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 6px 10px;
            border-radius: 6px;
            font-family: inherit;
            font-size: 13px;
        }
        .btn-update {
            background: #38bdf8;
            color: #0f172a;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            margin-left: 5px;
        }
    </style>
</head>
<body>

<header class="admin-nav">
    <h3>🏬 Store Admin: <span>Guru Woodworks</span></h3>
    <div class="admin-links">
        <a href="dashboard.php">Products</a>
        <a href="orders.php" class="active">Orders</a>
        <a href="add_product.php" class="btn-primary-admin">+ Add Product</a>
        <a href="logout.php" style="color:#ef4444;">Logout</a>
    </div>
</header>

<div class="admin-container">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px;">
        <h2 style="margin:0;">📦 Customer Orders Management</h2>
        <span style="color:#94a3b8; font-size:14px;">Total Orders: <?php echo count($orders); ?></span>
    </div>

    <?php if (!empty($message)): ?>
        <div style="background:rgba(16,185,129,0.2); border:1px solid #10b981; color:#34d399; padding:12px 18px; border-radius:10px; margin-bottom:20px;">
            <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="panel-card">
        <div style="overflow-x:auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Items Ordered</th>
                        <th>Total (₹)</th>
                        <th>Date</th>
                        <th>Current Status</th>
                        <th>Update Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($orders)): ?>
                        <?php foreach ($orders as $o): ?>
                            <tr>
                                <td><strong>#<?php echo (int)$o['id']; ?></strong></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($o['customer_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <div style="color:#64748b; font-size:12px;"><?php echo htmlspecialchars($o['customer_email'], ENT_QUOTES, 'UTF-8'); ?></div>
                                </td>
                                <td>
                                    <?php if (!empty($o['items'])): ?>
                                        <ul style="margin:0; padding-left:18px; font-size:13px; color:#cbd5e1;">
                                            <?php foreach ($o['items'] as $it): ?>
                                                <li>
                                                    <strong><?php echo htmlspecialchars($it['name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                                    <?php 
                                                    $spec = array_filter([$it['wood_type'], $it['size']]);
                                                    if (!empty($spec)):
                                                    ?>
                                                        <span style="color:#38bdf8; font-size:11px;">(<?php echo htmlspecialchars(implode(', ', $spec), ENT_QUOTES, 'UTF-8'); ?>)</span>
                                                    <?php endif; ?>
                                                    (×<?php echo (int)$it['qty']; ?>)
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <span style="color:#64748b; font-size:12px;">Standard Package</span>
                                    <?php endif; ?>
                                </td>
                                <td><strong>₹ <?php echo number_format((float)$o['total'], 2); ?></strong></td>
                                <td style="font-size:13px; color:#94a3b8;">
                                    <?php echo date('M d, Y h:i A', strtotime($o['created_at'])); ?>
                                </td>
                                <td>
                                    <?php 
                                    $s = strtolower($o['status']);
                                    $col = '#facc15';
                                    if ($s === 'completed') $col = '#34d399';
                                    if ($s === 'cancelled') $col = '#f87171';
                                    if ($s === 'processing') $col = '#60a5fa';
                                    ?>
                                    <span style="color:<?php echo $col; ?>; font-weight:600; text-transform:uppercase; font-size:12px;">
                                        ● <?php echo htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </td>
                                <td>
                                    <form method="POST" action="orders.php" style="display:flex; align-items:center;">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="order_id" value="<?php echo (int)$o['id']; ?>">
                                        <select name="status" class="status-select">
                                            <option value="pending" <?php echo ($s === 'pending') ? 'selected' : ''; ?>>Pending</option>
                                            <option value="processing" <?php echo ($s === 'processing') ? 'selected' : ''; ?>>Processing</option>
                                            <option value="completed" <?php echo ($s === 'completed') ? 'selected' : ''; ?>>Completed</option>
                                            <option value="cancelled" <?php echo ($s === 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                        </select>
                                        <button type="submit" name="update_status" class="btn-update">Save</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align:center; padding:30px; color:#94a3b8;">
                                No customer orders have been received yet.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
