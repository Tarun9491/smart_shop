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
        .badge {
            background: #ff416c;
            color: #fff;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            margin-left: 5px;
            vertical-align: middle;
        }
        .stock-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin: 6px 0;
        }
        .stock-in { background: rgba(16, 185, 129, 0.2); color: #34d399; border: 1px solid rgba(52, 211, 153, 0.4); }
        .stock-out { background: rgba(239, 68, 68, 0.2); color: #f87171; border: 1px solid rgba(248, 113, 113, 0.4); }
        .rating-box {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin: 8px 0;
        }
        .star-score { color: #f59e0b; font-weight: 600; font-size: 14px; }
        .star-picker {
            display: inline-flex;
            direction: rtl;
            gap: 2px;
        }
        .star-picker span {
            cursor: pointer;
            color: rgba(255, 255, 255, 0.3);
            font-size: 18px;
            transition: color 0.2s;
        }
        .star-picker span:hover,
        .star-picker span:hover ~ span {
            color: #fbbf24;
        }
        .card button:disabled {
            background: #4b5563;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: none !important;
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
                            <span class="badge" style="background:rgba(56,189,248,0.2); color:#38bdf8; border:1px solid rgba(56,189,248,0.4); font-size:12px; padding:4px 10px;">🪵 Teakwood & Rosewood</span>
                        </div>
                    <?php elseif ($row['wood_type'] === 'teakwood'): ?>
                        <div style="margin: 4px 0;">
                            <span class="badge" style="background:rgba(180,83,9,0.25); color:#fde68a; border:1px solid rgba(245,158,11,0.4); font-size:12px; padding:4px 10px;">🪵 Teakwood</span>
                        </div>
                    <?php elseif ($row['wood_type'] === 'rosewood'): ?>
                        <div style="margin: 4px 0;">
                            <span class="badge" style="background:rgba(157,23,77,0.25); color:#fbcfe8; border:1px solid rgba(244,63,94,0.4); font-size:12px; padding:4px 10px;">🪵 Rosewood</span>
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

                    <!-- Size options - only displayed if admin enabled sizes for this product -->
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