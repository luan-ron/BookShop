<?php
require_once '../config/db.php';

// Lấy các tham số lọc từ URL (GET)
$categoryId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$minPrice   = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? intval($_GET['min_price']) : 0;
$maxPrice   = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? intval($_GET['max_price']) : 0;
$publisher  = isset($_GET['publisher']) ? trim($_GET['publisher']) : '';

// Lấy thông tin tiêu đề danh mục
$categoryName = "Tất cả danh mục";
if ($categoryId > 0) {
    $stmt_cat = $conn->prepare("SELECT CategoryName FROM category WHERE CategoryID = ?");
    $stmt_cat->bind_param("i", $categoryId);
    $stmt_cat->execute();
    $result_cat = $stmt_cat->get_result();
    if ($row = $result_cat->fetch_assoc()) {
        $categoryName = $row['CategoryName'];
    }
    $stmt_cat->close();
}

$pageTitle = $categoryName . ' - Danh mục sách';
$extraCss = ['css/components/card.css', 'css/components/button.css', 'css/components/badge.css', 'css/components/form.css'];
include '../includes/header.php';

// XÂY DỰNG CÂU LỆNH TRUY VẤN LỌC SẢN PHẨM (FILTER LOGIC)
$sql_products = "
    SELECT p.ProductID, p.ProductName, p.Price, p.Status, p.Publisher, c.CategoryName, i.ImageURL,
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
    WHERE 1=1
";

// Lọc theo Danh mục
if ($categoryId > 0) {
    $sql_products .= " AND p.CategoryID = $categoryId";
}
// Lọc theo Giá Tối thiểu
if ($minPrice > 0) {
    $sql_products .= " AND p.Price >= $minPrice";
}
// Lọc theo Giá Tối đa
if ($maxPrice > 0) {
    $sql_products .= " AND p.Price <= $maxPrice";
}
// Lọc theo Thương hiệu
if (!empty($publisher)) {
    // Escaping để chống SQL Injection
    $safe_publisher = $conn->real_escape_string($publisher);
    $sql_products .= " AND p.Publisher = '$safe_publisher'";
}

$sql_products .= " ORDER BY p.ProductID DESC";
$products = $conn->query($sql_products);

// Lấy danh sách Thương hiệu độc nhất đang có trong Database để hiển thị ra dropdown
$publishers_query = $conn->query("SELECT DISTINCT Publisher FROM product WHERE Publisher IS NOT NULL AND Publisher != ''");
?>

<style>
    .category-page {
        --category-accent: rgb(0, 169, 242);
        --category-accent-dark: rgb(0, 135, 195);
        max-width: 1200px;
        margin: 0 auto 50px;
        padding: 0 20px;
    }

    .category-page .breadcrumb {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 20px 0 16px;
        font-size: 0.875rem;
        color: var(--color-text-light);
    }

    .category-page .breadcrumb a {
        color: var(--color-text-light);
        text-decoration: none;
        transition: color 0.2s ease;
    }

    .category-page .breadcrumb a:hover,
    .category-page .breadcrumb .current {
        color: var(--category-accent);
    }

    .category-page .breadcrumb .current {
        font-weight: 700;
    }

    .category-page .breadcrumb .separator {
        color: #94a3b8;
        font-size: 1.125rem;
    }

    .category-page .category-banner {
        position: relative;
        width: 100%;
        min-height: 220px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 28px;
        padding: 30px;
        overflow: hidden;
        border-radius: 20px;
        background:
            linear-gradient(135deg, rgba(15, 23, 42, 0.35), rgba(15, 23, 42, 0.35)),
            url('/BookShop/assets/images/category-bg.png') center / cover;
        background-color: #fcecd7;
        color: #fff;
        text-align: center;
        box-shadow: 0 12px 30px rgba(0, 169, 242, 0.18);
    }

    .category-page .category-banner::after {
        content: "";
        position: absolute;
        top: -80px;
        right: -70px;
        width: 220px;
        height: 220px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.08);
    }

    .category-page .category-banner__title {
        position: relative;
        z-index: 1;
        margin: 0;
        font-size: 2.2rem;
        font-weight: 700;
        font-family: "Times New Roman", Times, serif;
        text-transform: uppercase;
        text-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    }

    .category-page .advanced-filter-bar {
        margin-bottom: 30px;
        padding: 20px 24px;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 6px 20px rgba(15, 23, 42, 0.06);
    }

    .category-page .filter-form {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 20px;
    }

    .category-page .filter-group,
    .category-page .filter-price-inputs {
        display: flex;
        align-items: center;
    }

    .category-page .filter-group {
        gap: 10px;
    }

    .category-page .filter-price-inputs {
        gap: 8px;
    }

    .category-page .filter-label {
        color: var(--color-text);
        font-size: 0.875rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .category-page .filter-input,
    .category-page .filter-select {
        padding: 10px 12px;
        outline: none;
        border: 1px solid #dbe3ea;
        border-radius: 9px;
        background-color: #fff;
        color: var(--color-text);
        transition: border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .category-page .filter-input {
        width: 130px;
    }

    .category-page .filter-select {
        min-width: 200px;
        padding-right: 36px;
        cursor: pointer;
    }

    .category-page .filter-input:focus,
    .category-page .filter-select:focus {
        border-color: var(--category-accent);
        box-shadow: 0 0 0 3px rgba(0, 169, 242, 0.12);
    }

    .category-page .category-filter-submit {
        padding: 10px 22px;
        border-color: var(--category-accent);
        border-radius: 9px;
        background: var(--category-accent);
        color: #fff;
        font-weight: 700;
    }

    .category-page .category-filter-submit:hover {
        border-color: var(--category-accent-dark);
        background: var(--category-accent-dark);
        transform: translateY(-1px);
        box-shadow: 0 6px 15px rgba(0, 169, 242, 0.22);
    }

    .category-page .category-clear-filter {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 8px;
        border-radius: 8px;
        color: var(--category-accent);
    }

    .category-page .category-clear-filter:hover {
        background: rgba(0, 169, 242, 0.08);
    }

    .category-page .card-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 24px;
    }

    .category-page .category-product-card {
        overflow: hidden;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        background: #fff;
        box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06);
        transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
    }

    .category-page .category-product-card:hover {
        transform: translateY(-6px);
        border-color: rgba(0, 169, 242, 0.35);
        box-shadow: 0 15px 32px rgba(0, 169, 242, 0.16);
    }

    .category-page .category-product-media {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 240px;
        padding: 16px;
        overflow: hidden;
        box-sizing: border-box;
        background: linear-gradient(180deg, #f8fbfd, #eef8fc);
    }

    .category-page .category-product-image {
        max-width: 100%;
        max-height: 100%;
        border-radius: 6px;
        object-fit: contain;
        box-shadow: 0 5px 12px rgba(15, 23, 42, 0.10);
        transition: transform 0.3s ease;
    }

    .category-page .category-product-card:hover .category-product-image {
        transform: scale(1.04);
    }

    .category-page .category-product-card .card__body {
        padding: 16px;
        gap: 8px;
    }

    .category-page .category-product-title {
        min-height: 44px;
        display: -webkit-box;
        overflow: hidden;
        color: #172033;
        font-size: 0.9375rem;
        font-weight: 700;
        line-height: 1.45;
        font-family: "Times New Roman", Times, serif;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }

    .category-page .category-product-publisher {
        margin-top: 4px;
        color: #64748b;
        font-size: 0.8125rem;
    }

    .category-page .category-publisher-name,
    .category-page .category-current-price,
    .category-page .card__price {
        color: var(--category-accent);
        font-weight: 700;
        font-family: "Times New Roman", Times, serif;
    }

    .category-page .card__price {
        margin-top: 4px;
        font-size: 1.125rem;
    }

    .category-page .category-price-row {
        display: flex;
        flex-wrap: wrap;
        align-items: baseline;
        gap: 6px;
    }

    .category-page .category-old-price {
        color: #94a3b8;
        font-size: 0.8125rem;
        font-weight: 400;
        text-decoration: line-through;
    }

    .category-page .category-card-badges {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: var(--spacing-xs);
        margin-top: 8px;
    }

    .category-page .category-product-card .badge {
        padding: 5px 10px;
        border-radius: 999px;
        font-size: 0.6875rem;
        font-weight: 700;
    }

    .category-page .category-discount-badge {
        background: rgba(0, 169, 242, 0.12);
        color: rgb(0, 135, 195);
    }

    .category-page .category-product-card .card__footer {
        gap: 8px;
        padding: 14px 16px;
        border-top: 1px solid #eef2f6;
        background: #fff;
    }

    .category-page .category-card-action {
        flex: 1;
        min-height: 36px;
        border-radius: 8px;
        font-weight: 700;
    }

    .category-page a.category-card-action {
        line-height: 28px;
        text-align: center;
    }

    .category-page .category-add-form {
        flex: 1;
        display: flex;
        margin: 0;
    }

    .category-page button.category-card-action {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }

    .category-page .category-product-card .btn--outline {
        border-color: var(--category-accent);
        background: #fff;
        color: var(--category-accent);
    }

    .category-page .category-product-card .btn--outline:hover,
    .category-page .category-product-card .btn--primary {
        border-color: var(--category-accent);
        background: var(--category-accent);
        color: #fff;
    }

    .category-page .category-product-card .btn--primary:hover {
        border-color: var(--category-accent-dark);
        background: var(--category-accent-dark);
    }

    .category-page .category-empty-state {
        grid-column: 1 / -1;
        padding: 60px var(--spacing-md);
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        background: #fff;
        text-align: center;
        box-shadow: 0 6px 20px rgba(15, 23, 42, 0.05);
    }

    .category-page .category-empty-icon {
        display: block;
        margin-bottom: 12px;
        color: var(--color-text-light);
        font-size: 3rem;
    }

    .category-page .category-empty-title {
        margin: var(--spacing-sm) 0;
        color: var(--color-text);
    }

    .category-page .category-empty-copy {
        margin-bottom: var(--spacing-md);
        color: var(--color-text-light);
    }

    @media (max-width: 768px) {
        .category-page {
            padding: 0 14px;
        }

        .category-page .category-banner {
            min-height: 180px;
            padding: 24px 16px;
            border-radius: 14px;
        }

        .category-page .category-banner__title {
            font-size: 1.6rem;
        }

        .category-page .advanced-filter-bar {
            padding: 16px;
            border-radius: 14px;
        }

        .category-page .filter-form,
        .category-page .filter-group {
            flex-direction: column;
            align-items: stretch;
        }

        .category-page .filter-form {
            gap: 14px;
        }

        .category-page .filter-group,
        .category-page .filter-price-inputs,
        .category-page .filter-select,
        .category-page .advanced-filter-bar .btn {
            width: 100%;
        }

        .category-page .filter-input {
            width: 50%;
            min-width: 0;
        }

        .category-page .card-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
        }

        .category-page .category-product-media {
            height: 200px;
        }

        .category-page .category-product-card .card__body {
            padding: 12px;
        }

        .category-page .category-product-card .card__footer {
            flex-direction: column;
            padding: 10px;
        }

        .category-page .category-card-action,
        .category-page .category-add-form {
            width: 100%;
        }
    }

    @media (max-width: 480px) {
        .category-page .card-grid {
            grid-template-columns: 1fr;
        }

        .category-page .category-product-media {
            height: 260px;
        }
    }
</style>

<main class="page-container category-page">
    
    <div class="breadcrumb">
        <a href="index.php">Trang chủ</a>
        <span class="separator">›</span>
        <span class="current"><?= htmlspecialchars($categoryName) ?></span>
    </div>

    <div class="category-banner">
        <h1 class="category-banner__title">Tủ Sách <?= htmlspecialchars($categoryName) ?></h1>
    </div>

    <div class="advanced-filter-bar">
        <form action="category.php" method="GET" class="filter-form">
            <?php if ($categoryId > 0): ?>
                <input type="hidden" name="id" value="<?= $categoryId ?>">
            <?php endif; ?>

            <div class="filter-group">
                <span class="filter-label">Khoảng giá:</span>
                <div class="filter-price-inputs">
                    <input type="number" name="min_price" class="filter-input" placeholder="0 đ" value="<?= $minPrice > 0 ? $minPrice : '' ?>" min="0">
                    <span class="separator">-</span>
                    <input type="number" name="max_price" class="filter-input" placeholder="1.000.000 đ" value="<?= $maxPrice > 0 ? $maxPrice : '' ?>" min="0">
                </div>
            </div>

            <div class="filter-group">
                <span class="filter-label">Thương hiệu:</span>
                <select name="publisher" class="filter-select">
                    <option value="">-- Tất cả thương hiệu --</option>
                    <?php if ($publishers_query && $publishers_query->num_rows > 0): ?>
                        <?php while($pub = $publishers_query->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($pub['Publisher']) ?>" <?= ($publisher === $pub['Publisher']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($pub['Publisher']) ?>
                            </option>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <option value="AlphaBooks">AlphaBooks</option>
                        <option value="Nhã Nam">Nhã Nam</option>
                    <?php endif; ?>
                </select>
            </div>

            <button type="submit" class="btn btn--primary category-filter-submit">Lọc kết quả</button>
            
            <?php if ($minPrice > 0 || $maxPrice > 0 || !empty($publisher)): ?>
                <a href="category.php<?= $categoryId > 0 ? '?id='.$categoryId : '' ?>" class="btn btn--ghost category-clear-filter">Xóa lọc <i class="fa-solid fa-xmark"></i></a>
            <?php endif; ?>
        </form>
    </div>

    <div class="card-grid">
        <?php if ($products && $products->num_rows > 0): ?>
            <?php while($product = $products->fetch_assoc()): ?>
                <div class="card card--interactive category-product-card">
                    <?php 
                        $imgSrc = getProductImage($product['ImageURL'] ?? ''); 
                    ?>
                    <div class="category-product-media">
                        <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($product['ProductName']) ?>" class="category-product-image">
                    </div>
                    
                    <div class="card__body">
                        <h3 class="card__title category-product-title"><?= htmlspecialchars($product['ProductName']) ?></h3>
                        
                        <p class="card__subtitle category-product-publisher">Hãng: <span class="category-publisher-name"><?= htmlspecialchars($product['Publisher'] ?? 'Chưa rõ') ?></span></p>
                        
                        <?php 
                        $originalPrice = $product['Price'];
                        $discountRate = isset($product['DiscountRate']) ? floatval($product['DiscountRate']) : 0;
                        $discountedPrice = $originalPrice - ($originalPrice * $discountRate / 100);
                        ?>

                        <?php if ($discountRate > 0): ?>
                            <div class="card__price category-price-row">
                                <span class="category-current-price"><?= number_format($discountedPrice, 0, ',', '.') ?> đ</span>
                                <span class="category-old-price"><?= number_format($originalPrice, 0, ',', '.') ?> đ</span>
                            </div>
                        <?php else: ?>
                            <div class="card__price">
                                <?= number_format($originalPrice, 0, ',', '.') ?> đ
                            </div>
                        <?php endif; ?>
                        
                        <div class="category-card-badges">
                            <?php if($product['Status'] == 'Hết hàng'): ?>
                                <span class="badge badge--error">Hết hàng</span>
                            <?php else: ?>
                                <span class="badge badge--success">Còn hàng</span>
                            <?php endif; ?>
                            
                            <?php if ($discountRate > 0): ?>
                                <span class="badge badge--accent category-discount-badge">-<?= number_format($discountRate, 0) ?>%</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="card__footer">
                        <a href="detail.php?id=<?= $product['ProductID'] ?>" class="btn btn--outline btn--sm category-card-action"><i class="fa-solid fa-eye" aria-hidden="true"></i> Chi tiết</a>
                        <form action="../cart/add.php" method="POST" class="category-add-form">
                            <input type="hidden" name="product_id" value="<?= $product['ProductID'] ?>">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="btn btn--primary btn--sm category-card-action" <?= $product['Status'] == 'Hết hàng' ? 'disabled' : '' ?>>
                                <i class="fa-solid fa-cart-plus"></i> Thêm
                            </button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="category-empty-state">
                <i class="fa-solid fa-magnifying-glass category-empty-icon"></i>
                <h3 class="category-empty-title">Không tìm thấy sách phù hợp với bộ lọc</h3>
                <p class="category-empty-copy">Vui lòng thử điều chỉnh lại khoảng giá hoặc chọn thương hiệu khác.</p>
                <a href="category.php<?= $categoryId > 0 ? '?id='.$categoryId : '' ?>" class="btn btn--primary">Xóa bộ lọc</a>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
</body>
</html>
