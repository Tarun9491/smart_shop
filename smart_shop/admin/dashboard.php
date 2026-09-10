<?php
require_once __DIR__ . "/../includes/session.php";
require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/csrf.php";
require_once __DIR__ . "/../db.php";

require_admin_login();

// Metrics
$prodCount = $conn->query("SELECT COUNT(*) as c FROM products")->fetch_assoc()['c'] ?? 0;
$lowStock = $conn->query("SELECT COUNT(*) as c FROM products WHERE stock < 5")->fetch_assoc()['c'] ?? 0;
$pendingOrders = $conn->query("SELECT COUNT(*) as c FROM orders WHERE status = 'pending'")->fetch_assoc()['c'] ?? 0;
$totalOrders = $conn->query("SELECT COUNT(*) as c FROM orders")->fetch_assoc()['c'] ?? 0;

// Fetch products
$products = $conn->query("SELECT * FROM products ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Store Admin Dashboard - Guru Woodworks</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body>

<header class="admin-nav">
    <h3>🏬 Store Admin: <span>Guru Woodworks</span></h3>
    <div class="admin-links">
        <a href="dashboard.php" class="active">Products</a>
        <a href="orders.php">Orders (<?php echo (int)$pendingOrders; ?>)</a>
        <a href="add_product.php" class="btn-primary-admin">+ Add Product</a>
        <a href="logout.php" style="color:#ef4444;">Logout</a>
    </div>
</header>

<div class="admin-container">
    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-label">Total Products</div>
            <div class="stat-value"><?php echo (int)$prodCount; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Low Stock Alert (&lt; 5)</div>
            <div class="stat-value" style="color:<?php echo $lowStock > 0 ? '#f87171' : '#34d399'; ?>">
                <?php echo (int)$lowStock; ?>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Pending Orders</div>
            <div class="stat-value" style="color:#fbbf24;"><?php echo (int)$pendingOrders; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-label">Total Lifetime Orders</div>
            <div class="stat-value"><?php echo (int)$totalOrders; ?></div>
        </div>
    </div>

    <?php if (isset($_GET['msg'])): ?>
        <div style="background:rgba(16,185,129,0.2); border:1px solid #10b981; color:#34d399; padding:12px 18px; border-radius:10px; margin-bottom:20px;">
            <?php echo htmlspecialchars($_GET['msg'], ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <div class="panel-card">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h3 style="margin:0;">Product Catalog Management</h3>
            <a href="add_product.php" class="btn-primary-admin">+ New Product</a>
        </div>

        <div style="overflow-x:auto;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th style="width:60px;">Image</th>
                        <th>Product Name</th>
                        <th>Price (₹)</th>
                        <th>Stock Level</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($products)): ?>
                        <?php foreach ($products as $p): ?>
                            <tr>
                                <td>
                                    <img src="<?php echo htmlspecialchars($p['image'], ENT_QUOTES, 'UTF-8'); ?>" 
                                         alt="" 
                                         style="width:50px; height:50px; object-fit:cover; border-radius:8px;">
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <div style="color:#64748b; font-size:12px;">ID: #<?php echo (int)$p['id']; ?></div>
                                </td>
                                <td>₹ <?php echo number_format((float)$p['price'], 2); ?></td>
                                <td>
                                    <?php if ((int)$p['stock'] < 5): ?>
                                        <span class="badge-status badge-disabled"><?php echo (int)$p['stock']; ?> left (Low)</span>
                                    <?php else: ?>
                                        <span class="badge-status badge-active"><?php echo (int)$p['stock']; ?> in stock</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align:right;">
                                    <a href="edit_product.php?id=<?php echo (int)$p['id']; ?>" 
                                       style="color:#38bdf8; text-decoration:none; margin-right:12px; font-weight:500;">Edit</a>
                                    
                                    <form action="delete_product.php" method="POST" style="display:inline;" onsubmit="return confirm('Delete this product?');">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                                        <button type="submit" style="background:none; border:none; color:#ef4444; cursor:pointer; font-family:inherit; font-size:14px;">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align:center; padding:30px; color:#94a3b8;">
                                No products found. Click "+ Add Product" to create your first item.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

</body>
</html>