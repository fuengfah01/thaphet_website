<?php
include '../config.php';
include 'check_login.php';

$image_id = $_GET['id'];
$place_id = $_GET['place_id'];

// ลบจาก DB อย่างเดียว (รูปอยู่บน Cloudinary)
mysqli_query($conn, "DELETE FROM place_image WHERE image_id = $image_id");

header("Location: place_edit.php?id=$place_id");
exit;
