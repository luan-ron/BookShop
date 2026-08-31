<?php
require_once 'data.php';
require_once 'partials.php';

$editUser = null;
if (isset($_GET['edit'])) {
  $stmt = $conn->prepare("SELECT * FROM user WHERE CustomerID = ?");
  $stmt->bind_param("i", $_GET['edit']);
  $stmt->execute();
  $editUser = $stmt->get_result()->fetch_assoc();
  $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!verifyAdminCsrf($_POST['csrf_token'] ?? '')) {
    $_SESSION['log_toast'] = 'Yêu cầu không hợp lệ. Vui lòng thử lại.';
    redirectTo('users.php');
  }
  $action = $_POST['action'] ?? '';

  if ($action === 'save') {
    $id = $_POST['id'] !== '' ? (int) $_POST['id'] : null;
    $email = trim($_POST['email']);
    $roleId = (int) $_POST['role_id'];
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    // Tách Họ tên thành LastName và FirstName
    $fullName = trim($_POST['name']);
    $parts = explode(' ', $fullName);
    $firstName = array_pop($parts);
    $lastName = implode(' ', $parts);

    if ($id) {
      $stmt = $conn->prepare("UPDATE user SET RoleID = ?, LastName = ?, FirstName = ?, Email = ?, Phone = ?, Address = ? WHERE CustomerID = ?");
      $stmt->bind_param("isssssi", $roleId, $lastName, $firstName, $email, $phone, $address, $id);
      $stmt->execute();
      $stmt->close();
      write_user_log($conn, "Cập nhật người dùng ID " . $id . " - Email: " . $email);
    } else {
      // Tạo mật khẩu tạm thời ngẫu nhiên cho người dùng mới
      $temporaryPassword = bin2hex(random_bytes(16));
      $defaultPassword = password_hash($temporaryPassword, PASSWORD_DEFAULT);
      $stmt = $conn->prepare("INSERT INTO user (RoleID, LastName, FirstName, Email, Password, Phone, Address) VALUES (?, ?, ?, ?, ?, ?, ?)");
      $stmt->bind_param("issssss", $roleId, $lastName, $firstName, $email, $defaultPassword, $phone, $address);
      $stmt->execute();
      $newId = $stmt->insert_id;
      $stmt->close();
      write_user_log($conn, "Thêm mới người dùng ID " . $newId . " - Email: " . $email);
      $_SESSION['log_toast'] = "Đã thêm người dùng thành công.";
    }
  }

  if ($action === 'delete') {
    $id = (int)$_POST['id'];
    $stmt = $conn->prepare("DELETE FROM user WHERE CustomerID = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    write_user_log($conn, "Xóa người dùng ID " . $id);
  }

  redirectTo('users.php');
}

// Lấy danh sách Vai trò
$roles = [];
$resRoles = $conn->query("SELECT RoleID, RoleName FROM role ORDER BY RoleID ASC");
if ($resRoles) {
  while ($row = $resRoles->fetch_assoc()) {
    $roles[] = $row;
  }
}

// Lấy danh sách Người dùng kèm tên Vai trò từ DB
$users = [];
$resUsers = $conn->query("
  SELECT u.CustomerID, u.LastName, u.FirstName, u.Email, u.Phone, u.Address, r.RoleName 
  FROM user u 
  LEFT JOIN role r ON u.RoleID = r.RoleID 
  ORDER BY u.CustomerID DESC
");
if ($resUsers) {
  while ($row = $resUsers->fetch_assoc()) {
    $users[] = $row;
  }
}

$editFullName = $editUser ? trim(($editUser['LastName'] ?? '') . ' ' . ($editUser['FirstName'] ?? '')) : '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Quản lý người dùng</title><?= adminCssLinks() ?>
</head>
<body>
  <div class="app-layout">
    <?php adminSidebar(); ?>
    <main class="page-content">
      <header class="page-header"><div><h1>Quản lý người dùng</h1><p>Thêm, sửa, xóa người dùng</p></div></header>
      
      <form method="post" class="card admin-form-card">
        <input type="hidden" name="csrf_token" value="<?= h(adminCsrfToken()) ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= h($editUser['CustomerID'] ?? '') ?>">
        <div class="card__body form">
          <h2 class="admin-form-card__title"><i class="fa-solid fa-user-pen" aria-hidden="true"></i><?= $editUser ? 'Chỉnh sửa người dùng' : 'Thêm người dùng' ?></h2>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Họ tên</label>
              <input class="form-control" name="name" value="<?= h($editFullName) ?>" required>
            </div>
            <div class="form-group">
              <label class="form-label">Email</label>
              <input class="form-control" type="email" name="email" value="<?= h($editUser['Email'] ?? '') ?>" required>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Số điện thoại</label>
              <input class="form-control" type="text" name="phone" value="<?= h($editUser['Phone'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Vai trò</label>
              <select class="form-control" name="role_id" required>
                <option value="">-- Chọn vai trò --</option>
                <?php foreach ($roles as $role): ?>
                  <option value="<?= h($role['RoleID']) ?>" <?= ($editUser['RoleID'] ?? '') == $role['RoleID'] ? 'selected' : '' ?>><?= h($role['RoleName']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group form-group--full">
              <label class="form-label">Địa chỉ</label>
              <input class="form-control" type="text" name="address" value="<?= h($editUser['Address'] ?? '') ?>">
            </div>
          </div>
          <div class="btn-group">
            <button class="btn btn--primary" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i><?= $editUser ? 'Cập nhật người dùng' : 'Thêm người dùng' ?></button>
            <?php if ($editUser): ?><a class="btn btn--ghost" href="users.php"><i class="fa-solid fa-xmark" aria-hidden="true"></i>Hủy sửa</a><?php endif; ?>
          </div>
        </div>
      </form>
      
      <section class="admin-table-card" aria-labelledby="users-table-title">
        <h2 id="users-table-title" class="admin-table-card__title"><i class="fa-solid fa-users" aria-hidden="true"></i>Danh sách người dùng</h2>
        <div class="table-wrapper">
        <table class="table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Họ tên</th>
              <th>Email</th>
              <th>Số điện thoại</th>
              <th>Vai trò</th>
              <th>Địa chỉ</th>
              <th>Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($users)): ?>
              <tr><td colspan="7"><div class="admin-table-empty"><i class="fa-solid fa-users-slash" aria-hidden="true"></i><strong>Chưa có người dùng</strong><span>Danh sách người dùng hiện đang trống.</span></div></td></tr>
            <?php else: ?>
            <?php foreach ($users as $user): ?>
              <tr>
                <td><?= h($user['CustomerID']) ?></td>
                <td><strong><?= h(($user['LastName'] ?? '') . ' ' . ($user['FirstName'] ?? '')) ?></strong></td>
                <td><?= h($user['Email']) ?></td>
                <td><?= h($user['Phone'] ?? 'Chưa cập nhật') ?></td>
                <td><span class="badge badge--info"><?= h($user['RoleName'] ?? 'Chưa xác định') ?></span></td>
                <td><?= h($user['Address'] ?? 'Chưa cập nhật') ?></td>
                <td class="table__actions">
                  <a class="btn btn--sm btn--outline" href="users.php?edit=<?= h($user['CustomerID']) ?>"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>Sửa</a>
                  <form method="post" onsubmit="return confirm('Bạn có chắc chắn muốn xóa người dùng này?')" style="display:inline">
                    <input type="hidden" name="csrf_token" value="<?= h(adminCsrfToken()) ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= h($user['CustomerID']) ?>">
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
