<?php
// api/update_qty.php - Update cart quantity respecting stock limits (supports cart_id or product_id)

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../db.php";

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Please login first."]);
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$data = json_decode(file_get_contents("php://input"), true);
$cart_id = (int)($data['cart_id'] ?? 0);
$product_id = (int)($data['product_id'] ?? 0);
$qty = (int)($data['qty'] ?? 0);

// Resolve cart item and product
if ($cart_id > 0) {
    $cStmt = $conn->prepare("SELECT c.id, c.product_id, c.price as cart_price, p.price as base_price, p.stock FROM cart c JOIN products p ON c.product_id = p.id WHERE c.id = ? AND c.user_id = ? LIMIT 1");
    $cStmt->bind_param("ii", $cart_id, $user_id);
    $cStmt->execute();
    $item = $cStmt->get_result()->fetch_assoc();
    $cStmt->close();
} elseif ($product_id > 0) {
    $cStmt = $conn->prepare("SELECT c.id, c.product_id, c.price as cart_price, p.price as base_price, p.stock FROM cart c JOIN products p ON c.product_id = p.id WHERE c.product_id = ? AND c.user_id = ? LIMIT 1");
    $cStmt->bind_param("ii", $product_id, $user_id);
    $cStmt->execute();
    $item = $cStmt->get_result()->fetch_assoc();
    $cStmt->close();
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid cart item."]);
    exit();
}

if (!$item) {
    http_response_code(404);
    echo json_encode(["status" => "error", "message" => "Item not found in your cart."]);
    exit();
}

$resolvedCartId = (int)$item['id'];
$effectivePrice = $item['cart_price'] !== null ? (float)$item['cart_price'] : (float)$item['base_price'];

if ($qty <= 0) {
    $delStmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    $delStmt->bind_param("ii", $resolvedCartId, $user_id);
    $delStmt->execute();
    $delStmt->close();

    echo json_encode([
        "status" => "success", 
        "action" => "removed",
        "message" => "Item removed from cart."
    ]);
    exit();
}

if ($qty > (int)$item['stock']) {
    http_response_code(400);
    echo json_encode([
        "status" => "error", 
        "message" => "Cannot exceed available stock ({$item['stock']} available)."
    ]);
    exit();
}

$upStmt = $conn->prepare("UPDATE cart SET qty = ? WHERE id = ? AND user_id = ?");
$upStmt->bind_param("iii", $qty, $resolvedCartId, $user_id);
$upStmt->execute();
$upStmt->close();

echo json_encode([
    "status" => "success", 
    "action" => "updated",
    "qty" => $qty,
    "line_total" => (float)($effectivePrice * $qty),
    "message" => "Cart updated successfully."
]);
