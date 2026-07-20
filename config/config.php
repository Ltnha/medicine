<?php
// config.php
// Cấu hình dùng chung — nên chuyển các giá trị nhạy cảm ra biến môi trường (.env) khi lên production.

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// --- Kết nối MySQL ---
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'pharmachain_db');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');

// --- Địa chỉ dịch vụ Node.js nội bộ (chỉ xác minh chữ ký) ---
define('NODE_VERIFY_URL', 'http://127.0.0.1:3001/verify-signature');
define('INTERNAL_API_KEY', $_ENV['INTERNAL_API_KEY'] ?? ''); // phải khớp với verify-service.js

/**
 * Trả về kết nối PDO tới MySQL, dùng chung cho các file khác.
 */
function getDbConnection(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo;
}
