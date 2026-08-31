<?php

/**
 * Guest Model
 * 
 * Lớp xử lý các thao tác liên quan đến khách vãng lai chưa đăng nhập (RoleID = 10)
 * và quản lý giỏ hàng tạm thời.
 */
class Guest
{
    /**
     * @var mysqli Kết nối cơ sở dữ liệu
     */
    private $conn;

    /**
     * @var string Tên bảng người dùng
     */
    private $table = 'user';

    /**
     * Khởi tạo đối tượng Guest và thiết lập kết nối cơ sở dữ liệu.
     */
    public function __construct()
    {
        if (!isset($GLOBALS['conn'])) {
            require_once __DIR__ . '/../../config/db.php';
        }
        $this->conn = $GLOBALS['conn'];
    }

    /**
     * Tạo hoặc lấy ID khách vãng lai (Guest ID).
     * Tạo một bản ghi khách vãng lai mới trong bảng `user` nếu chưa có trong session.
     *
     * @return int ID khách vãng lai (CustomerID).
     */
    public function getGuestId(): int
    {
        // Kiểm tra nếu đã có guest_id trong session
        if (isset($_SESSION['guest_id'])) {
            return (int) $_SESSION['guest_id'];
        }

        // Tạo email ngẫu nhiên cho khách vãng lai
        $email = 'guest_' . bin2hex(random_bytes(8)) . '@bookstore.vn';
        
        // Tạo tài khoản khách vãng lai mới (RoleID = 10)
        $sql = "INSERT INTO {$this->table} (LastName, FirstName, Email, Password, RoleID, CreatedDate) 
                VALUES ('Guest', '', ?, '', 10, NOW())";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', $email);
        
        if ($stmt->execute()) {
            $guestId = $stmt->insert_id;
            $_SESSION['guest_id'] = $guestId;
            $stmt->close();
            return $guestId;
        }

        if (isset($stmt)) {
            $stmt->close();
        }

        // Trả về 0 nếu không tạo được
        return 0;
    }

    /**
     * Gộp giỏ hàng tạm thời của khách vãng lai vào giỏ hàng của người dùng khi đăng nhập.
     *
     * @param int $guestId ID của khách vãng lai.
     * @param int $userId ID của người dùng vừa đăng nhập.
     * @return bool True nếu gộp thành công, ngược lại là false.
     */
    public function mergeGuestCartToUser(int $guestId, int $userId): bool
    {
        // 1. Tìm hoặc lấy CartID của khách vãng lai
        $guestCartId = 0;
        $sql = "SELECT CartID FROM cart WHERE CustomerID = ? AND Status = 'Active' LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $guestId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $guestCartId = (int)$row['CartID'];
        }
        $stmt->close();

        // Nếu khách vãng lai không có giỏ hàng, kết thúc sớm
        if ($guestCartId === 0) {
            $this->deleteGuest($guestId);
            return true;
        }

        // 2. Lấy tất cả sản phẩm trong giỏ hàng của khách vãng lai
        $sql = "SELECT ProductID, Quantity, SizeID FROM cart_detail WHERE CartID = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $guestCartId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $guestItems = [];
        while ($row = $result->fetch_assoc()) {
            $guestItems[] = $row;
        }
        $stmt->close();

        // 3. Tìm hoặc tạo giỏ hàng hoạt động (Active) cho người dùng
        $userCartId = 0;
        $sql = "SELECT CartID FROM cart WHERE CustomerID = ? AND Status = 'Active' LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $userCartId = (int)$row['CartID'];
        }
        $stmt->close();

        if ($userCartId === 0) {
            // Tạo giỏ hàng mới cho người dùng
            $sql = "INSERT INTO cart (CustomerID, Status, CreatedDate) VALUES (?, 'Active', NOW())";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $userCartId = $stmt->insert_id;
            $stmt->close();
        }

        // 4. Gộp các sản phẩm vào giỏ hàng người dùng
        foreach ($guestItems as $item) {
            $productId = $item['ProductID'];
            $quantity = $item['Quantity'];
            $sizeId = $item['SizeID'];

            // Kiểm tra xem sản phẩm này đã tồn tại trong giỏ hàng người dùng chưa
            if ($sizeId !== null) {
                $checkSql = "SELECT Quantity FROM cart_detail WHERE CartID = ? AND ProductID = ? AND SizeID = ?";
                $checkStmt = $this->conn->prepare($checkSql);
                $checkStmt->bind_param('iii', $userCartId, $productId, $sizeId);
            } else {
                $checkSql = "SELECT Quantity FROM cart_detail WHERE CartID = ? AND ProductID = ? AND SizeID IS NULL";
                $checkStmt = $this->conn->prepare($checkSql);
                $checkStmt->bind_param('ii', $userCartId, $productId);
            }
            
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();

            if ($checkResult->num_rows > 0) {
                // Đã tồn tại -> cộng dồn số lượng
                $existing = $checkResult->fetch_assoc();
                $newQuantity = $existing['Quantity'] + $quantity;

                if ($sizeId !== null) {
                    $updateSql = "UPDATE cart_detail SET Quantity = ? WHERE CartID = ? AND ProductID = ? AND SizeID = ?";
                    $updateStmt = $this->conn->prepare($updateSql);
                    $updateStmt->bind_param('iiii', $newQuantity, $userCartId, $productId, $sizeId);
                } else {
                    $updateSql = "UPDATE cart_detail SET Quantity = ? WHERE CartID = ? AND ProductID = ? AND SizeID IS NULL";
                    $updateStmt = $this->conn->prepare($updateSql);
                    $updateStmt->bind_param('iii', $newQuantity, $userCartId, $productId);
                }
                $updateStmt->execute();
                $updateStmt->close();
            } else {
                // Chưa tồn tại -> thêm mới
                $insertSql = "INSERT INTO cart_detail (CartID, ProductID, Quantity, SizeID, AddedAt) VALUES (?, ?, ?, ?, NOW())";
                $insertStmt = $this->conn->prepare($insertSql);
                $insertStmt->bind_param('iiii', $userCartId, $productId, $quantity, $sizeId);
                $insertStmt->execute();
                $insertStmt->close();
            }
            $checkStmt->close();
        }

        // 5. Xóa giỏ hàng tạm thời của khách vãng lai
        $deleteDetailSql = "DELETE FROM cart_detail WHERE CartID = ?";
        $deleteDetailStmt = $this->conn->prepare($deleteDetailSql);
        $deleteDetailStmt->bind_param('i', $guestCartId);
        $deleteDetailStmt->execute();
        $deleteDetailStmt->close();

        $deleteCartSql = "DELETE FROM cart WHERE CartID = ?";
        $deleteCartStmt = $this->conn->prepare($deleteCartSql);
        $deleteCartStmt->bind_param('i', $guestCartId);
        $deleteCartStmt->execute();
        $deleteCartStmt->close();

        // 6. Xóa tài khoản khách vãng lai khỏi bảng user
        $this->deleteGuest($guestId);

        // Xóa thông tin khỏi session
        unset($_SESSION['guest_id']);

        return true;
    }

    /**
     * Lấy thông tin tài khoản khách vãng lai.
     *
     * @param int $guestId ID của khách vãng lai.
     * @return array|null Mảng thông tin khách vãng lai hoặc null.
     */
    public function getGuestInfo(int $guestId): ?array
    {
        $sql = "SELECT CustomerID, LastName, FirstName, Email, CreatedDate 
                FROM {$this->table} 
                WHERE CustomerID = ? AND RoleID = 10 
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $guestId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }

        return null;
    }

    /**
     * Xóa tài khoản khách vãng lai khỏi cơ sở dữ liệu.
     *
     * @param int $guestId ID của khách vãng lai.
     * @return bool True nếu xóa thành công, ngược lại là false.
     */
    public function deleteGuest(int $guestId): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE CustomerID = ? AND RoleID = 10";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $guestId);
        $result = $stmt->execute();
        $stmt->close();

        return $result;
    }
}