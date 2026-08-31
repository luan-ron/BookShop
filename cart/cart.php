<?php
require_once '../config/db.php';

$pageTitle = 'Giỏ hàng của tôi';
$extraCss = ['css/cart.css'];
include '../includes/header.php';

// Khởi tạo biến giỏ hàng và danh sách sản phẩm
$cart = $_SESSION['cart'] ?? [];
$cartProducts = [];
$totalAmount = 0;
$shippingFee = 0; // Phí giao hàng mặc định 30.000đ

if (!empty($cart)) {
    $productIds = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));
    
    $sql = "
        SELECT p.ProductID, p.ProductName, p.Price AS OriginalPrice, p.Quantity AS Stock, p.Status, i.ImageURL,
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
        // Đồng bộ lại số lượng giỏ hàng nếu vượt quá tồn kho thực tế
        $qty = $cart[$row['ProductID']];
        if ($qty > $row['Stock']) {
            $qty = $row['Stock'];
            $_SESSION['cart'][$row['ProductID']] = $qty;
        }
        
        // Tính toán giá khuyến mãi
        $discountRate = isset($row['DiscountRate']) ? floatval($row['DiscountRate']) : 0;
        $row['Price'] = $row['OriginalPrice'] - ($row['OriginalPrice'] * $discountRate / 100);
        
        $row['CartQuantity'] = $qty;
        $row['Subtotal'] = $row['Price'] * $qty;
        $totalAmount += $row['Subtotal'];
        $cartProducts[] = $row;
    }
    $stmt->close();
}
?>

<style>
    /* CSS custom bổ sung cho giao diện giỏ hàng */
    .cart-grid-layout {
        display: grid;
        grid-template-columns: 2.2fr 1fr;
        gap: var(--spacing-lg);
        margin-top: var(--spacing-md);
    }
    @media (max-width: 992px) {
        .cart-grid-layout {
            grid-template-columns: 1fr;
        }
    }
    .cart-item-card {
        display: flex;
        align-items: center;
        gap: var(--spacing-md);
        padding: var(--spacing-md);
        border: var(--border-width) solid var(--color-border);
        border-radius: var(--border-radius);
        background-color: var(--color-surface);
        margin-bottom: var(--spacing-md);
        box-shadow: var(--box-shadow-sm);
    }
    .cart-item-img {
        width: 70px;
        height: 95px;
        object-fit: contain;
        background: var(--color-background);
        border-radius: var(--border-radius-sm);
        padding: 4px;
        border: 1px solid var(--color-border);
        flex-shrink: 0;
    }
    .cart-item-info {
        flex: 1;
        min-width: 0;
    }
    .cart-item-title {
        font-size: var(--font-size-md);
        font-weight: var(--font-weight-bold);
        color: var(--color-text);
        margin: 0 0 6px 0;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .cart-item-price {
        color: var(--color-primary);
        font-weight: var(--font-weight-bold);
        font-size: 1.05rem;
    }
    .cart-qty-wrapper {
        display: flex;
        align-items: center;
        border: var(--border-width) solid var(--color-border);
        border-radius: var(--border-radius-sm);
        overflow: hidden;
        width: 110px;
        height: 34px;
    }
    .cart-qty-btn {
        background: var(--color-surface);
        border: none;
        color: var(--color-primary);
        width: 32px;
        height: 100%;
        cursor: pointer;
        font-weight: bold;
        font-size: 1rem;
    }
    .cart-qty-btn:hover {
        background: var(--color-accent-dark);
        color: var(--color-surface);
    }
    .cart-qty-btn:disabled {
        background: #cbd5e1;
        color: #94a3b8;
        cursor: not-allowed;
        opacity: 0.8;
    }
    .cart-qty-btn:disabled:hover {
        background: #cbd5e1;
        color: #94a3b8;
    }
    .cart-qty-input {
        flex: 1;
        border: none;
        text-align: center;
        width: 100%;
        font-weight: bold;
        font-size: 0.95rem;
    }
    .cart-subtotal-info {
        text-align: right;
        min-width: 120px;
    }
    .cart-subtotal-val {
        font-weight: var(--font-weight-bold);
        font-size: 1.1rem;
        color: var(--color-text);
    }
    .delete-cart-btn {
        background: transparent;
        border: none;
        color: var(--color-error);
        cursor: pointer;
        font-size: 1.2rem;
        padding: var(--spacing-xs);
        transition: opacity var(--transition-fast);
    }
    .delete-cart-btn:hover {
        opacity: 0.8;
    }
    .empty-cart-box {
        text-align: center;
        padding: 80px var(--spacing-md);
        background: var(--color-surface);
        border: 1px solid var(--color-border);
        border-radius: var(--border-radius-lg);
        box-shadow: var(--box-shadow-sm);
    }

    .cart-page {
        --cart-accent: rgb(0, 169, 242);
        --cart-accent-dark: rgb(0, 135, 195);
        max-width: 1200px;
        margin: 0 auto 50px;
        padding: 20px;
        font-family: "Times New Roman", Times, serif;
    }

    .cart-page .btn,
    .cart-page input,
    .cart-page button,
    .cart-page select,
    .cart-page textarea {
        font-family: var(--font-family-base);
    }

    .cart-page .cart-qty-btn,
    .cart-page .cart-qty-input {
        font-family: "Times New Roman", Times, serif;
    }

    .cart-page .breadcrumbs {
        margin-bottom: 18px;
        color: var(--color-text-light);
        font-size: 0.875rem;
    }

    .cart-page .breadcrumbs a:hover,
    .cart-page .breadcrumbs li:last-child {
        color: var(--cart-accent);
    }

    .cart-page .order-title-section {
        margin-bottom: 24px;
        padding-bottom: 16px;
        border-bottom: 1px solid #e2e8f0;
    }

    .cart-page .order-title {
        color: #172033;
        font-size: var(--font-size-xl);
        font-weight: var(--font-weight-bold);
    }

    .cart-page .cart-title-icon {
        margin-right: 10px;
        color: var(--cart-accent);
    }

    .cart-page .cart-item-count {
        color: var(--color-text-light);
        font-size: 0.9rem;
    }

    .cart-page .cart-alert {
        margin-bottom: 16px;
        padding: 12px 14px;
        border: 1px solid;
        border-radius: 10px;
        font-weight: 700;
    }

    .cart-page .cart-alert--success {
        border-color: #c3e6cb;
        background: #d4edda;
        color: #155724;
    }

    .cart-page .cart-alert--warning {
        border-color: #ffeeba;
        background: #fff3cd;
        color: #856404;
    }

    .cart-page .cart-alert--error {
        border-color: #f5c6cb;
        background: #f8d7da;
        color: #721c24;
    }

    .cart-page .empty-cart-box {
        padding: 72px 24px;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        background: linear-gradient(180deg, #fff, #f8fbfd);
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
    }

    .cart-page .empty-cart-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 82px;
        height: 82px;
        margin-bottom: 18px;
        border-radius: 50%;
        background: rgba(0, 169, 242, 0.10);
        color: var(--cart-accent);
        font-size: 2.75rem;
    }

    .cart-page .empty-cart-title {
        margin: 0 0 8px;
        color: #172033;
        font-size: 1.17rem;
    }

    .cart-page .empty-cart-copy {
        margin: 0 0 24px;
        color: var(--color-text-light);
    }

    .cart-page .empty-cart-action {
        padding: 11px 28px;
        border-color: var(--cart-accent);
        border-radius: 9px;
        background: var(--cart-accent);
        color: #fff;
        font-weight: 700;
    }

    .cart-page .cart-grid-layout {
        grid-template-columns: minmax(0, 2.2fr) minmax(300px, 1fr);
        gap: 24px;
        align-items: start;
    }

    .cart-page .cart-item-card {
        display: grid;
        grid-template-columns: 88px minmax(0, 1fr) 112px minmax(110px, auto) 36px;
        gap: 16px;
        margin-bottom: 16px;
        padding: 16px;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06);
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .cart-page .cart-item-card:hover {
        border-color: rgba(0, 169, 242, 0.35);
        box-shadow: 0 10px 24px rgba(0, 169, 242, 0.12);
    }

    .cart-page .cart-item-img {
        width: 88px;
        height: 118px;
        padding: 8px;
        border-color: #e2e8f0;
        border-radius: 10px;
        background: linear-gradient(180deg, #f8fbfd, #eef8fc);
    }

    .cart-page .cart-item-title {
        margin-bottom: 8px;
        color: #172033;
        line-height: 1.4;
    }

    .cart-page .cart-item-link {
        color: inherit;
        text-decoration: none;
    }

    .cart-page .cart-item-link:hover {
        color: var(--cart-accent);
    }

    .cart-page .cart-item-price,
    .cart-page .cart-item-price-current {
        color: var(--cart-accent);
        font-weight: 800;
    }

    .cart-page .cart-item-price-row {
        display: flex;
        align-items: baseline;
        gap: 6px;
    }

    .cart-page .cart-item-original-price {
        color: var(--color-text-light);
        font-size: 0.8rem;
        font-weight: 400;
        text-decoration: line-through;
    }

    .cart-page .cart-item-meta {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 5px;
    }

    .cart-page .cart-stock-text {
        color: var(--color-text-light);
        font-size: 0.8rem;
    }

    .cart-page .cart-discount-badge {
        padding: 3px 7px;
        border-radius: 999px;
        background: rgba(0, 169, 242, 0.12);
        color: rgb(0, 135, 195);
        font-size: 0.7rem;
        font-weight: 700;
    }

    .cart-page .cart-qty-wrapper {
        width: 112px;
        height: 38px;
        border-color: #dbe3ea;
        border-radius: 9px;
        background: #fff;
    }

    .cart-page .cart-qty-wrapper:focus-within {
        border-color: var(--cart-accent);
        box-shadow: 0 0 0 3px rgba(0, 169, 242, 0.12);
    }

    .cart-page .cart-qty-btn {
        background: #f8fbfd;
        color: var(--cart-accent-dark);
    }

    .cart-page .cart-qty-btn:hover:not(:disabled) {
        background: var(--cart-accent);
        color: #fff;
    }

    .cart-page .cart-qty-input {
        min-width: 38px;
        outline: none;
        background: #fff;
    }

    .cart-page .cart-subtotal-val {
        color: #172033;
        font-weight: 800;
    }

    .cart-page .cart-delete-form {
        margin: 0;
    }

    .cart-page .delete-cart-btn {
        width: 34px;
        height: 34px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        background: rgba(230, 57, 70, 0.08);
    }

    .cart-page .delete-cart-btn:hover {
        background: rgba(230, 57, 70, 0.14);
        opacity: 1;
    }

    .cart-page .detail-summary-card {
        top: 20px;
        padding: 22px;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.07);
    }

    .cart-page .detail-summary-title {
        margin-bottom: 18px;
        padding-bottom: 12px;
        border-bottom-color: #e2e8f0;
        color: #172033;
    }

    .cart-page .detail-summary-row--voucher {
        color: #2e7d32;
        font-weight: 700;
    }

    .cart-page .cart-voucher-label,
    .cart-page .cart-voucher-value {
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .cart-page .cart-voucher-remove-form {
        display: inline;
        margin: 0;
    }

    .cart-page .cart-voucher-remove {
        padding: 0;
        border: 0;
        background: none;
        color: var(--color-error);
        cursor: pointer;
        font-size: 0.95rem;
    }

    .cart-page .detail-summary-row--total .detail-summary-value {
        color: var(--cart-accent);
        font-size: 1.25rem;
    }

    .cart-page .cart-voucher-section {
        margin-top: 16px;
        padding-top: 16px;
        border-top: 1px dashed #dbe3ea;
    }

    .cart-page .cart-voucher-form {
        display: flex;
        gap: 8px;
        margin-bottom: 8px;
    }

    .cart-page .cart-voucher-input {
        min-width: 0;
        margin-bottom: 0;
        padding: 8px 12px;
        font-size: 0.9rem;
    }

    .cart-page .cart-voucher-submit {
        height: 38px;
        padding: 0 16px;
        border-color: var(--cart-accent);
        border-radius: 8px;
        background: var(--cart-accent);
        color: #fff;
        font-size: 0.9rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .cart-page .cart-voucher-note {
        display: flex;
        align-items: center;
        gap: 4px;
        margin-top: 4px;
        color: #2e7d32;
        font-size: 0.85rem;
    }

    .cart-page .cart-voucher-login {
        padding: 12px;
        border: 1px dashed #dbe3ea;
        border-radius: 10px;
        background: #f8fbfd;
        color: var(--color-text-light);
        font-size: 0.85rem;
        text-align: center;
    }

    .cart-page .cart-voucher-login i {
        margin-right: 4px;
    }

    .cart-page .cart-voucher-login a {
        color: var(--cart-accent);
        font-weight: 700;
        text-decoration: none;
    }

    .cart-page .detail-summary-actions {
        margin-top: 18px;
    }

    .cart-page .cart-checkout-action,
    .cart-page .cart-continue-action {
        text-align: center;
        text-decoration: none;
        border-radius: 9px;
    }

    .cart-page .cart-checkout-action {
        padding: 12px 0;
        border-color: var(--cart-accent);
        background: var(--cart-accent);
        color: #fff;
        font-weight: 700;
    }

    .cart-page .cart-checkout-action:hover {
        border-color: var(--cart-accent-dark);
        background: var(--cart-accent-dark);
    }

    .cart-page .cart-continue-action {
        padding: 10px 0;
        border-color: var(--cart-accent);
        color: var(--cart-accent);
    }

    .header-search:focus-within {
        border-color: rgb(0, 169, 242);
        box-shadow: 0 0 0 3px rgba(0, 169, 242, 0.12);
    }

    .header-search button { background: rgb(0, 169, 242); }
    .header-search button:hover { background: rgb(0, 135, 195); }

    @media (max-width: 992px) {
        .cart-page .cart-grid-layout {
            grid-template-columns: 1fr;
        }

        .cart-page .detail-summary-card {
            position: static;
        }
    }

    @media (max-width: 768px) {
        .cart-page {
            padding: 16px 14px 40px;
        }

        .cart-page .cart-item-card {
            position: relative;
            grid-template-columns: 76px minmax(0, 1fr) auto;
            gap: 12px;
            padding: 14px 48px 14px 14px;
        }

        .cart-page .cart-item-img {
            grid-column: 1;
            grid-row: 1 / 3;
            width: 76px;
            height: 102px;
        }

        .cart-page .cart-item-info {
            grid-column: 2 / 4;
            grid-row: 1;
        }

        .cart-page .cart-item-title {
            white-space: normal;
            display: -webkit-box;
            overflow: hidden;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .cart-page .cart-qty-wrapper {
            grid-column: 2;
            grid-row: 2;
        }

        .cart-page .cart-subtotal-info {
            grid-column: 3;
            grid-row: 2;
            min-width: 0;
        }

        .cart-page .cart-delete-form {
            position: absolute;
            top: 12px;
            right: 10px;
        }
    }

    @media (max-width: 480px) {
        .cart-page .order-title {
            font-size: 1.45rem;
        }

        .cart-page .empty-cart-box {
            padding: 48px 18px;
        }

        .cart-page .cart-item-card {
            grid-template-columns: 68px minmax(0, 1fr);
        }

        .cart-page .cart-item-img {
            width: 68px;
            height: 92px;
        }

        .cart-page .cart-item-info,
        .cart-page .cart-qty-wrapper {
            grid-column: 2;
        }

        .cart-page .cart-subtotal-info {
            grid-column: 1 / 3;
            grid-row: 3;
            text-align: left;
        }

        .cart-page .cart-voucher-form {
            flex-direction: column;
        }

        .cart-page .cart-voucher-submit,
        .cart-page .detail-summary-actions .btn {
            width: 100%;
        }
    }
</style>

<main class="order-container cart-page">
    <ul class="breadcrumbs">
        <li><a href="<?= url('/') ?>">Trang chủ</a></li>
        <li>Giỏ hàng</li>
    </ul>

    <div class="order-title-section">
        <h1 class="order-title"><i class="fa-solid fa-cart-shopping cart-title-icon"></i> Giỏ hàng của tôi</h1>
        <span class="cart-item-count"><?= count($cartProducts) ?> sản phẩm</span>
    </div>

    <!-- Thông báo Alerts -->
    <?php
    $successMsg = $_SESSION['success'] ?? '';
    $warningMsg = $_SESSION['warning'] ?? '';
    $errorMsg = $_SESSION['error'] ?? '';
    unset($_SESSION['success'], $_SESSION['warning'], $_SESSION['error']);
    ?>

    <?php if ($successMsg): ?>
        <div class="cart-alert cart-alert--success">
            <?= htmlspecialchars($successMsg) ?>
        </div>
    <?php endif; ?>
    <?php if ($warningMsg): ?>
        <div class="cart-alert cart-alert--warning">
            <?= htmlspecialchars($warningMsg) ?>
        </div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
        <div class="cart-alert cart-alert--error">
            <?= htmlspecialchars($errorMsg) ?>
        </div>
    <?php endif; ?>

    <?php if (empty($cartProducts)): ?>
        <div class="empty-cart-box">
            <i class="fa-solid fa-cart-flatbed-suitcase empty-cart-icon"></i>
            <h3 class="empty-cart-title">Giỏ hàng của bạn đang trống!</h3>
            <p class="empty-cart-copy">Hãy quay lại cửa hàng để chọn cho mình những cuốn sách ưng ý nhất.</p>
            <a href="<?= url('trangchu/index.php') ?>" class="btn btn--primary empty-cart-action">Tiếp tục mua sắm</a>
        </div>
    <?php else: ?>
        <div class="cart-grid-layout">
            <!-- Cột trái: Danh sách sản phẩm -->
            <div>
                <?php foreach ($cartProducts as $item): ?>
                    <div class="cart-item-card" data-cart-item="<?= $item['ProductID'] ?>">
                        <?php 
                        // ĐOẠN XỬ LÝ ẢNH ĐÃ ĐƯỢC CẬP NHẬT
                        if (!empty($item['ImageURL'])) {
                            if (strpos($item['ImageURL'], 'http') === 0) {
                                $imgSrc = $item['ImageURL'];
                            } else {
                                $fileName = basename($item['ImageURL']);
                                $imgSrc = asset('images/uploads/' . $fileName);
                            }
                        } else {
                            $imgSrc = asset('images/default-book.png'); 
                        }
                        ?>
                        <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($item['ProductName']) ?>" class="cart-item-img">
                        
                        <div class="cart-item-info">
                            <h3 class="cart-item-title" title="<?= htmlspecialchars($item['ProductName']) ?>">
                                <a href="<?= url('trangchu/detail.php?id=' . $item['ProductID']) ?>" class="cart-item-link">
                                    <?= htmlspecialchars($item['ProductName']) ?>
                                </a>
                            </h3>
                            <?php 
                            $itemDiscountRate = isset($item['DiscountRate']) ? floatval($item['DiscountRate']) : 0;
                            ?>
                            <?php if ($itemDiscountRate > 0): ?>
                                <div class="cart-item-price cart-item-price-row">
                                    <span class="cart-item-price-current"><?= number_format($item['Price'], 0, ',', '.') ?> đ</span>
                                    <span class="cart-item-original-price"><?= number_format($item['OriginalPrice'], 0, ',', '.') ?> đ</span>
                                </div>
                            <?php else: ?>
                                <div class="cart-item-price"><?= number_format($item['Price'], 0, ',', '.') ?> đ</div>
                            <?php endif; ?>
                            <div class="cart-item-meta">
                                <span class="cart-stock-text">Kho: <?= $item['Stock'] ?></span>
                                <?php if ($itemDiscountRate > 0): ?>
                                    <span class="badge cart-discount-badge">-<?= number_format($itemDiscountRate, 0) ?>%</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Cập nhật số lượng -->
                        <form action="update.php" method="POST" class="cart-qty-wrapper" id="qty-form-<?= $item['ProductID'] ?>">
                            <input type="hidden" name="product_id" value="<?= $item['ProductID'] ?>">
                            <input type="hidden" name="action" value="update">
                            
                            <button type="button" class="cart-qty-btn" onclick="updateQty(<?= $item['ProductID'] ?>, -1)" <?= $item['CartQuantity'] <= 1 ? 'disabled aria-disabled="true"' : '' ?>>-</button>
                            <input type="number" name="quantity" id="qty-input-<?= $item['ProductID'] ?>" class="cart-qty-input" 
                                   value="<?= $item['CartQuantity'] ?>" min="1" max="<?= $item['Stock'] ?>" 
                                   onchange="submitQty(<?= $item['ProductID'] ?>)" oninput="syncQtyButton(<?= $item['ProductID'] ?>)">
                            <button type="button" class="cart-qty-btn" onclick="updateQty(<?= $item['ProductID'] ?>, 1, <?= $item['Stock'] ?>)">+</button>
                        </form>

                        <div class="cart-subtotal-info">
                            <div class="cart-subtotal-val" data-cart-item-subtotal="<?= $item['ProductID'] ?>"><?= number_format($item['Subtotal'], 0, ',', '.') ?> đ</div>
                        </div>

                        <!-- Nút xóa -->
                        <form action="update.php" method="POST" class="cart-delete-form">
                            <input type="hidden" name="product_id" value="<?= $item['ProductID'] ?>">
                            <input type="hidden" name="action" value="delete">
                            <button type="submit" class="delete-cart-btn" title="Xóa khỏi giỏ" onclick="return confirm('Bạn có chắc muốn xóa sản phẩm này?')">
                                <i class="fa-regular fa-trash-can"></i>
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Cột phải: Summary -->
            <div>
                <div class="detail-summary-card">
                    <h2 class="detail-summary-title">Tóm tắt đơn hàng</h2>
                    
                    <div class="detail-summary-row">
                        <span>Tạm tính</span>
                        <span data-cart-subtotal><?= number_format($totalAmount, 0, ',', '.') ?> đ</span>
                    </div>
                    
                    <div class="detail-summary-row">
                        <span>Phí vận chuyển</span>
                        <span data-cart-shipping><?= number_format($shippingFee, 0, ',', '.') ?> đ</span>
                    </div>

                    <?php 
                    $voucherDiscount = 0;
                    $appliedVoucher = $_SESSION['applied_voucher'] ?? null;
                    if ($appliedVoucher && isset($_SESSION['user'])) {
                        $voucherDiscount = floatval($appliedVoucher['value']);
                    } else {
                        // Nếu chưa đăng nhập hoặc đăng xuất, gỡ voucher khỏi session
                        unset($_SESSION['applied_voucher']);
                        $appliedVoucher = null;
                    }
                    
                    $finalTotal = max(0, $totalAmount + $shippingFee - $voucherDiscount);
                    ?>

                    <?php if ($appliedVoucher): ?>
                        <div class="detail-summary-row detail-summary-row--voucher">
                            <span class="cart-voucher-label">
                                <i class="fa-solid fa-ticket"></i> Voucher (<?= htmlspecialchars($appliedVoucher['code']) ?>)
                            </span>
                            <span class="cart-voucher-value">
                                <span data-cart-voucher-discount>-<?= number_format($voucherDiscount, 0, ',', '.') ?> đ</span>
                                <form action="<?= url('cart/apply_voucher.php') ?>" method="POST" class="cart-voucher-remove-form">
                                    <input type="hidden" name="action" value="remove">
                                    <button type="submit" class="cart-voucher-remove" title="Gỡ mã">
                                        <i class="fa-solid fa-circle-xmark"></i>
                                    </button>
                                </form>
                            </span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="detail-summary-row detail-summary-row--total">
                        <span>Tổng tiền</span>
                        <span class="detail-summary-value" data-cart-final-total><?= number_format($finalTotal, 0, ',', '.') ?> đ</span>
                    </div>

                    <!-- Khu vực nhập Voucher -->
                    <div class="cart-voucher-section">
                        <?php if (isset($_SESSION['user'])): ?>
                            <form action="<?= url('cart/apply_voucher.php') ?>" method="POST" class="cart-voucher-form">
                                <input type="hidden" name="action" value="apply">
                                <input type="text" name="voucher_code" class="form-control cart-voucher-input" placeholder="Nhập mã giảm giá..." required value="<?= $appliedVoucher ? htmlspecialchars($appliedVoucher['code']) : '' ?>">
                                <button type="submit" class="btn btn--primary cart-voucher-submit">
                                    <?= $appliedVoucher ? 'Thay đổi' : 'Áp dụng' ?>
                                </button>
                            </form>
                            <?php if ($appliedVoucher): ?>
                                <div class="cart-voucher-note">
                                    <i class="fa-solid fa-circle-check"></i> Đang áp dụng mã: <strong><?= htmlspecialchars($appliedVoucher['code']) ?></strong> (-<?= number_format($voucherDiscount, 0, ',', '.') ?> đ)
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="cart-voucher-login">
                                <i class="fa-solid fa-lock"></i> <a href="<?= url('auth/pages/login.php') ?>">Đăng nhập</a> để sử dụng mã giảm giá.
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="detail-summary-actions">
                        <a href="checkout.php" class="btn btn--primary btn--block cart-checkout-action">
                            Tiến hành thanh toán
                        </a>
                        <a href="<?= url('trangchu/index.php') ?>" class="btn btn--outline btn--block cart-continue-action">
                            Tiếp tục mua hàng
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</main>

<?php include '../includes/footer.php'; ?>

<script>
    const quantityRequests = new Set();

    function formatVnd(value) {
        return new Intl.NumberFormat('vi-VN').format(Number(value) || 0) + ' đ';
    }

    function syncQtyButton(productId) {
        const input = document.getElementById('qty-input-' + productId);
        const form = document.getElementById('qty-form-' + productId);
        if (!input || !form) return;

        const minusButton = form.querySelector('.cart-qty-btn');
        const value = parseInt(input.value, 10) || 1;
        const isMinimum = value <= 1;
        minusButton.disabled = isMinimum;
        minusButton.setAttribute('aria-disabled', isMinimum ? 'true' : 'false');
    }

    function setQtyLocked(productId, locked) {
        const form = document.getElementById('qty-form-' + productId);
        if (!form) return;
        form.querySelectorAll('.cart-qty-btn').forEach((button) => {
            button.disabled = locked || (button.textContent.trim() === '-' && parseInt(form.querySelector('.cart-qty-input')?.value || '1', 10) <= 1);
            button.setAttribute('aria-busy', locked ? 'true' : 'false');
        });
        form.querySelector('.cart-qty-input').readOnly = locked;
    }

    function updateCartTotals(data) {
        const itemSubtotal = document.querySelector('[data-cart-item-subtotal="' + data.product_id + '"]');
        const cartSubtotal = document.querySelector('[data-cart-subtotal]');
        const voucherDiscount = document.querySelector('[data-cart-voucher-discount]');
        const shipping = document.querySelector('[data-cart-shipping]');
        const finalTotal = document.querySelector('[data-cart-final-total]');
        const cartBadge = document.querySelector('.header-cart-badge');

        if (itemSubtotal) itemSubtotal.textContent = formatVnd(data.item_subtotal);
        if (cartSubtotal) cartSubtotal.textContent = formatVnd(data.cart_subtotal);
        if (voucherDiscount) voucherDiscount.textContent = '-' + formatVnd(data.voucher_discount);
        if (shipping) shipping.textContent = formatVnd(data.shipping_fee);
        if (finalTotal) finalTotal.textContent = formatVnd(data.final_total);
        if (cartBadge) cartBadge.textContent = data.cart_count;
    }

    function submitQty(productId, requestedQuantity = null) {
        const form = document.getElementById('qty-form-' + productId);
        const input = document.getElementById('qty-input-' + productId);
        if (!form || !input || quantityRequests.has(productId)) return;

        const previousQuantity = input.value;
        if (requestedQuantity !== null) input.value = requestedQuantity;
        const quantity = parseInt(input.value, 10);
        if (!Number.isInteger(quantity) || quantity < 1) {
            input.value = previousQuantity;
            syncQtyButton(productId);
            return;
        }

        quantityRequests.add(productId);
        setQtyLocked(productId, true);
        const formData = new FormData(form);

        fetch(form.getAttribute('action'), {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then((response) => {
                if (!response.ok) throw new Error('Không thể cập nhật giỏ hàng.');
                return response.json();
            })
            .then((data) => {
                if (!data.success) throw new Error(data.message || 'Không thể cập nhật số lượng.');
                input.value = data.quantity;
                updateCartTotals(data);
                if (data.warning && typeof showToast === 'function') showToast(data.warning, 'warning');
            })
            .catch((error) => {
                input.value = previousQuantity;
                if (typeof showToast === 'function') showToast(error.message, 'error');
            })
            .finally(() => {
                quantityRequests.delete(productId);
                setQtyLocked(productId, false);
                syncQtyButton(productId);
                input.focus({ preventScroll: true });
            });
    }

    function updateQty(productId, delta, maxStock = 99) {
        const input = document.getElementById('qty-input-' + productId);
        let val = parseInt(input.value) || 1;
        val += delta;
        if (val < 1) val = 1;
        if (val > maxStock) val = maxStock;
        submitQty(productId, val);
    }

    document.querySelectorAll('.cart-qty-wrapper').forEach((form) => {
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            submitQty(form.id.replace('qty-form-', ''));
        });
    });

    document.querySelectorAll('.cart-qty-input').forEach((input) => {
        syncQtyButton(input.id.replace('qty-input-', ''));
    });
</script>
</body>
</html>
