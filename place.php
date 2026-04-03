<?php
include 'config.php';

$cat = $_GET['category'] ?? '';

$where = '';
if ($cat) {
    $where = "WHERE p.category = '$cat'";
}

$sql = "
SELECT 
  p.place_id,
  p.place_name,
  MIN(pi.image_path) AS image_path
FROM place p
LEFT JOIN place_image pi ON p.place_id = pi.place_id
$where
GROUP BY p.place_id
ORDER BY p.place_id DESC
";

$result = mysqli_query($conn, $sql);
?>


<!DOCTYPE html>
<html lang="th">
<head>
  <meta charset="UTF-8">
  <title>THAYANG TRIP</title>
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- ===== NAVBAR ===== -->
<header class="navbar">
  <div class="logo">THAPHET</div>
  <nav>
    <a href="index.php">HOME</a>
    <a class="active" href="place.php">TRIP</a>
    <a href="#">CONTACT</a>
    <a href="#">ABOUT US</a>
  </nav>
</header>

<!-- ===== HERO ===== -->
<section class="hero">
  <img src="assets/images/hero.jpg" class="hero-bg">
  <div class="hero-box">
    <h1>Travel around Thayang easily by yourself.</h1>
    <p>
      อำเภอท่ายาง จังหวัดเพชรบุรี แหล่งท่องเที่ยวเชิงวัฒนธรรม
      วิถีชุมชน พร้อมต้อนรับนักท่องเที่ยว
    </p>
    <a href="#trip"><button>View</button></a>
  </div>
</section>

<!-- ===== TRIP ===== -->
<section class="trip" id="trip">
  <h2>สถานที่ทั้งหมด</h2>

<div class="category-filter">
    <a href="place.php">ทั้งหมด</a>
    <a href="place.php?category=travel">ที่เที่ยว</a>
    <a href="place.php?category=eat">ที่กิน</a>
</div>
  <h2>THAYANG <span>TRIP</span></h2>
  <p class="subtitle">
    ค้นพบสถานที่ท่องเที่ยวทางวัฒนธรรม วิถีชุมชน และของดีที่คุณไม่ควรพลาด
  </p>

  <div class="trip-list">
    <?php while ($row = mysqli_fetch_assoc($result)) { ?>
      <div class="trip-card">
        <img src="<?php echo $row['image_path']; ?>">
        <div class="trip-name"><?php echo $row['place_name']; ?></div>
        <a href="place_detail.php?id=<?php echo $row['place_id']; ?>">
          <button class="view-btn">View</button>
        </a>
      </div>
    <?php } ?>
  </div>
</section>

<!-- ===== CONTACT ===== -->
<section class="contact">
  <h2>CONTACT <span>THAYANG</span></h2>

  <div class="contact-grid">
    <div class="contact-main">
      <img src="assets/images/contact.jpg">
      <div class="overlay">
        <h3>อำเภอท่ายาง จังหวัดเพชรบุรี</h3>
        <p>ข้อมูลข่าวสาร กิจกรรม และแหล่งท่องเที่ยว</p>
      </div>
    </div>

    <div class="contact-list">
      <div class="contact-item">
        <img src="assets/images/news1.jpg">
        <div>
          <h4>บทความและข่าว</h4>
          <p>อ.ท่ายาง ล่าสุด วันนี้</p>
        </div>
      </div>

      <div class="contact-item">
        <img src="assets/images/news2.jpg">
        <div>
          <h4>หลงเมืองเพชร</h4>
          <p>TIKTOK</p>
        </div>
      </div>

      <div class="contact-item">
        <img src="assets/images/news3.jpg">
        <div>
          <h4>หลงเมืองเพชร</h4>
          <p>FACEBOOK</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ===== FOOTER ===== -->
<footer class="footer">
  <div>THAPHET</div>
  <div>nongphet5405@gmail.com</div>
</footer>

</body>
</html>
