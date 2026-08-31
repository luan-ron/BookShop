<?php

require_once __DIR__ . '/env.php';

/**
 * Cấu hình xác thực JWT và remember-me.
 * Đổi AUTH_JWT_SECRET khi triển khai production.
 */
if (!defined('AUTH_JWT_SECRET')) {
    define('AUTH_JWT_SECRET', env('AUTH_JWT_SECRET', ''));
}

if (!defined('AUTH_COOKIE_NAME')) {
    define('AUTH_COOKIE_NAME', 'auth_token');
}

if (!defined('AUTH_COOKIE_PATH')) {
    define('AUTH_COOKIE_PATH', '/BookShop/');
}

if (!defined('AUTH_REMEMBER_DAYS')) {
    define('AUTH_REMEMBER_DAYS', 30);
}

if (!defined('AUTH_SESSION_HOURS')) {
    define('AUTH_SESSION_HOURS', 24);
}
