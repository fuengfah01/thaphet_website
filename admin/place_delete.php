<?php
include '../config.php';
include 'check_login.php';

$id = (int)($_GET['id'] ?? 0);

// debug ชั่วคราว
echo "id = " . $id . "<br>";
echo "URL = " . $_SERVER['REQUEST_URI'] . "<br>";

$del1 = mysqli_query($conn, "DELETE FROM place_image WHERE place_id = $id");
echo "ลบ place_image: " . ($del1 ? 'OK' : mysqli_error($conn)) . "<br>";

$del2 = mysqli_query($conn, "DELETE FROM model_3d WHERE place_id = $id");
echo "ลบ model_3d: " . ($del2 ? 'OK' : mysqli_error($conn)) . "<br>";

$del3 = mysqli_query($conn, "DELETE FROM place WHERE place_id = $id");
echo "ลบ place: " . ($del3 ? 'OK' : mysqli_error($conn)) . "<br>";

die("หยุดที่ debug");
