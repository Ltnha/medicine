<?php
// nonce.php
// PHP tự sinh nonce và lưu trong session của chính nó — Node.js không tham gia bước này.

session_start();
header('Content-Type: application/json');

// Nếu bạn deploy PHP và trang login khác domain/port, cần cấu hình CORS ở đây,
// nhưng thường PHP và login.html sẽ chạy cùng domain nên không cần allow origin riêng.

$nonce = bin2hex(random_bytes(16));
$_SESSION['nonce'] = $nonce;
$_SESSION['nonce_created_at'] = time();

echo json_encode(['nonce' => $nonce]);
