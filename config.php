<?php
$host = "sql7.freesqldatabase.com";
$user = "sql7824635";
$pass = "iUz24J2d6E";
$db   = "sql7824635";
$port = 3306;

$conn = mysqli_connect($host, $user, $pass, $db, $port);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
mysqli_set_charset($conn, "utf8");
?>
