<?php
// Bắt đầu session và kết nối cơ sở dữ liệu
require_once __DIR__ . '/../config/db.php';

// Kiểm tra quyền truy cập Admin
if (!isset($_SESSION['admin'])) {
    $_SESSION['error'] = 'Vui lòng đăng nhập tài khoản Quản trị viên.';
    header('Location: /BookShop/auth/pages/login.php');
    exit;
}

if (strtolower($_SESSION['admin']['role'] ?? '') !== 'admin') {
    $_SESSION['log_toast'] = 'Cảnh báo: Bạn không có quyền truy cập trang quản trị.';
    header('Location: /BookShop/trangchu/index.php');
    exit;
}

function adminCsrfToken() {
    if (empty($_SESSION['admin_csrf_token'])) {
        $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['admin_csrf_token'];
}

function verifyAdminCsrf($token) {
    return is_string($token)
        && isset($_SESSION['admin_csrf_token'])
        && hash_equals($_SESSION['admin_csrf_token'], $token);
}
?>
