<?php
include 'check_login.php';
include '../config.php';

$id        = $_POST['content_id'];
$name      = $_POST['content_name'];
$type      = $_POST['content_type'];
$path      = $_POST['content_path'];
$desc      = $_POST['content_description'];
$old_image = $_POST['old_image'];

$image_path = $old_image;

// ถ้ามีการอัปโหลดรูปใหม่
if (!empty($_FILES['content_image']['name']) && $_FILES['content_image']['error'] === UPLOAD_ERR_OK) {
    $new_url = uploadToCloudinary(
        $_FILES['content_image']['tmp_name'],
        $_FILES['content_image']['name']
    );
    if ($new_url) {
        $image_path = $new_url;
    }
}

// ===== UPDATE ฐานข้อมูล =====
$sql = "UPDATE content SET 
            content_name = ?,
            content_type = ?,
            content_path = ?,
            content_description = ?,
            content_image = ?
        WHERE content_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "sssssi", $name, $type, $path, $desc, $image_path, $id);
mysqli_stmt_execute($stmt);

header("Location: content_manage.php");
exit;
