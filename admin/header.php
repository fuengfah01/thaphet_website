<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!-- <link rel="stylesheet" href="/thaphet_website/assets/css/admin.css"> -->

<header class="admin-header header-green">
    <div class="header-left">
        <a href="/thaphet_website/index.php" class="logo">
                <img src="/thaphet_website/assets/image/logo.png" alt="Thayang Logo">
            </a>
    </div>

    <div class="header-right">
        <span class="admin-name">
        <?= $_SESSION['admin']['name'] ?? 'Admin' ?>
        </span>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</header>
