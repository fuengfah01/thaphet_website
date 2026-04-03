<?php
include '../config.php';
include 'check_login.php';
include 'header.php';
?>

<div class="admin-container">
    <div class="content-box1">

        <div class="content-header">
            <h2>เพิ่มสถานที่</h2>
            <a href="place_manage.php" class="btn-back1">ย้อนกลับ</a>
        </div>

        <form action="/thaphet_website/admin/place_add_process.php"
            method="post"
            enctype="multipart/form-data"
            class="admin-form">

            <div class="form-group">
                <label>ชื่อสถานที่</label>
                <input type="text" name="place_name" required>
            </div>

            <div class="form-group">
                <label>หมวดหมู่</label>
                <select name="category" required>
                    <option value="">-- เลือกหมวดหมู่ --</option>
                    <option value="travel">ที่เที่ยว</option>
                    <option value="eat">ที่กิน</option>
                </select>
            </div>

            <div class="form-group">
                <label>รายละเอียดสถานที่</label>
                <textarea name="place_description" rows="4"></textarea>
            </div>

            <div class="form-group">
                <label>อัปโหลดรูป (เลือกได้หลายรูป)</label>
                <input type="file" name="place_images[]" accept="image/*" multiple>
            </div>

            <div class="form-group">
                <label>อัปโหลด QR Code (สำหรับ 3D Model)</label>
                <input type="file" name="model_3d" accept="image/*">
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-save">บันทึก</button>
            </div>

        </form>

    </div>
</div>