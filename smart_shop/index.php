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
        body{margin:0!important;background:var(--cream)!important;color:var(--ink)!important;font-family:'DM Sans',sans-serif!important;line-height:1.6}
        a{color:inherit}
        .site-header{position:sticky;top:0;z-index:1000;display:flex;align-items:center;justify-content:space-between;gap:24px;padding:16px clamp(18px,5vw,76px);background:rgba(255,250,243,.96);backdrop-filter:blur(16px);border-bottom:1px solid var(--line)}
        .brand{font-family:'Playfair Display',serif;font-size:clamp(21px,2.3vw,30px);font-weight:700;text-decoration:none;white-space:nowrap;color:var(--wood-dark)}
        .brand-mark{margin-right:8px}
        .main-nav{display:flex;align-items:center;justify-content:flex-end;gap:clamp(12px,2vw,28px);flex-wrap:wrap}
        .main-nav a{text-decoration:none;color:var(--ink);font-size:14px;font-weight:600;transition:color .2s}
        .main-nav a:hover{color:var(--gold)}
        .nav-pill{padding:9px 16px;border:1px solid var(--wood)!important;border-radius:30px;color:var(--wood)!important}
        .nav-pill.primary{background:var(--wood);color:#fff!important}
        .cart-badge{display:inline-flex;align-items:center;justify-content:center;min-width:21px;height:21px;padding:0 6px;margin-left:4px;border-radius:20px;background:var(--wood);color:#fff;font-size:11px}
        .hero{position:relative;isolation:isolate;min-height:550px;display:flex;align-items:center;padding:clamp(64px,9vw,120px) clamp(20px,8vw,120px);margin:0;background:#5a4635 url('https://images.unsplash.com/photo-1600210492486-724fe5c67fb0?auto=format&fit=crop&w=2200&q=85') center/cover no-repeat;color:#fff}
        .hero:before{content:"";position:absolute;inset:0;z-index:-1;background:linear-gradient(90deg,rgba(35,23,15,.82),rgba(35,23,15,.46) 55%,rgba(35,23,15,.12))}
        .hero-content{max-width:680px}
        .eyebrow{display:inline-block;margin:0 0 18px;color:#f0d6b3;font-size:12px;font-weight:700;letter-spacing:.18em;text-transform:uppercase}
        .hero h1{max-width:650px;margin:0 0 20px;font-family:'Playfair Display',serif;font-size:clamp(40px,6vw,76px);line-height:1.08;font-weight:600;letter-spacing:-.025em}
        .hero p{max-width:560px;margin:0 0 30px;color:#f7eee4;font-size:clamp(16px,1.8vw,19px)}
        .button{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:13px 22px;border:1px solid transparent;border-radius:3px;background:var(--wood);color:#fff;text-decoration:none;font:600 14px 'DM Sans',sans-serif;cursor:pointer;transition:transform .2s,background .2s}
        .button:hover{transform:translateY(-2px);background:var(--wood-dark)}
        .button-light{background:#fff;color:var(--wood-dark)}
        .button-light:hover{background:#f2e5d5}
        .section{max-width:1440px;margin:0 auto;padding:72px clamp(18px,5vw,72px)}
        .section-head{display:flex;align-items:end;justify-content:space-between;gap:20px;margin-bottom:28px}
        .section-kicker{margin:0 0 7px;color:var(--gold);font-size:11px;font-weight:700;letter-spacing:.17em;text-transform:uppercase}
        .section-title{margin:0;font-family:'Playfair Display',serif;font-size:clamp(29px,3vw,42px);line-height:1.2;color:var(--wood-dark)}
        .section-description{margin:10px 0 0;color:var(--muted);font-size:15px}
        .text-link{font-size:14px;font-weight:700;text-decoration:none;color:var(--wood);white-space:nowrap}
        .product-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:26px}
        .product-card{min-width:0;overflow:hidden;background:#fff;border:1px solid var(--line);transition:transform .25s,box-shadow .25s}
        .product-card:hover{transform:translateY(-5px);box-shadow:0 15px 35px rgba(62,38,21,.10)}
        .product-image-wrap{position:relative;overflow:hidden;background:var(--sand);aspect-ratio:4/3}
        .product-image{display:block;width:100%;height:100%;object-fit:cover;cursor:zoom-in;transition:transform .5s}
        .product-card:hover .product-image{transform:scale(1.04)}
        .stock-tag{position:absolute;top:13px;left:13px;padding:5px 9px;background:rgba(255,250,243,.94);color:var(--wood-dark);font-size:11px;font-weight:700}
        .product-info{padding:20px}
        .product-type{margin:0 0 7px;color:var(--gold);font-size:11px;font-weight:700;letter-spacing:.12em;text-transform:uppercase}
        .product-name{margin:0 0 8px;font-family:'Playfair Display',serif;font-size:23px;line-height:1.3;color:var(--wood-dark)}
        .product-rating{margin:0 0 12px;color:var(--muted);font-size:13px}
        .stars{color:#b88642;letter-spacing:1px}
        .product-price{margin:0 0 16px;color:var(--wood);font-size:21px;font-weight:700}
        .product-actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
        .product-actions .button{flex:1}
        .button-outline{background:transparent;border-color:var(--line);color:var(--wood-dark)}
        .button-outline:hover{background:var(--sand)}
        .empty-state{padding:34px 20px;background:#fff;border:1px dashed var(--line);color:var(--muted);text-align:center}
        .benefits{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-top:8px}
        .benefit{padding:24px;background:#f6eee4;border:1px solid var(--line)}
        .benefit strong{display:block;margin-bottom:5px;font-family:'Playfair Display',serif;font-size:20px;color:var(--wood-dark)}
        .benefit span{font-size:13px;color:var(--muted)}
        .cart-callout{margin:0 clamp(18px,5vw,72px) 72px;padding:32px clamp(20px,4vw,48px);display:flex;align-items:center;justify-content:space-between;gap:24px;background:var(--wood-dark);color:#fff}
        .cart-callout h2{margin:0 0 5px;font-family:'Playfair Display',serif;font-size:28px}
        .cart-callout p{margin:0;color:#e8d8c7}
        .site-footer{padding:30px 20px;background:#241811;color:#e9d9c8;text-align:center;font-size:13px}
        .site-footer p{margin:4px}
        .image-modal{display:none;position:fixed;inset:0;z-index:2000;align-items:center;justify-content:center;padding:30px;background:rgba(20,13,9,.92)}
        .image-modal.open{display:flex}
        .full-image{max-width:min(1100px,92vw);max-height:85vh;object-fit:contain}
        .close-btn{position:absolute;top:18px;right:28px;border:0;background:transparent;color:#fff;font-size:42px;cursor:pointer}
        .page-error{max-width:1200px;margin:20px auto;padding:14px 18px;background:#fff0ed;color:#8a261d;border:1px solid #f1c7c1}
        @media(max-width:900px){.product-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.site-header{align-items:flex-start;flex-direction:column;gap:12px}.main-nav{justify-content:flex-start}.hero{min-height:480px}.benefits{grid-template-columns:1fr}}
        @media(max-width:600px){.site-header{padding:14px 18px}.main-nav{gap:10px 15px}.main-nav a{font-size:13px}.hero{min-height:440px;padding:72px 22px;background-position:60% center}.hero:before{background:linear-gradient(90deg,rgba(35,23,15,.84),rgba(35,23,15,.5))}.section{padding:50px 18px}.section-head{align-items:flex-start;flex-direction:column}.product-grid{grid-template-columns:1fr;gap:18px}.product-info{padding:17px}.cart-callout{margin:0 18px 48px;align-items:flex-start;flex-direction:column}.cart-callout h2{font-size:25px}}
    </style>
</head>
<body>
<header class="site-header">
    <a class="brand" href="index.php"><span class="brand-mark">🪵</span>Guru Woodworks</a>
    <nav class="main-nav" aria-label="Main navigation">
        <a href="index.php">Home</a>
        <a href="#collection">Collection</a>
        <a href="cart.php">Cart <span class="cart-badge" id="cart-count"><?php echo $cartCount; ?></span></a>
        <?php if ($isLoggedIn): ?>
            <a href="profile.php">Hi, <?php echo e($userName); ?></a>
            <a class="nav-pill" href="logout.php">Logout</a>
        <?php else: ?>
            <a class="nav-pill" href="login.php">Login</a>
            <a class="nav-pill primary" href="register.php">Create Account</a>
        <?php endif; ?>
    </nav>
</header>

<main>
    <section class="hero">
        <div class="hero-content">
            <p class="eyebrow">Made with care · Built to last</p>
            <h1>Furniture that feels like home.</h1>
            <p>Discover thoughtfully handcrafted wooden furniture, made with timeless materials and designed for the way you live.</p>
            <a class="button button-light" href="#collection">Explore the Collection <span aria-hidden="true">→</span></a>
        </div>
    </section>

    <?php if ($pageError !== ''): ?>
        <div class="page-error"><?php echo e($pageError); ?></div>
    <?php endif; ?>

    <section class="section" id="trending">
        <div class="section-head">
            <div>
                <p class="section-kicker">Customer favorites</p>
                <h2 class="section-title">Trending Products</h2>
                <p class="section-description">A considered collection of customer favorites and timeless designs.</p>
            </div>
            <a class="text-link" href="#collection">Shop all products ↗</a>
        </div>
        <?php if (!empty($trendingProducts)): ?>
            <div class="product-grid">
                <?php foreach ($trendingProducts as $product): ?>
                    <article class="product-card">
                        <div class="product-image-wrap">
                            <img class="product-image" src="<?php echo e($product['image']); ?>" alt="<?php echo e($product['name']); ?>" loading="lazy" onclick="openImageModal(this.src, this.alt)" onerror="this.onerror=null;this.src='https://placehold.co/800x600/f1e5d5/4a2b1c?text=Guru+Woodworks';">
                            <span class="stock-tag"><?php echo (int)$product['stock'] > 0 ? 'In stock' : 'Made to order'; ?></span>
                        </div>
                        <div class="product-info">
                            <p class="product-type"><?php echo e(ucwords(str_replace('_', ' ', (string)$product['wood_type']))); ?> craftsmanship</p>
                            <h3 class="product-name"><?php echo e($product['name']); ?></h3>
                            <p class="product-price">₹<?php echo number_format((float)$product['price'], 2); ?></p>
                            <div class="product-actions">
                                <button class="button" type="button" onclick="promptProductOptions(<?php echo (int)$product['id']; ?>, <?php echo json_encode((string)$product['name'], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>, <?php echo (float)$product['price']; ?>, <?php echo (int)$product['has_sizes']; ?>, <?php echo json_encode((string)$product['wood_type'], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>)" <?php echo (int)$product['stock'] <= 0 ? 'disabled' : ''; ?>>Add to Cart</button>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">Our featured collection is being updated. Please check back soon.</div>
        <?php endif; ?>
    </section>

    <section class="section" id="collection">
        <div class="section-head">
            <div>
                <p class="section-kicker">Crafted for your home</p>
                <h2 class="section-title">Explore the Collection</h2>
                <p class="section-description">Discover furniture made for everyday living and years of use.</p>
            </div>
        </div>
        <?php if (!empty($products)): ?>
            <div class="product-grid">
                <?php foreach ($products as $product): ?>
                    <article class="product-card">
                        <div class="product-image-wrap">
                            <img class="product-image" src="<?php echo e($product['image']); ?>" alt="<?php echo e($product['name']); ?>" loading="lazy" onclick="openImageModal(this.src, this.alt)" onerror="this.onerror=null;this.src='https://placehold.co/800x600/f1e5d5/4a2b1c?text=Guru+Woodworks';">
                            <span class="stock-tag"><?php echo (int)$product['stock'] > 0 ? 'In stock · ' . (int)$product['stock'] . ' available' : 'Out of stock'; ?></span>
                        </div>
                        <div class="product-info">
                            <p class="product-type"><?php echo e(ucwords(str_replace('_', ' ', (string)$product['wood_type']))); ?> craftsmanship</p>
                            <h3 class="product-name"><?php echo e($product['name']); ?></h3>
                            <p class="product-rating"><span class="stars" aria-label="<?php echo (float)$product['avg_rating']; ?> out of 5 stars">★★★★★</span> <?php echo number_format((float)$product['avg_rating'], 1); ?> (<?php echo (int)$product['total_ratings']; ?> reviews)</p>
                            <p class="product-price">₹<?php echo number_format((float)$product['price'], 2); ?></p>
                            <?php if ((int)$product['has_sizes'] === 1): ?><p class="product-rating">Available in 5 × 6 and 6 × 6 sizes</p><?php endif; ?>
                            <div class="product-actions">
                                <button class="button" type="button" onclick="promptProductOptions(<?php echo (int)$product['id']; ?>, <?php echo json_encode((string)$product['name'], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>, <?php echo (float)$product['price']; ?>, <?php echo (int)$product['has_sizes']; ?>, <?php echo json_encode((string)$product['wood_type'], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>)" <?php echo (int)$product['stock'] <= 0 ? 'disabled' : ''; ?>>Add to Cart</button>
                                <?php if ($isLoggedIn): ?>
                                    <div class="rating-form">
                                        <label class="sr-only" for="rating-<?php echo (int)$product['id']; ?>">Rate <?php echo e($product['name']); ?></label>
                                        <select id="rating-<?php echo (int)$product['id']; ?>" aria-label="Rate product">
                                            <?php for ($star = 5; $star >= 1; $star--): ?>
                                                <option value="<?php echo $star; ?>" <?php echo (int)($product['my_rating'] ?? 0) === $star ? 'selected' : ''; ?>><?php echo $star; ?> ★</option>
                                            <?php endfor; ?>
                                        </select>
                                        <button class="button button-outline" type="button" onclick="submitRating(<?php echo (int)$product['id']; ?>)">Rate</button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">No products are currently available. Please check back soon.</div>
        <?php endif; ?>

        <div class="benefits">
            <div class="benefit"><strong>Made to last</strong><span>Timeless forms and carefully selected materials.</span></div>
            <div class="benefit"><strong>Thoughtful craftsmanship</strong><span>Details that bring warmth to your everyday spaces.</span></div>
            <div class="benefit"><strong>Personalized choices</strong><span>Choose available wood and size options for selected products.</span></div>
        </div>
    </section>

    <section class="cart-callout">
        <div><h2>Your next favorite piece is waiting.</h2><p>Review your selections whenever you're ready.</p></div>
        <a class="button button-light" href="cart.php">View Shopping Cart (<?php echo $cartCount; ?>)</a>
    </section>
</main>

<footer class="site-footer">
    <p>© <?php echo date('Y'); ?> Guru Woodworks. Crafted for your home.</p>
    <p>Handcrafted furniture · Timeless materials · Thoughtful design</p>
</footer>

<div class="image-modal" id="image-modal" role="dialog" aria-modal="true" aria-label="Product image preview" onclick="if(event.target===this) closeImageModal()">
    <button class="close-btn" type="button" aria-label="Close image preview" onclick="closeImageModal()">&times;</button>
    <img class="full-image" id="full-image" src="" alt="">
</div>

<script>
    window.IS_USER_LOGGED_IN = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
    function submitRating(productId) {
        const select = document.getElementById('rating-' + productId);
        if (!select) return;
        fetch('api/rate_product.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({product_id: productId, rating: Number(select.value)})
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                alert(data.message || 'Thank you for your rating!');
                window.location.reload();
            } else {
                alert(data.message || 'Could not save your rating. Please try again.');
            }
        })
        .catch(() => alert('Unable to submit your rating right now.'));
    }
    function openImageModal(src, alt) {
        const modal = document.getElementById('image-modal');
        const image = document.getElementById('full-image');
        image.src = src;
        image.alt = alt || 'Product image';
        modal.classList.add('open');
    }
    function closeImageModal() {
        document.getElementById('image-modal').classList.remove('open');
    }
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') closeImageModal();
    });
</script>
<script src="js/cart.js" defer></script>
</body>
</html>
