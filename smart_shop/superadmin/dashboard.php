<?php
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../db.php";

require_superadmin_login();

// High level revenue & system analytics
$revRow = $conn->query("SELECT SUM(total) as rev, COUNT(*) as cnt FROM orders WHERE status != 'cancelled'")->fetch_assoc();
$totalRevenue = (float)($revRow['rev'] ?? 0);
$totalOrders = (int)($revRow['cnt'] ?? 0);

$customerCount = (int)($conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'] ?? 0);
$adminCount = (int)($conn->query("SELECT COUNT(*) as c FROM admins WHERE role = 'admin' AND is_active = 1")->fetch_assoc()['c'] ?? 0);

// Status breakdown
$statusBreakdown = $conn->query("
    SELECT status, COUNT(*) as count, SUM(total) as revenue 
    FROM orders 
    GROUP BY status
")->fetch_all(MYSQLI_ASSOC);

// Recent orders
$recentOrders = $conn->query("
    SELECT o.id, o.total, o.status, o.created_at, u.name as customer_name 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.id DESC 
    LIMIT 6
")->fetch_all(MYSQLI_ASSOC);

// Recent audit activities
$recentAudits = $conn->query("
    SELECT al.*, a.username 
    FROM audit_logs al 
    LEFT JOIN admins a ON al.admin_id = a.id 
    ORDER BY al.id DESC 
    LIMIT 6
")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Console - Guru Woodworks</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        .super-nav {
            background: #1e1b4b;
            border-bottom: 1px solid rgba(168, 85, 247, 0.3);
        }
        .super-nav h3 span { color: #c084fc; }
        .super-links a:hover, .super-links a.active {
            background: rgba(168, 85, 247, 0.2);
            color: #c084fc;
        }
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }
        @media (max-width: 900px) {
            .grid-2 { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<header class="admin-nav super-nav">
    <h3>⚡ Super Admin: <span>Executive Console</span></h3>
    <div class="admin-links super-links">
        <a href="dashboard.php" class="active">Overview & Sales</a>
        <a href="admins.php">Store Admins (<?php echo $adminCount; ?>)</a>
        <a href="customers.php">Customers (<?php echo $customerCount; ?>)</a>
        <a href="audit_logs.php">Audit Logs</a>
        <a href="logout.php" style="color:#ef4444;">Logout</a>
    </div>
</header>

<div class="admin-container">
    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-label">Total Platform Sales</div>
            <div class="stat-value" style="color:#34d399;">₹ <?php echo number_format($totalRevenue, 2); ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Completed Orders</div>
            <div class="stat-value"><?php echo $totalOrders; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Registered Customers</div>
            <div class="stat-value" style="color:#38bdf8;"><?php echo $customerCount; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Active Store Admins</div>
            <div class="stat-value" style="color:#c084fc;"><?php echo $adminCount; ?></div>
        </div>
    </div>

    <!-- SALES BREAKDOWN & RECENT ORDERS -->
    <div class="grid-2">
        <div class="panel-card">
            <h4 style="margin-top:0; color:#c084fc;">📊 Sales Breakdown by Order Status</h4>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Orders Count</th>
                        <th>Gross Value</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($statusBreakdown)): ?>
                        <?php foreach ($statusBreakdown as $sb): ?>
                            <tr>
                                <td style="text-transform:capitalize; font-weight:600;">
                                    <?php echo htmlspecialchars($sb['status'], ENT_QUOTES, 'UTF-8'); ?>
                                </td>
                                <td><?php echo (int)$sb['count']; ?></td>
                                <td>₹ <?php echo number_format((float)$sb['revenue'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="3" style="text-align:center; color:#64748b;">No transaction data yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="panel-card">
            <h4 style="margin-top:0; color:#38bdf8;">🕒 Recent Customer Orders</h4>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($recentOrders)): ?>
                        <?php foreach ($recentOrders as $ro): ?>
                            <tr>
                                <td>#<?php echo (int)$ro['id']; ?></td>
                                <td><?php echo htmlspecialchars($ro['customer_name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>₹ <?php echo number_format((float)$ro['total'], 2); ?></td>
                                <td style="text-transform:capitalize; font-size:12px; font-weight:600;">
                                    <?php echo htmlspecialchars($ro['status'], ENT_QUOTES, 'UTF-8'); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align:center; color:#64748b;">No orders yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- RECENT AUDIT LOGS PREVIEW -->
    <div class="panel-card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
            <h4 style="margin:0; color:#facc15;">🛡️ Recent Audit Trail</h4>
            <a href="audit_logs.php" style="color:#c084fc; text-decoration:none; font-size:13px;">View Full Log →</a>
        </div>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Timestamp</th>
                    <th>Operator</th>
                    <th>Action</th>
                    <th>Details</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($recentAudits)): ?>
                    <?php foreach ($recentAudits as $log): ?>
                        <tr>
                            <td style="font-size:12px; color:#94a3b8;"><?php echo date('M d, H:i', strtotime($log['created_at'])); ?></td>
                            <td><strong><?php echo htmlspecialchars($log['username'] ?? 'System', ENT_QUOTES, 'UTF-8'); ?></strong></td>
                            <td style="color:#38bdf8;"><?php echo htmlspecialchars($log['action'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td style="font-size:13px;"><?php echo htmlspecialchars($log['details'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td style="font-size:12px; color:#64748b;"><?php echo htmlspecialchars($log['ip_address'], ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align:center; color:#64748b;">No audit entries logged yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
