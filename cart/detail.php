<?php
require_once '../config/db.php';

$orderId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($orderId <= 0) {
    header('Location: ' . url('cart/history.php'));
    exit;
}

// 1. Truy vấn thông tin đơn hàng
$sqlOrder = "
    SELECT o.OrderID, o.CustomerID, o.OrderDate, o.ShippingAddress, o.OrderStatus, o.TotalAmount, o.VoucherID, o.Note,
           p.PaymentMethod, p.PaymentStatus, d.DeliveryStatus, d.ShippingFee,
           u.FirstName, u.LastName, u.Phone
    FROM `order` o
    LEFT JOIN `payment` p ON o.OrderID = p.OrderID
    LEFT JOIN `delivery` d ON o.OrderID = d.OrderID
    LEFT JOIN `user` u ON o.CustomerID = u.CustomerID
    WHERE o.OrderID = ?
";
$stmt = $conn->prepare($sqlOrder);
$stmt->bind_param("i", $orderId);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    $pageTitle = 'Không thể truy cập đơn hàng';
    $extraCss = ['css/cart.css'];
    include '../includes/header.php';
    ?>
    <main class="order-container">
        <section class="card order-detail-error">
            <i class="fa-solid fa-circle-exclamation order-detail-error__icon" aria-hidden="true"></i>
            <h1 class="order-detail-error__title">Không thể truy cập đơn hàng</h1>
            <p class="text-muted">Đơn hàng không tồn tại hoặc bạn không có quyền truy cập.</p>
            <a href="history.php" class="btn btn--primary">Quay lại lịch sử đơn hàng</a>
        </section>
    </main>
    <?php
    include '../includes/footer.php';
    exit;
}

$order = $res->fetch_assoc();
$stmt->close();

// Kiểm tra quyền truy cập đơn hàng
$currentCustomerId = isset($_SESSION['user']) ? intval($_SESSION['user']['id']) : 0;
$isAuthorized = false;
$orderPhone = '';

if ($order['CustomerID'] !== null) {
    // Đơn hàng của thành viên đăng nhập
    if ($order['CustomerID'] === $currentCustomerId) {
        $isAuthorized = true;
    }
} else {
    // Đơn hàng của khách vãng lai
    $guestInfo = getGuestInfoFromAddress($order['ShippingAddress']);
    $orderPhone = $guestInfo['phone'];
    
    if (isset($_SESSION['verified_orders'][$orderId]) && $_SESSION['verified_orders'][$orderId] === true) {
        $isAuthorized = true;
    } elseif (isset($_SESSION['guest_search_phone']) && $_SESSION['guest_search_phone'] === $orderPhone) {
        $isAuthorized = true;
    } elseif (isset($_SESSION['guest_checkout']['phone']) && $_SESSION['guest_checkout']['phone'] === $orderPhone) {
        $isAuthorized = true;
    }
}

// Xử lý POST xác minh số điện thoại cho khách vãng lai
$verifyPhoneError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_guest_phone'])) {
    $inputPhone = trim($_POST['verify_guest_phone']);
    if (preg_match('/^\d{10,11}$/', $inputPhone) &&
        preg_match('/^\d{10,11}$/', $orderPhone) &&
        $order['CustomerID'] === null && $inputPhone === $orderPhone) {
        $_SESSION['verified_orders'][$orderId] = true;
        $_SESSION['guest_search_phone'] = $inputPhone;
        $isAuthorized = true;
    } else {
        $verifyPhoneError = 'Số điện thoại xác minh không chính xác. Vui lòng thử lại!';
    }
}

if ($order['CustomerID'] !== null && !$isAuthorized) {
    $pageTitle = 'Không thể truy cập đơn hàng';
    $extraCss = ['css/cart.css'];
    include '../includes/header.php';
    ?>
    <main class="order-container">
        <section class="card order-detail-error">
            <i class="fa-solid fa-circle-exclamation order-detail-error__icon" aria-hidden="true"></i>
            <h1 class="order-detail-error__title">Không thể truy cập đơn hàng</h1>
            <p class="text-muted">Đơn hàng không tồn tại hoặc bạn không có quyền truy cập.</p>
            <a href="history.php" class="btn btn--primary">Quay lại lịch sử đơn hàng</a>
        </section>
    </main>
    <?php
    include '../includes/footer.php';
    exit;
}


// 2. Truy vấn chi tiết sản phẩm đã mua
$sqlDetails = "
    SELECT od.ProductID, od.Quantity, od.Price, od.UnitPrice, p.ProductName, i.ImageURL 
    FROM `order_detail` od
    JOIN `product` p ON od.ProductID = p.ProductID
    LEFT JOIN `image` i ON p.ProductID = i.ProductID AND i.IsThumbnail = 1
    WHERE od.OrderID = ?
";
$stmtD = $conn->prepare($sqlDetails);
$stmtD->bind_param("i", $orderId);
$stmtD->execute();
$resD = $stmtD->get_result();

$items = [];
while ($row = $resD->fetch_assoc()) {
    $items[] = $row;
}
$stmtD->close();

$pageTitle = 'Chi tiết đơn hàng #WBS-' . $orderId;
$extraCss = ['css/cart.css'];
include '../includes/header.php';

// Các hàm tiện ích đổi text trạng thái
function getOrderStatusText($status) {
    switch ($status) {
        case 'Pending': return 'Chờ xác nhận';
        case 'Processing': return 'Đang đóng gói';
        case 'Shipped': return 'Đang vận chuyển';
        case 'Delivered': return 'Giao thành công';
        case 'Cancelled': return 'Đã hủy đơn';
        default: return 'Chưa rõ';
    }
}

function getPaymentDisplayText($method, $status) {
    $method = strtoupper((string) $method);
    $status = (string) $status;

    if ($method === 'COD') {
        switch ($status) {
            case 'Pending': return 'Thanh toán khi nhận hàng';
            case 'Completed': return 'Đã thanh toán COD';
            case 'Failed': return 'Thanh toán COD thất bại';
            case 'Refunded': return 'Đã hoàn tiền';
        }
    }

    if ($method === 'VNPAY') {
        switch ($status) {
            case 'Pending': return 'Chờ thanh toán online';
            case 'Completed': return 'Đã thanh toán online';
            case 'Failed': return 'Thanh toán thất bại';
            case 'Refunded': return 'Đã hoàn tiền';
        }
    }

    return 'Phương thức hoặc trạng thái thanh toán chưa rõ';
}

function getDeliveryStatusText($status) {
    switch ($status) {
        case 'Preparing': return 'Đang chuẩn bị';
        case 'Shipping': return 'Đang vận chuyển';
        case 'Delivered': return 'Đã giao hàng';
        case 'Failed': return 'Giao hàng thất bại';
        default: return 'Chờ xử lý';
    }
}
?>

<style>
    .order-detail-page {
        --color-primary: rgb(0, 169, 242);
        --color-primary-hover: rgb(0, 135, 195);
        --color-secondary: #f9b234;
        --color-accent: rgb(0, 169, 242);
        --color-border: #dce8ef;
        --color-background: #f4f9fc;
        --color-text: #172033;
        --color-text-light: #64748b;
    }

    .order-detail-page .form-control:focus {
        box-shadow: 0 0 0 3px rgba(0, 169, 242, 0.12);
    }

    .order-detail-page .btn--ghost:hover {
        background-color: rgba(0, 169, 242, 0.08);
    }

    .order-detail-page .table thead {
        background-color: rgb(0, 169, 242);
    }

    .order-detail-page .table th {
        color: #fff;
    }
</style>

<main class="order-container order-detail-page">
    <ul class="breadcrumbs">
        <li><a href="<?= url('/') ?>">Trang chủ</a></li>
        <li><a href="history.php">Lịch sử đơn hàng</a></li>
        <li>Chi tiết đơn hàng #WBS-<?= $orderId ?></li>
    </ul>

    <?php if (!$isAuthorized): ?>
        <!-- Form xác minh số điện thoại cho khách vãng lai -->
        <div class="order-verification-card">
            <i class="fa-solid fa-user-shield order-verification-card__icon" aria-hidden="true"></i>
            <h2 class="order-verification-card__title">Xác minh đơn hàng</h2>
            <p class="order-verification-card__description">Đơn hàng này thuộc về <strong>Khách vãng lai</strong>. Vui lòng cung cấp số điện thoại mua hàng để tiếp tục xem chi tiết.</p>
            
            <?php if (!empty($verifyPhoneError)): ?>
                <div class="alert alert--error order-verification-card__alert">
                    <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i><?= htmlspecialchars($verifyPhoneError) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="order-verification-card__form">
                <input type="text" name="verify_guest_phone" class="form-control text-center" placeholder="Nhập số điện thoại mua hàng..." required>
                <button type="submit" class="btn btn--primary">Xác minh & Xem chi tiết</button>
            </form>
        </div>
    <?php else: ?>
        <div class="order-title-section">
        <div>
            <h1 class="order-title">Chi tiết đơn hàng #WBS-<?= $orderId ?></h1>
            <p class="order-status-line">
                Đặt ngày: <?= date('d/m/Y H:i', strtotime($order['OrderDate'])) ?> | Trạng thái đơn: 
                <span class="badge badge--sm <?= $order['OrderStatus'] === 'Delivered' ? 'badge--success' : ($order['OrderStatus'] === 'Cancelled' ? 'badge--error' : 'badge--info') ?>">
                    <?= getOrderStatusText($order['OrderStatus']) ?>
                </span>
            </p>
        </div>
        <div>
            <a href="<?= url('cart/tracking.php?id=' . $orderId) ?>" class="btn btn--primary">
                <i class="fa-solid fa-location-dot" aria-hidden="true"></i>Theo dõi vận chuyển
            </a>
        </div>
    </div>

    <div class="order-detail-layout">
        <!-- Cột trái: Thông tin nhận hàng, Thanh toán, Sản phẩm -->
        <div class="order-detail-main">
            <!-- Thông tin nhận hàng -->
            <div class="detail-section-card">
                <h2 class="detail-section-title"><i class="fa-solid fa-user" aria-hidden="true"></i>Thông tin nhận hàng</h2>
                <div class="order-contact-details">
                    <?php if ($order['CustomerID'] === null): 
                        $guestInfo = getGuestInfoFromAddress($order['ShippingAddress']);
                    ?>
                        <div class="info-details-item">
                            <span class="info-details-label">Người nhận:</span>
                            <span class="info-details-value"><?= htmlspecialchars($guestInfo['fullname']) ?></span>
                        </div>
                        <div class="info-details-item">
                            <span class="info-details-label">Số điện thoại:</span>
                            <span class="info-details-value"><?= htmlspecialchars($guestInfo['phone']) ?></span>
                        </div>
                        <div class="info-details-item">
                            <span class="info-details-label">Địa chỉ giao hàng:</span>
                            <span class="info-details-value"><?= htmlspecialchars($guestInfo['address']) ?></span>
                        </div>
                    <?php else: 
                        $memberFullName = trim(($order['FirstName'] ?? '') . ' ' . ($order['LastName'] ?? ''));
                        if (empty($memberFullName)) {
                            $memberFullName = $_SESSION['user']['full_name'] ?? 'Thành viên';
                        }
                        $memberPhone = !empty($order['Phone']) ? $order['Phone'] : ($_SESSION['user']['phone'] ?? 'Chưa rõ');
                    ?>
                        <!-- Định dạng thành viên đăng nhập -->
                        <div class="info-details-item">
                            <span class="info-details-label">Người nhận:</span>
                            <span class="info-details-value"><?= htmlspecialchars($memberFullName) ?></span>
                        </div>
                        <div class="info-details-item">
                            <span class="info-details-label">Số điện thoại:</span>
                            <span class="info-details-value"><?= htmlspecialchars($memberPhone) ?></span>
                        </div>
                        <div class="info-details-item">
                            <span class="info-details-label">Địa chỉ giao hàng:</span>
                            <span class="info-details-value"><?= htmlspecialchars($order['ShippingAddress']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($order['Note'] !== null && trim($order['Note']) !== ''): ?>
                <div class="detail-section-card">
                    <h2 class="detail-section-title"><i class="fa-solid fa-note-sticky" aria-hidden="true"></i>Ghi chú giao hàng</h2>
                    <div class="info-details-value"><?= nl2br(htmlspecialchars($order['Note'], ENT_QUOTES, 'UTF-8'), false) ?></div>
                </div>
            <?php endif; ?>

            <!-- Thanh toán và vận chuyển -->
            <div class="detail-section-card">
                <h2 class="detail-section-title"><i class="fa-solid fa-credit-card" aria-hidden="true"></i>Thanh toán & Vận chuyển</h2>
                <ul class="info-details-list">
                    <li class="info-details-item">
                        <span class="info-details-label">Phương thức:</span>
                        <span class="info-details-value">
                            <?= $order['PaymentMethod'] === 'VNPAY' ? 'Thanh toán trực tuyến cổng VNPAY' : 'Thanh toán khi nhận hàng (COD)' ?>
                        </span>
                    </li>
                    <li class="info-details-item">
                        <span class="info-details-label">Trạng thái thanh toán:</span>
                        <span class="info-details-value">
                            <span class="badge <?= $order['PaymentStatus'] === 'Completed' ? 'badge--success' : 'badge--warning' ?>">
                                <?= getPaymentDisplayText($order['PaymentMethod'], $order['PaymentStatus']) ?>
                            </span>
                        </span>
                    </li>
                    <li class="info-details-item">
                        <span class="info-details-label">Trạng thái giao hàng:</span>
                        <span class="info-details-value">
                            <span class="badge <?= $order['DeliveryStatus'] === 'Delivered' ? 'badge--success' : 'badge--info' ?>">
                                <?= getDeliveryStatusText($order['DeliveryStatus']) ?>
                            </span>
                        </span>
                    </li>
                </ul>
            </div>

            <!-- Sản phẩm đã mua -->
            <div class="detail-section-card">
                <h2 class="detail-section-title"><i class="fa-solid fa-box-open" aria-hidden="true"></i>Sản phẩm đã mua</h2>
                <div class="table-wrapper">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Sản phẩm</th>
                                <th>Đơn giá</th>
                                <th>Số lượng</th>
                                <th class="text-right">Thành tiền</th>
                                <?php if ($order['OrderStatus'] === 'Delivered'): ?>
                                    <th class="text-center detail-review-column">Thao tác</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): 
                                $imgSrc = getProductImage($item['ImageURL'] ?? '');
                            ?>
                                <tr>
                                    <td>
                                        <div class="detail-product-link">
                                            <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($item['ProductName']) ?>" class="detail-product-img">
                                            <div>
                                                <div class="detail-product-name"><?= htmlspecialchars($item['ProductName']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= number_format($item['UnitPrice'], 0, ',', '.') ?> đ</td>
                                    <td><?= $item['Quantity'] ?></td>
                                    <td class="text-right detail-line-total">
                                        <?= number_format($item['Price'], 0, ',', '.') ?> đ
                                    </td>
                                    <?php if ($order['OrderStatus'] === 'Delivered'): ?>
                                        <td class="text-center detail-review-column">
                                            <a href="<?= url('trangchu/detail.php?id=' . $item['ProductID']) ?>#review-form-section" class="btn btn--outline btn--sm detail-review-button">
                                                <i class="fa-solid fa-star" aria-hidden="true"></i> Đánh giá
                                            </a>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Cột phải: Tổng tiền thanh toán -->
        <div class="order-detail-sidebar">
            <div class="detail-summary-card">
                <h2 class="detail-summary-title">Tóm tắt thanh toán</h2>

                <?php
                $productSubtotal = 0;
                foreach ($items as $item) {
                    $productSubtotal += (float) $item['Price'];
                }
                $shippingFee = (float) ($order['ShippingFee'] ?? 0);
                $finalTotal = (float) $order['TotalAmount'];
                $derivedVoucherDiscount = null;
                if ($order['VoucherID'] !== null) {
                    $candidateDiscount = $productSubtotal + $shippingFee - $finalTotal;
                    // The persisted total identifies the discount only when the order was not capped at zero.
                    if ($finalTotal > 0 && $candidateDiscount > 0) {
                        $derivedVoucherDiscount = $candidateDiscount;
                    }
                }
                ?>
                <div class="detail-summary-row">
                    <span>Subtotal sản phẩm</span>
                    <span><?= number_format($productSubtotal, 0, ',', '.') ?> đ</span>
                </div>

                <?php if ($order['VoucherID'] !== null): ?>
                    <div class="detail-summary-row detail-summary-row--discount">
                        <span>Giảm giá voucher</span>
                        <span>
                            <?php if ($derivedVoucherDiscount !== null): ?>
                                -<?= number_format($derivedVoucherDiscount, 0, ',', '.') ?> đ
                            <?php else: ?>
                                Không xác định từ dữ liệu lưu
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="detail-summary-row detail-summary-row--voucher-note">
                        <span>Mã giảm giá</span>
                        <span>#<?= (int) $order['VoucherID'] ?></span>
                    </div>
                <?php endif; ?>

                <div class="detail-summary-row">
                    <span>Phí vận chuyển</span>
                    <span><?= number_format($shippingFee, 0, ',', '.') ?> đ</span>
                </div>

                <div class="detail-summary-row detail-summary-row--total">
                    <span>Tổng tiền thanh toán</span>
                    <span class="detail-summary-value"><?= number_format($finalTotal, 0, ',', '.') ?> đ</span>
                </div>

                <div class="detail-summary-actions">
                    <a href="<?= url('cart/tracking.php?id=' . $orderId) ?>" class="btn btn--primary btn--block">
                        <i class="fa-solid fa-location-dot" aria-hidden="true"></i>Theo dõi đơn hàng
                    </a>
                    <a href="history.php" class="btn btn--ghost btn--block">
                        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>Quay lại lịch sử đơn
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</main>

<?php include '../includes/footer.php'; ?>
</body>
</html>
