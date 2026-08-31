<?php
require_once '../config/db.php';

$orderId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$inputPhone = isset($_GET['phone']) ? trim($_GET['phone']) : '';

$order = null;
$isAuthorized = false;
$verifyError = '';

if ($orderId > 0) {
    // Truy vấn thông tin đơn hàng
    $sql = "
        SELECT o.OrderID, o.CustomerID, o.OrderDate, o.ShippingAddress, o.OrderStatus, p.PaymentMethod 
        FROM `order` o
        LEFT JOIN `payment` p ON o.OrderID = p.OrderID
        WHERE o.OrderID = ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $orderId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows > 0) {
        $order = $res->fetch_assoc();
    }
    $stmt->close();
    
    if ($order) {
        $currentCustomerId = isset($_SESSION['user']) ? intval($_SESSION['user']['id']) : 0;
        
        // 1. Kiểm tra đối với đơn hàng thành viên
        if ($order['CustomerID'] !== null) {
            if ($order['CustomerID'] === $currentCustomerId) {
                $isAuthorized = true;
            } else {
                $verifyError = 'Bạn không có quyền truy cập thông tin đơn hàng này!';
            }
        } 
        // 2. Kiểm tra đối với đơn hàng khách vãng lai
        else {
            $guestInfo = getGuestInfoFromAddress($order['ShippingAddress']);
            $orderPhone = $guestInfo['phone'];
            
            // Xử lý nếu người dùng cung cấp SĐT qua GET
            if (!empty($inputPhone)) {
                if (preg_match('/^\d{10,11}$/', $inputPhone) &&
                    preg_match('/^\d{10,11}$/', $orderPhone) &&
                    $inputPhone === $orderPhone) {
                    $_SESSION['verified_orders'][$orderId] = true;
                    $_SESSION['guest_search_phone'] = $inputPhone;
                    $isAuthorized = true;
                } else {
                    $verifyError = 'Số điện thoại xác minh không chính xác!';
                }
            } else {
                // Kiểm tra các session đã lưu trước đó
                if (isset($_SESSION['verified_orders'][$orderId]) && $_SESSION['verified_orders'][$orderId] === true) {
                    $isAuthorized = true;
                } elseif (isset($_SESSION['guest_search_phone']) && $_SESSION['guest_search_phone'] === $orderPhone) {
                    $isAuthorized = true;
                } elseif (isset($_SESSION['guest_checkout']['phone']) && $_SESSION['guest_checkout']['phone'] === $orderPhone) {
                    $isAuthorized = true;
                }
            }
        }
    } else {
        $verifyError = 'Đơn hàng không tồn tại trên hệ thống!';
    }
}

// Xử lý POST xác minh số điện thoại cho khách vãng lai
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_guest_phone'])) {
    $postPhone = trim($_POST['verify_guest_phone']);
    if ($order && $order['CustomerID'] === null) {
        $guestInfo = getGuestInfoFromAddress($order['ShippingAddress']);
        $orderPhone = $guestInfo['phone'];
        if (preg_match('/^\d{10,11}$/', $postPhone) &&
            preg_match('/^\d{10,11}$/', $orderPhone) &&
            $postPhone === $orderPhone) {
            $_SESSION['verified_orders'][$orderId] = true;
            $_SESSION['guest_search_phone'] = $postPhone;
            $isAuthorized = true;
            $verifyError = '';
        } else {
            $verifyError = 'Số điện thoại xác minh không chính xác. Vui lòng thử lại!';
        }
    }
}

$pageTitle = ($order && $isAuthorized) ? 'Theo dõi đơn hàng #WBS-' . $orderId : 'Tra cứu hành trình đơn hàng';
$extraCss = ['css/cart.css'];
include '../includes/header.php';

// Các mức độ của hành trình giao hàng
$statusSteps = ['Pending', 'Processing', 'Shipped', 'Delivered'];
$currentStatus = ($order && $isAuthorized) ? $order['OrderStatus'] : '';
$currentIndex = array_search($currentStatus, $statusSteps);
if ($currentIndex === false && $currentStatus === 'Cancelled') {
    $currentIndex = -1; // Đơn hàng đã hủy
}
?>

<style>
    .order-tracking-page {
        --color-primary: rgb(0, 169, 242);
        --color-primary-hover: rgb(0, 135, 195);
        --color-secondary: #f9b234;
        --color-accent: rgb(0, 169, 242);
        --color-border: #dce8ef;
        --color-background: #f4f9fc;
        --color-text: #172033;
        --color-text-light: #64748b;
    }

    .order-tracking-page .form-control:focus {
        box-shadow: 0 0 0 3px rgba(0, 169, 242, 0.12);
    }

    .order-tracking-page .btn--ghost:hover {
        background-color: rgba(0, 169, 242, 0.08);
    }

    .order-tracking-page .timeline-item.is-active .timeline-badge {
        box-shadow: 0 0 0 4px rgba(0, 169, 242, 0.2);
        animation-name: tracking-pulse-blue;
    }

    @keyframes tracking-pulse-blue {
        0% {
            box-shadow: 0 0 0 0 rgba(0, 169, 242, 0.4);
        }

        70%,
        100% {
            box-shadow: 0 0 0 6px rgba(0, 169, 242, 0);
        }
    }
</style>

<main class="order-container order-tracking-page">
    <ul class="breadcrumbs">
        <li><a href="<?= url('/') ?>">Trang chủ</a></li>
        <li><a href="history.php">Lịch sử đơn hàng</a></li>
        <?php if ($order): ?>
            <li><a href="detail.php?id=<?= $orderId ?>">Chi tiết đơn #WBS-<?= $orderId ?></a></li>
            <li>Theo dõi đơn hàng</li>
        <?php else: ?>
            <li>Tra cứu hành trình đơn hàng</li>
        <?php endif; ?>
    </ul>

    <div class="order-title-section">
        <h1 class="order-title">Theo dõi hành trình đơn hàng</h1>
    </div>

    <?php if (!$order || ($order['CustomerID'] !== null && !$isAuthorized)): ?>
        <?php if ($orderId <= 0): ?>
            <!-- Form tra cứu nhanh trạng thái đơn hàng (yêu cầu ID + SĐT) -->
            <div class="tracking-lookup-card">
                <i class="fa-solid fa-map-location-dot tracking-lookup-card__icon" aria-hidden="true"></i>
                <h2 class="tracking-lookup-card__title">Tra cứu hành trình đơn hàng</h2>
                <p class="tracking-lookup-card__description">Nhập Mã đơn hàng và Số điện thoại đặt hàng để tra cứu hành trình vận chuyển.</p>
                
                <?php if (!empty($verifyError)): ?>
                    <div class="alert alert--error tracking-alert">
                        <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i><?= htmlspecialchars($verifyError) ?>
                    </div>
                <?php endif; ?>

                <form method="GET" action="tracking.php" class="tracking-lookup-form">
                <div>
                        <label class="form-label">Mã đơn hàng <span class="required-mark">*</span></label>
                        <input type="number" name="id" class="form-control" placeholder="Ví dụ: 1, 2..." required min="1">
                    </div>
                    <div>
                        <label class="form-label">Số điện thoại nhận hàng <?= isset($_SESSION['user']) ? '<span class="optional-mark">(Không bắt buộc với thành viên)</span>' : '<span class="required-mark">*</span>' ?></label>
                        <input type="text" name="phone" class="form-control" placeholder="Nhập số điện thoại đặt hàng..." <?= isset($_SESSION['user']) ? '' : 'required' ?> value="<?= htmlspecialchars($inputPhone) ?>">
                    </div>
                    <button type="submit" class="btn btn--primary">Tra cứu ngay</button>
                </form>
            </div>
        <?php else: ?>
            <!-- Báo lỗi không tìm thấy hoặc không có quyền truy cập -->
            <div class="tracking-error-card">
                <i class="fa-solid fa-triangle-exclamation tracking-error-card__icon" aria-hidden="true"></i>
                <h2 class="tracking-error-card__title">Không thể truy cập</h2>
                <p class="tracking-error-card__description"><?= !empty($verifyError) ? htmlspecialchars($verifyError) : 'Đơn hàng không tồn tại hoặc bạn không có quyền truy cập.' ?></p>
                <a href="tracking.php" class="btn btn--primary">Quay lại tra cứu</a>
            </div>
        <?php endif; ?>
    <?php elseif (!$isAuthorized): ?>
        <!-- Form xác minh số điện thoại cho khách vãng lai -->
        <div class="tracking-verify-card">
            <i class="fa-solid fa-user-shield tracking-verify-card__icon" aria-hidden="true"></i>
            <h2 class="tracking-verify-card__title">Xác minh đơn hàng</h2>
            <p class="tracking-verify-card__description">Đơn hàng này thuộc về <strong>Khách vãng lai</strong>. Vui lòng cung cấp số điện thoại mua hàng để tiếp tục theo dõi vận chuyển.</p>
            
            <?php if (!empty($verifyError)): ?>
                <div class="alert alert--error tracking-alert">
                    <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i><?= htmlspecialchars($verifyError) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="tracking-verify-form">
                <input type="text" name="verify_guest_phone" class="form-control text-center" placeholder="Nhập số điện thoại mua hàng..." required>
                <button type="submit" class="btn btn--primary">Xác minh & Theo dõi</button>
            </form>
        </div>
    <?php else: ?>
        <!-- Hiển thị thông tin hành trình đơn hàng cụ thể -->
        <div class="tracking-info-header">
            <div class="tracking-info-grid">
                <div>
                    <div class="tracking-info-item__label">Mã đơn hàng</div>
                    <div class="tracking-info-item__value tracking-info-item__value--order-id">
                        #WBS-<?= $order['OrderID'] ?>
                    </div>
                </div>
                <div>
                    <div class="tracking-info-item__label">Phương thức thanh toán</div>
                    <div class="tracking-info-item__value">
                        <?= $order['PaymentMethod'] === 'VNPAY' ? 'Thanh toán trực tuyến VNPAY' : 'Thanh toán COD khi nhận hàng' ?>
                    </div>
                </div>
                <div>
                    <div class="tracking-info-item__label">Ngày đặt hàng</div>
                    <div class="tracking-info-item__value">
                        <?= date('d/m/Y H:i', strtotime($order['OrderDate'])) ?>
                    </div>
                </div>
            </div>
            <div>
                <?php if ($currentStatus === 'Cancelled'): ?>
                    <span class="badge badge--error">Đơn hàng đã hủy</span>
                <?php else: ?>
                    <span class="badge badge--info badge--dot">Đang thực hiện</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="timeline-card">
            <h2 class="timeline-card__title">
                <i class="fa-solid fa-map-location-dot" aria-hidden="true"></i>Trạng thái vận chuyển chi tiết
            </h2>

            <div class="timeline">
                <?php if ($currentStatus === 'Cancelled'): ?>
                    <!-- Trạng thái hủy đơn hàng -->
                    <div class="timeline-item is-active">
                        <div class="timeline-badge timeline-badge--error"><i class="fa-solid fa-xmark" aria-hidden="true"></i></div>
                        <div class="timeline-content">
                            <div class="timeline-header">
                                <h3 class="timeline-title timeline-title--error">Đã hủy đơn hàng</h3>
                                <span class="timeline-time">Hành trình kết thúc</span>
                            </div>
                            <p class="timeline-desc">Đơn hàng này đã bị hủy bỏ trên hệ thống. Số lượng tồn kho sản phẩm đã được tự động hoàn lại.</p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Bước 4: Giao hàng thành công -->
                <?php 
                $isCompleted = ($currentIndex >= 3);
                $isActive = ($currentStatus === 'Delivered');
                ?>
                <div class="timeline-item <?= $isCompleted ? 'is-completed' : '' ?> <?= $isActive ? 'is-active' : '' ?>">
                    <div class="timeline-badge"><i class="fa-solid fa-check"></i></div>
                    <div class="timeline-content">
                        <div class="timeline-header">
                            <h3 class="timeline-title">Giao hàng thành công</h3>
                            <span class="timeline-time"><?= $isActive ? 'Hoàn tất' : 'Dự kiến' ?></span>
                        </div>
                        <p class="timeline-desc">Đơn hàng được bàn giao thành công và có chữ ký xác nhận của khách hàng.</p>
                    </div>
                </div>

                <!-- Bước 3: Đang vận chuyển -->
                <?php 
                $isCompleted = ($currentIndex >= 2);
                $isActive = ($currentStatus === 'Shipped');
                ?>
                <div class="timeline-item <?= $isCompleted ? 'is-completed' : '' ?> <?= $isActive ? 'is-active' : '' ?>">
                    <div class="timeline-badge"><i class="fa-solid fa-truck-fast"></i></div>
                    <div class="timeline-content">
                        <div class="timeline-header">
                            <h3 class="timeline-title">Đơn hàng đang trung chuyển</h3>
                            <span class="timeline-time"><?= $isActive ? 'Đang giao hàng' : '' ?></span>
                        </div>
                        <p class="timeline-desc">Hàng đã được bàn giao cho đơn vị vận chuyển GHN Express và đang trên đường trung chuyển tới địa chỉ nhận của bạn.</p>
                    </div>
                </div>

                <!-- Bước 2: Đang đóng gói -->
                <?php 
                $isCompleted = ($currentIndex >= 1);
                $isActive = ($currentStatus === 'Processing');
                ?>
                <div class="timeline-item <?= $isCompleted ? 'is-completed' : '' ?> <?= $isActive ? 'is-active' : '' ?>">
                    <div class="timeline-badge"><i class="fa-solid fa-box"></i></div>
                    <div class="timeline-content">
                        <div class="timeline-header">
                            <h3 class="timeline-title">Nhà sách đang đóng gói hàng</h3>
                            <span class="timeline-time"><?= $isActive ? 'Đang chuẩn bị' : '' ?></span>
                        </div>
                        <p class="timeline-desc">Nhân viên kho đang tiến hành lấy sách, đóng gói chống sốc bảo vệ sản phẩm và dán mã vận đơn giao nhận.</p>
                    </div>
                </div>

                <!-- Bước 1: Đặt hàng thành công -->
                <?php 
                $isCompleted = ($currentIndex >= 0);
                $isActive = ($currentStatus === 'Pending');
                ?>
                <div class="timeline-item <?= $isCompleted ? 'is-completed' : '' ?> <?= $isActive ? 'is-active' : '' ?>">
                    <div class="timeline-badge"><i class="fa-solid fa-check"></i></div>
                    <div class="timeline-content">
                        <div class="timeline-header">
                            <h3 class="timeline-title">Đặt hàng thành công</h3>
                            <span class="timeline-time"><?= date('d/m/Y H:i', strtotime($order['OrderDate'])) ?></span>
                        </div>
                        <p class="timeline-desc">Đơn hàng được ghi nhận thành công vào hệ thống. Chờ bộ phận quản trị nhà sách duyệt đơn xác nhận đóng gói.</p>
                    </div>
                </div>
            </div>

            <!-- Điều hướng -->
            <div class="tracking-actions">
                <a href="<?= url('cart/detail.php?id=' . $orderId) ?>" class="btn btn--outline">
                    <i class="fa-solid fa-file-invoice" aria-hidden="true"></i>Xem chi tiết đơn hàng
                </a>
                <a href="history.php" class="btn btn--ghost">
                    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>Quay lại danh sách đơn hàng
                </a>
            </div>
        </div>
    <?php endif; ?>
</main>

<?php include '../includes/footer.php'; ?>
</body>
</html>
