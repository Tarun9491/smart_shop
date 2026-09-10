<?php
// db.php - Environment-based database connection configuration

$host = getenv('DB_HOST') ?: '127.0.0.1';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : 'Tarun@123';
$name = getenv('DB_NAME') ?: 'smart_shop';
$port = (int)(getenv('DB_PORT') ?: 3306);

// Enable strict MySQLi error reporting
mysqli_report(MYSQLI_REPORT_OFF);

$conn = @new mysqli($host, $user, $pass, $name, $port);

if ($conn->connect_error) {
    error_log("Database connection failed [{$conn->connect_errno}]: {$conn->connect_error}");
    http_response_code(503);
    die("Service temporarily unavailable. Please try again later.");
}

$conn->set_charset("utf8mb4");