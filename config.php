<?php
// ตั้งค่าการเชื่อมต่อฐานข้อมูล
$host = "localhost";
$user = "root";
$pass = "";
$db   = "thaphet_website";

// เชื่อมต่อฐานข้อมูล
$conn = mysqli_connect($host, $user, $pass, $db);

// ตรวจสอบการเชื่อมต่อ
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// ป้องกันภาษาไทยกลายเป็น ????
mysqli_set_charset($conn, "utf8");
