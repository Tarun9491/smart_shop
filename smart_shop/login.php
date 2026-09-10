<?php
require_once "includes/session.php";
require_once "includes/csrf.php";
require_once "db.php";

// If already logged in, redirect to home
if (!empty($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Session expired or invalid token. Please try again.";
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = "Please provide both email and password.";
        } else {
            $stmt = $conn->prepare("SELECT id, name, email, password FROM users WHERE email = ? LIMIT 1");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($row = $result->fetch_assoc()) {
                if (password_verify($password, $row['password'])) {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = (int)$row['id'];
                    $_SESSION['user_name'] = $row['name'];
                    $_SESSION['user_email'] = $row['email'];
                    $stmt->close();
                    header("Location: index.php");
                    exit();
                } else {
                    $error = "Invalid email or password.";
                }
            } else {
                $error = "Invalid email or password.";
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Guru Woodworks</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea, #764ba2, #ff758c);
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .login-box {
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.4);
            width: 100%;
            max-width: 360px;
            text-align: center;
            color: white;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        h2 { margin-top: 0; margin-bottom: 20px; }
        input {
            width: 100%;
            padding: 13px;
            margin: 10px 0;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 10px;
            outline: none;
            background: rgba(255, 255, 255, 0.9);
            font-family: inherit;
            font-size: 14px;
        }
        input:focus { border-color: #36d1dc; background: #fff; }
        button {
            width: 100%;
            padding: 13px;
            margin-top: 10px;
            border: none;
            border-radius: 30px;
            background: linear-gradient(45deg, #ff416c, #ff4b2b);
            color: white;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: 0.3s;
        }
        button:hover {
            transform: scale(1.03);
            background: linear-gradient(45deg, #36d1dc, #5b86e5);
        }
        .error {
            background: rgba(255, 0, 0, 0.3);
            border: 1px solid rgba(255, 100, 100, 0.5);
            padding: 10px;
            border-radius: 8px;
            color: #fff;
            margin-bottom: 15px;
            font-size: 13px;
        }
        a {
            color: #fff;
            font-size: 14px;
            text-decoration: underline;
        }
        a:hover { color: #ffd700; }
        .links { margin-top: 20px; font-size: 13px; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>🔐 Customer Login</h2>
        <?php if (!empty($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php">
            <?php echo csrf_field(); ?>
            <input type="email" name="email" placeholder="Email Address" required autofocus>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="login">Login</button>
        </form>

        <div class="links">
            <a href="register.php">New customer? Create an Account</a><br><br>
            <a href="index.php" style="color: #cbd5e1; font-size: 13px; text-decoration: none;">← Back to Furniture Collection</a>
        </div>
    </div>
</body>
</html>