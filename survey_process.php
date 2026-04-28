<?php
session_start();
include 'config.php';

// ===== ป้องกันเข้า URL ตรง ๆ =====
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: survey.php");
    exit;
}

$gender    = $_POST['gender']    ?? '';
$age_range = $_POST['age_range'] ?? '';

// ===== Validate =====
$allowed_gender = ['male', 'female', 'unspecified'];
$allowed_age    = ['15-25', '26-35', '36-45', '46-55', '56-65', '65+'];

if (!in_array($gender, $allowed_gender) || !in_array($age_range, $allowed_age)) {
    header("Location: survey.php");
    exit;
}

// ===== INSERT ลงฐานข้อมูล =====
$sql  = "INSERT INTO visitor_log (gender, age_range) VALUES (?, ?)";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ss", $gender, $age_range);
mysqli_stmt_execute($stmt);

// ===== ตั้ง session ว่าทำแล้ว → ไม่ต้องทำซ้ำในการเปิดครั้งเดียวกัน =====
$_SESSION['survey_done'] = true;

header("Location: index.php");
exit;