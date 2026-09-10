<?php
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../db.php";

require_superadmin_login();

// Fetch audit logs with admin username
$sql = "
    SELECT al.id, al.action, al.details, al.ip_address, al.created_at,
           COALESCE(a.username, 'System') as admin_username,
           COALESCE(a.role, 'system') as admin_role
    FROM audit_logs al
    LEFT JOIN admins a ON al.admin_id = a.id
    ORDER BY al.id DESC
    LIMIT 200
";

$logs = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs - Super Admin</title>
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
        <a href="customers.php">Customers</a>
        <a href="audit_logs.php" class="active">Audit Logs</a>
        <a href="logout.php" style="color:#ef4444;">Logout</a>
    </div>
</header>

<div class="admin-container">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:25px;">
        <h2 style="margin:0;">🛡️ System Security & Administrative Audit Trail</h2>
        <span style="color:#94a3b8; font-size:14px;">Showing Last 200 Events</span>
    </div>

    <div class="panel-card">
        <div style="overflow-x:auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width:70px;">ID</th>
                        <th>Timestamp</th>
                        <th>Operator</th>
                        <th>Action Type</th>
                        <th>Details</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($logs)): ?>
                        <?php foreach ($logs as $l): ?>
                            <tr>
                                <td style="color:#64748b;">#<?php echo (int)$l['id']; ?></td>
                                <td style="font-size:13px; color:#94a3b8; white-space:nowrap;">
                                    <?php echo date('M d, Y · H:i:s', strtotime($l['created_at'])); ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($l['admin_username'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <span style="font-size:11px; text-transform:uppercase; color:<?php echo $l['admin_role'] === 'superadmin' ? '#c084fc' : '#38bdf8'; ?>; margin-left:4px;">
                                        [<?php echo htmlspecialchars($l['admin_role'], ENT_QUOTES, 'UTF-8'); ?>]
                                    </span>
                                </td>
                                <td>
                                    <span style="background:rgba(255,255,255,0.08); padding:4px 8px; border-radius:6px; font-family:monospace; color:#38bdf8;">
                                        <?php echo htmlspecialchars($l['action'], ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </td>
                                <td style="font-size:13px;"><?php echo htmlspecialchars($l['details'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td style="font-size:12px; font-family:monospace; color:#94a3b8;">
                                    <?php echo htmlspecialchars($l['ip_address'] ?? 'Unknown', ENT_QUOTES, 'UTF-8'); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center; padding:30px; color:#94a3b8;">No audit trail records present.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>
