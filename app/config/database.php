<?php

/**
 * Database Configuration
 * Supports both local (.env) and Railway environment variables
 */

// Railway injects MYSQLHOST, MYSQLPORT etc. directly as env vars
// Local dev uses .env file via env_loader (DB_HOST, DB_NAME etc.)

$db_host = getenv('MYSQLHOST')     ?: getenv('DB_HOST') ?: 'localhost';
$db_port = getenv('MYSQLPORT')     ?: getenv('DB_PORT') ?: '3306';
$db_name = getenv('MYSQLDATABASE') ?: getenv('DB_NAME') ?: 'carms_db';
$db_user = getenv('MYSQLUSER')     ?: getenv('DB_USER') ?: 'root';
$db_pass = getenv('MYSQLPASSWORD') ?: getenv('DB_PASS') ?: '';

try {
    $pdo = new PDO(
        "mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    error_log("DB Connection failed: " . $e->getMessage());
    http_response_code(500);
    die(json_encode(['success' => false, 'message' => 'Database connection failed. Please try again later.']));
}

return $pdo;
