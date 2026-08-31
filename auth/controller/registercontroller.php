<?php

/**
 * RegisterController - Xử lý đăng ký tài khoản mới
 */

require_once __DIR__ . '/../models/Customer.php';

class RegisterController
{
    private $customerModel;

    public function __construct()
    {
        $this->customerModel = new Customer();
    }

    /**
     * Xử lý đăng ký tài khoản
     * 
     * @return array ['success' => bool, 'message' => string]
     */
    public function register(string $username, string $email, string $password, string $confirmPassword): array
    {
        // Bước 1: Validate input
        $validation = $this->validateInput($username, $email, $password, $confirmPassword);

        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => $validation['message']
            ];
        }

        // Bước 2: Kiểm tra username đã tồn tại chưa
        if ($this->customerModel->usernameExists($username)) {
            return [
                'success' => false,
                'message' => 'Tên đăng nhập đã tồn tại. Vui lòng chọn tên khác.'
            ];
        }

        // Bước 3: Kiểm tra email đã tồn tại chưa
        if ($this->customerModel->emailExists($email)) {
            return [
                'success' => false,
                'message' => 'Email đã được sử dụng. Vui lòng dùng email khác.'
            ];
        }

        // Bước 4: Hash password
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        // Bước 5: Tạo user mới
        $userData = [
            'username' => $username,
            'email' => $email,
            'password' => $passwordHash,
            'full_name' => $username, // Mặc định full_name = username
            'role' => 'customer'
        ];

        if ($this->customerModel->create($userData)) {
            return [
                'success' => true,
                'message' => 'Đăng ký thành công! Đang chuyển đến trang đăng nhập...'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Đăng ký thất bại. Vui lòng thử lại sau.'
            ];
        }
    }

    /**
     * Validate input data
     * 
     * @return array ['valid' => bool, 'message' => string]
     */
    private function validateInput(string $username, string $email, string $password, string $confirmPassword): array
    {
        // Validate username
        if (empty($username)) {
            return ['valid' => false, 'message' => 'Vui lòng nhập tên đăng nhập.'];
        }

        if (strlen($username) < 3) {
            return ['valid' => false, 'message' => 'Tên đăng nhập phải có ít nhất 3 ký tự.'];
        }

        if (strlen($username) > 50) {
            return ['valid' => false, 'message' => 'Tên đăng nhập không được vượt quá 50 ký tự.'];
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            return ['valid' => false, 'message' => 'Tên đăng nhập chỉ được chứa chữ cái, số và dấu gạch dưới.'];
        }

        // Validate email
        if (empty($email)) {
            return ['valid' => false, 'message' => 'Vui lòng nhập email.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'message' => 'Email không hợp lệ.'];
        }

        // Validate password
        if (empty($password)) {
            return ['valid' => false, 'message' => 'Vui lòng nhập mật khẩu.'];
        }

        if (strlen($password) < 8) {
            return ['valid' => false, 'message' => 'Mật khẩu phải có ít nhất 8 ký tự.'];
        }

        if (strlen($password) > 255) {
            return ['valid' => false, 'message' => 'Mật khẩu không được vượt quá 255 ký tự.'];
        }

        // Validate confirm password
        if (empty($confirmPassword)) {
            return ['valid' => false, 'message' => 'Vui lòng xác nhận mật khẩu.'];
        }

        if ($password !== $confirmPassword) {
            return ['valid' => false, 'message' => 'Mật khẩu xác nhận không khớp.'];
        }

        return ['valid' => true, 'message' => ''];
    }
}