<?php
require_once 'config.php'; // หรือชื่อไฟล์ config ของคุณ

$hash = password_hash('admin1234', PASSWORD_BCRYPT);
$sql = "UPDATE admin SET admin_password = ? WHERE admin_id = 1";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "s", $hash);
mysqli_stmt_execute($stmt);

echo "สำเร็จ! Hash: " . $hash;
?>
