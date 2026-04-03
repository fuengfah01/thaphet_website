<?php
include '../config.php';
include 'check_login.php';
include 'header.php';

$sql = "SELECT * FROM content ORDER BY content_id DESC";
$result = mysqli_query($conn, $sql);
?>

<div class="admin-container">
    <div class="content-box2">

        <div class="content-header">
            <a href="dashboard.php" class="btn-back">ย้อนกลับ</a>
            <h2>จัดการคอนเทนต์</h2>
            <a href="content_add.php" class="btn-add">เพิ่มคอนเทนต์</a>
        </div>

        <div class="table-wrapper">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>ชื่อคอนเทนต์</th>
                        <th>ประเภท</th>
                        <th>รูป</th>
                        <th>ลิงก์</th>
                        <th>จัดการ</th>
                    </tr>
                </thead>
                <tbody>

                <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                    <tr>
                        <td><?= $row['content_id'] ?></td>

                        <td><?= htmlspecialchars($row['content_name']) ?></td>

                        <td>
                            <span class="tag"><?= htmlspecialchars($row['content_type']) ?></span>
                        </td>

                        <td class="image-cell">
                            <?php if ($row['content_image']) { ?>
                                <img src="../<?= $row['content_image'] ?>">
                            <?php } else { ?>
                                <span class="no-image">ไม่มีรูป</span>
                            <?php } ?>
                        </td>

                        <td>
                            <?php if ($row['content_path']) { ?>
                                <a href="<?= htmlspecialchars($row['content_path']) ?>"
                                   target="_blank"
                                   class="link">
                                   เปิดลิงก์
                                </a>
                            <?php } ?>
                        </td>

                        <td class="action-cell">
                            <a href="content_edit.php?id=<?= $row['content_id'] ?>"
                               class="btn-edit">แก้ไข</a>

                            <a href="content_delete.php?id=<?= $row['content_id'] ?>"
                               class="btn-delete"
                               onclick="return confirm('ยืนยันการลบ?')">
                               ลบ
                            </a>
                        </td>
                    </tr>
                <?php } ?>

                </tbody>
            </table>
        </div>

    </div>
</div>
