<?php
// ==========================================================
// visit-tracker: Database Configuration and Security Settings
// ==========================================================

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'visit_tracker_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Secret salt for calculating anonymous visitor_hash (GDPR compliant)
// Ensures IP addresses cannot be de-anonymized via rainbow tables
define('SECRET_SALT', 'vt_salt_9a8f4c2e6b1d8a3c5e7f0b2d4e6a8c1e3f5b7d9');

/**
 * Returns a PDO database connection instance with secure options
 *
 * @return PDO
 */
function getDBConnection(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error'   => 'Failed to connect to database',
                'detail'  => $e->getMessage()
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    return $pdo;
}

/**
 * Helper to send standardized JSON responses
 *
 * @param mixed $data
 * @param int $statusCode
 */
function sendJsonResponse($data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
