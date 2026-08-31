<?php
require_once '../config/db.php';

// ĐÁNH DẤU CỜ ĐỂ BUNG SẴN DANH MỤC TRÊN TRANG CHỦ
$isHomepage = true;

$pageTitle = 'Trang chủ - Hệ Thống Bán Sách Trực Tuyến';
$extraCss = ['css/components/card.css', 'css/components/button.css', 'css/components/badge.css', 'css/components/form.css'];
include '../includes/header.php';
?><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.css"><?php

// Thực hiện câu lệnh truy vấn sản phẩm mới (Đã bổ sung cột Price, kết nối bảng ảnh và lấy thông tin khuyến mãi đang hoạt động)
$sql_products = "
    SELECT p.ProductID, p.ProductName, p.Price, p.Status, c.CategoryName, i.ImageURL,
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
    ORDER BY p.ProductID DESC
    LIMIT 8
";
$products = $conn->query($sql_products);
?>

<style>
    .homepage-main {
        --homepage-reference-accent: rgb(0, 169, 242);
        background: var(--color-surface);
        padding: var(--spacing-lg) 0 var(--spacing-xl);
    }

    .homepage-main .homepage-layout,
    .homepage-main .section-container {
        width: min(1200px, calc(100% - 40px));
        max-width: 1200px;
        margin-inline: auto;
    }

    .homepage-main .homepage-carousel {
        position: relative;
        min-height: 320px;
        overflow: hidden;
        border-radius: 20px;
        box-shadow: 0 12px 35px rgba(15, 23, 42, .12);
        background: var(--color-background);
    }

    .homepage-main .homepage-banner {
        position: relative;
        display: flex;
        align-items: center;
        width: 100%;
        min-height: 320px;
        padding: 0;
        color: #14233b;
        background-color: var(--color-background);
        background-size: cover;
        background-position: center;
    }

    .homepage-main .homepage-banner::before {
        content: "";
        position: absolute;
        inset: 0;
        background: linear-gradient(90deg, rgba(15, 23, 42, .76) 0%, rgba(15, 23, 42, .46) 40%, rgba(15, 23, 42, 0) 74%);
        pointer-events: none;
    }

    .homepage-main .homepage-banner__content {
        position: relative;
        z-index: 1;
        width: min(480px, 52%);
        margin-left: 6%;
        padding: 24px;
        text-align: left;
    }

    .homepage-main .homepage-banner__eyebrow {
        margin: 0 0 10px;
        color: var(--homepage-reference-accent);
        font-size: var(--font-size-base);
        font-weight: 800;
        letter-spacing: .5px;
        text-transform: uppercase;
        font-family: "Times New Roman", Times, serif;
    }

    .homepage-main .homepage-banner__eyebrow,
    .homepage-main .homepage-banner h1,
    .homepage-main .homepage-banner__description { text-shadow: 0 2px 4px rgba(15, 23, 42, .45); }

    .homepage-main .homepage-banner h1 {
        margin: 0 0 18px;
        color: #ffffff;
        font-size: 42px;
        font-weight: 900;
        line-height: 1.2;
        font-family: "Times New Roman", Times, serif;
    }

    .homepage-main .homepage-banner__description {
        margin: 0 0 22px;
        color: #f8fafc;
        font-size: 16px;
        font-weight: 600;
        line-height: 1.6;
        font-family: "Times New Roman", Times, serif;
    }

    .homepage-main .homepage-banner__cta {
        gap: var(--spacing-sm);
        min-height: 0;
        padding: 13px 25px;
        background: var(--color-primary);
        color: var(--color-surface);
        border: 0;
        border-radius: 7px;
        box-shadow: none;
        font-size: 16px;
        font-weight: 700;
        font-family: "Times New Roman", Times, serif;
    }

    .homepage-main .homepage-banner__cta:hover {
        background: var(--color-primary-hover);
        color: var(--color-surface);
        transform: translateY(-1px);
    }

    .homepage-main .homepage-carousel__control {
        position: absolute;
        top: 50%;
        z-index: 2;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 42px;
        height: 42px;
        border: 1px solid rgba(13, 158, 231, .55);
        border-radius: 50%;
        background: rgba(255, 255, 255, .84);
        color: var(--homepage-reference-accent);
        cursor: pointer;
        transform: translateY(-50%);
        transition: background-color var(--transition-fast), border-color var(--transition-fast);
    }

    .homepage-main .homepage-carousel .swiper-button-prev::after,
    .homepage-main .homepage-carousel .swiper-button-next::after {
        font-size: 1rem;
        font-weight: 700;
    }

    .homepage-main .homepage-carousel__control:hover,
    .homepage-main .homepage-carousel__control:focus-visible {
        background: var(--homepage-reference-accent);
        border-color: var(--homepage-reference-accent);
        color: var(--color-surface);
        outline: none;
    }

    .homepage-main .homepage-carousel__control--prev {
        left: var(--spacing-lg);
    }

    .homepage-main .homepage-carousel__control--next {
        right: var(--spacing-lg);
    }

    .homepage-main .homepage-carousel .swiper-pagination {
        bottom: var(--spacing-md);
    }

    .homepage-main .homepage-carousel .swiper-pagination-bullet {
        width: 10px;
        height: 10px;
        background: rgba(255, 255, 255, .82);
        opacity: 1;
    }

    .homepage-main .homepage-carousel .swiper-pagination-bullet-active {
        background: var(--homepage-reference-accent);
        outline: 2px solid rgba(255, 255, 255, .85);
        outline-offset: 2px;
    }

    .homepage-main .section-container {
        margin-top: 45px;
    }

    .homepage-main .section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: var(--spacing-md);
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 2px solid #e5edf3;
    }

    .homepage-main .section-title {
        position: relative;
        margin: 0;
        padding-left: 32px;
        color: var(--color-text);
        font-size: 1.5rem;
        font-weight: 700;
        font-family: "Times New Roman", Times, serif;
    }

    .homepage-main .section-title::before {
        content: "★";
        position: absolute;
        top: 50%;
        left: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        width: 22px;
        height: 22px;
        color: var(--color-surface);
        background: var(--homepage-reference-accent);
        border-radius: 50%;
        font-size: 11px;
        font-weight: 900;
        line-height: 1;
        transform: translateY(-50%);
    }

    .homepage-main .homepage-section-link {
        color: var(--homepage-reference-accent);
        font-weight: var(--font-weight-bold);
    }

    .homepage-main .homepage-section-link:hover {
        color: var(--color-primary-hover);
    }

    .homepage-main .homepage-product-grid {
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: var(--spacing-md);
    }

    .homepage-main .homepage-product-card__image {
        height: 220px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 12px;
        background: var(--color-background);
        overflow: hidden;
    }

    .homepage-main .homepage-product-card__image img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
        border-radius: var(--spacing-xs);
        box-shadow: 0 4px 8px rgba(0, 0, 0, .08);
        transition: transform var(--transition-base);
    }

    .homepage-main .homepage-product-card:hover .homepage-product-card__image img {
        transform: scale(1.04);
    }

    .homepage-main .homepage-product-title {
        min-height: 44px;
        display: -webkit-box;
        overflow: hidden;
        font-family: "Times New Roman", Times, serif;
        font-size: 0.9375rem;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 2;
    }

    .homepage-main .homepage-product-card .card__price {
        color: var(--homepage-reference-accent);
        font-family: "Times New Roman", Times, serif;
        font-size: 1.0625rem;
        font-weight: 700;
    }

    .homepage-main .homepage-product-price {
        display: flex;
        align-items: baseline;
        flex-wrap: wrap;
        gap: 6px;
    }

    .homepage-main .homepage-product-price__old {
        color: var(--color-text-light);
        font-size: .85rem;
        font-weight: var(--font-weight-normal);
        text-decoration: line-through;
    }

    .homepage-main .homepage-card-badges {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: var(--spacing-xs);
        margin-top: var(--spacing-sm);
    }

    .homepage-main .homepage-discount-badge {
        color: var(--color-surface);
        background: var(--homepage-reference-accent);
    }

    .homepage-main .homepage-add-form {
        display: flex;
        flex: 1;
        margin: 0;
    }

    .homepage-main .homepage-card-action {
        flex: 1;
        width: 100%;
    }

    .homepage-main .homepage-empty-state {
        grid-column: 1 / -1;
        padding: var(--spacing-xl);
        color: var(--color-text-light);
        text-align: center;
    }

    @media (max-width: 768px) {
        .homepage-main {
            padding-top: var(--spacing-md);
        }

        .homepage-main .homepage-layout,
        .homepage-main .section-container {
            width: min(100% - 24px, 744px);
        }

        .homepage-main .homepage-carousel,
        .homepage-main .homepage-banner {
            min-height: 350px;
        }

        .homepage-main .homepage-banner__content {
            width: min(500px, 70%);
            margin-left: 4%;
            padding: var(--spacing-lg);
        }

        .homepage-main .homepage-banner h1 {
            font-size: 2rem;
        }

        .homepage-main .homepage-banner__description {
            font-size: .95rem;
        }

        .homepage-main .section-container {
            margin-top: 35px;
        }

        .homepage-main .section-title {
            font-size: 1.25rem;
        }
    }

    @media (max-width: 480px) {
        .homepage-main .homepage-carousel,
        .homepage-main .homepage-banner {
            min-height: 380px;
        }

        .homepage-main .homepage-banner__content {
            width: 100%;
            margin-left: 0;
            padding: var(--spacing-xl) var(--spacing-lg);
        }

        .homepage-main .homepage-banner h1 {
            font-size: 1.75rem;
        }

        .homepage-main .homepage-carousel__control {
            width: 36px;
            height: 36px;
        }

        .homepage-main .homepage-carousel__control--prev {
            left: var(--spacing-sm);
        }

        .homepage-main .homepage-carousel__control--next {
            right: var(--spacing-sm);
        }

        .homepage-main .section-header {
            align-items: center;
        }

        .homepage-main .homepage-product-grid {
            grid-template-columns: 1fr;
        }

        .homepage-main .homepage-product-card__image {
            height: 240px;
        }
    }
</style>

<main class="homepage-main">
    <div class="homepage-layout">
        <div class="homepage-carousel swiper" aria-label="Banner giới thiệu BookShop">
            <div class="swiper-wrapper">
                <div class="homepage-banner swiper-slide" style="background-image: url('<?= asset('images/uploads/banner_homepage.jpg') ?>');">
                    <div class="homepage-banner__content">
                        <p class="homepage-banner__eyebrow">Không gian dành cho người yêu sách</p>
                        <h1>Khám phá những cuốn sách dành cho bạn</h1>
                        <p class="homepage-banner__description">Từ văn học, kỹ năng sống đến những tác phẩm kinh điển, tìm thấy cuốn sách phù hợp với hành trình của riêng bạn.</p>
                        <a href="category.php" class="btn btn--primary homepage-banner__cta">MUA SẮM NGAY</a>
                    </div>
                </div>

                <div class="homepage-banner swiper-slide" style="background-image: url('<?= asset('images/category-bg.png') ?>');">
                    <div class="homepage-banner__content">
                        <p class="homepage-banner__eyebrow">Mở rộng thế giới qua từng trang sách</p>
                        <h1>Mỗi cuốn sách, một hành trình mới</h1>
                        <p class="homepage-banner__description">Chọn một cuốn sách, mở ra một góc nhìn mới và bắt đầu hành trình khám phá tri thức cùng BookShop.</p>
                        <a href="category.php" class="btn btn--primary homepage-banner__cta">KHÁM PHÁ NGAY</a>
                    </div>
                </div>
            </div>

            <button type="button" class="homepage-carousel__control homepage-carousel__control--prev swiper-button-prev" aria-label="Banner trước"></button>
            <button type="button" class="homepage-carousel__control homepage-carousel__control--next swiper-button-next" aria-label="Banner tiếp theo"></button>
            <div class="homepage-carousel__dots swiper-pagination" aria-label="Chọn banner"></div>
        </div>
    </div>

    <section class="section-container">
        <div class="section-header">
            <div>
                <h2 class="section-title">Sách mới cập nhật</h2>
            </div>
            <a href="category.php" class="btn btn--ghost btn--sm homepage-section-link">Xem tất cả <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
        </div>
        
        <div class="card-grid homepage-product-grid">
            <?php if ($products && $products->num_rows > 0): ?>
                <?php while($product = $products->fetch_assoc()): ?>
                    <div class="card card--interactive homepage-product-card">
                        <?php 
                            $imgSrc = getProductImage($product['ImageURL'] ?? ''); 
                        ?>
                        <div class="homepage-product-card__image">
                            <img src="<?= $imgSrc ?>" alt="<?= htmlspecialchars($product['ProductName']) ?>">
                        </div>
                        
                        <div class="card__body">
                            <h3 class="card__title homepage-product-title"><?= htmlspecialchars($product['ProductName']) ?></h3>
                            <p class="card__subtitle"><?= htmlspecialchars($product['CategoryName'] ?? 'Chưa phân loại') ?></p>
                            
                            <?php 
                            $originalPrice = $product['Price'];
                            $discountRate = isset($product['DiscountRate']) ? floatval($product['DiscountRate']) : 0;
                            $discountedPrice = $originalPrice - ($originalPrice * $discountRate / 100);
                            ?>

                            <?php if ($discountRate > 0): ?>
                                <div class="card__price homepage-product-price">
                                    <span><?= number_format($discountedPrice, 0, ',', '.') ?> đ</span>
                                    <span class="homepage-product-price__old"><?= number_format($originalPrice, 0, ',', '.') ?> đ</span>
                                </div>
                            <?php else: ?>
                                <div class="card__price">
                                    <?= number_format($originalPrice, 0, ',', '.') ?> đ
                                </div>
                            <?php endif; ?>
                            
                            <div class="homepage-card-badges">
                                <?php if($product['Status'] == 'Hết hàng'): ?>
                                    <span class="badge badge--error">Hết hàng</span>
                                <?php else: ?>
                                    <span class="badge badge--success">Còn hàng</span>
                                <?php endif; ?>
                                
                                <?php if ($discountRate > 0): ?>
                                    <span class="badge badge--warning homepage-discount-badge">-<?= number_format($discountRate, 0) ?>%</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="card__footer">
                            <a href="detail.php?id=<?= $product['ProductID'] ?>" class="btn btn--outline btn--sm homepage-card-action">Chi tiết</a>
                            <form action="../cart/add.php" method="POST" class="homepage-add-form">
                                <input type="hidden" name="product_id" value="<?= $product['ProductID'] ?>">
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit" class="btn btn--primary btn--sm homepage-card-action" <?= $product['Status'] == 'Hết hàng' ? 'disabled' : '' ?>>
                                    <i class="fa-solid fa-cart-plus"></i> Thêm
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="homepage-empty-state">
                    <p>Hiện chưa có sản phẩm nào được cập nhật trong hệ thống CSDL.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php include '../includes/footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.js"></script>
<script>
    const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    new Swiper('.homepage-carousel', {
        slidesPerView: 1,
        loop: true,
        speed: prefersReducedMotion ? 0 : 600,
        autoplay: prefersReducedMotion ? false : {
            delay: 5000,
            disableOnInteraction: false
        },
        navigation: {
            nextEl: '.homepage-carousel__control--next',
            prevEl: '.homepage-carousel__control--prev'
        },
        pagination: {
            el: '.homepage-carousel__dots',
            clickable: true
        },
        keyboard: {
            enabled: true
        },
        a11y: {
            enabled: true
        }
    });
</script>
</body>
</html>
