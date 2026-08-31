<?php
require_once 'data.php';
require_once 'partials.php';

$editProduct = null;
$editProductImages = [];
if (isset($_GET['edit'])) {
  $stmt = $conn->prepare("SELECT * FROM product WHERE ProductID = ?");
  $stmt->bind_param("i", $_GET['edit']);
  $stmt->execute();
  $editProduct = $stmt->get_result()->fetch_assoc();
  $stmt->close();
  
  if ($editProduct) {
    $imgStmt = $conn->prepare("SELECT * FROM image WHERE ProductID = ? ORDER BY IsThumbnail DESC, SortOrder ASC");
    $imgStmt->bind_param("i", $editProduct['ProductID']);
    $imgStmt->execute();
    $imgRes = $imgStmt->get_result();
    while ($row = $imgRes->fetch_assoc()) {
      $editProductImages[] = $row;
    }
    $imgStmt->close();
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!verifyAdminCsrf($_POST['csrf_token'] ?? '')) {
    $_SESSION['log_toast'] = 'Yêu cầu không hợp lệ. Vui lòng thử lại.';
    redirectTo('products.php');
  }
  $action = $_POST['action'] ?? '';

  if ($action === 'save') {
    $id = $_POST['id'] !== '' ? (int) $_POST['id'] : null;
    $name = trim($_POST['name']);
    $categoryId = (int) $_POST['category_id'];
    $price = (int) $_POST['price'];
    $stock = (int) $_POST['stock'];
    $publisher = trim($_POST['publisher'] ?? 'AlphaBooks');
    $description = trim($_POST['description'] ?? '');
    
    // Tự động cập nhật trạng thái nếu hết hàng
    $status = ($stock <= 0) ? 'Hết hàng' : trim($_POST['status'] ?? 'Còn hàng');

    // Xử lý upload nhiều ảnh (Cloudinary)
    $uploadedImageUrls = [];
    $uploadFailed = false;
    if (isset($_FILES['product_images'])) {
      $files = $_FILES['product_images'];
      $fileCount = is_array($files['name']) ? count($files['name']) : 0;
      
      for ($i = 0; $i < $fileCount; $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_OK) {
          require_once __DIR__ . '/../config/cloudinary.php';
          $uploadedUrl = CloudinaryHelper::uploadImage($files['tmp_name'][$i]);
          if ($uploadedUrl) {
            $uploadedImageUrls[] = $uploadedUrl;
          } else {
            $uploadFailed = true;
          }
        }
      }
    }

    if ($id) {
      $stmt = $conn->prepare("UPDATE product SET CategoryID = ?, ProductName = ?, Price = ?, Quantity = ?, Status = ?, Publisher = ?, Description = ? WHERE ProductID = ?");
      $stmt->bind_param("isiisssi", $categoryId, $name, $price, $stock, $status, $publisher, $description, $id);
      $stmt->execute();
      $stmt->close();

      // 1. Xử lý xóa ảnh được đánh dấu xóa
      if (isset($_POST['delete_image_ids'])) {
        $deleteIds = $_POST['delete_image_ids'];
        foreach ($deleteIds as $delId) {
          $delId = (int)$delId;
          $delStmt = $conn->prepare("DELETE FROM image WHERE ImageID = ? AND ProductID = ?");
          $delStmt->bind_param("ii", $delId, $id);
          $delStmt->execute();
          $delStmt->close();
        }
      }

      // 2. Xử lý cập nhật ảnh đại diện (Thumbnail)
      if (isset($_POST['thumbnail_image_id'])) {
        $thumbId = (int)$_POST['thumbnail_image_id'];
        
        // Đặt tất cả ảnh của sản phẩm này về IsThumbnail = 0
        $resetStmt = $conn->prepare("UPDATE image SET IsThumbnail = 0 WHERE ProductID = ?");
        $resetStmt->bind_param("i", $id);
        $resetStmt->execute();
        $resetStmt->close();
        
        // Đặt ảnh được chọn làm IsThumbnail = 1
        $setStmt = $conn->prepare("UPDATE image SET IsThumbnail = 1 WHERE ImageID = ? AND ProductID = ?");
        $setStmt->bind_param("ii", $thumbId, $id);
        $setStmt->execute();
        $setStmt->close();
      }

      // 3. Nếu có ảnh mới upload thành công
      if (!empty($uploadedImageUrls)) {
        // Kiểm tra xem sản phẩm đã có ảnh đại diện chưa
        $checkStmt = $conn->prepare("SELECT ImageID FROM image WHERE ProductID = ? AND IsThumbnail = 1 LIMIT 1");
        $checkStmt->bind_param("i", $id);
        $checkStmt->execute();
        $hasThumbnail = $checkStmt->get_result()->num_rows > 0;
        $checkStmt->close();

        foreach ($uploadedImageUrls as $index => $url) {
          $isThumb = (!$hasThumbnail && $index === 0) ? 1 : 0;
          $altText = 'Ảnh sản phẩm ' . $name;
          $insertImgStmt = $conn->prepare("INSERT INTO image (ProductID, ImageURL, AltText, IsThumbnail, SortOrder) VALUES (?, ?, ?, ?, ?)");
          $sortOrder = $index + 2;
          $insertImgStmt->bind_param("issii", $id, $url, $altText, $isThumb, $sortOrder);
          $insertImgStmt->execute();
          $insertImgStmt->close();

          if ($isThumb) {
            $hasThumbnail = true;
          }
        }
      }

      // Đảm bảo luôn có ít nhất 1 ảnh đại diện nếu sản phẩm còn ảnh
      $checkThumbStmt = $conn->prepare("SELECT ImageID FROM image WHERE ProductID = ? AND IsThumbnail = 1 LIMIT 1");
      $checkThumbStmt->bind_param("i", $id);
      $checkThumbStmt->execute();
      $hasThumb = $checkThumbStmt->get_result()->num_rows > 0;
      $checkThumbStmt->close();

      if (!$hasThumb) {
        $fallbackStmt = $conn->prepare("SELECT ImageID FROM image WHERE ProductID = ? LIMIT 1");
        $fallbackStmt->bind_param("i", $id);
        $fallbackStmt->execute();
        $fallbackRes = $fallbackStmt->get_result();
        if ($fallbackRes->num_rows > 0) {
          $fallbackId = $fallbackRes->fetch_assoc()['ImageID'];
          $setFallbackStmt = $conn->prepare("UPDATE image SET IsThumbnail = 1 WHERE ImageID = ?");
          $setFallbackStmt->bind_param("i", $fallbackId);
          $setFallbackStmt->execute();
          $setFallbackStmt->close();
        }
        $fallbackStmt->close();
      }
      
      if ($uploadFailed) {
        $_SESSION['log_toast'] = "Lỗi: Cập nhật sản phẩm thành công nhưng không thể upload ảnh lên Cloudinary (hãy kiểm tra credentials trong .env).";
      } else {
        write_user_log($conn, "Cập nhật sản phẩm ID " . $id . " - Tên: " . $name);
      }
    } else {
      $stmt = $conn->prepare("INSERT INTO product (CategoryID, ProductName, Price, Quantity, Status, Publisher, Description) VALUES (?, ?, ?, ?, ?, ?, ?)");
      $stmt->bind_param("isiisss", $categoryId, $name, $price, $stock, $status, $publisher, $description);
      $stmt->execute();
      $newId = $stmt->insert_id;
      $stmt->close();
      
      // Nếu có ảnh mới upload thành công
      if (!empty($uploadedImageUrls)) {
        foreach ($uploadedImageUrls as $index => $url) {
          $isThumb = ($index === 0) ? 1 : 0; // Ảnh đầu tiên làm ảnh đại diện
          $altText = 'Ảnh sản phẩm ' . $name;
          $insertImgStmt = $conn->prepare("INSERT INTO image (ProductID, ImageURL, AltText, IsThumbnail, SortOrder) VALUES (?, ?, ?, ?, ?)");
          $sortOrder = $index + 1;
          $insertImgStmt->bind_param("issii", $newId, $url, $altText, $isThumb, $sortOrder);
          $insertImgStmt->execute();
          $insertImgStmt->close();
        }
      }
      
      if ($uploadFailed) {
        $_SESSION['log_toast'] = "Lỗi: Thêm sản phẩm thành công nhưng không thể upload ảnh lên Cloudinary (hãy kiểm tra credentials trong .env).";
      } else {
        write_user_log($conn, "Thêm mới sản phẩm ID " . $newId . " - Tên: " . $name);
      }
    }
  }

  if ($action === 'delete') {
    $id = (int)$_POST['id'];
    $stmt = $conn->prepare("DELETE FROM product WHERE ProductID = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    write_user_log($conn, "Xóa sản phẩm ID " . $id);
  }

  redirectTo('products.php');
}

// Lấy danh sách danh mục để điền vào select dropdown
$categoriesSelect = [];
$resCats = $conn->query("SELECT CategoryID, CategoryName FROM category ORDER BY CategoryName ASC");
if ($resCats) {
  while ($row = $resCats->fetch_assoc()) {
    $categoriesSelect[] = $row;
  }
}

// Lấy danh sách sản phẩm thực tế từ DB kèm tên danh mục
$products = [];
$resProds = $conn->query("
  SELECT p.ProductID, p.ProductName, p.Price, p.Quantity, p.Status, p.Publisher, p.Description, c.CategoryName 
  FROM product p 
  LEFT JOIN category c ON p.CategoryID = c.CategoryID 
  ORDER BY p.ProductID DESC
");
if ($resProds) {
  while ($row = $resProds->fetch_assoc()) {
    $products[] = $row;
  }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Quản lý sản phẩm</title><?= adminCssLinks() ?>
</head>
<body>
  <div class="app-layout">
    <?php adminSidebar(); ?>

    <main class="page-content">
      <header class="page-header"><div><h1>Quản lý sản phẩm</h1><p>Thêm, sửa, xóa sản phẩm sách</p></div></header>

      <form method="post" class="card admin-form-card" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= h(adminCsrfToken()) ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= h($editProduct['ProductID'] ?? '') ?>">
        <div class="card__body form">
          <h2 class="admin-form-card__title"><i class="fa-solid fa-book" aria-hidden="true"></i><?= $editProduct ? 'Chỉnh sửa sản phẩm' : 'Thêm sản phẩm' ?></h2>
          <h3 class="admin-form-card__section-title">Thông tin sản phẩm</h3>
          <div class="form-row">
            <div class="form-group form-group--wide">
              <label class="form-label">Tên sách</label>
              <input class="form-control" type="text" name="name" value="<?= h($editProduct['ProductName'] ?? '') ?>" required>
            </div>
            <div class="form-group">
              <label class="form-label">Danh mục</label>
              <select class="form-control" name="category_id" required>
                <option value="">-- Chọn danh mục --</option>
                <?php foreach ($categoriesSelect as $cat): ?>
                  <option value="<?= h($cat['CategoryID']) ?>" <?= ($editProduct['CategoryID'] ?? '') == $cat['CategoryID'] ? 'selected' : '' ?>><?= h($cat['CategoryName']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Giá sách</label>
              <div class="input-unit">
                <input class="form-control" type="number" name="price" value="<?= h($editProduct['Price'] ?? '') ?>" required>
                <span class="input-unit__text">đ</span>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Số lượng tồn kho</label>
              <div class="input-unit">
                <input class="form-control" type="number" name="stock" value="<?= h($editProduct['Quantity'] ?? '') ?>" required>
                <span class="input-unit__text">Cuốn</span>
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Nhà xuất bản</label>
              <input class="form-control" type="text" name="publisher" value="<?= h($editProduct['Publisher'] ?? 'AlphaBooks') ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Trạng thái</label>
              <select class="form-control" name="status">
                <?php foreach (['Còn hàng', 'Hết hàng'] as $status): ?>
                  <option value="<?= h($status) ?>" <?= ($editProduct['Status'] ?? 'Còn hàng') === $status ? 'selected' : '' ?>><?= h($status) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group form-group--full">
              <label class="form-label">Mô tả sản phẩm</label>
              <textarea class="form-control" name="description" rows="3"><?= h($editProduct['Description'] ?? '') ?></textarea>
            </div>
          </div>
          <?php if (!empty($editProductImages)): ?>
            <section class="product-image-section" aria-labelledby="existing-images-title">
              <h3 id="existing-images-title" class="admin-form-card__section-title"><i class="fa-solid fa-images" aria-hidden="true"></i>Hình ảnh sản phẩm</h3>
              <div class="product-image-grid">
                  <?php foreach ($editProductImages as $img): ?>
                    <div class="product-image-card <?= $img['IsThumbnail'] ? 'is-thumbnail' : '' ?>">
                      <img src="<?= getProductImage($img['ImageURL']) ?>" class="product-image-preview" alt="Ảnh sản phẩm">
                      <label class="product-image-option">
                        <input type="radio" name="thumbnail_image_id" value="<?= $img['ImageID'] ?>" <?= $img['IsThumbnail'] ? 'checked' : '' ?>>
                        <span>Đại diện</span>
                      </label>
                      <label class="product-image-option product-image-option--delete">
                        <input type="checkbox" name="delete_image_ids[]" value="<?= $img['ImageID'] ?>">
                        <span>Xóa ảnh</span>
                      </label>
                      <?php if ($img['IsThumbnail']): ?>
                        <span class="product-image-badge">Bìa</span>
                      <?php endif; ?>
                    </div>
                  <?php endforeach; ?>
              </div>
            </section>
          <?php endif; ?>

          <div class="form-row product-upload-row">
            <div class="form-group form-group--full product-upload-area">
              <h3 class="admin-form-card__section-title"><i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i>Thêm hình ảnh</h3>
              <label class="form-label" for="product-images">Tải lên hình ảnh sản phẩm mới (Có thể chọn nhiều ảnh)</label>
              <input id="product-images" class="form-control" type="file" name="product_images[]" accept="image/*" multiple>
              <small class="form-text">Hỗ trợ tải lên nhiều file ảnh cùng lúc. Nếu không chọn, các ảnh cũ sẽ được giữ nguyên.</small>
            </div>
          </div>
          <div class="btn-group">
            <button class="btn btn--primary" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i><?= $editProduct ? 'Cập nhật sản phẩm' : 'Thêm sản phẩm' ?></button>
            <?php if ($editProduct): ?><a class="btn btn--ghost" href="products.php"><i class="fa-solid fa-xmark" aria-hidden="true"></i>Hủy sửa</a><?php endif; ?>
          </div>
        </div>
      </form>

      <section class="admin-table-card" aria-labelledby="products-table-title">
        <h2 id="products-table-title" class="admin-table-card__title"><i class="fa-solid fa-book" aria-hidden="true"></i>Danh sách sản phẩm</h2>
        <div class="table-wrapper">
        <table class="table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Tên sách</th>
              <th>Danh mục</th>
              <th>Giá bán</th>
              <th>Tồn kho</th>
              <th>Trạng thái</th>
              <th>Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($products)): ?>
              <tr><td colspan="7"><div class="admin-table-empty"><i class="fa-solid fa-book" aria-hidden="true"></i><strong>Chưa có sản phẩm</strong><span>Danh sách sản phẩm hiện đang trống.</span></div></td></tr>
            <?php else: ?>
            <?php foreach ($products as $product): ?>
              <tr>
                <td><?= h($product['ProductID']) ?></td>
                <td><strong><?= h($product['ProductName']) ?></strong><small class="product-table-meta">NXB: <?= h($product['Publisher']) ?></small></td>
                <td><?= h($product['CategoryName'] ?? 'Không có') ?></td>
                <td><?= number_format($product['Price'], 0, ',', '.') ?> đ</td>
                <td><?= h($product['Quantity']) ?></td>
                <td><span class="badge <?= badgeClass($product['Status']) ?>"><?= h($product['Status']) ?></span></td>
                <td class="table__actions">
                  <a class="btn btn--sm btn--outline" href="products.php?edit=<?= h($product['ProductID']) ?>"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>Sửa</a>
                  <form method="post" class="table-action-form" onsubmit="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này?')">
                    <input type="hidden" name="csrf_token" value="<?= h(adminCsrfToken()) ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= h($product['ProductID']) ?>">
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
