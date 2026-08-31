<?php
function h($value) {
  return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function activeClass($page) {
  return basename($_SERVER['PHP_SELF']) === $page ? 'is-active' : '';
}

function adminCssLinks() {
  return '
  <link rel="stylesheet" href="../assets/css/variables.css?v=3">
  <link rel="stylesheet" href="../assets/css/components/button.css?v=3">
  <link rel="stylesheet" href="../assets/css/components/badge.css?v=3">
  <link rel="stylesheet" href="../assets/css/components/card.css?v=3">
  <link rel="stylesheet" href="../assets/css/components/form.css?v=3">
  <link rel="stylesheet" href="../assets/css/components/navbar.css?v=3">
  <link rel="stylesheet" href="../assets/css/components/table.css?v=3">
  <link rel="stylesheet" href="../assets/css/components/alert_toast.css?v=3">
  <link rel="stylesheet" href="../assets/css/admin.css?v=2">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js@1.12.0/src/toastify.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">';
}

function badgeClass($status) {
  if (in_array($status, ['Đang bán', 'Hiển thị', 'Hoạt động', 'Đang áp dụng', 'Hoàn thành'], true)) {
    return 'badge--success';
  }

  if (in_array($status, ['Chờ xử lý', 'Sắp hết hạn', 'Đang giao', 'Chờ xác nhận'], true)) {
    return 'badge--warning';
  }

  if (in_array($status, ['Ngừng bán', 'Ẩn', 'Đã khóa', 'Đã hết hạn', 'Đã hủy'], true)) {
    return 'badge--danger';
  }

  return 'badge--info'; // Ví dụ: 'Đã xác nhận' sẽ có màu badge--info
}

function adminSidebar() {
  ?>
  <aside class="admin-sidebar" id="admin-sidebar" aria-label="Điều hướng quản trị">
    <div class="admin-sidebar__header">
      <a href="index.php" class="admin-sidebar__brand">BookShop Admin</a>
      <button type="button" class="admin-sidebar__close" aria-label="Đóng menu quản trị">
        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
      </button>
    </div>
    <nav class="admin-sidebar__nav">
      <ul class="admin-sidebar__menu">
        <li><a href="index.php" class="admin-sidebar__link <?= activeClass('index.php') ?>"><i class="fa-solid fa-gauge-high" aria-hidden="true"></i><span>Dashboard</span></a></li>
        <li><a href="products.php" class="admin-sidebar__link <?= activeClass('products.php') ?>"><i class="fa-solid fa-book" aria-hidden="true"></i><span>Sản phẩm</span></a></li>
        <li><a href="categories.php" class="admin-sidebar__link <?= activeClass('categories.php') ?>"><i class="fa-solid fa-layer-group" aria-hidden="true"></i><span>Danh mục</span></a></li>
        <li><a href="orders.php" class="admin-sidebar__link <?= activeClass('orders.php') ?>"><i class="fa-solid fa-cart-shopping" aria-hidden="true"></i><span>Đơn hàng</span></a></li>
        <li><a href="users.php" class="admin-sidebar__link <?= activeClass('users.php') ?>"><i class="fa-solid fa-users" aria-hidden="true"></i><span>Người dùng</span></a></li>
        <li><a href="coupons.php" class="admin-sidebar__link <?= activeClass('coupons.php') ?>"><i class="fa-solid fa-ticket" aria-hidden="true"></i><span>Mã giảm giá</span></a></li>
      </ul>
    </nav>
    <div class="admin-sidebar__footer">
      <a href="/BookShop/trangchu/index.php" class="admin-sidebar__link"><i class="fa-solid fa-store" aria-hidden="true"></i><span>Về trang chủ</span></a>
      <a href="#" class="admin-sidebar__link admin-sidebar__link--danger" id="admin-logout-link"><i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i><span>Đăng xuất</span></a>
      <form id="admin-logout-form" action="/BookShop/auth/pages/login.php" method="POST" hidden>
        <input type="hidden" name="action" value="logout">
        <input type="hidden" name="type" value="admin">
        <input type="hidden" name="csrf_token" value="<?= h(adminCsrfToken()) ?>">
      </form>
    </div>
  </aside>
  <button type="button" class="admin-sidebar-overlay" aria-label="Đóng menu quản trị"></button>
  <div class="admin-main">
    <header class="admin-topbar">
      <button type="button" class="admin-menu-toggle" aria-label="Mở menu quản trị" aria-expanded="false" aria-controls="admin-sidebar">
        <i class="fa-solid fa-bars" aria-hidden="true"></i>
      </button>
      <span class="admin-topbar__title">Quản trị BookShop</span>
    </header>

  <script src="https://cdn.jsdelivr.net/npm/toastify-js@1.12.0/src/toastify.min.js"></script>
  <script src="../assets/js/toast.js?v=<?= time() ?>"></script>
  <script>
    (() => {
      const sidebar = document.getElementById('admin-sidebar');
      const toggle = document.querySelector('.admin-menu-toggle');
      const closeButtons = document.querySelectorAll('.admin-sidebar__close, .admin-sidebar-overlay');
      const logoutLink = document.getElementById('admin-logout-link');
      if (!sidebar || !toggle) return;

      const setOpen = (open) => {
        sidebar.classList.toggle('is-open', open);
        document.body.classList.toggle('admin-layout-open', open);
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      };

      toggle.addEventListener('click', () => setOpen(!sidebar.classList.contains('is-open')));
      closeButtons.forEach((button) => button.addEventListener('click', () => setOpen(false)));
      document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') setOpen(false);
      });
      logoutLink?.addEventListener('click', (event) => {
        event.preventDefault();
        document.getElementById('admin-logout-form')?.submit();
      });
    })();
  </script>
  <?php if (isset($_SESSION['log_toast'])): 
    $toastMsg = $_SESSION['log_toast'];
    $toastType = preg_match('/(thất bại|hủy|lỗi|xóa|vượt quá|giới hạn|hết hàng|cảnh báo)/i', $toastMsg) ? 'error' : 'success';
  ?>
    <script>showToast(<?= json_encode($toastMsg, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>, <?= json_encode($toastType) ?>);</script>
    <?php unset($_SESSION['log_toast']); ?>
  <?php endif; ?>
  <?php
}

function redirectTo($page) {
  header('Location: ' . $page);
  exit;
}
?>
