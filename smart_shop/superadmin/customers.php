<?php
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../db.php";

require_superadmin_login();

// Query all customers with aggregated order statistics
$sql = "
    SELECT u.id, u.name, u.email, u.created_at,
           COUNT(o.id) as total_orders,
           COALESCE(SUM(o.total), 0) as total_spent,
           MAX(o.created_at) as last_order_date
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id AND o.status != 'cancelled'
    GROUP BY u.id
    ORDER BY u.id DESC
";

$customers = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Directory - Super Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        .super-nav { background: #1e1b4b; border-bottom: 1px solid rgba(168, 85, 247, 0.3); }
        .super-nav h3 span { color: #c084fc; }
        .super-links a:hover, .super-links a.active { background: rgba(168, 85, 247, 0.2); color: #c084fc; }
    </style>
</head>
<body>

<header class="admin-nav super-nav">
    <h3>⚡ Super Admin: <span>Executive Console</span></h3>
    <div class="admin-links super-links">
        <a href="dashboard.php">Overview & Sales</a>
        <a href="admins.php">Store Admins</a>
        <a href="customers.php" class="active">Customers</a>
        <a href="audit_logs.php">Audit Logs</a>
        <a href="logout.php" style="color:#ef4444;">Logout</a>
    </div>
</header>

<div class="admin-container">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px;">
        <h2 style="margin:0;">👤 Registered Customer Directory</h2>
        <span style="color:#94a3b8; font-size:14px;">Total Customers: <?php echo count($customers); ?></span>
    </div>

    <div class="panel-card">
        <div style="overflow-x:auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Customer ID</th>
                        <th>Name</th>
                        <th>Email Address</th>
                        <th>Orders Placed</th>
                        <th>Total Spend (₹)</th>
                        <th>Joined Date</th>
                        <th>Last Order</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($customers)): ?>
                        <?php foreach ($customers as $c): ?>
                            <tr>
                                <td>#<?php echo (int)$c['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($c['name'], ENT_QUOTES, 'UTF-8'); ?></strong></td>
                                <td><?php echo htmlspecialchars($c['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <span style="background:rgba(56,189,248,0.2); color:#38bdf8; padding:3px 8px; border-radius:12px; font-weight:600; font-size:12px;">
                                        <?php echo (int)$c['total_orders']; ?> orders
                                    </span>
                                </td>
                                <td><strong>₹ <?php echo number_format((float)$c['total_spent'], 2); ?></strong></td>
                                <td style="font-size:13px; color:#94a3b8;">
                                    <?php echo date('M d, Y', strtotime($c['created_at'])); ?>
                                </td>
                                <td style="font-size:13px; color:#94a3b8;">
                                    <?php echo $c['last_order_date'] ? date('M d, Y', strtotime($c['last_order_date'])) : '<span style="color:#64748b;">Never</span>'; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align:center; padding:30px; color:#94a3b8;">No registered customers found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
