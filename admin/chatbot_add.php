<?php
include 'check_login.php';
include '../config.php';
include 'header.php';

$type = $_GET['type'] ?? '';
$allowed = ['place', 'restaurant', 'activity', 'souvenir'];
if ($type && !in_array($type, $allowed)) $type = '';

$type_meta = [
    'place'      => ['label' => 'สถานที่ท่องเที่ยว / ตลาด', 'icon' => 'fa-map-marker-alt', 'color' => '#2d7a3a'],
    'restaurant' => ['label' => 'ร้านอาหาร',                 'icon' => 'fa-utensils',        'color' => '#d97706'],
    'activity'   => ['label' => 'กิจกรรม',                   'icon' => 'fa-running',          'color' => '#7c3aed'],
    'souvenir'   => ['label' => 'ร้านของฝาก',                'icon' => 'fa-gift',             'color' => '#0369a1'],
];
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
    }

.add-topbar {
    padding: 14px 24px;
    background: #ffffff;
}
    /* ── Top bar ── */
    .add-title {
        text-align: center;
        font-size: 18px;
        font-weight: 800;
        color: #1a1a1a;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 9px;
    }

    .add-title i {
        color: #057C42;
    }

    .btn-back {
        background: #057C42;
        border: none;
        color: #fff;
        padding: 8px 20px;
        border-radius: 50px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 7px;
        transition: background .15s;
        white-space: nowrap;
    }

    .btn-back:hover {
        background: #057C42;
        color: #fff;
    }

    /* ── Content ── */
    .add-content {
        padding: 32px 36px;
        max-width: 1100px;
        margin: 0 auto;
    }

    /* ── Type selector ── */
    .selector-heading {
        text-align: center;
        margin-bottom: 32px;
    }

    .selector-heading h2 {
        font-size: 22px;
        font-weight: 800;
        color: #057C42;
        margin-bottom: 6px;
    }

    .selector-heading p {
        font-size: 13px;
        color: #888;
    }

    .type-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        /* เปลี่ยนจาก 4 → 3 */
        gap: 18px;
        max-width: 820px;
        margin: 0 auto;
    }

    .type-card {
        background: #fff;
        border: 2px solid #e8e8e8;
        border-radius: 20px;
        padding: 32px 20px 26px;
        text-align: center;
        text-decoration: none;
        transition: all .2s cubic-bezier(.34, 1.56, .64, 1);
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 14px;
        position: relative;
        overflow: hidden;
    }

    .type-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        opacity: 0;
        transition: opacity .2s;
    }

    .type-card.place-card::before {
        background: #057C42;
    }

    .type-card.rest-card::before {
        background: #d97706;
    }

    .type-card.act-card::before {
        background: #7c3aed;
    }

    .type-card.sou-card::before {
        background: #0369a1;
    }

    .type-card:hover {
        border-color: transparent;
        box-shadow: 0 10px 32px rgba(0, 0, 0, .12);
        transform: translateY(-4px);
    }

    .type-card:hover::before {
        opacity: 1;
    }

    .type-card-icon {
        width: 72px;
        height: 72px;
        border-radius: 22px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        transition: transform .2s;
    }

    .type-card:hover .type-card-icon {
        transform: scale(1.12) rotate(-4deg);
    }

    .icon-place {
        background: #e8f5eb;
        color: #2d7a3a;
    }

    .icon-restaurant {
        background: #fef3e2;
        color: #d97706;
    }

    .icon-activity {
        background: #f3e8ff;
        color: #7c3aed;
    }

    .icon-souvenir {
        background: #e0f2fe;
        color: #0369a1;
    }

    .type-card-label {
        font-size: 15px;
        font-weight: 800;
        color: #1a1a1a;
    }

    .type-card-desc {
        font-size: 11.5px;
        color: #999;
        line-height: 1.6;
    }

    .type-card-arrow {
        font-size: 11px;
        color: #ccc;
        transition: all .2s;
    }

    .type-card:hover .type-card-arrow {
        color: #2d7a3a;
        transform: translateX(3px);
    }

    /* ── Form card ── */
    .form-card {
        background: #fff;
        border-radius: 20px;
        box-shadow: 0 4px 24px rgba(0, 0, 0, .07);
        overflow: hidden;
    }

    .form-card-head {
        padding: 20px 28px;
        display: flex;
        align-items: center;
        gap: 14px;
        border-bottom: 2px solid #f0f0f0;
    }

    .form-card-head-icon {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }

    .form-card-head-text h2 {
        font-size: 16px;
        font-weight: 800;
        color: #1a1a1a;
    }

    .form-card-head-text p {
        font-size: 12px;
        color: #999;
        margin-top: 3px;
    }

    .form-card-body {
        padding: 28px;
    }

    /* ── Sections ── */
    .form-section {
        margin-bottom: 28px;
    }

    .form-section:last-child {
        margin-bottom: 0;
    }

    .section-divider {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 18px;
    }

    .section-divider-icon {
        width: 32px;
        height: 32px;
        background: #e8f5eb;
        color: #2d7a3a;
        border-radius: 9px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        flex-shrink: 0;
    }

    .section-divider-label {
        font-size: 13px;
        font-weight: 800;
        color: #1a1a1a;
    }

    .section-divider-line {
        flex: 1;
        height: 1.5px;
        background: #f0f0f0;
    }

    .form-grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .form-label {
        font-size: 12px;
        font-weight: 700;
        color: #444;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .form-label .req {
        color: #e74c3c;
    }

    .form-label .hint {
        font-weight: 400;
        color: #bbb;
        font-size: 11px;
    }

    .form-control {
        width: 100%;
        padding: 11px 14px;
        border: 1.5px solid #e8e8e8;
        border-radius: 11px;
        font-size: 13px;
        color: #1a1a1a;
        font-family: inherit;
        background: #fafafa;
        transition: all .15s;
    }

    .form-control:focus {
        outline: none;
        border-color: #2d7a3a;
        background: #fff;
        box-shadow: 0 0 0 3px rgba(45, 122, 58, .08);
    }

    .form-control::placeholder {
        color: #ccc;
    }

    textarea.form-control {
        min-height: 110px;
        resize: vertical;
        line-height: 1.6;
    }

    select.form-control {
        cursor: pointer;
    }

    .map-hint {
        background: linear-gradient(135deg, #f0f9f1, #e8f5eb);
        border: 1px solid #c8e6ce;
        border-radius: 10px;
        padding: 12px 16px;
        font-size: 12px;
        color: #2d7a3a;
        display: flex;
        align-items: flex-start;
        gap: 9px;
        margin-top: 4px;
        line-height: 1.6;
    }

    .map-hint i {
        margin-top: 1px;
        flex-shrink: 0;
    }

    /* Upload */
    .upload-area {
        border: 2px dashed #ddd;
        border-radius: 14px;
        background: #fafafa;
        padding: 36px 24px;
        text-align: center;
        cursor: pointer;
        transition: all .2s;
        position: relative;
        overflow: hidden;
    }

    .upload-area:hover {
        border-color: #2d7a3a;
        background: #f0f9f1;
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
        width: 52px;
        height: 52px;
        background: #e8f5eb;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        color: #2d7a3a;
        margin: 0 auto 12px;
        transition: transform .2s;
    }

    .upload-area:hover .upload-icon {
        transform: scale(1.1);
    }

    .upload-title {
        font-size: 13px;
        font-weight: 700;
        color: #444;
        margin-bottom: 4px;
    }

    .upload-sub {
        font-size: 11px;
        color: #bbb;
    }

    .upload-sub strong {
        color: #2d7a3a;
    }

    .img-preview {
        width: 100%;
        max-height: 220px;
        border-radius: 12px;
        object-fit: cover;
        display: none;
        border: 2px solid #e8e8e8;
        margin-top: 10px;
    }

    /* Footer */
    .form-card-foot {
        padding: 20px 28px;
        border-top: 1.5px solid #f0f0f0;
        background: #fafafa;
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 12px;
    }

.btn-cancel {
    background: #e74c3c;
    border: none;
    color: #fff;
    padding: 12px 32px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.btn-cancel:hover {
    background: #c0392b;
    color: #fff;
}

.btn-save {
    background: #057C42;
    color: #fff;
    border: none;
    padding: 12px 32px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 9px;
    box-shadow: none;
    letter-spacing: 0.02em;
}

.btn-save:hover {
    background: #235e2c;
    color: #fff;
}

.btn-save:active {
    background: #1a4d23;
}

.btn-save i {
    font-size: 15px;
}
</style>

<div class="add-wrapper">

    <div class="add-topbar">
        <a href="<?= $type ? 'chatbot_add.php' : 'chatbot_manage.php' ?>" class="btn-back">
            ย้อนกลับ
        </a>
    </div>

    <div class="add-content">

        <?php if (!$type): ?>
            <!-- Step 1: เลือกประเภท -->
            <div class="selector-heading">
                <h2>เลือกประเภทข้อมูลที่ต้องการเพิ่ม</h2>
                <p>ข้อมูลที่เพิ่มจะถูกนำไปใช้ตอบคำถามในแชทบอทโดยอัตโนมัติ</p>
            </div>
            <div class="type-grid">
                <a href="?type=place" class="type-card place-card">
                    <div class="type-card-icon icon-place"><i class="fa fa-map-marker-alt"></i></div>
                    <div>
                        <div class="type-card-label">สถานที่</div>
                        <div class="type-card-desc">วัด ตลาด แหล่งท่องเที่ยว<br>สถานที่สำคัญ</div>
                    </div>
                    <div class="type-card-arrow"><i class="fa fa-arrow-right"></i></div>
                </a>
                <a href="?type=restaurant" class="type-card rest-card">
                    <div class="type-card-icon icon-restaurant"><i class="fa fa-utensils"></i></div>
                    <div>
                        <div class="type-card-label">ร้านอาหาร</div>
                        <div class="type-card-desc">อาหารคาว อาหารหวาน<br>ขนมพื้นบ้าน</div>
                    </div>
                    <div class="type-card-arrow"><i class="fa fa-arrow-right"></i></div>
                </a>
                <a href="?type=souvenir" class="type-card sou-card">
                    <div class="type-card-icon icon-souvenir"><i class="fa fa-gift"></i></div>
                    <div>
                        <div class="type-card-label">ของฝาก</div>
                        <div class="type-card-desc">ร้านของฝาก ขนม<br>ผลิตภัณฑ์ชุมชน</div>
                    </div>
                    <div class="type-card-arrow"><i class="fa fa-arrow-right"></i></div>
                </a>
            </div>

        <?php else:
            $meta    = $type_meta[$type];
            $iconCls = ['place' => 'icon-place', 'restaurant' => 'icon-restaurant', 'activity' => 'icon-activity', 'souvenir' => 'icon-souvenir'][$type];
        ?>

            <div class="form-card">
                <div class="form-card-head">
                    <div class="form-card-head-icon <?= $iconCls ?>"><i class="fa <?= $meta['icon'] ?>"></i></div>
                    <div class="form-card-head-text">
                        <h2>เพิ่ม<?= $meta['label'] ?>ใหม่</h2>
                        <p>กรอกข้อมูลให้ครบถ้วน เพื่อให้แชทบอทตอบคำถามได้อย่างแม่นยำ</p>
                    </div>
                </div>

                <form method="post" action="chatbot_add_process.php" enctype="multipart/form-data">
                    <input type="hidden" name="type" value="<?= $type ?>">
                    <div class="form-card-body">

                        <?php
                        $ic = [
                            'place'      => ['bg' => '#e8f5eb', 'cl' => '#2d7a3a'],
                            'restaurant' => ['bg' => '#fef3e2', 'cl' => '#d97706'],
                            'activity'   => ['bg' => '#f3e8ff', 'cl' => '#7c3aed'],
                            'souvenir'   => ['bg' => '#e0f2fe', 'cl' => '#0369a1'],
                        ][$type];
                        $divStyle = "style=\"background:{$ic['bg']};color:{$ic['cl']};\"";

                        function secDiv($icon, $label, $divStyle)
                        {
                            echo "<div class=\"section-divider\">";
                            echo "<div class=\"section-divider-icon\" $divStyle><i class=\"fa $icon\"></i></div>";
                            echo "<span class=\"section-divider-label\">$label</span>";
                            echo "<div class=\"section-divider-line\"></div>";
                            echo "</div>";
                        }
                        ?>

                        <?php if ($type === 'place'): ?>
                            <div class="form-section">
                                <?php secDiv('fa-circle-info', 'ข้อมูลทั่วไป', $divStyle); ?>
                                <div class="form-grid-2" style="margin-bottom:16px;">
                                    <div class="form-group">
                                        <label class="form-label">ชื่อสถานที่ <span class="req">*</span></label>
                                        <input type="text" name="place_name" class="form-control" placeholder="เช่น วัดท่าคอย" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">หมวดหมู่ <span class="req">*</span></label>
                                        <select name="category" class="form-control" required>
                                            <option value="">-- เลือกหมวดหมู่ --</option>
                                            <option value="travel">🏛 สถานที่ท่องเที่ยว</option>
                                            <option value="eat">🍜 ร้านอาหาร / ตลาด</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group" style="margin-bottom:16px;">
                                    <label class="form-label">ประวัติโดยย่อ</label>
                                    <textarea name="place_description" class="form-control" placeholder="เล่าประวัติหรือที่มาของสถานที่นี้..."></textarea>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">จุดเด่น <span class="hint">(สิ่งที่น่าสนใจ / ไฮไลต์)</span></label>
                                    <textarea name="highlight" class="form-control" rows="3" placeholder="เช่น อุโบสถเก่าแก่ 100 ปี อุทยานปลาริมน้ำ"></textarea>
                                </div>
                            </div>
                            <div class="form-section">
                                <?php secDiv('fa-clock', 'เวลาเปิด-ปิด', $divStyle); ?>
                                <div class="form-grid-2">
                                    <div class="form-group"><label class="form-label">เวลาเปิด</label><input type="time" name="open_time" class="form-control" value="08:00"></div>
                                    <div class="form-group"><label class="form-label">เวลาปิด</label><input type="time" name="close_time" class="form-control" value="17:00"></div>
                                </div>
                            </div>
                            <div class="form-section">
                                <?php secDiv('fa-location-dot', 'แผนที่', $divStyle); ?>
                                <div class="form-group" style="margin-bottom:10px;">
                                    <label class="form-label">ลิงก์ Google Maps</label>
                                    <input type="url" name="map_url" class="form-control" placeholder="เช่น https://maps.google.com/?q=...">
                                </div>
                                <div class="map-hint"><i class="fa fa-lightbulb"></i><span>เปิด <strong>Google Maps</strong> → ค้นหาสถานที่ → คลิก Share → คัดลอกลิงก์</span></div>
                            </div>
                            <div class="form-section">
                                <?php secDiv('fa-image', 'รูปภาพหลัก', $divStyle); ?>
                                <div class="upload-area" id="box1">
                                    <input type="file" name="cover_image" accept="image/*" onchange="previewImg(this,'prev1','box1')">
                                    <div class="upload-icon"><i class="fa fa-cloud-arrow-up"></i></div>
                                    <div class="upload-title">ลากไฟล์มาวาง หรือคลิกเพื่อเลือก</div>
                                    <div class="upload-sub">JPG, PNG, WEBP — <strong>ไม่เกิน 5MB</strong></div>
                                </div>
                                <img id="prev1" class="img-preview" alt="preview">
                            </div>

                        <?php elseif ($type === 'restaurant'): ?>
                            <div class="form-section">
                                <?php secDiv('fa-circle-info', 'ข้อมูลร้าน', $divStyle); ?>
                                <div class="form-grid-2" style="margin-bottom:16px;">
                                    <div class="form-group"><label class="form-label">ชื่อร้าน <span class="req">*</span></label><input type="text" name="name" class="form-control" placeholder="เช่น ผัดไทย 100 ปี" required></div>
                                    <div class="form-group">
                                        <label class="form-label">หมวดหมู่ <span class="req">*</span></label>
                                        <select name="category" class="form-control" required>
                                            <option value="">-- เลือกหมวดหมู่ --</option>
                                            <option value="อาหารคาว">🍖 อาหารคาว</option>
                                            <option value="อาหารหวาน">🍮 อาหารหวาน</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group" style="margin-bottom:16px;"><label class="form-label">จุดเด่น / เมนูแนะนำ</label><textarea name="highlight" class="form-control" rows="3" placeholder="เช่น ผัดไทยสูตรโบราณ คิวเยอะ หมดไว"></textarea></div>
                                <div class="form-group"><label class="form-label">เบอร์โทรศัพท์</label><input type="text" name="phone" class="form-control" placeholder="เช่น 090-000-0000"></div>
                            </div>
                            <div class="form-section">
                                <?php secDiv('fa-clock', 'เวลาเปิด-ปิด', $divStyle); ?>
                                <div class="form-grid-2">
                                    <div class="form-group"><label class="form-label">เวลาเปิด</label><input type="time" name="open_hours" class="form-control" value="08:00"></div>
                                    <div class="form-group"><label class="form-label">เวลาปิด</label><input type="time" name="close_hours" class="form-control" value="17:00"></div>
                                </div>
                            </div>
                            <div class="form-section">
                                <?php secDiv('fa-location-dot', 'แผนที่', $divStyle); ?>
                                <div class="form-group" style="margin-bottom:10px;">
                                    <label class="form-label">ลิงก์ Google Maps</label>
                                    <input type="url" name="map_url" class="form-control" placeholder="เช่น https://maps.google.com/?q=...">
                                </div>
                                <div class="map-hint"><i class="fa fa-lightbulb"></i><span>เปิด <strong>Google Maps</strong> → ค้นหาสถานที่ → คลิก Share → คัดลอกลิงก์</span></div>
                            </div>
                            <div class="form-section">
                                <?php secDiv('fa-image', 'รูปภาพหลัก', $divStyle); ?>
                                <div class="upload-area" id="box1">
                                    <input type="file" name="cover_image" accept="image/*" onchange="previewImg(this,'prev1','box1')">
                                    <div class="upload-icon" style="background:<?= $ic['bg'] ?>;color:<?= $ic['cl'] ?>;"><i class="fa fa-cloud-arrow-up"></i></div>
                                    <div class="upload-title">ลากไฟล์มาวาง หรือคลิกเพื่อเลือก</div>
                                    <div class="upload-sub">JPG, PNG, WEBP — <strong>ไม่เกิน 5MB</strong></div>
                                </div>
                                <img id="prev1" class="img-preview" alt="preview">
                            </div>

                        <?php elseif ($type === 'activity'): ?>
                            <div class="form-section">
                                <?php secDiv('fa-circle-info', 'ข้อมูลกิจกรรม', $divStyle); ?>
                                <div class="form-grid-2" style="margin-bottom:16px;">
                                    <div class="form-group">
                                        <label class="form-label">ชื่อกิจกรรม <span class="req">*</span></label>
                                        <input type="text" name="name" class="form-control" placeholder="เช่น ไหว้พระในท่ายาง" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="form-label">ประเภทกิจกรรม <span class="req">*</span></label>
                                        <select name="act_type" class="form-control" required> <!-- เปลี่ยนตรงนี้ -->
                                            <option value="">-- เลือกประเภท --</option>
                                            <option value="ไหว้พระ">🙏 ไหว้พระ</option>
                                            <option value="ถ่ายรูป">📷 ถ่ายรูป</option>
                                            <option value="ให้อาหารปลา">🐟 ให้อาหารปลา</option>
                                            <option value="ตะลอนกิน">🍽 ตะลอนกิน</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">สถานที่ที่เกี่ยวข้อง <span class="hint">(คั่นด้วย ,)</span></label>
                                    <textarea name="description" class="form-control" rows="3" placeholder="เช่น วัดท่าคอย, ศาลเจ้าพ่อกวนอู"></textarea>
                                </div>
                            </div>

                        <?php elseif ($type === 'souvenir'): ?>
                            <div class="form-section">
                                <?php secDiv('fa-circle-info', 'ข้อมูลร้าน', $divStyle); ?>
                                <div class="form-group" style="margin-bottom:16px;"><label class="form-label">ชื่อร้าน <span class="req">*</span></label><input type="text" name="name" class="form-control" placeholder="เช่น ทองม้วนแม่เล็ก" required></div>
                                <div class="form-group" style="margin-bottom:16px;"><label class="form-label">รายละเอียด / สินค้าที่ขาย</label><textarea name="description" class="form-control" rows="3" placeholder="เช่น ทองม้วนกรอบ หอมกะทิ สูตรดั้งเดิม"></textarea></div>
                                <div class="form-group"><label class="form-label">เบอร์โทรศัพท์</label><input type="text" name="phone" class="form-control" placeholder="เช่น 090-000-0000"></div>
                            </div>
                            <div class="form-section">
                                <?php secDiv('fa-clock', 'เวลาเปิด-ปิด', $divStyle); ?>
                                <div class="form-grid-2">
                                    <div class="form-group"><label class="form-label">เวลาเปิด</label><input type="time" name="open_hours" class="form-control" value="08:00"></div>
                                    <div class="form-group"><label class="form-label">เวลาปิด</label><input type="time" name="close_hours" class="form-control" value="17:00"></div>
                                </div>
                            </div>
                            <div class="form-section">
                                <?php secDiv('fa-location-dot', 'แผนที่', $divStyle); ?>
                                <div class="form-group" style="margin-bottom:10px;">
                                    <label class="form-label">ลิงก์ Google Maps</label>
                                    <input type="url" name="map_url" class="form-control" placeholder="เช่น https://maps.google.com/?q=...">
                                </div>
                                <div class="map-hint"><i class="fa fa-lightbulb"></i><span>เปิด <strong>Google Maps</strong> → ค้นหาสถานที่ → คลิก Share → คัดลอกลิงก์</span></div>
                            </div>
                            <div class="form-section">
                                <?php secDiv('fa-image', 'รูปภาพหลัก', $divStyle); ?>
                                <div class="upload-area" id="box1">
                                    <input type="file" name="cover_image" accept="image/*" onchange="previewImg(this,'prev1','box1')">
                                    <div class="upload-icon" style="background:<?= $ic['bg'] ?>;color:<?= $ic['cl'] ?>;"><i class="fa fa-cloud-arrow-up"></i></div>
                                    <div class="upload-title">ลากไฟล์มาวาง หรือคลิกเพื่อเลือก</div>
                                    <div class="upload-sub">JPG, PNG, WEBP — <strong>ไม่เกิน 5MB</strong></div>
                                </div>
                                <img id="prev1" class="img-preview" alt="preview">
                            </div>
                        <?php endif; ?>

                    </div><!-- /body -->

                    <div class="form-card-foot">
                        <a href="?" class="btn-cancel"><i class="fa fa-xmark"></i> ยกเลิก</a>
                        <button type="submit" class="btn-save"><i class="fa fa-floppy-disk"></i> บันทึกข้อมูล</button>
                    </div>
                </form>
            </div><!-- /form-card -->

        <?php endif; ?>
    </div><!-- /add-content -->
</div>

<script>
    function previewImg(input, previewId, boxId) {
        const file = input.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = e => {
            const img = document.getElementById(previewId);
            const box = document.getElementById(boxId);
            img.src = e.target.result;
            img.style.display = 'block';
            box.style.display = 'none';
        };
        reader.readAsDataURL(file);
    }
</script>