<?php
include '../config.php';
include 'check_login.php';
include 'header.php';

$sql = "SELECT p.* FROM place p";
$result = mysqli_query($conn, $sql);

$places = [];
while ($row = mysqli_fetch_assoc($result)) {
    $pid = $row['place_id'];
    $img = mysqli_query($conn, "SELECT image_path FROM place_image WHERE place_id = $pid LIMIT 1");
    $row['image_path'] = ($img && mysqli_num_rows($img) > 0) ? mysqli_fetch_assoc($img)['image_path'] : '';
    $places[] = $row;
}
?>
<div class="admin-container">
    <div class="content-box">
        <div class="content-header">
            <a href="dashboard.php" class="btn-back">ย้อนกลับ</a>
            <h2>จัดการสถานที่</h2>
            <a href="place_add.php" class="btn-add">เพิ่มสถานที่</a>
        </div>
        <div class="table-wrapper">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>รูป</th>
                        <th>ชื่อสถานที่</th>
                        <th>รายละเอียด</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($places as $row): ?>
                    <tr>
                        <td class="image-cell">
                            <?php if (!empty($row['image_path'])): ?>
                                <img src="<?= htmlspecialchars($row['image_path']) ?>">
                            <?php else: ?>
                                <span class="no-image">ไม่มีรูป</span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($row['place_name']) ?></td>
                        <td class="description">
                            <?= nl2br(htmlspecialchars($row['place_description'])) ?>
                        </td>
                        <td class="action-cell">
                            <a href="place_edit.php?id=<?= $row['place_id'] ?>" class="btn-edit">แก้ไข</a>
                            <a href="place_delete.php?id=<?= $row['place_id'] ?>"
                               class="btn-delete"
                               onclick="return confirm('ลบสถานที่นี้จริงหรือไม่?')">
                               ลบ
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
