<?php
require_once '../config/db.php';

$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$pageTitle = 'Kết quả tìm kiếm cho: "' . htmlspecialchars($keyword) . '"';
$extraCss = ['css/components/card.css', 'css/components/button.css', 'css/components/badge.css', 'css/components/form.css'];
include '../includes/header.php';

$products = null;
if (!empty($keyword)) {
    $searchPattern = "%" . $keyword . "%";
    
    $sql_search = "
        SELECT p.ProductID, p.ProductName, p.Price, p.Status, c.CategoryName, i.ImageURL 
        FROM product p
        LEFT JOIN category c ON p.CategoryID = c.CategoryID
        LEFT JOIN image i ON p.ProductID = i.ProductID AND i.IsThumbnail = 1
        WHERE p.ProductName LIKE ? OR c.CategoryName LIKE ? OR p.Description LIKE ?
        ORDER BY p.ProductID DESC
    ";
    
    $stmt = $conn->prepare($sql_search);
    $stmt->bind_param("sss", $searchPattern, $searchPattern, $searchPattern);
    $stmt->execute();
    $products = $stmt->get_result();
}
?>

<style>
    .search-page {
        --search-accent: rgb(0, 169, 242);
        --search-accent-dark: rgb(0, 135, 195);
        max-width: 1200px;
        margin: 0 auto 50px;
        padding: 0 20px;
    }

    .search-page .breadcrumb {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 14px 0 10px;
        color: var(--color-text-light);
        font-size: 0.875rem;
    }

    .search-page .breadcrumb a {
        color: var(--color-text-light);
        text-decoration: none;
        transition: color 0.2s ease;
    }

    .search-page .breadcrumb a:hover,
    .search-page .breadcrumb .current {
        color: var(--search-accent);
    }

    .search-page .breadcrumb .current {
        font-weight: 700;
    }

    .search-page .breadcrumb .separator {
        color: #94a3b8;
        font-size: 1.125rem;
    }

    .search-page .search-header {
        position: relative;
        margin-bottom: 20px;
        padding: 16px 20px;
        overflow: hidden;
        border: 1px solid #e2e8f0;
        border-left: 5px solid var(--search-accent);
        border-radius: 12px;
        background: linear-gradient(135deg, #fff 0%, #f2fbff 100%);
        box-shadow: 0 6px 20px rgba(15, 23, 42, 0.06);
    }

    .search-page .search-header::after {
        content: "\f002";
        position: absolute;
        right: 20px;
        top: 50%;
        color: rgba(0, 169, 242, 0.10);
        font-family: "Font Awesome 6 Free";
        font-size: 3.5rem;
        font-weight: 900;
        transform: translateY(-50%);
        pointer-events: none;
    }

    .search-page .search-title {
        position: relative;
        z-index: 1;
        max-width: calc(100% - 90px);
        margin: 0 0 8px;
        color: var(--color-text);
        font-size: 1.25rem;
        font-weight: 700;
        line-height: 1.35;
        font-family: "Times New Roman", Times, serif;
    }

    .search-page .keyword-highlight {
        color: var(--search-accent);
        font-style: normal;
    }

    .search-page .search-summary {
        position: relative;
        z-index: 1;
        margin: 0;
        color: var(--color-text-light);
        font-size: 0.875rem;
        line-height: 1.5;
    }

    .search-page .search-summary strong {
        color: var(--search-accent);
    }

    .search-page .card-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 18px;
    }

    .search-page .search-product-card {
        overflow: hidden;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        background: #fff;
        box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06);
        transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
    }

    .search-page .search-product-card:hover {
        transform: translateY(-6px);
        border-color: rgba(0, 169, 242, 0.35);
        box-shadow: 0 15px 32px rgba(0, 169, 242, 0.16);
    }

    .search-page .search-product-media {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 190px;
        padding: 12px;
        overflow: hidden;
        box-sizing: border-box;
        background: linear-gradient(180deg, #f8fbfd, #eef8fc);
        text-align: center;
    }

    .search-page .search-product-image {
        width: 100%;
        max-width: 100%;
        max-height: 100%;
        border-radius: 6px;
        object-fit: contain;
        box-shadow: 0 5px 12px rgba(15, 23, 42, 0.10);
        transition: transform 0.3s ease;
    }

    .search-page .search-product-card:hover .search-product-image {
        transform: scale(1.04);
    }

    .search-page .search-product-card .card__body {
        padding: 12px;
        gap: 6px;
    }

    .search-page .search-product-title {
        min-height: 38px;
        display: -webkit-box;
        overflow: hidden;
        color: #172033;
        font-size: 0.875rem;
        font-weight: 700;
        line-height: 1.35;
        font-family: "Times New Roman", Times, serif;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 2;
    }

    .search-page .search-product-category {
        color: #64748b;
        font-size: 0.75rem;
    }

    .search-page .search-product-card .card__price {
        margin-top: 4px;
        color: var(--search-accent);
        font-size: 0.95rem;
        font-weight: 700;
        font-family: "Times New Roman", Times, serif;
    }

    .search-page .search-stock-badge {
        width: fit-content;
        margin-top: 5px;
        padding: 4px 8px;
        border-radius: 999px;
        font-size: 0.6875rem;
        font-weight: 700;
    }

    .search-page .search-product-card .card__footer {
        gap: 8px;
        padding: 10px 12px;
        border-top: 1px solid #eef2f6;
        background: #fff;
    }

    .search-page .search-card-action {
        flex: 1;
        min-height: 34px;
        border-radius: 8px;
        font-weight: 700;
    }

    .search-page a.search-card-action {
        text-align: center;
    }

    .search-page .search-add-form {
        flex: 1;
        display: flex;
        margin: 0;
    }

    .search-page button.search-card-action {
        width: 100%;
    }

    .search-page .search-product-card .btn--outline {
        border-color: var(--search-accent);
        background: #fff;
        color: var(--search-accent);
    }

    .search-page .search-product-card .btn--outline:hover,
    .search-page .search-product-card .btn--primary {
        border-color: var(--search-accent);
        background: var(--search-accent);
        color: #fff;
    }

    .search-page .search-product-card .btn--primary:hover {
        border-color: var(--search-accent-dark);
        background: var(--search-accent-dark);
    }

    .search-page .search-empty-state {
        grid-column: 1 / -1;
        padding: 56px 28px;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        background: linear-gradient(180deg, #fff, #f8fbfd);
        text-align: center;
        box-shadow: 0 6px 20px rgba(15, 23, 42, 0.05);
    }

    .search-page .search-empty-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 72px;
        height: 72px;
        margin-bottom: 16px;
        border-radius: 50%;
        background: rgba(0, 169, 242, 0.10);
        color: var(--search-accent);
        font-size: 2rem;
    }

    .search-page .search-empty-title {
        margin: 0 0 10px;
        color: #172033;
    }

    .search-page .search-empty-copy {
        margin: 0 auto 24px;
        color: var(--color-text-light);
    }

    .search-page .search-empty-form {
        max-width: 450px;
        display: flex;
        gap: 8px;
        margin: 0 auto;
    }

    .search-page .search-empty-form .form-control {
        min-width: 0;
        flex: 1;
    }

    .search-page .search-empty-form .btn--primary {
        border-color: var(--search-accent);
        background: var(--search-accent);
        color: #fff;
        white-space: nowrap;
    }

    @media (max-width: 768px) {
        .search-page {
            padding: 0 14px;
        }

        .search-page .search-header {
            padding: 14px 16px;
            border-radius: 12px;
        }

        .search-page .search-title {
            max-width: calc(100% - 50px);
            font-size: 1.2rem;
        }

        .search-page .search-header::after {
            right: 16px;
            font-size: 3.25rem;
        }

        .search-page .card-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        .search-page .search-product-media {
            height: 170px;
        }

        .search-page .search-product-card .card__body {
            padding: 10px;
        }

        .search-page .search-product-card .card__footer {
            flex-direction: column;
            padding: 8px;
        }

        .search-page .search-card-action,
        .search-page .search-add-form {
            width: 100%;
        }
    }

    @media (max-width: 480px) {
        .search-page .search-title {
            max-width: 100%;
        }

        .search-page .search-header::after {
            display: none;
        }

        .search-page .card-grid {
            grid-template-columns: 1fr;
        }

        .search-page .search-product-media {
            height: 210px;
        }

        .search-page .search-empty-state {
            padding: 40px 18px;
        }

        .search-page .search-empty-form {
            flex-direction: column;
        }

        .search-page .search-empty-form .btn {
            width: 100%;
        }
    }
</style>

<main class="search-container search-page">
    <div class="breadcrumb">
        <a href="index.php">Trang chủ</a>
        <span class="separator">›</span>
        <span class="current">Tìm kiếm sản phẩm</span>
    </div>

    <div class="search-header">
        <h1 class="search-title">Kết quả tìm kiếm cho: <span class="keyword-highlight">"<?= htmlspecialchars($keyword) ?>"</span></h1>
        <?php if ($products): ?>
            <p class="search-summary">Tìm thấy <strong><?= $products->num_rows ?></strong> đầu sách phù hợp với yêu cầu tìm kiếm của bạn.</p>
        <?php endif; ?>
    </div>

    <div class="card-grid">
        <?php if ($products && $products->num_rows > 0): ?>
            <?php while($product = $products->fetch_assoc()): ?>
                <div class="card card--interactive search-product-card">
                    <?php 
                        // ĐOẠN CODE XỬ LÝ ẢNH ĐÃ ĐƯỢC CẬP NHẬT
                        if (!empty($product['ImageURL'])) {
                            if (strpos($product['ImageURL'], 'http') === 0) {
                                $imgSrc = $product['ImageURL'];
                            } else {
                                $fileName = basename($product['ImageURL']);
                                $imgSrc = asset('images/uploads/' . $fileName);
                            }
                        } else {
                            $imgSrc = asset('images/default-book.png'); 
                        }
                    ?>
                    <div class="search-product-media">
                        <img src="<?= $imgSrc ?>" class="card__image search-product-image" alt="<?= htmlspecialchars($product['ProductName']) ?>">
                    </div>
                    
                    <div class="card__body">
                        <h3 class="card__title search-product-title"><?= htmlspecialchars($product['ProductName']) ?></h3>
                        <p class="card__subtitle search-product-category"><?= htmlspecialchars($product['CategoryName'] ?? 'Chưa phân loại') ?></p>
                        
                        <div class="card__price">
                            <?= number_format($product['Price'], 0, ',', '.') ?> đ
                        </div> 
                        
                        <?php if($product['Status'] == 'Hết hàng'): ?>
                            <span class="badge badge--error search-stock-badge">Hết hàng</span>
                        <?php else: ?>
                            <span class="badge badge--success search-stock-badge">Còn hàng</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card__footer">
                        <a href="detail.php?id=<?= $product['ProductID'] ?>" class="btn btn--outline btn--sm search-card-action"><i class="fa-solid fa-eye" aria-hidden="true"></i> Chi tiết</a>
                        <form action="../cart/add.php" method="POST" class="search-add-form">
                            <input type="hidden" name="product_id" value="<?= $product['ProductID'] ?>">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="btn btn--primary btn--sm search-card-action" <?= $product['Status'] == 'Hết hàng' ? 'disabled' : '' ?>>
                                <i class="fa-solid fa-cart-plus" aria-hidden="true"></i> Thêm
                            </button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="search-empty-state">
                <div class="search-empty-icon"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i></div>
                <h3 class="search-empty-title">Rất tiếc, không tìm thấy sản phẩm tương thích!</h3>
                <p class="search-empty-copy">Hãy thử tra cứu lại bằng một từ khóa tổng quan hơn hoặc nhập tên tựa đề sách khác.</p>
                <form action="search.php" method="GET" class="search-empty-form">
                    <input type="text" name="keyword" class="form-control" placeholder="Nhập từ khóa tìm kiếm mới..." required value="<?= htmlspecialchars($keyword) ?>">
                    <button type="submit" class="btn btn--primary">Tìm kiếm</button>
                </form>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include '../includes/footer.php'; ?>
</body>
</html>
