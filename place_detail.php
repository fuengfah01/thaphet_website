<?php
include 'config.php';

$place_id = $_GET['id'] ?? 0;

// ===== บันทึกการเข้าชม =====
if ($place_id > 0) {
    $sql_log = "INSERT INTO place_view_log (place_id) VALUES (?)";
    $stmt = mysqli_prepare($conn, $sql_log);
    mysqli_stmt_bind_param($stmt, "i", $place_id);
    mysqli_stmt_execute($stmt);
}

/* ---------- ดึงข้อมูลสถานที่ ---------- */
$sql_place = "SELECT * FROM place WHERE place_id = $place_id";
$result_place = mysqli_query($conn, $sql_place);
$place = mysqli_fetch_assoc($result_place);

/* ---------- ดึงรูปสถานที่ ---------- */
$sql_images = "SELECT image_path FROM place_image WHERE place_id = $place_id";
$result_images = mysqli_query($conn, $sql_images);

$images = [];
while ($row = mysqli_fetch_assoc($result_images)) {
  $images[] = $row['image_path'];
}

/* ---------- ดึง QR โมเดล 3D (1 รูป) ---------- */
$sql_model = "SELECT model_3d FROM model_3d WHERE place_id = $place_id LIMIT 1";
$result_model = mysqli_query($conn, $sql_model);
$model = mysqli_fetch_assoc($result_model);
?>
<!DOCTYPE html>
<html lang="th">

<head>
  <meta charset="UTF-8">
  <title><?= $place['place_name']; ?></title>

  <style>
    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      font-family: 'Sarabun', sans-serif;
      background: #f2f2f2;
    }

    .place-card {
      position: relative;
      max-width: 1200px;
      margin: 40px auto;
      background: #0a7a3c;
      border-radius: 24px;
      padding: 32px;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 32px;
      color: #fff;
    }

    .close-btn {
      position: absolute;
      top: 16px;
      right: 16px;
      width: 40px;
      height: 40px;
      border-radius: 50%;
      border: none;
      background: #fff;
      color: #0a7a3c;
      font-size: 1.4rem;
      cursor: pointer;
      z-index: 1000;
    }

    .image-wrapper {
      position: relative;
      width: 100%;
      aspect-ratio: 4 / 3;
      overflow: hidden;
      border-radius: 16px;
      background: #1e1e1e;
    }

    .main-image {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
      transition: opacity 0.25s ease;
    }

    .model-overlay {
      position: absolute;
      bottom: 12px;
      right: 12px;
      width: 90px;
      background: #fff;
      padding: 6px;
      border-radius: 10px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
    }

    .model-box {
      position: absolute;
      bottom: 20px;
      right: 20px;
      background: #0a7a3c;
      border: 4px solid #0a7a3c;
      border-radius: 16px;
      padding: 10px;
      width: 120px;
      height: 120px;
      text-align: center;
      box-shadow: 0 6px 15px rgba(0, 0, 0, 0.2);
    }

    .model-label {
      position: absolute;
      top: -16px;
      left: 50%;
      transform: translateX(-50%);
      background: #0a7a3c;
      color: #ffffff;
      font-size: 12.6px;
      padding: 5px 10px;
      border-radius: 12px 12px 0 0;
      font-weight: bold;
      white-space: nowrap;
    }

    .thumbnail-row {
      display: flex;
      gap: 10px;
      margin-top: 12px;
      overflow-x: auto;
      min-height: 80px;
    }

    .thumbnail-row img {
      width: 80px;
      height: 60px;
      object-fit: cover;
      border-radius: 8px;
      cursor: pointer;
      opacity: 0.7;
    }

    .thumbnail-row img:hover {
      opacity: 1;
    }

    .place-info h1 {
      font-size: 2.4rem;
      margin-bottom: 16px;
    }

    .place-info p {
      font-size: 1.05rem;
      line-height: 1.75;
      white-space: pre-line;
    }

    @media (max-width: 900px) {
      .place-card {
        grid-template-columns: 1fr;
        padding: 20px;
        margin: 20px 12px;
      }

      .image-wrapper {
        width: 100%;
        height: auto;
      }

      .thumbnail-row {
        width: 100%;
        height: 200px;
      }

      .thumbnail-row img {
        width: 100%;
        height: 200px;
      }

      .main-image {
        width: 100%;
        height: auto;
      }

      .place-info h1 {
        font-size: 60px;
        margin-top: 12px;
      }

      .place-info p {
        font-size: 45px;
      }

      .model-overlay {
        width: 150px;
      }

      .model-box {
        width: 180px;
        height: 180px;
      }

      .model-label {
        font-size: 19px;
        padding: 5px 14px;
      }
    }
  </style>
</head>

<body>

  <div class="place-card">

    <!-- ปุ่มปิด -->
    <button class="close-btn" onclick="location.href='/index.php'">✕</button>

    <!-- รูป -->
    <div>
      <div class="image-wrapper">
        <img src="<?= $images[0] ?? ''; ?>" class="main-image" id="mainImage">

        <?php if ($model) { ?>
          <div class="model-box">
            <div class="model-label">รับชมโมเดล 3 มิติ</div>
            <img src="<?= $model['model_3d']; ?>" class="model-overlay">
          </div>
        <?php } ?>
      </div>

      <!-- Thumbnail -->
      <div class="thumbnail-row">
        <?php foreach ($images as $img) { ?>
          <img src="<?= $img; ?>" onclick="changeImage(this.src)">
        <?php } ?>
      </div>
    </div>

    <!-- ข้อมูล -->
    <div class="place-info">
      <h1><?= $place['place_name']; ?></h1>
      <p><?= $place['place_description']; ?></p>
    </div>

  </div>

  <script>
    function changeImage(src) {
      const img = document.getElementById('mainImage');
      img.style.opacity = 0;
      setTimeout(() => {
        img.src = src;
        img.style.opacity = 1;
      }, 120);
    }
  </script>

</body>

</html>
