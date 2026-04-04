<?php
include 'check_login.php';
include '../config.php';

$id         = $_POST['content_id'];
$name       = $_POST['content_name'];
$type       = $_POST['content_type'];
$path       = $_POST['content_path'];
$desc       = $_POST['content_description'];
$old_image  = $_POST['old_image'];
$image_path = $old_image;

if (!empty($_FILES['content_image']['name']) && $_FILES['content_image']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = "/var/www/html/media/";
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

    $filename    = time() . "_" . basename($_FILES['content_image']['name']);
    $target_file = $upload_dir . $filename;

    if (move_uploaded_file($_FILES['content_image']['tmp_name'], $target_file)) {
        $image_path = "media/" . $filename;
    }
}

$sql = "UPDATE content SET 
            content_name        = '$name',
            content_type        = '$type',
            content_path        = '$path',
            content_description = '$desc',
            content_image       = '$image_path'
        WHERE content_id = $id";
mysqli_query($conn, $sql);

header("Location: content_manage.php");
exit;
