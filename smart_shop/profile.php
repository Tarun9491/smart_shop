<?php
require_once "includes/session.php";
require_once "includes/auth.php";
require_once "includes/csrf.php";
require_once "db.php";

require_user_login();

$user_id = (int)$_SESSION['user_id'];
$success_msg = '';
$error_msg = '';

// Handle Profile Update (Name & Password)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error_msg = "Session expired or invalid security token.";
    } else {
        $newName = trim($_POST['name'] ?? '');
        $newPass = $_POST['new_password'] ?? '';

        if (empty($newName)) {
            $error_msg = "Full Name cannot be empty.";
        } else {
            if (!empty($newPass)) {
                if (strlen($newPass) < 6) {
                    $error_msg = "New password must be at least 6 characters long.";
                } else {
                    $hashed = password_hash($newPass, PASSWORD_DEFAULT);
                    $up = $conn->prepare("UPDATE users SET name = ?, password = ? WHERE id = ?");
                    $up->bind_param("ssi", $newName, $hashed, $user_id);
                    $up->execute();
                    $up->close();
                    $_SESSION['user_name'] = $newName;
                    $success_msg = "Profile and password updated successfully!";
                }
            } else {
                $up = $conn->prepare("UPDATE users SET name = ? WHERE id = ?");
                $up->bind_param("si", $newName, $user_id);
                $up->execute();
                $up->close();
                $_SESSION['user_name'] = $newName;
                $success_msg = "Profile updated successfully!";
            }
        }
    }
}

// Fetch user profile
$uStmt = $conn->prepare("SELECT id, name, email, created_at FROM users WHERE id = ? LIMIT 1");
$uStmt->bind_param("i", $user_id);
$uStmt->execute();
$user = $uStmt->get_result()->fetch_assoc();
$uStmt->close();

if (!$user) {
    header("Location: logout.php");
    exit();
}

// Fetch user orders
$oStmt = $conn->prepare("SELECT id, total, status, created_at FROM orders WHERE user_id = ? ORDER BY id DESC");
$oStmt->bind_param("i", $user_id);
$oStmt->execute();
$ordersRes = $oStmt->get_result();

$orders = [];
while ($order = $ordersRes->fetch_assoc()) {
    $order_id = (int)$order['id'];
    $itemStmt = $conn->prepare("
        SELECT oi.qty, oi.price, oi.wood_type, oi.size, p.name, p.image 
        FROM order_items oi 
        JOIN products p ON oi.product_id = p.id 
        WHERE oi.order_id = ?
    ");
    $itemStmt->bind_param("i", $order_id);
    $itemStmt->execute();
    $order['items'] = $itemStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $itemStmt->close();

    $orders[] = $order;
}
$oStmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Profile & Orders - Guru Woodworks</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #0f2027, #203a43, #2c5364);
            min-height: 100vh;
            color: #fff;
            padding: 30px 15px;
        }
        .profile-container {
            max-width: 900px;
            margin: auto;
        }
        .nav-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        .btn-nav {
            color: #fff;
            text-decoration: none;
            padding: 8px 18px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.2s;
        }
        .btn-nav:hover {
            background: rgba(255, 255, 255, 0.2);
            color: #ffd700;
        }
        .profile-card {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 18px;
            padding: 30px;
            margin-bottom: 25px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4);
        }
        .avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 3px solid rgba(255, 255, 255, 0.3);
            margin-right: 20px;
        }
        .order-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 18px;
        }
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-pending { background: rgba(234, 179, 8, 0.2); color: #facc15; border: 1px solid rgba(250, 204, 21, 0.4); }
        .status-processing { background: rgba(59, 130, 246, 0.2); color: #60a5fa; border: 1px solid rgba(96, 165, 250, 0.4); }
        .status-completed { background: rgba(16, 185, 129, 0.2); color: #34d399; border: 1px solid rgba(52, 211, 153, 0.4); }
        .status-cancelled { background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid rgba(248, 113, 113, 0.4); }
        .item-thumb-sm {
            width: 48px;
            height: 48px;
            object-fit: cover;
            border-radius: 6px;
            margin-right: 12px;
        }
        input.form-control {
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #fff;
            padding: 10px 14px;
            border-radius: 8px;
        }
        input.form-control:focus {
            background: rgba(15, 23, 42, 0.9);
            border-color: #38bdf8;
            color: #fff;
            box-shadow: none;
        }
        .btn-save {
            background: linear-gradient(45deg, #10b981, #059669);
            border: none;
            padding: 10px 24px;
            border-radius: 25px;
            color: #fff;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-save:hover { opacity: 0.9; }
    </style>
</head>
<body>

<div class="profile-container">
    <div class="nav-header">
        <a href="index.php" class="btn-nav">← Back to Shop</a>
        <div class="d-flex gap-2">
            <a href="cart.php" class="btn-nav">🛒 Cart</a>
            <a href="logout.php" class="btn btn-outline-danger btn-sm" style="border-radius:20px; padding:8px 18px;">Logout</a>
        </div>
    </div>

    <?php if (!empty($success_msg)): ?>
        <div class="alert alert-success alert-dismissible fade show" style="background:rgba(16,185,129,0.25); border:1px solid #10b981; color:#34d399;" role="alert">
            <?php echo htmlspecialchars($success_msg, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($error_msg)): ?>
        <div class="alert alert-danger" style="background:rgba(239,68,68,0.25); border:1px solid #ef4444; color:#fca5a5;" role="alert">
            <?php echo htmlspecialchars($error_msg, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <!-- USER INFO & EDIT FORM -->
    <div class="profile-card">
        <div class="d-flex align-items-center mb-4">
            <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" class="avatar" alt="Avatar">
            <div>
                <h3 class="m-0"><?php echo htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                <p class="text-white-50 m-0 mt-1">📧 <?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?></p>
                <small class="text-muted">
                    Customer Account · Registered <?php echo $user['created_at'] ? date('M d, Y', strtotime($user['created_at'])) : 'Active'; ?>
                </small>
            </div>
        </div>

        <!-- COLLAPSIBLE EDIT PROFILE FORM -->
        <div class="border-top border-secondary pt-3">
            <h5 class="mb-3">⚙️ Account Settings</h5>
            <form method="POST" action="profile.php">
                <?php echo csrf_field(); ?>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small text-white-50">Full Name</label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small text-white-50">New Password (leave blank to keep current)</label>
                        <input type="password" name="new_password" class="form-control" placeholder="••••••••" minlength="6">
                    </div>
                    <div class="col-12 text-end">
                        <button type="submit" name="update_profile" class="btn-save">Save Profile Changes</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- ORDER HISTORY -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="m-0">📦 Your Past Orders</h4>
        <span class="text-white-50 small"><?php echo count($orders); ?> order(s) found</span>
    </div>

    <?php if (!empty($orders)): ?>
        <?php foreach ($orders as $order): ?>
            <div class="order-card">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom border-secondary">
                    <div>
                        <span class="fw-bold fs-5">Order #<?php echo (int)$order['id']; ?></span>
                        <span class="text-white-50 ms-2 small"><?php echo date('M d, Y · h:i A', strtotime($order['created_at'])); ?></span>
                    </div>
                    <div>
                        <?php 
                        $status = strtolower($order['status'] ?? 'pending');
                        $statusClass = 'status-' . $status;
                        ?>
                        <span class="status-badge <?php echo $statusClass; ?>">
                            <?php echo htmlspecialchars(ucfirst($status), ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </div>
                </div>

                <!-- Items list -->
                <div class="mb-3">
                    <?php if (!empty($order['items'])): ?>
                        <?php foreach ($order['items'] as $item): ?>
                            <div class="d-flex align-items-center justify-content-between py-2 border-bottom border-dark">
                                <div class="d-flex align-items-center">
                                    <img src="<?php echo htmlspecialchars($item['image'], ENT_QUOTES, 'UTF-8'); ?>" class="item-thumb-sm" alt="">
                                    <div>
                                        <strong><?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <?php if (!empty($item['wood_type']) || !empty($item['size'])): ?>
                                            <div style="font-size:11px; margin-top:2px;">
                                                <?php if (!empty($item['wood_type'])): ?>
                                                    <span class="badge" style="background:rgba(56,189,248,0.2); color:#38bdf8; font-size:10px; padding:2px 6px;">🪵 <?php echo htmlspecialchars($item['wood_type'], ENT_QUOTES, 'UTF-8'); ?></span>
                                                <?php endif; ?>
                                                <?php if (!empty($item['size'])): ?>
                                                    <span class="badge" style="background:rgba(250,204,21,0.2); color:#facc15; font-size:10px; padding:2px 6px;">📏 <?php echo htmlspecialchars($item['size'], ENT_QUOTES, 'UTF-8'); ?></span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="text-white-50 small">Quantity: <?php echo (int)$item['qty']; ?></div>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div>₹ <?php echo number_format((float)$item['price'] * (int)$item['qty'], 2); ?></div>
                                    <small class="text-white-50">(₹<?php echo number_format((float)$item['price'], 2); ?> each)</small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-white-50 small py-2">Handcrafted Woodwork Item</div>
                    <?php endif; ?>
                </div>

                <div class="d-flex justify-content-between align-items-center pt-2">
                    <span class="text-white-50">Status: <strong class="text-white text-capitalize"><?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?></strong></span>
                    <div>
                        <span class="text-white-50 me-2">Grand Total:</span>
                        <strong class="fs-4 text-warning">₹ <?php echo number_format((float)$order['total'], 2); ?></strong>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="profile-card text-center py-5">
            <p class="text-white-50 fs-5 mb-3">You haven't placed any orders yet.</p>
            <a href="index.php" class="btn btn-primary" style="border-radius:25px; padding:10px 26px;">Browse Collection & Shop Now</a>
        </div>
    <?php endif; ?>
</div>

</body>
</html>