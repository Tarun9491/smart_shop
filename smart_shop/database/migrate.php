<?php
// database/migrate.php - One-time migration & schema sync script

require_once __DIR__ . '/../db.php';

echo "=== Smart Shop Database Migration & Password Hashing ===" . PHP_EOL;

// 1. Create / Update tables
$queries = [
    "CREATE TABLE IF NOT EXISTS `users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `email` VARCHAR(150) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS `admins` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(100) NOT NULL UNIQUE,
        `email` VARCHAR(150) NOT NULL,
        `password` VARCHAR(255) NOT NULL,
        `role` ENUM('admin', 'superadmin') NOT NULL DEFAULT 'admin',
        `is_active` TINYINT(1) NOT NULL DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS `products` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(200) NOT NULL,
        `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `image` TEXT NOT NULL,
        `stock` INT NOT NULL DEFAULT 15,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS `cart` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `product_id` INT NOT NULL,
        `qty` INT NOT NULL DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `unique_user_product` (`user_id`, `product_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS `orders` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `total` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `status` ENUM('pending', 'processing', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS `order_items` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `order_id` INT NOT NULL,
        `product_id` INT NOT NULL,
        `qty` INT NOT NULL DEFAULT 1,
        `price` DECIMAL(10,2) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS `ratings` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `product_id` INT NOT NULL,
        `rating` TINYINT NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY `unique_user_product_rating` (`user_id`, `product_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

    "CREATE TABLE IF NOT EXISTS `audit_logs` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `admin_id` INT NOT NULL,
        `action` VARCHAR(150) NOT NULL,
        `details` TEXT,
        `ip_address` VARCHAR(45) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
];

foreach ($queries as $q) {
    if (!$conn->query($q)) {
        echo "Error running table query: " . $conn->error . PHP_EOL;
    }
}

// Check if created_at column exists in users
$checkUserCreated = $conn->query("SHOW COLUMNS FROM `users` LIKE 'created_at'");
if ($checkUserCreated->num_rows == 0) {
    $conn->query("ALTER TABLE `users` ADD COLUMN `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    echo "Added created_at column to users table." . PHP_EOL;
}

// Check if stock column exists in products
$checkStock = $conn->query("SHOW COLUMNS FROM `products` LIKE 'stock'");
if ($checkStock->num_rows == 0) {
    $conn->query("ALTER TABLE `products` ADD COLUMN `stock` INT NOT NULL DEFAULT 15");
    echo "Added stock column to products table." . PHP_EOL;
}

// Check if user_id column exists in orders
$checkUser = $conn->query("SHOW COLUMNS FROM `orders` LIKE 'user_id'");
if ($checkUser->num_rows == 0) {
    $conn->query("ALTER TABLE `orders` ADD COLUMN `user_id` INT NOT NULL DEFAULT 1 AFTER `id`");
    echo "Added user_id column to orders table." . PHP_EOL;
}

// Check if status column exists in orders
$checkStatus = $conn->query("SHOW COLUMNS FROM `orders` LIKE 'status'");
if ($checkStatus->num_rows == 0) {
    $conn->query("ALTER TABLE `orders` ADD COLUMN `status` ENUM('pending', 'processing', 'completed', 'cancelled') NOT NULL DEFAULT 'pending' AFTER `total`");
    echo "Added status column to orders table." . PHP_EOL;
}

// Check if price column exists in order_items
$checkPrice = $conn->query("SHOW COLUMNS FROM `order_items` LIKE 'price'");
if ($checkPrice->num_rows == 0) {
    $conn->query("ALTER TABLE `order_items` ADD COLUMN `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `qty`");
    echo "Added price column to order_items table." . PHP_EOL;
}

// Check has_sizes and wood_type in products
$checkHasSizes = $conn->query("SHOW COLUMNS FROM `products` LIKE 'has_sizes'");
if ($checkHasSizes->num_rows == 0) {
    $conn->query("ALTER TABLE `products` ADD COLUMN `has_sizes` TINYINT(1) NOT NULL DEFAULT 0 AFTER `stock`");
    echo "Added has_sizes column to products table." . PHP_EOL;
}

$checkWoodType = $conn->query("SHOW COLUMNS FROM `products` LIKE 'wood_type'");
if ($checkWoodType->num_rows == 0) {
    $conn->query("ALTER TABLE `products` ADD COLUMN `wood_type` VARCHAR(50) NOT NULL DEFAULT 'teakwood' AFTER `has_sizes`");
    echo "Added wood_type column to products table." . PHP_EOL;
}

// Check wood_type, size, price in cart
$checkCartWood = $conn->query("SHOW COLUMNS FROM `cart` LIKE 'wood_type'");
if ($checkCartWood->num_rows == 0) {
    $conn->query("ALTER TABLE `cart` ADD COLUMN `wood_type` VARCHAR(50) DEFAULT NULL AFTER `qty`");
    $conn->query("ALTER TABLE `cart` ADD COLUMN `size` VARCHAR(50) DEFAULT NULL AFTER `wood_type`");
    $conn->query("ALTER TABLE `cart` ADD COLUMN `price` DECIMAL(10,2) DEFAULT NULL AFTER `size`");
    echo "Added variant columns to cart table." . PHP_EOL;
}

// Check wood_type, size in order_items
$checkItemWood = $conn->query("SHOW COLUMNS FROM `order_items` LIKE 'wood_type'");
if ($checkItemWood->num_rows == 0) {
    $conn->query("ALTER TABLE `order_items` ADD COLUMN `wood_type` VARCHAR(50) DEFAULT NULL AFTER `price`");
    $conn->query("ALTER TABLE `order_items` ADD COLUMN `size` VARCHAR(50) DEFAULT NULL AFTER `wood_type`");
    echo "Added variant columns to order_items table." . PHP_EOL;
}

// Update existing products with realistic variant settings
$conn->query("UPDATE products SET has_sizes = 1, wood_type = 'both' WHERE name LIKE '%Bed%' OR name LIKE '%plain%' OR name LIKE '%design%'");
$conn->query("UPDATE products SET has_sizes = 0, wood_type = 'teakwood' WHERE name LIKE '%Table%' OR name LIKE '%Wardrobe%'");
$conn->query("UPDATE products SET has_sizes = 0, wood_type = 'none' WHERE name LIKE '%Chair%'");


// Migrate any rows from legacy 'admin' table into 'admins' table if needed
$hasLegacyAdmin = $conn->query("SHOW TABLES LIKE 'admin'");
if ($hasLegacyAdmin && $hasLegacyAdmin->num_rows > 0) {
    $r = $conn->query("SELECT * FROM `admin`");
    while ($row = $r->fetch_assoc()) {
        $u = $row['username'];
        $p = $row['password'];
        $hp = password_get_info($p)['algo'] ? $p : password_hash($p, PASSWORD_DEFAULT);
        $role = ($u === 'superadmin') ? 'superadmin' : 'admin';
        $stmt = $conn->prepare("INSERT INTO `admins` (username, email, password, role, is_active) VALUES (?, ?, ?, ?, 1) ON DUPLICATE KEY UPDATE password = VALUES(password)");
        $email = $u . "@smartshop.com";
        $stmt->bind_param("ssss", $u, $email, $hp, $role);
        $stmt->execute();
        $stmt->close();
    }
}

// Ensure default superadmin exists
$checkSuper = $conn->query("SELECT id FROM `admins` WHERE `username` = 'superadmin'");
if ($checkSuper->num_rows == 0) {
    $hp = password_hash('Lakkoju@9491', PASSWORD_DEFAULT);
    $email = 'tarunlakkoju966@gmail.com';
    $role = 'superadmin';
    $u = 'superadmin';
    $stmt = $conn->prepare("INSERT INTO `admins` (username, email, password, role, is_active) VALUES (?, ?, ?, ?, 1)");
    $stmt->bind_param("ssss", $u, $email, $hp, $role);
    $stmt->execute();
    $stmt->close();
    echo "Created default superadmin account (username: superadmin / pass: Admin@123)" . PHP_EOL;
}

// Ensure default store admin exists
$checkAdmin = $conn->query("SELECT id FROM `admins` WHERE `username` = 'admin'");
if ($checkAdmin->num_rows == 0) {
    $hp = password_hash('Admin@123', PASSWORD_DEFAULT);
    $email = 'admin@smartshop.com';
    $role = 'admin';
    $u = 'admin';
    $stmt = $conn->prepare("INSERT INTO `admins` (username, email, password, role, is_active) VALUES (?, ?, ?, ?, 1)");
    $stmt->bind_param("ssss", $u, $email, $hp, $role);
    $stmt->execute();
    $stmt->close();
    echo "Created default store admin account (username: admin / pass: Admin@123)" . PHP_EOL;
}

// 2. Migrate plaintext passwords in users table
$res = $conn->query("SELECT id, password FROM `users`");
$migratedUsers = 0;
while ($row = $res->fetch_assoc()) {
    $p = $row['password'];
    $info = password_get_info($p);
    if ($info['algo'] === 0 || empty($info['algo'])) {
        // Plaintext password detected - hash it!
        $hashed = password_hash($p, PASSWORD_DEFAULT);
        $uStmt = $conn->prepare("UPDATE `users` SET `password` = ? WHERE `id` = ?");
        $uStmt->bind_param("si", $hashed, $row['id']);
        $uStmt->execute();
        $uStmt->close();
        $migratedUsers++;
    }
}
echo "Migrated $migratedUsers plaintext user password(s) to secure bcrypt hashes." . PHP_EOL;

// 3. Migrate plaintext passwords in admins table
$res = $conn->query("SELECT id, password FROM `admins`");
$migratedAdmins = 0;
while ($row = $res->fetch_assoc()) {
    $p = $row['password'];
    $info = password_get_info($p);
    if ($info['algo'] === 0 || empty($info['algo'])) {
        $hashed = password_hash($p, PASSWORD_DEFAULT);
        $uStmt = $conn->prepare("UPDATE `admins` SET `password` = ? WHERE `id` = ?");
        $uStmt->bind_param("si", $hashed, $row['id']);
        $uStmt->execute();
        $uStmt->close();
        $migratedAdmins++;
    }
}
echo "Migrated $migratedAdmins plaintext admin password(s) to secure bcrypt hashes." . PHP_EOL;

echo "=== Migration Finished Successfully! ===" . PHP_EOL;
