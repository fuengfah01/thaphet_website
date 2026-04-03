<?php
session_start();
include '../config.php';

$admin_name     = $_POST['admin_name'] ?? '';
$admin_password = $_POST['admin_password'] ?? '';

if ($admin_name === '' || $admin_password === '') {
    header("Location: login.php?error=1");
    exit;
}

$sql = "SELECT * FROM admin WHERE admin_name = '$admin_name' LIMIT 1";
$result = mysqli_query($conn, $sql);

if (!$result) {
    die("SQL Error: " . mysqli_error($conn));
}

if ($row = mysqli_fetch_assoc($result)) {

    if (password_verify($admin_password, $row['admin_password'])) {

        $_SESSION['admin'] = [
            'id'   => $row['admin_id'],
            'name' => $row['admin_name']
        ];

        header("Location: dashboard.php");
        exit;
    }
}

header("Location: login.php?error=1");
exit;
