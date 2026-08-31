<?php
session_start();
require_once '../controller/registercontroller.php';

// Nếu đã đăng nhập → về trang chủ
if (isset($_SESSION['user'])) {
    header('Location: /BookShop/index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    $registerController = new RegisterController();
    $result = $registerController->register($username, $email, $password, $confirmPassword);

    if ($result['success']) {
        $success = $result['message'];
        // Redirect sau 2 giây
        header('Refresh: 2; URL=/BookShop/auth/pages/login.php');
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký</title>
    <link rel="stylesheet" href="/BookShop/assets/css/variables.css">
    <link rel="stylesheet" href="/BookShop/assets/css/components/button.css">
    <link rel="stylesheet" href="/BookShop/assets/css/components/form.css">
    <link rel="stylesheet" href="/BookShop/assets/css/components/card.css">
    <style>
        .alert {
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 16px;
            font-size: 0.9rem;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: var(--font-family-base);
            background: var(--color-background) url('/BookShop/assets/images/uploads/background_login.jpg') no-repeat center center fixed;
            background-size: cover;
            box-sizing: border-box;
        }

        .auth-card {
            width: 100%;
            max-width: 420px;
            padding: 24px;
            box-sizing: border-box;
        }

        .auth-card h1 {
            margin: 0 0 8px;
            font-size: 1.4rem;
            color: var(--color-primary);
            text-align: center
        }

        .auth-card p {
            margin: 0 0 16px;
            text-align: center;
            color: var(--color-text-light)
        }

        .form-group {
            margin-bottom: 16px
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--color-text);
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            box-sizing: border-box;
            border: var(--border-width) solid var(--color-border);
            border-radius: var(--border-radius-sm);
            font-family: inherit;
            font-size: var(--font-size-md);
            transition: border-color 0.2s ease;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 3px rgba(var(--color-primary-rgb), 0.1);
        }

        .actions {
            display: flex;
            flex-direction: column;
            gap: 10px;
            align-items: stretch;
            justify-content: center;
            margin-top: 20px;
        }

        .actions .btn,
        .actions a.btn {
            width: 100%;
            box-sizing: border-box;
            padding: 12px 20px;
        }

        .auth-footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 16px;
            border-top: var(--border-width) solid var(--color-border);
        }

        .auth-footer a {
            color: var(--color-primary);
            text-decoration: none;
            font-size: var(--font-size-sm);
        }

        .auth-footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <div class="card auth-card">
        <h1>Đăng ký</h1>
        <p>Tạo tài khoản mới của bạn</p>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        <form action="/BookShop/auth/pages/register.php" method="POST">
            <div class="form-group">
                <label for="username">Tên đăng nhập</label>
                <input type="text" id="username" name="username" placeholder="Nhập tên đăng nhập"
                    value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                    required autofocus>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="example@email.com"
                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
            </div>

            <div class="form-group">
                <label for="password">Mật khẩu</label>
                <div class="password-wrapper" style="position: relative;">
                    <input type="password" id="password" name="password" placeholder="Ít nhất 8 ký tự" required style="padding-right: 40px;">
                    <button type="button" class="toggle-password" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--color-text-light); display: flex; align-items: center; justify-content: center; z-index: 10; padding: 0;" aria-label="Hiện mật khẩu">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Xác nhận mật khẩu</label>
                <div class="password-wrapper" style="position: relative;">
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Nhập lại mật khẩu" required style="padding-right: 40px;">
                    <button type="button" class="toggle-password" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--color-text-light); display: flex; align-items: center; justify-content: center; z-index: 10; padding: 0;" aria-label="Hiện mật khẩu">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="actions">
                <button type="submit" class="btn btn-primary">Đăng ký</button>
                <a class="btn btn-secondary" href="/BookShop/auth/pages/login.php">Quay lại đăng nhập</a>
            </div>
        </form>

        <div class="auth-footer">
            <p style="margin: 0 0 8px; color: var(--color-text-light); font-size: var(--font-size-sm);">
                Đã có tài khoản?
            </p>
            <a href="/BookShop/auth/pages/login.php">Đăng nhập ngay</a>
        </div>
    </div>
    <script>
    document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', function() {
            const input = this.previousElementSibling;
            const icon = this.querySelector('svg');
            if (input.type === 'password') {
                input.type = 'text';
                this.setAttribute('aria-label', 'Ẩn mật khẩu');
                icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
            } else {
                input.type = 'password';
                this.setAttribute('aria-label', 'Hiện mật khẩu');
                icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
            }
        });
    });
    </script>
</body>

</html>