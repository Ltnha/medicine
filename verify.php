<?php
// verify.php
// PHP là nơi ra quyết định cuối cùng: gọi Node chỉ để nhờ verify chữ ký,
// còn nonce, DB, session đều do PHP tự quản lý.

session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/config/config.php';

$input = json_decode(file_get_contents('php://input'), true);
$message   = $input['message']   ?? null;
$signature = $input['signature'] ?? null;
$address   = $input['address']   ?? null;

if (!$message || !$signature || !$address) {
    http_response_code(400);
    echo json_encode(['error' => 'Thiếu dữ liệu xác thực bắt buộc!']);
    exit;
}

// BƯỚC 1: Kiểm tra nonce PHẢI khớp với nonce đã lưu trong session của chính request này
//         (làm trước khi gọi Node để tránh tốn 1 lần gọi mạng nếu nonce đã sai)
if (empty($_SESSION['nonce'])) {
    http_response_code(422);
    echo json_encode(['error' => 'Chưa có phiên đăng nhập hợp lệ, vui lòng lấy nonce lại!']);
    exit;
}

if (!preg_match('/Nonce: ([a-f0-9]+)/', $message, $match) || $match[1] !== $_SESSION['nonce']) {
    http_response_code(422);
    echo json_encode(['error' => 'Mã định danh phiên (Nonce) hết hạn hoặc không khớp!']);
    exit;
}

// Nonce hết hạn sau 10 phút
if (time() - ($_SESSION['nonce_created_at'] ?? 0) > 600) {
    unset($_SESSION['nonce']);
    http_response_code(422);
    echo json_encode(['error' => 'Nonce đã hết hạn, vui lòng thử lại!']);
    exit;
}

// BƯỚC 2: Gọi sang Node.js — CHỈ để verify chữ ký, Node không biết gì về nonce hay DB
$ch = curl_init(NODE_VERIFY_URL);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'X-Internal-Key: ' . INTERNAL_API_KEY,
    ],
    CURLOPT_POSTFIELDS => json_encode(compact('message', 'signature', 'address')),
    CURLOPT_TIMEOUT => 5,
]);
$nodeResponseRaw = curl_exec($ch);
$curlError = curl_error($ch);
curl_close($ch);

if ($curlError || !$nodeResponseRaw) {
    http_response_code(502);
    echo json_encode(['error' => 'Không thể kết nối dịch vụ xác minh chữ ký!']);
    exit;
}

$nodeResult = json_decode($nodeResponseRaw, true);

if (empty($nodeResult['valid'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Chữ ký số giả mạo hoặc không hợp lệ!']);
    exit;
}

// Hủy nonce ngay sau khi dùng, chống replay attack
unset($_SESSION['nonce']);

// BƯỚC 3: PHP tự tra cứu quyền admin trong DB
try {
    $pdo = getDbConnection();
    $stmt = $pdo->prepare('SELECT * FROM admin WHERE LOWER(ma_vi) = ?');
    $stmt->execute([strtolower($address)]);
    $admin = $stmt->fetch();

    if (!$admin) {
        http_response_code(401);
        echo json_encode(['error' => 'Địa chỉ ví này không có quyền quản trị hoặc đã bị vô hiệu hóa!']);
        exit;
    }

    // BƯỚC 4: PHP tự thiết lập session đăng nhập — đây là session PHP thật sự (PHPSESSID)
    session_regenerate_id(true); // chống session fixation
    $_SESSION['admin_id'] = $admin['ma_admin'];
    $_SESSION['role']     = $admin['role'];
    $_SESSION['ma_vi']    = $admin['ma_vi'];

    echo json_encode([
        'success' => true,
        'role' => $admin['role'],
        'message' => 'Đăng nhập Web3 thành công!'
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Lỗi xử lý xác thực hệ thống!']);
}
