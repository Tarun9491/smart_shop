<?php
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../includes/csrf.php";
require_once __DIR__ . "/../includes/audit.php";
require_once __DIR__ . "/../db.php";

// If already authenticated as store admin, redirect to dashboard
if (!empty($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Session expired or invalid security token. Please try again.";
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = "Please provide both username and password.";
        } else {
            $stmt = $conn->prepare("
                SELECT id, username, email, password, role, is_active 
                FROM admins 
                WHERE username = ? AND (role = 'admin' OR role = 'superadmin')
                LIMIT 1
            ");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $admin = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($admin && (int)$admin['is_active'] === 1 && password_verify($password, $admin['password'])) {
                session_regenerate_id(true);
                $_SESSION['admin_id'] = (int)$admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_role'] = $admin['role'];

                log_audit_action($conn, (int)$admin['id'], 'admin_login', "Store admin logged in successfully");

                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Invalid administrator credentials or account disabled.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store Admin Login - Guru Woodworks</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: linear-gradient(135deg, #0f172a, #1e293b, #334155);
        }
        .login-card {
            background: #1e293b;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 40px;
            width: 100%;
            max-width: 380px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
            text-align: center;
        }
        input {
            width: 100%;
            padding: 13px;
            margin: 10px 0;
            background: #0f172a;
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            color: #fff;
            outline: none;
            font-family: inherit;
        }
        input:focus { border-color: #38bdf8; }
        .btn-submit {
            width: 100%;
            padding: 13px;
            margin-top: 15px;
            background: #38bdf8;
            color: #0f172a;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn-submit:hover { background: #7dd3fc; }
        .alert-error {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.4);
            color: #fca5a5;
            padding: 10px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

<div class="login-card">
    <h2 style="margin-top:0;">🏬 Store Admin Portal</h2>
    <p style="color:#94a3b8; font-size:14px; margin-bottom:25px;">Sign in to manage catalog & orders</p>

    <?php if (!empty($error)): ?>
        <div class="alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <?php echo csrf_field(); ?>
        <input type="text" name="username" placeholder="Admin Username" required autofocus>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" name="login" class="btn-submit">Sign In to Store Admin</button>
    </form>

    <div style="margin-top:25px; font-size:13px; color:#64748b;">
        Looking for Super Admin? <a href="../superadmin/login.php" style="color:#38bdf8; text-decoration:none;">Go here</a>
    </div>
</div>

</body>
</html>