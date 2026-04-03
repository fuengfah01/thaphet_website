<?php
include 'check_login.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>

<?php include 'header.php'; ?>

<div class="admin-container">
    <div class="dashboard-box">
        <h1>Thaphet Admin Dashboard</h1>
        <p class="subtitle">ยินดีต้อนรับเข้าสู่ระบบจัดการเว็บไซต์</p>

        <div class="admin-cards">
            <div class="admin-card">
                <h3>จัดการสถานที่</h3>
                <p>เพิ่ม แก้ไข ลบ สถานที่ท่องเที่ยว</p>
                <a href="place_manage.php">ไปจัดการ</a>
            </div>

            <div class="admin-card">
                <h3>จัดการเนื้อหา</h3>
                <p>เพิ่ม แก้ไข ลบ ข่าวสาร และบทความ</p>
                <a href="content_manage.php">ไปจัดการ</a>
            </div>
        </div>
    </div>
</div>

</body>
</html>
