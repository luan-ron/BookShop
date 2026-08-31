<?php
session_start();
require_once '../controller/authcontroller.php';
require_once '../controller/googleauthcontroller.php';
// require_once '../controller/fbauthcontroller.php';

// Xử lý logout
if (isset($_POST['action']) && $_POST['action'] === 'logout') {
    $type = $_POST['type'] ?? 'user';
    if ($type === 'admin') {
        require_once '../../admin/data.php';
        if (!verifyAdminCsrf($_POST['csrf_token'] ?? '')) {
            $_SESSION['log_toast'] = 'Yêu cầu không hợp lệ. Vui lòng thử lại.';
            header('Location: /BookShop/admin/index.php');
            exit;
        }
    }
    AuthController::logout($type);
    if ($type === 'admin') {
        header('Location: /BookShop/auth/pages/login.php');
    } else {
        header('Location: /BookShop/index.php');
    }
    exit;
}

// Nếu đã đăng nhập → chuyển theo vai trò
if (isset($_SESSION['user'])) {
    $redirectAfterLogin = $_SESSION['redirect_after_login'] ?? '';
    unset($_SESSION['redirect_after_login']);
    header('Location: ' . ($redirectAfterLogin === '/BookShop/cart/checkout.php'
        ? $redirectAfterLogin
        : AuthController::getRedirectUrl($_SESSION['user'])));
    exit;
}

// Xử lý đăng nhập bằng form
$error = $_SESSION['error'] ?? '';
unset($_SESSION['error']);
$success = $_SESSION['success'] ?? '';
unset($_SESSION['success']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ thông tin đăng nhập.';
    } else {
        $user = AuthController::login($username, $password);
        if ($user) {
            $remember = isset($_POST['remember']) && $_POST['remember'] === 'on';
            AuthController::establishSession($user, $remember);
            
            // Đồng bộ giỏ hàng khi đăng nhập thành công (từ nhánh Payment-cart)
            require_once '../../config/db.php';
            sync_cart_to_db($conn, $user['id']);
            write_user_log($conn, "Đăng nhập hệ thống");
            
            $redirectAfterLogin = $_SESSION['redirect_after_login'] ?? '';
            unset($_SESSION['redirect_after_login']);
            header('Location: ' . ($redirectAfterLogin === '/BookShop/cart/checkout.php'
                ? $redirectAfterLogin
                : AuthController::getRedirectUrl($user)));
            exit;
        } else {
            $error = 'Tên đăng nhập hoặc mật khẩu không đúng.';
        }
    }
}

// Xử lý callback từ Google
if (isset($_GET['code'])) {
    $user = googleauthcontroller::handleCallback();

    if ($user) {
        AuthController::establishSession($user, false);
        
        // Đồng bộ giỏ hàng khi đăng nhập bằng Google thành công (từ nhánh Payment-cart)
        require_once '../../config/db.php';
        sync_cart_to_db($conn, $user['id']);
        write_user_log($conn, "Đăng nhập hệ thống bằng Google");
        
        $redirectAfterLogin = $_SESSION['redirect_after_login'] ?? '';
        unset($_SESSION['redirect_after_login']);
        header('Location: ' . ($redirectAfterLogin === '/BookShop/cart/checkout.php'
            ? $redirectAfterLogin
            : AuthController::getRedirectUrl($user)));
        exit;
    } else {
        $error = 'Đăng nhập bằng tài khoản Google thất bại. Vui lòng kiểm tra lại cấu hình hoặc thử lại.';
    }
}

$google_login_url = googleauthcontroller::getLoginUrl();
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng nhập</title>
    <link rel="stylesheet" href="/BookShop/assets/css/variables.css">
    <link rel="stylesheet" href="/BookShop/assets/css/components/form.css">
    <link rel="stylesheet" href="/BookShop/assets/css/components/button.css">
    <link rel="stylesheet" href="/BookShop/assets/css/components/card.css">

    <style>
        * {
            box-sizing: border-box;
        }

        .login-page {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            background:
                linear-gradient(135deg, rgba(0, 169, 242, 0.18), rgba(15, 23, 42, 0.45)),
                url('/BookShop/assets/images/uploads/background_login.jpg') no-repeat center center fixed;
            background-size: cover;
            font-family: var(--font-family-base);
        }

        .auth-card {
            width: 100%;
            max-width: 430px;
            padding: 34px;
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid rgba(255, 255, 255, 0.7);
            border-radius: 20px;
            box-shadow: 0 20px 55px rgba(15, 23, 42, 0.22);
            backdrop-filter: blur(10px);
        }

        .auth-card h1 {
            margin: 0 0 26px;
            text-align: center;
            color: var(--color-primary);
            font-size: 30px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group > label {
            display: block;
            margin-bottom: 7px;
            color: var(--color-text);
            font-size: 14px;
            font-weight: 600;
        }

        .form-group > input {
            width: 100%;
            min-height: 46px;
            padding: 11px 14px;
            box-sizing: border-box;
            background: #ffffff;
            color: var(--color-text);
            border: 1px solid var(--color-border);
            border-radius: 10px;
            outline: none;
            font-size: 15px;
        }

        .form-group > input::placeholder,
        .input-row input::placeholder { color: #94a3b8; }

        .remember-group {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .remember-group input[type="checkbox"] {
            width: 16px;
            height: 16px;
            margin: 0;
            padding: 0;
        }

        .input-row {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .input-row input {
            width: 100%;
            flex: 1;
            min-height: 46px;
            padding: 11px 44px 11px 14px !important;
            background: #ffffff;
            color: var(--color-text);
            border: 1px solid var(--color-border);
            border-radius: 10px;
            outline: none;
            font-size: 15px;
        }

        .form-group > input:focus,
        .input-row input:focus {
            border-color: var(--color-primary);
            box-shadow: 0 0 0 4px rgba(0, 169, 242, 0.12);
            transform: translateY(-1px);
        }

        .input-row .btn,
        .input-row a.btn {
            white-space: nowrap;
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
            border-radius: 10px;
            box-sizing: border-box;
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

        .actions a[href*="google"] {
            background: #ffffff !important;
            color: #334155 !important;
            border: 1px solid var(--color-border) !important;
            font-weight: 600;
        }

        .actions a[href*="google"] img {
            width: 22px !important;
            height: 22px !important;
        }

        .remember-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            margin-top: 8px;
        }

        .remember-row .btn,
        .remember-row a.btn {
            width: auto;
            min-width: 0;
        }

        .remember-row .register-link {
            margin-left: auto;
        }

        .register-link {
            padding: 9px 16px;
            background: #ffffff !important;
            color: var(--color-primary) !important;
            border: 1px solid var(--color-primary) !important;
            border-radius: 9px;
            font-size: 14px;
            font-weight: 700;
        }

        .actions a[href*="google"]:hover {
            background: #f8fafc !important;
            border-color: var(--color-primary) !important;
            transform: translateY(-1px);
        }

        .auth-card > div[style*="f8d7da"] {
            background: #fff1f2 !important;
            color: #be123c !important;
            border: 1px solid #fecdd3 !important;
            border-radius: 10px !important;
        }

        .auth-card > div[style*="d4edda"] {
            background: #ecfdf5 !important;
            color: #047857 !important;
            border: 1px solid #a7f3d0 !important;
            border-radius: 10px !important;
        }

        .auth-card > div:last-child {
            margin-top: 24px !important;
            padding-top: 18px !important;
            border-top: 1px solid #e2e8f0 !important;
        }

        .auth-card > div:last-child a {
            font-size: 13px !important;
        }

        .auth-card > div:last-child a:first-child { color: #64748b !important; }
        .auth-card > div:last-child a:last-child { color: var(--color-primary) !important; }

        @media (max-width: 600px) {
            .login-page {
                padding: 16px;
            }

            .login-page .auth-card {
                padding: 25px 20px;
                border-radius: 16px;
            }

            .login-page .auth-card h1 {
                font-size: 26px;
            }

            .login-page .remember-row {
                align-items: flex-start;
            }
        }
    </style>
</head>

<body class="login-page">
    <div class="card auth-card">
        <h1>Đăng nhập</h1>
        <?php if ($error): ?>
            <div
                style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 14px; text-align: center; border: 1px solid #f5c6cb;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div
                style="background-color: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 15px; font-size: 14px; text-align: center; border: 1px solid #c3e6cb;">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>
        <form action="/BookShop/auth/pages/login.php" method="POST">
            <div class="form-group">
                <label for="username">Tên đăng nhập hoặc Email</label>
                <input id="username" name="username" type="text" required placeholder="Tên đăng nhập hoặc email">
            </div>
            <div class="form-group">
                <label for="password">Mật khẩu</label>
                <div class="input-row" style="position: relative;">
                    <input id="password" name="password" type="password" required placeholder="Mật khẩu" style="padding-right: 40px;">
                    <button type="button" class="toggle-password" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--color-text-light); display: flex; align-items: center; justify-content: center; z-index: 10; padding: 0;" aria-label="Hiện mật khẩu">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="remember-row">
                <div class="remember-group">
                    <input type="checkbox" name="remember" id="remember">
                    <label for="remember">Ghi nhớ đăng nhập</label>
                </div>
                <a class="btn btn-secondary register-link" href="/BookShop/auth/pages/register.php">Đăng ký</a>
            </div>
            <div class="actions">
                <button type="submit" class="btn btn-primary"
                    style="display:flex; align-items:center; justify-content:center; font-weight: bold; color: white; background-color: #ff6b1a; border: none; cursor: pointer;">
                    Đăng nhập</button>
                <a href="<?= $google_login_url ?>" class="btn btn-primary"
                    style="text-align:center; text-decoration:none; font-weight: bold; color: white; display:flex; align-items:center; justify-content:center; background-color: #ef3030ff; gap: 8px;">
                    <img src="/BookShop/assets/images/icon/gg.png" alt="Google"
                        style="width: 24px; height: 24px; object-fit: contain; background: white; border-radius: 50%; padding: 2px;">
                    Đăng nhập với Google
                </a>
            </div>
        </form>
        <div style="text-align:center; margin-top:16px; border-top: 1px solid var(--color-border); padding-top: 12px; display: flex; align-items: center; justify-content: center; gap: 8px;">
            <a href="/BookShop/auth/pages/Forgetpassword/index.php" style="color: var(--color-text-light); text-decoration: none; font-size: 0.9rem;">Quên mật khẩu?</a>
            <span style="color: var(--color-border);">|</span>
            <a href="/BookShop/trangchu/index.php" style="color: var(--color-primary); text-decoration: none; font-size: 0.9rem; font-weight: bold;">Quay lại trang chủ</a>
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
