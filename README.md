# BookShop

## Yêu cầu

- PHP 7.4+ và Apache/XAMPP
- MySQL hoặc MariaDB
- PHPMailer trong `vendor/PHPMailer/`

## Cấu hình và chạy

1. Sao chép `.env.example` thành `.env` và điền các giá trị môi trường cần thiết.
   Khi triển khai production, đặt `APP_ENV=production`.
2. Import schema và dữ liệu từ `database/bookstore.sql` vào MySQL/MariaDB.
3. Đặt project trong thư mục document root của Apache, ví dụ `C:\xampp\htdocs\BookShop`.
4. Mở `http://localhost/BookShop/`.

Không commit `.env` vì file này chứa credentials và secrets. PHPMailer hiện được require trực tiếp từ `vendor/PHPMailer/`; thư mục này cần được version control cùng project.
