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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
                    #8F552F 100%
                ) !important;
            border-bottom: 2px solid rgba(214, 165, 109, 0.75) !important;
            box-shadow: 0 8px 25px rgba(62, 31, 22, 0.28) !important;
        }

        header h2 {
            color: #FFF8EE !important;
            letter-spacing: 0.2px;
        }

        header nav a {
            color: #FFF8EE !important;
            transition: all 0.25s ease !important;
        }

        header nav a:hover {
            color: #E8C99F !important;
        }

        .badge {
            background: #D6A56D !important;
            color: #3E1F16 !important;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            margin-left: 5px;
            vertical-align: middle;
            font-weight: 700;
        }

        .nav-btn {
            border-radius: 25px !important;
            transition: all 0.25s ease !important;
        }

        .nav-btn-login {
            background: transparent !important;
            border: 1px solid #D6A56D !important;
            color: #FFF8EE !important;
        }

        .nav-btn-login:hover {
            background: #D6A56D !important;
            color: #3E1F16 !important;
        }

        .nav-btn-register {
            background: linear-gradient(135deg, #D6A56D, #B9824A) !important;
            color: #3E1F16 !important;
            border: 1px solid #E8C99F !important;
            box-shadow: 0 5px 16px rgba(62, 31, 22, 0.28) !important;
        }

        .nav-btn-register:hover {
            background: linear-gradient(135deg, #E8C99F, #D6A56D) !important;
            color: #3E1F16 !important;
            transform: translateY(-2px);
        }

        /* =========================
           HERO
           ========================= */
        .hero {
            background:
                linear-gradient(
                    135deg,
                    rgba(62, 31, 22, 0.96) 0%,
                    rgba(107, 53, 31, 0.92) 52%,
                    rgba(143, 85, 47, 0.90) 100%
                ) !important;
            color: #FFF8EE !important;
            position: relative;
            overflow: hidden;
        }

        .hero::before,
        .hero::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            pointer-events: none;
        }

        .hero::before {
            width: 430px;
            height: 430px;
            top: -240px;
            left: -150px;
            background: rgba(214, 165, 109, 0.14);
        }

        .hero::after {
            width: 500px;
            height: 500px;
            right: -230px;
            bottom: -330px;
            background: rgba(244, 231, 211, 0.10);
        }

        .hero h1,
        .hero p,
        .hero button {
            position: relative;
            z-index: 1;
        }

        .hero h1 {
            color: #FFF8EE !important;
        }

        .hero p {
            color: #F4E7D3 !important;
        }

        .hero button {
            background: linear-gradient(135deg, #D6A56D, #B9824A) !important;
            color: #3E1F16 !important;
            border: 1px solid #E8C99F !important;
            box-shadow: 0 8px 22px rgba(62, 31, 22, 0.32) !important;
            font-weight: 700 !important;
        }

        .hero button:hover {
            background: linear-gradient(135deg, #E8C99F, #D6A56D) !important;
            color: #3E1F16 !important;
            transform: translateY(-2px) !important;
        }

        /* =========================
           SECTION TITLE
           ========================= */
        .title {
            color: #5A2E1E !important;
            text-shadow: none !important;
        }

        /* =========================
           PRODUCT AREA
           ========================= */
        .products {
            background: transparent !important;
        }

        .card {
            background:
                linear-gradient(
                    145deg,
                    #FFF8EE 0%,
                    #F4E7D3 58%,
                    #E8C99F 100%
                ) !important;
            border: 1px solid #C99A68 !important;
            border-radius: 20px !important;
            box-shadow:
                0 12px 30px rgba(62, 31, 22, 0.20),
                inset 0 1px 0 rgba(255,255,255,0.65) !important;
            color: #3A2117 !important;
            transition: transform 0.25s ease, box-shadow 0.25s ease !important;
        }

        .card:hover {
            transform: translateY(-6px) !important;
            box-shadow:
                0 18px 38px rgba(62, 31, 22, 0.27),
                inset 0 1px 0 rgba(255,255,255,0.75) !important;
        }

        .card h3 {
            color: #5A2E1E !important;
        }

        .card > p {
            color: #6B351F !important;
            font-weight: 700 !important;
        }

        /* Wood material badges */
        .wood-badge,
        .card .badge[style] {
            background: #E8C99F !important;
            color: #5A2E1E !important;
            border: 1px solid #B9824A !important;
        }

        /* Stock */
        .stock-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin: 6px 0;
        }

        .stock-in {
            background: #E4D4B9 !important;
            color: #5A2E1E !important;
            border: 1px solid #B9824A !important;
        }

        .stock-out {
            background: #F1D8CC !important;
            color: #7A2F22 !important;
            border: 1px solid #C78A73 !important;
        }

        /* =========================
           RATINGS
           ========================= */
        .rating-box {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin: 8px 0;
        }

        .star-score {
            color: #9A6137 !important;
            font-weight: 700;
            font-size: 14px;
        }

        .star-picker {
            display: inline-flex;
            direction: rtl;
            gap: 2px;
        }

        .star-picker span {
            cursor: pointer;
            color: rgba(107, 53, 31, 0.30) !important;
            font-size: 18px;
            transition: color 0.2s;
        }

        .star-picker span:hover,
        .star-picker span:hover ~ span {
            color: #B9824A !important;
        }

        /* =========================
           SIZES
           ========================= */
        .size-info {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 8px;
            margin: 10px 0;
        }

        .size-label {
            background: #E1D2BD !important;
            color: #5A2E1E !important;
            border: 1px solid #C2A47D !important;
            border-radius: 12px !important;
            font-weight: 600;
        }

        /* =========================
           PRODUCT BUTTON
           ========================= */
        .card button {
            background: linear-gradient(135deg, #6B351F, #B9824A) !important;
            color: #FFF8EE !important;
            border: 1px solid #8F552F !important;
            box-shadow: 0 7px 17px rgba(62, 31, 22, 0.22) !important;
        }

        .card button:hover {
            background: linear-gradient(135deg, #3E1F16, #8F552F) !important;
            color: #FFF8EE !important;
            transform: translateY(-2px) !important;
        }

        .card button:disabled {
            background: #8C7A68 !important;
            color: #F4E7D3 !important;
            border-color: #80614A !important;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
        }

        /* =========================
           CART CALLOUT
           ========================= */
        .cart {
            background:
                linear-gradient(
                    135deg,
                    #3E1F16,
                    #6B351F 55%,
                    #8F552F
                ) !important;
            color: #FFF8EE !important;
            border-top: 2px solid #D6A56D !important;
            border-bottom: 2px solid #D6A56D !important;
        }

        .cart h2 {
            color: #FFF8EE !important;
        }

        .cart p {
            color: #F4E7D3 !important;
        }

        .checkout-btn {
            background: linear-gradient(135deg, #D6A56D, #B9824A) !important;
            color: #3E1F16 !important;
            border: 1px solid #E8C99F !important;
            box-shadow: 0 7px 18px rgba(62, 31, 22, 0.28) !important;
        }

        .checkout-btn:hover {
            background: #E8C99F !important;
            color: #3E1F16 !important;
        }

        /* =========================
           IMAGE MODAL
           ========================= */
        .image-modal {
            background: rgba(42, 23, 16, 0.94) !important;
        }

        .close-btn {
            color: #FFF8EE !important;
        }

        /* =========================
           FOOTER
           ========================= */
        footer {
            background: #2A1710 !important;
            color: #E8C99F !important;
            border-top: 1px solid #6B351F !important;
        }

        /* Keep error/no-product text readable */
        .products > p {
            color: #5A2E1E !important;
        }


        /* =========================================================
           MOBILE — FINAL RESPONSIVE LAYOUT
           Keeps every section inside the phone viewport.
           Desktop layout is unchanged.
           ========================================================= */
        @media (max-width: 768px) {
            html,
            body {
                width: 100% !important;
                max-width: 100% !important;
                min-width: 0 !important;
                overflow-x: hidden !important;
            }

            body {
                margin: 0 !important;
            }

            /* ---------- HEADER ---------- */
            header {
                position: relative !important;
                top: auto !important;
                left: auto !important;
                width: 100% !important;
                max-width: 100% !important;
                min-width: 0 !important;
                height: auto !important;
                min-height: 0 !important;
                margin: 0 !important;
                padding: 18px 12px 22px !important;
                display: block !important;
                overflow: hidden !important;
                box-sizing: border-box !important;
            }

            header h2 {
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 0 18px !important;
                padding: 0 !important;
                text-align: center !important;
                font-size: 25px !important;
                line-height: 1.25 !important;
                white-space: nowrap !important;
            }

            header nav {
                width: 100% !important;
                max-width: 100% !important;
                min-width: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
                display: flex !important;
                flex-wrap: wrap !important;
                justify-content: center !important;
                align-items: center !important;
                gap: 10px 14px !important;
                overflow: visible !important;
                box-sizing: border-box !important;
            }

            header nav a {
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                width: auto !important;
                max-width: 100% !important;
                min-width: 0 !important;
                margin: 0 !important;
                padding: 7px 4px !important;
                font-size: 15px !important;
                line-height: 1.2 !important;
                white-space: nowrap !important;
                box-sizing: border-box !important;
            }

            header nav .nav-btn {
                padding: 10px 18px !important;
                border-radius: 28px !important;
            }

            /* Put the account button on its own centered row.
               This prevents the header from squeezing other links. */
            header nav .nav-btn-register {
                flex-basis: auto !important;
                margin-top: 2px !important;
            }

            header nav .badge {
                flex: 0 0 auto !important;
                max-width: none !important;
                margin-left: 4px !important;
                padding: 4px 8px !important;
                white-space: nowrap !important;
            }

            /* ---------- HERO ---------- */
            .hero {
                position: relative !important;
                z-index: 1 !important;
                width: 100% !important;
                max-width: 100% !important;
                min-width: 0 !important;
                min-height: 390px !important;
                margin: 0 !important;
                padding: 52px 18px 55px !important;
                display: flex !important;
                flex-direction: column !important;
                justify-content: center !important;
                align-items: center !important;
                text-align: center !important;
                overflow: hidden !important;
                box-sizing: border-box !important;
            }

            .hero h1 {
                position: relative !important;
                z-index: 2 !important;
                width: 100% !important;
                max-width: 650px !important;
                margin: 0 0 18px !important;
                padding: 0 !important;
                font-size: clamp(29px, 8vw, 42px) !important;
                line-height: 1.15 !important;
                text-align: center !important;
                overflow-wrap: normal !important;
                word-break: normal !important;
            }

            .hero p {
                position: relative !important;
                z-index: 2 !important;
                width: 100% !important;
                max-width: 620px !important;
                margin: 0 auto 25px !important;
                padding: 0 !important;
                font-size: 15px !important;
                line-height: 1.65 !important;
                text-align: center !important;
            }

            .hero button {
                position: relative !important;
                z-index: 2 !important;
                width: auto !important;
                max-width: calc(100% - 20px) !important;
                margin: 0 auto !important;
                padding: 13px 24px !important;
                font-size: 15px !important;
                line-height: 1.3 !important;
                box-sizing: border-box !important;
            }

            /* ---------- COLLECTION TITLE ---------- */
            .title {
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 !important;
                padding: 34px 14px 22px !important;
                font-size: 27px !important;
                line-height: 1.25 !important;
                text-align: center !important;
                box-sizing: border-box !important;
            }

            /* ---------- PRODUCTS ---------- */
            .products {
                width: 100% !important;
                max-width: 100% !important;
                min-width: 0 !important;
                margin: 0 !important;
                padding: 8px 14px 35px !important;
                display: grid !important;
                grid-template-columns: minmax(0, 1fr) !important;
                gap: 22px !important;
                overflow: hidden !important;
                box-sizing: border-box !important;
            }

            .card {
                width: 100% !important;
                max-width: 100% !important;
                min-width: 0 !important;
                margin: 0 !important;
                padding: 0 0 24px !important;
                overflow: hidden !important;
                box-sizing: border-box !important;
            }

            .product-image {
                display: block !important;
                width: 100% !important;
                max-width: 100% !important;
                height: auto !important;
                aspect-ratio: 4 / 3 !important;
                object-fit: cover !important;
                margin: 0 !important;
                box-sizing: border-box !important;
            }

            .card h3 {
                width: 100% !important;
                max-width: 100% !important;
                margin: 16px 0 8px !important;
                padding: 0 14px !important;
                font-size: 22px !important;
                line-height: 1.25 !important;
                text-align: center !important;
                overflow-wrap: anywhere !important;
                word-break: break-word !important;
                box-sizing: border-box !important;
            }

            .card > p {
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 0 10px !important;
                padding: 0 10px !important;
                font-size: 20px !important;
                line-height: 1.3 !important;
                text-align: center !important;
                overflow-wrap: anywhere !important;
                word-break: break-word !important;
                box-sizing: border-box !important;
            }

            .card .badge[style],
            .stock-badge {
                max-width: calc(100% - 28px) !important;
                box-sizing: border-box !important;
                overflow-wrap: anywhere !important;
                word-break: break-word !important;
            }

            .rating-box {
                width: 100% !important;
                max-width: 100% !important;
                padding: 0 10px !important;
                margin: 10px 0 !important;
                display: flex !important;
                flex-wrap: wrap !important;
                justify-content: center !important;
                align-items: center !important;
                gap: 5px !important;
                box-sizing: border-box !important;
            }

            .star-score {
                max-width: 100% !important;
                font-size: 14px !important;
                overflow-wrap: anywhere !important;
            }

            .star-picker {
                max-width: 100% !important;
                gap: 1px !important;
            }

            .star-picker span {
                font-size: 18px !important;
            }

            .size-info {
                width: 100% !important;
                max-width: 100% !important;
                padding: 0 10px !important;
                margin: 10px 0 !important;
                display: flex !important;
                flex-wrap: wrap !important;
                justify-content: center !important;
                gap: 7px !important;
                overflow: hidden !important;
                box-sizing: border-box !important;
            }

            .size-label {
                max-width: 100% !important;
                padding: 8px 10px !important;
                font-size: 12px !important;
                line-height: 1.2 !important;
                white-space: normal !important;
                overflow-wrap: anywhere !important;
                box-sizing: border-box !important;
            }

            .card button {
                display: block !important;
                width: calc(100% - 28px) !important;
                max-width: 360px !important;
                min-width: 0 !important;
                margin: 16px auto 0 !important;
                padding: 13px 16px !important;
                font-size: 15px !important;
                line-height: 1.3 !important;
                box-sizing: border-box !important;
            }

            /* ---------- SHOPPING CART CALLOUT ---------- */
            .cart {
                width: calc(100% - 30px) !important;
                max-width: calc(100% - 30px) !important;
                min-width: 0 !important;
                margin: 30px auto 34px !important;
                padding: 38px 18px !important;
                text-align: center !important;
                border-radius: 22px !important;
                overflow: hidden !important;
                box-sizing: border-box !important;
            }

            .cart h2 {
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 0 18px !important;
                padding: 0 !important;
                font-size: clamp(23px, 6.5vw, 30px) !important;
                line-height: 1.3 !important;
                text-align: center !important;
                overflow-wrap: normal !important;
                word-break: normal !important;
            }

            .cart p {
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 auto 24px !important;
                padding: 0 4px !important;
                font-size: 15px !important;
                line-height: 1.6 !important;
                text-align: center !important;
                overflow-wrap: normal !important;
                word-break: normal !important;
                box-sizing: border-box !important;
            }

            .checkout-btn {
                display: inline-flex !important;
                align-items: center !important;
                justify-content: center !important;
                width: auto !important;
                max-width: calc(100% - 10px) !important;
                min-width: 0 !important;
                margin: 0 auto !important;
                padding: 13px 22px !important;
                font-size: 15px !important;
                line-height: 1.3 !important;
                white-space: normal !important;
                text-align: center !important;
                box-sizing: border-box !important;
            }

            /* ---------- FOOTER ---------- */
            footer {
                width: 100% !important;
                max-width: 100% !important;
                min-width: 0 !important;
                margin: 0 !important;
                padding: 20px 12px !important;
                text-align: center !important;
                overflow: hidden !important;
                box-sizing: border-box !important;
            }

            footer p {
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 !important;
                font-size: 12px !important;
                line-height: 1.5 !important;
                overflow-wrap: anywhere !important;
            }

            /* ---------- IMAGE MODAL ---------- */
            .image-modal {
                width: 100% !important;
                max-width: 100% !important;
                min-width: 0 !important;
                padding: 15px !important;
                overflow: hidden !important;
                box-sizing: border-box !important;
            }

            .full-image {
                display: block !important;
                width: auto !important;
                max-width: 92vw !important;
                max-height: 82vh !important;
                object-fit: contain !important;
                margin: auto !important;
            }
        }

        /* ---------- SMALL PHONES ---------- */
        @media (max-width: 380px) {
            header {
                padding: 15px 9px 19px !important;
            }

            header h2 {
                font-size: 21px !important;
                margin-bottom: 15px !important;
            }

            header nav {
                gap: 8px 10px !important;
            }

            header nav a {
                font-size: 13px !important;
            }

            header nav .nav-btn {
                padding: 8px 13px !important;
            }

            .hero {
                min-height: 360px !important;
                padding: 48px 14px 48px !important;
            }

            .hero h1 {
                font-size: 27px !important;
                margin-bottom: 16px !important;
            }

            .hero p {
                font-size: 13px !important;
                line-height: 1.55 !important;
                margin-bottom: 22px !important;
            }

            .hero button {
                font-size: 14px !important;
                padding: 12px 20px !important;
            }

            .title {
                font-size: 23px !important;
                padding: 30px 10px 20px !important;
            }

            .products {
                padding-left: 10px !important;
                padding-right: 10px !important;
            }

            .card h3 {
                font-size: 20px !important;
            }

            .card > p {
                font-size: 18px !important;
            }

            .cart {
                width: calc(100% - 20px) !important;
                max-width: calc(100% - 20px) !important;
                padding: 32px 14px !important;
            }

            .cart h2 {
                font-size: 22px !important;
            }

            .cart p {
                font-size: 13px !important;
            }

            .checkout-btn {
                padding: 12px 17px !important;
                font-size: 14px !important;
            }
        }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <header>
        <h2>🪵 Guru Woodworks</h2>
        <nav>
            <a href="index.php">Home</a>
            <a href="#products">Products</a>
            <a href="cart.php">Cart <span id="cart-count" class="badge"><?php echo $cartCount; ?></span></a>
            <?php if ($isLoggedIn): ?>
                <a href="profile.php">My Profile</a>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="login.php" class="nav-btn nav-btn-login">🔑 Login</a>
                <a href="register.php" class="nav-btn nav-btn-register">✨ Create Account</a>
            <?php endif; ?>
        </nav>
    </header>

    <!-- HERO -->
    <section class="hero">
        <h1>Discover Handcrafted Woodwork</h1>
        <p>Premium beds, dining sets, and bespoke carpentry built to last generations.</p>
        <button onclick="document.getElementById('products').scrollIntoView({ behavior: 'smooth' })">
            🔥 Browse Collection
        </button>
    </section>

    <!-- PRODUCTS -->
    <h2 class="title" id="products">🔥 Available Collection</h2>

    <section class="products">
        <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
                <div class="card">
                    <img src="<?php echo htmlspecialchars($row['image'], ENT_QUOTES, 'UTF-8'); ?>"
                         alt="<?php echo htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'); ?>"
                         class="product-image"
                         onclick="openImage(this.src)">

                    <h3><?php echo htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                    <p>₹ <?php echo number_format((float)$row['price'], 2); ?></p>

                    <!-- Wood Material Badge -->
                    <?php if ($row['wood_type'] === 'both'): ?>
                        <div style="margin: 4px 0;">
                            <span class="badge" style="font-size:12px; padding:4px 10px;">🪵 Teakwood & Rosewood</span>
                        </div>
                    <?php elseif ($row['wood_type'] === 'teakwood'): ?>
                        <div style="margin: 4px 0;">
                            <span class="badge" style="font-size:12px; padding:4px 10px;">🪵 Teakwood</span>
                        </div>
                    <?php elseif ($row['wood_type'] === 'rosewood'): ?>
                        <div style="margin: 4px 0;">
                            <span class="badge" style="font-size:12px; padding:4px 10px;">🪵 Rosewood</span>
                        </div>
                    <?php endif; ?>

                    <!-- Stock indicator -->
                    <?php if ((int)$row['stock'] > 0): ?>
                        <span class="stock-badge stock-in">In Stock (<?php echo (int)$row['stock']; ?> left)</span>
                    <?php else: ?>
                        <span class="stock-badge stock-out">Out of Stock</span>
                    <?php endif; ?>

                    <!-- Ratings -->
                    <div class="rating-box">
                        <span class="star-score" id="prod-rating-<?php echo (int)$row['id']; ?>">
                            ★ <?php echo number_format((float)$row['avg_rating'], 1); ?> (<?php echo (int)$row['total_ratings']; ?>)
                        </span>
                        <div class="star-picker" title="Click to rate this item">
                            <span onclick="rateProduct(<?php echo (int)$row['id']; ?>, 5)">★</span>
                            <span onclick="rateProduct(<?php echo (int)$row['id']; ?>, 4)">★</span>
                            <span onclick="rateProduct(<?php echo (int)$row['id']; ?>, 3)">★</span>
                            <span onclick="rateProduct(<?php echo (int)$row['id']; ?>, 2)">★</span>
                            <span onclick="rateProduct(<?php echo (int)$row['id']; ?>, 1)">★</span>
                        </div>
                    </div>

                    <!-- Size options -->
                    <?php if ((int)$row['has_sizes'] === 1): ?>
                        <div class="size-info">
                            <span class="size-label">5 × 6 : ₹<?php echo number_format((float)$row['price'], 2); ?></span>
                            <span class="size-label">6 × 6 : ₹<?php echo number_format((float)$row['price'] + 8000, 2); ?></span>
                        </div>
                    <?php endif; ?>

                    <button onclick="promptProductOptions(<?php echo (int)$row['id']; ?>, '<?php echo addslashes(htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8')); ?>', <?php echo (float)$row['price']; ?>, <?php echo (int)$row['has_sizes']; ?>, '<?php echo htmlspecialchars($row['wood_type'], ENT_QUOTES, 'UTF-8'); ?>')" <?php echo ((int)$row['stock'] <= 0) ? 'disabled' : ''; ?>>
                        <?php echo ((int)$row['stock'] > 0) ? '🛒 Add to Cart' : 'Sold Out'; ?>
                    </button>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="text-align:center; grid-column: 1/-1;">No products currently available.</p>
        <?php endif; ?>
    </section>

    <!-- CART CALLOUT -->
    <section class="cart">
        <h2>🧾 Your Shopping Cart</h2>
        <p>Ready to review your handcrafted furniture selections?</p>
        <a href="cart.php" class="checkout-btn">Go to Cart (<?php echo $cartCount; ?> items)</a>
    </section>

    <!-- IMAGE MODAL -->
    <div id="imageModal" class="image-modal" onclick="closeImage()">
        <span class="close-btn">&times;</span>
        <img id="fullImage" class="full-image" alt="Full Preview">
    </div>

    <!-- FOOTER -->
    <footer>
        <p>© <?php echo date('Y'); ?> Guru Woodworks. All rights reserved.</p>
    </footer>

    <!-- JS -->
    <script>
        window.IS_USER_LOGGED_IN = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
    </script>
    <script src="js/cart.js"></script>
    <script>
        function openImage(src) {
            document.getElementById("imageModal").style.display = "flex";
            document.getElementById("fullImage").src = src;
        }
        function closeImage() {
            document.getElementById("imageModal").style.display = "none";
        }
    </script>
</body>
</html>
