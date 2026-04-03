<?php
include '../config.php';
include 'check_login.php';
include 'header.php';
?>

<div class="admin-container">
    <div class="content-box1">

        <div class="content-header">
            <div class="header-left">
                <h2>เพิ่มคอนเทนต์</h2>
            </div>
            <a href="content_manage.php" class="btn-back1">ย้อนกลับ</a>
        </div>

        <form action="content_add_process.php"
              method="post"
              enctype="multipart/form-data"
              class="admin-form">

            <div class="form-group">
                <label>ชื่อคอนเทนต์</label>
                <input type="text" name="content_name" required>
            </div>

            <div class="form-group">
                <label>ประเภท (เช่น NEWS, FACEBOOK, TIKTOK)</label>
                <input type="text" name="content_type">
            </div>

            <div class="form-group">
                <label>Link URL</label>
                <input type="text" name="content_path">
            </div>

            <div class="form-group">
                <label>รายละเอียด</label>
                <textarea name="content_description" rows="4"></textarea>
            </div>

            <div class="form-group">
                <label>อัปโหลดรูป</label>
                <input type="file" name="content_image" accept="image/*">
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-save">บันทึก</button>
            </div>

        </form>

    </div>
</div>
