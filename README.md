# BookShop

BookShop là website bán sách chạy trên PHP và MySQL/MariaDB. Ứng dụng gồm storefront cho khách hàng, quy trình giỏ hàng–đặt hàng–thanh toán và khu vực quản trị cho tài khoản có vai trò `admin`.

## Tính năng hiện có

- Duyệt sản phẩm, danh mục, tìm kiếm và xem chi tiết sản phẩm.
- Giỏ hàng cho khách vãng lai và người dùng đăng nhập; cập nhật số lượng bằng AJAX.
- Đặt hàng với thông tin giao hàng, ghi chú và voucher.
- Thanh toán khi nhận hàng (COD) hoặc qua cổng VNPay; xem kết quả thanh toán.
- Lịch sử đơn hàng, chi tiết đơn hàng và tra cứu/tracking đơn.
- Đánh giá sản phẩm ngay trên trang chi tiết (yêu cầu đăng nhập).
- Đăng ký, đăng nhập bằng username/email, ghi nhớ đăng nhập, Google OAuth, quên mật khẩu bằng OTP và quản lý hồ sơ.
- Admin dashboard và CRUD người dùng, sản phẩm, danh mục, đơn hàng, mã giảm giá; upload ảnh sản phẩm qua Cloudinary.

Các liên kết “Tin tức” và “Review sách” trên header hiện là liên kết trình bày (`#`); project chưa có trang listing riêng cho hai nội dung này.

## Công nghệ

- PHP 7.4+ với mysqli và prepared statements.
- MySQL hoặc MariaDB, charset `utf8mb4`.
- HTML/CSS và JavaScript thuần; helper `url()`/`asset()` dùng cho URL local `/BookShop/`.
- Chart.js, Font Awesome và Toastify được tải từ CDN ở các giao diện sử dụng.
- PHPMailer được vendored tại `vendor/PHPMailer/` cho email OTP.
- Google OAuth 2.0, VNPay (cấu hình sandbox) và Cloudinary signed upload.

## Yêu cầu

- XAMPP (Apache và MySQL/MariaDB).
- PHP 7.4 trở lên với các extension được ứng dụng sử dụng như `mysqli`, `curl`, `openssl`, `json` và `mbstring`.
- Trình duyệt hiện đại bật JavaScript.

## Cài đặt với XAMPP

1. Đặt project tại document root của Apache, mặc định:

   ```text
   C:\xampp\htdocs\BookShop
   ```

2. Sao chép file môi trường mẫu và điền cấu hình cục bộ:

   ```powershell
   cd C:\xampp\htdocs\BookShop
   Copy-Item .env.example .env
   ```

3. Mở XAMPP Control Panel và khởi động Apache, MySQL.
4. Tạo database `bookstore`, sau đó import `database/bookstore.sql` bằng phpMyAdmin hoặc lệnh MySQL tương đương:

   ```text
   mysql -u root bookstore < database/bookstore.sql
   ```

5. Truy cập:

   ```text
   http://localhost/BookShop/
   ```

File SQL cung cấp schema và seed data cho các thực thể người dùng/vai trò, danh mục, sản phẩm/hình ảnh, giỏ hàng, đơn hàng, thanh toán, giao hàng, voucher, khuyến mãi và review.

## Cấu hình `.env`

`config/env.php` nạp `.env`; `config/db.php` sử dụng thông tin database và khởi tạo session. Không commit `.env` và không ghi secret thật vào tài liệu. Các biến được hỗ trợ:

```dotenv
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=bookstore
APP_ENV=local
AUTH_JWT_SECRET=
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=
SMTP_PASSWORD=
SMTP_FROM_EMAIL=
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
CLOUDINARY_CLOUD_NAME=
CLOUDINARY_API_KEY=
CLOUDINARY_API_SECRET=
VNP_TMNCODE=
VNP_HASHSECRET=
VNP_URL=
VNP_RETURNURL=
```

Để gửi OTP thật cần cấu hình SMTP/PHPMailer; Google OAuth, Cloudinary và VNPay chỉ hoạt động khi các biến tương ứng được cung cấp.

## Cấu trúc chính

```text
BookShop/
├── index.php
├── .env.example
├── database/bookstore.sql
├── config/                 # env, DB, auth, Cloudinary, VNPay
├── includes/               # header.php, footer.php
├── assets/                 # CSS, JS, images
├── trangchu/               # storefront và add_review.php
├── cart/                   # cart, checkout, voucher, order, VNPay
├── auth/
│   ├── controller/         # login, register, profile, OTP, Google OAuth
│   ├── helpers/            # JwtHelper.php
│   ├── models/             # Customer, Admin, Guest
│   └── pages/              # login, register, profile, quên mật khẩu
├── admin/                  # dashboard, CRUD và partials dùng chung
└── vendor/PHPMailer/       # thư viện gửi email đã kèm trong project
```

## Xác thực và tài khoản

`auth/pages/login.php` xử lý đăng nhập bằng username/email, remember-me, callback Google và logout. `AuthController` tạo session cho customer/admin; khi bật ghi nhớ, JWT HS256 được lưu trong cookie HttpOnly `auth_token` (30 ngày) và được khôi phục trong request tiếp theo. Cấu hình hiện đặt mốc hết hạn JWT của phiên không ghi nhớ là 24 giờ. Người dùng được chuyển tới checkout nếu trước đó đang có ý định thanh toán, còn lại chuyển theo vai trò.

`auth/pages/register.php` đăng ký tài khoản với kiểm tra username/email/mật khẩu và `password_hash()`. Nhóm `auth/pages/Forgetpassword/` thực hiện gửi OTP 6 chữ số, xác minh trong 30 phút và đặt lại mật khẩu. `auth/pages/profile.php` cho phép cập nhật thông tin và đổi mật khẩu; admin có thêm liên kết tới khu vực quản trị.

## Sản phẩm và danh mục

Các trang `trangchu/index.php`, `category.php`, `search.php` và `detail.php` đọc sản phẩm/danh mục từ database. Trang chi tiết hiển thị tồn kho, hình ảnh, giá, thông tin khuyến mãi và các review hiện có; `trangchu/add_review.php` lưu đánh giá của người dùng đã đăng nhập.

## Review

Review được đọc và hiển thị theo từng sản phẩm trong `trangchu/detail.php`; người dùng đăng nhập gửi review qua `trangchu/add_review.php`. Project hiện không có trang listing review độc lập.

## Giỏ hàng và checkout

`cart/cart.php` hiển thị giỏ; `cart/add.php` và `cart/update.php` xử lý thêm/cập nhật. Giỏ session được đồng bộ với giỏ database khi người dùng đăng nhập. `cart/apply_voucher.php` kiểm tra voucher và lưu trạng thái áp dụng trong session.

`cart/checkout.php` tạo form giao hàng có CSRF token, ghi chú và lựa chọn phương thức. `cart/process_checkout.php` kiểm tra lại giỏ/tồn kho, voucher, tạo order–order_detail–payment–delivery trong transaction. COD tạo đơn theo luồng nội bộ; VNPAY chuyển tới cổng cấu hình trong `config/vnpay.php` và nhận kết quả tại `cart/vnpay_return.php`.

## Đơn hàng

- `cart/history.php`: danh sách đơn của người dùng.
- `cart/detail.php`: chi tiết sản phẩm, tổng tiền, voucher, ghi chú, payment và delivery.
- `cart/tracking.php`: tra cứu trạng thái đơn.
- `cart/success.php`: trang kết quả sau khi tạo đơn.

## Admin

Các trang `admin/index.php`, `users.php`, `products.php`, `categories.php`, `orders.php` và `coupons.php` dùng layout trong `admin/partials.php`, kiểm tra vai trò admin ở `admin/data.php` và dùng CSRF token cho thao tác thay đổi. Quản lý đơn có cập nhật payment/delivery, transaction và hoàn kho theo trạng thái; quản lý sản phẩm hỗ trợ nhiều ảnh và Cloudinary.

## Ghi chú phát triển

- Luôn chạy qua Apache để URL `/BookShop/` và session/cookie hoạt động đúng.
- Giữ `utf8mb4` khi làm việc với dữ liệu tiếng Việt.
- Dùng `url()` và `asset()` thay vì thêm đường dẫn runtime khác.
- Không ghi secret vào source/README, không commit `.env`.
- Khi kiểm thử thao tác tạo đơn, thanh toán hoặc thay đổi dữ liệu, dùng database phát triển và dữ liệu test riêng; không tự ý sửa seed production.
