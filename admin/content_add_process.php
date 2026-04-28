<?php
include '../config.php';
include 'check_login.php';

$content_name        = $_POST['content_name'] ?? '';
$content_type        = $_POST['content_type'] ?? '';
$content_path        = $_POST['content_path'] ?? '';
$content_description = $_POST['content_description'] ?? '';

// ===== UPLOAD IMAGE via Cloudinary =====
$image_path = null;
if (!empty($_FILES['content_image']['name']) && $_FILES['content_image']['error'] === UPLOAD_ERR_OK) {
    $image_path = uploadToCloudinary(
        $_FILES['content_image']['tmp_name'],
        $_FILES['content_image']['name']
    );
}

// ===== INSERT =====
$sql = "INSERT INTO content 
        (content_name, content_type, content_image, content_path, content_description)
        VALUES (?, ?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param(
    $stmt,
    "sssss",
    $content_name,
    $content_type,
    $image_path,
    $content_path,
    $content_description
);
mysqli_stmt_execute($stmt);

header("Location: content_manage.php");
exit;
