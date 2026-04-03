<?php
include 'config.php';

if (!isset($_GET['id'])) {
  echo "ไม่พบเนื้อหา";
  exit;
}

$content_id = (int)$_GET['id'];
$sql = "SELECT * FROM content WHERE content_id = $content_id";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) == 0) {
  echo "ไม่พบเนื้อหา";
  exit;
}

$row = mysqli_fetch_assoc($result);
?>

<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title><?php echo $row['content_name']; ?></title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<section class="detail-container">
  <img src="<?php echo $row['content_image']; ?>" class="detail-image">

  <div class="detail-content">
    <h1><?php echo $row['content_name']; ?></h1>
    <p><?php echo nl2br($row['content_description']); ?></p>

    <?php if (!empty($row['content_path'])) { ?>
      <a href="<?php echo $row['content_path']; ?>" target="_blank" class="detail-link">
        อ่านเพิ่มเติม
      </a>
    <?php } ?>

    <a href="index.php" class="back-btn">← กลับหน้าหลัก</a>
  </div>
</section>

</body>
</html>
