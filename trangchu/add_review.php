<?php
/**
 * Add Review Controller
 * Tiếp nhận và xử lý bình luận/đánh giá sách từ phía người dùng
 */

require_once '../config/db.php';

// Kiểm tra quyền và phương thức yêu cầu
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('trangchu/index.php'));
    exit;
}

// 1. Kiểm tra đăng nhập
if (!isset($_SESSION['user'])) {
    $_SESSION['review_error'] = 'Bạn cần đăng nhập để gửi đánh giá.';
    $productId = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    if ($productId > 0) {
        header('Location: ' . url('trangchu/detail.php?id=' . $productId));
    } else {
        header('Location: ' . url('trangchu/index.php'));
    }
    exit;
}

// 2. Lấy dữ liệu biểu mẫu
$customerId = intval($_SESSION['user']['id']);
$productId = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

// 3. Kiểm tra tính hợp lệ của ProductID
if ($productId <= 0) {
    $_SESSION['review_error'] = 'Sản phẩm không hợp lệ.';
    header('Location: ' . url('trangchu/index.php'));
    exit;
}

// 4. Kiểm tra tính hợp lệ của Rating & Comment
if ($rating < 1 || $rating > 5) {
    $_SESSION['review_error'] = 'Vui lòng chọn số sao đánh giá (từ 1 đến 5 sao).';
    header('Location: ' . url('trangchu/detail.php?id=' . $productId));
    exit;
}

if (empty($comment)) {
    $_SESSION['review_error'] = 'Vui lòng nhập nội dung bình luận.';
    header('Location: ' . url('trangchu/detail.php?id=' . $productId));
    exit;
}

// 5. Thực hiện thêm đánh giá mới vào cơ sở dữ liệu
$sql = "INSERT INTO review (CustomerID, ProductID, Rating, Comment, ReviewDate) VALUES (?, ?, ?, ?, NOW())";
$stmt = $conn->prepare($sql);
if ($stmt) {
    $stmt->bind_param("iiis", $customerId, $productId, $rating, $comment);
    if ($stmt->execute()) {
        $_SESSION['review_success'] = 'Cảm ơn bạn đã gửi đánh giá và bình luận!';
        write_user_log($conn, "Đánh giá sản phẩm ID " . $productId . " (" . $rating . " sao)", $customerId);
    } else {
        $_SESSION['review_error'] = 'Đã có lỗi xảy ra trong quá trình lưu đánh giá. Vui lòng thử lại sau.';
    }
    $stmt->close();
} else {
    $_SESSION['review_error'] = 'Lỗi kết nối cơ sở dữ liệu. Vui lòng thử lại sau.';
}

// 6. Quay trở lại trang chi tiết sản phẩm
header('Location: ' . url('trangchu/detail.php?id=' . $productId));
exit;
