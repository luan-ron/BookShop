<?php
/**
 * Cấu hình kết nối VNPAY Sandbox
 * Các giá trị được lấy từ tệp .env ở thư mục gốc của dự án.
 */

require_once __DIR__ . '/env.php';

define('VNP_TMNCODE', env('VNP_TMNCODE'));       // Mã định danh website của bạn tại hệ thống VNPAY
define('VNP_HASHSECRET', env('VNP_HASHSECRET')); // Chuỗi bí mật dùng để tạo mã kiểm tra checksum
define('VNP_URL', env('VNP_URL')); // URL thanh toán VNPAY Sandbox
define('VNP_RETURNURL', env('VNP_RETURNURL')); // URL nhận kết quả trả về sau khi thanh toán
?>
