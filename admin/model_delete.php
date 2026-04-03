<?php
include '../config.php';

$place_id = (int)($_GET['place_id'] ?? 0);

$result = mysqli_query($conn, "
    SELECT model_3d FROM model_3d WHERE place_id = $place_id
");

$row = mysqli_fetch_assoc($result);

if ($row && !empty($row['model_3d'])) {

    $file_path = "../" . $row['model_3d'];

    if (file_exists($file_path)) {
        unlink($file_path); // ลบไฟล์จริง
    }

    mysqli_query($conn, "
        DELETE FROM model_3d WHERE place_id = $place_id
    ");
}

header("Location: place_edit.php?id=$place_id");
exit;