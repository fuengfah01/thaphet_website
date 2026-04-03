<?php
include 'check_login.php';
include '../config.php';

$id = $_GET['id'] ?? 0;

$result = mysqli_query($conn, "SELECT * FROM content WHERE content_id = $id");
$content = mysqli_fetch_assoc($result);

if (!$content) {
    echo "ไม่พบข้อมูล";
    exit;
}
?>

<?php include 'header.php'; ?>

<div class="admin-container">
    <div class="content-box1">

        <div class="content-header">
            <h2>แก้ไขคอนเทนต์</h2>
            <a href="content_manage.php" class="btn-back">ย้อนกลับ</a>
        </div>

        <form action="content_edit_process.php"
              method="post"
              enctype="multipart/form-data"
              class="admin-form">

            <input type="hidden" name="content_id" value="<?= $content['content_id'] ?>">
            <input type="hidden" name="old_image" value="<?= $content['content_image'] ?>">

            <div class="form-group">
                <label>ชื่อคอนเทนต์</label>
                <input type="text" name="content_name" required
                       value="<?= htmlspecialchars($content['content_name']) ?>">
            </div>

            <div class="form-group">
                <label>ประเภทคอนเทนต์ (เช่น NEWS, FACEBOOK, TIKTOK)</label>
                <input type="text" name="content_type"
                       value="<?= htmlspecialchars($content['content_type']) ?>">
            </div>

            <div class="form-group">
                <label>รูปภาพปัจจุบัน</label>
                <?php if ($content['content_image']) : ?>
                    <div class="image-preview">
                        <img src="../<?= $content['content_image'] ?>">
                    </div>
                <?php else: ?>
                    <p class="no-image">ไม่มีรูปภาพ</p>
                <?php endif; ?>

                <input type="file" name="content_image" accept="image/*">
            </div>

            <div class="form-group">
                <label>Link URL</label>
                <input type="text" name="content_path"
                       value="<?= htmlspecialchars($content['content_path']) ?>">
            </div>

            <div class="form-group">
                <label>รายละเอียด</label>
                <textarea name="content_description" rows="5"><?= htmlspecialchars($content['content_description']) ?></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-save">บันทึก</button>
            </div>

        </form>

    </div>
</div>
