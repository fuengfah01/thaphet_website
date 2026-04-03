<?php
include '../config.php';
include 'check_login.php';

$content_name = $_POST['content_name'] ?? '';
$content_type = $_POST['content_type'] ?? '';
$content_path = $_POST['content_path'] ?? '';
$content_description = $_POST['content_description'] ?? '';

$admin_id = $_SESSION['admin']['id'] ?? null;

// ===== UPLOAD IMAGE =====
$image_path = null;

if (!empty($_FILES['content_image']['name'])) {

    $upload_dir = "../uploads/content/";

    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $file_name = time() . '_' . basename($_FILES['content_image']['name']);
    $target_file = $upload_dir . $file_name;

    if (move_uploaded_file($_FILES['content_image']['tmp_name'], $target_file)) {
        $image_path = "uploads/content/" . $file_name;
    }
}

// ===== INSERT =====
$sql = "INSERT INTO content 
(content_name, content_type, content_image, content_path, content_description, admin_id)
VALUES (?, ?, ?, ?, ?, ?)";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param(
    $stmt,
    "sssssi",
    $content_name,
    $content_type,
    $image_path,
    $content_path,
    $content_description,
    $admin_id
);

mysqli_stmt_execute($stmt);

header("Location: content_manage.php");
exit;
