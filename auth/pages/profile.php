<?php
session_start();

require_once '../../config/db.php';
require_once '../../auth/controller/profilecontroller.php';

// Kiểm tra đã login chưa
$sessionUser = $_SESSION['user'] ?? $_SESSION['admin'] ?? null;
if (!$sessionUser) {
    header('Location: /BookShop/auth/pages/login.php');
    exit;
}

if (isset($_SESSION['admin']) && empty($_SESSION['admin_csrf_token'])) {
    $_SESSION['admin_csrf_token'] = bin2hex(random_bytes(32));
}

$pageTitle = 'Hồ sơ cá nhân';

$userId = $sessionUser['id'];
$error = '';
$success = $_SESSION['success'] ?? '';
unset($_SESSION['success']);

// Load user data từ DB
$profileController = new ProfileController();
$userData = $profileController->getUserProfile($userId);

$overviewStats = ['total_orders' => 0, 'active_orders' => 0];
$overviewStmt = $conn->prepare("SELECT COUNT(*) AS total_orders, SUM(CASE WHEN OrderStatus IN ('Pending', 'Processing', 'Shipped') THEN 1 ELSE 0 END) AS active_orders FROM `order` WHERE CustomerID = ?");
if ($overviewStmt) {
    $overviewStmt->bind_param('i', $userId);
    $overviewStmt->execute();
    $overviewStats = array_merge($overviewStats, $overviewStmt->get_result()->fetch_assoc() ?: []);
    $overviewStmt->close();
}

// Nếu không load được data từ DB, dùng data từ session
if (!$userData) {
    $userData = $sessionUser;
}

// Xử lý POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'username' => trim($_POST['username'] ?? ''),
        'full_name' => trim($_POST['full_name'] ?? ''),
        'email' => trim($_POST['email'] ?? ''),
        'phone' => trim($_POST['phone'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
        'current_password' => $_POST['current_password'] ?? '',
        'new_password' => $_POST['new_password'] ?? '',
        'confirm_password' => $_POST['confirm_password'] ?? ''
    ];

    // Kiểm tra xem có đổi mật khẩu không
    if (!empty($data['current_password']) || !empty($data['new_password']) || !empty($data['confirm_password'])) {
        // Có đổi mật khẩu
        $result = $profileController->changePassword(
            $userId,
            $data['current_password'],
            $data['new_password'],
            $data['confirm_password']
        );
    } else {
        // Chỉ cập nhật thông tin cá nhân
        $result = $profileController->updateProfile($userId, $data);
    }

    if ($result['success']) {
        $_SESSION['success'] = $result['message'];
        // Reload user data
        $userData = $profileController->getUserProfile($userId);
        // Update session
        if (isset($_SESSION['user']) && $_SESSION['user']['id'] == $userId) {
            $_SESSION['user'] = array_merge($_SESSION['user'], $userData);
        }
        if (isset($_SESSION['admin']) && $_SESSION['admin']['id'] == $userId) {
            $_SESSION['admin'] = array_merge($_SESSION['admin'], $userData);
        }
        header('Location: /BookShop/auth/pages/profile.php');
        exit;
    } else {
        $error = $result['message'];
    }
}

// Chỉ render HTML sau khi mọi xử lý request và redirect đã hoàn tất.
include '../../includes/header.php';
?>

<style>
    .profile-page {
        padding: var(--spacing-lg) 0 0;
        color: var(--color-text);
        background-color: var(--color-background);
        font-family: "Times New Roman", Times, serif;
    }

    .profile-page .form-control,
    .profile-page .form-control::placeholder {
        font-family: "Times New Roman", Times, serif;
    }

    .profile-page .btn,
    .profile-page .toggle-password {
        font-family: var(--font-family-base);
    }

    .profile-page .form-label {
        font-family: "Times New Roman", Times, serif;
        font-size: 1rem !important;
    }

    .profile-page .form-control { font-size: 1rem !important; }
    .profile-page .profile-nav-item a { font-size: 1rem !important; }
    .profile-page .profile-user-info p,
    .profile-page .profile-user-subtitle { font-size: 1rem; }

    .profile-page-container {
        max-width: 1200px;
        margin: 0 auto var(--spacing-xl);
        padding: 0 var(--spacing-md);
    }

    .profile-page .profile-header {
        display: flex;
        align-items: center;
        gap: var(--spacing-md);
        margin-bottom: var(--spacing-lg);
        padding: var(--spacing-lg) var(--spacing-xl);
        background: var(--color-surface);
        border: var(--border-width) solid var(--color-border);
        border-radius: var(--border-radius-lg);
    }

    .profile-page .profile-header .profile-avatar-container { flex: 0 0 auto; margin: 0; }
    .profile-page .profile-header .profile-user-info h1 { margin: 0 0 4px; color: var(--color-text); font-size: var(--font-size-xl); line-height: 1.3; }
    .profile-page .profile-header .profile-user-info p { margin: 0 0 4px; }
    .profile-page .profile-header .profile-user-subtitle { margin: 0; }

    .profile-page .profile-layout {
        display: grid;
        grid-template-columns: 240px minmax(0, 1fr);
        gap: var(--spacing-lg);
        align-items: start;
    }

    .profile-page .profile-sidebar {
        padding: var(--spacing-md) 0;
        background: transparent;
        box-shadow: none;
        text-align: center;
    }

    .profile-page .profile-avatar-container {
        width: 96px;
        height: 96px;
        margin: 0 auto var(--spacing-lg);
    }

    .profile-page .profile-avatar-large {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 96px;
        height: 96px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
        color: var(--color-surface);
        font-size: var(--font-size-xxl);
        font-weight: var(--font-weight-extra-bold);
        box-shadow: var(--box-shadow-sm);
    }

    .profile-page .profile-user-info h2 {
        margin: 0 0 10px;
        color: var(--color-text);
        font-size: var(--font-size-lg);
        font-weight: var(--font-weight-bold);
        line-height: 1.4;
    }

    .profile-page .profile-user-info p {
        margin: 0 0 10px;
        color: var(--color-text-light);
        font-size: var(--font-size-sm);
        line-height: var(--line-height-base);
        overflow-wrap: anywhere;
    }

    .profile-page .profile-user-subtitle {
        display: block;
        margin-top: 0;
        margin-bottom: var(--spacing-md);
        color: var(--color-primary);
        font-size: var(--font-size-sm);
        font-weight: var(--font-weight-bold);
    }

    .profile-page .profile-nav-menu {
        list-style: none;
        margin: 0;
        padding: var(--spacing-md) 0 0;
        border-top: var(--border-width) solid var(--color-border);
        text-align: left;
    }

    .profile-page .profile-nav-item a {
        display: flex;
        align-items: center;
        gap: var(--spacing-sm);
        margin-bottom: 10px;
        padding: 13px var(--spacing-md);
        color: var(--color-text);
        border-radius: var(--border-radius);
        font-size: var(--font-size-sm);
        font-weight: var(--font-weight-medium);
        line-height: 1.5;
        text-decoration: none;
        transition: color var(--transition-fast), background-color var(--transition-fast);
    }

    .profile-page .profile-nav-item a:hover {
        background-color: rgba(79, 70, 229, 0.08);
        color: var(--color-primary);
    }

    .profile-page .profile-nav-item.active a {
        background-color: var(--color-primary);
        color: var(--color-surface);
    }

    .profile-page .profile-nav-item.logout {
        margin-top: 12px;
        padding-top: 12px;
        border-top: var(--border-width) solid var(--color-border);
    }

    .profile-page .profile-nav-item:last-child a {
        margin-bottom: 0;
    }

    .profile-page .profile-nav-item.logout a {
        color: var(--color-error);
    }

    .profile-page .profile-nav-item.logout a:hover {
        background-color: rgba(239, 68, 68, 0.08);
        color: var(--color-error);
    }

    .profile-page .profile-content-area,
    .profile-page .profile-form {
        min-width: 0;
    }

    .profile-page .profile-form {
        gap: var(--spacing-lg);
    }

    .profile-page .profile-card {
        padding: var(--spacing-lg);
    }

    .profile-page .profile-card-title {
        display: flex;
        align-items: center;
        gap: var(--spacing-sm);
        margin: 0 0 var(--spacing-lg);
        padding-bottom: var(--spacing-sm);
        color: var(--color-text);
        border-bottom: var(--border-width) solid var(--color-border);
        font-size: var(--font-size-lg);
        font-weight: var(--font-weight-bold);
        line-height: 1.4;
    }

    .profile-page .profile-card-title::before {
        content: '';
        display: inline-block;
        width: 4px;
        height: 18px;
        background-color: var(--color-primary);
        border-radius: 2px;
    }

    .profile-page .form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: var(--spacing-md);
    }

    .profile-page .form-group-full {
        grid-column: span 2;
    }

    .profile-page .form-control[readonly] {
        background-color: var(--color-background);
        color: var(--color-text-light);
        cursor: not-allowed;
    }

    .profile-page .password-wrapper {
        position: relative;
    }

    .profile-page .password-wrapper .form-control {
        padding-right: 44px;
    }

    .profile-page .toggle-password {
        position: absolute;
        top: 50%;
        right: var(--spacing-sm);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        padding: 0;
        color: var(--color-text-light);
        background: transparent;
        border: 0;
        border-radius: var(--border-radius-sm);
        cursor: pointer;
        transform: translateY(-50%);
    }

    .profile-page .toggle-password:hover,
    .profile-page .toggle-password:focus-visible {
        color: var(--color-primary);
        background-color: rgba(79, 70, 229, 0.08);
    }

    .profile-page .toggle-password:focus-visible {
        outline: 2px solid var(--color-primary);
        outline-offset: 2px;
    }

    .profile-page .profile-btn-container {
        display: flex;
        justify-content: flex-end;
    }

    .profile-page .profile-alert {
        margin-bottom: var(--spacing-lg);
    }

    .profile-page #profile-save-actions[hidden] { display: none !important; }

    .profile-page [data-profile-section][hidden] { display: none; }
    .profile-page .profile-overview-greeting { margin: -8px 0 var(--spacing-lg); color: var(--color-text-light); }
    .profile-page .profile-overview-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: var(--spacing-md); margin-bottom: var(--spacing-lg); }
    .profile-page .profile-summary-card { display: grid; grid-template-columns: auto 1fr; column-gap: 12px; align-items: center; padding: var(--spacing-md); background: var(--color-background); border: 1px solid var(--color-border); border-radius: var(--border-radius); }
    .profile-page .profile-summary-card i { grid-row: span 2; color: var(--color-primary); font-size: 1.35rem; }
    .profile-page .profile-summary-card strong { color: var(--color-text); font-size: var(--font-size-xl); line-height: 1; }
    .profile-page .profile-summary-card span { color: var(--color-text-light); font-size: var(--font-size-sm); }
    .profile-page .profile-quick-actions { display: flex; flex-wrap: wrap; align-items: center; gap: var(--spacing-sm); }
    .profile-page .profile-quick-actions .btn { display: inline-flex; align-items: center; gap: 8px; }
    .profile-page .profile-support-note { display: flex; align-items: center; gap: 10px; flex: 1 1 220px; min-width: 200px; padding: 10px 12px; color: var(--color-text-light); background: rgba(0,169,242,.06); border-radius: var(--border-radius); }
    .profile-page .profile-support-note > i { color: var(--color-primary); font-size: 1.15rem; }
    .profile-page .profile-support-note span { display: grid; gap: 2px; font-size: var(--font-size-sm); }
    .profile-page .profile-support-note small { font-size: .78rem; }

    @media (max-width: 900px) {
        .profile-page .profile-layout {
            grid-template-columns: 1fr;
        }

        .profile-page .profile-sidebar {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .profile-page .profile-header { padding: var(--spacing-md); }

        .profile-page .profile-nav-menu {
            width: 100%;
            display: flex;
            flex-wrap: wrap;
            gap: var(--spacing-sm);
            justify-content: center;
        }

        .profile-page .profile-nav-item a {
            margin-bottom: 0;
        }

        .profile-page .profile-nav-item.logout {
            margin-top: 0;
            padding-top: 0;
            border-top: none;
        }
    }

    @media (max-width: 640px) {
        .profile-page {
            padding-top: var(--spacing-md);
        }

        .profile-page .form-grid {
            grid-template-columns: 1fr;
        }

        .profile-page .form-group-full {
            grid-column: auto;
        }

        .profile-page .profile-sidebar,
        .profile-page .profile-header,
        .profile-page .profile-card {
            padding: var(--spacing-md);
        }

        .profile-page .profile-nav-menu {
            display: block;
        }

        .profile-page .profile-nav-item.logout {
            margin-top: 10px;
            padding-top: 10px;
            border-top: var(--border-width) solid var(--color-border);
        }

        .profile-page .profile-nav-item a {
            margin-bottom: var(--spacing-sm);
        }

        .profile-page .profile-nav-item:last-child a {
            margin-bottom: 0;
        }

        .profile-page .profile-btn-container .btn {
            width: 100%;
        }
    }
</style>

<main class="profile-page">
    <div class="profile-page-container">

        <?php if ($error): ?>
            <div class="alert alert--error profile-alert">
                <svg class="alert__icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"></circle>
                    <line x1="12" y1="8" x2="12" y2="12"></line>
                    <line x1="12" y1="16" x2="12.01" y2="16"></line>
                </svg>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert--success profile-alert">
                <svg class="alert__icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <header class="profile-header">
            <div class="profile-avatar-container">
                <div class="profile-avatar-large">
                    <?php echo strtoupper(substr($userData['full_name'] ?? 'U', 0, 1)); ?>
                </div>
            </div>
            <div class="profile-user-info">
                <h1><?php echo htmlspecialchars($userData['full_name'] ?? 'User'); ?></h1>
                <p><?php echo htmlspecialchars($userData['email'] ?? ''); ?></p>
                <span class="profile-user-subtitle">Tài khoản của tôi</span>
            </div>
        </header>

        <div class="profile-layout">

            <!-- Left Sidebar -->
            <aside class="card profile-sidebar">
                <ul class="profile-nav-menu">
                    <li class="profile-nav-item active" data-profile-tab="overview">
                        <a href="#profile-overview"><i class="fa-solid fa-chart-pie" aria-hidden="true"></i> Tổng quan</a>
                    </li>
                    <li class="profile-nav-item" data-profile-tab="personal">
                        <a href="#profile-personal">
                            <i class="fa-solid fa-user" aria-hidden="true"></i>
                            Thông tin cá nhân
                        </a>
                    </li>
                    <li class="profile-nav-item">
                        <a href="/BookShop/cart/history.php">
                            <i class="fa-solid fa-box" aria-hidden="true"></i>
                            Đơn hàng của tôi
                        </a>
                    </li>
                    <li class="profile-nav-item" data-profile-tab="password">
                        <a href="#profile-password"><i class="fa-solid fa-lock" aria-hidden="true"></i> Đổi mật khẩu</a>
                    </li>
                    <?php if (strtolower($sessionUser['role'] ?? '') === 'admin'): ?>
                    <li class="profile-nav-item">
                        <a href="/BookShop/admin/index.php">
                            <i class="fa-solid fa-gauge-high" aria-hidden="true"></i>
                            Trang quản trị (Admin)
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="profile-nav-item logout">
                        <a href="#"
                            onclick="event.preventDefault(); document.getElementById('logout-form-profile').submit();">
                            <i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i>
                            Đăng xuất
                        </a>
                    </li>
                </ul>

                <form id="logout-form-profile" action="/BookShop/auth/pages/login.php" method="POST"
                    style="display: none;">
                    <input type="hidden" name="action" value="logout">
                    <input type="hidden" name="type" value="<?php echo isset($_SESSION['user']) ? 'user' : 'admin'; ?>">
                    <?php if (isset($_SESSION['admin'])): ?>
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['admin_csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                    <?php endif; ?>
                </form>
            </aside>

            <!-- Right Content Area -->
            <div class="profile-content-area">

                <section class="card profile-card profile-overview" id="profile-overview" data-profile-section="overview">
                    <h2 class="profile-card-title">Tổng quan</h2>
                    <p class="profile-overview-greeting">Xin chào, <strong><?php echo htmlspecialchars($userData['full_name'] ?? 'bạn'); ?></strong>!</p>
                    <div class="profile-overview-grid">
                        <div class="profile-summary-card"><i class="fa-solid fa-box" aria-hidden="true"></i><strong><?php echo (int) ($overviewStats['total_orders'] ?? 0); ?></strong><span>Tổng số đơn hàng</span></div>
                        <div class="profile-summary-card"><i class="fa-solid fa-truck-fast" aria-hidden="true"></i><strong><?php echo (int) ($overviewStats['active_orders'] ?? 0); ?></strong><span>Đơn đang xử lý / giao</span></div>
                    </div>
                    <div class="profile-quick-actions">
                        <a class="btn btn--primary" href="/BookShop/cart/history.php"><i class="fa-solid fa-receipt" aria-hidden="true"></i> Xem đơn hàng</a>
                        <a class="btn btn--outline" href="/BookShop/trangchu/index.php"><i class="fa-solid fa-book-open" aria-hidden="true"></i> Tiếp tục mua sắm</a>
                        <div class="profile-support-note"><i class="fa-solid fa-circle-question" aria-hidden="true"></i><span><strong>Hỗ trợ BookShop</strong><small>Cần trợ giúp? Hãy liên hệ đội ngũ hỗ trợ của chúng tôi.</small></span></div>
                    </div>
                </section>

                <form action="/BookShop/auth/pages/profile.php" method="POST" class="form profile-form">

                    <!-- Personal Info Card -->
                    <section class="card profile-card" id="profile-personal" data-profile-section="personal" hidden>
                        <h2 class="profile-card-title">Thông tin cá nhân</h2>

                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label" for="username">Tên đăng nhập (Email)</label>
                                <input type="text" id="username" name="username" class="form-control"
                                    value="<?php echo htmlspecialchars($userData['username'] ?? ''); ?>" required
                                    readonly>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="full_name">Họ và tên</label>
                                <input type="text" id="full_name" name="full_name" class="form-control"
                                    value="<?php echo htmlspecialchars($userData['full_name'] ?? ''); ?>" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="email">Email liên hệ</label>
                                <input type="email" id="email" name="email" class="form-control"
                                    value="<?php echo htmlspecialchars($userData['email'] ?? ''); ?>" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="phone">Số điện thoại</label>
                                <input type="tel" id="phone" name="phone" class="form-control"
                                    value="<?php echo htmlspecialchars($userData['phone'] ?? ''); ?>">
                            </div>

                            <div class="form-group form-group-full">
                                <label class="form-label" for="address">Địa chỉ giao hàng</label>
                                <textarea id="address" name="address" class="form-control"
                                    placeholder="Số nhà, tên đường, phường/xã, quận/huyện, tỉnh/thành phố"><?php echo htmlspecialchars($userData['address'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </section>

                    <!-- Change Password Card -->
                    <section class="card profile-card" id="profile-password" data-profile-section="password" hidden>
                        <h2 class="profile-card-title">Đổi mật khẩu</h2>

                        <div class="form-grid">
                            <div class="form-group form-group-full">
                                <label class="form-label" for="current_password">Mật khẩu hiện tại</label>
                                <div class="password-wrapper">
                                    <input type="password" id="current_password" name="current_password"
                                        class="form-control" autocomplete="current-password"
                                        placeholder="Nhập mật khẩu hiện tại nếu muốn đổi mật khẩu">
                                    <button type="button" class="toggle-password" aria-label="Hiện mật khẩu">
                                        <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="new_password">Mật khẩu mới</label>
                                <div class="password-wrapper">
                                    <input type="password" id="new_password" name="new_password" class="form-control"
                                        autocomplete="new-password" placeholder="Tối thiểu 8 ký tự">
                                    <button type="button" class="toggle-password" aria-label="Hiện mật khẩu">
                                        <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="confirm_password">Xác nhận mật khẩu mới</label>
                                <div class="password-wrapper">
                                    <input type="password" id="confirm_password" name="confirm_password"
                                        class="form-control" autocomplete="new-password"
                                        placeholder="Nhập lại mật khẩu mới">
                                    <button type="button" class="toggle-password" aria-label="Hiện mật khẩu">
                                        <i class="fa-solid fa-eye" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </section>

                    <div class="profile-btn-container" id="profile-save-actions" hidden>
                        <button type="submit" class="btn btn--primary">Lưu thay đổi</button>
                    </div>

                </form>

            </div>

        </div>
    </div>
</main>

    <script>
    document.querySelectorAll('.profile-page .toggle-password').forEach(button => {
        button.addEventListener('click', function() {
            const input = this.previousElementSibling;
            const icon = this.querySelector('i');
            if (input.type === 'password') {
                input.type = 'text';
                this.setAttribute('aria-label', 'Ẩn mật khẩu');
                icon.className = 'fa-solid fa-eye-slash';
            } else {
                input.type = 'password';
                this.setAttribute('aria-label', 'Hiện mật khẩu');
                icon.className = 'fa-solid fa-eye';
            }
        });
    });

    const profileSections = document.querySelectorAll('[data-profile-section]');
    const profileTabs = document.querySelectorAll('[data-profile-tab]');
    profileTabs.forEach(tab => tab.querySelector('a')?.addEventListener('click', event => {
        event.preventDefault();
        const target = tab.dataset.profileTab;
        profileSections.forEach(section => { section.hidden = section.dataset.profileSection !== target; });
        profileTabs.forEach(item => item.classList.toggle('active', item === tab));
        const saveActions = document.getElementById('profile-save-actions');
        if (saveActions) saveActions.hidden = target === 'overview';
    }));
    </script>
<?php include '../../includes/footer.php'; ?>
