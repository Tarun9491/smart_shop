```php
<?php
// admin/dashboard.php

require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../db.php';

require_admin_login();

// Dashboard metrics
$prodCount = 0;
$lowStock = 0;
$pendingOrders = 0;
$totalOrders = 0;
$trendingCount = 0;
$products = [];
$dbError = '';

try {
    $result = $conn->query("SELECT COUNT(*) AS c FROM products");
    $prodCount = (int) ($result->fetch_assoc()['c'] ?? 0);

    $result = $conn->query(
        "SELECT COUNT(*) AS c FROM products WHERE stock < 5"
    );
    $lowStock = (int) ($result->fetch_assoc()['c'] ?? 0);

    $result = $conn->query(
        "SELECT COUNT(*) AS c FROM orders WHERE status = 'pending'"
    );
    $pendingOrders = (int) ($result->fetch_assoc()['c'] ?? 0);

    $result = $conn->query("SELECT COUNT(*) AS c FROM orders");
    $totalOrders = (int) ($result->fetch_assoc()['c'] ?? 0);

    // Count products marked as trending.
    $result = $conn->query(
        "SELECT COUNT(*) AS c FROM products WHERE is_trending = 1"
    );
    $trendingCount = (int) ($result->fetch_assoc()['c'] ?? 0);

    // Fetch product catalog.
    $result = $conn->query("SELECT * FROM products ORDER BY id DESC");
    $products = $result->fetch_all(MYSQLI_ASSOC);

} catch (mysqli_sql_exception $e) {
    error_log('Admin dashboard database error: ' . $e->getMessage());
    $dbError = 'Unable to load dashboard information. Please check the database configuration.';
}

function dashboard_escape($value): string
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8'
    );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Store Admin Dashboard - Guru Woodworks</title>

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
          rel="stylesheet">
    <link rel="stylesheet" href="../css/admin.css">

    <style>
        .trending-stat {
            border: 1px solid rgba(184, 137, 84, .5);
            background: linear-gradient(135deg, rgba(184, 137, 84, .16), rgba(120, 75, 40, .08));
        }

        .trending-stat .stat-value {
            color: #d6ad78;
        }

        .trending-badge,
        .regular-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            white-space: nowrap;
        }

        .trending-badge {
            background: rgba(184, 137, 84, .15);
            border: 1px solid rgba(184, 137, 84, .4);
            color: #e7c397;
            font-weight: 600;
        }

        .regular-badge {
            background: rgba(148, 163, 184, .1);
            color: #94a3b8;
        }

        .product-name { color: #f1f5f9; }
        .product-id { color: #94a3b8; font-size: 12px; }

        .product-thumb {
            width: 54px;
            height: 54px;
            object-fit: cover;
            border-radius: 8px;
            background: #0f172a;
        }

        .dashboard-message {
            padding: 12px 18px;
            border-radius: 10px;
            margin-bottom: 20px;
            overflow-wrap: anywhere;
        }

        .dashboard-success {
            background: rgba(16, 185, 129, .15);
            border: 1px solid #10b981;
            color: #34d399;
        }

        .dashboard-error {
            background: rgba(239, 68, 68, .15);
            border: 1px solid #ef4444;
            color: #fca5a5;
        }

        .catalog-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        .catalog-heading h3 { margin: 0; }

        .catalog-help {
            color: #94a3b8;
            font-size: 13px;
            margin: 8px 0 18px;
            line-height: 1.6;
        }

        .action-links {
            text-align: right;
            white-space: nowrap;
        }

        .edit-link {
            color: #38bdf8;
            text-decoration: none;
            margin-right: 12px;
            font-weight: 500;
        }

        .delete-button {
            background: none;
            border: none;
            color: #ef4444;
            cursor: pointer;
            font-family: inherit;
            font-size: 14px;
        }

        @media (max-width: 600px) {
            .catalog-heading { align-items: flex-start; }
            .catalog-heading .btn-primary-admin {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

<header class="admin-nav">
    <h3>🏬 Store Admin: <span>Guru Woodworks</span></h3>

    <div class="admin-links">
        <a href="dashboard.php" class="active">Products</a>
        <a href="orders.php">Orders (<?= $pendingOrders ?>)</a>
        <a href="add_product.php" class="btn-primary-admin">+ Add Product</a>
        <a href="logout.php" style="color:#ef4444;">Logout</a>
    </div>
</header>

<div class="admin-container">

    <?php if ($dbError !== ''): ?>
        <div class="dashboard-message dashboard-error">
            <?= dashboard_escape($dbError) ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['msg'])): ?>
        <div class="dashboard-message dashboard-success">
            <?= dashboard_escape($_GET['msg']) ?>
        </div>
    <?php endif; ?>

    <div class="stat-grid">
        <div class="stat-card">
            <div class="stat-label">Total Products</div>
            <div class="stat-value"><?= $prodCount ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Low Stock Alert (&lt; 5)</div>
            <div class="stat-value"
                 style="color:<?= $lowStock > 0 ? '#f87171' : '#34d399' ?>;">
                <?= $lowStock ?>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Pending Orders</div>
            <div class="stat-value" style="color:#fbbf24;">
                <?= $pendingOrders ?>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Total Lifetime Orders</div>
            <div class="stat-value"><?= $totalOrders ?></div>
        </div>

        <div class="stat-card trending-stat">
            <div class="stat-label">Trending Products</div>
            <div class="stat-value"><?= $trendingCount ?></div>
        </div>
    </div>

    <div class="panel-card" style="margin-top:25px;">
        <div class="catalog-heading">
            <h3>Product Catalog Management</h3>
            <a href="add_product.php" class="btn-primary-admin">+ New Product</a>
        </div>

        <p class="catalog-help">
            Products marked ⭐ Trending appear in the Trending Products section
            on the customer homepage. Use Edit to change this setting.
        </p>

        <div style="overflow-x:auto;">
            <table class="admin-table">
                <thead>
                <tr>
                    <th style="width:70px;">Image</th>
                    <th>Product Name</th>
                    <th>Price (₹)</th>
                    <th>Stock Level</th>
                    <th>Homepage Display</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
                </thead>

                <tbody>
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td>
                                <img
                                    class="product-thumb"
                                    src="<?= dashboard_escape($p['image'] ?? '') ?>"
                                    alt="<?= dashboard_escape($p['name'] ?? 'Product') ?>"
                                    loading="lazy"
                                    onerror="this.style.visibility='hidden';"
                                >
                            </td>

                            <td>
                                <strong class="product-name">
                                    <?= dashboard_escape($p['name'] ?? '') ?>
                                </strong>
                                <div class="product-id">
                                    ID: #<?= (int) $p['id'] ?>
                                </div>
                            </td>

                            <td>₹ <?= number_format((float) $p['price'], 2) ?></td>

                            <td>
                                <?php if ((int) $p['stock'] < 5): ?>
                                    <span class="badge-status badge-disabled">
                                        <?= (int) $p['stock'] ?> left (Low)
                                    </span>
                                <?php else: ?>
                                    <span class="badge-status badge-active">
                                        <?= (int) $p['stock'] ?> in stock
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if (!empty($p['is_trending'])): ?>
                                    <span class="trending-badge">⭐ Trending</span>
                                <?php else: ?>
                                    <span class="regular-badge">Regular</span>
                                <?php endif; ?>
                            </td>

                            <td class="action-links">
                                <a
                                    href="edit_product.php?id=<?= (int) $p['id'] ?>"
                                    class="edit-link"
                                >Edit</a>

                                <form
                                    action="delete_product.php"
                                    method="POST"
                                    style="display:inline;"
                                    onsubmit="return confirm('Delete this product?');"
                                >
                                    <?= csrf_field() ?>

                                    <input
                                        type="hidden"
                                        name="id"
                                        value="<?= (int) $p['id'] ?>"
                                    >

                                    <button type="submit" class="delete-button">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6"
                            style="text-align:center;padding:30px;color:#94a3b8;">
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
```
