<?php
include '../config.php';
include 'check_login.php';

$image_id = $_GET['id'];
$place_id = $_GET['place_id'];

// ดึง path ไฟล์
$row = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT image_path FROM place_image WHERE image_id = $image_id
"));

if (!empty($row['image_path'])) {
    $file = '../' . $row['image_path'];
    if (file_exists($file)) {
        unlink($file); // ลบไฟล์จริง
    }
}

// ลบจาก DB
mysqli_query($conn, "
    DELETE FROM place_image WHERE image_id = $image_id
");

header("Location: place_edit.php?id=$place_id");
exit;
