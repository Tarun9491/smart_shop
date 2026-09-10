<?php
// scratch/verify_all.php - Stream-based automated test suite (no curl extension required)

echo "=== GURU WOODWORKS / SMART SHOP - AUTOMATED VERIFICATION ===" . PHP_EOL;

$baseUrl = 'http://127.0.0.1:8000';
$passed = 0;
$failed = 0;

function runTest($name, $closure) {
    global $passed, $failed;
    echo "Testing: {$name}... ";
    try {
        $result = $closure();
        if ($result === true) {
            echo "PASSED [OK]" . PHP_EOL;
            $passed++;
        } else {
            echo "FAILED: {$result}" . PHP_EOL;
            $failed++;
        }
    } catch (Throwable $e) {
        echo "FAILED WITH EXCEPTION: " . $e->getMessage() . PHP_EOL;
        $failed++;
    }
}

function makeRequest($url, $method = 'GET', $data = null, &$cookies = [], $headers = []) {
    $opts = [
        'http' => [
            'method' => $method,
            'ignore_errors' => true,
            'follow_location' => 0
        ]
    ];

    $hdr = [];
    if (!empty($cookies)) {
        $cookieParts = [];
        foreach ($cookies as $k => $v) {
            $cookieParts[] = "{$k}={$v}";
        }
        $hdr[] = "Cookie: " . implode('; ', $cookieParts);
    }

    if ($data !== null) {
        if (is_array($data)) {
            $content = http_build_query($data);
            $hdr[] = "Content-Type: application/x-www-form-urlencoded";
        } else {
            $content = $data;
            $hdr[] = "Content-Type: application/json";
        }
        $opts['http']['content'] = $content;
    }

    foreach ($headers as $h) {
        $hdr[] = $h;
    }

    $opts['http']['header'] = implode("\r\n", $hdr);
    $ctx = stream_context_create($opts);

    $body = @file_get_contents($url, false, $ctx);
    $resHeaders = $http_response_header ?? [];

    $statusCode = 0;
    $location = '';

    foreach ($resHeaders as $headerLine) {
        if (preg_match('#^HTTP/\d\.\d\s+(\d+)#', $headerLine, $m)) {
            $statusCode = (int)$m[1];
        }
        if (stripos($headerLine, 'Location:') === 0) {
            $location = trim(substr($headerLine, 9));
        }
        if (stripos($headerLine, 'Set-Cookie:') === 0) {
            $cookieLine = trim(substr($headerLine, 11));
            $parts = explode(';', $cookieLine);
            $kv = explode('=', $parts[0], 2);
            if (count($kv) === 2) {
                $cookies[trim($kv[0])] = trim($kv[1]);
            }
        }
    }

    return [
        'status' => $statusCode,
        'body' => $body,
        'location' => $location,
        'headers' => $resHeaders
    ];
}

// 1. Health check
runTest("Health Check Endpoint (/healthz.php)", function() use ($baseUrl) {
    $cookies = [];
    $res = makeRequest("{$baseUrl}/healthz.php", 'GET', null, $cookies);
    return ($res['status'] === 200 && trim($res['body']) === 'OK') ? true : "Got status {$res['status']} with body: {$res['body']}";
});

// 2. Customer public storefront access & guest buttons
runTest("Public Storefront Access (index.php directly accessible with Login & Create Account)", function() use ($baseUrl) {
    $cookies = [];
    $res = makeRequest("{$baseUrl}/index.php", 'GET', null, $cookies);
    $ok = ($res['status'] === 200 
           && str_contains($res['body'], 'Guru Woodworks') 
           && str_contains($res['body'], 'nav-btn-login') 
           && str_contains($res['body'], 'nav-btn-register'));
    return $ok ? true : "Expected 200 with Login and Create Account buttons, got status {$res['status']}";
});

// 3. Store admin auth redirect
runTest("Store Admin Auth Guard (admin/dashboard.php -> admin/login.php)", function() use ($baseUrl) {
    $cookies = [];
    $res = makeRequest("{$baseUrl}/admin/dashboard.php", 'GET', null, $cookies);
    return ($res['status'] === 302 && str_contains($res['location'], 'login.php')) ? true : "Expected 302 to admin/login.php, got {$res['status']} -> {$res['location']}";
});

// 4. Super admin auth redirect
runTest("Super Admin Auth Guard (superadmin/dashboard.php -> superadmin/login.php)", function() use ($baseUrl) {
    $cookies = [];
    $res = makeRequest("{$baseUrl}/superadmin/dashboard.php", 'GET', null, $cookies);
    return ($res['status'] === 302 && str_contains($res['location'], 'login.php')) ? true : "Expected 302 to superadmin/login.php, got {$res['status']} -> {$res['location']}";
});

// 5. Store Admin login test
runTest("Store Admin Authentication (admin / Admin@123)", function() use ($baseUrl) {
    $cookies = [];
    $loginPage = makeRequest("{$baseUrl}/admin/login.php", 'GET', null, $cookies);
    preg_match('/name="csrf_token"\s+value="([^"]+)"/', $loginPage['body'], $matches);
    $csrf = $matches[1] ?? '';
    if (empty($csrf)) return "Could not find CSRF token on store admin login page";

    $postRes = makeRequest("{$baseUrl}/admin/login.php", 'POST', [
        'login' => '1',
        'username' => 'admin',
        'password' => 'Admin@123',
        'csrf_token' => $csrf
    ], $cookies);

    return ($postRes['status'] === 302 && str_contains($postRes['location'], 'dashboard.php')) ? true : "Admin login failed with status {$postRes['status']} -> {$postRes['location']}";
});

// 6. Super Admin login test
runTest("Super Admin Authentication (superadmin / Admin@123)", function() use ($baseUrl) {
    $cookies = [];
    $loginPage = makeRequest("{$baseUrl}/superadmin/login.php", 'GET', null, $cookies);
    preg_match('/name="csrf_token"\s+value="([^"]+)"/', $loginPage['body'], $matches);
    $csrf = $matches[1] ?? '';
    if (empty($csrf)) return "Could not find CSRF token on superadmin login page";

    $postRes = makeRequest("{$baseUrl}/superadmin/login.php", 'POST', [
        'login' => '1',
        'username' => 'superadmin',
        'password' => 'Admin@123',
        'csrf_token' => $csrf
    ], $cookies);

    return ($postRes['status'] === 302 && str_contains($postRes['location'], 'dashboard.php')) ? true : "Superadmin login failed with status {$postRes['status']} -> {$postRes['location']}";
});

// 7. Customer workflow: Login -> Rating -> Add Cart -> Update Qty -> Transactional Checkout
runTest("Customer Login, Star Rating, Cart & Order Flow", function() use ($baseUrl) {
    $cookies = [];
    $loginPage = makeRequest("{$baseUrl}/login.php", 'GET', null, $cookies);
    preg_match('/name="csrf_token"\s+value="([^"]+)"/', $loginPage['body'], $matches);
    $csrf = $matches[1] ?? '';
    if (empty($csrf)) return "Customer login CSRF token not found";

    $postRes = makeRequest("{$baseUrl}/login.php", 'POST', [
        'login' => '1',
        'email' => 'tarunlakkoju3@gmail.com',
        'password' => 'Tarun@123',
        'csrf_token' => $csrf
    ], $cookies);

    if ($postRes['status'] !== 302) return "Customer login failed with status {$postRes['status']}";

    // Rate product 6
    $rateRes = makeRequest("{$baseUrl}/api/rate_product.php", 'POST', json_encode([
        'product_id' => 6,
        'rating' => 5
    ]), $cookies);
    $rateJson = json_decode($rateRes['body'], true);
    if (($rateJson['status'] ?? '') !== 'success') {
        return "Rating API returned failure: {$rateRes['body']}";
    }

    // Add product 6 to cart
    $addRes = makeRequest("{$baseUrl}/api/add_cart.php", 'POST', json_encode([
        'product_id' => 6,
        'qty' => 1
    ]), $cookies);
    $addJson = json_decode($addRes['body'], true);
    if (($addJson['status'] ?? '') !== 'success') {
        return "Add to cart returned failure: {$addRes['body']}";
    }

    // Update quantity for product 6 to 2
    $qtyRes = makeRequest("{$baseUrl}/api/update_qty.php", 'POST', json_encode([
        'product_id' => 6,
        'qty' => 2
    ]), $cookies);
    $qtyJson = json_decode($qtyRes['body'], true);
    if (($qtyJson['status'] ?? '') !== 'success') {
        return "Update qty returned failure: {$qtyRes['body']}";
    }

    // Fetch checkout page and CSRF
    $chkPage = makeRequest("{$baseUrl}/checkout.php", 'GET', null, $cookies);
    preg_match('/name="csrf_token"\s+value="([^"]+)"/', $chkPage['body'], $chkMatches);
    $chkCsrf = $chkMatches[1] ?? '';
    if (empty($chkCsrf)) return "Checkout CSRF token not found";

    // Place order
    $orderRes = makeRequest("{$baseUrl}/save_order.php", 'POST', [
        'csrf_token' => $chkCsrf
    ], $cookies);

    return ($orderRes['status'] === 302 && str_contains($orderRes['location'], 'payment_success.php')) ? true : "Order failed: status {$orderRes['status']} -> {$orderRes['location']}";
});

// 8. Store Admin product add, edit & order update
runTest("Store Admin Product Management & Order Processing", function() use ($baseUrl) {
    $cookies = [];
    $loginPage = makeRequest("{$baseUrl}/admin/login.php", 'GET', null, $cookies);
    preg_match('/name="csrf_token"\s+value="([^"]+)"/', $loginPage['body'], $m);
    $csrf = $m[1] ?? '';

    makeRequest("{$baseUrl}/admin/login.php", 'POST', [
        'login' => '1',
        'username' => 'admin',
        'password' => 'Admin@123',
        'csrf_token' => $csrf
    ], $cookies);

    // Get CSRF for add product
    $addPage = makeRequest("{$baseUrl}/admin/add_product.php", 'GET', null, $cookies);
    preg_match('/name="csrf_token"\s+value="([^"]+)"/', $addPage['body'], $m);
    $addCsrf = $m[1] ?? '';

    // Add product
    $addRes = makeRequest("{$baseUrl}/admin/add_product.php", 'POST', [
        'add' => '1',
        'name' => 'Automated Test Chair',
        'price' => '4500.00',
        'stock' => '25',
        'image' => 'https://images.unsplash.com/photo-1592078615290-033ee584e267?w=600',
        'csrf_token' => $addCsrf
    ], $cookies);

    if ($addRes['status'] !== 302) return "Add product failed with status {$addRes['status']}";

    // Update first pending order status
    $ordersPage = makeRequest("{$baseUrl}/admin/orders.php", 'GET', null, $cookies);
    preg_match('/name="csrf_token"\s+value="([^"]+)"/', $ordersPage['body'], $m);
    $ordCsrf = $m[1] ?? '';
    preg_match('/name="order_id"\s+value="(\d+)"/', $ordersPage['body'], $om);
    $orderId = $om[1] ?? 0;

    if ($orderId > 0) {
        $upOrder = makeRequest("{$baseUrl}/admin/orders.php", 'POST', [
            'update_status' => '1',
            'order_id' => $orderId,
            'status' => 'completed',
            'csrf_token' => $ordCsrf
        ], $cookies);
        if ($upOrder['status'] !== 200) return "Order status update failed";
    }

    return true;
});

// 9. Super Admin store admin provisioning & audit log inspection
runTest("Super Admin Governance & Audit Log Verification", function() use ($baseUrl) {
    $cookies = [];
    $loginPage = makeRequest("{$baseUrl}/superadmin/login.php", 'GET', null, $cookies);
    preg_match('/name="csrf_token"\s+value="([^"]+)"/', $loginPage['body'], $m);
    $csrf = $m[1] ?? '';

    makeRequest("{$baseUrl}/superadmin/login.php", 'POST', [
        'login' => '1',
        'username' => 'superadmin',
        'password' => 'Admin@123',
        'csrf_token' => $csrf
    ], $cookies);

    // Get CSRF for provisioning
    $admPage = makeRequest("{$baseUrl}/superadmin/admins.php", 'GET', null, $cookies);
    preg_match('/name="csrf_token"\s+value="([^"]+)"/', $admPage['body'], $m);
    $admCsrf = $m[1] ?? '';

    $testUser = "testops_" . time();
    $createRes = makeRequest("{$baseUrl}/superadmin/admins.php", 'POST', [
        'create_admin' => '1',
        'username' => $testUser,
        'email' => "{$testUser}@smartshop.com",
        'password' => 'TestPass@123',
        'csrf_token' => $admCsrf
    ], $cookies);

    if ($createRes['status'] !== 200 || !str_contains($createRes['body'], 'created successfully')) {
        return "Admin creation failed";
    }

    // Check audit logs
    $logPage = makeRequest("{$baseUrl}/superadmin/audit_logs.php", 'GET', null, $cookies);
    if (!str_contains($logPage['body'], 'create_store_admin')) {
        return "Audit log did not record create_store_admin action";
    }

    return true;
});

echo PHP_EOL . "=== RESULTS: {$passed} PASSED, {$failed} FAILED ===" . PHP_EOL;
