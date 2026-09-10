<?php
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/csrf.php";
require_once __DIR__ . "/../includes/audit.php";
require_once __DIR__ . "/../db.php";

require_admin_login();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: dashboard.php");
    exit();
}

// Fetch existing product
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    header("Location: dashboard.php?msg=" . urlencode("Product not found."));
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = "Session expired or invalid security token.";
    } else {
        $name = trim($_POST['name'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $image = trim($_POST['image'] ?? '');
        $stock = max(0, (int)($_POST['stock'] ?? 0));
        $wood_type = $_POST['wood_type'] ?? 'teakwood';
        $has_sizes = isset($_POST['has_sizes']) ? (int)$_POST['has_sizes'] : 0;

        $allowedWoods = ['teakwood', 'rosewood', 'both', 'none'];
        if (!in_array($wood_type, $allowedWoods)) {
            $wood_type = 'teakwood';
        }

        if (empty($name) || $price <= 0 || empty($image)) {
            $error = "Please fill in all fields with valid values.";
        } else {
            $upStmt = $conn->prepare("UPDATE products SET name = ?, price = ?, image = ?, stock = ?, has_sizes = ?, wood_type = ? WHERE id = ?");
            $upStmt->bind_param("sdsiisi", $name, $price, $image, $stock, $has_sizes, $wood_type, $id);

            if ($upStmt->execute()) {
                log_audit_action($conn, (int)$_SESSION['admin_id'], 'edit_product', "Updated product #{$id}: {$name} (Stock: {$stock}, Price: ₹{$price}, Wood: {$wood_type}, HasSizes: {$has_sizes})");
                $upStmt->close();
                header("Location: dashboard.php?msg=" . urlencode("Product updated successfully!"));
                exit();
            } else {
                $error = "Failed to update product.";
            }
            $upStmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product #<?php echo $id; ?> - Store Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        .form-card {
            background: #1e293b;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 35px;
            max-width: 600px;
            margin: 40px auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-size: 14px; color: #94a3b8; }
        input, select {
            width: 100%;
            padding: 12px;
            background: #0f172a;
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            color: #fff;
            outline: none;
            font-family: inherit;
        }
        input:focus, select:focus { border-color: #38bdf8; }
        .alert-error {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.4);
            color: #fca5a5;
            padding: 10px;
            border-radius: 8px;
            font-size: 13px;
            margin-bottom: 20px;
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
        <h3 style="margin-top:0; margin-bottom:20px;">✏️ Edit Product #<?php echo $id; ?></h3>

        <?php if (!empty($error)): ?>
            <div class="alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <form method="POST" action="edit_product.php?id=<?php echo $id; ?>">
            <?php echo csrf_field(); ?>
            <div class="form-group">
                <label>Product Title / Name</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>

            <div class="form-group">
                <label>Base Price in INR (₹)</label>
                <input type="number" step="0.01" name="price" value="<?php echo htmlspecialchars($product['price'], ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>

            <div class="form-group">
                <label>Wood Type / Timber Material</label>
                <select name="wood_type" required>
                    <option value="teakwood" <?php echo ($product['wood_type'] === 'teakwood') ? 'selected' : ''; ?>>Teakwood Only</option>
                    <option value="rosewood" <?php echo ($product['wood_type'] === 'rosewood') ? 'selected' : ''; ?>>Rosewood Only</option>
                    <option value="both" <?php echo ($product['wood_type'] === 'both') ? 'selected' : ''; ?>>Both Teakwood & Rosewood Available (Customer Chooses)</option>
                    <option value="none" <?php echo ($product['wood_type'] === 'none') ? 'selected' : ''; ?>>Not Wood Specific / Standard Material</option>
                </select>
            </div>

            <div class="form-group">
                <label>Dimension / Size Options</label>
                <select name="has_sizes" required>
                    <option value="0" <?php echo ((int)$product['has_sizes'] === 0) ? 'selected' : ''; ?>>Standard / Fixed Size Only (Do NOT show 5x6 or 6x6)</option>
                    <option value="1" <?php echo ((int)$product['has_sizes'] === 1) ? 'selected' : ''; ?>>Enable Bed Size Options (5 × 6 Base Price & 6 × 6 +₹8,000)</option>
                </select>
            </div>

            <div class="form-group">
                <label>Stock Level</label>
                <input type="number" name="stock" value="<?php echo (int)$product['stock']; ?>" min="0" required>
            </div>

            <div class="form-group">
                <label>Image URL</label>
                <input type="url" name="image" value="<?php echo htmlspecialchars($product['image'], ENT_QUOTES, 'UTF-8'); ?>" required>
            </div>

            <button type="submit" name="update" class="btn-primary-admin" style="width:100%; justify-content:center; padding:13px; font-size:15px; margin-top:10px;">
                Update Product Changes
            </button>
        </form>
    </div>
</div>

</body>
</html>