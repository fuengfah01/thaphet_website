<?php
include '../config.php';
include 'check_login.php';

$id                = (int)($_POST['place_id'] ?? 0);
$place_name        = $_POST['place_name'] ?? '';
$place_description = $_POST['place_description'] ?? '';
$raw_category      = $_POST['category'] ?? '';
$category          = trim(strtolower($raw_category));

<?php
// หา path ที่เขียนได้
$paths = ['/tmp', '/var/www/html/uploads', '/var/www/html', '/tmp/uploads'];
foreach ($paths as $p) {
    echo $p . ' — exists: ' . (is_dir($p) ? 'yes' : 'no') . ' — writable: ' . (is_writable($p) ? 'YES ✓' : 'NO ✗') . '<br>';
}
die();

if (!in_array($category, ['travel', 'eat'])) die('หมวดหมู่ไม่ถูกต้อง');
if (empty($place_name)) die('ข้อมูลไม่ครบ');

$sql = "UPDATE place SET place_name = ?, category = ?, place_description = ? WHERE place_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "sssi", $place_name, $category, $place_description, $id);
mysqli_stmt_execute($stmt);

$upload_dir = "/var/www/html/uploads/";
if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

// ===== UPLOAD QR =====
if (!empty($_FILES['model_3d']['name']) && $_FILES['model_3d']['error'] === UPLOAD_ERR_OK) {
    $file_name   = time() . "_" . basename($_FILES['model_3d']['name']);
    $target_file = $upload_dir . $file_name;

    if (move_uploaded_file($_FILES['model_3d']['tmp_name'], $target_file)) {
        $db_path = "uploads/" . $file_name;
        $check   = mysqli_query($conn, "SELECT model_id FROM model_3d WHERE place_id = $id");

        if (mysqli_num_rows($check) > 0) {
            mysqli_query($conn, "UPDATE model_3d SET model_3d = '$db_path' WHERE place_id = $id");
        } else {
            mysqli_query($conn, "INSERT INTO model_3d (place_id, model_3d) VALUES ($id, '$db_path')");
        }
    }
}

// ===== UPLOAD รูปเพิ่มเติม =====
if (!empty($_FILES['place_images']['name'][0])) {
    foreach ($_FILES['place_images']['tmp_name'] as $key => $tmp_name) {
        if ($_FILES['place_images']['error'][$key] !== UPLOAD_ERR_OK) continue;

        $ext         = pathinfo(basename($_FILES['place_images']['name'][$key]), PATHINFO_EXTENSION);
        $new_name    = time() . '_' . uniqid() . '.' . $ext;
        $target_file = $upload_dir . $new_name;

        if (move_uploaded_file($tmp_name, $target_file)) {
            $db_path  = 'uploads/' . $new_name;
            $sql_img  = "INSERT INTO place_image (place_id, image_path) VALUES (?, ?)";
            $stmt_img = mysqli_prepare($conn, $sql_img);
            mysqli_stmt_bind_param($stmt_img, "is", $id, $db_path);
            mysqli_stmt_execute($stmt_img);
        }
    }
}

header("Location: place_manage.php");
exit;
