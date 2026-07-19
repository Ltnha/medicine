<?php
// logout.php
// Hủy toàn bộ session hiện tại và đưa người dùng về trang login.

session_start();

// Xóa hết dữ liệu trong session
$_SESSION = [];

// Xóa cookie session ở trình duyệt (nếu dùng cookie-based session, gần như luôn đúng)
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

// Hủy session phía server
session_destroy();

header('Location: login.php');
exit;