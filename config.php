<?php
$host = "sql7.freesqldatabase.com";
$user = "sql7824635";
$pass = "iUz24J2d6E";
$db   = "sql7824635";
$port = 3306;
$conn = mysqli_connect($host, $user, $pass, $db, $port);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8");

// Cloudinary Config
define('CLOUDINARY_CLOUD_NAME', 'dtqdc6au1');
define('CLOUDINARY_API_KEY', '848722437152954');
define('CLOUDINARY_API_SECRET', '3XUWck6U1OYO2Yx_X8HXfHClarg');

if (!function_exists('uploadToCloudinary')) {
    function uploadToCloudinary($file_tmp, $file_name) {
        $timestamp = time();
        $params = "folder=thaphet&timestamp=" . $timestamp . CLOUDINARY_API_SECRET;
        $signature = sha1($params);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://api.cloudinary.com/v1_1/" . CLOUDINARY_CLOUD_NAME . "/image/upload");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            'file'      => new CURLFile($file_tmp, mime_content_type($file_tmp), $file_name),
            'api_key'   => CLOUDINARY_API_KEY,
            'timestamp' => $timestamp,
            'signature' => $signature,
            'folder'    => 'thaphet'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($response, true);
        return $result['secure_url'] ?? null;
    }
}
