<?php
require_once __DIR__ . '/env.php';

/**
 * CloudinaryHelper - Hỗ trợ upload ảnh lên Cloudinary không qua thư viện bên thứ ba
 */
class CloudinaryHelper
{
    /**
     * Upload ảnh lên Cloudinary sử dụng API ký chữ ký (Signed Upload)
     * 
     * @param string $fileTmpPath Đường dẫn tạm thời của file ảnh (ví dụ: $_FILES['product_image']['tmp_name'])
     * @return string|null Trả về URL bảo mật (secure_url) của ảnh, hoặc null nếu lỗi
     */
    public static function uploadImage(string $fileTmpPath): ?string
    {
        $cloudName = env('CLOUDINARY_CLOUD_NAME');
        $apiKey = env('CLOUDINARY_API_KEY');
        $apiSecret = env('CLOUDINARY_API_SECRET');

        // Kiểm tra xem đã thiết lập cấu hình chưa
        if (empty($cloudName) || empty($apiKey) || empty($apiSecret) || 
            $cloudName === 'your_cloud_name' || $apiKey === 'your_api_key' || $apiSecret === 'your_api_secret') {
            error_log("Cloudinary configuration has not been set properly in .env file.");
            return null;
        }

        $timestamp = time();

        // Chuỗi ký chữ ký: các tham số xếp theo bảng chữ cái A-Z ghép với nhau và nối thêm API Secret
        // Vì ở đây ta chỉ cần tham số timestamp để kiểm chứng tính toàn vẹn của request:
        $signatureStr = "timestamp=" . $timestamp . $apiSecret;
        $signature = sha1($signatureStr);

        $url = "https://api.cloudinary.com/v1_1/{$cloudName}/image/upload";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        // Chuẩn bị file bằng CURLFile đối với các phiên bản PHP >= 5.5
        $cfile = new CURLFile($fileTmpPath);

        $postFields = [
            'file' => $cfile,
            'api_key' => $apiKey,
            'timestamp' => $timestamp,
            'signature' => $signature
        ];

        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);

        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            error_log("Cloudinary cURL Error: " . $error_msg);
            curl_close($ch);
            return null;
        }

        curl_close($ch);

        $responseData = json_decode($response, true);
        
        if (isset($responseData['secure_url'])) {
            return $responseData['secure_url'];
        } else {
            $errorMessage = $responseData['error']['message'] ?? 'Lỗi không rõ nguyên nhân';
            error_log("Cloudinary Upload Response Error: " . $errorMessage);
            return null;
        }
    }
}
