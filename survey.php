<?php
session_start();

// ถ้าทำแบบสอบถามไปแล้ว → ข้ามไปหน้าหลักเลย
if (isset($_SESSION['survey_done'])) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ก่อนเข้าชมเว็บไซต์</title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0; 
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #1a7a3c;
            font-family: 'Sarabun', sans-serif;
        }

        /* ===== CARD ===== */
        .survey-card {
            background: #fff;
            border-radius: 24px;
            padding: 48px 52px;
            width: 100%;
            max-width: 560px;
            box-shadow: 0 16px 48px rgba(0, 0, 0, 0.18);
        }

        .survey-title {
            text-align: center;
            font-size: 1.45rem;
            font-weight: 700;
            color: #1a7a3c;
            margin-bottom: 36px;
            line-height: 1.4;
        }

        /* ===== GROUP ===== */
        .survey-group {
            margin-bottom: 28px;
        }

        .survey-group label.group-label {
            display: block;
            font-size: 1rem;
            font-weight: 700;
            color: #1a7a3c;
            margin-bottom: 14px;
        }

        /* ===== RADIO GRID ===== */
        .radio-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px 8px;
        }

        .radio-option {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            color: #222;
        }

        /* Custom radio */
        .radio-option input[type="radio"] {
            appearance: none;
            -webkit-appearance: none;
            width: 24px;
            height: 24px;
            border: 2.5px solid #bbb;
            border-radius: 50%;
            cursor: pointer;
            flex-shrink: 0;
            transition: border-color 0.2s, background 0.2s;
        }

        .radio-option input[type="radio"]:checked {
            border-color: #1a7a3c;
            background: radial-gradient(circle, #1a7a3c 45%, #fff 50%);
        }

        /* ===== ERROR MSG ===== */
        .error-msg {
            color: #d32f2f;
            font-size: 0.85rem;
            margin-top: 6px;
            display: none;
        }

        .error-msg.show {
            display: block;
        }

        /* ===== DIVIDER ===== */
        .divider {
            border: none;
            border-top: 1px solid #e8e8e8;
            margin: 28px 0;
        }

        /* ===== SUBMIT ===== */
        .btn-submit-wrap {
            text-align: center;
            margin-top: 8px;
        }

        .btn-submit {
            background: #1a7a3c;
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: 14px 52px;
            font-size: 1.1rem;
            font-family: 'Sarabun', sans-serif;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
        }

        .btn-submit:hover {
            background: #155f2f;
        }

        .btn-submit:active {
            transform: scale(0.97);
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 480px) {
            .survey-card {
                padding: 36px 28px;
                border-radius: 18px;
                margin: 16px;
            }

            .survey-title {
                font-size: 1.2rem;
            }

            .radio-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>

<div class="survey-card">
    <h1 class="survey-title">กรุณาตอบคำถามก่อนเข้าชมเว็บไซต์</h1>

    <form action="survey_process.php" method="post" id="surveyForm">

        <!-- ===== เพศ ===== -->
        <div class="survey-group">
            <label class="group-label">เพศ</label>
            <div class="radio-grid">
                <label class="radio-option">
                    <input type="radio" name="gender" value="male"> ชาย
                </label>
                <label class="radio-option">
                    <input type="radio" name="gender" value="female"> หญิง
                </label>
                <label class="radio-option">
                    <input type="radio" name="gender" value="unspecified"> ไม่ระบุ
                </label>
            </div>
            <p class="error-msg" id="err-gender">* กรุณาเลือกเพศ</p>
        </div>

        <hr class="divider">

        <!-- ===== อายุ ===== -->
        <div class="survey-group">
            <label class="group-label">อายุ</label>
            <div class="radio-grid">
                <label class="radio-option">
                    <input type="radio" name="age_range" value="12-20"> 12-20
                </label>
                <label class="radio-option">
                    <input type="radio" name="age_range" value="21-30"> 21-30
                </label>
                <label class="radio-option">
                    <input type="radio" name="age_range" value="31-42"> 31-42
                </label>
                <label class="radio-option">
                    <input type="radio" name="age_range" value="43-52"> 43-52
                </label>
                <label class="radio-option">
                    <input type="radio" name="age_range" value="53-60"> 53-60
                </label>
                <label class="radio-option">
                    <input type="radio" name="age_range" value="60+"> 60+
                </label>
            </div>
            <p class="error-msg" id="err-age">* กรุณาเลือกช่วงอายุ</p>
        </div>

        <!-- ===== ปุ่มส่ง ===== -->
        <div class="btn-submit-wrap">
            <button type="submit" class="btn-submit">ส่ง</button>
        </div>

    </form>
</div>

<script>
    document.getElementById('surveyForm').addEventListener('submit', function (e) {
        let valid = true;

        const gender = document.querySelector('input[name="gender"]:checked');
        const age = document.querySelector('input[name="age_range"]:checked');

        const errGender = document.getElementById('err-gender');
        const errAge = document.getElementById('err-age');

        if (!gender) {
            errGender.classList.add('show');
            valid = false;
        } else {
            errGender.classList.remove('show');
        }

        if (!age) {
            errAge.classList.add('show');
            valid = false;
        } else {
            errAge.classList.remove('show');
        }

        if (!valid) e.preventDefault();
    });
</script>

</body>
</html>
