# Module xác thực BookShop

Tài liệu này mô tả luồng xác thực đang có trong `BookShop/auth/`. Các controller dùng mysqli/prepared statements thông qua `config/db.php`; mật khẩu được lưu bằng `password_hash()` và kiểm tra bằng `password_verify()`.

## Cấu trúc module

```text
auth/
├── controller/
│   ├── authcontroller.php
│   ├── registercontroller.php
│   ├── forgetpasswordcontroller.php
│   ├── googleauthcontroller.php
│   └── profilecontroller.php
├── helpers/JwtHelper.php
├── models/
│   ├── Customer.php
│   ├── Admin.php
│   └── Guest.php
├── pages/
│   ├── login.php
│   ├── register.php
│   ├── profile.php
│   └── Forgetpassword/
│       ├── index.php
│       ├── verifyotp.php
│       └── reset.php
└── README.md
```

## Đăng ký

`auth/pages/register.php` gọi `RegisterController::register()` với username, email, password và confirm password. Username dài 3–50 ký tự, chỉ gồm chữ/số/gạch dưới; email phải hợp lệ; mật khẩu dài 8–255 ký tự và hai trường mật khẩu phải trùng nhau. Controller kiểm tra username/email trùng, hash mật khẩu rồi tạo user với vai trò customer.

## Đăng nhập, session và remember-me

`auth/pages/login.php` nhận username hoặc email cùng mật khẩu. `AuthController::login()` tìm user qua `Customer::findByIdentity()` và kiểm tra hash. Sau đó `establishSession()`:

- lưu user customer trong `$_SESSION['user']`, admin trong `$_SESSION['admin']`;
- tạo JWT HS256 có `sub`, `jti`, `role`, `iat`, `exp`, `rm` bằng `auth/helpers/JwtHelper.php`;
- với remember-me, đặt cookie HttpOnly, SameSite=Lax tên `auth_token`, thời hạn 30 ngày;
- không bật remember-me thì chỉ dùng session, đặt mốc hết hạn JWT tương ứng 24 giờ và xóa cookie cũ.

`config/db.php` gọi `AuthController::tryRestoreSession()` một lần mỗi request để khôi phục session từ JWT còn hạn. Cookie dùng path `/BookShop/` và bật cờ `secure` khi request chạy HTTPS.

## Redirect và logout

Admin được chuyển tới `/BookShop/admin/index.php`, customer tới `/BookShop/index.php`. Nếu session `redirect_after_login` trỏ tới `/BookShop/cart/checkout.php`, người dùng được trả lại checkout sau khi đăng nhập.

Logout được gửi bằng POST tới `auth/pages/login.php` với `action=logout` và `type=user` hoặc `admin`. `AuthController::logout()` xóa session phù hợp và cookie `auth_token`; admin logout quay về trang login, customer quay về trang chủ.

## Google OAuth

`auth/controller/googleauthcontroller.php` tạo URL OAuth và xử lý callback `code` tại `auth/pages/login.php`. Luồng dùng Google scopes `email profile`, trao đổi code qua HTTPS, lấy thông tin user rồi liên kết/tạo bản ghi trong `user_provider` và `user`. Cần cấu hình `GOOGLE_CLIENT_ID` và `GOOGLE_CLIENT_SECRET`; không ghi giá trị secret vào source hoặc tài liệu.

## Quên mật khẩu và OTP

Các bước nằm trong thư mục có tên đúng hoa thường `auth/pages/Forgetpassword/`:

1. `index.php` kiểm tra email, tạo OTP 6 chữ số, lưu `ResetToken`/`ResetTokenExpires` và gửi qua PHPMailer.
2. `verifyotp.php` ghép sáu ô OTP, gọi `ForgetPasswordController::verifyOTP()` và lưu email đã xác minh vào session.
3. `reset.php` gọi `resetPassword()`, hash mật khẩu mới, xóa token/session reset và chuyển về login.

OTP có hiệu lực 30 phút; gửi lại dùng `ForgetPasswordController::resendOTP()`. Các biến SMTP (`SMTP_HOST`, `SMTP_PORT`, `SMTP_USERNAME`, `SMTP_PASSWORD`, `SMTP_FROM_EMAIL`) được đọc từ `.env`. PHPMailer được kèm tại `vendor/PHPMailer/src/` và hiện được require trực tiếp bởi trang gửi OTP.

## Hồ sơ và đổi mật khẩu

`auth/pages/profile.php` yêu cầu session user/admin và dùng `ProfileController`:

- `getUserProfile()` đọc thông tin hiện tại;
- `updateProfile()` kiểm tra email, họ tên, số điện thoại và cập nhật dữ liệu;
- `changePassword()` yêu cầu mật khẩu hiện tại, kiểm tra mật khẩu mới (8–255 ký tự), hash rồi cập nhật.

Form/field contract, password visibility toggle và redirect hiện tại được giữ ở page này.

## Vai trò và CSRF

`Customer` ánh xạ `RoleID = 1` thành `admin`, `RoleID = 2` thành `customer`; `Admin` chỉ truy vấn user có `RoleID = 1`. `admin/data.php` chặn người chưa có `$_SESSION['admin']` hoặc không có role admin và tạo/kiểm tra `admin_csrf_token` cho các form quản trị. Admin logout từ login và form admin profile cũng kiểm tra token này.

Các form login, register và quên mật khẩu hiện không có token CSRF riêng; checkout có token riêng trong `cart/checkout.php`/`cart/process_checkout.php`. Đây là mô tả trạng thái code hiện tại, không phải cam kết về một lớp CSRF chung cho toàn bộ auth.

## Cấu hình cần thiết

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
```

`AUTH_JWT_SECRET` phải là giá trị bí mật riêng của môi trường chạy. SMTP và Google OAuth có thể để trống khi không dùng các luồng tương ứng; không đưa giá trị thật vào README.

## Kiểm tra thủ công không tạo dữ liệu

Các URL hiện có:

```text
http://localhost/BookShop/auth/pages/login.php
http://localhost/BookShop/auth/pages/register.php
http://localhost/BookShop/auth/pages/profile.php
http://localhost/BookShop/auth/pages/Forgetpassword/index.php
```

Khi kiểm thử đăng ký, đổi mật khẩu hoặc reset OTP, dùng tài khoản/database phát triển riêng. Không chạy thao tác tạo user hay thay đổi mật khẩu trên dữ liệu production chỉ để kiểm tra giao diện.
