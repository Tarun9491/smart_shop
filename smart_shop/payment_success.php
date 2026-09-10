<?php
require_once "includes/session.php";
require_once "includes/auth.php";
require_once "db.php";

require_user_login();

$order_id = $_SESSION['last_order_id'] ?? null;
unset($_SESSION['last_order_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Placed Successfully - Guru Woodworks</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #0f2027, #203a43, #2c5364);
            min-height: 100vh;
            color: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .success-box {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 20px;
            padding: 50px 40px;
            text-align: center;
            max-width: 500px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.5);
        }
        .check-icon {
            font-size: 60px;
            margin-bottom: 20px;
            display: inline-block;
        }
        .btn-action {
            padding: 12px 28px;
            border-radius: 30px;
            font-weight: 600;
            margin: 8px;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.2s;
        }
        .btn-action:hover { transform: scale(1.05); }
    </style>
</head>
<body>
<div class="success-box">
    <div class="check-icon">🎉</div>
    <h2 class="text-success mb-3">Order Confirmed!</h2>
    <p class="text-white-50 mb-4">
        Thank you for choosing Guru Woodworks. Your order has been placed and our master craftsmen are preparing your pieces.
    </p>

    <?php if ($order_id): ?>
        <div class="alert alert-dark bg-transparent border-secondary text-white mb-4">
            Order Reference: <strong>#<?php echo (int)$order_id; ?></strong>
        </div>
    <?php endif; ?>

    <div>
        <a href="profile.php" class="btn btn-outline-light btn-action">View Order History</a>
        <a href="index.php" class="btn btn-success btn-action">Continue Shopping</a>
    </div>
</div>
</body>
</html>