<?php
include 'check_login.php';
include '../config.php';

$type = $_POST['type'] ?? '';

// ── Helper: upload image to Cloudinary ───────────────────────────
function uploadImage($fieldName)
{
    if (empty($_FILES[$fieldName]['name'])) return null;

    $file    = $_FILES[$fieldName];
    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    $maxSize = 5 * 1024 * 1024;

    if (!in_array($file['type'], $allowed)) return null;
    if ($file['size'] > $maxSize) return null;

    $cloud_name = 'dtqdc6au1';
    $api_key    = '848722437152954';
    $api_secret = '3XUWck6U1OYO2Yx_X8HXfHClarg';
    $timestamp  = time();
    $signature  = sha1("timestamp={$timestamp}{$api_secret}");

    $ch = curl_init("https://api.cloudinary.com/v1_1/{$cloud_name}/image/upload");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => [
            'file'      => new CURLFile($file['tmp_name'], $file['type'], $file['name']),
            'api_key'   => $api_key,
            'timestamp' => $timestamp,
            'signature' => $signature,
        ],
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    return $data['secure_url'] ?? null;
}

function esc($conn, $val)
{
    return mysqli_real_escape_string($conn, trim($val));
}

$redirect = 'chatbot_manage.php';
$msg      = '';
$msg_type = 'success';

/* ════ INSERT PLACE ════ */
if ($type === 'place') {
    $name    = esc($conn, $_POST['place_name'] ?? '');
    $desc    = esc($conn, $_POST['place_description'] ?? '');
    $hi      = esc($conn, $_POST['highlight'] ?? '');
    $cat     = esc($conn, $_POST['category'] ?? '');
    $open    = esc($conn, $_POST['open_time'] ?? '');
    $cls     = esc($conn, $_POST['close_time'] ?? '');
    $map_url = esc($conn, $_POST['map_url'] ?? '');
    $img     = uploadImage('cover_image');

    $imgVal = $img     ? "'" . mysqli_real_escape_string($conn, $img) . "'" : 'NULL';
    $mapVal = $map_url ? "'$map_url'" : 'NULL';

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

/* ════ INSERT RESTAURANT ════ */
elseif ($type === 'restaurant') {
    $name    = esc($conn, $_POST['name'] ?? '');
    $cat     = esc($conn, $_POST['category'] ?? '');
    $hi      = esc($conn, $_POST['highlight'] ?? '');
    $phone   = esc($conn, $_POST['phone'] ?? '');
    $open    = esc($conn, $_POST['open_hours'] ?? '');
    $cls     = esc($conn, $_POST['close_hours'] ?? '');
    $map_url = esc($conn, $_POST['map_url'] ?? '');
    $img     = uploadImage('cover_image');

    $imgVal = $img     ? "'" . mysqli_real_escape_string($conn, $img) . "'" : 'NULL';
    $phVal  = $phone   ? "'$phone'"   : 'NULL';
    $mapVal = $map_url ? "'$map_url'" : 'NULL';

    $sql = "INSERT INTO restaurant
              (name, category, highlight, phone, open_hours, close_hours, map_url, cover_image, image_credit)
            VALUES
              ('$name','$cat','$hi',$phVal,'$open','$cls',$mapVal,$imgVal,NULL)";

    if (mysqli_query($conn, $sql)) {
        $msg      = 'เพิ่มร้านอาหารเรียบร้อย';
        $redirect = 'chatbot_manage.php?tab=restaurant';
    } else {
        $msg      = 'เกิดข้อผิดพลาด: ' . mysqli_error($conn);
        $msg_type = 'danger';
    }
}

/* ════ INSERT ACTIVITY ════ */
elseif ($type === 'activity') {
    $name     = esc($conn, $_POST['name'] ?? '');
    $act_type = esc($conn, $_POST['act_type'] ?? '');   // ✅ แก้: ใช้ act_type ไม่ใช่ type
    $desc     = esc($conn, $_POST['description'] ?? '');
    $img      = uploadImage('cover_image');              // ✅ แก้: ใช้ cover_image ให้ตรงกับ form
    $imgVal   = $img ? "'" . mysqli_real_escape_string($conn, $img) . "'" : 'NULL';

    $sql = "INSERT INTO activity (name, type, description, image_url)
            VALUES ('$name','$act_type','$desc',$imgVal)";

    if (mysqli_query($conn, $sql)) {
        $msg      = 'เพิ่มกิจกรรมเรียบร้อย';
        $redirect = 'chatbot_manage.php?tab=activity';
    } else {
        $msg      = 'เกิดข้อผิดพลาด: ' . mysqli_error($conn);
        $msg_type = 'danger';
    }
}

/* ════ INSERT SOUVENIR ════ */
elseif ($type === 'souvenir') {
    $name    = esc($conn, $_POST['name'] ?? '');
    $desc    = esc($conn, $_POST['description'] ?? '');
    $phone   = esc($conn, $_POST['phone'] ?? '');
    $open    = esc($conn, $_POST['open_hours'] ?? '');
    $cls     = esc($conn, $_POST['close_hours'] ?? '');
    $map_url = esc($conn, $_POST['map_url'] ?? '');
    $img     = uploadImage('cover_image');

    $imgVal = $img     ? "'" . mysqli_real_escape_string($conn, $img) . "'" : 'NULL';
    $phVal  = $phone   ? "'$phone'"   : 'NULL';
    $mapVal = $map_url ? "'$map_url'" : 'NULL';

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

if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION['flash_msg']  = $msg;
$_SESSION['flash_type'] = $msg_type;
header('Location: ' . $redirect);
exit;
