<?php
require_once '../config/db.php';

$productId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($productId <= 0) {
    header('Location: index.php');
    exit;
}

$sql_product = "
    SELECT p.ProductID, p.ProductName, p.Price, p.Description, p.Status, p.Quantity AS Stock, c.CategoryName, i.ImageURL,
           ap.DiscountRate, ap.PromotionName
    FROM product p
    LEFT JOIN category c ON p.CategoryID = c.CategoryID
    LEFT JOIN image i ON p.ProductID = i.ProductID AND i.IsThumbnail = 1
    LEFT JOIN (
        SELECT pd.ProductID, MAX(pd.DiscountRate) AS DiscountRate, MIN(pr.PromotionName) AS PromotionName
        FROM promotion_detail pd
        JOIN promotion pr ON pd.PromotionID = pr.PromotionID
        WHERE NOW() BETWEEN COALESCE(pd.StartDate, pr.StartDate) AND COALESCE(pd.EndDate, pr.EndDate)
        GROUP BY pd.ProductID
    ) ap ON p.ProductID = ap.ProductID
    WHERE p.ProductID = ?
";

$stmt = $conn->prepare($sql_product);
$stmt->bind_param("i", $productId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $pageTitle = 'Không tìm thấy sản phẩm';
    $extraCss = ['css/components/button.css', 'css/components/card.css'];
    include '../includes/header.php';
    ?>
    <style>
        .detail-page--not-found {
            max-width: 760px;
            margin: 0 auto 50px;
            padding: 40px 20px;
        }
        .detail-page--not-found .detail-not-found {
            padding: 56px 32px;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            background: linear-gradient(180deg, #fff, #f7fcff);
            text-align: center;
            box-shadow: 0 12px 30px rgba(0, 169, 242, 0.12);
        }
        .detail-page--not-found .detail-not-found__icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 76px;
            height: 76px;
            margin-bottom: 18px;
            border-radius: 50%;
            background: rgba(0, 169, 242, 0.10);
            color: rgb(0, 169, 242);
            font-size: 2.25rem;
        }
        .detail-page--not-found .detail-not-found__title { margin: 0 0 10px; color: #172033; }
        .detail-page--not-found .detail-not-found__text { margin: 0 0 24px; color: var(--color-text-light); }
        .detail-page--not-found .btn--primary { border-color: rgb(0, 169, 242); background: rgb(0, 169, 242); }
        .header-search:focus-within {
            border-color: rgb(0, 169, 242);
            box-shadow: 0 0 0 3px rgba(0, 169, 242, 0.12);
        }
        .header-search button { background: rgb(0, 169, 242); }
        .header-search button:hover { background: rgb(0, 135, 195); }
    </style>
    <main class="detail-container detail-page--not-found">
        <section class="card detail-not-found" aria-labelledby="detail-not-found-title">
            <div class="detail-not-found__icon"><i class="fa-solid fa-book-open" aria-hidden="true"></i></div>
            <h1 class="detail-not-found__title" id="detail-not-found-title">Không tìm thấy sản phẩm</h1>
            <p class="detail-not-found__text">Sản phẩm này không hiện diện trên hệ thống hoặc đường dẫn không còn hợp lệ.</p>
            <a href="index.php" class="btn btn--primary"><i class="fa-solid fa-house" aria-hidden="true"></i> Quay lại trang chủ</a>
        </section>
    </main>
    <?php
    include '../includes/footer.php';
    exit;
}

$product = $result->fetch_assoc();
$stmt->close();

$pageTitle = $product['ProductName'] . ' - Chi tiết sách';
$extraCss = ['css/components/button.css', 'css/components/badge.css', 'css/components/form.css', 'css/components/card.css', 'css/components/review.css'];

// Tính toán đánh giá trung bình và tổng số lượng bình luận
$avgRating = 0;
$totalReviews = 0;
$starCounts = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];

$sql_rating_stats = "SELECT AVG(Rating) AS avg_rating, COUNT(*) AS total_reviews FROM review WHERE ProductID = ?";
$stmt_stats = $conn->prepare($sql_rating_stats);
$stmt_stats->bind_param("i", $productId);
$stmt_stats->execute();
$res_stats = $stmt_stats->get_result()->fetch_assoc();
if ($res_stats) {
    $avgRating = $res_stats['avg_rating'] !== null ? round(floatval($res_stats['avg_rating']), 1) : 0;
    $totalReviews = intval($res_stats['total_reviews']);
}
$stmt_stats->close();

if ($totalReviews > 0) {
    $sql_star_distribution = "SELECT Rating, COUNT(*) AS count FROM review WHERE ProductID = ? GROUP BY Rating";
    $stmt_dist = $conn->prepare($sql_star_distribution);
    $stmt_dist->bind_param("i", $productId);
    $stmt_dist->execute();
    $res_dist = $stmt_dist->get_result();
    while ($row = $res_dist->fetch_assoc()) {
        $star = intval($row['Rating']);
        if ($star >= 1 && $star <= 5) {
            $starCounts[$star] = intval($row['count']);
        }
    }
    $stmt_dist->close();
}

// Truy vấn danh sách đánh giá kèm theo thông tin kiểm tra Đã mua hàng
$reviews = [];
$sql_reviews = "
    SELECT r.ReviewID, r.CustomerID, r.Rating, r.Comment, r.ReviewDate, u.FirstName, u.LastName,
           (SELECT COUNT(*) 
            FROM `order` o 
            JOIN `order_detail` od ON o.OrderID = od.OrderID 
            WHERE o.CustomerID = r.CustomerID AND od.ProductID = r.ProductID AND o.OrderStatus = 'Delivered'
           ) AS VerifiedPurchase
    FROM review r
    LEFT JOIN user u ON r.CustomerID = u.CustomerID
    WHERE r.ProductID = ?
    ORDER BY r.ReviewDate DESC
";
$stmt_rev = $conn->prepare($sql_reviews);
$stmt_rev->bind_param("i", $productId);
$stmt_rev->execute();
$res_rev = $stmt_rev->get_result();
while ($row = $res_rev->fetch_assoc()) {
    $reviews[] = $row;
}
$stmt_rev->close();

// Truy vấn tất cả hình ảnh của sản phẩm, ảnh đại diện lên đầu
$sql_images = "SELECT ImageURL, IsThumbnail FROM image WHERE ProductID = ? ORDER BY IsThumbnail DESC, SortOrder ASC";
$stmt_images = $conn->prepare($sql_images);
$stmt_images->bind_param("i", $productId);
$stmt_images->execute();
$res_images = $stmt_images->get_result();
$productImages = [];
while ($row = $res_images->fetch_assoc()) {
    $productImages[] = $row;
}
$stmt_images->close();

// Fallback nếu sản phẩm chưa có ảnh nào
if (empty($productImages)) {
    $productImages[] = [
        'ImageURL' => '',
        'IsThumbnail' => 1
    ];
}

include '../includes/header.php';
?>

<style>
    .detail-container { max-width: 1200px; margin: 0 auto var(--spacing-xl); padding: 0 var(--spacing-md); }
    .breadcrumb { display: flex; align-items: center; gap: var(--spacing-xs); padding: var(--spacing-md) 0; font-size: 0.95rem; }
    .breadcrumb a { color: var(--color-text); text-decoration: none; }
    .breadcrumb .separator { color: var(--color-text-light); }
    .breadcrumb .current { color: var(--color-primary); font-weight: var(--font-weight-medium); }
    
    .product-layout { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.4fr); gap: var(--spacing-xl); background-color: var(--color-surface); border: var(--border-width) solid var(--color-border); border-radius: var(--border-radius-lg); padding: var(--spacing-xl); box-shadow: var(--box-shadow-sm); }
    @media (max-width: 768px) { .product-layout { grid-template-columns: 1fr; gap: var(--spacing-lg); padding: var(--spacing-md); } }
    
    .product-image-wrapper, .product-info-wrapper { min-width: 0; }
    .product-image-wrapper { display: flex; flex-direction: column; gap: var(--spacing-md); }
    .product-main-image-container { text-align: center; border: var(--border-width) solid var(--color-border); border-radius: var(--border-radius); padding: var(--spacing-md); background-color: var(--color-background); display: flex; align-items: center; justify-content: center; height: 380px; overflow: hidden; }
    .product-main-image { max-width: 100%; max-height: 100%; object-fit: contain; border-radius: var(--border-radius-sm); transition: opacity 0.2s ease-in-out; }
    .product-image-gallery { display: flex; gap: var(--spacing-xs); overflow-x: auto; padding: 4px 0; justify-content: center; min-width: 0; }
    .gallery-thumb-item { width: 70px; height: 70px; border-radius: var(--border-radius-sm); border: 2px solid var(--color-border); overflow: hidden; display: flex; align-items: center; justify-content: center; cursor: pointer; background: var(--color-surface); flex-shrink: 0; padding: 2px; transition: all 0.2s ease; }
    .gallery-thumb-item:hover { border-color: var(--color-primary) !important; transform: translateY(-2px); }
    .gallery-thumb-item.active { border-color: var(--color-primary) !important; }
    .gallery-thumb-item img { max-height: 100%; max-width: 100%; object-fit: contain; }
    
    .product-info-wrapper { display: flex; flex-direction: column; gap: var(--spacing-md); }
    .product-detail-title { font-size: 1.75rem; font-weight: var(--font-weight-bold); color: var(--color-text); margin: 0; line-height: 1.3; }
    .product-meta-row { display: flex; flex-wrap: wrap; gap: var(--spacing-sm) var(--spacing-lg); align-items: center; font-size: var(--font-size-sm); color: var(--color-text-light); border-bottom: 1px solid var(--color-border); padding-bottom: var(--spacing-sm); }
    .product-meta-item { display: inline-flex; align-items: center; gap: var(--spacing-xs); min-width: 0; flex-wrap: wrap; }
    .product-meta-label { color: var(--color-text-light); }
    .product-category-badge { color: var(--color-text); font-weight: var(--font-weight-bold); }
    .product-detail-price { font-size: 2.2rem; font-weight: var(--font-weight-bold); color: var(--color-primary); margin: var(--spacing-xs) 0; }
    .product-price-row { display: flex; align-items: baseline; flex-wrap: wrap; gap: var(--spacing-sm) var(--spacing-md); margin: var(--spacing-xs) 0; }
    .product-original-price { text-decoration: line-through; color: var(--color-text-light); font-size: var(--font-size-lg); }
    .product-discount-badge { font-size: var(--font-size-sm); }
    .product-promotion-note { color: var(--color-success); font-weight: var(--font-weight-bold); font-size: var(--font-size-sm); margin: -6px 0 var(--spacing-sm); }
    
    .product-description-box { line-height: var(--line-height-base); color: var(--color-text); font-size: var(--font-size-md); border-top: 1px dashed var(--color-border); border-bottom: 1px dashed var(--color-border); padding: var(--spacing-md) 0; }
    .product-description-title { margin: 0 0 var(--spacing-xs); color: var(--color-text); font-size: var(--font-size-md); }
    .product-description-content { color: var(--color-text-light); white-space: pre-line; }
    .purchase-action-box { display: flex; align-items: flex-end; flex-wrap: wrap; gap: var(--spacing-md); margin-top: var(--spacing-sm); }
    .quantity-select-group { width: 130px; flex: 0 0 130px; }
    .quantity-label { font-weight: var(--font-weight-bold); margin-bottom: 6px; }
    .purchase-cta { flex: 1 1 240px; min-width: 0; }
    .purchase-cta .btn { width: 100%; height: 40px; font-weight: var(--font-weight-bold); font-size: var(--font-size-md); }
    
    .quantity-input-wrapper { display: flex; align-items: center; border: var(--border-width) solid var(--color-border); border-radius: var(--border-radius); overflow: hidden; background: var(--color-surface); transition: border-color var(--transition-fast), box-shadow var(--transition-fast); }
    .quantity-input-wrapper:focus-within { border-color: var(--color-primary); box-shadow: 0 0 0 3px rgba(0, 169, 242, 0.18); }
    .quantity-btn { display: inline-flex; align-items: center; justify-content: center; flex: 0 0 40px; background: var(--color-background); border: none; width: 40px; height: 40px; padding: 0; color: var(--color-text); cursor: pointer; font-weight: var(--font-weight-bold); font-size: 1.1rem; transition: background-color var(--transition-fast), color var(--transition-fast); }
    .quantity-btn:hover { background: var(--color-border); }
    .quantity-btn:focus-visible { outline: 2px solid var(--color-primary); outline-offset: -2px; position: relative; z-index: 1; }
    .quantity-btn:disabled { background: var(--color-border); color: var(--color-text-light); cursor: not-allowed; opacity: 0.75; }
    .quantity-btn:disabled:hover { background: var(--color-border); }
    .quantity-input { flex: 1; min-width: 40px; border: none; outline: none; text-align: center; height: 40px; width: 100%; padding: 0 var(--spacing-xs); color: var(--color-text); background: var(--color-surface); font-size: var(--font-size-md); font-weight: var(--font-weight-bold); }
    .purchase-cta .fa-cart-plus { margin-right: var(--spacing-sm); }
    .rating-bar-label .fa-star { color: var(--color-secondary); font-size: 0.8rem; }
    .quantity-input::-webkit-outer-spin-button, .quantity-input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
    @media (max-width: 768px) {
        .purchase-action-box { align-items: stretch; flex-direction: column; }
        .quantity-select-group, .purchase-cta { width: 100%; flex-basis: auto; }
    }

    /* Detail-page presentation overrides: intentionally scoped to this page. */
    .detail-page {
        --detail-accent: rgb(0, 169, 242);
        --detail-accent-dark: rgb(0, 135, 195);
        max-width: 1200px;
        margin: 0 auto 50px;
        padding: 0 20px;
    }

    .detail-page .breadcrumb {
        gap: 8px;
        padding: 20px 0 16px;
        color: var(--color-text-light);
        font-size: 0.875rem;
        min-width: 0;
    }

    .detail-page .breadcrumb a {
        color: var(--color-text-light);
        transition: color 0.2s ease;
        white-space: nowrap;
    }

    .detail-page .breadcrumb a:hover,
    .detail-page .breadcrumb .current {
        color: var(--detail-accent);
    }

    .detail-page .breadcrumb .current {
        max-width: 400px;
        display: -webkit-box;
        overflow: hidden;
        font-weight: 700;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 1;
    }

    .detail-page .product-layout {
        grid-template-columns: minmax(0, 1fr) minmax(0, 1.4fr);
        gap: 32px;
        padding: 32px;
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        background: #fff;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.07);
    }

    .detail-page .product-main-image-container {
        height: 410px;
        padding: 20px;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        background: linear-gradient(180deg, #f8fbfd, #eef8fc);
    }

    .detail-page .product-main-image {
        border-radius: 8px;
        filter: drop-shadow(0 8px 12px rgba(15, 23, 42, 0.12));
    }

    .detail-page .product-image-gallery {
        gap: 10px;
        justify-content: flex-start;
        padding: 4px 2px 8px;
    }

    .detail-page .gallery-thumb-item {
        width: 72px;
        height: 72px;
        border-color: #dbe3ea;
        border-radius: 10px;
        background: #fff;
    }

    .detail-page .gallery-thumb-item:hover,
    .detail-page .gallery-thumb-item.active {
        border-color: var(--detail-accent) !important;
        box-shadow: 0 5px 12px rgba(0, 169, 242, 0.16);
    }

    .detail-page .product-info-wrapper {
        gap: 16px;
    }

    .detail-page .product-detail-title {
        color: #172033;
        font-size: 1.75rem;
        font-weight: 600;
        line-height: 1.3;
        font-family: "Times New Roman", Times, serif;
    }

    .detail-page .product-rating-meta {
        margin: 0;
    }

    .detail-page .product-meta-row {
        gap: 12px 24px;
        padding-bottom: 14px;
        border-bottom-color: #e2e8f0;
    }

    .detail-page .product-meta-item {
        gap: 6px;
    }

    .detail-page .product-category-badge {
        color: var(--detail-accent);
    }

    .detail-page .product-meta-row .badge,
    .detail-page .product-discount-badge {
        padding: 5px 10px;
        border-radius: 999px;
    }

    .detail-page .product-detail-price {
        color: var(--detail-accent);
        font-size: 2.2rem;
        font-weight: 600;
        font-family: "Times New Roman", Times, serif;
    }

    .detail-page .product-discount-badge {
        background: rgba(0, 169, 242, 0.12);
        color: rgb(0, 135, 195);
        font-weight: 700;
    }

    .detail-page .product-promotion-note {
        color: #2e7d32;
    }

    .detail-page .product-description-box {
        padding: 18px 0;
        border-color: #dbe3ea;
        line-height: 1.7;
    }

    .detail-page .product-description-title {
        margin-bottom: 8px;
        color: #172033;
        font-weight: 700;
    }

    .detail-page .quantity-input-wrapper {
        border-color: #dbe3ea;
        border-radius: 9px;
        background: #fff;
    }

    .detail-page .quantity-input-wrapper:focus-within {
        border-color: var(--detail-accent);
        box-shadow: 0 0 0 3px rgba(0, 169, 242, 0.12);
    }

    .detail-page .quantity-btn {
        background: #f8fbfd;
    }

    .detail-page .quantity-btn:hover:not(:disabled) {
        background: rgba(0, 169, 242, 0.10);
        color: var(--detail-accent-dark);
    }

    .detail-page .purchase-cta .btn--primary {
        min-height: 42px;
        border-color: var(--detail-accent);
        border-radius: 9px;
        background: var(--detail-accent);
        color: #fff;
    }

    .detail-page .purchase-cta .btn--primary:hover:not(:disabled) {
        border-color: var(--detail-accent-dark);
        background: var(--detail-accent-dark);
        box-shadow: 0 7px 18px rgba(0, 169, 242, 0.22);
    }

    .detail-page .reviews-section {
        margin-top: 30px;
        padding: 30px;
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        background: #fff;
        box-shadow: 0 8px 24px rgba(15, 23, 42, 0.06);
    }

    .detail-page .reviews-section-title {
        margin-bottom: 24px;
        padding-bottom: 14px;
        border-bottom-color: #e2e8f0;
        color: #172033;
        font-size: 1.2rem;
        font-family: "Times New Roman", Times, serif;
    }

    .detail-page .review-form-title {
        font-family: "Times New Roman", Times, serif;
    }

    .detail-page .reviews-section-title i {
        color: var(--detail-accent);
    }

    .detail-page .rating-summary-box {
        padding: 24px;
        border: 1px solid #e7eef3;
        border-radius: 14px;
        background: linear-gradient(135deg, #f8fbfd, #eefaff);
    }

    .detail-page .rating-average-number {
        color: var(--detail-accent);
    }

    .detail-page .rating-bar-fill {
        background: var(--detail-accent);
    }

    .detail-page .review-item {
        padding: 18px;
        border: 1px solid #e7eef3;
        border-radius: 14px;
        background: #fff;
    }

    .detail-page .review-item:last-child {
        padding-bottom: 18px;
        border-bottom: 1px solid #e7eef3;
    }

    .detail-page .review-avatar {
        background: var(--detail-accent);
    }

    .detail-page .no-reviews-placeholder {
        padding: 36px 20px;
        border: 1px dashed #cfdce5;
        border-radius: 14px;
        background: #f8fbfd;
    }

    .detail-page .no-reviews-placeholder i {
        color: rgba(0, 169, 242, 0.45);
    }

    .detail-page .review-form-container {
        padding: 24px;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        background: #f8fbfd;
    }

    .detail-page .review-submit,
    .detail-page .review-login-prompt .btn--outline {
        border-color: var(--detail-accent);
        color: var(--detail-accent);
    }

    .detail-page .review-submit {
        background: var(--detail-accent);
        color: #fff;
    }

    /* Header DOM and icons stay untouched; only the detail-page search presentation changes. */
    .header-search:focus-within {
        border-color: rgb(0, 169, 242);
        box-shadow: 0 0 0 3px rgba(0, 169, 242, 0.12);
    }

    .header-search button {
        background: rgb(0, 169, 242);
    }

    .header-search button:hover {
        background: rgb(0, 135, 195);
    }

    @media (max-width: 768px) {
        .detail-page {
            padding: 0 14px;
        }

        .detail-page .product-layout {
            grid-template-columns: 1fr;
            gap: 24px;
            padding: 20px;
            border-radius: 16px;
        }

        .detail-page .product-main-image-container {
            height: 350px;
        }

        .detail-page .purchase-action-box {
            flex-direction: column;
            align-items: stretch;
        }

        .detail-page .quantity-select-group,
        .detail-page .purchase-cta {
            width: 100%;
            flex-basis: auto;
        }

        .detail-page .reviews-section {
            padding: 20px;
            border-radius: 16px;
        }
    }

    @media (max-width: 480px) {
        .detail-page .breadcrumb .current {
            max-width: 160px;
        }

        .detail-page .product-layout {
            padding: 14px;
        }

        .detail-page .product-main-image-container {
            height: 300px;
            padding: 14px;
        }

        .detail-page .product-detail-title {
            font-size: 1.5rem;
        }

        .detail-page .product-detail-price {
            font-size: 1.8rem;
        }

        .detail-page .product-price-row {
            gap: 8px 12px;
        }

        .detail-page .reviews-section {
            padding: 14px;
        }

        .detail-page .rating-summary-box,
        .detail-page .review-form-container {
            padding: 16px;
        }

        .detail-page .rating-bar-row {
            gap: 6px;
        }

        .detail-page .review-item {
            flex-direction: column;
        }
    }
</style>

<main class="detail-container detail-page">
    <div class="breadcrumb">
        <a href="index.php">Trang chủ</a>
        <span class="separator">›</span>
        <a href="category.php">Cửa hàng</a>
        <span class="separator">›</span>
        <span class="current"><?= htmlspecialchars($product['ProductName']) ?></span>
    </div>

    <div class="product-layout">
        <div class="product-image-wrapper">
            <div class="product-main-image-container">
                <?php $defaultImg = getProductImage($productImages[0]['ImageURL']); ?>
                <img id="main-product-image" src="<?= $defaultImg ?>" class="product-main-image" alt="<?= htmlspecialchars($product['ProductName']) ?>">
            </div>
            
            <?php if (count($productImages) > 1): ?>
                <div class="product-image-gallery">
                    <?php foreach ($productImages as $index => $img): ?>
                        <div class="gallery-thumb-item <?= $index === 0 ? 'active' : '' ?>" data-src="<?= getProductImage($img['ImageURL']) ?>">
                            <img src="<?= getProductImage($img['ImageURL']) ?>" alt="Ảnh phụ <?= $index + 1 ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="product-info-wrapper">
            <h1 class="product-detail-title"><?= htmlspecialchars($product['ProductName']) ?></h1>
            
            <div class="product-rating-meta">
                <div class="product-rating-stars">
                    <?php 
                    $fullStars = floor($avgRating);
                    $halfStar = ($avgRating - $fullStars) >= 0.5 ? 1 : 0;
                    $emptyStars = 5 - $fullStars - $halfStar;
                    for ($i = 0; $i < $fullStars; $i++) echo '<i class="fa-solid fa-star"></i>';
                    if ($halfStar) echo '<i class="fa-solid fa-star-half-stroke"></i>';
                    for ($i = 0; $i < $emptyStars; $i++) echo '<i class="fa-regular fa-star"></i>';
                    ?>
                </div>
                <span class="product-rating-average"><?= $avgRating > 0 ? $avgRating : '0.0' ?></span>
                <span class="product-rating-count">(<?= $totalReviews ?> đánh giá)</span>
            </div>

            <div class="product-meta-row">
                <div class="product-meta-item">
                    <span class="product-meta-label">Thể loại:</span>
                    <span class="product-category-badge"><?= htmlspecialchars($product['CategoryName'] ?? 'Chưa phân loại') ?></span>
                </div>
                <div class="product-meta-item">
                    <span class="product-meta-label">Trạng thái kho:</span>
                    <?php if($product['Status'] == 'Hết hàng'): ?>
                        <span class="badge badge--error">Hết hàng</span>
                    <?php else: ?>
                        <span class="badge badge--success">Còn hàng</span>
                    <?php endif; ?>
                </div>
            </div>

            <?php 
            $originalPrice = $product['Price'];
            $discountRate = isset($product['DiscountRate']) ? floatval($product['DiscountRate']) : 0;
            $discountedPrice = $originalPrice - ($originalPrice * $discountRate / 100);
            ?>

            <?php if ($discountRate > 0): ?>
                <div class="product-price-row">
                    <div class="product-detail-price"><?= number_format($discountedPrice, 0, ',', '.') ?> đ</div>
                    <div class="product-original-price"><?= number_format($originalPrice, 0, ',', '.') ?> đ</div>
                    <span class="badge badge--accent product-discount-badge">-<?= number_format($discountRate, 0) ?>%</span>
                </div>
                <div class="product-promotion-note">
                    <i class="fa-solid fa-tags"></i> Áp dụng chương trình: <?= htmlspecialchars($product['PromotionName']) ?>
                </div>
            <?php else: ?>
                <div class="product-detail-price"><?= number_format($originalPrice, 0, ',', '.') ?> đ</div>
            <?php endif; ?>

            <div class="product-description-box">
                <h4 class="product-description-title">Tóm tắt nội dung tác phẩm:</h4>
                <div class="product-description-content">
                    <?= !empty($product['Description']) ? htmlspecialchars($product['Description']) : 'Mô tả nội dung chi tiết cho đầu sách này đang được cập nhật...' ?>
                </div>
            </div>

            <form action="../cart/add.php" method="POST" class="purchase-action-box">
                <input type="hidden" name="product_id" value="<?= $product['ProductID'] ?>">
                
                <div class="form-group quantity-select-group">
                    <label class="form-label quantity-label">Chọn số lượng:</label>
                    <div class="quantity-input-wrapper">
                        <button type="button" class="quantity-btn quantity-btn--minus" onclick="decreaseQty()" disabled aria-disabled="true">-</button>
                        <input type="number" id="quantity" name="quantity" class="quantity-input" value="1" min="1" max="<?= max(1, min(99, (int) $product['Stock'])) ?>">
                        <button type="button" class="quantity-btn" onclick="increaseQty()">+</button>
                    </div>
                </div>

                <div class="purchase-cta">
                    <button type="submit" class="btn btn--primary" <?= $product['Status'] == 'Hết hàng' || (int) $product['Stock'] <= 0 ? 'disabled' : '' ?>>
                        <i class="fa-solid fa-cart-plus" aria-hidden="true"></i>Thêm vào giỏ hàng
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- PHẦN ĐÁNH GIÁ & BÌNH LUẬN -->
    <section class="reviews-section">
        <h2 class="reviews-section-title">
            <i class="fa-solid fa-comments"></i> Đánh giá từ độc giả
        </h2>

        <!-- Khối thống kê tổng quan -->
        <div class="rating-summary-box">
            <div class="rating-average-card">
                <div class="rating-average-number"><?= $avgRating > 0 ? $avgRating : '0.0' ?></div>
                <div class="rating-average-stars">
                    <?php 
                    for ($i = 0; $i < $fullStars; $i++) echo '<i class="fa-solid fa-star"></i>';
                    if ($halfStar) echo '<i class="fa-solid fa-star-half-stroke"></i>';
                    for ($i = 0; $i < $emptyStars; $i++) echo '<i class="fa-regular fa-star"></i>';
                    ?>
                </div>
                <div class="rating-average-count">Tất cả <?= $totalReviews ?> đánh giá</div>
            </div>

            <div class="rating-bars-list">
                <?php for ($star = 5; $star >= 1; $star--): 
                    $count = $starCounts[$star];
                    $percent = $totalReviews > 0 ? round(($count / $totalReviews) * 100) : 0;
                ?>
                    <div class="rating-bar-row">
                        <span class="rating-bar-label"><?= $star ?> sao <i class="fa-solid fa-star" aria-hidden="true"></i></span>
                        <div class="rating-bar-track">
                            <div class="rating-bar-fill" style="--rating-percent: <?= $percent ?>%;"></div>
                        </div>
                        <span class="rating-bar-percent"><?= $percent ?>%</span>
                    </div>
                <?php endfor; ?>
            </div>
        </div>

        <!-- Thông báo từ session -->
        <?php if (isset($_SESSION['review_success'])): ?>
            <div class="alert alert--success review-alert">
                <i class="alert__icon fa-solid fa-circle-check"></i>
                <div class="alert__content">
                    <div class="alert__title">Thành công</div>
                    <div><?= htmlspecialchars($_SESSION['review_success']) ?></div>
                </div>
            </div>
            <?php unset($_SESSION['review_success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['review_error'])): ?>
            <div class="alert alert--error review-alert">
                <i class="alert__icon fa-solid fa-circle-xmark"></i>
                <div class="alert__content">
                    <div class="alert__title">Lỗi</div>
                    <div><?= htmlspecialchars($_SESSION['review_error']) ?></div>
                </div>
            </div>
            <?php unset($_SESSION['review_error']); ?>
        <?php endif; ?>

        <!-- Danh sách bình luận -->
        <div class="reviews-list">
            <?php if (empty($reviews)): ?>
                <div class="no-reviews-placeholder">
                    <i class="fa-regular fa-comment-dots"></i>
                    <span>Chưa có đánh giá nào cho cuốn sách này. Hãy là người đầu tiên chia sẻ cảm nhận của bạn!</span>
                </div>
            <?php else: ?>
                <?php foreach ($reviews as $rev): 
                    // Tạo avatar viết tắt từ tên người dùng
                    $fullName = trim(($rev['LastName'] ?? '') . ' ' . ($rev['FirstName'] ?? ''));
                    if (empty($fullName)) {
                        $fullName = 'Khách hàng';
                    }
                    $words = explode(' ', $fullName);
                    $initials = '';
                    if (count($words) >= 2) {
                        $initials = mb_substr($words[0], 0, 1) . mb_substr(end($words), 0, 1);
                    } else {
                        $initials = mb_substr($fullName, 0, 2);
                    }
                    $initials = mb_strtoupper($initials);
                ?>
                    <div class="review-item">
                        <div class="review-avatar"><?= htmlspecialchars($initials) ?></div>
                        <div class="review-body">
                            <div class="review-header">
                                <div class="review-user-info">
                                    <span class="review-username"><?= htmlspecialchars($fullName) ?></span>
                                    <?php if (intval($rev['VerifiedPurchase']) > 0): ?>
                                        <span class="review-verified-badge">
                                            <i class="fa-solid fa-circle-check"></i> Đã mua hàng
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <span class="review-date"><?= date('d/m/Y H:i', strtotime($rev['ReviewDate'])) ?></span>
                            </div>
                            <div class="review-stars">
                                <?php 
                                $r = intval($rev['Rating']);
                                for ($i = 0; $i < $r; $i++) echo '<i class="fa-solid fa-star"></i>';
                                for ($i = 0; $i < (5 - $r); $i++) echo '<i class="fa-regular fa-star review-star-empty"></i>';
                                ?>
                            </div>
                            <p class="review-comment"><?= htmlspecialchars($rev['Comment']) ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Biểu mẫu gửi đánh giá mới -->
        <div class="review-form-container" id="review-form-section">
            <h3 class="review-form-title">Để lại nhận xét của bạn</h3>
            <?php if (isset($_SESSION['user'])): ?>
                <form action="add_review.php" method="POST">
                    <input type="hidden" name="product_id" value="<?= $productId ?>">
                    
                    <div class="star-selector-group">
                        <span class="star-selector-label">Đánh giá của bạn về cuốn sách này:</span>
                        <div class="rating-input">
                            <input type="radio" id="star5" name="rating" value="5" required>
                            <label for="star5" title="5 sao"><i class="fa-solid fa-star"></i></label>
                            
                            <input type="radio" id="star4" name="rating" value="4">
                            <label for="star4" title="4 sao"><i class="fa-solid fa-star"></i></label>
                            
                            <input type="radio" id="star3" name="rating" value="3">
                            <label for="star3" title="3 sao"><i class="fa-solid fa-star"></i></label>
                            
                            <input type="radio" id="star2" name="rating" value="2">
                            <label for="star2" title="2 sao"><i class="fa-solid fa-star"></i></label>
                            
                            <input type="radio" id="star1" name="rating" value="1">
                            <label for="star1" title="1 sao"><i class="fa-solid fa-star"></i></label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label review-comment-label" for="comment">Nội dung bình luận:</label>
                        <textarea class="form-control review-comment-input" id="comment" name="comment" rows="4" placeholder="Nhập cảm nhận của bạn về nội dung cuốn sách, dịch vụ giao hàng hoặc đóng gói..." required></textarea>
                    </div>
                    
                    <div class="review-form-actions">
                        <button type="submit" class="btn btn--primary review-submit">Gửi đánh giá</button>
                    </div>
                </form>
            <?php else: ?>
                <div class="review-login-prompt">
                    <p>Bạn phải đăng nhập tài khoản thành viên để gửi bình luận và đánh giá cho cuốn sách này.</p>
                    <a href="<?= url('auth/pages/login.php') ?>" class="btn btn--outline">Đăng nhập ngay</a>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php include '../includes/footer.php'; ?>
<script>
    const qtyInput = document.getElementById('quantity');
    const qtyMinusButton = document.querySelector('.quantity-btn--minus');

    function syncMinusButton() {
        let value = parseInt(qtyInput.value, 10);
        if (!Number.isFinite(value) || value < 1) {
            value = 1;
            qtyInput.value = value;
        }

        const isMinimum = value <= 1;
        qtyMinusButton.disabled = isMinimum;
        qtyMinusButton.setAttribute('aria-disabled', isMinimum ? 'true' : 'false');
    }

    function increaseQty() {
        let current = parseInt(qtyInput.value, 10) || 1;
        const maxQuantity = parseInt(qtyInput.max, 10) || 99;
        if (current < maxQuantity) qtyInput.value = current + 1;
        syncMinusButton();
    }

    function decreaseQty() {
        let current = parseInt(qtyInput.value, 10) || 1;
        if (current > 1) qtyInput.value = current - 1;
        syncMinusButton();
    }

    qtyInput.addEventListener('input', syncMinusButton);
    qtyInput.addEventListener('change', syncMinusButton);
    syncMinusButton();

    // Tự động cuộn mượt và focus vào ô bình luận khi url có #review-form-section
    window.addEventListener('DOMContentLoaded', () => {
        if (window.location.hash === '#review-form-section') {
            const reviewForm = document.getElementById('review-form-section');
            if (reviewForm) {
                setTimeout(() => {
                    reviewForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    const commentArea = document.getElementById('comment');
                    if (commentArea) {
                        commentArea.focus();
                    }
                }, 300); // Trì hoãn nhẹ để trang ổn định giao diện trước khi cuộn
            }
        }

        // Xử lý chuyển đổi hình ảnh trong thư viện (gallery)
        const mainImg = document.getElementById('main-product-image');
        const thumbs = document.querySelectorAll('.gallery-thumb-item');
        
        thumbs.forEach(thumb => {
            thumb.addEventListener('click', function() {
                const newSrc = this.getAttribute('data-src');
                if (mainImg) {
                    mainImg.style.opacity = 0;
                    setTimeout(() => {
                        mainImg.src = newSrc;
                        mainImg.style.opacity = 1;
                    }, 150);
                }
                
                thumbs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');
            });
        });
    });
</script>
</body>
</html>
