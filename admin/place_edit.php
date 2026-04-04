<?php
include '../config.php';
include 'check_login.php';
include 'header.php';

$id = (int)($_GET['id'] ?? 0);

/* ===== ข้อมูลสถานที่ ===== */
$result = mysqli_query($conn, "
    SELECT p.*, m.model_3d
    FROM place p
    LEFT JOIN model_3d m ON p.place_id = m.place_id
    WHERE p.place_id = $id
");

$place = mysqli_fetch_assoc($result);

if (!$place) {
    die('ไม่พบข้อมูลสถานที่นี้');
}

/* ===== รูปทั้งหมด ===== */
$images = mysqli_query($conn, "
    SELECT * FROM place_image 
    WHERE place_id = $id
");
?>

<div class="admin-container">
    <div class="content-box1">

        <div class="content-header">
            <h2>แก้ไขสถานที่</h2>
            <a href="place_manage.php" class="btn-back1">ย้อนกลับ</a>
        </div>

        <form action="place_edit_process.php"
            method="post"
            enctype="multipart/form-data"
            class="admin-form">

            <input type="hidden" name="place_id" value="<?= $place['place_id'] ?>">

            <div class="form-group">
                <label>ชื่อสถานที่</label>
                <input type="text" name="place_name"
                    value="<?= htmlspecialchars($place['place_name']) ?>" required>
            </div>

            <div class="form-group">
                <label>หมวดหมู่</label>
                <select name="category" required>
                    <option value="">-- เลือกหมวดหมู่ --</option>
                    <option value="travel" <?= ($place['category'] == 'travel') ? 'selected' : '' ?>>ที่เที่ยว</option>
                    <option value="eat" <?= ($place['category'] == 'eat') ? 'selected' : '' ?>>ที่กิน</option>
                </select>
            </div>

            <div class="form-group">
                <label>รายละเอียด</label>
                <textarea name="place_description" rows="4"><?= htmlspecialchars($place['place_description']) ?></textarea>
            </div>

            <hr class="divider">

            <h3 class="section-title">รูปภาพปัจจุบัน</h3>

            <div class="image-grid">
                <?php while ($img = mysqli_fetch_assoc($images)) { ?>
                    <div class="image-card">
                        <img src="../<?= htmlspecialchars($img['image_path']) ?>">
                        <a href="place_image_delete.php?id=<?= $img['image_id'] ?>&place_id=<?= $id ?>"
                            onclick="return confirm('ลบรูปนี้จริงไหม?')"
                            class="btn-image-delete">
                            ลบรูป
                        </a>
                    </div>
                <?php } ?>
            </div>

            <hr class="divider">

            <div class="form-group">
                <label>เพิ่มรูปใหม่</label>
                <input type="file" name="place_images[]" accept="image/*" multiple>
            </div>

            <div class="form-group">
                <label>QR ปัจจุบัน</label><br>

                <style>
                    .qr-box {
                        background: #fafafa;
                        border-radius: 20px;
                        padding: 15px;
                        display: inline-block;
                        text-align: center;
                        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
                        margin-top: 10px;
                    }

                    .qr-img {
                        width: 150px;
                        border-radius: 12px;
                        display: block;
                        margin: 0 auto 10px;
                    }

                    .no-qr {
                        color: #888;
                        font-size: 14px;
                        margin-top: 8px;
                    }
                </style>

                <?php if (!empty($place['model_3d'])) { ?>
                    <div class="qr-box">
                        <!-- debug path: <?= htmlspecialchars($place['model_3d']) ?> -->
                        <img src="../<?= htmlspecialchars($place['model_3d']) ?>"
                             class="qr-img"
                             onerror="this.style.border='2px solid red'; this.title='ไม่พบรูป: ' + this.src;">

                        <a href="model_delete.php?place_id=<?= $place['place_id'] ?>"
                            onclick="return confirm('ลบ QR นี้จริงไหม?')"
                            class="btn-image-delete">
                            ลบ QR
                        </a>
                    </div>
                <?php } else { ?>
                    <p class="no-qr">ยังไม่มี QR</p>
                <?php } ?>
            </div>

            <div class="form-group">
                <label>เปลี่ยน QR Code</label>
                <input type="file" name="model_3d" accept="image/*">
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-save">บันทึก</button>
            </div>

        </form>

    </div>
</div>
