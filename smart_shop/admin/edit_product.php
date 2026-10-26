```php
<?php
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/csrf.php";
require_once __DIR__ . "/../includes/audit.php";
require_once __DIR__ . "/../db.php";

require_admin_login();

$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: dashboard.php");
    exit();
}

// Fetch existing product
$stmt = $conn->prepare(
    "SELECT * FROM products WHERE id = ? LIMIT 1"
);

$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    header(
        "Location: dashboard.php?msg=" .
        urlencode("Product not found.")
    );
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['update'])) {

    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Session expired or invalid security token. Please try again.";
    } else {
        $name = trim($_POST['name'] ?? '');
        $price = (float) ($_POST['price'] ?? 0);
        $image = trim($_POST['image'] ?? '');
        $stock = max(0, (int) ($_POST['stock'] ?? 0));

        $wood_type = $_POST['wood_type'] ?? 'teakwood';
        $has_sizes = isset($_POST['has_sizes'])
            ? (int) $_POST['has_sizes']
            : 0;

        // Trending Products setting
        $is_trending = isset($_POST['is_trending']) ? 1 : 0;

        // Validate wood type
        $allowedWoods = [
            'teakwood',
            'rosewood',
            'both',
            'none'
        ];

        if (!in_array($wood_type, $allowedWoods, true)) {
            $wood_type = 'teakwood';
        }

        // Validate size setting
        if (!in_array($has_sizes, [0, 1], true)) {
            $has_sizes = 0;
        }

        if ($name === '' || $price <= 0 || $image === '') {
            $error = "Please fill in all fields with valid values.";
        } elseif (!filter_var($image, FILTER_VALIDATE_URL)) {
            $error = "Please enter a valid image URL.";
        } else {
            $upStmt = $conn->prepare(
                "UPDATE products
                 SET name = ?,
                     price = ?,
                     image = ?,
                     stock = ?,
                     has_sizes = ?,
                     wood_type = ?,
                     is_trending = ?
                 WHERE id = ?"
            );

            if (!$upStmt) {
                $error = "Unable to prepare the product update.";
            } else {
                $upStmt->bind_param(
                    "sdsiisii",
                    $name,
                    $price,
                    $image,
                    $stock,
                    $has_sizes,
                    $wood_type,
                    $is_trending,
                    $id
                );

                if ($upStmt->execute()) {
                    log_audit_action(
                        $conn,
                        (int) $_SESSION['admin_id'],
                        'edit_product',
                        "Updated product #{$id}: {$name} " .
                        "(Stock: {$stock}, Price: ₹{$price}, " .
                        "Wood: {$wood_type}, HasSizes: {$has_sizes}, " .
                        "Trending: {$is_trending})"
                    );

                    $upStmt->close();

                    header(
                        "Location: dashboard.php?msg=" .
                        urlencode("Product updated successfully!")
                    );
                    exit();
                } else {
                    $error = "Failed to update product. Please try again.";
                }

                $upStmt->close();
            }
        }

        // Preserve submitted values if validation or saving fails
        $product['name'] = $name;
        $product['price'] = $price;
        $product['image'] = $image;
        $product['stock'] = $stock;
        $product['wood_type'] = $wood_type;
        $product['has_sizes'] = $has_sizes;
        $product['is_trending'] = $is_trending;
    }
}

function escapeHtml($value) {
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Edit Product #<?php echo (int) $id; ?>
        - Guru Woodworks Admin
    </title>

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

        .current-image {
            width: 120px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            margin-top: 10px;
            border: 1px solid rgba(255, 255, 255, 0.15);
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
            ✏️ Edit Product #<?php echo (int) $id; ?>
        </h3>

        <?php if ($error !== ''): ?>
            <div class="alert-error">
                <?php echo escapeHtml($error); ?>
            </div>
        <?php endif; ?>

        <form
            method="POST"
            action="edit_product.php?id=<?php echo (int) $id; ?>"
        >
            <?php echo csrf_field(); ?>

            <div class="form-group">
                <label for="name">Product Title / Name</label>

                <input
                    type="text"
                    id="name"
                    name="name"
                    maxlength="255"
                    value="<?php echo escapeHtml($product['name']); ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="price">Base Price in INR (₹)</label>

                <input
                    type="number"
                    id="price"
                    name="price"
                    min="0.01"
                    step="0.01"
                    value="<?php echo escapeHtml($product['price']); ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="wood_type">Wood Type / Timber Material</label>

                <select id="wood_type" name="wood_type" required>
                    <option value="teakwood"
                        <?php echo ($product['wood_type'] === 'teakwood') ? 'selected' : ''; ?>>
                        Teakwood Only
                    </option>

                    <option value="rosewood"
                        <?php echo ($product['wood_type'] === 'rosewood') ? 'selected' : ''; ?>>
                        Rosewood Only
                    </option>

                    <option value="both"
                        <?php echo ($product['wood_type'] === 'both') ? 'selected' : ''; ?>>
                        Both Teakwood & Rosewood Available
                    </option>

                    <option value="none"
                        <?php echo ($product['wood_type'] === 'none') ? 'selected' : ''; ?>>
                        Standard Material / Not Wood Specific
                    </option>
                </select>
            </div>

            <div class="form-group">
                <label for="has_sizes">Dimension / Size Options</label>

                <select id="has_sizes" name="has_sizes" required>
                    <option value="0"
                        <?php echo ((int) $product['has_sizes'] === 0) ? 'selected' : ''; ?>>
                        Standard / Fixed Size Only
                    </option>

                    <option value="1"
                        <?php echo ((int) $product['has_sizes'] === 1) ? 'selected' : ''; ?>>
                        Enable Bed Size Options (5 × 6 and 6 × 6)
                    </option>
                </select>

                <small class="form-help">
                    Enable size options for beds and cots only.
                </small>
            </div>

            <div class="form-group">
                <label for="stock">Stock Level</label>

                <input
                    type="number"
                    id="stock"
                    name="stock"
                    min="0"
                    value="<?php echo (int) $product['stock']; ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label for="image">Image URL</label>

                <input
                    type="url"
                    id="image"
                    name="image"
                    value="<?php echo escapeHtml($product['image']); ?>"
                    required
                >

                <small class="form-help">
                    Enter a direct, publicly accessible product image URL.
                </small>

                <?php if (!empty($product['image'])): ?>
                    <img
                        class="current-image"
                        src="<?php echo escapeHtml($product['image']); ?>"
                        alt="Current product image"
                        onerror="this.style.display='none';"
                    >
                <?php endif; ?>
            </div>

            <!-- Trending Products setting -->
            <div class="form-group">
                <label>Homepage Display</label>

                <label class="trending-option">
                    <input
                        type="checkbox"
                        name="is_trending"
                        value="1"
                        <?php echo !empty($product['is_trending']) ? 'checked' : ''; ?>
                    >

                    <span>
                        <strong>⭐ Show in Trending Products</strong>

                        <small>
                            Enable this to feature the product in the
                            Trending Products section on your homepage.
                            Uncheck it to remove the product from that section.
                        </small>
                    </span>
                </label>
            </div>

            <button
                type="submit"
                name="update"
                class="btn-primary-admin"
                style="width:100%; justify-content:center; padding:13px; font-size:15px; margin-top:10px;"
            >
                Save Product Changes
            </button>

        </form>
    </div>
</div>

</body>
</html>
```
