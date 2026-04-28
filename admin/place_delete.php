<?php
include '../config.php';
include 'check_login.php';

$id = (int)($_GET['id'] ?? 0);

if ($id) {
    // ลบรูปจาก DB ก่อน
    mysqli_query($conn, "DELETE FROM place_image WHERE place_id = $id");
    
    // ลบ model_3d ที่ผูกกับ place นี้
    mysqli_query($conn, "DELETE FROM model_3d WHERE place_id = $id");
    
    // แล้วค่อยลบ place
    mysqli_query($conn, "DELETE FROM place WHERE place_id = $id");
}

header("Location: place_manage.php");
exit;
