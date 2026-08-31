<?php
require_once 'data.php';
require_once 'partials.php';

$editCoupon = null;
if (isset($_GET['edit'])) {
  $stmt = $conn->prepare("SELECT * FROM voucher WHERE VoucherID = ?");
  $stmt->bind_param("i", $_GET['edit']);
  $stmt->execute();
  $editCoupon = $stmt->get_result()->fetch_assoc();
  $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!verifyAdminCsrf($_POST['csrf_token'] ?? '')) {
    $_SESSION['log_toast'] = 'Yêu cầu không hợp lệ. Vui lòng thử lại.';
    redirectTo('coupons.php');
  }
  $action = $_POST['action'] ?? '';

  if ($action === 'save') {
    $id = $_POST['id'] !== '' ? (int) $_POST['id'] : null;
    $code = strtoupper(trim($_POST['code']));
    $discount = (float) $_POST['discount'];
    $expiredDate = $_POST['expired_date'] !== '' ? $_POST['expired_date'] : null;

    if ($id) {
      $stmt = $conn->prepare("UPDATE voucher SET VoucherCode = ?, DiscountValue = ?, ExpiredDate = ? WHERE VoucherID = ?");
      $stmt->bind_param("sdsi", $code, $discount, $expiredDate, $id);
      $stmt->execute();
      $stmt->close();
      write_user_log($conn, "Cập nhật mã giảm giá ID " . $id . " - Mã: " . $code);
    } else {
      $stmt = $conn->prepare("INSERT INTO voucher (VoucherCode, DiscountValue, ExpiredDate) VALUES (?, ?, ?)");
      $stmt->bind_param("sds", $code, $discount, $expiredDate);
      $stmt->execute();
      $newId = $stmt->insert_id;
      $stmt->close();
      write_user_log($conn, "Thêm mới mã giảm giá ID " . $newId . " - Mã: " . $code);
    }
  }

  if ($action === 'delete') {
    $id = (int)$_POST['id'];
    $stmt = $conn->prepare("DELETE FROM voucher WHERE VoucherID = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    write_user_log($conn, "Xóa mã giảm giá ID " . $id);
  }

  redirectTo('coupons.php');
}

// Lấy danh sách mã giảm giá từ CSDL
$coupons = [];
$res = $conn->query("SELECT * FROM voucher ORDER BY VoucherID DESC");
if ($res) {
  while ($row = $res->fetch_assoc()) {
    $coupons[] = $row;
  }
}

// Hàm tính trạng thái của Voucher một cách động
function getCouponStatus($expiredDate) {
  if (empty($expiredDate)) return 'Đang áp dụng';
  $expTime = strtotime($expiredDate);
  $nowTime = time();
  if (date('Y-m-d', $expTime) >= date('Y-m-d', $nowTime)) {
    if ($expTime - $nowTime < 3 * 86400) {
      return 'Sắp hết hạn';
    }
    return 'Đang áp dụng';
  }
  return 'Đã hết hạn';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Quản lý mã giảm giá</title><?= adminCssLinks() ?>
</head>
<body>
  <div class="app-layout">
    <?php adminSidebar(); ?>
    <main class="page-content">
      <header class="page-header"><div><h1>Quản lý mã giảm giá</h1><p>Thêm, sửa, xóa mã giảm giá</p></div></header>
      
      <form method="post" class="card admin-form-card">
        <input type="hidden" name="csrf_token" value="<?= h(adminCsrfToken()) ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= h($editCoupon['VoucherID'] ?? '') ?>">
        <div class="card__body form">
          <h2 class="admin-form-card__title"><i class="fa-solid fa-ticket" aria-hidden="true"></i><?= $editCoupon ? 'Chỉnh sửa mã giảm giá' : 'Thêm mã giảm giá' ?></h2>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Mã giảm giá</label>
              <input class="form-control" name="code" value="<?= h($editCoupon['VoucherCode'] ?? '') ?>" placeholder="Ví dụ: GIAM20K" required>
            </div>
            <div class="form-group">
              <label class="form-label">Mức giảm (đ)</label>
              <input class="form-control" type="number" min="0" name="discount" value="<?= h($editCoupon['DiscountValue'] ?? '') ?>" required>
            </div>
            <div class="form-group">
              <label class="form-label">Ngày hết hạn</label>
              <input class="form-control" type="date" name="expired_date" value="<?= h($editCoupon['ExpiredDate'] ? date('Y-m-d', strtotime($editCoupon['ExpiredDate'])) : '') ?>" required>
            </div>
          </div>
          <div class="btn-group">
            <button class="btn btn--primary" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i><?= $editCoupon ? 'Cập nhật mã giảm giá' : 'Thêm mã giảm giá' ?></button>
            <?php if ($editCoupon): ?><a class="btn btn--ghost" href="coupons.php"><i class="fa-solid fa-xmark" aria-hidden="true"></i>Hủy sửa</a><?php endif; ?>
          </div>
        </div>
      </form>
      
      <section class="admin-table-card" aria-labelledby="coupons-table-title">
        <h2 id="coupons-table-title" class="admin-table-card__title"><i class="fa-solid fa-ticket" aria-hidden="true"></i>Danh sách mã giảm giá</h2>
        <div class="table-wrapper">
        <table class="table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Mã</th>
              <th>Mức giảm</th>
              <th>Ngày hết hạn</th>
              <th>Trạng thái</th>
              <th>Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($coupons)): ?>
              <tr><td colspan="6"><div class="admin-table-empty"><i class="fa-solid fa-ticket" aria-hidden="true"></i><strong>Chưa có mã giảm giá</strong><span>Danh sách mã giảm giá hiện đang trống.</span></div></td></tr>
            <?php else: ?>
            <?php foreach ($coupons as $coupon): 
              $status = getCouponStatus($coupon['ExpiredDate']);
            ?>
              <tr>
                <td><?= h($coupon['VoucherID']) ?></td>
                <td><strong><?= h($coupon['VoucherCode']) ?></strong></td>
                <td><?= number_format($coupon['DiscountValue'], 0, ',', '.') ?> đ</td>
                <td><?= $coupon['ExpiredDate'] ? date('d/m/Y', strtotime($coupon['ExpiredDate'])) : 'Không giới hạn' ?></td>
                <td><span class="badge <?= badgeClass($status) ?>"><?= h($status) ?></span></td>
                <td class="table__actions">
                  <a class="btn btn--sm btn--outline" href="coupons.php?edit=<?= h($coupon['VoucherID']) ?>"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>Sửa</a>
                  <form method="post" onsubmit="return confirm('Bạn có chắc chắn muốn xóa mã giảm giá này?')" style="display:inline">
                    <input type="hidden" name="csrf_token" value="<?= h(adminCsrfToken()) ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= h($coupon['VoucherID']) ?>">
                    <button class="btn btn--sm btn--danger" type="submit"><i class="fa-solid fa-trash" aria-hidden="true"></i>Xóa</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
        </div>
      </section>
    </main>
  </div>
  </div>
</body>
</html>
