<?php
// api/remove_cart.php - Remove item from cart (supports cart_id or product_id)

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

if ($cart_id > 0) {
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND id = ?");
    $stmt->bind_param("ii", $user_id, $cart_id);
    $stmt->execute();
    $stmt->close();
} elseif ($product_id > 0) {
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    $stmt->close();
} else {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid item."]);
    exit();
}

echo json_encode([
    "status" => "success", 
    "message" => "Item removed from cart."
]);