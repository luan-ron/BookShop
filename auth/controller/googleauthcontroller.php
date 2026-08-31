<?php

require_once __DIR__ . '/../../config/env.php';

define('GOOGLE_CLIENT_ID', env('GOOGLE_CLIENT_ID', ''));
define('GOOGLE_CLIENT_SECRET', env('GOOGLE_CLIENT_SECRET', ''));
define('GOOGLE_REDIRECT_URI', 'http://localhost/BookShop/auth/pages/login.php');

class googleauthcontroller
{
    // Tạo URL redirect sang Google
    public static function getLoginUrl(): string
    {
        $params = http_build_query([
            'client_id' => GOOGLE_CLIENT_ID,
            'redirect_uri' => GOOGLE_REDIRECT_URI,
            'response_type' => 'code',
            'scope' => 'email profile',
            'access_type' => 'online',
        ]);
        return 'https://accounts.google.com/o/oauth2/auth?' . $params;
    }

    // Xử lý sau khi Google redirect về
    public static function handleCallback(): ?array
    {
        if (empty($_GET['code'])) {
            return null;
        }

        // Đổi code lấy access token
        $token = self::getAccessToken($_GET['code']);
        if (empty($token['access_token'])) {
            return null;
        }

        // Lấy thông tin user
        $user = self::getUserInfo($token['access_token']);
        if (empty($user['email'])) {
            return null;
        }

        return self::saveOrUpdateUser($user, $token['access_token']);
    }

    // Lưu hoặc cập nhật user đăng nhập bằng Google vào database
    private static function saveOrUpdateUser(array $userData, string $accessToken): ?array
    {
        require_once __DIR__ . '/../../config/db.php';
        $conn = $conn ?? $GLOBALS['conn'] ?? null;

        $googleId = $userData['id'];
        $name = $userData['name'] ?? ('User ' . $googleId);
        $email = $userData['email'] ?? '';

        // 1. Kiểm tra xem tài khoản đã được liên kết trong user_provider chưa
        $checkSql = "SELECT User_ID FROM user_provider WHERE ProviderName = 'Google' AND Provider_userID = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param('s', $googleId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $userId = $row['User_ID'];
            
            // Cập nhật access token mới
            $updateTokenSql = "UPDATE user_provider SET access_token = ? WHERE User_ID = ? AND ProviderName = 'Google'";
            $updateTokenStmt = $conn->prepare($updateTokenSql);
            $updateTokenStmt->bind_param('si', $accessToken, $userId);
            $updateTokenStmt->execute();
            $updateTokenStmt->close();

            // Lấy thông tin người dùng từ bảng user
            $userSql = "SELECT CustomerID, LastName, FirstName, Email, Phone, Address, RoleID, CreatedDate FROM user WHERE CustomerID = ?";
            $userStmt = $conn->prepare($userSql);
            $userStmt->bind_param('i', $userId);
            $userStmt->execute();
            $userResult = $userStmt->get_result();
            if ($userResult->num_rows > 0) {
                $userRow = $userResult->fetch_assoc();
                $checkStmt->close();
                $userStmt->close();
                
                return [
                    'id' => (int)$userRow['CustomerID'],
                    'username' => $userRow['Email'],
                    'email' => $userRow['Email'],
                    'full_name' => trim($userRow['LastName'] . ' ' . $userRow['FirstName']),
                    'phone' => $userRow['Phone'] ?? '',
                    'address' => $userRow['Address'] ?? '',
                    'role' => (int)($userRow['RoleID'] ?? 2) === 1 ? 'admin' : 'customer',
                    'role_id' => (int)($userRow['RoleID'] ?? 2),
                    'login_type' => 'google'
                ];
            }
            $userStmt->close();
        }
        $checkStmt->close();

        // 2. Nếu chưa liên kết, kiểm tra xem đã có user nào trùng email trong bảng user chưa
        $userId = null;
        if (!empty($email)) {
            $userSql = "SELECT CustomerID FROM user WHERE Email = ? LIMIT 1";
            $userStmt = $conn->prepare($userSql);
            $userStmt->bind_param('s', $email);
            $userStmt->execute();
            $userResult = $userStmt->get_result();
            if ($userResult->num_rows > 0) {
                $userRow = $userResult->fetch_assoc();
                $userId = $userRow['CustomerID'];
            }
            $userStmt->close();
        }

        // 3. Nếu chưa có user trong bảng user, tạo mới
        if (!$userId) {
            $parts = explode(' ', $name);
            if (count($parts) > 1) {
                $firstName = array_pop($parts);
                $lastName = implode(' ', $parts);
            } else {
                $firstName = $name;
                $lastName = '';
            }

            $insertSql = "INSERT INTO user (LastName, FirstName, Email, Password, RoleID, CreatedDate) VALUES (?, ?, ?, '', 2, NOW())";
            $insertStmt = $conn->prepare($insertSql);
            $insertStmt->bind_param('sss', $lastName, $firstName, $email);
            $insertStmt->execute();
            $userId = $insertStmt->insert_id;
            $insertStmt->close();
        }

        // 4. Lưu liên kết vào bảng user_provider
        $linkSql = "INSERT INTO user_provider (User_ID, ProviderName, Provider_userID, access_token, CreatedAt) VALUES (?, 'Google', ?, ?, NOW())";
        $linkStmt = $conn->prepare($linkSql);
        $linkStmt->bind_param('iss', $userId, $googleId, $accessToken);
        $linkStmt->execute();
        $linkStmt->close();

        // Lấy thông tin user cuối cùng để trả về
        $userSql = "SELECT CustomerID, LastName, FirstName, Email, Phone, Address, RoleID, CreatedDate FROM user WHERE CustomerID = ?";
        $userStmt = $conn->prepare($userSql);
        $userStmt->bind_param('i', $userId);
        $userStmt->execute();
        $userResult = $userStmt->get_result();
        $userRow = $userResult->fetch_assoc();
        $userStmt->close();

        return [
            'id' => (int)$userRow['CustomerID'],
            'username' => $userRow['Email'],
            'email' => $userRow['Email'],
            'full_name' => trim($userRow['LastName'] . ' ' . $userRow['FirstName']),
            'phone' => $userRow['Phone'] ?? '',
            'address' => $userRow['Address'] ?? '',
            'role' => (int)($userRow['RoleID'] ?? 2) === 1 ? 'admin' : 'customer',
            'role_id' => (int)($userRow['RoleID'] ?? 2),
            'login_type' => 'google'
        ];
    }

    // --- Private helpers ---

    private static function getAccessToken(string $code): array
    {
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'code' => $code,
                'client_id' => GOOGLE_CLIENT_ID,
                'client_secret' => GOOGLE_CLIENT_SECRET,
                'redirect_uri' => GOOGLE_REDIRECT_URI,
                'grant_type' => 'authorization_code',
            ]),
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true) ?? [];
    }

    private static function getUserInfo(string $access_token): array
    {
        $ch = curl_init('https://www.googleapis.com/oauth2/v2/userinfo');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $access_token],
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        return json_decode($response, true) ?? [];
    }
}