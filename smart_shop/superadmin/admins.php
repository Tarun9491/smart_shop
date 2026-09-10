<?php
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/csrf.php";
require_once __DIR__ . "/../includes/audit.php";
require_once __DIR__ . "/../db.php";

require_superadmin_login();

$msg = '';
$error = '';

// 1. Handle create new store admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_admin'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Session expired or invalid security token.";
    } else {
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($email) || strlen($password) < 6) {
            $error = "Username, email, and password (min 6 characters) are required.";
        } else {
            // Check username duplicate
            $chk = $conn->prepare("SELECT id FROM admins WHERE username = ? OR email = ? LIMIT 1");
            $chk->bind_param("ss", $username, $email);
            $chk->execute();
            if ($chk->get_result()->num_rows > 0) {
                $error = "An administrator with that username or email already exists.";
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $ins = $conn->prepare("INSERT INTO admins (username, email, password, role, is_active) VALUES (?, ?, ?, 'admin', 1)");
                $ins->bind_param("sss", $username, $email, $hashed);
                if ($ins->execute()) {
                    $newId = $conn->insert_id;
                    log_audit_action($conn, (int)$_SESSION['superadmin_id'], 'create_store_admin', "Created store admin #{$newId} ({$username})");
                    $msg = "Store admin '{$username}' created successfully.";
                } else {
                    $error = "Failed to create administrator account.";
                }
                $ins->close();
            }
            $chk->close();
        }
    }
}

// 2. Handle toggle active/disable
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Invalid security token.";
    } else {
        $targetId = (int)($_POST['target_id'] ?? 0);
        if ($targetId === (int)$_SESSION['superadmin_id']) {
            $error = "Cannot disable your own superadmin account.";
        } else {
            $getAdmin = $conn->prepare("SELECT username, is_active FROM admins WHERE id = ? LIMIT 1");
            $getAdmin->bind_param("i", $targetId);
            $getAdmin->execute();
            $target = $getAdmin->get_result()->fetch_assoc();
            $getAdmin->close();

            if ($target) {
                $newStatus = ((int)$target['is_active'] === 1) ? 0 : 1;
                $up = $conn->prepare("UPDATE admins SET is_active = ? WHERE id = ?");
                $up->bind_param("ii", $newStatus, $targetId);
                $up->execute();
                $up->close();

                $actionStr = $newStatus === 1 ? 'activated' : 'disabled';
                log_audit_action($conn, (int)$_SESSION['superadmin_id'], 'toggle_admin_status', "{$actionStr} administrator #{$targetId} ({$target['username']})");
                $msg = "Administrator '{$target['username']}' is now {$actionStr}.";
            }
        }
    }
}

// 3. Handle password reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Invalid security token.";
    } else {
        $targetId = (int)($_POST['target_id'] ?? 0);
        $newPass = $_POST['new_password'] ?? '';

        if (strlen($newPass) < 6) {
            $error = "New password must be at least 6 characters long.";
        } else {
            $hashed = password_hash($newPass, PASSWORD_DEFAULT);
            $up = $conn->prepare("UPDATE admins SET password = ? WHERE id = ?");
            $up->bind_param("si", $hashed, $targetId);
            $up->execute();
            $up->close();

            log_audit_action($conn, (int)$_SESSION['superadmin_id'], 'reset_admin_password', "Reset password for administrator #{$targetId}");
            $msg = "Password reset successfully.";
        }
    }
}

// Fetch all admins
$admins = $conn->query("SELECT id, username, email, role, is_active, created_at FROM admins ORDER BY id ASC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Store Admins - Super Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        .super-nav { background: #1e1b4b; border-bottom: 1px solid rgba(168, 85, 247, 0.3); }
        .super-nav h3 span { color: #c084fc; }
        .super-links a:hover, .super-links a.active { background: rgba(168, 85, 247, 0.2); color: #c084fc; }
        .grid-layout {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
        }
        @media (max-width: 950px) {
            .grid-layout { grid-template-columns: 1fr; }
        }
        input {
            width: 100%;
            padding: 11px;
            margin: 8px 0;
            background: #0f172a;
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            color: #fff;
            outline: none;
            font-family: inherit;
        }
    </style>
</head>
<body>

<header class="admin-nav super-nav">
    <h3>⚡ Super Admin: <span>Executive Console</span></h3>
    <div class="admin-links super-links">
        <a href="dashboard.php">Overview & Sales</a>
        <a href="admins.php" class="active">Store Admins</a>
        <a href="customers.php">Customers</a>
        <a href="audit_logs.php">Audit Logs</a>
        <a href="logout.php" style="color:#ef4444;">Logout</a>
    </div>
</header>

<div class="admin-container">
    <?php if (!empty($msg)): ?>
        <div style="background:rgba(16,185,129,0.2); border:1px solid #10b981; color:#34d399; padding:12px 18px; border-radius:10px; margin-bottom:20px;">
            <?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div style="background:rgba(239,68,68,0.2); border:1px solid #ef4444; color:#fca5a5; padding:12px 18px; border-radius:10px; margin-bottom:20px;">
            <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="grid-layout">
        <!-- ADMINS DIRECTORY -->
        <div class="panel-card">
            <h3 style="margin-top:0; margin-bottom:20px;">👥 Administrator Accounts</h3>

            <div style="overflow-x:auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($admins as $adm): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($adm['username'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <div style="color:#64748b; font-size:12px;"><?php echo htmlspecialchars($adm['email'], ENT_QUOTES, 'UTF-8'); ?></div>
                                </td>
                                <td>
                                    <span style="font-size:12px; font-weight:600; text-transform:uppercase; color:<?php echo $adm['role'] === 'superadmin' ? '#c084fc' : '#38bdf8'; ?>">
                                        <?php echo htmlspecialchars($adm['role'], ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ((int)$adm['is_active'] === 1): ?>
                                        <span class="badge-status badge-active">Active</span>
                                    <?php else: ?>
                                        <span class="badge-status badge-disabled">Disabled</span>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size:12px; color:#94a3b8;">
                                    <?php echo date('M d, Y', strtotime($adm['created_at'])); ?>
                                </td>
                                <td>
                                    <?php if ((int)$adm['id'] !== (int)$_SESSION['superadmin_id']): ?>
                                        <div style="display:flex; gap:8px; align-items:center;">
                                            <!-- Toggle status -->
                                            <form method="POST" action="admins.php" style="margin:0;">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="target_id" value="<?php echo (int)$adm['id']; ?>">
                                                <button type="submit" name="toggle_status" style="background:none; border:none; color:<?php echo (int)$adm['is_active'] === 1 ? '#f87171' : '#34d399'; ?>; cursor:pointer; font-size:12px; text-decoration:underline;">
                                                    <?php echo (int)$adm['is_active'] === 1 ? 'Disable' : 'Enable'; ?>
                                                </button>
                                            </form>

                                            <!-- Reset password prompt -->
                                            <button onclick="promptReset(<?php echo (int)$adm['id']; ?>, '<?php echo htmlspecialchars($adm['username'], ENT_QUOTES, 'UTF-8'); ?>')" 
                                                    style="background:none; border:none; color:#38bdf8; cursor:pointer; font-size:12px; text-decoration:underline;">
                                                Reset Pass
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <span style="color:#64748b; font-size:12px;">(Current User)</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- CREATE STORE ADMIN FORM -->
        <div class="panel-card">
            <h4 style="margin-top:0; color:#38bdf8;">➕ Provision Store Admin</h4>
            <p style="font-size:13px; color:#94a3b8; margin-bottom:15px;">Create a new operator account for catalog and order fulfillment.</p>

            <form method="POST" action="admins.php">
                <?php echo csrf_field(); ?>
                <label style="font-size:13px; color:#94a3b8;">Username</label>
                <input type="text" name="username" placeholder="e.g. john_manager" required>

                <label style="font-size:13px; color:#94a3b8;">Email Address</label>
                <input type="email" name="email" placeholder="john@smartshop.com" required>

                <label style="font-size:13px; color:#94a3b8;">Temporary Password (min 6)</label>
                <input type="password" name="password" minlength="6" placeholder="••••••••" required>

                <button type="submit" name="create_admin" class="btn-primary-admin" style="width:100%; justify-content:center; margin-top:15px; padding:12px;">
                    Create Store Admin
                </button>
            </form>
        </div>
    </div>
</div>

<!-- HIDDEN PASSWORD RESET FORM -->
<form id="resetForm" method="POST" action="admins.php" style="display:none;">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="reset_password" value="1">
    <input type="hidden" name="target_id" id="reset_target_id">
    <input type="hidden" name="new_password" id="reset_new_password">
</form>

<script>
function promptReset(adminId, username) {
    const newPass = prompt(`Enter new password for ${username} (min 6 characters):`);
    if (newPass && newPass.trim().length >= 6) {
        document.getElementById('reset_target_id').value = adminId;
        document.getElementById('reset_new_password').value = newPass.trim();
        document.getElementById('resetForm').submit();
    } else if (newPass !== null) {
        alert("Password must be at least 6 characters long.");
    }
}
</script>

</body>
</html>
