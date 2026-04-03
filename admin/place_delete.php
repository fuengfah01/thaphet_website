<?php
include '../config.php';
include 'check_login.php';

$id = $_GET['id'] ?? 0;

if ($id) {

    // 1) ดึง path รูป เพื่อจะลบไฟล์จริง
    $res = mysqli_query($conn, "SELECT image_path FROM place_image WHERE place_id = $id");

    while ($row = mysqli_fetch_assoc($res)) {
        $image_path = "assets/image/" . $filename;
        if (file_exists($file)) {
            unlink($file); // ลบไฟล์ออกจาก server
        }
    }

    // 2) ลบรูปจาก DB ก่อน
    mysqli_query($conn, "DELETE FROM place_image WHERE place_id = $id");

    // 3) แล้วค่อยลบ place
    mysqli_query($conn, "DELETE FROM place WHERE place_id = $id");
}

header("Location: place_manage.php");
exit;
