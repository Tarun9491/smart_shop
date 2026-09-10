<?php
// api/rate_product.php - Submit or update 1-5 star rating for a product

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../db.php";

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Please login to rate products."]);
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$data = json_decode(file_get_contents("php://input"), true);
$product_id = (int)($data['product_id'] ?? 0);
$rating = (int)($data['rating'] ?? 0);

if ($product_id <= 0) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid product ID."]);
    exit();
}

if ($rating < 1 || $rating > 5) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Rating must be between 1 and 5 stars."]);
    exit();
}

// Ensure product exists
$pStmt = $conn->prepare("SELECT id FROM products WHERE id = ? LIMIT 1");
$pStmt->bind_param("i", $product_id);
$pStmt->execute();
$exists = $pStmt->get_result()->fetch_assoc();
$pStmt->close();

if (!$exists) {
    http_response_code(404);
    echo json_encode(["status" => "error", "message" => "Product not found."]);
    exit();
}

// Upsert rating
$rStmt = $conn->prepare("
    INSERT INTO ratings (user_id, product_id, rating) 
    VALUES (?, ?, ?) 
    ON DUPLICATE KEY UPDATE rating = VALUES(rating)
");
$rStmt->bind_param("iii", $user_id, $product_id, $rating);
$rStmt->execute();
$rStmt->close();

// Fetch updated average and count
$avgStmt = $conn->prepare("
    SELECT ROUND(AVG(rating), 1) as avg_rating, COUNT(id) as total_ratings 
    FROM ratings 
    WHERE product_id = ?
");
$avgStmt->bind_param("i", $product_id);
$avgStmt->execute();
$stats = $avgStmt->get_result()->fetch_assoc();
$avgStmt->close();

echo json_encode([
    "status" => "success",
    "message" => "Thank you! Your rating has been recorded.",
    "user_rating" => $rating,
    "avg_rating" => (float)($stats['avg_rating'] ?? 0),
    "total_ratings" => (int)($stats['total_ratings'] ?? 0)
]);
