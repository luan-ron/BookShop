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
            padding: 24px;
            font-family: var(--font-family-base);
            background:
                linear-gradient(135deg, rgba(0, 169, 242, 0.18), rgba(15, 23, 42, 0.45)),
                url('/BookShop/assets/images/uploads/background_login.jpg') no-repeat center center fixed;
            background-size: cover;
            box-sizing: border-box;
        }

        .auth-card {
            width: 100%;
            max-width: 430px;
            padding: 34px;
            box-sizing: border-box;
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid rgba(255, 255, 255, 0.7);
            border-radius: 20px;
            box-shadow: 0 20px 55px rgba(15, 23, 42, 0.22);
            backdrop-filter: blur(10px);
        }

        .auth-card h1 {
            margin: 0 0 26px;
            font-size: 30px;
            font-weight: 800;
            color: var(--color-primary);
            text-align: center
        }

        .auth-card p {
            margin: 0 0 16px;
            text-align: center;
            color: var(--color-text-light);
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 18px
        }

        label {
            display: block;
            margin-bottom: 7px;
            font-size: 14px;
            font-weight: 600;
            color: var(--color-text);
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            min-height: 46px;
            padding: 11px 14px;
            box-sizing: border-box;
            border: var(--border-width) solid var(--color-border);
            border-radius: 10px;
            font-family: inherit;
            font-size: 15px;
            transition: border-color 0.2s ease;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: var(--color-primary);
            box-shadow: 0 0 0 4px rgba(0, 169, 242, 0.12);
            transform: translateY(-1px);
        }

        .actions {
            display: flex;
            flex-direction: column;
            gap: 12px;
            align-items: stretch;
            justify-content: center;
            margin-top: 22px;
        }

        .actions .btn,
        .actions a.btn {
            width: 100%;
            min-height: 46px;
            box-sizing: border-box;
            border-radius: 10px;
        }

        .actions button[type="submit"] {
            background: var(--color-primary) !important;
            color: #ffffff !important;
            border: none !important;
            font-size: 15px;
            font-weight: 700;
        }

        .actions button[type="submit"]:hover {
            background: var(--color-primary-hover) !important;
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(0, 169, 242, 0.25);
        }

        .auth-footer {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: 4px;
            text-align: center;
            margin-top: 24px;
            padding-top: 18px;
            border-top: 1px solid #e2e8f0;
        }

        .auth-footer a {
            color: var(--color-primary);
            text-decoration: none;
            font-size: 13px;
        }

        .auth-footer a:hover {
            text-decoration: underline;
        }

        @media (max-width: 480px) {
            body {
                padding: 16px;
            }

            .auth-card {
                padding: 24px;
            }

            .auth-card h1 {
                margin-bottom: 22px;
                font-size: 26px;
            }
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
            </div>
        </form>

        <div class="auth-footer">
            <span>Đã có tài khoản?</span>
            <a href="/BookShop/auth/pages/login.php">Đăng nhập</a>
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
