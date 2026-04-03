<?php
$host = "junction.proxy.rlwy.net";
$port = 37604;
$user = "root";
$pass = "LtvqydRohNxyXdZZQhtZPqEAgfiZuvsy";
$db   = "railway";

$conn = mysqli_connect($host, $user, $pass, $db, $port);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8");
?>