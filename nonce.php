<?php
session_start();
header('Content-Type: application/json');

$nonce = bin2hex(random_bytes(16));
$_SESSION['nonce'] = $nonce;
$_SESSION['nonce_created_at'] = time();

echo json_encode(['nonce' => $nonce]);
