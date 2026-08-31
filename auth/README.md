# Auth System - Hệ thống xác thực người dùng

## 📁 Cấu trúc thư mục

```
auth/
├── controller/
│   ├── authcontroller.php          # Xử lý login/logout
│   ├── registercontroller.php      # Xử lý đăng ký
│   ├── profilecontroller.php       # Xử lý cập nhật profile
│   ├── forgetpasswordcontroller.php # Xử lý quên mật khẩu
│   └── googleauthcontroller.php    # Google OAuth
├── models/
│   ├── Customer.php                # Model khách hàng
│   ├── Admin.php                   # Model admin
│   └── Guest.php                   # Model guest (chưa đăng nhập)
├── pages/
│   ├── login.php                   # Trang đăng nhập
│   ├── register.php                # Trang đăng ký
│   ├── profile.php                 # Trang hồ sơ cá nhân
│   └── Forgetpassword/
│       ├── index.php               # Bước 1: Nhập email
│       ├── verifyotp.php           # Bước 2: Verify OTP
│       └── reset.php               # Bước 3: Reset password
```

## 🚀 Tính năng

### 1. Đăng ký tài khoản
- Validate username, email, password
- Kiểm tra username/email đã tồn tại
- Hash password với `password_hash()`
- Lưu vào database

### 2. Đăng nhập
- Login với username hoặc email
- Verify password với `password_verify()`
- Hỗ trợ login với Google OAuth
- Session management

### 3. Quên mật khẩu
- Gửi OTP qua email
- Verify OTP (4 chữ số, hiệu lực 30 phút)
- Reset password với validation
- Xóa token sau khi sử dụng

### 4. Quản lý Profile
- Cập nhật thông tin cá nhân
- Đổi mật khẩu (cần nhập password hiện tại)
- Validate input data
- Load data từ database

## 📦 Dependencies

- PHP 7.4+
- MySQL/MariaDB
- PHPMailer (optional, để gửi email OTP)
- Google OAuth credentials (optional)

## 🔧 Cấu hình

### 1. Database Setup

Import schema và dữ liệu từ file `database/bookstore.sql`.

### 2. SMTP Configuration (để gửi email OTP)

Các biến SMTP được đọc từ `.env`:

```php
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your-email@gmail.com
SMTP_PASSWORD=your-app-password
SMTP_FROM_EMAIL=your-email@gmail.com
```

**Lưu ý:** Với Gmail, bạn cần tạo App Password:
1. Vào Google Account
2. Security → 2-Step Verification
3. Tạo App Password

### 3. Google OAuth (optional)

Mở file `auth/controller/googleauthcontroller.php`:

```php
define('GOOGLE_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID');
define('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET');
define('GOOGLE_REDIRECT_URI', 'http://localhost/BookShop/auth/pages/login.php');
```

## 📝 Sử dụng

### Đăng ký tài khoản

```php
// Trong register.php
$registerController = new RegisterController();
$result = $registerController->register($username, $email, $password, $confirmPassword);

if ($result['success']) {
    // Đăng ký thành công
} else {
    // Hiển thị lỗi: $result['message']
}
```

### Đăng nhập

```php
// Trong login.php
$user = AuthController::login($identity, $password);

if ($user) {
    $_SESSION['user'] = $user;
    // Đăng nhập thành công
} else {
    // Sai thông tin
}
```

### Cập nhật profile

```php
// Trong profile.php
$profileController = new ProfileController();

// Cập nhật thông tin
$result = $profileController->updateProfile($userId, $data);

// Đổi mật khẩu
$result = $profileController->changePassword($userId, $currentPassword, $newPassword, $confirmPassword);
```

### Quên mật khẩu

```php
// Bước 1: Gửi OTP
$forgetPasswordController = new ForgetPasswordController();
$result = $forgetPasswordController->sendOTP($email);

// Bước 2: Verify OTP
$result = $forgetPasswordController->verifyOTP($email, $otp);

// Bước 3: Reset password
$result = $forgetPasswordController->resetPassword($email, $newPassword, $confirmPassword);
```

## 🔒 Security Features

1. **Password Hashing**: Sử dụng `password_hash()` và `password_verify()`
2. **SQL Injection Prevention**: Sử dụng prepared statements
3. **XSS Prevention**: Sử dụng `htmlspecialchars()` khi output
4. **CSRF Protection**: Có thể thêm CSRF token vào forms
5. **Session Security**: Session regenerate, secure flags
6. **Input Validation**: Validate tất cả input trước khi xử lý
7. **OTP Security**: OTP có hiệu lực 30 phút, tự động hết hạn

## 🧪 Testing

### Test Register Flow
1. Truy cập `/BookShop/auth/pages/register.php`
2. Điền thông tin đăng ký
3. Kiểm tra database có user mới không

### Test Login Flow
1. Truy cập `/BookShop/auth/pages/login.php`
2. Đăng nhập với tài khoản vừa tạo
3. Kiểm tra session có user không

### Test Profile Flow
1. Đăng nhập
2. Truy cập `/BookShop/auth/pages/profile.php`
3. Cập nhật thông tin
4. Đổi mật khẩu

### Test Forget Password Flow
1. Truy cập `/BookShop/auth/pages/Forgetpassword/index.php`
2. Nhập email
3. Kiểm tra email nhận OTP
4. Nhập OTP
5. Đặt mật khẩu mới
6. Đăng nhập với mật khẩu mới

## 🐛 Troubleshooting

### Lỗi "Class 'Customer' not found"
- Kiểm tra đường dẫn require_once trong controller
- Đảm bảo file `auth/models/Customer.php` tồn tại

### Lỗi "Table doesn't exist"
- Chạy SQL trong `database/bookstore.sql`
- Kiểm tra database name trong `config/db.php`

### Lỗi "Email không gửi được"
- Kiểm tra SMTP configuration
- Kiểm tra PHPMailer đã được cài đặt
- Kiểm tra SMTP variables trong `.env` và PHPMailer trong `vendor/PHPMailer/`

### Lỗi "OTP không đúng"
- Kiểm tra database có token không
- Kiểm tra token đã hết hạn chưa
- Xem error_log để xem OTP được gửi đi

## 📚 API Reference

### Customer Model

```php
$customer = new Customer();

// Tìm user
$customer->findById($id);
$customer->findByEmail($email);
$customer->findByUsername($username);
$customer->findByIdentity($identity);

// Tạo user
$customer->create($data);

// Cập nhật
$customer->update($id, $data);
$customer->updatePassword($id, $passwordHash);

// Kiểm tra
$customer->checkPassword($id, $password);
$customer->usernameExists($username);
$customer->emailExists($email);
```

### RegisterController

```php
$controller = new RegisterController();
$result = $controller->register($username, $email, $password, $confirmPassword);
// Return: ['success' => bool, 'message' => string]
```

### ProfileController

```php
$controller = new ProfileController();

// Lấy profile
$user = $controller->getUserProfile($userId);

// Cập nhật profile
$result = $controller->updateProfile($userId, $data);

// Đổi mật khẩu
$result = $controller->changePassword($userId, $currentPassword, $newPassword, $confirmPassword);
```

### ForgetPasswordController

```php
$controller = new ForgetPasswordController();

// Gửi OTP
$result = $controller->sendOTP($email);

// Verify OTP
$result = $controller->verifyOTP($email, $otp);

// Reset password
$result = $controller->resetPassword($email, $newPassword, $confirmPassword);

// Gửi lại OTP
$result = $controller->resendOTP($email);
```

## 🎯 Next Steps

1. Setup database theo hướng dẫn
2. Tạo tài khoản admin đầu tiên
3. Test các chức năng
4. (Optional) Cấu hình SMTP để gửi email thật
5. (Optional) Cấu hình Google OAuth
6. (Optional) Thêm CSRF protection
7. (Optional) Thêm Remember Me functionality

## 📞 Support

Nếu gặp vấn đề, kiểm tra:
- PHP error log
- MySQL error log
- File `config/db.php`
- Database đã được setup chưa

## 📄 License

MIT License
