```php
<?php
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/csrf.php";
require_once __DIR__ . "/../includes/audit.php";
require_once __DIR__ . "/../db.php";

require_admin_login();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Session expired or invalid security token. Please try again.";
    } else {
        $name = trim($_POST['name'] ?? '');
        $price = (float) ($_POST['price'] ?? 0);
        $image = trim($_POST['image'] ?? '');
        $stock = max(0, (int) ($_POST['stock'] ?? 10));
        $wood_type = $_POST['wood_type'] ?? 'teakwood';
        $has_sizes = isset($_POST['has_sizes'])
            ? (int) $_POST['has_sizes']
            : 0;

        // New field: homepage Trending Products setting
        $is_trending = isset($_POST['is_trending']) ? 1 : 0;

        $allowedWoods = ['teakwood', 'rosewood', 'both', 'none'];

        if (!in_array($wood_type, $allowedWoods, true)) {
            $wood_type = 'teakwood';
        }

        if (!in_array($has_sizes, [0, 1], true)) {
            $has_sizes = 0;
        }

        if ($name === '' || $price <= 0 || $image === '') {
            $error = "Please fill in all fields with valid values.";
        } elseif (!filter_var($image, FILTER_VALIDATE_URL)) {
            $error = "Please provide a valid hosted image URL.";
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO products
                (name, price, image, stock, has_sizes, wood_type, is_trending)
                VALUES (?, ?, ?, ?, ?, ?, ?)"
            );

            if (!$stmt) {
                $error = "Unable to prepare the product query.";
            } else {
                $stmt->bind_param(
                    "sdsiisi",
                    $name,
                    $price,
                    $image,
                    $stock,
                    $has_sizes,
                    $wood_type,
                    $is_trending
                );

                if ($stmt->execute()) {
                    $newId = $conn->insert_id;

                    log_audit_action(
                        $conn,
                        (int) $_SESSION['admin_id'],
                        'add_product',
                        "Added product #{$newId}: {$name} " .
                        "(Stock: {$stock}, Price: ₹{$price}, " .
                        "Wood: {$wood_type}, HasSizes: {$has_sizes}, " .
                        "Trending: {$is_trending})"
                    );

                    $stmt->close();

                    header(
                        "Location: dashboard.php?msg=" .
                        urlencode("Product added successfully!")
                    );
                    exit();
                } else {
                    $error = "Failed to add product. Please try again.";
                }

                $stmt->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Add Product - Guru Woodworks Admin</title>

    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="../css/admin.css">

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
        }

        .form-card {
            background: #1e293b;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 35px;
            max-width: 650px;
            width: calc(100% - 30px);
            margin: 40px auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }

        .form-card h3 {
            color: #f8fafc;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group > label {
            display: block;
            margin-bottom: 8px;
            font-size: 14px;
            color: #cbd5e1;
        }

        .form-group input:not([type="checkbox"]),
        .form-group select {
            width: 100%;
            padding: 12px;
            background: #0f172a;
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            color: #fff;
            outline: none;
            font-family: inherit;
            font-size: 14px;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: #b88954;
        }

        .form-help {
            color: #94a3b8;
            font-size: 12px;
            display: block;
            margin-top: 6px;
            line-height: 1.6;
        }

        .trending-option {
            display: flex !important;
            align-items: flex-start;
            gap: 12px;
            padding: 16px;
            border: 1px solid rgba(184, 137, 84, 0.45);
            border-radius: 10px;
            background: rgba(184, 137, 84, 0.08);
            cursor: pointer;
        }

        .trending-option input[type="checkbox"] {
            width: 18px;
            height: 18px;
            margin-top: 3px;
            flex-shrink: 0;
            accent-color: #b88954;
            cursor: pointer;
        }

        .trending-option strong {
            display: block;
            color: #f8fafc;
            font-size: 14px;
            margin-bottom: 4px;
        }

        .trending-option small {
            display: block;
            color: #94a3b8;
            font-size: 12px;
            line-height: 1.6;
            font-weight: 400;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.4);
            color: #fca5a5;
            padding: 12px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 20px;
        }

        .btn-primary-admin {
            cursor: pointer;
        }

        @media (max-width: 600px) {
            .form-card {
                padding: 22px 18px;
                margin: 20px auto;
            }

            .admin-nav {
                flex-wrap: wrap;
                gap: 12px;
            }
        }
    </style>
</head>

<body>

<header class="admin-nav">
    <h3>🏬 Store Admin: <span>Guru Woodworks</span></h3>

    <div class="admin-links">
        <a href="dashboard.php">← Back to Dashboard</a>
        <a href="logout.php" style="color:#ef4444;">Logout</a>
    </div>
</header>

<div class="admin-container">
    <div class="form-card">

        <h3 style="margin-top:0; margin-bottom:24px;">
            ➕ Add New Woodwork Product
        </h3>

        <?php if ($error !== ''): ?>
            <div class="alert-error">
                <?php
                echo htmlspecialchars(
                    $error,
                    ENT_QUOTES,
                    'UTF-8'
                );
                ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="add_product.php">
            <?php echo csrf_field(); ?>

            <div class="form-group">
                <label for="name">Product Title / Name</label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    placeholder="e.g. Royal Teakwood King Bed"
                    maxlength="255"
                    value="<?php echo htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                    required
                    autofocus
                >
            </div>

            <div class="form-group">
                <label for="price">Base Price in INR (₹)</label>

                <input
                    type="number"
                    id="price"
                    name="price"
                    step="0.01"
                    min="0.01"
                    placeholder="e.g. 45000"
                    value="<?php echo htmlspecialchars($_POST['price'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="wood_type">Wood Type / Timber Material</label>

                <select id="wood_type" name="wood_type" required>
                    <option value="teakwood"
                        <?php echo (($_POST['wood_type'] ?? '') === 'teakwood') ? 'selected' : ''; ?>>
                        Teakwood Only
                    </option>

                    <option value="rosewood"
                        <?php echo (($_POST['wood_type'] ?? '') === 'rosewood') ? 'selected' : ''; ?>>
                        Rosewood Only
                    </option>

                    <option value="both"
                        <?php echo (($_POST['wood_type'] ?? 'both') === 'both') ? 'selected' : ''; ?>>
                        Both Teakwood & Rosewood Available
                    </option>

                    <option value="none"
                        <?php echo (($_POST['wood_type'] ?? '') === 'none') ? 'selected' : ''; ?>>
                        Standard Material / Not Wood Specific
                    </option>
                </select>

                <small class="form-help">
                    If both wood types are available, customers can choose
                    their preferred wood type when adding the product to cart.
                </small>
            </div>

            <div class="form-group">
                <label for="has_sizes">Dimension / Size Options</label>

                <select id="has_sizes" name="has_sizes" required>
                    <option value="0"
                        <?php echo (($_POST['has_sizes'] ?? '0') === '0') ? 'selected' : ''; ?>>
                        Standard / Fixed Size Only
                    </option>

                    <option value="1"
                        <?php echo (($_POST['has_sizes'] ?? '0') === '1') ? 'selected' : ''; ?>>
                        Enable Bed Size Options (5 × 6 and 6 × 6)
                    </option>
                </select>

                <small class="form-help">
                    Enable size options for beds and cots. Leave disabled for
                    chairs, dining tables, and other fixed-size furniture.
                </small>
            </div>

            <div class="form-group">
                <label for="stock">Initial Stock Units</label>

                <input
                    type="number"
                    id="stock"
                    name="stock"
                    min="0"
                    value="<?php echo htmlspecialchars($_POST['stock'] ?? '15', ENT_QUOTES, 'UTF-8'); ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="image">Hosted Image URL</label>

                <input
                    type="url"
                    id="image"
                    name="image"
                    placeholder="https://example.com/furniture.jpg"
                    value="<?php echo htmlspecialchars($_POST['image'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                    required
                >

                <small class="form-help">
                    Use a direct image URL from your image host, such as
                    Cloudinary or another permanent image-storage service.
                </small>
            </div>

            <!-- Trending Products setting -->
            <div class="form-group">
                <label>Homepage Display</label>

                <label class="trending-option">
                    <input
                        type="checkbox"
                        name="is_trending"
                        value="1"
                        <?php echo isset($_POST['is_trending']) ? 'checked' : ''; ?>
                    >

                    <span>
                        <strong>⭐ Show in Trending Products</strong>

                        <small>
                            Display this product in the Trending Products
                            section on your customer homepage.
                        </small>
                    </span>
                </label>
            </div>

            <button
                type="submit"
                name="add"
                class="btn-primary-admin"
                style="width:100%; justify-content:center; padding:13px; font-size:15px; margin-top:10px;"
            >
                Save & Publish Product
            </button>
        </form>
    </div>
</div>

</body>
</html>
```
