<?php
require_once '../config/db.php';
require_once '../config/vnpay.php';

$vnp_SecureHash = $_GET['vnp_SecureHash'] ?? '';
$vnp_ResponseCode = $_GET['vnp_ResponseCode'] ?? '';
$vnp_TxnRef = $_GET['vnp_TxnRef'] ?? ''; // Đây chính là OrderID của chúng ta
$orderId = intval($vnp_TxnRef);

$pageTitle = 'Kết quả thanh toán VNPAY';
$extraCss = ['css/cart.css'];
include '../includes/header.php';

$isValidSignature = false;

// 1. Kiểm tra chữ ký bảo mật từ VNPAY
if (!empty($vnp_SecureHash) && defined('VNP_HASHSECRET') && VNP_HASHSECRET !== 'YOUR_HASHSECRET') {
    $vnp_Params = [];
    foreach ($_GET as $key => $value) {
        if (substr($key, 0, 4) == 'vnp_') {
            $vnp_Params[$key] = $value;
        }
    }
    
    unset($vnp_Params['vnp_SecureHash']);
    unset($vnp_Params['vnp_SecureHashType']);
    ksort($vnp_Params);
    
    $i = 0;
    $hashData = "";
    foreach ($vnp_Params as $key => $value) {
        if ($i == 1) {
            $hashData .= '&' . urlencode($key) . "=" . urlencode($value);
        } else {
            $hashData .= urlencode($key) . "=" . urlencode($value);
            $i = 1;
        }
    }
    
    $secureHash = hash_hmac('sha512', $hashData, VNP_HASHSECRET);
    if ($secureHash === $vnp_SecureHash) {
        $isValidSignature = true;
    }
} else {
    // Không chấp nhận callback nếu thiếu hash secret hoặc chữ ký.
    $isValidSignature = false;
}

$paymentSuccess = false;

if ($isValidSignature && $orderId > 0) {
    if ($vnp_ResponseCode == '00') {
        // Thanh toán thành công!
        $paymentSuccess = true;
        
        $conn->begin_transaction();
        try {
            // Cập nhật trạng thái Payment
            $stmtPayment = $conn->prepare("UPDATE `payment` SET PaymentStatus = 'Completed', PaymentDate = NOW() WHERE OrderID = ?");
            $stmtPayment->bind_param("i", $orderId);
            $stmtPayment->execute();
            $stmtPayment->close();

            // Cập nhật trạng thái Order sang 'Processing'
            $stmtOrder = $conn->prepare("UPDATE `order` SET OrderStatus = 'Processing' WHERE OrderID = ?");
            $stmtOrder->bind_param("i", $orderId);
            $stmtOrder->execute();
            $stmtOrder->close();

            $orderCustId = null;
            // Đọc CustomerID từ đơn hàng để tìm và đánh dấu giỏ hàng của họ thành Completed
            $stmtGetCust = $conn->prepare("SELECT CustomerID FROM `order` WHERE OrderID = ?");
            $stmtGetCust->bind_param("i", $orderId);
            $stmtGetCust->execute();
            $resCust = $stmtGetCust->get_result();
            if ($resCust->num_rows > 0) {
                $orderRow = $resCust->fetch_assoc();
                $orderCustId = $orderRow['CustomerID'];
                if ($orderCustId !== null && $orderCustId > 0) {
                    $stmtDelCartDetail = $conn->prepare("DELETE FROM cart_detail WHERE CartID IN (SELECT CartID FROM cart WHERE CustomerID = ? AND Status = 'Active')");
                    $stmtDelCartDetail->bind_param("i", $orderCustId);
                    $stmtDelCartDetail->execute();
                    $stmtDelCartDetail->close();

                    $stmtCompleteCart = $conn->prepare("UPDATE cart SET Status = 'Completed' WHERE CustomerID = ? AND Status = 'Active'");
                    $stmtCompleteCart->bind_param("i", $orderCustId);
                    $stmtCompleteCart->execute();
                    $stmtCompleteCart->close();
                }
            }
            $stmtGetCust->close();

            $conn->commit();
            
            // Ghi nhật ký thanh toán thành công qua VNPAY
            write_user_log($conn, "Thanh toán thành công đơn hàng #WBS-" . $orderId . " qua VNPAY", $orderCustId);
            
            // Xóa giỏ hàng và voucher khi thanh toán thành công
            unset($_SESSION['cart']);
            unset($_SESSION['applied_voucher']);
            $_SESSION['success_order_id'] = $orderId;
        } catch (Exception $e) {
            $conn->rollback();
            $paymentSuccess = false;
        }
    } else {
        // Thanh toán thất bại hoặc người dùng hủy: tiến hành hủy đơn hàng và hoàn kho
        $conn->begin_transaction();
        try {
            // 1. Cập nhật trạng thái Payment sang Failed
            $stmtPayment = $conn->prepare("UPDATE `payment` SET PaymentStatus = 'Failed' WHERE OrderID = ?");
            $stmtPayment->bind_param("i", $orderId);
            $stmtPayment->execute();
            $stmtPayment->close();

            // 2. Cập nhật trạng thái Order sang Cancelled
            $stmtOrder = $conn->prepare("UPDATE `order` SET OrderStatus = 'Cancelled' WHERE OrderID = ?");
            $stmtOrder->bind_param("i", $orderId);
            $stmtOrder->execute();
            $stmtOrder->close();

            // 3. Hoàn kho sản phẩm (Cộng trả lại số lượng)
            $stmtDetails = $conn->prepare("SELECT ProductID, Quantity FROM `order_detail` WHERE OrderID = ?");
            $stmtDetails->bind_param("i", $orderId);
            $stmtDetails->execute();
            $resDetails = $stmtDetails->get_result();
            
            $stmtRestoreStock = $conn->prepare("UPDATE `product` SET Quantity = Quantity + ?, Status = 'Còn hàng' WHERE ProductID = ?");
            while ($item = $resDetails->fetch_assoc()) {
                $stmtRestoreStock->bind_param("ii", $item['Quantity'], $item['ProductID']);
                $stmtRestoreStock->execute();
            }
            $stmtRestoreStock->close();
            $stmtDetails->close();

            $custVal = null;
            // 4. Hoàn lại trạng thái voucher trong database (nếu có)
            $stmtGetVoucher = $conn->prepare("SELECT CustomerID, VoucherID FROM `order` WHERE OrderID = ?");
            $stmtGetVoucher->bind_param("i", $orderId);
            $stmtGetVoucher->execute();
            $resVoucher = $stmtGetVoucher->get_result();
            if ($resVoucher->num_rows > 0) {
                $orderRow = $resVoucher->fetch_assoc();
                $custVal = $orderRow['CustomerID'];
                $vouchVal = $orderRow['VoucherID'];
                if ($custVal !== null && $vouchVal !== null) {
                    $stmtRevertVoucher = $conn->prepare("UPDATE voucher_detail SET UsedStatus = 0 WHERE CustomerID = ? AND VoucherID = ?");
                    $stmtRevertVoucher->bind_param("ii", $custVal, $vouchVal);
                    $stmtRevertVoucher->execute();
                    $stmtRevertVoucher->close();
                }
            }
            $stmtGetVoucher->close();

            $conn->commit();
            
            // Ghi nhật ký thanh toán thất bại qua VNPAY
            write_user_log($conn, "Thanh toán thất bại đơn hàng #WBS-" . $orderId . " qua VNPAY", $custVal);
        } catch (Exception $e) {
            $conn->rollback();
        }
    }
}
?>

<style>
    .result-card {
        max-width: 600px;
        margin: 40px auto;
        background: var(--color-surface);
        border: var(--border-width) solid var(--color-border);
        border-radius: var(--border-radius-lg);
        padding: var(--spacing-xl) var(--spacing-lg);
        text-align: center;
        box-shadow: var(--box-shadow-md);
    }
    .result-icon {
        font-size: 4rem;
        margin-bottom: var(--spacing-md);
        display: block;
    }
    .result-title {
        font-size: 1.6rem;
        font-weight: var(--font-weight-bold);
        margin-bottom: var(--spacing-sm);
    }
    .result-info {
        font-size: 1rem;
        color: var(--color-text-light);
        margin-bottom: var(--spacing-xl);
    }
    .result-details {
        text-align: left;
        background: var(--color-background);
        border: 1px solid var(--color-border);
        border-radius: var(--border-radius);
        padding: var(--spacing-md);
        margin-bottom: var(--spacing-xl);
    }
    .result-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: var(--spacing-xs);
        font-size: 0.95rem;
    }
    .result-row:last-child {
        margin-bottom: 0;
    }
</style>

<main class="order-container">
    <div class="result-card">
        <?php if ($paymentSuccess): ?>
            <i class="fa-solid fa-circle-check result-icon" style="color: var(--color-success); display: block;"></i>
            <h1 class="result-title" style="color: var(--color-success);">Thanh toán thành công!</h1>
            <p class="result-info">Cảm ơn bạn đã tin tưởng mua sắm tại BookShop.</p>
            
            <div class="result-details">
                <div class="result-row">
                    <span>Mã đơn hàng:</span>
                    <strong>#WBS-<?= $orderId ?></strong>
                </div>
                <div class="result-row">
                    <span>Số tiền giao dịch:</span>
                    <strong><?= number_format(($_GET['vnp_Amount'] ?? 0) / 100, 0, ',', '.') ?> đ</strong>
                </div>
                <div class="result-row">
                    <span>Mã giao dịch VNPAY:</span>
                    <strong style="font-family: monospace;"><?= htmlspecialchars($_GET['vnp_TransactionNo'] ?? 'Chưa rõ') ?></strong>
                </div>
                <div class="result-row">
                    <span>Trạng thái:</span>
                    <span class="badge badge--success">Đã thanh toán trực tuyến</span>
                </div>
            </div>

            <div style="display: flex; gap: var(--spacing-md); justify-content: center;">
                <a href="<?= url('cart/detail.php?id=' . $orderId) ?>" class="btn btn--primary" style="padding: 10px 24px;">Chi tiết đơn hàng</a>
                <a href="<?= url('trangchu/index.php') ?>" class="btn btn--outline" style="padding: 10px 24px;">Quay lại trang chủ</a>
            </div>
            
        <?php else: ?>
            <i class="fa-solid fa-circle-xmark result-icon" style="color: var(--color-error); display: block;"></i>
            <h1 class="result-title" style="color: var(--color-error);">Thanh toán thất bại!</h1>
            <p class="result-info">Giao dịch thanh toán trực tuyến đã bị hủy hoặc gặp sự cố.</p>
            
            <div class="result-details">
                <div class="result-row">
                    <span>Mã đơn hàng:</span>
                    <strong>#WBS-<?= $orderId ?></strong>
                </div>
                <div class="result-row">
                    <span>Mã phản hồi từ VNPAY:</span>
                    <strong style="color: var(--color-error);"><?= htmlspecialchars($vnp_ResponseCode) ?></strong>
                </div>
                <div class="result-row">
                    <span>Gợi ý xử lý:</span>
                    <span>Bạn vẫn có thể kiểm tra đơn hàng và thử thanh toán lại hoặc liên hệ hỗ trợ.</span>
                </div>
            </div>

             <div style="display: flex; gap: var(--spacing-md); justify-content: center;">
                 <a href="<?= url('cart/checkout.php') ?>" class="btn btn--primary" style="padding: 10px 24px; text-decoration: none;">Thử thanh toán lại</a>
                 <a href="<?= url('cart/cart.php') ?>" class="btn btn--outline" style="padding: 10px 24px; text-decoration: none;">Quay lại giỏ hàng</a>
             </div>
        <?php endif; ?>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
</body>
</html>
