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
        $price = (float)($_POST['price'] ?? 0);
        $image = trim($_POST['image'] ?? '');
        $stock = max(0, (int)($_POST['stock'] ?? 10));
        $wood_type = $_POST['wood_type'] ?? 'teakwood';
        $has_sizes = isset($_POST['has_sizes']) ? (int)$_POST['has_sizes'] : 0;

        $allowedWoods = ['teakwood', 'rosewood', 'both', 'none'];
        if (!in_array($wood_type, $allowedWoods)) {
            $wood_type = 'teakwood';
        }

        if (empty($name) || $price <= 0 || empty($image)) {
            $error = "Please fill in all fields with valid values.";
        } else {
            $stmt = $conn->prepare("INSERT INTO products (name, price, image, stock, has_sizes, wood_type) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sdsiis", $name, $price, $image, $stock, $has_sizes, $wood_type);

            if ($stmt->execute()) {
                $newId = $conn->insert_id;
                log_audit_action($conn, (int)$_SESSION['admin_id'], 'add_product', "Added product #{$newId}: {$name} (Stock: {$stock}, Price: ₹{$price}, Wood: {$wood_type}, HasSizes: {$has_sizes})");
                $stmt->close();
                header("Location: dashboard.php?msg=" . urlencode("Product added successfully!"));
                exit();
            } else {
                $error = "Failed to add product. Please check inputs.";
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - Store Admin</title>
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
        <h3 style="margin-top:0; margin-bottom:20px;">➕ Add New Woodwork Product</h3>

        <?php if (!empty($error)): ?>
            <div class="alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <form method="POST" action="add_product.php">
            <?php echo csrf_field(); ?>
            <div class="form-group">
                <label>Product Title / Name</label>
                <input type="text" name="name" placeholder="e.g. Royal Teakwood King Bed" required autofocus>
            </div>

            <div class="form-group">
                <label>Base Price in INR (₹)</label>
                <input type="number" step="0.01" name="price" placeholder="e.g. 45000" required>
            </div>

            <div class="form-group">
                <label>Wood Type / Timber Material</label>
                <select name="wood_type" required>
                    <option value="teakwood">Teakwood Only (Guaranteed Teakwood)</option>
                    <option value="rosewood">Rosewood Only (Guaranteed Rosewood)</option>
                    <option value="both" selected>Both Teakwood & Rosewood Available (Customer Chooses)</option>
                    <option value="none">Not Wood Specific / Standard Material</option>
                </select>
                <small style="color:#64748b; font-size:12px; display:block; margin-top:4px;">
                    Select the timber variant. If "Both", customer will be prompted to pick their preferred wood type when adding to cart.
                </small>
            </div>

            <div class="form-group">
                <label>Dimension / Size Options</label>
                <select name="has_sizes" required>
                    <option value="0">Standard / Fixed Size Only (Do NOT show 5x6 or 6x6)</option>
                    <option value="1" selected>Enable Bed Size Options (5 × 6 Base Price & 6 × 6 +₹8,000)</option>
                </select>
                <small style="color:#64748b; font-size:12px; display:block; margin-top:4px;">
                    Enable 5x6 and 6x6 options only for beds/cots. Leave disabled for chairs, dining tables, etc.
                </small>
            </div>

            <div class="form-group">
                <label>Initial Stock Units</label>
                <input type="number" name="stock" value="15" min="0" required>
            </div>

            <div class="form-group">
                <label>Hosted Image URL</label>
                <input type="url" name="image" placeholder="https://images.unsplash.com/..." required>
                <small style="color:#64748b; font-size:12px; display:block; margin-top:4px;">
                    Provide a direct hosted image URL (e.g. Unsplash, Cloudinary). Render disks are ephemeral.
                </small>
            </div>

            <button type="submit" name="add" class="btn-primary-admin" style="width:100%; justify-content:center; padding:13px; font-size:15px; margin-top:10px;">
                Save & Publish Product
            </button>
        </form>
    </div>
</div>

</body>
</html>