<?php
require_once '../config/db.php';

if (!isset($_SESSION['user'])) {
    $_SESSION['redirect_after_login'] = url('cart/checkout.php');
    header('Location: ' . url('auth/pages/login.php'));
    exit;
}

$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    $_SESSION['warning'] = 'Giỏ hàng của bạn đang trống. Vui lòng thêm sản phẩm trước khi thanh toán!';
    header('Location: ' . url('cart/cart.php'));
    exit;
}

$pageTitle = 'Thanh toán đơn hàng';
$extraCss = ['css/cart.css'];

if (empty($_SESSION['checkout_csrf_token'])) {
    $_SESSION['checkout_csrf_token'] = bin2hex(random_bytes(32));
}

include '../includes/header.php';

// Tính toán tổng tiền đơn hàng
$cartProducts = [];
$totalAmount = 0;
$shippingFee = 0;

$productIds = array_keys($cart);
$placeholders = implode(',', array_fill(0, count($productIds), '?'));
$sql = "
    SELECT p.ProductID, p.ProductName, p.Price AS OriginalPrice, p.Quantity AS Stock, i.ImageURL,
           ap.DiscountRate
    FROM product p
    LEFT JOIN image i ON p.ProductID = i.ProductID AND i.IsThumbnail = 1
    LEFT JOIN (
        SELECT pd.ProductID, MAX(pd.DiscountRate) AS DiscountRate
        FROM promotion_detail pd
        JOIN promotion pr ON pd.PromotionID = pr.PromotionID
        WHERE NOW() BETWEEN COALESCE(pd.StartDate, pr.StartDate) AND COALESCE(pd.EndDate, pr.EndDate)
        GROUP BY pd.ProductID
    ) ap ON p.ProductID = ap.ProductID
    WHERE p.ProductID IN ($placeholders)
";
$stmt = $conn->prepare($sql);
$types = str_repeat('i', count($productIds));
$stmt->bind_param($types, ...$productIds);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $qty = $cart[$row['ProductID']];
    
    // Tính toán giá khuyến mãi
    $discountRate = isset($row['DiscountRate']) ? floatval($row['DiscountRate']) : 0;
    $row['Price'] = $row['OriginalPrice'] - ($row['OriginalPrice'] * $discountRate / 100);
    
    $row['CartQuantity'] = $qty;
    $row['Subtotal'] = $row['Price'] * $qty;
    $totalAmount += $row['Subtotal'];
    $cartProducts[] = $row;
}
$stmt->close();

// Kiểm tra thông tin người dùng đã đăng nhập hoặc thông tin đã lưu trong Cookie
$fullName = '';
$phone = '';
$address = '';
$saveInfoChecked = false;

if (isset($_SESSION['user'])) {
    $fullName = $_SESSION['user']['full_name'] ?? '';
    $phone = $_SESSION['user']['phone'] ?? '';
    $address = $_SESSION['user']['address'] ?? '';
} elseif (isset($_COOKIE['guest_checkout'])) {
    $guestData = json_decode($_COOKIE['guest_checkout'], true);
    if ($guestData) {
        $fullName = $guestData['fullname'] ?? '';
        $phone = $guestData['phone'] ?? '';
        $address = $guestData['address'] ?? '';
        $saveInfoChecked = true;
    }
}
?>

<style>
    .checkout-grid {
        display: grid;
        grid-template-columns: 1.4fr 1fr;
        gap: var(--spacing-lg);
        margin-top: var(--spacing-md);
    }
    @media (max-width: 992px) {
        .checkout-grid {
            grid-template-columns: 1fr;
        }
    }
    .form-card {
        background: var(--color-surface);
        border: var(--border-width) solid var(--color-border);
        border-radius: var(--border-radius-lg);
        padding: var(--spacing-lg);
        box-shadow: var(--box-shadow-sm);
        margin-bottom: var(--spacing-md);
    }
    .form-card-title {
        font-size: var(--font-size-lg);
        font-weight: var(--font-weight-bold);
        color: var(--color-text);
        margin: 0 0 var(--spacing-md) 0;
        border-bottom: 2px solid var(--color-background);
        padding-bottom: var(--spacing-sm);
    }
    .payment-option {
        display: flex;
        align-items: center;
        gap: var(--spacing-sm);
        padding: var(--spacing-md);
        border: var(--border-width) solid var(--color-border);
        border-radius: var(--border-radius);
        margin-bottom: var(--spacing-sm);
        cursor: pointer;
        transition: all var(--transition-fast);
        background: var(--color-background);
    }
    .payment-option:hover {
        border-color: var(--color-primary);
    }
    .payment-option input[type="radio"] {
        width: 18px;
        height: 18px;
        margin: 0;
        cursor: pointer;
    }
    .payment-option-label {
        font-weight: var(--font-weight-bold);
        font-size: var(--font-size-md);
        cursor: pointer;
        flex: 1;
    }
    .checkout-summary-item {
        display: flex;
        align-items: center;
        gap: var(--spacing-sm);
        padding: var(--spacing-sm) 0;
        border-bottom: 1px dashed var(--color-border);
    }
    .checkout-summary-img {
        width: 40px;
        height: 55px;
        object-fit: contain;
        border-radius: var(--border-radius-sm);
        border: 1px solid var(--color-border);
        background: var(--color-background);
    }
    .checkout-summary-name {
        flex: 1;
        font-size: 0.9rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        color: var(--color-text);
    }
    .checkout-summary-qty-price {
        text-align: right;
        font-size: 0.9rem;
        color: var(--color-text-light);
    }

    /* Checkout-only palette overrides from the UI reference. */
    .checkout-page {
        --color-primary: rgb(0, 169, 242);
        --color-primary-dark: rgb(0, 135, 195);
        --color-accent: rgb(0, 169, 242);
    }

    .checkout-page .breadcrumbs a:hover,
    .checkout-page .breadcrumbs li:last-child,
    .checkout-page .order-title i,
    .checkout-page .form-card-title i {
        color: var(--color-primary);
    }

    .checkout-page .form-card,
    .checkout-page .detail-summary-card {
        border-color: #e2e8f0;
        background: #fff;
        box-shadow: 0 6px 20px rgba(15, 23, 42, 0.06);
    }

    .checkout-page .form-card-title,
    .checkout-page .detail-summary-title {
        border-bottom-color: #e7eef3;
    }

    .checkout-page .form-control:focus {
        border-color: var(--color-primary);
        box-shadow: 0 0 0 3px rgba(0, 169, 242, 0.12);
    }

    .checkout-page .payment-option {
        border-color: #dbe3ea;
        background: #f8fbfd;
    }

    .checkout-page .payment-option:hover,
    .checkout-page .payment-option:focus-within,
    .checkout-page .payment-option:has(input:checked) {
        border-color: var(--color-primary);
        background: rgba(0, 169, 242, 0.06);
        box-shadow: 0 0 0 2px rgba(0, 169, 242, 0.08);
    }

    .checkout-page .payment-option input[type="radio"] {
        accent-color: var(--color-primary);
    }

    .checkout-page .checkout-summary-img {
        border-color: #e2e8f0;
        background: #f8fbfd;
    }

    .checkout-page .detail-summary-row--total .detail-summary-value {
        color: var(--color-primary);
    }

    .checkout-page .btn--primary {
        border-color: var(--color-primary);
        background: var(--color-primary);
    }

    .checkout-page .btn--primary:hover {
        border-color: var(--color-primary-dark);
        background: var(--color-primary-dark);
    }

    .checkout-page .btn--outline {
        border-color: var(--color-primary);
        color: var(--color-primary);
    }

    .header-search:focus-within {
        border-color: rgb(0, 169, 242);
        box-shadow: 0 0 0 3px rgba(0, 169, 242, 0.12);
    }

    .header-search button { background: rgb(0, 169, 242); }
    .header-search button:hover { background: rgb(0, 135, 195); }

    @media (max-width: 480px) {
        .checkout-page .form-card,
        .checkout-page .detail-summary-card {
            padding: var(--spacing-md);
        }

        .checkout-page .payment-option {
            align-items: flex-start;
            padding: var(--spacing-sm);
        }
    }
</style>

<main class="order-container checkout-page">
    <ul class="breadcrumbs">
        <li><a href="<?= url('/') ?>">Trang chủ</a></li>
        <li><a href="<?= url('cart/cart.php') ?>">Giỏ hàng</a></li>
        <li>Thanh toán</li>
    </ul>

    <div class="order-title-section">
        <h1 class="order-title"><i class="fa-regular fa-credit-card" style="margin-right: 10px;"></i> Tiến hành đặt nhận hàng & Thanh toán</h1>
    </div>

    <form action="process_checkout.php" method="POST" class="checkout-grid">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['checkout_csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
        <!-- Cột trái: Thông tin nhận hàng & Thanh toán -->
        <div>
            <!-- Form nhận hàng -->
            <div class="form-card">
                <h2 class="form-card-title"><i class="fa-regular fa-user" style="margin-right: 8px;"></i> Thông tin nhận hàng</h2>
                
                <div class="form-group">
                    <label class="form-label" for="fullname" style="font-weight: bold;">Họ và tên người nhận <span style="color: var(--color-error);">*</span></label>
                    <input type="text" id="fullname" name="fullname" class="form-control" placeholder="Nhập họ tên đầy đủ..." required value="<?= htmlspecialchars($fullName) ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="phone" style="font-weight: bold;">Số điện thoại liên hệ <span style="color: var(--color-error);">*</span></label>
                    <input type="tel" id="phone" name="phone" class="form-control" placeholder="Nhập số điện thoại..." required value="<?= htmlspecialchars($phone) ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="address" style="font-weight: bold;">Địa chỉ giao hàng đầy đủ <span style="color: var(--color-error);">*</span></label>
                    <textarea id="address" name="address" class="form-control" style="min-height: 80px;" placeholder="Số nhà, tên đường, phường/xã, quận/huyện, tỉnh/thành..." required><?= htmlspecialchars($address) ?></textarea>
                </div>

                <?php if (!isset($_SESSION['user'])): ?>
                    <div class="form-group" style="display: flex; align-items: center; gap: var(--spacing-xs); margin-top: 10px;">
                        <input type="checkbox" name="save_info" id="save_info" value="1" <?= $saveInfoChecked ? 'checked' : '' ?> style="width: 18px; height: 18px; cursor: pointer;">
                        <label for="save_info" style="cursor: pointer; font-size: 0.9rem; color: var(--color-text-light); margin: 0;">Lưu thông tin giao hàng cho lần mua sau</label>
                    </div>
                <?php endif; ?>

                <div class="form-group" style="margin-top: var(--spacing-md);">
                    <label class="form-label" for="note" style="font-weight: bold;">Ghi chú giao hàng (Không bắt buộc)</label>
                    <textarea id="note" name="note" class="form-control" style="min-height: 60px;" placeholder="Ví dụ: Giao giờ hành chính, gọi trước khi giao..."></textarea>
                </div>
            </div>

            <div class="form-card">
                <h2 class="form-card-title"><i class="fa-solid fa-wallet" style="margin-right: 8px;"></i> Phương thức thanh toán</h2>
                
                <label class="payment-option">
                    <input type="radio" name="payment_method" value="COD" checked>
                    <div class="payment-option-label">
                        <i class="fa-solid fa-house-chimney-user" style="margin-right: 6px; color: var(--color-primary);"></i> Thanh toán khi nhận hàng (COD)
                        <div style="font-weight: normal; font-size: 0.85rem; color: var(--color-text-light); margin-top: 4px;">
                            Bạn sẽ trả tiền mặt trực tiếp cho shipper khi nhận được hàng.
                        </div>
                    </div>
                </label>

                <label class="payment-option">
                    <input type="radio" name="payment_method" value="VNPAY">
                    <div class="payment-option-label">
                        <i class="fa-regular fa-credit-card" style="margin-right: 6px; color: var(--color-primary);"></i> Thanh toán trực tuyến qua cổng VNPAY
                        <div style="font-weight: normal; font-size: 0.85rem; color: var(--color-text-light); margin-top: 4px;">
                            Hỗ trợ thẻ ATM nội địa, ứng dụng ngân hàng quét mã QR Code, thẻ quốc tế (Visa/Master).
                        </div>
                    </div>
                </label>
            </div>
        </div>

        <!-- Cột phải: Tóm tắt giỏ hàng & Đơn đặt -->
        <div>
            <div class="detail-summary-card">
                <h2 class="detail-summary-title">Tóm tắt đơn hàng</h2>
                
                <!-- Danh sách sản phẩm rút gọn -->
                <div style="max-height: 220px; overflow-y: auto; margin-bottom: var(--spacing-md); padding-right: var(--spacing-xs);">
                    <?php foreach ($cartProducts as $item): ?>
                        <div class="checkout-summary-item">
                            <?php 
                            $imgSrc = getProductImage($item['ImageURL'] ?? ''); 
                            ?>
                            <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($item['ProductName']) ?>" class="checkout-summary-img">
                            
                            <div class="checkout-summary-name">
                                <?= htmlspecialchars($item['ProductName']) ?>
                            </div>
                            
                            <div class="checkout-summary-qty-price">
                                <div><?= number_format($item['Price'], 0, ',', '.') ?> đ</div>
                                <div>x <?= $item['CartQuantity'] ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="detail-summary-row">
                    <span>Tạm tính tiền hàng</span>
                    <span><?= number_format($totalAmount, 0, ',', '.') ?> đ</span>
                </div>

                <?php 
                $voucherDiscount = 0;
                $appliedVoucher = $_SESSION['applied_voucher'] ?? null;
                if ($appliedVoucher && isset($_SESSION['user'])) {
                    $voucherDiscount = floatval($appliedVoucher['value']);
                } else {
                    unset($_SESSION['applied_voucher']);
                    $appliedVoucher = null;
                }
                
                $finalTotal = max(0, $totalAmount + $shippingFee - $voucherDiscount);
                ?>

                <?php if ($appliedVoucher): ?>
                    <div class="detail-summary-row" style="color: #2e7d32; font-weight: bold;">
                        <span style="display: flex; align-items: center; gap: 4px;">
                            <i class="fa-solid fa-ticket"></i> Voucher (<?= htmlspecialchars($appliedVoucher['code']) ?>)
                        </span>
                        <span style="display: flex; align-items: center; gap: 8px;">
                            -<?= number_format($voucherDiscount, 0, ',', '.') ?> đ
                            <button type="submit" form="voucher-remove-form" style="background: none; border: none; color: var(--color-error); cursor: pointer; padding: 0; font-size: 0.95rem;" title="Gỡ mã">
                                <i class="fa-solid fa-circle-xmark"></i>
                            </button>
                        </span>
                    </div>
                <?php endif; ?>
                
                <div class="detail-summary-row">
                    <span>Phí vận chuyển</span>
                    <span><?= number_format($shippingFee, 0, ',', '.') ?> đ</span>
                </div>
                
                <div class="detail-summary-row detail-summary-row--total" style="margin-top: 10px; padding-top: 10px;">
                    <span>Tổng số tiền cần trả</span>
                    <span class="detail-summary-value"><?= number_format($finalTotal, 0, ',', '.') ?> đ</span>
                </div>

                <!-- Khu vực nhập Voucher phụ tại trang thanh toán -->
                <div style="border-top: 1px dashed var(--color-border); margin-top: var(--spacing-md); padding-top: var(--spacing-md);">
                    <?php if (isset($_SESSION['user'])): ?>
                        <div style="display: flex; gap: var(--spacing-xs); margin-bottom: 8px;">
                            <input type="text" name="voucher_code" form="voucher-form" class="form-control" placeholder="Nhập mã giảm giá..." style="padding: 8px 12px; font-size: 0.9rem; margin-bottom: 0;" required value="<?= $appliedVoucher ? htmlspecialchars($appliedVoucher['code']) : '' ?>">
                            <button type="submit" form="voucher-form" class="btn btn--primary" style="padding: 0 16px; font-size: 0.9rem; font-weight: bold; white-space: nowrap; height: 38px;">
                                <?= $appliedVoucher ? 'Thay đổi' : 'Áp dụng' ?>
                            </button>
                        </div>
                        <?php if ($appliedVoucher): ?>
                            <div style="font-size: 0.85rem; color: #2e7d32; display: flex; align-items: center; gap: 4px; margin-top: 4px;">
                                <i class="fa-solid fa-circle-check"></i> Đang áp dụng mã: <strong><?= htmlspecialchars($appliedVoucher['code']) ?></strong> (-<?= number_format($voucherDiscount, 0, ',', '.') ?> đ)
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div style="font-size: 0.85rem; color: var(--color-text-light); text-align: center; background: var(--color-background); padding: var(--spacing-sm); border-radius: var(--border-radius-sm); border: 1px dashed var(--color-border);">
                            <i class="fa-solid fa-lock" style="margin-right: 4px;"></i> Vui lòng <a href="<?= url('auth/pages/login.php') ?>" style="color: var(--color-primary); font-weight: bold; text-decoration: none;">Đăng nhập</a> để sử dụng mã giảm giá.
                        </div>
                    <?php endif; ?>
                </div>

                <div class="detail-summary-actions" style="margin-top: var(--spacing-lg);">
                    <button type="submit" class="btn btn--primary btn--block" style="padding: 14px 0; font-size: 1.1rem; font-weight: bold; cursor: pointer; border: none; display: flex; align-items: center; justify-content: center; gap: 8px;">
                        <i class="fa-solid fa-paper-plane"></i> Đặt Hàng Ngay
                    </button>
                    <a href="<?= url('cart/cart.php') ?>" class="btn btn--outline btn--block" style="text-align: center; text-decoration: none; padding: 10px 0; display: flex; align-items: center; justify-content: center; gap: 6px;">
                        <i class="fa-solid fa-arrow-left-long"></i> Quay lại giỏ hàng
                    </a>
                </div>
            </div>
        </div>
    </form>

    <!-- Các form ẩn xử lý mã giảm giá nằm ngoài form đặt hàng chính -->
    <form id="voucher-form" action="<?= url('cart/apply_voucher.php') ?>" method="POST" style="display: none;">
        <input type="hidden" name="action" value="apply">
    </form>
    <form id="voucher-remove-form" action="<?= url('cart/apply_voucher.php') ?>" method="POST" style="display: none;">
        <input type="hidden" name="action" value="remove">
    </form>
</main>

<?php include '../includes/footer.php'; ?>
</body>
</html>
