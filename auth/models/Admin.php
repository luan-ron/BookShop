<?php

/**
 * Admin Model
 * 
 * Lớp xử lý các thao tác truy vấn dữ liệu liên quan đến quản trị viên (bảng `user` với RoleID = 1).
 */
class Admin
{
    /**
     * @var mysqli Kết nối cơ sở dữ liệu
     */
    private $conn;

    /**
     * @var string Tên bảng trong cơ sở dữ liệu
     */
    private $table = 'user';

    /**
     * Khởi tạo đối tượng Admin và thiết lập kết nối cơ sở dữ liệu.
     */
    public function __construct()
    {
        if (!isset($GLOBALS['conn'])) {
            require_once __DIR__ . '/../../config/db.php';
        }
        $this->conn = $GLOBALS['conn'];
    }

    /**
     * Ánh xạ dữ liệu từ một dòng trong cơ sở dữ liệu sang định dạng mảng admin mà ứng dụng sử dụng.
     *
     * @param array $row Dòng dữ liệu từ bảng `user`.
     * @return array Mảng thông tin admin được chuẩn hóa.
     */
    private function mapRowToAdmin(array $row): array
    {
        $fullName = trim(($row['LastName'] ?? '') . ' ' . ($row['FirstName'] ?? ''));
        return [
            'id' => (int)$row['CustomerID'],
            'username' => $row['Email'], // Sử dụng Email làm username do bảng user không có cột username
            'email' => $row['Email'],
            'password' => $row['Password'] ?? '',
            'full_name' => $fullName,
            'phone' => $row['Phone'] ?? '',
            'address' => $row['Address'] ?? '',
            'role' => 'admin',
            'role_id' => 1,
            'created_at' => $row['CreatedDate'] ?? null
        ];
    }

    /**
     * Tìm kiếm admin theo ID.
     *
     * @param int $id ID của admin (CustomerID).
     * @return array|null Mảng thông tin quản trị viên nếu tìm thấy, ngược lại là null.
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT CustomerID, LastName, FirstName, Email, Phone, Address, RoleID, CreatedDate 
                FROM {$this->table} 
                WHERE CustomerID = ? AND RoleID = 1
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            return $this->mapRowToAdmin($result->fetch_assoc());
        }

        return null;
    }

    /**
     * Tìm kiếm admin theo địa chỉ email.
     *
     * @param string $email Địa chỉ email cần tìm.
     * @return array|null Mảng thông tin quản trị viên nếu tìm thấy, ngược lại là null.
     */
    public function findByEmail(string $email): ?array
    {
        $sql = "SELECT CustomerID, LastName, FirstName, Email, Password, Phone, Address, RoleID, CreatedDate 
                FROM {$this->table} 
                WHERE Email = ? AND RoleID = 1
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            return $this->mapRowToAdmin($result->fetch_assoc());
        }

        return null;
    }

    /**
     * Tìm kiếm admin theo tên đăng nhập (email).
     *
     * @param string $username Tên đăng nhập của quản trị viên.
     * @return array|null Mảng thông tin quản trị viên nếu tìm thấy, ngược lại là null.
     */
    public function findByUsername(string $username): ?array
    {
        return $this->findByEmail($username);
    }

    /**
     * Kiểm tra người dùng có phải là quản trị viên (RoleID = 1) hay không.
     *
     * @param int $userId ID của người dùng.
     * @return bool True nếu là admin, ngược lại là false.
     */
    public function isAdmin(int $userId): bool
    {
        $sql = "SELECT RoleID FROM {$this->table} WHERE CustomerID = ? LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            return (int)$user['RoleID'] === 1;
        }

        return false;
    }

    /**
     * Lấy danh sách tất cả các quản trị viên trong hệ thống.
     *
     * @return array Danh sách mảng thông tin các quản trị viên.
     */
    public function getAllAdmins(): array
    {
        $sql = "SELECT CustomerID, LastName, FirstName, Email, Phone, CreatedDate 
                FROM {$this->table} 
                WHERE RoleID = 1
                ORDER BY CreatedDate DESC";

        $result = $this->conn->query($sql);
        $admins = [];

        while ($row = $result->fetch_assoc()) {
            $admins[] = $this->mapRowToAdmin($row);
        }

        return $admins;
    }

    /**
     * Tạo một tài khoản admin mới.
     *
     * @param array $data Mảng dữ liệu admin chứa: email, password, full_name, phone, address.
     * @return bool True nếu tạo thành công, ngược lại là false.
     */
    public function create(array $data): bool
    {
        $sql = "INSERT INTO {$this->table} 
                (LastName, FirstName, Email, Password, Phone, Address, RoleID, CreatedDate) 
                VALUES (?, ?, ?, ?, ?, ?, 1, NOW())";

        $fullName = trim($data['full_name'] ?? $data['username'] ?? '');
        $parts = explode(' ', $fullName);
        if (count($parts) > 1) {
            $firstName = array_pop($parts);
            $lastName = implode(' ', $parts);
        } else {
            $firstName = $fullName;
            $lastName = '';
        }

        $phone = $data['phone'] ?? '';
        $address = $data['address'] ?? '';

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            'ssssss',
            $lastName,
            $firstName,
            $data['email'],
            $data['password'],
            $phone,
            $address
        );

        return $stmt->execute();
    }

    /**
     * Cập nhật thông tin của tài khoản admin.
     *
     * @param int $id ID của admin cần cập nhật.
     * @param array $data Mảng dữ liệu cập nhật chứa: full_name, email, phone, address.
     * @return bool True nếu cập nhật thành công, ngược lại là false.
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE {$this->table} 
                SET LastName = ?, FirstName = ?, Email = ?, Phone = ?, Address = ? 
                WHERE CustomerID = ? AND RoleID = 1";

        $fullName = trim($data['full_name'] ?? '');
        $parts = explode(' ', $fullName);
        if (count($parts) > 1) {
            $firstName = array_pop($parts);
            $lastName = implode(' ', $parts);
        } else {
            $firstName = $fullName;
            $lastName = '';
        }

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            'sssssi',
            $lastName,
            $firstName,
            $data['email'],
            $data['phone'],
            $data['address'],
            $id
        );

        return $stmt->execute();
    }

    /**
     * Xóa tài khoản admin khỏi nhóm quản trị (chuyển đổi vai trò về customer - RoleID = 2).
     *
     * @param int $id ID của admin cần hạ quyền.
     * @return bool True nếu thực hiện thành công, ngược lại là false.
     */
    public function delete(int $id): bool
    {
        $sql = "UPDATE {$this->table} SET RoleID = 2 WHERE CustomerID = ? AND RoleID = 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $id);

        return $stmt->execute();
    }
}