<?php
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../includes/csrf.php";
require_once __DIR__ . "/../includes/audit.php";
require_once __DIR__ . "/../db.php";

// If already authenticated as superadmin, redirect to dashboard
if (!empty($_SESSION['superadmin_id'])) {
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
            $error = "Please enter your superadmin username and password.";
        } else {
            $stmt = $conn->prepare("
                SELECT id, username, email, password, role, is_active 
                FROM admins 
                WHERE username = ? AND role = 'superadmin' 
                LIMIT 1
            ");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $admin = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($admin && (int)$admin['is_active'] === 1 && password_verify($password, $admin['password'])) {
                session_regenerate_id(true);
                $_SESSION['superadmin_id'] = (int)$admin['id'];
                $_SESSION['superadmin_username'] = $admin['username'];

                log_audit_action($conn, (int)$admin['id'], 'superadmin_login', "Superadmin logged in");

                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Invalid super administrator credentials or account disabled.";
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
    <title>Super Admin Portal - Guru Woodworks</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: radial-gradient(circle at top right, #1e1b4b, #0f172a, #020617);
        }
        .login-card {
            background: #1e1b4b;
            border: 1px solid rgba(168, 85, 247, 0.3);
            border-radius: 16px;
            padding: 40px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.6);
            text-align: center;
        }
        input {
            width: 100%;
            padding: 13px;
            margin: 10px 0;
            background: #0f172a;
            border: 1px solid rgba(168, 85, 247, 0.3);
            border-radius: 8px;
            color: #fff;
            outline: none;
            font-family: inherit;
        }
        input:focus { border-color: #a855f7; }
        .btn-submit {
            width: 100%;
            padding: 13px;
            margin-top: 15px;
            background: linear-gradient(135deg, #a855f7, #6366f1);
            color: #fff;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        .btn-submit:hover { opacity: 0.9; }
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
    <div style="font-size:36px; margin-bottom:10px;">⚡</div>
    <h2 style="margin-top:0; color:#c084fc;">Super Admin Portal</h2>
    <p style="color:#94a3b8; font-size:14px; margin-bottom:25px;">Platform governance, reports & audit trail</p>

    <?php if (!empty($error)): ?>
        <div class="alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <?php echo csrf_field(); ?>
        <input type="text" name="username" placeholder="Superadmin Username" required autofocus>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" name="login" class="btn-submit">Authenticate as Superadmin</button>
    </form>

    <div style="margin-top:25px; font-size:13px; color:#64748b;">
        Store Admin? <a href="../admin/login.php" style="color:#a855f7; text-decoration:none;">Switch to Store Admin Login</a>
    </div>
</div>

</body>
</html>
