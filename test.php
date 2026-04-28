<?php
include 'config.php';
if ($conn) {
    echo "DB Connected OK!";
} else {
    echo "DB Error: " . mysqli_connect_error();
}
?>
