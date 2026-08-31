<?php
require_once '../config/db.php';

$pageTitle = 'Lịch sử đơn hàng';
$extraCss = ['css/cart.css'];
include '../includes/header.php';

// Trạng thái lọc từ URL
$statusFilter = isset($_GET['status']) ? trim($_GET['status']) : 'all';
$searchPhone = isset($_GET['search_phone']) ? trim($_GET['search_phone']) : ($_SESSION['guest_search_phone'] ?? '');

$orders = [];
$customerId = isset($_SESSION['user']) ? intval($_SESSION['user']['id']) : 0;
$guestPhoneError = '';

// Xử lý truy vấn đơn hàng
if ($customerId > 0) {
    // Xóa session tra cứu khách vãng lai nếu đã đăng nhập thành viên
    unset($_SESSION['guest_search_phone']);
    
    // Người dùng đăng nhập
    $sql = "SELECT o.OrderID, o.OrderDate, o.ShippingAddress, o.OrderStatus, o.TotalAmount, p.PaymentStatus, p.PaymentMethod 
            FROM `order` o
            LEFT JOIN `payment` p ON o.OrderID = p.OrderID
            WHERE o.CustomerID = ?";
    
    if ($statusFilter !== 'all') {
        $sql .= " AND o.OrderStatus = ?";
    }
    $sql .= " ORDER BY o.OrderDate DESC";
    
    $stmt = $conn->prepare($sql);
    if ($statusFilter !== 'all') {
        $stmt->bind_param("is", $customerId, $statusFilter);
    } else {
        $stmt->bind_param("i", $customerId);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    $stmt->close();
} elseif (!empty($searchPhone)) {
    if (!preg_match('/^\d{10,11}$/', $searchPhone)) {
        $guestPhoneError = 'Số điện thoại phải gồm 10–11 chữ số.';
    } else {
    // Lưu SĐT tra cứu của khách vãng lai vào session
    $_SESSION['guest_search_phone'] = $searchPhone;
    
    // Khách vãng lai tra cứu bằng số điện thoại
    $phonePattern = "%SĐT: " . $searchPhone . " |%";
    $sql = "SELECT o.OrderID, o.OrderDate, o.ShippingAddress, o.OrderStatus, o.TotalAmount, p.PaymentStatus, p.PaymentMethod 
            FROM `order` o
            LEFT JOIN `payment` p ON o.OrderID = p.OrderID
            WHERE o.CustomerID IS NULL AND o.ShippingAddress LIKE ?";
            
    if ($statusFilter !== 'all') {
        $sql .= " AND o.OrderStatus = ?";
    }
    $sql .= " ORDER BY o.OrderDate DESC";
    
    $stmt = $conn->prepare($sql);
    if ($statusFilter !== 'all') {
        $stmt->bind_param("ss", $phonePattern, $statusFilter);
    } else {
        $stmt->bind_param("s", $phonePattern);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    $stmt->close();
    }
}

// Lấy danh sách chi tiết sản phẩm cho các đơn hàng tìm thấy
$orderDetails = [];
if (!empty($orders)) {
    $orderIds = array_column($orders, 'OrderID');
    $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
    
    $sqlDetails = "
        SELECT od.OrderID, od.ProductID, od.Quantity, od.Price, od.UnitPrice, p.ProductName, i.ImageURL 
        FROM `order_detail` od
        JOIN `product` p ON od.ProductID = p.ProductID
        LEFT JOIN `image` i ON p.ProductID = i.ProductID AND i.IsThumbnail = 1
        WHERE od.OrderID IN ($placeholders)
    ";
    
    $stmtD = $conn->prepare($sqlDetails);
    $types = str_repeat('i', count($orderIds));
    $stmtD->bind_param($types, ...$orderIds);
    $stmtD->execute();
    $resD = $stmtD->get_result();
    
    while ($detail = $resD->fetch_assoc()) {
        $orderDetails[$detail['OrderID']][] = $detail;
    }
    $stmtD->close();
}

// Trực quan hóa tên trạng thái
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'Pending': return 'badge--info';
        case 'Processing': return 'badge--warning';
        case 'Shipped': return 'badge--primary';
        case 'Delivered': return 'badge--success';
        case 'Cancelled': return 'badge--error';
        default: return 'badge--secondary';
    }
}

function getStatusText($status) {
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
            case 'Completed': return 'Đã thanh toán COD';
            case 'Failed': return 'Thanh toán COD thất bại';
            case 'Refunded': return 'Đã hoàn tiền COD';
            case 'Pending': return 'Thanh toán khi nhận hàng';
            default: return 'Trạng thái COD chưa rõ';
        }
    }

    if ($method === 'VNPAY') {
        switch ($status) {
            case 'Completed': return 'Đã thanh toán online';
            case 'Failed': return 'Thanh toán thất bại';
            case 'Refunded': return 'Đã hoàn tiền';
            case 'Pending': return 'Chờ thanh toán online';
            default: return 'Trạng thái thanh toán online chưa rõ';
        }
    }

    return 'Phương thức thanh toán chưa rõ';
}
?>

<style>
    .history-page {
        --history-primary: rgb(0, 169, 242);
        --history-primary-dark: rgb(0, 135, 195);
        font-family: "Times New Roman", Times, serif;
    }

    .history-page .breadcrumbs a:hover,
    .history-page .breadcrumbs li:last-child,
    .history-page .order-title__icon,
    .history-page .order-guest-search__title {
        color: var(--history-primary);
    }

    .history-page .order-guest-search__form .form-control:focus {
        border-color: var(--history-primary);
        box-shadow: 0 0 0 3px rgba(0, 169, 242, 0.12);
    }

    .history-page .order-guest-search__form .btn--primary,
    .history-page .order-card__footer .btn--primary,
    .history-page .order-empty-state__action {
        background: var(--history-primary);
        border-color: var(--history-primary);
        color: #fff;
    }

    .history-page .order-guest-search__form .btn--primary:hover,
    .history-page .order-card__footer .btn--primary:hover,
    .history-page .order-empty-state__action:hover {
        background: var(--history-primary-dark);
        border-color: var(--history-primary-dark);
    }

    .history-page .order-tabs {
        border-bottom-color: #e2e8f0;
    }

    .history-page .order-tab-item:hover,
    .history-page .order-tab-item.is-active {
        color: var(--history-primary);
    }

    .history-page .order-tab-item.is-active {
        border-bottom-color: var(--history-primary);
    }

    .history-page .order-card__product-price-current,
    .history-page .order-card__total-price {
        color: var(--history-primary);
    }

    .history-page .order-card__total-price {
        font-size: 1rem;
        font-weight: 600;
    }

    .history-page .order-card__footer .btn--outline {
        border-color: var(--history-primary);
        color: var(--history-primary);
    }

    .history-page .order-card__footer .btn--outline:hover {
        background: rgba(0, 169, 242, 0.08);
    }

    .history-page .order-empty-state__icon {
        color: rgba(0, 169, 242, 0.45);
    }

    @media (max-width: 480px) {
        .history-page .order-guest-search__form {
            flex-direction: column;
        }

        .history-page .order-guest-search__form .btn {
            width: 100%;
        }
    }
</style>

<main class="order-container history-page">
    <ul class="breadcrumbs">
        <li><a href="<?= url('/') ?>">Trang chủ</a></li>
        <?php if ($customerId > 0): ?>
            <li><a href="<?= url('auth/pages/profile.php') ?>">Tài khoản</a></li>
        <?php endif; ?>
        <li>Lịch sử đơn hàng</li>
    </ul>

    <div class="order-title-section">
        <h1 class="order-title"><i class="fa-solid fa-clipboard-list order-title__icon" aria-hidden="true"></i>Lịch sử đơn hàng</h1>
    </div>

    <!-- Giao diện tra cứu cho Khách vãng lai -->
    <?php if ($customerId <= 0): ?>
        <div class="order-guest-search">
            <h3 class="order-guest-search__title"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>Tra cứu đơn hàng của Khách vãng lai</h3>
            <p class="order-guest-search__description">Vui lòng nhập số điện thoại bạn dùng khi đặt hàng để kiểm tra lịch sử và hành trình đơn hàng.</p>
            <form method="GET" class="order-guest-search__form">
                <input type="text" name="search_phone" class="form-control" placeholder="Nhập số điện thoại đặt hàng..." required value="<?= htmlspecialchars($searchPhone) ?>">
                <button type="submit" class="btn btn--primary">Tra cứu</button>
            </form>
            <?php if ($guestPhoneError): ?>
                <p class="form-error" role="alert"><?= htmlspecialchars($guestPhoneError) ?></p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Bộ lọc trạng thái đơn hàng -->
    <?php if ($customerId > 0 || !empty($searchPhone)): ?>
        <div class="order-tabs">
            <?php 
            $statuses = [
                'all' => 'Tất cả',
                'Pending' => 'Chờ xác nhận',
                'Processing' => 'Đang đóng gói',
                'Shipped' => 'Đang giao',
                'Delivered' => 'Đã giao',
                'Cancelled' => 'Đã hủy'
            ];
            foreach ($statuses as $val => $label): 
                $activeClass = ($statusFilter === $val) ? 'is-active' : '';
                $queryStr = "?status=" . $val;
                if ($customerId <= 0 && !empty($searchPhone)) {
                    $queryStr .= "&search_phone=" . urlencode($searchPhone);
                }
            ?>
                <a href="<?= $queryStr ?>" class="order-tab-item <?= $activeClass ?>"><?= $label ?></a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Danh sách đơn hàng -->
    <div class="order-card-list">
        <?php if (!empty($orders)): ?>
            <?php foreach ($orders as $order): 
                $oId = $order['OrderID'];
                $items = $orderDetails[$oId] ?? [];
                $totalQty = array_sum(array_column($items, 'Quantity'));
            ?>
                <div class="order-card">
                    <div class="order-card__header">
                        <div class="order-card__header-left">
                            <span class="order-card__id">Đơn hàng: #WBS-<?= $oId ?></span>
                            <span class="order-card__date">Đặt ngày: <?= date('d/m/Y H:i', strtotime($order['OrderDate'])) ?></span>
                        </div>
                        <div class="order-card__status-group">
                            <span class="badge <?= getStatusBadgeClass($order['OrderStatus']) ?> badge--dot">
                                <?= getStatusText($order['OrderStatus']) ?>
                            </span>
                            <span class="badge badge--secondary">
                                <?= htmlspecialchars(getPaymentDisplayText($order['PaymentMethod'], $order['PaymentStatus'])) ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="order-card__products">
                        <?php foreach ($items as $item): 
                            $imgSrc = getProductImage($item['ImageURL'] ?? '');
                        ?>
                            <div class="order-card__product-row">
                                <a href="<?= url('cart/detail.php?id=' . $oId) ?>" class="order-card__product">
                                    <img class="order-card__product-img" src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($item['ProductName']) ?>">
                                    <div class="order-card__product-info">
                                        <h3 class="order-card__product-title"><?= htmlspecialchars($item['ProductName']) ?></h3>
                                        <span class="order-card__product-meta">Đơn giá: <?= number_format($item['UnitPrice'], 0, ',', '.') ?> đ</span>
                                    </div>
                                    <div class="order-card__product-price">
                                        <span class="order-card__product-price-current"><?= number_format($item['Price'], 0, ',', '.') ?> đ</span>
                                        <div class="order-card__product-price-qty">x <?= $item['Quantity'] ?></div>
                                    </div>
                                </a>
                                <?php if ($order['OrderStatus'] === 'Delivered'): ?>
                                    <div class="order-card__review-action">
                                        <a href="<?= url('trangchu/detail.php?id=' . $item['ProductID']) ?>#review-form-section" class="btn btn--outline btn--sm order-card__review-button">
                                            <i class="fa-solid fa-star" aria-hidden="true"></i> Đánh giá
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="order-card__footer">
                        <span class="order-card__summary">
                            Tổng cộng: <strong><?= $totalQty ?> sản phẩm</strong>
                            <span class="order-card__total-price">Tổng thanh toán: <?= number_format($order['TotalAmount'], 0, ',', '.') ?> đ</span>
                        </span>
                        <div class="btn-group">
                            <a href="<?= url('cart/detail.php?id=' . $oId) ?>" class="btn btn--outline btn--sm">Chi tiết đơn</a>
                            <a href="<?= url('cart/tracking.php?id=' . $oId) ?>" class="btn btn--primary btn--sm">Theo dõi vận chuyển</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="order-empty-state">
                <i class="fa-solid fa-folder-open order-empty-state__icon" aria-hidden="true"></i>
                <?php if ($customerId <= 0 && empty($searchPhone)): ?>
                    <h3>Vui lòng nhập số điện thoại để tra cứu</h3>
                    <p class="text-muted">Nhập thông tin tại ô tra cứu phía trên.</p>
                <?php else: ?>
                    <h3>Không tìm thấy đơn hàng nào!</h3>
                    <p class="text-muted">Bạn chưa thực hiện giao dịch nào hoặc đơn hàng thuộc trạng thái khác.</p>
                    <a href="<?= url('trangchu/index.php') ?>" class="btn btn--primary order-empty-state__action">Mua sách ngay</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
</body>
</html>
