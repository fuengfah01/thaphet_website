<?php
include '../config.php';
include 'check_login.php';

// ===== Cloudinary Config =====
$cloud_name = "dtqdc6au1";
$api_key    = "848722437152954";
$api_secret = "3XUWck6U1OYO2Yx_X8HXfHClarg";

function uploadToCloudinary($tmp_file, $cloud_name, $api_key, $api_secret) {
    $timestamp = time();
    $params = "timestamp=" . $timestamp . $api_secret;
    $signature = sha1($params);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.cloudinary.com/v1_1/{$cloud_name}/image/upload");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, [
        'file'      => new CURLFile($tmp_file),
        'api_key'   => $api_key,
        'timestamp' => $timestamp,
        'signature' => $signature,
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    return $data['secure_url'] ?? null;
}

// ===== รับค่าจากฟอร์ม =====
$place_name        = $_POST['place_name'] ?? '';
$place_description = $_POST['place_description'] ?? '';
$raw_category      = $_POST['category'] ?? '';
$category          = trim(strtolower($raw_category));

// ===== validate category =====
if (!in_array($category, ['travel', 'eat'])) {
    die('หมวดหมู่ไม่ถูกต้อง: ' . htmlspecialchars($category));
}

// ===== ตรวจข้อมูล =====
if (empty($place_name) || empty($category)) {
    die('ข้อมูลไม่ครบ (ชื่อสถานที่ หรือ หมวดหมู่)');
}

// ===== INSERT place =====
$sql = "INSERT INTO place (place_name, place_description, category) VALUES (?, ?, ?)";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "sss", $place_name, $place_description, $category);
mysqli_stmt_execute($stmt);
$place_id = mysqli_insert_id($conn);

// ===== UPLOAD IMAGES (หลายรูป) via Cloudinary =====
if (!empty($_FILES['place_images']['name'][0])) {
    foreach ($_FILES['place_images']['tmp_name'] as $key => $tmp_name) {
        if ($_FILES['place_images']['error'][$key] !== UPLOAD_ERR_OK) continue;

        $url = uploadToCloudinary($tmp_name, $cloud_name, $api_key, $api_secret);
        if ($url) {
            $sql_img = "INSERT INTO place_image (place_id, image_path) VALUES (?, ?)";
            $stmt_img = mysqli_prepare($conn, $sql_img);
            mysqli_stmt_bind_param($stmt_img, "is", $place_id, $url);
            mysqli_stmt_execute($stmt_img);
        }
    }
}

// ===== UPLOAD QR Code via Cloudinary =====
if (!empty($_FILES['model_3d']['name']) && $_FILES['model_3d']['error'] === UPLOAD_ERR_OK) {
    $url = uploadToCloudinary($_FILES['model_3d']['tmp_name'], $cloud_name, $api_key, $api_secret);
    if ($url) {
        $sql_model = "INSERT INTO model_3d (model_3d, place_id) VALUES (?, ?)";
        $stmt_model = mysqli_prepare($conn, $sql_model);
        mysqli_stmt_bind_param($stmt_model, "si", $url, $place_id);
        mysqli_stmt_execute($stmt_model);
    }
}

// ===== กลับไปหน้าจัดการ =====
header("Location: place_manage.php");
exit;
