<?php
include 'check_login.php';
include '../config.php';

$type = $_POST['type'] ?? '';

// ── Helper: upload image ──────────────────────────────────────────
function uploadImage($fieldName, $uploadDir = '../assets/image/')
{
    if (empty($_FILES[$fieldName]['name'])) return null;

    $file     = $_FILES[$fieldName];
    $allowed  = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $maxSize  = 5 * 1024 * 1024; // 5 MB

    if (!in_array($file['type'], $allowed)) return null;
    if ($file['size'] > $maxSize) return null;

    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('img_') . '.' . strtolower($ext);
    $dest     = $uploadDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $dest)) {
        return 'assets/image/' . $filename;
    }
    return null;
}

// ── Helper: safe string ───────────────────────────────────────────
function esc($conn, $val)
{
    return mysqli_real_escape_string($conn, trim($val));
}

$redirect = 'chatbot_manage.php';
$msg      = '';
$msg_type = 'success';

/* ════════════════════════════════════════════════════════
   INSERT PLACE → chatbot_place (แยกจากตาราง place ของเว็บ)
════════════════════════════════════════════════════════ */
if ($type === 'place') {
    $name    = esc($conn, $_POST['place_name'] ?? '');
    $desc    = esc($conn, $_POST['place_description'] ?? '');
    $hi      = esc($conn, $_POST['highlight'] ?? '');
    $cat     = esc($conn, $_POST['category'] ?? '');
    $open    = esc($conn, $_POST['open_time'] ?? '');
    $cls     = esc($conn, $_POST['close_time'] ?? '');
    $map_url = esc($conn, $_POST['map_url'] ?? '');
    $img     = uploadImage('cover_image');

    $imgVal  = $img ? "'$img'" : 'NULL';
    $mapVal  = $map_url ? "'$map_url'" : 'NULL';

    $sql = "INSERT INTO chatbot_place
              (place_name, place_description, highlight, category, open_time, close_time, map_url, cover_image)
            VALUES
              ('$name','$desc','$hi','$cat','$open','$cls',$mapVal,$imgVal)";

    if (mysqli_query($conn, $sql)) {
        $msg      = 'เพิ่มสถานที่เรียบร้อย';
        $redirect = 'chatbot_manage.php?tab=place';
    } else {
        $msg      = 'เกิดข้อผิดพลาด: ' . mysqli_error($conn);
        $msg_type = 'danger';
    }
}

/* ════════════════════════════════════════════════════════
   INSERT RESTAURANT
════════════════════════════════════════════════════════ */ elseif ($type === 'restaurant') {
    $name    = esc($conn, $_POST['name'] ?? '');
    $cat     = esc($conn, $_POST['category'] ?? '');
    $hi      = esc($conn, $_POST['highlight'] ?? '');
    $phone   = esc($conn, $_POST['phone'] ?? '');
    $open    = esc($conn, $_POST['open_hours'] ?? '');
    $cls     = esc($conn, $_POST['close_hours'] ?? '');
    $map_url = esc($conn, $_POST['map_url'] ?? '');
    $img     = uploadImage('cover_image');

    $imgVal  = $img     ? "'$img'"     : 'NULL';
    $phVal   = $phone   ? "'$phone'"   : 'NULL';
    $mapVal  = $map_url ? "'$map_url'" : 'NULL';

    $sql = "INSERT INTO restaurant
          (name, category, highlight, open_hours, close_hours, map_url, cover_image, image_credit)
        VALUES
          ('$name','$cat','$hi','$open','$cls',$mapVal,$imgVal,NULL)";

    if (mysqli_query($conn, $sql)) {
        $msg      = 'เพิ่มร้านอาหารเรียบร้อย';
        $redirect = 'chatbot_manage.php?tab=restaurant';
    } else {
        $msg      = 'เกิดข้อผิดพลาด: ' . mysqli_error($conn);
        $msg_type = 'danger';
    }
}

/* ════════════════════════════════════════════════════════
   INSERT ACTIVITY
════════════════════════════════════════════════════════ */ 
elseif ($type === 'activity') {
    $name     = esc($conn, $_POST['name'] ?? '');
    $act_type = esc($conn, $_POST['act_type'] ?? '');  // เปลี่ยนตรงนี้
    $desc     = esc($conn, $_POST['description'] ?? '');

    $sql = "INSERT INTO activity (name, type, description)
            VALUES ('$name','$act_type','$desc')";
}

/* ════════════════════════════════════════════════════════
   INSERT SOUVENIR SHOP
════════════════════════════════════════════════════════ */ elseif ($type === 'souvenir') {
    $name    = esc($conn, $_POST['name'] ?? '');
    $desc    = esc($conn, $_POST['description'] ?? '');
    $phone   = esc($conn, $_POST['phone'] ?? '');
    $open    = esc($conn, $_POST['open_hours'] ?? '');
    $cls     = esc($conn, $_POST['close_hours'] ?? '');
    $map_url = esc($conn, $_POST['map_url'] ?? '');
    $img     = uploadImage('cover_image');

    $imgVal  = $img     ? "'$img'"     : 'NULL';
    $phVal   = $phone   ? "'$phone'"   : 'NULL';
    $mapVal  = $map_url ? "'$map_url'" : 'NULL';

    $sql = "INSERT INTO souvenir_shop
              (name, description, phone, open_hours, close_hours, map_url, cover_image, image_credit)
            VALUES
              ('$name','$desc',$phVal,'$open','$cls',$mapVal,$imgVal,NULL)";

    if (mysqli_query($conn, $sql)) {
        $msg      = 'เพิ่มร้านของฝากเรียบร้อย';
        $redirect = 'chatbot_manage.php?tab=souvenir';
    } else {
        $msg      = 'เกิดข้อผิดพลาด: ' . mysqli_error($conn);
        $msg_type = 'danger';
    }
} else {
    $msg      = 'ประเภทข้อมูลไม่ถูกต้อง';
    $msg_type = 'danger';
}

session_start_if_not_started();
$_SESSION['flash_msg']  = $msg;
$_SESSION['flash_type'] = $msg_type;
header('Location: ' . $redirect);
exit;

function session_start_if_not_started()
{
    if (session_status() === PHP_SESSION_NONE) session_start();
}
