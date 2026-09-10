<?php
require_once "includes/session.php";
require_once "includes/auth.php";
require_once "includes/csrf.php";
require_once "db.php";

require_user_login();

$user_id = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT c.product_id, p.name, COALESCE(c.price, p.price) as price, p.stock, c.qty, c.wood_type, c.size
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$items = [];
$total = 0.0;
while ($row = $result->fetch_assoc()) {
    $lineTotal = (float)$row['price'] * (int)$row['qty'];
    $total += $lineTotal;
    $row['line_total'] = $lineTotal;
    $items[] = $row;
}
$stmt->close();

if (empty($items)) {
    header("Location: cart.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Guru Woodworks</title>
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
        .checkout-box {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 18px;
            padding: 35px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4);
            max-width: 800px;
            margin: auto;
        }
        .table {
            color: #fff;
        }
        .table thead th {
            background: rgba(0, 0, 0, 0.3);
            border-color: rgba(255, 255, 255, 0.1);
            color: #cbd5e1;
        }
        .table td {
            background: transparent;
            border-color: rgba(255, 255, 255, 0.1);
            color: #f1f5f9;
        }
        .btn-pay {
            background: linear-gradient(45deg, #10b981, #059669);
            border: none;
            padding: 14px 35px;
            border-radius: 30px;
            color: #fff;
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            width: 100%;
        }
        .btn-pay:hover {
            transform: scale(1.02);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
        }
    </style>
</head>
<body>
<div class="container">
    <div class="checkout-box">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="m-0">💳 Order Checkout</h2>
            <a href="cart.php" class="text-white-50 text-decoration-none">← Return to Cart</a>
        </div>

        <div class="mb-4">
            <h5>Order Summary</h5>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Unit Price</th>
                            <th>Qty</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <?php if (!empty($item['wood_type']) || !empty($item['size'])): ?>
                                        <div class="small" style="color:#38bdf8;">
                                            <?php if (!empty($item['wood_type'])) echo '🪵 ' . htmlspecialchars($item['wood_type'], ENT_QUOTES, 'UTF-8') . ' '; ?>
                                            <?php if (!empty($item['size'])) echo ' · 📏 ' . htmlspecialchars($item['size'], ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>₹ <?php echo number_format((float)$item['price'], 2); ?></td>
                                <td><?php echo (int)$item['qty']; ?></td>
                                <td class="text-end">₹ <?php echo number_format($item['line_total'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end fs-5 fw-bold">Grand Total:</td>
                            <td class="text-end fs-5 fw-bold text-warning">₹ <?php echo number_format($total, 2); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="p-3 mb-4 rounded" style="background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);">
            <h6 class="text-info mb-2">Delivery & Customer Note</h6>
            <p class="small text-white-50 m-0">
                Order will be confirmed and handcrafted immediately. Our workshop will contact you on your registered email address <strong><?php echo htmlspecialchars($_SESSION['user_email'] ?? '', ENT_QUOTES, 'UTF-8'); ?></strong> for dispatch updates.
            </p>
        </div>

        <form action="save_order.php" method="POST">
            <?php echo csrf_field(); ?>
            <button type="submit" class="btn-pay">
                Confirm & Place Order (₹ <?php echo number_format($total, 2); ?>)
            </button>
        </form>
    </div>
</div>
</body>
</html>