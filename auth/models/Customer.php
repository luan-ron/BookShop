<?php

/**
 * Customer Model
 * 
 * Lớp xử lý các thao tác truy vấn dữ liệu liên quan đến khách hàng (bảng `user`).
 */
class Customer
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
     * Khởi tạo đối tượng Customer và thiết lập kết nối cơ sở dữ liệu.
     */
    public function __construct()
    {
        if (!isset($GLOBALS['conn'])) {
            require_once __DIR__ . '/../../config/db.php';
        }
        $this->conn = $GLOBALS['conn'];
    }

    /**
     * Ánh xạ mã RoleID sang tên vai trò (role name).
     *
     * @param int $roleId Mã vai trò từ cơ sở dữ liệu.
     * @return string Tên vai trò tương ứng (mặc định là 'customer').
     */
    private function getRoleName(int $roleId): string
    {
        $roles = [
            1 => 'admin',
            2 => 'customer'
        ];
        return $roles[$roleId] ?? 'customer';
    }

    /**
     * Ánh xạ tên vai trò (role name) sang mã RoleID.
     *
     * @param string $roleName Tên vai trò.
     * @return int Mã vai trò tương ứng (mặc định là 2 - customer).
     */
    private function getRoleId(string $roleName): int
    {
        $roles = [
            'admin' => 1,
            'customer' => 2
        ];
        return $roles[strtolower($roleName)] ?? 2;
    }

    /**
     * Ánh xạ dữ liệu từ một dòng trong cơ sở dữ liệu sang định dạng mảng user mà ứng dụng sử dụng.
     *
     * @param array $row Dòng dữ liệu từ bảng `user`.
     * @return array Mảng thông tin người dùng được chuẩn hóa.
     */
    private function mapRowToUser(array $row): array
    {
        $fullName = trim(($row['LastName'] ?? '') . ' ' . ($row['FirstName'] ?? ''));
        return [
            'id' => (int) $row['CustomerID'],
            'username' => $row['username'] ?? $row['Email'] ?? '',
            'email' => $row['Email'],
            'password' => $row['Password'] ?? '',
            'full_name' => $fullName,
            'phone' => $row['Phone'] ?? '',
            'address' => $row['Address'] ?? '',
            'role' => $this->getRoleName((int) ($row['RoleID'] ?? 2)),
            'role_id' => (int) ($row['RoleID'] ?? 2),
            'created_at' => $row['CreatedDate'] ?? null
        ];
    }

    /**
     * Tìm kiếm người dùng theo ID.
     *
     * @param int $id ID của khách hàng (CustomerID).
     * @return array|null Mảng thông tin khách hàng nếu tìm thấy, ngược lại là null.
     */
    public function findById(int $id): ?array
    {
        $sql = "SELECT CustomerID, RoleID, LastName, FirstName, Email, username, Phone, Address, CreatedDate 
                FROM {$this->table} 
                WHERE CustomerID = ? 
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            return $this->mapRowToUser($result->fetch_assoc());
        }

        return null;
    }

    /**
     * Tìm kiếm người dùng theo địa chỉ email.
     *
     * @param string $email Địa chỉ email cần tìm.
     * @return array|null Mảng thông tin khách hàng nếu tìm thấy, ngược lại là null.
     */
    public function findByEmail(string $email): ?array
    {
        $sql = "SELECT CustomerID, RoleID, LastName, FirstName, Email, username, Password, Phone, Address, CreatedDate 
                FROM {$this->table} 
                WHERE Email = ? 
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            return $this->mapRowToUser($result->fetch_assoc());
        }

        return null;
    }

    /**
     * Tìm kiếm người dùng theo tên đăng nhập.
     * Do cấu trúc bảng `user` không có cột `username`, hệ thống sẽ tìm kiếm theo email.
     *
     * @param string $username Tên đăng nhập (email).
     * @return array|null Mảng thông tin khách hàng nếu tìm thấy, ngược lại là null.
     */
    public function findByUsername(string $username): ?array
    {
        $sql = "SELECT CustomerID, RoleID, LastName, FirstName, Email, username, Password, Phone, Address, CreatedDate 
                FROM {$this->table} 
                WHERE username = ? 
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            return $this->mapRowToUser($result->fetch_assoc());
        }

        return null;
    }

    /**
     * Tìm kiếm người dùng theo định danh (username hoặc email) để phục vụ đăng nhập.
     *
     * @param string $identity Tên đăng nhập hoặc email.
     * @return array|null Mảng thông tin khách hàng nếu tìm thấy, ngược lại là null.
     */
    public function findByIdentity(string $identity): ?array
    {
        $sql = "SELECT CustomerID, RoleID, LastName, FirstName, Email, username, Password, Phone, Address, CreatedDate 
                FROM {$this->table} 
                WHERE username = ? OR Email = ? 
                LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ss', $identity, $identity);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            return $this->mapRowToUser($result->fetch_assoc());
        }

        return null;
    }

    /**
     * Tạo một người dùng mới trong hệ thống.
     *
     * @param array $data Mảng dữ liệu người dùng chứa: email, password, full_name, phone, address, role.
     * @return bool True nếu tạo thành công, ngược lại là false.
     */
    public function create(array $data): bool
    {
        $sql = "INSERT INTO {$this->table} 
                (LastName, FirstName, Email, username, Password, Phone, Address, RoleID, CreatedDate) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $fullName = trim($data['full_name'] ?? $data['username'] ?? '');
        $parts = explode(' ', $fullName);
        if (count($parts) > 1) {
            $firstName = array_pop($parts);
            $lastName = implode(' ', $parts);
        } else {
            $firstName = $fullName;
            $lastName = '';
        }

        $roleId = $this->getRoleId($data['role'] ?? 'customer');
        $phone = $data['phone'] ?? '';
        $address = $data['address'] ?? '';
        $username = $data['username'] ?? '';

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            'sssssssi',
            $lastName,
            $firstName,
            $data['email'],
            $username,
            $data['password'],
            $phone,
            $address,
            $roleId
        );

        return $stmt->execute();
    }

    /**
     * Cập nhật thông tin cá nhân của người dùng.
     *
     * @param int $id ID của khách hàng cần cập nhật.
     * @param array $data Mảng dữ liệu cập nhật chứa: full_name, email, phone, address.
     * @return bool True nếu cập nhật thành công, ngược lại là false.
     */
    public function update(int $id, array $data): bool
    {
        $sql = "UPDATE {$this->table} 
                SET LastName = ?, FirstName = ?, Email = ?, Phone = ?, Address = ? 
                WHERE CustomerID = ?";

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
     * Cập nhật mật khẩu mới cho người dùng.
     *
     * @param int $id ID của khách hàng cần cập nhật.
     * @param string $newPasswordHash Mật khẩu mới đã được mã hóa.
     * @return bool True nếu cập nhật thành công, ngược lại là false.
     */
    public function updatePassword(int $id, string $newPasswordHash): bool
    {
        $sql = "UPDATE {$this->table} SET Password = ? WHERE CustomerID = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('si', $newPasswordHash, $id);

        return $stmt->execute();
    }

    /**
     * Kiểm tra mật khẩu của người dùng có chính xác không.
     *
     * @param int $id ID của khách hàng cần kiểm tra.
     * @param string $password Mật khẩu gốc nhập vào.
     * @return bool True nếu mật khẩu đúng, ngược lại là false.
     */
    public function checkPassword(int $id, string $password): bool
    {
        $sql = "SELECT Password FROM {$this->table} WHERE CustomerID = ? LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            return password_verify($password, $user['Password']);
        }

        return false;
    }

    /**
     * Kiểm tra tên đăng nhập đã tồn tại chưa.
     *
     * @param string $username Tên đăng nhập (email) cần kiểm tra.
     * @return bool True nếu đã tồn tại, ngược lại là false.
     */
    public function usernameExists(string $username): bool
    {
        $sql = "SELECT CustomerID FROM {$this->table} WHERE username = ? LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->num_rows > 0;
    }

    /**
     * Kiểm tra địa chỉ email đã tồn tại trong hệ thống chưa.
     *
     * @param string $email Địa chỉ email cần kiểm tra.
     * @return bool True nếu đã tồn tại, ngược lại là false.
     */
    public function emailExists(string $email): bool
    {
        $sql = "SELECT CustomerID FROM {$this->table} WHERE Email = ? LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->num_rows > 0;
    }

    /**
     * Ghi nhận lịch sử đăng nhập thành công của người dùng vào bảng `user_log`.
     *
     * @param int $id ID của khách hàng vừa đăng nhập.
     * @return bool True nếu ghi nhận thành công, ngược lại là false.
     */
    public function updateLastLogin(int $id): bool
    {
        $sql = "INSERT INTO user_log (CustomerID, Action, LogDate) VALUES (?, 'Đăng nhập hệ thống', NOW())";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $id);

        return $stmt->execute();
    }

    /**
     * Lấy thông tin chi tiết hồ sơ cá nhân của người dùng (không bao gồm mật khẩu).
     *
     * @param int $id ID của khách hàng.
     * @return array|null Mảng thông tin hồ sơ hoặc null nếu không tìm thấy.
     */
    public function getUserProfile(int $id): ?array
    {
        return $this->findById($id);
    }
}