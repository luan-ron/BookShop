<?php
/** @var string $pageTitle Tiêu đề trang định nghĩa riêng trước khi include */
$pageTitle = $pageTitle ?? 'BookShop';

// Thực hiện kết nối và lấy danh mục dùng chung toàn hệ thống từ $conn
$global_categories = [];
if (isset($conn)) {
    $conn->set_charset("utf8mb4");
    $res_cat = $conn->query("SELECT * FROM category ORDER BY CategoryID ASC");
    if ($res_cat && $res_cat->num_rows > 0) {
        while ($c = $res_cat->fetch_assoc()) {
            $global_categories[] = $c;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>

    <link rel="stylesheet" href="/BookShop/assets/css/variables.css?v=<?= time() ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js@1.12.0/src/toastify.min.css">

    <link rel="stylesheet" href="<?= asset('css/components/navbar.css') ?>?v=<?= time() ?>">
    <link rel="stylesheet" href="<?= asset('css/components/footer.css') ?>?v=<?= time() ?>">

    <link rel="stylesheet" href="<?= asset('css/components/button.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/components/form.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/components/card.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/components/badge.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/components/alert_toast.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/components/modal.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/components/pagination.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/components/table.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/components/spinner.css') ?>">

    <?php if (isset($extraCss)): ?>
        <?php foreach ((array) $extraCss as $css): ?>
            <link rel="stylesheet" href="<?= asset($css) ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>

    <div class="storefront-header-sticky">
    <header class="header-top">
        <div class="header-top-container">

            <a href="<?= url('trangchu/index.php') ?>" class="header-logo" aria-label="BookShop - Trang chủ">
                <img src="<?= asset('images/books/book.png') ?>" alt="" class="header-logo-image">
                <span class="header-logo-text">BookShop</span>
            </a>

            <form action="<?= url('trangchu/search.php') ?>" method="GET" class="header-search" role="search">
                <label class="visually-hidden" for="header-search-input">Tìm kiếm sách</label>
                <input id="header-search-input" type="text" name="keyword" placeholder="Tìm kiếm tên sách, tác giả, nhà xuất bản..." required>
                <button type="submit" aria-label="Tìm kiếm">
                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                </button>
            </form>

            <div class="header-actions">

                <a href="<?= url('cart/cart.php') ?>" class="header-action-item" aria-label="Giỏ hàng">
                    <div class="header-action-icon">
                        <i class="fa-solid fa-cart-shopping" aria-hidden="true"></i>
                        <span class="header-cart-badge"><?= array_sum($_SESSION['cart'] ?? []) ?></span>
                    </div>
                    <div class="header-action-text">
                        <span style="opacity: 0; height: 0;">&nbsp;</span>
                        <strong style="margin-top: 8px;">Giỏ hàng</strong>
                    </div>
                </a>

                <a href="<?= (isset($_SESSION['user']) || isset($_SESSION['admin'])) ? url('auth/pages/profile.php') : url('auth/pages/login.php') ?>"
                    class="header-action-item" aria-label="Tài khoản">
                    <div class="header-action-icon">
                        <i class="fa-solid fa-user" aria-hidden="true"></i>
                    </div>
                    <div class="header-action-text">
                        <?php if (isset($_SESSION['user']) || isset($_SESSION['admin']) || isset($_SESSION['profile'])): ?>
                            <span style="opacity: 0; height: 0;">&nbsp;</span>
                            <strong style="margin-top: 8px;">Tài khoản</strong>
                        <?php else: ?>
                            <span style="opacity: 0; height: 0;">&nbsp;</span>
                            <strong style="margin-top: 8px;">Đăng nhập</strong>
                        <?php endif; ?>
                    </div>
                </a>

                <button type="button" class="storefront-menu-toggle" aria-label="Mở menu điều hướng"
                    aria-controls="storefront-navigation" aria-expanded="false">
                    <i class="fa-solid fa-bars" aria-hidden="true"></i>
                </button>

            </div>
        </div>
    </header>


    <nav class="orange-bar" id="storefront-navigation" aria-label="Điều hướng chính">
        <div class="orange-bar-container">

            <div class="category-dropdown-wrapper">
                <div class="category-dropdown-header">
                    <i class="fa-solid fa-bars" style="margin-right: 8px;"></i> DANH MỤC
                </div>

                <ul class="category-sidebar-list">
                    <?php if (!empty($global_categories)): ?>
                        <?php foreach ($global_categories as $cat):
                            $catName = $cat['CategoryName'];

                            // Phân loại mảng dữ liệu MEGA MENU tự động theo tên
                            $subTopics = [];
                            $authors = [];
                            if (stripos($catName, 'Kinh tế') !== false || stripos($catName, 'Tài chính') !== false) {
                                $subTopics = ['Sách kinh tế học'];
                                $authors = ['Tim Marshall'];
                            } elseif (stripos($catName, 'Kỹ năng') !== false || stripos($catName, 'Sống') !== false || stripos($catName, 'Tâm lý') !== false) {
                                $subTopics = ['Tâm lý học', 'Sách tư duy - Kỹ năng sống', 'Bài học thành công'];
                                $authors = ['Vãn Tình', 'Minh Niệm', 'Jo Hemmings'];
                            } elseif (stripos($catName, 'Thiếu nhi') !== false) {
                                $subTopics = ['Truyện kể cho bé'];
                                $authors = ['Antoine De Saint-Exupéry', 'John Boyne'];
                            } elseif (stripos($catName, 'Văn học') !== false) {
                                $subTopics = ['Tiểu Thuyết', 'Truyện ngắn', 'Tản văn', 'Tác phẩm kinh điển', 'Truyện dài'];
                                $authors = ['Higashino Keigo', 'Hae Min', 'Paulo Coelho', 'Haruki Murakami', 'Victor Hugo', 'Albert Camus'];
                            } else {
                                $subTopics = ['Tiểu Thuyết', 'Truyện ngắn', 'Tâm lý học', 'Sách kinh tế học'];
                                $authors = ['Tim Marshall', 'Hae Min', 'Lê Bảo Ngọc', 'Higashino Keigo'];
                            }
                            ?>
                            <li>
                                <a href="<?= url('trangchu/category.php?id=' . $cat['CategoryID']) ?>">
                                    <?= htmlspecialchars($catName) ?>
                                    <span style="font-weight: 300;">›</span>
                                </a>

                                <div class="category-submenu">
                                    <div class="category-submenu-title"><?= htmlspecialchars($catName) ?></div>

                                    <div>
                                        <h4 class="category-submenu-column-title">Theo chủ đề</h4>
                                        <ul class="category-submenu-list">
                                            <?php foreach ($subTopics as $topic): ?>
                                                <li><a
                                                        href="<?= url('trangchu/search.php?keyword=' . urlencode($topic)) ?>"><?= htmlspecialchars($topic) ?></a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>

                                    <div>
                                        <h4 class="category-submenu-column-title">Tác giả nổi bật</h4>
                                        <ul class="category-submenu-list">
                                            <?php foreach ($authors as $author): ?>
                                                <li><a
                                                        href="<?= url('trangchu/search.php?keyword=' . urlencode($author)) ?>"><?= htmlspecialchars($author) ?></a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <li><a href="#">Hệ thống đang tải danh mục...</a></li>
                    <?php endif; ?>
                </ul>
            </div>

            <ul class="top-bar-menu">
                <li><a href="<?= url('trangchu/gioithieu.php') ?>">Giới thiệu</a></li>
                <li><a href="#">Tin tức</a></li>
                <li><a href="#">Review sách</a></li>
                <li><a href="<?= url('cart/tracking.php') ?>">Tra cứu đơn</a></li>
            </ul>

        </div>
    </nav>
    </div>
