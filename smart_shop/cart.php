<?php
require_once "includes/session.php";
require_once "includes/auth.php";
require_once "db.php";

$isLoggedIn = !empty($_SESSION['user_id']);
$user_id = $isLoggedIn ? (int)$_SESSION['user_id'] : 0;

$total = 0.0;
$items = [];

if ($isLoggedIn) {
    $stmt = $conn->prepare("
        SELECT c.id as cart_id, c.product_id, p.name, COALESCE(c.price, p.price) as price, p.image, p.stock, c.qty, c.wood_type, c.size
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ?
        ORDER BY c.id DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $lineTotal = (float)$row['price'] * (int)$row['qty'];
        $total += $lineTotal;
        $row['line_total'] = $lineTotal;
        $items[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart - Guru Woodworks</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #0f2027, #203a43, #2c5364);
            min-height: 100vh;
            color: #fff;
            padding: 40px 15px;
        }
        .cart-card {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 18px;
            padding: 35px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4);
        }
        .table {
            color: #fff;
            vertical-align: middle;
        }
        .table thead th {
            background: rgba(0, 0, 0, 0.4);
            color: #cbd5e1;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .table td {
            background: transparent;
            color: #f1f5f9;
            border-color: rgba(255, 255, 255, 0.1);
        }
        .qty-control {
            display: inline-flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        .qty-btn {
            background: transparent;
            border: none;
            color: #fff;
            padding: 6px 12px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s;
        }
        .qty-btn:hover { background: rgba(255, 255, 255, 0.2); }
        .qty-num {
            padding: 0 10px;
            font-weight: 600;
        }
        .btn-checkout {
            background: linear-gradient(45deg, #ff416c, #ff4b2b);
            border: none;
            padding: 12px 30px;
            border-radius: 30px;
            color: #fff;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.2s;
        }
        .btn-checkout:hover {
            color: #fff;
            transform: scale(1.05);
        }
        .btn-continue {
            color: #94a3b8;
            text-decoration: none;
            margin-right: 15px;
        }
        .btn-continue:hover { color: #fff; }
        .item-thumb {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            margin-right: 12px;
        }
    </style>
</head>
<body>
<div class="container" style="max-width: 950px;">
    <div class="cart-card">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="m-0">🛒 Shopping Cart</h2>
            <a href="index.php" class="btn-continue">← Continue Shopping</a>
        </div>

        <?php if (!empty($items)): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Unit Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo htmlspecialchars($item['image'], ENT_QUOTES, 'UTF-8'); ?>" class="item-thumb" alt="">
                                        <div>
                                            <strong><?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                            <?php if (!empty($item['wood_type']) || !empty($item['size'])): ?>
                                                <div style="font-size:12px; margin-top:3px;">
                                                    <?php if (!empty($item['wood_type'])): ?>
                                                        <span class="badge" style="background:rgba(56,189,248,0.2); color:#38bdf8; font-size:11px; padding:2px 8px;">🪵 <?php echo htmlspecialchars($item['wood_type'], ENT_QUOTES, 'UTF-8'); ?></span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($item['size'])): ?>
                                                        <span class="badge" style="background:rgba(250,204,21,0.2); color:#facc15; font-size:11px; padding:2px 8px;">📏 <?php echo htmlspecialchars($item['size'], ENT_QUOTES, 'UTF-8'); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endif; ?>
                                            <div class="text-muted small mt-1">Max available: <?php echo (int)$item['stock']; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>₹ <?php echo number_format((float)$item['price'], 2); ?></td>
                                <td>
                                    <div class="qty-control">
                                        <button class="qty-btn" onclick="updateQty(<?php echo (int)$item['cart_id']; ?>, <?php echo (int)$item['qty'] - 1; ?>)">−</button>
                                        <span class="qty-num"><?php echo (int)$item['qty']; ?></span>
                                        <button class="qty-btn" onclick="updateQty(<?php echo (int)$item['cart_id']; ?>, <?php echo (int)$item['qty'] + 1; ?>)" <?php echo ((int)$item['qty'] >= (int)$item['stock']) ? 'disabled' : ''; ?>>+</button>
                                    </div>
                                </td>
                                <td><strong>₹ <?php echo number_format($item['line_total'], 2); ?></strong></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-danger" onclick="removeProduct(<?php echo (int)$item['cart_id']; ?>)" title="Remove item">
                                        🗑️
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-4 pt-3 border-top border-secondary">
                <div>
                    <span class="text-muted">Total items: <?php echo count($items); ?></span>
                </div>
                <div class="text-end">
                    <h3 class="mb-3">Total: ₹ <?php echo number_format($total, 2); ?></h3>
                    <button onclick="window.print()" class="btn btn-outline-light me-2" style="border-radius:30px; padding:12px 22px; font-weight:500;">
                        🖨️ Print Invoice
                    </button>
                    <a href="checkout.php" class="btn-checkout">Proceed to Checkout →</a>
                </div>
            </div>
        <?php elseif (!$isLoggedIn): ?>
            <div class="text-center py-5 px-3">
                <div style="font-size: 50px; margin-bottom: 16px;">🪵 🛒</div>
                <h3 class="mb-2" style="font-weight: 600; color: #f8fafc;">Ready to Place Your Order?</h3>
                <p class="text-muted mb-4" style="max-width: 480px; margin: 0 auto; font-size: 15px; line-height: 1.6;">
                    Please create an account or log in to view your cart items, select custom wood variants, and complete your order.
                </p>
                <div class="d-flex justify-content-center gap-3 flex-wrap my-4">
                    <a href="register.php" class="btn-checkout" style="background: linear-gradient(135deg, #10b981, #059669); text-decoration: none; padding: 13px 28px;">
                        ✨ Create New Account
                    </a>
                    <a href="login.php" class="btn btn-outline-light px-4 py-2" style="border-radius: 30px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center;">
                        🔑 Login to Account
                    </a>
                </div>
                <div>
                    <a href="index.php#products" class="btn-continue">← Continue Browsing Collection</a>
                </div>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <p class="fs-4 text-muted mb-3">Your shopping cart is empty.</p>
                <a href="index.php" class="btn-checkout">Browse Products</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="js/cart.js"></script>
</body>
</html>