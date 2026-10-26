<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/db.php';

$isLoggedIn = !empty($_SESSION['user_id']);
$userId = $isLoggedIn ? (int) $_SESSION['user_id'] : 0;
$userName = $isLoggedIn ? (string) ($_SESSION['user_name'] ?? 'Customer') : 'Guest';

function e($value): string {
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$products = [];
$trendingProducts = [];
$cartCount = 0;
$pageError = '';

/* Load the full product catalog. */
$sql = "
    SELECT p.id, p.name, p.price, p.image, p.stock, p.has_sizes, p.wood_type,
           COALESCE(ROUND(AVG(r.rating), 1), 0.0) AS avg_rating,
           COUNT(DISTINCT r.id) AS total_ratings,
           (SELECT r2.rating FROM ratings r2
            WHERE r2.user_id = ? AND r2.product_id = p.id LIMIT 1) AS my_rating
    FROM products p
    LEFT JOIN ratings r ON p.id = r.product_id
    GROUP BY p.id, p.name, p.price, p.image, p.stock, p.has_sizes, p.wood_type
    ORDER BY p.id DESC
";

$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param('i', $userId);
    if ($stmt->execute()) {
        $productResult = $stmt->get_result();
        if ($productResult) {
            while ($row = $productResult->fetch_assoc()) {
                $products[] = $row;
            }
        }
    } else {
        error_log('Homepage product query failed: ' . $stmt->error);
        $pageError = 'Products are temporarily unavailable. Please try again later.';
    }
    $stmt->close();
} else {
    error_log('Homepage product query could not be prepared: ' . $conn->error);
    $pageError = 'Products are temporarily unavailable. Please try again later.';
}

/* Load products marked as trending by the admin. If the database migration
   has not been run yet, keep the homepage working without the trending row. */
$trendingResult = $conn->query(
    "SELECT id, name, price, image, stock, has_sizes, wood_type
     FROM products WHERE is_trending = 1 ORDER BY id DESC"
);
if ($trendingResult) {
    while ($row = $trendingResult->fetch_assoc()) {
        $trendingProducts[] = $row;
    }
} else {
    error_log('Trending products query failed. Ensure products.is_trending exists: ' . $conn->error);
}

/* Count cart items only for a signed-in customer. */
if ($isLoggedIn) {
    $countStmt = $conn->prepare('SELECT COALESCE(SUM(qty), 0) AS item_count FROM cart WHERE user_id = ?');
    if ($countStmt) {
        $countStmt->bind_param('i', $userId);
        if ($countStmt->execute()) {
            $countRow = $countStmt->get_result()->fetch_assoc();
            $cartCount = (int) ($countRow['item_count'] ?? 0);
        }
        $countStmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#fff8ee">
    <title>Guru Woodworks | Premium Handcrafted Furniture</title>
    <meta name="description" content="Discover handcrafted wooden furniture made with timeless materials at Guru Woodworks.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        :root{--ink:#2c211b;--muted:#78685b;--cream:#fffaf3;--sand:#f1e5d5;--wood:#80502e;--wood-dark:#4a2b1c;--gold:#b88a50;--line:#e8d9c8;--white:#fff}
        *{box-sizing:border-box}
        html{scroll-behavior:smooth;scroll-padding-top:100px}
