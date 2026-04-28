<?php
include '../config.php';
include 'check_login.php';

$id                = (int)($_POST['place_id'] ?? 0);
$place_name        = $_POST['place_name'] ?? '';
$place_description = $_POST['place_description'] ?? '';
$raw_category      = $_POST['category'] ?? '';
$category          = trim(strtolower($raw_category));

if (!in_array($category, ['travel', 'eat'])) {
    die('หมวดหมู่ไม่ถูกต้อง');
}
if (empty($place_name)) {
    die('ข้อมูลไม่ครบ');
}

// ===== UPDATE place =====
$sql = "UPDATE place SET place_name = ?, category = ?, place_description = ? WHERE place_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "sssi", $place_name, $category, $place_description, $id);
mysqli_stmt_execute($stmt);

// ===== UPLOAD QR Code via Cloudinary =====
if (!empty($_FILES['model_3d']['name']) && $_FILES['model_3d']['error'] === UPLOAD_ERR_OK) {
    $url = uploadToCloudinary($_FILES['model_3d']['tmp_name'], $_FILES['model_3d']['name']);
    if ($url) {
        $check = mysqli_query($conn, "SELECT model_id FROM model_3d WHERE place_id = $id");
        if (mysqli_num_rows($check) > 0) {
            $sql_m = "UPDATE model_3d SET model_3d = ? WHERE place_id = ?";
            $stmt_m = mysqli_prepare($conn, $sql_m);
            mysqli_stmt_bind_param($stmt_m, "si", $url, $id);
            mysqli_stmt_execute($stmt_m);
        } else {
            $sql_m = "INSERT INTO model_3d (model_3d, place_id) VALUES (?, ?)";
            $stmt_m = mysqli_prepare($conn, $sql_m);
            mysqli_stmt_bind_param($stmt_m, "si", $url, $id);
            mysqli_stmt_execute($stmt_m);
        }
    }
}

// ===== UPLOAD รูปเพิ่มเติม via Cloudinary =====
if (!empty($_FILES['place_images']['name'][0])) {
    foreach ($_FILES['place_images']['tmp_name'] as $key => $tmp_name) {
        if ($_FILES['place_images']['error'][$key] !== UPLOAD_ERR_OK) continue;
        $url = uploadToCloudinary($tmp_name, $_FILES['place_images']['name'][$key]);
        if ($url) {
            $sql_img = "INSERT INTO place_image (place_id, image_path) VALUES (?, ?)";
            $stmt_img = mysqli_prepare($conn, $sql_img);
            mysqli_stmt_bind_param($stmt_img, "is", $id, $url);
            mysqli_stmt_execute($stmt_img);
        }
    }
}

header("Location: place_manage.php");
exit;
