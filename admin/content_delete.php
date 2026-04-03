<?php
include '../config.php';
include 'check_login.php';

$id = $_GET['id'] ?? 0;

if ($id) {
    mysqli_query($conn, "DELETE FROM content WHERE content_id = $id");
}

header("Location: content_manage.php");
exit;
