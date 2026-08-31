<?php
require_once '../config/db.php';

function sendResponse($redirectUrl) {
    global $_SESSION;
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        
        $status = 'success';
        $message = '';
        if (isset($_SESSION['error'])) {
            $status = 'error';
            $message = $_SESSION['error'];
            unset($_SESSION['error']);
        } elseif (isset($_SESSION['warning'])) {
            $status = 'warning';
            $message = $_SESSION['warning'];
            unset($_SESSION['warning']);
        } elseif (isset($_SESSION['success'])) {
            $status = 'success';
            $message = $_SESSION['success'];
            unset($_SESSION['success']);
        } elseif (isset($_SESSION['log_toast'])) {
            $message = $_SESSION['log_toast'];
            unset($_SESSION['log_toast']);
        }
        
        echo json_encode([
            'status' => $status,
            'message' => $message,
            'cart_count' => array_sum($_SESSION['cart'] ?? [])
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    header('Location: ' . $redirectUrl);
    exit;
}

// Kiểm tra phương thức gửi lên
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(url('trangchu/index.php'));
}

$productId = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

if ($productId <= 0) {
    sendResponse(url('trangchu/index.php'));
}

if ($quantity <= 0) {
    $quantity = 1;
}

// Kiểm tra sản phẩm trong CSDL
$stmt = $conn->prepare("SELECT ProductID, ProductName, Price, Quantity, Status FROM product WHERE ProductID = ?");
$stmt->bind_param("i", $productId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Sản phẩm không tồn tại
    sendResponse(url('trangchu/index.php'));
}

$product = $result->fetch_assoc();
$stmt->close();

// Kiểm tra tồn kho và trạng thái
if ($product['Quantity'] <= 0 || $product['Status'] === 'Hết hàng') {
    // Hết hàng
    $_SESSION['error'] = 'Sản phẩm "' . $product['ProductName'] . '" đã hết hàng!';
    sendResponse(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : url('trangchu/index.php'));
}

// Khởi tạo giỏ hàng nếu chưa có
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Kiểm tra nếu khách hàng đã đăng nhập thì đồng bộ vào Database
$customerId = isset($_SESSION['user']) ? intval($_SESSION['user']['id']) : null;
if ($customerId !== null && $customerId > 0) {
    // 1. Tìm hoặc tạo giỏ hàng Active trong DB
    $cartId = 0;
    $stmtCart = $conn->prepare("SELECT CartID FROM cart WHERE CustomerID = ? AND Status = 'Active' LIMIT 1");
    $stmtCart->bind_param("i", $customerId);
    $stmtCart->execute();
    $resCart = $stmtCart->get_result();
    if ($resCart->num_rows > 0) {
        $rowCart = $resCart->fetch_assoc();
        $cartId = intval($rowCart['CartID']);
    } else {
        $stmtInsertCart = $conn->prepare("INSERT INTO cart (CustomerID, Status) VALUES (?, 'Active')");
        $stmtInsertCart->bind_param("i", $customerId);
        $stmtInsertCart->execute();
        $cartId = $stmtInsertCart->insert_id;
        $stmtInsertCart->close();
    }
    $stmtCart->close();

    if ($cartId > 0) {
        // 2. Kiểm tra xem sản phẩm đã có trong giỏ hàng DB chưa
        $stmtCheck = $conn->prepare("SELECT Quantity FROM cart_detail WHERE CartID = ? AND ProductID = ?");
        $stmtCheck->bind_param("ii", $cartId, $productId);
        $stmtCheck->execute();
        $resCheck = $stmtCheck->get_result();
        
        $dbQty = 0;
        if ($resCheck->num_rows > 0) {
            $rowCheck = $resCheck->fetch_assoc();
            $dbQty = intval($rowCheck['Quantity']);
        }
        $stmtCheck->close();
        
        $newQty = $dbQty + $quantity;
        if ($newQty > $product['Quantity']) {
            $newQty = $product['Quantity'];
            $_SESSION['warning'] = 'Chỉ có thể thêm tối đa ' . $product['Quantity'] . ' sản phẩm vào giỏ hàng do giới hạn tồn kho.';
        } else {
            $_SESSION['success'] = 'Đã thêm "' . $product['ProductName'] . '" vào giỏ hàng!';
        }
        
        if ($dbQty > 0) {
            $stmtUpdateCD = $conn->prepare("UPDATE cart_detail SET Quantity = ? WHERE CartID = ? AND ProductID = ?");
            $stmtUpdateCD->bind_param("iii", $newQty, $cartId, $productId);
            $stmtUpdateCD->execute();
            $stmtUpdateCD->close();
        } else {
            $stmtInsertCD = $conn->prepare("INSERT INTO cart_detail (CartID, ProductID, Quantity) VALUES (?, ?, ?)");
            $stmtInsertCD->bind_param("iii", $cartId, $productId, $newQty);
            $stmtInsertCD->execute();
            $stmtInsertCD->close();
        }
        
        // 3. Đồng bộ lại Session
        $_SESSION['cart'][$productId] = $newQty;
    }
} else {
    // Luồng khách vãng lai bình thường (chỉ lưu session)
    $currentQtyInCart = isset($_SESSION['cart'][$productId]) ? $_SESSION['cart'][$productId] : 0;
    $newQty = $currentQtyInCart + $quantity;
    
    if ($newQty > $product['Quantity']) {
        $_SESSION['cart'][$productId] = $product['Quantity'];
        $_SESSION['warning'] = 'Chỉ có thể thêm tối đa ' . $product['Quantity'] . ' sản phẩm vào giỏ hàng do giới hạn tồn kho.';
    } else {
        $_SESSION['cart'][$productId] = $newQty;
        $_SESSION['success'] = 'Đã thêm "' . $product['ProductName'] . '" vào giỏ hàng!';
    }
}

// Hủy áp dụng voucher cũ khi thêm sản phẩm mới
unset($_SESSION['applied_voucher']);

// Thiết lập Toast popup hiển thị ở giỏ hàng (không ghi CSDL)
if (isset($_SESSION['warning'])) {
    $_SESSION['log_toast'] = $_SESSION['warning'];
} elseif (isset($_SESSION['success'])) {
    $_SESSION['log_toast'] = $_SESSION['success'];
}

sendResponse(url('cart/cart.php'));
?>
