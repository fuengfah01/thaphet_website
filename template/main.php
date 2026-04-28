<?php if (!isset($_SESSION['admin'])): ?>
<?php endif; ?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thaphet Cultural Explorer</title>

    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">

</head>


<body>

    <!-- ================= HEADER ================= -->
    <header class="navbar">
        <div class="nav-container">
            <a href="/admin/login.php" class="logo">
                <img src="/assets/image/logo.png" alt="Thayang Logo">
            </a>


            <!-- Hamburger -->
            <div class="hamburger" id="hamburger">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </div>


            <!-- Menu -->
            <nav class="nav-menu">
                <a href="#">HOME</a>
                <a href="#trip">TRIP</a>
                <a href="#contact">CONTACT</a>
                <a href="#about">ABOUT US</a>
            </nav>


        </div>
    </header>


    <!-- ================= HERO ================= -->
    <section class="hero">
        <div class="slides">
            <span></span>
            <span></span>
            <span></span>
            <span></span>
            <span></span>
        </div>

        <div class="hero-content">
            <h1>Travel around Thayang<br>easily by yourself.</h1>
            <p>
                อำเภอท่ายาง จังหวัดเพชรบุรี แหล่งท่องเที่ยวเชิงวัฒนธรรมและวิถีชุมชน
                ที่พร้อมต้อนรับนักท่องเที่ยวด้วยอัตลักษณ์ท้องถิ่นอันโดดเด่น ถ่ายทอด
                เรื่องราวผ่านประเพณี วิถีชีวิต และภูมิปัญญาชาวบ้าน สร้างประสบการณ์
                การเดินทางที่อบอุ่น มีคุณค่า และน่าจดจำ
            </p>
            <a href="#trip" class="btn-view">View</a>
        </div>
    </section>


    <!-- ================= TRIP ================= -->
    <section id="trip" class="trip-section">


        <!-- ====== TRIP HEADER (ไม่เลื่อน) ====== -->
        <div class="trip-header">
            <h2 class="section-title">THAYANG <span>TRIP</span></h2>
            <p class="section-subtitle">
                ค้นพบเสน่ห์ไทยในทุกการเดินทาง สัมผัสธรรมชาติ วัฒนธรรม และวิถีชีวิต
            </p>

            <div class="filter-container">
                <button class="filter-btn active" onclick="filterSelection('all', this)">ทั้งหมด</button>
                <button class="filter-btn" onclick="filterSelection('travel', this)">ที่เที่ยว</button>
                <button class="filter-btn" onclick="filterSelection('eat', this)">ที่กิน</button>
            </div>
        </div>

        <!-- ====== TRIP SCROLL AREA (เลื่อนได้) ====== -->
        <div class="trip-grid">
            <?php
            $sql = "
      SELECT 
        p.place_id,
        p.place_name,
        p.category,
        MIN(pi.image_path) AS image_path
      FROM place p
      LEFT JOIN place_image pi ON p.place_id = pi.place_id
      GROUP BY p.place_id
    ";
            $result = mysqli_query($conn, $sql);

            while ($row = mysqli_fetch_assoc($result)) {
                $category = $row['category'];
            ?>
                <div class="trip-card filter-item" data-category="<?= $category ?>">
                    <div class="trip-image">
                        <img src="<?= $row['image_path'] ?>" alt="<?= $row['place_name'] ?>">

                        <div class="trip-overlay">
                            <a href="/place_detail.php?id=<?= $row['place_id'] ?>">
                                <button class="btn-primary small">View</button>
                            </a>

                        </div>
                    </div>

                    <div class="trip-info">
                        <h3><?= $row['place_name'] ?></h3>
                    </div>
                </div>

            <?php } ?>
        </div>

    </section>


    <!-- ================= CONTACT ================= -->
    <section id="contact">
        <h2 class="section-title"><span>CONTENT</span> THAYANG</h2>
        <p class="section-subtitle">เชื่อมต่อทุกเรื่องราวของท่ายาง</p>


        <?php
        $sql_content = "SELECT * FROM content ORDER BY content_id DESC LIMIT 10";
        $res_content = mysqli_query($conn, $sql_content);

        $contents = [];
        while ($row = mysqli_fetch_assoc($res_content)) {
            $contents[] = $row;
        }
        ?>

        <div class="contact-grid">

            <!-- ฝั่งซ้าย (คงเดิม) -->
            <?php if (isset($contents[0])) { ?>
                <a href="<?php echo $contents[0]['content_path']; ?>" target="_blank" class="contact-main-link">

                    <div class="contact-main">
                        <img src="/<?php echo $contents[0]['content_image']; ?>" alt="">

                        <div class="contact-overlay">
                            <h3><?php echo $contents[0]['content_name']; ?></h3>

                            <p>
                                <?php
                                if ($contents[0]['content_type'] === 'facebook') {
                                    echo 'FACEBOOK';
                                } elseif ($contents[0]['content_type'] === 'tiktok') {
                                    echo 'TIKTOK';
                                } else {
                                    echo 'NEWS';
                                }
                                ?>
                            </p>

                            <small>
                                <?php echo $contents[0]['content_description']; ?>
                            </small>
                        </div>
                    </div>

                </a>
            <?php } ?>



            <!-- ฝั่งขวา (ดึงจาก DB) -->
            <div class="news-list">
                <?php
                for ($i = 1; $i < count($contents); $i++) {
                    $c_row = $contents[$i];
                ?>
                    <div class="news-item">
                        <a href="<?php echo $c_row['content_path']; ?>" target="_blank">
                            <img src="<?php echo $c_row['content_image']; ?>" alt="">
                        </a>

                        <div class="news-content">
                            <h4>
                                <a href="<?php echo $c_row['content_path']; ?>" target="_blank">
                                    <?php echo $c_row['content_name']; ?>
                                </a>
                            </h4>

                            <p class="news-desc">
                                <?php echo $c_row['content_description']; ?>
                            </p>

                            <small class="news-type <?php echo $c_row['content_type']; ?>">
                                <?php
                                if ($c_row['content_type'] === 'facebook') {
                                    echo 'FACEBOOK';
                                } elseif ($c_row['content_type'] === 'tiktok') {
                                    echo 'TIKTOK';
                                } else {
                                    echo 'NEWS';
                                }
                                ?>
                            </small>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </section>


    <!-- ================= ABOUT ================= -->
    <section class="about-section" id="about">
        <div class="about-content">
            <div class="about-text">
                <h2>ABOUT US</h2>
                <p>ยินดีต้อนรับสู่ชุมชนท่าเพชร ชุมชนเล็ก ๆ ที่เต็มไปด้วยเรื่องราว ประวัติศาสตร์ วัฒนธรรม และวิถีชีวิตท้องถิ่นอันทรงคุณค่า
                    ณ อำเภอท่ายาง จังหวัดเพชรบุรี ถนนสายวัฒนธรรมแห่งนี้
                    จะพาคุณย้อนเวลา สัมผัสอัตลักษณ์ของผู้คน บ้านเรือน และรสชาติอาหารพื้นถิ่น ที่ยังคงมีชีวิตในทุกย่างก้าว</p>
            </div>
            <div class="about-image">
                <img src="/assets/image/a2.jpg">

            </div>
        </div>
    </section>

    <!-- Chat Button -->
    <a href="https://lin.ee/bNozvTS" target="_blank" class="chat-button" style="text-decoration: none;">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="white">
            <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z" />
        </svg>
    </a>

    <!-- ================= FOOTER ================= -->
    <footer class="footer">
        <a href="/index.php" class="logo1">
            <img src="/assets/image/logo.png" alt="Thayang Logo">
        </a>

        <h5>nongphet5405@gmail.com</h5>
    </footer>

    <!-- ================= SCRIPT ================= -->
    <script>
        function filterSelection(category, btn) {
            const items = document.querySelectorAll('.filter-item');
            const buttons = document.querySelectorAll('.filter-btn');

            buttons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');

            items.forEach(item => {
                if (category === 'all' || item.dataset.category === category) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        const slider = document.querySelector('.trip-grid');
        let isDown = false;
        let startX;
        let scrollLeft;

        slider.addEventListener('mousedown', (e) => {
            isDown = true;
            slider.classList.add('active');
            startX = e.pageX - slider.offsetLeft;
            scrollLeft = slider.scrollLeft;
        });

        slider.addEventListener('mouseleave', () => {
            isDown = false;
        });

        slider.addEventListener('mouseup', () => {
            isDown = false;
        });

        slider.addEventListener('mousemove', (e) => {
            if (!isDown) return;
            e.preventDefault();
            const x = e.pageX - slider.offsetLeft;
            const walk = (x - startX) * 1.5; // ความเร็วการลาก
            slider.scrollLeft = scrollLeft - walk;
        });


        const hamburger = document.getElementById("hamburger");
        const navMenu = document.querySelector(".nav-menu");

        hamburger.addEventListener("click", () => {
            hamburger.classList.toggle("active");
            navMenu.classList.toggle("active");
        });
    </script>


</body>

</html>
