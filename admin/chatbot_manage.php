<?php
include 'check_login.php';
include '../config.php';
include 'header.php';

$msg = '';
$msg_type = '';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!empty($_SESSION['flash_msg'])) {
    $msg      = $_SESSION['flash_msg'];
    $msg_type = $_SESSION['flash_type'] ?? 'success';
    unset($_SESSION['flash_msg'], $_SESSION['flash_type']);
}

$tab = $_GET['tab'] ?? 'place';

$places      = mysqli_query($conn, "SELECT * FROM chatbot_place ORDER BY place_id");
$restaurants = mysqli_query($conn, "SELECT * FROM restaurant ORDER BY restaurant_id");
$activities  = mysqli_query($conn, "SELECT * FROM activity ORDER BY activity_id");
$souvenirs   = mysqli_query($conn, "SELECT * FROM souvenir_shop ORDER BY shop_id");
$abouts      = mysqli_query($conn, "SELECT * FROM about_us ORDER BY about_id");

function imgSrc($cover_image)
{
    if (empty($cover_image)) return '';
    if (str_starts_with($cover_image, 'http://') || str_starts_with($cover_image, 'https://')) {
        return htmlspecialchars($cover_image);
    }
    return '../' . htmlspecialchars($cover_image);
}

function fmtTime($t)
{
    if (!$t) return '';
    return date('H:i', strtotime($t)) . ' น.';
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
    .cb-wrapper {
        padding: 24px 32px;
        background: #eef1ee;
        min-height: calc(100vh - 64px);
    }

    .cb-topbar {
        display: grid;
        grid-template-columns: 1fr auto 1fr;
        align-items: center;
        margin-bottom: 22px;
    }

    .cb-topbar .btn-back {
        justify-self: start;
    }

.cb-title {
    justify-self: center;
    font-size: 20px;
    font-weight: 700;
    color:  #057C42;  /* เปลี่ยนจาก #1a1a1a →  #057C42 */
    display: flex;
    align-items: center;
    gap: 10px;
}

    .cb-title i {
        color:  #057C42;
    }

.btn-back {
    background:  #057C42;
    border: none;
    color: #fff;
    padding: 9px 20px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all .15s;
    min-width: 80px;
    justify-content: center;
}
    .btn-back:hover {
        background:  #057C42;
        border-color:  #057C42;
        color: #fff;
    }

.btn-add {
    background: #057C42;
    border: none;
    color: #fff;
    padding: 9px 20px;
    border-radius: 20px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all .15s;
    min-width: 80px;
    justify-content: center;
}

    .btn-add:hover {
        background: #235e2c;
        color: #fff;
    }

    .cb-alert {
        padding: 11px 18px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        margin-bottom: 18px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .cb-alert.success {
        background: #e6f4ea;
        color: #1d6f42;
        border: 1px solid #b6dfc4;
    }

    .cb-alert.danger {
        background: #fdecea;
        color: #c0392b;
        border: 1px solid #f5b7b1;
    }

    .cb-tabs {
        display: flex;
        gap: 6px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .cb-tab {
        padding: 8px 16px;
        border-radius: 50px;
        font-size: 13px;
        font-weight: 600;
        color: #666;
        cursor: pointer;
        text-decoration: none;
        border: 1.5px solid #e0e0e0;
        background: #fff;
        transition: all .18s;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    }

    .cb-tab:hover {
        color: #057C42;
        border-color: #057C42;
        background: #f5fbf6;
    }

    .cb-tab.active {
        background: #057C42;
        color: #fff;
        border-color: #057C42;
        box-shadow: 0 4px 12px rgba(5, 124, 66, 0.25);
    }

    .cb-tab .badge-count {
        background: rgba(0, 0, 0, 0.12);
        color: inherit;
        font-size: 10px;
        padding: 1px 7px;
        border-radius: 20px;
        font-weight: 700;
    }

    .cb-tab.active .badge-count {
        background: rgba(255, 255, 255, 0.25);
        color: #fff;
    }

    .cb-section {
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, .06);
        overflow: hidden;
    }

    .cb-section-header {
        background: linear-gradient(135deg, #057C42, #057C42);
        color: #fff;
        padding: 16px 22px;
        font-size: 14px;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-radius: 14px 14px 0 0;
    }

    .cb-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }

    .cb-table th {
        background: #f4f7f5;
        padding: 12px 14px;
        text-align: left;
        font-weight: 700;
        color: #3a6b42;
        font-size: 11.5px;
        border-bottom: 2px solid #e4ede6;
        letter-spacing: 0.03em;
        text-transform: uppercase;
    }

    .cb-table td {
        padding: 14px 14px;
        border-bottom: 1px solid #f0f0f0;
        vertical-align: middle;
        color: #1a1a1a;
    }

    .cb-table tr:last-child td {
        border-bottom: none;
    }

    .cb-table tr:hover td {
        background: #fafffe;
    }

    .cover-img {
        width: 64px;
        height: 52px;
        border-radius: 8px;
        object-fit: cover;
        display: block;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    }

    .cover-img-placeholder {
        width: 64px;
        height: 52px;
        border-radius: 8px;
        background: #f0f2f0;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ccc;
        font-size: 20px;
    }

    .txt-truncate {
        max-width: 220px;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        font-size: 12px;
        color: #666;
        line-height: 1.5;
    }

    .badge {
        display: inline-flex;
        align-items: center;
        padding: 3px 9px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        white-space: nowrap;
    }

    .badge-travel {
        background: #e6f4ea;
        color: #1d6f42;
    }

    .badge-eat {
        background: #fef9e7;
        color: #b45309;
    }

    .badge-salty {
        background: #e3f0fb;
        color: #1d4ed8;
    }

    .badge-sweet {
        background: #fce7f3;
        color: #9d174d;
    }

    .badge-activity {
        background: #f3e8ff;
        color: #6d28d9;
    }

    .badge-souvenir {
        background: #e0f2fe;
        color: #0369a1;
    }

    .time-badge {
        background: #f0f2f0;
        color: #555;
        font-size: 11px;
        padding: 3px 8px;
        border-radius: 6px;
        white-space: nowrap;
    }

    .map-link {
        color: #2563eb;
        font-size: 12px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        transition: color .15s;
    }

    .map-link:hover {
        color: #1d4ed8;
        text-decoration: underline;
    }

    .no-map {
        color: #bbb;
        font-size: 11px;
    }

    .actions {
        display: flex;
        gap: 6px;
        align-items: center;
        flex-wrap: nowrap;
    }

    .actions form {
        display: flex;
        align-items: center;
        margin: 0;
        padding: 0;
    }

    .btn-edit {
        background: #e8f5eb;
        border: none;
        color: #2d7a3a;
        padding: 0 16px;
        height: 32px;
        border-radius: 50px;
        font-size: 12.5px;
        font-weight: 700;
        cursor: pointer;
        transition: all .15s;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        line-height: 1;
        text-decoration: none;
    }

    .btn-edit:hover {
        background: #2d7a3a;
        color: #fff;
    }

    .btn-del {
        background: #fde8e8;
        border: none;
        color: #e74c3c;
        padding: 0 16px;
        height: 32px;
        border-radius: 50px;
        font-size: 12.5px;
        font-weight: 700;
        cursor: pointer;
        transition: all .15s;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        line-height: 1;
    }

    .btn-del:hover {
        background: #e74c3c;
        color: #fff;
    }

    .modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, .45);
        backdrop-filter: blur(3px);
        -webkit-backdrop-filter: blur(3px);
        z-index: 9999;
        align-items: center;
        justify-content: center;
        padding: 20px;
        box-sizing: border-box;
    }

    .modal-overlay.show {
        display: flex;
    }

    .modal-box {
        background: #fff;
        border-radius: 18px;
        width: 100%;
        max-width: 540px;
        max-height: calc(100vh - 40px);
        display: flex;
        flex-direction: column;
        box-shadow: 0 20px 60px rgba(0, 0, 0, .25);
        animation: modalIn .25s ease;
    }

    @keyframes modalIn {
        from {
            opacity: 0;
            transform: scale(.94) translateY(12px);
        }

        to {
            opacity: 1;
            transform: scale(1) translateY(0);
        }
    }

    .modal-head {
        background: #057C42;
        color: #fff;
        padding: 16px 20px;
        font-size: 15px;
        font-weight: 700;
        border-radius: 18px 18px 0 0;
        display: flex;
        align-items: center;
        gap: 10px;
        flex-shrink: 0;
    }

    .modal-head span {
        flex: 1;
    }

    .modal-close {
        background: rgba(255, 255, 255, .2);
        border: none;
        color: #fff;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        cursor: pointer;
        font-size: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background .15s;
    }

    .modal-close:hover {
        background: rgba(255, 255, 255, .35);
    }

    .modal-body {
        padding: 22px 20px 8px;
        overflow-y: auto;
        flex: 1;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-label {
        display: block;
        font-size: 12px;
        font-weight: 700;
        color: #2d7a3a;
        margin-bottom: 5px;
    }

    .form-label .req {
        color: #e74c3c;
    }

    .form-control {
        width: 100%;
        padding: 10px 13px;
        border: 1.5px solid #e0e0e0;
        border-radius: 10px;
        font-size: 13px;
        color: #1a1a1a;
        font-family: inherit;
        background: #fff;
        transition: all .15s;
        box-sizing: border-box;
    }

    .form-control:focus {
        outline: none;
        border-color: #2d7a3a;
        box-shadow: 0 0 0 3px rgba(45, 122, 58, .1);
    }

    .form-control::placeholder {
        color: #bbb;
    }

    textarea.form-control {
        min-height: 90px;
        resize: vertical;
        line-height: 1.6;
    }

    select.form-control {
        cursor: pointer;
    }

    .grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
    }

    .map-input-wrap {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .map-field-wrap {
        position: relative;
        flex: 1;
    }

    .map-field-wrap .map-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #2d7a3a;
        font-size: 13px;
        pointer-events: none;
    }

    .map-field-wrap .form-control {
        padding-left: 32px;
    }

    .btn-map-open {
        flex-shrink: 0;
        background: #057C42;
        color: #fff;
        border: none;
        border-radius: 10px;
        padding: 10px 14px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        white-space: nowrap;
        transition: background .15s;
        text-decoration: none;
    }

    .btn-map-open:hover {
        background: #235e2c;
        color: #fff;
    }

    .map-valid-hint {
        display: none;
        align-items: center;
        gap: 5px;
        font-size: 11.5px;
        color: #2d7a3a;
        font-weight: 600;
        margin-top: 5px;
    }

    .map-valid-hint.show {
        display: flex;
    }

    .sec-label {
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: .7px;
        color: #999;
        margin-bottom: 12px;
        margin-top: 4px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .sec-label::after {
        content: '';
        flex: 1;
        height: 1px;
        background: #eee;
    }

    .upload-area {
        border: 2px dashed #d0e8d3;
        border-radius: 12px;
        background: #f5fbf6;
        padding: 24px 16px;
        text-align: center;
        cursor: pointer;
        position: relative;
        overflow: hidden;
        transition: all .2s;
    }

    .upload-area:hover {
        border-color: #2d7a3a;
        background: #edf7ee;
    }

    .upload-area input[type=file] {
        position: absolute;
        inset: 0;
        opacity: 0;
        cursor: pointer;
        width: 100%;
        height: 100%;
    }

    .upload-icon {
        font-size: 26px;
        color: #2d7a3a;
        margin-bottom: 6px;
        display: block;
    }

    .upload-title {
        font-size: 13px;
        font-weight: 700;
        color: #2d7a3a;
        margin-bottom: 3px;
    }

    .upload-sub {
        font-size: 11.5px;
        color: #888;
    }

    .img-preview-wrap {
        position: relative;
        display: none;
        margin-top: 10px;
    }

    .img-preview-wrap img {
        width: 100%;
        max-height: 180px;
        object-fit: cover;
        border-radius: 10px;
        border: 2px solid #e0e0e0;
    }

    .img-preview-wrap .remove-img {
        position: absolute;
        top: 6px;
        right: 6px;
        background: rgba(0, 0, 0, .55);
        color: #fff;
        border: none;
        border-radius: 50%;
        width: 26px;
        height: 26px;
        cursor: pointer;
        font-size: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .current-img-row {
        display: flex;
        gap: 12px;
        align-items: flex-start;
        margin-bottom: 10px;
    }

    .current-img-thumb {
        width: 80px;
        height: 60px;
        object-fit: cover;
        border-radius: 8px;
        border: 1.5px solid #e0e0e0;
        flex-shrink: 0;
    }

    .current-img-info {
        font-size: 12px;
        color: #777;
        padding-top: 4px;
        line-height: 1.6;
    }

    .modal-foot {
        padding: 14px 20px;
        border-top: 1.5px solid #f0f0f0;
        background: #fafafa;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        flex-shrink: 0;
        border-radius: 0 0 18px 18px;
    }

 .btn-cancel {
    background: #e74c3c;
    border: none;
    color: #fff;
    padding: 9px 24px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: background .15s;
}

.btn-cancel:hover {
    background: #c0392b;
    color: #fff;
}

.btn-save {
    background: #057C42;
    color: #fff;
    border: none;
    padding: 9px 24px;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    box-shadow: none;
    transition: background .15s;
}

.btn-save:hover {
    background: #235e2c;
}

.btn-save:active {
    background: #1a4d23;
}

    .about-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        padding: 20px;
    }

    .about-card {
        border: 1.5px solid #e8e8e8;
        border-radius: 12px;
        padding: 18px;
    }

    .about-card-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 10px;
    }

    .about-section-label {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: #2d7a3a;
        background: #e6f4ea;
        padding: 3px 9px;
        border-radius: 20px;
    }

    .about-content {
        font-size: 13px;
        color: #444;
        line-height: 1.6;
        white-space: pre-line;
    }

    .empty-row td {
        text-align: center;
        padding: 32px;
        color: #bbb;
        font-size: 13px;
    }

    body {
        background: #eef1ee;
    }

    #flashAlert {
        transition: opacity 0.5s ease, max-height 0.5s ease, margin 0.5s ease, padding 0.5s ease;
        max-height: 80px;
        overflow: hidden;
    }

    #flashAlert.hiding {
        opacity: 0;
        max-height: 0;
        margin-bottom: 0;
        padding-top: 0;
        padding-bottom: 0;
    }

    .credit-text {
        font-size: 12px;
        color: #666;
        max-width: 120px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .credit-none {
        font-size: 11px;
        color: #bbb;
    }
</style>

<div class="cb-wrapper">

    <?php if ($msg): ?>
        <div class="cb-alert <?= $msg_type ?>" id="flashAlert">
            <i class="fa fa-<?= $msg_type === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <?= htmlspecialchars($msg) ?>
        </div>
    <?php endif; ?>

    <div class="cb-topbar">
        <a href="index.php" class="btn-back">ย้อนกลับ</a>
        <div class="cb-title"><i class="fa fa-robot"></i>จัดการเนื้อหาแชทบอท</div>
        <div style="justify-self:end;">
            <?php if ($tab !== 'about'): ?>
                <a href="chatbot_add.php" class="btn-add">เพิ่มข้อมูล</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="cb-tabs">
        <?php
        $tab_items = [
            'place'      => ['icon' => 'map-marker-alt', 'label' => 'สถานที่',      'count' => mysqli_num_rows($places)],
            'restaurant' => ['icon' => 'utensils',        'label' => 'ร้านอาหาร',    'count' => mysqli_num_rows($restaurants)],
            'activity'   => ['icon' => 'running',         'label' => 'กิจกรรม',      'count' => mysqli_num_rows($activities)],
            'souvenir'   => ['icon' => 'gift',             'label' => 'ของฝาก',       'count' => mysqli_num_rows($souvenirs)],
            'about'      => ['icon' => 'info-circle',     'label' => 'เกี่ยวกับเรา', 'count' => mysqli_num_rows($abouts)],
        ];
        foreach ($tab_items as $key => $t):
        ?>
            <a href="?tab=<?= $key ?>" class="cb-tab <?= $tab === $key ? 'active' : '' ?>">
                <i class="fa fa-<?= $t['icon'] ?>"></i>
                <?= $t['label'] ?>
                <span class="badge-count"><?= $t['count'] ?></span>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- TAB: PLACE -->
    <?php if ($tab === 'place'): ?>
        <div class="cb-section">
            <div class="cb-section-header">
                <span><i class="fa fa-map-marker-alt" style="margin-right:8px;"></i>สถานที่ท่องเที่ยวและร้านอาหาร (แชทบอท)</span>
            </div>
            <table class="cb-table">
                <thead>
                    <tr>
                        <th style="width:70px;">รูป</th>
                        <th>ชื่อสถานที่</th>
                        <th>ประวัติโดยย่อ</th>
                        <th>จุดเด่น</th>
                        <th>เวลาเปิด-ปิด</th>
                        <th>หมวด</th>
                        <th>แผนที่</th>
                        <th style="width:140px;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    mysqli_data_seek($places, 0);
                    $has = false;
                    while ($row = mysqli_fetch_assoc($places)):
                        $has = true;
                        $src = imgSrc($row['cover_image']);
                    ?>
                        <tr>
                            <td>
                                <?php if ($src): ?>
                                    <img src="<?= $src ?>" class="cover-img" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                                    <div class="cover-img-placeholder" style="display:none;"><i class="fa fa-image"></i></div>
                                <?php else: ?>
                                    <div class="cover-img-placeholder"><i class="fa fa-image"></i></div>
                                <?php endif; ?>
                            </td>
                            <td style="font-weight:700;"><?= htmlspecialchars($row['place_name']) ?></td>
                            <td>
                                <div class="txt-truncate"><?= htmlspecialchars($row['place_description'] ?? '') ?></div>
                            </td>
                            <td>
                                <div class="txt-truncate" style="max-width:160px;"><?= htmlspecialchars($row['highlight'] ?? '') ?></div>
                            </td>
                            <td>
                                <?php if ($row['open_time'] && $row['close_time']): ?>
                                    <span class="time-badge"><?= fmtTime($row['open_time']) ?> – <?= fmtTime($row['close_time']) ?></span>
                                <?php else: ?>
                                    <span style="color:#bbb;font-size:11px;">–</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?= $row['category'] === 'travel' ? 'badge-travel' : 'badge-eat' ?>">
                                    <?= $row['category'] === 'travel' ? '🏛 ท่องเที่ยว' : '🍜 กิน' ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($row['map_url'])): ?>
                                    <a href="<?= htmlspecialchars($row['map_url']) ?>" target="_blank" class="map-link">
                                        <i class="fa fa-location-dot"></i> ดูแผนที่
                                    </a>
                                <?php else: ?>
                                    <span class="no-map">–</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="actions">
                                    <button class="btn-edit" onclick='openPlaceModal(<?= json_encode($row, JSON_UNESCAPED_UNICODE) ?>)'>
                                        <i class="fa fa-pen" style="font-size:10px;"></i> แก้ไข
                                    </button>
                                    <form method="post" action="chatbot_edit_process.php" onsubmit="return confirm('ลบสถานที่นี้?')">
                                        <input type="hidden" name="type" value="place">
                                        <input type="hidden" name="id" value="<?= $row['place_id'] ?>">
                                        <input type="hidden" name="_delete" value="1">
                                        <button type="submit" class="btn-del"><i class="fa fa-trash" style="font-size:10px;"></i> ลบ</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    <?php if (!$has): ?>
                        <tr class="empty-row">
                            <td colspan="8"><i class="fa fa-inbox" style="font-size:24px;display:block;margin-bottom:6px;"></i>ยังไม่มีข้อมูล</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Modal: แก้ไขสถานที่ -->
        <div class="modal-overlay" id="placeModal">
            <div class="modal-box">
                <div class="modal-head">
                    <i class="fa fa-pen-to-square"></i>
                    <span>แก้ไขสถานที่</span>
                    <button class="modal-close" onclick="closeModal('placeModal')"><i class="fa fa-xmark"></i></button>
                </div>
                <form method="post" action="chatbot_edit_process.php" enctype="multipart/form-data" style="display:contents;">
                    <input type="hidden" name="type" value="place">
                    <input type="hidden" name="id" id="p_id">
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-label">ชื่อสถานที่ <span class="req">*</span></label>
                            <input type="text" name="place_name" id="p_name" class="form-control" required placeholder="ระบุชื่อสถานที่">
                        </div>
                        <div class="form-group">
                            <label class="form-label">หมวดหมู่ <span class="req">*</span></label>
                            <select name="category" id="p_category" class="form-control" required>
                                <option value="">-- เลือกหมวดหมู่ --</option>
                                <option value="travel">🏛 สถานที่ท่องเที่ยว</option>
                                <option value="eat">🍜 ร้านอาหาร / ตลาด</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">ประวัติโดยย่อ</label>
                            <textarea name="place_description" id="p_desc" class="form-control" placeholder="เล่าประวัติหรือที่มา..."></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">จุดเด่น</label>
                            <textarea name="highlight" id="p_highlight" class="form-control" rows="3" placeholder="ไฮไลต์ของสถานที่..."></textarea>
                        </div>
                        <div class="sec-label">เวลาเปิด-ปิด</div>
                        <div class="grid-2">
                            <div class="form-group">
                                <label class="form-label">เวลาเปิด</label>
                                <input type="time" name="open_time" id="p_open" class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="form-label">เวลาปิด</label>
                                <input type="time" name="close_time" id="p_close" class="form-control">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">ลิงก์แผนที่ (Google Maps)</label>
                            <div class="map-input-wrap">
                                <div class="map-field-wrap">
                                    <i class="fa fa-location-dot map-icon"></i>
                                    <input type="url" name="map_url" id="p_map_url" class="form-control"
                                        placeholder="https://maps.google.com/?q=..."
                                        oninput="checkMapUrl(this,'p_map_btn','p_map_hint')">
                                </div>
                                <a id="p_map_btn" href="#" target="_blank" class="btn-map-open" style="display:none;">
                                    <i class="fa fa-arrow-up-right-from-square"></i> เปิด
                                </a>
                            </div>
                            <div class="map-valid-hint" id="p_map_hint">
                                <i class="fa fa-check-circle"></i> ลิงก์ถูกต้อง
                            </div>
                        </div>
                        <div class="sec-label">รูปภาพสถานที่</div>
                        <div class="current-img-row" id="p_cur_img_row" style="display:none;">
                            <img id="p_cur_img" src="" class="current-img-thumb" alt="">
                            <div class="current-img-info">รูปปัจจุบัน<br><span style="color:#aaa;">อัปโหลดรูปใหม่เพื่อเปลี่ยน</span></div>
                        </div>
                        <div class="form-group">
                            <div class="upload-area" id="p_upload_box">
                                <input type="file" name="cover_image" accept="image/*"
                                    onchange="previewModalImg(this,'p_img_preview','p_img_preview_wrap','p_upload_box')">
                                <span class="upload-icon"><i class="fa fa-image"></i></span>
                                <div class="upload-title">คลิกเพื่ออัปโหลดรูปภาพ</div>
                                <div class="upload-sub">PNG, JPG หรือ WEBP (สูงสุด 5MB)</div>
                            </div>
                            <div class="img-preview-wrap" id="p_img_preview_wrap">
                                <img id="p_img_preview" alt="preview">
                                <button type="button" class="remove-img"
                                    onclick="removeModalImg('p_img_preview_wrap','p_upload_box','p_img_preview')">
                                    <i class="fa fa-xmark"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="modal-foot">
                        <button type="button" class="btn-cancel" onclick="closeModal('placeModal')">ยกเลิก</button>
                        <button type="submit" class="btn-save"><i class="fa fa-floppy-disk"></i> บันทึก</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- TAB: RESTAURANT -->
    <?php elseif ($tab === 'restaurant'): ?>
        <div class="cb-section">
            <div class="cb-section-header">
                <span><i class="fa fa-utensils" style="margin-right:8px;"></i>ร้านอาหาร</span>
            </div>
            <table class="cb-table">
                <thead>
                    <tr>
                        <th style="width:70px;">รูป</th>
                        <th>ชื่อร้าน</th>
                        <th>หมวด</th>
                        <th>จุดเด่น</th>
                        <th>เวลาเปิด-ปิด</th>
                        <th>แผนที่</th>
                        <th>เครดิตรูป</th>
                        <th style="width:140px;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    mysqli_data_seek($restaurants, 0);
                    $has = false;
                    while ($row = mysqli_fetch_assoc($restaurants)):
                        $has = true;
                        $src = imgSrc($row['cover_image']);
                    ?>
                        <tr>
                            <td>
                                <?php if ($src): ?>
                                    <img src="<?= $src ?>" class="cover-img" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                                    <div class="cover-img-placeholder" style="display:none;"><i class="fa fa-image"></i></div>
                                <?php else: ?>
                                    <div class="cover-img-placeholder"><i class="fa fa-image"></i></div>
                                <?php endif; ?>
                            </td>
                            <td style="font-weight:700;"><?= htmlspecialchars($row['name']) ?></td>
                            <td>
                                <span class="badge <?= $row['category'] === 'อาหารคาว' ? 'badge-salty' : 'badge-sweet' ?>">
                                    <?= $row['category'] === 'อาหารคาว' ? '🍖 อาหารคาว' : '🍮 อาหารหวาน' ?>
                                </span>
                            </td>
                            <td>
                                <div class="txt-truncate"><?= htmlspecialchars($row['highlight'] ?? '') ?></div>
                            </td>
                            <td>
                                <?php if ($row['open_hours'] && $row['close_hours']): ?>
                                    <span class="time-badge"><?= fmtTime($row['open_hours']) ?> – <?= fmtTime($row['close_hours']) ?></span>
                                <?php else: ?>
                                    <span style="color:#bbb;font-size:11px;">–</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($row['map_url'])): ?>
                                    <a href="<?= htmlspecialchars($row['map_url']) ?>" target="_blank" class="map-link">
                                        <i class="fa fa-location-dot"></i> ดูแผนที่
                                    </a>
                                <?php else: ?>
                                    <span class="no-map">–</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($row['image_credit'])): ?>
                                    <span class="credit-text" title="<?= htmlspecialchars($row['image_credit']) ?>">
                                        <?= htmlspecialchars($row['image_credit']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="credit-none">–</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="actions">
                                    <button class="btn-edit" onclick='openRestModal(<?= json_encode($row, JSON_UNESCAPED_UNICODE) ?>)'>
                                        <i class="fa fa-pen" style="font-size:10px;"></i> แก้ไข
                                    </button>
                                    <form method="post" action="chatbot_edit_process.php" onsubmit="return confirm('ลบร้านนี้?')">
                                        <input type="hidden" name="type" value="restaurant">
                                        <input type="hidden" name="id" value="<?= $row['restaurant_id'] ?>">
                                        <input type="hidden" name="_delete" value="1">
                                        <button type="submit" class="btn-del"><i class="fa fa-trash" style="font-size:10px;"></i> ลบ</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    <?php if (!$has): ?>
                        <tr class="empty-row">
                            <td colspan="8"><i class="fa fa-inbox" style="font-size:24px;display:block;margin-bottom:6px;"></i>ยังไม่มีข้อมูล</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Modal: แก้ไขร้านอาหาร -->
        <div class="modal-overlay" id="restModal">
            <div class="modal-box">
                <div class="modal-head">
                    <i class="fa fa-pen-to-square"></i>
                    <span>แก้ไขร้านอาหาร</span>
                    <button class="modal-close" onclick="closeModal('restModal')"><i class="fa fa-xmark"></i></button>
                </div>
                <form method="post" action="chatbot_edit_process.php" enctype="multipart/form-data" style="display:contents;">
                    <input type="hidden" name="type" value="restaurant">
                    <input type="hidden" name="id" id="r_id">
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-label">ชื่อร้าน <span class="req">*</span></label>
                            <input type="text" name="name" id="r_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">หมวดหมู่ <span class="req">*</span></label>
                            <select name="category" id="r_category" class="form-control" required>
                                <option value="">-- เลือกหมวดหมู่ --</option>
                                <option value="อาหารคาว">🍖 อาหารคาว</option>
                                <option value="อาหารหวาน">🍮 อาหารหวาน</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">จุดเด่น / เมนูแนะนำ</label>
                            <textarea name="highlight" id="r_highlight" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="sec-label">เวลาเปิด-ปิด</div>
                        <div class="grid-2">
                            <div class="form-group">
                                <label class="form-label">เวลาเปิด</label>
                                <input type="time" name="open_hours" id="r_open" class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="form-label">เวลาปิด</label>
                                <input type="time" name="close_hours" id="r_close" class="form-control">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">ลิงก์แผนที่ (Google Maps)</label>
                            <div class="map-input-wrap">
                                <div class="map-field-wrap">
                                    <i class="fa fa-location-dot map-icon"></i>
                                    <input type="url" name="map_url" id="r_map_url" class="form-control"
                                        placeholder="https://maps.google.com/?q=..."
                                        oninput="checkMapUrl(this,'r_map_btn','r_map_hint')">
                                </div>
                                <a id="r_map_btn" href="#" target="_blank" class="btn-map-open" style="display:none;">
                                    <i class="fa fa-arrow-up-right-from-square"></i> เปิด
                                </a>
                            </div>
                            <div class="map-valid-hint" id="r_map_hint">
                                <i class="fa fa-check-circle"></i> ลิงก์ถูกต้อง
                            </div>
                        </div>
                        <div class="sec-label">รูปภาพร้าน</div>
                        <div class="current-img-row" id="r_cur_img_row" style="display:none;">
                            <img id="r_cur_img" src="" class="current-img-thumb" alt="">
                            <div class="current-img-info">รูปปัจจุบัน<br><span style="color:#aaa;">อัปโหลดรูปใหม่เพื่อเปลี่ยน</span></div>
                        </div>
                        <div class="form-group">
                            <div class="upload-area" id="r_upload_box">
                                <input type="file" name="cover_image" accept="image/*"
                                    onchange="previewModalImg(this,'r_img_preview','r_img_preview_wrap','r_upload_box')">
                                <span class="upload-icon"><i class="fa fa-image"></i></span>
                                <div class="upload-title">คลิกเพื่ออัปโหลดรูปภาพ</div>
                                <div class="upload-sub">PNG, JPG หรือ WEBP (สูงสุด 5MB)</div>
                            </div>
                            <div class="img-preview-wrap" id="r_img_preview_wrap">
                                <img id="r_img_preview" alt="preview">
                                <button type="button" class="remove-img"
                                    onclick="removeModalImg('r_img_preview_wrap','r_upload_box','r_img_preview')">
                                    <i class="fa fa-xmark"></i>
                                </button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">เครดิตรูปภาพ</label>
                            <input type="text" name="image_credit" id="r_credit" class="form-control" placeholder="เช่น ถ่ายเอง หรือชื่อเจ้าของรูป">
                        </div>
                    </div>
                    <div class="modal-foot">
                        <button type="button" class="btn-cancel" onclick="closeModal('restModal')">ยกเลิก</button>
                        <button type="submit" class="btn-save"><i class="fa fa-floppy-disk"></i> บันทึก</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- TAB: ACTIVITY -->
    <?php elseif ($tab === 'activity'): ?>
        <div class="cb-section">
            <div class="cb-section-header">
                <span><i class="fa fa-running" style="margin-right:8px;"></i>กิจกรรม</span>
            </div>
            <table class="cb-table">
                <thead>
                    <tr>
                        <th>ชื่อกิจกรรม</th>
                        <th>ประเภท</th>
                        <th>สถานที่ที่เกี่ยวข้อง</th>
                        <th style="width:140px;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    mysqli_data_seek($activities, 0);
                    $has = false;
                    while ($row = mysqli_fetch_assoc($activities)):
                        $has = true;
                    ?>
                        <tr>
                            <td style="font-weight:700;"><?= htmlspecialchars($row['name']) ?></td>
                            <td><span class="badge badge-activity"><?= htmlspecialchars($row['type']) ?></span></td>
                            <td>
                                <div class="txt-truncate" style="max-width:300px;"><?= htmlspecialchars($row['description'] ?? '') ?></div>
                            </td>
                            <td>
                                <div class="actions">
                                    <button class="btn-edit" onclick='openActModal(<?= json_encode($row, JSON_UNESCAPED_UNICODE) ?>)'>
                                        <i class="fa fa-pen" style="font-size:10px;"></i> แก้ไข
                                    </button>
                                    <form method="post" action="chatbot_edit_process.php" onsubmit="return confirm('ลบกิจกรรมนี้?')">
                                        <input type="hidden" name="record_type" value="activity">
                                        <input type="hidden" name="id" value="<?= $row['activity_id'] ?>">
                                        <input type="hidden" name="_delete" value="1">
                                        <button type="submit" class="btn-del"><i class="fa fa-trash" style="font-size:10px;"></i> ลบ</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    <?php if (!$has): ?>
                        <tr class="empty-row">
                            <td colspan="4"><i class="fa fa-inbox" style="font-size:24px;display:block;margin-bottom:6px;"></i>ยังไม่มีข้อมูล</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Modal: แก้ไขกิจกรรม -->
        <div class="modal-overlay" id="actModal">
            <div class="modal-box">
                <div class="modal-head">
                    <i class="fa fa-pen-to-square"></i>
                    <span>แก้ไขกิจกรรม</span>
                    <button class="modal-close" onclick="closeModal('actModal')"><i class="fa fa-xmark"></i></button>
                </div>
                <form method="post" action="chatbot_edit_process.php" style="display:contents;">
                    <input type="hidden" name="record_type" value="activity">
                    <input type="hidden" name="type" value="about">
                    <input type="hidden" name="id" id="a_id">
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-label">ชื่อกิจกรรม <span class="req">*</span></label>
                            <input type="text" name="name" id="a_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">ประเภทกิจกรรม <span class="req">*</span></label>
                            <select name="act_type" id="a_type" class="form-control" required>
                                <option value="">-- เลือกประเภท --</option>
                                <option value="ไหว้พระ">🙏 ไหว้พระ</option>
                                <option value="ถ่ายรูป">📷 ถ่ายรูป</option>
                                <option value="ให้อาหารปลา">🐟 ให้อาหารปลา</option>
                                <option value="ตะลอนกิน">🍽 ตะลอนกิน</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">สถานที่ที่เกี่ยวข้อง <span style="font-weight:400;color:#bbb;font-size:11px;">(คั่นด้วย ,)</span></label>
                            <textarea name="description" id="a_desc" class="form-control" rows="3" placeholder="เช่น วัดท่าคอย, ศาลเจ้าพ่อกวนอู"></textarea>
                        </div>
                    </div>
                    <div class="modal-foot">
                        <button type="button" class="btn-cancel" onclick="closeModal('actModal')">ยกเลิก</button>
                        <button type="submit" class="btn-save"><i class="fa fa-floppy-disk"></i> บันทึก</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- TAB: SOUVENIR -->
    <?php elseif ($tab === 'souvenir'): ?>
        <div class="cb-section">
            <div class="cb-section-header">
                <span><i class="fa fa-gift" style="margin-right:8px;"></i>ของฝาก</span>
            </div>
            <table class="cb-table">
                <thead>
                    <tr>
                        <th style="width:70px;">รูป</th>
                        <th>ชื่อร้าน</th>
                        <th>รายละเอียด</th>
                        <th>เบอร์โทร</th>
                        <th>เวลาเปิด-ปิด</th>
                        <th>แผนที่</th>
                        <th>เครดิตรูป</th>
                        <th style="width:140px;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    mysqli_data_seek($souvenirs, 0);
                    $has = false;
                    while ($row = mysqli_fetch_assoc($souvenirs)):
                        $has = true;
                        $src = imgSrc($row['cover_image']);
                    ?>
                        <tr>
                            <td>
                                <?php if ($src): ?>
                                    <img src="<?= $src ?>" class="cover-img" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';">
                                    <div class="cover-img-placeholder" style="display:none;"><i class="fa fa-image"></i></div>
                                <?php else: ?>
                                    <div class="cover-img-placeholder"><i class="fa fa-image"></i></div>
                                <?php endif; ?>
                            </td>
                            <td style="font-weight:700;"><?= htmlspecialchars($row['name']) ?></td>
                            <td>
                                <div class="txt-truncate"><?= htmlspecialchars($row['description'] ?? '') ?></div>
                            </td>
                            <td>
                                <?php if (!empty($row['phone'] ?? '')): ?>
                                    <a href="tel:<?= htmlspecialchars($row['phone']) ?>" style="color:#2d7a3a;font-size:12px;text-decoration:none;">
                                        <i class="fa fa-phone"></i> <?= htmlspecialchars($row['phone']) ?>
                                    </a>
                                <?php else: ?>
                                    <span style="color:#bbb;font-size:11px;">–</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['open_hours'] && $row['close_hours']): ?>
                                    <span class="time-badge"><?= fmtTime($row['open_hours']) ?> – <?= fmtTime($row['close_hours']) ?></span>
                                <?php else: ?>
                                    <span style="color:#bbb;font-size:11px;">–</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($row['map_url'])): ?>
                                    <a href="<?= htmlspecialchars($row['map_url']) ?>" target="_blank" class="map-link">
                                        <i class="fa fa-location-dot"></i> ดูแผนที่
                                    </a>
                                <?php else: ?>
                                    <span class="no-map">–</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($row['image_credit'])): ?>
                                    <span class="credit-text" title="<?= htmlspecialchars($row['image_credit']) ?>">
                                        <?= htmlspecialchars($row['image_credit']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="credit-none">–</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="actions">
                                    <button class="btn-edit" onclick='openSouModal(<?= json_encode($row, JSON_UNESCAPED_UNICODE) ?>)'>
                                        <i class="fa fa-pen" style="font-size:10px;"></i> แก้ไข
                                    </button>
                                    <form method="post" action="chatbot_edit_process.php" onsubmit="return confirm('ลบร้านนี้?')">
                                        <input type="hidden" name="type" value="souvenir">
                                        <input type="hidden" name="id" value="<?= $row['shop_id'] ?>">
                                        <input type="hidden" name="_delete" value="1">
                                        <button type="submit" class="btn-del"><i class="fa fa-trash" style="font-size:10px;"></i> ลบ</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    <?php if (!$has): ?>
                        <tr class="empty-row">
                            <td colspan="8"><i class="fa fa-inbox" style="font-size:24px;display:block;margin-bottom:6px;"></i>ยังไม่มีข้อมูล</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Modal: แก้ไขของฝาก -->
        <div class="modal-overlay" id="souModal">
            <div class="modal-box">
                <div class="modal-head">
                    <i class="fa fa-pen-to-square"></i>
                    <span>แก้ไขร้านของฝาก</span>
                    <button class="modal-close" onclick="closeModal('souModal')"><i class="fa fa-xmark"></i></button>
                </div>
                <form method="post" action="chatbot_edit_process.php" enctype="multipart/form-data" style="display:contents;">
                    <input type="hidden" name="type" value="souvenir">
                    <input type="hidden" name="id" id="s_id">
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-label">ชื่อร้าน <span class="req">*</span></label>
                            <input type="text" name="name" id="s_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">รายละเอียด / สินค้าที่ขาย</label>
                            <textarea name="description" id="s_desc" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label class="form-label">เบอร์โทรศัพท์</label>
                            <input type="text" name="phone" id="s_phone" class="form-control" placeholder="เช่น 090-000-0000">
                        </div>
                        <div class="sec-label">เวลาเปิด-ปิด</div>
                        <div class="grid-2">
                            <div class="form-group">
                                <label class="form-label">เวลาเปิด</label>
                                <input type="time" name="open_hours" id="s_open" class="form-control">
                            </div>
                            <div class="form-group">
                                <label class="form-label">เวลาปิด</label>
                                <input type="time" name="close_hours" id="s_close" class="form-control">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">ลิงก์แผนที่ (Google Maps)</label>
                            <div class="map-input-wrap">
                                <div class="map-field-wrap">
                                    <i class="fa fa-location-dot map-icon"></i>
                                    <input type="url" name="map_url" id="s_map_url" class="form-control"
                                        placeholder="https://maps.google.com/?q=..."
                                        oninput="checkMapUrl(this,'s_map_btn','s_map_hint')">
                                </div>
                                <a id="s_map_btn" href="#" target="_blank" class="btn-map-open" style="display:none;">
                                    <i class="fa fa-arrow-up-right-from-square"></i> เปิด
                                </a>
                            </div>
                            <div class="map-valid-hint" id="s_map_hint">
                                <i class="fa fa-check-circle"></i> ลิงก์ถูกต้อง
                            </div>
                        </div>
                        <div class="sec-label">รูปภาพร้าน</div>
                        <div class="current-img-row" id="s_cur_img_row" style="display:none;">
                            <img id="s_cur_img" src="" class="current-img-thumb" alt="">
                            <div class="current-img-info">รูปปัจจุบัน<br><span style="color:#aaa;">อัปโหลดรูปใหม่เพื่อเปลี่ยน</span></div>
                        </div>
                        <div class="form-group">
                            <div class="upload-area" id="s_upload_box">
                                <input type="file" name="cover_image" accept="image/*"
                                    onchange="previewModalImg(this,'s_img_preview','s_img_preview_wrap','s_upload_box')">
                                <span class="upload-icon"><i class="fa fa-image"></i></span>
                                <div class="upload-title">คลิกเพื่ออัปโหลดรูปภาพ</div>
                                <div class="upload-sub">PNG, JPG หรือ WEBP (สูงสุด 5MB)</div>
                            </div>
                            <div class="img-preview-wrap" id="s_img_preview_wrap">
                                <img id="s_img_preview" alt="preview">
                                <button type="button" class="remove-img"
                                    onclick="removeModalImg('s_img_preview_wrap','s_upload_box','s_img_preview')">
                                    <i class="fa fa-xmark"></i>
                                </button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">เครดิตรูปภาพ</label>
                            <input type="text" name="image_credit" id="s_credit" class="form-control" placeholder="เช่น ถ่ายเอง หรือชื่อเจ้าของรูป">
                        </div>
                    </div>
                    <div class="modal-foot">
                        <button type="button" class="btn-cancel" onclick="closeModal('souModal')">ยกเลิก</button>
                        <button type="submit" class="btn-save"><i class="fa fa-floppy-disk"></i> บันทึก</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- TAB: ABOUT -->
    <?php elseif ($tab === 'about'): ?>
        <div class="cb-section">
            <div class="cb-section-header">
                <span><i class="fa fa-info-circle" style="margin-right:8px;"></i>เกี่ยวกับเรา</span>
            </div>
            <?php
            $section_labels = ['highlight' => 'จุดเด่น', 'lifestyle' => 'วิถีชีวิต', 'culture' => 'วัฒนธรรม', 'contact' => 'ติดต่อเรา'];
            $section_icons  = ['highlight' => 'star', 'lifestyle' => 'leaf', 'culture' => 'landmark', 'contact' => 'phone'];
            ?>
            <div class="about-grid">
                <?php
                mysqli_data_seek($abouts, 0);
                while ($row = mysqli_fetch_assoc($abouts)):
                    $sec   = $row['section'];
                    $label = $section_labels[$sec] ?? $sec;
                    $icon  = $section_icons[$sec] ?? 'circle';
                ?>
                    <div class="about-card">
                        <div class="about-card-head">
                            <span class="about-section-label"><i class="fa fa-<?= $icon ?>"></i> <?= $label ?></span>
                            <button class="btn-edit" onclick='openAboutModal(<?= json_encode($row, JSON_UNESCAPED_UNICODE) ?>)'>
                                <i class="fa fa-pen" style="font-size:10px;"></i> แก้ไข
                            </button>
                        </div>
                        <div class="about-content"><?= htmlspecialchars($row['content'] ?? '(ยังไม่มีเนื้อหา)') ?></div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <!-- Modal: แก้ไขเกี่ยวกับเรา -->
        <div class="modal-overlay" id="aboutModal">
            <div class="modal-box">
                <div class="modal-head">
                    <i class="fa fa-pen-to-square"></i>
                    <span>แก้ไขเนื้อหา</span>
                    <button class="modal-close" onclick="closeModal('aboutModal')"><i class="fa fa-xmark"></i></button>
                </div>
                <form method="post" action="chatbot_edit_process.php" style="display:contents;">
                    <input type="hidden" name="type" value="about">
                    <input type="hidden" name="id" id="ab_id">
                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-label" id="ab_section_label">หัวข้อ</label>
                            <textarea name="content" id="ab_content" class="form-control" rows="7"
                                placeholder="กรอกเนื้อหาที่ต้องการให้แชทบอทนำไปตอบ"></textarea>
                        </div>
                        <p style="font-size:11px;color:#999;margin-top:-8px;">
                            <i class="fa fa-circle-info"></i> แชทบอทจะนำข้อความนี้ไปแสดงเมื่อผู้ใช้ถามเกี่ยวกับหัวข้อนี้
                        </p>
                    </div>
                    <div class="modal-foot">
                        <button type="button" class="btn-cancel" onclick="closeModal('aboutModal')">ยกเลิก</button>
                        <button type="submit" class="btn-save"><i class="fa fa-floppy-disk"></i> บันทึก</button>
                    </div>
                </form>
            </div>
        </div>

    <?php endif; ?>

</div>

<script>
    function openModal(id) {
        document.getElementById(id).classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    function closeModal(id) {
        document.getElementById(id).classList.remove('show');
        document.body.style.overflow = '';
    }

    document.querySelectorAll('.modal-overlay').forEach(el => {
        el.addEventListener('click', function(e) {
            if (e.target === this) closeModal(this.id);
        });
    });

    function previewModalImg(input, previewId, wrapId, boxId) {
        const file = input.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById(previewId).src = e.target.result;
            document.getElementById(wrapId).style.display = 'block';
            document.getElementById(boxId).style.display = 'none';
        };
        reader.readAsDataURL(file);
    }

    function removeModalImg(wrapId, boxId, previewId) {
        document.getElementById(wrapId).style.display = 'none';
        document.getElementById(boxId).style.display = 'block';
        document.getElementById(previewId).src = '';
    }

    function checkMapUrl(input, btnId, hintId) {
        const val = input.value.trim();
        const hint = document.getElementById(hintId);
        const btn = document.getElementById(btnId);
        const ok = val && (
            val.includes('google.com/maps') ||
            val.includes('maps.google.com') ||
            val.includes('goo.gl/maps')
        );
        hint.classList.toggle('show', ok);
        if (btn) {
            btn.href = ok ? val : '#';
            btn.style.display = ok ? 'inline-flex' : 'none';
        }
    }

    function initMapField(inputId, btnId, hintId, val) {
        const inp = document.getElementById(inputId);
        if (!inp) return;
        inp.value = val || '';
        checkMapUrl(inp, btnId, hintId);
    }

    function resetUpload(wrapId, boxId, previewId) {
        document.getElementById(wrapId).style.display = 'none';
        document.getElementById(boxId).style.display = 'block';
        document.getElementById(previewId).src = '';
    }

    function resolveImgSrc(coverImage) {
        if (!coverImage) return '';
        if (coverImage.startsWith('http://') || coverImage.startsWith('https://')) return coverImage;
        return '../' + coverImage;
    }

    function showCurrentImg(rowId, imgId, coverImage) {
        const src = resolveImgSrc(coverImage);
        if (src) {
            document.getElementById(imgId).src = src;
            document.getElementById(rowId).style.display = 'flex';
        } else {
            document.getElementById(rowId).style.display = 'none';
        }
    }

    function openPlaceModal(row) {
        document.getElementById('p_id').value = row.place_id;
        document.getElementById('p_name').value = row.place_name || '';
        document.getElementById('p_desc').value = row.place_description || '';
        document.getElementById('p_highlight').value = row.highlight || '';
        document.getElementById('p_category').value = row.category || '';
        document.getElementById('p_open').value = row.open_time ? row.open_time.substring(0, 5) : '';
        document.getElementById('p_close').value = row.close_time ? row.close_time.substring(0, 5) : '';
        initMapField('p_map_url', 'p_map_btn', 'p_map_hint', row.map_url);
        showCurrentImg('p_cur_img_row', 'p_cur_img', row.cover_image);
        resetUpload('p_img_preview_wrap', 'p_upload_box', 'p_img_preview');
        openModal('placeModal');
    }

    function openRestModal(row) {
        document.getElementById('r_id').value = row.restaurant_id;
        document.getElementById('r_name').value = row.name || '';
        document.getElementById('r_category').value = row.category || '';
        document.getElementById('r_highlight').value = row.highlight || '';
        document.getElementById('r_open').value = row.open_hours ? row.open_hours.substring(0, 5) : '';
        document.getElementById('r_close').value = row.close_hours ? row.close_hours.substring(0, 5) : '';
        initMapField('r_map_url', 'r_map_btn', 'r_map_hint', row.map_url);
        showCurrentImg('r_cur_img_row', 'r_cur_img', row.cover_image);
        resetUpload('r_img_preview_wrap', 'r_upload_box', 'r_img_preview');
        document.getElementById('r_credit').value = row.image_credit || '';
        openModal('restModal');
    }

    function openActModal(row) {
        document.getElementById('a_id').value = row.activity_id;
        document.getElementById('a_name').value = row.name || '';
        document.getElementById('a_type').value = row.type || '';
        document.getElementById('a_desc').value = row.description || '';
        openModal('actModal');
    }

    function openSouModal(row) {
        document.getElementById('s_id').value = row.shop_id;
        document.getElementById('s_name').value = row.name || '';
        document.getElementById('s_desc').value = row.description || '';
        document.getElementById('s_phone').value = row.phone || '';
        document.getElementById('s_open').value = row.open_hours ? row.open_hours.substring(0, 5) : '';
        document.getElementById('s_close').value = row.close_hours ? row.close_hours.substring(0, 5) : '';
        initMapField('s_map_url', 's_map_btn', 's_map_hint', row.map_url);
        showCurrentImg('s_cur_img_row', 's_cur_img', row.cover_image);
        resetUpload('s_img_preview_wrap', 's_upload_box', 's_img_preview');
        document.getElementById('s_credit').value = row.image_credit || '';
        openModal('souModal');
    }

    const sectionLabels = {
        highlight: 'จุดเด่น',
        lifestyle: 'วิถีชีวิต',
        culture: 'วัฒนธรรม',
        contact: 'ติดต่อเรา'
    };

    function openAboutModal(row) {
        document.getElementById('ab_id').value = row.about_id;
        document.getElementById('ab_content').value = row.content || '';
        document.getElementById('ab_section_label').textContent = sectionLabels[row.section] ?? row.section;
        openModal('aboutModal');
    }

    const flashAlert = document.getElementById('flashAlert');
    if (flashAlert) {
        setTimeout(() => {
            flashAlert.classList.add('hiding');
            setTimeout(() => flashAlert.remove(), 500);
        }, 3000);
    }
</script>