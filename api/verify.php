<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../vendor/autoload.php'; // Đảm bảo đúng đường dẫn tới Composer autoload

use Kornrunner\Ethereum\Address;

// Lấy dữ liệu JSON từ request
$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['message']) || empty($data['signature'])) {
    echo json_encode(['success' => false, 'error' => 'Thiếu thông tin thông điệp hoặc chữ ký.']);
    exit;
}

$message = $data['message'];
$signature = $data['signature'];

// 1. Phân tách thông điệp để lấy Địa chỉ ví và mã Nonce từ Client gửi lên
$lines = explode("\n", $message);

// Theo chuẩn SIWE, địa chỉ ví nằm ở dòng thứ 2 (index 1)
$clientAddress = trim($lines[1]); 

// Tìm dòng chứa Nonce
$clientNonce = '';
foreach ($lines as $line) {
    if (strpos($line, 'Nonce:') === 0) {
        $clientNonce = trim(str_replace('Nonce:', '', $line));
        break;
    }
}

// 2. Kiểm tra bảo mật Nonce
if (empty($_SESSION['nonce']) || $clientNonce !== $_SESSION['nonce']) {
    echo json_encode(['success' => false, 'error' => 'Mã Nonce không hợp lệ hoặc đã hết hạn.']);
    exit;
}

// 3. Hàm giải mã và xác thực chữ ký (Ethereum EcRecover)
function verifyEthereumSignature($message, $signature, $address) {
    try {
        // Ethereum thêm tiền tố này vào thông điệp trước khi ký bảo mật
        $msgLength = strlen($message);
        $ethMessage = "\x19Ethereum Signed Message:\n" . $msgLength . $message;
        $hash = keccak256($ethMessage); // Hàm băm Keccak256 chuẩn

        // Tách chữ ký r, s, v
        $signature = substr($signature, 2); // Bỏ phần '0x'
        $r = substr($signature, 0, 64);
        $s = substr($signature, 64, 64);
        $v = hexdec(substr($signature, 128, 2));

        // Chuẩn hóa V của Ethereum
        if ($v < 27) {
            $v += 27;
        }

        // Dùng thư viện kornrunner để recover lại địa chỉ từ chữ ký
        $recoveredAddress = Address::ecRecover($hash, ['r' => $r, 's' => $s, 'v' => $v]);

        // So sánh không phân biệt hoa thường
        return strtolower($recoveredAddress) === strtolower($address);
    } catch (\Exception $e) {
        return false;
    }
}

// Hàm bổ trợ băm keccak256
function keccak256($str) {
    return \Kornrunner\Keccak::hash($str, 256);
}

// 4. Tiến hành xác thực chữ ký
if (verifyEthereumSignature($message, $signature, $clientAddress)) {
    // Xóa nonce sau khi dùng xong nhằm chống Replay Attack
    unset($_SESSION['nonce']);
    
    // Lưu trạng thái đăng nhập vào Session
    $_SESSION['user_address'] = $clientAddress;
    $_SESSION['logged_in'] = true;

    echo json_encode([
        'success' => true,
        'address' => $clientAddress
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Chữ ký không hợp lệ, xác thực thất bại.']);
}