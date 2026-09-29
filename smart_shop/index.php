<?php
require_once "includes/session.php";
require_once "includes/auth.php";
require_once "db.php";

$isLoggedIn = !empty($_SESSION['user_id']);
$user_id = $isLoggedIn ? (int)$_SESSION['user_id'] : 0;
$user_name = $isLoggedIn ? ($_SESSION['user_name'] ?? 'Customer') : 'Guest';

// Fetch products with rating statistics
$sql = "
    SELECT p.id, p.name, p.price, p.image, p.stock, p.has_sizes, p.wood_type,
           COALESCE(ROUND(AVG(r.rating), 1), 0.0) as avg_rating,
           COUNT(r.id) as total_ratings,
           (SELECT rating FROM ratings WHERE user_id = ? AND product_id = p.id LIMIT 1) as my_rating
    FROM products p
    LEFT JOIN ratings r ON p.id = r.product_id
    GROUP BY p.id
    ORDER BY p.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Count items in user cart if logged in
$cartCount = 0;
if ($isLoggedIn) {
    $countStmt = $conn->prepare("SELECT SUM(qty) as count FROM cart WHERE user_id = ?");
    $countStmt->bind_param("i", $user_id);
    $countStmt->execute();
    $cartCount = (int)($countStmt->get_result()->fetch_assoc()['count'] ?? 0);
    $countStmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Guru Woodworks - Premium Handcrafted Furniture</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">

    <style>
        /* =========================================================
           GURU WOODWORKS — NATURAL TEAK + ROSEWOOD HOME THEME
           Interface only. PHP/database/cart functionality preserved.
           ========================================================= */

        :root {
            --rosewood-dark: #3E1F16;
            --rosewood: #6B351F;
            --rosewood-light: #8F552F;
            --teak-dark: #8A5A32;
            --teak: #B9824A;
            --teak-light: #D6A56D;
            --teak-pale: #E8C99F;
            --cream: #FFF8EE;
            --cream-dark: #F4E7D3;
            --text: #3A2117;
            --muted: #80614A;
            --white: #FFFFFF;
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0 !important;
            font-family: 'Poppins', sans-serif !important;
            color: var(--text) !important;
            background:
                linear-gradient(
                    180deg,
                    #F4E7D3 0%,
                    #E8C99F 42%,
                    #F4E7D3 100%
                ) !important;
        }

        /* =========================
           NAVBAR
           ========================= */
        header {
            background:
                linear-gradient(
                    135deg,
                    #3E1F16 0%,
                    #6B351F 48%,
