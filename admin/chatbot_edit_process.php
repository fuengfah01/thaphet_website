<?php
include 'check_login.php';
include '../config.php';

$type = $_POST['type'] ?? '';
$id   = intval($_POST['id'] ?? 0);

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

$allowed_types = ['place', 'restaurant', 'activity', 'souvenir', 'about'];
if (!in_array($type, $allowed_types) || !$id) {
    header('Location: chatbot_manage.php');
    exit;
}

$redirect = 'chatbot_manage.php';
$msg      = '';
$msg_type = 'success';

// DELETE
if (!empty($_POST['_delete'])) {
    $tbl_map = [
        'place'      => ['table' => 'chatbot_place', 'pk' => 'place_id'],
        'restaurant' => ['table' => 'restaurant',     'pk' => 'restaurant_id'],
        'activity'   => ['table' => 'activity',       'pk' => 'activity_id'],
        'souvenir'   => ['table' => 'souvenir_shop',  'pk' => 'shop_id'],
        'about'      => ['table' => 'about_us',       'pk' => 'about_id'],
    ];
    if (isset($tbl_map[$type])) {
        $t  = $tbl_map[$type]['table'];
        $pk = $tbl_map[$type]['pk'];
        mysqli_query($conn, "DELETE FROM $t WHERE $pk = $id");
        $msg      = 'ลบข้อมูลเรียบร้อย';
        $redirect = "chatbot_manage.php?tab=$type";
    }
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['flash_msg']  = $msg;
    $_SESSION['flash_type'] = $msg_type;
    header('Location: ' . $redirect);
    exit;
}

// UPDATE PLACE
if ($type === 'place') {
    $name    = esc($conn, $_POST['place_name'] ?? '');
    $desc    = esc($conn, $_POST['place_description'] ?? '');
    $hi      = esc($conn, $_POST['highlight'] ?? '');
    $cat     = esc($conn, $_POST['category'] ?? '');
    $open    = esc($conn, $_POST['open_time'] ?? '');
    $cls     = esc($conn, $_POST['close_time'] ?? '');
    $map_url = esc($conn, $_POST['map_url'] ?? '');
    $newImg  = uploadImage('cover_image');
    $imgSet  = $newImg ? ", cover_image = '" . mysqli_real_escape_string($conn, $newImg) . "'" : '';
    $mapVal  = $map_url ? "'$map_url'" : 'NULL';
    $sql = "UPDATE chatbot_place SET place_name='$name', place_description='$desc', highlight='$hi', category='$cat', open_time='$open', close_time='$cls', map_url=$mapVal $imgSet WHERE place_id=$id";
    if (mysqli_query($conn, $sql)) { $msg = 'แก้ไขสถานที่เรียบร้อย'; $redirect = 'chatbot_manage.php?tab=place'; }
    else { $msg = 'เกิดข้อผิดพลาด: ' . mysqli_error($conn); $msg_type = 'danger'; $redirect = 'chatbot_manage.php?tab=place'; }
}

// UPDATE RESTAURANT
elseif ($type === 'restaurant') {
    $name    = esc($conn, $_POST['name'] ?? '');
    $cat     = esc($conn, $_POST['category'] ?? '');
    $hi      = esc($conn, $_POST['highlight'] ?? '');
    $open    = esc($conn, $_POST['open_hours'] ?? '');
    $cls     = esc($conn, $_POST['close_hours'] ?? '');
    $map_url = esc($conn, $_POST['map_url'] ?? '');
    $credit  = esc($conn, $_POST['image_credit'] ?? '');
    $newImg  = uploadImage('cover_image');
    $imgSet    = $newImg  ? ", cover_image = '" . mysqli_real_escape_string($conn, $newImg) . "'" : '';
    $mapVal    = $map_url ? "'$map_url'" : 'NULL';
    $creditVal = $credit  ? "'$credit'"  : 'NULL';
    $sql = "UPDATE restaurant SET name='$name', category='$cat', highlight='$hi', open_hours='$open', close_hours='$cls', map_url=$mapVal, image_credit=$creditVal $imgSet WHERE restaurant_id=$id";
    if (mysqli_query($conn, $sql)) { $msg = 'แก้ไขร้านอาหารเรียบร้อย'; $redirect = 'chatbot_manage.php?tab=restaurant'; }
    else { $msg = 'เกิดข้อผิดพลาด: ' . mysqli_error($conn); $msg_type = 'danger'; $redirect = 'chatbot_manage.php?tab=restaurant'; }
}

// UPDATE ACTIVITY
elseif ($type === 'activity') {
    $name     = esc($conn, $_POST['name'] ?? '');
    $act_type = esc($conn, $_POST['act_type'] ?? '');
    $desc     = esc($conn, $_POST['description'] ?? '');
    $newImg   = uploadImage('image_file'); // ✅ แก้: ตรงกับ name="image_file" ในฟอร์ม
    $imgSet   = $newImg ? ", image_url = '" . mysqli_real_escape_string($conn, $newImg) . "'" : '';
    $sql = "UPDATE activity SET name='$name', type='$act_type', description='$desc' $imgSet WHERE activity_id=$id";
    if (mysqli_query($conn, $sql)) { $msg = 'แก้ไขกิจกรรมเรียบร้อย'; $redirect = 'chatbot_manage.php?tab=activity'; }
    else { $msg = 'เกิดข้อผิดพลาด: ' . mysqli_error($conn); $msg_type = 'danger'; $redirect = 'chatbot_manage.php?tab=activity'; }
}

// UPDATE SOUVENIR
elseif ($type === 'souvenir') {
    $name    = esc($conn, $_POST['name'] ?? '');
    $desc    = esc($conn, $_POST['description'] ?? '');
    $phone   = esc($conn, $_POST['phone'] ?? '');
    $open    = esc($conn, $_POST['open_hours'] ?? '');
    $cls     = esc($conn, $_POST['close_hours'] ?? '');
    $map_url = esc($conn, $_POST['map_url'] ?? '');
    $credit  = esc($conn, $_POST['image_credit'] ?? '');
    $newImg  = uploadImage('cover_image');
    $imgSet    = $newImg  ? ", cover_image = '" . mysqli_real_escape_string($conn, $newImg) . "'" : '';
    $phVal     = $phone   ? "'$phone'"   : 'NULL';
    $mapVal    = $map_url ? "'$map_url'" : 'NULL';
    $creditVal = $credit  ? "'$credit'"  : 'NULL';
    $sql = "UPDATE souvenir_shop SET name='$name', description='$desc', phone=$phVal, open_hours='$open', close_hours='$cls', map_url=$mapVal, image_credit=$creditVal $imgSet WHERE shop_id=$id";
    if (mysqli_query($conn, $sql)) { $msg = 'แก้ไขร้านของฝากเรียบร้อย'; $redirect = 'chatbot_manage.php?tab=souvenir'; }
    else { $msg = 'เกิดข้อผิดพลาด: ' . mysqli_error($conn); $msg_type = 'danger'; $redirect = 'chatbot_manage.php?tab=souvenir'; }
}

// UPDATE ABOUT
elseif ($type === 'about') {
    $content = esc($conn, $_POST['content'] ?? '');
    $sql = "UPDATE about_us SET content='$content' WHERE about_id=$id";
    if (mysqli_query($conn, $sql)) { $msg = 'แก้ไขเนื้อหาเรียบร้อย'; $redirect = 'chatbot_manage.php?tab=about'; }
    else { $msg = 'เกิดข้อผิดพลาด: ' . mysqli_error($conn); $msg_type = 'danger'; $redirect = 'chatbot_manage.php?tab=about'; }
}

if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION['flash_msg']  = $msg;
$_SESSION['flash_type'] = $msg_type;
header('Location: ' . $redirect);
exit;
