<?php
require_once 'data.php';
require_once 'partials.php';

// Đếm tổng sản phẩm
$res = $conn->query("SELECT COUNT(*) FROM product");
$productCount = $res ? (int)$res->fetch_row()[0] : 0;

// Đếm tổng danh mục
$res = $conn->query("SELECT COUNT(*) FROM category");
$categoryCount = $res ? (int)$res->fetch_row()[0] : 0;

// Đếm tổng đơn hàng
$res = $conn->query("SELECT COUNT(*) FROM `order`");
$orderCount = $res ? (int)$res->fetch_row()[0] : 0;

// Đếm tổng người dùng
$res = $conn->query("SELECT COUNT(*) FROM user");
$userCount = $res ? (int)$res->fetch_row()[0] : 0;

// Đếm tổng mã giảm giá
$res = $conn->query("SELECT COUNT(*) FROM voucher");
$couponCount = $res ? (int)$res->fetch_row()[0] : 0;

// Tính tổng doanh thu từ các đơn hàng thành công (Delivered)
$res = $conn->query("SELECT SUM(TotalAmount) FROM `order` WHERE OrderStatus = 'Delivered'");
$revenue = $res ? (float)$res->fetch_row()[0] : 0.0;

// Thống kê cố định 12 tháng của năm hiện tại, giống range cũ của dashboard.
$chartYear = (int) date('Y');
$monthlyStats = array_fill(1, 12, ['orders' => 0, 'revenue' => 0.0]);
$resMonthlyOrders = $conn->query("
  SELECT YEAR(OrderDate) AS OrderYear, MONTH(OrderDate) AS OrderMonth, COUNT(*) AS OrderCount
  FROM `order`
  WHERE YEAR(OrderDate) = {$chartYear}
  GROUP BY YEAR(OrderDate), MONTH(OrderDate)
  ORDER BY YEAR(OrderDate), MONTH(OrderDate)
");
if ($resMonthlyOrders) {
  while ($row = $resMonthlyOrders->fetch_assoc()) {
    $month = (int)$row['OrderMonth'];
    if ($month >= 1 && $month <= 12) {
      $monthlyStats[$month]['orders'] = (int)$row['OrderCount'];
    }
  }
}

// Doanh thu theo tháng: chỉ tính các đơn đã Delivered, đồng bộ với KPI doanh thu.
$resMonthlyRevenue = $conn->query("
  SELECT YEAR(OrderDate) AS OrderYear, MONTH(OrderDate) AS OrderMonth,
         SUM(TotalAmount) AS TotalRevenue
  FROM `order`
  WHERE OrderStatus = 'Delivered' AND YEAR(OrderDate) = {$chartYear}
  GROUP BY YEAR(OrderDate), MONTH(OrderDate)
  ORDER BY YEAR(OrderDate), MONTH(OrderDate)
");
if ($resMonthlyRevenue) {
  while ($row = $resMonthlyRevenue->fetch_assoc()) {
    $month = (int)$row['OrderMonth'];
    if ($month >= 1 && $month <= 12) {
      $monthlyStats[$month]['revenue'] = (float)($row['TotalRevenue'] ?? 0);
    }
  }
}
$monthlyLabels = array_map(function ($month) use ($chartYear) {
  return sprintf('%02d/%04d', $month, $chartYear);
}, range(1, 12));
$monthlyOrders = array_column($monthlyStats, 'orders');
$monthlyRevenue = array_column($monthlyStats, 'revenue');

// Thống kê trạng thái đơn hàng (ánh xạ sang tiếng Việt)
$statusMapping = [
  'Pending' => 'Chờ xác nhận',
  'Processing' => 'Đã xác nhận',
  'Shipped' => 'Đang giao',
  'Delivered' => 'Hoàn thành',
  'Cancelled' => 'Đã hủy'
];
$orderStatusStats = [];
$resStatus = $conn->query("SELECT OrderStatus, COUNT(*) AS Count FROM `order` GROUP BY OrderStatus");
if ($resStatus) {
  while ($row = $resStatus->fetch_assoc()) {
    $vnStatus = $statusMapping[$row['OrderStatus']] ?? $row['OrderStatus'];
    $orderStatusStats[$vnStatus] = (int)$row['Count'];
  }
}

// Thống kê số lượng sản phẩm theo danh mục
$productCategoryStats = [];
$resCatStats = $conn->query("
  SELECT c.CategoryName, COUNT(p.ProductID) AS Count 
  FROM category c 
  INNER JOIN product p ON c.CategoryID = p.CategoryID 
  GROUP BY c.CategoryID
");
if ($resCatStats) {
  while ($row = $resCatStats->fetch_assoc()) {
    $productCategoryStats[$row['CategoryName']] = (int)$row['Count'];
  }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Panel - Dashboard</title><?= adminCssLinks() ?>
</head>
<body>
  <div class="app-layout">
    <?php adminSidebar(); ?>

    <main class="page-content">
      <header class="page-header">
        <div>
          <h1>Dashboard & Thống kê</h1>
        </div>
      </header>

      <section class="card-grid">
        <article class="card stat-card"><div class="card__body"><div class="stat-card__icon"><i class="fa-solid fa-book" aria-hidden="true"></i></div><p class="card__subtitle">Tổng sản phẩm</p><h3 class="card__title"><?= $productCount ?></h3></div></article>
        <article class="card stat-card"><div class="card__body"><div class="stat-card__icon"><i class="fa-solid fa-cart-shopping" aria-hidden="true"></i></div><p class="card__subtitle">Đơn hàng</p><h3 class="card__title"><?= $orderCount ?></h3></div></article>
        <article class="card stat-card"><div class="card__body"><div class="stat-card__icon"><i class="fa-solid fa-users" aria-hidden="true"></i></div><p class="card__subtitle">Người dùng</p><h3 class="card__title"><?= $userCount ?></h3></div></article>
        <article class="card stat-card"><div class="card__body"><div class="stat-card__icon"><i class="fa-solid fa-ticket" aria-hidden="true"></i></div><p class="card__subtitle">Mã giảm giá</p><h3 class="card__title"><?= $couponCount ?></h3></div></article>
        <article class="card stat-card"><div class="card__body"><div class="stat-card__icon"><i class="fa-solid fa-chart-line" aria-hidden="true"></i></div><p class="card__subtitle">Doanh thu</p><h3 class="card__title"><?= number_format($revenue, 0, ',', '.') ?> đ</h3></div></article>
      </section>

      <section class="card-grid card-grid--charts">
        <article class="card chart-card chart-card--wide">
          <div class="card__body">
            <h2 class="card__title">Doanh thu theo tháng</h2>
            <canvas
              data-chart="bar"
              data-labels='<?= h(json_encode($monthlyLabels, JSON_UNESCAPED_UNICODE)) ?>'
              data-values='<?= h(json_encode($monthlyRevenue, JSON_UNESCAPED_UNICODE)) ?>'
              data-color="rgb(0,169,242)"
              data-unit="đ">
            </canvas>
          </div>
        </article>

        <article class="card chart-card">
          <div class="card__body">
            <h2 class="card__title">Đơn hàng theo tháng</h2>
            <canvas
              data-chart="line"
              data-labels='<?= h(json_encode($monthlyLabels, JSON_UNESCAPED_UNICODE)) ?>'
              data-values='<?= h(json_encode($monthlyOrders, JSON_UNESCAPED_UNICODE)) ?>'
              data-color="#f9b234"
              data-unit="đơn">
            </canvas>
          </div>
        </article>

        <article class="card chart-card">
          <div class="card__body">
            <h2 class="card__title">Trạng thái đơn hàng</h2>
            <canvas
              data-chart="doughnut"
              data-labels='<?= h(json_encode(array_keys($orderStatusStats), JSON_UNESCAPED_UNICODE)) ?>'
              data-values='<?= h(json_encode(array_values($orderStatusStats), JSON_UNESCAPED_UNICODE)) ?>'
              data-unit="đơn">
            </canvas>
          </div>
        </article>

        <article class="card chart-card">
          <div class="card__body">
            <h2 class="card__title">Sản phẩm theo danh mục</h2>
            <canvas
              data-chart="doughnut"
              data-labels='<?= h(json_encode(array_keys($productCategoryStats), JSON_UNESCAPED_UNICODE)) ?>'
              data-values='<?= h(json_encode(array_values($productCategoryStats), JSON_UNESCAPED_UNICODE)) ?>'
              data-unit="Sản Phẩm">
            </canvas>
          </div>
        </article>
      </section>
    </main>
  </div>
  </div>

  <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.5.1/chart.umd.min.js"></script>
  <script src="../assets/js/admin.js"></script>
</body>
</html>
