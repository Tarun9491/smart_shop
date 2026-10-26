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

    <!-- TRENDING PRODUCTS: controlled from the admin product form -->
    <section class="trending-section" id="trending">
        <div class="trending-heading">
            <p class="trending-eyebrow">CURATED FOR YOUR HOME</p>
            <h2>Trending Products</h2>
            <p>Explore customer favorites, crafted with timeless materials.</p>
        </div>

        <div class="trending-grid">
            <?php if (!empty($trendingProducts)): ?>
                <?php foreach ($trendingProducts as $trend): ?>
                    <article class="trending-card">
                        <div class="trending-image-wrap">
                            <img
                                class="trending-image"
                                src="<?php echo htmlspecialchars($trend['image'], ENT_QUOTES, 'UTF-8'); ?>"
                                alt="<?php echo htmlspecialchars($trend['name'], ENT_QUOTES, 'UTF-8'); ?>"
                                loading="lazy"
                                onclick="openImage(this.src)"
                            >
                            <span class="trending-ribbon">TRENDING</span>
                        </div>

                        <div class="trending-content">
                            <h3><?php echo htmlspecialchars($trend['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p class="trending-wood">
                                <?php
                                $woodLabels = [
                                    'teakwood' => 'Teakwood',
                                    'rosewood' => 'Rosewood',
                                    'both' => 'Teakwood & Rosewood available',
                                    'none' => 'Standard material'
                                ];
                                echo htmlspecialchars(
                                    $woodLabels[$trend['wood_type']] ?? 'Furniture',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );
                                ?>
                            </p>
                            <p class="trending-price">₹ <?php echo number_format((float)$trend['price'], 2); ?></p>

                            <?php if ((int)$trend['stock'] > 0): ?>
                                <p class="trending-stock">In stock · <?php echo (int)$trend['stock']; ?> available</p>
                            <?php else: ?>
                                <p class="trending-stock out">Currently out of stock</p>
                            <?php endif; ?>

                            <button
                                class="trending-add-button"
                                onclick="promptProductOptions(<?php
                                    echo (int)$trend['id'] . ', ' .
                                        json_encode($trend['name'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) . ', ' .
                                        (float)$trend['price'] . ', ' .
                                        (int)$trend['has_sizes'] . ', ' .
                                        json_encode($trend['wood_type'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
                                ?>)"
                                <?php echo ((int)$trend['stock'] <= 0) ? 'disabled' : ''; ?>
                            >
                                <?php echo ((int)$trend['stock'] > 0) ? 'Add to Cart' : 'Sold Out'; ?>
                            </button>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="trending-empty">
                    Our featured collection is being updated. Please check back soon.
                </p>
            <?php endif; ?>
        </div>
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
