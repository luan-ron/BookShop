<?php
require_once '../config/db.php';

$orderId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($orderId <= 0) {
    header('Location: ' . url('trangchu/index.php'));
    exit;
}

// Lấy thông tin đơn hàng để hiển thị tóm tắt
$stmt = $conn->prepare("
    SELECT o.OrderID, o.OrderDate, o.ShippingAddress, o.TotalAmount, o.OrderStatus, p.PaymentMethod 
    FROM `order` o
    LEFT JOIN `payment` p ON o.OrderID = p.OrderID
    WHERE o.OrderID = ?
");
$stmt->bind_param("i", $orderId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: ' . url('trangchu/index.php'));
    exit;
}

$order = $result->fetch_assoc();
$stmt->close();

$pageTitle = 'Đặt hàng thành công';
$extraCss = ['css/cart.css'];
include '../includes/header.php';
?>

<style>
    .success-card {
        max-width: 650px;
        margin: 40px auto;
        background: var(--color-surface);
        border: var(--border-width) solid var(--color-border);
        border-radius: var(--border-radius-lg);
        padding: var(--spacing-xl) var(--spacing-lg);
        text-align: center;
        box-shadow: var(--box-shadow-md);
    }
    .success-icon {
        font-size: 4.5rem;
        color: var(--color-success);
        margin-bottom: var(--spacing-md);
        display: block;
    }
    .success-title {
        font-size: 1.8rem;
        font-weight: var(--font-weight-bold);
        color: var(--color-success);
        margin-bottom: var(--spacing-sm);
    }
    .success-info {
        font-size: 1rem;
        color: var(--color-text-light);
        margin-bottom: var(--spacing-xl);
    }
    .success-details {
        text-align: left;
        background: var(--color-background);
        border: 1px solid var(--color-border);
        border-radius: var(--border-radius);
        padding: var(--spacing-md);
        margin-bottom: var(--spacing-xl);
    }
    .success-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: var(--spacing-xs);
        font-size: 0.95rem;
        border-bottom: 1px dashed rgba(0,0,0,0.05);
        padding-bottom: var(--spacing-xs);
    }
    .success-row:last-child {
        margin-bottom: 0;
        border-bottom: none;
        padding-bottom: 0;
    }
</style>

<main class="order-container">
    <div class="success-card">
        <i class="fa-solid fa-circle-check success-icon"></i>
        <h1 class="success-title">Đặt hàng thành công!</h1>
        <p class="success-info">Cảm ơn bạn đã tin tưởng mua sắm sách tại BookShop.</p>
        
        <div class="success-details">
            <h3 style="margin-top: 0; margin-bottom: 12px; color: var(--color-text); font-size: 1.05rem;"><i class="fa-solid fa-receipt" style="margin-right: 6px; color: var(--color-primary);"></i> Chi tiết đơn đặt hàng</h3>
            
            <div class="success-row">
                <span>Mã đơn hàng:</span>
                <strong>#WBS-<?= $order['OrderID'] ?></strong>
            </div>
            
            <div class="success-row">
                <span>Ngày đặt hàng:</span>
                <span><?= date('d/m/Y H:i', strtotime($order['OrderDate'])) ?></span>
            </div>
            
            <div class="success-row" style="flex-direction: column; align-items: flex-start; gap: 4px;">
                <span>Thông tin giao nhận hàng:</span>
                <span style="font-weight: bold; line-height: 1.4; color: var(--color-text);"><?= htmlspecialchars($order['ShippingAddress']) ?></span>
            </div>
            
            <div class="success-row">
                <span>Hình thức thanh toán:</span>
                <strong><?= $order['PaymentMethod'] === 'VNPAY' ? 'Thanh toán trực tuyến VNPAY' : 'Thanh toán khi nhận hàng (COD)' ?></strong>
            </div>

            <div class="success-row">
                <span>Tổng tiền thanh toán:</span>
                <strong style="color: var(--color-accent); font-size: 1.1rem;"><?= number_format($order['TotalAmount'], 0, ',', '.') ?> đ</strong>
            </div>

            <div class="success-row">
                <span>Trạng thái đơn hàng:</span>
                <span class="badge badge--info"><?= $order['OrderStatus'] === 'Pending' ? 'Chờ xác nhận' : 'Đang xử lý' ?></span>
            </div>
        </div>

        <div style="display: flex; gap: var(--spacing-md); justify-content: center;">
            <a href="<?= url('cart/detail.php?id=' . $order['OrderID']) ?>" class="btn btn--primary" style="padding: 12px 28px; font-weight: bold; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                <i class="fa-solid fa-magnifying-glass"></i> Theo dõi đơn hàng
            </a>
            <a href="<?= url('trangchu/index.php') ?>" class="btn btn--outline" style="padding: 12px 28px; text-decoration: none;">
                Tiếp tục mua sách
            </a>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
</body>
</html>
