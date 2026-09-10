<?php
// save_order.php - Transactional order placement with user session scoping & inventory decrement

require_once "includes/session.php";
require_once "includes/auth.php";
require_once "includes/csrf.php";
require_once "db.php";

require_user_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: cart.php");
    exit();
}

if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    die("Security verification failed. Please return to checkout and try again.");
}

$user_id = (int)$_SESSION['user_id'];

// Start transaction
$conn->begin_transaction();

try {
    // 1. Fetch current user's cart with row locking
    $cartStmt = $conn->prepare("
        SELECT c.product_id, c.qty, c.wood_type, c.size, COALESCE(c.price, p.price) as effective_price, p.name, p.stock
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ?
        FOR UPDATE
    ");
    $cartStmt->bind_param("i", $user_id);
    $cartStmt->execute();
    $cartItems = $cartStmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $cartStmt->close();

    if (empty($cartItems)) {
        $conn->rollback();
        header("Location: cart.php?error=empty_cart");
        exit();
    }

    // 2. Validate stock and calculate total
    $orderTotal = 0.0;
    foreach ($cartItems as $item) {
        if ($item['qty'] > $item['stock']) {
            $conn->rollback();
            header("Location: cart.php?error=insufficient_stock&product=" . urlencode($item['name']));
            exit();
        }
        $orderTotal += ((float)$item['effective_price'] * (int)$item['qty']);
    }

    // 3. Insert order record
    $orderStmt = $conn->prepare("INSERT INTO orders (user_id, total, status) VALUES (?, ?, 'pending')");
    $orderStmt->bind_param("id", $user_id, $orderTotal);
    $orderStmt->execute();
    $order_id = $conn->insert_id;
    $orderStmt->close();

    // 4. Insert order items & decrement stock
    $itemStmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, qty, price, wood_type, size) VALUES (?, ?, ?, ?, ?, ?)");
    $stockStmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");

    foreach ($cartItems as $item) {
        $prodId = (int)$item['product_id'];
        $qty = (int)$item['qty'];
        $price = (float)$item['effective_price'];
        $wood = $item['wood_type'];
        $size = $item['size'];

        $itemStmt->bind_param("iiidss", $order_id, $prodId, $qty, $price, $wood, $size);
        $itemStmt->execute();

        $stockStmt->bind_param("ii", $qty, $prodId);
        $stockStmt->execute();
    }
    $itemStmt->close();
    $stockStmt->close();

    // 5. Clear only this user's cart
    $clearStmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $clearStmt->bind_param("i", $user_id);
    $clearStmt->execute();
    $clearStmt->close();

    // Commit all atomic operations
    $conn->commit();

    $_SESSION['last_order_id'] = $order_id;
    header("Location: payment_success.php");
    exit();

} catch (Throwable $e) {
    $conn->rollback();
    error_log("Order placement error for user {$user_id}: " . $e->getMessage());
    http_response_code(500);
    die("An error occurred while placing your order. Please try again.");
}