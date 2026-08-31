<?php

// Tải các biến môi trường từ .env
require_once __DIR__ . '/env.php';

// Bắt đầu session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = env('DB_HOST');
$username = env('DB_USER');
$password = env('DB_PASS');
$dbname = env('DB_NAME');

// Tạo kết nối
$conn = new mysqli($host, $username, $password, $dbname);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối CSDL thất bại: " . $conn->connect_error);
}

// Set charset UTF-8 để hiển thị tiếng Việt không bị lỗi
$conn->set_charset("utf8mb4");
$GLOBALS['conn'] = $conn;

// --- Các hàm tiện ích dùng chung ---

// Hàm tạo đường dẫn tĩnh cho assets (CSS, JS, Images)
if (!function_exists('asset')) {
    function asset($path)
    {
        return '/BookShop/assets/' . ltrim($path, '/');
    }
}

// Hàm tạo đường dẫn absolute cho các trang
if (!function_exists('url')) {
    function url($path = '')
    {
        return '/BookShop/' . ltrim($path, '/');
    }
}

// Hàm lấy nguồn ảnh sản phẩm (hỗ trợ cả ảnh Cloudinary và ảnh local)
if (!function_exists('getProductImage')) {
    function getProductImage($imageUrl)
    {
        if (empty($imageUrl)) {
            return asset('images/default-book.png');
        }
        if (preg_match('/^https?:\/\//i', $imageUrl)) {
            return $imageUrl;
        }
        return url('assets' . $imageUrl);
    }
}

// Khôi phục đăng nhập từ JWT remember-me (chạy một lần mỗi request)
if (!defined('AUTH_SESSION_RESTORED')) {
    define('AUTH_SESSION_RESTORED', true);
    require_once __DIR__ . '/auth.php';
    require_once __DIR__ . '/../auth/helpers/JwtHelper.php';
    require_once __DIR__ . '/../auth/models/Customer.php';
    require_once __DIR__ . '/../auth/controller/authcontroller.php';
    AuthController::tryRestoreSession();
}

// Đồng bộ giỏ hàng session vào database cho thành viên khi đăng nhập
if (!function_exists('sync_cart_to_db')) {
    function sync_cart_to_db($conn, $customerId) {
        $customerId = intval($customerId);
        if ($customerId <= 0) return;

        // 1. Tìm hoặc tạo giỏ hàng Active của user
        $cartId = 0;
        $stmt = $conn->prepare("SELECT CartID FROM cart WHERE CustomerID = ? AND Status = 'Active' LIMIT 1");
        $stmt->bind_param("i", $customerId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows > 0) {
            $row = $res->fetch_assoc();
            $cartId = intval($row['CartID']);
        } else {
            $stmtInsert = $conn->prepare("INSERT INTO cart (CustomerID, Status) VALUES (?, 'Active')");
            $stmtInsert->bind_param("i", $customerId);
            $stmtInsert->execute();
            $cartId = $stmtInsert->insert_id;
            $stmtInsert->close();
        }
        $stmt->close();

        if ($cartId <= 0) return;

        // 2. Gộp sản phẩm từ Session nếu giỏ hàng session không trống
        $sessionCart = $_SESSION['cart'] ?? [];
        if (!empty($sessionCart)) {
            // Lấy giới hạn tồn kho của các sản phẩm để kiểm tra
            $placeholders = implode(',', array_fill(0, count($sessionCart), '?'));
            $productIds = array_keys($sessionCart);
            $sqlStock = "SELECT ProductID, Quantity FROM product WHERE ProductID IN ($placeholders)";
            $stmtStock = $conn->prepare($sqlStock);
            $types = str_repeat('i', count($productIds));
            $stmtStock->bind_param($types, ...$productIds);
            $stmtStock->execute();
            $resStock = $stmtStock->get_result();
            $stocks = [];
            while ($sRow = $resStock->fetch_assoc()) {
                $stocks[intval($sRow['ProductID'])] = intval($sRow['Quantity']);
            }
            $stmtStock->close();

            foreach ($sessionCart as $pId => $qty) {
                $pId = intval($pId);
                $qty = intval($qty);
                $stockLimit = $stocks[$pId] ?? 0;
                if ($stockLimit <= 0) continue;

                // Kiểm tra xem sản phẩm đã có trong cart_detail chưa
                $stmtCheck = $conn->prepare("SELECT Quantity FROM cart_detail WHERE CartID = ? AND ProductID = ?");
                $stmtCheck->bind_param("ii", $cartId, $pId);
                $stmtCheck->execute();
                $resCheck = $stmtCheck->get_result();
                
                if ($resCheck->num_rows > 0) {
                    // Đã có: Cộng dồn
                    $cdRow = $resCheck->fetch_assoc();
                    $newQty = intval($cdRow['Quantity']) + $qty;
                    if ($newQty > $stockLimit) {
                        $newQty = $stockLimit;
                    }
                    $stmtUpdate = $conn->prepare("UPDATE cart_detail SET Quantity = ? WHERE CartID = ? AND ProductID = ?");
                    $stmtUpdate->bind_param("iii", $newQty, $cartId, $pId);
                    $stmtUpdate->execute();
                    $stmtUpdate->close();
                } else {
                    // Chưa có: Thêm mới
                    if ($qty > $stockLimit) {
                        $qty = $stockLimit;
                    }
                    $stmtInsertCD = $conn->prepare("INSERT INTO cart_detail (CartID, ProductID, Quantity) VALUES (?, ?, ?)");
                    $stmtInsertCD->bind_param("iii", $cartId, $pId, $qty);
                    $stmtInsertCD->execute();
                    $stmtInsertCD->close();
                }
                $stmtCheck->close();
            }
        }

        // 3. Tải giỏ hàng từ Database đè lên Session
        $_SESSION['cart'] = [];
        $stmtLoad = $conn->prepare("SELECT ProductID, Quantity FROM cart_detail WHERE CartID = ?");
        $stmtLoad->bind_param("i", $cartId);
        $stmtLoad->execute();
        $resLoad = $stmtLoad->get_result();
        while ($lRow = $resLoad->fetch_assoc()) {
            $_SESSION['cart'][intval($lRow['ProductID'])] = intval($lRow['Quantity']);
        }
        $stmtLoad->close();
    }
}

if (!function_exists('getGuestInfoFromAddress')) {
    function getGuestInfoFromAddress($address) {
        $info = [
            'fullname' => 'Khách vãng lai',
            'phone' => '',
            'address' => $address
        ];
        if (preg_match('/Người nhận:\s*([^|]+)/i', $address, $matches)) {
            $info['fullname'] = trim($matches[1]);
        }
        if (preg_match('/SĐT:\s*([^|]+)/i', $address, $matches)) {
            $info['phone'] = trim($matches[1]);
        }
        if (preg_match('/Địa chỉ:\s*(.+)$/i', $address, $matches)) {
            $info['address'] = trim($matches[1]);
        }
        return $info;
    }
}

if (!function_exists('write_user_log')) {
    function write_user_log($conn, $action, $customerId = null, $employeeId = null) {
        // Tự động nhận diện CustomerID / EmployeeID từ session
        if ($customerId === null && $employeeId === null) {
            $sessionUser = $_SESSION['user'] ?? $_SESSION['admin'] ?? null;
            if ($sessionUser) {
                $userId = intval($sessionUser['id']);
                $roleId = intval($sessionUser['role_id'] ?? 2);
                if ($roleId === 2) { // 2 là Customer
                    $customerId = $userId;
                } else { // Các vai trò khác là nhân viên (Admin, Staff, Manager...)
                    $employeeId = $userId;
                }
            }
        }
        
        $sql = "INSERT INTO `user_log` (CustomerID, EmployeeID, Action) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("iis", $customerId, $employeeId, $action);
            $stmt->execute();
            $stmt->close();
        }
        // Lưu lại log để hiển thị Toast popup tức thời
        $_SESSION['log_toast'] = $action;
    }
}
?>
