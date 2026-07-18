<?php
session_start();
header('Content-Type: application/json');

// Tạo một chuỗi ngẫu nhiên làm Nonce
if (empty($_SESSION['nonce'])) {
    $_SESSION['nonce'] = bin2hex(random_bytes(16));
}

echo json_encode(['nonce' => $_SESSION['nonce']]);