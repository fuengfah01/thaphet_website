<?php
include '../config.php';
include 'check_login.php';

$image_id = (int)($_GET['id'] ?? 0);
$place_id = (int)($_GET['place_id'] ?? 0);

if ($image_id && $place_id) {
    mysqli_query($conn, "DELETE FROM place_image WHERE image_id = $image_id");
}

header("Location: place_edit.php?id=$place_id");
exit;
