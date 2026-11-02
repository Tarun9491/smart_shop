
<?php
// db.php - Aiven MySQL connection for Render

mysqli_report(MYSQLI_REPORT_OFF);

// Aiven database configuration
$host = getenv('DB_HOST') ?: 'mysql-1da1ba52-lakkojutarun966-2d57.d.aivencloud.com';
$user = getenv('DB_USER') ?: 'avnadmin';
$pass = getenv('DB_PASS') ?: 'Tarun@123';
$name = getenv('DB_NAME') ?: 'defaultdb';
$port = (int)(getenv('DB_PORT') ?: 24897);

// Aiven CA certificate path
$caPath = getenv('DB_SSL_CA') ?: '/var/www/html/certs/ca.pem';

// Validate database credentials
if ($host === '' || $user === '' || $pass === '') {
    error_log('Database configuration missing.');
    http_response_code(503);
    exit('Service temporarily unavailable.');
}

// Check CA certificate
if (!is_readable($caPath)) {
    error_log('Aiven CA certificate missing or unreadable: ' . $caPath);
    http_response_code(503);
    exit('Service temporarily unavailable.');
}

// Initialize MySQLi
$conn = mysqli_init();

if (!$conn) {
    error_log('MySQLi initialization failed.');
    http_response_code(503);
    exit('Service temporarily unavailable.');
}

// Connection timeout
$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);

// Configure SSL
if (!$conn->ssl_set(null, null, $caPath, null, null)) {
    error_log('Unable to configure MySQL SSL.');
    http_response_code(503);
    exit('Service temporarily unavailable.');
}

// Connect to Aiven MySQL
$connected = @$conn->real_connect(
    $host,
    $user,
    $pass,
    $name,
    $port,
    null,
    MYSQLI_CLIENT_SSL
);

if (!$connected) {
    error_log(
        "Database connection failed [{$conn->connect_errno}]: " .
        $conn->connect_error
    );

    http_response_code(503);
    exit('Service temporarily unavailable.');
}

// Set UTF-8 encoding
$conn->set_charset('utf8mb4');
