<?php

/**
 * ProfileController - Xử lý cập nhật thông tin profile và đổi mật khẩu
 */

require_once __DIR__ . '/../models/Customer.php';

class ProfileController
{
    private $customerModel;

    public function __construct()
    {
        $this->customerModel = new Customer();
    }

    /**
     * Lấy thông tin profile của user
     */
    public function getUserProfile(int $userId): ?array
    {
        return $this->customerModel->getUserProfile($userId);
    }

    /**
     * Cập nhật thông tin profile
     * 
     * @return array ['success' => bool, 'message' => string]
     */
    public function updateProfile(int $userId, array $data): array
    {
        // Bước 1: Validate input
        $validation = $this->validateProfileData($data);

        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => $validation['message']
            ];
        }

        // Bước 2: Kiểm tra email có bị trùng với user khác không
        $existingUser = $this->customerModel->findByEmail($data['email']);

        if ($existingUser && $existingUser['id'] !== $userId) {
            return [
                'success' => false,
                'message' => 'Email đã được sử dụng bởi tài khoản khác.'
            ];
        }

        // Bước 3: Cập nhật thông tin
        $updateData = [
            'username' => $data['username'],
            'email' => $data['email'],
            'full_name' => $data['full_name'],
            'phone' => $data['phone'] ?? '',
            'address' => $data['address'] ?? ''
        ];

        if ($this->customerModel->update($userId, $updateData)) {
            return [
                'success' => true,
                'message' => 'Cập nhật thông tin thành công!'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Cập nhật thất bại. Vui lòng thử lại sau.'
            ];
        }
    }

    /**
     * Đổi mật khẩu
     * 
     * @return array ['success' => bool, 'message' => string]
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword, string $confirmPassword): array
    {
        // Bước 1: Validate password mới
        $validation = $this->validatePassword($newPassword, $confirmPassword);

        if (!$validation['valid']) {
            return [
                'success' => false,
                'message' => $validation['message']
            ];
        }

        // Bước 2: Kiểm tra current password có đúng không
        if (!$this->customerModel->checkPassword($userId, $currentPassword)) {
            return [
                'success' => false,
                'message' => 'Mật khẩu hiện tại không đúng.'
            ];
        }

        // Bước 3: Hash password mới
        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

        // Bước 4: Cập nhật password
        if ($this->customerModel->updatePassword($userId, $newPasswordHash)) {
            return [
                'success' => true,
                'message' => 'Đổi mật khẩu thành công!'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Đổi mật khẩu thất bại. Vui lòng thử lại sau.'
            ];
        }
    }

    /**
     * Validate profile data
     * 
     * @return array ['valid' => bool, 'message' => string]
     */
    private function validateProfileData(array $data): array
    {
        // Validate email
        if (empty($data['email'])) {
            return ['valid' => false, 'message' => 'Vui lòng nhập email.'];
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'message' => 'Email không hợp lệ.'];
        }

        // Validate full_name
        if (empty($data['full_name'])) {
            return ['valid' => false, 'message' => 'Vui lòng nhập họ và tên.'];
        }

        if (strlen($data['full_name']) > 100) {
            return ['valid' => false, 'message' => 'Họ và tên không được vượt quá 100 ký tự.'];
        }

        // Validate phone (optional)
        if (!empty($data['phone'])) {
            if (!preg_match('/^[0-9]{10,11}$/', $data['phone'])) {
                return ['valid' => false, 'message' => 'Số điện thoại không hợp lệ (10-11 số).'];
            }
        }

        return ['valid' => true, 'message' => ''];
    }

    /**
     * Validate password
     * 
     * @return array ['valid' => bool, 'message' => string]
     */
    private function validatePassword(string $newPassword, string $confirmPassword): array
    {
        // Validate new password
        if (empty($newPassword)) {
            return ['valid' => false, 'message' => 'Vui lòng nhập mật khẩu mới.'];
        }

        if (strlen($newPassword) < 8) {
            return ['valid' => false, 'message' => 'Mật khẩu mới phải có ít nhất 8 ký tự.'];
        }

        if (strlen($newPassword) > 255) {
            return ['valid' => false, 'message' => 'Mật khẩu không được vượt quá 255 ký tự.'];
        }

        // Validate confirm password
        if (empty($confirmPassword)) {
            return ['valid' => false, 'message' => 'Vui lòng xác nhận mật khẩu mới.'];
        }

        if ($newPassword !== $confirmPassword) {
            return ['valid' => false, 'message' => 'Mật khẩu xác nhận không khớp.'];
        }

        return ['valid' => true, 'message' => ''];
    }
}
