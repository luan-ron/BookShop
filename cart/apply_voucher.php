<?php
require_once '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('cart/cart.php'));
    exit;
}

$action = isset($_POST['action']) ? trim($_POST['action']) : 'apply';
$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : url('cart/cart.php');

if ($action === 'apply') {
    // 1. Kiểm tra đăng nhập (Bắt buộc phải đăng nhập mới được dùng voucher)
    if (!isset($_SESSION['user'])) {
        $_SESSION['error'] = 'Vui lòng đăng nhập để sử dụng mã giảm giá!';
        header('Location: ' . $referer);
        exit;
    }

    $customerId = intval($_SESSION['user']['id']);
    $voucherCode = isset($_POST['voucher_code']) ? trim($_POST['voucher_code']) : '';

    if (empty($voucherCode)) {
        $_SESSION['error'] = 'Vui lòng nhập mã giảm giá!';
        header('Location: ' . $referer);
        exit;
    }

    // 2. Tìm voucher trong database
    $sql = "SELECT * FROM voucher WHERE VoucherCode = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $voucherCode);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $_SESSION['error'] = 'Mã giảm giá không tồn tại trong hệ thống!';
        $stmt->close();
        header('Location: ' . $referer);
        exit;
    }

    $voucher = $result->fetch_assoc();
    $stmt->close();

    // 3. Kiểm tra hết hạn
    if ($voucher['ExpiredDate'] !== null) {
        $expiredTime = strtotime($voucher['ExpiredDate']);
        if ($expiredTime < time()) {
            $_SESSION['error'] = 'Mã giảm giá này đã hết hạn sử dụng!';
            header('Location: ' . $referer);
            exit;
        }
    }

    // 4. Kiểm tra xem người dùng đã sử dụng voucher này chưa
    $sqlCheckUsed = "SELECT UsedStatus FROM voucher_detail WHERE CustomerID = ? AND VoucherID = ? LIMIT 1";
    $stmtCheck = $conn->prepare($sqlCheckUsed);
    $stmtCheck->bind_param("ii", $customerId, $voucher['VoucherID']);
    $stmtCheck->execute();
    $resCheck = $stmtCheck->get_result();

    if ($resCheck->num_rows > 0) {
        $usedRow = $resCheck->fetch_assoc();
        if (intval($usedRow['UsedStatus']) === 1) {
            $_SESSION['error'] = 'Bạn đã sử dụng mã giảm giá này cho đơn hàng trước!';
            $stmtCheck->close();
            header('Location: ' . $referer);
            exit;
        }
    }
    $stmtCheck->close();

    // 5. Lưu vào session
    $_SESSION['applied_voucher'] = [
        'id' => intval($voucher['VoucherID']),
        'code' => $voucher['VoucherCode'],
        'value' => floatval($voucher['DiscountValue'])
    ];
    $_SESSION['success'] = "Áp dụng mã giảm giá '" . htmlspecialchars($voucher['VoucherCode']) . "' thành công!";

} elseif ($action === 'remove') {
    unset($_SESSION['applied_voucher']);
    $_SESSION['success'] = 'Đã hủy áp dụng mã giảm giá thành công!';
}

header('Location: ' . $referer);
exit;
?>
