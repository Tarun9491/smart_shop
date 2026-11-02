
<?php
// db.php - Aiven MySQL connection for Render

mysqli_report(MYSQLI_REPORT_OFF);

// Read database credentials from environment variables
$host = getenv('DB_HOST') ?: 'mysql-1da1ba52-lakkojutarun966-2d57.d.aivencloud.com';
$user = getenv('DB_USER') ?: '24897';
$pass = getenv('DB_PASS') ?: 'Tarun@123';
$name = getenv('DB_NAME') ?: 'defaultdb';
$port = (int)(getenv('DB_PORT') ?: 24897);

// Optional path to the Aiven CA certificate
$caPath = getenv('DB_SSL_CA') ?: '';

// Validate required configuration
if ($host === '' || $user === '' || $pass === '') {
    error_log('Database configuration missing. Check DB_HOST, DB_USER and DB_PASS.');
    http_response_code(503);
    exit('Service temporarily unavailable. Please try again later.');
}

// Initialize MySQLi
$conn = mysqli_init();

if (!$conn) {
    error_log('MySQLi initialization failed.');
    http_response_code(503);
    exit('Service temporarily unavailable. Please try again later.');
}

// Set connection timeout
$conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 10);

// Enable SSL before connecting.
// If a CA certificate is supplied, use it for certificate validation.
if (!$conn->ssl_set(
    null,
    null,
    $caPath !== '' ? $caPath : null,
    null,
    null
)) {
    error_log('Failed to configure MySQL SSL.');
    http_response_code(503);
    exit('Service temporarily unavailable. Please try again later.');
}

// Connect using SSL
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
    exit('Service temporarily unavailable. Please try again later.');
}

// Set character encoding
$conn->set_charset('utf8mb4');
