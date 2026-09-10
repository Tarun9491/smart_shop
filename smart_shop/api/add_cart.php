<?php
// api/add_cart.php - Add item with wood_type & size variant options

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../db.php";

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        "status" => "auth_required",
        "message" => "Please create an account or login to order handcrafted furniture.",
        "login_url" => "login.php",
        "register_url" => "register.php"
    ]);
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$data = json_decode(file_get_contents("php://input"), true);
$product_id = (int)($data['product_id'] ?? 0);
$qty = max(1, (int)($data['qty'] ?? 1));
$wood_type = trim($data['wood_type'] ?? '');
$size = trim($data['size'] ?? '');

if ($product_id <= 0) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid product ID."]);
    exit();
}

// Fetch product details
$prodStmt = $conn->prepare("SELECT id, name, price, stock, has_sizes, wood_type FROM products WHERE id = ? LIMIT 1");
$prodStmt->bind_param("i", $product_id);
$prodStmt->execute();
$product = $prodStmt->get_result()->fetch_assoc();
$prodStmt->close();

if (!$product) {
    http_response_code(404);
    echo json_encode(["status" => "error", "message" => "Product not found."]);
    exit();
}

if ($product['stock'] <= 0) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Product is currently out of stock."]);
    exit();
}

// Validate / sanitize wood_type selection
if ($product['wood_type'] === 'both') {
    if (empty($wood_type) || !in_array(strtolower($wood_type), ['teakwood', 'rosewood'])) {
        $wood_type = 'Teakwood'; // default fallback
    }
} elseif ($product['wood_type'] === 'teakwood') {
    $wood_type = 'Teakwood';
} elseif ($product['wood_type'] === 'rosewood') {
    $wood_type = 'Rosewood';
} else {
    $wood_type = null;
}

// Validate / sanitize size selection
$basePrice = (float)$product['price'];
$effectivePrice = $basePrice;

if ((int)$product['has_sizes'] === 1) {
    if (empty($size) || !in_array($size, ['5 × 6', '6 × 6', '5x6', '6x6'])) {
        $size = '5 × 6';
    }
    if ($size === '6 × 6' || $size === '6x6') {
        $size = '6 × 6';
        $effectivePrice = $basePrice + 8000.0;
    } else {
        $size = '5 × 6';
        $effectivePrice = $basePrice;
    }
} else {
    $size = null;
}

// Check existing item matching this exact product + variant
$findSql = "
    SELECT id, qty FROM cart 
    WHERE user_id = ? AND product_id = ? 
      AND (wood_type = ? OR (wood_type IS NULL AND ? IS NULL))
      AND (size = ? OR (size IS NULL AND ? IS NULL))
    LIMIT 1
";
$findStmt = $conn->prepare($findSql);
$findStmt->bind_param("iissss", $user_id, $product_id, $wood_type, $wood_type, $size, $size);
$findStmt->execute();
$cartRes = $findStmt->get_result()->fetch_assoc();
$findStmt->close();

$currentQty = $cartRes ? (int)$cartRes['qty'] : 0;
$newQty = $currentQty + $qty;

if ($newQty > $product['stock']) {
    http_response_code(400);
    echo json_encode([
        "status" => "error", 
        "message" => "Cannot add more. Only {$product['stock']} item(s) available in stock."
    ]);
    exit();
}

if ($cartRes) {
    $cartId = (int)$cartRes['id'];
    $upStmt = $conn->prepare("UPDATE cart SET qty = ?, price = ? WHERE id = ?");
    $upStmt->bind_param("idi", $newQty, $effectivePrice, $cartId);
    $upStmt->execute();
    $upStmt->close();
} else {
    $insStmt = $conn->prepare("INSERT INTO cart (user_id, product_id, qty, wood_type, size, price) VALUES (?, ?, ?, ?, ?, ?)");
    $insStmt->bind_param("iiissd", $user_id, $product_id, $newQty, $wood_type, $size, $effectivePrice);
    $insStmt->execute();
    $insStmt->close();
}

// Count total cart items for badge
$cCountStmt = $conn->prepare("SELECT SUM(qty) as cnt FROM cart WHERE user_id = ?");
$cCountStmt->bind_param("i", $user_id);
$cCountStmt->execute();
$totalCartCount = (int)($cCountStmt->get_result()->fetch_assoc()['cnt'] ?? 0);
$cCountStmt->close();

$variantDesc = [];
if ($wood_type) $variantDesc[] = $wood_type;
if ($size) $variantDesc[] = $size;
$variantStr = !empty($variantDesc) ? " (" . implode(', ', $variantDesc) . ")" : "";

echo json_encode([
    "status" => "success", 
    "message" => "Added {$product['name']}{$variantStr} to cart.",
    "cart_qty" => $totalCartCount
]);