<?php
include '../config.php';
include 'check_login.php';

$place_name        = $_POST['place_name'] ?? '';
$place_description = $_POST['place_description'] ?? '';
$raw_category      = $_POST['category'] ?? '';
$category          = trim(strtolower($raw_category));

if (!in_array($category, ['travel', 'eat'])) {
    die('หมวดหมู่ไม่ถูกต้อง: ' . htmlspecialchars($category));
}
if (empty($place_name) || empty($category)) {
    die('ข้อมูลไม่ครบ');
}

$admin_id = $_SESSION['admin']['id'] ?? null;

$sql = "INSERT INTO place (place_name, place_description, category, admin_id) VALUES (?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "sssi", $place_name, $place_description, $category, $admin_id);
mysqli_stmt_execute($stmt);
$place_id = mysqli_insert_id($conn);

$upload_dir = "/var/www/html/uploads/";
if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

// ===== UPLOAD รูปสถานที่ =====
if (!empty($_FILES['place_images']['name'][0])) {
    foreach ($_FILES['place_images']['tmp_name'] as $key => $tmp_name) {
        if ($_FILES['place_images']['error'][$key] !== UPLOAD_ERR_OK) continue;

        $original_name = basename($_FILES['place_images']['name'][$key]);
        $file_name     = time() . '_' . rand(1000, 9999) . '_' . $original_name;
        $target_file   = $upload_dir . $file_name;

        if (move_uploaded_file($tmp_name, $target_file)) {
            $img_path = "uploads/" . $file_name;
            $sql_img  = "INSERT INTO place_image (place_id, image_path) VALUES (?, ?)";
            $stmt_img = mysqli_prepare($conn, $sql_img);
            mysqli_stmt_bind_param($stmt_img, "is", $place_id, $img_path);
            mysqli_stmt_execute($stmt_img);
        }
    }
}

// ===== UPLOAD QR Code =====
if (!empty($_FILES['model_3d']['name']) && $_FILES['model_3d']['error'] === UPLOAD_ERR_OK) {
    $original_name = basename($_FILES['model_3d']['name']);
    $file_name     = time() . '_qr_' . rand(1000, 9999) . '_' . $original_name;
    $target_file   = $upload_dir . $file_name;

    if (move_uploaded_file($_FILES['model_3d']['tmp_name'], $target_file)) {
        $model_path = "uploads/" . $file_name;
        $sql_model  = "INSERT INTO model_3d (model_3d, place_id) VALUES (?, ?)";
        $stmt_model = mysqli_prepare($conn, $sql_model);
        mysqli_stmt_bind_param($stmt_model, "si", $model_path, $place_id);
        mysqli_stmt_execute($stmt_model);
    }
}

header("Location: place_manage.php");
exit;
